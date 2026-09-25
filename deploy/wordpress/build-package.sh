#!/usr/bin/env bash
# One-time package builder on the existing test VM. Never run in the company jail.
set -euo pipefail
umask 077
SOURCE=/var/www/skysend
TOOLS="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
STAMP="$(date -u +%Y%m%d_%H%M%S)"
WORK="/opt/skysend-packages/${STAMP}"
NAME="skysend-wordpress-${STAMP}"
PAYLOAD="${WORK}/${NAME}"
SITE="${PAYLOAD}/site"
DB="skysend_pkg_${STAMP}"
HTTP_PID=''
DB_CREATED=0
USER_CREATED=0
cleanup() {
    if [[ -n "$HTTP_PID" ]]; then kill "$HTTP_PID" 2>/dev/null || true; wait "$HTTP_PID" 2>/dev/null || true; fi
    # Exact newly-created identifiers only. Never drop the live WordPress DB/user.
    if [[ "$DB" =~ ^skysend_pkg_[0-9]{8}_[0-9]{6}$ ]]; then
        if [[ "$DB_CREATED" -eq 1 ]]; then mariadb -e "DROP DATABASE \`${DB}\`"; fi
        if [[ "$USER_CREATED" -eq 1 ]]; then mariadb -e "DROP USER '${DB}'@'localhost'"; fi
    fi
}
trap cleanup EXIT
[[ "$EUID" -eq 0 && "$SOURCE" == /var/www/skysend && -f "$SOURCE/wp-config.php" ]]
REPO=/opt/skysend
ASSETS="$REPO/wordpress/native-blocks/assets/provider-catalog-20260925"
[[ -f "$SOURCE/wp-content/mu-plugins/skysend-provider-catalog.php" ]]
for filename in catalog.js catalog.css catalog.json; do
    [[ -f "$SOURCE/wp-content/mu-plugins/skysend-provider-catalog/$filename" ]]
done
[[ -d "$SOURCE/wp-content/uploads/skysend-providers-20260925" ]]
[[ "$(find "$SOURCE/wp-content/uploads/skysend-providers-20260925" -maxdepth 1 -type f -name '*.webp' | wc -l)" -eq 5000 ]]
[[ "$(find "$ASSETS/logos" -maxdepth 1 -type f -name '*.webp' | wc -l)" -eq 5000 ]]
cmp -s "$REPO/wordpress/wp-content/mu-plugins/skysend-provider-catalog.php" "$SOURCE/wp-content/mu-plugins/skysend-provider-catalog.php"
for filename in catalog.js catalog.css catalog.json; do
    cmp -s "$REPO/wordpress/wp-content/mu-plugins/skysend-provider-catalog/$filename" "$SOURCE/wp-content/mu-plugins/skysend-provider-catalog/$filename"
done
while IFS= read -r -d '' logo; do
    cmp -s "$logo" "$SOURCE/wp-content/uploads/skysend-providers-20260925/${logo##*/}" || {
        echo "Provider logo differs from committed asset: ${logo##*/}" >&2
        exit 1
    }
done < <(find "$ASSETS/logos" -maxdepth 1 -type f -name '*.webp' -print0)
[[ -f "$TOOLS/README-INSTALL.md" && -f "$TOOLS/package-snapshot.php" ]]
[[ -d /opt/skysend/.git && -z "$(git -C /opt/skysend status --porcelain)" ]]
php -r 'exit(class_exists("ZipArchive") ? 0 : 1);'
[[ "$(wp --allow-root --path="$SOURCE" core version)" == 7.1.1 ]]
wp --allow-root --path="$SOURCE" core verify-checksums --version=7.1.1 --locale=en_US
wp --allow-root --path="$SOURCE" plugin verify-checksums carousel-block wp-seopress wp-super-cache
[[ -z "$(find "$SOURCE/wp-content/uploads" -type l -print -quit)" ]]
[[ -z "$(find "$SOURCE/wp-content/uploads" -type f | grep -Ei '\.(php[0-9]?|phtml|phar|sql|log|zip|gz|pem|key|env|bak)$' || true)" ]]
mkdir -p /opt/skysend-packages
chmod 700 /opt/skysend-packages
mkdir -m 700 "$WORK"
mkdir -p "$SITE/wp-content/plugins" "$SITE/wp-content/themes" "$SITE/wp-content/mu-plugins"
wp --allow-root --path="$SOURCE" --skip-plugins --skip-themes eval-file "$TOOLS/package-snapshot.php" > "$WORK/source-before.json"
php "$TOOLS/package-archive.php" check-source "$WORK/source-before.json"
wp --allow-root --path="$SOURCE" db export "$WORK/source-private.sql" \
    --single-transaction --skip-lock-tables --skip-add-locks --skip-comments \
    --skip-add-drop-table --skip-triggers
# Copy all core files without the live configuration or unrelated root files.
cp -a "$SOURCE/wp-admin" "$SOURCE/wp-includes" "$SITE/"
for filename in index.php license.txt readme.html wp-activate.php wp-blog-header.php wp-comments-post.php wp-config-sample.php wp-cron.php wp-links-opml.php wp-load.php wp-login.php wp-mail.php wp-settings.php wp-signup.php wp-trackback.php xmlrpc.php; do
    cp -a "$SOURCE/$filename" "$SITE/$filename"
done
cp -a "$SOURCE/wp-content/index.php" "$SITE/wp-content/"
cp -a "$SOURCE/wp-content/themes/twentytwentyfive" "$SITE/wp-content/themes/"
cp -a "$SOURCE/wp-content/plugins/carousel-block" "$SOURCE/wp-content/plugins/wp-seopress" "$SOURCE/wp-content/plugins/wp-super-cache" "$SITE/wp-content/plugins/"
cp -a "$SOURCE/wp-content/mu-plugins/skysend-provider-catalog.php" "$SOURCE/wp-content/mu-plugins/skysend-provider-catalog" "$SITE/wp-content/mu-plugins/"
cp -a "$SOURCE/wp-content/uploads" "$SITE/wp-content/"
if [[ -d "$SOURCE/wp-content/languages" ]]; then cp -a "$SOURCE/wp-content/languages" "$SITE/wp-content/"; fi
cp -a "$SOURCE/.htaccess" "$SOURCE/robots.txt" "$SITE/"
cp "$TOOLS/README-INSTALL.md" "$PAYLOAD/README-INSTALL.md"
cp "$TOOLS/robots-production.txt" "$PAYLOAD/robots-production.txt"
php "$TOOLS/package-archive.php" scan "$SITE"
# A constrained temporary account can access only the new clone, not the source.
[[ "$(mariadb -Nse "SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='${DB}'")" == 0 ]]
[[ "$(mariadb -Nse "SELECT COUNT(*) FROM mysql.user WHERE User='${DB}'")" == 0 ]]
[[ "$(mariadb -Nse "SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA='skysend_wp'")" == 0 ]]
[[ "$(mariadb -Nse "SELECT COUNT(*) FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA='skysend_wp'")" == 0 ]]
[[ "$(mariadb -Nse "SELECT COUNT(*) FROM information_schema.EVENTS WHERE EVENT_SCHEMA='skysend_wp'")" == 0 ]]
mariadb -e "CREATE DATABASE \`${DB}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
DB_CREATED=1
DB_PASS="$(openssl rand -hex 32)"
mariadb <<SQL
CREATE USER '${DB}'@'localhost' IDENTIFIED BY '${DB_PASS}';
SQL
USER_CREATED=1
mariadb -e "GRANT ALL PRIVILEGES ON \`${DB}\`.* TO '${DB}'@'localhost'"
printf '%s\n' "$DB_PASS" | wp --allow-root --path="$SITE" config create \
    --dbname="$DB" --dbuser="$DB" --dbhost=localhost --dbprefix=wp_ \
    --dbcharset=utf8mb4 --locale=ru_RU --skip-check --skip-salts --quiet --prompt=dbpass \
    > "$WORK/config-private.log" 2>&1
unset DB_PASS
wp --allow-root --path="$SITE" config set DISABLE_WP_CRON true --raw
wp --allow-root --path="$SITE" config set WP_HTTP_BLOCK_EXTERNAL true --raw
wp --allow-root --path="$SITE" config set DISALLOW_FILE_MODS true --raw
wp --allow-root --path="$SITE" config set WP_ENVIRONMENT_TYPE local
wp --allow-root --path="$SITE" db import "$WORK/source-private.sql"
wp --allow-root --path="$SITE" --skip-plugins --skip-themes eval-file "$TOOLS/package-sanitize.php" "$DB"
wp --allow-root --path="$SITE" db export "$PAYLOAD/database.sql" \
    --single-transaction --skip-lock-tables --skip-add-locks --skip-comments \
    --skip-add-drop-table --skip-triggers
# Drop only the explicitly guarded clone, then prove the delivered SQL imports.
[[ "$(wp --allow-root --path="$SITE" config get DB_NAME)" == "$DB" ]]
wp --allow-root --path="$SITE" db reset --yes
wp --allow-root --path="$SITE" db import "$PAYLOAD/database.sql"
wp --allow-root --path="$SITE" db check
wp --allow-root --path="$SITE" core verify-checksums --version=7.1.1 --locale=en_US
wp --allow-root --path="$SITE" plugin verify-checksums carousel-block wp-seopress wp-super-cache
wp --allow-root --path="$SITE" --skip-plugins --skip-themes eval-file "$TOOLS/package-snapshot.php" > "$WORK/restored.json"
wp --allow-root --path="$SOURCE" --skip-plugins --skip-themes eval-file "$TOOLS/package-snapshot.php" > "$WORK/source-after.json"
php "$TOOLS/package-archive.php" compare "$WORK/source-before.json" "$WORK/restored.json" "$WORK/source-after.json"
wp --allow-root --path="$SITE" eval-file "$TOOLS/package-verify.php" > "$WORK/blocks-check.json"
# The SQL for delivery is already frozen above. URL rewrite below is clone-only QA.
[[ -z "$(ss -H -ltn 'sport = :58081')" ]]
wp --allow-root --path="$SITE" --skip-plugins --skip-themes search-replace \
    'http://31.129.98.28' 'http://127.0.0.1:58081' --all-tables-with-prefix --skip-columns=guid --precise --report-changed-only
wp --allow-root --path="$SITE" rewrite flush
php -S 127.0.0.1:58081 -t "$SITE" "$TOOLS/package-router.php" > "$WORK/http-private.log" 2>&1 &
HTTP_PID=$!
sleep 1
php "$TOOLS/package-http-check.php" "$SITE" > "$WORK/http-check.json"
kill "$HTTP_PID"
wait "$HTTP_PID" 2>/dev/null || true
HTTP_PID=''
# This exact temporary configuration was created by this script and never ships.
[[ "$(realpath "$SITE/wp-config.php")" == "$WORK/$NAME/site/wp-config.php" ]]
rm -- "$SITE/wp-config.php"
php "$TOOLS/package-archive.php" create "$PAYLOAD" "$WORK/source-before.json" "$WORK/blocks-check.json" "$WORK/http-check.json"
chmod 600 "$WORK/$NAME.zip"
(
    cd "$WORK"
    sha256sum "$NAME.zip" | tee "$NAME.zip.sha256"
)
chmod 600 "$WORK/$NAME.zip.sha256"
printf 'DELIVERY_ARCHIVE=%s\n' "$WORK/$NAME.zip"
printf 'DELIVERY_CHECKSUM=%s\n' "$WORK/$NAME.zip.sha256"
