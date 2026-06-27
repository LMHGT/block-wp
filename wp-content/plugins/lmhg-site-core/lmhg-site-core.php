<?php
/*
Plugin Name: LMHG Site Core
Description: Durable site behavior for the LMHG WordPress proof track.
Version: 0.1.0
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

require_once __DIR__ . '/includes/importer.php';
require_once __DIR__ . '/includes/redirects.php';
require_once __DIR__ . '/includes/seo.php';
require_once __DIR__ . '/includes/rendering.php';
require_once __DIR__ . '/includes/taxonomies.php';

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

	$host = isset( $_SERVER['HTTP_X_FORWARDED_HOST'] )
		? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_HOST'] ) )
		: sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ?? '' ) );

	$host = preg_replace( '/:\d+$/', '', $host );

	if ( $tailnet_host !== $host ) {
		return $value;
	}

	return 'https://' . $tailnet_host;
}
