#!/usr/bin/env bash
# Run on the Plesk server (SSH) from the Laravel project root
# (the folder that contains artisan), AFTER uploading code.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

echo "==> SNS ERP Plesk post-deploy ($ROOT)"

if [[ ! -f .env ]]; then
  echo "Missing .env — copy deploy/plesk/env.production.example to .env and fill secrets."
  exit 1
fi

php -v
composer install --no-dev --optimize-autoloader --no-interaction

# Build assets on server if Node is available; otherwise upload public/build from CI/local
if command -v npm >/dev/null 2>&1; then
  npm ci --omit=dev || npm install --omit=dev
  npm run build
else
  echo "npm not found — ensure public/build was uploaded from a local vite build."
fi

php artisan migrate --force
php artisan storage:link --force || true
php artisan optimize

# Writable dirs (Plesk user must own these)
chmod -R ug+rwx storage bootstrap/cache || true

echo "==> Done. Point the domain document root to: $ROOT/public"
echo "==> Add cron (every minute): cd $ROOT && php artisan schedule:run >> /dev/null 2>&1"
