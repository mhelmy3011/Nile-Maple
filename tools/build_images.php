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
foreach ($argv ?? [] as $a) {
    if (str_starts_with($a, '--engine=')) $engine = substr($a, 7);
    if (str_starts_with($a, '--sizes=')) $sizes = array_map('intval', explode(',', substr($a, 8)));
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
$engine = match (true) {
    $engine !== 'auto' => $engine,
    $haveGd => 'gd',
    $haveImagick => 'imagick',
    default => 'convert',
};
$formats = [];
foreach (['avif' => 55, 'webp' => 78, 'jpg' => 82] as $fmt => $q) {
    if ($engine === 'gd' && !Img::can($fmt)) continue;
    if ($engine === 'imagick' && !in_array(strtoupper($fmt), (new \Imagick())->getSupportedFormats(), true)) continue;
    $formats[$fmt] = $q;
}
if (!$formats) { $formats['jpg'] = 82; }
/* JPEG is the safety net every <picture> needs, even on a stripped GD build */
if (!isset($formats['jpg'])) $formats['jpg'] = 82;
ksort($formats);

echo "engine=$engine formats=" . implode(',', array_keys($formats)) . ' widths=' . implode(',', $sizes) . "\n";

/** write one variant; returns bytes */
$emit = static function (string $srcFile, string $outFile, int $w, ?int $h, string $fmt, int $q) use ($engine): int {
    if (!is_dir(dirname($outFile))) mkdir(dirname($outFile), 0775, true);
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
                /* doc 04 §4: JPEG is the safety net at 640/800 only. AVIF follows the same rule —
                   libavif in several shared-hosting GD builds refuses the tiny sizes, so we ask it
                   for what it can encode and let <picture> fall through to WebP below 640. */
                if (in_array($fmt, ['jpg', 'avif'], true) && $w < 640) continue;
                $rel = "$ck/$slug-$w.$fmt";
                $abs = "$outRoot/$rel";
                $exists = is_file($abs);
                if ($exists && !$force && Db::val('SELECT bytes FROM media_variant WHERE media_id=? AND fmt=? AND w=?', [$mediaId, $fmt, $w]) !== null) {
                    $reused++; continue;
                }
                $bytes = $emit($file, $abs, $w, null, $fmt, $q);
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
printf("variants written %d · reused %d · linked %d · sources missing %d · %d ms · %.1f MB in %s\n",
    $done, $reused, $linked, $missing, (int) round((microtime(true) - $t0) * 1000),
    (float) shell_exec('du -sm ' . escapeshellarg($outRoot) . ' 2>/dev/null | cut -f1') ?: 0, $outRoot);
if ($missingList) {
    fwrite(STDERR, "unresolved sources (" . count($missingList) . "):\n  " . implode("\n  ", array_slice($missingList, 0, 20)) . "\n");
}
$total = (int) Db::val('SELECT COUNT(*) FROM products WHERE card_media_id IS NOT NULL');
echo "products with an image: $total / " . (int) Db::val('SELECT COUNT(*) FROM products') . "\n";
exit($total >= (int) Db::val('SELECT COUNT(*) FROM products') ? 0 : 1);
