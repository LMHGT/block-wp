import crypto from "node:crypto";
import fs from "node:fs";
import path from "node:path";

const root = process.cwd();
const manifestPath = path.join(root, "data/lmhg/source-route-manifest.json");
const dataDir = path.join(root, "data/lmhg/staging-snapshot");
const artifactDir = path.join(root, "artifacts/staging-snapshot");
const htmlDir = path.join(artifactDir, "html");
const assetDir = path.join(artifactDir, "assets");
const screenshotDir = path.join(artifactDir, "screenshots");
const docsSnapshotPath = path.join(root, "docs/staging-snapshot-report.md");
const docsParityPath = path.join(root, "docs/route-parity-matrix.md");
const stagingBaseUrl = process.env.STAGING_BASE_URL || "https://staging.website-production-26u.pages.dev";
const screenshotMode = process.env.STAGING_CRAWL_SCREENSHOTS !== "0";
const maxDiscoveredRoutes = Number.parseInt(process.env.STAGING_CRAWL_MAX_DISCOVERED || "200", 10);
const viewports = [
  { name: "desktop", width: 1440, height: 1000 },
  { name: "mobile", width: 390, height: 844 }
];

const stagingOrigin = new URL(stagingBaseUrl).origin;
const manifest = JSON.parse(fs.readFileSync(manifestPath, "utf8"));
const manifestRoutes = Array.isArray(manifest.routes) ? manifest.routes : [];
const manifestRedirects = Array.isArray(manifest.redirects) ? manifest.redirects : [];
const routeByPath = new Map(manifestRoutes.map((route) => [normalizeRoutePath(route.url), route]));
const discoveredPaths = new Set();
const routes = [];
const redirects = [];
const assetMap = new Map();
const failures = [];

function ensureDirs() {
  for (const dir of [dataDir, artifactDir, htmlDir, assetDir, screenshotDir]) {
    fs.mkdirSync(dir, { recursive: true });
  }
}

function sha256(value) {
  return crypto.createHash("sha256").update(value).digest("hex");
}

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

function normalizeRoutePath(value) {
  if (!value || typeof value !== "string") return "";
  const url = value.startsWith("http") ? new URL(value) : new URL(value, stagingBaseUrl);
  if (url.pathname === "/") return "/";
  if (path.extname(url.pathname)) return url.pathname;
  return url.pathname.endsWith("/") ? url.pathname : `${url.pathname}/`;
}

function redirectSourcePath(value) {
  if (!value || typeof value !== "string") return "";
  const url = value.startsWith("http") ? new URL(value) : new URL(value, stagingBaseUrl);
  return url.pathname || "/";
}

function normalizeAssetUrl(value, pagePath = "/") {
  if (!value || typeof value !== "string") return "";
  const clean = decodeHtml(value).trim();
  if (
    !clean ||
    clean.startsWith("data:") ||
    clean.startsWith("blob:") ||
    clean.startsWith("#") ||
    /^(mailto|tel|sms|javascript):/i.test(clean)
  ) {
    return "";
  }

  let url;
  try {
    url = new URL(clean, new URL(pagePath, stagingBaseUrl));
  } catch {
    return "";
  }

  if (url.origin !== stagingOrigin) return "";
  if (!/\.(css|js|mjs|png|jpe?g|webp|svg|avif|ico|woff2?|ttf|otf)(\?|$)/i.test(url.pathname + url.search)) return "";
  url.hash = "";
  return url.toString();
}

function routePathFromHref(value, pagePath = "/") {
  if (!value || typeof value !== "string") return "";
  const clean = decodeHtml(value).trim();
  if (
    !clean ||
    clean.startsWith("#") ||
    /^(mailto|tel|sms|javascript):/i.test(clean)
  ) {
    return "";
  }

  let url;
  try {
    url = new URL(clean, new URL(pagePath, stagingBaseUrl));
  } catch {
    return "";
  }

  if (url.origin !== stagingOrigin) return "";
  if (/\.(css|js|mjs|png|jpe?g|webp|svg|avif|ico|woff2?|ttf|otf|pdf|xml|txt)(\?|$)/i.test(url.pathname + url.search)) {
    return "";
  }
  return normalizeRoutePath(url.pathname);
}

function safeFileStem(routePath) {
  if (routePath === "/") return "index";
  return routePath
    .replace(/^\/+|\/+$/g, "")
    .replace(/[^a-zA-Z0-9._-]+/g, "-")
    .replace(/^-+|-+$/g, "") || "route";
}

function safeAssetName(assetUrl) {
  const url = new URL(assetUrl);
  const ext = path.extname(url.pathname) || ".asset";
  const base = url.pathname
    .replace(/^\/+/, "")
    .replace(new RegExp(`${ext.replace(".", "\\.")}$`), "")
    .replace(/[^a-zA-Z0-9._-]+/g, "-")
    .replace(/^-+|-+$/g, "")
    .slice(0, 140) || "asset";
  return `${base}-${sha256(assetUrl).slice(0, 12)}${ext}`;
}

function headersObject(response) {
  if (typeof response.headers === "function") {
    return response.headers();
  }

  const headers = {};
  for (const [key, value] of response.headers.entries()) headers[key] = value;
  return headers;
}

function responseStatus(response) {
  return typeof response.status === "function" ? response.status() : response.status;
}

function cleanVisibleText(value) {
  return String(value || "").replace(/\s+/g, " ").trim();
}

function stripVisibleText(html) {
  return decodeHtml(html)
    .replace(/<script[\s\S]*?<\/script>/gi, " ")
    .replace(/<style[\s\S]*?<\/style>/gi, " ")
    .replace(/<noscript[\s\S]*?<\/noscript>/gi, " ")
    .replace(/<!--[\s\S]*?-->/g, " ")
    .replace(/<[^>]+>/g, " ")
    .replace(/\s+/g, " ")
    .trim();
}

function matchFirst(html, pattern) {
  const match = html.match(pattern);
  return match ? decodeHtml(match[1]).replace(/\s+/g, " ").trim() : "";
}

function extractMetaTags(html) {
  const meta = {};
  for (const match of html.matchAll(/<meta\b([^>]*)>/gi)) {
    const attrs = attributes(match[1]);
    const key = attrs.name || attrs.property;
    if (key) meta[key] = attrs.content || "";
  }
  return meta;
}

function attributes(value) {
  const attrs = {};
  for (const match of String(value || "").matchAll(/([:@a-zA-Z0-9_-]+)\s*=\s*(["'])(.*?)\2/g)) {
    attrs[match[1].toLowerCase()] = decodeHtml(match[3]);
  }
  return attrs;
}

function extractHeadings(html) {
  const headings = [];
  for (const match of html.matchAll(/<(h[1-6])\b[^>]*>([\s\S]*?)<\/\1>/gi)) {
    headings.push({
      level: Number.parseInt(match[1].slice(1), 10),
      text: stripVisibleText(match[2])
    });
  }
  return headings.filter((heading) => heading.text);
}

function extractJsonLd(html) {
  const nodes = [];
  const types = [];
  for (const match of html.matchAll(/<script[^>]+type=["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/gi)) {
    const raw = decodeHtml(match[1]).trim();
    try {
      const parsed = JSON.parse(raw);
      const graph = Array.isArray(parsed["@graph"]) ? parsed["@graph"] : [parsed];
      nodes.push(...graph);
      for (const node of graph) {
        const type = node?.["@type"];
        if (Array.isArray(type)) types.push(...type);
        else if (type) types.push(type);
      }
    } catch {
      nodes.push({ "@type": "(invalid-json)" });
      types.push("(invalid-json)");
    }
  }
  return { count: nodes.length, types: [...new Set(types)].sort(), nodes };
}

function extractLinks(html, pagePath) {
  const links = [];
  for (const match of html.matchAll(/<a\b[^>]*\bhref\s*=\s*(["'])(.*?)\1/gi)) {
    const href = decodeHtml(match[2]).trim();
    const internalPath = routePathFromHref(href, pagePath);
    links.push({ href, internalPath });
  }
  return links;
}

function extractAssets(html, pagePath) {
  const assets = new Set();
  const attrPattern = /(?:src|href|poster)\s*=\s*(["'])(.*?)\1/gi;
  for (const match of html.matchAll(attrPattern)) {
    const assetUrl = normalizeAssetUrl(match[2], pagePath);
    if (assetUrl) assets.add(assetUrl);
  }

  for (const match of html.matchAll(/srcset\s*=\s*(["'])(.*?)\1/gi)) {
    for (const candidate of decodeHtml(match[2]).split(",")) {
      const assetUrl = normalizeAssetUrl(candidate.trim().split(/\s+/)[0], pagePath);
      if (assetUrl) assets.add(assetUrl);
    }
  }

  return [...assets].sort();
}

function extractCssAssets(css, assetUrl) {
  const assets = new Set();
  for (const match of css.matchAll(/url\((["']?)(.*?)\1\)/gi)) {
    const nested = normalizeAssetUrl(match[2], new URL(assetUrl).pathname);
    if (nested) assets.add(nested);
  }
  return [...assets].sort();
}

function relativeToRoot(filePath) {
  return path.relative(root, filePath).split(path.sep).join("/");
}

async function fetchTextRoute(routePath) {
  const url = new URL(routePath, stagingBaseUrl);
  const response = await fetch(url, { redirect: "manual" });
  const body = await response.text();
  const text = stripVisibleText(body);
  return {
    response,
    body,
    text,
    mainText: text,
    structure: {},
    textSource: "html-strip-fallback",
  };
}

async function createBrowserRouteReader() {
  try {
    const { chromium } = await import("playwright");
    const browser = await chromium.launch();
    const context = await browser.newContext({ viewport: { width: 1440, height: 1200 } });
    const page = await context.newPage();
    return { browser, context, page };
  } catch (error) {
    failures.push(`browser visible-text capture unavailable: ${error.message}`);
    return null;
  }
}

async function fetchBrowserTextRoute(routePath, reader) {
  if (!reader) return fetchTextRoute(routePath);

  const url = new URL(routePath, stagingBaseUrl);
  const response = await reader.page.goto(url.toString(), { waitUntil: "networkidle", timeout: 30000 });
  if (!response) throw new Error(`no browser response for ${url.toString()}`);

  const body = await response.text();
  const text = cleanVisibleText(await reader.page.evaluate(() => document.body?.innerText || ""));
  const mainText = cleanVisibleText(await reader.page.evaluate(() => document.querySelector("main")?.innerText || document.body?.innerText || ""));
  const structure = await reader.page.evaluate(() => {
    const clean = (value) => String(value || "").replace(/\s+/g, " ").trim();
    const root = document.querySelector("main") || document.body;
    const paragraphs = Array.from(root.querySelectorAll("p"))
      .map((element) => clean(element.innerText))
      .filter(Boolean);
    return {
      headingCount: root.querySelectorAll("h1,h2,h3,h4,h5,h6").length,
      paragraphCount: paragraphs.length,
      imageCount: root.querySelectorAll("img").length,
      maxParagraphLength: Math.max(0, ...paragraphs.map((text) => text.length)),
    };
  });
  return { response, body, text, mainText, structure, textSource: "browser-innerText" };
}

function classifyRoute(route, liveStatus) {
  const status = route?.migrationStatus || (route ? "missing-status" : "discovered");
  if (route?.url === "/404.html") return "special-404-route";
  if (status === "out-of-scope" && liveStatus === 200) return "requires-verbatim-decision";
  if (liveStatus >= 300 && liveStatus < 400) return "redirect";
  if (liveStatus === 200) return "migrate-verbatim";
  if (liveStatus === 404) return "not-found";
  return "review";
}

function collectInitialPaths() {
  const paths = new Set();
  for (const route of manifestRoutes) {
    const routePath = normalizeRoutePath(route.url);
    if (routePath) paths.add(routePath);
  }
  return paths;
}

async function crawlRoutes() {
  const queue = [...collectInitialPaths()].sort();
  const queued = new Set(queue);
  const browserRouteReader = await createBrowserRouteReader();

  try {
    for (let index = 0; index < queue.length; index += 1) {
      const routePath = queue[index];
      const route = routeByPath.get(routePath) || null;
      const stem = safeFileStem(routePath);
      const htmlFile = path.join(htmlDir, `${stem}.html`);

      try {
      const { response, body, text, mainText, structure, textSource } = await fetchBrowserTextRoute(routePath, browserRouteReader);
      fs.writeFileSync(htmlFile, body);

      const headers = headersObject(response);
      const liveStatus = responseStatus(response);
      const headings = extractHeadings(body);
      const links = extractLinks(body, routePath);
      const assets = extractAssets(body, routePath);
      const meta = extractMetaTags(body);
      const jsonLd = extractJsonLd(body);
      const editableMarkers = [...body.matchAll(/data-lmhg-edit-field=/g)].length;

      for (const assetUrl of assets) {
        if (!assetMap.has(assetUrl)) {
          assetMap.set(assetUrl, {
            url: assetUrl,
            path: new URL(assetUrl).pathname,
            query: new URL(assetUrl).search,
            referencedBy: new Set()
          });
        }
        assetMap.get(assetUrl).referencedBy.add(routePath);
      }

      for (const link of links) {
        if (
          link.internalPath &&
          !queued.has(link.internalPath) &&
          !link.internalPath.startsWith("/wp-") &&
          discoveredPaths.size < maxDiscoveredRoutes
        ) {
          discoveredPaths.add(link.internalPath);
          queued.add(link.internalPath);
          queue.push(link.internalPath);
        }
      }

      routes.push({
        url: routePath,
        source: route ? "manifest" : "discovered",
        manifestStatus: route?.migrationStatus || "",
        pageFamily: route?.pageFamily || "",
        templateFamily: route?.templateFamily || "",
        sourcePath: route?.sourceContent?.path || "",
        liveStatus,
        redirectLocation: headers.location || "",
        classification: classifyRoute(route, liveStatus),
        htmlHash: sha256(body),
        htmlArtifactPath: relativeToRoot(htmlFile),
        title: matchFirst(body, /<title>([\s\S]*?)<\/title>/i),
        h1: headings.find((heading) => heading.level === 1)?.text || "",
        canonical: matchFirst(body, /<link[^>]+rel=["']canonical["'][^>]+href=["']([^"']+)["'][^>]*>/i),
        metaDescription: meta.description || "",
        robotsMeta: meta.robots || "",
        xRobotsTag: headers["x-robots-tag"] || "",
        openGraphCount: Object.keys(meta).filter((key) => key.startsWith("og:")).length,
        twitterMetaCount: Object.keys(meta).filter((key) => key.startsWith("twitter:")).length,
        jsonLdTypes: jsonLd.types,
        jsonLdCount: jsonLd.count,
        headingOutline: headings.slice(0, 20),
        visibleTextHash: sha256(text),
        visibleTextLength: text.length,
        mainVisibleTextHash: sha256(mainText),
        mainVisibleTextLength: mainText.length,
        mainHeadingCount: Number(structure.headingCount || 0),
        mainParagraphCount: Number(structure.paragraphCount || 0),
        mainImageCount: Number(structure.imageCount || 0),
        mainMaxParagraphLength: Number(structure.maxParagraphLength || 0),
        visibleTextSource: textSource,
        editableMarkerCount: editableMarkers,
        internalLinkCount: links.filter((link) => link.internalPath).length,
        externalLinkCount: links.filter((link) => !link.internalPath).length,
        assetCount: assets.length,
        assets
      });
      } catch (error) {
        failures.push(`${routePath}: ${error.message}`);
        routes.push({
          url: routePath,
          source: route ? "manifest" : "discovered",
          manifestStatus: route?.migrationStatus || "",
          pageFamily: route?.pageFamily || "",
          templateFamily: route?.templateFamily || "",
          sourcePath: route?.sourceContent?.path || "",
          liveStatus: 0,
          redirectLocation: "",
          classification: "blocked",
          error: error.message
        });
      }
    }
  } finally {
    if (browserRouteReader) {
      await browserRouteReader.context.close();
      await browserRouteReader.browser.close();
    }
  }

  routes.sort((a, b) => a.url.localeCompare(b.url));
}

async function crawlRedirects() {
  for (const redirect of manifestRedirects) {
    const source = redirectSourcePath(redirect.source);
    if (!source) continue;

    try {
      const response = await fetch(new URL(source, stagingBaseUrl), { redirect: "manual" });
      redirects.push({
        source,
        expectedTarget: normalizeRoutePath(redirect.target),
        expectedStatusCode: Number.parseInt(redirect.statusCode, 10) || redirect.statusCode,
        section: redirect.section || "",
        liveStatus: response.status,
        liveLocation: response.headers.get("location") || "",
        xRobotsTag: response.headers.get("x-robots-tag") || "",
        matchesExpectedStatus: response.status === (Number.parseInt(redirect.statusCode, 10) || redirect.statusCode)
      });
    } catch (error) {
      failures.push(`redirect ${source}: ${error.message}`);
      redirects.push({
        source,
        expectedTarget: normalizeRoutePath(redirect.target),
        expectedStatusCode: Number.parseInt(redirect.statusCode, 10) || redirect.statusCode,
        section: redirect.section || "",
        liveStatus: 0,
        liveLocation: "",
        error: error.message,
        matchesExpectedStatus: false
      });
    }
  }
}

async function downloadAssets() {
  const pendingNestedAssets = new Set();

  for (const [assetUrl, asset] of assetMap) {
    try {
      const response = await fetch(assetUrl);
      const buffer = Buffer.from(await response.arrayBuffer());
      const fileName = safeAssetName(assetUrl);
      const filePath = path.join(assetDir, fileName);
      fs.writeFileSync(filePath, buffer);

      asset.status = response.status;
      asset.contentType = response.headers.get("content-type") || "";
      asset.contentLength = buffer.length;
      asset.contentHash = sha256(buffer);
      asset.artifactPath = relativeToRoot(filePath);
      asset.referencedBy = [...asset.referencedBy].sort();

      if (response.status === 200 && /text\/css/i.test(asset.contentType)) {
        const css = buffer.toString("utf8");
        for (const nested of extractCssAssets(css, assetUrl)) {
          if (!assetMap.has(nested)) pendingNestedAssets.add(nested);
        }
      }
    } catch (error) {
      asset.status = 0;
      asset.error = error.message;
      asset.referencedBy = [...asset.referencedBy].sort();
      failures.push(`asset ${assetUrl}: ${error.message}`);
    }
  }

  for (const nested of pendingNestedAssets) {
    if (!assetMap.has(nested)) {
      assetMap.set(nested, {
        url: nested,
        path: new URL(nested).pathname,
        query: new URL(nested).search,
        referencedBy: ["css-url()"]
      });
    }
  }

  for (const [assetUrl, asset] of assetMap) {
    if (asset.status !== undefined) continue;
    try {
      const response = await fetch(assetUrl);
      const buffer = Buffer.from(await response.arrayBuffer());
      const fileName = safeAssetName(assetUrl);
      const filePath = path.join(assetDir, fileName);
      fs.writeFileSync(filePath, buffer);
      asset.status = response.status;
      asset.contentType = response.headers.get("content-type") || "";
      asset.contentLength = buffer.length;
      asset.contentHash = sha256(buffer);
      asset.artifactPath = relativeToRoot(filePath);
      asset.referencedBy = Array.isArray(asset.referencedBy) ? asset.referencedBy : [...asset.referencedBy].sort();
    } catch (error) {
      asset.status = 0;
      asset.error = error.message;
      asset.referencedBy = Array.isArray(asset.referencedBy) ? asset.referencedBy : [...asset.referencedBy].sort();
      failures.push(`asset ${assetUrl}: ${error.message}`);
    }
  }
}

async function captureScreenshots() {
  if (!screenshotMode) return [];

  let chromium;
  try {
    chromium = (await import("playwright")).chromium;
  } catch (error) {
    failures.push(`screenshots unavailable: ${error.message}`);
    return [];
  }

  const browser = await chromium.launch();
  const screenshotRows = [];
  const screenshotRoutes = routes.filter((route) => route.liveStatus === 200);

  for (const viewport of viewports) {
    const context = await browser.newContext({ viewport: { width: viewport.width, height: viewport.height } });
    const page = await context.newPage();
    const viewportDir = path.join(screenshotDir, viewport.name);
    fs.mkdirSync(viewportDir, { recursive: true });

    for (const route of screenshotRoutes) {
      const filePath = path.join(viewportDir, `${safeFileStem(route.url)}.png`);
      try {
        const response = await page.goto(new URL(route.url, stagingBaseUrl).toString(), { waitUntil: "networkidle" });
        await page.screenshot({ path: filePath, fullPage: true });
        screenshotRows.push({
          route: route.url,
          viewport: viewport.name,
          status: response?.status() || 0,
          artifactPath: relativeToRoot(filePath)
        });
      } catch (error) {
        failures.push(`screenshot ${viewport.name} ${route.url}: ${error.message}`);
        screenshotRows.push({
          route: route.url,
          viewport: viewport.name,
          status: 0,
          error: error.message
        });
      }
    }

    await context.close();
  }

  await browser.close();
  return screenshotRows;
}

function summarize(screenshots) {
  const routeStatusCounts = {};
  const routeClassificationCounts = {};
  const redirectStatusCounts = {};
  const assetStatusCounts = {};
  const assetExtensionCounts = {};

  for (const route of routes) {
    routeStatusCounts[route.liveStatus] = (routeStatusCounts[route.liveStatus] || 0) + 1;
    routeClassificationCounts[route.classification] = (routeClassificationCounts[route.classification] || 0) + 1;
  }

  for (const redirect of redirects) {
    redirectStatusCounts[redirect.liveStatus] = (redirectStatusCounts[redirect.liveStatus] || 0) + 1;
  }

  for (const asset of assetMap.values()) {
    assetStatusCounts[asset.status] = (assetStatusCounts[asset.status] || 0) + 1;
    const ext = path.extname(new URL(asset.url).pathname).replace(".", "").toLowerCase() || "unknown";
    assetExtensionCounts[ext] = (assetExtensionCounts[ext] || 0) + 1;
  }

  return {
    generatedAt: new Date().toISOString(),
    stagingBaseUrl,
    manifestRoutes: manifestRoutes.length,
    manifestRedirects: manifestRedirects.length,
    capturedRoutes: routes.length,
    discoveredRoutes: routes.filter((route) => route.source === "discovered").length,
    capturedRedirects: redirects.length,
    capturedAssets: assetMap.size,
    capturedScreenshots: screenshots.length,
    routeStatusCounts,
    routeClassificationCounts,
    redirectStatusCounts,
    assetStatusCounts,
    assetExtensionCounts,
    failures
  };
}

function writeJsonFiles(summary, screenshots) {
  const assetRows = [...assetMap.values()]
    .map((asset) => ({
      ...asset,
      referencedBy: Array.isArray(asset.referencedBy) ? asset.referencedBy : [...asset.referencedBy].sort()
    }))
    .sort((a, b) => a.path.localeCompare(b.path) || a.url.localeCompare(b.url));

  fs.writeFileSync(path.join(dataDir, "summary.json"), `${JSON.stringify(summary, null, 2)}\n`);
  fs.writeFileSync(path.join(dataDir, "routes.json"), `${JSON.stringify(routes, null, 2)}\n`);
  fs.writeFileSync(path.join(dataDir, "redirects.json"), `${JSON.stringify(redirects, null, 2)}\n`);
  fs.writeFileSync(path.join(dataDir, "assets.json"), `${JSON.stringify(assetRows, null, 2)}\n`);
  fs.writeFileSync(path.join(dataDir, "screenshots.json"), `${JSON.stringify(screenshots, null, 2)}\n`);
}

function markdownTable(rows, columns) {
  const header = `| ${columns.map((column) => column.label).join(" | ")} |`;
  const separator = `| ${columns.map(() => "---").join(" | ")} |`;
  const body = rows.map((row) => `| ${columns.map((column) => escapeCell(column.value(row))).join(" | ")} |`);
  return [header, separator, ...body].join("\n");
}

function escapeCell(value) {
  return String(value ?? "")
    .replace(/\|/g, "\\|")
    .replace(/\n/g, " ")
    .trim();
}

function writeDocs(summary) {
  const visibleRoutes = routes.filter((route) => route.liveStatus === 200);
  const assetsWithFailures = [...assetMap.values()].filter((asset) => asset.status !== 200);
  const redirectMismatches = redirects.filter((redirect) => !redirect.matchesExpectedStatus);
  const requiresDecision = routes.filter((route) => route.classification === "requires-verbatim-decision");

  fs.writeFileSync(docsSnapshotPath, `# LMHG Cloudflare Staging Snapshot

Date: ${summary.generatedAt}

Staging baseline: ${stagingBaseUrl}

This snapshot is the first migration-grade baseline for converting the current
Cloudflare staging site into a standalone WordPress site. Bulk HTML, asset, and
screenshot files are written under \`artifacts/staging-snapshot/\`; compact JSON
indexes are committed under \`data/lmhg/staging-snapshot/\`.

## Summary

- Manifest routes: ${summary.manifestRoutes}
- Captured routes: ${summary.capturedRoutes}
- Discovered non-manifest routes: ${summary.discoveredRoutes}
- Visible \`200\` routes: ${visibleRoutes.length}
- Manifest redirects checked: ${summary.capturedRedirects}
- Distinct assets captured: ${summary.capturedAssets}
- Screenshots captured: ${summary.capturedScreenshots}
- Route status counts: \`${JSON.stringify(summary.routeStatusCounts)}\`
- Route classifications: \`${JSON.stringify(summary.routeClassificationCounts)}\`
- Redirect status counts: \`${JSON.stringify(summary.redirectStatusCounts)}\`
- Asset extension counts: \`${JSON.stringify(summary.assetExtensionCounts)}\`
- Asset status counts: \`${JSON.stringify(summary.assetStatusCounts)}\`

## Indexing Suppression

This is still a development/staging migration. WordPress staging must preserve
noindex and discovery suppression until Tyler explicitly approves production
cutover. Parity scripts should verify staging \`X-Robots-Tag\`, robots meta,
and discovery-file behavior separately from the future production launch switch.

## Decisions Required

${requiresDecision.length > 0 ? requiresDecision.map((route) => `- \`${route.url}\` is marked \`${route.manifestStatus}\` in the current manifest but returns \`${route.liveStatus}\` on staging.`).join("\n") : "- No out-of-scope staging 200 routes were found."}

## Redirect Status Mismatches

${redirectMismatches.length > 0 ? redirectMismatches.slice(0, 40).map((redirect) => `- \`${redirect.source}\`: expected ${redirect.expectedStatusCode}, got ${redirect.liveStatus} (${redirect.liveLocation || "no location"})`).join("\n") : "- No redirect status mismatches were found."}

## Asset Fetch Issues

${assetsWithFailures.length > 0 ? assetsWithFailures.slice(0, 40).map((asset) => `- \`${asset.url}\`: status ${asset.status || 0}${asset.error ? ` (${asset.error})` : ""}`).join("\n") : "- No asset fetch issues were found."}

## Generated Files

- \`data/lmhg/staging-snapshot/summary.json\`
- \`data/lmhg/staging-snapshot/routes.json\`
- \`data/lmhg/staging-snapshot/redirects.json\`
- \`data/lmhg/staging-snapshot/assets.json\`
- \`data/lmhg/staging-snapshot/screenshots.json\`
- \`docs/route-parity-matrix.md\`
- \`artifacts/staging-snapshot/\` (ignored bulk crawl artifacts)
`);

  fs.writeFileSync(docsParityPath, `# LMHG Route Parity Matrix

Date: ${summary.generatedAt}

Staging baseline: ${stagingBaseUrl}

This matrix is the routing baseline for the verbatim WordPress migration. It is
generated from \`npm run crawl:staging\` and should be regenerated whenever the
Cloudflare staging site changes.

${markdownTable(routes, [
  { label: "Route", value: (route) => route.url },
  { label: "Source", value: (route) => route.source },
  { label: "Family", value: (route) => route.pageFamily || route.templateFamily || "" },
  { label: "Manifest Status", value: (route) => route.manifestStatus },
  { label: "Live Status", value: (route) => route.liveStatus },
  { label: "Classification", value: (route) => route.classification },
  { label: "H1", value: (route) => route.h1 },
  { label: "Assets", value: (route) => route.assetCount ?? "" },
  { label: "Text Hash", value: (route) => route.visibleTextHash ? route.visibleTextHash.slice(0, 12) : "" }
])}
`);
}

ensureDirs();
await crawlRoutes();
await crawlRedirects();
await downloadAssets();
const screenshots = await captureScreenshots();
const summary = summarize(screenshots);
writeJsonFiles(summary, screenshots);
writeDocs(summary);

console.log(JSON.stringify(summary, null, 2));

if (failures.length > 0) {
  console.error("Staging snapshot completed with warnings:");
  for (const failure of failures.slice(0, 50)) console.error(`- ${failure}`);
  if (failures.length > 50) console.error(`- ... ${failures.length - 50} more`);
}

console.log("LMHG staging snapshot crawl completed.");
