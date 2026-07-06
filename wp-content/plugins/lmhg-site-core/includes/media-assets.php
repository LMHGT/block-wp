<?php
/**
 * Durable media-library assets for LMHG runtime visuals.
 *
 * @package LMHGSiteCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const LMHG_SITE_CORE_MEDIA_ASSET_ROLE_META         = '_lmhg_asset_role';
const LMHG_SITE_CORE_MEDIA_ASSET_SOURCE_PATH_META  = '_lmhg_asset_source_path';
const LMHG_SITE_CORE_MEDIA_ASSET_SEED_OPTION       = 'lmhg_media_asset_seed_version';
const LMHG_SITE_CORE_MEDIA_ASSET_SEED_VERSION      = '2026-07-05-media-library-assets-v1';

add_action( 'init', 'lmhg_site_core_register_media_asset_meta', 11 );
add_action( 'init', 'lmhg_site_core_seed_media_library_assets', 40 );
add_action( 'wp_head', 'lmhg_site_core_print_media_asset_css', 20 );

/**
 * Registers attachment metadata used to rebuild LMHG visual roles after import.
 */
function lmhg_site_core_register_media_asset_meta(): void {
	register_post_meta(
		'attachment',
		LMHG_SITE_CORE_MEDIA_ASSET_ROLE_META,
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_key',
			'auth_callback'     => 'lmhg_site_core_media_asset_meta_auth_callback',
		)
	);

	register_post_meta(
		'attachment',
		LMHG_SITE_CORE_MEDIA_ASSET_SOURCE_PATH_META,
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => 'lmhg_site_core_media_asset_meta_auth_callback',
		)
	);
}

/**
 * Authorizes media asset metadata edits.
 *
 * @param mixed  $allowed Existing permission value.
 * @param string $meta_key Meta key.
 * @param int    $object_id Attachment ID.
 * @return bool
 */
function lmhg_site_core_media_asset_meta_auth_callback( mixed $allowed = false, string $meta_key = '', int $object_id = 0 ): bool {
	unset( $allowed, $meta_key );
	return $object_id > 0 ? current_user_can( 'edit_post', $object_id ) : current_user_can( 'upload_files' );
}

/**
 * Returns broad service icon definitions keyed by page slug.
 *
 * @return array<string,array{file:string,alt:string,title:string}>
 */
function lmhg_site_core_service_icon_definitions(): array {
	return array(
		'individual-counseling'       => array(
			'file'  => 'individual-counseling-card-icon-transparent.webp',
			'alt'   => 'Individual Counseling icon',
			'title' => 'Individual Counseling service icon',
		),
		'child-counseling'            => array(
			'file'  => 'child-counseling-card-icon-transparent.webp',
			'alt'   => 'Child Therapy icon',
			'title' => 'Child Counseling service icon',
		),
		'family-therapy'              => array(
			'file'  => 'family-therapy-card-icon-transparent.webp',
			'alt'   => 'Family Therapy icon',
			'title' => 'Family Therapy service icon',
		),
		'couples-counseling'          => array(
			'file'  => 'couples-counseling-card-icon-transparent.webp',
			'alt'   => 'Couples Counseling icon',
			'title' => 'Couples Counseling service icon',
		),
		'court-ordered'               => array(
			'file'  => 'court-ordered-card-icon-transparent.webp',
			'alt'   => 'Court Ordered Services icon',
			'title' => 'Court-Ordered Services service icon',
		),
		'community-based-services'    => array(
			'file'  => 'community-based-services-card-icon-transparent.webp',
			'alt'   => 'Community-Based Services icon',
			'title' => 'Community-Based Services service icon',
		),
		'group-therapy'               => array(
			'file'  => 'group-therapy-card-icon-transparent.webp',
			'alt'   => 'Group Therapy icon',
			'title' => 'Group Therapy service icon',
		),
		'trauma-therapy'              => array(
			'file'  => 'trauma-therapy-card-icon-transparent.webp',
			'alt'   => 'Trauma Therapy icon',
			'title' => 'Trauma Therapy service icon',
		),
	);
}

/**
 * Returns specialty icon definitions keyed by page slug.
 *
 * @return array<string,array{file:string,alt:string,title:string}>
 */
function lmhg_site_core_specialty_icon_definitions(): array {
	return array(
		'adult-counseling'              => array(
			'file'  => 'adult-counseling-card-icon-transparent.webp',
			'alt'   => 'Adult Counseling icon',
			'title' => 'Adult Counseling specialty icon',
		),
		'adolescent-counseling'         => array(
			'file'  => 'adolescent-counseling-card-icon-transparent.webp',
			'alt'   => 'Teen Therapy icon',
			'title' => 'Teen Therapy specialty icon',
		),
		'anxiety-depression-therapy'    => array(
			'file'  => 'anxiety-depression-therapy-card-icon-transparent.webp',
			'alt'   => 'Anxiety and Depression Therapy icon',
			'title' => 'Anxiety and Depression Therapy specialty icon',
		),
		'attachment-therapy'            => array(
			'file'  => 'attachment-therapy-card-icon-transparent.webp',
			'alt'   => 'Attachment Therapy icon',
			'title' => 'Attachment Therapy specialty icon',
		),
		'case-management'               => array(
			'file'  => 'case-management-card-icon-transparent.webp',
			'alt'   => 'Case Management icon',
			'title' => 'Case Management specialty icon',
		),
		'child-behavioral-intervention' => array(
			'file'  => 'child-behavioral-intervention-card-icon-transparent.webp',
			'alt'   => 'Child Behavioral Therapy icon',
			'title' => 'Child Behavioral Therapy specialty icon',
		),
		'co-parenting'                  => array(
			'file'  => 'co-parenting-card-icon-transparent.webp',
			'alt'   => 'Co-Parenting icon',
			'title' => 'Co-Parenting specialty icon',
		),
		'community-support'             => array(
			'file'  => 'community-support-card-icon-transparent.webp',
			'alt'   => 'Community Support Services icon',
			'title' => 'Community Support Services specialty icon',
		),
		'couples-conflict-resolution'   => array(
			'file'  => 'couples-conflict-resolution-card-icon-transparent.webp',
			'alt'   => 'Couples Conflict Resolution icon',
			'title' => 'Couples Conflict Resolution specialty icon',
		),
		'emdr-therapy'                  => array(
			'file'  => 'emdr-therapy-card-icon-transparent.webp',
			'alt'   => 'EMDR Therapy icon',
			'title' => 'EMDR Therapy specialty icon',
		),
		'family-reunification'          => array(
			'file'  => 'family-reunification-card-icon-transparent.webp',
			'alt'   => 'Family Reunification icon',
			'title' => 'Family Reunification specialty icon',
		),
		'parenting-support'             => array(
			'file'  => 'parenting-support-card-icon-transparent.webp',
			'alt'   => 'Parenting Support icon',
			'title' => 'Parenting Support specialty icon',
		),
		'play-therapy'                  => array(
			'file'  => 'play-therapy-card-icon-transparent.webp',
			'alt'   => 'Play Therapy icon',
			'title' => 'Play Therapy specialty icon',
		),
		'relationship-counseling'       => array(
			'file'  => 'relationship-counseling-card-icon-transparent.webp',
			'alt'   => 'Relationship Counseling icon',
			'title' => 'Relationship Counseling specialty icon',
		),
	);
}

/**
 * Returns all durable media assets keyed by LMHG role.
 *
 * @return array<string,array<string,mixed>>
 */
function lmhg_site_core_media_asset_registry(): array {
	$assets = array(
		'logo-watermark' => array(
			'role'         => 'logo-watermark',
			'type'         => 'watermark',
			'relativePath' => '2026/07/lmhg-logo-watermark.png',
			'file'         => 'lmhg-logo-watermark.png',
			'alt'          => 'Louisville Mental Health Group logo watermark',
			'title'        => 'LMHG logo watermark',
			'themeFile'    => 'assets/lmhg-logo-watermark.png',
		),
	);

	foreach ( lmhg_site_core_service_icon_definitions() as $slug => $definition ) {
		$role = 'service-icon-' . $slug;
		$assets[ $role ] = array(
			'role'         => $role,
			'type'         => 'service-icon',
			'slug'         => $slug,
			'termSlug'     => $slug,
			'termName'     => str_replace( ' service icon', '', (string) $definition['title'] ),
			'relativePath' => '2026/06/' . $definition['file'],
			'file'         => $definition['file'],
			'alt'          => $definition['alt'],
			'title'        => $definition['title'],
			'pluginFile'   => 'assets/specialty-icons/' . $definition['file'],
		);
	}

	foreach ( lmhg_site_core_specialty_icon_definitions() as $slug => $definition ) {
		$role = 'specialty-icon-' . $slug;
		$assets[ $role ] = array(
			'role'         => $role,
			'type'         => 'specialty-icon',
			'slug'         => $slug,
			'termSlug'     => $slug,
			'termName'     => str_replace( ' specialty icon', '', (string) $definition['title'] ),
			'relativePath' => '2026/07/' . $definition['file'],
			'file'         => $definition['file'],
			'alt'          => $definition['alt'],
			'title'        => $definition['title'],
			'pluginFile'   => 'assets/specialty-icons/' . $definition['file'],
		);
	}

	return $assets;
}

/**
 * Gets an icon asset definition for a service or specialty slug.
 *
 * @param string $type Asset type, either service-icon or specialty-icon.
 * @param string $slug Page or term slug.
 * @return array<string,mixed>
 */
function lmhg_site_core_icon_asset_definition( string $type, string $slug ): array {
	$type = sanitize_key( $type );
	$slug = sanitize_title( $slug );
	$role = $type . '-' . $slug;
	$registry = lmhg_site_core_media_asset_registry();

	return isset( $registry[ $role ] ) ? $registry[ $role ] : array();
}

/**
 * Gets a media asset definition by durable role.
 *
 * @param string $role Durable asset role.
 * @return array<string,mixed>
 */
function lmhg_site_core_media_asset_definition_for_role( string $role ): array {
	$role = sanitize_key( $role );
	$registry = lmhg_site_core_media_asset_registry();

	return isset( $registry[ $role ] ) ? $registry[ $role ] : array();
}

/**
 * Finds the first media-library attachment assigned to a durable LMHG asset role.
 *
 * @param string $role Asset role, for example "logo-watermark".
 * @return int
 */
function lmhg_site_core_media_asset_id( string $role ): int {
	$role = sanitize_key( $role );
	if ( '' === $role ) {
		return 0;
	}

	$attachments = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'meta_key'       => LMHG_SITE_CORE_MEDIA_ASSET_ROLE_META,
			'meta_value'     => $role,
			'fields'         => 'ids',
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'posts_per_page' => 1,
		)
	);

	return ! empty( $attachments ) ? (int) $attachments[0] : 0;
}

/**
 * Gets the public media-library URL for a durable LMHG asset role.
 *
 * @param string $role Asset role.
 * @return string
 */
function lmhg_site_core_media_asset_url( string $role ): string {
	$attachment_id = lmhg_site_core_media_asset_id( $role );
	if ( $attachment_id <= 0 ) {
		return '';
	}

	$url = wp_get_attachment_url( $attachment_id );
	return is_string( $url ) ? $url : '';
}

/**
 * Gets the best public URL for an LMHG asset role, with upload and archive fallback.
 *
 * @param string $role Durable asset role.
 * @return string
 */
function lmhg_site_core_media_asset_role_url( string $role ): string {
	$role = sanitize_key( $role );
	if ( '' === $role ) {
		return '';
	}

	$media_url = lmhg_site_core_media_asset_url( $role );
	if ( '' !== $media_url ) {
		return $media_url;
	}

	$asset = lmhg_site_core_media_asset_definition_for_role( $role );
	if ( empty( $asset ) ) {
		return '';
	}

	$relative_path = lmhg_site_core_media_asset_relative_path( $asset );
	$upload_path = lmhg_site_core_media_asset_upload_path( $relative_path );
	if ( '' !== $upload_path && file_exists( $upload_path ) ) {
		return lmhg_site_core_media_asset_upload_url( $relative_path );
	}

	foreach ( lmhg_site_core_media_asset_source_candidates( $asset ) as $candidate ) {
		$path = (string) ( $candidate['path'] ?? '' );
		$url  = (string) ( $candidate['url'] ?? '' );
		if ( '' !== $path && '' !== $url && file_exists( $path ) ) {
			return $url;
		}
	}

	return '';
}

/**
 * Seeds all known LMHG runtime visuals as Media Library attachments.
 *
 * @param bool $force Force a rescan even when the seed version is current.
 * @return array<string,int|bool>
 */
function lmhg_site_core_seed_media_library_assets( bool $force = false ): array {
	$result = array(
		'created'    => 0,
		'updated'    => 0,
		'failed'     => 0,
		'termLinked' => 0,
		'complete'   => false,
	);

	if (
		! $force
		&& LMHG_SITE_CORE_MEDIA_ASSET_SEED_VERSION === (string) get_option( LMHG_SITE_CORE_MEDIA_ASSET_SEED_OPTION, '' )
		&& lmhg_site_core_media_asset_seed_complete()
	) {
		$result['complete'] = true;
		return $result;
	}

	foreach ( lmhg_site_core_media_asset_registry() as $asset ) {
		$existing_id = lmhg_site_core_media_asset_id( (string) $asset['role'] );
		$attachment_id = lmhg_site_core_ensure_media_asset_attachment( $asset );
		if ( is_wp_error( $attachment_id ) ) {
			++$result['failed'];
			continue;
		}

		if ( $existing_id > 0 ) {
			++$result['updated'];
		} else {
			++$result['created'];
		}

		if ( lmhg_site_core_sync_media_asset_term_meta( $asset, (int) $attachment_id ) ) {
			++$result['termLinked'];
		}
	}

	$result['complete'] = 0 === $result['failed'] && lmhg_site_core_media_asset_seed_complete();
	if ( $result['complete'] ) {
		update_option( LMHG_SITE_CORE_MEDIA_ASSET_SEED_OPTION, LMHG_SITE_CORE_MEDIA_ASSET_SEED_VERSION, false );
	}

	return $result;
}

/**
 * Checks whether every registered asset has a role attachment and expected term link.
 *
 * @return bool
 */
function lmhg_site_core_media_asset_seed_complete(): bool {
	foreach ( lmhg_site_core_media_asset_registry() as $asset ) {
		$attachment_id = lmhg_site_core_media_asset_id( (string) $asset['role'] );
		if ( $attachment_id <= 0 ) {
			return false;
		}

		if ( isset( $asset['termSlug'] ) && ! lmhg_site_core_media_asset_term_link_complete( $asset, $attachment_id ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Ensures one role attachment exists and points at the persistent uploads path.
 *
 * @param array<string,mixed> $asset Asset definition.
 * @return int|WP_Error
 */
function lmhg_site_core_ensure_media_asset_attachment( array $asset ): int|WP_Error {
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$role = sanitize_key( (string) ( $asset['role'] ?? '' ) );
	$relative_path = lmhg_site_core_media_asset_relative_path( $asset );
	if ( '' === $role || '' === $relative_path ) {
		return new WP_Error( 'lmhg_media_asset_invalid', 'Media asset registry entry is missing a role or path.' );
	}

	$file_path = lmhg_site_core_ensure_media_asset_file( $asset );
	if ( is_wp_error( $file_path ) ) {
		return $file_path;
	}

	$attachment_id = lmhg_site_core_media_asset_id( $role );
	if ( $attachment_id <= 0 ) {
		$attachment_id = lmhg_site_core_find_attachment_by_relative_path( $relative_path );
	}

	$file_type = wp_check_filetype( basename( $file_path ), null );
	$mime_type = (string) ( $file_type['type'] ?? '' );
	if ( '' === $mime_type ) {
		return new WP_Error( 'lmhg_media_asset_unknown_type', 'Unable to determine media asset MIME type.' );
	}

	if ( $attachment_id <= 0 ) {
		$attachment_id = wp_insert_attachment(
			wp_slash(
				array(
					'guid'           => lmhg_site_core_media_asset_upload_url( $relative_path ),
					'post_mime_type' => $mime_type,
					'post_title'     => (string) ( $asset['title'] ?? basename( $file_path ) ),
					'post_status'    => 'inherit',
				)
			),
			$file_path,
			0,
			true
		);

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}
	}

	$attachment_id = (int) $attachment_id;
	update_attached_file( $attachment_id, $file_path );
	update_post_meta( $attachment_id, LMHG_SITE_CORE_MEDIA_ASSET_ROLE_META, $role );
	update_post_meta( $attachment_id, LMHG_SITE_CORE_MEDIA_ASSET_SOURCE_PATH_META, $relative_path );

	$alt = sanitize_text_field( (string) ( $asset['alt'] ?? '' ) );
	if ( '' !== $alt ) {
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
	}

	$metadata = wp_get_attachment_metadata( $attachment_id );
	if ( empty( $metadata ) || ! is_array( $metadata ) ) {
		$metadata = wp_generate_attachment_metadata( $attachment_id, $file_path );
		if ( is_array( $metadata ) ) {
			wp_update_attachment_metadata( $attachment_id, $metadata );
		}
	}

	return $attachment_id;
}

/**
 * Ensures an asset file exists at its persistent uploads path.
 *
 * @param array<string,mixed> $asset Asset definition.
 * @return string|WP_Error
 */
function lmhg_site_core_ensure_media_asset_file( array $asset ): string|WP_Error {
	$relative_path = lmhg_site_core_media_asset_relative_path( $asset );
	$upload_path = lmhg_site_core_media_asset_upload_path( $relative_path );
	if ( '' === $upload_path ) {
		return new WP_Error( 'lmhg_media_asset_upload_unavailable', 'WordPress upload directory is unavailable.' );
	}

	if ( file_exists( $upload_path ) ) {
		return $upload_path;
	}

	if ( ! wp_mkdir_p( dirname( $upload_path ) ) ) {
		return new WP_Error( 'lmhg_media_asset_upload_not_writable', 'Unable to create the asset upload directory.' );
	}

	foreach ( lmhg_site_core_media_asset_source_candidates( $asset ) as $candidate ) {
		$source_path = (string) ( $candidate['path'] ?? '' );
		if ( '' !== $source_path && file_exists( $source_path ) && copy( $source_path, $upload_path ) ) {
			return $upload_path;
		}
	}

	return new WP_Error( 'lmhg_media_asset_source_missing', 'No packaged source file exists for the media asset.' );
}

/**
 * Finds an attachment already pointed at a relative uploads path.
 *
 * @param string $relative_path Relative path such as 2026/07/file.webp.
 * @return int
 */
function lmhg_site_core_find_attachment_by_relative_path( string $relative_path ): int {
	$attachments = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'meta_key'       => '_wp_attached_file',
			'meta_value'     => $relative_path,
			'fields'         => 'ids',
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'posts_per_page' => 1,
		)
	);

	return ! empty( $attachments ) ? (int) $attachments[0] : 0;
}

/**
 * Syncs service/specialty taxonomy icon metadata to the seeded attachment.
 *
 * @param array<string,mixed> $asset Asset definition.
 * @param int                 $attachment_id Attachment ID.
 * @return bool True when the term meta was created or changed.
 */
function lmhg_site_core_sync_media_asset_term_meta( array $asset, int $attachment_id ): bool {
	$term_slug = sanitize_title( (string) ( $asset['termSlug'] ?? '' ) );
	if ( '' === $term_slug || $attachment_id <= 0 ) {
		return false;
	}

	$taxonomy = lmhg_site_core_media_asset_relationship_taxonomy();
	if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
		return false;
	}

	$term_id = lmhg_site_core_ensure_media_asset_term( $asset, $taxonomy );
	if ( $term_id <= 0 ) {
		return false;
	}

	$meta_key = lmhg_site_core_media_asset_term_icon_meta_key();
	if ( '' === $meta_key ) {
		return false;
	}

	$current_id = absint( get_term_meta( $term_id, $meta_key, true ) );
	if ( $current_id === $attachment_id ) {
		return false;
	}

	if ( $current_id > 0 ) {
		$current_role = (string) get_post_meta( $current_id, LMHG_SITE_CORE_MEDIA_ASSET_ROLE_META, true );
		if ( '' !== $current_role && $current_role !== (string) $asset['role'] ) {
			return false;
		}
	}

	update_term_meta( $term_id, $meta_key, $attachment_id );
	return true;
}

/**
 * Checks one asset's taxonomy icon link.
 *
 * @param array<string,mixed> $asset Asset definition.
 * @param int                 $attachment_id Attachment ID.
 * @return bool
 */
function lmhg_site_core_media_asset_term_link_complete( array $asset, int $attachment_id ): bool {
	$taxonomy = lmhg_site_core_media_asset_relationship_taxonomy();
	$meta_key = lmhg_site_core_media_asset_term_icon_meta_key();
	$term_slug = sanitize_title( (string) ( $asset['termSlug'] ?? '' ) );
	if ( '' === $taxonomy || '' === $meta_key || '' === $term_slug || ! taxonomy_exists( $taxonomy ) ) {
		return false;
	}

	$term = get_term_by( 'slug', $term_slug, $taxonomy );
	if ( ! $term instanceof WP_Term ) {
		return false;
	}

	return $attachment_id === absint( get_term_meta( (int) $term->term_id, $meta_key, true ) );
}

/**
 * Ensures a service or specialty taxonomy term exists for an icon role.
 *
 * @param array<string,mixed> $asset Asset definition.
 * @param string              $taxonomy Taxonomy name.
 * @return int
 */
function lmhg_site_core_ensure_media_asset_term( array $asset, string $taxonomy ): int {
	$term_slug = sanitize_title( (string) ( $asset['termSlug'] ?? '' ) );
	$term_name = sanitize_text_field( (string) ( $asset['termName'] ?? $asset['title'] ?? $term_slug ) );
	if ( '' === $term_slug || '' === $term_name ) {
		return 0;
	}

	if ( function_exists( 'lmhg_site_core_ensure_related_page_term' ) ) {
		return lmhg_site_core_ensure_related_page_term( $term_slug, $term_name );
	}

	$term = get_term_by( 'slug', $term_slug, $taxonomy );
	if ( $term instanceof WP_Term ) {
		return (int) $term->term_id;
	}

	$created = wp_insert_term( $term_name, $taxonomy, array( 'slug' => $term_slug ) );
	if ( is_wp_error( $created ) ) {
		$existing_id = $created->get_error_data( 'term_exists' );
		return is_numeric( $existing_id ) ? (int) $existing_id : 0;
	}

	return isset( $created['term_id'] ) ? (int) $created['term_id'] : 0;
}

/**
 * Gets the relationship taxonomy used for service/specialty icon term links.
 *
 * @return string
 */
function lmhg_site_core_media_asset_relationship_taxonomy(): string {
	return defined( 'LMHG_SITE_CORE_SPECIALTY_TAXONOMY' ) ? LMHG_SITE_CORE_SPECIALTY_TAXONOMY : 'lmhg_specialty';
}

/**
 * Gets the term meta key used for service/specialty icon attachment IDs.
 *
 * @return string
 */
function lmhg_site_core_media_asset_term_icon_meta_key(): string {
	return defined( 'LMHG_SITE_CORE_SPECIALTY_ICON_ID_META' ) ? LMHG_SITE_CORE_SPECIALTY_ICON_ID_META : '_lmhg_specialty_icon_id';
}

/**
 * Gets the normalized relative uploads path for an asset.
 *
 * @param array<string,mixed> $asset Asset definition.
 * @return string
 */
function lmhg_site_core_media_asset_relative_path( array $asset ): string {
	$relative_path = str_replace( '\\', '/', (string) ( $asset['relativePath'] ?? '' ) );
	$relative_path = ltrim( $relative_path, '/' );

	return false === str_contains( $relative_path, '..' ) ? $relative_path : '';
}

/**
 * Gets the absolute uploads path for a relative asset path.
 *
 * @param string $relative_path Relative uploads path.
 * @return string
 */
function lmhg_site_core_media_asset_upload_path( string $relative_path ): string {
	$relative_path = ltrim( str_replace( '\\', '/', $relative_path ), '/' );
	if ( '' === $relative_path || str_contains( $relative_path, '..' ) ) {
		return '';
	}

	$upload_dir = wp_upload_dir();
	if ( ! empty( $upload_dir['error'] ) || empty( $upload_dir['basedir'] ) ) {
		return '';
	}

	return trailingslashit( (string) $upload_dir['basedir'] ) . $relative_path;
}

/**
 * Gets the public uploads URL for a relative asset path.
 *
 * @param string $relative_path Relative uploads path.
 * @return string
 */
function lmhg_site_core_media_asset_upload_url( string $relative_path ): string {
	$relative_path = ltrim( str_replace( '\\', '/', $relative_path ), '/' );
	if ( '' === $relative_path || str_contains( $relative_path, '..' ) ) {
		return '';
	}

	$upload_dir = wp_upload_dir();
	if ( ! empty( $upload_dir['error'] ) || empty( $upload_dir['baseurl'] ) ) {
		return '';
	}

	$encoded = implode( '/', array_map( 'rawurlencode', explode( '/', $relative_path ) ) );
	return trailingslashit( (string) $upload_dir['baseurl'] ) . $encoded;
}

/**
 * Gets packaged source candidates for an asset.
 *
 * @param array<string,mixed> $asset Asset definition.
 * @return array<int,array{path:string,url:string}>
 */
function lmhg_site_core_media_asset_source_candidates( array $asset ): array {
	$candidates = array();

	$plugin_file = ltrim( str_replace( '\\', '/', (string) ( $asset['pluginFile'] ?? '' ) ), '/' );
	if ( '' !== $plugin_file && ! str_contains( $plugin_file, '..' ) ) {
		$candidates[] = array(
			'path' => dirname( __DIR__ ) . '/' . $plugin_file,
			'url'  => plugins_url( $plugin_file, dirname( __DIR__ ) . '/lmhg-site-core.php' ),
		);
	}

	$theme_file = ltrim( str_replace( '\\', '/', (string) ( $asset['themeFile'] ?? '' ) ), '/' );
	if ( '' !== $theme_file && ! str_contains( $theme_file, '..' ) ) {
		$candidates[] = array(
			'path' => dirname( __DIR__, 3 ) . '/themes/wordpress-2026/' . $theme_file,
			'url'  => content_url( 'themes/wordpress-2026/' . $theme_file ),
		);
	}

	return $candidates;
}

/**
 * Switches persistent decorative theme graphics to media-library assets when present.
 */
function lmhg_site_core_print_media_asset_css(): void {
	$watermark_url = lmhg_site_core_media_asset_role_url( 'logo-watermark' );
	if ( '' === $watermark_url ) {
		return;
	}

	printf(
		'<style id="lmhg-site-core-media-assets">:root{--lmhg-logo-watermark-image:url("%1$s");}.wp2026-home-hero::before,.wp2026-page-title::before{background-image:var(--lmhg-logo-watermark-image) !important;}</style>' . "\n",
		esc_url( $watermark_url )
	);
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Seeds durable LMHG media-library assets.
	 */
	function lmhg_site_core_cli_seed_media_assets(): void {
		$result = lmhg_site_core_seed_media_library_assets( true );
		WP_CLI::success( wp_json_encode( $result ) );
	}

	WP_CLI::add_command( 'lmhg seed-media-assets', 'lmhg_site_core_cli_seed_media_assets' );
}
