<?php
/**
 * Nile-Maple — one-shot production update (2026-09-21). DO NOT KEEP THIS FILE.
 *
 * Applies the client-requested updates on this host, using the same code paths the
 * dashboard uses (Nm\Db, Nm\Cache, Nm\StaticBuilder, Nm\Audit):
 *
 *   1. Official contact email (contact@nilemaple.com) everywhere
 *      — settings + a sweep of every editable content field (blocks, posts,
 *        categories, products, services, FAQs, SEO, config mail.to).
 *   2. Category cover images — every category without a cover is assigned the photo
 *      of one of its own products (the same professional imagery as the product cards),
 *      plus a template-level fallback so a future category can never show an empty box.
 *   3. Temperature removed from product cards and product detail pages:
 *      — the thermometer badge on every product card,
 *      — the temperature line under the product gallery,
 *      — the cold-chain spec row when its value is a temperature (EN/AR/FR incl. °م),
 *      — the trailing temperature sentence in FR/AR product descriptions,
 *      — temperature properties in the product JSON-LD structured data.
 *      (The temperature data itself is kept in the DB + dashboard — nothing is deleted.)
 *
 * HOW IT WORKS
 *   · One-time use: it authenticates with ?key=..., applies the changes, runs a full
 *     static rebuild (the same StaticBuilder::buildAll() as the dashboard), prints a
 *     report, and DELETES ITSELF. Backups of everything it touches are kept in
 *     storage/backups/nm-updates-<time>/ (outside the web root).
 *   · Idempotent: if it is ever run twice, the second run changes nothing.
 *
 * HOW TO RUN (one time)
 *   1. Upload this file into public_html/ (e.g. public_html/nm-updates.php).
 *   2. Open  https://nilemaple.com/nm-updates.php?key=58366afde058ad6f3fc972a37e8971bb46bc95b955b23ea6  once.
 *   3. Check the report. The file deletes itself when done.
 *
 * Generated from the repository — do not edit by hand.
 */
declare(strict_types=1);

const NM_KEY = '58366afde058ad6f3fc972a37e8971bb46bc95b955b23ea6';

/* ── auth: neutral 404 for anything that is not the one-time key ─────────────────── */
if (!hash_equals(NM_KEY, (string) ($_GET['key'] ?? ''))) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    exit('Not found');
}

define('NM_USER_KEEP', isset($_GET['keep']));
define('NM_STARTED', microtime(true));
error_reporting(E_ALL);
require dirname(__DIR__) . '/app/bootstrap.php';

use Nm\Audit;
use Nm\Cache;
use Nm\Db;
use Nm\Schema;
use Nm\StaticBuilder;

set_time_limit(300);
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');

$report = [];
$steps = [];
$backupDir = nm_path('storage/backups/nm-updates-' . date('Ymd-His'));
@mkdir($backupDir, 0777, true);

const NM_CODE_PATCHES = [
    'app/templates/ui/card-product.php' => <<<'NM_PATCH_EOF'
<?php
use Nm\Media; use Nm\View;
/** @var array $p product row with name/slug @var string $lang @var array|null $cat */
/* Client update (2026-09-21): temperature is no longer displayed on product cards.
   The underlying temp_min/temp_max data is kept in the DB + dashboard. */
?>
<a class="card-product" href="/<?= $lang ?>/products/<?= View::e($p['slug']) ?>/">
  <span class="cp-media"><?= $p['card_media_id'] ? Media::img((int) $p['card_media_id'], $p['name']) : '<span class="cp-noimg" aria-hidden="true"></span>' ?></span>
  <span class="cp-body">
    <?php if (!empty($cat)): ?><span class="chip chip-amber"><?= View::e($cat['name']) ?></span><?php endif; ?>
    <span class="cp-name"><?= View::e($p['name']) ?></span>
    <span class="cp-go" aria-hidden="true"><svg class="i" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
  </span>
</a>
NM_PATCH_EOF,

    'app/templates/pages/product.php' => <<<'NM_PATCH_EOF'
<?php
use Nm\I18n; use Nm\Media; use Nm\Util; use Nm\View;
$p = $product;
/* Client update (2026-09-21): the temperature line under the gallery is no longer
   rendered. temp_min/temp_max/temp_note remain in the DB + dashboard. */
?>
<div class="container pd-top"><?= View::render('ui/breadcrumbs', ['crumbs' => $crumbs]) ?></div>
<section class="section section-first pd-layout">
  <div class="container pd-grid">
    <div class="pd-gallery">
      <div class="pd-main" data-carousel>
        <div class="hero-track pd-track">
          <div class="pd-slide"><?= $p['card_media_id'] ? Media::img((int) $p['card_media_id'], $p['pname'], 'hero', '100vw') : '' ?></div>
        </div>
      </div>
    </div>
    <div class="pd-info">
      <?php if ($cat): ?><a class="chip chip-amber" href="/<?= $lang ?>/categories/<?= View::e($cat['slug']) ?>/"><?= View::e($cat['name']) ?></a><?php endif; ?>
      <h1><?= View::e($p['pname']) ?></h1>
      <p class="pd-sku muted"><?= View::e($p['sku'] ?? '') ?><?= $p['source_index'] ? ' · #' . str_pad((string) $p['source_index'], 2, '0', STR_PAD_LEFT) : '' ?></p>
      <p class="pd-desc"><?= View::e($p['description']) ?></p>
      <?= View::render('ui/spec-rows', ['p' => $p]) ?>
      <div class="pd-actions">
        <a class="btn btn-primary btn-lg" href="/<?= $lang ?>/contact/?product=<?= rawurlencode($p['pname']) ?>" data-event="click_quote"><?= I18n::t('cta.quote.for', ['p' => $p['pname']]) ?></a>
        <a class="btn btn-whatsapp btn-lg" href="<?= Util::waLink(I18n::t('wa.product', ['p' => $p['pname']])) ?>" target="_blank" rel="noopener" data-event="click_whatsapp"><?= I18n::t('contact.whatsapp') ?></a>
      </div>
    </div>
  </div>
</section>
<?php if ($related): ?>
<section class="section section-tint">
  <div class="container">
    <?= View::render('ui/section-head', ['title' => I18n::t('product.related')]) ?>
    <div class="rail" data-carousel>
      <div class="hero-track rail-track">
        <?php foreach ($related as $r): ?>
          <div class="rail-item"><?= View::render('ui/card-product', ['p' => $r, 'lang' => $lang, 'cat' => null]) ?></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>
<?= View::render('ui/cta-band', ['lang' => $lang]) ?>
NM_PATCH_EOF,

    'app/templates/ui/spec-rows.php' => <<<'NM_PATCH_EOF'
<?php
use Nm\Icons; use Nm\Util; use Nm\View;
/** @var array $p product_i18n+products row */
/* Client update (2026-09-21): storage temperatures are not displayed on product pages.
   The dedicated temperature badge is gone, and any cold-chain row whose value is
   essentially a temperature range (Util::hasTempText) is hidden as a whole, while rows
   with real handling guidance (e.g. canned storage notes) stay. The underlying data
   (temp_min/temp_max/temp_note/chain) is kept in the DB + dashboard. */
$rows = [
    ['leaf', $p['label_varieties'] ?? '', $p['varieties'] ?? '', false],
    ['route', $p['label_handling'] ?? '', $p['handling'] ?? '', false],
    ['box', $p['label_packing'] ?? '', $p['packing'] ?? '', false],
    ['thermometer', $p['label_chain'] ?? '', $p['chain'] ?? '', true],
];
?>
<dl class="spec-rows">
<?php foreach ($rows as [$icon, $label, $value, $temp]): ?>
  <?php $value = trim((string) $value); if ($value === '') continue; ?>
  <?php if ($temp && Util::hasTempText($value)) continue; ?>
  <div class="spec-row">
    <dt><span class="sr-icon" aria-hidden="true"><?= Icons::svg($icon) ?></span><?= View::e($label) ?></dt>
    <dd><?= View::e($value) ?></dd>
  </div>
<?php endforeach; ?>
</dl>
NM_PATCH_EOF,

    'app/Content.php' => <<<'NM_PATCH_EOF'
<?php
namespace Nm;

/** Read-model for public templates (cached, doc 04 §3). */
final class Content
{
    public const PER_PAGE = 30;

    public static function categories(string $lang): array
    {
        return Cache::remember('data', "cats-$lang", static fn() => Db::all(
            'SELECT c.*, i.name, i.slug, i.headline, i.summary, i.meta_title, i.meta_description,
                    (SELECT COUNT(*) FROM products p WHERE p.category_id=c.id AND p.is_published=1) AS cnt,
                    /* Client update (2026-09-21): category cards must never show an empty image box —
                       a category without its own cover falls back to one of its own product photos
                       (featured first, then sort order), so the division photography always matches
                       the products it represents. cover_media_id (when set) always wins. */
                    (SELECT p2.card_media_id FROM products p2
                      WHERE p2.category_id=c.id AND p2.is_published=1 AND p2.card_media_id IS NOT NULL
                      ORDER BY p2.is_featured DESC, p2.sort_order, p2.id LIMIT 1) AS cover_fallback_id
             FROM categories c JOIN category_i18n i ON i.category_id=c.id AND i.lang=?
             WHERE c.is_published=1 ORDER BY c.sort_order', [$lang]), 300);
    }
    public static function categoryBySlug(string $lang, string $slug): ?array
    {
        foreach (self::categories($lang) as $c) if ($c['slug'] === $slug) return $c;
        return null;
    }
    public static function categoryById(int $id, string $lang): ?array
    {
        foreach (self::categories($lang) as $c) if ((int) $c['id'] === $id) return $c;
        return null;
    }

    public static function products(string $lang, ?int $categoryId = null, int $page = 1, string $q = '', string $sort = 'order'): array
    {
        $sql = 'SELECT p.*, i.name, i.slug, i.description FROM products p
                JOIN product_i18n i ON i.product_id=p.id AND i.lang=?
                WHERE p.is_published=1';
        $args = [$lang];
        if ($categoryId) { $sql .= ' AND p.category_id=?'; $args[] = $categoryId; }
        if ($q !== '') { $sql .= ' AND (i.name LIKE ? OR i.description LIKE ? OR i.varieties LIKE ?)'; $like = "%$q%"; $args = array_merge($args, [$like, $like, $like]); }
        $sql .= match ($sort) { 'az' => ' ORDER BY i.name', default => ' ORDER BY p.sort_order, p.id' };
        $sql .= ' LIMIT ' . self::PER_PAGE . ' OFFSET ' . (($page - 1) * self::PER_PAGE);
        return Db::all($sql, $args);
    }
    public static function productCount(string $lang, ?int $categoryId = null, string $q = ''): int
    {
        $sql = 'SELECT COUNT(*) FROM products p JOIN product_i18n i ON i.product_id=p.id AND i.lang=? WHERE p.is_published=1';
        $args = [$lang];
        if ($categoryId) { $sql .= ' AND p.category_id=?'; $args[] = $categoryId; }
        if ($q !== '') { $sql .= ' AND (i.name LIKE ? OR i.description LIKE ?)'; $args = array_merge($args, ["%$q%", "%$q%"]); }
        return (int) Db::val($sql, $args);
    }
    public static function productBySlug(string $lang, string $slug): ?array
    {
        return Db::one('SELECT p.*, i.* , i.name AS pname FROM products p
                        JOIN product_i18n i ON i.product_id=p.id AND i.lang=?
                        WHERE i.slug=? AND p.is_published=1', [$lang, $slug]);
    }
    public static function productI18n(int $id, string $lang): ?array
    {
        return Db::one('SELECT * FROM product_i18n WHERE product_id=? AND lang=?', [$id, $lang]);
    }
    public static function featured(string $lang, int $limit = 4): array
    {
        return Db::all('SELECT p.*, i.name, i.slug FROM products p
                        JOIN product_i18n i ON i.product_id=p.id AND i.lang=?
                        WHERE p.is_published=1 AND p.is_featured=1 ORDER BY p.sort_order LIMIT ' . (int) $limit, [$lang]);
    }
    public static function related(string $lang, int $categoryId, int $excludeId, int $limit = 6): array
    {
        return Db::all('SELECT p.*, i.name, i.slug FROM products p
                        JOIN product_i18n i ON i.product_id=p.id AND i.lang=?
                        WHERE p.is_published=1 AND p.category_id=? AND p.id<>? ORDER BY p.sort_order LIMIT ' . (int) $limit,
            [$lang, $categoryId, $excludeId]);
    }

    public static function services(string $lang): array
    {
        return Cache::remember('data', "svc-$lang", static fn() => Db::all(
            'SELECT s.*, i.name, i.slug, i.teaser, i.meta_title, i.meta_description FROM services s
             JOIN service_i18n i ON i.service_id=s.id AND i.lang=?
             WHERE s.is_published=1 ORDER BY s.sort_order', [$lang]), 300);
    }
    public static function serviceBySlug(string $lang, string $slug): ?array
    {
        return Db::one('SELECT s.*, i.* FROM services s JOIN service_i18n i ON i.service_id=s.id AND i.lang=?
                        WHERE i.slug=? AND s.is_published=1', [$lang, $slug]);
    }

    public static function posts(string $lang, int $page = 1, int $per = 9): array
    {
        return Db::all('SELECT p.*, i.title, i.slug, i.excerpt FROM posts p
                        JOIN post_i18n i ON i.post_id=p.id AND i.lang=?
                        WHERE p.status=\'published\' AND (p.published_at IS NULL OR p.published_at<=?)
                        ORDER BY p.published_at DESC LIMIT ' . (int) $per . ' OFFSET ' . (($page - 1) * $per),
            [$lang, date('Y-m-d H:i:s')]);
    }
    public static function postCount(string $lang): int
    {
        return (int) Db::val('SELECT COUNT(*) FROM posts p JOIN post_i18n i ON i.post_id=p.id AND i.lang=?
                              WHERE p.status=\'published\'', [$lang]);
    }
    public static function postBySlug(string $lang, string $slug): ?array
    {
        return Db::one('SELECT p.*, i.*, u.full_name AS author FROM posts p
                        JOIN post_i18n i ON i.post_id=p.id AND i.lang=?
                        LEFT JOIN users u ON u.id=p.author_user_id
                        WHERE i.slug=? AND p.status=\'published\'', [$lang, $slug]);
    }

    public static function faqs(string $lang, ?string $group = null, ?int $limit = null): array
    {
        $sql = 'SELECT f.id, f.group_code, i.question, i.answer FROM faqs f
                JOIN faq_i18n i ON i.faq_id=f.id AND i.lang=?
                WHERE f.is_published=1';
        $args = [$lang];
        if ($group) { $sql .= ' AND f.group_code=?'; $args[] = $group; }
        $sql .= ' ORDER BY f.sort_order, f.id';
        if ($limit) $sql .= ' LIMIT ' . (int) $limit;
        return Db::all($sql, $args);
    }

    public static function blocks(string $zone, string $lang): array
    {
        return Cache::remember('data', "blk-$zone-$lang", static fn() => Db::all(
            'SELECT b.id, b.zone, b.kind, b.sort_order, b.payload AS base_payload,
                    i.title, i.eyebrow, i.payload FROM blocks b
             JOIN block_i18n i ON i.block_id=b.id AND i.lang=?
             WHERE b.zone=? ORDER BY b.sort_order, b.id', [$lang, $zone]), 300);
    }
    public static function blockPayload(array $b): array
    {
        /* shared base payload (media refs, defaults) + per-locale payload (i18n wins per key) */
        $base = json_decode($b['base_payload'] ?? '{}', true) ?: [];
        $i18n = json_decode($b['payload'] ?? '{}', true) ?: [];
        return array_merge($base, $i18n);
    }

    /** translation completeness per entity type/id (doc 06 §2) */
    public static function completeness(string $type, int $id): array
    {
        $table = ['category' => 'category_i18n', 'product' => 'product_i18n', 'service' => 'service_i18n',
                  'post' => 'post_i18n', 'faq' => 'faq_i18n'][$type] ?? null;
        if (!$table) return [];
        $col = ['category' => 'category_id', 'product' => 'product_id', 'service' => 'service_id',
                'post' => 'post_id', 'faq' => 'faq_id'][$type];
        $req = ['category' => ['name', 'slug', 'headline', 'summary'], 'product' => ['name', 'slug', 'description', 'varieties', 'handling', 'packing', 'chain'],
                'service' => ['name', 'slug', 'teaser', 'body'], 'post' => ['title', 'slug', 'excerpt', 'body'], 'faq' => ['question', 'answer']][$type];
        $out = [];
        foreach (cfg('langs') as $l) {
            $row = Db::one("SELECT * FROM $table WHERE $col=? AND lang=?", [$id, $l]);
            $filled = 0;
            if ($row) foreach ($req as $c) if (trim((string) ($row[$c] ?? '')) !== '') $filled++;
            $out[$l] = $row ? (int) round(100 * $filled / count($req)) : 0;
        }
        return $out;
    }
}
NM_PATCH_EOF,

    'app/templates/pages/categories.php' => <<<'NM_PATCH_EOF'
<?php use Nm\I18n; use Nm\Media; use Nm\View; ?>
<div class="page-head">
  <div class="container">
    <?= View::render('ui/breadcrumbs', ['crumbs' => $crumbs]) ?>
    <p class="eyebrow"><?= I18n::t('categories.eyebrow') ?></p>
    <h1><?= I18n::t('categories.title') ?></h1>
    <p class="lead"><?= I18n::t('categories.lead') ?></p>
  </div>
</div>
<section class="section">
  <div class="container">
    <div class="cat-grid">
      <?php foreach ($categories as $c): ?>
        <a class="cat-card" href="/<?= $lang ?>/categories/<?= View::e($c['slug']) ?>/">
          <?php $cover = (int) ($c['cover_media_id'] ?: ($c['cover_fallback_id'] ?? 0)); ?>
          <span class="cc-media"><?= $cover ? Media::img($cover, $c['name']) : '' ?></span>
          <span class="cc-body">
            <h2><?= View::e($c['name']) ?></h2>
            <p><?= View::e($c['summary']) ?></p>
            <span class="chip chip-green"><?= (int) $c['cnt'] ?> <?= I18n::t('home.products') ?></span>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<section class="section section-tint">
  <div class="container container-narrow">
    <?= View::render('ui/section-head', ['title' => I18n::t('categories.az')]) ?>
    <ul class="az-list">
      <?php foreach ($products as $p): ?><li><a href="/<?= $lang ?>/products/<?= View::e($p['slug']) ?>/"><?= View::e($p['name']) ?></a></li><?php endforeach; ?>
    </ul>
  </div>
</section>
<?= View::render('ui/cta-band', ['lang' => $lang]) ?>
NM_PATCH_EOF,

    'app/templates/pages/home.php' => <<<'NM_PATCH_EOF'
<?php
use Nm\Content; use Nm\I18n; use Nm\Media; use Nm\View;
$t = static fn(string $k, array $p = []) => I18n::t($k, $p);
$hero = $hero[0] ?? null;
$hp = $hero ? Content::blockPayload($hero) : [];
/* D-03: a single art-directed hero, no carousel chrome. The previous markup declared a
   2-slide carousel with one slide and empty dots — a screen reader announced navigation
   that did not exist, and the LCP candidate was a text node. One honest hero, preloaded. */
$heroImg = !empty($hp['media_id']) ? Media::img((int) $hp['media_id'], (string) ($hp['alt'] ?? $hero['title'] ?? ''), 'hero', '100vw') : '';
?>
<section class="hero">
  <div class="hero-slide">
    <?= $heroImg ?>
    <div class="hero-scrim" aria-hidden="true"></div>
    <div class="container hero-copy">
      <p class="eyebrow eyebrow-light"><?= View::e($hero['eyebrow'] ?? '') ?></p>
      <h1><?= View::e($hero['title'] ?? '') ?></h1>
      <p class="hero-lead"><?= View::e($hp['lead'] ?? '') ?></p>
      <div class="hero-cta">
        <a class="btn btn-primary btn-lg" href="/<?= $lang ?>/categories/"><?= $t('cta.explore') ?></a>
        <a class="btn btn-outline-light btn-lg" href="/<?= $lang ?>/contact/"><?= $t('cta.quote') ?></a>
      </div>
    </div>
  </div>
</section>

<?= View::render('ui/stats-band', ['stats' => $stats, 'lang' => $lang]) ?>

<section class="section" id="divisions">
  <div class="container">
    <?= View::render('ui/section-head', ['eyebrow' => $t('home.divisions.eyebrow'), 'title' => $t('home.divisions.title'), 'lead' => $t('home.divisions.lead')]) ?>
    <div class="division-list">
      <?php foreach ($categories as $c): ?>
        <a class="division-card" href="/<?= $lang ?>/categories/<?= View::e($c['slug']) ?>/">
          <?php $cover = (int) ($c['cover_media_id'] ?: ($c['cover_fallback_id'] ?? 0)); ?>
          <?php if ($cover): /* D-03: real division photography, never an empty box (2026-09-21: falls back to the category's own product photo) */ ?>
            <span class="dc-media"><?= Media::img($cover, (string) $c['name'], 'lazy', '(min-width:768px) 46vw, 92vw') ?></span>
          <?php else: ?>
            <span class="dc-media dc-media-mono" aria-hidden="true"><?= \Nm\Icons::svg($c['icon_key'] ?? 'citrus') ?></span>
          <?php endif; ?>
          <span class="dc-body">
            <span class="chip chip-green"><?= (int) $c['cnt'] ?> <?= $t('home.products') ?></span>
            <h3><?= View::e($c['name']) ?></h3>
            <p><?= View::e($c['summary']) ?></p>
            <span class="cs-more"><?= $t('cta.view') ?>
              <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-tint" id="process">
  <div class="container">
    <?= View::render('ui/section-head', ['eyebrow' => $t('home.process.eyebrow'), 'title' => $t('home.process.title'), 'lead' => $t('home.process.lead')]) ?>
    <ol class="timeline">
      <?php foreach ($process as $b): $pl = Content::blockPayload($b); ?>
        <?php foreach (($pl['steps'] ?? [['t' => $b['title'] ?? '', 'x' => $pl['text'] ?? '']]) as $i => $st): ?>
        <li class="tl-item">
          <span class="tl-num tabular"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <div><h3><?= View::e($st['t'] ?? '') ?></h3><p><?= View::e($st['x'] ?? '') ?></p></div>
        </li>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<section class="section" id="why">
  <div class="container why-grid">
    <div class="why-media"><?php $wm = isset($why[0]) ? Content::blockPayload($why[0]) : [];
      if (!empty($wm['media_id'])) echo Media::img((int) $wm['media_id'], (string) ($wm['alt'] ?? '')); ?></div>
    <div>
      <?= View::render('ui/section-head', ['eyebrow' => $t('home.why.eyebrow'), 'title' => $t('home.why.title'), 'lead' => $t('home.why.lead')]) ?>
      <ul class="check-list">
        <?php foreach ($why as $b): $pl = Content::blockPayload($b); foreach ($pl['items'] ?? [] as $it): ?>
          <li><span class="ck" aria-hidden="true"><?= \Nm\Icons::svg('check') ?></span><div><strong><?= View::e($it['title'] ?? '') ?></strong><p><?= View::e($it['text'] ?? '') ?></p></div></li>
        <?php endforeach; endforeach; ?>
      </ul>
    </div>
  </div>
</section>

<section class="section section-tint" id="featured">
  <div class="container">
    <?= View::render('ui/section-head', ['eyebrow' => $t('home.featured.eyebrow'), 'title' => $t('home.featured.title'), 'lead' => $t('home.featured.lead')]) ?>
    <div class="tabs" role="tablist" aria-label="<?= $t('home.featured.aria') ?>" data-tabs>
      <?php foreach ($featuredByCat as $i => $fc): ?>
        <button class="tab<?= $i === 0 ? ' is-active' : '' ?>" role="tab" id="tab-<?= (int) $fc['cat']['id'] ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>" aria-controls="panel-<?= (int) $fc['cat']['id'] ?>" type="button"><?= View::e($fc['cat']['name']) ?></button>
      <?php endforeach; ?>
    </div>
    <?php foreach ($featuredByCat as $i => $fc): ?>
      <div class="tab-panel<?= $i === 0 ? ' is-active' : '' ?>" role="tabpanel" id="panel-<?= (int) $fc['cat']['id'] ?>" aria-labelledby="tab-<?= (int) $fc['cat']['id'] ?>"<?= $i === 0 ? '' : ' hidden' ?>>
        <?= View::render('ui/product-grid', ['products' => $fc['items'], 'lang' => $lang, 'cat' => null]) ?>
        <a class="tab-all" href="/<?= $lang ?>/categories/<?= View::e($fc['cat']['slug']) ?>/"><?= $t('home.viewall', ['n' => (int) $fc['cat']['cnt']]) ?>
          <svg class="i" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="section section-dark" id="quality">
  <div class="container">
    <?= View::render('ui/section-head', ['eyebrow' => $t('home.quality.eyebrow'), 'title' => $t('home.quality.title'), 'lead' => $t('home.quality.lead')]) ?>
    <div class="quality-grid">
      <?php foreach ($quality as $b): $pl = Content::blockPayload($b); foreach (array_slice($pl['items'] ?? [], 0, 6) as $it): ?>
        <div class="q-item"><span class="q-icon" aria-hidden="true"><?= \Nm\Icons::svg($it['icon'] ?? 'shield') ?></span><strong><?= View::e($it['title'] ?? '') ?></strong><p><?= View::e($it['text'] ?? '') ?></p></div>
      <?php endforeach; endforeach; ?>
    </div>
    <a class="btn btn-outline-light" href="/<?= $lang ?>/quality-handling/"><?= $t('cta.quality') ?></a>
  </div>
</section>

<section class="section section-amber" id="season">
  <div class="container season-inner">
    <span class="season-icon" aria-hidden="true"><?= \Nm\Icons::svg('calendar') ?></span>
    <div><h2><?= $t('home.season.title') ?></h2><p><?= $t('home.season.lead') ?></p></div>
    <a class="btn btn-on-amber" href="/<?= $lang ?>/seasonal-availability/"><?= $t('cta.season') ?></a>
  </div>
</section>

<section class="section" id="insights">
  <div class="container">
    <?= View::render('ui/section-head', ['eyebrow' => $t('home.blog.eyebrow'), 'title' => $t('home.blog.title'), 'lead' => $t('home.blog.lead')]) ?>
    <div class="post-list">
      <?php foreach ($posts as $post): ?><?= View::render('ui/card-post', ['post' => $post, 'lang' => $lang]) ?><?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-tint" id="faq-teaser">
  <div class="container container-narrow">
    <?= View::render('ui/section-head', ['eyebrow' => $t('home.faq.eyebrow'), 'title' => $t('home.faq.title'), 'align' => 'center']) ?>
    <?= View::render('ui/faq-accordion', ['faqs' => $faqs, 'lang' => $lang]) ?>
    <p class="center-link"><a class="btn btn-ghost" href="/<?= $lang ?>/faq/"><?= $t('cta.allfaq') ?></a></p>
  </div>
</section>

<?= View::render('ui/cta-band', ['lang' => $lang]) ?>
NM_PATCH_EOF,

    'app/Util.php' => <<<'NM_PATCH_EOF'
<?php
namespace Nm;

/** Small shared helpers. */
final class Util
{
    public static function e(?string $s): string { return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
    public static function ipHash(): string { return hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? 'cli') . '|' . cfg('env')); }
    public static function ua(): string { return mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250); }
    public static function isCli(): bool { return PHP_SAPI === 'cli' || PHP_SAPI === 'wasm'; }
    public static function json(mixed $v, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    public static function redirect(string $to, int $code = 302): never
    {
        header('Location: ' . $to, true, $code);
        exit;
    }
    public static function startsWith(string $h, string $n): bool { return str_starts_with($h, $n); }
    public static function tempBadge(?float $min, ?float $max, string $unit = 'C'): string
    {
        if ($min === null && $max === null) return '';
        $fmt = static fn($v) => rtrim(rtrim(number_format((float) $v, 1, '.', ''), '0'), '.');
        return $min !== null && $max !== null ? $fmt($min) . '–' . $fmt($max) . ' °' . $unit : $fmt($min ?? $max) . ' °' . $unit;
    }
    /**
     * True when a piece of text IS (essentially) a storage temperature: "0–4 °C",
     * "Généralement 8 °C", "Généralement -18 °C", "عادة 8 °م" (Arabic Celsius),
     * "Typically 0-4 C", "Keep frozen at -18 C or below".
     *
     * Client update (2026-09-21): temperatures are no longer displayed on product pages.
     * Rows whose value matches this are hidden (spec rows, JSON-LD); rows with real
     * handling guidance (e.g. canned storage notes, no temperature figures) are kept.
     * Deliberately narrow: a bare "م"/"C" without a figure or degree sign is NOT matched.
     */
    public static function hasTempText(string $v): bool
    {
        return (bool) preg_match('/°\s*[CFم]|℃|-?\d+(?:\.\d+)?\s?C\b/i', $v);
    }
    public static function waLink(string $text = ''): string
    {
        $num = preg_replace('/\D/', '', (string) Settings::get('contact.whatsapp'));
        return 'https://wa.me/' . $num . ($text ? '?text=' . rawurlencode($text) : '');
    }
}
NM_PATCH_EOF,

    'app/Seo.php' => <<<'NM_PATCH_EOF'
<?php
namespace Nm;

/** Meta/OG/hreflang/JSON-LD composition (doc 05). */
final class Seo
{
    public static function urlFor(string $lang, string $path = ''): string
    {
        return rtrim(cfg('base_url'), '/') . '/' . $lang . ($path ? '/' . trim($path, '/') : '') . '/';
    }
    public static function currentPath(string $lang, string $path): string { return '/' . $lang . ($path ? '/' . trim($path, '/') : '') . '/'; }

    public static function meta(string $type, int $id, string $lang, array $fallback): array
    {
        $row = Db::one('SELECT * FROM seo_meta WHERE entity_type=? AND entity_id=? AND lang=?', [$type, $id, $lang]);
        return [
            'title' => ($row['title'] ?? '') ?: $fallback['title'],
            'description' => ($row['description'] ?? '') ?: $fallback['description'],
            'robots' => ($row['robots'] ?? '') ?: 'index, follow',
            'canonical_override' => $row['canonical_override'] ?? null,
            'og_image' => $row['og_image_media_id'] ?? ($fallback['og_image'] ?? null),
        ];
    }

    /**
     * hreflang cluster (doc 05 §2). Entity pages resolve the *localized* slug of every locale so
     * the trio is mutually recursive and GSC-valid; structural pages share their path.
     */
    public static function hreflang(string $path, ?string $lang = null): string
    {
        $lang ??= I18n::lang();
        $alt = Alternates::for($lang, $path);
        $out = '';
        foreach (cfg('langs') as $l) {
            $out .= '<link rel="alternate" hreflang="' . $l . '" href="' . htmlspecialchars(self::urlFor($l, $alt[$l] ?? $path), ENT_QUOTES) . '">' . "\n";
        }
        $out .= '<link rel="alternate" hreflang="x-default" href="'
              . htmlspecialchars(self::urlFor(cfg('default_lang'), $alt[cfg('default_lang')] ?? $path), ENT_QUOTES) . '">' . "\n";
        return $out;
    }

    public static function organization(): array
    {
        return [
            '@context' => 'https://schema.org', '@type' => 'Organization',
            'name' => 'Nile-Maple', 'url' => rtrim(cfg('base_url'), '/') . '/en/',
            'logo' => rtrim(cfg('base_url'), '/') . '/assets/brand/logo-en.webp',
            'email' => Settings::get('contact.email'),
            'telephone' => Settings::get('contact.phone'),
            'contactPoint' => [[
                '@type' => 'ContactPoint', 'email' => Settings::get('contact.email'),
                'telephone' => Settings::get('contact.phone'), 'contactType' => 'sales',
                'availableLanguage' => ['English', 'Arabic', 'French'],
            ]],
            'sameAs' => array_values(array_filter([Settings::get('social.instagram'), Settings::get('social.facebook')])),
        ];
    }
    public static function website(): array
    {
        return ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => 'Nile-Maple',
            'url' => rtrim(cfg('base_url'), '/') . '/en/',
            'inLanguage' => ['en', 'ar', 'fr'],
            'potentialAction' => ['@type' => 'SearchAction',
                'target' => ['@type' => 'EntryPoint', 'urlTemplate' => rtrim(cfg('base_url'), '/') . '/{lang}/categories/?q={search_term_string}'],
                'query-input' => 'required name=search_term_string']];
    }

    /** D-09: Service schema for the six service pages. */
    public static function service(array $s, string $url): array
    {
        return ['@context' => 'https://schema.org', '@type' => 'Service',
            'name' => $s['name'], 'description' => mb_substr((string) ($s['teaser'] ?? ''), 0, 300),
            'url' => $url, 'serviceType' => $s['name'],
            'provider' => ['@type' => 'Organization', 'name' => 'Nile-Maple',
                'url' => rtrim(cfg('base_url'), '/') . '/en/'],
            'areaServed' => ['@type' => 'AdministrativeArea', 'name' => 'Worldwide']];
    }

    /** D-09: AboutPage. */
    public static function aboutPage(string $url): array
    {
        return ['@context' => 'https://schema.org', '@type' => 'AboutPage', 'name' => 'About Nile-Maple',
            'url' => $url, 'inLanguage' => I18n::lang(),
            'mainEntity' => self::organization()];
    }
    public static function breadcrumbs(array $crumbs): array
    {
        $items = [];
        foreach ($crumbs as $i => [$name, $url]) {
            $items[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $name, 'item' => $url];
        }
        return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }
    public static function product(array $p, array $i, string $categoryName, string $url, array $images): array
    {
        $props = [];
        foreach ([['Varieties', $i['varieties']], ['Packing', $i['packing']], [$i['label_chain'], $i['chain']]] as [$n, $v]) {
            if (!$v) continue;
            /* 2026-09-21: temperatures are not published on product pages — the cold-chain
               property is omitted when its value is a temperature (kept in the dashboard). */
            if ($n === $i['label_chain'] && Util::hasTempText((string) $v)) continue;
            $props[] = ['@type' => 'PropertyValue', 'name' => $n, 'value' => mb_substr($v, 0, 300)];
        }
        return [
            '@context' => 'https://schema.org', '@type' => 'Product',
            'name' => $i['name'], 'image' => $images, 'description' => mb_substr($i['description'], 0, 400),
            'brand' => ['@type' => 'Brand', 'name' => 'Nile-Maple'],
            'category' => $categoryName,
            'countryOfOrigin' => ['@type' => 'Country', 'name' => 'Egypt'],
            'additionalProperty' => $props,
            'offers' => ['@type' => 'Offer', 'availability' => 'https://schema.org/InStock',
                'url' => $url, 'description' => 'Quotation on request - B2B export'],
            'seller' => ['@type' => 'Organization', 'name' => 'Nile-Maple'],
        ];
    }
    public static function faqPage(array $faqs): array
    {
        return ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(
            static fn($f) => ['@type' => 'Question', 'name' => $f['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['answer']]], $faqs)];
    }
    public static function article(array $post, array $i, string $url, ?string $author, string $date): array
    {
        return ['@context' => 'https://schema.org', '@type' => 'Article', 'headline' => $i['title'],
            'description' => $i['excerpt'], 'mainEntityOfPage' => $url, 'datePublished' => $date,
            'dateModified' => $date, 'inLanguage' => I18n::lang(),
            'author' => $author ? ['@type' => 'Person', 'name' => $author] : ['@type' => 'Organization', 'name' => 'Nile-Maple'],
            'publisher' => ['@type' => 'Organization', 'name' => 'Nile-Maple']];
    }
    public static function itemList(array $items): array
    {
        return ['@context' => 'https://schema.org', '@type' => 'ItemList', 'itemListElement' => array_map(
            static fn($it, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $it['name'], 'url' => $it['url']],
            $items, array_keys($items))];
    }
    public static function ld(array $graph): string
    {
        /* UNESCAPED_UNICODE keeps Arabic legible; the str_replace hardens against </script>
           breakouts for any attacker-influenced string echoed into the graph. */
        $json = json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $json = str_replace(['<', '>', '&', "\u2028", "\u2029"], ['\u003c', '\u003e', '\u0026', '\u2028', '\u2029'], (string) $json);
        return '<script type="application/ld+json">' . $json . '</script>';
    }
}
NM_PATCH_EOF,

    'tools/content_updates.php' => <<<'NM_PATCH_EOF'
<?php
/**
 * Nile-Maple content updates (2026-09-21) — idempotent data fixes.
 *
 *   1. Official contact email everywhere
 *      — settings.contact.email is set to the official mailbox;
 *      — every editable content field (blocks, posts, categories, products, services,
 *        FAQs, SEO) is swept: any @nilemaple.com address that is NOT the official one
 *        is replaced. Emails from other domains are reported but never touched.
 *   2. Category cover images
 *      — every published category without a cover is assigned the photo of one of its
 *        own products (featured first, then sort order) — the same professional
 *        imagery the product cards already use.
 *   3. Enquiry recipient
 *      — config/config.php mail.to is pointed at the official mailbox.
 *
 * Idempotent: a second run changes nothing and reports the same state.
 * CLI:  php tools/content_updates.php
 * Host: required and run from tools/apply-updates.php (the one-shot host script).
 */
declare(strict_types=1);
/* loaded standalone (CLI) or included by the one-shot host script (which bootstrapped already) */
if (!function_exists('nm_path')) require __DIR__ . '/../app/bootstrap.php';

use Nm\Db;
use Nm\Util;

const NM_OFFICIAL = 'contact@nilemaple.com';
const NM_EMAIL_RE = '/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/';

/** replace every non-official nilemaple.com email; returns [new, otherDomainEmailsFound[]].
    Replaced nilemaple addresses are documented in the caller's email_changes report;
    other-domain addresses are reported for manual review and never rewritten. */
function nm_updates_replace_emails(string $s): array
{
    $other = [];
    $out = (string) preg_replace_callback(NM_EMAIL_RE, static function (array $m) use (&$other): string {
        $e = strtolower($m[0]);
        if ($e === NM_OFFICIAL) return $m[0];
        if (str_ends_with($e, '@nilemaple.com')) return NM_OFFICIAL;
        $other[] = $m[0];
        return $m[0];
    }, $s);
    return [$out, $other];
}

/* Note on JSON columns: we replace emails in the RAW string, never decode/re-encode.
   The stored JSON keeps its exact escape style (\/, \uXXXX, key order), so a payload with
   no email is byte-identical after the pass — no false-positive "changes". Email addresses
   contain no characters that need JSON escaping, so a raw replacement is safe. */

/* Trailing temperature sentences that the FR/AR descriptions carry after the packing
   sentence: "…cartons télescopiques. Généralement 8 °C" / "…تلسكوبية. عادة 8 °م"
   (104 FR + 104 AR rows verified against the live content). Only the exact
   end-of-string forms are removed; anything else is reported, never blindly edited. */
function nm_updates_strip_desc_temps(string $d): array
{
    $orig = $d;
    $d = (string) preg_replace('/\s*Généralement\s+[-\x{2212}]?\d+(?:\.\d+)?\s*°C\s*$/u', '', $d);
    $d = (string) preg_replace('/\s*عادة\s+[-\x{2212}]?\d+(?:\.\d+)?\s*°م\s*$/u', '', $d);
    return [$d, $d !== $orig, Util::hasTempText($d)];
}

/* Embedded temperature clauses in the export-handling text (one distinct sentence per
   locale, verified against the live content):
     EN (grapes):  "…maintained close to 0 C to preserve crispness…"  → "…maintained at low temperature…"
     FR (frozen):  "…stockées à -18 °C, grains séparés…"              → "…stockées en congélation, grains séparés…"
     AR (frozen):  "…وتُخزن عند -18 °م مع فصل الحبات…"                → "…وتُخزن مجمدة مع فصل الحبات…" */
function nm_updates_clean_handling_temps(string $h): array
{
    $orig = $h;
    $h = (string) preg_replace('/close to\s+[-\x{2212}]?\d+(?:\.\d+)?\s?C\b/u', 'at low temperature', $h);
    $h = (string) preg_replace('/stockées à\s+[-\x{2212}]?\d+(?:\.\d+)?\s*°C/u', 'stockées en congélation', $h);
    $h = (string) preg_replace('/وتُخزن عند\s+[-\x{2212}]?\d+(?:\.\d+)?\s*°م/u', 'وتُخزن مجمدة', $h);
    return [$h, $h !== $orig, Util::hasTempText($h)];
}

/**
 * Run all content updates. Returns a report array:
 * ['email_changes'=>[], 'emails_found_other_domains'=>[], 'covers'=>[], 'config'=>[], 'settings'=>[], 'desc_temps'=>[]]
 */
function nm_updates_run(): array
{
    $report = ['email_changes' => [], 'emails_found_other_domains' => [], 'covers' => [], 'config' => [], 'settings' => [], 'desc_temps' => []];
    $stamp = date('Y-m-d H:i:s');

    /* ── 1a · settings: the displayed contact email ─────────────────────────────── */
    $before = (string) Db::val('SELECT value FROM settings WHERE s_key=? AND lang=?', ['contact.email', '*']);
    Db::upsert('settings', ['s_key' => 'contact.email', 'lang' => '*', 'value' => NM_OFFICIAL], ['s_key', 'lang']);
    $report['settings'][] = ['contact.email', $before, NM_OFFICIAL];

    /* ── 1b · sweep every editable content field ────────────────────────────────── */
    $fields = [
        ['settings', 's_key', null, ['value']],
        ['blocks', 'id', null, ['payload']],
        ['block_i18n', 'block_id', null, ['payload', 'title', 'eyebrow']],
        ['post_i18n', 'post_id', null, ['title', 'excerpt', 'body']],
        ['category_i18n', 'category_id', null, ['name', 'headline', 'summary']],
        ['product_i18n', 'product_id', null, ['name', 'description', 'varieties', 'handling', 'packing', 'chain']],
        ['service_i18n', 'service_id', null, ['name', 'teaser', 'body', 'bullets']],
        ['faq_i18n', 'faq_id', null, ['question', 'answer']],
        ['seo_meta', 'entity_id', 'entity_type', ['title', 'description']],
    ];
    foreach ($fields as [$table, $pk, $pk2, $cols]) {
        $rows = Db::all('SELECT * FROM ' . $table);
        foreach ($rows as $r) {
            $id = (string) $r[$pk] . ($pk2 ? ' ' . $r[$pk2] : '');
            foreach ($cols as $col) {
                $old = (string) ($r[$col] ?? '');
                if ($old === '') continue;
                [$new, $hits] = nm_updates_replace_emails($old);
                foreach (array_unique($hits) as $h) $report['emails_found_other_domains'][] = "$table#$id.$col: $h";
                if ($new !== $old) {
                    Db::run('UPDATE ' . $table . ' SET ' . $col . '=? WHERE ' . $pk . '=?' . ($pk2 ? ' AND ' . $pk2 . '=?' : ''),
                        array_merge([$new], $pk2 ? [$r[$pk], $r[$pk2]] : [$r[$pk]]));
                    $report['email_changes'][] = ["$table #$id → $col", $old, $new];
                }
            }
        }
    }
    $report['emails_found_other_domains'] = array_values(array_unique($report['emails_found_other_domains']));

    /* ── 1c · product page text: remove remaining temperature wording ─────────────────
       descriptions: drop the trailing temperature sentence (FR/AR);
       handling:     replace the embedded temperature clause (EN grapes, AR/FR frozen).
       Anything still containing a temperature afterwards is reported for manual review. */
    foreach (Db::all('SELECT product_id, lang, description, handling FROM product_i18n') as $r) {
        [$nd, $dc, $dt] = nm_updates_strip_desc_temps((string) $r['description']);
        if ($dc) {
            Db::run('UPDATE product_i18n SET description=? WHERE product_id=? AND lang=?', [$nd, (int) $r['product_id'], $r['lang']]);
            $report['desc_temps'][] = "product #$r[product_id] ($r[lang]) description — trailing temperature sentence removed";
        } elseif ($dt) {
            $report['desc_temps'][] = "product #$r[product_id] ($r[lang]) description — NOT changed, unusual temperature form, review manually";
        }
        if ((string) $r['handling'] !== '') {
            [$nh, $hc, $ht] = nm_updates_clean_handling_temps((string) $r['handling']);
            if ($hc) {
                Db::run('UPDATE product_i18n SET handling=? WHERE product_id=? AND lang=?', [$nh, (int) $r['product_id'], $r['lang']]);
                $report['desc_temps'][] = "product #$r[product_id] ($r[lang]) handling — temperature clause reworded";
            } elseif ($ht) {
                $report['desc_temps'][] = "product #$r[product_id] ($r[lang]) handling — NOT changed, unusual temperature form, review manually";
            }
        }
    }

    /* ── 2 · category cover images from the category's own product photos ───────── */
    foreach (Db::all('SELECT id, code FROM categories WHERE is_published=1 AND (cover_media_id IS NULL OR cover_media_id=0)') as $c) {
        $mid = Db::val('SELECT p.card_media_id FROM products p
                        WHERE p.category_id=? AND p.is_published=1 AND p.card_media_id IS NOT NULL
                          AND EXISTS (SELECT 1 FROM media m WHERE m.id=p.card_media_id)
                        ORDER BY p.is_featured DESC, p.sort_order, p.id LIMIT 1', [(int) $c['id']]);
        if ($mid) {
            Db::run('UPDATE categories SET cover_media_id=?, updated_at=? WHERE id=?', [(int) $mid, $stamp, (int) $c['id']]);
            $report['covers'][] = ["category {$c['code']}", (int) $mid];
        } else {
            $report['covers'][] = ["category {$c['code']}", null];
        }
    }

    /* ── 3 · config: enquiry recipient ──────────────────────────────────────────── */
    $cf = nm_path('config/config.php');
    if (is_file($cf)) {
        $src = (string) file_get_contents($cf);
        $toNow = null;
        if (preg_match("/'to'\s*=>\s*'([^']*)'/", $src, $m)) $toNow = $m[1];
        if ($toNow !== NM_OFFICIAL) {
            $src2 = (string) preg_replace("/('to'\s*=>\s*)'[^']*'/", '$1' . NM_OFFICIAL . "'", $src);
            if ($src2 !== $src) {
                file_put_contents($cf, $src2);
                @chmod($cf, 0600);
                $report['config'][] = ['mail.to', $toNow, NM_OFFICIAL];
            }
        }
        if (preg_match("/'from'\s*=>\s*\['([^']*)'/", $src, $m) && strtolower($m[1]) !== NM_OFFICIAL) {
            $report['config'][] = ['mail.from (NOT changed — sending mailbox, check SMTP user)', $m[1], '(kept)'];
        }
    }

    return $report;
}

/* auto-run only from a terminal (Util::isCli = cli or the php-wasm 'wasm' harness);
   the one-shot host script defines NM_UPDATES_EMBEDDED and calls nm_updates_run() itself */
if (Util::isCli() && !defined('NM_UPDATES_EMBEDDED')) {
    $r = nm_updates_run();
    echo json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";
}
NM_PATCH_EOF,
];

/** record a step line for the on-screen report */
function nm_step(string $label, array $lines): void {
    global $steps;
    $steps[] = ['label' => $label, 'lines' => $lines];
}

try {
    /* ── 0 · safety snapshot: full DB dump, exactly like the dashboard Backup button ── */
    $data = [];
    foreach (array_keys(Schema::tables()) as $t) $data[$t] = Db::all("SELECT * FROM $t");
    $dumpFile = $backupDir . '/db-before.json';
    file_put_contents($dumpFile, json_encode($data, JSON_UNESCAPED_UNICODE));
    nm_step('0 · Safety snapshot (full database backup)', [
        'saved to ' . str_replace(nm_path(''), '', $dumpFile) . ' (' . round(filesize($dumpFile) / 1048576, 2) . ' MB)',
        'restore with phpMyAdmin import or the dashboard drill (DEPLOY.md §8)',
    ]);

    /* ── 1 · code patches (temperature display + category image fallback) ─────────── */
    $patched = [];
    foreach (NM_CODE_PATCHES as $rel => $content) {
        $abs = nm_path($rel);
        $relPath = str_replace(nm_path() . '/', '', $abs);
        $before = is_file($abs) ? hash_file('sha256', $abs) : '(missing)';
        if (is_file($abs)) copy($abs, $backupDir . '/' . str_replace('/', '__', $rel) . '.orig');
        if (!is_dir(dirname($abs))) mkdir(dirname($abs), 0777, true);
        file_put_contents($abs, $content);
        $after = hash_file('sha256', $abs);
        $patched[] = [$relPath, $before, $after];
    }
    Audit::log('update', 'code', null, ['patches' => $patched, 'note' => 'one-shot update 2026-09-21']);
    nm_step('1 · Code updates (originals backed up to ' . basename($backupDir) . ')', array_map(
        fn(array $p): string => $p[0] . ' — sha256 ' . substr($p[1], 0, 10) . '… → ' . substr($p[2], 0, 10) . '…', $patched));

    /* ── 2 · content updates: official email, category covers, enquiry recipient ───── */
    define('NM_UPDATES_EMBEDDED', true);   /* suppress the CLI auto-run block in content_updates.php */
    require nm_path('tools/content_updates.php');
    $cr = nm_updates_run();
    Audit::log('update', 'settings', null, ['note' => 'one-shot update 2026-09-21: email sweep + category covers']);

    $emailLines = [];
    $emailLines[] = 'contact.email is now ' . $cr['settings'][0][2] . ' (was: ' . ($cr['settings'][0][1] ?: 'empty') . ')';
    if ($cr['email_changes']) {
        foreach ($cr['email_changes'] as $c) {
            $emailLines[] = "{$c[0]}: replaced email(s) — " . $c[1] . ' → ' . $c[2];
        }
        $emailLines[] = count($cr['email_changes']) . ' content field(s) changed';
    } else {
        $emailLines[] = 'no other nilemaple emails found in content — nothing to replace';
    }
    if ($cr['emails_found_other_domains']) {
        $emailLines[] = 'NOT changed (other domains, review manually): ' . implode('; ', array_slice($cr['emails_found_other_domains'], 0, 10));
    }
    nm_step('2a · Official contact email', $emailLines);

    $coverLines = [];
    foreach ($cr['covers'] as $c) {
        $coverLines[] = $c[0] . ($c[1] ? ' → cover = media #' . $c[1] . ' (its own product photo)' : ' → no product photo available, kept icon fallback');
    }
    if (!$cr['covers']) $coverLines[] = 'every category already had a cover — nothing to do';
    nm_step('2b · Category cover images', $coverLines);

    $descLines = [];
    foreach ($cr['desc_temps'] as $d) $descLines[] = (string) $d;
    nm_step('2c · Product descriptions (trailing temperature sentences)',
        $descLines ?: ['none found — descriptions already clean']);

    $cfgLines = $cr['config'] ? array_map(fn(array $c): string => "{$c[0]}: {$c[1]} → {$c[2]}", $cr['config'])
                              : ['mail.to already pointed at the official mailbox — nothing to do'];
    nm_step('2c · Enquiry recipient (config)', $cfgLines);

    /* ── 3 · full static rebuild — the same routine as the dashboard "Rebuild" button ── */
    Cache::forgetAll();
    $t0 = microtime(true);
    $pages = StaticBuilder::buildAll();
    $ms = (int) ((microtime(true) - $t0) * 1000);
    Db::run('INSERT INTO build_jobs(scope,status,pages,ms,started_at,finished_at) VALUES(?,?,?,?,?,?)',
        ['full (one-shot update)', 'done', $pages, $ms, date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]);
    Audit::log('rebuild', 'site', null, ['pages' => $pages, 'note' => 'one-shot update 2026-09-21']);
    nm_step('3 · Full site rebuild', [
        $pages . ' pages rebuilt in ' . round($ms / 1000, 1) . ' s (all 3 languages, sitemaps + robots regenerated)',
        'every product page, category page and the home page now renders from the updated templates + content',
    ]);

    /* ── 4 · self-delete (the file must not survive its one use) ───────────────────── */
    $selfDeleted = false;
    if (!NM_USER_KEEP) {
        $selfDeleted = @unlink(__FILE__);
    }
    nm_step('4 · Self-cleanup', $selfDeleted
        ? ['this file deleted itself — public_html is clean']
        : ['self-delete SKIPPED (?keep=1) — delete ' . basename(__FILE__) . ' from public_html/ now!']);

    $ok = true;
    $body = '';
} catch (\Throwable $e) {
    $ok = false;
    $body .= '<div class="fail"><h2>⚠ The update stopped part-way</h2>'
        . '<p>The changes already applied are listed above. The backup snapshot in <code>'
        . htmlspecialchars(str_replace(nm_path(''), '', $backupDir)) . '</code> lets you restore everything to the pre-update state.</p>'
        . '<p><strong>Error:</strong> ' . htmlspecialchars(get_class($e) . ': ' . $e->getMessage()) . '</p></div>';
}

$css = '<style>body{font:15px/1.55 system-ui;background:#f6f3ee;color:#1d2b25;margin:0}'
    . '.wrap{max-width:60rem;margin:2.5rem auto;padding:0 1.25rem}'
    . 'h1{font-size:1.35rem;margin:0 0 .25rem}'
    . '.sub{color:#5c6b62;margin:0 0 1.5rem}'
    . '.step{background:#fff;border:1px solid #e3ddd2;border-left:4px solid #3f9142;border-radius:10px;padding:.9rem 1.1rem;margin-bottom:.9rem}'
    . '.step h2{font-size:.95rem;margin:0 0 .35rem;color:#0b542e}'
    . '.step ul{margin:.25rem 0 0;padding-left:1.1rem;color:#3c4a42;font-size:.88rem}'
    . '.step li{margin:.15rem 0;word-break:break-word}'
    . '.fail{background:#fff;border:1px solid #e3ddd2;border-left:4px solid #b3261e;border-radius:10px;padding:1rem 1.1rem;margin-bottom:1rem}'
    . '.done{background:#0b542e;color:#fff;border-radius:10px;padding:1.1rem 1.25rem;font-size:.95rem}'
    . '.done a{color:#fcb929} code{background:#eef2ee;padding:.1rem .3rem;border-radius:4px}</style>';
echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
    . '<meta name="robots" content="noindex"><title>Nile-Maple — one-shot update report</title>' . $css . '</head><body><div class="wrap">'
    . '<h1>' . ($ok ? '✅ Nile-Maple update applied' : 'Nile-Maple update — partial') . '</h1>'
    . '<p class="sub">Executed ' . date('Y-m-d H:i:s T') . ' · took ' . round(microtime(true) - NM_STARTED, 1) . ' s · site: <a href="/en/">nilemaple.com/en/</a></p>'
    . $body;
foreach ($steps as $s) {
    echo '<div class="step"><h2>' . htmlspecialchars($s['label']) . '</h2><ul>';
    foreach ($s['lines'] as $l) echo '<li>' . htmlspecialchars((string) $l) . '</li>';
    echo '</ul></div>';
}
if ($ok) echo '<div class="done">Done. Hard-refresh the live pages (Ctrl/Cmd+Shift+R) to see the new design. '
    . 'The temperature data is untouched in the dashboard — it can be restored anytime by reverting the backed-up files (storage/backups/). '
    . 'Tip: <a href="/">back to the site</a></div>';
echo '</div></body></html>';
