import fs from "node:fs";
import path from "node:path";

const root = process.cwd();
const manifestPath = path.join(root, "data/lmhg/source-route-manifest.json");
const allowedStatuses = new Set(["ready", "needs-template", "needs-copy-model", "redirect-only", "out-of-scope"]);
const failures = [];

function fail(message) {
  failures.push(message);
}

function normalizeUrl(value) {
  if (!value || typeof value !== "string") return "";
  if (value === "/") return "/";
  const clean = value.startsWith("/") ? value : `/${value}`;
  return clean.endsWith("/") || path.extname(clean) ? clean : `${clean}/`;
}

function assertUnique(items, getKey, label) {
  const seen = new Map();
  for (const item of items) {
    const key = getKey(item);
    if (!key) continue;
    if (seen.has(key)) {
      fail(`${label} duplicate: ${key}`);
      continue;
    }
    seen.set(key, item);
  }
}

if (!fs.existsSync(manifestPath)) {
  fail(`missing route manifest: ${manifestPath}`);
} else {
  const manifest = JSON.parse(fs.readFileSync(manifestPath, "utf8"));
  const routes = Array.isArray(manifest.routes) ? manifest.routes : [];
  const redirects = Array.isArray(manifest.redirects) ? manifest.redirects : [];
  const sourceOnlyRoutes = Array.isArray(manifest.sourceOnlyRoutes) ? manifest.sourceOnlyRoutes : [];

  if (routes.length < 50) fail(`expected at least 50 LMHG route entries, found ${routes.length}`);
  if (redirects.length < 1) fail("expected redirect inventory to be present");

  assertUnique(routes, (route) => normalizeUrl(route.url), "route URL");
  assertUnique(redirects, (redirect) => redirect.source || "", "redirect source");

  const routeUrls = new Set(routes.map((route) => normalizeUrl(route.url)).filter(Boolean));
  const redirectSources = new Set(redirects.map((redirect) => normalizeUrl(redirect.source)).filter(Boolean));
  const redirectTargets = new Set(redirects.map((redirect) => normalizeUrl(redirect.target)).filter(Boolean));
  const redirectsByNormalizedSource = new Map();
  const canonicalUrls = [];
  let inScopeCount = 0;

  for (const route of routes) {
    const url = normalizeUrl(route.url);
    const status = route.migrationStatus || "";

    if (!url) {
      fail(`route has missing URL: ${JSON.stringify(route)}`);
      continue;
    }

    if (!allowedStatuses.has(status)) {
      fail(`${url} has invalid migration status: ${status || "(missing)"}`);
    }

    if (status !== "out-of-scope" && !url.startsWith("/review/")) {
      inScopeCount += 1;
      if (!route.pageFamily) fail(`${url} missing pageFamily`);
      if (!route.templateFamily) fail(`${url} missing templateFamily`);
      if (!route.title) fail(`${url} missing title`);
    }

    const seo = route.seo && typeof route.seo === "object" ? route.seo : null;
    if (seo?.canonicalUrl) {
      canonicalUrls.push({ url, canonicalUrl: normalizeUrl(seo.canonicalUrl) });
    }

    for (const related of Array.isArray(route.relatedPages) ? route.relatedPages : []) {
      const target = normalizeUrl(related.targetPageUrl);
      if (!target || related.avoidLink) continue;
      if (!routeUrls.has(target) && !redirectSources.has(target) && !redirectTargets.has(target)) {
        fail(`${url} related page target is not in route or redirect inventory: ${target}`);
      }
    }

    for (const faq of Array.isArray(route.faqItems) ? route.faqItems : []) {
      if (!faq.question) fail(`${url} FAQ item is missing question text`);
    }
  }

  assertUnique(canonicalUrls, (entry) => entry.canonicalUrl, "canonical URL");

  for (const redirect of redirects) {
    const source = normalizeUrl(redirect.source);
    const target = normalizeUrl(redirect.target);
    if (!source) fail(`redirect missing source: ${JSON.stringify(redirect)}`);
    if (!target) fail(`redirect ${source || "(missing source)"} missing target`);
    if (!redirect.statusCode) fail(`redirect ${source} missing statusCode`);
    if (!redirectsByNormalizedSource.has(source)) redirectsByNormalizedSource.set(source, []);
    redirectsByNormalizedSource.get(source).push({ target, statusCode: redirect.statusCode });
  }

  for (const [source, rules] of redirectsByNormalizedSource) {
    const signatures = new Set(rules.map((rule) => `${rule.statusCode}:${rule.target}`));
    if (signatures.size > 1) {
      fail(`redirect ${source} has conflicting normalized targets: ${[...signatures].join(", ")}`);
    }
  }

  for (const route of sourceOnlyRoutes) {
    const url = normalizeUrl(route.url);
    if (!allowedStatuses.has(route.migrationStatus || "")) {
      fail(`source-only route ${url || "(missing URL)"} has invalid migration status: ${route.migrationStatus || "(missing)"}`);
    }
  }

  console.log(JSON.stringify({
    routes: routes.length,
    inScopeRoutes: inScopeCount,
    redirects: redirects.length,
    sourceOnlyRoutes: sourceOnlyRoutes.length,
    canonicalUrls: canonicalUrls.length
  }, null, 2));
}

if (failures.length > 0) {
  console.error("LMHG static verification failed:");
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log("LMHG static verification passed.");
