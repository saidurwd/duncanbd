#!/usr/bin/env bash
# Production-like QA stack: nginx + PHP-FPM 8.4 with OPcache, an optimised no-dev Composer install, and MySQL 8.4 +
# Redis 7 reached over a Docker network (like a real server, unlike a laptop's host.docker.internal bridge).
#
# Prereqs (once):
#   docker run -d --name dbd-mysql -e MYSQL_ROOT_PASSWORD=secret -p 33060:3306 mysql:8.4
#   docker run -d --name dbd-redis -p 63790:6379 redis:7-alpine
#   tools/qa/serve.sh   # creates + seeds the duncanbd_qa database; then stop its PHP server (Ctrl-C)
# Then:  tools/qa/docker-app.sh      -> http://127.0.0.1:8125
set -euo pipefail
cd "$(dirname "$0")/../.."
NAME=dbd-app2; PORT=8125
APP_KEY="${APP_KEY:-$(grep ^APP_KEY= .env | cut -d= -f2-)}"

docker network create dbd-net >/dev/null 2>&1 || true
docker network connect dbd-net dbd-mysql >/dev/null 2>&1 || true
docker network connect dbd-net dbd-redis >/dev/null 2>&1 || true

# Production-style code tree: no dev packages, authoritative classmap.
BUILD="$(mktemp -d)"
rsync -a --exclude node_modules --exclude vendor --exclude .git --exclude storage --exclude tools --exclude tests --exclude public/storage --exclude 'bootstrap/cache/*.php' ./ "$BUILD/"
(cd "$BUILD" && composer install --no-dev --optimize-autoloader --classmap-authoritative --no-interaction --no-scripts >/dev/null 2>&1)
mkdir -p "$BUILD/storage"/{app/public,framework/{cache,sessions,views},logs} "$BUILD/bootstrap/cache"

docker rm -f "$NAME" >/dev/null 2>&1 || true
docker run -d --name "$NAME" --network dbd-net -p "$PORT:8080" \
  -e APP_ENV=local -e APP_DEBUG=false -e APP_URL="http://127.0.0.1:$PORT" -e APP_KEY="$APP_KEY" \
  -e DB_CONNECTION=mysql -e DB_HOST=dbd-mysql -e DB_PORT=3306 -e DB_DATABASE=duncanbd_qa -e DB_USERNAME=root -e DB_PASSWORD=secret \
  -e REDIS_CLIENT=phpredis -e REDIS_HOST=dbd-redis -e REDIS_PORT=6379 \
  -e SESSION_DRIVER=redis -e CACHE_STORE=redis -e QUEUE_CONNECTION=sync -e RESPONSE_CACHE_ENABLED="${RESPONSE_CACHE_ENABLED:-true}" -e RESPONSE_CACHE_DRIVER=redis \
  -e MAIL_MAILER=log -e LOG_LEVEL=error -e PHP_OPCACHE_ENABLE=1 -e PHP_FPM_PM_CONTROL=static -e PHP_FPM_PM_MAX_CHILDREN="${FPM_CHILDREN:-16}" \
  -e SSL_MODE=off -e AUTORUN_ENABLED=false serversideup/php:8.4-fpm-nginx >/dev/null
sleep 5

# Public files (uploaded media + built assets) live in the host tree; code is copied in so the filesystem is native.
tar --no-xattrs -C "$BUILD" -cf - . 2>/dev/null | docker exec -i -u root "$NAME" sh -c "tar -xf - -C /var/www/html 2>/dev/null"
tar --no-xattrs -C public -cf - build 2>/dev/null | docker exec -i -u root "$NAME" sh -c "tar -xf - -C /var/www/html/public 2>/dev/null"
tar --no-xattrs -C storage/app -cf - public 2>/dev/null | docker exec -i -u root "$NAME" sh -c "mkdir -p /var/www/html/storage/app && tar -xf - -C /var/www/html/storage/app 2>/dev/null"
docker exec -u root "$NAME" sh -c "install-php-extensions gd exif intl bcmath >/dev/null 2>&1; chown -R www-data:www-data /var/www/html"
docker restart "$NAME" >/dev/null; sleep 8
docker exec "$NAME" sh -c "cd /var/www/html && php artisan package:discover >/dev/null 2>&1; php artisan storage:link --force >/dev/null; php artisan config:cache >/dev/null; php artisan route:cache >/dev/null; php artisan event:cache >/dev/null; php artisan view:cache >/dev/null"
rm -rf "$BUILD"
echo "QA production-like stack: http://127.0.0.1:$PORT"
