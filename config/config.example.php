<?php
/**
 * Nile-Maple configuration EXAMPLE (committed). The real config lives at config/config.php
 * ABOVE-equivalent, chmod 600, git-ignored (doc 04 §7). Copy and adapt per environment.
 */
return [
    'env'          => 'dev',                 // dev | prod
    'base_url'     => 'https://nilemaple.com',
    'db' => [
        'driver'   => 'sqlite',              // sqlite (local/test) | mysql (Hostinger prod)
        'sqlite'   => __DIR__ . '/../storage/db.sqlite',
        // WAL is faster on a real disk; tools/serve.mjs (WASM dev bridge) needs it off.
        'sqlite_wal' => true,
        'mysql'    => ['host' => 'localhost', 'name' => 'u000000_nilemaple', 'user' => 'u000000', 'pass' => ''],
    ],
    'paths' => [
        'root'     => dirname(__DIR__),
        'public'   => dirname(__DIR__) . '/public_html',
        'cache'    => dirname(__DIR__) . '/cache',
        'storage'  => dirname(__DIR__) . '/storage',
        'app'      => dirname(__DIR__) . '/app',
    ],
    'langs'        => ['en', 'ar', 'fr'],
    'default_lang' => 'en',
    'rtl'          => ['ar'],
    'mail' => [
        'transport' => 'log',                 // log (dev/test) | smtp (prod)
        'smtp'      => ['host' => 'smtp.hostinger.com', 'port' => 465, 'tls' => true, 'user' => '', 'pass' => ''],
        'from'      => ['contact@nilemaple.com', 'Nile-Maple'],
        'to'        => 'contact@nilemaple.com',
    ],
    'admin' => [
        'path'        => '/manage',
        'idle_min'    => 30,
        'allow_ips'   => [],                  // optional allowlist
        'totp_secret' => '',                  // owner 2FA (base32); empty = disabled
    ],
    'cdn_purge_url' => '',                     // optional webhook
];
