# Application configuration and upgrade reference

[Production deployment](../DEPLOYMENT.md) · [Operations](SERVICES.md) · [Scheduler and queues](SCHEDULER.md)

This reference preserves application-specific migration, content, OAuth, upload and editor requirements. It is not an instruction to rerun old migrations or seeders. Use the migration status of the exact release being deployed. All production Artisan commands below must run inside `php-workspace-production-itechorbit-php-1` as `www-data`, even where an inline command is abbreviated.

The verified canonical host is `https://www.itechorbit.com`; the bare domain redirects to it. Existing OAuth registrations must match the callback actually used by the application. Coordinate provider-console changes with application configuration; changing a documentation example does not migrate an existing OAuth registration.

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
| Google | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` | `https://www.itechorbit.com/login/google/callback` |
| LinkedIn | `LINKEDIN_CLIENT_ID`, `LINKEDIN_CLIENT_SECRET`, `LINKEDIN_REDIRECT_URI` | `https://www.itechorbit.com/login/linkedin/callback` |
| GitHub | `GITHUB_CLIENT_ID`, `GITHUB_CLIENT_SECRET`, `GITHUB_REDIRECT_URI` | `https://www.itechorbit.com/login/github/callback` |

Register the exact callback URL in each provider console; local/staging applications need separate matching URLs. Google uses a Web application OAuth client and its consent-screen settings. LinkedIn requires **Sign In with LinkedIn using OpenID Connect**, with `openid`, `profile`, and `email` scopes. GitHub uses an OAuth App and the default `user:email` scope. Missing/unverified provider email prevents first-time registration or linking; normal email registration remains available.

Existing users sign in through an established method and connect providers in Account settings. Provider email matching alone does not grant access. Legacy Google identities recorded in `provider_id` are migrated; users without a recorded identity can use password recovery and then connect Google. New social users receive role 4 with portfolio publication disabled. A social user's local password can be set through password recovery. Disconnecting providers is not implemented.

After configuring credentials, refresh the config cache and test real consent, callbacks, session expiry, cancellation and disabled accounts on staging. Mocked tests do not verify provider-console configuration or live token exchange. To disable a provider, remove its configured credentials and refresh the config cache; retain its connection records.

## Email, uploads and editor

Configure and verify the actual SMTP/mail transport and `MAIL_FROM_*` values. Inquiry notifications use the configured sender with the visitor's address as Reply-To. Both personal and business notifications include HTML/plain-text bodies and require the supervised queue worker. Test delivery with a controlled recipient before release; a saved inquiry is not evidence of successful delivery.

Image uploads accept JPG/PNG/WebP up to 2,048 KB; CVs accept PDF up to 5,000 KB. Existing image/PDF previews retain permission and ownership checks. A private admin CV preview does not enable public download. Banner covers appear in all five portfolio templates; originals remain unchanged, with display-only cropping at 4:1 desktop and 2:1 mobile.

CKEditor is self-hosted with `licenseKey: 'GPL'`, using the open-source package without premium/cloud features. Its GPL distribution requires GPL 2-or-later compliance; it is not an unrestricted proprietary lifetime license. Preserve dependency license notices and confirm licensing requirements for distribution/deployment. The application's own license has not been changed.


## Administrative email notices

The additive `2026_10_17_000003_create_mail_notices_tables.php` migration supplies the notice outbox. The navigation registration migration supplies its admin links; existing permissions remain authoritative. Confirm both through `migrate:status` rather than editing migration history.

Notice sending requires a real mail transport, an asynchronous default queue, a valid sender and the dedicated `mail_notices` worker. The scheduler dispatches remaining recipient batches and recovers undispatched records. The default worker does not consume this dedicated connection. See [the queue inventory and setup](SCHEDULER.md#queue-connections-and-workers).

`NOTICE_MAIL_PER_MINUTE` defaults to 30. A provider or destination accepting a message does not prove inbox placement. Monitor the notice ledger and failed jobs; retry confirmed failures only. For unconfirmed outcomes, check SMTP logs before retrying to avoid duplicates. Template edits do not modify campaign snapshots. Use a shared cache for rate limiting if adding workers on multiple hosts.

Image/PDF data, OAuth identities, serialized job/model class names and historical public URLs are compatibility boundaries. A code rollback must remain compatible with the current schema and uploaded data; follow [backup and recovery](BACKUP-RECOVERY.md).
