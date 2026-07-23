# LMHG content, route, and design provenance

Status: current reconciliation record
Last verified: 2026-07-23

This document preserves the useful decisions discovered on historical branches
without restoring their retired runtime instructions or raw research archives.
Current code and the accepted OVH/MariaDB development site remain authoritative
for implemented state.

## Provenance and precedence

Use sources in this order for content and design work:

1. Current owner-approved content in MariaDB and explicit new owner direction.
2. Current tracked LMHG plugin, theme, templates, and page-data baseline on
   `main`.
3. Direct owner answers retained in the historical `resources` branch.
4. Route-specific SEO evidence after mapping it to a current canonical route.
5. Model inferences, draft copy, Core30 notes, Astro snapshots, and raw
   DataForSEO exports as non-authoritative research.

The historical evidence can be inspected without checking out or merging its
branch:

```bash
git show resources:resources/seo-copy-intake/brand-and-page-copy-intake.json
git show resources:resources/seo-copy-intake/page-architecture-recommendations.md
git show resources:resources/seo-copy-intake/rank-math-keyword-map.json
git show reference/wordpress-2026-full:docs/design.md
```

Those files contain useful reasoning, but their old paths, source machines,
Dell host, Playground/SQLite runtime, and deployment commands are superseded.
Do not import the raw DataForSEO archive into the deployable source tree.

## Current canonical routes

The canonical public origin is `https://louisvillementalhealth.org/`, hosted on
SiteGround. The route inventory in `wp2026-page-data.json` and the accepted
development runtime define current paths, but the private development origin is
not canonical and may change. Some historical SEO records use proposed aliases;
translate them before writing links, metadata, relationships, or documentation.

| Historical or proposed path | Current project path |
| --- | --- |
| `/services/` | `/our-services/` |
| `/articles/` | `/blogs/` |
| `/careers/` | `/we-are-hiring/` |
| `/individual-counseling/` | `/individual-therapy/` |
| `/child-counseling/` | `/child-therapy/` |
| `/court-ordered/` | `/family-court/` |
| `/faq/about-lmhg/` | `/what-we-do/` |
| `/articles/<article-slug>/` | `/<article-slug>/` for the five existing Article Pages |

Current consolidation decisions are:

- `/relationship-counseling/` resolves to `/couples-counseling/`.
- `/couples-conflict-resolution/` resolves to
  `/conflict-resolution-counseling/`.
- `/therapy-in-your-home/` resolves to `/locations/in-home/`.

Do not create a second page at an alias. Preserve one canonical destination and
verify the exact redirect behavior, canonical metadata, sitemap membership, and
internal links after route work.

## Durable information architecture

| Service family | Current family page | Direct specific pages |
| --- | --- | --- |
| Individual Counseling | `/individual-therapy/` | `/adult-counseling/`, `/anxiety-depression-therapy/` |
| Child Therapy | `/child-therapy/` | `/adolescent-counseling/`, `/play-therapy/`, `/child-behavioral-intervention/`, `/parenting-support/` |
| Family Therapy | `/family-therapy/` | `/attachment-therapy/`, `/conflict-resolution-counseling/` |
| Couples Counseling | `/couples-counseling/` | No separate Relationship Counseling page |
| Court-Ordered Services | `/family-court/` | `/family-reunification/`, `/co-parenting/` |
| Community-Based Services | `/community-based-services/` | `/case-management/`, `/community-support/`, access through `/locations/in-home/` and other location pages |
| Group Therapy | `/group-therapy/` | No fixed child page |
| Trauma Therapy | `/trauma-therapy/` | `/emdr-therapy/` |

Content boundaries carried forward from direct owner decisions:

- Use **Parent-Child Attachment Therapy** for parent-child work; do not add an
  adult attachment page without a new decision.
- Use **Child Behavioral Therapy** as the public label.
- Parenting Support belongs under Child Therapy.
- Conflict Resolution Counseling serves non-couple family relationships;
  romantic-partner work belongs on Couples Counseling.
- Family Reunification is a distinct structured service, not a synonym for
  ordinary therapy or counseling.
- Co-Parenting can be proactive or court-related, but it is not mediation, a
  custody evaluation, or a parenting class.
- Keep **Case Management** as the primary public label. “Targeted case
  management” may be supporting language; it does not justify a duplicate page.
- In-home therapy is a care setting at `/locations/in-home/`, not a duplicate
  service page.
- Do not add a new commercial page solely to capture another keyword. Use
  Articles and FAQs for informational intent that would overlap a service page.

## Editorial guardrails

- Write in plain, practical, human language, generally at or below a sixth-grade
  reading level.
- Speak to the person seeking help; avoid institutional or hospital-style
  language when it does not describe LMHG.
- Keep clinical, insurance, availability, timing, and service claims specific
  and supportable. Qualify facts that vary by person, provider, plan, or service.
- Do not promise outcomes, emergency response, crisis availability, legal
  advice, custody opinions, transportation, resources, or services LMHG does
  not provide.
- Direct emergency or crisis needs to appropriate emergency/crisis resources,
  not the routine scheduling flow.
- Use the shared **Reach Out** action as the primary next step. A phone call may
  be a secondary action where useful; avoid competing primary CTAs.
- Use descriptive, contextual internal links. Do not repeat the same link in a
  thin presentation section merely to satisfy an SEO checklist.
- Keep relationship data even when its visual presentation is retired. The
  generic Related Pages display is intentionally removed; Helpful Articles are
  manually curated and limited to the most useful associations.

## Durable design rules

The historical design contract supplied rationale, but `DESIGN.md` now defines
the current WordPress design intent. Confirm implemented state against
`theme.json`, templates, CSS, the LMHG Site Core plugin, and `AGENTS.md`.

- Use Gutenberg core blocks, block-theme templates and parts, `theme.json`, and
  the LMHG Site Core plugin. Do not add a page builder or a duplicate component
  system.
- Use the `wp2026-*` palette and shared typography/layout tokens. Current
  constrained and wide widths are `900px` and `1180px`; do not turn those
  values into page-specific box sizing.
- Standard Pages and Posts render one visible H1. It must stay on one line at
  every supported viewport without overflowing its container or the document.
- Derive H1 fitting from the shared title token, title length, and
  container-relative properties. Preserve the fitted-title nowrap exception
  while allowing all other text to wrap.
- Keep presentation fluid and intrinsic. Avoid page-specific fixed font or box
  dimensions; absolute values are reserved for true invariants such as
  hairlines and minimum accessible target sizes.
- Keep the header operational, preserve one clear global CTA, and keep footer
  contact, insurance, legal, and service-area information factual.
- Use cards for genuine repeated collections, not nested decoration. Keep core
  content and links accessible and crawlable without script-only presentation.
- Preserve logical heading order, descriptive controls and links, meaningful
  image alt text, and non-nested interactive elements.
- `wp-content/themes/wordpress-2026/style.css` and
  `theme.json.styles.css` are one mirrored stylesheet and must remain identical.
- Public markup and saved editor markup must remain valid Gutenberg
  serialization without recovery prompts.

## Making a new content or design decision

1. Confirm the current development route and editor state before using a
   historical brief.
2. Record whether the decision came from an owner, current runtime evidence,
   current source, or research.
3. Check `DESIGN.md` for the approved page archetype, element owner, decision
   state, and any open conflict that requires approval before implementation.
4. Reconcile the smallest durable change into the appropriate source: database
   content, page-data baseline, theme, or plugin.
5. Preserve existing relationship data unless the content model itself is being
   intentionally changed.
6. Run the validation and publication gates in `AGENTS.md` and
   `docs/project-authority.md`.
