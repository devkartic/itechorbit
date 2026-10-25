# Loading performance

Use production assets and a running SSR service when measuring this application. `npm run build` followed by `npm run serve` starts the local preview at `http://127.0.0.1:8000`; Ctrl+C stops its HTTP and SSR processes. Vite development timings are not production performance measurements.

For the local PHP Docker workspace, run `npm run ssr:docker` and set `INERTIA_SSR_URL=http://itechorbit-ssr:13714` in the local application environment. The Node container connects to the existing `php-workspace_app_network` (override with `ITECHORBIT_DOCKER_NETWORK`), mounts the project read-only and exposes no host port. The command recreates the SSR process and waits for health, so run it again after rebuilding frontend assets. Use `npm run ssr:docker:stop` to stop it. Native PHP previews use `INERTIA_SSR_URL=http://127.0.0.1:13714` instead. Production continues to default to loopback; `INERTIA_SSR_HOST` is explicitly set only for the isolated Docker network.

The initial HTML includes public page styles so portfolios render correctly before hydration. Styles take priority over hydration scripts, the heading font is preloaded, and optional fonts preserve system text on slow connections rather than swapping after paint. The assistant starts after document loading during an idle period. Service/product listings omit detail-only content; detail pages retain sanitized descriptions and intrinsic cover-image dimensions.

Admin page assets retain their existing dynamic loading. Eagerly loading their styles changed the editor popup positioning during browser verification; public page optimization must not be extended to admin entries without checking the editor workflows.

## Repeatable mobile measurements

In separate terminals, after building:

```sh
node tests/browser/server.mjs
npm run test:performance -- /tmp/itechorbit-performance
```

The fixture server creates a disposable SQLite database and uses synthetic content, uploads, and a non-relaying mail sink. Stop it with Ctrl+C when finished. Do not run browser tests at the same time as Lighthouse measurements; they modify fixtures and compete for CPU.

The measurement command uses Lighthouse 13.5.0, Playwright's installed Chromium, default mobile simulated throttling, and three cold-cache runs per URL. It writes the raw Lighthouse reports and a `summary.json` containing medians. The first invocation may download Lighthouse into npm's cache. Set `PERFORMANCE_BASE_URL` to measure the same fixtures through another local web server.

Covered fixtures: homepage, article detail, service detail, products listing, and all five sparse portfolio templates. The products listing is empty in these synthetic fixtures; product detail behavior and image sizing are verified by PHP/browser tests. These measurements do not represent large production images or real visitor INP.

## Web server delivery

PHP's built-in server does not process `.htaccess` and sends static assets uncompressed. Its results help compare application changes but overstate transfer costs relative to a configured production web server.

`public/.htaccess` enables text/HTML compression when `mod_deflate` is available and gives Vite assets a one-year immutable cache policy when the required modules are available. It does not apply that policy to HTML or uploaded media. Verify `Content-Encoding` and `Cache-Control` on the actual host; hosts can disable modules or overrides. The Nginx example includes the shared `deploy/nginx-performance.conf` snippet for gzip and immutable asset caching. The local Docker Nginx site includes it from `/var/www/projects/itechorbit/deploy/nginx-performance.conf`; retain that include if the workspace regenerates its site configuration.

Targets are FCP ≤1.8s, LCP ≤2.5s, CLS ≤0.1, and lab total blocking time ≤200ms. TBT is a diagnostic rather than a substitute for field INP. Local results do not establish a production Core Web Vitals pass: production also needs working SSR, compression, representative media, and field measurements.

## Local verification — 21 October 2026

Three-run medians with the same uncompressed PHP fixture server and Lighthouse mobile configuration:

| Page | FCP before | FCP after |
| --- | ---: | ---: |
| Homepage | 4.67s | 1.80s |
| Article | 4.21s | 1.65s |
| Service | 4.14s | 1.35s |
| Products listing | 3.99s | 1.35s |
| Studio portfolio | 4.44s | 1.95s |
| Executive portfolio | 4.28s | 1.95s |
| Blueprint portfolio | 4.28s | 1.95s |
| Editorial portfolio | 4.28s | 1.95s |
| Spotlight portfolio | 4.44s | 1.95s |

Separate three-run checks through a local Apache server with compressed HTML/assets produced:

| Page | FCP | LCP | Performance score |
| --- | ---: | ---: | ---: |
| Homepage | 1.20s | 2.85s | 95 |
| Article | 1.21s | 2.85s | 95 |
| Service | 1.05s | 2.63s | 97 |
| Products listing | 1.06s | 2.63s | 97 |
| Studio portfolio | 1.20s | 2.70s | 96 |

All compressed checks had zero CLS and median TBT below 15ms. Apache confirmed gzip delivery of the approximately 74KB stylesheet as approximately 14KB and immutable caching of hashed assets. These results were collected before expanding the service catalogue. They establish the effect of the loading changes, rather than a production score or a benchmark of the final catalogue's content.

The LCP target is not fully met: compressed simulated LCP remains 2.63–2.85s, while the uncompressed PHP preview reports 4.58–5.33s. Further work should use representative media, server timings and real-user measurements. Keep this limitation visible when assessing the results.
