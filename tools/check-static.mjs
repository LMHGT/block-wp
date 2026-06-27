import fs from "node:fs";
import path from "node:path";

const root = process.cwd();
const requiredFiles = [
  "AGENTS.md",
  ".wp-env.json",
  "blueprints/local-dev/blueprint.json",
  "docs/source-provenance.md",
  "docs/worker-checklist.md",
  "wp-content/themes/lmhg-block-theme/style.css",
  "wp-content/themes/lmhg-block-theme/theme.json",
  "wp-content/themes/lmhg-block-theme/templates/index.html",
  "wp-content/themes/lmhg-block-theme/parts/header.html",
  "wp-content/themes/lmhg-block-theme/parts/footer.html",
  "wp-content/plugins/lmhg-site-core/lmhg-site-core.php",
  ".codex/skills/wordpress-router/SKILL.md",
  ".codex/skills/wp-playground/SKILL.md",
  ".codex/skills/wp-block-themes/SKILL.md"
];
const jsonFiles = [
  "package.json",
  ".wp-env.json",
  "blueprints/local-dev/blueprint.json",
  "wp-content/themes/lmhg-block-theme/theme.json",
  "wp-content/themes/lmhg-block-theme/styles/editorial.json",
  "wp-content/themes/lmhg-block-theme/styles/high-contrast.json"
];
const forbiddenPaths = [
  "wp-content/themes/custom-block-theme",
  "wp-content/plugins/agentic-site-core"
];
let failures = 0;

for (const file of requiredFiles) {
  if (!fs.existsSync(path.join(root, file))) {
    console.error(`missing required file: ${file}`);
    failures += 1;
  }
}

for (const dir of forbiddenPaths) {
  if (fs.existsSync(path.join(root, dir))) {
    console.error(`unexpected generic scaffold path exists: ${dir}`);
    failures += 1;
  }
}

for (const file of jsonFiles) {
  try {
    JSON.parse(fs.readFileSync(path.join(root, file), "utf8"));
  } catch (error) {
    console.error(`invalid JSON in ${file}: ${error.message}`);
    failures += 1;
  }
}

const blueprint = JSON.parse(fs.readFileSync(path.join(root, "blueprints/local-dev/blueprint.json"), "utf8"));
if (blueprint.$schema !== "https://playground.wordpress.net/blueprint-schema.json") {
  console.error("blueprint uses an unexpected schema URL");
  failures += 1;
}

const themeJson = JSON.parse(fs.readFileSync(path.join(root, "wp-content/themes/lmhg-block-theme/theme.json"), "utf8"));
if (themeJson.version !== 3) {
  console.error("theme.json must use version 3");
  failures += 1;
}

const styleCss = fs.readFileSync(path.join(root, "wp-content/themes/lmhg-block-theme/style.css"), "utf8");
if (!styleCss.includes("Theme Name: LMHG Block Theme") || !styleCss.includes("Text Domain: lmhg-block-theme")) {
  console.error("theme header does not use LMHG-owned naming");
  failures += 1;
}

const pluginPhp = fs.readFileSync(path.join(root, "wp-content/plugins/lmhg-site-core/lmhg-site-core.php"), "utf8");
if (!pluginPhp.includes("Plugin Name: LMHG Site Core") || !pluginPhp.includes("Text Domain: lmhg-site-core")) {
  console.error("plugin header does not use LMHG-owned naming");
  failures += 1;
}

if (failures > 0) process.exit(1);
console.log("Static project checks passed.");
