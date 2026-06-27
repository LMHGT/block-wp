import fs from "node:fs";
import path from "node:path";

const root = process.cwd();
const manifestPath = path.join(root, "data/lmhg/source-route-manifest.json");
const baseUrl = process.env.WP_BASE_URL || "http://localhost:8888";
const manifest = JSON.parse(fs.readFileSync(manifestPath, "utf8"));
const failures = [];

function fail(message) {
  failures.push(message);
}

function decodeHtml(value) {
  return String(value || "")
    .replace(/&#(\d+);/g, (_, code) => String.fromCharCode(Number.parseInt(code, 10)))
    .replace(/&amp;/g, "&")
    .replace(/&quot;/g, '"')
    .replace(/&#039;/g, "'")
    .replace(/&lt;/g, "<")
    .replace(/&gt;/g, ">");
}

function normalizePath(value) {
  if (!value || typeof value !== "string") return "";
  const url = value.startsWith("http") ? new URL(value) : new URL(value, baseUrl);
  if (url.pathname === "/") return "/";
  if (path.extname(url.pathname)) return url.pathname;
  return url.pathname.endsWith("/") ? url.pathname : `${url.pathname}/`;
}

function visibleText(html) {
  return decodeHtml(html)
    .replace(/<style[\s\S]*?<\/style>/gi, " ")
    .replace(/<script[\s\S]*?<\/script>/gi, " ")
    .replace(/<[^>]+>/g, " ")
    .replace(/\s+/g, " ")
    .trim();
}

function normalizeText(value) {
  return decodeHtml(value)
    .replace(/\s+/g, " ")
    .trim();
}

function marker(sourceUrl, field) {
  return `page:${sourceUrl}:` + field;
}

function collectCardCount(value) {
  if (!value || typeof value !== "object") return 0;
  let count = 0;
  if (Array.isArray(value.cards)) count += value.cards.filter((card) => card && typeof card === "object").length;
  if (value.services && typeof value.services === "object" && Array.isArray(value.services.cards)) {
    count += value.services.cards.filter((card) => card && typeof card === "object").length;
  }
  return count;
}

const routes = manifest.routes
  .filter((route) => route.migrationStatus !== "out-of-scope")
  .filter((route) => normalizePath(route.url) !== "/404.html")
  .filter((route) => !normalizePath(route.url).startsWith("/review/"));

let checkedRoutes = 0;
let checkedCopyRoutes = 0;
let checkedSnippets = 0;
let checkedSourceCards = 0;
let checkedMarkdownRoutes = 0;
let checkedReadinessRoutes = 0;

for (const route of routes) {
  const sourcePath = normalizePath(route.url);
  const response = await fetch(new URL(sourcePath, baseUrl));
  const html = await response.text();
  checkedRoutes += 1;

  if (response.status !== 200) {
    fail(`${sourcePath} expected HTTP 200, got ${response.status}`);
    continue;
  }

  const text = visibleText(html);
  if (/\{(?:address|neighborhoods|counties)\}/i.test(text)) {
    fail(`${sourcePath} renders unresolved template token`);
  }

  const sourceContent = route.sourceContent && typeof route.sourceContent === "object" ? route.sourceContent : {};
  const snippets = Array.isArray(sourceContent.textSnippets)
    ? sourceContent.textSnippets.map(normalizeText).filter(Boolean)
    : [];

  if (snippets.length === 0) {
    checkedReadinessRoutes += 1;
    if (!html.includes(`data-lmhg-edit-field="${marker(sourcePath, "source-content-readiness")}"`)) {
      fail(`${sourcePath} missing source copy readiness marker`);
    }
    continue;
  }

  checkedCopyRoutes += 1;
  if (!html.includes(`data-lmhg-edit-field="${marker(sourcePath, "source-content")}"`)) {
    fail(`${sourcePath} missing source content section marker`);
  }
  if (!html.includes("data-lmhg-source-content-path=")) {
    fail(`${sourcePath} missing source content path marker`);
  }

  for (const [index, snippet] of snippets.slice(0, 4).entries()) {
    checkedSnippets += 1;
    if (!text.includes(snippet)) {
      fail(`${sourcePath} missing source snippet ${index + 1}: ${snippet.slice(0, 90)}`);
    }
    if (!html.includes(`data-lmhg-edit-field="${marker(sourcePath, `sourceContent.textSnippets[${index}]`)}"`)) {
      fail(`${sourcePath} missing source snippet marker ${index}`);
    }
  }

  const expectedCardCount = collectCardCount(sourceContent.data);
  if (expectedCardCount > 0) {
    const renderedCardCount = [...html.matchAll(/data-lmhg-source-card=/g)].length;
    checkedSourceCards += renderedCardCount;
    if (renderedCardCount !== expectedCardCount) {
      fail(`${sourcePath} expected ${expectedCardCount} source cards, found ${renderedCardCount}`);
    }
  }

  if (sourceContent.type === "markdown") {
    checkedMarkdownRoutes += 1;
    if (!/<h2[^>]*data-lmhg-edit-field="page:[^"]+:sourceContent\.blocks\[\d+\]\.text"/.test(html)) {
      fail(`${sourcePath} missing rendered markdown heading markers`);
    }
  }
}

console.log(JSON.stringify({
  baseUrl,
  checkedRoutes,
  checkedCopyRoutes,
  checkedSnippets,
  checkedSourceCards,
  checkedMarkdownRoutes,
  checkedReadinessRoutes
}, null, 2));

if (failures.length > 0) {
  console.error("LMHG source copy verification failed:");
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log("LMHG source copy verification passed.");
