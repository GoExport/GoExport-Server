#!/bin/bash
set -e

echo "=========================================="
echo "  GoExport Server - Starting Up"
echo "=========================================="

# -----------------------------------------------------------------------------
# Chromium runtime environment for www-data
# -----------------------------------------------------------------------------
echo "[*] Preparing Chromium runtime environment..."

export HOME=/tmp/www-data-home
export XDG_RUNTIME_DIR=/tmp/xdg-runtime-www-data
export DISPLAY=:99
export PULSE_SERVER=unix:/var/run/pulse/native

mkdir -p \
    "$HOME/.cache" \
    "$HOME/.config" \
    "$XDG_RUNTIME_DIR"

chown -R www-data:www-data "$HOME" "$XDG_RUNTIME_DIR"
chmod 700 "$HOME" "$HOME/.cache" "$HOME/.config" "$XDG_RUNTIME_DIR"

chmod 1777 /tmp
[ -d /dev/shm ] && chmod 1777 /dev/shm

echo "[✓] Chromium runtime ready"

# -----------------------------------------------------------------------------
# Required application directories
# -----------------------------------------------------------------------------
echo "[*] Creating application directories..."

mkdir -p \
    /var/log/supervisor \
    /var/www/html/storage/logs \
    /var/www/html/storage/framework/{cache/data,sessions,views} \
    /var/www/html/storage/app/public/exports \
    /var/www/html/bootstrap/cache

chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

chmod -R 775 \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

# -----------------------------------------------------------------------------
# GoExport Binary Download
# -----------------------------------------------------------------------------
echo "[*] Checking GoExport binary..."

GOEXPORT_DIR="/var/www/html/bin/goexport"
GOEXPORT_BIN="$GOEXPORT_DIR/goexport"

if [ ! -f "$GOEXPORT_BIN" ]; then
    echo "[*] Downloading GoExport binary..."
    cd "$GOEXPORT_DIR"
    wget -q https://github.com/GoExport/GoExport/releases/latest/download/goexport_linux_portable_amd64.tar.gz
    tar -xzf goexport_linux_portable_amd64.tar.gz
    rm goexport_linux_portable_amd64.tar.gz
    chmod +x goexport 2>/dev/null || true
    echo "[✓] GoExport binary downloaded"
else
    echo "[✓] GoExport binary already present"
fi

# -----------------------------------------------------------------------------
# Optional database wait
# -----------------------------------------------------------------------------
if [ -n "$DB_HOST" ] && [ -n "$DB_PORT" ]; then
    echo "[*] Waiting for database at $DB_HOST:$DB_PORT..."
    for i in {1..60}; do
        if (echo > /dev/tcp/$DB_HOST/$DB_PORT) >/dev/null 2>&1; then
            echo "[✓] Database available"
            break
        fi
        sleep 1
    done
fi

# -----------------------------------------------------------------------------
# Laravel bootstrap
# -----------------------------------------------------------------------------
cd /var/www/html

if [ ! -f .env ]; then
    cp .env.example .env 2>/dev/null || touch .env
    chown www-data:www-data .env
fi

update_env() {
    [ -n "$2" ] || return
    grep -q "^$1=" .env \
        && sed -i "s|^$1=.*|$1=$2|" .env \
        || echo "$1=$2" >> .env
}

update_env APP_NAME        "${APP_NAME:-GoExport}"
update_env APP_ENV         "${APP_ENV:-production}"
update_env APP_DEBUG       "${APP_DEBUG:-false}"
update_env APP_URL         "${APP_URL:-http://localhost}"

update_env DB_CONNECTION   "${DB_CONNECTION:-mysql}"
update_env DB_HOST         "${DB_HOST:-127.0.0.1}"
update_env DB_PORT         "${DB_PORT:-3306}"
update_env DB_DATABASE     "${DB_DATABASE:-laravel}"
update_env DB_USERNAME     "${DB_USERNAME:-root}"
update_env DB_PASSWORD     "${DB_PASSWORD:-}"

update_env QUEUE_CONNECTION "${QUEUE_CONNECTION:-database}"
update_env REDIS_HOST       "${REDIS_HOST:-127.0.0.1}"
update_env REDIS_PORT       "${REDIS_PORT:-6379}"

if ! grep -q "APP_KEY=base64:" .env; then
    php artisan key:generate --force
fi

php artisan config:cache || true
php artisan route:cache  || true
php artisan view:cache   || true
php artisan storage:link || true

if [ "$AUTO_MIGRATE" = "true" ]; then
    php artisan migrate --force
    php artisan orchid:admin admin admin@admin.com password || true
fi

# -----------------------------------------------------------------------------
# X11 / Xvfb readiness
# -----------------------------------------------------------------------------
echo "[*] Waiting for X display :99..."

for i in {1..30}; do
    if xdpyinfo -display :99 >/dev/null 2>&1; then
        echo "[✓] X display ready"
        break
    fi
    sleep 1
done

[ -S /tmp/.X11-unix/X99 ] && chmod 777 /tmp/.X11-unix/X99

# -----------------------------------------------------------------------------
# DBus (best-effort)
# -----------------------------------------------------------------------------
mkdir -p /var/run/dbus
[ -S /var/run/dbus/system_bus_socket ] && chmod 666 /var/run/dbus/system_bus_socket

# -----------------------------------------------------------------------------
# PulseAudio (system mode)
# -----------------------------------------------------------------------------
echo "[*] Starting PulseAudio..."

mkdir -p /var/run/pulse
chown -R pulse:pulse /var/run/pulse
chmod 755 /var/run/pulse

pulseaudio \
    --system \
    --daemonize \
    --exit-idle-time=-1 \
    --disallow-exit \
    --disable-shm \
    2>/dev/null || true

sleep 2

pactl info >/dev/null 2>&1 \
    && echo "[✓] PulseAudio available" \
    || echo "[!] PulseAudio not accessible"

# -----------------------------------------------------------------------------
# Custom init hook
# -----------------------------------------------------------------------------
[ -f /var/www/html/docker/init.sh ] && bash /var/www/html/docker/init.sh

# -----------------------------------------------------------------------------
# Start main process
# -----------------------------------------------------------------------------
echo "=========================================="
echo "  GoExport Server - Ready"
echo "=========================================="

exec "$@"
