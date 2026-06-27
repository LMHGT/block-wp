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

add_filter( 'pre_option_home', 'lmhg_site_core_tailnet_url_for_serve' );
add_filter( 'pre_option_siteurl', 'lmhg_site_core_tailnet_url_for_serve' );
add_action( 'wp_head', 'lmhg_site_core_output_meta_description', 5 );
add_action( 'wp_head', 'lmhg_site_core_output_json_ld', 20 );
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

/**
 * Outputs a concise meta description for baseline SEO checks.
 */
function lmhg_site_core_output_meta_description(): void {
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
function lmhg_site_core_output_json_ld(): void {
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
