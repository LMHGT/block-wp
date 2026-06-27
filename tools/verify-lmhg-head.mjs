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

function matchHeadValue(html, pattern) {
  const match = html.match(pattern);
  return match ? decodeHtml(match[1]) : "";
}

function jsonLdTypes(html) {
  const types = [];
  const scripts = html.matchAll(/<script[^>]+type=["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/gi);
  for (const script of scripts) {
    try {
      const parsed = JSON.parse(decodeHtml(script[1]).trim());
      const graph = Array.isArray(parsed["@graph"]) ? parsed["@graph"] : [parsed];
      for (const item of graph) {
        const type = item?.["@type"];
        if (Array.isArray(type)) types.push(...type);
        else if (type) types.push(type);
      }
    } catch {
      types.push("(invalid-json)");
    }
  }
  return types;
}

function cleanFaqText(value) {
  const text = String(value || "")
    .replace(/<[^>]+>/g, " ")
    .replace(/\s*---\s*$/g, "")
    .replace(/`+\s*$/g, "")
    .replace(/\s+/g, " ")
    .trim();
  if (!text || text === "[...]" || text.includes("[...]") || /^\[[^\]]+\]$/.test(text)) return "";
  return text;
}

const routes = manifest.routes
  .filter((route) => route.migrationStatus !== "out-of-scope")
  .filter((route) => normalizePath(route.url) !== "/404.html")
  .filter((route) => !normalizePath(route.url).startsWith("/review/"));

let checkedMetaDescriptions = 0;
let checkedSeoTitles = 0;
let checkedSchemaTypes = 0;
let checkedFaqSchemaTypes = 0;

for (const route of routes) {
  const sourcePath = normalizePath(route.url);
  const response = await fetch(new URL(sourcePath, baseUrl), { redirect: "manual" });
  const html = await response.text();

  if (response.status !== 200) {
    fail(`${sourcePath} expected HTTP 200, got ${response.status}`);
    continue;
  }

  const seo = route.seo && typeof route.seo === "object" ? route.seo : {};
  const canonicalHref = matchHeadValue(html, /<link[^>]+rel=["']canonical["'][^>]+href=["']([^"']+)["'][^>]*>/i);
  const expectedCanonicalPath = normalizePath(seo.canonicalUrl || route.url);
  const actualCanonicalPath = canonicalHref ? normalizePath(canonicalHref) : "";
  if (actualCanonicalPath !== expectedCanonicalPath) {
    fail(`${sourcePath} canonical expected ${expectedCanonicalPath}, got ${canonicalHref || "(missing)"}`);
  }

  const title = matchHeadValue(html, /<title>([\s\S]*?)<\/title>/i);
  if (seo.title) {
    checkedSeoTitles += 1;
    if (title !== seo.title) fail(`${sourcePath} title expected "${seo.title}", got "${title}"`);
  }

  const metaDescription = matchHeadValue(html, /<meta[^>]+name=["']description["'][^>]+content=["']([^"']*)["'][^>]*>/i);
  if (seo.description) {
    checkedMetaDescriptions += 1;
    if (metaDescription !== seo.description) {
      fail(`${sourcePath} meta description mismatch`);
    }
  } else if (/migration stub/i.test(metaDescription)) {
    fail(`${sourcePath} meta description uses migration stub copy`);
  }

  const schemaType = seo.schemaType || "";
  const types = jsonLdTypes(html);
  if (schemaType) {
    checkedSchemaTypes += 1;
    if (!types.includes(schemaType)) {
      fail(`${sourcePath} JSON-LD expected type ${schemaType}, got ${types.join(", ") || "(missing)"}`);
    }
  }

  const publishableFaqItems = Array.isArray(route.faqItems)
    ? route.faqItems.filter((item) => cleanFaqText(item?.question) && cleanFaqText(item?.answer))
    : [];
  if (publishableFaqItems.length > 0) {
    checkedFaqSchemaTypes += 1;
    if (!types.includes("FAQPage")) {
      fail(`${sourcePath} JSON-LD expected FAQPage for ${publishableFaqItems.length} rendered FAQs`);
    }
  }
}

console.log(JSON.stringify({
  baseUrl,
  checkedRoutes: routes.length,
  checkedSeoTitles,
  checkedMetaDescriptions,
  checkedSchemaTypes,
  checkedFaqSchemaTypes
}, null, 2));

if (failures.length > 0) {
  console.error("LMHG head verification failed:");
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log("LMHG head verification passed.");
