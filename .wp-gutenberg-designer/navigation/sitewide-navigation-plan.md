# Sitewide Navigation, Header, Footer, And Breadcrumb Plan

Generated: 2026-06-30T14:42:37.414Z

Status: planning only. No WordPress navigation menu, page parent, template, metadata, or visible surface was changed.

## Header

Implementation target: `parts/header.html` using Gutenberg-native Template Part, Group, Navigation, Navigation Link, and Buttons blocks.

| Order | Label | Path | Children |
| ---: | --- | --- | --- |
| 1 | Home | / | - |
| 2 | Services | /services/ |  (8 children) |
| 3 | Specialties | /specialties/ | - |
| 4 | Team | /meet-the-team/ | - |
| 5 | Locations | /locations/ |  (5 children) |
| 6 | FAQ | /faq/ | - |
| 7 | Contact | /contact-us/ | - |

### Services Dropdown

- Services (/services/)
  - Individual Counseling (/individual-counseling/)
    - Adult Counseling (/adult-counseling/)
    - Anxiety and Depression Therapy (/anxiety-depression-therapy/)
  - Child Therapy (/child-counseling/)
    - Adolescent Counseling (/adolescent-counseling/)
    - Play Therapy (/play-therapy/)
    - Child Behavioral Intervention (/child-behavioral-intervention/)
  - Family Therapy (/family-therapy/)
    - Attachment Therapy (/attachment-therapy/)
    - Parenting Support (/parenting-support/)
  - Couples Counseling (/couples-counseling/)
    - Couples Conflict Resolution (/couples-conflict-resolution/)
    - Relationship Counseling (/relationship-counseling/)
  - Court Ordered Services (/court-ordered/)
    - Co-Parenting (/co-parenting/)
    - Family Reunification (/family-reunification/)
  - Community Based Services (/community-based-services/)
    - Case Management (/case-management/)
    - Community Support (/community-support/)
  - Group Therapy (/group-therapy/)
  - Trauma Therapy (/trauma-therapy/)
    - EMDR Therapy (/emdr-therapy/)

### Locations Dropdown

- Locations (/locations/)
  - Office (/locations/in-person/)
  - Telehealth (/locations/online/)
  - Community (/locations/community/)
  - In Home (/locations/in-home/)
  - School (/locations/school/)

### Header CTA

- Label: Reach Out
- Destination: https://intakeq.com/new/g91Z8x/bjxuno
- Treatment: separate Button block, not a normal navigation item.

## Footer

Implementation target: `parts/footer.html` using Gutenberg-native Group, Columns, Column, Heading, Paragraph, Navigation, Navigation Link, and Buttons blocks.

| Column | Heading | Purpose | Links / Content |
| ---: | --- | --- | --- |
| 1 | Louisville Mental Health Group | entity information plus service-area coverage links | Louisville, KY -> /louisville-ky/; Jefferson County, KY -> /jefferson-county-ky/; Bullitt County, KY -> /bullitt-county-ky/; Oldham County, KY -> /oldham-county-ky/ |
| 2 | Other Links | secondary utility paths; not primary header navigation | Insurance -> /insurance/; Reviews -> /reviews/; Articles -> /articles/; Careers -> /careers/; Client Portal -> #; HIPAA -> /compliance/ |
| 3 | Louisville Office | single Louisville office contact details | Privacy Policy -> /privacy-policy/; Terms of Use -> /terms-of-use/ |

Footer bottom text: Accepting most commercial insurances, all Medicaid plans, and private pay. Serving Louisville, KY and surrounding areas in person, across Kentucky via telehealth.

Service-area exclusions: Bardstown, Clarksville, Jeffersonville, and New Albany must not render as active footer coverage links unless explicitly approved later.

## Breadcrumbs

Implementation target: Gutenberg-native breadcrumb block patterns generated from approved relationship data.

Rules:

- Use Home as the first breadcrumb.
- Use approved hub/core parent relationships, not raw URL segment parsing.
- Use visible Gutenberg block markup only; do not import Astro breadcrumb components.
- Keep breadcrumb display labels separate from SEO titles and schema fields.
- BreadcrumbList JSON-LD can be added later through approved SEO/schema tooling; do not place PHP in block-template HTML.

## Open Decisions

- Specialties dropdown: top-level link only, or expose a separate dropdown as well?
- FAQ dropdown: top-level link only, or expose About LMHG, Our Approach, and Cost?
- HIPAA footer link: point to `/compliance/`, or stay pending until a separate destination exists?
- Client Portal: keep as `#` until an external portal URL is supplied.
