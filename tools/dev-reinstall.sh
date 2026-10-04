#!/usr/bin/env bash
# Reinstalls the local Docker stack's database from the seed.
set -euo pipefail
cd "$(dirname "$0")/docker"
docker compose exec -T web php /var/www/project/tools/install-cli.php --host=db --port=3306 --db=adepc --user=adepc \
  --pass=adepc_dev_password --admin-email=admin@adepc.test --admin-pass=dev-password-123 --site-url="${SITE_URL:-https://adepc.ca}"
