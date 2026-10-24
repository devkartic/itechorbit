#!/usr/bin/env bash
# Update the existing bind-mounted Docker application; never recreate data volumes.
set -euo pipefail
umask 0022

source_path=${1:?Existing application source path required}
source_path=${source_path%/}
root="$source_path.deploy"
release_id=${2:?Release ID required}
run_migrations=${3:-false}
[[ "$source_path" =~ ^/[a-zA-Z0-9/_-]+$ && "$source_path" != / ]] || exit 2
[[ "$release_id" =~ ^[a-f0-9]{40}-[0-9]+-[0-9]+$ ]] || exit 2
[[ "$run_migrations" == true || "$run_migrations" == false ]] || exit 2

for executable in docker tar flock rsync realpath; do
    command -v "$executable" >/dev/null
done
root=$(realpath -e "$root")
source_path=$(realpath -e "$source_path")
[[ "$root/" != "$source_path/"* ]] || {
    echo 'Staging must be outside the live source directory.' >&2; exit 1;
}
test -s "$source_path/.env"
test -w "$source_path"
exec 9>"$root/.deploy.lock"
flock -n 9 || { echo 'Another deployment is running.' >&2; exit 1; }

php_container=php-workspace-production-itechorbit-php-1
nginx_container=php-workspace-production-itechorbit-nginx-1
ssr_container=itechorbit-local-ssr-1
for container in "$php_container" "$nginx_container" "$ssr_container"; do
    [[ $(docker inspect --format '{{.State.Running}}' "$container") == true ]] || {
        echo "Container is not running: $container" >&2; exit 1;
    }
done
# Reject a wrong target path before synchronizing any source files.
for container in "$php_container" "$nginx_container"; do
    mount=$(docker inspect --format '{{range .Mounts}}{{if eq .Destination "/var/www/html"}}{{.Source}}{{end}}{{end}}' "$container")
    [[ "$mount" == "$source_path" ]] || {
        echo "Source mount does not match VPS_PATH: $container" >&2; exit 1;
    }
done
mount=$(docker inspect --format '{{range .Mounts}}{{if eq .Destination "/app"}}{{.Source}}{{end}}{{end}}' "$ssr_container")
[[ "$mount" == "$source_path" ]] || {
    echo 'SSR source mount does not match VPS_PATH.' >&2; exit 1;
}
php_image=$(docker inspect --format '{{.Image}}' "$php_container")
node_image=$(docker inspect --format '{{.Image}}' "$ssr_container")
docker exec "$php_container" php --version

release="$root/releases/$release_id"
backup="$root/backups/$release_id"
archive="$root/incoming/$release_id.tar.gz"
test ! -e "$release"
test ! -e "$backup"
mkdir -p "$release" "$backup"
tar -xzf "$archive" -C "$release"
rm -f "$archive"

# Reuse installed PHP/Node images, building outside the live source mount.
# Composer scripts run later with the runtime environment and storage volumes.
build_user="$(id -u):$(id -g)"
docker run --rm --user "$build_user" -e HOME=/tmp -e COMPOSER_HOME=/tmp/composer \
    --volume "$release:/var/www/html" --workdir /var/www/html "$php_image" \
    composer install --no-dev --no-scripts --prefer-dist --no-interaction --optimize-autoloader
docker run --rm --user "$build_user" --volume "$release:/var/www/html" \
    --workdir /var/www/html "$php_image" composer check-platform-reqs --no-dev
docker run --rm --user "$build_user" -e HOME=/tmp \
    --volume "$release:/app" --workdir /app "$node_image" \
    sh -ec 'npm ci --include=dev && npm run build'
test -s "$release/public/build/manifest.json"
test -s "$release/bootstrap/ssr/ssr.js"

# Protect secrets, uploads, volume mountpoints and local tooling.
# Never use --delete-excluded or copy into Docker's volume directories.
# Keep existing ownership, modes and ACLs; the SSH user may have ACL access
# to root-owned source directories without permission to chmod or timestamp them.
sync_options=(-rlc --exclude='/.env' --exclude='/.env.*'
    --exclude='/.git/' --exclude='/.docker/' --exclude='/storage/'
    --exclude='/bootstrap/cache/' --exclude='/public/uploads/'
    --exclude='/public/storage' --exclude='/logs/')
rsync "${sync_options[@]}" "$source_path/" "$backup/"
echo "Previous code backed up at: $backup"

artisan() { docker exec --user www-data "$php_container" php artisan "$@"; }
maintenance=false
on_error() {
    status=${1:-$?}
    if [[ "$maintenance" == true ]]; then
        echo "Deployment failed after maintenance began. Inspect the application before running: docker exec --user www-data $php_container php artisan up" >&2
        echo "Code backup: $backup (database and uploads are not included)." >&2
    fi
    exit "$status"
}
trap on_error ERR
artisan down --retry=60
maintenance=true
docker stop "$ssr_container"
rsync "${sync_options[@]}" --delete "$release/" "$source_path/"
rm -f "$source_path/public/hot"
# Old discovery manifests can reference development packages absent in this build.
docker exec --user www-data "$php_container" php -r \
    'foreach (glob("bootstrap/cache/*.php") as $file) { if (!unlink($file)) { exit(1); } }'
artisan config:clear
artisan route:clear
artisan package:discover --ansi
if [[ "$run_migrations" == true ]]; then
    artisan migrate --force
fi
artisan config:cache
artisan route:cache
artisan view:cache
artisan queue:restart
# Restart existing containers only; database, Redis, Caddy and volumes stay running.
docker restart "$php_container" "$nginx_container"
docker start "$ssr_container"
ssr_ready=false
for attempt in {1..15}; do
    if artisan inertia:check-ssr; then
        ssr_ready=true
        break
    fi
    sleep 2
done
if [[ "$ssr_ready" != true ]]; then
    echo 'SSR did not become ready; leaving maintenance enabled.' >&2
    on_error 1
fi
artisan up
maintenance=false
echo "Deployed $release_id to $source_path"
