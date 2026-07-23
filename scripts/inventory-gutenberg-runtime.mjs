#!/usr/bin/env node

import { spawn } from 'node:child_process';
import { existsSync } from 'node:fs';
import { readFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const projectRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const collectorPath = path.join(projectRoot, 'scripts', 'php', 'gutenberg-runtime-inventory.php');
const collectorSentinel = 'LMHG_GUTENBERG_RUNTIME_INVENTORY_JSON:';
const defaultRuntimeRoot = '/srv/codex/services/lmhg-blockwp-wordpress-mariadb';
const defaultDevelopmentUrl = 'http://100.116.130.39:8093';
const expectedWordPressVersion = '7.0.2';
const expectedTheme = 'wordpress-2026';
const requiredSiteCorePlugin = 'lmhg-site-core/lmhg-site-core.php';
const disableAutomaticCronExec = "if (! defined('DISABLE_WP_CRON')) { define('DISABLE_WP_CRON', true); }";
const maximumOutputBytes = 8 * 1024 * 1024;
const terminationGraceMs = 5000;
const signalExitCodes = { SIGHUP: 129, SIGINT: 130, SIGTERM: 143 };
let interruptedSignal = null;

function printHelp() {
  process.stdout.write(`LMHG read-only Gutenberg runtime inventory

Discovers every reviewed Gutenberg-capable runtime content type and durable
status through the configured Docker Compose WP-CLI service. It also inventories
merged Site Editor templates/parts and raw template rows without emitting post
content, credentials, cookies, or database secrets. A pre-bootstrap guard
protects WordPress's shared database connection and reports attempted writes
before inventory output. The collector does not create, update, save, or delete
WordPress objects; extension-owned secondary connections are outside its guard.

Usage:
  node scripts/inventory-gutenberg-runtime.mjs

Options:
  -h, --help  Print this help without contacting Docker or WordPress.

Environment:
  WP_URL                           Exact development URL expected from both
                                   WordPress home and siteurl
                                   (default: ${defaultDevelopmentUrl})
  WP_RUNTIME_ROOT                  Docker Compose runtime root
                                   (default: ${defaultRuntimeRoot})
  WP_COMPOSE_FILE                  Compose file
                                   (default: <WP_RUNTIME_ROOT>/compose.yml)
  WP_CLI_SERVICE                   Compose WP-CLI service (default: cli)
  WP_INVENTORY_TIMEOUT_MS          Positive timeout in milliseconds
                                   (default: 120000)

Exit status:
  0  Inventory completed with no blockers. Informational risks and integrity
     findings remain present in JSON but do not change the exit status.
  1  A blocker, runtime identity mismatch, or inventory execution failure.
  2  Invalid command-line usage.

The canonical production host louisvillementalhealth.org is always refused.
The development endpoint is configurable because it is operational and may
change; WP_URL must exactly match the runtime on every invocation. The runtime
must also use WordPress ${expectedWordPressVersion}, the ${expectedTheme} block
theme, and the active LMHG Site Core plugin.
`);
}

function parsePositiveInteger(name, fallback) {
  const raw = process.env[name];
  if (raw === undefined || raw === '') {
    return fallback;
  }
  if (!/^\d+$/.test(raw)) {
    throw new Error('invalid-positive-integer');
  }
  const value = Number.parseInt(raw, 10);
  if (!Number.isSafeInteger(value) || value <= 0) {
    throw new Error('invalid-positive-integer');
  }
  return value;
}

function normalizeSiteUrl(value) {
  let candidate;
  try {
    candidate = new URL(value);
  } catch {
    throw new Error('invalid-site-url');
  }
  if (
    !['http:', 'https:'].includes(candidate.protocol)
    || candidate.username
    || candidate.password
    || candidate.search
    || candidate.hash
  ) {
    throw new Error('invalid-site-url');
  }
  const pathname = candidate.pathname.replace(/\/{2,}/g, '/').replace(/\/+$/, '');
  return `${candidate.origin}${pathname}`;
}

function isProductionHost(value) {
  try {
    const hostname = new URL(value).hostname.toLowerCase().replace(/\.+$/, '');
    return hostname.replace(/^www\./, '') === 'louisvillementalhealth.org';
  } catch {
    return false;
  }
}

function buildConfig() {
  const runtimeRoot = process.env.WP_RUNTIME_ROOT || defaultRuntimeRoot;
  return {
    composeFile: process.env.WP_COMPOSE_FILE || path.join(runtimeRoot, 'compose.yml'),
    expectedUrl: normalizeSiteUrl(process.env.WP_URL || defaultDevelopmentUrl),
    runtimeRoot,
    timeoutMs: parsePositiveInteger('WP_INVENTORY_TIMEOUT_MS', 120000),
    wpCliService: process.env.WP_CLI_SERVICE || 'cli',
  };
}

function runWpCliCollector(config, phpSource) {
  const commandArgs = [
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
    '--volume',
    `${collectorPath}:/tmp/lmhg-gutenberg-runtime-inventory.php:ro`,
    config.wpCliService,
    `--exec=${disableAutomaticCronExec}`,
    '--require=/tmp/lmhg-gutenberg-runtime-inventory.php',
    'eval-file',
    '-',
  ];

  return new Promise((resolve, reject) => {
    const child = spawn('docker', commandArgs, {
      cwd: projectRoot,
      detached: true,
      env: process.env,
      stdio: ['pipe', 'pipe', 'pipe'],
    });
    let stdout = '';
    let stderr = '';
    let exceededLimit = false;
    let timedOut = false;
    let terminationStarted = false;
    let killTimer;

    const signalProcessGroup = (signal) => {
      if (!Number.isSafeInteger(child.pid) || child.pid <= 0) {
        return;
      }
      try {
        process.kill(-child.pid, signal);
      } catch (error) {
        if (error?.code !== 'ESRCH') {
          throw error;
        }
      }
    };

    const terminate = () => {
      if (terminationStarted) {
        return;
      }
      terminationStarted = true;
      signalProcessGroup('SIGTERM');
      killTimer = setTimeout(() => {
        signalProcessGroup('SIGKILL');
      }, terminationGraceMs);
    };

    const signalHandlers = new Map(
      Object.keys(signalExitCodes).map((signal) => [
        signal,
        () => {
          interruptedSignal ||= signal;
          if (terminationStarted) {
            signalProcessGroup('SIGKILL');
          } else {
            terminate();
          }
        },
      ]),
    );
    const removeSignalHandlers = () => {
      for (const [signal, handler] of signalHandlers) {
        process.removeListener(signal, handler);
      }
    };
    for (const [signal, handler] of signalHandlers) {
      process.on(signal, handler);
    }

    const timer = setTimeout(() => {
      timedOut = true;
      terminate();
    }, config.timeoutMs);

    const append = (current, chunk) => {
      const next = current + chunk;
      if (Buffer.byteLength(next) > maximumOutputBytes) {
        exceededLimit = true;
        terminate();
      }
      return next;
    };

    child.stdout.setEncoding('utf8');
    child.stderr.setEncoding('utf8');
    child.stdout.on('data', (chunk) => {
      stdout = append(stdout, chunk);
    });
    child.stderr.on('data', (chunk) => {
      stderr = append(stderr, chunk);
    });
    child.stdin.on('error', () => {});
    child.on('error', () => {
      clearTimeout(timer);
      clearTimeout(killTimer);
      removeSignalHandlers();
      reject(new Error('wp-cli-process-error'));
    });
    child.on('close', (code, signal) => {
      clearTimeout(timer);
      clearTimeout(killTimer);
      removeSignalHandlers();
      if (exceededLimit) {
        reject(new Error('wp-cli-output-limit'));
      } else if (timedOut) {
        reject(new Error('wp-cli-process-timeout'));
      } else if (code !== 0) {
        reject(new Error(signal ? 'wp-cli-process-terminated' : 'wp-cli-process-failed'));
      } else {
        resolve({ stdout, stderr });
      }
    });
    child.stdin.end(phpSource);
  });
}

function assertReadOnlyCollector(phpSource) {
  const allowedMysqliStatements = new Set([
    'SET SESSION TRANSACTION READ ONLY',
    'START TRANSACTION READ ONLY',
  ]);
  let sourceWithoutGuardOperations = phpSource.replace(
    /mysqli_query\s*\(\s*\$wpdb->dbh\s*,\s*'([^']+)'\s*\)/g,
    (match, statement) => allowedMysqliStatements.has(statement) ? '' : match,
  );
  sourceWithoutGuardOperations = sourceWithoutGuardOperations.replace(
    /mysqli_rollback\s*\(\s*\$wpdb->dbh\s*\)/g,
    '',
  );
  const mutationPatterns = [
    /\b(?:wp_insert_post|wp_update_post|wp_delete_post|wp_trash_post)\s*\(/i,
    /\b(?:wp_insert_user|wp_create_user|wp_update_user|wp_delete_user)\s*\(/i,
    /\b(?:wp_insert_term|wp_update_term|wp_delete_term|wp_set_object_terms|wp_set_post_terms)\s*\(/i,
    /\b(?:add|update|delete)_(?:post|term|user|comment)_meta\s*\(/i,
    /\b(?:add|update|delete)_metadata\s*\(/i,
    /\b(?:add|update|delete)_(?:option|site_option)\s*\(/i,
    /\b(?:set|delete)_(?:transient|site_transient)\s*\(/i,
    /\b(?:set_theme_mod|remove_theme_mod|switch_theme)\s*\(/i,
    /\b(?:wp_schedule_event|wp_schedule_single_event|wp_unschedule_event|wp_clear_scheduled_hook)\s*\(/i,
    /\$wpdb\s*->\s*(?:insert|update|delete|replace|query)\s*\(/i,
    /\bmysqli_(?:query|multi_query|prepare|execute_query|commit|rollback|begin_transaction|autocommit)\s*\(/i,
  ];
  if (mutationPatterns.some((pattern) => pattern.test(sourceWithoutGuardOperations))) {
    throw new Error('collector-read-only-contract-failed');
  }
}

function extractCollectorPayload(stdout) {
  const sentinelLines = String(stdout)
    .replaceAll('\r\n', '\n')
    .split('\n')
    .filter((line) => line.startsWith(collectorSentinel));
  if (sentinelLines.length !== 1) {
    throw new Error('collector-sentinel-invalid');
  }

  let payload;
  try {
    payload = JSON.parse(sentinelLines[0].slice(collectorSentinel.length));
  } catch {
    throw new Error('collector-json-invalid');
  }
  return payload;
}

function assertNoSensitiveOutput(value, pathParts = []) {
  if (Array.isArray(value)) {
    value.forEach((entry, index) => assertNoSensitiveOutput(entry, [...pathParts, String(index)]));
    return;
  }
  if (!value || typeof value !== 'object') {
    return;
  }

  const forbiddenKeys = new Set([
    'content',
    'contentraw',
    'cookie',
    'password',
    'post_content',
    'postcontent',
    'rawcontent',
    'secret',
    'token',
    'userpass',
  ]);
  for (const [key, entry] of Object.entries(value)) {
    if (forbiddenKeys.has(key.toLowerCase())) {
      throw new Error('collector-sensitive-key-detected');
    }
    assertNoSensitiveOutput(entry, [...pathParts, key]);
  }
}

function assertCollectorShape(collector) {
  if (
    !collector
    || typeof collector !== 'object'
    || collector.schemaVersion !== 1
    || collector.sentinel !== 'lmhg-gutenberg-runtime-inventory-v1'
    || !collector.wordpress
    || typeof collector.wordpress.home !== 'string'
    || typeof collector.wordpress.siteUrl !== 'string'
    || typeof collector.wordpress.version !== 'string'
    || typeof collector.wordpress.activeStylesheet !== 'string'
    || typeof collector.wordpress.activeTemplate !== 'string'
    || !Array.isArray(collector.wordpress.activePluginFiles)
    || !Number.isSafeInteger(collector.wordpress.unreportableActivePluginCount)
    || !collector.readOnlyGuard
    || collector.readOnlyGuard.active !== true
    || collector.readOnlyGuard.automaticCronDisabled !== true
    || collector.readOnlyGuard.sessionDefaultReadOnly !== true
    || collector.readOnlyGuard.transactionReadOnly !== true
    || collector.readOnlyGuard.objectCacheDropinSuppressed !== true
    || collector.readOnlyGuard.transactionRolledBack !== true
    || !Number.isSafeInteger(collector.readOnlyGuard.blockedOperationCount)
    || !Array.isArray(collector.readOnlyGuard.blockedOperationCounts)
    || !Array.isArray(collector.readOnlyGuard.blockedTargetCounts)
    || !Number.isSafeInteger(collector.readOnlyGuard.expectedBlockedOperationCount)
    || !Array.isArray(collector.readOnlyGuard.expectedBlockedOperationCounts)
    || !Array.isArray(collector.readOnlyGuard.expectedBlockedTargetCounts)
    || !Array.isArray(collector.readOnlyGuard.suppressedCallbacks)
    || !collector.contentInventory
    || !Number.isSafeInteger(collector.contentInventory.total)
    || !Array.isArray(collector.contentInventory.types)
    || !collector.siteEditorInventory
    || !Number.isSafeInteger(collector.siteEditorInventory.mergedTotal)
    || !Array.isArray(collector.siteEditorInventory.mergedTypes)
    || !Array.isArray(collector.siteEditorInventory.rawDatabaseRows)
    || !Array.isArray(collector.registeredPostTypes)
    || !Array.isArray(collector.dormantPostTypes)
    || !Array.isArray(collector.blockers)
    || !Array.isArray(collector.integrityFindings)
    || !Array.isArray(collector.risks)
  ) {
    throw new Error('collector-output-shape-invalid');
  }
  assertNoSensitiveOutput(collector);
}

function sortDiagnostics(diagnostics) {
  const unique = new Map();
  for (const diagnostic of diagnostics) {
    const key = JSON.stringify(diagnostic);
    unique.set(key, diagnostic);
  }
  return [...unique.values()].sort((left, right) => {
    const leftKey = JSON.stringify(left);
    const rightKey = JSON.stringify(right);
    return leftKey < rightKey ? -1 : leftKey > rightKey ? 1 : 0;
  });
}

function failureReport(code, expectedUrl = null) {
  return {
    schemaVersion: 1,
    status: 'blocked',
    runtimeIdentity: {
      expectedUrl,
      home: null,
      siteUrl: null,
      exactHomeMatch: false,
      exactSiteUrlMatch: false,
      productionHostDetected: null,
    },
    wordpress: null,
    readOnlyGuard: null,
    policy: null,
    registeredPostTypes: [],
    contentInventory: null,
    siteEditorInventory: null,
    dormantPostTypes: [],
    blockers: [{ code }],
    integrityFindings: [],
    risks: [],
  };
}

function buildReport(config, collector) {
  const blockers = [...collector.blockers];
  let home = null;
  let siteUrl = null;
  try {
    home = normalizeSiteUrl(collector.wordpress.home);
    siteUrl = normalizeSiteUrl(collector.wordpress.siteUrl);
  } catch {
    blockers.push({ code: 'runtime-identity-malformed' });
  }

  const productionHostDetected = [config.expectedUrl, home, siteUrl]
    .filter(Boolean)
    .some(isProductionHost);
  if (productionHostDetected) {
    blockers.push({ code: 'production-runtime-refused' });
  }
  const exactHomeMatch = home === config.expectedUrl;
  const exactSiteUrlMatch = siteUrl === config.expectedUrl;
  if (!exactHomeMatch || !exactSiteUrlMatch) {
    blockers.push({ code: 'runtime-url-mismatch' });
  }
  if (collector.wordpress.isBlockTheme !== true) {
    blockers.push({ code: 'runtime-active-theme-not-block-theme' });
  }
  if (collector.wordpress.version !== expectedWordPressVersion) {
    blockers.push({ code: 'runtime-wordpress-version-mismatch' });
  }
  if (collector.wordpress.activeStylesheet !== expectedTheme) {
    blockers.push({ code: 'runtime-active-stylesheet-mismatch' });
  }
  if (collector.wordpress.activeTemplate !== expectedTheme) {
    blockers.push({ code: 'runtime-active-template-mismatch' });
  }
  if (collector.wordpress.unreportableActivePluginCount !== 0) {
    blockers.push({ code: 'runtime-active-plugin-evidence-malformed' });
  }
  if (!collector.wordpress.activePluginFiles.includes(requiredSiteCorePlugin)) {
    blockers.push({ code: 'runtime-site-core-plugin-not-active' });
  }

  const sortedBlockers = sortDiagnostics(blockers);
  const hasFindings = collector.integrityFindings.length > 0 || collector.risks.length > 0;
  return {
    schemaVersion: 1,
    status: sortedBlockers.length > 0 ? 'blocked' : hasFindings ? 'passed-with-findings' : 'passed',
    runtimeIdentity: {
      expectedUrl: config.expectedUrl,
      home,
      siteUrl,
      exactHomeMatch,
      exactSiteUrlMatch,
      productionHostDetected,
    },
    wordpress: {
      version: collector.wordpress.version,
      activeStylesheet: collector.wordpress.activeStylesheet,
      activeTemplate: collector.wordpress.activeTemplate,
      isBlockTheme: collector.wordpress.isBlockTheme,
      activePluginFiles: collector.wordpress.activePluginFiles,
      unreportableActivePluginCount: collector.wordpress.unreportableActivePluginCount,
    },
    readOnlyGuard: collector.readOnlyGuard,
    policy: collector.policy,
    registeredPostTypes: collector.registeredPostTypes,
    contentInventory: collector.contentInventory,
    siteEditorInventory: collector.siteEditorInventory,
    dormantPostTypes: collector.dormantPostTypes,
    blockers: sortedBlockers,
    integrityFindings: sortDiagnostics(collector.integrityFindings),
    risks: sortDiagnostics(collector.risks),
  };
}

async function main() {
  let config;
  try {
    config = buildConfig();
  } catch (error) {
    return { exitCode: 1, report: failureReport(error.message) };
  }

  if (isProductionHost(config.expectedUrl)) {
    return { exitCode: 1, report: failureReport('production-runtime-refused', config.expectedUrl) };
  }
  if (!existsSync(config.composeFile) || !existsSync(collectorPath)) {
    return { exitCode: 1, report: failureReport('inventory-required-file-missing', config.expectedUrl) };
  }

  try {
    const phpSource = await readFile(collectorPath, 'utf8');
    assertReadOnlyCollector(phpSource);
    const { stdout } = await runWpCliCollector(config, phpSource);
    const collector = extractCollectorPayload(stdout);
    assertCollectorShape(collector);
    const report = buildReport(config, collector);
    return { exitCode: report.blockers.length > 0 ? 1 : 0, report };
  } catch (error) {
    const safeCode = /^[a-z0-9-]{1,80}$/.test(error?.message || '')
      ? error.message
      : 'inventory-execution-failed';
    return { exitCode: 1, report: failureReport(safeCode, config.expectedUrl) };
  }
}

const args = process.argv.slice(2);
if (args.includes('--help') || args.includes('-h')) {
  if (args.some((argument) => !['--help', '-h'].includes(argument))) {
    process.stderr.write('Help cannot be combined with other arguments.\n');
    process.exitCode = 2;
  } else {
    printHelp();
  }
} else if (args.length > 0) {
  process.stderr.write(`Unknown option(s): ${args.join(', ')}\nRun with --help for usage.\n`);
  process.exitCode = 2;
} else {
  const { exitCode, report } = await main();
  process.stdout.write(`${JSON.stringify(report, null, 2)}\n`);
  process.exitCode = interruptedSignal ? signalExitCodes[interruptedSignal] : exitCode;
}
