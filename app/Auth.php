<?php
namespace Nm;

/** Admin authentication & RBAC (doc 04 §7, doc 06 §6). */
final class Auth
{
    /** Seed credential — accepted exactly once per account, then forced off (D-11). */
    public const SEED_PASSWORD = 'ChangeMe!2026';

    public static function attempt(string $email, string $pass): array
    {
        $u = Db::one('SELECT * FROM users WHERE email=?', [strtolower(trim($email))]);
        if (!$u) return ['error' => 'invalid'];
        if ($u['status'] !== 'active') return ['error' => 'disabled'];
        if ($u['locked_until'] && strtotime($u['locked_until']) > time()) return ['error' => 'locked'];
        if (!password_verify($pass, $u['password_hash'])) {
            $fails = (int) $u['failed_logins'] + 1;
            $lock = $fails >= 5 ? date('Y-m-d H:i:s', time() + 900) : null;
            Db::run('UPDATE users SET failed_logins=?, locked_until=? WHERE id=?', [$fails, $lock, $u['id']]);
            Audit::log('auth.fail', 'user', (int) $u['id']);
            return ['error' => 'invalid'];
        }
        Db::run('UPDATE users SET failed_logins=0, locked_until=NULL, last_login_at=? WHERE id=?', [date('Y-m-d H:i:s'), $u['id']]);
        Session::start();
        session_regenerate_id(true);
        $_SESSION['uid'] = (int) $u['id'];
        $_SESSION['role'] = $u['role'];
        $_SESSION['t0'] = time();
        /* D-11: an account still on the seed password may go nowhere until it rotates.
           The value is also blocked from ever being chosen again (see pgUsers / pgPassword). */
        if (hash_equals(self::SEED_PASSWORD, $pass)) {
            $_SESSION['force_pw'] = 1;
            Audit::log('auth.seed-pw', 'user', (int) $u['id']);
        }
        Audit::log('auth.login', 'user', (int) $u['id']);
        return ['ok' => true, 'user' => $u];
    }

    /** D-11: the logged-in account authenticated with the seed password and must rotate now. */
    public static function needsRotation(): bool
    {
        Session::start();
        return !empty($_SESSION['force_pw']);
    }

    public static function user(): ?array
    {
        Session::start();
        if (empty($_SESSION['uid'])) return null;
        if (isset($_SESSION['t0']) && time() - $_SESSION['t0'] > cfg('admin.idle_min') * 60) { self::logout(); return null; }
        $_SESSION['t0'] = time();
        return Db::one('SELECT id,email,full_name,role FROM users WHERE id=? AND status=\'active\'', [$_SESSION['uid']]);
    }
    public static function role(): string { return $_SESSION['role'] ?? ''; }
    public static function isOwner(): bool { return self::role() === 'owner'; }
    public static function requireLogin(): array
    {
        $u = self::user();
        if (!$u) Util::redirect(cfg('admin.path') . '/login');
        return $u;
    }
    public static function requireOwner(): void
    {
        if (!self::isOwner()) { http_response_code(403); exit('Forbidden'); }
    }
    public static function logout(): void
    {
        Session::start();
        Audit::log('auth.logout');
        $_SESSION = [];
        session_destroy();
    }
    /** minimal RFC6238 TOTP for owner 2FA (optional, doc 06) */
    public static function totpVerify(string $secret, string $code): bool
    {
        if ($secret === '') return true;
        $key = self::b32decode($secret);
        $t = intdiv(time(), 30);
        foreach ([-1, 0, 1] as $drift) {
            $bin = pack('N*', 0, $t + $drift);
            $h = hash_hmac('sha1', $bin, $key, true);
            $o = ord($h[19]) & 15;
            $otp = ( ((ord($h[$o]) & 127) << 24) | (ord($h[$o+1]) << 16) | (ord($h[$o+2]) << 8) | ord($h[$o+3]) ) % 1000000;
            if (hash_equals(str_pad((string) $otp, 6, '0', STR_PAD_LEFT), $code)) return true;
        }
        return false;
    }
    private static function b32decode(string $s): string
    {
        $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split(strtoupper(preg_replace('/[^A-Z2-7]/', '', $s))) as $c) $bits .= str_pad(decbin(strpos($map, $c)), 5, '0', STR_PAD_LEFT);
        $out = '';
        foreach (str_split($bits, 8) as $b) if (strlen($b) === 8) $out .= chr(bindec($b));
        return $out;
    }
}
