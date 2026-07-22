#!/usr/bin/env node

import { spawn } from 'node:child_process';
import { randomUUID } from 'node:crypto';
import {
  existsSync,
  mkdirSync,
  realpathSync,
  writeFileSync,
} from 'node:fs';
import { mkdir, writeFile } from 'node:fs/promises';
import path from 'node:path';

const WIDTHS = [319, 360, 390, 600, 768, 1024, 1292, 1440];
const ACCEPTED_RUNTIME_ROOT = '/srv/codex/services/lmhg-blockwp-wordpress-mariadb';
const ACCEPTED_COMPOSE_FILE = `${ACCEPTED_RUNTIME_ROOT}/compose.yml`;
const ACCEPTED_BASE_URL = 'http://100.116.130.39:8093';
const ACCEPTED_COMPOSE_PROJECT = 'wordpress-2026-mariadb';
const EXPECTED_THEME = 'wordpress-2026';
const EXPECTED_PLUGIN = 'lmhg-site-core';
const EXPECTED_SITE_NAME = 'Louisville Mental Health Group';
const FIXTURE_SENTINEL_META_KEY = '_lmhg_article_runtime_sentinel';
const NONSTANDARD_RUNTIME_CONFIRMATION = 'I_ACCEPT_WORDPRESS_DATABASE_MUTATION';
const OFFICIAL_SOURCES = [
  'https://developer.wordpress.org/cli/commands/post/create/',
  'https://developer.wordpress.org/cli/commands/post/delete/',
  'https://developer.wordpress.org/cli/commands/post/list/',
  'https://developer.wordpress.org/block-editor/reference-guides/core-blocks/core-blocks-text/core-block-paragraph/',
  'https://developer.wordpress.org/block-editor/reference-guides/core-blocks/core-blocks-text/core-block-heading/',
  'https://playwright.dev/docs/api/class-page#page-goto',
];

const args = new Set(process.argv.slice(2));
const supportedArgs = new Set(['--help', '-h']);
const unknownArgs = [...args].filter((arg) => !supportedArgs.has(arg));

function printHelp() {
  console.log(`LMHG conventional Article runtime verifier

Creates one uniquely named, temporary published WordPress Post in the active
OVH/MariaDB development runtime. While the fixture exists, the verifier checks:

  - the Post has a root-level permalink and returns HTTP 200;
  - the rendered Article has exactly one visible H1 and fixture-bound
    BlogPosting schema;
  - /blogs/ has a visible same-origin link to the Post;
  - sitemap_index.xml and post-sitemap.xml agree before, during, and after;
  - H1, ordinary text, a long token, and long link text remain contained at
    319, 360, 390, 600, 768, 1024, 1292, and 1440 CSS pixels.

The fixture is force-deleted in a finally block. Cleanup is successful only
after WP-CLI confirms deletion, the public route is gone, and the Post is no
longer present in post-sitemap.xml. Cleanup failures are fatal and retain the
fixture Post ID in the JSON report.

Usage:
  node scripts/verify-article-runtime.mjs
  node scripts/verify-article-runtime.mjs --help

Environment:
  WP_URL                         WordPress URL
                                   (default: http://100.116.130.39:8093)
  WP_RUNTIME_ROOT                Active runtime root
                                   (default: /srv/codex/services/lmhg-blockwp-wordpress-mariadb)
  WP_COMPOSE_FILE                Active Compose file
                                   (default: <WP_RUNTIME_ROOT>/compose.yml)
  WP_CLI_SERVICE                Compose WP-CLI service (default: cli)
  WP_COMPOSE_PROJECT_NAME       Compose project name
                                   (default: ${ACCEPTED_COMPOSE_PROJECT})
  WP_ARTICLE_RUNTIME_OUTPUT_DIR  JSON report directory
                                   (default: .runtime/inspect/article-runtime-...)
  WP_ARTICLE_HTTP_TIMEOUT_MS     Per-request/browser timeout (default: 30000)
  WP_ARTICLE_POLL_TIMEOUT_MS     Discovery/cleanup timeout (default: 45000)
  WP_ARTICLE_POLL_INTERVAL_MS    Poll interval (default: 750)
  WP_CLI_TIMEOUT_MS             WP-CLI command timeout (default: 120000)
  WP_ARTICLE_SIGNAL_CLEANUP_DEADLINE_MS
                                  Maximum cleanup time after SIGINT/SIGTERM
                                  before an emergency report and exit
                                  (default: 60000)
  CHROME_PATH                   Chromium-compatible executable; uses
                                /usr/bin/google-chrome when present

  LMHG_ARTICLE_UNSAFE_ALLOW_NONSTANDARD_RUNTIME
                                  Set exactly to
                                  ${NONSTANDARD_RUNTIME_CONFIRMATION}
                                  to bypass the fixed development URL/runtime/
                                  Compose/service allowlist. Theme, plugin, and
                                  WP-CLI URL identity checks still apply.

Safety:
  This command intentionally mutates only the development database. It does
  not create a user and does not accept credentials. WP-CLI receives Gutenberg
  content over stdin, not in process arguments. Run database-mutating checks
  serially and only against the accepted development runtime.
`);
}

if (args.has('--help') || args.has('-h')) {
  printHelp();
  process.exit(0);
}

if (unknownArgs.length > 0) {
  console.error(`Unknown option(s): ${unknownArgs.join(', ')}`);
  console.error('Run with --help for usage.');
  process.exit(2);
}

function configurationError(message) {
  console.error(`Configuration error: ${message}`);
  console.error('Run with --help for usage.');
  process.exit(2);
}

function parsePositiveInteger(name, fallback) {
  const raw = process.env[name];
  if (raw === undefined || raw === '') {
    return fallback;
  }
  const value = Number.parseInt(raw, 10);
  if (!Number.isSafeInteger(value) || value <= 0 || String(value) !== raw.trim()) {
    configurationError(`${name} must be a positive integer; received ${JSON.stringify(raw)}`);
  }
  return value;
}

function parseBaseUrl(raw) {
  let parsed;
  try {
    parsed = new URL(raw);
  } catch {
    configurationError(`WP_URL must be an absolute HTTP(S) URL; received ${JSON.stringify(raw)}`);
  }
  if (!['http:', 'https:'].includes(parsed.protocol)) {
    configurationError(`WP_URL must use HTTP or HTTPS; received ${parsed.protocol}`);
  }
  if (parsed.username || parsed.password) {
    configurationError('WP_URL must not contain credentials.');
  }
  if (parsed.search || parsed.hash) {
    configurationError('WP_URL must not contain a query string or fragment.');
  }
  parsed.pathname = parsed.pathname.replace(/\/+$/, '') || '/';
  return parsed.href.replace(/\/+$/, '');
}

const startedAt = new Date();
const timestamp = startedAt.toISOString().replace(/[-:]/g, '').replace(/\..+/, 'Z');
const suffix = randomUUID().replaceAll('-', '').slice(0, 12);
const runtimeRoot = process.env.WP_RUNTIME_ROOT || ACCEPTED_RUNTIME_ROOT;
const baseUrl = parseBaseUrl(process.env.WP_URL || ACCEPTED_BASE_URL);
const base = new URL(`${baseUrl}/`);
const unsafeNonstandardRuntime =
  process.env.LMHG_ARTICLE_UNSAFE_ALLOW_NONSTANDARD_RUNTIME
  === NONSTANDARD_RUNTIME_CONFIRMATION;
const fixture = {
  createAttempted: false,
  title: `LMHG Article Runtime Check ${timestamp}-${suffix}`,
  slug: `lmhg-article-runtime-check-${timestamp.toLowerCase()}-${suffix}`,
  sentinel: `lmhg-article-runtime-${randomUUID()}`,
  postId: null,
  permalink: null,
};
const longToken = `LMHGArticleRuntimeToken${'X'.repeat(112)}${suffix}`;
const longLinkText = `LMHGArticleRuntimeLink${'Y'.repeat(112)}${suffix}`;
const basePath = base.pathname.replace(/\/+$/, '');
const expectedPath = `${basePath}/${fixture.slug}/`.replace(/\/{2,}/g, '/');
const expectedPermalink = new URL(expectedPath, base.origin).href;

const config = {
  baseUrl,
  browserExecutablePath:
    process.env.CHROME_PATH
    || (existsSync('/usr/bin/google-chrome') ? '/usr/bin/google-chrome' : ''),
  cliTimeoutMs: parsePositiveInteger('WP_CLI_TIMEOUT_MS', 120000),
  composeFile: process.env.WP_COMPOSE_FILE || path.join(runtimeRoot, 'compose.yml'),
  composeProjectName:
    process.env.WP_COMPOSE_PROJECT_NAME || ACCEPTED_COMPOSE_PROJECT,
  httpTimeoutMs: parsePositiveInteger('WP_ARTICLE_HTTP_TIMEOUT_MS', 30000),
  outputDir:
    process.env.WP_ARTICLE_RUNTIME_OUTPUT_DIR
    || path.join('.runtime', 'inspect', `article-runtime-${timestamp}-${suffix}`),
  pollIntervalMs: parsePositiveInteger('WP_ARTICLE_POLL_INTERVAL_MS', 750),
  pollTimeoutMs: parsePositiveInteger('WP_ARTICLE_POLL_TIMEOUT_MS', 45000),
  runtimeRoot,
  signalCleanupDeadlineMs: parsePositiveInteger(
    'WP_ARTICLE_SIGNAL_CLEANUP_DEADLINE_MS',
    60000,
  ),
  unsafeNonstandardRuntime,
  wpCliService: process.env.WP_CLI_SERVICE || 'cli',
};

if (!config.wpCliService.trim() || config.wpCliService.startsWith('-')) {
  configurationError('WP_CLI_SERVICE must be a non-option Compose service name.');
}
if (!/^[a-z0-9][a-z0-9_-]*$/.test(config.composeProjectName)) {
  configurationError(
    'WP_COMPOSE_PROJECT_NAME must contain only lowercase letters, digits, underscores, and hyphens.',
  );
}

if (
  process.env.LMHG_ARTICLE_UNSAFE_ALLOW_NONSTANDARD_RUNTIME
  && !config.unsafeNonstandardRuntime
) {
  configurationError(
    'LMHG_ARTICLE_UNSAFE_ALLOW_NONSTANDARD_RUNTIME was set without the exact required confirmation.',
  );
}

const fixtureContent = `<!-- wp:paragraph {"className":"wp2026-lead"} -->
<p class="wp2026-lead">This temporary Article verifies the LMHG development publishing path. It contains no client or clinical information.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Runtime verification fixture</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>The verifier confirms public discovery, structured data, sitemap membership, and responsive text containment before deleting this Post.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"lmhg-runtime-long-token"} -->
<p class="lmhg-runtime-long-token">${longToken}</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"lmhg-runtime-long-link"} -->
<p class="lmhg-runtime-long-link"><a href="${siteUrl('/contact/')}">${longLinkText}</a></p>
<!-- /wp:paragraph -->`;

const summary = {
  schemaVersion: 1,
  startedAt: startedAt.toISOString(),
  finishedAt: null,
  status: 'running',
  config: {
    baseUrl: config.baseUrl,
    browserExecutablePath: config.browserExecutablePath || 'playwright-managed',
    composeFile: config.composeFile,
    composeProjectName: config.composeProjectName,
    httpTimeoutMs: config.httpTimeoutMs,
    pollIntervalMs: config.pollIntervalMs,
    pollTimeoutMs: config.pollTimeoutMs,
    runtimeRoot: config.runtimeRoot,
    signalCleanupDeadlineMs: config.signalCleanupDeadlineMs,
    unsafeNonstandardRuntime: config.unsafeNonstandardRuntime,
    widths: WIDTHS,
    wpCliService: config.wpCliService,
  },
  fixture: {
    title: fixture.title,
    slug: fixture.slug,
    sentinelMetaKey: FIXTURE_SENTINEL_META_KEY,
    sentinel: fixture.sentinel,
    expectedPermalink,
    postId: null,
    permalink: null,
    postType: 'post',
    postStatus: 'publish',
    blockTypes: [
      'core/paragraph',
      'core/heading',
      'core/paragraph',
      'core/paragraph',
      'core/paragraph',
    ],
  },
  checks: [],
  cleanup: {
    attempted: false,
    ok: null,
    postId: null,
    databaseRemoved: false,
    routeRemoved: false,
    sitemapRemoved: false,
  },
  failures: [],
  interruption: null,
  emergency: null,
  sitemaps: {
    before: null,
    during: null,
    after: null,
  },
  sources: OFFICIAL_SOURCES,
};

const lifecycle = {
  browser: null,
  children: new Set(),
  cleaning: false,
  cleanupDeadlineAt: null,
  cleanupDeadlineTimer: null,
  interruptedBy: null,
};
const verificationAbort = new AbortController();

function emergencyExitAfterCleanupDeadline(signal) {
  const error = `Signal cleanup exceeded ${config.signalCleanupDeadlineMs}ms.`;
  summary.finishedAt = new Date().toISOString();
  summary.status = 'cleanup-deadline-exceeded';
  summary.cleanup.ok = false;
  summary.cleanup.error ||= error;
  summary.emergency = {
    at: summary.finishedAt,
    error,
    postId: fixture.postId,
    sentinel: fixture.sentinel,
    signal,
  };
  summary.failures.push({
    stage: 'signal-cleanup-deadline',
    error,
    postId: fixture.postId,
    slug: fixture.slug,
  });

  for (const child of lifecycle.children) {
    child.kill('SIGKILL');
  }

  try {
    mkdirSync(config.outputDir, { recursive: true });
    const body = `${JSON.stringify(summary, null, 2)}\n`;
    writeFileSync(path.join(config.outputDir, 'summary.json'), body, { mode: 0o600 });
    writeFileSync(path.join(config.outputDir, 'emergency-summary.json'), body, { mode: 0o600 });
    console.error(
      `FATAL: ${error} Emergency report: `
      + path.resolve(config.outputDir, 'emergency-summary.json'),
    );
  } catch (reportError) {
    console.error(`FATAL: ${error} Emergency report failed: ${errorMessage(reportError)}`);
  }

  process.exit(signal === 'SIGINT' ? 130 : 143);
}

for (const signal of ['SIGINT', 'SIGTERM']) {
  process.on(signal, () => {
    if (lifecycle.interruptedBy) {
      console.error(
        `Received another ${signal}; cleanup remains in progress and will not be bypassed.`,
      );
      return;
    }
    lifecycle.interruptedBy = signal;
    lifecycle.cleanupDeadlineAt = Date.now() + config.signalCleanupDeadlineMs;
    summary.interruption = signal;
    console.error(`Received ${signal}; stopping verification and force-deleting the fixture.`);
    verificationAbort.abort(new Error(`Verification interrupted by ${signal}.`));
    lifecycle.cleanupDeadlineTimer = setTimeout(
      () => emergencyExitAfterCleanupDeadline(signal),
      config.signalCleanupDeadlineMs,
    );
    if (!lifecycle.cleaning) {
      for (const child of lifecycle.children) {
        child.kill('SIGTERM');
        setTimeout(() => {
          if (lifecycle.children.has(child)) {
            child.kill('SIGKILL');
          }
        }, 5000).unref();
      }
      void lifecycle.browser?.close().catch(() => {});
    }
  });
}

function errorMessage(error) {
  return error instanceof Error ? error.message : String(error);
}

function safeDetails(value) {
  if (value === undefined) {
    return null;
  }
  return JSON.parse(JSON.stringify(value));
}

function addCheck(name, status, details = null, error = null) {
  const check = { name, status, details: safeDetails(details) };
  if (error) {
    check.error = errorMessage(error);
    summary.failures.push({ stage: name, error: check.error, details: check.details });
  }
  summary.checks.push(check);
  return check;
}

async function captureCheck(name, operation) {
  try {
    const details = await operation();
    addCheck(name, 'passed', details);
    return details;
  } catch (error) {
    addCheck(name, 'failed', null, error);
    return null;
  }
}

function assertCheck(name, condition, message, details = null) {
  if (condition) {
    addCheck(name, 'passed', details);
    return true;
  }
  addCheck(name, 'failed', details, new Error(message));
  return false;
}

async function writeSummary() {
  summary.finishedAt = new Date().toISOString();
  await mkdir(config.outputDir, { recursive: true });
  await writeFile(
    path.join(config.outputDir, 'summary.json'),
    `${JSON.stringify(summary, null, 2)}\n`,
    { mode: 0o600 },
  );
}

function runProcess(command, commandArgs, {
  acceptExitCodes = [0],
  environment = process.env,
  input = '',
  timeoutMs = config.cliTimeoutMs,
} = {}) {
  return new Promise((resolve, reject) => {
    const child = spawn(command, commandArgs, {
      env: environment,
      stdio: ['pipe', 'pipe', 'pipe'],
    });
    lifecycle.children.add(child);
    let stdout = '';
    let stderr = '';
    let timedOut = false;
    let settled = false;

    const settle = (callback) => {
      if (settled) return;
      settled = true;
      clearTimeout(timer);
      lifecycle.children.delete(child);
      callback();
    };
    const timer = setTimeout(() => {
      timedOut = true;
      child.kill('SIGTERM');
      setTimeout(() => child.kill('SIGKILL'), 5000).unref();
    }, timeoutMs);

    child.stdout.setEncoding('utf8');
    child.stderr.setEncoding('utf8');
    child.stdout.on('data', (chunk) => {
      stdout += chunk;
    });
    child.stderr.on('data', (chunk) => {
      stderr += chunk;
    });
    child.on('error', (error) => settle(() => reject(error)));
    child.on('close', (code, signal) => settle(() => {
      const exitCode = Number.isInteger(code) ? code : null;
      if (!timedOut && exitCode !== null && acceptExitCodes.includes(exitCode)) {
        resolve({ code: exitCode, stdout, stderr });
        return;
      }
      const reason = timedOut
        ? `timed out after ${timeoutMs}ms`
        : `exited with ${exitCode ?? signal ?? 'unknown status'}`;
      reject(
        new Error(
          `${command} ${commandArgs.join(' ')} ${reason}`
          + `${stderr.trim() ? `: ${stderr.trim().slice(0, 1200)}` : ''}`,
        ),
      );
    }));

    child.stdin.end(input);
  });
}

function wpCli(commandArgs, options = {}) {
  const environment = { ...process.env };
  environment.LMHG_MARIADB_RUNTIME_ROOT = config.runtimeRoot;
  delete environment.COMPOSE_FILE;
  delete environment.COMPOSE_PROJECT_NAME;
  delete environment.COMPOSE_PROFILES;
  delete environment.COMPOSE_ENV_FILES;
  delete environment.COMPOSE_PATH_SEPARATOR;
  if (!config.unsafeNonstandardRuntime) {
    environment.DOCKER_HOST = 'unix:///var/run/docker.sock';
    delete environment.DOCKER_CONTEXT;
    delete environment.DOCKER_TLS_VERIFY;
    delete environment.DOCKER_CERT_PATH;
  }
  return runProcess(
    'docker',
    [
      'compose',
      '--project-directory',
      config.runtimeRoot,
      '--project-name',
      config.composeProjectName,
      '-f',
      config.composeFile,
      '--profile',
      'tools',
      'run',
      '--rm',
      '-T',
      '--no-deps',
      config.wpCliService,
      ...commandArgs,
      `--url=${config.baseUrl}`,
      '--no-color',
    ],
    { ...options, environment },
  );
}

function cleanupTimeRemainingMs() {
  if (!lifecycle.cleanupDeadlineAt) {
    return null;
  }
  const remaining = lifecycle.cleanupDeadlineAt - Date.now();
  if (remaining <= 0) {
    throw new Error('Signal cleanup deadline was reached.');
  }
  return remaining;
}

function cleanupCommandOptions(options = {}) {
  const remaining = cleanupTimeRemainingMs();
  if (remaining === null) {
    return options;
  }
  return {
    ...options,
    timeoutMs: Math.max(1, Math.min(options.timeoutMs || config.cliTimeoutMs, remaining)),
  };
}

function siteUrl(relativePath) {
  return new URL(relativePath.replace(/^\/+/, ''), base).href;
}

function cacheBusted(url, label) {
  const result = new URL(url);
  result.searchParams.set('lmhg_runtime_check', `${suffix}-${label}-${Date.now()}`);
  return result.href;
}

async function fetchText(url, { cleanup = false, redirect = 'manual' } = {}) {
  const cleanupRemaining = cleanup ? cleanupTimeRemainingMs() : null;
  const timeoutMs = cleanupRemaining === null
    ? config.httpTimeoutMs
    : Math.max(1, Math.min(config.httpTimeoutMs, cleanupRemaining));
  const timeoutSignal = AbortSignal.timeout(timeoutMs);
  const signal = cleanup
    ? timeoutSignal
    : AbortSignal.any([timeoutSignal, verificationAbort.signal]);
  const response = await fetch(url, {
    cache: 'no-store',
    headers: {
      Accept: '*/*',
      'Cache-Control': 'no-cache',
      'User-Agent': 'lmhg-article-runtime-verifier/1.0',
    },
    redirect,
    signal,
  });
  return {
    body: await response.text(),
    headers: Object.fromEntries(response.headers.entries()),
    status: response.status,
    url: response.url,
  };
}

function delay(milliseconds) {
  return new Promise((resolve) => setTimeout(resolve, milliseconds));
}

async function poll(label, probe, { cleanup = false } = {}) {
  const cleanupRemaining = cleanup ? cleanupTimeRemainingMs() : null;
  const deadline = cleanupRemaining === null
    ? Date.now() + config.pollTimeoutMs
    : Math.min(Date.now() + config.pollTimeoutMs, Date.now() + cleanupRemaining);
  let lastObservation = null;
  let lastError = null;

  while (Date.now() <= deadline) {
    if (!cleanup && verificationAbort.signal.aborted) {
      throw verificationAbort.signal.reason;
    }
    try {
      lastObservation = await probe();
      lastError = null;
      if (lastObservation?.ok) {
        return lastObservation.details ?? lastObservation;
      }
    } catch (error) {
      lastError = errorMessage(error);
    }
    const remaining = deadline - Date.now();
    if (remaining > 0) {
      await delay(Math.min(config.pollIntervalMs, remaining));
    }
  }

  throw new Error(
    `${label} did not pass within ${config.pollTimeoutMs}ms. Last observation: `
    + `${lastError || JSON.stringify(lastObservation)}`,
  );
}

function parsePorcelainPostId(stdout) {
  const porcelain = stdout.trim();
  if (!/^[1-9]\d*$/.test(porcelain)) {
    throw new Error(
      'WP-CLI --porcelain output was not exactly one positive integer Post ID; '
      + `received ${JSON.stringify(stdout.slice(0, 500))}.`,
    );
  }
  const postId = Number.parseInt(porcelain, 10);
  if (!Number.isSafeInteger(postId)) {
    throw new Error(`WP-CLI returned an unsafe numeric Post ID: ${porcelain}.`);
  }
  return postId;
}

async function preflight() {
  if (!existsSync(config.runtimeRoot)) {
    throw new Error(`WP_RUNTIME_ROOT does not exist: ${config.runtimeRoot}`);
  }
  if (!existsSync(config.composeFile)) {
    throw new Error(`WP_COMPOSE_FILE does not exist: ${config.composeFile}`);
  }

  const canonicalRuntimeRoot = realpathSync(config.runtimeRoot);
  const canonicalComposeFile = realpathSync(config.composeFile);
  const canonicalAcceptedRuntimeRoot = realpathSync(ACCEPTED_RUNTIME_ROOT);
  const canonicalAcceptedComposeFile = realpathSync(ACCEPTED_COMPOSE_FILE);
  const standardRuntime = canonicalRuntimeRoot === canonicalAcceptedRuntimeRoot
    && canonicalComposeFile === canonicalAcceptedComposeFile
    && config.baseUrl === ACCEPTED_BASE_URL
    && config.composeProjectName === ACCEPTED_COMPOSE_PROJECT
    && config.wpCliService === 'cli';

  if (!standardRuntime && !config.unsafeNonstandardRuntime) {
    throw new Error(
      'Refusing mutation outside the accepted development runtime. '
      + `Expected root=${canonicalAcceptedRuntimeRoot}, compose=${canonicalAcceptedComposeFile}, `
      + `URL=${ACCEPTED_BASE_URL}, project=${ACCEPTED_COMPOSE_PROJECT}, service=cli; `
      + `observed root=${canonicalRuntimeRoot}, compose=${canonicalComposeFile}, `
      + `URL=${config.baseUrl}, project=${config.composeProjectName}, `
      + `service=${config.wpCliService}.`,
    );
  }
  if (config.unsafeNonstandardRuntime) {
    console.warn(
      'WARNING: explicit unsafe nonstandard-runtime override is active; '
      + 'WordPress identity checks remain mandatory.',
    );
  }

  await mkdir(config.outputDir, { recursive: true });
  await wpCli(['core', 'is-installed']);

  const [{ stdout: homeOutput }, { stdout: siteUrlOutput }, { stdout: themeOutput }] =
    await Promise.all([
      wpCli(['option', 'get', 'home']),
      wpCli(['option', 'get', 'siteurl']),
      wpCli(['theme', 'list', '--status=active', '--field=name']),
    ]);
  await wpCli(['plugin', 'is-active', EXPECTED_PLUGIN]);

  const wpHome = parseBaseUrl(homeOutput.trim());
  const wpSiteUrl = parseBaseUrl(siteUrlOutput.trim());
  const activeThemes = themeOutput.trim().split(/\s+/).filter(Boolean);
  if (wpHome !== config.baseUrl || wpSiteUrl !== config.baseUrl) {
    throw new Error(
      `WP-CLI URL identity mismatch: expected ${config.baseUrl}; `
      + `home=${wpHome}; siteurl=${wpSiteUrl}.`,
    );
  }
  if (activeThemes.length !== 1 || activeThemes[0] !== EXPECTED_THEME) {
    throw new Error(
      `Active theme identity mismatch: expected only ${EXPECTED_THEME}; `
      + `observed ${activeThemes.join(', ') || '(none)'}.`,
    );
  }

  const rootResponse = await fetchText(cacheBusted(siteUrl('/'), 'preflight'));
  if (rootResponse.status !== 200) {
    throw new Error(`WordPress development URL returned HTTP ${rootResponse.status}.`);
  }
  return {
    composeFile: config.composeFile,
    composeProjectName: config.composeProjectName,
    canonicalComposeFile,
    canonicalRuntimeRoot,
    activePlugin: EXPECTED_PLUGIN,
    activeTheme: activeThemes[0],
    runtimeRoot: config.runtimeRoot,
    standardRuntime,
    wordpressStatus: rootResponse.status,
    wpHome,
    wpSiteUrl,
  };
}

async function selectFixtureAuthor() {
  const { stdout } = await wpCli([
    'user',
    'list',
    '--role=administrator',
    '--field=ID',
    '--orderby=ID',
    '--order=ASC',
  ]);
  const tokens = stdout.trim().split(/\s+/).filter(Boolean);
  if (tokens.length === 0 || tokens.some((token) => !/^[1-9]\d*$/.test(token))) {
    throw new Error('The runtime has no administrator ID available to own the temporary Post.');
  }
  const authorIds = tokens.map((token) => Number.parseInt(token, 10));
  if (authorIds.some((authorId) => !Number.isSafeInteger(authorId))) {
    throw new Error('The administrator lookup returned an unsafe numeric ID.');
  }
  return authorIds[0];
}

async function createFixture(authorId) {
  fixture.createAttempted = true;
  // Passing "-" follows WP-CLI's documented stdin path, so block content never
  // appears in the process list. --porcelain returns only the inserted Post ID.
  // Source: https://developer.wordpress.org/cli/commands/post/create/
  const { stdout } = await wpCli(
    [
      'post',
      'create',
      '-',
      '--post_type=post',
      '--post_status=publish',
      `--post_author=${authorId}`,
      `--post_title=${fixture.title}`,
      `--post_name=${fixture.slug}`,
      '--post_excerpt=Temporary LMHG development Article runtime verification fixture.',
      '--comment_status=closed',
      '--ping_status=closed',
      `--meta_input=${JSON.stringify({ [FIXTURE_SENTINEL_META_KEY]: fixture.sentinel })}`,
      '--porcelain',
    ],
    { input: `${fixtureContent}\n` },
  );
  fixture.postId = parsePorcelainPostId(stdout);
  summary.fixture.postId = fixture.postId;
  summary.cleanup.postId = fixture.postId;
  return {
    postId: fixture.postId,
    slug: fixture.slug,
    title: fixture.title,
  };
}

async function discoverPublishedFixture() {
  return poll('Published Post REST discovery', async () => {
    const endpoint = new URL(siteUrl(`/wp-json/wp/v2/posts/${fixture.postId}`));
    endpoint.searchParams.set('context', 'view');
    endpoint.searchParams.set('_fields', 'id,link,slug,status,title,type');
    endpoint.searchParams.set('lmhg_runtime_check', `${suffix}-${Date.now()}`);
    const response = await fetchText(endpoint.href);
    if (response.status !== 200) {
      return { ok: false, details: { status: response.status } };
    }
    const post = JSON.parse(response.body);
    const permalink = new URL(post.link);
    const expectedOrigin = new URL(config.baseUrl).origin;
    const correct = Number(post.id) === fixture.postId
      && post.type === 'post'
      && post.status === 'publish'
      && post.slug === fixture.slug
      && permalink.origin === expectedOrigin
      && permalink.pathname === expectedPath
      && !permalink.search
      && !permalink.hash;
    return {
      ok: correct,
      details: {
        id: Number(post.id),
        link: post.link,
        rootPathExpected: expectedPath,
        rootPathObserved: permalink.pathname,
        slug: post.slug,
        status: post.status,
        type: post.type,
      },
    };
  });
}

async function loadPlaywright() {
  try {
    return await import('playwright');
  } catch (error) {
    throw new Error(`Playwright is unavailable. Run npm install first. ${errorMessage(error)}`);
  }
}

async function launchBrowser() {
  const { chromium } = await loadPlaywright();
  return chromium.launch({
    headless: true,
    ...(config.browserExecutablePath ? { executablePath: config.browserExecutablePath } : {}),
  });
}

async function inspectArticleAtWidths(browser, permalink) {
  const observations = [];

  for (const width of WIDTHS) {
    if (verificationAbort.signal.aborted) {
      throw verificationAbort.signal.reason;
    }
    const context = await browser.newContext({ viewport: { width, height: 900 } });
    try {
      await context.route('**/*', async (route) => {
        const resourceType = route.request().resourceType();
        if (resourceType === 'image' || resourceType === 'media') {
          await route.abort();
          return;
        }
        await route.continue();
      });
      const page = await context.newPage();
      const response = await page.goto(cacheBusted(permalink, `viewport-${width}`), {
        timeout: config.httpTimeoutMs,
        waitUntil: 'domcontentloaded',
      });
      await page.waitForTimeout(150);
      const metrics = await page.evaluate((contract) => {
        const {
          expectedTitle,
          expectedPermalink: articlePermalink,
          expectedSiteName,
          longLinkText: expectedLongLinkText,
          longToken: expectedLongToken,
        } = contract;
        const tolerance = 1;
        const visibleHeadings = [...document.querySelectorAll('main h1')].filter((heading) => {
          const style = getComputedStyle(heading);
          const rect = heading.getBoundingClientRect();
          return style.display !== 'none'
            && style.visibility !== 'hidden'
            && Number.parseFloat(style.opacity) !== 0
            && rect.width > 0
            && rect.height > 0;
        });
        let h1 = { count: visibleHeadings.length, expectedTitle, text: null };

        if (visibleHeadings.length === 1) {
          const heading = visibleHeadings[0];
          const rect = heading.getBoundingClientRect();
          const boundary = heading.parentElement?.getBoundingClientRect() || null;
          const range = document.createRange();
          range.selectNodeContents(heading);
          const lineRects = [...range.getClientRects()]
            .filter((line) => line.width > 0 && line.height > 0);
          const lineTops = [...new Set(
            lineRects.map((line) => Math.round(line.top * 10) / 10),
          )];
          const textLeft = lineRects.length ? Math.min(...lineRects.map((line) => line.left)) : null;
          const textRight = lineRects.length ? Math.max(...lineRects.map((line) => line.right)) : null;
          const style = getComputedStyle(heading);
          const fitViewport = Number.parseFloat(
            heading.style.getPropertyValue('--wp2026-title-fit-vw'),
          );
          const fitContainer = Number.parseFloat(
            heading.style.getPropertyValue('--wp2026-title-fit-cqi'),
          );
          const queryContainer = heading.closest(
            '.wp2026-hero-copy, .wp2026-content-section, main[class*="wp2026-template-"]',
          );
          const containerRect = queryContainer?.getBoundingClientRect();
          const containerStyle = queryContainer ? getComputedStyle(queryContainer) : null;
          const containerWidth = containerRect && containerStyle
            ? containerRect.width
              - (Number.parseFloat(containerStyle.paddingLeft) || 0)
              - (Number.parseFloat(containerStyle.paddingRight) || 0)
            : window.innerWidth;
          const probe = document.createElement('span');
          probe.style.cssText = 'font-size:var(--wp2026-heading-1);position:absolute;visibility:hidden';
          document.body.append(probe);
          const tokenFontSize = Number.parseFloat(getComputedStyle(probe).fontSize);
          probe.remove();
          const expectedFontSize = Math.min(
            tokenFontSize,
            fitViewport * window.innerWidth / 100,
            fitContainer * containerWidth / 100,
          );

          h1 = {
            count: visibleHeadings.length,
            expectedTitle,
            text: heading.textContent?.trim() || '',
            fitted: heading.classList.contains('wp2026-title-fit'),
            hasViewportFit: Boolean(heading.style.getPropertyValue('--wp2026-title-fit-vw')),
            hasContainerFit: Boolean(heading.style.getPropertyValue('--wp2026-title-fit-cqi')),
            fontSize: Number.parseFloat(style.fontSize),
            expectedFontSize,
            fontMatchesContract:
              Number.isFinite(expectedFontSize)
              && Math.abs(Number.parseFloat(style.fontSize) - expectedFontSize) < 0.25,
            whiteSpace: style.whiteSpace,
            lineCount: lineTops.length,
            viewportContained: rect.left >= -tolerance && rect.right <= window.innerWidth + tolerance,
            parentContained: boundary
              ? rect.left >= boundary.left - tolerance && rect.right <= boundary.right + tolerance
              : true,
            textViewportContained: textLeft !== null
              && textLeft >= -tolerance
              && textRight <= window.innerWidth + tolerance,
            textParentContained: textLeft !== null && boundary
              ? textLeft >= boundary.left - tolerance && textRight <= boundary.right + tolerance
              : textLeft !== null,
          };
        }

        const boundarySelector = [
          '.wp-block-group',
          '.wp-block-column',
          '.wp-block-buttons',
          '.wp-block-button',
          '.wp-block-list',
          '.wp-block-image',
          'header',
          'main',
          'footer',
        ].join(',');
        const candidates = document.querySelectorAll([
          'header p', 'header a', 'header button',
          'main p', 'main h1', 'main h2', 'main h3', 'main h4', 'main h5', 'main h6',
          'main li', 'main a', 'main button', 'main figcaption', 'main summary', 'main blockquote',
          'footer p', 'footer a', 'footer button', 'footer li', 'footer h2', 'footer h3',
        ].join(','));
        const textFailures = [];

        for (const element of candidates) {
          if (!element.textContent?.trim()) continue;
          const style = getComputedStyle(element);
          const rect = element.getBoundingClientRect();
          if (
            style.display === 'none'
            || style.visibility === 'hidden'
            || Number.parseFloat(style.opacity) === 0
            || rect.width <= 0
            || rect.height <= 0
          ) continue;

          const range = document.createRange();
          range.selectNodeContents(element);
          const textRects = [...range.getClientRects()].filter(
            (textRect) => textRect.width > 0 && textRect.height > 0,
          );
          if (!textRects.length) continue;

          let boundary = element.closest(boundarySelector);
          if (boundary === element) {
            boundary = element.parentElement?.closest(boundarySelector) || null;
          }
          const boundaryRect = boundary?.getBoundingClientRect() || null;
          const outsideViewport = textRects.some(
            (textRect) => textRect.left < -tolerance
              || textRect.right > window.innerWidth + tolerance,
          );
          const outsideBoundary = boundaryRect
            ? textRects.some(
              (textRect) => textRect.left < boundaryRect.left - tolerance
                || textRect.right > boundaryRect.right + tolerance,
            )
            : false;
          if (outsideViewport || outsideBoundary) {
            textFailures.push({
              tag: element.tagName.toLowerCase(),
              text: element.textContent.trim().replace(/\s+/g, ' ').slice(0, 160),
              outsideViewport,
              outsideBoundary,
              boundary: boundary
                ? `${boundary.tagName.toLowerCase()}.${[...boundary.classList].join('.')}`
                : null,
            });
          }
        }

        const inspectStressElement = (selector, expectedText, requireSameOriginLink = false) => {
          const elements = [...document.querySelectorAll(selector)];
          const observations = elements.map((element) => {
            const style = getComputedStyle(element);
            const rect = element.getBoundingClientRect();
            const visible = style.display !== 'none'
              && style.visibility !== 'hidden'
              && Number.parseFloat(style.opacity) !== 0
              && rect.width > 0
              && rect.height > 0;
            const range = document.createRange();
            range.selectNodeContents(element);
            const textRects = [...range.getClientRects()].filter(
              (textRect) => textRect.width > 0 && textRect.height > 0,
            );
            let boundary = element.closest(boundarySelector);
            if (boundary === element) {
              boundary = element.parentElement?.closest(boundarySelector) || null;
            }
            const boundaryRect = boundary?.getBoundingClientRect() || null;
            const viewportContained = textRects.length > 0 && textRects.every(
              (textRect) => textRect.left >= -tolerance
                && textRect.right <= window.innerWidth + tolerance,
            );
            const boundaryContained = textRects.length > 0 && (!boundaryRect || textRects.every(
              (textRect) => textRect.left >= boundaryRect.left - tolerance
                && textRect.right <= boundaryRect.right + tolerance,
            ));
            let sameOriginLink = null;
            if (requireSameOriginLink) {
              try {
                sameOriginLink = new URL(element.href, document.baseURI).origin === location.origin;
              } catch {
                sameOriginLink = false;
              }
            }
            return {
              boundaryContained,
              sameOriginLink,
              text: element.textContent?.trim() || '',
              textRectCount: textRects.length,
              viewportContained,
              visible,
            };
          });
          return {
            count: elements.length,
            expectedText,
            observations,
            passed: observations.length === 1
              && observations[0].visible
              && observations[0].text === expectedText
              && observations[0].viewportContained
              && observations[0].boundaryContained
              && (!requireSameOriginLink || observations[0].sameOriginLink),
          };
        };

        const schemaTypes = new Set();
        const blogPostingEntities = [];
        const schemaParseErrors = [];
        const visitSchema = (value) => {
          if (Array.isArray(value)) {
            value.forEach(visitSchema);
            return;
          }
          if (!value || typeof value !== 'object') return;
          const types = Array.isArray(value['@type']) ? value['@type'] : [value['@type']];
          for (const type of types) {
            if (typeof type === 'string' && type) schemaTypes.add(type);
          }
          if (types.includes('BlogPosting')) {
            blogPostingEntities.push(value);
          }
          Object.values(value).forEach(visitSchema);
        };
        for (const script of document.querySelectorAll('script[type="application/ld+json"]')) {
          try {
            visitSchema(JSON.parse(script.textContent || ''));
          } catch (error) {
            schemaParseErrors.push(String(error));
          }
        }

        const canonicalArticleUrl = (value) => {
          if (typeof value !== 'string' || !value) return null;
          try {
            const parsed = new URL(value, document.baseURI);
            parsed.hash = '';
            parsed.search = '';
            return parsed.href;
          } catch {
            return null;
          }
        };
        const expectedCanonicalArticleUrl = canonicalArticleUrl(articlePermalink);
        const schemaCandidates = blogPostingEntities.map((entity) => {
          const references = [];
          const collectReferences = (value) => {
            if (typeof value === 'string') {
              references.push(value);
              return;
            }
            if (Array.isArray(value)) {
              value.forEach(collectReferences);
              return;
            }
            if (!value || typeof value !== 'object') return;
            if (typeof value['@id'] === 'string') references.push(value['@id']);
            if (typeof value.url === 'string') references.push(value.url);
          };
          collectReferences(entity['@id']);
          collectReferences(entity.url);
          collectReferences(entity.mainEntityOfPage);
          const canonicalReferences = [...new Set(
            references.map(canonicalArticleUrl).filter(Boolean),
          )];
          const headline = typeof entity.headline === 'string' ? entity.headline : null;
          const acceptedHeadlines = new Set([
            expectedTitle,
            `${expectedTitle} - ${expectedSiteName}`,
          ]);
          return {
            atId: typeof entity['@id'] === 'string' ? entity['@id'] : null,
            canonicalReferences,
            headline,
            matchesFixture: acceptedHeadlines.has(headline)
              && canonicalReferences.includes(expectedCanonicalArticleUrl),
            url: typeof entity.url === 'string' ? entity.url : null,
          };
        });

        return {
          documentOverflow: document.documentElement.scrollWidth
            > document.documentElement.clientWidth + tolerance,
          h1,
          schema: {
            blogPostingCandidates: schemaCandidates,
            fixtureBlogPostingMatches: schemaCandidates.filter(
              (candidate) => candidate.matchesFixture,
            ),
            parseErrors: schemaParseErrors,
            types: [...schemaTypes].sort(),
          },
          stress: {
            longLink: inspectStressElement(
              '.lmhg-runtime-long-link a',
              expectedLongLinkText,
              true,
            ),
            longToken: inspectStressElement(
              '.lmhg-runtime-long-token',
              expectedLongToken,
            ),
          },
          textFailures: textFailures.slice(0, 25),
        };
      }, {
        expectedPermalink: permalink,
        expectedSiteName: EXPECTED_SITE_NAME,
        expectedTitle: fixture.title,
        longLinkText,
        longToken,
      });
      observations.push({
        width,
        httpStatus: response?.status() ?? null,
        metrics,
      });
    } finally {
      await context.close();
    }
  }

  return observations;
}

function h1Failure(observation) {
  const { h1 } = observation.metrics;
  return observation.httpStatus !== 200
    || h1.count !== 1
    || h1.text !== fixture.title
    || !h1.fitted
    || !h1.hasViewportFit
    || !h1.hasContainerFit
    || !h1.fontMatchesContract
    || h1.whiteSpace !== 'nowrap'
    || h1.lineCount !== 1
    || !h1.viewportContained
    || !h1.parentContained
    || !h1.textViewportContained
    || !h1.textParentContained
    || observation.metrics.documentOverflow;
}

function textFailure(observation) {
  return observation.httpStatus !== 200
    || observation.metrics.documentOverflow
    || observation.metrics.textFailures.length > 0;
}

function stressFailure(observation) {
  return observation.httpStatus !== 200
    || !observation.metrics.stress.longToken.passed
    || !observation.metrics.stress.longLink.passed
    || observation.metrics.documentOverflow;
}

async function verifyBlogsDiscovery(browser, permalink) {
  const expected = new URL(permalink);
  const context = await browser.newContext({ viewport: { width: 1292, height: 900 } });
  const page = await context.newPage();
  try {
    return await poll('/blogs/ Article discovery', async () => {
      const response = await page.goto(cacheBusted(siteUrl('/blogs/'), 'blogs'), {
        timeout: config.httpTimeoutMs,
        waitUntil: 'domcontentloaded',
      });
      const discovery = await page.evaluate(({ href, origin, pathname, title }) => ({
        pageOriginMatches: location.origin === origin,
        matches: [...document.querySelectorAll('a[href]')]
          .map((anchor) => {
            let url;
            try {
              url = new URL(anchor.href, document.baseURI);
            } catch {
              return null;
            }
            const style = getComputedStyle(anchor);
            const rect = anchor.getBoundingClientRect();
            return {
              href: url.href,
              canonicalUrl: url.href === href,
              pathname: url.pathname,
              sameOrigin: url.origin === origin,
              text: anchor.textContent?.trim() || '',
              visible: style.display !== 'none'
                && style.visibility !== 'hidden'
                && Number.parseFloat(style.opacity) !== 0
                && rect.width > 0
                && rect.height > 0,
            };
          })
          .filter((link) => link
            && link.pathname === pathname
            && link.canonicalUrl
            && link.text === title
            && link.sameOrigin
            && link.visible)
      }), {
        href: expected.href,
        origin: expected.origin,
        pathname: expected.pathname,
        title: fixture.title,
      });
      return {
        ok: response?.status() === 200
          && discovery.pageOriginMatches
          && discovery.matches.length > 0,
        details: {
          articlePath: expected.pathname,
          blogsStatus: response?.status() ?? null,
          matchingLinks: discovery.matches,
          pageOriginMatches: discovery.pageOriginMatches,
          pageUrl: page.url(),
        },
      };
    });
  } finally {
    await context.close();
  }
}

function decodeXml(value) {
  return value
    .replaceAll('&amp;', '&')
    .replaceAll('&lt;', '<')
    .replaceAll('&gt;', '>')
    .replaceAll('&quot;', '"')
    .replaceAll('&apos;', "'");
}

function sitemapLocations(xml) {
  return [...xml.matchAll(/<loc>\s*([^<]+?)\s*<\/loc>/gi)]
    .map((match) => decodeXml(match[1].trim()));
}

function validateSitemapResponse(response, { allow404, label, rootElement }) {
  if (allow404 && response.status === 404) {
    return {
      contentType: response.headers['content-type'] || null,
      locations: [],
      status: response.status,
    };
  }
  if (response.status !== 200) {
    throw new Error(`${label} returned HTTP ${response.status}.`);
  }
  const contentType = response.headers['content-type'] || '';
  if (!contentType.toLowerCase().includes('xml')) {
    throw new Error(`${label} returned non-XML Content-Type ${JSON.stringify(contentType)}.`);
  }
  if (!new RegExp(`<${rootElement}(?:\\s|>)`, 'i').test(response.body)) {
    throw new Error(`${label} did not contain a <${rootElement}> document root.`);
  }
  return {
    contentType,
    locations: sitemapLocations(response.body),
    status: response.status,
  };
}

async function readSitemapPair(relativePath, label, {
  allow404 = false,
  cleanup = false,
  rootElement,
} = {}) {
  const canonicalUrl = siteUrl(relativePath);
  const [canonicalResponse, cacheBustedResponse] = await Promise.all([
    fetchText(canonicalUrl, { cleanup }),
    fetchText(cacheBusted(canonicalUrl, `${label}-${cleanup ? 'cleanup' : 'active'}`), {
      cleanup,
    }),
  ]);
  const canonical = validateSitemapResponse(canonicalResponse, {
    allow404,
    label: `${label} canonical URL`,
    rootElement,
  });
  const cacheBustedResult = validateSitemapResponse(cacheBustedResponse, {
    allow404,
    label: `${label} cache-busted URL`,
    rootElement,
  });
  const canonicalLocations = [...canonical.locations].sort();
  const cacheBustedLocations = [...cacheBustedResult.locations].sort();
  if (
    canonical.status !== cacheBustedResult.status
    || JSON.stringify(canonicalLocations) !== JSON.stringify(cacheBustedLocations)
  ) {
    throw new Error(
      `${label} canonical and cache-busted responses disagreed: `
      + `canonical status=${canonical.status}, locations=${canonicalLocations.length}; `
      + `cache-busted status=${cacheBustedResult.status}, `
      + `locations=${cacheBustedLocations.length}.`,
    );
  }
  return {
    cacheBusted: cacheBustedResult,
    canonical,
    locations: canonicalLocations,
    status: canonical.status,
  };
}

async function readSitemapIndex({ cleanup = false, label = 'sitemap-index' } = {}) {
  return readSitemapPair('/sitemap_index.xml', label, {
    cleanup,
    rootElement: 'sitemapindex',
  });
}

async function readPostSitemap({ cleanup = false, label = 'post-sitemap' } = {}) {
  return readSitemapPair('/post-sitemap.xml', label, {
    allow404: true,
    cleanup,
    rootElement: 'urlset',
  });
}

function sitemapHasPath(sitemap, pathname) {
  return sitemap.locations.some((location) => {
    try {
      const parsed = new URL(location);
      return parsed.origin === base.origin && parsed.pathname === pathname;
    } catch {
      return false;
    }
  });
}

async function verifySitemapMembership(permalink) {
  const pathname = new URL(permalink).pathname;
  return poll('post-sitemap.xml Article membership', async () => {
    const sitemap = await readPostSitemap({ label: 'post-sitemap-during' });
    return {
      ok: sitemap.status === 200 && sitemapHasPath(sitemap, pathname),
      details: {
        articlePath: pathname,
        locationCount: sitemap.locations.length,
        status: sitemap.status,
      },
    };
  });
}

async function inspectSitemapIndexPhase(phase, {
  cleanup = false,
  expectedPostSitemapIndexed = null,
} = {}) {
  const index = await readSitemapIndex({ cleanup, label: `sitemap-index-${phase}` });
  const postSitemapPath = new URL(siteUrl('/post-sitemap.xml')).pathname;
  const postSitemapIndexed = sitemapHasPath(index, postSitemapPath);
  if (
    expectedPostSitemapIndexed !== null
    && postSitemapIndexed !== expectedPostSitemapIndexed
  ) {
    throw new Error(
      `sitemap_index.xml ${phase} state expected post-sitemap indexed=`
      + `${expectedPostSitemapIndexed}, observed ${postSitemapIndexed}.`,
    );
  }
  return {
    canonicalContentType: index.canonical.contentType,
    locationCount: index.locations.length,
    postSitemapIndexed,
    status: index.status,
  };
}

async function inspectSitemapBaseline() {
  const index = await inspectSitemapIndexPhase('before');
  const postSitemap = await readPostSitemap({ label: 'post-sitemap-before' });
  const postSitemapAvailable = postSitemap.status === 200;
  if (index.postSitemapIndexed !== postSitemapAvailable) {
    throw new Error(
      'The pre-mutation sitemap index and post-sitemap.xml endpoint disagreed: '
      + `indexed=${index.postSitemapIndexed}, endpointStatus=${postSitemap.status}.`,
    );
  }
  return {
    ...index,
    postSitemapAvailable,
    postSitemapLocationCount: postSitemap.locations.length,
    postSitemapStatus: postSitemap.status,
  };
}

async function findFixtureCandidatesBySentinel() {
  // Query by the unique sentinel without WP_Query's post_status filtering.
  // This includes Trash, auto-drafts, and any plugin-registered status while
  // still bootstrapping through the selected WordPress runtime and table prefix.
  const php = [
    'global $wpdb;',
    '$sql = $wpdb->prepare(',
    "'SELECT p.ID, p.post_title, p.post_name, p.post_type, p.post_status '",
    ". 'FROM ' . $wpdb->posts . ' AS p '",
    ". 'INNER JOIN ' . $wpdb->postmeta . ' AS pm ON pm.post_id = p.ID '",
    ". 'WHERE pm.meta_key = %s AND pm.meta_value = %s',",
    `${JSON.stringify(FIXTURE_SENTINEL_META_KEY)},`,
    `${JSON.stringify(fixture.sentinel)}`,
    ');',
    '$rows = $wpdb->get_results($sql, ARRAY_A);',
    'echo wp_json_encode($rows);',
  ].join(' ');
  const { stdout } = await wpCli(['eval', php], cleanupCommandOptions());
  let posts;
  try {
    posts = JSON.parse(stdout || '[]');
  } catch (error) {
    throw new Error(`WP-CLI sentinel lookup returned invalid JSON: ${errorMessage(error)}`);
  }
  if (!Array.isArray(posts)) {
    throw new Error('WP-CLI sentinel lookup did not return a JSON array.');
  }
  const candidates = posts.map((post) => ({
    id: Number.parseInt(String(post.ID), 10),
    postName: post.post_name,
    postStatus: post.post_status,
    postTitle: post.post_title,
    postType: post.post_type,
  }));
  const malformed = candidates.filter(
    (post) => !Number.isSafeInteger(post.id) || post.id <= 0,
  );
  if (malformed.length > 0) {
    throw new Error('WP-CLI sentinel lookup returned a malformed Post ID.');
  }
  const mismatches = candidates.filter(
    (post) => post.postName !== fixture.slug
      || post.postTitle !== fixture.title
      || post.postType !== 'post',
  );
  if (mismatches.length > 0) {
    throw new Error(
      'Sentinel lookup found records whose title, slug, or type did not match the fixture; '
      + 'refusing deletion.',
    );
  }
  return candidates;
}

async function findExactIdentityRecords() {
  const php = [
    'global $wpdb;',
    '$sql = $wpdb->prepare(',
    "'SELECT p.ID, p.post_title, p.post_name, p.post_type, p.post_status '",
    ". 'FROM ' . $wpdb->posts . ' AS p '",
    ". 'WHERE p.post_type = %s AND p.post_name = %s AND p.post_title = %s',",
    '"post",',
    `${JSON.stringify(fixture.slug)},`,
    `${JSON.stringify(fixture.title)}`,
    ');',
    '$rows = $wpdb->get_results($sql, ARRAY_A);',
    'echo wp_json_encode($rows);',
  ].join(' ');
  const { stdout } = await wpCli(['eval', php], cleanupCommandOptions());
  let posts;
  try {
    posts = JSON.parse(stdout || '[]');
  } catch (error) {
    throw new Error(`WP-CLI exact-identity lookup returned invalid JSON: ${errorMessage(error)}`);
  }
  if (!Array.isArray(posts)) {
    throw new Error('WP-CLI exact-identity lookup did not return a JSON array.');
  }
  const matchingPosts = posts
    .filter((post) => post.post_name === fixture.slug
      && post.post_title === fixture.title
      && post.post_type === 'post');
  const postIds = matchingPosts.map((post) => Number.parseInt(String(post.ID), 10));
  if (postIds.some((postId) => !Number.isSafeInteger(postId) || postId <= 0)) {
    throw new Error('WP-CLI exact-identity lookup returned a malformed Post ID.');
  }
  return postIds;
}

async function readPostRecordById(postId) {
  if (!Number.isSafeInteger(postId) || postId <= 0) {
    throw new Error(`Refusing invalid exact Post ID lookup: ${postId}.`);
  }
  const php = [
    `$post = get_post(${postId}, ARRAY_A);`,
    'if (!$post) { echo "null"; return; }',
    '$keys = array_flip(["ID", "post_title", "post_name", "post_type", "post_status"]);',
    'echo wp_json_encode(array_intersect_key($post, $keys));',
  ].join(' ');
  const { stdout } = await wpCli([
    'eval',
    php,
  ], cleanupCommandOptions());
  let record;
  try {
    record = JSON.parse(stdout.trim());
  } catch (error) {
    throw new Error(`Exact-ID lookup returned invalid JSON: ${errorMessage(error)}`);
  }
  if (record === null) {
    return null;
  }
  if (!record || typeof record !== 'object' || Array.isArray(record)) {
    throw new Error('Exact-ID lookup returned an unexpected record shape.');
  }
  return {
    id: Number.parseInt(String(record.ID), 10),
    postName: record.post_name,
    postStatus: record.post_status,
    postTitle: record.post_title,
    postType: record.post_type,
  };
}

async function verifyFixtureIdentityImmediatelyBeforeDelete(postId) {
  const record = await readPostRecordById(postId);
  if (!record) {
    throw new Error(`Temporary Post ${postId} disappeared before identity verification.`);
  }
  const { stdout } = await wpCli([
    'post',
    'meta',
    'get',
    String(postId),
    FIXTURE_SENTINEL_META_KEY,
  ], cleanupCommandOptions());
  const observedSentinel = stdout.trim();
  const mismatches = [];
  if (record.id !== postId) mismatches.push(`ID=${record.id}`);
  if (record.postType !== 'post') mismatches.push(`post_type=${record.postType}`);
  if (record.postTitle !== fixture.title) mismatches.push(`post_title=${record.postTitle}`);
  if (record.postName !== fixture.slug) mismatches.push(`post_name=${record.postName}`);
  if (observedSentinel !== fixture.sentinel) mismatches.push('sentinel mismatch');
  if (mismatches.length > 0) {
    throw new Error(
      `Temporary Post ${postId} failed immediate pre-delete identity verification `
      + `(${mismatches.join(', ')}); refusing force deletion.`,
    );
  }
  return {
    ...record,
    sentinelVerified: true,
  };
}

async function verifyPostRemovedFromDatabase(postId) {
  // A successful get_post() evaluation returns null only when this exact
  // database ID is absent, regardless of post type or any core/custom status.
  // This avoids treating a generic WP-CLI error as proof of deletion.
  const remaining = await readPostRecordById(postId);
  if (remaining) {
    throw new Error(
      `Temporary Post ${postId} still exists after force deletion as `
      + `${remaining.postType}/${remaining.postStatus}.`,
    );
  }
  return true;
}

async function verifyRoutesRemoved(urls) {
  const uniqueUrls = [...new Set(urls.filter(Boolean))];
  return poll('Temporary Article route removal', async () => {
    const observations = [];
    for (const url of uniqueUrls) {
      const response = await fetchText(cacheBusted(url, 'route-cleanup'), { cleanup: true });
      observations.push({ url, status: response.status });
    }
    return {
      ok: observations.every(({ status }) => status === 404 || status === 410),
      details: { observations },
    };
  }, { cleanup: true });
}

async function verifySitemapRemoved(paths, expectedStatus) {
  const uniquePaths = [...new Set(paths.filter(Boolean))];
  return poll('post-sitemap.xml cleanup', async () => {
    const sitemap = await readPostSitemap({
      cleanup: true,
      label: 'post-sitemap-after',
    });
    const retainedPaths = uniquePaths.filter((pathname) => sitemapHasPath(sitemap, pathname));
    return {
      ok: retainedPaths.length === 0 && sitemap.status === expectedStatus,
      details: {
        expectedStatus,
        locationCount: sitemap.locations.length,
        retainedPaths,
        status: sitemap.status,
      },
    };
  }, { cleanup: true });
}

async function cleanupFixture() {
  lifecycle.cleaning = true;
  summary.cleanup.attempted = fixture.createAttempted;
  if (!fixture.createAttempted) {
    summary.cleanup.ok = true;
    summary.cleanup.reason = 'fixture creation was not attempted';
    lifecycle.cleaning = false;
    return;
  }

  try {
    const sentinelCandidates = await findFixtureCandidatesBySentinel();
    let postIds = fixture.postId
      ? [fixture.postId]
      : sentinelCandidates.map((candidate) => candidate.id);
    postIds = [...new Set(postIds)];
    if (
      fixture.postId
      && sentinelCandidates.some((candidate) => candidate.id !== fixture.postId)
    ) {
      throw new Error(
        'The unique fixture sentinel resolved to a different Post ID; refusing deletion.',
      );
    }
    if (!fixture.postId && postIds.length === 1) {
      fixture.postId = postIds[0];
      summary.fixture.postId = fixture.postId;
      summary.cleanup.postId = fixture.postId;
    }
    if (postIds.length > 1) {
      throw new Error(
        `Fixture cleanup found multiple exact identity matches: ${postIds.join(', ')}.`,
      );
    }

    if (postIds.length === 1) {
      const postId = postIds[0];
      const preDeleteIdentity = await verifyFixtureIdentityImmediatelyBeforeDelete(postId);
      summary.cleanup.preDeleteIdentity = preDeleteIdentity;
      // --force bypasses Trash and removes the Post, its metadata, and terms.
      // Source: https://developer.wordpress.org/cli/commands/post/delete/
      await wpCli(
        ['post', 'delete', String(postId), '--force'],
        cleanupCommandOptions(),
      );
      await verifyPostRemovedFromDatabase(postId);
      summary.cleanup.databaseRemoved = true;
    } else {
      const remaining = await findFixtureCandidatesBySentinel();
      if (remaining.length > 0) {
        throw new Error(
          'Could not resolve the fixture ID for force deletion: '
          + `${remaining.map((candidate) => candidate.id).join(', ')}.`,
        );
      }
      const missingSentinelRecords = await findExactIdentityRecords();
      if (missingSentinelRecords.length > 0) {
        throw new Error(
          'Exact fixture identity records remain without the unique sentinel '
          + `(${missingSentinelRecords.join(', ')}); refusing unsafe deletion.`,
        );
      }
      summary.cleanup.databaseRemoved = true;
      summary.cleanup.reason = 'create command failed before an exact fixture remained';
    }

    const cleanupUrls = [expectedPermalink, fixture.permalink];
    const routeResult = await verifyRoutesRemoved(cleanupUrls);
    summary.cleanup.routeRemoved = true;
    summary.cleanup.route = routeResult;

    const cleanupPaths = cleanupUrls.filter(Boolean).map((url) => new URL(url).pathname);
    const expectedPostSitemapStatus = summary.sitemaps.before?.postSitemapStatus;
    if (![200, 404].includes(expectedPostSitemapStatus)) {
      throw new Error('The pre-mutation post-sitemap.xml baseline is unavailable.');
    }
    const sitemapResult = await verifySitemapRemoved(
      cleanupPaths,
      expectedPostSitemapStatus,
    );
    summary.cleanup.sitemapRemoved = true;
    summary.cleanup.sitemap = sitemapResult;

    const expectedPostSitemapIndexed = summary.sitemaps.before?.postSitemapIndexed;
    if (typeof expectedPostSitemapIndexed !== 'boolean') {
      throw new Error('The pre-mutation sitemap index baseline is unavailable.');
    }
    const sitemapIndexAfter = await poll('sitemap_index.xml cleanup state', async () => {
      try {
        const details = await inspectSitemapIndexPhase('after', {
          cleanup: true,
          expectedPostSitemapIndexed,
        });
        return { ok: true, details };
      } catch (error) {
        return { ok: false, details: { error: errorMessage(error) } };
      }
    }, { cleanup: true });
    const postSitemapAvailableAfter = sitemapResult.status === 200;
    if (sitemapIndexAfter.postSitemapIndexed !== postSitemapAvailableAfter) {
      throw new Error(
        'The restored sitemap index and post-sitemap.xml endpoint disagree: '
        + `indexed=${sitemapIndexAfter.postSitemapIndexed}, `
        + `endpointStatus=${sitemapResult.status}.`,
      );
    }
    summary.sitemaps.after = {
      ...sitemapIndexAfter,
      postSitemapAvailable: postSitemapAvailableAfter,
      postSitemapStatus: sitemapResult.status,
    };
    addCheck('sitemap-index-after', 'passed', summary.sitemaps.after);
    summary.cleanup.ok = true;
  } catch (error) {
    summary.cleanup.ok = false;
    summary.cleanup.postId = fixture.postId;
    summary.cleanup.error = errorMessage(error);
    summary.failures.push({
      stage: 'fixture-cleanup',
      error: summary.cleanup.error,
      postId: fixture.postId,
      slug: fixture.slug,
    });
    console.error(
      `FATAL: temporary Post cleanup failed (Post ID ${fixture.postId ?? 'unknown'}): `
      + summary.cleanup.error,
    );
  } finally {
    lifecycle.cleaning = false;
  }
}

let executionError = null;

try {
  const preflightResult = await preflight();
  addCheck('preflight', 'passed', preflightResult);

  const authorId = await selectFixtureAuthor();
  addCheck('fixture-author', 'passed', { authorId });

  const sitemapBaseline = await inspectSitemapBaseline();
  summary.sitemaps.before = sitemapBaseline;
  addCheck('sitemap-index-before', 'passed', sitemapBaseline);

  const createResult = await createFixture(authorId);
  addCheck('fixture-created', 'passed', createResult);
  console.log(`Created temporary published Post ${fixture.postId}.`);

  const published = await captureCheck('root-permalink', discoverPublishedFixture);
  if (published?.link) {
    fixture.permalink = published.link;
  }
  fixture.permalink ||= expectedPermalink;
  summary.fixture.permalink = fixture.permalink;

  await captureCheck('public-http-200', async () => {
    const response = await fetchText(cacheBusted(fixture.permalink, 'public-route'));
    if (response.status !== 200) {
      throw new Error(`Temporary Article returned HTTP ${response.status}, expected 200.`);
    }
    if (!response.headers['content-type']?.includes('text/html')) {
      throw new Error(`Temporary Article returned unexpected Content-Type ${response.headers['content-type']}.`);
    }
    return { contentType: response.headers['content-type'], status: response.status };
  });

  try {
    lifecycle.browser = await launchBrowser();
    const observations = await inspectArticleAtWidths(lifecycle.browser, fixture.permalink);
    const h1Failures = observations.filter(h1Failure);
    const textFailures = observations.filter(textFailure);
    const stressFailures = observations.filter(stressFailure);
    const schemas = observations.map(({ width, metrics }) => ({
      width,
      ...metrics.schema,
    }));
    const schemaFailures = schemas.filter(
      (schema) => schema.parseErrors.length > 0
        || schema.fixtureBlogPostingMatches.length === 0,
    );

    assertCheck(
      'one-visible-h1',
      observations.every(({ metrics }) => metrics.h1.count === 1 && metrics.h1.text === fixture.title),
      'The temporary Article did not render exactly one visible, title-matching H1 at every width.',
      {
        failures: observations
          .filter(({ metrics }) => metrics.h1.count !== 1 || metrics.h1.text !== fixture.title),
        widths: WIDTHS,
      },
    );
    assertCheck(
      'blogposting-schema',
      schemaFailures.length === 0,
      'The temporary Article did not expose valid, fixture-bound BlogPosting JSON-LD at every width.',
      { failures: schemaFailures, schemas },
    );
    assertCheck(
      'responsive-h1',
      h1Failures.length === 0,
      'The temporary Article failed the responsive single-line H1 contract.',
      { audited: observations.length, failures: h1Failures, widths: WIDTHS },
    );
    assertCheck(
      'responsive-text',
      textFailures.length === 0,
      'The temporary Article failed responsive text containment.',
      { audited: observations.length, failures: textFailures, widths: WIDTHS },
    );
    assertCheck(
      'long-token-link-containment',
      stressFailures.length === 0,
      'The long unbroken token or same-origin link text escaped its container.',
      { audited: observations.length, failures: stressFailures, widths: WIDTHS },
    );

    await captureCheck(
      'blogs-discovery',
      () => verifyBlogsDiscovery(lifecycle.browser, fixture.permalink),
    );
  } catch (error) {
    addCheck('browser-runtime-inspection', 'failed', null, error);
  } finally {
    await lifecycle.browser?.close().catch(() => {});
    lifecycle.browser = null;
  }

  const sitemapIndexDuring = await captureCheck('sitemap-index-during', async () => {
    const details = await poll('sitemap_index.xml Article membership', async () => {
      try {
        return {
          ok: true,
          details: await inspectSitemapIndexPhase('during', {
            expectedPostSitemapIndexed: true,
          }),
        };
      } catch (error) {
        return { ok: false, details: { error: errorMessage(error) } };
      }
    });
    summary.sitemaps.during = details;
    return details;
  });
  if (sitemapIndexDuring) {
    summary.sitemaps.during = sitemapIndexDuring;
  }

  await captureCheck(
    'post-sitemap-membership',
    () => verifySitemapMembership(fixture.permalink),
  );

  if (summary.failures.length > 0) {
    executionError = new Error('One or more Article runtime checks failed.');
  }
} catch (error) {
  executionError = error;
  if (!summary.failures.some((failure) => failure.error === errorMessage(error))) {
    summary.failures.push({ stage: 'execution', error: errorMessage(error) });
  }
} finally {
  await lifecycle.browser?.close().catch(() => {});
  lifecycle.browser = null;
  await cleanupFixture();
  if (lifecycle.cleanupDeadlineTimer) {
    clearTimeout(lifecycle.cleanupDeadlineTimer);
    lifecycle.cleanupDeadlineTimer = null;
  }

  if (!summary.cleanup.ok) {
    summary.status = 'cleanup-failed';
  } else if (lifecycle.interruptedBy) {
    summary.status = 'interrupted';
  } else if (executionError || summary.failures.length > 0) {
    summary.status = 'failed';
  } else {
    summary.status = 'passed';
  }

  try {
    await writeSummary();
  } catch (error) {
    summary.status = 'failed';
    executionError ||= error;
    console.error(`Could not write the JSON report: ${errorMessage(error)}`);
  }
}

const reportPath = path.resolve(config.outputDir, 'summary.json');
console.log(`Article runtime verification: ${summary.status}`);
console.log(`JSON report: ${reportPath}`);

if (executionError) {
  console.error(errorMessage(executionError));
}
if (!summary.cleanup.ok) {
  console.error(`Cleanup is incomplete for temporary Post ID ${fixture.postId ?? 'unknown'}.`);
}

if (summary.status !== 'passed') {
  process.exitCode = lifecycle.interruptedBy === 'SIGINT'
    ? 130
    : lifecycle.interruptedBy === 'SIGTERM'
      ? 143
      : 1;
}
