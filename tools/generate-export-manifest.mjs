import crypto from "node:crypto";
import fs from "node:fs";
import path from "node:path";

const root = process.cwd();
const generatedAt = new Date().toISOString();
const outputDir = path.join(root, "data/lmhg/export");
const manifestPath = path.join(outputDir, "first-slice-export-manifest.json");
const reportPath = path.join(root, "docs/export-bundle-manifest.md");

const roots = [
  "wp-content/themes/lmhg-block-theme",
  "wp-content/plugins/lmhg-site-core",
];
const explicitFiles = [
  "AGENTS.md",
  "package.json",
  "package-lock.json",
  "data/lmhg/source-route-manifest.json",
  "data/lmhg/staging-snapshot/summary.json",
  "data/lmhg/staging-snapshot/routes.json",
  "data/lmhg/staging-snapshot/assets.json",
  "data/lmhg/block-migration/first-slice-block-manifest.json",
  "data/lmhg/block-migration/first-slice-media-manifest.json",
  "docs/block-migration-slice-report.md",
  "docs/cloud-verification-workflow.md",
  "docs/staging-snapshot-report.md",
  "docs/route-parity-matrix.md",
  "plan/2026-06-27-cloud-run-editable-gutenberg-migration-pipeline.md",
  "tools/generate-block-migration-slice.mjs",
  "tools/verify-block-migration-slice.mjs",
  "tools/generate-export-manifest.mjs",
  "tools/verify-export-manifest.mjs",
];

await fs.promises.mkdir(outputDir, { recursive: true });

const files = Array.from(new Set([
  ...explicitFiles,
  ...roots.flatMap((dir) => walk(dir)),
]))
  .filter((file) => fs.existsSync(path.join(root, file)))
  .sort((a, b) => a.localeCompare(b));

const entries = files.map((file) => {
  const absolutePath = path.join(root, file);
  const buffer = fs.readFileSync(absolutePath);
  return {
    path: file,
    bytes: buffer.length,
    sha256: crypto.createHash("sha256").update(buffer).digest("hex"),
  };
});

const manifest = {
  schemaVersion: "2026-06-27.first-slice-export.v1",
  generatedAt,
  purpose: "Exportable source package manifest for the first editable Gutenberg migration slice.",
  routes: ["/compliance/", "/privacy-policy/", "/terms-of-use/", "/individual-counseling/"],
  importCommands: [
    "wp lmhg import-manifest data/lmhg/source-route-manifest.json",
    "wp lmhg import-block-manifest data/lmhg/block-migration/first-slice-block-manifest.json data/lmhg/block-migration/first-slice-media-manifest.json",
  ],
  files: entries,
};

await fs.promises.writeFile(manifestPath, `${JSON.stringify(manifest, null, 2)}\n`, "utf8");
await fs.promises.writeFile(reportPath, renderReport(manifest), "utf8");

console.log(JSON.stringify({
  generatedAt,
  files: entries.length,
  bytes: entries.reduce((sum, entry) => sum + entry.bytes, 0),
  manifestPath: path.relative(root, manifestPath),
  reportPath: path.relative(root, reportPath),
}, null, 2));

function walk(relativeDir) {
  const absoluteDir = path.join(root, relativeDir);
  if (!fs.existsSync(absoluteDir)) return [];

  const entries = [];
  for (const item of fs.readdirSync(absoluteDir, { withFileTypes: true })) {
    const relativePath = path.join(relativeDir, item.name);
    if (item.isDirectory()) {
      entries.push(...walk(relativePath));
    } else if (item.isFile()) {
      entries.push(relativePath);
    }
  }
  return entries;
}

function renderReport(manifest) {
  const rows = manifest.files.map((entry) => `| ${entry.path} | ${entry.bytes} | ${entry.sha256.slice(0, 12)} |`).join("\n");
  return `# First Slice Export Bundle Manifest

Date: ${manifest.generatedAt}

This manifest defines the exportable source package for the first editable
Gutenberg migration slice. It is not a database dump and does not contain
secrets. A cloud WordPress runtime can use this package to install the theme and
plugin, import the route manifest, import the editable block manifest, sideload
the media manifest, and verify the four first-review routes.

## Routes

${manifest.routes.map((route) => `- \`${route}\``).join("\n")}

## Import Commands

\`\`\`bash
${manifest.importCommands.join("\n")}
\`\`\`

## Files

| Path | Bytes | SHA-256 |
|---|---:|---:|
${rows}
`;
}
