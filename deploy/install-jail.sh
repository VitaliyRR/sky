#!/usr/bin/env bash
set -euo pipefail

BUILD_DIR="${1:-dist/client}"
JAIL_ROOT="/srv/jails/www"
SITE_DIR="${JAIL_ROOT}/site"

if [[ "${EUID}" -ne 0 ]]; then
  echo "Run this script as root." >&2
  exit 1
fi

if [[ ! -f "${BUILD_DIR}/index.html" ]]; then
  echo "Static build not found: ${BUILD_DIR}/index.html" >&2
  exit 1
fi

if ! command -v busybox >/dev/null 2>&1; then
  apt-get update
  apt-get install -y busybox-static
fi

if ! getent group www >/dev/null 2>&1; then
  groupadd --system www
fi

if ! id www >/dev/null 2>&1; then
  useradd --system --gid www --home-dir /nonexistent --shell /usr/sbin/nologin www
fi

install -d -o root -g root -m 0755 "${JAIL_ROOT}" "${JAIL_ROOT}/bin" "${SITE_DIR}"
install -o root -g root -m 0755 "$(command -v busybox)" "${JAIL_ROOT}/bin/busybox"
cp -a "${BUILD_DIR}/." "${SITE_DIR}/"
chown -R root:root "${SITE_DIR}"
chmod -R a=rX "${SITE_DIR}"

install -o root -g root -m 0644 \
  "$(dirname "$0")/skysend-www.service" \
  /etc/systemd/system/skysend-www.service

systemctl daemon-reload
systemctl enable --now skysend-www.service
systemctl --no-pager --full status skysend-www.service
