# iTechOrbit

Laravel 13 + React 19 + TypeScript + Inertia 3, with Vite 8 and Tailwind 4.
Existing MySQL schema, custom permissions and public article/profile URLs are retained.

## Development

Requirements: PHP >=8.3 (production target: latest stable PHP 8.5 patch after staging validation), Composer 2, Node 24 LTS (minimum 22.12), npm, PDO MySQL, mbstring, DOM/XML, fileinfo and GD. SQLite is required for isolated tests. Redis is optional.

```sh
composer install
npm ci
cp .env.example .env
php artisan key:generate
npm run build
php artisan inertia:start-ssr
```

Run `php artisan serve` in another terminal. Configure `.env` with an explicitly disposable development database before running any migrations. Never replace an existing `.env` or APP_KEY. Existing databases and uploads must be imported/restored only through an approved staging process. Do not run demonstration seeders against existing data.

For frontend editing run `npm run dev`; SSR uses the compiled bundle, so rebuild and restart SSR to check server HTML after edits. The public root is `public/`, never the repository root. Production must supervise SSR and the database queue worker.

## Verification

```sh
php artisan test
vendor/bin/pint --test
npm run typecheck
npm run lint
npm test
npm run build
npx playwright install chromium
npm run test:browser
composer audit
npm audit
```

PHP tests force SQLite memory. Browser tests create a new temporary SQLite database, session/cache directories and synthetic fixtures, then clean them up. They need free localhost ports 8010 and 13714. Never point browser fixtures at a real database.

## Operations and architecture

- [Audit](docs/AUDIT.md), [architecture](docs/ARCHITECTURE.md), [migration plan](docs/MIGRATION_PLAN.md)
- [Progress checklist](docs/IMPLEMENTATION_CHECKLIST.md)
- [Deployment and rollback](docs/DEPLOYMENT.md)
- [Verification report](docs/COMPLETION_REPORT.md)
- [Website cleanup record](docs/CLEANUP.md)

CI is supplied in `.github/workflows/modernization.yml`, using paths relative to this standalone application repository. No automatic deployment is configured.
# itechorbit
