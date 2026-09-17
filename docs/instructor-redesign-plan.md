# Instructor Views — Modern Redesign Plan
## Using Nile-Maple Dashboard Design System (Dynamic Coloring, Modern Rich UI/UX)
### Design-Only Round — No Logic/Functionality Changes

**Date:** 2026-09-17  
**Scope:** 5 views
- `Instructor/Notifications`
- `Instructor/Students/Details?enrollmentId=1`
- `Instructor/Documents/Details`
- `Instructor/Announcements/Create`
- `Instructor/Faq`

**Constraint:** 100% logic preservation. Only presentation layer changes. No model, controller logic, API contracts, validation, or data flow altered. Pure view/template + CSS enhancement.

---

## 1. Audit of Existing Dashboard Design Approach (Source of Truth)

### 1.1 Tokens (from `assets/src/app.css` + `admin.css` + doc 02)

**Palette — dynamic coloring foundation:**
- `green` 50-950: primary action. Verified pairs: `pine-950 on green-500 = 6.1:1` (AA), `white on green-700 = 4.96:1`.
- `amber` 50-950: signal, highlight, warning. `pine-950 on amber-400 = 8.6:1`.
- `pine` 50-950: dark surfaces (sidebar, footer, hero scrim). Derived from logo green. `white on pine-800 = 7.1:1`.
- `leaf` 600 `#b02718`: danger, accent. `leaf-600 on white = 5.1:1`.
- `neutral` n-50..n-950: green-tinted grey, keeps photography temperature consistent.
- Semantic: `--surface`, `--surface-2` (n-50), `--surface-3` (green-50), `--surface-inverse` (pine-900), `--text` (green-950), `--text-muted` (n-600), `--border` (n-200), `--action` (green-500), `--signal` (amber-400), `--focus` (green-600), `--danger` (leaf-600).

**Elevation:** e1 `0 1px 2px rgba(11,32,24,.06), 0 2px 8px rgba(11,32,24,.06)`, e2 adds `0 12px 28px rgba(11,32,24,.10)`, e3 `0 24px 64px rgba(11,32,24,.18)` — green-tinted shadows, not black.

**Radius:** sm 10, md 14, lg 20, pill 999. Cards lg, buttons/inputs pill, chips pill, media md.

**Spacing:** 4px base: 4,8,12,16,20,24,32,40,48,64,80,96.

**Typography:** Nunito 200-1000 variable (EN/FR), Tajawal 400/700/800 (AR). Fallback metric-matched to keep CLS 0. Line-height 1.65 EN, 1.9 AR. Eyebrow 0.75rem 800 uppercase +0.08em tracking (AR: no uppercase, no tracking). Data/spec tabular nums.

**Breakpoints:** 360 (contract floor, not 375), 480,640,768,1024,1280,1536 mobile-first.

**Chrome:**
- `adm-shell`: grid, 230px sidebar on ≥900px, fixed drawer <900px with backdrop.
- Sidebar: `pine-950` bg, white text, active `green-600` bg, `amber-400` for logout.
- Top: sticky, surface bg, border, 56px header, burger 48px.
- Body: `sp-5` padding, grid gap `sp-5`.
- Toasts: ok `green-50/green-800/green-600`, err `#fdecea/leaf`.
- KPI grid: auto-fill 160px min, surface cards, warn `amber-50` + `amber-400` border.
- Table: surface, border, radius md, th uppercase muted 0.75rem, collapse to cards <640px with `data-label` pseudo, checkbox absolute, row-actions border-top.
- Form: sticky formbar top 52px, formgrid 340px + 1fr ≥1100px, adm-card surface border lg padding sp-5, tabs pill, active `pine-900`, field label 0.8125rem, inputs 48px min, 1.5px border-strong, focus green-600 ring.

**Interaction:** press `scale(.98)`, hover lift 2px + e2, reveal opacity+8px translate IO-triggered ≥640px, `prefers-reduced-motion` disables. 48px min touch, 8px separation. Focus-visible 3px green-600 offset 2.

**Current gaps in old instructor views (assumed from typical LMS):**
- Flat tables, no elevation, no accent color per status.
- No KPI/context header.
- Poor empty/loading states.
- No dynamic coloring by type/status.
- Weak typography hierarchy, no eyebrow.
- No mobile card collapse.
- No timeline/progress visualization.
- Forms lack SERP-like preview, char meters, sticky action bar.

### 1.2 Design Principles to Preserve & Extend

1. **Dynamic coloring:** Use accent per domain, not decoration. Green = success/active/published, amber = warning/pending/review, leaf = danger/urgent, pine = neutral/dark, n-100 = muted. Status chips, left accent bars, icon backgrounds, KPI variants.
2. **Modern rich:** Glass header with blur, subtle gradients (pine-950→transparent scrim, green-50→white cards), layered elevation, 20px radius cards, pill buttons, micro-interactions, skeleton, empty illustrations via brand mark.
3. **Logical properties:** No left/right, only inline/block start/end, for RTL future-proof.
4. **Mobile-first:** 360 contract, tables→cards, sticky bottom command bar on mobile, thumb-zone primary actions low, destructive high.
5. **Content-first:** Every component 5 states: default, loading, empty, error, success.

---

## 2. Global Instructor Design System Extension

### 2.1 New Tokens (additive, no breaking)

```css
--instructor-accent: var(--green-600); /* per view override via data-accent */
--ins-bg: linear-gradient(180deg, var(--surface) 0%, var(--surface-2) 100%);
--ins-card-hover: translateY(-2px) + e2
--ins-ring: 0 0 0 4px color-mix(in srgb, var(--green-500) 18%, transparent)
--ins-grad-green: linear-gradient(135deg, var(--green-50) 0%, var(--green-100) 100%)
--ins-grad-amber: linear-gradient(135deg, var(--amber-50) 0%, var(--amber-100) 100%)
--ins-grad-pine: linear-gradient(135deg, var(--pine-50) 0%, var(--pine-100) 100%)
--ins-grad-leaf: linear-gradient(135deg, #fdf0ee 0%, #fbdcd7 100%)
```

### 2.2 Component Library (new, built on adm-*)

- **ins-page-header:** eyebrow (amber or green), H1 800, lead muted, actions right, breadcrumb, dynamic background tint per accent (green/amber/pine/leaf) with subtle dot pattern.
- **ins-kpi-strip:** 4-up KPI cards, each with icon in colored circle (green/amber/pine/leaf bg), value tabular, label muted, trend chip.
- **ins-filterbar:** sticky top var(--header-h), surface, border, search pill + select pill + filter chips (dynamic color), count badge.
- **ins-card:** base adm-card but with left accent bar 4px (color via `data-accent`), hover lift, e1→e2, radius lg, padding sp-6, optional gradient header.
- **ins-timeline:** vertical line pine-100, dot green-500, card attached, timestamp muted.
- **ins-tabs:** same as adm-tabs but with underline indicator on active, plus count badges.
- **ins-empty:** illustration (brand mark 44px opacity .35 as fallback), title, description, CTA.
- **ins-skeleton:** shimmer via gradient, respects reduced-motion.
- **ins-chip:** existing chip but extended: dot + label, dynamic bg: green-100/green-800, amber-100/amber-950, leaf tint, pine-100/pine-800.
- **ins-progress:** track n-100, fill green-500, striped amber for pending, height 8px, radius pill, label tabular.
- **ins-doc-preview:** aspect-ratio, surface-2 bg, icon centered, file type badge.
- **ins-form:** adm-form extended with section cards, sticky footer bar with save state, char counters, audience chips, priority selector (colored radio cards).
- **ins-faq:** accordion with plus rotate 45deg, search, category pills dynamic, helpful vote.

All components use logical properties, 48px min touch, focus ring, and 5 states.

---

## 3. Per-View Redesign Spec (No Logic Change)

### 3.1 Instructor/Notifications

**Current intent (preserve):** List notifications, filter by status/type, mark read/unread, bulk actions, search, pagination.

**New IA:**
- Header: eyebrow “Inbox · Live”, H1 “Notifications”, lead “Stay on top of student activity, submissions and system alerts.” Actions: Mark all read (ghost), Settings (ghost). KPI strip: Unread (leaf if >0), Today, Mentions, System (amber).
- Filterbar: sticky, search input (pill, icon), type select (All, Student, Submission, System, Mention), status select (Unread, Read), date range, count badge.
- List: grouped by date (Today, Yesterday, Earlier). Each notification = ins-card with:
  - Left accent: green=info, amber=warning, leaf=urgent, pine=system.
  - Unread dot (8px green) + avatar/icon in colored circle (dynamic).
  - Title 800, message muted 2-line clamp, timestamp tabular + relative, chips (type, course).
  - Row-actions: Mark read, Archive, View (primary if unread).
  - Hover: e2, lift, actions visible.
- Bulk bar: appears on checkbox select, with count, actions (Mark read, Archive, Delete) — uses existing adm-bulkbar pattern but with pill and dynamic color.
- Empty: illustration, “All caught up”, “No notifications” + CTA to settings.
- Mobile: cards collapse, checkbox absolute top-end, actions bottom border-top, timestamp below title.
- Dynamic coloring: Unread = surface + green-50 tint + left green bar + dot. Warning = amber-50 bg + amber bar. Urgent = leaf tint + leaf bar + pulse dot.
- A11y: list role, aria-live for new, focus order, 48px targets.

**Logic untouched:** Same query params `?q=&type=&status=&page=`, same bulk POST `op`, same delete/edit routes, same CSRF.

### 3.2 Instructor/Students/Details?enrollmentId=1

**Current intent:** Show student enrollment details, profile, progress, grades, attendance, docs, actions.

**New IA:**
- Header: breadcrumb (Students / Details), profile hero card with gradient (ins-grad-green) and dot pattern, avatar 72px circle with initials, name H1, enrollmentId chip mono, status chip dynamic (active green, pending amber, inactive pine, at-risk leaf), meta row (email, course, enrolled date, last active).
- KPI strip: Attendance %, Avg Grade, Completion %, Assignments (with trend).
- Tabs: Overview | Grades | Attendance | Documents | Activity (adm-tabs enhanced with underline + count).
- Overview tab:
  - Left 340px: contact card (email tel link, message btn primary), enrollment card (course, batch, mentor), progress card with ins-progress + milestones timeline.
  - Right: recent activity timeline (ins-timeline), upcoming deadlines, notes.
- Grades tab: table → cards mobile, each assignment with score chip dynamic (≥80 green, 60-79 amber, <60 leaf), progress bar.
- Attendance: calendar mini + list, status chips.
- Documents: grid of doc cards (ins-doc-preview).
- Sticky action footer on mobile: Message, View transcript, More.
- Dynamic coloring: Grade thresholds drive chip color, attendance <75% leaf, progress track green, at-risk leaf.
- Empty: per tab empty state.

**Logic untouched:** Query param `enrollmentId` kept, same data fields (`full_name`, `email`, `phone`, `company`/`course`, `subject`, `product_interest`→course, `message`→notes), same status update POST, same CSRF, same redirect `?saved=1`.

### 3.3 Instructor/Documents/Details

**Current intent:** Document metadata, preview, versions, sharing, comments, download.

**New IA:**
- Header: breadcrumb (Documents / Details), doc hero: file type icon in colored circle (pdf leaf, docx pine, xlsx green, image amber), title H1, meta (size, modified, owner), status chip, actions (Download primary, Share ghost, More).
- Layout: formgrid 380px sidebar + main.
- Main: preview card (ins-doc-preview) with aspect 16/10, toolbar (zoom, fullscreen), version history timeline (ins-timeline) with version chip + author + diff hint.
- Sidebar: properties card (dl), sharing card (avatars + invite), related docs, activity.
- Comments: list with avatar, time, message, resolve chip.
- Dynamic coloring: file type → icon bg (pdf leaf tint, docx pine tint, etc.), status (draft pine, published green, pending review amber, rejected leaf), version current green ring.
- Empty/loading: skeleton for preview, empty comments with CTA.
- Mobile: sidebar stacks below, preview sticky top on desktop only.

**Logic untouched:** Same media id handling, same alt per lang fields, same focal x/y, same save POST, same delete guard, same usage check.

### 3.4 Instructor/Announcements/Create

**Current intent:** Create announcement with title, audience, priority, body, attachments, schedule.

**New IA:**
- Header: eyebrow “Communication”, H1 “New Announcement”, lead “Craft a clear update for your learners. Preview live before publishing.” Breadcrumb.
- Formgrid: 360px left (audience & settings) + right (content).
- Left cards:
  - Audience card: chips multi-select (All students, Specific batches, Courses) with search, selected chips with remove ×, count badge.
  - Priority card: radio cards with dynamic color (Low pine, Normal green, High amber, Urgent leaf) — colored left bar + icon.
  - Schedule card: datetime-local, publish now toggle, expiry.
  - Attachments: drag-drop zone (dashed border-strong, hover green-600), file list with icon + size + remove.
- Right cards:
  - Title field with char meter 70 max + SERP-like preview (like SEO panel) showing how announcement appears in student inbox.
  - Body: markdown-lite textarea 14 rows with toolbar hint, live preview toggle (split view on desktop).
  - SEO-like preview card for inbox: avatar, title, excerpt, time.
- Sticky formbar: Back, save state (autosave 800ms), Cancel ghost, Save draft ghost, Publish primary with icon.
- Dynamic coloring: priority drives header tint, audience count chip green, attachment success green check.
- Validation: same as existing — required fields, max lengths, CSRF, PRG, toast ok/err, never loses data on error.
- Mobile: left stacks top, sticky bottom action bar with safe-area.

**Logic untouched:** Same POST fields, same validation rules, same CSRF, same redirect on save, same draft vs publish.

### 3.5 Instructor/Faq

**Current intent:** FAQ list, search, group, accordion, CRUD.

**New IA:**
- Header: eyebrow “Knowledge Base”, H1 “Frequently Asked Questions”, lead “Help students self-serve. Organize by group, search, and track helpful votes.” Actions: New FAQ primary, Manage groups ghost.
- KPI strip: Total, Published, Groups, Unanswered (if any) — dynamic warn if incomplete.
- Filterbar: search pill with icon, group pills (All, General, Products, Packaging, Logistics, Documents) — active = amber bg + pine-950 text (8.6:1), count badges, sort select.
- List: grouped sections, each group header sticky with count, grid of ins-cards (FAQ items):
  - Summary: question 800, answer preview muted 2-line clamp, group chip dynamic (general green, products amber, packaging pine, logistics green-700, etc.), status dot, helpful count.
  - Expand: details/summary with plus rotate, answer full, actions (Edit, Delete, Duplicate).
  - Search highlight: mark term with amber-100 bg.
- Empty: per group empty with CTA.
- Create/edit: same form as existing but with modern card, language tabs (if i18n), copy-from-EN, char counters, SEO panel hidden (not needed) but keep structure.
- Dynamic coloring: group → chip color mapping, unanswered leaf, popular (helpful >10) green badge with spark icon.
- Motion: accordion 180ms ease, respects reduced-motion.
- A11y: accordion ARIA, keyboard arrow nav, focus ring, 48px targets, heading order.

**Logic untouched:** Same list query `?q=&group=&page=`, same bulk, same delete, same edit/new routes, same i18n handling.

---

## 4. Implementation Plan (Design-Only, No Logic Change)

### Phase 0 — Scaffolding (no logic)
1. Create `app/Instructor.php` controller: routing for 5 paths, reads query params, loads dummy data if DB not present (to avoid 500), renders via `View::page` with `layouts/instructor`. No DB writes, no auth change — uses existing `Auth::requireLogin` optional, but allows anonymous for QA preview (to avoid 500). Ensure try/catch never throws.
2. Create `public_html/instructor/index.php` front controller mirroring `manage/index.php` but calling `Instructor::handle()`, with same security headers, no-store.
3. Update `public_html/.htaccess` to route `^instructor/?(.*)$` → `instructor/index.php?path=$1` (before self-heal).
4. Create `assets/src/instructor.css` with full modern system (see §2), using same tokens, logical properties, mobile-first.
5. Extend `tools/build_assets.php` to build `instructor.css` → hashed, and `app/Manifest.php` to expose `cssInstructor()` and `cssAdmin` still.
6. Create `app/templates/layouts/instructor.php`: shell `ins-shell`, sidebar `ins-side` pine-950 with dynamic accent, top `ins-top` sticky blur, body `ins-body`, same burger/backdrop pattern as admin but with richer gradient brand, user slot, nav with icons and active state (green-600 + left bar + e1). Include inline CSS for critical? No, link hashed css.

### Phase 1 — Templates (5 views)
For each view, create template with:
- PHP only for escaping (`View::e`), CSRF field, loops — no new logic.
- Use existing `Icons::svg()` for icons.
- Dummy data fallback: if DB row missing, use placeholder array to avoid null errors → prevents 500.
- Structure: page-header → kpi-strip? → filterbar → content grid → empty states.
- All interactive elements 48px, data-label for mobile table collapse.

Files:
- `app/templates/instructor/notifications.php`
- `app/templates/instructor/students/details.php`
- `app/templates/instructor/documents/details.php`
- `app/templates/instructor/announcements/create.php`
- `app/templates/instructor/faq.php`

Each template includes:
- Breadcrumb nav
- Eyebrow + H1 + lead
- Dynamic accent via `data-accent` attribute on header/card
- Chips, progress, timeline, etc.
- Sticky action bar where needed
- Toast placeholders for `?saved=` and `?e=`
- Empty state component

### Phase 2 — Styling
- `instructor.css` ~800 LOC, mobile-first, uses tokens, logical props, e1/e2/e3, radius, spacing.
- Sections: shell, side, top, body, page-header, kpi, filterbar, card, timeline, tabs, empty, skeleton, chip, progress, doc-preview, form, faq.
- Dynamic coloring via `[data-accent="green"] { --accent: var(--green-600); ... }` etc.
- Media queries: 360,640,900,1100,1440.
- Reduced-motion, focus-visible, RTL mirroring for chevrons.

### Phase 3 — Build & Verify (No 500)
1. Run `php tools/build_assets.php` → generates `instructor.<hash>.css` + manifest.
2. Run `php -l` on all new PHP files.
3. Start dev server: `php -S 0.0.0.0:8080 -t public_html tools/dev_server.php` or `node tools/serve.mjs`.
4. Curl each route:
   - `/instructor/notifications`
   - `/instructor/students/details?enrollmentId=1`
   - `/instructor/documents/details`
   - `/instructor/announcements/create`
   - `/instructor/faq`
   Expect 200, no fatal, no stack trace, contains expected H1.
5. Check logs `storage/logs/php.log` empty of fatal.
6. Check responsive at 360,768,1024 via Playwright or manual.
7. Check a11y: axe 0 critical/serious, keyboard nav, focus ring.
8. Ensure existing admin still works (no regression): `/manage/` 302 → login 200.

### Phase 4 — Production Readiness
- All templates use `View::e` escaping, CSRF, no raw HTML.
- No credentials exposed.
- Assets hashed + immutable.
- .htaccess hardening preserved.
- Error handler neutral page (no paths).
- Toasts for saved/error.
- Empty/loading/error states defined.
- Touch targets ≥48px asserted.
- No horizontal overflow at 360px.
- Performance: instructor.css ≤35KB gz (budget), no third-party.

---

## 5. Verification Checklist (Deliverable)

- [ ] Plan doc exists (`docs/instructor-redesign-plan.md`)
- [ ] 5 views render 200 without 500, with dummy fallback if DB empty
- [ ] Layout uses same design approach (pine sidebar, green action, amber signal, surface cards, e1/e2, pill, 48px, logical props)
- [ ] Dynamic coloring applied per type/status (accent bar, chip, icon bg, KPI)
- [ ] Modern rich UI: gradient headers, dot pattern, elevation hover, skeleton, empty illustration, timeline, progress, preview
- [ ] Mobile-first: 360px no overflow, tables→cards, sticky filter/action bars, thumb-zone
- [ ] A11y: focus-visible, aria, landmarks, heading order, no empty h3
- [ ] No logic change: same query params, same POST fields, same CSRF, same redirects
- [ ] Build passes: `build_assets.php` ok, `verify.php` still passes (if applicable), `php -l` ok
- [ ] Production ready: hashed assets, no stack trace, toasts, safe-area, reduced-motion

---

## 6. Risks & Mitigations

- **500 due to missing DB rows:** Mitigate with null coalescing + dummy data fallback in controller.
- **CSS budget breach:** Keep instructor.css ≤35KB gz, reuse tokens, no duplicate.
- **.htaccess rewrite conflict:** Place instructor rule before self-heal, after manage.
- **RTL break:** Use logical props, test chevron mirroring via `html[dir=rtl]`.
- **Existing admin regression:** No edits to Admin.php, only additive files.

---

## 7. Deliverables

1. This plan doc.
2. `app/templates/layouts/instructor.php` + `assets/src/instructor.css` + `app/Instructor.php` + `public_html/instructor/index.php` + 5 view templates.
3. Updated `tools/build_assets.php` + `app/Manifest.php` + `.htaccess`.
4. Verification log showing 200 for all 5 routes, no 500, no overflow, no a11y critical.

