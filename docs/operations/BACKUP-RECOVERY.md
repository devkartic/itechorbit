# Backup and recovery

[Deployment](../DEPLOYMENT.md) · [Services](SERVICES.md) · [Troubleshooting](TROUBLESHOOTING.md)

## What each backup contains

| Backup | Includes | Does not replace |
| --- | --- | --- |
| `src.deploy/backups/<release-id>` | Previous source/dependencies and previous Vite manifest | Database, environment, uploaded/runtime storage, parent Docker settings |
| Database SQL dump | Schema and data from the selected application database | Uploaded files, signing keys, source or credentials |
| Laravel storage archive | Public uploads, private media and other storage data | Database records and source code |
| Private infrastructure archive | Parent `.env`/`.docker`, generated workspace settings, cron and operational secrets | Live Docker named volumes unless separately archived |
| SMTP/DKIM and forwarding state | Queued mail, signing keys, SRS secrets, filters/configuration | Gmail inbox contents or already-delivered messages |

Keep backups encrypted/off-host with a defined retention policy. A file's existence or size is not a restore test. Rehearse on an isolated staging installation with outbound mail disabled and different ports/networks/volume names. Never attach production volumes to staging writers.

## Before a data-changing release

Record the reviewed application commit, deployment-tool commit, runtime image IDs/tags, mounts, volume names, parent configuration revision and backup locations. Full `docker inspect` and rendered Compose can contain secrets; store them privately, not in issue comments.

Take database/media snapshots during an agreed write pause if they must represent the same point in time. Laravel maintenance does not stop queue workers, incoming email or administrators changing the database directly. Coordinate those writers explicitly. The automatic deployment code backup is taken before maintenance and is not an atomic whole-system snapshot.

## Back up the database

**VPS / root, from `/srv/Workspace`; requires private workspace credentials and generated application configuration:**

```sh
cd /srv/Workspace
make backup ENV=production NAME=itechorbit
```

Expected: a private `.sql` file path under `docker/backups/production/itechorbit/`. The workspace uses the existing MariaDB credentials, transactional dump, routines/events/triggers and reports a failure rather than leaving a successful-looking partial dump. Copy the resulting file off-host securely and record the corresponding code revision. Do not print passwords or put them in a command-line `-pPASSWORD` argument.

## Back up uploads and private configuration

**VPS / root; example backup session, do not run as an incident diagnostic.** Confirm the volume names in [Services](SERVICES.md#find-logs-and-mounted-data). Coordinate writes first; for a consistent application snapshot, enter maintenance and pause relevant queue consumers. Take the SQL dump in the same window. Keep track of which services were previously running so only those are resumed.

```sh
umask 077
backup_dir="/root/itechorbit-backup-$(date -u +%Y%m%dT%H%M%SZ)"
mkdir "$backup_dir"
php_image=$(docker inspect --format '{{.Image}}' php-workspace-production-itechorbit-php-1)
docker image inspect "$php_image" >/dev/null

docker run --rm --network none --read-only \
  --mount type=volume,source=php-workspace-production-itechorbit_runtime_storage,target=/data,readonly \
  --mount "type=bind,source=$backup_dir,target=/backup" \
  --entrypoint tar "$php_image" -C /data -czf /backup/laravel-storage.tar.gz .

tar -C /srv/Workspace -czf "$backup_dir/private-config.tar.gz" \
  projects/itechorbit/.env \
  projects/itechorbit/src/.env \
  projects/itechorbit/.docker \
  docker/.env.production \
  docker/stacks/production/gateway \
  docker/stacks/production/itechorbit
crontab -u deploy -l > "$backup_dir/deploy.crontab"
cp /etc/logrotate.d/itechorbit-scheduler "$backup_dir/itechorbit-scheduler.logrotate"
```

The private archive includes forwarding state beneath `.docker/forwarding/`. Stop the inbound container while archiving its queue/state if using file-level archives, then resume its prior running state. A running SMTP queue changing under `tar` is not a consistent queue backup. The example does not stop services automatically; plan and record the write pause before executing it.

The source `public/uploads` may still contain legacy media, while current uploads live on the storage volume. Back up legacy host uploads separately if they remain, and include any other persistent source media identified in your inventory. `public/storage` is the link, not the upload content. Keep private file modes/ownership in the archive.

For outgoing queue/signing-key archives, stop only outgoing SMTP during the snapshot, then use the same read-only volume/tar pattern for `php-workspace-production-itechorbit_smtp_queue` and `_smtp_dkim`. Capture the Caddy certificates/config volumes too for whole-VPS recovery, or plan fresh certificate issuance with its ACME rate limits. Redis persistence is separate; if using its data for jobs or application state, obtain a Redis-consistent backup rather than copying a running database file. Bootstrap cache can be rebuilt and is not a substitute for application data.

Resume paused workers/services and reopen the application only after checking health. Verify archives with `tar -tzf`, checksum the backups, and rehearse restoring into disposable volumes/database. An SQL backup containing secrets must use the same private handling.

## Recover a failed activation

**VPS / deploy; inspect before changing state:**

```sh
docker ps -a --format 'table {{.Names}}\t{{.Status}}'
docker logs --since 15m --tail 100 itechorbit-local-ssr-1
docker exec php-workspace-production-itechorbit-php-1 tail -n 80 storage/logs/laravel.log
```

1. Find the workflow's exact failing phase and recorded backup path. Build-stage failure normally leaves source unchanged; activation failure may leave partial source and maintenance enabled.
2. Correct the underlying issue. For a stopped existing SSR container, `docker start itechorbit-local-ssr-1` preserves its definition; it does not repair a stale bundle or wrong listener.
3. Prefer a fresh deployment of the intended compatible commit with a **new** release ID. The script can recover while maintenance is already enabled.
4. Before reopening manually, verify PHP/SSR readiness, intended source/schema compatibility, runtime permissions and logs. `artisan up` alone is not a repair.

**VPS / deploy; only after readiness succeeds:**

```sh
docker exec --user www-data php-workspace-production-itechorbit-php-1 php artisan inertia:check-ssr
docker exec --user www-data php-workspace-production-itechorbit-php-1 php artisan up
curl --fail --silent --show-error https://www.itechorbit.com/healthz
```

The public health check runs after reopening; investigate a failure rather than assuming automatic rollback occurred.

## Restore previous code only

Use this only after reviewing database compatibility and the backup. It does not undo migrations, mail delivery or user data changes. Retain the current failed release for diagnosis, and take another private snapshot before overwriting it. For feature changes with data incompatibilities, follow the reviewed database recovery plan instead.

**VPS / deploy; Bash; from `/srv/Workspace/projects/itechorbit`; writes live source:** replace the backup placeholder with the actual path printed by deployment. Keep application queue consumers stopped while replacing their code; list any separately added notice/AI workers and pause them too. Maintenance makes the regular scheduler skip ordinary tasks.

```bash
set -euo pipefail
cd /srv/Workspace/projects/itechorbit
backup=/srv/Workspace/projects/itechorbit/src.deploy/backups/REPLACE_WITH_RELEASE_ID
test -s "$backup/vendor/autoload.php"
test -s "$backup/bootstrap/ssr/ssr.js"
test -s "$backup/public/build/manifest.json"

docker exec --user www-data php-workspace-production-itechorbit-php-1 php artisan down --retry=60
docker stop itechorbit-local-ssr-1 php-workspace-production-itechorbit-worker-1

sync_options=(-rlc --exclude='/.env' --exclude='/.env.*'
  --exclude='/.git/' --exclude='/.docker/' --exclude='/storage'
  --exclude='/bootstrap/cache' --exclude='/public/uploads'
  --exclude='/public/storage' --exclude='/logs/' --exclude='/public/build'
  --exclude='/test-deploy/' --exclude='/test-deploy.deploy/')
rsync "${sync_options[@]}" --delete "$backup/" src/
cp "$backup/public/build/manifest.json" src/public/build/manifest.json
```

Compare `sync_options` with the deployment script revision in use before executing. Previous hashed assets must still exist under live `public/build`; verify each old manifest asset before reopening. Do not delete current or older hashes as part of rollback. Stop if assets are missing and recover the matching build. Never remove uploads or use `--delete-excluded`.

**VPS / deploy; continuation after the reviewed source restore:**

```sh
docker exec --user www-data php-workspace-production-itechorbit-php-1 php -r 'foreach (glob("bootstrap/cache/*.php") as $file) { if (!unlink($file)) { exit(1); } }'
docker exec --user www-data php-workspace-production-itechorbit-php-1 php artisan package:discover --ansi
docker exec --user www-data php-workspace-production-itechorbit-php-1 php artisan config:cache
docker exec --user www-data php-workspace-production-itechorbit-php-1 php artisan route:cache
docker exec --user www-data php-workspace-production-itechorbit-php-1 php artisan view:cache
docker restart php-workspace-production-itechorbit-php-1 php-workspace-production-itechorbit-nginx-1
docker start itechorbit-local-ssr-1
docker exec --user www-data php-workspace-production-itechorbit-php-1 php artisan inertia:check-ssr
```

Allow PHP and SSR to finish starting. If the immediate SSR check fails during startup, inspect their logs and retry `inertia:check-ssr`; require success before reopening. Inspect runtime health/logs, confirm new uploads remain accessible, resume only the workers that were running before the pause, and use `artisan up` only after these checks pass. If anything fails, keep maintenance enabled. Check the illustrated 404 and assets after reopening. The source restore preserves the current `.env`, `APP_KEY`, database and storage volumes.

## Database restore boundary

A database restore can overwrite customer/admin changes since the backup. Obtain a reviewed recovery point, coordinated write pause and matching code/media first. Test the SQL against an isolated database before touching production.

**VPS / root, `/srv/Workspace`; destructive restore only after the above review:**

```sh
cd /srv/Workspace
make restore ENV=production NAME=itechorbit FILE=/absolute/path/to/REVIEWED_BACKUP.sql
```

Expected: a successful import. Verify record counts, schema, authentication and media references before resuming writers. This imports the supplied SQL; it is not a merge or automatic full reset, and failure may leave a partial import. Preserve the pre-restore database snapshot. Consolidated migrations intentionally refuse destructive rollback; do not delete migration history, run `migrate:fresh`, or run demo seeders to bypass that boundary.

## Infrastructure and mail recovery

Rebuild a replacement host using reviewed workspace configuration and the same project/volume identities. Restore application secrets privately, preserving `APP_KEY`. Restore database/media as a matched recovery point. Inspect actual mounts before starting writers; empty same-named-looking volumes are not recovered data.

Restore parent Docker settings and mail keys/state before starting mail services. Queue archives can contain messages already accepted by recipients; blindly replaying old queues can duplicate email. Compare logs/queue IDs and reconcile delivery outcomes first. Preserve the SRS secret and ARC/DKIM keys corresponding to DNS. Reissue a valid mail certificate if restoring old credentials is unsuitable; verify automatic renewal and certificate mounts.

A rollback of application source should not restore the entire workspace, DNS or mail configuration. Use the smallest reviewed recovery scope.
