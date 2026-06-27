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

function normalizePath(value) {
  if (!value || typeof value !== "string") return "";
  const url = value.startsWith("http") ? new URL(value) : new URL(value, baseUrl);
  if (url.pathname === "/") return "/";
  if (path.extname(url.pathname)) return url.pathname;
  return url.pathname.endsWith("/") ? url.pathname : `${url.pathname}/`;
}

function marker(sourceUrl, field) {
  return `page:${sourceUrl}:` + field;
}

function escapeRegExp(value) {
  return value.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}

function countMatches(html, pattern) {
  return [...html.matchAll(pattern)].length;
}

function visibleText(html) {
  return html
    .replace(/<style[\s\S]*?<\/style>/gi, " ")
    .replace(/<script[\s\S]*?<\/script>/gi, " ")
    .replace(/<[^>]+>/g, " ")
    .replace(/\s+/g, " ")
    .trim();
}

const routes = manifest.routes
  .filter((route) => route.migrationStatus !== "out-of-scope")
  .filter((route) => normalizePath(route.url) !== "/404.html")
  .filter((route) => !normalizePath(route.url).startsWith("/review/"));

let checkedRoutes = 0;
let checkedSummaryMarkers = 0;
let checkedBreadcrumbs = 0;
let checkedRelatedSections = 0;
let checkedFaqReadiness = 0;

for (const route of routes) {
  const sourceUrl = normalizePath(route.url);
  const response = await fetch(new URL(sourceUrl, baseUrl));
  const html = await response.text();
  checkedRoutes += 1;

  if (response.status !== 200) {
    fail(`${sourceUrl} expected HTTP 200, got ${response.status}`);
    continue;
  }

  const text = visibleText(html);
  if (/Migration stub/i.test(text)) fail(`${sourceUrl} still renders migration stub copy`);
  if (/\[[^\]]+\]/.test(text)) fail(`${sourceUrl} renders bracketed workbook prompt text`);

  const seo = route.seo && typeof route.seo === "object" ? route.seo : {};
  const hasSummarySource = Boolean(seo.description || (Array.isArray(seo.optimizationTerms) && seo.optimizationTerms.length > 0));
  if (hasSummarySource) {
    checkedSummaryMarkers += 1;
    if (!html.includes(`data-lmhg-edit-field="${marker(sourceUrl, "summary")}"`)) {
      fail(`${sourceUrl} missing summary edit marker`);
    }
  }

  if (sourceUrl !== "/") {
    checkedBreadcrumbs += 1;
    if (!html.includes(`data-lmhg-edit-field="${marker(sourceUrl, "breadcrumbs")}"`)) {
      fail(`${sourceUrl} missing breadcrumb marker`);
    }
    const parentUrl = route.relationship?.primaryParentPageUrl || "";
    if (parentUrl && parentUrl !== "/" && parentUrl !== sourceUrl && !html.includes(`data-lmhg-graph-url="${parentUrl}"`)) {
      fail(`${sourceUrl} missing breadcrumb parent ${parentUrl}`);
    }
  }

  const related = Array.isArray(route.relatedPages)
    ? route.relatedPages.filter((item) => item && !item.avoidLink && item.targetPageUrl)
    : [];
  if (related.length > 0) {
    checkedRelatedSections += 1;
    if (!html.includes(`data-lmhg-edit-field="${marker(sourceUrl, "related-pages")}"`)) {
      fail(`${sourceUrl} missing related-pages marker`);
    }
    const renderedRelatedCount = countMatches(html, /data-lmhg-related-page=/g);
    if (renderedRelatedCount !== related.length) {
      fail(`${sourceUrl} expected ${related.length} related links, found ${renderedRelatedCount}`);
    }
  } else if (!html.includes(`data-lmhg-edit-field="${marker(sourceUrl, "related-pages-readiness")}"`)) {
    fail(`${sourceUrl} missing related-pages readiness marker`);
  }

  const faqItems = Array.isArray(route.faqItems) ? route.faqItems : [];
  if (faqItems.length > 0) {
    checkedFaqReadiness += 1;
    if (!html.includes(`data-lmhg-edit-field="${marker(sourceUrl, "faq-readiness")}"`)) {
      fail(`${sourceUrl} missing FAQ readiness marker`);
    }
    if (!new RegExp(`data-lmhg-faq-count="${escapeRegExp(String(faqItems.length))}"`).test(html)) {
      fail(`${sourceUrl} missing FAQ readiness count ${faqItems.length}`);
    }
  }
}

console.log(JSON.stringify({
  baseUrl,
  checkedRoutes,
  checkedSummaryMarkers,
  checkedBreadcrumbs,
  checkedRelatedSections,
  checkedFaqReadiness
}, null, 2));

if (failures.length > 0) {
  console.error("LMHG marker verification failed:");
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log("LMHG marker verification passed.");
