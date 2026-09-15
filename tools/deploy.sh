#!/usr/bin/env bash
#
# Nile-Maple deploy packager for Hostinger (shared/LiteSpeed). See DEPLOY.md §4–§6.
#
#   ./tools/deploy.sh                      build, then write dist/nilemaple-<stamp>.tar.gz
#   ./tools/deploy.sh --skip-build         package what is already built
#   ./tools/deploy.sh --no-media           omit public_html/assets/media (42 MB of derivatives)
#   ./tools/deploy.sh --list               print the packaged file list and exit
#   HOST=u123456@nilemaple.com REMOTE_ROOT=/home/u123456 ./tools/deploy.sh --push
#
# What ships: app/ assets/src config/config.example.php public_html/ tools/*.php.
# What never ships: config/config.php (credentials), storage/, cache/, node_modules/, docs/.
# A deploy therefore cannot clobber the live database, the uploads or the real config — but it also
# will not delete pages for content you removed: use --prune for that (it wipes the language trees,
# then rebuilds them from the database).
set -euo pipefail
cd "$(dirname "$0")/.."

BUILD=1; MEDIA=1; PUSH=0; LIST=0; PRUNE=0
for a in "$@"; do
  case "$a" in
    --skip-build) BUILD=0 ;;
    --no-media)   MEDIA=0 ;;
    --push)       PUSH=1 ;;
    --list)       LIST=1 ;;
    --prune)      PRUNE=1 ;;
    -h|--help)    sed -n '2,16p' "$0" | sed 's/^# \{0,1\}//'; exit 0 ;;
    *) echo "deploy.sh: unknown option '$a' (try --help)" >&2; exit 2 ;;
  esac
done

[ "$BUILD" = 1 ] && ./tools/build.sh

STAMP=$(date +%Y%m%d-%H%M)
OUT="dist/nilemaple-$STAMP"
rm -rf "$OUT"; mkdir -p "$OUT"

# staged with the same layout the host uses (paths above public_html + the webroot itself)
mkdir -p "$OUT/app" "$OUT/config" "$OUT/tools" "$OUT/assets" "$OUT/public_html"
cp -R app/. "$OUT/app/"
cp -R assets/src "$OUT/assets/src"
cp config/config.example.php "$OUT/config/config.example.php"
for f in tools/*.php tools/serve.mjs tools/build.sh tools/deploy.sh; do [ -f "$f" ] && cp "$f" "$OUT/tools/"; done
cp public_html/index.php public_html/.htaccess public_html/.user.ini "$OUT/public_html/"
mkdir -p "$OUT/public_html/manage" && cp public_html/manage/index.php "$OUT/public_html/manage/"
cp -R public_html/assets "$OUT/public_html/assets"
[ "$MEDIA" = 0 ] && rm -rf "$OUT/public_html/assets/media"
for l in en ar fr; do [ -d "public_html/$l" ] && cp -R "public_html/$l" "$OUT/public_html/$l"; done
for f in robots.txt sitemap.xml sitemap-en.xml sitemap-ar.xml sitemap-fr.xml 404.html; do
  [ -f "public_html/$f" ] && cp "public_html/$f" "$OUT/public_html/$f"
done
[ -f docs/data/content-manifest.json ] && mkdir -p "$OUT/docs/data" && cp docs/data/content-manifest.json "$OUT/docs/data/"

if [ "$LIST" = 1 ]; then (cd "$OUT" && find . -type f | sort | sed 's|^\./||'); rm -rf "$OUT"; exit 0; fi

TARBALL="dist/nilemaple-$STAMP.tar.gz"
tar czf "$TARBALL" -C "$OUT" .
FILES=$(tar tzf "$TARBALL" | grep -cv '/$')
SIZE=$(du -h "$TARBALL" | cut -f1)
rm -rf "$OUT"
echo
echo "packed  $TARBALL · $FILES files · $SIZE"
echo "upload  scp $TARBALL \"\${HOST:-user@host}:~/\" && ssh \"\${HOST:-user@host}\" 'cd \$REMOTE_ROOT && tar xzf $(basename "$TARBALL") && rm -f $(basename "$TARBALL")'"

if [ "$PUSH" = 1 ]; then
  : "${HOST:?deploy.sh --push needs HOST=user@server}"
  : "${REMOTE_ROOT:?deploy.sh --push needs REMOTE_ROOT=/home/uXXXXXX}"
  echo "── pushing to $HOST:$REMOTE_ROOT"
  scp -q "$TARBALL" "$HOST:~/"
  if [ "$PRUNE" = 1 ]; then
    echo "── pruning generated language trees (removed content)"
    ssh "$HOST" "cd '$REMOTE_ROOT' && rm -rf public_html/en public_html/ar public_html/fr"
  fi
  TB=$(basename "$TARBALL")
  ssh "$HOST" "set -e; cd '$REMOTE_ROOT'
    tar xzf ~/$TB
    rm -f ~/$TB
    [ -f config/config.php ] || cp config/config.example.php config/config.php
    chmod 600 config/config.php
    php tools/rebuild.php -- sitemaps
    php tools/verify.php || true"
  echo "── pushed. Open https://nilemaple.com/ and /manage/ to confirm."
else
  echo "        (add --push with HOST and REMOTE_ROOT to ship it, or upload $TARBALL via hPanel → File Manager)"
fi
