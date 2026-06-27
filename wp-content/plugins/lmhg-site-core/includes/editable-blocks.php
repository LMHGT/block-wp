<?php
/**
 * Editable Gutenberg block imports for cloud-run migration slices.
 *
 * @package LMHGSiteCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns true when a page owns imported editable block content.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function lmhg_site_core_has_editable_block_content( int $post_id ): bool {
	return '1' === (string) get_post_meta( $post_id, '_lmhg_editable_blocks_imported', true );
}

/**
 * Imports route-level editable Gutenberg block content.
 *
 * @param array<string,mixed> $manifest Block migration manifest.
 * @return array<string,int>
 */
function lmhg_site_core_import_block_manifest( array $manifest ): array {
	$routes = isset( $manifest['routes'] ) && is_array( $manifest['routes'] )
		? $manifest['routes']
		: array();

	$result = array(
		'updated' => 0,
		'missing' => 0,
		'skipped' => 0,
		'failed'  => 0,
		'blocks'  => 0,
		'assets'  => 0,
	);

	$assets = lmhg_site_core_editable_media_assets_by_route( $manifest );

	foreach ( $routes as $route ) {
		if ( ! is_array( $route ) ) {
			++$result['skipped'];
			continue;
		}

		$raw_url = trim( (string) ( $route['url'] ?? '' ) );
		if ( '' === $raw_url ) {
			++$result['skipped'];
			continue;
		}
		$url = lmhg_site_core_normalize_manifest_url( $raw_url );

		$content = (string) ( $route['postContent'] ?? '' );
		if ( '' === trim( $content ) || ! str_contains( $content, '<!-- wp:' ) ) {
			++$result['failed'];
			continue;
		}

		$page = lmhg_site_core_find_existing_route_page( $url, implode( '/', lmhg_site_core_url_segments( $url ) ) );
		if ( ! $page instanceof WP_Post ) {
			++$result['missing'];
			continue;
		}

		$post_data = array(
			'ID'           => $page->ID,
			'post_content' => $content,
		);

		$h1 = trim( (string) ( $route['h1'] ?? '' ) );
		if ( '' !== $h1 ) {
			$post_data['post_title'] = $h1;
		}

		$updated = wp_update_post( wp_slash( $post_data ), true );
		if ( is_wp_error( $updated ) ) {
			++$result['failed'];
			continue;
		}

		lmhg_site_core_update_editable_block_meta( (int) $updated, $manifest, $route, $assets[ $url ] ?? array() );
		++$result['updated'];
		$result['blocks'] += is_array( $route['blocks'] ?? null ) ? count( $route['blocks'] ) : 0;
		$result['assets'] += count( $assets[ $url ] ?? array() );
	}

	return $result;
}

/**
 * Stores block migration metadata for audit and future editor tooling.
 *
 * @param int                 $post_id Post ID.
 * @param array<string,mixed> $manifest Full block manifest.
 * @param array<string,mixed> $route Route entry.
 * @param array<int,array<string,mixed>> $assets Media assets used by the route.
 */
function lmhg_site_core_update_editable_block_meta( int $post_id, array $manifest, array $route, array $assets ): void {
	$schema = (string) ( $manifest['schemaVersion'] ?? '' );
	$blocks = isset( $route['blocks'] ) && is_array( $route['blocks'] ) ? $route['blocks'] : array();

	$meta = array(
		'_lmhg_editable_blocks_imported'       => '1',
		'_lmhg_editable_blocks_imported_at'    => current_time( 'mysql', true ),
		'_lmhg_editable_blocks_schema'         => $schema,
		'_lmhg_editable_blocks_source_mode'    => (string) ( $route['sourceMode'] ?? '' ),
		'_lmhg_editable_blocks_visible_hash'   => (string) ( $route['visibleTextHash'] ?? '' ),
		'_lmhg_editable_blocks_source_hash'    => (string) ( $route['sourceRouteTextHash'] ?? '' ),
		'_lmhg_editable_blocks_count'          => (string) count( $blocks ),
		'_lmhg_editable_assets_count'          => (string) count( $assets ),
		'_lmhg_editable_blocks_manifest_entry' => wp_json_encode( $route ),
		'_lmhg_editable_blocks'                => wp_json_encode( $blocks ),
		'_lmhg_editable_media_assets'          => wp_json_encode( $assets ),
	);

	foreach ( $meta as $key => $value ) {
		if ( in_array( $key, lmhg_site_core_json_meta_keys(), true ) ) {
			update_post_meta( $post_id, $key, wp_slash( $value ) );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	}
}

/**
 * Groups media-manifest assets by source route.
 *
 * @param array<string,mixed> $manifest Block migration manifest.
 * @return array<string,array<int,array<string,mixed>>>
 */
function lmhg_site_core_editable_media_assets_by_route( array $manifest ): array {
	$assets = isset( $manifest['mediaAssets'] ) && is_array( $manifest['mediaAssets'] )
		? $manifest['mediaAssets']
		: array();
	$by_route = array();

	foreach ( $assets as $asset ) {
		if ( ! is_array( $asset ) || ! isset( $asset['routeUsage'] ) || ! is_array( $asset['routeUsage'] ) ) {
			continue;
		}

		foreach ( $asset['routeUsage'] as $usage ) {
			if ( ! is_array( $usage ) ) {
				continue;
			}

			$raw_route = trim( (string) ( $usage['route'] ?? '' ) );
			if ( '' === $raw_route ) {
				continue;
			}
			$route = lmhg_site_core_normalize_manifest_url( $raw_route );

			$by_route[ $route ][] = $asset;
		}
	}

	return $by_route;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Imports editable Gutenberg block content via WP-CLI.
	 *
	 * @param string[] $args Command args.
	 */
	function lmhg_site_core_cli_import_block_manifest( array $args ): void {
		$file = $args[0] ?? '';
		if ( '' === $file || ! file_exists( $file ) ) {
			WP_CLI::error( 'Usage: wp lmhg import-block-manifest <block-manifest.json> [media-manifest.json]' );
		}

		$manifest = json_decode( (string) file_get_contents( $file ), true );
		if ( ! is_array( $manifest ) ) {
			WP_CLI::error( 'Block manifest is not valid JSON.' );
		}

		$media_file = $args[1] ?? '';
		if ( '' !== $media_file ) {
			if ( ! file_exists( $media_file ) ) {
				WP_CLI::error( 'Media manifest file does not exist.' );
			}

			$media_manifest = json_decode( (string) file_get_contents( $media_file ), true );
			if ( ! is_array( $media_manifest ) ) {
				WP_CLI::error( 'Media manifest is not valid JSON.' );
			}

			$manifest['mediaAssets'] = $media_manifest['assets'] ?? array();
		}

		$result = lmhg_site_core_import_block_manifest( $manifest );
		WP_CLI::success( wp_json_encode( $result ) );
	}

	WP_CLI::add_command( 'lmhg import-block-manifest', 'lmhg_site_core_cli_import_block_manifest' );
}
