<?php
declare(strict_types=1);

$root = dirname(__DIR__) . '/public_html';
$uri = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$uri = rawurldecode($uri);
if (str_contains($uri, '..')) { http_response_code(400); exit('bad path'); }

$mimes = [
    'html' => 'text/html; charset=utf-8', 'css' => 'text/css; charset=utf-8',
    'js' => 'application/javascript; charset=utf-8', 'mjs' => 'application/javascript; charset=utf-8',
    'json' => 'application/json; charset=utf-8', 'svg' => 'image/svg+xml', 'png' => 'image/png',
    'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp', 'avif' => 'image/avif',
    'ico' => 'image/x-icon', 'woff2' => 'font/woff2', 'woff' => 'font/woff', 'txt' => 'text/plain; charset=utf-8',
    'xml' => 'application/xml; charset=utf-8', 'webmanifest' => 'application/manifest+json',
];
$send = static function (string $file) use ($mimes): void {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', (int) filemtime($file)) . ' GMT');
    if (preg_match('#/assets/#', $file)) header('Cache-Control: public, max-age=31536000, immutable');
    elseif (str_ends_with($file, '.html')) header('Cache-Control: no-cache');
    readfile($file);
    exit;
};

$cand = rtrim($root . ($uri === '/' ? '' : $uri), '/');
if (is_file($cand)) $send($cand);
if (is_dir($cand) && is_file($cand . '/index.html')) $send($cand . '/index.html');
if (preg_match('#^/assets/src/([\w.-]+\.(?:css|js))$#', $uri, $m) && is_file(dirname(__DIR__) . "/assets/src/{$m[1]}")) {
    $send(dirname(__DIR__) . "/assets/src/{$m[1]}");
}

if (preg_match('#^/api(/.*)?$#', $uri, $am)) {
    $_GET['r'] = 'api';
    $_GET['path'] = ltrim((string) ($am[1] ?? ''), '/');
    require $root . '/index.php';
    exit;
}
if (preg_match('#^/manage/?(.*)$#', $uri, $mm)) {
    $_GET['path'] = $mm[1];
    require $root . '/manage/index.php';
    exit;
}
if (preg_match('#^/instructor/?(.*)$#', $uri, $im)) {
    $_GET['path'] = $im[1];
    require $root . '/instructor/index.php';
    exit;
}
if ($uri === '/' || $uri === '') { $_GET['r'] = 'lang'; require $root . '/index.php'; exit; }

$_GET['r'] = 'render';
require $root . '/index.php';
