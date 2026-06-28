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
		'updated'        => 0,
		'missing'        => 0,
		'skipped'        => 0,
		'failed'         => 0,
		'blocks'         => 0,
		'assets'         => 0,
		'mediaImported'  => 0,
		'mediaExisting'  => 0,
		'mediaSkipped'   => 0,
		'mediaFailed'    => 0,
	);

	$media_imports = lmhg_site_core_import_editable_media_assets( $manifest );
	$assets = lmhg_site_core_editable_media_assets_by_route( $manifest, $media_imports );
	foreach ( $media_imports as $import ) {
		$status = (string) ( $import['status'] ?? '' );
		if ( 'imported' === $status ) {
			++$result['mediaImported'];
		} elseif ( 'existing' === $status ) {
			++$result['mediaExisting'];
		} elseif ( 'skipped' === $status ) {
			++$result['mediaSkipped'];
		} elseif ( 'failed' === $status ) {
			++$result['mediaFailed'];
		}
	}

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
		$content = lmhg_site_core_rewrite_editable_media_urls( $content, $media_imports );

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

	$title = trim( (string) ( $route['title'] ?? '' ) );
	if ( '' !== $title ) {
		$meta['_lmhg_seo_title'] = $title;
	}

	$description = trim( (string) ( $route['metaDescription'] ?? '' ) );
	if ( '' !== $description ) {
		$meta['_lmhg_meta_description'] = $description;
	}

	$h1 = trim( (string) ( $route['h1'] ?? '' ) );
	if ( '' !== $h1 ) {
		$meta['_lmhg_h1'] = $h1;
	}

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
 * @param array<string,array<string,mixed>> $media_imports Imported media details by asset ID.
 * @return array<string,array<int,array<string,mixed>>>
 */
function lmhg_site_core_editable_media_assets_by_route( array $manifest, array $media_imports ): array {
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

			$asset_id = (string) ( $asset['assetId'] ?? '' );
			if ( '' !== $asset_id && isset( $media_imports[ $asset_id ] ) ) {
				$asset['wordpress'] = $media_imports[ $asset_id ];
			}

			$by_route[ $route ][] = $asset;
		}
	}

	return $by_route;
}

/**
 * Imports editor-owned media assets and returns WordPress attachment mappings.
 *
 * @param array<string,mixed> $manifest Block migration manifest.
 * @return array<string,array<string,mixed>>
 */
function lmhg_site_core_import_editable_media_assets( array $manifest ): array {
	$assets = isset( $manifest['mediaAssets'] ) && is_array( $manifest['mediaAssets'] )
		? $manifest['mediaAssets']
		: array();
	$imports = array();

	foreach ( $assets as $asset ) {
		if ( ! is_array( $asset ) ) {
			continue;
		}

		$asset_id = (string) ( $asset['assetId'] ?? '' );
		if ( '' === $asset_id ) {
			continue;
		}

		$kind = (string) ( $asset['kind'] ?? '' );
		$source_url = esc_url_raw( (string) ( $asset['sourceUrl'] ?? '' ) );
		if ( 'image' !== $kind || '' === $source_url ) {
			$imports[ $asset_id ] = array(
				'status' => 'skipped',
				'kind'   => $kind,
			);
			continue;
		}

		if ( str_ends_with( strtolower( (string) wp_parse_url( $source_url, PHP_URL_PATH ) ), '.svg' ) ) {
			$static_url = lmhg_site_core_static_imported_asset_url( $asset );
			$imports[ $asset_id ] = array(
				'status'    => '' !== $static_url ? 'static' : 'failed',
				'url'       => $static_url,
				'sourceUrl' => $source_url,
				'kind'      => $kind,
			);
			continue;
		}

		$existing_id = lmhg_site_core_find_existing_media_asset( $source_url );
		if ( $existing_id > 0 ) {
			$imports[ $asset_id ] = array(
				'status'       => 'existing',
				'attachmentId' => $existing_id,
				'url'          => wp_get_attachment_url( $existing_id ),
				'sourceUrl'    => $source_url,
			);
			continue;
		}

		$attachment_id = lmhg_site_core_sideload_editable_media_asset( $asset, $source_url );
		if ( is_wp_error( $attachment_id ) ) {
			$imports[ $asset_id ] = array(
				'status'    => 'failed',
				'sourceUrl' => $source_url,
				'error'     => $attachment_id->get_error_message(),
			);
			continue;
		}

		$imports[ $asset_id ] = array(
			'status'       => 'imported',
			'attachmentId' => $attachment_id,
			'url'          => wp_get_attachment_url( $attachment_id ),
			'sourceUrl'    => $source_url,
		);
	}

	return $imports;
}

/**
 * Finds an existing media attachment for a source asset URL.
 *
 * @param string $source_url Source asset URL.
 * @return int
 */
function lmhg_site_core_find_existing_media_asset( string $source_url ): int {
	$attachments = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'meta_key'       => '_lmhg_source_asset_url',
			'meta_value'     => $source_url,
			'fields'         => 'ids',
			'posts_per_page' => 1,
		)
	);

	return ! empty( $attachments ) ? (int) $attachments[0] : 0;
}

/**
 * Sideloads one media asset into the WordPress media library.
 *
 * @param array<string,mixed> $asset Asset manifest entry.
 * @param string              $source_url Source asset URL.
 * @return int|WP_Error
 */
function lmhg_site_core_sideload_editable_media_asset( array $asset, string $source_url ): int|WP_Error {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$upload_dir = wp_upload_dir();
	if ( ! empty( $upload_dir['error'] ) ) {
		return new WP_Error( 'lmhg_upload_dir_unavailable', (string) $upload_dir['error'] );
	}
	if ( ! wp_mkdir_p( (string) $upload_dir['path'] ) ) {
		return new WP_Error( 'lmhg_upload_dir_not_writable', 'Unable to create the WordPress upload directory.' );
	}

	$alt = sanitize_text_field( (string) ( $asset['alt'] ?? '' ) );
	$attachment_id = media_sideload_image( $source_url, 0, $alt, 'id' );
	if ( is_wp_error( $attachment_id ) ) {
		return $attachment_id;
	}

	$attachment_id = (int) $attachment_id;
	update_post_meta( $attachment_id, '_lmhg_source_asset_url', $source_url );
	update_post_meta( $attachment_id, '_lmhg_source_asset_id', (string) ( $asset['assetId'] ?? '' ) );
	update_post_meta( $attachment_id, '_lmhg_source_asset_hash', (string) ( $asset['sourceHash'] ?? '' ) );
	if ( '' !== $alt ) {
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
	}

	return $attachment_id;
}

/**
 * Rewrites staging media URLs to imported WordPress attachment URLs.
 *
 * @param string $content Serialized block content.
 * @param array<string,array<string,mixed>> $media_imports Imported media details by asset ID.
 * @return string
 */
function lmhg_site_core_rewrite_editable_media_urls( string $content, array $media_imports ): string {
	foreach ( $media_imports as $import ) {
		$source_url = (string) ( $import['sourceUrl'] ?? '' );
		$target_url = (string) ( $import['url'] ?? '' );
		if ( '' === $source_url || '' === $target_url ) {
			continue;
		}

		$content = str_replace( $source_url, $target_url, $content );
		$content = str_replace( esc_url( $source_url ), esc_url( $target_url ), $content );
	}

	return $content;
}

/**
 * Gets a plugin-served URL for packaged imported assets that should not be sideloaded.
 *
 * @param array<string,mixed> $asset Asset manifest entry.
 * @return string
 */
function lmhg_site_core_static_imported_asset_url( array $asset ): string {
	$artifact_path = (string) ( $asset['artifactPath'] ?? '' );
	$file_name = sanitize_file_name( basename( $artifact_path ) );
	if ( '' === $file_name ) {
		return '';
	}

	$file_path = dirname( __DIR__ ) . '/assets/imported/' . $file_name;
	if ( ! file_exists( $file_path ) ) {
		return '';
	}

	return plugins_url( 'assets/imported/' . $file_name, dirname( __DIR__ ) . '/lmhg-site-core.php' );
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
