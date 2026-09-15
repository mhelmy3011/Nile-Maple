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
                    (SELECT COUNT(*) FROM products p WHERE p.category_id=c.id AND p.is_published=1) AS cnt
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
        $p = json_decode($b['payload'] ?? '{}', true) ?: [];
        return $p ?: (json_decode($b['base_payload'] ?? '{}', true) ?: []);
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
