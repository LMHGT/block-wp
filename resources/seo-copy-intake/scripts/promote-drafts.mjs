#!/usr/bin/env node

import fs from 'node:fs';
import path from 'node:path';

const targetSlugs = new Set([
  'individual-counseling',
  'child-counseling',
  'family-therapy',
  'court-ordered',
  'community-based-services',
  'group-therapy',
  'trauma-therapy',
  'adult-counseling',
  'anxiety-depression-therapy',
  'case-management',
  'co-parenting',
  'community-support',
  'emdr-therapy',
  'family-reunification',
  'play-therapy',
]);

const [, , draftsDir, pageDataPath] = process.argv;
if (!draftsDir || !pageDataPath) {
  throw new Error('Usage: promote-drafts.mjs <drafts-dir> <wp2026-page-data.json>');
}

const escapeHtml = (value) => value
  .replaceAll('&', '&amp;')
  .replaceAll('<', '&lt;')
  .replaceAll('>', '&gt;')
  .replaceAll('"', '&quot;');

function inlineMarkup(value) {
  let result = '';
  let lastIndex = 0;
  const links = /\[([^\]]+)\]\(([^)]+)\)/g;
  for (const match of value.matchAll(links)) {
    result += escapeHtml(value.slice(lastIndex, match.index)).replace(/`([^`]+)`/g, '$1');
    result += `<a href="${escapeHtml(match[2])}">${escapeHtml(match[1])}</a>`;
    lastIndex = match.index + match[0].length;
  }
  return result + escapeHtml(value.slice(lastIndex)).replace(/`([^`]+)`/g, '$1');
}

function plainText(value) {
  return value
    .replace(/\[([^\]]+)\]\([^)]+\)/g, '$1')
    .replace(/`([^`]+)`/g, '$1')
    .replace(/\s+/g, ' ')
    .trim();
}

function parseDraft(filePath) {
  const lines = fs.readFileSync(filePath, 'utf8').split(/\r?\n/);
  const meta = {};
  const metaPattern = /^-?\s*(Status|Source slug|Primary keyword|Secondary keywords|Title tag|Meta description|H1):\s*(.*)$/i;
  let metadataEnd = -1;

  lines.forEach((line, index) => {
    const match = line.match(metaPattern);
    if (!match) return;
    meta[match[1].toLowerCase()] = match[2].replaceAll('`', '').trim();
    metadataEnd = Math.max(metadataEnd, index);
  });

  const h1 = meta.h1;
  if (!h1) throw new Error(`Missing H1 metadata in ${filePath}`);

  const explicitH1 = lines.findIndex((line, index) => index > metadataEnd && line.trim() === `# ${h1}`);
  const bodyStart = explicitH1 >= 0 ? explicitH1 + 1 : metadataEnd + 1;
  const sections = [{ title: '', blocks: [] }];
  let section = sections[0];
  let paragraph = [];
  let list = [];

  const flushParagraph = () => {
    if (paragraph.length) section.blocks.push({ type: 'paragraph', text: paragraph.join(' ') });
    paragraph = [];
  };
  const flushList = () => {
    if (list.length) section.blocks.push({ type: 'list', items: list });
    list = [];
  };
  const flush = () => {
    flushParagraph();
    flushList();
  };

  for (let index = bodyStart; index < lines.length; index += 1) {
    const line = lines[index].trim();
    if (line === '## Implementation Note') break;
    if (line.startsWith('## ')) {
      flush();
      section = { title: line.slice(3).trim(), blocks: [] };
      sections.push(section);
      continue;
    }
    if (line.startsWith('### ')) {
      flush();
      section.blocks.push({ type: 'question', text: line.slice(4).trim() });
      continue;
    }
    const item = line.match(/^[-*]\s+(.+)$/);
    if (item) {
      flushParagraph();
      list.push(item[1]);
      continue;
    }
    if (!line) {
      flush();
      continue;
    }
    flushList();
    paragraph.push(line);
  }
  flush();

  return { meta, sections };
}

function renderBlock(block) {
  if (block.type === 'paragraph') {
    return `<!-- wp:paragraph -->\n<p>${inlineMarkup(block.text)}</p>\n<!-- /wp:paragraph -->`;
  }
  if (block.type === 'list') {
    const items = block.items.map((item) => `<li>${inlineMarkup(item)}</li>`).join('');
    return `<!-- wp:list -->\n<ul class="wp-block-list">${items}</ul>\n<!-- /wp:list -->`;
  }
  return '';
}

function buildContent(page, draft) {
  const kind = page.template === 'service-page' ? 'service' : 'specialty';
  const intro = draft.sections.find((item) => item.title === '');
  const leadIndex = intro?.blocks.findIndex((item) => item.type === 'paragraph') ?? -1;
  if (!intro || leadIndex < 0) throw new Error(`Missing opening paragraph for ${page.slug}`);
  const lead = intro.blocks[leadIndex].text;
  const breadcrumbs = page.content.match(/<p class="wp2026-breadcrumbs">[\s\S]*?<\/p>/)?.[0]
    ?? `<p class="wp2026-breadcrumbs"><a href="/">Home</a> &nbsp;/&nbsp; <a href="/services/">Services</a> &nbsp;/&nbsp; <span>${escapeHtml(draft.meta.h1)}</span></p>`;

  const detail = [];
  intro.blocks.forEach((block, index) => {
    if (index !== leadIndex) detail.push(renderBlock(block));
  });

  for (const current of draft.sections) {
    if (!current.title || current.title === 'Getting Started' || current.title === 'Frequently Asked Questions') continue;
    detail.push(`<!-- wp:heading {"level":2,"className":"wp2026-section-title"} -->\n<h2 class="wp-block-heading wp2026-section-title">${escapeHtml(current.title)}</h2>\n<!-- /wp:heading -->`);
    current.blocks.forEach((block) => detail.push(renderBlock(block)));
  }

  const gettingStarted = draft.sections.find((item) => item.title === 'Getting Started');
  const gettingStartedCopy = gettingStarted?.blocks.map(renderBlock).filter(Boolean).join('\n\n') ?? '';

  return `<!-- wp:group {"className":"wp2026-${kind}-hero-copy","layout":{"type":"constrained"}} -->
<div class="wp-block-group wp2026-${kind}-hero-copy">
<!-- wp:paragraph {"className":"wp2026-breadcrumbs"} -->
${breadcrumbs}
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"wp2026-lead"} -->
<p class="wp2026-lead">${inlineMarkup(lead)}</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"className":"wp2026-hero-actions"} -->
<div class="wp-block-buttons wp2026-hero-actions"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://intakeq.com/new/g91Z8x/bjxuno" rel="noopener">Reach Out</a></div>
<!-- /wp:button -->
<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="tel:+15024161416">Call (502) 416-1416</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"wp2026-${kind}-detail","layout":{"type":"constrained"}} -->
<div class="wp-block-group wp2026-${kind}-detail">${detail.join('\n\n')}</div>
<!-- /wp:group -->

<!-- wp:shortcode -->
[lmhg_related_pages heading="Related Pages"]
<!-- /wp:shortcode -->

<!-- wp:shortcode -->
[lmhg_faqs heading="Common Questions"]
<!-- /wp:shortcode -->

<!-- wp:group {"className":"wp2026-page-cta","layout":{"type":"constrained"}} -->
<div class="wp-block-group wp2026-page-cta"><!-- wp:heading {"level":2,"className":"wp2026-section-title"} -->
<h2 class="wp-block-heading wp2026-section-title">Ready To Reach Out?</h2>
<!-- /wp:heading -->
${gettingStartedCopy}
<!-- wp:buttons {"className":"wp2026-closing-actions"} -->
<div class="wp-block-buttons wp2026-closing-actions"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://intakeq.com/new/g91Z8x/bjxuno">Reach Out</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->`;
}

function faqItems(draft) {
  const faq = draft.sections.find((item) => item.title === 'Frequently Asked Questions');
  if (!faq) return [];
  const items = [];
  let current = null;
  for (const block of faq.blocks) {
    if (block.type === 'question') {
      if (current) items.push(current);
      current = { question: plainText(block.text), answers: [] };
    } else if (current && block.type === 'paragraph') {
      current.answers.push(plainText(block.text));
    }
  }
  if (current) items.push(current);
  return items.map((item) => ({ question: item.question, answer: item.answers.join(' ') }));
}

const data = JSON.parse(fs.readFileSync(pageDataPath, 'utf8'));
const promoted = [];
for (const page of data.pages) {
  if (!targetSlugs.has(page.slug)) continue;
  const draftPath = path.join(draftsDir, `${page.slug}.md`);
  if (!fs.existsSync(draftPath)) throw new Error(`Draft not found: ${draftPath}`);
  const draft = parseDraft(draftPath);
  page.title = draft.meta.h1;
  page.content = buildContent(page, draft);
  page.faqItems = faqItems(draft);
  page.seo = {
    title: draft.meta['title tag'],
    description: draft.meta['meta description'],
    h1: draft.meta.h1,
    primaryKeyword: draft.meta['primary keyword'],
    secondaryKeywords: (draft.meta['secondary keywords'] ?? '').split(';').map((item) => item.trim()).filter(Boolean),
    schemaType: 'MedicalWebPage',
    canonicalUrl: page.path,
    status: 'owner-answer-based-rich-copy',
  };
  promoted.push(page.slug);
}

const missing = [...targetSlugs].filter((slug) => !promoted.includes(slug));
if (missing.length) throw new Error(`Page-data entries not found: ${missing.join(', ')}`);

fs.writeFileSync(pageDataPath, `${JSON.stringify(data, null, 2)}\n`);
console.log(`Promoted ${promoted.length} drafts: ${promoted.join(', ')}`);
