# Codex WordPress Workflow

Use the project-local WordPress Agent Skills before changing WordPress code.
Start with `.codex/skills/wordpress-router/SKILL.md`, then follow the routed
skill for block themes, plugins, WP-CLI, Playground, Blueprints, or performance
work.

## LMHG Boundaries

- Work in `/Users/tyler-lcsw/projects/lmhg-blockwp`.
- Treat `/Users/tyler-lcsw/projects/lmhg-astro-integrate` as read-only source
  context unless Tyler explicitly changes the scope.
- This repository is the WordPress 2026 source-of-truth track for the runtime
  currently served at `http://100.70.222.25:8093`. It is not the approved
  production replacement for Astro/NocoBase/Workbench until user review is
  complete.
- Runtime target for this project is the Dell-hosted non-Docker WordPress
  Playground instance at `/srv/storage/services/wordpress 2026/wordpress`.
- RackNerd, local WordPress, local Docker, and the retired Codex cloud package
  workflow are not accepted proof surfaces for this corrected workflow.
- Do not publish WordPress, change DNS, change Cloudflare Pages, or push Astro
  branches from this workflow.

## Development Rules

- Keep theme changes inside `wp-content/themes/wordpress-2026`.
- Keep durable SEO, schema, redirects, graph behavior, and business logic inside
  `wp-content/plugins/lmhg-site-core`.
- Prefer `theme.json`, templates, template parts, and patterns before adding
  broad CSS.
- Use WordPress APIs for scripts, styles, images, escaping, nonces, and
  capabilities.
- Do not add a page builder or heavy framework unless Tyler explicitly asks.
- Keep public frontend HTML cache-friendly: no unnecessary cookies, random
  nonces, or per-user anonymous markup.
- Preserve LMHG's rendered-marker editing model in future phases. Standard-mode
  editor fields must come from rendered preview markers, not raw database
  inventory.

## Common Commands

```bash
npm run check:static
npm run extract:astro-reference
npm run runtime:verify
npm run verify
```

Dell runtime commands:

```bash
WP2026_WORDPRESS_DIR="/srv/storage/services/wordpress 2026/wordpress" npm run runtime:sync
WP2026_WORDPRESS_DIR="/srv/storage/services/wordpress 2026/wordpress" npm run runtime:verify
```

## Verification Before Completion

For file-only changes, run:

```bash
npm run verify
```

For Dell WordPress runtime changes, sync the repo-owned runtime files into the
mounted 8093 WordPress root, then run:

```bash
WP2026_WORDPRESS_DIR="/srv/storage/services/wordpress 2026/wordpress" npm run runtime:sync
WP2026_WORDPRESS_DIR="/srv/storage/services/wordpress 2026/wordpress" npm run runtime:verify
```

If Node, npm, network access to `http://100.70.222.25:8093`, or the mounted
WordPress root is missing, report the exact failing prerequisite or runtime URL
instead of claiming runtime verification.
