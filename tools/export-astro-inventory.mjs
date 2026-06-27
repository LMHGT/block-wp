import fs from "node:fs";
import path from "node:path";
import { spawnSync } from "node:child_process";

const repoRoot = process.cwd();
const sourceRoot = process.env.ASTRO_SOURCE_ROOT || "/Users/tyler-lcsw/projects/lmhg-astro-integrate";
const outDir = path.join(repoRoot, "data/lmhg");

function readText(file) {
  return fs.readFileSync(path.join(sourceRoot, file), "utf8");
}

function readJson(file) {
  return JSON.parse(readText(file));
}

function exists(file) {
  return fs.existsSync(path.join(sourceRoot, file));
}

function walk(dir, predicate = () => true) {
  const full = path.join(sourceRoot, dir);
  if (!fs.existsSync(full)) return [];
  const results = [];
  const stack = [full];
  while (stack.length > 0) {
    const current = stack.pop();
    for (const entry of fs.readdirSync(current, { withFileTypes: true })) {
      const entryPath = path.join(current, entry.name);
      if (entry.isDirectory()) {
        stack.push(entryPath);
      } else if (entry.isFile()) {
        const rel = path.relative(sourceRoot, entryPath);
        if (predicate(rel)) results.push(rel);
      }
    }
  }
  return results.sort();
}

function git(args) {
  const result = spawnSync("git", ["-C", sourceRoot, ...args], { encoding: "utf8" });
  return result.status === 0 ? result.stdout.trim() : "";
}

function normalizeUrl(value) {
  if (!value) return "";
  if (value.includes("[")) return "";
  if (value === "/none/" || value === "none" || value === "/none") return "";
  if (value === "/") return "/";
  const clean = value.startsWith("/") ? value : `/${value}`;
  return clean.endsWith("/") || path.extname(clean) ? clean : `${clean}/`;
}

function cleanPlaceholder(value) {
  if (!value || typeof value !== "string") return value || "";
  const trimmed = value.trim();
  if (/^\[.*\]$/.test(trimmed)) return "";
  if (trimmed === "none" || trimmed === "/none/" || trimmed === "/none") return "";
  return trimmed;
}

function pageFileToRoute(file) {
  let rel = file.replace(/^src\/pages\//, "").replace(/\.(astro|md|mdx)$/, "");
  if (rel === "404") return "/404.html";
  if (rel === "index") return "/";
  if (rel.endsWith("/index")) rel = rel.slice(0, -"/index".length);
  if (rel.includes("[")) return null;
  return normalizeUrl(`/${rel}`);
}

function parseRedirects(text) {
  const redirects = [];
  let section = "Uncategorized";
  for (const [index, rawLine] of text.split(/\r?\n/).entries()) {
    const line = rawLine.trim();
    if (!line) continue;
    if (line.startsWith("#")) {
      const label = line.replace(/^#+\s*/, "").trim();
      if (label && !/^=+$/.test(label)) section = label;
      continue;
    }
    const [from, to, status = ""] = line.split(/\s+/);
    if (!from || !to) continue;
    redirects.push({
      source: from,
      target: to,
      statusCode: status || null,
      section,
      lineNumber: index + 1,
      migrationStatus: "redirect-only"
    });
  }
  return redirects;
}

function migrationStatusForPage(page) {
  if (page.status && page.status !== "current") return "out-of-scope";
  if (page.url?.startsWith("/review/")) return "out-of-scope";
  if (page.protected) return "out-of-scope";
  if (page.implementationTarget) return "needs-copy-model";
  if (page.templateFamily) return "needs-template";
  return "ready";
}

function extractCssVariables(text) {
  const matches = [...text.matchAll(/(--[a-zA-Z0-9_-]+)\s*:\s*([^;]+);/g)];
  return matches.map((match) => ({
    name: match[1],
    value: match[2].trim()
  }));
}

function extractColors(text) {
  const values = new Set();
  for (const match of text.matchAll(/#[0-9a-fA-F]{3,8}\b|oklch\([^)]+\)/g)) {
    values.add(match[0]);
  }
  return [...values].sort();
}

function assetKind(file) {
  if (file.startsWith("public/brand/")) return "brand";
  if (file.includes("/service-categories/")) return "service-category";
  if (file.includes("/specialties/")) return "specialty";
  if (file.includes("/service-areas/")) return "service-area";
  return "illustration";
}

function summarizeAsset(file) {
  const stat = fs.statSync(path.join(sourceRoot, file));
  return {
    path: file,
    kind: assetKind(file),
    extension: path.extname(file).replace(".", ""),
    bytes: stat.size,
    migrationStatus: "needs-copy-model"
  };
}

function main() {
  if (!fs.existsSync(sourceRoot)) {
    throw new Error(`Astro source root does not exist: ${sourceRoot}`);
  }
  fs.mkdirSync(outDir, { recursive: true });

  const dataset = readJson("var/nocobase-sync/dataset.json");
  const pages = dataset.pages || [];
  const seoByUrl = new Map((dataset.seoFields || []).map((entry) => [normalizeUrl(entry.pageUrl), entry]));
  const relationshipBriefByUrl = new Map((dataset.pageRelationshipBriefs || []).map((entry) => [normalizeUrl(entry.pageUrl), entry]));
  const relatedByUrl = new Map();
  for (const entry of dataset.relatedPages || []) {
    const key = normalizeUrl(entry.sourcePageUrl);
    if (!relatedByUrl.has(key)) relatedByUrl.set(key, []);
    relatedByUrl.get(key).push({
      targetPageUrl: normalizeUrl(entry.targetPageUrl),
      relationshipBucket: entry.relationshipBucket,
      label: entry.label || "",
      sortOrder: entry.sortOrder,
      avoidLink: Boolean(entry.avoidLink),
      source: entry.source || "",
      truthSource: entry.truthSource || ""
    });
  }
  const faqByUrl = new Map();
  for (const entry of dataset.pageFaqItems || []) {
    const key = normalizeUrl(entry.pageUrl);
    if (!faqByUrl.has(key)) faqByUrl.set(key, []);
    faqByUrl.get(key).push({
      question: entry.question,
      schemaEligible: Boolean(entry.schemaEligible),
      optional: Boolean(entry.optional),
      sortOrder: entry.sortOrder,
      status: entry.status,
      sourceFile: entry.sourceFile || ""
    });
  }

  const pageFiles = walk("src/pages", (file) => /\.(astro|md|mdx)$/.test(file));
  const staticRoutes = pageFiles
    .map((file) => ({ file, url: pageFileToRoute(file), dynamic: file.includes("[") }))
    .map((entry) => ({
      ...entry,
      url: entry.url || null,
      generatedByDynamicRoute: entry.dynamic
    }));
  const staticRouteByUrl = new Map(staticRoutes.filter((entry) => entry.url).map((entry) => [entry.url, entry.file]));
  const redirectRules = parseRedirects(readText("public/_redirects"));

  const routeEntries = pages.map((page) => {
    const url = normalizeUrl(page.url);
    const seo = seoByUrl.get(url) || null;
    const relationshipBrief = relationshipBriefByUrl.get(url) || null;
    return {
      url,
      title: page.title,
      pageFamily: page.pageFamily,
      templateFamily: page.templateFamily,
      facetedPageType: cleanPlaceholder(page.facetedPageType || seo?.pageType || relationshipBrief?.pageType),
      status: page.status,
      protected: Boolean(page.protected),
      sourceFile: page.sourceFile || "",
      implementationTarget: page.implementationTarget || "",
      routeFile: staticRouteByUrl.get(url) || "",
      dynamicRouteFile: staticRouteByUrl.has(url) ? "" : (url.startsWith("/articles/") ? "src/pages/articles/[slug].astro" : "src/pages/[service].astro"),
      seo: seo ? {
        title: cleanPlaceholder(seo.title),
        description: cleanPlaceholder(seo.description),
        h1: cleanPlaceholder(seo.h1),
        canonicalUrl: normalizeUrl(seo.canonicalUrl || url),
        primaryKeyword: cleanPlaceholder(seo.primaryKeyword),
        secondaryKeywords: seo.secondaryKeywords || [],
        optimizationTerms: seo.optimizationTerms || [],
        schemaType: cleanPlaceholder(seo.schemaType || relationshipBrief?.recommendedSchemaType),
        noindex: Boolean(seo.noindex),
        status: cleanPlaceholder(seo.status)
      } : null,
      relationship: relationshipBrief ? {
        primaryParentPageUrl: normalizeUrl(relationshipBrief.primaryParentPageUrl || ""),
        specialtyKind: cleanPlaceholder(relationshipBrief.specialtyKind),
        recommendedSchemaType: cleanPlaceholder(relationshipBrief.recommendedSchemaType),
        sourceFile: relationshipBrief.sourceFile || ""
      } : null,
      relatedPages: (relatedByUrl.get(url) || []).sort((a, b) => (a.sortOrder || 0) - (b.sortOrder || 0)),
      faqItems: (faqByUrl.get(url) || []).sort((a, b) => (a.sortOrder || 0) - (b.sortOrder || 0)),
      migrationStatus: migrationStatusForPage(page)
    };
  });

  const datasetUrls = new Set(routeEntries.map((entry) => entry.url));
  const sourceOnlyRoutes = staticRoutes
    .filter((entry) => entry.url && !datasetUrls.has(entry.url))
    .map((entry) => ({
      url: entry.url,
      routeFile: entry.file,
      dynamic: entry.generatedByDynamicRoute,
      migrationStatus: entry.url.startsWith("/review/") ? "out-of-scope" : "needs-template"
    }));

  const routeManifest = {
    generatedAt: new Date().toISOString(),
    sourceRoot,
    sourceBranch: git(["rev-parse", "--abbrev-ref", "HEAD"]),
    sourceHead: git(["rev-parse", "HEAD"]),
    sourceOriginStaging: git(["rev-parse", "origin/staging"]),
    dataset: {
      generatedAt: dataset.generatedAt || "",
      version: dataset.version,
      pageCount: pages.length,
      relatedPageCount: (dataset.relatedPages || []).length,
      faqItemCount: (dataset.pageFaqItems || []).length,
      teamCount: (dataset.team || []).length,
      articleCount: (dataset.articles || []).length
    },
    routes: routeEntries.sort((a, b) => a.url.localeCompare(b.url)),
    sourceOnlyRoutes,
    dynamicRoutes: staticRoutes.filter((entry) => entry.generatedByDynamicRoute),
    redirects: redirectRules,
    migrationStatusCounts: routeEntries.reduce((acc, entry) => {
      acc[entry.migrationStatus] = (acc[entry.migrationStatus] || 0) + 1;
      return acc;
    }, {})
  };

  const designFiles = [
    "DESIGN.md",
    "brand.md",
    "docs/seo/core30-keyword-architecture.md",
    "src/styles/global.css"
  ].filter(exists);
  const designManifest = {
    generatedAt: routeManifest.generatedAt,
    sourceRoot,
    sourceHead: routeManifest.sourceHead,
    files: designFiles.map((file) => {
      const text = readText(file);
      return {
        path: file,
        bytes: Buffer.byteLength(text),
        colors: extractColors(text),
        cssVariables: file.endsWith(".css") ? extractCssVariables(text) : [],
        migrationStatus: "needs-template"
      };
    }),
    guardrails: {
      visualPosture: "light, warm, structured, text-first, calm, practical",
      noPageBuilder: true,
      noGenericStockPhotos: true,
      crawlableCoreContent: true,
      graphBackedBreadcrumbsAndRelatedLinks: true,
      workbenchMarkersRequiredForVisibleEditableFields: true
    }
  };

  const assetFiles = walk("public/brand", () => true).concat(walk("public/illustrations", () => true)).sort();
  const assetManifest = {
    generatedAt: routeManifest.generatedAt,
    sourceRoot,
    sourceHead: routeManifest.sourceHead,
    assets: assetFiles.map(summarizeAsset),
    countsByKind: assetFiles.map(assetKind).reduce((acc, kind) => {
      acc[kind] = (acc[kind] || 0) + 1;
      return acc;
    }, {})
  };

  fs.writeFileSync(path.join(outDir, "source-route-manifest.json"), `${JSON.stringify(routeManifest, null, 2)}\n`);
  fs.writeFileSync(path.join(outDir, "source-design-manifest.json"), `${JSON.stringify(designManifest, null, 2)}\n`);
  fs.writeFileSync(path.join(outDir, "source-assets-manifest.json"), `${JSON.stringify(assetManifest, null, 2)}\n`);

  console.log(`Wrote ${routeManifest.routes.length} route entries and ${routeManifest.redirects.length} redirects.`);
  console.log(`Wrote ${designManifest.files.length} design source summaries.`);
  console.log(`Wrote ${assetManifest.assets.length} asset entries.`);
}

main();
