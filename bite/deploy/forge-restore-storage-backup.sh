#!/usr/bin/env bash
# Bite-POS Forge uploaded-image restore drill.
# Extracts a storage backup into a separate directory so the operator can prove
# product photos are restorable without overwriting live uploads.
set -euo pipefail

APP_ROOT="${APP_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
BACKUP_DIR="${BACKUP_DIR:-$APP_ROOT/storage/app/backups}"
RESTORE_DRILL_DIR="${RESTORE_DRILL_DIR:-$APP_ROOT/storage/app/restore-drills/storage-$(date +%Y-%m-%d-%H%M%S)}"

archive="${1:-}"

if [[ -z "$archive" ]]; then
    if [[ -d "$BACKUP_DIR" ]]; then
        archive="$(find "$BACKUP_DIR" -maxdepth 1 -type f -name 'getbite-storage-*.tgz' -print | sort | tail -n 1)"
    fi
fi

if [[ -z "$archive" || ! -f "$archive" ]]; then
    echo "Storage backup archive not found. Pass a getbite-storage-*.tgz path or set BACKUP_DIR." >&2
    exit 1
fi

if [[ -e "$RESTORE_DRILL_DIR" ]]; then
    echo "Restore drill directory already exists: $RESTORE_DRILL_DIR" >&2
    exit 1
fi

if ! tar tzf "$archive" | grep -q '^storage/app/public'; then
    echo "Archive does not contain storage/app/public." >&2
    exit 1
fi

umask 077
mkdir -p "$RESTORE_DRILL_DIR"
tar xzf "$archive" -C "$RESTORE_DRILL_DIR"

if [[ ! -d "$RESTORE_DRILL_DIR/storage/app/public" ]]; then
    echo "Restore drill failed: storage/app/public was not extracted." >&2
    exit 1
fi

file_count="$(find "$RESTORE_DRILL_DIR/storage/app/public" -type f | wc -l | tr -d '[:space:]')"

echo "Storage backup restore drill passed: $archive"
echo "Restored to: $RESTORE_DRILL_DIR"
echo "Restored files: $file_count"
