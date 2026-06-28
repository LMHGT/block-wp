import fs from "node:fs";
import path from "node:path";
import { chromium } from "playwright";

const root = process.cwd();
const baseUrl = process.env.CODEX_CLOUD_WP_URL || process.env.WP_BASE_URL || "";
const blockManifest = readJson("data/lmhg/block-migration/full-site-block-manifest.json");
const reportPath = path.join(root, "docs/codex-cloud-runtime-report.md");
const failures = [];

if (!baseUrl) {
  fail("Set CODEX_CLOUD_WP_URL or WP_BASE_URL to the Codex-managed cloud WordPress URL.");
}

if (baseUrl && /racknerd|localhost|127\.0\.0\.1/i.test(baseUrl)) {
  fail(`Cloud runtime URL must not be RackNerd or local: ${baseUrl}`);
}

const routes = Array.isArray(blockManifest.routes) ? blockManifest.routes : [];
if (routes.length < 55) fail(`Expected full-site block manifest, got ${routes.length} routes.`);

const rows = [];
if (failures.length === 0) {
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 1440, height: 1200 } });

  for (const route of routes) {
    const url = new URL(route.url, baseUrl).toString();
    let status = 0;
    let metrics = {};
    try {
      const response = await page.goto(url, { waitUntil: "networkidle", timeout: 45000 });
      status = response?.status() || 0;
      metrics = await page.evaluate(() => {
        const text = (selector) => document.querySelector(selector)?.textContent?.trim() || "";
        const all = (selector) => [...document.querySelectorAll(selector)];
        const paragraphs = all("main p").map((p) => p.innerText.trim()).filter(Boolean);
        return {
          title: document.title,
          h1: text("main h1"),
          noindex: document.querySelector('meta[name="robots"]')?.content || "",
          sourceSections: all(".lmhg-source-section").length,
          sourceCards: all(".lmhg-source-card").length,
          sourceFaqItems: all(".lmhg-source-faq-item").length,
          images: all("main img").length,
          maxParagraphLength: Math.max(0, ...paragraphs.map((paragraph) => paragraph.length)),
          stagingRefs: document.documentElement.innerHTML.includes("staging.website-production-26u.pages.dev"),
          racknerdRefs: /racknerd/i.test(document.documentElement.innerHTML),
        };
      });
    } catch (error) {
      metrics = { error: error.message };
    }

    const expectedH1 = route.h1 || "";
    const issues = [];
    if (status !== 200) issues.push(`status ${status}`);
    if (!metrics.h1) issues.push("missing h1");
    if (expectedH1 && metrics.h1 !== expectedH1) issues.push("h1 mismatch");
    if (!/noindex/i.test(metrics.noindex || "")) issues.push("missing noindex");
    if ((metrics.sourceSections || 0) < 1) issues.push("missing source sections");
    if ((metrics.maxParagraphLength || 0) > 520) issues.push(`oversized paragraph ${metrics.maxParagraphLength}`);
    if (metrics.stagingRefs) issues.push("staging host reference");
    if (metrics.racknerdRefs) issues.push("racknerd reference");

    if (issues.length > 0) {
      fail(`${route.url}: ${issues.join(", ")}`);
    }

    rows.push({
      route: route.url,
      status,
      expectedH1,
      actualH1: metrics.h1 || "",
      sourceSections: metrics.sourceSections || 0,
      cards: metrics.sourceCards || 0,
      faqItems: metrics.sourceFaqItems || 0,
      images: metrics.images || 0,
      maxParagraphLength: metrics.maxParagraphLength || 0,
      issues,
    });
  }

  await browser.close();
}

await fs.promises.writeFile(reportPath, renderReport(), "utf8");

console.log(JSON.stringify({
  baseUrl,
  routes: rows.length,
  routesWithIssues: rows.filter((row) => row.issues.length > 0).length,
  reportPath: path.relative(root, reportPath),
}, null, 2));

if (failures.length > 0) process.exit(1);

function readJson(relativePath) {
  return JSON.parse(fs.readFileSync(path.join(root, relativePath), "utf8"));
}

function fail(message) {
  failures.push(message);
}

function escapeCell(value) {
  return String(value ?? "").replace(/\|/g, "\\|").replace(/\n/g, " ");
}

function renderReport() {
  const table = rows.map((row) => `| ${escapeCell(row.route)} | ${row.status} | ${escapeCell(row.actualH1)} | ${row.sourceSections} | ${row.cards} | ${row.faqItems} | ${row.images} | ${row.maxParagraphLength} | ${escapeCell(row.issues.join("; ") || "ok")} |`).join("\n");
  return `# Codex Cloud WordPress Runtime Report

Date: ${new Date().toISOString()}

WordPress base URL: ${baseUrl || "(not configured)"}

This report is the active runtime proof target for the LMHG WordPress transition.
RackNerd and local WordPress/Docker are not accepted proof surfaces for this
project.

## Summary

- Routes checked: ${rows.length}
- Routes with issues: ${rows.filter((row) => row.issues.length > 0).length}
- Staging controls required: noindex/noarchive must remain active until live use is approved.

## Routes

| Route | Status | H1 | Source sections | Cards | FAQ items | Images | Max paragraph | Issues |
|---|---:|---|---:|---:|---:|---:|---:|---|
${table || "| not run | 0 |  | 0 | 0 | 0 | 0 | 0 | cloud URL missing |"}

## Failures

${failures.length ? failures.map((failure) => `- ${failure}`).join("\n") : "- none"}
`;
}
