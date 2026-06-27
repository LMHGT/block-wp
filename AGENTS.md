# Codex WordPress Workflow

Use the project-local WordPress Agent Skills before changing WordPress code.
Start with `.codex/skills/wordpress-router/SKILL.md`, then follow the routed
skill for block themes, plugins, WP-CLI, Playground, Blueprints, or performance
work.

## LMHG Boundaries

- Work in `/Users/tyler-lcsw/projects/lmhg-blockwp`.
- Treat `/Users/tyler-lcsw/projects/lmhg-astro-integrate` as read-only source
  context unless Tyler explicitly changes the scope.
- This repository is a WordPress proof track. It is not the approved production
  replacement for Astro/NocoBase/Workbench.
- Do not publish WordPress, change DNS, change Cloudflare Pages, or push Astro
  branches from this workflow.
- Keep remote user-facing review URLs on Tailscale Serve, not raw localhost.

## Development Rules

- Keep theme changes inside `wp-content/themes/lmhg-block-theme`.
- Keep durable SEO, schema, redirects, graph behavior, and business logic inside
  `wp-content/plugins/lmhg-site-core`.
- Prefer `theme.json`, templates, template parts, and patterns before adding CSS.
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
npm run wp-env:start
npm run wp-env:seed
npm run wp-env:import:lmhg
npm run verify:lmhg
npm run test:screenshots
npm run test:lighthouse
```

## Verification Before Completion

For file-only changes, run:

```bash
npm run check:static
```

For WordPress runtime changes, run:

```bash
npm run wp-env:start
npm run wp-env:seed
npm run verify:lmhg
npm run test:screenshots
npm run test:lighthouse
```

If Docker, Node, WP-CLI, or browser prerequisites are missing, report the exact
failing prerequisite from `npm run check:prereqs` instead of claiming runtime
verification.
