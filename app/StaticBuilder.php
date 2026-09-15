<?php
namespace Nm;

/**
 * Static-render engine (doc 04 §2): renders every public page per language to
 * public_html/{lang}/{path}/index.html with atomic tmp+rename writes.
 *
 * Two rules keep the output honest on shared hosting:
 *  1. specs are computed **per locale** — AR/FR entities have their own slugs, so a spec list
 *     built from all languages at once would emit thousands of 404 bodies as real files;
 *  2. a page is only written when it renders 200. A 404 removes the stale file instead
 *     (product unpublished in the dashboard ⇒ its URL must not linger in the CDN).
 */
final class StaticBuilder
{
    /** locale-independent pages (first entry is the home page) */
    public const STRUCTURAL = ['', 'about', 'services', 'categories', 'blog', 'contact', 'faq',
        'quality-handling', 'packaging-logistics', 'seasonal-availability', 'export-documentation',
        'privacy', 'terms'];

    public static function pageSpecs(string $lang): array
    {
        $specs = array_map(static fn(string $p) => [$p, 1], self::STRUCTURAL);

        foreach (Db::all('SELECT i.slug, (SELECT COUNT(*) FROM products p WHERE p.category_id=c.id AND p.is_published=1) cnt
                          FROM categories c JOIN category_i18n i ON i.category_id=c.id AND i.lang=?
                          WHERE c.is_published=1', [$lang]) as $r) {
            $pages = max(1, (int) ceil((int) $r['cnt'] / Content::PER_PAGE));
            for ($p = 1; $p <= $pages; $p++) $specs[] = ['categories/' . $r['slug'], $p];
        }
        foreach (Db::all("SELECT i.slug FROM products p JOIN product_i18n i ON i.product_id=p.id AND i.lang=?
                          WHERE p.is_published=1", [$lang]) as $r) {
            $specs[] = ['products/' . $r['slug'], 1];
        }
        foreach (Db::all("SELECT i.slug FROM services s JOIN service_i18n i ON i.service_id=s.id AND i.lang=?
                          WHERE s.is_published=1", [$lang]) as $r) {
            $specs[] = ['services/' . $r['slug'], 1];
        }
        foreach (Db::all("SELECT i.slug FROM posts p JOIN post_i18n i ON i.post_id=p.id AND i.lang=?
                          WHERE p.status='published'", [$lang]) as $r) {
            $specs[] = ['blog/' . $r['slug'], 1];
        }
        $postPages = (int) ceil(Content::postCount($lang) / 9);
        for ($p = 2; $p <= $postPages; $p++) $specs[] = ['blog', $p];

        $seen = []; $out = [];
        foreach ($specs as [$sp, $pg]) { if (!isset($seen["$sp|$pg"])) { $seen["$sp|$pg"] = 1; $out[] = [$sp, $pg]; } }
        return $out;
    }

    /** @return array{status:int,file:?string,skipped:bool} */
    public static function writePage(string $lang, string $path, int $page = 1): array
    {
        $diskPath = $page > 1 ? ($path === '' ? '' : trim($path, '/')) . "/page-$page" : trim($path, '/');
        if ($page > 1 && $path === '') $diskPath = "page-$page";        // paginated blog index
        $r = PublicController::page($lang, $path . ($page > 1 ? "/page-$page" : ''), []);
        $dir = rtrim((string) cfg('paths.public'), '/') . "/$lang/" . ($diskPath !== '' ? $diskPath . '/' : '');
        $file = $dir . 'index.html';

        if ((int) $r['status'] !== 200) {
            if (is_file($file)) @unlink($file);                          // stale URL → retire it
            return ['status' => (int) $r['status'], 'file' => null, 'skipped' => true];
        }
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $tmp = $file . '.' . getmypid() . '.tmp';
        file_put_contents($tmp, $r['html']);
        @chmod($tmp, 0664);
        rename($tmp, $file);
        return ['status' => 200, 'file' => $file, 'skipped' => false];
    }

    public static function buildAll(?callable $progress = null): int
    {
        $n = 0;
        foreach (cfg('langs') as $lang) {
            foreach (self::pageSpecs($lang) as [$path, $page]) {
                $res = self::writePage($lang, $path, $page);
                if (!$res['skipped']) $n++;
                if ($progress) $progress($n, "$lang/$path" . ($page > 1 ? "/page-$page" : ''));
            }
        }
        self::sitemaps();
        self::robots();
        return $n;
    }

    /** scoped rebuild after a dashboard save (doc 06 §7) — only the locale that owns the slug */
    public static function buildScope(array $scopes): int
    {
        $n = 0;
        foreach ($scopes as $s) {
            [$kind, $arg, $extra] = array_pad((array) $s, 3, null);
            if ($kind === 'full') return self::buildAll();
            if ($kind === 'sitemaps') { self::sitemaps(); self::robots(); $n++; continue; }
            if ($kind === 'entity' && $arg !== null) {
                $id = (int) $extra;
                foreach (cfg('langs') as $l) {
                    $p = Alternates::path((string) $arg, $id, $l, false);
                    if ($p) { $r = self::writePage($l, $p); if (!$r['skipped']) $n++; }
                }
                continue;
            }
            if ($kind === 'listing' && $arg !== null) {
                /* every page of one category listing, in every locale (a product save can add/drop page 2) */
                $cid = (int) $arg;
                $cnt = (int) Db::val('SELECT COUNT(*) FROM products WHERE category_id=? AND is_published=1', [$cid]);
                $pages = max(1, (int) ceil($cnt / Content::PER_PAGE));
                foreach (cfg('langs') as $l) {
                    $slug = Db::val('SELECT slug FROM category_i18n WHERE category_id=? AND lang=?', [$cid, $l]);
                    if (!$slug) continue;
                    for ($p = 1; $p <= $pages; $p++) {
                        $r = self::writePage($l, 'categories/' . $slug, $p);
                        if (!$r['skipped']) $n++;
                    }
                }
                continue;
            }
            if ($kind === 'page') {
                foreach (cfg('langs') as $l) {
                    /* entity slugs are per-locale: skip a locale that does not own this slug */
                    if ($arg !== '' && preg_match('#^(products|categories|services|blog)/#', (string) $arg)
                        && Alternates::entityOf($l, (string) $arg) === null) continue;
                    $r = self::writePage($l, (string) $arg);
                    if (!$r['skipped']) $n++;
                }
            }
        }
        return $n;
    }

    public static function sitemaps(): void
    {
        $base = rtrim((string) cfg('base_url'), '/');
        $index = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
               . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach (cfg('langs') as $l) {
            $urls = [];
            foreach (self::pageSpecs($l) as [$path, $page]) {
                if ($page > 1) continue;                                  // paginated twins are not indexed
                $loc = htmlspecialchars(Seo::urlFor($l, $path), ENT_XML1);
                $alt = Alternates::for($l, $path);
                $alts = '';
                foreach (cfg('langs') as $al) {
                    $alts .= '<xhtml:link rel="alternate" hreflang="' . $al . '" href="'
                           . htmlspecialchars(Seo::urlFor($al, $alt[$al] ?? $path), ENT_XML1) . '"/>';
                }
                $urls[] = "<url><loc>$loc</loc>$alts<changefreq>weekly</changefreq></url>";
            }
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
                . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'
                . implode('', $urls) . '</urlset>';
            file_put_contents(rtrim((string) cfg('paths.public'), '/') . "/sitemap-$l.xml", $xml);
            $index .= "<sitemap><loc>$base/sitemap-$l.xml</loc><lastmod>" . date('c') . '</lastmod></sitemap>';
        }
        $index .= '</sitemapindex>';
        file_put_contents(rtrim((string) cfg('paths.public'), '/') . '/sitemap.xml', $index);
    }

    public static function robots(): void
    {
        $base = rtrim((string) cfg('base_url'), '/');
        file_put_contents(rtrim((string) cfg('paths.public'), '/') . '/robots.txt',
            "User-agent: *\nAllow: /\nDisallow: /manage/\nDisallow: /api/\n\nSitemap: $base/sitemap.xml\n");
    }
}
