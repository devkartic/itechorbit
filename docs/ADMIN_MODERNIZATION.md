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
