import { spawnSync } from "node:child_process";

function runWp(args) {
  const result = spawnSync("npx", ["--no-install", "wp-env", "run", "cli", "wp", ...args], { encoding: "utf8" });
  if (result.status !== 0) {
    console.error(result.stdout);
    console.error(result.stderr);
    process.exit(result.status ?? 1);
  }
  if (result.stdout.trim()) console.log(result.stdout.trim());
}

function runNodeScript(script) {
  const result = spawnSync("node", [script], { encoding: "utf8" });
  if (result.status !== 0) {
    console.error(result.stdout);
    console.error(result.stderr);
    process.exit(result.status ?? 1);
  }
  if (result.stdout.trim()) console.log(result.stdout.trim());
  if (result.stderr.trim()) console.error(result.stderr.trim());
}

function detectTailnetHost() {
  if (process.env.TAILSCALE_HOST) return process.env.TAILSCALE_HOST.trim();
  const result = spawnSync("tailscale", ["status", "--json"], { encoding: "utf8" });
  if (result.status !== 0) return "";
  try {
    return JSON.parse(result.stdout).Self.DNSName.replace(/\.$/, "");
  } catch {
    return "";
  }
}

runWp(["theme", "activate", "lmhg-block-theme"]);
runWp(["plugin", "activate", "lmhg-site-core"]);
runWp(["rewrite", "structure", "/%postname%/"]);

const tailnetHost = detectTailnetHost();
if (tailnetHost) runWp(["option", "update", "lmhg_tailnet_host", tailnetHost]);

runWp([
  "eval",
  `
$home = get_page_by_path('home');
if (!$home) {
    $home_id = wp_insert_post(array(
        'post_type' => 'page',
        'post_title' => 'Home',
        'post_name' => 'home',
        'post_status' => 'publish',
        'post_content' => '<!-- wp:pattern {"slug":"lmhg-block-theme/hero"} /--><!-- wp:pattern {"slug":"lmhg-block-theme/content-band"} /-->'
    ));
} else {
    $home_id = $home->ID;
}
$about = get_page_by_path('about');
if (!$about) {
    wp_insert_post(array(
        'post_type' => 'page',
        'post_title' => 'About',
        'post_name' => 'about',
        'post_status' => 'publish',
        'post_content' => '<!-- wp:heading --><h2>Built for controlled parity</h2><!-- /wp:heading --><!-- wp:paragraph --><p>This page is seeded by the local WordPress environment so screenshots and Lighthouse checks have stable content before LMHG route import begins.</p><!-- /wp:paragraph -->'
    ));
}
update_option('blogname', 'LMHG Block WP');
update_option('blogdescription', 'WordPress proof track for LMHG parity work.');
update_option('show_on_front', 'page');
update_option('page_on_front', $home_id);
flush_rewrite_rules();
`
]);
runNodeScript("tools/seed-lmhg-wp.mjs");
console.log("Seeded LMHG wp-env site content and route manifest.");
