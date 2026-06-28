# LMHG Block WP

This repository is the exportable WordPress transition track for Louisville
Mental Health Group. It reads the current Astro implementation as source context,
then builds a standalone custom block WordPress theme, companion site-core
plugin, route/content manifests, media manifest, and import/export workflow.

Active operating model:

- Source of truth: `/Users/tyler-lcsw/projects/lmhg-astro-integrate`, read-only.
- Working repo: `/Users/tyler-lcsw/projects/lmhg-blockwp`.
- Runtime target: Codex-managed cloud WordPress environment.
- Out of scope for this project: RackNerd, local WordPress, and local Docker as
  proof surfaces.
- Staging controls stay `noindex`/`noarchive` until live use is approved.

Primary plan:

- [plan/2026-06-27-lmhg-blockwp-refactor-plan.md](plan/2026-06-27-lmhg-blockwp-refactor-plan.md)
- [plan/2026-06-27-lmhg-blockwp-refactor-plan.html](plan/2026-06-27-lmhg-blockwp-refactor-plan.html)

Corrected cloud workflow:

- [docs/cloud-verification-workflow.md](docs/cloud-verification-workflow.md)

Cutover boundary:

- [docs/staging-cutover-decision.md](docs/staging-cutover-decision.md)

## Quick Start

```bash
npm install
npm run setup:browsers
npm run inventory:astro
npm run generate:block-full
npm run generate:export-manifest
npm run verify
```

`npm run verify` is intentionally non-runtime. It confirms the source-driven
full-site manifests, static repo shape, prerequisite availability for local
artifact generation, Cloudflare staging snapshot integrity, and export manifest
hashes.

## Cloud WordPress Runtime

After the package is available inside the Codex-managed cloud WordPress runtime,
run the import from this repo. If the WordPress core root is not the repo root,
set `WP_PATH`:

```bash
WP_PATH="/path/to/wordpress" bash tools/import-codex-cloud-wordpress.sh
```

The import script copies `lmhg-block-theme` and `lmhg-site-core` into the target
WordPress runtime, activates them, imports the route manifest, imports all
generated Gutenberg block content and media, keeps development indexing disabled,
flushes rewrites, and exports runtime artifacts:

- WXR content export: `data/lmhg/export/runtime/lmhg-pages.xml`
- Database export: `data/lmhg/export/runtime/lmhg-wordpress.sql`
- Runtime notes: `data/lmhg/export/runtime/README.md`

Then verify the cloud runtime:

```bash
CODEX_CLOUD_WP_URL="https://<codex-cloud-wordpress-url>" npm run cloud:verify
```

The cloud verifier rejects RackNerd and local URLs.

## Runtime Roles

- Theme boundary: `wp-content/themes/lmhg-block-theme`
- Durable behavior boundary: `wp-content/plugins/lmhg-site-core`
- Full-site block manifest: `data/lmhg/block-migration/full-site-block-manifest.json`
- Media manifest: `data/lmhg/block-migration/full-site-media-manifest.json`
- Export manifest: `data/lmhg/export/codex-cloud-export-manifest.json`
- Project-local WordPress skills: `.codex/skills`

## Optional Local Tooling

The repository still contains legacy `wp-env`, Playground, Lighthouse, and
Playwright helper scripts because they are useful for isolated experiments. They
are not required on this machine and are not accepted as the active proof target
for this transition.
