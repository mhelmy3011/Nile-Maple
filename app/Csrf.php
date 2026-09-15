<?php
namespace Nm;

/**
 * CSRF tokens for a site whose HTML is *static* (doc 04 §7). A session-bound token cannot work
 * here: the page is built once and served from disk to every visitor, so the token is either
 * signed-and-rotating or per-visitor.
 *
 *  · /manage runs with a real session → per-session token (strongest).
 *  · public forms get a visit token: `GET /api/csrf` sets an HttpOnly `nm_visit` cookie and
 *    returns HMAC(secret, visit|hour). base/contact.js refreshes it on page load, so a page
 *    cached for hours still submits a live token.
 *  · no-JS fallback: HMAC(secret, hour) — accepted for one hour plus one hour of grace, which is
 *    what the honeypot, the time-trap and the 5-per-10-min rate limit are there to back up.
 */
final class Csrf
{
    public const BUCKET_SEC = 3600;

    public static function token(): string
    {
        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['csrf'])) return (string) $_SESSION['csrf'];
        $visit = self::visit();
        if ($visit !== '') return self::sign($visit, self::bucket());
        return self::sign('', self::bucket());
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(self::token(), ENT_QUOTES) . '">';
    }

    public static function check(?string $t): bool
    {
        if (!is_string($t) || $t === '' || strlen($t) !== 64) return false;
        if (session_status() === PHP_SESSION_ACTIVE && hash_equals((string) ($_SESSION['csrf'] ?? ''), $t)) return true;
        $b = self::bucket();
        $visit = self::visit();
        foreach ([$b, $b - 1] as $bucket) {
            if ($visit !== '' && hash_equals(self::sign($visit, $bucket), $t)) return true;
            if (hash_equals(self::sign('', $bucket), $t)) return true;
        }
        return false;
    }

    /** issue the per-visit cookie + token (called from /api/csrf) */
    public static function issue(): string
    {
        $visit = preg_match('/^[a-f0-9]{32}$/', (string) ($_COOKIE['nm_visit'] ?? ''))
            ? $_COOKIE['nm_visit'] : bin2hex(random_bytes(16));
        header('Set-Cookie: nm_visit=' . $visit . '; Path=/; Max-Age=86400; SameSite=Lax; HttpOnly'
             . (cfg('env') === 'prod' ? '; Secure' : ''));
        return self::sign($visit, self::bucket());
    }

    private static function visit(): string
    {
        $v = (string) ($_COOKIE['nm_visit'] ?? '');
        return preg_match('/^[a-f0-9]{32}$/', $v) ? $v : '';
    }
    private static function bucket(): int { return (int) floor(time() / self::BUCKET_SEC); }
    private static function sign(string $visit, int $bucket): string
    {
        return hash_hmac('sha256', 'nm-csrf|' . $bucket . '|' . $visit, self::secret());
    }

    /** config value wins; otherwise a 0600 key file generated once (never in git, never in webroot) */
    private static function secret(): string
    {
        $s = (string) cfg('security.salt', '');
        if (strlen($s) >= 16) return $s;
        $f = nm_path('storage/secret.key');
        if (is_file($f)) return trim((string) file_get_contents($f));
        $s = bin2hex(random_bytes(32));
        if (!is_dir(dirname($f))) @mkdir(dirname($f), 0700, true);
        @file_put_contents($f, $s);
        @chmod($f, 0600);
        return $s;
    }
}
