import fs from "node:fs";
import { spawnSync } from "node:child_process";
import path from "node:path";

const root = process.cwd();
const manifestPath = path.join(root, "data/lmhg/source-route-manifest.json");
const manifest = JSON.parse(fs.readFileSync(manifestPath, "utf8"));
const failures = [];

const taxonomyMap = {
  lmhg_page_family: (route) => route.pageFamily || "",
  lmhg_template_family: (route) => route.templateFamily || "",
  lmhg_faceted_type: (route) => route.facetedPageType || "",
  lmhg_schema_type: (route) => route.seo?.schemaType || "",
  lmhg_migration_status: (route) => route.migrationStatus || "",
  lmhg_seo_status: (route) => route.seo?.status || ""
};

function fail(message) {
  failures.push(message);
}

function normalizeUrl(value) {
  if (!value || typeof value !== "string") return "";
  if (value === "/") return "/";
  const clean = value.startsWith("/") ? value : `/${value}`;
  return clean.endsWith("/") || path.extname(clean) ? clean : `${clean}/`;
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
  if (!match) throw new Error(`Could not find LMHG JSON payload in wp-env output:\n${output}`);
  return JSON.parse(match[1]);
}

const expectedRoutes = manifest.routes
  .filter((route) => route.migrationStatus !== "out-of-scope")
  .filter((route) => !normalizeUrl(route.url).startsWith("/review/"));

const php = `
$taxonomies = array('lmhg_page_family', 'lmhg_template_family', 'lmhg_faceted_type', 'lmhg_schema_type', 'lmhg_migration_status', 'lmhg_seo_status');
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
    $terms = array();
    foreach ($taxonomies as $taxonomy) {
        $terms[$taxonomy] = wp_get_object_terms($post->ID, $taxonomy, array('fields' => 'names'));
    }
    $rows[] = array(
        'id' => $post->ID,
        'source_url' => get_post_meta($post->ID, '_lmhg_source_url', true),
        'terms' => $terms,
    );
}
$taxonomy_exists = array();
foreach ($taxonomies as $taxonomy) {
    $taxonomy_exists[$taxonomy] = taxonomy_exists($taxonomy);
}
echo 'LMHG_JSON_START' . wp_json_encode(array(
    'taxonomy_exists' => $taxonomy_exists,
    'rows' => $rows,
)) . 'LMHG_JSON_END';
`;

const payload = extractJson(runWpEval(php));
const rowsByUrl = new Map();

for (const [taxonomy, exists] of Object.entries(payload.taxonomy_exists || {})) {
  if (!exists) fail(`taxonomy is not registered: ${taxonomy}`);
}

for (const row of payload.rows || []) {
  const url = normalizeUrl(row.source_url);
  rowsByUrl.set(url, row);
}

for (const route of expectedRoutes) {
  const url = normalizeUrl(route.url);
  const row = rowsByUrl.get(url);
  if (!row) {
    fail(`${url} missing imported page for taxonomy verification`);
    continue;
  }

  for (const [taxonomy, getExpected] of Object.entries(taxonomyMap)) {
    const expected = String(getExpected(route) || "").trim();
    const terms = Array.isArray(row.terms?.[taxonomy]) ? row.terms[taxonomy].filter(Boolean) : [];
    if (!expected && terms.length > 0) {
      fail(`${url} expected no ${taxonomy} term, found ${terms.join(", ")}`);
      continue;
    }
    if (expected && (terms.length !== 1 || terms[0] !== expected)) {
      fail(`${url} expected ${taxonomy} term "${expected}", found ${terms.join(", ") || "(none)"}`);
    }
  }
}

console.log(JSON.stringify({
  expectedRoutes: expectedRoutes.length,
  importedPages: (payload.rows || []).length,
  taxonomies: Object.keys(taxonomyMap).length
}, null, 2));

if (failures.length > 0) {
  console.error("LMHG taxonomy verification failed:");
  for (const failure of failures) console.error(`- ${failure}`);
  process.exit(1);
}

console.log("LMHG taxonomy verification passed.");
