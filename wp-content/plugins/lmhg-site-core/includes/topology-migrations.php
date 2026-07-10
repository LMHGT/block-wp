<?php
/**
 * Versioned page, relationship, and hidden-meta migrations for approved topology changes.
 *
 * @package LMHGSiteCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const LMHG_SITE_CORE_TOPOLOGY_MIGRATION_OPTION  = 'lmhg_content_topology_migration_version';
const LMHG_SITE_CORE_TOPOLOGY_MIGRATION_VERSION = '2026-07-10-service-topology-v3';

add_action( 'init', 'lmhg_site_core_run_topology_migration', 27 );

/**
 * Applies the approved service-page renames and in-home consolidation once.
 */
function lmhg_site_core_run_topology_migration(): void {
	if ( LMHG_SITE_CORE_TOPOLOGY_MIGRATION_VERSION === (string) get_option( LMHG_SITE_CORE_TOPOLOGY_MIGRATION_OPTION, '' ) ) {
		return;
	}

	$page_data = lmhg_site_core_topology_page_data();
	if ( empty( $page_data ) ) {
		return;
	}

	$complete = true;
	$complete = lmhg_site_core_sync_topology_page( $page_data, '/attachment-therapy/', '/attachment-therapy/' ) && $complete;
	$complete = lmhg_site_core_sync_topology_page( $page_data, '/adolescent-counseling/', '/adolescent-counseling/' ) && $complete;
	$complete = lmhg_site_core_sync_topology_page( $page_data, '/child-behavioral-intervention/', '/child-behavioral-intervention/' ) && $complete;
	$complete = lmhg_site_core_sync_topology_page( $page_data, '/couples-conflict-resolution/', '/conflict-resolution-counseling/' ) && $complete;
	$complete = lmhg_site_core_sync_topology_page( $page_data, '/couples-counseling/', '/couples-counseling/' ) && $complete;
	$complete = lmhg_site_core_sync_topology_page( $page_data, '/parenting-support/', '/parenting-support/' ) && $complete;
	$complete = lmhg_site_core_sync_topology_page( $page_data, '/locations/in-home/', '/locations/in-home/' ) && $complete;

	$complete = lmhg_site_core_rename_topology_term(
		LMHG_SITE_CORE_SPECIALTY_TAXONOMY,
		'attachment-therapy',
		'attachment-therapy',
		'Parent-Child Attachment Therapy'
	) && $complete;
	$complete = lmhg_site_core_rename_topology_term(
		LMHG_SITE_CORE_SPECIALTY_TAXONOMY,
		'child-behavioral-intervention',
		'child-behavioral-intervention',
		'Child Behavioral Therapy'
	) && $complete;
	$complete = lmhg_site_core_rename_topology_term(
		LMHG_SITE_CORE_SPECIALTY_TAXONOMY,
		'couples-conflict-resolution',
		'conflict-resolution-counseling',
		'Conflict Resolution Counseling'
	) && $complete;
	$complete = lmhg_site_core_rename_topology_term(
		LMHG_SITE_CORE_FAQ_SET_TAXONOMY,
		'attachment-therapy',
		'attachment-therapy',
		'Parent-Child Attachment Therapy'
	) && $complete;
	$complete = lmhg_site_core_rename_topology_term(
		LMHG_SITE_CORE_FAQ_SET_TAXONOMY,
		'couples-conflict-resolution',
		'conflict-resolution-counseling',
		'Conflict Resolution Counseling'
	) && $complete;

	$complete = lmhg_site_core_rename_topology_faq_posts(
		'attachment-therapy',
		'attachment-therapy',
		'Attachment Therapy',
		'Parent-Child Attachment Therapy'
	) && $complete;
	$complete = lmhg_site_core_rename_topology_faq_posts(
		'couples-conflict-resolution',
		'conflict-resolution-counseling',
		'Couples Conflict Resolution',
		'Conflict Resolution Counseling'
	) && $complete;
	$complete = lmhg_site_core_sync_topology_faq_items( $page_data, '/attachment-therapy/', 'attachment-therapy' ) && $complete;
	$complete = lmhg_site_core_sync_topology_faq_items( $page_data, '/adolescent-counseling/', 'adolescent-counseling' ) && $complete;
	$complete = lmhg_site_core_sync_topology_faq_items( $page_data, '/child-behavioral-intervention/', 'child-behavioral-intervention' ) && $complete;
	$complete = lmhg_site_core_sync_topology_faq_items( $page_data, '/conflict-resolution-counseling/', 'conflict-resolution-counseling' ) && $complete;
	$complete = lmhg_site_core_sync_topology_faq_items( $page_data, '/couples-counseling/', 'couples-counseling' ) && $complete;
	$complete = lmhg_site_core_sync_topology_faq_items( $page_data, '/parenting-support/', 'parenting-support' ) && $complete;
	$complete = lmhg_site_core_sync_topology_faq_items( $page_data, '/locations/in-home/', 'locations-in-home' ) && $complete;

	$complete = lmhg_site_core_move_conflict_resolution_relationship() && $complete;
	$complete = lmhg_site_core_move_parenting_support_relationship() && $complete;
	$complete = lmhg_site_core_remove_obsolete_in_home_records() && $complete;
	$complete = lmhg_site_core_replace_topology_references() && $complete;
	$complete = lmhg_site_core_remove_relationship_counseling_records() && $complete;

	if ( $complete ) {
		update_option( LMHG_SITE_CORE_TOPOLOGY_MIGRATION_OPTION, LMHG_SITE_CORE_TOPOLOGY_MIGRATION_VERSION, false );
	}
}

/**
 * Reads the active theme's structured page data, keyed by normalized path.
 *
 * @return array<string,array<string,mixed>>
 */
function lmhg_site_core_topology_page_data(): array {
	$path = get_theme_file_path( 'wp2026-page-data.json' );
	if ( ! is_readable( $path ) ) {
		return array();
	}

	$decoded = json_decode( (string) file_get_contents( $path ), true );
	$pages   = is_array( $decoded ) && isset( $decoded['pages'] ) && is_array( $decoded['pages'] ) ? $decoded['pages'] : array();
	$indexed = array();

	foreach ( $pages as $page ) {
		if ( ! is_array( $page ) ) {
			continue;
		}

		$page_path = lmhg_site_core_normalize_redirect_path( (string) ( $page['path'] ?? '' ) );
		if ( '' !== $page_path ) {
			$indexed[ $page_path ] = $page;
		}
	}

	return $indexed;
}

/**
 * Updates a live page from one page-data entry while keeping the existing post ID.
 *
 * @param array<string,array<string,mixed>> $page_data Page data keyed by path.
 * @param string                            $current_path Current live path.
 * @param string                            $target_path Target page-data path.
 * @return bool
 */
function lmhg_site_core_sync_topology_page( array $page_data, string $current_path, string $target_path ): bool {
	$target_path = lmhg_site_core_normalize_redirect_path( $target_path );
	$entry       = $page_data[ $target_path ] ?? null;
	if ( ! is_array( $entry ) ) {
		return false;
	}

	$current = lmhg_site_core_find_published_topology_page( $current_path );
	$target  = lmhg_site_core_find_published_topology_page( $target_path );
	$page    = $current instanceof WP_Post ? $current : $target;
	if ( ! $page instanceof WP_Post ) {
		return false;
	}

	if ( $current instanceof WP_Post && $target instanceof WP_Post && $current->ID !== $target->ID ) {
		$page = $target;
	}

	$parent_id   = 0;
	$parent_path = trim( (string) ( $entry['parentPath'] ?? '' ), '/' );
	if ( '' !== $parent_path ) {
		$parent = lmhg_site_core_find_published_topology_page( '/' . $parent_path . '/' );
		if ( ! $parent instanceof WP_Post ) {
			return false;
		}
		$parent_id = (int) $parent->ID;
	}

	$updated = wp_update_post(
		wp_slash(
			array(
				'ID'           => (int) $page->ID,
				'post_title'   => sanitize_text_field( (string) ( $entry['title'] ?? $page->post_title ) ),
				'post_name'    => sanitize_title( (string) ( $entry['slug'] ?? $page->post_name ) ),
				'post_content' => (string) ( $entry['content'] ?? $page->post_content ),
				'post_status'  => 'publish',
				'post_parent'  => $parent_id,
			)
		),
		true
	);

	if ( is_wp_error( $updated ) || (int) $updated <= 0 ) {
		return false;
	}

	$template = sanitize_key( (string) ( $entry['template'] ?? '' ) );
	if ( '' !== $template ) {
		update_post_meta( (int) $page->ID, '_wp_page_template', $template );
	}

	lmhg_site_core_update_topology_source_url( (int) $page->ID, $target_path );

	if ( $current_path !== $target_path ) {
		lmhg_site_core_delete_topology_page_duplicates( sanitize_title( basename( trim( $current_path, '/' ) ) ), 0 );
		lmhg_site_core_delete_topology_page_duplicates( sanitize_title( (string) ( $entry['slug'] ?? '' ) ), (int) $page->ID );
	}

	return true;
}

/**
 * Finds the published page for an exact nested path, ignoring stale draft duplicates.
 *
 * @param string $path Page path.
 * @return WP_Post|null
 */
function lmhg_site_core_find_published_topology_page( string $path ): ?WP_Post {
	$path  = trim( $path, '/' );
	$slug  = sanitize_title( basename( $path ) );
	$pages = get_posts(
		array(
			'name'           => $slug,
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		)
	);

	foreach ( $pages as $page ) {
		if ( $page instanceof WP_Post && trim( get_page_uri( $page ), '/' ) === $path ) {
			return $page;
		}
	}

	return null;
}

/**
 * Removes obsolete or duplicate page records for one route slug.
 *
 * @param string $slug Page slug.
 * @param int    $keep_id Page ID to preserve, or zero.
 */
function lmhg_site_core_delete_topology_page_duplicates( string $slug, int $keep_id ): void {
	if ( '' === $slug ) {
		return;
	}

	$pages = get_posts(
		array(
			'name'           => $slug,
			'post_type'      => 'page',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		)
	);

	foreach ( $pages as $page ) {
		if ( $page instanceof WP_Post && (int) $page->ID !== $keep_id ) {
			wp_delete_post( (int) $page->ID, true );
		}
	}
}

/**
 * Keeps source and canonical metadata aligned with a renamed page path.
 *
 * @param int    $post_id Page ID.
 * @param string $target_path New normalized path.
 */
function lmhg_site_core_update_topology_source_url( int $post_id, string $target_path ): void {
	foreach ( array( '_lmhg_source_url', '_lmhg_canonical_url' ) as $meta_key ) {
		$current = trim( (string) get_post_meta( $post_id, $meta_key, true ) );
		if ( '' === $current ) {
			update_post_meta( $post_id, $meta_key, home_url( $target_path ) );
			continue;
		}

		$scheme = (string) wp_parse_url( $current, PHP_URL_SCHEME );
		$host   = (string) wp_parse_url( $current, PHP_URL_HOST );
		$port   = absint( wp_parse_url( $current, PHP_URL_PORT ) );
		if ( '' !== $scheme && '' !== $host ) {
			$base = $scheme . '://' . $host . ( $port > 0 ? ':' . $port : '' );
			update_post_meta( $post_id, $meta_key, $base . $target_path );
		} else {
			update_post_meta( $post_id, $meta_key, $target_path );
		}
	}
}

/**
 * Renames or merges one relationship term without losing object assignments.
 *
 * @param string $taxonomy Taxonomy name.
 * @param string $old_slug Current term slug.
 * @param string $new_slug Replacement term slug.
 * @param string $new_name Replacement display name.
 * @return bool
 */
function lmhg_site_core_rename_topology_term( string $taxonomy, string $old_slug, string $new_slug, string $new_name ): bool {
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return false;
	}

	$old_term = get_term_by( 'slug', $old_slug, $taxonomy );
	$new_term = get_term_by( 'slug', $new_slug, $taxonomy );

	if ( $old_term instanceof WP_Term && $new_term instanceof WP_Term && $old_term->term_id !== $new_term->term_id ) {
		$object_ids = get_objects_in_term( (int) $old_term->term_id, $taxonomy );
		if ( is_wp_error( $object_ids ) ) {
			return false;
		}
		foreach ( $object_ids as $object_id ) {
			wp_set_object_terms( (int) $object_id, array( (int) $new_term->term_id ), $taxonomy, true );
		}
		$deleted = wp_delete_term( (int) $old_term->term_id, $taxonomy );
		if ( is_wp_error( $deleted ) || false === $deleted ) {
			return false;
		}
		$old_term = $new_term;
	}

	$term = $old_term instanceof WP_Term ? $old_term : $new_term;
	if ( ! $term instanceof WP_Term ) {
		$created = wp_insert_term( $new_name, $taxonomy, array( 'slug' => $new_slug ) );
		return ! is_wp_error( $created );
	}

	$result = wp_update_term(
		(int) $term->term_id,
		$taxonomy,
		array(
			'name' => $new_name,
			'slug' => $new_slug,
		)
	);

	return ! is_wp_error( $result );
}

/**
 * Renames seeded FAQ records while preserving any editor-authored detail.
 *
 * @param string $old_slug Current specialty slug.
 * @param string $new_slug Replacement specialty slug.
 * @param string $old_label Current display label.
 * @param string $new_label Replacement display label.
 * @return bool
 */
function lmhg_site_core_rename_topology_faq_posts( string $old_slug, string $new_slug, string $old_label, string $new_label ): bool {
	foreach ( array( 'fit', 'start' ) as $suffix ) {
		$old_post_slug = 'specialty-' . $old_slug . '-faq-' . $suffix;
		$new_post_slug = 'specialty-' . $new_slug . '-faq-' . $suffix;
		$posts         = get_posts(
			array(
				'name'           => $old_post_slug,
				'post_type'      => LMHG_SITE_CORE_FAQ_POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'no_found_rows'  => true,
			)
		);

		if ( empty( $posts ) || ! $posts[0] instanceof WP_Post ) {
			continue;
		}

		$faq     = $posts[0];
		$updated = wp_update_post(
			wp_slash(
				array(
					'ID'           => (int) $faq->ID,
					'post_name'    => $new_post_slug,
					'post_title'   => str_replace( $old_label, $new_label, $faq->post_title ),
					'post_content' => str_replace( $old_label, $new_label, $faq->post_content ),
				)
			),
			true
		);

		if ( is_wp_error( $updated ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Publishes approved FAQ copy from page data through the editable FAQ records.
 *
 * @param array<string,array<string,mixed>> $page_data Page data keyed by path.
 * @param string                            $page_path Page path.
 * @param string                            $faq_slug FAQ-set slug.
 * @return bool
 */
function lmhg_site_core_sync_topology_faq_items( array $page_data, string $page_path, string $faq_slug ): bool {
	$page_path = lmhg_site_core_normalize_redirect_path( $page_path );
	$entry     = $page_data[ $page_path ] ?? null;
	$items     = is_array( $entry ) && isset( $entry['faqItems'] ) && is_array( $entry['faqItems'] ) ? $entry['faqItems'] : array();
	$page      = lmhg_site_core_find_published_topology_page( $page_path );
	if ( empty( $items ) || ! $page instanceof WP_Post ) {
		return false;
	}

	$label   = preg_replace( '/\s+in Louisville, KY$/', '', (string) ( $entry['title'] ?? $faq_slug ) );
	$term_id = lmhg_site_core_ensure_specialty_faq_set( $faq_slug, (string) $label );
	if ( $term_id <= 0 ) {
		return false;
	}

	wp_set_object_terms( (int) $page->ID, array( $term_id ), LMHG_SITE_CORE_FAQ_SET_TAXONOMY, true );

	foreach ( array_values( $items ) as $index => $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}

		$suffix = 0 === $index ? 'fit' : ( 1 === $index ? 'start' : (string) ( $index + 1 ) );
		$slug   = 'specialty-' . $faq_slug . '-faq-' . $suffix;
		$posts  = get_posts(
			array(
				'name'           => $slug,
				'post_type'      => LMHG_SITE_CORE_FAQ_POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'no_found_rows'  => true,
			)
		);

		$post_id = ! empty( $posts ) && $posts[0] instanceof WP_Post ? (int) $posts[0]->ID : 0;
		$data    = array(
			'post_type'    => LMHG_SITE_CORE_FAQ_POST_TYPE,
			'post_status'  => 'publish',
			'post_name'    => $slug,
			'post_title'   => sanitize_text_field( (string) ( $item['question'] ?? '' ) ),
			'post_excerpt' => 'Owner-approved FAQ copy.',
			'post_content' => '<!-- wp:paragraph --><p>' . esc_html( (string) ( $item['answer'] ?? '' ) ) . '</p><!-- /wp:paragraph -->',
			'menu_order'   => ( $index + 1 ) * 10,
		);
		if ( $post_id > 0 ) {
			$data['ID'] = $post_id;
		}

		$result = wp_insert_post( wp_slash( $data ), true );
		if ( is_wp_error( $result ) || (int) $result <= 0 ) {
			return false;
		}
		wp_set_object_terms( (int) $result, array( $term_id ), LMHG_SITE_CORE_FAQ_SET_TAXONOMY, false );
	}

	return true;
}

/**
 * Moves Conflict Resolution Counseling from the couples family to Family Therapy.
 *
 * @return bool
 */
function lmhg_site_core_move_conflict_resolution_relationship(): bool {
	$term    = get_term_by( 'slug', 'conflict-resolution-counseling', LMHG_SITE_CORE_SPECIALTY_TAXONOMY );
	$family  = lmhg_site_core_find_published_topology_page( '/family-therapy/' );
	$couples = lmhg_site_core_find_published_topology_page( '/couples-counseling/' );
	if ( ! $term instanceof WP_Term || ! $family instanceof WP_Post || ! $couples instanceof WP_Post ) {
		return false;
	}

	$added   = wp_set_object_terms( (int) $family->ID, array( (int) $term->term_id ), LMHG_SITE_CORE_SPECIALTY_TAXONOMY, true );
	$removed = wp_remove_object_terms( (int) $couples->ID, array( (int) $term->term_id ), LMHG_SITE_CORE_SPECIALTY_TAXONOMY );

	return ! is_wp_error( $added ) && ! is_wp_error( $removed );
}

/**
 * Moves Parenting Support from Family Therapy to Child Therapy.
 *
 * @return bool
 */
function lmhg_site_core_move_parenting_support_relationship(): bool {
	$term   = get_term_by( 'slug', 'parenting-support', LMHG_SITE_CORE_SPECIALTY_TAXONOMY );
	$child  = lmhg_site_core_find_published_topology_page( '/child-counseling/' );
	$family = lmhg_site_core_find_published_topology_page( '/family-therapy/' );
	if ( ! $term instanceof WP_Term || ! $child instanceof WP_Post || ! $family instanceof WP_Post ) {
		return false;
	}

	$added   = wp_set_object_terms( (int) $child->ID, array( (int) $term->term_id ), LMHG_SITE_CORE_SPECIALTY_TAXONOMY, true );
	$removed = wp_remove_object_terms( (int) $family->ID, array( (int) $term->term_id ), LMHG_SITE_CORE_SPECIALTY_TAXONOMY );

	return ! is_wp_error( $added ) && ! is_wp_error( $removed );
}

/**
 * Removes the Relationship Counseling page and its dedicated runtime records.
 *
 * Unexpected editor-authored FAQs are moved to the Couples Counseling FAQ set.
 *
 * @return bool
 */
function lmhg_site_core_remove_relationship_counseling_records(): bool {
	$couples = lmhg_site_core_find_published_topology_page( '/couples-counseling/' );
	if ( ! $couples instanceof WP_Post ) {
		return false;
	}

	$couples_faq_id = lmhg_site_core_ensure_specialty_faq_set( 'couples-counseling', 'Couples Counseling' );
	if ( $couples_faq_id <= 0 ) {
		return false;
	}

	$relationship_faq = get_term_by( 'slug', 'relationship-counseling', LMHG_SITE_CORE_FAQ_SET_TAXONOMY );
	if ( $relationship_faq instanceof WP_Term ) {
		$object_ids = get_objects_in_term( (int) $relationship_faq->term_id, LMHG_SITE_CORE_FAQ_SET_TAXONOMY );
		if ( is_wp_error( $object_ids ) ) {
			return false;
		}

		foreach ( $object_ids as $object_id ) {
			$faq = get_post( (int) $object_id );
			if ( ! $faq instanceof WP_Post || LMHG_SITE_CORE_FAQ_POST_TYPE !== $faq->post_type ) {
				continue;
			}

			if ( str_starts_with( $faq->post_name, 'specialty-relationship-counseling-faq-' ) ) {
				if ( false === wp_delete_post( (int) $faq->ID, true ) ) {
					return false;
				}
				continue;
			}

			$added   = wp_set_object_terms( (int) $faq->ID, array( $couples_faq_id ), LMHG_SITE_CORE_FAQ_SET_TAXONOMY, true );
			$removed = wp_remove_object_terms( (int) $faq->ID, array( (int) $relationship_faq->term_id ), LMHG_SITE_CORE_FAQ_SET_TAXONOMY );
			if ( is_wp_error( $added ) || is_wp_error( $removed ) ) {
				return false;
			}
		}

		$deleted = wp_delete_term( (int) $relationship_faq->term_id, LMHG_SITE_CORE_FAQ_SET_TAXONOMY );
		if ( is_wp_error( $deleted ) || false === $deleted ) {
			return false;
		}
	}

	$relationship_term = get_term_by( 'slug', 'relationship-counseling', LMHG_SITE_CORE_SPECIALTY_TAXONOMY );
	$icon_id           = $relationship_term instanceof WP_Term
		? absint( get_term_meta( (int) $relationship_term->term_id, LMHG_SITE_CORE_SPECIALTY_ICON_ID_META, true ) )
		: 0;
	if ( $relationship_term instanceof WP_Term ) {
		$deleted = wp_delete_term( (int) $relationship_term->term_id, LMHG_SITE_CORE_SPECIALTY_TAXONOMY );
		if ( is_wp_error( $deleted ) || false === $deleted ) {
			return false;
		}
	}

	$pages = get_posts(
		array(
			'name'           => 'relationship-counseling',
			'post_type'      => 'page',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		)
	);
	foreach ( $pages as $page ) {
		if ( ! $page instanceof WP_Post ) {
			continue;
		}

		$uri        = '/' . trim( get_page_uri( $page ), '/' ) . '/';
		$source_url = (string) get_post_meta( (int) $page->ID, '_lmhg_source_url', true );
		$imported   = '/relationship-counseling/' === lmhg_site_core_normalize_redirect_path( (string) wp_parse_url( $source_url, PHP_URL_PATH ) );
		if ( '/relationship-counseling/' !== $uri && ! $imported ) {
			continue;
		}

		if ( false === wp_delete_post( (int) $page->ID, true ) ) {
			return false;
		}
	}

	$media_role_meta = defined( 'LMHG_SITE_CORE_MEDIA_ASSET_ROLE_META' )
		? (string) constant( 'LMHG_SITE_CORE_MEDIA_ASSET_ROLE_META' )
		: '_lmhg_asset_role';
	$attachments    = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'meta_key'       => $media_role_meta,
			'meta_value'     => 'specialty-icon-relationship-counseling',
		)
	);
	if ( $icon_id > 0 && empty( array_filter( $attachments, static fn( $item ) => $item instanceof WP_Post && (int) $item->ID === $icon_id ) ) ) {
		$icon = get_post( $icon_id );
		if ( $icon instanceof WP_Post && 'specialty-icon-relationship-counseling' === get_post_meta( $icon_id, $media_role_meta, true ) ) {
			$attachments[] = $icon;
		}
	}
	foreach ( $attachments as $attachment ) {
		if ( $attachment instanceof WP_Post && false === wp_delete_attachment( (int) $attachment->ID, true ) ) {
			return false;
		}
	}

	$remaining_pages = get_posts(
		array(
			'name'           => 'relationship-counseling',
			'post_type'      => 'page',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		)
	);
	foreach ( $remaining_pages as $page ) {
		if ( $page instanceof WP_Post && '/relationship-counseling/' === '/' . trim( get_page_uri( $page ), '/' ) . '/' ) {
			return false;
		}
	}

	return ! get_term_by( 'slug', 'relationship-counseling', LMHG_SITE_CORE_SPECIALTY_TAXONOMY )
		&& ! get_term_by( 'slug', 'relationship-counseling', LMHG_SITE_CORE_FAQ_SET_TAXONOMY );
}

/**
 * Deletes the replaced in-home specialty page, taxonomy terms, and seeded FAQ records.
 *
 * @return bool
 */
function lmhg_site_core_remove_obsolete_in_home_records(): bool {
	$target = lmhg_site_core_find_published_topology_page( '/locations/in-home/' );
	if ( ! $target instanceof WP_Post ) {
		return false;
	}

	lmhg_site_core_delete_topology_page_duplicates( 'therapy-in-your-home', 0 );

	foreach ( array( LMHG_SITE_CORE_SPECIALTY_TAXONOMY, LMHG_SITE_CORE_FAQ_SET_TAXONOMY ) as $taxonomy ) {
		$term = get_term_by( 'slug', 'therapy-in-your-home', $taxonomy );
		if ( $term instanceof WP_Term ) {
			$deleted = wp_delete_term( (int) $term->term_id, $taxonomy );
			if ( is_wp_error( $deleted ) || false === $deleted ) {
				return false;
			}
		}
	}

	foreach ( array( 'fit', 'start' ) as $suffix ) {
		$posts = get_posts(
			array(
				'name'           => 'specialty-therapy-in-your-home-faq-' . $suffix,
				'post_type'      => LMHG_SITE_CORE_FAQ_POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'no_found_rows'  => true,
			)
		);
		if ( ! empty( $posts ) && $posts[0] instanceof WP_Post ) {
			wp_delete_post( (int) $posts[0]->ID, true );
		}
	}

	return true;
}

/**
 * Rewrites approved labels and paths in page bodies and importer-owned hidden metadata.
 *
 * @return bool
 */
function lmhg_site_core_replace_topology_references(): bool {
	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		)
	);

	$meta_keys = array(
		'_lmhg_source_url',
		'_lmhg_canonical_url',
		'_lmhg_route_manifest_entry',
		'_lmhg_related_pages',
		'_lmhg_source_content',
		'_lmhg_seo_title',
		'_lmhg_meta_description',
		'_lmhg_h1',
		'_lmhg_primary_keyword',
		'_lmhg_secondary_keywords',
		'_lmhg_optimization_terms',
		'_lmhg_faq_items',
		'_lmhg_editable_blocks_manifest_entry',
		'_lmhg_editable_blocks',
		'_lmhg_editable_media_assets',
	);

	foreach ( $pages as $page ) {
		if ( ! $page instanceof WP_Post ) {
			continue;
		}

		$content = lmhg_site_core_replace_topology_string( $page->post_content );
		$title   = lmhg_site_core_replace_topology_string( $page->post_title );
		if ( $content !== $page->post_content || $title !== $page->post_title ) {
			$updated = wp_update_post(
				wp_slash(
					array(
						'ID'           => (int) $page->ID,
						'post_content' => $content,
						'post_title'   => $title,
					)
				),
				true
			);
			if ( is_wp_error( $updated ) ) {
				return false;
			}
		}

		foreach ( $meta_keys as $meta_key ) {
			$value = get_post_meta( (int) $page->ID, $meta_key, true );
			if ( ! is_string( $value ) || '' === $value ) {
				continue;
			}
			$replacement = lmhg_site_core_replace_topology_string( $value );
			if ( $replacement !== $value ) {
				update_post_meta( (int) $page->ID, $meta_key, wp_slash( $replacement ) );
			}
		}
	}

	foreach ( array( LMHG_SITE_CORE_SPECIALTY_TAXONOMY, LMHG_SITE_CORE_FAQ_SET_TAXONOMY ) as $taxonomy ) {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);
		if ( is_wp_error( $terms ) ) {
			return false;
		}

		foreach ( $terms as $term ) {
			if ( ! $term instanceof WP_Term ) {
				continue;
			}
			$description = lmhg_site_core_replace_topology_string( (string) $term->description );
			if ( $description !== (string) $term->description ) {
				$result = wp_update_term( (int) $term->term_id, $taxonomy, array( 'description' => $description ) );
				if ( is_wp_error( $result ) ) {
					return false;
				}
			}

			if ( LMHG_SITE_CORE_SPECIALTY_TAXONOMY === $taxonomy ) {
				$card_copy = (string) get_term_meta( (int) $term->term_id, LMHG_SITE_CORE_SPECIALTY_CARD_DESCRIPTION_META, true );
				$updated   = lmhg_site_core_replace_topology_string( $card_copy );
				if ( $updated !== $card_copy ) {
					update_term_meta( (int) $term->term_id, LMHG_SITE_CORE_SPECIALTY_CARD_DESCRIPTION_META, $updated );
				}
			}
		}
	}

	return true;
}

/**
 * Applies the approved non-overlapping path and label replacements.
 *
 * @param string $value Source text.
 * @return string
 */
function lmhg_site_core_replace_topology_string( string $value ): string {
	$value = str_replace(
		array(
			'/couples-conflict-resolution/',
			'/relationship-counseling/',
			'/therapy-in-your-home/',
			'Couples Conflict Resolution',
			'Child Behavioral Intervention',
		),
		array(
			'/couples-counseling/',
			'/couples-counseling/',
			'/locations/in-home/',
			'Couples Counseling',
			'Child Behavioral Therapy',
		),
		$value
	);

	return (string) preg_replace( '/(?<!Parent-Child )Attachment Therapy/', 'Parent-Child Attachment Therapy', $value );
}
