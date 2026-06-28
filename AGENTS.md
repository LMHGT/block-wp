# Codex WordPress Workflow

Use the project-local WordPress Agent Skills before changing WordPress code.
Start with `.codex/skills/wordpress-router/SKILL.md`, then follow the routed
skill for block themes, plugins, WP-CLI, Playground, Blueprints, or performance
work.

## LMHG Boundaries

- Work in `/Users/tyler-lcsw/projects/lmhg-blockwp`.
- Treat `/Users/tyler-lcsw/projects/lmhg-astro-integrate` as read-only source
  context unless Tyler explicitly changes the scope.
- This repository is a WordPress transition/proof track. It is not the approved
  production replacement for Astro/NocoBase/Workbench until cloud runtime parity
  and user review are complete.
- Runtime target for this project is a Codex-managed cloud WordPress
  environment.
- RackNerd, local WordPress, local Docker, and local Tailscale Serve are not
  accepted proof surfaces for this corrected workflow.
- Do not publish WordPress, change DNS, change Cloudflare Pages, or push Astro
  branches from this workflow.

## Development Rules

- Keep theme changes inside `wp-content/themes/lmhg-block-theme`.
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
npm run check:prereqs
npm run inventory:astro
npm run generate:block-full
npm run generate:export-manifest
npm run verify
```

Cloud runtime commands:

```bash
bash tools/import-codex-cloud-wordpress.sh
CODEX_CLOUD_WP_URL="https://<codex-cloud-wordpress-url>" npm run cloud:verify
```

## Verification Before Completion

For file-only changes, run:

```bash
npm run verify
```

For cloud WordPress runtime changes, run the cloud import inside the
Codex-managed WordPress runtime, then run:

```bash
CODEX_CLOUD_WP_URL="https://<codex-cloud-wordpress-url>" npm run cloud:verify
```

If Node, npm, browser prerequisites, or the Codex cloud WordPress runtime are
missing, report the exact failing prerequisite or missing runtime URL instead of
claiming runtime verification.
