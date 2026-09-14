# 05 — SEO Architecture, International SEO & Content Strategy

## 1. URL & canonical architecture

| Route | Pattern (per language) | Canonical |
|---|---|---|
| Home | `/en/` `/ar/` `/fr/` | self |
| About | `/{lang}/about/` | self |
| Services | `/{lang}/services/` · `/{lang}/services/{slug}/` | self |
| Categories hub | `/{lang}/categories/` | self |
| Category listing | `/{lang}/categories/{slug}/` (+ `?page=n` self-canonical) | self |
| Product | `/{lang}/products/{slug}/` | self |
| Blog | `/{lang}/blog/` · `/{lang}/blog/{slug}/` | self |
| Contact / FAQ | `/{lang}/contact/` · `/{lang}/faq/` | self |
| Supporting | `/{lang}/quality-handling/` `/packaging-logistics/` `/seasonal-availability/` `/export-documentation/` `/privacy/` `/terms/` | self |
| Root | `/` → 302 by preference | **noindex,nofollow + no canonical** (redirector only) |

Rules: lowercase, hyphens, trailing slash everywhere, no session/id params, slugs **per language**
(`orange` / `برتقال`→ transliterated `portaqal`? **No** — AR slugs use readable Arabic-ISO words URL-encoded,
e.g. `/ar/products/برتقال/` is valid & user-friendly in AR SERPs; FR `orange`). hreflang matrix on every page:

```html
<link rel="alternate" hreflang="en" href="https://nilemaple.com/en/products/orange/">
<link rel="alternate" hreflang="ar" href="https://nilemaple.com/ar/products/%D8%A8%D8%B1%D8%AA%D9%82%D8%A7%D9%84/">
<link rel="alternate" hreflang="fr" href="https://nilemaple.com/fr/products/orange/">
<link rel="alternate" hreflang="x-default" href="https://nilemaple.com/en/products/orange/">
```
Sitemaps: `/sitemap.xml` (index) → `sitemap-en.xml`, `sitemap-ar.xml`, `sitemap-fr.xml` with
`<image:image>` entries for product/category covers + `<xhtml:link>` alternates; regenerated on rebuild.
`robots.txt`: allow all, disallow `/manage/ /api/ /cache/`, sitemap pointer, no crawl of `?page=` duplicates? (allowed, canonical handles).

## 2. On-page template rules (all templates)

Single `<h1>` = page entity name (+ locale); eyebrow is `<p>`; section titles `<h2>`; card titles `<h3>`;
no skipped levels; `<title>` = `{Entity} | Nile-Maple` pattern ≤ 60 chars from `seo_meta` with fallbacks;
meta description ≤ 160 chars trilingual; OG/Twitter cards (og:image = composed 1200×630, og:locale
`en_US`/`ar_EG`/`fr_FR` + og:locale:alternate pair); breadcrumbs visible + schema; semantic landmarks;
alt text = descriptive per language (never "image1"); internal links use descriptive anchors in locale language;
`<html lang dir>`; text never in images; spec values as real text (indexable: "0–2 °C", "Valencia, Navel…").

## 3. Structured data (JSON-LD, emitted per template)

- **Every page:** `Organization` (name, url, logo, contactPoint[email,telephone], sameAs[IG,FB]) + `WebSite` + `BreadcrumbList`.
- **Category:** `CollectionPage` + `ItemList` of products (position, url, name).
- **Product (B2B-correct):**
```json
{"@context":"https://schema.org","@type":"Product",
 "name":"Orange","image":["…/orange-800.avif"],"description":"…",
 "brand":{"@type":"Brand","name":"Nile-Maple"},
 "category":"Fresh Fruits","countryOfOrigin":{"@type":"Country","name":"Egypt"},
 "additionalProperty":[
   {"@type":"PropertyValue","name":"Varieties","value":"Valencia, Navel, Baladi, Salustiana and Sukkari"},
   {"@type":"PropertyValue","name":"Packing","value":"Ventilated cartons or telescopic boxes"},
   {"@type":"PropertyValue","name":"Cold-chain guide","value":"Typically 3-8 °C"}],
 "offers":{"@type":"Offer","availability":"https://schema.org/InStock","priceSpecification":null,
           "description":"Quotation on request — B2B export"}}
```
- **FAQ page + teasers:** `FAQPage` (only where Q&A visible). **Blog:** `Article` + `Person` author + `datePublished/Modified`.
- **Contact:** `ContactPage` + `Organization` contactPoint. **Seasonal availability:** `Dataset`-style `Table`? use `ItemList`+`Article`; keep simple `Article`.
Validation gate: Rich Results Test + Schema.org validator in CI for one sample per template per language.

## 4. Keyword & intent map (seed; expanded in Phase 5 with GSC data)

| Level | EN | FR | AR |
|---|---|---|---|
| Brand | nile maple egypt, nile-maple food industrial | nile maple égypte | نايل ميبل, شركة نيل مابل |
| Category | egyptian fresh fruit exporter · egyptian vegetables exporter · IQF frozen vegetables supplier egypt · canned & processed food manufacturer egypt | exportateur de fruits frais égyptien · fournisseur légumes surgelés IQF Égypte · conserves alimentaires Égypte | شركة تصدير فاكهة مصرية · مصدر خضروات طازجة مصر · خضروات مجمدة مصر · مصانع معلبات مصرية |
| Family (top money pages) | egyptian orange exporter · mango exporter egypt · egyptian potato exporter · onion exporter egypt · frozen okra supplier · frozen molokhia supplier · canned fava beans supplier · egyptian dates exporter barhi · strawberry exporter egypt | exportateur d'oranges égyptiennes · exportateur de mangues égypte · fournisseur d'okra surgelé · exportateur de dattes égyptiennes | مصدر برتقال مصري · تصدير مانجو · مصدر بطاطس · تصدير بصل مصري · فراولة تصدير · تمر برحي تصدير |
| Long-tail / informational | egyptian citrus season months · mango varieties list egypt · IQF grades explained · cold chain temperature for strawberries · export documents egypt food shipment · container loading pallets standard · private label canned food egypt | saison des agrumes égypte · températures chaîne du froid fraises · documents exportation alimentaire égypte | موسم البرتقال المصري · درجات حفظ الفراولة · مستندات تصدير الأغذية مصر · تحميل الكونتينر بالات |

Intent policy: category pages = commercial; product pages = commercial-narrow; seasonal-availability +
export-documentation + quality-handling = informational magnets that link down to categories; blog = long-tail.

## 5. Content hubs & internal linking model

```
HOME ──► CATEGORIES hub ──► CATEGORY (31–55 products) ──► PRODUCT ──► CONTACT (quote prefilled)
  │            │                       ▲                      │
  │            └── seasonal-availability (month × category table, links both ways)
  ├── SERVICES (6) ──► service detail ──► relevant CATEGORY
  ├── BLOG ──► post ──► category/product mentions (contextual, 2–4 links) + related posts
  └── FAQ ── links to services/packaging/contact
```
Every product page links up (category), across (4 related), down (quote CTA). Every blog post links to
≥ 1 category and ≥ 1 product with commercial anchor. No orphan pages (CI check).

## 6. Editorial programme (launch + cadence)

Launch with the 8 seeded briefs (doc 01 §5.4), published in 3 languages (AR/FR may follow within 2 weeks —
hreflang tolerates staggered rollout if `seo_meta` rows exist). Cadence: **2 posts/month/language**,
alternating: seasonal window posts (calendar-driven: citrus Nov–Mar, mango May–Sep, dates Aug–Oct,
strawberry Nov–Apr), buyer guides, handling/cold-chain explainers, market notes (EU/Gulf/Russia-ish demand
trends — factual, no fabricated stats). Each post: 700–1100 words, 1 real image, 1 table or list, FAQ block
at end (dual-uses FAQPage schema), CTA band.

## 7. International SEO specifics

- AR is **written**, not MT-post-edited-lite: professional translator + trade-term glossary maintained in
  dashboard glossary block (e.g. reefer = حاوية مبردة, calibre = عيار/حجم, IQF = تجميد سريع فردي).
- FR reviewed for EU trade register vocabulary.
- hreflang only when all 3 exist; until then emit available set (GSC-clean).
- `og:locale` + `Content-Language` header per language dir via `.htaccess` env or PHP header on dynamic; static via `<meta http-equiv>`? → use `<html lang>` + hreflang (sufficient, avoid meta http-equiv).
- Geo signals: Organization `areaServed` [EG, SA, AE, EU member states generic "Europe"], address when provided (DQ-07), Google Business Profile for the Cairo office (phase 6).
- No auto-redirect by GeoIP (bad for crawlers); only cookie/Accept-Language on `/` with 302 (D-02).

## 8. Analytics without an analytics tax (perf-aligned)

- Page views: LiteSpeed access log → GoAccess report (offline, zero client cost).
- Events (click-to-call, WhatsApp, quote CTA, form success, load-more): 0.4 KB inline beacon POSTing to
  `/api/event` (stored in DB table `events`) — still 0 third parties.
- Search: GSC (domain property) + hreflang report; CrUX for field CWV; rankings tracked against §4 map monthly.
- Conversion definition: enquiry submitted (and mailed) — dashboard KPI card.

## 9. Launch SEO checklist (gates, doc 07)

GSC submitted for 3 sitemaps · hreflang no-errors report · Rich results valid per template · no redirect
chains (max 1 hop) · all 200 (no 404/5xx in crawl of 700 URLs) · canonical self-consistent · OG debugger pass
per language · title/description uniqueness 100 % · image alts 100 % · CWV field data collecting ·
`robots.txt` + sitemap reachable · 404 page returns 404 status · form success event fires ·
breadcrumb schema matches visible trail · AR pages render RTL in GSC mobile-friendly test.
