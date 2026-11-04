# Deployment guide

Target: one Ubuntu 24.04 LTS VM (4 vCPU, 8 GB RAM) behind Cloudflare, as in the architecture plan §13.
Everything here is exercised by the drills in [qa-report.md](qa-report.md) except the real-server steps marked ⚠.

## 1. Server prerequisites

| Component | Notes |
|---|---|
| PHP 8.4-FPM, OPcache on (`opcache.validate_timestamps=0` in production, reload FPM on deploy) | Extensions: `mbstring pdo_mysql redis gd (or imagick) exif fileinfo bcmath xml curl zip` (`intl` not required) |
| MySQL 8.4 | `utf8mb4_0900_ai_ci`; a dedicated user with rights on one database; daily backups |
| Redis 7 | cache, sessions, queue (`REDIS_CLIENT=phpredis`) |
| nginx | use `deploy/nginx.conf` (headers, caching, rate limits, dotfile blocking) |
| Supervisor | `deploy/supervisor-queue.conf` (2 queue workers) |
| Cron | `* * * * * www-data cd /var/www/duncanbd/current && php artisan schedule:run >> /dev/null 2>&1` |
| Composer 2, Git | Node is only needed on the build machine; ship built assets (`SKIP_BUILD=1`) or build on the server |

Optional but recommended: ClamAV for upload scanning (plan §10), unattended security upgrades, fail2ban.

## 2. One-time setup

```sh
sudo mkdir -p /var/www/duncanbd/shared && sudo chown -R deploy:www-data /var/www/duncanbd
cp .env.example /var/www/duncanbd/shared/.env && chmod 600 /var/www/duncanbd/shared/.env   # then edit, see §3
```

## 3. Production `.env` essentials

```
APP_ENV=production   APP_DEBUG=false   APP_URL=https://duncanbd.com   APP_KEY=<php artisan key:generate --show>
DB_*  REDIS_*  SESSION_DRIVER=redis  CACHE_STORE=redis  QUEUE_CONNECTION=redis  RESPONSE_CACHE_DRIVER=redis
SESSION_SECURE_COOKIE=true  SESSION_SAME_SITE=lax
ADMIN_PATH=<non-obvious>   ADMIN_IP_ALLOWLIST=<office/VPN ranges>   ADMIN_SEED_EMAIL / ADMIN_SEED_PASSWORD (first deploy only, then remove)
TRUSTED_PROXIES=<Cloudflare ranges or *>        # behind Cloudflare, or every visitor shares one IP
BACKUP_ARCHIVE_PASSWORD=<long random, also in the vault>   BACKUP_DISKS=backups,s3   BACKUP_NOTIFY_EMAIL=it@…
MAIL_* (or set SMTP in Admin > Settings)   TURNSTILE_SITE_KEY / TURNSTILE_SECRET (optional)   CSP_ALLOW_ANALYTICS=true only if analytics is used
```

`ADMIN_SEED_*` creates the first Super Admin on `db:seed`; that person must set up two-factor authentication at first
sign-in. Keep at most two Super Admins.

## 4. Deploy and roll back

```sh
export DEPLOY_PATH=/var/www/duncanbd REPO_URL=git@github.com:<org>/duncanbd.git
deploy/deploy.sh deploy v1.0.0     # clone, composer --no-dev -o -a, build, migrate, cache, atomic switch, smoke test, prune
deploy/deploy.sh status
deploy/deploy.sh rollback          # previous release, < 1 s; migrations are NOT undone, so keep them backward compatible
```

First deploy only: `php artisan db:seed --force` (roles, categories, first admin), then
`php artisan db:seed --class=StarterContentSeeder` if you want the draft page structure, and
`php artisan legacy:import` for migrated content (see [content-migration.md](content-migration.md)).
Never run `QaDemoSeeder` in production (it refuses to).

## 5. After deploy

* `php artisan about` shows `Debug Mode OFF`, caches `CACHED`.
* Admin sign-in works, 2FA enforced; `https://duncanbd.com/<ADMIN_PATH>` returns 404 from outside the allow-list.
* Send a test enquiry, a test application and a newsletter sign-up; confirm all four emails arrive (SPF/DKIM/DMARC pass).
* `php artisan backup:run`, then `php artisan backup:monitor`; confirm the archive appears on the off-site disk.
* ⚠ Run `tools/qa/a11y.mjs`, `lighthouse.mjs` and the ZAP baseline against the real URL (`QA_BASE=https://…`).
