#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const host = process.env.WP2026_HOST || '100.70.222.25';
const port = process.env.WP2026_PORT || '8093';
const baseUrl = process.env.WP2026_URL || `http://${host}:${port}`;

function readCoreVersion() {
  const versionPath = path.join(root, 'wordpress', 'wp-includes', 'version.php');
  if (!fs.existsSync(versionPath)) return null;
  const text = fs.readFileSync(versionPath, 'utf8');
  return text.match(/\$wp_version\s*=\s*'([^']+)'/)?.[1] || null;
}

async function get(url) {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 8000);
  try {
    const res = await fetch(url, { signal: controller.signal });
    const text = await res.text();
    return { ok: res.ok, status: res.status, bytes: text.length, text: text.slice(0, 500) };
  } finally {
    clearTimeout(timeout);
  }
}

const packageLockPath = path.join(root, 'package-lock.json');
const packageLock = fs.existsSync(packageLockPath) ? JSON.parse(fs.readFileSync(packageLockPath, 'utf8')) : null;
const playgroundVersion = packageLock?.packages?.['node_modules/@wp-playground/cli']?.version || null;
const home = await get(baseUrl);
const rest = await get(baseUrl.replace(/\/$/, '') + '/wp-json/');
const generator = home.text.match(/<meta name="generator" content="([^"]+)"/)?.[1] || null;
const result = {
  status: home.ok && rest.ok ? 'ok' : 'failed',
  url: baseUrl,
  transport: 'WordPress Playground CLI over HTTP',
  docker: false,
  playgroundCliVersion: playgroundVersion,
  wordpressCoreVersion: readCoreVersion(),
  generator,
  home: { ok: home.ok, status: home.status, bytes: home.bytes },
  rest: { ok: rest.ok, status: rest.status, bytes: rest.bytes },
  pluginTooling: 'wp-gutenberg-designer@personal recorded in .wp-gutenberg-designer/project.json'
};
console.log(JSON.stringify(result, null, 2));
if (result.status !== 'ok') process.exit(1);
