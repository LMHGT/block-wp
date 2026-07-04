# WordPress 2026 Design Contract

Generated: 2026-07-04

This document records the present design state for the LMHG WordPress 2026
runtime. It is a runtime-derived contract, not a redesign proposal.

## Authority

- Runtime source of truth: `http://100.70.222.25:8093`
- Working repo: `/Users/tyler-lcsw/projects/lmhg-blockwp`
- Active theme: `wp-content/themes/wordpress-2026`
- Durable behavior plugin: `wp-content/plugins/lmhg-site-core`
- Gutenberg project state: `.wp-gutenberg-designer`
- Runtime mirror: `/srv/storage/services/wordpress 2026/wordpress`

Astro remains read-only reference context. The only approved Astro inputs in
this repo are Core30 documentation and redirect candidates under
`data/lmhg/astro-reference`.

## Core30 Design Inputs

Status: `confirmed reference input`

Core30 is new to this repo and the WordPress runtime will not discover it unless
future design and implementation work intentionally references it. Use these
local files as the design-facing Core30 source set:

- Primary reference:
  [`../data/lmhg/astro-reference/core30/core30-keyword-architecture.md`](../data/lmhg/astro-reference/core30/core30-keyword-architecture.md)
- Structured reference:
  [`../data/lmhg/astro-reference/core30/core30-keyword-architecture.json`](../data/lmhg/astro-reference/core30/core30-keyword-architecture.json)
- Historical analysis:
  [`../data/lmhg/astro-reference/core30/CORE30_ANALYSIS.md`](../data/lmhg/astro-reference/core30/CORE30_ANALYSIS.md)
- Historical implementation notes:
  [`../data/lmhg/astro-reference/core30/CORE30_IMPLEMENTATION.md`](../data/lmhg/astro-reference/core30/CORE30_IMPLEMENTATION.md)
- Extracted source snapshot:
  [`../data/lmhg/astro-reference/core30/source-core30.ts.txt`](../data/lmhg/astro-reference/core30/source-core30.ts.txt)

Use `core30-keyword-architecture.md` and
`core30-keyword-architecture.json` first. The analysis and implementation docs
contain useful rationale and earlier plans, but they also include older route
ideas and should not override the current keyword architecture.

Core30 design implications:

- The homepage owns the broadest entity intent: mental health clinic and mental
  health services in Louisville.
- `/services/` is the commercial navigation hub for mental health services,
  therapy, and counseling language.
- Broad service categories should act as service-family pages.
- More-specific service, specialty, modality, court-service, community-service,
  and concern pages should each own one dominant commercial intent.
- Related pages should be crawlable HTML links, not hidden data or purely visual
  cards.
- Location and delivery-mode pages support local relevance and access, but must
  avoid duplicating service-page intent.
- Articles should absorb informational, comparison, and modifier demand that
  would otherwise create duplicate commercial pages.
- Breadcrumbs and related-page sections should reflect canonical parent context
  while still allowing secondary relationships where they help users understand
  fit.

Core30 service-family map for design planning:

| Broad service family | Canonical page | Direct specific pages from Core30 |
| --- | --- | --- |
| Individual Counseling | `/individual-counseling/` | `/adult-counseling/`, `/anxiety-depression-therapy/` |
| Child Counseling | `/child-counseling/` | `/adolescent-counseling/`, `/play-therapy/`, `/child-behavioral-intervention/` |
| Family Therapy | `/family-therapy/` | `/attachment-therapy/`, `/parenting-support/` |
| Couples Counseling | `/couples-counseling/` | `/relationship-counseling/`, `/couples-conflict-resolution/` |
| Court-Ordered Services | `/court-ordered/` | `/family-reunification/`, `/co-parenting/` |
| Community-Based Services | `/community-based-services/` | `/case-management/`, `/community-support/`, `/therapy-in-your-home/` |
| Group Therapy | `/group-therapy/` | No direct child pages currently assigned |
| Trauma Therapy | `/trauma-therapy/` | `/emdr-therapy/` |

The design system must support this map before a service-page pass is considered
complete. That does not mean every page uses the same card layout. It means the
visual system must make parent category, primary service intent, secondary
relationships, and next-step CTA obvious and consistent.

## Status Model

Use these labels when evaluating design work:

- `confirmed`: already implemented in the 8093 runtime and acceptable as a
  baseline design rule.
- `provisional`: present in the runtime, but still needs review before it
  becomes a long-term design rule.
- `open`: template shell or content category exists, but detailed design rules
  are not yet decided.
- `blocked`: cannot be finalized until a dependency is installed, reviewed, or
  approved.

## Confirmed Baseline

The following are confirmed design rules for the current runtime:

- The site is a Gutenberg-native block theme using `theme.json` version 3.
- The active layout system uses `900px` content width and `1180px` wide width.
- The active palette is the `wp2026-*` token set in `theme.json`.
- The active type family is `Inter, Arial, sans-serif`.
- Header, menu placement, global CTA placement, footer, and homepage pilot
  sections are the current approved baseline.
- Standard page shells use a header, one H1 from post title, post content, and
  footer.
- The homepage uses full-width bands, a centered hero with logo watermark, a
  start-care bar, a service-card grid, and a closing CTA band.
- Core30 is approved as a design and information-architecture reference for
  service-family grouping, page intent, relationship planning, and article/FAQ
  separation.
- The runtime remains private/noindex until live use is explicitly approved.

## Provisional Baseline

The following exist and may be used carefully, but should be revisited as page
category rules mature:

- The logo watermark is used in the homepage hero and page-title treatment.
- Generic `.wp2026-content-section` paragraphs currently carry many imported
  page bodies. These are content containers, not final content design systems.
- Breadcrumbs are present in page content and styled globally, but their exact
  placement and hierarchy policy should be finalized per page category.
- The `.wp2026-page-cta` block is the current generic CTA, but page-category CTA
  variants are still open.
- `.wp2026-service-card` is confirmed for the homepage services grid only. It is
  not yet the universal card pattern for service pages, related-content blocks,
  articles, FAQs, or locations.

## Design Tokens

Color tokens from `wp-content/themes/wordpress-2026/theme.json`:

| Token | Hex | Use |
| --- | --- | --- |
| `wp2026-green` | `#31584a` | Primary actions, links, brand emphasis |
| `wp2026-deep` | `#152821` | Header text, footer anchors, high-emphasis text |
| `wp2026-mint` | `#dce9df` | CTA panels and soft section backgrounds |
| `wp2026-cream` | `#f7f3ed` | Warm neutral surfaces |
| `wp2026-clay` | `#a75f3f` | Accent borders, breadcrumbs, secondary emphasis |
| `wp2026-ink` | `#1f2a26` | Body text |
| `wp2026-soft` | `#f8fbf8` | Soft white-green banding |

Typography:

- Body and controls: `Inter, Arial, sans-serif`.
- Headings: same family, line-height `1.08`, letter spacing `0`.
- Body copy: line-height approximately `1.6`.
- Lead copy: `.wp2026-lead`, `.wp2026-home-lead`, and
  `.wp2026-section-lead` use larger sizes and max-width constraints.

Spacing and layout:

- Main constrained content width: `900px`.
- Wide layout width: `1180px`.
- Full-width section padding uses viewport-aware horizontal padding:
  `max(18px, calc((100vw - 1180px) / 2))`.
- Primary section padding uses `clamp(32px, 5vw, 72px)`.
- Main responsive breakpoints currently appear around `1120px`, `1000px`,
  `760px`, and `680px`.

## Global Components

### Header

Status: `confirmed`

The header is sticky and uses:

- Site title and tagline on the left.
- Navigation aligned to the right.
- Nested Services menu with service-family links.
- Top-level links for Specialties, Team, Locations, FAQ, and Contact.
- A persistent `Reach Out` CTA.

Design policy:

- Keep the header compact and operational. Avoid marketing-style expansion.
- Preserve the Services nested menu until a replacement navigation structure is
  explicitly approved.
- Keep the primary CTA visible on desktop and reachable on mobile.
- Do not add page-builder navigation or duplicate menu systems.

### Footer

Status: `confirmed`

The active footer is the baseline footer with:

- Trust badges.
- About/coverage column.
- Other links column.
- Louisville office contact details.
- Legal links.
- Insurance and service-area note.

Design policy:

- Keep footer content structured and factual.
- Do not add decorative card stacks inside the footer.
- Preserve office, phone, fax, insurance, legal, and service-area information
  unless content ownership changes.

### Buttons

Status: `confirmed`

Default button treatment:

- Green fill.
- Clay border.
- White text.
- `6px` radius.
- Minimum height around `46px`.

Design policy:

- Use button blocks only for clear actions.
- Primary CTA text currently standardizes on `Reach Out`.
- Avoid multiple competing primary CTAs in the same section.

### Breadcrumbs

Status: `provisional`

Current treatment:

- `.wp2026-breadcrumbs`
- Clay text.
- Bold `0.9rem`.
- Green links.

Open decision:

- Whether breadcrumbs should remain inline page content, become a reusable
  pattern, or be generated by plugin/theme behavior.

### Generic Page CTA

Status: `provisional`

Current treatment:

- `.wp2026-page-cta`
- Mint background.
- Green left border.
- Heading, short paragraph, and `Reach Out` button.

Open decision:

- Whether every page category gets this same CTA or category-specific variants.

## Homepage

Status: `confirmed baseline`

The homepage is the only fully shaped page experience at this stage.

Current sections:

- Header.
- Full-width hero with centered copy and logo watermark.
- Start-care bar with four step cards.
- Services overview section with service-card grid.
- Closing CTA band.
- Footer.

Design policy:

- Do not restructure the homepage without explicit approval.
- Preserve the current section order while making small refinements.
- Treat homepage service cards as homepage-specific until service-page rules are
  approved.
- Keep the hero focused on immediate orientation and starting care, not a long
  marketing pitch.

## Page Categories

The theme defines template shells for several page categories. Most category
templates currently share the same pattern:

- Header template part.
- Main wrapper class for the category.
- Post title as H1.
- Post content.
- Footer template part.

That shell is confirmed. Detailed category design rules are not yet confirmed.

| Category | Template | Status | Notes |
| --- | --- | --- | --- |
| Home | `front-page.html` | `confirmed` | Full homepage layout exists. |
| Generic page | `page.html` | `confirmed shell` | Useful fallback. Not a category-specific design. |
| Services hub | `services-hub.html` | `open` | Needs hub layout, service grouping, and card/list policy. |
| Service page | `service-page.html` | `open` | Needs service-page pattern and relationship blocks. |
| Specialties hub | `specialties-hub.html` | `open` | Needs specialty discovery rules. |
| Specialty page | `specialty-page.html` | `open` | Needs specialized treatment distinct from service families. |
| Location/access page | `location-access-page.html` | `open` | Needs physical/telehealth/community access pattern. |
| Article hub | `article-hub.html` | `open` | Needs article listing, filtering, and related service policy. |
| Article page | `article-page.html` | `open` | Needs editorial reading pattern. |
| FAQ hub | `faq-hub.html` | `open` | Needs FAQ taxonomy, grouping, and schema policy. |
| FAQ page | `faq-page.html` | `open` | Needs question/answer template and cross-link policy. |
| Trust page | `trust-page.html` | `open` | Currently used for contact/reviews-like trust pages. Needs rules. |
| Legal utility page | `legal-utility-page.html` | `open` | Needs legal/plain-language constraints. |
| Single post | `single.html` | `open` | Should align with article-page once editorial rules are decided. |
| 404 | `404.html` | `confirmed minimal` | Simple explanation plus Reach Out CTA. |

## Open Page-Category Decisions

### Service Pages

Status: `open`

Need to decide:

- How each service page visually expresses its Core30 role: broad category,
  specialty/specific page, concern page, court-service page, or
  community-service page.
- Standard hero structure for service families.
- Whether service pages use a two-column intro, single-column clinical copy, or
  a hybrid.
- Required sections such as overview, who it helps, what to expect, related
  specialties, common questions, insurance/access note, and CTA.
- Whether related services are rendered as cards, links, compact lists, or a
  plugin-generated relationship block.
- How service-family pages differ from specialty pages.
- Whether child pages/sibling pages appear near the top, near the CTA, or both.
- How to preserve one dominant commercial intent while still showing secondary
  relationships.

Minimum consistency rule now:

- Use one H1.
- Keep breadcrumbs above the primary content.
- Keep body content crawlable.
- Use Core30 primary keyword and page-type ownership when deciding H1, intro,
  related links, and CTA language.
- Keep the current generic CTA until a service-specific CTA is approved.

### Specialty Pages

Status: `open`

Need to decide:

- Whether specialty pages should be narrower and more condition/situation
  focused than service pages.
- How to show parent service family and sibling specialties.
- Whether to include an FAQ cluster by default.
- How to avoid over-promising clinical fit while still making the next step
  clear.

### Services And Specialties Hubs

Status: `open`

Need to decide:

- Hub card density and grouping.
- Whether hubs should use service-family grouping, audience grouping, or
  problem/need grouping.
- Whether all links are visible at once or grouped behind accordions.
- How Core30 keyword structure informs hub organization without importing Astro
  UI patterns.
- How to show the eight broad service families without turning the hub into an
  overloaded directory.

### FAQ

Status: `open`

Need to decide:

- Hub organization: categories, search-like index, or short grouped landing
  page.
- Question page shape: standalone answer page, accordion group, or both.
- FAQ schema ownership: theme, plugin, SEO plugin, or manual field.
- How to distinguish general information from clinical advice.
- Whether FAQ pages always include related services and a Reach Out CTA.
- How cost, insurance, approach, and about-LMHG questions cross-link to service
  pages.
- Which FAQ topics support Core30 commercial pages and which should become
  article/support content instead.

Current policy:

- Do not implement FAQ schema until ownership is decided.
- Do not rely on accordion-only content if it harms crawlability or editor
  portability.
- Keep FAQ answers linked to service-family or support pages where Core30
  relationship logic supports the link.

### Articles

Status: `open`

Need to decide:

- Article hub layout and listing controls.
- Article page reading width, byline/date policy, and related-service placement.
- Whether article pages use the same CTA as service pages.
- How article pages link back to Core30 themes and service pages.
- Whether articles remain WordPress pages or move to posts/custom post types.
- Which Core30 article backlog items are first-class launch content and which
  are later SEO support.

### Location And Access Pages

Status: `open`

Need to decide:

- Separate visual treatment for office, telehealth, community, in-home, and
  school-based pages.
- Whether to use maps, address blocks, service-area cards, or access pathways.
- How to handle service-area pages for Louisville, Jefferson County, Bullitt
  County, and Oldham County.
- How to explain one physical office plus multiple care settings without
  implying separate clinics.
- How to support local relevance without creating doorway-page layouts or
  duplicate service-page content.

### Trust, Contact, Reviews, Team, Careers

Status: `open`

Need to decide:

- Whether contact, reviews, meet-the-team, careers, insurance, compliance, and
  legal pages share one trust/utility design system or split into separate
  categories.
- Review page presentation and moderation/source policy.
- Provider/team card structure.
- Careers callout and application path.
- Contact page structure for phone, fax, office, hours, insurance, and intake.

### Legal Utility Pages

Status: `open`

Need to decide:

- Plain-language legal layout.
- Readability rules.
- Whether legal pages suppress large CTAs or keep the generic page CTA.
- How privacy, terms, compliance, and records requests cross-link.

## Consistency Policy

These rules apply before any new page-category design is accepted:

- Update this document when a page category moves from `open` to `confirmed`.
- Prefer `theme.json`, templates, template parts, and Gutenberg core blocks
  before custom CSS or custom blocks.
- Keep global tokens in `theme.json`; do not introduce one-off colors in page
  content unless they become named tokens.
- Keep page-category selectors under the `wp2026-` namespace.
- Do not make a visual rule by editing only one page's imported content.
- Do not use a page builder.
- Do not import Astro source code, templates, CSS, component markup, or old block
  markup.
- Use Core30 as an information-architecture and design-planning reference, not
  as a source of Astro UI implementation.
- Do not import redirects into Rank Math until Rank Math is installed and the
  import workflow is reviewed.
- Keep public frontend markup cache-friendly: no unnecessary anonymous cookies,
  random nonces, or per-user anonymous HTML.
- Keep CTAs consistent and avoid competing primary actions.
- Avoid nested cards. Use cards for repeated items only when the category rule
  explicitly calls for cards.
- Text must fit on mobile and desktop without overlapping or forcing horizontal
  scrolling.

## Accessibility And SEO Guardrails

Current project criteria require:

- One H1 maximum per page.
- Logical heading order.
- Crawlable text.
- Image alt intent.
- Alt text for meaningful images.
- No nested interactive elements.

Additional design rules:

- Do not hide core page content in purely visual or script-dependent UI.
- Accordions/toggles must keep content accessible and crawlable.
- Button/link labels must describe the action.
- Long page titles and service names must be tested on mobile.
- Any image-heavy category must define alt-text and loading behavior before
  implementation.

## Implementation Gates

Before considering design work complete, run the checks that match the change:

```bash
npm run check:static
node .codex/skills/wp-block-themes/scripts/detect_block_themes.mjs
npm run runtime:verify
npm run verify
```

For runtime sync on Dell:

```bash
WP2026_WORDPRESS_DIR="/srv/storage/services/wordpress 2026/wordpress" npm run runtime:sync
WP2026_WORDPRESS_DIR="/srv/storage/services/wordpress 2026/wordpress" npm run runtime:verify
```

When plugin PHP changes are included, also run targeted PHP lint for touched PHP
files.

## Next Design Work

Recommended order:

1. Service hub and service page pattern.
2. Specialty hub and specialty page pattern.
3. FAQ hub, FAQ page, and schema ownership policy.
4. Location/access page pattern.
5. Article hub and article page reading pattern.
6. Trust/contact/reviews/team/careers utility system.
7. Legal utility page rules.

Each pass should produce:

- Confirmed template purpose.
- Required sections.
- Allowed block patterns.
- Relationship/cross-link rules.
- Core30 page intent and keyword ownership notes.
- CTA policy.
- Accessibility and SEO checks.
- Runtime verification evidence.
