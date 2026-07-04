# LMHG Block WP

This repository is the source-of-truth track for the Louisville Mental Health
Group WordPress 2026 runtime currently served at `http://100.70.222.25:8093`.
The active runtime is a non-Docker WordPress Playground instance on the Dell
host. Astro is now read-only reference context for selected inputs, not the
active WordPress implementation authority.

Active operating model:

- Runtime source of truth: `http://100.70.222.25:8093` and its associated
  `wordpress-2026` files mirrored into this repo.
- Astro reference source: `/Users/tyler-lcsw/projects/lmhg-astro-integrate`,
  read-only.
- Working repo: `/Users/tyler-lcsw/projects/lmhg-blockwp`.
- Runtime target: Dell-hosted WordPress Playground CLI at
  `/srv/storage/services/wordpress 2026/wordpress`.
- Out of scope for this project: RackNerd, local WordPress, local Docker, and
  the retired Codex cloud package workflow as proof surfaces.
- Staging controls stay `noindex`/`noarchive` until live use is approved.

Runtime workflow:

- [docs/wordpress-2026-source-of-truth.md](docs/wordpress-2026-source-of-truth.md)
- [docs/design.md](docs/design.md)
- [docs/cloud-verification-workflow.md](docs/cloud-verification-workflow.md)
- [docs/astro-reference-intake.md](docs/astro-reference-intake.md)

## Quick Start

```bash
npm install
npm run extract:astro-reference
npm run verify
```

`npm run verify` confirms the current repo shape and compares the mirrored
`wordpress-2026` theme against the live 8093 runtime.

## WordPress 2026 Runtime

The active theme is:

- `wp-content/themes/wordpress-2026`

The active durable behavior plugin is:

- `wp-content/plugins/lmhg-site-core`

The project-local Gutenberg planning state is:

- `.wp-gutenberg-designer`

Astro-derived Core30 and redirect reference inputs are staged under:

- `data/lmhg/astro-reference`
- `docs/astro-reference-intake.md`

To refresh the live Dell runtime from this repo, run on the Dell host or with
`WP2026_WORDPRESS_DIR` pointed at the mounted 8093 WordPress root:

```bash
WP2026_WORDPRESS_DIR="/srv/storage/services/wordpress 2026/wordpress" npm run runtime:sync
WP2026_WORDPRESS_DIR="/srv/storage/services/wordpress 2026/wordpress" npm run runtime:verify
```

`runtime:sync` copies repo-owned theme/plugin/project-state files into the
mounted runtime. It does not copy WordPress core, `wp-config.php`, the SQLite
database, or `node_modules`.

## Runtime Roles

- Active theme boundary: `wp-content/themes/wordpress-2026`
- Durable behavior boundary: `wp-content/plugins/lmhg-site-core`
- Gutenberg project state: `.wp-gutenberg-designer`
- Astro reference intake: `data/lmhg/astro-reference`
- Project-local WordPress skills: `.codex/skills`

## Astro Reference Boundary

The only approved Astro inputs in this repo are the Core30 documentation and
redirect lists extracted into `data/lmhg/astro-reference`. Rank Math is not
installed in the 8093 runtime yet, so the redirect CSV is a candidate import
resource only.
