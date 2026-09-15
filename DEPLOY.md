# Nile-Maple — deployment & operations runbook

Target: **Hostinger Business/shared (LiteSpeed + MySQL/MariaDB + PHP 8.2+)**, domain `nilemaple.com`.
The public site is **static HTML** generated from the database; only `/manage/`, `/api/*` and the
self-heal fallback touch PHP (doc 04 §2). This file is the contract between "it builds here" and
"it is live and supportable there".

---

## 1. Directory layout (important)

Only `public_html/` is web-accessible. Everything else must sit **above** it:

```
/home/uXXXXXX/
├── app/                    PHP classes + templates + per-locale lang files
├── assets/                 source CSS/JS/fonts (compiled into public_html/assets by build_assets)
├── config/
│   ├── config.example.php  committed template
│   └── config.php          REAL config — chmod 600, never in git
├── cache/                  generated at runtime (data/, lang/, frag/)
├── storage/
│   ├── db.sqlite           local/dev only
│   ├── logs/php.log        error log (never displayed to visitors)
│   ├── mail/               transport=log drops (.eml)
│   └── backups/            dashboard JSON snapshots
├── tools/                  build/migrate/seed/rebuild/verify scripts
└── public_html/            ← webroot
    ├── index.php .htaccess .user.ini
    ├── manage/index.php    the dashboard entry point
    ├── en/ ar/ fr/          generated pages (index.html per directory)
    ├── assets/             hashed css/js, fonts, media/, brand/, manifest.json, .htaccess
    ├── robots.txt sitemap.xml sitemap-{en,ar,fr}.xml 404.html
```

`tools/deploy.sh` packages exactly this tree and never includes `config/config.php`, `storage/`
or `cache/`, so a deploy cannot overwrite credentials, uploads or the live database.

## 2. Host prerequisites

| Requirement | Notes |
|---|---|
| PHP 8.2 or 8.3 | `app/` uses readonly classes, first-class callable syntax. Set in hPanel → PHP Version. |
| Extensions | `pdo_mysql`, `mbstring`, `json`, `zlib`, `session`. For images: `gd` **with AVIF + WebP**, or `imagick`. |
| Memory / upload | `.user.ini` ships `memory_limit=128M`, `upload_max_filesize=8M`, `post_max_size=12M`, `max_execution_time=90`, `error_log=../storage/logs/php.log`. Raise `memory_limit` to 256M if you batch-import very large photography. |
| MySQL | 5.7+/MariaDB 10.4+, `utf8mb4_unicode_ci` (Arabic + French accents). |
| `.htaccess` honoured | LiteSpeed on Hostinger reads it; keep `AllowOverride All` (default there). |
| HTTPS + www redirect | Enable SSL in hPanel; `public_html/.htaccess` already 301s `http→https` and `www→apex`. |
| Cron (optional) | `php /home/uXXXXXX/tools/rebuild.php -- full` nightly if you prefer a scheduled rebuild; the dashboard already rebuilds on save. |

## 3. Database

```bash
php tools/migrate.php --check    # what exists / what is missing
php tools/migrate.php --fresh    # create every table (DROPS data — first install only)
php tools/migrate.php --sql      # print MySQL DDL, for phpMyAdmin import instead
```

`--sql` is the preferred route on shared hosting: hPanel → phpMyAdmin → import the dump, then
skip `migrate.php` entirely. Content baseline: `php tools/seed.php` (products + categories from
`docs/data/content-manifest.json`), then `php tools/build_images.php` (media rows + derivatives).
Both are idempotent for services/FAQs/blocks/posts/pages, but `seed.php` **replaces** products and
their `card_media_id`, so re-run `build_images.php` after it.

## 4. Configuration

```bash
cp config/config.example.php config/config.php
chmod 600 config/config.php
```

Set before the first request:

| Key | Production value |
|---|---|
| `env` | `'prod'` — switches on HSTS, suppressed error output, and turns the deploy warnings into failures |
| `base_url` | `https://nilemaple.com` (no trailing slash — it feeds canonicals, hreflang, sitemap) |
| `db.driver` | `'mysql'`; `db.mysql.{host,name,user,pass}` from hPanel |
| `mail.transport` | `'smtp'`; `mail.smtp.{host,port,tls,user,pass}` = the `contact@nilemaple.com` mailbox (hPanel → Email Accounts; SMTP `smtp.hostinger.com:465` TLS) |
| `mail.to` | `contact@nilemaple.com` (client-mandated recipient) |
| `admin.totp_secret` | base32 secret for owner 2FA (empty = off) |
| `admin.allow_ips` | optional dashboard allowlist |

`config/config.php` is auto-created from the example on first boot (and chmod 600 by
`app/bootstrap.php`) so a half-configured host fails loudly instead of showing PHP errors.

## 5. Deploy

**Build + package locally** (needs PHP with GD for image work; or run the same steps on the host):

```bash
./tools/build.sh                    # assets → brand (if missing) → full rebuild → verify gate
./tools/deploy.sh --no-media        # packages dist/nilemaple-<stamp>.tar.gz
```

**Ship it:**

```bash
HOST=uXXXXXX@nilemaple.com REMOTE_ROOT=/home/uXXXXXX ./tools/deploy.sh --push
# or: upload the tarball in hPanel → File Manager, extract in /home/uXXXXXX, then
ssh uXXXXXX@nilemaple.com 'cd /home/uXXXXXX && php tools/rebuild.php -- full && php tools/verify.php'
```

Media (42 MB, 1 431 derivatives) is the slow part. `--no-media` skips it; upload
`public_html/assets/media/` once over SFTP and re-run `deploy.sh --push` without the flag only when
images changed. Content edits made in the dashboard rebuild the affected pages on the spot.

Content **removed** in the dashboard leaves its old page behind (deploy never deletes). Use
`./tools/deploy.sh --push --prune`, or on the host:
`rm -rf public_html/{en,ar,fr} && php tools/rebuild.php -- full`.

## 6. Post-deploy gate

```bash
php tools/verify.php            # 83 checks: config hygiene, schema, content×locale, build, per-page SEO, assets, server rules
php tools/verify.php --links    # + crawl internal links of the built tree
```

`► READY TO DEPLOY` is required before calling a deploy done. Then in the browser:

1. `https://nilemaple.com/` → 302 to `/en/` (or `/ar/` for an Arabic browser), no cookie needed.
2. `/ar/` renders RTL with Arabic UI (title, nav, footer) — an English title there means the language
   cache is stale: `php tools/rebuild.php -- full` purges `cache/`.
3. Language switcher on any page keeps you on the equivalent page (`/fr/…` ↔ `/ar/…`).
4. Contact form: submit → thank-you state, `enquiries` row written, mail in the inbox;
   a 6th submit inside 10 minutes returns "too many requests" (429), a filled honeypot returns a fake
   success. No credentials in any response.
5. `/manage/` → login → edit a product → 302 `?saved=1`, page rebuilt in <5 s.
6. `curl -I https://nilemaple.com/assets/css/app.*.css` → `cache-control: public, max-age=31536000, immutable`.
7. `https://nilemaple.com/sitemap.xml` → index with three locale sitemaps; `/nope/` → the styled 404.

## 7. Local development & QA preview

```bash
node tools/serve.mjs                       # http://0.0.0.0:8080 — php-wasm bridge, .htaccess parity
PORT=9000 node tools/serve.mjs             # different port
php -S 0.0.0.8080 -t public_html tools/dev_server.php   # same contract with a system PHP
./tools/build.sh --lint                    # parse-check every file
```

`serve.mjs` serves generated files from disk and forwards everything else into the same front
controllers `.htaccess` would, so `/manage/`, `/api/*` and the self-heal path are testable locally.
It is dev-only tooling: the runtime is a Node dependency, never part of a deploy. Locally the
database is SQLite (`storage/db.sqlite`); `db.sqlite_wal => false` in `config/config.php` keeps the
WASM filesystem from dead-locking on shared memory. Default dev admin: `owner@nilemaple.com` /
`ChangeMe!2026` — **rotate it** (`/manage/users`); `verify.php` fails the build in prod if you don't.

## 8. Backups & restore drill

* hPanel → Backups: keep the daily/weekly snapshots; note the retention date in the launch log.
* Content snapshot: `/manage/settings/system` → **Backup** writes `storage/backups/db-<stamp>.json`
  (all editable content, per locale) before any bulk edit.
* Full dump: `mysqldump --single-transaction --default-character-set=utf8mb4 uXXXXXX_nilemaple > db.sql`
  (or phpMyAdmin → Export). `tools/migrate.php --sql` reproduces the schema from code if a table is lost.
* **Drill before launch and after any schema change** (doc 07 §3): on a staging subdomain, restore the
  dump, run `php tools/rebuild.php -- full && php tools/verify.php`, and confirm the count of built
  pages and the two last enquiries. A backup nobody has restored is not a backup.

## 9. Known gaps to close with the client

1. **Post cover images** reference `profile/imageN.jpg` from the company profile, which the extractor
   does not export. Upload them in `/manage/media` and set the post cover, or ship the profile's
   `word/media/*.jpg` and re-run `tools/build_images.php`.
2. **About page history/quality/progress** copy is the operating model restated (no invented dates or
   milestones). Replace with the profile's own milestone wording in `/manage/blocks` — zones
   `about:history`, `about:quality`, `about:progress` — then republish.
3. Social links (`instagram`, `facebook`) and `hours`/`address` come from `settings`; the dashboard
   Settings screen is the place to correct them — no code change, then `rebuild.php -- full`.

## 10. Rollback

Keep the previous tarball in `dist/`. Rollback = upload it and extract over the tree (config,
storage and cache are untouched), then `php tools/rebuild.php -- full`. Because the public site is
static, a rollback needs no PHP restart and cannot leave half-rendered pages: every page is
written `tmp + rename` (app/StaticBuilder.php), so a file is either the old page or the new one.
