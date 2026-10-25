# Scheduler and queue operations

Example placeholders: IP addresses, private email addresses, SSH users, host paths and infrastructure names below are illustrative. Replace them with verified values from your private deployment configuration before running commands. These examples do not change production settings.

[Deployment](../DEPLOYMENT.md) · [Mail](MAIL.md) · [Troubleshooting](TROUBLESHOOTING.md)

## Scheduler versus workers

Cron invokes Laravel's scheduler. Scheduled commands enqueue work or perform maintenance; queue workers process queued jobs. An SMTP container delivers messages after application jobs submit them. Mail-container cron processes are separate maintenance and must not be disabled to remove an apparent duplicate Laravel scheduler.

Verified 26 October 2026: one Laravel scheduler entry is installed for VPS user `example-user`; host cron is active/enabled. The regular application worker consumes the **default** database queue only.

| Scheduled command | Frequency | Behavior |
| --- | --- | --- |
| `notices:dispatch` | Every minute | Dispatch/recover pending admin notice recipients |
| `ai-pro:maintain` | Daily, midnight UTC | Purge expired conversations/old sync records and enqueue eligible site synchronization |

Production Laravel timezone is **UTC**: midnight is **06:00 Asia/Dhaka**. This is the application's schedule timezone, not necessarily the host log display timezone. Both tasks use `withoutOverlapping`; the host `flock` also prevents concurrent scheduler invocations. Ordinary tasks skip Laravel maintenance mode by default.

## Inspect installed cron

**VPS / root:**

```sh
crontab -u deploy -l
systemctl status cron --no-pager
```

**VPS / example-user:** use `crontab -l` instead. Exactly one application entry should be present:

```cron
* * * * * /usr/bin/flock -n /tmp/example-production-scheduler.lock /usr/bin/docker exec --user www-data example-production-app-php-1 php artisan schedule:run >> /srv/example-project/src.deploy/scheduler.log 2>&1
```

**VPS / example-user; read-only:**

```sh
tail -n 50 /srv/example-project/src.deploy/scheduler.log
docker exec --user www-data example-production-app-php-1 php artisan schedule:list
```

Expected: both commands listed and recent `notices:dispatch ... DONE` entries. The log need not show daily maintenance before midnight. A `DONE` dispatch does not prove Gmail delivery or consumption of the dedicated notice queue.

## Run due tasks manually

**VPS / example-user; runs real scheduled work, including possible notice dispatch:** do not use as a harmless readiness check.

```sh
/usr/bin/flock -n /tmp/example-production-scheduler.lock \
  /usr/bin/docker exec --user www-data example-production-app-php-1 php artisan schedule:run
```

This runs tasks due now; it does not force the daily task outside its schedule. To deliberately force AI maintenance after reviewing its deletions/enqueueing behavior, use `docker exec --user www-data example-production-app-php-1 php artisan ai-pro:maintain`. Check pending synchronization jobs afterward. Do not clear overlap locks while another task is active.

## Install, change or remove the scheduler

**VPS / example-user:** first check system/root crontabs with an administrator so there is only one scheduler. Back up the existing crontab privately, then use `crontab -e` to add/change the single marked application entry. Preserve unrelated cron jobs. Do not pipe a one-line replacement directly over an existing crontab.

Previous-crontab backup from initial installation: `src.deploy/hotfix-backups/deploy.crontab.before-scheduler-*`. Those files preserve the state at that time; restoring an old whole crontab could remove jobs added later. To disable scheduling, remove only the marked iTechOrbit entry with `crontab -e`, then verify `crontab -l`. Mail service cron remains independent.

Host logrotate config is `/etc/logrotate.d/itechorbit-scheduler`: daily rotation, 14 retained rotations, compression/delayed compression, 1 MB max-size check when logrotate runs, and `copytruncate` as `example-user`. This is not an instantaneous hard size cap.

**VPS / root; read-only validation:**

```sh
logrotate --debug --state /dev/null /etc/logrotate.d/itechorbit-scheduler
```

Expected: parsed config without errors. `--debug` does not rotate. Host package updates/administration should preserve the cron daemon and logrotate timer.

## Queue connections and workers

Source of truth: `config/queue.php`, job constructors and parent production Compose overrides.

| Connection / queue | Current consumer | Requirement |
| --- | --- | --- |
| `database` / `default` | `example-production-app-worker-1` | Contact/portfolio and other ordinary queued jobs |
| `mail_notices` / `mail-notices` | `example-production-app-worker-notices-1` | Admin notice dispatch enqueues here; dedicated consumer installed |
| `ai_pro` / `ai-pro` | `example-production-app-worker-ai-1` | Dedicated AI site synchronization consumer installed |

The notice consumer was installed on 26 October 2026 (Asia/Dhaka) as Compose service `worker-notices` in the parent `.docker/compose.override.production.yml`. It inherits the default worker's image, read-only source, writable runtime volumes, `www-data` user, limits, logging and `unless-stopped` restart policy through the `application-worker` YAML anchor. It consumes only `mail_notices --queue=mail-notices`, with a 60-second timeout and 90-second shutdown grace. The previous override is saved beside it as `compose.override.production.yml.before-notice-worker-20261025203251`.

Verification: the previously waiting notice changed from queued/zero attempts to sent/one attempt, and outgoing Postfix recorded Gmail acceptance with SMTP `250 2.0.0 OK`. The notice queue and failed-job list were empty afterward. SMTP acceptance does not establish inbox placement. Inspect this consumer using `docker logs --since 10m --tail 100 example-production-app-worker-notices-1`.

The AI service was added as `worker-ai` in the same parent override and activated on 26 October 2026 (Asia/Dhaka), after the owner explicitly approved processing website knowledge with the configured Gemini service. It inherits the application's runtime mounts and restrictions, with a 600-second timeout, 630-second shutdown grace, two attempts, 384 MB PHP memory limit, 256 MB worker recycle threshold and 512 MB container limit. The pre-change backup is `compose.override.production.yml.before-ai-worker-20261025203535`; updated Compose validation passed.

The inspected configuration uses Gemini for chat and embeddings, with credentials present and 73 local knowledge sources. The worker started processing the four waiting runs. The service has `profiles: [ai-pro]`: Docker's `unless-stopped` policy restarts an existing container after host reboot, while explicit service selection or the profile is required when creating it again. Inspect with `docker logs --since 10m --tail 100 example-production-app-worker-ai-1`. Do not describe a default worker as handling every queue.

Activation verification: all four waiting runs completed, each reporting 73 processed sources with no errors. All 73 knowledge sources were ready; the job backlog and failed-job table were empty. This verifies synchronization/indexing, not a separate end-to-end chat request. Future provider quota or credential failures still require inspecting run errors and worker logs.

### Provision a missing consumer

**VPS / root; reviewed infrastructure change:** back up parent `.docker/compose.override.production.yml`, clone the existing worker's image, `www-data` user, read-only source mount, storage/bootstrap-cache volume mounts, tmpfs, limits, logging, restart policy and dependencies. Give the new service its own name. Preserve all existing services and mail settings.

Use the following commands as the worker's Compose `command` (not as host daemons):

```sh
php artisan queue:work mail_notices --queue=mail-notices --sleep=1 --tries=3 --timeout=60 --max-time=3600
php artisan queue:work ai_pro --queue=ai-pro --sleep=3 --tries=3 --timeout=600 --max-time=3600
```

The first worker timeout is below its connection's 120-second retry interval; the AI timeout is below its 660-second interval. Keep stop grace longer than the configured timeout (for example 90 seconds for notice mail and 630 seconds for AI). Use the same mounted-production PHP image as the application. The native Supervisor example is a command reference, not a service to install on this Docker VPS.

Job properties override corresponding worker defaults. `SyncSite` currently sets its own 600-second timeout, two attempts and 30-second backoff; `--tries=3` does not give that job a third attempt. Provider retry scheduling is separate. Review each job class before changing timeouts, retry intervals or shutdown grace periods.

Validate the override and start only the new services with [the application Compose helper](SERVICES.md#target-one-application-compose-service). Inspect each worker's logs and controlled test-job completion. Starting a consumer can process previously queued real emails or AI jobs immediately; review backlog before activation.

## Failed jobs and deployment restarts

**VPS / example-user; inspect first:**

```sh
docker exec --user www-data example-production-app-php-1 php artisan queue:failed
docker logs --since 15m --tail 100 example-production-app-worker-1
```

Retry only a reviewed failed-job identifier after correcting its cause. A retry may send an email again. Do not use broad retry/flush commands to investigate an unknown delivery outcome.

Application deployment signals `queue:restart`; workers supervised by Docker exit and restart gracefully. This does not change their image, mounts or environment supplied by Compose. After an image/Compose change, deliberately recreate affected workers. Expect a graceful restart to wait for current jobs; do not force-kill active work just because restart takes longer than the PHP request timeout.
