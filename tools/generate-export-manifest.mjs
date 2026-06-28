import crypto from "node:crypto";
import fs from "node:fs";
import path from "node:path";

const root = process.cwd();
const generatedAt = new Date().toISOString();
const outputDir = path.join(root, "data/lmhg/export");
const manifestPath = path.join(outputDir, "codex-cloud-export-manifest.json");
const reportPath = path.join(root, "docs/export-bundle-manifest.md");

const roots = [
  "wp-content/themes/lmhg-block-theme",
  "wp-content/plugins/lmhg-site-core",
];
const explicitFiles = [
  "AGENTS.md",
  ".gitignore",
  "package.json",
  "package-lock.json",
  "data/lmhg/source-route-manifest.json",
  "data/lmhg/staging-snapshot/summary.json",
  "data/lmhg/staging-snapshot/routes.json",
  "data/lmhg/staging-snapshot/assets.json",
  "data/lmhg/block-migration/full-site-block-manifest.json",
  "data/lmhg/block-migration/full-site-media-manifest.json",
  "docs/codex-cloud-runtime-report.md",
  "docs/cloud-verification-workflow.md",
  "docs/full-site-block-migration-report.md",
  "docs/staging-snapshot-report.md",
  "docs/route-parity-matrix.md",
  "plan/2026-06-27-cloudflare-staging-to-wordpress-verbatim-migration-plan.md",
  "tools/generate-full-site-block-migration.mjs",
  "tools/verify-full-site-block-migration.mjs",
  "tools/generate-export-manifest.mjs",
  "tools/verify-export-manifest.mjs",
  "tools/import-codex-cloud-wordpress.sh",
  "tools/verify-codex-cloud-runtime.mjs",
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
  schemaVersion: "2026-06-28.codex-cloud-wordpress-export.v1",
  generatedAt,
  purpose: "Exportable full-site package manifest for the Codex-managed cloud WordPress runtime.",
  sourceOfTruth: "/Users/tyler-lcsw/projects/lmhg-astro-integrate",
  workingRepo: "/Users/tyler-lcsw/projects/lmhg-blockwp",
  runtimeTarget: "Codex-managed cloud WordPress environment",
  routeCount: JSON.parse(fs.readFileSync(path.join(root, "data/lmhg/block-migration/full-site-block-manifest.json"), "utf8")).routes.length,
  importCommands: [
    "WP_PATH=\"/path/to/wordpress\" bash tools/import-codex-cloud-wordpress.sh",
    "wp core is-installed",
    "wp lmhg import-manifest data/lmhg/source-route-manifest.json",
    "wp lmhg import-block-manifest data/lmhg/block-migration/full-site-block-manifest.json data/lmhg/block-migration/full-site-media-manifest.json",
    "wp export --post_type=page --dir=data/lmhg/export/runtime --filename_format=lmhg-pages.xml",
    "wp db export data/lmhg/export/runtime/lmhg-wordpress.sql",
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
  return `# Codex Cloud WordPress Export Bundle Manifest

Date: ${manifest.generatedAt}

This manifest defines the exportable source package for the full LMHG WordPress
transition. It does not contain secrets. A Codex-managed cloud WordPress runtime
can use this package to install the theme and plugin, import the route manifest,
import the editable full-site block manifest, sideload media, and then export
runtime content/database artifacts.

## Operating Model

- Source of truth: \`${manifest.sourceOfTruth}\` (read-only)
- Working repo: \`${manifest.workingRepo}\`
- Runtime target: ${manifest.runtimeTarget}
- Routes: ${manifest.routeCount}
- Staging controls: noindex/noarchive remain active until live use is approved.

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
