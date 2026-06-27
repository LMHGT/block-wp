# Full Site Page Confirmation

Date: 2026-06-27

Runtime URL:

```text
http://racknerd.beagle-perch.ts.net:8091
```

Verified commit:

```text
8cb7e9b fix: render homepage source h1
```

## Acceptance Standard

The target standard is no gap on any page. That means every route from
`https://staging.website-production-26u.pages.dev/` must have a WordPress
counterpart with matching route behavior, rendered content, visual/assets,
metadata, and editability, with no dependency on the Astro/Cloudflare staging
runtime.

The current runtime does not yet meet that no-gap standard.

## Hosted WordPress Page Smoke Check

The WordPress runtime currently serves all 55 imported manifest pages.

```json
{
  "wp_page_urls_checked": 55,
  "wp_page_issues": 0,
  "source_paths_checked": 55,
  "source_path_non_200": 1,
  "editable_pages": 4,
  "min_body_bytes": 30747,
  "max_body_bytes": 47385,
  "total_staging_refs": 0,
  "total_marker_count": 1493,
  "home_h1": "Mental Health Clinic in Louisville, KY"
}
```

This confirms that all imported WordPress page URLs return `200`, render
non-empty HTML, include noindex controls, include a title and H1, and contain no
direct references to `staging.website-production-26u.pages.dev`.

One source-path behavior remains different:

```text
/404.html status=404 location=
```

The staging snapshot records `/404.html` as a special 308 route. The WordPress
import currently represents that source page at `/not-found/` and the direct
`/404.html` request is handled by WordPress as a 404 response.

## Strict Staging Parity Check

The stricter staging parity checker was run against the hosted WordPress
runtime:

```bash
WP_BASE_URL=http://racknerd.beagle-perch.ts.net:8091 npm run report:wp-vs-staging
```

Result:

```json
{
  "wpBaseUrl": "http://racknerd.beagle-perch.ts.net:8091",
  "comparableRoutes": 54,
  "routesWithIssues": 54,
  "issueCounts": {
    "title mismatch": 32,
    "visible text hash mismatch": 54,
    "h1 mismatch": 27
  }
}
```

The detailed route-by-route gap table is in
`docs/wp-vs-staging-gap-report.md`.

## Current Interpretation

The current WordPress runtime is a served full-route proof plus a four-page
editable Gutenberg slice. It is not a completed no-gap transition.

The next implementation phase must close the strict parity gaps across all
routes, including full visible-copy parity, title/H1 parity, asset parity,
route behavior parity, and block editability beyond the first four routes.
