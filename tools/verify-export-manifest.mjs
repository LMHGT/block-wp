import crypto from "node:crypto";
import fs from "node:fs";
import path from "node:path";

const root = process.cwd();
const manifestPath = path.join(root, "data/lmhg/export/first-slice-export-manifest.json");
const required = new Set([
  "wp-content/themes/lmhg-block-theme/theme.json",
  "wp-content/plugins/lmhg-site-core/lmhg-site-core.php",
  "wp-content/plugins/lmhg-site-core/includes/editable-blocks.php",
  "data/lmhg/source-route-manifest.json",
  "data/lmhg/block-migration/first-slice-block-manifest.json",
  "data/lmhg/block-migration/first-slice-media-manifest.json",
]);
let failures = 0;

const manifest = JSON.parse(fs.readFileSync(manifestPath, "utf8"));
if (manifest.schemaVersion !== "2026-06-27.first-slice-export.v1") {
  fail("export manifest has an unexpected schema version");
}

const files = Array.isArray(manifest.files) ? manifest.files : [];
const paths = new Set(files.map((entry) => entry.path));
for (const file of required) {
  if (!paths.has(file)) fail(`export manifest is missing ${file}`);
}

for (const entry of files) {
  const filePath = path.join(root, entry.path);
  if (!fs.existsSync(filePath)) {
    fail(`export file is missing on disk: ${entry.path}`);
    continue;
  }

  const buffer = fs.readFileSync(filePath);
  const actual = crypto.createHash("sha256").update(buffer).digest("hex");
  if (actual !== entry.sha256) {
    fail(`hash mismatch for ${entry.path}`);
  }
}

if (failures > 0) process.exit(1);

console.log(JSON.stringify({
  files: files.length,
  bytes: files.reduce((sum, entry) => sum + entry.bytes, 0),
}, null, 2));
console.log("First slice export manifest verification passed.");

function fail(message) {
  console.error(message);
  failures += 1;
}
