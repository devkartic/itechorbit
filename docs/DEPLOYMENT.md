# Staging, deployment and rollback

No production deployment or migration has been performed. Local tests use synthetic isolated SQLite databases; production data preservation is a release gate, not an established result.

## Runtime and infrastructure

Laravel 13 requires PHP >=8.3. The target runtime is the latest stable PHP 8.5 patch, with Composer platform checks and the full regression suite on staging. This workstation runs PHP 8.3.3; do not use that old patch in production. PHP's official support table lists 8.5 as actively supported: https://www.php.net/supported-versions.php. Node 24 LTS is used locally and in CI. Use the locked Composer/npm dependencies, not unbounded updates during deployment.

Preserve the existing database engine, APP_KEY, uploads and environment configuration. Confirm actual MySQL/MariaDB version, collation, SQL mode and schema before release. Supply mbstring, DOM/XML, fileinfo, PDO MySQL, OpenSSL and GD; PDO SQLite is needed for tests. Redis is optional and must have its PHP driver if enabled. Existing contact mail uses database queues; supervise the worker. Email notices also require the dedicated worker and scheduler described below.

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

The application now has a standalone Git repository at `/Users/karticgharami/Workspace/projects/itechorbit`. Its `.github/workflows/modernization.yml` uses repository-root working-directory, dependency-cache and artifact paths. CI has no deployment credentials or deployment step; PHP 8.5 CI execution remains unverified until GitHub runs it.

## Browser history encryption and local HTTP

Inertia history encryption requires `window.crypto.subtle` in a secure browser context (https://inertiajs.com/docs/v3/security/history-encryption). Custom development origins such as `http://itechorbit.local` do not have that API. Configuration now defaults encryption off for local/testing and on for APP_ENV=production; `INERTIA_HISTORY_ENCRYPT` can explicitly override it. Use HTTPS and trusted-proxy configuration in production, preserving its default privacy protection. After changing environment/configuration, clear/rebuild the configuration cache as appropriate and reload the page. Do not patch vendor JavaScript or disable CSRF/authentication to work around this error. Locally the configuration cache was cleared and client/SSR assets rebuilt.

## Administrative email notices

Apply `2026_10_17_000004_register_workspace_navigation.php` with this release. It registers the previously hardcoded Overview, Portfolio templates and email notice links in the existing navigation tables, and fills absent resource entries. Existing navigation records and permission assignments are preserved. The sidebar follows the names, parent groups/modules, active states and order numbers managed through Navigation groups, Navigation modules and Navigation links. Overview and Portfolio templates initially appear under Workspace → General. Hiding an entry changes navigation visibility; page access remains governed by the existing authorization checks. Email notices retain their Admin/Super Admin restriction regardless of sidebar configuration. The data migration intentionally retains records on rollback because administrators may have edited them or assigned permissions.

Review and apply the additive `2026_10_17_000003_create_mail_notices_tables.php` migration during deployment. Configure a real mail transport and sender using the existing `MAIL_*` environment variables. An asynchronous `QUEUE_CONNECTION` is required; log/array mailers and synchronous queues disable sending. `NOTICE_MAIL_PER_MINUTE` defaults to 30; match it to the provider's limits. Accepted means the provider accepted the message, not that it reached an inbox.

Run the dedicated database worker in `deploy/supervisor.conf.example`: `php artisan queue:work mail_notices --queue=mail-notices --sleep=1 --tries=3 --timeout=60 --max-time=3600`. This connection deliberately uses the application's default database so that enqueueing and marking each recipient as dispatched commit together. Keep the regular queue worker for other application mail. Restart both workers after deployment.

Run Laravel's scheduler every minute, for example with this crontab entry (adapt the path and PHP binary):

```cron
* * * * * cd /srv/itechorbit/current && /usr/bin/php artisan schedule:run >> /var/log/itechorbit-scheduler.log 2>&1
```

The scheduler dispatches remaining recipient batches and recovers undispatched records after interruptions. `php artisan notices:dispatch` can also resume them manually. Use a shared cache for the global mail rate limiter when workers run on multiple hosts. Monitor the notices ledger, failed jobs and worker logs. Only confirmed failures can be retried from the page; unconfirmed outcomes require checking provider logs to avoid sending duplicate messages. Templates can be changed or deleted without altering past campaign snapshots. Use synthetic recipients and a non-relaying SMTP sink for local verification; do not validate a deployment by broadcasting to real users.
