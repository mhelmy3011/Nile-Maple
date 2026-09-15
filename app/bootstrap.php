<?php
/** Nile-Maple bootstrap: config, autoloader, error policy, shared services. */
declare(strict_types=1);

define('NM_START', microtime(true));
$NM_CONFIG_FILE = __DIR__ . '/../config/config.php';
if (!is_file($NM_CONFIG_FILE)) {
    /* First run on a fresh host: adopt the example config, then lock it down — it holds the DB
       password, and a shared server that lets another tenant read it is a breach, not a typo. */
    copy(__DIR__ . '/../config/config.example.php', $NM_CONFIG_FILE);
    @chmod($NM_CONFIG_FILE, 0600);
}
$GLOBALS['NM_CONFIG'] = $GLOBALS['NM_CONFIG_OVERRIDE'] ?? require $NM_CONFIG_FILE;

function cfg(string $key, mixed $default = null): mixed {
    $p = explode('.', $key); $v = $GLOBALS['NM_CONFIG'];
    foreach ($p as $s) { if (!is_array($v) || !array_key_exists($s, $v)) return $default; $v = $v[$s]; }
    return $v;
}
function nm_path(string $rel = ''): string { return cfg('paths.root') . ($rel ? '/' . ltrim($rel, '/') : ''); }

spl_autoload_register(static function (string $c): void {
    if (!str_starts_with($c, 'Nm\\')) return;
    $f = cfg('paths.app') . '/' . str_replace('\\', '/', substr($c, 3)) . '.php';
    if (is_file($f)) require_once $f;
});

// error policy (doc 04 §7): log, never display in prod
ini_set('display_errors', cfg('env') === 'dev' ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', nm_path('storage/logs/php.log'));
error_reporting(E_ALL);
set_error_handler(static function (int $no, string $str, string $file = '', int $line = 0): bool {
    if (!(error_reporting() & $no)) return false;
    error_log("[nm] $str @ $file:$line");
    if (in_array($no, [E_USER_ERROR, E_RECOVERABLE_ERROR], true)) throw new ErrorException($str, 0, $no, $file, $line);
    return true;
});
/* Uncaught throwables log verbosely server-side and render a NEUTRAL page — never a stack
 * trace with filesystem paths (D-01 secondary finding: the /manage/login fatal leaked paths). */
set_exception_handler(static function (\Throwable $e): void {
    error_log("[nm] UNCAUGHT " . get_class($e) . ": {$e->getMessage()} @ {$e->getFile()}:{$e->getLine()}\n{$e->getTraceAsString()}");
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store');
    }
    $dev = cfg('env') === 'dev';
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>Something went wrong — Nile-Maple</title>'
        . '<body style="font:16px/1.6 system-ui;background:#fdf9f6;color:#1d2b25;display:grid;place-items:center;min-height:100vh;margin:0">'
        . '<div style="max-width:36rem;padding:2rem;text-align:center"><h1 style="font-size:1.3rem">Something went wrong on our side.</h1>'
        . '<p>The error has been logged and we are looking into it. Please try again in a moment.</p>'
        . '<p><a href="/" style="color:#16382b">Back to the home page</a></p>'
        . ($dev ? '<pre style="text-align:left;background:#fff;border:1px solid #e5ddd2;padding:1rem;overflow:auto;white-space:pre-wrap">'
            . htmlspecialchars(get_class($e) . ': ' . $e->getMessage() . "\n" . $e->getTraceAsString(), ENT_QUOTES, 'UTF-8') . '</pre>' : '')
        . '</div></body></html>';
});

foreach (['cache/data', 'cache/lang', 'cache/frag', 'storage/logs', 'storage/tmp', 'storage/mail', 'storage/backups'] as $d) {
    if (!is_dir(nm_path($d))) @mkdir(nm_path($d), 0775, true);
}
date_default_timezone_set('Africa/Cairo');
mb_internal_encoding('UTF-8');
