#!/usr/bin/env bash
set -e

# Tạo APP_KEY nếu thiếu
if ! grep -q '^APP_KEY=' .env || [ -z "$(grep '^APP_KEY=' .env | cut -d= -f2)" ]; then
  php artisan key:generate --force || true
fi

# Liên kết storage & cache
php artisan storage:link 2>/dev/null || true
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Migrate DB (bạn có thể bỏ nếu không muốn chạy tự động)
php artisan migrate --force || true

exec "$@"
