import fs from "node:fs";
import path from "node:path";

const root = process.cwd();
const blockManifestPath = path.join(root, "data/lmhg/block-migration/full-site-block-manifest.json");
const mediaManifestPath = path.join(root, "data/lmhg/block-migration/full-site-media-manifest.json");
const stagingRoutesPath = path.join(root, "data/lmhg/staging-snapshot/routes.json");
let failures = 0;

const blockManifest = readJson(blockManifestPath);
const mediaManifest = readJson(mediaManifestPath);
const stagingRoutes = readJson(stagingRoutesPath);
const expectedRoutes = stagingRoutes
  .filter((route) => route.liveStatus === 200)
  .map((route) => route.url)
  .sort((a, b) => a.localeCompare(b));

if (blockManifest.schemaVersion !== "2026-06-27.full-site-editable-blocks.v1") {
  fail("block manifest has an unexpected schema version");
}
if (mediaManifest.schemaVersion !== "2026-06-27.full-site-editable-media.v1") {
  fail("media manifest has an unexpected schema version");
}

const routes = Array.isArray(blockManifest.routes) ? blockManifest.routes : [];
const actualRoutes = routes.map((route) => route.url).sort((a, b) => a.localeCompare(b));
if (JSON.stringify(actualRoutes) !== JSON.stringify(expectedRoutes)) {
  fail(`full-site block manifest route set mismatch: expected ${expectedRoutes.length}, got ${actualRoutes.length}`);
}

for (const route of routes) {
  if (typeof route.postContent !== "string" || !route.postContent.includes("<!-- wp:")) {
    fail(`${route.url} has no serialized Gutenberg block content`);
  }
  if (!Array.isArray(route.blocks) || route.blocks.length < 1) {
    fail(`${route.url} has no correlated blocks`);
  }
  if (!route.generatedTextMatchesSource) {
    fail(`${route.url} generated visible text hash does not match staging source hash`);
  }
  if (route.generatedTextHash !== route.sourceRouteTextHash) {
    fail(`${route.url} generated hash ${route.generatedTextHash} != source hash ${route.sourceRouteTextHash}`);
  }
  const blockIds = new Set();
  for (const block of route.blocks ?? []) {
    if (!block.blockId || blockIds.has(block.blockId)) {
      fail(`${route.url} has missing or duplicate block id ${block.blockId}`);
    }
    blockIds.add(block.blockId);
    if (!block.coreBlockName || !block.sourceField || !block.sourceSelector) {
      fail(`${route.url} block ${block.blockId} is missing correlation fields`);
    }
  }
}

const assets = Array.isArray(mediaManifest.assets) ? mediaManifest.assets : [];
for (const asset of assets) {
  if (!asset.assetId || !asset.kind || !Array.isArray(asset.routeUsage) || asset.routeUsage.length < 1) {
    fail(`asset ${asset.assetId || "(missing id)"} is missing required correlation`);
  }
}

if (failures > 0) process.exit(1);

console.log(JSON.stringify({
  routes: routes.length,
  blocks: routes.reduce((sum, route) => sum + route.blocks.length, 0),
  assets: assets.length,
}, null, 2));
console.log("Full-site editable block migration verification passed.");

function readJson(filePath) {
  try {
    return JSON.parse(fs.readFileSync(filePath, "utf8"));
  } catch (error) {
    fail(`${path.relative(root, filePath)} is not readable JSON: ${error.message}`);
    return {};
  }
}

function fail(message) {
  console.error(message);
  failures += 1;
}
