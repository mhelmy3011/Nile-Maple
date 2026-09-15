<?php
/**
 * Schema migration (doc 01 §3 / doc 04 §9). Idempotent: safe to run on every deploy.
 *   php tools/migrate.php            apply missing tables/indexes
 *   php tools/migrate.php --fresh    DROP every table first (local/dev only — destroys content)
 *   php tools/migrate.php --sql      print the DDL for the active driver and exit (no DB writes)
 *   php tools/migrate.php --check    exit non-zero if a table is missing (deploy gate)
 */
require __DIR__ . '/../app/bootstrap.php';
use Nm\Db; use Nm\Schema;

$argvv = $argv ?? [];
$fresh = in_array('--fresh', $argvv, true);
$sqlOnly = in_array('--sql', $argvv, true);
$check = in_array('--check', $argvv, true);
$driver = Db::driver();
$ddl = Schema::ddl($driver);

if ($sqlOnly) { echo implode(";\n", $ddl) . ";\n"; exit(0); }

if ($check) {
    $missing = [];
    foreach (array_keys(Schema::tables()) as $t) {
        $row = $driver === 'mysql'
            ? Db::one('SELECT COUNT(*) c FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?', [$t])
            : Db::one("SELECT COUNT(*) c FROM sqlite_master WHERE type='table' AND name=?", [$t]);
        if (!(int) ($row['c'] ?? 0)) $missing[] = $t;
    }
    if ($missing) { fwrite(STDERR, "missing tables: " . implode(', ', $missing) . "\n"); exit(1); }
    echo "schema ok (" . count(Schema::tables()) . " tables)\n"; exit(0);
}

if ($fresh) {
    if ($driver !== 'sqlite' && cfg('env') === 'prod') {
        fwrite(STDERR, "refusing --fresh in production on mysql\n"); exit(1);
    }
    foreach (array_reverse(array_keys(Schema::tables())) as $t) {
        $q = $driver === 'mysql' ? "`$t`" : "\"$t\"";
        if ($driver === 'mysql') Db::pdo()->exec('SET FOREIGN_KEY_CHECKS=0');
        Db::pdo()->exec("DROP TABLE IF EXISTS $q");
        if ($driver === 'mysql') Db::pdo()->exec('SET FOREIGN_KEY_CHECKS=1');
    }
}

$applied = 0; $skipped = 0;
foreach ($ddl as $sql) {
    try { Db::pdo()->exec($sql); $applied++; }
    catch (\Throwable $e) {
        $m = $e->getMessage();
        // "already present" is the expected outcome on a re-run; anything else is a real error.
        if (preg_match('/duplicate|already exists/i', $m)) { $skipped++; continue; }
        fwrite(STDERR, "MIGRATE FAILED: $m\n$sql\n"); exit(1);
    }
}

/* ── forward migration 001: sessions.user_id must be nullable (D-01) ──────────────────
 * Session::write() stores NULL for anonymous sessions (pre-auth /manage requests start a
 * session before any user exists), so NOT NULL here fatals every dashboard request.
 * Fresh installs already get the nullable column from the DDL above; existing databases
 * converge here. Sessions are ephemeral (30-minute expiry), so SQLite rebuilds the table
 * (no ALTER COLUMN) and only anonymous/login sessions are ever lost — users just re-login. */
$isNotNull = static function () use ($driver): bool {
    if ($driver === 'mysql') {
        $r = Db::one('SELECT IS_NULLABLE n FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?', ['sessions', 'user_id']);
        return $r && $r['n'] === 'NO';
    }
    $cols = Db::all('PRAGMA table_info("sessions")');
    foreach ($cols as $c) {
        if ($c['name'] === 'user_id') return (int) $c['notnull'] === 1 && !(bool) $c['pk'];
    }
    return false;   // table missing — DDL above just created it correctly
};
if (!$fresh && $isNotNull()) {
    if ($driver === 'mysql') {
        Db::pdo()->exec('ALTER TABLE sessions MODIFY user_id INT UNSIGNED NULL');
        echo "migrated 001: sessions.user_id is now nullable (ALTER)\n";
    } else {
        Db::pdo()->exec('DROP TABLE "sessions"');
        foreach (Schema::ddl('sqlite') as $sql) {
            if (str_contains($sql, 'CREATE TABLE IF NOT EXISTS "sessions"')) { Db::pdo()->exec($sql); break; }
        }
        foreach (Schema::ddl('sqlite') as $sql) {
            if (str_starts_with($sql, 'CREATE INDEX') && str_contains($sql, '"sessions"')) Db::pdo()->exec($sql);
        }
        echo "migrated 001: sessions table rebuilt with nullable user_id (sessions are ephemeral; re-login required)\n";
    }
    $applied++;
}

/* ── forward migration 002: enquiries gain status='spam' + spam_reason (D-10) ─────────
 * SQLite encodes the enum as a CHECK constraint, which cannot be altered in place — the
 * table is rebuilt and every row is preserved. MySQL gets a MODIFY + ADD COLUMN. */
$enqNeedsUpgrade = static function () use ($driver): bool {
    if ($driver === 'mysql') {
        $r = Db::one('SELECT COLUMN_TYPE t FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?', ['enquiries', 'status']);
        return $r && !str_contains((string) $r['t'], 'spam');
    }
    $sql = (string) (Db::one("SELECT sql FROM sqlite_master WHERE type='table' AND name='enquiries'")['sql'] ?? '');
    return $sql !== '' && !str_contains($sql, "'spam'");
};
if (!$fresh && $enqNeedsUpgrade()) {
    if ($driver === 'mysql') {
        Db::pdo()->exec("ALTER TABLE enquiries MODIFY status ENUM('new','read','replied','closed','spam') NOT NULL DEFAULT 'new'");
        try { Db::pdo()->exec('ALTER TABLE enquiries ADD COLUMN spam_reason VARCHAR(40) NULL'); } catch (\Throwable) { /* already there */ }
    } else {
        $cols = 'id,full_name,email,phone,company,country,subject,product_interest,message,consent,lang,ip_hash,ua,status,mailed,mail_tries,created_at';
        Db::pdo()->exec('ALTER TABLE enquiries RENAME TO enquiries_old');
        foreach (Schema::ddl('sqlite') as $sql) {
            if (str_contains($sql, 'CREATE TABLE IF NOT EXISTS "enquiries"')) { Db::pdo()->exec($sql); break; }
        }
        Db::pdo()->exec("INSERT INTO enquiries($cols,spam_reason) SELECT $cols,NULL FROM enquiries_old");
        Db::pdo()->exec('DROP TABLE enquiries_old');
    }
    echo "migrated 002: enquiries.status now supports 'spam' (+ spam_reason), all rows preserved\n";
    $applied++;
}

echo "migrated ($driver): +$applied statements, $skipped already present, "
   . count(Schema::tables()) . " tables\n";
