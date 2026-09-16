// The template x locale matrix under test. Slugs are the real seeded per-language slugs
// (doc 01: trilingual slugs, not transliterated URLs) — pulled from storage/db.sqlite once and
// hard-coded here because the browser suites must not depend on a live DB connection.
export const LANGS = ['en', 'ar', 'fr'];

const CAT = { en: 'fresh-fruits', ar: 'فواكه-طازجة', fr: 'fruits-frais' };
const PRODUCT = { en: 'orange', ar: 'برتقال', fr: 'orange' };
const SERVICE = { en: 'sourcing-selection', ar: 'التوريد-والانتقاء', fr: 'sourcing-selection' };
const POST = {
  en: 'how-to-read-a-citrus-export-programme',
  ar: 'كيف-تقرأ-برنامج-تصدير-الحمضيات',
  fr: 'comment-lire-un-programme-d-export-d-agrumes',
};

/** name -> (lang) => path. One entry per distinct template (doc 03), not per URL. */
export const TEMPLATES = {
  home: (l) => `/${l}/`,
  about: (l) => `/${l}/about/`,
  services: (l) => `/${l}/services/`,
  'service-detail': (l) => `/${l}/services/${encodeURIComponent(SERVICE[l])}/`,
  'categories-hub': (l) => `/${l}/categories/`,
  'category-listing': (l) => `/${l}/categories/${encodeURIComponent(CAT[l])}/`,
  'product-detail': (l) => `/${l}/products/${encodeURIComponent(PRODUCT[l])}/`,
  'blog-index': (l) => `/${l}/blog/`,
  'blog-post': (l) => `/${l}/blog/${encodeURIComponent(POST[l])}/`,
  contact: (l) => `/${l}/contact/`,
  faq: (l) => `/${l}/faq/`,
  'quality-handling': (l) => `/${l}/quality-handling/`,
  'packaging-logistics': (l) => `/${l}/packaging-logistics/`,
  'seasonal-availability': (l) => `/${l}/seasonal-availability/`,
  'export-documentation': (l) => `/${l}/export-documentation/`,
  privacy: (l) => `/${l}/privacy/`,
  terms: (l) => `/${l}/terms/`,
};

/** [name, lang, path] for every (template, language) pair — the full matrix. */
export function allRoutes() {
  const out = [];
  for (const [name, fn] of Object.entries(TEMPLATES)) {
    for (const l of LANGS) out.push([name, l, fn(l)]);
  }
  return out;
}

/** A fast subset: every template once, in the language most likely to break (Arabic, for RTL
    layout risk) plus home in all 3 langs — used by suites where the full 48-page matrix is not
    the point (e.g. overflow/touch-targets already share layout across products/posts/services
    of the same template). */
export function fastRoutes() {
  const out = [['home', 'en', TEMPLATES.home('en')], ['home', 'fr', TEMPLATES.home('fr')]];
  for (const [name, fn] of Object.entries(TEMPLATES)) out.push([name, 'ar', fn('ar')]);
  return out;
}
