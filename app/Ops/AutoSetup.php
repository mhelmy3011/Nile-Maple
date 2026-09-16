<?php
namespace Nm\Ops;

use Nm\Db;
use Nm\Cache;

/**
 * First-request database bootstrap for a shared-hosting deploy with no SSH / remote-MySQL
 * access. bootstrap.php calls maybeRun() on every request; it is a no-op unless
 * storage/SETUP_PENDING exists, so cost after the one real run is a single file_exists().
 *
 * storage/SETUP_PENDING and storage/seed_data.php ship inside the deploy zip. On success both
 * are deleted, which is what makes this self-disabling — nothing needs to remember to turn it
 * off, and it cannot replay itself once the marker is gone.
 */
final class AutoSetup
{
    public static function maybeRun(): void
    {
        $marker = nm_path('storage/SETUP_PENDING');
        if (!is_file($marker) || cfg('db.driver') !== 'mysql') return;

        $fh = @fopen($marker, 'r+');
        if (!$fh) return;
        if (!flock($fh, LOCK_EX)) { fclose($fh); return; }
        try {
            clearstatcache(true, $marker);
            if (!is_file($marker)) return;   // another request already finished this
            self::run($marker);
        } catch (\Throwable $e) {
            error_log('[nm] AutoSetup failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            // marker stays — the next request retries
        } finally {
            flock($fh, LOCK_UN);
            fclose($fh);
        }
    }

    private static function run(string $marker): void
    {
        set_time_limit(180);
        $seedFile = nm_path('storage/seed_data.php');
        if (!is_file($seedFile)) { error_log('[nm] AutoSetup: storage/seed_data.php missing'); return; }

        if ((int) Db::val('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?', ['categories'])
            && (int) Db::val('SELECT COUNT(*) FROM categories') > 0) {
            @unlink($marker);   // schema+data already present — nothing to do
            return;
        }

        $seed = require $seedFile;
        foreach ($seed['schema'] as $sql) Db::pdo()->exec($sql);

        Db::tx(static function () use ($seed): void {
            foreach ($seed['tables'] as $table => $rows) {
                if (!$rows) continue;
                $cols = array_keys($rows[0]);
                $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $cols) . '`) VALUES ('
                     . implode(',', array_fill(0, count($cols), '?')) . ')';
                $st = Db::pdo()->prepare($sql);
                foreach ($rows as $row) $st->execute(array_values($row));
            }
        });

        @unlink($seedFile);
        @unlink($marker);
        Cache::forgetGroup('data');
        error_log('[nm] AutoSetup: production database seeded successfully');
    }
}
