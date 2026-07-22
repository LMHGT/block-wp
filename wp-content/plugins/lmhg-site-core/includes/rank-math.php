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
const LMHG_SITE_CORE_RANK_MATH_KEYWORD_SYNC_OPTION  = 'lmhg_rank_math_keyword_sync_version';
const LMHG_SITE_CORE_RANK_MATH_KEYWORD_SYNC_VERSION = '2026-07-16-seo-decision-lab-metadata-v3';
const LMHG_SITE_CORE_RANK_MATH_KEYWORD_REPORT_OPTION = 'lmhg_rank_math_keyword_sync_report';
const LMHG_SITE_CORE_RANK_MATH_SITEMAP_SYNC_OPTION = 'lmhg_rank_math_sitemap_sync_version';
const LMHG_SITE_CORE_RANK_MATH_SITEMAP_SYNC_VERSION = '2026-07-22-page-and-post-inventory-v3';
const LMHG_SITE_CORE_RANK_MATH_REWRITE_OPTION = 'lmhg_rank_math_rewrite_version';
const LMHG_SITE_CORE_RANK_MATH_REWRITE_VERSION = '2026-07-21-sitemap-handoff-v2';
const LMHG_SITE_CORE_RANK_MATH_PAGE_SCHEMA_OPTION = 'lmhg_rank_math_page_schema_default_version';
const LMHG_SITE_CORE_RANK_MATH_PAGE_SCHEMA_VERSION = '2026-07-22-page-schema-default-off-v1';
const LMHG_SITE_CORE_RANK_MATH_PAGE_SCHEMA_REPORT = 'lmhg_rank_math_page_schema_default_report';
const LMHG_SITE_CORE_RANK_MATH_SETTINGS_CLEANUP_OPTION = 'lmhg_rank_math_settings_cleanup_version';
const LMHG_SITE_CORE_RANK_MATH_SETTINGS_CLEANUP_VERSION = '2026-07-22-high-confidence-settings-v1';
const LMHG_SITE_CORE_RANK_MATH_SETTINGS_CLEANUP_REPORT = 'lmhg_rank_math_settings_cleanup_report';

add_action( 'init', 'lmhg_site_core_configure_rank_math_integration', 99 );
add_action( 'init', 'lmhg_site_core_sync_rank_math_sitemap_inventory', 100 );
add_action( 'init', 'lmhg_site_core_sync_canonical_rank_math_keywords', 101 );
add_action( 'init', 'lmhg_site_core_refresh_rank_math_rewrites', 102 );
add_action( 'init', 'lmhg_site_core_sync_rank_math_page_schema_default', 103 );
add_action( 'init', 'lmhg_site_core_sync_rank_math_settings_cleanup', 104 );
add_action( 'admin_post_lmhg_rank_math_handoff', 'lmhg_site_core_handle_rank_math_handoff' );
add_action( 'admin_post_lmhg_rank_math_handoff_rollback', 'lmhg_site_core_handle_rank_math_handoff_rollback' );
add_action( 'enqueue_block_editor_assets', 'lmhg_site_core_enqueue_rank_math_content_bridge', 99 );
add_filter( 'rank_math/frontend/disable_integration', 'lmhg_site_core_rank_math_disable_pending_frontend', PHP_INT_MAX );
add_filter( 'rank_math/modules', 'lmhg_site_core_rank_math_limit_pending_modules', PHP_INT_MAX );

/** Keeps Rank Math's sitemap aligned with LMHG's Page-based public inventory. */
function lmhg_site_core_sync_rank_math_sitemap_inventory(): void {
	if ( ! lmhg_site_core_rank_math_owns_standard_seo() ) {
		return;
	}
	if ( LMHG_SITE_CORE_RANK_MATH_SITEMAP_SYNC_VERSION === (string) get_option( LMHG_SITE_CORE_RANK_MATH_SITEMAP_SYNC_OPTION, '' ) ) {
		return;
	}

	$settings = get_option( 'rank-math-options-sitemap', array() );
	if ( ! is_array( $settings ) ) {
		return;
	}
	$current_post_sitemap = $settings['pt_post_sitemap'] ?? null;
	if ( ! in_array( $current_post_sitemap, array( 'off', 'on' ), true ) ) {
		return;
	}
	$settings['pt_page_sitemap']      = 'on';
	if ( 'off' === $current_post_sitemap ) {
		$settings['pt_post_sitemap'] = 'on';
	}
	$settings['authors_sitemap']      = 'off';
	$settings['tax_category_sitemap'] = 'off';
	$settings['tax_post_tag_sitemap'] = 'off';
	update_option( 'rank-math-options-sitemap', $settings, false );
	if ( class_exists( '\\RankMath\\Sitemap\\Cache' ) ) {
		\RankMath\Sitemap\Cache::invalidate_storage();
	}

	$stored = get_option( 'rank-math-options-sitemap', array() );
	if ( is_array( $stored ) && 'on' === (string) ( $stored['pt_page_sitemap'] ?? '' ) && 'on' === (string) ( $stored['pt_post_sitemap'] ?? '' ) && 'off' === (string) ( $stored['tax_category_sitemap'] ?? '' ) ) {
		update_option( LMHG_SITE_CORE_RANK_MATH_SITEMAP_SYNC_OPTION, LMHG_SITE_CORE_RANK_MATH_SITEMAP_SYNC_VERSION, false );
	}
}

/** Returns only the Rank Math values governed by the cleanup migration. */
function lmhg_site_core_rank_math_settings_cleanup_snapshot( array $settings ): array {
	$paths = array(
		'titles' => array(
			'knowledgegraph_type',
			'disable_author_archives',
			'tax_category_custom_robots',
			'tax_category_robots',
			'noindex_password_protected',
			'pt_post_default_rich_snippet',
			'pt_post_default_article_type',
		),
		'general' => array( 'new_window_external_links', 'llms_post_types' ),
		'sitemap' => array( 'pt_page_sitemap', 'pt_post_sitemap', 'pt_product_sitemap', 'html_sitemap', 'tax_category_sitemap' ),
	);
	$snapshot = array();
	foreach ( $paths as $group => $keys ) {
		$snapshot[ $group ] = array();
		if ( ! isset( $settings[ $group ] ) || ! is_array( $settings[ $group ] ) ) {
			continue;
		}
		foreach ( $keys as $key ) {
			$snapshot[ $group ][ $key ] = $settings[ $group ][ $key ] ?? null;
		}
	}
	$snapshot['modules'] = isset( $settings['modules'] ) && is_array( $settings['modules'] ) ? $settings['modules'] : null;
	return $snapshot;
}

/**
 * Produces a conflict-safe, side-effect-free Rank Math cleanup plan.
 *
 * A governed scalar changes only from its audited value to the approved value.
 * Already-correct values are no-ops; any third value is retained and reported.
 */
function lmhg_site_core_rank_math_settings_cleanup_plan( array $settings ): array {
	$after     = $settings;
	$changed   = array();
	$conflicts = array();
	$failures  = array();
	$rules     = array(
		'titles' => array(
			'knowledgegraph_type'        => array( 'person', 'company' ),
			'disable_author_archives'    => array( 'off', 'on' ),
			'tax_category_custom_robots' => array( 'off', 'on' ),
			'tax_category_robots'        => array( array( 'index' ), array( 'noindex' ) ),
			'noindex_password_protected' => array( 'off', 'on' ),
		),
		'general' => array(
			'new_window_external_links' => array( 'on', 'off' ),
		),
		'sitemap' => array(
			'pt_post_sitemap'    => array( 'off', 'on' ),
			'pt_product_sitemap' => array( 'on', 'off' ),
			'html_sitemap'       => array( 'on', 'off' ),
		),
	);

	foreach ( $rules as $group => $group_rules ) {
		if ( ! isset( $after[ $group ] ) || ! is_array( $after[ $group ] ) ) {
			$failures[] = $group . '_settings_unavailable';
			continue;
		}
		foreach ( $group_rules as $key => $values ) {
			$path    = $group . '.' . $key;
			$current = $after[ $group ][ $key ] ?? null;
			if ( $values[1] === $current ) {
				continue;
			}
			if ( $values[0] !== $current ) {
				$conflicts[] = $path;
				continue;
			}
			$after[ $group ][ $key ] = $values[1];
			$changed[]                = $path;
		}
	}

	if ( ! isset( $after['modules'] ) || ! is_array( $after['modules'] ) ) {
		$failures[] = 'modules_settings_unavailable';
	} else {
		$unused_modules = array( 'woocommerce', 'buddypress', 'bbpress', 'acf', 'web-stories', 'image-seo' );
		$modules        = $after['modules'];
		foreach ( $unused_modules as $module ) {
			foreach ( $modules as $key => $candidate ) {
				if ( $module !== $candidate ) {
					continue;
				}
				unset( $modules[ $key ] );
				$changed[] = 'modules.' . $module;
			}
		}
		$after['modules'] = $modules;
	}

	return array(
		'before'   => lmhg_site_core_rank_math_settings_cleanup_snapshot( $settings ),
		'after'    => $after,
		'changed'  => $changed,
		'conflicts' => $conflicts,
		'failures' => $failures,
	);
}

/** Produces the exact-match metadata portion of the Rank Math cleanup plan. */
function lmhg_site_core_rank_math_metadata_cleanup_plan( array $metadata ): array {
	$after     = $metadata;
	$changed   = array();
	$conflicts = array();
	$title_old = 'Parent Child Parent-Child Attachment Therapy Louisville KY';
	$title_new = 'Parent-Child Attachment Therapy Louisville KY | LMHG';
	$title_keys = array( '_lmhg_seo_title', 'rank_math_title' );

	foreach ( $title_keys as $key ) {
		$path  = 'metadata.attachment-therapy.' . $key;
		$entry = $after['attachment-therapy'][ $key ] ?? array( 'exists' => false, 'value' => '' );
		if ( true === (bool) ( $entry['exists'] ?? false ) && $title_new === (string) ( $entry['value'] ?? '' ) ) {
			continue;
		}
		if ( true !== (bool) ( $entry['exists'] ?? false ) || $title_old !== (string) ( $entry['value'] ?? '' ) ) {
			$conflicts[] = $path;
			continue;
		}
		$after['attachment-therapy'][ $key ] = array( 'exists' => true, 'value' => $title_new );
		$changed[] = $path;
	}

	$canonical_path  = 'metadata.specialties._lmhg_canonical_url';
	$canonical_entry = $after['specialties']['_lmhg_canonical_url'] ?? array( 'exists' => false, 'value' => '' );
	if ( true === (bool) ( $canonical_entry['exists'] ?? false ) ) {
		if ( 'http://100.70.222.25:8093/specialties/' === (string) ( $canonical_entry['value'] ?? '' ) ) {
			$after['specialties']['_lmhg_canonical_url'] = array( 'exists' => false, 'value' => '' );
			$changed[] = $canonical_path;
		} else {
			$conflicts[] = $canonical_path;
		}
	}

	return array(
		'before'    => $metadata,
		'after'     => $after,
		'changed'   => $changed,
		'conflicts' => $conflicts,
		'failures'  => array(),
	);
}

/** Reads only the page metadata governed by the cleanup migration. */
function lmhg_site_core_rank_math_metadata_cleanup_state(): array {
	$specs = array(
		'attachment-therapy' => array( '_lmhg_seo_title', 'rank_math_title' ),
		'specialties'        => array( '_lmhg_canonical_url' ),
	);
	$metadata = array();
	$post_ids = array();
	$failures = array();
	foreach ( $specs as $slug => $keys ) {
		$post = get_page_by_path( $slug, OBJECT, 'page' );
		if ( ! $post instanceof WP_Post ) {
			$failures[] = 'metadata_page_not_found:' . $slug;
			continue;
		}
		$post_ids[ $slug ] = (int) $post->ID;
		foreach ( $keys as $key ) {
			$exists = metadata_exists( 'post', (int) $post->ID, $key );
			$metadata[ $slug ][ $key ] = array(
				'exists' => $exists,
				'value'  => $exists ? get_post_meta( (int) $post->ID, $key, true ) : '',
			);
		}
	}
	return array( 'metadata' => $metadata, 'post_ids' => $post_ids, 'failures' => $failures );
}

/** Applies the approved high-confidence Rank Math cleanup exactly once. */
function lmhg_site_core_sync_rank_math_settings_cleanup(): void {
	if (
		! lmhg_site_core_rank_math_is_active()
		|| LMHG_SITE_CORE_RANK_MATH_SETTINGS_CLEANUP_VERSION === (string) get_option( LMHG_SITE_CORE_RANK_MATH_SETTINGS_CLEANUP_OPTION, '' )
	) {
		return;
	}

	$option_names = array(
		'titles'  => 'rank-math-options-titles',
		'general' => 'rank-math-options-general',
		'sitemap' => 'rank-math-options-sitemap',
		'modules' => 'rank_math_modules',
	);
	$settings = array();
	foreach ( $option_names as $group => $option_name ) {
		$settings[ $group ] = get_option( $option_name, null );
	}

	$plan           = lmhg_site_core_rank_math_settings_cleanup_plan( $settings );
	$metadata_state = lmhg_site_core_rank_math_metadata_cleanup_state();
	$metadata_plan  = lmhg_site_core_rank_math_metadata_cleanup_plan( $metadata_state['metadata'] );
	$report = array(
		'version'      => LMHG_SITE_CORE_RANK_MATH_SETTINGS_CLEANUP_VERSION,
		'completed_at' => '',
		'before'       => array( 'settings' => $plan['before'], 'metadata' => $metadata_plan['before'] ),
		'after'        => array( 'settings' => lmhg_site_core_rank_math_settings_cleanup_snapshot( $plan['after'] ), 'metadata' => $metadata_plan['after'] ),
		'changed'      => array_merge( $plan['changed'], $metadata_plan['changed'] ),
		'conflicts'    => array_merge( $plan['conflicts'], $metadata_plan['conflicts'] ),
		'failures'     => array_merge( $plan['failures'], $metadata_plan['failures'], $metadata_state['failures'] ),
	);
	if ( ! empty( $report['conflicts'] ) || ! empty( $report['failures'] ) ) {
		update_option( LMHG_SITE_CORE_RANK_MATH_SETTINGS_CLEANUP_REPORT, $report, false );
		return;
	}

	$changed_groups = array();
	foreach ( $plan['changed'] as $path ) {
		$changed_groups[ strstr( $path, '.', true ) ] = true;
	}
	foreach ( array_keys( $changed_groups ) as $group ) {
		if ( 'metadata' === $group ) {
			continue;
		}
		update_option( $option_names[ $group ], $plan['after'][ $group ], false );
	}
	if ( isset( $changed_groups['sitemap'] ) && class_exists( '\\RankMath\\Sitemap\\Cache' ) ) {
		\RankMath\Sitemap\Cache::invalidate_storage();
	}
	foreach ( $metadata_plan['changed'] as $path ) {
		[, $slug, $key] = explode( '.', $path, 3 );
		$post_id         = (int) $metadata_state['post_ids'][ $slug ];
		$entry           = $metadata_plan['after'][ $slug ][ $key ];
		if ( true === (bool) $entry['exists'] ) {
			update_post_meta( $post_id, $key, $entry['value'] );
		} else {
			delete_post_meta( $post_id, $key );
		}
	}

	$stored = array();
	foreach ( $option_names as $group => $option_name ) {
		$stored[ $group ] = get_option( $option_name, null );
	}
	$stored_metadata = lmhg_site_core_rank_math_metadata_cleanup_state();
	$report['after'] = array(
		'settings' => lmhg_site_core_rank_math_settings_cleanup_snapshot( $stored ),
		'metadata' => $stored_metadata['metadata'],
	);
	if ( $stored !== $plan['after'] || $stored_metadata['metadata'] !== $metadata_plan['after'] ) {
		$report['failures'][] = 'cleanup_not_persisted';
		update_option( LMHG_SITE_CORE_RANK_MATH_SETTINGS_CLEANUP_REPORT, $report, false );
		return;
	}

	$report['completed_at'] = gmdate( 'c' );
	update_option( LMHG_SITE_CORE_RANK_MATH_SETTINGS_CLEANUP_REPORT, $report, false );
	if ( $report !== get_option( LMHG_SITE_CORE_RANK_MATH_SETTINGS_CLEANUP_REPORT, array() ) ) {
		return;
	}
	update_option( LMHG_SITE_CORE_RANK_MATH_SETTINGS_CLEANUP_OPTION, LMHG_SITE_CORE_RANK_MATH_SETTINGS_CLEANUP_VERSION, false );
	if ( LMHG_SITE_CORE_RANK_MATH_SETTINGS_CLEANUP_VERSION !== (string) get_option( LMHG_SITE_CORE_RANK_MATH_SETTINGS_CLEANUP_OPTION, '' ) ) {
		$report['completed_at'] = '';
		$report['failures'][]   = 'completion_marker_not_persisted';
		update_option( LMHG_SITE_CORE_RANK_MATH_SETTINGS_CLEANUP_REPORT, $report, false );
		return;
	}
}

/** Refreshes rewrites once after Rank Math's public sitemap module takes ownership. */
function lmhg_site_core_refresh_rank_math_rewrites(): void {
	if ( ! lmhg_site_core_rank_math_owns_standard_seo() ) {
		return;
	}
	if ( LMHG_SITE_CORE_RANK_MATH_REWRITE_VERSION === (string) get_option( LMHG_SITE_CORE_RANK_MATH_REWRITE_OPTION, '' ) ) {
		return;
	}

	flush_rewrite_rules( false );
	update_option( LMHG_SITE_CORE_RANK_MATH_REWRITE_OPTION, LMHG_SITE_CORE_RANK_MATH_REWRITE_VERSION, false );
}

/**
 * Disables Rank Math's post-type-wide Article placeholder for WordPress Pages.
 *
 * LMHG applies each Page's semantic type to the base graph entity at render
 * time. Conventional article templates remain Article through that explicit
 * mapping instead of making every Page begin as an Article rich snippet.
 */
function lmhg_site_core_sync_rank_math_page_schema_default(): void {
	if (
		! lmhg_site_core_rank_math_is_active()
		|| LMHG_SITE_CORE_RANK_MATH_PAGE_SCHEMA_VERSION === (string) get_option( LMHG_SITE_CORE_RANK_MATH_PAGE_SCHEMA_OPTION, '' )
	) {
		return;
	}

	$settings = get_option( 'rank-math-options-titles', null );
	$report   = array(
		'version'      => LMHG_SITE_CORE_RANK_MATH_PAGE_SCHEMA_VERSION,
		'completed_at' => '',
		'before'       => is_array( $settings ) ? (string) ( $settings['pt_page_default_rich_snippet'] ?? '' ) : '',
		'after'        => '',
		'changed'      => false,
		'conflicts'    => array(),
		'failures'     => array(),
	);
	if ( ! is_array( $settings ) ) {
		$report['failures'][] = 'rank_math_titles_settings_unavailable';
		update_option( LMHG_SITE_CORE_RANK_MATH_PAGE_SCHEMA_REPORT, $report, false );
		return;
	}

	$current = strtolower( trim( (string) ( $settings['pt_page_default_rich_snippet'] ?? '' ) ) );
	if ( ! in_array( $current, array( 'article', 'off' ), true ) ) {
		$report['after']       = (string) ( $settings['pt_page_default_rich_snippet'] ?? '' );
		$report['conflicts'][] = 'unexpected_page_schema_default';
		update_option( LMHG_SITE_CORE_RANK_MATH_PAGE_SCHEMA_REPORT, $report, false );
		return;
	}

	if ( 'article' === $current ) {
		$settings['pt_page_default_rich_snippet'] = 'off';
		update_option( 'rank-math-options-titles', $settings, false );
		$report['changed'] = true;
	} elseif ( 'off' !== (string) ( $settings['pt_page_default_rich_snippet'] ?? '' ) ) {
		$settings['pt_page_default_rich_snippet'] = 'off';
		update_option( 'rank-math-options-titles', $settings, false );
		$report['changed'] = true;
	}

	$stored          = get_option( 'rank-math-options-titles', null );
	$report['after'] = is_array( $stored ) ? (string) ( $stored['pt_page_default_rich_snippet'] ?? '' ) : '';
	if ( 'off' !== $report['after'] ) {
		$report['failures'][] = 'page_schema_default_not_persisted';
		update_option( LMHG_SITE_CORE_RANK_MATH_PAGE_SCHEMA_REPORT, $report, false );
		return;
	}

	$report['completed_at'] = gmdate( 'c' );
	update_option( LMHG_SITE_CORE_RANK_MATH_PAGE_SCHEMA_OPTION, LMHG_SITE_CORE_RANK_MATH_PAGE_SCHEMA_VERSION, false );
	update_option( LMHG_SITE_CORE_RANK_MATH_PAGE_SCHEMA_REPORT, $report, false );
}

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
	if ( function_exists( 'lmhg_site_core_page_graphic_markup_for_post' ) ) {
		$sections[] = lmhg_site_core_page_graphic_markup_for_post( $post );
	}
	if (
		function_exists( 'lmhg_site_core_render_related_articles' )
		&& in_array( get_page_template_slug( $post ), array( 'service-page', 'specialty-page' ), true )
	) {
		$sections[] = lmhg_site_core_render_related_articles( $post_id, 'Helpful Articles', 3 );
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
	add_filter( 'rank_math/snippet/rich_snippet_article_entity', 'lmhg_site_core_rank_math_article_entity', 90 );
	add_filter( 'rank_math/json_ld', 'lmhg_site_core_extend_rank_math_json_ld', 90, 2 );
	add_filter( 'rank_math/json_ld', 'lmhg_site_core_finalize_rank_math_page_schema', 999, 2 );
	add_filter( 'rank_math/sitemap/exclude_post_type', 'lmhg_site_core_rank_math_exclude_sitemap_post_type', 90, 2 );
	add_filter( 'rank_math/sitemap/exclude_taxonomy', '__return_true', 90 );
}

/** Keeps the Rank Math XML sitemap limited to canonical Pages and Posts. */
function lmhg_site_core_rank_math_exclude_sitemap_post_type( bool $exclude, string $post_type ): bool {
	return $exclude || ! in_array( $post_type, array( 'page', 'post' ), true );
}

/** Keeps Rank Math's post-type-wide Article default only on article templates. */
function lmhg_site_core_rank_math_article_entity( array $entity ): array {
	$post_id = is_singular() ? (int) get_queried_object_id() : 0;
	if ( $post_id <= 0 || 'page' !== get_post_type( $post_id ) ) {
		return $entity;
	}

	return 'Article' === lmhg_site_core_default_schema_type_for_page( $post_id ) ? $entity : array();
}

/** Applies the LMHG page type after Rank Math connects its graph entities. */
function lmhg_site_core_finalize_rank_math_page_schema( array $data, mixed $json_ld = null ): array {
	unset( $json_ld );
	if ( ! is_singular() ) {
		return $data;
	}
	$post_id = (int) get_queried_object_id();
	if ( $post_id <= 0 || 'page' !== get_post_type( $post_id ) ) {
		return $data;
	}

	$canonical = lmhg_site_core_current_canonical_url();
	$schema_type = trim( (string) get_post_meta( $post_id, '_lmhg_schema_type', true ) );
	$schema_type = '' !== $schema_type ? $schema_type : lmhg_site_core_default_schema_type_for_page( $post_id );
	$webpage_id        = untrailingslashit( $canonical ) . '/#webpage';
	$has_article_node  = false;
	$has_webpage_node  = false;
	foreach ( $data as $node ) {
		if ( ! is_array( $node ) ) {
			continue;
		}
		$types = isset( $node['@type'] ) && is_array( $node['@type'] ) ? $node['@type'] : array( $node['@type'] ?? '' );
		if ( in_array( 'Article', $types, true ) && str_ends_with( (string) ( $node['@id'] ?? '' ), '/#richSnippet' ) ) {
			$has_article_node = true;
		}
	}

	foreach ( $data as $key => $node ) {
		if ( ! is_array( $node ) ) {
			continue;
		}
		$types = isset( $node['@type'] ) && is_array( $node['@type'] ) ? $node['@type'] : array( $node['@type'] ?? '' );
		if (
			'Article' !== $schema_type
			&& in_array( 'Article', $types, true )
			&& str_ends_with( (string) ( $node['@id'] ?? '' ), '/#richSnippet' )
		) {
			unset( $data[ $key ] );
			continue;
		}
		if ( $webpage_id !== (string) ( $node['@id'] ?? '' ) ) {
			continue;
		}

		$has_webpage_node = true;
		if ( 'Article' === $schema_type ) {
			continue;
		}
		$data[ $key ]['@type'] = in_array( 'FAQPage', $types, true ) && 'FAQPage' !== $schema_type
			? array( $schema_type, 'FAQPage' )
			: $schema_type;
	}

	if ( ! $has_webpage_node ) {
		$base_type = 'Article' === $schema_type ? 'WebPage' : $schema_type;
		$data['lmhg_webpage'] = lmhg_site_core_rank_math_base_page_entity( $post_id, $canonical, $base_type );
	}
	if ( 'Article' === $schema_type && ! $has_article_node ) {
		$data['lmhg_article'] = lmhg_site_core_rank_math_article_entity_for_page( $post_id, $canonical, $data );
	}
	return $data;
}

/** Builds a minimal canonical base entity if Rank Math omitted its WebPage. */
function lmhg_site_core_rank_math_base_page_entity( int $post_id, string $canonical, string $schema_type ): array {
	$title = wp_strip_all_tags( (string) get_the_title( $post_id ) );
	$node  = array(
		'@type'    => $schema_type,
		'@id'      => untrailingslashit( $canonical ) . '/#webpage',
		'url'      => $canonical,
		'name'     => $title,
		'isPartOf' => array( '@id' => home_url( '/#website' ) ),
	);
	return $node;
}

/** Builds the explicit Article entity used only by LMHG article templates. */
function lmhg_site_core_rank_math_article_entity_for_page( int $post_id, string $canonical, array $graph ): array {
	$webpage_id = untrailingslashit( $canonical ) . '/#webpage';
	$title      = function_exists( 'lmhg_site_core_resolved_seo_title_for_post' )
		? lmhg_site_core_resolved_seo_title_for_post( $post_id )
		: wp_strip_all_tags( (string) get_the_title( $post_id ) );
	$article    = array(
		'@type'            => 'Article',
		'@id'              => untrailingslashit( $canonical ) . '/#richSnippet',
		'url'              => $canonical,
		'headline'         => $title,
		'name'             => $title,
		'isPartOf'         => array( '@id' => $webpage_id ),
		'mainEntityOfPage' => array( '@id' => $webpage_id ),
	);
	if ( function_exists( 'lmhg_site_core_resolved_meta_description_for_post' ) ) {
		$description = lmhg_site_core_resolved_meta_description_for_post( $post_id );
		if ( '' !== $description ) {
			$article['description'] = $description;
		}
	}
	$keywords = trim( (string) get_post_meta( $post_id, 'rank_math_focus_keyword', true ) );
	if ( '' !== $keywords ) {
		$article['keywords'] = $keywords;
	}
	$language = trim( (string) get_bloginfo( 'language' ) );
	if ( '' !== $language ) {
		$article['inLanguage'] = $language;
	}

	$author_id    = '';
	$publisher_id = '';
	foreach ( $graph as $node ) {
		if ( ! is_array( $node ) || empty( $node['@id'] ) ) {
			continue;
		}
		$types = isset( $node['@type'] ) && is_array( $node['@type'] ) ? $node['@type'] : array( $node['@type'] ?? '' );
		if ( '' === $author_id && array( 'Person' ) === $types ) {
			$author_id = (string) $node['@id'];
		}
		if ( '' === $publisher_id && in_array( 'Organization', $types, true ) ) {
			$publisher_id = (string) $node['@id'];
		}
	}
	if ( '' !== $author_id ) {
		$article['author'] = array( '@id' => $author_id );
	}
	if ( '' !== $publisher_id ) {
		$article['publisher'] = array( '@id' => $publisher_id );
	}
	$published = (string) get_the_date( DATE_W3C, $post_id );
	$modified  = (string) get_the_modified_date( DATE_W3C, $post_id );
	if ( '' !== $published ) {
		$article['datePublished'] = $published;
	}
	if ( '' !== $modified ) {
		$article['dateModified'] = $modified;
	}
	return $article;
}

/** Adds LMHG's Service, FAQ, and Review entities to Rank Math's graph. */
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
	$canonical = trim( (string) get_post_meta( $post_id, '_lmhg_canonical_url', true ) );
	if ( '' !== $canonical ) {
		$canonical_path = lmhg_site_core_normalize_redirect_path( (string) wp_parse_url( $canonical, PHP_URL_PATH ) );
		$permalink_path = lmhg_site_core_normalize_redirect_path( (string) wp_parse_url( get_permalink( $post_id ), PHP_URL_PATH ) );
		if ( '' !== $canonical_path && $canonical_path === $permalink_path ) {
			$canonical = '';
		}
	}

	return array(
		'rank_math_title'          => trim( (string) get_post_meta( $post_id, '_lmhg_seo_title', true ) ),
		'rank_math_description'    => trim( (string) get_post_meta( $post_id, '_lmhg_meta_description', true ) ),
		'rank_math_canonical_url'  => $canonical,
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

/**
 * Promotes the active theme's canonical SEO Decision Lab metadata once.
 *
 * Existing Rank Math values are changed only when they are blank or still
 * match the previous LMHG-derived value. This keeps later manual edits intact.
 */
function lmhg_site_core_sync_canonical_rank_math_keywords(): void {
	if ( LMHG_SITE_CORE_RANK_MATH_KEYWORD_SYNC_VERSION === (string) get_option( LMHG_SITE_CORE_RANK_MATH_KEYWORD_SYNC_OPTION, '' ) ) {
		return;
	}
	if ( ! function_exists( 'lmhg_site_core_topology_page_data' ) || ! function_exists( 'lmhg_site_core_find_published_topology_page' ) ) {
		return;
	}

	$page_data = lmhg_site_core_topology_page_data();
	if ( empty( $page_data ) ) {
		return;
	}

	$report = array(
		'version'                  => LMHG_SITE_CORE_RANK_MATH_KEYWORD_SYNC_VERSION,
		'status'                   => 'pending',
		'eligible'                 => 0,
		'metadata_eligible'        => 0,
		'lmhg_updates'             => 0,
		'lmhg_title_updates'       => 0,
		'lmhg_description_updates' => 0,
		'rank_updates'             => 0,
		'rank_title_updates'       => 0,
		'rank_description_updates' => 0,
		'scores_cleared'           => 0,
		'missing_pages'            => array(),
		'invalid_items'            => array(),
		'conflicts'                => array(),
		'completed_at'             => '',
	);

	foreach ( $page_data as $path => $entry ) {
		$seo         = isset( $entry['seo'] ) && is_array( $entry['seo'] ) ? $entry['seo'] : array();
		$title       = trim( sanitize_text_field( (string) ( $seo['title'] ?? '' ) ) );
		$description = trim( sanitize_textarea_field( (string) ( $seo['description'] ?? '' ) ) );
		$primary     = trim( sanitize_text_field( (string) ( $seo['primaryKeyword'] ?? '' ) ) );
		$secondary   = isset( $seo['secondaryKeywords'] ) && is_array( $seo['secondaryKeywords'] ) ? $seo['secondaryKeywords'] : array();
		$keywords    = lmhg_site_core_unique_keywords( array_merge( array( $primary ), $secondary ) );
		if ( '' === $primary ) {
			continue;
		}

		++$report['eligible'];
		if ( '' !== $title || '' !== $description ) {
			++$report['metadata_eligible'];
		}
		if ( count( $keywords ) !== count( array_filter( array_merge( array( $primary ), $secondary ), static fn( mixed $keyword ): bool => '' !== trim( (string) $keyword ) ) ) ) {
			$report['invalid_items'][] = array( 'path' => $path, 'reason' => 'duplicate_or_empty_keyword' );
			continue;
		}
		if ( array_filter( $keywords, static fn( string $keyword ): bool => str_contains( $keyword, ',' ) ) ) {
			$report['invalid_items'][] = array( 'path' => $path, 'reason' => 'keyword_contains_delimiter' );
			continue;
		}

		if ( '/' === $path ) {
			$front_page_id = (int) get_option( 'page_on_front', 0 );
			$page          = $front_page_id > 0 ? get_post( $front_page_id ) : null;
			if ( ! $page instanceof WP_Post || 'page' !== $page->post_type || 'publish' !== $page->post_status ) {
				$page = null;
			}
		} else {
			$page = lmhg_site_core_find_published_topology_page( $path );
		}
		if ( ! $page instanceof WP_Post ) {
			$report['missing_pages'][] = $path;
			continue;
		}

		$post_id              = (int) $page->ID;
		$previous_title       = trim( (string) get_post_meta( $post_id, '_lmhg_seo_title', true ) );
		$previous_description = trim( (string) get_post_meta( $post_id, '_lmhg_meta_description', true ) );
		$previous_primary     = trim( (string) get_post_meta( $post_id, '_lmhg_primary_keyword', true ) );
		$previous_secondary   = json_decode( (string) get_post_meta( $post_id, '_lmhg_secondary_keywords', true ), true );
		$previous_secondary   = is_array( $previous_secondary ) ? $previous_secondary : array();
		$previous_rank        = implode( ',', lmhg_site_core_unique_keywords( array_merge( array( $previous_primary ), $previous_secondary ) ) );
		$canonical_rank       = implode( ',', $keywords );
		$canonical_secondary  = array_slice( $keywords, 1 );

		if ( '' !== $title && $previous_title !== $title ) {
			update_post_meta( $post_id, '_lmhg_seo_title', $title );
			++$report['lmhg_title_updates'];
		}
		if ( '' !== $description && $previous_description !== $description ) {
			update_post_meta( $post_id, '_lmhg_meta_description', $description );
			++$report['lmhg_description_updates'];
		}

		if ( $previous_primary !== $primary ) {
			update_post_meta( $post_id, '_lmhg_primary_keyword', $primary );
			++$report['lmhg_updates'];
		}
		if ( $previous_secondary !== $canonical_secondary ) {
			update_post_meta( $post_id, '_lmhg_secondary_keywords', wp_slash( wp_json_encode( $canonical_secondary ) ) );
			++$report['lmhg_updates'];
		}

		if ( ! lmhg_site_core_rank_math_is_active() ) {
			continue;
		}

		$rank_changed = false;
		if ( '' !== $title ) {
			$current_rank_title = trim( (string) get_post_meta( $post_id, 'rank_math_title', true ) );
			if ( '' !== $current_rank_title && $current_rank_title !== $previous_title && $current_rank_title !== $title ) {
				$report['conflicts'][] = array( 'path' => $path, 'post_id' => $post_id, 'field' => 'title' );
			} elseif ( $current_rank_title !== $title ) {
				update_post_meta( $post_id, 'rank_math_title', $title );
				++$report['rank_title_updates'];
				$rank_changed = true;
			}
		}
		if ( '' !== $description ) {
			$current_rank_description = trim( (string) get_post_meta( $post_id, 'rank_math_description', true ) );
			if ( '' !== $current_rank_description && $current_rank_description !== $previous_description && $current_rank_description !== $description ) {
				$report['conflicts'][] = array( 'path' => $path, 'post_id' => $post_id, 'field' => 'description' );
			} elseif ( $current_rank_description !== $description ) {
				update_post_meta( $post_id, 'rank_math_description', $description );
				++$report['rank_description_updates'];
				$rank_changed = true;
			}
		}

		$current_rank = trim( (string) get_post_meta( $post_id, 'rank_math_focus_keyword', true ) );
		if ( '' !== $current_rank && $current_rank !== $previous_rank && $current_rank !== $canonical_rank ) {
			$report['conflicts'][] = array( 'path' => $path, 'post_id' => $post_id, 'field' => 'focus_keyword' );
		} elseif ( $current_rank !== $canonical_rank ) {
			update_post_meta( $post_id, 'rank_math_focus_keyword', $canonical_rank );
			++$report['rank_updates'];
			$rank_changed = true;
		}

		if ( $rank_changed && metadata_exists( 'post', $post_id, 'rank_math_seo_score' ) ) {
			delete_post_meta( $post_id, 'rank_math_seo_score' );
			++$report['scores_cleared'];
		}
	}

	$report['completed_at'] = gmdate( 'c' );
	$report['status'] = empty( $report['missing_pages'] ) && empty( $report['invalid_items'] )
		? ( empty( $report['conflicts'] ) ? 'complete' : 'complete_with_conflicts' )
		: 'incomplete';
	update_option( LMHG_SITE_CORE_RANK_MATH_KEYWORD_REPORT_OPTION, $report, false );

	if ( 'incomplete' !== $report['status'] ) {
		update_option( LMHG_SITE_CORE_RANK_MATH_KEYWORD_SYNC_OPTION, LMHG_SITE_CORE_RANK_MATH_KEYWORD_SYNC_VERSION, false );
	}
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
