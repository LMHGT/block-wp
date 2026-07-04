# WordPress 2026 Runtime Verification Workflow

Date: 2026-07-03

This is the active runtime model for the LMHG WordPress 2026 transition. The
older exportable cloud package workflow has been removed from the active repo
surface.

- Runtime source of truth: `http://100.70.222.25:8093` and its associated
  `wordpress-2026` files mirrored into this repo.
- Astro reference source: `/Users/tyler-lcsw/projects/lmhg-astro-integrate`,
  read-only.
- Working repo: `/Users/tyler-lcsw/projects/lmhg-blockwp`.
- Runtime target: Dell-hosted WordPress Playground CLI at
  `/srv/storage/services/wordpress 2026/wordpress`.
- Out of scope: RackNerd, local WordPress, local Docker, and the retired Codex
  cloud package workflow as proof surfaces.
- Staging controls: noindex/noarchive/noimageindex remain active until live use
  is explicitly approved.

## Active 8093 Workflow

Run from the working repo:

```bash
npm run extract:astro-reference
npm run runtime:verify
```

When running on the Dell host, refresh the mounted 8093 runtime from the repo:

```bash
npm run runtime:sync
WP2026_WORDPRESS_DIR="/srv/storage/services/wordpress 2026/wordpress" npm run runtime:verify
```

`runtime:sync` copies:

- `wp-content/themes/wordpress-2026`
- `wp-content/plugins/lmhg-site-core`
- `.wp-gutenberg-designer`

It does not copy WordPress core, `wp-config.php`, the SQLite database, or
`node_modules`.

Astro-derived Core30 documentation and redirect lists are staged here:

- `data/lmhg/astro-reference`
- `docs/astro-reference-intake.md`

Rank Math is not installed yet in the 8093 runtime. Redirect rows are prepared
as candidate inputs only:

- `data/lmhg/astro-reference/redirects/redirects.json`
- `data/lmhg/astro-reference/redirects/rank-math-redirect-candidates.csv`
