# LMHG DataForSEO Reference Runs

This directory stores non-deployable SEO research artifacts for Louisville Mental Health Group.

The data is kept on a dedicated reference branch so baseline results can be compared against later DataForSEO reruns without mixing research artifacts into the deployable WordPress work.

## Runs

- `20260705T212750Z`: Louisville, KY local SEO and AI-answer visibility baseline for `louisvillementalhealth.org`.

## Branch Policy

- Keep this data on a reference branch, not `main`.
- Commit raw responses, normalized summaries, cost ledgers, executive reports, and dashboard payloads together for each run.
- Do not include DataForSEO credentials or local API configuration files.
- Treat raw SERP and LLM responses as third-party research data. They may include public result metadata and should not be used as deployable site content without review.

## Comparison Points

Future runs should preserve the same core files when possible:

- `api-run-ledger.csv`
- `final-summary.json`
- `dataforseo-executive-report.md`
- `normalized/`
- `dashboard-artifact-payload-final.json`
