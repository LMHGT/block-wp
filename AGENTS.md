# LMHG Block WordPress Working Contract

## Source and runtime authority

- `main` is the definitive source for current code and working rules. Do not
  create or preserve another long-lived implementation branch unless the user
  explicitly requests one.
- Existing reference branches are historical evidence, not current authority;
  reconcile useful rules into `main` instead of reviving those branches.
- The development site is `http://100.116.130.39:8093/` and runs from
  `/srv/codex/services/lmhg-blockwp-wordpress-mariadb` with WordPress 7.0.2,
  PHP 8.3, and MariaDB 10.11 in Docker Compose.
- Development-site publication is allowed after proportional validation.
  Production is a separate environment with separate release rules.
- Never commit credentials, runtime secrets, database files, backups, or
  licensed third-party plugin files.

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

## Required closeout evidence

- Run PHP syntax checks for changed plugin files and `git diff --check`.
- Validate responsive H1 behavior and full text containment across all
  published pages and supported viewports.
- Re-run Gutenberg editor recovery checks after changes that affect block
  serialization or rendered editor content.
- Commit and push verified development-site work to `main`.
