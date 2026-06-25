#!/usr/bin/env bash
# Bite-POS Forge uploaded-image backup.
# Archives storage/app/public, which contains restaurant product photos on the
# Forge public disk. Set BACKUP_S3_URI to copy the archive to a remote bucket.
set -euo pipefail

APP_ROOT="${APP_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
BACKUP_DIR="${BACKUP_DIR:-$APP_ROOT/storage/app/backups}"
BACKUP_KEEP_DAYS="${BACKUP_KEEP_DAYS:-14}"
PUBLIC_STORAGE_DIR="$APP_ROOT/storage/app/public"

cd "$APP_ROOT"
umask 077
mkdir -p "$BACKUP_DIR"

if [[ -n "${BACKUP_S3_URI:-}" ]] && ! command -v aws >/dev/null 2>&1; then
    echo "aws CLI is required when BACKUP_S3_URI is set." >&2
    exit 1
fi

if [[ ! -d "$PUBLIC_STORAGE_DIR" ]]; then
    echo "Missing storage/app/public; run php artisan storage:link and verify uploads before handoff." >&2
    exit 1
fi

timestamp="$(date +%Y-%m-%d-%H%M%S)"
archive="$BACKUP_DIR/getbite-storage-$timestamp.tgz"

tar czf "$archive" -C "$APP_ROOT" storage/app/public

if [[ -n "${BACKUP_S3_URI:-}" ]]; then
    aws s3 cp "$archive" "${BACKUP_S3_URI%/}/$(basename "$archive")"
fi

find "$BACKUP_DIR" -type f -name 'getbite-storage-*.tgz' -mtime +"$BACKUP_KEEP_DAYS" -delete

echo "Storage backup written: $archive"
