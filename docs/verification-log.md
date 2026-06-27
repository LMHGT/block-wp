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
Seeded LMHG wp-env site content.
HTTP/1.1 200 OK
```

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
