# 2026-07-05 DataForSEO Baseline

Baseline run for Louisville Mental Health Group local SEO, Louisville-area rankings, Google Business Profile context, backlink risk, and AI-answer visibility.

## Scope

- Domain: `louisvillementalhealth.org`
- Primary market: Louisville, Kentucky
- Repository context: WordPress 2026 sidecar in `/Users/tyler-lcsw/projects/lmhg-blockwp`
- Budget guardrail: `$25` checkpoint and `$35` maximum working cap
- Observed spend: `$4.299044`, including five supplemental launch-copy SERP checks added after the initial baseline.

## Key Files

- `api-run-ledger.csv`: per-call cost/status ledger.
- `final-summary.json`: compact run summary and final spend.
- `dataforseo-executive-report.md`: written findings and implementation plan.
- `onpage-launch-copy-plan.md`: short-term launch plan for on-page copy, FAQs, metadata, schema, discovery, and internal links.
- `superpowers-launch-onpage-execution-plan.md`: Superpowers-assisted implementation plan with file surfaces, work lanes, page deliverables, link matrix, and validation gates.
- `dashboard-artifact-payload-final.json`: validated interactive dashboard payload.
- `normalized/`: CSV/JSON extracts for comparisons and future dashboards.
- `raw/`: request/response evidence from DataForSEO endpoints.
- `normalized/onpage-launch-copy-actions.csv`: page-by-page launch actions.
- `normalized/onpage-launch-serp-supplement.csv`: five supplemental SERP checks used for the launch-copy plan.

## Headline Findings

- The original DataForSEO OnPage crawls were blocked with `forbidden_http_header` and crawled `0` pages. The internal crawler gate was later fixed on the WordPress 2026 dev surface; a post-edit DataForSEO OnPage recrawl is still pending.
- Direct LLM responses mentioned LMHG in `11/48` prompts and cited LMHG in `8/48`.
- Google AI Mode mentioned LMHG in `1/17` prompts and cited LMHG in `0/17`.
- Case management is the strongest organic asset, including rank `1` for `mental health case management Louisville KY`.
- Louisville demand is highest for `therapy Louisville KY`, `therapist Louisville KY`, and `counseling Louisville KY`.
- The launch-copy plan prioritizes clean metadata, visible page-specific FAQs with matching FAQPage schema, stronger case-management/community/in-home/child-family copy, removal of duplicate feed exposure, and production discovery URLs.

## Dashboard

The rendered in-chat artifact is backed by `dashboard-artifact-payload-final.json`.
Use that file as the source payload for later Data Analytics dashboard rerenders or comparison dashboards.
