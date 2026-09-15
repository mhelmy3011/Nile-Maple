<?php
/**
 * Nile-Maple root front controller (doc 04 §2.1).
 *
 * It only ever sees traffic that LiteSpeed could NOT serve from disk, i.e.:
 *   r=lang    → "/"  language detection + 302 to /en|ar|fr/
 *   r=api     → /api/*  enquiry, event beacon, load-more fragment
 *   r=render  → any other unmatched path: render from DB, WRITE the static file
 *               (self-healing), then serve it. 99.9 % of traffic never gets here.
 */
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

use Nm\ApiController;
use Nm\Auth;
use Nm\Db;
use Nm\PublicController;
use Nm\Settings;
use Nm\Util;

/* ── security + caching headers (static files get the same set from .htaccess) ───────── */
$SECRETS = [
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options' => 'DENY',
    'Referrer-Policy' => 'strict-origin-when-cross-origin',
    'Permissions-Policy' => 'camera=(), geolocation=(), microphone=(), payment=()',
];
if (cfg('env') === 'prod') {
    $SECRETS['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains; preload';
}
foreach ($SECRETS as $h => $v) header("$h: $v");

/* ── resolve request path ────────────────────────────────────────────────────────────── */
$mode = (string) ($_GET['r'] ?? (php_sapi_name() === 'cli' ? 'cli' : 'render'));
$raw = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$uri = $raw;
/*
 * The local php-wasm bridge (tools/serve.mjs) cannot preserve REQUEST_URI across its rewrite,
 * so it passes the pretty path as ?p=. Never trusted in prod: on LiteSpeed the path always
 * comes from REQUEST_URI, and a request-supplied path would otherwise be a self-heal
 * (write-this-file-there) vector.
 */
if (cfg('env') !== 'prod' && isset($_GET['p'])) $uri = '/' . ltrim((string) $_GET['p'], '/');
$uri = '/' . trim(rawurldecode($uri), '/');

if ($mode === 'api') {
    header('Cache-Control: no-store');
    ApiController::handle((string) ($_GET['path'] ?? ''));
    exit;
}

$langs = cfg('langs');
$default = cfg('default_lang');

/** Accept-Language / cookie → best supported locale (D-04: cookie wins, never a hard reset). */
$detect = static function () use ($langs, $default): string {
    $c = (string) ($_COOKIE['nm_lang'] ?? '');
    if (in_array($c, $langs, true)) return $c;
    $best = $default; $bestQ = 0.0;
    foreach (array_slice(array_reverse(explode(',', (string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''))), 0, 8) as $part) {
        if (!preg_match('/^\s*([a-zA-Z-]+)\s*(?:;\s*q\s*=\s*([0-9.]+))?\s*$/', $part, $m)) continue;
        $tag = strtolower(str_replace('_', '-', $m[1]));
        $q = isset($m[2]) ? (float) $m[2] : 1.0;
        $iso = explode('-', $tag)[0];
        if (in_array($iso, $langs, true) && $q > $bestQ) { $best = $iso; $bestQ = $q; }
    }
    return $best;
};

if ($mode === 'lang') {
    $l = $detect();
    header('Set-Cookie: nm_lang=' . $l . '; Path=/; Max-Age=31536000; SameSite=Lax' . (cfg('env') === 'prod' ? '; Secure' : ''));
    header('Cache-Control: no-store');
    Util::redirect('/' . $l . '/', 302);
}

/* ── r=render ───────────────────────────────────────────────────────────────────────── */
$segs = $uri === '/' ? [] : explode('/', ltrim($uri, '/'));
$lang = (string) ($segs[0] ?? '');

/* legacy/short URLs without a locale prefix → 301 into the preferred locale (keeps deep links alive) */
if (!in_array($lang, $langs, true)) {
    if (preg_match('#^/(en|ar|fr)([-_][A-Za-z]{2,4})?/#', $uri, $m)) $lang = $m[1];   // en-US → en
    else {
        $target = '/' . $detect() . ($uri === '/' ? '/' : $uri . '/');
        header('Cache-Control: no-store');
        Util::redirect($target, 301);
    }
}
$path = implode('/', array_slice($segs, 1));
$path = preg_replace('/index\.html$/', '', $path);
$path = trim($path, '/');

/*
 * Trailing-slash canonicalisation (mirrors the static layout: /en/products/orange/).
 * Test and target must use the path AS REQUESTED ($raw): $uri is already trimmed, so testing it
 * here would send every clean URL into a 301 loop that lands on itself.
 */
if ($uri !== '/' && !str_ends_with($raw, '/') && !str_contains(basename($raw), '.')) {
    Util::redirect(rtrim($raw, '/') . '/', 301);
}
if ($uri === '/') Util::redirect('/' . $lang . '/', 302);

/* path must stay inside the locale subtree — no traversal, no dotfiles */
if (preg_match('#(^|/)\.\.(/|$)#', $path) || preg_match('#/[.]|(^|\.)git|\.ini$|\.php$#', $path)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Not found');
}

/* admin-curated redirects (doc 06 §8) win before rendering */
$red = Db::one('SELECT to_path, code FROM redirects WHERE from_path=?', ['/' . $lang . ($path ? "/$path" : '') . '/']);
if ($red) {
    header('Cache-Control: no-store');
    Util::redirect((string) $red['to_path'], (int) ($red['code'] ?: 301));
}

/* maintenance mode (owner-only escape hatch: logged-in admins still see the site) */
if (Settings::get('sys.maintenance') === '1' && !Auth::user()) {
    http_response_code(503);
    header('Retry-After: 3600');
    header('Content-Type: text/html; charset=utf-8');
    $m = '<!doctype html><html lang="' . $lang . '" dir="' . ($lang === 'ar' ? 'rtl' : 'ltr') . '"><meta charset="utf-8">'
       . '<meta name="viewport" content="width=device-width,initial-scale=1"><title>Nile-Maple — up</title>'
       . '<body style="font:16px/1.6 system-ui;background:#16382b;color:#fff;display:grid;place-items:center;min-height:100vh;margin:0">'
       . '<div style="max-width:34rem;padding:2rem;text-align:center"><h1 style="font-size:1.4rem">We are updating the site</h1>'
       . '<p style="opacity:.85">Nile-Maple is briefly offline. Products, packing and cold-chain details will be back in a few minutes.</p>'
       . '<p><a href="mailto:contact@nilemaple.com" style="color:#fcb929">contact@nilemaple.com</a></p></div></body></html>';
    echo $m;
    exit;
}

$res = PublicController::page($lang, $path, $_GET);
if (!empty($res['location'])) {   /* D-14: foreign-slug fallback → 301 into the localized URL */
    http_response_code((int) ($res['status'] ?: 301));
    header('Cache-Control: public, max-age=86400');
    header('Location: ' . $res['location'], true, (int) ($res['status'] ?: 301));
    exit;
}
http_response_code((int) $res['status']);
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: ' . ($res['status'] === 200
    ? 'public, max-age=0, must-revalidate, s-maxage=3600, stale-while-revalidate=86400'
    : 'no-store'));
echo $res['html'];

/* self-healing: persist so the next hit is a 0-PHP static read (doc 04 §2) */
if ($res['status'] === 200 && $path !== '' && !isset($_GET['page'])) {
    try {
        $dir = rtrim((string) cfg('paths.public'), '/') . '/' . $lang . '/' . $path . '/';
        $root = rtrim((string) cfg('paths.public'), '/') . '/';
        if (!str_starts_with($dir, $root)) exit;                       // traversal guard (belt)
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $tmp = $dir . '.index.html.' . getmypid() . '.tmp';
        if (@file_put_contents($tmp, $res['html']) && @rename($tmp, $dir . 'index.html')) {
            @chmod($dir . 'index.html', 0664);
        } else { @unlink($tmp); }
    } catch (\Throwable) { /* never fail a served page because a cache write failed */ }
}
