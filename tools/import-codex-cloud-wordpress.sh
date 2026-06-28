#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
EXPORT_DIR="${ROOT_DIR}/data/lmhg/export/runtime"
WP_BIN="${WP_CLI_BIN:-wp}"
WP_PATH="${WP_PATH:-${WORDPRESS_ROOT:-${ROOT_DIR}}}"
WP_CONTENT_PATH="${WP_CONTENT_PATH:-${WP_PATH}/wp-content}"
WP_ARGS=()

if [[ "${WP_ALLOW_ROOT:-0}" == "1" ]]; then
  WP_ARGS+=(--allow-root)
fi
WP_ARGS+=(--path="${WP_PATH}")

wp_cmd() {
  printf '+ %q' "${WP_BIN}"
  printf ' %q' "${WP_ARGS[@]}" "$@"
  printf '\n'
  "${WP_BIN}" "${WP_ARGS[@]}" "$@"
}

sync_runtime_dir() {
  local source_dir="$1"
  local target_dir="$2"
  local source_real=""
  local target_real=""

  source_real="$(cd "${source_dir}" && pwd -P)"
  if [[ -d "${target_dir}" ]]; then
    target_real="$(cd "${target_dir}" && pwd -P)"
  fi

  if [[ "${source_real}" == "${target_real}" ]]; then
    echo "Runtime directory already in place: ${target_dir}"
    return
  fi

  mkdir -p "$(dirname "${target_dir}")"
  if command -v rsync >/dev/null 2>&1; then
    printf '+ rsync -a --delete %q/ %q/\n' "${source_dir}" "${target_dir}"
    rsync -a --delete "${source_dir}/" "${target_dir}/"
  else
    rm -rf "${target_dir:?}"
    mkdir -p "${target_dir}"
    printf '+ cp -R %q/. %q/\n' "${source_dir}" "${target_dir}"
    cp -R "${source_dir}/." "${target_dir}/"
  fi
}

cd "${ROOT_DIR}"
mkdir -p "${EXPORT_DIR}"

wp_cmd core is-installed
sync_runtime_dir "${ROOT_DIR}/wp-content/themes/lmhg-block-theme" "${WP_CONTENT_PATH}/themes/lmhg-block-theme"
sync_runtime_dir "${ROOT_DIR}/wp-content/plugins/lmhg-site-core" "${WP_CONTENT_PATH}/plugins/lmhg-site-core"

wp_cmd option update blog_public 0
wp_cmd option update default_ping_status closed
wp_cmd option update default_comment_status closed
wp_cmd rewrite structure '/%postname%/' --hard
wp_cmd theme activate lmhg-block-theme
wp_cmd plugin activate lmhg-site-core
wp_cmd lmhg import-manifest data/lmhg/source-route-manifest.json
wp_cmd lmhg import-block-manifest data/lmhg/block-migration/full-site-block-manifest.json data/lmhg/block-migration/full-site-media-manifest.json
wp_cmd rewrite flush --hard

wp_cmd export \
  --post_type=page \
  --dir="${EXPORT_DIR}" \
  --filename_format=lmhg-pages.xml

wp_cmd db export "${EXPORT_DIR}/lmhg-wordpress.sql"

cat > "${EXPORT_DIR}/README.md" <<'EOF'
# LMHG WordPress Runtime Export

This directory is generated inside the Codex-managed cloud WordPress runtime
after importing the LMHG source-driven block manifest.

Files:

- `lmhg-pages.xml`: WordPress WXR content export for imported pages.
- `lmhg-wordpress.sql`: Database export from the cloud runtime after import.

Staging controls remain active through WordPress options and the LMHG Site Core
plugin until live use is explicitly approved.
EOF

echo "Codex cloud WordPress import and runtime export complete: ${EXPORT_DIR}"
