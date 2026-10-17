# Deployment

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

5. Switch the release atomically and restart PHP/SSR services and queue workers. Start SSR with `php artisan inertia:start-ssr`; supervise it and `php artisan queue:work`. Keep SSR on loopback. Queue `retry_after` must exceed the worker timeout. Use `php artisan queue:restart` for existing workers. No application scheduler is currently required.
6. Smoke-test public pages and server-rendered headings, metadata/canonicals, sign-in, account permissions, admin CRUD, uploads/PDF previews, all portfolio designs, contact forms, and email delivery/retry using staging accounts. `/healthz` confirms application boot only; separately verify database, SSR and queue readiness.

Do not deploy `.git`, test reports, local logs, development databases or credentials in public directories. Tests and CI remain in the source branch for maintenance; they are not runtime dependencies.

## Database migrations and data preservation

Keep all migrations, even when their names are historical: they define valid installation and upgrade paths. Review pending changes against the existing schema. Current upgrades include the nullable Sanctum token expiry, portfolio template preference, widened privacy-policy content and social-account connections.

The privacy-policy migration widens content to LONGTEXT and intentionally does not shrink it on rollback. The social-account migration retains `users.provider_id`, copies known Google identities and refuses duplicate legacy identities before creating its table. Resolve identified duplicate identities before retrying; do not delete user accounts to make the migration pass. Portfolio template values default safely to Studio when unset.

Preserve uploads, public CV visibility, existing article/portfolio URLs, permissions and primary keys across releases. Historical public images and fonts are retained because stored content can reference their URLs. Compare media checksums and record counts during staging rehearsal.

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

## Rollback and monitoring

Keep the previous release available. Roll back code, locked dependencies and matching client/SSR assets together, rebuild that release's caches, and restart PHP/SSR/queue workers. Retain additive database columns and social connections unless a separately reviewed data rollback is necessary. Do not restore a live database wholesale to undo a code release. Preserve `APP_KEY` and shared media throughout.

Monitor application errors, HTTP health, actual SSR output, database connectivity, queued/failed jobs and mail delivery. Validate OAuth with real provider applications and review public indexing/canonical behavior after release. Test backups independently of the running site. CI has no deployment credentials or automatic deployment step.
