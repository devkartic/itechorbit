# iTechOrbit

Laravel 13, React 19, TypeScript and Inertia 3 application with public articles, personal portfolios and an administration workspace.

## Requirements and setup

PHP >=8.3 with PDO MySQL, mbstring, DOM/XML, fileinfo and GD; Composer 2; Node >=22.12 (Node 24 used in CI); npm. Tests also require PDO SQLite. Install the locked dependencies:

```sh
composer install
npm ci
```

For a new local installation, copy `.env.example` to `.env`, configure a disposable development database, and generate a new application key. For an existing installation, preserve its `.env`, `APP_KEY`, database and uploaded media. Apply reviewed migrations without demonstration seeders.

```sh
npm run build
php artisan serve
php artisan inertia:start-ssr
```

Run the HTTP server and SSR process in separate terminals. `npm run dev` enables frontend development; SSR uses the compiled bundle. The web server document root must be `public/`.

## Verification

```sh
composer validate --strict
composer audit
npm audit
vendor/bin/pint --test
php artisan test
npm run typecheck
npm run lint
npm test
npm run build
npx playwright install chromium
npm run test:browser
```

PHP tests use isolated SQLite memory. Browser tests use a temporary SQLite database, synthetic uploads and intercepted OAuth navigation, requiring free ports 8010 and 13714. Do not point fixtures at an application database.

## Production

Follow the [deployment guide](docs/DEPLOYMENT.md) for configuration, migrations, OAuth, workers, licensing and rollback. Server examples are in `deploy/`. CI verifies changes without automatically deploying them.
