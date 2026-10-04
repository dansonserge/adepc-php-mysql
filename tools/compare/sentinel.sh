#!/usr/bin/env bash
# Proves no visible text is written in code: marks all content with "§",
# crawls every page, then restores the database.
set -euo pipefail
cd "$(dirname "$0")/../docker"
dc() { docker compose exec -T "$@"; }
backup=$(mktemp)
dc db mariadb-dump -uadepc -padepc_dev_password adepc > "$backup"
dc db mariadb -uadepc -padepc_dev_password adepc < ../compare/sentinel.sql
dc web sh -c 'rm -f /var/www/app/storage/cache/*.php'
status=0
python3 ../compare/sentinel.py || status=$?
dc db mariadb -uadepc -padepc_dev_password adepc < "$backup"
dc web sh -c 'rm -f /var/www/app/storage/cache/*.php'
rm -f "$backup"
exit $status
