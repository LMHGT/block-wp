# RackNerd First Slice Runtime

Date: 2026-06-27

This document records the private VPS runtime path for the first editable
Gutenberg migration slice. It exists because local WordPress/Docker is not a
required proof surface, Codex Cloud did not expose an environment ID in this
session, and the available GitHub token cannot create workflow files.

## Target

- Host alias: `racknerd`
- Tailnet host: `racknerd.beagle-perch.ts.net`
- Bind model: Docker publishes WordPress to the tailnet IP and a non-default
  port. It does not replace the existing Tailscale Serve proxy on `/`.
- Runtime directory: `/home/codex/lmhg-blockwp-first-slice`

## Setup

The runtime expects a private env file at:

```text
.runtime/racknerd-first-slice.env
```

Required values:

```bash
LMHG_BIND_IP=100.97.208.92
LMHG_BIND_PORT=8091
LMHG_WP_URL=http://racknerd.beagle-perch.ts.net:8091
LMHG_TAILNET_HOST=racknerd.beagle-perch.ts.net
LMHG_DB_NAME=lmhg_blockwp
LMHG_DB_USER=lmhg_blockwp
LMHG_DB_PASSWORD=...
LMHG_DB_ROOT_PASSWORD=...
LMHG_WP_ADMIN_USER=...
LMHG_WP_ADMIN_PASSWORD=...
LMHG_WP_ADMIN_EMAIL=...
```

Then run:

```bash
deploy/racknerd/bootstrap-first-slice.sh
```

The bootstrap script installs WordPress if needed, activates the LMHG block
theme and `lmhg-site-core`, preserves `blog_public=0`, imports all route
manifest pages, imports the first editable block slice, sideloads first-slice
media, and flushes permalinks.

## Review URL

Expected private review URL:

```text
http://racknerd.beagle-perch.ts.net:8091
```

This URL is tailnet-scoped by network address, not a public production URL.
Before treating it as reviewer-ready, verify:

```bash
curl -sSI http://racknerd.beagle-perch.ts.net:8091/ | grep -i 'x-robots-tag'
curl -sS http://racknerd.beagle-perch.ts.net:8091/compliance/ | grep -i 'data-lmhg'
```

## Boundaries

- Do not alter the existing Tailscale Serve mapping for
  `https://racknerd.beagle-perch.ts.net/`.
- Do not bind this proof runtime to `0.0.0.0`.
- Do not store `.runtime/*.env` in Git.
- Do not change DNS, Cloudflare Pages, Astro branches, or production.
