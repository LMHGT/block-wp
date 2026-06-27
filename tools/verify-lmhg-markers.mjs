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

function cleanFaqText(value) {
  let text = String(value || "")
    .replace(/<[^>]+>/g, " ")
    .replace(/\s*---\s*$/g, "")
    .replace(/`+\s*$/g, "")
    .replace(/\s+/g, " ")
    .trim();
  if (!text || text === "[...]" || text.includes("[...]") || /^\[[^\]]+\]$/.test(text)) return "";
  return text;
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

function firstH1Text(html) {
  const match = html.match(/<h1[^>]*>([\s\S]*?)<\/h1>/i);
  return match ? decodeHtml(visibleText(match[1])) : "";
}

const routes = manifest.routes
  .filter((route) => route.migrationStatus !== "out-of-scope")
  .filter((route) => normalizePath(route.url) !== "/404.html")
  .filter((route) => !normalizePath(route.url).startsWith("/review/"));

let checkedRoutes = 0;
let checkedSummaryMarkers = 0;
let checkedH1Values = 0;
let checkedBreadcrumbs = 0;
let checkedRelatedSections = 0;
let checkedFaqSections = 0;
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
  if (/LMHG Block WP|WordPress proof track|Source parity is still being verified|Built for controlled parity/i.test(text)) {
    fail(`${sourceUrl} renders scaffold/proof-track copy`);
  }
  if (/\[[^\]]+\]/.test(text)) fail(`${sourceUrl} renders bracketed workbook prompt text`);

  const seo = route.seo && typeof route.seo === "object" ? route.seo : {};
  if (seo.h1) {
    checkedH1Values += 1;
    const h1 = firstH1Text(html);
    if (h1 !== seo.h1) fail(`${sourceUrl} H1 expected "${seo.h1}", got "${h1 || "(missing)"}"`);
  }

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
  const publishableFaqItems = faqItems.filter((item) => cleanFaqText(item?.question) && cleanFaqText(item?.answer));
  if (publishableFaqItems.length > 0) {
    checkedFaqSections += 1;
    if (!html.includes(`data-lmhg-edit-field="${marker(sourceUrl, "faq")}"`)) {
      fail(`${sourceUrl} missing FAQ section marker`);
    }
    const renderedFaqCount = countMatches(html, /data-lmhg-faq-question=/g);
    if (renderedFaqCount !== publishableFaqItems.length) {
      fail(`${sourceUrl} expected ${publishableFaqItems.length} rendered FAQs, found ${renderedFaqCount}`);
    }
  }

  const incompleteFaqCount = faqItems.length - publishableFaqItems.length;
  if (incompleteFaqCount > 0) {
    checkedFaqReadiness += 1;
    if (!html.includes(`data-lmhg-edit-field="${marker(sourceUrl, "faq-readiness")}"`)) {
      fail(`${sourceUrl} missing FAQ readiness marker`);
    }
    if (!new RegExp(`data-lmhg-faq-count="${escapeRegExp(String(incompleteFaqCount))}"`).test(html)) {
      fail(`${sourceUrl} missing FAQ readiness count ${incompleteFaqCount}`);
    }
  }
}

console.log(JSON.stringify({
  baseUrl,
  checkedRoutes,
  checkedSummaryMarkers,
  checkedH1Values,
  checkedBreadcrumbs,
  checkedRelatedSections,
  checkedFaqSections,
  checkedFaqReadiness
}, null, 2));

if (failures.length > 0) {
  console.error("LMHG marker verification failed:");
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log("LMHG marker verification passed.");
