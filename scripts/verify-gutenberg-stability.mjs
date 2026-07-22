#!/usr/bin/env node

import { spawn } from 'node:child_process';
import { randomBytes, randomUUID } from 'node:crypto';
import { existsSync } from 'node:fs';
import { chmod, mkdir, writeFile } from 'node:fs/promises';
import { createRequire } from 'node:module';
import path from 'node:path';

const require = createRequire(import.meta.url);
const startedAt = new Date();
const timestamp = startedAt.toISOString().replace(/[-:]/g, '').replace(/\..+/, 'Z');
const args = new Set(process.argv.slice(2));
const supportedArgs = new Set(['--ephemeral-admin', '--headed', '--help', '-h']);
const unknownArgs = [...args].filter((arg) => !supportedArgs.has(arg));

const OFFICIAL_SOURCES = [
  'https://playwright.dev/docs/auth',
  'https://developer.wordpress.org/block-editor/reference-guides/data/data-core-editor/',
  'https://developer.wordpress.org/plugins/javascript/heartbeat-api/',
  'https://developer.wordpress.org/rest-api/using-the-rest-api/pagination/',
  'https://developer.wordpress.org/cli/commands/user/create/',
  'https://developer.wordpress.org/cli/commands/user/delete/',
  'https://developer.wordpress.org/cli/commands/user/list/',
];

const CHILD_TERMINATION_GRACE_MS = 5000;
const BROWSER_CLOSE_TIMEOUT_MS = 5000;
const CLEANUP_PROCESS_TIMEOUT_MS = 30000;

const CONTENT_TYPES = [
  { label: 'Page', postType: 'page', restBase: 'pages' },
  { label: 'Post', postType: 'post', restBase: 'posts' },
];

function printHelp() {
  console.log(`LMHG Gutenberg stability verifier

Scans every published WordPress Page and Post in the block editor. The command
never saves a post. It fails when an editor cannot load, Gutenberg reports an
invalid block, or a block-recovery warning is visible.

Usage:
  WP_ADMIN_USER=<login> WP_ADMIN_PASSWORD=<password> npm run test:gutenberg
  npm run test:gutenberg -- --ephemeral-admin

Options:
  --ephemeral-admin  Create a unique temporary administrator through the active
                     Docker Compose WP-CLI service, then delete it in cleanup.
  --headed           Show Chromium while the scan runs. Headless is the default.
  -h, --help         Print this help without contacting WordPress or Docker.

Environment:
  WP_URL                         Development URL (default: http://100.116.130.39:8093)
  WP_ADMIN_USER                  Existing administrator login
  WP_ADMIN_PASSWORD             Existing administrator password
  WP_GUTENBERG_OUTPUT_DIR       JSON/screenshot directory (default: .runtime/inspect/...)
  WP_EDITOR_TIMEOUT_MS          Navigation/editor timeout (default: 60000)
  WP_EDITOR_SETTLE_MS           Delay after the block store loads (default: 750)
  CHROME_PATH                   Chromium-compatible browser executable; uses
                                /usr/bin/google-chrome when present
  WP_RUNTIME_ROOT               Runtime root used by --ephemeral-admin
                                  (default: /srv/codex/services/lmhg-blockwp-wordpress-mariadb)
  WP_COMPOSE_FILE               Active Compose file used by --ephemeral-admin
                                  (default: <WP_RUNTIME_ROOT>/compose.yml)
  WP_CLI_SERVICE                Compose WP-CLI service (default: cli)

Security:
  Credentials are read only from the environment and are never written to the
  report. Browser authentication state remains in memory. Ephemeral passwords
  are sent to WP-CLI over stdin, not in process arguments.
  A browser-context guard allows reads and the exact wp-login.php POST only;
  every other same-origin non-idempotent request is blocked. Unexpected writes
  fail the run.
  Periodic admin heartbeat/post-lock polls are also blocked and reported, but
  are classified as expected and nonfatal because blocking prevents mutation.
  Session cookies remain in memory and GET/HEAD/OPTIONS reads remain enabled.
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

const runtimeRoot = process.env.WP_RUNTIME_ROOT || '/srv/codex/services/lmhg-blockwp-wordpress-mariadb';
const externalUser = process.env.WP_ADMIN_USER || '';
const externalPassword = process.env.WP_ADMIN_PASSWORD || process.env.WP_ADMIN_PASS || '';
const ephemeralAdminRequested = args.has('--ephemeral-admin');

if (Boolean(externalUser) !== Boolean(externalPassword)) {
  configurationError('WP_ADMIN_USER and WP_ADMIN_PASSWORD must be provided together.');
}

if (ephemeralAdminRequested && (externalUser || externalPassword)) {
  configurationError('--ephemeral-admin cannot be combined with WP_ADMIN_USER/WP_ADMIN_PASSWORD.');
}

if (!ephemeralAdminRequested && !externalUser) {
  configurationError(
    'Administrator authentication is required. Set WP_ADMIN_USER and WP_ADMIN_PASSWORD, '
      + 'or explicitly use --ephemeral-admin.',
  );
}

const config = {
  baseUrl: (process.env.WP_URL || 'http://100.116.130.39:8093').replace(/\/+$/, ''),
  browserExecutablePath:
    process.env.CHROME_PATH
    || (existsSync('/usr/bin/google-chrome') ? '/usr/bin/google-chrome' : ''),
  composeFile: process.env.WP_COMPOSE_FILE || path.join(runtimeRoot, 'compose.yml'),
  editorSettleMs: parsePositiveInteger('WP_EDITOR_SETTLE_MS', 750),
  editorTimeoutMs: parsePositiveInteger('WP_EDITOR_TIMEOUT_MS', 60000),
  headed: args.has('--headed'),
  outputDir:
    process.env.WP_GUTENBERG_OUTPUT_DIR
    || path.join('.runtime', 'inspect', `gutenberg-stability-${timestamp}`),
  runtimeRoot,
  wpCliService: process.env.WP_CLI_SERVICE || 'cli',
};

const summary = {
  schemaVersion: 1,
  startedAt: startedAt.toISOString(),
  finishedAt: null,
  status: 'running',
  config: {
    authMode: ephemeralAdminRequested ? 'ephemeral-admin' : 'environment',
    baseUrl: config.baseUrl,
    browserExecutablePath: config.browserExecutablePath || 'playwright-managed',
    contentTypes: CONTENT_TYPES.map(({ postType }) => postType),
    editorSettleMs: config.editorSettleMs,
    editorTimeoutMs: config.editorTimeoutMs,
    headed: config.headed,
    readOnlyGuard: 'same-origin writes blocked except exact wp-login.php POST',
  },
  inventory: {
    page: 0,
    post: 0,
    total: 0,
  },
  counts: {
    scanned: 0,
    passed: 0,
    failed: 0,
  },
  cleanup: ephemeralAdminRequested
    ? { attempted: false, ok: null, username: null, userId: null, locatedUserId: null }
    : { attempted: false, ok: true, reason: 'external administrator supplied' },
  readOnlyGuard: {
    expectedBlocked: { count: 0, requests: [] },
    fatalBlocked: { count: 0, requests: [] },
  },
  failures: [],
  results: [],
  sources: OFFICIAL_SOURCES,
};

const lifecycle = {
  abortController: new AbortController(),
  browser: null,
  browserClosePromise: null,
  children: new Set(),
  interruptedBy: null,
  receivedSignals: [],
};

async function closeBrowserBounded() {
  if (!lifecycle.browser) {
    return;
  }
  if (!lifecycle.browserClosePromise) {
    const browser = lifecycle.browser;
    let timeout;
    const timeoutPromise = new Promise((resolve) => {
      timeout = setTimeout(resolve, BROWSER_CLOSE_TIMEOUT_MS);
    });
    lifecycle.browserClosePromise = Promise.race([
      browser.close().catch(() => {}),
      timeoutPromise,
    ]).finally(() => {
      clearTimeout(timeout);
      if (lifecycle.browser === browser) {
        lifecycle.browser = null;
      }
      lifecycle.browserClosePromise = null;
    });
  }
  await lifecycle.browserClosePromise;
}

for (const signal of ['SIGINT', 'SIGTERM']) {
  process.on(signal, () => {
    lifecycle.receivedSignals.push(signal);
    if (!lifecycle.interruptedBy) {
      lifecycle.interruptedBy = signal;
      lifecycle.abortController.abort(new Error(`Received ${signal}`));
      console.error(`Received ${signal}; stopping after browser shutdown and administrator cleanup.`);
    } else {
      console.error(`Received ${signal} again; bounded administrator cleanup will still run.`);
    }

    for (const processHandle of lifecycle.children) {
      if (!processHandle.cleanupSafe) {
        processHandle.terminate(`received ${signal}`);
      }
    }
    void closeBrowserBounded();
  });
}

function errorMessage(error) {
  return error instanceof Error ? error.message : String(error);
}

function redact(value, secrets = []) {
  let rendered = String(value || '');
  for (const secret of secrets) {
    if (secret) {
      rendered = rendered.split(secret).join('[REDACTED]');
    }
  }
  return rendered;
}

function abortableDelay(milliseconds) {
  return new Promise((resolve, reject) => {
    const signal = lifecycle.abortController.signal;
    if (signal.aborted) {
      reject(signal.reason || new Error('Verifier interrupted.'));
      return;
    }

    const timer = setTimeout(() => {
      signal.removeEventListener('abort', onAbort);
      resolve();
    }, milliseconds);
    const onAbort = () => {
      clearTimeout(timer);
      reject(signal.reason || new Error('Verifier interrupted.'));
    };
    signal.addEventListener('abort', onAbort, { once: true });
  });
}

async function writeSummary() {
  await mkdir(config.outputDir, { recursive: true });
  const summaryPath = path.join(config.outputDir, 'summary.json');
  await writeFile(
    summaryPath,
    `${JSON.stringify(summary, null, 2)}\n`,
    { mode: 0o600 },
  );
  await chmod(summaryPath, 0o600);
}

function runProcess(
  command,
  commandArgs,
  {
    cleanupSafe = false,
    input = '',
    timeoutMs = 120000,
    secrets = [],
  } = {},
) {
  return new Promise((resolve, reject) => {
    const child = spawn(command, commandArgs, {
      env: process.env,
      stdio: ['pipe', 'pipe', 'pipe'],
    });
    let stdout = '';
    let stderr = '';
    let settled = false;
    let terminationReason = '';
    let killTimer = null;

    const finalize = (callback) => {
      if (settled) {
        return;
      }
      settled = true;
      clearTimeout(timeoutTimer);
      clearTimeout(killTimer);
      lifecycle.abortController.signal.removeEventListener('abort', onAbort);
      lifecycle.children.delete(processHandle);
      callback();
    };
    const terminate = (reason) => {
      if (!terminationReason) {
        terminationReason = reason;
      }
      if (child.exitCode !== null || child.signalCode !== null) {
        return;
      }
      try {
        child.kill('SIGTERM');
      } catch {
        // The close/error event below remains the authoritative process result.
      }
      if (!killTimer) {
        killTimer = setTimeout(() => {
          if (child.exitCode === null && child.signalCode === null) {
            try {
              child.kill('SIGKILL');
            } catch {
              // A concurrent close may make the process unavailable here.
            }
          }
        }, CHILD_TERMINATION_GRACE_MS);
      }
    };
    const processHandle = { child, cleanupSafe, terminate };
    const onAbort = () => terminate('aborted after verifier interruption');
    const timeoutTimer = setTimeout(
      () => terminate(`timed out after ${timeoutMs}ms`),
      timeoutMs,
    );
    lifecycle.children.add(processHandle);
    if (!cleanupSafe) {
      lifecycle.abortController.signal.addEventListener('abort', onAbort, { once: true });
      if (lifecycle.abortController.signal.aborted) {
        onAbort();
      }
    }

    child.stdout.setEncoding('utf8');
    child.stderr.setEncoding('utf8');
    child.stdout.on('data', (chunk) => {
      stdout += chunk;
    });
    child.stderr.on('data', (chunk) => {
      stderr += chunk;
    });
    child.on('error', (error) => {
      finalize(() => reject(error));
    });
    child.on('close', (code, signal) => {
      const safeStdout = redact(stdout, secrets);
      const safeStderr = redact(stderr, secrets);
      finalize(() => {
        if (code === 0 && !terminationReason) {
          resolve({ stdout: safeStdout, stderr: safeStderr });
          return;
        }
        const reason = terminationReason || `exited with ${code ?? signal ?? 'unknown status'}`;
        reject(
          new Error(
            `${command} ${commandArgs.join(' ')} ${reason}`
              + `${safeStderr.trim() ? `: ${safeStderr.trim()}` : ''}`,
          ),
        );
      });
    });

    child.stdin.on('error', () => {
      // EPIPE is expected if a bounded termination races with stdin delivery.
    });
    child.stdin.end(input);
  });
}

function wpCli(commandArgs, options = {}) {
  return runProcess(
    'docker',
    [
      'compose',
      '--project-directory',
      config.runtimeRoot,
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
    ],
    options,
  );
}

function buildEphemeralAdmin() {
  const suffix = randomUUID().replaceAll('-', '').slice(0, 16);
  return {
    createAttempted: false,
    email: `lmhg-gutenberg-${suffix}@example.invalid`,
    password: `${randomBytes(30).toString('base64url')}!Aa8`,
    userId: null,
    username: `lmhg_gutenberg_${suffix}`,
  };
}

async function createEphemeralAdmin(identity) {
  identity.createAttempted = true;
  // WP-CLI supports prompting for selected arguments, which keeps the generated
  // password out of the process list.
  // Source: https://developer.wordpress.org/cli/commands/user/create/
  const { stdout } = await wpCli(
    [
      'user',
      'create',
      identity.username,
      identity.email,
      '--role=administrator',
      '--display_name=LMHG Gutenberg Verifier',
      '--porcelain',
      '--prompt=user_pass',
    ],
    { input: `${identity.password}\n`, secrets: [identity.password] },
  );
  const userId = stdout
    .trim()
    .split(/\s+/)
    .find((token) => /^\d+$/.test(token));
  if (!userId) {
    throw new Error('WP-CLI created the ephemeral administrator but did not return its numeric ID.');
  }
  identity.userId = Number.parseInt(userId, 10);
  summary.cleanup.username = identity.username;
  summary.cleanup.userId = identity.userId;
  console.log(`Created ephemeral administrator ID ${identity.userId}.`);
}

async function findEphemeralAdminId(identity) {
  // An exit-0 empty result means the exact login is absent. Any WP-CLI,
  // Docker, Compose, or database error rejects and is a cleanup failure.
  // Source: https://developer.wordpress.org/cli/commands/user/list/
  const { stdout } = await wpCli(
    ['user', 'list', `--login=${identity.username}`, '--field=ID'],
    {
      cleanupSafe: true,
      secrets: [identity.password],
      timeoutMs: CLEANUP_PROCESS_TIMEOUT_MS,
    },
  );
  const tokens = stdout.trim() ? stdout.trim().split(/\s+/) : [];
  if (tokens.some((token) => !/^\d+$/.test(token)) || tokens.length > 1) {
    throw new Error(
      `Exact-login lookup returned an unexpected ID list for ${identity.username}: `
        + `${tokens.join(', ') || '(empty)'}`,
    );
  }
  return tokens.length === 1 ? Number.parseInt(tokens[0], 10) : null;
}

async function cleanupEphemeralAdmin(identity) {
  if (!identity?.createAttempted) {
    return;
  }

  summary.cleanup.attempted = true;
  summary.cleanup.username = identity.username;
  summary.cleanup.userId = identity.userId;
  try {
    const userId = await findEphemeralAdminId(identity);
    summary.cleanup.locatedUserId = userId;
    if (!userId) {
      summary.cleanup.ok = true;
      summary.cleanup.reason = 'exact-login lookup confirmed the ephemeral user is absent';
      return;
    }
    if (!summary.cleanup.userId) {
      summary.cleanup.userId = userId;
    }
    if (identity.userId && userId !== identity.userId) {
      throw new Error(
        `Exact-login lookup for ${identity.username} returned ID ${userId}; `
          + `creation returned ID ${identity.userId}. Refusing ambiguous deletion.`,
      );
    }

    // Source: https://developer.wordpress.org/cli/commands/user/delete/
    await wpCli(
      ['user', 'delete', String(userId), '--yes'],
      {
        cleanupSafe: true,
        secrets: [identity.password],
        timeoutMs: CLEANUP_PROCESS_TIMEOUT_MS,
      },
    );
    const remainingUserId = await findEphemeralAdminId(identity);
    if (remainingUserId !== null) {
      throw new Error(
        `Exact-login verification still found ${identity.username} as ID ${remainingUserId} after deletion.`,
      );
    }
    summary.cleanup.ok = true;
    summary.cleanup.userId = userId;
    summary.cleanup.reason = 'delete succeeded and exact-login lookup confirmed absence';
    console.log(`Deleted ephemeral administrator ID ${userId}.`);
  } catch (error) {
    summary.cleanup.ok = false;
    summary.cleanup.error = redact(errorMessage(error), [identity.password]);
    summary.failures.push({
      stage: 'ephemeral-admin-cleanup',
      error: summary.cleanup.error,
    });
    console.error(`Ephemeral administrator cleanup failed: ${summary.cleanup.error}`);
  }
}

async function fetchWithLifecycleTimeout(url, options = {}) {
  const controller = new AbortController();
  const lifecycleSignal = lifecycle.abortController.signal;
  const onLifecycleAbort = () => {
    controller.abort(lifecycleSignal.reason || new Error('Verifier interrupted.'));
  };
  const timeout = setTimeout(
    () => controller.abort(new Error(`Fetch timed out after ${config.editorTimeoutMs}ms.`)),
    config.editorTimeoutMs,
  );
  lifecycleSignal.addEventListener('abort', onLifecycleAbort, { once: true });
  if (lifecycleSignal.aborted) {
    onLifecycleAbort();
  }

  try {
    return await fetch(url, { ...options, signal: controller.signal });
  } finally {
    clearTimeout(timeout);
    lifecycleSignal.removeEventListener('abort', onLifecycleAbort);
  }
}

async function fetchPublishedItems(contentType) {
  const items = [];
  let pageNumber = 1;
  let totalPages = 1;

  do {
    const endpoint = new URL(`/wp-json/wp/v2/${contentType.restBase}`, `${config.baseUrl}/`);
    endpoint.searchParams.set('status', 'publish');
    endpoint.searchParams.set('per_page', '100');
    endpoint.searchParams.set('page', String(pageNumber));
    endpoint.searchParams.set('orderby', 'id');
    endpoint.searchParams.set('order', 'asc');
    endpoint.searchParams.set('_fields', 'id,link,slug,status,title,type');

    const response = await fetchWithLifecycleTimeout(endpoint, {
      headers: { 'User-Agent': 'lmhg-gutenberg-stability-check/2.0' },
    });
    const body = await response.text();
    if (!response.ok) {
      throw new Error(
        `Could not enumerate published ${contentType.label}s: HTTP ${response.status} ${body.slice(0, 240)}`,
      );
    }

    const payload = JSON.parse(body);
    if (!Array.isArray(payload)) {
      throw new Error(`Published ${contentType.label} REST response was not an array.`);
    }
    items.push(...payload.map((item) => ({
      ...item,
      expectedPostType: contentType.postType,
      expectedRestBase: contentType.restBase,
    })));

    // WordPress caps collections at 100 records and exposes the remaining page
    // count in X-WP-TotalPages.
    // Source: https://developer.wordpress.org/rest-api/using-the-rest-api/pagination/
    const totalPagesHeader = Number.parseInt(response.headers.get('x-wp-totalpages') || '1', 10);
    totalPages = Number.isSafeInteger(totalPagesHeader) && totalPagesHeader > 0 ? totalPagesHeader : 1;
    pageNumber += 1;
  } while (pageNumber <= totalPages);

  return items;
}

function loadPlaywright() {
  try {
    return require('playwright');
  } catch (error) {
    throw new Error(`Playwright is unavailable. Run npm install first. ${errorMessage(error)}`);
  }
}

async function login(page, credentials) {
  await page.goto(`${config.baseUrl}/wp-login.php`, {
    timeout: config.editorTimeoutMs,
    waitUntil: 'domcontentloaded',
  });
  await page.locator('#user_login').fill(credentials.username);
  await page.locator('#user_pass').fill(credentials.password);
  await Promise.all([
    page.waitForURL((url) => url.pathname.startsWith('/wp-admin'), {
      timeout: config.editorTimeoutMs,
      waitUntil: 'domcontentloaded',
    }),
    page.locator('#wp-submit').click(),
  ]).catch(async (error) => {
    const loginError = await page.locator('#login_error').innerText().catch(() => '');
    throw new Error(loginError.trim() || `WordPress administrator login failed: ${errorMessage(error)}`);
  });

  const authenticatedUrl = new URL(page.url());
  const expectedOrigin = new URL(config.baseUrl).origin;
  if (
    authenticatedUrl.origin !== expectedOrigin
    || !authenticatedUrl.pathname.startsWith('/wp-admin')
  ) {
    throw new Error(
      `Administrator login did not reach same-origin wp-admin; landed on ${page.url()}`,
    );
  }
}

function requestTargetForReport(requestUrl) {
  const restRoute = requestUrl.searchParams.get('rest_route');
  return restRoute === null
    ? requestUrl.pathname
    : `${requestUrl.pathname}?rest_route=${encodeURIComponent(restRoute)}`;
}

function isExpectedHeartbeatRequest(request, requestUrl, method) {
  if (
    method !== 'POST'
    || !requestUrl.pathname.endsWith('/wp-admin/admin-ajax.php')
  ) {
    return false;
  }
  const form = new URLSearchParams(request.postData() || '');
  // Core Heartbeat carries post-lock and nonce-refresh polls through the
  // heartbeat admin-ajax action. We block them to avoid lock mutations but do
  // not turn that expected safety action into a false editor failure.
  // Source: https://developer.wordpress.org/plugins/javascript/heartbeat-api/
  if (form.get('action') !== 'heartbeat') {
    return false;
  }

  const coreHeartbeatFields = new Set([
    '_nonce',
    'action',
    'has_focus',
    'interval',
    'screen_id',
  ]);
  return [...form.keys()].every(
    (field) => coreHeartbeatFields.has(field)
      || /^data\[(?:wp-auth-check|wp-refresh-post-lock|wp-refresh-post-nonces)\](?:\[[^\]]+\])*$/.test(field),
  );
}

async function installReadOnlyRequestBarrier(context) {
  const blockedWrites = [];
  const siteOrigin = new URL(config.baseUrl).origin;
  const loginUrl = new URL(`${config.baseUrl}/wp-login.php`);
  const readMethods = new Set(['GET', 'HEAD', 'OPTIONS']);

  // This method-and-origin guard covers REST requests made through both
  // /wp-json/... and ?rest_route=..., plus admin-ajax, post.php, and future
  // same-origin write endpoints. The only allowed non-idempotent request is
  // the exact wp-login.php POST needed to establish the in-memory session.
  await context.route('**/*', async (route) => {
    const request = route.request();
    const method = request.method().toUpperCase();
    const requestUrl = new URL(request.url());
    const isSameOrigin = requestUrl.origin === siteOrigin;
    const isLoginPost = isSameOrigin
      && method === 'POST'
      && requestUrl.pathname === loginUrl.pathname;

    if (!isSameOrigin || readMethods.has(method) || isLoginPost) {
      await route.continue();
      return;
    }

    const expectedHeartbeat = isExpectedHeartbeatRequest(request, requestUrl, method);
    blockedWrites.push({
      classification: expectedHeartbeat
        ? 'expected-heartbeat-or-post-lock-poll'
        : 'unexpected-write',
      fatal: !expectedHeartbeat,
      method,
      target: requestTargetForReport(requestUrl),
    });
    await route.abort('blockedbyclient');
  });

  return blockedWrites;
}

async function readAuthenticatedRawContent(page, item) {
  return page.evaluate(async ({ id, postType, restBase }) => {
    if (typeof window.wp?.apiFetch !== 'function') {
      throw new Error('wp.apiFetch is unavailable after editor hydration.');
    }

    // Cookie authentication plus WordPress's REST nonce middleware provides a
    // context=edit record. Only lengths leave the page; raw content is never
    // copied into the verifier report.
    const query = new URLSearchParams({
      context: 'edit',
      _fields: 'id,type,content',
    });
    const record = await window.wp.apiFetch({
      path: `/wp/v2/${restBase}/${id}?${query.toString()}`,
      method: 'GET',
    });
    if (Number(record?.id) !== Number(id) || record?.type !== postType) {
      throw new Error(
        `Authenticated REST identity mismatch: expected ${postType} ${id}, `
          + `received ${record?.type ?? 'unknown'} ${record?.id ?? 'unknown'}.`,
      );
    }
    if (typeof record?.content?.raw !== 'string') {
      throw new Error('Authenticated REST context=edit response did not include content.raw.');
    }

    return {
      rawContentLength: record.content.raw.length,
      rawContentNonWhitespaceLength: record.content.raw.trim().length,
      rawContentSource: 'authenticated-rest-context-edit',
    };
  }, {
    id: item.id,
    postType: item.expectedPostType,
    restBase: item.expectedRestBase,
  });
}

async function readEditorHydrationSnapshot(page, item) {
  return page.evaluate(({ id, postType }) => {
    // getCurrentPostAttribute is the core/editor selector for the saved entity
    // value; edited content has separate selectors. Waiting on this value
    // prevents an early, empty block-store snapshot from passing.
    // Source: https://developer.wordpress.org/block-editor/reference-guides/data/data-core-editor/
    const editor = window.wp?.data?.select?.('core/editor');
    const blockEditor = window.wp?.data?.select?.('core/block-editor');
    const currentPost = editor?.getCurrentPost?.();
    const savedContentAttribute = editor?.getCurrentPostAttribute?.('content');
    const savedContent = typeof savedContentAttribute === 'string'
      ? savedContentAttribute
      : typeof savedContentAttribute?.raw === 'string'
        ? savedContentAttribute.raw
        : typeof currentPost?.content === 'string'
          ? currentPost.content
          : typeof currentPost?.content?.raw === 'string'
            ? currentPost.content.raw
            : null;
    const rootBlocks = typeof blockEditor?.getBlocks === 'function'
      ? blockEditor.getBlocks()
      : null;
    const flatten = (blocks) => blocks.flatMap(
      (block) => [block, ...flatten(block.innerBlocks || [])],
    );
    const allBlocks = Array.isArray(rootBlocks) ? flatten(rootBlocks) : [];
    let serializedBlocks = '';
    if (Array.isArray(rootBlocks)) {
      serializedBlocks = typeof window.wp?.blocks?.serialize === 'function'
        ? window.wp.blocks.serialize(rootBlocks)
        : JSON.stringify(rootBlocks.map((block) => ({
          attributes: block.attributes,
          clientId: block.clientId,
          innerBlocks: block.innerBlocks,
          name: block.name,
        })));
    }
    let serializedHash = 2166136261;
    for (let index = 0; index < serializedBlocks.length; index += 1) {
      serializedHash ^= serializedBlocks.charCodeAt(index);
      serializedHash = Math.imul(serializedHash, 16777619);
    }
    const currentPostId = Number(editor?.getCurrentPostId?.());
    const currentPostType = editor?.getCurrentPostType?.();
    const hydrated = currentPostId === Number(id)
      && currentPostType === postType
      && Number(currentPost?.id) === Number(id)
      && savedContent !== null
      && Array.isArray(rootBlocks);

    return {
      blockCount: allBlocks.length,
      currentPostId,
      currentPostType,
      editorSavedContentLength: savedContent?.length ?? null,
      hydrated,
      isAutosaving: Boolean(editor?.isAutosavingPost?.()),
      isSaving: Boolean(editor?.isSavingPost?.()),
      signature: hydrated
        ? [
          currentPostId,
          currentPostType,
          savedContent.length,
          allBlocks.length,
          serializedBlocks.length,
          serializedHash >>> 0,
        ].join(':')
        : null,
    };
  }, { id: item.id, postType: item.expectedPostType });
}

async function waitForStableEditor(page, item, rawContent) {
  const deadline = Date.now() + config.editorTimeoutMs;
  const pollMs = Math.min(250, Math.max(75, Math.floor(config.editorSettleMs / 3)));
  let stableSince = null;
  let stableSamples = 0;
  let previousSignature = null;
  let lastSnapshot = null;

  while (Date.now() < deadline) {
    if (lifecycle.abortController.signal.aborted) {
      throw lifecycle.abortController.signal.reason || new Error('Verifier interrupted.');
    }
    const snapshot = await readEditorHydrationSnapshot(page, item);
    lastSnapshot = snapshot;
    const ready = snapshot.hydrated
      && !snapshot.isSaving
      && !snapshot.isAutosaving
      && snapshot.editorSavedContentLength === rawContent.rawContentLength;

    if (ready && snapshot.signature === previousSignature) {
      stableSamples += 1;
    } else if (ready) {
      stableSince = Date.now();
      stableSamples = 1;
    } else {
      stableSince = null;
      stableSamples = 0;
    }
    previousSignature = ready ? snapshot.signature : null;

    if (
      ready
      && stableSamples >= 3
      && Date.now() - stableSince >= config.editorSettleMs
    ) {
      return snapshot;
    }
    await abortableDelay(pollMs);
  }

  throw new Error(
    'Editor entity/content did not hydrate and reach a stable block state before timeout. '
      + `Last state: ${JSON.stringify(lastSnapshot)}; authenticated raw length: `
      + `${rawContent.rawContentLength}.`,
  );
}

async function scanEditor(page, item, contextBlockedWrites) {
  const editUrl = `${config.baseUrl}/wp-admin/post.php?post=${item.id}&action=edit`;
  const consoleErrors = [];
  const pageErrors = [];
  const blockedWriteStart = contextBlockedWrites.length;
  const onConsole = (message) => {
    if (message.type() === 'error') {
      consoleErrors.push(message.text().slice(0, 500));
    }
  };
  const onPageError = (error) => pageErrors.push(errorMessage(error).slice(0, 500));
  page.on('console', onConsole);
  page.on('pageerror', onPageError);

  const result = {
    id: item.id,
    type: item.expectedPostType,
    slug: item.slug,
    title: item.title?.rendered || '',
    permalink: item.link,
    editUrl,
    status: 'failed',
    reasons: [],
    editor: null,
    diagnostics: {
      blockedContentWrites: [],
      consoleErrors,
      pageErrors,
    },
    screenshot: null,
  };

  try {
    const response = await page.goto(editUrl, {
      timeout: config.editorTimeoutMs,
      waitUntil: 'domcontentloaded',
    });
    if (!response?.ok()) {
      throw new Error(`Editor returned HTTP ${response?.status() ?? 'unknown'}.`);
    }
    if (new URL(page.url()).pathname === '/wp-login.php') {
      throw new Error('Authentication expired and the editor redirected to wp-login.php.');
    }
    if (new URL(page.url()).origin !== new URL(config.baseUrl).origin) {
      throw new Error(`Editor navigation escaped the guarded WordPress origin: ${page.url()}`);
    }

    await page.waitForFunction(
      ({ postId, postType }) => {
        const editor = window.wp?.data?.select?.('core/editor');
        const blockEditor = window.wp?.data?.select?.('core/block-editor');
        return Number(editor?.getCurrentPostId?.()) === Number(postId)
          && editor?.getCurrentPostType?.() === postType
          && typeof blockEditor?.getBlocks === 'function';
      },
      { postId: item.id, postType: item.expectedPostType },
      { timeout: config.editorTimeoutMs },
    );
    const rawContent = await readAuthenticatedRawContent(page, item);
    const stableEditor = await waitForStableEditor(page, item, rawContent);

    result.editor = await page.evaluate(() => {
      const blockEditor = window.wp.data.select('core/block-editor');
      const editor = window.wp.data.select('core/editor');
      const flatten = (blocks, parentNames = []) => blocks.flatMap((block) => {
        const selectorSaysValid = typeof blockEditor.isBlockValid === 'function'
          ? blockEditor.isBlockValid(block.clientId)
          : true;
        const record = {
          clientId: block.clientId,
          name: block.name,
          parentNames,
          isValid:
            block.isValid !== false
            && selectorSaysValid !== false,
        };
        return [record, ...flatten(block.innerBlocks || [], [...parentNames, block.name])];
      });
      const blocks = flatten(blockEditor.getBlocks());
      const warningNodes = [
        ...document.querySelectorAll(
          '.block-editor-warning, .block-editor-block-list__block .components-notice.is-error',
        ),
      ];
      const warningTexts = [...new Set(
        warningNodes
          .map((node) => (node.innerText || node.textContent || '').trim())
          .filter(Boolean),
      )];
      const bodyText = document.body.innerText || '';
      const recoveryTextMatches = [
        'Attempt Block Recovery',
        'This block contains unexpected or invalid content.',
        'This block has encountered an error and cannot be previewed.',
      ].filter((text) => bodyText.includes(text));

      return {
        blockCount: blocks.length,
        postId: Number(editor.getCurrentPostId()),
        postType: editor.getCurrentPostType(),
        invalidBlocks: blocks.filter((block) => !block.isValid),
        recoveryTextMatches,
        warningTexts,
      };
    });
    Object.assign(result.editor, rawContent, {
      editorSavedContentLength: stableEditor.editorSavedContentLength,
      stabilitySamplesRequired: 3,
      stabilityWindowMs: config.editorSettleMs,
    });

    if (result.editor.invalidBlocks.length > 0) {
      result.reasons.push('Gutenberg block store reports invalid blocks.');
    }
    if (
      result.editor.postId !== Number(item.id)
      || result.editor.postType !== item.expectedPostType
    ) {
      result.reasons.push(
        `Editor identity mismatch: expected ${item.expectedPostType} ${item.id}, `
          + `loaded ${result.editor.postType} ${result.editor.postId}.`,
      );
    }
    if (result.editor.recoveryTextMatches.length > 0 || result.editor.warningTexts.length > 0) {
      result.reasons.push('The editor displays a block warning or recovery prompt.');
    }
    if (
      result.editor.rawContentNonWhitespaceLength > 0
      && result.editor.blockCount === 0
    ) {
      result.reasons.push(
        'The authenticated saved content is nonempty, but the stable editor block inventory is empty.',
      );
    }
  } catch (error) {
    result.reasons.push(errorMessage(error));
  } finally {
    result.diagnostics.blockedContentWrites = contextBlockedWrites.slice(blockedWriteStart);
    page.off('console', onConsole);
    page.off('pageerror', onPageError);
  }

  if (result.diagnostics.blockedContentWrites.some(({ fatal }) => fatal)) {
    result.reasons.push('The read-only guard blocked an unexpected same-origin write request.');
  }
  result.status = result.reasons.length === 0 ? 'passed' : 'failed';

  if (result.status === 'failed') {
    const screenshotName = `${result.type}-${result.id}.png`;
    const screenshotPath = path.join(config.outputDir, screenshotName);
    await page.screenshot({ path: screenshotPath, fullPage: true }).catch(() => {});
    result.screenshot = screenshotName;
  }

  return result;
}

async function runScan(credentials) {
  const inventory = await Promise.all(CONTENT_TYPES.map(fetchPublishedItems));
  const items = inventory.flat();
  for (let index = 0; index < CONTENT_TYPES.length; index += 1) {
    summary.inventory[CONTENT_TYPES[index].postType] = inventory[index].length;
  }
  summary.inventory.total = items.length;
  console.log(
    `Discovered ${summary.inventory.page} published Page(s) and ${summary.inventory.post} published Post(s).`,
  );
  if (summary.inventory.total === 0) {
    summary.failures.push({
      stage: 'inventory-zero',
      error: 'Published Page/Post inventory is empty; refusing a vacuous editor pass.',
    });
    return;
  }

  const playwright = loadPlaywright();
  lifecycle.browser = await playwright.chromium.launch({
    headless: !config.headed,
    ...(config.browserExecutablePath ? { executablePath: config.browserExecutablePath } : {}),
  });
  if (lifecycle.abortController.signal.aborted) {
    await closeBrowserBounded();
    throw lifecycle.abortController.signal.reason || new Error('Verifier interrupted.');
  }
  let context = null;
  let contextBlockedWrites = [];

  try {
    context = await lifecycle.browser.newContext({
      serviceWorkers: 'block',
      viewport: { width: 1440, height: 1000 },
    });
    contextBlockedWrites = await installReadOnlyRequestBarrier(context);
    // Authentication cookies stay in this in-memory context and are never
    // saved as storageState. GET/HEAD/OPTIONS remain available so the editor
    // and authenticated context=edit REST reads can hydrate normally.
    // Source: https://playwright.dev/docs/auth
    const page = await context.newPage();
    page.setDefaultTimeout(config.editorTimeoutMs);
    page.setDefaultNavigationTimeout(config.editorTimeoutMs);

    await login(page, credentials);
    console.log('Authenticated to the WordPress development editor.');

    for (const item of items) {
      if (lifecycle.interruptedBy) {
        break;
      }
      const result = await scanEditor(page, item, contextBlockedWrites);
      summary.results.push(result);
      const prefix = result.status === 'passed' ? 'ok' : 'not ok';
      console.log(`${prefix} - ${result.type} ${result.id} ${result.slug || '(no slug)'}`);
      if (result.status === 'failed') {
        summary.failures.push({
          stage: 'editor-scan',
          id: result.id,
          type: result.type,
          slug: result.slug,
          reasons: result.reasons,
          screenshot: result.screenshot,
        });
      }
    }

    summary.counts.scanned = summary.results.length;
    summary.counts.passed = summary.results.filter(({ status }) => status === 'passed').length;
    summary.counts.failed = summary.results.filter(({ status }) => status === 'failed').length;
    if (!lifecycle.interruptedBy && summary.counts.scanned !== summary.inventory.total) {
      summary.failures.push({
        stage: 'inventory-coverage',
        expected: summary.inventory.total,
        scanned: summary.counts.scanned,
      });
    }
  } finally {
    // The context-level route remains installed until context.close() settles,
    // so late non-idempotent requests are still blocked and recorded.
    await context?.close().catch(() => {});
    const expectedBlocked = contextBlockedWrites.filter(({ fatal }) => !fatal);
    const fatalBlocked = contextBlockedWrites.filter(({ fatal }) => fatal);
    summary.readOnlyGuard.expectedBlocked = {
      count: expectedBlocked.length,
      requests: expectedBlocked,
    };
    summary.readOnlyGuard.fatalBlocked = {
      count: fatalBlocked.length,
      requests: fatalBlocked,
    };
    if (fatalBlocked.length > 0) {
      summary.failures.push({
        stage: 'read-only-write-blocked',
        count: fatalBlocked.length,
        requests: fatalBlocked,
      });
    }
    await closeBrowserBounded();
  }
}

async function main() {
  await mkdir(config.outputDir, { recursive: true });
  const ephemeralIdentity = ephemeralAdminRequested ? buildEphemeralAdmin() : null;
  if (ephemeralIdentity) {
    summary.cleanup.username = ephemeralIdentity.username;
  }
  const credentials = ephemeralIdentity
    ? { username: ephemeralIdentity.username, password: ephemeralIdentity.password }
    : { username: externalUser, password: externalPassword };

  try {
    if (ephemeralIdentity) {
      await createEphemeralAdmin(ephemeralIdentity);
    }
    await runScan(credentials);
  } catch (error) {
    const safeError = redact(errorMessage(error), [credentials.password]);
    summary.failures.push({ stage: 'fatal', error: safeError });
    console.error(safeError);
  } finally {
    await closeBrowserBounded();
    await cleanupEphemeralAdmin(ephemeralIdentity);

    summary.finishedAt = new Date().toISOString();
    if (lifecycle.interruptedBy) {
      summary.status = 'interrupted';
      summary.failures.push({
        stage: 'signal',
        signal: lifecycle.interruptedBy,
        receivedSignals: lifecycle.receivedSignals,
      });
    } else {
      summary.status = summary.failures.length === 0 ? 'passed' : 'failed';
    }
    await writeSummary();
    console.log(`summary: ${path.join(config.outputDir, 'summary.json')}`);
  }

  if (lifecycle.interruptedBy === 'SIGINT') {
    process.exitCode = 130;
  } else if (lifecycle.interruptedBy === 'SIGTERM') {
    process.exitCode = 143;
  } else if (summary.status !== 'passed') {
    process.exitCode = 1;
  }
}

await main();
