<?php
/**
 * Manifest importer for the LMHG WordPress proof track.
 *
 * @package LMHGSiteCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Imports route manifest pages into WordPress.
 *
 * @param array<string,mixed> $manifest Source route manifest.
 * @return array<string,int>
 */
function lmhg_site_core_import_manifest( array $manifest ): array {
	$routes = isset( $manifest['routes'] ) && is_array( $manifest['routes'] )
		? $manifest['routes']
		: array();

	$result = array(
		'created'   => 0,
		'updated'   => 0,
		'skipped'   => 0,
		'failed'    => 0,
		'redirects' => 0,
	);

	foreach ( $routes as $route ) {
		if ( ! is_array( $route ) ) {
			++$result['skipped'];
			continue;
		}

		$status = isset( $route['migrationStatus'] ) ? (string) $route['migrationStatus'] : '';
		$url    = isset( $route['url'] ) ? (string) $route['url'] : '';

		if ( 'out-of-scope' === $status || '' === $url || str_starts_with( $url, '/review/' ) ) {
			++$result['skipped'];
			continue;
		}

		$page_id = lmhg_site_core_import_route_page( $route );

		if ( is_wp_error( $page_id ) ) {
			++$result['failed'];
			continue;
		}

		if ( true === (bool) get_post_meta( $page_id, '_lmhg_import_created_this_run', true ) ) {
			delete_post_meta( $page_id, '_lmhg_import_created_this_run' );
			++$result['created'];
		} else {
			++$result['updated'];
		}
	}

	$result['redirects'] = lmhg_site_core_store_redirect_rules( $manifest );

	return $result;
}

/**
 * Stores redirect inventory for front-end redirect handling.
 *
 * @param array<string,mixed> $manifest Source route manifest.
 * @return int
 */
function lmhg_site_core_store_redirect_rules( array $manifest ): int {
	$redirects = isset( $manifest['redirects'] ) && is_array( $manifest['redirects'] )
		? array_values( array_filter( $manifest['redirects'], 'is_array' ) )
		: array();

	update_option( 'lmhg_route_redirects', $redirects, false );

	return count( $redirects );
}

/**
 * Imports one route entry as a WordPress page.
 *
 * @param array<string,mixed> $route Route entry.
 * @return int|WP_Error
 */
function lmhg_site_core_import_route_page( array $route ): int|WP_Error {
	$url      = lmhg_site_core_normalize_manifest_url( (string) ( $route['url'] ?? '' ) );
	$segments = lmhg_site_core_url_segments( $url );
	$title    = lmhg_site_core_page_title_from_route( $route, $segments );

	if ( '/' === $url ) {
		$segments = array( 'home' );
		$title    = 'Home';
	} elseif ( '/404.html' === $url ) {
		$segments = array( 'not-found' );
	}

	if ( empty( $segments ) ) {
		return new WP_Error( 'lmhg_missing_segments', 'Route has no importable path segments.' );
	}

	$parent_id = 0;
	foreach ( array_slice( $segments, 0, -1 ) as $segment ) {
		$parent_id = lmhg_site_core_ensure_page( $segment, lmhg_site_core_title_from_slug( $segment ), $parent_id );
		if ( is_wp_error( $parent_id ) ) {
			return $parent_id;
		}
	}

	$slug = end( $segments );
	$path = implode( '/', $segments );
	$existing = lmhg_site_core_find_existing_route_page( $url, $path );
	$content = lmhg_site_core_stub_content( $route, $url );

	$post_data = array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_parent'  => $parent_id,
		'post_content' => $content,
	);

	if ( $existing instanceof WP_Post ) {
		$post_data['ID'] = $existing->ID;
		$page_id = wp_update_post( wp_slash( $post_data ), true );
	} else {
		$page_id = wp_insert_post( wp_slash( $post_data ), true );
		if ( ! is_wp_error( $page_id ) ) {
			update_post_meta( $page_id, '_lmhg_import_created_this_run', true );
		}
	}

	if ( is_wp_error( $page_id ) ) {
		return $page_id;
	}

	lmhg_site_core_update_route_meta( $page_id, $route, $url );
	lmhg_site_core_assign_route_terms( $page_id, $route );

	if ( '/' === $url ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $page_id );
	}

	return $page_id;
}

/**
 * Finds an existing imported page by durable source identity before path.
 *
 * @param string $url Source URL.
 * @param string $path WordPress page path.
 * @return WP_Post|null
 */
function lmhg_site_core_find_existing_route_page( string $url, string $path ): ?WP_Post {
	$source_matches = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'any',
			'meta_key'       => '_lmhg_source_url',
			'meta_value'     => $url,
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'posts_per_page' => -1,
		)
	);

	if ( ! empty( $source_matches ) ) {
		$primary = array_shift( $source_matches );
		foreach ( $source_matches as $duplicate ) {
			lmhg_site_core_delete_owned_duplicate_page( $duplicate );
		}

		return $primary instanceof WP_Post ? $primary : null;
	}

	$existing = get_page_by_path( $path, OBJECT, 'page' );
	return $existing instanceof WP_Post ? $existing : null;
}

/**
 * Deletes duplicate pages created by this proof importer.
 *
 * @param WP_Post $post Duplicate page.
 */
function lmhg_site_core_delete_owned_duplicate_page( WP_Post $post ): void {
	if ( 'page' !== $post->post_type ) {
		return;
	}

	$manifest_entry = get_post_meta( $post->ID, '_lmhg_route_manifest_entry', true );
	if ( '' === $manifest_entry ) {
		return;
	}

	wp_delete_post( $post->ID, true );
}

/**
 * Ensures a parent page exists.
 *
 * @param string $slug Page slug.
 * @param string $title Page title.
 * @param int    $parent_id Parent page ID.
 * @return int|WP_Error
 */
function lmhg_site_core_ensure_page( string $slug, string $title, int $parent_id = 0 ): int|WP_Error {
	$path = $parent_id > 0 ? get_page_uri( $parent_id ) . '/' . $slug : $slug;
	$existing = get_page_by_path( $path, OBJECT, 'page' );

	if ( $existing instanceof WP_Post ) {
		return $existing->ID;
	}

	return wp_insert_post(
		array(
			'post_type'   => 'page',
			'post_status' => 'publish',
			'post_title'  => $title,
			'post_name'   => $slug,
			'post_parent' => $parent_id,
		),
		true
	);
}

/**
 * Updates route metadata.
 *
 * @param int                 $page_id Page ID.
 * @param array<string,mixed> $route Route entry.
 * @param string              $url Source URL.
 */
function lmhg_site_core_update_route_meta( int $page_id, array $route, string $url ): void {
	$seo = isset( $route['seo'] ) && is_array( $route['seo'] ) ? $route['seo'] : array();

	$meta = array(
		'_lmhg_source_url'             => $url,
		'_lmhg_source_file'            => (string) ( $route['sourceFile'] ?? '' ),
		'_lmhg_implementation_target'  => (string) ( $route['implementationTarget'] ?? '' ),
		'_lmhg_page_family'            => (string) ( $route['pageFamily'] ?? '' ),
		'_lmhg_template_family'        => (string) ( $route['templateFamily'] ?? '' ),
		'_lmhg_faceted_page_type'      => (string) ( $route['facetedPageType'] ?? '' ),
		'_lmhg_migration_status'       => (string) ( $route['migrationStatus'] ?? '' ),
		'_lmhg_seo_title'              => (string) ( $seo['title'] ?? '' ),
		'_lmhg_meta_description'       => (string) ( $seo['description'] ?? '' ),
		'_lmhg_h1'                     => (string) ( $seo['h1'] ?? '' ),
		'_lmhg_primary_keyword'        => (string) ( $seo['primaryKeyword'] ?? '' ),
		'_lmhg_secondary_keywords'     => wp_json_encode( $seo['secondaryKeywords'] ?? array() ),
		'_lmhg_optimization_terms'     => wp_json_encode( $seo['optimizationTerms'] ?? array() ),
		'_lmhg_schema_type'            => (string) ( $seo['schemaType'] ?? '' ),
		'_lmhg_canonical_url'          => (string) ( $seo['canonicalUrl'] ?? $url ),
		'_lmhg_noindex'                => ! empty( $seo['noindex'] ) ? '1' : '0',
		'_lmhg_seo_status'             => (string) ( $seo['status'] ?? '' ),
		'_lmhg_related_pages'          => wp_json_encode( $route['relatedPages'] ?? array() ),
		'_lmhg_faq_items'              => wp_json_encode( $route['faqItems'] ?? array() ),
		'_lmhg_route_manifest_entry'   => wp_json_encode( $route ),
	);

	foreach ( $meta as $key => $value ) {
		if ( in_array( $key, lmhg_site_core_json_meta_keys(), true ) ) {
			update_post_meta( $page_id, $key, wp_slash( $value ) );
		} else {
			update_post_meta( $page_id, $key, $value );
		}
	}
}

/**
 * Lists meta keys whose values are stored as JSON strings.
 *
 * @return string[]
 */
function lmhg_site_core_json_meta_keys(): array {
	return array(
		'_lmhg_secondary_keywords',
		'_lmhg_optimization_terms',
		'_lmhg_related_pages',
		'_lmhg_faq_items',
		'_lmhg_route_manifest_entry',
	);
}

/**
 * Builds temporary migration-stub block content.
 *
 * @param array<string,mixed> $route Route entry.
 * @param string              $url Source URL.
 * @return string
 */
function lmhg_site_core_stub_content( array $route, string $url ): string {
	$title = esc_html( lmhg_site_core_page_title_from_route( $route, lmhg_site_core_url_segments( $url ) ) );
	$family = esc_html( (string) ( $route['pageFamily'] ?? 'page' ) );
	$status = esc_html( (string) ( $route['migrationStatus'] ?? 'needs-copy-model' ) );

	return sprintf(
		'<!-- wp:heading {"level":1} --><h1>%1$s</h1><!-- /wp:heading --><!-- wp:paragraph --><p>Migration stub for %2$s. Source family: %3$s. Status: %4$s.</p><!-- /wp:paragraph -->',
		$title,
		esc_html( $url ),
		$family,
		$status
	);
}

/**
 * Normalizes a manifest URL.
 *
 * @param string $url URL path.
 * @return string
 */
function lmhg_site_core_normalize_manifest_url( string $url ): string {
	if ( '/' === $url ) {
		return '/';
	}

	$url = '/' . trim( $url, '/' );

	if ( str_ends_with( $url, '.html' ) ) {
		return $url;
	}

	return $url . '/';
}

/**
 * Converts a URL into importable path segments.
 *
 * @param string $url URL path.
 * @return string[]
 */
function lmhg_site_core_url_segments( string $url ): array {
	if ( '/' === $url ) {
		return array();
	}

	$url = preg_replace( '/\.html$/', '', $url );
	return array_values( array_filter( explode( '/', trim( (string) $url, '/' ) ) ) );
}

/**
 * Gets a readable page title.
 *
 * @param array<string,mixed> $route Route entry.
 * @param string[]            $segments URL segments.
 * @return string
 */
function lmhg_site_core_page_title_from_route( array $route, array $segments ): string {
	$seo = isset( $route['seo'] ) && is_array( $route['seo'] ) ? $route['seo'] : array();
	$h1  = trim( (string) ( $seo['h1'] ?? '' ) );
	if ( '' !== $h1 ) {
		return $h1;
	}

	$title = trim( (string) ( $route['title'] ?? '' ) );

	if ( '' !== $title && ! str_starts_with( $title, '/' ) ) {
		return $title;
	}

	$slug = end( $segments );
	return $slug ? lmhg_site_core_title_from_slug( (string) $slug ) : 'Home';
}

/**
 * Converts a slug into title case.
 *
 * @param string $slug Slug.
 * @return string
 */
function lmhg_site_core_title_from_slug( string $slug ): string {
	return ucwords( str_replace( '-', ' ', $slug ) );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Imports the LMHG manifest via WP-CLI.
	 *
	 * @param string[] $args Command args.
	 */
	function lmhg_site_core_cli_import_manifest( array $args ): void {
		$file = $args[0] ?? '';
		if ( '' === $file || ! file_exists( $file ) ) {
			WP_CLI::error( 'Usage: wp lmhg import-manifest <manifest.json>' );
		}

		$manifest = json_decode( (string) file_get_contents( $file ), true );
		if ( ! is_array( $manifest ) ) {
			WP_CLI::error( 'Manifest is not valid JSON.' );
		}

		$result = lmhg_site_core_import_manifest( $manifest );
		WP_CLI::success( wp_json_encode( $result ) );
	}

	WP_CLI::add_command( 'lmhg import-manifest', 'lmhg_site_core_cli_import_manifest' );
}
