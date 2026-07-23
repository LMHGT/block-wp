# Gutenberg Validation Runbook

This runbook is the agent-facing gate for WordPress block content, theme
templates, template parts, and editor integration. Astro is not an
implementation or validation surface in this repository.

The canonical live site is `https://louisvillementalhealth.org/` on SiteGround.
The current `http://100.116.130.39:8093/` endpoint is a private/noindex
development runtime, is not canonical, and may change. These commands do not
authorize SiteGround or production access.

## Proof levels

| Proof level | What it proves | What it does not prove |
| --- | --- | --- |
| Static | Project profile, design criteria, file shape, and syntax | Gutenberg editor validity |
| Runtime registry | Tracked block source parses with the active WordPress block registry | Save/reload persistence |
| Runtime read-only | Exact durable inventory opens cleanly in the real editor with writes blocked | No-op save stability |
| Runtime roundtrip | Save, reload, no-op save, and cleanup are stable in a disposable clone | Production behavior |

Never describe static plugin output as editor proof. Never describe the Git
worktree as database isolation.

## Required sequence

Run these serially; do not run editor scans or write-capable tests concurrently.

```bash
npm ci
npm run test:gutenberg:self
npm run test:gutenberg:inventory
npm run test:gutenberg:all-editable -- --ephemeral-admin
```

The shorter published Page/Post diagnostic remains available as:

```bash
npm run test:gutenberg -- --ephemeral-admin
```

The release-scope post-editor command is `test:gutenberg:all-editable`. It discovers all
durable Page, Post, FAQ, and Review records in reviewed statuses. It fails
closed when a new Gutenberg-capable post type or durable status appears without
an explicit classification. The inventory command discovers Navigation, synced
Patterns, templates, and template parts, but their separate real Site Editor
sweep remains a mandatory open gate; ordinary `post.php` coverage must not be
misrepresented as that proof.

After any presentation change, also run:

```bash
npm run test:page-data
npm run test:responsive-h1
npm run test:responsive-text
npm run test:site-core:accessibility
npm run test:site-core:redirects
git diff --check
```

Run PHP syntax checks for every changed Site Core PHP file. If CSS changes,
synchronize the exact `style.css` mirror under `theme.json` `styles.css` before
testing.

## Runtime safety contract

The read-only verifier:

- verifies the configured development `home` and `siteurl`, WordPress 7.0.2,
  active `wordpress-2026` block theme, active LMHG Site Core plugin, and exact
  deployable theme/plugin file parity before mutation;
- runs that pre-administrator identity bootstrap under a database-session
  read-only guard and requires zero blocked write attempts plus rollback proof;
- requires a root-origin runtime URL, proves `DISABLE_WP_CRON` is strictly true
  in the running web container, and independently disables automatic cron in
  every verifier WP-CLI process before any administrator or lock mutation;
- creates one unique ephemeral administrator and independently verifies its
  exact ID and login;
- inventories records through authoritative WP-CLI/MariaDB evidence and
  authenticated REST identity reads;
- snapshots every inventoried `_edit_lock` value before opening an editor;
- persists that exact lock snapshot in a mode-0600 recovery marker until
  restoration is independently verified;
- allows browser reads and the exact login POST only;
- blocks and classifies Heartbeat, preference, lock-release, autosave, content,
  and all other browser writes;
- restores every prior lock, removes scan-created locks, deletes the exact
  administrator ID, and clears recovery markers;
- writes only redacted metadata, hashes, lengths, IDs, slugs, counts, and
  screenshots under ignored `.runtime/inspect/`.

The separate read-only inventory can trigger WordPress core's theme-pattern
cache refresh. Only the exact derived `_site_transient_*wp_theme_files_patterns`
delete/insert attempts are classified as expected; the database guard blocks
them and reports their counts. Every other SQL write target remains a blocker.

A result cannot pass unless inventory parity, complete coverage, runtime
identity, zero record failures, zero fatal writes, lock restoration,
administrator cleanup, and recovery-marker cleanup all pass one final
invariant.

## Editor failure policy

Hard failures include:

- `core/missing`, an unregistered block, or a false/unknown block-validity
  result;
- Gutenberg block-validation, saved-content mismatch, invalid-content, or
  recovery console signatures;
- recovery, crash, or invalid-block UI in the top document or any same-origin
  editor iframe;
- an uncaught page exception or same-origin HTTP 5xx response;
- raw, saved, and edited entity identity/hash disagreement;
- an editor that becomes dirty without interaction;
- active block-tree serialization that differs from a deterministic
  parse/serialize of the saved content;
- incomplete inventory or failed cleanup.

Gutenberg may deterministically canonicalize older but valid serialization even
when the editor is clean. The read-only report records that raw-to-canonical
drift but does not call it an invalid block when the saved entity, edited entity,
active block tree, and deterministic parse agree. Exact persistence belongs to
the isolated roundtrip gate.

The verifier permits only narrow, named navigation/preflight aborts and the
browser error produced by its own proven write barrier. Unknown console errors
and unknown same-origin read failures are fatal. Same-origin HTTP 4xx responses
remain explicit diagnostics because authenticated identity reads and primary
editor navigation already have endpoint-specific hard checks; every 5xx is
fatal.

## Write-capable roundtrip isolation

Do not add or run a save/reload command until all of these are verified:

1. A fresh accepted-development MariaDB backup exists and is readable.
2. A separately named disposable Compose project uses separate ports, database
   and uploads volumes, and the tooling worktree's theme/plugin code.
3. Its `home` and `siteurl` point only to the disposable endpoint.
4. Its noindex and network boundary are proven.
5. The command refuses the accepted development URL, the canonical live URL,
   and the accepted development Compose project/database.
6. It creates only a unique temporary Draft, records the exact ID, and has
   signal-safe Draft, revision, lock, administrator, and recovery-marker cleanup.

Never save an existing Page, Post, FAQ, Review, Navigation, Pattern, template,
or template part as validation.

## 2026-07-23 audit snapshot

The exhaustive read-only post-editor run discovered 218 durable records: 53
Pages, 11 draft Posts, 154 FAQs, and zero Reviews. All 218 runtime editor records
passed with exact inventory and cleanup proof. The source registry gate found
two separate tracked-source defects: `parts/header.html` and the draft Sample
Page record in `wp2026-page-data.json`. This distinction matters: current
database content is clean, but those tracked sources can reintroduce invalid
serialization later.

The Site Editor inventory contains 18 templates, two template parts, and one
Navigation entity. It also reports a dormant database `footer` template-part row
without the normal theme/area relationships. Treat it as an integrity finding;
do not delete or activate it automatically.

These counts are an audit snapshot, not a future allowlist. Every run must
rediscover the current inventory.

## Handoff rule

Keep tooling changes on `codex/gutenberg-integrity-tooling` until source,
post-editor, Site Editor, isolated roundtrip, responsive, plugin, cleanup, and
repository gates are reviewed. After approval, merge the reviewed commits to
`main`, push, and prove local `main` equals `origin/main`; the side branch must
not remain the only authoritative copy.
