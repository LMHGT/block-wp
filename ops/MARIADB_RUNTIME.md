# WordPress 2026 MariaDB staging and cutover

MariaDB is the planned replacement for the current WordPress Playground/SQLite
runtime. It is **not yet authoritative**. Build and validate it in parallel on
the OVH host before any cutover.

- Source checkout: `/srv/codex/projects/lmhg-blockwp`
- Current rollback runtime: `/srv/codex/services/lmhg-blockwp-wordpress`
- MariaDB runtime root: `/srv/codex/services/lmhg-blockwp-wordpress-mariadb`
- Staging endpoint: `http://100.116.130.39:8094`
- Intended cutover endpoint: `http://100.116.130.39:8093`
- WordPress: `wordpress:7.0.2-php8.3-apache`
- Database: `mariadb:10.11.18`, internal to the Compose network

The existing one-worker SQLite service remains the rollback path until the
MariaDB site passes the complete acceptance checklist. Do not run both sites on
port 8093, and do not delete the SQLite database or its verified backups during
this migration.

## Prepare the isolated runtime

Copy the Compose and backup files from this repository into the runtime root as
`compose.yml` and `backup-wordpress-mariadb.sh`. Create these runtime-only
directories with restrictive ownership:

```text
/srv/codex/services/lmhg-blockwp-wordpress-mariadb/
  backups/
  mariadb/
  secrets/
    mariadb.env
    wordpress.env
  wordpress/
    wp-content/
```

The two environment files are secrets. Keep them outside Git, set their mode to
`0600`, and restrict the runtime and backup directories to the deployment user.
`mariadb.env` must define `MARIADB_DATABASE`, `MARIADB_USER`,
`MARIADB_PASSWORD`, and `MARIADB_ROOT_PASSWORD`. `wordpress.env` must define the
matching `WORDPRESS_DB_NAME`, `WORDPRESS_DB_USER`, and `WORDPRESS_DB_PASSWORD`,
plus `WORDPRESS_DB_HOST=db:3306`.

Stage `wp-content` from the recovered runtime so uploads and the licensed Rank
Math Pro files are preserved, then overlay the tracked plugin and theme files
from the current source checkout. Exclude the SQLite `database/` directory,
debug logs, and WordPress upgrade-temporary directories. Do not copy the old
WordPress core or its placeholder `wp-config.php`; the pinned WordPress image
must create a fresh core and environment-backed configuration in the new
runtime root.

## Build and import on port 8094

The checked-in Compose file defaults to the OVH Tailscale address on staging
port 8094. It can be inspected without starting anything:

```bash
export LMHG_MARIADB_RUNTIME_ROOT=/srv/codex/services/lmhg-blockwp-wordpress-mariadb
docker compose -f ops/wordpress-2026-mariadb.compose.yml config
```

Before importing data:

1. Preserve a checksummed SQLite snapshot and a `wp-content`/source archive.
2. Start only the new MariaDB project on port 8094.
3. Bootstrap WordPress 7.0.2 and activate the exact current LMHG Site Core,
   Rank Math Free, and Rank Math Pro versions so their MariaDB tables exist.
4. Generate SQL and its manifest from a verified SQLite snapshot with
   `migrate-wordpress-sqlite-to-mariadb.py`. Never use the corrupt file.
5. Compare every source table and column in the manifest with the bootstrapped
   MariaDB schema. The migration generator creates the Google Review tables,
   but expects WordPress, Action Scheduler, and Rank Math tables to exist.
6. Import once, then compare per-table row counts and critical serialized
   options against the manifest and SQLite source.

The source database already uses the intended `http://100.116.130.39:8093`
canonical URL. Validate staging with an explicit Host/connect mapping so the
canonical options do not need a temporary rewrite.

## Acceptance and cutover

Validate the staging runtime sequentially: Compose health, PHP/WordPress logs,
plugin versions and activation, authenticated administration, all canonical
routes, all 24 FAQ Set assignments, metadata, schema, sitemap membership,
images, redirects, and SQLite-to-MariaDB row-count parity. Run the MariaDB
backup script and verify its checksum manifest before cutover.

Only after acceptance:

1. Stop and disable the one-worker Playground service without deleting data.
2. Confirm port 8093 is free.
3. Change `LMHG_WORDPRESS_BIND` to `100.116.130.39:8093` in the external
   deployment environment and start the accepted Compose project.
4. Repeat the sequential public and administrative acceptance checks.

If a cutover check fails, stop the Compose project and restart the retained
one-worker Playground service against the verified SQLite snapshot. Database-
changing work must always be serialized through one designated coordinator;
read-only audits may run concurrently.

## Operations after acceptance

```bash
cd /srv/codex/services/lmhg-blockwp-wordpress-mariadb
docker compose -f compose.yml ps
docker compose -f compose.yml logs --tail=100 db wordpress
docker compose -f compose.yml --profile tools run --rm cli core version
./backup-wordpress-mariadb.sh
```
