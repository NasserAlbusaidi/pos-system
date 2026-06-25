#!/usr/bin/env bash
# Bite-POS Forge database restore drill.
# Imports a database backup into an explicit throwaway database. It refuses to
# target the configured app database so a drill cannot overwrite production.
set -euo pipefail

APP_ROOT="${APP_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
PHP_BIN="${FORGE_PHP:-php}"
BACKUP_DIR="${BACKUP_DIR:-$APP_ROOT/storage/app/backups}"

archive="${1:-}"

if [[ -z "$archive" ]]; then
    if [[ -d "$BACKUP_DIR" ]]; then
        archive="$(find "$BACKUP_DIR" -maxdepth 1 -type f -name 'getbite-db-*.sql.gz' -print | sort | tail -n 1)"
    fi
fi

if [[ -z "$archive" || ! -f "$archive" ]]; then
    echo "Database backup archive not found. Pass a getbite-db-*.sql.gz path or set BACKUP_DIR." >&2
    exit 1
fi

if ! command -v mysql >/dev/null 2>&1; then
    echo "mysql client is required for database restore drills." >&2
    exit 1
fi

cd "$APP_ROOT"

eval "$("$PHP_BIN" <<'PHP'
<?php

$root = getcwd();

require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$connection = config('database.connections.mysql');

$values = [
    'CONFIG_DB_HOST' => $connection['host'] ?? '',
    'CONFIG_DB_PORT' => $connection['port'] ?? '',
    'CONFIG_DB_SOCKET' => $connection['unix_socket'] ?? '',
    'CONFIG_DB_DATABASE' => $connection['database'] ?? '',
    'CONFIG_DB_USERNAME' => $connection['username'] ?? '',
    'CONFIG_DB_PASSWORD' => $connection['password'] ?? '',
];

foreach ($values as $key => $value) {
    echo $key.'='.escapeshellarg((string) $value).PHP_EOL;
}
PHP
)"

RESTORE_DB_DATABASE="${RESTORE_DB_DATABASE:-}"
RESTORE_DB_USERNAME="${RESTORE_DB_USERNAME:-$CONFIG_DB_USERNAME}"
RESTORE_DB_PASSWORD="${RESTORE_DB_PASSWORD:-$CONFIG_DB_PASSWORD}"
RESTORE_DB_HOST="${RESTORE_DB_HOST:-$CONFIG_DB_HOST}"
RESTORE_DB_PORT="${RESTORE_DB_PORT:-$CONFIG_DB_PORT}"
RESTORE_DB_SOCKET="${RESTORE_DB_SOCKET:-$CONFIG_DB_SOCKET}"

if [[ -z "$CONFIG_DB_DATABASE" ]]; then
    echo "Configured MySQL database name is missing; cannot verify restore target safety." >&2
    exit 1
fi

if [[ -z "$RESTORE_DB_DATABASE" ]]; then
    echo "Set RESTORE_DB_DATABASE to a pre-created throwaway database for the restore drill." >&2
    exit 1
fi

if [[ "$RESTORE_DB_DATABASE" == "$CONFIG_DB_DATABASE" ]]; then
    echo "Refusing to restore into configured app database: $CONFIG_DB_DATABASE" >&2
    exit 1
fi

if [[ -z "$RESTORE_DB_USERNAME" ]]; then
    echo "RESTORE_DB_USERNAME is required." >&2
    exit 1
fi

gzip -t "$archive"

mysql_args=(
    --default-character-set=utf8mb4
    --user="$RESTORE_DB_USERNAME"
)

if [[ -n "$RESTORE_DB_SOCKET" ]]; then
    mysql_args+=(--socket="$RESTORE_DB_SOCKET")
else
    mysql_args+=(--host="${RESTORE_DB_HOST:-127.0.0.1}" --port="${RESTORE_DB_PORT:-3306}")
fi

export MYSQL_PWD="$RESTORE_DB_PASSWORD"
gunzip -c "$archive" | mysql "${mysql_args[@]}" "$RESTORE_DB_DATABASE"

echo "Database backup restore drill passed: $archive"
echo "Restored into throwaway database: $RESTORE_DB_DATABASE"
