# LMHG WordPress Staging And Cutover Decision

Status: sidecar proof track, not production-approved.

Date: 2026-06-27

## Current Reviewer Surface

- Local authoritative runtime: `http://localhost:8888`
- Tailnet review URL: `https://mbp.beagle-perch.ts.net/`
- Tailnet proxy: `mbp.beagle-perch.ts.net:443 / -> http://127.0.0.1:8888`
- Public production source remains Astro/Cloudflare until Tyler explicitly
  approves a separate cutover.

## Proven In WordPress

- 52 imported in-scope pages from the repo-owned route manifest.
- 117 route manifest redirects stored and enforced by `lmhg-site-core`.
- Plugin-owned LMHG taxonomies for page family, template family, faceted type,
  schema type, migration status, and SEO status.
- Source canonical URLs, SEO titles, meta descriptions, H1 values where present,
  JSON-LD schema types, FAQPage nodes, and graph-derived BreadcrumbList nodes.
- Rendered graph breadcrumbs, related-page sections, FAQ readiness markers, and
  visible `data-lmhg-edit-field` markers.
- Sitewide `Reach Out` IntakeQ CTA and active `tel:5024161416` phone link.
- Suppression of redirect-only and unsupported city/service-area links in the
  rendered WordPress output.
- Representative route browser checks across desktop and mobile.
- Tailnet verification for redirects, links, CTA/phone actions, canonical head
  output, and JSON-LD output.

## Still Better Or More Complete In Astro

- Astro remains the current public staging and production pipeline.
- LMHG Workbench Standard mode is proven against rendered Astro markers today;
  WordPress write integration is not built.
- NocoBase extraction/validation remains attached to the Astro workflow.
- Protected utility/legal pages are present in the Astro source but intentionally
  out-of-scope in the current WordPress import manifest.
- The WordPress proof currently renders source-derived summaries and graph
  sections, not full hand-polished page-copy parity for every page family.

## Required Staging Model Before Public Use

Before any public WordPress staging environment is created, decide and document:

- Hosting model: managed WordPress, self-hosted VPS/container, or another
  isolated staging host.
- Domain model: tailnet-only, private staging subdomain, or public noindex
  staging subdomain.
- Backup model: database backup, uploaded media backup, plugin/theme source
  backup, and restore drill.
- Editor access model: named user roles, MFA expectations, plugin permissions,
  and whether editors use WordPress directly or Workbench remains the authoring
  surface.
- Deployment model: how `wp-content/themes/lmhg-block-theme` and
  `wp-content/plugins/lmhg-site-core` reach staging from GitHub.
- Secrets model: no IntakeQ, Rank Math, SMTP, analytics, or production
  credentials should be committed to this repository.

## Cutover Gate

Do not change public DNS, Cloudflare Pages production, Astro `origin/staging`,
or Astro `main` from this repository.

A future cutover recommendation must include:

- A human review sign-off for representative pages on the WordPress staging URL.
- A route-by-route redirect and canonical report against the final staging host.
- A content parity report for full page copy, not only source-derived summaries.
- A Workbench/editor workflow decision.
- A Rank Math configuration decision that treats `lmhg-site-core` as taxonomy
  owner and Rank Math as SEO consumer.
- A rollback plan that keeps the Astro deployment available until WordPress is
  proven and accepted.

## Rollback Outline

If WordPress is ever promoted and needs to roll back:

- Point DNS or the reverse proxy back to the existing Astro/Cloudflare surface.
- Preserve the WordPress database and uploads before rollback for forensic
  review.
- Re-run Astro validation and staging smoke checks before declaring rollback
  complete.
- Do not delete the WordPress proof track; retain it for post-rollback analysis.
