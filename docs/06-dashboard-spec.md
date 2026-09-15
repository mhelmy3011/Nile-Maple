# 06 — Admin Dashboard Specification ("Nile-Maple Control Room")

Goal: the client edits **every** dynamic datum smoothly, in 3 languages, without developer help,
on shared hosting, at vanilla-PHP cost. Design principle: **server-rendered screens + ≤ 15 KB of
dashboard JS** (autosave, reorder, media picker, SERP preview) — no SPA, no admin framework, instant
loads even on the client's office connection.

URL root: `/manage/` (D-12). Admin UI language: English (operators publish *into* AR/FR via the
translation tabs; UI chrome stays EN to keep one tested interface).

## 1. Information architecture

```
/manage/
├─ login · logout · 2fa
├─ dashboard            KPI cards: enquiries (7/30 d), publish completeness per language,
│                       CWV snapshot link, build jobs, storage, quick actions
├─ content/
│  ├─ categories        list · new · {id}/edit · reorder (drag) · bulk publish
│  ├─ products          list (filters: category, status, lang-completeness, media-flag, q)
│  │                    · new · {id}/edit · bulk (publish/unpublish/move category/feature)
│  │                    · import (seed from docs/data/content-manifest.json) · export CSV
│  ├─ services          list · edit · reorder
│  ├─ posts             list (status tabs) · new · {id}/edit · preview per lang
│  ├─ faqs              grouped list · drag within group · edit
│  └─ blocks            zone tree (home/about/quality/packaging/seasonal/docs/privacy/terms) · edit
├─ media                library · upload · {id}/edit (alt per lang, focal, variants, usage)
├─ enquiries            inbox · {id} · statuses · export · mail log
├─ seo/
│  ├─ index             completeness board: every entity × 3 langs missing title/desc/slug
│  ├─ globals           title/desc templates, OG defaults, robots defaults per lang
│  ├─ redirects         manager + tester
│  └─ sitemap           regenerate + download
├─ settings/
│  ├─ contact           email/phone/whatsapp/socials/hours/address + live header/footer preview
│  ├─ languages         glossary table (trade terms EN→AR→FR), language availability toggles
│  └─ system            maintenance mode, backup now, purge caches, rebuild static, event analytics
├─ users                list · new · roles · audit log
└─ audit                filterable log
```

## 2. Cross-cutting editor UX (the "smooth" contract)

| Behaviour | Spec |
|---|---|
| Language tabs | Every i18n form shows `EN · AR · FR` tabs; active tab badge shows fill %; **Copy from EN** button per field and per tab; AR tab renders RTL inside the fieldset |
| Completeness meter | Per entity: required fields × langs; ring 0–100 %; < 100 % blocks *publish* (draft allowed) with tooltip listing gaps |
| Save model | Sticky bottom action bar (Save · Save & view · Cancel) + `Ctrl/Cmd+S`; debounced **autosave draft** (800 ms) to `draft` columns; unsaved-changes guard; PRG pattern; toast confirmations |
| Slugs | Auto-generated from name (transliterated for AR), editable, live URL preview `https://nilemaple.com/{lang}/products/{slug}/`, uniqueness check on blur |
| SEO panel | Inside every entity form: title (60-char meter), description (160), **live SERP preview** (Google snippet, mobile + desktop), OG image picker + preview, robots, canonical override; inherits globals if empty |
| Media picker | Modal library (search, filter by category/unused, upload dropzone inline) returning id; shows variant sizes; alt required per lang before attach to published entity |
| Ordering | Drag-and-drop rows (fetch PATCH `/manage/api/order`), persisted `sort_order`; keyboard alternative (move up/down buttons) |
| Lists | 25/page, column sort, saved filters in URL, row quick-actions (view live per lang, duplicate, publish toggle), bulk bar on selection |
| Validation | Server-side authoritative + inline client hints; errors summarised at top with anchor links; never loses posted data on error |
| Preview | "View live" opens the *static* page per language in new tab; "Preview draft" renders via dynamic route with `?preview=token` (noindex) |
| Audit | Every write logs user, entity, before/after diff (JSON), IP hash; visible in /audit |
| A11y & mobile | Admin meets WCAG AA; all lists/forms usable on tablet & phone (client will edit from mobile) — tables collapse to cards < 640 px |

## 3. Entity field-level specs (dashboard forms)

### 3.1 Categories
`code` (locked after create) · `icon_key` (select from design-system set w/ preview) · `accent` ·
`cover_media` · `sort` · `published` | i18n: `name` `slug` `headline` `summary` `meta_title` `meta_description`.
List shows product count + 3 completeness dots. Deleting blocked if products exist (must move first).

### 3.2 Products
`category` · `sku` · `source_index` · `card_media` · `gallery[]` · `featured` · `published` · `sort` ·
`temp_min/temp_max/temp_unit/temp_note` (structured, DQ-04; renders as badge "3–8 °C") |
i18n: `name` `slug` `description` + 4 spec groups, each = `label` + `value`
(labels default from category schema: Varieties/Types|Available Forms, Export Handling|Processing & Handling,
Packing, Cold-Chain|Storage Guide — editable per product) + `meta_*`.
Extras: **shared-media warning** (DQ-01) · "Open catalogue source" link (`source_ref`) ·
duplicate detection on name/slug per lang · import wizard maps manifest JSON → rows (one-time, Phase 4).

### 3.3 Services
`code` `icon_key` `media` `sort` `published` | i18n `name` `slug` `teaser` `body` (markdown-lite)
`bullets[]` (repeatable rows, drag) `meta_*`.

### 3.4 Posts
`author_user` `cover_media` `status` `published_at` (scheduler: future = auto-publish via cron)
`reading_minutes` (auto-recomputed) | i18n `title` `slug` `excerpt` (auto from body, editable)
`body` (markdown-lite editor with toolbar + live preview) `meta_*`.

### 3.5 Blocks (About company / Home / supporting pages)
Zone tree navigator; per block: `kind` renderer —
`text` (eyebrow/title/rich), `list` (repeatable icon+title+text rows), `table` (column defs + rows editor),
`stat` (value+label pairs), `person` (name+role+mandate) | i18n mirror payload with copy-from-EN and
per-row completeness. Home hero slide editor includes media + CTA targets (picker of internal routes).

### 3.6 FAQs
`group` · `sort` · `published` | i18n `question` `answer` (rich-lite). Group manager inline (add/rename).

### 3.7 Contact info & settings
`contact.email/phone/whatsapp` (format-validated, `tel:`/`wa.me` preview links) · `social.instagram/facebook`
(URL-validated, handle extracted) · `hours` · `address` · `legal_note` · `quote_promise` · per-lang
`whatsapp_prefill_template` ("Hello Nile-Maple, I'd like a quote for {product}") · **live preview strip**
rendering topbar/header/command-bar/footer snippets with current values. Changing any of these triggers
full static rebuild (they appear on every page).

### 3.8 SEO data
Global per-lang templates: `{entity} | Nile-Maple` patterns, default OG image, robots default;
per-entity overrides (see §2 SEO panel); **completeness board** (entity × lang matrix, click → editor);
redirects manager (from/to/code, loop detection, test button); sitemap regen; robots.txt editor with
syntax validation; hreflang status per URL (3/3 or missing list).

## 4. Media manager

Upload (multi, drag-drop, progress) → server GD pipeline → variants matrix (doc 04 §4) as a queued job
(row status `processing→ready`) · grid with usage badges ("3 products, 1 post") · edit: alt per language
(required), focal point crosshair on preview, title, source_ref · **replace** keeps URL (new hash folder,
old purged after rebuild) · delete guarded by usage · storage statistics card · orphan finder.

## 5. Enquiry inbox

List: status chips (new/read/replied/closed), lang filter, date range, q-search · detail: all fields,
consent timestamp, ip hash, UA · actions: mark replied, **reply via mail client** (mailto with subject
prefill), internal note · export CSV (UTF-8 BOM for Excel AR) · delivery log (`mailed`, attempts, error) ·
digest: weekly mail summary to owner (opt-in setting).

## 6. Roles, security, sessions

`owner`: everything incl. users/settings/SEO globals/backups. `editor`: content + media + enquiries.
Auth per doc 04 §7 (argon2id, lockout, 2FA for owner, idle 30 min, session regen). Every admin route:
CSRF + role check + audit. `/manage/` rate-limited separately. Maintenance mode serves 503 + retry-after
to public, allows admin IPs.

## 7. Static rebuild integration

Save hooks enqueue scopes: product → `[product, category, category?pages, home(featured), sitemaps]`;
category → `[category, pages, home, sitemaps]`; post → `[post, blog index, home, sitemaps]`;
settings/blocks/faq/seo-globals → `full`. Builder runs post-response (fastcgi_finish_request) or via cron
`* * * * *` draining `build_jobs` if the host kills post-response work. Dashboard shows job queue with
durations; "Rebuild full" button with confirm + progress polling (≤ 60 s for ~700 pages on shared CPU,
benchmarked in Phase 1; if > 90 s, chunked cron drain is the fallback — already designed).

## 8. Dashboard non-goals
No WYSIWYG heavy editors, no page-builder, no plugin marketplace, no client-facing login, no payments.
