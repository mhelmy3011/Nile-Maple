# NILE-MAPLE — FINALIZATION PLAN

### From "builds and deploys" to "enterprise-grade, test-assured, production-signed-off"

**Version** 1.0 · **Date** 2026-09-15 · **Owner** UI/UX & Technical Consulting Lead
**Scope** Public trilingual site (EN/AR/FR) + Admin Control Room + test architecture + production audit
**Design benchmark** https://greenchem-egy.com/ — structure adopted, craft deliberately out-engineered
**Status** Awaiting sign-off to execute. **No code has been changed in producing this plan.**

---

## 0. How to read this document

`PLAN.md` and `docs/01–07` are the **greenfield design contract**. They describe what should exist.
This document is the **finalization contract**: it starts from what *actually* exists today — verified
by running the site, querying the database, driving the browser at 375 px and submitting real requests —
and defines everything that remains between here and a signed production launch.

| Part | Answers |
|---|---|
| [1. Verified current state](#1-verified-current-state-evidence-based) | What is genuinely built, measured, not assumed |
| [2. Defect register](#2-defect-register) | Every defect found, with root cause, fix and proof-of-fix |
| [3. Workstream A — Design & mobile-first](#3-workstream-a--design--mobile-first-finalization) | Screen-by-screen finalization to best-in-class mobile |
| [4. Workstream B — Performance](#4-workstream-b--performance-to-the-maximum) | Budgets, LCP strategy, image pipeline, measurement |
| [5. Workstream C — SEO](#5-workstream-c--seo-completion) | Structured data, per-entity SEO, international SEO, content |
| [6. Workstream D — Dashboard](#6-workstream-d--dashboard-finalization) | Every entity, every editing interaction, smoothness contract |
| [7. Workstream E — Test architecture](#7-workstream-e--test-architecture-unit--integration--e2e) | Unit + integration + E2E covering all 303 units |
| [8. Workstream F — Real-user audit](#8-workstream-f--the-real-user-audit) | The human pass: personas, journeys, 400-point checklist |
| [9. CI/CD quality gates](#9-cicd-quality-gates) | What must be green before anything merges |
| [10. Schedule & exit gates](#10-schedule--exit-gates) | Phases, durations, who signs what |
| [11. Launch runbook](#11-launch-runbook--hypercare) | T-minus sequence, rollback, hypercare |
| [12. Risk register](#12-risk-register) | What can go wrong and the pre-agreed response |
| [13. Definition of done](#13-definition-of-done-acceptance-contract) | The acceptance contract |

**A note on method.** I did not read the code and infer quality. I ran it. Every number and every
defect below carries the command or observation that produced it. That distinction is the whole reason
this plan exists — see §1.4.

---

## 1. Verified current state (evidence-based)

### 1.1 What is built

| Dimension | Measured value | Method |
|---|---|---|
| Application code | **7,664 LOC** vanilla PHP 8, 28 classes, no framework, no runtime dependencies | `wc -l app/ tools/ assets/src/` |
| PHP methods | **180** across 28 classes | function-signature count |
| Client JS | **123 functions** across 5 ES modules (`base`, `admin`, `contact`, `listing`, `post`) | function-signature count |
| Static pages built | **582** `index.html` files across `/en/ /ar/ /fr/` | `find public_html -name index.html` |
| Products | **159** rows, **477** i18n rows (159 × 3 languages — complete) | SQLite |
| Categories / Services / Posts / FAQs / Blocks | 4 / 6 / 8 / 12 / 21, all with 3-language i18n children | SQLite |
| Media | **158** assets, **1,264** generated variants, **474** i18n alt rows | SQLite |
| Home page weight | **94,769 B raw → 16,472 B gzipped** | `gzip -c public_html/en/index.html` |
| Home network requests | **5 total** (document, 2 logo images, 1 JS, 1 font). Zero third-party. CSS fully inlined. | DevTools network capture |
| Route smoke | `/en/` 200 · `/ar/` 200 · `/en/products/orange/` 200 · `/manage/` 302 | `curl` |
| Internal links on EN home | **40 links, 0 broken** | filesystem resolution of every `href` |
| hreflang | Correct 4-way (`en`/`ar`/`fr`/`x-default`) with **translated slugs** (`/ar/products/برتقال/`) | markup inspection |
| RTL | `<html lang="ar" dir="rtl">`, mirrored chrome, Tajawal rendering correctly | 375 px browser render |
| Enquiry pipeline | End-to-end POST → validation → DB row → `mailed=1` | live POST to `/api/enquiry` |
| Toolchain available | PHP 8.5.4 (gd, intl, sqlite3, mbstring, curl) · Node 26.8.1 · Composer 2.9.5 · npx 11.19 | version probes |

**This is a genuinely strong foundation.** The architecture decision that matters most — static-render
public pages with PHP only as a control plane — is correctly implemented and is the single biggest
reason the performance ceiling here is higher than the benchmark site's.

### 1.2 What is NOT built

| Missing | Evidence |
|---|---|
| **Any automated test whatsoever** | Zero test files in the repository. No PHPUnit, no Playwright, no Vitest, no CI configuration. |
| **Per-entity SEO data** | `seo_meta` table: **0 rows**. Every page currently falls back to global templates. |
| **Audit trail** | `audit_log` table: **0 rows** despite the write path existing. |
| **Hero imagery** | The home hero contains no `<img>` at all — see D-03. |
| **Division card imagery** | `.dc-media` renders as an empty `<span>`. |

### 1.3 The functional blockers

Two defects make the current build **unshippable**, and neither is visible from the code or from the
existing verification tooling:

1. **The dashboard cannot be opened at all.** `/manage/login` throws an uncaught `PDOException` before
   the login form can be used.
2. **The English and French home pages display Arabic text** in the statistics band.

Both are detailed in §2.

### 1.4 Why the existing verification passed anyway — the core lesson

```
$ php tools/verify.php
83 passed · 1 warnings · 0 failed
► READY TO DEPLOY
```

`tools/verify.php` reports the build is ready to deploy **while the dashboard is fatally broken and the
English home page is showing Arabic**. It is not a bad tool — it is an excellent *artifact* checker
(files exist, variants are under budget, `.htaccess` directives are present, secrets are outside the
webroot). But it asserts nothing about **behaviour**.

> **This is the central justification for Workstream E.** Artifact checks verify that things were
> *produced*. Only functional tests verify that things *work*. A pipeline that says "READY TO DEPLOY"
> over a dead dashboard is worse than no pipeline, because it converts an unknown into a false
> assurance. Every gate defined in §9 is designed so that this specific class of failure cannot recur.

---

## 2. Defect register

Severity: **P0** blocks launch · **P1** blocks launch, user-visible quality · **P2** must fix before
launch, lower blast radius · **P3** polish, may ship to hypercare.

### P0 — Launch blockers

#### D-01 · Dashboard is completely unreachable (fatal error on login)

- **Symptom** `GET /manage/login` renders the login card, then dumps
  `Fatal error: Uncaught PDOException: SQLSTATE[23000]: Integrity constraint violation: 19 NOT NULL constraint failed: sessions.user_id`
  with a full stack trace including absolute filesystem paths.
- **Root cause** A direct contradiction between two files:
  - `app/Schema.php` declares `sessions.user_id INTEGER NOT NULL` with an FK to `users(id)`.
  - `app/Session.php:37` deliberately writes `NULL` for anonymous sessions — its own comment states
    *"an anonymous session must store NULL, not 0 (no user id 0)"*.
  The session handler fires on **every** `/manage/` request, including the pre-authentication login
  page. The intent in `Session.php` is correct; the schema contradicts it.
- **Blast radius** 100 % of dashboard functionality. Every entity editor, the media library, the
  enquiry inbox, the SEO board, settings, users and the audit log are unreachable. The public site is
  unaffected (public forms use stateless HMAC CSRF, not PHP sessions — verified).
- **Fix** Make `sessions.user_id` nullable (`INTEGER NULL`, FK `ON DELETE CASCADE` retained) via a
  forward migration; keep `Session::write()` as written. Add the migration to `tools/migrate.php` so
  existing installations converge.
- **Secondary finding** The fatal leaked a full stack trace with server paths to the browser. Correct
  in production (`display_errors` is off when `env !== 'dev'`), but the error page must never reveal
  internals in *any* environment reachable by a browser. Route all uncaught throwables through a
  handler that logs verbosely and renders a neutral page.
- **Proof of fix** Integration test `AuthFlowTest`: anonymous `GET /manage/login` → 200 with no
  exception; valid credentials → 302 to dashboard; session row written with `user_id` populated;
  anonymous session row written with `user_id IS NULL`. Plus E2E `admin-login.spec.ts`.

#### D-02 · Arabic strings leak into the English and French home pages

- **Symptom** The home statistics band renders Arabic labels on `/en/` and `/fr/`:
  `قطاعات تشغيلية` (operating divisions), `خط منتج` (product lines), `لغات خدمة` (service languages),
  `ساعة للرد على عرض السعر` (hours to quote response).
- **Scope, measured precisely** A script that strips markup and excludes the legitimate
  `العربية` language-switcher label found exactly **2 affected files**: `public_html/en/index.html`
  and `public_html/fr/index.html`. All 580 other pages are clean. This is a seeded-content defect in
  the home hero-stat block payload, not a systemic i18n failure.
- **Blast radius** The highest-traffic page in two of three languages. An English-speaking buyer's
  first impression is four lines of Arabic under the hero. Credibility damage is disproportionate to
  the size of the bug.
- **Fix** Repair the `stat` block i18n payload for `en` and `fr` in the seed, re-run the seed for that
  block, rebuild. Then prevent recurrence with the language-purity gate below.
- **Prevention gate (new, mandatory)** CI check: no page under `/en/` or `/fr/` may contain
  characters in the Arabic Unicode block `U+0600–U+06FF` outside an element explicitly marked
  `lang="ar"`; no page under `/ar/` or `/fr/` may contain untranslated English fallback markers.
  This gate is cheap, runs on the built output, and would have caught D-02 at the first build.

### P1 — User-visible quality, launch blocking

#### D-03 · The home hero is an empty dark void on mobile

- **Symptom** At 375 px the hero occupies the full above-the-fold area with **no image** — roughly
  180 px of flat dark green above the eyebrow before any content appears. Confirmed by network
  capture: the home page requests **no hero image at all**.
- **Against spec** `docs/03 §2` requires a hero photograph with a `pine-950 → transparent` scrim and
  the text block bottom-anchored in the thumb zone.
- **Compounding issues in the same component**
  - The hero declares `aria-roledescription="carousel"` and `role="group" aria-label="1/2"` but
    contains **one** slide; the second slide element renders empty and `.hero-dots` is an empty
    container. A screen reader announces a carousel with navigation that does not exist.
  - Division cards below carry an empty `<span class="dc-media">` — no imagery either.
- **Why this matters commercially** This is an agricultural export business. The product *is* the
  photography. The benchmark site leads with a full-bleed field image and stat overlay; we currently
  lead with a coloured rectangle. We hold 158 professionally shot 800×800 product images and division
  photography, and we are showing none of them above the fold.
- **Fix** Ship the hero as specified: one art-directed image per slide, `fetchpriority="high"`,
  `preload` for the LCP candidate, explicit `width`/`height`, AVIF/WebP/JPEG `<picture>`, scrim
  applied as a CSS gradient (never a second image request). Either populate the second slide or
  remove the carousel semantics entirely — a single static hero is the faster and more honest choice,
  and is my recommendation for mobile (see §3.2). Populate `.dc-media` with the division photography.

#### D-04 · AVIF variants are missing at mobile widths — mobile pays a desktop-sized image

- **Measured** Per product, the pipeline emits: WebP at **320/480/640/800**, JPEG at **640/800**,
  AVIF at **640/800 only**. `orange-320.avif` returns **301** (does not exist).
- **Consequence** The `<picture>` element lists the AVIF `<source>` first. A 375 px phone rendering a
  2-up grid needs roughly a 320–375 px image. Because no AVIF below 640 exists, every supporting
  browser downloads the **640 px AVIF** — the browser cannot fall back to the smaller WebP, since the
  AVIF source matched first. Mobile therefore consistently over-downloads on the single most
  image-dense pages (category listings, 30 products per page).
- **Fix** Generate AVIF at all four widths (320/480/640/800) so the AVIF and WebP ladders are
  identical. Add a **build-time invariant**: every media item must have the same width ladder in every
  format, or the build fails. Re-encode the existing 158 assets.
- **Expected gain** On a 30-product category listing at 375 px, roughly **55–70 % reduction** in image
  bytes versus today. This is the single largest remaining performance win in the project.

#### D-05 · 22 interactive targets are below the minimum touch size

- **Measured at 375 px on the home page**, 22 of 97 interactive elements have a hit box under 44 px:

| Element group | Actual | Required (`docs/02 §10`) |
|---|---|---|
| Topbar email / phone links | 149×**28** / 118×**28** | ≥ 48 |
| Topbar Instagram / Facebook | 28×**28** | ≥ 48 |
| Footer navigation links (14 of them) | h=**21** | ≥ 48 |
| "View all 31 products" | 188×**26** | ≥ 48 |
| Cookie banner Accept / Privacy | h=**40** | ≥ 48 |

- **Against spec** Our own contract says ≥ 48×48 with ≥ 8 px separation. WCAG 2.2 AA (2.5.8) requires
  ≥ 24×24 as a floor, so the footer links at 21 px are a **conformance failure**, not just a
  preference. Everything else is a usability failure against our stated standard.
- **Why footer links matter more than they look** On mobile this is the site's secondary navigation.
  14 links at 21 px high, stacked, is a mis-tap generator — precisely the interaction a buyer performs
  when they are hunting for "Export Documentation" at the end of a page.
- **Fix** Apply padding (not font size) to reach 48 px hit areas while preserving visual density;
  increase footer row rhythm; enlarge social glyph hit areas with a transparent inset. Add an
  automated Playwright assertion (§7.4) that **no** interactive element on **any** template at 360/375/
  390/412 px is under 48×48 — run per template per language, so this cannot regress.

#### D-06 · Horizontal overflow at 375 px

- **Measured** Three element classes extend past the viewport: `.topbar-item` (the phone number is
  visibly **clipped** — it renders as `+20 1515919` with the last digits cut off), an unclassed
  `<span>`, and `button.tab`.
- **Note** `document.scrollWidth` equals `innerWidth` (375), so the page does not scroll sideways —
  overflow is being hidden, which *conceals* the clipping rather than fixing it. A truncated phone
  number on a B2B lead-generation site is a direct conversion defect.
- **Fix** Make the topbar a true single-row scroll container with momentum and edge fade, or drop the
  phone number to the command bar on the narrowest breakpoint and keep email in the topbar. Assert
  zero overflow in CI at 360 px (the real floor, narrower than the 375 tested here).

#### D-07 · Empty `<h3>` in the document outline

- **Measured** The heading sequence on the home page contains `H3:` with no text content, inside the
  "A controlled path from enquiry to delivery" section.
- **Impact** Screen-reader heading navigation lands on an unlabelled node; automated a11y audits flag
  it as a serious violation; it pollutes the document outline that search engines parse.
- **Fix** Populate it (step title) or demote it to a non-heading element. Add an `html-validate` rule
  banning empty heading elements.

### P2 — Must fix before launch

| ID | Defect | Detail & fix |
|---|---|---|
| **D-08** | `seo_meta` is empty (0 rows) | No per-entity title/description/OG overrides exist for any of the 159 products, 4 categories, 6 services, 8 posts, 12 FAQs in any of 3 languages. Everything inherits global templates, so titles are formulaic and descriptions are auto-derived. **Fix:** author real meta for all category, service, post and supporting pages (28 entities × 3 langs = 84 records) and generate templated-but-differentiated meta for the 159 products (§5.3). |
| **D-09** | Home JSON-LD is incomplete | Only `Organization` and `WebSite` are emitted. The home page renders a visible FAQ section with no `FAQPage` markup, a featured-product grid with no `ItemList`, and no `BreadcrumbList`. **Fix:** per-template schema matrix in §5.2. |
| **D-10** | Spam gates silently discard genuine leads | `ApiController::enquiry()` returns `{"ok":true}` — the **success** response — on honeypot hit *and* on time-trap hit (`_t > time()-3`), without persisting anything. A false positive shows the buyer "Thank you — your enquiry was delivered" while the lead is destroyed with no record anywhere. The 3-second trap is genuinely reachable by a fast user with autofill. **Fix:** always persist, flag as `status='spam'` with the trip reason, exclude from the inbox by default behind a "Spam" tab, and alert on volume. Never lose a B2B lead — the value of one is far higher than the cost of reviewing a false positive. |
| **D-11** | Owner account still on the seed password | `verify.php` warns: owner still uses `ChangeMe!2026`. **Fix:** force rotation on first login; block the seed password value at the authentication layer; make this a **hard FAIL** rather than a warning in the deploy gate. |
| **D-12** | `audit_log` empty despite a write path | No administrative action has ever been recorded. Cannot currently be exercised because of D-01. **Fix:** verify after D-01 lands; assert in integration tests that every entity write produces an audit row with before/after diff. |
| **D-13** | Category chip renders with link underline | On the product page the "Fresh Fruits" chip shows a text underline, reading as an unstyled link rather than a chip. Cosmetic but visible on the highest-intent page. |
| **D-14** | No localized-slug fallback | `/ar/categories/fresh-fruits/` 404s (correct AR slug is `فواكه-طازجة`). Slug localization is working as designed, but a user arriving from a shared English URL with an Arabic preference, or any legacy/mistyped link, hits a hard 404. **Fix:** resolve foreign-language slugs to a 301 into the correct localized URL. Low frequency, high annoyance, cheap to fix. |

### P3 — Polish (may ship to hypercare)

| ID | Item |
|---|---|
| D-15 | Logo delivered as `.webp`, not the SVG specified in `docs/02 §4`. Costs crispness at 2x/3x and blocks single-request theming. Vector-trace as planned. |
| D-16 | 582 pages built against ~700 projected. Reconcile the expected URL set against the sitemap and the router to confirm nothing is silently unbuilt. |
| D-17 | Product gallery renders a single image with no thumbnail row; `docs/03 §7` specifies a swipeable gallery. Either source additional imagery or formally de-scope the gallery to a single hero image. |

---

## 3. Workstream A — Design & mobile-first finalization

### 3.1 What we take from the benchmark, and where we beat it

I analysed `greenchem-egy.com` directly. Its section sequence is a proven conversion skeleton for this
market, and we should not be clever about replacing it:

| GreenChem section | Their execution | **Our finalized execution** |
|---|---|---|
| Hero | JS slider, 3 slides, each with a 3-stat overlay | Single art-directed image (mobile) / 2-slide scroll-snap (desktop). No slider library. Stats moved *below* the hero to protect LCP. **Fixes D-03.** |
| Stats band | 4 counters (+12 years, +500 products, +6 branches, +1000 clients) | 4 counters (4 divisions, 159 lines, 3 languages, 48 h quote). Tabular figures, animate once, honour `prefers-reduced-motion`. |
| Business areas | 6 cards with **emoji icons** (🧪 🔬 💧 🌱 🛡️ 🌾) | 6 cards with a custom 24 px inline-SVG set, `currentColor`, on real division photography. **No emoji anywhere** — this is the single most visible craft gap between a local supplier site and an international trade house. |
| Why choose us | Headline + paragraph + 4 ✅ checkmark rows | Same structure; real SVG check glyphs, verified 4.5:1 contrast, image pinned on desktop / stacked on mobile. |
| Product showcase | 5 category tabs + 6 cards, text-only, truncated | 4 category tabs + 2-up cards with **1:1 studio photography**, category chip, and a **tabular cold-chain spec line** (`3–8 °C`). Specification data on the card is a genuine B2B differentiator: it answers the buyer's first question before they click. |
| Methodology | 4 numbered steps | 6-step vertical timeline with connecting rule, each step linking to the relevant service page. **Fixes D-07.** |
| Trust band | 4 icon items | Certification / handling / documentation proof points, real icons. |
| Blog | 5 cards with date + author | 3 cards, 16:9, reading time, locale-formatted dates. |
| Testimonials | 3 cards, 5 stars, initials avatars | **Deliberately omitted until real, attributable client quotes exist.** Fabricated testimonials on an export site are a legal and credibility risk. Replaced by verifiable proof: divisions, catalogue depth, documentation capability. |
| FAQ | 5-question accordion | 12 FAQs, grouped, deep-linkable, `FAQPage` schema. |
| Contact | Floating WhatsApp bubble | **Mobile Command Bar** — Call / WhatsApp / Quote, permanently in the thumb zone. |
| Language | Arabic only | **Three languages**, full RTL, translated slugs, correct hreflang. |

**The honest summary:** GreenChem gets the *sequence* right and the *craft* wrong. Our architecture is
already better than theirs. Our craft is currently ahead on typography, colour discipline and RTL, and
**behind on imagery** — and imagery is the one dimension a buyer judges in the first 400 ms. D-03 and
D-04 are therefore the highest-value design work remaining in the entire project.

### 3.2 The mobile-first finalization pass

Design order stays `360 → 390 → 430 → 768 → 1024 → 1440`. **360 px is the contract**, not 375 — the
Galaxy A-series floor is where layouts actually break, and D-06 was found at 375 px, so 360 will be
worse.

**Per-template finalization checklist** (applied to all 16 templates × 3 languages):

1. **Above the fold at 360 px** — H1 fully visible without scrolling; primary CTA reachable in the
   thumb arc; no empty decorative void (D-03); LCP element is an image with `fetchpriority="high"`.
2. **Touch** — every interactive element ≥ 48×48 with ≥ 8 px separation (D-05); whole cards tappable;
   no hover-only affordances.
3. **Overflow** — zero horizontal overflow at 360 px; no clipped content; tables and code in their own
   `overflow-x:auto` containers with a visible affordance (D-06).
4. **Command bar** — present on every page except Contact; clears `env(safe-area-inset-bottom)`; never
   occludes the final interactive element (add matching `scroll-padding-bottom`).
5. **Thumb-zone IA** — destructive or low-frequency actions sit high; money actions sit low.
6. **Typography** — body ≥ 16 px (prevents iOS zoom-on-focus); AR line-height 1.9; no text under 12 px
   anywhere; line length 45–75 characters.
7. **Motion** — reveals only ≥ 640 px; `prefers-reduced-motion` honoured; nothing animates above the
   fold on mobile (protects LCP and INP).
8. **RTL mirror** — chevrons, timelines, progress, swipe direction, command-bar order all mirrored;
   numbers, temperatures and Latin product names stay LTR inside `<bdi>` / `lang="en"`.
9. **Forms** — correct `inputmode` and `autocomplete` on every field; visible labels; errors adjacent
   to their field with `aria-describedby`; success moves focus and announces.
10. **State design** — every component specified in five states: default, loading, empty, error,
    success. **Empty states are currently unspecified anywhere in the project** and will appear the
    first time an admin unpublishes a category or a filter matches nothing.

### 3.3 Design deliverables

| Deliverable | Detail |
|---|---|
| Hero art direction | 2 division-collage compositions + scrim spec + focal points for 360/768/1440 crops |
| Division photography | 4 category covers, art-directed, focal-point tagged |
| Icon set completion | Audit the 40-key set against actual template usage; produce any missing glyph; ship as one inline sprite |
| Logo vectorization | `logo-mark.svg`, `logo-en.svg`, `logo-ar.svg`, mono variants (D-15) |
| Empty/loading/error states | Illustrated or typographic states for: empty category, no search results, form error summary, load-more failure, offline, 404, 500, maintenance |
| OG image composer | Build-time 1200×630 per entity per language |
| Component state matrix | Every `ui-*` partial × 5 states, reviewed at 360 px in EN and AR |

---

## 4. Workstream B — Performance to the maximum

### 4.1 Where we stand

The foundation is genuinely excellent and I want to be precise about that, because the remaining work
is narrow:

- 16.5 KB gzipped home document
- **5 network requests**, zero third-party, zero blocking CSS (fully inlined)
- Pre-built static HTML served from disk — no PHP, no database on a page view
- Fonts self-hosted, subset, with metric-matched fallbacks

The architecture ceiling here is higher than the benchmark site's by a wide margin. What remains is
image delivery and measurement rigour.

### 4.2 Budgets (enforced in CI, build fails on breach)

| Metric | Budget | Enforcement |
|---|---|---|
| LCP (mobile, 4G, mid-tier Android) | **< 1.8 s** p75 | Lighthouse CI, 5 templates × 3 langs |
| CLS | **< 0.05** | Lighthouse CI + font-swap test |
| INP | **< 200 ms** | Lighthouse CI + field data post-launch |
| TTFB | < 150 ms origin / < 80 ms CDN | synthetic + field |
| Home page transferred | ≤ 700 KB | build assertion |
| Category listing (30 products) transferred | ≤ 900 KB | build assertion — **currently breached by D-04** |
| JS | ≤ 45 KB gzipped | build assertion |
| CSS | ≤ 35 KB gzipped | build assertion |
| Fonts per page | ≤ 40 KB (EN/FR) / ≤ 85 KB (AR) | build assertion |
| Third-party requests | **0** | build assertion |
| Lighthouse mobile scores | ≥ 95 Performance / SEO / Best Practices / **Accessibility** | Lighthouse CI |

### 4.3 The work

1. **Fix the AVIF ladder (D-04)** — the largest single win available. Regenerate all 158 assets with
   AVIF at 320/480/640/800. Add the format-parity invariant to the build.
2. **Establish a real LCP element (D-03)** — hero image with `fetchpriority="high"` and a matching
   `<link rel="preload">` emitted per template per breakpoint. Currently the LCP is a text node, which
   scores acceptably but leaves the page looking unfinished; we want both speed *and* the image.
3. **Verify `sizes` accuracy** — current `sizes="(min-width:1024px) 25vw, 50vw"` must be checked against
   the real rendered box at every breakpoint. An inaccurate `sizes` silently defeats the whole srcset.
4. **`content-visibility: auto`** with explicit `contain-intrinsic-size` on below-fold sections — cuts
   initial layout and paint cost on the long home page at effectively zero risk.
5. **Speculation Rules** for likely next navigations (home → categories → product) with conservative
   eagerness. Genuinely instant navigation on a static site, no framework required.
6. **Cache headers** — immutable, hashed assets at 1 year; HTML with a short TTL plus CDN revalidation;
   confirm compression is actually negotiated at the edge (currently verified only as an `.htaccess`
   directive, never as a live response header).
7. **Long-task audit** — profile `base.js` (39 functions) for main-thread work above the fold; move
   counters and reveals behind `requestIdleCallback`.
8. **Field instrumentation** — ship the `web-vitals` attribution build behind the existing first-party
   `/api/events` endpoint. No third-party analytics, no extra request to a foreign origin, real p75
   data from real buyers on real Egyptian and European networks.

### 4.4 Measurement protocol

Lab numbers alone will not be accepted as evidence. Every performance claim must be reproduced:

- **Lab** — Lighthouse CI, mobile preset, 5 runs, median reported, on 5 templates × 3 languages.
- **Throttled real device** — Moto G-class Android over a throttled 4G profile, and one 3G sanity run
  (home LCP < 3.5 s).
- **Field** — CrUX + first-party `web-vitals` beacon, p75 reviewed at T+7 and T+28 days.
- **Load** — 500 requests at 50 concurrency against static pages, p95 < 300 ms via CDN.

---

## 5. Workstream C — SEO completion

### 5.1 Technical SEO — already correct, to be regression-locked

hreflang (4-way with translated slugs), canonicals, per-language sitemaps, robots, semantic single-H1
documents and the trilingual URL scheme are all implemented correctly. These move into CI assertions
(§9) so they cannot silently regress.

### 5.2 Structured data matrix (fixes D-09)

| Template | Required JSON-LD | Present today |
|---|---|---|
| Home | `Organization`, `WebSite` + `SearchAction`, `ItemList` (featured), `FAQPage` (visible FAQs), `BreadcrumbList` | Organization, WebSite only |
| Category listing | `CollectionPage` + `ItemList` of products, `BreadcrumbList` | to verify |
| Product detail | `Product` with `PropertyValue` specs (varieties, handling, packing, cold-chain), `Organization` seller, `BreadcrumbList` | to verify |
| Service detail | `Service`, `BreadcrumbList` | to verify |
| Blog post | `Article` with author/dates/image, `BreadcrumbList` | to verify |
| FAQ page | `FAQPage` | to verify |
| About | `AboutPage`, `Organization` with founders | to verify |
| Contact | `ContactPage`, `ContactPoint` with `availableLanguage: [en, ar, fr]` | to verify |

Every emitted block must validate in the Rich Results Test **per language**, and be asserted in CI by
parsing the built HTML (§9 gate 9).

### 5.3 Per-entity SEO content (fixes D-08)

`seo_meta` is empty. The work:

- **Hand-authored** meta titles and descriptions for the 4 categories, 6 services, 8 posts, 12 FAQ
  groups and 8 static pages — **28 entities × 3 languages = 84 records**, written by a copywriter, not
  generated.
- **Templated-but-differentiated** meta for the 159 products × 3 languages = 477 records, composed from
  real specification data (variety, season, pack format, temperature) so that no two descriptions are
  identical. Titles ≤ 60 chars, descriptions ≤ 160, uniqueness asserted in CI.
- **OG images** auto-composed per entity per language at build time.
- **Completeness board** in the dashboard must read 100 % for every published entity in all three
  languages before launch.

### 5.4 International & content SEO

- Keyword map per category and per product family, in all three languages, with Egyptian-export and
  EU-buyer intent separated.
- The **Seasonal Availability** page is the primary organic asset — a month × category matrix
  targeting "egyptian orange season", "mango season egypt", "when does egypt export strawberries".
  This page deserves disproportionate content investment; it is the highest-intent non-branded query
  cluster in the sector.
- Internal-link audit: zero orphan pages; every product reachable within 3 clicks of the home page.
- Editorial calendar for the 8 seeded posts plus 12 months of cadence.
- Google Search Console: submit all three sitemaps, resolve every coverage error, monitor the
  International Targeting report for hreflang errors weekly through hypercare.

---

## 6. Workstream D — Dashboard finalization

### 6.1 Reality check

The dashboard specification (`docs/06`) is thorough, and reading the implementation, the ambitious
parts are genuinely present: language tabs with per-tab completeness, copy-from-EN per field,
slug auto-sync with transliteration, live SERP preview, media picker modal, autosave, drag reorder,
repeatable row editors, and a generic entity engine covering all six content types.

**None of it can be reached, because of D-01.** The correct reading of the current state is not
"the dashboard needs building" — it is "the dashboard has never once been executed." Everything below
assumes D-01 is fixed first, at which point the true defect count becomes knowable.

> **Planning honesty:** I will not pretend to know the dashboard's quality from source alone. After
> D-01 is fixed, the first task is a **full exploratory pass over every screen** (§6.3), which will
> generate its own defect list. I have reserved schedule capacity for that unknown in §10 rather than
> assuming it is zero.

### 6.2 Entity coverage — the client's requirement, mapped

Every entity the client named must be fully editable, in three languages, with SEO, without a
developer. Required state at acceptance:

| Entity | CRUD | i18n EN/AR/FR | Media | SEO panel | Order | Publish gate | Rebuild scope |
|---|---|---|---|---|---|---|---|
| **Categories** | ✓ + delete guarded by product count | ✓ | cover | ✓ | drag | completeness 100 % | category + hub + home + sitemaps |
| **Products** (159) | ✓ + bulk + CSV export + manifest import | ✓ | card + gallery | ✓ | drag | ✓ | product + category + home + sitemaps |
| **Blogs** | ✓ + draft/scheduled/published | ✓ | cover | ✓ | date | ✓ | post + index + home + sitemaps |
| **Services** | ✓ | ✓ + repeatable bullets | icon + media | ✓ | drag | ✓ | service + index + home |
| **About company** | ✓ via block zones (text/list/table/stat/person) | ✓ | ✓ | ✓ | drag | ✓ | full |
| **Contact info** | ✓ with live header/footer/command-bar preview | ✓ per-language WhatsApp template | — | — | — | format validation | **full** (appears on every page) |
| **FAQs** | ✓ grouped, group manager | ✓ | — | ✓ | drag in group | ✓ | faq + home + sitemaps |
| **SEO data** | global templates + per-entity overrides + redirects + sitemap regen + robots editor | ✓ | OG default | — | — | completeness board | full |

### 6.3 Exploratory pass (post-D-01) — what gets driven by hand

For every screen: load, empty state, create, validate (empty / too long / Unicode / RTL / HTML
injection / duplicate slug), save, PRG behaviour on refresh, autosave recovery, unsaved-changes guard,
cancel, language-tab switching with unsaved data, copy-from-EN, media attach and detach, reorder by
drag **and** by keyboard, publish gate on incomplete translations, delete guard, audit row written,
static rebuild fired with the correct scope, live page reflects the change, and the whole flow repeated
**on a 390 px phone** — the client has stated they will edit from mobile.

### 6.4 The "smooth" contract (acceptance criteria)

| Criterion | Threshold |
|---|---|
| Any dashboard screen interactive | < 1.5 s on a 10 Mbit connection |
| Save → toast confirmation | < 800 ms perceived |
| Save → live static page updated | < 5 s per affected page |
| Full rebuild (582 pages) | < 90 s, else chunked cron drain (already designed) |
| Autosave | every 800 ms idle, recoverable after a browser crash |
| Data loss on validation error | **zero** — posted data always returned to the form |
| Accidental data loss | impossible without an explicit confirm |
| Dashboard JS | ≤ 15 KB |
| Dashboard a11y | WCAG 2.2 AA, full keyboard operation, usable at 390 px |
| Every write | produces an audit row with before/after diff |

---

## 7. Workstream E — Test architecture (unit + integration + E2E)

> The client's requirement: *"unit and end-to-end testing with working assurance for every single
> function in the whole website. Never miss a single detail."*
>
> Taken literally and delivered literally. **303 units** are in scope: **180 PHP methods** across 28
> classes and **123 JavaScript functions** across 5 modules. The matrix below assigns every one of
> them to a named test layer with an explicit coverage threshold. Nothing is left to "covered
> incidentally."

### 7.1 Stack (no new runtime dependencies — dev-only)

| Layer | Tool | Rationale |
|---|---|---|
| PHP unit + integration | **PHPUnit 11** (dev-only via Composer) | Standard, runs on the host PHP 8.5, zero production footprint |
| PHP mutation testing | **Infection** | Coverage percentage lies; mutation score does not |
| JS unit | **Vitest** + jsdom | Fast, ESM-native, matches the vanilla ES-module source |
| E2E / browser | **Playwright** | Chromium + WebKit + Firefox, real mobile emulation, RTL, a11y, visual diff, network interception |
| Accessibility | **axe-core** via `@axe-core/playwright` | Assert 0 critical/serious per template per language |
| Performance | **Lighthouse CI** | Budget enforcement in CI |
| HTML validity | **html-validate** + custom rules | Single H1, no empty headings (D-07), img dimensions, landmarks |
| Visual regression | Playwright snapshots | LTR vs RTL, and per-breakpoint |
| Load | **hey** / **k6** | p95 under concurrency |

All dev dependencies live in `composer.json` `require-dev` and `package.json` `devDependencies`, and
are **never deployed** — the vanilla-PHP production pledge in `docs/04` is preserved intact.

### 7.2 PHP unit coverage matrix — all 180 methods

Thresholds are per class. "Pure" classes carry the highest bar because they are cheap to test and
carry the most silent-failure risk.

| Class | Methods | Line cov. | What is asserted (representative) |
|---|---|---|---|
| `Slug` | 2 | **100 %** | Latin/Arabic/French slugification, transliteration, diacritics, collisions, max length, empty input, RTL characters |
| `Validator` | 1 | **100 %** | Every rule × pass/fail; the `$errs`-vs-key-count trap already noted in source; Unicode, max-length boundaries, `checked` |
| `Csrf` | 8 | **100 %** | Session token, visit-cookie token, no-JS token, hour-bucket rollover **and grace window**, wrong length, tampered token, missing secret → generated key file |
| `I18n` | 7 | **100 %** | Key resolution per language, **missing-key behaviour (would have caught D-02)**, pluralization, interpolation, RTL detection |
| `Alternates` | 3 | **100 %** | hreflang set per entity, translated slugs, x-default, missing-translation fallback |
| `Seo` | 12 | ≥ 95 % | Meta resolution precedence (entity → global → fallback), title/description truncation, canonical, OG, JSON-LD builders per type |
| `Markdown` | 4 | ≥ 95 % | Every supported token, nesting, **XSS injection attempts**, unclosed tokens, RTL content |
| `Util` | 9 | ≥ 95 % | ipHash stability + salt, ua truncation, json envelope, redirect, escaping |
| `Db` | 10 | ≥ 90 % | Prepared-statement binding, `upsert` conflict paths, transactions + rollback, null handling (**the D-01 class of bug**), error propagation |
| `Auth` | 9 | ≥ 95 % | argon2id hashing, verification, lockout threshold + window, 2FA, session regeneration, **seed-password rejection (D-11)**, timing-safe compare |
| `Session` | 7 | **100 %** | **Anonymous write with NULL user_id (D-01 regression lock)**, authenticated write, read, expiry, destroy, gc |
| `RateLimit` | 2 | **100 %** | Under limit, at limit, over limit, window expiry, per-key isolation |
| `Cache` | 6 | ≥ 95 % | Get/set/miss/invalidate/TTL, concurrent write, corrupt file recovery |
| `Content` | 18 | ≥ 90 % | Every query per language, pagination, search filter, featured, related, empty result sets, missing translation |
| `Img` | 6 | ≥ 90 % | Variant naming, width ladder, **format parity (D-04 regression lock)**, aspect preservation |
| `Media` | 6 | ≥ 90 % | Ingest, MIME validation, **polyglot/php-in-jpeg rejection**, variant generation, usage counting, delete guard |
| `Manifest` | 6 | ≥ 90 % | Manifest parse, field mapping, **159-product parity**, defect handling (shared image DQ-01) |
| `Schema` | 5 | ≥ 90 % | Table creation, **migration idempotency**, nullable columns, FK integrity |
| `StaticBuilder` | 6 | ≥ 90 % | Scope resolution per entity type, page generation, sitemap generation, atomic write, failure rollback |
| `Settings` | 4 | ≥ 95 % | Read/write/cache invalidation/typed access |
| `Mailer` | 4 | ≥ 90 % | Compose, headers, encoding (**UTF-8 Arabic subjects**), SMTP failure + retry, dry-run |
| `Icons` | 2 | **100 %** | Key resolution, missing key, `aria-hidden` |
| `View` | 4 | ≥ 90 % | Partial resolution, escaping by default, missing template, nested render |
| `Routes` | 1 | **100 %** | Every route pattern, language prefix, trailing slash, unknown → 404 |
| `Audit` | 1 | **100 %** | Row written with before/after diff and actor (**D-12**) |
| `Admin` | 29 | ≥ 85 % | Every entity page, save path, validation, completeness board, SEO missing, media ingest, backup, redirect-loop detection |
| `ApiController` | 5 | ≥ 95 % | csrf issue, **enquiry full matrix (D-10)**, events, load-more fragment, rate limiting |
| `PublicController` | 3 | ≥ 90 % | Page resolution, language negotiation, 404 |

**Aggregate target: ≥ 92 % line coverage, ≥ 85 % mutation score.** Mutation testing is mandatory
because line coverage alone would have happily reported the D-01 code path as "covered."

### 7.3 JavaScript unit coverage — all 123 functions

| Module | Functions | Threshold | Representative assertions |
|---|---|---|---|
| `base.js` | 39 | ≥ 90 % | Sheet menu open/close/focus-trap/ESC/scroll-lock, header hide-on-scroll, command-bar reveal threshold, language switch + cookie, counters + reduced-motion, cookie consent persistence, carousel dots + ARIA, copy-to-clipboard + toast |
| `admin.js` | 50 | ≥ 90 % | Language tabs, copy-from-EN, slugify (Latin + Arabic + French), completeness meters, SERP preview truncation, autosave debounce + recovery, drag reorder + **keyboard alternative**, media picker, repeatable rows add/remove/reorder |
| `contact.js` | 19 | ≥ 95 % | Field validation per rule, inline errors + `aria-describedby`, CSRF refresh, submit states, success/error rendering + focus move, honeypot/time-trap fields, `?product=` prefill |
| `listing.js` | 10 | ≥ 95 % | Client-side search filter, sort, live result count announcement, load-more + `pushState`, back-button restoration, **empty-result state** |
| `post.js` | 5 | ≥ 95 % | Reading progress, TOC generation ≥ 4 H2, Web Share API + fallback, share links |

### 7.4 End-to-end suites (Playwright)

**Matrix:** 3 languages × 4 viewports (360 / 390 / 768 / 1440) × 3 engines (Chromium, WebKit, Firefox).
Mobile viewports run with touch emulation and a throttled network profile.

| Suite | Scenarios |
|---|---|
| `smoke` | All 16 templates load 200 in 3 languages; no console errors; no failed requests |
| `navigation` | Header, sheet menu, footer, breadcrumbs, command bar, back/forward, deep links, anchors under the sticky header |
| `language` | Switch on every template preserves the equivalent page (client mandate), cookie persists, `dir`/`lang` flip, scroll position retained, **translated slugs resolve**, foreign-slug 301 (D-14) |
| `catalogue` | Categories hub → listing → filter → sort → load more → product → related → back; result counts correct; 159 products all reachable |
| `product` | Gallery, spec rows, temperature badge, quote prefill, WhatsApp deep link with correct per-language text, breadcrumb, schema present |
| `enquiry` | **Full matrix:** valid submit → DB row + mail; every field empty; invalid email; over-length; Unicode + RTL input; HTML/script injection; missing consent; honeypot trip; time-trap trip (**asserts the lead is persisted, not silently dropped — D-10**); rate-limit 6th attempt; expired CSRF; JS disabled; double-submit; network failure mid-submit |
| `faq` | Accordion ARIA + keyboard, one-open-per-group, deep link `#faq-12`, client-side search, empty search state |
| `blog` | Index, load more, post render, TOC, share (Web Share + fallback), related |
| `i18n-purity` | **No Arabic on `/en/` or `/fr/` outside `lang="ar"` (D-02 regression lock)**; no untranslated fallback markers; no placeholder/lorem strings anywhere |
| `a11y` | axe-core on every template × language: 0 critical/serious. Full keyboard journey browse → product → quote → submit. Focus order, visible focus, skip link, landmarks, heading order with **no empty headings (D-07)**, 200 % zoom without overlap, screen-reader labels in EN and AR |
| `touch-targets` | **Every** interactive element on **every** template at 360/375/390/412 px is ≥ 48×48 with ≥ 8 px separation (**D-05 regression lock**) |
| `overflow` | Zero horizontal overflow and zero clipped text at 360 px on every template in all 3 languages (**D-06 regression lock**) |
| `rtl-visual` | Screenshot every template LTR vs RTL; assert mirroring; assert numbers/temps/Latin names stay LTR |
| `seo` | Per template per language: single H1, canonical, 4-way hreflang, meta present and unique, JSON-LD parses with required properties (**D-09**), OG tags, sitemap matches the rendered URL set |
| `performance` | Lighthouse CI budgets; image-format-parity assertion (**D-04**); LCP element is an image with `fetchpriority` (**D-03**); zero third-party requests |
| `admin-auth` | **Anonymous login page loads without a fatal error (D-01 regression lock)**; valid login; invalid login; lockout after N attempts; 2FA; session expiry; logout; direct-URL access while logged out; role enforcement (editor cannot reach users/settings); CSRF rejection |
| `admin-crud` | For **each of the 8 entities**: create → validate → save → verify in DB → verify audit row → verify static page rebuilt → edit → verify → reorder → publish gate on incomplete translation → unpublish → delete guard. Repeated at 390 px. |
| `admin-media` | Upload, progress, variant generation, alt per language required, focal point, replace, usage badge, delete guard, orphan finder, **malicious-file rejection** |
| `admin-seo` | Completeness board accuracy, per-entity override, SERP preview, redirect create + loop detection + test, sitemap regen, robots edit |
| `admin-enquiries` | Inbox list, filters, detail, status transitions, CSV export with UTF-8 BOM (Arabic opens correctly in Excel), **spam tab (D-10)**, mail delivery log |
| `admin-resilience` | Autosave recovery after crash, unsaved-changes guard, PRG on refresh, concurrent edit by two users, validation error preserves input, session expiry mid-edit does not lose work |
| `security` | XSS probes on every input (stored + reflected), SQLi probes, CSRF probes, directory traversal, file-upload polyglot, security headers present on live responses, CSP violations empty, rate-limit enforcement, admin path not indexable, no stack traces exposed (**D-01 secondary**) |

### 7.5 Test data & environment

- Deterministic seed fixture derived from `docs/data/content-manifest.json` — tests never depend on
  production data.
- Isolated SQLite database per test run; transaction rollback between integration tests.
- Mail captured by a dry-run transport; delivery asserted without sending.
- Time frozen where time-dependent (CSRF hour buckets, rate-limit windows, time-trap).
- A dedicated `test` environment config; **no test ever touches production**.

### 7.6 "Working assurance" — the definition

A function is **assured** only when all of the following hold:

1. Unit test covering the happy path, every branch, and every documented failure mode.
2. Boundary and adversarial inputs: empty, null, max length, Unicode, RTL, HTML, SQL, path traversal.
3. Where it participates in a user-visible flow, an E2E test exercising that flow through the UI.
4. Mutation score ≥ 85 % for its class.
5. Each of the 17 defects in §2 has a **named regression test** that fails against today's code and
   passes after the fix.

---

## 8. Workstream F — The real-user audit

> The client's requirement: *"monitor the whole website and dashboard and all functionalities as a
> logic audit, or as a real user browsing and using the website. Take care of every single detail for
> the user interface and user experience. Never miss a single detail."*

Tests prove the system does what we told it to do. This workstream asks the different question:
**is what we told it to do the right thing?** It runs **after** §7 is green, because manual attention
is too expensive to spend on defects a machine can find.

### 8.1 Personas and their journeys

| Persona | Context | Journey audited end to end |
|---|---|---|
| **Dutch importer, desktop** | Sourcing citrus for a Q1 programme, evaluating 5 Egyptian suppliers in parallel | Google → category landing → compare 4 products → cold-chain specs → export documentation → quote request. **Question: within 30 seconds, does this look like a company that can ship 40 containers?** |
| **Gulf buyer, mobile, Arabic** | On a phone, on mobile data, in Arabic | Instagram → Arabic home → frozen products → WhatsApp with prefilled product text. **Question: does the Arabic read as native trade language, or as translated English?** |
| **French retail buyer, mobile** | Evaluating private-label canned goods | Search → processed & canned → private-label capability → packaging → contact in French |
| **Returning buyer, mobile** | Knows the company, wants one specific product fast | Direct → search/filter → product → call. **Question: how many taps?** |
| **Logistics coordinator** | Needs documentation specifics, not marketing | Deep link → export documentation → FAQ → email |
| **The client's own admin** | Non-technical, editing from a phone, publishing in 3 languages | Login → add a product with images and 3 translations → publish → verify live. **Question: can they complete it unaided, without fear of breaking something?** |

Each journey is walked on a **real device**, narrated aloud, timed, and recorded. Every hesitation,
back-tap, mis-tap, squint and moment of doubt is logged as a finding — hesitation is a defect even
when nothing is broken.

### 8.2 Device matrix

| Class | Devices |
|---|---|
| Android | 360×740 budget device (the contract floor), Pixel 412, Samsung 384 w/ Samsung Internet |
| iOS | iPhone SE 375, iPhone 15 393, iPhone Pro Max 430 — Safari, including the dynamic toolbar and safe areas |
| Tablet | iPad mini 768, iPad Air 820 — portrait and landscape |
| Desktop | 1280, 1440, 1920 — Chrome, Safari, Firefox, Edge |
| Conditions | Throttled 4G, throttled 3G, offline, 200 % zoom, dark-mode OS preference, `prefers-reduced-motion`, VoiceOver, TalkBack, one-handed use, direct sunlight legibility |

### 8.3 The audit dimensions (~400 checkpoints)

1. **First impression** (0–3 s) — clarity of what the company does, where it operates, and what to do
   next. Judged per language.
2. **Visual craft** — alignment, optical spacing, rhythm, typographic colour, image treatment
   consistency, icon weight consistency, no orphaned words in headings, no widow lines in CTAs.
3. **Content quality** — English idiom, French register (vouvoiement, EU trade vocabulary), Arabic as
   *native* trade Arabic and not translationese. **A native Arabic trade reviewer signs this off.**
4. **Interaction detail** — every tap has feedback within 100 ms; loading states never blank the
   screen; nothing shifts after load; scroll position survives navigation; back always does what the
   user expects; no dead ends on any page.
5. **Form experience** — keyboard type per field, autofill, error recovery, no lost input, confirmation
   the buyer believes, clear next step after submission.
6. **Trust signals** — real contact details reachable in one tap from anywhere; consistent response
   promise; no fabricated proof; privacy and terms present and readable.
7. **RTL quality** — not merely mirrored, but *right*: punctuation, mixed LTR runs, number alignment,
   icon direction, sheet-menu slide direction.
8. **Edge cases** — the longest product name in each language, the shortest, missing images, an empty
   category, a search with no results, a slow network, a failed submission, an expired session
   mid-edit.
9. **Dashboard operator experience** — can a non-technical bilingual operator add a product, translate
   it, attach media, set SEO and publish it, on a phone, without help and without anxiety?
10. **Cross-page consistency** — one component behaves identically everywhere; no template drifts.

### 8.4 Output

A findings register, each item carrying: screenshot or recording, device, language, severity,
reproduction steps, root cause, recommended fix, and effort. Triaged with the client into
must-fix-before-launch / fix-in-hypercare / backlog. **Re-audited after fixes** — an audit without a
verified second pass is an opinion, not a gate.

---

## 9. CI/CD quality gates

Every gate runs on every push. A red gate blocks merge. No exceptions, no overrides without a written
waiver from the client owner.

| # | Gate | Fails when |
|---|---|---|
| 1 | Lint & static analysis | `php -l` error, PHPStan level 8 error, ESLint error |
| 2 | PHP unit + integration | Any failure; coverage < 92 %; mutation score < 85 % |
| 3 | JS unit | Any failure; coverage < 90 % |
| 4 | Build assertions | JS > 45 KB gz, CSS > 35 KB gz, fonts over budget, third-party requests > 0, **image format parity broken (D-04)** |
| 5 | HTML validity | Invalid markup, multiple H1, **empty heading (D-07)**, `img` without dimensions or alt, missing landmarks |
| 6 | Accessibility | Any critical/serious axe violation on any template in any language |
| 7 | **Touch targets** | Any interactive element < 48×48 at 360/375/390/412 px (**D-05**) |
| 8 | **Overflow** | Any horizontal overflow or clipped text at 360 px (**D-06**) |
| 9 | SEO & schema | Missing/duplicate meta, broken hreflang, JSON-LD missing required properties (**D-09**), sitemap ≠ rendered URL set |
| 10 | **i18n purity** | Arabic characters on `/en/` or `/fr/` outside `lang="ar"` (**D-02**); untranslated fallback markers; placeholder/lorem strings |
| 11 | Links | Any internal link not 200; redirect chain > 1 hop; orphan page |
| 12 | Lighthouse CI | Any budget in §4.2 breached on 5 templates × 3 languages |
| 13 | RTL visual | Visual diff beyond threshold between LTR and RTL |
| 14 | Contrast lint | Any forbidden colour pair from `docs/02 §2.2` present in templates |
| 15 | Manifest parity | DB product count or field content diverges from `content-manifest.json` |
| 16 | Security | Missing header on a live response, CSP violation, exposed stack trace, **seed password still active (D-11)** |
| 17 | E2E | Any Playwright suite failure on any engine |

**Deployment gate:** `tools/verify.php` is retained but **demoted** — it may no longer print
"READY TO DEPLOY". That verdict now requires gates 1–17 green, which is precisely the failure mode
described in §1.4.

---

## 10. Schedule & exit gates

Durations assume one senior full-stack engineer plus a designer at ~50 % and a native Arabic trade
reviewer at ~15 %. Sequencing matters more than the absolute numbers.

| Phase | Duration | Content | Exit gate |
|---|---|---|---|
| **F0 · Unblock** | 2 days | D-01 (sessions schema + migration + error handler), D-02 (i18n leak + purity gate), D-11 (password rotation) | Dashboard opens and authenticates; EN/FR home pages are Arabic-free; **first two regression tests exist and pass** |
| **F1 · Test foundation** | 5 days | PHPUnit + Vitest + Playwright harnesses, fixtures, isolated test DB, CI pipeline with gates 1–5, regression tests for every §2 defect | CI runs on push; every §2 regression test fails against unfixed code and passes against fixed code |
| **F2 · Dashboard exploratory + fixes** | 6 days | §6.3 hand-driven pass over every screen; triage and fix what it finds; `admin-*` E2E suites | Gate 17 green for all admin suites; §6.4 smoothness thresholds met; a non-technical operator completes the product-publish journey unaided on a phone |
| **F3 · Design & mobile finalization** | 8 days | D-03, D-05, D-06, D-07, D-13, D-15; hero and division art direction; empty/loading/error states; component state matrix; per-template 360 px pass in 3 languages | Gates 6, 7, 8, 13 green; design review signed off at 360 px in EN and AR |
| **F4 · Performance** | 4 days | D-04 AVIF ladder + format parity; LCP preload; `sizes` audit; `content-visibility`; speculation rules; cache headers verified live; field instrumentation | Gate 12 green; all §4.2 budgets met; throttled real-device run recorded |
| **F5 · SEO & content** | 6 days | D-08 (84 hand-authored + 477 generated meta records), D-09 schema matrix, D-14 slug fallback, keyword map, seasonal page investment, internal-link audit | Gates 9, 11 green; Rich Results Test clean per template per language; completeness board 100 % |
| **F6 · Full E2E + security** | 5 days | Remaining public suites, security suite, load test, mail deliverability (Gmail/Outlook/Yahoo inbox, not spam), backup + restore drill | All 17 gates green; restore drill completed and timed |
| **F7 · Real-user audit** | 5 days | §8 in full, on real devices, with the Arabic reviewer | Findings register triaged; all must-fix items closed; **second audit pass verifies the fixes** |
| **F8 · Launch** | 2 days | §11 runbook | Production live, smoke passed, monitoring on |
| **F9 · Hypercare** | 14 days | Daily checks week 1, weekly week 2; CWV field data at T+7 and T+28; first editorial cadence | Field p75 within budget; zero P0/P1 open |

**Total: ~43 working days (≈ 8.5 weeks) to launch, plus 2 weeks hypercare.**

Buffer note: F2 is the least predictable phase, because the dashboard has never executed. If its
exploratory pass surfaces structural problems rather than surface defects, F2 expands and F3 starts
in parallel on the public site. I would rather state that uncertainty now than discover it in week 4.

---

## 11. Launch runbook & hypercare

| T | Action | Owner |
|---|---|---|
| **T-72 h** | Content freeze. Full backup (DB + media + build). Full rebuild on staging. All 17 gates green on the staging URL. | Dev |
| **T-48 h** | Client final walkthrough on their own phones, in all 3 languages. Sign-off recorded. | Client + Dev |
| **T-24 h** | DNS TTL lowered to 300 s. SPF/DKIM/DMARC verified. Mail deliverability retested to Gmail/Outlook/Yahoo. | Dev |
| **T-4 h** | Final production backup. Rollback path rehearsed and timed. | Dev |
| **T-0** | Deploy (atomic release swap) → migrate → full rebuild → CDN purge → smoke 20 URLs × 3 languages → live enquiry POST → admin login → verify the enquiry arrived in the real inbox | Dev |
| **T+10 m** | Submit 3 sitemaps to GSC. Verify hreflang. Baseline CrUX. Uptime monitoring and alerting armed. | Dev |
| **T+1 h** | Client walkthrough on production, real phones, EN + AR. | Client + Dev |
| **T+24 h** | Error-log review. Enquiry delivery confirmed. Lab CWV re-check. First field data sampled. | Dev |
| **T+7 d** | Field p75 CWV review. GSC coverage review. Enquiry volume and spam-tab false-positive review (D-10). | Dev |
| **T+28 d** | Full field CWV review. GSC International Targeting review. Handover of the admin manual (EN/AR) and the recorded training session. | Dev + Client |
| **Rollback** | Swap to previous release; migrations are forward-compatible. **< 5 minutes.** Authority: dev lead + client owner. | Both |

---

## 12. Risk register

| Risk | P | Impact | Pre-agreed response |
|---|---|---|---|
| Dashboard exploratory pass (F2) finds structural, not surface, problems | **M** | Schedule +5–10 d | F2 expands; F3 starts in parallel on the public site; client informed within 24 h of the finding |
| Arabic content reads as translated English | **M** | Credibility with the primary regional market | Native trade reviewer engaged from F0, not F7; glossary in the dashboard; sign-off is an F7 exit condition |
| Hero and division photography not available | **M** | D-03 cannot close; the site keeps looking unfinished | Client supplies or licenses within F3; fallback is an art-directed composition from the 158 existing product shots |
| Host GD lacks AVIF at the required widths | L | D-04 partially unresolved | WebP ladder already covers all 4 widths; generate AVIF locally at build time and deploy as artifacts |
| Full rebuild exceeds 90 s on shared CPU | M | Slow publishing | Chunked cron drain is already designed; scoped rebuilds remain the default path |
| Mail to `contact@` lands in spam | **M** | Lost leads — the site's entire purpose | SPF/DKIM/DMARC in F0; deliverability is an F6 gate; DB record + weekly digest as the safety net |
| Client adds products mid-build | L | Rework | Manifest re-import handles deltas; dashboard handles ad-hoc additions |
| Scope creep during the real-user audit (F7) | **M** | Launch slips | Findings triaged into must-fix / hypercare / backlog with the client at the time of discovery, not at the end |
| Test suite becomes slow enough to be bypassed | M | Gates erode over time | Unit suites < 60 s; E2E sharded and parallelized; full matrix nightly, a fast subset per push |

---

## 13. Definition of done (acceptance contract)

The project is complete when **every** statement below is independently verifiable:

**Functional**
1. All 17 defects in §2 are fixed, each with a named regression test that fails before the fix and passes after.
2. All 8 entities (Categories, Products, Blogs, Services, About, Contact info, FAQs, SEO data) are fully editable in 3 languages from the dashboard by a non-technical operator, on a phone, without developer help.
3. Every dashboard save regenerates the correct static pages in < 5 s and writes an audit row.
4. The enquiry pipeline delivers to `contact@nilemaple.com` on production SMTP, and **no genuine lead is ever silently discarded**.

**Testing**
5. All **303 units** (180 PHP methods, 123 JS functions) are covered per the §7.2/§7.3 matrix.
6. Aggregate ≥ 92 % PHP line coverage, ≥ 85 % mutation score, ≥ 90 % JS coverage.
7. Every E2E suite in §7.4 passes across 3 languages × 4 viewports × 3 browser engines.
8. All 17 CI gates are green, and `verify.php` no longer issues a deployment verdict on its own.

**Performance**
9. Lighthouse mobile ≥ 95 on Performance, SEO, Best Practices **and Accessibility**, for all key templates in all 3 languages.
10. Field p75: LCP < 1.8 s, CLS < 0.05, INP < 200 ms at T+28 days.
11. Every budget in §4.2 met, verified on a throttled real device, not only in the lab.

**Mobile & design**
12. Zero interactive elements under 48×48 and zero horizontal overflow at 360 px, on every template, in every language — asserted in CI.
13. Every template reviewed and signed off at 360 px in EN and AR by design.
14. Every component specified and implemented in all five states (default, loading, empty, error, success).

**SEO**
15. `seo_meta` complete for every published entity in all 3 languages; all titles and descriptions unique.
16. JSON-LD validates in the Rich Results Test for every template in every language.
17. GSC: zero coverage errors, zero hreflang errors.

**Accessibility**
18. WCAG 2.2 AA: axe 0 critical/serious across all templates and languages; full keyboard journey verified; screen-reader pass in **both** English and Arabic.

**Audit & operations**
19. The §8 real-user audit is complete on real devices, all must-fix findings closed, and **re-verified in a second pass**.
20. Native Arabic trade reviewer has signed off the Arabic content.
21. Backup and restore drill executed and timed; rollback rehearsed at < 5 minutes.
22. Client training delivered and recorded; admin manual shipped in EN and AR.

---

## Appendix A — Commands that produced the evidence in §1

```bash
# Scale
find public_html -name index.html | wc -l                     # 582
sqlite3 storage/db.sqlite "select count(*) from products"      # 159

# Weight
gzip -c public_html/en/index.html | wc -c                      # 16472

# The i18n leak (D-02) — 2 files, precisely
grep -rlP '[\x{0600}-\x{06FF}]' public_html/en --include="*.html"

# The image ladder gap (D-04)
ls public_html/assets/media/fresh-fruits/ \
  | sed 's/.*-\([0-9]*\)\.\(.*\)/\2 \1/' | sort | uniq -c
# avif 640, avif 800, jpg 640, jpg 800, webp 320, webp 480, webp 640, webp 800

# The dashboard blocker (D-01)
php -S 127.0.0.1:8099 -t public_html tools/dev_server.php &
curl -s http://127.0.0.1:8099/manage/login | tail -5
sqlite3 storage/db.sqlite ".schema sessions"   # user_id INTEGER NOT NULL
sed -n '32,40p' app/Session.php                # writes NULL for anonymous

# The false assurance (§1.4)
php tools/verify.php                           # 83 passed · 0 failed · READY TO DEPLOY
```

Touch-target sizes, overflow, heading order, image dimensions and JSON-LD coverage were measured by
executing an audit script in the browser against the running site at a 375 px viewport.

---

## Appendix B — Document map

| Document | Role |
|---|---|
| `PLAN.md` | Greenfield master plan — the original design contract |
| `docs/01-content-inventory-data-model.md` | Entities, schema, i18n model, seed data |
| `docs/02-brand-visual-identity-design-system.md` | Tokens, palette, typography, components, a11y contract |
| `docs/03-ux-mobile-first-page-specs.md` | Mobile-first page specifications, RTL spec |
| `docs/04-architecture-performance-hostinger.md` | Architecture, caching, image pipeline, deployment |
| `docs/05-seo-content-strategy.md` | Technical and international SEO, keyword map |
| `docs/06-dashboard-spec.md` | Admin IA and field-level CRUD specifications |
| `docs/07-roadmap-qa-launch.md` | Original phased roadmap and QA matrix |
| **`Finalization-Plan.md`** | **This document — verified state, defects, and the path to production sign-off** |

---

*Prepared without modifying a single line of application code. Every defect above is reproducible with
the commands in Appendix A.*
