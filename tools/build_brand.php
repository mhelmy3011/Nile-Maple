<?php
/**
 * Brand asset pipeline (doc 02 §4). The supplied logo PNGs are RGB on an opaque cream
 * #FDF9F6 plate with no alpha (DQ-02), so nothing can ship to a browser until it has been
 * de-backgrounded, trimmed and re-cut to the exact ratio the CSS box expects.
 *
 *   php tools/build_brand.php [--docx path/to/Company_Profile.docx]
 *
 * Sources (inside the company-profile DOCX, per tools/extract_content.py):
 *   word/media/image1.png  mark only        580×500
 *   word/media/image2.png  EN lockup        875×745
 *   word/media/image7.png  AR lockup        915×875
 * Output: public_html/assets/brand/{logo-mark,logo-word-en,logo-word-ar,logo-mono-light}.webp
 *         + favicon.svg, favicon-32.png, apple-touch-icon.png, og-default.jpg
 */
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

use Nm\Img;

$out = rtrim((string) cfg('paths.public'), '/') . '/assets/brand';
if (!is_dir($out)) mkdir($out, 0775, true);

$docx = nm_path('Nile-Maple_Company_Profile.docx');
foreach ($argv ?? [] as $i => $a) if ($a === '--docx') $docx = $argv[$i + 1] ?? $docx;
if (!is_file($docx)) { fwrite(STDERR, "missing $docx\n"); exit(1); }

$tmp = nm_path('storage/tmp/brand');
if (!is_dir($tmp)) mkdir($tmp, 0775, true);
$zip = new ZipArchive;
if ($zip->open($docx) !== true) { fwrite(STDERR, "cannot open $docx\n"); exit(1); }
$members = ['mark' => 'word/media/image1.png', 'lockup_en' => 'word/media/image2.png', 'lockup_ar' => 'word/media/image7.png'];
$files = [];
foreach ($members as $k => $m) {
    $data = $zip->getFromName($m);
    if ($data === false) { fwrite(STDERR, "missing $m in docx\n"); continue; }
    file_put_contents("$tmp/$k.png", $data);
    $files[$k] = "$tmp/$k.png";
}
$zip->close();
if (!$files) { fwrite(STDERR, "no brand members extracted\n"); exit(1); }

/** de-background + tight crop to the alpha bounding box */
function nm_alpha_trim(string $png): ?GdImage
{
    $im = Img::debackground($png, 26);            // cream plate + its paper texture
    if (!$im) return null;
    $w = imagesx($im); $h = imagesy($im);
    $x0 = $w; $y0 = $h; $x1 = 0; $y1 = 0; $found = false;
    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            if (((imagecolorat($im, $x, $y) >> 24) & 0x7F) > 100) continue;   // transparent
            $found = true;
            if ($x < $x0) $x0 = $x; if ($x > $x1) $x1 = $x;
            if ($y < $y0) $y0 = $y; if ($y > $y1) $y1 = $y;
        }
    }
    if (!$found) return $im;
    $cw = max(1, $x1 - $x0); $ch = max(1, $y1 - $y0);
    $d = imagecreatetruecolor($cw, $ch);
    imagealphablending($d, false); imagesavealpha($d, true);
    imagefill($d, 0, 0, (127 << 24));
    imagecopy($d, $im, 0, 0, $x0, $y0, $cw, $ch);
    imagedestroy($im);
    return $d;
}

/** split a stacked lockup into [mark, wordmark] by finding the horizontal whitespace band */
function nm_split_lockup(GdImage $im): array
{
    $w = imagesx($im); $h = imagesy($im);
    $rowHas = [];
    for ($y = 0; $y < $h; $y++) {
        $on = 0;
        for ($x = 0; $x < $w; $x += 2) {
            if (((imagecolorat($im, $x, $y) >> 24) & 0x7F) <= 100) { $on++; if ($on > 3) break; }
        }
        $rowHas[$y] = $on > 3;
    }
    /* longest empty band in the lower half = gap between symbol and lettering */
    $best = [0, 0]; $run = null;
    for ($y = (int) ($h * .45); $y < $h; $y++) {
        if (!$rowHas[$y]) { $run ??= $y; continue; }
        if ($run !== null && $y - $run > $best[1] - $best[0]) $best = [$run, $y];
        $run = null;
    }
    if ($run !== null && $h - $run > $best[1] - $best[0]) $best = [$run, $h];
    $cut = $best[1] > 0 ? (int) round(($best[0] + $best[1]) / 2) : (int) ($h * .62);

    $crop = static function (GdImage $src, int $y0, int $y1) use ($w): GdImage {
        $y1 = min($y1, imagesy($src)); $h = max(1, $y1 - $y0);
        $d = imagecreatetruecolor($w, $h);
        imagealphablending($d, false); imagesavealpha($d, true); imagefill($d, 0, 0, (127 << 24));
        imagecopy($d, $src, 0, 0, 0, $y0, $w, $h);
        return $d;
    };
    return [$crop($im, 0, $cut), $crop($im, $cut, $h)];
}

/** letterbox onto an exact aspect so the CSS box (width+height) never distorts the art */
function nm_fit(GdImage $im, int $tw, int $th): GdImage
{
    $d = imagecreatetruecolor($tw, $th);
    imagealphablending($d, false); imagesavealpha($d, true); imagefill($d, 0, 0, (127 << 24));
    $sw = imagesx($im); $sh = imagesy($im);
    $s = min($tw / $sw, $th / $sh) * 0.98;
    $w = max(1, (int) round($sw * $s)); $h = max(1, (int) round($sh * $s));
    imagealphablending($d, true);
    imagecopyresampled($d, $im, (int) (($tw - $w) / 2), (int) (($th - $h) / 2), 0, 0, $w, $h, $sw, $sh);
    return $d;
}
/** recolour every opaque pixel (keeps alpha) — the mono light lockup for the dark footer */
function nm_tint(GdImage $im, int $rgb): GdImage
{
    $w = imagesx($im); $h = imagesy($im);
    for ($y = 0; $y < $h; $y++) for ($x = 0; $x < $w; $x++) {
        $c = imagecolorat($im, $x, $y);
        $a = ($c >> 24) & 0x7F;
        if ($a === 0) imagesetpixel($im, $x, $y, $c);
        else imagesetpixel($im, $x, $y, ($a << 24) | $rgb);
    }
    return $im;
}
$save = static function (GdImage $im, string $file, string $fmt = 'webp', int $q = 90): int {
    if (!is_dir(dirname($file))) mkdir(dirname($file), 0775, true);
    Img::save($im, $file, $fmt, $q);
    return (int) filesize($file);
};

$made = [];
$mark = nm_alpha_trim($files['mark'] ?? $files['lockup_en']);
if (!$mark) { fwrite(STDERR, "mark decode failed\n"); exit(1); }
$made['logo-mark.webp'] = $save(nm_fit($mark, 132, 114), "$out/logo-mark.webp");            /* CSS 36×31 → 3.6× */
$made['logo-mark.png'] = $save(nm_fit($mark, 132, 114), "$out/logo-mark.png", 'png');

foreach (['en' => 'lockup_en', 'ar' => 'lockup_ar'] as $lang => $key) {
    if (!isset($files[$key])) continue;
    $lock = nm_alpha_trim($files[$key]);
    if (!$lock) continue;
    [$sym, $word] = nm_split_lockup($lock);
    $made["logo-word-$lang.webp"] = $save(nm_fit($word, 472, 100), "$out/logo-word-$lang.webp");
    if ($lang === 'en') {
        $mono = nm_tint(nm_fit($word, 700, 200), 0xFFFFFF);
        $made['logo-mono-light.webp'] = $save($mono, "$out/logo-mono-light.webp");
        /* social card: cream plate + full EN lockup, 1200×630 (doc 04 §4) */
        $og = imagecreatetruecolor(1200, 630);
        imagefill($og, 0, 0, imagecolorallocate($og, 253, 249, 246));
        imagealphablending($og, true);
        $full = nm_fit($lock, 1000, 520);
        imagecopy($og, $full, (int) ((1200 - imagesx($full)) / 2), (int) ((630 - imagesy($full)) / 2), 0, 0, imagesx($full), imagesy($full));
        imagewebp($og, "$out/og-default.webp", 88);
        imagejpeg($og, "$out/og-default.jpg", 86);
        $made['og-default.jpg'] = (int) filesize("$out/og-default.jpg");
        imagedestroy($og);
    }
    imagedestroy($word);
}

/* icons: a 64 px mark rasterised into an SVG wrapper (crisp at every favicon size) + the
   alpha-less variants iOS needs. Replace favicon.svg with a designer-supplied vector any time —
   the filename is the contract. */
$ico = nm_fit($mark, 128, 110);
imagepng($ico, "$out/favicon-64.png");
$f32 = imagecreatetruecolor(32, 32);
imagefill($f32, 0, 0, imagecolorallocate($f32, 253, 249, 246));
imagealphablending($f32, true);
$c32 = nm_fit($mark, 26, 23);
imagecopy($f32, $c32, 3, 5, 0, 0, 26, 23);
imagepng($f32, "$out/favicon-32.png");
$apple = imagecreatetruecolor(180, 180);
imagefill($apple, 0, 0, imagecolorallocate($apple, 253, 249, 246));
imagealphablending($apple, true);
$c180 = nm_fit($mark, 128, 110);
imagecopy($apple, $c180, (int) ((180 - 128) / 2), 35, 0, 0, 128, 110);
imagepng($apple, "$out/apple-touch-icon.png");
$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64" role="img" aria-label="Nile-Maple">'
     . '<rect width="64" height="64" rx="12" fill="#FDF9F6"/>'
     . '<image href="data:image/png;base64,' . base64_encode((string) file_get_contents("$out/favicon-64.png"))
     . '" x="6" y="9" width="52" height="46" image-rendering="auto"/></svg>';
file_put_contents("$out/favicon.svg", $svg);
$made['favicon.svg'] = strlen($svg);
$made['favicon-32.png'] = (int) filesize("$out/favicon-32.png");
$made['apple-touch-icon.png'] = (int) filesize("$out/apple-touch-icon.png");

Nm\Cache::forgetGroup('data');
foreach ($made as $f => $b) printf("  %-24s %6.1f KB\n", $f, $b / 1024);
echo "brand assets → $out (" . count($made) . " files)\n";
