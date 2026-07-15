<?php
/**
 * Publication-readiness checks and legacy draft cleanup.
 *
 * @package LMHGSiteCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const LMHG_SITE_CORE_COPY_REVIEW_META          = '_lmhg_copy_review_status';
const LMHG_SITE_CORE_READINESS_MIGRATION_OPTION = 'lmhg_publication_readiness_migration_version';
const LMHG_SITE_CORE_READINESS_MIGRATION_VERSION = '2026-07-11-stale-drafts-v1';
const LMHG_SITE_CORE_READINESS_AUDIT_OPTION     = 'lmhg_publication_readiness_cleanup_audit';

add_action( 'init', 'lmhg_site_core_run_readiness_migration', 28 );

/**
 * Returns the allowed editorial copy-review states.
 *
 * @return array<string,string>
 */
function lmhg_site_core_copy_review_states(): array {
	return array(
		'needs-review' => 'Needs Copy Review',
		'approved'     => 'Copy Approved',
		'exempt'       => 'System / Legal Exemption',
	);
}

/**
 * Returns the filterable page-readiness states shown in administration.
 *
 * @return array<string,string>
 */
function lmhg_site_core_readiness_filter_states(): array {
	return array(
		'ready'      => 'Ready',
		'needs-work' => 'Needs Work',
		'exempt'     => 'System / Legal Exemption',
	);
}

/**
 * Sanitizes an editorial copy-review state.
 */
function lmhg_site_core_sanitize_copy_review_status( string $status ): string {
	$status = sanitize_key( $status );
	return isset( lmhg_site_core_copy_review_states()[ $status ] ) ? $status : '';
}

/**
 * Returns the effective copy-review state for a page.
 */
function lmhg_site_core_page_copy_review_status( int $post_id ): string {
	$status = lmhg_site_core_sanitize_copy_review_status( (string) get_post_meta( $post_id, LMHG_SITE_CORE_COPY_REVIEW_META, true ) );
	if ( '' !== $status ) {
		return $status;
	}

	if ( 'owner-answer-based-rich-copy' === (string) get_post_meta( $post_id, '_lmhg_seo_status', true ) ) {
		return 'approved';
	}

	return 'needs-review';
}

/**
 * Computes the publication-readiness state for one page.
 *
 * @return array{code:string,label:string,blockers:string[],copy_review:string,primary:string,secondary:string[]}
 */
function lmhg_site_core_page_readiness( int $post_id ): array {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post || 'page' !== $post->post_type ) {
		return array(
			'code'        => 'invalid',
			'label'       => 'Invalid Page',
			'blockers'    => array( 'Page record is unavailable' ),
			'copy_review' => 'needs-review',
			'primary'     => '',
			'secondary'   => array(),
		);
	}

	$keywords    = function_exists( 'lmhg_site_core_admin_keyword_data' ) ? lmhg_site_core_admin_keyword_data( $post_id ) : array( 'primary' => '', 'secondary' => array() );
	$primary     = trim( (string) ( $keywords['primary'] ?? '' ) );
	$secondary   = isset( $keywords['secondary'] ) && is_array( $keywords['secondary'] ) ? array_values( array_filter( array_map( 'trim', $keywords['secondary'] ) ) ) : array();
	$copy_review = lmhg_site_core_page_copy_review_status( $post_id );

	if ( 'trash' === $post->post_status ) {
		return array( 'code' => 'archived', 'label' => 'Archived', 'blockers' => array(), 'copy_review' => $copy_review, 'primary' => $primary, 'secondary' => $secondary );
	}

	if ( 'exempt' === $copy_review ) {
		return array( 'code' => 'exempt', 'label' => 'System / Legal Exemption', 'blockers' => array(), 'copy_review' => $copy_review, 'primary' => $primary, 'secondary' => $secondary );
	}

	$blockers = array();
	if ( 'publish' !== $post->post_status ) {
		$blockers[] = 'Not published';
	}
	if ( '' === $primary ) {
		$blockers[] = 'Missing primary keyword';
	}
	if ( empty( $secondary ) ) {
		$blockers[] = 'Missing secondary keyword';
	}
	if ( 'approved' !== $copy_review ) {
		$blockers[] = 'Copy review required';
	}

	return array(
		'code'        => empty( $blockers ) ? 'ready' : 'needs-work',
		'label'       => empty( $blockers ) ? 'Ready' : 'Needs Work',
		'blockers'    => $blockers,
		'copy_review' => $copy_review,
		'primary'     => $primary,
		'secondary'   => $secondary,
	);
}

/**
 * Counts current canonical page readiness states for the admin overview.
 *
 * @return array<string,int>
 */
function lmhg_site_core_readiness_counts(): array {
	$counts = array( 'ready' => 0, 'needs-work' => 0, 'exempt' => 0 );
	$pages  = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => array( 'publish', 'private' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	foreach ( $pages as $post_id ) {
		$code = lmhg_site_core_page_readiness( (int) $post_id )['code'];
		if ( isset( $counts[ $code ] ) ) {
			++$counts[ $code ];
		}
	}

	return $counts;
}

/**
 * Restricts page query arguments to one computed readiness state.
 *
 * @param array<string,mixed> $args Existing WP_Query arguments.
 * @return array<string,mixed>
 */
function lmhg_site_core_apply_readiness_filter( array $args, string $filter ): array {
	if ( ! isset( lmhg_site_core_readiness_filter_states()[ $filter ] ) ) {
		return $args;
	}

	$candidate_args                   = $args;
	$candidate_args['posts_per_page'] = -1;
	$candidate_args['paged']          = 1;
	$candidate_args['fields']         = 'ids';
	$candidate_args['no_found_rows']  = true;
	$candidates                       = new WP_Query( $candidate_args );
	$matched_ids                      = array();

	foreach ( $candidates->posts as $post_id ) {
		if ( $filter === lmhg_site_core_page_readiness( (int) $post_id )['code'] ) {
			$matched_ids[] = (int) $post_id;
		}
	}

	$args['post__in'] = ! empty( $matched_ids ) ? $matched_ids : array( 0 );
	return $args;
}

/**
 * Seeds explicit exemptions and moves confirmed legacy source drafts to Trash.
 */
function lmhg_site_core_run_readiness_migration(): void {
	if ( LMHG_SITE_CORE_READINESS_MIGRATION_VERSION === (string) get_option( LMHG_SITE_CORE_READINESS_MIGRATION_OPTION, '' ) ) {
		return;
	}

	foreach ( array( 'privacy-policy', 'terms-of-use', 'not-found' ) as $slug ) {
		$page = function_exists( 'lmhg_site_core_find_published_topology_page' ) ? lmhg_site_core_find_published_topology_page( '/' . $slug . '/' ) : null;
		if ( $page instanceof WP_Post && 'publish' === $page->post_status && '' === (string) get_post_meta( $page->ID, LMHG_SITE_CORE_COPY_REVIEW_META, true ) ) {
			update_post_meta( $page->ID, LMHG_SITE_CORE_COPY_REVIEW_META, 'exempt' );
		}
	}

	$page_data  = function_exists( 'lmhg_site_core_topology_page_data' ) ? lmhg_site_core_topology_page_data() : array();
	$source_ids = array();
	foreach ( $page_data as $path => $entry ) {
		if ( is_array( $entry ) && ! empty( $entry['sourceId'] ) ) {
			$source_ids[ absint( $entry['sourceId'] ) ] = (string) $path;
		}
	}

	$audit    = array();
	$failed   = array();
	$explicit = array(
		'sample-page'                 => 'WordPress placeholder',
		'couples-conflict-resolution' => 'Replaced by conflict-resolution-counseling',
		'relationship-counseling'     => 'Obsolete route',
	);
	$drafts   = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'draft',
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	);

	foreach ( $drafts as $draft ) {
		if ( ! $draft instanceof WP_Post || ! isset( $source_ids[ (int) $draft->ID ] ) ) {
			continue;
		}

		$reason       = $explicit[ $draft->post_name ] ?? '';
		$canonical_id = 0;
		$source_path  = trim( $source_ids[ (int) $draft->ID ], '/' );
		if ( '' === $reason && '' !== $source_path ) {
			$candidates = get_posts(
				array(
					'name'           => sanitize_title( basename( $source_path ) ),
					'post_type'      => 'page',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'no_found_rows'  => true,
				)
			);
			foreach ( $candidates as $candidate ) {
				if ( $candidate instanceof WP_Post && trim( get_page_uri( $candidate ), '/' ) === $source_path ) {
					$canonical_id = (int) $candidate->ID;
					$reason       = 'Older duplicate of canonical published page';
					break;
				}
			}
		}

		if ( '' === $reason ) {
			continue;
		}

		update_post_meta( $draft->ID, '_lmhg_readiness_cleanup_reason', $reason );
		$trashed = wp_trash_post( (int) $draft->ID );
		if ( $trashed instanceof WP_Post ) {
			$audit[] = array(
				'post_id'      => (int) $draft->ID,
				'slug'         => $draft->post_name,
				'reason'       => $reason,
				'canonical_id' => $canonical_id,
			);
		} else {
			$failed[] = (int) $draft->ID;
		}
	}

	update_option(
		LMHG_SITE_CORE_READINESS_AUDIT_OPTION,
		array(
			'version'    => LMHG_SITE_CORE_READINESS_MIGRATION_VERSION,
			'completed'  => gmdate( 'c' ),
			'trash_count' => count( $audit ),
			'pages'      => $audit,
			'failed_ids' => $failed,
		),
		false
	);
	if ( empty( $failed ) ) {
		update_option( LMHG_SITE_CORE_READINESS_MIGRATION_OPTION, LMHG_SITE_CORE_READINESS_MIGRATION_VERSION, false );
	}
}
