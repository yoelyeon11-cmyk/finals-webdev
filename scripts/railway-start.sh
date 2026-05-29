#!/bin/sh
set -e

cd /app

echo "[railway] APP_ENV=${APP_ENV:-unset}"

# DATABASE_URL must come from Railway (Reference → MySQL → MYSQL_URL)
if [ -z "${DATABASE_URL}" ]; then
  echo "[railway] ERROR: DATABASE_URL is not set."
  echo "[railway] In Railway: web service → Variables → Add Reference → MySQL → MYSQL_URL"
  exit 1
fi
case "${DATABASE_URL}" in
  *'use Reference'*|*'${{'*|*'CHANGE_ME'*|*'USER:PASS'*)
    echo "[railway] ERROR: DATABASE_URL looks like a placeholder, not a real MySQL URL."
    echo "[railway] Delete the variable and re-add it with: Add Reference → MySQL → MYSQL_URL"
    exit 1
    ;;
esac
echo "[railway] DATABASE_URL is set (host hidden)"

# JWT keys (not in git — generate on first boot if missing)
if [ ! -f config/jwt/private.pem ] || [ ! -f config/jwt/public.pem ]; then
  if [ -z "${JWT_PASSPHRASE}" ]; then
    echo "[railway] ERROR: JWT_PASSPHRASE is required to generate JWT keys."
    exit 1
  fi
  echo "[railway] Generating JWT keypair..."
  mkdir -p config/jwt
  if php bin/console lexik:jwt:generate-keypair --overwrite -n 2>/dev/null; then
    echo "[railway] JWT keys created via lexik command."
  else
    openssl genrsa -aes256 -passout pass:"${JWT_PASSPHRASE}" -out config/jwt/private.pem 4096
    openssl rsa -pubout -in config/jwt/private.pem -passin pass:"${JWT_PASSPHRASE}" -out config/jwt/public.pem
    echo "[railway] JWT keys created via openssl."
  fi
fi

echo "[railway] Running migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

mkdir -p var/sessions/prod var/sessions/dev
chmod -R 775 var/sessions 2>/dev/null || true

echo "[railway] Repairing product images if upload files are missing..."
php bin/console app:fix-product-images --no-interaction 2>/dev/null || true

echo "[railway] Warming cache..."
php bin/console cache:clear --env=prod --no-warmup
php bin/console cache:warmup --env=prod

PORT="${PORT:-8080}"
echo "[railway] Starting server on 0.0.0.0:${PORT}"
# router.php serves static assets directly; Symfony handles API routes like /admin/stats.json
exec php -S "0.0.0.0:${PORT}" -t public public/router.php
