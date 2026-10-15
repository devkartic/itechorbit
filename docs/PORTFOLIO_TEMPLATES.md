# Portfolio template studio — 2026-10-15

## What changed

The existing `/portfolios` and `/portfolios/{key}` pages now render a saved user-specific design. Content, project detail URLs, contact routing, CV visibility, canonical URLs and publication controls are retained. Five designs use the same sanitized presentation payload:

| Design | Presentation |
| --- | --- |
| Studio | Sculpted violet identity, orbital artwork, lime work badge, tactile bento surfaces and an asymmetric project gallery. |
| Executive | Cinematic navy introduction with mint details, architectural identity frames and a career intelligence grid. |
| Blueprint | Dark technical workbench, mint circuit details, expertise nodes and a project-first gallery. |
| Editorial | Ivory engineering journal, oversized serif type, orange annotations, arch identity and alternating project stories. |
| Spotlight | Electric-blue identity stage, lime details, oversized outline typography and a visual work mosaic. |

Missing images use an initial-based identity card or neutral engineering artwork rendered in CSS/SVG; no client logos, claims, testimonials or achievements are fabricated. Empty sections are omitted. The first project occupies a complete showcase row. Symbolic project artwork displays the actual project name and technology tags; it is decorative artwork, not a fabricated product screenshot. Existing active content appears in all five templates, including introduction, objective, additional information, skills, experience, services, projects, social links and public CVs.

## User workflow

Open **Portfolio templates** in the workspace sidebar, or visit `/workspace/portfolio-templates`. Choose a design card to preview your own active content immediately. Switch between desktop and mobile preview widths, then click **Save template**. The current design is labeled and persists across visits. Previewing alone does not save a choice. Links are inactive inside the preview; the public portfolio retains working project, contact, social and CV links.

Project category filters work in both the preview and public page, with keyboard-operable buttons, pressed states and a result announcement. Public section navigation links to the work, experience, expertise and contact sections when present. Identity metadata and counts derive from active profile content. Decorative orbit lines do not imply availability, verified results or proficiency ratings.

Preview rendering uses the same React component as the public page, with CSS container queries for the preview width. It does not use an iframe or weaken the existing framing policy. Public pages remain rendered through the existing Inertia SSR process. The chooser is private and marked noindex through the workspace layout and security middleware.

Only active, verified users may view/save their own preferences. The server derives the user from the authenticated session, accepts five allowlisted template IDs, rate-limits writes and never accepts a target user ID. Previewing or saving does not enable portfolio publication. Hidden portfolios remain blocked publicly. The visible preview is limited to the same active fields and sanitized HTML as the public view; email, password, private CVs and inactive content are not added.

## Storage and deployment

Migration `2026_10_15_190000_add_portfolio_template_to_users.php` adds one nullable VARCHAR(32) column, `users.portfolio_template`. There are no record rewrites, publication changes or new dependencies. Null and unknown/retired values resolve to Studio.

Before releasing, back up the database using the existing deployment procedure, test the migration against a staging copy and apply it in the approved runtime:

```sh
php artisan migrate --pretend --path=database/migrations/2026_10_15_190000_add_portfolio_template_to_users.php
php artisan migrate --path=database/migrations/2026_10_15_190000_add_portfolio_template_to_users.php
npm ci
npm run build
```

Restart the existing SSR worker after deploying the matching client/SSR builds. On production, use the established approved deployment command/environment; no production deployment was performed here. Schema changes on a large users table should be scheduled according to the database's locking behavior.

The migration was applied and exercised on isolated SQLite test databases, then reviewed with `--pretend` and applied to the local development database through `docker exec php-workspace-itechorbit-1 php artisan migrate --path=database/migrations/2026_10_15_190000_add_portfolio_template_to_users.php`. Migration status confirms batch 3 / Ran. The host shell cannot resolve `mariadb`, but the application container can. The migration added only the nullable column; no existing records were rewritten. No production migration was run.

If another environment has not applied the migration, previews remain available, saving is disabled with a clear setup message, and the server rejects direct save attempts with a validation error instead of an unknown-column exception. Existing public reads safely fall back to Studio when the preference is absent.

For rollback, revert the feature's code/build and restart SSR. The nullable column may remain without affecting older code. Prefer leaving it in place to preserve selections. The migration's `down()` removes only this new column and discards saved template choices; obtain approval and a backup before executing that destructive rollback. Do not roll back unrelated migrations or restore an entire database just to change a portfolio design.

## Changed modules

- `PortfolioPresentation`: shared sanitized payload, five-choice catalog, safe default resolution.
- `PortfolioTemplateController`: authenticated self-service chooser and validated preference update.
- `PublicSiteController`: delegates existing portfolio payload creation to the shared service.
- `PortfolioView` and `portfolio.css`: reusable semantic sections and five structurally distinct responsive designs, transitions, focus states and reduced-motion support.
- `Admin/PortfolioTemplates`, `AdminLayout`, `admin.css`: template cards, local preview, device sizing, save/error/success/current state and sidebar entry.
- Two new workspace routes and one additive migration; no existing routes renamed.
- PHP regression coverage and richer synthetic browser fixtures; browser coverage loops through preview/save/reload/public SSR for every template on desktop/mobile.

## Validation

- PHP: 44 tests / 1,040 assertions passed, including five template tests and the missing-column regression.
- Frontend: 3 unit tests, TypeScript, ESLint, Pint and client/SSR builds passed.
- All 30 desktop/mobile browser regressions passed, including private preview/save/reload and public SSR checks for every template.
- No Lighthouse scores measured for this change. Portfolio view JS is about 16.8 KB / 4.8 KB gzip and template CSS about 33.4 KB / 7 KB gzip in the production build; these assets are shared between the public portfolio and private chooser.
- The 2026-10-16 art-direction refresh passed all 30 browser regressions, including keyboard/category filtering and all five saved layouts. Reduced-motion preferences disable gallery transitions/animations, and container sizing keeps private previews responsive. No new dependencies or schema changes were introduced by this refresh.
- Visual review uses synthetic profiles only. Screenshots below contain no application user data.

## Screenshots

- [Template chooser](assets/portfolio-template-studio-desktop.png)
- [Studio](assets/portfolio-studio-desktop.png)
- [Executive](assets/portfolio-executive-desktop.png)
- [Blueprint](assets/portfolio-blueprint-desktop.png)
- [Editorial](assets/portfolio-editorial-desktop.png)
- [Spotlight](assets/portfolio-spotlight-desktop.png)
- [Blueprint on mobile](assets/portfolio-blueprint-mobile.png)
