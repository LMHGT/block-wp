import fs from "node:fs";
import path from "node:path";

const root = process.cwd();
const blockManifestPath = path.join(root, "data/lmhg/block-migration/first-slice-block-manifest.json");
const mediaManifestPath = path.join(root, "data/lmhg/block-migration/first-slice-media-manifest.json");
const expectedRoutes = new Set(["/compliance/", "/privacy-policy/", "/terms-of-use/", "/individual-counseling/"]);
let failures = 0;

const blockManifest = readJson(blockManifestPath);
const mediaManifest = readJson(mediaManifestPath);

if (blockManifest.schemaVersion !== "2026-06-27.editable-block-slice.v1") {
  fail("block manifest has an unexpected schema version");
}

if (mediaManifest.schemaVersion !== "2026-06-27.editable-block-media.v1") {
  fail("media manifest has an unexpected schema version");
}

const routes = Array.isArray(blockManifest.routes) ? blockManifest.routes : [];
for (const route of expectedRoutes) {
  if (!routes.some((entry) => entry.url === route)) {
    fail(`missing block route ${route}`);
  }
}

for (const route of routes) {
  if (typeof route.postContent !== "string" || !route.postContent.includes("<!-- wp:")) {
    fail(`${route.url} has no serialized Gutenberg block content`);
  }
  if (!Array.isArray(route.blocks) || route.blocks.length < 3) {
    fail(`${route.url} has too few correlated blocks`);
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
if (assets.length < 1) {
  fail("media manifest must contain at least one visual asset");
}

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
console.log("Editable block migration slice verification passed.");

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
