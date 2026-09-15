#!/usr/bin/env bash
#
# Nile-Maple build pipeline (doc 04 §9, doc 07 §2). One entry point for the whole chain so a
# rebuild is never "the three commands I run by hand".
#
#   tools/build.sh              assets → brand (if missing) → full static rebuild → verify
#   tools/build.sh --rebuild    rebuild the static site only
#   tools/build.sh --assets     rebuild CSS/JS/fonts/manifest only
#   tools/build.sh --verify     run the deploy gate (tools/verify.php)
#   tools/build.sh --bootstrap  fresh database: extract → migrate --fresh → seed → seed_seo → hero
#                               → images → brand → assets → full rebuild → verify (DESTRUCTIVE: wipes content)
#   tools/build.sh --lint       php -l over every shipped file
#
# PHP is auto-detected: a system `php` when present, otherwise the php-wasm CLI used in this
# sandbox (see DEPLOY.md §7). Override with PHP_BIN=/path/to/php or PHP_WASM_CLI=/path/to/php-wasm-cli.
set -euo pipefail
cd "$(dirname "$0")/.."

PHP_BIN="${PHP_BIN:-}"
PHP_WASM_CLI="${PHP_WASM_CLI:-}"
if [ -z "$PHP_BIN" ] && command -v php >/dev/null 2>&1; then PHP_BIN="php"; fi
if [ -z "$PHP_BIN" ] && [ -z "$PHP_WASM_CLI" ]; then
  for c in tools/harness/node_modules/.bin/php-wasm-cli /home/user/phpruntime/node_modules/.bin/php-wasm-cli; do
    [ -f "$c" ] && PHP_WASM_CLI="$c" && break
  done
fi
if [ -z "$PHP_BIN" ] && [ -z "$PHP_WASM_CLI" ]; then
  echo "build.sh: no PHP found. Install PHP 8.x, or set PHP_BIN / PHP_WASM_CLI." >&2
  exit 1
fi

php_run() { # php_run script [args…]
  local script="$1"; shift
  printf '\n\033[1m── %s %s\033[0m\n' "$script" "${*:-}"
  if [ "$PHP_BIN" = "php" ]; then php "$script" "$@"
  else node "$PHP_WASM_CLI" -f "$script" -- "$@"; fi
}

lint() {
  printf '\n\033[1m── php -l\033[0m\n'
  local bad=0 f
  for f in $(find app public_html config tools -name '*.php' 2>/dev/null | grep -v '/harness/' | sort); do
    if [ "$PHP_BIN" = "php" ]; then
      php -l "$f" >/dev/null 2>&1 || { echo "  syntax error: $f"; bad=1; }
    else
      node "$PHP_WASM_CLI" -l "$f" >/dev/null 2>&1 || { echo "  syntax error: $f"; bad=1; }
    fi
  done
  [ "$bad" = 0 ] && echo "  all files parse" || { echo "build.sh: lint failed"; exit 1; }
}

extract() {
  if command -v python3 >/dev/null 2>&1; then
    printf '\n\033[1m── tools/extract_content.py\033[0m\n'
    python3 tools/extract_content.py --media
  else
    echo "python3 not found — skipping extract (docs/data/content-manifest.json is committed)"
  fi
}

do_assets=0; do_brand=0; do_rebuild=0; do_verify=0; do_lint=0; do_bootstrap=0
case "${1:-}" in
  "")            do_assets=1; do_brand=1; do_rebuild=1; do_verify=1 ;;
  --assets)      do_assets=1 ;;
  --rebuild)     do_rebuild=1 ;;
  --verify)      do_verify=1 ;;
  --lint)        do_lint=1 ;;
  --bootstrap)   do_bootstrap=1; do_assets=1; do_brand=1; do_rebuild=1; do_verify=1 ;;
  -h|--help)     sed -n '2,17p' "$0" | sed 's/^# \{0,1\}//'; exit 0 ;;
  *) echo "build.sh: unknown option '$1' (try --help)" >&2; exit 2 ;;
esac

start=$(date +%s)
[ "$do_lint" = 1 ] && lint
if [ "$do_bootstrap" = 1 ]; then
  echo "!! --bootstrap recreates the database and re-runs the image pipeline"
  extract
  php_run tools/migrate.php --fresh
  php_run tools/seed.php
  php_run tools/seed_seo.php
  if [ -x "$(command -v node)" ] && [ -d tools/harness/node_modules ]; then
    printf '\n\033[1m── tools/build_hero.mjs\033[0m\n'
    node tools/build_hero.mjs
  else
    echo 'tools/build.sh: node/sharp not available — skipping hero collage composition' \
         '(the hero renders without an image until tools/build_hero.mjs is run once)'
  fi
  php_run tools/build_images.php
fi
[ "$do_assets" = 1 ] && php_run tools/build_assets.php
if [ "$do_brand" = 1 ] && [ ! -f public_html/assets/brand/logo-mark.webp ]; then
  php_run tools/build_brand.php
fi
[ "$do_rebuild" = 1 ] && php_run tools/rebuild.php -- full
[ "$do_verify" = 1 ] && php_run tools/verify.php
echo
echo "build.sh: done in $(( $(date +%s) - start ))s"
