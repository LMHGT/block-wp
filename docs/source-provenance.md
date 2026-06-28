# Source Provenance

Captured: 2026-06-27T16:21:46Z

This file records the external state used to start the LMHG Block WP proof
track. Treat it as provenance for import/export planning, not as permission to
modify the source Astro repo.

## Target Repository

- Path: `/Users/tyler-lcsw/projects/lmhg-blockwp`
- Remote: `https://github.com/LMHGT/block-wp.git`
- Branch: `main`
- Baseline commit: `4974be6e8acf3c490ee57a5e47cf119054cf67dd`

## Observed Source Repository

- Path: `/Users/tyler-lcsw/projects/lmhg-astro-integrate`
- Branch: `codex/homepage-alignment-polish`
- Local `HEAD`: `63d7ed9e58eb34f2fd730561ca0e0514a32ebcb0`
- `origin/staging`: `63d7ed9e58eb34f2fd730561ca0e0514a32ebcb0`
- Working tree note: untracked `company-kb/` exists and is unrelated to this
  WordPress proof track.

## Plugin And Skill Context

- LMHG plugin: `lmhg@personal`
- Status: installed, enabled
- Version: `0.1.0+codex.20260627150604`
- Source path: `/Users/tyler-lcsw/plugins/lmhg`

The active LMHG routing surface is the packaged plugin source and installed
plugin cache, not loose copies under `~/.codex/skills`.

## Health Checks

### LMHG Content Design

Command:

```bash
cd /Users/tyler-lcsw/projects/lmhg-astro-integrate
node /Users/tyler-lcsw/plugins/lmhg/skills/lmhg-content-design/scripts/health.mjs
```

Observed result:

```text
LMHG Content Design health: check warnings
- Repo: /Users/tyler-lcsw/projects/lmhg-astro-integrate
- NocoBase dataset: present (/Users/tyler-lcsw/projects/lmhg-astro-integrate/var/nocobase-sync/dataset.json)
- Dataset counts: 55 pages, 2518 input needs
- Astro preview command: PUBLIC_CONTENT_SOURCE=nocobase-draft npm run dev -- --host 127.0.0.1 --port 4322
- Tailscale Serve: available
- Open Design: not healthy
- Open Design CLI: /Users/tyler-lcsw/projects/open-design/apps/daemon/dist/cli.js
```

Interpretation: NocoBase and Astro source context are available. Open Design is
not required for the first WordPress scaffold slice.

### LMHG Launch Workbench

Command:

```bash
cd /Users/tyler-lcsw/projects/lmhg-astro-integrate
node /Users/tyler-lcsw/plugins/lmhg/skills/lmhg-launch-workbench/scripts/workbench-health.mjs
```

Observed result:

```text
LMHG Launch Workbench health: ok
- LMHG repo: directory (/Users/tyler-lcsw/projects/lmhg-astro-integrate)
- Workbench repo: directory (/Users/tyler-lcsw/projects/seo-dashboard)
- NocoBase dataset: file (/Users/tyler-lcsw/projects/lmhg-astro-integrate/var/nocobase-sync/dataset.json)
- Workbench app: http://127.0.0.1:5173/
- Workbench API: reachable (http://127.0.0.1:5174/)
- Staging preview: https://staging.website-production-26u.pages.dev/
- Repo scripts: check:yes, build:yes, nocobase:extract:yes, nocobase:validate:yes, validate:yes
- Workbench scripts: dev:lmhg:yes, build:yes, lint:yes, test:yes
```

Interpretation: Workbench parity requirements are in scope for later phases, but
live Workbench write integration is out of scope until rendered-marker parity is
proven in this repository.

## No-Edit Rule For Source Repo

Workers in this repository may inspect `/Users/tyler-lcsw/projects/lmhg-astro-integrate`
for current route, content, SEO, relationship, design, and redirect truth.
They must not edit, stage, commit, push, or publish changes from that repo unless
Tyler explicitly changes the scope.

## Current Proof Surface

The current public-preview source of truth remains Astro `origin/staging` plus
Cloudflare Pages staging:

- `https://staging.website-production-26u.pages.dev/`

WordPress output in this repository targets a Codex-managed cloud WordPress
runtime until a later cutover gate explicitly changes that status. Local
WordPress, local Docker, Tailscale Serve, and RackNerd are not accepted proof
surfaces for the corrected transition workflow.
