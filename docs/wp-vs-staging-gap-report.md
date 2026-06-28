# WordPress vs Cloudflare Staging Gap Report

Snapshot date: 2026-06-28

WordPress base URL: not run against corrected runtime.

Staging snapshot: `data/lmhg/staging-snapshot/routes.json`

This report is intentionally reset for the corrected operating model. The prior
hosted comparison used an out-of-scope VPS runtime and is not accepted as
current project evidence.

## Active Proof Loop

The current proof loop is:

```bash
npm run inventory:astro
npm run generate:block-full
npm run generate:export-manifest
npm run verify
bash tools/import-codex-cloud-wordpress.sh
CODEX_CLOUD_WP_URL="https://<codex-cloud-wordpress-url>" npm run cloud:verify
```

`npm run cloud:verify` writes the active route-by-route cloud runtime report to:

```text
docs/codex-cloud-runtime-report.md
```

## Current Status

- Comparable staging routes in manifest: 55.
- Full-site block routes generated from read-only Astro source: 55.
- Runtime comparison status: pending Codex-managed cloud WordPress URL.
- Staging controls required: `noindex`/`noarchive` stay active until live use is
  approved.

No no-gap transition claim should be made until the cloud runtime verifier
passes against every generated route and the user completes visual review.
