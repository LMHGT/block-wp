# CORE 30 Keyword And Information Architecture

Last updated: 2026-06-24

## Purpose

This document is the durable reference for:

- CORE 30 commercial page structure
- primary and secondary keyword targets
- faceted page relationships
- redundancy and cannibalization controls
- article topics that should absorb overlapping or informational demand
- structured-output alignment with [core30-keyword-architecture.json](/Users/tyler-lcsw/projects/lmhg-astro-integrate/docs/seo/core30-keyword-architecture.json)

Use this document when making:

- page-title decisions
- H1 and section-heading decisions
- meta description decisions
- internal-linking decisions
- structured content exports
- JSON generation or automation inputs

## Source Of Truth

This reference is based on:

- implemented site structure in [src/data/core30.ts](/Users/tyler-lcsw/projects/lmhg-astro-integrate/src/data/core30.ts)
- current service hub in [src/pages/services/index.astro](/Users/tyler-lcsw/projects/lmhg-astro-integrate/src/pages/services/index.astro)
- CORE 30 repo philosophy in [CORE_30.md](/Users/tyler-lcsw/projects/lmhg-astro-integrate/CORE_30.md)
- supporting methodology notes in [core-30/CORE30_IMPLEMENTATION.md](/Users/tyler-lcsw/projects/lmhg-astro-integrate/core-30/CORE30_IMPLEMENTATION.md), [core-30/CORE30_ANALYSIS.md](/Users/tyler-lcsw/projects/lmhg-astro-integrate/core-30/CORE30_ANALYSIS.md), and [core-30/gmb-services.md](/Users/tyler-lcsw/projects/lmhg-astro-integrate/core-30/gmb-services.md)
- current service-area decisions in [core-30/LOCATION_ANALYSIS.md](/Users/tyler-lcsw/projects/lmhg-astro-integrate/core-30/LOCATION_ANALYSIS.md)
- Google guidance reviewed 2026-05-11:
  - local ranking factors
  - business category and services completeness
  - helpful-content guidance
  - doorway-page risk
  - title-link and crawlable-link best practices

## Working Principles

1. CORE 30 here means exact or near-exact alignment between the public site and the business entity represented by the Google Business Profile.
2. Homepage and service hub should carry the broadest `mental health` language.
3. Category and service pages should carry the strongest `therapy` and service-intent language.
4. `Counseling` remains important because it still matches several core public labels and GBP-adjacent language, but it should usually be a supporting phrase rather than the only target phrase.
5. One commercial page should own one main intent. Close variants should normally live on the same page, not as separate competing pages.
6. Location and delivery-mode pages should support service discovery and local relevance without duplicating service pages.
7. Overlapping or informational demand should be absorbed by articles rather than creating near-duplicate commercial pages.
8. Stable commercial pages should remain code-managed and SEO-sensitive.
9. The site uses a faceted parent-context model, not a strict category-to-leaf tree. A page can have one canonical parent while also linking to secondary service categories, care settings, specialties, concern pages, and service areas.
10. Do not globally standardize on only `therapy` or only `counseling` until keyword research resolves page-level ownership.
11. Breadcrumbs and related-page sections are generated from [src/data/page-relationships.ts](/Users/tyler-lcsw/projects/lmhg-astro-integrate/src/data/page-relationships.ts). Do not add a separate route-parser breadcrumb system or Astro-only package as the source of truth.
12. NocoBase relationship briefs may draft `primary-parent` rows and related-link buckets, but production breadcrumbs, `BreadcrumbList` JSON-LD, and visible related-page links must remain graph-backed in the static Astro build.

## Keyword Strategy Summary

### Lead Terms By Page Layer

| Layer | Lead Language | Notes |
|---|---|---|
| Homepage | `mental health clinic`, `mental health services` | Broad entity and trust intent |
| Services hub | `mental health services`, `therapy` | Commercial navigation hub |
| Category pages | `therapy` + current category name | Category ownership |
| Specialties / specific pages | exact service, modality, audience, court, or concern intent | One main intent per page; can connect to multiple parent contexts |
| Articles | questions, comparisons, modifiers | Absorb overlap and informational demand |

### Keep As Supporting Terms, Not New Page Families

- `therapy louisville ky`
- `anxiety therapy louisville ky`
- `depression therapy louisville ky`
- `adolescent therapy louisville ky`
- `medicaid therapy louisville ky`
- `therapist louisville ky`
- `counselor louisville ky`

These terms should usually reinforce existing canonical pages instead of becoming new standalone silos.

## Canonical Commercial Architecture

### Breadcrumbs And Related Pages

- `primaryParent` in `src/data/page-relationships.ts` controls canonical breadcrumb chains.
- `getBreadcrumbEntries()` controls visible breadcrumb navigation.
- `getBreadcrumbJsonLd()` controls `BreadcrumbList` structured data.
- `getRelatedPageGroups()` controls visible grouped internal links for related service categories, care settings, specialties, concerns/audiences, and service areas.
- `npm run validate:relationships` checks parent cycles, missing parent targets, self-links, duplicate related links, missing related targets, support-page graph coverage, and article-detail breadcrumb fallback behavior.
- Related pages are crawlable HTML internal links. Do not add speculative related-page JSON-LD unless there is a specific supported schema reason.

### Confirmed Core And Specialty Relationships

Core category pages list direct specialties or specific child pages only:

| Core page | Direct specialty or specific pages |
|---|---|
| `/individual-counseling/` | `/adult-counseling/`, `/anxiety-depression-therapy/` |
| `/child-counseling/` | `/adolescent-counseling/`, `/play-therapy/`, `/child-behavioral-intervention/` |
| `/family-therapy/` | `/attachment-therapy/`, `/parenting-support/` |
| `/couples-counseling/` | `/relationship-counseling/`, `/couples-conflict-resolution/` |
| `/court-ordered/` | `/family-reunification/`, `/co-parenting/` |
| `/community-based-services/` | `/case-management/`, `/community-support/`, `/therapy-in-your-home/` |
| `/group-therapy/` | none |
| `/trauma-therapy/` | `/emdr-therapy/` |

Specialty and concern pages may carry secondary relationships when they help visitors understand fit without changing the canonical parent:

| Page | Secondary relationships |
|---|---|
| `/anxiety-depression-therapy/` | `/child-counseling/`, `/family-therapy/`, `/adult-counseling/`, `/adolescent-counseling/` |
| `/adolescent-counseling/` | `/individual-counseling/`, `/family-therapy/`, `/locations/online/` |
| `/play-therapy/` | `/child-behavioral-intervention/`, `/parenting-support/`, `/family-therapy/` |
| `/attachment-therapy/` | `/child-counseling/` |
| `/parenting-support/` | `/child-counseling/`, `/child-behavioral-intervention/`, `/co-parenting/` |
| `/emdr-therapy/` | `/individual-counseling/`, `/family-therapy/`, `/anxiety-depression-therapy/` |
| `/case-management/` | `/community-support/`, `/therapy-in-your-home/`, `/locations/community/`, `/locations/in-home/` |
| `/community-support/` | `/case-management/`, `/therapy-in-your-home/`, `/locations/community/` |
| `/therapy-in-your-home/` | `/case-management/`, `/community-support/`, `/locations/in-home/`, `/locations/community/` |
| `/family-reunification/` | `/co-parenting/`, `/family-therapy/`, `/parenting-support/` |
| `/co-parenting/` | `/family-reunification/`, `/family-therapy/`, `/parenting-support/` |
| `/relationship-counseling/` | `/couples-conflict-resolution/`, `/family-therapy/` |
| `/couples-conflict-resolution/` | `/relationship-counseling/`, `/family-therapy/` |

### Faceted Page Types

| Page type | Role | Notes |
|---|---|---|
| Homepage | Broad entity and trust page | Owns `mental health clinic louisville ky` and broad clinic/service trust |
| Primary navigation hubs | Routing pages | Services, Specialties, Team, Locations, FAQ, Contact |
| Broad service categories | Main service families | Individual, Child, Family, Couples, Court-Ordered, Community-Based Services, Group Therapy, Trauma Therapy |
| Contextual parent pages | Care settings and delivery contexts | Community Care and In-Home Care are care settings and secondary contexts; Community-Based Services is the broad category label and canonical parent for Case Management, Community Support, and In-Home Services |
| Specialties / specific pages | More-specific service paths | EMDR, court-service pages, community-service pages, and other keyword-validated specific pages |
| Concern / condition pages | Cross-cutting concern intent | Anxiety & Depression should not be forced under only Individual Counseling |
| Service area pages | Local support and coverage | Support local relevance without duplicating service pages |
| Secondary/footer pages | Trust/access/support | Insurance, Reviews, Articles, Careers, future client portal |
| Utility pages | Quiet support/legal | FAQ detail, Compliance, Privacy, Terms |

### Top-Level Commercial Pages

| URL | Page Type | Primary Keyword | Secondary Keywords | Notes |
|---|---|---|---|---|
| `/` | homepage | `mental health clinic louisville ky` | `mental health services louisville ky`, `therapy louisville ky` | Broadest entity page |
| `/services/` | service hub | `mental health services louisville ky` | `therapy louisville ky`, `counseling louisville ky` | Category navigation hub |

### Broad Service Categories And Specific Pages

#### Individual Counseling

| URL | Page Type | Primary Keyword | Secondary Keywords | Redundancy Notes |
|---|---|---|---|---|
| `/individual-counseling/` | category | `individual therapy louisville ky` | `individual counseling louisville ky`, `adult counseling louisville ky` | Owns broad individual-treatment intent |
| `/adult-counseling/` | service | `adult counseling louisville ky` | `adult therapy louisville ky` | Do not split into separate adult-therapy page |
| `/anxiety-depression-therapy/` | concern / condition | `anxiety and depression therapy louisville ky` | `anxiety therapy louisville ky`, `depression therapy louisville ky` | Cross-cutting page; relates to individual, child/adolescent, and family contexts instead of being only an individual-counseling leaf |

#### Child Counseling

| URL | Page Type | Primary Keyword | Secondary Keywords | Redundancy Notes |
|---|---|---|---|---|
| `/child-counseling/` | category | `child therapy louisville ky` | `child counseling louisville ky` | Owns broad child-therapy intent |
| `/child-behavioral-intervention/` | service | `child behavioral therapy louisville ky` | `child behavioral intervention louisville ky` | Keep school-behavior discussion here, not on a separate school-only therapy page |
| `/adolescent-counseling/` | concern / audience-specific page | `teen therapy louisville ky` | `adolescent counseling louisville ky`, `adolescent therapy louisville ky` | Cross-cutting page connected to child, family, and individual contexts |
| `/play-therapy/` | specialty / child-service page | `play therapy louisville ky` | `child play therapy louisville ky`, `play therapist louisville ky` | Specialty child-counseling path for younger children who need developmentally appropriate expression and caregiver context |

#### Family Counseling

| URL | Page Type | Primary Keyword | Secondary Keywords | Redundancy Notes |
|---|---|---|---|---|
| `/family-therapy/` | category | `family therapy louisville ky` | `family counseling louisville ky` | Owns broad family-therapy intent |
| `/attachment-therapy/` | service | `attachment therapy louisville ky` | `attachment based therapy louisville ky` | Canonical parent is Family Therapy; also relates to Child Counseling where parent-child attachment and caregiver work are relevant |
| `/parenting-support/` | concern / family-support page | `parenting support louisville ky` | `parenting help louisville ky` | Cross-cutting page connected to family, child, and court-involved support where appropriate |

#### Couples Counseling

| URL | Page Type | Primary Keyword | Secondary Keywords | Redundancy Notes |
|---|---|---|---|---|
| `/couples-counseling/` | category | `couples therapy louisville ky` | `couples counseling louisville ky` | Owns broad couples-therapy intent |
| `/relationship-counseling/` | service | `relationship counseling louisville ky` | `relationship therapy louisville ky` | Distinct support page under couples silo |
| `/couples-conflict-resolution/` | concern / relationship page | `couples conflict resolution louisville ky` | `couples conflict resolution therapy louisville ky` | More-specific relationship conflict page under the couples category |

#### Court-Ordered Services

| URL | Page Type | Primary Keyword | Secondary Keywords | Redundancy Notes |
|---|---|---|---|---|
| `/court-ordered/` | category | `court ordered services louisville ky` | `family court services louisville ky`, `court ordered treatment louisville ky` | Owns formal court-related family support intent |
| `/family-reunification/` | specialty / court-service page | `family reunification services louisville ky` | `reunification therapy louisville ky` | Primary reunification page under Court-Ordered Services |
| `/co-parenting/` | specialty / court-service page | `co parenting services louisville ky` | `coparenting services louisville ky`, `co parenting support louisville ky` | Short two-word slug under the court-ordered context |

Court-ordered treatment remains a supporting intent owned by `/court-ordered/` rather than a separate canonical service page. This keeps the court-involved family-support silo on two-word slugs without fragmenting treatment-support intent across a near-duplicate child page.

#### Community-Based Services

| URL | Page Type | Primary Keyword | Secondary Keywords | Redundancy Notes |
|---|---|---|---|---|
| `/community-based-services/` | category | `community based mental health services louisville ky` | `mental health case management louisville ky`, `community support services louisville ky` | Owns broad community-based support intent and links to specific specialties |
| `/case-management/` | specialty / community-service page | `case management louisville ky` | `mental health case management louisville ky`, `care coordination louisville ky` | Case Management is a specialty under Community-Based Services, not a separate broad category and not a Targeted Case Management page |
| `/therapy-in-your-home/` | specialty / contextual service page | `in home therapy louisville ky` | `therapy in your home louisville ky` | Home-based care page; also relates to In-Home Care |
| `/community-support/` | specialty / community-service page | `community support services louisville ky` | `community support louisville ky` | Public-facing support page under Community Care and related to Community-Based Services |

Case Management is the confirmed public specialty page inside the Community-Based Services family. Do not create a separate Targeted Case Management page or route unless a later business decision explicitly changes the service inventory.

#### Group Therapy

| URL | Page Type | Primary Keyword | Secondary Keywords | Redundancy Notes |
|---|---|---|---|---|
| `/group-therapy/` | category | `group therapy louisville ky` | `group counseling louisville ky` | Owns group-format service intent and should not redirect to the services hub |

#### Trauma Therapy

| URL | Page Type | Primary Keyword | Secondary Keywords | Redundancy Notes |
|---|---|---|---|---|
| `/trauma-therapy/` | category | `trauma therapy louisville ky` | `trauma-focused therapy louisville ky`, `ptsd therapy louisville ky` | Owns broad trauma-therapy intent; EMDR is a related specialty beneath this category rather than the broad trauma owner |
| `/emdr-therapy/` | specialty | `emdr therapy louisville ky` | `emdr for trauma louisville ky`, `ptsd therapist louisville ky` | EMDR remains a specific modality page related to Trauma Therapy, Individual Counseling, and Anxiety & Depression support |

## Specialty / Specific Pages

Specialty means more specific than the broad service categories. EMDR is a specialty, but it is not the only specialty pattern. Court-service pages, community-service pages, modality pages, and narrower concern pages may all function as specialty/specific pages when keyword research supports them.

| URL | Page Type | Primary Keyword | Secondary Keywords | Notes |
|---|---|---|---|---|
| `/emdr-therapy/` | specialty | `emdr therapy louisville ky` | `emdr louisville ky`, `emdr for trauma louisville ky`, `ptsd therapy louisville ky` | EMDR remains a specialty entry point under Trauma Therapy rather than the broad trauma category itself |
| `/play-therapy/` | specialty | `play therapy louisville ky` | `child play therapy louisville ky`, `play therapist louisville ky` | Play Therapy is a specialty entry point related to Child Counseling and caregiver-supported child therapy |

## Supporting Non-Silo Pages

These pages matter, but they are not primary CORE 30 service silos.

### Service-Area Pages

| URL | Role | Keyword Use |
|---|---|---|
| `/louisville-ky/` | primary city page | broad Louisville service-area intent |
| `/jefferson-county-ky/` | county page | county-level service-area support |
| `/bullitt-county-ky/` | county page | county-level service-area support |
| `/oldham-county-ky/` | county page | county-level service-area support |

Bardstown, KY plus Clarksville, IN, Jeffersonville, IN, and New Albany, IN are not part of the current service-area page set. Public legacy routes for those city-level intents should redirect to `/locations/` unless a later local-SEO decision restores a distinct page with enough unique value to avoid doorway-page risk.

### Contextual Parent Pages / Delivery Modes

| Slug | Role | Keyword Use |
|---|---|---|
| `in-person` | contextual parent / access mode | supports in-office care messaging |
| `community` | contextual parent / service context | supports community-based service messaging; secondary context for Case Management and Community Support |
| `in-home` | contextual parent / access mode | supports home-based care messaging |
| `online` | contextual parent / access mode | supports telehealth messaging if offered |
| `school` | contextual parent / access mode | supports school-context care messaging |

These should support discovery and page copy. They should not become duplicate versions of the same commercial service page without a clearer service distinction.

## Terms To De-Prioritize As Standalone Pages

| Term | Why It Should Not Be A New Canonical Page Right Now |
|---|---|
| `therapy louisville ky` | too broad; belongs to homepage and service hub |
| `medicaid therapy louisville ky` | better as access, insurance, FAQ, and trust copy |
| `group therapy louisville ky` | not in the current implemented commercial tree |
| `therapist louisville ky` | broad and highly competitive head term |
| `counselor louisville ky` | broad and highly competitive head term |
| `psychiatry louisville ky` | off-model for current service architecture |
| `adhd treatment louisville ky` | not a defined current silo/service |
| `tms louisville ky` | off-model for current service architecture |

## Article Backlog For Redundancy Control

Use articles to absorb overlapping, explanatory, and modifier-heavy intent that should not become a separate commercial page.

| Article Topic | Search Intent | Supports These Canonical Pages |
|---|---|---|
| `therapy vs counseling in Louisville` | informational/comparison | homepage, `/services/` |
| `how to know if you need anxiety and depression therapy` | informational | `/anxiety-depression-therapy/` |
| `signs your child may need therapy` | informational | `/child-counseling/`, `/child-behavioral-intervention/` |
| `when teen therapy may help` | informational | `/adolescent-counseling/` |
| `what family reunification services involve` | informational/commercial support | `/family-reunification/` |
| `how co-parenting support differs from family therapy` | informational/comparison | `/co-parenting/`, `/family-therapy/` |
| `what to expect from EMDR therapy` | informational/commercial support | `/emdr-therapy/` |
| `can EMDR help with anxiety` | informational/commercial support | `/emdr-therapy/` |
| `trauma therapy vs EMDR therapy` | informational/commercial support | `/trauma-therapy/`, `/emdr-therapy/` |
| `what play therapy involves` | informational/commercial support | `/play-therapy/`, `/child-counseling/` |
| `case management vs therapy` | informational/comparison | `/case-management/`, `/community-based-services/`, `/therapy-in-your-home/` |
| `does Kentucky Medicaid cover therapy` | informational/transactional support | `/insurance/`, `/individual-counseling/` |
| `when home-based therapy is a better fit` | informational/commercial support | `/therapy-in-your-home/` |
| `what court-ordered treatment support means` | informational/commercial support | `/court-ordered/` |

## On-Page SEO Rules For This Architecture

1. Each commercial page should have one dominant keyword target and a small set of close variants.
2. Title tags should be unique, descriptive, and aligned with the page’s real topic.
3. H1s should align tightly with the page’s commercial intent.
4. Meta descriptions should be page-specific and written for click-through, not keyword dumping.
5. Internal links should stay crawlable and descriptive.
6. Category and service links should respect silo logic unless there is a strong UX reason to bridge across silos.
7. Broad informational expansion should happen through articles, not through duplicate service pages.
8. Location pages should add real local value and not become doorway-like copies of service pages.

## Structured Output Notes

The parallel JSON file is intended for:

- internal tooling
- content planning
- title and meta generation
- page inventory audits
- structured export into future automation or schema-generation steps

When generating structured outputs, prefer the JSON file and use this markdown document for explanatory context.
