import fs from "node:fs";
import { spawnSync } from "node:child_process";
import path from "node:path";

const root = process.cwd();
const manifestPath = path.join(root, "data/lmhg/source-route-manifest.json");
const tempManifestPath = path.join(root, "wp-content/plugins/lmhg-site-core/.lmhg-import-manifest.json");
const tempManifestWpPath = "wp-content/plugins/lmhg-site-core/.lmhg-import-manifest.json";

function run(command, args) {
  const result = spawnSync(command, args, { encoding: "utf8" });
  if (result.stdout.trim()) console.log(result.stdout.trim());
  if (result.stderr.trim()) console.error(result.stderr.trim());
  if (result.status !== 0) process.exit(result.status ?? 1);
}

if (!fs.existsSync(manifestPath)) {
  console.error(`Missing route manifest: ${manifestPath}`);
  process.exit(1);
}

const manifest = fs.readFileSync(manifestPath, "utf8");
JSON.parse(manifest);

try {
  fs.writeFileSync(tempManifestPath, manifest);
  run("npx", ["--no-install", "wp-env", "run", "cli", "wp", "lmhg", "import-manifest", tempManifestWpPath]);
} finally {
  fs.rmSync(tempManifestPath, { force: true });
}
