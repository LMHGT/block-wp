import crypto from "node:crypto";
import fs from "node:fs";
import path from "node:path";
import { execFileSync } from "node:child_process";

const root = process.cwd();
const sourceRoot = process.env.ASTRO_SOURCE_ROOT || "/Users/tyler-lcsw/projects/lmhg-astro-integrate";
const outRoot = path.join(root, "data/lmhg/astro-reference");
const core30Out = path.join(outRoot, "core30");
const redirectsOut = path.join(outRoot, "redirects");
const reportPath = path.join(root, "docs/astro-reference-intake.md");

const core30Files = [
  ["core-30/CORE30_ANALYSIS.md", "CORE30_ANALYSIS.md"],
  ["core-30/CORE30_IMPLEMENTATION.md", "CORE30_IMPLEMENTATION.md"],
  ["docs/seo/core30-keyword-architecture.md", "core30-keyword-architecture.md"],
  ["docs/seo/core30-keyword-architecture.json", "core30-keyword-architecture.json"],
  ["src/data/core30.ts", "source-core30.ts.txt"],
];

const redirectFiles = [
  ["public/_redirects", "public-redirects.txt"],
  ["REDIRECTS.md", "REDIRECTS.md"],
];

assertDirectory(sourceRoot, "Astro source root");
await fs.promises.mkdir(core30Out, { recursive: true });
await fs.promises.mkdir(redirectsOut, { recursive: true });
await fs.promises.mkdir(path.dirname(reportPath), { recursive: true });

const sourceState = getSourceState(sourceRoot);
const copiedFiles = [];

for (const [relativeSource, outputName] of core30Files) {
  copiedFiles.push(await copyReferenceFile(relativeSource, path.join(core30Out, outputName)));
}

for (const [relativeSource, outputName] of redirectFiles) {
  copiedFiles.push(await copyReferenceFile(relativeSource, path.join(redirectsOut, outputName)));
}

const redirectsText = fs.readFileSync(path.join(sourceRoot, "public/_redirects"), "utf8");
const redirects = parseRedirects(redirectsText);
const redirectBundle = {
  schemaVersion: 1,
  generatedAt: new Date().toISOString(),
  source: {
    sourceRoot,
    sourceBranch: sourceState.branch,
    sourceHead: sourceState.head,
    file: "public/_redirects",
    sha256: sha256(redirectsText),
  },
  importTarget: {
    currentStage: "rank-math-candidate",
    note: "Rank Math is not installed in the 8093 runtime yet; these rows are staged as candidate inputs only.",
  },
  redirects,
};

await fs.promises.writeFile(
  path.join(redirectsOut, "redirects.json"),
  `${JSON.stringify(redirectBundle, null, 2)}\n`,
  "utf8",
);

await fs.promises.writeFile(
  path.join(redirectsOut, "rank-math-redirect-candidates.csv"),
  renderRedirectCsv(redirects),
  "utf8",
);

const core30Architecture = JSON.parse(
  fs.readFileSync(path.join(sourceRoot, "docs/seo/core30-keyword-architecture.json"), "utf8"),
);
const summary = {
  schemaVersion: 1,
  generatedAt: redirectBundle.generatedAt,
  source: {
    sourceRoot,
    sourceBranch: sourceState.branch,
    sourceHead: sourceState.head,
  },
  runtimeAuthority: {
    url: "http://100.70.222.25:8093",
    projectSlug: "wordpress-2026",
    note: "8093 is the active WordPress authority; Astro files are reference inputs only.",
  },
  core30: {
    documentVersion: core30Architecture.document_version ?? null,
    updatedAt: core30Architecture.updated_at ?? null,
    categories: Array.isArray(core30Architecture.categories) ? core30Architecture.categories.length : 0,
    specialtyPages: Array.isArray(core30Architecture.specialty_pages) ? core30Architecture.specialty_pages.length : 0,
    articleBacklog: Array.isArray(core30Architecture.article_backlog) ? core30Architecture.article_backlog.length : 0,
    copiedFiles: copiedFiles.filter((file) => file.group === "core30").map((file) => file.output),
  },
  redirects: {
    total: redirects.length,
    permanent: redirects.filter((redirect) => redirect.statusCode === 301).length,
    candidateJson: "data/lmhg/astro-reference/redirects/redirects.json",
    rankMathCsv: "data/lmhg/astro-reference/redirects/rank-math-redirect-candidates.csv",
  },
  files: copiedFiles,
};

await fs.promises.writeFile(
  path.join(outRoot, "summary.json"),
  `${JSON.stringify(summary, null, 2)}\n`,
  "utf8",
);

await fs.promises.writeFile(reportPath, renderReport(summary), "utf8");

console.log(JSON.stringify({
  sourceRoot,
  sourceBranch: sourceState.branch,
  sourceHead: sourceState.head,
  core30Files: summary.core30.copiedFiles.length,
  redirects: redirects.length,
  reportPath: path.relative(root, reportPath),
}, null, 2));

async function copyReferenceFile(relativeSource, outputPath) {
  const absoluteSource = path.join(sourceRoot, relativeSource);
  assertFile(absoluteSource);
  await fs.promises.copyFile(absoluteSource, outputPath);
  const content = fs.readFileSync(absoluteSource, "utf8");
  return {
    group: relativeSource.includes("redirect") || relativeSource === "REDIRECTS.md" ? "redirects" : "core30",
    source: relativeSource,
    output: path.relative(root, outputPath),
    bytes: Buffer.byteLength(content),
    sha256: sha256(content),
  };
}

function parseRedirects(text) {
  const redirects = [];
  let section = "";

  text.split(/\r?\n/).forEach((line, index) => {
    const trimmed = line.trim();
    if (!trimmed) return;

    if (trimmed.startsWith("#")) {
      const label = trimmed.replace(/^#+\s*/, "").trim();
      if (label && !/^=+$/.test(label) && !/^Netlify Redirects/i.test(label) && !/^Updated:/i.test(label)) {
        section = label;
      }
      return;
    }

    const parts = trimmed.split(/\s+/);
    if (parts.length < 2) return;

    const source = parts[0];
    const target = parts[1];
    const statusCode = Number.parseInt(parts[2] || "301", 10);

    redirects.push({
      source,
      target,
      statusCode: Number.isFinite(statusCode) ? statusCode : 301,
      rankMathMatchType: "exact",
      rankMathDestinationType: "url",
      section,
      sourceLine: index + 1,
    });
  });

  return redirects;
}

function renderRedirectCsv(redirects) {
  const header = [
    "source",
    "target",
    "status_code",
    "match_type",
    "destination_type",
    "source_section",
    "source_line",
  ];
  const rows = redirects.map((redirect) => [
    redirect.source,
    redirect.target,
    redirect.statusCode,
    redirect.rankMathMatchType,
    redirect.rankMathDestinationType,
    redirect.section,
    redirect.sourceLine,
  ]);
  return `${[header, ...rows].map((row) => row.map(csvCell).join(",")).join("\n")}\n`;
}

function renderReport(summary) {
  const fileRows = summary.files
    .map((file) => `| ${file.group} | \`${file.source}\` | \`${file.output}\` | ${file.bytes} | \`${file.sha256.slice(0, 12)}\` |`)
    .join("\n");

  return `# Astro Reference Intake For WordPress 2026

Generated: ${summary.generatedAt}

Runtime authority: ${summary.runtimeAuthority.url} (${summary.runtimeAuthority.projectSlug})

Astro source remains read-only. These files are imported as reference inputs for
the WordPress 2026 project, not as executable Astro code or old block markup.

## Source

- Source root: \`${summary.source.sourceRoot}\`
- Source branch: \`${summary.source.sourceBranch || "(unknown)"}\`
- Source HEAD: \`${summary.source.sourceHead || "(unknown)"}\`

## Core30

- Core30 document version: ${summary.core30.documentVersion ?? "unknown"}
- Core30 updated at: ${summary.core30.updatedAt ?? "unknown"}
- Categories recorded: ${summary.core30.categories}
- Specialty pages recorded: ${summary.core30.specialtyPages}
- Article backlog items: ${summary.core30.articleBacklog}

## Redirects

- Parsed redirects: ${summary.redirects.total}
- Permanent redirects: ${summary.redirects.permanent}
- Rank Math candidate CSV: \`${summary.redirects.rankMathCsv}\`
- JSON candidate bundle: \`${summary.redirects.candidateJson}\`

Rank Math is not installed yet in the 8093 runtime. These redirect rows are
staged for later plugin import or transformation.

## Copied Files

| Group | Source | Output | Bytes | SHA-256 |
|---|---|---|---:|---|
${fileRows}
`;
}

function getSourceState(directory) {
  return {
    branch: git(directory, ["branch", "--show-current"]),
    head: git(directory, ["rev-parse", "HEAD"]),
  };
}

function git(directory, args) {
  try {
    return execFileSync("git", args, { cwd: directory, encoding: "utf8", stdio: ["ignore", "pipe", "ignore"] }).trim();
  } catch {
    return "";
  }
}

function sha256(content) {
  return crypto.createHash("sha256").update(content).digest("hex");
}

function csvCell(value) {
  const text = String(value ?? "");
  return /[",\n]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text;
}

function assertDirectory(directory, label) {
  if (!fs.existsSync(directory) || !fs.statSync(directory).isDirectory()) {
    throw new Error(`${label} not found: ${directory}`);
  }
}

function assertFile(file) {
  if (!fs.existsSync(file) || !fs.statSync(file).isFile()) {
    throw new Error(`Required source file not found: ${file}`);
  }
}
