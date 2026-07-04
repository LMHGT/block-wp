# URL Redirects Tracking

This file tracks all URL changes needed to match the GMB (Google My Business) listing.

**⚠️ IMPORTANT:** Redirects updated 2026-06-24 to reflect the current canonical structure: `family-therapy`, `court-ordered`, `community-based-services`, `case-management`, `group-therapy`, `trauma-therapy`, `community-support`, EMDR as a specialty under Trauma Therapy, Play Therapy as a specialty under Child Counseling, and the removal of `private-counseling` in favor of `attachment-therapy`.

## Format
- **Old URL** → **New URL** | Reason
- Unless separately noted, both trailing-slash and non-trailing-slash variants should be present in `public/_redirects` for removed or renamed public routes.

---

## Current Redirects (from _redirects file)

### Service Pages
- `/individual-therapy` → `/individual-counseling/` (301) - Legacy therapy page collapsed into current category
- `/family-counseling` → `/family-therapy/` (301) - Prior counseling category renamed to the current family-therapy category
- `/family-therapy` → `/family-therapy/` (301) - Trailing slash normalization for current category
- `/child-therapy` → `/child-counseling/` (301) - Legacy therapy page collapsed into current category
- `/couples-therapy` → `/couples-counseling/` (301) - Legacy therapy page collapsed into current category
- `/anxiety-treatment` → `/anxiety-depression-therapy/` (301) - Legacy therapy page collapsed into current detailed service page
- `/depression-treatment` → `/anxiety-depression-therapy/` (301) - Legacy therapy page collapsed into current detailed service page
- `/trauma-treatment` → `/trauma-therapy/` (301) - Legacy trauma page collapsed into current Trauma Therapy category
- `/private-counseling` → `/attachment-therapy/` (301) - Removed standalone private-counseling page replaced by the Attachment Therapy page
- `/group-therapy` → `/group-therapy/` (301) - Trailing slash normalization for current category
- `/trauma-therapy` → `/trauma-therapy/` (301) - Trailing slash normalization for current category
- `/play-therapy` → `/play-therapy/` (301) - Trailing slash normalization for current specialty page
- `/community-support` → `/community-support/` (301) - Trailing slash normalization for current service page
- `/community-based-services` → `/community-based-services/` (301) - Trailing slash normalization for current category
- `/case-management` → `/case-management/` (301) - Trailing slash normalization for current Case Management specialty page
- `/community-support-associates` → `/community-support/` (301) - Internal/legacy label collapsed into public-facing community support page
- `/family-court` → `/court-ordered/` (301) - Legacy alias collapsed into current court-ordered silo
- `/family-court-services` → `/court-ordered/` (301) - Prior category renamed to the current court-ordered silo
- `/court-ordered` → `/court-ordered/` (301) - Trailing slash normalization for current category
- `/family-rebuilding` → `/family-therapy/` (301) - Overlapping family-repair page collapsed into the broader family-therapy category
- `/trauma-focused-emdr` → `/emdr-therapy/` (301) - EMDR child page collapsed into specialty page
- `/emdr-for-ptsd` → `/emdr-therapy/` (301) - EMDR child page collapsed into specialty page
- `/emdr-for-anxiety` → `/emdr-therapy/` (301) - EMDR child page collapsed into specialty page
- `/coparenting-services` → `/co-parenting/` (301) - Old slug normalized to the shorter canonical co-parenting route
- `/co-parenting-services` → `/co-parenting/` (301) - Old child route shortened to the canonical co-parenting route
- `/court-ordered-evaluations` → `/court-ordered/` (301) - Legacy treatment/evaluations route collapsed into the court-ordered category
- `/court-ordered-treatment` → `/court-ordered/` (301) - Old child route collapsed into the court-ordered category

### Canonical Location Pages (Updated 2026-05-10)
- `/locations/in-person/`
- `/locations/community/`
- `/locations/in-home/`
- `/locations/online/`
- `/locations/school/`

### Canonical Location Slash Redirects
- `/locations` → `/locations/` (301) - Trailing slash normalization for the locations hub
- `/locations/in-person` → `/locations/in-person/` (301) - Trailing slash normalization
- `/locations/community` → `/locations/community/` (301) - Trailing slash normalization
- `/locations/in-home` → `/locations/in-home/` (301) - Trailing slash normalization
- `/locations/online` → `/locations/online/` (301) - Trailing slash normalization
- `/locations/school` → `/locations/school/` (301) - Trailing slash normalization

### Location Alias Redirects
- `/locations/in-office` → `/locations/in-person/` (301) - Old care-setting slug replaced
- `/locations/in-the-community` → `/locations/community/` (301) - Old care-setting slug replaced
- `/locations/in-your-home` → `/locations/in-home/` (301) - Old care-setting slug replaced
- `/locations/office` → `/locations/in-person/` (301) - Remote live snapshot alias removed
- `/locations/home` → `/locations/in-home/` (301) - Remote live snapshot alias removed
- `/locations/telehealth` → `/locations/online/` (301) - Remote live snapshot alias removed

### Current Service Area Pages
- `/louisville-ky/`
- `/jefferson-county-ky/`
- `/bullitt-county-ky/`
- `/oldham-county-ky/`

### Current Service Area Slash Redirects
- `/louisville-ky` → `/louisville-ky/` (301) - Trailing slash normalization
- `/jefferson-county-ky` → `/jefferson-county-ky/` (301) - Trailing slash normalization
- `/bullitt-county-ky` → `/bullitt-county-ky/` (301) - Trailing slash normalization
- `/oldham-county-ky` → `/oldham-county-ky/` (301) - Trailing slash normalization

### City → County Redirects (Reorganization 2026-04-30)
- `/jeffersontown-ky` → `/jefferson-county-ky/` (301) - City consolidated to county
- `/jeffersontown-ky/` → `/jefferson-county-ky/` (301) - City consolidated to county
- `/shively-ky` → `/jefferson-county-ky/` (301) - City consolidated to county
- `/shively-ky/` → `/jefferson-county-ky/` (301) - City consolidated to county
- `/prospect-ky` → `/oldham-county-ky/` (301) - City consolidated to county
- `/prospect-ky/` → `/oldham-county-ky/` (301) - City consolidated to county

### Removed Location Pages → Louisville
- `/jeffersonville-ky` → `/louisville-ky/` (301) - Consolidated
- `/jeffersonville-ky/` → `/louisville-ky/` (301) - Consolidated
- `/springhurst-ky` → `/louisville-ky/` (301) - Consolidated
- `/springhurst-ky/` → `/louisville-ky/` (301) - Consolidated
- `/crestwood-ky` → `/louisville-ky/` (301) - Consolidated
- `/crestwood-ky/` → `/louisville-ky/` (301) - Consolidated
- `/taylorsville-ky` → `/louisville-ky/` (301) - Consolidated
- `/taylorsville-ky/` → `/louisville-ky/` (301) - Consolidated
- `/la-grange-ky` → `/louisville-ky/` (301) - Consolidated
- `/la-grange-ky/` → `/louisville-ky/` (301) - Consolidated

### Removed Location Pages → Locations Hub
- `/bardstown-ky` → `/locations/` (301) - Live public page removed from the current service-area set
- `/bardstown-ky/` → `/locations/` (301) - Live public page removed from the current service-area set
- `/clarksville-in` → `/locations/` (301) - Removed from the current service-area set
- `/clarksville-in/` → `/locations/` (301) - Removed from the current service-area set
- `/clarksville-ky` → `/locations/` (301) - Live public page/state-error slug removed from the current service-area set
- `/clarksville-ky/` → `/locations/` (301) - Live public page/state-error slug removed from the current service-area set

### Other Pages
- `/meet-the-team` → `/meet-the-team/` (301) - Trailing slash
- `/reviews` → `/reviews/` (301) - Trailing slash
- `/contact-us` → `/contact-us/` (301) - Trailing slash

### Legacy Redirects
- `/about` → `/meet-the-team/` (301)
- `/blog` → `/` (301)
- `/careers` → `/careers/` (301)
- `/career` → `/careers/` (301)
- `/we-are-hiring` → `/careers/` (301)
- `/we-are-hiring/` → `/careers/` (301)
- `/what-we-do` → `/services/` (301)
- `/what-we-do/` → `/services/` (301)
- `/our-services` → `/services/` (301)
- `/our-services/` → `/services/` (301)

### Live Sitemap Post Migration Redirects (2026-06-10)
- `/blogs` → `/articles/` (301) - WordPress post/category archive collapsed into the current Articles hub
- `/blogs/` → `/articles/` (301) - WordPress post/category archive collapsed into the current Articles hub
- `/how-to-talk-to-your-loved-ones-about-going-to-therapy` → `/articles/how-to-talk-to-your-loved-ones-about-going-to-therapy/` (301) - Legacy WordPress post migrated into the Astro article template
- `/how-to-talk-to-your-loved-ones-about-going-to-therapy/` → `/articles/how-to-talk-to-your-loved-ones-about-going-to-therapy/` (301) - Legacy WordPress post migrated into the Astro article template
- `/family-therapy-vs-individual-therapy` → `/articles/family-therapy-vs-individual-therapy/` (301) - Legacy WordPress post migrated into the Astro article template
- `/family-therapy-vs-individual-therapy/` → `/articles/family-therapy-vs-individual-therapy/` (301) - Legacy WordPress post migrated into the Astro article template
- `/top-5-signs-its-time-to-seek-therapy` → `/articles/top-5-signs-its-time-to-seek-therapy/` (301) - Legacy WordPress post migrated into the Astro article template
- `/top-5-signs-its-time-to-seek-therapy/` → `/articles/top-5-signs-its-time-to-seek-therapy/` (301) - Legacy WordPress post migrated into the Astro article template
- `/guide-to-individual-therapy` → `/articles/guide-to-individual-therapy/` (301) - Legacy WordPress post migrated into the Astro article template
- `/guide-to-individual-therapy/` → `/articles/guide-to-individual-therapy/` (301) - Legacy WordPress post migrated into the Astro article template

---

## Footer Updates (2026-04-30)

**Quick Links Menu:**
- "Book Appointment" → "Client Portal" (same IntakeQ link)
- Added "Compliance" link → `/compliance/`

**Areas We Serve:**
- Removed Clarksville/Southern Indiana as a linked service-area page
- Removed Jeffersonville IN and New Albany IN as linked footer/service-area entries; legacy city routes redirect to the Locations hub
- Removed: Jeffersontown KY, Shively KY, Prospect KY
- Added: Jefferson County KY, Bullitt County KY, Oldham County KY

---

## CORE 30 Implementation

For CORE 30 methodology, page templates, and implementation plan, see **CORE_30.md**.

## Implementation Progress

### Completed ✅
- CTA button updates (all pages link to IntakeQ form)
- GMB data extracted and organized
- CORE 30 implementation plan created
- Category pages created or normalized (individual-counseling, child-counseling, family-therapy, couples-counseling, court-ordered, community-based-services, group-therapy, trauma-therapy)
- Play Therapy added as a specialty page under Child Counseling (2026-06-24)
- Trauma Therapy added as a core service category, with EMDR related beneath it (2026-06-24)
- Case Management restored as its own specialty page under Community-Based Services (2026-06-04)
- EMDR retained as a standalone specialty page rather than a category silo (2026-05-11)
- Care-setting pages normalized to the current canonical slugs: `in-person`, `community`, `in-home`, `online`, `school`
- FAQ section created
- Careers page moved from `/we-are-hiring/`
- Bardstown and Clarksville removed from the current service-area set and redirected to the Locations hub (2026-05-22)
- County-level service-area pages created and city aliases consolidated
- Legacy therapy-entry pages collapsed into the current category/service architecture (2026-05-10)
- Legacy alias pages like `/our-services/` and `/what-we-do/` collapsed into current hubs (2026-05-10)
- Private Counseling removed and redirected to Attachment Therapy (2026-06-03)

### In Progress 🔄
- Schema markup component
- Homepage optimization
- Remaining page-by-page design normalization on still-live secondary pages
