import crypto from "node:crypto";
import fs from "node:fs";
import path from "node:path";
import { chromium } from "playwright";

const root = process.cwd();
const stagingBaseUrl = "https://staging.website-production-26u.pages.dev";
const outputDir = path.join(root, "data/lmhg/block-migration");
const reportPath = path.join(root, "docs/full-site-block-migration-report.md");
const blockManifestPath = path.join(outputDir, "full-site-block-manifest.json");
const mediaManifestPath = path.join(outputDir, "full-site-media-manifest.json");
const routeManifest = readJson("data/lmhg/source-route-manifest.json");
const stagingRoutes = readJson("data/lmhg/staging-snapshot/routes.json");
const stagingAssets = readJson("data/lmhg/staging-snapshot/assets.json");
const stagingAssetByUrl = new Map(stagingAssets.map((asset) => [asset.url, asset]));
const generatedAt = new Date().toISOString();

await fs.promises.mkdir(outputDir, { recursive: true });

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1440, height: 1200 } });
const routeOutputs = [];
const assetMap = new Map();

try {
  const targetRoutes = stagingRoutes
    .filter((route) => route.liveStatus === 200)
    .map((route) => route.url)
    .sort((a, b) => a.localeCompare(b));

  for (const route of targetRoutes) {
    const stagingEntry = stagingRoutes.find((entry) => entry.url === route);
    const routeEntry = routeManifest.routes.find((entry) => entry.url === route);
    if (!stagingEntry) throw new Error(`Route ${route} is not present in the staging snapshot.`);
    if (!routeEntry) throw new Error(`Route ${route} is not present in the source route manifest.`);

    const source = await loadRoute(page, stagingEntry, route);
    const extraction = await page.evaluate(extractFullPage, {
      route,
      stagingBaseUrl,
    });
    const canonicalBodyText = bodyVisibleText(source.html);
    const bodyForBlocks = removeLeadingSkipLinkText(canonicalBodyText);
    const canonicalFullText = cleanText([stagingEntry.title || extraction.title, "Skip to content", bodyForBlocks].filter(Boolean).join(" "));
    const canonicalTextBlocks = canonicalTextItems(bodyForBlocks, stagingEntry.h1 || extraction.h1);

    const blocks = [];
    const postContent = [];
    let order = 0;

    for (const item of [...canonicalTextBlocks, ...extraction.items.filter((item) => item.kind === "image")]) {
      const block = toBlock(item, route, order);
      if (!block) continue;

      order += 1;
      blocks.push(block.entry);
      postContent.push(block.content);

      if (block.asset) {
        const existing = assetMap.get(block.asset.assetId);
        if (existing) {
          existing.routeUsage.push({ route, blockId: block.entry.blockId });
        } else {
          assetMap.set(block.asset.assetId, {
            ...block.asset,
            routeUsage: [{ route, blockId: block.entry.blockId }],
          });
        }
      }
    }

    routeOutputs.push({
      url: route,
      title: stagingEntry.title || extraction.title,
      h1: stagingEntry.h1 || extraction.h1,
      metaDescription: stagingEntry.metaDescription || "",
      sourceMode: source.mode,
      sourceHtmlArtifactPath: stagingEntry.htmlArtifactPath ?? "",
      sourceRouteTextHash: stagingEntry.visibleTextHash ?? "",
      generatedTextHash: hashText(canonicalFullText),
      generatedTextMatchesSource: hashText(canonicalFullText) === (stagingEntry.visibleTextHash ?? ""),
      visibleTextSample: canonicalFullText.slice(0, 500),
      routeManifest: {
        pageFamily: routeEntry.pageFamily ?? "",
        templateFamily: routeEntry.templateFamily ?? "",
        migrationStatus: routeEntry.migrationStatus ?? "",
        sourceFile: routeEntry.sourceFile ?? "",
        implementationTarget: routeEntry.implementationTarget ?? "",
      },
      postContent: postContent.join("\n"),
      blocks,
      blockCount: blocks.length,
    });
  }
} finally {
  await browser.close();
}

const assets = Array.from(assetMap.values()).sort((a, b) => a.assetId.localeCompare(b.assetId));
const blockManifest = {
  schemaVersion: "2026-06-27.full-site-editable-blocks.v1",
  generatedAt,
  source: {
    stagingBaseUrl,
    routeManifestPath: "data/lmhg/source-route-manifest.json",
    stagingSnapshotPath: "data/lmhg/staging-snapshot/routes.json",
    mode: "local-artifact-preferred",
  },
  importContract: {
    importerCommand: "wp lmhg import-block-manifest data/lmhg/block-migration/full-site-block-manifest.json data/lmhg/block-migration/full-site-media-manifest.json",
    postContentField: "postContent",
    routeIdentityField: "url",
    mediaManifestPath: "data/lmhg/block-migration/full-site-media-manifest.json",
    requiredPrecondition: "Run wp lmhg import-manifest data/lmhg/source-route-manifest.json before importing block content.",
    noLocalRuntimeRequired: true,
  },
  targetRoutes: routeOutputs.map((route) => route.url),
  routes: routeOutputs,
};

const mediaManifest = {
  schemaVersion: "2026-06-27.full-site-editable-media.v1",
  generatedAt,
  source: {
    stagingBaseUrl,
    mode: "local-artifact-preferred",
  },
  assets,
};

await writeJson(blockManifestPath, blockManifest);
await writeJson(mediaManifestPath, mediaManifest);
await fs.promises.writeFile(reportPath, renderReport(blockManifest, mediaManifest), "utf8");

const mismatchedRoutes = routeOutputs.filter((route) => !route.generatedTextMatchesSource);
console.log(JSON.stringify({
  generatedAt,
  routes: routeOutputs.length,
  blocks: routeOutputs.reduce((sum, route) => sum + route.blockCount, 0),
  assets: assets.length,
  generatedTextMismatches: mismatchedRoutes.length,
  blockManifestPath: path.relative(root, blockManifestPath),
  mediaManifestPath: path.relative(root, mediaManifestPath),
  reportPath: path.relative(root, reportPath),
}, null, 2));

if (mismatchedRoutes.length > 0) {
  console.error("Full-site block migration text extraction mismatches:");
  for (const route of mismatchedRoutes.slice(0, 20)) {
    console.error(`- ${route.url}: generated ${route.generatedTextHash}, source ${route.sourceRouteTextHash}`);
  }
  if (mismatchedRoutes.length > 20) {
    console.error(`- ... ${mismatchedRoutes.length - 20} more`);
  }
  process.exit(1);
}

function readJson(relativePath) {
  return JSON.parse(fs.readFileSync(path.join(root, relativePath), "utf8"));
}

async function writeJson(filePath, payload) {
  await fs.promises.writeFile(filePath, `${JSON.stringify(payload, null, 2)}\n`, "utf8");
}

async function loadRoute(page, stagingEntry, route) {
  if (stagingEntry.htmlArtifactPath) {
    const artifactPath = path.join(root, stagingEntry.htmlArtifactPath);
    if (fs.existsSync(artifactPath)) {
      const html = await fs.promises.readFile(artifactPath, "utf8");
      await page.setContent(html, { waitUntil: "domcontentloaded" });
      await page.evaluate((base) => {
        const baseElement = document.createElement("base");
        baseElement.href = base;
        document.head.prepend(baseElement);
      }, new URL(route, stagingBaseUrl).toString());
      return { mode: "local-html-artifact", html };
    }
  }

  const response = await page.goto(new URL(route, stagingBaseUrl).toString(), { waitUntil: "networkidle" });
  return { mode: "live-staging-fetch", html: await response.text() };
}

function extractFullPage({ route, stagingBaseUrl }) {
  const cleanText = (text) => String(text || "").replace(/\s+/g, " ").trim();
  const isExcluded = (node) => {
    const element = node.nodeType === Node.ELEMENT_NODE ? node : node.parentElement;
    return Boolean(element?.closest("script,style,noscript,template"));
  };
  const absoluteUrl = (value) => {
    if (!value || value.startsWith("data:")) return value || "";
    try {
      return new URL(value, stagingBaseUrl).toString();
    } catch {
      return value;
    }
  };
  const selectorForElement = (element) => {
    const parts = [];
    let cursor = element;
    while (cursor && cursor !== document.body && parts.length < 6) {
      const tag = cursor.tagName.toLowerCase();
      const siblings = Array.from(cursor.parentElement?.children ?? []).filter((sibling) => sibling.tagName === cursor.tagName);
      const nth = siblings.length > 1 ? `:nth-of-type(${siblings.indexOf(cursor) + 1})` : "";
      parts.unshift(`${tag}${nth}`);
      cursor = cursor.parentElement;
    }
    return `body ${parts.join(" > ")}`;
  };
  const sourceForElement = (element, index) => {
    const editField = element.getAttribute?.("data-lmhg-edit-field");
    if (editField) return editField;
    return `${element.tagName.toLowerCase()}[${index}]`;
  };

  const items = [];
  let textIndex = 0;
  let imageIndex = 0;

  const visit = (node) => {
    if (isExcluded(node)) return;

    if (node.nodeType === Node.TEXT_NODE) {
      const text = cleanText(node.textContent);
      if (!text) return;

      const parent = node.parentElement;
      const heading = parent?.closest("h1,h2,h3,h4,h5,h6");
      const element = heading || parent || document.body;
      items.push({
        kind: heading ? "heading" : "paragraph",
        level: heading ? Number(heading.tagName.slice(1)) : 0,
        text,
        sourceField: sourceForElement(element, textIndex),
        selector: selectorForElement(element),
      });
      textIndex += 1;
      return;
    }

    if (node.nodeType !== Node.ELEMENT_NODE) return;
    const element = node;
    const tag = element.tagName.toLowerCase();

    if (tag === "img") {
      const src = absoluteUrl(element.getAttribute("src"));
      if (src) {
        items.push({
          kind: "image",
          src,
          srcset: cleanText(element.getAttribute("srcset")),
          alt: cleanText(element.getAttribute("alt")),
          sourceField: sourceForElement(element, imageIndex),
          selector: selectorForElement(element),
        });
        imageIndex += 1;
      }
      return;
    }

    for (const child of Array.from(element.childNodes)) {
      visit(child);
    }
  };

  visit(document.body);

  return {
    title: cleanText(document.title),
    h1: cleanText(document.body.querySelector("h1")?.textContent),
    items,
    route,
  };
}

function canonicalTextItems(bodyText, h1) {
  const items = [];
  const cleanH1 = cleanText(h1);
  const h1Index = cleanH1 ? bodyText.indexOf(cleanH1) : -1;

  if (h1Index >= 0) {
    items.push(...paragraphItems(bodyText.slice(0, h1Index), "canonical.body.beforeH1"));
    items.push({
      kind: "heading",
      level: 1,
      text: cleanH1,
      sourceField: "canonical.body.h1",
      selector: "body h1",
    });
    items.push(...paragraphItems(bodyText.slice(h1Index + cleanH1.length), "canonical.body.afterH1"));
    return items;
  }

  return paragraphItems(bodyText, "canonical.body");
}

function paragraphItems(text, sourcePrefix) {
  return splitOnWordBoundaries(cleanText(text), 700).map((chunk, index) => ({
    kind: "paragraph",
    level: 0,
    text: chunk,
    sourceField: `${sourcePrefix}[${index}]`,
    selector: "body",
  }));
}

function splitOnWordBoundaries(text, maxLength) {
  if (!text) return [];
  const words = text.split(/\s+/).filter(Boolean);
  const chunks = [];
  let current = "";

  for (const word of words) {
    if (!current) {
      current = word;
      continue;
    }
    if (`${current} ${word}`.length > maxLength) {
      chunks.push(current);
      current = word;
      continue;
    }
    current += ` ${word}`;
  }

  if (current) chunks.push(current);
  return chunks;
}

function bodyVisibleText(html) {
	const body = String(html || "").match(/<body[^>]*>([\s\S]*?)<\/body>/i)?.[1] || "";
	return stripVisibleText(body);
}

function removeLeadingSkipLinkText(text) {
	const clean = cleanText(text);
	return clean.startsWith("Skip to content ") ? clean.slice("Skip to content ".length) : clean;
}

function stripVisibleText(html) {
  return decodeHtml(html)
    .replace(/<script[\s\S]*?<\/script>/gi, " ")
    .replace(/<style[\s\S]*?<\/style>/gi, " ")
    .replace(/<noscript[\s\S]*?<\/noscript>/gi, " ")
    .replace(/<!--[\s\S]*?-->/g, " ")
    .replace(/<[^>]+>/g, " ")
    .replace(/\s+/g, " ")
    .trim();
}

function decodeHtml(value) {
  return String(value || "")
    .replace(/&#(\d+);/g, (_, code) => String.fromCharCode(Number.parseInt(code, 10)))
    .replace(/&#x([0-9a-f]+);/gi, (_, code) => String.fromCharCode(Number.parseInt(code, 16)))
    .replace(/&amp;/g, "&")
    .replace(/&quot;/g, '"')
    .replace(/&#039;/g, "'")
    .replace(/&apos;/g, "'")
    .replace(/&lt;/g, "<")
    .replace(/&gt;/g, " ");
}

function cleanText(value) {
  return String(value || "").replace(/\s+/g, " ").trim();
}

function toBlock(item, route, order) {
  const blockId = `${route.replace(/[^a-z0-9]+/gi, "-").replace(/^-|-$/g, "") || "home"}-${String(order + 1).padStart(4, "0")}`;
  const sourceField = item.sourceField || `dom[${order}]`;
  const metadata = { name: `LMHG ${route} ${sourceField}` };
  const className = `lmhg-migrated-block lmhg-migrated-block--${item.kind}`;
  const baseEntry = {
    blockId,
    order,
    kind: item.kind,
    sourceField,
    sourceSelector: item.selector ?? "",
  };

  if (item.kind === "heading") {
    const attrs = blockAttrs({ level: item.level, className, metadata });
    return {
      entry: { ...baseEntry, coreBlockName: "core/heading", text: item.text, textHash: hashText(item.text), level: item.level },
      content: `<!-- wp:heading ${attrs} --><h${item.level} class="wp-block-heading ${className}">${escapeHtml(item.text)}</h${item.level}><!-- /wp:heading -->`,
    };
  }

  if (item.kind === "paragraph") {
    const attrs = blockAttrs({ className, metadata });
    return {
      entry: { ...baseEntry, coreBlockName: "core/paragraph", text: item.text, textHash: hashText(item.text) },
      content: `<!-- wp:paragraph ${attrs} --><p class="${className}">${escapeHtml(item.text)}</p><!-- /wp:paragraph -->`,
    };
  }

  if (item.kind === "image") {
    const assetId = `asset-${hashText(item.src).slice(0, 12)}`;
    const asset = stagingAssetByUrl.get(item.src) || {};
    const attrs = blockAttrs({ sizeSlug: "large", linkDestination: "none", className, metadata });
    return {
      entry: { ...baseEntry, coreBlockName: "core/image", assetId, alt: item.alt, sourceUrl: item.src },
      asset: {
        assetId,
        kind: "image",
        sourceUrl: item.src,
        srcset: item.srcset,
        alt: item.alt,
        sourceHash: hashText(item.src),
        artifactPath: asset.artifactPath || "",
        contentHash: asset.contentHash || "",
        contentType: asset.contentType || "",
      },
      content: `<!-- wp:image ${attrs} --><figure class="wp-block-image size-large ${className}"><img src="${escapeAttribute(item.src)}" alt="${escapeAttribute(item.alt)}"/></figure><!-- /wp:image -->`,
    };
  }

  return null;
}

function blockAttrs(attrs) {
  return JSON.stringify(attrs).replace(/--/g, "\\u002d\\u002d");
}

function hashText(value) {
  return crypto.createHash("sha256").update(String(value)).digest("hex");
}

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;");
}

function escapeAttribute(value) {
  return escapeHtml(value).replace(/"/g, "&quot;");
}

function renderReport(blockManifest, mediaManifest) {
  const rows = blockManifest.routes.map((route) => `| ${route.url} | ${route.sourceMode} | ${route.blockCount} | ${route.blocks.filter((block) => block.assetId).length} | ${route.generatedTextMatchesSource ? "yes" : "no"} | ${route.h1.replace(/\|/g, "\\|")} |`).join("\n");
  const assetRows = mediaManifest.assets.map((asset) => `| ${asset.assetId} | ${asset.kind} | ${asset.routeUsage.length} | ${asset.sourceUrl || "(inline)"} |`).join("\n");
  return `# Full Site Editable Block Migration

Date: ${blockManifest.generatedAt}

Source: ${blockManifest.source.stagingBaseUrl}

This manifest converts every current \`200\` Cloudflare staging route into
serialized editable Gutenberg content for the no-gap WordPress transition.

## Import Contract

\`\`\`bash
wp lmhg import-manifest data/lmhg/source-route-manifest.json
wp lmhg import-block-manifest data/lmhg/block-migration/full-site-block-manifest.json data/lmhg/block-migration/full-site-media-manifest.json
\`\`\`

## Routes

| Route | Source mode | Blocks | Asset blocks | Text hash matches source | H1 |
|---|---:|---:|---:|---:|---|
${rows}

## Media And Visual Asset Correlation

| Asset ID | Kind | Route usages | Source URL |
|---|---:|---:|---|
${assetRows || "| none | none | 0 | none |"}
`;
}
