#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WP_PATH="${WP2026_WORDPRESS_DIR:-${WP_PATH:-/srv/storage/services/wordpress 2026/wordpress}}"
PROJECT_STATE_DIR="${WP2026_PROJECT_STATE_DIR:-/srv/storage/services/wordpress 2026/.wp-gutenberg-designer}"

if [[ ! -d "${WP_PATH}/wp-content" ]]; then
  echo "WordPress wp-content directory not found at: ${WP_PATH}/wp-content" >&2
  echo "Set WP2026_WORDPRESS_DIR or WP_PATH to the mounted 8093 WordPress root." >&2
  exit 1
fi

sync_runtime_dir() {
  local source_dir="$1"
  local target_dir="$2"

  if [[ ! -d "${source_dir}" ]]; then
    echo "Source directory missing: ${source_dir}" >&2
    exit 1
  fi

  mkdir -p "$(dirname "${target_dir}")"
  rsync -a --delete "${source_dir}/" "${target_dir}/"
}

sync_runtime_dir "${ROOT_DIR}/wp-content/themes/wordpress-2026" "${WP_PATH}/wp-content/themes/wordpress-2026"
sync_runtime_dir "${ROOT_DIR}/wp-content/plugins/lmhg-site-core" "${WP_PATH}/wp-content/plugins/lmhg-site-core"

if [[ -d "${ROOT_DIR}/.wp-gutenberg-designer" ]]; then
  sync_runtime_dir "${ROOT_DIR}/.wp-gutenberg-designer" "${PROJECT_STATE_DIR}"
fi

echo "Synced WordPress 2026 runtime files from repo:"
echo "- Theme: ${WP_PATH}/wp-content/themes/wordpress-2026"
echo "- Plugin: ${WP_PATH}/wp-content/plugins/lmhg-site-core"
echo "- Project state: ${PROJECT_STATE_DIR}"
