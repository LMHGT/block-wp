import fs from 'node:fs';
import path from 'node:path';

const repoRoot = path.resolve(import.meta.dirname, '../../..');
const intakeDir = path.join(repoRoot, 'resources/seo-copy-intake');
const draftsDir = path.join(intakeDir, 'page-copy-drafts');
const pageDataPath = path.join(repoRoot, 'wp-content/themes/wordpress-2026/wp2026-page-data.json');
const pageData = JSON.parse(fs.readFileSync(pageDataPath, 'utf8'));
const aliases = {
  'attachment-therapy': 'parent-child-attachment-therapy',
  'child-behavioral-intervention': 'child-behavioral-therapy',
};
const hubKeywords = {
  services: ['counseling services Louisville KY', 'mental health services Louisville KY'],
  specialties: ['specialized therapy Louisville KY', 'specialty counseling Louisville KY'],
};

function normalize(value) {
  return value
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, ' ')
    .replace(/\bin (?=louisville\b)/g, '')
    .trim();
}

function stripMarkdown(value) {
  return value
    .replace(/^#.*$/gm, '')
    .replace(/^[-*]\s+/gm, '')
    .replace(/\[([^\]]+)\]\([^\)]+\)/g, '$1')
    .replace(/[`*_>#]/g, '')
    .replace(/\s+/g, ' ')
    .trim();
}

function syllables(word) {
  const cleaned = word.toLowerCase().replace(/[^a-z]/g, '');
  if (cleaned.length <= 3) return cleaned ? 1 : 0;
  const trimmed = cleaned.replace(/(?:es|ed|e)$/i, '').replace(/^y/i, '');
  return Math.max(1, (trimmed.match(/[aeiouy]{1,2}/g) || []).length);
}

function grade(text) {
  const words = text.match(/[A-Za-z]+(?:'[A-Za-z]+)?/g) || [];
  const sentences = text.split(/[.!?]+/).filter((item) => item.trim()).length || 1;
  const syllableCount = words.reduce((sum, word) => sum + syllables(word), 0);
  return 0.39 * (words.length / sentences) + 11.8 * (syllableCount / words.length) - 15.59;
}

const pages = pageData.pages.filter((page) => /service|special/.test(page.template));
const failures = [];

for (const page of pages) {
  const filename = `${aliases[page.slug] || page.slug}.md`;
  const filePath = path.join(draftsDir, filename);
  if (!fs.existsSync(filePath)) {
    failures.push(`${page.slug}: missing draft ${filename}`);
    continue;
  }

  const draft = fs.readFileSync(filePath, 'utf8');
  const h1Offset = draft.search(/^# (?!.*Page Copy Draft).*$/m);
  const rawVisitorCopy = h1Offset >= 0 ? draft.slice(h1Offset) : draft;
  const visitorCopy = rawVisitorCopy.split(/^## Implementation Note$/m)[0];
  const plain = stripMarkdown(visitorCopy);
  const normalizedBody = normalize(visitorCopy);
  const keywords = hubKeywords[page.slug] || [page.seo.primaryKeyword, page.seo.secondaryKeywords[0]];
  const wordCount = plain.match(/[A-Za-z0-9]+(?:'[A-Za-z]+)?/g)?.length || 0;
  const readingGrade = grade(plain);

  for (const [index, keyword] of keywords.entries()) {
    if (!normalizedBody.includes(normalize(keyword))) {
      failures.push(`${page.slug}: ${index === 0 ? 'primary' : 'secondary'} keyword missing from visitor copy: ${keyword}`);
    }
  }
  if (wordCount < 475 || wordCount > 750) failures.push(`${page.slug}: ${wordCount} words; expected 475-750`);
  if (readingGrade > 6.5) failures.push(`${page.slug}: grade ${readingGrade.toFixed(1)}; expected 6.5 or lower`);
  if (!/Title tag:/i.test(draft) || !/Meta description:/i.test(draft) || (!/H1:/i.test(draft) && h1Offset < 0)) {
    failures.push(`${page.slug}: missing title, meta description, or H1 metadata`);
  }
  if (!/## Getting Started/i.test(visitorCopy)) failures.push(`${page.slug}: missing Getting Started section`);

  console.log(`${page.slug}\t${wordCount} words\tgrade ${readingGrade.toFixed(1)}\tkeywords present`);
}

if (failures.length) {
  console.error(`\n${failures.length} verification failure(s):`);
  failures.forEach((failure) => console.error(`- ${failure}`));
  process.exit(1);
}

console.log(`\nVerified ${pages.length} Services and Specialties drafts.`);
