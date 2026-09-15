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
            if ($v) $props[] = ['@type' => 'PropertyValue', 'name' => $n, 'value' => mb_substr($v, 0, 300)];
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
