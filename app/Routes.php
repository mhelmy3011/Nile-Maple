<?php
namespace Nm;

/**
 * Public route table (doc 05 §1). Returns [template, params, pathForCanonical].
 *
 * Paginated listings are real pages (`/en/categories/fresh-vegetables/page-2/`), so the
 * noscript "load more" link and the JS fragment both resolve to identical content (doc 04 §5:
 * progressive enhancement — every feature must work with JS off).
 */
final class Routes
{
    public static function match(string $path): ?array
    {
        $path = trim($path, '/');
        $segs = $path === '' ? [] : explode('/', $path);

        /* trailing page-N marker belongs to the query model, not to the canonical path */
        $page = 1;
        if ($segs && preg_match('/^page-(\d+)$/', (string) end($segs), $pm)) {
            $page = max(1, (int) $pm[1]);
            if ($page === 1) return null;                       // /page-1/ must 301 to the base URL
            array_pop($segs);
        }

        $m = static function (int $n) use ($segs) { return count($segs) === $n; };
        $g = static function (int $i) use ($segs) { return $segs[$i] ?? ''; };
        $withPage = static function (array $params) use ($page) { return $page > 1 ? $params + ['page' => $page] : $params; };

        $r = null;
        if ($m(0)) $r = ['pages/home', [], ''];
        elseif ($m(1) && $g(0) === 'about-us') $r = ['pages/about', [], 'about'];
        elseif ($m(1) && $g(0) === 'about') $r = ['pages/about', [], 'about'];
        elseif ($m(1) && $g(0) === 'services') $r = ['pages/services', [], 'services'];
        elseif ($m(2) && $g(0) === 'services') $r = ['pages/service', ['slug' => $g(1)], "services/{$g(1)}"];
        elseif ($m(1) && $g(0) === 'categories') $r = ['pages/categories', [], 'categories'];
        elseif ($m(2) && $g(0) === 'categories') $r = ['pages/category', ['slug' => $g(1)], "categories/{$g(1)}"];
        elseif ($m(2) && $g(0) === 'products') $r = ['pages/product', ['slug' => $g(1)], "products/{$g(1)}"];
        elseif ($m(1) && $g(0) === 'blog') $r = ['pages/blog', [], 'blog'];
        elseif ($m(2) && $g(0) === 'blog') $r = ['pages/post', ['slug' => $g(1)], "blog/{$g(1)}"];
        elseif ($m(1) && $g(0) === 'contact') $r = ['pages/contact', [], 'contact'];
        elseif ($m(1) && $g(0) === 'faq') $r = ['pages/faq', [], 'faq'];
        else {
            foreach (['quality-handling', 'packaging-logistics', 'seasonal-availability', 'export-documentation', 'privacy', 'terms'] as $s) {
                if ($m(1) && $g(0) === $s) { $r = ['pages/static-block', ['zone' => $s], $s]; break; }
            }
        }
        if (!$r) return null;
        /* pagination only exists on the two collection templates */
        if ($page > 1 && !in_array($r[0], ['pages/category', 'pages/blog'], true)) return null;
        $r[1] = $withPage($r[1]);
        return $r;
    }
}
