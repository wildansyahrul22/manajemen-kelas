#!/usr/bin/env bash
# Dijalankan di server hosting oleh GitHub Actions (atau manual: bash bin/deploy.sh).
# Menarik commit terbaru dari GitHub, memasang dependensi production, migrasi, dan cache ulang.
set -euo pipefail

cd "$(dirname "$0")/.."

PHP="${PHP_BIN:-php}"
COMPOSER="${COMPOSER_BIN:-composer}"

echo "==> Deploy dimulai: $(date '+%Y-%m-%d %H:%M:%S')"

# Situs kembali online apa pun hasilnya (berhasil atau gagal di tengah jalan).
trap '"$PHP" artisan up >/dev/null 2>&1 || true' EXIT

"$PHP" artisan down --retry=15 || true

git fetch --prune origin main
git reset --hard origin/main

"$COMPOSER" install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-progress

"$PHP" artisan migrate --force
"$PHP" artisan optimize

echo "==> Deploy selesai pada commit $(git rev-parse --short HEAD)"
