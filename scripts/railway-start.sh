#!/bin/sh
set -e

cd /app

echo "[railway] APP_ENV=${APP_ENV:-unset}"

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

echo "[railway] Warming cache..."
php bin/console cache:clear --env=prod --no-warmup
php bin/console cache:warmup --env=prod

PORT="${PORT:-8080}"
echo "[railway] Starting server on 0.0.0.0:${PORT}"
exec php -S "0.0.0.0:${PORT}" -t public
