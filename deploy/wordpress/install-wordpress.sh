#!/usr/bin/env bash
set -euo pipefail

THEME_SOURCE="${1:-wordpress/wp-content/themes/skysend}"
PUBLIC_SOURCE="${2:-wordpress/public}"
SITE_URL="${3:-http://31.129.98.28}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WP_ROOT="/var/www/skysend"
DB_NAME="skysend_wp"
DB_USER="skysend_wp"
ADMIN_FILE="/root/skysend-wordpress-admin.txt"

if [[ "${EUID}" -ne 0 ]]; then
    echo "Run this script as root." >&2
    exit 1
fi

if [[ ! -f "${THEME_SOURCE}/style.css" || ! -f "${THEME_SOURCE}/front-page.php" ]]; then
    echo "SkySend theme was not found at ${THEME_SOURCE}." >&2
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
        --version=7.1 \
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

install -d -o root -g www-data -m 0755 "${WP_ROOT}/wp-content/themes/skysend"
cp -a "${THEME_SOURCE}/." "${WP_ROOT}/wp-content/themes/skysend/"
install -d -o www-data -g www-data -m 0775 "${WP_ROOT}/wp-content/uploads"
install -o root -g www-data -m 0644 "${PUBLIC_SOURCE}/robots.txt" "${WP_ROOT}/robots.txt"
install -o root -g www-data -m 0644 "${PUBLIC_SOURCE}/.htaccess" "${WP_ROOT}/.htaccess"

if ! wp core is-installed --path="${WP_ROOT}" --allow-root; then
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
wp option update blogdescription "Система приёма платежей для бизнеса" --path="${WP_ROOT}" --allow-root
wp option update timezone_string "Europe/Moscow" --path="${WP_ROOT}" --allow-root
wp option update blog_public 1 --path="${WP_ROOT}" --allow-root
wp option update default_comment_status closed --path="${WP_ROOT}" --allow-root
wp option update default_ping_status closed --path="${WP_ROOT}" --allow-root
wp theme activate skysend --path="${WP_ROOT}" --allow-root
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

wp core verify-checksums --path="${WP_ROOT}" --version=7.1 --locale=ru_RU --allow-root
wp db check --path="${WP_ROOT}" --allow-root
systemctl --no-pager --full status apache2
