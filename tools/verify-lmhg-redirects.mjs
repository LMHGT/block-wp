import fs from "node:fs";
import path from "node:path";

const root = process.cwd();
const manifestPath = path.join(root, "data/lmhg/source-route-manifest.json");
const baseUrl = process.env.WP_BASE_URL || "http://localhost:8888";
const manifest = JSON.parse(fs.readFileSync(manifestPath, "utf8"));
const redirects = Array.isArray(manifest.redirects) ? manifest.redirects : [];
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

function targetPathFromLocation(location) {
  if (!location) return "";
  return normalizePath(location);
}

const uniqueRules = new Map();
for (const redirect of redirects) {
  const source = normalizePath(redirect.source);
  const target = normalizePath(redirect.target);
  const statusCode = Number.parseInt(redirect.statusCode || "301", 10);
  if (!source || !target || source === target) continue;
  const key = `${source} ${statusCode} ${target}`;
  if (!uniqueRules.has(key)) uniqueRules.set(key, { source, target, statusCode });
}

for (const rule of uniqueRules.values()) {
  const response = await fetch(new URL(rule.source, baseUrl), {
    method: "GET",
    redirect: "manual"
  });
  const location = response.headers.get("location") || "";
  const locationPath = targetPathFromLocation(location);

  if (response.status !== rule.statusCode) {
    fail(`${rule.source} expected ${rule.statusCode}, got ${response.status}`);
  }

  if (locationPath !== rule.target) {
    fail(`${rule.source} expected Location ${rule.target}, got ${location || "(missing)"}`);
  }
}

console.log(JSON.stringify({
  baseUrl,
  manifestRedirects: redirects.length,
  uniqueRedirectChecks: uniqueRules.size
}, null, 2));

if (failures.length > 0) {
  console.error("LMHG redirect verification failed:");
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log("LMHG redirect verification passed.");
