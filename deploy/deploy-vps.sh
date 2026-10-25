#!/usr/bin/env bash
# Deploy source and fresh assets to the existing Docker application.
set -euo pipefail
umask 0022

# Arguments from GitHub: VPS_PATH, unique release ID, migration checkbox.
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
source_path=$(realpath -m "$source_path")
[[ "$root/" != "$source_path/"* ]] || {
    echo 'Staging must be outside the live source directory.' >&2; exit 1;
}
exec 9>"$root/.deploy.lock"
# Also prevent deployments launched outside GitHub from running simultaneously.
flock -n 9 || { echo 'Another deployment is running.' >&2; exit 1; }

php_container=php-workspace-production-itechorbit-php-1
nginx_container=php-workspace-production-itechorbit-nginx-1
ssr_container=itechorbit-local-ssr-1
# SSR may be stopped by a previous failed deployment; its image and mounts can
# still be inspected, and activation starts it again after rebuilding the bundle.
for container in "$php_container" "$nginx_container"; do
    [[ $(docker inspect --format '{{.State.Running}}' "$container") == true ]] || {
        echo "Container is not running: $container" >&2; exit 1;
    }
done
# Resolve the running application's mount before accepting a deployment target.
live_source=$(docker inspect --format '{{range .Mounts}}{{if eq .Destination "/var/www/html"}}{{.Source}}{{end}}{{end}}' "$php_container")
test -n "$live_source"
for container in "$php_container" "$nginx_container"; do
    mount=$(docker inspect --format '{{range .Mounts}}{{if eq .Destination "/var/www/html"}}{{.Source}}{{end}}{{end}}' "$container")
    [[ "$mount" == "$live_source" ]] || {
        echo "Application containers have different source mounts: $container" >&2; exit 1;
    }
done
mount=$(docker inspect --format '{{range .Mounts}}{{if eq .Destination "/app"}}{{.Source}}{{end}}{{end}}' "$ssr_container")
[[ "$mount" == "$live_source" ]] || {
    echo 'SSR source mount does not match the PHP source mount.' >&2; exit 1;
}
[[ "$source_path" == "$live_source" ]] || {
    echo 'VPS_PATH must match the running containers source directory.' >&2; exit 1;
}
test -s "$source_path/.env" || {
    echo 'The live application .env file is missing or empty.' >&2; exit 1;
}
test -w "$source_path"
# PHP and Nginx must serve the same persistent public-disk files.
storage_mount=$(docker inspect --format '{{range .Mounts}}{{if eq .Destination "/var/www/html/storage"}}{{.Source}}{{end}}{{end}}' "$php_container")
nginx_storage_mount=$(docker inspect --format '{{range .Mounts}}{{if eq .Destination "/var/www/html/storage"}}{{.Source}}{{end}}{{end}}' "$nginx_container")
[[ -n "$storage_mount" && "$storage_mount" == "$nginx_storage_mount" ]] || {
    echo 'PHP and Nginx must share the same persistent Laravel storage volume.' >&2; exit 1;
}
# Check the real runtime user before entering maintenance. Do not boot Laravel:
# its logger cannot explain a permission failure when the log itself is blocked.
docker exec --user www-data "$php_container" php -r '
    foreach (["storage/logs", "storage/app/public", "storage/framework/cache/data", "storage/framework/sessions", "storage/framework/views", "bootstrap/cache"] as $directory) {
        if (! is_dir($directory) || ! is_writable($directory)) {
            fwrite(STDERR, "Runtime directory must exist and be writable by www-data: ".$directory.PHP_EOL);
            exit(1);
        }
        $probe = $directory."/.deploy-write-".bin2hex(random_bytes(8));
        $handle = @fopen($probe, "x");
        if ($handle === false) {
            fwrite(STDERR, "Runtime write check failed for www-data: ".$directory.PHP_EOL);
            exit(1);
        }
        fclose($handle);
        if (! unlink($probe)) {
            exit(1);
        }
    }
    if (file_exists("storage/logs/laravel.log") && ! is_writable("storage/logs/laravel.log")) {
        fwrite(STDERR, "storage/logs/laravel.log must be writable by www-data.".PHP_EOL);
        exit(1);
    }
    echo "Runtime storage permissions verified for www-data.".PHP_EOL;
    '
php_image=$(docker inspect --format '{{.Image}}' "$php_container")
node_image=$(docker inspect --format '{{.Image}}' "$ssr_container")
# A container can keep running after its original image has been removed.
# Build with the exact runtime images, and fail before extracting a release if
# either image is unavailable. A mutable tag may refer to a different runtime.
require_runtime_image() {
    local container=$1 image=$2
    if ! docker image inspect "$image" >/dev/null 2>&1; then
        echo "Runtime image unavailable for container $container: $image" >&2
        echo 'Reconcile the affected container using its existing Compose configuration and intended image, preserving mounts and volumes, then retry deployment.' >&2
        exit 1
    fi
}
require_runtime_image "$php_container" "$php_image"
require_runtime_image "$ssr_container" "$node_image"
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
# Install the locked frontend dependencies, including build tools.
docker run --rm --user "$build_user" -e HOME=/tmp \
    --volume "$release:/app" --workdir /app "$node_image" \
    npm ci --include=dev
# Refresh frontend assets AND the Inertia SSR bundle on every deployment.
# Node runs inside Docker; npm does not need to be installed on Ubuntu.
echo 'Refreshing application assets: npm run build'
docker run --rm --user "$build_user" -e HOME=/tmp \
    --volume "$release:/app" --workdir /app "$node_image" \
    npm run build
test -s "$release/public/build/manifest.json"
test -s "$release/bootstrap/ssr/ssr.js"
# Reject an incomplete build before entering maintenance or replacing live code.
docker run --rm --user "$build_user" --volume "$release:/var/www/html" \
    --workdir /var/www/html "$php_image" php -r '
        $manifest = json_decode(file_get_contents("public/build/manifest.json"), true, 512, JSON_THROW_ON_ERROR);
        foreach ($manifest as $chunk) {
            foreach ([$chunk["file"], ...($chunk["css"] ?? []), ...($chunk["assets"] ?? [])] as $asset) {
                if (! is_file("public/build/".$asset)) {
                    fwrite(STDERR, "Build asset is missing: ".$asset.PHP_EOL);
                    exit(1);
                }
            }
        }
        echo "Verified all Vite manifest assets.".PHP_EOL;
    '

# The application source is read-only INSIDE production containers. Create the
# standard Laravel link on the writable VPS host instead of running storage:link
# as a host UID inside PHP. The relative target resolves against the container's
# persistent storage volume, not Docker's host-side volume directory.
storage_link="$source_path/public/storage"
if [[ ! -L "$storage_link" ]]; then
    if [[ -e "$storage_link" ]]; then
        echo 'public/storage exists but is not a symlink; preserve its files and resolve this manually before deploying.' >&2
        exit 1
    fi
    ln -s ../storage/app/public "$storage_link"
fi
docker exec --user www-data "$php_container" php -r '
    $target = realpath("storage/app/public");
    if (! is_link("public/storage") || $target === false || realpath("public/storage") !== $target) {
        fwrite(STDERR, "public/storage must link to the mounted storage/app/public directory.".PHP_EOL);
        exit(1);
    }
    echo "Laravel public storage link verified.".PHP_EOL;
    '

# Protect secrets, uploads, volume mountpoints and local tooling.
# Keep the legacy source-level .docker exclusion until server-specific settings
# have been reviewed and moved to the parent project's single .docker directory.
# Never use --delete-excluded or copy into Docker's volume directories.
# Keep existing ownership, modes and ACLs; the SSH user may have ACL access
# to root-owned source directories without permission to chmod or timestamp them.
sync_options=(-rlc --exclude='/.env' --exclude='/.env.*'
    --exclude='/.git/' --exclude='/.docker/' --exclude='/storage'
    --exclude='/bootstrap/cache' --exclude='/public/uploads'
    --exclude='/public/storage' --exclude='/logs/'
    --exclude='/public/build'
    --exclude='/test-deploy/' --exclude='/test-deploy.deploy/')
rsync "${sync_options[@]}" "$source_path/" "$backup/"
# Old hashed files remain live; keep the previous manifest for code rollback.
if [[ -f "$source_path/public/build/manifest.json" ]]; then
    mkdir -p "$backup/public/build"
    cp "$source_path/public/build/manifest.json" "$backup/public/build/manifest.json"
fi
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
# Builds have finished. Briefly pause requests while replacing source and assets.
artisan down --retry=60
maintenance=true
docker stop "$ssr_container"
# Publish new hashed assets without deleting older chunks used by open tabs.
# The source sync excludes this directory so --delete cannot remove them later.
mkdir -p "$source_path/public/build"
rsync -rlc "$release/public/build/" "$source_path/public/build/"
rsync "${sync_options[@]}" --delete "$release/" "$source_path/"
rm -f "$source_path/public/hot"
# Old discovery manifests can reference development packages absent in this build.
docker exec --user www-data "$php_container" php -r \
    'foreach (glob("bootstrap/cache/*.php") as $file) { if (!unlink($file)) { exit(1); } }'
artisan config:clear
artisan route:clear
artisan package:discover --ansi
# Keep a checksum-verified copy of old uploads on the existing storage volume.
# This command never overwrites conflicting files or deletes public/uploads.
artisan media:preserve-uploads
if [[ "$run_migrations" == true ]]; then
    # Enabled only through the manual workflow checkbox; never reset or seed data.
    artisan migrate --force
fi
# Refresh Laravel caches so the new code and production settings take effect.
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
    docker inspect --format 'SSR container: {{.State.Status}}; health: {{if .State.Health}}{{.State.Health.Status}}{{else}}not configured{{end}}' "$ssr_container" || true
    echo 'SSR must listen on 0.0.0.0 inside Docker (INERTIA_SSR_HOST). PHP must use its Docker address, e.g. INERTIA_SSR_URL=http://itechorbit-ssr:13714, rather than 127.0.0.1.' >&2
    echo 'SSR did not become ready; leaving maintenance enabled.' >&2
    on_error 1
fi
artisan up
maintenance=false
echo "Deployed $release_id to $source_path"
