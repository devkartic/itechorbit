# Admin modernization — 2026-10-15

The private workspace now has a dedicated responsive design, menu/module navigation, server-side AJAX resource tables and rich-text content forms. Existing IDs, records, routes, permissions and CRUD handlers are preserved. The preceding website cleanup is recorded in CLEANUP.md and commit `cdd75bf`.

## Interface and navigation

The sidebar groups authorized links using their existing Link → Module → Menu relationships. Resources without configured relationships use Content, Administration or Portfolio & inquiries fallback groups. Modules collapse independently, the active module opens, and Find a module filters the visible navigation. Overview, account settings, sign out and the public website remain directly accessible. Mobile navigation has an overlay, Escape dismissal, focus trapping/restoration and body-scroll locking.

At each sidebar level, explicit `order_number` values sort numerically ascending, including zero. Items with no order follow alphabetically by displayed name; equal numbers also use case-insensitive natural alphabetical order (for example, Module 2 before Module 10). Menu order takes precedence over module order, then link order. Configure these using the existing Menus, Modules and Links forms; leave Order number blank for alphabetical fallback. The client preserves the server's sequence even for numeric menu/module names. Overview remains pinned above the configurable groups. No database migration or existing-record changes are required.

The overview uses real existing counts and recent articles, with permission-aware quick access and a create-article action only for authorized roles. The resource list, create/edit, detail and permission-matrix pages share scoped workspace styling. Public website styling remains separate; `resources/css/admin.css` loads with AdminLayout.

## Server-side tables

`GET /workspace/data/{resource}` returns JSON for the configured admin resource allowlist. The route requires authentication; AdminTable additionally applies the existing active/verified-account checks, resource index permission and ownership rules. Member user lists contain only the current user. Owned resources are scoped to the current user unless the existing administrator rule allows broader access.

The initial page remains server-rendered. Subsequent search, status filtering, sorting, pagination and refresh requests use fetch without full page reloads. Search is debounced and superseded requests are aborted. Empty/error/loading states, retry, clear filters and record counts are provided. Page sizes are restricted to 10/20/25/50/100. Sort columns and directions are allowlisted; SQL values use Eloquent bindings. Secondary ID ordering keeps page boundaries deterministic.

List payloads select only displayed columns and operational IDs/statuses. Rich bodies, password hashes, provider IDs and tokens are excluded. Existing endpoints for create, update, deletion, media removal and user/portfolio status remain authoritative. The specialized permission matrix still uses its existing server handlers.

All original 210 route definitions retain their methods, URI, names, actions and middleware; the only addition is the protected table route. Closure source-line metadata shifts with the new import/route and is excluded from the semantic comparison.

## Forms and editor

Forms separate content, visibility and media, with responsive columns and clear labels. Required rich content is checked before submission; server validation remains authoritative. Field errors, summaries, focus on the first invalid field, saving/disabled states, media errors and success notices are implemented. Edits clear the corresponding stale error. Successful saves show a one-time confirmation after returning to the list; this uses sessionStorage for the non-sensitive message. In restricted browsers where storage is unavailable, saving and navigation still work but that cross-page confirmation may be unavailable.

Long-form content uses Tiptap 3.32.0 with bold, italic, underline, strike, H2/H3, lists, quotations, code blocks, links, clear formatting, undo/redo and HTML source editing. HTML with advanced elements or custom attributes opens in source mode to preserve it. Merely opening a form does not rewrite stored HTML. Code examples, project job notes and testimonial quotations remain plain/source text where appropriate.

RichTextEditor is lazy-loaded only when a content form renders. SSR uses `immediatelyRender: false` following the official React integration guidance. Added packages are `@tiptap/react`, `@tiptap/pm` and `@tiptap/starter-kit`, locked to 3.32.0 in package-lock.json. Registry peer requirements include React 19; install reported zero vulnerabilities. No table dependency, paid editor service or remote script was introduced.

Content previews are sanitized on the server. Portfolio objectives, service descriptions and responsibilities now also pass through the existing HTML sanitizer before rich rendering, so the editor's formatting is supported safely. Unsafe source is never rendered directly into admin detail previews. Existing server validation and upload restrictions are retained.

Official references: [React installation and SSR](https://tiptap.dev/docs/editor/getting-started/install/react), [StarterKit features](https://tiptap.dev/docs/editor/extensions/functionality/starterkit), [HTML persistence](https://tiptap.dev/docs/editor/core-concepts/persistence).

## Changed modules

- Backend: AdminTable, TableController, AdminPresentation, admin field configuration, one authenticated route and sanitized portfolio presentation.
- Frontend: AdminLayout, dashboard, Resource, permission matrix, DataTable, ResourceForm, RichTextEditor, Notice, shared admin types and the JSON request helper.
- Public integration: sanitized rich portfolio fields; no public URL changes.
- Regression coverage: AdminTableTest and desktop/mobile admin table/editor scenarios.

## Validation and remaining limits

PHP regression suite: 37 tests / 805 assertions. Frontend: 3 Vitest tests. TypeScript, ESLint, Pint, remaining Blade compilation and client/SSR production builds pass. The final full browser suite passes all 28 desktop/mobile checks, covering existing public/admin flows plus grouped navigation, AJAX requests, sorting/pagination, error/retry, client/server validation, preserved editor content, success after navigation and public rich-text rendering. QA images use synthetic fixtures, not real records: [dashboard](assets/admin-dashboard-desktop.png), [table](assets/admin-table-desktop.png), [desktop editor](assets/admin-editor-desktop.png), [mobile editor](assets/admin-editor-mobile.png).

An initial mobile check caught a hidden page indicator; it is now visible. Field errors now clear on correction. No Lighthouse/Core Web Vitals score is claimed for this work. Legacy AdminLTE assets/templates remain for a separate compatibility cleanup; the new workspace does not load those theme scripts. Advanced legacy HTML should be edited in source mode when it exceeds the visual editor's supported blocks.

## Development, staging and rollback

Install the locked dependencies with `npm ci`, run the checks from README.md, and build both client and SSR with `npm run build`. Restart the supervised SSR process after deploying a matching versioned bundle to approved staging. There are no schema migrations or new service/environment requirements for this change. Verify representative admin permissions, ownership, rich fields, uploads and both viewport sizes on staging before requesting production approval.

To undo this feature, revert its commit, restore the corresponding package lock, run `npm ci` and rebuild/restart client/SSR for that release. No database rollback is required. Source-mode HTML and existing records/media remain compatible with the prior application. Production deployment was not performed.


## Workspace refinement — 2026-10-16

The existing Tiptap and AJAX infrastructure is extended rather than replaced. No dependency versions or schema changes are required.

### Text formatting

All content fields use the visual editor, including project Engineering contributions and testimonial Comment. Article Codebase remains a plain code input. The editor now offers font sizes 14, 16, 18, 20, 24 and 32px, plus theme, sans serif, serif and monospace font options. Existing bold, italic, underline, headings, lists, links, source editing and undo/redo remain available. Existing advanced HTML still opens in source mode to preserve its content.

The small TextFormatting mark uses bounded data attributes instead of arbitrary inline CSS. PublicContent and TextFormattingSanitizer allow only those six sizes and three font identifiers on spans. Unsafe URLs, event handlers and arbitrary CSS remain removed. Shared CSS renders the same attributes in the editor, admin preview and public content. Project contributions are sanitized before HTML rendering; legacy plain-text line breaks and comparison symbols are preserved. Tests cover unsafe formatting rejection and save → detail → public article → reopen behavior.

References checked: [Tiptap Mark API](https://tiptap.dev/docs/editor/extensions/custom-extensions/create-new/mark) and [Symfony HTML Sanitizer](https://symfony.com/doc/current/html_sanitizer.html), together with the installed package interfaces.

### Workspace appearance and navigation

The topbar has accessible Light theme and Dark theme buttons. A choice persists in browser localStorage under itechorbit-workspace-theme; absent an explicit choice, the OS preference is used after hydration. When browser storage is blocked, an in-memory preference keeps the current session usable. Storage events synchronize other tabs. SSR starts with a deterministic light snapshot; public pages and individual portfolio designs are independent of the workspace preference.

A semantic token palette now covers workspace surfaces, borders, forms, notices, editor, dashboard, tables, detail previews and dialogs. Categories use uppercase markers, modules use a folder icon, a Module label and an expandable surface, and actual pages are indented along a guide with a distinct selected state. Numerical ordering and alphabetical fallback are unchanged; no order numbers are displayed.

### Resource lists

The existing authenticated server-side endpoint continues to handle debounced search, allowlisted sorting, status filtering, page size and pagination. The refreshed list adds more readable titles, badges, row actions, filter summaries, Reset filters and live loading/result feedback. Requests still abort on replacement; errors have retry behavior; responses remain permission-scoped and exclude rich bodies/secrets. No external DataTables package or client-side full-dataset loading is introduced.

### Verification, release and rollback

51 PHP tests / 1,104 assertions, 3 frontend tests and all 34 browser checks pass, as do TypeScript, ESLint, Pint and client/SSR production builds. Reviewed synthetic captures include [dark table](assets/admin-table-dark-desktop.png), [light table](assets/admin-table-desktop.png), [dark editor](assets/admin-editor-dark-desktop.png) and [mobile editor](assets/admin-editor-dark-mobile.png). Tests verify actual AJAX requests, sorting/search/pagination and error recovery, persistence of both themes, permission-sensitive flows and safe formatting round trips. No Lighthouse score is claimed.

Deploy the matching PHP/config files and rebuilt client/SSR assets using the existing approved staging workflow; refresh the configuration cache if used and restart SSR. There are no new environment variables, migrations or external services. Revert this change and rebuild matching assets/config cache to roll back; saved rich content remains intact, though an older release will omit the new font-size/font-family presentation. No data rollback is required. Production deployment was not performed.


## CKEditor replacement — 2026-10-17

The shared ResourceForm now uses self-hosted CKEditor 5 (49.0.0) through the official React integration. Tiptap packages, its custom toolbar/formatting extension, and retired Blade TinyMCE/Summernote bootstraps have been removed. Every configured rich-text field uses the same CKEditor component, including portfolio basics, projects, experience, services, testimonials, article bodies, About, Services information, Privacy policy and Contact information. Plain code inputs remain plain text.

The toolbar provides headings, font size/family, bold/italic/underline/strike, native links, lists, quotes, inline/block code, tables, clear formatting, source editing and undo/redo. The editor is loaded only in the browser on content forms; SSR renders a loading placeholder. HTML fallback preserves editing access if initialization fails. Source-mode edits synchronize on blur before a form save. Workspace light/dark tokens also style native CKEditor balloons. Existing bounded font attributes are converted into editor styles and back on save so public sanitization and previously stored formatting remain compatible. No existing content records are migrated.

Licensing: this integration uses `licenseKey: 'GPL'` and only the open-source `ckeditor5` package; it uses no premium package, trial, account, CDN or usage-metered cloud plan. Its installed GPL distribution has no subscription expiry or editor-load quota. Free use requires compliance with GPL 2 or later; it is not an unrestricted proprietary lifetime license or a promise about future releases/support. The application's own license has not been changed. Resolve the application's GPL compliance before distributing/deploying this integration. Official references: [CKEditor licensing](https://ckeditor.com/docs/ckeditor5/latest/getting-started/licensing/license-and-legal.html), [activation](https://ckeditor.com/docs/ckeditor5/latest/getting-started/licensing/license-key-and-activation.html), and [React self-hosted installation](https://ckeditor.com/docs/ckeditor5/latest/getting-started/installation/self-hosted/react/react-default-npm.html).

The CKEditor browser regressions cover desktop/mobile native link insertion and unlink/undo/redo, fonts and headings, validation, saved public rendering, reopening legacy formatting, HTML source saves, table persistence and every rich-text resource. Test data is isolated from application records.

Validation: all 36 desktop/mobile browser tests passed, including CKEditor formatting/link/source/table persistence and multi-resource coverage. TypeScript, ESLint, client/SSR builds, and the three text-formatting sanitizer tests (15 assertions) passed. npm installation reported zero vulnerabilities.


## Long privacy policy saves — 2026-10-17

MySQL logs showed privacy-policy updates failing with SQLSTATE 22001 / error 1406 because pasted rich HTML exceeded the 65,535-byte TEXT column. Migration `2026_10_17_000001_expand_privacy_policy_content` widens only `privacy_policies.descriptions` to LONGTEXT and preserves existing rows. Its rollback intentionally retains the wider column to avoid truncating saved policies. Both create and update now validate a maximum of 1,000,000 content characters; the admin marks the policy description required to match server validation.

The migration was previewed and applied to the local `php-workspace-itechorbit-1` application container after confirming APP_ENV=local. Other environments need this migration before accepting long pasted policies. No policy text or publication state was overwritten. Seven AdminWorkspace tests (304 assertions), two desktop/mobile long-policy save/reopen browser checks, Pint and ESLint passed. Browser fixtures exercise content above the old TEXT byte limit; MySQL column widening was checked separately because SQLite does not enforce that limit.


## Upload validation and saved previews — 2026-10-17

All five upload fields share explicit accepted extensions and size hints: article covers, project screenshots, profile banners and profile photos accept JPG/PNG/WebP up to 2,048 KB, and CVs accept PDF up to 5,000 KB. Create/edit image limits now match. Optional file inputs are not serialized as empty form fields; clearing a replacement preserves the existing file. Selection checks show field errors immediately, while server image/MIME checks remain authoritative and reject renamed non-image/non-PDF content.

The shared upload component shows selected image previews and saved image thumbnails. Saved PDFs have an Open PDF link with target=_blank and inline file headers. Existing files appear on edit/detail screens where supported. Missing or unsupported stored files show an unavailable message. Preview metadata is derived from allowlisted model fields and verified files, not client-supplied paths.

`/workspace/media/{resource}/{record}/{field}` requires authentication, a valid account, resource index/edit permission and ownership (or existing super-admin privileges). It serves only the allowlisted stored upload fields for posts/projects/basics, resolves files inside uploads, checks the stored MIME type and sends private/no-store/noindex responses. Opening a CV through the admin preview does not toggle its public-download setting.

Verification includes all four image fields, malformed/oversized uploads, image persistence and previews, existing CV preservation, PDF link/new-tab requests, permission/ownership checks and path-traversal rejection. Browser tests use synthetic uploads and delete their own files.

### Contact inquiry reading view — 2026-10-17

Contact detail pages now use a dedicated message card with subject, sender initials/name/email, received date/time in UTC, and a full-width readable message body. A compact Reply link opens the email application with an encoded destination and reply subject. Raw record fields and numeric operational statuses are omitted from this view. Other resource detail pages retain their existing layout.

The card uses workspace theme tokens for light/dark mode, 24px internal padding on tablet/desktop and 16px on mobile. Long text wraps, newlines are preserved, and message HTML is escaped. Missing fields have clear fallbacks; invalid saved email addresses do not produce reply links. No API, schema, delivery or permission changes are required. Opening an inquiry still marks it read through the existing controller.

Verification includes component safety/fallback tests and isolated browser flows at 390px, 768px and 1440px in both themes, covering reply encoding, keyboard focus, wrapping, read status and existing deletion. Screenshots use synthetic inquiries only.

### Social sign-in and connected accounts — 2026-10-17

Login and registration offer Google, LinkedIn and GitHub when each provider has complete configuration and the `social_accounts` migration has run. The centered form uses compact outlined provider links. Account settings shows connection status and Connect actions. Existing users must sign in using an existing method and explicitly connect a matching-email provider; email matching alone does not grant access. If they previously used Google through a password account without a recorded provider ID, they can use password recovery and then connect Google.

The shared controller retains `/login/google` and its `login.google` route name. Public starts use `GET /login/{provider}`, callbacks use `GET /login/{provider}/callback`, and connection starts use authenticated, CSRF-protected `POST /account/connections/{provider}`. Only `google`, `linkedin`, and `github` are accepted; LinkedIn maps to Socialite's `linkedin-openid` driver. `Auth` receives public `socialProviders` entries (`id`, `name`, `url`) instead of `googleEnabled`; Account settings additionally receives a `connected` boolean. No client secrets, identity IDs or access tokens are included in these props.

Connections store user ID, provider and provider user ID, with unique constraints on provider/identity and user/provider. MySQL/MariaDB identity matching uses a binary collation. The migration backfills nonempty legacy Google `users.provider_id` values, retains that column, and refuses duplicate legacy identities before creating the table. New registrations are active role-4 users with verified provider email, random password hashes and portfolio publication disabled. Google/LinkedIn registration and linking require a verified email claim; GitHub's default `user:email` scope resolves the verified primary email. Returning connections authenticate by stable provider identity, not the current provider email. Linking preserves local email, role and publication settings. Missing or unverified email prompts use of normal registration instead. Users can set a usable local password through password recovery. Disconnecting providers is not implemented.

OAuth state checking stays enabled, callback context expires after ten minutes and is consumed once, connection callbacks bind to the initiating authenticated user, disabled accounts are rejected, and successful logins regenerate the session. Database transactions and unique constraints prevent duplicate connection writes. Provider errors are shown without exposing response bodies/tokens. No provider access or refresh tokens are stored.

#### Provider application setup

Create OAuth applications under the site's organization accounts and configure only the providers you intend to enable. In each environment, set its server-only client ID, secret and exact callback URL using the placeholders in `.env.example`:

| Provider | Environment settings | Production callback |
| --- | --- | --- |
| Google | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` | `https://itechorbit.com/login/google/callback` |
| LinkedIn | `LINKEDIN_CLIENT_ID`, `LINKEDIN_CLIENT_SECRET`, `LINKEDIN_REDIRECT_URI` | `https://itechorbit.com/login/linkedin/callback` |
| GitHub | `GITHUB_CLIENT_ID`, `GITHUB_CLIENT_SECRET`, `GITHUB_REDIRECT_URI` | `https://itechorbit.com/login/github/callback` |

Use the canonical host's exact casing/hostname in both provider and app configuration. Local/staging applications need their own exact callback URLs. Google needs a Web application OAuth client with the application's origins and callback registered, and its consent screen configured for the desired audience. LinkedIn needs **Sign In with LinkedIn using OpenID Connect** enabled with `openid`, `profile`, and `email` scopes; email claims may be unavailable for some accounts. GitHub uses an OAuth App with its authorization callback configured; the app requests only the default `user:email` scope. References: [Google web-server OAuth](https://developers.google.com/identity/protocols/oauth2/web-server), [LinkedIn OIDC](https://learn.microsoft.com/linkedin/consumer/integrations/self-serve/sign-in-with-linkedin-v2), [GitHub OAuth Apps](https://docs.github.com/en/apps/oauth-apps/building-oauth-apps/creating-an-oauth-app), [Laravel Socialite](https://laravel.com/framework/docs/socialite).

#### Deployment and verification

Back up the database with the normal deployment procedure. Deploy PHP code, run `php artisan migrate --path=database/migrations/2026_10_17_000002_create_social_accounts_table.php`, configure credentials on the server, rebuild client/SSR assets, refresh configuration caches through the normal deployment process, and restart SSR/long-running workers. The providers remain unavailable before the table exists. A migration error about duplicate legacy identities requires resolving those specific duplicates before retrying. No existing accounts are deleted or assigned different roles.

To disable a provider, remove its server-side client credentials and refresh the config cache. Preserve `social_accounts` when rolling back presentation code; dropping it would discard connections, and the old Google-only controller does not implement the explicit linking policy. Do not roll back the connection table as a routine disable operation.

Automated coverage uses mocked provider user data/Guzzle responses for all three providers, legacy migration and identity uniqueness, email collisions, explicit connection, disabled accounts, expiry/replay/cancellation, session regeneration and GitHub verified-primary-email selection. Browser coverage uses synthetic OAuth credentials, captures login redirect responses before navigating to real providers, intercepts connection consent navigation, and checks buttons, errors, connected state and responsive centering. Live consent and token exchange still require staging verification with real provider applications; automated results do not certify provider-console configuration.

Local development verification: `APP_ENV=local` was confirmed inside `php-workspace-itechorbit-1`. The social-account migration was reviewed with `--pretend`, applied successfully to the local MariaDB database, and recorded as batch 5 / Ran. No production migration was performed. Automated verification passed 67 PHP tests / 1,780 assertions, 5 frontend tests, and 8 affected desktop/mobile browser regressions (6 existing authentication/account checks plus 2 social sign-in checks), along with TypeScript, ESLint, Pint and client/SSR builds.
