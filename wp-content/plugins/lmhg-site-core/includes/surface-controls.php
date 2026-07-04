<?php
/**
 * Controls default WordPress public surfaces that are not part of the LMHG page inventory.
 *
 * @package LMHGSiteCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'template_redirect', 'lmhg_site_core_block_default_public_surfaces', 1 );
add_filter( 'wp_sitemaps_post_types', 'lmhg_site_core_filter_sitemap_post_types' );
add_filter( 'wp_sitemaps_posts_query_args', 'lmhg_site_core_filter_sitemap_posts_query_args', 10, 2 );
add_filter( 'wp_sitemaps_taxonomies', 'lmhg_site_core_filter_sitemap_taxonomies' );
add_filter( 'wp_sitemaps_add_provider', 'lmhg_site_core_filter_sitemap_providers', 10, 2 );
add_filter( 'the_content', 'lmhg_site_core_filter_core30_content_copy', 35 );
add_filter( 'the_title', 'lmhg_site_core_filter_core30_title_copy', 20, 2 );
add_filter( 'pre_get_document_title', 'lmhg_site_core_filter_core30_document_title_copy', 20 );

/**
 * Returns a 404 for default posts, archives, and the imported not-found page route.
 */
function lmhg_site_core_block_default_public_surfaces(): void {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	if ( is_singular( 'post' ) || is_author() || is_category() || is_tag() || is_date() || is_page( 'not-found' ) ) {
		lmhg_site_core_render_inventory_404();
	}
}

/**
 * Renders the active theme's 404 template with a 404 status.
 */
function lmhg_site_core_render_inventory_404(): void {
	global $wp_query;

	if ( $wp_query instanceof WP_Query ) {
		$wp_query->set_404();
	}

	status_header( 404 );
	nocache_headers();

	$template = get_404_template();
	if ( '' !== $template ) {
		if ( function_exists( 'lmhg_site_core_should_filter_frontend_html' ) && lmhg_site_core_should_filter_frontend_html() ) {
			ob_start( 'lmhg_site_core_filter_frontend_html' );
		}
		include $template;
		exit;
	}

	echo '<!doctype html><html><head><meta charset="utf-8"><title>Page Not Found</title></head><body><h1>Page Not Found</h1></body></html>';
	exit;
}

/**
 * Removes blog posts from the public WordPress sitemap while keeping page URLs.
 *
 * @param array<string,WP_Post_Type> $post_types Sitemap post types.
 * @return array<string,WP_Post_Type>
 */
function lmhg_site_core_filter_sitemap_post_types( array $post_types ): array {
	unset( $post_types['post'] );
	return $post_types;
}

/**
 * Removes the intentionally routed 404 inventory page from page sitemaps.
 *
 * @param array<string,mixed> $args Sitemap query args.
 * @param string              $post_type Sitemap post type.
 * @return array<string,mixed>
 */
function lmhg_site_core_filter_sitemap_posts_query_args( array $args, string $post_type ): array {
	if ( 'page' !== $post_type ) {
		return $args;
	}

	$not_found_page = get_page_by_path( 'not-found', OBJECT, 'page' );
	if ( ! $not_found_page instanceof WP_Post ) {
		return $args;
	}

	$excluded = array();
	if ( isset( $args['post__not_in'] ) && is_array( $args['post__not_in'] ) ) {
		$excluded = array_map( 'absint', $args['post__not_in'] );
	}

	$excluded[]            = absint( $not_found_page->ID );
	$args['post__not_in'] = array_values( array_unique( array_filter( $excluded ) ) );

	return $args;
}

/**
 * Removes default blog taxonomies from the public WordPress sitemap.
 *
 * @param array<string,WP_Taxonomy> $taxonomies Sitemap taxonomies.
 * @return array<string,WP_Taxonomy>
 */
function lmhg_site_core_filter_sitemap_taxonomies( array $taxonomies ): array {
	unset( $taxonomies['category'], $taxonomies['post_tag'] );
	return $taxonomies;
}

/**
 * Removes author/user sitemaps from this page-inventory proof surface.
 *
 * @param mixed  $provider Sitemap provider.
 * @param string $name Provider name.
 * @return mixed
 */
function lmhg_site_core_filter_sitemap_providers( mixed $provider, string $name ): mixed {
	if ( 'users' === $name ) {
		return false;
	}

	return $provider;
}

/**
 * Corrects Core30 copy mismatches in live rendered page content without mutating page bodies.
 *
 * @param string $content Rendered content.
 * @return string
 */
function lmhg_site_core_filter_core30_content_copy( string $content ): string {
	if ( is_admin() || ! is_singular( 'page' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$post = get_post();
	if ( ! $post instanceof WP_Post ) {
		return $content;
	}

	if ( 'case-management' === $post->post_name ) {
		$content = str_replace(
			array( 'Targeted Case Management', 'Targeted case management', 'targeted case management' ),
			array( 'Case Management', 'Case management', 'case management' ),
			$content
		);
	}

		if ( 'therapy-in-your-home' === $post->post_name ) {
			$content = str_replace(
				'<a href="/specialties/">Specialties</a> &nbsp;/&nbsp; <span>In-Home Therapy in Louisville, KY</span>',
				'<a href="/services/">Services</a> &nbsp;/&nbsp; <a href="/community-based-services/">Community-Based Services</a> &nbsp;/&nbsp; <span>In-Home Therapy in Louisville, KY</span>',
				$content
			);
		}

	return $content;
}

/**
 * Corrects Core30 title mismatches on frontend output.
 *
 * @param string $title Rendered title.
 * @param int    $post_id Post ID.
 * @return string
 */
function lmhg_site_core_filter_core30_title_copy( string $title, int $post_id ): string {
	if ( is_admin() || $post_id <= 0 || 'case-management' !== get_post_field( 'post_name', $post_id ) ) {
		return $title;
	}

	return str_replace(
		array( 'Targeted Case Management', 'Targeted case management', 'targeted case management' ),
		array( 'Case Management', 'Case management', 'case management' ),
		$title
	);
}

/**
 * Corrects Core30 wording in the document title.
 *
 * @param string $title Document title.
 * @return string
 */
function lmhg_site_core_filter_core30_document_title_copy( string $title ): string {
	if ( is_admin() || ! is_singular( 'page' ) ) {
		return $title;
	}

	$post = get_post();
	if ( ! $post instanceof WP_Post || 'case-management' !== $post->post_name ) {
		return $title;
	}

	return str_replace(
		array( 'Targeted Case Management', 'Targeted case management', 'targeted case management' ),
		array( 'Case Management', 'Case management', 'case management' ),
		$title
	);
}
