# CORE 30 Implementation Plan for Louisville Mental Health Group

> Current-state note (2026-05-11): this document contains older implementation-planning detail and historical route decisions. For the active canonical route map, keyword ownership, and redundancy controls, use [docs/seo/core30-keyword-architecture.md](/Users/tyler-lcsw/projects/lmhg-astro-integrate/docs/seo/core30-keyword-architecture.md) and [docs/seo/core30-keyword-architecture.json](/Users/tyler-lcsw/projects/lmhg-astro-integrate/docs/seo/core30-keyword-architecture.json) first.

## CORE 30 Philosophy Applied

**Core Principle:** Mirror your Google Business Profile exactly on your website. Google uses entity matching - when your website entity matches your GBP entity, Google gains confidence and ranks you higher.

**Entity = What your business IS + WHERE you operate + WHAT you do**

---

## Part 1: The Homepage (GBP Landing Page)

### Purpose
The homepage is the "hub" that establishes your primary entity and links to all secondary category pages.

### H1 Strategy
**Current:** Cultivating Strength, Stability, & Self Discovery
**CORE 30:** Mental Health Clinic in Louisville, KY

### H2 Strategy (Links to Secondary Category Pages)
Based on your GMB categories, homepage should have H2s for:

1. **Individual Counseling** → Links to `/individual-counseling/`
2. **Child & Adolescent Counseling** → Links to `/child-counseling/`
3. **Family Counseling** → Links to `/family-counseling/`
4. **Couples Counseling** → Links to `/couples-counseling/`
5. **Family Court Services** → Links to `/family-court-services/`
6. **EMDR & Trauma Therapy** → Links to `/emdr-therapy/`
7. **Case Management** → Links to `/case-management/`

### Homepage Content Requirements
- Goal completion focused (user needs, not company history)
- Phone number prominently displayed
- Google Reviews embed
- Google Map embed
- Serving [neighborhoods] section
- All H2s link to category pages

---

## Part 2: Secondary Category Pages (The Silos)

### CORE 30 Principle
Homepage links to category pages. Category pages link ONLY to service pages within that category. This creates "topical authority" silos.

### Silo Structure
```
Homepage
├── /individual-counseling/ (Category)
│   ├── /adult-counseling/ (Service)
│   ├── /anxiety-depression-therapy/ (Service)
│   └── /private-counseling/ (Service)
├── /child-counseling/ (Category)
│   ├── /child-behavioral-intervention/ (Service)
│   └── /adolescent-counseling/ (Service)
├── /family-counseling/ (Category)
│   ├── /parenting-support/ (Service)
│   ├── /attachment-therapy/ (Service)
│   └── /family-rebuilding/ (Service)
├── /couples-counseling/ (Category)
│   ├── /relationship-counseling/ (Service)
│   └── /couples-conflict-resolution/ (Service)
├── /family-court-services/ (Category)
│   ├── /family-reunification/ (Service)
│   ├── /coparenting-services/ (Service)
│   └── /court-ordered-evaluations/ (Service)
├── /emdr-therapy/ (Category)
│   ├── /trauma-focused-emdr/ (Service)
│   ├── /emdr-for-ptsd/ (Service)
│   └── /emdr-for-anxiety/ (Service)
└── /case-management/ (Category)
    ├── /home-based-therapy/ (Service)
    └── /community-support/ (Service)
```

---

## Part 3: Category Page Templates

### Template Structure (Based on GMB Data)

```astro
---
// Category Page Template
// H1: [Category Name from GMB] in Louisville, KY
---

<H1>[Category Name] in Louisville, KY</H1>

<p class="intro">
  [What this category is, who it's for - user-focused]
</p>

<section>
  <H2>Our [Category] Services</H2>
  <ul>
    <li><a href="/[service-1]/">[Service 1 from GMB]</a> - [Brief description]</li>
    <li><a href="/[service-2]/">[Service 2 from GMB]</a> - [Brief description]</li>
    <!-- All services from this GMB category -->
  </ul>
</section>

<section>
  <H2>Why Louisville Residents Choose Us</H2>
  [Local relevance - neighborhoods, local context]
</section>

<section>
  <H2>[Category] FAQ</H2>
  [FAQ with schema markup]
</section>

<a href="/">← Back to Home</a>
```

---

## Part 4: Service Page Templates

### Template Structure (Based on GMB Data)

```astro
---
// Service Page Template
// H1: [Primary Service from GMB] in Louisville, KY
// H2: [Related Services from same GMB category]
// SEO Artifacts: [Exact GMB phrases sprinkled throughout]
---

<H1>[Primary Service Name] in Louisville, KY</H1>

<p class="intro">
  [Acknowledge their problem, offer solution - user-focused]
</p>

<CTA Box>
  <a href="tel:5024161416">Call (502) 416-1416</a>
  <a href="https://intakeq.com/new/g91Z8x/bjxuno">Schedule Now</a>
</CTA Box>

<section>
  <H2>What is [Service]?</H2>
  [Clear explanation, include GMB phrases as artifacts]
</section>

<section>
  <H2>[Related Service from GMB]</H2>
  [Content about this related service]
</section>

<section>
  <H2>How We Help Louisville Residents</H2>
  [Local relevance - neighborhoods, Ohio River, local context]
  [Include more GMB phrases as artifacts]
</section>

<section>
  <H2>[Service] FAQ</H2>
  [FAQ with schema markup]
</section>

<section>
  <H2>Take the First Step</H2>
  <CTA>
    <a href="tel:5024161416">Call (502) 416-1416</a>
    <a href="https://intakeq.com/new/g91Z8x/bjxuno">Schedule Now</a>
    <a href="/[parent-category]/">View all [category] services →</a>
  </CTA>
</section>
```

---

## Part 5: Complete Page-by-Page Plan

### Homepage: `/`
**H1:** Mental Health Clinic in Louisville, KY
**H2s:** (link to category pages)
- Individual Counseling
- Child Counseling
- Family Counseling
- Couples Counseling
- Co-Parenting & Reunification
- EMDR & Trauma Therapy
- Case Management
**GMB Categories Covered:** All primary categories

---

### Category Page 1: `/individual-counseling/`
**H1:** Individual Counseling in Louisville, KY
**H2s:** (service descriptions)
- Adult Counseling
- Anxiety & Depression Therapy
- Private Counseling
**GMB Source:** Mental Health Service category
**Links to:**
- `/adult-counseling/`
- `/anxiety-depression-therapy/`
- `/private-counseling/`

---

### Service Page 1.1: `/adult-counseling/`
**H1:** Adult Counseling in Louisville, KY
**H2:** Individual Therapy for Anxiety and Depression
**SEO Artifacts:** Adult Counseling, Mental Health Service
**Parent Category:** /individual-counseling/

---

### Service Page 1.2: `/anxiety-depression-therapy/`
**H1:** Individual Therapy for Anxiety and Depression in Louisville, KY
**H2:** Trauma Therapy
**SEO Artifacts:** Individual Therapy for Anxiety and Depression, Private Counseling, Trauma Treatment
**Parent Category:** /individual-counseling/

---

### Service Page 1.3: `/private-counseling/`
**H1:** Private Counseling in Louisville, KY
**H2:** Adult Counseling
**SEO Artifacts:** Private Counseling, Mental Health Service
**Parent Category:** /individual-counseling/

---

### Category Page 2: `/child-counseling/`
**H1:** Child Counseling in Louisville, KY
**H2s:**
- Child Behavioral Intervention
- Adolescent Counseling
**GMB Source:** Mental Health Clinic + Counselor categories

---

### Service Page 2.1: `/child-behavioral-intervention/`
**H1:** Child Behavioral Intervention in Louisville, KY
**H2:** Child Counseling
**SEO Artifacts:** Child Counseling, Child Behavioral Intervention
**Parent Category:** /child-counseling/

---

### Service Page 2.2: `/adolescent-counseling/`
**H1:** Child Counseling for Teens in Louisville, KY
**H2:** Adolescent Counseling
**SEO Artifacts:** Child Counseling
**Parent Category:** /child-counseling/

---

### Category Page 3: `/family-counseling/`
**H1:** Family Counseling in Louisville, KY
**H2s:**
- Parenting Help
- Attachment-Based Therapy
- Therapy for Family Rebuilding
**GMB Source:** Family Counselor category

---

### Service Page 3.1: `/parenting-support/`
**H1:** Parenting Help in Louisville, KY
**H2:** Parental Conflict Resolution
**SEO Artifacts:** Parenting Help, Parental Conflict Resolution
**Parent Category:** /family-counseling/

---

### Service Page 3.2: `/attachment-therapy/`
**H1:** Attachment-Based Therapy in Louisville, KY
**H2:** Family Counseling
**SEO Artifacts:** Attachment-based Therapy
**Parent Category:** /family-counseling/

---

### Service Page 3.3: `/family-rebuilding/`
**H1:** Therapy for Family Rebuilding in Louisville, KY
**H2:** Family Counseling
**SEO Artifacts:** Therapy for Family Rebuilding, Family Counseling
**Parent Category:** /family-counseling/

---

### Category Page 4: `/couples-counseling/`
**H1:** Couples Counseling in Louisville, KY
**H2s:**
- Relationship Counseling Services
- Couples Conflict Resolution Therapy
- Couples Therapy Services
**GMB Source:** Family Counselor + Mental Health Service categories

---

### Service Page 4.1: `/relationship-counseling/`
**H1:** Relationship Counseling Services in Louisville, KY
**H2:** Couples Counseling
**SEO Artifacts:** Relationship Counseling Services, Couples Counseling
**Parent Category:** /couples-counseling/

---

### Service Page 4.2: `/couples-conflict-resolution/`
**H1:** Couples Conflict Resolution Therapy in Louisville, KY
**H2:** Couples Therapy Services
**SEO Artifacts:** Couples Conflict Resolution Therapy, Couples Therapy Services
**Parent Category:** /couples-counseling/

---

### Category Page 5: `/family-court-services/`
**H1:** Family Court Services in Louisville, KY
**H2s:**
- Family Reunification
- Co-Parenting Services
- Court-Ordered Evaluations
**GMB Source:** Family Counselor category
**Note:** These are "products" - explicit evaluations, services, and court testimony (not insurance-covered)

---

### Service Page 5.1: `/family-reunification/`
**H1:** Family Reunification Therapy in Louisville, KY
**H2:** Co-Parenting
**SEO Artifacts:** Family Reunification, Family Reunification Therapy, Therapy for Family Rebuilding
**Parent Category:** /family-court-services/

---

### Service Page 5.2: `/coparenting-services/`
**H1:** Co-Parenting in Louisville, KY
**H2:** Parental Conflict Resolution
**SEO Artifacts:** Co-Parenting, Parenting Help, Parental Conflict Resolution
**Parent Category:** /family-court-services/

---

### Service Page 5.3: `/court-ordered-evaluations/`
**H1:** Court-Ordered Evaluations in Louisville, KY
**H2:** Court Ordered Treatment
**SEO Artifacts:** Court Ordered Treatment, Court-Ordered Evaluations
**Parent Category:** /family-court-services/

---

### Category Page 6: `/emdr-therapy/`
**H1:** EMDR Therapy in Louisville, KY
**H2s:**
- Trauma Focused EMDR
- EMDR for PTSD
- EMDR for Anxiety
**GMB Source:** Counselor + Mental Health Service categories

---

### Service Page 6.1: `/trauma-focused-emdr/`
**H1:** Trauma Focused EMDR Sessions in Louisville, KY
**H2:** EMDR Therapy
**SEO Artifacts:** Trauma Focused EMDR Sessions, EMDR, Trauma Therapy
**Parent Category:** /emdr-therapy/

---

### Service Page 6.2: `/emdr-for-ptsd/`
**H1:** EMDR Therapy for PTSD and Trauma in Louisville, KY
**H2:** Trauma Treatment
**SEO Artifacts:** EMDR Therapy for PTSD and Trauma, EMDR Therapy Sessions, Trauma Treatment
**Parent Category:** /emdr-therapy/

---

### Service Page 6.3: `/emdr-for-anxiety/`
**H1:** EMDR for Anxiety and Emotional Healing in Louisville, KY
**H2:** Trauma Focused EMDR
**SEO Artifacts:** EMDR for Anxiety and Emotional Healing, EMDR
**Parent Category:** /emdr-therapy/

---

### Category Page 7: `/case-management/`
**H1:** Case Management Services in Louisville, KY
**H2s:**
- Therapy in Your Home
- Community Support
**GMB Source:** Social Worker category

---

### Service Page 7.1: `/home-based-therapy/`
**H1:** Therapy in Your Home in Louisville, KY
**H2:** Case Management
**SEO Artifacts:** Therapy in Your Home, Case Management
**Parent Category:** /case-management/

---

### Service Page 7.2: `/community-support/`
**H1:** Community Support Associates in Louisville, KY
**H2:** Case Management
**SEO Artifacts:** Community Support Associates, Case Management
**Parent Category:** /case-management/

---

## Part 6: Internal Linking Rules (CORE 30 Critical)

### Homepage Links TO:
- All 7 category pages
- About, Contact, Reviews pages
- Primary location page

### Category Page Links TO:
- All service pages within that category ONLY
- Back to homepage
- NOT to services in other categories (maintains silo)

### Service Page Links TO:
- Parent category page
- Related services within same category ONLY
- Contact/schedule CTAs
- NOT to services in other categories (breaks silo)

### What NOT To Do:
- ❌ Link EMDR page to Couples Counseling page
- ❌ Link Child Counseling to Case Management
- ❌ Cross-link between categories
- ✅ Only link within the silo

---

## Part 7: Schema Markup Strategy

### Homepage Schema
```json
{
  "@type": "MedicalClinic",
  "name": "Louisville Mental Health Group",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "4229 Bardstown Rd, Suite 310",
    "addressLocality": "Louisville",
    "addressRegion": "KY",
    "postalCode": "40218"
  },
  "medicalSpecialty": "Mental Health Clinic"
}
```

### Category Page Schema
```json
{
  "@type": "MedicalSpecialty",
  "name": "[Category Name]",
  "partOfSystem": "Mental Health Services"
}
```

### Service Page Schema
```json
{
  "@type": "MedicalTherapy",
  "name": "[Service Name from GMB]",
  "provider": {
    "@type": "MedicalClinic",
    "name": "Louisville Mental Health Group"
  }
}
```

---

## Part 8: Local Relevance Requirements

### Every Page Must Include:
- Louisville neighborhood mentions (Highlands, St. Matthews, Germantown, Clifton, Crescent Hill, etc.)
- Kentucky context
- Local insurance mentions (Passport, Kentucky Medicaid)
- Local problems/context
- Phone number (502) 416-1416

### Example Local Content:
"Serving families in the Highlands, St. Matthews, and throughout Jefferson County, we understand the unique challenges Kentucky families face..."

---

## Part 9: URL Redirects Needed

### Current URL → New URL
| Current | New | Redirect? |
|---------|-----|----------|
| /individual-therapy/ | /adult-counseling/ | Yes (301) |
| /family-therapy/ | /family-counseling/ | Yes (301) |
| /child-therapy/ | /child-counseling/ | Yes (301) |
| /group-therapy/ | REMOVE (not in GMB) | Yes (410 or redirect to /) |
| /case-management/ | /case-management/ | No (keep, expand) |
| /community-support/ | /case-management/ | Yes (301 merge) |
| /family-court/ | /family-court-services/ | No (keep existing, expand) |

---

## Part 10: Implementation Priority

### Week 1: Foundation
1. Add schema markup to Layout.astro
2. Update homepage (new H1, H2s for categories)
3. Create 7 category placeholder pages

### Week 2-3: Core Services (High GMB Volume)
4. Create /couples-counseling/ category + 2 service pages
5. Create /emdr-therapy/ category + 3 service pages
6. Expand /family-court-services/ category + add 2 new service pages (Co-Parenting, Evaluations)

### Week 4-5: Existing Page Updates
7. Update /individual-counseling/ (rename from individual-therapy)
8. Update /child-counseling/ (rename from child-therapy)
9. Update /family-counseling/ (rename from family-therapy)
10. Update /case-management/ (add home therapy content)

### Week 6: Technical
11. Setup redirects for renamed pages
12. Verify internal linking (silo integrity)
13. Add breadcrumbs
14. Verify schema on all pages

---

## Part 11: Success Metrics

CORE 30 success is measured by:
- Google Business Profile ranking improvement
- Map pack visibility (top 3)
- Entity consistency (website = GBP)
- Topical authority for each category
- Local search rankings for "service + Louisville"

---

## Summary

**CORE 30 Applied = 7 Category Pages + ~20 Service Pages**

Each GMB service becomes a page. Each GMB category becomes a category page. Homepage links to categories. Categories link to services. Silos are maintained. Entity is consistent.

**Total Pages:** Homepage + 7 categories + ~20 services + about/contact/reviews = ~30 pages (hence CORE 30)
