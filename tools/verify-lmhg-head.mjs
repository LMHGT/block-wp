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
  for (const item of jsonLdNodes(html)) {
    const type = item?.["@type"];
    if (Array.isArray(type)) types.push(...type);
    else if (type) types.push(type);
  }
  return types;
}

function jsonLdNodes(html) {
  const nodes = [];
  const scripts = html.matchAll(/<script[^>]+type=["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/gi);
  for (const script of scripts) {
    try {
      const parsed = JSON.parse(decodeHtml(script[1]).trim());
      const graph = Array.isArray(parsed["@graph"]) ? parsed["@graph"] : [parsed];
      nodes.push(...graph);
    } catch {
      nodes.push({ "@type": "(invalid-json)" });
    }
  }
  return nodes;
}

function nodeHasType(node, expectedType) {
  const type = node?.["@type"];
  return Array.isArray(type) ? type.includes(expectedType) : type === expectedType;
}

function findNode(nodes, expectedType) {
  return nodes.find((node) => nodeHasType(node, expectedType));
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
let checkedBreadcrumbLists = 0;

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
  const nodes = jsonLdNodes(html);
  const types = jsonLdTypes(html);
  if (schemaType) {
    checkedSchemaTypes += 1;
    if (!types.includes(schemaType)) {
      fail(`${sourcePath} JSON-LD expected type ${schemaType}, got ${types.join(", ") || "(missing)"}`);
    } else {
      const pageNode = findNode(nodes, schemaType);
      const expectedCanonical = new URL(expectedCanonicalPath, baseUrl).toString();
      if (!pageNode?.name) fail(`${sourcePath} JSON-LD ${schemaType} missing name`);
      if (!pageNode?.url) fail(`${sourcePath} JSON-LD ${schemaType} missing url`);
      if (pageNode?.url && normalizePath(pageNode.url) !== expectedCanonicalPath) {
        fail(`${sourcePath} JSON-LD ${schemaType} url expected ${expectedCanonical}, got ${pageNode.url}`);
      }
      if (!pageNode?.isPartOf || pageNode.isPartOf["@type"] !== "WebSite") {
        fail(`${sourcePath} JSON-LD ${schemaType} missing WebSite isPartOf`);
      }
      if (!pageNode?.dateModified) fail(`${sourcePath} JSON-LD ${schemaType} missing dateModified`);
    }
  }

  if (sourcePath !== "/") {
    checkedBreadcrumbLists += 1;
    const breadcrumbNode = findNode(nodes, "BreadcrumbList");
    if (!breadcrumbNode) {
      fail(`${sourcePath} JSON-LD missing BreadcrumbList`);
    } else {
      const elements = Array.isArray(breadcrumbNode.itemListElement) ? breadcrumbNode.itemListElement : [];
      const relationship = route.relationship && typeof route.relationship === "object" ? route.relationship : {};
      const parentUrl = relationship.primaryParentPageUrl || "";
      const expectedLength = parentUrl && parentUrl !== "/" && parentUrl !== route.url ? 3 : 2;
      if (elements.length !== expectedLength) {
        fail(`${sourcePath} BreadcrumbList expected ${expectedLength} items, got ${elements.length}`);
      }
      elements.forEach((element, index) => {
        if (element?.position !== index + 1) {
          fail(`${sourcePath} BreadcrumbList item ${index + 1} has position ${element?.position || "(missing)"}`);
        }
        if (!element?.name) fail(`${sourcePath} BreadcrumbList item ${index + 1} missing name`);
        if (!element?.item) fail(`${sourcePath} BreadcrumbList item ${index + 1} missing item URL`);
      });
      if (elements[0] && normalizePath(elements[0].item) !== "/") {
        fail(`${sourcePath} BreadcrumbList first item expected /, got ${elements[0].item}`);
      }
      const last = elements[elements.length - 1];
      if (last && normalizePath(last.item) !== expectedCanonicalPath) {
        fail(`${sourcePath} BreadcrumbList last item expected ${expectedCanonicalPath}, got ${last.item}`);
      }
      if (expectedLength === 3 && elements[1] && normalizePath(elements[1].item) !== normalizePath(parentUrl)) {
        fail(`${sourcePath} BreadcrumbList parent expected ${normalizePath(parentUrl)}, got ${elements[1].item}`);
      }
    }
  }

  const publishableFaqItems = Array.isArray(route.faqItems)
    ? route.faqItems.filter((item) => cleanFaqText(item?.question) && cleanFaqText(item?.answer))
    : [];
  if (publishableFaqItems.length > 0) {
    checkedFaqSchemaTypes += 1;
    const faqNode = findNode(nodes, "FAQPage");
    if (!faqNode) {
      fail(`${sourcePath} JSON-LD expected FAQPage for ${publishableFaqItems.length} rendered FAQs`);
    } else {
      const mainEntity = Array.isArray(faqNode.mainEntity) ? faqNode.mainEntity : [];
      if (mainEntity.length !== publishableFaqItems.length) {
        fail(`${sourcePath} FAQPage expected ${publishableFaqItems.length} questions, got ${mainEntity.length}`);
      }
      mainEntity.forEach((item, index) => {
        if (item?.["@type"] !== "Question") fail(`${sourcePath} FAQPage item ${index + 1} is not a Question`);
        if (!item?.name) fail(`${sourcePath} FAQPage item ${index + 1} missing question name`);
        if (item?.acceptedAnswer?.["@type"] !== "Answer") {
          fail(`${sourcePath} FAQPage item ${index + 1} acceptedAnswer is not an Answer`);
        }
        if (!item?.acceptedAnswer?.text) fail(`${sourcePath} FAQPage item ${index + 1} missing answer text`);
      });
    }
  }
}

console.log(JSON.stringify({
  baseUrl,
  checkedRoutes: routes.length,
  checkedSeoTitles,
  checkedMetaDescriptions,
  checkedSchemaTypes,
  checkedFaqSchemaTypes,
  checkedBreadcrumbLists
}, null, 2));

if (failures.length > 0) {
  console.error("LMHG head verification failed:");
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log("LMHG head verification passed.");
