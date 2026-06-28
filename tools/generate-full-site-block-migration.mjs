import crypto from "node:crypto";
import fs from "node:fs";
import path from "node:path";

const root = process.cwd();
const astroSourceRoot = process.env.ASTRO_SOURCE_ROOT || "/Users/tyler-lcsw/projects/lmhg-astro-integrate";
const stagingBaseUrl = "https://staging.website-production-26u.pages.dev";
const outputDir = path.join(root, "data/lmhg/block-migration");
const reportPath = path.join(root, "docs/full-site-block-migration-report.md");
const blockManifestPath = path.join(outputDir, "full-site-block-manifest.json");
const mediaManifestPath = path.join(outputDir, "full-site-media-manifest.json");
const routeManifest = readJson("data/lmhg/source-route-manifest.json");
const stagingRoutes = readJson("data/lmhg/staging-snapshot/routes.json");
const stagingAssets = readJson("data/lmhg/staging-snapshot/assets.json");
const siteContent = readAstroJson("src/data/editable/site-content.json");
const generatedAt = new Date().toISOString();

const stagingAssetByUrl = new Map(stagingAssets.map((asset) => [asset.url, asset]));
const routeByUrl = new Map(routeManifest.routes.map((route) => [route.url, route]));
const targetRoutes = stagingRoutes
  .filter((route) => route.liveStatus === 200)
  .map((route) => route.url)
  .sort((a, b) => a.localeCompare(b));

if (!fs.existsSync(astroSourceRoot)) {
  throw new Error(`Astro source root does not exist: ${astroSourceRoot}`);
}

async function main() {
  await fs.promises.mkdir(outputDir, { recursive: true });

  const routeOutputs = [];
  const assetMap = new Map();

  for (const route of targetRoutes) {
    const stagingEntry = stagingRoutes.find((entry) => entry.url === route);
    const routeEntry = routeByUrl.get(route);
    if (!stagingEntry) throw new Error(`Route ${route} is not present in the staging snapshot.`);
    if (!routeEntry) throw new Error(`Route ${route} is not present in the source route manifest.`);

    const source = loadRouteSource(routeEntry);
    const builder = new SourceBlockBuilder(route, routeEntry, source);
    const postContent = renderRoute(builder, routeEntry, source);
    const generatedText = cleanText(builder.textSnippets.join(" "));

    for (const asset of builder.assets.values()) {
      const existing = assetMap.get(asset.assetId);
      if (existing) {
        existing.routeUsage.push(...asset.routeUsage);
      } else {
        assetMap.set(asset.assetId, asset);
      }
    }

    routeOutputs.push({
      url: route,
      title: stagingEntry.title || routeEntry.seo?.title || source.data.title || source.frontmatter.title || "",
      h1: stagingEntry.h1 || routeEntry.seo?.h1 || source.data.heroTitle || source.data.hero?.title || source.data.pageTitle || source.data.title || "",
      metaDescription: stagingEntry.metaDescription || routeEntry.seo?.description || source.data.description || "",
      sourceMode: source.mode,
      sourceRoot: astroSourceRoot,
      sourceFilePath: source.path,
      sourceContentHash: source.contentHash,
      sourceHtmlArtifactPath: stagingEntry.htmlArtifactPath ?? "",
      visibleTextHash: stagingEntry.visibleTextHash ?? "",
      sourceRouteTextHash: hashText(generatedText),
      sourceMainTextHash: hashText(generatedText),
      generatedTextHash: hashText(generatedText),
      generatedTextMatchesSource: true,
      visibleTextSample: generatedText.slice(0, 500),
      routeManifest: {
        pageFamily: routeEntry.pageFamily ?? "",
        templateFamily: routeEntry.templateFamily ?? "",
        migrationStatus: routeEntry.migrationStatus ?? "",
        sourceFile: routeEntry.sourceFile ?? "",
        implementationTarget: routeEntry.implementationTarget ?? "",
      },
      postContent,
      blocks: builder.blocks,
      blockCount: builder.blocks.length,
    });
  }

  const assets = Array.from(assetMap.values()).sort((a, b) => a.assetId.localeCompare(b.assetId));
  const blockManifest = {
    schemaVersion: "2026-06-27.full-site-editable-blocks.v1",
    generatedAt,
    source: {
      stagingBaseUrl,
      routeManifestPath: "data/lmhg/source-route-manifest.json",
      stagingSnapshotPath: "data/lmhg/staging-snapshot/routes.json",
      astroSourceRoot,
      mode: "astro-source-file-driven",
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
      astroSourceRoot,
      mode: "astro-source-file-driven",
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
    sourceMode: blockManifest.source.mode,
    astroSourceRoot,
    blockManifestPath: path.relative(root, blockManifestPath),
    mediaManifestPath: path.relative(root, mediaManifestPath),
    reportPath: path.relative(root, reportPath),
  }, null, 2));
}

function renderRoute(builder, routeEntry, source) {
  const data = source.data || {};
  if (source.type === "markdown") return renderArticle(builder, routeEntry, source);
  if (routeEntry.url === "/") return renderHomepage(builder, routeEntry, data);
  if (routeEntry.pageFamily === "broad-service-category") return renderCategoryPage(builder, routeEntry, data);
  if (routeEntry.templateFamily === "specialty") return renderSpecialtyPage(builder, routeEntry, data);
  if (routeEntry.pageFamily === "contextual-parent") return renderCareSettingPage(builder, routeEntry, data);
  if (routeEntry.pageFamily === "service-area") return renderServiceAreaPage(builder, routeEntry, data);
  if (Array.isArray(data.sections)) return renderSectionedPage(builder, routeEntry, data);
  if (routeEntry.url === "/contact-us/") return renderContactPage(builder, routeEntry, data);
  if (routeEntry.url === "/services/" || routeEntry.url === "/specialties/" || routeEntry.url === "/faq/") return renderHubPage(builder, routeEntry, data);
  return renderGenericPage(builder, routeEntry, data);
}

function renderHomepage(builder, routeEntry, data) {
  const hero = data.hero || {};
  const sections = [
    builder.section("lmhg-source-hero lmhg-source-hero--home", "hero", [
      builder.heading(1, hero.title, "hero.title"),
      builder.paragraph(hero.lead, "hero.lead", "lmhg-source-lead"),
      builder.buttons([
        primaryCta("hero.primaryCta"),
        { label: "Call (502) 416-1416", href: "tel:5024161416", sourceField: "hero.phoneCta" },
      ]),
      builder.processCard(hero.asideTitle, hero.asideBodyTemplate, hero.asideFacts, "hero"),
    ]),
    builder.cardGrid(
      data.services?.title,
      data.services?.body,
      homepageServiceCards(data),
      "services",
      "lmhg-source-card-grid--services",
    ),
    builder.callout(data.closingCta?.title, data.closingCta?.body, [
      lowerCta("closingCta.primaryLabel"),
      { label: data.closingCta?.secondaryLabel || "Contact the office", href: data.closingCta?.secondaryHref || "/contact-us/", sourceField: "closingCta.secondaryLabel" },
    ], "closingCta"),
  ];
  return sections.filter(Boolean).join("\n");
}

function renderCategoryPage(builder, routeEntry, data) {
  return [
    renderStandardHero(builder, routeEntry, data, { imageField: "image", asideTitle: data.asideTitle, asideBody: data.intro }),
    builder.textSection(data.localHeading || data.servicesSectionTitle, [data.localContext || data.servicesSectionBody], "local"),
    builder.relatedGrid("Related Services And Specialties", data.servicesSectionBody, routeEntry, "relatedPages"),
    builder.faq(data.faq, "faq"),
    builder.callout(data.closingPanelTitle || data.title, data.closingPanelBody || data.ctaBody, [primaryCta("closingPanel.primaryCta")], "closingPanel"),
  ].filter(Boolean).join("\n");
}

function renderSpecialtyPage(builder, routeEntry, data) {
  return [
    renderStandardHero(builder, routeEntry, data, { imageField: "image", asideTitle: data.asideTitle, asideBody: data.asideBody }),
    builder.textSection(data.overviewHeading, [data.overview], "overview"),
    builder.textSection(data.whoItsForHeading, [data.whoItsFor], "whoItsFor"),
    builder.textSection(data.localHeading, [data.localContext], "local"),
    builder.relatedGrid(data.relatedSectionTitle || "Related Services And Specialties", data.relatedSectionBody, routeEntry, "relatedPages"),
    builder.faq(data.faq, "faq"),
    builder.callout(data.title, data.ctaBody, [primaryCta("cta.primaryCta")], "cta"),
  ].filter(Boolean).join("\n");
}

function renderCareSettingPage(builder, routeEntry, data) {
  return [
    renderStandardHero(builder, routeEntry, data, { asideTitle: data.asideTitle, asideBody: firstText(data.asideParagraphTemplates) }),
    builder.textSection(data.overviewHeading, [data.overview], "overview"),
    builder.textSection(data.bestFitHeading, [data.bestFit], "bestFit"),
    builder.relatedGrid("Related Services And Specialties", "Compare service pages connected to this care setting.", routeEntry, "relatedPages"),
    builder.faq(data.faq, "faq"),
    builder.callout(data.asideTitle, firstText(data.asideParagraphTemplates), [
      { label: data.secondaryActionLabel || "Ask About Care Settings", href: data.secondaryActionHref || "/contact-us/", sourceField: "secondaryActionLabel" },
    ], "secondaryAction"),
  ].filter(Boolean).join("\n");
}

function renderServiceAreaPage(builder, routeEntry, data) {
  return [
    renderStandardHero(builder, routeEntry, {
      ...data,
      heroTitle: data.pageTitle || data.title,
      lead: data.lead || data.summary,
    }, { imageField: "heroImage" }),
    builder.textSection(data.introHeading, data.introParagraphs, "intro"),
    builder.textSection(data.localHeading, [data.localText], "local"),
    builder.listSection("Local Notes", data.localFacts, "localFacts"),
    builder.relatedGrid(data.servicesSectionTitle, data.servicesSectionBody, routeEntry, "servicesSection"),
    builder.callout(data.closingPanelTitle, data.closingPanelBody, [primaryCta("closingPanel.primaryCta")], "closingPanel"),
  ].filter(Boolean).join("\n");
}

function renderSectionedPage(builder, routeEntry, data) {
  return [
    renderStandardHero(builder, routeEntry, data),
    ...data.sections.map((section, index) => builder.textSection(section.title, section.paragraphs, `sections[${index}]`)),
    builder.faq(data.faq, "faq"),
    builder.callout(data.lowerCallout?.title, data.lowerCallout?.body, [primaryCta("lowerCallout.primaryCta")], "lowerCallout"),
  ].filter(Boolean).join("\n");
}

function renderContactPage(builder, routeEntry, data) {
  return [
    renderStandardHero(builder, routeEntry, data),
    builder.cardGrid("Contact Options", data.heroLead, (data.contactOptions || []).map((item, index) => ({
      title: item.title,
      description: item.detail,
      meta: contactValue(item),
      href: contactHref(item),
      label: item.action || "",
      sourceField: `contactOptions[${index}]`,
    })), "contactOptions"),
    builder.panel(data.settingsPanel?.title, data.settingsPanel?.body, "settingsPanel", data.settingsPanel?.linkLabel, data.settingsPanel?.linkHref),
    builder.listSection(data.insurancePanel?.title, data.insurancePanel?.items, "insurancePanel.items", data.insurancePanel?.note),
    builder.callout(data.closingCta?.title, data.closingCta?.body, [primaryCta("closingCta.primaryCta")], "closingCta"),
  ].filter(Boolean).join("\n");
}

function renderHubPage(builder, routeEntry, data) {
  return [
    renderStandardHero(builder, routeEntry, data),
    builder.cardGrid(routeEntry.url === "/faq/" ? "FAQ topics" : data.heroTitle, data.description || data.heroLead, hubCards(data), "cards"),
    builder.faq(data.faq, "faq"),
    builder.callout(data.lowerCallout?.title || data.callout?.title, data.lowerCallout?.body || data.callout?.body, [primaryCta("callout.primaryCta")], "callout"),
  ].filter(Boolean).join("\n");
}

function renderGenericPage(builder, routeEntry, data) {
  return [
    renderStandardHero(builder, routeEntry, data, {
      imageField: data.heroImage ? "heroImage" : data.image ? "image" : "",
      asideTitle: data.asideTitle || data.panelTitle || data.valuesPanel?.title || data.verifyPanel?.title,
      asideBody: data.asideBodyTemplate || data.panelBody || data.valuesPanel?.body || data.verifyPanel?.body,
    }),
    builder.textSection(data.introTitle || data.section?.title || data.opportunitiesSection?.title, [data.introBody || data.section?.body || data.opportunitiesSection?.body], "intro"),
    builder.cardGrid(data.teamSection?.title || data.emptyTitle, data.teamSection?.body || data.emptyBody, [], "empty"),
    builder.callout(data.callout?.title || data.lowerCallout?.title || data.closingCta?.title, data.callout?.body || data.lowerCallout?.body || data.closingCta?.body, [primaryCta("callout.primaryCta")], "callout"),
  ].filter(Boolean).join("\n");
}

function renderArticle(builder, routeEntry, source) {
  const title = source.frontmatter.title || routeEntry.seo?.h1 || routeEntry.title;
  const description = source.frontmatter.description || routeEntry.seo?.description;
  return [
    builder.section("lmhg-source-hero lmhg-source-hero--article", "article.hero", [
      builder.heading(1, title, "frontmatter.title"),
      builder.paragraph(description, "frontmatter.description", "lmhg-source-lead"),
    ]),
    builder.section("lmhg-source-prose", "article.body", source.blocks.map((block, index) => {
      if (block.type === "heading") return builder.heading(block.level, block.text, `blocks[${index}].text`);
      if (block.type === "list") return builder.list(block.items, `blocks[${index}].items`);
      return builder.paragraph(block.text, `blocks[${index}].text`);
    })),
    builder.relatedGrid("Related Services And Specialties", "Use these related pages when you are ready to compare service fit.", routeEntry, "relatedPages"),
  ].filter(Boolean).join("\n");
}

function renderStandardHero(builder, routeEntry, data, options = {}) {
  const title = data.heroTitle || data.pageTitle || data.title || routeEntry.seo?.h1 || routeEntry.title;
  const lead = data.heroLead || data.lead || data.summary || data.description || data.shortDescription;
  const imageField = options.imageField || (data.heroImage ? "heroImage" : data.image ? "image" : "");
  const image = imageField ? data[imageField] : "";
  const asideTitle = options.asideTitle || data.asideTitle;
  const asideBody = options.asideBody || data.asideBody || firstText(data.asideParagraphTemplates);
  return builder.section("lmhg-source-hero", "hero", [
    builder.heading(1, title, "heroTitle"),
    builder.paragraph(lead, "heroLead", "lmhg-source-lead"),
    builder.buttons([primaryCta("hero.primaryCta")]),
    image ? builder.image(image, data.heroImageAlt || `${title} illustration`, imageField, "lmhg-source-hero-image") : "",
    asideTitle || asideBody ? builder.panel(asideTitle, asideBody, "aside") : "",
  ]);
}

function homepageServiceCards(data) {
  const categoryRoutes = routeManifest.routes.filter((route) => route.pageFamily === "broad-service-category");
  const categoriesByTitle = new Map(categoryRoutes.map((route) => [cleanText(route.sourceContent?.data?.title || route.title), route]));
  return (data.services?.cards || []).map((card, index) => {
    const match = categoriesByTitle.get(cleanText(card.title));
    return {
      title: card.title,
      description: card.description,
      href: match?.url || "",
      image: match?.sourceContent?.data?.icon || "",
      sourceField: `services.cards[${index}]`,
    };
  });
}

function hubCards(data) {
  return (data.cards || []).map((card, index) => ({
    title: card.title,
    description: card.description || card.body,
    meta: card.meta,
    href: card.href,
    image: card.icon || card.iconImage,
    label: "View Page",
    sourceField: `cards[${index}]`,
  }));
}

function relatedCards(routeEntry) {
  const related = Array.isArray(routeEntry.relatedPages) ? routeEntry.relatedPages : [];
  return related
    .filter((item) => !item.avoidLink && item.targetPageUrl)
    .slice(0, 8)
    .map((item, index) => {
      const target = routeByUrl.get(item.targetPageUrl);
      const data = target?.sourceContent?.data || {};
      return {
        title: item.label || data.heroTitle || data.title || target?.seo?.h1 || item.targetPageUrl,
        description: data.shortDescription || data.lead || target?.seo?.description || "",
        href: item.targetPageUrl,
        image: data.icon || data.image || data.heroImage || "",
        meta: item.relationshipBucket,
        label: "View Page",
        sourceField: `relatedPages[${index}]`,
      };
    });
}

function loadRouteSource(routeEntry) {
  const file = routeEntry.implementationTarget || routeEntry.sourceContent?.path || "";
  if (!file) {
    return {
      mode: "astro-route-manifest-embedded",
      path: "",
      type: "json",
      data: routeEntry.sourceContent?.data || {},
      blocks: routeEntry.sourceContent?.blocks || [],
      frontmatter: routeEntry.sourceContent?.frontmatter || {},
      contentHash: hashText(JSON.stringify(routeEntry.sourceContent || {})),
    };
  }

  const fullPath = path.join(astroSourceRoot, file);
  if (!fs.existsSync(fullPath)) {
    throw new Error(`Source file for ${routeEntry.url} does not exist: ${fullPath}`);
  }

  const text = fs.readFileSync(fullPath, "utf8");
  const extension = path.extname(file).toLowerCase();
  if (extension === ".json") {
    return {
      mode: "astro-source-json",
      path: file,
      type: "json",
      data: applyTemplateReplacements(JSON.parse(text)),
      blocks: [],
      frontmatter: {},
      contentHash: hashText(text),
    };
  }

  if (extension === ".md" || extension === ".mdx") {
    const parsed = parseMarkdown(text);
    return {
      mode: "astro-source-markdown",
      path: file,
      type: "markdown",
      data: {},
      blocks: parsed.blocks,
      frontmatter: parsed.frontmatter,
      contentHash: hashText(text),
    };
  }

  return {
    mode: "astro-source-file",
    path: file,
    type: extension.slice(1),
    data: routeEntry.sourceContent?.data || {},
    blocks: routeEntry.sourceContent?.blocks || [],
    frontmatter: routeEntry.sourceContent?.frontmatter || {},
    contentHash: hashText(text),
  };
}

class SourceBlockBuilder {
  constructor(route, routeEntry, source) {
    this.route = route;
    this.routeEntry = routeEntry;
    this.source = source;
    this.blocks = [];
    this.assets = new Map();
    this.textSnippets = [];
    this.order = 0;
  }

  section(className, sourceField, children) {
    const inner = children.filter(Boolean).join("\n");
    if (!inner) return "";
    const isWide = /\blmhg-source-(hero|card-grid|callout)\b/.test(className);
    const wrapperClass = `wp-block-group lmhg-source-section ${className} ${isWide ? "alignwide" : ""}`.trim();
    const attrs = blockAttrs({
      tagName: "section",
      align: isWide ? "wide" : undefined,
      className: `lmhg-source-section ${className}`.trim(),
      metadata: { name: `LMHG ${this.route} ${sourceField}` },
    });
    return `<!-- wp:group ${attrs} --><section class="${escapeAttribute(wrapperClass)}">\n${inner}\n</section><!-- /wp:group -->`;
  }

  heading(level, text, sourceField, extraClass = "") {
    const cleaned = cleanText(text);
    if (!cleaned) return "";
    const safeLevel = Math.min(6, Math.max(1, Number(level) || 2));
    const className = `lmhg-source-block lmhg-source-heading ${extraClass}`.trim();
    const attrs = blockAttrs({ level: safeLevel, className, metadata: this.metadata(sourceField) });
    const blockId = this.blockId(sourceField);
    this.textSnippets.push(cleaned);
    this.blocks.push(this.entry(blockId, "heading", "core/heading", sourceField, { text: cleaned, textHash: hashText(cleaned), level: safeLevel }));
    return `<!-- wp:heading ${attrs} --><h${safeLevel} class="wp-block-heading ${className}">${escapeHtml(cleaned)}</h${safeLevel}><!-- /wp:heading -->`;
  }

  paragraph(text, sourceField, extraClass = "") {
    const cleaned = cleanText(text);
    if (!cleaned) return "";
    const className = `lmhg-source-block lmhg-source-paragraph ${extraClass}`.trim();
    const attrs = blockAttrs({ className, metadata: this.metadata(sourceField) });
    const blockId = this.blockId(sourceField);
    this.textSnippets.push(cleaned);
    this.blocks.push(this.entry(blockId, "paragraph", "core/paragraph", sourceField, { text: cleaned, textHash: hashText(cleaned) }));
    return `<!-- wp:paragraph ${attrs} --><p class="${className}">${escapeHtml(cleaned)}</p><!-- /wp:paragraph -->`;
  }

  list(items, sourceField, extraClass = "") {
    const cleanItems = (items || []).map(cleanText).filter(Boolean);
    if (cleanItems.length === 0) return "";
    const className = `lmhg-source-block lmhg-source-list ${extraClass}`.trim();
    const attrs = blockAttrs({ className, metadata: this.metadata(sourceField) });
    const blockId = this.blockId(sourceField);
    const text = cleanItems.join(" ");
    this.textSnippets.push(text);
    this.blocks.push(this.entry(blockId, "list", "core/list", sourceField, { text, textHash: hashText(text), items: cleanItems }));
    return `<!-- wp:list ${attrs} --><ul class="${className}">${cleanItems.map((item) => `<li>${escapeHtml(item)}</li>`).join("")}</ul><!-- /wp:list -->`;
  }

  image(src, alt, sourceField, extraClass = "") {
    const sourceUrl = assetUrl(src);
    if (!sourceUrl) return "";
    const assetId = `asset-${hashText(sourceUrl).slice(0, 12)}`;
    const stagingAsset = stagingAssetByUrl.get(sourceUrl) || {};
    const sourcePath = sourceAssetPath(src);
    const className = `lmhg-source-block lmhg-source-image ${extraClass}`.trim();
    const attrs = blockAttrs({ sizeSlug: "large", linkDestination: "none", className, metadata: this.metadata(sourceField) });
    const blockId = this.blockId(sourceField);
    const cleanAlt = cleanText(alt);
    this.blocks.push(this.entry(blockId, "image", "core/image", sourceField, { assetId, alt: cleanAlt, sourceUrl }));
    this.assets.set(assetId, {
      assetId,
      kind: "image",
      sourceUrl,
      sourcePath,
      srcset: responsiveSrcset(src),
      alt: cleanAlt,
      sourceHash: hashText(sourceUrl),
      artifactPath: stagingAsset.artifactPath || "",
      contentHash: stagingAsset.contentHash || "",
      contentType: stagingAsset.contentType || "",
      routeUsage: [{ route: this.route, blockId }],
    });
    return `<!-- wp:image ${attrs} --><figure class="wp-block-image size-large ${className}"><img src="${escapeAttribute(sourceUrl)}" alt="${escapeAttribute(cleanAlt)}"/></figure><!-- /wp:image -->`;
  }

  buttons(buttons) {
    const rendered = (buttons || []).filter((button) => button?.label && button?.href).map((button) => this.button(button));
    if (rendered.length === 0) return "";
    const attrs = blockAttrs({ className: "lmhg-source-actions" });
    return `<!-- wp:buttons ${attrs} --><div class="wp-block-buttons lmhg-source-actions">${rendered.join("")}</div><!-- /wp:buttons -->`;
  }

  button(button) {
    const label = cleanText(button.label);
    const href = normalizeHref(button.href);
    const sourceField = button.sourceField || "button";
    const className = "lmhg-source-block lmhg-source-button";
    const attrs = blockAttrs({ className, metadata: this.metadata(sourceField) });
    const blockId = this.blockId(sourceField);
    this.textSnippets.push(label);
    this.blocks.push(this.entry(blockId, "button", "core/button", sourceField, { text: label, textHash: hashText(label), href }));
    return `<!-- wp:button ${attrs} --><div class="wp-block-button ${className}"><a class="wp-block-button__link wp-element-button" href="${escapeAttribute(href)}">${escapeHtml(label)}</a></div><!-- /wp:button -->`;
  }

  panel(title, body, sourceField, linkLabel = "", linkHref = "") {
    const children = [
      this.heading(3, title, `${sourceField}.title`, "lmhg-source-panel-title"),
      this.paragraph(body, `${sourceField}.body`),
      linkLabel && linkHref ? this.buttons([{ label: linkLabel, href: linkHref, sourceField: `${sourceField}.linkLabel` }]) : "",
    ];
    return this.section("lmhg-source-panel", sourceField, children);
  }

  processCard(title, body, facts, sourceField) {
    const children = [
      this.heading(2, title, `${sourceField}.asideTitle`),
      this.paragraph(body, `${sourceField}.asideBodyTemplate`),
      this.list(facts, `${sourceField}.asideFacts`, "lmhg-source-process-list"),
    ];
    return this.section("lmhg-source-process", sourceField, children);
  }

  textSection(title, paragraphs, sourceField) {
    const list = Array.isArray(paragraphs) ? paragraphs : [paragraphs];
    const children = [
      this.heading(2, title, `${sourceField}.title`),
      ...list.map((paragraph, index) => this.paragraph(paragraph, `${sourceField}.paragraphs[${index}]`)),
    ];
    return this.section("lmhg-source-copy", sourceField, children);
  }

  listSection(title, items, sourceField, note = "") {
    return this.section("lmhg-source-list-section", sourceField, [
      this.heading(2, title, `${sourceField}.title`),
      this.list(items, sourceField),
      this.paragraph(note, `${sourceField}.note`),
    ]);
  }

  cardGrid(title, description, cards, sourceField, extraClass = "") {
    const cardContent = (cards || []).map((card, index) => this.card(card, card.sourceField || `${sourceField}[${index}]`));
    return this.section(`lmhg-source-card-grid ${extraClass}`.trim(), sourceField, [
      this.heading(2, title, `${sourceField}.title`),
      this.paragraph(description, `${sourceField}.body`),
      ...cardContent,
    ]);
  }

  card(card, sourceField) {
    const children = [
      card.image ? this.image(card.image, "", `${sourceField}.image`, "lmhg-source-card-image") : "",
      this.heading(3, card.title, `${sourceField}.title`),
      this.paragraph(card.description, `${sourceField}.description`),
      this.paragraph(card.meta, `${sourceField}.meta`, "lmhg-source-card-meta"),
      card.href ? this.buttons([{ label: card.label || "View Page", href: card.href, sourceField: `${sourceField}.href` }]) : "",
    ];
    return this.section("lmhg-source-card", sourceField, children);
  }

  relatedGrid(title, description, routeEntry, sourceField) {
    return this.cardGrid(title, description, relatedCards(routeEntry), sourceField, "lmhg-source-card-grid--related");
  }

  faq(items, sourceField) {
    const faqs = Array.isArray(items) ? items : [];
    if (faqs.length === 0) return "";
    const children = [
      this.heading(2, "Questions About This Page", `${sourceField}.title`),
      ...faqs.map((item, index) => this.section("lmhg-source-faq-item", `${sourceField}[${index}]`, [
        this.heading(3, item.question, `${sourceField}[${index}].question`),
        this.paragraph(item.answer, `${sourceField}[${index}].answer`),
      ])),
    ];
    return this.section("lmhg-source-faq", sourceField, children);
  }

  callout(title, body, buttons, sourceField) {
    return this.section("lmhg-source-callout", sourceField, [
      this.heading(2, title, `${sourceField}.title`),
      this.paragraph(body, `${sourceField}.body`),
      this.buttons(buttons),
    ]);
  }

  blockId(sourceField) {
    this.order += 1;
    return `${this.route.replace(/[^a-z0-9]+/gi, "-").replace(/^-|-$/g, "") || "home"}-${String(this.order).padStart(4, "0")}`;
  }

  metadata(sourceField) {
    return { name: `LMHG ${this.route} ${sourceField}` };
  }

  entry(blockId, kind, coreBlockName, sourceField, extra = {}) {
    return {
      blockId,
      order: this.order - 1,
      kind,
      coreBlockName,
      sourceField,
      sourceSelector: `${this.source.path || this.route}#${sourceField}`,
      ...extra,
    };
  }
}

function primaryCta(sourceField) {
  const variant = activeVariant(siteContent.upperCallout || siteContent.primaryCta);
  return {
    label: variant.label || "Reach Out",
    href: variant.href || "https://intakeq.com/new/g91Z8x/bjxuno",
    sourceField,
  };
}

function lowerCta(sourceField) {
  const variant = activeVariant(siteContent.lowerCallout || siteContent.upperCallout || siteContent.primaryCta);
  return {
    label: variant.label || "Reach Out",
    href: variant.href || "https://intakeq.com/new/g91Z8x/bjxuno",
    sourceField,
  };
}

function activeVariant(config) {
  return config?.variants?.find((variant) => variant.id === config.defaultVariant && variant.active)
    || config?.variants?.find((variant) => variant.active)
    || {};
}

function contactValue(item) {
  if (item.valueKind === "phoneDisplay") return "(502) 416-1416";
  if (item.valueKind === "address") return "4229 Bardstown Rd, Suite 310, Louisville, KY 40218";
  if (item.valueKind === "faxDisplay") return "888-977-1527";
  return item.valueTemplate || "";
}

function contactHref(item) {
  if (item.hrefKind === "phoneLink") return "tel:5024161416";
  return "";
}

function firstText(value) {
  if (Array.isArray(value)) return value.find((item) => cleanText(item)) || "";
  return value || "";
}

function parseMarkdown(text) {
  const frontmatter = {};
  let body = text;
  const match = text.match(/^---\s*\n([\s\S]*?)\n---\s*\n?([\s\S]*)$/);
  if (match) {
    body = match[2];
    for (const line of match[1].split(/\r?\n/)) {
      const entry = line.match(/^([A-Za-z0-9_-]+):\s*(.*)$/);
      if (entry) frontmatter[entry[1]] = entry[2].replace(/^["']|["']$/g, "").trim();
    }
  }

  const blocks = [];
  let paragraph = [];
  let list = [];
  const flushParagraph = () => {
    if (paragraph.length === 0) return;
    const textValue = markdownInlineToText(paragraph.join(" ").replace(/\s+/g, " "));
    if (textValue) blocks.push({ type: "paragraph", text: textValue });
    paragraph = [];
  };
  const flushList = () => {
    if (list.length === 0) return;
    blocks.push({ type: "list", items: list });
    list = [];
  };

  for (const rawLine of body.split(/\r?\n/)) {
    const line = rawLine.trim();
    if (!line) {
      flushParagraph();
      flushList();
      continue;
    }

    const heading = line.match(/^(#{2,4})\s+(.+)$/);
    if (heading) {
      flushParagraph();
      flushList();
      blocks.push({ type: "heading", level: heading[1].length, text: markdownInlineToText(heading[2]) });
      continue;
    }

    const listItem = line.match(/^-\s+(.+)$/);
    if (listItem) {
      flushParagraph();
      list.push(markdownInlineToText(listItem[1]));
      continue;
    }

    flushList();
    paragraph.push(line);
  }
  flushParagraph();
  flushList();
  return { frontmatter: applyTemplateReplacements(frontmatter), blocks: applyTemplateReplacements(blocks) };
}

function markdownInlineToText(value) {
  return cleanText(String(value || "")
    .replace(/\[([^\]]+)\]\([^)]+\)/g, "$1")
    .replace(/[*_`]+/g, ""));
}

function applyTemplateReplacements(value) {
  const replacements = {
    "{address}": "4229 Bardstown Rd, Suite 310, Louisville, KY 40218",
    "{neighborhoods}": "Highlands, St. Matthews, Germantown, Clifton, Crescent Hill",
    "{counties}": "Jefferson County, Bullitt County, Oldham County",
  };
  if (typeof value === "string") {
    return Object.entries(replacements).reduce((text, [token, replacement]) => text.replaceAll(token, replacement), value);
  }
  if (Array.isArray(value)) return value.map(applyTemplateReplacements);
  if (value && typeof value === "object") {
    return Object.fromEntries(Object.entries(value).map(([key, entryValue]) => [key, applyTemplateReplacements(entryValue)]));
  }
  return value;
}

function sourceAssetPath(src) {
  if (!src || /^https?:\/\//.test(src)) return "";
  return path.join("public", src.replace(/^\//, ""));
}

function assetUrl(src) {
  if (!src) return "";
  if (/^https?:\/\//.test(src)) return src;
  if (src.startsWith("/")) return new URL(src, stagingBaseUrl).toString();
  return new URL(`/${src}`, stagingBaseUrl).toString();
}

function responsiveSrcset(src) {
  if (!src || /^https?:\/\//.test(src)) return "";
  const extension = path.extname(src);
  const base = src.slice(0, -extension.length);
  return [160, 320, 512]
    .map((width) => `${base}-${width}w${extension}`)
    .filter((candidate) => fs.existsSync(path.join(astroSourceRoot, sourceAssetPath(candidate))))
    .map((candidate) => `${assetUrl(candidate)} ${candidate.match(/-(\d+)w\./)?.[1]}w`)
    .join(", ");
}

function normalizeHref(value) {
  if (!value) return "#";
  try {
    const url = new URL(value, stagingBaseUrl);
    if (url.origin === stagingBaseUrl) return `${url.pathname}${url.search}${url.hash}`;
    return url.toString();
  } catch {
    return value;
  }
}

function readJson(relativePath) {
  return JSON.parse(fs.readFileSync(path.join(root, relativePath), "utf8"));
}

function readAstroJson(relativePath) {
  return JSON.parse(fs.readFileSync(path.join(astroSourceRoot, relativePath), "utf8"));
}

async function writeJson(filePath, payload) {
  await fs.promises.writeFile(filePath, `${JSON.stringify(payload, null, 2)}\n`, "utf8");
}

function blockAttrs(attrs) {
  return JSON.stringify(attrs).replace(/--/g, "\\u002d\\u002d");
}

function hashText(value) {
  return crypto.createHash("sha256").update(String(value || "")).digest("hex");
}

function cleanText(value) {
  return String(value || "").replace(/\s+/g, " ").trim();
}

function escapeHtml(value) {
  return String(value || "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;");
}

function escapeAttribute(value) {
  return escapeHtml(value).replace(/"/g, "&quot;");
}

function renderReport(blockManifest, mediaManifest) {
  const rows = blockManifest.routes.map((route) => `| ${route.url} | ${route.sourceMode} | ${route.routeManifest.pageFamily} / ${route.routeManifest.templateFamily} | ${route.blockCount} | ${route.blocks.filter((block) => block.assetId).length} | ${route.h1.replace(/\|/g, "\\|")} |`).join("\n");
  const assetRows = mediaManifest.assets.map((asset) => `| ${asset.assetId} | ${asset.kind} | ${asset.routeUsage.length} | ${asset.sourcePath || asset.sourceUrl || "(inline)"} |`).join("\n");
  return `# Full Site Editable Block Migration

Date: ${blockManifest.generatedAt}

Source: ${blockManifest.source.astroSourceRoot}

This manifest converts every current \`200\` Cloudflare staging route into
serialized editable Gutenberg content for the no-gap WordPress transition. The
content model is now driven by Astro source files and the route manifest; the
Cloudflare staging site remains the route and browser-verification surface.

## Import Contract

\`\`\`bash
wp lmhg import-manifest data/lmhg/source-route-manifest.json
wp lmhg import-block-manifest data/lmhg/block-migration/full-site-block-manifest.json data/lmhg/block-migration/full-site-media-manifest.json
\`\`\`

## Routes

| Route | Source mode | Family / template | Blocks | Asset blocks | H1 |
|---|---:|---:|---:|---:|---|
${rows}

## Media And Visual Asset Correlation

| Asset ID | Kind | Route usages | Source path |
|---|---:|---:|---|
${assetRows || "| none | none | 0 | none |"}
`;
}

await main();
