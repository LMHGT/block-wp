# WordPress 2026 MariaDB runtime

The authoritative development site at `http://100.70.222.25:8093` runs in the
`wordpress-2026-mariadb` Docker Compose project on `dell-4229`.

- Runtime root: `/srv/storage/services/wordpress-2026-mariadb`
- WordPress: `wordpress:7.0.1-php8.3-apache`
- Database: `mariadb:10.11.18`
- Database port: internal to the Compose network only
- Public binding: Tailscale address `100.70.222.25:8093`
- Rank Math: intentionally absent until the MariaDB prerequisite and adapter
  safeguards are accepted

The retired Playground/SQLite services must remain disabled. Its launcher has a
hard guard that exits unless `WP2026_WORKERS=1`; the offline SQLite database is
retained only as a recovery source.

## Operations

```bash
cd /srv/storage/services/wordpress-2026-mariadb
docker compose -f compose.yml ps
docker compose -f compose.yml logs --tail=100 db wordpress
docker compose -f compose.yml --profile tools run --rm cli core version
```

Create a protected logical database and `wp-content` backup:

```bash
/srv/storage/services/wordpress-2026-mariadb/backup-wordpress-mariadb.sh
```

Database-changing work must be serialized through one designated coordinator.
Read-only audits may run concurrently, but they must not create users, save
posts, activate plugins, run migrations, or issue bulk REST mutations.
