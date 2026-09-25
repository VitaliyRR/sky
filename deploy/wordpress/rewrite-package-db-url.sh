#!/usr/bin/env bash
# Export a production-domain SQL dump from a sanitized SkySend package in an
# isolated database. The development database and the original ZIP stay intact.
set -euo pipefail
umask 077

if [[ "$EUID" -ne 0 || "$#" -ne 1 ]]; then
    echo "Usage: sudo bash $0 /opt/skysend-packages/STAMP/skysend-wordpress-STAMP" >&2
    exit 2
fi
payload="$(realpath -e -- "$1")"
work="$(dirname "$payload")"
stamp="$(basename "$work")"
if [[ ! "$stamp" =~ ^[0-9]{8}_[0-9]{6}$ ||
      "$payload" != "/opt/skysend-packages/$stamp/skysend-wordpress-$stamp" ]]; then
    echo 'Refusing unexpected package path.' >&2
    exit 2
fi
site="$payload/site"
source_sql="$payload/database.sql"
output="$work/skysend-database-${stamp}-skysend-ru.sql"
db="skysend_domain_${stamp}"
if [[ ! -f "$source_sql" || ! -f "$site/wp-load.php" ||
      -e "$site/wp-config.php" || -e "$output" ||
      ! "$db" =~ ^skysend_domain_[0-9]{8}_[0-9]{6}$ ]]; then
    echo 'Package files, output path, or temporary database name are not safe.' >&2
    exit 2
fi
if [[ "$(mariadb -Nse "SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='${db}'")" != 0 ||
      "$(mariadb -Nse "SELECT COUNT(*) FROM mysql.user WHERE User='${db}'")" != 0 ]]; then
    echo 'Temporary database or account already exists.' >&2
    exit 2
fi

db_created=0
user_created=0
config_started=0
cleanup() {
    if [[ "$config_started" -eq 1 && -f "$site/wp-config.php" ]]; then
        rm -- "$site/wp-config.php"
    fi
    if [[ "$db_created" -eq 1 ]]; then
        mariadb -e "DROP DATABASE \`$db\`"
    fi
    if [[ "$user_created" -eq 1 ]]; then
        mariadb -e "DROP USER '$db'@'localhost'"
    fi
}
trap cleanup EXIT

mariadb -e "CREATE DATABASE \`$db\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
db_created=1
db_password="$(openssl rand -hex 32)"
mariadb -e "CREATE USER '$db'@'localhost' IDENTIFIED BY '$db_password'"
user_created=1
mariadb -e "GRANT ALL PRIVILEGES ON \`$db\`.* TO '$db'@'localhost'"
config_started=1
printf '%s\n' "$db_password" | wp --allow-root --path="$site" config create \
    --dbname="$db" --dbuser="$db" --dbhost=localhost --dbprefix=wp_ \
    --dbcharset=utf8mb4 --locale=ru_RU --skip-check --skip-salts --quiet \
    --prompt=dbpass >/dev/null
unset db_password
wp --allow-root --path="$site" config set DISABLE_WP_CRON true --raw --quiet
wp --allow-root --path="$site" config set WP_HTTP_BLOCK_EXTERNAL true --raw --quiet
wp --allow-root --path="$site" config set DISALLOW_FILE_MODS true --raw --quiet
wp --allow-root --path="$site" db import "$source_sql" --quiet
[[ "$(wp --allow-root --path="$site" --skip-plugins --skip-themes option get home)" == 'http://31.129.98.28' ]]
[[ "$(wp --allow-root --path="$site" --skip-plugins --skip-themes option get siteurl)" == 'http://31.129.98.28' ]]

wp --allow-root --path="$site" --skip-plugins --skip-themes search-replace \
    'http://31.129.98.28' 'https://skysend.ru' \
    --all-tables-with-prefix --precise --report-changed-only
[[ "$(wp --allow-root --path="$site" --skip-plugins --skip-themes option get home)" == 'https://skysend.ru' ]]
[[ "$(wp --allow-root --path="$site" --skip-plugins --skip-themes option get siteurl)" == 'https://skysend.ru' ]]
[[ "$(wp --allow-root --path="$site" --skip-plugins --skip-themes post get 431 --field=guid)" == \
   'https://skysend.ru/wp-content/uploads/2026/09/allvend_main.png' ]]
wp --allow-root --path="$site" --skip-plugins --skip-themes post get 67 --field=post_content |
    grep -F 'https://skysend.ru/wp-content/uploads/2026/09/allvend_main.png' >/dev/null

wp --allow-root --path="$site" db export "$output" \
    --single-transaction --skip-lock-tables --skip-add-locks --skip-comments \
    --skip-add-drop-table --skip-triggers --quiet
if [[ ! -s "$output" ]] || grep -Fq '31.129.98.28' "$output"; then
    echo 'Production-domain SQL validation failed.' >&2
    exit 1
fi
sha256sum "$output" > "$output.sha256"
chmod 600 "$output" "$output.sha256"
printf 'PRODUCTION_SQL=%s\n' "$output"
printf 'PRODUCTION_SQL_CHECKSUM=%s\n' "$output.sha256"
