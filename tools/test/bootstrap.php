<?php
/**
 * Nile-Maple test bootstrap — isolated environment for tools/test/unit.php.
 * Runs under tools/harness/php-wasm-cli.mjs or a real PHP 8 CLI.
 *
 * Contract (Finalization-Plan §7.5): deterministic fixtures, isolated SQLite per run,
 * mail captured by dry-run transport, no test ever touches production data.
 */
declare(strict_types=1);

define('NM_TEST_ROOT', sys_get_temp_dir() . '/nm-test-' . uniqid('', false));
foreach (['public_html/assets/media', 'cache/data', 'cache/lang', 'storage/logs', 'storage/tmp', 'storage/mail', 'storage/backups'] as $d) mkdir(NM_TEST_ROOT . "/$d", 0775, true);

$GLOBALS['NM_CONFIG_OVERRIDE'] = [
    'env' => 'dev',
    'base_url' => 'https://nilemaple.com',
    'db' => ['driver' => 'sqlite', 'sqlite' => NM_TEST_ROOT . '/db.sqlite', 'sqlite_wal' => false],
    'paths' => [
        'root' => NM_TEST_ROOT,
        'public' => NM_TEST_ROOT . '/public_html',
        'cache' => NM_TEST_ROOT . '/cache',
        'storage' => NM_TEST_ROOT . '/storage',
        'app' => __DIR__ . '/../../app',
    ],
    'langs' => ['en', 'ar', 'fr'],
    'default_lang' => 'en',
    'rtl' => ['ar'],
    'mail' => ['transport' => 'log', 'smtp' => [], 'from' => ['contact@nilemaple.com', 'Nile-Maple'], 'to' => 'contact@nilemaple.com'],
    'admin' => ['path' => '/manage', 'idle_min' => 30, 'allow_ips' => [], 'totp_secret' => ''],
    'cdn_purge_url' => '',
];

require __DIR__ . '/../../app/bootstrap.php';

/* schema + deterministic minimal fixtures (NOT the production seed) */
$ddl = Nm\Schema::ddl('sqlite');
foreach ($ddl as $sql) Nm\Db::pdo()->exec($sql);

Nm\Db::run("INSERT INTO users(email,password_hash,full_name,role,status) VALUES('owner@test.nilemaple', ?, 'Test Owner', 'owner', 'active')",
    [password_hash('TestOwner!2026x', PASSWORD_DEFAULT)]);
foreach ([['fresh-fruits', 'citrus'], ['frozen-products', 'snowflake']] as $i => [$code, $icon]) {
    Nm\Db::run('INSERT INTO categories(code,icon_key,accent,sort_order,is_published) VALUES(?,?,?,?,1)', [$code, $icon, 'green', $i]);
    foreach (['en', 'ar', 'fr'] as $l) {
        $names = ['fresh-fruits' => ['en' => 'Fresh Fruits', 'ar' => 'الفواكه الطازجة', 'fr' => 'Fruits frais'],
                  'frozen-products' => ['en' => 'Frozen Products', 'ar' => 'المنتجات المجمدة', 'fr' => 'Produits surgelés']][$code];
        $slugs = ['fresh-fruits' => ['en' => 'fresh-fruits', 'ar' => 'فواكه-طازجة', 'fr' => 'fruits-frais'],
                  'frozen-products' => ['en' => 'frozen-products', 'ar' => 'منتجات-مجمدة', 'fr' => 'produits-surgeles']][$code];
        Nm\Db::run('INSERT INTO category_i18n(category_id,lang,name,slug,headline,summary) VALUES(?,?,?,?,?,?)',
            [$i + 1, $l, $names[$l], $slugs[$l], 'Headline', 'Summary text']);
    }
}
Nm\Db::run('INSERT INTO products(category_id,sku,sort_order,is_published) VALUES(1,?,1,1)', ['SKU-TEST']);
$pidFix = Nm\Db::lastId();
foreach (['en', 'ar', 'fr'] as $l) {
    Nm\Db::run('INSERT INTO product_i18n(product_id,lang,name,slug,description,label_varieties,varieties,label_handling,handling,label_packing,packing,label_chain,chain) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)',
        [$pidFix, $l, ['en' => 'Test Orange', 'ar' => 'برتقال اختبار', 'fr' => 'Orange test'][$l],
         ['en' => 'test-orange', 'ar' => 'برتقال-اختبار', 'fr' => 'orange-test'][$l],
         'A juicy test orange for the suite.', 'Varieties', 'Valencia, Navel', 'Handling', 'Handle with care', 'Packing', 'Ventilated cartons', 'Chain', '3–8 °C']);
}
/* sessions table shape guard data */
Nm\Db::run("INSERT INTO enquiries(full_name,email,subject,message,consent,lang,ip_hash,status) VALUES('Nina Buyer','nina@buyer.example','Orange quote','Please quote two containers of navel oranges.',1,'en','testiphash','new')");

/* minimal media fixture with a FULL parity ladder (mirrors the D-04 contract) */
Nm\Db::run("INSERT INTO media(filename,mime,width,height,bytes,source_ref) VALUES('t.jpg','image/jpeg',800,800,100,'fresh-fruits/t.jpg')");
$mid = Nm\Db::lastId();
foreach (['avif', 'webp'] as $fmt) foreach ([320, 480, 640, 800] as $w)
    Nm\Db::run('INSERT INTO media_variant(media_id,fmt,w,path,bytes) VALUES(?,?,?,?,?)', [$mid, $fmt, $w, "fresh-fruits/t-$w.$fmt", 5000]);
foreach ([640, 800] as $w) Nm\Db::run('INSERT INTO media_variant(media_id,fmt,w,path,bytes) VALUES(?,?,?,?,?)', [$mid, 'jpg', $w, "fresh-fruits/t-$w.jpg", 9000]);
foreach (['en', 'ar', 'fr'] as $l) Nm\Db::run('INSERT INTO media_i18n(media_id,lang,alt) VALUES(?,?,?)', [$mid, $l, 'Test orange photo']);
Nm\Db::run('UPDATE products SET card_media_id=?', [$mid]);
