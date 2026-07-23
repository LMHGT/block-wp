# Gutenberg Integrity Tooling and Regression-Safety Plan

Status: approved implementation plan
Created: 2026-07-23
Branch: `codex/gutenberg-integrity-tooling`
Worktree: `/srv/codex/projects/lmhg-blockwp-gutenberg-tooling`

## Objective

Give independent agents deterministic preflight and runtime gates that prevent
Gutenberg recovery prompts, invalid-block warnings, silent serialization drift,
and incomplete editor coverage from reaching `main`.

The primary acceptance target is every published WordPress Page and Post on the
accepted development runtime. Tracked block-theme templates, template parts,
and page-data content are also source candidates and must be validated even when
they are not currently represented by a published database record.

The canonical public site, `https://louisvillementalhealth.org/`, is hosted on
SiteGround and is out of scope. The current private/noindex development endpoint
is operational, may change, and is the only runtime allowed for this pass.

## Known regression classes

Repository history provides the initial fixture and gate requirements:

| Regression class | Prior evidence | Required prevention |
| --- | --- | --- |
| Linked image block attributes and saved wrapper markup diverged | `247d4ab` | Candidate parse/validation fixture plus live editor validity check |
| Not Found template contained invalid block serialization | `06617d6` | All tracked templates/parts included in source preflight |
| Save normalization and editor rendering produced instability | `9061788`, `9aebed0` | Dirty-state and saved-versus-edited serialization diagnostics; bounded draft roundtrip |
| Shared design rules were reapplied after drift | `51408d9` | Project criteria and source inventory must remain explicit and reproducible |
| Editor scanner could strand temporary identity/lock state | `986b5d8`, `533b507` | Preserve exact-ID-first cleanup, lock snapshots, signal cleanup, and fail-closed evidence |

These commits are regression fixtures and design inputs, not permission to
restore their earlier implementation wholesale.

## Separation and safety boundaries

- Keep the primary checkout on clean `main`. Perform tooling work only in the
  worktree and branch named above.
- Do not change CSS, `theme.json`, templates, page content, Site Core rendering,
  media, database migrations, or production configuration to make a validator
  pass. Report an implementation defect separately.
- Do not access or modify SiteGround or the canonical live site.
- Run static and fixture tests before any runtime test that can write.
- Serialize all runtime tests that create a user, draft, Post, lock, revision,
  option, or metadata row through one coordinator.
- Existing published content is read-only during validation. Never save a
  published Page/Post as a test strategy.
- Any save/reload proof uses a uniquely named temporary Draft, captures its exact
  ID, and deletes/verifies that exact ID during bounded cleanup.
- Create and verify a fresh MariaDB backup before introducing a new
  save/reload fixture or any test with durable database-write capability.
- Keep credentials and browser state in memory. Do not write passwords,
  cookies, database contents, or raw private editor content to reports.
- Keep reports and screenshots under ignored `.runtime/inspect/`; reports may
  include IDs, slugs, counts, hashes, lengths, and redacted diagnostics only.
- Preserve signal-safe cleanup and an exact recovery marker for any temporary
  identity or content record that survives an interrupted process.
- Each phase receives its own reviewed commit. Do not merge the tooling branch
  into `main` until all mandatory gates pass and the evidence is reviewed.

## Phase 0: Baseline and candidate inventory

1. Confirm branch/worktree, runtime identity, active theme/plugin, WordPress
   version, and development-only URL.
2. Inventory published Pages and Posts through the same authenticated/runtime
   authority used by the live editor scan.
   Require strict REST pagination headers and exact bidirectional ID/type/status
   parity with an authoritative WP-CLI/MariaDB inventory; do not allow a missing
   or malformed pagination header to default to one passing page.
3. Inventory tracked theme templates, parts, and all `content` fields in
   `wp2026-page-data.json`.
4. Run the existing Gutenberg verifier unchanged and retain its cleanup summary
   as the pre-change baseline.
5. Fail if an inventory is empty, truncated, duplicated, or disagrees with its
   declared source authority.

## Phase 1: Deterministic source preflight

Add a tracked source validator or a source-preflight stage to the existing
verifier that:

- reads every tracked template and part plus every page-data block-content
  candidate;
- validates balanced block comments and JSON attributes;
- parses candidates with the active runtime's Gutenberg block registry;
- fails for any block whose editor record or block selector reports invalid;
- serializes, reparses, and serializes again, then requires the normalized
  second and third representations to be stable;
- fails when nonempty candidate content produces an empty block inventory;
- reports candidate identity, block names, counts, hashes, and redacted issue
  details without copying full content into artifacts.

Create valid and invalid fixtures for each known regression class. Unit/fixture
tests must prove that valid fixtures pass and intentionally invalid fixtures
fail for the expected reason.

## Phase 2: Harden the published-content editor scan

Extend `scripts/verify-gutenberg-stability.mjs` without weakening its request
barrier or cleanup behavior:

- retain complete published Page/Post coverage and fail closed on partial scans;
- reconcile the REST inventory with exact WP-CLI IDs before acquiring locks or
  opening an editor;
- assert the editor identity, stable hydration, block count, and invalid-block
  state as it does now;
- require the Gutenberg validity and serialization APIs instead of defaulting to
  valid or falling back to non-equivalent JSON output when an API is absent;
- assert that a no-interaction editor load is not unexpectedly dirty;
- compare saved and edited content using redacted lengths/hashes and flag
  normalization drift without storing the content;
- fail on uncaught page errors and block-validation console signatures;
- inspect recovery/crash warnings in the top document and every same-origin
  editor canvas frame;
- record same-origin failed requests and HTTP 4xx/5xx responses under a reviewed
  fail/allow policy;
- record other console errors separately until a reviewed allow/fail policy is
  defined, avoiding both silent failures and uncalibrated false positives;
- retain UI recovery-prompt detection as defense in depth;
- remove duplicate or ambiguous diagnostic behavior and keep one screenshot per
  failed editor record;
- record exact discovered/scanned/passed/failed counts and cleanup proof.
- compute the final pass state through one invariant that requires runtime
  identity, complete inventories, zero record failures, zero fatal blocked
  writes, verified lock restoration, verified administrator cleanup, and no
  recovery marker.

Do not add arbitrary delays as the primary fix. Continue waiting for verified
editor identity, saved-content hydration, stable block state, and bounded settle
samples.

## Phase 3: Bounded draft roundtrip proof

Add a separate opt-in command for write-capable editor proof:

1. Create a separately named disposable Compose project with separate ports,
   MariaDB and uploads volumes, restored from a fresh accepted-development
   backup. Verify its rewritten `home`/`siteurl`, noindex boundary, and isolation.
2. Create one uniquely named temporary Draft with a chosen fixture.
3. Open it in the editor and require zero invalid blocks or recovery prompts.
4. Save, reload, save without intentional edits, and reload again.
5. Compare exact stored `post_content` after the first and no-op saves. Any
   unexplained change is a failure.
6. Preview the Draft and check HTTP/render success without publishing it.
7. Delete the exact Draft ID and independently prove its ID, slug, revisions,
   route, and related test metadata are absent.
8. Restore and verify any edit-lock state and remove the temporary administrator
   using the existing exact-ID-first cleanup contract.
9. Remove only the explicitly named disposable Compose project and volumes after
   its cleanup evidence is complete; then rerun the accepted-development
   read-only sweep.

A Git worktree alone does not isolate the WordPress database. This command must
never run against the accepted development database and must never iterate save
operations across existing published content.

## Phase 4: Templates, parts, and Site Editor evidence

- Validate `theme.json`, all tracked templates, and both template parts with
  project-aware rules.
- Recognize filenames registered in `theme.json.customTemplates`; a generic
  filename heuristic must not classify those registered templates as blockers.
- Open representative and changed templates/parts in the Site Editor when a
  safe read-only route is available, and fail on load errors, invalid blocks, or
  recovery prompts.
- Detect database template overrides and report whether the tracked file or a
  database customization is active. Do not delete an override automatically.

## Phase 5: Agent and plugin integration

- Add project-local WP Gutenberg Designer profile/criteria so the theme path,
  custom templates, content sources, editor/runtime evidence, and hard gates are
  explicit.
- Prefer project-owned scripts for LMHG-specific runtime and database behavior.
  The installed plugin must not contain hard-coded LMHG paths, credentials, or
  host assumptions.
- Modify the reusable WP Gutenberg Designer plugin only for a demonstrated,
  project-neutral gap with fixtures proving both the old failure and the new
  behavior. Do not edit an ephemeral cache as the only source of a plugin fix;
  locate or establish the authoritative plugin source and publish/version it
  through its own review path.
- Document one agent-facing command sequence: static source preflight, published
  read-only editor scan, optional Draft roundtrip, proportional public rendering,
  cleanup verification, and evidence summary.

## Mandatory merge gates

The branch may be proposed for merge only when all applicable gates pass:

1. Known valid fixtures pass and known invalid fixtures fail deterministically.
2. Every tracked template, part, and page-data content candidate passes the
   source preflight.
3. Every currently published Page and Post is scanned in the real development
   editor with zero invalid blocks and zero recovery prompts.
4. No-interaction editor loads produce no unexplained dirty/serialization drift.
5. The opt-in Draft roundtrip passes twice with exact no-op serialization
   stability and verified cleanup.
6. Temporary administrators, Drafts, revisions, edit locks, recovery markers,
   and test routes are independently absent after cleanup.
7. Page-data, responsive H1/text, Site Core accessibility/redirect, PHP syntax,
   CSS mirror, and `git diff --check` gates remain green where applicable.
8. The accepted development runtime remains healthy and no new scoped PHP,
   WordPress, browser, or HTTP 5xx errors appear.
9. No production or SiteGround access occurred.
10. The reviewed tooling branch is merged into `main`, pushed, and verified to
    match `origin/main`; tooling must not remain authoritative only on a side
    branch.

## Rollback and stop conditions

Stop immediately when:

- runtime identity differs from the approved development environment;
- a published record would need to be saved to continue;
- cleanup identity is ambiguous;
- a temporary administrator, Draft, revision, or lock cannot be removed and
  independently verified;
- source/runtime truth cannot be distinguished;
- a validator produces widespread uncalibrated false positives;
- implementation changes outside the approved tooling scope appear necessary;
- any command targets production or SiteGround.

On failure, preserve the redacted report and exact recovery IDs, complete
cleanup first, leave the tooling branch unmerged, and report the blocker. Restore
from the verified MariaDB backup only when cleanup cannot safely return the
development database to its pre-test state.

## Evidence handoff

The review handoff must include:

- branch, worktree, and commit IDs;
- exact commands and versions;
- source and runtime inventory counts;
- per-gate pass/fail totals;
- invalid fixture failure reasons;
- published editor scan summary;
- Draft roundtrip hashes and cleanup proof;
- report/screenshot paths;
- current runtime health and scoped log result;
- explicit confirmation that production and SiteGround were untouched;
- remaining risks and the exact approval needed before merging to `main`.
