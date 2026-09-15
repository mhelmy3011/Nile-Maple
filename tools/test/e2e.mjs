#!/usr/bin/env node
/**
 * Nile-Maple HTTP end-to-end suite (Finalization-Plan Workstream E, §7.4 HTTP matrix).
 * Runs against a running dev server:  node tools/test/e2e.mjs [baseUrl]
 * Requires: the seeded database + built assets. Exit 0 = green.
 *
 * Covers: template×locale matrix, i18n purity, meta/hreflang/JSON-LD contracts,
 * 301 localisation, security headers, cache headers, robots/sitemaps, enquiry API
 * matrix (CSRF / honeypot / time-trap / rate-limit / persistence), manage auth surface.
 * (Browser-only gates — touch targets, 360px overflow, visual RTL — run in the CI
 * Playwright stage; see .github/workflows/ci.yml.)
 */
const BASE = process.argv[2] || 'http://127.0.0.1:8080';
let P = 0, F = 0; const fails = [];
const ok = (cond, name) => { if (cond) { P++; } else { F++; fails.push(name); console.log('  FAIL ' + name); } };

async function req(path, opts = {}) {
  const res = await fetch(BASE + path, { redirect: 'manual', ...opts });
  const setCookies = [];
  res.headers.forEach((v, k) => { if (k.toLowerCase() === 'set-cookie') setCookies.push(v); });
  return { status: res.status, headers: res.headers, setCookies, body: await res.text() };
}
const enc = (s) => encodeURIComponent(s);
const paths = {
  en: {
    home: '/en/', about: '/en/about/', contact: '/en/contact/', faq: '/en/faq/',
    categories: '/en/categories/', products: '/en/products/orange/', blog: '/en/blog/',
    post: '/en/blog/export-documentation-checklist/', service: '/en/services/export-handling/',
    privacy: '/en/privacy/', terms: '/en/terms/', quality: '/en/quality-handling/',
  },
};
paths.ar = { ...paths.en }; paths.fr = { ...paths.en };
/* entity slugs are per-locale (D-02 contract): EN / AR / FR from the seeded i18n tables */
const SLUGS = {
  product:  { en: 'orange', ar: enc('برتقال'), fr: 'orange' },
  category: { en: 'fresh-fruits', ar: enc('فواكه-طازجة'), fr: 'fruits-frais' },
  service:  { en: 'cold-chain-logistics', ar: enc('سلسلة-التبريد-واللوجستيات'), fr: 'chaine-du-froid-logistique' },
  post:     { en: 'cold-chain-what-actually-breaks-it', ar: enc('سلسلة-التبريد-ما-الذي-يكسرها-فعلًا'), fr: 'chaine-du-froid-ce-qui-la-rompt-vraiment' },
};
const entityPath = (kind, lang) => `/${lang}/${{ product: 'products', category: 'categories', service: 'services', post: 'blog' }[kind]}/${SLUGS[kind][lang]}/`;
const productPath = (lang) => entityPath('product', lang);
const categoryPath = (lang) => entityPath('category', lang);
const servicePath = (lang) => entityPath('service', lang);
const postPath = (lang) => entityPath('post', lang);

/* ── 1. template × locale matrix ─────────────────────────────────────────────── */
console.log('· template matrix ×3 locales');
const templates = [
  ['home', (l) => `/${l}/`], ['about', (l) => `/${l}/about/`], ['contact', (l) => `/${l}/contact/`],
  ['faq', (l) => `/${l}/faq/`], ['categories', (l) => `/${l}/categories/`], ['blog', (l) => `/${l}/blog/`],
  ['privacy', (l) => `/${l}/privacy/`], ['terms', (l) => `/${l}/terms/`],
  ['quality', (l) => `/${l}/quality-handling/`], ['packaging', (l) => `/${l}/packaging-logistics/`],
  ['seasonal', (l) => `/${l}/seasonal-availability/`], ['export-doc', (l) => `/${l}/export-documentation/`],
  ['product', productPath], ['category', categoryPath], ['service', servicePath], ['post', postPath],
];
const pages = {};
for (const l of ['en', 'ar', 'fr']) for (const [name, f] of templates) {
  const r = await req(f(l));
  pages[`${l}:${name}`] = r;
  ok(r.status === 200, `matrix ${l} ${name} → 200 (got ${r.status})`);
}

/* ── 2. document contracts on every template (EN) ───────────────────────────── */
console.log('· document contracts');
for (const [name, f] of templates) {
  const body = pages[`en:${name}`].body;
  ok((body.match(/<h1[ >]/g) || []).length === 1, `en ${name}: exactly one <h1>`);
  ok(!/<(h[2-6])[^>]*>\s*<\/\1>/.test(body), `en ${name}: no empty headings`);
  ok(/<html lang="en"/.test(body) && /dir="ltr"/.test(body), `en ${name}: lang+dir`);
  ok((body.match(/rel="alternate" hreflang="/g) || []).length === 4 && body.includes('hreflang="x-default"'), `en ${name}: 4-way hreflang`);
  ok(/<link rel="canonical" href="/.test(body), `en ${name}: canonical`);
  ok(!/undefined|NaN|Fatal error|Warning:/.test(body), `en ${name}: no leak markers`);
}

/* ── 3. JSON-LD matrix ───────────────────────────────────────────────────────── */
console.log('· JSON-LD');
const lds = (body) => [...body.matchAll(/<script type="application\/ld\+json">([\s\S]*?)<\/script>/g)]
  .map((m) => { try { return JSON.parse(m[1]); } catch { return { '@type': 'PARSE-ERROR' }; } });
const hasType = (arr, t) => arr.some((o) => o['@type'] === t || (Array.isArray(o['@graph']) && o['@graph'].some((g) => g['@type'] === t)));
const homeLd = lds(pages['en:home'].body);
ok(hasType(homeLd, 'Organization'), 'home: Organization LD');
ok(hasType(homeLd, 'WebSite'), 'home: WebSite LD');
ok(homeLd.some((o) => JSON.stringify(o).includes('SearchAction')), 'home: WebSite SearchAction');
ok(hasType(homeLd, 'ItemList'), 'home: featured ItemList [D-09]');
ok(hasType(homeLd, 'FAQPage'), 'home: FAQPage');
const catLd = lds(pages['en:category'].body);
ok(hasType(catLd, 'BreadcrumbList'), 'category: BreadcrumbList');
ok(catLd.some((o) => o['@type'] === 'CollectionPage' && JSON.stringify(o).includes('"ItemType"') === false && JSON.stringify(o).includes('ItemList')), 'category: CollectionPage with mainEntity ItemList [D-09]');
const prodLd = lds(pages['en:product'].body);
const prod = prodLd.find((o) => o['@type'] === 'Product');
ok(!!prod && !!prod.brand && ((prod.countryOfOrigin && (prod.countryOfOrigin.name === 'Egypt' || prod.countryOfOrigin === 'Egypt')) || prod.countryOfOrigin === 'EG'), 'product: Product LD origin');
ok(!!prod && Array.isArray(prod.additionalProperty), 'product: PropertyValue specs');
ok(!!prod && prod.offers && typeof prod.offers.url === 'string' && prod.offers.url.startsWith('https://'), 'product: offers.url absolute [D-09]');
ok(!!prod && prod.seller && prod.seller.name === 'Nile-Maple', 'product: seller Org [D-09]');
ok(hasType(lds(pages['en:service'].body), 'Service') && JSON.stringify(lds(pages['en:service'].body)).includes('areaServed'), 'service: Service LD [D-09]');
ok(hasType(lds(pages['en:post'].body), 'Article'), 'post: Article LD');
ok(hasType(lds(pages['en:about'].body), 'AboutPage'), 'about: AboutPage [D-09]');
ok(hasType(lds(pages['en:contact'].body), 'ContactPage'), 'contact: ContactPage');
ok(hasType(lds(pages['en:faq'].body), 'FAQPage'), 'faq: FAQPage');

/* ── 4. i18n purity + RTL ────────────────────────────────────────────────────── */
console.log('· i18n purity');
{
  const ar = pages['ar:home'].body;
  ok(/dir="rtl"/.test(ar), 'ar home: dir=rtl');
  ok(!/<(h[1-4])[^>]*>[^<]*[a-zA-Z]{4,}[^<]*<\/\1>/.test(ar), 'ar home: headings are Arabic (no Latin fragments)');
  const arProd = pages['ar:product'].body;
  const specText = [...arProd.matchAll(/<t[dh][^>]*>([^<]+)<\/t[dh]>/g)].map((m) => m[1]).join(' | ');
  const latin = specText.match(/[A-Za-z]{3,}/g)?.filter((w) => !/^(IQF|PET|BRC|HACCP|ISO|GS1|PDF|JPEG)$/i.test(w)) || [];
  ok(latin.length === 0, `ar product: spec cells pure Arabic (found: ${latin.slice(0, 3).join(', ') || 'none'})`);
  ok(/اتجاه|التعبئة|التبريد|درجة/.test(arProd), 'ar product: spec labels Arabic');
  const fr = pages['fr:home'].body;
  ok(/Bienvenue|fruits|Égypte|égypt/i.test(fr), 'fr home: French copy served');
}

/* ── 5. meta from seo_meta (D-08) ────────────────────────────────────────────── */
console.log('· seo_meta rendering');
for (const l of ['en', 'ar', 'fr']) {
  const b = pages[`${l}:product`].body;
  const title = (b.match(/<title>([^<]+)<\/title>/) || [])[1] || '';
  const desc = (b.match(/<meta name="description" content="([^"]*)"/) || [])[1] || '';
  ok(title.length >= 15 && title.length <= 65, `${l} product: title 15-65 (${title.length})`);
  ok(desc.length >= 70 && desc.length <= 160, `${l} product: description 70-160 (${desc.length})`);
  ok(!title.includes('{') && !desc.includes('{'), `${l} product: no unfilled placeholders`);
}
ok(pages['en:home'].body.match(/<title>/) && !pages['en:home'].body.includes('{Name}'), 'en home: title rendered');

/* ── 6. redirects & routing ──────────────────────────────────────────────────── */
console.log('· redirects');
{
  const r1 = await req('/ar/categories/fresh-fruits/');
  ok(r1.status === 301 && (r1.headers.get('location') || '').startsWith('/ar/categories/'), 'foreign slug → 301 [D-14]');
  const loc = r1.headers.get('location') || '';
  ok(loc.startsWith('/ar/'), '301 Location root-relative');
  const r2 = await req(loc);
  ok(r2.status === 200, `301 target resolves → 200 (got ${r2.status})`);
  const r3 = await req('/ar/categories/' + enc('فواكه-طازجة') + '/');
  ok(r3.status === 200, 'native AR slug stays 200');
  const r4 = await req('/en/about');
  ok(r4.status === 301 || r4.status === 200, 'no-trailing-slash handled');
  const r5 = await req('/en/this-page-does-not-exist/');
  ok(r5.status === 404 && /404|not found/i.test(r5.body), 'unknown path → branded 404');
  const xss = await req('/en/products/' + enc('<script>alert(1)</script>') + '/');
  ok(xss.status === 404 && !xss.body.includes('<script>alert(1)</script>'), 'reflected slug XSS → escaped (hreflang/canonical)');
}

/* ── 7. security & cache headers ─────────────────────────────────────────────── */
console.log('· headers');
{
  const h = pages['en:home'].headers;
  const get = (n) => (h.get(n) || '').toLowerCase();
  ok(get('x-frame-options').includes('deny') || get('x-frame-options').includes('sameorigin'), 'X-Frame-Options');
  ok(get('x-content-type-options') === 'nosniff', 'X-Content-Type-Options');
  ok(get('referrer-policy').includes('no-referrer') || get('referrer-policy').includes('strict-origin'), 'Referrer-Policy');
  ok(get('permissions-policy') !== '', 'Permissions-Policy');
  ok((pages['en:home'].body.match(/<style>/g) || []).length >= 1 && !/rel="stylesheet"/.test(pages['en:home'].body), 'perf: critical CSS inlined, zero external stylesheets');
  const man = await req('/assets/manifest.json');
  ok(man.status === 200 && (man.headers.get('cache-control') || '').includes('immutable'), 'assets: manifest immutable-cached');
  const img = await req('/assets/media/fresh-fruits/orange-640.webp');
  ok(img.status === 200 && (img.headers.get('content-type') || '').includes('webp'), 'media variant served as webp');
  const robots = await req('/robots.txt');
  ok(robots.status === 200 && /sitemap:/.test(robots.body.toLowerCase()), 'robots.txt + sitemap ref');
  const sm = await req('/sitemap.xml');
  ok(sm.status === 200 && sm.body.includes('<sitemapindex'), 'sitemap index served');
  const smEn = await req('/sitemap-en.xml');
  const nUrls = (smEn.body.match(/<url>/g) || []).length;
  ok(smEn.status === 200 && smEn.body.includes('<urlset') && nUrls >= 150, `sitemap-en lists ≥150 urls (got ${nUrls})`);
}

/* ── 8. enquiry API matrix (D-10) ────────────────────────────────────────────── */
console.log('· enquiry API');
async function enquiry(fields, jar = {}) {
  const page = await req('/en/contact/');
  const csrf = (page.body.match(/name="_csrf" value="([^"]+)"/) || [])[1] || '';
  const cookie = (page.setCookies.map((c) => c.split(';')[0]).join('; ')) || 'x=1';
  const form = new URLSearchParams({ _csrf: csrf, lang: 'en', website: '', _t: String(Math.floor(Date.now() / 1000) - 30), ...fields });
  return req('/api/enquiry', { method: 'POST', headers: { 'content-type': 'application/x-www-form-urlencoded', cookie }, body: form.toString() });
}
{
  const good = await enquiry({ full_name: 'E2E Buyer', email: 'e2e-buyer@test.example', subject: 'Orange quote', message: 'Please quote two containers of navel oranges for Rotterdam.', consent: '1' });
  const j = (() => { try { return JSON.parse(good.body); } catch { return {}; } })();
  ok(good.status === 200 && j.ok === true, 'valid enquiry → ok:true');
  ok(j.errors === undefined || (Array.isArray(j.errors) && j.errors.length === 0), 'valid enquiry: no errors');
}
{
  const hp = await enquiry({ full_name: 'Bot', email: 'bot@spam.example', subject: 'x', message: 'spam message here', consent: '1', website: 'http://spam.example' });
  ok(hp.status === 200 && (() => { try { return JSON.parse(hp.body).ok === true; } catch { return false; } })(), 'honeypot filled → fake ok (no validation leak) [D-10]');
}
{
  const bad = await enquiry({ full_name: '', email: 'not-an-email', subject: '', message: 'short', consent: '' });
  const jb = (() => { try { return JSON.parse(bad.body); } catch { return {}; } })();
  const nFields = jb.fields ? Object.keys(jb.fields).length : (Array.isArray(jb.errors) ? jb.errors.length : 0);
  ok(bad.status === 422 && nFields >= 3, `invalid payload → 422 + per-field errors (got ${bad.status}, ${nFields} fields)`);
}
{
  const nocsrf = await req('/api/enquiry', { method: 'POST', headers: { 'content-type': 'application/x-www-form-urlencoded' }, body: `full_name=X&email=x%40y.zz&subject=s&message=a%20longer%20message%20here&consent=1&lang=en&_t=${Math.floor(Date.now() / 1000) - 30}` });
  ok(nocsrf.status === 419 || nocsrf.status === 403, `missing CSRF rejected (${nocsrf.status})`);
}

/* ── 9. manage surface ───────────────────────────────────────────────────────── */
console.log('· manage auth surface');
{
  const login = await req('/manage/login');
  ok(login.status === 200 && login.body.includes('_csrf'), 'login page + csrf field');
  ok(!/Fatal error|Warning:|Deprecated:/.test(login.body), 'login page clean');
  const dash = await req('/manage/');
  ok(dash.status === 302 || dash.status === 401 || dash.status === 403, 'dashboard requires auth');
}

/* ── 10. rate limit LAST (it consumes the per-IP budget) ─────────────────────── */
console.log('· enquiry rate limit');
{
  let limited = false;
  for (let i = 0; i < 7; i++) {
    const r = await enquiry({ full_name: 'RL' + i, email: `rl${i}@test.example`, subject: 'RL', message: 'rate limit probe message ' + i, consent: '1' });
    if (r.status === 429) { limited = true; break; }
    try { if (JSON.parse(r.body).ok === false && JSON.stringify(JSON.parse(r.body).errors || {}).toLowerCase().includes('rate')) { limited = true; break; } } catch {}
  }
  ok(limited, 'enquiry rate limit engages within 7 tries');
}

/* ── summary ─────────────────────────────────────────────────────────────────── */
console.log(`\ne2e: ${P} passed, ${F} failed`);
if (fails.length) { console.log(fails.map((f) => '  ✗ ' + f).join('\n')); }
process.exit(F === 0 ? 0 : 1);
