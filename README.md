# Duncan Brothers (Bangladesh) Ltd — Corporate Website

Laravel 13 · Blade · MySQL 8.4 · PHP 8.4. See [docs/architecture-and-plan.md](docs/architecture-and-plan.md).

## Local setup

```sh
composer install && npm install
cp .env.example .env && php artisan key:generate
# set DB_* and ADMIN_SEED_EMAIL / ADMIN_SEED_PASSWORD in .env
php artisan migrate --seed
npm run dev   # and: php artisan serve
```

The admin panel lives at `/{ADMIN_PATH}` (default `/dbbd-console`). The seeded Super Admin must set up
two-factor authentication on first sign-in and change the seed password.

## Quality gates

```sh
./vendor/bin/pint --test && ./vendor/bin/phpstan analyse && ./vendor/bin/pest
```

## Status

Phases 2–4 are built: foundation, core CMS, and content modules (documents, news, businesses & estates, people, timeline). Starter page structure: `php artisan db:seed --class=StarterContentSeeder`. See the plan for what remains.
