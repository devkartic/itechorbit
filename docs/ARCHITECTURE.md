# Architecture

## Existing system
Laravel monolith with legacy HTTP/Console kernels and providers; session auth + Sanctum API guard; database-driven role/link/action permission matrix; owner-scoped portfolio modules. Blade public blog/profile and AdminLTE admin. Keep existing schema, IDs, data and upload references.

## Target
Laravel 13, retaining the supported legacy application structure. React 19 + TypeScript + Inertia 3 pages; Vite 8 + Tailwind 4, Lucide and small shadcn-compatible UI primitives. Public request DTOs whitelist fields (never serialize full users/profiles). Server selects published data, eager-loads associations and paginates. Public HTML requires Inertia SSR with a supervised Node process and fail-closed rendering in production; verify crawler responses before release.

Public pages share typography, navigation, cards, breadcrumbs, pagination and contact form. Existing `/blogs/{id}` and `/portfolios/{key}` remain. New services use `/services/{slug}` because `/services` is an existing admin resource. About/Contact retain old URLs; friendly aliases redirect permanently. Admin migration is incremental: React management pages preserve existing resource URLs and custom server permissions; legacy capabilities remain available until equivalent operations are verified. No database replacement or production migration is authorized.

Security: session/CSRF protection, server authentication and permission middleware, ownership checks, published scope, HTML sanitization at rendering, bounded validation, safe media paths, form throttling. Jobs report delivery only after successful mail. File cache works by default; Redis remains opt-in after infrastructure verification. No real data is used in tests: forced SQLite memory database with migrated schema and synthetic fixtures.

## Implemented modules
PublicSiteController handles agency/public pages, legacy article/profile URLs and profile-specific inquiry recipients. PublicContent sanitizes rich HTML and whitelists public data. SitemapController owns XML/robots. AdminPresentation supplies server-scoped pagination, options and permissions to React management pages while existing controllers retain mutations. Auth screens use the existing laravel/ui sessions/reset/verification backend.

Page components load independently; article highlighting loads only on article pages. Tailwind scans React and the root view rather than legacy templates. Existing Blade/template assets remain on disk for reference and rollback, and are not loaded by the modern layouts. CSS motion respects reduced-motion preferences.
