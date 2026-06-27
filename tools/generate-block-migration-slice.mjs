import crypto from "node:crypto";
import fs from "node:fs";
import path from "node:path";
import { chromium } from "playwright";

const root = process.cwd();
const stagingBaseUrl = "https://staging.website-production-26u.pages.dev";
const defaultRoutes = ["/compliance/", "/privacy-policy/", "/terms-of-use/", "/individual-counseling/"];
const args = new Set(process.argv.slice(2));
const useLive = args.has("--live");
const routeArgs = process.argv
  .slice(2)
  .filter((arg) => arg.startsWith("/") || arg === "home")
  .map((arg) => (arg === "home" ? "/" : normalizeRoute(arg)));
const targetRoutes = routeArgs.length > 0 ? routeArgs : defaultRoutes;

const outputDir = path.join(root, "data/lmhg/block-migration");
const reportPath = path.join(root, "docs/block-migration-slice-report.md");
const blockManifestPath = path.join(outputDir, "first-slice-block-manifest.json");
const mediaManifestPath = path.join(outputDir, "first-slice-media-manifest.json");
const routeManifest = readJson("data/lmhg/source-route-manifest.json");
const stagingRoutes = readJson("data/lmhg/staging-snapshot/routes.json");
const generatedAt = new Date().toISOString();

await fs.promises.mkdir(outputDir, { recursive: true });

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1440, height: 1200 } });
const routeOutputs = [];
const assetMap = new Map();

try {
  for (const route of targetRoutes) {
    const stagingEntry = stagingRoutes.find((entry) => entry.url === route);
    if (!stagingEntry) throw new Error(`Route ${route} is not present in the staging snapshot.`);

    const routeEntry = routeManifest.routes.find((entry) => entry.url === route);
    if (!routeEntry) throw new Error(`Route ${route} is not present in the source route manifest.`);

    const sourceMode = await loadRoute(page, stagingEntry, route);
    const extraction = await page.evaluate(extractPage, {
      route,
      stagingBaseUrl,
    });

    const blocks = [];
    const postContent = [];
    let order = 0;

    for (const item of extraction.items) {
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

    const visibleText = extraction.visibleText.replace(/\s+/g, " ").trim();
    routeOutputs.push({
      url: route,
      title: extraction.title,
      h1: extraction.h1,
      sourceMode,
      sourceHtmlArtifactPath: stagingEntry.htmlArtifactPath ?? "",
      sourceRouteTextHash: stagingEntry.visibleTextHash ?? "",
      visibleTextHash: hashText(visibleText),
      visibleTextSample: visibleText.slice(0, 500),
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
  schemaVersion: "2026-06-27.editable-block-slice.v1",
  generatedAt,
  source: {
    stagingBaseUrl,
    routeManifestPath: "data/lmhg/source-route-manifest.json",
    stagingSnapshotPath: "data/lmhg/staging-snapshot/routes.json",
    mode: useLive ? "live-staging" : "local-artifact-preferred",
  },
  importContract: {
    importerCommand: "wp lmhg import-block-manifest data/lmhg/block-migration/first-slice-block-manifest.json data/lmhg/block-migration/first-slice-media-manifest.json",
    postContentField: "postContent",
    routeIdentityField: "url",
    mediaManifestPath: "data/lmhg/block-migration/first-slice-media-manifest.json",
    requiredPrecondition: "Run wp lmhg import-manifest data/lmhg/source-route-manifest.json before importing block content.",
    noLocalRuntimeRequired: true,
  },
  targetRoutes,
  routes: routeOutputs,
};

const mediaManifest = {
  schemaVersion: "2026-06-27.editable-block-media.v1",
  generatedAt,
  source: {
    stagingBaseUrl,
    mode: useLive ? "live-staging" : "local-artifact-preferred",
  },
  assets,
};

await writeJson(blockManifestPath, blockManifest);
await writeJson(mediaManifestPath, mediaManifest);
await fs.promises.writeFile(reportPath, renderReport(blockManifest, mediaManifest), "utf8");

console.log(JSON.stringify({
  generatedAt,
  routes: routeOutputs.length,
  blocks: routeOutputs.reduce((sum, route) => sum + route.blockCount, 0),
  assets: assets.length,
  blockManifestPath: path.relative(root, blockManifestPath),
  mediaManifestPath: path.relative(root, mediaManifestPath),
  reportPath: path.relative(root, reportPath),
}, null, 2));

function readJson(relativePath) {
  return JSON.parse(fs.readFileSync(path.join(root, relativePath), "utf8"));
}

async function writeJson(filePath, payload) {
  await fs.promises.writeFile(filePath, `${JSON.stringify(payload, null, 2)}\n`, "utf8");
}

function normalizeRoute(route) {
  if (route === "/") return "/";
  const normalized = `/${route.replace(/^\/+|\/+$/g, "")}/`;
  return normalized === "//" ? "/" : normalized;
}

async function loadRoute(page, stagingEntry, route) {
  if (!useLive && stagingEntry.htmlArtifactPath) {
    const artifactPath = path.join(root, stagingEntry.htmlArtifactPath);
    if (fs.existsSync(artifactPath)) {
      const html = await fs.promises.readFile(artifactPath, "utf8");
      await page.setContent(html, { waitUntil: "domcontentloaded" });
      await page.evaluate((base) => {
        const baseElement = document.createElement("base");
        baseElement.href = base;
        document.head.prepend(baseElement);
      }, new URL(route, stagingBaseUrl).toString());
      return "local-html-artifact";
    }
  }

  await page.goto(new URL(route, stagingBaseUrl).toString(), { waitUntil: "networkidle" });
  return "live-staging-fetch";
}

function extractPage({ route, stagingBaseUrl }) {
  const main = document.querySelector("main#main") ?? document.querySelector("main");
  if (!main) throw new Error(`Route ${route} has no main element.`);

  const hiddenSelector = "[hidden], [aria-hidden='true'], script, style, template, noscript";
  const sourceForElement = (element) => {
    const editField = element.getAttribute("data-lmhg-edit-field");
    if (editField) return editField;

    const section = element.closest("[aria-label], section[class], article[class], div[class]");
    const label = section?.getAttribute("aria-label") || section?.className || element.tagName.toLowerCase();
    return String(label).replace(/\s+/g, " ").trim().slice(0, 120);
  };
  const selectorForElement = (element) => {
    const parts = [];
    let cursor = element;
    while (cursor && cursor !== main && parts.length < 5) {
      const tag = cursor.tagName.toLowerCase();
      const id = cursor.id ? `#${cursor.id}` : "";
      const className = classTokenString(cursor)
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((value) => `.${CSS.escape(value)}`)
        .join("");
      const siblings = Array.from(cursor.parentElement?.children ?? []).filter((sibling) => sibling.tagName === cursor.tagName);
      const nth = siblings.length > 1 ? `:nth-of-type(${siblings.indexOf(cursor) + 1})` : "";
      parts.unshift(`${tag}${id}${className}${nth}`);
      cursor = cursor.parentElement;
    }
    return `main ${parts.join(" > ")}`;
  };
  const cleanText = (text) => String(text || "").replace(/\s+/g, " ").trim();
  const classTokenString = (element) => {
    const className = element.className;
    if (typeof className === "string") return className;
    if (className && typeof className.baseVal === "string") return className.baseVal;
    return "";
  };
  const absoluteUrl = (value) => {
    if (!value || value.startsWith("data:")) return value || "";
    try {
      return new URL(value, stagingBaseUrl).toString();
    } catch {
      return value;
    }
  };
  const normalizeHref = (href) => {
    if (!href) return "";
    try {
      const url = new URL(href);
      if (url.origin === stagingBaseUrl) {
        return `${url.pathname}${url.search}${url.hash}`;
      }
    } catch {
      return href;
    }
    return href;
  };
  const isVisible = (element) => {
    if (!(element instanceof HTMLElement || element instanceof SVGElement)) return false;
    if (element.closest(hiddenSelector)) return false;
    const rect = element.getBoundingClientRect();
    const style = window.getComputedStyle(element);
    return rect.width > 0 && rect.height > 0 && style.display !== "none" && style.visibility !== "hidden";
  };

  const items = [];
  const consumed = new WeakSet();
  const elements = Array.from(main.querySelectorAll("h1,h2,h3,h4,p,ul,ol,img,svg[role='img'],a,details"));

  for (const element of elements) {
    if (!isVisible(element)) continue;
    if (Array.from(consumed).includes?.(element)) continue;

    const tag = element.tagName.toLowerCase();
    if (element.closest("header,footer,nav:not(.page-breadcrumbs)")) continue;

    if (/^h[1-4]$/.test(tag)) {
      const text = cleanText(element.textContent);
      if (text) items.push({ kind: "heading", level: Number(tag.slice(1)), text, sourceField: sourceForElement(element), selector: selectorForElement(element) });
      continue;
    }

    if (tag === "p") {
      const text = cleanText(element.textContent);
      if (text) items.push({ kind: "paragraph", text, sourceField: sourceForElement(element), selector: selectorForElement(element) });
      continue;
    }

    if (tag === "ul" || tag === "ol") {
      const listItems = Array.from(element.children)
        .filter((child) => child.tagName.toLowerCase() === "li")
        .map((child) => cleanText(child.textContent))
        .filter(Boolean);
      if (listItems.length > 0) {
        items.push({ kind: "list", ordered: tag === "ol", items: listItems, sourceField: sourceForElement(element), selector: selectorForElement(element) });
      }
      continue;
    }

    if (tag === "img") {
      const src = absoluteUrl(element.getAttribute("src"));
      if (!src) continue;
      const srcset = cleanText(element.getAttribute("srcset"));
      items.push({
        kind: "image",
        src,
        srcset,
        alt: cleanText(element.getAttribute("alt")),
        sourceField: sourceForElement(element),
        selector: selectorForElement(element),
      });
      continue;
    }

    if (tag === "svg") {
      const label = cleanText(element.getAttribute("aria-label") || document.getElementById(element.getAttribute("aria-labelledby") || "")?.textContent || "");
      const outerHtml = element.outerHTML;
      items.push({
        kind: "inlineSvg",
        label,
        outerHtml,
        sourceField: sourceForElement(element),
        selector: selectorForElement(element),
      });
      continue;
    }

    if (tag === "a") {
      const text = cleanText(element.textContent);
      const href = normalizeHref(absoluteUrl(element.getAttribute("href")));
      const className = String(element.className || "");
      const buttonish = /button|btn|rounded-md|cta|primary/i.test(className) || element.closest(".lower-callout,.lmhg-sw-hero");
      if (text && href && href !== "#" && buttonish) {
        items.push({ kind: "button", text, href, sourceField: sourceForElement(element), selector: selectorForElement(element) });
      }
      continue;
    }

    if (tag === "details") {
      const summary = cleanText(element.querySelector("summary")?.textContent);
      const text = cleanText(Array.from(element.children).filter((child) => child.tagName.toLowerCase() !== "summary").map((child) => child.textContent).join(" "));
      if (summary || text) {
        items.push({ kind: "details", summary, text, sourceField: sourceForElement(element), selector: selectorForElement(element) });
      }
    }
  }

  return {
    title: document.title,
    h1: cleanText(main.querySelector("h1")?.textContent),
    visibleText: cleanText(main.textContent),
    items,
  };
}

function toBlock(item, route, order) {
  const blockId = `${route.replace(/[^a-z0-9]+/gi, "-").replace(/^-|-$/g, "") || "home"}-${String(order + 1).padStart(3, "0")}`;
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

  if (item.kind === "list") {
    const attrs = blockAttrs({ ordered: item.ordered, className, metadata });
    const tag = item.ordered ? "ol" : "ul";
    const items = item.items.map((text) => `<li>${escapeHtml(text)}</li>`).join("");
    return {
      entry: { ...baseEntry, coreBlockName: "core/list", items: item.items, textHash: hashText(item.items.join(" ")) },
      content: `<!-- wp:list ${attrs} --><${tag} class="${className}">${items}</${tag}><!-- /wp:list -->`,
    };
  }

  if (item.kind === "button") {
    const attrs = blockAttrs({ className: "lmhg-migrated-buttons", metadata });
    const buttonAttrs = blockAttrs({ url: item.href, className, metadata });
    return {
      entry: { ...baseEntry, coreBlockName: "core/buttons/core/button", text: item.text, href: item.href, textHash: hashText(`${item.text} ${item.href}`) },
      content: `<!-- wp:buttons ${attrs} --><div class="wp-block-buttons lmhg-migrated-buttons"><!-- wp:button ${buttonAttrs} --><div class="wp-block-button ${className}"><a class="wp-block-button__link wp-element-button" href="${escapeAttribute(item.href)}">${escapeHtml(item.text)}</a></div><!-- /wp:button --></div><!-- /wp:buttons -->`,
    };
  }

  if (item.kind === "image") {
    const assetId = `asset-${hashText(item.src).slice(0, 12)}`;
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
      },
      content: `<!-- wp:image ${attrs} --><figure class="wp-block-image size-large ${className}"><img src="${escapeAttribute(item.src)}" alt="${escapeAttribute(item.alt)}"/></figure><!-- /wp:image -->`,
    };
  }

  if (item.kind === "inlineSvg") {
    const assetId = `inline-svg-${hashText(item.outerHtml).slice(0, 12)}`;
    const attrs = blockAttrs({ className, metadata });
    return {
      entry: { ...baseEntry, coreBlockName: "core/html", assetId, label: item.label, htmlHash: hashText(item.outerHtml) },
      asset: {
        assetId,
        kind: "inline-svg",
        sourceUrl: "",
        alt: item.label,
        sourceHash: hashText(item.outerHtml),
      },
      content: `<!-- wp:html ${attrs} -->\n${item.outerHtml}\n<!-- /wp:html -->`,
    };
  }

  if (item.kind === "details") {
    const attrs = blockAttrs({ className, metadata });
    return {
      entry: { ...baseEntry, coreBlockName: "core/details", summary: item.summary, text: item.text, textHash: hashText(`${item.summary} ${item.text}`) },
      content: `<!-- wp:details ${attrs} --><details class="${className}"><summary>${escapeHtml(item.summary)}</summary><p>${escapeHtml(item.text)}</p></details><!-- /wp:details -->`,
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
  const rows = blockManifest.routes.map((route) => `| ${route.url} | ${route.sourceMode} | ${route.blockCount} | ${route.blocks.filter((block) => block.assetId).length} | ${route.h1.replace(/\|/g, "\\|")} |`).join("\n");
  const assetRows = mediaManifest.assets.map((asset) => `| ${asset.assetId} | ${asset.kind} | ${asset.routeUsage.length} | ${asset.sourceUrl || "(inline)"} |`).join("\n");
  return `# Editable Block Migration Slice

Date: ${blockManifest.generatedAt}

Source: ${blockManifest.source.stagingBaseUrl}

This is the first review-slice manifest for moving LMHG pages from generated
proof content to editable Gutenberg block documents. It is designed for a
Codex/cloud WordPress runtime and does not require local Docker or local
WordPress.

## Import Contract

\`\`\`bash
wp lmhg import-manifest data/lmhg/source-route-manifest.json
wp lmhg import-block-manifest data/lmhg/block-migration/first-slice-block-manifest.json data/lmhg/block-migration/first-slice-media-manifest.json
\`\`\`

The block import writes serialized core Gutenberg blocks to \`post_content\` and
stores source-to-block correlation metadata for audit and future editor tooling.

## Route Slice

| Route | Source mode | Blocks | Asset blocks | H1 |
|---|---:|---:|---:|---|
${rows}

## Media And Visual Asset Correlation

| Asset ID | Kind | Route usages | Source URL |
|---|---:|---:|---|
${assetRows || "| none | none | 0 | none |"}

## Current Limits

- Image blocks still reference staging asset URLs in serialized block content;
  the paired media manifest records the assets to sideload and rewrite in the
  cloud runtime.
- Inline SVG illustrations are preserved as editable custom HTML blocks for the
  first slice; a later pass can convert repeatable icons/illustrations to block
  patterns or media-library SVG records if the host permits SVG uploads.
- This slice proves block editability and correlation. It is not yet the final
  visual-parity pass.
`;
}
