# Portfolio template studio — 2026-10-15

## What changed

The existing `/portfolios` and `/portfolios/{key}` pages now render a saved user-specific design. Content, project detail URLs, contact routing, CV visibility, canonical URLs and publication controls are retained. Five designs use the same sanitized presentation payload:

| Design | Presentation |
| --- | --- |
| Studio | Violet accents, an asymmetric profile hero, factual portfolio counts and bento sections. |
| Executive | Navy resume, persistent identity rail, career-first reading column and restrained project cards. |
| Blueprint | Dark grid canvas, mint accents, technical profile header and projects before experience. |
| Editorial | Ivory background, serif display typography, arch portrait and alternating horizontal project stories. |
| Spotlight | Centered cobalt hero, lime call to action and a large featured project followed by a project grid. |

Missing images use an initial-based identity card or neutral engineering artwork rendered in CSS/SVG; no client logos, claims, testimonials or achievements are fabricated. Empty sections are omitted. Single projects occupy a complete showcase row. Existing active content appears in all five templates, including introduction, objective, additional information, skills, experience, services, projects, social links and public CVs.

## User workflow

Open **Portfolio templates** in the workspace sidebar, or visit `/workspace/portfolio-templates`. Choose a design card to preview your own active content immediately. Switch between desktop and mobile preview widths, then click **Save template**. The current design is labeled and persists across visits. Previewing alone does not save a choice. Links are inactive inside the preview; the public portfolio retains working project, contact, social and CV links.

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

The migration was applied and exercised on isolated SQLite test databases. The configured application connection uses the `mariadb` hostname, which does not resolve from this shell. Its migration status could not be read, and this migration has **not** been applied to that database. Saving a design requires applying it in the runtime where that database is reachable. Existing public reads safely fall back to Studio when the preference is absent.

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

- PHP: 43 tests / 1,027 assertions passed, including four new template tests.
- Frontend: 3 unit tests, TypeScript, ESLint, Pint and client/SSR builds passed.
- All 30 desktop/mobile browser regressions passed, including private preview/save/reload and public SSR checks for every template.
- No Lighthouse scores measured for this change. Portfolio view JS is about 9.8 KB / 3 KB gzip and template CSS about 18 KB / 3.8 KB gzip in the production build; these assets are shared between the public portfolio and private chooser.
- Visual review uses synthetic profiles only. Screenshots below contain no application user data.

## Screenshots

- [Template chooser](assets/portfolio-template-studio-desktop.png)
- [Studio](assets/portfolio-studio-desktop.png)
- [Executive](assets/portfolio-executive-desktop.png)
- [Blueprint](assets/portfolio-blueprint-desktop.png)
- [Editorial](assets/portfolio-editorial-desktop.png)
- [Spotlight](assets/portfolio-spotlight-desktop.png)
- [Blueprint on mobile](assets/portfolio-blueprint-mobile.png)
