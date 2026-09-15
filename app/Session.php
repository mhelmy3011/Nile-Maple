<?php
namespace Nm;

/** DB-backed sessions (shared-hosting safe, no FS session races). */
final class Session
{
    private static bool $on = false;
    public static function start(): void
    {
        if (self::$on || session_status() === PHP_SESSION_ACTIVE) { self::$on = true; return; }
        session_set_save_handler(new DbSessionHandler(), true);
        session_name('nm_sid');
        session_set_cookie_params([
            'lifetime' => 0, 'path' => '/', 'domain' => '',
            'secure' => cfg('env') === 'prod', 'httponly' => true, 'samesite' => 'Lax',
        ]);
        session_start();
        self::$on = true;
    }
}

final class DbSessionHandler implements \SessionHandlerInterface
{
    public function open($path, $name): bool { return true; }
    public function close(): bool { return true; }
    public function read($id): string|false
    {
        $r = Db::one('SELECT data, expires_at FROM sessions WHERE id=?', [$id]);
        if (!$r || strtotime($r['expires_at']) < time()) return '';
        return (string) $r['data'];
    }
    public function write($id, $data): bool
    {
        $exp = date('Y-m-d H:i:s', time() + 1800);
        /* user_id is a real FK: an anonymous session must store NULL, not 0 (no user id 0). */
        $uid = isset($_SESSION['uid']) ? (int) $_SESSION['uid'] : null;
        Db::upsert('sessions', ['id' => $id, 'user_id' => $uid, 'data' => $data,
            'ip_hash' => Util::ipHash(), 'ua' => Util::ua(), 'expires_at' => $exp], ['id']);
        return true;
    }
    public function destroy($id): bool { Db::run('DELETE FROM sessions WHERE id=?', [$id]); return true; }
    public function gc($max): int|false
    {
        Db::run('DELETE FROM sessions WHERE expires_at < ?', [date('Y-m-d H:i:s')]);
        return 0;
    }
}
