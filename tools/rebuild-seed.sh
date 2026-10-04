#!/usr/bin/env bash
# Re-exports the Next.js site's content into app/database/seed and rebuilds
# the image sizes. Dev-only; reads ../adepc without changing it.
# With the reference site running (cd ../adepc && pnpm start), the seed photos'
# WebP sizes are then taken from its image optimizer, byte for byte.
set -euo pipefail
here="$(cd "$(dirname "$0")/.." && pwd)"
(cd "$here/../adepc" && node --import tsx --require ./scripts/lib/register-assets.cjs "$here/tools/export-content.ts")
php "$here/tools/build-media.php" > /dev/null
if curl -s -o /dev/null --max-time 3 http://localhost:3000/fr; then
  node "$here/tools/fetch-next-variants.mjs"
else
  echo "reference site not running: keeping the GD-generated WebP sizes"
fi
echo "seed rebuilt"
