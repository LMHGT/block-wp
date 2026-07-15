<?php
/**
 * Dormant Rank Math handoff and runtime integration.
 *
 * @package LMHGSiteCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const LMHG_SITE_CORE_RANK_MATH_HANDOFF_OPTION  = 'lmhg_rank_math_handoff_version';
const LMHG_SITE_CORE_RANK_MATH_HANDOFF_VERSION = '2026-07-11-rank-math-v1';
const LMHG_SITE_CORE_RANK_MATH_JOURNAL_OPTION  = 'lmhg_rank_math_handoff_journal';
const LMHG_SITE_CORE_RANK_MATH_LOCK_OPTION     = 'lmhg_rank_math_handoff_lock';
const LMHG_SITE_CORE_RANK_MATH_LOCK_TTL        = 900;

add_action( 'init', 'lmhg_site_core_configure_rank_math_integration', 99 );
add_action( 'admin_post_lmhg_rank_math_handoff', 'lmhg_site_core_handle_rank_math_handoff' );
add_action( 'admin_post_lmhg_rank_math_handoff_rollback', 'lmhg_site_core_handle_rank_math_handoff_rollback' );
add_action( 'enqueue_block_editor_assets', 'lmhg_site_core_enqueue_rank_math_content_bridge', 99 );
add_filter( 'rank_math/frontend/disable_integration', 'lmhg_site_core_rank_math_disable_pending_frontend', PHP_INT_MAX );
add_filter( 'rank_math/modules', 'lmhg_site_core_rank_math_limit_pending_modules', PHP_INT_MAX );

/** Returns whether Rank Math Free is loaded and available. */
function lmhg_site_core_rank_math_is_active(): bool {
	return defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' );
}

/** Returns whether Rank Math PRO is loaded and available. */
function lmhg_site_core_rank_math_pro_is_active(): bool {
	return defined( 'RANK_MATH_PRO_VERSION' ) || class_exists( 'RankMathPro' );
}

/**
 * Adds LMHG's server-rendered page sections to Rank Math's editor analysis.
 *
 * Rank Math reads Gutenberg post_content natively. This bridge intentionally
 * sends only content assembled from taxonomy relationships or plugin settings,
 * avoiding duplicate analysis of the page's ordinary blocks.
 */
function lmhg_site_core_enqueue_rank_math_content_bridge(): void {
	if ( ! lmhg_site_core_rank_math_is_active() ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen instanceof WP_Screen || 'page' !== $screen->post_type ) {
		return;
	}

	$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
	if ( $post_id <= 0 ) {
		return;
	}

	$asset_path = dirname( __DIR__ ) . '/assets/rank-math-content-bridge.js';
	if ( ! is_readable( $asset_path ) ) {
		return;
	}

	wp_enqueue_script(
		'lmhg-rank-math-content-bridge',
		plugins_url( 'assets/rank-math-content-bridge.js', dirname( __DIR__ ) . '/lmhg-site-core.php' ),
		array( 'wp-hooks', 'rank-math-analyzer' ),
		(string) filemtime( $asset_path ),
		true
	);
	wp_localize_script(
		'lmhg-rank-math-content-bridge',
		'lmhgRankMathAnalysis',
		array( 'extraContent' => lmhg_site_core_rank_math_extra_analysis_content( $post_id ) )
	);
}

/** Returns the page-specific content LMHG appends outside Gutenberg post_content. */
function lmhg_site_core_rank_math_extra_analysis_content( int $post_id ): string {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post || 'page' !== $post->post_type ) {
		return '';
	}

	$sections = array();
	if ( function_exists( 'lmhg_site_core_render_taxonomy_related_pages' ) && has_term( '', LMHG_SITE_CORE_SPECIALTY_TAXONOMY, $post ) ) {
		$sections[] = lmhg_site_core_render_taxonomy_related_pages( $post_id );
	}
	if ( function_exists( 'lmhg_site_core_render_faqs_for_page' ) && has_term( '', LMHG_SITE_CORE_FAQ_SET_TAXONOMY, $post ) ) {
		$sections[] = lmhg_site_core_render_faqs_for_page( $post_id );
	}
	if ( function_exists( 'lmhg_site_core_is_review_showcase_page' ) && function_exists( 'lmhg_site_core_render_review_showcase' ) && lmhg_site_core_is_review_showcase_page( $post ) ) {
		$sections[] = lmhg_site_core_render_review_showcase();
	}
	if ( function_exists( 'lmhg_site_core_render_team_members' ) && ( 'team-page' === get_page_template_slug( $post ) || ( function_exists( 'lmhg_site_core_team_page_slugs' ) && in_array( $post->post_name, lmhg_site_core_team_page_slugs(), true ) ) ) ) {
		$sections[] = lmhg_site_core_render_team_members();
	}
	if ( function_exists( 'lmhg_site_core_page_cta_term' ) ) {
		$term = lmhg_site_core_page_cta_term( $post_id );
		if ( $term instanceof WP_Term && LMHG_SITE_CORE_CTA_NONE_SLUG !== $term->slug && 'active' === (string) get_term_meta( $term->term_id, '_lmhg_cta_lifecycle', true ) ) {
			$sections[] = (string) get_term_meta( $term->term_id, '_lmhg_cta_title', true ) . ' ' . (string) get_term_meta( $term->term_id, '_lmhg_cta_description', true );
		}
	}

	$content = implode( "\n", array_filter( $sections ) );
	return trim( wp_kses_post( $content ) );
}

/** Returns whether the one-time data handoff is complete. */
function lmhg_site_core_rank_math_handoff_complete(): bool {
	return LMHG_SITE_CORE_RANK_MATH_HANDOFF_VERSION === (string) get_option( LMHG_SITE_CORE_RANK_MATH_HANDOFF_OPTION, '' );
}

/** Returns whether Rank Math currently owns standard public SEO output. */
function lmhg_site_core_rank_math_owns_standard_seo(): bool {
	return lmhg_site_core_rank_math_is_active() && lmhg_site_core_rank_math_handoff_complete();
}

/** Keeps Rank Math's public metadata dormant until the handoff commits. */
function lmhg_site_core_rank_math_disable_pending_frontend( mixed $disabled ): bool {
	if ( lmhg_site_core_rank_math_is_active() && ! lmhg_site_core_rank_math_handoff_complete() ) {
		return true;
	}
	return (bool) $disabled;
}

/**
 * Prevents write-heavy or public-output modules from loading before handoff.
 *
 * Module preferences remain stored by Rank Math. The temporary disabled flag
 * disappears automatically after a successful handoff.
 */
function lmhg_site_core_rank_math_limit_pending_modules( array $modules ): array {
	if ( ! lmhg_site_core_rank_math_is_active() || lmhg_site_core_rank_math_handoff_complete() ) {
		return $modules;
	}

	$held_modules = array(
		'404-monitor',
		'ai-visibility',
		'analytics',
		'content-ai',
		'image-seo',
		'instant-indexing',
		'link-counter',
		'link-genius',
		'llms-txt',
		'local-seo',
		'redirections',
		'sitemap',
	);
	foreach ( $held_modules as $module_id ) {
		if ( ! isset( $modules[ $module_id ] ) || ! is_array( $modules[ $module_id ] ) ) {
			continue;
		}
		$modules[ $module_id ]['disabled']      = true;
		$modules[ $module_id ]['disabled_text'] = esc_html__( 'Complete the LMHG conflict-safe SEO handoff before enabling this module.', 'lmhg-site-core' );
	}
	return $modules;
}

/** Hands standard SEO output to Rank Math while retaining LMHG-only extensions. */
function lmhg_site_core_configure_rank_math_integration(): void {
	if ( ! lmhg_site_core_rank_math_is_active() ) {
		return;
	}
	if ( ! lmhg_site_core_rank_math_handoff_complete() ) {
		return;
	}

	remove_filter( 'pre_get_document_title', 'lmhg_site_core_document_title' );
	remove_action( 'wp_head', 'lmhg_site_core_output_canonical', 4 );
	remove_action( 'wp_head', 'lmhg_site_core_output_meta_description', 5 );
	remove_action( 'wp_head', 'lmhg_site_core_output_json_ld', 20 );
	add_filter( 'rank_math/json_ld', 'lmhg_site_core_extend_rank_math_json_ld', 90, 2 );
}

/** Adds only LMHG-specific Service, FAQ, and Review entities to Rank Math's graph. */
function lmhg_site_core_extend_rank_math_json_ld( array $data, mixed $json_ld = null ): array {
	unset( $json_ld );
	if ( ! is_singular() ) {
		return $data;
	}
	$post_id = (int) get_queried_object_id();
	if ( $post_id <= 0 ) {
		return $data;
	}

	$canonical = lmhg_site_core_current_canonical_url();
	$nodes     = array();
	$service   = lmhg_site_core_service_schema_node( $post_id, $canonical );
	if ( ! empty( $service ) && ! lmhg_site_core_rank_math_graph_has_type( $data, 'Service' ) ) {
		if ( lmhg_site_core_rank_math_graph_has_id( $data, home_url( '/#organization' ) ) ) {
			$service['provider'] = array( '@id' => home_url( '/#organization' ) );
		}
		$nodes['lmhg_service'] = $service;
	}

	$faq_items = function_exists( 'lmhg_site_core_publishable_faq_items_for_page' ) ? lmhg_site_core_publishable_faq_items_for_page( $post_id ) : array();
	if ( empty( $faq_items ) ) {
		$faq_items = lmhg_site_core_publishable_faq_items( lmhg_site_core_route_manifest_entry( $post_id ) );
	}
	if ( ! empty( $faq_items ) && ! lmhg_site_core_rank_math_graph_has_type( $data, 'FAQPage' ) ) {
		$nodes['lmhg_faq'] = lmhg_site_core_faq_schema_node( $faq_items, $canonical );
	}

	$reviews = function_exists( 'lmhg_site_core_review_showcase_schema_nodes' ) ? lmhg_site_core_review_showcase_schema_nodes( $post_id ) : array();
	foreach ( array_values( $reviews ) as $index => $review ) {
		$nodes[ 'lmhg_review_' . $index ] = $review;
	}

	foreach ( $nodes as $key => $node ) {
		$id = isset( $node['@id'] ) ? (string) $node['@id'] : '';
		if ( '' !== $id && lmhg_site_core_rank_math_graph_has_id( $data, $id ) ) {
			continue;
		}
		$data[ $key ] = $node;
	}
	return $data;
}

/** Checks a Rank Math graph for an entity type, including multi-type nodes. */
function lmhg_site_core_rank_math_graph_has_type( array $data, string $type ): bool {
	foreach ( $data as $node ) {
		if ( ! is_array( $node ) ) {
			continue;
		}
		$types = isset( $node['@type'] ) && is_array( $node['@type'] ) ? $node['@type'] : array( $node['@type'] ?? '' );
		if ( in_array( $type, $types, true ) ) {
			return true;
		}
	}
	return false;
}

/** Checks a Rank Math graph for a stable entity ID. */
function lmhg_site_core_rank_math_graph_has_id( array $data, string $id ): bool {
	foreach ( $data as $node ) {
		if ( is_array( $node ) && $id === (string) ( $node['@id'] ?? '' ) ) {
			return true;
		}
	}
	return false;
}

/** Returns a stable report shape for dry runs, applies, and rollbacks. */
function lmhg_site_core_rank_math_empty_handoff_report(): array {
	return array(
		'eligible'       => 0,
		'mapped'         => 0,
		'conflicts'      => 0,
		'conflict_items' => array(),
		'skipped'        => 0,
		'writes'         => 0,
		'rolled_back'    => 0,
		'complete'       => false,
		'blocked'        => false,
		'reason'         => 'pending',
		'journal_id'     => '',
	);
}

/** Returns whether one LMHG source value has nothing meaningful to hand off. */
function lmhg_site_core_rank_math_mapping_value_is_empty( mixed $value ): bool {
	return '' === $value || ( is_array( $value ) && empty( $value ) );
}

/** Builds a no-write mapping plan and identifies every pre-existing value. */
function lmhg_site_core_rank_math_handoff_plan(): array {
	$report  = lmhg_site_core_rank_math_empty_handoff_report();
	$entries = array();
	$pages   = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		)
	);

	foreach ( $pages as $page ) {
		if ( ! $page instanceof WP_Post ) {
			continue;
		}
		++$report['eligible'];
		foreach ( lmhg_site_core_rank_math_mapping_for_page( (int) $page->ID ) as $target => $source ) {
			if ( lmhg_site_core_rank_math_mapping_value_is_empty( $source ) ) {
				++$report['skipped'];
				continue;
			}

			$exists   = metadata_exists( 'post', $page->ID, $target );
			$existing = $exists ? get_post_meta( $page->ID, $target, true ) : null;
			if ( $exists ) {
				if ( $existing === $source ) {
					++$report['skipped'];
				} else {
					++$report['conflicts'];
					$report['conflict_items'][] = array(
						'post_id'    => (int) $page->ID,
						'page_title' => get_the_title( $page ),
						'target'     => $target,
					);
				}
				continue;
			}

			++$report['mapped'];
			$entries[] = array(
				'post_id'       => (int) $page->ID,
				'target'        => $target,
				'before_exists' => false,
				'before'        => null,
				'after'         => $source,
				'attempting'    => false,
				'applied'       => false,
			);
		}
	}

	$report['blocked'] = $report['conflicts'] > 0;
	$report['reason']  = $report['blocked'] ? 'conflicts' : 'ready';
	return array( 'report' => $report, 'entries' => $entries );
}

/** Stores the durable handoff journal without autoloading it on public requests. */
function lmhg_site_core_rank_math_store_journal( array $journal ): bool {
	$missing  = '__lmhg_rank_math_journal_missing__';
	$existing = get_option( LMHG_SITE_CORE_RANK_MATH_JOURNAL_OPTION, $missing );
	if ( $missing === $existing ) {
		add_option( LMHG_SITE_CORE_RANK_MATH_JOURNAL_OPTION, $journal, '', false );
	} else {
		update_option( LMHG_SITE_CORE_RANK_MATH_JOURNAL_OPTION, $journal, false );
	}
	return get_option( LMHG_SITE_CORE_RANK_MATH_JOURNAL_OPTION, array() ) === $journal;
}

/** Acquires the one-writer handoff lock and returns its token. */
function lmhg_site_core_rank_math_acquire_lock(): string {
	$existing = get_option( LMHG_SITE_CORE_RANK_MATH_LOCK_OPTION, array() );
	if ( is_array( $existing ) && isset( $existing['created_at'] ) && (int) $existing['created_at'] < time() - LMHG_SITE_CORE_RANK_MATH_LOCK_TTL ) {
		delete_option( LMHG_SITE_CORE_RANK_MATH_LOCK_OPTION );
	}

	$token = wp_generate_uuid4();
	$lock  = array( 'token' => $token, 'created_at' => time(), 'user_id' => get_current_user_id() );
	return add_option( LMHG_SITE_CORE_RANK_MATH_LOCK_OPTION, $lock, '', false ) ? $token : '';
}

/** Releases the handoff lock only when this request owns it. */
function lmhg_site_core_rank_math_release_lock( string $token ): void {
	$lock = get_option( LMHG_SITE_CORE_RANK_MATH_LOCK_OPTION, array() );
	if ( is_array( $lock ) && hash_equals( (string) ( $lock['token'] ?? '' ), $token ) ) {
		delete_option( LMHG_SITE_CORE_RANK_MATH_LOCK_OPTION );
	}
}

/** Rolls back journaled writes without overwriting later manual Rank Math edits. */
function lmhg_site_core_rank_math_rollback_journal( array $journal, string $reason = 'administrator' ): array {
	$result  = array( 'journal' => $journal, 'rolled_back' => 0, 'conflicts' => 0 );
	$entries = isset( $journal['entries'] ) && is_array( $journal['entries'] ) ? $journal['entries'] : array();

	foreach ( $entries as $entry ) {
		if ( empty( $entry['applied'] ) && empty( $entry['attempting'] ) ) {
			continue;
		}
		$post_id = (int) ( $entry['post_id'] ?? 0 );
		$target  = (string) ( $entry['target'] ?? '' );
		$exists  = metadata_exists( 'post', $post_id, $target );
		$current = $exists ? get_post_meta( $post_id, $target, true ) : null;
		if ( ( $exists && $current !== ( $entry['after'] ?? null ) ) || ( ! $exists && ! empty( $entry['before_exists'] ) ) ) {
			++$result['conflicts'];
		}
	}

	if ( $result['conflicts'] > 0 ) {
		$journal['status']             = 'rollback_blocked';
		$journal['rollback_reason']    = sanitize_key( $reason );
		$journal['rolled_back_at']     = gmdate( 'c' );
		$journal['rollback_count']     = 0;
		$journal['rollback_conflicts'] = $result['conflicts'];
		lmhg_site_core_rank_math_store_journal( $journal );
		$result['journal'] = $journal;
		return $result;
	}

	for ( $index = count( $entries ) - 1; $index >= 0; --$index ) {
		$entry = $entries[ $index ];
		if ( empty( $entry['applied'] ) && empty( $entry['attempting'] ) ) {
			continue;
		}
		$post_id = (int) ( $entry['post_id'] ?? 0 );
		$target  = (string) ( $entry['target'] ?? '' );
		$exists  = metadata_exists( 'post', $post_id, $target );
		$current = $exists ? get_post_meta( $post_id, $target, true ) : null;
		if ( ! empty( $entry['before_exists'] ) ) {
			update_post_meta( $post_id, $target, $entry['before'] ?? '' );
		} elseif ( $exists ) {
			delete_post_meta( $post_id, $target );
		}

		$restored = ! empty( $entry['before_exists'] )
			? metadata_exists( 'post', $post_id, $target ) && get_post_meta( $post_id, $target, true ) === ( $entry['before'] ?? '' )
			: ! metadata_exists( 'post', $post_id, $target );
		if ( ! $restored ) {
			++$result['conflicts'];
			continue;
		}

		++$result['rolled_back'];
		$entries[ $index ]['attempting'] = false;
		$entries[ $index ]['applied']    = false;
		$entries[ $index ]['rolled_back'] = true;
		$journal['entries']               = $entries;
		lmhg_site_core_rank_math_store_journal( $journal );
	}

	$journal['entries']       = $entries;
	$journal['status']        = $result['conflicts'] > 0 ? 'rollback_blocked' : 'rolled_back';
	$journal['rollback_reason'] = sanitize_key( $reason );
	$journal['rolled_back_at']  = gmdate( 'c' );
	$journal['rollback_count']  = $result['rolled_back'];
	$journal['rollback_conflicts'] = $result['conflicts'];
	lmhg_site_core_rank_math_store_journal( $journal );
	$result['journal'] = $journal;
	return $result;
}

/** Produces a no-write report or atomically applies a conflict-free handoff. */
function lmhg_site_core_rank_math_handoff( bool $apply = false ): array {
	$report = lmhg_site_core_rank_math_empty_handoff_report();
	if ( ! lmhg_site_core_rank_math_is_active() ) {
		$report['blocked'] = true;
		$report['reason']  = 'rank_math_inactive';
		return $report;
	}
	if ( ! lmhg_site_core_rank_math_pro_is_active() ) {
		$report['blocked'] = true;
		$report['reason']  = 'rank_math_pro_inactive';
		return $report;
	}
	if ( lmhg_site_core_rank_math_handoff_complete() ) {
		$report['complete'] = true;
		$report['reason']   = 'already_complete';
		return $report;
	}

	$plan   = lmhg_site_core_rank_math_handoff_plan();
	$report = $plan['report'];
	if ( ! $apply || $report['blocked'] ) {
		return $report;
	}

	$lock_token = lmhg_site_core_rank_math_acquire_lock();
	if ( '' === $lock_token ) {
		$report['blocked'] = true;
		$report['reason']  = 'locked';
		return $report;
	}

	$journal = array();
	try {
		$plan   = lmhg_site_core_rank_math_handoff_plan();
		$report = $plan['report'];
		if ( $report['blocked'] ) {
			return $report;
		}

		$journal = array(
			'id'         => wp_generate_uuid4(),
			'version'    => LMHG_SITE_CORE_RANK_MATH_HANDOFF_VERSION,
			'status'     => 'prepared',
			'started_at' => gmdate( 'c' ),
			'user_id'    => get_current_user_id(),
			'entries'    => $plan['entries'],
		);
		$report['journal_id'] = $journal['id'];
		if ( ! lmhg_site_core_rank_math_store_journal( $journal ) ) {
			$report['blocked'] = true;
			$report['reason']  = 'journal_prepare_failed';
			return $report;
		}

		$journal['status'] = 'applying';
		lmhg_site_core_rank_math_store_journal( $journal );
		$failure = '';
		foreach ( $journal['entries'] as $index => $entry ) {
			$post_id = (int) $entry['post_id'];
			$target  = (string) $entry['target'];
			if ( metadata_exists( 'post', $post_id, $target ) ) {
				$failure = 'concurrent_value_detected';
				break;
			}

			$journal['entries'][ $index ]['attempting'] = true;
			if ( ! lmhg_site_core_rank_math_store_journal( $journal ) ) {
				$failure = 'journal_write_failed';
				break;
			}
			if ( ! add_post_meta( $post_id, $target, $entry['after'], true ) ) {
				$failure = 'metadata_write_failed';
				break;
			}

			$journal['entries'][ $index ]['attempting'] = false;
			$journal['entries'][ $index ]['applied']    = true;
			++$report['writes'];
			if ( ! lmhg_site_core_rank_math_store_journal( $journal ) ) {
				$failure = 'journal_write_failed';
				break;
			}
		}

		if ( '' !== $failure ) {
			$rollback = lmhg_site_core_rank_math_rollback_journal( $journal, $failure );
			$report['rolled_back'] = $rollback['rolled_back'];
			$report['conflicts']  += $rollback['conflicts'];
			$report['blocked']     = true;
			$report['reason']      = $rollback['conflicts'] > 0 ? 'rollback_blocked' : $failure;
			return $report;
		}

		$journal['status']     = 'applied';
		$journal['applied_at'] = gmdate( 'c' );
		if ( ! lmhg_site_core_rank_math_store_journal( $journal ) ) {
			$rollback = lmhg_site_core_rank_math_rollback_journal( $journal, 'journal_finalize_failed' );
			$report['rolled_back'] = $rollback['rolled_back'];
			$report['blocked']     = true;
			$report['reason']      = 'journal_finalize_failed';
			return $report;
		}

		update_option( LMHG_SITE_CORE_RANK_MATH_HANDOFF_OPTION, LMHG_SITE_CORE_RANK_MATH_HANDOFF_VERSION, false );
		if ( ! lmhg_site_core_rank_math_handoff_complete() ) {
			$rollback = lmhg_site_core_rank_math_rollback_journal( $journal, 'completion_marker_failed' );
			$report['rolled_back'] = $rollback['rolled_back'];
			$report['blocked']     = true;
			$report['reason']      = 'completion_marker_failed';
			return $report;
		}

		$journal['status']       = 'complete';
		$journal['completed_at'] = gmdate( 'c' );
		if ( ! lmhg_site_core_rank_math_store_journal( $journal ) ) {
			delete_option( LMHG_SITE_CORE_RANK_MATH_HANDOFF_OPTION );
			$rollback = lmhg_site_core_rank_math_rollback_journal( $journal, 'journal_completion_failed' );
			$report['rolled_back'] = $rollback['rolled_back'];
			$report['blocked']     = true;
			$report['reason']      = 'journal_completion_failed';
			return $report;
		}

		$report['complete'] = true;
		$report['reason']   = 'complete';
		return $report;
	} catch ( Throwable $error ) {
		if ( ! empty( $journal['entries'] ) ) {
			$rollback = lmhg_site_core_rank_math_rollback_journal( $journal, 'unexpected_error' );
			$report['rolled_back'] = $rollback['rolled_back'];
			$report['conflicts']  += $rollback['conflicts'];
		}
		$report['blocked'] = true;
		$report['reason']  = $report['conflicts'] > 0 ? 'rollback_blocked' : 'unexpected_error';
		return $report;
	} finally {
		lmhg_site_core_rank_math_release_lock( $lock_token );
	}
}

/** Reverts the most recent handoff when none of its values changed afterward. */
function lmhg_site_core_rank_math_handoff_rollback(): array {
	$report  = lmhg_site_core_rank_math_empty_handoff_report();
	$journal = get_option( LMHG_SITE_CORE_RANK_MATH_JOURNAL_OPTION, array() );
	if ( ! is_array( $journal ) || empty( $journal['entries'] ) ) {
		$report['blocked'] = true;
		$report['reason']  = 'journal_missing';
		return $report;
	}

	$lock_token = lmhg_site_core_rank_math_acquire_lock();
	if ( '' === $lock_token ) {
		$report['blocked'] = true;
		$report['reason']  = 'locked';
		return $report;
	}

	try {
		$rollback = lmhg_site_core_rank_math_rollback_journal( $journal );
		$report['journal_id']  = (string) ( $journal['id'] ?? '' );
		$report['rolled_back'] = $rollback['rolled_back'];
		$report['conflicts']   = $rollback['conflicts'];
		if ( $rollback['conflicts'] > 0 ) {
			$report['complete'] = lmhg_site_core_rank_math_handoff_complete();
			$report['blocked']  = true;
			$report['reason']   = 'rollback_blocked';
			return $report;
		}

		delete_option( LMHG_SITE_CORE_RANK_MATH_HANDOFF_OPTION );
		$report['complete'] = false;
		$report['reason']   = 'rolled_back';
		return $report;
	} finally {
		lmhg_site_core_rank_math_release_lock( $lock_token );
	}
}

/** Returns the integration state used by the SEO Overview. */
function lmhg_site_core_rank_math_handoff_state(): array {
	$active   = lmhg_site_core_rank_math_is_active();
	$pro      = lmhg_site_core_rank_math_pro_is_active();
	$complete = lmhg_site_core_rank_math_handoff_complete();
	$journal  = get_option( LMHG_SITE_CORE_RANK_MATH_JOURNAL_OPTION, array() );
	$preview  = lmhg_site_core_rank_math_empty_handoff_report();
	$status   = 'not-installed';

	if ( $active && ! $pro ) {
		$status = 'pro-required';
	} elseif ( $active && $pro && ! $complete ) {
		$preview = lmhg_site_core_rank_math_handoff( false );
		$status  = $preview['conflicts'] > 0 ? 'conflicts' : 'ready';
	} elseif ( $active && $complete ) {
		$status = 'complete';
	} elseif ( ! $active && $complete ) {
		$status = 'fallback-active';
	}
	if ( is_array( $journal ) && 'rollback_blocked' === (string) ( $journal['status'] ?? '' ) ) {
		$status = 'rollback-blocked';
	}

	return array(
		'active'         => $active,
		'pro_active'     => $pro,
		'complete'       => $complete,
		'owner'          => lmhg_site_core_rank_math_owns_standard_seo() ? 'Rank Math' : 'LMHG',
		'status'         => $status,
		'preview'        => $preview,
		'journal_status' => is_array( $journal ) ? (string) ( $journal['status'] ?? '' ) : '',
		'journal_id'     => is_array( $journal ) ? (string) ( $journal['id'] ?? '' ) : '',
	);
}

/** Builds one page's Rank Math metadata mapping. */
function lmhg_site_core_rank_math_mapping_for_page( int $post_id ): array {
	$primary   = trim( (string) get_post_meta( $post_id, '_lmhg_primary_keyword', true ) );
	$secondary = json_decode( (string) get_post_meta( $post_id, '_lmhg_secondary_keywords', true ), true );
	$secondary = is_array( $secondary ) ? $secondary : array();
	$keywords  = lmhg_site_core_unique_keywords( array_merge( array( $primary ), $secondary ) );
	$robots    = '1' === (string) get_post_meta( $post_id, '_lmhg_noindex', true ) ? array( 'noindex', 'nofollow' ) : array();

	return array(
		'rank_math_title'          => trim( (string) get_post_meta( $post_id, '_lmhg_seo_title', true ) ),
		'rank_math_description'    => trim( (string) get_post_meta( $post_id, '_lmhg_meta_description', true ) ),
		'rank_math_canonical_url'  => trim( (string) get_post_meta( $post_id, '_lmhg_canonical_url', true ) ),
		'rank_math_robots'         => $robots,
		'rank_math_focus_keyword'  => implode( ',', $keywords ),
	);
}

/** De-duplicates keywords while preserving primary-first order. */
function lmhg_site_core_unique_keywords( array $keywords ): array {
	$unique = array();
	$seen   = array();
	foreach ( $keywords as $keyword ) {
		$keyword = trim( sanitize_text_field( (string) $keyword ) );
		$key     = function_exists( 'mb_strtolower' ) ? mb_strtolower( $keyword ) : strtolower( $keyword );
		if ( '' === $keyword || isset( $seen[ $key ] ) ) {
			continue;
		}
		$seen[ $key ] = true;
		$unique[]     = $keyword;
	}
	return $unique;
}

/** Applies the administrator-confirmed one-time handoff. */
function lmhg_site_core_handle_rank_math_handoff(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to run the Rank Math handoff.', 'lmhg-site-core' ) );
	}
	check_admin_referer( 'lmhg_rank_math_handoff', 'lmhg_rank_math_nonce' );
	$report = lmhg_site_core_rank_math_handoff( true );
	wp_safe_redirect(
		add_query_arg(
			array(
				'page'      => 'lmhg-seo-overview',
				'handoff'   => $report['complete'] ? 'complete' : 'blocked',
				'mapped'    => (int) $report['mapped'],
				'writes'    => (int) $report['writes'],
				'conflicts' => (int) $report['conflicts'],
				'reason'    => sanitize_key( (string) $report['reason'] ),
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}

/** Rolls back the journaled handoff after administrator confirmation. */
function lmhg_site_core_handle_rank_math_handoff_rollback(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to roll back the Rank Math handoff.', 'lmhg-site-core' ) );
	}
	check_admin_referer( 'lmhg_rank_math_handoff_rollback', 'lmhg_rank_math_rollback_nonce' );
	$report = lmhg_site_core_rank_math_handoff_rollback();
	wp_safe_redirect(
		add_query_arg(
			array(
				'page'        => 'lmhg-seo-overview',
				'rollback'    => $report['blocked'] ? 'blocked' : 'complete',
				'rolled_back' => (int) $report['rolled_back'],
				'conflicts'   => (int) $report['conflicts'],
				'reason'      => sanitize_key( (string) $report['reason'] ),
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}
