# NILE-MAPLE — Master Implementation Plan
### Enterprise trilingual (EN / AR / FR) agri-export website + admin dashboard
### Vanilla PHP · Hostinger shared hosting · Mobile-first · Max performance · Full SEO

> **Status:** PLANNING COMPLETE — awaiting sign-off to start Phase 0.
> **Prepared by:** UI/UX & Technical Consulting lead (Arena.ai Agent Mode)
> **Source of truth for content:** the six `Nile-Maple_*.docx` files in the repository root,
> machine-extracted into [`docs/data/content-manifest.json`](docs/data/content-manifest.json)
> by [`tools/extract_content.py`](tools/extract_content.py).
> **Design benchmark:** https://greenchem-egy.com/ (same field: Egyptian agri-export) — we adopt its
> proven conversion structure and deliberately out-engineer it on mobile UX, performance and SEO.

---

## 0. How to read this plan

This plan is written to be **executable**: every document ends in concrete contracts (design tokens,
DDL, file trees, component names, acceptance criteria) so that implementation can start without
further discovery. Read in order; each doc is self-contained enough to hand to a specialist.

| # | Document | Answers |
|---|----------|---------|
| 01 | [`docs/01-content-inventory-data-model.md`](docs/01-content-inventory-data-model.md) | What content exists, entity model, DB schema, i18n data model, seed data, data-quality defects |
| 02 | [`docs/02-brand-visual-identity-design-system.md`](docs/02-brand-visual-identity-design-system.md) | Palette reconciliation, tokens, typography (Nunito/Tajawal), logo production pipeline, iconography, imagery, motion, a11y |
| 03 | [`docs/03-ux-mobile-first-page-specs.md`](docs/03-ux-mobile-first-page-specs.md) | Mobile-first design system + section-by-section spec of **every** page (LTR + RTL) |
| 04 | [`docs/04-architecture-performance-hostinger.md`](docs/04-architecture-performance-hostinger.md) | Vanilla-PHP architecture for shared hosting, static-render engine, caching, image/font pipeline, budgets, security, deployment |
| 05 | [`docs/05-seo-content-strategy.md`](docs/05-seo-content-strategy.md) | Technical + international SEO, structured data, hreflang, keyword map, editorial calendar |
| 06 | [`docs/06-dashboard-spec.md`](docs/06-dashboard-spec.md) | Admin IA, field-level CRUD specs for all 8 dynamic entities, media/translation/SEO tooling |
| 07 | [`docs/07-roadmap-qa-launch.md`](docs/07-roadmap-qa-launch.md) | Phases, milestones, QA matrix, CWV/perf/a11y/SEO gates, launch runbook |

---

## 1. Executive summary

**Nile-Maple** is an Egyptian import/export company for food & agricultural products (fresh fruits,
fresh vegetables, frozen products, manufactured/processed/canned). The deliverable is a
**trilingual, mobile-first, B2B lead-generation website** plus an **admin dashboard** that controls
every piece of dynamic data, deployed as **vanilla PHP on Hostinger shared hosting** with
enterprise-grade performance and SEO.

### 1.1 What we discovered in the source documents (verified, not assumed)

| Fact | Value |
|---|---|
| Product catalogue | **159 products** in **4 categories**: Fresh Fruits **31**, Fresh Vegetables **37**, Frozen Products **36**, Processed & Canned **55** |
| Product media | **158 unique 800×800 JPEG** product photos (perfect square = perfect card grid). One source defect: `image44.jpg` is used by both *Fruit Juice* and *Fruit Cocktail Drink* |
| Product field schema (fresh) | description · VARIETIES/TYPES · EXPORT HANDLING · PACKING · COLD-CHAIN GUIDE |
| Product field schema (frozen/processed) | description · AVAILABLE FORMS · PROCESSING & HANDLING · PACKING · FROZEN-CHAIN / STORAGE GUIDE |
| Brand assets | 3 logo PNGs: mark-only 580×500, EN lockup 875×745, AR lockup 915×875 — **all RGB with an opaque cream `#FDF9F6` background (no alpha!)** → must be re-cut before use |
| Logo inks (sampled) | leaf red ≈ `#D0302 0–#D8402C`, Nile-"S" green ≈ `#306040–#3D6644`, wordmark charcoal ≈ `#30303B` |
| Requested palette | primary `#1dbf5a`, secondary `#fcb929` — **does not match logo inks** → reconciliation strategy defined in doc 02 |
| Fonts | EN/FR: **Nunito** · AR: **Tajawal** (both self-hosted, subset, woff2) |
| Contact (must appear in header **and** footer of every page, clickable) | `contact@nilemaple.com` · tel/WhatsApp `+20 1515919135` · IG `@nilemaple2025` · FB page |
| Leadership | Eng. Issa Abousheloua (Founder & Owner) · Eng. Omar Issa (Co-Founder & Executive Manager) |
| Tagline | "Reliable products. Practical solutions. Long-term partnerships." |
| Client-mandated requirements (from Company Profile §Website) | language selector on every page (EN / العربية / Français) keeping the equivalent page; full RTL Arabic; editable per-language translation fields; enquiry form with 8 specified fields delivering to `contact@nilemaple.com` with spam protection & rate limiting and **no exposed credentials**; FAQ page; product detail pages; supporting pages (Quality & Handling, Packaging & Logistics, Seasonal Availability, Export Documentation, Privacy, Terms) |
| Narrative blocks available for pages | 4 purpose pillars · 4 history stages · 4-division table · 6-step order process · 6 quality pillars + 4 commitments · packaging/logistics/documentation bullet sets · 4 "why buyers" points · vision + 4 direction bullets |

### 1.2 The five non-negotiables and how this plan guarantees them

| Non-negotiable | Engineering answer (detail in doc 04/03) |
|---|---|
| **1. Performance to the max** | *Static-render + PHP control-plane*: every public page is a pre-built `.html` file served directly by LiteSpeed/CDN (0 PHP, 0 DB per view). Budgets: TTFB < 150 ms origin / < 80 ms CDN, LCP < 1.8 s on Moto-G4-class 4G, CLS < 0.05, INP < 200 ms, home page ≤ 700 KB transferred, JS ≤ 45 KB gz, CSS ≤ 35 KB gz, fonts ≤ 120 KB, **zero third-party blocking requests** (no jQuery/Bootstrap/Swiper/Google-Fonts CDN/Maps embed/analytics JS). AVIF/WebP srcset pipeline, inline critical CSS, self-hosted subset fonts, `content-visibility`, speculative prerender of likely next pages. |
| **2. Mobile-first, best-in-world** | 360 px base design, thumb-zone IA, sticky **Mobile Command Bar** (Call / WhatsApp / Quote), bottom-sheet nav, 2-up product grid with 1:1 imagery, scroll-snap carousels (no JS slider lib), 48 px targets, `dvh` + safe-area, tap-to-call/WhatsApp/copy-email, per-page mobile wireframes in doc 03, RTL mirror spec, `prefers-reduced-motion`. |
| **3. SEO optimized** | hreflang + x-default across 3 langs, canonical per locale, sitemap index, JSON-LD (Organization, WebSite, Breadcrumb, CollectionPage/ItemList, **Product with PropertyValue specs**, FAQPage, Article), semantic single-H1 pages, trilingual slugs, keyword map per category/family, content hubs (seasonal calendar, export docs guide), CWV as ranking substrate. |
| **4. Trilingual dynamic content** | Every string (UI, content, products, SEO meta, alt text, form labels, mail templates) lives per-language in DB/lang files; URL prefix `/en|ar|fr/`; language switch preserves the equivalent page; RTL applied at `<html dir>`; Arabic gets its own type scale & line-height, never machine-flipped layouts. |
| **5. Dashboard edits everything** | Vanilla-PHP admin with CRUD + i18n tabs + completeness meters for Categories, Products, Blogs, Services, About/company blocks, Contact info, FAQs, SEO data; media manager with auto AVIF/WebP/size variants & per-language alt; enquiry inbox; roles, CSRF, audit log, one-click static rebuild. |

### 1.3 Design direction in one paragraph

We keep GreenChem's **conversion skeleton** — utility bar with contact, hero with proof stats,
alternating story sections with counters, icon-card service grid, "why us" checklist split,
category-tabbed product showcase, blog cards, testimonials, FAQ accordion, floating WhatsApp —
because it is proven in this exact market. We then **out-class it**: real art-directed photography
instead of emoji icons, a scroll-snap hero instead of a heavy JS slider, a proper mobile command
bar instead of a desktop header squeezed down, a disciplined two-green + amber token system with
verified contrast pairs, Nunito/Tajawal typography with an Arabic-native scale, and micro-motion
that respects `prefers-reduced-motion`. The result reads as "international food-trade house",
not "local fertiliser shop" — matching Nile-Maple's B2B export positioning.

### 1.4 Key decisions log (all reversible, all reasoned)

| ID | Decision | Rationale |
|---|---|---|
| D-01 | Static-render public site; PHP only for form, search-less APIs and `/manage/` | Shared hosting has no daemon/OPcache-friendly heavy frameworks; static files give CDN-grade TTFB and survive traffic spikes |
| D-02 | Language via path prefix `/en/ /ar/ /fr/`, `/` 302-redirects by cookie/Accept-Language | Clean hreflang, language switch = prefix swap (client requirement "keep equivalent page"), no duplicate content |
| D-03 | Logo never recoloured; palette harmonised around it; logo re-cut to transparent + SVG | Trademark integrity; fixes the opaque-cream-background defect |
| D-04 | `#1dbf5a` = interactive/CTA fill with **pine-950 text** (6.1:1); white text only on `green-700` (4.96:1); `#fcb929` = highlight chips with pine-950 text (8.6:1) | Raw `#1dbf5a` with white text is 2.4:1 = WCAG fail; enterprise means verified contrast |
| D-05 | No CSS/JS frameworks; one small vanilla ES-module bundle per concern | Max performance on shared hosting; full control of RTL |
| D-06 | Images pre-processed at build time (AVIF+WebP+JPEG @4 widths); **no runtime conversion on the server** | GD-per-request on shared hosting would destroy TTFB |
| D-07 | MySQL (Hostinger MariaDB) with `*_i18n` child tables; file cache for everything else | Native to the host; simple backups; no Redis/Memcached dependency |
| D-08 | App/config/cache live **above** `public_html`; only static output + assets + front controller inside | Credentials and cache never web-reachable on shared hosting |
| D-09 | Enquiry form: server-side SMTP to `contact@nilemaple.com` + DB record; honeypot + time-trap + rate limit (+ optional Cloudflare Turnstile) | Client requirement; no exposed credentials; no heavy captcha UX on mobile |
| D-10 | Blog ships with 8 seeded, expert-written briefs; FAQ ships with 12 seeded Q&As derived from the profile | Launch with indexable depth, not an empty blog |
| D-11 | Arabic-Indic vs Western digits: **Western digits** for specs/temperatures/phones; locale formatting only for dates | International trade clarity; avoids buyer misreading cold-chain values |
| D-12 | Dashboard at `/manage/` (not `/admin/`) behind auth + optional IP allowlist; admin UI English-first | Reduces bot noise; operators work in EN while publishing in 3 langs |

---

## 2. Scope

**In scope — public site pages:** Home · About · Services (+6 service detail) · Categories hub ·
4 category listings · 159 product detail · Blog index + detail · Contact · FAQ · Quality & Handling ·
Packaging & Logistics · Seasonal Availability · Export Documentation · Privacy · Terms · 404 ·
sitemap/robots artifacts.

**In scope — dashboard:** auth/RBAC, Categories, Products, Blogs, Services, About/company blocks,
Contact info & socials, FAQs, SEO data (global + per-entity), media library, translations workbench,
enquiry inbox, settings, audit log, static rebuild, backups.

**Out of scope (phase 2 candidates):** customer login/pricing portal, e-commerce checkout,
live chat, dark mode (tokens prepared), AMP, native app, ERP integration.

---

## 3. Definition of done (site-wide, verified before launch)

1. Lighthouse (mobile, throttled) ≥ 95 on Performance/SEO/Best-Practices/**Accessibility** for all 6 main templates.
2. Field-ready CWV: p75 LCP < 1.8 s, CLS < 0.05, INP < 200 ms on 4G mid-tier Android.
3. W3C valid HTML; WCAG 2.2 AA (axe-core 0 critical/serious); keyboard + screen-reader pass in EN **and** AR.
4. hreflang/canonical/sitemap/JSON-LD validate in GSC + Rich Results test for all 3 locales.
5. All 159 products present in 3 languages with image, 4 spec fields, slug, SEO meta; completeness meter = 100 %.
6. Enquiry form delivers to `contact@nilemaple.com` end-to-end on production SMTP; spam + rate-limit tests pass.
7. Dashboard CRUD round-trip for all 8 entities regenerates the affected static pages in < 5 s per page.
8. Full backup + restore drill executed; rollback runbook signed off.
