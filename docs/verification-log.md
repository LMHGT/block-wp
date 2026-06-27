# Verification Log

## 2026-06-27 Scaffold And Runtime

### Static And Prerequisite Checks

```bash
npm run verify
```

Result:

```text
Static project checks passed.
OK       Node.js: v25.9.0; required >=20.18.0 for this WordPress proof track
OK       npm: 11.12.1; required >=10.2.3
OK       PHP: PHP 8.5.7 (cli) (built: Jun  2 2026 20:59:56) (NTS)
OK       WP-CLI: available
OK       Docker: daemon available
OK       Docker Compose: Docker Compose version 5.2.0
OK       Composer: Composer version 2.10.1 2026-06-04 10:25:59
OK       Automation browser: Playwright Chromium available
```

Runtime prerequisite notes:

- Homebrew installed `php`, `composer`, `wp-cli`, `docker`, `docker-compose`,
  and `colima`.
- Colima is running with Docker context `colima`.
- `~/.docker/config.json` was backed up to
  `~/.docker/config.json.lmhg-blockwp-backup-20260627T1635Z` before removing the
  stale Docker Desktop credential helper.

### PHP Syntax

```bash
php -l wp-content/themes/lmhg-block-theme/functions.php
php -l wp-content/plugins/lmhg-site-core/lmhg-site-core.php
```

Result:

```text
No syntax errors detected in wp-content/themes/lmhg-block-theme/functions.php
No syntax errors detected in wp-content/plugins/lmhg-site-core/lmhg-site-core.php
```

### WordPress Runtime

```bash
npm run wp-env:start
npm run wp-env:seed
curl -sS -I http://localhost:8888 | sed -n '1,20p'
```

Result:

```text
WordPress development site started at http://localhost:8888
MySQL is listening on port 32768
Success: Switched to 'LMHG Block Theme' theme.
Success: Plugin already activated.
Success: Rewrite structure set.
Success: Rewrite rules flushed.
Success: {"created":0,"updated":52,"skipped":3,"failed":0,"redirects":117}
Seeded LMHG wp-env site content and route manifest.
HTTP/1.1 200 OK
```

Current seed behavior: `npm run wp-env:seed` activates the local theme/plugin,
configures permalinks and tailnet URL behavior, and imports the route manifest.
The latest verified run printed:

```text
Success: Plugin already activated.
Success: Rewrite structure set.
Success: Rewrite rules flushed.
Success: Value passed for 'lmhg_tailnet_host' option is unchanged.
Success: {"created":0,"updated":52,"skipped":3,"failed":0,"redirects":117}
Seeded LMHG wp-env site content and route manifest.
```

### LMHG Route Manifest Import

```bash
npm run wp-env:import:lmhg
npm run wp-env:import:lmhg
npx --no-install wp-env run cli wp post list --post_type=page --meta_key=_lmhg_source_url --format=count
npx --no-install wp-env run cli wp eval 'echo get_post_meta((int) get_option("page_on_front"), "_lmhg_source_url", true);'
npx --no-install wp-env run cli wp eval '$ok = get_page_by_path("faq/cost", OBJECT, "page") instanceof WP_Post; echo $ok ? "faq/cost ok" : "faq/cost missing";'
npx --no-install wp-env run cli wp eval '$q = new WP_Query(array("post_type"=>"page","post_status"=>"any","meta_key"=>"_lmhg_source_url","meta_value"=>"/404.html","posts_per_page"=>20,"orderby"=>"ID","order"=>"ASC")); $rows = array_map(fn($p) => array("ID"=>$p->ID,"post_name"=>$p->post_name,"post_status"=>$p->post_status), $q->posts); echo wp_json_encode($rows);'
```

Result:

```text
Success: {"created":0,"updated":52,"skipped":3,"failed":0,"redirects":117}
Success: {"created":0,"updated":52,"skipped":3,"failed":0,"redirects":117}
52
/
faq/cost ok
[{"ID":8,"post_name":"not-found","post_status":"publish"}]
```

Importer notes:

- The first fixed import created 51 pages, updated the existing front page,
  skipped 3 out-of-scope routes, and failed 0 routes.
- The importer now resolves existing pages by `_lmhg_source_url` before path
  lookup, which keeps `/404.html` idempotent even though WordPress rewrites
  numeric slugs.
- Temporary manifest files are written inside the mounted plugin directory and
  removed after the import command exits.

### LMHG Static And Route Verification

```bash
npm run verify:lmhg
```

Result:

```text
LMHG static verification:
{
  "routes": 55,
  "inScopeRoutes": 52,
  "redirects": 117,
  "sourceOnlyRoutes": 1,
  "canonicalUrls": 53
}
LMHG static verification passed.

LMHG route verification:
{
  "expectedRoutes": 52,
  "importedPages": 52,
  "frontSourceUrl": "/",
  "showOnFront": "page"
}
LMHG route verification passed.

LMHG taxonomy verification:
{
  "expectedRoutes": 52,
  "importedPages": 52,
  "taxonomies": 6
}
LMHG taxonomy verification passed.

LMHG redirect verification:
{
  "baseUrl": "http://localhost:8888",
  "manifestRedirects": 117,
  "uniqueRedirectChecks": 49
}
LMHG redirect verification passed.

LMHG link verification:
{
  "baseUrl": "http://localhost:8888",
  "checkedRoutes": 51,
  "checkedLinks": 730,
  "redirectOnlySources": 117,
  "unsupportedLocationSources": 26,
  "checkedUnsupportedLocationSources": 0
}
LMHG link verification passed.

LMHG action verification:
{
  "baseUrl": "http://localhost:8888",
  "checkedRoutes": 51,
  "checkedPrimaryCtas": 102,
  "checkedPhoneLinks": 51,
  "primaryCtaHref": "https://intakeq.com/new/g91Z8x/bjxuno",
  "phoneHref": "tel:5024161416"
}
LMHG action verification passed.

LMHG head verification:
{
  "baseUrl": "http://localhost:8888",
  "checkedRoutes": 51,
  "checkedSeoTitles": 22,
  "checkedMetaDescriptions": 22,
  "checkedSchemaTypes": 50,
  "checkedFaqSchemaTypes": 1,
  "checkedBreadcrumbLists": 50
}
LMHG head verification passed.

LMHG source copy verification:
{
  "baseUrl": "http://localhost:8888",
  "checkedRoutes": 51,
  "checkedCopyRoutes": 51,
  "checkedSnippets": 204,
  "checkedSourceCards": 38,
  "checkedMarkdownRoutes": 5,
  "checkedReadinessRoutes": 0
}
LMHG source copy verification passed.

LMHG marker verification:
{
  "baseUrl": "http://localhost:8888",
  "checkedRoutes": 51,
  "checkedSummaryMarkers": 50,
  "checkedH1Values": 22,
  "checkedBreadcrumbs": 50,
  "checkedRelatedSections": 49,
  "checkedFaqSections": 1,
  "checkedFaqReadiness": 41
}
LMHG marker verification passed.
```

Current scope: this proves repo manifest shape, imported WordPress route/meta
state, plugin-owned taxonomy registration and term assignment, front-end
redirect responses for effective legacy redirects, canonical paths, populated
source SEO titles/descriptions, rendered internal links that avoid redirect-only
or unsupported service-area URLs, the sitewide IntakeQ primary CTA, active phone
links, JSON-LD schema types and required fields, graph-derived BreadcrumbList
nodes, 204 rendered source-copy snippet checks across all 51 public routes, 38
rendered JSON source cards, five Markdown article routes with heading markers,
one rendered FAQ section with FAQPage schema, rendered graph breadcrumbs,
related links, and
Workbench-style marker presence. It also verifies source H1 values where present
and rejects scaffold/proof-track visible copy. FAQ answer parity remains
incomplete for pages whose source records still contain workbook prompts; those
pages receive hidden readiness markers so prompts are not published as visible
page copy.

Site identity evidence:

```text
blogname: Louisville Mental Health Group
blogdescription: Therapy, case management, and community support in Louisville, Kentucky.
```

Representative rendered head output:

```html
<title>Individual Therapy in Louisville, KY | Counseling | LMHG</title>
<link rel="canonical" href="http://localhost:8888/individual-counseling/" />
<meta name="description" content="Individual therapy in Louisville for anxiety, depression, trauma, stress, grief, relationship strain, life transitions, and one-on-one support." />
<script type="application/ld+json">{"@context":"https://schema.org","@type":"MedicalWebPage",...}</script>
```

Representative rendered marker output:

```html
<h1 data-lmhg-edit-field="page:/individual-counseling/:h1">Individual Therapy in Louisville, KY</h1>
<section data-lmhg-edit-field="page:/individual-counseling/:summary">...</section>
<section class="lmhg-source-copy" data-lmhg-edit-field="page:/individual-counseling/:source-content" data-lmhg-source-content-path="src/data/copy/categories/individual-counseling.json">...</section>
<nav data-lmhg-edit-field="page:/individual-counseling/:breadcrumbs">...</nav>
<section data-lmhg-edit-field="page:/individual-counseling/:related-pages">...</section>
<div hidden data-lmhg-readiness-warning="faq-answer-missing" data-lmhg-faq-count="3" data-lmhg-edit-field="page:/individual-counseling/:faq-readiness"></div>
```

Representative rendered FAQ output:

```html
<section class="lmhg-faq-section" data-lmhg-edit-field="page:/case-management/:faq">
  <details class="lmhg-faq-item" data-lmhg-faq-question="0">
    <summary data-lmhg-edit-field="page:/case-management/:faq[0].question">Is case management the same as therapy?</summary>
    <p data-lmhg-edit-field="page:/case-management/:faq[0].answer">No. Therapy focuses on clinical treatment goals...</p>
  </details>
</section>
```

### Screenshots And Lighthouse

```bash
npm run verify:site
```

Result:

```text
Playwright: 22 passed
Lighthouse:
{
  "performance": 100,
  "accessibility": 100,
  "best-practices": 100,
  "seo": 100
}
```

Playwright now checks the plan's representative route set on desktop and mobile:
home, services, individual counseling, child counseling, play therapy,
community-based services, locations, FAQ, contact, one article page, and team.
Each screenshot check asserts HTTP 200, canonical URL, non-empty main content,
the active IntakeQ CTA, the active phone link, and no visible scaffold/stub copy.

Performance note: the first Lighthouse run scored 76 because the scaffold used
the core Navigation block and default emoji assets. The current theme uses a
static header nav, and `lmhg-site-core` disables frontend emoji assets.

### Tailnet Review Surface

```bash
tailscale serve status --json
curl -sS -I https://mbp.beagle-perch.ts.net/
WP_BASE_URL=https://mbp.beagle-perch.ts.net npm run verify:lmhg-redirects
WP_BASE_URL=https://mbp.beagle-perch.ts.net npm run verify:lmhg-links
WP_BASE_URL=https://mbp.beagle-perch.ts.net npm run verify:lmhg-actions
WP_BASE_URL=https://mbp.beagle-perch.ts.net npm run verify:lmhg-head
WP_BASE_URL=https://mbp.beagle-perch.ts.net npm run verify:lmhg-copy
```

Result:

```text
Tailscale Serve: mbp.beagle-perch.ts.net:443 / -> http://127.0.0.1:8888
HTTP/2 200
LMHG redirect verification passed.
LMHG link verification passed.
LMHG action verification passed.
LMHG head verification passed.
LMHG source copy verification passed.
```

Tailnet evidence confirms the reviewer URL is
`https://mbp.beagle-perch.ts.net/`, not raw localhost, and that canonical,
redirect, action, link, source-copy, and JSON-LD output survive the Tailscale
Serve host replacement.

### Production Audit

```bash
npm audit --omit=dev
```

Result:

```text
found 0 vulnerabilities
```

### Playground Blueprint

```bash
npm run playground:blueprint
```

Result:

```text
Playground Blueprint smoke test timed out. Use wp-env for authoritative local verification on this host.
```

Interpretation: this matches the known handoff caveat for WordPress Playground
WASM file-lock behavior. `wp-env` is the authoritative local runtime on this
host.

### Tailscale Serve

```bash
TAILSCALE_HOST="$(tailscale status --json | jq -r '.Self.DNSName | sub("\\.$"; "")')"
tailscale serve --bg --yes 8888
tailscale serve status
npm run wp-env:seed
npx --no-install wp-env run cli wp option get lmhg_tailnet_host
curl -sS \
  -H "Host: ${TAILSCALE_HOST}" \
  -H "X-Forwarded-Host: ${TAILSCALE_HOST}" \
  -H "X-Forwarded-Proto: https" \
  http://127.0.0.1:8888 \
  | grep -o "https://${TAILSCALE_HOST}\\|http://localhost:8888" \
  | sort \
  | uniq -c
```

Result:

```text
https://mbp.beagle-perch.ts.net (tailnet only)
|-- / proxy http://127.0.0.1:8888

mbp.beagle-perch.ts.net
22 https://mbp.beagle-perch.ts.net
```

Tailnet review URL:

```text
https://mbp.beagle-perch.ts.net
```
