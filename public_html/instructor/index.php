<?php
/**
 * Instructor dashboard front controller.
 * Mirrors manage/index.php but for /instructor/ routes.
 */
declare(strict_types=1);
require __DIR__ . '/../../app/bootstrap.php';

use Nm\Instructor;

header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');
header('Permissions-Policy: camera=(), geolocation=(), microphone=(), payment=()');
header('Cache-Control: no-store, max-age=0');
if (cfg('env') === 'prod') header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

Instructor::handle();
