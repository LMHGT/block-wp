# 2026-07-05 DataForSEO Baseline

Baseline run for Louisville Mental Health Group local SEO, Louisville-area rankings, Google Business Profile context, backlink risk, and AI-answer visibility.

## Scope

- Domain: `louisvillementalhealth.org`
- Primary market: Louisville, Kentucky
- Repository context: WordPress 2026 sidecar in `/Users/tyler-lcsw/projects/lmhg-blockwp`
- Budget guardrail: `$25` checkpoint and `$35` maximum working cap
- Observed spend: `$4.281544`

## Key Files

- `api-run-ledger.csv`: per-call cost/status ledger.
- `final-summary.json`: compact run summary and final spend.
- `dataforseo-executive-report.md`: written findings and implementation plan.
- `dashboard-artifact-payload-final.json`: validated interactive dashboard payload.
- `normalized/`: CSV/JSON extracts for comparisons and future dashboards.
- `raw/`: request/response evidence from DataForSEO endpoints.

## Headline Findings

- DataForSEO OnPage crawls were blocked with `forbidden_http_header` and crawled `0` pages.
- Direct LLM responses mentioned LMHG in `11/48` prompts and cited LMHG in `8/48`.
- Google AI Mode mentioned LMHG in `1/17` prompts and cited LMHG in `0/17`.
- Case management is the strongest organic asset, including rank `1` for `mental health case management Louisville KY`.
- Louisville demand is highest for `therapy Louisville KY`, `therapist Louisville KY`, and `counseling Louisville KY`.

## Dashboard

The rendered in-chat artifact is backed by `dashboard-artifact-payload-final.json`.
Use that file as the source payload for later Data Analytics dashboard rerenders or comparison dashboards.
