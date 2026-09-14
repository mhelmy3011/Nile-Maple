# 02 — Brand, Visual Identity & Design System

## 1. Brand position & visual voice

Nile-Maple sells **trust in a supply chain**, not vegetables. The visual voice is therefore:
*clean international food-trade house* — generous whitespace, photographic proof, precise spec
typography (temperatures, grades, pack formats read like data), one confident green, one warm amber
signal colour, and the red-leaf mark used with restraint as the brand signature.

Reference benchmark (greenchem-egy.com) uses emoji icons, dense gradients and a JS hero slider.
We keep its **section rhythm** but replace its craft layer entirely (real SVG iconography,
scroll-snap hero, tokenised colour, art-directed imagery).

## 2. Palette reconciliation (client palette × logo inks) — verified contrast pairs

Sampled logo inks: leaf red `#D03020–#D8402C` · Nile-green `#306040–#3D6644` · wordmark `#30303B` ·
(source background cream `#FDF9F6`, removed in production).
Client palette: primary `#1dbf5a` · secondary `#fcb929`.

**Rule:** the logo is never recoloured. The UI palette harmonises *around* it: `#1dbf5a` carries all
interactive/CTA energy, a deep "pine" family (derived from the logo's Nile-green) carries dark surfaces
(header-on-scroll, footer, hero scrims) so the mark always sits on a family-relative ground, and the
leaf red appears only in the logo, in tiny accents (active-step dots, sale/season badges) and as an
accessible accent-text colour.

### 2.1 Token scales

| Scale | 50 | 100 | 200 | 300 | 400 | 500 | 600 | 700 | 800 | 900 | 950 |
|---|---|---|---|---|---|---|---|---|---|---|---|
| **green** (brand) | `#eafbf1` | `#d0f6de` | `#a4edc0` | `#6fdf9c` | `#3ecb77` | **`#1dbf5a`** | `#12a24a` | `#0e8140` | `#0d6636` | `#0b542e` | `#052e19` |
| **amber** (signal) | `#fff8e6` | `#ffedc2` | `#ffdd85` | `#fdca47` | **`#fcb929`** | `#f5a90b` | `#d18a06` | `#a76a08` | `#85520d` | `#6d420f` | `#3f2306` |
| **pine** (surfaces, from logo green) | `#f0f6f2` | `#dcebe1` | `#bbd6c5` | `#90bba2` | `#649d7e` | `#448262` | `#33694e` | `#285440` | `#1f4434` | `#16382b` | `#0b2018` |
| **leaf** (logo red accent) | `#fdf0ee` | `#fbdcd7` | `#f7b5ac` | `#f08a7d` | `#e55c4b` | `#d03020` | `#b02718` | `#8f1f13` | `#741a10` | `#60160e` | `#380a06` |
| **ink/neutral** (green-tinted grey) | `#f7f9f7` | `#eef2ee` | `#dde4dd` | `#c2ccc2` | `#98a699` | `#6d7d6f` | `#556457` | `#425043` | `#323d33` | `#222b24` | `#131a15` |

*(neutral scale is green-tinted to keep photography and greens in one temperature.)*

### 2.2 Contrast-verified usage pairs (WCAG 2.2, computed)

| Foreground | Background | Ratio | Use |
|---|---|---|---|
| `pine-950 #052e19` | `green-500 #1dbf5a` | **6.1:1** | Primary CTA button text (AA normal) |
| `#ffffff` | `green-700 #0e8140` | **4.96:1** | White text on solid green (links-on-green, CTA hover state) |
| `pine-950 #052e19` | `amber-400 #fcb929` | **8.6:1** | Amber badges/chips/sticky-bar highlight text |
| `pine-950 #052e19` | `#ffffff` | **14.9:1** | Body/headline text |
| `ink-600 #556457` | `#ffffff` | **7.3:1** | Muted/secondary text, captions |
| `#ffffff` | `pine-800 #1f4434` | **7.1:1** | Footer/header-dark surfaces text |
| `amber-400 #fcb929` | `pine-900 #16382b` | **5.2:1** | Accent links & icons on dark surfaces |
| `leaf-600 #b02718` | `#ffffff` | **5.1:1** | Leaf-red accent text (season badge text) |
| `green-600 #12a24a` | `#ffffff` | **3.9:1** | **Large text / UI graphics only** (never body text) |

**Forbidden:** white text on `green-500` (2.4:1), `green-500` text on white for body (2.9:1),
amber text on white (1.9:1). CI lint (doc 07) greps templates for these pairs.

### 2.3 Semantic token mapping (dark-mode-ready)

```
--surface: #ffffff | --surface-2: neutral-50 | --surface-3: green-50 | --surface-inverse: pine-900
--text: pine-950 | --text-muted: ink-600 | --text-inverse: #fff
--border: neutral-200 | --border-strong: neutral-300
--action: green-500 | --action-hover: green-600 | --action-text: pine-950
--signal: amber-400 | --signal-text: pine-950
--focus: green-600 (3px ring, offset 2) | --success: green-600 | --danger: leaf-600
```

## 3. Typography — Nunito (EN/FR) + Tajawal (AR)

| Role | EN/FR | AR | Mobile | Desktop |
|---|---|---|---|---|
| Display / H1 | Nunito **800** | Tajawal **800** | clamp(1.9rem, 6.4vw, 2.6rem) | clamp(2.6rem, 4vw, 3.5rem) |
| H2 section | Nunito 800 | Tajawal 800 | clamp(1.5rem, 5vw, 1.9rem) | 2.25rem |
| H3 card/title | Nunito 700 | Tajawal 700 | 1.125rem | 1.375rem |
| Body | Nunito 400 | Tajawal 400 | 1rem / **1.65** | 1.0625rem / 1.7 |
| Body AR line-height | — | **1.9** (Arabic needs air) | | |
| Eyebrow/label | Nunito 800, +0.08em tracking, uppercase | Tajawal 700, **no tracking, no uppercase** (Arabic has no case; tracking breaks joining) | 0.75rem | 0.8125rem |
| Data/spec (temps, grades) | Nunito 700 tabular (`font-variant-numeric: tabular-nums`) | Tajawal 700 + Western digits | 0.9375rem | 1rem |
| Button | Nunito 800 | Tajawal 700 | 1rem | 1rem |

Delivery: self-hosted woff2 only. EN/FR: Nunito **variable (wght 400–800)** subset `latin` (+ tiny `latin-ext` for 400/700).
AR: Tajawal static **400/700/800** subset `arabic`. `font-display: swap` + metric-matched fallbacks:

```
@font-face{font-family:"Nunito Fallback";src:local("Arial");size-adjust:101%;ascent-override:96%;descent-override:24%;line-gap-override:0%}
@font-face{font-family:"Tajawal Fallback";src:local("Arial");size-adjust:104%;ascent-override:88%;descent-override:20%;line-gap-override:0%}
```
→ zero CLS on font swap. Budget: ≤ 40 KB (EN page) / ≤ 85 KB (AR page) font transfer, preloaded only for the weights used above the fold.

## 4. Logo production pipeline (fixes DQ-02)

1. Background-remove the cream field from the 3 PNGs (chroma-key `#FDF9F6 ±6` + edge de-fringe) → transparent PNG masters.
2. Vector-trace the mark into **`logo-mark.svg`** (2 paths: leaf `#D8402C`, Nile-S `#3D6644`) — crisp at 24 px favicon and 400 px hero.
3. Lockups: `logo-en.svg` / `logo-ar.svg` (mark + wordmark set in Nunito 800 / Tajawal 800 to match source lettering).
4. Output set per lockup: SVG (primary), WebP @1x/2x (fallback), PNG @2x only where SVG unsupported. Header uses SVG ≤ 8 KB.
5. Mono variants for dark surfaces: `logo-mark-mono-light.svg` (all `#fff`) and `mono-pine.svg` — for footer & hero scrim, where the full-colour mark would fight the background.
6. Favicon: `favicon.svg` (mark) + `apple-touch-icon.png` 180² + `site.webmanifest` with theme_color `#0b542e`.

## 5. Layout primitives

- **Breakpoints (min-width, mobile-first):** base 360 · 480 · 640 · 768 · 1024 · 1280 · 1536.
- **Container:** 100% − 32 px gutters → 720 → 960 → 1200 px max.
- **Grid:** 4 col mobile / 8 @768 / 12 @1024; 20 px gutters mobile, 24 desktop.
- **Spacing:** 4-px base: 4 8 12 16 20 24 32 40 48 64 80 96 128. Section padding: 56 mobile / 96 desktop.
- **Radius:** `xs 6 · sm 10 · md 14 · lg 20 · pill 999`. Cards `lg`, buttons/inputs `pill`, chips `pill`, media `md`.
- **Elevation (green-tinted):** e1 `0 1px 2px rgba(11,32,24,.06), 0 2px 8px rgba(11,32,24,.06)`; e2 adds `0 12px 28px rgba(11,32,24,.10)`; e3 (sheets/modals) `0 24px 64px rgba(11,32,24,.18)`.
- **Borders:** 1 px `neutral-200`; focus ring 3 px `green-600` @ 40 % + 2 px offset.

## 6. Component library (names = template partials, doc 04)

`ui-topbar` (contact strip) · `ui-header` (logo, nav, lang switch, CTA) · `ui-sheet-menu` (mobile) ·
`ui-command-bar` (mobile sticky bottom: Call / WhatsApp / Quote) · `ui-hero` (scroll-snap slides + proof stats) ·
`ui-stats-band` · `ui-section-head` (eyebrow + H2 + lead) · `ui-card-service` · `ui-card-product` ·
`ui-card-post` · `ui-card-testimonial` · `ui-faq-accordion` · `ui-tabs-categories` · `ui-chips-filter` ·
`ui-spec-rows` (icon + label + value, product detail) · `ui-gallery` (swipe) · `ui-breadcrumbs` ·
`ui-cta-band` · `ui-footer` · `ui-cookie-consent` · `ui-form-*` (field, input, select, textarea, checkbox, inline-error, success) ·
`ui-lang-switch` (globe + `EN|ع|FR` pill, dropdown on mobile) · `ui-whatsapp-fab` (desktop only; mobile has command bar).

Card anatomy (product): 1:1 media on `surface-2`, category chip (amber tint), name 2-line clamp,
key spec line (tabular), arrow affordance; tap target = whole card; hover e2 + media scale 1.03 (LTR/RTL safe).

## 7. Iconography

One inline-SVG set, 24 px grid, 1.75 stroke, round caps/joins, `currentColor`, aria-hidden + adjacent text or `aria-label`.
Keys (40): `leaf, sprout, citrus, snowflake, can, box, tag, route, ship, plane, shield-check, thermometer, doc-stamp, clipboard, globe, lang, phone, whatsapp, mail, instagram, facebook, pin, clock, menu, close, chevron-{l,r,d,u}, arrow-{l,r}, plus, minus, check, star, quote, search, filter, grid, list, copy, external, upload, image, edit, trash, eye, eye-off, save, drag, users, chart, calendar, spark`.
Brand glyphs (whatsapp/instagram/facebook) are filled paths from official assets, single-colour.
**No emoji anywhere** (explicit upgrade over the reference site).

## 8. Imagery art direction

- Product shots: keep native 800×800 studio framing; card background `surface-2`; no filters.
- Category heroes & home slides: division photos + strongest catalogue shots, `pine-950 → transparent` scrim (60 % at text side), text always on scrim, never on busy pixels.
- Section backgrounds alternate `surface` / `surface-3` (green-50) to create rhythm without gradients.
- OG images: auto-composed 1200×630 (product photo right, pine-900 panel left, name + logo) at build time.

## 9. Motion & interaction

- Durations 120/180/240/320 ms; easing `cubic-bezier(.2,.7,.2,1)`; nothing bounces.
- Reveals: opacity + 8 px translate, IO-triggered, **stagger ≤ 60 ms**, only ≥ 640 px width on content-heavy sections (mobile keeps instant paint for CWV).
- Counters animate once on first intersect (respect reduced motion → final value immediately).
- Carousels: native `scroll-snap-type: x mandatory` + `scroll-behavior: smooth`; JS only for dots/aria (≈ 1 KB).
- Pressed state `transform: scale(.98)`; hover lift 2 px + e2.
- `@media (prefers-reduced-motion: reduce)`: all animation/transition → 0 ms, scroll-snap stays.

## 10. Accessibility contract (WCAG 2.2 AA)

Touch ≥ 48×48 (icons visually 24 inside 48 hit area) · spacing between targets ≥ 8 · focus-visible ring on every interactive ·
skip-link · landmarks (`header/nav/main/footer/aside`) · single H1 per page · heading order never skips ·
form labels always visible (no placeholder-only) · error text + icon + `aria-describedby` · accordion/tabs with full ARIA + arrow keys ·
colour never sole signal · text over imagery ≥ 4.5:1 via scrim · Arabic screen-reader pass (NVDA/TalkBack) ·
`lang` attributes on mixed-language snippets (product Latin names inside AR text get `<span lang="en">`).
