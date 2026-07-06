# LMHG Launch On-Page Copy and AI Retrieval Plan

Run folder: `/Users/tyler-lcsw/projects/lmhg-blockwp/data/seo-dataforseo/20260705T212750Z`  
Prepared: 2026-07-05  
Scope: short-term launch changes to page copy, FAQs, metadata, internal links, and crawlable answer surfaces. This does not cover backlinks, citations, domain authority, reputation campaigns, or longer-term content publishing.

## Data Used

- Existing DataForSEO baseline: SERP, Local Finder, Maps, Google AI Mode, LLM Responses, LLM Mentions, keyword volume, competitor pages, GBP profile/Q&A, backlinks, and attempted OnPage crawls.
- Current WordPress 2026 dev surface: `http://100.70.222.25:8093` after the internal crawl gate fix.
- Current WordPress source export: `wp-content/themes/wordpress-2026/wp2026-page-data.json`.
- Supplemental DataForSEO SERP checks, Louisville city location code `1017825`, desktop organic, depth 20:
  - `therapist that accepts Medicaid Louisville KY`
  - `in home therapy Louisville KY`
  - `community support services Louisville KY mental health`
  - `child therapist Louisville KY`
  - `mental health case management Medicaid Louisville KY`

The original baseline spend was `$4.281544`. The supplemental SERP checks added `$0.017500`, bringing tracked spend to approximately `$4.299044`.

## What Changed Since The Baseline

The baseline correctly found that DataForSEO OnPage was blocked by crawler headers before it could crawl content. That gate has now been fixed for the private Tailscale development surface and for the production hostnames. This plan still does not claim a completed DataForSEO OnPage recrawl; it uses SERP, local, AI, LLM, GBP, and current rendered-copy evidence. A post-edit OnPage recrawl should be a launch validation step.

## Launch Thesis

LMHG already has a strong local signal for case management and useful breadth across therapy, counseling, family services, community-based support, and in-home care. The current launch copy is organized but often too thin, too templated, or too indirect for extraction by AI answer systems. The short-term fix is not more pages for every keyword. It is stronger answer-first copy, visible page-specific FAQs, cleaner metadata, and internal links that make the canonical service owners obvious.

Every priority page should answer these in crawlable text near the top:

1. What does LMHG provide on this page?
2. Who is it for?
3. Where is it available in or near Louisville?
4. What setting is possible: office, telehealth, in-home, school, or community?
5. Does insurance or Medicaid need to be verified?
6. What should the visitor do next?

## DataForSEO Evidence To Act On

### Highest Demand Queries

- `therapy Louisville KY`: city search volume `880`, DMA `1300`, core volume `1600`.
- `therapist Louisville KY`: city `880`, DMA `1300`, core `1600`.
- `counseling Louisville KY`: city `590`, DMA `590`, core `720`.
- `behavioral health Louisville KY`: city `90`, DMA `140`.
- `depression therapy Louisville KY`: city `50`, DMA `50`, core `70`.

### Current LMHG Organic Positioning

- Strong: `mental health case management Louisville KY` ranked `#1` desktop and `#4` mobile.
- Strong: `case management Louisville KY` ranked `#4` desktop and `#5` mobile.
- Strong: `group therapy Louisville KY` ranked `#3` desktop and mobile.
- Strong but ambiguous: `community based mental health services Louisville KY` ranked `#6` desktop and `#4` mobile, but the baseline surfaced homepage/location pages rather than a single clear canonical service page.
- Weak: `therapy Louisville KY` ranked `#20` desktop and `>100` mobile.
- Weak: `therapist Louisville KY` ranked `#21` desktop and `#26` mobile.
- Weak: `counseling Louisville KY` ranked `#56` desktop and `#58` mobile.
- Weak: `individual therapy Louisville KY` ranked around `#20`.
- Missing: `anxiety therapy Louisville KY`, `trauma therapy Louisville KY`, and `play therapy Louisville KY` were absent in depth-100 checks.

### AI And LLM Visibility

- Direct LLM responses mentioned LMHG in `11/48` prompts and cited the domain in `8/48`.
- Google AI Mode mentioned LMHG in `1/17` prompts and cited the domain in `0/17`.
- Best LLM performance was case management: `Where can someone get mental health case management in Louisville, KY?` produced `4/4` mentions and `3/4` citations.
- Weak or absent prompts included:
  - `Compare local outpatient mental health providers in Louisville.`
  - `What should I ask before choosing a therapist in Louisville?`
  - `Which Louisville providers help teens with anxiety, depression, or trauma?`
  - `Who offers family therapy and care coordination in Louisville?`
  - `Who are the best mental health counseling providers in Louisville, KY?`

### Supplemental SERP Findings

- `therapist that accepts Medicaid Louisville KY`: local pack favored Kentucky Counseling Center, Evolved Counseling, and LifeStance. Organic results favored Medicaid-filtered directories and pages with explicit insurance language. LMHG did not appear in the top returned items.
- `in home therapy Louisville KY`: results were polluted by physical therapy pages. The LMHG page must disambiguate with "in-home mental health therapy" and "home-based mental health support" in title, intro, and FAQ.
- `community support services Louisville KY mental health`: results favored government/resource pages, Seven Counties, Bridgehaven, and community-service entities. LMHG needs a clear definition and provider statement, not only a short service paragraph.
- `child therapist Louisville KY`: local pack and organic results favored pages using "child therapist," "children and teens," "play therapy," age ranges, and direct call language. LMHG's current `/child-counseling/` title is just `Child Therapy` and the source text is thin.
- `mental health case management Medicaid Louisville KY`: LMHG appeared around organic `#7`, but Best Life, FindHelp, CHFS, Kentucky Mental Health Care, and Humana ranked above it with explicit Medicaid/case-management language. LMHG can improve this without creating a duplicate route by expanding `/case-management/`.

## Cross-Site Launch Fixes

### 0. Remove Duplicate Post And Feed Exposure

The rendered sitemap is clean, but the RSS feed still exposes duplicate dated article posts plus the default `Hello world!` post even though dated post URLs return 404. This is a launch-control issue because crawlers and AI systems can discover feed URLs even when navigation and sitemap are clean.

Launch fix:

- Remove or unpublish the default `Hello world!` post.
- Remove duplicate dated article posts from feeds, or redirect them to the canonical article pages.
- Confirm `/feed/` only exposes canonical, intended content.
- Confirm article canonical URLs are the clean `/articles/.../` routes.

### 0.5. Regenerate Discovery URLs On Production Host

The internal dev surface correctly emits Tailscale URLs such as `http://100.70.222.25:8093/` in canonical, sitemap, REST, and `/llms.txt`. That is expected for internal analysis but must not survive launch.

Launch fix:

- Verify production `home_url()` and `site_url()` resolve to `https://louisvillementalhealth.org/`.
- Regenerate sitemap and `/llms.txt` on the production host.
- Confirm no `100.70.222.25`, `8093`, `.ts.net`, or local-only hostnames appear in production HTML, sitemap, REST responses, `/robots.txt`, or `/llms.txt`.

### 1. Clean Meta Descriptions

Current rendered meta descriptions on several pages are being excerpted from breadcrumb/body text, for example:

- `/services/`: starts with `Home / Services Compare mental health services...`
- `/individual-counseling/`: starts with breadcrumb text.
- `/case-management/`: starts with breadcrumb text.
- `/therapy-in-your-home/`: starts with breadcrumb text.
- `/child-counseling/`: starts with breadcrumb text.

Launch fix: store or generate clean route-level meta descriptions that do not include breadcrumbs. Keep them answer-first and under roughly 155 characters.

Recommended examples:

- `/`: `Therapy, counseling, EMDR, case management, and community-based mental health services in Louisville, KY for adults, children, couples, and families.`
- `/services/`: `Compare LMHG therapy, counseling, case management, community support, EMDR, family services, and in-home mental health support in Louisville.`
- `/individual-counseling/`: `Individual counseling in Louisville for adults and teens facing anxiety, depression, trauma, stress, relationship strain, grief, or life changes.`
- `/case-management/`: `Mental health case management in Louisville for Medicaid clients who need care coordination, resources, referrals, appointments, and follow-through.`
- `/community-based-services/`: `Community-based mental health services in Louisville for case management, community support, in-home help, resources, and care coordination.`
- `/therapy-in-your-home/`: `In-home mental health therapy and home-based support in Louisville for clients and families who need care connected to daily routines.`
- `/child-counseling/`: `Child therapy in Louisville for emotional regulation, behavior concerns, trauma, school stress, caregiver support, and family involvement.`

### 2. Make FAQPage Schema Match Visible FAQs

The rendered pages show visible FAQ sections, but sampled pages did not show `FAQPage` JSON-LD. The plugin has FAQPage schema code, so the likely launch task is a bridge bug or assignment issue, not a brand-new schema system.

Launch fix:

- Verify that every visible service-page FAQ is returned by `lmhg_site_core_json_ld_faq_items()`.
- Ensure the same question/answer pairs visible in `<details>` render in JSON-LD.
- Do not emit FAQPage schema on pages without visible FAQ content.
- Validate at least: `/case-management/`, `/community-based-services/`, `/therapy-in-your-home/`, `/child-counseling/`, `/family-therapy/`, `/individual-counseling/`, `/faq/cost/`.

### 3. Add AEO Answer Blocks Above The Fold

Use a consistent short answer block on high-priority pages:

```text
Short answer: Louisville Mental Health Group provides [service] in Louisville, Kentucky for [audience]. This page explains when [service] may fit, what support can include, what care settings may be available, and how to ask about fit, availability, insurance, and next steps.
```

This style helps AI retrieval because it creates a self-contained answer that can be extracted without stitching together the whole page.

### 4. Add Internal Links From General To Specific Pages

The broad search terms currently resolve mostly to the homepage. Add stronger internal links from `/`, `/services/`, `/locations/in-person/`, `/insurance/`, and `/faq/` to the canonical owner pages:

- `individual counseling in Louisville` -> `/individual-counseling/`
- `case management in Louisville` -> `/case-management/`
- `community-based mental health services` -> `/community-based-services/`
- `in-home mental health therapy` -> `/therapy-in-your-home/`
- `child therapy in Louisville` -> `/child-counseling/`
- `teen therapy for anxiety, depression, or trauma` -> `/adolescent-counseling/`
- `family therapy and care coordination` -> `/family-therapy/` and `/community-based-services/`
- `Medicaid mental health services` -> `/insurance/` and `/faq/cost/`

## Priority Page Changes

### Homepage `/`

Current gap: no visible FAQ section, no Medicaid mention in sampled text, and broad therapy/counseling head terms are present but not strongly answered.

Add a section after the opening/get-started block:

```text
Therapy, counseling, and mental health services in Louisville, KY

Louisville Mental Health Group provides therapy, counseling, EMDR, family therapy, case management, community support, and in-home mental health services from one Louisville practice. Adults, teens, children, couples, and families can contact the office to ask about provider fit, availability, Medicaid or commercial insurance, telehealth, in-person visits, and community-based options.

If you are not sure which service fits, start with what is happening now. The office can help compare individual counseling, child therapy, family therapy, case management, community support, group therapy, trauma therapy, and in-home support.
```

Add homepage FAQs:

- `Does Louisville Mental Health Group offer therapy in Louisville, KY?`
  - `Yes. LMHG offers therapy and counseling services in Louisville, Kentucky, including individual counseling, child therapy, family therapy, couples counseling, trauma therapy, EMDR, group therapy, case management, and community-based support when available and clinically appropriate.`
- `Do I need to know which service I need before contacting LMHG?`
  - `No. You can start by describing the main concern, the age of the client, preferred setting, insurance questions, and timing needs. The office can help route the request to a practical starting point.`
- `Does LMHG accept Medicaid or insurance?`
  - `LMHG works with commercial insurance and Medicaid plans, but coverage and current availability should be verified before care begins. Call or use intake with your insurance details and the service you are considering.`
- `Can mental health care happen outside the office?`
  - `Some services may be available by telehealth, in home, at school, or in the community when the service, location, provider availability, and clinical fit support that setting.`

### Services Hub `/services/`

Current gap: good service breadth, but the page should more directly own `mental health services Louisville KY`, `therapy Louisville KY`, `counseling Louisville KY`, and `therapist Louisville KY` intent.

Add a comparison block:

```text
Which LMHG service fits your situation?

If you are looking for therapy in Louisville, start with individual counseling, child therapy, family therapy, couples counseling, or trauma therapy. If the challenge is less about one weekly therapy session and more about resources, appointments, transportation, benefits, family coordination, or staying connected to care, case management or community-based services may be the better first question.

LMHG can also talk through whether care should start in the Louisville office, by telehealth, in home, at school, or in the community when that setting is available and appropriate.
```

Add a small visible table:

| Need | Start With | Link |
| --- | --- | --- |
| Anxiety, depression, grief, stress, trauma, or life changes | Individual Counseling | `/individual-counseling/` |
| Child behavior, school stress, emotional regulation, or caregiver support | Child Therapy | `/child-counseling/` |
| Family conflict, routines, attachment, parenting stress, or transitions | Family Therapy | `/family-therapy/` |
| Practical barriers, appointments, benefits, referrals, or follow-through | Case Management | `/case-management/` |
| Support tied to home, school, court, or community settings | Community-Based Services | `/community-based-services/` |

Also normalize labels across navigation, headings, related cards, and page titles before launch. Prefer the public-facing labels already present in the Core30 plan: `Child Therapy`, `Teen Therapy`, `Court-Ordered Services`, `Community-Based Services`, and `In-Home Mental Health Therapy`.

### Individual Counseling `/individual-counseling/`

Current gap: only about 800 source-text characters; broad individual therapy/counseling intent needs more explicit condition, setting, and next-step copy.

Add:

```text
Individual counseling in Louisville gives adults and teens a private place to work on anxiety, depression, trauma, grief, stress, relationship strain, burnout, major life changes, or patterns that keep repeating. Sessions may focus on understanding symptoms, building coping strategies, improving communication, processing grief or trauma, and deciding what needs to change next.

This page is a good starting point if you are searching for therapy, counseling, or a therapist in Louisville but are not sure whether you need a specialty page. If anxiety or depression is the main concern, start here or review anxiety and depression therapy. If the concern involves a child, family pattern, care coordination, or community support, LMHG can help compare those paths.
```

Add FAQs:

- `What concerns do you address in individual counseling?`
- `Is individual counseling different from therapy?`
- `Can I ask about telehealth or in-person counseling?`
- `Can individual counseling help with anxiety, depression, trauma, grief, or stress?`
- `How do I choose a Louisville therapist?`

Recommended answer for the therapist-choice FAQ:

```text
Ask about licensure, experience with your concern, insurance fit, appointment setting, availability, and whether the provider can explain how treatment usually starts. You can also ask what happens if another service, such as family therapy, case management, or community-based support, is a better fit.
```

### Anxiety And Depression `/anxiety-depression-therapy/`

Current gap: absent in depth-100 for `anxiety therapy Louisville KY`; depression appears on desktop but absent mobile. The page needs to be more explicit and less thin.

Add:

```text
Anxiety and depression therapy in Louisville can help when worry, panic, sadness, low motivation, irritability, numbness, sleep changes, work stress, school pressure, relationship strain, or daily responsibilities start to feel harder to manage. Therapy may focus on understanding symptoms, identifying triggers, reducing avoidance, strengthening routines, improving communication, and building a realistic plan for support.

LMHG works with adults, teens, and families whose anxiety or depression is connected to stress at home, school, work, relationships, grief, trauma, or major life changes. If another service is a better fit, such as individual counseling, teen therapy, trauma therapy, EMDR, family therapy, or case management, the office can help route the request.
```

Add FAQs:

- `How do I know if anxiety or depression therapy is a good starting point?`
- `Can therapy help when anxiety and depression affect work, school, or family life?`
- `Can teens get help for anxiety or depression at LMHG?`
- `Should I ask about individual counseling, trauma therapy, or EMDR instead?`

### Case Management `/case-management/`

Current gap: strong existing ranking but vulnerable to competitors with more explicit Medicaid and targeted-case-management language. Preserve this route as the canonical owner.

Do not create a duplicate `targeted-case-management` page before launch unless the operational service name requires it. Add the phrase on this page only if it is clinically, billing, and compliance accurate.

Add:

```text
Mental health case management in Louisville helps clients organize the practical parts of care that can be hard to manage alone. Support may include care coordination, referrals, appointment follow-through, insurance or Medicaid questions, transportation barriers, school or family coordination, housing or benefits stress, and connection to community resources.

Case management may be a fit when mental health needs are connected to several systems at once. It can help turn scattered tasks into a clearer plan so the client and family know what to do next, who to contact, and how services fit together.

For Medicaid clients, case management may be especially relevant when practical barriers are making it harder to stay connected to treatment. Coverage, eligibility, provider availability, and service fit should be verified before care begins.
```

Add FAQs:

- `Does LMHG offer mental health case management in Louisville, KY?`
- `Is case management available for Medicaid clients?`
- `What can a mental health case manager help with?`
- `Is case management the same as therapy?`
- `Can case management connect with in-home or community-based services?`

Recommended answer:

```text
Case management is not the same as therapy. Therapy focuses on clinical treatment goals, symptoms, relationships, trauma, or coping patterns. Case management focuses on care coordination, resources, referrals, appointments, barriers, and follow-through. Some clients may use both when that fits their needs.
```

### Community-Based Services `/community-based-services/`

Current gap: only about 700 source-text characters; Google AI Mode and local SERPs favor government/resource pages because they explain what community-based care means.

Add:

```text
Community-based mental health services connect treatment goals with the places and systems clients already navigate. For LMHG, this may include case management, community support, in-home help, school-related coordination, family support, referrals, resources, and follow-through outside a traditional office visit when the service and client needs fit.

This page is the best starting point when the question is not just "Can I talk to a therapist?" but "Can someone help me stay connected to care, manage appointments, coordinate resources, or use support in real life?" Community-based support can be especially useful when transportation, family stress, school needs, court expectations, benefits, housing stress, or multiple services are part of the concern.
```

Add FAQs:

- `What are community-based mental health services?`
- `Who provides community-based mental health services in Louisville?`
- `How are community-based services different from office therapy?`
- `Can community-based services include in-home or school-related support?`
- `Can community-based services help with care coordination?`

### Community Support `/community-support/`

Current gap: decent text, but it should connect more directly to the exact query phrase `community support services Louisville KY mental health`.

Add:

```text
Community support services focus on the everyday systems that affect mental health stability. That can include help organizing appointments, following through with referrals, connecting to resources, coordinating with family or school supports, managing transportation barriers, and building routines that make treatment goals easier to use outside the office.
```

Add FAQs:

- `What is community support in mental health care?`
- `Can community support help with appointments, resources, or transportation barriers?`
- `Is community support the same as case management?`
- `How do I ask if community support is available?`

### In-Home Therapy `/therapy-in-your-home/`

Current gap: supplemental SERP shows `in home therapy Louisville KY` is polluted by physical therapy and home health results. The page must say mental health repeatedly and naturally.

Recommended title/H1 adjustment:

- Title: `In-Home Mental Health Therapy in Louisville, KY`
- H1: `In-Home Mental Health Therapy in Louisville, KY`

Add:

```text
In-home mental health therapy is different from in-home physical therapy or home health care. LMHG's home-based mental health support is for clients and families whose emotional, behavioral, family, or access needs may be better understood in the context of daily routines.

Home-based care may be useful when transportation, child behavior, family stress, caregiving demands, school concerns, or household routines make office-only care less practical. Availability depends on service fit, provider availability, location, insurance, and whether in-home work is clinically appropriate.
```

Add FAQs:

- `Does LMHG offer in-home mental health therapy in Louisville?`
- `Is in-home therapy the same as physical therapy or home health?`
- `Who is a good fit for home-based mental health support?`
- `Can in-home therapy connect with case management or community support?`
- `How do I ask whether in-home care is available?`

### Child Therapy `/child-counseling/`

Current gap: page title is `Child Therapy`, source text is thin, and supplemental SERP shows competitors winning with `child therapist`, age ranges, parent involvement, and play therapy language.

Recommended title/H1 adjustment:

- Title: `Child Therapy in Louisville, KY`
- H1: `Child Therapy in Louisville, KY`

Add:

```text
Child therapy in Louisville can support children who are struggling with emotional regulation, behavior concerns, school stress, trauma, anxiety, sadness, family transitions, grief, or changes at home. Caregiver involvement is often important because younger children usually need adults to help carry new skills into daily routines.

LMHG can help families ask whether child counseling, play therapy, child behavioral intervention, parenting support, family therapy, or another service is the most practical first step. The office can also talk through insurance questions, provider fit, and whether care should involve the office, telehealth, school coordination, in-home support, or community-based services when appropriate.
```

Add GBP-derived FAQ:

- `Can a four-year-old have individual sessions at LMHG?`
  - `LMHG has worked with four-year-old children before. With younger children, family or caregiver involvement is usually more central to care, because support often needs to connect with routines, behavior patterns, and adult responses at home. Each situation is different, so families should contact the office to ask about fit, availability, and the best starting service.`

Add additional FAQs:

- `What concerns can child therapy help with?`
- `Do parents or caregivers participate in child therapy?`
- `Is play therapy available?`
- `How do I know whether my child needs child therapy, family therapy, or parenting support?`

### Teen Therapy `/adolescent-counseling/`

Current gap: AI prompts for teens with anxiety, depression, or trauma did not mention LMHG. The page has a good start but should answer this exact prompt.

Add:

```text
Teen therapy at LMHG may help adolescents dealing with anxiety, depression, trauma responses, school stress, social pressure, identity stress, emotional shutdown, irritability, family conflict, grief, or overwhelm. Some teens need individual counseling first. Others benefit from family involvement, parent support, trauma therapy, EMDR, or community-based help when daily systems are part of the concern.
```

Add FAQs:

- `Does LMHG help teens with anxiety, depression, or trauma?`
- `Can parents be involved in teen therapy?`
- `How do we choose between teen therapy, family therapy, and trauma therapy?`
- `Can teen therapy connect with school or community-based support?`

### Family Therapy `/family-therapy/`

Current gap: about 650 source-text characters; AI prompts for family therapy plus care coordination did not mention LMHG.

Add:

```text
Family therapy in Louisville helps families work on communication, routines, parenting stress, trust, attachment concerns, conflict, grief, trauma impact, and major transitions. The goal is not to blame one person. The goal is to understand the pattern between people and build more stable ways to respond.

Family therapy may connect with child therapy, teen therapy, parenting support, co-parenting services, reunification work, case management, or community-based services when a family needs both relationship support and practical coordination.
```

Add FAQs:

- `What can family therapy help with?`
- `Can family therapy include care coordination or case management?`
- `When should we choose family therapy instead of individual therapy?`
- `Can family therapy help with parenting stress, attachment, or conflict?`

### Group Therapy `/group-therapy/`

Current gap: good rankings but thin copy. Preserve and expand.

Add:

```text
Group therapy in Louisville can provide structured support, guided discussion, shared learning, skill practice, and connection with others working on similar concerns. Group therapy may be used alongside individual counseling, family therapy, or other supports when that combination fits the client.
```

Add FAQs:

- `What kinds of concerns can group therapy support?`
- `Is group therapy a replacement for individual counseling?`
- `How do I ask which groups are currently available?`
- `Can insurance or Medicaid apply to group therapy?`

### Insurance `/insurance/` And Cost FAQ `/faq/cost/`

Current gap: the site has a useful Medicaid page and cost FAQ, but DataForSEO SERPs show insurance-filtered directories winning for Medicaid therapist queries. LMHG should answer the query directly without overpromising.

Add to `/insurance/`:

```text
If you are searching for a therapist in Louisville who accepts Medicaid, contact LMHG with your plan details and the service you are considering. LMHG commonly works with Medicaid and commercial insurance plans, but coverage, eligibility, provider availability, and service fit should be verified before care begins.
```

Add FAQs:

- `Can I see a therapist at LMHG with Medicaid?`
- `What insurance information should I provide before scheduling?`
- `Does Medicaid coverage apply to therapy, case management, community support, or in-home services?`
- `What should I do if I am not sure which service my plan covers?`

### Specialties Hub `/specialties/`

Current gap: the page overlaps the service hub but should behave more like a symptom/need finder.

Add a section organized around user needs:

```text
Find support by concern

Some visitors start with a service name. Others start with what is happening: anxiety, depression, trauma, child behavior, parenting stress, family conflict, relationship strain, school pressure, court-related family stress, or barriers to staying connected to care. Use these specialty pages to narrow the concern, then move to the service page that fits the next step.
```

Recommended grouping:

- Anxiety, depression, stress, grief, burnout -> `/anxiety-depression-therapy/`, `/adult-counseling/`, `/individual-counseling/`
- Trauma, EMDR, distressing memories, triggers -> `/trauma-therapy/`, `/emdr-therapy/`
- Child behavior, school stress, emotional regulation -> `/child-counseling/`, `/play-therapy/`, `/child-behavioral-intervention/`
- Teen anxiety, depression, trauma, social pressure -> `/adolescent-counseling/`
- Parenting, attachment, family conflict -> `/parenting-support/`, `/attachment-therapy/`, `/family-therapy/`
- Court-involved family needs -> `/court-ordered/`, `/co-parenting/`, `/family-reunification/`
- Practical barriers and care coordination -> `/case-management/`, `/community-support/`, `/community-based-services/`

### Meet The Team `/meet-the-team/`

Current gap: trust and provider-fit signals are thin. AI answers and local SERPs often cite directories because they expose clinician details, categories, and review/provider snippets.

Launch fix:

- Add provider cards if available: name, credentials, role, population focus, service focus, and settings.
- Add a short answer block: `How LMHG matches clients with providers`.
- Link provider-fit language back to `/contact-us/`, `/individual-counseling/`, `/child-counseling/`, `/family-therapy/`, and `/insurance/`.
- Avoid unsupported claims about availability or specialties that are not confirmed.

### Reviews `/reviews/`

Current gap: GBP has `4.4` rating from `19` reviews, but the site should not overstate or fabricate review content.

Launch fix:

- Add a review/trust explanation page that states how visitors can evaluate fit, where public reviews may appear, and how to contact the office with concerns.
- If embedding or quoting reviews, use only approved, compliant review text and avoid PHI.
- Link to contact and service pages rather than turning reviews into a hard-sell page.

### Articles

Current gap: article pages are useful but sampled metadata is weak, and article pages should reinforce service owners.

Launch fix:

- Add clean article meta descriptions.
- Emit Article schema for article pages.
- Add summaries near the top.
- Add contextual links from articles back to `/individual-counseling/`, `/family-therapy/`, `/services/`, `/faq/cost/`, and related service pages.
- Remove duplicate dated posts from feeds or redirect them to canonical article routes.

## FAQ Additions By Intent Cluster

### High-Intent Local Therapy FAQs

- `Who provides therapy in Louisville, KY?`
- `How do I find a Louisville therapist who fits my needs?`
- `What is the difference between therapy, counseling, and case management?`
- `Can I ask about availability before completing intake?`

### Medicaid And Insurance FAQs

- `Does LMHG accept Medicaid for mental health services?`
- `Can Medicaid cover case management or community-based support?`
- `What should I bring when asking about insurance coverage?`
- `Can LMHG confirm whether my plan is accepted before scheduling?`

### Community-Based And In-Home FAQs

- `What are community-based mental health services?`
- `Who offers in-home mental health support in Louisville?`
- `Is in-home therapy the same as physical therapy?`
- `Can community-based services help with school, court, family, or resource coordination?`

### Child, Teen, And Family FAQs

- `Can a four-year-old receive child therapy at LMHG?`
- `Do caregivers participate in child therapy?`
- `Does LMHG help teens with anxiety, depression, or trauma?`
- `How do we choose between child therapy, teen therapy, family therapy, and parenting support?`
- `Can family therapy connect with care coordination?`

## Internal Link Changes

Add or verify these exact anchor patterns:

- Homepage: `therapy and counseling in Louisville` -> `/services/`
- Homepage: `individual counseling` -> `/individual-counseling/`
- Homepage: `case management` -> `/case-management/`
- Homepage: `community-based services` -> `/community-based-services/`
- Homepage: `in-home mental health support` -> `/therapy-in-your-home/`
- `/services/`: `therapist in Louisville` -> `/individual-counseling/`
- `/services/`: `child therapist in Louisville` -> `/child-counseling/`
- `/services/`: `family therapy and care coordination` -> `/family-therapy/` and `/community-based-services/`
- `/insurance/`: `Medicaid case management` -> `/case-management/`
- `/faq/cost/`: `Medicaid mental health services` -> `/insurance/`
- `/locations/in-person/`: `therapy at the Bardstown Road office` -> `/individual-counseling/`

## Launch Validation Checklist

Before launch:

- Confirm production host has no blocking `X-Robots-Tag`, `noindex`, or `nofollow` directives.
- Confirm `/robots.txt`, sitemap, and `/llms.txt` are reachable on the launch host.
- Confirm each priority page has a clean title, clean meta description, one H1, canonical URL, and no breadcrumb-polluted description.
- Confirm visible FAQ content renders and matching `FAQPage` JSON-LD appears only where visible FAQs exist.
- Confirm service pages include `LocalBusiness` or service/provider JSON-LD with LMHG name, URL, Bardstown Road address, and Louisville/Jefferson County area served.
- Rerun DataForSEO OnPage after the copy/schema changes.
- Rerun the five supplemental SERP checks and the original LLM/AI prompt set after launch.

## First 10 Launch Tasks

1. Fix meta-description generation so breadcrumb text is excluded.
2. Remove duplicate dated article/default post exposure from RSS feeds, or redirect feed-discoverable duplicates to canonical article routes.
3. Fix or verify FAQPage JSON-LD for visible FAQ sections.
4. Expand `/case-management/` while preserving it as the canonical owner.
5. Expand `/community-based-services/`, `/community-support/`, and `/therapy-in-your-home/` with direct community/in-home answer language.
6. Rename or retitle `/child-counseling/` visible H1/title to `Child Therapy in Louisville, KY`.
7. Add the GBP-derived four-year-old FAQ to `/child-counseling/`.
8. Expand `/individual-counseling/` and `/anxiety-depression-therapy/` for broad therapy/counseling and condition intent.
9. Add homepage FAQs plus direct therapy/counseling/mental-health-services and Medicaid answer blocks.
10. Strengthen internal links from homepage, service hub, specialties hub, location pages, insurance, and FAQ pages to canonical service owners.
