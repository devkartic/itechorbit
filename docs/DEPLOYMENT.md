# Deployment

## Deploy from GitHub Actions to a VPS

The manual workflow defaults to **test** mode: it builds with the installed PHP/Node images and uploads the result to `/srv/Workspace/projects/itechorbit/src/test-deploy`, without activating it. It does not use the production `.env`, run migrations, change maintenance mode, restart containers, or check the production health URL. This is a file-transfer/build test, not a separate running website. A browser-accessible staging site needs its own containers, environment, database and routing.

Choose **live** mode only when updating the running application. In live mode, the workflow runs `deploy/deploy-vps.sh` against the existing Docker containers. It builds with the exact PHP and Node images already installed on the VPS, backs up the previous code, enables maintenance mode, synchronizes the bind-mounted source, optionally migrates, rebuilds caches, and restarts PHP, Nginx and SSR. It checks SSR before reopening the application, then checks the public health URL. Run verification for the application commit before deploying.

The **Branch to build and deploy** input defaults to `production-version`. **Use workflow from** chooses the workflow and deployment script; the branch input chooses application code. The deployment script comes from the workflow branch, so the application branch does not need to contain it.

### Existing VPS Docker layout

This script targets the inspected running containers:

| Container | Role |
| --- | --- |
| `php-workspace-production-itechorbit-php-1` | PHP 8.5 and Composer |
| `php-workspace-production-itechorbit-nginx-1` | Web server |
| `itechorbit-local-ssr-1` | Node 24 and Inertia SSR |

All three must mount `/srv/Workspace/projects/itechorbit/src` as their source. The script verifies those mounts before updating files. In test mode, `VPS_PATH` must be that source path followed by `/test-deploy`. In live mode, it must equal the source mount itself. Live synchronization excludes `test-deploy` and `test-deploy.deploy`, preserving test files and avoiding recursive backups. It preserves `.env` and `.env.*`, `public/uploads`, `public/storage`, `storage`, `bootstrap/cache`, `.git`, `.docker` and `logs`. Existing storage/cache named volumes remain attached. MariaDB, Redis, Caddy and access containers are not restarted or recreated. This workflow updates application code and dependencies using existing images; changes to PHP extensions, container configuration, Dockerfiles or Compose require a separate workspace image rebuild. Existing queue workers receive Laravel's `queue:restart` signal; the workflow does not provision workers or a scheduler.

### One-time VPS setup

Keep the existing Docker stack, production `.env`, `APP_KEY`, database and uploads. There is no host PHP-FPM or Supervisor requirement. `PHP_FPM_SERVICE` is not used.

For your test deployment, run these commands as root on the VPS:

```sh
apt update
apt install -y rsync util-linux acl
usermod -aG docker deploy
install -d -o deploy -g deploy -m 755 \
  /srv/Workspace/projects/itechorbit/src/test-deploy \
  /srv/Workspace/projects/itechorbit/src/test-deploy.deploy
```

Docker group access gives the deployment user administrative control of the host. Reconnect SSH after adding group membership. Ensure the user can traverse every parent directory. Test `docker ps` and source access from that user's SSH session. PHP artisan commands run inside the existing PHP container as `www-data`, which must already have write access to the mounted storage and bootstrap-cache volumes.

`VPS_PATH` is the destination source directory: use `/srv/Workspace/projects/itechorbit/src/test-deploy` for test mode. The workflow derives its temporary build directory automatically by appending `.deploy`. No additional path variable is needed.

For live mode, change `VPS_PATH` to `/srv/Workspace/projects/itechorbit/src`. As root, prepare the corresponding staging directory and grant source-write access first:

```sh
install -d -o deploy -g deploy -m 755 /srv/Workspace/projects/itechorbit/src.deploy
setfacl -R -m u:deploy:rwX /srv/Workspace/projects/itechorbit/src
```

The ACL command grants source access without changing existing ownership or application permissions. Back up the database and uploads separately before migrating. Code backups under this staging root exclude secrets, uploads and runtime storage; they are not database or media backups. The staging root needs enough space for the new build and the previous code, including dependencies.

### GitHub configuration

Create a GitHub environment named `production` under **Settings → Environments**. Add these environment secrets:

| Secret | Value |
| --- | --- |
| `VPS_HOST` | VPS hostname or IPv4 address |
| `VPS_USER` | SSH deployment username, for example `deploy` |
| `VPS_PORT` | SSH port; optional, defaults to `22` |
| `VPS_SSH_KEY` | Complete private SSH key without a passphrase; matching public key goes in the deployment user's `authorized_keys` |
| `VPS_KNOWN_HOSTS` | Server public host key copied from the trusted VPS console, or verified known-hosts entries matching the configured host and port |

The exact secret name is `VPS_KNOWN_HOSTS`, not `VPS_KNON_HOSTS`. In the trusted VPS console, run `cat /etc/ssh/ssh_host_ed25519_key.pub` and copy the complete single line beginning `ssh-ed25519` into this secret. This is the server's public identity key, different from your deployment user's authorized key. The workflow associates a bare public host key with `VPS_HOST` and `VPS_PORT` automatically. Existing known-hosts entries must match those values exactly (including `[host]:port` for a nonstandard port). Obtain keys through a trusted server console or independently verify their fingerprints before saving. Host-key verification remains enforced; unknown keys are never accepted automatically.

Add these environment **variables**:

| Variable | Example |
| --- | --- |
| `VPS_PATH` | `/srv/Workspace/projects/itechorbit/src/test-deploy` for test mode; `/srv/Workspace/projects/itechorbit/src` for live mode |
| `HEALTHCHECK_URL` | `https://itechorbit.com/healthz` (used only in live mode) |

Commit and push the workflow, script and documentation. The workflow must exist on the repository's default branch to appear for manual runs. Open **Actions → Deploy to VPS → Run workflow**, select the branch containing the updated workflow under **Use workflow from**, leave **Branch to build and deploy** as `production-version` or enter another verified branch, choose **test** mode with migrations unchecked for your test-directory deployment. For **live** mode, use the live source path and enable migrations only after reviewing pending migrations and backing up the database. No deployment runs automatically on push. No credentials are stored in the repository.

In test mode, successful builds synchronize only into the test destination and skip live activation and health checks. In live mode, build failures leave live application code unchanged. Activation uses an in-place source sync during maintenance because the containers bind-mount a fixed directory; it is not an atomic symlink switch. An activation failure leaves maintenance enabled and may leave SSR stopped. Inspect the failure before recovery. The script prints the previous-code backup path. To restore code, use the same rsync exclusions from `deploy/deploy-vps.sh`, rebuild Laravel caches, restart the three application containers, verify SSR, then run `docker exec --user www-data php-workspace-production-itechorbit-php-1 php artisan up`. Never restore excluded paths from a code backup or automatically roll back migrations. A later public health failure leaves the new deployment active for inspection. Retained builds and code backups require periodic manual cleanup.

The sections below describe application requirements and general deployment/rollback procedures. Native Nginx/Supervisor examples are for hosts without this Docker workspace. For this Docker setup, run application PHP commands inside the PHP container and use the existing Compose networking; the SSR URL in `.env` must resolve to the SSR service from PHP (for example `http://itechorbit-ssr:13714`), rather than host loopback.

## Runtime and configuration

Use PHP >=8.3 with Composer's platform requirements satisfied, Node >=22.12 for asset builds (CI uses Node 24), and the locked Composer/npm dependencies. PHP needs PDO MySQL, mbstring, DOM/XML, OpenSSL, fileinfo and GD. Enable the matching Redis driver only if Redis is configured. Validate the actual MySQL/MariaDB version, collation and SQL mode in staging.

Use `public/` as the web root. Adapt the Nginx and Supervisor examples in `deploy/` to the deployment paths, service users, PHP socket and TLS setup. Upload directories must never execute PHP. Keep `.env`, the original `APP_KEY`, database and shared uploads/storage outside replaceable release directories. Only storage and bootstrap cache directories need application write access; preserve existing upload storage permissions.

Production settings include:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://itechorbit.com
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database
INERTIA_SSR_ENABLED=true
INERTIA_SSR_URL=http://127.0.0.1:13714
INERTIA_SSR_THROW_ON_ERROR=true
```

Use the actual canonical host, configured mail transport, database credentials and site owner ID. Do not copy `.env.example` over an existing environment or regenerate its key. Inertia history encryption defaults on in production and requires HTTPS; its environment override is `INERTIA_HISTORY_ENCRYPT`. Configure trusted proxies for the actual hosting environment. Analytics and advertising integrations remain optional settings.

## Build and release

1. Back up the database and media, and verify restoration in a separate staging environment. Keep staging private/noindex with outbound integrations restricted to test accounts.
2. Run CI checks with development dependencies and disposable test data. In a new release directory, install production PHP dependencies and build the client/SSR bundles:

   ```sh
   composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
   composer check-platform-reqs --no-dev
   npm ci
   npm run build
   ```

   Node build tools are needed during the build. Do not omit npm development dependencies before building. Preserve the Node packages needed by the SSR bundle; validate dependency pruning against SSR before using it in deployment.
3. Link the existing environment and shared media/storage into the release. Review `php artisan migrate:status` and `php artisan migrate --pretend`, then apply approved migrations using `php artisan migrate --force`. Never use database fresh/wipe/seed commands on existing application data.
4. Build release caches:

   ```sh
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

5. Switch the release atomically and restart PHP/SSR services and queue workers. Start SSR with `php artisan inertia:start-ssr`; supervise it and `php artisan queue:work`. Keep SSR on loopback. Queue `retry_after` must exceed the worker timeout. Use `php artisan queue:restart` for existing workers. Run the scheduler every minute and supervise the dedicated email notice worker as described below.
6. Smoke-test public pages and server-rendered headings, metadata/canonicals, sign-in, account permissions, admin CRUD, uploads/PDF previews, all portfolio designs, contact forms, and email delivery/retry using staging accounts. `/healthz` confirms application boot only; separately verify database, SSR and queue readiness.

Do not deploy `.git`, test reports, local logs, development databases or credentials in public directories. Tests and CI remain in the source branch for maintenance; they are not runtime dependencies.

## Database migrations and data preservation

Keep migrations dated October 17, 2026 and earlier unchanged. Migrations after that date are consolidated as described below. Review pending changes against the existing schema. Current upgrades include the nullable Sanctum token expiry, portfolio template preference, widened privacy-policy content, social-account connections, email notice tables and database-managed workspace navigation.

The privacy-policy migration widens content to LONGTEXT and intentionally does not shrink it on rollback. The social-account migration retains `users.provider_id`, copies known Google identities and refuses duplicate legacy identities before creating its table. Resolve identified duplicate identities before retrying; do not delete user accounts to make the migration pass. Portfolio template values default safely to Studio when unset.

Preserve uploads, public CV visibility, existing article/portfolio URLs, permissions and primary keys across releases. Historical public images and fonts are retained because stored content can reference their URLs. Compare media checksums and record counts during staging rehearsal.

### Consolidated migrations after October 17, 2026

The eleven migration files from October 18–23 are replaced by four feature migrations:

| Migration | Responsibility |
| --- | --- |
| `2026_10_18_000002_create_ai_pro_tables.php` | Independent AI assistant tables; unchanged. |
| `2026_10_24_000001_upgrade_website_catalog.php` | Legacy service expansion, separate website products and the one-time marketing refresh. |
| `2026_10_24_000002_upgrade_website_content.php` | Website contact/about renaming, structured contact fields and transfer of any temporary contact profile. |
| `2026_10_24_000003_upgrade_website_technologies.php` | Technology tables, service assignments, aliases, canonical catalogue and navigation. |

Before deployment, take a database backup and keep the matching pre-upgrade code revision. Rehearse on a restored staging database, then run `php artisan migrate --force`. Do not reset the database, delete migration-history rows or manually mark the new migrations as applied. Existing historical rows stay intact. The new runners inspect the current schema and existing migration history: completed schema changes are skipped, completed marketing refreshes are not repeated, and saved content, assignments, publication settings and navigation permissions remain intact. Fresh installations add fields directly to `website_contact`; they never create the discarded contact-details table or menu.

Upgrade transformations live in `app/Support/Database` and are shared by the consolidated runners and upgrade tests. The tests cover fresh installation, the October 17 baseline, partial and fully migrated historical databases, contact transfer and repeated execution.

**Rollback boundary:** the consolidated runners deliberately refuse `migrate:rollback`, `migrate:reset` and migration-based refresh to prevent removing previously existing data. Restore the pre-upgrade database backup together with the corresponding code revision instead. Historical migration rows may reference removed files; keep them as history, not runnable rollback targets. Use a newly created disposable database for tests or installation rehearsal, never an application database.

## Website service catalogue

The consolidated `2026_10_24_000001_upgrade_website_catalog.php` migration refreshes the nine original service descriptions once, retaining their IDs, slugs, media, publication status and highlight choices. It also orders any existing growth-service records first. It skips the refresh when either the historical refresh or consolidated catalogue migration has already completed, preserving later admin edits. Rollback uses the backup procedure above.

After applying reviewed migrations, add the five new offerings with the dedicated, repeatable seeder:

```sh
php artisan db:seed --class=WebsiteGrowthServicesSeeder --force
```

This publishes Website Speed Optimization, AI-Generated Code Review & Fixing, Technical SEO Optimization, Google Analytics & Tag Manager, and Website Security Audit when their slugs are absent. Subsequent seeder runs preserve workspace edits and publication choices. Do not run the general demonstration database seeder against an existing application. Prices and delivery commitments remain scoped through the inquiry process.

## Website technologies

Apply `2026_10_24_000003_upgrade_website_technologies.php` with the reviewed migrations. They add the technology catalogue, service pivot relationships and a Technologies link beside Services in Website Control (the existing Site Control module). Existing navigation permissions are retained; Super Admin has access, and other roles need the usual index/create/edit/destroy permissions for Technologies.

The initial import trims technology names, merges case-insensitive duplicates, maps REST to REST APIs and MySQL to SQL, and links all existing services without changing their content or publication settings. The original service `technologies` JSON column remains a snapshot; public pages and service editing use the relationships. Products and portfolio skills retain their existing independent storage. Newly created growth-service seed records also import their initial assignments; re-running the seeder preserves existing assignments and administrator edits.

Manage names, slugs, active status, homepage visibility and display order in Technologies. Assign technologies in the service editor's searchable selector. Inactive assignments remain editable but are hidden publicly; remove assignments before deleting a technology. The six original toolkit entries are selected for homepage display initially, and only entries connected to published services appear there.

Homepage buttons filter its first six matching cards in place. Services-page links use `/service/info?technology=laravel`; selected filters survive refresh and browser history, use the unfiltered canonical URL and are marked noindex. Unknown or inactive slugs return 404. After building, restart SSR and verify both public sections and the admin selector. The consolidated migration preserves existing catalogue and navigation settings. To reverse the upgrade, restore the database backup and matching code as described above.

## Social sign-in

Set the server-side client ID, secret and callback URL for each enabled provider. Buttons appear only with complete configuration and the social-account migration applied.

| Provider | Settings | Callback for the canonical host |
| --- | --- | --- |
| Google | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` | `https://itechorbit.com/login/google/callback` |
| LinkedIn | `LINKEDIN_CLIENT_ID`, `LINKEDIN_CLIENT_SECRET`, `LINKEDIN_REDIRECT_URI` | `https://itechorbit.com/login/linkedin/callback` |
| GitHub | `GITHUB_CLIENT_ID`, `GITHUB_CLIENT_SECRET`, `GITHUB_REDIRECT_URI` | `https://itechorbit.com/login/github/callback` |

Register the exact callback URL in each provider console; local/staging applications need separate matching URLs. Google uses a Web application OAuth client and its consent-screen settings. LinkedIn requires **Sign In with LinkedIn using OpenID Connect**, with `openid`, `profile`, and `email` scopes. GitHub uses an OAuth App and the default `user:email` scope. Missing/unverified provider email prevents first-time registration or linking; normal email registration remains available.

Existing users sign in through an established method and connect providers in Account settings. Provider email matching alone does not grant access. Legacy Google identities recorded in `provider_id` are migrated; users without a recorded identity can use password recovery and then connect Google. New social users receive role 4 with portfolio publication disabled. A social user's local password can be set through password recovery. Disconnecting providers is not implemented.

After configuring credentials, refresh the config cache and test real consent, callbacks, session expiry, cancellation and disabled accounts on staging. Mocked tests do not verify provider-console configuration or live token exchange. To disable a provider, remove its configured credentials and refresh the config cache; retain its connection records.

## Email, uploads and editor

Configure and verify the actual SMTP/mail transport and `MAIL_FROM_*` values. Inquiry notifications use the configured sender with the visitor's address as Reply-To. Both personal and business notifications include HTML/plain-text bodies and require the supervised queue worker. Test delivery with a controlled recipient before release; a saved inquiry is not evidence of successful delivery.

Image uploads accept JPG/PNG/WebP up to 2,048 KB; CVs accept PDF up to 5,000 KB. Existing image/PDF previews retain permission and ownership checks. A private admin CV preview does not enable public download. Banner covers appear in all five portfolio templates; originals remain unchanged, with display-only cropping at 4:1 desktop and 2:1 mobile.

CKEditor is self-hosted with `licenseKey: 'GPL'`, using the open-source package without premium/cloud features. Its GPL distribution requires GPL 2-or-later compliance; it is not an unrestricted proprietary lifetime license. Preserve dependency license notices and confirm licensing requirements for distribution/deployment. The application's own license has not been changed.

## Administrative email notices

Apply `2026_10_17_000004_register_workspace_navigation.php` with this release. It registers the previously hardcoded Overview, Portfolio templates and email notice links in the existing navigation tables, and fills absent resource entries. Existing navigation records and permission assignments are preserved. The sidebar follows the names, parent groups/modules, active states and order numbers managed through Navigation groups, Navigation modules and Navigation links. Overview and Portfolio templates initially appear under Workspace → General. Hiding an entry changes navigation visibility; page access remains governed by the existing authorization checks. Email notices retain their Admin/Super Admin restriction regardless of sidebar configuration. The data migration intentionally retains records on rollback because administrators may have edited them or assigned permissions.

Review and apply the additive `2026_10_17_000003_create_mail_notices_tables.php` migration during deployment. Configure a real mail transport and sender using the existing `MAIL_*` environment variables. An asynchronous `QUEUE_CONNECTION` is required; log/array mailers and synchronous queues disable sending. `NOTICE_MAIL_PER_MINUTE` defaults to 30; match it to the provider's limits. Accepted means the provider accepted the message, not that it reached an inbox.

Run the dedicated database worker in `deploy/supervisor.conf.example`: `php artisan queue:work mail_notices --queue=mail-notices --sleep=1 --tries=3 --timeout=60 --max-time=3600`. This connection deliberately uses the application's default database so that enqueueing and marking each recipient as dispatched commit together. Keep the regular queue worker for other application mail. Restart both workers after deployment.

Run Laravel's scheduler every minute, for example with this crontab entry (adapt the path and PHP binary):

```cron
* * * * * cd /srv/itechorbit/current && /usr/bin/php artisan schedule:run >> /var/log/itechorbit-scheduler.log 2>&1
```

The scheduler dispatches remaining recipient batches and recovers undispatched records after interruptions. `php artisan notices:dispatch` can also resume them manually. Use a shared cache for the global mail rate limiter when workers run on multiple hosts. Monitor the notices ledger, failed jobs and worker logs. Only confirmed failures can be retried from the page; unconfirmed outcomes require checking provider logs to avoid sending duplicate messages. Templates can be changed or deleted without altering past campaign snapshots. Use synthetic recipients and a non-relaying SMTP sink for local verification; do not validate a deployment by broadcasting to real users.

## Rollback and monitoring

Keep the previous release available. Roll back code, locked dependencies and matching client/SSR assets together, rebuild that release's caches, and restart PHP/SSR/queue workers. Retain additive database columns and social connections unless a separately reviewed data rollback is necessary. Do not restore a live database wholesale to undo a code release. Preserve `APP_KEY` and shared media throughout.

Monitor application errors, HTTP health, actual SSR output, database connectivity, queued/failed jobs and mail delivery. Validate OAuth with real provider applications and review public indexing/canonical behavior after release. Test backups independently of the running site. The verification workflow has no deployment credentials; the separate deployment workflow uses the `production` environment and is triggered manually.
