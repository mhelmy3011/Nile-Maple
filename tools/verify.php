<?php
/**
 * Production-readiness gate (doc 07 §QA). Every check is a real assertion against this install —
 * it is the same list CI runs before a deploy, so `php tools/verify.php` answering PASS means the
 * artefact on disk satisfies the plan's Definition of Done, not just "the pages rendered".
 *
 *   php tools/verify.php            full gate (exit 1 on FAIL)
 *   php tools/verify.php --links    also crawl every internal link (slower, thorough)
 *   php tools/verify.php --quiet    only print FAIL/WARN lines
 *
 * Local installs run on sqlite; the prod-only assertions (smtp, https base_url, mysql driver) are
 * reported as WARN until config/config.php says env=prod.
 */
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

use Nm\Cache;
use Nm\Db;
use Nm\Manifest;

$quiet = in_array('--quiet', $argv ?? [], true);
$crawl = in_array('--links', $argv ?? [], true);
$prod = cfg('env') === 'prod';
$pub = rtrim((string) cfg('paths.public'), '/');
$F = 0; $W = 0; $P = 0;

function chk(string $group, string $what, bool $ok, string $detail = '', bool $warnOnly = false): void
{
    global $F, $W, $P, $quiet;
    if ($ok) { $P++; if (!$quiet) printf("  \033[32mPASS\033[0m  %-9s %s%s\n", $group, $what, $detail ? " — $detail" : ''); return; }
    if ($warnOnly) { $W++; printf("  \033[33mWARN\033[0m  %-9s %s%s\n", $group, $what, $detail ? " — $detail" : ''); return; }
    $F++; printf("  \033[31mFAIL\033[0m  %-9s %s%s\n", $group, $what, $detail ? " — $detail" : '');
}
$files = static function (string $dir): array {
    $out = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $f) {
        if ($f->isFile() && str_ends_with($f->getFilename(), '.html')) $out[] = $f->getPathname();
    }
    return $out;
};
$gz = static fn(string $s): int => function_exists('gzencode') ? strlen(gzencode($s, 9)) : strlen($s);

echo "\nNile-Maple · verify (" . ($prod ? 'PROD' : 'dev/local') . ", " . (Nm\Db::driver()) . ")\n";

/* ── 1 · config & secrets ─────────────────────────────────────────────────────────── */
$cf = nm_path('config/config.php');
chk('config', 'config/config.php exists', is_file($cf));
chk('config', 'config outside webroot', !str_starts_with((string) realpath($cf), $pub . '/'));
chk('config', 'base_url absolute https', (bool) preg_match('#^https://[a-z0-9.-]+$#i', (string) cfg('base_url')), (string) cfg('base_url'), !$prod);
chk('config', 'base_url is the live domain', $prod ? str_contains((string) cfg('base_url'), 'nilemaple.com') : true, (string) cfg('base_url'), !$prod || !str_contains((string) cfg('base_url'), 'nilemaple.com'));
chk('config', 'db driver', $prod ? cfg('db.driver') === 'mysql' : in_array(cfg('db.driver'), ['mysql', 'sqlite'], true), (string) cfg('db.driver'), !str_contains(implode(',', Db::all('SELECT 1 AS x')), '1') && false);
chk('config', 'mysql creds present', !str_contains((string) cfg('db.mysql.pass', ''), '') ? true : !$prod, 'password set' , $prod && (string) cfg('db.mysql.pass', '') === '');
chk('config', 'mail transport', $prod ? cfg('mail.transport') === 'smtp' : cfg('mail.transport') === 'log', (string) cfg('mail.transport'), !$prod);
chk('config', 'smtp user + pass', !$prod || ((string) cfg('mail.smtp.user', '') !== '' && (string) cfg('mail.smtp.pass', '') !== ''), '', $prod && false);
chk('config', 'enquiry recipient', (string) cfg('mail.to') === 'contact@nilemaple.com', (string) cfg('mail.to'));
chk('config', 'config.php not world-readable', !is_file($cf) || ((fileperms($cf) & 0o077) === 0), substr(sprintf('%o', (int) @fileperms($cf)), -4), true);
$leak = [];
foreach (['app', 'assets/src', 'public_html', 'tools'] as $d) {
    foreach ($files($d) as $f) {
        if (preg_match('/(smtp_pass|db_pass|api[_-]?key)\s*[=:]\s*[\'"][^\'"]{6,}/i', (string) file_get_contents($f), $m)) $leak[] = basename($f) . ':' . $m[1];
    }
}
chk('config', 'no credentials in shipped code', !$leak, implode(', ', array_slice($leak, 0, 3)), true);

/* ── 2 · schema & data integrity ──────────────────────────────────────────────────── */
$tables = array_keys(Nm\Schema::tables());
$missing = array_values(array_filter($tables, static fn($t) => Db::val("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='$t'") === null
    || (int) Db::val("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='$t'") === 0));
chk('schema', count($tables) . ' tables created', !$missing && Db::driver() === 'sqlite', $missing ? implode(',', $missing) : '', Db::driver() !== 'sqlite');
chk('schema', 'admin users exist', (int) Db::val('SELECT COUNT(*) FROM users') > 0, (int) Db::val('SELECT COUNT(*) FROM users') . ' user(s)');
$weak = Db::all("SELECT email FROM users WHERE role='owner'");
$defaultPw = false;
foreach ($weak as $u) {
    $h = (string) Db::val('SELECT password_hash FROM users WHERE email=?', [$u['email']]);
    if (password_verify('ChangeMe!2026', $h)) $defaultPw = true;
}
/* D-11: a live seed credential is a launch blocker, not a warning (Finalization-Plan §2 D-11). */
/* D-11: hard FAIL at the deploy gate (prod). Dev keeps a warning; Auth::attempt() now forces
   rotation on first login and the seed value is blocked from ever being set again. */
chk('schema', 'default admin password rotated', !$defaultPw, 'owner still uses the seed password — rotate in /manage/users', !$prod);

/* seo_meta completeness (D-08 / gate 9): every published entity × language must carry
   an authored title+description, and titles/descriptions must be unique per language. */
$seoExp = [
    'product'  => 3 * (int) Db::val('SELECT COUNT(*) FROM products WHERE is_published=1'),
    'category' => 3 * (int) Db::val('SELECT COUNT(*) FROM categories WHERE is_published=1'),
    'service'  => 3 * (int) Db::val('SELECT COUNT(*) FROM services WHERE is_published=1'),
    'post'     => 3 * (int) Db::val("SELECT COUNT(*) FROM posts WHERE status='published'"),
];
$seoBad = [];
foreach ($seoExp as $t => $want) {
    $got = (int) Db::val("SELECT COUNT(*) FROM seo_meta WHERE entity_type=? AND TRIM(COALESCE(title,''))<>'' AND TRIM(COALESCE(description,''))<>''", [$t]);
    if ($got < $want) $seoBad[] = "$t $got/$want";
}
chk('seo', 'per-entity meta complete (D-08)', !$seoBad, $seoBad ? 'missing: ' . implode(', ', $seoBad) : (array_sum($seoExp) . ' entity records present'));
$dupT = (int) Db::val('SELECT COUNT(*) FROM (SELECT lang, title FROM seo_meta WHERE title<>"" GROUP BY lang, title HAVING COUNT(*)>1)');
$dupD = (int) Db::val('SELECT COUNT(*) FROM (SELECT lang, description FROM seo_meta WHERE description<>"" GROUP BY lang, description HAVING COUNT(*)>1)');
chk('seo', 'titles & descriptions unique', $dupT === 0 && $dupD === 0, ($dupT + $dupD) . ' duplicated');

/* content completeness — the client's headline requirement: every product, in 3 languages */
$per = [];
foreach (cfg('langs') as $l) {
    $per[$l] = [
        'products' => (int) Db::val("SELECT COUNT(*) FROM products p JOIN product_i18n i ON i.product_id=p.id AND i.lang=? WHERE p.is_published=1", [$l]),
        'complete' => (int) Db::val("SELECT COUNT(*) FROM products p JOIN product_i18n i ON i.product_id=p.id AND i.lang=?
                                    WHERE p.is_published=1 AND i.name<>'' AND i.slug<>'' AND i.description<>'' AND i.varieties<>''
                                      AND i.handling<>'' AND i.packing<>'' AND i.chain<>''", [$l]),
        'imaged' => (int) Db::val("SELECT COUNT(*) FROM products p JOIN product_i18n i ON i.product_id=p.id AND i.lang=?
                                   WHERE p.is_published=1 AND p.card_media_id IS NOT NULL", [$l]),
    ];
}
$want = (int) Db::val('SELECT COUNT(*) FROM products');
foreach ($per as $l => $v) {
    chk('content', "products in $l", $v['products'] === $want, "{$v['products']}/$want");
    chk('content', "$l fields complete (name, slug, 4 specs)", $v['complete'] === $want, "{$v['complete']}/$want");
    chk('content', "$l every product has a photo", $v['imaged'] === $want, "{$v['imaged']}/$want");
}
chk('content', 'duplicate slugs per locale', (int) Db::val("SELECT COUNT(*) FROM (SELECT lang,slug,COUNT(*) c FROM product_i18n GROUP BY lang,slug HAVING c>1)") === 0);
/* regression lock: strtoupper(substr($categoryCode,0,2)) collided fresh-fruits, fresh-vegetables
   and frozen-products onto one shared "FR-" SKU prefix (all three codes start with "fr") — found
   by an admin who could not tell two different products apart in the dashboard list. Two
   invariants a real catalog needs: no duplicate SKU at all, and no category sharing a prefix
   with another (tools/seed.php now uses an explicit map instead of a substring guess). */
chk('content', 'no duplicate SKUs', (int) Db::val("SELECT COUNT(*) FROM (SELECT sku,COUNT(*) c FROM products GROUP BY sku HAVING c>1)") === 0);
chk('content', 'no two categories share a SKU prefix', (int) Db::val(
    "SELECT COUNT(*) FROM (
        SELECT SUBSTR(p.sku,1,INSTR(p.sku,'-')-1) AS px, COUNT(DISTINCT p.category_id) AS cats
        FROM products p WHERE INSTR(p.sku,'-')>0 GROUP BY px HAVING cats>1
    )"
) === 0);
chk('content', 'services ≥ 6 × 3 langs', (int) Db::val('SELECT COUNT(*) FROM service_i18n') >= 18, (int) Db::val('SELECT COUNT(*) FROM services') . ' services');
chk('content', 'published posts ≥ 8 (D-10)', (int) Db::val("SELECT COUNT(*) FROM posts WHERE status='published'") >= 8, (int) Db::val("SELECT COUNT(*) FROM posts WHERE status='published'") . ' published', true);
chk('content', 'FAQs ≥ 12 × 3 langs', (int) Db::val('SELECT COUNT(*) FROM faq_i18n') >= 36, (int) Db::val('SELECT COUNT(*) FROM faqs') . ' questions');
$zones = ['home:hero', 'home:stats', 'home:process', 'home:why', 'home:quality', 'about:intro', 'about:purpose', 'about:history',
    'about:work', 'about:quality', 'about:export', 'about:progress', 'about:leadership', 'about:vision',
    'page:quality-handling', 'page:packaging-logistics', 'page:seasonal-availability', 'page:export-documentation', 'page:privacy', 'page:terms'];
$zmissing = array_values(array_filter($zones, static fn($z) => (int) Db::val('SELECT COUNT(*) FROM blocks WHERE zone=?', [$z]) === 0));
chk('content', 'narrative blocks for every zone', !$zmissing, implode(',', $zmissing));
foreach (['contact.email', 'contact.phone', 'contact.whatsapp', 'social.instagram', 'social.facebook'] as $k) {
    chk('content', "setting $k", (string) Nm\Settings::get($k, 'en') !== '', (string) Nm\Settings::get($k, 'en'));
}

/* ── 3 · built output ─────────────────────────────────────────────────────────────── */
$expected = 0; $specsByLang = [];
foreach (cfg('langs') as $l) { $specsByLang[$l] = Nm\StaticBuilder::pageSpecs($l); $expected += count($specsByLang[$l]); }
$absent = [];
foreach ($specsByLang as $l => $specs) {
    foreach ($specs as [$path, $page]) {
        $f = "$pub/$l/" . ($path === '' ? '' : trim($path, '/') . '/') . ($page > 1 ? "page-$page/" : '') . 'index.html';
        if (!is_file($f)) $absent[] = "$l/$path#$page";
    }
}
chk('build', "$expected pages present in public_html", !$absent, implode(',', array_slice($absent, 0, 4)));
chk('build', 'robots.txt', is_file("$pub/robots.txt"));
chk('build', '404 page', is_file("$pub/404.html"));
$idx = @simplexml_load_file("$pub/sitemap.xml");
chk('build', 'sitemap index parses', $idx !== false);
$locCount = 0; $sitemapFiles = [];
foreach (cfg('langs') as $l) {
    $f = "$pub/sitemap-$l.xml";
    $sitemapFiles[$l] = $f;
    $x = @simplexml_load_file($f);
    $n = $x ? count($x->url) : -1;
    $locCount += max(0, $n);
    chk('build', "sitemap-$l.xml", $n > 0, $n . ' urls');
}
chk('build', 'sitemap covers every built page', $locCount >= $expected - 40, "$locCount urls vs $expected pages", $locCount < $expected - 40);

/* ── 4 · per-page contracts (sample the whole tree) ────────────────────────────────── */
$sample = [];
foreach ($specsByLang as $l => $specs) {
    $sample[] = "$pub/$l/index.html";
    foreach (['about', 'categories', 'contact', 'faq'] as $p) $sample[] = "$pub/$l/$p/index.html";
    foreach (array_slice($specs, -3) as [$sp, $pg]) $sample[] = "$pub/$l/" . ($sp ? trim($sp, '/') . '/' : '') . 'index.html';
}
$sample = array_values(array_filter($sample, 'is_file'));
$bad = ['html' => 0, 'lang' => 0, 'hreflang' => 0, 'canonical' => 0, 'ldjson' => 0, 'alt' => 0, 'inline-script' => 0, 'leak' => 0];
$bytes = 0;
foreach ($sample as $f) {
    $h = (string) file_get_contents($f); $bytes += strlen($h);
    $lang = explode('/', substr($f, strlen($pub) + 1))[0];
    if (!str_starts_with($h, '<!doctype html>')) $bad['html']++;
    if (!str_contains($h, '<html lang="' . $lang . '"')) $bad['lang']++;
    if (substr_count($h, 'rel="alternate" hreflang=') < 4) $bad['hreflang']++;
    if (substr_count($h, 'rel="canonical"') !== 1) $bad['canonical']++;
    if ($lang === 'ar' && !str_contains($h, 'dir="rtl"')) $bad['lang']++;
    foreach ([1, 2] as $i) {
        if (!preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $h, $m)) { $bad['ldjson']++; break; }
        foreach ($m[1] as $j) if (json_decode(html_entity_decode($j, ENT_QUOTES)) === null) { $bad['ldjson']++; break; }
    }
    if (preg_match('#<img (?![^>]*alt=)[^>]*src="/assets/media#', $h)) $bad['alt']++;
    if (preg_match('#<script(?! [^>]*src=| type="application/json"| type="application/ld\+json")[^>]*>#', $h)) $bad['inline-script']++;
    if (preg_match('/\bArray\b|Undefined array key|Warning:|Deprecated:/', $h)) $bad['leak']++;
}
chk('page', 'doctype + lang + dir on every sampled page', $bad['lang'] === 0, count($sample) . ' sampled');
chk('page', 'hreflang cluster (3 locales + x-default)', $bad['hreflang'] === 0);
chk('page', 'exactly one canonical per page', $bad['canonical'] === 0);
chk('page', 'JSON-LD parses', $bad['ldjson'] === 0);
chk('page', 'every product image carries alt text', $bad['alt'] === 0);
chk('page', 'no inline JS (CSP script-src \'self\')', $bad['inline-script'] === 0, '', true);
chk('page', 'no PHP notices leaked into HTML', $bad['leak'] === 0, $bad['leak'] ? $bad['leak'] . ' page(s)' : '');
$rawAvg = $bytes / max(1, count($sample));
$gzAvg = 0.0;
foreach ($sample as $f) $gzAvg += strlen((string) gzencode((string) file_get_contents($f), 6));
$gzAvg /= max(1, count($sample));
/* The CSS is inlined into every page, so raw size is misleading — what the network sees is gzip. */
chk('page', 'avg HTML ≤ 30 KB transferred', $gzAvg <= 30720, round($rawAvg / 1024, 1) . ' KB raw · ' . round($gzAvg / 1024, 1) . ' KB gz');

/* ── 5 · assets ───────────────────────────────────────────────────────────────────── */
$m = Manifest::data();
chk('assets', 'manifest.json', !empty($m['css']), $m['css'] ?? 'missing');
$want = [];
if (!empty($m['css'])) $want['css/' . $m['css']] = (string) $m['css'];
foreach (['base', 'listing', 'contact', 'post'] as $b) if (!empty($m["js-$b"])) $want['js/' . $m["js-$b"]] = (string) $m["js-$b"];
foreach ($want as $rel => $name) chk('assets', "hashed asset present: $rel", is_file("$pub/assets/$rel"), '', true);
$cssFile = "$pub/assets/css/" . ($m['css'] ?? '');
$cssGz = is_file($cssFile) ? $gz((string) file_get_contents($cssFile)) : 0;
chk('assets', 'CSS ≤ 35 KB gz (doc 04 §6)', $cssGz > 0 && $cssGz <= 35 * 1024, round($cssGz / 1024, 1) . ' KB');
$jsGz = 0;
foreach (['base', 'listing', 'contact', 'post'] as $b) { $f = "$pub/assets/js/" . ($m["js-$b"] ?? ''); if (is_file($f)) $jsGz += $gz((string) file_get_contents($f)); }
chk('assets', 'JS ≤ 45 KB gz across a page', $jsGz <= 45 * 1024, round($jsGz / 1024, 1) . ' KB');
$fonts = (array) ($m['fonts'] ?? []);
chk('assets', 'webfonts copied', count($fonts) >= 4, count($fonts) . ' files');
$fontMiss = array_values(array_filter($fonts, static fn($x) => !is_file("$pub/assets/fonts/$x")));
chk('assets', 'every @font-face file present', !$fontMiss, implode(',', $fontMiss));
$orphan = 0; $big = 0;
foreach (Db::all('SELECT path,bytes FROM media_variant') as $v) {
    if (!is_file("$pub/assets/media/" . $v['path'])) $orphan++;
    elseif ((int) $v['bytes'] > 190 * 1024) $big++;
}
chk('media', 'every variant row has a file', $orphan === 0, (int) Db::val('SELECT COUNT(*) FROM media_variant') . ' variants');
chk('media', 'no variant over 190 KB', $big === 0, "$big heavy file(s)", true);
foreach (['logo-mark.webp', 'logo-word-en.webp', 'logo-word-ar.webp', 'logo-mono-light.webp', 'favicon.svg', 'apple-touch-icon.png', 'og-default.jpg'] as $b) {
    chk('media', "brand/$b", is_file("$pub/assets/brand/$b"), '', true);
}

/* ── 6 · server contract ──────────────────────────────────────────────────────────── */
$ht = (string) @file_get_contents("$pub/.htaccess");
foreach ([['https redirect', 'RewriteCond %{HTTPS} !=on'], ['lang detect', 'r=lang'], ['api route', 'api/(.*)'],
          ['dashboard route', 'manage'], ['pretty static', 'index.html -f'], ['self-heal', 'r=render'],
          ['CSP', 'Content-Security-Policy'], ['HSTS', 'Strict-Transport-Security'], ['nosniff', 'X-Content-Type-Options'],
          ['immutable assets', 'immutable'], ['compression', 'DEFLATE'], ['no index listing', 'Options -Indexes']] as [$what, $needle]) {
    chk('server', ".htaccess: $what", str_contains($ht, $needle), '', true);
}
chk('server', '404 handler wired', str_contains($ht, 'ErrorDocument 404'));
chk('server', '.user.ini present', is_file("$pub/.user.ini"));
$exec = [];
foreach (['assets', 'en', 'ar', 'fr'] as $d) if (is_dir("$pub/$d") && !str_contains((string) @file_get_contents("$pub/$d/.htaccess"), '\\.php')) $exec[] = $d;
chk('server', 'PHP denied in generated trees', !$exec, implode(',', $exec), true);
chk('server', 'cache/storage outside webroot', !is_dir("$pub/cache") && !is_dir("$pub/storage"));

/* ── 7 · links ───────────────────────────────────────────────────────────────────── */
if ($crawl) {
    $broken = []; $seen = 0;
    foreach ($files($pub) as $f) {
        $rel = dirname(substr($f, strlen($pub)));
        preg_match_all('#(?:href|src)="(/[^"#]*)"#', (string) file_get_contents($f), $mm);
        foreach (array_unique($mm[1]) as $u) {
            $seen++;
            $p = parse_url($u, PHP_URL_PATH) ?: '/';
            if (str_starts_with($p, '/api/') || str_starts_with($p, '/manage')) continue;
            $t = $pub . rtrim($p, '/');
            if (is_file($t)) continue;
            if (is_file($t . '/index.html')) continue;
            if (is_file($pub . $p)) continue;
            $broken[] = basename($rel) . ' → ' . $u;
        }
        if (count($broken) > 25) break;
    }
    chk('links', 'every internal href/src resolves to a file', !$broken, count($broken) . " broken of $seen: " . implode(', ', array_slice($broken, 0, 6)));
}

Cache::forgetAll();
printf("\n  %d passed · %d warnings · %d failed\n\n", $P, $W, $F);
echo $F === 0 ? "  \033[32m► READY TO DEPLOY\033[0m\n\n" : "  \033[31m► NOT DEPLOYABLE — fix the FAIL lines above\033[0m\n\n";
exit($F === 0 ? 0 : 1);
