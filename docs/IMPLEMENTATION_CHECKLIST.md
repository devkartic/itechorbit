# Implementation checklist

Status legend: [x] implemented/verified, [ ] pending. Updated continuously; incomplete checks are release gates.

## Phase 1 — Discovery
- [x] Inspect dependencies, runtimes, routes, controllers, models, middleware, migrations, auth, integrations and views.
- [x] Capture route/schema inventory and distinguish verified facts from assumptions.
- [x] Audit risks and baseline tests: 7/8 pass; example lacks schema.
- [x] Create modernization branch; preserve existing user changes.
- [ ] Production database version/schema, complete live URL crawl and record/media inventory (operator access needed).

## Phase 2 — Preparation
- [x] Review official major-version guides and frontend compatibility.
- [x] Safe source baseline commit, forced SQLite isolation, critical regression suite (14 tests / 52 assertions).
- [ ] Verified backup/restore rehearsal on isolated staging (operator infrastructure needed).

## Phase 3 — Backend
- [x] Initial security regression fixes: privacy/search, owner scope, auth/replies, email validation, OAuth state/hash, media fields/paths. More coverage added during feature migration.
- [x] Laravel 10.50.3 / UI 4 / Sanctum 3 / PHPUnit 10: 14 tests, 52 assertions. Intermediate framework has reported security advisories; continue to supported release.
- [x] Laravel 11.57.0 / Sanctum 4: 14 tests, 52 assertions. Owned token migration extended additively in isolated tests only.
- [x] Laravel 12.70.0 / Carbon 3 / PHPUnit 11: 14 tests, 52 assertions; no Composer advisories.
- [x] Laravel 13.36.0 / Tinker 3 / PHPUnit 12.5.39: 14 tests, 52 assertions, no Composer advisories. CSRF origin protection retained; cache arrays and legacy session configuration reviewed.

## Phase 4 — Frontend foundation
- [x] React/TypeScript/Inertia/Vite/Tailwind/Lucide and reusable UI.
- [x] Public SSR and crawlable-content checks.

## Phase 5 — Public website
- [x] Homepage, factual services, portfolio index/detail/profile.
- [x] Blog search/category/tags/pagination/detail/reading time/TOC/code/comments.
- [x] About/Contact/privacy and validated inquiries.
- [x] Responsive navigation, accessibility, error/empty/loading states.

## Phase 6 — Admin
- [x] React dashboard and content CRUD.
- [x] Categories/tags/projects/users/inquiries/profile/security modules preserved.
- [x] Server permissions, owner isolation, validation/uploads/pagination.

## Phase 7 — SEO, performance, security
- [x] Metadata/canonicals/OG/schema/sitemap/robots/redirects.
- [x] Dependency audits/security headers/auth/forms/media.
- [x] Measured performance and query/bundle checks; do not fabricate Lighthouse scores.

## Phase 8 — Verification
- [x] PHP tests/Pint; frontend tests/types/lint/build.
- [x] Playwright responsive/public/auth/admin/SEO smoke tests.
- [x] Source URL compatibility and route/config/view cache checks.
- [ ] Operator staging data preservation/crawl/SMTP/OAuth/queue validation.

## Phase 9 — Release preparation
- [x] Placeholder env, runtime/development docs, CI, staging/deployment/rollback guide.
- [x] Accurate completion report and remaining risks.
- [ ] Explicit production deployment approval (no deployment attempted).

## Blockers / observations
- Configured MySQL metadata connection unavailable; tests use disposable SQLite only.
- Sandbox network DNS restricted; approved network commands can retrieve official references.
- No trustworthy production metrics/testimonials or infrastructure evidence; no claims invented.

## Final local verification — 2026-10-14
- Framework 13.36.0, PHP 8.3.3 local; React 19.3.0, TypeScript 6.0.3, Inertia Laravel 3.5.1 / React 3.9.1, Tailwind 4.3.3, Vite 8.3.4.
- 28 PHP tests / 617 assertions; 3 Vitest tests; TypeScript, ESLint, Pint, Composer validation and client/SSR build pass. Browser suite passes 10 desktop/mobile tests.
- Composer/npm audits: zero known advisories. Route cache with a temporary cache path and Blade compilation pass.
- Lighthouse 13.5.0 local synthetic mobile: Performance 73, Accessibility 100, Best Practices 100, SEO 100. LCP 4.7s, CLS 0.032, TBT 30ms. Performance target 90 remains unmet; production compression/cache/CDN and representative content must be measured on staging.
- Discovered 110 public URL strings (including host variants), including 49 article paths, from the live homepage, its pagination and sitemap. Added permanent redirects for legacy `/?page=N`; preserve concrete article/category/profile URLs. Full crawl reconciliation with production data remains a release gate.
- Preserved profile-specific inquiry delivery; recipient is an active public profile and private email is never serialized.
- Fixed SSR title rendering/escaping, required-field browser selectors, Vitest/Playwright discovery, fixture process cleanup and forced cache/mail/queue/session test isolation.
- GitHub Actions workflow installed at actual workspace Git root; PHP 8.5 matrix execution has not yet run remotely. Nginx/Supervisor examples and deployment/rollback guidance prepared.
- No valuable database writes or production deployment. Backup/restore, data/media counts, SMTP/OAuth and production runtime verification still require operator staging access.

- Live pre-release Lighthouse baseline: 70 Performance / 78 Accessibility / 77 Best Practices / 83 SEO (LCP 9.2s). Remote/live vs local/synthetic runs are different workloads, not a controlled performance comparison.

## Visual and navigation revision — 2026-10-14
- [x] Replace the initial blue/slate treatment with ivory, deep ink, violet and lime accents.
- [x] Recompose the hero, engineering illustration, feature sections and dark contact/footer panels.
- [x] Improve display typography, heading hierarchy, body measure and spacing; continue self-hosting Inter.
- [x] Add restrained card elevation, image hover, button shadows, focus styling and reduced-motion support.
- [x] Use native anchors for public navigation/cards/pagination so links remain functional before hydration and without JavaScript. Admin/forms retain their existing Inertia behavior.
- [x] Sticky header, compact scroll state, scroll shadow, active links and native mobile menu with Escape handling. Full navigation begins at 1024px to avoid tablet crowding.
- [x] Browser tests cover navigation with JavaScript disabled and sticky-header/overflow behavior at 390/768/1024/1280px. 14 checks pass; types, lint, builds, 28 PHP tests and 3 frontend tests pass.
- Previous Lighthouse numbers describe the earlier modernization theme; the redesigned theme has not been remeasured and no new scores are claimed.
- Changes are local only. The specific user preview URL was requested but has not yet been provided; no production deployment occurred.

## Future-facing visual revision and encryption fix — 2026-10-14
- [x] Dark blueprint hero with violet/mint illumination, glass surfaces and lime CTA; retain readable light content sections and sticky header.
- [x] Interactive React/TypeScript, Laravel/PHP and data architecture tabs with pointer/arrow/Home/End keyboard controls and connected tab panels.
- [x] Reduced-motion-aware decorative light effects; interactive controls remain stationary.
- [x] Replace unconditional history encryption with environment-aware defaults: local/testing off, production on, explicit INERTIA_HISTORY_ENCRYPT override. Production HTTPS requirement documented from official Inertia docs.
- [x] PHP verifies encryption flag configuration; browser regression tests search/back navigation with Web Crypto unavailable, avoiding the reported unhandled promise.
- [x] Clear local config cache and rebuild assets. No production configuration or deployment changed.

## AI offering highlight — 2026-10-14
- [x] Add a highlighted homepage AI section with knowledge assistants, document processing and assisted operations use cases.
- [x] Add `/services/ai-workflow-integration` with a scoped pilot approach, deliverables, technologies and relevant inquiry CTA.
- [x] Highlight AI in header/mobile/footer navigation and the existing services listing. Service route allowlist, sitemap, robots and structured data reuse existing configuration-driven behavior.
- [x] Keep copy factual: proposed engagements/use cases, no invented AI project history, results, testimonials or provider partnership. No live model calls, credentials, purchases or dependencies added.
- [x] Verify 29 PHP tests / 635 assertions, 3 frontend tests, 20 browser checks, types/lint/builds. Visually review desktop/mobile AI screenshots.

## Typography clarity correction — 2026-10-14
- [x] Remove rotation and backdrop filters from text-bearing panels; use opaque surfaces with the existing borders/glow styling.
- [x] Restore native browser font rendering, prevent synthetic font weights, ease heading tracking and strengthen muted text contrast.
- [x] Increase small labels/code to readable sizes; use solid mint hero emphasis instead of clipped gradient glyphs.
- [x] Keep hover shadows/image/icon effects while removing translation from text-bearing cards/buttons.
- [x] Constrain architecture panels on mobile, keep code scrolling within its panel and move the note below the panel to prevent text overlap.
- [x] Verify self-hosted Inter loads; 20 browser checks pass, followed by 6 final layout/typography/navigation checks after the mobile adjustment. Types, lint and client/SSR builds pass; desktop/mobile typography captures reviewed.

## Engineering studio framing refinement — 2026-10-14
- [x] Restore the tilted studio aesthetic using violet/mint decorative sheets behind the upright content panel.
- [x] Coordinate toolbar/border/shadow colors with the site theme and keep pointer/keyboard controls stationary.
- [x] Verify 6 desktop/mobile layout, sticky-header and architecture interaction checks, plus types/lint/builds. Text remains untransformed.

## Single-panel rotation and spacing correction — 2026-10-14
- [x] Remove layered framing and restore the original single studio panel at -2 degrees, as explicitly requested.
- [x] Reduce standard section padding from 64/96px to 40/64px (mobile/desktop); reduce hero/page-intro and section-heading spacing.
- [x] Remove doubled gaps before the AI highlight/contact panel; reduce AI panel inner padding while preserving the approved colors and improved font sizes.
- [x] Verify 8 affected desktop/mobile layout, navigation, studio interaction and AI inquiry checks, plus types/lint/builds; review the overall page rhythm.

- [x] Remove decorative dot from public header/footer, authentication and admin branding (user-requested brand correction).

## Header and account experience — 2026-10-14
- [x] Refine the sticky header with pill navigation, elevated active links, a two-line brand treatment, theme accents and responsive actions.
- [x] Add guest Sign in and an authenticated avatar dropdown with profile, settings, workspace, optional public portfolio and secure POST logout.
- [x] Support keyboard navigation, Escape/focus restoration and outside-click dismissal; retain mobile navigation.
- [x] Implement protected self-service profile/email/password settings with validation, independent rate limits and current-password checks for sensitive changes.
- [x] Exclude account pages from indexing and clear Inertia history after logout. Password changes refresh the session/CSRF state.
- [x] Verify 34 PHP tests / 693 assertions, 3 frontend tests, 22 desktop/mobile browser checks, types, lint, Pint and client/SSR builds. Review account-menu screenshots on both viewports.
- [x] Update CI paths for the application's standalone Git repository. No production deployment or existing-data migration performed.

## Open navigation and engineering/AI logo — 2026-10-14
- [x] Remove boxed/pill navigation and item backgrounds; use open spacing, fine animated underlines and a small AI accent.
- [x] Simplify Sign in and the mobile menu control while preserving sticky behavior and account functionality.
- [x] Create a custom SVG engineering-core/orbit/AI-node symbol with light/dark wordmark exports and a favicon.
- [x] Apply the shared symbol to public, authentication and workspace branding; document meaning, colors and assets in BRAND.md.
- [x] Review the light/dark logo preview; verify 8 affected desktop/mobile navigation, sticky-header and account checks, TypeScript, ESLint and client/SSR builds.

## Unified wordmark refinement — 2026-10-14
- [x] Follow the supplied logo references and subsequent direction: remove the left illustration, integrate a subtle orbit around the O and use ENGINEERING WITH AI.
- [x] Consolidate the name, orbit and tagline in one SVG image with embedded Inter; apply background-specific coloring to the same asset.
- [x] Replace branding in public, auth, admin and retained legacy views; use a PNG export of the same artwork for email. Preserve personal portfolio names.
- [x] Update browser and mobile icons to the orbital O; remove superseded logo variants and the former symbol component.
- [x] Compile retained Blade templates; verify types/lint and client/SSR builds. PHP regression suite passes (34 tests / 693 assertions).
- [x] Verify the final single-image wordmark with 22 browser checks and review dedicated desktop/mobile header captures and the dark-background preview.
- [x] Replace the faint watermark and font O with a clear orbital O icon; regenerate the matching email image, browser icon and mobile shortcut icon. Verify 8 affected desktop/mobile browser checks, types, lint and client/SSR builds; review header and light/dark captures.
