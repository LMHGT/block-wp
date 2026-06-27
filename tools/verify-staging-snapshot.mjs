import fs from "node:fs";
import path from "node:path";

const root = process.cwd();
const snapshotDir = path.join(root, "data/lmhg/staging-snapshot");
const manifestPath = path.join(root, "data/lmhg/source-route-manifest.json");
const failures = [];

function fail(message) {
  failures.push(message);
}

function readJson(file) {
  return JSON.parse(fs.readFileSync(path.join(snapshotDir, file), "utf8"));
}

function hasNoindex(value) {
  return /noindex/i.test(String(value || ""));
}

const manifest = JSON.parse(fs.readFileSync(manifestPath, "utf8"));
const summary = readJson("summary.json");
const routes = readJson("routes.json");
const redirects = readJson("redirects.json");
const assets = readJson("assets.json");
const screenshots = readJson("screenshots.json");

if (!Array.isArray(routes) || routes.length < (manifest.routes || []).length) {
  fail(`expected at least ${(manifest.routes || []).length} captured routes, found ${routes.length || 0}`);
}

if (!Array.isArray(redirects) || redirects.length !== (manifest.redirects || []).length) {
  fail(`expected ${(manifest.redirects || []).length} captured redirects, found ${redirects.length || 0}`);
}

if (!Array.isArray(assets) || assets.length < 1) {
  fail("expected captured assets");
}

if (!Array.isArray(screenshots) || screenshots.length < 1) {
  fail("expected screenshot metadata from the staging crawl");
}

if (Array.isArray(summary.failures) && summary.failures.length > 0) {
  fail(`snapshot summary recorded ${summary.failures.length} failures`);
}

const visibleRoutes = routes.filter((route) => route.liveStatus === 200);
const decisionRoutes = routes.filter((route) => route.classification === "requires-verbatim-decision");
const specialRoutes = routes.filter((route) => route.classification === "special-404-route");

if (visibleRoutes.length < 50) {
  fail(`expected at least 50 visible staging routes, found ${visibleRoutes.length}`);
}

if (decisionRoutes.length !== 0) {
  fail(`expected no route-decision gaps, found ${decisionRoutes.length}`);
}

if (specialRoutes.length !== 1 || specialRoutes[0].url !== "/404.html") {
  fail("expected /404.html to be the only special 404 route");
}

for (const route of visibleRoutes) {
  if (!route.htmlHash || !route.visibleTextHash) fail(`${route.url} missing content hash`);
  if (!route.title) fail(`${route.url} missing title`);
  if (!hasNoindex(route.xRobotsTag)) fail(`${route.url} missing staging X-Robots-Tag noindex`);
  if (!hasNoindex(route.robotsMeta)) fail(`${route.url} missing staging robots meta noindex`);
}

for (const redirect of redirects) {
  if (redirect.liveStatus !== 301) {
    fail(`${redirect.source} expected live 301 redirect, got ${redirect.liveStatus}`);
  }
  if (!redirect.liveLocation) {
    fail(`${redirect.source} missing live redirect location`);
  }
}

for (const asset of assets) {
  if (asset.status !== 200) fail(`${asset.url} asset status ${asset.status}`);
  if (!asset.contentHash) fail(`${asset.url} missing content hash`);
  if (!asset.artifactPath) fail(`${asset.url} missing artifact path`);
}

console.log(JSON.stringify({
  generatedAt: summary.generatedAt,
  capturedRoutes: routes.length,
  visibleRoutes: visibleRoutes.length,
  decisionRoutes: decisionRoutes.length,
  redirects: redirects.length,
  assets: assets.length,
  screenshots: screenshots.length
}, null, 2));

if (failures.length > 0) {
  console.error("Staging snapshot verification failed:");
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log("Staging snapshot verification passed.");
