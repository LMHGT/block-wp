# Codex Cloud WordPress Export Bundle Manifest

Date: 2026-06-28T16:57:29.765Z

This manifest defines the exportable source package for the full LMHG WordPress
transition. It does not contain secrets. A Codex-managed cloud WordPress runtime
can use this package to install the theme and plugin, import the route manifest,
import the editable full-site block manifest, sideload media, and then export
runtime content/database artifacts.

## Operating Model

- Source of truth: `/Users/tyler-lcsw/projects/lmhg-astro-integrate` (read-only)
- Working repo: `/Users/tyler-lcsw/projects/lmhg-blockwp`
- Runtime target: Codex-managed cloud WordPress environment
- Routes: 55
- Staging controls: noindex/noarchive remain active until live use is approved.

## Import Commands

```bash
WP_PATH="/path/to/wordpress" bash tools/import-codex-cloud-wordpress.sh
wp core is-installed
wp lmhg import-manifest data/lmhg/source-route-manifest.json
wp lmhg import-block-manifest data/lmhg/block-migration/full-site-block-manifest.json data/lmhg/block-migration/full-site-media-manifest.json
wp export --post_type=page --dir=data/lmhg/export/runtime --filename_format=lmhg-pages.xml
wp db export data/lmhg/export/runtime/lmhg-wordpress.sql
```

## Files

| Path | Bytes | SHA-256 |
|---|---:|---:|
| .gitignore | 165 | 950c1b1af697 |
| AGENTS.md | 2560 | 91cb17aee370 |
| data/lmhg/block-migration/full-site-block-manifest.json | 1730490 | 1ac837a07fc6 |
| data/lmhg/block-migration/full-site-media-manifest.json | 59803 | 8c59dc1292a7 |
| data/lmhg/source-route-manifest.json | 478578 | 0a92853ae590 |
| data/lmhg/staging-snapshot/assets.json | 99227 | 06a06632d135 |
| data/lmhg/staging-snapshot/routes.json | 240908 | 91f4056b7bc7 |
| data/lmhg/staging-snapshot/summary.json | 658 | 6d5f64b6848f |
| docs/cloud-verification-workflow.md | 3264 | 475503a187e1 |
| docs/codex-cloud-runtime-report.md | 356 | 9297aee37ee5 |
| docs/full-site-block-migration-report.md | 13799 | 0f907d3c35d0 |
| docs/route-parity-matrix.md | 9211 | 73b9ae6da43e |
| docs/staging-snapshot-report.md | 1794 | 6e33c9c20f15 |
| package-lock.json | 274333 | f1b97ddd9262 |
| package.json | 3224 | 68835d5de109 |
| plan/2026-06-27-cloudflare-staging-to-wordpress-verbatim-migration-plan.md | 24064 | afe578c85192 |
| tools/generate-export-manifest.mjs | 4884 | 9c346fe956e6 |
| tools/generate-full-site-block-migration.mjs | 35918 | ad2e2506d3af |
| tools/import-codex-cloud-wordpress.sh | 2828 | 0fc226465bf2 |
| tools/verify-codex-cloud-runtime.mjs | 5193 | 9a4b531f1cf0 |
| tools/verify-export-manifest.mjs | 2071 | fd20fe8deeed |
| tools/verify-full-site-block-migration.mjs | 4107 | 2477fb824bb1 |
| wp-content/plugins/lmhg-site-core/assets/imported/illustrations-service-areas-bullitt-county-ky-shape-transparent-f868511776a2.svg | 5752 | 3755d9b5f283 |
| wp-content/plugins/lmhg-site-core/assets/imported/illustrations-service-areas-jefferson-county-ky-shape-transparent-5f497cd6e3da.svg | 4014 | fade61fc1c49 |
| wp-content/plugins/lmhg-site-core/assets/imported/illustrations-service-areas-louisville-ky-county-shape-transparent-1b2453a8ae4b.svg | 4010 | cd9c44c49668 |
| wp-content/plugins/lmhg-site-core/assets/imported/illustrations-service-areas-oldham-county-ky-shape-transparent-f467133beaf2.svg | 3290 | 832dce920794 |
| wp-content/plugins/lmhg-site-core/includes/editable-blocks.php | 12990 | 344d09c05afe |
| wp-content/plugins/lmhg-site-core/includes/importer.php | 12504 | 4ebeb7d18ce5 |
| wp-content/plugins/lmhg-site-core/includes/redirects.php | 4928 | 1aba8ee5b4fb |
| wp-content/plugins/lmhg-site-core/includes/rendering.php | 19885 | 88414abeb2f5 |
| wp-content/plugins/lmhg-site-core/includes/seo.php | 12621 | 336cb33c2005 |
| wp-content/plugins/lmhg-site-core/includes/taxonomies.php | 3707 | 37fae4f59d16 |
| wp-content/plugins/lmhg-site-core/lmhg-site-core.php | 2369 | 9d41632b8671 |
| wp-content/themes/lmhg-block-theme/assets/css/blocks/navigation.css | 293 | 9d7e181b1d2b |
| wp-content/themes/lmhg-block-theme/functions.php | 857 | 1c9ed4b1a660 |
| wp-content/themes/lmhg-block-theme/parts/footer.html | 1052 | e0f66013f509 |
| wp-content/themes/lmhg-block-theme/parts/header.html | 1019 | 75b3df0aca48 |
| wp-content/themes/lmhg-block-theme/patterns/content-band.php | 1025 | 43eb41405b16 |
| wp-content/themes/lmhg-block-theme/patterns/hero.php | 1189 | 3490fa1dad3e |
| wp-content/themes/lmhg-block-theme/style.css | 10576 | 72de2bb9328f |
| wp-content/themes/lmhg-block-theme/styles/editorial.json | 300 | 8681bed7e397 |
| wp-content/themes/lmhg-block-theme/styles/high-contrast.json | 423 | 56d04be7d87c |
| wp-content/themes/lmhg-block-theme/templates/404.html | 616 | 2574a1cd3457 |
| wp-content/themes/lmhg-block-theme/templates/archive.html | 922 | 89a3d32295ae |
| wp-content/themes/lmhg-block-theme/templates/front-page.html | 413 | 92a9dd79ae6e |
| wp-content/themes/lmhg-block-theme/templates/index.html | 993 | 94b072ae469d |
| wp-content/themes/lmhg-block-theme/templates/page.html | 490 | 00d7bd587dfc |
| wp-content/themes/lmhg-block-theme/templates/single.html | 473 | 1e00e82652e3 |
| wp-content/themes/lmhg-block-theme/theme.json | 3805 | e3eb803653bd |
