<?php
namespace Nm;

/** Assembles + renders any public page for a locale (used by builder AND dynamic fallback). */
final class PublicController
{
    public static function page(string $lang, string $path, array $query = []): array
    {
        I18n::boot($lang);
        $route = Routes::match($path);
        if (!$route) return self::notFound($lang, $path);
        [$tpl, $params, $canon] = $route;

        $data = ['lang' => $lang, 'path' => $canon, 'query' => $query, 'crumbs' => [], 'ld' => [], 'preload' => null, 'meta' => null];
        $home = Seo::urlFor($lang, '');
        $data['crumbs'][] = [I18n::t('nav.home'), $home];

        switch ($tpl) {
            case 'pages/home':
                $data += self::home($lang);
                $fb = ['title' => I18n::t('seo.home.title'), 'description' => I18n::t('seo.home.desc')];
                break;
            case 'pages/about':
                $data['blocks'] = [];
                foreach (['about:intro', 'about:purpose', 'about:history', 'about:work', 'about:quality', 'about:export', 'about:progress', 'about:leadership', 'about:vision'] as $z) {
                    $data['blocks'][$z] = Content::blocks($z, $lang);
                }
                $data['crumbs'][] = [I18n::t('nav.about'), Seo::urlFor($lang, 'about')];
                $fb = ['title' => I18n::t('seo.about.title'), 'description' => I18n::t('seo.about.desc')];
                break;
            case 'pages/services':
                $data['services'] = Content::services($lang);
                $data['crumbs'][] = [I18n::t('nav.services'), Seo::urlFor($lang, 'services')];
                $fb = ['title' => I18n::t('seo.services.title'), 'description' => I18n::t('seo.services.desc')];
                break;
            case 'pages/service':
                $s = Content::serviceBySlug($lang, $params['slug']);
                if (!$s) return self::notFound($lang, $path);
                $data['service'] = $s;
                $data['related'] = array_values(array_filter(Content::services($lang), fn($x) => $x['id'] != $s['id'])) ?: [];
                $data['crumbs'][] = [I18n::t('nav.services'), Seo::urlFor($lang, 'services')];
                $data['crumbs'][] = [$s['name'], Seo::urlFor($lang, "services/{$s['slug']}")];
                $data['ld'][] = Seo::breadcrumbs($data['crumbs']);
                $fb = ['title' => ($s['meta_title'] ?? '') ?: $s['name'] . ' | Nile-Maple', 'description' => ($s['meta_description'] ?? '') ?: $s['teaser']];
                $data['meta'] = Seo::meta('service', (int) $s['id'], $lang, $fb);
                break;
            case 'pages/categories':
                $data['categories'] = Content::categories($lang);
                $data['products'] = Content::products($lang, null, 1, '', 'az');
                $data['crumbs'][] = [I18n::t('nav.categories'), Seo::urlFor($lang, 'categories')];
                $fb = ['title' => I18n::t('seo.categories.title'), 'description' => I18n::t('seo.categories.desc')];
                break;
            case 'pages/category':
                $c = Content::categoryBySlug($lang, $params['slug']);
                if (!$c) return self::notFound($lang, $path);
                $page = max(1, (int) ($params['page'] ?? $query['page'] ?? 1));
                $q = trim((string) ($query['q'] ?? ''));
                $data['cat'] = $c;
                $data['pageNo'] = $page;
                $data['q'] = $q;
                $data['total'] = Content::productCount($lang, (int) $c['id'], $q);
                $data['products'] = Content::products($lang, (int) $c['id'], $page, $q);
                $data['pages'] = (int) ceil($data['total'] / Content::PER_PAGE);
                $data['crumbs'][] = [I18n::t('nav.categories'), Seo::urlFor($lang, 'categories')];
                $data['crumbs'][] = [$c['name'], Seo::urlFor($lang, "categories/{$c['slug']}")];
                $fb = ['title' => ($c['meta_title'] ?? '') ?: $c['name'] . ' | Nile-Maple', 'description' => ($c['meta_description'] ?? '') ?: $c['summary']];
                $data['meta'] = Seo::meta('category', (int) $c['id'], $lang, $fb);
                $items = [];
                foreach ($data['products'] as $i => $p) $items[] = ['name' => $p['name'], 'url' => Seo::urlFor($lang, "products/{$p['slug']}")];
                $data['ld'][] = Seo::itemList($items);
                break;
            case 'pages/product':
                $p = Content::productBySlug($lang, $params['slug']);
                if (!$p) return self::notFound($lang, $path);
                $cat = Content::categoryById((int) $p['category_id'], $lang);
                $data['product'] = $p;
                $data['cat'] = $cat;
                $data['related'] = Content::related($lang, (int) $p['category_id'], (int) $p['id']);
                $data['crumbs'][] = [I18n::t('nav.categories'), Seo::urlFor($lang, 'categories')];
                $data['crumbs'][] = [$cat['name'] ?? '', Seo::urlFor($lang, 'categories/' . ($cat['slug'] ?? ''))];
                $data['crumbs'][] = [$p['pname'], Seo::urlFor($lang, "products/{$p['slug']}")];
                $fb = ['title' => ($p['meta_title'] ?? '') ?: $p['pname'] . ' | Nile-Maple', 'description' => ($p['meta_description'] ?? '') ?: Markdown::excerpt($p['description'], 155)];
                $data['meta'] = Seo::meta('product', (int) $p['id'], $lang, $fb);
                $imgs = array_values(array_filter([Media::url((int) $p['card_media_id'], 800, 'jpg')]));
                $data['ld'][] = Seo::product($p, $p, $cat['name'] ?? '', Seo::urlFor($lang, "products/{$p['slug']}"), $imgs);
                $data['preload'] = $p['card_media_id'] ? Media::url((int) $p['card_media_id'], 800, 'webp') : null;
                break;
            case 'pages/blog':
                $page = max(1, (int) ($params['page'] ?? $query['page'] ?? 1));
                $data['posts'] = Content::posts($lang, $page);
                $data['total'] = Content::postCount($lang);
                $data['pageNo'] = $page;
                $data['pages'] = (int) ceil($data['total'] / 9);
                $data['crumbs'][] = [I18n::t('nav.blog'), Seo::urlFor($lang, 'blog')];
                $fb = ['title' => I18n::t('seo.blog.title'), 'description' => I18n::t('seo.blog.desc')];
                break;
            case 'pages/post':
                $post = Content::postBySlug($lang, $params['slug']);
                if (!$post) return self::notFound($lang, $path);
                $data['post'] = $post;
                $data['related'] = array_values(array_slice(array_filter(Content::posts($lang, 1, 6), fn($x) => $x['id'] != $post['id']), 0, 2));
                $data['crumbs'][] = [I18n::t('nav.blog'), Seo::urlFor($lang, 'blog')];
                $data['crumbs'][] = [$post['title'], Seo::urlFor($lang, "blog/{$post['slug']}")];
                $fb = ['title' => ($post['meta_title'] ?? '') ?: $post['title'] . ' | Nile-Maple', 'description' => ($post['meta_description'] ?? '') ?: $post['excerpt']];
                $data['meta'] = Seo::meta('post', (int) $post['id'], $lang, $fb);
                $data['ld'][] = Seo::article($post, $post, Seo::urlFor($lang, "blog/{$post['slug']}"), $post['author'], $post['published_at'] ?? date('Y-m-d'));
                break;
            case 'pages/contact':
                $data['categories'] = Content::categories($lang);
                $data['productsByCat'] = [];
                foreach ($data['categories'] as $c) {
                    $data['productsByCat'][$c['name']] = Db::all('SELECT i.name FROM products p JOIN product_i18n i ON i.product_id=p.id AND i.lang=? WHERE p.category_id=? AND p.is_published=1 ORDER BY p.sort_order', [$lang, $c['id']]);
                }
                $data['faqs'] = Content::faqs($lang, null, 3);
                $data['crumbs'][] = [I18n::t('nav.contact'), Seo::urlFor($lang, 'contact')];
                $data['ld'][] = ['@context' => 'https://schema.org', '@type' => 'ContactPage', 'name' => I18n::t('contact.title'), 'url' => Seo::urlFor($lang, 'contact')];
                $fb = ['title' => I18n::t('seo.contact.title'), 'description' => I18n::t('seo.contact.desc')];
                break;
            case 'pages/faq':
                $data['faqs'] = Content::faqs($lang);
                $data['crumbs'][] = [I18n::t('nav.faq'), Seo::urlFor($lang, 'faq')];
                $data['ld'][] = Seo::faqPage($data['faqs']);
                $fb = ['title' => I18n::t('seo.faq.title'), 'description' => I18n::t('seo.faq.desc')];
                break;
            case 'pages/static-block':
                $z = $params['zone'];
                $data['zone'] = $z;
                $data['blocks'] = Content::blocks('page:' . $z, $lang);
                $data['crumbs'][] = [I18n::t('zone.' . $z), Seo::urlFor($lang, $z)];
                $fb = ['title' => I18n::t('seo.' . $z . '.title'), 'description' => I18n::t('seo.' . $z . '.desc')];
                break;
            default:
                $fb = ['title' => 'Nile-Maple', 'description' => ''];
        }

        $data['meta'] ??= Seo::meta('page', crc32($canon) % 100000, $lang, $fb);
        if (!in_array(Seo::breadcrumbs($data['crumbs']), $data['ld'], true) && count($data['crumbs']) > 1) {
            array_unshift($data['ld'], Seo::breadcrumbs($data['crumbs']));
        }
        array_unshift($data['ld'], Seo::organization(), Seo::website());
        $html = View::page($tpl, $data);
        return ['status' => 200, 'html' => $html];
    }

    private static function home(string $lang): array
    {
        return [
            'hero'      => Content::blocks('home:hero', $lang),
            'stats'     => Content::blocks('home:stats', $lang),
            'categories'=> Content::categories($lang),
            'process'   => Content::blocks('home:process', $lang),
            'why'       => Content::blocks('home:why', $lang),
            'featured'  => array_map(static fn($c) => ['cat' => $c, 'items' => Content::featured($lang, 0) ?: []], []),
            'featuredByCat' => array_map(static fn($c) => ['cat' => $c, 'items' => Db::all(
                'SELECT p.*, i.name, i.slug FROM products p JOIN product_i18n i ON i.product_id=p.id AND i.lang=?
                 WHERE p.category_id=? AND p.is_published=1 AND p.is_featured=1 ORDER BY p.sort_order LIMIT 4', [$lang, $c['id']])], Content::categories($lang)),
            'quality'   => Content::blocks('home:quality', $lang),
            'posts'     => Content::posts($lang, 1, 3),
            'faqs'      => Content::faqs($lang, null, 4),
        ];
    }

    public static function notFound(string $lang, string $path): array
    {
        I18n::boot($lang);
        $data = ['lang' => $lang, 'path' => $path, 'crumbs' => [], 'ld' => [Seo::organization()], 'preload' => null,
            'meta' => ['title' => I18n::t('seo.404.title'), 'description' => I18n::t('seo.404.desc'), 'robots' => 'noindex, follow', 'canonical_override' => null, 'og_image' => null],
            'categories' => Content::categories($lang)];
        return ['status' => 404, 'html' => View::page('pages/404', $data)];
    }
}
