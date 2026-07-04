# CORE 30 Analysis & Implementation Plan

> Current-state note (2026-05-11): this document captures broader analysis and older route-planning ideas. For the active canonical route map, keyword ownership, and redundancy controls, use [docs/seo/core30-keyword-architecture.md](/Users/tyler-lcsw/projects/lmhg-astro-integrate/docs/seo/core30-keyword-architecture.md) and [docs/seo/core30-keyword-architecture.json](/Users/tyler-lcsw/projects/lmhg-astro-integrate/docs/seo/core30-keyword-architecture.json) first.

## Video Summary

The CORE 30 is a local SEO website structure developed by Caleb Ulku that mirrors your Google Business Profile (GBP) perfectly. Instead of focusing on informational blog posts that generate traffic from the wrong locations, the CORE 30 structure creates massive topical and local relevance for Google's algorithm by building a dedicated page for every category and service listed on your GBP.

**Key Insight:** Google shifted from keyword matching to entity matching in 2018-2019. An entity is Google's understanding of what your business actually is, where it operates, and what it does. When your website entity matches your GBP entity perfectly, Google gains confidence in your legitimacy and ranks you higher.

## The 30 Core Pages

The CORE 30 structure consists of:

### 1. Homepage (GBP Landing Page)
- **Target:** Primary category + city
- **Purpose:** Main hub that links to all secondary categories
- **Content Requirements:**
  - H1: Primary service + city
  - H2s: All secondary category pages
  - Goal completion focused (talks about user needs, not company history)
  - Strong CTAs with phone number prominently displayed
  - Google Reviews widget
  - Google Business Profile embed map

### 2-5. Secondary Category Pages (4-5 pages)
- Google allows up to 10 GBP categories - use 4-5
- Each becomes an H2 on homepage with dedicated page
- Examples for mental health: "Family Counseling," "Child Psychology," "Group Therapy Programs"
- **Structure:**
  - Links to all service pages beneath that category
  - Internal linking back to homepage
  - Category-specific content establishing topical authority

### 6-30. Individual Service Pages (20-25 pages)
- Every service on GBP gets its own page
- These are the "who can do this for me" transactional pages
- **Content Requirements:**
  - H1: Service name + city (optional for city on sub-pages)
  - H2s: Related topics, FAQ section
  - Locally relevant content (neighborhoods, local conditions, local problems)
  - Strong conversion elements (why call us, phone numbers)
  - Links back to parent category page

### Additional Pages (31+)
- About Us page
- Contact Us page
- Reviews/Testimonials page
- Location pages (if serving multiple cities)

## Core Principles

### 1. Entity Consistency
Every page on your website must match what's on your GBP:
- If GBP lists "Anxiety Treatment" as a service, website must have a page
- If GBP has "Child Psychologist" as secondary category, website must reflect this
- Business name, address, phone must match exactly

### 2. Topical Relevance Through Siloing
```
Homepage
  ├── Category Page 1
  │   ├── Service Page 1A
  │   ├── Service Page 1B
  │   └── Service Page 1C
  ├── Category Page 2
  │   ├── Service Page 2A
  │   └── Service Page 2B
  └── Category Page 3
      ├── Service Page 3A
      └── Service Page 3B
```

### 3. Transactional Over Informational
- Focus on "who can do this for me" searches
- Not "how to do this" blog posts
- Example: "Therapist Louisville" (transactional) vs "How to manage anxiety" (informational)
- Transactional searches bring actual clients from your service area

### 4. Local Relevance
Content must mention:
- Local neighborhoods (Highlands, St. Matthews, Germantown, etc.)
- Local conditions (Ohio River weather affecting mental health, local economic factors)
- Specific local problems
- Sound like someone who actually operates in that market

## Current Site Gap Analysis

### What We Have ✓
1. **Homepage** - Well-structured with services overview
2. **Individual Therapy** page
3. **Family Therapy** page
4. **Child Therapy** page
5. **Group Therapy** page
6. **Case Management** page
7. **Community Support** page
8. **Family Court Services** page
9. **Our Services** overview page
10. **Meet the Team** page
11. **Reviews** page
12. **Contact Us** page
13. **Multiple Location pages** (Louisville, Jeffersontown, Shively, Clarksville, etc.)

### What We Need ✗

#### Missing Core Elements:
1. **No Schema Markup** - Critical for entity matching
2. **No Secondary Category Structure** - Services aren't organized under categories
3. **No GBP Mirroring** - Structure doesn't reflect GBP organization
4. **Weak Internal Linking** - Pages don't follow silo structure
5. **Insufficient Service Depth** - Only 7 service pages, need 25-30
6. **No Location-Specific Service Pages** - Location pages exist but don't contain services

#### Missing Services (Potential):
Based on mental health industry standards and likely GBP services:
- Anxiety Treatment
- Depression Counseling
- Trauma/PTSD Therapy
- EMDR Therapy (mentioned in homepage text)
- Couples Counseling/Marriage Counseling
- Adolescent/Teen Therapy
- Substance Abuse Counseling
- Grief Counseling
- Anger Management
- Life Coaching/Transition Counseling
- Medication Management (if applicable)
- Psychological Testing/Assessment
- ADHD Treatment
- Bipolar Disorder Treatment
- OCD Treatment
- Eating Disorder Treatment
- Stress Management
- Workplace/Career Counseling
- LGBTQ+ Affirmative Therapy
- Veterans Counseling
- Domestic Violence Counseling

## Implementation Plan

### Phase 1: Critical Foundation (Week 1)

#### 1.1 GBP Audit & Optimization
- [ ] Audit current GBP categories
- [ ] Add secondary categories (up to 10 total)
- [ ] Add all services to GBP (30+ services)
- [ ] Complete business description
- [ ] Fill in all GBP fields
- [ ] Ensure NAP consistency

#### 1.2 Schema Markup Implementation
Create JSON-LD schema for:
- [ ] LocalBusiness schema (organization type)
- [ ] MedicalClinic or HealthClinic schema
- [ ] Provider schema for practitioners
- [ ] Service schema for each service
- [ ] FAQPage schema for FAQ sections
- [ ] Review schema for testimonials

#### 1.3 Homepage Optimization
- [ ] Update H1 to include primary category + "Louisville, KY"
- [ ] Add H2s for secondary categories
- [ ] Improve goal completion (focus on user needs)
- [ ] Add Google Reviews widget
- [ ] Embed Google Business Profile map
- [ ] Strengthen CTAs with phone number prominence

### Phase 2: Secondary Category Pages (Week 2)

Create 4-5 secondary category pages:

#### Category 1: Individual Services
- [ ] Create `/individual-services/` category page
- [ ] Include: Individual Therapy, Anxiety Treatment, Depression Counseling, Trauma/PTSD
- [ ] Internal links to all child service pages
- [ ] Link back to homepage

#### Category 2: Family & Child Services
- [ ] Create `/family-child-services/` category page
- [ ] Include: Family Therapy, Child Therapy, Adolescent/Teen Therapy
- [ ] Internal links to all child service pages
- [ ] Link back to homepage

#### Category 3: Specialized Programs
- [ ] Create `/specialized-programs/` category page
- [ ] Include: EMDR Therapy, Group Therapy, Case Management
- [ ] Internal links to all child service pages
- [ ] Link back to homepage

#### Category 4: Court & Community Services
- [ ] Create `/court-community-services/` category page
- [ ] Include: Family Court Services, Community Support
- [ ] Internal links to all child service pages
- [ ] Link back to homepage

### Phase 3: Service Page Expansion (Weeks 3-4)

Create individual service pages for:

#### Individual Services (8-10 pages):
- [ ] `/individual-therapy/` (update existing)
- [ ] `/anxiety-treatment/`
- [ ] `/depression-counseling/`
- [ ] `/trauma-therapy/` or `/ptsd-treatment/`
- [ ] `/emdr-therapy/`
- [ ] `/stress-management/`
- [ ] `/life-transitions-counseling/`
- [ ] `/adhd-treatment/`

#### Family & Child Services (5-7 pages):
- [ ] `/family-therapy/` (update existing)
- [ ] `/child-therapy/` (update existing)
- [ ] `/teen-therapy/` or `/adolescent-counseling/`
- [ ] `/couples-counseling/` or `/marriage-counseling/`
- [ ] `/parenting-support/`
- [ ] `/family-conflict-resolution/`

#### Specialized Programs (5-7 pages):
- [ ] `/group-therapy/` (update existing)
- [ ] `/case-management/` (update existing)
- [ ] `/grief-counseling/`
- [ ] `/anger-management/`
- [ ] `/substance-abuse-counseling/`
- [ ] `/domestic-violence-counseling/`

#### Court & Community Services (3-5 pages):
- [ ] `/family-court-services/` (update existing)
- [ ] `/community-support/` (update existing)
- [ ] `/court-ordered-therapy/`
- [ ] `/reunification-services/`

### Phase 4: Location Page Enhancement (Week 5)

Update existing location pages to include:
- [ ] Services offered in each location
- [ ] Location-specific content
- [ ] Internal links to main service pages
- [ ] Location schema markup
- [ ] Embedded Google Maps

### Phase 5: Technical Optimization (Week 6)

- [ ] Create sitemap.xml
- [ ] Create robots.txt
- [ ] Verify internal linking structure
- [ ] Add breadcrumbs
- [ ] Optimize page load speeds
- [ ] Mobile optimization check
- [ ] SSL verification
- [ ] Meta tags optimization for all pages

## Page Templates

### Homepage Template
```astro
---
// H1: [Primary Category] in [City], [State]
// H2s: All secondary category names
---

<H1>[Primary Service] in Louisville, KY</H1>
<p class="hero-subtitle">
  [User-focused opening - what we do FOR THEM, not company history]
</p>

<CTA Section>
  [Phone number prominent]
  [Schedule button]
</CTA Section>

<section>
  <H2>[Secondary Category 1]</H2>
  <p>Brief description</p>
  <a href="/[category-1-slug]/">Learn more →</a>
</section>

<!-- Repeat for all secondary categories -->

<section>
  <H2>Why Choose [Business Name]</H2>
  <ul>
    <li>[Local relevance point 1]</li>
    <li>[Local relevance point 2]</li>
    <li>[Trust signal]</li>
  </ul>
</section>

<section>
  <H2>Serving [City] & Surrounding Areas</H2>
  [Neighborhoods/areas list]
  <Google Map Embed>
</section>

<section>
  <H2>What Our Clients Say</H2>
  [Google Reviews Widget]
</section>

<Final CTA>
  [Strong call to action with phone]
</Final CTA>
```

### Category Page Template
```astro
---
// H1: [Category Name] in [City], [State]
// Purpose: Establish topical authority for category
---

<H1>[Category Name] in Louisville, KY</H1>
<p class="intro">
  [What this category is, who it's for, why it matters locally]
</p>

<section>
  <H2>Our [Category] Services</H2>
  <ul>
    <li><a href="/[service-1-slug]/">[Service 1]</a> - Brief description</li>
    <li><a href="/[service-2-slug]/">[Service 2]</a> - Brief description</li>
    <!-- All services in this category -->
  </ul>
</section>

<section>
  <H2>Why Choose Us for [Category]</H2>
  [Local expertise, qualifications, approach]
</section>

<section>
  <H2>[Category] FAQ</H2>
  [FAQ with schema markup]
</section>

<a href="/">← Back to Home</a>
```

### Service Page Template
```astro
---
// H1: [Service Name] in [City], [State] (optional city on sub-pages)
// Purpose: Transactional - "who can do this for me"
---

<H1>[Service Name] in Louisville, KY</H1>
<p class="intro">
  [User-focused opening - acknowledge their problem, offer solution]
</p>

<CTA Box>
  [Phone number]
  [Schedule button]
</CTA Box>

<section>
  <H2>What is [Service]?</H2>
  [Clear explanation]
</section>

<section>
  <H2>How We Can Help</H2>
  [Your approach, what to expect]
</section>

<section>
  <H2>Why Louisville Residents Choose Us</H2>
  [Local relevance - specific to Louisville/Kentucky context]
</section>

<section>
  <H2>[Service] FAQ</H2>
  [FAQ with schema markup]
</section>

<section>
  <H2>Take the First Step</H2>
  <CTA>
    [Phone number]
    [Schedule button]
    <a href="/[parent-category-slug]/">View related services →</a>
  </CTA>
</section>
```

### Location Page Template
```astro
---
// H1: Mental Health Services in [City], [State]
// Purpose: Local relevance + service links
---

<H1>Mental Health Services in [City], KY</H1>
<p class="intro">
  [How we serve this specific community]
</p>

<section>
  <H2>Services Available in [City]</H2>
  <ul>
    <li><a href="/[service-1-slug]/">[Service 1]</a></li>
    <li><a href="/[service-2-slug]/">[Service 2]</a></li>
    <!-- Key services -->
  </ul>
  <a href="/our-services/">View all services →</a>
</section>

<section>
  <H2>Serving [City] Neighborhoods</H2>
  [List specific neighborhoods/areas]
</section>

<section>
  <H2>[City] Location Information</H2>
  [Distance from main office]
  [Telehealth options]
  <Google Map Embed>
</section>

<CTA>
  [Phone number]
  [Schedule button]
</CTA>
```

## URL Structure

### Recommended URL Patterns
```
/                                    (Homepage - Primary Category + Louisville, KY)
/individual-services/                 (Secondary Category 1)
  /individual-therapy/               (Service under Individual Services)
  /anxiety-treatment/                (Service under Individual Services)
  /depression-counseling/            (Service under Individual Services)
/family-child-services/              (Secondary Category 2)
  /family-therapy/                   (Service under Family & Child Services)
  /child-therapy/                    (Service under Family & Child Services)
  /teen-therapy/                     (Service under Family & Child Services)
/specialized-programs/               (Secondary Category 3)
  /emdr-therapy/                     (Service under Specialized Programs)
  /group-therapy/                    (Service under Specialized Programs)
  /case-management/                  (Service under Specialized Programs)
/court-community-services/           (Secondary Category 4)
  /family-court-services/            (Service under Court & Community Services)
  /community-support/                (Service under Court & Community Services)
/about/                              (About Us)
/contact/                            (Contact Us)
/reviews/                            (Reviews/Testimonials)
/louisville-ky/                      (Primary Location)
/louisville-ky/[service-slug]/       (Location-specific service pages)
/[city]-ky/                          (Other locations)
```

### URL Best Practices
- Use hyphens between words
- Keep URLs short and descriptive
- Include location keywords on location pages
- Match URL structure to GBP category/service structure
- Create logical hierarchy (category/service)

## Internal Linking Strategy

### Silo Structure Rules

1. **Homepage** links to:
   - All secondary category pages
   - Key location pages
   - About, Contact, Reviews pages

2. **Category Page** links to:
   - All service pages within that category
   - Back to homepage
   - Related category pages (optional)

3. **Service Page** links to:
   - Parent category page
   - Related services (same category)
   - Contact/schedule CTA
   - NOT to services in other categories (maintain silo)

4. **Location Page** links to:
   - Homepage
   - Main service pages (or location-specific versions)
   - Contact page

### Link Distribution
- Homepage should have the most internal links
- Category pages should have 5-10 internal links
- Service pages should have 3-5 internal links
- Use descriptive anchor text (not "click here")
- Include phone number links on every page

### Breadcrumbs
Implement breadcrumb navigation:
```
Home > Individual Services > Individual Therapy
Home > Family & Child Services > Child Therapy
```

## Schema Requirements

### Required Schema Types

1. **LocalBusiness / MedicalClinic**
   ```json
   {
     "@type": "MedicalClinic",
     "name": "Louisville Mental Health Group",
     "address": {
       "@type": "PostalAddress",
       "streetAddress": "[full address]",
       "addressLocality": "Louisville",
       "addressRegion": "KY",
       "postalCode": "[zip]",
       "addressCountry": "US"
     },
     "geo": {
       "@type": "GeoCoordinates",
       "latitude": [lat],
       "longitude": [lng]
     },
     "url": "https://louisvillementalhealth.com",
     "telephone": "+1-502-416-1416",
     "openingHoursSpecification": [hours],
     "priceRange": "$$"
   }
   ```

2. **Organization**
   - Same as LocalBusiness but with logo, social profiles

3. **Service Schema** (for each service page)
   ```json
   {
     "@type": "MedicalProcedure" or "MedicalTherapy",
     "name": "[Service Name]",
     "description": "[description]",
     "provider": {
       "@type": "MedicalClinic",
       "name": "Louisville Mental Health Group"
     }
   }
   ```

4. **FAQPage Schema** (for FAQ sections)
   ```json
   {
     "@type": "FAQPage",
     "mainEntity": [{
       "@type": "Question",
       "name": "[Question text]",
       "acceptedAnswer": {
         "@type": "Answer",
         "text": "[Answer text]"
       }
     }]
   }
   ```

5. **Review Schema** (for testimonials)
   ```json
   {
     "@type": "Review",
     "itemReviewed": {
       "@type": "MedicalClinic",
       "name": "Louisville Mental Health Group"
     },
     "reviewRating": {
       "@type": "Rating",
       "ratingValue": "5",
       "bestRating": "5"
     },
     "author": {
       "@type": "Person",
       "name": "[Reviewer name]"
     },
     "reviewBody": "[Review text]"
   }
   ```

6. **BreadcrumbList Schema** (for breadcrumbs)
   ```json
   {
     "@type": "BreadcrumbList",
     "itemListElement": [{
       "@type": "ListItem",
       "position": 1,
       "name": "Home",
       "item": "https://louisvillementalhealth.com/"
     }, {
       "@type": "ListItem",
       "position": 2,
       "name": "Service Name",
       "item": "https://louisvillementalhealth.com/service/"
     }]
   }
   ```

7. **Article Schema** (if adding blog content later)

### Schema Implementation
- Add JSON-LD to `<head>` section
- Use Astro `<script slot="head">` for JSON-LD
- Validate with Google's Structured Data Testing Tool
- Ensure no errors or warnings

## Content Guidelines

### Local Relevance Markers
- Mention Louisville neighborhoods (Highlands, St. Matthews, Germantown, Clifton, Crescent Hill, etc.)
- Reference local landmarks and areas
- Discuss Kentucky-specific mental health challenges
- Mention Ohio River Valley seasonal effects
- Local insurance providers (Kentucky Medicaid, Passport, etc.)
- Local referral sources (doctors, hospitals, schools)

### Conversion Optimization
- Phone number above fold on every page
- Multiple CTAs per page
- "Why Choose Us" sections with trust signals
- Social proof (reviews, testimonials, years in service)
- Urgency elements (same-day appointments, limited availability)
- Easy scheduling (IntakeQ link prominent)

### Content Quality Standards
- Goal completion focused
- User-centric language ("you" not "we")
- Clear, jargon-free explanations
- Answer common questions proactively
- Include process/what to expect
- Length: 800-1500 words for service pages
- FAQ section on every page

## Next Steps

1. **Immediate Actions:**
   - Audit and optimize GBP
   - Implement schema markup on existing pages
   - Update homepage H1 and add H2s

2. **Week 1-2:**
   - Create category page structure
   - Build internal linking framework
   - Update existing service pages

3. **Week 3-4:**
   - Create missing service pages
   - Optimize location pages
   - Add FAQ sections with schema

4. **Week 5-6:**
   - Technical SEO optimization
   - Performance improvements
   - Finalize internal linking

## Success Metrics

- Google Business Profile ranking improvement
- Map pack visibility (top 3 positions)
- Organic traffic from local searches
- Phone call volume
- Appointment scheduling rate
- Average position for target keywords

---

**Note:** This plan should be adjusted based on actual GBP categories and services. Always start with a complete GBP audit before implementing website changes.
