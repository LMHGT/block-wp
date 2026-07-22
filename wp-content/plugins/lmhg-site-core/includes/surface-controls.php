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
add_filter( 'rank_math/sitemap/entry', 'lmhg_site_core_filter_rank_math_sitemap_entry', 20, 3 );
add_filter( 'the_title', 'lmhg_site_core_filter_core30_title_copy', 20, 2 );
add_filter( 'pre_get_document_title', 'lmhg_site_core_filter_core30_document_title_copy', 20 );
add_filter( 'wp_headers', 'lmhg_site_core_add_public_security_headers', 20 );
add_action( 'send_headers', 'lmhg_site_core_remove_runtime_disclosure_header', 20 );
add_filter( 'rest_endpoints', 'lmhg_site_core_hide_public_user_rest_endpoints' );
add_filter( 'xmlrpc_enabled', '__return_false' );
add_action( 'plugins_loaded', 'lmhg_site_core_disable_xmlrpc_endpoint', 0 );
add_action( 'init', 'lmhg_site_core_remove_xmlrpc_discovery' );
add_action( 'init', 'lmhg_site_core_run_stock_hello_world_cleanup', 47 );
add_action( 'init', 'lmhg_site_core_run_article_permalink_migration', 48 );

const LMHG_SITE_CORE_STOCK_POST_CLEANUP_OPTION  = 'lmhg_stock_hello_world_cleanup_version';
const LMHG_SITE_CORE_STOCK_POST_CLEANUP_VERSION = '2026-07-22-conventional-articles-v1';
const LMHG_SITE_CORE_STOCK_POST_CLEANUP_REPORT  = 'lmhg_stock_hello_world_cleanup_report';
const LMHG_SITE_CORE_ARTICLE_PERMALINK_MIGRATION_OPTION  = 'lmhg_article_permalink_migration_version';
const LMHG_SITE_CORE_ARTICLE_PERMALINK_MIGRATION_VERSION = '2026-07-22-root-article-permalinks-v1';
const LMHG_SITE_CORE_ARTICLE_PERMALINK_MIGRATION_REPORT  = 'lmhg_article_permalink_migration_report';

/** Stops the XML-RPC bootstrap before it can expose introspection or callable methods. */
function lmhg_site_core_disable_xmlrpc_endpoint(): void {
	if ( ! defined( 'XMLRPC_REQUEST' ) || ! XMLRPC_REQUEST ) {
		return;
	}

	status_header( 403 );
	nocache_headers();
	header( 'Content-Type: text/plain; charset=UTF-8' );
	header( 'X-Robots-Tag: noindex, nofollow', true );
	echo 'XML-RPC is disabled.';
	exit;
}

/** Removes the obsolete XML-RPC discovery link from public HTML. */
function lmhg_site_core_remove_xmlrpc_discovery(): void {
	remove_action( 'wp_head', 'rsd_link' );
}

/**
 * Returns a 404 for unsupported archives and the imported not-found page route.
 *
 * Conventional WordPress Posts are the site's public Articles and must remain
 * available as singular requests. Their unsupported archive surfaces stay
 * closed until the site intentionally designs and launches those experiences.
 */
function lmhg_site_core_block_default_public_surfaces(): void {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	if ( is_feed() || is_search() || is_author() || is_category() || is_tag() || is_date() || is_page( 'not-found' ) ) {
		lmhg_site_core_render_inventory_404();
	}
}

/**
 * Identifies only the untouched stock WordPress starter Post.
 *
 * @param WP_Post|int|null $post Candidate Post or ID.
 */
function lmhg_site_core_is_stock_hello_world_post( WP_Post|int|null $post ): bool {
	$post = get_post( $post );
	if ( ! $post instanceof WP_Post ) {
		return false;
	}

	$content        = trim( str_replace( array( "\r\n", "\r" ), "\n", (string) $post->post_content ) );
	$stock_sentence = 'Welcome to WordPress. This is your first post. Edit or delete it, then start writing!';
	$stock_content  = array(
		$stock_sentence,
		"<!-- wp:paragraph -->\n<p>{$stock_sentence}</p>\n<!-- /wp:paragraph -->",
	);
	return 'post' === $post->post_type
		&& 'Hello world!' === $post->post_title
		&& 'hello-world' === $post->post_name
		&& '' === trim( (string) $post->post_excerpt )
		&& in_array( $content, $stock_content, true );
}

/**
 * Drafts the unique, untouched WordPress starter Post for recoverability.
 *
 * Every identifying field must match exactly. Missing, customized, or
 * ambiguous Posts are reported and left intact. The migration never touches
 * future editorial Posts.
 */
function lmhg_site_core_run_stock_hello_world_cleanup(): void {
	if ( LMHG_SITE_CORE_STOCK_POST_CLEANUP_VERSION === (string) get_option( LMHG_SITE_CORE_STOCK_POST_CLEANUP_OPTION, '' ) ) {
		return;
	}

	$candidates = get_posts(
		array(
			'post_type'              => 'post',
			'post_status'            => array( 'publish', 'future', 'draft', 'pending', 'private' ),
			'name'                   => 'hello-world',
			'posts_per_page'         => -1,
			'orderby'                => 'ID',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'suppress_filters'       => true,
		)
	);
	$matches    = array_values( array_filter( $candidates, 'lmhg_site_core_is_stock_hello_world_post' ) );
	$report     = array(
		'version'      => LMHG_SITE_CORE_STOCK_POST_CLEANUP_VERSION,
		'completed_at' => current_time( 'mysql', true ),
		'status'       => 'not_found',
		'post_id'      => 0,
	);
	$complete   = true;

	if ( count( $matches ) > 1 ) {
		$report['status'] = 'ambiguous';
	} elseif ( 1 === count( $matches ) ) {
		$placeholder       = $matches[0];
		$report['post_id'] = (int) $placeholder->ID;
		if ( 'draft' === $placeholder->post_status ) {
			$report['status'] = 'already_draft';
		} else {
			$updated = wp_update_post(
				array(
					'ID'          => (int) $placeholder->ID,
					'post_status' => 'draft',
				),
				true
			);
			if ( is_wp_error( $updated ) ) {
				$report['status'] = 'write_failed';
				$complete         = false;
			} else {
				$report['status'] = 'drafted';
			}
		}
	}

	update_option( LMHG_SITE_CORE_STOCK_POST_CLEANUP_REPORT, $report, false );
	if ( $complete ) {
		update_option( LMHG_SITE_CORE_STOCK_POST_CLEANUP_OPTION, LMHG_SITE_CORE_STOCK_POST_CLEANUP_VERSION, false );
	}
}

/**
 * Moves only the known date-based Post permalink structure to root-level slugs.
 *
 * An already-correct structure is accepted without flushing. Any unexpected
 * structure is reported as a conflict and preserved for owner review.
 */
function lmhg_site_core_run_article_permalink_migration(): void {
	if ( LMHG_SITE_CORE_ARTICLE_PERMALINK_MIGRATION_VERSION === (string) get_option( LMHG_SITE_CORE_ARTICLE_PERMALINK_MIGRATION_OPTION, '' ) ) {
		return;
	}

	$legacy_structure  = '/%year%/%monthnum%/%day%/%postname%/';
	$target_structure  = '/%postname%/';
	$current_structure = (string) get_option( 'permalink_structure', '' );
	$report             = array(
		'version'        => LMHG_SITE_CORE_ARTICLE_PERMALINK_MIGRATION_VERSION,
		'completed_at'   => current_time( 'mysql', true ),
		'status'         => 'conflict',
		'previous'       => $current_structure,
		'current'        => $current_structure,
		'rewrites_flush' => false,
	);
	$complete           = false;

	if ( $target_structure === $current_structure ) {
		$report['status'] = 'already_current';
		$complete         = true;
	} elseif ( $legacy_structure === $current_structure ) {
		$updated = update_option( 'permalink_structure', $target_structure );
		if ( $updated ) {
			flush_rewrite_rules( false );
			$report['status']         = 'updated';
			$report['current']        = $target_structure;
			$report['rewrites_flush'] = true;
			$complete                 = true;
		} elseif ( $target_structure === (string) get_option( 'permalink_structure', '' ) ) {
			$report['status']  = 'concurrently_current';
			$report['current'] = $target_structure;
			$complete          = true;
		} else {
			$report['status'] = 'write_failed';
		}
	}

	update_option( LMHG_SITE_CORE_ARTICLE_PERMALINK_MIGRATION_REPORT, $report, false );
	if ( $complete ) {
		update_option( LMHG_SITE_CORE_ARTICLE_PERMALINK_MIGRATION_OPTION, LMHG_SITE_CORE_ARTICLE_PERMALINK_MIGRATION_VERSION, false );
	}
}

/**
 * Adds conservative public response headers that do not depend on HTTPS.
 *
 * @param array<string,string> $headers Existing response headers.
 * @return array<string,string>
 */
function lmhg_site_core_add_public_security_headers( array $headers ): array {
	if ( is_admin() ) {
		return $headers;
	}

	$headers['X-Content-Type-Options'] = 'nosniff';
	$headers['X-Frame-Options']        = 'SAMEORIGIN';
	$headers['Referrer-Policy']        = 'strict-origin-when-cross-origin';
	$headers['Permissions-Policy']     = 'camera=(), microphone=(), geolocation=()';
	return $headers;
}

/** Removes the PHP version disclosure header from public responses. */
function lmhg_site_core_remove_runtime_disclosure_header(): void {
	if ( ! is_admin() && headers_sent() === false ) {
		header_remove( 'X-Powered-By' );
	}
}

/**
 * Prevents anonymous account enumeration without limiting authenticated editors.
 *
 * @param array<string,array<int,array<string,mixed>>> $endpoints Registered REST routes.
 * @return array<string,array<int,array<string,mixed>>>
 */
function lmhg_site_core_hide_public_user_rest_endpoints( array $endpoints ): array {
	if ( is_user_logged_in() ) {
		return $endpoints;
	}

	foreach ( array_keys( $endpoints ) as $route ) {
		if ( '/wp/v2/users' === $route || str_starts_with( $route, '/wp/v2/users/' ) ) {
			unset( $endpoints[ $route ] );
		}
	}

	return $endpoints;
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
 * Allows only the intentional Page and Article post types in core sitemaps.
 *
 * @param array<string,WP_Post_Type> $post_types Sitemap post types.
 * @return array<string,WP_Post_Type>
 */
function lmhg_site_core_filter_sitemap_post_types( array $post_types ): array {
	return array_intersect_key( $post_types, array_flip( array( 'page', 'post' ) ) );
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
 * Keeps Rank Math sitemaps aligned with LMHG's intentionally public inventory.
 *
 * Rank Math does not use WordPress core's sitemap query filters, so the routed
 * not-found inventory page exclusion must be repeated at its entry boundary.
 * Password-protected and non-published objects are rejected as a final
 * safeguard even if a future Rank Math query starts returning them. Published
 * Posts remain eligible because WordPress Posts are LMHG Articles.
 *
 * @param mixed  $url Sitemap entry generated by Rank Math.
 * @param string $type Rank Math object type.
 * @param mixed  $object Object represented by the sitemap entry.
 * @return mixed
 */
function lmhg_site_core_filter_rank_math_sitemap_entry( mixed $url, string $type, mixed $object ): mixed {
	if ( 'post' !== $type || ! is_object( $object ) ) {
		return $url;
	}

	$post_id = isset( $object->ID ) ? absint( $object->ID ) : 0;
	$post    = $object instanceof WP_Post ? $object : get_post( $post_id );
	if ( $post instanceof WP_Post && ( 'publish' !== $post->post_status || '' !== $post->post_password ) ) {
		return false;
	}

	$post_type = isset( $object->post_type ) ? (string) $object->post_type : '';
	$post_name = isset( $object->post_name ) ? (string) $object->post_name : '';
	if ( 'page' === $post_type && 'not-found' === $post_name ) {
		return false;
	}

	return $url;
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
