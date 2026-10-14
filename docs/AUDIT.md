# iTechOrbit discovery audit

Audited 2026-10-14 before application modifications. Branch: `modernization/laravel-react`.

## Verified facts

- Composer manifest requires Laravel ^9.11 / PHP ^8.0.2. Lock and running Artisan: **Laravel 9.52.22**. PHP CLI **8.3.3**, Composer **2.7.1**, Node **24.19.0**, npm **11.17.0**.
- Installed packages: Sanctum 2.15.1, Socialite 5.31.0, Tinker 2.11.1, laravel/ui 3.4.6; PHPUnit 9.6.38. No Inertia, React or TypeScript yet.
- Build: Laravel Mix 6 / webpack 5.105.0, Sass, Axios 0.25, Bootstrap 5. Public templates independently load Bootstrap, jQuery 3.6, AOS, Swiper, GLightbox, Font Awesome. AdminLTE, Select2, DataTables and CKEditor/TinyMCE exist in public assets. Three separate Blade layout systems.
- Local `.env` selects MySQL; default database config is also MySQL. PDO MySQL and SQLite installed. Read-only connection metadata request failed; **server version and live schema/data cannot be verified**. No writes were made to the configured database.
- 28 migration files cover users/roles, password resets, tokens, permissions, navigation, categories, tags, posts, polymorphic comments/replies, profile basics/social links/skills/services/projects/experiences/testimonials, contact inbox/newsletters, page content and queue tables. Schema inventory is in SCHEMA_BASELINE.md. Posts store tags as JSON in a string column; projects store technologies as JSON text; media references use `public/uploads/...`.
- Authentication uses laravel/ui sessions, registration, hashed local passwords, reset broker on existing `password_resets`, email verification, Google Socialite. Authorization uses custom permissions joining roles, links and routes, plus owner checks. Role 1 is super-admin; role 4 is default registration role. No registered policies.
- Admin resources: menus, modules, links, permissions, roles, users, categories, tags, posts, page content, profile basics/social links/skills/services/projects/experiences/testimonials, contact inbox. Media uploads are embedded in article/project/profile forms; no independent media library or generic site settings module exists.
- Public routes: `/` blog list, `/blogs`, `/blogs/{id}`, POST `/blogs/search`, `/blogs/category/{id}`, `/about/info`, `/service/info`, `/contact/info`, `/privacy-n-policy`, `/portfolios`, `/portfolios/{portfolio_id}`. Full method/name/action inventory: ROUTES_BASELINE.json. Article IDs, category IDs and portfolio keys must remain reachable. Public resource declarations also expose unused CRUD methods.
- Integrations: Google OAuth, Guzzle, SMTP/mail transports, queued contact and portfolio notification jobs, filesystem disks including optional S3 config. Existing public templates have Google Analytics, AdSense and site verification identifiers (identifiers are not secrets). No payment integration found.
- Tests: 8 existing PHPUnit tests; isolated baseline **7 pass, 1 fails** (homepage test has no posts table). Auth view assertions cover login/register/password reset/confirmation/verification. No existing CRUD, privacy, authorization, upload, SEO or browser coverage. Test config does not force a disposable database.
- Git starting state: only `.env.example`, dev config and root index tracked; root index already deleted, application source mostly untracked. User edits are preserved; an explicit source baseline is needed. No AGENTS.md found.

## Security findings before modernization

1. Blog search OR body condition is not grouped, bypassing public scope for private/unpublished posts. Blog detail `find()` similarly lacks status/private filtering and missing-page handling.
2. Some admin constructors perform custom permission checks without authentication middleware; contact controller adds middleware inside actions (too late). Reply creation has no auth enforcement; comment edit returns unrestricted data.
3. User list/post ownership filtering compares **user ID** to admin **role ID**, allowing user 1 inappropriate scope when its role changes.
4. OAuth falls back to stateless login on any error, bypassing state validation; Google account password is unhashed and uses a fixed remember token. Legacy GET logout mutates session.
5. Profile basic edit lacks ownership checking. Arbitrary `field_name` in media deletion may alter unrelated fields. Paths/deletion are environment-dependent and not constrained to upload directory.
6. Rich content is rendered raw without sanitization. Client-controlled names inserted into HTML notification. Public form email validation is incomplete, contact delivery marked sent at dispatch rather than actual delivery. No public form rate limits/honeypot.
7. Database cascades delete substantial related content; user/role/category/skill deletion requires particular care. Existing seeders are demonstration/bootstrap content and must never run against valuable data.
8. No root gitignore; missing forced testing DB isolation; env example needs a reviewed placeholder-only configuration. Existing assets carry older dependency risks.

## UI, SEO and performance findings

- Separate template systems duplicate navigation/forms/assets. Public pages use multiple external fonts and JS libraries. No measured Lighthouse baseline: no accessible running production-equivalent dataset yet.
- Empty meta descriptions, generic detail titles; no canonical, social metadata, dynamic sitemap or meaningful robots controls found. Existing Google verification should be preserved.
- Missing article detail can become 500; disabled portfolio lookup dereferences a missing user. Lists risk N+1 category/author lookups. Dashboard loads full users/posts; custom menu cache mutates its key and caches Eloquent objects.

## Assumptions and external verification still required

- Production is likely MySQL, but only local configuration is verified. Need operator read-only schema/version inventory and approved backup/restore rehearsal before deployment.
- No trustworthy client metrics, testimonials permission, project outcomes or AI expertise evidence has been supplied. Present existing permitted published records only; do not generate claims.
- Redis, production PHP/Node versions, domain proxy/CDN, SMTP, queue supervisor, analytics consent and media permissions are unknown.
- Production URL/content crawl and record/media counts require staging or operator data access. Source route inventory alone cannot prove every live URL or data preservation.

## Follow-up verification (2026-10-14)
The preserved discovery facts above describe the original Laravel 9 codebase. Current upgraded versions and checks are recorded in COMPLETION_REPORT.md. Direct public HTTP reads of the live homepage, two pagination pages and sitemap succeeded, yielding 110 URL strings and 49 article paths (LIVE_URL_SAMPLE.txt). Production database metadata/data remain unavailable. Live Lighthouse baseline: 70/78/77/83; final local synthetic mobile: 73/100/100/100. Neither proves production data preservation or post-release real-user performance.
