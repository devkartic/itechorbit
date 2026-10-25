# Production troubleshooting

Example placeholders: IP addresses, private email addresses, SSH users, host paths and infrastructure names below are illustrative. Replace them with verified values from your private deployment configuration before running commands. These examples do not change production settings.

[Deployment](../DEPLOYMENT.md) · [Services](SERVICES.md) · [Mail](MAIL.md) · [Scheduler](SCHEDULER.md) · [Recovery](BACKUP-RECOVERY.md)

Start with the exact release ID, source commit, time, URL/job and failure message. Read-only commands below run on **VPS / example-user** unless stated otherwise. Read the complete error before restarting services. Keep secrets and message contents out of shared logs.

## Quick decision table

| Symptom | First check | Likely interpretation | Targeted next step |
| --- | --- | --- | --- |
| Workflow skipped | Workflow branch and application branch inputs | Only `main`/`production-version` allowed | Select allowed branches containing the actual fix |
| Successful deploy, old 404/UI | Source component and deployed release ID | Selected branch lacks the change | Deploy the corrected committed branch; rebuilding Nginx won't change React |
| Missing `sha256:...` image | Runtime `.Image`, then `docker image inspect` | Container's old image metadata removed | Reconcile affected service to a reviewed compatible image |
| Build failure before maintenance | Workflow build logs and staging path | Live source normally unchanged | Fix build, use new release ID, redeploy |
| HTTP 503 after failed deployment | SSR logs, Laravel log, maintenance state | Activation stopped while in maintenance | Follow recovery; don't reopen blindly |
| HTTP 502 | PHP/Nginx health, shared backend network | Backend unavailable or invalid runtime config | Repair affected service and verify health |
| 404 becomes 500 / `aiService` undefined | Handler fallback props and SSR logs | Old error handler rendered without shared layout data | Deploy handler + client/SSR build containing permanent fix |
| Missing storage file shows plain Nginx error | Loaded Nginx `error_page` and fallback rules | Old image/template or non-HTML request | Verify `Accept: text/html`, update parent template/image if needed |
| Blank page or old assets | Browser network errors, manifest and `public/hot` | Stale Vite marker or incomplete/mismatched build | Verify Vite stopped, remove stale marker through reviewed deployment and rebuild |
| Laravel cannot write logs/cache | Runtime write access as `www-data` | Wrong ownership or missing writable volume | Repair only affected runtime paths |
| SSR healthy internally, PHP can't connect | PHP `inertia:check-ssr`, SSR host/network | SSR bound to loopback or wrong URL | Use container-wide listener and shared-network alias |
| Mail saved but not sent | Queue failures and matching consumer | Missing consumer/transport failure | Inspect connections; default worker is not notice/AI worker |
| Forwarded mail accepted, absent from inbox | SMTP `status=sent` and Gmail Spam | Recipient-side classification | Inspect original auth headers; apply intentional mailbox filter if wanted |
| Cron appears twice | System/user crontabs and container commands | Host scheduler vs mail-maintenance cron, or real duplicate | Keep one Laravel scheduler; leave unrelated mail cron alone |

## Missing runtime image

```sh
php_image=$(docker inspect --format '{{.Image}}' example-production-app-php-1)
docker image inspect "$php_image" >/dev/null
node_image=$(docker inspect --format '{{.Image}}' example-local-ssr-1)
docker image inspect "$node_image" >/dev/null
```

Expected: exit 0 for both. Containers may keep running after their original image is deleted. A newer `:current` tag is not necessarily the same runtime. Do not silently substitute that tag for build dependencies.

**VPS / root:** inspect the intended replacement's PHP version/extensions/Composer platform checks, runtime label, environment, networks and mounts. Retain the old container/image if available. Recreate **only** the affected service using the existing generated Compose configuration, as described in [Services](SERVICES.md#target-one-application-compose-service). Recheck its exact image ID exists, health, SSR and site, then retry deployment. Avoid broad image pruning or recreating database/storage volumes.

## Diagnose 404 design and SSR errors

```sh
docker exec example-production-app-php-1 grep -n 'Inertia::getShared' app/Exceptions/Handler.php
docker exec example-production-app-php-1 grep -n 'error-page-art' resources/js/pages/Shared/Error.tsx
docker logs --since 5m --tail 80 example-local-ssr-1
docker exec example-production-app-nginx-1 nginx -T 2>&1 | grep -nE 'error_page|laravel_not_found|itechorbit_error_html|fastcgi_intercept_errors'
```

Expected: fallback `site`, `auth`, `flash` props; illustrated error component; loaded HTML-aware error rules. Missing fallback props caused production `aiService` SSR failures; missing component/CSS in an older deployment branch caused the old design. A server-only patch is overwritten by source deployment, so fix the deployable branch permanently.

```sh
curl -sS -H 'Accept: text/html' -o /tmp/itechorbit-404.html -w '%{http_code}\n' https://www.itechorbit.com/storage/not-found.png
grep 'error-page-art' /tmp/itechorbit-404.html
curl -sS -H 'Accept: application/json' -o /tmp/itechorbit-error.json -w '%{http_code}\n' https://www.itechorbit.com/not-a-real-page
```

Expected: both 404; browser navigation has illustrated markup/styles and JSON stays JSON. Missing script/image subrequests should remain failed resources, not themed HTML. Hidden/protected paths should remain 403; do not weaken denial rules to route everything through Laravel. A `curl` request without HTML Accept does not reproduce every browser-navigation error path.

## Diagnose SSR networking

```sh
docker exec --user www-data example-production-app-php-1 php artisan inertia:check-ssr
docker inspect --format '{{json .NetworkSettings.Networks}}' example-local-ssr-1
docker inspect --format '{{json .NetworkSettings.Networks}}' example-production-app-php-1
docker logs --since 10m --tail 100 example-local-ssr-1
```

Expected: both share `example-production-app_backend`; PHP uses `http://example-ssr:13714`. SSR must listen on `0.0.0.0` inside its container. Its own health check can succeed on loopback while PHP cannot reach it. Environment-only fixes require config refresh, while listener-source changes require rebuilding SSR. Existing stopped SSR can be started; recreating it requires the correct reviewed parent Compose file and network.

## Repair runtime permissions narrowly

**VPS / example-user; diagnostic:**

```sh
docker exec --user www-data example-production-app-php-1 php -r '
foreach (["storage/logs", "storage/app/public", "storage/framework/cache/data", "storage/framework/sessions", "storage/framework/views", "bootstrap/cache"] as $path) {
    echo $path, ": ", is_dir($path) && is_writable($path) ? "writable" : "CHECK", PHP_EOL;
}
'
```

If Laravel's existing log was created by another user, **VPS / Docker administrator; repairs only logs:**

```sh
docker exec --user root example-production-app-php-1 sh -ec '
mkdir -p storage/logs
chown www-data:www-data storage/logs
chmod u+rwx storage/logs
if [ -f storage/logs/laravel.log ]; then
    chown www-data:www-data storage/logs/laravel.log
    chmod u+rw storage/logs/laravel.log
fi
'
```

Expected: failed paths become writable to `www-data`; repeat the diagnostic. Investigate mounts if directories are absent/read-only. Do not recursively change source ownership, enable write access to the whole application, or use `chmod 777`.

`public/storage` should be the relative symlink `../storage/app/public`, resolving inside PHP to persistent storage. The source-only deployment creates/verifies it on the host. A conflicting directory or incorrect link must be inspected and preserved before any replacement; do not run container `storage:link` as the host deploy UID to bypass the conflict.

## SSH and deployment access

GitHub-to-VPS login and VPS-to-GitHub pulls use different key relationships. `VPS_SSH_KEY` authenticates the runner to the `example-user` account. A GitHub repository/deploy key authorizes Git operations on that repository. Workspace and application repositories require appropriate access individually.

For a failing pull, inspect `git remote -v`, `whoami` and `ssh -T git@github.com` as the user running Git. A missing SSH agent alone does not mean key-file authentication cannot work. Verify GitHub host fingerprints through official documentation; never disable strict host checking to solve a key rejection.

Root-only workspace `.env`/Compose files are intentionally unavailable to `example-user`. Use a root operator for infrastructure reconciliation; do not expose private settings or change permissions broadly. Source deployment as `example-user` should use its existing Docker access and staging ACLs.

## Queue, mail and scheduler failures

Follow [scheduler diagnostics](SCHEDULER.md) and [mail queue interpretation](MAIL.md#delivery-tests-and-failures). Check the specific queue/SMTP role first. The installed default consumer does not process `mail-notices` or `ai-pro`; those features require their dedicated consumers.

A restart signal does not repair an invalid SMTP host, held transport, missing queue consumer or recipient rejection. Preserve queue IDs and inspect acceptance before retrying. Retrying a job whose previous outcome is unknown can duplicate mail.
