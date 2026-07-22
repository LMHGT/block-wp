#!/usr/bin/env node

import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';

const pageDataUrl = new URL('../wp2026-page-data.json', import.meta.url);
const pageData = JSON.parse(await readFile(pageDataUrl, 'utf8'));
const pages = Array.isArray(pageData.pages) ? pageData.pages : [];
const attachment = pages.find((page) => page.path === '/attachment-therapy/');

assert.equal(pages.length, 54, 'The source page inventory must retain all 54 records.');
assert.ok(attachment, 'Attachment Therapy must remain in the source page inventory.');
assert.equal(
  attachment.seo?.title,
  'Parent-Child Attachment Therapy Louisville KY | LMHG',
  'Attachment Therapy source metadata must match the current migration and live database.',
);

console.log('PASS: page-data authority contract');
