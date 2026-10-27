# Public-page SEO and business-content audit

Reviewed 27 October 2026. Implementation and database changes were verified locally. Production was not deployed or certified by this audit.

## Coverage and evidence

The final local crawl covered **203 URLs**, including **125 sitemap URLs**, published detail pages, portfolio contact pages, pagination, linked filters, authentication pages, aliases and a deliberately missing page. It checked **158 internal link targets** and found **no broken internal links**. Every sitemap URL returned HTTP 200 with one server-rendered H1, a title, description and canonical URL. Checked JSON-LD parsed, and checked images had alt attributes and dimensions. These checks validate markup and routing; they do not prove indexing, factual accuracy of every tutorial, image availability at external hosts or ranking eligibility.

The sitemap contains 49 articles, 17 article categories, 31 personal portfolios, 10 projects, 9 services, 1 product and the main business/collection pages. Account, admin, private articles, disabled portfolios and unpublished content remain outside public sitemaps.

Evidence comes from the local database, route/controllers, a standard-library HTML crawl (`dev/audit-public-pages.py`), isolated PHP/React/browser tests and a real Google reCAPTCHA verification. A production HTTPS probe failed because this environment could not build a trusted certificate chain; this is not a conclusion that the production certificate is invalid. Production indexing, Search Console metrics, backlinks and field Core Web Vitals were unavailable.

## Findings and implemented changes

| Priority | Pages | Evidence and correction | Content source | Verification |
| --- | --- | --- | --- | --- |
| High | All public HTML | Local PHP and SSR were on different Docker networks, producing client-only HTML. Connected the local SSR service to PHP’s backend network and refreshed the build. | Local hosting configuration | PHP SSR health check succeeds; all sitemap pages have visible SSR content. Production needs its own check. |
| High | `/about/info` | Stored copy introduced a technical blog, conflicting with the engineering business. Replaced matching legacy title, introduction and body with the actual services/products/knowledge context. | `website_about` | Fingerprint-checked command applied after a private backup. |
| Medium | `/service/info`, `/` | Listing copy named growth services absent from the current published catalog. Added editable, catalog-appropriate page copy and neutral fallback copy. | `website_pages`, `website_services` | Editor/public rendering tests; crawl checks. |
| Medium | `/contact/info` | Removed legacy font wrappers and empty headings while retaining the contact purpose and stored contact details. | `website_contact` | Backed-up correction and mobile browser review. |
| Medium | `/privacy-n-policy` | “Privacy & Policy” / “Term & Conditions” labels did not describe the page. Corrected labels, business description and factual CAPTCHA disclosure without replacing unrelated commitments. | `privacy_policies` | Backed-up correction; public rendering checked. |
| Medium | 33 article titles | Corrected spelling, grammar and technology capitalization without changing IDs, URLs or tutorial bodies. | `posts.title` | Original-value fingerprints and private backup; all article URLs still resolve. |
| Medium | Article categories | Category URLs shared generic collection metadata. Added category-specific titles, descriptions and headings; unpublished categories return 404 and are absent from navigation/sitemaps. | `categories`, `posts` | PHP and rendered HTML checks. |
| Medium | Public portfolios | Every owner was labeled as an engineer. Metadata now uses the owner’s active designation and introduction; empty fallbacks say “Professional portfolio.” | `users`, `basics` | Role-specific PHP test and crawl. |
| Medium | `/portfolios` | Owner alias duplicated the keyed portfolio in sitemaps. Alias now canonicalizes to the keyed URL and is omitted from the sitemap when that URL exists. | Portfolio controller, sitemap controller | Canonical/publication tests; duplicate alias removed from sitemap. |
| Medium | Article detail pages | Incrementing view counts updated content modification timestamps. Views now leave `updated_at` unchanged. | `posts` | Regression test verifies the content date stays unchanged after a view. |
| Medium | Search/filter/pagination URLs | Work filters were indexable duplicates. Filters/search are `noindex,follow`; pagination keeps its own canonical and page number in metadata. | Shared page/controller metadata | PHP tests and crawl. |
| Medium | Detail/social metadata | Added optional absolute social preview images and alt text, stable metadata keys, breadcrumb JSON-LD and appropriate Product brand/Offer properties. Prices and ratings are not invented for quote-based products. | Existing record media, `website_pages` | PHP, build and rendered HTML checks. |
| High | Guest submissions | Added server-verified Google CAPTCHA, including legacy inquiry endpoints. Current local keys are v3; v2 checkbox remains supported through configuration. | `.env`, server middleware, shared React component | Real v3 token verified for the configured hostname; intentionally invalid email then reached normal email validation without creating a subscription. |

Home and collection page headings, introductions, end-of-page CTA, SEO title/description and optional share image can now be edited at **Website Control → Public pages** using existing permission controls. About, Contact, privacy, catalog entries and portfolios continue to use their existing editors. Body HTML is sanitized before public rendering.

## Product snippet follow-up — local fix

Search Console reported that Product markup lacked `offers`, `review` or `aggregateRating`. The product template previously emitted Product even for quote-based entries. The local fix now emits ordinary WebPage markup when a product has no valid published price/currency; priced products retain Product plus Offer. A shared pricing check controls both the visible price and the Offer, including genuine zero prices and rejecting blank or non-finite values. No prices, ratings or reviews were invented, and no database content or environment settings were changed for this fix.

The local AI Assistant page returns WebPage and BreadcrumbList JSON-LD. Quote-based pages remain eligible for normal indexing but do not target Product rich results. This follow-up is local only: after deployment, check the affected URL using Google’s [Rich Results Test](https://search.google.com/test/rich-results), then request Search Console validation. Google must recrawl the page before its report changes. See [Product snippet requirements](https://developers.google.com/search/docs/appearance/structured-data/product-snippet).

## Remaining SEO and content work

| Priority | Pages | Recommended action and acceptance |
| --- | --- | --- |
| High | `/blogs/11`, `/blogs/12` | The titles, bodies and code fields are identical. Choose the article that should remain primary after reviewing references and comments, merge any useful discussion, then add a permanent redirect from the duplicate and remove it from listings/sitemaps. This audit preserved both records and their URLs. |
| High | Short articles listed below | Eighteen articles have fewer than 100 words across body/code text. This is a review signal, not a ranking rule. Expand useful notes with accurate, version-specific explanations, working examples, limitations and primary references, or consolidate overlapping material with redirects. Avoid adding filler to reach a word count. |
| High | Laravel/Vue/React/analytics tutorials | Recheck technical statements against the versions being taught. Examples include Vue instance lifecycle language, older Laravel query APIs/cache defaults, overlapping Eloquent introductions (`/blogs/13`, `/blogs/34`), and GA4 tracking. Title corrections do not certify these bodies as current. |
| Medium | 19 published portfolios without an active introduction | Ask owners to add truthful introductions, designations and work evidence through their existing editors. Publication status is preserved; names alone do not establish duplicate identities. |
| Medium | `/work/1`, `/work/2`, `/work/3`, `/work/9`, `/work/10` and other brief projects | Add verified problem, individual contribution, implementation decisions and outcomes. Avoid implying that a contributor’s personal project was delivered by iTechOrbit as a business engagement. Current detail pages remain descriptions of published contributions. |
| Medium | `/blog` | Laravel defines a permanent redirect, but the local Nginx static `/public/blog` directory takes precedence and the alias returns 404. Add exact-location redirects in the infrastructure Nginx configuration (example below); keep `/blog/img/...` assets available. This hosting change is outside the application release and remains pending. |
| Medium | Social previews | Set a suitable absolute share-image URL for collection pages through Public pages. Record pages use their existing published image when available. Do not add dummy images or fake offers solely to satisfy rich-result requirements. |
| Medium | Business claims and privacy operations | Verify real addresses, availability, client permissions, support expectations, provider processing and any consent-dependent analytics behavior before making stronger claims. Preserve only claims supported by actual operations; this audit is not a legal-policy certification. |
| Medium | Production indexing/performance | Run the production checks below after deployment. No production Search Console or field performance measurements were available here. |

Short article URLs: `/blogs/2`, `/blogs/3`, `/blogs/4`, `/blogs/7`, `/blogs/9`, `/blogs/11`, `/blogs/12`, `/blogs/13`, `/blogs/16`, `/blogs/17`, `/blogs/18`, `/blogs/22`, `/blogs/23`, `/blogs/24`, `/blogs/33`, `/blogs/34`, `/blogs/41`, `/blogs/48`.

Suggested infrastructure rule, inside the correct Nginx server block:

```nginx
location = /blog  { return 301 /blogs$is_args$args; }
location = /blog/ { return 301 /blogs$is_args$args; }
```

Verify both aliases return 301 to `/blogs`, and existing `/blog/img/...` URLs still work before reloading production hosting.

## Ranking development priorities

1. **Match search intent to actual services.** Prioritize Laravel application development, Laravel modernization, REST API integration, custom web applications and database/application performance. Keep one focused service page per genuine offering. Use the page title, main heading, introduction and internal links to clearly identify the problem solved; do not stuff repeated keywords into unrelated copy.
2. **Build evidence around services.** Publish useful technical explanations linked to relevant services and credible case studies. Include the original problem, constraints, contribution and measured result only when evidence exists. Personal portfolios should remain distinct from company delivery claims.
3. **Improve the existing article library.** Resolve the exact duplicate first, then review short/outdated notes and overlapping Eloquent material. Explain version constraints and link to primary technical documentation. Preserve useful historical tutorials with clear version context rather than presenting every example as current.
4. **Measure production outcomes.** Use Search Console to inspect representative home/service/product/article/category/portfolio URLs, submit the sitemap index, and track impressions, clicks, queries, indexed pages and inquiry conversions. Review performance over meaningful periods instead of treating a deployment as evidence of ranking improvement.
5. **Validate production delivery.** Confirm canonical HTTPS/www redirects, SSR availability, robots/sitemap accessibility and correct index/noindex behavior. Run mobile PageSpeed/Lighthouse and compare field LCP, INP and CLS where sufficient traffic exists. Existing slow-network browser checks are not a substitute for production field data.

Guidance: [Google SEO Starter Guide](https://developers.google.com/search/docs/fundamentals/seo-starter-guide), [Search guidance for developers](https://developers.google.com/search/docs/fundamentals/get-started-developers), [helpful, reliable content](https://developers.google.com/search/docs/fundamentals/creating-helpful-content). These changes improve crawlability and content clarity; no first-place ranking is promised.

## CAPTCHA configuration and deployment

The local `.env` uses `RECAPTCHA_ENABLED=true`, `RECAPTCHA_VERSION=v3`, a minimum score of `0.5`, and the exact allowed hostname `itechorbit.local`. Both keys remain in `.env`; the secret is not shared with React. Google accepted a real test token with the expected hostname/action. The follow-up invalid-email request returned only an email validation error, proving it passed CAPTCHA without creating business data.

Production must supply keys registered for its domains and set `RECAPTCHA_ALLOWED_HOSTNAMES` accordingly. Key registration and hostname checks are both required. v3 rejects incorrect actions, scores below the configured threshold, missing scores, wrong hostnames, invalid/expired tokens and verification outages. v2 validates the checkbox token and hostname. Enabled but incomplete configuration fails closed. Explicitly disabling protection is an operational setting, not an automatic outage fallback.

Protected operations: business/portfolio inquiries, legacy `/contacts`, newsletters, login, registration, reset-link requests and password-reset completion. Search, OAuth callbacks, signed-in comments and private account/admin forms are excluded. Existing CSRF, throttles and honeypots remain. Verification tokens are not stored in the business records or flashed as old input. Integration follows [Google’s v3 guidance](https://developers.google.com/recaptcha/docs/v3) and [server verification contract](https://developers.google.com/recaptcha/docs/verify). Google scripts load through the shared client loader on protected form pages, with scoped CSP permissions and privacy/terms links.

Apply the release through the normal deployment process, including migrations, frontend/SSR build and SSR refresh. Then review and run `website:correct-public-content --dry-run` and `--apply` on the intended database. The command is repeatable and skips later editorial changes; backups live under `storage/app/private/public-content-backups/`. No demonstration seeding is needed. See [README](../README.md#public-pages-seo-and-spam-protection) for environment variables and commands.

Verification covers PHP server rules, content updates/permissions, TypeScript/lint/frontend tests, production client/SSR builds, desktop/mobile CAPTCHA and contact/editor flows, SSR metadata and the complete local crawl. The full PHP suite passed 220 tests; 14 focused desktop/mobile CAPTCHA, contact and public-page-editor checks passed. TypeScript, lint, frontend tests and production builds passed. A broader browser run also exposed a separate desktop CKEditor link-insertion timeout: its Insert button stays outside the viewport; an isolated rerun reproduced it. No CKEditor application code was changed by this work. Remaining production-only checks include real-user v3 score behavior, Google rich-result validation, Search Console indexing and production performance. The default score may need review using operational evidence; rejected tokens do not trigger a bypass.

## URL inventory

The following inventory records every fetched page/variant from the final local crawl. “Index” means the page permits indexing; it is not evidence that Google has indexed it. HTTP status is the final response after following redirects. The `/blog` hosting issue and deliberately unknown URL are listed explicitly.

| URL | HTTP | Index policy | Content source / editorial follow-up |
| --- | --- | --- | --- |
| `/` | 200 | Index | website_pages + published catalog/projects/articles |
| `/about` | 200 | Index | website_about |
| `/about/info` | 200 | Index | website_about |
| `/blog` | 404 | Error | hosting redirect pending; static directory shadows Laravel route |
| `/blogs` | 200 | Index | website_pages + public posts |
| `/blogs/1` | 200 | Index | posts |
| `/blogs/10` | 200 | Index | posts |
| `/blogs/11` | 200 | Index | posts; exact duplicate requires consolidation; short note: review depth/version context |
| `/blogs/12` | 200 | Index | posts; exact duplicate requires consolidation; short note: review depth/version context |
| `/blogs/13` | 200 | Index | posts; short note: review depth/version context |
| `/blogs/14` | 200 | Index | posts |
| `/blogs/15` | 200 | Index | posts |
| `/blogs/16` | 200 | Index | posts; short note: review depth/version context |
| `/blogs/17` | 200 | Index | posts; short note: review depth/version context |
| `/blogs/18` | 200 | Index | posts; short note: review depth/version context |
| `/blogs/19` | 200 | Index | posts |
| `/blogs/2` | 200 | Index | posts; short note: review depth/version context |
| `/blogs/20` | 200 | Index | posts |
| `/blogs/21` | 200 | Index | posts |
| `/blogs/22` | 200 | Index | posts; short note: review depth/version context |
| `/blogs/23` | 200 | Index | posts; short note: review depth/version context |
| `/blogs/24` | 200 | Index | posts; short note: review depth/version context |
| `/blogs/25` | 200 | Index | posts |
| `/blogs/26` | 200 | Index | posts |
| `/blogs/27` | 200 | Index | posts |
| `/blogs/28` | 200 | Index | posts |
| `/blogs/29` | 200 | Index | posts |
| `/blogs/3` | 200 | Index | posts; short note: review depth/version context |
| `/blogs/30` | 200 | Index | posts |
| `/blogs/31` | 200 | Index | posts |
| `/blogs/32` | 200 | Index | posts |
| `/blogs/33` | 200 | Index | posts; short note: review depth/version context |
| `/blogs/34` | 200 | Index | posts; short note: review depth/version context |
| `/blogs/35` | 200 | Index | posts |
| `/blogs/36` | 200 | Index | posts |
| `/blogs/37` | 200 | Index | posts |
| `/blogs/38` | 200 | Index | posts |
| `/blogs/39` | 200 | Index | posts |
| `/blogs/4` | 200 | Index | posts; short note: review depth/version context |
| `/blogs/40` | 200 | Index | posts |
| `/blogs/41` | 200 | Index | posts; short note: review depth/version context |
| `/blogs/42` | 200 | Index | posts |
| `/blogs/43` | 200 | Index | posts |
| `/blogs/44` | 200 | Index | posts |
| `/blogs/45` | 200 | Index | posts |
| `/blogs/46` | 200 | Index | posts |
| `/blogs/47` | 200 | Index | posts |
| `/blogs/48` | 200 | Index | posts; short note: review depth/version context |
| `/blogs/49` | 200 | Index | posts |
| `/blogs/5` | 200 | Index | posts |
| `/blogs/6` | 200 | Index | posts |
| `/blogs/7` | 200 | Index | posts; short note: review depth/version context |
| `/blogs/8` | 200 | Index | posts |
| `/blogs/9` | 200 | Index | posts; short note: review depth/version context |
| `/blogs/category/1` | 200 | Index | categories + public posts |
| `/blogs/category/10` | 200 | Index | categories + public posts |
| `/blogs/category/11` | 200 | Index | categories + public posts |
| `/blogs/category/12` | 200 | Index | categories + public posts |
| `/blogs/category/13` | 200 | Index | categories + public posts |
| `/blogs/category/2` | 200 | Index | categories + public posts |
| `/blogs/category/22` | 200 | Index | categories + public posts |
| `/blogs/category/24` | 200 | Index | categories + public posts |
| `/blogs/category/25` | 200 | Index | categories + public posts |
| `/blogs/category/3` | 200 | Index | categories + public posts |
| `/blogs/category/32` | 200 | Index | categories + public posts |
| `/blogs/category/33` | 200 | Index | categories + public posts |
| `/blogs/category/34` | 200 | Index | categories + public posts |
| `/blogs/category/4` | 200 | Index | categories + public posts |
| `/blogs/category/5` | 200 | Index | categories + public posts |
| `/blogs/category/6` | 200 | Index | categories + public posts |
| `/blogs/category/8` | 200 | Index | categories + public posts |
| `/blogs/search` | 200 | Noindex | website_pages + public posts |
| `/blogs?key=audit` | 200 | Noindex | website_pages + public posts |
| `/blogs?tag=Devops` | 200 | Noindex | website_pages + public posts |
| `/blogs?tag=General` | 200 | Noindex | website_pages + public posts |
| `/blogs?tag=Relational%20Database` | 200 | Noindex | website_pages + public posts |
| `/blogs?tag=Server` | 200 | Noindex | website_pages + public posts |
| `/blogs?tag=Web%20application%20development` | 200 | Noindex | website_pages + public posts |
| `/blogs?tag=Web%20design` | 200 | Noindex | website_pages + public posts |
| `/contact` | 200 | Index | website_contact + protected inquiry form |
| `/contact/info` | 200 | Index | website_contact + protected inquiry form |
| `/contact/info?subject=AI%20integration%20and%20workflow%20automation` | 200 | Index | shared public route |
| `/contact/info?subject=Application%20performance%20optimization` | 200 | Index | shared public route |
| `/contact/info?subject=Custom%20web%20application%20development` | 200 | Index | shared public route |
| `/contact/info?subject=Database%20optimization` | 200 | Index | shared public route |
| `/contact/info?subject=Engineering%20opportunity` | 200 | Index | shared public route |
| `/contact/info?subject=Enterprise%20software%20and%20ERP%20development` | 200 | Index | shared public route |
| `/contact/info?subject=Laravel%20application%20development` | 200 | Index | shared public route |
| `/contact/info?subject=Laravel%20modernization%20and%20upgrades` | 200 | Index | shared public route |
| `/contact/info?subject=Maintenance%20and%20technical%20support` | 200 | Index | shared public route |
| `/contact/info?subject=REST%20API%20development%20and%20integration` | 200 | Index | shared public route |
| `/contact/info?subject=iTechOrbit%20AI%20Assistant` | 200 | Index | shared public route |
| `/login` | 200 | Noindex | authentication; protected guest submissions |
| `/not-a-real-page` | 404 | Noindex | intentional missing-page control |
| `/password/reset` | 200 | Noindex | authentication; protected guest submissions |
| `/portfolios` | 200 | Index | users + active portfolio data |
| `/portfolios/1C0FF1FADB269961` | 200 | Index | users + active portfolio data |
| `/portfolios/1C0FF1FADB269961/contact` | 200 | Noindex | recipient profile + protected inquiry form |
| `/portfolios/1C0FF21172A62922` | 200 | Index | users + active portfolio data |
| `/portfolios/1C0FF21172A62922/contact` | 200 | Noindex | recipient profile + protected inquiry form |
| `/portfolios/1C0FF2409C39BB63` | 200 | Index | users + active portfolio data |
| `/portfolios/1C0FF2409C39BB63/contact` | 200 | Noindex | recipient profile + protected inquiry form |
| `/portfolios/1C0FF240A658ACA4` | 200 | Index | users + active portfolio data |
| `/portfolios/1C0FF240A658ACA4/contact` | 200 | Noindex | recipient profile + protected inquiry form |
| `/portfolios/1C0FF255763D1AA5` | 200 | Index | users + active portfolio data |
| `/portfolios/1C0FF255763D1AA5/contact` | 200 | Noindex | recipient profile + protected inquiry form |
| `/portfolios/1C0FF2E333E53786` | 200 | Index | users + active portfolio data |
| `/portfolios/1C0FF2E333E53786/contact` | 200 | Noindex | recipient profile + protected inquiry form |
| `/portfolios/1C0FF2FA401C8A08` | 200 | Index | users + active portfolio data |
| `/portfolios/1C0FF2FA401C8A08/contact` | 200 | Noindex | recipient profile + protected inquiry form |
| `/portfolios/1C0FF2FA4034F407` | 200 | Index | users + active portfolio data |
| `/portfolios/1C0FF2FA4034F407/contact` | 200 | Noindex | recipient profile + protected inquiry form |
| `/portfolios/1C0FF2FAA55E2B89` | 200 | Index | users + active portfolio data |
| `/portfolios/1C0FF2FAA55E2B89/contact` | 200 | Noindex | recipient profile + protected inquiry form |
| `/portfolios/1C0FF3298C1C0FAA` | 200 | Index | users + active portfolio data |
| `/portfolios/1C0FF3298C1C0FAA/contact` | 200 | Noindex | recipient profile + protected inquiry form |
| `/portfolios/1C0FF3CD2F8172EB` | 200 | Index | users + active portfolio data |
| `/portfolios/1C0FF3CD2F8172EB/contact` | 200 | Noindex | recipient profile + protected inquiry form |
| `/portfolios/1C0FF3CD2F8C214C` | 200 | Index | users + active portfolio data |
| `/portfolios/1C0FF3CD2F8C214C/contact` | 200 | Noindex | recipient profile + protected inquiry form |
| `/portfolios/1C0FF3E3574F90CD` | 200 | Index | users + active portfolio data |
| `/portfolios/1C0FF3E3574F90CD/contact` | 200 | Noindex | recipient profile + protected inquiry form |
| `/portfolios/1C0FFA9E7EC8AEEE` | 200 | Index | users + active portfolio data |
| `/portfolios/1C0FFA9E7EC8AEEE/contact` | 200 | Noindex | recipient profile + protected inquiry form |
| `/portfolios/1C13372A901C1BCF` | 200 | Index | users + active portfolio data |
| `/portfolios/1C13372A901C1BCF/contact` | 200 | Noindex | recipient profile + protected inquiry form |
| `/portfolios/1C1337CE227888D0` | 200 | Index | users + active portfolio data |
| `/portfolios/1C1337CE227888D0/contact` | 200 | Noindex | recipient profile + protected inquiry form |
| `/portfolios/1C1337CE23C21FD1` | 200 | Index | users + active portfolio data |
| `/portfolios/1C1337CE23C21FD1/contact` | 200 | Noindex | recipient profile + protected inquiry form |
| `/portfolios/1C133812AF6CE212` | 200 | Index | users + active portfolio data |
| `/portfolios/1C133812AF6CE212/contact` | 200 | Noindex | recipient profile + protected inquiry form |
| `/portfolios/1C1338131108AD13` | 200 | Index | users + active portfolio data |
| `/portfolios/1C1338131108AD13/contact` | 200 | Noindex | recipient profile + protected inquiry form |
| `/portfolios/1C13387076742374` | 200 | Index | users + active portfolio data |
| `/portfolios/1C13387076742374/contact` | 200 | Noindex | recipient profile + protected inquiry form |
| `/portfolios/1C13522E0438F6F5` | 200 | Index | users + active portfolio data |
| `/portfolios/1C13522E0438F6F5/contact` | 200 | Noindex | recipient profile + protected inquiry form |
| `/portfolios/1C135ABA793AD3D6` | 200 | Index | users + active portfolio data |
| `/portfolios/1C135ABA793AD3D6/contact` | 200 | Noindex | recipient profile + protected inquiry form |
| `/portfolios/1C13778F6062C8D8` | 200 | Index | users + active portfolio data |
| `/portfolios/1C13778F6062C8D8/contact` | 200 | Noindex | recipient profile + protected inquiry form |
| `/portfolios/1C16C54D58BAAFD9` | 200 | Index | users + active portfolio data |
| `/portfolios/1C16C54D58BAAFD9/contact` | 200 | Noindex | recipient profile + protected inquiry form |
| `/portfolios/1C16E0AD760F60FB` | 200 | Index | users + active portfolio data |
| `/portfolios/1C16E0AD760F60FB/contact` | 200 | Noindex | recipient profile + protected inquiry form |
| `/portfolios/1C16E0AD76157B7A` | 200 | Index | users + active portfolio data |
| `/portfolios/1C16E0AD76157B7A/contact` | 200 | Noindex | recipient profile + protected inquiry form |
| `/portfolios/1C1A9218E439737C` | 200 | Index | users + active portfolio data |
| `/portfolios/1C1A9218E439737C/contact` | 200 | Noindex | recipient profile + protected inquiry form |
| `/portfolios/1C1DD6A77550BFFE` | 200 | Index | users + active portfolio data |
| `/portfolios/1C1DD6A77550BFFE/contact` | 200 | Noindex | recipient profile + protected inquiry form |
| `/portfolios/1C1E0593F2A2F89F` | 200 | Index | users + active portfolio data |
| `/portfolios/1C1E0593F2A2F89F/contact` | 200 | Noindex | recipient profile + protected inquiry form |
| `/portfolios/1C1E0593F2A78C7D` | 200 | Index | users + active portfolio data |
| `/portfolios/1C1E0593F2A78C7D/contact` | 200 | Noindex | recipient profile + protected inquiry form |
| `/portfolios/1C1E0CF04B1A48E0` | 200 | Index | users + active portfolio data |
| `/portfolios/1C1E0CF04B1A48E0/contact` | 200 | Noindex | recipient profile + protected inquiry form |
| `/privacy-n-policy` | 200 | Index | privacy_policies |
| `/products` | 200 | Index | website_pages + website_products |
| `/products/itechorbit-ai-assistant` | 200 | Index | website_pages + website_products |
| `/register` | 200 | Noindex | authentication; protected guest submissions |
| `/service/info` | 200 | Index | website_pages + published services/technologies |
| `/service/info?technology=ai` | 200 | Noindex | website_pages + published services/technologies |
| `/service/info?technology=cicd` | 200 | Noindex | website_pages + published services/technologies |
| `/service/info?technology=inertia` | 200 | Noindex | website_pages + published services/technologies |
| `/service/info?technology=laravel` | 200 | Noindex | website_pages + published services/technologies |
| `/service/info?technology=performance` | 200 | Noindex | website_pages + published services/technologies |
| `/service/info?technology=php` | 200 | Noindex | website_pages + published services/technologies |
| `/service/info?technology=queues` | 200 | Noindex | website_pages + published services/technologies |
| `/service/info?technology=react` | 200 | Noindex | website_pages + published services/technologies |
| `/service/info?technology=redis` | 200 | Noindex | website_pages + published services/technologies |
| `/service/info?technology=rest-apis` | 200 | Noindex | website_pages + published services/technologies |
| `/service/info?technology=sql` | 200 | Noindex | website_pages + published services/technologies |
| `/service/info?technology=testing` | 200 | Noindex | website_pages + published services/technologies |
| `/service/info?technology=typescript` | 200 | Noindex | website_pages + published services/technologies |
| `/services/ai-workflow-integration` | 200 | Index | website_services; legacy product paths redirect |
| `/services/api-development` | 200 | Index | website_services; legacy product paths redirect |
| `/services/custom-web-applications` | 200 | Index | website_services; legacy product paths redirect |
| `/services/database-optimization` | 200 | Index | website_services; legacy product paths redirect |
| `/services/enterprise-software` | 200 | Index | website_services; legacy product paths redirect |
| `/services/itechorbit-ai-assistant` | 200 | Index | website_services; legacy product paths redirect |
| `/services/laravel-development` | 200 | Index | website_services; legacy product paths redirect |
| `/services/laravel-modernization` | 200 | Index | website_services; legacy product paths redirect |
| `/services/maintenance-support` | 200 | Index | website_services; legacy product paths redirect |
| `/services/performance-optimization` | 200 | Index | website_services; legacy product paths redirect |
| `/work` | 200 | Index | website_pages + published projects |
| `/work/1` | 200 | Index | projects + owner publication status; verify case-study evidence |
| `/work/10` | 200 | Index | projects + owner publication status; verify case-study evidence |
| `/work/2` | 200 | Index | projects + owner publication status; verify case-study evidence |
| `/work/3` | 200 | Index | projects + owner publication status; verify case-study evidence |
| `/work/4` | 200 | Index | projects + owner publication status; verify case-study evidence |
| `/work/5` | 200 | Index | projects + owner publication status; verify case-study evidence |
| `/work/6` | 200 | Index | projects + owner publication status; verify case-study evidence |
| `/work/7` | 200 | Index | projects + owner publication status; verify case-study evidence |
| `/work/8` | 200 | Index | projects + owner publication status; verify case-study evidence |
| `/work/9` | 200 | Index | projects + owner publication status; verify case-study evidence |
| `/work?category=Blog%20site%20with%20dynamic%20portfolio` | 200 | Noindex | website_pages + published projects |
| `/work?category=Commercial` | 200 | Noindex | website_pages + published projects |
| `/work?category=E%20commerce` | 200 | Noindex | website_pages + published projects |
| `/work?category=ERP` | 200 | Noindex | website_pages + published projects |
| `/work?category=Information%20Management%20System` | 200 | Noindex | website_pages + published projects |
| `/work?category=audit` | 200 | Noindex | website_pages + published projects |
