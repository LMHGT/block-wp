# LMHG Block WordPress project authority

Status: accepted working contract
Last reconciled: 2026-07-22

This document explains which project surface answers each kind of question. It
supplements `AGENTS.md`; it does not replace that file or relax its validation
requirements.

## Authority order

1. `AGENTS.md` and the current `main` branch define working rules and the
   deployable LMHG theme, plugin, operations files, tests, and approved assets.
2. The accepted OVH development database defines current editor-managed
   content and state: Pages, Posts, FAQs, team records, relationships, metadata,
   options, and revisions.
3. `wp-content/themes/wordpress-2026/wp2026-page-data.json` is the tracked
   baseline and migration input. It is not an automatic export of every editor
   change in MariaDB.
4. Reference branches, the `resources` branch, stashes, raw research, and the
   former Astro implementation are historical evidence only. Select useful
   decisions into `main`; never merge one of those surfaces wholesale.

When sources disagree, classify the disagreement before changing anything:

- Executable behavior and durable rules belong in tracked theme or plugin code.
- A deliberate editor change may remain database-managed, but a rule that must
  survive a rebuild also needs a tracked representation.
- A runtime-generated file, secret, backup, licensed plugin, or database is not
  a candidate for Git.
- A historical claim, route, or keyword must be checked against current source
  and the accepted development runtime before reuse.

## Accepted development environment

| Item | Current development authority |
| --- | --- |
| Endpoint | `http://100.116.130.39:8093/` |
| Runtime root | `/srv/codex/services/lmhg-blockwp-wordpress-mariadb` |
| Source checkout | `/srv/codex/projects/lmhg-blockwp` |
| WordPress | `wordpress:7.0.2-php8.3-apache` |
| Database | `mariadb:10.11.18` |
| Active LMHG theme | `wp-content/themes/wordpress-2026` |
| Durable behavior plugin | `wp-content/plugins/lmhg-site-core` |

This is a private/noindex development site. Development publication is allowed
only after proportional validation. The retained Playground/SQLite service is
a rollback artifact, not a source of current content or configuration. See
`ops/MARIADB_RUNTIME.md` for recovery and cutover details.

## Production boundary

Production is a separate environment with separate data, credentials, backups,
release controls, and approval. A successful change or publication on the OVH
development site does not authorize a production deployment. Do not copy
development secrets to production, infer production state from development, or
change production without an explicit production release request.

## Tracked and runtime-owned surfaces

| Surface | Ownership rule |
| --- | --- |
| LMHG theme and Site Core plugin | Track in Git and deploy from verified `main` |
| Shared templates, parts, `theme.json`, and CSS | Track in Git; preserve valid Gutenberg serialization |
| Approved custom images | Track only intentional source and approved derivative assets |
| WordPress core and generated configuration | Runtime-owned; do not commit |
| MariaDB, SQLite files, SQL dumps, and backups | Runtime-owned; do not commit |
| Environment files, certificates, keys, and credentials | Runtime-owned; do not commit |
| Rank Math Pro | Licensed runtime-only plugin; do not commit |
| WordPress-generated caches, upgrade state, and logs | Runtime-owned; do not commit |

The repository `.gitignore` makes this boundary portable. It does not replace
pre-commit review: inspect every staged file before committing.

## Branch and reconciliation policy

- Start reconciliation work from current `main` on a short-lived branch.
- Treat `codex/seo-dataforseo-dashboard-20260705`,
  `reference/wordpress-2026-full`, `resources`, and the stashes as selective
  evidence sources.
- Do not revive a long-lived reference branch or merge an auxiliary branch as a
  unit. Old branches contain retired Dell, Playground, and SQLite assumptions.
- Preserve local-only historical refs until every selected decision has either
  been reconciled or explicitly rejected.
- After validation, land the smallest reviewed change set on `main` and push
  the verified development-site work as required by `AGENTS.md`.

## Migration and publication safety

The Site Core plugin runs versioned migrations during ordinary WordPress
requests. Those migrations can change content, relationships, metadata,
redirects, and options. Treat changes to migration versions, catalogs, and
inputs as database-changing releases even when the PHP diff is small.

Before publication:

1. Verify the intended host, branch, runtime root, and database.
2. Create and verify a fresh MariaDB backup when a request can mutate data.
3. Review migration version changes and their stored completion reports.
4. Synchronize `style.css` into `theme.json.styles.css` after CSS changes.
5. Run PHP syntax checks for changed plugin files and `git diff --check`.
6. Run `npm run test:responsive-h1` and `npm run test:responsive-text` across
   the complete published route inventory.
7. Run authenticated Gutenberg recovery checks after serialization or rendered
   editor-content changes.
8. Verify affected routes, redirects, metadata, schema, sitemaps, images, and
   logs on the development runtime.

Database-changing operations must be serialized through one coordinator.
Read-only audits may run concurrently.
