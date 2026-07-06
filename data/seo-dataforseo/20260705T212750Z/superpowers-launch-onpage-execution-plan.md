# Superpowers Launch On-Page Execution Plan

Run folder: `/Users/tyler-lcsw/projects/lmhg-blockwp/data/seo-dataforseo/20260705T212750Z`  
Prepared: 2026-07-06  
Scope: implementation plan for launch-time on-page SEO, local SEO, and AI retrieval improvements that are under LMHG control before or at WordPress launch.  
Mode: reference-only plan artifact. This file does not change runtime behavior.

## Superpowers Pass Summary

This plan was built with two read-only Superpowers subagents plus local verification:

- Code-surface agent: mapped metadata, feed, FAQ schema, FAQ rendering, page-copy, and internal-link implementation surfaces.
- SEO/content agent: checked the DataForSEO launch-copy artifacts and current page export against the requested page and FAQ recommendations.
- Main pass: verified the live development surface at `http://100.70.222.25:8093` without paid API calls.

No new DataForSEO spend was incurred for this planning pass. The latest tracked DataForSEO spend remains approximately `$4.299044`.

## Current Evidence

Live checks on `http://100.70.222.25:8093` show:

- `/services/`, `/case-management/`, `/community-based-services/`, `/therapy-in-your-home/`, `/child-counseling/`, `/individual-counseling/`, `/anxiety-depression-therapy/`, `/family-therapy/`, and `/insurance/` still emit meta descriptions that start with breadcrumb/body text.
- Visible FAQ sections now emit `FAQPage` JSON-LD on sampled service/specialty pages including `/case-management/`, `/community-based-services/`, `/therapy-in-your-home/`, `/child-counseling/`, `/individual-counseling/`, `/anxiety-depression-therapy/`, and `/family-therapy/`.
- `/` and `/insurance/` currently have no visible FAQ details and no `FAQPage` JSON-LD.
- `/feed/` still returns RSS with four items and includes `Hello world!`.
- The page export is wrapped as `{"generatedAt": "...", "pages": [...]}` and does not provide clean `metaDescription` values for the priority pages; page `content` includes breadcrumb markup, which explains the polluted fallback descriptions.

Current source evidence:

- SEO output: `wp-content/plugins/lmhg-site-core/includes/seo.php`
- Public-surface controls: `wp-content/plugins/lmhg-site-core/includes/surface-controls.php`
- FAQ CPT/taxonomy seeds and shortcode rendering: `wp-content/plugins/lmhg-site-core/includes/content-relationships.php`
- Editable block import metadata: `wp-content/plugins/lmhg-site-core/includes/editable-blocks.php`
- Service/specialty FAQ template slots: `wp-content/themes/wordpress-2026/templates/service-page.html` and `wp-content/themes/wordpress-2026/templates/specialty-page.html`
- Current page export: `wp-content/themes/wordpress-2026/wp2026-page-data.json`

## Execution Lanes

### Lane 1: Technical Launch Hygiene

Owner surface:

- `wp-content/plugins/lmhg-site-core/includes/seo.php`
- `wp-content/plugins/lmhg-site-core/includes/surface-controls.php`
- import/update surfaces only if explicit clean metadata is seeded through source data

Tasks:

1. Fix breadcrumb-polluted meta descriptions.
   - Preferred implementation: add route-level clean description overrides for the priority launch pages, then keep a fallback sanitizer that strips breadcrumb text when `_lmhg_meta_description` is missing.
   - Do not rely on excerpting page body content while body content begins with breadcrumb markup.
   - Keep descriptions under roughly 155 characters and include Louisville, KY where useful.

2. Remove duplicate dated article/default post exposure from feeds.
   - Existing code blocks singular posts and default archives but does not block feeds.
   - Add feed-specific handling so `/feed/`, `/comments/feed/`, and default post feeds do not expose default posts or duplicate dated article URLs.
   - Acceptable launch outcomes: clean 404/410 for default feeds, redirect to canonical article hub, or a feed that contains only intentional canonical page/article URLs. The first two are simpler and safer for this sidecar.

3. Confirm production discovery URL behavior before launch.
   - Internal `100.70.222.25:8093` URLs are expected in dev.
   - Production must emit `https://louisvillementalhealth.org/` in canonical, sitemap, REST, `/robots.txt`, and `/llms.txt`.

Proof:

- Meta descriptions for all priority pages no longer contain `Home`, `/ Services`, `&nbsp;`, or breadcrumb separators.
- `/feed/` no longer exposes `Hello world!` or dated duplicate article URLs.
- `/wp-sitemap.xml` lists intended page URLs only.
- Production-host simulation and final launch host emit public host URLs only.

### Lane 2: FAQ Schema And Page-Specific FAQ Content

Owner surface:

- `wp-content/plugins/lmhg-site-core/includes/content-relationships.php`
- `wp-content/plugins/lmhg-site-core/includes/seo.php`
- `wp-content/themes/wordpress-2026/templates/service-page.html`
- `wp-content/themes/wordpress-2026/templates/specialty-page.html`

Tasks:

1. Preserve the current visible FAQ to `FAQPage` bridge.
   - The current bridge is mostly working for service/specialty pages.
   - Regression risk is high because `lmhg_site_core_json_ld_faq_items()` changes behavior depending on editable block metadata.

2. Replace generic two-question FAQ seeds with page-specific launch FAQs.
   - The existing generic seed creates broad questions such as availability and fit.
   - Add page-specific seeded FAQ records for the priority pages, with version bumping so the new starter FAQs seed once while preserving non-starter editor-authored content.

3. Add the GBP-derived four-year-old child FAQ.
   - Target page: `/child-counseling/`
   - Recommended question: `Does LMHG offer therapy for a four-year-old child?`
   - Recommended answer: `LMHG can help a caregiver ask whether child therapy is a fit for a young child. For a four-year-old, care may involve caregiver participation, developmentally appropriate play-based support, family context, and coordination around behavior, emotional regulation, routines, or school/daycare concerns. Availability, provider fit, insurance, and clinical appropriateness should be confirmed before scheduling.`

4. Add homepage and insurance FAQ coverage only when visible on the page.
   - If `/` gets visible FAQs, emit matching `FAQPage` JSON-LD.
   - If `/insurance/` gets visible FAQs, emit matching `FAQPage` JSON-LD.
   - Do not emit FAQPage on pages that are only FAQ/link hubs without visible Q&A.

Proof:

- Each visible `<details class="lmhg-faq-item">` question on target pages has a matching `FAQPage.mainEntity` question.
- `/child-counseling/` visibly includes the four-year-old FAQ and JSON-LD includes the same question.
- `/` and `/insurance/` either have no visible FAQs and no FAQPage, or visible FAQs with matching FAQPage.

### Lane 3: Priority Page Copy Expansion

Owner surface:

- Preferred source: `wp-content/themes/wordpress-2026/wp2026-page-data.json` or the current import source that generates it.
- Runtime import/update: existing importer/editable-block pipeline.
- Avoid hand-editing live WordPress content only, unless the goal is an emergency launch patch with a separate export afterwards.

Copy requirements by page:

1. `/case-management/`
   - Keep this as the canonical owner for case management; do not create a duplicate targeted-case-management route unless the service claim is operationally confirmed.
   - Add explicit Medicaid-verification language, care coordination examples, resources, referrals, appointments, benefits, transportation, school/family coordination, and follow-through.
   - Add FAQ coverage for Medicaid case management, what case managers help with, case management versus therapy, and when to compare community or in-home services.

2. `/community-based-services/`
   - Expand from a thin definition into a direct answer for `community-based mental health services Louisville KY`.
   - Include case management, community support, in-home help, school coordination, family support, resources, practical barriers, and follow-through.
   - Clarify how community-based care differs from office-only therapy.

3. `/therapy-in-your-home/`
   - Retitle and describe as `In-Home Mental Health Therapy in Louisville, KY` where feasible.
   - Disambiguate from physical therapy, home health, and general home care.
   - Connect the page to community-based services, case management, family routines, transportation/access barriers, and provider availability.

4. `/child-counseling/`
   - Retitle to `Child Therapy in Louisville, KY` where feasible.
   - Include `child therapist`, `child counseling`, caregiver involvement, school stress, emotional regulation, behavior concerns, trauma, play therapy paths, and age/fit language.
   - Add the GBP-derived four-year-old FAQ.

5. `/individual-counseling/`
   - Make this the broad owner for `therapy Louisville KY`, `therapist Louisville KY`, `counseling Louisville KY`, and `individual therapy Louisville KY`.
   - Include adults and teens, anxiety, depression, trauma, grief, stress, relationship strain, burnout, life changes, office/telehealth options, insurance verification, and how to choose a therapist.

6. `/anxiety-depression-therapy/`
   - Add direct symptom language: worry, panic, sadness, low motivation, irritability, numbness, sleep changes, avoidance, work stress, school pressure, relationship strain, grief, trauma, and daily responsibilities.
   - Link to individual counseling, teen therapy, trauma therapy, EMDR, family therapy, and case management where relevant.

7. `/family-therapy/`
   - Expand around communication, routines, parenting stress, attachment concerns, conflict, grief, trauma impact, transitions, caregiver burnout, and care coordination.
   - Link to child therapy, teen therapy, parenting support, community-based services, and case management.

8. `/insurance/`
   - Answer `therapist that accepts Medicaid Louisville KY` without overclaiming.
   - Recommended answer framing: LMHG works with Medicaid and commercial insurance questions, but plan, service, provider, and availability must be verified before care begins.
   - Link to `/faq/cost/`, `/case-management/`, `/individual-counseling/`, `/services/`, and `/contact/` or intake.

Proof:

- Each priority page has an answer-first opening paragraph that can stand alone in AI retrieval.
- Each priority page has at least three crawlable internal links to relevant canonical owner pages.
- Each priority page has visible, page-specific FAQs.
- The text does not overstate availability, coverage, provider fit, or guaranteed services.

### Lane 4: Internal Link Graph Strengthening

Owner surface:

- `wp-content/plugins/lmhg-site-core/includes/content-relationships.php`
- `wp-content/plugins/lmhg-site-core/includes/page-class-design.php`
- page source bodies in `wp2026-page-data.json` or upstream source
- article-related link map in `lmhg_site_core_article_related_links()`

Source-to-target matrix:

| Source | Add/strengthen anchors | Canonical targets |
| --- | --- | --- |
| `/` | `individual counseling in Louisville`, `child therapy`, `family therapy`, `case management`, `community-based services`, `in-home mental health therapy`, `Medicaid and insurance questions` | `/individual-counseling/`, `/child-counseling/`, `/family-therapy/`, `/case-management/`, `/community-based-services/`, `/therapy-in-your-home/`, `/insurance/` |
| `/services/` | `therapy or counseling`, `care coordination`, `home or community support`, `child and teen support`, `family support`, `insurance fit` | all eight priority pages plus `/adolescent-counseling/` and `/faq/cost/` |
| `/specialties/` | `anxiety and depression therapy`, `play therapy`, `teen therapy`, `case management`, `community support`, `in-home care`, `family care` | `/anxiety-depression-therapy/`, `/play-therapy/`, `/adolescent-counseling/`, `/case-management/`, `/community-support/`, `/therapy-in-your-home/`, `/family-therapy/` |
| `/insurance/` | `Medicaid mental health services`, `case management`, `individual counseling`, `community-based services`, `cost questions` | `/case-management/`, `/individual-counseling/`, `/community-based-services/`, `/faq/cost/`, `/services/` |
| `/faq/` and `/faq/cost/` | `choose a service`, `insurance verification`, `case management`, `community support`, `therapy options` | `/services/`, `/insurance/`, `/case-management/`, `/community-based-services/`, `/individual-counseling/` |
| `/locations/in-person/` | `in-person counseling`, `individual counseling`, `child therapy`, `family therapy`, `insurance questions` | `/individual-counseling/`, `/child-counseling/`, `/family-therapy/`, `/insurance/`, `/contact/` |
| article pages | topic-specific links from each article conclusion and related-care section | family article to `/family-therapy/` and `/individual-counseling/`; individual therapy guide to `/individual-counseling/`; therapy-start articles to `/services/`, `/insurance/`, `/faq/cost/` |

Proof:

- Rendered HTML contains descriptive anchors, not only `View Page` and `Reach Out`.
- Article pages contain contextual links in body/conclusion sections plus generated related-care links.
- Broad hubs route crawlers to the canonical owner pages within one click.

### Lane 5: Deployment And Validation

This repo has a live remote runtime boundary. Runtime-visible changes require:

1. Back up the remote target.
2. Sync deployable changes to both:
   - `/srv/storage/services/lmhg-blockwp/repo`
   - `/srv/storage/services/wordpress 2026/wordpress`
3. Verify `http://100.70.222.25:8093/` and the named priority URLs.
4. Only after local/runtime proof passes, consider a paid DataForSEO OnPage recrawl.

Suggested validation commands:

```bash
php -l wp-content/plugins/lmhg-site-core/includes/seo.php
php -l wp-content/plugins/lmhg-site-core/includes/surface-controls.php
php -l wp-content/plugins/lmhg-site-core/includes/content-relationships.php
php -l wp-content/plugins/lmhg-site-core/includes/editable-blocks.php
php -l wp-content/plugins/lmhg-site-core/lmhg-site-core.php
```

```bash
node - <<'NODE'
const paths = ['/', '/services/', '/case-management/', '/community-based-services/', '/therapy-in-your-home/', '/child-counseling/', '/individual-counseling/', '/anxiety-depression-therapy/', '/family-therapy/', '/insurance/', '/feed/'];
const base = 'http://100.70.222.25:8093';
for (const path of paths) {
  const res = await fetch(base + path, {headers: {'User-Agent': 'Codex-LMHG-Launch-Validator/1.0'}});
  const text = await res.text();
  const meta = text.match(/<meta\s+name=["']description["']\s+content=["']([^"']*)/i)?.[1] || '';
  const detailCount = (text.match(/<details\b/gi) || []).length;
  const faqPage = (text.match(/"@type"\s*:\s*"FAQPage"/g) || []).length;
  const itemCount = (text.match(/<item>/gi) || []).length;
  const hello = /Hello world/i.test(text);
  console.log(JSON.stringify({path, status: res.status, meta, detailCount, faqPage, itemCount, hello}));
}
NODE
```

Minimum launch gates:

- All priority pages return `200`.
- Priority-page meta descriptions are clean and not breadcrumb-derived.
- `/feed/` does not expose `Hello world!` or dated duplicate article URLs.
- Service/specialty pages with visible FAQs emit matching `FAQPage` JSON-LD.
- `/child-counseling/` includes the four-year-old FAQ visibly and in JSON-LD.
- Homepage, services, specialties, insurance, FAQ, location, and article pages link to the canonical service-owner pages.
- `/robots.txt`, `/wp-sitemap.xml`, and `/llms.txt` are crawlable on internal dev and use public production URLs after launch.

## Work Order

1. Adopt or isolate the current dirty SEO/FAQ/internal-link overlay so the implementation has a clean include set.
2. Patch metadata and feed behavior first, because those affect crawl surfaces across the site.
3. Add page-specific FAQ seed data and verify visible FAQ/schema parity.
4. Expand the eight priority pages in the source content path and re-import/sync.
5. Strengthen internal links from hubs, location, FAQ, insurance, and articles.
6. Run local lint and live `8093` validation.
7. Back up, sync to remote source/runtime, and re-run live validation.
8. Run a small paid DataForSEO OnPage recrawl only after the visible runtime passes the local checks.

