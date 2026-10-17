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

## Project structure and operations

The public website, public portfolios and private admin workspace have separate route files, controllers and frontend page folders. Authentication, accounts and shared code are grouped separately. See the [developer structure guide](docs/ARCHITECTURE.md) for the directory map and compatibility rules, including why historical public image paths remain available.

Follow [deployment and rollback](docs/DEPLOYMENT.md) for production setup. CI lives in `.github/workflows/modernization.yml`; deployment is manual.
