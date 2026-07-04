import crypto from "node:crypto";
import fs from "node:fs";
import path from "node:path";

const root = process.cwd();
const baseUrl = (process.env.WP2026_URL || process.env.CODEX_CLOUD_WP_URL || "http://100.70.222.25:8093").replace(/\/$/, "");
const wpPath = process.env.WP2026_WORDPRESS_DIR || process.env.WP_PATH || "";
const failures = [];

const repoThemeJson = path.join(root, "wp-content/themes/wordpress-2026/theme.json");
const repoStyleCss = path.join(root, "wp-content/themes/wordpress-2026/style.css");
const repoPlugin = path.join(root, "wp-content/plugins/lmhg-site-core/lmhg-site-core.php");
const repoAccessibility = path.join(root, "wp-content/plugins/lmhg-site-core/includes/accessibility.php");
const repoSurfaceControls = path.join(root, "wp-content/plugins/lmhg-site-core/includes/surface-controls.php");
const repoPageClassDesign = path.join(root, "wp-content/plugins/lmhg-site-core/includes/page-class-design.php");
const sourcePolicy = path.join(root, ".wp-gutenberg-designer/content-intake/source-policy.json");

assertFile(repoThemeJson);
assertFile(repoStyleCss);
assertFile(repoPlugin);
assertFile(repoAccessibility);
assertFile(repoSurfaceControls);
assertFile(repoPageClassDesign);
assertFile(sourcePolicy);

const rest = await getJson(`${baseUrl}/wp-json/`);
if (rest.name !== "Louisville Mental Health Group") {
  failures.push(`Unexpected REST site name: ${rest.name || "(missing)"}`);
}

const liveThemeJson = await getText(`${baseUrl}/wp-content/themes/wordpress-2026/theme.json`);
const liveStyleCss = await getText(`${baseUrl}/wp-content/themes/wordpress-2026/style.css`);
const repoThemeHash = sha256(fs.readFileSync(repoThemeJson));
const repoStyleHash = sha256(fs.readFileSync(repoStyleCss));
const liveThemeHash = sha256(liveThemeJson);
const liveStyleHash = sha256(liveStyleCss);
if (repoThemeHash !== liveThemeHash) {
  failures.push(`Live theme.json hash ${liveThemeHash} does not match repo ${repoThemeHash}`);
}
if (repoStyleHash !== liveStyleHash) {
  failures.push(`Live style.css hash ${liveStyleHash} does not match repo ${repoStyleHash}`);
}

const policy = JSON.parse(fs.readFileSync(sourcePolicy, "utf8"));
const approvedIds = new Set((policy.approvedSources || []).map((source) => source.id));
if (!approvedIds.has("lmhg-astro-integrate-core30-and-redirects")) {
  failures.push("source-policy.json has not approved the Astro Core30 and redirects reference intake");
}

const filesystem = [];
if (wpPath) {
  const runtimeThemeJson = path.join(wpPath, "wp-content/themes/wordpress-2026/theme.json");
  const runtimeStyleCss = path.join(wpPath, "wp-content/themes/wordpress-2026/style.css");
  const runtimePlugin = path.join(wpPath, "wp-content/plugins/lmhg-site-core/lmhg-site-core.php");
  const runtimeAccessibility = path.join(wpPath, "wp-content/plugins/lmhg-site-core/includes/accessibility.php");
  const runtimeSurfaceControls = path.join(wpPath, "wp-content/plugins/lmhg-site-core/includes/surface-controls.php");
  const runtimePageClassDesign = path.join(wpPath, "wp-content/plugins/lmhg-site-core/includes/page-class-design.php");

  for (const [label, repoFile, runtimeFile] of [
    ["theme.json", repoThemeJson, runtimeThemeJson],
    ["style.css", repoStyleCss, runtimeStyleCss],
    ["plugin entry", repoPlugin, runtimePlugin],
    ["accessibility include", repoAccessibility, runtimeAccessibility],
    ["surface controls include", repoSurfaceControls, runtimeSurfaceControls],
    ["page-class design include", repoPageClassDesign, runtimePageClassDesign],
  ]) {
    assertFile(runtimeFile);
    const repoHash = sha256(fs.readFileSync(repoFile));
    const runtimeHash = sha256(fs.readFileSync(runtimeFile));
    filesystem.push({ label, repoHash, runtimeHash, match: repoHash === runtimeHash });
    if (repoHash !== runtimeHash) {
      failures.push(`${label} runtime hash ${runtimeHash} does not match repo ${repoHash}`);
    }
  }
}

const result = {
  status: failures.length === 0 ? "ok" : "failed",
  baseUrl,
  rest: {
    name: rest.name,
    home: rest.home,
    url: rest.url,
  },
  http: {
    themeJson: {
      repoHash: repoThemeHash,
      liveHash: liveThemeHash,
      match: repoThemeHash === liveThemeHash,
    },
    styleCss: {
      repoHash: repoStyleHash,
      liveHash: liveStyleHash,
      match: repoStyleHash === liveStyleHash,
    },
  },
  filesystem,
  failures,
};

console.log(JSON.stringify(result, null, 2));
if (failures.length > 0) process.exit(1);

async function getText(url) {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 10000);
  try {
    const response = await fetch(url, { signal: controller.signal });
    const text = await response.text();
    if (!response.ok) {
      throw new Error(`${url} returned ${response.status}: ${text.slice(0, 200)}`);
    }
    return text;
  } finally {
    clearTimeout(timeout);
  }
}

async function getJson(url) {
  return JSON.parse(await getText(url));
}

function assertFile(file) {
  if (!fs.existsSync(file) || !fs.statSync(file).isFile()) {
    failures.push(`Missing required file: ${path.relative(root, file) || file}`);
  }
}

function sha256(content) {
  return crypto.createHash("sha256").update(content).digest("hex");
}
