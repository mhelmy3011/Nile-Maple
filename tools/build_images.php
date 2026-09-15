<?php
/**
 * Image pipeline (doc 04 §4) — the only place that touches pixels. Runs on the build machine
 * (or CI), never at request time.
 *
 *   php tools/build_images.php                 register + derive every product photo
 *   php tools/build_images.php --force         rebuild variants even when they already exist
 *   php tools/build_images.php --engine=gd     force an engine: auto | gd | convert | imagick
 *   php tools/build_images.php --sizes=320,480,640,800
 *
 * Source: docs/data/media/{category}/{image}.jpg — produced by
 *   python3 tools/extract_content.py --media          (images live inside the catalogue .docx)
 *
 * Output: public_html/assets/media/{category}/{slug}-{w}.{avif|webp|jpg} + og/{slug}.jpg (1200×630)
 * plus the media / media_variant / media_i18n rows the dashboard and templates read.
 * Formats are emitted only when the local GD/Imagick build supports them, so a host without
 * AVIF simply produces fewer rows (doc 07 risk register) — nothing else changes.
 */
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

use Nm\Db;
use Nm\Img;
use Nm\Slug;

$force = in_array('--force', $argv ?? [], true);
$sizes = Img::WIDTHS;
$engine = 'auto';
$collect = false;
foreach ($argv ?? [] as $a) {
    if (str_starts_with($a, '--engine=')) $engine = substr($a, 9);
    if (str_starts_with($a, '--sizes=')) $sizes = array_map('intval', explode(',', substr($a, 8)));
    if ($a === '--collect') $collect = true;
}
$srcRoot = nm_path('docs/data/media');
$outRoot = rtrim((string) cfg('paths.public'), '/') . '/assets/media';
if (!is_dir($srcRoot)) {
    fwrite(STDERR, "no media source at $srcRoot — run: python3 tools/extract_content.py --media\n");
    exit(1);
}
if (!is_dir($outRoot)) mkdir($outRoot, 0775, true);

$manifest = json_decode((string) file_get_contents(nm_path('docs/data/content-manifest.json')), true);
if (!$manifest) { fwrite(STDERR, "content-manifest.json missing\n"); exit(1); }

/* ── engine ─────────────────────────────────────────────────────────────────────────── */
$haveGd = function_exists('imagecreatetruecolor');
$haveImagick = class_exists(\Imagick::class);
/* engine=node: plan only — pixels are encoded by tools/img_sharp.mjs (native libvips, fast AVIF)
   and recorded afterwards with --collect. Used in this sandbox/CI; a real PHP host uses gd. */
if ($collect) {
    /* pass B: record real byte sizes for variants the node encoder produced */
    $fixed = $gone = 0;
    foreach (Db::all('SELECT media_id, fmt, w, path FROM media_variant WHERE bytes <= 0') as $r) {
        $abs = "$outRoot/{$r['path']}";
        if (is_file($abs)) {
            Db::run('UPDATE media_variant SET bytes=? WHERE media_id=? AND fmt=? AND w=?',
                [(int) filesize($abs), $r['media_id'], $r['fmt'], $r['w']]);
            $fixed++;
        } else {
            Db::run('DELETE FROM media_variant WHERE media_id=? AND fmt=? AND w=?',
                [$r['media_id'], $r['fmt'], $r['w']]);
            $gone++;
        }
    }
    $ogMissing = 0;
    foreach (Db::all("SELECT DISTINCT substr(path, 1, instr(path, '/') - 1) AS cat FROM media_variant") ?: [] as $_) { }
    Nm\Cache::forgetGroup('data');
    $du = 0;
    if (is_dir($outRoot)) foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($outRoot, FilesystemIterator::SKIP_DOTS)) as $f) { if ($f->isFile()) $du += $f->getSize(); }
    printf("collect: %d variants sized, %d empty rows removed · %.1f MB in %s\n", $fixed, $gone, $du / 1048576, $outRoot);
    $total = (int) Db::val('SELECT COUNT(*) FROM products WHERE card_media_id IS NOT NULL');
    echo "products with an image: $total / " . (int) Db::val('SELECT COUNT(*) FROM products') . "\n";
    /* format-parity invariant (D-04 regression lock): avif and webp ladders must match */
    $bad = Db::all("SELECT v.media_id, m.source_ref,
            GROUP_CONCAT(CASE WHEN fmt='avif' THEN w END) AS av, GROUP_CONCAT(CASE WHEN fmt='webp' THEN w END) AS wp
            FROM media_variant v JOIN media m ON m.id=v.media_id GROUP BY v.media_id
            HAVING (av IS NOT NULL OR wp IS NOT NULL) AND COALESCE(av,'') != COALESCE(wp,'')");
    if ($bad) {
        fwrite(STDERR, "format-parity violation (avif ladder != webp ladder) for " . count($bad) . " media:\n");
        foreach (array_slice($bad, 0, 10) as $b) fwrite(STDERR, "  {$b['source_ref']} avif[{$b['av']}] webp[{$b['wp']}]\n");
        exit(1);
    }
    echo "format parity: avif ladder == webp ladder for every media item\n";
    exit($total >= (int) Db::val('SELECT COUNT(*) FROM products') ? 0 : 1);
}
$engine = match (true) {
    $engine !== 'auto' => $engine,
    $haveGd => 'gd',
    $haveImagick => 'imagick',
    default => 'convert',
};
$formats = [];
foreach (['avif' => 55, 'webp' => 78, 'jpg' => 82] as $fmt => $q) {
    $supported = match ($engine) {
        'node'    => true,                                   /* libvips encodes all three */
        'gd'      => Img::can($fmt),
        'imagick' => in_array(strtoupper($fmt), (new \Imagick())->getSupportedFormats(), true),
        default   => true,                                   /* `convert` delegates to ImageMagick CLI */
    };
    if ($supported) $formats[$fmt] = $q;
}
if (!$formats) { $formats['jpg'] = 82; }
/* JPEG is the safety net every <picture> needs, even on a stripped GD build */
if (!isset($formats['jpg'])) $formats['jpg'] = 82;
ksort($formats);

echo "engine=$engine formats=" . implode(',', array_keys($formats)) . ' widths=' . implode(',', $sizes) . "\n";

/** write one variant; returns bytes (0 = deferred/failed) */
$jobsFile = nm_path('storage/tmp/img-jobs-' . getmypid() . '.jsonl');
$jobsFp = null;
$emit = static function (string $srcFile, string $outFile, int $w, ?int $h, string $fmt, int $q) use ($engine, &$jobsFp, $jobsFile): int {
    if (!is_dir(dirname($outFile))) mkdir(dirname($outFile), 0775, true);
    if ($engine === 'node') {
        /* plan-only: the native encoder (tools/img_sharp.mjs) does the pixels, --collect records bytes */
        if (!$jobsFp) {
            if (!is_dir(dirname($jobsFile))) mkdir(dirname($jobsFile), 0775, true);
            $jobsFp = fopen($jobsFile, 'w');
        }
        fwrite($jobsFp, json_encode(['src' => $srcFile, 'dst' => $outFile, 'w' => $w, 'h' => $h, 'fmt' => $fmt, 'q' => $q]) . "\n");
        return -1;   /* pending — bytes recorded by --collect */
    }
    if ($engine === 'gd') {
        /* rolling single-entry decode cache: 158 × 800×800 truecolor would not fit in memory */
        static $cacheFile = null; static $cacheIm = null;
        if ($cacheFile !== $srcFile) {
            if ($cacheIm instanceof \GdImage) imagedestroy($cacheIm);
            $cacheFile = $srcFile; $cacheIm = Img::load($srcFile);
        }
        $im = $cacheIm;
        if (!$im) return 0;
        $dst = $h && $h !== $w ? Img::crop($im, $w, $h) : Img::resize($im, $w, $w);
        $bytes = Img::save($dst, $outFile, $fmt, $q);
        imagedestroy($dst);
        return $bytes;
    }
    if ($engine === 'imagick') {
        $im = new \Imagick($srcFile);
        $im->setImageBackgroundColor('#FDF9F6');
        $geo = $h && $h !== $w ? "{$w}x{$h}^" : "{$w}x{$w}";
        $im->resizeImage($w, $h ?: $w, \Imagick::FILTER_LANCZOS, 1, true);
        $im->stripImage();
        $im->setImageCompressionQuality($q);
        $im->setImageFormat($fmt === 'jpg' ? 'jpeg' : $fmt);
        $im->writeImage($outFile);
        $im->clear();
        return (int) @filesize($outFile);
    }
    $geo = $h && $h !== $w ? "{$w}x{$h}^" : "{$w}x{$w}";
    $cmd = sprintf('convert %s -resize %s -gravity center -extent %sx%s -strip -interlace Plane -quality %d %s 2>/dev/null',
        escapeshellarg($srcFile), escapeshellarg($geo), $w, $h ?: $w, $q, escapeshellarg($outFile));
    exec($cmd);
    return (int) @filesize($outFile);
};

/* ── pass 1: media rows + variants ──────────────────────────────────────────────────── */
$t0 = microtime(true);
$done = $reused = $missing = $linked = 0;
$missingList = [];

foreach ($manifest['categories'] as $mc) {
    $ck = $mc['key'];
    $catId = (int) Db::val('SELECT id FROM categories WHERE code=?', [$ck]);
    foreach ($mc['products'] as $p) {
        $file = "$srcRoot/$ck/{$p['image']}";
        if (!is_file($file)) { $missing++; $missingList[] = "$ck/{$p['image']}"; continue; }

        $pid = (int) Db::val('SELECT id FROM products WHERE category_id=? AND source_index=?', [$catId, $p['index']]);
        if (!$pid) continue;
        $slug = (string) (Db::val("SELECT i.slug FROM product_i18n i WHERE i.product_id=? AND i.lang='en'", [$pid])
            ?: Slug::make(ucwords(strtolower((string) $p['name_en'])), 'en'));

        $info = @getimagesize($file) ?: [800, 800, 'mime' => 'image/jpeg'];
        $mediaId = (int) Db::val('SELECT id FROM media WHERE source_ref=?', ["$ck/{$p['image']}"]);
        $base = ['filename' => (string) $p['image'], 'mime' => image_type_to_mime_type($info[2] ?? IMAGETYPE_JPEG),
                 'width' => (int) $info[0], 'height' => (int) $info[1], 'bytes' => (int) filesize($file),
                 'focal_x' => 0.5, 'focal_y' => 0.5, 'source_ref' => "$ck/{$p['image']}"];
        if ($mediaId) Db::run('UPDATE media SET filename=?,mime=?,width=?,height=?,bytes=?,focal_x=?,focal_y=? WHERE id=?',
            [...array_values(array_slice($base, 0, 7)), $mediaId]);
        else { Db::run('INSERT INTO media(filename,mime,width,height,bytes,focal_x,focal_y,source_ref) VALUES(?,?,?,?,?,?,?,?)',
            array_values($base)); $mediaId = Db::lastId(); }

        foreach ($sizes as $w) {
            foreach ($formats as $fmt => $q) {
                /* D-04 fix: the AVIF and WebP ladders are identical (320/480/640/800). JPEG stays the
                   640/800 safety net for <picture> fallback — browsers that old are rare and get a
                   single adequate size. A shorter ladder below 640 was the defect: mobile browsers
                   matching the AVIF source were forced to download the 640 px file. */
                if ($fmt === 'jpg' && $w < 640) continue;
                $rel = "$ck/$slug-$w.$fmt";
                $abs = "$outRoot/$rel";
                $exists = is_file($abs);
                $prevBytes = Db::val('SELECT bytes FROM media_variant WHERE media_id=? AND fmt=? AND w=?', [$mediaId, $fmt, $w]);
                if ($exists && !$force && $prevBytes !== null && (int) $prevBytes > 0) {
                    $reused++; continue;
                }
                $bytes = $emit($file, $abs, $w, null, $fmt, $q);
                if ($bytes === -1) {   /* deferred to the node encoder */
                    Db::upsert('media_variant', ['media_id' => $mediaId, 'fmt' => $fmt, 'w' => $w, 'path' => $rel, 'bytes' => -1],
                        ['media_id', 'fmt', 'w']);
                    $done++;
                    continue;
                }
                if (!$bytes) { @unlink($abs); $missing++; continue; }
                Db::upsert('media_variant', ['media_id' => $mediaId, 'fmt' => $fmt, 'w' => $w, 'path' => $rel, 'bytes' => $bytes],
                    ['media_id', 'fmt', 'w']);
                $done++;
            }
        }

        /* social card: 1200×630 focal crop of the same square (doc 04 §4) */
        $ogRel = "og/$slug.jpg";
        if ($force || !is_file("$outRoot/$ogRel")) {
            $b = $emit($file, "$outRoot/$ogRel", 1200, 630, 'jpg', 80);
            if ($b) $done++; else @unlink("$outRoot/$ogRel");
        }

        /* alt text per locale — required before publish (doc 04 §7), generated from the product name */
        foreach (cfg('langs') as $l) {
            $name = (string) Db::val('SELECT name FROM product_i18n WHERE product_id=? AND lang=?', [$pid, $l]);
            $catName = (string) Db::val('SELECT i.name FROM categories c JOIN category_i18n i ON i.category_id=c.id AND i.lang=? WHERE c.id=?', [$l, $catId]);
            $alt = trim(($name ?: ucwords(strtolower((string) $p['name_en']))) . ($catName ? ' — ' . $catName : ''));
            Db::upsert('media_i18n', ['media_id' => $mediaId, 'lang' => $l, 'alt' => mb_substr($alt, 0, 220)], ['media_id', 'lang']);
        }
        if (!Db::val('SELECT card_media_id FROM products WHERE id=?', [$pid])) {
            Db::run('UPDATE products SET card_media_id=? WHERE id=?', [$mediaId, $pid]);
            $linked++;
        }
    }
}

/* ── pass 3: composed brand imagery (hero collage, D-03) ─────────────────────────────
 * tools/build_hero.mjs composes the four-division studio mosaic used by the home hero.
 * It gets the full variant ladder + per-locale alt + OG card like any product photo. */
$heroRel = 'brand/hero-collage.jpg';
$heroSrc = "$srcRoot/$heroRel";
if (is_file($heroSrc)) {
    $info = @getimagesize($heroSrc) ?: [2400, 1350, 'mime' => 'image/jpeg'];
    $mediaId = (int) Db::val('SELECT id FROM media WHERE source_ref=?', [$heroRel]);
    $base = ['filename' => 'hero-collage.jpg', 'mime' => 'image/jpeg', 'width' => (int) $info[0], 'height' => (int) $info[1],
             'bytes' => (int) filesize($heroSrc), 'focal_x' => 0.5, 'focal_y' => 0.5, 'source_ref' => $heroRel];
    if ($mediaId) Db::run('UPDATE media SET filename=?,mime=?,width=?,height=?,bytes=?,focal_x=?,focal_y=? WHERE id=?',
        [...array_values(array_slice($base, 0, 7)), $mediaId]);
    else { Db::run('INSERT INTO media(filename,mime,width,height,bytes,focal_x,focal_y,source_ref) VALUES(?,?,?,?,?,?,?,?)', array_values($base)); $mediaId = Db::lastId(); }
    foreach ($sizes as $w) {
        $hh = (int) round($w * 9 / 16);                      /* keep the 16:9 art direction */
        foreach ($formats as $fmt => $q) {
            $rel = "brand/hero-$w.$fmt";
            $abs = "$outRoot/$rel";
            $prevBytes = Db::val('SELECT bytes FROM media_variant WHERE media_id=? AND fmt=? AND w=?', [$mediaId, $fmt, $w]);
            if (is_file($abs) && !$force && $prevBytes !== null && (int) $prevBytes > 0) { $reused++; continue; }
            $bytes = $emit($heroSrc, $abs, $w, $hh, $fmt, $q);
            if ($bytes === -1) { Db::upsert('media_variant', ['media_id' => $mediaId, 'fmt' => $fmt, 'w' => $w, 'path' => $rel, 'bytes' => -1], ['media_id', 'fmt', 'w']); $done++; continue; }
            if (!$bytes) { @unlink($abs); $missing++; continue; }
            Db::upsert('media_variant', ['media_id' => $mediaId, 'fmt' => $fmt, 'w' => $w, 'path' => $rel, 'bytes' => $bytes], ['media_id', 'fmt', 'w']);
            $done++;
        }
    }
    foreach (cfg('langs') as $l) {
        $alt = ['en' => 'Nile-Maple product divisions: fresh fruits, fresh vegetables, frozen products and canned foods',
                'ar' => 'قطاعات نيل مابل: فواكه طازجة وخضروات طازجة ومنتجات مجمدة وأغذية معلبة',
                'fr' => 'Divisions Nile-Maple : fruits frais, légumes frais, produits surgelés et conserves'][$l] ?? '';
        Db::upsert('media_i18n', ['media_id' => $mediaId, 'lang' => $l, 'alt' => $alt], ['media_id', 'lang']);
    }
    printf("hero collage: media #%d with a %d-format ladder\n", $mediaId, count($formats));
}

/* ── pass 3.5: company-profile photography (profile/image3-6.jpg) ────────────────────
 * Real photos from the company profile DOCX (mango, broccoli, frozen strawberries, olives),
 * referenced by about:* blocks and blog covers. Full ladder + per-locale alt, like products. */
foreach (glob("$srcRoot/profile/image*.jpg") ?: [] as $pf) {
    $rel = 'profile/' . basename($pf);
    $info = @getimagesize($pf) ?: [800, 800, 'mime' => 'image/jpeg'];
    $mediaId = (int) Db::val('SELECT id FROM media WHERE source_ref=?', [$rel]);
    $base = ['filename' => basename($pf), 'mime' => 'image/jpeg', 'width' => (int) $info[0], 'height' => (int) $info[1],
             'bytes' => (int) filesize($pf), 'focal_x' => 0.5, 'focal_y' => 0.5, 'source_ref' => $rel];
    if ($mediaId) Db::run('UPDATE media SET filename=?,mime=?,width=?,height=?,bytes=?,focal_x=?,focal_y=? WHERE id=?',
        [...array_values(array_slice($base, 0, 7)), $mediaId]);
    else { Db::run('INSERT INTO media(filename,mime,width,height,bytes,focal_x,focal_y,source_ref) VALUES(?,?,?,?,?,?,?,?)', array_values($base)); $mediaId = Db::lastId(); }
    foreach ($sizes as $w) {
        foreach ($formats as $fmt => $q) {
            if ($fmt === 'jpg' && $w < 640) continue;
            $vrel = "profile/" . basename($pf, '.jpg') . "-$w.$fmt";
            $abs = "$outRoot/$vrel";
            $prevBytes = Db::val('SELECT bytes FROM media_variant WHERE media_id=? AND fmt=? AND w=?', [$mediaId, $fmt, $w]);
            if (is_file($abs) && !$force && $prevBytes !== null && (int) $prevBytes > 0) { $reused++; continue; }
            $bytes = $emit($pf, $abs, $w, null, $fmt, $q);
            if ($bytes === -1) { Db::upsert('media_variant', ['media_id' => $mediaId, 'fmt' => $fmt, 'w' => $w, 'path' => $vrel, 'bytes' => -1], ['media_id', 'fmt', 'w']); $done++; continue; }
            if (!$bytes) { @unlink($abs); $missing++; continue; }
            Db::upsert('media_variant', ['media_id' => $mediaId, 'fmt' => $fmt, 'w' => $w, 'path' => $vrel, 'bytes' => $bytes], ['media_id', 'fmt', 'w']);
            $done++;
        }
    }
    foreach (cfg('langs') as $l) {
        $alt = ['en' => 'Nile-Maple ' . ucfirst(basename($pf, '.jpg')) . ' — company profile photography',
                'ar' => 'صورة من الملف التعريفي لشركة نيل مابل',
                'fr' => 'Photographie du profil de société Nile-Maple'][$l] ?? '';
        Db::upsert('media_i18n', ['media_id' => $mediaId, 'lang' => $l, 'alt' => $alt], ['media_id', 'lang']);
    }
}

/* ── pass 4: resolve media references inside block payloads (D-03) ───────────────────
 * Seeds reference media either as {"__ref":"cat/image.jpg"} or as a plain
 * "media": "cat/image.jpg" string (media rows don't exist at seed time). After ingest,
 * every reference is resolved against media.source_ref and materialised as an integer
 * `media_id` sibling key — the thing templates actually read. Idempotent. */
$resolveRefs = static function (array &$p) use (&$resolveRefs, &$refFix): void {
    foreach ($p as $k => &$v) {
        if (is_array($v)) { $resolveRefs($v); continue; }
        $ref = null;
        if (is_array($v) && isset($v['__ref'])) $ref = (string) $v['__ref'];
        elseif (is_string($v) && $k === 'media' && preg_match('#^[a-z0-9-]+/[\w.-]+\.(jpe?g|png|webp)$#i', $v)) $ref = $v;
        if ($ref !== null) {
            $id = Db::val('SELECT id FROM media WHERE source_ref=?', [$ref]);
            if ($id) {
                if (is_array($v)) $v = (int) $id;
                if ($k === 'media' || (is_array($v) && isset($v['__ref']))) { /* fallthrough */ }
                $p['media_id'] = (int) $id;
                $refFix++;
            }
        }
    }
};
foreach (Db::all('SELECT id, payload FROM blocks') as $b) {
    $p = json_decode((string) $b['payload'], true);
    if (!is_array($p)) continue;
    $resolveRefs($p);
    Db::run('UPDATE blocks SET payload=? WHERE id=?', [json_encode($p, JSON_UNESCAPED_UNICODE), (int) $b['id']]);
}
foreach (Db::all('SELECT block_id, lang, payload FROM block_i18n') as $b) {
    $p = json_decode((string) $b['payload'], true);
    if (!is_array($p)) continue;
    $resolveRefs($p);
    Db::run('UPDATE block_i18n SET payload=? WHERE block_id=? AND lang=?', [json_encode($p, JSON_UNESCAPED_UNICODE), (int) $b['block_id'], $b['lang']]);
}
if ($refFix) printf("block payloads: %d media references resolved to media ids\n", $refFix);

/* ── pass 5: division covers (D-03) — each category wears a representative studio shot ── */
$coverFix = 0;
foreach ($manifest['categories'] as $mc) {
    $catId = (int) Db::val('SELECT id FROM categories WHERE code=?', [$mc['key']]);
    if (!$catId) continue;
    $cover = (int) Db::val('SELECT id FROM media WHERE source_ref=?', ["{$mc['key']}/image2.jpg"]);
    if (!$cover) continue;
    $cur = (int) (Db::val('SELECT cover_media_id FROM categories WHERE id=?', [$catId]) ?? 0);
    if ($cur !== $cover) { Db::run('UPDATE categories SET cover_media_id=? WHERE id=?', [$cover, $catId]); $coverFix++; }
}
if ($coverFix) printf("category covers set: %d\n", $coverFix);

/* ── pass 2: relink every product (idempotent, keeps the dashboard as source of truth) ── */
Db::run('UPDATE products SET card_media_id=NULL WHERE card_media_id IS NOT NULL');
foreach ($manifest['categories'] as $mc) {
    $catId = (int) Db::val('SELECT id FROM categories WHERE code=?', [$mc['key']]);
    foreach ($mc['products'] as $p) {
        $mid = (int) Db::val('SELECT id FROM media WHERE source_ref=?', ["{$mc['key']}/{$p['image']}"]);
        if (!$mid) continue;
        Db::run('UPDATE products SET card_media_id=? WHERE category_id=? AND source_index=?', [$mid, $catId, $p['index']]);
    }
}

Nm\Cache::forgetGroup('data');
$outBytes = 0;
if (is_dir($outRoot)) foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($outRoot, FilesystemIterator::SKIP_DOTS)) as $f) { if ($f->isFile()) $outBytes += $f->getSize(); }
printf("variants written %d · reused %d · linked %d · sources missing %d · %d ms · %.1f MB in %s\n",
    $done, $reused, $linked, $missing, (int) round((microtime(true) - $t0) * 1000), 
    $outBytes / 1048576, $outRoot);
if ($missingList) {
    fwrite(STDERR, "unresolved sources (" . count($missingList) . "):\n  " . implode("\n  ", array_slice($missingList, 0, 20)) . "\n");
}
$total = (int) Db::val('SELECT COUNT(*) FROM products WHERE card_media_id IS NOT NULL');
echo "products with an image: $total / " . (int) Db::val('SELECT COUNT(*) FROM products') . "\n";
if ($engine === 'node') {
    if ($jobsFp) { fclose($jobsFp); }
    if (is_file($jobsFile) && filesize($jobsFile) > 0) {
        echo "deferred " . $done . " encodes to tools/img_sharp.mjs (job file: $jobsFile)\n";
        exit(77);   /* caller runs: node tools/img_sharp.mjs <jobfile> && php tools/build_images.php --collect */
    }
    exit($total >= (int) Db::val('SELECT COUNT(*) FROM products') ? 0 : 1);
}
exit($total >= (int) Db::val('SELECT COUNT(*) FROM products') ? 0 : 1);
