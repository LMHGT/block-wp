# LMHG Reference Import Review Plan

Generated: 2026-06-30T12:10:56.541Z

Status: planning import only. No WordPress pages, metadata, templates, CSS, assets, or block markup were written.

## What Was Imported

| Dataset | Default Status | Intended Use | Not Allowed |
| --- | --- | --- | --- |
| Page topology and relationships | review-needed | Compare parent/child intent, related links, breadcrumbs, page grouping | Automatic relationship writes |
| SEO metadata and schema | candidate-clean | Review title/meta/schema/keyword rows against the public inventory | SEO plugin writes before approval |
| Page type and field contracts | candidate-mostly-clean | Separate templateFamily, faceted page type, schema, visible fields, hidden SEO fields | Collapsing all classifications into one page type |
| Brand/design/Gutenberg adaptation | requires-user-design-transplant-decision | Convert desired concepts into Gutenberg Basics patterns page type by page type | Astro, Starwind, CSS, visual asset, or block-markup transplant |

## Counts

- Public WordPress inventory pages: 56
- LMHG metadata rows: 54
- Matched public + LMHG rows: 54
- Public-only gaps: 2
- LMHG-only gaps: 0

## Review Buckets

- `candidate-clean`: likely usable, but still needs your approval before implementation.
- `candidate-mostly-clean`: likely directionally valid, but expects one-by-one review.
- `review-needed`: compare against your current intent before use.
- `requires-user-design-transplant-decision`: no implementation until you describe the Gutenberg Basics pattern you want.

## Page-Type Queues

### article

| Path | Source Title | Metadata | Page Type / Schema | Relationship | Design | Gutenberg Basics Starting Point |
| --- | --- | --- | --- | --- | --- | --- |
| /articles/ | Mental Health Articles from Louisville Mental Health Group | candidate-clean | candidate-mostly-clean: Article hub / Article | review-needed | requires-user-design-transplant-decision | Article planning surface: decide whether each article route stays a WordPress Page or becomes a Post before implementation; use clean headings, body copy, and optional FAQ only when visible. |
| /articles/family-therapy-vs-individual-therapy/ | Family Therapy vs. Individual Therapy \| Which Fits? | candidate-clean | candidate-mostly-clean: Article / Article | review-needed | requires-user-design-transplant-decision | Article planning surface: decide whether each article route stays a WordPress Page or becomes a Post before implementation; use clean headings, body copy, and optional FAQ only when visible. |
| /articles/guide-to-individual-therapy/ | Guide to Individual Therapy \| What to Expect | candidate-clean | candidate-mostly-clean: Article / Article | review-needed | requires-user-design-transplant-decision | Article planning surface: decide whether each article route stays a WordPress Page or becomes a Post before implementation; use clean headings, body copy, and optional FAQ only when visible. |
| /articles/how-to-talk-to-your-loved-ones-about-going-to-therapy/ | How to Talk to Someone About Therapy | candidate-clean | candidate-mostly-clean: Article / Article | review-needed | requires-user-design-transplant-decision | Article planning surface: decide whether each article route stays a WordPress Page or becomes a Post before implementation; use clean headings, body copy, and optional FAQ only when visible. |
| /articles/top-5-signs-its-time-to-seek-therapy/ | When to Seek Therapy \| 5 Signs Support May Help | candidate-clean | candidate-mostly-clean: Article / Article | review-needed | requires-user-design-transplant-decision | Article planning surface: decide whether each article route stays a WordPress Page or becomes a Post before implementation; use clean headings, body copy, and optional FAQ only when visible. |
| /articles/what-to-expect-when-starting-therapy/ | What to Expect When Starting Therapy | candidate-clean | candidate-mostly-clean: Article / Article | review-needed | requires-user-design-transplant-decision | Article planning surface: decide whether each article route stays a WordPress Page or becomes a Post before implementation; use clean headings, body copy, and optional FAQ only when visible. |

### faq

| Path | Source Title | Metadata | Page Type / Schema | Relationship | Design | Gutenberg Basics Starting Point |
| --- | --- | --- | --- | --- | --- | --- |
| /faq/ | Mental Health Services FAQ in Louisville, KY | candidate-clean | candidate-mostly-clean: FAQ hub / MedicalWebPage | review-needed | requires-user-design-transplant-decision | FAQ template: question groups using accessible heading structure and Details/Accordion-style blocks if approved; FAQ schema only when visible Q&A content exists. |
| /faq/about-lmhg/ | About Louisville Mental Health Group \| Therapy and Support | candidate-clean | candidate-mostly-clean: FAQ/support / AboutPage; FAQPage | review-needed | requires-user-design-transplant-decision | FAQ template: question groups using accessible heading structure and Details/Accordion-style blocks if approved; FAQ schema only when visible Q&A content exists. |
| /faq/cost/ | Therapy Cost in Louisville, KY | candidate-clean | candidate-mostly-clean: FAQ/support / MedicalWebPage; FAQPage | review-needed | requires-user-design-transplant-decision | FAQ template: question groups using accessible heading structure and Details/Accordion-style blocks if approved; FAQ schema only when visible Q&A content exists. |
| /faq/our-approach/ | Louisville Mental Health Group Approach | candidate-clean | candidate-mostly-clean: FAQ/support / MedicalWebPage; FAQPage | review-needed | requires-user-design-transplant-decision | FAQ template: question groups using accessible heading structure and Details/Accordion-style blocks if approved; FAQ schema only when visible Q&A content exists. |

### home

| Path | Source Title | Metadata | Page Type / Schema | Relationship | Design | Gutenberg Basics Starting Point |
| --- | --- | --- | --- | --- | --- | --- |
| / | Mental Health Clinic in Louisville, KY | candidate-clean | candidate-mostly-clean: Homepage / MedicalClinic | review-needed | requires-user-design-transplant-decision | Core block homepage: hero Group with Heading/Paragraph/Buttons, process Columns/List, service-category card Grid, closing CTA Group. No imported Astro/Starwind code; translate desired sections into Gutenberg patterns one at a time. |

### legal-utility

| Path | Source Title | Metadata | Page Type / Schema | Relationship | Design | Gutenberg Basics Starting Point |
| --- | --- | --- | --- | --- | --- | --- |
| /compliance/ | Mental Health Compliance in Louisville, KY | candidate-clean | candidate-mostly-clean: Utility/compliance / MedicalWebPage | review-needed | requires-user-design-transplant-decision | Legal/utility template: prose-first, protected copy, minimal CTA pressure, no AI rewriting of legal meaning without explicit review. |
| /privacy-policy/ | Privacy Policy for Louisville Mental Health Group | candidate-clean | candidate-mostly-clean: Utility/legal / MedicalWebPage | review-needed | requires-user-design-transplant-decision | Legal/utility template: prose-first, protected copy, minimal CTA pressure, no AI rewriting of legal meaning without explicit review. |
| /terms-of-use/ | Terms of Use for Louisville Mental Health Group | candidate-clean | candidate-mostly-clean: Utility/legal / MedicalWebPage | review-needed | requires-user-design-transplant-decision | Legal/utility template: prose-first, protected copy, minimal CTA pressure, no AI rewriting of legal meaning without explicit review. |

### location-access

| Path | Source Title | Metadata | Page Type / Schema | Relationship | Design | Gutenberg Basics Starting Point |
| --- | --- | --- | --- | --- | --- | --- |
| /bullitt-county-ky/ | Mental Health Services in Bullitt County, KY | candidate-clean | candidate-mostly-clean: Service area / MedicalWebPage | review-needed | requires-user-design-transplant-decision | Location/access template: one-office/multiple-setting language, setting facts, service links, conservative local SEO copy, CTA. Avoid doorway-page patterns. |
| /jefferson-county-ky/ | Mental Health Services in Jefferson County, KY | candidate-clean | candidate-mostly-clean: Service area / MedicalWebPage | review-needed | requires-user-design-transplant-decision | Location/access template: one-office/multiple-setting language, setting facts, service links, conservative local SEO copy, CTA. Avoid doorway-page patterns. |
| /locations/ | Mental Health Services Near Louisville, KY | candidate-clean | candidate-mostly-clean: Locations hub / MedicalWebPage | review-needed | requires-user-design-transplant-decision | Location/access template: one-office/multiple-setting language, setting facts, service links, conservative local SEO copy, CTA. Avoid doorway-page patterns. |
| /locations/community/ | Community-Based Mental Health Care in Louisville, KY | candidate-clean | candidate-mostly-clean: Care setting / MedicalWebPage | review-needed | requires-user-design-transplant-decision | Location/access template: one-office/multiple-setting language, setting facts, service links, conservative local SEO copy, CTA. Avoid doorway-page patterns. |
| /locations/in-home/ | In-Home Mental Health Services in Louisville, KY | candidate-clean | candidate-mostly-clean: Care setting / MedicalWebPage | review-needed | requires-user-design-transplant-decision | Location/access template: one-office/multiple-setting language, setting facts, service links, conservative local SEO copy, CTA. Avoid doorway-page patterns. |
| /locations/in-person/ | In-Person Counseling in Louisville, KY | candidate-clean | candidate-mostly-clean: Care setting / MedicalWebPage | review-needed | requires-user-design-transplant-decision | Location/access template: one-office/multiple-setting language, setting facts, service links, conservative local SEO copy, CTA. Avoid doorway-page patterns. |
| /locations/online/ | Online Therapy in Kentucky | candidate-clean | candidate-mostly-clean: Care setting / MedicalWebPage | review-needed | requires-user-design-transplant-decision | Location/access template: one-office/multiple-setting language, setting facts, service links, conservative local SEO copy, CTA. Avoid doorway-page patterns. |
| /locations/school/ | School-Based Mental Health Support in Louisville, KY | candidate-clean | candidate-mostly-clean: Care setting / MedicalWebPage | review-needed | requires-user-design-transplant-decision | Location/access template: one-office/multiple-setting language, setting facts, service links, conservative local SEO copy, CTA. Avoid doorway-page patterns. |
| /louisville-ky/ | Louisville Mental Health Services and Counseling | candidate-clean | candidate-mostly-clean: Service area / MedicalWebPage | review-needed | requires-user-design-transplant-decision | Location/access template: one-office/multiple-setting language, setting facts, service links, conservative local SEO copy, CTA. Avoid doorway-page patterns. |
| /oldham-county-ky/ | Mental Health Services in Oldham County, KY | candidate-clean | candidate-mostly-clean: Service area / MedicalWebPage | review-needed | requires-user-design-transplant-decision | Location/access template: one-office/multiple-setting language, setting facts, service links, conservative local SEO copy, CTA. Avoid doorway-page patterns. |

### needs-classification

| Path | Source Title | Metadata | Page Type / Schema | Relationship | Design | Gutenberg Basics Starting Point |
| --- | --- | --- | --- | --- | --- | --- |
| /sample-page/ | Sample Page | missing-from-lmhg-metadata | needs-classification: Needs Classification / MedicalWebPage | review-needed | requires-user-design-transplant-decision | Needs Gutenberg implementation pattern after classification for /sample-page/. |

### not-found

| Path | Source Title | Metadata | Page Type / Schema | Relationship | Design | Gutenberg Basics Starting Point |
| --- | --- | --- | --- | --- | --- | --- |
| /not-found/ | Page Not Found | missing-from-lmhg-metadata | needs-classification: Not Found / MedicalWebPage | review-needed | requires-user-design-transplant-decision | Operational 404 template: short message, search/route links, no SEO migration claims. |

### service

| Path | Source Title | Metadata | Page Type / Schema | Relationship | Design | Gutenberg Basics Starting Point |
| --- | --- | --- | --- | --- | --- | --- |
| /child-counseling/ | Child Counseling in Louisville, KY | candidate-clean | candidate-mostly-clean: Broad service category / MedicalWebPage; FAQPage | review-needed | requires-user-design-transplant-decision | Broad service template: hero Group, service-routing card Grid, explanatory sections, related services, FAQ section, CTA. Use core Group/Columns/List/Buttons/Details where possible. |
| /community-based-services/ | Community-Based Mental Health Services in Louisville, KY | candidate-clean | candidate-mostly-clean: Broad service category / MedicalWebPage; FAQPage | review-needed | requires-user-design-transplant-decision | Broad service template: hero Group, service-routing card Grid, explanatory sections, related services, FAQ section, CTA. Use core Group/Columns/List/Buttons/Details where possible. |
| /couples-counseling/ | Couples Counseling in Louisville, KY | candidate-clean | candidate-mostly-clean: Broad service category / MedicalWebPage; FAQPage | review-needed | requires-user-design-transplant-decision | Broad service template: hero Group, service-routing card Grid, explanatory sections, related services, FAQ section, CTA. Use core Group/Columns/List/Buttons/Details where possible. |
| /court-ordered/ | Court-Ordered Services in Louisville, KY | candidate-clean | candidate-mostly-clean: Broad service category / MedicalWebPage; FAQPage | review-needed | requires-user-design-transplant-decision | Broad service template: hero Group, service-routing card Grid, explanatory sections, related services, FAQ section, CTA. Use core Group/Columns/List/Buttons/Details where possible. |
| /family-therapy/ | Family Therapy in Louisville, KY | candidate-clean | candidate-mostly-clean: Broad service category / MedicalWebPage; FAQPage | review-needed | requires-user-design-transplant-decision | Broad service template: hero Group, service-routing card Grid, explanatory sections, related services, FAQ section, CTA. Use core Group/Columns/List/Buttons/Details where possible. |
| /group-therapy/ | Group Therapy in Louisville, KY | candidate-clean | candidate-mostly-clean: Broad service category / MedicalWebPage; FAQPage | review-needed | requires-user-design-transplant-decision | Broad service template: hero Group, service-routing card Grid, explanatory sections, related services, FAQ section, CTA. Use core Group/Columns/List/Buttons/Details where possible. |
| /individual-counseling/ | Individual Therapy in Louisville, KY | candidate-clean | candidate-mostly-clean: Broad service category / MedicalWebPage; FAQPage | review-needed | requires-user-design-transplant-decision | Broad service template: hero Group, service-routing card Grid, explanatory sections, related services, FAQ section, CTA. Use core Group/Columns/List/Buttons/Details where possible. |
| /services/ | Mental Health Services in Louisville, KY | candidate-clean | candidate-mostly-clean: Primary hub / MedicalWebPage | review-needed | requires-user-design-transplant-decision | Broad service template: hero Group, service-routing card Grid, explanatory sections, related services, FAQ section, CTA. Use core Group/Columns/List/Buttons/Details where possible. |
| /trauma-therapy/ | Trauma Therapy in Louisville, KY | candidate-clean | candidate-mostly-clean: Broad service category / MedicalWebPage; FAQPage | review-needed | requires-user-design-transplant-decision | Broad service template: hero Group, service-routing card Grid, explanatory sections, related services, FAQ section, CTA. Use core Group/Columns/List/Buttons/Details where possible. |

### specialty

| Path | Source Title | Metadata | Page Type / Schema | Relationship | Design | Gutenberg Basics Starting Point |
| --- | --- | --- | --- | --- | --- | --- |
| /adolescent-counseling/ | Teen Therapy in Louisville, KY | candidate-clean | candidate-mostly-clean: Audience/concern page / MedicalWebPage; FAQPage | review-needed | requires-user-design-transplant-decision | Specialty/service template: hero Group with optional aside, practical support sections, related services/specialties, FAQ, CTA. Start from Gutenberg core blocks and add custom block patterns only after approval. |
| /adult-counseling/ | Adult Counseling in Louisville, KY | candidate-clean | candidate-mostly-clean: Service / MedicalWebPage; FAQPage | review-needed | requires-user-design-transplant-decision | Specialty/service template: hero Group with optional aside, practical support sections, related services/specialties, FAQ, CTA. Start from Gutenberg core blocks and add custom block patterns only after approval. |
| /anxiety-depression-therapy/ | Anxiety and Depression Therapy in Louisville, KY | candidate-clean | candidate-mostly-clean: Concern page / MedicalWebPage; FAQPage | review-needed | requires-user-design-transplant-decision | Specialty/service template: hero Group with optional aside, practical support sections, related services/specialties, FAQ, CTA. Start from Gutenberg core blocks and add custom block patterns only after approval. |
| /attachment-therapy/ | Attachment Therapy in Louisville, KY | candidate-clean | candidate-mostly-clean: Service / MedicalWebPage; FAQPage | review-needed | requires-user-design-transplant-decision | Specialty/service template: hero Group with optional aside, practical support sections, related services/specialties, FAQ, CTA. Start from Gutenberg core blocks and add custom block patterns only after approval. |
| /case-management/ | Targeted Case Management in Louisville, KY | candidate-clean | candidate-mostly-clean: Community-service page / MedicalWebPage; FAQPage | review-needed | requires-user-design-transplant-decision | Specialty/service template: hero Group with optional aside, practical support sections, related services/specialties, FAQ, CTA. Start from Gutenberg core blocks and add custom block patterns only after approval. |
| /child-behavioral-intervention/ | Child Behavioral Therapy in Louisville, KY | candidate-clean | candidate-mostly-clean: Service / MedicalWebPage; FAQPage | review-needed | requires-user-design-transplant-decision | Specialty/service template: hero Group with optional aside, practical support sections, related services/specialties, FAQ, CTA. Start from Gutenberg core blocks and add custom block patterns only after approval. |
| /co-parenting/ | Co-Parenting Services in Louisville, KY | candidate-clean | candidate-mostly-clean: Court-service page / MedicalWebPage; FAQPage | review-needed | requires-user-design-transplant-decision | Specialty/service template: hero Group with optional aside, practical support sections, related services/specialties, FAQ, CTA. Start from Gutenberg core blocks and add custom block patterns only after approval. |
| /community-support/ | Community Support Services in Louisville, KY | candidate-clean | candidate-mostly-clean: Community-service page / MedicalWebPage; FAQPage | review-needed | requires-user-design-transplant-decision | Specialty/service template: hero Group with optional aside, practical support sections, related services/specialties, FAQ, CTA. Start from Gutenberg core blocks and add custom block patterns only after approval. |
| /couples-conflict-resolution/ | Couples Conflict Resolution in Louisville, KY | candidate-clean | candidate-mostly-clean: Concern page / MedicalWebPage; FAQPage | review-needed | requires-user-design-transplant-decision | Specialty/service template: hero Group with optional aside, practical support sections, related services/specialties, FAQ, CTA. Start from Gutenberg core blocks and add custom block patterns only after approval. |
| /emdr-therapy/ | EMDR Therapy in Louisville, KY | candidate-clean | candidate-mostly-clean: Specialty/modality / MedicalWebPage; FAQPage | review-needed | requires-user-design-transplant-decision | Specialty/service template: hero Group with optional aside, practical support sections, related services/specialties, FAQ, CTA. Start from Gutenberg core blocks and add custom block patterns only after approval. |
| /family-reunification/ | Family Reunification Services in Louisville, KY | candidate-clean | candidate-mostly-clean: Court-service page / MedicalWebPage; FAQPage | review-needed | requires-user-design-transplant-decision | Specialty/service template: hero Group with optional aside, practical support sections, related services/specialties, FAQ, CTA. Start from Gutenberg core blocks and add custom block patterns only after approval. |
| /parenting-support/ | Parenting Support in Louisville, KY | candidate-clean | candidate-mostly-clean: Concern page / MedicalWebPage; FAQPage | review-needed | requires-user-design-transplant-decision | Specialty/service template: hero Group with optional aside, practical support sections, related services/specialties, FAQ, CTA. Start from Gutenberg core blocks and add custom block patterns only after approval. |
| /play-therapy/ | Play Therapy in Louisville, KY | candidate-clean | candidate-mostly-clean: Service / MedicalWebPage; FAQPage | review-needed | requires-user-design-transplant-decision | Specialty/service template: hero Group with optional aside, practical support sections, related services/specialties, FAQ, CTA. Start from Gutenberg core blocks and add custom block patterns only after approval. |
| /relationship-counseling/ | Relationship Counseling in Louisville, KY | candidate-clean | candidate-mostly-clean: Service / MedicalWebPage; FAQPage | review-needed | requires-user-design-transplant-decision | Specialty/service template: hero Group with optional aside, practical support sections, related services/specialties, FAQ, CTA. Start from Gutenberg core blocks and add custom block patterns only after approval. |
| /specialties/ | Mental Health Specialties in Louisville, KY | candidate-clean | candidate-mostly-clean: Specialties hub / MedicalWebPage | review-needed | requires-user-design-transplant-decision | Specialty/service template: hero Group with optional aside, practical support sections, related services/specialties, FAQ, CTA. Start from Gutenberg core blocks and add custom block patterns only after approval. |
| /therapy-in-your-home/ | In-Home Therapy in Louisville, KY | candidate-clean | candidate-mostly-clean: Community/in-home service / MedicalWebPage; FAQPage | review-needed | requires-user-design-transplant-decision | Specialty/service template: hero Group with optional aside, practical support sections, related services/specialties, FAQ, CTA. Start from Gutenberg core blocks and add custom block patterns only after approval. |

### trust

| Path | Source Title | Metadata | Page Type / Schema | Relationship | Design | Gutenberg Basics Starting Point |
| --- | --- | --- | --- | --- | --- | --- |
| /careers/ | Mental Health Careers in Louisville, KY | candidate-clean | candidate-mostly-clean: Careers page / MedicalWebPage | review-needed | requires-user-design-transplant-decision | Trust/support template: warm but factual page structure for team, contact, insurance, reviews, careers. Contact/insurance/cost language must stay consistent. |
| /contact-us/ | Contact Louisville Mental Health Group | candidate-clean | candidate-mostly-clean: Contact page / ContactPage | review-needed | requires-user-design-transplant-decision | Trust/support template: warm but factual page structure for team, contact, insurance, reviews, careers. Contact/insurance/cost language must stay consistent. |
| /insurance/ | Medicaid Mental Health Services in Louisville, KY | candidate-clean | candidate-mostly-clean: Access/support page / MedicalWebPage | review-needed | requires-user-design-transplant-decision | Trust/support template: warm but factual page structure for team, contact, insurance, reviews, careers. Contact/insurance/cost language must stay consistent. |
| /meet-the-team/ | Mental Health Providers in Louisville, KY | candidate-clean | candidate-mostly-clean: Team hub / AboutPage | review-needed | requires-user-design-transplant-decision | Trust/support template: warm but factual page structure for team, contact, insurance, reviews, careers. Contact/insurance/cost language must stay consistent. |
| /reviews/ | Louisville Mental Health Group Reviews \| Client Feedback | candidate-clean | candidate-mostly-clean: Trust page / MedicalWebPage | review-needed | requires-user-design-transplant-decision | Trust/support template: warm but factual page structure for team, contact, insurance, reviews, careers. Contact/insurance/cost language must stay consistent. |

## Implementation Rule

For each page type, the next step is a human review loop: mark source fields clean or dirty, choose what to transplant conceptually, then implement in Gutenberg Basics using fresh blocks/patterns only. No old code or markup should cross the boundary.
