# Migration plan

## Official references retrieved 2026-10-14
- https://laravel.com/docs/10.x/upgrade
- https://laravel.com/docs/11.x/upgrade
- https://laravel.com/docs/12.x/upgrade
- https://laravel.com/docs/13.x/upgrade
- https://laravel.com/docs/13.x/releases
- https://inertiajs.com/server-side-setup
- https://inertiajs.com/server-side-rendering
- https://react.dev/versions
- https://tailwindcss.com/docs/installation/using-vite
- https://ui.shadcn.com/docs/installation/laravel

Guides downloaded to temporary reference files; dependencies also checked via official registries. Never ignore platform requirements or select prereleases. PHP remains 8.3.3 locally; use latest compatible stable PHP on staging after testing, without changing host-wide runtimes.

## Dependency compatibility and major steps
| Step | Required changes | Verification |
| --- | --- | --- |
| 9 → 10 | PHP ≥8.1, framework ^10, Sanctum ^3.2, UI ^4, Ignition ^2, Collision ^7, PHPUnit ^10, stable Composer resolution | Isolated regression tests, auth views, route inventory |
| 10 → 11 | PHP ≥8.2, framework ^11, Sanctum ^4, Collision ^8; keep kernels/providers; preserve owned token migration | Review float columns (fresh schemas only), explicit Sanctum middleware, auth/password reset tests |
| 11 → 12 | framework ^12, PHPUnit ^11, Carbon 3 | Date output regression, pagination, no column alterations |
| 12 → 13 | framework ^13, Tinker ^3, PHPUnit ^12 (PHP ≥8.3) | Request forgery middleware, cache/session serialization, legacy explicit prefixes, route caching |
| Frontend | registry stable React 19.3, Inertia React 3.9, Vite 8.3, Laravel plugin 3.2, Tailwind 4.3, Node ≥22.12 (local 24.19), TS 6.0 (locked 6.0.3) | Actual installs/lockfiles are final authority; check adapter compatibility, SSR/build/types/lint/tests |

Review all guides: this app has no raw expression casts, custom Monolog handlers, `$dates`, custom database drivers, UUID traits, custom password providers or column-change migrations. Preserve explicit local filesystem root and password-reset table. Add Sanctum `expires_at` only through an additive pending migration; staging/operator approval required before database/deployment changes. Replace cached Eloquent objects with array payloads before Laravel 13 strict serialization. Laravel 13 request forgery middleware must be applied with CSRF protections retained.

## Execution gates
1. Capture source/routes/schema baseline, forced isolated test config, critical privacy/auth/upload/contact regressions.
2. Fix critical defects; upgrade one major at a time with Composer lock snapshots and test results.
3. Introduce public React foundation, SSR and preserved URL mapping; migrate admin incrementally without dropping workflows.
4. SEO/security/performance checks and CI; publish accurate test/build report.
5. Staging only with operator backup, schema diff and data/media counts. No production deployment without explicit approval.

## Rollback
Preserve initial working files and source baseline commit. Each major dependency update has a reversible commit. Restore prior source + composer/package lock, reinstall locked dependencies, rebuild assets, restore previous SSR service. Preserve APP_KEY, session cookie and cache prefixes; clear compiled caches appropriately. No destructive data operations. If an approved future schema migration runs, prefer forward-compatible old code; rehearse restoring encrypted database/media backups on an isolated environment, verify checksums/counts and business flows. Never seed production or restore over live data without approval.
