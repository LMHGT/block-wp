import fs from "node:fs";
import path from "node:path";

const root = process.cwd();
const manifestPath = path.join(root, "data/lmhg/source-route-manifest.json");
const baseUrl = process.env.WP_BASE_URL || "http://localhost:8888";
const baseOrigin = new URL(baseUrl).origin;
const manifest = JSON.parse(fs.readFileSync(manifestPath, "utf8"));
const failures = [];

function fail(message) {
  failures.push(message);
}

function normalizeRoutePath(value) {
  if (!value || typeof value !== "string") return "";
  const url = value.startsWith("http") ? new URL(value) : new URL(value, baseUrl);
  if (url.pathname === "/") return "/";
  if (path.extname(url.pathname)) return url.pathname;
  return url.pathname.endsWith("/") ? url.pathname : `${url.pathname}/`;
}

function decodeAttribute(value) {
  return String(value || "")
    .replace(/&amp;/g, "&")
    .replace(/&quot;/g, '"')
    .replace(/&#039;/g, "'")
    .replace(/&lt;/g, "<")
    .replace(/&gt;/g, ">");
}

function hrefToInternalPath(href) {
  const decoded = decodeAttribute(href).trim();
  if (
    !decoded ||
    decoded.startsWith("#") ||
    /^(mailto|tel|sms|javascript):/i.test(decoded)
  ) {
    return "";
  }

  let url;
  try {
    url = new URL(decoded, baseUrl);
  } catch {
    return "";
  }

  if (url.origin !== baseOrigin) return "";
  return url.pathname || "/";
}

function extractAnchorPaths(html) {
  const links = [];
  const quotedHrefPattern = /<a\b[^>]*\bhref\s*=\s*(["'])(.*?)\1/gi;
  let match;

  while ((match = quotedHrefPattern.exec(html)) !== null) {
    const href = match[2];
    const pathname = hrefToInternalPath(href);
    if (pathname) links.push({ href: decodeAttribute(href).trim(), pathname });
  }

  return links;
}

const routePaths = new Set(
  (manifest.routes || [])
    .filter((route) => route.migrationStatus !== "out-of-scope")
    .map((route) => normalizeRoutePath(route.url))
    .filter((routePath) => routePath && routePath !== "/404.html" && !routePath.startsWith("/review/"))
);

const renderedRoutePaths = [...routePaths].sort();
const redirectSourcePaths = new Set();
const unsupportedLocationSources = new Set();

for (const redirect of manifest.redirects || []) {
  const sourcePath = hrefToInternalPath(redirect.source);
  if (!sourcePath) continue;

  redirectSourcePaths.add(sourcePath);

  if (redirect.section === "Legacy city/county location pages -> current service area pages") {
    const canonicalSource = normalizeRoutePath(sourcePath);
    if (!routePaths.has(canonicalSource)) {
      unsupportedLocationSources.add(sourcePath);
    }
  }
}

let checkedRoutes = 0;
let checkedLinks = 0;
let checkedUnsupportedLocationSources = 0;

for (const routePath of renderedRoutePaths) {
  const response = await fetch(new URL(routePath, baseUrl));
  const html = await response.text();
  checkedRoutes += 1;

  if (response.status !== 200) {
    fail(`${routePath} expected HTTP 200, got ${response.status}`);
    continue;
  }

  for (const link of extractAnchorPaths(html)) {
    checkedLinks += 1;

    if (unsupportedLocationSources.has(link.pathname)) {
      checkedUnsupportedLocationSources += 1;
      fail(`${routePath} links unsupported service-area redirect source ${link.href}`);
      continue;
    }

    if (redirectSourcePaths.has(link.pathname)) {
      fail(`${routePath} links redirect-only source ${link.href}`);
      continue;
    }

    const canonicalPath = normalizeRoutePath(link.pathname);
    if (!routePaths.has(canonicalPath)) {
      fail(`${routePath} links missing internal route ${link.href}`);
      continue;
    }

    if (link.pathname !== canonicalPath) {
      fail(`${routePath} links non-canonical internal path ${link.href}; expected ${canonicalPath}`);
    }
  }
}

console.log(JSON.stringify({
  baseUrl,
  checkedRoutes,
  checkedLinks,
  redirectOnlySources: redirectSourcePaths.size,
  unsupportedLocationSources: unsupportedLocationSources.size,
  checkedUnsupportedLocationSources
}, null, 2));

if (failures.length > 0) {
  console.error("LMHG link verification failed:");
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log("LMHG link verification passed.");
