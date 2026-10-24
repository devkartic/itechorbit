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
npm run serve
```

`npm run serve` starts the local production preview at `http://127.0.0.1:8000`, waits for the configured SSR service to become ready, and stops both processes on Ctrl+C. Stop any existing SSR service first and rebuild after frontend changes. Use this production preview for mobile performance measurements. `npm run dev` starts Vite at `http://127.0.0.1:5173` for live frontend updates while Laravel remains at the configured `APP_URL` (locally `http://itechorbit.local`). Keep the command running during development. Local requests use client rendering while `public/hot` exists because this Vite configuration does not supply an Inertia SSR endpoint. Stopping Vite removes its hot file and returns Laravel to the built assets and configured SSR service; rebuild before previewing those assets. If Vite exits unexpectedly, check that port 5173 is stopped before removing a stale `public/hot`. Internal application links use Inertia navigation; external links and downloads retain normal browser navigation. Development timings are not performance baselines. The web server document root must be `public/`.

### Blank page after reloading locally

If Vite exits unexpectedly, `public/hot` can remain and make Laravel load scripts from a stopped server. With local SSR disabled in hot mode, this leaves an empty page. Check `lsof -nP -iTCP:5173 -sTCP:LISTEN`; once no Vite process is listening, remove the stale marker and rebuild:

```sh
rm -f public/hot
npm run build
```

For the Docker workspace at `http://itechorbit.local`, run `npm run ssr:docker` after rebuilding to refresh the SSR service, then reload the page. Keep the existing `.env` and database. To resume live frontend updates, run `npm run dev` and keep it running.

## Google integrations

Set these optional values in `.env`; blank values disable the corresponding integration:

```dotenv
GOOGLE_TAG_MANAGER_ID=GTM-XXXXXXX
GOOGLE_ANALYTICS_ID=G-XXXXXXXXXX
GOOGLE_SITE_VERIFICATION=your-search-console-verification-token
```

Tag Manager loads in the document head with its fallback immediately after the opening body tag. Direct GA4 loads in the head, and Search Console verification appears as a server-rendered meta tag. These integrations apply only to public website and portfolio pages, excluding authentication, account, and admin pages. Search Console uses the HTML-tag verification token, not an Analytics Measurement ID. AdSense configuration does not load an advertising script.

Use either direct GA4 through `GOOGLE_ANALYTICS_ID` or GA4 configured inside Tag Manager. When using the latter, leave `GOOGLE_ANALYTICS_ID` blank to avoid duplicate tracking. For client-side navigation, enable GA4 enhanced measurement page views based on browser history changes; no manual page-view events are added. Configuring both IDs loads both integrations independently.

The Content Security Policy permits the configured Google integrations on public pages and uses script nonces. Additional services or custom tags installed through Tag Manager may require their own CSP endpoints; preview/debug and advertising features are not enabled by this configuration.

After changing environment values in a deployment using cached configuration, run `php artisan config:cache`. Restart long-running application workers if applicable. Environment configuration requires no frontend rebuild. Installation references: [Tag Manager](https://developers.google.com/tag-platform/tag-manager/web), [Search Console verification](https://support.google.com/webmasters/answer/9008080), and [GA4 single-page applications](https://developers.google.com/analytics/devguides/collection/ga4/single-page-applications).

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

See [loading performance](docs/PERFORMANCE.md) for repeatable mobile benchmarks, local preview setup, and compression/cache requirements.

The public website, public portfolios and private admin workspace have separate route files, controllers and frontend page folders. Authentication, accounts and shared code are grouped separately. See the [developer structure guide](docs/ARCHITECTURE.md) for the directory map and compatibility rules, including why historical public image paths remain available.

Follow [deployment and rollback](docs/DEPLOYMENT.md) for production setup, including the **Deploy to VPS** GitHub Actions workflow. CI lives in `.github/workflows/modernization.yml`; deployment is started manually from GitHub Actions.
