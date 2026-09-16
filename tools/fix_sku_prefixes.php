<?php
/**
 * One-off backfill for the SKU-prefix collision fixed in tools/seed.php (see the comment there):
 * `strtoupper(substr($categoryCode,0,2))` put fresh-vegetables and frozen-products on the same
 * "FR-" prefix as fresh-fruits, since all three category codes start with "fr". This corrects
 * SKUs already written to an existing database without a full --fresh reseed (which would
 * discard any admin edits). Idempotent — re-running it is a no-op once corrected.
 *
 *   php tools/fix_sku_prefixes.php
 */
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

use Nm\Db;

$prefix = ['fresh-fruits' => 'FR', 'fresh-vegetables' => 'VG', 'frozen-products' => 'FZ', 'processed-canned' => 'PR'];
$fixed = 0;
foreach ($prefix as $code => $want) {
    $catId = Db::val('SELECT id FROM categories WHERE code=?', [$code]);
    if (!$catId) continue;
    $rows = Db::all('SELECT id, sku, source_index FROM products WHERE category_id=?', [(int) $catId]);
    foreach ($rows as $r) {
        $correct = $want . '-' . str_pad((string) $r['source_index'], 2, '0', STR_PAD_LEFT);
        if ($r['sku'] !== $correct) {
            Db::run('UPDATE products SET sku=? WHERE id=?', [$correct, (int) $r['id']]);
            $fixed++;
        }
    }
}
echo "sku prefixes corrected: $fixed row(s)\n";
