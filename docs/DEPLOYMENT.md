# Deployment

## One Docker directory for local development and VPS

All application Docker definitions live in the parent project's `itechorbit/.docker/`, alongside `itechorbit/src/`. The application source has no `.docker/` directory. PHP and Nginx use the same base Compose file and Dockerfiles in both environments; the workspace selects the development or production build target and generates the source mounts. SSR uses `.docker/compose.ssr.yml`, mounting `../src` read-only. Run its npm commands from `src/`; the Compose project name remains `itechorbit-local`, preserving the existing SSR container identity.

The parent `itechorbit/.env` contains workspace metadata, including `WORKSPACE_SOURCE_MODE=mounted` and `WORKSPACE_SOURCE_ROOT=src`. Laravel settings and credentials remain in `itechorbit/src/.env`. Private infrastructure credentials stay in `.docker/infra.development.env` or `.docker/infra.production.env`; keep each environment's existing values. Common overrides belong in `.docker/compose.override.yml`. Environment-specific overrides use `.docker/compose.override.development.yml` and `.docker/compose.override.production.yml`. Only development publishes MariaDB at `127.0.0.1:3306`; production does not load that override.

From the workspace root (`Workspace/`), validate and reconcile local development with:

```sh
make validate NAME=itechorbit
make project-up NAME=itechorbit
```

On the VPS, use the same layout at `/srv/Workspace/projects/itechorbit/` and append `ENV=production` to workspace commands. Before removing a legacy Docker directory under `src/`, inspect it for server-only customizations and preserve those in the parent configuration. Keep existing production credentials, stack names, networks and volume identities.

The parent `.docker/` is outside this application's Git repository. Transfer its Dockerfiles, templates and Compose files separately to the matching parent directory on the VPS; preserve server-specific overrides and private `infra.*.env` files rather than overwriting them with local copies. Do not place another copy under `src/`. After reviewing configuration changes, validate, rebuild and reconcile through the workspace:

```sh
make validate ENV=production NAME=itechorbit
make project-build ENV=production NAME=itechorbit
make project-up ENV=production NAME=itechorbit
```

These commands retain the configured persistent volumes; do not remove volumes or regenerate credentials. Ordinary source-only deployments below do not transfer Docker configuration or rebuild images. Moving the SSR Compose file requires no container recreation by itself. When starting or recreating SSR, run `npm run ssr:docker` from `src/`, with `ITECHORBIT_DOCKER_NETWORK` set to the existing network shared with PHP if it differs from the default `php-workspace_app_network`. Use the VPS's actual network rather than creating or renaming one.

## Deploy from GitHub Actions to a VPS

The manual workflow deploys the selected application's commit to the source directory configured in `VPS_PATH`. It builds with the exact PHP and Node images already installed on the VPS, backs up the previous code, enables maintenance mode, synchronizes source and fresh assets, optionally migrates, rebuilds caches, and restarts PHP, Nginx and SSR. It checks SSR before reopening the application, then checks the public health URL. Every deployment runs `npm ci --include=dev` followed by `npm run build` inside Docker, refreshing both the client assets and the SSR bundle before replacing live files. Run verification for the application commit before deploying.

The **Branch to build and deploy** dropdown offers only `main` and `production-version`, defaulting to `production-version`. **Use workflow from** chooses the workflow and deployment script; the branch input chooses application code. GitHub's built-in workflow branch selector still lists repository branches; the updated deployment job skips runs from any workflow branch other than `main` or `production-version`, and rejects any other application branch. Normally select `main` for **Use workflow from**. The deployment script comes from the workflow branch, so the application branch does not need to contain it.

### Existing VPS Docker layout

This script targets the inspected running containers:

| Container | Role |
| --- | --- |
| `php-workspace-production-itechorbit-php-1` | PHP 8.5 and Composer |
| `php-workspace-production-itechorbit-nginx-1` | Web server |
| `itechorbit-local-ssr-1` | Node 24 and Inertia SSR |

All three must mount `/srv/Workspace/projects/itechorbit/src` as their source. The script verifies those mounts before updating files. `VPS_PATH` must equal the source mount itself. Synchronization excludes any earlier `test-deploy` and `test-deploy.deploy` directories, preserving those files and avoiding recursive backups; there is no test mode in the workflow. It preserves `.env` and `.env.*`, `public/uploads`, `public/storage`, `storage`, `bootstrap/cache`, `.git`, `.docker` and `logs`. Existing storage/cache named volumes remain attached. MariaDB, Redis, Caddy and access containers are not restarted or recreated. This workflow updates application code and dependencies using existing images; changes to PHP extensions, container configuration, Dockerfiles or Compose require a separate workspace image rebuild. Existing queue workers receive Laravel's `queue:restart` signal; the workflow does not provision workers or a scheduler.

### One-time VPS setup

Keep the existing Docker stack, production `.env`, `APP_KEY`, database and uploads. There is no host PHP-FPM or Supervisor requirement. `PHP_FPM_SERVICE` is not used.

Run these commands as root on the VPS to prepare deployment permissions:

```sh
apt update
apt install -y rsync util-linux acl
usermod -aG docker deploy
install -d -o deploy -g deploy -m 755 /srv/Workspace/projects/itechorbit/src.deploy
setfacl -R -m u:deploy:rwX /srv/Workspace/projects/itechorbit/src
```

Docker group access gives the deployment user administrative control of the host. Reconnect SSH after adding group membership. Ensure the user can traverse every parent directory. Test `docker ps` and source access from that user's SSH session. PHP artisan commands run inside the existing PHP container as `www-data`, which must already have write access to the mounted storage and bootstrap-cache volumes.

`VPS_PATH` is `/srv/Workspace/projects/itechorbit/src`. The workflow derives its temporary build and code-backup directory automatically by appending `.deploy`. No additional path variable is needed.

The ACL command grants source access without changing existing ownership or application permissions. Back up the database and uploads separately before migrating. Code backups under this staging root exclude secrets, uploads and runtime storage; they are not database or media backups. The staging root needs enough space for the new build and the previous code, including dependencies.

### Build assets and persistent uploads

`npm run build` compiles client and SSR assets only. Vite's public-directory copying is disabled, and builds retain previous hashed chunks. Deployment publishes new `public/build` files without deleting old hashes, then updates the application source with `public/build` excluded from deletion. This lets open tabs continue loading the CSS, JavaScript and fonts referenced by their existing page. The previous manifest is included in the code backup. Old build assets need deliberate periodic cleanup after the support window for old tabs; do not wipe `public/build` as part of a release. Default Vite URLs are same-origin relative paths, avoiding incorrect backend HTTP/hostname URLs behind a TLS proxy; an explicit `ASSET_URL` CDN is still honored. HTML and Inertia responses revalidate, and every page's CSS is linked in its initial HTML rather than waiting for hydration.

New images and PDFs are stored through Laravel's public filesystem disk at `storage/app/public/uploads` on the existing Docker storage volume. Their URLs use `/storage/uploads/...`. Deployment runs `media:preserve-uploads` to copy legacy `public/uploads` files into that volume with SHA-256 verification. It keeps original files, refuses to replace differing files with the same path, and can be rerun safely. Existing database paths remain unchanged and existing `/uploads/...` links continue working. Admin previews, replacement/deletion and public CV visibility retain their existing checks. Already-missing files require recovery from an existing backup; a build cannot restore them.

Production containers may mount application source read-only. The deployment script creates the standard `public/storage` symlink on the VPS host, with relative target `../storage/app/public`, then verifies its resolution inside PHP as `www-data` before entering maintenance. It never runs `storage:link` as the host deployment UID inside the container. Existing correct links are retained; conflicting files/directories or incorrect links stop deployment without replacement. PHP and Nginx must share the same storage volume; the script checks this before deployment. The source sync excludes `public/uploads`, `public/storage`, `storage` and `bootstrap/cache` regardless of whether they are directories or symlinks. Never run rsync with `--delete-excluded`, wipe/recreate the storage volume, or copy build output over uploaded files. Docker image rebuilds must retain the same named volumes. A code rollback must retain storage-aware media handling so uploads created after this change remain readable.

Before building or entering maintenance, deployment probes runtime directories as `www-data` without booting Laravel. The existing `laravel.log`, if present, must also be writable by that user. Source remains read-only inside PHP; only persistent storage and bootstrap-cache mounts need write access. Run all runtime Artisan commands as `www-data` so deployment cannot create root- or host-UID-owned log/cache files. If existing files have incorrect ownership, repair only the affected runtime directories/files inside the container; do not make the entire source writable or use `chmod 777`.

If a previous deployment created a log under the wrong user, this one-time command repairs only Laravel's log directory/file without deleting logs:

```sh
docker exec --user root php-workspace-production-itechorbit-php-1 sh -ec '
  mkdir -p storage/logs
  chown www-data:www-data storage/logs
  chmod u+rwx storage/logs
  if [ -f storage/logs/laravel.log ]; then
    chown www-data:www-data storage/logs/laravel.log
    chmod u+rw storage/logs/laravel.log
  fi
'
```

Retry a fresh deployment after correcting any reported runtime permissions. SSR may be stopped following an earlier failure; the workflow accepts an existing stopped SSR container, rebuilds its bundle and starts it during activation. It does not reopen an application left in maintenance until the SSR check passes.

For a manual local setup after configuring `.env`, run:

```sh
php artisan storage:link
php artisan media:preserve-uploads
npm run build
```

The GitHub workflow performs these operations in the existing containers; PHP, Composer and npm do not need to be installed on the VPS host. Confirm `APP_URL` is the canonical HTTPS domain and that any `ASSET_URL` value points to a server that actually serves the same build. Leave `ASSET_URL` unset for same-origin assets. Do not run the Vite development server in production; deployment removes `public/hot`.

### Themed 404/403 pages and Nginx rollout

Missing pages and browser navigations to missing files use the website's themed 404, with HTTP status 404, noindex metadata, the original URL and links back to Home and Contact. JSON errors stay JSON. Missing script/style/image/font subrequests remain failed resources rather than receiving an Inertia document. Protected paths keep HTTP 403 and show a themed “Access forbidden” page for browser requests. Nginx serves `public/errors/403.html` directly with matching navigation, footer, recovery links and error artwork, without PHP, JavaScript or built assets; non-HTML requests remain denied without the themed document. The error location is internal-only and carries a noindex header.

The Nginx template uses `error_page 404 = @laravel_not_found` to send Nginx-generated browser 404s (including missing files under its storage alias) through Laravel's front controller. The fallback only accepts requests whose `Accept` header includes `text/html`; it preserves the original request URI and disables FastCGI error interception to prevent recursion. Existing files continue to be served directly.

The Docker workspace builds its Nginx image from the single **parent project's** `.docker/nginx.conf.template` (on the VPS: `/srv/Workspace/projects/itechorbit/.docker/nginx.conf.template`). Edit that template for both local and VPS Docker use, retaining its existing security rules. Transfer the reviewed template to the VPS parent directory separately from source-only GitHub deployments, which neither update it nor rebuild its image. `deploy/nginx.conf.example` is an example for a separate host-Nginx deployment, not a Docker configuration source. Using the workspace's existing production configuration/environment, rebuild and recreate **only Nginx** for a Nginx-only change, keeping the database, storage volumes and application containers intact. Restarting an old image alone does not install the new template.

For a graceful rollout without container recreation, first build/tag the updated Nginx image and update the parent template. Back up and replace both `/etc/nginx/templates/default.conf.template` and the rendered `/etc/nginx/conf.d/default.conf` in the running Nginx container, validate with `nginx -t`, then reload with `nginx -s reload`. Updating only the rendered configuration is temporary: the entrypoint regenerates it from the template on restart. Retain the previous image tag and configuration for rollback. This only changes Nginx; it does not deploy the Laravel application.

Before applying the changed image, validate the rendered configuration with `nginx -t`. After recreation, confirm the loaded rules with:

```sh
docker exec php-workspace-production-itechorbit-nginx-1 nginx -t
docker exec php-workspace-production-itechorbit-nginx-1 nginx -T
```

Verify a browser request to `/storage/not-found.png` and an unknown page displays the themed page with status 404, a script/image request to the same missing file stays 404, existing assets/uploads still return 200, and `/.env`, other hidden files and forbidden upload scripts display the themed page with HTTP 403 and no protected contents. Publish `public/errors/403.html` with the application before activating the Nginx rules. The UI and Laravel exception handling are delivered by the normal application deployment; Nginx-generated file errors need the separate Nginx image rollout described above.

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
| `VPS_PATH` | `/srv/Workspace/projects/itechorbit/src` (the existing running containers source mount) |
| `HEALTHCHECK_URL` | `https://itechorbit.com/healthz` |

Commit and push the workflow, script and documentation. The workflow must exist on the repository's default branch to appear for manual runs. Open **Actions → Deploy to VPS → Run workflow**, select `main` under **Use workflow from**, choose `main` or `production-version` under **Branch to build and deploy**, and enable migrations only after reviewing pending migrations and backing up the database. No deployment runs automatically on push. No credentials are stored in the repository.

Build failures leave live application code unchanged. Activation uses an in-place source sync during maintenance because the containers bind-mount a fixed directory; it is not an atomic symlink switch. An activation failure leaves maintenance enabled and may leave SSR stopped. Inspect the failure before recovery. The script prints the previous-code backup path. To restore code, use the same rsync exclusions from `deploy/deploy-vps.sh`, rebuild Laravel caches, restart the three application containers, verify SSR, then run `docker exec --user www-data php-workspace-production-itechorbit-php-1 php artisan up`. Never restore excluded paths from a code backup or automatically roll back migrations. A later public health failure leaves the new deployment active for inspection. Retained builds and code backups require periodic manual cleanup.

The sections below describe application requirements and general deployment/rollback procedures. Native Nginx/Supervisor examples are for hosts without this Docker workspace. For this Docker setup, run application PHP commands inside the PHP container and use the existing Compose networking; the SSR URL in `.env` must resolve to the SSR service from PHP (for example `http://itechorbit-ssr:13714`), rather than host loopback.

If deployment reports that SSR is unavailable, confirm the SSR container has `INERTIA_SSR_HOST=0.0.0.0` and the deployed `resources/js/ssr.tsx` reads that setting when starting its listener. A hardcoded `127.0.0.1` listener can pass a health check inside the SSR container while remaining unreachable from PHP. Rebuild the bundle and restart SSR after correcting its source. PHP's `.env` must use `INERTIA_SSR_URL=http://itechorbit-ssr:13714` (or the actual shared-network alias), and both containers must share that Docker network. Refresh PHP's config cache after changing its URL. Start a fresh workflow run from `main` targeting the corrected application branch to recover deployment; it can run while maintenance remains enabled.

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
