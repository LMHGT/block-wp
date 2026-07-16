<?php
/*
Plugin Name: LMHG Site Core
Description: Durable site behavior for the LMHG WordPress proof track.
Version: 0.3.2
Requires at least: 6.9
Requires PHP: 8.1
Author: Codex
License: GPL-2.0-or-later
Text Domain: lmhg-site-core
*/

/**
 * @package LMHGSiteCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', true );
}

require_once __DIR__ . '/includes/importer.php';
require_once __DIR__ . '/includes/editable-blocks.php';
require_once __DIR__ . '/includes/redirects.php';
require_once __DIR__ . '/includes/seo.php';
require_once __DIR__ . '/includes/media-assets.php';
require_once __DIR__ . '/includes/discovery.php';
require_once __DIR__ . '/includes/accessibility.php';
require_once __DIR__ . '/includes/surface-controls.php';
require_once __DIR__ . '/includes/rendering.php';
require_once __DIR__ . '/includes/cta.php';
require_once __DIR__ . '/includes/reviews.php';
require_once __DIR__ . '/includes/taxonomies.php';
require_once __DIR__ . '/includes/content-relationships.php';
require_once __DIR__ . '/includes/admin.php';
require_once __DIR__ . '/includes/readiness.php';
require_once __DIR__ . '/includes/rank-math.php';
require_once __DIR__ . '/includes/topology-migrations.php';
require_once __DIR__ . '/includes/page-class-design.php';

add_filter( 'pre_option_home', 'lmhg_site_core_tailnet_url_for_serve' );
add_filter( 'pre_option_siteurl', 'lmhg_site_core_tailnet_url_for_serve' );
add_action( 'init', 'lmhg_site_core_disable_emoji_assets' );

/**
 * Removes default emoji assets from the public scaffold.
 */
function lmhg_site_core_disable_emoji_assets(): void {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}

/**
 * Uses the Tailscale Serve URL when the request arrives through MagicDNS.
 *
 * @param mixed $value Existing pre-option value.
 * @return mixed
 */
function lmhg_site_core_tailnet_url_for_serve( mixed $value ): mixed {
	$tailnet_host = trim( (string) get_option( 'lmhg_tailnet_host', '' ) );

	if ( '' === $tailnet_host ) {
		return $value;
	}

	$request_host = isset( $_SERVER['HTTP_X_FORWARDED_HOST'] )
		? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_HOST'] ) )
		: sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ?? '' ) );

	$host = preg_replace( '/:\d+$/', '', $request_host );

	if ( $tailnet_host !== $host ) {
		return $value;
	}

	$forwarded_proto = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '' ) );
	$scheme = is_ssl() || 'https' === strtolower( $forwarded_proto ) ? 'https' : 'http';

	if ( str_contains( $request_host, ':' ) ) {
		return $scheme . '://' . $request_host;
	}

	return 'https://' . $tailnet_host;
}
