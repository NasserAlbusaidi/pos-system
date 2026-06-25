#!/usr/bin/env bash
# Bite-POS Forge database backup.
# Run from Forge Scheduler/cron after setting BACKUP_S3_URI when remote upload
# is configured. The script reads Laravel's cached database config instead of
# parsing .env directly.
set -euo pipefail

APP_ROOT="${APP_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
PHP_BIN="${FORGE_PHP:-php}"
BACKUP_DIR="${BACKUP_DIR:-$APP_ROOT/storage/app/backups}"
BACKUP_KEEP_DAYS="${BACKUP_KEEP_DAYS:-14}"

cd "$APP_ROOT"
umask 077
mkdir -p "$BACKUP_DIR"

if ! command -v mysqldump >/dev/null 2>&1; then
    echo "mysqldump is required for database backups." >&2
    exit 1
fi

if [[ -n "${BACKUP_S3_URI:-}" ]] && ! command -v aws >/dev/null 2>&1; then
    echo "aws CLI is required when BACKUP_S3_URI is set." >&2
    exit 1
fi

eval "$("$PHP_BIN" <<'PHP'
<?php

$root = getcwd();

require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$connection = config('database.connections.mysql');

$values = [
    'DB_HOST' => $connection['host'] ?? '',
    'DB_PORT' => $connection['port'] ?? '',
    'DB_SOCKET' => $connection['unix_socket'] ?? '',
    'DB_DATABASE' => $connection['database'] ?? '',
    'DB_USERNAME' => $connection['username'] ?? '',
    'DB_PASSWORD' => $connection['password'] ?? '',
];

foreach ($values as $key => $value) {
    echo $key.'='.escapeshellarg((string) $value).PHP_EOL;
}
PHP
)"

if [[ -z "${DB_DATABASE}" || -z "${DB_USERNAME}" ]]; then
    echo "Missing MySQL database name or username in Laravel config." >&2
    exit 1
fi

timestamp="$(date +%Y-%m-%d-%H%M%S)"
archive="$BACKUP_DIR/getbite-db-$timestamp.sql.gz"
mysql_args=(
    --single-transaction
    --quick
    --routines
    --triggers
    --default-character-set=utf8mb4
    --user="$DB_USERNAME"
)

if [[ -n "${DB_SOCKET}" ]]; then
    mysql_args+=(--socket="$DB_SOCKET")
else
    mysql_args+=(--host="${DB_HOST:-127.0.0.1}" --port="${DB_PORT:-3306}")
fi

MYSQL_PWD="$DB_PASSWORD" mysqldump "${mysql_args[@]}" "$DB_DATABASE" | gzip -c > "$archive"

if [[ -n "${BACKUP_S3_URI:-}" ]]; then
    aws s3 cp "$archive" "${BACKUP_S3_URI%/}/$(basename "$archive")"
fi

find "$BACKUP_DIR" -type f -name 'getbite-db-*.sql.gz' -mtime +"$BACKUP_KEEP_DAYS" -delete

echo "Database backup written: $archive"
