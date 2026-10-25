# Project structure

Example placeholders: IP addresses, private email addresses, SSH users, host paths and infrastructure names below are illustrative. Replace them with verified values from your private deployment configuration before running commands. These examples do not change production settings.

The application has three areas: the public company website, public user portfolios, and the private administration workspace. Authentication and account screens are separate supporting areas. Laravel's standard bootstrap, configuration, migrations, middleware, providers, commands, jobs and mail conventions remain in place.

## Application code

| Area | Controllers | Services | React pages |
| --- | --- | --- | --- |
| Website, articles, services, work, SEO | `app/Http/Controllers/Website/` | `app/Services/Shared/PublicContent.php` | `resources/js/pages/Website/` |
| Public portfolios and personal inquiries | `app/Http/Controllers/Portfolio/` | `app/Services/Portfolio/` | `resources/js/pages/Portfolio/` |
| Admin resources, navigation, permissions, notices | `app/Http/Controllers/Admin/` | `app/Services/Admin/` | `resources/js/pages/Admin/` |
| Sign-in, registration, provider connections | `app/Http/Controllers/Auth/` | `app/Services/Auth/` | `resources/js/pages/Auth/` |
| Account profile and settings | `app/Http/Controllers/Account/` | Shared services | `resources/js/pages/Account/` |
| Shared public rendering and error pages | `app/Http/Controllers/Shared/` | `app/Services/Shared/` | `resources/js/pages/Shared/` |

`Shared/PublicPageController` supplies the existing SEO envelope and inquiry dispatch behavior to the website and portfolio controllers. Admin authorization remains in the existing server checks. Sidebar configuration controls navigation visibility, not access rights.

`routes/web.php` loads `website.php`, `auth.php`, `admin.php`, and `portfolio.php`. The website service routes must load before the admin service resource routes because they share the `/services` prefix. All URL paths, route names, middleware, constraints and permissions retain their existing behavior. `routes/api.php` and Laravel's console/channel routes retain their standard locations.

## Frontend

- `resources/js/pages/`: Inertia entry pages grouped by application area; the server returns names such as `Website/Blog`, `Portfolio/Show`, and `Admin/Resource`.
- `resources/js/components/website/`, `portfolio/`, `admin/`, `auth/`: components owned by an area.
- `resources/js/components/shared/`: branding, SEO, links, and the contact form reused across areas. `components/ui/` contains reusable UI primitives.
- `resources/js/layouts/website/` and `layouts/admin/`: company website and private workspace shells. Portfolio layouts belong to `components/portfolio/PortfolioView.tsx` and remain independent of the company shell.
- `resources/css/app.css`: Tailwind entry, base tokens, and shared/company styling. `resources/css/admin/admin.css` and `resources/css/portfolio/portfolio.css` contain scoped area styles.
- `resources/js/lib/` and `hooks/`: shared types, HTTP helpers, utilities, and hooks. `app.tsx` and `ssr.tsx` resolve the same page tree for browser hydration and server rendering.

## Public files and stored data

`public/` is the web server document root. It contains entry files, brand assets, generated `build/` files and publicly addressable media. It does not contain the current page source.

`public/blog/img/` and `public/portfolio/img/` are historical image URLs that stored articles and profiles may reference. The production branch removes obsolete admin libraries and Blade screens. Keep these URL paths stable; do not develop current pages there. New frontend code belongs in `resources/` and is built by Vite. Existing uploads and private media preview permissions remain unchanged.

`resources/views/app.blade.php` is the active Inertia shell. Current email bodies live in `resources/views/mail/`. The retained compatibility mail template lives in `resources/views/legacy/admin/mail_templates/`; its existing callers use the `legacy.admin.mail_templates.mail` view name. Obsolete Blade admin screens and their view composers remain removed from the production branch.

Models remain in `app/Models/` and `app/Models/Admin/` with their existing class identities and table mappings. Despite the historical Admin namespace, some models represent public content and portfolio records. Existing polymorphic comments store model class names, and queued jobs/mail can store serialized class names, so those identities are intentionally stable. Reorganizing them requires a separate data and queued-message compatibility migration. No database or stored-file migration is required for this directory refactor.

## Verification and deployment

Backend coverage lives in `tests/Feature/` and `tests/Unit/`; browser flows and synthetic assets live in `tests/browser/`. Tests use disposable SQLite data and a non-relaying local SMTP sink. Operational instructions are in [DEPLOYMENT.md](DEPLOYMENT.md). The Nginx and Supervisor examples in `deploy/` describe a separate native-host installation; they are not the active Docker VPS configuration.

After deploying moved classes, rebuild Composer's autoloader, clear/rebuild Laravel caches, rebuild client/SSR assets, and restart PHP, SSR and queue workers together. Keep the existing application key, database and media. No schema change is introduced by this refactor.

Production uses a fixed source mount at `/srv/example-project/src`, exposed read-only at `/var/www/html` in PHP/Nginx and `/app` in SSR. Storage and bootstrap cache use persistent writable volumes. The deployment script stages and builds a release separately, then synchronizes source in place during maintenance; it does not switch a `current` symlink. The single parent `../.docker/` directory contains project infrastructure and environment-specific overrides. Source deployment does not provision cron, queue consumers or mail services; see [services](operations/SERVICES.md) and [scheduler/queues](operations/SCHEDULER.md).
