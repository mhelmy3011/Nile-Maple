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
$GLOBALS['NM_CONFIG'] = require $NM_CONFIG_FILE;

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

foreach (['cache/data', 'cache/lang', 'cache/frag', 'storage/logs', 'storage/tmp', 'storage/mail', 'storage/backups'] as $d) {
    if (!is_dir(nm_path($d))) @mkdir(nm_path($d), 0775, true);
}
date_default_timezone_set('Africa/Cairo');
mb_internal_encoding('UTF-8');
