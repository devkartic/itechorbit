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

## Technology catalog

Website Control → Technologies manages the shared catalog used by homepage features, service filters, and service-card labels. Assign entries through the Technologies selector in each service editor. The data flow is `website_technologies → website_service_technology → TechnologyCatalog → public pages`; public filtering does not depend on a hardcoded grouping list.

Each entry has its own active status, display order, homepage visibility, and services-filter visibility. Public lists show only active entries attached to published services. The homepage starts with Laravel, PHP, React, TypeScript, REST APIs, and SQL; administrators can change these choices. Homepage “All” clears the local selection. Services filters use `/service/info?technology=<slug>`.

The technology editor also supports merging into another entry. Confirming a merge moves service assignments and previous names/links to the target, keeps the target’s visibility settings, and removes the source. Renaming a slug preserves the previous link automatically. Assigned entries cannot be deleted without removing their assignments or merging them first. Merging requires both edit and delete permissions.

The catalog consolidation migration creates 15 initial entries and stores original records and relationships under `storage/app/private/technology-backups/` before merging legacy children. It is a data migration: rollback requires restoring the corresponding JSON snapshot, not a generic migration rollback. Existing technology slugs and former `?group=` links redirect to canonical technology URLs through database aliases.

After consolidation, seed/import code resolves existing names or aliases and reports unknown technologies rather than creating records. Create unknown entries in Website Control first. Initial names and legacy mappings live in `database/data/technology_catalog.php` for migration/install purposes; subsequent admin edits are not overwritten by seeders.

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
