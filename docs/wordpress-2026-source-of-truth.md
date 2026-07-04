# WordPress 2026 Source Of Truth

Date: 2026-07-03

The active WordPress project for this repository is the runtime currently
responding at:

```text
http://100.70.222.25:8093
```

## Runtime Authority

- Host alias: `dell-4229`
- Runtime root: `/srv/storage/services/wordpress 2026`
- Mounted WordPress root: `/srv/storage/services/wordpress 2026/wordpress`
- Runtime transport: WordPress Playground CLI
- Active theme slug: `wordpress-2026`
- Active plugin slug: `lmhg-site-core`

The runtime remains private/noindex until live use is explicitly approved.

## Repo-Owned Runtime Files

The runtime-owned files mirrored into this repo are:

- `.wp-gutenberg-designer`
- `runtime/wordpress-2026`
- `wp-content/themes/wordpress-2026`
- `wp-content/plugins/lmhg-site-core`

The repo intentionally does not track WordPress core, `wp-config.php`, the
Playground SQLite database, `node_modules`, or generated runtime logs.

## Astro Reference Inputs

Astro is read-only reference context. The approved inputs for this phase are:

- Core30 documentation and keyword architecture
- Redirect inventory for later Rank Math import planning

Generated artifacts:

- `data/lmhg/astro-reference/summary.json`
- `data/lmhg/astro-reference/core30/`
- `data/lmhg/astro-reference/redirects/`
- `docs/astro-reference-intake.md`

Rank Math is not installed in the 8093 runtime yet, so redirect rows remain
candidate inputs only.

## Verification

Run:

```bash
npm run extract:astro-reference
npm run runtime:verify
npm run verify
```

When running on the Dell host, refresh the live runtime from the repo with:

```bash
WP2026_WORDPRESS_DIR="/srv/storage/services/wordpress 2026/wordpress" npm run runtime:sync
WP2026_WORDPRESS_DIR="/srv/storage/services/wordpress 2026/wordpress" npm run runtime:verify
```
