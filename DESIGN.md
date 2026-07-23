# LMHG Block WordPress Design Contract

Status: accepted WordPress design authority
Last reviewed: 2026-07-23

## Purpose and authority

This document defines the durable visual intent, content presentation, page
anatomy, element ownership, media semantics, and responsive outcomes for the
LMHG WordPress site. It does not replace executable source or authorize an
implementation merely because that implementation is described here.

- `AGENTS.md` governs inspection, editing, validation, publication, and
  troubleshooting.
- `DESIGN.md` governs the approved WordPress design system and rendered
  experience.
- `docs/project-authority.md` decides which source or runtime surface answers a
  question.
- `docs/content-design-provenance.md` governs content, routes, owner-approved
  facts, and historical-source precedence.
- Current `main` and the accepted MariaDB development site prove implemented
  state. When implementation and this contract disagree, record the conflict;
  do not silently change either surface.

WordPress is the only implementation. Astro components, templates, CSS, routes,
content schemas, asset paths, build commands, and deployment assumptions are
historical and must not be copied. A framework-independent idea found in
historical material may be reconsidered only after current WordPress source,
current MariaDB content, and current rendered behavior support it or the owner
approves it again.

## Decision states

Use these states when recording a design rule or unresolved conflict:

| State | Meaning |
| --- | --- |
| Confirmed | Approved and implemented in current WordPress source/runtime |
| Provisional | Approved direction awaiting complete implementation evidence |
| Open | Requires an owner or implementation decision before shared use |
| Deprecated | Must not be used for new work; removal may be separately planned |
| Historical | Context only; not current authority |

An Open, Deprecated, or Historical item is not permission to modify CSS, PHP,
theme JSON, content, media, or tooling. Plan regression safety and obtain
approval before implementation.

## Design principles

- Present LMHG as calm, practical, trustworthy, and human rather than
  institutional, hospital-like, or decorative for its own sake.
- Keep the experience text-first, readable, and direct. Visual styling should
  clarify hierarchy, grouping, navigation, or page identity.
- Preserve one clear primary action, **Reach Out**, and avoid competing primary
  CTAs.
- Prefer reusable Gutenberg compositions, shared fluid tokens, and intrinsic
  layouts over page-specific styling or a duplicate component system.
- Preserve editor-managed content and valid block serialization. Public polish
  must not make Gutenberg content fragile or produce recovery prompts.
- Design for real long titles, paragraphs, links, missing optional data, and
  narrow screens rather than only ideal sample content.
- Treat editor output, public output, and the accepted development runtime as
  distinct evidence surfaces.

## Foundations and tokens

### Canonical theme settings

The following values are registered in `theme.json` and are the current
editor-facing foundation:

| Role | Registered value |
| --- | --- |
| Primary green | `wp2026-green` / `#31584a` |
| Deep green | `wp2026-deep` / `#152821` |
| Soft mint | `wp2026-mint` / `#dce9df` |
| Warm neutral | `wp2026-cream` / `#f7f3ed` |
| Clay | `wp2026-clay` / `#a75f3f` |
| Ink | `wp2026-ink` / `#1f2a26` |
| Typeface | Inter with Arial and sans-serif fallbacks |
| Constrained content width | `900px` |
| Wide content width | `1180px` |

Use semantic registered presets and existing shared `--wp2026-*` properties.
Do not invent a new preset name, hard-coded page-family palette, or duplicate
spacing/type scale without a reviewed system-level decision.

### Implemented extensions and open token conflicts

- `--wp2026-link-orange` and `--wp2026-soft` are implemented CSS-only
  extensions; they are not registered editor palette presets.
- Plugin styles currently use fallback names including `surface`, `canvas`,
  `contrast`, `muted-text`, and `evergreen` that are not registered theme
  presets. Their fallbacks prevent immediate failure, but their canonical token
  mapping is Open.
- `theme.json` declares the warm-neutral preset as the body background while the
  mirrored stylesheet currently sets the public body background to white. The
  intended canonical body surface is Open.

Do not resolve these conflicts incidentally during unrelated work. A separate
regression-safe theme plan must choose the canonical values, update all owning
surfaces, and verify editor and public rendering.

## Rendered composition ownership

Public output is a composition of theme templates and parts, MariaDB blocks and
metadata, Site Core filters and dynamic sections, registered media roles,
mirrored theme CSS, and browser behavior. Saved `post_content` is not the whole
page.

| Element | Primary owner | Contract |
| --- | --- | --- |
| Header and footer | Theme template parts | One operational global shell with factual navigation, contact, service-area, insurance, and legal information |
| Page shell and main width | Assigned block template | Select the page-family template; do not recreate the shell inside page content |
| Ordinary Page/Post title | WordPress title plus template/Site Core | Exactly one visible fitted H1; body content begins at H2 |
| Front-page title and hero | Front-page block content | Sole content-authored H1 exception |
| Imported-page shared hero | Site Core plus title, first lead, metadata, and shared CTA | Do not hand-author a second public hero or fabricate source metadata solely to obtain this layout |
| Editorial sections | Gutenberg blocks in MariaDB | Editable, semantic, validly serialized, and consistent with the page family |
| FAQs and FAQ sets | Site Core FAQ records, taxonomy, and dynamic rendering | Do not duplicate generated questions and answers in ordinary page blocks |
| Helpful Articles | Site Core relationships to published Posts | Manual, useful, and limited to three; no taxonomy-inferred fallback |
| Team and relationship collections | Site Core records and dynamic rendering | Preserve relationship data even when a presentation is retired |
| Shared lower-page CTA | Site Core/shared block behavior | One consistent primary Reach Out action; no competing duplicate CTA |
| Global styling | `theme.json` and mirrored `style.css` | Shared tokens and fluid rules; no page-specific fixed layout system |
| SEO and schema | Site Core, Rank Math, and page archetype | Validate the semantic type for the actual page role rather than applying `Article` globally |

When an element appears wrong, diagnose the owning layer before changing its
content or presentation. CSS must not compensate for incorrect template,
metadata, relationship, or render-filter state.

## Page archetypes

| Archetype | Template or source | Required design anatomy |
| --- | --- | --- |
| Home | `front-page.html` plus front-page blocks | Content-authored fitted H1, direct lead, one primary CTA, clear service/access navigation, and restrained supporting collections |
| Standard Page | `page.html` | One title H1, readable editorial flow beginning at H2, contextual links, and only applicable generated sections |
| Services or Specialties hub | `services-hub.html` or `specialties-hub.html` | One title H1, concise orientation, and an accessible card collection that remains crawlable without script-only behavior |
| Service or Specialty detail | `service-page.html` or `specialty-page.html` | Shared hero, page-identity artwork, practical service/specialty sections, contextual relationships, applicable FAQs, and one closing action |
| Location/access Page | `location-access-page.html` | One title H1, setting or access explanation, practical expectations, applicable location media, and no unsupported availability or transportation promises |
| FAQ hub or detail | `faq-hub.html` or `faq-page.html` plus Site Core | Clear question grouping; published answers only; no duplicate page-authored FAQ system |
| Article hub | `article-hub.html` | Legacy Article Page cards followed by current published Posts, normalized and deduplicated links, and readable collection hierarchy |
| Article | `single.html` for new Posts; `article-page.html` only for the five legacy Pages | One title H1, editorial body beginning at H2, Article-specific metadata/schema, and no new legacy Article Pages |
| Team | `team-page.html` plus Site Core | Accessible team directory, consistent portrait treatment, names and credentials in readable order, and responsive collection behavior |
| Contact | `contact-page.html` | Direct contact and scheduling information, clear Reach Out action, factual expectations, and no emergency-response implication |
| Trust or legal/utility | `trust-page.html` or `legal-utility-page.html` | Factual, readable, minimally decorative content with page-role-appropriate metadata and no marketing embellishment |
| Search/index fallback | `index.html` | One H1 and a readable list or fallback appropriate to the returned content |
| Not found | `404.html` | One static H1, helpful recovery links, correct 404 status, and no misleading homepage redirect |

Template-specific schema expectations are part of the archetype: ordinary Pages
use a suitable WebPage type, collection hubs use CollectionPage where
appropriate, contact uses ContactPage, service/specialty/location pages may use
MedicalWebPage plus their specific entities, visible FAQ collections may use
FAQPage, and conventional Article Posts use BlogPosting/Article semantics.

## Gutenberg composition grammar

- Prefer core blocks and supported block attributes. Use an LMHG dynamic block
  only for data that must be queried, related, filtered, or kept consistent
  across pages.
- Use theme templates and parts for the global shell; do not paste header,
  footer, title, or shared hero markup into page content.
- Use Groups for meaningful sections, Columns only for actual parallel content,
  Buttons for actions, Images/Figures for media, Lists for genuine lists, and
  Headings in logical order.
- Do not create new reusable cards, FAQs, images, or layout systems as raw HTML.
  Existing raw legacy fragments may be preserved when necessary, but they are
  not examples for new content.
- Keep block comments paired and keep serialized attributes consistent with the
  saved wrapper, classes, and styles.
- The current theme has no tracked `patterns/` directory. Repeated composition
  may be documented here, but adding a pattern is a separate implementation
  change requiring regression planning and editor/public verification.
- Shared classes such as page-family hero, lead, detail, collection, and CTA
  hooks are contracts between content, Site Core, and CSS. Do not rename or
  repurpose them without examining all three owners.

## Content presentation

- Write in plain, practical, human language, generally at or below a sixth-grade
  reading level.
- Speak to the person seeking help. Keep sentences and answers brief enough to
  scan while retaining necessary clinical, legal, insurance, or service
  qualifications.
- Use conditional language for facts that vary by person, provider, plan,
  service, timing, or availability. Do not promise outcomes, emergency response,
  legal advice, custody opinions, transportation, or services LMHG does not
  provide.
- Use descriptive contextual links and one primary Reach Out action. Do not add
  thin duplicate card/link sections solely for SEO.
- Keep relationship data when presentation changes. Generic Related Pages
  presentation remains retired; Helpful Articles are the manual editorial
  exception.
- New public Articles are Posts. Unanswered or unsupported FAQs remain Draft.
- Preserve established page-family structure and terminology unless explicit
  owner direction changes the underlying content model.

## Media system

- Each service or specialty currently has one canonical watercolor identity
  asset. The same `service-icon-*` or `specialty-icon-*` role is used on its card
  and destination-page hero to prevent a second visual system from drifting.
- Destination-page service/specialty artwork is decorative support for the
  visible title and is rendered with empty alternative text and
  `aria-hidden="true"`. Card use must retain the accessible context required by
  the card and link.
- Older `service-graphic-*` and `specialty-graphic-*` registry entries are
  Deprecated for service/specialty hero use. Their presence in source is not
  permission to reactivate a separate page-graphic system.
- Use images only when they clarify navigation, page identity, people, place, or
  useful context. Avoid generic wellness imagery, corporate-SaaS illustration,
  and decorative filler.
- Use WebP for approved illustration derivatives where appropriate, include
  explicit intrinsic dimensions, and allow WordPress responsive image output.
- Verify actual alpha-channel behavior, dimensions, attachment/role mapping,
  responsive sources, HTTP loading, and rendered HTML. A transparent-looking
  filename or repository file alone is not acceptance evidence.
- Meaningful images require truthful concise alt text. Decorative images require
  empty alt text and must not be the only carrier of information.

## Responsive and accessibility outcomes

- Every public Page and Post has exactly one visible H1.
- The public H1 remains on one rendered line without container or document
  overflow at every supported viewport. It is the sole nowrap exception.
- All other text wraps inside its container. Long links, labels, headings, list
  items, and translated or edited copy must not cause horizontal document
  overflow.
- Use shared fluid type and spacing tokens, intrinsic sizing, and responsive
  grids. Do not introduce page-specific fixed font or box dimensions.
- Preserve semantic landmarks, logical heading order, visible keyboard focus,
  the skip link, descriptive link purpose, accessible control names, minimum
  target sizes, and non-nested interactive elements.
- Collections must handle one item, several items, missing optional media, long
  labels, and narrow screens without empty decorative shells.
- Motion is optional enhancement. Content, navigation, and meaning must remain
  available without animation or script-only presentation.

## Rendering acceptance

A visible change is accepted only when its intended outcome is confirmed in the
Gutenberg editor where applicable and on the public development route. Evidence
must identify the route, archetype, owning template or renderer, target element,
tested widths and states, HTTP result, screenshots, console/page errors, failed
requests, heading count, overflow result, and Gutenberg validity.

The responsive H1 and text suites prove geometry and containment; they are not a
substitute for images-enabled visual rendering. The authenticated Gutenberg
suite proves editor stability; it is not a substitute for public design review.
Use the proportional commands and troubleshooting order in `AGENTS.md`.

## Current open decisions and deferred debt

| Item | State | Constraint |
| --- | --- | --- |
| Canonical public body surface: warm neutral or white | Open | Do not change either owning surface without a regression-safe theme plan |
| Canonical mapping for plugin fallback color names | Open | Do not add more unregistered semantic names; plan editor and public token reconciliation |
| Separate service/specialty page-graphic registry | Deprecated | Do not reactivate; code cleanup requires separate review and render evidence |
| Reusable Gutenberg pattern library | Provisional | Document compositions first; implementation requires editor recovery and public rendering checks |
| Generic browser screenshot/regression harness | Open tooling need | Do not claim visual-regression coverage until a separately approved safety plan exists |
| Tracked CSS synchronization/parity helper | Open tooling need | Continue honoring the mirror contract; tooling implementation is separately gated |

## Recording future decisions

When an approved design decision changes, update this contract with:

1. Date and decision state.
2. Affected page archetype, component, token, or media role.
3. The owner direction or current evidence supporting the decision.
4. The owning WordPress implementation surfaces.
5. Alternatives rejected and the reason.
6. Required editor, public-render, accessibility, responsive, and runtime-parity
   evidence.
7. The rule or debt item superseded.

Keep commands and operational steps in `AGENTS.md`; keep content and route
provenance in `docs/content-design-provenance.md`. This document should remain a
stable design contract rather than a chronological implementation log.
