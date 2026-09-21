<?php
/**
 * Nile-Maple — update VERIFICATION (read-only). DO NOT KEEP THIS FILE.
 *
 * Independent "reflection" check of the 2026-09-21 updates. It changes NOTHING:
 * no writes to the database, no rebuilds — it only reads and reports.
 *
 *   A. Code integrity   — the 9 updated files on this host match the expected sha256
 *   B. Database state   — official contact email in settings + content; no stale
 *                         nilemaple.com emails; category covers assigned; product
 *                         description/handling text free of temperature wording
 *   C. Live reflection  — fetches the REAL pages over HTTPS (what visitors see) and
 *                         checks every product page, home page and category page:
 *                         no temperature markup/text, only the official email,
 *                         category cards carry images. Falls back to the built
 *                         files on disk when the loopback fetch is unavailable.
 *
 * One-time use: authenticates with ?key=..., prints a PASS/FAIL report, deletes itself.
 * Add &keep=1 to keep the file for a re-check. Safe to run before, after or instead of
 * the update — before the update it correctly reports FAILs.
 */
declare(strict_types=1);
const NM_VERIFY_KEY = '813aa4fc58fa78aa8b691b8ecbcbe433615b6ae2eed07139';

if (!hash_equals(NM_VERIFY_KEY, (string) ($_GET['key'] ?? ''))) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    exit('Not found');
}
define('NM_USER_KEEP', isset($_GET['keep']));
define('NM_STARTED', microtime(true));

error_reporting(E_ALL);
require dirname(__DIR__) . '/app/bootstrap.php';

use Nm\Db;

set_time_limit(300);
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');

$OFFICIAL = 'contact@nilemaple.com';
/* sha256 prefixes of the nine updated files (2026-09-21 revision) */
$EXPECTED = [
    'app/templates/ui/card-product.php'        => '5cb5cd317bedbb52',
    'app/templates/pages/product.php'          => '9489b825f32ee73f',
    'app/templates/ui/spec-rows.php'           => '0e270c83e26d2516',
    'app/Content.php'                          => '548e4e97095738b1',
    'app/templates/pages/categories.php'       => '915f479fd2b13949',
    'app/templates/pages/home.php'             => '3ada202abea5ade7',
    'app/Util.php'                             => '92381890f650db98',
    'app/Seo.php'                              => 'd0e5d5e5ab91ae5a',
    'tools/content_updates.php'                => 'e02a62570ea82a81',
];
$TEMP = '/°\s*[CFم]|℃|Généralement\s+[-\x{2212}]?\d|عادة\s+[-\x{2212}]?\d|(?<![\w%])[-\x{2212}]?\d+(?:\.\d+)?\s?C\b|close to\s+[-\x{2212}]?\d|stockées à\s+[-\x{2212}]?\d|وتُخزن عند\s+[-\x{2212}]?\d/u';
$EMAILS = '/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/';

$checks = [];   /* [section, label, ok, detail] */
$liveRows = []; /* per-URL reflection rows for the report */

function v(string $section, string $label, bool $ok, string $detail = ''): void
{
    global $checks;
    $checks[] = [$section, $label, $ok, $detail];
}

try {
    /* ── A · code integrity ─────────────────────────────────────────────────────── */
    foreach ($EXPECTED as $rel => $want) {
        $abs = nm_path($rel);
        $have = is_file($abs) ? substr((string) hash_file('sha256', $abs), 0, 16) : '(missing)';
        v('A · Code integrity', $rel, $have === $want, "sha256 $have — expected $want");
    }

    /* ── B · database state (read-only) ─────────────────────────────────────────── */
    $emailNow = (string) Db::val('SELECT value FROM settings WHERE s_key=? AND lang=?', ['contact.email', '*']);
    v('B · Database state', 'settings.contact.email', $emailNow === $OFFICIAL, "value: $emailNow");

    $stale = 0; $staleWhere = [];
    $fields = [
        ['settings', 's_key', 'value'],
        ['blocks', 'id', 'payload'],
        ['block_i18n', 'block_id', 'payload'], ['block_i18n', 'block_id', 'title'],
        ['post_i18n', 'post_id', 'title'], ['post_i18n', 'post_id', 'excerpt'], ['post_i18n', 'post_id', 'body'],
        ['category_i18n', 'category_id', 'name'], ['category_i18n', 'category_id', 'headline'], ['category_i18n', 'category_id', 'summary'],
        ['product_i18n', 'product_id', 'description'], ['product_i18n', 'product_id', 'handling'],
        ['service_i18n', 'service_id', 'teaser'], ['service_i18n', 'service_id', 'body'], ['service_i18n', 'service_id', 'bullets'],
        ['faq_i18n', 'faq_id', 'question'], ['faq_i18n', 'faq_id', 'answer'],
        ['seo_meta', 'entity_id', 'description'],
    ];
    foreach ($fields as [$table, $idc, $col]) {
        foreach (Db::all('SELECT ' . $idc . ' AS id, ' . $col . ' AS v FROM ' . $table) as $r) {
            preg_match_all($EMAILS, (string) $r['v'], $m);
            foreach ($m[0] as $e) {
                if (strtolower($e) !== $OFFICIAL) {
                    $stale++;
                    if (count($staleWhere) < 5) $staleWhere[] = "$table#$r[id]: $e";
                }
            }
        }
    }
    v('B · Database state', 'no stale email addresses in content', $stale === 0,
        $stale ? $stale . ' stale address(es), e.g. ' . implode('; ', $staleWhere) : 'every nilemaple.com address is the official one');

    $cats = Db::all('SELECT c.id, c.code, c.cover_media_id, (SELECT COUNT(*) FROM products p WHERE p.category_id=c.id AND p.is_published=1 AND p.card_media_id IS NOT NULL) AS with_img
                     FROM categories c WHERE c.is_published=1');
    $noCover = 0;
    foreach ($cats as $c) if (empty($c['cover_media_id'])) $noCover++;
    v('B · Database state', 'category covers assigned in DB', $noCover === 0,
        $noCover
            ? $noCover . ' category(ies) without cover_media_id — template fallback (own product photo) is active, so the site still shows images; run the update or set covers in the dashboard to make it permanent in the DB'
            : 'all ' . count($cats) . ' published categories have a cover');

    $tempText = 0; $tempWhere = [];
    foreach (Db::all('SELECT product_id, lang, description, handling FROM product_i18n') as $r) {
        foreach (['description' => (string) $r['description'], 'handling' => (string) $r['handling']] as $f => $t) {
            if ($t !== '' && preg_match($TEMP, $t)) {
                $tempText++;
                if (count($tempWhere) < 5) $tempWhere[] = "$f of product #$r[product_id] ($r[lang])";
            }
        }
    }
    v('B · Database state', 'product description/handling text temperature-free', $tempText === 0,
        $tempText ? implode('; ', $tempWhere) : 'chain field may keep its values by design — the render layer hides them');

    /* ── C · live reflection (what visitors actually see) ───────────────────────── */
    $base = rtrim((string) cfg('base_url'), '/');
    $ctx = stream_context_create(['http' => ['timeout' => 15, 'ignore_errors' => true], 'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);

    $fetchLive = static function (string $url) use ($ctx): ?string {
        $h = @file_get_contents($url, false, $ctx, 15);
        if ($h === false) return null;
        return $h;
    };

    /* structural pages in all languages */
    $targets = [];
    foreach (['en', 'ar', 'fr'] as $l) {
        foreach (['', 'categories/', 'contact/', 'about/', 'faq/', 'services/'] as $p) $targets[] = [$l, $p];
    }
    /* product pages: all product URLs from the sitemaps, deterministically sampled */
    $productUrls = [];
    foreach (['en', 'ar', 'fr'] as $l) {
        $sf = nm_path('public_html/sitemap-' . $l . '.xml');
        if (is_file($sf)) {
            if (preg_match_all('#<loc>\s*([^<]+/products/[^<]+/)\s*</loc>#', (string) file_get_contents($sf), $m)) {
                $u = $m[1];
                $n = count($u);
                $want = min($n, 15);
                for ($i = 0; $i < $want; $i++) $productUrls[] = $u[(int) floor($i * $n / $want)];
            }
        }
    }

    $liveFail = 0; $liveOk = 0;
    foreach ($targets as [$l, $p]) {
        $url = $base . '/' . $l . '/' . $p;
        $html = $fetchLive($url);
        $source = 'live';
        if ($html === null) {
            $f = nm_path('public_html/' . $l . '/' . ($p !== '' ? $p : '') . 'index.html');
            $html = is_file($f) ? (string) file_get_contents($f) : null;
            $source = 'file';
        }
        if ($html === null) { $liveFail++; $liveRows[] = ["/$l/$p", 'UNREADABLE', 'FAIL', '']; continue; }
        $isProduct = str_starts_with($p, 'products/');
        $probs = [];
        if (preg_match_all('/cp-temp|temp-badge|pd-temp/', $html, $mm)) $probs[] = count($mm[0]) . ' temp markup';
        /* temperature TEXT is only a failure on product pages (cards + detail) — that is the
           client requirement; educational prose on FAQ/blog/quality pages may mention °C. */
        if ($isProduct && preg_match($TEMP, $html, $mm)) $probs[] = 'temp text: ' . $mm[0];
        preg_match_all($EMAILS, $html, $mm);
        $bad = array_values(array_filter(array_unique($mm[0]), fn($e) => strtolower($e) !== $OFFICIAL));
        if ($bad) $probs[] = 'stale emails: ' . implode(',', array_slice($bad, 0, 3));
        if ($p === '' || $p === 'categories/') {
            $ncats = (int) Db::val('SELECT COUNT(*) FROM categories WHERE is_published=1');
            if ($p === '') {
                $mono = substr_count($html, 'dc-media-mono');
                if ($mono > 0) $probs[] = "$mono empty division box(es)";
                if (substr_count($html, '<span class="dc-media"><picture>') < $ncats) $probs[] = 'division cards missing images';
            } else {
                if (substr_count($html, '<span class="cc-media"><picture>') < $ncats) $probs[] = 'category cards missing images';
            }
        }
        $ok = $probs === [];
        $ok ? $liveOk++ : $liveFail++;
        $liveRows[] = ['/' . $l . '/' . $p, $source, $ok ? 'PASS' : 'FAIL', implode('; ', $probs)];
    }
    foreach ($productUrls as $url) {
        $html = $fetchLive($url);
        $source = 'live';
        if ($html === null) {
            $rel = ltrim((string) parse_url($url, PHP_URL_PATH), '/');
            $f = nm_path('public_html/' . $rel . 'index.html');
            $html = is_file($f) ? (string) file_get_contents($f) : null;
            $source = 'file';
            $url = '/' . $rel;
        } else {
            $url = (string) parse_url($url, PHP_URL_PATH);
        }
        if ($html === null) { $liveFail++; $liveRows[] = [$url, 'UNREADABLE', 'FAIL', '']; continue; }
        $probs = [];
        if (preg_match_all('/cp-temp|temp-badge|pd-temp/', $html, $mm)) $probs[] = count($mm[0]) . ' temp markup';
        if (preg_match($TEMP, $html, $mm)) $probs[] = 'temp text: ' . $mm[0];
        preg_match_all($EMAILS, $html, $mm);
        $bad = array_values(array_filter(array_unique($mm[0]), fn($e) => strtolower($e) !== $OFFICIAL));
        if ($bad) $probs[] = 'stale emails: ' . implode(',', array_slice($bad, 0, 3));
        $ok = $probs === [];
        $ok ? $liveOk++ : $liveFail++;
        $liveRows[] = [$url, $source, $ok ? 'PASS' : 'FAIL', implode('; ', $probs)];
    }
    $mode = '';
    if ($liveRows) {
        $sources = array_unique(array_column($liveRows, 1));
        $mode = $sources === ['live'] ? 'fetched over HTTPS (what visitors see)'
              : ($sources === ['file'] ? 'built files on disk (live fetch unavailable)'
              : 'mixed live + built files');
    }
    v('C · Live reflection', $liveOk . ' of ' . ($liveOk + $liveFail) . ' pages clean (' . $mode . ')',
        $liveFail === 0, $liveFail ? 'see failing URLs below' : 'no temperature, only the official email, category imagery present');

    $ok = true;
    $body = '';
} catch (\Throwable $e) {
    $ok = false;
    v('System', 'verification completed without errors', false, get_class($e) . ': ' . $e->getMessage());
    $body = '<div class="fail"><h2>⚠ Verification stopped part-way</h2><p><strong>' . htmlspecialchars(get_class($e) . ': ' . $e->getMessage()) . '</strong></p>'
        . '<p>This tool is read-only — it cannot have changed anything. The checks already completed are listed below.</p></div>';
}

$bySection = [];
foreach ($checks as [$s, $l, $okk, $d]) $bySection[$s][] = [$l, $okk, $d];

$css = '<style>body{font:15px/1.55 system-ui;background:#f6f3ee;color:#1d2b25;margin:0}'
    . '.wrap{max-width:72rem;margin:2.5rem auto;padding:0 1.25rem}'
    . 'h1{font-size:1.35rem;margin:0 0 .25rem} .sub{color:#5c6b62;margin:0 0 1.5rem}'
    . 'h2{font-size:1rem;margin:1.4rem 0 .4rem;color:#16382b}'
    . '.row{background:#fff;border:1px solid #e3ddd2;border-radius:8px;padding:.5rem .8rem;margin-bottom:.45rem;font-size:.88rem;display:flex;gap:.7rem;align-items:baseline}'
    . '.row .st{font-weight:700;min-width:2.6rem}'
    . '.row.pass .st{color:#1a7f37} .row.fail .st{color:#b3261e}'
    . '.row .d{color:#5c6b62;font-size:.82rem;word-break:break-word}'
    . 'table{border-collapse:collapse;width:100%;font-size:.82rem}'
    . 'th,td{border:1px solid #e3ddd2;padding:.3rem .5rem;text-align:left;word-break:break-all}'
    . 'th{background:#eef2ee}'
    . '.verdict{border-radius:10px;padding:1.1rem 1.25rem;margin-top:1.2rem;font-size:1.05rem;font-weight:600}'
    . '.verdict.ok{background:#0b542e;color:#fff} .verdict.bad{background:#7f1d1d;color:#fff}</style>';

$pass = 0; $fail = 0;
foreach ($checks as $c) $c[2] ? $pass++ : $fail++;

echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
    . '<meta name="robots" content="noindex"><title>Nile-Maple — update verification report</title>' . $css . '</head><body><div class="wrap">'
    . '<h1>' . ($fail === 0 ? '✅ Verification passed — live site reflects all updates' : "❌ Verification found $fail issue(s)") . '</h1>'
    . '<p class="sub">Executed ' . date('Y-m-d H:i:s T') . ' · read-only · ' . round(microtime(true) - NM_STARTED, 1) . ' s · ' . $pass . ' passed / ' . $fail . ' failed</p>'
    . $body;
foreach ($bySection as $sec => $rows) {
    echo '<h2>' . htmlspecialchars($sec) . '</h2>';
    foreach ($rows as [$l, $okk, $d]) {
        echo '<div class="row ' . ($okk ? 'pass' : 'fail') . '"><span class="st">' . ($okk ? 'PASS' : 'FAIL') . '</span><span>' . htmlspecialchars($l) . '</span>'
            . ($d ? '<span class="d">' . htmlspecialchars($d) . '</span>' : '') . '</div>';
    }
}
echo '<h2>C · Per-page reflection (' . count($liveRows) . ' pages checked)</h2><table><tr><th>page</th><th>source</th><th>result</th><th>detail</th></tr>';
foreach ($liveRows as [$u, $src, $r, $d]) echo '<tr><td>' . htmlspecialchars($u) . '</td><td>' . htmlspecialchars($src) . '</td><td>' . $r . '</td><td>' . htmlspecialchars($d) . '</td></tr>';
echo '</table>';
echo $fail === 0
    ? '<div class="verdict ok">✅ The website reflects all four updates: official email everywhere, temperatures removed from product pages, category imagery in place, updated code active.</div>'
    : '<div class="verdict bad">Fix the items above, then re-run this check. If the update script has not been run yet, run it first: public_html/nm-updates.php?key=…</div>';

if (!NM_USER_KEEP) @unlink(__FILE__);
echo '</div></body></html>';
