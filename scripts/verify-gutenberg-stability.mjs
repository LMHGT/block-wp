#!/usr/bin/env node

import { spawn } from 'node:child_process';
import { randomBytes, randomUUID } from 'node:crypto';
import { existsSync } from 'node:fs';
import { chmod, mkdir, unlink, writeFile } from 'node:fs/promises';
import { createRequire } from 'node:module';
import path from 'node:path';

const require = createRequire(import.meta.url);
const startedAt = new Date();
const timestamp = startedAt.toISOString().replace(/[-:]/g, '').replace(/\..+/, 'Z');
const runId = randomUUID();
const args = new Set(process.argv.slice(2));
const supportedArgs = new Set(['--ephemeral-admin', '--headed', '--help', '-h']);
const unknownArgs = [...args].filter((arg) => !supportedArgs.has(arg));

const OFFICIAL_SOURCES = [
  'https://playwright.dev/docs/auth',
  'https://developer.wordpress.org/block-editor/reference-guides/data/data-core-editor/',
  'https://developer.wordpress.org/plugins/javascript/heartbeat-api/',
  'https://developer.wordpress.org/rest-api/using-the-rest-api/pagination/',
  'https://developer.wordpress.org/reference/functions/wp_insert_user/',
  'https://developer.wordpress.org/cli/commands/user/delete/',
  'https://developer.wordpress.org/cli/commands/user/list/',
  'https://developer.wordpress.org/cli/commands/eval-file/',
];

const CHILD_TERMINATION_GRACE_MS = 5000;
const BROWSER_CLOSE_TIMEOUT_MS = 5000;
const CLEANUP_PROCESS_TIMEOUT_MS = 30000;
const WRITE_BODY_LIMIT_BYTES = 64 * 1024;

const CONTENT_TYPES = [
  { label: 'Page', postType: 'page', restBase: 'pages' },
  { label: 'Post', postType: 'post', restBase: 'posts' },
];

function printHelp() {
  console.log(`LMHG Gutenberg stability verifier

Scans every published WordPress Page and Post in the block editor. Browser
network writes are blocked, except for the exact login POST; the command never
saves a post. It fails when an editor cannot load, Gutenberg reports an invalid
block, or a block-recovery warning is visible.

Usage:
  npm run test:gutenberg -- --ephemeral-admin

Options:
  --ephemeral-admin  Create a unique temporary administrator through the active
                     Docker Compose WP-CLI service, then delete it in cleanup.
  --headed           Show Chromium while the scan runs. Headless is the default.
  -h, --help         Print this help without contacting WordPress or Docker.

Environment:
  WP_URL                         Development URL (default: http://100.116.130.39:8093)
  WP_GUTENBERG_OUTPUT_DIR       JSON/screenshot directory (default: .runtime/inspect/...)
  WP_EDITOR_TIMEOUT_MS          Navigation/editor timeout (default: 60000)
  WP_EDITOR_SETTLE_MS           Delay after the block store loads (default: 750)
  CHROME_PATH                   Chromium-compatible browser executable; uses
                                /usr/bin/google-chrome when present
  WP_RUNTIME_ROOT               Runtime root used for WP-CLI lock/admin cleanup
                                  (default: /srv/codex/services/lmhg-blockwp-wordpress-mariadb)
  WP_COMPOSE_FILE               Active Compose file used by WP-CLI
                                  (default: <WP_RUNTIME_ROOT>/compose.yml)
  WP_CLI_SERVICE                Compose WP-CLI service (default: cli)

Security:
  A unique administrator is required so _edit_lock ownership is provable.
  Existing/shared administrator credentials are rejected. The generated
  password is sent to WP-CLI over stdin, remains in memory, and is never written
  to the report. Browser authentication state likewise remains in memory.
  A browser-context guard allows reads and the exact wp-login.php POST only;
  every other non-idempotent browser request is blocked. Strictly recognized
  core heartbeat, post-lock release, and preference-persistence requests remain
  blocked and are reported as expected/nonfatal. Every other write fails the
  run.
  WordPress can acquire _edit_lock metadata while rendering an editor GET. The
  verifier snapshots every inventoried lock through WP-CLI before browser use,
  restores that exact database state during bounded cleanup, and verifies it.
  Ephemeral administrator state is likewise deleted and verified in cleanup.
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
  configurationError('--ephemeral-admin is required for unique _edit_lock ownership.');
}

if (!ephemeralAdminRequested) {
  configurationError(
    'Existing/shared administrator credentials cannot prove that changed _edit_lock values '
      + 'belong to this scan; use --ephemeral-admin.',
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
    || path.join(
      '.runtime',
      'inspect',
      `gutenberg-stability-${timestamp}-${runId.replaceAll('-', '').slice(0, 8)}`,
    ),
  runtimeRoot,
  wpCliService: process.env.WP_CLI_SERVICE || 'cli',
};

const summary = {
  schemaVersion: 1,
  runId,
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
    readOnlyGuard: 'browser network writes blocked except the exact wp-login.php POST',
    databaseLifecycle: 'exact _edit_lock snapshot, bounded restoration, and verification',
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
    ? {
      attempted: false,
      ok: null,
      username: null,
      userId: null,
      locatedUserId: null,
      creationOutputShape: null,
      creationPreflightAbsent: false,
      creationVerified: false,
      recoveryMarkers: [],
    }
    : { attempted: false, ok: true, reason: 'external administrator supplied' },
  editLocks: {
    snapshotAttempted: false,
    snapshotCount: 0,
    restorationAttempted: false,
    restorationCount: 0,
    deletedScanCreatedCount: 0,
    verifiedCount: 0,
    ok: null,
  },
  runtimeIdentity: {
    attempted: false,
    homeMatches: null,
    siteUrlMatches: null,
    ok: null,
  },
  readOnlyGuard: {
    expectedBlocked: { count: 0, requests: [] },
    fatalBlocked: { count: 0, requests: [] },
  },
  failures: [],
  results: [],
  sources: OFFICIAL_SOURCES,
};

const lifecycle = {
  adminCreationPromise: null,
  abortController: new AbortController(),
  browser: null,
  browserClosePromise: null,
  children: new Set(),
  editLockSnapshot: null,
  editLockSnapshotPromise: null,
  ephemeralIdentity: null,
  interruptedBy: null,
  itemTracker: { currentId: null, priorId: null },
  receivedSignals: [],
  resourceCleanupPromise: null,
  reportSecrets: [],
  signalFinalizationPromise: null,
  summaryWritePromise: Promise.resolve(),
  userId: null,
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

function recordSignalFailure() {
  if (!lifecycle.interruptedBy) {
    return;
  }
  const existing = summary.failures.find(({ stage }) => stage === 'signal');
  if (existing) {
    existing.signal = lifecycle.interruptedBy;
    existing.receivedSignals = [...lifecycle.receivedSignals];
    return;
  }
  summary.failures.push({
    stage: 'signal',
    signal: lifecycle.interruptedBy,
    receivedSignals: [...lifecycle.receivedSignals],
  });
}

async function writeSummarySerialized() {
  lifecycle.summaryWritePromise = lifecycle.summaryWritePromise
    .catch(() => {})
    .then(() => writeSummary());
  return lifecycle.summaryWritePromise;
}

function startSignalFinalization() {
  if (lifecycle.signalFinalizationPromise) {
    return lifecycle.signalFinalizationPromise;
  }
  lifecycle.signalFinalizationPromise = (async () => {
    await cleanupResources();
    summary.finishedAt = new Date().toISOString();
    summary.status = 'interrupted';
    recordSignalFailure();
    try {
      await writeSummarySerialized();
      console.error(`Interrupted summary: ${path.join(config.outputDir, 'summary.json')}`);
    } catch (error) {
      console.error(`Could not write the interrupted summary: ${errorMessage(error)}`);
    }
  })();
  return lifecycle.signalFinalizationPromise;
}

for (const signal of ['SIGINT', 'SIGTERM', 'SIGHUP']) {
  process.on(signal, () => {
    lifecycle.receivedSignals.push(signal);
    if (!lifecycle.interruptedBy) {
      lifecycle.interruptedBy = signal;
      lifecycle.abortController.abort(new Error(`Received ${signal}`));
      console.error(
        `Received ${signal}; starting bounded browser, edit-lock, and administrator cleanup.`,
      );
    } else {
      console.error(
        `Received ${signal} again; cleanup is already running and cleanup children remain protected.`,
      );
    }

    for (const processHandle of lifecycle.children) {
      if (!processHandle.cleanupSafe) {
        processHandle.terminate(`received ${signal}`);
      }
    }
    void startSignalFinalization();
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
  if (!cleanupSafe && lifecycle.abortController.signal.aborted) {
    return Promise.reject(
      lifecycle.abortController.signal.reason || new Error('Verifier interrupted.'),
    );
  }
  return new Promise((resolve, reject) => {
    const child = spawn(command, commandArgs, {
      // Cleanup commands use their own process group. A second Ctrl-C sent to
      // npm's foreground group must not kill the exact-delete/restore checks.
      detached: cleanupSafe,
      env: process.env,
      stdio: ['pipe', 'pipe', 'pipe'],
    });
    let stdout = '';
    let stderr = '';
    let settled = false;
    let terminationReason = '';
    let killTimer = null;
    let resolveSettled;
    const settledPromise = new Promise((resolve) => {
      resolveSettled = resolve;
    });

    const finalize = (callback) => {
      if (settled) {
        return;
      }
      settled = true;
      clearTimeout(timeoutTimer);
      clearTimeout(killTimer);
      lifecycle.abortController.signal.removeEventListener('abort', onAbort);
      lifecycle.children.delete(processHandle);
      resolveSettled();
      callback();
    };
    const sendSignal = (signal) => {
      if (child.exitCode !== null || child.signalCode !== null) {
        return;
      }
      try {
        if (cleanupSafe && Number.isSafeInteger(child.pid) && child.pid > 0) {
          process.kill(-child.pid, signal);
        } else {
          child.kill(signal);
        }
      } catch {
        // The close/error event below remains the authoritative process result.
      }
    };
    const terminate = (reason) => {
      if (!terminationReason) {
        terminationReason = reason;
      }
      if (child.exitCode !== null || child.signalCode !== null) {
        return;
      }
      sendSignal('SIGTERM');
      if (!killTimer) {
        killTimer = setTimeout(() => {
          if (child.exitCode === null && child.signalCode === null) {
            sendSignal('SIGKILL');
          }
        }, CHILD_TERMINATION_GRACE_MS);
      }
    };
    const processHandle = { child, cleanupSafe, settledPromise, terminate };
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
    preflightAbsent: false,
    recoveryMarkerPaths: new Set(),
    userId: null,
    username: `lmhg_gutenberg_${suffix}`,
  };
}

function ephemeralRecoveryMarkerPath(identity) {
  const identitySuffix = identity.userId ? `user-${identity.userId}` : 'login-only';
  return path.join(
    config.outputDir,
    `ephemeral-admin-recovery-${identity.username}-${identitySuffix}.json`,
  );
}

async function persistEphemeralRecoveryMarker(identity, reason) {
  if (!identity) {
    return;
  }
  await mkdir(config.outputDir, { recursive: true });
  const markerPath = ephemeralRecoveryMarkerPath(identity);
  if (identity.recoveryMarkerPaths.has(markerPath)) {
    return;
  }
  const marker = {
    schemaVersion: 1,
    createdAt: new Date().toISOString(),
    reason,
    exactLogin: identity.username,
    exactUserId: identity.userId,
    recovery: identity.userId
      ? `Verify login ${identity.username} resolves only to ID ${identity.userId}, then delete that exact ID.`
      : `Resolve the exact login ${identity.username}; delete only the single matching ID.`,
  };
  // Identity-specific, exclusive creation means a later run can neither
  // overwrite nor unlink an earlier orphan administrator's recovery record.
  await writeFile(
    markerPath,
    `${JSON.stringify(marker, null, 2)}\n`,
    { flag: 'wx', mode: 0o600 },
  );
  await chmod(markerPath, 0o600);
  identity.recoveryMarkerPaths.add(markerPath);
  summary.cleanup.recoveryMarkers = [...identity.recoveryMarkerPaths];
}

async function clearEphemeralRecoveryMarkers(identity) {
  if (!ephemeralAdminRequested || !identity) {
    return;
  }
  for (const markerPath of identity.recoveryMarkerPaths) {
    await unlink(markerPath).catch((error) => {
      if (error?.code !== 'ENOENT') {
        throw error;
      }
    });
  }
  identity.recoveryMarkerPaths.clear();
  summary.cleanup.recoveryMarkers = [];
}

function processOutputShape(stdout) {
  const normalized = String(stdout).replaceAll('\r\n', '\n');
  const lines = normalized.split('\n');
  if (lines.at(-1) === '') {
    lines.pop();
  }
  const nonEmptyLines = lines.filter((line) => line.length > 0);
  const terminalLine = nonEmptyLines.at(-1) || '';
  let terminalLineKind = 'other';
  if (/^\d+$/.test(terminalLine)) {
    terminalLineKind = 'positive-integer-candidate';
  } else if (terminalLine.startsWith('{') && terminalLine.endsWith('}')) {
    terminalLineKind = 'json-object-candidate';
  } else if (terminalLine === '') {
    terminalLineKind = 'empty';
  }
  return {
    byteLength: Buffer.byteLength(normalized),
    endsWithNewline: normalized.endsWith('\n'),
    lineCount: lines.length,
    nonEmptyLineCount: nonEmptyLines.length,
    terminalLineKind,
  };
}

async function createEphemeralAdmin(identity) {
  const preexistingUserId = await findExactUserIdByLogin(identity.username, {
    secrets: [identity.password, identity.username],
  });
  if (preexistingUserId !== null) {
    throw new Error('Generated administrator login unexpectedly existed before creation.');
  }
  identity.preflightAbsent = true;
  summary.cleanup.creationPreflightAbsent = true;
  identity.createAttempted = true;
  // Persist the exact generated identity before the ambiguous create boundary.
  // The marker contains no password and is removed only after exact absence is
  // verified, so SIGKILL/npm-wrapper termination still leaves recovery data.
  await persistEphemeralRecoveryMarker(identity, 'ephemeral administrator creation pending');
  // `--prompt=user_pass` prints both a prompt and a reconstructed command to
  // stdout in current WP-CLI, so it cannot be combined with strict porcelain
  // parsing. Instead, eval-file receives the complete creation program over
  // stdin; the generated password never appears in argv, environment, or disk.
  // Source: https://developer.wordpress.org/cli/commands/eval-file/
  const encodedIdentity = Buffer.from(JSON.stringify({
    displayName: 'LMHG Gutenberg Verifier',
    email: identity.email,
    password: identity.password,
    username: identity.username,
  }), 'utf8').toString('base64');
  const php = `<?php
$payload = json_decode(base64_decode('${encodedIdentity}', true), true);
if (!is_array($payload)) {
    fwrite(STDERR, "Could not decode the administrator creation payload.\n");
    exit(61);
}
$required = array('displayName', 'email', 'password', 'username');
sort($required);
$actual = array_keys($payload);
sort($actual);
if ($actual !== $required) {
    fwrite(STDERR, "Administrator creation payload has an invalid shape.\n");
    exit(62);
}
$user_id = wp_insert_user(array(
    'display_name' => (string) $payload['displayName'],
    'role' => 'administrator',
    'user_email' => (string) $payload['email'],
    'user_login' => (string) $payload['username'],
    'user_pass' => (string) $payload['password'],
));
if (is_wp_error($user_id)) {
    $codes = array_values(array_filter(
        $user_id->get_error_codes(),
        static function ($code) {
            return is_string($code) && preg_match('/^[a-z0-9_-]{1,80}$/i', $code);
        }
    ));
    fwrite(STDERR, "Administrator creation failed" . ($codes ? ":" . implode(',', $codes) : "") . "\n");
    exit(63);
}
$user_id = (int) $user_id;
if ($user_id <= 0) {
    fwrite(STDERR, "Administrator creation returned an invalid ID.\n");
    exit(64);
}
$user = get_userdata($user_id);
echo wp_json_encode(array(
    'administratorRole' => $user && in_array('administrator', (array) $user->roles, true),
    'emailMatches' => $user && hash_equals((string) $payload['email'], (string) $user->user_email),
    'loginMatches' => $user && hash_equals((string) $payload['username'], (string) $user->user_login),
    'sentinel' => 'lmhg-gutenberg-admin-created-v1',
    'userId' => $user_id,
));
`;
  const { stdout } = await wpCliEvalFile(php, {
    secrets: [identity.password, encodedIdentity],
  });
  let creationResult;
  try {
    creationResult = JSON.parse(stdout.trim());
  } catch {
    creationResult = null;
  }
  if (
    !hasOnlyKeys(creationResult, [
      'administratorRole',
      'emailMatches',
      'loginMatches',
      'sentinel',
      'userId',
    ])
    || creationResult.sentinel !== 'lmhg-gutenberg-admin-created-v1'
    || !Number.isSafeInteger(creationResult.userId)
    || creationResult.userId <= 0
    || typeof creationResult.administratorRole !== 'boolean'
    || typeof creationResult.emailMatches !== 'boolean'
    || typeof creationResult.loginMatches !== 'boolean'
  ) {
    summary.cleanup.creationOutputShape = processOutputShape(stdout);
    throw new Error(
      'WP-CLI administrator creation returned an invalid redacted output shape: '
        + JSON.stringify(summary.cleanup.creationOutputShape),
    );
  }
  identity.userId = creationResult.userId;
  summary.cleanup.username = identity.username;
  summary.cleanup.userId = identity.userId;
  await persistEphemeralRecoveryMarker(identity, 'ephemeral administrator cleanup pending');
  if (
    !creationResult.administratorRole
    || !creationResult.emailMatches
    || !creationResult.loginMatches
  ) {
    throw new Error(
      'Created administrator failed server-side role or identity verification; '
        + 'exact-ID recovery evidence was preserved.',
    );
  }
  const exactLoginId = await findExactUserIdByLogin(identity.username, {
    secrets: [identity.password, identity.username],
  });
  if (exactLoginId !== identity.userId) {
    throw new Error(
      `Immediate exact-login verification expected ID ${identity.userId}; `
        + `received ${exactLoginId ?? 'no match'}.`,
    );
  }
  summary.cleanup.creationVerified = true;
  console.log(`Created ephemeral administrator ID ${identity.userId}.`);
}

async function findExactUserIdByLogin(login, { cleanupSafe = false, secrets = [] } = {}) {
  // An exit-0 empty result means the exact login is absent. Any WP-CLI,
  // Docker, Compose, or database error rejects and is a cleanup failure.
  // Source: https://developer.wordpress.org/cli/commands/user/list/
  const { stdout } = await wpCli(
    ['user', 'list', `--login=${login}`, '--field=ID'],
    {
      cleanupSafe,
      secrets,
      ...(cleanupSafe ? { timeoutMs: CLEANUP_PROCESS_TIMEOUT_MS } : {}),
    },
  );
  const tokens = stdout.trim() ? stdout.trim().split(/\s+/) : [];
  if (tokens.some((token) => !/^\d+$/.test(token)) || tokens.length > 1) {
    throw new Error(
      `Exact-login lookup returned an unexpected ID list: ${tokens.join(', ') || '(empty)'}`,
    );
  }
  if (tokens.length === 0) {
    return null;
  }
  const userId = Number.parseInt(tokens[0], 10);
  if (!Number.isSafeInteger(userId) || userId <= 0) {
    throw new Error('Exact-login lookup returned an out-of-range user ID.');
  }
  return userId;
}

async function findEphemeralAdminId(identity) {
  return findExactUserIdByLogin(identity.username, {
    cleanupSafe: true,
    secrets: [identity.password],
  });
}

async function inspectExactUserId(identity) {
  if (!Number.isSafeInteger(identity.userId) || identity.userId <= 0) {
    throw new Error('Cannot inspect an invalid recovery user ID.');
  }
  const encodedLogin = Buffer.from(identity.username, 'utf8').toString('base64');
  const php = `<?php
$user_id = ${identity.userId};
$expected_login = base64_decode('${encodedLogin}', true);
if (!is_int($user_id) || $user_id <= 0 || !is_string($expected_login)) {
    fwrite(STDERR, "Invalid exact-ID recovery lookup.\n");
    exit(65);
}
$user = get_userdata($user_id);
echo wp_json_encode(array(
    'exists' => (bool) $user,
    'loginMatches' => $user ? hash_equals($expected_login, (string) $user->user_login) : false,
));
`;
  const { stdout } = await wpCliEvalFile(php, {
    cleanupSafe: true,
    secrets: [identity.password, identity.username, encodedLogin],
    timeoutMs: CLEANUP_PROCESS_TIMEOUT_MS,
  });
  const result = JSON.parse(stdout.trim());
  if (
    !hasOnlyKeys(result, ['exists', 'loginMatches'])
    || typeof result.exists !== 'boolean'
    || typeof result.loginMatches !== 'boolean'
    || (!result.exists && result.loginMatches)
  ) {
    throw new Error('Exact-ID recovery lookup returned malformed evidence.');
  }
  return result;
}

async function cleanupEphemeralAdmin(identity) {
  if (!identity?.createAttempted) {
    return;
  }

  summary.cleanup.attempted = true;
  summary.cleanup.username = identity.username;
  summary.cleanup.userId = identity.userId;
  try {
    if (!identity.preflightAbsent) {
      throw new Error(
        'Ephemeral administrator absence was not proven before creation; refusing deletion.',
      );
    }

    let userId;
    if (identity.userId === null) {
      // Creation may have succeeded even when its output was malformed. The
      // generated login was proven absent immediately before that boundary, so
      // exact-login recovery is the only safe way to discover the unknown ID.
      userId = await findEphemeralAdminId(identity);
      summary.cleanup.locatedUserId = userId;
      if (userId === null) {
        summary.cleanup.ok = true;
        summary.cleanup.reason = 'exact-login lookup confirmed the unknown-ID user is absent';
        await clearEphemeralRecoveryMarkers(identity);
        return;
      }
      identity.userId = userId;
      summary.cleanup.userId = userId;
      await persistEphemeralRecoveryMarker(identity, 'ephemeral administrator cleanup pending');
      const idState = await inspectExactUserId(identity);
      if (!idState.exists || !idState.loginMatches) {
        throw new Error(
          'Exact-login recovery did not resolve to a matching exact user ID; refusing deletion.',
        );
      }
    } else {
      if (!Number.isSafeInteger(identity.userId) || identity.userId <= 0) {
        throw new Error('Creation recorded an invalid exact user ID; refusing deletion.');
      }
      // Once creation supplies an ID, inspect that ID first. This keeps
      // cleanup independent of a failing or stale user-list query and avoids
      // deleting a different account that later acquires the generated login.
      userId = identity.userId;
      summary.cleanup.locatedUserId = userId;
      const idState = await inspectExactUserId(identity);
      if (!idState.exists) {
        const remainingLoginId = await findEphemeralAdminId(identity);
        if (remainingLoginId !== null) {
          throw new Error(
            `Created user ID ${userId} is absent, but the generated login now resolves to `
              + `ID ${remainingLoginId}; refusing ambiguous deletion.`,
          );
        }
        summary.cleanup.ok = true;
        summary.cleanup.reason = 'exact ID and exact login were both confirmed absent';
        await clearEphemeralRecoveryMarkers(identity);
        return;
      }
      if (!idState.loginMatches) {
        throw new Error(
          `Created user ID ${userId} now belongs to a different login; refusing deletion.`,
        );
      }
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
    const postDeleteIdState = await inspectExactUserId(identity);
    if (postDeleteIdState.exists) {
      throw new Error(`Exact-ID verification still found user ID ${userId} after deletion.`);
    }
    const remainingLoginId = await findEphemeralAdminId(identity);
    if (remainingLoginId !== null) {
      throw new Error(
        `Exact-login verification still found ${identity.username} as ID ${remainingLoginId} after deletion.`,
      );
    }
    summary.cleanup.ok = true;
    summary.cleanup.userId = userId;
    summary.cleanup.reason = 'delete succeeded and exact ID plus exact login were confirmed absent';
    await clearEphemeralRecoveryMarkers(identity);
    console.log(`Deleted ephemeral administrator ID ${userId}.`);
  } catch (error) {
    summary.cleanup.ok = false;
    summary.cleanup.error = redact(errorMessage(error), [identity.password]);
    summary.failures.push({
      stage: 'ephemeral-admin-cleanup',
      error: summary.cleanup.error,
    });
    await persistEphemeralRecoveryMarker(
      identity,
      'bounded cleanup failed; exact administrator recovery is required',
    ).catch(() => {});
    console.error(`Ephemeral administrator cleanup failed: ${summary.cleanup.error}`);
  }
}

function wpCliEvalFile(phpSource, options = {}) {
  // `wp eval-file -` reads code from stdin. This is important for restoration:
  // pre-scan lock values never enter argv, logs, reports, or a temporary file.
  // Source: https://developer.wordpress.org/cli/commands/eval-file/
  return wpCli(['eval-file', '-'], { ...options, input: phpSource });
}

function normalizeSiteIdentityUrl(value, label) {
  let candidate;
  try {
    candidate = new URL(value);
  } catch {
    throw new Error(`${label} is not an absolute URL.`);
  }
  if (
    !['http:', 'https:'].includes(candidate.protocol)
    || candidate.username
    || candidate.password
    || candidate.search
    || candidate.hash
  ) {
    throw new Error(`${label} is not a plain HTTP(S) site URL.`);
  }
  const normalizedPath = candidate.pathname.replace(/\/{2,}/g, '/').replace(/\/+$/, '');
  return `${candidate.origin}${normalizedPath}`;
}

async function verifyRuntimeSiteIdentity() {
  summary.runtimeIdentity.attempted = true;
  const php = `<?php
echo wp_json_encode(array(
    'home' => (string) get_option('home'),
    'siteurl' => (string) get_option('siteurl'),
));
`;
  try {
    const { stdout } = await wpCliEvalFile(php);
    const identity = JSON.parse(stdout.trim());
    if (!hasOnlyKeys(identity, ['home', 'siteurl'])) {
      throw new Error('WP-CLI returned malformed site identity evidence.');
    }
    const expected = normalizeSiteIdentityUrl(config.baseUrl, 'WP_URL');
    const home = normalizeSiteIdentityUrl(identity.home, 'WordPress home option');
    const siteUrl = normalizeSiteIdentityUrl(identity.siteurl, 'WordPress siteurl option');
    summary.runtimeIdentity.homeMatches = home === expected;
    summary.runtimeIdentity.siteUrlMatches = siteUrl === expected;
    summary.runtimeIdentity.ok = home === expected && siteUrl === expected;
    if (!summary.runtimeIdentity.ok) {
      throw new Error(
        'WP_URL does not exactly match both WP-CLI WordPress home and siteurl; '
          + 'refusing cross-site administrator or edit-lock mutation.',
      );
    }
  } catch (error) {
    summary.runtimeIdentity.ok = false;
    const safeError = errorMessage(error);
    summary.failures.push({ stage: 'runtime-site-identity', error: safeError });
    throw new Error(`Runtime site identity preflight failed: ${safeError}`);
  }
}

function exactPositiveIds(items) {
  const ids = items.map(({ id }) => Number(id));
  if (
    ids.some((id) => !Number.isSafeInteger(id) || id <= 0)
    || new Set(ids).size !== ids.length
  ) {
    throw new Error('Published inventory did not contain unique positive integer IDs.');
  }
  return ids;
}

async function snapshotEditLocks(items) {
  const ids = exactPositiveIds(items);
  const inventoryProof = items.map((item) => ({
    id: Number(item.id),
    postType: item.expectedPostType,
    slug: item.slug,
    status: item.status,
  }));
  if (inventoryProof.some((item) => (
    !['page', 'post'].includes(item.postType)
    || typeof item.slug !== 'string'
    || item.status !== 'publish'
  ))) {
    throw new Error('Published REST inventory contains malformed identity evidence.');
  }
  summary.editLocks.snapshotAttempted = true;
  const encodedInventory = Buffer.from(JSON.stringify(inventoryProof), 'utf8').toString('base64');
  const php = `<?php
$inventory = json_decode(base64_decode('${encodedInventory}', true), true);
if (!is_array($inventory)) {
    fwrite(STDERR, "Could not decode the exact edit-lock ID inventory.\n");
    exit(41);
}
$result = array();
foreach ($inventory as $expected) {
    $id = is_array($expected) && array_key_exists('id', $expected) ? $expected['id'] : null;
    $post_type = is_array($expected) && array_key_exists('postType', $expected) ? $expected['postType'] : null;
    $slug = is_array($expected) && array_key_exists('slug', $expected) ? $expected['slug'] : null;
    $status = is_array($expected) && array_key_exists('status', $expected) ? $expected['status'] : null;
    if (!is_int($id) || $id <= 0 || !is_string($post_type) || !is_string($slug) || $status !== 'publish') {
        fwrite(STDERR, "Edit-lock inventory contains an invalid ID.\n");
        exit(42);
    }
    $post = get_post($id);
    if (
        !$post
        || (int) $post->ID !== $id
        || $post->post_type !== $post_type
        || $post->post_name !== $slug
        || $post->post_status !== $status
    ) {
        fwrite(STDERR, "REST/WP-CLI inventory identity mismatch for post " . $id . "\n");
        exit(43);
    }
    $exists = metadata_exists('post', $id, '_edit_lock');
    $values = $exists ? array_values(get_post_meta($id, '_edit_lock', false)) : array();
    foreach ($values as $value) {
        if (!is_string($value)) {
            fwrite(STDERR, "Edit-lock metadata is not scalar for post " . $id . "\n");
            exit(44);
        }
    }
    $result[] = array('id' => $id, 'exists' => $exists, 'values' => $values);
}
echo wp_json_encode($result);
`;

  try {
    const { stdout } = await wpCliEvalFile(php);
    const payload = JSON.parse(stdout.trim());
    if (!Array.isArray(payload) || payload.length !== ids.length) {
      throw new Error('WP-CLI returned an incomplete edit-lock snapshot.');
    }
    const expectedIds = new Set(ids);
    const seenIds = new Set();
    for (const record of payload) {
      if (
        !record
        || !Number.isSafeInteger(record.id)
        || !expectedIds.has(record.id)
        || seenIds.has(record.id)
        || typeof record.exists !== 'boolean'
        || !Array.isArray(record.values)
        || record.values.some((value) => typeof value !== 'string')
        || (record.exists && record.values.length === 0)
        || (!record.exists && record.values.length !== 0)
      ) {
        throw new Error('WP-CLI returned a malformed edit-lock snapshot.');
      }
      seenIds.add(record.id);
    }
    lifecycle.editLockSnapshot = payload;
    summary.editLocks.snapshotCount = payload.length;
    summary.editLocks.ok = null;
  } catch (error) {
    summary.editLocks.ok = false;
    const safeError = errorMessage(error);
    summary.failures.push({ stage: 'edit-lock-snapshot', error: safeError });
    throw new Error(`Edit-lock snapshot failed: ${safeError}`);
  }
}

async function restoreEditLocks() {
  const snapshot = lifecycle.editLockSnapshot;
  if (!snapshot) {
    return;
  }
  if (!Number.isSafeInteger(lifecycle.userId) || lifecycle.userId <= 0) {
    summary.editLocks.ok = false;
    summary.failures.push({
      stage: 'edit-lock-restoration',
      error: 'The exact scan user ID is unavailable; refusing ambiguous lock cleanup.',
    });
    return;
  }

  summary.editLocks.restorationAttempted = true;
  const encodedSnapshot = Buffer.from(JSON.stringify(snapshot), 'utf8').toString('base64');
  const scanUserId = lifecycle.userId;
  const php = `<?php
$records = json_decode(base64_decode('${encodedSnapshot}', true), true);
$scan_user_id = ${scanUserId};
if (!is_array($records) || !is_int($scan_user_id) || $scan_user_id <= 0) {
    fwrite(STDERR, "Could not decode the exact edit-lock restoration state.\n");
    exit(51);
}
$plans = array();
foreach ($records as $record) {
    $id = is_array($record) && array_key_exists('id', $record) ? $record['id'] : null;
    $prior_exists = is_array($record) && array_key_exists('exists', $record) ? $record['exists'] : null;
    $prior_values = is_array($record) && array_key_exists('values', $record) ? $record['values'] : null;
    if (!is_int($id) || $id <= 0 || !is_bool($prior_exists) || !is_array($prior_values)) {
        fwrite(STDERR, "Malformed edit-lock restoration record.\n");
        exit(52);
    }
    $post = get_post($id);
    if (!$post || (int) $post->ID !== $id) {
        fwrite(STDERR, "Edit-lock restoration post is absent: " . $id . "\n");
        exit(53);
    }
    $current_exists = metadata_exists('post', $id, '_edit_lock');
    $current_values = $current_exists
        ? array_values(get_post_meta($id, '_edit_lock', false))
        : array();
    if ($current_exists === $prior_exists && $current_values === $prior_values) {
        $plans[] = array('id' => $id, 'mode' => 'same', 'prior_exists' => $prior_exists, 'prior_values' => $prior_values);
        continue;
    }
    $scan_owned = count($current_values) > 0;
    foreach ($current_values as $value) {
        if (!is_string($value) || !preg_match('/^\\d{9,12}:' . preg_quote((string) $scan_user_id, '/') . '$/', $value)) {
            $scan_owned = false;
            break;
        }
    }
    if ($current_exists && !$scan_owned) {
        fwrite(STDERR, "Refusing to overwrite a non-scan edit lock for post " . $id . "\n");
        exit(54);
    }
    $plans[] = array(
        'id' => $id,
        'mode' => $prior_exists ? 'restore' : 'delete-scan-created',
        'prior_exists' => $prior_exists,
        'prior_values' => $prior_values,
    );
}

$restored = 0;
$deleted = 0;
foreach ($plans as $plan) {
    if ($plan['mode'] === 'same') {
        continue;
    }
    delete_post_meta($plan['id'], '_edit_lock');
    if ($plan['prior_exists']) {
        foreach ($plan['prior_values'] as $value) {
            if (!add_post_meta($plan['id'], '_edit_lock', $value, false)) {
                fwrite(STDERR, "Could not restore an edit lock for post " . $plan['id'] . "\n");
                exit(55);
            }
        }
        $restored++;
    } else {
        $deleted++;
    }
}

foreach ($plans as $plan) {
    $actual_exists = metadata_exists('post', $plan['id'], '_edit_lock');
    $actual_values = $actual_exists
        ? array_values(get_post_meta($plan['id'], '_edit_lock', false))
        : array();
    if ($actual_exists !== $plan['prior_exists'] || $actual_values !== $plan['prior_values']) {
        fwrite(STDERR, "Edit-lock restoration verification failed for post " . $plan['id'] . "\n");
        exit(56);
    }
}
echo wp_json_encode(array(
    'restored' => $restored,
    'deletedScanCreated' => $deleted,
    'verified' => count($plans),
));
`;

  try {
    const { stdout } = await wpCliEvalFile(php, {
      cleanupSafe: true,
      timeoutMs: CLEANUP_PROCESS_TIMEOUT_MS,
    });
    const result = JSON.parse(stdout.trim());
    if (
      !result
      || !Number.isSafeInteger(result.restored)
      || result.restored < 0
      || !Number.isSafeInteger(result.deletedScanCreated)
      || result.deletedScanCreated < 0
      || result.verified !== snapshot.length
    ) {
      throw new Error('WP-CLI returned malformed edit-lock restoration evidence.');
    }
    summary.editLocks.restorationCount = result.restored;
    summary.editLocks.deletedScanCreatedCount = result.deletedScanCreated;
    summary.editLocks.verifiedCount = result.verified;
    summary.editLocks.ok = true;
    lifecycle.editLockSnapshot = null;
  } catch (error) {
    summary.editLocks.ok = false;
    const safeError = errorMessage(error);
    summary.failures.push({ stage: 'edit-lock-restoration', error: safeError });
    console.error(`Edit-lock restoration failed: ${safeError}`);
  }
}

async function waitForNonCleanupChildren() {
  const pending = [...lifecycle.children]
    .filter(({ cleanupSafe }) => !cleanupSafe)
    .map(({ settledPromise }) => settledPromise);
  if (pending.length === 0) {
    return;
  }
  let timeout;
  const timedOut = Symbol('timed-out');
  const result = await Promise.race([
    Promise.allSettled(pending),
    new Promise((resolve) => {
      timeout = setTimeout(
        () => resolve(timedOut),
        CHILD_TERMINATION_GRACE_MS + 2000,
      );
    }),
  ]);
  clearTimeout(timeout);
  if (result === timedOut) {
    summary.failures.push({
      stage: 'cleanup-process-drain',
      error: 'A pre-cleanup child did not settle within the bounded termination window.',
    });
  }
}

function cleanupResources() {
  if (!lifecycle.resourceCleanupPromise) {
    lifecycle.resourceCleanupPromise = (async () => {
      await Promise.allSettled([
        lifecycle.adminCreationPromise,
        lifecycle.editLockSnapshotPromise,
      ].filter(Boolean));
      await Promise.allSettled([closeBrowserBounded(), waitForNonCleanupChildren()]);
      await restoreEditLocks();
      await cleanupEphemeralAdmin(lifecycle.ephemeralIdentity);
    })();
  }
  return lifecycle.resourceCleanupPromise;
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

function editorUrlForItem(item) {
  const editUrl = new URL('/wp-admin/post.php', `${config.baseUrl}/`);
  editUrl.searchParams.set('post', String(item.id));
  editUrl.searchParams.set('action', 'edit');
  return editUrl;
}

function isExactEditorLocation(candidate, item) {
  const actual = new URL(candidate);
  const expected = editorUrlForItem(item);
  return actual.origin === expected.origin
    && actual.pathname === expected.pathname
    && actual.searchParams.get('post') === String(item.id)
    && actual.searchParams.get('action') === 'edit';
}

async function login(page, credentials, firstItem) {
  const editUrl = editorUrlForItem(firstItem);
  const loginUrl = new URL('/wp-login.php', `${config.baseUrl}/`);
  loginUrl.searchParams.set('redirect_to', editUrl.href);
  await page.goto(loginUrl.href, {
    timeout: config.editorTimeoutMs,
    waitUntil: 'domcontentloaded',
  });
  await page.locator('#user_login').fill(credentials.username);
  await page.locator('#user_pass').fill(credentials.password);
  await page.locator('input[name="redirect_to"]').evaluate((element, redirectTo) => {
    element.value = redirectTo;
  }, editUrl.href);
  await Promise.all([
    page.waitForURL((url) => isExactEditorLocation(url, firstItem), {
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
    || !isExactEditorLocation(authenticatedUrl, firstItem)
  ) {
    throw new Error(
      `Administrator login did not reach the first same-origin editor; landed on ${page.url()}`,
    );
  }
}

function requestTargetForReport(requestUrl) {
  const restRoute = requestUrl.searchParams.get('rest_route');
  return restRoute === null
    ? requestUrl.pathname
    : `${requestUrl.pathname}?rest_route=${encodeURIComponent(restRoute)}`;
}

function requestContentType(request) {
  const header = request.headers()['content-type'] || '';
  return {
    header,
    mediaType: header.split(';', 1)[0].trim().toLowerCase(),
  };
}

function safeFieldNameForReport(field) {
  return /^[a-z0-9_.\[\]-]{1,160}$/i.test(field) ? field : '[unsafe-field-name]';
}

function parseMultipartForm(request) {
  const { header, mediaType } = requestContentType(request);
  const result = {
    action: null,
    bodyLength: request.postDataBuffer()?.length ?? Buffer.byteLength(request.postData() || ''),
    duplicateFields: [],
    error: null,
    fieldNames: [],
    fields: new Map(),
    hasFiles: false,
    mediaType,
  };
  if (mediaType !== 'multipart/form-data' || result.bodyLength > WRITE_BODY_LIMIT_BYTES) {
    result.error = mediaType !== 'multipart/form-data' ? 'not-multipart' : 'body-too-large';
    return result;
  }

  const boundaryMatch = header.match(/(?:^|;)\s*boundary=(?:"([^"]+)"|([^;\s]+))\s*(?:;|$)/i);
  const boundary = boundaryMatch?.[1] || boundaryMatch?.[2] || '';
  if (!boundary || boundary.length > 200 || /[\r\n]/.test(boundary)) {
    result.error = 'invalid-boundary';
    return result;
  }
  const bodyBuffer = request.postDataBuffer();
  if (!bodyBuffer) {
    result.error = 'missing-body';
    return result;
  }
  const body = bodyBuffer.toString('latin1');
  const delimiter = `--${boundary}`;
  const segments = body.split(delimiter);
  const validFinalSegment = segments.at(-1) === '--\r\n' || segments.at(-1) === '--';
  if (segments[0] !== '' || segments.length < 3 || !validFinalSegment) {
    result.error = 'invalid-framing';
    return result;
  }

  for (const segment of segments.slice(1, -1)) {
    if (!segment.startsWith('\r\n') || !segment.endsWith('\r\n')) {
      result.error = 'invalid-part-framing';
      return result;
    }
    const part = segment.slice(2, -2);
    const headerEnd = part.indexOf('\r\n\r\n');
    if (headerEnd < 0) {
      result.error = 'invalid-part-headers';
      return result;
    }
    const rawHeaders = part.slice(0, headerEnd).split('\r\n');
    const value = part.slice(headerEnd + 4);
    const partHeaders = new Map();
    for (const rawHeader of rawHeaders) {
      const separator = rawHeader.indexOf(':');
      if (separator <= 0) {
        result.error = 'invalid-part-header';
        return result;
      }
      const name = rawHeader.slice(0, separator).trim().toLowerCase();
      const headerValue = rawHeader.slice(separator + 1).trim();
      if (partHeaders.has(name)) {
        result.error = 'duplicate-part-header';
        return result;
      }
      partHeaders.set(name, headerValue);
    }
    if (partHeaders.size !== 1 || !partHeaders.has('content-disposition')) {
      result.error = 'unexpected-part-header';
      return result;
    }
    const disposition = partHeaders.get('content-disposition');
    if (/;\s*filename\s*=/i.test(disposition)) {
      result.hasFiles = true;
      result.error = 'file-part';
      return result;
    }
    const dispositionMatch = disposition.match(/^form-data;\s*name="([^"\r\n]+)"$/i);
    if (!dispositionMatch) {
      result.error = 'invalid-content-disposition';
      return result;
    }
    const field = dispositionMatch[1];
    if (result.fields.has(field)) {
      result.duplicateFields.push(safeFieldNameForReport(field));
      result.error = 'duplicate-field';
      return result;
    }
    result.fields.set(field, value);
  }

  result.fieldNames = [...result.fields.keys()].map(safeFieldNameForReport).sort();
  const candidateAction = result.fields.get('action');
  if (candidateAction && /^[a-z0-9_-]{1,80}$/i.test(candidateAction)) {
    result.action = candidateAction;
  }
  return result;
}

function requestBodyShapeForReport(request) {
  const { mediaType } = requestContentType(request);
  const postData = request.postData() || '';
  const fieldNames = new Set();
  const duplicateFields = new Set();
  let action = null;
  let parseStatus = 'not-inspected';
  let scopes = [];

  if (mediaType === 'application/x-www-form-urlencoded') {
    const form = new URLSearchParams(postData);
    const counts = new Map();
    for (const field of form.keys()) {
      const safeField = safeFieldNameForReport(field);
      fieldNames.add(safeField);
      counts.set(safeField, (counts.get(safeField) || 0) + 1);
    }
    for (const [field, count] of counts) {
      if (count > 1) {
        duplicateFields.add(field);
      }
    }
    const candidateAction = form.get('action');
    if (candidateAction && /^[a-z0-9_-]{1,80}$/i.test(candidateAction)) {
      action = candidateAction;
    }
    parseStatus = 'parsed';
  } else if (mediaType === 'multipart/form-data') {
    const multipart = parseMultipartForm(request);
    multipart.fieldNames.forEach((field) => fieldNames.add(field));
    multipart.duplicateFields.forEach((field) => duplicateFields.add(field));
    action = multipart.action;
    parseStatus = multipart.error || 'parsed';
  } else if (mediaType === 'application/json') {
    try {
      const body = JSON.parse(postData);
      const visit = (value, prefix = '', depth = 0) => {
        if (!value || typeof value !== 'object' || depth > 3) {
          return;
        }
        for (const key of Object.keys(value)) {
          const path = prefix ? `${prefix}.${key}` : key;
          fieldNames.add(safeFieldNameForReport(path));
          visit(value[key], path, depth + 1);
        }
      };
      visit(body);
      const persisted = body?.meta?.persisted_preferences;
      if (persisted && typeof persisted === 'object' && !Array.isArray(persisted)) {
        scopes = Object.keys(persisted)
          .filter((key) => key !== '_modified')
          .map(safeFieldNameForReport)
          .sort();
      }
      parseStatus = 'parsed';
    } catch {
      fieldNames.add('[invalid-json]');
      parseStatus = 'invalid-json';
    }
  }

  return {
    contentType: mediaType || null,
    bodyLength: request.postDataBuffer()?.length ?? Buffer.byteLength(postData),
    fieldNames: [...fieldNames].sort(),
    duplicateFields: [...duplicateFields].sort(),
    action,
    parseStatus,
    scopes,
  };
}

function isExpectedHeartbeatRequest(request, requestUrl, method, itemTracker) {
  const { mediaType } = requestContentType(request);
  const bodyBuffer = request.postDataBuffer();
  if (
    method !== 'POST'
    || requestUrl.pathname !== '/wp-admin/admin-ajax.php'
    || requestUrl.search !== ''
    || mediaType !== 'application/x-www-form-urlencoded'
    || !bodyBuffer
    || bodyBuffer.length <= 0
    || bodyBuffer.length > WRITE_BODY_LIMIT_BYTES
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
  const keys = [...form.keys()];
  if (
    new Set(keys).size !== keys.length
    || !/^[a-z0-9_-]{6,64}$/i.test(form.get('_nonce') || '')
  ) {
    return false;
  }
  const fieldsAreCore = keys.every(
    (field) => coreHeartbeatFields.has(field)
      || /^data\[(?:wp-auth-check|wp-refresh-post-lock|wp-refresh-post-nonces)\](?:\[[a-z0-9_-]{1,64}\]){0,3}$/i.test(field),
  );
  if (!fieldsAreCore) {
    return false;
  }
  const postIdFields = keys.filter((field) => /\[(?:post_id|post_ID)\]$/i.test(field));
  return postIdFields.every((field) => {
    const postIdText = form.get(field) || '';
    const postId = Number.parseInt(postIdText, 10);
    return /^\d+$/.test(postIdText)
      && Number.isSafeInteger(postId)
      && [itemTracker.currentId, itemTracker.priorId].includes(postId);
  });
}

function isExpectedPostLockRelease(request, requestUrl, method, itemTracker, userId) {
  if (
    method !== 'POST'
    || requestUrl.pathname !== '/wp-admin/admin-ajax.php'
    || requestUrl.search !== ''
    || !Number.isSafeInteger(userId)
    || userId <= 0
  ) {
    return false;
  }
  const multipart = parseMultipartForm(request);
  const expectedFields = ['_wpnonce', 'action', 'active_post_lock', 'post_ID'];
  if (
    multipart.error
    || multipart.hasFiles
    || multipart.duplicateFields.length > 0
    || multipart.fieldNames.length !== expectedFields.length
    || !expectedFields.every((field, index) => multipart.fieldNames[index] === field)
    || multipart.fields.get('action') !== 'wp-remove-post-lock'
    || !/^[a-z0-9_-]{6,64}$/i.test(multipart.fields.get('_wpnonce') || '')
  ) {
    return false;
  }
  const postIdText = multipart.fields.get('post_ID') || '';
  const postId = Number.parseInt(postIdText, 10);
  if (
    !/^\d+$/.test(postIdText)
    || !Number.isSafeInteger(postId)
    || postId <= 0
    || ![itemTracker.currentId, itemTracker.priorId].includes(postId)
  ) {
    return false;
  }
  const activePostLock = multipart.fields.get('active_post_lock') || '';
  const lockMatch = activePostLock.match(/^(\d{9,12}):(\d+)$/);
  return Boolean(lockMatch) && Number.parseInt(lockMatch[2], 10) === userId;
}

function isPlainJsonObject(value) {
  return Boolean(value) && typeof value === 'object' && !Array.isArray(value);
}

function hasOnlyKeys(value, expectedKeys) {
  if (!isPlainJsonObject(value)) {
    return false;
  }
  const actual = Object.keys(value).sort();
  const expected = [...expectedKeys].sort();
  return actual.length === expected.length
    && actual.every((key, index) => key === expected[index]);
}

function isExpectedCorePreferencePersistence(request, requestUrl, method) {
  const { mediaType } = requestContentType(request);
  const requestLength = request.postDataBuffer()?.length ?? Buffer.byteLength(request.postData() || '');
  const queryEntries = [...requestUrl.searchParams.entries()];
  const queryIsExpected = queryEntries.length === 0
    || (
      queryEntries.length === 1
      && queryEntries[0][0] === '_locale'
      && queryEntries[0][1] === 'user'
    );
  if (
    method !== 'POST'
    || requestUrl.pathname !== '/wp-json/wp/v2/users/me'
    || !queryIsExpected
    || mediaType !== 'application/json'
    || requestLength <= 0
    || requestLength >= WRITE_BODY_LIMIT_BYTES
    || (request.headers()['x-http-method-override'] || '').trim().toUpperCase() !== 'PUT'
  ) {
    return false;
  }

  try {
    const body = JSON.parse(request.postData() || '');
    if (!hasOnlyKeys(body, ['meta']) || !hasOnlyKeys(body.meta, ['persisted_preferences'])) {
      return false;
    }
    const persisted = body.meta.persisted_preferences;
    if (!hasOnlyKeys(persisted, ['_modified', 'core'])) {
      return false;
    }
    if (
      typeof persisted._modified !== 'string'
      || !/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?Z$/.test(persisted._modified)
      || !Number.isFinite(Date.parse(persisted._modified))
      || !hasOnlyKeys(persisted.core, ['isComplementaryAreaVisible'])
      || typeof persisted.core.isComplementaryAreaVisible !== 'boolean'
    ) {
      return false;
    }
    return true;
  } catch {
    return false;
  }
}

function expectedBlockedClassification(request, requestUrl, method, itemTracker, userId) {
  if (isExpectedHeartbeatRequest(request, requestUrl, method, itemTracker)) {
    return 'expected-core-heartbeat';
  }
  if (isExpectedPostLockRelease(request, requestUrl, method, itemTracker, userId)) {
    return 'expected-post-lock-release';
  }
  if (isExpectedCorePreferencePersistence(request, requestUrl, method)) {
    return 'expected-core-preference-persistence';
  }
  return null;
}

function isExactLoginPost(request, requestUrl, method, state, credentials, firstItem) {
  const { mediaType } = requestContentType(request);
  const bodyBuffer = request.postDataBuffer();
  if (
    !state.loginPostAllowed
    || method !== 'POST'
    || requestUrl.pathname !== '/wp-login.php'
    || requestUrl.search !== ''
    || !request.isNavigationRequest()
    || mediaType !== 'application/x-www-form-urlencoded'
    || !bodyBuffer
    || bodyBuffer.length <= 0
    || bodyBuffer.length >= WRITE_BODY_LIMIT_BYTES
  ) {
    return false;
  }
  try {
    const frame = request.frame();
    if (frame !== frame.page().mainFrame()) {
      return false;
    }
  } catch {
    return false;
  }
  const form = new URLSearchParams(request.postData() || '');
  const keys = [...form.keys()].sort();
  const expectedKeys = ['log', 'pwd', 'redirect_to', 'testcookie', 'wp-submit'];
  return keys.length === expectedKeys.length
    && new Set(keys).size === keys.length
    && expectedKeys.every((key, index) => keys[index] === key)
    && form.get('log') === credentials.username
    && form.get('pwd') === credentials.password
    && form.get('redirect_to') === editorUrlForItem(firstItem).href
    && form.get('testcookie') === '1'
    && /^.{1,80}$/u.test(form.get('wp-submit') || '');
}

async function installReadOnlyRequestBarrier(
  context,
  itemTracker,
  userId,
  credentials,
  firstItem,
) {
  const blockedWrites = [];
  const state = { loginPostAllowed: true };
  const siteOrigin = new URL(config.baseUrl).origin;
  const loginUrl = new URL(`${config.baseUrl}/wp-login.php`);
  const readMethods = new Set(['GET', 'HEAD', 'OPTIONS']);

  // The guard covers all origins. Only one main-frame, same-origin login POST
  // is permitted; every other browser write is aborted before network I/O.
  await context.route('**/*', async (route) => {
    const request = route.request();
    const method = request.method().toUpperCase();
    const requestUrl = new URL(request.url());
    const isSameOrigin = requestUrl.origin === siteOrigin;
    const isLoginPost = isSameOrigin
      && requestUrl.pathname === loginUrl.pathname
      && isExactLoginPost(
        request,
        requestUrl,
        method,
        state,
        credentials,
        firstItem,
      );

    if (readMethods.has(method)) {
      await route.continue();
      return;
    }
    if (isLoginPost) {
      state.loginPostAllowed = false;
      await route.continue();
      return;
    }

    const expectedClassification = isSameOrigin
      ? expectedBlockedClassification(request, requestUrl, method, itemTracker, userId)
      : null;
    blockedWrites.push({
      classification: expectedClassification || 'unexpected-write',
      fatal: !expectedClassification,
      method,
      target: requestTargetForReport(requestUrl),
      bodyShape: requestBodyShapeForReport(request),
    });
    await route.abort('blockedbyclient');
  });

  return { blockedWrites, state };
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
  const editUrl = editorUrlForItem(item).href;
  const consoleErrors = [];
  const pageErrors = [];
  const blockedWriteStart = contextBlockedWrites.length;
  const onConsole = (message) => {
    if (message.type() === 'error') {
      consoleErrors.push(redact(message.text(), lifecycle.reportSecrets).slice(0, 500));
    }
  };
  const onPageError = (error) => pageErrors.push(
    redact(errorMessage(error), lifecycle.reportSecrets).slice(0, 500),
  );
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
    if (!isExactEditorLocation(page.url(), item)) {
      const response = await page.goto(editUrl, {
        timeout: config.editorTimeoutMs,
        waitUntil: 'domcontentloaded',
      });
      if (!response?.ok()) {
        throw new Error(`Editor returned HTTP ${response?.status() ?? 'unknown'}.`);
      }
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
    result.reasons.push(redact(errorMessage(error), lifecycle.reportSecrets));
  } finally {
    result.diagnostics.blockedContentWrites = contextBlockedWrites.slice(blockedWriteStart);
    page.off('console', onConsole);
    page.off('pageerror', onPageError);
  }

  if (result.diagnostics.blockedContentWrites.some(({ fatal }) => fatal)) {
    result.reasons.push('The network guard blocked an unexpected browser write request.');
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
  lifecycle.editLockSnapshotPromise = snapshotEditLocks(items);
  await lifecycle.editLockSnapshotPromise;
  if (lifecycle.abortController.signal.aborted) {
    throw lifecycle.abortController.signal.reason || new Error('Verifier interrupted.');
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
  let barrierState = null;

  try {
    context = await lifecycle.browser.newContext({
      serviceWorkers: 'block',
      viewport: { width: 1440, height: 1000 },
    });
    lifecycle.itemTracker.currentId = Number(items[0].id);
    lifecycle.itemTracker.priorId = null;
    const barrier = await installReadOnlyRequestBarrier(
      context,
      lifecycle.itemTracker,
      lifecycle.userId,
      credentials,
      items[0],
    );
    contextBlockedWrites = barrier.blockedWrites;
    barrierState = barrier.state;
    // Authentication cookies stay in this in-memory context and are never
    // saved as storageState. GET/HEAD/OPTIONS remain available so the editor
    // and authenticated context=edit REST reads can hydrate normally.
    // Source: https://playwright.dev/docs/auth
    const page = await context.newPage();
    page.setDefaultTimeout(config.editorTimeoutMs);
    page.setDefaultNavigationTimeout(config.editorTimeoutMs);

    await login(page, credentials, items[0]);
    barrierState.loginPostAllowed = false;
    console.log('Authenticated to the WordPress development editor.');

    for (const item of items) {
      if (lifecycle.interruptedBy) {
        break;
      }
      const itemId = Number(item.id);
      if (lifecycle.itemTracker.currentId !== itemId) {
        lifecycle.itemTracker.priorId = lifecycle.itemTracker.currentId;
        lifecycle.itemTracker.currentId = itemId;
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
    if (barrierState) {
      barrierState.loginPostAllowed = false;
    }
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
  const ephemeralIdentity = ephemeralAdminRequested ? buildEphemeralAdmin() : null;
  lifecycle.ephemeralIdentity = ephemeralIdentity;
  if (ephemeralIdentity) {
    summary.cleanup.username = ephemeralIdentity.username;
  }
  await mkdir(config.outputDir, { recursive: true });
  const credentials = ephemeralIdentity
    ? { username: ephemeralIdentity.username, password: ephemeralIdentity.password }
    : { username: externalUser, password: externalPassword };
  lifecycle.reportSecrets = [credentials.password, credentials.username].filter(Boolean);

  try {
    if (lifecycle.abortController.signal.aborted) {
      throw lifecycle.abortController.signal.reason || new Error('Verifier interrupted.');
    }
    await verifyRuntimeSiteIdentity();
    if (lifecycle.abortController.signal.aborted) {
      throw lifecycle.abortController.signal.reason || new Error('Verifier interrupted.');
    }
    if (ephemeralIdentity) {
      lifecycle.adminCreationPromise = createEphemeralAdmin(ephemeralIdentity);
      await lifecycle.adminCreationPromise;
    }
    if (lifecycle.abortController.signal.aborted) {
      throw lifecycle.abortController.signal.reason || new Error('Verifier interrupted.');
    }
    const resolvedUserId = ephemeralIdentity?.userId
      || await findExactUserIdByLogin(credentials.username, {
        secrets: [credentials.username, credentials.password],
      });
    if (!Number.isSafeInteger(resolvedUserId) || resolvedUserId <= 0) {
      throw new Error('Exact-login lookup did not resolve the supplied administrator to one user ID.');
    }
    if (ephemeralIdentity?.userId && resolvedUserId !== ephemeralIdentity.userId) {
      throw new Error(
        `Ephemeral administrator ID mismatch: create returned ${ephemeralIdentity.userId}, `
          + `exact-login lookup returned ${resolvedUserId}.`,
      );
    }
    lifecycle.userId = resolvedUserId;
    await runScan(credentials);
  } catch (error) {
    const safeError = redact(errorMessage(error), [credentials.password, credentials.username]);
    summary.failures.push({ stage: 'fatal', error: safeError });
    console.error(safeError);
  } finally {
    await cleanupResources();

    summary.finishedAt = new Date().toISOString();
    if (lifecycle.interruptedBy) {
      summary.status = 'interrupted';
      recordSignalFailure();
    } else {
      summary.status = summary.failures.length === 0 ? 'passed' : 'failed';
    }
    await writeSummarySerialized();
    console.log(`summary: ${path.join(config.outputDir, 'summary.json')}`);
  }

  if (lifecycle.interruptedBy === 'SIGINT') {
    process.exitCode = 130;
  } else if (lifecycle.interruptedBy === 'SIGTERM') {
    process.exitCode = 143;
  } else if (lifecycle.interruptedBy === 'SIGHUP') {
    process.exitCode = 129;
  } else if (summary.status !== 'passed') {
    process.exitCode = 1;
  }
}

await main();
