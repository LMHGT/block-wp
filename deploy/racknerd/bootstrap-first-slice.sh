#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COMPOSE_FILE="${ROOT_DIR}/deploy/racknerd/compose.first-slice.yml"
ENV_FILE="${ROOT_DIR}/.runtime/racknerd-first-slice.env"

if [[ ! -f "${ENV_FILE}" ]]; then
  echo "Missing ${ENV_FILE}" >&2
  exit 1
fi

set -a
# shellcheck disable=SC1090
source "${ENV_FILE}"
LMHG_REPO_ROOT="${ROOT_DIR}"
set +a

compose() {
  docker compose --env-file "${ENV_FILE}" -f "${COMPOSE_FILE}" "$@"
}

wp() {
  compose run --rm cli "$@" --allow-root
}

compose up -d db wordpress

for _ in $(seq 1 60); do
  if wp core version >/dev/null 2>&1; then
    break
  fi
  sleep 3
done

if ! wp core is-installed >/dev/null 2>&1; then
  wp core install \
    --url="${LMHG_WP_URL}" \
    --title="LMHG Block WP First Slice" \
    --admin_user="${LMHG_WP_ADMIN_USER}" \
    --admin_password="${LMHG_WP_ADMIN_PASSWORD}" \
    --admin_email="${LMHG_WP_ADMIN_EMAIL}" \
    --skip-email
fi

wp option update home "${LMHG_WP_URL}"
wp option update siteurl "${LMHG_WP_URL}"
wp option update blog_public 0
wp rewrite structure '/%postname%/' --hard
wp theme activate lmhg-block-theme
wp plugin activate lmhg-site-core
wp option update lmhg_tailnet_host "${LMHG_TAILNET_HOST}"
wp lmhg import-manifest data/lmhg/source-route-manifest.json
wp lmhg import-block-manifest data/lmhg/block-migration/first-slice-block-manifest.json data/lmhg/block-migration/first-slice-media-manifest.json
wp rewrite flush --hard

echo "First-slice WordPress runtime is ready: ${LMHG_WP_URL}"
