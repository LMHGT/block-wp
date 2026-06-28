import fs from "node:fs";
import crypto from "node:crypto";
import path from "node:path";
import { chromium } from "playwright";

const root = process.cwd();
const snapshotPath = path.join(root, "data/lmhg/staging-snapshot/routes.json");
const snapshotSummaryPath = path.join(root, "data/lmhg/staging-snapshot/summary.json");
const reportPath = path.join(root, "docs/wp-vs-staging-gap-report.md");
const wpBaseUrl = process.env.WP_BASE_URL || "http://localhost:8888";
const reportOnly = process.argv.includes("--report-only");
const routes = JSON.parse(fs.readFileSync(snapshotPath, "utf8"));
const snapshotSummary = JSON.parse(fs.readFileSync(snapshotSummaryPath, "utf8"));
const failures = [];
const rows = [];

function decodeHtml(value) {
  return String(value || "")
    .replace(/&#(\d+);/g, (_, code) => String.fromCharCode(Number.parseInt(code, 10)))
    .replace(/&#x([0-9a-f]+);/gi, (_, code) => String.fromCharCode(Number.parseInt(code, 16)))
    .replace(/&amp;/g, "&")
    .replace(/&quot;/g, '"')
    .replace(/&#039;/g, "'")
    .replace(/&apos;/g, "'")
    .replace(/&lt;/g, "<")
    .replace(/&gt;/g, " ");
}

function cleanVisibleText(value) {
  return String(value || "").replace(/\s+/g, " ").trim();
}

function sha256(value) {
  return crypto.createHash("sha256").update(value).digest("hex");
}

function matchFirst(html, pattern) {
  const match = html.match(pattern);
  return match ? decodeHtml(match[1]).replace(/\s+/g, " ").trim() : "";
}

function hasNoindex(value) {
  return /noindex/i.test(String(value || ""));
}

function extractAssets(html, baseUrl) {
  const assets = new Set();
  for (const match of html.matchAll(/(?:src|href|poster)\s*=\s*(["'])(.*?)\1/gi)) {
    const raw = decodeHtml(match[2]).trim();
    if (!raw || raw.startsWith("#") || raw.startsWith("data:") || /^(mailto|tel|sms|javascript):/i.test(raw)) continue;
    let url;
    try {
      url = new URL(raw, baseUrl);
    } catch {
      continue;
    }
    if (/\.(css|js|mjs|png|jpe?g|webp|svg|avif|ico|woff2?|ttf|otf)(\?|$)/i.test(url.pathname + url.search)) {
      assets.add(url.toString());
    }
  }
  return [...assets].sort();
}

function countBy(items, key) {
  const counts = {};
  for (const item of items) {
    const value = typeof key === "function" ? key(item) : item[key];
    counts[value] = (counts[value] || 0) + 1;
  }
  return counts;
}

function markdownTable(tableRows, columns) {
  const header = `| ${columns.map((column) => column.label).join(" | ")} |`;
  const separator = `| ${columns.map(() => "---").join(" | ")} |`;
  const body = tableRows.map((row) => `| ${columns.map((column) => escapeCell(column.value(row))).join(" | ")} |`);
  return [header, separator, ...body].join("\n");
}

function escapeCell(value) {
  return String(value ?? "").replace(/\|/g, "\\|").replace(/\n/g, " ").trim();
}

function recordFailure(route, issue) {
  route.issues.push(issue);
  failures.push(`${route.url}: ${issue}`);
}

const comparableRoutes = routes.filter((route) => route.liveStatus === 200);
const browser = await chromium.launch();
const context = await browser.newContext({ viewport: { width: 1440, height: 1200 } });
const page = await context.newPage();

try {
  for (const stagingRoute of comparableRoutes) {
    const row = {
      url: stagingRoute.url,
      stagingClassification: stagingRoute.classification,
      stagingStatus: stagingRoute.liveStatus,
      wpStatus: 0,
      issues: [],
      stagingTitle: stagingRoute.title || "",
      wpTitle: "",
      stagingH1: stagingRoute.h1 || "",
      wpH1: "",
      stagingAssets: stagingRoute.assetCount || 0,
      wpAssets: 0,
      stagingHostReferences: 0
    };

    try {
      const response = await page.goto(new URL(stagingRoute.url, wpBaseUrl).toString(), { waitUntil: "networkidle", timeout: 30000 });
      if (!response) throw new Error(`no browser response for ${stagingRoute.url}`);

      const html = await response.text();
      const headers = response.headers();
      row.wpStatus = response.status();
      row.wpTitle = matchFirst(html, /<title>([\s\S]*?)<\/title>/i);
      row.wpH1 = matchFirst(html, /<h1[^>]*>([\s\S]*?)<\/h1>/i);
      row.wpAssets = extractAssets(html, wpBaseUrl).length;
      row.stagingHostReferences = (html.match(/staging\.website-production-26u\.pages\.dev/g) || []).length;

      if (row.wpStatus !== stagingRoute.liveStatus) {
        recordFailure(row, `status ${row.wpStatus} != staging ${stagingRoute.liveStatus}`);
      }
      if (row.wpTitle !== row.stagingTitle) {
        recordFailure(row, "title mismatch");
      }
      if (row.wpH1 !== row.stagingH1) {
        recordFailure(row, "h1 mismatch");
      }

      const renderedText = cleanVisibleText(await page.evaluate(() => document.body?.innerText || ""));
      const textHash = sha256(renderedText);
      if (textHash !== stagingRoute.visibleTextHash) {
        recordFailure(row, "visible text hash mismatch");
      }

      const xRobots = headers["x-robots-tag"] || "";
      const robotsMeta = matchFirst(html, /<meta[^>]+name=["']robots["'][^>]+content=["']([^"']+)["'][^>]*>/i);
      if (!hasNoindex(xRobots)) recordFailure(row, "missing staging X-Robots-Tag noindex");
      if (!hasNoindex(robotsMeta)) recordFailure(row, "missing staging robots meta noindex");
      if (row.stagingHostReferences > 0) recordFailure(row, "contains staging host reference");
    } catch (error) {
      recordFailure(row, `fetch failed: ${error.message}`);
    }

    rows.push(row);
  }
} finally {
  await context.close();
  await browser.close();
}

const issueCounts = countBy(
  rows.flatMap((row) => row.issues),
  (issue) => issue
);
const routesWithIssues = rows.filter((row) => row.issues.length > 0);

fs.writeFileSync(reportPath, `# WordPress vs Cloudflare Staging Gap Report

Snapshot date: ${snapshotSummary.generatedAt}

WordPress base URL: ${wpBaseUrl}

Staging snapshot: \`data/lmhg/staging-snapshot/routes.json\`

This report intentionally compares the current WordPress proof surface against
the verbatim Cloudflare staging snapshot. It fails when route status, titles,
H1s, browser-visible text, staging host references, or noindex controls drift.

## Summary

- Comparable staging routes: ${rows.length}
- Routes with issues: ${routesWithIssues.length}
- Issue counts: \`${JSON.stringify(issueCounts)}\`

## Route Gaps

${markdownTable(rows, [
  { label: "Route", value: (row) => row.url },
  { label: "WP Status", value: (row) => row.wpStatus },
  { label: "Classification", value: (row) => row.stagingClassification },
  { label: "Staging H1", value: (row) => row.stagingH1 },
  { label: "WP H1", value: (row) => row.wpH1 },
  { label: "Staging Assets", value: (row) => row.stagingAssets },
  { label: "WP Assets", value: (row) => row.wpAssets },
  { label: "Issues", value: (row) => row.issues.join("; ") || "none" }
])}
`);

console.log(JSON.stringify({
  wpBaseUrl,
  comparableRoutes: rows.length,
  routesWithIssues: routesWithIssues.length,
  issueCounts
}, null, 2));

if (failures.length > 0 && !reportOnly) {
  console.error("WordPress vs staging verification failed:");
  for (const failure of failures.slice(0, 80)) console.error(`- ${failure}`);
  if (failures.length > 80) console.error(`- ... ${failures.length - 80} more`);
  process.exit(1);
}

if (failures.length > 0) {
  console.log("WordPress vs staging gap report written.");
} else {
  console.log("WordPress vs staging verification passed.");
}
