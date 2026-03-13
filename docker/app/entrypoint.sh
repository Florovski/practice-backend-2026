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
set_env_var "DB_PASSWORD" "${DB_PASSWORD:-gym_pass}"

echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT}..."
DB_ADMIN_BIN="$(command -v mariadb-admin || command -v mysqladmin || true)"
DB_WAIT_MAX_ATTEMPTS="${DB_WAIT_MAX_ATTEMPTS:-120}"
attempt=1

if [ -z "${DB_ADMIN_BIN}" ]; then
  echo "MySQL admin client not found in container."
  exit 1
fi

# Check server reachability only; auth may fail until init is complete.
until "${DB_ADMIN_BIN}" ping -h"${DB_HOST}" -P"${DB_PORT}" --silent >/dev/null 2>&1; do
  if [ "${attempt}" -ge "${DB_WAIT_MAX_ATTEMPTS}" ]; then
    echo "MySQL is still unavailable after ${DB_WAIT_MAX_ATTEMPTS} attempts."
    echo "Run: docker compose logs --tail=200 db"
    exit 1
  fi
  attempt=$((attempt + 1))
  sleep 2
done

echo "MySQL is ready, running migrations..."
php artisan optimize:clear
php artisan migrate:fresh --seed --force

echo "Starting Laravel API on 0.0.0.0:8000"
exec php artisan serve --host=0.0.0.0 --port=8000
