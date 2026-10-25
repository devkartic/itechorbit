# Services and configuration

Example placeholders: IP addresses, private email addresses, SSH users, host paths and infrastructure names below are illustrative. Replace them with verified values from your private deployment configuration before running commands. These examples do not change production settings.

[Deployment](../DEPLOYMENT.md) · [Troubleshooting](TROUBLESHOOTING.md) · [Recovery](BACKUP-RECOVERY.md)

## Verified production inventory

Snapshot: **26 October 2026, Asia/Dhaka**. Names are operational identifiers, not instructions to recreate infrastructure from scratch.

| Container | Purpose | Exposure |
| --- | --- | --- |
| `example-production-gateway-caddy-1` | HTTPS, canonical redirects, certificate renewal | Public 80/443 |
| `example-production-app-nginx-1` | Static files and PHP routing | Private backend/edge networks |
| `example-production-app-php-1` | PHP 8.5 / Laravel / Composer | Private backend |
| `example-local-ssr-1` | Node 24 / Inertia SSR; historical name retained | Private backend, port 13714 |
| `example-production-app-worker-1` | Default database queue consumer | Private backend |
| `example-production-app-worker-notices-1` | Dedicated `mail_notices` / `mail-notices` consumer | Private backend |
| `example-production-app-worker-ai-1` | Dedicated `ai_pro` / `ai-pro` synchronization consumer | Private backend |
| `example-production-app-mariadb-1` | Application database | Private backend |
| `example-production-app-redis-1` | Redis | Private backend |
| `example-production-app-smtp-1` | Website outgoing mail | Private backend, submission 587 |
| `example-forwarding-inbound-1` | Support email forwarding | Public IPv4 port 25 only |
| `example-production-app-access-db-proxy-1` | Temporary/admin DB access | Loopback 13306 |
| `example-production-app-access-adminer-1` | Adminer | Loopback 18080 |

Application network: `example-production-app_backend`. Nginx also joins `example-production-app_edge`, which Caddy joins for web routing. Inbound mail uses independent `example-forwarding_default`. Do not rename these networks during an incident.

Canonical URL: `https://www.itechorbit.com`; `https://itechorbit.com` redirects there. Mail hostname: `mail.itechorbit.com`. VPS IPv4: `192.0.2.10`. Loopback DB/Adminer ports are for trusted SSH access, not public firewall openings.

## Daily health check

**VPS / example-user; any directory; read-only:**

```sh
docker ps --format 'table {{.Names}}\t{{.Status}}'
curl --fail --silent --show-error https://www.itechorbit.com/healthz
docker exec --user www-data example-production-app-php-1 php artisan inertia:check-ssr
docker logs --since 10m --tail 80 example-production-app-worker-1
docker exec example-forwarding-inbound-1 postqueue -p
docker exec example-production-app-smtp-1 postqueue -p
```

Expected: containers running, health checks healthy where defined, HTTP success, SSR reachable from PHP, no recurring worker failures. Queued mail may be legitimate; investigate its status rather than deleting it. Worker running state alone does not prove that every queue has a consumer; see [queue inventory](SCHEDULER.md#queue-connections-and-workers).

A fuller service snapshot:

```sh
docker inspect --format '{{.Name}} {{.State.Status}} {{if .State.Health}}{{.State.Health.Status}}{{end}}' \
  example-production-app-php-1 \
  example-production-app-nginx-1 \
  example-local-ssr-1 \
  example-production-app-smtp-1 \
  example-forwarding-inbound-1
```

## Find logs and mounted data

| Data | Location / inspection |
| --- | --- |
| Laravel errors | Inside PHP: `/var/www/html/storage/logs/laravel.log` |
| PHP error log | Host `/srv/example-project/logs/php-error.log` |
| Nginx, SSR, worker, SMTP logs | `docker logs --since 10m --tail 100 CONTAINER_NAME` (replace the identifier) |
| Scheduler | Host `src.deploy/scheduler.log`; [cron operations](SCHEDULER.md) |
| MariaDB | Volume `example-production-app_database` |
| Laravel uploads/runtime | Volume `example-production-app_runtime_storage` |
| Bootstrap cache | Volume `example-production-app_runtime_bootstrap_cache` |
| Redis | Volume `example-production-app_redis_data` |
| SMTP spool / signing keys | Volumes ending `_smtp_queue` / `_smtp_dkim` |
| Inbound forwarding state | Parent `.docker/forwarding/`; [mail operations](MAIL.md) |

**VPS / example-user; any directory:**

```sh
docker exec example-production-app-php-1 tail -n 80 storage/logs/laravel.log
docker inspect --format '{{range .Mounts}}{{println .Type .Name .Source "->" .Destination "writable=" .RW}}{{end}}' example-production-app-php-1
```

Logs and full Compose/inspect output may include personal data or secrets. Share relevant redacted lines; do not publish `.env`, full container environments or private signing keys.

## Change Laravel settings

**VPS / root, from `/srv/example-project`:** back up `.env` privately, edit only intended values, then rebuild configuration.

```sh
cd /srv/example-project
umask 077
backup_dir="/root/example-project-env-backup-$(date -u +%Y%m%dT%H%M%SZ)"
mkdir "$backup_dir"
cp src/.env "$backup_dir/application.env"
nano src/.env
docker exec --user www-data example-production-app-php-1 php artisan config:cache
docker exec --user www-data example-production-app-php-1 php artisan queue:restart
```

Expected: config cache succeeds; long-running Laravel workers exit/restart gracefully. Inspect worker logs afterward. Add no duplicate dotenv keys; workspace validation rejects them. Preserve `APP_KEY` and existing database/Redis passwords. Changing an environment password does not rotate the database account itself. Frontend changes need a release build; changing server environment values usually does not.

Relevant non-secret settings for this installation:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://www.itechorbit.com
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database
INERTIA_SSR_ENABLED=true
INERTIA_SSR_URL=http://example-ssr:13714
INERTIA_SSR_THROW_ON_ERROR=true
```

Do not paste this over the existing environment. `app.timezone` is currently UTC. The SSR container additionally needs `INERTIA_SSR_HOST=0.0.0.0`; the PHP container cannot reach its own loopback as the SSR service.

If edited values fail validation, restore the saved application environment, rebuild config cache and signal workers again. Laravel runtime config inspection should print only the keys being diagnosed.

## Restart versus rebuild versus recreate

| Change | Required action |
| --- | --- |
| Application PHP/React/CSS | Deploy tracked source and fresh assets using the release script |
| PHP/Nginx Dockerfile or image-baked config | Build the affected image, then recreate its service |
| Compose environment, mounts, ports or command | Validate Compose, then recreate only the affected service |
| Already-built image still tagged the same way | Recreate; restarting the old container retains its original image ID |
| Nginx rendered config edited in a running container | Validate then reload; also update the parent template/image for persistence |
| Stuck process with unchanged configuration | Restart only its container, then verify readiness |

**VPS / example-user; any directory; causes a brief service interruption:**

```sh
docker restart example-local-ssr-1
docker exec --user www-data example-production-app-php-1 php artisan inertia:check-ssr
```

Do not restart MariaDB/Redis/Caddy/mail to solve an unrelated application problem. Do not remove persistent volumes or run broad image pruning while recovering a missing runtime image.

## Target one application Compose service

Private Compose settings are root-readable. Do not make them world-readable to work around a deploy-user permission error. The workspace generates required mounts and interpolation values; `docker compose -f .docker/compose.yml up` alone is incomplete.

**VPS / root; Bash; start in `/srv/example-workspace`:** validate the existing stack, then define this shell helper for the examples in this section. It uses the current workspace's Python API to supply the same configuration as the Make commands; inspect it again if the workspace implementation changes.

```bash
cd /srv/example-workspace
make validate ENV=production NAME=example-project
project_compose() {
  WORKSPACE_ENV=production python3 - "$@" <<'PY'
import sys
sys.path.insert(0, '/srv/example-workspace/scripts')
import stacks
project = stacks.project('example-project')
result = stacks.compose(project, *sys.argv[1:], profiles=False, check=False)
raise SystemExit(result.returncode)
PY
}
```

**Nginx template/image change:** back up the parent template first. The image uses `.docker/nginx.conf.template`; changing `deploy/nginx.conf.example` does not change Docker Nginx.

```sh
project_compose build nginx
project_compose run --rm --no-deps nginx nginx -t
project_compose up -d --no-deps --no-build --pull never --wait --wait-timeout 60 nginx
docker exec example-production-app-nginx-1 nginx -t
curl --fail --silent --show-error https://www.itechorbit.com/healthz
```

The one-off validation uses generated source/storage mounts and the existing PHP backend. If it fails, do not recreate the serving Nginx. After activation, check themed errors and existing media. Reverting the saved template requires rebuilding and reconciling again; a failed candidate build leaves the old serving container intact.

**PHP image change:** compare PHP version, extensions, `mounted-production` runtime label, environment, networks and volume names before recreation.

```sh
project_compose build php
project_compose run --rm --no-deps php php --version
project_compose run --rm --no-deps php composer check-platform-reqs --no-dev
project_compose up -d --no-deps --no-build --pull never --wait --wait-timeout 60 php
```

Expected: PHP healthy, unchanged source/storage/cache mounts, site and PHP-to-SSR check succeed. Workers sharing the PHP image need their own deliberate recreation after compatible image changes. Retain a known-good image tag before replacing an image; build success is not evidence that all workers have switched images.

## SSR service lifecycle

The production container's historical name includes `local`; that does not make it a local-development service. It mounts the VPS source at `/app` and joins the production backend network.

Ordinary application deployment rebuilds the SSR bundle and restarts its process. For Compose-based SSR recreation, first locate/restore the parent `.docker/compose.ssr.yml`: the inspected VPS did not contain this file in the last inventory. Source-only deployment will not upload it. Verify the source mount and exact image/network before recreating an existing SSR container.

**VPS / root, after the parent SSR file has been installed and reviewed:**

```sh
cd /srv/example-project/src
ITECHORBIT_DOCKER_NETWORK=example-production-app_backend \
  docker compose -f ../.docker/compose.ssr.yml up -d --force-recreate --wait --wait-timeout 30
docker exec --user www-data example-production-app-php-1 php artisan inertia:check-ssr
```

This command requires Docker Compose, with no host Node/npm dependency. The optional `npm run ssr:docker` wrapper requires host npm. Do not run either against a missing/unreviewed file. Use `docker start example-local-ssr-1` to recover an existing stopped container without changing its definition. After any start/restart, allow the process to initialize and require PHP's `inertia:check-ssr` to succeed before treating SSR as ready. If it fails, inspect SSR logs and retry the readiness check; do not repeatedly recreate the container to wait for startup.

## Gateway and certificate operations

Caddy's active files are generated under `/srv/example-workspace/docker/stacks/production/gateway/caddy/`. The durable website hostname/aliases belong in the parent project `.env`; generated-file-only edits can be lost on gateway synchronization. The mail alias is recorded there for continued certificate coverage. The current explicit mail site block also pins the Let's Encrypt provisioner; inspect certificate paths after future gateway regeneration.

**VPS / example-user; read-only:**

```sh
docker exec example-production-gateway-caddy-1 caddy validate --config /etc/caddy/Caddyfile --adapter caddyfile
docker logs --since 30m --tail 80 example-production-gateway-caddy-1
curl -I https://www.itechorbit.com
curl -I https://mail.itechorbit.com
```

For reviewed hostname changes, **VPS / root, `/srv/example-workspace`**, back up parent settings and gateway config, then use `make gateway-sync ENV=production`. It reconciles selected projects' routing and reloads Caddy; verify all hosted routes, not just this application. Preserve the mail certificate directory mounted by the forwarder, or update that mount if the provisioner changes. See [mail TLS](MAIL.md#certificates-and-forwarding-state).
