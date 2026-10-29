# Development

Shifty is a Laravel app with a Vue 3 front end glued together by Inertia, so there is no separate API: controllers render Vue pages and pass them props. Routes are typed on the front end through Wayfinder, which generates TypeScript helpers from the Laravel routes into `resources/js/routes` and `resources/js/actions`.

## Running it locally

You need PHP 8.4, Composer and Node 24. SQLite is enough for development.

```bash
composer install && npm install
```

```bash
cp .env.example .env && php artisan key:generate
```

In `.env`, set `APP_ENV=local`, `APP_DEBUG=true` and `APP_URL=http://localhost:8000`, then:

```bash
php artisan migrate --seed && composer dev
```

`composer dev` starts the PHP server, Vite and a log viewer together. The seeder adds an admin, four volunteers and two weeks of shifts.

With a local environment, debug mode on and the request coming from your own machine, the sign-in page shows a **Debug login** box that signs in as any user without a password. All three conditions must hold, so it never appears on a production install.

Laravel Sail works too if you prefer Docker for development: the `docker-compose.yml` in the root is the Sail setup.

## Wayfinder

After adding or renaming a route, regenerate the helpers so the type check sees them. Vite does this on its own while `npm run dev` runs.

```bash
php artisan wayfinder:generate
```

## Checks

CI runs these, and a pull request should pass all of them:

```bash
vendor/bin/pint --test && npx eslint . && npx vue-tsc --noEmit && php artisan test
```

The test suite runs on SQLite by default. CI also runs it against MariaDB and PostgreSQL; set `DB_CONNECTION` and the other `DB_*` variables to do the same locally.
