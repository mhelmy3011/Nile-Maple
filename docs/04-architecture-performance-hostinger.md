# 04 — Architecture, Performance Engineering & Hostinger Deployment

## 1. Hosting reality (constraints that shape everything)

Hostinger **shared** hosting = LiteSpeed web server, PHP 8.x (FPM/LSAPI), MariaDB, cron, SSH on
Business/Cloud tiers, `.htaccess` + `.user.ini` support, **no** Node at runtime, **no** Redis/Memcached,
**no** long-running workers, limited CPU seconds per process. Consequences:

1. Runtime PHP per page-view is the enemy → **public pages are pre-built static HTML** (D-01).
2. All heavy transforms (AVIF/WebP, font subsetting, CSS/JS minify, OG composition) happen **at build time**
   on the developer machine/CI, never per-request (D-06). Dashboard uploads use GD (present on Hostinger)
   for WebP/JPEG variants; AVIF for uploads only if `gd` reports AVIF support, else WebP-only (graceful).
3. Caching = **files on disk** + CDN edge. No daemons.
4. Concurrency safety = atomic `tmp+rename` writes, never partial HTML.

## 2. Architecture: *Static-Render public site + PHP control plane*

```
                        ┌────────────── CDN edge (Cloudflare free / Hostinger CDN) ──────────────┐
 Browser ──HTTPS/HTTP3──┤  html: s-maxage 3600 + stale-while-revalidate 86400                    │
                        │  assets: immutable 1y                                                 │
                        └───────────────────────────────┬───────────────────────────────────────┘
                                                        ▼
                                        LiteSpeed (public_html/)
        GET /en/products/orange/  ──►  en/products/orange/index.html      ← 0 PHP, 0 SQL  (99.9 % of traffic)
        GET /assets/*             ──►  hashed static files                ← immutable
        POST /api/enquiry         ──►  index.php → Api controller         ← PHP + SQL + SMTP
        GET  /api/more?…          ──►  index.php → fragment (load-more)   ← PHP + SQL (cached)
        ANY  /manage/*            ──►  manage/index.php (auth)            ← PHP + SQL  (admins only)
        GET  unknown-path         ──►  index.php → render from DB → WRITE static file → serve  (self-healing)
```

Dashboard write path: `save entity → invalidate file caches → enqueue BuildJob → (after response)
StaticBuilder re-renders affected pages atomically → purge CDN by URL`. Typical rebuild of one product
page + its category + sitemaps < 3 s.

### 2.1 Repository / server file tree

```
/home/<hpanel-user>/
├─ public_html/                     # ONLY web-reachable things
│  ├─ index.php                     # root: language 302 + dynamic router fallback + /api/*
│  ├─ .htaccess                     # rewrites, headers, caching, hardening
│  ├─ en/  ar/  fr/                 # GENERATED static pages: {path}/index.html
│  ├─ assets/
│  │  ├─ css/app.<h>.css  css/crit-<template>.<h>.css
│  │  ├─ js/base.<h>.js  js/<route>.<h>.js
│  │  ├─ fonts/nunito-var-latin.woff2  tajawal-{400,700,800}-arabic.woff2 …
│  │  ├─ brand/logo-{mark,en,ar}.svg  logo-mark-mono-light.svg  favicon.svg  apple-touch-icon.png
│  │  └─ media/{category}/{slug}-{320|480|640|800}.{avif|webp|jpg} + og/{slug}.jpg
│  └─ manage/index.php              # dashboard front controller
├─ app/                             # PHP source (NOT web-reachable)
│  ├─ bootstrap.php  Db.php  Router.php  View.php  Cache.php  I18n.php  Seo.php  Slug.php
│  ├─ StaticBuilder.php  Mailer.php  Auth.php  Csrf.php  Validator.php  ImageGd.php  Audit.php
│  ├─ controllers/{Public,Api}.php  controllers/Admin/{Auth,Dashboard,Categories,Products,Services,
│  │     Posts,Faqs,Blocks,Settings,Media,Seo,Enquiries,Users,Rebuild}.php
│  ├─ templates/layouts/{base,admin}.php  templates/ui/*.php  templates/pages/*.php
│  └─ lang/{en,ar,fr}.php
├─ config/config.php                # creds & settings — chmod 600, NEVER in git (.gitignore)
├─ cache/{data,lang,frag}/          # file caches (world-unreadable: outside webroot)
├─ storage/{logs,tmp,backups}/
├─ releases/prev/                   # rollback snapshot
└─ tools/{migrate.php,seed.php,rebuild.php,build_images.php,build_assets.sh,extract_content.py}
```

### 2.2 `.htaccess` contract (public_html)

```
Options -Indexes -MultiViews
RewriteEngine On
# 1 canonical host + https
RewriteCond %{HTTPS} !=on            [OR]
RewriteCond %{HTTP_HOST} ^www\.      [NC]
RewriteRule ^ https://nilemaple.com%{REQUEST_URI} [R=301,L]
# 2 root → language detector (cookie / Accept-Language → 302 /en|ar|fr/)
RewriteRule ^$ index.php?r=lang [L,QSA]
# 3 api + manage front controllers
RewriteRule ^api/(.*)$   index.php?r=api&path=$1 [L,QSA]
RewriteRule ^manage(/.*)?$ manage/index.php?path=$1 [L,QSA]
# 4 pretty static: /en/x/ already a dir; extensionless fallback
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{DOCUMENT_ROOT}%{REQUEST_URI}index.html -f
RewriteRule ^(.*/)$ $1index.html [L]
# 5 self-healing dynamic fallback
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php?r=render [L,QSA]

<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType text/html "access plus 0 seconds"
  ExpiresByType image/avif "access plus 1 year"
  ExpiresByType image/webp "access plus 1 year"
  ExpiresByType font/woff2 "access plus 1 year"
  ExpiresByType text/css "access plus 1 year"
  ExpiresByType application/javascript "access plus 1 year"
</IfModule>
<IfModule mod_headers.c>
  Header set X-Content-Type-Options nosniff
  Header set X-Frame-Options DENY
  Header set Referrer-Policy strict-origin-when-cross-origin
  Header set Permissions-Policy "camera=(), geolocation=(), microphone=(), payment=()"
  Header set Strict-Transport-Security "max-age=31536000; includeSubDomains"
  Header set Content-Security-Policy "default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self'; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'"
  <FilesMatch "\.(avif|webp|jpg|png|svg|woff2|css|js)$">
    Header set Cache-Control "public, max-age=31536000, immutable"
  </FilesMatch>
  <FilesMatch "index\.html$">
    Header set Cache-Control "public, max-age=0, must-revalidate, s-maxage=3600, stale-while-revalidate=86400"
  </FilesMatch>
</IfModule>
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/html text/css application/javascript image/svg+xml application/json
</IfModule>
AddType image/avif .avif
AddType image/webp .webp
# hardening: no PHP execution anywhere except the two front controllers
<FilesMatch "\.php$">
  Require all denied
</FilesMatch>
<FilesMatch "^(index\.php|manage/index\.php)$">
  Require all granted
</FilesMatch>
```
*(LiteSpeed honours all directives above; `mod_deflate` = LiteSpeed's compression module; Brotli is
enabled server-side on Hostinger for text types automatically.)*

## 3. Caching layers (ordered by hit-rate)

| Layer | What | TTL / invalidation |
|---|---|---|
| CDN edge | html + assets | html s-maxage 1 h + SWR 24 h; assets immutable; purge-by-URL on rebuild |
| LiteSpeed | static files, ETag/Last-Modified | n/a |
| Disk `cache/data/` | settings snapshot, nav model, category trees, featured lists, sitemap XML | invalidated on any admin save |
| Disk `cache/lang/` | compiled lang arrays | on lang file change (hash key) |
| Disk `cache/frag/` | load-more fragments keyed `cat|page|lang` | on category/product save |
| OPcache | PHP bytecode | Hostinger default; `opcache.validate_timestamps=0` in prod via `.user.ini` if permitted |
| Browser | assets 1 y immutable; html revalidate | — |

DB load on public traffic: **zero** for page views; 1–2 indexed queries only for `/api/more` fragments.

## 4. Image pipeline (build-time)

`tools/build_images.php` (GD/Imagick local): for every media row → widths **320/480/640/800** ×
**avif(q55)/webp(q78)/jpg(q82)** (jpg only 640/800) + `og/{slug}.jpg` 1200×630 focal-crop with brand panel.
Expected: 800w avif ≈ 25–40 KB (from 800×800 JPEG source), 480w avif ≈ 12–18 KB.
Markup contract (every image):

```html
<picture>
 <source type="image/avif" srcset="…-320.avif 320w, …-480.avif 480w, …-640.avif 640w, …-800.avif 800w" sizes="(min-width:1024px) 25vw, 50vw">
 <source type="image/webp" srcset="…" sizes="…">
 <img src="…-640.jpg" width="800" height="800" alt="{media_i18n.alt}" decoding="async" loading="lazy">
</picture>
```
LCP image (hero/first gallery): `loading="eager" fetchpriority="high"` + `<link rel="preload" as="image" imagesrcset="…" imagesizes="…">`.
Dashboard uploads: GD resize to the same matrix on upload (WebP+JPEG; AVIF if supported), focal point picker, alt per language required before publish.

## 5. Fonts & CSS/JS delivery

- Fonts: self-hosted woff2 with `unicode-range`; EN pages load Nunito variable latin (≤ 40 KB) only;
  AR pages load Tajawal 400/700/800 arabic (≤ 85 KB); FR adds tiny latin-ext 400/700. `font-display:swap`
  + metric-matched fallback faces (doc 02 §3) → **0 CLS**. Preload only above-fold weights.
- CSS: source → purge (template scan) → minify → `app.<h>.css` (≤ 28 KB gz) + per-template critical
  file inlined in `<head>` (≤ 9 KB); remainder `<link rel="preload" as="style" onload="this.rel='stylesheet'">`
  with `<noscript>` fallback. Logical properties only → **one CSS bundle for LTR+RTL**.
- JS: ES2020 modules, zero deps: `base.<h>.js` (header/sheet/command-bar/lang/reveal/counters/tabs/
  accordion/carousel-dots/clipboard/share ≈ 12 KB gz) + route extras (`listing.<h>.js` filter+load-more,
  `contact.<h>.js` validation, `post.<h>.js` progress/TOC ≈ 2–6 KB gz each), all `defer`.
  Progressive enhancement: every feature works with JS off (accordions = `<details>`, tabs = anchor links,
  load-more = plain link page 2).

## 6. Performance budgets (enforced in CI, doc 07)

| Metric | Target | Enforcement |
|---|---|---|
| TTFB origin (static) | < 150 ms | curl p75 in deploy check |
| TTFB via CDN | < 80 ms | CrUX/GSC + synthetic |
| LCP mobile 4G (Moto G4 class) | < 1.8 s | Lighthouse CI budget file |
| CLS | < 0.05 | LHCI + explicit dims audit |
| INP | < 200 ms | LHCI interaction trace |
| Home transfer weight | ≤ 550 KB | LHCI resource-summary |
| Product page transfer | ≤ 400 KB | LHCI |
| JS gz total per page | ≤ 45 KB | build assert |
| CSS gz total per page | ≤ 35 KB | build assert |
| Fonts per page | ≤ 120 KB | build assert |
| Third-party requests | **0** blocking (Turnstile lazy only) | CSP + LHCI |
| Requests home (first paint) | ≤ 12 | LHCI |

Extra wins: `content-visibility:auto` + `contain-intrinsic-size` on below-fold sections ·
`<link rel="speculationrules">` prerender of the 2 most-likely next pages (category chips) where supported ·
HTTP/3 + 0-RTT via CDN · no cookies on static asset domain paths · sessions started **only** for `/manage`
and form POST (cookie-less anonymous browsing) · `rel=preconnect` to nothing (everything first-party).

## 7. Security contract

- PDO + prepared statements everywhere; `View::e()` escaping on all output; rich-text fields pass a
  whitelist sanitizer (allow p/h2/h3/ul/ol/li/strong/em/a/span-lang/table only).
- CSRF token per session on every POST (admin + form); SameSite=Lax cookies; secure+httponly.
- Auth: `password_hash(ARGON2ID)` (bcrypt fallback), lockout 5 tries/15 min, session regenerate on login,
  idle timeout 30 min, optional TOTP 2FA for `owner`, IP allowlist option in config.
- Uploads: finfo mime whitelist (jpeg/png/webp), dimension & size caps, random names, stored outside
  webroot then published as variants only; no PHP in media dirs (htaccess denies).
- Enquiry form: honeypot field + min-time trap (3 s) + per-IP token-bucket rate limit (file-based,
  5/10 min) + optional Cloudflare Turnstile (lazy-loaded post-interaction) + consent required.
- Headers: CSP / HSTS / XFO / XCTO / Referrer / Permissions (see §2.2). No `eval`, no inline JS except
  JSON-LD + `nonce`-free tiny config object rendered as JSON in a `type="application/json"` script tag.
- Secrets: `config/config.php` above webroot, chmod 600, `.gitignore`d; SMTP creds never in templates/JS.
- Errors: `display_errors=Off`, log to `storage/logs/app.log` (rotated), generic 500 page.
- Dependencies: none at runtime (vanilla) → near-zero CVE surface; PHP version pinned to Hostinger's
  current 8.x; quarterly update check task.
- Backups: nightly cron → `storage/backups/db-{date}.sql.gz` (mysqldump) + weekly full-file snapshot to
  off-site (Hostinger backup + manual S3 pull); restore drill each quarter (doc 07).

## 8. Mail (enquiry → contact@nilemaple.com)

Minimal vanilla SMTP client (`Mailer.php`, socket + STARTTLS/TLS + AUTH) using the domain mailbox on
Hostinger; 5 s connect timeout; on failure row stays `mailed=0` and cron `*/5` retries (max 5 attempts,
then alert log). Message = branded HTML (trilingual template) + plain-text part + reply-to = visitor email.
Admin notification separate short mail. No third-party SaaS required (keeps 0 external requests & 0 cost).

## 9. Deployment pipeline

1. Work on branch `arena/01a0a234-nile-maple`; CI (or local `tools/build_assets.sh`) runs: extract content →
   build images → subset fonts → purge/minify CSS/JS → hash → `tools/seed.php` (first time) →
   `tools/rebuild.php full` into `build/public_html/`.
2. Upload: `rsync -az --delete --exclude cache --exclude storage --exclude config` over SSH
   (Business/Cloud) to a staging dir, then atomic `mv` swap; on SSH-less plans: hPanel zip upload + same swap.
3. Post-deploy: `tools/migrate.php` → `tools/rebuild.php full` (server-side, CLI) → purge CDN →
   smoke URLs (home/cat/product/contact in 3 langs, form POST test, admin login) → publish.
4. Rollback: `releases/prev/` swap back (< 2 min), DB migrations are backwards-compatible by policy.
5. Monitoring: external uptime ping 5 min; GSC + CrUX weekly review; error-log cron digest; enquiry
   delivery self-test cron (sends test mail weekly, alerts on failure).
