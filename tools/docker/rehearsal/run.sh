#!/usr/bin/env bash
# Simulates a cPanel upload and runs the web installer in a browser.
set -euo pipefail
here="$(cd "$(dirname "$0")" && pwd)"
root="$(cd "$here/../../.." && pwd)"
rm -rf "$here/upload" && mkdir -p "$here/upload"
cp -R "$root/public" "$here/upload/public_html"
rsync -a --exclude config.php --exclude 'storage/cache/*.php' --exclude 'storage/cache/*.json' --exclude 'storage/logs/*.log' "$root/app/" "$here/upload/app/"
chmod -R a+rwX "$here/upload"
cd "$here"
docker compose down -v >/dev/null 2>&1 || true
docker compose up -d
for i in $(seq 1 60); do curl -s -o /dev/null http://localhost:8090/install/ && break; sleep 2; done
docker compose exec -T web sh -c 'command -v node || echo "no node in the container"'
node "$here/installer.mjs"
