import fs from "node:fs";
import { spawnSync } from "node:child_process";
import path from "node:path";

const root = process.cwd();
const manifestPath = path.join(root, "data/lmhg/source-route-manifest.json");
const manifest = JSON.parse(fs.readFileSync(manifestPath, "utf8"));
const failures = [];

function fail(message) {
  failures.push(message);
}

function normalizeUrl(value) {
  if (!value || typeof value !== "string") return "";
  if (value === "/") return "/";
  const clean = value.startsWith("/") ? value : `/${value}`;
  return clean.endsWith("/") || path.extname(clean) ? clean : `${clean}/`;
}

function expectedPageUri(url) {
  const normalized = normalizeUrl(url);
  if (normalized === "/") return "home";
  if (normalized === "/404.html") return "not-found";
  return normalized.replace(/^\/|\/$/g, "").replace(/\.html$/, "");
}

function runWpEval(code) {
  const result = spawnSync("npx", ["--no-install", "wp-env", "run", "cli", "wp", "eval", code], {
    encoding: "utf8"
  });
  if (result.status !== 0) {
    console.error(result.stdout);
    console.error(result.stderr);
    process.exit(result.status ?? 1);
  }
  return `${result.stdout}\n${result.stderr}`;
}

function extractJson(output) {
  const match = output.match(/LMHG_JSON_START([\s\S]*?)LMHG_JSON_END/);
  if (!match) {
    throw new Error(`Could not find LMHG JSON payload in wp-env output:\n${output}`);
  }
  return JSON.parse(match[1]);
}

const inScopeRoutes = manifest.routes
  .filter((route) => route.migrationStatus !== "out-of-scope")
  .filter((route) => !normalizeUrl(route.url).startsWith("/review/"))
  .map((route) => ({
    url: normalizeUrl(route.url),
    expectedUri: expectedPageUri(route.url),
    schemaType: route.seo?.schemaType || ""
  }))
  .sort((a, b) => a.url.localeCompare(b.url));

const php = `
$posts = get_posts(array(
    'post_type' => 'page',
    'post_status' => 'any',
    'meta_key' => '_lmhg_source_url',
    'orderby' => 'ID',
    'order' => 'ASC',
    'posts_per_page' => -1,
));
$rows = array();
foreach ($posts as $post) {
    $route_entry = get_post_meta($post->ID, '_lmhg_route_manifest_entry', true);
    $rows[] = array(
        'id' => $post->ID,
        'post_status' => $post->post_status,
        'post_name' => $post->post_name,
        'page_uri' => get_page_uri($post),
        'source_url' => get_post_meta($post->ID, '_lmhg_source_url', true),
        'canonical_url' => get_post_meta($post->ID, '_lmhg_canonical_url', true),
        'schema_type' => get_post_meta($post->ID, '_lmhg_schema_type', true),
        'route_manifest_valid' => is_array(json_decode((string) $route_entry, true)),
    );
}
echo 'LMHG_JSON_START' . wp_json_encode(array(
    'front_source_url' => get_post_meta((int) get_option('page_on_front'), '_lmhg_source_url', true),
    'show_on_front' => get_option('show_on_front'),
    'rows' => $rows,
)) . 'LMHG_JSON_END';
`;

const payload = extractJson(runWpEval(php));
const rows = Array.isArray(payload.rows) ? payload.rows : [];
const rowsByUrl = new Map();

for (const row of rows) {
  const url = normalizeUrl(row.source_url);
  if (!rowsByUrl.has(url)) rowsByUrl.set(url, []);
  rowsByUrl.get(url).push(row);
}

for (const route of inScopeRoutes) {
  const matches = rowsByUrl.get(route.url) || [];
  if (matches.length === 0) {
    fail(`${route.url} missing imported WordPress page`);
    continue;
  }
  if (matches.length > 1) {
    fail(`${route.url} has duplicate imported WordPress pages: ${matches.map((row) => row.id).join(", ")}`);
  }

  const row = matches[0];
  if (row.post_status !== "publish") fail(`${route.url} imported page is not published: ${row.post_status}`);
  if (row.page_uri !== route.expectedUri) fail(`${route.url} expected page_uri ${route.expectedUri}, found ${row.page_uri}`);
  if (!row.route_manifest_valid) fail(`${route.url} has invalid _lmhg_route_manifest_entry JSON`);
  if (route.schemaType && row.schema_type !== route.schemaType) {
    fail(`${route.url} expected schema type ${route.schemaType}, found ${row.schema_type || "(missing)"}`);
  }
}

const expectedUrls = new Set(inScopeRoutes.map((route) => route.url));
for (const row of rows) {
  const url = normalizeUrl(row.source_url);
  if (!expectedUrls.has(url)) fail(`unexpected imported WordPress page with source URL ${url || "(missing)"}: post ${row.id}`);
}

if (payload.show_on_front !== "page") fail(`show_on_front expected page, found ${payload.show_on_front || "(missing)"}`);
if (normalizeUrl(payload.front_source_url) !== "/") fail(`front page source URL expected /, found ${payload.front_source_url || "(missing)"}`);

console.log(JSON.stringify({
  expectedRoutes: inScopeRoutes.length,
  importedPages: rows.length,
  frontSourceUrl: payload.front_source_url,
  showOnFront: payload.show_on_front
}, null, 2));

if (failures.length > 0) {
  console.error("LMHG route verification failed:");
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log("LMHG route verification passed.");
