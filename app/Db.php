<?php
namespace Nm;

use PDO;

/** PDO wrapper: mysql (prod) / sqlite (local+tests). Prepared statements only (doc 04 §7). */
final class Db
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo) return self::$pdo;
        if (cfg('db.driver') === 'sqlite') {
            $f = cfg('db.sqlite');
            if (!is_dir(dirname($f))) mkdir(dirname($f), 0775, true);
            self::$pdo = new PDO('sqlite:' . $f, null, null, [PDO::ATTR_TIMEOUT => 30]);
            /*
             * WAL keeps a -shm file that every reader has to map. That is fine on a real disk and
             * unfunny on a virtual one: under the WASM filesystem used by tools/serve.mjs the WAL
             * pragma throws, and if the file header still says WAL then every later query dies with
             * "locking protocol". So: ask for WAL, and if the host refuses, checkpoint and fall back
             * to a journal that needs no shared memory.
             */
            $wal = filter_var((string) cfg('db.sqlite_wal', '1'), FILTER_VALIDATE_BOOLEAN);
            try {
                self::$pdo->exec('PRAGMA journal_mode=' . ($wal ? 'WAL' : 'MEMORY'));
            } catch (\Throwable) {
                try {
                    self::$pdo->exec('PRAGMA wal_checkpoint(TRUNCATE)');   /* keep -wal content, then drop it */
                    self::$pdo->exec('PRAGMA journal_mode=MEMORY');
                } catch (\Throwable) { /* pragmas are optimisations, not requirements */ }
            }
            foreach (['foreign_keys=ON', 'busy_timeout=5000'] as $pr) {
                try { self::$pdo->exec('PRAGMA ' . $pr); } catch (\Throwable) { /* as above */ }
            }
        } else {
            $m = cfg('db.mysql');
            self::$pdo = new PDO(
                "mysql:host={$m['host']};dbname={$m['name']};charset=utf8mb4",
                $m['user'], $m['pass'],
                [PDO::ATTR_EMULATE_PREPARES => false]
            );
        }
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return self::$pdo;
    }

    public static function driver(): string { return cfg('db.driver'); }

    public static function run(string $sql, array $p = []): \PDOStatement
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($p);
        return $st;
    }
    public static function all(string $sql, array $p = []): array { return self::run($sql, $p)->fetchAll(); }
    public static function one(string $sql, array $p = []): ?array { $r = self::run($sql, $p)->fetch(); return $r ?: null; }
    public static function val(string $sql, array $p = []): mixed { $r = self::one($sql, $p); return $r ? reset($r) : null; }
    /** portable upsert (sqlite ON CONFLICT / mysql ON DUPLICATE KEY) */
    public static function upsert(string $table, array $data, array $keys): void
    {
        $cols = array_keys($data);
        $vals = array_values($data);
        if (self::driver() === 'sqlite') {
            $upd = implode(',', array_map(fn($c) => "$c=excluded.$c", array_diff($cols, $keys)));
            $sql = 'INSERT INTO ' . $table . '(' . implode(',', $cols) . ') VALUES(' . implode(',', array_fill(0, count($cols), '?')) . ')';
            if ($upd) $sql .= ' ON CONFLICT(' . implode(',', $keys) . ') DO UPDATE SET ' . $upd;
            else $sql .= ' ON CONFLICT(' . implode(',', $keys) . ') DO NOTHING';
        } else {
            $upd = implode(',', array_map(fn($c) => "$c=VALUES($c)", array_diff($cols, $keys)));
            $sql = 'INSERT INTO ' . $table . '(' . implode(',', $cols) . ') VALUES(' . implode(',', array_fill(0, count($cols), '?')) . ')';
            if ($upd) $sql .= ' ON DUPLICATE KEY UPDATE ' . $upd;
        }
        self::run($sql, $vals);
    }
    /**
     * Column names of a table, cached per request. The admin list screens need it: the *_i18n
     * children do not share a shape (products have no title, posts no name, FAQs no slug), so a
     * hardcoded column list 500s on whichever entity it forgot.
     */
    public static function columns(string $table): array
    {
        static $cache = [];
        if (isset($cache[$table])) return $cache[$table];
        $cache[$table] = self::driver() === 'sqlite'
            ? array_column(self::all('PRAGMA table_info(' . $table . ')'), 'name')
            : array_column(self::all(
                'SELECT column_name AS c FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=?',
                [$table]), 'c');
        return $cache[$table];
    }

    public static function lastId(): int { return (int) self::pdo()->lastInsertId(); }
    public static function tx(callable $fn): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try { $r = $fn(); $pdo->commit(); return $r; }
        catch (\Throwable $e) { $pdo->rollBack(); throw $e; }
    }
}
