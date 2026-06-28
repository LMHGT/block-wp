# Worker Checklist

Use this checklist before and during implementation in this repository.

## Boundaries

- [ ] Work in `/Users/tyler-lcsw/projects/lmhg-blockwp`.
- [ ] Keep `/Users/tyler-lcsw/projects/lmhg-astro-integrate` read-only.
- [ ] Do not push or publish Astro `origin/staging`, `main`, or Cloudflare
      changes from this workflow.
- [ ] Do not treat WordPress as the approved production replacement.
- [ ] Use the Codex-managed cloud WordPress environment as the runtime proof
      target.
- [ ] Do not use RackNerd, local WordPress, local Docker, or Tailscale Serve as
      accepted proof surfaces for the corrected workflow.
- [ ] Do not add a page builder or heavy frontend framework.

## Naming

- [ ] Theme slug: `lmhg-block-theme`.
- [ ] Theme name: `LMHG Block Theme`.
- [ ] Theme text domain: `lmhg-block-theme`.
- [ ] Plugin slug: `lmhg-site-core`.
- [ ] Plugin name: `LMHG Site Core`.
- [ ] Plugin text domain: `lmhg-site-core`.

## Scaffold Gate

- [ ] Base Node/npm project files exist.
- [ ] `@wordpress/env` is configured.
- [ ] WordPress Agent Skills are installed under `.codex/skills`.
- [ ] Theme files exist under `wp-content/themes/lmhg-block-theme`.
- [ ] Plugin files exist under `wp-content/plugins/lmhg-site-core`.
- [ ] Playground Blueprint exists under `blueprints/local-dev/blueprint.json`.
- [ ] Static verification scripts exist under `tools/` and `tests/`.
- [ ] `npm run check:static` passes.
- [ ] `npm run check:prereqs` has been run and any missing runtime dependency is
      reported precisely.

## LMHG Parity Gate

Do not claim migration parity until all of these have real evidence:

- [ ] Route inventory parity.
- [ ] Redirect parity.
- [ ] Canonical URL parity.
- [ ] Title, H1, and meta-description parity.
- [ ] JSON-LD and schema parity.
- [ ] Breadcrumb parity from graph data, not route parsing.
- [ ] Related-link parity from graph data.
- [ ] FAQ presence/absence parity.
- [ ] Unsupported city/service-area links remain suppressed.
- [ ] Visible editable content has stable marker identity.
- [ ] Standard-mode field inventory can be derived from rendered markers.
- [ ] Placeholder scaffold copy is absent from migrated LMHG pages.
- [ ] `CODEX_CLOUD_WP_URL=... npm run cloud:verify` passes against every route
      before any no-gap transition claim.

## Commit And Push

- [ ] Commit at clean checkpoints.
- [ ] Push to `origin/main` after each successful checkpoint.
- [ ] Do not commit generated runtime artifacts such as `node_modules/`,
      `.wp-env/`, Lighthouse reports, or Playwright screenshots.
