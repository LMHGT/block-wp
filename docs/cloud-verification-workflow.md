# Codex Cloud WordPress Verification Workflow

Date: 2026-06-28

This is the active runtime model for the LMHG WordPress transition.

- Source of truth: `/Users/tyler-lcsw/projects/lmhg-astro-integrate`, read-only.
- Working repo: `/Users/tyler-lcsw/projects/lmhg-blockwp`.
- Runtime target: Codex-managed cloud WordPress environment.
- Out of scope: RackNerd, local WordPress, and local Docker as proof surfaces.
- Staging controls: noindex/noarchive/noimageindex remain active until live use
  is explicitly approved.

## Build The Exportable Package

Run from the working repo:

```bash
npm install
npm run inventory:astro
npm run generate:block-full
npm run generate:export-manifest
npm run verify
```

The package is source-driven. `tools/generate-full-site-block-migration.mjs`
reads the Astro source files directly through `ASTRO_SOURCE_ROOT`, then writes:

- `data/lmhg/source-route-manifest.json`
- `data/lmhg/block-migration/full-site-block-manifest.json`
- `data/lmhg/block-migration/full-site-media-manifest.json`
- `data/lmhg/export/codex-cloud-export-manifest.json`
- `docs/export-bundle-manifest.md`

## Import In Codex Cloud WordPress

In the Codex-managed cloud WordPress runtime, check out or unpack this repo,
then run the import from this repo. If the WordPress core root is not this repo
root, set `WP_PATH`:

```bash
WP_PATH="/path/to/wordpress" bash tools/import-codex-cloud-wordpress.sh
```

The script runs:

```bash
wp core is-installed
rsync -a --delete wp-content/themes/lmhg-block-theme/ "$WP_PATH/wp-content/themes/lmhg-block-theme/"
rsync -a --delete wp-content/plugins/lmhg-site-core/ "$WP_PATH/wp-content/plugins/lmhg-site-core/"
wp option update blog_public 0
wp theme activate lmhg-block-theme
wp plugin activate lmhg-site-core
wp lmhg import-manifest data/lmhg/source-route-manifest.json
wp lmhg import-block-manifest data/lmhg/block-migration/full-site-block-manifest.json data/lmhg/block-migration/full-site-media-manifest.json
wp export --post_type=page --dir=data/lmhg/export/runtime --filename_format=lmhg-pages.xml
wp db export data/lmhg/export/runtime/lmhg-wordpress.sql
```

Set `WP_ALLOW_ROOT=1` only if the cloud runtime requires `wp --allow-root`.

## Verify The Cloud Runtime

After import, run from this repo with the cloud URL:

```bash
CODEX_CLOUD_WP_URL="https://<codex-cloud-wordpress-url>" npm run cloud:verify
```

The verifier rejects RackNerd and local URLs. It checks every full-site route for:

- HTTP 200.
- Expected H1 from the full-site block manifest.
- Source-generated Gutenberg sections.
- Development noindex controls.
- No staging host references.
- No RackNerd references.
- No oversized flattened paragraph blocks.

The report is written to:

```text
docs/codex-cloud-runtime-report.md
```

## Export Artifacts

After cloud import, preserve:

- Theme: `wp-content/themes/lmhg-block-theme`
- Plugin: `wp-content/plugins/lmhg-site-core`
- Media manifest: `data/lmhg/block-migration/full-site-media-manifest.json`
- Block/content manifest: `data/lmhg/block-migration/full-site-block-manifest.json`
- Route/SEO/redirect manifest: `data/lmhg/source-route-manifest.json`
- WXR content export: `data/lmhg/export/runtime/lmhg-pages.xml`
- Database export: `data/lmhg/export/runtime/lmhg-wordpress.sql`
