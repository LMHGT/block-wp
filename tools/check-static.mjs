import fs from "node:fs";
import path from "node:path";

const root = process.cwd();
const requiredFiles = [
  "AGENTS.md",
  ".gitignore",
  "README.md",
  "docs/design.md",
  "docs/source-provenance.md",
  "docs/wordpress-2026-source-of-truth.md",
  "docs/cloud-verification-workflow.md",
  "docs/astro-reference-intake.md",
  "data/lmhg/astro-reference/summary.json",
  "data/lmhg/astro-reference/core30/CORE30_ANALYSIS.md",
  "data/lmhg/astro-reference/core30/CORE30_IMPLEMENTATION.md",
  "data/lmhg/astro-reference/core30/core30-keyword-architecture.md",
  "data/lmhg/astro-reference/core30/core30-keyword-architecture.json",
  "data/lmhg/astro-reference/core30/source-core30.ts.txt",
  "data/lmhg/astro-reference/redirects/public-redirects.txt",
  "data/lmhg/astro-reference/redirects/REDIRECTS.md",
  "data/lmhg/astro-reference/redirects/redirects.json",
  "data/lmhg/astro-reference/redirects/rank-math-redirect-candidates.csv",
  "runtime/wordpress-2026/README.md",
  "runtime/wordpress-2026/package.json",
  "runtime/wordpress-2026/scripts/start-server.mjs",
  "runtime/wordpress-2026/scripts/verify-runtime.mjs",
  "wp-content/themes/wordpress-2026/style.css",
  "wp-content/themes/wordpress-2026/theme.json",
  "wp-content/themes/wordpress-2026/templates/front-page.html",
  "wp-content/themes/wordpress-2026/templates/index.html",
  "wp-content/themes/wordpress-2026/templates/page.html",
  "wp-content/themes/wordpress-2026/parts/header.html",
  "wp-content/themes/wordpress-2026/parts/footer.html",
  "wp-content/themes/wordpress-2026/wp2026-page-data.json",
  "wp-content/plugins/lmhg-site-core/lmhg-site-core.php",
  "wp-content/plugins/lmhg-site-core/includes/editable-blocks.php",
  "wp-content/plugins/lmhg-site-core/includes/importer.php",
  "wp-content/plugins/lmhg-site-core/includes/redirects.php",
  "wp-content/plugins/lmhg-site-core/includes/rendering.php",
  "wp-content/plugins/lmhg-site-core/includes/seo.php",
  "wp-content/plugins/lmhg-site-core/includes/accessibility.php",
  "wp-content/plugins/lmhg-site-core/includes/surface-controls.php",
  "wp-content/plugins/lmhg-site-core/includes/taxonomies.php",
  "wp-content/plugins/lmhg-site-core/includes/reviews.php",
  "wp-content/plugins/lmhg-site-core/includes/content-relationships.php",
  "wp-content/plugins/lmhg-site-core/includes/page-class-design.php",
  "wp-content/plugins/lmhg-site-core/assets/css/reviews.css",
  "wp-content/plugins/lmhg-site-core/assets/css/relationships.css",
  ".wp-gutenberg-designer/project.json",
  ".wp-gutenberg-designer/content-intake/source-policy.json",
  "tools/check-static.mjs",
  "tools/extract-astro-reference-data.mjs",
  "tools/sync-wordpress-2026-runtime.sh",
  "tools/verify-wordpress-2026-source-truth.mjs",
  ".codex/skills/wordpress-router/SKILL.md",
  ".codex/skills/wp-playground/SKILL.md",
  ".codex/skills/wp-block-themes/SKILL.md"
];
const forbiddenPaths = [
  ".wp-env.json",
  "blueprints",
  "plan",
  "tests",
  "deploy",
  "wp-content/themes/lmhg-block-theme",
  "wp-content/themes/custom-block-theme",
  "wp-content/plugins/agentic-site-core",
  "data/lmhg/block-migration",
  "data/lmhg/export",
  "data/lmhg/staging-snapshot",
  "data/lmhg/source-route-manifest.json",
  "data/lmhg/source-design-manifest.json",
  "data/lmhg/source-assets-manifest.json",
  "docs/export-bundle-manifest.md",
  "docs/codex-cloud-runtime-report.md",
  "docs/codex-cloud-bootstrap-report.md",
  "tools/bootstrap-codex-cloud-runtime.mjs",
  "tools/import-codex-cloud-wordpress.sh",
  "tools/generate-full-site-block-migration.mjs",
  "tools/generate-export-manifest.mjs",
  "tools/crawl-staging-snapshot.mjs",
  "tools/verify-codex-cloud-runtime.mjs"
];
let failures = 0;

for (const file of requiredFiles) {
  if (!fs.existsSync(path.join(root, file))) {
    console.error(`missing required file: ${file}`);
    failures += 1;
  }
}

for (const target of forbiddenPaths) {
  if (fs.existsSync(path.join(root, target))) {
    console.error(`legacy package-track path still exists: ${target}`);
    failures += 1;
  }
}

const jsonFiles = [
  "package.json",
  ...listJsonFiles(".wp-gutenberg-designer"),
  ...listJsonFiles("data/lmhg/astro-reference"),
  ...listJsonFiles("runtime/wordpress-2026"),
  ...listJsonFiles("wp-content/themes/wordpress-2026"),
];
for (const file of jsonFiles) {
  try {
    JSON.parse(fs.readFileSync(path.join(root, file), "utf8"));
  } catch (error) {
    console.error(`invalid JSON in ${file}: ${error.message}`);
    failures += 1;
  }
}

const pluginPhp = fs.readFileSync(path.join(root, "wp-content/plugins/lmhg-site-core/lmhg-site-core.php"), "utf8");
if (!pluginPhp.includes("Plugin Name: LMHG Site Core") || !pluginPhp.includes("Text Domain: lmhg-site-core")) {
  console.error("plugin header does not use LMHG-owned naming");
  failures += 1;
}

const wp2026ThemeJson = JSON.parse(fs.readFileSync(path.join(root, "wp-content/themes/wordpress-2026/theme.json"), "utf8"));
if (wp2026ThemeJson.version !== 3) {
  console.error("WordPress 2026 theme.json must use version 3");
  failures += 1;
}

const wp2026StyleCss = fs.readFileSync(path.join(root, "wp-content/themes/wordpress-2026/style.css"), "utf8");
if (!wp2026StyleCss.includes("Theme Name: WordPress 2026 LMHG") || !wp2026StyleCss.includes("Text Domain: wordpress-2026")) {
  console.error("WordPress 2026 theme header does not match the 8093 runtime theme");
  failures += 1;
}

const packageJson = JSON.parse(fs.readFileSync(path.join(root, "package.json"), "utf8"));
const scriptNames = Object.keys(packageJson.scripts || {});
const legacyScriptPattern = /^(cloud|wp-env|playground|inventory|generate|crawl|test|setup:|verify:(lmhg|staging|block|export|wp-vs))/;
for (const scriptName of scriptNames) {
  if (legacyScriptPattern.test(scriptName) || scriptName === "check:prereqs") {
    console.error(`legacy package script still exists: ${scriptName}`);
    failures += 1;
  }
}

const sourcePolicy = JSON.parse(fs.readFileSync(path.join(root, ".wp-gutenberg-designer/content-intake/source-policy.json"), "utf8"));
const approvedSourceIds = new Set((sourcePolicy.approvedSources || []).map((source) => source.id));
if (!approvedSourceIds.has("lmhg-astro-integrate-core30-and-redirects")) {
  console.error("Astro Core30 and redirects reference intake is not approved in source-policy.json");
  failures += 1;
}

const summary = JSON.parse(fs.readFileSync(path.join(root, "data/lmhg/astro-reference/summary.json"), "utf8"));
if (summary.runtimeAuthority?.url !== "http://100.70.222.25:8093") {
  console.error("Astro reference summary does not name the 8093 runtime authority");
  failures += 1;
}
if (summary.core30?.documentVersion !== 3 || summary.core30?.categories < 8) {
  console.error("Core30 summary does not match the extracted Astro reference set");
  failures += 1;
}
if (summary.redirects?.total !== 117 || summary.redirects?.permanent !== 117) {
  console.error("redirect summary does not contain the expected 117 permanent redirect candidates");
  failures += 1;
}

const redirects = JSON.parse(fs.readFileSync(path.join(root, "data/lmhg/astro-reference/redirects/redirects.json"), "utf8"));
if (!Array.isArray(redirects.redirects) || redirects.redirects.length !== 117) {
  console.error("redirects.json does not contain the expected 117 redirect candidates");
  failures += 1;
}

const rankMathCsv = fs.readFileSync(path.join(root, "data/lmhg/astro-reference/redirects/rank-math-redirect-candidates.csv"), "utf8");
if (!rankMathCsv.includes("source,target,status_code,match_type,destination_type")) {
  console.error("Rank Math redirect candidate CSV header is missing or unexpected");
  failures += 1;
}

if (failures > 0) process.exit(1);
console.log("Static project checks passed.");

function listJsonFiles(relativeDir) {
  const start = path.join(root, relativeDir);
  if (!fs.existsSync(start)) return [];

  const files = [];
  for (const entry of fs.readdirSync(start, { withFileTypes: true })) {
    const relativePath = path.join(relativeDir, entry.name);
    const absolutePath = path.join(root, relativePath);
    if (entry.isDirectory()) {
      files.push(...listJsonFiles(relativePath));
    } else if (entry.isFile() && entry.name.endsWith(".json")) {
      files.push(relativePath);
    }
  }
  return files;
}
