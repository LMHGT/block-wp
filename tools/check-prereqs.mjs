import { spawnSync } from "node:child_process";
import fs from "node:fs";

const strict = process.argv.includes("--strict");

function commandExists(command) {
  return spawnSync("bash", ["-lc", `command -v ${command}`], { encoding: "utf8" }).status === 0;
}

function commandOutput(command, args) {
  const result = spawnSync(command, args, { encoding: "utf8" });
  return { ok: result.status === 0, output: `${result.stdout || ""}${result.stderr || ""}`.trim() };
}

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

const checks = [];
checks.push({
  name: "Node.js",
  ok: isAtLeast(parseVersion(process.version), [20, 18, 0]),
  detail: `${process.version}; required >=20.18.0 for this WordPress proof track`
});

const npm = commandOutput("npm", ["--version"]);
checks.push({
  name: "npm",
  ok: npm.ok && isAtLeast(parseVersion(npm.output), [10, 2, 3]),
  detail: npm.ok ? `${npm.output}; required >=10.2.3` : "not found"
});

const php = commandOutput("php", ["--version"]);
checks.push({ name: "PHP", ok: php.ok, detail: php.ok ? php.output.split("\n")[0] : "not found" });

const wp = commandOutput("wp", ["--info"]);
checks.push({ name: "WP-CLI", ok: wp.ok, detail: wp.ok ? "available" : "not found" });

const dockerInstalled = commandExists("docker");
const dockerInfo = dockerInstalled ? commandOutput("docker", ["info"]) : { ok: false, output: "not found" };
checks.push({
  name: "Docker",
  ok: dockerInstalled && dockerInfo.ok,
  detail: dockerInstalled ? (dockerInfo.ok ? "daemon available" : "installed but daemon unavailable") : "not found; required for @wordpress/env"
});

const dockerCompose = dockerInstalled ? commandOutput("docker", ["compose", "version"]) : { ok: false, output: "not found" };
checks.push({
  name: "Docker Compose",
  ok: dockerCompose.ok,
  detail: dockerCompose.ok ? dockerCompose.output : "not found; required for @wordpress/env"
});

const composer = commandOutput("composer", ["--version"]);
checks.push({
  name: "Composer",
  ok: composer.ok,
  detail: composer.ok ? composer.output.split("\n")[0] : "not found; optional until PHP static analysis is added"
});

const systemChromium = commandExists("google-chrome") || commandExists("chromium") || commandExists("chromium-browser");
let playwrightChromium = false;
try {
  const { chromium } = await import("@playwright/test");
  playwrightChromium = fs.existsSync(chromium.executablePath());
} catch {
  playwrightChromium = false;
}
checks.push({
  name: "Automation browser",
  ok: systemChromium || playwrightChromium,
  detail: systemChromium ? "system Chrome/Chromium available" : playwrightChromium ? "Playwright Chromium available" : "not found; run npm run setup:browsers"
});

let missingRequired = 0;
for (const check of checks) {
  console.log(`${(check.ok ? "OK" : "MISSING").padEnd(8)} ${check.name}: ${check.detail}`);
  if (!check.ok && ["Node.js", "npm", "PHP", "WP-CLI", "Docker", "Docker Compose"].includes(check.name)) {
    missingRequired += 1;
  }
}

if (strict && missingRequired > 0) process.exit(1);
if (missingRequired > 0) {
  console.log("\nSome required runtime prerequisites are missing. Static checks can still run, but wp-env/Playground runtime verification will be blocked.");
}
