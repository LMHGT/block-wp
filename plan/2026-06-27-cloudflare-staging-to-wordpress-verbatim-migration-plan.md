# Cloudflare Staging To Standalone WordPress Verbatim Migration Plan

Date: 2026-06-27

Target repo: `/Users/tyler-lcsw/projects/lmhg-blockwp`

Target GitHub repo: `LMHGT/block-wp`

Current public render baseline:
`https://staging.website-production-26u.pages.dev/`

LMHG source/reference layer: `lmhg@personal` plus
`/Users/tyler-lcsw/projects/lmhg-astro-integrate`

## Corrected Objective

The target is not a generic WordPress proof track.

The target is a standalone WordPress site that can replace the current
Cloudflare Pages staging site only after it proves verbatim route, content,
layout, asset, metadata, schema, redirect, and editing-parity behavior.

The WordPress site should use a minimal block-based theme, either a tightly
controlled starter such as D3 Lite if it is confirmed suitable, or the existing
`lmhg-block-theme` if it remains the safer minimal base. The theme must be fast,
exportable, and independent of the Astro runtime.

## Current Finding

The existing `LMHGT/block-wp` repo has useful infrastructure but should be
treated as a scaffold, not as the completed migration.

Already useful:

- Local `wp-env` runtime.
- Minimal LMHG-owned block theme scaffold.
- `lmhg-site-core` plugin.
- Route manifest import.
- Plugin-owned custom taxonomies.
- Redirect, SEO, schema, related-link, marker, and browser verification scripts.
- Tailscale review URL for the current local WordPress proof surface.

Not yet sufficient for the corrected objective:

- It renders generated source-content sections, not verbatim staging page bodies.
- It does not port every staging layout, section, class, and asset behavior.
- It intentionally treats legal/protected utility pages as out-of-scope.
- It does not import every live staging asset into an exportable WordPress
  package.
- It does not prove visual or DOM parity against the Cloudflare staging URL.
- It does not provide a final WordPress staging/export package.

## Live Staging Baseline Observed

Sampled on 2026-06-27 from `https://staging.website-production-26u.pages.dev/`.

From the current route manifest cross-check:

- 55 manifest routes total.
- 52 routes marked `needs-copy-model`.
- 3 utility/legal routes marked `out-of-scope` in the proof track, but they
  return `200` on staging and must be handled for a verbatim migration unless
  explicitly excluded.
- 117 redirect rules in the route manifest.
- 50 JSON-backed content routes.
- 5 Markdown-backed article routes.
- Across manifest routes, live staging returned:
  - 51 normal public `200` routes.
  - 3 utility/legal `200` routes.
  - `/404.html` as a `308` special case.
- Across the in-scope live pages sampled from the manifest, 58 distinct
  referenced live assets were found:
  - 5 SVG
  - 4 CSS
  - 2 JS
  - 47 WebP
- The repo-side source asset manifest currently lists 177 assets.
- Staging has `robots.txt`, but `/sitemap.xml`, `/sitemap-index.xml`, and
  `/llms.txt` returned staging 404/noindex responses during this check.

Observed live homepage asset examples:

- `/favicon.svg`
- `/nocobase-preview.css?v=2026-06-14-save-button`
- `/nocobase-preview.js?v=2026-06-14-save-button`
- `/_astro/Layout.CijB05aq.css`
- `/_astro/index@_@astro.CIgYBr7x.css`
- `/_astro/Dialog.astro_astro_type_script_index_0_lang.Cml2qqUS.js`
- `/illustrations/service-categories/*-transparent-320w.webp`

This means the migration must start with a crawler/snapshot phase. The live
staging site has no sitemap endpoint that can be trusted as the complete route
inventory.

## Source Of Truth Rules

Use this order when data disagrees:

1. Cloudflare staging rendered output is the verbatim public-output baseline.
   Text, routes, assets, metadata, schema, layout, links, and redirects must be
   compared against it.
2. `lmhg@personal`, NocoBase exports, LMHG Workbench markers, and the Astro repo
   explain why the output exists and provide current semantic/editing context.
3. `LMHGT/block-wp` is the WordPress implementation target and should not invent
   content or page structures that are absent from staging/source.
4. Rank Math Pro may consume SEO/taxonomy data later, but it should not own the
   LMHG taxonomy model. `lmhg-site-core` should register custom taxonomies.

## Development And Indexing Suppression Policy

This remains a continued development and staging project until Tyler explicitly
approves a live cutover.

All WordPress development, tailnet review, cloud staging, and exportable staging
builds must preserve discovery suppression by default:

- Send `X-Robots-Tag: noindex, nofollow, noarchive, nosnippet, noimageindex` on
  staging/development hosts where server control is available.
- Render `<meta name="robots" content="noindex,nofollow,noarchive,nosnippet,noimageindex">`
  on staging/development hosts.
- Keep staging `robots.txt` restrictive unless a specific verification task
  requires a temporary exception.
- Do not publish sitemap, `llms.txt`, IndexNow, search-console, analytics,
  public discovery feeds, or production canonical host signals from development
  WordPress.
- Do not submit, ping, or advertise the WordPress staging host to search engines
  or third-party discovery tools.
- Keep production-ready sitemap and discovery behavior as a cutover-time switch,
  not a default during migration.

These controls should be verified by the parity scripts separately from the
production launch configuration. The expected production behavior can change
later if this WordPress version is approved to go live.

## Definition Of Verbatim

For this migration, "verbatim" means:

- Every staging route has a WordPress route with the same status behavior,
  canonical destination, title, visible H1, body text, CTA labels/hrefs, links,
  image alt text, metadata, schema, and noindex behavior after host rewriting.
- Every staging redirect has a WordPress redirect with the same source and
  target behavior after host rewriting.
- Every staging-rendered asset is present in the WordPress export or is replaced
  only by an explicitly documented equivalent. No public page may depend on the
  Astro staging host after migration.
- Page screenshots match within an agreed visual threshold at desktop and mobile
  viewports. Differences must be listed by route and selector.
- DOM text content hashes match except for intentional host rewrites, WordPress
  admin tooling, build hashes, and nonce/script-loader details.
- JSON-LD, canonical, robots, Open Graph, and Twitter metadata match after host
  rewriting.
- WordPress block markup is valid, reusable, and exportable.
- The site remains fast without requiring a page builder or runtime Astro
  service.

## Architecture Decision

Use a split WordPress architecture:

### Theme Starter Recommendation

D3 Lite was located in the WordPress.org theme directory, but it should not be
the default base for this migration without a local code audit. The public
listing describes it as a fast, lightweight multipurpose theme with block editor
styles and compatibility with Elementor, Beaver Builder, Brizy, and the block
editor. That is not the same as a true block theme built entirely from block
templates and `theme.json`.

For a verbatim LMHG staging port, the recommended base is the existing custom
`lmhg-block-theme`, tightened into a minimal exportable block theme. It already
has the correct ownership boundary in this repo, avoids inherited page-builder
opinions, and can be shaped around exact staging markup, assets, and verification
requirements.

Use D3 Lite only if a source audit proves it has no unwanted classic-theme,
Customizer, sidebar/widget, or page-builder assumptions that would make exact
LMHG parity harder.

Acceptable alternatives if the custom theme needs a reset:

- Start from a blank custom block theme generated through the WordPress Site
  Editor/Create Block Theme workflow.
- Use a current default WordPress block theme only as a reference for file
  structure and `theme.json`, not as a design source.

### Theme: Minimal LMHG Block Theme

Owns:

- `theme.json` tokens.
- Header and footer template parts.
- Block templates.
- Page-family patterns.
- Stable LMHG CSS ported from the staging visual system.
- Theme-owned asset references when assets are layout/theme concerns.

The theme should stay minimal. If D3 Lite is adopted, it must be evaluated first
for license, block-template structure, `theme.json` footprint, style opinions,
performance, and export behavior. If it adds unnecessary styling or workflow
surface, continue with the existing `lmhg-block-theme` instead.

### Plugin: `lmhg-site-core`

Owns:

- Route import/update commands.
- Custom taxonomies.
- SEO metadata storage and Rank Math compatibility handoff.
- Redirects.
- JSON-LD/schema output.
- Canonical and robots behavior.
- Asset mapping.
- Staging snapshot provenance.
- Verification helpers.
- Optional REST/WP-CLI admin tools for migration operators.

### Content Storage

For migration-grade parity, do not render only source snippets.

The importer must generate full WordPress page content from the captured
staging/Astro source model:

- Core block markup for section layout where possible.
- Custom CSS classes matching the LMHG staging visual vocabulary.
- Pattern references only when they preserve exact output.
- HTML blocks only for isolated sections that cannot be represented safely with
  core blocks.
- Stable source/edit markers in comments or `data-lmhg-edit-field` attributes
  where the Workbench contract requires them.

Articles may start as pages if that is the shortest path to exact route parity,
but the preferred final model is:

- `/articles/` as an article index page.
- Individual articles as posts or a lightweight `lmhg_article` CPT with a
  rewrite rule preserving `/articles/<slug>/`.
- No route change without a redirect parity report.

### Assets

Use a two-tier asset strategy:

1. Theme/plugin assets for structural visual assets that must keep stable paths
   and exact rendering.
2. WordPress media library assets for editor-owned images, with importer-created
   attachment metadata and alt text.

For exact-path parity, the deployment package may also include a webroot-level
public asset mirror for paths such as `/illustrations/...`, `/favicon.svg`, and
other staging paths. This must be documented in the export package because it is
outside normal `wp-content` conventions.

## Migration Work Plan

### Phase 0: Reset Scope And Branch

Goal: prevent the current proof-track closeout from being mistaken for a full
transition.

Tasks:

- Create a new branch such as `codex/verbatim-wordpress-migration-plan`.
- Keep the existing proof-track commits.
- Add this plan as the new migration-grade acceptance target.
- Mark the existing closeout as "proof-track complete, migration parity not
  complete."
- Do not edit the Astro source repo.
- Do not change Cloudflare staging, production, DNS, or Astro branches.

Deliverables:

- This plan.
- Updated status note if implementation begins.

Acceptance:

- `git diff --check`
- No runtime or source-site changes.

### Phase 1: Staging Snapshot Crawler

Goal: capture the exact Cloudflare staging output before porting.

Tasks:

- Build `tools/crawl-staging-snapshot.mjs`.
- Use route sources from:
  - current route manifest,
  - live staging anchor discovery,
  - redirect inventory,
  - LMHG Workbench/source data where routes are present but not linked.
- Fetch every route from `https://staging.website-production-26u.pages.dev/`.
- Capture per route:
  - status,
  - redirects,
  - final URL,
  - headers,
  - title,
  - meta description,
  - canonical,
  - robots/noindex,
  - Open Graph/Twitter tags,
  - JSON-LD blocks,
  - H1 and heading outline,
  - visible text digest,
  - link list,
  - image list,
  - script/style asset list,
  - editable markers,
  - screenshot paths.
- Download every referenced asset and record:
  - source URL,
  - content hash,
  - MIME type,
  - byte size,
  - image dimensions,
  - routes that reference it,
  - local target path.
- Store snapshots under `data/lmhg/staging-snapshot/`.

Deliverables:

- `data/lmhg/staging-snapshot/routes.json`
- `data/lmhg/staging-snapshot/assets.json`
- `data/lmhg/staging-snapshot/redirects.json`
- `data/lmhg/staging-snapshot/html/<route>.html`
- `data/lmhg/staging-snapshot/screenshots/<viewport>/<route>.png`
- `docs/staging-snapshot-report.md`

Acceptance:

- Every known manifest route is classified as `captured`, `redirect`, `404`,
  `excluded-with-reason`, or `blocked`.
- Every referenced asset either downloads successfully or has a documented
  external reason.
- Snapshot run is deterministic enough to compare hashes in future runs.

### Phase 2: LMHG Source Cross-Check

Goal: reconcile live staging with the current LMHG semantic source layer.

Tasks:

- Run LMHG content-design health and Workbench health.
- Read the Astro topology authority before deployment assumptions.
- Compare staging snapshot routes to:
  - `data/lmhg/source-route-manifest.json`,
  - NocoBase dataset route/page records,
  - Workbench rendered markers,
  - Astro source files,
  - asset manifest.
- Create a route parity matrix with columns:
  - staging URL,
  - WordPress target URL,
  - source file,
  - page family,
  - live status,
  - source status,
  - existing WordPress import status,
  - content parity status,
  - asset parity status,
  - visual parity status,
  - decision.
- Reopen utility/legal routes that staging serves with `200`, unless Tyler
  explicitly excludes them.
- Record all disagreements before implementation.

Deliverables:

- `docs/route-parity-matrix.md`
- `data/lmhg/parity/route-parity.json`

Acceptance:

- No route is silently skipped.
- Every discrepancy has an owner: staging wins, LMHG source wins, or explicit
  user decision required.

### Phase 3: WordPress Content Model Upgrade

Goal: move from generated proof content to full page-body migration.

Tasks:

- Decide final WordPress object type per route family:
  - homepage: page/front page,
  - primary hubs: pages,
  - service/category/specialty/context/service-area pages: pages,
  - FAQ details: pages under `/faq/`,
  - articles: posts or `lmhg_article` CPT with `/articles/<slug>/`,
  - legal/support pages: pages unless excluded.
- Extend `lmhg-site-core` meta:
  - `_lmhg_staging_snapshot_hash`,
  - `_lmhg_staging_html_hash`,
  - `_lmhg_visible_text_hash`,
  - `_lmhg_asset_manifest`,
  - `_lmhg_visual_baseline`,
  - `_lmhg_parity_status`.
- Keep plugin-owned custom taxonomies:
  - `lmhg_page_family`,
  - `lmhg_template_family`,
  - `lmhg_faceted_type`,
  - `lmhg_schema_type`,
  - `lmhg_migration_status`,
  - `lmhg_seo_status`.
- Add any needed non-public migration taxonomies or statuses, but do not make
  public archive URLs for them.
- Define Rank Math handoff fields and filters after Rank Math Pro is installed.

Deliverables:

- Updated `docs/content-model.md`.
- Updated importer schema.
- Updated taxonomy verifier.

Acceptance:

- WordPress can represent every staging route without depending on source
  snippets or Astro runtime rendering.

### Phase 4: Asset Port

Goal: make WordPress independent from the Cloudflare staging asset host.

Tasks:

- Download the live asset set from Phase 1.
- Compare against the repo-side 177 asset manifest.
- Copy runtime assets into deterministic targets:
  - `wp-content/themes/lmhg-block-theme/assets/lmhg/...` for theme assets,
  - WordPress uploads for content media,
  - optional webroot mirror for exact public paths.
- Preserve responsive image variants where staging uses them.
- Preserve image dimensions and alt text.
- Preserve `favicon.svg`, Open Graph imagery, and visual asset families.
- Add an asset rewrite/map layer so generated block markup never points to
  `staging.website-production-26u.pages.dev`.

Deliverables:

- `data/lmhg/asset-map.json`
- Downloaded/exportable assets.
- Asset verification script.

Acceptance:

- Every public WordPress page serves assets from the WordPress host or packaged
  local static paths only.
- No page HTML contains the Cloudflare staging hostname.
- Image dimensions and alt text match the staging baseline.

### Phase 5: Block Theme Visual Port

Goal: recreate the staging visual system in a minimal block theme.

Tasks:

- Evaluate D3 Lite against the current `lmhg-block-theme`.
- Choose one starter and document the decision.
- Port LMHG tokens from the staging source:
  - colors,
  - typography,
  - spacing,
  - radii,
  - layout widths,
  - focus states,
  - responsive breakpoints.
- Port staging CSS from hashed Astro assets into stable theme CSS, organized by
  LMHG page family and component role.
- Create or refine patterns for:
  - homepage hero/process/service list/closing CTA,
  - services hub,
  - specialty/service detail,
  - broad category pages,
  - care setting pages,
  - service area pages,
  - FAQ hub/detail,
  - articles,
  - team page,
  - support/legal pages,
  - 404.
- Keep templates block-based, but allow carefully scoped HTML blocks where exact
  markup is required.

Deliverables:

- Updated `theme.json`.
- Updated template parts and templates.
- Page-family patterns.
- Stable LMHG CSS.

Acceptance:

- Representative desktop and mobile screenshots visually match staging within
  the agreed threshold before moving to all-route parity.

### Phase 6: Full Content Importer

Goal: generate real WordPress content from the staging/source baseline.

Tasks:

- Replace proof-track source-summary rendering with generated block markup for
  every page body.
- Generate complete page content from staged DOM plus source semantic fields.
- Preserve:
  - headings,
  - paragraphs,
  - cards,
  - CTAs,
  - FAQ content,
  - related links,
  - image placement,
  - classes needed by the ported CSS,
  - editable markers where appropriate.
- Keep SEO/AIO hidden fields out of visible content.
- Import/update idempotently via WP-CLI.
- Store route parity metadata on each post.

Deliverables:

- Updated `tools/seed-lmhg-wp.mjs`.
- Updated importer includes.
- Generated block content for all routes.

Acceptance:

- Re-running import updates existing posts without duplicate pages.
- WordPress visible text hashes match staging for every route, excluding agreed
  host/build differences.

### Phase 7: SEO, Schema, Redirect, And Rank Math Handoff

Goal: make durable site behavior match staging and prepare optional Rank Math
Pro usage.

Tasks:

- Port canonical URLs with host rewrite.
- Port title, description, robots, Open Graph, Twitter, and JSON-LD.
- Preserve noindex behavior on staging environments.
- Import all 117 redirect rules.
- Add redirect verifier against staging behavior.
- Keep `lmhg-site-core` as the taxonomy owner.
- After Rank Math Pro is installed, configure it to consume registered post
  types/taxonomies and verify:
  - no duplicate title/meta output,
  - no duplicate schema output,
  - sitemap behavior matches the chosen WordPress staging policy,
  - taxonomy archives remain disabled or noindexed unless deliberately exposed.

Deliverables:

- SEO/schema parity verifier.
- Redirect parity report.
- Rank Math setup note.

Acceptance:

- One and only one source emits final SEO/schema tags.
- All redirects match staging or have a documented improvement decision.

### Phase 8: Visual And DOM Parity Verification

Goal: prove exact migration quality.

Tasks:

- Add `npm run crawl:staging`.
- Add `npm run verify:wp-vs-staging`.
- Add `npm run verify:assets`.
- Add `npm run verify:visual-parity`.
- Use Playwright to capture:
  - staging desktop,
  - WordPress desktop,
  - staging mobile,
  - WordPress mobile.
- Compare:
  - status,
  - redirects,
  - title/meta/canonical/schema,
  - H1/headings,
  - visible text hash,
  - links,
  - images/assets,
  - screenshot diffs.
- Produce route-specific failures, not summary-only failures.

Deliverables:

- `docs/parity-report.md`
- `artifacts/parity/<date>/...`

Acceptance:

- 100 percent route classification.
- 100 percent required asset availability.
- Text parity for every non-excluded route.
- Screenshot parity within threshold for representative pages, then all routes.
- Any remaining difference is listed and accepted explicitly.

### Phase 9: Cloud WordPress Staging If Needed

Goal: move beyond local `wp-env` when a real WordPress server is needed.

Preferred cloud staging model:

- Disposable VPS or cloud VM.
- Docker Compose with WordPress, MariaDB, WP-CLI, and a reverse proxy.
- Tailscale access first.
- Optional public noindex staging hostname only after the tailnet version is
  stable.
- GitHub deploy path for theme/plugin.
- WP-CLI import command for content.
- Scheduled database and uploads export.

Why this model:

- It is exportable.
- It avoids managed-host lock-in during migration.
- It lets us run WP-CLI, filesystem asset mirrors, and repeatable imports.
- It can later be packed for managed WordPress if desired.

Export package must include:

- theme zip,
- plugin zip,
- uploads/media archive,
- optional webroot public asset mirror,
- database SQL dump,
- WordPress WXR export,
- `wp-cli.yml`,
- install/import commands,
- parity reports,
- rollback notes.

Acceptance:

- A fresh WordPress install can be rebuilt from the export package.
- The rebuilt install passes the same parity verifiers.

### Phase 10: Human Review And Cutover Readiness

Goal: decide whether WordPress is actually ready to replace staging/production.

Tasks:

- Review representative pages on the WordPress staging URL.
- Review all route parity reports.
- Review visual diffs.
- Review editor workflow:
  - WordPress editor only,
  - Workbench retained as authoring layer,
  - hybrid model.
- Review Rank Math configuration.
- Review backup/restore drill.
- Review rollback plan.
- Only then consider DNS/Cloudflare/production changes.

Acceptance:

- Tyler explicitly approves cutover.
- The current Astro/Cloudflare deployment remains available as rollback until
  WordPress is accepted in production.

## Verification Command Target

The final repo should support at least:

```bash
npm run verify
npm run crawl:staging
npm run wp-env:seed
npm run verify:lmhg
npm run verify:assets
npm run verify:wp-vs-staging
npm run verify:visual-parity
npm run verify:site
npm audit --omit=dev
git diff --check
```

For cloud staging:

```bash
WP_BASE_URL=https://<wordpress-staging-host> npm run verify:wp-vs-staging
WP_BASE_URL=https://<wordpress-staging-host> npm run verify:assets
WP_BASE_URL=https://<wordpress-staging-host> npm run verify:visual-parity
```

## Immediate Next Implementation Slice

Do not start by restyling WordPress pages.

Start with the crawler and parity matrix:

1. Create `tools/crawl-staging-snapshot.mjs`.
2. Capture all known route HTML, headers, redirects, assets, and screenshots.
3. Download assets to an ignored or committed migration snapshot path as
   appropriate.
4. Generate `docs/staging-snapshot-report.md`.
5. Generate `docs/route-parity-matrix.md`.
6. Update `docs/content-model.md` to replace proof-track scope with
   migration-grade content parity requirements.
7. Only then implement the importer/theme work.

This keeps the next implementation anchored to the real Cloudflare staging
surface instead of continuing from an incomplete proof-track assumption.

## Open Decisions For Tyler

These decisions do not block the crawler/parity-matrix slice, but they matter
before cloud staging or cutover:

- Should D3 Lite be evaluated as the theme starter, or should the current
  `lmhg-block-theme` remain the base?
- Should articles become real WordPress posts/CPT records now, or remain pages
  until visual parity is complete?
- Should Workbench remain the long-term editing surface, or should WordPress
  editor become primary after migration?
- Should the three currently out-of-scope legal/utility routes be migrated
  verbatim because staging serves them with `200`?
- Where should the first cloud WordPress staging environment live?
- When Rank Math Pro is available, should `lmhg-site-core` continue emitting
  schema/head tags until Rank Math parity is proven, or should Rank Math be
  introduced earlier behind a verifier?
