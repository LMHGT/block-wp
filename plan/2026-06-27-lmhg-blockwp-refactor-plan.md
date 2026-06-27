# LMHG Block WP Refactor Plan

Date: 2026-06-27

Target repo: `/Users/tyler-lcsw/projects/lmhg-blockwp`

Source repo to observe only: `/Users/tyler-lcsw/projects/lmhg-astro-integrate`

Baseline handoff copied into this repo:
`docs/superpowers/plans/2026-06-26-agentic-wordpress-codex-environment-handoff.md`

## Current Verified Context

- `LMHGT/block-wp.git` was empty when cloned into this workspace.
- `lmhg@personal` is installed and enabled at `0.1.0+codex.20260626150640`.
- The current Astro Integrate branch is `codex/homepage-alignment-polish`.
- Local Astro `HEAD` and `origin/staging` both resolve to `63d7ed9e58eb34f2fd730561ca0e0514a32ebcb0`.
- The Astro worktree has an unrelated untracked `company-kb/` directory. This plan does not touch it.
- LMHG Content Design health found the NocoBase dataset present with 55 pages and 2518 input needs. Open Design was not healthy.
- LMHG Launch Workbench health passed. The Workbench app/API are present, the API is reachable, and the expected Astro/NocoBase validation scripts exist.

## Operating Decision

This repository is a sidecar proof track, not a replacement for the current
Astro/NocoBase/Workbench production pipeline.

The generic WordPress handoff should be treated as the local runtime scaffold:
`@wordpress/env`, WordPress Agent Skills, a minimal block theme, a site-core
plugin, Playground Blueprint, Playwright, Lighthouse, and Tailscale Serve.

The LMHG overlay controls whether that scaffold is acceptable for this practice:
route inventory parity, Core30 IA parity, static SEO/schema parity, redirect
parity, rendered content parity, Workbench/editing parity, and staging proof must
be shown before any cutover discussion.

## Implementation Status Addendum

Updated: 2026-06-27

The original checklists in this plan are the implementation baseline. Current
repo status is recorded here so future workers do not mistake the baseline
checklist for current state.

Completed and pushed to `origin/main`:

- Phase 0 provenance, handoff copy, no-edit source boundary, and worker
  checklist.
- Phase 1 LMHG-owned WordPress scaffold, project-local WordPress skills,
  `@wordpress/env`, Playground blueprint, Playwright, Lighthouse, and static
  prerequisite checks.
- Phase 2 read-only Astro inventory export into route, design, and asset
  manifests.
- Phase 3 idempotent WordPress import contract for 52 in-scope pages, 117
  redirects, front-page setup, route meta, custom taxonomies, and source-owned
  structured data fields.
- Phase 5 site-core parity for canonical URLs, SEO titles/descriptions, robots,
  JSON-LD schema types, FAQPage output, graph-derived BreadcrumbList output,
  redirects, related links, unsupported city/service-area suppression, and
  local/tailnet URL handling.
- Phase 6 rendered-marker parity for H1, summary, source-copy snippets,
  breadcrumbs, related links, publishable FAQ items, FAQ readiness warnings, and
  scaffold-copy rejection.
- Phase 7 verification commands covering static manifests, imported routes,
  custom taxonomies, redirects, internal links, IntakeQ/phone actions, head
  metadata/schema, rendered markers, representative route browser checks,
  Lighthouse, PHP lint, npm audit, and tailnet proof.
- Phase 8 boundary artifact in `docs/staging-cutover-decision.md`.

Partially complete:

- Phase 4 has LMHG-owned tokens, header/footer actions, crawlable generated
  source-copy and graph sections, and representative route browser checks. Full
  hand-polished page-family template parity is not claimed.

Still intentionally not complete:

- Public WordPress hosting, DNS, Cloudflare, and Astro branch changes remain out
  of scope.
- Full hand-polished page copy parity is not claimed; current WordPress pages
  render source-copy snippets and graph sections rather than every Astro content
  block and layout.
- Live Workbench write integration into WordPress is not built.
- Protected legal/utility pages remain out-of-scope because the source manifest
  marks them out-of-scope for this proof track.
- Human review and a public staging/cutover decision are still required before
  any production recommendation.

## Hard Boundaries

- Do not edit `/Users/tyler-lcsw/projects/lmhg-astro-integrate` during this work.
- Do not push, merge, or fast-forward `origin/staging` or `main` for Astro.
- Do not make the public WordPress frontend depend on live NocoBase, SEO
  Dashboard, Workbench, or Astro runtime services.
- Do not replace LMHG Workbench Standard mode with raw WordPress database fields.
- Do not introduce a page builder or heavy frontend framework.
- Do not publish or expose WordPress as a production replacement without an
  explicit later approval gate.

## Handoff Adaptation

Use the supplied handoff tasks as the scaffold, with these LMHG-specific changes:

| Handoff Area | Keep | Adapt For LMHG |
|---|---|---|
| Runtime | `@wordpress/env` as authoritative local runtime | Keep isolated from the Astro Node 22 toolchain. Use the handoff's Node 20.18 requirement inside this repo only. |
| Theme | Custom filesystem block theme | Rename to an LMHG-owned slug before implementation, such as `lmhg-block-theme`. Translate LMHG tokens from `DESIGN.md` and `brand.md`. |
| Plugin | Durable site-core plugin | Rename to an LMHG-owned slug, such as `lmhg-site-core`. Own SEO, schema, redirects, graph data, Tailscale URL behavior, and local import tools. |
| Playground | Disposable smoke/demo runtime | Keep as non-authoritative. `wp-env` plus parity tests are the proof surface. |
| Tailscale Serve | Remote review URL | Keep tailnet-only. Never hand off raw localhost as a user review URL. |
| Verification | Static checks, Playwright, Lighthouse | Add LMHG route, redirect, metadata, schema, breadcrumb, related-link, and Workbench-marker parity checks. |

## Phase 0: Repo And Source Provenance

- [ ] Keep the copied handoff file under `docs/superpowers/plans/`.
- [ ] Add a source provenance file that records:
  - Astro source path.
  - Astro source branch and SHA.
  - `origin/staging` SHA.
  - LMHG plugin version used.
  - health-check results.
  - date/time of extraction.
- [ ] Add a no-edit note for the Astro source worktree.
- [ ] Create a worker checklist that distinguishes scaffold tasks from LMHG parity tasks.

Deliverable: `docs/source-provenance.md`.

## Phase 1: Agentic WordPress Scaffold

Implement the supplied handoff with LMHG-owned names.

- [ ] Create base Node, npm, `@wordpress/env`, Playwright, Lighthouse, and tool files.
- [ ] Install WordPress Agent Skills project-locally under `.codex/skills`.
- [ ] Create a minimal LMHG block theme.
- [ ] Create the LMHG site-core plugin.
- [ ] Create the local Playground Blueprint.
- [ ] Verify static file shape before runtime work.

Initial verification:

```bash
npm install
npm run check:static
npm run check:prereqs
php -l wp-content/themes/lmhg-block-theme/functions.php
php -l wp-content/plugins/lmhg-site-core/lmhg-site-core.php
```

Acceptance gate: the scaffold runs locally but still contains only placeholder
LMHG-safe content. It is not a migration yet.

## Phase 2: Astro Staging Inventory Export

Build import inputs from the current Astro staging snapshot without editing it.

- [ ] Read and inventory route sources:
  - `src/pages/`
  - `src/data/page-relationships.ts`
  - `src/data/core30.ts`
  - `src/data/seo.ts`
  - `src/data/site.ts`
  - `src/data/copy/`
  - `src/content/team/`
  - `src/content/articles/`
  - `public/_redirects`
- [ ] Export a route manifest with:
  - URL path.
  - page type.
  - canonical parent.
  - title/H1/meta owner.
  - source copy file or collection record.
  - expected template family.
  - related-page buckets.
  - FAQ assignment.
  - redirect/canonical requirements.
- [ ] Export a design/token manifest from `DESIGN.md`, `brand.md`, and current CSS token values.
- [ ] Export a visual asset manifest for active runtime assets under `public/brand` and `public/illustrations`.

Deliverables:

- `data/lmhg/source-route-manifest.json`
- `data/lmhg/source-design-manifest.json`
- `data/lmhg/source-assets-manifest.json`
- `docs/source-provenance.md`

Acceptance gate: every Astro public route and redirect has an explicit WordPress
migration status: `ready`, `needs-template`, `needs-copy-model`, `redirect-only`,
or `out-of-scope`.

## Phase 3: WordPress Content Model And Import Contract

Keep the public WordPress frontend file-driven and testable.

- [ ] Define the WordPress representation for each LMHG page family:
  - homepage
  - services hub
  - broad service category
  - specialty/service detail
  - care setting/context page
  - service area
  - FAQ hub/detail
  - team
  - article
  - support/legal
- [ ] Decide where each field lives:
  - block markup in page content
  - post meta owned by `lmhg-site-core`
  - taxonomy or custom post type
  - generated plugin manifest
- [ ] Keep SEO/schema/graph state in plugin-owned structured files or generated options, not in theme templates.
- [ ] Preserve stable field identity for future Workbench parity. Use marker IDs compatible with the LMHG rendered-marker contract, including collection-backed record identity where needed.
- [ ] Build an idempotent seed/import command that can rebuild local WordPress from the exported manifests.

Deliverables:

- `docs/content-model.md`
- `wp-content/plugins/lmhg-site-core/includes/importer.php`
- `tools/export-astro-inventory.mjs`
- `tools/seed-lmhg-wp.mjs`

Acceptance gate: `wp-env:seed` can rebuild the local WordPress site from repo-owned source files with no manual Site Editor dependency.

## Phase 4: LMHG Block Theme Templates

Translate the LMHG visual system to WordPress block theme files.

- [ ] Implement `theme.json` with LMHG color, typography, spacing, and layout tokens.
- [ ] Build header/footer parts that match current navigation policy:
  - Home, Services, Specialties, Team, Locations, FAQ, Contact.
  - Secondary/footer pages stay secondary.
  - Reach Out remains the primary CTA.
  - Phone appears in appropriate action surfaces.
- [ ] Implement page-family templates/patterns for the route families from Phase 3.
- [ ] Keep related links, service cards, FAQ content, and CTAs crawlable in static HTML.
- [ ] Avoid tabs, carousels, high-motion elements, stock imagery, generic wellness imagery, and decorative filler.
- [ ] Preserve one clear page illustration tier where applicable. Do not repeat page visuals as both hero and body decoration.

Acceptance gate: representative pages visually match LMHG's current calm,
structured, text-first direction without importing Astro code into WordPress.

Representative routes:

- `/`
- `/services/`
- `/individual-counseling/`
- `/child-counseling/`
- `/play-therapy/`
- `/community-based-services/`
- `/locations/`
- `/faq/`
- `/contact/`
- one article page
- one team page

## Phase 5: Site-Core Plugin Parity

The plugin owns durable behavior, not presentation.

- [ ] Generate metadata and JSON-LD from the route/content manifest.
- [ ] Generate breadcrumbs and `BreadcrumbList` from the faceted page graph, not path parsing.
- [ ] Generate related-page sections from the graph.
- [ ] Register and enforce redirects from the Astro `public/_redirects` inventory.
- [ ] Preserve trailing slash canonical behavior.
- [ ] Preserve IntakeQ CTA and phone behavior.
- [ ] Keep Tailscale Serve URL replacement local/tailnet-only.
- [ ] Add validation commands for:
  - duplicate canonical URLs
  - missing meta descriptions
  - missing H1
  - missing breadcrumbs where expected
  - related links pointing to missing pages
  - unsupported city service-area exposure
  - placeholder WordPress scaffold copy

Acceptance gate: the plugin can explain and validate every public SEO, schema,
redirect, breadcrumb, related-page, and local-review behavior it changes.

## Phase 6: Workbench And Editing Parity

WordPress must not silently break LMHG's launch-editing model.

- [ ] Preserve the Minimum Publishable Mode principle: Standard mode fields come from rendered preview markers, not raw database inventory.
- [ ] Add `data-lmhg-edit-field` marker output for visible editable content, or document the replacement marker contract before implementation.
- [ ] Keep collection-backed record identity stable for team/article content.
- [ ] Build a marker audit that compares rendered WordPress pages to the source route manifest.
- [ ] Keep hidden SEO/AIO data out of visible block copy.
- [ ] Treat absent FAQs and unselected related pages as readiness warnings unless fallback content renders.
- [ ] Defer any live Workbench write integration until local marker parity is proven.

Acceptance gate: a rendered WordPress page can be audited by the same mental model
as the Astro Workbench contract: visible editable text has a stable marker, and
Standard mode would not expose invisible/internal fields.

## Phase 7: Parity Verification

Add LMHG-specific proof beyond the generic WordPress checks.

Static checks:

```bash
npm run check:static
npm run verify:lmhg-static
git diff --check
```

Runtime checks:

```bash
npm run wp-env:start
npm run wp-env:seed
npm run verify:site
npm run verify:lmhg-routes
npm run verify:lmhg-redirects
npm run verify:lmhg-markers
npm audit --omit=dev
```

Route parity checks should compare WordPress local/tailnet output to the Astro
staging snapshot for:

- HTTP status and redirect target.
- canonical URL.
- title tag.
- meta description.
- H1.
- primary CTA label and href.
- breadcrumbs.
- related links.
- FAQ presence.
- JSON-LD type and required fields.
- unsupported footer/service-area links.
- visible placeholder/generic scaffold copy.

Acceptance gate: failures are listed by URL and field, not summarized as generic
"migration mismatch" text.

## Phase 8: Staging And Cutover Decision

Do not start this phase until Phases 1 through 7 pass.

- [ ] Create a WordPress-specific staging environment plan.
- [ ] Confirm hosting model, domain model, backup model, editor access model, and rollback model.
- [ ] Confirm whether WordPress is replacing Astro, becoming a content mirror, or remaining a sidecar prototype.
- [ ] Require human review of representative pages on the tailnet URL.
- [ ] Require explicit approval before any public DNS, Cloudflare Pages, or production branch change.

Acceptance gate: a cutover recommendation must name what remains better in Astro,
what is proven in WordPress, what editor workflow changes, and how rollback works.

## First Implementation Slice

The next worker should do only this slice:

1. Commit the copied handoff and this plan.
2. Create `docs/source-provenance.md`.
3. Implement Phase 1 scaffold with LMHG-owned names.
4. Run static verification.
5. Stop before importing LMHG content.

This gives the repo a clean WordPress base without pretending that the LMHG
migration is already solved.

## Done Means

For this plan, "done" means the repo contains a reviewed, source-controlled,
locally verifiable WordPress proof track that can be compared against the
current Astro staging site route by route.

It does not mean WordPress is approved for production.
