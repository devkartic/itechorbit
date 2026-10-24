#!/usr/bin/env bash
# Invoked by .github/workflows/deploy-vps.yml on the Linux VPS.
set -euo pipefail
umask 0027

root=${1:?Deployment root required}
release_id=${2:?Release ID required}
fpm_service=${3:?PHP-FPM service required}
run_migrations=${4:-false}
[[ "$root" =~ ^/[a-zA-Z0-9/_-]+$ && "$root" != / ]]
[[ "$release_id" =~ ^[a-f0-9]{40}-[0-9]+-[0-9]+$ ]]
[[ "$fpm_service" =~ ^php[0-9]+\.[0-9]+-fpm$ ]]
[[ "$run_migrations" == true || "$run_migrations" == false ]]

for executable in php composer node npm tar flock curl; do
    command -v "$executable" >/dev/null
done

# Prevent competing deployments, including invocations outside GitHub Actions.
exec 9>"$root/.deploy.lock"
flock -n 9 || { echo 'Another deployment is running.' >&2; exit 1; }

shared="$root/shared"
release="$root/releases/$release_id"
archive="$root/incoming/$release_id.tar.gz"
test -s "$shared/.env"
test -d "$shared/storage"
test -d "$shared/uploads"
if [[ -e "$root/current" && ! -L "$root/current" ]]; then
    echo 'current must be a release symlink, not an existing application directory.' >&2
    exit 1
fi
test ! -e "$release"
mkdir -p "$root/releases"
mkdir "$release"
tar -xzf "$archive" -C "$release"
rm -f "$archive"
cd "$release"

# These paths belong to the VPS, never to the uploaded source archive.
rm -rf storage
ln -s "$shared/storage" storage
ln -s "$shared/.env" .env
ln -s "$shared/uploads" public/uploads
ln -s "$shared/storage/app/public" public/storage
mkdir -p bootstrap/cache storage/app/public storage/framework/{cache/data,sessions,views} storage/logs
chmod -R g+rwX bootstrap/cache

composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
composer check-platform-reqs --no-dev
# Keep node_modules: the SSR bundle can depend on runtime Node packages.
npm ci --include=dev
npm run build
rm -f public/hot

if [[ "$run_migrations" == true ]]; then
    php artisan migrate --force
fi
php artisan config:cache
php artisan route:cache
php artisan view:cache

previous=$(readlink -f "$root/current" || true)
echo "Previous release: ${previous:-none}"
ln -s "$release" "$root/.current-$release_id"
mv -Tf "$root/.current-$release_id" "$root/current"

# The sudoers configuration must permit only these service operations.
sudo -n systemctl reload "$fpm_service"
sudo -n supervisorctl restart itechorbit-ssr itechorbit-queue itechorbit-mail-notices
ssr_ready=false
for attempt in {1..10}; do
    if php artisan inertia:check-ssr; then
        ssr_ready=true
        break
    fi
    sleep 2
done
[[ "$ssr_ready" == true ]] || { echo 'SSR did not become ready.' >&2; exit 1; }
echo "Activated release: $release"
# Retain previous releases for deliberate rollback; never prune shared data.
