# 07 — Delivery Roadmap, Quality Gates & Launch Runbook

## 1. Phases (≈ 8 weeks to launch + 2 weeks hypercare)

### Phase 0 — Sign-off & production assets (3 days)
- Client confirms: DQ-07 (address/hours), Facebook page vanity URL, WhatsApp display format, legal entity line for footer.
- Approve: palette reconciliation (doc 02 §2), logo re-cut + SVG trace proofs (mono variants included).
- Hosting: confirm plan tier (SSH + cron + SSL), create DB, mailbox `contact@` + SMTP creds, **SPF/DKIM/DMARC** DNS records, CDN (Cloudflare) attach.
- Outputs: signed-off token sheet, logo pack in `assets/brand/`, hosting access sheet (secrets never in git).
- **Gate G0:** assets + access + DNS ready; palette & logo approved in writing.

### Phase 1 — Foundation & pipelines (5 days)
- Repo structure per doc 04 §2.1; `config` outside webroot; `.gitignore` for secrets/cache/storage.
- Core PHP kit: Db/Router/View/Cache/I18n/Seo/Slug/Validator/Csrf/Mailer/ImageGd/StaticBuilder/Audit.
- DB `tools/migrate.php` (schema doc 01 §3) + `tools/seed.php` (settings, categories, 6 services, 12 FAQs, blocks from profile, 8 post stubs).
- Design tokens → CSS build (purge/minify/critical), base JS modules, font subsetting, image pipeline;
  **benchmark**: full static rebuild of ~700 pages on the actual Hostinger account (accept ≤ 90 s, else chunked cron).
- CI gates wired (see §3). **Gate G1:** rebuild benchmark + LHCI "hello world" template within budgets.

### Phase 2 — Public site, mobile-first (10 days)
Build order (mobile contract first, desktop delta second, RTL third per template):
chrome (topbar/header/sheet/command-bar/lang/footer) → home → categories hub → category listing →
product detail → about → services + detail → blog index/detail → contact + form + mail → faq →
4 supporting pages → privacy/terms → 404 → sitemaps/robots artifacts.
- Per-template exit: LHCI budgets green (mobile), axe 0 serious, keyboard pass, RTL screenshot diff clean,
  HTML valid, no CLS, links resolve.
- **Gate G2:** all templates pass §3 gates on 360/390/768/1440 in EN + AR.

### Phase 3 — Dashboard (8 days)
Auth/RBAC → layout + lists UX → products & categories (incl. manifest import wizard) → services/posts/blocks/faqs →
media manager → SEO board/redirects/sitemap → enquiries → settings/contact preview → rebuild integration → audit.
- **Gate G3:** CRUD round-trip per entity regenerates correct static pages < 5 s; completeness meter blocks
  incomplete publish; autosave/PRG/CSRF verified; admin usable at 390 px.

### Phase 4 — Content & translations (8 days, overlaps Phase 3 tail)
- Import 159 products (EN) from manifest with media + variants + alts; fix DQ-01 (second juice photo).
- Professional translation EN→AR, EN→FR of: UI strings, 159 products × 6 fields, categories, services,
  blocks, FAQs, 8 posts, meta/alt; glossary applied; native review pass (trade terms).
- **Gate G4:** completeness board = 100 % for published entities in 3 langs; AR proofread sign-off;
  manifest-parity check passes (159/159, field-level).

### Phase 5 — SEO & performance hardening (5 days)
- JSON-LD validation per template × lang; sitemap submission; GSC + hreflang report clean; on-page keyword
  pass per §4 map; internal-link orphan check; OG debug per lang; CWV field instrumentation;
  security checklist execution (§3); mail deliverability tests (Gmail/Outlook/Yahoo inbox, not spam);
  load test static pages (hey 500 req @ 50 conc → p95 < 300 ms via CDN).
- **Gate G5:** all §3 CI gates green on production URL; GSC zero coverage errors.

### Phase 6 — Launch + hypercare (2 days + 14 days)
Runbook §4; training (2 h, recorded) + admin manual EN/AR (PDF from dashboard docs); hypercare daily checks
week 1, weekly week 2–4; first editorial cadence posts scheduled.

## 2. QA matrix

| Dimension | Coverage |
|---|---|
| Devices | 360×740 (Galaxy A-class), 375 (iPhone SE), 393 (iPhone 15), 412 (Pixel), 768 (iPad mini), 820 (iPad Air), 1440 desktop |
| Browsers | Chrome/Android, Safari iOS 16+, Samsung Internet, Firefox, Edge, Safari macOS |
| RTL | every template: mirror audit (chevrons, swipe, timeline, command bar, tables sticky column), Tajawal render, no Latin leakage (except tagged spans), TalkBack pass |
| A11y | axe-core CI + manual: keyboard full journey (browse→product→quote→submit), focus order, SR labels EN/AR, contrast (doc 02 §2.2), reduced-motion, 200 % zoom no overlap |
| Forms | validation matrix (empty/invalid/long/unicode/RTL input), spam probes, rate-limit probe, mail delivery + reply-to, success/error SR announcement, JS-off fallback |
| Content | field-level diff of 159 products vs `content-manifest.json`; spelling EN/FR; Arabic proofread; numbers/temps tabular check; no lorem/placeholder strings (CI grep) |
| Perf | §budgets per template; cold-cache CDN miss test; font-swap CLS test; 3G throttle sanity (home < 3.5 s LCP) |
| SEO | §05 checklist; redirect chain scan; canonical/hreflang audit; schema validators; title/desc uniqueness |
| Security | headers scan, SQLi/XSS probe set on form + admin, CSRF probe, upload probe (php/jpeg polyglot), auth lockout probe, directory traversal probe, backup restore drill |
| Ops | rebuild after each entity type; rollback drill; cron mail retry drill; maintenance mode drill |

## 3. CI gates (run on every push to the session branch)

1. `php -l` all sources; unit smoke (Db/View/Slug/I18n/Mailer-dry).
2. Build asserts: JS ≤ 45 KB gz, CSS ≤ 35 KB gz, fonts ≤ 120 KB, third-party requests = 0.
3. LHCI: budgets doc 04 §6 for 5 key templates (mobile preset).
4. `html-validate` + custom rules: single h1, img width/height+alt, landmark presence, no inline JS except JSON-LD.
5. axe-core: 0 critical/serious per template.
6. i18n: every template renders in 3 langs without missing-key markers; completeness = 100 % for seeds.
7. Contrast lint: grep forbidden pairs (doc 02 §2.2 "Forbidden").
8. Links: internal crawl 200-only; redirects ≤ 1 hop; sitemap == rendered URL set.
9. Schema: JSON-LD parse + required props per template.
10. Manifest parity: DB product count/fields vs `docs/data/content-manifest.json`.
11. RTL: Playwright screenshots EN vs AR per template → visual diff threshold.
12. Security headers assertion + CSP violation report empty in test run.

## 4. Launch runbook (T-0 = 09:00 Cairo)

| T | Action | Owner |
|---|---|---|
| T-24 h | freeze content; full backup; full rebuild on staging; run §2 QA smoke | dev |
| T-4 h | DNS TTL lowered; final prod backup; mail creds test | dev |
| T-0 | deploy (rsync swap) → migrate → rebuild full → CDN purge → smoke 20 URLs × 3 langs → form POST test → admin login test | dev |
| T+10 m | GSC sitemap submit; hreflang check; CrUX/CWV baseline; uptime + alerts on | dev |
| T+1 h | client walkthrough on real phones (EN + AR) | client + dev |
| T+24 h | error-log review; enquiry delivery confirm; CWV lab re-check | dev |
| Rollback | swap `releases/prev/` + DB is migration-safe → < 5 min; decision authority: dev lead + client owner | |

## 5. Risks & mitigations

| Risk | P | Impact | Mitigation |
|---|---|---|---|
| Shared CPU throttles full rebuild | M | slow publishes | chunked cron drain (designed), scoped rebuilds default |
| GD lacks AVIF on host | M | bigger images | WebP fallback path already in `<picture>`; build-time AVIF locally covers seeds |
| Hostinger mail to spam | M | lost leads | SPF/DKIM/DMARC in Phase 0; deliverability test gate G5; DB copy + inbox fallback |
| Translation slips on trade terms | M | credibility | glossary table in dashboard + native reviewer sign-off (G4) |
| Logo vector trace quality | L | brand damage | client approval of trace at G0; PNG-2x fallback retained |
| Scope creep (supporting pages) | M | schedule | pages frozen at G2; additions → phase-2 backlog |
| Client adds products mid-build | L | rework | import wizard + dashboard handles delta; manifest re-run |

## 6. Phase-2 backlog (post-launch, not in contract baseline)
Dark mode (tokens ready) · multi-image galleries per product · WhatsApp Business API catalogue sync ·
dealer/importer portal login · ERP/stock feed · additional languages (de/es) via same i18n tables ·
AMP-free PWA offline catalogue for trade shows.

## 7. Agent execution contract (how *I* will build this, when you say go)

1. Work only on branch `arena/01a0a234-nile-maple`; one commit per phase milestone; push after each.
2. Implementation order = Phase 1 → 6 above; every template lands with its CI gates green before the next.
3. Naming contracts to honour: partials `templates/ui/{name}.php` exactly as doc 02 §6; tokens exactly as
   doc 02 §2–3; DB exactly doc 01 §3; routes exactly doc 05 §1; admin routes exactly doc 06 §1.
4. Content enters only via `tools/seed.php` from `docs/data/content-manifest.json` (never hand-typed rows).
5. No new runtime dependencies without a written amendment to doc 04 (vanilla-PHP pledge).
6. Each phase ends with: preview deploy on the sandbox live-preview (0.0.0.0 bind), LHCI report attached to
   the commit message, and a short client-facing changelog in `docs/CHANGELOG.md`.
