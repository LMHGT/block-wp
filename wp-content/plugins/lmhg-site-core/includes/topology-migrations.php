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
const LMHG_SITE_CORE_TOPOLOGY_MIGRATION_VERSION = '2026-07-10-rich-copy-v8';
const LMHG_SITE_CORE_HOMEPAGE_PRESENTATION_OPTION = 'lmhg_homepage_presentation_migration_version';
const LMHG_SITE_CORE_HOMEPAGE_PRESENTATION_VERSION = '2026-07-20-linked-service-grid-v1';
const LMHG_SITE_CORE_ROUTE_PARITY_MIGRATION_OPTION = 'lmhg_public_route_parity_migration_version';
const LMHG_SITE_CORE_ROUTE_PARITY_MIGRATION_VERSION = '2026-07-21-public-route-parity-v2';
const LMHG_SITE_CORE_ARTICLE_STUB_RETIREMENT_OPTION = 'lmhg_article_stub_retirement_version';
const LMHG_SITE_CORE_ARTICLE_STUB_RETIREMENT_VERSION = '2026-07-21-page-canonicals-v1';

add_action( 'init', 'lmhg_site_core_run_topology_migration', 27 );
add_action( 'init', 'lmhg_site_core_run_homepage_presentation_migration', 28 );
add_action( 'init', 'lmhg_site_core_run_public_route_parity_migration', 29 );
add_action( 'init', 'lmhg_site_core_retire_duplicate_article_posts', 30 );

/**
 * Retires imported Post stubs whose complete canonical versions are Pages.
 *
 * The development site is not indexed, so preserving the richer root-level
 * Page as the sole published object is safer than exposing a second date-based
 * URL or sitemap entry for the same article intent.
 */
function lmhg_site_core_retire_duplicate_article_posts(): void {
	if ( LMHG_SITE_CORE_ARTICLE_STUB_RETIREMENT_VERSION === (string) get_option( LMHG_SITE_CORE_ARTICLE_STUB_RETIREMENT_OPTION, '' ) ) {
		return;
	}

	$slugs = array(
		'family-therapy-vs-individual-therapy',
		'guide-to-individual-therapy',
		'top-5-signs-its-time-to-seek-therapy',
	);
	$complete = true;
	foreach ( $slugs as $slug ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
		if ( ! $page instanceof WP_Post || 'publish' !== $page->post_status ) {
			$complete = false;
			continue;
		}

		$posts = get_posts(
			array(
				'name'           => $slug,
				'post_type'      => 'post',
				'post_status'    => array( 'publish', 'future', 'pending', 'private' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);
		foreach ( $posts as $post_id ) {
			$updated = wp_update_post(
				array(
					'ID'          => (int) $post_id,
					'post_status' => 'draft',
				),
				true
			);
			if ( is_wp_error( $updated ) || (int) $updated <= 0 ) {
				$complete = false;
			}
		}
	}

	if ( $complete ) {
		update_option( LMHG_SITE_CORE_ARTICLE_STUB_RETIREMENT_OPTION, LMHG_SITE_CORE_ARTICLE_STUB_RETIREMENT_VERSION, false );
	}
}

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

	$complete        = true;
	$page_migrations = array(
		'/attachment-therapy/'              => '/attachment-therapy/',
		'/adolescent-counseling/'            => '/adolescent-counseling/',
		'/adult-counseling/'                 => '/adult-counseling/',
		'/anxiety-depression-therapy/'       => '/anxiety-depression-therapy/',
		'/case-management/'                  => '/case-management/',
		'/child-behavioral-intervention/'    => '/child-behavioral-intervention/',
		'/child-counseling/'                 => '/child-therapy/',
		'/co-parenting/'                     => '/co-parenting/',
		'/community-based-services/'         => '/community-based-services/',
		'/community-support/'                => '/community-support/',
		'/couples-conflict-resolution/'      => '/conflict-resolution-counseling/',
		'/couples-counseling/'               => '/couples-counseling/',
		'/court-ordered/'                    => '/family-court/',
		'/emdr-therapy/'                     => '/emdr-therapy/',
		'/family-reunification/'             => '/family-reunification/',
		'/family-therapy/'                   => '/family-therapy/',
		'/group-therapy/'                    => '/group-therapy/',
		'/individual-counseling/'            => '/individual-therapy/',
		'/locations/in-home/'                => '/locations/in-home/',
		'/parenting-support/'                => '/parenting-support/',
		'/play-therapy/'                     => '/play-therapy/',
		'/trauma-therapy/'                   => '/trauma-therapy/',
	);

	foreach ( $page_migrations as $current_path => $target_path ) {
		$complete = lmhg_site_core_sync_topology_page( $page_data, $current_path, $target_path ) && $complete;
	}

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
	$faq_migrations = array(
		'/attachment-therapy/'           => 'attachment-therapy',
		'/adolescent-counseling/'         => 'adolescent-counseling',
		'/adult-counseling/'              => 'adult-counseling',
		'/anxiety-depression-therapy/'    => 'anxiety-depression-therapy',
		'/case-management/'               => 'case-management',
		'/child-behavioral-intervention/' => 'child-behavioral-intervention',
		'/child-therapy/'                 => 'child-counseling',
		'/co-parenting/'                  => 'co-parenting',
		'/community-based-services/'      => 'community-based-services',
		'/community-support/'             => 'community-support',
		'/conflict-resolution-counseling/' => 'conflict-resolution-counseling',
		'/couples-counseling/'            => 'couples-counseling',
		'/family-court/'                  => 'court-ordered',
		'/emdr-therapy/'                  => 'emdr-therapy',
		'/family-reunification/'          => 'family-reunification',
		'/family-therapy/'                => 'family-therapy',
		'/group-therapy/'                 => 'group-therapy',
		'/individual-therapy/'            => 'individual-counseling',
		'/locations/in-home/'             => 'locations-in-home',
		'/parenting-support/'             => 'parenting-support',
		'/play-therapy/'                  => 'play-therapy',
		'/trauma-therapy/'                => 'trauma-therapy',
	);

	foreach ( $faq_migrations as $page_path => $faq_slug ) {
		$complete = lmhg_site_core_sync_topology_faq_items( $page_data, $page_path, $faq_slug ) && $complete;
	}

	$complete = lmhg_site_core_move_conflict_resolution_relationship() && $complete;
	$complete = lmhg_site_core_move_parenting_support_relationship() && $complete;
	$complete = lmhg_site_core_remove_legacy_couples_faqs() && $complete;
	$complete = lmhg_site_core_remove_obsolete_in_home_records() && $complete;
	$complete = lmhg_site_core_replace_topology_references() && $complete;
	$complete = lmhg_site_core_remove_relationship_counseling_records() && $complete;

	if ( $complete ) {
		update_option( LMHG_SITE_CORE_TOPOLOGY_MIGRATION_OPTION, LMHG_SITE_CORE_TOPOLOGY_MIGRATION_VERSION, false );
	}
}

/**
 * Publishes the homepage interaction refinements from the durable page-data
 * source without rerunning the broader page-topology migration.
 */
function lmhg_site_core_run_homepage_presentation_migration(): void {
	if ( LMHG_SITE_CORE_HOMEPAGE_PRESENTATION_VERSION === (string) get_option( LMHG_SITE_CORE_HOMEPAGE_PRESENTATION_OPTION, '' ) ) {
		return;
	}

	$page_data = lmhg_site_core_topology_page_data();
	$entry     = $page_data['/'] ?? null;
	$page      = get_post( (int) get_option( 'page_on_front' ) );
	if ( ! is_array( $entry ) || ! $page instanceof WP_Post || 'page' !== $page->post_type ) {
		return;
	}

	$content = (string) ( $entry['content'] ?? '' );
	if ( '' === trim( $content ) ) {
		return;
	}

	if ( $content !== (string) $page->post_content ) {
		$updated = wp_update_post(
			wp_slash(
				array(
					'ID'           => (int) $page->ID,
					'post_content' => $content,
				)
			),
			true
		);
		if ( is_wp_error( $updated ) || (int) $updated <= 0 ) {
			return;
		}
	}

	update_option( LMHG_SITE_CORE_HOMEPAGE_PRESENTATION_OPTION, LMHG_SITE_CORE_HOMEPAGE_PRESENTATION_VERSION, false );
}

/**
 * Aligns private-development routes with their established public counterparts.
 *
 * Article children are detached before their former hub is renamed so their
 * existing nested paths remain resolvable during the ID-preserving migration.
 */
function lmhg_site_core_run_public_route_parity_migration(): void {
	if ( LMHG_SITE_CORE_ROUTE_PARITY_MIGRATION_VERSION === (string) get_option( LMHG_SITE_CORE_ROUTE_PARITY_MIGRATION_OPTION, '' ) ) {
		return;
	}

	$page_data = lmhg_site_core_topology_page_data();
	if ( empty( $page_data ) ) {
		return;
	}

	$complete   = true;
	$migrations = array(
		'/articles/family-therapy-vs-individual-therapy/'            => '/family-therapy-vs-individual-therapy/',
		'/articles/guide-to-individual-therapy/'                      => '/guide-to-individual-therapy/',
		'/articles/how-to-talk-to-your-loved-ones-about-going-to-therapy/' => '/how-to-talk-to-your-loved-ones-about-going-to-therapy/',
		'/articles/top-5-signs-its-time-to-seek-therapy/'             => '/top-5-signs-its-time-to-seek-therapy/',
		'/articles/what-to-expect-when-starting-therapy/'             => '/what-to-expect-when-starting-therapy/',
		'/articles/'                                                  => '/blogs/',
		'/court-ordered/'                                             => '/family-court/',
		'/child-counseling/'                                          => '/child-therapy/',
		'/individual-counseling/'                                     => '/individual-therapy/',
		'/faq/about-lmhg/'                                            => '/what-we-do/',
		'/careers/'                                                   => '/we-are-hiring/',
		'/services/'                                                  => '/our-services/',
	);

	foreach ( $migrations as $current_path => $target_path ) {
		$synced   = lmhg_site_core_sync_topology_page( $page_data, $current_path, $target_path );
		$complete = $synced && $complete;
		if ( ! $synced ) {
			continue;
		}

		$page = lmhg_site_core_find_published_topology_page( $target_path );
		if ( ! $page instanceof WP_Post ) {
			$complete = false;
			continue;
		}

		$complete = lmhg_site_core_prepare_rank_math_route_meta( (int) $page->ID, $current_path, $target_path ) && $complete;
	}

	$complete = lmhg_site_core_replace_topology_references() && $complete;

	if ( $complete ) {
		update_option( LMHG_SITE_CORE_ROUTE_PARITY_MIGRATION_OPTION, LMHG_SITE_CORE_ROUTE_PARITY_MIGRATION_VERSION, false );
	}
}

/**
 * Removes only ordinary self-canonical Rank Math overrides after a route move.
 *
 * Rank Math should derive self-canonicals from the permalink so environment
 * hosts are never persisted. An intentional cross-page canonical is preserved.
 */
function lmhg_site_core_prepare_rank_math_route_meta( int $post_id, string $current_path, string $target_path ): bool {
	$canonical = trim( (string) get_post_meta( $post_id, 'rank_math_canonical_url', true ) );
	if ( '' !== $canonical ) {
		$canonical_path = lmhg_site_core_normalize_redirect_path( (string) wp_parse_url( $canonical, PHP_URL_PATH ) );
		$current_path   = lmhg_site_core_normalize_redirect_path( $current_path );
		$target_path    = lmhg_site_core_normalize_redirect_path( $target_path );
		if ( in_array( $canonical_path, array( $current_path, $target_path ), true ) ) {
			delete_post_meta( $post_id, 'rank_math_canonical_url' );
		}
	}

	delete_post_meta( $post_id, 'rank_math_seo_score' );
	clean_post_cache( $post_id );
	return true;
}

/**
 * Removes superseded seeded FAQs after the approved Couples copy is published.
 *
 * @return bool
 */
function lmhg_site_core_remove_legacy_couples_faqs(): bool {
	$slugs = array(
		'placeholder-couples-counseling-faq-1',
		'placeholder-couples-counseling-faq-2',
		'placeholder-couples-counseling-faq-3',
		'service-couples-counseling-faq-louisville',
		'service-couples-counseling-faq-fit',
	);

	foreach ( $slugs as $slug ) {
		$posts = get_posts(
			array(
				'name'           => $slug,
				'post_type'      => LMHG_SITE_CORE_FAQ_POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
			)
		);

		foreach ( $posts as $post ) {
			if ( $post instanceof WP_Post && false === wp_delete_post( (int) $post->ID, true ) ) {
				return false;
			}
		}
	}

	return true;
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
	lmhg_site_core_sync_topology_meta( (int) $page->ID, $entry, $target_path );

	if ( $current_path !== $target_path ) {
		$current_slug = sanitize_title( basename( trim( $current_path, '/' ) ) );
		$target_slug  = sanitize_title( (string) ( $entry['slug'] ?? '' ) );
		if ( $current_slug !== $target_slug ) {
			lmhg_site_core_delete_topology_page_duplicates( $current_slug, 0 );
		}
		lmhg_site_core_delete_topology_page_duplicates( $target_slug, (int) $page->ID );
	}

	return true;
}

/**
 * Publishes page-specific SEO and FAQ metadata without clearing unrelated importer data.
 *
 * @param int                 $post_id Page ID.
 * @param array<string,mixed> $entry Page-data entry.
 * @param string              $target_path Canonical page path.
 */
function lmhg_site_core_sync_topology_meta( int $post_id, array $entry, string $target_path ): void {
	$seo = isset( $entry['seo'] ) && is_array( $entry['seo'] ) ? $entry['seo'] : array();
	if ( ! empty( $seo ) ) {
		$meta = array(
			'_lmhg_seo_title'          => (string) ( $seo['title'] ?? '' ),
			'_lmhg_meta_description'   => (string) ( $seo['description'] ?? '' ),
			'_lmhg_h1'                 => (string) ( $seo['h1'] ?? '' ),
			'_lmhg_primary_keyword'    => (string) ( $seo['primaryKeyword'] ?? '' ),
			'_lmhg_secondary_keywords' => wp_json_encode( $seo['secondaryKeywords'] ?? array() ),
			'_lmhg_canonical_url'      => home_url( $target_path ),
			'_lmhg_seo_status'         => (string) ( $seo['status'] ?? 'owner-answer-based-rich-copy' ),
		);

		foreach ( $meta as $key => $value ) {
			update_post_meta( $post_id, $key, '_lmhg_secondary_keywords' === $key ? wp_slash( $value ) : $value );
		}
	}

	if ( isset( $entry['faqItems'] ) && is_array( $entry['faqItems'] ) ) {
		update_post_meta( $post_id, '_lmhg_faq_items', wp_slash( wp_json_encode( $entry['faqItems'] ) ) );
	}
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
 * Removes superseded placeholder FAQs after approved page-specific FAQs exist.
 *
 * @param string $faq_slug FAQ-set slug.
 * @return bool
 */
function lmhg_site_core_remove_legacy_faq_posts( string $faq_slug ): bool {
	foreach ( array( 'placeholder-', 'service-' ) as $prefix ) {
		$posts = get_posts(
			array(
				'post_type'      => LMHG_SITE_CORE_FAQ_POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
				'post_name__in'  => array(
					$prefix . $faq_slug . '-faq-1',
					$prefix . $faq_slug . '-faq-2',
					$prefix . $faq_slug . '-faq-3',
					$prefix . $faq_slug . '-faq-4',
					$prefix . $faq_slug . '-faq-fit',
					$prefix . $faq_slug . '-faq-start',
					$prefix . $faq_slug . '-faq-louisville',
				),
			)
		);

		foreach ( $posts as $post ) {
			if ( $post instanceof WP_Post && false === wp_delete_post( (int) $post->ID, true ) ) {
				return false;
			}
		}
	}

	$legacy_slugs = array(
		'individual-counseling' => array(
			'individual-counseling-concerns',
			'individual-counseling-therapy-type',
			'individual-counseling-start-louisville',
		),
	);
	if ( isset( $legacy_slugs[ $faq_slug ] ) ) {
		$posts = get_posts(
			array(
				'post_type'      => LMHG_SITE_CORE_FAQ_POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
				'post_name__in'  => $legacy_slugs[ $faq_slug ],
			)
		);

		foreach ( $posts as $post ) {
			if ( $post instanceof WP_Post && false === wp_delete_post( (int) $post->ID, true ) ) {
				return false;
			}
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

	return lmhg_site_core_remove_legacy_faq_posts( $faq_slug );
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
	$child  = lmhg_site_core_find_published_topology_page( '/child-therapy/' );
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
			'post_type'      => array( 'page', LMHG_SITE_CORE_FAQ_POST_TYPE, 'wp_navigation', 'wp_template_part' ),
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
		'_lmhg_faq_queue_page_path',
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
			'/articles/family-therapy-vs-individual-therapy/',
			'/articles/guide-to-individual-therapy/',
			'/articles/how-to-talk-to-your-loved-ones-about-going-to-therapy/',
			'/articles/top-5-signs-its-time-to-seek-therapy/',
			'/articles/what-to-expect-when-starting-therapy/',
			'/individual-counseling/',
			'/child-counseling/',
			'/court-ordered/',
			'/faq/about-lmhg/',
			'/careers/',
			'/services/',
			'/articles/',
			'/couples-conflict-resolution/',
			'/relationship-counseling/',
			'/therapy-in-your-home/',
			'Couples Conflict Resolution',
			'Child Behavioral Intervention',
		),
		array(
			'/family-therapy-vs-individual-therapy/',
			'/guide-to-individual-therapy/',
			'/how-to-talk-to-your-loved-ones-about-going-to-therapy/',
			'/top-5-signs-its-time-to-seek-therapy/',
			'/what-to-expect-when-starting-therapy/',
			'/individual-therapy/',
			'/child-therapy/',
			'/family-court/',
			'/what-we-do/',
			'/we-are-hiring/',
			'/our-services/',
			'/blogs/',
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
