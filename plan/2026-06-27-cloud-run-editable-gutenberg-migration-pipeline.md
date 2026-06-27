# Cloud Run Editable Gutenberg Migration Pipeline Plan

Date: 2026-06-27

Repo: `/Users/tyler-lcsw/projects/lmhg-blockwp`

Status: implementation plan and checklist. This is not approval to publish,
change DNS, change Cloudflare Pages, push Astro branches, or expose a public
WordPress site.

## Goal

Build a cloud-run staging pipeline that proves LMHG pages can migrate into
editable Gutenberg block content without requiring local WordPress or local
Docker.

The first review slice is:

- `/compliance/`
- `/privacy-policy/`
- `/terms-of-use/`
- `/individual-counseling/`

The pipeline stops at:

- a private review URL,
- route/block/media/SEO/privacy verification reports,
- an exportable theme/plugin/media/content bundle,
- and a written decision record for remaining gaps.

## Non-Goals

- Do not replace the approved Astro/NocoBase/Workbench production path.
- Do not edit `/Users/tyler-lcsw/projects/lmhg-astro-integrate`.
- Do not publish WordPress publicly.
- Do not change DNS, Cloudflare Pages, or Astro branches.
- Do not use local `wp-env`, Docker, or a local WordPress runtime as required
  proof.
- Do not claim legal approval for `/compliance/`, `/privacy-policy/`, or
  `/terms-of-use/`; only prove migrated content parity and editability.
- Do not require Rank Math, SMTP, analytics, search-console, or production
  credentials for the first slice.

## Operating Constraints

- Runtime proof must happen in cloud staging controlled by Codex automation.
- Local work may create code, manifests, and reports, but must not require local
  WordPress or Docker to validate the migration.
- Review URLs must be private: authenticated Cloud Run/IAP access, tailnet-only
  access, or another explicitly private equivalent.
- Staging must preserve noindex/private controls by default.
- Theme changes stay in `wp-content/themes/lmhg-block-theme`.
- Durable SEO, schema, redirect, import, export, media, and correlation behavior
  stays in `wp-content/plugins/lmhg-site-core`.
- Source Astro state is read-only context. Cloudflare staging rendered output is
  the rendered baseline for the migration proof.

## Target Architecture

Use managed cloud runtime components so the proof can run from Codex/cloud
without a local WordPress prerequisite.

- Cloud Run service: private WordPress review runtime.
- Cloud Run jobs: import, verification, export, and cleanup jobs.
- Cloud SQL: MySQL-compatible WordPress database.
- Cloud Storage: uploads, downloaded source media, exported bundles, and
  verification artifacts.
- Artifact Registry and Cloud Build: build container images without local
  Docker.
- Secret Manager: database credentials, WordPress salts, admin bootstrap
  password, and any future private tokens.
- IAM or IAP: private access gate for reviewers and agents.
- Optional tailnet bridge: only if the private review URL needs Tailscale
  reachability instead of a raw Cloud Run URL.

Cloud Run is allowed to be replaced only if the replacement still satisfies the
same contract: no local runtime requirement, private review URL, reproducible
import/export, and verifiable WordPress/Gutenberg behavior.

## Required Durable Artifacts

Planned implementation should create or update these kinds of artifacts:

- `data/lmhg/cloud-run/slice-routes.json`
- `data/lmhg/cloud-run/source-snapshot.json`
- `data/lmhg/cloud-run/block-map.json`
- `data/lmhg/cloud-run/media-map.json`
- `data/lmhg/cloud-run/export-manifest.json`
- `artifacts/cloud-run/<run-id>/verification-summary.json`
- `artifacts/cloud-run/<run-id>/screenshots/`
- `docs/cloud-run-review-report.md`
- `docs/block-correlation-report.md`
- `docs/media-correlation-report.md`
- `docs/private-staging-controls-report.md`
- `docs/export-bundle-manifest.md`

Generated runtime artifacts may stay ignored if they are too large or contain
environment-specific details. Committed summary artifacts must be enough to
reproduce and review the result.

## Source-To-Block Contract

Every migrated page in the first slice must have a route-level source record and
a block-level correlation record.

Each route record should include:

- source route,
- Cloudflare staging final URL,
- WordPress target route,
- source HTML hash,
- visible text hash,
- metadata hash,
- source screenshot references,
- WordPress post ID and slug after import,
- import job ID,
- verification status.

Each block record should include:

- WordPress post ID,
- stable route-local block key,
- block name, such as `core/heading`, `core/paragraph`, `core/group`,
  `core/buttons`, `core/image`, or an approved LMHG block name,
- source selector or source semantic field ID,
- source text or media hash,
- rendered-marker ID when applicable,
- editable field identity when applicable,
- media attachment ID when applicable,
- migration decision: `core-block`, `pattern`, `custom-block`, or
  `html-fallback`.

Preferred block order:

1. Use core Gutenberg blocks for editable text, headings, groups, lists,
   buttons, links, and images.
2. Use theme patterns for repeated LMHG layout groups.
3. Use custom blocks only when a repeated semantic component cannot remain
   stable and editable with core blocks and patterns.
4. Use Custom HTML blocks only for isolated, explicitly documented fallback
   sections.

Acceptance for the first slice: every visible content section is editable in
the block editor and reloads without "invalid block" warnings.

## Media Correlation Contract

Every migrated image or downloadable media reference must resolve to an
exportable WordPress-owned target or an explicitly documented external target.

Each media record should include:

- source URL,
- source route references,
- source content hash,
- MIME type,
- byte size,
- image dimensions when applicable,
- source alt text,
- WordPress attachment ID,
- WordPress attachment URL,
- upload/storage path,
- block references,
- export package path,
- decision: `media-library`, `theme-asset`, `plugin-asset`, `external-allowed`,
  or `excluded-with-reason`.

Acceptance for the first slice:

- No page HTML points to `staging.website-production-26u.pages.dev`.
- Image alt text, dimensions, and placement are checked against staging.
- Editor-owned images live as WordPress media attachments.
- Theme-owned structural assets are packaged with the theme or plugin.
- The export bundle includes all required media or records a deliberate external
  dependency.

## Private Staging And Noindex Controls

Staging must be private at the network/access layer and suppressed at the crawl
layer.

Required controls:

- Cloud Run service is not publicly invokable unless a separate private access
  proxy protects it.
- Reviewer access is granted only to named accounts, groups, or tailnet users.
- WordPress option `blog_public` is set to `0`.
- Every frontend response sends
  `X-Robots-Tag: noindex, nofollow, noarchive, nosnippet, noimageindex`.
- Every frontend document renders a matching robots meta tag.
- `robots.txt` is restrictive.
- Sitemaps, `llms.txt`, IndexNow, analytics, and search-console integrations
  are disabled or blocked for the staging host.
- Canonical and Open Graph host behavior is verified and reported. Any
  production-canonical behavior on private staging must be explicit and noindex.

Stop immediately if an unauthenticated public URL renders the WordPress staging
site.

## Agent Parallelization

Use disjoint work lanes. Each lane writes to owned files or produces artifacts
through a clearly named job output. The coordinator merges only after contracts
pass.

### Lane A: Cloud Runtime

Owns:

- Cloud Build/Artifact Registry setup.
- Cloud Run service/job definitions.
- Cloud SQL and Cloud Storage wiring.
- Secret Manager and IAM access.
- Private review URL creation.
- Runtime health checks.

Outputs:

- deployment runbook,
- private URL,
- runtime health report,
- cleanup procedure.

Stop conditions:

- service requires public unauthenticated access,
- database or uploads cannot persist safely,
- secrets would need to be committed,
- projected cost exceeds the approved staging envelope.

### Lane B: Source Snapshot

Owns:

- first-slice route capture from Cloudflare staging,
- status/header/meta/schema/link/image extraction,
- source HTML and screenshot hashes,
- read-only cross-check against available LMHG source manifests.

Outputs:

- source snapshot JSON,
- route slice matrix,
- baseline screenshots.

Stop conditions:

- route returns unexpected status,
- legal/support copy differs between staging and source without an owner,
- required page content is blocked or incomplete.

### Lane C: Gutenberg Block Compiler

Owns:

- DOM-to-block mapping rules,
- core block serialization,
- pattern/custom-block decisions,
- rendered-marker preservation,
- import idempotency for page content.

Outputs:

- block map,
- generated block content,
- editor reload verification.

Stop conditions:

- generated content creates invalid blocks after save/reload,
- visible content cannot be mapped without unapproved HTML fallback,
- editable marker identity cannot be preserved for visible fields.

### Lane D: Media Import

Owns:

- asset download and hash verification,
- WordPress attachment creation,
- alt text and dimension preservation,
- media path rewriting,
- export media manifest.

Outputs:

- media map,
- attachment verification report,
- packaged media files.

Stop conditions:

- required media cannot be downloaded,
- attachment URLs point back to staging,
- media storage is not exportable.

### Lane E: SEO, Schema, Redirects, And Privacy

Owns:

- title/meta/canonical/robots/Open Graph/Twitter/JSON-LD parity,
- noindex/private controls,
- redirect behavior for first-slice adjacent routes,
- sitemap/discovery suppression.

Outputs:

- head parity report,
- private staging controls report,
- redirect notes.

Stop conditions:

- duplicate or conflicting SEO/schema emitters,
- any staging URL can be crawled publicly,
- sitemap or discovery output appears unexpectedly.

### Lane F: QA And Reports

Owns:

- Playwright checks against private staging,
- editor login and save/reload checks,
- frontend screenshot capture,
- visible text/link/image comparison,
- final review report assembly.

Outputs:

- review report,
- screenshot set,
- verification summary JSON.

Stop conditions:

- private review URL is unavailable to reviewers,
- route verification cannot distinguish source from WordPress output,
- screenshot or text diffs exceed the accepted threshold without explanation.

### Lane G: Export Bundle

Owns:

- theme zip,
- plugin zip,
- media package,
- content export,
- database snapshot or WXR export decision,
- restore/import instructions,
- bundle manifest.

Outputs:

- exportable bundle,
- restore report,
- bundle manifest.

Stop conditions:

- bundle cannot recreate the reviewed staging result,
- export includes secrets,
- export depends on unowned Cloudflare staging assets.

## Implementation Phases

### Phase 0: Boundary And Contract Setup

Tasks:

- Confirm this plan is the active implementation target.
- Record the approved cloud project, billing boundary, private access model, and
  reviewer identities before creating resources.
- Freeze the first route slice as the four routes listed above.
- Define the run ID format and artifact locations.
- Confirm no local WordPress/Docker command is part of the acceptance gate.

Acceptance:

- The route slice and private access model are written down.
- The implementation branch or worktree is identified.
- Resource creation limits and cleanup expectations are recorded.
- No files outside the approved implementation scope are changed.

Stop if:

- private access model is undecided,
- cloud project or billing boundary is not approved,
- implementation would require production credentials.

### Phase 1: Cloud Runtime Foundation

Tasks:

- Build WordPress runtime image through Cloud Build, not local Docker.
- Deploy private Cloud Run WordPress service.
- Deploy Cloud Run jobs for import, verify, export, and cleanup.
- Wire Cloud SQL, Cloud Storage, Secret Manager, and IAM.
- Activate `lmhg-block-theme` and `lmhg-site-core` in staging.
- Bootstrap a named admin/editor account for review.

Acceptance:

- A private WordPress URL returns HTTP `200` only for authorized access.
- Unauthorized access is denied before WordPress renders.
- WordPress admin is reachable for named reviewers only.
- Runtime health report records WordPress version, PHP version, active theme,
  active plugin, database target, upload target, and service revision.
- Proof did not require local Docker or local WordPress.

Stop if:

- Cloud Run cannot keep uploads and database state durable,
- private access cannot be enforced,
- secrets would need to leave Secret Manager or another approved secret store.

### Phase 2: First-Slice Source Snapshot

Tasks:

- Capture Cloudflare staging output for the four first-slice routes.
- Record status, redirects, headers, title, meta, canonical, robots, Open Graph,
  Twitter tags, JSON-LD, H1, heading outline, visible text, links, images,
  scripts, styles, and screenshots.
- Cross-check against existing source manifests and read-only Astro context.
- Record whether staging or source wins for any disagreement.

Acceptance:

- Every first-slice route is classified as `captured`, `redirect`,
  `excluded-with-reason`, or `blocked`.
- Each captured route has HTML, screenshot, metadata, text hash, and media
  reference records.
- Any legal/support copy discrepancy is listed for user review instead of being
  silently resolved.

Stop if:

- a first-slice route cannot be captured,
- staging and source disagree on legal/support copy with no decision owner,
- the capture discovers unexpected PHI or private data.

### Phase 3: Block Mapping And Editable Content Generation

Tasks:

- Map captured DOM sections to Gutenberg blocks.
- Generate valid serialized block content for each first-slice page.
- Use core blocks and patterns before custom blocks.
- Preserve `data-lmhg-edit-field` or equivalent rendered-marker identity for
  visible editable copy.
- Store route-level and block-level correlation metadata.
- Import pages idempotently through a Cloud Run job.

Acceptance:

- Each first-slice route exists as a WordPress page at the expected path.
- The editor shows a navigable block tree, not a single full-page HTML blob.
- Save/reload in the block editor produces no invalid block warnings.
- Frontend visible text matches the staging baseline except documented host or
  WordPress wrapper differences.
- Block map covers every visible content section.

Stop if:

- the page can render only as an uneditable full-page HTML block,
- save/reload invalidates blocks,
- Standard-mode marker identity cannot be represented for visible fields.

### Phase 4: Media Correlation And Import

Tasks:

- Download first-slice media from staging.
- Deduplicate by content hash.
- Import editor-owned images as WordPress attachments.
- Package structural assets with the theme or plugin.
- Rewrite block media references to WordPress-owned URLs.
- Store media provenance metadata on attachments and in the media map.

Acceptance:

- Every first-slice media reference resolves to an attachment, packaged asset,
  allowed external dependency, or documented exclusion.
- No first-slice frontend HTML references the Cloudflare staging host.
- Attachment alt text and image dimensions match staging where available.
- The media map can be joined to the block map by route and block key.

Stop if:

- required media cannot be imported into an exportable store,
- source media identity cannot be correlated back to blocks,
- generated pages depend on staging-hosted images.

### Phase 5: SEO, Schema, Redirect, And Private Controls

Tasks:

- Import first-slice title, description, canonical, robots, Open Graph, Twitter,
  and JSON-LD data.
- Verify noindex headers and robots meta tags on every first-slice route.
- Confirm sitemap and discovery outputs are suppressed.
- Verify adjacent redirects needed to reach or protect first-slice pages.
- Confirm one owner emits SEO/schema output.

Acceptance:

- Each first-slice route has a head parity record.
- Staging noindex/private controls pass for headers, markup, and `robots.txt`.
- There is no sitemap, `llms.txt`, IndexNow, analytics, or search-console
  staging output unless explicitly approved later.
- SEO/schema output is not duplicated by multiple plugins or theme code.

Stop if:

- a route is indexable,
- unauthenticated users can render staging,
- duplicate metadata/schema output appears.

### Phase 6: Cloud Verification And Editor Proof

Tasks:

- Run frontend verification from a cloud job or Codex-controlled cloud worker.
- Run editor login, block tree, save, reload, and frontend smoke checks.
- Capture desktop and mobile screenshots for staging and WordPress outputs.
- Compare visible text, links, images, metadata, schema, and noindex controls.
- Write route-specific reports instead of summary-only pass/fail output.

Acceptance:

- The private review URL is usable by the approved reviewer.
- All four first-slice routes pass route, content, block, media, and private
  staging checks or have explicit route-specific gap entries.
- The report states exactly which checks passed, failed, or were intentionally
  deferred.
- No acceptance evidence depends on local WordPress or Docker.

Stop if:

- verification cannot reach the private URL through the approved access path,
- tests require local `wp-env`,
- failures are only visible in screenshots without machine-readable report
  entries.

### Phase 7: Exportable Bundle

Tasks:

- Build theme and plugin packages from reviewed source.
- Export content for the first slice as WXR and/or database snapshot, with the
  chosen format documented.
- Export media files and media manifest.
- Export block map, media map, route map, SEO/private controls report, and
  restore instructions.
- Run a restore drill in cloud staging or a separate cloud job if feasible.

Acceptance:

- Bundle includes theme, plugin, content, media, manifests, and restore
  instructions.
- Bundle excludes secrets, local machine paths, private credentials, and
  generated transient cache.
- Bundle manifest maps each reviewed route to included content/media/plugin/theme
  assets.
- Restored output can be verified against the same first-slice reports, or the
  restore gap is recorded before review handoff.

Stop if:

- exported bundle cannot recreate the reviewed state,
- export includes credentials or private tokens,
- media/content package is incomplete.

### Phase 8: Review Handoff And Stop

Tasks:

- Produce the private review URL.
- Produce final reports:
  - cloud runtime health,
  - route parity,
  - block correlation,
  - media correlation,
  - SEO/schema/head parity,
  - noindex/private controls,
  - screenshot summary,
  - export bundle manifest.
- List open decisions separately from implementation failures.
- Stop before public publishing or cutover.

Acceptance:

- Reviewer has a private URL and report set.
- The four first-slice routes are independently inspectable in the frontend and
  WordPress editor.
- Export bundle is available and documented.
- Remaining gaps are route-specific and assigned to user decision, source
  mismatch, implementation follow-up, or deferred phase.

Stop if:

- the next action would expose WordPress publicly,
- the next action would change DNS, Cloudflare Pages, Astro staging, or
  production,
- the next action would expand beyond the first route slice without user
  approval.

## Global Acceptance Criteria

- No local WordPress or Docker dependency is required for proof.
- Cloud runtime is private and reproducible from repo-owned automation.
- First-slice pages are editable Gutenberg pages, not opaque rendered snapshots.
- Source-to-block and source-to-media correlation is complete for first-slice
  visible content.
- Noindex/private staging behavior is verified at access, HTTP, HTML, and
  discovery layers.
- First-slice media is WordPress-owned or explicitly documented as an allowed
  external dependency.
- Theme/plugin/content/media export bundle can recreate the reviewed result.
- Final output stops at private review URL and reports.

## Global Stop Conditions

Stop and ask Tyler before continuing if any of these occur:

- a public unauthenticated URL renders the WordPress staging site,
- cloud setup needs production credentials,
- implementation needs edits in the Astro repo,
- implementation needs DNS or Cloudflare Pages changes,
- legal/support page text conflicts with source and staging,
- generated block content is not editable after save/reload,
- source-to-block or media correlation cannot reach full first-slice coverage,
- export bundle cannot exclude secrets,
- scope expands beyond the four first-slice routes,
- runtime cost, hosting model, or private access model differs from the approved
  plan.

## Suggested Agent Order

1. Coordinator opens the implementation branch and confirms Phase 0.
2. Lane A builds private cloud runtime.
3. Lane B captures first-slice source snapshots.
4. Lane C and Lane D work in parallel after snapshot records are stable.
5. Lane E starts once routes exist in WordPress and continues through QA.
6. Lane F runs verification after content, media, and private controls are in
   place.
7. Lane G packages only the reviewed state.
8. Coordinator writes handoff reports and stops.

No lane should mark its work complete without attaching its report or artifact
manifest to the run ID.
