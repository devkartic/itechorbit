# Website cleanup — 2026-10-15

Removed 200 tracked files, approximately 26 MB (24.82 MiB including one ignored .DS_Store file). Work is isolated on `refactor/website-cleanup`. This phase covers the retired public website and unused shared scaffolding; admin cleanup remains a separate task.

## Evidence for removal

Every current public route resolves to PublicSiteController/Inertia. BlogController and PortfolioController have no callers in routes, providers, application code or tests. The removed Blog model was used only by its retired controller/composer and one legacy-helper test; the test now checks privacy through both active search endpoints. The empty Portfolio model was used only by its retired controller. The blog view composer's registration was removed with it, leaving admin composers intact.

The removed Blade trees were reachable only from those retired controllers. The Bootstrap auth/home scaffolding had no current render callers; all auth controllers render React Auth. Its Sass and Mix outputs had no remaining entry point or live asset reference. Vite is the active build. Removed public theme CSS/JS/vendor trees were referenced only from the retired blog/portfolio views. Old brand, static architecture and studio-module CSS selectors had no references in React source. Runtime-generated syntax-highlighting selectors are preserved.

| Removed group | Tracked files |
| --- | ---: |
| `app/Http/Controllers/BlogController.php` | 1 |
| `app/Http/Controllers/PortfolioController.php` | 1 |
| `app/Models/Blog.php` | 1 |
| `app/Models/Portfolio.php` | 1 |
| `app/View/Composers/Blog` | 1 |
| `resources/views/blog` | 19 |
| `resources/views/portfolio` | 14 |
| `resources/views/auth` | 6 |
| `resources/views/layouts/app.blade.php` | 1 |
| `resources/views/home.blade.php` | 1 |
| `resources/sass` | 2 |
| `public/blog/css` | 2 |
| `public/blog/js` | 1 |
| `public/blog/vendor` | 69 |
| `public/portfolio/css` | 3 |
| `public/portfolio/js` | 1 |
| `public/portfolio/vendor` | 69 |
| `public/css` | 2 |
| `public/js` | 3 |
| `public/mix-manifest.json` | 1 |
| `public/favicon.ico` | 1 |

## Preserved

- All 210 routes: before/after JSON inventories compared equal, including method, URI, name, action and middleware.
- All 147 files beneath `public/blog/img` and `public/portfolio/img`: byte-for-byte SHA-256 verification. These may appear in existing article/media URLs and must not be inferred unused from source searches.
- Existing uploads, storage files, database files, migrations, seeds and public content records. No existing database was queried or modified for this cleanup.
- Admin controllers, Blade layouts/assets and composers, shared icon libraries, mail templates and pagination overrides. Their deeper cleanup awaits the separate admin refactoring phase.
- React/TypeScript pages, shared components, brand assets, installed Composer/npm dependencies, generated Vite/SSR runtime builds, tests, operational documentation and deployment configuration.

## Validation

- PHP: 34 tests, 716 assertions; Pint passed.
- Frontend: 3 Vitest tests; TypeScript and ESLint passed.
- Client and SSR production builds passed; Composer autoload regenerated.
- Remaining Blade templates compiled successfully, and compiled views were cleared afterward.
- Full desktop/mobile browser suite: 24 passed. After the final unused CSS removal, 8 homepage, header, auth-branding and architecture interaction checks passed again.
- Dependency/reference scan found no live caller of the removed controllers/models/composer, public views or Mix outputs. No new performance score is claimed.

Tests use disposable SQLite data and synthetic fixtures. No production release, mail delivery, dependency upgrade or data migration occurred.

## Recovery

Use Git history to review or revert the website-cleanup commit. After restoring code, run `composer dump-autoload`, rebuild client/SSR assets and clear compiled views; restart the local/staging SSR process if used. No database rollback is needed. Restoring old public templates requires restoring their matching controllers, assets and composer registration together. Keep the retained public images and uploaded media in place.
