import fs from "node:fs";
import { spawnSync } from "node:child_process";
import path from "node:path";

const url = process.env.WP_BASE_URL || "http://localhost:8888";
const outDir = path.join(process.cwd(), "artifacts/lighthouse");
const outFile = path.join(outDir, "report.json");
fs.mkdirSync(outDir, { recursive: true });

let chromePath = process.env.CHROME_PATH || "";
if (!chromePath) {
  try {
    const { chromium } = await import("@playwright/test");
    chromePath = chromium.executablePath();
  } catch {
    chromePath = "";
  }
}

const env = { ...process.env };
if (chromePath) env.CHROME_PATH = chromePath;

const result = spawnSync("npx", [
  "--no-install",
  "lighthouse",
  url,
  "--quiet",
  "--output=json",
  `--output-path=${outFile}`,
  "--only-categories=performance,accessibility,best-practices,seo",
  "--chrome-flags=--headless=new --no-sandbox"
], { encoding: "utf8", env });

if (result.status !== 0) {
  console.error(result.stdout);
  console.error(result.stderr);
  console.error("Lighthouse failed. Ensure the WordPress site is running and Chromium is installed with npm run setup:browsers.");
  process.exit(result.status ?? 1);
}

const report = JSON.parse(fs.readFileSync(outFile, "utf8"));
const scores = Object.fromEntries(
  Object.entries(report.categories).map(([key, category]) => [key, Math.round(category.score * 100)])
);
console.log(`Lighthouse report written to ${outFile}`);
console.log(JSON.stringify(scores, null, 2));

const allowIndexing = ["1", "true"].includes(String(process.env.LMHG_ALLOW_INDEXING || "").toLowerCase());
const expectNoindex = process.env.LH_EXPECT_NOINDEX !== "0" && !allowIndexing;
const minimums = {
  performance: Number(process.env.LH_PERFORMANCE_MIN || 85),
  accessibility: Number(process.env.LH_ACCESSIBILITY_MIN || 95),
  "best-practices": Number(process.env.LH_BEST_PRACTICES_MIN || 90),
  seo: Number(process.env.LH_SEO_MIN || (expectNoindex ? 60 : 95))
};

if (expectNoindex) {
  console.log("Lighthouse SEO threshold is using the development noindex floor. Set LMHG_ALLOW_INDEXING=1 or LH_EXPECT_NOINDEX=0 for production-like SEO gating.");
}

let failed = false;
for (const [category, minimum] of Object.entries(minimums)) {
  if ((scores[category] ?? 0) < minimum) {
    console.error(`${category} score ${scores[category]} is below minimum ${minimum}`);
    failed = true;
  }
}

if (failed) process.exit(1);
