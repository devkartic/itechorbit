# Production deployment

This is the deployment entry point for iTechOrbit on the Contabo Docker VPS. Commands describe the existing mounted-source installation, not a new host installation. The production inventory was checked on **26 October 2026 (Asia/Dhaka)**; recheck it before changing infrastructure.

## Choose a task

| Task | Runbook |
| --- | --- |
| Deploy application code from GitHub or manually | This page |
| Inspect services, change environment settings, rebuild PHP/Nginx, manage SSR/TLS | [Services and configuration](operations/SERVICES.md) |
| Fix deployment, HTTP, SSR, permission, mail or scheduler failures | [Troubleshooting](operations/TROUBLESHOOTING.md) |
| Send website email or forward support email to Gmail | [Mail and DNS](operations/MAIL.md) |
| Inspect cron, queues and missing consumers | [Scheduler and queues](operations/SCHEDULER.md) |
| Back up or recover code, database, uploads and infrastructure | [Backup and recovery](operations/BACKUP-RECOVERY.md) |
| Review migrations, OAuth, content upgrades and media constraints | [Application reference](operations/APPLICATION.md) |

## Command conventions and ownership

Each command block identifies its machine and account. **VPS / deploy** is for Docker inspection and application deployment. **VPS / root** is for private workspace settings, host packages, firewall, crontab administration and infrastructure reconciliation. Docker-group membership gives administrative control of the host. Do not grant it to untrusted users.

Production PHP runs inside Docker. Prefix Artisan commands with `docker exec --user www-data php-workspace-production-itechorbit-php-1 php artisan`. This prevents root-owned runtime files. Host PHP, npm, PHP-FPM and Supervisor are not required for this installation. Workstation builds/tests use local tooling separately.

| Location | Owner/purpose |
| --- | --- |
| `/srv/Workspace` | `workspace-php` infrastructure repository; run `make ... ENV=production NAME=itechorbit` here |
| `/srv/Workspace/projects/itechorbit/.env` | Workspace metadata: source root, hostname, aliases and writable paths |
| `/srv/Workspace/projects/itechorbit/.docker/` | Single project Docker directory; PHP/Nginx, overrides, mail and forwarding config |
| `/srv/Workspace/projects/itechorbit/src` | Application source mount; application repository is `devkartic/itechorbit` |
| `src/.env` | Production Laravel settings and secrets, preserved by deployment |
| `src.deploy/` beside `src/` | Uploaded archives, staged builds, previous-code backups, deployment lock and scheduler log |

`projects/itechorbit/` is ignored by the workspace repository. **Pulling `workspace-php` updates neither application source nor project-local Docker configuration.** The application repository no longer contains `.docker/`. Copy reviewed parent configuration separately, preserving VPS overrides and private `infra.*.env` files. Ordinary application deployment does not rebuild images or change DNS, cron or mail containers.

## Before deployment

1. Choose the exact application branch/commit containing the intended fix. A successful build of an older branch can still restore an older UI or remove a server hotfix. Never rely on edits made only on the VPS.
2. Run repository verification on disposable local/staging data: PHP tests, TypeScript, frontend tests and client/SSR build. Use the full check list in [README](../README.md#verification) for broader changes.
3. Take a database and media backup before schema/data changes; rehearse recovery. Code backups made by the deployment script do **not** contain the database, production `.env` or uploaded storage.
4. Inspect pending migrations. Enable migrations only after reviewing the exact release. Preserve `APP_KEY`; never reset/seed an existing production database.
5. Confirm healthy PHP/Nginx, an existing SSR container (it may be stopped), matching source mounts and available exact runtime images. See [preflight checks](operations/SERVICES.md#daily-health-check).

The live source is read-only inside production containers. Storage/bootstrap-cache volumes remain writable to `www-data`. The deployment user needs host access to the source and sibling staging directory.

## GitHub Actions deployment

Workflow: [deploy-vps.yml](../.github/workflows/deploy-vps.yml). Script: [deploy-vps.sh](../deploy/deploy-vps.sh). The script from **Use workflow from** and source from **Branch to build and deploy** are checked out separately.

1. Open **Actions → Deploy to VPS → Run workflow**.
2. Choose **Use workflow from: main** to use the maintained deployment script.
3. Choose application branch **production-version** or **main**, verifying it contains your change. The default is `production-version`; `dev` is not an allowed workflow deployment input.
4. Leave **Apply reviewed database migrations** unchecked unless this release needs approved migrations and backups are ready.
5. Record the application commit and release ID printed in the job. A release ID has the shape `<40-character-commit>-<run-number>-<attempt>`.
6. Require both the deployment step and public-health step to pass, then perform the application checks below.

The workflow is manual; a push does not deploy. It must exist on the repository's default branch to appear in Actions. GitHub's generic workflow branch selector may list other branches, but the job accepts only `main` and `production-version` for workflow and application selection.

### GitHub production environment

Configure **Settings → Environments → production**:

| Kind | Name | Value/purpose |
| --- | --- | --- |
| Secret | `VPS_HOST` | `84.46.254.80` for this VPS |
| Secret | `VPS_USER` | `deploy` |
| Secret | `VPS_PORT` | `22`, optional/default |
| Secret | `VPS_SSH_KEY` | Deployment private key; matching public key is authorized for `deploy` on the VPS |
| Secret | `VPS_KNOWN_HOSTS` | Independently verified host public key or matching known-hosts entry |
| Variable | `VPS_PATH` | `/srv/Workspace/projects/itechorbit/src` |
| Variable | `HEALTHCHECK_URL` | `https://www.itechorbit.com/healthz` |

`VPS_KNOWN_HOSTS` is not a login key or a fingerprint string. Obtain the public host key through the trusted VPS console (`cat /etc/ssh/ssh_host_ed25519_key.pub`) or an independently verified known-hosts entry. Nonstandard ports require `[host]:port` entries. Keep host-key verification enabled. Credentials belong in GitHub secrets/private VPS files, never documentation or source control.

## Manual deployment using the same script

Use this if Actions is unavailable. It performs the same staging, preservation and activation as CI; do not `git pull` into the live source or copy local build folders over it.

**Workstation / Bash, from a clean application checkout:** SSH access as `deploy` and a verified host key must already work. Select a reviewed remote branch; edit `app_ref` only if deliberately deploying another reviewed revision.

```bash
set -euo pipefail
git fetch origin main production-version
app_ref=origin/production-version
app_commit=$(git rev-parse "$app_ref")
release_id="${app_commit}-$(date +%s)-1"
staging=$(mktemp -d)
vps=deploy@84.46.254.80

git archive --format=tar.gz --output="$staging/release.tar.gz" "$app_commit"
git show origin/main:deploy/deploy-vps.sh > "$staging/deploy-vps.sh"
bash -n "$staging/deploy-vps.sh"
ssh "$vps" 'mkdir -p /srv/Workspace/projects/itechorbit/src.deploy/incoming'
scp "$staging/release.tar.gz" "$vps:/srv/Workspace/projects/itechorbit/src.deploy/incoming/$release_id.tar.gz"
ssh "$vps" bash -s -- /srv/Workspace/projects/itechorbit/src "$release_id" false < "$staging/deploy-vps.sh"
```

The last argument is `false` for no migrations. Use `true` only for the reviewed migration release. Each retry needs a new release ID; the script refuses an existing release/backup directory. SSH key/port customization belongs in your SSH configuration or the SSH commands, not in a committed credential file.

**Expected:** `Deployed <release-id> to /srv/Workspace/projects/itechorbit/src`. Then run the same public checks as Actions. Retain the staged script/release ID for incident investigation; the workstation archive contains tracked source only.

## What deployment changes

| Phase | Behavior and failure boundary |
| --- | --- |
| Preflight | Checks container source/storage mounts, runtime permissions and exact installed PHP/Node image IDs. Missing images fail early. |
| Staging | Extracts source under `src.deploy/releases/`; installs locked production Composer packages without scripts, checks platform requirements, installs npm build tools and builds client **and** SSR assets in temporary Docker containers. |
| Verification | Requires manifests and their assets; verifies the persistent-storage symlink. These checks happen before maintenance. |
| Backup | Saves previous code under `src.deploy/backups/<release-id>` with deployment exclusions; keeps the old Vite manifest. |
| Activation | Enters Laravel maintenance, stops SSR, publishes new hashed assets without deleting older hashes, then syncs application source in place. This is **not** an atomic symlink switch. |
| Runtime refresh | Removes stale package manifests, refreshes packages/caches, preserves legacy uploads, optionally migrates, signals queue restart, restarts PHP/Nginx and starts SSR. |
| Reopening | Requires PHP-to-SSR readiness before `artisan up`. Actions then checks the public health URL. |

Preserved paths include `.env*`, `.git`, legacy source `.docker`, `storage`, `bootstrap/cache`, `public/uploads`, `public/storage`, logs, existing build assets and old test-deploy directories. Read the script's `sync_options` before manual recovery; keep exclusions identical. Never use `rsync --delete-excluded`.

Database, Redis, gateway, private SMTP and inbound forwarding are not restarted by application deployment. Existing workers receive Laravel's restart signal, but new workers are not provisioned. Cron is configured independently. `/healthz` proves application boot; it does not prove SMTP delivery, queue consumption or SSR rendering.

## Verify after deployment

**VPS / deploy; any directory (read-only):**

```sh
curl --fail --silent --show-error https://www.itechorbit.com/healthz
docker exec --user www-data php-workspace-production-itechorbit-php-1 php artisan inertia:check-ssr
docker exec --user www-data php-workspace-production-itechorbit-php-1 php artisan migrate:status
docker logs --since 5m --tail 80 itechorbit-local-ssr-1
curl -sS -H 'Accept: text/html' -o /tmp/itechorbit-404.html -w '%{http_code}\n' https://www.itechorbit.com/not-a-real-directory/
grep 'error-page-art' /tmp/itechorbit-404.html
```

Expected: health success, SSR running, intended migration state, no fresh SSR errors, HTTP **404** and illustrated error markup. Check a missing storage file too. Open the site on desktop/mobile, verify CSS/JS loads, then test affected features using controlled accounts. Do not broadcast emails to validate a deployment.

If activation fails, maintenance can remain enabled and SSR can be stopped. Do not reopen blindly. Use [troubleshooting](operations/TROUBLESHOOTING.md) and [recovery](operations/BACKUP-RECOVERY.md#recover-a-failed-activation). A failed public-health check after reopening does not automatically roll code back.

## First-time host prerequisites

For a replacement/new VPS only: install Docker Engine with Compose, Python 3 and make for workspace tooling, Git, rsync, tar, util-linux (`flock`), ACL tools, cron and logrotate. Register the nested project using the workspace's own guide before running deployment. This runbook does not recreate missing database credentials, gateway routing or private infrastructure from source.

**VPS / root, only if preparing deployment access:**

```sh
usermod -aG docker deploy
install -d -o deploy -g deploy -m 755 /srv/Workspace/projects/itechorbit/src.deploy
setfacl -R -m u:deploy:rwX /srv/Workspace/projects/itechorbit/src
```

Reconnect the deployment SSH session, confirm `docker ps`, directory traversal and source/staging write access. Preserve existing ownership and environment bytes. ACLs give host deployment access; application runtime storage still needs its own `www-data` permissions. See the workspace's `PROJECT_GUIDE.md` and `docs/PRODUCTION.md` for infrastructure registration and gateway setup.

## Native-host examples

`deploy/nginx.conf.example`, `deploy/nginx-performance.conf` and `deploy/supervisor.conf.example` are references for a **separate native-host installation**. Do not install their host PHP sockets, `/srv/itechorbit/current` paths or Supervisor processes over this Docker stack. The supported production procedure above uses fixed source mounts, Docker services and the host scheduler entry.
