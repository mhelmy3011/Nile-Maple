# Instructor Views — Phase 2 Modern Redesign Plan
## Using Nile-Maple Dashboard Design System (Dynamic Coloring, Modern Rich UI/UX)
### Design-Only Round — No Logic/Functionality Changes

**Date:** 2026-09-17 (Phase 2)  
**Scope:** 4 views (new batch)
- `Instructor/CourseInstructors`
- `Instructor/Coupons/Create`
- `Instructor/WithdrawalRequests/Create`
- `Instructor/Earnings`

**Previous batch delivered:** Notifications, Students/Details, Documents/Details, Announcements/Create, Faq — all verified 200 no 500.

**Constraint:** 100% logic preservation. Only presentation layer changes. No model, controller logic, API contracts, validation, or data flow altered. Pure view/template + CSS enhancement.

---

## 1. Audit — Existing Design Approach (Same Source of Truth as Phase 1)

Tokens, chrome, components from Phase 1 plan remain valid:
- Palette green/amber/pine/leaf/neutral, semantic --surface, --text, --border, --action, --signal, --focus, --danger
- Elevation e1/e2/e3 green-tinted, radius sm/md/lg/pill, spacing 4-96, Nunito/Tajawal, breakpoints 360 contract, logical properties
- Chrome: ins-shell 280px sidebar ≥900px drawer <900px, sidebar pine-950 with radial gradients green 18% + amber 14% + dot pattern 24px, active nav green-600→700 gradient + left amber 4px bar + e1, top sticky blur 10px 84% surface, body max 1440 padding sp-5/sp-8
- Components: ins-page-header with gradient tint per accent + dot overlay, ins-kpi-strip 200px min, ins-filterbar sticky 56px pill search/select + count badge, ins-card left 4px accent + hover lift + e1→e2, ins-timeline vertical line n-200 dot 40px, ins-tabs underline, ins-empty brand mark fallback, ins-skeleton shimmer, ins-chip dot+label dynamic, ins-progress track n-100 fill gradient, ins-doc-preview, ins-form formgrid 360px+1fr, dropzone dashed, priority radio cards :has(input:checked), audience chips toggle green, char meters, live preview, ins-faq accordion plus rotate 45deg, search highlight amber-100

Phase 1 CSS `instructor.css` 20.5KB raw / 4.5KB gz — under 35KB budget, reusable.

**Gaps for Phase 2 views (typical LMS):**
- CourseInstructors: flat table, no role color, no avatar, no invite flow visualization
- Coupons/Create: basic inputs, no discount type color, no scope chips, no preview of savings
- WithdrawalRequests/Create: no balance KPI, no method cards, no fee breakdown
- Earnings: no KPI with trend, no chart placeholder, no course breakdown with dynamic coloring, no transaction timeline

---

## 2. Global Design System Extension (Phase 2 additive)

### 2.1 New tokens (reuse existing, no breaking)
- Earnings chart: bars use --green-500, --amber-400, --pine-600, --leaf-600 with opacity 12% track, 100% fill
- Coupon preview: savings badge uses --amber-100/amber-950 (8.6:1) + --green-100/green-800
- Withdrawal method: PayPal leaf tint, bank pine tint, wallet green tint — same as doc type mapping
- CourseInstructors role: owner green, co-instructor amber, assistant pine, pending leaf — same as student status mapping

### 2.2 Component extensions (built on ins-*)
- **ins-chart-bars:** flex align-end, bar width 12-20px, track n-100 radius pill, fill gradient per accent, height via inline style %, tooltip on hover, respects reduced-motion
- **ins-method-card:** radio card with icon circle 44px tinted per method, title 800, desc muted, fee chip, selected state border-color + bg tint via :has(input:checked)
- **ins-coupon-preview:** card with code mono, discount large 2rem 800, savings breakdown, usage progress, expiry chip
- **ins-earnings-breakdown:** grid of course cards with progress + amount, income green, pending amber, withdrawn pine
- **ins-avatar-stack:** overlapping avatars with +N badge, used in CourseInstructors

All use logical props, 48px min touch, focus ring, 5 states.

---

## 3. Per-View Redesign Spec (No Logic Change)

### 3.1 Instructor/CourseInstructors

**Intent preserve:** List/manage instructors for a course, add/remove, edit role, search, pagination. Query params ?courseId=&q=&page=, POST role, CSRF.

**New IA:**
- Header: breadcrumb Instructor / CourseInstructors, eyebrow “Team · Collaboration”, H1 “Course Instructors”, lead “Manage teaching team with role-based coloring and collaboration insights.” Actions: Invite instructor primary spark icon, Manage roles ghost. Accent amber (team).
- KPI strip: Total instructors, Active (green), Pending invites (amber), Courses co-taught (pine). Each with icon users/shield/clock/tag.
- Course selector: filterbar sticky with course select (from categories), search pill with icon, count badge, view toggle (grid/list).
- List: grid 2-up on ≥768px, each instructor ins-card data-accent per role:
  - Avatar 56px circle with initials gradient (green→700 for owner, amber for co-instructor, pine for assistant, leaf tint for pending), name 800, email muted, role chip dynamic (Owner green, Co-Instructor amber, Assistant pine, Pending leaf), courses chips, status dot, last active mono.
  - Role progress: courses taught count + students impacted.
  - Actions: Edit role, Message, Remove (leaf ghost). Hover e2 lift.
- Invite flow: card with dashed border, illustration users icon, title “Invite new instructor”, email input + role select + Invite button — same POST fields as before.
- Empty: illustration users, “No instructors yet”, CTA Invite.
- Mobile: cards collapse, avatar top, actions bottom border-top, role chip below name.
- Dynamic coloring: role drives left bar + icon bg + chip, pending gets leaf tint + pulse dot, owner gets green gradient avatar + amber left bar highlight.
- A11y: list role, avatar alt, 48px targets, focus ring.

Logic untouched: same courseId, q, page, same role POST, CSRF, redirect ?saved=1.

### 3.2 Instructor/Coupons/Create

**Intent preserve:** Create coupon with code, discount type (percent/fixed), value, course scope, usage limit, expiry, status. POST fields same.

**New IA:**
- Header: breadcrumb Instructor / Coupons / Create, eyebrow “Growth · Incentives”, H1 “Create Coupon”, lead “Craft a compelling offer with live savings preview and scope visualization.” Accent green (growth).
- Formgrid 360px left (settings) + right (content + preview).
- Left cards:
  - Code card: input mono with generate button, char meter, validation hint, status chip active/inactive.
  - Discount card: type radio cards with dynamic color (Percent green, Fixed amber) — icon percent/tag, value input with prefix %/$ and large font, savings calculation live.
  - Scope card: course chips multi-select (from categories) with search, selected chips removable, All courses toggle, count badge.
  - Limits card: usage limit number + unlimited toggle, expiry datetime-local, min purchase, max uses per user.
- Right cards:
  - Preview card: ins-coupon-preview with code mono large, discount 2rem 800, original price struck + discounted price green-700, savings badge amber-100, usage progress bar, expiry chip, QR placeholder (dot pattern).
  - Details: description textarea with char meter, terms, how student sees it (inbox mock).
  - Example: table of affected courses with price impact.
- Sticky formbar: Back, save state autosave 800ms, Cancel ghost, Save draft ghost, Create primary tag icon.
- Dynamic coloring: percent green, fixed amber, active green, expired leaf, limited amber, unlimited pine, scope count chip green.
- Validation: same required, max, CSRF, PRG, never loses data.

Logic untouched: same POST fields code/type/value/courseIds/limit/expiry, same validation.

### 3.3 Instructor/WithdrawalRequests/Create

**Intent preserve:** Create withdrawal request with amount, method (PayPal, bank, wallet), account details, earnings balance, fee, history. POST amount/method/account.

**New IA:**
- Header: breadcrumb Instructor / WithdrawalRequests / Create, eyebrow “Payouts · Secure”, H1 “Request Withdrawal”, lead “Withdraw your earnings securely with method-based coloring and fee transparency.” Accent pine (secure).
- KPI strip: Available balance (green large), Pending withdrawals (amber), Total withdrawn (pine), This month earnings (green trend).
- Formgrid 360px left (balance & methods) + right (amount & details).
- Left:
  - Balance card: gradient green-50→100, large tabular amount 2.2rem 800, available chip green, pending chip amber, info about minimum.
  - Method card: radio cards ins-method-card per method:
    - PayPal: icon leaf tint (PayPal blue approximated with pine? but use leaf for urgency? Actually use green for wallet, pine for bank, leaf for PayPal to differentiate) — title, desc, fee chip (e.g., 2% fee), selected border green-600 + bg green-50 / amber-50 / pine-50 per method.
    - Bank Transfer: pine tint, IBAN hint, 1-3 days chip
    - Wallet: green tint, instant chip
  - History: mini timeline of last 3 withdrawals with status chip (completed green, pending amber, failed leaf).
- Right:
  - Amount card: large input with $ prefix, tabular 1.6rem, available max hint, fee breakdown live (amount, fee, net), slider for quick 25%/50%/75%/100% with pill buttons.
  - Account details: dynamic per method — PayPal email, bank account/IBAN, wallet — with format validation hint, same fields as before.
  - Confirmation: checkbox terms, summary card with net payout large green-700.
- Sticky formbar: Back, save state, Cancel ghost, Request withdrawal primary with shield icon, shows net amount.
- Dynamic coloring: balance green, pending amber, method selected drives header tint, fee amber, net green-700, failed leaf, completed green.
- Empty: balance 0 empty with CTA to earnings.

Logic untouched: same amount/method/account fields, same validation, CSRF, redirect.

### 3.4 Instructor/Earnings

**Intent preserve:** Earnings dashboard with total, breakdown by course, transactions, filters date/course, pagination. Query ?from=&to=&courseId=&page=.

**New IA:**
- Header: breadcrumb Instructor / Earnings, eyebrow “Revenue · Insights”, H1 “Earnings”, lead “Track your revenue with dynamic coloring, course breakdown, and transaction timeline.” Accent green (revenue). Actions: Export CSV ghost, Request withdrawal primary.
- KPI strip: Total earnings (green gradient), This month (green + trend), Pending (amber), Available (green large), Withdrawn (pine). Each with icon chart/tag/clock/box.
- Chart: ins-chart-bars with 12 months or last 7 days, bars green-500 for income, amber-400 for pending, track n-100, height %, tooltip with amount, legend with chips. No external chart lib — pure CSS, respects reduced-motion (no animation).
- Breakdown: grid 2-up course cards with course name, earnings amount green-800, students count, progress bar completion, income vs pending split, sparkline mini bars.
- Transactions: filterbar sticky with date from/to, course select, status select (completed green, pending amber, refunded leaf), search, count, export. Table ins-table collapse to cards mobile with data-label, each row with course chip, amount green/amber/leaf per status, date mono, student avatar, status chip dynamic.
- Timeline: recent payouts timeline with status.
- Empty: no earnings empty with illustration chart + CTA to create course.
- Dynamic coloring: income green, pending amber, withdrawn pine, refunded/failed leaf, course accent per category (fresh-fruits green, vegetables green, frozen pine, processed amber).
- A11y: chart bars with aria-label, table with scope, 48px targets.

Logic untouched: same from/to/courseId/page query, same export, same data fields.

---

## 4. Implementation Plan (Design-Only)

### Phase 0 — Scaffolding (no logic)
1. Update `app/Instructor.php`: add 4 new methods pgCourseInstructors, pgCouponCreate, pgWithdrawalCreate, pgEarnings with dummy fallbacks to prevent 500, reuse existing Db calls with try/catch. Add routing for courseinstructors, coupons/create, withdrawalrequests/create, earnings (case-insensitive, with/without slash).
2. Update `app/templates/layouts/instructor.php` nav to include 9 total items (5 old + 4 new) with sections Teaching / Growth / Payouts / Platform, icons search/tag/shield/chart, accent per view.
3. Extend `assets/src/instructor.css` with chart-bars, method-card, coupon-preview, earnings-breakdown, avatar-stack — keep under 35KB gz (currently 4.5KB gz, budget 35KB, plenty headroom).
4. Build assets via php-wasm-cli.
5. Create 4 new templates:
   - `instructor/courseinstructors.php`
   - `instructor/coupons/create.php`
   - `instructor/withdrawalrequests/create.php`
   - `instructor/earnings.php`
   Each with breadcrumb, eyebrow+H1+lead, KPI strip, filterbar, ins-card with data-accent, empty states, toast placeholders, CSRF, View::e escaping, 48px targets, data-label.

### Phase 1 — Templates detail (per view, UI only)
- CourseInstructors: course select, search, grid of instructor cards with avatar 56px gradient per role, role chip dynamic, courses chips, actions Edit/Message/Remove, invite dashed card with email+role+Invite.
- Coupons/Create: formgrid left Code/Discount/Scope/Limits cards with radio cards percent green fixed amber, scope chips, limits; right Preview card code mono + discount 2rem + savings badge + usage progress + expiry + QR dot pattern + affected courses table; sticky formbar.
- WithdrawalRequests/Create: KPI Available/Pending/Withdrawn/This month, balance card gradient green, method radio cards PayPal leaf/Bank pine/Wallet green with fee chip, history timeline, amount card large input $ prefix + quick % pills + fee breakdown live, account dynamic fields, confirmation summary net green-700, sticky formbar with net.
- Earnings: KPI Total/This month/Pending/Available/Withdrawn, chart-bars 12 months with green/amber bars + legend, breakdown grid course cards with progress, transactions filterbar date/course/status + ins-table collapse to cards with status chip dynamic, timeline recent payouts.

### Phase 2 — Build & Verify (No 500)
1. `php -l` on all new PHP files
2. `build_assets.php --no-min` → instructor.<hash>.css + manifest
3. php-wasm render for each route: courseinstructors, coupons/create, withdrawalrequests/create, earnings — expect 200, no Something went wrong, has-header
4. Dev server curl :8085 for same routes → 200
5. Check logs storage/logs/php.log no fatal
6. Responsive at 360,768,1024 — no overflow, tables→cards, sticky bars
7. A11y: axe 0 critical/serious, keyboard, focus ring
8. Existing 5 old views still 200 — no regression

### Phase 3 — Production Readiness
- All templates View::e, CSRF, no raw HTML, no credentials
- Hashed immutable assets
- .htaccess hardening preserved
- Neutral error page
- Toasts, safe-area, reduced-motion, 48px targets, logical props
- instructor.css ≤35KB gz

---

## 5. Verification Checklist

- [ ] Plan doc exists docs/instructor-redesign-plan-phase2.md
- [ ] 4 new views render 200 without 500, dummy fallback if DB empty
- [ ] Layout nav includes 9 items with sections, active state green gradient + amber left bar
- [ ] Dynamic coloring per role/type/status/method/course
- [ ] Modern rich: gradients, dot pattern, glass blur, elevation hover, chart bars CSS, method cards, coupon preview, empty illustration, timeline, progress
- [ ] Mobile-first 360 no overflow, tables→cards, sticky filter/action bars
- [ ] A11y focus-visible, aria, landmarks, heading order
- [ ] No logic change: same query params courseId/q/page, code/type/value, amount/method, from/to/courseId, same POST fields, CSRF, PRG
- [ ] Build passes, lint ok, budgets ok
- [ ] Production ready

---

## 6. Risks & Mitigations

- 500 due to missing DB tables: dummy fallbacks + try/catch in controller
- CSS budget: current 4.5KB gz, adding ~5KB for chart/method/coupon still <10KB gz <<35KB
- Nav overflow: 9 items still fits 280px sidebar, sections + scroll, mobile drawer
- Chart without JS lib: pure CSS bars, no external dep, accessible via aria-label

---

## 7. Deliverables

1. This plan doc
2. Updated Instructor.php + layout + instructor.css + 4 new view templates
3. Updated manifest + verification log showing 200 for all 9 routes (5 old + 4 new), no 500
4. Production ready
