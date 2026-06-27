# LMHG Cloudflare Staging Snapshot

Date: 2026-06-27T19:31:32.165Z

Staging baseline: https://staging.website-production-26u.pages.dev

This snapshot is the first migration-grade baseline for converting the current
Cloudflare staging site into a standalone WordPress site. Bulk HTML, asset, and
screenshot files are written under `artifacts/staging-snapshot/`; compact JSON
indexes are committed under `data/lmhg/staging-snapshot/`.

## Summary

- Manifest routes: 55
- Captured routes: 55
- Discovered non-manifest routes: 0
- Visible `200` routes: 54
- Manifest redirects checked: 117
- Distinct assets captured: 131
- Screenshots captured: 108
- Route status counts: `{"200":54,"308":1}`
- Route classifications: `{"migrate-verbatim":51,"special-404-route":1,"requires-verbatim-decision":3}`
- Redirect status counts: `{"301":117}`
- Asset extension counts: `{"js":2,"css":4,"svg":5,"woff2":1,"webp":119}`
- Asset status counts: `{"200":131}`

## Indexing Suppression

This is still a development/staging migration. WordPress staging must preserve
noindex and discovery suppression until Tyler explicitly approves production
cutover. Parity scripts should verify staging `X-Robots-Tag`, robots meta,
and discovery-file behavior separately from the future production launch switch.

## Decisions Required

- `/compliance/` is marked `out-of-scope` in the current manifest but returns `200` on staging.
- `/privacy-policy/` is marked `out-of-scope` in the current manifest but returns `200` on staging.
- `/terms-of-use/` is marked `out-of-scope` in the current manifest but returns `200` on staging.

## Redirect Status Mismatches

- No redirect status mismatches were found.

## Asset Fetch Issues

- No asset fetch issues were found.

## Generated Files

- `data/lmhg/staging-snapshot/summary.json`
- `data/lmhg/staging-snapshot/routes.json`
- `data/lmhg/staging-snapshot/redirects.json`
- `data/lmhg/staging-snapshot/assets.json`
- `data/lmhg/staging-snapshot/screenshots.json`
- `docs/route-parity-matrix.md`
- `artifacts/staging-snapshot/` (ignored bulk crawl artifacts)
