<?php
/**
 * Asset pipeline (doc 04 §5): minify → fingerprint → manifest.json → copy fonts.
 * Runs on the build machine; public_html/assets is what the browser ever sees.
 *
 *   php tools/build_assets.php [--force] [--no-min]
 *
 * JS minification is optional and out-of-band: set NM_JS_MINIFIER (e.g. "npx terser --compress
 * --mangle") and the pipe is used when available; otherwise sources ship un-minified. The
 * stylesheet is the single blocking resource and is inlined by the layout, so it is minified here
 * with a string-safe tokenizer (no regex over the whole file).
 */
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

$pub = rtrim((string) cfg('paths.public'), '/');
$srcDir = nm_path('assets/src');
$noMin = in_array('--no-min', $argv ?? [], true);
$force = in_array('--force', $argv ?? [], true);

/** remove comments + redundant whitespace without touching quoted strings or url() */
function nm_min_css(string $css): string
{
    $out = ''; $len = strlen($css); $i = 0; $q = null;
    while ($i < $len) {
        $c = $css[$i];
        if ($q !== null) {
            $out .= $c;
            if ($c === '\\') { $out .= $css[++$i] ?? ''; }
            elseif ($c === $q) $q = null;
            $i++; continue;
        }
        if ($c === '"' || $c === "'") { $q = $c; $out .= $c; $i++; continue; }
        if ($c === '/' && ($css[$i + 1] ?? '') === '*') {          // comment
            $e = strpos($css, '*/', $i + 2);
            $i = $e === false ? $len : $e + 2;
            continue;
        }
        if (ctype_space($c)) {
            $j = $i; while ($j < $len && ctype_space($css[$j])) $j++;
            $t = rtrim($out); $prev = $t === '' ? '' : substr($t, -1);
            $next = $css[$j] ?? '';
            /* '+' is deliberately NOT in either "no space needed" set: unlike the other
               punctuation here, it is also a calc() arithmetic operator (calc(a + b)), where
               the surrounding whitespace is not cosmetic — CSS requires it or the whole calc()
               is invalid. Stripping it here silently zeroed out every calc(...+...) value
               (command-bar body padding, .lang-menu positioning, sticky offsets, the cookie
               banner) with no error, just layout that quietly stopped applying. */
            $need = !in_array($prev, ['', '{', '}', ';', ':', ',', '(', '&', '>', '~', '!'], true)
                 && !in_array($next, ['}', ';', ',', ')', ']', '>', ''], true);
            $out .= $need ? ' ' : '';
            $i = $j; continue;
        }
        $out .= $c; $i++;
    }
    $out = preg_replace('/;\s*}/', '}', $out) ?? $out;              // drop trailing ; in blocks
    $out = preg_replace('/\s*([{}:;,>~])\s*/', '$1', $out) ?? $out;
    return trim($out);
}

$minifyJs = static function (string $js): string {
    $cmd = getenv('NM_JS_MINIFIER');
    if (!$cmd) return $js;
    $tmp = tempnam(sys_get_temp_dir(), 'nmjs');
    file_put_contents($tmp, $js);
    $out = [];
    exec(escapeshellcmd($cmd) . ' ' . escapeshellarg($tmp) . ' 2>/dev/null', $out, $rc);
    $res = $rc === 0 ? implode("\n", $out) : '';
    @unlink($tmp);
    return $res !== '' && strlen($res) < strlen($js) ? $res : $js;
};

$gz = static fn(string $s): int => function_exists('gzencode') ? (int) strlen(gzencode($s, 9)) : strlen($s);
$write = static function (string $dir, string $name, string $body) use ($gz): array {
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $hash = substr(sha1($body), 0, 10);
    file_put_contents($dir . '/' . $name, $body);
    return ['file' => $name, 'hash' => $hash, 'bytes' => strlen($body), 'gz' => $gz($body)];
};

$manifest = ['built' => date('c'), 'generator' => 'tools/build_assets.php'];
$rows = [];

/* CSS */
foreach (['css' => ['app.css', 'app'], 'css-admin' => ['admin.css', 'admin']] as $key => [$file, $stem]) {
    $src = "$srcDir/$file";
    if (!is_file($src)) { fwrite(STDERR, "missing $src\n"); continue; }
    $body = (string) file_get_contents($src);
    if (!$noMin) $body = nm_min_css($body);
    $r = $write("$pub/assets/css", $stem . '.' . substr(sha1($body), 0, 10) . '.css', $body);
    $manifest[$key] = basename($r['file']);
    $rows[] = sprintf('%-14s %7.1f KB raw %7.1f KB gz', $key, $r['bytes'] / 1024, $r['gz'] / 1024);
}

/* JS bundles: base + route extras + admin */
foreach (['base', 'listing', 'contact', 'post', 'admin'] as $b) {
    $src = "$srcDir/$b.js";
    if (!is_file($src)) { fwrite(STDERR, "missing $src\n"); continue; }
    $body = (string) file_get_contents($src);
    if (!$noMin) $body = $minifyJs($body);
    $r = $write("$pub/assets/js", "$b." . substr(sha1($body), 0, 10) . '.js', $body);
    $manifest["js-$b"] = $r['file'];
    $rows[] = sprintf('%-14s %7.1f KB raw %7.1f KB gz', "js-$b", $r['bytes'] / 1024, $r['gz'] / 1024);
}

/* fonts (self-hosted, subset — doc 02 §3): copy the vendored woff2 next to the hashed assets */
$fontBytes = 0; $fontFiles = [];
foreach (glob(nm_path('assets/fonts/*.woff2')) ?: [] as $f) {
    if (!is_dir("$pub/assets/fonts")) mkdir("$pub/assets/fonts", 0775, true);
    copy($f, "$pub/assets/fonts/" . basename($f));
    $fontBytes += (int) filesize($f);
    $fontFiles[] = basename($f);
}
if (!$fontFiles) fwrite(STDERR, "no woff2 in assets/fonts — pages will fall back to system faces\n");

$manifest['fonts'] = $fontFiles;
file_put_contents("$pub/assets/manifest.json", json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

/* the asset tree is immutable + must never execute PHP (doc 04 §7) */
file_put_contents("$pub/assets/.htaccess",
    "# generated by tools/build_assets.php\nOptions -Indexes\n<IfModule mod_rewrite.c>\n  RewriteEngine On\n  RewriteRule \\.php$ - [F,L]\n</IfModule>\n"
  . "<FilesMatch \"\\.(css|js|woff2|svg|png|jpe?g|webp|avif)$\">\n  Header always set Cache-Control \"public, max-age=31536000, immutable\"\n</FilesMatch>\n");

Nm\Manifest::flush();
echo implode("\n", $rows) . "\n";
printf("fonts: %d file(s), %.1f KB total · manifest → %s/assets/manifest.json\n", count($fontFiles), $fontBytes / 1024, $pub);

/* budgets (doc 04 §6) */
$fail = [];
foreach ([['css', 35], ['css-admin', 35]] as [$k, $maxKb]) {
    $f = "$pub/assets/css/" . ($manifest[$k] ?? '');
    if (($manifest[$k] ?? '') && ($f !== '' ) && is_file($f) && $gz((string) file_get_contents($f)) > $maxKb * 1024) {
        $fail[] = "$k over {$maxKb} KB gz";
    }
}
$jsTotal = 0;
foreach (['base', 'listing', 'contact', 'post'] as $b) {
    $f = "$pub/assets/js/" . ($manifest["js-$b"] ?? '');
    if (is_file($f)) $jsTotal += $gz((string) file_get_contents($f));
}
if ($jsTotal > 45 * 1024) $fail[] = 'js payload ' . round($jsTotal / 1024, 1) . ' KB gz > 45 KB';
if ($fontBytes > 220 * 1024) $fail[] = 'fonts ' . round($fontBytes / 1024) . ' KB shipped (per-page subset should stay ≤ 120 KB)';
if ($fail) { foreach ($fail as $f) fwrite(STDERR, "BUDGET: $f\n"); exit(1); }
echo "budgets ok (js " . round($jsTotal / 1024, 1) . " KB gz across route bundles)\n";
