<?php
/**
 * Re-seed BLOCKS only (idempotent) — used after editing zone payloads in the seed files so a
 * content fix never requires re-running the full seed (which replaces products).
 *
 *   php tools/reseed_blocks.php
 */
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

use Nm\Db;

$C = require __DIR__ . '/seed_content.php';
$B = $C['blocks'];

Db::run('DELETE FROM block_i18n');
Db::run('DELETE FROM blocks');
foreach ($B as $i => $b) {
    Db::run('INSERT INTO blocks(zone,kind,sort_order,payload) VALUES(?,?,?,?)',
        [$b['zone'], $b['kind'], $i, json_encode($b['base'], JSON_UNESCAPED_UNICODE)]);
    $id = Db::lastId();
    foreach (cfg('langs') as $l) {
        Db::run('INSERT INTO block_i18n(block_id,lang,title,eyebrow,payload) VALUES(?,?,?,?,?)',
            [$id, $l, $b['title'][$l] ?? null, $b['eyebrow'][$l] ?? null,
             json_encode($b['payload'][$l] ?? $b['base'], JSON_UNESCAPED_UNICODE)]);
    }
}
Nm\Cache::forgetGroup('data');
printf("reseeded blocks: %d zones × %d languages\n", count($B), count(cfg('langs')));
