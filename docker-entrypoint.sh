#!/bin/sh
set -e

LOG_DIR=/var/www/html/public/application/logs
mkdir -p "$LOG_DIR"
chown www-data:www-data "$LOG_DIR"
chmod 775 "$LOG_DIR"

exec /usr/bin/supervisord -c /etc/supervisord.conf
