# LMHG Block WordPress Working Contract

## Source and runtime authority

- `main` is the definitive source for current code and working rules. Do not
  create or preserve another long-lived implementation branch unless the user
  explicitly requests one.
- Existing reference branches are historical evidence, not current authority;
  reconcile useful rules into `main` instead of reviving those branches.
- The current development site is `http://100.116.130.39:8093/` and runs from
  `/srv/codex/services/lmhg-blockwp-wordpress-mariadb` with WordPress 7.0.2,
  PHP 8.3, and MariaDB 10.11 in Docker Compose. This private/noindex development
  URL is operational, not canonical, and may change.
- The canonical public site is `https://louisvillementalhealth.org/`, hosted on
  SiteGround. Do not infer its state from the development URL or access or change
  it without an explicit production request and the approved SiteGround
  connection workflow.
- Development-site publication is allowed after proportional validation.
  Production is a separate environment with separate release rules.
- Never commit credentials, runtime secrets, database files, backups, or
  licensed third-party plugin files.

## WordPress-only implementation boundary

- WordPress is the only implementation surface. Do not copy or port Astro
  components, templates, CSS, markup, routes, asset paths, content schemas,
  build commands, or deployment assumptions.
- Historical Astro and reference-branch material may be consulted only for
  rationale. Revalidate every retained decision against current `main`, the
  accepted MariaDB editor state, current canonical routes, and rendered
  WordPress output.
- Before visible content or theme work, read `DESIGN.md`,
  `docs/project-authority.md`, and `docs/content-design-provenance.md`.
- Use the narrowest durable WordPress surface that owns the requirement:
  editor-managed blocks for page copy, `wp2026-page-data.json` only when rebuild
  durability is intentional, theme tokens/templates/parts for shared
  presentation, and the LMHG Site Core plugin for dynamic or cross-page
  behavior.
- Prefer implementation surfaces in this order: core blocks; block attributes
  and supports; `theme.json`; template parts; approved patterns; templates;
  minimal shared CSS; narrow Site Core behavior; a custom block only when the
  preceding surfaces cannot meet the requirement.

## Content and design preflight

- Before changing content or presentation, identify the current route, assigned
  template, raw Gutenberg content, relevant metadata and relationships, owning
  render layer, and current editor and public output.
- MariaDB is the current editor-managed state. The tracked
  `wp2026-page-data.json` file is a baseline and migration input, not an
  automatic export of every editor change. Never replace intentional editor
  changes from that baseline wholesale.
- Record whether a new fact or design decision comes from explicit owner
  direction, current MariaDB content, current tracked source, current runtime
  evidence, or historical research. Do not promote inference or historical
  material to an approved rule.
- Check existing page-family composition and shared classes before creating a
  new layout. A composition that will repeat should become an approved pattern
  or dynamic block through a separately reviewed implementation, not copied raw
  HTML.

## Content authoring invariants

- For ordinary Pages and Posts, the WordPress title is the H1 source and body
  headings begin at H2. The front page is the sole content-authored H1
  exception. Do not rely on render-time duplicate-title suppression as an
  authoring strategy.
- Save valid Gutenberg serialization. Block delimiters must be paired, and
  comment attributes must agree with saved wrapper elements, classes, and
  inline styles. Do not add unsupported attributes to core-block HTML.
- Publicly named Articles are conventional WordPress Posts. Do not create new
  `article-page` Pages; preserve the five legacy Article Pages unless a separate
  migration is approved.
- Helpful Articles are manually assigned published Posts, limited to three.
  Do not infer those placements from taxonomy or add a duplicate in-copy list.
- FAQ questions without a supported, practice-specific answer remain Draft. A
  published FAQ must contain an answer, and owner-published answers must be
  preserved unless the owner approves a revision.
- Do not hand-author copies of Site Core-generated FAQs, Helpful Articles,
  relationship sections, team directories, shared heroes, or lower-page CTAs.

## Responsive design invariants

- Every public WordPress Page and Post has exactly one visible H1.
- Every public Page/Post H1 stays on one rendered line at every supported viewport,
  without overflowing its container or the document.
- Do not assign page-specific fixed font or box sizes. Presentation sizing must
  derive from shared fluid tokens, intrinsic layout, and container- or
  viewport-relative constraints. Reserve absolute values for true invariants
  such as hairline borders and minimum accessible target sizes.
- H1 sizing must derive from the shared theme token plus title-length and
  container-relative custom properties.
- Generic text-containment rules must preserve the fitted-H1 nowrap exception.
  All other text must wrap inside its box without horizontal document overflow.
- Validate at 319, 360, 390, 600, 768, 1024, 1292, and 1440 CSS pixels with
  `npm run test:responsive-h1` and `npm run test:responsive-text`.

## Gutenberg and theme invariants

- Core blocks, theme templates, template parts, `theme.json`, and the LMHG Site
  Core plugin are the preferred implementation surfaces.
- Public and editor markup must not produce Gutenberg recovery prompts.
- `wp-content/themes/wordpress-2026/style.css` is mirrored into
  `wp-content/themes/wordpress-2026/theme.json` under `styles.css`. After every
  CSS change, synchronize and verify that mirror before publication.
- Keep public behavior in tracked theme/plugin code. Database content remains
  editable through WordPress and must not become the only copy of a rule.

## Render ownership and development parity

- Do not infer public output from saved `post_content` alone. Final output is
  composed from tracked theme templates and parts, MariaDB content and metadata,
  Site Core save/render filters and dynamic sections, the media-role registry,
  mirrored theme CSS, and browser behavior.
- Identify the owning layer before changing an element: template or part; saved
  block content; Site Core filter or dynamic block; relationship or media
  registry; shared CSS; or final browser output. Do not use a database workaround
  for behavior owned by tracked code.
- The source checkout and the development runtime contain separate theme and
  plugin copies; a Git change or commit does not publish the development site.
  Publish only the intended tracked theme or Site Core files. Never copy an
  entire `wp-content` tree or overwrite uploads, licensed plugins, runtime
  configuration, secrets, tests, or unrelated files.
- After development publication, compare the exact changed source and runtime
  files or hashes, confirm the active theme/plugin and endpoint, preserve the
  development noindex boundary, and inspect logs scoped to the change window.
- Keep disposable screenshots, JSON summaries, and diagnostic captures under
  ignored `.runtime/inspect/`. Historical screenshots and ignored artifacts are
  context, not proof of the current runtime.

## Troubleshooting order

1. Confirm the repository, branch, worktree state, endpoint, containers,
   WordPress version, active theme, and active Site Core plugin.
2. Compare the exact affected source files with the development-runtime copies.
3. Inspect the assigned template, raw block serialization, relevant post meta,
   relationships, and media roles.
4. Inspect Site Core save/render filters and dynamic sections before changing
   stored content or CSS.
5. Check PHP syntax, Gutenberg validity, and the `style.css`/`theme.json` CSS
   mirror.
6. Render the affected route at narrow and desktop widths; inspect the final
   DOM, computed styles, HTTP status, console errors, page errors, and failed
   requests. Capture full-page and target-element screenshots for visible
   changes.
7. Run the complete responsive and Gutenberg suites when the change affects a
   shared layout, stylesheet, template, part, or editor-visible serialization.
8. Inspect scoped PHP, WordPress, web-server, browser, and network logs, then
   recheck source/runtime parity after the final publication.

Do not patch a later layer merely to conceal branch, publication, serialization,
or render-filter drift discovered earlier in this sequence.

## Database-writing coordination

- Serialize editor saves, migrations, development publication, and
  database-writing verification through one coordinator. Read-only source and
  browser audits may run concurrently.
- Create and verify a fresh MariaDB backup before durable content changes or a
  release that can execute a migration.
- `npm run test:gutenberg -- --ephemeral-admin` creates and removes a temporary
  administrator and can temporarily touch edit-lock state. Require its cleanup
  and lock-restoration evidence.
- `npm run test:article-runtime` creates and deletes a temporary published Post.
  Run it serially and require cleanup status `passed`.

## Required closeout evidence

- Run `git diff --check` for every tracked change and PHP syntax checks for every
  changed PHP file.
- Run `npm run test:page-data` after changing the tracked page-data baseline.
- Run `npm run test:responsive-h1` and `npm run test:responsive-text` after
  changing shared CSS, typography, containers, templates, parts, or rendering
  behavior. Validate all published Pages and Posts at every supported viewport.
- Run `npm run test:gutenberg -- --ephemeral-admin` after changing block
  serialization, templates, parts, editor-visible rendering, or save
  normalization. Record the summary path and confirm administrator and edit-lock
  cleanup.
- Run `npm run test:article-runtime` after changing conventional Posts, Article
  permalinks, the Article hub, `BlogPosting` schema, Post sitemap behavior, or
  Helpful Articles. Confirm temporary-Post cleanup.
- Run the applicable `npm run test:site-core:accessibility` and
  `npm run test:site-core:redirects` contracts after changing those surfaces.
- For visible changes, report affected routes and archetypes, owning templates
  or renderers, tested viewports and states, screenshots, HTTP results, H1 and
  overflow results, console/network evidence, and Gutenberg validity where
  applicable.
- Report exact commands, route and viewport counts, evidence paths, cleanup
  status, and source/runtime parity rather than only stating that tests passed.
- Commit and push verified development-site work to `main`.
