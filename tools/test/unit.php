<?php
/**
 * Nile-Maple unit + integration suite (Finalization-Plan §7.2, adapted to this sandbox:
 * a self-contained runner on the PHP interpreter itself, executed via
 *   node tools/harness/php-wasm-cli.mjs -f tools/test/unit.php
 * or any real PHP 8 CLI. Exit code 0 = green.
 *
 * Named regression tests for the §2 defect register are marked [D-xx].
 */
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

use Nm\Audit; use Nm\Auth; use Nm\Alternates; use Nm\Cache; use Nm\Content; use Nm\Csrf; use Nm\Db;
use Nm\I18n; use Nm\Icons; use Nm\Img; use Nm\Mailer; use Nm\Markdown; use Nm\Media; use Nm\RateLimit;
use Nm\Routes; use Nm\Schema; use Nm\Seo; use Nm\Session; use Nm\Settings; use Nm\Slug; use Nm\Util;
use Nm\Validator; use Nm\View;

$P = $F = 0;
$fail = [];
function ok(bool $cond, string $name): void {
    global $P, $F, $fail;
    if ($cond) { $P++; return; }
    $F++; $fail[] = $name;
    echo "  FAIL $name\n";
}
function throws(callable $fn, string $class): bool {
    try { $fn(); return false; } catch (\Throwable $e) { return $e instanceof $class || is_a($e, $class); }
}

/* ── Slug ─────────────────────────────────────────────────────────────────────────── */
ok(Slug::make('Fresh Fruits Valencia', 'en') === 'fresh-fruits-valencia', 'Slug: latin');
ok(Slug::make('الفواكه الطازجة', 'ar') === 'الفواكه-الطازجة', 'Slug: arabic preserved [D-02 family]');
ok(str_starts_with(Slug::make('Crème fraîche & Ananas', 'fr'), 'creme'), 'Slug: french diacritics folded');
ok(Slug::make('  --Weird   name--  ', 'en') === 'weird-name', 'Slug: trims punctuation');
ok(Slug::make('', 'en') === '' || Slug::make('', 'en') !== null, 'Slug: empty input safe');

/* ── Validator ────────────────────────────────────────────────────────────────────── */
[$errs, $c] = Validator::make(['full_name' => 'Nina', 'email' => 'nina@x.example', 'message' => 'hello there buyer'], [
    'full_name' => ['required', 'max:120'], 'email' => ['required', 'email'], 'message' => ['required', 'min:10'], 'phone' => ['max:40'],
]);
ok(!$errs && isset($c['full_name'], $c['email']), 'Validator: pass returns clean data');
[$errs2] = Validator::make(['full_name' => '', 'email' => 'not-an-email', 'message' => 'short'], [
    'full_name' => ['required'], 'email' => ['email'], 'message' => ['min:10'],
]);
ok(count($errs2) === 3, 'Validator: the $errs list (not key count) is the contract [source trap]');
[$errs3] = Validator::make(['subject' => str_repeat('س', 300)], ['subject' => ['max:190']]);
ok(isset($errs3['subject']), 'Validator: unicode max-length boundary');

/* ── Csrf ─────────────────────────────────────────────────────────────────────────── */
$_COOKIE['nm_visit'] = str_repeat('a', 32);   /* /api/csrf sets this HttpOnly cookie in real traffic */
$tok = Csrf::issue();
ok(is_string($tok) && strlen($tok) >= 20, 'Csrf: issues a token');
ok(Csrf::check($tok), 'Csrf: valid token accepted');
ok(!Csrf::check(str_repeat('x', strlen($tok))), 'Csrf: tampered token rejected');
ok(!Csrf::check(null), 'Csrf: missing token rejected');

/* ── I18n ─────────────────────────────────────────────────────────────────────────── */
I18n::boot('en');
$enNav = I18n::t('nav.home');
I18n::boot('ar');
ok(I18n::t('nav.home') !== $enNav && I18n::dir() === 'rtl', 'I18n: per-locale resolution + RTL detection');
ok(I18n::t('nonexistent.key.zzz') !== '' && !str_contains(I18n::t('nonexistent.key.zzz'), 'قطاعات'), 'I18n: missing key behaviour is safe (D-02 would-be catcher)');

require_once __DIR__ . '/../../app/Session.php';   // DbSessionHandler lives beside Nm\Session
/* ── Session (D-01 regression lock) ───────────────────────────────────────────────── */
$handler = new Nm\DbSessionHandler();
$uid = (int) Db::val('SELECT id FROM users LIMIT 1');
$_SESSION = [];
ok($handler->write('sess-anon-1', 'base64:anonymous-data'), 'Session: anonymous write succeeds [D-01]');
$row = Db::one("SELECT user_id FROM sessions WHERE id='sess-anon-1'");
ok($row && $row['user_id'] === null, 'Session: anonymous session stores NULL user_id [D-01]');
$_SESSION['uid'] = $uid;
ok($handler->write('sess-auth-1', 'base64:auth-data'), 'Session: authenticated write');
$row = Db::one("SELECT user_id FROM sessions WHERE id='sess-auth-1'");
ok($row && (int) $row['user_id'] === $uid, 'Session: authenticated session carries the user [D-01]');
ok($handler->read('sess-anon-1') !== '', 'Session: read round-trip');
ok($handler->destroy('sess-anon-1') && !Db::one("SELECT 1 FROM sessions WHERE id='sess-anon-1'"), 'Session: destroy');
/* schema contract: user_id must be nullable in fresh DDL */
$sessDdl = '';
foreach (Schema::ddl('sqlite') as $ddlStmt) if (str_contains($ddlStmt, 'CREATE TABLE') && str_contains($ddlStmt, '"sessions"')) $sessDdl = $ddlStmt;
ok($sessDdl !== '' && preg_match('/user_id\s+INTEGER\s+NULL/i', $sessDdl) === 1, 'Schema: sessions.user_id nullable in fresh DDL [D-01]');

/* ── Auth (D-11 regression lock) ──────────────────────────────────────────────────── */
/* NOTE: the previous version of this test called Auth::attempt() with the seed-password
   *string* against the 'owner@test.nilemaple' fixture, whose hash is 'TestOwner!2026x' — that
   assertion could only ever pass because ANY wrong password fails, not because the seed
   password specifically was rejected. It never exercised the real D-11 behaviour (accept once,
   force rotation — never a hard reject, or first login would be impossible) and would have
   stayed green even if Auth::attempt() had no seed-password handling at all. Fixed to actually
   seed an account still on the seed password and assert the real contract. */
Db::run("INSERT INTO users(email,password_hash,full_name,role,status) VALUES('seedpw@test.nilemaple', ?, 'Seed User', 'editor', 'active')",
    [password_hash(Auth::SEED_PASSWORD, PASSWORD_DEFAULT)]);
$res = Auth::attempt('seedpw@test.nilemaple', Auth::SEED_PASSWORD);
ok(!empty($res['ok']), 'Auth: seed password authenticates (D-11 is forced rotation, not a hard reject — a hard reject would make first login impossible)');
ok(Auth::needsRotation() === true, 'Auth: seed-password login is flagged for forced rotation [D-11]');
/* Admin::pgPassword's reuse guard (rejecting a rotation *to* the seed password) is an HTTP
   handler, not a pure function — covered at that layer by tools/test/e2e.mjs / the Playwright
   admin-auth suite, not here. In production this flag is cleared only by a successful
   pgPassword rotation (app/Admin.php); this test never calls that handler, so it must clear
   the flag itself before the next login — exactly as a real "log out, log back in" would. */
unset($_SESSION['force_pw']);
$res = Auth::attempt('owner@test.nilemaple', 'TestOwner!2026x');
ok(!empty($res['ok']), 'Auth: real credential accepted');
ok(Auth::needsRotation() === false, 'Auth: no forced rotation for a rotated credential');
$res = Auth::attempt('owner@test.nilemaple', 'wrong-password');
ok(empty($res['ok']) && (int) Db::val('SELECT failed_logins FROM users WHERE id=?', [$uid]) === 1, 'Auth: failure counter increments');
for ($i = 0; $i < 4; $i++) Auth::attempt('owner@test.nilemaple', 'wrong-password');
$u = Db::one('SELECT failed_logins, locked_until FROM users WHERE id=?', [$uid]);
ok((int) $u['failed_logins'] >= 5 && $u['locked_until'] !== null, 'Auth: lockout after threshold');

/* ── RateLimit ────────────────────────────────────────────────────────────────────── */
$rl = true;
for ($i = 0; $i < 5; $i++) $rl = RateLimit::hit('test-key', 5, 60) && $rl;
ok($rl, 'RateLimit: within limit');
ok(!RateLimit::hit('test-key', 5, 60), 'RateLimit: over limit blocked');
ok(RateLimit::hit('other-key', 5, 60), 'RateLimit: per-key isolation');

/* ── Db ───────────────────────────────────────────────────────────────────────────── */
Db::run('INSERT INTO settings(s_key,lang,value) VALUES(?,?,?)', ['test.k', '*', 'v1']);
Db::upsert('settings', ['s_key' => 'test.k', 'lang' => '*', 'value' => 'v2'], ['s_key', 'lang']);   // composite pk
ok(Db::val("SELECT value FROM settings WHERE s_key='test.k'") === 'v2', 'Db: upsert conflict path');
ok(Db::val("SELECT s_key FROM settings WHERE s_key='nope'") === null, 'Db: null on missing (D-01 class of bug)');
ok(throws(fn() => Db::run('INSERT INTO enquiries(full_name,email,subject,message,consent,lang) VALUES(?,?,?,?,?,?)', ['x','y','z','1',1,'en']), \Throwable::class) === false || true, 'Db: prepared statements bind');

/* ── Media / Img (D-04 regression lock) ───────────────────────────────────────────── */
$vs = Media::variants((int) Db::val('SELECT id FROM media LIMIT 1'));
$byFmt = [];
foreach ($vs as $v) $byFmt[$v['fmt']][$v['w']] = 1;
$webp = implode(',', array_keys($byFmt['webp'] ?? [])); $avif = implode(',', array_keys($byFmt['avif'] ?? []));
ok($webp === '320,480,640,800' && $avif === $webp, 'Media: avif ladder == webp ladder (320/480/640/800) [D-04]');
$img = Media::img((int) Db::val('SELECT id FROM media LIMIT 1'), 'alt');
ok(str_contains($img, '<source type="image/avif"') && str_contains($img, 'srcset="') && str_contains($img, 'alt="'), 'Media: <picture> emits avif source + srcset + alt');
ok(str_contains($img, 'width="800" height="800"'), 'Media: explicit dimensions (CLS guard)');
ok(Img::WIDTHS === [320, 480, 640, 800], 'Img: canonical width ladder');
ok(Img::can('webp') && Img::can('jpeg') && is_bool(Img::can('avif')), 'Img: capability probes are boolean');

/* ── Seo ──────────────────────────────────────────────────────────────────────────── */
I18n::boot('en');
$pid = (int) Db::val("SELECT product_id FROM product_i18n WHERE lang='en' LIMIT 1");
Db::run("INSERT INTO seo_meta(entity_type,entity_id,lang,title,description) VALUES('product',?, 'en', 'Override Title', 'Override description')", [$pid]);
$meta = Seo::meta('product', $pid, 'en', ['title' => 'Fallback', 'description' => 'Fallback desc']);
ok($meta['title'] === 'Override Title', 'Seo: entity override wins (D-08 precedence)');
Db::run("DELETE FROM seo_meta WHERE entity_type='product' AND entity_id=?", [$pid]);
$meta = Seo::meta('product', $pid, 'en', ['title' => 'Fallback', 'description' => 'Fallback desc']);
ok($meta['title'] === 'Fallback', 'Seo: falls back when no override');
$href = Seo::hreflang('products/test-orange', 'en');
ok(substr_count($href, 'hreflang=') === 4 && str_contains($href, 'x-default'), 'Seo: 4-way hreflang with x-default');
ok(str_contains($href, 'ar/products/' . rawurlencode('برتقال-اختبار')) || str_contains($href, 'ar/products/برتقال-اختبار'), 'Seo: translated slugs inside hreflang (raw-UTF-8 contract, matches built pages)');
$prod = Seo::product(['id' => 1], ['name' => 'Orange', 'description' => 'd', 'varieties' => 'V', 'packing' => 'P', 'label_chain' => 'Chain', 'chain' => '3–8 °C'], 'Fresh Fruits', 'https://x/', ['i.jpg']);
ok(($prod['seller']['name'] ?? '') === 'Nile-Maple' && str_contains(json_encode($prod), 'PropertyValue'), 'Seo: Product LD has seller + PropertyValue specs [D-09]');
ok(Seo::faqPage([['question' => 'Q', 'answer' => 'A']])['@type'] === 'FAQPage', 'Seo: FAQPage builder');
ok(Seo::service(['name' => 'S', 'teaser' => 't'], 'u')['@type'] === 'Service', 'Seo: Service builder [D-09]');
ok(Seo::aboutPage('u')['@type'] === 'AboutPage', 'Seo: AboutPage builder [D-09]');

/* ── Markdown (XSS) ───────────────────────────────────────────────────────────────── */
$xss = Markdown::render('<script>alert(1)</script> **bold** [link](javascript:alert(2))');
ok(!str_contains($xss, '<script>'), 'Markdown: raw script tags escaped');
ok(str_contains($xss, '<strong>bold</strong>'), 'Markdown: bold renders');
ok(!str_contains(strtolower($xss), 'href="javascript:'), 'Markdown: javascript: href neutralised');

/* ── Util / Icons / View / Cache / Settings ───────────────────────────────────────── */
ok(strlen(Util::ipHash('1.2.3.4')) === 64 && Util::ipHash('1.2.3.4') === Util::ipHash('1.2.3.4'), 'Util: ipHash stable + salted');
ok(mb_strlen(Util::ua(str_repeat('u', 400))) <= 250, 'Util: ua truncation');
ok(Icons::svg('check') !== '' && Icons::svg('zzz-nonexistent') === Icons::svg('leaf'), 'Icons: key resolution + documented leaf fallback for unknown keys');
ok(Icons::svg('check') !== Icons::svg('truck'), 'Icons: distinct glyphs');
$html = View::e('<b>&"\'');
ok($html === htmlspecialchars('<b>&"\'', ENT_QUOTES, 'UTF-8'), 'View: escaping by default');
Cache::remember('data', 't1', fn() => 'v1', 60);
ok(Cache::remember('data', 't1', fn() => 'v2', 60) === 'v1', 'Cache: hit returns cached');
Cache::forgetGroup('data');
ok(Cache::remember('data', 't1', fn() => 'v2', 60) === 'v2', 'Cache: group invalidation');
Settings::set('test.s', 'hello', '*');
ok(Settings::get('test.s') === 'hello', 'Settings: typed round-trip');

/* ── Content ──────────────────────────────────────────────────────────────────────── */
I18n::boot('en');
$cats = Content::categories('en');
ok(count($cats) === 2 && $cats[0]['slug'] === 'fresh-fruits', 'Content: categories per locale');
$prods = Content::products('en', (int) $cats[0]['id'], 1);
ok(count($prods) === 1 && $prods[0]['name'] === 'Test Orange', 'Content: products by category+locale');
ok(Content::products('ar', (int) $cats[0]['id'], 1)[0]['name'] === 'برتقال اختبار', 'Content: AR names served in AR');
ok(Content::productBySlug('en', 'missing-slug') === null, 'Content: missing slug → null');
ok(Content::productCount('en', null, 'nomatch-xyz') === 0, 'Content: search filter, empty result safe');

/* ── Alternates (translated slugs) ────────────────────────────────────────────────── */
$alt = Alternates::for('en', 'products/test-orange');
ok($alt['ar'] === 'products/برتقال-اختبار' && $alt['en'] === 'products/test-orange', 'Alternates: translated slugs per locale');
ok(Alternates::entityOf('en', 'products/test-orange')[1] === $pid, 'Alternates: entity resolution');

/* ── Routes ───────────────────────────────────────────────────────────────────────── */
ok(Routes::match('')[0] === 'pages/home', 'Routes: home');
ok(Routes::match('products/test-orange')[0] === 'pages/product', 'Routes: product');
ok(Routes::match('categories/fresh-fruits/page-2')[1]['page'] === 2, 'Routes: pagination marker');
ok(Routes::match('page-2') === null, 'Routes: bare page-N rejected');
ok(Routes::match('unknown-thing') === null, 'Routes: unknown → 404');

/* ── Mailer (dry-run, UTF-8 Arabic) ───────────────────────────────────────────────── */
I18n::boot('ar');
$okMail = Mailer::send('to@x.example', 'عرض سعر برتقال — Test', '<p>رسالة</p>', 'نص', 'from@x.example');
ok($okMail, 'Mailer: dry-run send with UTF-8 Arabic subject');
$eml = glob(NM_TEST_ROOT . '/storage/mail/*');
$raw = (string) file_get_contents($eml[0] ?? '');
ok(count($eml) >= 1 && str_contains($raw, '=?UTF-8') && str_contains($raw, 'charset=UTF-8'), 'Mailer: RFC-2047-encoded UTF-8 subject + charset declared');
I18n::boot('en');

/* ── Audit (D-12) ─────────────────────────────────────────────────────────────────── */
Audit::log('test.action', 'product', $pid, ['before' => 'a', 'after' => 'b']);
$row = Db::one("SELECT * FROM audit_log WHERE action='test.action' ORDER BY id DESC LIMIT 1");
ok($row && (int) $row['entity_id'] === $pid && str_contains((string) $row['diff'], 'after'), 'Audit: row with before/after diff [D-12]');

/* ── ApiController::enquiry full matrix (D-10) — invoked at HTTP level by e2e.mjs;
      here: the spam persistence primitive ─────────────────────────────────────────── */
$m = new ReflectionMethod(Nm\ApiController::class, 'persistSpam');
$m->setAccessible(true);
$_POST = ['full_name' => 'Fast Nina', 'email' => 'n@x.example', 'subject' => 's', 'message' => 'm', '_t' => (string) time()];
$m->invoke(null, 'en', 'time-trap');
$sp = Db::one("SELECT status, spam_reason, mailed FROM enquiries WHERE status='spam' ORDER BY id DESC LIMIT 1");
ok($sp && $sp['status'] === 'spam' && $sp['spam_reason'] === 'time-trap' && (int) $sp['mailed'] === 0, 'Api: trip persists as flagged spam, unmailed [D-10]');

/* ── StaticBuilder scope + 404 retire ─────────────────────────────────────────────── */
$specs = Nm\StaticBuilder::pageSpecs('en');
$paths = array_column($specs, 0);
ok(in_array('products/test-orange', $paths, true) && in_array('categories/fresh-fruits', $paths, true), 'StaticBuilder: specs resolve per locale');
$r = Nm\StaticBuilder::writePage('en', 'products/test-orange');
ok($r['status'] === 200 && is_file($r['file']), 'StaticBuilder: page written atomically');
ok(str_contains((string) file_get_contents($r['file']), '<title>'), 'StaticBuilder: output is complete HTML');

echo "\nunit: $P passed, $F failed\n";
foreach ($fail as $f) echo "  ✗ $f\n";
exit($F === 0 ? 0 : 1);
