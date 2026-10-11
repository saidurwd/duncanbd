#!/usr/bin/env bash
# Boots a throwaway QA copy of the site on http://127.0.0.1:8123 using the MySQL 8.4 container
# (docker run -d --name dbd-mysql -e MYSQL_ROOT_PASSWORD=secret -p 33060:3306 mysql:8.4) and demo content.
set -euo pipefail
cd "$(dirname "$0")/../.."

export APP_ENV=local APP_DEBUG=false APP_URL=http://127.0.0.1:8123 APP_KEY="${APP_KEY:-base64:$(php -r 'echo base64_encode(random_bytes(32));')}"
export DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=33060 DB_DATABASE=duncanbd_qa DB_USERNAME=root DB_PASSWORD=secret
export SESSION_DRIVER=file CACHE_STORE=file QUEUE_CONNECTION=sync MAIL_MAILER=log
export RESPONSE_CACHE_ENABLED=true RESPONSE_CACHE_DRIVER=file LOG_LEVEL=error
export ADMIN_SEED_EMAIL=qa-admin@example.com ADMIN_SEED_PASSWORD='Qa-Password-12345!'
export PHP_CLI_SERVER_WORKERS=8

# Keep the optimised config/route caches out of bootstrap/cache so they can never leak into test or dev runs.
QA_CACHE="$PWD/storage/framework/qa-cache"; mkdir -p "$QA_CACHE"
export APP_CONFIG_CACHE="$QA_CACHE/config.php" APP_ROUTES_CACHE="$QA_CACHE/routes.php" APP_EVENTS_CACHE="$QA_CACHE/events.php" APP_PACKAGES_CACHE="$QA_CACHE/packages.php" APP_SERVICES_CACHE="$QA_CACHE/services.php"
rm -f "$QA_CACHE"/*.php

docker exec dbd-mysql mysql -uroot -psecret -e "DROP DATABASE IF EXISTS duncanbd_qa; CREATE DATABASE duncanbd_qa CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;" 2>/dev/null
rm -rf storage/app/public/* storage/app/private/documents storage/framework/cache/data/*
php artisan migrate --force --seed >/dev/null
php artisan db:seed --class=QaDemoSeeder --force >/dev/null
php artisan storage:link --force >/dev/null 2>&1 || true
php artisan optimize >/dev/null

echo "QA site ready: $APP_URL  (admin: $ADMIN_SEED_EMAIL / $ADMIN_SEED_PASSWORD)"
cd public
exec php -S 127.0.0.1:8123 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
