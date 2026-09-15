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
echo "migrated ($driver): +$applied statements, $skipped already present, "
   . count(Schema::tables()) . " tables\n";
