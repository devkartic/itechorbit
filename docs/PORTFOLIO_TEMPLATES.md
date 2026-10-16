# Portfolio template studio — 2026-10-15

## What changed

The existing `/portfolios` and `/portfolios/{key}` pages now render a saved user-specific design. Content, project detail URLs, contact routing, CV visibility, canonical URLs and publication controls are retained. Five designs use the same sanitized presentation payload:

| Design | Presentation |
| --- | --- |
| Studio | Sculpted violet identity, orbital artwork, lime work badge, tactile bento surfaces and an asymmetric project gallery. |
| Executive | Cinematic navy introduction with mint details, architectural identity frames and a balanced full-width career timeline and open skills/personal sections. |
| Blueprint | Dark technical workbench, mint circuit details, expertise nodes and a structured career gallery. |
| Editorial | Ivory engineering journal, oversized serif type, orange annotations, arch identity and alternating project stories. |
| Spotlight | Electric-blue identity stage, lime details, oversized outline typography and a visual work mosaic. |

Missing images use an initial-based identity card or neutral engineering artwork rendered in CSS/SVG; no client logos, claims, testimonials or achievements are fabricated. Empty sections are omitted. Project cards form centered rows. When no screenshot is uploaded, a compact text-free visual accent replaces the former large generated mockup, so the title/category/technologies are shown only in the content section. Uploaded screenshots remain genuine, uncropped previews and link to the full project detail page. Existing active content appears in all five templates, including introduction, objective, additional information, skills, experience, services, projects, social links and public CVs.

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
- No Lighthouse scores measured for this change. Portfolio view JS is about 16.8 KB / 5 KB gzip and shared portfolio CSS about 39.3 KB / 7.9 KB gzip in the production build; these assets are shared between the public portfolio and private chooser.
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

## Layout and visual quality corrections — 2026-10-16

The canvas uses content-driven heroes and shorter section spacing. The later alignment correction uses the shared site shell, as described below. Gallery rows wrap around a centered axis; an unmatched final card is centered, while a two-project gallery uses two complete showcase rows. Service columns fit the available content, and empty paired sections collapse. Executive experience occupies a complete row rather than leaving a short timeline beside a tall sidebar. Larger content labels and explicit theme-scoped heading/contact colors improve readability across public pages and private previews.

Cards have visible resting shadows and stronger hover/focus depth, with a colored edge light on Blueprint's dark surfaces. Floating identity chips and signal accents animate gently. IntersectionObserver triggers section reveals once as they enter view. Content remains visible when JavaScript or IntersectionObserver is unavailable. Reduced-motion preferences disable animations and transitions.

Browser coverage now includes five sparse synthetic profiles, incomplete gallery-row centering, gallery hover-shadow changes, reduced-motion behavior, and sampled heading/body/contact text contrast against their background surfaces. Contrast checks use the declared base surface of gradients and do not constitute a complete WCAG audit. Synthetic fixtures and all checks run separately from application data. No environment settings, database schema, dependencies or publication rules were changed.

Follow-up verification: all 30 browser tests passed, including the added checks on each template. PHP: 44 tests / 1,040 assertions; frontend: 3 tests; TypeScript, lint, Pint and client/SSR builds pass. Additional captures: [sparse desktop](assets/portfolio-sparse-desktop.png), [sparse mobile](assets/portfolio-sparse-mobile.png).

Portfolio contact now uses an inline personal form and a dedicated owner page, with owner-only notification delivery. The two inquiry email templates include full HTML/text message bodies. See [CONTACT_AND_EMAIL.md](CONTACT_AND_EMAIL.md) for updated routes, recipient rules, previews and verification.

## Project screenshot and card hierarchy — 2026-10-16

The supplied screenshot exposed a duplicated title and tags inside a generated mockup above the actual project content. That fallback has been replaced by a small decorative strip: 76px desktop, 64px mobile, without repeating any project text. Description and contribution text are larger, and card content follows one clear category → title → contribution → description → technology → action hierarchy.

Real screenshots are preserved. Upload a JPG, PNG or WebP (up to 2 MB) through **Admin → Projects → Create/Edit → Project screenshot**, then save the active project. The existing validated image storage path feeds the portfolio. Image previews use `object-fit: contain`, at 190px desktop / 160px mobile, and remain clickable to project details where the full screenshot is available. No existing media files or database records were removed by this presentation change.

Browser coverage now exercises a real screenshot upload through the admin form, verifies the image loads uncropped at the compact height, follows the project link, and cleans up only its own synthetic upload. Card checks also verify a single displayed title, compact empty-image accents, centered rows, contrast, and template selection on desktop/mobile.

Final browser result: 34 passed. Review: [card desktop](assets/project-card-desktop.png), [card mobile](assets/project-card-mobile.png), [uploaded thumbnail desktop](assets/project-screenshot-desktop.png), [uploaded thumbnail mobile](assets/project-screenshot-mobile.png). Upload verification uses a synthetic email preview image, not a claimed client project screenshot.

Entrance motion keeps text fully opaque from the first frame; only its position changes. This avoids the low-contrast text seen during an opacity fade.

## Header/footer alignment — 2026-10-16

Public portfolio content now reuses the exact shared `.shell` used by the header and footer: 80rem maximum outer width with 1.5rem gutters below the desktop breakpoint and 2.5rem on desktop (1.25rem on very narrow screens). Removed the competing portfolio-only maximum width and width-minus-gutter calculations. The personal contact page uses the same shell. Admin previews use matching container-width gutter rules without nesting another padded container.

Browser coverage measures both left/right portfolio content edges against the header and footer for each template at the standard mobile viewport and 768px, 1280px and 1440px desktop/tablet widths. Existing template content, cards, uploads and contact behavior are unchanged.

## Independent About and Expertise sections — 2026-10-16

About and Technology & Expertise are now two separate full-width rows in every template. They no longer occupy unequal-height side-by-side cards. Each row has a heading column and a readable content column, separated by a quiet vertical rule; narrow layouts stack those elements with a horizontal divider. Expertise tiles wrap within their own full-width section. Existing text, theme colors, subtle shadows and reduced-motion handling are retained. Empty rows remain omitted.

Verified all five designs in both public pages and admin previews, including full-row width/non-overlap measurements and shared header/footer edges. All 6 affected desktop/mobile browser flows pass, including personal contact and screenshot upload. TypeScript, lint and client/SSR builds pass. Updated screenshots above show the final row composition.

## Career-first order and open sections — 2026-10-16

The final section order is consistent across all five templates: introduction → skills → professional experience → selected projects → services → personal/About content → contact. Local section navigation follows Skills, Experience, Work, Connect. Sections without content remain omitted. The template choice controls art direction, rather than changing the order recruiters must follow.

The previous boxed profile rows and side-by-side heading/content columns were removed. Skills now use compact wrapping tiles with actual technology names and neutral code icons; decorative indices and link-like arrows were removed. Personal content is a simple readable About me section, with a quiet divider and natural reading width. There is no dedicated hobby field in the current schema; existing introduction/additional information is preserved without inventing hobbies or relabeling technical content as hobby facts.

TypeScript, lint, Pint and production client/SSR builds pass. Browser coverage explicitly checks the section order, Skills-first position, full-width content alignment, contact forms and uploaded screenshots on desktop/mobile.


### Candidate-first presentation (2026-10-16)
Public portfolios use the creator’s personal header with a theme-aware initials tile, creator name and Skills, Experience, Work and Connect section links where content exists. A discreet Main site link with a directional icon sits beside the section navigation; links have subtle hover underlines rather than individual bordered containers. On mobile, identity and return link share the first row, with section navigation below. The personal header stays sticky at the viewport top, with a full-width theme-matched translucent background, divider and subtle shadow. Its inner content uses the same centered shell and gutters as the portfolio sections. Anchor offsets keep section headings beneath it, including the two-row mobile header. The corporate site header remains absent; the footer contains only the current-year copyright in the creator’s name. The real professional title sits directly beneath the candidate name, replacing generic marketing slogans. A public CV is the first action when available; otherwise the opportunity contact action leads. Contact messages still belong to the candidate. There are no promotional, registration or navigation links in the footer.

The Technology & expertise grid uses six equal columns above 1,023px, three from 681–1,023px and two at 680px or below. Container queries also apply inside private mobile previews. Long skill names wrap instead of overflowing. Section order remains skills, experience, projects, services, About, then contact. This applies across all five templates and their private previews; the main business site and separate personal inquiry page retain their own layouts.

The template chooser includes an expandable content-preparation guide using existing public preview data. It checks presence of role, objective, skills, projects and a publicly visible CV; this is not a quality score or a promise of interviews. Existing registration, email verification, permission configuration and administrator-controlled publication still apply. Self-service automatic publication is not implemented by this presentation change.

No database, dependency or environment changes are required. Build client and SSR assets and restart the existing SSR process as part of the documented deployment workflow. Revert this presentation commit and rebuild to roll back; no data rollback is needed.


### Card and contact surfaces (2026-10-16)
Project, experience, capability, overview and skill cards share restrained theme-aware depth and refined borders. Project contributions have a distinct accent rule, technology tags use subtle surfaces, and the project action has its own footer divider. Real uploaded thumbnails remain compact and uncropped. Six-column expertise and personal sticky navigation are preserved.

Inline portfolio contact uses an open two-column composition with one form surface, replacing the enclosing colored parent card. The form inherits each theme, including Blueprint dark mode, and uses visible labels, focus rings, field errors and the existing submission status. The separate personal inquiry page is unchanged. Messages still target the portfolio owner; previews stay disabled. No dependency, database or environment changes are required.

### Optional portfolio banner (2026-10-17)

The existing **Basic information → Banner image** upload now appears below the personal navigation and above the introduction in all five templates. Public pages and private desktop/mobile previews share the same banner component and active-profile payload. The cover aligns with the header, content and footer, with borders and corners adapted to each theme. The profile photo remains separate.

The display uses a 4:1 cover above 680px container width and 2:1 on smaller containers. Images are centered and cropped with `object-fit: cover`, without stretching or modifying the original upload. Any image dimensions are accepted under the existing JPG/PNG/WebP and 2,048 KB validation. Absent, removed, inactive or missing banners leave no cover area; browser image-load failures also remove the cover, including failures before React hydration. No migration or dependency changes are required.

Regression coverage exercises every template and both preview widths, public desktop/mobile alignment, landscape/portrait/square uploads, original-file preservation, replacements, removal and failed-image fallback. PHP coverage checks shared preview/public data and inactive/missing-file handling.
