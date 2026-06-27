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
Success: {"created":0,"updated":52,"skipped":3,"failed":0}
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
Success: {"created":0,"updated":52,"skipped":3,"failed":0}
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
Success: {"created":0,"updated":52,"skipped":3,"failed":0}
Success: {"created":0,"updated":52,"skipped":3,"failed":0}
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
```

Current scope: this proves repo manifest shape and imported WordPress route/meta
state. It does not yet prove redirect, canonical tag, schema, breadcrumb,
related-link, FAQ, or Workbench marker parity.

### Screenshots And Lighthouse

```bash
npm run verify:site
```

Result:

```text
Playwright: 4 passed
Lighthouse:
{
  "performance": 100,
  "accessibility": 100,
  "best-practices": 100,
  "seo": 100
}
```

Performance note: the first Lighthouse run scored 76 because the scaffold used
the core Navigation block and default emoji assets. The current theme uses a
static header nav, and `lmhg-site-core` disables frontend emoji assets.

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
