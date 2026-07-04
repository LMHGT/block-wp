# Gutenberg Template Map

Generated: 2026-06-30T14:42:37.414Z

Status: planned only. No WordPress page, theme, template, metadata, or visible surface was changed.

## Confirmed Requirements

- Carry the approved page/template categories into the fresh WordPress site.
- Give each approved category a distinct Gutenberg block-theme template.
- Keep one consistent header template part and one consistent footer template part across all templates.
- Rename the visible/core-service label for /child-counseling/ from Child Counseling to Child Therapy.
- Continue using LMHG plugin data as review material only until each part is marked clean or dirty.

## Shared Template Parts

| Part | Target file | Area | Applies to |
|---|---|---|---|
| Header | parts/header.html | header | all templates |
| Footer | parts/footer.html | footer | all templates |

## Template Files

| Template ID | Target file | Assigned pages | Gate |
|---|---:|---:|---|
| home | templates/front-page.html | 1 | homepage title and design transplant must be approved before WordPress write |
| services-hub | templates/services-hub.html | 1 | separate from home and service detail templates; approved by user as carried forward |
| service | templates/service-page.html | 8 | core service page family approved as correct; per-page copy/title review still required |
| specialties-hub | templates/specialties-hub.html | 1 | hub layout must be reviewed before WordPress write |
| specialty | templates/specialty-page.html | 15 | design transplant must be reviewed one subtype at a time |
| location-access | templates/location-access-page.html | 10 | avoid doorway-page patterns; local claims require review before publishing |
| article-hub | templates/article-hub.html | 1 | requires decision whether articles remain pages or move to posts |
| article | templates/article-page.html | 5 | requires decision whether article routes stay pages or become posts |
| faq-hub | templates/faq-hub.html | 1 | FAQ schema only after visible FAQ content is confirmed |
| faq | templates/faq-page.html | 3 | FAQ schema only after visible FAQ content is confirmed |
| trust | templates/trust-page.html | 5 | contact/team/careers/reviews may need pattern variants after user review |
| legal-utility | templates/legal-utility-page.html | 3 | plain content import only after user approves source text and legal/compliance handling |
| not-found | templates/404.html | 1 | planned; old /not-found/ page content should not be treated as canonical until reviewed |
| needs-classification | templates/page.html | 1 | do not implement sample/unclassified page unless user approves keeping it |

The block theme must also include `templates/index.html` as the required fallback.

## Theme JSON Registration Plan

Custom page templates to register later: `services-hub`, `service-page`, `specialties-hub`, `specialty-page`, `location-access-page`, `article-hub`, `article-page`, `faq-hub`, `faq-page`, `trust-page`, `legal-utility-page`.

Template parts to register later: `header`, `footer`.

## Page Assignments

| Path | Working title | Source family | Page type | Planned template |
|---|---|---|---|---|
| / | Mental Health Clinic in Louisville, KY | home | Homepage | templates/front-page.html |
| /adolescent-counseling/ | Teen Therapy in Louisville, KY | specialty | Audience/concern page | templates/specialty-page.html |
| /adult-counseling/ | Adult Counseling in Louisville, KY | specialty | Service | templates/specialty-page.html |
| /anxiety-depression-therapy/ | Anxiety and Depression Therapy in Louisville, KY | specialty | Concern page | templates/specialty-page.html |
| /articles/ | Mental Health Articles from Louisville Mental Health Group | article | Article hub | templates/article-hub.html |
| /articles/family-therapy-vs-individual-therapy/ | Family Therapy vs. Individual Therapy / Which Fits? | article | Article | templates/article-page.html |
| /articles/guide-to-individual-therapy/ | Guide to Individual Therapy / What to Expect | article | Article | templates/article-page.html |
| /articles/how-to-talk-to-your-loved-ones-about-going-to-therapy/ | How to Talk to Someone About Therapy | article | Article | templates/article-page.html |
| /articles/top-5-signs-its-time-to-seek-therapy/ | When to Seek Therapy / 5 Signs Support May Help | article | Article | templates/article-page.html |
| /articles/what-to-expect-when-starting-therapy/ | What to Expect When Starting Therapy | article | Article | templates/article-page.html |
| /attachment-therapy/ | Attachment Therapy in Louisville, KY | specialty | Service | templates/specialty-page.html |
| /bullitt-county-ky/ | Mental Health Services in Bullitt County, KY | location-access | Service area | templates/location-access-page.html |
| /careers/ | Mental Health Careers in Louisville, KY | trust | Careers page | templates/trust-page.html |
| /case-management/ | Targeted Case Management in Louisville, KY | specialty | Community-service page | templates/specialty-page.html |
| /child-behavioral-intervention/ | Child Behavioral Therapy in Louisville, KY | specialty | Service | templates/specialty-page.html |
| /child-counseling/ | Child Therapy in Louisville, KY | service | Broad service category | templates/service-page.html |
| /co-parenting/ | Co-Parenting Services in Louisville, KY | specialty | Court-service page | templates/specialty-page.html |
| /community-based-services/ | Community-Based Mental Health Services in Louisville, KY | service | Broad service category | templates/service-page.html |
| /community-support/ | Community Support Services in Louisville, KY | specialty | Community-service page | templates/specialty-page.html |
| /compliance/ | Mental Health Compliance in Louisville, KY | legal-utility | Utility/compliance | templates/legal-utility-page.html |
| /contact-us/ | Contact Louisville Mental Health Group | trust | Contact page | templates/trust-page.html |
| /couples-conflict-resolution/ | Couples Conflict Resolution in Louisville, KY | specialty | Concern page | templates/specialty-page.html |
| /couples-counseling/ | Couples Counseling in Louisville, KY | service | Broad service category | templates/service-page.html |
| /court-ordered/ | Court-Ordered Services in Louisville, KY | service | Broad service category | templates/service-page.html |
| /emdr-therapy/ | EMDR Therapy in Louisville, KY | specialty | Specialty/modality | templates/specialty-page.html |
| /family-reunification/ | Family Reunification Services in Louisville, KY | specialty | Court-service page | templates/specialty-page.html |
| /family-therapy/ | Family Therapy in Louisville, KY | service | Broad service category | templates/service-page.html |
| /faq/ | Mental Health Services FAQ in Louisville, KY | faq | FAQ hub | templates/faq-hub.html |
| /faq/about-lmhg/ | About Louisville Mental Health Group / Therapy and Support | faq | FAQ/support | templates/faq-page.html |
| /faq/cost/ | Therapy Cost in Louisville, KY | faq | FAQ/support | templates/faq-page.html |
| /faq/our-approach/ | Louisville Mental Health Group Approach | faq | FAQ/support | templates/faq-page.html |
| /group-therapy/ | Group Therapy in Louisville, KY | service | Broad service category | templates/service-page.html |
| /individual-counseling/ | Individual Therapy in Louisville, KY | service | Broad service category | templates/service-page.html |
| /insurance/ | Medicaid Mental Health Services in Louisville, KY | trust | Access/support page | templates/trust-page.html |
| /jefferson-county-ky/ | Mental Health Services in Jefferson County, KY | location-access | Service area | templates/location-access-page.html |
| /locations/ | Mental Health Services Near Louisville, KY | location-access | Locations hub | templates/location-access-page.html |
| /locations/community/ | Community-Based Mental Health Care in Louisville, KY | location-access | Care setting | templates/location-access-page.html |
| /locations/in-home/ | In-Home Mental Health Services in Louisville, KY | location-access | Care setting | templates/location-access-page.html |
| /locations/in-person/ | In-Person Counseling in Louisville, KY | location-access | Care setting | templates/location-access-page.html |
| /locations/online/ | Online Therapy in Kentucky | location-access | Care setting | templates/location-access-page.html |
| /locations/school/ | School-Based Mental Health Support in Louisville, KY | location-access | Care setting | templates/location-access-page.html |
| /louisville-ky/ | Louisville Mental Health Services and Counseling | location-access | Service area | templates/location-access-page.html |
| /meet-the-team/ | Mental Health Providers in Louisville, KY | trust | Team hub | templates/trust-page.html |
| /not-found/ | Page Not Found | not-found | Not Found | templates/404.html |
| /oldham-county-ky/ | Mental Health Services in Oldham County, KY | location-access | Service area | templates/location-access-page.html |
| /parenting-support/ | Parenting Support in Louisville, KY | specialty | Concern page | templates/specialty-page.html |
| /play-therapy/ | Play Therapy in Louisville, KY | specialty | Service | templates/specialty-page.html |
| /privacy-policy/ | Privacy Policy for Louisville Mental Health Group | legal-utility | Utility/legal | templates/legal-utility-page.html |
| /relationship-counseling/ | Relationship Counseling in Louisville, KY | specialty | Service | templates/specialty-page.html |
| /reviews/ | Louisville Mental Health Group Reviews / Client Feedback | trust | Trust page | templates/trust-page.html |
| /sample-page/ | Sample Page | needs-classification | Needs Classification | templates/page.html |
| /services/ | Mental Health Services in Louisville, KY | service | Primary hub | templates/services-hub.html |
| /specialties/ | Mental Health Specialties in Louisville, KY | specialty | Specialties hub | templates/specialties-hub.html |
| /terms-of-use/ | Terms of Use for Louisville Mental Health Group | legal-utility | Utility/legal | templates/legal-utility-page.html |
| /therapy-in-your-home/ | In-Home Therapy in Louisville, KY | specialty | Community/in-home service | templates/specialty-page.html |
| /trauma-therapy/ | Trauma Therapy in Louisville, KY | service | Broad service category | templates/service-page.html |

## Services Nested Menu

Status: planning only; implement later in `parts/header.html` with a Gutenberg Navigation block.

- Services (`/services/`)
  - Individual Counseling (`/individual-counseling/`)
    - Adult Counseling (`/adult-counseling/`)
    - Anxiety and Depression Therapy (`/anxiety-depression-therapy/`)
  - Child Therapy (`/child-counseling/`)
    - Adolescent Counseling (`/adolescent-counseling/`)
    - Play Therapy (`/play-therapy/`)
    - Child Behavioral Intervention (`/child-behavioral-intervention/`)
  - Family Therapy (`/family-therapy/`)
    - Attachment Therapy (`/attachment-therapy/`)
    - Parenting Support (`/parenting-support/`)
  - Couples Counseling (`/couples-counseling/`)
    - Couples Conflict Resolution (`/couples-conflict-resolution/`)
    - Relationship Counseling (`/relationship-counseling/`)
  - Court Ordered Services (`/court-ordered/`)
    - Co-Parenting (`/co-parenting/`)
    - Family Reunification (`/family-reunification/`)
  - Community Based Services (`/community-based-services/`)
    - Case Management (`/case-management/`)
    - Community Support (`/community-support/`)
  - Group Therapy (`/group-therapy/`)
  - Trauma Therapy (`/trauma-therapy/`)
    - EMDR Therapy (`/emdr-therapy/`)

## Sitewide Header, Footer, And Breadcrumbs

- Header target: `parts/header.html` with native Navigation and Button blocks.
- Footer target: `parts/footer.html` with native Group, Columns, Navigation, Paragraph, and Button blocks.
- Breadcrumb target: Gutenberg-native block patterns generated from approved relationship data; no Astro component import and no URL-segment inference.
- Plan files: `.wp-gutenberg-designer/navigation/sitewide-navigation-plan.json` and `.wp-gutenberg-designer/navigation/sitewide-navigation-plan.md`.

## Open Decisions

- Homepage title: current source is `Mental Health Clinic in Louisville, KY`; services hub source is `Mental Health Services in Louisville, KY`.
- Article model: keep article routes as Pages using `templates/article-page.html`, or convert them to Posts using `templates/single.html`.
- Trust/support variants: decide whether Contact, Team, Careers, Reviews, and Insurance stay under one trust template with different patterns or need separate templates.
- Sample Page: discard it or assign a real category before implementation.

## Implementation Rules

- Use fresh Gutenberg templates and patterns only.
- No Astro, Starwind, legacy CSS, old template files, or copied block markup.
- Keep block templates in `templates/*.html` and template parts in flat `parts/*.html`.
- Do not use PHP inside block-template HTML files.
- Prefer core blocks such as Template Part, Group, Post Title, Post Content, Navigation, Query Loop, Details, Buttons, Columns, and List.
