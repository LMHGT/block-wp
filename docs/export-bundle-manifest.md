# First Slice Export Bundle Manifest

Date: 2026-06-27T23:13:39.357Z

This manifest defines the exportable source package for the first editable
Gutenberg migration slice. It is not a database dump and does not contain
secrets. A cloud WordPress runtime can use this package to install the theme and
plugin, import the route manifest, import the editable block manifest, sideload
the media manifest, and verify the four first-review routes.

## Routes

- `/compliance/`
- `/privacy-policy/`
- `/terms-of-use/`
- `/individual-counseling/`

## Import Commands

```bash
wp lmhg import-manifest data/lmhg/source-route-manifest.json
wp lmhg import-block-manifest data/lmhg/block-migration/first-slice-block-manifest.json data/lmhg/block-migration/first-slice-media-manifest.json
```

## Files

| Path | Bytes | SHA-256 |
|---|---:|---:|
| .gitignore | 165 | 950c1b1af697 |
| AGENTS.md | 2166 | 54d41d18ecd0 |
| data/lmhg/block-migration/first-slice-block-manifest.json | 107206 | ca2b171528df |
| data/lmhg/block-migration/first-slice-media-manifest.json | 2923 | c8c697c0a098 |
| data/lmhg/source-route-manifest.json | 478578 | 0a92853ae590 |
| data/lmhg/staging-snapshot/assets.json | 99094 | 50cdddcb3265 |
| data/lmhg/staging-snapshot/routes.json | 223417 | 1c11650ebeca |
| data/lmhg/staging-snapshot/summary.json | 672 | ae874a447da6 |
| deploy/racknerd/bootstrap-first-slice.sh | 1654 | 4af88f7d3dc5 |
| deploy/racknerd/compose.first-slice.yml | 2035 | f10105341b8a |
| docs/block-migration-slice-report.md | 2424 | b3be84f66bca |
| docs/cloud-verification-workflow.md | 2362 | 7fd4675df2ca |
| docs/full-site-page-confirmation.md | 2468 | 8b973e13d60e |
| docs/hosted-first-slice-verification.md | 2786 | 1aeb0e05e378 |
| docs/racknerd-first-slice-runtime.md | 2227 | 504cb520f3ba |
| docs/route-parity-matrix.md | 9197 | 8931d6358f8f |
| docs/staging-snapshot-report.md | 1802 | cc765bc28147 |
| package-lock.json | 274333 | f1b97ddd9262 |
| package.json | 3228 | e1dc9954d83a |
| plan/2026-06-27-cloud-run-editable-gutenberg-migration-pipeline.md | 21527 | a1767f90e395 |
| tools/generate-block-migration-slice.mjs | 19232 | 30dfdc17e959 |
| tools/generate-export-manifest.mjs | 4246 | 3ba6dc5d5d58 |
| tools/verify-block-migration-slice.mjs | 2730 | 907cb3fd8656 |
| tools/verify-export-manifest.mjs | 1723 | 7033807babff |
| wp-content/plugins/lmhg-site-core/includes/editable-blocks.php | 11589 | 888bfda37bbf |
| wp-content/plugins/lmhg-site-core/includes/importer.php | 12361 | 2584d5916fe5 |
| wp-content/plugins/lmhg-site-core/includes/redirects.php | 3642 | 9df7fe8da586 |
| wp-content/plugins/lmhg-site-core/includes/rendering.php | 18716 | 98039970206a |
| wp-content/plugins/lmhg-site-core/includes/seo.php | 12621 | 336cb33c2005 |
| wp-content/plugins/lmhg-site-core/includes/taxonomies.php | 3707 | 37fae4f59d16 |
| wp-content/plugins/lmhg-site-core/lmhg-site-core.php | 2369 | 9d41632b8671 |
| wp-content/themes/lmhg-block-theme/assets/css/blocks/navigation.css | 293 | 9d7e181b1d2b |
| wp-content/themes/lmhg-block-theme/functions.php | 673 | 4547f2cb879b |
| wp-content/themes/lmhg-block-theme/parts/footer.html | 1052 | e0f66013f509 |
| wp-content/themes/lmhg-block-theme/parts/header.html | 928 | 6cb99977e510 |
| wp-content/themes/lmhg-block-theme/patterns/content-band.php | 1025 | 43eb41405b16 |
| wp-content/themes/lmhg-block-theme/patterns/hero.php | 1189 | 3490fa1dad3e |
| wp-content/themes/lmhg-block-theme/style.css | 2655 | 43a72fda1930 |
| wp-content/themes/lmhg-block-theme/styles/editorial.json | 300 | 8681bed7e397 |
| wp-content/themes/lmhg-block-theme/styles/high-contrast.json | 423 | 56d04be7d87c |
| wp-content/themes/lmhg-block-theme/templates/404.html | 616 | 2574a1cd3457 |
| wp-content/themes/lmhg-block-theme/templates/archive.html | 922 | 89a3d32295ae |
| wp-content/themes/lmhg-block-theme/templates/front-page.html | 352 | 6b2cb671b775 |
| wp-content/themes/lmhg-block-theme/templates/index.html | 993 | 94b072ae469d |
| wp-content/themes/lmhg-block-theme/templates/page.html | 449 | df60f85d026d |
| wp-content/themes/lmhg-block-theme/templates/single.html | 473 | 1e00e82652e3 |
| wp-content/themes/lmhg-block-theme/theme.json | 3805 | e3eb803653bd |
