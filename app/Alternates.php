<?php
namespace Nm;

/**
 * Localized-slug resolver (client requirement: the language switcher must keep the buyer on
 * the *equivalent* page and hreflang must point at real URLs, so an EN product URL resolves to
 * its AR/FR slug and back. Structural pages (about, contact, faq, ...) share one path per locale
 * while entity pages (products, categories, services, posts) keep a per-language slug in *_i18n.
 */
final class Alternates
{
    /** entity type → [table, i18n table, fk, url prefix] */
    public const ENTITIES = [
        'product'  => ['products',  'product_i18n',  'product_id',  'products'],
        'category' => ['categories', 'category_i18n', 'category_id', 'categories'],
        'service'  => ['services',  'service_i18n',  'service_id',  'services'],
        'post'     => ['posts',     'post_i18n',     'post_id',     'blog'],
    ];

    /** url path for one entity in one language (null when it has no translation/not published) */
    public static function path(string $type, int $id, string $lang, bool $publishedOnly = true): ?string
    {
        [$t, $t18n, $fk] = self::ENTITIES[$type] ?? throw new \InvalidArgumentException("bad entity $type");
        $sql = "SELECT i.slug FROM $t t JOIN $t18n i ON i.$fk = t.id AND i.lang = ? WHERE t.id = ?";
        if ($publishedOnly) {
            $sql .= $type === 'post' ? " AND t.status = 'published'" : ' AND t.is_published = 1';
        }
        $slug = Db::val($sql, [$lang, $id]);
        return $slug ? self::ENTITIES[$type][3] . '/' . $slug : null;
    }

    /** the same path in every locale: [lang => path]; falls back to the identical path */
    public static function for(string $lang, string $path): array
    {
        $out = [];
        $hit = self::entityOf($lang, $path);
        foreach (cfg('langs') as $l) {
            $out[$l] = $hit ? (self::path($hit[0], $hit[1], $l) ?? $path) : $path;
        }
        return $out;
    }

    /** [type, id] for a public path such as "products/valencia-orange", or null */
    public static function entityOf(string $lang, string $path): ?array
    {
        $path = trim($path, '/');
        if ($path === '' || substr_count($path, '/') !== 1) return null;
        [$prefix, $slug] = explode('/', $path, 2);
        foreach (self::ENTITIES as $type => [$t, $t18n, $fk, $pfx]) {
            if ($pfx !== $prefix) continue;
            $sql = "SELECT t.id FROM $t t JOIN $t18n i ON i.$fk = t.id AND i.lang = ? WHERE i.slug = ?";
            $sql .= $type === 'post' ? " AND t.status = 'published'" : ' AND t.is_published = 1';
            $id = Db::val($sql, [$lang, $slug]);
            return $id === null ? null : [$type, (int) $id];
        }
        return null;
    }
}
