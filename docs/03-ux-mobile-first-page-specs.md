# 03 — UX Architecture: Mobile-First Design System & Page-by-Page Specs

Design order is **360 px → 390 → 430 → 768 → 1024 → 1440**. Every component is drawn mobile-first in
Figma; desktop is an expansion, never the source. All specs below list the **mobile** behaviour as the
contract; "Desktop delta" notes only what changes ≥ 1024 px.

---

## 1. Mobile chrome (global, every page)

### 1.1 Utility topbar (40 px, pine-900, white text, 0.75rem)
`✉ contact@nilemaple.com` · `☎ +20 1515919135` · right side: IG/FB glyphs.
Client mandate: contact visible in header **and** footer of every page, clickable.
Mobile: single row, horizontally scrollable if needed (no wrap), `tel:`/`mailto:` links; hides on scroll-down.

### 1.2 Header (56 px sticky, `surface` + blur 8 px + bottom border on scroll)
Left: logo mark SVG 32 px + wordmark (hidden < 380 px, mark only). Right: **language pill**
(`🌐 EN ▾` → sheet with English / العربية / Français, current marked) + **menu button** (48 px).
Primary CTA "Request a Quote" lives in the command bar on mobile (thumb zone), in header on desktop.
Scroll behaviour: hide on scroll-down > 12 px, reveal on scroll-up (never traps content).

### 1.3 Sheet menu (bottom sheet, e1, radius lg top, max-height 85 dvh, drag handle)
Rows 56 px: Home · About · Services · Categories · Blog · Contact · FAQ (48 px tap, chevron).
Active row: green-50 bg + green-700 text + 3 px inline-start bar. Bottom of sheet: contact block
(call / whatsapp / email buttons) + socials + language switch repeat. Focus-trapped, ESC/back-drop close,
scroll locked on body (`overflow:hidden` + `dvh` compensation).

### 1.4 Mobile Command Bar (the signature mobile pattern)
Fixed bottom, 64 px + `env(safe-area-inset-bottom)`, `surface` + top border + e3, appears after 40 vh scroll
(slides 240 ms), hidden on Contact page (redundant) and in dashboard.
Three equal zones: **[ ☎ Call ]** (tel:) · **[ WhatsApp ]** (wa.me/201515919135, green-500 fill, pine-950 label) ·
**[ Get Quote ]** (amber-400 fill, pine-950 label → /{lang}/contact/ or opens quote sheet with product prefilled).
This puts the three money actions in the thumb zone at all times — the single biggest mobile-conversion
upgrade over the reference site (which only has a floating WhatsApp bubble).

### 1.5 Language switch contract
Visible on every page (header pill). Switching keeps the **equivalent page** (prefix swap, D-02), sets
`nm_lang` cookie, preserves scroll position, updates `dir`/`lang`, reloads fonts if AR↔LTR.
Labels exactly: `English` · `العربية` · `Français` (native names, client mandate).

### 1.6 Footer (pine-900, white/amber text)
Mobile stack: logo-mono-light → one-line description → **contact card** (email/phone/whatsapp rows, tappable,
48 px) → nav columns as accordions (Company / Products / Resources) → socials row → legal row
(© 2026 Nile-Maple · Privacy · Terms) → language switch repeat. Desktop: 4 columns inline.
Newsletter: **none** (B2B export — no list to fake); replaced by "Request seasonal availability list" CTA.

### 1.7 Overlays & misc
Cookie consent: bottom sheet above command bar, 2 buttons (Accept / Manage), no dark pattern, stored 180 d.
WhatsApp FAB: **desktop only** (mobile has command bar) — SVG link, no SDK. 404: friendly, search-less,
links to categories + contact. Skip-link first in DOM. `scroll-padding-top: 72px` for anchors under sticky header.

---

## 2. Home (`/{lang}/`) — mobile section order

| # | Section | Content source | Mobile behaviour |
|---|---|---|---|
| 1 | **Hero (scroll-snap, 2 slides, 78 dvh max)** | Slide A: division collage + H1 "Egyptian food & agricultural products, export-ready" + tagline "Reliable products. Practical solutions. Long-term partnerships." + CTA pair [Explore products][Request a quote]; Slide B: cold-chain/quality photo + "One partner across fresh, frozen & shelf-stable programmes" + CTA [Our services] | Native horizontal snap; dots + swipe; text block bottom-anchored in thumb reach; stats row below hero (not inside) to protect LCP |
| 2 | **Proof stats band** (green-50) | 4 divisions · 159 catalogue lines · 3 languages · 48 h quote target | 2×2 grid mobile, counters animate once, tabular nums |
| 3 | **Divisions** (section-head + 4 cards) | 4 categories + division photos + one-line handling focus from profile table | Vertical snap carousel of 4 full-width cards (image left 40 %, text right) OR 2×2 compact; chosen: **vertical stack of 4 horizontal cards** (no carousel = zero JS, all content visible for SEO) |
| 4 | **How we work** (6 steps) | profile 6-step table | Vertical timeline, numbered dots, connecting line; step 1 open by default; "see service" link per step |
| 5 | **Why buyers work with Nile-Maple** | 4 two-column pairs → mobile 4 stacked check-rows with icons | Checklist split: image (division photo) sticky on desktop, stacked on mobile |
| 6 | **Featured products** (category tabs) | `is_featured` products per category | Tab pills (horizontal scroll, amber active) + 2-up product card grid, 4 per tab + "View all N →" per tab (mirrors reference's tabbed showcase, upgraded to real cards) |
| 7 | **Quality & cold-chain strip** (pine-900 dark) | 6 quality pillars condensed to 3 mobile-visible + link | Icon rows; dark band gives palette depth |
| 8 | **Seasonal teaser** | link block to Seasonal Availability page | Amber-tinted band, calendar icon, CTA |
| 9 | **Latest insights** (blog) | 3 latest posts | Vertical cards 16:9 thumb + meta |
| 10 | **FAQ teaser** | 4 FAQs | Accordion (first open) + "All questions" link |
| 11 | **CTA band** | "Tell us your product, quantity, destination and timing — we reply with a clear commercial offer." | Full-width green-500 band, pine-950 H2 + white-on-green-700 button + WhatsApp secondary |

Desktop delta: hero 92 vh with slide thumbnails right; divisions 4-col; steps 6-col horizontal; tabs grid 4-up; sections keep order.

## 3. About (`/{lang}/about/`)

Mobile order: breadcrumb → H1 + lead (profile "A practical partner…") → **story** (2 paragraphs) →
**purpose** (4 pillar cards 2×2) → **history** (4-stage timeline from development table) →
**our work** (4-division table → accordion per division with handling focus) → **quality** (6 pillars as icon grid + 4 commitments checklist) →
**export capability** (3 accordions: Packaging & presentation / Logistics coordination / Export documentation, bullets verbatim) →
**progress & why buyers** (4 pairs) → **leadership** (2 person cards: name, role, one-line mandate — no fake photos; use monogram avatars in pine circle) →
**vision + direction** (quote-styled band + 4 bullets) → CTA band.
Desktop: 2-col story+image, tables render as real tables ≥ 1024 (they are comparative data).

## 4. Services (`/{lang}/services/` + `/{lang}/services/{slug}/`)

Index: section-head + 6 `ui-card-service` (icon, name, teaser, arrow) stacked mobile / 3×2 desktop.
Detail: breadcrumb → H1 + teaser → body H2 sections → **bullets as check-rows** → related service cards (2) →
CTA band with product-context link ("See products we handle this way" → relevant category).
The 6-step process diagram renders on `export-programme`; cold-chain spec table on `quality-coldchain`
(reuses product chain values as examples: "strawberries 0–2 °C", "bananas 13–14 °C" — real data, strong E-E-O-R).

## 5. Categories hub (`/{lang}/categories/`)

Mobile: 4 large division cards (photo top 16:10, name, count badge "31 products", one-line summary, arrow) stacked;
below: **"All products A–Z"** index chips (jump links) + total count. Desktop: 2×2 large cards + A–Z grid.

## 6. Category listing (`/{lang}/categories/{slug}/`)

Mobile contract:
1. Compact hero (photo scrim, H1, summary, count).
2. **Sticky filter bar** under header: search input (filters loaded set client-side, 0 requests) + sort select (A–Z / catalogue order) + result count live-region.
3. **2-up product grid**, 12 px gap; card: 1:1 image, chip, name (2-line clamp), key spec (tabular), arrow.
4. Pagination: **"Load more" button** (30 per page server-rendered; button fetches HTML fragment + pushState) — infinite scroll rejected (hurts SEO + back button).
5. End: category SEO text block (H2 + 2 paragraphs) + CTA band.
Desktop: 4-up grid, left sidebar with sibling-category links + spec legend.
RTL: filter bar order mirrored, chevrons flipped, sort select arrow flipped.

## 7. Product detail (`/{lang}/products/{slug}/`) — the mobile money page

Mobile order & behaviour:
1. Breadcrumb (category → product) 1 line, scrollable.
2. **Gallery**: 1:1 swipeable (scroll-snap), dots, `fetchpriority=high` on first, thumbs row below (horizontal snap).
3. H1 + category chip + sku/catalogue no. as muted data line.
4. **Sticky mini-bar** (appears on scroll): product name truncated + [Quote] button (prefills enquiry).
5. **Spec rows** (`ui-spec-rows`): 4 rows with icons — Varieties/Forms (leaf/box icon) · Export/Processing handling (route) · Packing (box) · Cold-chain (thermometer, **tabular value + unit badge**, e.g. `0–2 °C` in amber chip). Rows are definition-list semantics (`<dl>`).
6. Description paragraph(s).
7. **Enquiry shortcut card**: "Request a quote for {product}" → contact with `product_interest` prefilled via `?product=` param (also WhatsApp deep link with prefilled text `Hello Nile-Maple, I'd like a quote for {name} ({lang})`).
8. Related products (same category) horizontal snap carousel, 2.4 cards visible.
9. SEO text: none fake — instead "Handling summary" reuses spec values in prose (auto-composed sentence template, translatable).
Desktop: 2-col sticky gallery left / specs right; related 4-up.

## 8. Blog (`/{lang}/blog/`, `/{lang}/blog/{slug}/`)

Index: featured post card (full-width) + list cards (16:9 thumb left 40 % on mobile? no — mobile: thumb top 16:9, text below; ≥ 640: horizontal) + category-less tags (use service link) + Load more.
Detail: progress bar 2 px top · H1 · meta (date locale-formatted, reading time, author) · cover 16:9 · body typography (1.0625/1.75, H2/H3, pull-quote style, lists) · share row (**Web Share API** primary, fallback WhatsApp/mail links) · author box (real user from dashboard) · related 2 · CTA band.
TOC: only if ≥ 4 H2 (desktop right rail; mobile: collapsible chips row under meta).

## 9. Contact (`/{lang}/contact/`)

Mobile order: H1 + lead → **contact action cards** (4 tappable rows: Call, WhatsApp, Email (tap = mailto, long-press/copy icon = clipboard + toast), Socials (IG/FB external)) →
**form** (fields exactly per client mandate): Full name* · Email* (validated) · Phone/WhatsApp (optional, `inputmode=tel`) · Company & country (recommended, 2 fields) · Subject* · Product of interest (select grouped by category, 159 options + "General") · Message* · Consent checkbox* (links Privacy) →
Submit button (spinner + disabled while sending) → inline success card ("We received your enquiry… reply within 48 h") or error summary box (focus moved).
Below: **response-time promise** + language note ("We reply in English, Arabic or French") + FAQ teaser (3).
No map embed (perf); instead static styled address card if client supplies address (DQ-07) + "Cairo, Egypt" line.
Desktop: 2-col (info left sticky, form right).

## 10. FAQ (`/{lang}/faq/`)
Grouped accordions (6 groups), one open per group, deep-linkable (`#faq-12`), `FAQPage` JSON-LD emitted,
search filter input client-side, "Still have a question?" CTA → contact prefilled subject.

## 11. Supporting pages
`quality-handling/` · `packaging-logistics/` · `seasonal-availability/` · `export-documentation/` —
built from profile sections; **seasonal-availability** is a data-driven table (month × category, derived in
dashboard as a `block` payload; mobile = horizontally scrollable table with sticky first column + legend;
this page is our SEO magnet for "egyptian orange season", "mango season egypt" queries).
`privacy/` `terms/`: simple prose pages from dashboard blocks.

## 12. RTL specification (Arabic)

- `<html lang="ar" dir="rtl">`; **all** CSS uses logical properties (`margin-inline-start`, `inset-inline-end`, `padding-inline`, `border-inline-start`, `text-align: start`) → zero duplicate stylesheets.
- Mirrored: chevrons/arrows/sort indicators/swipe direction/progress bar/timeline side/command-bar icon order.
- Not mirrored: phone numbers (+20 … stays LTR inside `<bdi>`), temperatures, Latin product names (`<span lang="en" dir="ltr">`), logos.
- Typography: Tajawal, line-height 1.9 body, sizes +6 % vs EN at same token, **never** letter-spacing/uppercase/italic.
- Numbers: Western digits (D-11). Dates: `IntlDateFormatter('ar-EG', …, calendar=GREGORY)` → "14 سبتمبر 2026".
- QA: every template screenshot-compared LTR vs RTL in CI (doc 07).

## 13. Key mobile wireframes (contract sketches)

```
┌ HOME 360px ─────────────┐  ┌ PRODUCT DETAIL 360px ──┐  ┌ CATEGORY 360px ────────┐
│[✉ mail][☎ +20 15…]  IG FB│  │‹ Fresh Fruits ‹ Orange │  │[search……][sort ▾] 31    │← sticky
│[🍁mark] NILE-MAPLE  [EN▾][≡]│ │┌────────────────────┐ │  │┌──────────┐┌──────────┐ │
│┌──────────────────────┐ │  ││   1:1 photo (snap)   │ │  ││ 1:1 img  ││ 1:1 img  │ │
││  hero photo + scrim  │ │  │└────────────────────┘ │  ││ ORANGE   ││ MANGO    │ │
││ EYEBROW              │ │  │ ●○○  thumbs →          │  ││ 3-8 °C → ││ 10-13 °C→│ │
││ H1 (clamp 6.4vw)     │ │  │ORANGE            [chip]│  │└──────────┘└──────────┘ │
││ lead 1rem/1.6        │ │  │Varieties  Valencia,…  │  │┌──────────┐┌──────────┐ │
││[Explore products   ]│ │  │Handling   Harvested…  │  ││ 1:1 img  ││ 1:1 img  │ │
││[Request a quote    ]│ │  │Packing    Ventilated… │  ││ LEMON    ││ LIMES…   │ │
│└──────────────────────┘ │  │Cold-chain [ 3-8 °C ]  │  │└──────────└──────────┘ │
│ 4 divisions · 159 lines│  │[Request quote: Orange]│  │      [ Load more ]       │
├────────────────────────┤  │Related →→→            │  ├──────────────────────────┤
│ …sections…             │  ├────────────────────────┤  │[☎ Call][WhatsApp][Quote]│← command bar
│[☎ Call][WhatsApp][Quote]│  │[☎ Call][WhatsApp][Quote]│  │└──────────────────────────┘
└────────────────────────┘  └────────────────────────┘  └──────────────────────────┘
```

## 14. Micro-copy voice (trilingual)

EN: plain international-trade English, active voice, numbers first ("31 fruit lines, one commercial partner").
AR: formal modern standard Arabic, no slang, trade terms transliterated only where standard (كونتينر، ريفر).
FR: professional export French, vouvoiement, EU-buyer vocabulary (calibre, cahier des charges, conteneur reefer).
Buttons never exceed 2 words mobile ("Get quote" / "اطلب عرض" / "Demander un devis" → FR allowed 3).
All strings live in lang files / DB — designers write into the i18n sheet, never into templates.
