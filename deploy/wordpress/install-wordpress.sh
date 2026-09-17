#!/usr/bin/env bash
set -euo pipefail

ASSET_SOURCE="${1:-wordpress/wp-content/themes/skysend}"
PUBLIC_SOURCE="${2:-wordpress/public}"
SITE_URL="${3:-}"
OFFICIAL_THEME="twentytwentyfive"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WP_ROOT="/var/www/skysend"
DB_NAME="skysend_wp"
DB_USER="skysend_wp"
ADMIN_FILE="/root/skysend-wordpress-admin.txt"
NEW_INSTALL=0

if [[ "${EUID}" -ne 0 ]]; then
    echo "Run this script as root." >&2
    exit 1
fi

if [[ -z "${SITE_URL}" ]]; then
    echo "Pass the current site URL as the third argument; it must not silently overwrite a saved domain or HTTPS URL." >&2
    exit 1
fi

if [[ ! -f "${ASSET_SOURCE}/assets/images/skysend-logo.png" || ! -d "${ASSET_SOURCE}/assets/images/providers" ]]; then
    echo "SkySend image source was not found at ${ASSET_SOURCE}." >&2
    exit 1
fi

if [[ ! -f "${PUBLIC_SOURCE}/robots.txt" || ! -f "${SCRIPT_DIR}/skysend.conf" ]]; then
    echo "Deployment files are incomplete." >&2
    exit 1
fi

if systemctl list-unit-files skysend-www.service >/dev/null 2>&1; then
    systemctl disable --now skysend-www.service || true
fi

export DEBIAN_FRONTEND=noninteractive
apt-get update
apt-get install -y \
    apache2 \
    mariadb-server \
    curl \
    unzip \
    php \
    libapache2-mod-php \
    php-curl \
    php-gd \
    php-intl \
    php-mbstring \
    php-mysql \
    php-xml \
    php-zip

systemctl enable --now mariadb

if [[ ! -x /usr/local/bin/wp ]]; then
    curl -fsSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o /usr/local/bin/wp
    chmod 0755 /usr/local/bin/wp
fi

install -d -o root -g www-data -m 0750 "${WP_ROOT}"

if [[ ! -f "${WP_ROOT}/wp-load.php" ]]; then
    wp core download \
        --path="${WP_ROOT}" \
        --version=latest \
        --locale=ru_RU \
        --skip-content \
        --allow-root
fi

if [[ ! -f "${WP_ROOT}/wp-config.php" ]]; then
    DB_PASSWORD="$(openssl rand -hex 24)"
    mariadb <<SQL
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

    wp config create \
        --path="${WP_ROOT}" \
        --dbname="${DB_NAME}" \
        --dbuser="${DB_USER}" \
        --dbpass="${DB_PASSWORD}" \
        --dbhost=localhost \
        --dbcharset=utf8mb4 \
        --skip-check \
        --allow-root
    wp config set DISALLOW_FILE_EDIT true --raw --path="${WP_ROOT}" --allow-root
    wp config set WP_ENVIRONMENT_TYPE production --path="${WP_ROOT}" --allow-root
    wp config set WP_AUTO_UPDATE_CORE true --raw --path="${WP_ROOT}" --allow-root
fi

# Retain the previous assets only for the one-time Media Library import.
# This source directory is never a request to activate the legacy theme.
install -d -o root -g www-data -m 0755 "${WP_ROOT}/wp-content/themes/skysend/assets/images"
cp -a "${ASSET_SOURCE}/assets/images/." "${WP_ROOT}/wp-content/themes/skysend/assets/images/"
install -d -o www-data -g www-data -m 0775 "${WP_ROOT}/wp-content/uploads"
install -o root -g www-data -m 0644 "${PUBLIC_SOURCE}/robots.txt" "${WP_ROOT}/robots.txt"
sed -i "s|^Sitemap:.*|Sitemap: ${SITE_URL%/}/wp-sitemap.xml|" "${WP_ROOT}/robots.txt"
install -o root -g www-data -m 0644 "${PUBLIC_SOURCE}/.htaccess" "${WP_ROOT}/.htaccess"

if ! wp core is-installed --path="${WP_ROOT}" --allow-root; then
    NEW_INSTALL=1
    ADMIN_PASSWORD="$(openssl rand -base64 36 | tr -d '/+=' | cut -c1-28)"
    wp core install \
        --path="${WP_ROOT}" \
        --url="${SITE_URL}" \
        --title="SkySend" \
        --admin_user="skysend_admin" \
        --admin_password="${ADMIN_PASSWORD}" \
        --admin_email="webmaster@skysend.ru" \
        --skip-email \
        --allow-root

    # Keep the two default examples recoverable, but never publish them on the landing.
    wp post update 1 2 --post_status=draft --path="${WP_ROOT}" --allow-root

    wp option update blogdescription "Система приёма платежей для бизнеса" --path="${WP_ROOT}" --allow-root
    wp option update timezone_string "Europe/Moscow" --path="${WP_ROOT}" --allow-root
    wp option update blog_public 1 --path="${WP_ROOT}" --allow-root
    wp option update default_comment_status closed --path="${WP_ROOT}" --allow-root
    wp option update default_ping_status closed --path="${WP_ROOT}" --allow-root

    umask 077
    {
        echo "WordPress: ${SITE_URL}/wp-admin/"
        echo "User: skysend_admin"
        echo "Password: ${ADMIN_PASSWORD}"
        echo "Change this password and the administrator email after first sign-in."
    } > "${ADMIN_FILE}"
    chmod 0600 "${ADMIN_FILE}"
fi

wp option update home "${SITE_URL}" --path="${WP_ROOT}" --allow-root
wp option update siteurl "${SITE_URL}" --path="${WP_ROOT}" --allow-root
if ! wp theme is-installed "${OFFICIAL_THEME}" --path="${WP_ROOT}" --allow-root; then
    wp theme install "${OFFICIAL_THEME}" --path="${WP_ROOT}" --allow-root
fi

NATIVE_MIGRATION_COMPLETE="$(wp option get skysend_native_migration_complete --path="${WP_ROOT}" --allow-root 2>/dev/null || true)"
if [[ -n "${NATIVE_MIGRATION_COMPLETE}" ]]; then
    ACTIVE_THEME="$(wp option get stylesheet --path="${WP_ROOT}" --allow-root)"
    if [[ "${ACTIVE_THEME}" == "skysend" ]]; then
        echo "Native migration is complete, but the legacy SkySend theme is active. Refusing to continue; inspect the saved native page before selecting the official theme." >&2
        exit 1
    fi
    echo "Native migration already complete: existing page, templates, styles and active theme are unchanged."
elif [[ "${NEW_INSTALL}" -eq 1 ]]; then
    wp theme activate "${OFFICIAL_THEME}" --path="${WP_ROOT}" --allow-root
    echo "Official theme ready. Run the one-time native import (media, build, prepare, QA, activate) to create the landing."
else
    echo "Existing database detected: leaving the published theme and editor content unchanged until the separate native import is approved."
fi
# Do not import/rebuild page content here, and never activate skysend.
wp rewrite structure '/%postname%/' --hard --path="${WP_ROOT}" --allow-root
wp rewrite flush --hard --path="${WP_ROOT}" --allow-root
wp language core install ru_RU --activate --path="${WP_ROOT}" --allow-root || true

chown -R root:www-data "${WP_ROOT}"
find "${WP_ROOT}" -type d -exec chmod 0750 {} +
find "${WP_ROOT}" -type f -exec chmod 0640 {} +
chown -R www-data:www-data "${WP_ROOT}/wp-content/uploads"
chmod 0640 "${WP_ROOT}/wp-config.php"
chmod 0644 "${WP_ROOT}/robots.txt" "${WP_ROOT}/.htaccess"

PHP_APACHE_DIR="$(find /etc/php -mindepth 2 -maxdepth 2 -type d -path '*/apache2' | sort -V | tail -1)"
if [[ -n "${PHP_APACHE_DIR}" ]]; then
    install -o root -g root -m 0644 "${SCRIPT_DIR}/skysend-php.ini" "${PHP_APACHE_DIR}/conf.d/99-skysend.ini"
fi

install -o root -g root -m 0644 "${SCRIPT_DIR}/skysend.conf" /etc/apache2/sites-available/skysend.conf
a2enmod rewrite headers expires >/dev/null
a2dissite 000-default >/dev/null 2>&1 || true
a2ensite skysend >/dev/null
apache2ctl configtest
systemctl enable apache2
systemctl restart apache2

INSTALLED_CORE_VERSION="$(wp core version --path="${WP_ROOT}" --allow-root)"
wp core verify-checksums --path="${WP_ROOT}" --version="${INSTALLED_CORE_VERSION}" --locale=ru_RU --allow-root
wp theme get "${OFFICIAL_THEME}" --fields=name,status,version --format=table --path="${WP_ROOT}" --allow-root
wp db check --path="${WP_ROOT}" --allow-root
systemctl --no-pager --full status apache2
