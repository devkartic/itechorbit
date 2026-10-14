# Staging, deployment and rollback

No production deployment or migration has been performed. Local tests use synthetic isolated SQLite databases; production data preservation is a release gate, not an established result.

## Runtime and infrastructure

Laravel 13 requires PHP >=8.3. The target runtime is the latest stable PHP 8.5 patch, with Composer platform checks and the full regression suite on staging. This workstation runs PHP 8.3.3; do not use that old patch in production. PHP's official support table lists 8.5 as actively supported: https://www.php.net/supported-versions.php. Node 24 LTS is used locally and in CI. Use the locked Composer/npm dependencies, not unbounded updates during deployment.

Preserve the existing database engine, APP_KEY, uploads and environment configuration. Confirm actual MySQL/MariaDB version, collation, SQL mode and schema before release. Supply mbstring, DOM/XML, fileinfo, PDO MySQL, OpenSSL and GD; PDO SQLite is needed for tests. Redis is optional and must have its PHP driver if enabled. Existing contact mail uses database queues; supervise the worker. No scheduled application tasks were found, so no scheduler service is introduced.

Use `public/` as document root. Example Nginx and Supervisor configurations are in `deploy/`; adapt paths, PHP socket, service users, HTTPS and logging retention to the operator's infrastructure. Bind SSR to loopback port 13714 and supervise it. `/healthz` proves the HTTP application boots; it does not prove database, mail, queue or SSR readiness. Check a public page for actual rendered `<h1>` and canonical tags to monitor SSR.

## Required operator evidence

- Approved, encrypted database and media backups with checksums and retention outside the web root.
- Restore rehearsal onto a disposable staging database and storage volume. Compare record counts, primary keys, article URLs, portfolio keys and media-file checksums. Validate authentication and permission assignments without logging personal records.
- Production URL inventory from sitemap, analytics/search-console exports or a bounded public crawl; compare legacy URLs to staging status, canonical and redirect behavior.
- Staging SMTP, Google OAuth callback/state, queue delivery/retry and proxy/TLS behavior verified with test accounts.
- Explicit approval for the additive Sanctum `expires_at` migration and any production deployment. It must not be auto-applied simply because it is committed.

## Staging rehearsal

1. Create an isolated release directory and use an approved staging database clone, never the live database. Disable outbound mail/OAuth initially; use mail log or a test inbox. Keep staging private with network authentication and an `X-Robots-Tag: noindex` at the proxy.
2. Install dependencies with `composer install --no-interaction --prefer-dist` and `npm ci`. Run `composer check-platform-reqs`, `composer audit`, `npm audit`, PHP tests, Pint, TypeScript, lint and Vitest. Build client + SSR with `npm run build`; run Playwright with its temporary synthetic database.
3. Configure the staging environment separately. Review `php artisan migrate:status` and `php artisan migrate --pretend`; approve the schema delta before applying `migrate --force` on staging. Never use fresh/wipe/seed commands on existing data.
4. Use shared, writable media/storage outside release directories. Preserve relative `uploads/...` references. Verify PHP files cannot execute inside uploads; verify only expected public CV/media files are accessible.
5. Set APP_ENV=production, APP_DEBUG=false, canonical APP_URL to the intended site domain, SESSION_SECURE_COOKIE=true, INERTIA_SSR_ENABLED=true and INERTIA_SSR_THROW_ON_ERROR=true. Keep the original APP_KEY and explicit session/cache prefixes. Supply the original Google verification token; optional analytics/AdSense need operator consent/configuration review before activation.
6. Cache config/routes/views in the release directory. Start supervised SSR and queue services. Confirm server HTML, authentication, admin CRUD on disposable records, form delivery, search/pagination, sitemap XML and existing media.
7. Measure Lighthouse on representative staging pages and collect real-user Core Web Vitals after approved release. Synthetic local scores are not production results.

## Approved production release

After all evidence and explicit approval: build a versioned release, link existing environment/shared media/storage, review pending migration SQL and take a recoverable backup. Apply only approved additive migrations, atomically switch the release symlink, restart PHP/SSR services and run `php artisan queue:restart`. Queue `retry_after` must exceed worker timeout (example worker: 60s). Run immediate HTTP/crawler/auth/form checks. Keep the previous release available. Do not copy `.env.example` over production `.env` or regenerate its key.

## Rollback

Switch to the prior release and its locked dependency/assets/SSR bundle, restart PHP and SSR, and restart workers. Clear or rebuild compiled caches for that release. Leave the additive nullable token column in place unless an operator-approved rollback was rehearsed; old code ignores it. Do not roll back by dropping tables or overwriting current records. If data recovery is required, restore backups into a separate database first, verify counts/checksums and obtain approval for any live replacement. Preserve APP_KEY and shared uploads throughout.

## CI layout

The actual Git root is `/Users/karticgharami/Workspace`. The installed workflow is `.github/workflows/itechorbit-modernization.yml` at that root and runs only this project's checks, using `projects/itechorbit` paths. A copy lives in this application's `.github/workflows/modernization.yml`. When extracting a standalone repository, update working-directory/cache/artifact paths. CI has no deployment credentials or deployment step; PHP 8.5 CI execution remains unverified until GitHub runs it.

## Browser history encryption and local HTTP

Inertia history encryption requires `window.crypto.subtle` in a secure browser context (https://inertiajs.com/docs/v3/security/history-encryption). Custom development origins such as `http://itechorbit.local` do not have that API. Configuration now defaults encryption off for local/testing and on for APP_ENV=production; `INERTIA_HISTORY_ENCRYPT` can explicitly override it. Use HTTPS and trusted-proxy configuration in production, preserving its default privacy protection. After changing environment/configuration, clear/rebuild the configuration cache as appropriate and reload the page. Do not patch vendor JavaScript or disable CSRF/authentication to work around this error. Locally the configuration cache was cleared and client/SSR assets rebuilt.
