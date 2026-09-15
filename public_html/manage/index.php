<?php
/**
 * Dashboard front controller (doc 06). The ONLY entry point under /manage/.
 * Lives inside public_html but is rewritten here by .htaccess; all other PHP in the
 * webroot is denied execution (public_html/.htaccess + generated per-dir rules).
 */
declare(strict_types=1);
require __DIR__ . '/../../app/bootstrap.php';

use Nm\Admin;

/* the control room must never be indexed, cached by a CDN, or frame-embedded */
header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');
header('Permissions-Policy: camera=(), geolocation=(), microphone=(), payment=()');
header('Cache-Control: no-store, max-age=0');
if (cfg('env') === 'prod') header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

Admin::handle();
