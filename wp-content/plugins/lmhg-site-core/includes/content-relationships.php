<?php
/**
 * Admin-managed content relationships for LMHG pages, FAQs, articles, and team members.
 *
 * @package LMHGSiteCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const LMHG_SITE_CORE_FAQ_POST_TYPE       = 'lmhg_faq';
const LMHG_SITE_CORE_TEAM_POST_TYPE      = 'lmhg_team_member';
const LMHG_SITE_CORE_SPECIALTY_TAXONOMY  = 'lmhg_specialty';
const LMHG_SITE_CORE_FAQ_SET_TAXONOMY    = 'lmhg_faq_set';
const LMHG_SITE_CORE_RELATED_PAGES_META  = '_lmhg_related_page_ids';
const LMHG_SITE_CORE_ARTICLE_CARD_DESCRIPTION_META = '_lmhg_article_card_description';
const LMHG_SITE_CORE_SPECIALTY_CARD_DESCRIPTION_META = '_lmhg_specialty_card_description';
const LMHG_SITE_CORE_SPECIALTY_ICON_ID_META = '_lmhg_specialty_icon_id';
const LMHG_SITE_CORE_RELATIONSHIP_STYLE  = 'lmhg-site-core-relationships';
const LMHG_SITE_CORE_TEAM_FIRST_META     = '_lmhg_team_first_name';
const LMHG_SITE_CORE_TEAM_LAST_META      = '_lmhg_team_last_name';
const LMHG_SITE_CORE_TEAM_CREDENTIALS    = '_lmhg_team_credentials';
const LMHG_SITE_CORE_TEAM_HEADSHOT_URL   = '_lmhg_team_headshot_url';
const LMHG_SITE_CORE_SPECIALTY_FAQ_SEED_OPTION  = 'lmhg_specialty_placeholder_faq_seed_version';
const LMHG_SITE_CORE_SPECIALTY_FAQ_SEED_VERSION = '2026-07-10-specialty-faq-placeholders-v3';
const LMHG_SITE_CORE_SERVICE_SPECIALTY_SEED_OPTION  = 'lmhg_service_specialty_relationship_seed_version';
const LMHG_SITE_CORE_SERVICE_SPECIALTY_SEED_VERSION = '2026-07-10-service-specialty-taxonomy-v3';
const LMHG_SITE_CORE_RELATED_PAGE_TERM_SEED_OPTION  = 'lmhg_related_page_term_metadata_seed_version';
const LMHG_SITE_CORE_RELATED_PAGE_TERM_SEED_VERSION = '2026-07-05-related-page-term-metadata-v1';
const LMHG_SITE_CORE_IN_HOME_SPECIALTY_CLEANUP_OPTION  = 'lmhg_in_home_specialty_cleanup_version';
const LMHG_SITE_CORE_IN_HOME_SPECIALTY_CLEANUP_VERSION = '2026-07-05-in-home-location-v1';
const LMHG_SITE_CORE_RELATIONSHIP_BLOCK_MIGRATION_OPTION  = 'lmhg_relationship_block_migration_version';
const LMHG_SITE_CORE_RELATIONSHIP_BLOCK_MIGRATION_VERSION = '2026-07-11-native-relationship-blocks-v1';
const LMHG_SITE_CORE_RELATED_PAGE_PRESENTATION_MIGRATION_OPTION  = 'lmhg_related_page_presentation_migration_version';
const LMHG_SITE_CORE_RELATED_PAGE_PRESENTATION_MIGRATION_VERSION = '2026-07-21-contextual-links-v1';
const LMHG_SITE_CORE_ARTICLE_CONTEXTUAL_LINK_MIGRATION_OPTION     = 'lmhg_article_contextual_link_migration_version';
const LMHG_SITE_CORE_ARTICLE_CONTEXTUAL_LINK_MIGRATION_VERSION    = '2026-07-21-article-contextual-links-v1';
const LMHG_SITE_CORE_ARTICLE_CONTEXTUAL_LINK_MIGRATION_REPORT     = 'lmhg_article_contextual_link_migration_report';
const LMHG_SITE_CORE_FAQ_PRESENTATION_MIGRATION_OPTION            = 'lmhg_faq_presentation_migration_version';
const LMHG_SITE_CORE_FAQ_PRESENTATION_MIGRATION_VERSION           = '2026-07-21-native-faq-presentation-v1';
const LMHG_SITE_CORE_FAQ_PRESENTATION_MIGRATION_REPORT            = 'lmhg_faq_presentation_migration_report';

add_action( 'init', 'lmhg_site_core_register_relationship_taxonomies', 8 );
add_action( 'init', 'lmhg_site_core_register_relationship_post_types', 9 );
add_action( 'init', 'lmhg_site_core_register_relationship_meta', 10 );
add_action( 'init', 'lmhg_site_core_register_relationship_blocks', 20 );
add_action( 'init', 'lmhg_site_core_seed_related_page_terms', 28 );
add_action( 'init', 'lmhg_site_core_seed_service_specialty_relationships', 29 );
add_action( 'init', 'lmhg_site_core_seed_specialty_placeholder_faqs', 30 );
add_action( 'init', 'lmhg_site_core_cleanup_in_home_specialty_classification', 31 );
add_action( 'init', 'lmhg_site_core_run_relationship_block_migration', 44 );
add_action( 'init', 'lmhg_site_core_run_related_page_presentation_migration', 45 );
add_action( 'init', 'lmhg_site_core_run_article_contextual_link_migration', 46 );
add_action( 'init', 'lmhg_site_core_run_faq_presentation_migration', 48 );
add_action( 'add_meta_boxes', 'lmhg_site_core_add_relationship_meta_boxes', 10, 2 );
add_action( 'save_post_post', 'lmhg_site_core_save_article_relationship_meta', 10, 2 );
add_action( 'save_post_post', 'lmhg_site_core_save_article_card_description_meta', 10, 2 );
add_action( 'save_post_' . LMHG_SITE_CORE_TEAM_POST_TYPE, 'lmhg_site_core_save_team_member_meta', 10, 2 );
add_action( LMHG_SITE_CORE_SPECIALTY_TAXONOMY . '_add_form_fields', 'lmhg_site_core_add_specialty_card_description_field' );
add_action( LMHG_SITE_CORE_SPECIALTY_TAXONOMY . '_add_form_fields', 'lmhg_site_core_add_specialty_icon_field', 11 );
add_action( LMHG_SITE_CORE_SPECIALTY_TAXONOMY . '_edit_form_fields', 'lmhg_site_core_edit_specialty_card_description_field' );
add_action( LMHG_SITE_CORE_SPECIALTY_TAXONOMY . '_edit_form_fields', 'lmhg_site_core_edit_specialty_icon_field', 11 );
add_action( 'created_' . LMHG_SITE_CORE_SPECIALTY_TAXONOMY, 'lmhg_site_core_save_specialty_card_description_field' );
add_action( 'edited_' . LMHG_SITE_CORE_SPECIALTY_TAXONOMY, 'lmhg_site_core_save_specialty_card_description_field' );
add_action( 'admin_enqueue_scripts', 'lmhg_site_core_enqueue_specialty_icon_admin_assets' );
add_action( 'wp_enqueue_scripts', 'lmhg_site_core_register_relationship_assets' );
add_filter( 'the_content', 'lmhg_site_core_append_relationship_sections', 30 );
add_shortcode( 'lmhg_service_specialties', 'lmhg_site_core_service_specialties_shortcode' );
add_shortcode( 'lmhg_faqs', 'lmhg_site_core_faqs_shortcode' );
add_shortcode( 'lmhg_faq_index', 'lmhg_site_core_faq_index_shortcode' );
add_shortcode( 'lmhg_article_pages', 'lmhg_site_core_article_pages_shortcode' );
add_shortcode( 'lmhg_related_pages', 'lmhg_site_core_related_pages_shortcode' );
add_shortcode( 'lmhg_related_articles', 'lmhg_site_core_related_articles_shortcode' );
add_shortcode( 'lmhg_team', 'lmhg_site_core_team_shortcode' );

/** Registers native server-rendered blocks for taxonomy-driven page relationships. */
function lmhg_site_core_register_relationship_blocks(): void {
	lmhg_site_core_register_relationship_assets();
	$script_path = dirname( __DIR__ ) . '/assets/js/relationship-blocks.js';
	$script_handle = 'lmhg-site-core-relationship-blocks';
	wp_register_script(
		$script_handle,
		plugin_dir_url( dirname( __DIR__ ) . '/lmhg-site-core.php' ) . 'assets/js/relationship-blocks.js',
		array( 'wp-block-editor', 'wp-blocks', 'wp-components', 'wp-data', 'wp-element', 'wp-i18n', 'wp-server-side-render' ),
		is_readable( $script_path ) ? (string) filemtime( $script_path ) : '0.1.0',
		true
	);

	register_block_type(
		'lmhg/faqs',
		array(
			'api_version'     => 3,
			'editor_script'   => $script_handle,
			'editor_style'    => LMHG_SITE_CORE_RELATIONSHIP_STYLE,
			'style'           => LMHG_SITE_CORE_RELATIONSHIP_STYLE,
			'uses_context'    => array( 'postId' ),
			'attributes'      => array(
				'heading' => array( 'type' => 'string', 'default' => 'Common Questions' ),
			),
			'render_callback' => 'lmhg_site_core_render_faqs_block',
		)
	);
}

/** Resolves the current page for a dynamic relationship block. */
function lmhg_site_core_relationship_block_post_id( ?WP_Block $block = null ): int {
	$post_id = $block instanceof WP_Block ? (int) ( $block->context['postId'] ?? 0 ) : 0;
	if ( $post_id <= 0 && isset( $_GET['post_id'] ) ) {
		$post_id = absint( wp_unslash( $_GET['post_id'] ) );
	}
	return $post_id > 0 ? $post_id : (int) get_the_ID();
}

/** Returns an editor-only explanation when a taxonomy-driven block has no data. */
function lmhg_site_core_relationship_block_empty_preview( int $post_id, string $message ): string {
	if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST || ! current_user_can( 'edit_post', $post_id ) ) {
		return '';
	}
	return '<p class="lmhg-relationship-editor-empty">' . esc_html( $message ) . '</p>';
}

/** Renders FAQs from the current page's assigned FAQ Set taxonomy. */
function lmhg_site_core_render_faqs_block( array $attributes, string $content = '', ?WP_Block $block = null ): string {
	unset( $content );
	$post_id = lmhg_site_core_relationship_block_post_id( $block );
	$heading = sanitize_text_field( (string) ( $attributes['heading'] ?? 'Common Questions' ) );
	$rendered = $post_id > 0 ? lmhg_site_core_render_faqs( lmhg_site_core_faq_set_term_ids( '', $post_id ), $heading, -1 ) : '';
	return '' !== $rendered ? $rendered : lmhg_site_core_relationship_block_empty_preview( $post_id, 'No FAQ Set is assigned to this page.' );
}

/** Converts supported legacy Gutenberg shortcode blocks to native dynamic blocks. */
function lmhg_site_core_replace_relationship_shortcodes( string $content ): string {
	$blocks = array(
		'lmhg_related_pages' => array( 'name' => 'lmhg/related-pages', 'heading' => 'Related Pages' ),
		'lmhg_faqs'          => array( 'name' => 'lmhg/faqs', 'heading' => 'Common Questions' ),
	);
	foreach ( $blocks as $shortcode => $definition ) {
		$pattern = '/<!--\s+wp:shortcode\s+-->\s*\[' . preg_quote( $shortcode, '/' ) . '([^\]]*)\]\s*<!--\s+\/wp:shortcode\s+-->/';
		$content = (string) preg_replace_callback(
			$pattern,
			static function ( array $matches ) use ( $definition ): string {
				$parsed  = shortcode_parse_atts( trim( (string) $matches[1] ) );
				$heading = is_array( $parsed ) && isset( $parsed['heading'] )
					? sanitize_text_field( (string) $parsed['heading'] )
					: $definition['heading'];
				return sprintf(
					'<!-- wp:%s %s /-->',
					$definition['name'],
					wp_json_encode( array( 'heading' => $heading ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
				);
			},
			$content
		);
	}
	return $content;
}

/** Migrates page content and revisions, then disables the migrated FAQ shortcode. */
function lmhg_site_core_run_relationship_block_migration(): void {
	global $wpdb;
	$installed = (string) get_option( LMHG_SITE_CORE_RELATIONSHIP_BLOCK_MIGRATION_OPTION, '' );
	if ( LMHG_SITE_CORE_RELATIONSHIP_BLOCK_MIGRATION_VERSION !== $installed ) {
		$rows    = $wpdb->get_results( "SELECT ID, post_content FROM {$wpdb->posts} WHERE post_type IN ('page', 'revision')" );
		$changed = 0;
		$complete = is_array( $rows );
		foreach ( $rows as $row ) {
			$content = lmhg_site_core_replace_relationship_shortcodes( (string) $row->post_content );
			if ( $content === (string) $row->post_content ) {
				continue;
			}
			$written = $wpdb->update(
				$wpdb->posts,
				array( 'post_content' => $content ),
				array( 'ID' => (int) $row->ID ),
				array( '%s' ),
				array( '%d' )
			);
			$complete = false !== $written && $complete;
			if ( false !== $written ) {
				++$changed;
				clean_post_cache( (int) $row->ID );
			}
		}
		update_option( 'lmhg_relationship_block_migration_report', array( 'rows' => count( $rows ), 'changed' => $changed ), false );
		if ( $complete ) {
			update_option( LMHG_SITE_CORE_RELATIONSHIP_BLOCK_MIGRATION_OPTION, LMHG_SITE_CORE_RELATIONSHIP_BLOCK_MIGRATION_VERSION, false );
		}
	}

	if ( LMHG_SITE_CORE_RELATIONSHIP_BLOCK_MIGRATION_VERSION === (string) get_option( LMHG_SITE_CORE_RELATIONSHIP_BLOCK_MIGRATION_OPTION, '' ) ) {
		remove_shortcode( 'lmhg_related_pages' );
		remove_shortcode( 'lmhg_faqs' );
	}
}

/**
 * Removes retired Related Pages presentation from Pages and revisions.
 *
 * Taxonomy assignments and relationship metadata remain intact for backend
 * classification, service icons, breadcrumbs, and editorial article curation.
 */
function lmhg_site_core_run_related_page_presentation_migration(): void {
	if ( LMHG_SITE_CORE_RELATED_PAGE_PRESENTATION_MIGRATION_VERSION === (string) get_option( LMHG_SITE_CORE_RELATED_PAGE_PRESENTATION_MIGRATION_OPTION, '' ) ) {
		return;
	}

	global $wpdb;
	$post_types  = array( 'page', 'revision' );
	$placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
	$query        = "SELECT ID, post_content FROM {$wpdb->posts} WHERE post_type IN ({$placeholders}) AND (post_content LIKE %s OR post_content LIKE %s)";
	$parameters   = array_merge(
		$post_types,
		array(
			'%' . $wpdb->esc_like( 'wp:lmhg/related-pages' ) . '%',
			'%' . $wpdb->esc_like( 'lmhg_related_pages' ) . '%',
		)
	);
	$rows         = $wpdb->get_results( $wpdb->prepare( $query, ...$parameters ) );
	if ( ! is_array( $rows ) ) {
		return;
	}

	$changed  = 0;
	$complete = true;
	foreach ( $rows as $row ) {
		$content = lmhg_site_core_remove_related_page_presentation( (string) $row->post_content );
		if ( $content === (string) $row->post_content ) {
			continue;
		}

		$written = $wpdb->update(
			$wpdb->posts,
			array( 'post_content' => $content ),
			array( 'ID' => (int) $row->ID ),
			array( '%s' ),
			array( '%d' )
		);
		if ( false === $written ) {
			$complete = false;
			continue;
		}

		++$changed;
		clean_post_cache( (int) $row->ID );
	}

	update_option(
		'lmhg_related_page_presentation_migration_report',
		array(
			'rows'    => count( $rows ),
			'changed' => $changed,
		),
		false
	);
	if ( $complete ) {
		update_option(
			LMHG_SITE_CORE_RELATED_PAGE_PRESENTATION_MIGRATION_OPTION,
			LMHG_SITE_CORE_RELATED_PAGE_PRESENTATION_MIGRATION_VERSION,
			false
		);
	}
}

/**
 * Removes only native or legacy Related Pages presentation markup.
 *
 * @param string $content Gutenberg post content.
 */
function lmhg_site_core_remove_related_page_presentation( string $content ): string {
	$patterns = array(
		'/<!--\s+wp:lmhg\/related-pages(?:\s+\{.*?\})?\s+\/-->/s',
		'/<!--\s+wp:lmhg\/related-pages(?:\s+\{.*?\})?\s+-->.*?<!--\s+\/wp:lmhg\/related-pages\s+-->/s',
		'/<!--\s+wp:shortcode\s+-->\s*\[lmhg_related_pages[^\]]*\]\s*<!--\s+\/wp:shortcode\s+-->/s',
		'/\[lmhg_related_pages[^\]]*\]/',
	);

	$cleaned = preg_replace( $patterns, '', $content );
	return is_string( $cleaned ) ? $cleaned : $content;
}

/**
 * Adds the approved contextual links to the five canonical article Pages.
 *
 * Only exact, unique plain-text phrases are upgraded. If an editor has changed
 * a source phrase or introduced a duplicate, the Page is left untouched and a
 * conflict is recorded for review.
 */
function lmhg_site_core_run_article_contextual_link_migration(): void {
	if ( LMHG_SITE_CORE_ARTICLE_CONTEXTUAL_LINK_MIGRATION_VERSION === (string) get_option( LMHG_SITE_CORE_ARTICLE_CONTEXTUAL_LINK_MIGRATION_OPTION, '' ) ) {
		return;
	}

	$catalog = lmhg_site_core_article_contextual_link_migration_catalog();
	$report  = array(
		'version'          => LMHG_SITE_CORE_ARTICLE_CONTEXTUAL_LINK_MIGRATION_VERSION,
		'completed_at'     => '',
		'pages_expected'   => count( $catalog ),
		'pages_updated'    => 0,
		'pages_current'    => 0,
		'pages_conflicted' => 0,
		'conflicts'        => array(),
		'failures'         => array(),
	);

	foreach ( $catalog as $path => $replacements ) {
		$page = get_page_by_path( trim( $path, '/' ), OBJECT, 'page' );
		if ( ! $page instanceof WP_Post || 'publish' !== $page->post_status ) {
			$report['failures'][] = array(
				'path'   => $path,
				'reason' => 'published_page_not_found',
			);
			continue;
		}

		$result = lmhg_site_core_apply_article_contextual_links( (string) $page->post_content, $replacements );
		if ( ! $result['valid'] ) {
			++$report['pages_conflicted'];
			$report['conflicts'][] = array(
				'path'        => $path,
				'post_id'     => (int) $page->ID,
				'replacement' => (int) $result['replacement'],
				'reason'      => (string) $result['reason'],
			);
			continue;
		}

		if ( ! $result['changed'] ) {
			++$report['pages_current'];
			continue;
		}

		$updated = wp_update_post(
			wp_slash(
				array(
					'ID'           => (int) $page->ID,
					'post_content' => (string) $result['content'],
				)
			),
			true
		);
		if ( is_wp_error( $updated ) || (int) $updated !== (int) $page->ID ) {
			$report['failures'][] = array(
				'path'   => $path,
				'reason' => is_wp_error( $updated ) ? $updated->get_error_code() : 'page_update_failed',
			);
			continue;
		}

		++$report['pages_updated'];
	}

	if (
		empty( $report['conflicts'] )
		&& empty( $report['failures'] )
		&& $report['pages_expected'] === $report['pages_updated'] + $report['pages_current']
	) {
		$report['completed_at'] = gmdate( 'c' );
		update_option(
			LMHG_SITE_CORE_ARTICLE_CONTEXTUAL_LINK_MIGRATION_OPTION,
			LMHG_SITE_CORE_ARTICLE_CONTEXTUAL_LINK_MIGRATION_VERSION,
			false
		);
	}

	update_option( LMHG_SITE_CORE_ARTICLE_CONTEXTUAL_LINK_MIGRATION_REPORT, $report, false );
}

/**
 * Converges four published Pages on the native, taxonomy-backed FAQ block.
 *
 * This migration changes Page presentation only. FAQ records and FAQ Set
 * assignments remain under their existing editorial migrations and controls.
 */
function lmhg_site_core_run_faq_presentation_migration(): void {
	if ( LMHG_SITE_CORE_FAQ_PRESENTATION_MIGRATION_VERSION === (string) get_option( LMHG_SITE_CORE_FAQ_PRESENTATION_MIGRATION_OPTION, '' ) ) {
		return;
	}

	$paths  = array( '/adolescent-counseling/', '/locations/in-home/', '/our-services/', '/specialties/' );
	$report = array(
		'version'        => LMHG_SITE_CORE_FAQ_PRESENTATION_MIGRATION_VERSION,
		'completed_at'   => '',
		'pages_expected' => count( $paths ),
		'pages_updated'  => 0,
		'pages_current'  => 0,
		'conflicts'      => array(),
		'failures'       => array(),
	);

	foreach ( $paths as $path ) {
		$page = get_page_by_path( trim( $path, '/' ), OBJECT, 'page' );
		if (
			! $page instanceof WP_Post
			|| 'publish' !== $page->post_status
			|| trim( (string) get_page_uri( $page ), '/' ) !== trim( $path, '/' )
		) {
			$report['failures'][] = array( 'path' => $path, 'reason' => 'exact_published_page_not_found' );
			continue;
		}

		$result = lmhg_site_core_apply_native_faq_presentation( (string) $page->post_content, $path );
		if ( ! $result['valid'] ) {
			$report['conflicts'][] = array(
				'path'    => $path,
				'post_id' => (int) $page->ID,
				'reason'  => (string) $result['reason'],
			);
			continue;
		}

		if ( ! $result['changed'] ) {
			++$report['pages_current'];
			continue;
		}

		$updated = wp_update_post(
			wp_slash(
				array(
					'ID'           => (int) $page->ID,
					'post_content' => (string) $result['content'],
				)
			),
			true
		);
		if ( is_wp_error( $updated ) || (int) $updated !== (int) $page->ID ) {
			$report['failures'][] = array(
				'path'   => $path,
				'reason' => is_wp_error( $updated ) ? $updated->get_error_code() : 'page_update_failed',
			);
			continue;
		}

		++$report['pages_updated'];
	}

	if (
		empty( $report['conflicts'] )
		&& empty( $report['failures'] )
		&& $report['pages_expected'] === $report['pages_updated'] + $report['pages_current']
	) {
		$report['completed_at'] = gmdate( 'c' );
		update_option( LMHG_SITE_CORE_FAQ_PRESENTATION_MIGRATION_OPTION, LMHG_SITE_CORE_FAQ_PRESENTATION_MIGRATION_VERSION, false );
	}

	update_option( LMHG_SITE_CORE_FAQ_PRESENTATION_MIGRATION_REPORT, $report, false );
}

/**
 * Applies the approved FAQ-only presentation changes without rewriting other copy.
 *
 * @param string $content Existing Page content.
 * @param string $path Exact public Page path.
 * @return array{valid:bool,changed:bool,content:string,reason:string}
 */
function lmhg_site_core_apply_native_faq_presentation( string $content, string $path ): array {
	$approved_paths = array( '/adolescent-counseling/', '/locations/in-home/', '/our-services/', '/specialties/' );
	if ( ! in_array( $path, $approved_paths, true ) ) {
		return array( 'valid' => false, 'changed' => false, 'content' => $content, 'reason' => 'path_not_approved' );
	}

	$candidate = $content;
	$changed   = false;

	if ( '/adolescent-counseling/' === $path ) {
		$heading = "<!-- wp:heading {\"level\":2,\"className\":\"wp2026-section-title\"} -->\n<h2 class=\"wp-block-heading wp2026-section-title\">FAQs</h2>\n<!-- /wp:heading -->";
		if ( 1 === substr_count( $candidate, $heading ) ) {
			$start = strpos( $candidate, $heading );
			$end   = false !== $start ? strpos( $candidate, "</div>\n<!-- /wp:group -->", $start ) : false;
			$legacy = false !== $start && false !== $end ? substr( $candidate, $start, $end - $start ) : '';
			$required_answers = array(
				'No. It can help with stress, worry, sadness, school pressure, social strain, family conflict, or a hard change.',
				'They may be. Parent check-ins or family sessions can be used when they fit the teen\'s needs.',
				'Yes, but privacy has limits.',
				'No. Teen therapy centers on the teen.',
			);
			$exact_legacy = 4 === substr_count( $legacy, '<!-- wp:paragraph -->' );
			foreach ( $required_answers as $answer ) {
				$exact_legacy = str_contains( $legacy, $answer ) && $exact_legacy;
			}
			if ( ! $exact_legacy ) {
				return array( 'valid' => false, 'changed' => false, 'content' => $content, 'reason' => 'adolescent_legacy_faq_conflict' );
			}
			$candidate = substr_replace( $candidate, '', $start, $end - $start );
			$changed   = true;
		} elseif ( str_contains( $candidate, '>FAQs</h2>' ) ) {
			return array( 'valid' => false, 'changed' => false, 'content' => $content, 'reason' => 'adolescent_faq_heading_conflict' );
		}
	}

	if ( '/locations/in-home/' === $path ) {
		$opening = '<!-- wp:group {"className":"wp2026-location-faq lmhg-faqs","layout":{"type":"constrained"}} -->';
		if ( 1 === substr_count( $candidate, $opening ) ) {
			$start  = strpos( $candidate, $opening );
			$end    = false !== $start ? strpos( $candidate, '<!-- /wp:group -->', $start ) : false;
			$legacy = false !== $start && false !== $end ? substr( $candidate, $start, $end + strlen( '<!-- /wp:group -->' ) - $start ) : '';
			if ( 4 !== substr_count( $legacy, '<details class="lmhg-faq-item wp2026-location-faq-item">' ) ) {
				return array( 'valid' => false, 'changed' => false, 'content' => $content, 'reason' => 'in_home_legacy_faq_conflict' );
			}
			$candidate = substr_replace( $candidate, '', $start, strlen( $legacy ) );
			$changed   = true;
		} elseif ( str_contains( $candidate, 'wp2026-location-faq' ) ) {
			return array( 'valid' => false, 'changed' => false, 'content' => $content, 'reason' => 'in_home_faq_marker_conflict' );
		}
	}

	if ( str_contains( $candidate, '[lmhg_faqs' ) ) {
		return array( 'valid' => false, 'changed' => false, 'content' => $content, 'reason' => 'legacy_shortcode_conflict' );
	}

	$block_count = substr_count( $candidate, 'wp:lmhg/faqs' );
	if ( $block_count > 1 ) {
		return array( 'valid' => false, 'changed' => false, 'content' => $content, 'reason' => 'multiple_native_faq_blocks' );
	}

	$canonical = '<!-- wp:lmhg/faqs {"heading":"Common Questions"} /-->';
	if ( 0 === $block_count ) {
		$candidate = rtrim( $candidate ) . "\n\n" . $canonical;
		$changed   = true;
	} elseif ( 1 !== substr_count( $candidate, $canonical ) ) {
		$replaced = 0;
		$candidate = (string) preg_replace(
			'/<!--\s+wp:lmhg\/faqs(?:\s+\{[^\r\n]*\})?\s+\/-->/',
			$canonical,
			$candidate,
			1,
			$replaced
		);
		if ( 1 !== $replaced ) {
			return array( 'valid' => false, 'changed' => false, 'content' => $content, 'reason' => 'native_faq_block_conflict' );
		}
		$changed = true;
	}

	return array( 'valid' => true, 'changed' => $changed, 'content' => $candidate, 'reason' => '' );
}

/**
 * Applies exact, unique phrase-to-anchor replacements without partial writes.
 *
 * @param string                                          $content      Existing Page content.
 * @param array<int,array{before:string,after:string}>     $replacements Approved replacements.
 * @return array{valid:bool,changed:bool,content:string,replacement:int,reason:string}
 */
function lmhg_site_core_apply_article_contextual_links( string $content, array $replacements ): array {
	$candidate = $content;
	$changed   = false;

	foreach ( $replacements as $index => $replacement ) {
		$before       = (string) ( $replacement['before'] ?? '' );
		$after        = (string) ( $replacement['after'] ?? '' );
		$before_count = '' !== $before ? substr_count( $candidate, $before ) : 0;
		$after_count  = '' !== $after ? substr_count( $candidate, $after ) : 0;

		if ( 0 === $before_count && 1 === $after_count ) {
			continue;
		}
		if ( 1 !== $before_count || 0 !== $after_count ) {
			return array(
				'valid'       => false,
				'changed'     => false,
				'content'     => $content,
				'replacement' => (int) $index,
				'reason'      => 0 !== $after_count ? 'target_count_unexpected' : 'source_count_unexpected',
			);
		}

		$candidate = str_replace( $before, $after, $candidate );
		$changed   = true;
	}

	return array(
		'valid'       => true,
		'changed'     => $changed,
		'content'     => $candidate,
		'replacement' => -1,
		'reason'      => '',
	);
}

/**
 * Returns the five canonical article Page phrases approved for contextual links.
 *
 * @return array<string,array<int,array{before:string,after:string}>>
 */
function lmhg_site_core_article_contextual_link_migration_catalog(): array {
	return array(
		'/family-therapy-vs-individual-therapy/' => array(
			array(
				'before' => 'They answer different questions. Individual counseling focuses',
				'after'  => 'They answer different questions. <a href="/individual-therapy/">Individual counseling</a> focuses',
			),
			array(
				'before' => 'and goals. Family therapy focuses on the patterns between people',
				'after'  => 'and goals. <a href="/family-therapy/">Family therapy</a> focuses on the patterns between people',
			),
		),
		'/guide-to-individual-therapy/' => array(
			array(
				'before' => 'start by reviewing the individual counseling page or contacting the office',
				'after'  => 'start by reviewing the <a href="/individual-therapy/">individual counseling</a> page or contacting the office',
			),
		),
		'/how-to-talk-to-your-loved-ones-about-going-to-therapy/' => array(
			array(
				'before' => 'can help compare individual counseling, family therapy, couples counseling, and other support options',
				'after'  => 'can help compare <a href="/individual-therapy/">individual counseling</a>, <a href="/family-therapy/">family therapy</a>, <a href="/couples-counseling/">couples counseling</a>, and other support options',
			),
		),
		'/top-5-signs-its-time-to-seek-therapy/' => array(
			array(
				'before' => 'If several signs feel familiar, reaching out can help clarify the right service instead of guessing alone.',
				'after'  => 'If several signs feel familiar, <a href="/contact-us/">reaching out</a> can help clarify the <a href="/our-services/">right service</a> instead of guessing alone.',
			),
		),
		'/what-to-expect-when-starting-therapy/' => array(
			array(
				'before' => 'even if they are not sure which service, clinician, or care setting is the best fit.',
				'after'  => 'even if they are not sure which <a href="/our-services/">service</a>, clinician, or care setting is the best fit.',
			),
			array(
				'before' => 'whether care should happen at the Louisville office, by telehealth, or in another appropriate setting.',
				'after'  => 'whether care should happen at the <a href="/locations/in-person/">Louisville office</a>, by <a href="/locations/online/">telehealth</a>, or in another appropriate setting.',
			),
		),
	);
}

/**
 * Registers LMHG relationship taxonomies.
 */
function lmhg_site_core_register_relationship_taxonomies(): void {
	register_taxonomy(
		LMHG_SITE_CORE_SPECIALTY_TAXONOMY,
		array( 'page' ),
		array(
			'labels'            => array(
				'name'                       => 'LMHG Specialties',
				'singular_name'              => 'LMHG Specialty',
				'search_items'               => 'Search Specialties',
				'popular_items'              => 'Popular Specialties',
				'all_items'                  => 'All Specialties',
				'edit_item'                  => 'Edit Specialty',
				'update_item'                => 'Update Specialty',
				'add_new_item'               => 'Add New Specialty',
				'new_item_name'              => 'New Specialty Name',
				'separate_items_with_commas' => 'Separate specialties with commas',
				'add_or_remove_items'        => 'Add or remove specialties',
				'choose_from_most_used'      => 'Choose from the most used specialties',
				'menu_name'                  => 'LMHG Specialties',
			),
			'public'            => false,
			'publicly_queryable' => false,
			'hierarchical'      => true,
			'sort'              => true,
			'show_ui'           => true,
				'show_admin_column' => false,
				'show_in_menu'       => false,
			'show_in_rest'      => true,
			'show_tagcloud'     => false,
			'rewrite'           => false,
			'query_var'         => false,
		)
	);

	register_taxonomy(
		LMHG_SITE_CORE_FAQ_SET_TAXONOMY,
		array( 'page', LMHG_SITE_CORE_FAQ_POST_TYPE ),
		array(
			'labels'            => array(
				'name'                       => 'LMHG FAQ Sets',
				'singular_name'              => 'LMHG FAQ Set',
				'search_items'               => 'Search FAQ Sets',
				'popular_items'              => 'Popular FAQ Sets',
				'all_items'                  => 'All FAQ Sets',
				'edit_item'                  => 'Edit FAQ Set',
				'update_item'                => 'Update FAQ Set',
				'add_new_item'               => 'Add New FAQ Set',
				'new_item_name'              => 'New FAQ Set Name',
				'separate_items_with_commas' => 'Separate FAQ sets with commas',
				'add_or_remove_items'        => 'Add or remove FAQ sets',
				'choose_from_most_used'      => 'Choose from the most used FAQ sets',
				'menu_name'                  => 'LMHG FAQ Sets',
			),
			'public'            => false,
			'publicly_queryable' => false,
			'hierarchical'      => true,
			'show_ui'           => true,
				'show_admin_column' => false,
				'show_in_menu'       => false,
			'show_in_rest'      => true,
			'show_tagcloud'     => false,
			'rewrite'           => false,
			'query_var'         => false,
		)
	);
}

/**
 * Registers admin-owned FAQ and team member post types.
 */
function lmhg_site_core_register_relationship_post_types(): void {
	register_post_type(
		LMHG_SITE_CORE_FAQ_POST_TYPE,
		array(
			'labels'              => array(
				'name'               => 'LMHG FAQs',
				'singular_name'      => 'LMHG FAQ',
				'add_new_item'       => 'Add New FAQ',
				'edit_item'          => 'Edit FAQ',
				'new_item'           => 'New FAQ',
				'view_item'          => 'View FAQ',
				'search_items'       => 'Search FAQs',
				'not_found'          => 'No FAQs found',
				'not_found_in_trash' => 'No FAQs found in Trash',
				'menu_name'          => 'LMHG FAQs',
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'show_in_rest'        => true,
			'menu_icon'           => 'dashicons-editor-help',
			'supports'            => array( 'title', 'editor', 'excerpt', 'revisions', 'page-attributes' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'map_meta_cap'        => true,
		)
	);

	register_post_type(
		LMHG_SITE_CORE_TEAM_POST_TYPE,
		array(
			'labels'              => array(
				'name'               => 'LMHG Team',
				'singular_name'      => 'LMHG Team Member',
				'add_new_item'       => 'Add New Team Member',
				'edit_item'          => 'Edit Team Member',
				'new_item'           => 'New Team Member',
				'view_item'          => 'View Team Member',
				'search_items'       => 'Search Team Members',
				'not_found'          => 'No team members found',
				'not_found_in_trash' => 'No team members found in Trash',
				'menu_name'          => 'LMHG Team',
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'show_in_rest'        => true,
			'menu_icon'           => 'dashicons-groups',
			'supports'            => array( 'title', 'thumbnail', 'revisions', 'page-attributes' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'map_meta_cap'        => true,
		)
	);
}

/**
 * Seeds service-family terms so their card copy and icons can be edited in WordPress.
 */
function lmhg_site_core_seed_related_page_terms(): void {
	if (
		LMHG_SITE_CORE_RELATED_PAGE_TERM_SEED_VERSION === (string) get_option( LMHG_SITE_CORE_RELATED_PAGE_TERM_SEED_OPTION, '' )
		|| ! taxonomy_exists( LMHG_SITE_CORE_SPECIALTY_TAXONOMY )
	) {
		return;
	}

	$complete = true;

	foreach ( lmhg_site_core_related_page_term_seed_items() as $slug => $item ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
		if ( ! $page instanceof WP_Post ) {
			$complete = false;
		}

		$term_id = lmhg_site_core_ensure_related_page_term(
			$slug,
			(string) $item['name'],
			(string) $item['description']
		);

		if ( $term_id <= 0 ) {
			$complete = false;
		}
	}

	if ( $complete ) {
		update_option( LMHG_SITE_CORE_RELATED_PAGE_TERM_SEED_OPTION, LMHG_SITE_CORE_RELATED_PAGE_TERM_SEED_VERSION, false );
	}
}

/**
 * Returns broad service terms that should be editable through the LMHG Specialty taxonomy.
 *
 * @return array<string,array{name:string,description:string}>
 */
function lmhg_site_core_related_page_term_seed_items(): array {
	return array(
		'individual-therapy'       => array(
			'name'        => 'Individual Counseling',
			'description' => 'One-on-one care for adults and teens comparing therapy, counseling, anxiety, depression, stress, trauma, and life-change support.',
		),
		'child-therapy'            => array(
			'name'        => 'Child Counseling',
			'description' => 'Child and adolescent support for behavior, emotional regulation, family stress, school pressure, trauma, and developmentally appropriate care.',
		),
		'family-therapy'           => array(
			'name'        => 'Family Therapy',
			'description' => 'Family support for communication, routines, parenting stress, attachment concerns, conflict, and major transitions.',
		),
		'couples-counseling'       => array(
			'name'        => 'Couples Counseling',
			'description' => 'Relationship support for communication, recurring conflict, emotional distance, repair, and relationship decisions.',
		),
		'family-court'             => array(
			'name'        => 'Court-Ordered Services',
			'description' => 'Court-involved family support for reunification, co-parenting, documentation questions, and stability during legal stress.',
		),
		'community-based-services' => array(
			'name'        => 'Community-Based Services',
			'description' => 'Practical support for care coordination, community support, in-home needs, resources, and follow-through outside a traditional office visit.',
		),
		'group-therapy'            => array(
			'name'        => 'Group Therapy',
			'description' => 'Structured therapy in a group setting for shared learning, skill practice, and guided support.',
		),
		'trauma-therapy'           => array(
			'name'        => 'Trauma Therapy',
			'description' => 'Trauma-focused support for distressing memories, triggers, grief, anxiety, PTSD symptoms, and experiences that still feel active.',
		),
	);
}

/**
 * Ensures a related-page term exists without overwriting editor-authored copy.
 *
 * @param string $slug Term slug.
 * @param string $name Term display name.
 * @param string $description Optional starter description.
 * @return int
 */
function lmhg_site_core_ensure_related_page_term( string $slug, string $name, string $description = '' ): int {
	$term = get_term_by( 'slug', $slug, LMHG_SITE_CORE_SPECIALTY_TAXONOMY );
	if ( $term instanceof WP_Term ) {
		if ( '' !== trim( $description ) && '' === trim( (string) $term->description ) ) {
			$result = wp_update_term(
				(int) $term->term_id,
				LMHG_SITE_CORE_SPECIALTY_TAXONOMY,
				array( 'description' => $description )
			);

			if ( is_wp_error( $result ) ) {
				return 0;
			}
		}

		return (int) $term->term_id;
	}

	$args = array( 'slug' => $slug );
	if ( '' !== trim( $description ) ) {
		$args['description'] = $description;
	}

	$created = wp_insert_term( $name, LMHG_SITE_CORE_SPECIALTY_TAXONOMY, $args );
	if ( is_wp_error( $created ) ) {
		$existing_id = $created->get_error_data( 'term_exists' );
		return is_numeric( $existing_id ) ? (int) $existing_id : 0;
	}

	return isset( $created['term_id'] ) ? (int) $created['term_id'] : 0;
}

/**
 * Seeds explicit service-to-specialty taxonomy relationships.
 */
function lmhg_site_core_seed_service_specialty_relationships(): void {
	if (
		LMHG_SITE_CORE_SERVICE_SPECIALTY_SEED_VERSION === (string) get_option( LMHG_SITE_CORE_SERVICE_SPECIALTY_SEED_OPTION, '' )
		|| ! taxonomy_exists( LMHG_SITE_CORE_SPECIALTY_TAXONOMY )
	) {
		return;
	}

	$complete = true;

	foreach ( lmhg_site_core_service_specialty_relationship_seed_items() as $service_slug => $specialties ) {
		$service = get_page_by_path( $service_slug, OBJECT, 'page' );
		if ( ! $service instanceof WP_Post ) {
			$complete = false;
			continue;
		}

		$term_ids = array();
		foreach ( $specialties as $specialty_slug => $label ) {
			$term_id = lmhg_site_core_ensure_service_specialty_term( $specialty_slug, $label );
			if ( $term_id <= 0 ) {
				$complete = false;
				continue;
			}

			$term_ids[] = $term_id;
		}

		if ( empty( $term_ids ) ) {
			continue;
		}

		$result = wp_set_object_terms( $service->ID, $term_ids, LMHG_SITE_CORE_SPECIALTY_TAXONOMY, true );
		if ( is_wp_error( $result ) ) {
			$complete = false;
		}
	}

	if ( $complete ) {
		update_option( LMHG_SITE_CORE_SERVICE_SPECIALTY_SEED_OPTION, LMHG_SITE_CORE_SERVICE_SPECIALTY_SEED_VERSION, false );
	}
}

/**
 * Returns the explicit specialty taxonomy relationships for service pages.
 *
 * @return array<string,array<string,string>>
 */
function lmhg_site_core_service_specialty_relationship_seed_items(): array {
	return array(
		'child-therapy'            => array(
			'adolescent-counseling'         => 'Teen Therapy',
			'child-behavioral-intervention' => 'Child Behavioral Therapy',
			'parenting-support'              => 'Parenting Support',
			'play-therapy'                  => 'Play Therapy',
		),
		'community-based-services' => array(
			'case-management'   => 'Case Management',
			'community-support' => 'Community Support',
		),
		'family-court'             => array(
			'co-parenting'         => 'Co-Parenting',
			'family-reunification' => 'Family Reunification',
		),
		'family-therapy'           => array(
			'attachment-therapy'             => 'Parent-Child Attachment Therapy',
			'conflict-resolution-counseling' => 'Conflict Resolution Counseling',
		),
		'individual-therapy'       => array(
			'adult-counseling'           => 'Adult Counseling',
			'anxiety-depression-therapy' => 'Anxiety and Depression Therapy',
		),
		'trauma-therapy'           => array(
			'emdr-therapy' => 'EMDR Therapy',
		),
	);
}

/**
 * Ensures a specialty taxonomy term exists for a related page relationship.
 *
 * @param string $slug Term slug.
 * @param string $name Term display name.
 * @return int
 */
function lmhg_site_core_ensure_service_specialty_term( string $slug, string $name ): int {
	return lmhg_site_core_ensure_related_page_term( $slug, $name );
}

/**
 * Seeds editable starter FAQ records for specialty pages.
 */
function lmhg_site_core_seed_specialty_placeholder_faqs(): void {
	if (
		LMHG_SITE_CORE_SPECIALTY_FAQ_SEED_VERSION === (string) get_option( LMHG_SITE_CORE_SPECIALTY_FAQ_SEED_OPTION, '' )
		|| ! post_type_exists( LMHG_SITE_CORE_FAQ_POST_TYPE )
		|| ! taxonomy_exists( LMHG_SITE_CORE_FAQ_SET_TAXONOMY )
	) {
		return;
	}

	$complete = true;

	foreach ( lmhg_site_core_specialty_placeholder_faq_seed_items() as $page_slug => $label ) {
		$page = get_page_by_path( $page_slug, OBJECT, 'page' );
		if ( ! $page instanceof WP_Post ) {
			$complete = false;
			continue;
		}

		$term_id = lmhg_site_core_ensure_specialty_faq_set( $page_slug, $label );
		if ( $term_id <= 0 ) {
			$complete = false;
			continue;
		}

		wp_set_object_terms( $page->ID, array( $term_id ), LMHG_SITE_CORE_FAQ_SET_TAXONOMY, true );

		foreach ( lmhg_site_core_specialty_placeholder_faq_posts( $page_slug, $label ) as $faq ) {
			$faq_id = lmhg_site_core_ensure_specialty_faq_post( $faq, $term_id );
			if ( $faq_id <= 0 ) {
				$complete = false;
			}
		}
	}

	if ( $complete ) {
		update_option( LMHG_SITE_CORE_SPECIALTY_FAQ_SEED_OPTION, LMHG_SITE_CORE_SPECIALTY_FAQ_SEED_VERSION, false );
	}
}

/**
 * Returns the specialty pages that need editable starter FAQ content.
 *
 * @return array<string,string>
 */
function lmhg_site_core_specialty_placeholder_faq_seed_items(): array {
	return array(
		'adolescent-counseling'         => 'Teen Therapy',
		'adult-counseling'              => 'Adult Counseling',
		'anxiety-depression-therapy'    => 'Anxiety and Depression Therapy',
		'attachment-therapy'             => 'Parent-Child Attachment Therapy',
		'case-management'                => 'Case Management',
		'child-behavioral-intervention'  => 'Child Behavioral Therapy',
		'co-parenting'                   => 'Co-Parenting Services',
		'community-support'              => 'Community Support Services',
		'conflict-resolution-counseling' => 'Conflict Resolution Counseling',
		'emdr-therapy'                   => 'EMDR Therapy',
		'family-reunification'           => 'Family Reunification Services',
		'parenting-support'              => 'Parenting Support',
		'play-therapy'                   => 'Play Therapy',
	);
}

/**
 * Removes the old In-Home specialty classification while leaving the location page intact.
 */
function lmhg_site_core_cleanup_in_home_specialty_classification(): void {
	if (
		LMHG_SITE_CORE_IN_HOME_SPECIALTY_CLEANUP_VERSION === (string) get_option( LMHG_SITE_CORE_IN_HOME_SPECIALTY_CLEANUP_OPTION, '' )
		|| ! taxonomy_exists( LMHG_SITE_CORE_SPECIALTY_TAXONOMY )
		|| ! taxonomy_exists( LMHG_SITE_CORE_FAQ_SET_TAXONOMY )
	) {
		return;
	}

	$complete = true;

	$specialty_term = get_term_by( 'slug', 'therapy-in-your-home', LMHG_SITE_CORE_SPECIALTY_TAXONOMY );
	if ( $specialty_term instanceof WP_Term ) {
		$deleted = wp_delete_term( (int) $specialty_term->term_id, LMHG_SITE_CORE_SPECIALTY_TAXONOMY );
		if ( is_wp_error( $deleted ) || false === $deleted ) {
			$complete = false;
		}
	}

	$faq_term = get_term_by( 'slug', 'therapy-in-your-home', LMHG_SITE_CORE_FAQ_SET_TAXONOMY );
	if ( $faq_term instanceof WP_Term ) {
		$deleted = wp_delete_term( (int) $faq_term->term_id, LMHG_SITE_CORE_FAQ_SET_TAXONOMY );
		if ( is_wp_error( $deleted ) || false === $deleted ) {
			$complete = false;
		}
	}

	if ( $complete ) {
		update_option( LMHG_SITE_CORE_IN_HOME_SPECIALTY_CLEANUP_OPTION, LMHG_SITE_CORE_IN_HOME_SPECIALTY_CLEANUP_VERSION, false );
	}
}

/**
 * Ensures a specialty FAQ set term exists.
 *
 * @param string $slug Term slug.
 * @param string $name Term display name.
 * @return int
 */
function lmhg_site_core_ensure_specialty_faq_set( string $slug, string $name ): int {
	$term = get_term_by( 'slug', $slug, LMHG_SITE_CORE_FAQ_SET_TAXONOMY );
	if ( $term instanceof WP_Term ) {
		return (int) $term->term_id;
	}

	$created = wp_insert_term(
		$name,
		LMHG_SITE_CORE_FAQ_SET_TAXONOMY,
		array(
			'slug'        => $slug,
			'description' => 'Starter FAQ set for the ' . $name . ' specialty page.',
		)
	);

	if ( is_wp_error( $created ) ) {
		return 0;
	}

	return isset( $created['term_id'] ) ? (int) $created['term_id'] : 0;
}

/**
 * Returns the starter FAQ posts for one specialty page.
 *
 * @param string $slug Specialty page slug.
 * @param string $label Specialty display label.
 * @return array<int,array{slug:string,title:string,content:string,order:int}>
 */
function lmhg_site_core_specialty_placeholder_faq_posts( string $slug, string $label ): array {
	return array(
		array(
			'slug'    => 'specialty-' . $slug . '-faq-fit',
			'title'   => 'Is ' . $label . ' the right starting point?',
			'content' => 'Start with the main concern, who needs support, preferred care setting, and insurance questions. LMHG can help confirm whether ' . $label . ' or another service is the most practical first step.',
			'order'   => 10,
		),
		array(
			'slug'    => 'specialty-' . $slug . '-faq-start',
			'title'   => 'How do I get started with ' . $label . '?',
			'content' => 'Use the intake form or call (502) 416-1416 with the concern, availability needs, insurance questions, and any preferences for office-based, telehealth, in-home, school-based, or community-based support.',
			'order'   => 20,
		),
	);
}

/**
 * Ensures a specialty FAQ post exists and is assigned to its FAQ set.
 *
 * @param array{slug:string,title:string,content:string,order:int} $faq FAQ seed data.
 * @param int                                                     $term_id FAQ set term ID.
 * @return int
 */
function lmhg_site_core_ensure_specialty_faq_post( array $faq, int $term_id ): int {
	$existing = get_posts(
		array(
			'name'           => $faq['slug'],
			'post_type'      => LMHG_SITE_CORE_FAQ_POST_TYPE,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'no_found_rows'  => true,
		)
	);

	$faq_id = ! empty( $existing ) && $existing[0] instanceof WP_Post ? (int) $existing[0]->ID : 0;
	if ( $faq_id <= 0 ) {
		$faq_id = wp_insert_post(
			wp_slash(
				array(
					'post_type'    => LMHG_SITE_CORE_FAQ_POST_TYPE,
					'post_status'  => 'publish',
					'post_name'    => $faq['slug'],
					'post_title'   => $faq['title'],
					'post_excerpt' => 'Starter specialty FAQ placeholder for backend editing.',
					'post_content' => '<!-- wp:paragraph --><p>' . esc_html( $faq['content'] ) . '</p><!-- /wp:paragraph -->',
					'menu_order'   => $faq['order'],
				)
			),
			true
		);

		if ( is_wp_error( $faq_id ) ) {
			return 0;
		}
	}

	wp_set_object_terms( (int) $faq_id, array( $term_id ), LMHG_SITE_CORE_FAQ_SET_TAXONOMY, true );

	return (int) $faq_id;
}

/**
 * Registers relationship meta for REST/editor compatibility.
 */
function lmhg_site_core_register_relationship_meta(): void {
	$auth_callback         = 'lmhg_site_core_relationship_meta_auth_callback';
	$article_auth_callback = 'lmhg_site_core_article_meta_auth_callback';

	register_term_meta(
		LMHG_SITE_CORE_SPECIALTY_TAXONOMY,
		LMHG_SITE_CORE_SPECIALTY_CARD_DESCRIPTION_META,
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_textarea_field',
			'auth_callback'     => 'lmhg_site_core_relationship_term_meta_auth_callback',
		)
	);

	register_term_meta(
		LMHG_SITE_CORE_SPECIALTY_TAXONOMY,
		LMHG_SITE_CORE_SPECIALTY_ICON_ID_META,
		array(
			'type'              => 'integer',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'absint',
			'auth_callback'     => 'lmhg_site_core_relationship_term_meta_auth_callback',
		)
	);

	register_post_meta(
		'post',
		LMHG_SITE_CORE_RELATED_PAGES_META,
		array(
			'type'              => 'array',
			'single'            => true,
			'show_in_rest'      => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array(
						'type' => 'integer',
					),
				),
			),
			'sanitize_callback' => 'lmhg_site_core_sanitize_page_id_array',
			'auth_callback'     => $article_auth_callback,
		)
	);

	register_post_meta(
		'post',
		LMHG_SITE_CORE_ARTICLE_CARD_DESCRIPTION_META,
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_textarea_field',
			'auth_callback'     => $article_auth_callback,
		)
	);

	foreach ( lmhg_site_core_team_meta_definitions() as $meta_key => $definition ) {
		register_post_meta(
			LMHG_SITE_CORE_TEAM_POST_TYPE,
			$meta_key,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => $definition['sanitize_callback'],
				'auth_callback'     => $auth_callback,
			)
		);
	}
}

/**
 * Returns team member meta definitions.
 *
 * @return array<string,array{sanitize_callback:callable|string}>
 */
function lmhg_site_core_team_meta_definitions(): array {
	return array(
		LMHG_SITE_CORE_TEAM_FIRST_META   => array( 'sanitize_callback' => 'sanitize_text_field' ),
		LMHG_SITE_CORE_TEAM_LAST_META    => array( 'sanitize_callback' => 'sanitize_text_field' ),
		LMHG_SITE_CORE_TEAM_CREDENTIALS  => array( 'sanitize_callback' => 'sanitize_text_field' ),
		LMHG_SITE_CORE_TEAM_HEADSHOT_URL => array( 'sanitize_callback' => 'esc_url_raw' ),
	);
}

/**
 * Authorizes relationship meta edits.
 *
 * @param mixed  $allowed Existing permission value.
 * @param string $meta_key Meta key.
 * @param int    $object_id Post ID.
 * @return bool
 */
function lmhg_site_core_relationship_meta_auth_callback( mixed $allowed = false, string $meta_key = '', int $object_id = 0 ): bool {
	unset( $allowed, $meta_key );
	return $object_id > 0 ? current_user_can( 'edit_post', $object_id ) : current_user_can( 'edit_posts' );
}

/**
 * Determines whether a post is an LMHG article.
 *
 * Public copy calls these entries Articles; WordPress stores them as Posts.
 *
 * @param WP_Post|int|null $post Post object or ID.
 */
function lmhg_site_core_is_article( WP_Post|int|null $post ): bool {
	$post = get_post( $post );
	if ( ! $post instanceof WP_Post ) {
		return false;
	}

	return 'post' === $post->post_type;
}

/**
 * Authorizes article metadata only for conventional WordPress Posts.
 *
 * @param mixed  $allowed Existing permission value.
 * @param string $meta_key Meta key.
 * @param int    $object_id Post ID.
 */
function lmhg_site_core_article_meta_auth_callback( mixed $allowed = false, string $meta_key = '', int $object_id = 0 ): bool {
	unset( $allowed, $meta_key );
	return $object_id > 0 && lmhg_site_core_is_article( $object_id ) && current_user_can( 'edit_post', $object_id );
}

/**
 * Authorizes relationship term meta edits.
 *
 * @param mixed  $allowed Existing permission value.
 * @param string $meta_key Meta key.
 * @param int    $term_id Term ID.
 * @return bool
 */
function lmhg_site_core_relationship_term_meta_auth_callback( mixed $allowed = false, string $meta_key = '', int $term_id = 0 ): bool {
	unset( $allowed, $meta_key );
	return $term_id > 0 ? current_user_can( 'edit_term', $term_id ) : current_user_can( 'manage_categories' );
}

/**
 * Adds admin metaboxes for relationships and team member fields.
 */
function lmhg_site_core_add_relationship_meta_boxes( string $post_type, WP_Post $post ): void {
	if ( lmhg_site_core_is_article( $post ) ) {
		add_meta_box(
			'lmhg-related-pages',
			'Helpful Articles Placement',
			'lmhg_site_core_render_article_pages_meta_box',
			$post_type,
			'side',
			'default'
		);

		add_meta_box(
			'lmhg-article-card-description',
			'LMHG Article Card Description',
			'lmhg_site_core_render_article_card_description_meta_box',
			$post_type,
			'normal',
			'default'
		);
	}

	if ( LMHG_SITE_CORE_TEAM_POST_TYPE === $post_type ) {
		add_meta_box(
			'lmhg-team-member-details',
			'Team Member Details',
			'lmhg_site_core_render_team_member_meta_box',
			LMHG_SITE_CORE_TEAM_POST_TYPE,
			'normal',
			'high'
		);
	}
}

/**
 * Renders the article-to-page relationship picker.
 *
 * @param WP_Post $post Article post.
 */
function lmhg_site_core_render_article_pages_meta_box( WP_Post $post ): void {
	wp_nonce_field( 'lmhg_site_core_save_article_relationship_meta', 'lmhg_site_core_article_relationship_nonce' );

	$selected_ids = lmhg_site_core_related_page_ids( $post->ID );
	$pages        = lmhg_site_core_page_choices();
	?>
	<label class="screen-reader-text" for="lmhg-related-page-ids">Show this article on LMHG pages</label>
	<select id="lmhg-related-page-ids" name="lmhg_related_page_ids[]" multiple="multiple" size="12" style="width:100%;">
		<?php foreach ( $pages as $page ) : ?>
			<option value="<?php echo esc_attr( (string) $page->ID ); ?>" <?php selected( in_array( $page->ID, $selected_ids, true ) ); ?>>
				<?php echo esc_html( lmhg_site_core_page_choice_label( $page ) ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<p class="description">Select the service or specialty pages where this article should appear. No more than three cards show on each selected page.</p>
	<p>
		<label for="lmhg-article-order"><strong>Helpful Articles order</strong></label><br />
		<input id="lmhg-article-order" name="lmhg_article_order" type="number" step="1" class="small-text" value="<?php echo esc_attr( (string) $post->menu_order ); ?>" />
	</p>
	<p class="description">Lower numbers appear first; title breaks ties.</p>
	<?php
}

/**
 * Renders the article card description editor.
 *
 * @param WP_Post $post Article post.
 */
function lmhg_site_core_render_article_card_description_meta_box( WP_Post $post ): void {
	wp_nonce_field( 'lmhg_site_core_save_article_card_description_meta', 'lmhg_site_core_article_card_description_nonce' );

	$description = (string) get_post_meta( $post->ID, LMHG_SITE_CORE_ARTICLE_CARD_DESCRIPTION_META, true );
	?>
	<label class="screen-reader-text" for="lmhg-article-card-description">Helpful Articles card description</label>
	<textarea id="lmhg-article-card-description" name="lmhg_article_card_description" rows="4" style="width:100%;"><?php echo esc_textarea( $description ); ?></textarea>
	<p class="description">Controls the short description shown for this article inside Helpful Articles cards. If empty, the card falls back to the excerpt or first content paragraph.</p>
	<?php
}

/**
 * Renders the related-service card description field on the add form.
 *
 * @param string $taxonomy Taxonomy name.
 */
function lmhg_site_core_add_specialty_card_description_field( string $taxonomy ): void {
	unset( $taxonomy );
	?>
	<div class="form-field term-lmhg-card-description-wrap">
		<label for="lmhg-specialty-card-description">Service Card Description</label>
		<?php wp_nonce_field( 'lmhg_site_core_save_specialty_card_description', 'lmhg_specialty_card_description_nonce' ); ?>
		<textarea id="lmhg-specialty-card-description" name="lmhg_specialty_card_description" rows="5" cols="40"></textarea>
		<p>Controls the short description shown when this page appears in a taxonomy-driven service or specialty card. If empty, the term description or linked page summary is used.</p>
	</div>
	<?php
}

/**
 * Renders the related-service card description field on the edit form.
 *
 * @param WP_Term $term Specialty term.
 */
function lmhg_site_core_edit_specialty_card_description_field( WP_Term $term ): void {
	$description = lmhg_site_core_specialty_card_description_meta( $term );
	?>
	<tr class="form-field term-lmhg-card-description-wrap">
		<th scope="row"><label for="lmhg-specialty-card-description">Service Card Description</label></th>
		<td>
			<?php wp_nonce_field( 'lmhg_site_core_save_specialty_card_description', 'lmhg_specialty_card_description_nonce' ); ?>
			<textarea id="lmhg-specialty-card-description" name="lmhg_specialty_card_description" rows="5" cols="50" class="large-text"><?php echo esc_textarea( $description ); ?></textarea>
			<p class="description">Controls the short description shown when this page appears in a taxonomy-driven service or specialty card. If empty, the term description or linked page summary is used.</p>
		</td>
	</tr>
	<?php
}

/**
 * Renders the service icon field on the add form.
 *
 * @param string $taxonomy Taxonomy name.
 */
function lmhg_site_core_add_specialty_icon_field( string $taxonomy ): void {
	unset( $taxonomy );
	?>
	<div class="form-field term-lmhg-icon-wrap lmhg-specialty-icon-field">
		<label for="lmhg-specialty-icon-id">Icon Override</label>
		<input id="lmhg-specialty-icon-id" class="lmhg-specialty-icon-id" name="lmhg_specialty_icon_id" type="hidden" value="" />
		<div class="lmhg-specialty-icon-preview"><span>No icon selected.</span></div>
		<p>
			<button type="button" class="button lmhg-specialty-icon-select">Choose icon</button>
			<button type="button" class="button lmhg-specialty-icon-remove">Remove icon</button>
		</p>
		<p>Optional override for the icon shown at the top of the matching service or specialty process section. Broad service pages fall back to the homepage service icon when this is empty.</p>
	</div>
	<?php
}

/**
 * Renders the service icon field on the edit form.
 *
 * @param WP_Term $term Specialty term.
 */
function lmhg_site_core_edit_specialty_icon_field( WP_Term $term ): void {
	$icon_id = lmhg_site_core_specialty_icon_id( $term );
	?>
	<tr class="form-field term-lmhg-icon-wrap lmhg-specialty-icon-field">
		<th scope="row"><label for="lmhg-specialty-icon-id">Icon Override</label></th>
		<td>
			<input id="lmhg-specialty-icon-id" class="lmhg-specialty-icon-id" name="lmhg_specialty_icon_id" type="hidden" value="<?php echo esc_attr( (string) $icon_id ); ?>" />
			<div class="lmhg-specialty-icon-preview"><?php echo wp_kses_post( lmhg_site_core_specialty_icon_admin_preview( $icon_id ) ); ?></div>
			<p>
				<button type="button" class="button lmhg-specialty-icon-select">Choose icon</button>
				<button type="button" class="button lmhg-specialty-icon-remove">Remove icon</button>
			</p>
			<p class="description">Optional override for the icon shown at the top of the matching service or specialty process section. Broad service pages fall back to the homepage service icon when this is empty.</p>
		</td>
	</tr>
	<?php
}

/**
 * Saves the related-service card description term field.
 *
 * @param int $term_id Term ID.
 */
function lmhg_site_core_save_specialty_card_description_field( int $term_id ): void {
	$nonce = isset( $_POST['lmhg_specialty_card_description_nonce'] )
		? sanitize_text_field( wp_unslash( $_POST['lmhg_specialty_card_description_nonce'] ) )
		: '';
	if ( ! wp_verify_nonce( $nonce, 'lmhg_site_core_save_specialty_card_description' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_term', $term_id ) ) {
		return;
	}

	$description = isset( $_POST['lmhg_specialty_card_description'] )
		? sanitize_textarea_field( wp_unslash( $_POST['lmhg_specialty_card_description'] ) )
		: '';

	if ( '' === $description ) {
		delete_term_meta( $term_id, LMHG_SITE_CORE_SPECIALTY_CARD_DESCRIPTION_META );
	} else {
		update_term_meta( $term_id, LMHG_SITE_CORE_SPECIALTY_CARD_DESCRIPTION_META, $description );
	}

	$icon_id = isset( $_POST['lmhg_specialty_icon_id'] )
		? absint( wp_unslash( $_POST['lmhg_specialty_icon_id'] ) )
		: 0;

	if ( $icon_id > 0 ) {
		update_term_meta( $term_id, LMHG_SITE_CORE_SPECIALTY_ICON_ID_META, $icon_id );
		return;
	}

	delete_term_meta( $term_id, LMHG_SITE_CORE_SPECIALTY_ICON_ID_META );
}

/**
 * Enqueues the taxonomy icon media picker.
 *
 * @param string $hook_suffix Current admin hook.
 */
function lmhg_site_core_enqueue_specialty_icon_admin_assets( string $hook_suffix ): void {
	if ( ! in_array( $hook_suffix, array( 'edit-tags.php', 'term.php' ), true ) ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen instanceof WP_Screen || LMHG_SITE_CORE_SPECIALTY_TAXONOMY !== $screen->taxonomy ) {
		return;
	}

	wp_enqueue_media();
	wp_enqueue_script(
		'lmhg-specialty-icon-admin',
		plugins_url( 'assets/js/specialty-icon-admin.js', dirname( __DIR__ ) . '/lmhg-site-core.php' ),
		array( 'jquery' ),
		'0.1.1',
		true
	);
	wp_enqueue_style(
		'lmhg-specialty-icon-admin',
		plugins_url( 'assets/css/specialty-icon-admin.css', dirname( __DIR__ ) . '/lmhg-site-core.php' ),
		array(),
		'0.1.1'
	);
}

/**
 * Renders team member fields.
 *
 * @param WP_Post $post Team member post.
 */
function lmhg_site_core_render_team_member_meta_box( WP_Post $post ): void {
	wp_nonce_field( 'lmhg_site_core_save_team_member_meta', 'lmhg_site_core_team_member_nonce' );

	$order       = (int) $post->menu_order;
	$first_name  = get_post_meta( $post->ID, LMHG_SITE_CORE_TEAM_FIRST_META, true );
	$last_name   = get_post_meta( $post->ID, LMHG_SITE_CORE_TEAM_LAST_META, true );
	$credentials = get_post_meta( $post->ID, LMHG_SITE_CORE_TEAM_CREDENTIALS, true );
	$headshot    = get_post_meta( $post->ID, LMHG_SITE_CORE_TEAM_HEADSHOT_URL, true );
	?>
	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th scope="row"><label for="lmhg-team-order">Order</label></th>
				<td><input id="lmhg-team-order" name="lmhg_team_order" type="number" step="1" class="small-text" value="<?php echo esc_attr( (string) $order ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="lmhg-team-headshot-url">Headshot Media URL</label></th>
				<td>
					<input id="lmhg-team-headshot-url" name="lmhg_team_headshot_url" type="url" class="regular-text" value="<?php echo esc_url( (string) $headshot ); ?>" />
					<p class="description">Use a media-library URL, or leave blank to use the featured image.</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="lmhg-team-first-name">First Name</label></th>
				<td><input id="lmhg-team-first-name" name="lmhg_team_first_name" type="text" class="regular-text" value="<?php echo esc_attr( (string) $first_name ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="lmhg-team-last-name">Last Name</label></th>
				<td><input id="lmhg-team-last-name" name="lmhg_team_last_name" type="text" class="regular-text" value="<?php echo esc_attr( (string) $last_name ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="lmhg-team-credentials">Credentials</label></th>
				<td><input id="lmhg-team-credentials" name="lmhg_team_credentials" type="text" class="regular-text" value="<?php echo esc_attr( (string) $credentials ); ?>" /></td>
			</tr>
		</tbody>
	</table>
	<?php
}

/**
 * Saves article related page IDs.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post Article post.
 */
function lmhg_site_core_save_article_relationship_meta( int $post_id, WP_Post $post ): void {
	static $saving_order = false;

	if ( $saving_order || ! lmhg_site_core_is_article( $post ) || lmhg_site_core_should_skip_relationship_save( $post_id ) ) {
		return;
	}

	$nonce = isset( $_POST['lmhg_site_core_article_relationship_nonce'] )
		? sanitize_text_field( wp_unslash( $_POST['lmhg_site_core_article_relationship_nonce'] ) )
		: '';
	if ( ! wp_verify_nonce( $nonce, 'lmhg_site_core_save_article_relationship_meta' ) ) {
		return;
	}

	$page_ids = isset( $_POST['lmhg_related_page_ids'] )
		? lmhg_site_core_sanitize_page_id_array( wp_unslash( $_POST['lmhg_related_page_ids'] ) )
		: array();

	if ( empty( $page_ids ) ) {
		delete_post_meta( $post_id, LMHG_SITE_CORE_RELATED_PAGES_META );
	} else {
		update_post_meta( $post_id, LMHG_SITE_CORE_RELATED_PAGES_META, $page_ids );
	}

	if ( 'post' === $post->post_type && isset( $_POST['lmhg_article_order'] ) ) {
		$order = (int) wp_unslash( $_POST['lmhg_article_order'] );
		if ( (int) $post->menu_order !== $order ) {
			$saving_order = true;
			wp_update_post(
				array(
					'ID'         => $post_id,
					'menu_order' => $order,
				)
			);
			$saving_order = false;
		}
	}
}

/**
 * Saves the article card description field.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post Article post.
 */
function lmhg_site_core_save_article_card_description_meta( int $post_id, WP_Post $post ): void {
	if ( ! lmhg_site_core_is_article( $post ) || lmhg_site_core_should_skip_relationship_save( $post_id ) ) {
		return;
	}

	$nonce = isset( $_POST['lmhg_site_core_article_card_description_nonce'] )
		? sanitize_text_field( wp_unslash( $_POST['lmhg_site_core_article_card_description_nonce'] ) )
		: '';
	if ( ! wp_verify_nonce( $nonce, 'lmhg_site_core_save_article_card_description_meta' ) ) {
		return;
	}

	$description = isset( $_POST['lmhg_article_card_description'] )
		? sanitize_textarea_field( wp_unslash( $_POST['lmhg_article_card_description'] ) )
		: '';

	lmhg_site_core_update_or_delete_relationship_meta( $post_id, LMHG_SITE_CORE_ARTICLE_CARD_DESCRIPTION_META, $description );
}

/**
 * Saves team member fields.
 *
 * @param int     $post_id Team member post ID.
 * @param WP_Post $post Team member post.
 */
function lmhg_site_core_save_team_member_meta( int $post_id, WP_Post $post ): void {
	static $saving = false;

	if ( $saving || lmhg_site_core_should_skip_relationship_save( $post_id ) ) {
		return;
	}

	$nonce = isset( $_POST['lmhg_site_core_team_member_nonce'] )
		? sanitize_text_field( wp_unslash( $_POST['lmhg_site_core_team_member_nonce'] ) )
		: '';
	if ( ! wp_verify_nonce( $nonce, 'lmhg_site_core_save_team_member_meta' ) ) {
		return;
	}

	$fields = array(
		LMHG_SITE_CORE_TEAM_FIRST_META   => isset( $_POST['lmhg_team_first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['lmhg_team_first_name'] ) ) : '',
		LMHG_SITE_CORE_TEAM_LAST_META    => isset( $_POST['lmhg_team_last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['lmhg_team_last_name'] ) ) : '',
		LMHG_SITE_CORE_TEAM_CREDENTIALS  => isset( $_POST['lmhg_team_credentials'] ) ? sanitize_text_field( wp_unslash( $_POST['lmhg_team_credentials'] ) ) : '',
		LMHG_SITE_CORE_TEAM_HEADSHOT_URL => isset( $_POST['lmhg_team_headshot_url'] ) ? esc_url_raw( wp_unslash( $_POST['lmhg_team_headshot_url'] ) ) : '',
	);

	foreach ( $fields as $key => $value ) {
		lmhg_site_core_update_or_delete_relationship_meta( $post_id, $key, $value );
	}

	$order = isset( $_POST['lmhg_team_order'] ) ? (int) $_POST['lmhg_team_order'] : (int) $post->menu_order;
	if ( (int) $post->menu_order !== $order ) {
		$saving = true;
		wp_update_post(
			array(
				'ID'         => $post_id,
				'menu_order' => $order,
			)
		);
		$saving = false;
	}
}

/**
 * Determines whether an admin save should be ignored.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function lmhg_site_core_should_skip_relationship_save( int $post_id ): bool {
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return true;
	}

	return ! current_user_can( 'edit_post', $post_id );
}

/**
 * Updates or deletes empty relationship meta.
 *
 * @param int    $post_id Post ID.
 * @param string $key Meta key.
 * @param string $value Meta value.
 */
function lmhg_site_core_update_or_delete_relationship_meta( int $post_id, string $key, string $value ): void {
	if ( '' === $value ) {
		delete_post_meta( $post_id, $key );
		return;
	}

	update_post_meta( $post_id, $key, $value );
}

/**
 * Sanitizes arrays of page IDs.
 *
 * @param mixed $value Raw value.
 * @return int[]
 */
function lmhg_site_core_sanitize_page_id_array( mixed $value ): array {
	if ( is_string( $value ) ) {
		$value = '' === trim( $value ) ? array() : preg_split( '/[\s,]+/', $value );
	}

	if ( ! is_array( $value ) ) {
		return array();
	}

	$page_ids = array();
	foreach ( $value as $page_id ) {
		$page_id = absint( $page_id );
		if ( lmhg_site_core_is_relationship_target_page( $page_id ) ) {
			$page_ids[] = $page_id;
		}
	}

	return array_values( array_unique( $page_ids ) );
}

/**
 * Determines whether a Page can receive manually related articles.
 *
 * @param WP_Post|int|null $post Page object or ID.
 */
function lmhg_site_core_is_relationship_target_page( WP_Post|int|null $post ): bool {
	$post = get_post( $post );
	if ( ! $post instanceof WP_Post || 'page' !== $post->post_type ) {
		return false;
	}

	return in_array( get_page_template_slug( $post ), array( 'service-page', 'specialty-page' ), true );
}

/**
 * Gets related page IDs for an article.
 *
 * @param int $post_id Post ID.
 * @return int[]
 */
function lmhg_site_core_related_page_ids( int $post_id ): array {
	return lmhg_site_core_sanitize_page_id_array( get_post_meta( $post_id, LMHG_SITE_CORE_RELATED_PAGES_META, true ) );
}

/**
 * Returns available page choices for the article relationship picker.
 *
 * @return WP_Post[]
 */
function lmhg_site_core_page_choices(): array {
	$pages = get_posts(
		array(
			'post_type'              => 'page',
			'post_status'            => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page'         => -1,
			'orderby'                => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'order'                  => 'ASC',
			'meta_query'             => array(
				array(
					'key'     => '_wp_page_template',
					'value'   => array( 'service-page', 'specialty-page' ),
					'compare' => 'IN',
				),
			),
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
		)
	);

	return array_values( array_filter( $pages, 'lmhg_site_core_is_relationship_target_page' ) );
}

/**
 * Builds a readable hierarchical page label.
 *
 * @param WP_Post $page Page post.
 * @return string
 */
function lmhg_site_core_page_choice_label( WP_Post $page ): string {
	$ancestors = array_reverse( get_post_ancestors( $page ) );
	$labels    = array();

	foreach ( $ancestors as $ancestor_id ) {
		$labels[] = wp_strip_all_tags( get_the_title( $ancestor_id ) );
	}

	$labels[] = wp_strip_all_tags( get_the_title( $page ) );
	return implode( ' / ', array_filter( $labels ) );
}

/**
 * Registers relationship assets and queues them for pages that need them.
 */
function lmhg_site_core_register_relationship_assets(): void {
	wp_register_style(
		LMHG_SITE_CORE_RELATIONSHIP_STYLE,
		plugin_dir_url( dirname( __DIR__ ) . '/lmhg-site-core.php' ) . 'assets/css/relationships.css',
		array(),
		'0.1.20'
	);

	if ( lmhg_site_core_request_needs_relationship_assets() ) {
		wp_enqueue_style( LMHG_SITE_CORE_RELATIONSHIP_STYLE );
	}
}

/**
 * Ensures relationship CSS is queued.
 */
function lmhg_site_core_enqueue_relationship_assets(): void {
	if ( ! wp_style_is( LMHG_SITE_CORE_RELATIONSHIP_STYLE, 'registered' ) ) {
		lmhg_site_core_register_relationship_assets();
	}

	wp_enqueue_style( LMHG_SITE_CORE_RELATIONSHIP_STYLE );
}

/**
 * Determines whether the current request should load relationship CSS.
 */
function lmhg_site_core_request_needs_relationship_assets(): bool {
	if ( is_admin() || ! is_singular() ) {
		return false;
	}

	$post = get_post();
	if ( ! $post instanceof WP_Post ) {
		return false;
	}

	foreach ( array( 'lmhg_service_specialties', 'lmhg_faqs', 'lmhg_faq_index', 'lmhg_article_pages', 'lmhg_related_pages', 'lmhg_related_articles', 'lmhg_team' ) as $shortcode ) {
		if ( has_shortcode( $post->post_content, $shortcode ) ) {
			return true;
		}
	}

	if ( 'page' === $post->post_type ) {
		$template = get_page_template_slug( $post );

		return in_array( $template, array( 'service-page', 'specialty-page' ), true )
			|| has_term( '', LMHG_SITE_CORE_SPECIALTY_TAXONOMY, $post )
			|| has_term( '', LMHG_SITE_CORE_FAQ_SET_TAXONOMY, $post )
			|| 'faq-hub' === $template
			|| 'team-page' === $template
			|| in_array( $post->post_name, lmhg_site_core_team_page_slugs(), true );
	}

	return 'post' === $post->post_type && ! empty( lmhg_site_core_related_page_ids( $post->ID ) );
}

/**
 * Appends common relationship sections without requiring template edits.
 *
 * @param string $content Existing content.
 * @return string
 */
function lmhg_site_core_append_relationship_sections( string $content ): string {
	if ( is_admin() || ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$post = get_post();
	if ( ! $post instanceof WP_Post ) {
		return $content;
	}
	if (
		'page' === $post->post_type
		&& (
			( 'services-hub' === get_page_template_slug( $post ) && str_contains( (string) $post->post_content, 'wp2026-home-services' ) )
			|| ( 'specialties-hub' === get_page_template_slug( $post ) && str_contains( (string) $post->post_content, 'wp2026-specialty-grid' ) )
			|| ( 'location-access-page' === get_page_template_slug( $post ) && 'locations' === $post->post_name && str_contains( (string) $post->post_content, 'wp2026-location-grid' ) )
		)
	) {
		return $content;
	}

	$sections = array();
	$has_faqs             = lmhg_site_core_content_has_rendered_section( $content, 'lmhg-faqs' );
	$has_faq_index        = lmhg_site_core_content_has_rendered_section( $content, 'lmhg-faq-index' );
	$has_team             = lmhg_site_core_content_has_rendered_section( $content, 'lmhg-team-directory' );
	$has_related_articles = lmhg_site_core_content_has_rendered_section( $content, 'lmhg-related-articles' );

	if ( 'page' === $post->post_type ) {
		$template = get_page_template_slug( $post );

		if ( in_array( $template, array( 'service-page', 'specialty-page' ), true ) && ! $has_related_articles ) {
			$sections[] = lmhg_site_core_render_related_articles( $post->ID, 'Helpful Articles', 3 );
		}

		if ( ! $has_faqs && ! $has_faq_index && has_term( '', LMHG_SITE_CORE_FAQ_SET_TAXONOMY, $post ) ) {
			$sections[] = lmhg_site_core_render_faqs_for_page( $post->ID );
		}

		if ( 'faq-hub' === $template && ! $has_faq_index ) {
			$sections[] = lmhg_site_core_faq_index_shortcode(
				array(
					'heading' => 'Frequently asked questions',
				)
			);
		}

		if ( ! $has_team && ( 'team-page' === $template || in_array( $post->post_name, lmhg_site_core_team_page_slugs(), true ) ) ) {
			$sections[] = lmhg_site_core_render_team_members();
		}
	}

	$sections = array_filter( $sections );
	if ( empty( $sections ) ) {
		return $content;
	}

	$section_html = implode( "\n", $sections );
	if ( function_exists( 'lmhg_site_core_insert_before_page_cta' ) ) {
		return lmhg_site_core_insert_before_page_cta( $content, $section_html );
	}

	return $content . "\n" . $section_html;
}

/**
 * Detects whether a relationship section already rendered into content.
 *
 * @param string $content Rendered content.
 * @param string $class_name Section class to find.
 * @return bool
 */
function lmhg_site_core_content_has_rendered_section( string $content, string $class_name ): bool {
	return str_contains( $content, $class_name );
}

/**
 * Renders the service specialties shortcode.
 *
 * @param array<string,mixed>|string $atts Shortcode attributes.
 * @return string
 */
function lmhg_site_core_service_specialties_shortcode( array|string $atts = array() ): string {
	$atts = is_array( $atts ) ? $atts : array();
	$atts = shortcode_atts(
		array(
			'id'      => '',
			'heading' => 'Related Services',
		),
		$atts,
		'lmhg_service_specialties'
	);

	$post_id = '' !== (string) $atts['id'] ? absint( $atts['id'] ) : get_the_ID();
	return lmhg_site_core_render_service_specialties( (int) $post_id, (string) $atts['heading'] );
}

/**
 * Renders specialties assigned to a service page.
 *
 * @param int    $post_id Page ID.
 * @param string $heading Section heading.
 * @return string
 */
function lmhg_site_core_render_service_specialties( int $post_id, string $heading = 'Related Services' ): string {
	if ( $post_id <= 0 ) {
		return '';
	}

	$terms = wp_get_object_terms(
		$post_id,
		LMHG_SITE_CORE_SPECIALTY_TAXONOMY,
		array(
			'orderby' => 'term_order',
			'order'   => 'ASC',
		)
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return '';
	}

	$cards = array();
	foreach ( $terms as $term ) {
		if ( ! $term instanceof WP_Term ) {
			continue;
		}

		$url         = lmhg_site_core_specialty_term_page_url( $term );
		$name_markup = '' !== $url
			? sprintf( '<a href="%1$s">%2$s</a>', esc_url( $url ), esc_html( $term->name ) )
			: esc_html( $term->name );
		$description = lmhg_site_core_specialty_card_description( $term );

		$cards[] = sprintf(
			'<article class="lmhg-relationship-card"><h3>%1$s</h3>%2$s</article>',
			$name_markup,
			'' !== $description ? '<p>' . esc_html( $description ) . '</p>' : ''
		);
	}

	if ( empty( $cards ) ) {
		return '';
	}

	lmhg_site_core_enqueue_relationship_assets();

	return sprintf(
		'<section class="lmhg-relationship-section lmhg-service-specialties"><h2>%1$s</h2><div class="lmhg-relationship-grid">%2$s</div></section>',
		esc_html( $heading ),
		implode( '', $cards )
	);
}

/**
 * Gets the editable related-service card description for a specialty term.
 *
 * @param WP_Term $term Specialty term.
 * @return string
 */
function lmhg_site_core_specialty_card_description( WP_Term $term ): string {
	$description = lmhg_site_core_specialty_card_description_meta( $term );
	if ( '' === $description ) {
		$description = trim( wp_strip_all_tags( term_description( $term, LMHG_SITE_CORE_SPECIALTY_TAXONOMY ) ) );
	}

	return $description;
}

/**
 * Gets the raw related-service card description term meta.
 *
 * @param WP_Term $term Specialty term.
 * @return string
 */
function lmhg_site_core_specialty_card_description_meta( WP_Term $term ): string {
	return trim( wp_strip_all_tags( (string) get_term_meta( (int) $term->term_id, LMHG_SITE_CORE_SPECIALTY_CARD_DESCRIPTION_META, true ) ) );
}

/**
 * Gets the service icon attachment ID for a specialty/service term.
 *
 * @param WP_Term $term Specialty term.
 * @return int
 */
function lmhg_site_core_specialty_icon_id( WP_Term $term ): int {
	return absint( get_term_meta( (int) $term->term_id, LMHG_SITE_CORE_SPECIALTY_ICON_ID_META, true ) );
}

/**
 * Renders the admin preview for a service icon attachment.
 *
 * @param int $icon_id Attachment ID.
 * @return string
 */
function lmhg_site_core_specialty_icon_admin_preview( int $icon_id ): string {
	if ( $icon_id <= 0 ) {
		return '<span>No icon selected.</span>';
	}

	$image = wp_get_attachment_image(
		$icon_id,
		'thumbnail',
		false,
		array(
			'alt'   => '',
			'class' => 'lmhg-specialty-icon-preview__image',
		)
	);

	return '' !== $image ? $image : '<span>Selected attachment cannot be previewed as an image.</span>';
}

/**
 * Renders a process-section icon from the matching editable taxonomy term.
 *
 * @param WP_Post $page Page post.
 * @return string
 */
function lmhg_site_core_page_process_icon_markup( WP_Post $page ): string {
	$image = lmhg_site_core_related_page_term_icon_image( $page );

	if ( '' === $image && 'service-page' === get_page_template_slug( $page ) ) {
		$image = lmhg_site_core_home_service_icon_image( $page );
	}

	if ( '' === $image ) {
		return '';
	}

	return sprintf(
		'<div class="lmhg-page-process-icon" aria-hidden="true">%s</div>',
		$image
	);
}

/**
 * Back-compat wrapper for the original service-page icon helper.
 *
 * @param WP_Post $page Page post.
 * @return string
 */
function lmhg_site_core_service_page_icon_markup( WP_Post $page ): string {
	return lmhg_site_core_page_process_icon_markup( $page );
}

/**
 * Renders an icon image from the matching related-page taxonomy term.
 *
 * @param WP_Post $page Page post.
 * @return string
 */
function lmhg_site_core_related_page_term_icon_image( WP_Post $page ): string {
	$term = lmhg_site_core_specialty_term_for_page( $page );

	if ( $term instanceof WP_Term ) {
		$icon_id = lmhg_site_core_specialty_icon_id( $term );
		if ( $icon_id > 0 ) {
			$image = wp_get_attachment_image(
				$icon_id,
				'thumbnail',
				false,
				array(
					'alt'      => '',
					'class'    => 'lmhg-page-process-icon__image',
					'decoding' => 'async',
					'loading'  => 'lazy',
				)
			);

			return is_string( $image ) ? $image : '';
		}
	}

	return '';
}

/**
 * Renders the service icon already assigned to the matching homepage services card.
 *
 * @param WP_Post $page Page post.
 * @return string
 */
function lmhg_site_core_home_service_icon_image( WP_Post $page ): string {
	$icon = lmhg_site_core_home_service_icon_data( $page );
	if ( empty( $icon['src'] ) ) {
		return '';
	}

	return sprintf(
		'<img src="%1$s" alt="" class="lmhg-page-process-icon__image" decoding="async" loading="lazy" />',
		esc_url( $icon['src'] )
	);
}

/**
 * Gets the homepage services-card icon data for a service page.
 *
 * @param WP_Post $page Page post.
 * @return array{src:string,alt:string}
 */
function lmhg_site_core_home_service_icon_data( WP_Post $page ): array {
	$path = '/' . trim( (string) $page->post_name, '/' ) . '/';
	if ( '//' === $path ) {
		return array();
	}

	$front_page = lmhg_site_core_home_page_for_service_icons();
	if ( $front_page instanceof WP_Post ) {
		$icon = lmhg_site_core_home_service_icon_from_content( (string) $front_page->post_content, $path );
		if ( ! empty( $icon['src'] ) ) {
			return $icon;
		}
	}

	return lmhg_site_core_home_service_icon_fallback_data( (string) $page->post_name );
}

/**
 * Gets the homepage page that owns the service-card icon associations.
 *
 * @return WP_Post|null
 */
function lmhg_site_core_home_page_for_service_icons(): ?WP_Post {
	$front_id = (int) get_option( 'page_on_front' );
	if ( $front_id > 0 ) {
		$front_page = get_post( $front_id );
		if ( $front_page instanceof WP_Post ) {
			return $front_page;
		}
	}

	$pages = get_posts(
		array(
			'name'           => 'home',
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
		)
	);

	return isset( $pages[0] ) && $pages[0] instanceof WP_Post ? $pages[0] : null;
}

/**
 * Reads a service-card icon association from homepage block markup.
 *
 * @param string $content Homepage block content.
 * @param string $path Service page path.
 * @return array{src:string,alt:string}
 */
function lmhg_site_core_home_service_icon_from_content( string $content, string $path ): array {
	if ( '' === $content || ! str_contains( $content, 'wp2026-service-icon' ) ) {
		return array();
	}

	if ( ! preg_match_all( '/<figure\\b(?=[^>]*\\bwp2026-service-icon\\b)[\\s\\S]*?<\\/figure>/i', $content, $figures ) ) {
		return array();
	}

	foreach ( $figures[0] as $figure ) {
		if ( ! preg_match( '~<a\b[^>]*\bhref=["\']([^"\']+)["\']~i', $figure, $href_match ) ) {
			continue;
		}

		$href_path = (string) wp_parse_url( html_entity_decode( $href_match[1], ENT_QUOTES ), PHP_URL_PATH );
		$href_path = '/' . trim( $href_path, '/' ) . '/';
		if ( $href_path !== $path ) {
			continue;
		}

		if ( ! preg_match( '~<img\b[^>]*\bsrc=["\']([^"\']+)["\']~i', $figure, $src_match ) ) {
			continue;
		}

		$alt = '';
		if ( preg_match( '~<img\b[^>]*\balt=["\']([^"\']*)["\']~i', $figure, $alt_match ) ) {
			$alt = html_entity_decode( wp_strip_all_tags( $alt_match[1] ), ENT_QUOTES );
		}

		return array(
			'src' => esc_url_raw( html_entity_decode( $src_match[1], ENT_QUOTES ) ),
			'alt' => sanitize_text_field( $alt ),
		);
	}

	return array();
}

/**
 * Provides the current homepage service-card icon filenames when homepage markup is unavailable.
 *
 * @param string $slug Service page slug.
 * @return array{src:string,alt:string}
 */
function lmhg_site_core_home_service_icon_fallback_data( string $slug ): array {
	$icons = array(
		'individual-therapy'          => array( 'individual-counseling-card-icon-transparent.webp', 'Individual Counseling icon' ),
		'child-therapy'               => array( 'child-counseling-card-icon-transparent.webp', 'Child Therapy icon' ),
		'family-therapy'              => array( 'family-therapy-card-icon-transparent.webp', 'Family Therapy icon' ),
		'couples-counseling'          => array( 'couples-counseling-card-icon-transparent.webp', 'Couples Counseling icon' ),
		'family-court'                => array( 'court-ordered-card-icon-transparent.webp', 'Court Ordered Services icon' ),
		'community-based-services'    => array( 'community-based-services-card-icon-transparent.webp', 'Community-Based Services icon' ),
		'group-therapy'               => array( 'group-therapy-card-icon-transparent.webp', 'Group Therapy icon' ),
		'trauma-therapy'              => array( 'trauma-therapy-card-icon-transparent.webp', 'Trauma Therapy icon' ),
	);

	if ( ! isset( $icons[ $slug ] ) ) {
		return array();
	}

	$src = '/wp-content/uploads/2026/06/' . $icons[ $slug ][0];
	return array(
		'src' => $src,
		'alt' => $icons[ $slug ][1],
	);
}

/**
 * Finds the service/specialty taxonomy term that represents a page itself.
 *
 * @param WP_Post $page Page post.
 * @return WP_Term|null
 */
function lmhg_site_core_specialty_term_for_page( WP_Post $page ): ?WP_Term {
	$slug = trim( (string) $page->post_name );
	if ( '' === $slug ) {
		return null;
	}

	$term = get_term_by( 'slug', $slug, LMHG_SITE_CORE_SPECIALTY_TAXONOMY );
	return $term instanceof WP_Term ? $term : null;
}

/**
 * Gets the card description for a related service page.
 *
 * @param WP_Post $page Page post.
 * @return string
 */
function lmhg_site_core_related_page_card_description( WP_Post $page ): string {
	$term = get_term_by( 'slug', $page->post_name, LMHG_SITE_CORE_SPECIALTY_TAXONOMY );
	if ( $term instanceof WP_Term ) {
		$description = lmhg_site_core_specialty_card_description( $term );
		if ( '' !== $description ) {
			return $description;
		}
	}

	return lmhg_site_core_post_card_excerpt( $page );
}

/**
 * Finds a page by a site-relative URL path.
 *
 * @param string $url Page URL or path.
 * @return WP_Post|null
 */
function lmhg_site_core_page_by_url_path( string $url ): ?WP_Post {
	$path = (string) wp_parse_url( $url, PHP_URL_PATH );
	if ( '' === $path ) {
		$path = $url;
	}

	$page = get_page_by_path( trim( $path, '/' ), OBJECT, 'page' );
	return $page instanceof WP_Post ? $page : null;
}

/**
 * Finds a page URL that matches a specialty term slug.
 *
 * @param WP_Term $term Specialty term.
 * @return string
 */
function lmhg_site_core_specialty_term_page_url( WP_Term $term ): string {
	$page = get_page_by_path( $term->slug, OBJECT, 'page' );
	return $page instanceof WP_Post ? get_permalink( $page ) : '';
}

/**
 * Renders FAQ items for the current page or explicit FAQ set.
 *
 * @param array<string,mixed>|string $atts Shortcode attributes.
 * @return string
 */
function lmhg_site_core_faqs_shortcode( array|string $atts = array() ): string {
	$atts = is_array( $atts ) ? $atts : array();
	$atts = shortcode_atts(
		array(
			'id'      => '',
			'set'     => '',
			'count'   => '',
			'heading' => 'Common questions',
		),
		$atts,
		'lmhg_faqs'
	);

	$post_id = '' !== (string) $atts['id'] ? absint( $atts['id'] ) : get_the_ID();
	$limit   = '' !== (string) $atts['count'] ? max( 1, absint( $atts['count'] ) ) : -1;
	$term_ids = lmhg_site_core_faq_set_term_ids( (string) $atts['set'], (int) $post_id );

	return lmhg_site_core_render_faqs( $term_ids, (string) $atts['heading'], $limit );
}

/**
 * Renders FAQ items assigned to a page.
 *
 * @param int $post_id Page ID.
 * @return string
 */
function lmhg_site_core_render_faqs_for_page( int $post_id ): string {
	return lmhg_site_core_render_faqs( lmhg_site_core_faq_set_term_ids( '', $post_id ), 'Common Questions', -1 );
}

/**
 * Returns publishable FAQ question/answer pairs assigned to a page.
 *
 * @param int $post_id Page ID.
 * @return array<int,array{question:string,answer:string}>
 */
function lmhg_site_core_publishable_faq_items_for_page( int $post_id ): array {
	$term_ids = lmhg_site_core_faq_set_term_ids( '', $post_id );
	if ( empty( $term_ids ) ) {
		return array();
	}

	return lmhg_site_core_publishable_faq_items_from_posts( lmhg_site_core_query_faqs( $term_ids, -1 ) );
}

/**
 * Renders FAQ items for FAQ set terms.
 *
 * @param int[]  $term_ids FAQ set IDs.
 * @param string $heading Section heading.
 * @param int    $limit Query limit.
 * @return string
 */
function lmhg_site_core_render_faqs( array $term_ids, string $heading, int $limit ): string {
	if ( empty( $term_ids ) ) {
		return '';
	}

	$faqs = lmhg_site_core_query_faqs( $term_ids, $limit );
	if ( empty( $faqs ) ) {
		return '';
	}

	lmhg_site_core_enqueue_relationship_assets();

	return sprintf(
		'<section class="lmhg-relationship-section lmhg-faqs"><h2>%1$s</h2>%2$s</section>',
		esc_html( $heading ),
		lmhg_site_core_render_faq_items( $faqs )
	);
}

/**
 * Renders the master FAQ index shortcode.
 *
 * @param array<string,mixed>|string $atts Shortcode attributes.
 * @return string
 */
function lmhg_site_core_faq_index_shortcode( array|string $atts = array() ): string {
	$atts = is_array( $atts ) ? $atts : array();
	$atts = shortcode_atts(
		array(
			'heading' => 'FAQ index',
			'count'   => '',
		),
		$atts,
		'lmhg_faq_index'
	);

	$limit = '' !== (string) $atts['count'] ? max( 1, absint( $atts['count'] ) ) : -1;
	$terms = get_terms(
		array(
			'taxonomy'   => LMHG_SITE_CORE_FAQ_SET_TAXONOMY,
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return '';
	}

	$groups = array();
	foreach ( $terms as $term ) {
		if ( ! $term instanceof WP_Term ) {
			continue;
		}

		$faqs = lmhg_site_core_query_faqs( array( (int) $term->term_id ), $limit );
		if ( empty( $faqs ) ) {
			continue;
		}

		$description = trim( wp_strip_all_tags( term_description( $term, LMHG_SITE_CORE_FAQ_SET_TAXONOMY ) ) );
		$groups[] = sprintf(
			'<section class="lmhg-faq-index__group"><h3>%1$s</h3>%2$s%3$s</section>',
			esc_html( $term->name ),
			'' !== $description ? '<p>' . esc_html( $description ) . '</p>' : '',
			lmhg_site_core_render_faq_items( $faqs )
		);
	}

	if ( empty( $groups ) ) {
		return '';
	}

	lmhg_site_core_enqueue_relationship_assets();

	return sprintf(
		'<section class="lmhg-relationship-section lmhg-faq-index"><h2>%1$s</h2>%2$s</section>',
		esc_html( (string) $atts['heading'] ),
		implode( '', $groups )
	);
}

/**
 * Resolves FAQ set IDs from shortcode input or page taxonomy terms.
 *
 * @param string $value Comma-separated term IDs or slugs.
 * @param int    $post_id Page ID.
 * @return int[]
 */
function lmhg_site_core_faq_set_term_ids( string $value, int $post_id ): array {
	$term_ids = array();

	if ( '' !== trim( $value ) ) {
		foreach ( preg_split( '/\s*,\s*/', trim( $value ) ) as $token ) {
			if ( '' === $token ) {
				continue;
			}

			$term = is_numeric( $token )
				? get_term( absint( $token ), LMHG_SITE_CORE_FAQ_SET_TAXONOMY )
				: get_term_by( 'slug', sanitize_title( $token ), LMHG_SITE_CORE_FAQ_SET_TAXONOMY );
			if ( $term instanceof WP_Term ) {
				$term_ids[] = (int) $term->term_id;
			}
		}
	} elseif ( $post_id > 0 ) {
		$terms = wp_get_object_terms(
			$post_id,
			LMHG_SITE_CORE_FAQ_SET_TAXONOMY,
			array(
				'fields' => 'ids',
			)
		);
		if ( ! is_wp_error( $terms ) ) {
			$term_ids = array_map( 'absint', $terms );
		}
	}

	return array_values( array_unique( array_filter( $term_ids ) ) );
}

/**
 * Queries FAQ posts for a set of FAQ terms.
 *
 * @param int[] $term_ids FAQ set IDs.
 * @param int   $limit Query limit.
 * @return WP_Post[]
 */
function lmhg_site_core_query_faqs( array $term_ids, int $limit ): array {
	$query = new WP_Query(
		array(
			'post_type'              => LMHG_SITE_CORE_FAQ_POST_TYPE,
			'post_status'            => 'publish',
			'posts_per_page'         => $limit,
			'orderby'                => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'order'                  => 'ASC',
			'tax_query'              => array(
				array(
					'taxonomy' => LMHG_SITE_CORE_FAQ_SET_TAXONOMY,
					'field'    => 'term_id',
					'terms'    => $term_ids,
				),
			),
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	return $query->posts;
}

/**
 * Renders FAQ details items.
 *
 * @param WP_Post[] $faqs FAQ posts.
 * @return string
 */
function lmhg_site_core_render_faq_items( array $faqs ): string {
	$items = array();
	foreach ( $faqs as $faq ) {
		if ( ! $faq instanceof WP_Post ) {
			continue;
		}

		$question = trim( wp_strip_all_tags( get_the_title( $faq ) ) );
		$answer   = lmhg_site_core_render_post_body( $faq );
		if ( '' === $question || '' === trim( wp_strip_all_tags( $answer ) ) ) {
			continue;
		}

		$items[] = sprintf(
			'<details class="lmhg-faq-item"><summary>%1$s</summary><div class="lmhg-faq-item__answer">%2$s</div></details>',
			esc_html( $question ),
			$answer
		);
	}

	return implode( '', $items );
}

/**
 * Returns publishable FAQ question/answer pairs from FAQ posts.
 *
 * @param WP_Post[] $faqs FAQ posts.
 * @return array<int,array{question:string,answer:string}>
 */
function lmhg_site_core_publishable_faq_items_from_posts( array $faqs ): array {
	$items = array();
	foreach ( $faqs as $faq ) {
		if ( ! $faq instanceof WP_Post ) {
			continue;
		}

		$question = trim( wp_strip_all_tags( get_the_title( $faq ) ) );
		$answer   = trim( wp_strip_all_tags( lmhg_site_core_render_post_body( $faq ) ) );
		if ( '' === $question || '' === $answer ) {
			continue;
		}

		$items[] = array(
			'question' => $question,
			'answer'   => $answer,
		);
	}

	return $items;
}

/**
 * Renders related pages attached to the current article.
 *
 * @param array<string,mixed>|string $atts Shortcode attributes.
 * @return string
 */
function lmhg_site_core_article_pages_shortcode( array|string $atts = array() ): string {
	$atts = is_array( $atts ) ? $atts : array();
	$atts = shortcode_atts(
		array(
			'id'      => '',
			'heading' => 'Related pages',
		),
		$atts,
		'lmhg_article_pages'
	);

	$post_id = '' !== (string) $atts['id'] ? absint( $atts['id'] ) : get_the_ID();
	return lmhg_site_core_render_article_pages( (int) $post_id, (string) $atts['heading'] );
}

/**
 * Renders related pages for the current page or article context.
 *
 * @param array<string,mixed>|string $atts Shortcode attributes.
 * @return string
 */
function lmhg_site_core_related_pages_shortcode( array|string $atts = array() ): string {
	$atts = is_array( $atts ) ? $atts : array();
	$atts = shortcode_atts(
		array(
			'id'      => '',
			'heading' => 'Related Pages',
		),
		$atts,
		'lmhg_related_pages'
	);

	$post_id = '' !== (string) $atts['id'] ? absint( $atts['id'] ) : get_the_ID();
	if ( $post_id <= 0 ) {
		return '';
	}

	if ( 'page' === get_post_type( $post_id ) ) {
		return lmhg_site_core_render_taxonomy_related_pages( (int) $post_id, (string) $atts['heading'] );
	}

	if ( 'post' === get_post_type( $post_id ) ) {
		return lmhg_site_core_render_article_pages( (int) $post_id, (string) $atts['heading'] );
	}

	return '';
}

/**
 * Renders page-to-page relationships from explicit service/specialty taxonomy links.
 *
 * @param int    $post_id Page post ID.
 * @param string $heading Section heading.
 * @return string
 */
function lmhg_site_core_render_taxonomy_related_pages( int $post_id, string $heading = 'Related Pages' ): string {
	$page = get_post( $post_id );
	if ( ! $page instanceof WP_Post || 'page' !== $page->post_type ) {
		return '';
	}

	$related = lmhg_site_core_taxonomy_related_pages( $page );
	if ( empty( $related ) ) {
		return '';
	}

	return lmhg_site_core_render_related_page_cards( $related, $heading, 'lmhg-related-pages' );
}

/**
 * Renders related page cards with page-specific descriptions.
 *
 * @param WP_Post[] $pages Pages to render.
 * @param string    $heading Section heading.
 * @param string    $class_name Extra section class.
 * @return string
 */
function lmhg_site_core_render_related_page_cards( array $pages, string $heading, string $class_name ): string {
	$cards = array();
	foreach ( $pages as $page ) {
		if ( ! $page instanceof WP_Post || 'page' !== $page->post_type || 'publish' !== $page->post_status ) {
			continue;
		}

		$description = lmhg_site_core_related_page_card_description( $page );
		$cards[] = sprintf(
			'<article class="lmhg-relationship-card"><h3><a href="%1$s">%2$s</a></h3>%3$s</article>',
			esc_url( get_permalink( $page ) ),
			esc_html( wp_strip_all_tags( get_the_title( $page ) ) ),
			'' !== $description ? '<p>' . esc_html( $description ) . '</p>' : ''
		);
	}

	if ( empty( $cards ) ) {
		return '';
	}

	lmhg_site_core_enqueue_relationship_assets();

	$count_class = count( $cards ) > 3
		? 'lmhg-relationship-section--count-many'
		: 'lmhg-relationship-section--count-' . count( $cards );
	$class_name  = trim( $class_name . ' ' . $count_class );

	return sprintf(
		'<section class="lmhg-relationship-section %1$s"><h2>%2$s</h2><div class="lmhg-relationship-grid">%3$s</div></section>',
		esc_attr( $class_name ),
		esc_html( $heading ),
		implode( '', $cards )
	);
}

/**
 * Gets taxonomy-related pages for a service or specialty page.
 *
 * @param WP_Post $page Current page.
 * @return WP_Post[]
 */
function lmhg_site_core_taxonomy_related_pages( WP_Post $page ): array {
	$related = array();

	$terms = wp_get_object_terms(
		$page->ID,
		LMHG_SITE_CORE_SPECIALTY_TAXONOMY,
		array(
			'orderby' => 'term_order',
			'order'   => 'ASC',
		)
	);

	if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
		foreach ( $terms as $term ) {
			if ( ! $term instanceof WP_Term ) {
				continue;
			}

			$related_page = get_page_by_path( $term->slug, OBJECT, 'page' );
			if ( $related_page instanceof WP_Post && 'publish' === $related_page->post_status ) {
				$related[ $related_page->ID ] = $related_page;
			}
		}
	}

	$own_term = lmhg_site_core_specialty_term_for_page( $page );
	if ( $own_term instanceof WP_Term ) {
		$parents = get_posts(
			array(
				'post_type'              => 'page',
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'orderby'                => array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				),
				'order'                  => 'ASC',
				'tax_query'              => array(
					array(
						'taxonomy' => LMHG_SITE_CORE_SPECIALTY_TAXONOMY,
						'field'    => 'term_id',
						'terms'    => array( (int) $own_term->term_id ),
					),
				),
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
			)
		);

		foreach ( $parents as $parent ) {
			if ( $parent instanceof WP_Post && $parent->ID !== $page->ID ) {
				$related[ $parent->ID ] = $parent;
			}
		}
	}

	unset( $related[ $page->ID ] );

	return array_values( $related );
}

/**
 * Renders related page cards for an article.
 *
 * @param int    $post_id Article post ID.
 * @param string $heading Section heading.
 * @return string
 */
function lmhg_site_core_render_article_pages( int $post_id, string $heading = 'Related pages' ): string {
	$page_ids = lmhg_site_core_related_page_ids( $post_id );
	if ( empty( $page_ids ) ) {
		return '';
	}

	$pages = array_values(
		array_filter(
			array_map(
				static fn( int $page_id ): ?WP_Post => get_post( $page_id ),
				$page_ids
			),
			static fn( mixed $page ): bool => $page instanceof WP_Post && 'page' === $page->post_type && 'publish' === $page->post_status
		)
	);

	return lmhg_site_core_render_post_cards( $pages, $heading, 'lmhg-article-pages' );
}

/**
 * Queries manually related published articles for a page.
 *
 * @param int $page_id Related page ID.
 * @param int $limit Maximum articles to return.
 * @return WP_Post[]
 */
function lmhg_site_core_query_related_articles( int $page_id, int $limit = 3 ): array {
	if ( ! lmhg_site_core_is_relationship_target_page( $page_id ) ) {
		return array();
	}

	$query = new WP_Query(
		array(
			'post_type'              => 'post',
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'orderby'                => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'order'                  => 'ASC',
			'meta_query'             => array(
				array(
					'key'     => LMHG_SITE_CORE_RELATED_PAGES_META,
					'value'   => 'i:' . $page_id . ';',
					'compare' => 'LIKE',
				),
			),
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
		)
	);

	return array_slice( $query->posts, 0, min( 12, max( 1, $limit ) ) );
}

/**
 * Renders manually related articles for a page.
 *
 * @param int    $page_id Related page ID.
 * @param string $heading Section heading.
 * @param int    $limit Maximum articles to render.
 */
function lmhg_site_core_render_related_articles( int $page_id, string $heading = 'Helpful Articles', int $limit = 3 ): string {
	return lmhg_site_core_render_post_cards(
		lmhg_site_core_query_related_articles( $page_id, $limit ),
		$heading,
		'lmhg-related-articles'
	);
}

/**
 * Renders posts related to the current page.
 *
 * @param array<string,mixed>|string $atts Shortcode attributes.
 * @return string
 */
function lmhg_site_core_related_articles_shortcode( array|string $atts = array() ): string {
	$atts = is_array( $atts ) ? $atts : array();
	$atts = shortcode_atts(
		array(
			'id'      => '',
			'count'   => '6',
			'heading' => 'Related articles',
		),
		$atts,
		'lmhg_related_articles'
	);

	$page_id = '' !== (string) $atts['id'] ? absint( $atts['id'] ) : get_the_ID();
	if ( $page_id <= 0 || 'page' !== get_post_type( $page_id ) ) {
		return '';
	}

	return lmhg_site_core_render_related_articles(
		$page_id,
		(string) $atts['heading'],
		min( 12, max( 1, absint( $atts['count'] ) ) )
	);
}

/**
 * Renders team member cards.
 *
 * @param array<string,mixed>|string $atts Shortcode attributes.
 * @return string
 */
function lmhg_site_core_team_shortcode( array|string $atts = array() ): string {
	$atts = is_array( $atts ) ? $atts : array();
	$atts = shortcode_atts(
		array(
			'count'   => '',
			'heading' => 'Our team',
		),
		$atts,
		'lmhg_team'
	);

	$limit = '' !== (string) $atts['count'] ? max( 1, absint( $atts['count'] ) ) : -1;
	return lmhg_site_core_render_team_members( (string) $atts['heading'], $limit );
}

/**
 * Renders team members.
 *
 * @param string $heading Section heading.
 * @param int    $limit Query limit.
 * @return string
 */
function lmhg_site_core_render_team_members( string $heading = 'Our team', int $limit = -1 ): string {
	$query = new WP_Query(
		array(
			'post_type'              => LMHG_SITE_CORE_TEAM_POST_TYPE,
			'post_status'            => 'publish',
			'posts_per_page'         => $limit,
			'orderby'                => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
		)
	);

	if ( empty( $query->posts ) ) {
		return '';
	}

	$cards = array();
	foreach ( $query->posts as $member ) {
		if ( ! $member instanceof WP_Post ) {
			continue;
		}

		$name        = lmhg_site_core_team_member_name( $member );
		$credentials = trim( (string) get_post_meta( $member->ID, LMHG_SITE_CORE_TEAM_CREDENTIALS, true ) );
		$headshot    = lmhg_site_core_team_member_headshot_url( $member );
		$thumbnail_id = (int) get_post_thumbnail_id( $member );
		$image_attributes = array(
			'alt'      => $name,
			'loading'  => empty( $cards ) ? 'eager' : 'lazy',
			'decoding' => 'async',
		);
		if ( empty( $cards ) ) {
			$image_attributes['fetchpriority'] = 'high';
		}
		$headshot_html = $thumbnail_id > 0
			? wp_get_attachment_image(
				$thumbnail_id,
				'medium',
				false,
				$image_attributes
			)
			: ( '' !== $headshot ? '<img src="' . esc_url( $headshot ) . '" alt="' . esc_attr( $name ) . '" loading="' . ( empty( $cards ) ? 'eager' : 'lazy' ) . '" decoding="async"' . ( empty( $cards ) ? ' fetchpriority="high"' : '' ) . ' />' : '' );

		$cards[] = sprintf(
			'<article class="lmhg-team-card">%1$s<div class="lmhg-team-card__body"><h3>%2$s</h3>%3$s</div></article>',
			$headshot_html,
			esc_html( $name ),
			'' !== $credentials ? '<p>' . esc_html( $credentials ) . '</p>' : ''
		);
	}

	if ( empty( $cards ) ) {
		return '';
	}

	lmhg_site_core_enqueue_relationship_assets();

	return sprintf(
		'<section class="lmhg-relationship-section lmhg-team-directory"><h2>%1$s</h2><div class="lmhg-team-grid">%2$s</div></section>',
		esc_html( $heading ),
		implode( '', $cards )
	);
}

/**
 * Builds the team member display name.
 *
 * @param WP_Post $member Team member post.
 * @return string
 */
function lmhg_site_core_team_member_name( WP_Post $member ): string {
	$first_name = trim( (string) get_post_meta( $member->ID, LMHG_SITE_CORE_TEAM_FIRST_META, true ) );
	$last_name  = trim( (string) get_post_meta( $member->ID, LMHG_SITE_CORE_TEAM_LAST_META, true ) );
	$name       = trim( $first_name . ' ' . $last_name );

	return '' !== $name ? $name : wp_strip_all_tags( get_the_title( $member ) );
}

/**
 * Gets the team member headshot URL.
 *
 * @param WP_Post $member Team member post.
 * @return string
 */
function lmhg_site_core_team_member_headshot_url( WP_Post $member ): string {
	$headshot = trim( (string) get_post_meta( $member->ID, LMHG_SITE_CORE_TEAM_HEADSHOT_URL, true ) );
	if ( '' !== $headshot ) {
		return $headshot;
	}

	$thumbnail = get_the_post_thumbnail_url( $member, 'medium' );
	return is_string( $thumbnail ) ? $thumbnail : '';
}

/**
 * Returns page slugs that auto-render the team directory.
 *
 * @return string[]
 */
function lmhg_site_core_team_page_slugs(): array {
	return array( 'meet-the-team', 'team', 'our-team', 'team-members' );
}

/**
 * Renders a set of post cards.
 *
 * @param WP_Post[] $posts Posts to render.
 * @param string    $heading Section heading.
 * @param string    $class_name Extra section class.
 * @return string
 */
function lmhg_site_core_render_post_cards( array $posts, string $heading, string $class_name ): string {
	$cards = array();
	foreach ( $posts as $post ) {
		if ( ! $post instanceof WP_Post ) {
			continue;
		}

		$excerpt = lmhg_site_core_post_card_excerpt( $post );
		$cards[] = sprintf(
			'<article class="lmhg-relationship-card"><h3><a href="%1$s">%2$s</a></h3>%3$s</article>',
			esc_url( get_permalink( $post ) ),
			esc_html( wp_strip_all_tags( get_the_title( $post ) ) ),
			'' !== $excerpt ? '<p>' . esc_html( $excerpt ) . '</p>' : ''
		);
	}

	if ( empty( $cards ) ) {
		return '';
	}

	lmhg_site_core_enqueue_relationship_assets();

	return sprintf(
		'<section class="lmhg-relationship-section %1$s"><h2>%2$s</h2><div class="lmhg-relationship-grid">%3$s</div></section>',
		esc_attr( $class_name ),
		esc_html( $heading ),
		implode( '', $cards )
	);
}

/**
 * Builds a short card excerpt.
 *
 * @param WP_Post $post Post.
 * @return string
 */
function lmhg_site_core_post_card_excerpt( WP_Post $post ): string {
	$description = trim( (string) get_post_meta( $post->ID, LMHG_SITE_CORE_ARTICLE_CARD_DESCRIPTION_META, true ) );
	if ( '' === $description ) {
		$description = trim( (string) get_post_meta( $post->ID, '_lmhg_meta_description', true ) );
	}
	if ( '' === $description && lmhg_site_core_is_article( $post ) ) {
		$description = trim( wp_strip_all_tags( get_the_excerpt( $post ) ) );
	}
	if ( '' === $description ) {
		$description = lmhg_site_core_post_card_content_excerpt( $post );
	}

	return wp_trim_words( $description, 24, '...' );
}

/**
 * Builds a card excerpt from block content while skipping breadcrumbs.
 *
 * @param WP_Post $post Post.
 * @return string
 */
function lmhg_site_core_post_card_content_excerpt( WP_Post $post ): string {
	$paragraphs = lmhg_site_core_post_content_paragraphs( (string) $post->post_content );
	foreach ( $paragraphs as $paragraph ) {
		$paragraph = preg_replace( '/\s+Reach Out\s+What To Expect\b.*$/i', '', $paragraph );
		$paragraph = preg_replace( '/\s+Home\s*\/\s*Mental Health Services\b.*$/i', '', (string) $paragraph );
		$paragraph = trim( (string) $paragraph );
		if ( '' !== $paragraph ) {
			return $paragraph;
		}
	}

	return trim( wp_strip_all_tags( $post->post_content ) );
}

/**
 * Extracts visible non-breadcrumb paragraph text from block content.
 *
 * @param string $content Post content.
 * @return string[]
 */
function lmhg_site_core_post_content_paragraphs( string $content ): array {
	if ( '' === trim( $content ) ) {
		return array();
	}

	$paragraphs = array();
	foreach ( parse_blocks( $content ) as $block ) {
		lmhg_site_core_collect_paragraph_text( $block, $paragraphs );
	}

	return array_values( array_filter( $paragraphs ) );
}

/**
 * Recursively collects paragraph text from a block.
 *
 * @param array<string,mixed> $block Block data.
 * @param string[]           $paragraphs Collected paragraph text.
 * @return void
 */
function lmhg_site_core_collect_paragraph_text( array $block, array &$paragraphs ): void {
	$class_name = isset( $block['attrs']['className'] ) ? (string) $block['attrs']['className'] : '';
	if ( str_contains( ' ' . $class_name . ' ', ' wp2026-breadcrumbs ' ) ) {
		return;
	}

	if ( 'core/paragraph' === ( $block['blockName'] ?? '' ) ) {
		$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) ( $block['innerHTML'] ?? '' ) ) ) );
		if ( '' !== $text ) {
			$paragraphs[] = $text;
		}
	}

	foreach ( (array) ( $block['innerBlocks'] ?? array() ) as $inner_block ) {
		if ( is_array( $inner_block ) ) {
			lmhg_site_core_collect_paragraph_text( $inner_block, $paragraphs );
		}
	}
}

/**
 * Renders stored post body content for embedded FAQ answers.
 *
 * @param WP_Post $post Post.
 * @return string
 */
function lmhg_site_core_render_post_body( WP_Post $post ): string {
	$content = (string) get_post_field( 'post_content', $post->ID );
	if ( '' === trim( $content ) ) {
		$content = (string) get_post_field( 'post_excerpt', $post->ID );
	}
	if ( '' === trim( $content ) ) {
		return '';
	}

	$content = has_blocks( $content ) ? do_blocks( $content ) : wpautop( $content );
	return wp_kses_post( $content );
}
