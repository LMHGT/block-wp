import { spawnSync } from "node:child_process";
import path from "node:path";
import process from "node:process";

function parseVersion(value) {
  const match = value.match(/(\d+)\.(\d+)\.(\d+)/);
  return match ? match.slice(1).map((part) => Number(part)) : null;
}

function isAtLeast(actual, minimum) {
  if (!actual) return false;
  for (let i = 0; i < minimum.length; i += 1) {
    if (actual[i] > minimum[i]) return true;
    if (actual[i] < minimum[i]) return false;
  }
  return true;
}

if (!isAtLeast(parseVersion(process.version), [20, 18, 0])) {
  console.error(`WordPress Playground CLI requires Node >=20.18.0. Current: ${process.version}`);
  process.exit(1);
}

const root = process.cwd();
const port = process.env.PLAYGROUND_PORT || "9400";
const themePath = path.join(root, "wp-content/themes/lmhg-block-theme");
const pluginPath = path.join(root, "wp-content/plugins/lmhg-site-core");
const runBlueprintOnly = process.argv.includes("--run-blueprint");
const args = [
  "--no-install",
  "wp-playground-cli",
  runBlueprintOnly ? "run-blueprint" : "server",
  "--blueprint=./blueprints/local-dev",
  "--blueprint-may-read-adjacent-files",
  `--mount=${themePath}:/wordpress/wp-content/themes/lmhg-block-theme`,
  `--mount=${pluginPath}:/wordpress/wp-content/plugins/lmhg-site-core`,
  "--php=8.3",
  "--wp=6.9"
];

if (!runBlueprintOnly) {
  args.push(`--port=${port}`);
  console.log(`Starting WordPress Playground on http://localhost:${port}`);
} else {
  console.log("Running WordPress Playground Blueprint smoke test.");
}

const result = spawnSync("npx", args, {
  stdio: "inherit",
  timeout: runBlueprintOnly ? Number(process.env.PLAYGROUND_TIMEOUT_MS || 120000) : undefined
});

if (result.error?.code === "ETIMEDOUT") {
  console.error("Playground Blueprint smoke test timed out. Use wp-env for authoritative local verification on this host.");
  process.exit(124);
}

process.exit(result.status ?? 1);
