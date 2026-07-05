# LMHG DataForSEO Louisville SEO and AI Visibility Baseline

Run folder: `/Users/tyler-lcsw/projects/lmhg-blockwp/data/seo-dataforseo/20260705T212750Z`  
Known DataForSEO spend: `$4.281544` across `290` logged paid/free result calls. This is below the `$25` checkpoint and `$35` working cap.

## What Was Run
- SERP: organic top-20, organic depth-100 desktop/mobile, Local Finder, Maps, Google AI Mode, and local grid checks around Louisville.
- Keyword data: Google Ads search volume, DataForSEO Labs keyword overview, keyword ideas, search intent, ranked keywords, SERP competitors, and competitor relevant pages.
- AI visibility: live LLM responses across ChatGPT, Perplexity, Gemini, and Claude; LLM Mentions brand/domain and topical top-domain/page checks.
- Local/business: Google Business Profile info and Q&A for LMHG.
- Technical/link: OnPage crawl attempts, backlinks summary, backlinks detail, lost links, and referring domains.

## Executive Findings
- DataForSEO OnPage is blocked before content crawl. Both default and browser-user-agent OnPage crawls ended with `forbidden_http_header`, `0` pages crawled, and the home URL reported non-indexable by HTTP header from gateway `168.119.141.170`. Local curl showed normal browsers and Googlebot get `200`, while an RSiteAuditor-like user agent gets `403`. Fix Cloudflare/security rules before using DataForSEO OnPage as the technical audit source.
- AI answer visibility is weak. Direct LLM responses mentioned LMHG in `11/48` responses and cited the domain in `8/48`. Google AI Mode mentioned LMHG in `1/17` prompts and cited the domain in `0/17`.
- The strongest organic asset is case management: LMHG ranked `#1` for `mental health case management Louisville KY` and `#3` for `case management Louisville KY` in live organic results. Preserve and expand this page rather than splitting it into a duplicate Targeted Case Management route.
- The largest local keyword opportunities are `therapy Louisville KY`, `therapist Louisville KY`, and `counseling Louisville KY`. Observed city/DMA search volumes were 880/1300 for therapy and therapist, and 590/590 for counseling. LMHG is visible but not dominant for these head terms.
- GBP data is strong but under-leveraged: claimed profile, 4.4 rating from 19 reviews, 15 five-star reviews, 3 photos, category `Mental health clinic`, and a rich description with services and Medicaid language. Work hours were not returned by DataForSEO, and Q&A contains one child-therapy question that should be mirrored as website FAQ content.
- Backlinks need cleanup: DataForSEO reported `146` backlinks, `74` referring domains, `95` broken backlinks, and backlink spam score `35`. Detail rows show many broken links to the homepage with `520` status from crawler perspective.
- WordPress 2026 is not ready to be judged as production SEO until noindex is removed intentionally and route-level `_lmhg_*` metadata/schema renders on service pages. The current sidecar is still a staging/private authority, while production DataForSEO data is from `louisvillementalhealth.org`.

## Ranking And Copy Priorities
1. **Therapy / therapist / counseling Louisville KY:** make `/individual-counseling/`, `/services/`, and homepage copy answer the generic local head terms directly. Add comparison-resistant copy, intake details, Medicaid/payment fit, and internal links from local/service hubs.
2. **Case management:** keep `/case-management/` as the canonical owner. Add a short definition, who it helps, what support includes, eligibility/payment language, coordination examples, and FAQ schema.
3. **Community-Based Services and therapy-in-your-home:** create answer blocks for “who offers in-home therapy” and “community-based mental health support in Louisville,” because LLMs cited competitors and government/resources more often than LMHG.
4. **Child/family/teen therapy:** improve `/child-counseling/`, `/family-therapy/`, `/play-therapy/`, and `/adolescent-counseling/` with age ranges, family involvement, school/court/community context, trauma/anxiety/depression terms, and FAQs.
5. **AI citation pages:** add concise, crawlable sections answering: accepts Medicaid, in-home/community-based care, case management, family therapy plus care coordination, teen anxiety/depression/trauma, outpatient/non-hospital options, and how to choose a Louisville therapist.
6. **Local pages:** clean overlong titles, short meta descriptions, and duplicate H1s on local-area pages; use service-area pages as supporting local relevance, not doorway clones.

## Cost Lessons For Future Runs
- Actual total was low because SERP and Labs calls are cheap when tightly scoped. The most expensive useful calls were Claude live responses.
- LLM Responses `chat_gpt`: `12` calls cost `$0.250466`; mentions `2`, citations `2`.
- LLM Responses `claude`: `12` calls cost `$1.013457`; mentions `2`, citations `2`.
- LLM Responses `gemini`: `12` calls cost `$0.395011`; mentions `3`, citations `0`.
- LLM Responses `perplexity`: `12` calls cost `$0.074843`; mentions `4`, citations `4`.
- Local grid Maps/Local Finder: 100 calls added about `$0.20`. This is now a good estimator for future 5-point/10-keyword grids.
- Organic depth-100 desktop/mobile: 32 calls added about `$0.496`; about `$0.0155` per depth-100 call in this run.
- LLM Mentions top-domain/top-page tasks cost about `$0.101` each even when returning zero rows; use sparingly until target syntax/data coverage is proven.
- Google Ads search volume cost `$0.09` per task in this run; Labs keyword/competitor/relevant-page tasks were roughly `$0.014-$0.036` each; backlink detail tasks were roughly `$0.024-$0.028` each.

## Generated Artifacts
- `/Users/tyler-lcsw/projects/lmhg-blockwp/data/seo-dataforseo/20260705T212750Z/normalized/ai-mode-audit.csv`
- `/Users/tyler-lcsw/projects/lmhg-blockwp/data/seo-dataforseo/20260705T212750Z/normalized/ai-mode-references.csv`
- `/Users/tyler-lcsw/projects/lmhg-blockwp/data/seo-dataforseo/20260705T212750Z/normalized/backlinks-detail.csv`
- `/Users/tyler-lcsw/projects/lmhg-blockwp/data/seo-dataforseo/20260705T212750Z/normalized/backlinks-summary.json`
- `/Users/tyler-lcsw/projects/lmhg-blockwp/data/seo-dataforseo/20260705T212750Z/normalized/competitor-labs-ranked-summary.csv`
- `/Users/tyler-lcsw/projects/lmhg-blockwp/data/seo-dataforseo/20260705T212750Z/normalized/competitor-labs-relevant-pages.csv`
- `/Users/tyler-lcsw/projects/lmhg-blockwp/data/seo-dataforseo/20260705T212750Z/normalized/competitor-summary.csv`
- `/Users/tyler-lcsw/projects/lmhg-blockwp/data/seo-dataforseo/20260705T212750Z/normalized/cost-by-api-family.csv`
- `/Users/tyler-lcsw/projects/lmhg-blockwp/data/seo-dataforseo/20260705T212750Z/normalized/gmb-profile-summary.json`
- `/Users/tyler-lcsw/projects/lmhg-blockwp/data/seo-dataforseo/20260705T212750Z/normalized/keyword-ideas.csv`
- `/Users/tyler-lcsw/projects/lmhg-blockwp/data/seo-dataforseo/20260705T212750Z/normalized/keyword-metrics.csv`
- `/Users/tyler-lcsw/projects/lmhg-blockwp/data/seo-dataforseo/20260705T212750Z/normalized/labs-serp-competitors-core.csv`
- `/Users/tyler-lcsw/projects/lmhg-blockwp/data/seo-dataforseo/20260705T212750Z/normalized/llm-citations.csv`
- `/Users/tyler-lcsw/projects/lmhg-blockwp/data/seo-dataforseo/20260705T212750Z/normalized/llm-fanout-queries.csv`
- `/Users/tyler-lcsw/projects/lmhg-blockwp/data/seo-dataforseo/20260705T212750Z/normalized/llm-mentions-topic-results.csv`
- `/Users/tyler-lcsw/projects/lmhg-blockwp/data/seo-dataforseo/20260705T212750Z/normalized/llm-response-audit.csv`
- `/Users/tyler-lcsw/projects/lmhg-blockwp/data/seo-dataforseo/20260705T212750Z/normalized/local-grid-keyword-summary.csv`
- `/Users/tyler-lcsw/projects/lmhg-blockwp/data/seo-dataforseo/20260705T212750Z/normalized/local-grid-lmhg-summary.csv`
- `/Users/tyler-lcsw/projects/lmhg-blockwp/data/seo-dataforseo/20260705T212750Z/normalized/local-grid-rankings.csv`
- `/Users/tyler-lcsw/projects/lmhg-blockwp/data/seo-dataforseo/20260705T212750Z/normalized/local-serp-competitors.csv`
- `/Users/tyler-lcsw/projects/lmhg-blockwp/data/seo-dataforseo/20260705T212750Z/normalized/onpage-summary.json`
- `/Users/tyler-lcsw/projects/lmhg-blockwp/data/seo-dataforseo/20260705T212750Z/normalized/organic-depth100-lmhg-summary.csv`
- `/Users/tyler-lcsw/projects/lmhg-blockwp/data/seo-dataforseo/20260705T212750Z/normalized/serp-rankings.csv`
