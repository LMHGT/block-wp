#!/usr/bin/env node
import { spawn } from 'node:child_process';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, '..');
const wordpressDir = path.join(root, 'wordpress');
const bin = path.join(root, 'node_modules', '.bin', 'wp-playground-cli');
const host = process.env.WP2026_HOST || '100.70.222.25';
const port = process.env.WP2026_PORT || '8093';
const wp = process.env.WP2026_WP || 'latest';
const php = process.env.WP2026_PHP || '8.3';
const siteUrl = process.env.WP2026_URL || `http://${host}:${port}`;
const installMode = process.env.WP2026_INSTALL_MODE || 'install-from-existing-files-if-needed';
const verbosity = process.env.WP2026_VERBOSITY || 'normal';

const args = [
  'server',
  '--wp', wp,
  '--php', php,
  '--port', port,
  '--site-url', siteUrl,
  '--mount-before-install', `${wordpressDir}:/wordpress`,
  '--wordpress-install-mode', installMode,
  '--login=false',
  '--verbosity', verbosity
];

const child = spawn(bin, args, { cwd: root, stdio: 'inherit', env: process.env });
child.on('exit', (code, signal) => {
  if (signal) process.kill(process.pid, signal);
  process.exit(code ?? 0);
});
