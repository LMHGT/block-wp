#!/usr/bin/env bash
set -euo pipefail

root="${1:-/srv/codex/services/lmhg-blockwp-wordpress-mariadb}"
stamp="$(date -u +%Y%m%dT%H%M%SZ)"
destination="$root/backups/$stamp-mariadb"

test -f "$root/compose.yml"
test -f "$root/secrets/mariadb.env"
test -f "$root/secrets/wordpress.env"
test -d "$root/wordpress/wp-content"

install -d -m 0700 "$destination"
cd "$root"

docker compose -f compose.yml exec -T db sh -c \
  'exec mariadb-dump --single-transaction --routines --triggers --hex-blob -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"' \
  > "$destination/wordpress2026.sql"

tar -C "$root/wordpress" -czf "$destination/wp-content.tar.gz" wp-content
install -m 0600 "$root/secrets/mariadb.env" "$destination/mariadb.env"
install -m 0600 "$root/secrets/wordpress.env" "$destination/wordpress.env"
install -m 0640 "$root/compose.yml" "$destination/compose.yml"
gzip -9 "$destination/wordpress2026.sql"

manifest="$(mktemp)"
find "$destination" -type f ! -name SHA256SUMS -print0 \
  | sort -z \
  | xargs -0 sha256sum > "$manifest"
install -m 0640 "$manifest" "$destination/SHA256SUMS"
rm -f "$manifest"
sha256sum -c "$destination/SHA256SUMS"

printf '%s\n' "$destination"
