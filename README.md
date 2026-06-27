# LMHG Block WP

This repository is the parallel WordPress proof track for Louisville Mental
Health Group. It starts from the agentic WordPress environment handoff and
constrains that scaffold to the current LMHG Astro, NocoBase, and Workbench
contracts.

Primary plan:

- [plan/2026-06-27-lmhg-blockwp-refactor-plan.md](plan/2026-06-27-lmhg-blockwp-refactor-plan.md)
- [plan/2026-06-27-lmhg-blockwp-refactor-plan.html](plan/2026-06-27-lmhg-blockwp-refactor-plan.html)

Source handoff:

- [docs/superpowers/plans/2026-06-26-agentic-wordpress-codex-environment-handoff.md](docs/superpowers/plans/2026-06-26-agentic-wordpress-codex-environment-handoff.md)

The existing Astro Integrate worktree is read-only for this track unless Tyler
explicitly authorizes changes there.

## Quick Start

```bash
npm install
npm run setup:browsers
npm run verify
npm run wp-env:start
npm run wp-env:seed
npm run verify:site
```

The default `@wordpress/env` local URL is `http://localhost:8888`.
Give remote reviewers the Tailscale Serve URL, not the localhost URL.

## Runtime Roles

- Authoritative local WordPress runtime: `@wordpress/env`
- Disposable smoke/demo runtime: WordPress Playground CLI
- Theme boundary: `wp-content/themes/lmhg-block-theme`
- Durable behavior boundary: `wp-content/plugins/lmhg-site-core`
- Project-local Codex skills: `.codex/skills`

## Tailscale Serve

```bash
TAILSCALE_HOST="$(tailscale status --json | jq -r '.Self.DNSName | sub("\\.$"; "")')"
sudo tailscale set --operator="$USER" || true
tailscale serve --bg --yes 8888
npm run wp-env:seed
echo "Open from the tailnet: https://${TAILSCALE_HOST}"
```

## Known Caveat

On low-CPU hosts, `npm run playground:blueprint` may hit a WordPress Playground
WASM file-lock timeout. Use `wp-env` plus Playwright/Lighthouse as the
authoritative verification path when that happens.
