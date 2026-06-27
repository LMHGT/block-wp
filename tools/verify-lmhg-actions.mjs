import fs from "node:fs";
import path from "node:path";

const root = process.cwd();
const manifestPath = path.join(root, "data/lmhg/source-route-manifest.json");
const baseUrl = process.env.WP_BASE_URL || "http://localhost:8888";
const manifest = JSON.parse(fs.readFileSync(manifestPath, "utf8"));
const failures = [];

const expectedPrimaryCta = {
  label: "Reach Out",
  href: "https://intakeq.com/new/g91Z8x/bjxuno"
};
const expectedPhone = {
  label: "Call (502) 416-1416",
  href: "tel:5024161416"
};

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

function decodeHtml(value) {
  return String(value || "")
    .replace(/&#(\d+);/g, (_, code) => String.fromCharCode(Number.parseInt(code, 10)))
    .replace(/&amp;/g, "&")
    .replace(/&quot;/g, '"')
    .replace(/&#039;/g, "'")
    .replace(/&lt;/g, "<")
    .replace(/&gt;/g, ">");
}

function visibleText(html) {
  return decodeHtml(html)
    .replace(/<style[\s\S]*?<\/style>/gi, " ")
    .replace(/<script[\s\S]*?<\/script>/gi, " ")
    .replace(/<[^>]+>/g, " ")
    .replace(/\s+/g, " ")
    .trim();
}

function attrValue(tag, name) {
  const pattern = new RegExp(`\\b${name}\\s*=\\s*(["'])(.*?)\\1`, "i");
  const match = tag.match(pattern);
  return match ? decodeHtml(match[2]).trim() : "";
}

function extractAnchors(html) {
  const anchors = [];
  const pattern = /<a\b([^>]*)>([\s\S]*?)<\/a>/gi;
  let match;

  while ((match = pattern.exec(html)) !== null) {
    const tag = match[1];
    anchors.push({
      href: attrValue(tag, "href"),
      rel: attrValue(tag, "rel"),
      text: visibleText(match[2])
    });
  }

  return anchors;
}

const routes = (manifest.routes || [])
  .filter((route) => route.migrationStatus !== "out-of-scope")
  .filter((route) => normalizePath(route.url) !== "/404.html")
  .filter((route) => !normalizePath(route.url).startsWith("/review/"))
  .map((route) => normalizePath(route.url))
  .sort();

let checkedRoutes = 0;
let checkedPrimaryCtas = 0;
let checkedPhoneLinks = 0;

for (const routePath of routes) {
  const response = await fetch(new URL(routePath, baseUrl));
  const html = await response.text();
  checkedRoutes += 1;

  if (response.status !== 200) {
    fail(`${routePath} expected HTTP 200, got ${response.status}`);
    continue;
  }

  const anchors = extractAnchors(html);
  const primaryCtas = anchors.filter(
    (anchor) => anchor.text === expectedPrimaryCta.label && anchor.href === expectedPrimaryCta.href
  );
  const phoneLinks = anchors.filter(
    (anchor) => anchor.text === expectedPhone.label && anchor.href === expectedPhone.href
  );

  checkedPrimaryCtas += primaryCtas.length;
  checkedPhoneLinks += phoneLinks.length;

  if (primaryCtas.length < 1) {
    fail(`${routePath} missing primary CTA ${expectedPrimaryCta.label} -> ${expectedPrimaryCta.href}`);
  }

  for (const anchor of primaryCtas) {
    const relTokens = new Set(anchor.rel.split(/\s+/).filter(Boolean));
    if (!relTokens.has("noopener")) {
      fail(`${routePath} primary CTA is missing rel="noopener"`);
    }
  }

  if (phoneLinks.length < 1) {
    fail(`${routePath} missing phone CTA ${expectedPhone.label} -> ${expectedPhone.href}`);
  }
}

console.log(JSON.stringify({
  baseUrl,
  checkedRoutes,
  checkedPrimaryCtas,
  checkedPhoneLinks,
  primaryCtaHref: expectedPrimaryCta.href,
  phoneHref: expectedPhone.href
}, null, 2));

if (failures.length > 0) {
  console.error("LMHG action verification failed:");
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log("LMHG action verification passed.");
