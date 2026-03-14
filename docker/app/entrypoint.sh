#!/usr/bin/env sh
set -e

if [ ! -f /var/www/.env ]; then
  cp /var/www/.env.example /var/www/.env
fi

set_env_var() {
  key="$1"
  value="$2"
  escaped_value="$(printf '%s' "${value}" | sed 's/[\\/&]/\\&/g')"

  if grep -q "^${key}=" /var/www/.env; then
    sed -i "s/^${key}=.*/${key}=${escaped_value}/" /var/www/.env
  else
    echo "${key}=${value}" >> /var/www/.env
  fi
}

# Keep DB settings in .env aligned with Docker service-to-service networking.
set_env_var "DB_CONNECTION" "${DB_CONNECTION:-mysql}"
set_env_var "DB_HOST" "${DB_HOST:-db}"
set_env_var "DB_PORT" "${DB_PORT:-3306}"
set_env_var "DB_DATABASE" "${DB_DATABASE:-gym_booking}"
set_env_var "DB_USERNAME" "${DB_USERNAME:-gym_user}"
set_env_var "DB_PASSWORD" "${DB_PASSWORD}"
set_env_var "CACHE_STORE" "${CACHE_STORE:-file}"
set_env_var "SESSION_DRIVER" "${SESSION_DRIVER:-file}"
set_env_var "QUEUE_CONNECTION" "${QUEUE_CONNECTION:-sync}"

if [ -n "${APP_KEY:-}" ]; then
  set_env_var "APP_KEY" "${APP_KEY}"
fi

if [ -n "${JWT_SECRET:-}" ]; then
  set_env_var "JWT_SECRET" "${JWT_SECRET}"
fi

CURRENT_APP_KEY="$(grep '^APP_KEY=' /var/www/.env | head -n1 | cut -d= -f2- || true)"
if [ -z "${CURRENT_APP_KEY}" ] || echo "${CURRENT_APP_KEY}" | grep -q '^CHANGE_ME'; then
  GENERATED_APP_KEY="$(php -r "echo 'base64:'.base64_encode(random_bytes(32));")"
  set_env_var "APP_KEY" "${GENERATED_APP_KEY}"
fi

CURRENT_JWT_SECRET="$(grep '^JWT_SECRET=' /var/www/.env | head -n1 | cut -d= -f2- || true)"
if [ -z "${CURRENT_JWT_SECRET}" ] || echo "${CURRENT_JWT_SECRET}" | grep -q '^CHANGE_ME'; then
  GENERATED_JWT_SECRET="$(php -r "echo bin2hex(random_bytes(32));")"
  set_env_var "JWT_SECRET" "${GENERATED_JWT_SECRET}"
fi

DB_HOST_ACTUAL="${DB_HOST:-db}"
DB_PORT_ACTUAL="${DB_PORT:-3306}"

echo "Waiting for MySQL at ${DB_HOST_ACTUAL}:${DB_PORT_ACTUAL}..."
DB_ADMIN_BIN="$(command -v mariadb-admin || command -v mysqladmin || true)"
DB_WAIT_MAX_ATTEMPTS="${DB_WAIT_MAX_ATTEMPTS:-120}"
attempt=1

if [ -z "${DB_ADMIN_BIN}" ]; then
  echo "MySQL admin client not found in container."
  exit 1
fi

# Check server reachability only; auth may fail until init is complete.
until "${DB_ADMIN_BIN}" ping -h"${DB_HOST_ACTUAL}" -P"${DB_PORT_ACTUAL}" --silent >/dev/null 2>&1; do
  if [ "${attempt}" -ge "${DB_WAIT_MAX_ATTEMPTS}" ]; then
    echo "MySQL is still unavailable after ${DB_WAIT_MAX_ATTEMPTS} attempts."
    echo "Run: docker compose logs --tail=200 db"
    exit 1
  fi
  attempt=$((attempt + 1))
  sleep 2
done

echo "MySQL is ready, running migrations..."
php artisan config:clear
php artisan migrate:fresh --seed --force

echo "Starting Laravel API on 0.0.0.0:8000"
exec php artisan serve --host=0.0.0.0 --port=8000
