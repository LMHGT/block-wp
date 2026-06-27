Agentic WordPress Codex Environment Implementation Plan
For agentic workers: REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (- [ ]) syntax for tracking.

Goal: Build a portable Codex-first WordPress development environment with WordPress Agent Skills, a minimal block theme, a site-core plugin, Playground Blueprints, @wordpress/env, WP-CLI, Playwright screenshots, Lighthouse checks, and Tailscale Serve access.
Architecture: The target repository is source-controlled and keeps runtime config, agent instructions, WordPress theme code, durable plugin behavior, blueprints, and verification scripts in separate folders. @wordpress/env is the authoritative local runtime; WordPress Playground is included for disposable smoke tests and demos, with a timeout guard for low-CPU hosts. Tailscale Serve exposes the local WordPress site to the tailnet, and the plugin emits the tailnet URL only for Tailscale-hosted requests.
Tech Stack: WordPress 6.9, PHP 8.3, Node.js 20.18+, npm 10.2+, Docker, Docker Compose v2, Composer, WP-CLI, WordPress Agent Skills, @wordpress/env, @wp-playground/cli, Playwright, Lighthouse, Tailscale Serve.
Target File Structure
Create or modify these files in the target repository:
.
├── .codex/skills/
├── .gitignore
├── .nvmrc
├── .wp-env.json
├── AGENTS.md
├── README.md
├── blueprints/local-dev/blueprint.json
├── docs/superpowers/plans/2026-06-26-agentic-wordpress-codex-environment-handoff.md
├── package.json
├── tests/playwright.config.mjs
├── tests/visual.spec.mjs
├── tools/check-prereqs.mjs
├── tools/check-static.mjs
├── tools/run-lighthouse.mjs
├── tools/run-playground.mjs
├── tools/seed-wp-env.mjs
├── wp-content/plugins/agentic-site-core/agentic-site-core.php
└── wp-content/themes/custom-block-theme/
    ├── functions.php
    ├── parts/footer.html
    ├── parts/header.html
    ├── patterns/content-band.php
    ├── patterns/hero.php
    ├── style.css
    ├── templates/404.html
    ├── templates/archive.html
    ├── templates/front-page.html
    ├── templates/index.html
    ├── templates/page.html
    ├── templates/single.html
    └── theme.json
Task 1: Host Toolchain Prerequisites
Files:
No repository files changed in this task.


Step 1: Confirm current shell and repository root

Run:
pwd
git status --short
Expected: pwd prints the target repository root. git status --short may show existing work; do not overwrite unrelated user changes.

Step 2: Install Node.js 20.18.0 and npm 10.8.2 with n
Run:
sudo npm install -g n
sudo n 20.18.0
hash -r
node --version
npm --version
Expected:
v20.18.0
10.8.2

Step 3: Install Docker, Docker Compose v2, Composer, zip, and jq
Run:
sudo apt-get update
sudo apt-get install -y docker.io docker-compose-v2 composer zip jq
sudo service docker start
sudo usermod -aG docker "$USER" || true
sudo chmod 666 /var/run/docker.sock
docker --version
docker compose version
composer --version
jq --version
Expected: Docker, Docker Compose, Composer, and jq versions print without command-not-found errors.

Step 4: Confirm PHP and WP-CLI
Run:
php --version | head -1
wp --info | sed -n '1,20p'
Expected: PHP 8.x and WP-CLI info print. If wp is missing, install WP-CLI before continuing:
curl -L https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o /tmp/wp-cli.phar
php /tmp/wp-cli.phar --info
sudo mv /tmp/wp-cli.phar /usr/local/bin/wp
sudo chmod +x /usr/local/bin/wp
wp --info | sed -n '1,20p'
Task 2: Base Project Files
Files:
Create: .gitignore

Create: .nvmrc

Create: .wp-env.json

Create: package.json

Create: README.md

Create: AGENTS.md


Step 1: Create directories

Run:
mkdir -p blueprints/local-dev tools tests docs/superpowers/plans
mkdir -p wp-content/themes/custom-block-theme/{templates,parts,patterns,styles,assets/css/blocks,assets/js}
mkdir -p wp-content/plugins/agentic-site-core
Expected: all directories exist.

Step 2: Create .gitignore
Write:
node_modules/
.wp-env/
artifacts/
*.log
*.zip
.DS_Store
*.crt
*.key

Step 3: Create .nvmrc
Write:
20.18.0

Step 4: Create .wp-env.json
Write:
{
  "port": 8888,
  "testsEnvironment": false,
  "phpVersion": "8.3",
  "themes": [
    "./wp-content/themes/custom-block-theme"
  ],
  "plugins": [
    "./wp-content/plugins/agentic-site-core"
  ],
  "config": {
    "WP_DEBUG": true,
    "SCRIPT_DEBUG": true,
    "WP_ENVIRONMENT_TYPE": "local"
  }
}

Step 5: Create package.json
Write:
{
  "name": "wp-agentic-starter",
  "version": "0.1.0",
  "private": true,
  "description": "Agent-friendly WordPress starter with block theme, Playground Blueprint, wp-env, screenshots, and Lighthouse checks.",
  "type": "module",
  "engines": {
    "node": ">=20.18.0",
    "npm": ">=10.2.3"
  },
  "scripts": {
    "check:static": "node tools/check-static.mjs",
    "check:prereqs": "node tools/check-prereqs.mjs",
    "check:prereqs:strict": "node tools/check-prereqs.mjs --strict",
    "setup:browsers": "playwright install chromium",
    "wp-env:start": "wp-env start",
    "wp-env:stop": "wp-env stop",
    "wp-env:destroy": "wp-env destroy",
    "wp-env:cli": "wp-env run cli wp",
    "wp-env:seed": "node tools/seed-wp-env.mjs",
    "playground:start": "node tools/run-playground.mjs",
    "playground:blueprint": "node tools/run-playground.mjs --run-blueprint",
    "test:screenshots": "playwright test --config=tests/playwright.config.mjs",
    "test:lighthouse": "node tools/run-lighthouse.mjs",
    "verify:site": "npm run test:screenshots && npm run test:lighthouse",
    "verify": "npm run check:static && npm run check:prereqs"
  },
  "devDependencies": {
    "@playwright/test": "1.61.1",
    "@wordpress/env": "11.9.0",
    "@wp-playground/cli": "3.1.42",
    "lighthouse": "12.8.2"
  }
}

Step 6: Create AGENTS.md
Write:
# Codex WordPress Workflow

Use the project-local WordPress Agent Skills before changing WordPress code. Start with `.codex/skills/wordpress-router/SKILL.md`, then follow the routed skill for block themes, plugins, WP-CLI, Playground, Blueprints, or performance work.

## Development Rules

- Keep theme changes inside `wp-content/themes/custom-block-theme`.
- Keep durable SEO/schema/business logic inside `wp-content/plugins/agentic-site-core`.
- Prefer `theme.json`, templates, template parts, and patterns before adding CSS.
- Use WordPress APIs for scripts, styles, images, escaping, nonces, and capabilities.
- Do not add a page builder or heavy framework unless the user explicitly asks.
- Keep public frontend HTML cache-friendly: no unnecessary cookies, random nonces, or per-user anonymous markup.
- Present user-facing URLs through Tailscale Serve, not raw `localhost` URLs.

## Common Commands

```bash
npm run check:static
npm run check:prereqs
npm run wp-env:start
npm run wp-env:seed
npm run test:screenshots
npm run test:lighthouse
npm run playground:start
Verification Before Completion
For file-only changes, run:
npm run check:static
For WordPress runtime changes, run:
npm run wp-env:start
npm run wp-env:seed
npm run test:screenshots
npm run test:lighthouse
If Docker, Node, or browser prerequisites are missing, report the exact failing prerequisite from npm run check:prereqs instead of claiming runtime verification.

- [ ] **Step 7: Create `README.md`**

Write:

```markdown
# WordPress Agentic Starter

This workspace is set up for Codex-first WordPress development: a minimal block theme, a small plugin boundary for durable site behavior, project-local WordPress Agent Skills, Playground Blueprints, `@wordpress/env`, Playwright screenshots, Lighthouse checks, and Tailscale Serve access.

## Prerequisites

- Node.js 20.18+ and npm 10.2+
- Docker Engine and Docker Compose v2 for `@wordpress/env`
- PHP and WP-CLI for host-side WordPress operations
- Chromium through Playwright: `npm run setup:browsers`
- Tailscale logged into the target tailnet

## Quick Start

```bash
npm install
npm run setup:browsers
npm run verify
npm run wp-env:start
npm run wp-env:seed
npm run verify:site
The default @wordpress/env local URL is http://localhost:8888. Give remote users the Tailscale Serve URL from tailscale serve status, not a localhost URL.
Tailscale Serve
TAILSCALE_HOST="$(tailscale status --json | jq -r '.Self.DNSName | sub("\\.$"; "")')"
sudo tailscale set --operator="$USER" || true
tailscale serve --bg --yes 8888
npm run wp-env:seed
echo "Open: https://${TAILSCALE_HOST}"
Fast Disposable Playground
npm run playground:start
This runs blueprints/local-dev/blueprint.json and mounts the local theme and plugin into Playground. Playground is SQLite-backed and intentionally disposable; use it for fast smoke tests and demos, not production parity.
On low-CPU CI-style hosts, npm run playground:blueprint may hit a Playground WASM file-lock timeout. Treat wp-env plus Playwright/Lighthouse as the authoritative verification path when that happens.

- [ ] **Step 8: Install npm dependencies**

Run:

```bash
npm install
Expected: package-lock.json is created. Dev-only audit findings may appear; production audit must be checked later.

Step 9: Commit base files
Run:
git add .gitignore .nvmrc .wp-env.json AGENTS.md README.md package.json package-lock.json
git commit -m "chore: add agentic WordPress project scaffold"
Expected: commit succeeds. If the repository has an existing commit workflow, use that workflow and keep these files staged together.
Task 3: Install WordPress Agent Skills Project-Locally
Files:
Create: .codex/skills/


Step 1: Clone the WordPress Agent Skills repository

Run:
rm -rf /tmp/wordpress-agent-skills
git clone --depth 1 --branch trunk https://github.com/WordPress/agent-skills.git /tmp/wordpress-agent-skills
Expected: /tmp/wordpress-agent-skills/skills/wordpress-router/SKILL.md exists.

Step 2: Build and install all Codex skills into the project
Run:
node /tmp/wordpress-agent-skills/shared/scripts/skillpack-build.mjs --clean --targets=codex --out=/tmp/wordpress-agent-skills/dist
node /tmp/wordpress-agent-skills/shared/scripts/skillpack-install.mjs --from=/tmp/wordpress-agent-skills/dist --dest="$PWD" --targets=codex
Expected: output says OK: installed 17 skill(s) to .codex/skills.

Step 3: Verify skill installation
Run:
find .codex/skills -maxdepth 2 -name SKILL.md | sort
test -f .codex/skills/wordpress-router/SKILL.md
test -f .codex/skills/wp-block-themes/SKILL.md
test -f .codex/skills/wp-playground/SKILL.md
test -f .codex/skills/wp-performance/SKILL.md
Expected: the test commands exit 0.

Step 4: Commit skills
Run:
git add .codex/skills
git commit -m "chore: install WordPress Agent Skills for Codex"
Expected: commit succeeds.
Task 4: Custom Block Theme From Scratch
Files:
Create: wp-content/themes/custom-block-theme/style.css
Create: wp-content/themes/custom-block-theme/theme.json
Create: wp-content/themes/custom-block-theme/functions.php
Create: wp-content/themes/custom-block-theme/parts/header.html
Create: wp-content/themes/custom-block-theme/parts/footer.html
Create: wp-content/themes/custom-block-theme/templates/index.html
Create: wp-content/themes/custom-block-theme/templates/front-page.html
Create: wp-content/themes/custom-block-theme/templates/page.html
Create: wp-content/themes/custom-block-theme/templates/single.html
Create: wp-content/themes/custom-block-theme/templates/archive.html
Create: wp-content/themes/custom-block-theme/templates/404.html
Create: wp-content/themes/custom-block-theme/patterns/hero.php
Create: wp-content/themes/custom-block-theme/patterns/content-band.php
Create: wp-content/themes/custom-block-theme/styles/editorial.json
Create: wp-content/themes/custom-block-theme/styles/high-contrast.json
Create: wp-content/themes/custom-block-theme/assets/css/blocks/navigation.css
This task assumes the project is new or experimental. Do not preserve Site Editor database customizations, existing template edits, active default-theme state, menus, demo pages, widgets, or theme mods. The objective is to create a custom filesystem block theme that Codex can inspect, diff, test, and evolve.

Step 1: Confirm this is a custom-theme build, not a default-theme edit
Run:
find wp-content/themes -maxdepth 2 -name style.css | sort | rg 'twentytwenty|twenty' || true
test ! -d wp-content/themes/custom-block-theme || echo "Custom theme directory already exists."
Expected: if bundled default themes exist, the command prints their style.css paths; do not edit those themes. Continue with wp-content/themes/custom-block-theme. If custom-block-theme already exists, inspect it before overwriting:
find wp-content/themes/custom-block-theme -maxdepth 3 -type f | sort

Step 2: Create the custom theme directories
Run:
mkdir -p wp-content/themes/custom-block-theme/templates
mkdir -p wp-content/themes/custom-block-theme/parts
mkdir -p wp-content/themes/custom-block-theme/patterns
mkdir -p wp-content/themes/custom-block-theme/styles
mkdir -p wp-content/themes/custom-block-theme/assets/css/blocks
mkdir -p wp-content/themes/custom-block-theme/assets/js
Expected: every listed directory exists.

Step 3: Establish the theme design contract
Use this exact contract for the first custom theme iteration:
Theme slug: custom-block-theme
Theme name: Custom Block Theme
Text domain: custom-block-theme
Minimum WordPress: 6.9
Minimum PHP: 8.1
Primary audience: site readers and content editors
Visual posture: warm, editorial, fast, readable, not page-builder-heavy
Technical posture: block theme, source-controlled files, tiny CSS, semantic HTML landmarks
SEO posture: theme handles markup and readability; plugin handles metadata/schema
Expected: later files use these names exactly.

Step 4: Define the theme file ownership rules
Add these rules to the implementation notes for the worker:
style.css: WordPress theme header and a few global safety styles only.
theme.json: design tokens, layout widths, typography, colors, spacing, element defaults, and block-level defaults.
templates/*.html: template hierarchy and page structure.
parts/*.html: header/footer template parts only; no nested directories.
patterns/*.php: reusable content sections owned by the theme.
styles/*.json: style variations only.
assets/css/blocks/*.css: narrowly scoped block CSS loaded only when the block appears.
functions.php: theme setup and asset enqueueing only; no SEO, schema, CPTs, analytics, forms, or redirects.
Expected: no durable site functionality is added to the theme.

Step 5: Create style.css
Write:
/*
Theme Name: Custom Block Theme
Theme URI: https://example.com/
Author: Codex
Description: Minimal cache-friendly block theme for agentic WordPress development.
Requires at least: 6.9
Tested up to: 6.9
Requires PHP: 8.1
Version: 0.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: custom-block-theme
*/

:where(a) {
  text-underline-offset: 0.18em;
}

:where(.wp-site-blocks) {
  min-height: 100vh;
}

:where(.wp-block-navigation a:focus-visible, .wp-block-button__link:focus-visible) {
  outline: 2px solid currentColor;
  outline-offset: 3px;
}

:where(.wp-block-group[style*="min-height"]) {
  box-sizing: border-box;
}

Step 6: Create theme.json
Write:
{
  "$schema": "https://schemas.wp.org/trunk/theme.json",
  "version": 3,
  "settings": {
    "appearanceTools": true,
    "color": {
      "custom": false,
      "defaultDuotone": false,
      "defaultGradients": false,
      "defaultPalette": false,
      "palette": [
        { "slug": "base", "color": "#ffffff", "name": "Base" },
        { "slug": "contrast", "color": "#171717", "name": "Contrast" },
        { "slug": "surface", "color": "#fbfaf7", "name": "Surface" },
        { "slug": "muted", "color": "#f1ede5", "name": "Muted" },
        { "slug": "primary", "color": "#25635f", "name": "Primary" },
        { "slug": "accent", "color": "#b45309", "name": "Accent" },
        { "slug": "soft-blue", "color": "#dbeafe", "name": "Soft Blue" }
      ]
    },
    "layout": {
      "contentSize": "720px",
      "wideSize": "1120px"
    },
    "spacing": {
      "spacingScale": { "steps": 0 },
      "spacingSizes": [
        { "slug": "20", "size": "0.5rem", "name": "2" },
        { "slug": "30", "size": "1rem", "name": "3" },
        { "slug": "40", "size": "1.5rem", "name": "4" },
        { "slug": "50", "size": "2.5rem", "name": "5" },
        { "slug": "60", "size": "4rem", "name": "6" }
      ],
      "units": [ "px", "rem", "%" ]
    },
    "typography": {
      "customFontSize": false,
      "fontFamilies": [
        {
          "slug": "system",
          "name": "System",
          "fontFamily": "-apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif"
        },
        {
          "slug": "serif",
          "name": "Serif",
          "fontFamily": "Georgia, \"Times New Roman\", serif"
        }
      ],
      "fontSizes": [
        { "slug": "small", "size": "0.9rem", "name": "Small" },
        { "slug": "medium", "size": "1rem", "name": "Medium" },
        { "slug": "large", "size": "1.35rem", "name": "Large" },
        { "slug": "x-large", "size": "clamp(2.25rem, 6vw, 4.75rem)", "name": "Extra Large" }
      ]
    }
  },
  "styles": {
    "color": {
      "background": "var:preset|color|base",
      "text": "var:preset|color|contrast"
    },
    "spacing": {
      "blockGap": "var:preset|spacing|40",
      "padding": {
        "right": "var:preset|spacing|40",
        "left": "var:preset|spacing|40"
      }
    },
    "typography": {
      "fontFamily": "var:preset|font-family|system",
      "fontSize": "var:preset|font-size|medium",
      "lineHeight": "1.6"
    },
    "elements": {
      "button": {
        "border": { "radius": "6px" },
        "color": {
          "background": "var:preset|color|contrast",
          "text": "var:preset|color|base"
        },
        "spacing": {
          "padding": {
            "top": "0.75rem",
            "right": "1rem",
            "bottom": "0.75rem",
            "left": "1rem"
          }
        }
      },
      "heading": {
        "typography": {
          "fontFamily": "var:preset|font-family|serif",
          "fontWeight": "600",
          "lineHeight": "1.1"
        }
      },
      "link": {
        "color": { "text": "var:preset|color|primary" }
      }
    },
    "blocks": {
      "core/button": {
        "border": { "radius": "6px" }
      },
      "core/group": {
        "spacing": {
          "blockGap": "var:preset|spacing|40"
        }
      },
      "core/navigation": {
        "typography": {
          "fontSize": "var:preset|font-size|small",
          "fontWeight": "600"
        }
      },
      "core/post-title": {
        "typography": { "fontSize": "var:preset|font-size|x-large" }
      }
    }
  },
  "templateParts": [
    { "name": "header", "title": "Header", "area": "header" },
    { "name": "footer", "title": "Footer", "area": "footer" }
  ]
}

Step 7: Create functions.php
Write:
<?php
/**
 * Custom Block Theme setup.
 *
 * @package CustomBlockTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'wp-block-styles' );
		add_editor_style( 'style.css' );
	}
);

add_action(
	'init',
	function () {
		wp_enqueue_block_style(
			'core/navigation',
			array(
				'handle' => 'custom-block-theme-navigation',
				'src'    => get_theme_file_uri( 'assets/css/blocks/navigation.css' ),
				'path'   => get_theme_file_path( 'assets/css/blocks/navigation.css' ),
			)
		);
	}
);

Step 8: Create block-specific navigation CSS
Write assets/css/blocks/navigation.css:
.wp-block-navigation__responsive-container.is-menu-open {
  padding: var(--wp--preset--spacing--40);
}

.wp-block-navigation__responsive-container-close,
.wp-block-navigation__responsive-container-open {
  border-radius: 6px;
}

Step 9: Create template parts
Write parts/header.html:
<!-- wp:group {"tagName":"header","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<header class="wp-block-group alignfull">
	<!-- wp:group {"align":"wide","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:site-title {"level":0,"style":{"typography":{"fontWeight":"700"}}} /-->
		<!-- wp:navigation {"overlayMenu":"mobile","layout":{"type":"flex","justifyContent":"right"}} /-->
	</div>
	<!-- /wp:group -->
</header>
<!-- /wp:group -->
Write parts/footer.html:
<!-- wp:group {"tagName":"footer","align":"full","backgroundColor":"muted","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<footer class="wp-block-group alignfull has-muted-background-color has-background">
	<!-- wp:group {"align":"wide","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:paragraph {"fontSize":"small"} -->
		<p class="has-small-font-size">This site is built on a custom block theme designed for fast iteration and readable publishing.</p>
		<!-- /wp:paragraph -->
		<!-- wp:site-title {"level":0,"fontSize":"small"} /-->
	</div>
	<!-- /wp:group -->
</footer>
<!-- /wp:group -->

Step 10: Create templates
Write templates/front-page.html:
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->
<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->
<main class="wp-block-group">
	<!-- wp:post-content {"layout":{"type":"constrained"}} /-->
</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
Write templates/page.html:
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->
<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->
<main class="wp-block-group">
	<!-- wp:post-title {"level":1} /-->
	<!-- wp:post-content {"layout":{"type":"constrained"}} /-->
</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
Write templates/single.html:
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->
<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->
<main class="wp-block-group">
	<!-- wp:post-title {"level":1} /-->
	<!-- wp:post-date /-->
	<!-- wp:post-content {"layout":{"type":"constrained"}} /-->
</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
Write templates/index.html:
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->
<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->
<main class="wp-block-group">
	<!-- wp:query-title {"type":"archive"} /-->
	<!-- wp:query {"query":{"perPage":10,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":true},"layout":{"type":"default"}} -->
	<div class="wp-block-query">
		<!-- wp:post-template -->
			<!-- wp:post-title {"isLink":true} /-->
			<!-- wp:post-excerpt /-->
		<!-- /wp:post-template -->
		<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"space-between"}} -->
			<!-- wp:query-pagination-previous /-->
			<!-- wp:query-pagination-next /-->
		<!-- /wp:query-pagination -->
	</div>
	<!-- /wp:query -->
</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
Write templates/archive.html:
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->
<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->
<main class="wp-block-group">
	<!-- wp:query-title {"type":"archive"} /-->
	<!-- wp:term-description /-->
	<!-- wp:query {"query":{"perPage":10,"postType":"post","inherit":true},"layout":{"type":"default"}} -->
	<div class="wp-block-query">
		<!-- wp:post-template -->
			<!-- wp:post-title {"isLink":true} /-->
			<!-- wp:post-excerpt /-->
		<!-- /wp:post-template -->
		<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"space-between"}} -->
			<!-- wp:query-pagination-previous /-->
			<!-- wp:query-pagination-next /-->
		<!-- /wp:query-pagination -->
	</div>
	<!-- /wp:query -->
</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
Write templates/404.html:
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->
<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->
<main class="wp-block-group">
	<!-- wp:heading {"level":1} -->
	<h1>Page not found</h1>
	<!-- /wp:heading -->
	<!-- wp:paragraph -->
	<p>The page may have moved. Search the site or return to the homepage.</p>
	<!-- /wp:paragraph -->
	<!-- wp:search {"label":"Search","buttonText":"Search"} /-->
</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->

Step 11: Create theme patterns
Write patterns/hero.php:
<?php
/**
 * Title: Cache-friendly hero
 * Slug: custom-block-theme/hero
 * Categories: featured
 *
 * @package CustomBlockTheme
 */
?>
<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","wideSize":"960px"}} -->
<div class="wp-block-group alignwide">
	<!-- wp:heading {"level":1,"fontSize":"x-large"} -->
	<h1 class="has-x-large-font-size">A fast custom WordPress block theme</h1>
	<!-- /wp:heading -->
	<!-- wp:paragraph {"fontSize":"large"} -->
	<p class="has-large-font-size">This custom theme pairs thoughtful content structure with fast WordPress block rendering.</p>
	<!-- /wp:paragraph -->
	<!-- wp:buttons -->
	<div class="wp-block-buttons">
		<!-- wp:button -->
		<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/about/">Start here</a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
Write patterns/content-band.php:
<?php
/**
 * Title: Editorial intro band
 * Slug: custom-block-theme/content-band
 * Categories: text
 *
 * @package CustomBlockTheme
 */
?>
<!-- wp:group {"align":"wide","backgroundColor":"muted","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","right":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide has-muted-background-color has-background">
	<!-- wp:heading -->
	<h2>Readable by people, easy for agents to maintain</h2>
	<!-- /wp:heading -->
	<!-- wp:paragraph -->
	<p>The theme keeps templates, patterns, and design tokens in files so every change can be reviewed, tested, and improved without page-builder drift.</p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

Step 12: Create style variations
Write styles/editorial.json:
{
  "$schema": "https://schemas.wp.org/trunk/theme.json",
  "version": 3,
  "title": "Editorial",
  "styles": {
    "color": {
      "background": "#fbfaf7",
      "text": "#171717"
    },
    "elements": {
      "link": {
        "color": {
          "text": "#25635f"
        }
      }
    }
  }
}
Write styles/high-contrast.json:
{
  "$schema": "https://schemas.wp.org/trunk/theme.json",
  "version": 3,
  "title": "High Contrast",
  "styles": {
    "color": {
      "background": "#ffffff",
      "text": "#111111"
    },
    "elements": {
      "button": {
        "color": {
          "background": "#111111",
          "text": "#ffffff"
        }
      },
      "link": {
        "color": {
          "text": "#064e3b"
        }
      }
    }
  }
}

Step 13: Verify the theme filesystem shape
Run:
find wp-content/themes/custom-block-theme -maxdepth 3 -type f | sort
test -f wp-content/themes/custom-block-theme/templates/index.html
test -f wp-content/themes/custom-block-theme/theme.json
test -f wp-content/themes/custom-block-theme/styles/editorial.json
test -f wp-content/themes/custom-block-theme/assets/css/blocks/navigation.css
Expected: all theme files are listed and all test commands exit 0.

Step 14: Validate theme PHP and JSON
Run:
php -l wp-content/themes/custom-block-theme/functions.php
node -e 'JSON.parse(require("fs").readFileSync("wp-content/themes/custom-block-theme/theme.json","utf8")); console.log("theme.json ok")'
node -e 'JSON.parse(require("fs").readFileSync("wp-content/themes/custom-block-theme/styles/editorial.json","utf8")); console.log("editorial variation ok")'
node -e 'JSON.parse(require("fs").readFileSync("wp-content/themes/custom-block-theme/styles/high-contrast.json","utf8")); console.log("high contrast variation ok")'
Expected: no PHP syntax errors and all JSON checks print ok.

Step 15: Start WordPress and activate the custom theme
Run:
npm run wp-env:start
npm run wp-env:seed
npx --no-install wp-env run cli wp theme activate custom-block-theme
npx --no-install wp-env run cli wp theme list --status=active
Expected: active theme is custom-block-theme.

Step 16: Verify the custom theme renders
Run:
curl -sS http://localhost:8888 | rg 'A fast custom WordPress block theme|custom block theme'
npm run test:screenshots
Expected: curl finds the custom hero or site text, and Playwright reports 4 passed.

Step 17: Commit theme
Run:
git add wp-content/themes/custom-block-theme
git commit -m "feat: add custom block theme"
Expected: commit succeeds.
Task 5: Site-Core Plugin for Durable SEO, Schema, and Tailscale URL Behavior
Files:
Create: wp-content/plugins/agentic-site-core/agentic-site-core.php


Step 1: Create plugin file

Write:
<?php
/**
 * Plugin Name: Agentic Site Core
 * Description: Durable site behavior for the agentic WordPress starter.
 * Version: 0.1.0
 * Requires at least: 6.9
 * Requires PHP: 8.1
 * Author: Codex
 * License: GPL-2.0-or-later
 * Text Domain: agentic-site-core
 *
 * @package AgenticSiteCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'pre_option_home', 'agentic_site_core_tailnet_url_for_serve' );
add_filter( 'pre_option_siteurl', 'agentic_site_core_tailnet_url_for_serve' );
add_action( 'wp_head', 'agentic_site_core_output_meta_description', 5 );
add_action( 'wp_head', 'agentic_site_core_output_json_ld', 20 );

/**
 * Uses the Tailscale Serve URL when the request arrives through MagicDNS.
 *
 * @param mixed $value Existing pre-option value.
 * @return mixed
 */
function agentic_site_core_tailnet_url_for_serve( mixed $value ): mixed {
	$tailnet_host = trim( (string) get_option( 'agentic_tailnet_host', '' ) );

	if ( '' === $tailnet_host ) {
		return $value;
	}

	$host = isset( $_SERVER['HTTP_X_FORWARDED_HOST'] )
		? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_HOST'] ) )
		: sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ?? '' ) );

	$host = preg_replace( '/:\d+$/', '', $host );

	if ( $tailnet_host !== $host ) {
		return $value;
	}

	return 'https://' . $tailnet_host;
}

/**
 * Outputs a concise meta description for baseline SEO checks.
 */
function agentic_site_core_output_meta_description(): void {
	if ( is_admin() || is_feed() || is_robots() ) {
		return;
	}

	$description = get_bloginfo( 'description' );

	if ( is_singular() ) {
		$excerpt = get_the_excerpt();
		if ( '' !== trim( $excerpt ) ) {
			$description = $excerpt;
		}
	}

	$description = wp_html_excerpt( wp_strip_all_tags( $description ), 155, '...' );

	if ( '' === trim( $description ) ) {
		return;
	}

	printf(
		'<meta name="description" content="%s" />' . "\n",
		esc_attr( $description )
	);
}

/**
 * Outputs minimal JSON-LD that reflects visible site identity.
 */
function agentic_site_core_output_json_ld(): void {
	if ( is_admin() || is_feed() || is_robots() ) {
		return;
	}

	$site_url = home_url( '/' );
	$name     = get_bloginfo( 'name' );

	$graph = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'WebSite',
		'name'            => $name,
		'url'             => $site_url,
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => add_query_arg( 's', '{search_term_string}', $site_url ),
			'query-input' => 'required name=search_term_string',
		),
	);

	if ( is_singular() ) {
		$graph = array(
			'@context'     => 'https://schema.org',
			'@type'        => is_front_page() ? 'WebPage' : 'Article',
			'headline'     => wp_strip_all_tags( get_the_title() ),
			'url'          => get_permalink(),
			'isPartOf'     => array(
				'@type' => 'WebSite',
				'name'  => $name,
				'url'   => $site_url,
			),
			'dateModified' => get_the_modified_date( DATE_W3C ),
		);
	}

	printf(
		'<script type="application/ld+json">%s</script>' . "\n",
		wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
	);
}

Step 2: Validate plugin syntax
Run:
php -l wp-content/plugins/agentic-site-core/agentic-site-core.php
Expected: no syntax errors.

Step 3: Commit plugin
Run:
git add wp-content/plugins/agentic-site-core
git commit -m "feat: add durable site-core plugin"
Expected: commit succeeds.
Task 6: Playground Blueprint
Files:
Create: blueprints/local-dev/blueprint.json


Step 1: Create blueprints/local-dev/blueprint.json

Write:
{
  "$schema": "https://playground.wordpress.net/blueprint-schema.json",
  "landingPage": "/",
  "meta": {
    "title": "Agentic WordPress Starter",
    "author": "Codex",
    "description": "Disposable Playground setup for the custom block theme."
  },
  "preferredVersions": {
    "php": "8.3",
    "wp": "6.9"
  },
  "features": {
    "networking": true
  },
  "extraLibraries": [
    "wp-cli"
  ],
  "constants": {
    "WP_DEBUG": true,
    "SCRIPT_DEBUG": true
  },
  "steps": [
    { "step": "login" },
    {
      "step": "setSiteOptions",
      "options": {
        "blogname": "Agentic WordPress Starter",
        "blogdescription": "Fast, semantic, cache-friendly WordPress development."
      }
    },
    { "step": "activateTheme", "themeFolderName": "custom-block-theme" },
    { "step": "activatePlugin", "pluginPath": "agentic-site-core/agentic-site-core.php" },
    { "step": "wp-cli", "command": "wp rewrite structure '/%postname%/'" },
    {
      "step": "runPHP",
      "code": "<?php require '/wordpress/wp-load.php';\n$home = get_page_by_path('home');\nif (!$home) {\n    $home_id = wp_insert_post(array(\n        'post_type' => 'page',\n        'post_title' => 'Home',\n        'post_name' => 'home',\n        'post_status' => 'publish',\n        'post_content' => '<!-- wp:pattern {\"slug\":\"custom-block-theme/hero\"} /--><!-- wp:pattern {\"slug\":\"custom-block-theme/content-band\"} /-->'\n    ));\n} else {\n    $home_id = $home->ID;\n}\n$about = get_page_by_path('about');\nif (!$about) {\n    wp_insert_post(array(\n        'post_type' => 'page',\n        'post_title' => 'About',\n        'post_name' => 'about',\n        'post_status' => 'publish',\n        'post_content' => '<!-- wp:heading --><h2>Built for agentic iteration</h2><!-- /wp:heading --><!-- wp:paragraph --><p>This page is seeded by the local Blueprint so screenshots and Lighthouse checks have stable content.</p><!-- /wp:paragraph -->'\n    ));\n}\nupdate_option('show_on_front', 'page');\nupdate_option('page_on_front', $home_id);\nflush_rewrite_rules();"
    }
  ]
}

Step 2: Validate Blueprint JSON
Run:
node -e 'JSON.parse(require("fs").readFileSync("blueprints/local-dev/blueprint.json","utf8")); console.log("blueprint ok")'
Expected: blueprint ok.

Step 3: Commit Blueprint
Run:
git add blueprints/local-dev/blueprint.json
git commit -m "chore: add WordPress Playground Blueprint"
Expected: commit succeeds.
Task 7: Verification Scripts
Files:
Create: tools/check-static.mjs

Create: tools/check-prereqs.mjs

Create: tools/seed-wp-env.mjs

Create: tools/run-playground.mjs

Create: tools/run-lighthouse.mjs

Create: tests/playwright.config.mjs

Create: tests/visual.spec.mjs


Step 1: Create tools/check-static.mjs

Write:
import fs from "node:fs";
import path from "node:path";

const root = process.cwd();
const requiredFiles = [
  "AGENTS.md",
  ".wp-env.json",
  "blueprints/local-dev/blueprint.json",
  "wp-content/themes/custom-block-theme/style.css",
  "wp-content/themes/custom-block-theme/theme.json",
  "wp-content/themes/custom-block-theme/templates/index.html",
  "wp-content/themes/custom-block-theme/parts/header.html",
  "wp-content/themes/custom-block-theme/parts/footer.html",
  "wp-content/plugins/agentic-site-core/agentic-site-core.php",
  ".codex/skills/wordpress-router/SKILL.md",
  ".codex/skills/wp-playground/SKILL.md",
  ".codex/skills/wp-block-themes/SKILL.md"
];
const jsonFiles = [
  "package.json",
  ".wp-env.json",
  "blueprints/local-dev/blueprint.json",
  "wp-content/themes/custom-block-theme/theme.json"
];
let failures = 0;
for (const file of requiredFiles) {
  if (!fs.existsSync(path.join(root, file))) {
    console.error(`missing required file: ${file}`);
    failures += 1;
  }
}
for (const file of jsonFiles) {
  try {
    JSON.parse(fs.readFileSync(path.join(root, file), "utf8"));
  } catch (error) {
    console.error(`invalid JSON in ${file}: ${error.message}`);
    failures += 1;
  }
}
const blueprint = JSON.parse(fs.readFileSync(path.join(root, "blueprints/local-dev/blueprint.json"), "utf8"));
if (blueprint.$schema !== "https://playground.wordpress.net/blueprint-schema.json") {
  console.error("blueprint uses an unexpected schema URL");
  failures += 1;
}
const themeJson = JSON.parse(fs.readFileSync(path.join(root, "wp-content/themes/custom-block-theme/theme.json"), "utf8"));
if (themeJson.version !== 3) {
  console.error("theme.json must use version 3");
  failures += 1;
}
if (failures > 0) process.exit(1);
console.log("Static project checks passed.");

Step 2: Create tools/check-prereqs.mjs
Write:
import { spawnSync } from "node:child_process";
import fs from "node:fs";

const strict = process.argv.includes("--strict");
function commandExists(command) {
  return spawnSync("bash", ["-lc", `command -v ${command}`], { encoding: "utf8" }).status === 0;
}
function commandOutput(command, args) {
  const result = spawnSync(command, args, { encoding: "utf8" });
  return { ok: result.status === 0, output: `${result.stdout || ""}${result.stderr || ""}`.trim() };
}
function parseVersion(value) {
  const match = value.match(/(\d+)\.(\d+)\.(\d+)/);
  return match ? match.slice(1).map((part) => Number(part)) : null;
}
function isAtLeast(actual, minimum) {
  if (!actual) return false;
  for (let i = 0; i < minimum.length; i += 1) {
    if (actual[i] > minimum[i]) return true;
    if (actual[i] < minimum[i]) return false;
  }
  return true;
}
const checks = [];
checks.push({ name: "Node.js", ok: isAtLeast(parseVersion(process.version), [20, 18, 0]), detail: `${process.version}; required >=20.18.0 for WordPress Playground CLI` });
const npm = commandOutput("npm", ["--version"]);
checks.push({ name: "npm", ok: npm.ok && isAtLeast(parseVersion(npm.output), [10, 2, 3]), detail: npm.ok ? `${npm.output}; required >=10.2.3` : "not found" });
const php = commandOutput("php", ["--version"]);
checks.push({ name: "PHP", ok: php.ok, detail: php.ok ? php.output.split("\n")[0] : "not found" });
const wp = commandOutput("wp", ["--info"]);
checks.push({ name: "WP-CLI", ok: wp.ok, detail: wp.ok ? "available" : "not found" });
const dockerInstalled = commandExists("docker");
const dockerInfo = dockerInstalled ? commandOutput("docker", ["info"]) : { ok: false, output: "not found" };
checks.push({ name: "Docker", ok: dockerInstalled && dockerInfo.ok, detail: dockerInstalled ? (dockerInfo.ok ? "daemon available" : "installed but daemon unavailable") : "not found; required for @wordpress/env" });
const dockerCompose = dockerInstalled ? commandOutput("docker", ["compose", "version"]) : { ok: false, output: "not found" };
checks.push({ name: "Docker Compose", ok: dockerCompose.ok, detail: dockerCompose.ok ? dockerCompose.output : "not found; required for @wordpress/env" });
const composer = commandOutput("composer", ["--version"]);
checks.push({ name: "Composer", ok: composer.ok, detail: composer.ok ? composer.output.split("\n")[0] : "not found; optional until PHP static analysis is added" });
const systemChromium = commandExists("google-chrome") || commandExists("chromium") || commandExists("chromium-browser");
let playwrightChromium = false;
try {
  const { chromium } = await import("@playwright/test");
  playwrightChromium = fs.existsSync(chromium.executablePath());
} catch {
  playwrightChromium = false;
}
checks.push({
  name: "Automation browser",
  ok: systemChromium || playwrightChromium,
  detail: systemChromium ? "system Chrome/Chromium available" : playwrightChromium ? "Playwright Chromium available" : "not found; run npm run setup:browsers"
});
let missingRequired = 0;
for (const check of checks) {
  console.log(`${(check.ok ? "OK" : "MISSING").padEnd(8)} ${check.name}: ${check.detail}`);
  if (!check.ok && ["Node.js", "npm", "PHP", "WP-CLI", "Docker", "Docker Compose"].includes(check.name)) {
    missingRequired += 1;
  }
}
if (strict && missingRequired > 0) process.exit(1);
if (missingRequired > 0) {
  console.log("\nSome required runtime prerequisites are missing. Static checks can still run, but wp-env/Playground runtime verification will be blocked.");
}

Step 3: Create tools/seed-wp-env.mjs
Write:
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

runWp(["theme", "activate", "custom-block-theme"]);
runWp(["plugin", "activate", "agentic-site-core"]);
runWp(["rewrite", "structure", "/%postname%/"]);

const tailnetHost = detectTailnetHost();
if (tailnetHost) runWp(["option", "update", "agentic_tailnet_host", tailnetHost]);

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
        'post_content' => '<!-- wp:pattern {"slug":"custom-block-theme/hero"} /--><!-- wp:pattern {"slug":"custom-block-theme/content-band"} /-->'
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
        'post_content' => '<!-- wp:heading --><h2>Built for agentic iteration</h2><!-- /wp:heading --><!-- wp:paragraph --><p>This page is seeded by the local environment so screenshots and Lighthouse checks have stable content.</p><!-- /wp:paragraph -->'
    ));
}
update_option('blogname', 'Agentic WordPress Starter');
update_option('blogdescription', 'Fast, semantic, cache-friendly WordPress development.');
update_option('show_on_front', 'page');
update_option('page_on_front', $home_id);
flush_rewrite_rules();
`
]);
console.log("Seeded wp-env site content.");

Step 4: Create tools/run-playground.mjs
Write:
import { spawnSync } from "node:child_process";
import path from "node:path";
import process from "node:process";

function parseVersion(value) {
  const match = value.match(/(\d+)\.(\d+)\.(\d+)/);
  return match ? match.slice(1).map((part) => Number(part)) : null;
}
function isAtLeast(actual, minimum) {
  if (!actual) return false;
  for (let i = 0; i < minimum.length; i += 1) {
    if (actual[i] > minimum[i]) return true;
    if (actual[i] < minimum[i]) return false;
  }
  return true;
}
if (!isAtLeast(parseVersion(process.version), [20, 18, 0])) {
  console.error(`WordPress Playground CLI requires Node >=20.18.0. Current: ${process.version}`);
  process.exit(1);
}
const root = process.cwd();
const port = process.env.PLAYGROUND_PORT || "9400";
const themePath = path.join(root, "wp-content/themes/custom-block-theme");
const pluginPath = path.join(root, "wp-content/plugins/agentic-site-core");
const runBlueprintOnly = process.argv.includes("--run-blueprint");
const args = [
  "--no-install",
  "wp-playground-cli",
  runBlueprintOnly ? "run-blueprint" : "server",
  "--blueprint=./blueprints/local-dev",
  "--blueprint-may-read-adjacent-files",
  `--mount=${themePath}:/wordpress/wp-content/themes/custom-block-theme`,
  `--mount=${pluginPath}:/wordpress/wp-content/plugins/agentic-site-core`,
  "--php=8.3",
  "--wp=6.9"
];
if (!runBlueprintOnly) {
  args.push(`--port=${port}`);
  console.log(`Starting WordPress Playground on http://localhost:${port}`);
} else {
  console.log("Running WordPress Playground Blueprint smoke test.");
}
const result = spawnSync("npx", args, {
  stdio: "inherit",
  timeout: runBlueprintOnly ? Number(process.env.PLAYGROUND_TIMEOUT_MS || 120000) : undefined
});
if (result.error?.code === "ETIMEDOUT") {
  console.error("Playground Blueprint smoke test timed out. Use wp-env for authoritative local verification on this host.");
  process.exit(124);
}
process.exit(result.status ?? 1);

Step 5: Create tools/run-lighthouse.mjs
Write:
import fs from "node:fs";
import { spawnSync } from "node:child_process";
import path from "node:path";

const url = process.env.WP_BASE_URL || "http://localhost:8888";
const outDir = path.join(process.cwd(), "artifacts/lighthouse");
const outFile = path.join(outDir, "report.json");
fs.mkdirSync(outDir, { recursive: true });
let chromePath = process.env.CHROME_PATH || "";
if (!chromePath) {
  try {
    const { chromium } = await import("@playwright/test");
    chromePath = chromium.executablePath();
  } catch {
    chromePath = "";
  }
}
const env = { ...process.env };
if (chromePath) env.CHROME_PATH = chromePath;
const result = spawnSync("npx", [
  "--no-install",
  "lighthouse",
  url,
  "--quiet",
  "--output=json",
  `--output-path=${outFile}`,
  "--only-categories=performance,accessibility,best-practices,seo",
  "--chrome-flags=--headless=new --no-sandbox"
], { encoding: "utf8", env });
if (result.status !== 0) {
  console.error(result.stdout);
  console.error(result.stderr);
  console.error("Lighthouse failed. Ensure the WordPress site is running and Chromium is installed with npm run setup:browsers.");
  process.exit(result.status ?? 1);
}
const report = JSON.parse(fs.readFileSync(outFile, "utf8"));
const scores = Object.fromEntries(Object.entries(report.categories).map(([key, category]) => [key, Math.round(category.score * 100)]));
console.log(`Lighthouse report written to ${outFile}`);
console.log(JSON.stringify(scores, null, 2));
const minimums = {
  performance: Number(process.env.LH_PERFORMANCE_MIN || 85),
  accessibility: Number(process.env.LH_ACCESSIBILITY_MIN || 95),
  "best-practices": Number(process.env.LH_BEST_PRACTICES_MIN || 90),
  seo: Number(process.env.LH_SEO_MIN || 95)
};
let failed = false;
for (const [category, minimum] of Object.entries(minimums)) {
  if ((scores[category] ?? 0) < minimum) {
    console.error(`${category} score ${scores[category]} is below minimum ${minimum}`);
    failed = true;
  }
}
if (failed) process.exit(1);

Step 6: Create Playwright config and tests
Write tests/playwright.config.mjs:
import { defineConfig, devices } from "@playwright/test";

export default defineConfig({
  testDir: ".",
  timeout: 30000,
  outputDir: "../artifacts/playwright",
  reporter: [["list"], ["html", { outputFolder: "artifacts/playwright-report", open: "never" }]],
  use: {
    baseURL: process.env.WP_BASE_URL || "http://localhost:8888",
    trace: "retain-on-failure",
    screenshot: "only-on-failure"
  },
  projects: [
    { name: "desktop", use: { ...devices["Desktop Chrome"], viewport: { width: 1440, height: 1100 } } },
    { name: "mobile", use: { ...devices["Pixel 5"] } }
  ]
});
Write tests/visual.spec.mjs:
import fs from "node:fs";
import path from "node:path";
import { expect, test } from "@playwright/test";

const screenshotDir = path.join(process.cwd(), "artifacts/screenshots");
const pages = [
  { name: "home", path: "/" },
  { name: "about", path: "/about/" }
];

test.beforeAll(() => {
  fs.mkdirSync(screenshotDir, { recursive: true });
});

for (const pageInfo of pages) {
  test(`${pageInfo.name} renders meaningful content`, async ({ page }, testInfo) => {
    const consoleErrors = [];
    page.on("console", (message) => {
      if (message.type() === "error") consoleErrors.push(message.text());
    });
    await page.goto(pageInfo.path, { waitUntil: "networkidle" });
    await expect(page.locator("body")).toBeVisible();
    const title = await page.title();
    expect(title.length).toBeGreaterThan(0);
    const main = page.locator("main").first();
    await expect(main).toBeVisible();
    await expect(main).not.toHaveText(/^\\s*$/);
    await page.screenshot({
      path: path.join(screenshotDir, `${testInfo.project.name}-${pageInfo.name}.png`),
      fullPage: true
    });
    expect(consoleErrors).toEqual([]);
  });
}

Step 7: Install Playwright browser dependencies
Run:
npm run setup:browsers
sudo env PATH="$PATH" npx playwright install-deps chromium
Expected: Chromium downloads, and Linux browser libraries install without errors.

Step 8: Run static verification
Run:
npm run check:static
npm run check:prereqs
Expected: static checks pass, and all prerequisite lines show OK.

Step 9: Commit verification scripts
Run:
git add tools tests
git commit -m "test: add WordPress verification scripts"
Expected: commit succeeds.
Task 8: Start and Verify wp-env
Files:
Uses files created in previous tasks.


Step 1: Start the WordPress runtime

Run:
npm run wp-env:start
Expected: output includes WordPress development site started at http://localhost:8888.

Step 2: Seed content and activate theme/plugin
Run:
npm run wp-env:seed
Expected: output includes the theme activation, plugin activation, rewrite flush, and Seeded wp-env site content.

Step 3: Confirm local HTTP response
Run:
curl -sS -I http://localhost:8888 | sed -n '1,20p'
Expected: HTTP/1.1 200 OK.

Step 4: Run full site verification
Run:
npm run verify:site
Expected: Playwright reports 4 passed. Lighthouse prints scores at or above:
performance: 85
accessibility: 95
best-practices: 90
seo: 95

Step 5: Check production audit scope
Run:
npm audit --omit=dev
Expected: found 0 vulnerabilities.

Step 6: Commit runtime verification setup
Run:
git add .wp-env.json wp-content package.json package-lock.json README.md AGENTS.md
git commit -m "chore: wire WordPress runtime verification"
Expected: commit succeeds or reports no changes if previous commits already captured the files.
Task 9: Tailscale Serve for Remote Review
Files:
Uses tools/seed-wp-env.mjs

Uses wp-content/plugins/agentic-site-core/agentic-site-core.php


Step 1: Confirm Tailscale is authenticated

Run:
tailscale status --json | jq -r '.BackendState, .Self.DNSName, .AuthURL'
Expected: first line is Running, second line is a MagicDNS name ending in .ts.net., third line is empty. If AuthURL is non-empty, pass that URL to the user and wait for them to authenticate the VM before continuing.

Step 2: Let the current user manage Serve without sudo
Run:
sudo tailscale set --operator="$USER" || true
Expected: command exits 0 or reports no change.

Step 3: Start Tailscale Serve
Run:
tailscale serve --bg --yes 8888
tailscale serve status
Expected: output includes the host printed by this command:
TAILSCALE_HOST="$(tailscale status --json | jq -r '.Self.DNSName | sub("\\.$"; "")')"
printf 'https://%s (tailnet only)\n|-- / proxy http://127.0.0.1:8888\n' "$TAILSCALE_HOST"

Step 4: Seed the detected Tailscale host into WordPress
Run:
TAILSCALE_HOST="$(tailscale status --json | jq -r '.Self.DNSName | sub("\\.$"; "")')"
npm run wp-env:seed
npx --no-install wp-env run cli wp option get agentic_tailnet_host
Expected: the option prints the same host as $TAILSCALE_HOST.

Step 5: Verify Tailscale-hosted requests emit tailnet URLs
Run:
TAILSCALE_HOST="$(tailscale status --json | jq -r '.Self.DNSName | sub("\\.$"; "")')"
curl -sS \
  -H "Host: ${TAILSCALE_HOST}" \
  -H "X-Forwarded-Host: ${TAILSCALE_HOST}" \
  -H "X-Forwarded-Proto: https" \
  http://127.0.0.1:8888 \
  | grep -o "https://${TAILSCALE_HOST}\\|http://localhost:8888" \
  | sort \
  | uniq -c
Expected: output contains https://${TAILSCALE_HOST} and does not contain http://localhost:8888.

Step 6: Give the user the correct URL
Run:
TAILSCALE_HOST="$(tailscale status --json | jq -r '.Self.DNSName | sub("\\.$"; "")')"
echo "Open this from a device logged into the tailnet: https://${TAILSCALE_HOST}"
Expected: give the printed HTTPS URL to the user. Do not give http://localhost:8888 to a remote user.
Task 10: Playground Smoke Path
Files:
Uses blueprints/local-dev/blueprint.json

Uses tools/run-playground.mjs


Step 1: Confirm Playground CLI version

Run:
npx --no-install wp-playground-cli --version
Expected: 3.1.42.

Step 2: Run the headless Blueprint smoke test
Run:
npm run playground:blueprint
Expected: command exits 0 on hosts where Playground file locks behave. If it exits 124 with Playground Blueprint smoke test timed out, record that as a host/runtime limitation and continue to use wp-env as authoritative verification.

Step 3: Start interactive Playground when needed
Run:
npm run playground:start
Expected: output says Playground is starting on http://localhost:9400. If the user needs access, expose that port separately through Tailscale Serve:
tailscale serve --bg --yes --https=9400 9400
tailscale serve status
Expected: status shows the host and port printed by:
TAILSCALE_HOST="$(tailscale status --json | jq -r '.Self.DNSName | sub("\\.$"; "")')"
echo "https://${TAILSCALE_HOST}:9400"
Task 11: Final Verification and Handoff
Files:
All files created above.


Step 1: Run final verification

Run:
npm run verify
npm run wp-env:start
npm run wp-env:seed
npm run verify:site
npm audit --omit=dev
Expected:
Static project checks passed.
All prerequisites OK.
Playwright: 4 passed.
Lighthouse: performance >= 85, accessibility >= 95, best-practices >= 90, seo >= 95.
Production audit: found 0 vulnerabilities.

Step 2: Capture current Serve status
Run:
tailscale serve status
Expected: output shows the tailnet HTTPS URL proxying to http://127.0.0.1:8888.

Step 3: Review git status
Run:
git status --short
Expected: only intentional files are modified or untracked.

Step 4: Commit final documentation
Run:
git add README.md AGENTS.md docs/superpowers/plans
git commit -m "docs: document agentic WordPress environment setup"
Expected: commit succeeds or reports no changes if documentation was committed earlier.

Step 5: Final handoff message
Report these exact items to the user:
WordPress local runtime: http://localhost:8888
Remote tailnet URL: value printed by `TAILSCALE_HOST="$(tailscale status --json | jq -r '.Self.DNSName | sub("\\.$"; "")')" && echo "https://${TAILSCALE_HOST}"`
Primary verification: npm run verify && npm run verify:site
Project-local Codex skills: .codex/skills
Authoritative runtime: @wordpress/env
Disposable runtime: WordPress Playground CLI
Known Playground caveat: low-CPU hosts may hit a WASM file-lock timeout; use wp-env verification when that happens.
Self-Review Checklist

Every required system capability from the request is covered: Agent Skills, Playground CLI/Blueprints, @wordpress/env, WP-CLI, screenshots, Lighthouse, and Tailscale Serve.

The plan avoids raw localhost handoff URLs for remote users.

The theme owns presentation only.

The plugin owns durable SEO/schema/Tailscale URL behavior.

Verification commands prove both static setup and live WordPress runtime behavior.

The Tailscale authentication branch is explicit: if AuthURL is non-empty, pass it to the user and wait.