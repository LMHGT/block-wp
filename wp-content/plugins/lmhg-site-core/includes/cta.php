<?php
/**
 * Shared CTA variants and global Reach Out button behavior.
 *
 * @package LMHGSiteCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const LMHG_SITE_CORE_CTA_TAXONOMY         = 'lmhg_cta_variant';
const LMHG_SITE_CORE_CTA_DEFAULT_SLUG      = 'default-cta';
const LMHG_SITE_CORE_CTA_NONE_SLUG         = 'no-lower-cta';
const LMHG_SITE_CORE_CTA_OPTION            = 'lmhg_site_core_reach_out';
const LMHG_SITE_CORE_CTA_DEFAULT_URL       = 'https://intakeq.com/new/g91Z8x/bjxuno';
const LMHG_SITE_CORE_CTA_MIGRATION_OPTION  = 'lmhg_cta_taxonomy_migration_version';
const LMHG_SITE_CORE_CTA_MIGRATION_VERSION = '2026-07-11-cta-taxonomy-v7';
const LMHG_SITE_CORE_CTA_CLEANUP_OPTION    = 'lmhg_cta_content_cleanup_version';
const LMHG_SITE_CORE_CTA_CLEANUP_VERSION   = '2026-07-11-cta-content-v6';

add_action( 'init', 'lmhg_site_core_register_cta_taxonomy', 7 );
add_action( 'init', 'lmhg_site_core_register_cta_term_meta', 8 );
add_action( 'init', 'lmhg_site_core_register_reach_out_block', 20 );
add_action( 'enqueue_block_editor_assets', 'lmhg_site_core_enqueue_reach_out_editor_block' );
add_action( 'init', 'lmhg_site_core_seed_cta_terms', 26 );
add_action( 'init', 'lmhg_site_core_run_cta_migration', 43 );
add_action( 'admin_init', 'lmhg_site_core_run_admin_cta_content_cleanup', 99 );
add_action( 'transition_post_status', 'lmhg_site_core_assign_default_cta_to_new_page', 10, 3 );
add_action( 'set_object_terms', 'lmhg_site_core_enforce_single_cta_term', 10, 6 );
add_action( LMHG_SITE_CORE_CTA_TAXONOMY . '_add_form_fields', 'lmhg_site_core_render_cta_add_fields' );
add_action( LMHG_SITE_CORE_CTA_TAXONOMY . '_edit_form_fields', 'lmhg_site_core_render_cta_edit_fields' );
add_action( 'created_' . LMHG_SITE_CORE_CTA_TAXONOMY, 'lmhg_site_core_save_cta_term_fields' );
add_action( 'edited_' . LMHG_SITE_CORE_CTA_TAXONOMY, 'lmhg_site_core_save_cta_term_fields' );
add_filter( 'rest_pre_dispatch', 'lmhg_site_core_protect_cta_system_terms_rest', 10, 3 );
add_action( 'pre_delete_term', 'lmhg_site_core_protect_cta_system_terms', 10, 2 );
add_filter( LMHG_SITE_CORE_CTA_TAXONOMY . '_row_actions', 'lmhg_site_core_filter_protected_cta_row_actions', 10, 2 );
add_filter( 'the_content', 'lmhg_site_core_resolve_reach_out_links', 39 );
add_filter( 'the_content', 'lmhg_site_core_append_lower_cta', 40 );

/** Registers the page CTA variant taxonomy. */
function lmhg_site_core_register_cta_taxonomy(): void {
	register_taxonomy(
		LMHG_SITE_CORE_CTA_TAXONOMY,
		array( 'page' ),
		array(
			'labels'             => array(
				'name'          => 'CTA Variants',
				'singular_name' => 'CTA Variant',
				'add_new_item'  => 'Add CTA Variant',
				'edit_item'     => 'Edit CTA Variant',
				'not_found'     => 'No CTA variants found',
			),
			'public'             => false,
			'publicly_queryable' => false,
			'hierarchical'       => false,
			'show_ui'            => true,
			'show_in_menu'       => false,
			'show_admin_column'  => false,
			'show_in_rest'       => true,
			'show_tagcloud'      => false,
			'rewrite'            => false,
			'query_var'          => false,
			'capabilities'       => array(
				'manage_terms' => 'manage_options',
				'edit_terms'   => 'manage_options',
				'delete_terms' => 'manage_options',
				'assign_terms' => 'manage_options',
			),
		)
	);
}

/** Registers structured metadata stored on CTA terms. */
function lmhg_site_core_register_cta_term_meta(): void {
	$auth = static fn(): bool => current_user_can( 'manage_options' );
	foreach ( lmhg_site_core_cta_term_meta_schema() as $key => $schema ) {
		register_term_meta(
			LMHG_SITE_CORE_CTA_TAXONOMY,
			$key,
			array(
				'type'              => $schema['type'],
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => $schema['sanitize'],
				'auth_callback'     => $auth,
			)
		);
	}
}

/** @return array<string,array{type:string,sanitize:callable|string}> */
function lmhg_site_core_cta_term_meta_schema(): array {
	return array(
		'_lmhg_cta_title'            => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
		'_lmhg_cta_description'      => array( 'type' => 'string', 'sanitize' => 'sanitize_textarea_field' ),
		'_lmhg_cta_lifecycle'        => array( 'type' => 'string', 'sanitize' => 'lmhg_site_core_sanitize_cta_lifecycle' ),
		'_lmhg_cta_experiment_label' => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
		'_lmhg_cta_system'           => array( 'type' => 'boolean', 'sanitize' => 'rest_sanitize_boolean' ),
	);
}

/** Sanitizes the CTA lifecycle. */
function lmhg_site_core_sanitize_cta_lifecycle( mixed $value ): string {
	$value = sanitize_key( (string) $value );
	return in_array( $value, array( 'draft', 'active', 'retired' ), true ) ? $value : 'draft';
}

/** Seeds the protected control and opt-out terms. */
function lmhg_site_core_seed_cta_terms(): void {
	if ( ! taxonomy_exists( LMHG_SITE_CORE_CTA_TAXONOMY ) ) {
		return;
	}

	$terms = array(
		LMHG_SITE_CORE_CTA_DEFAULT_SLUG => array(
			'name'        => 'Default CTA',
			'title'       => 'Ready To Reach Out?',
			'description' => "We're ready to help, use the Reach Out form to give us some basic information and we'll contact you ASAP.",
			'lifecycle'   => 'active',
		),
		LMHG_SITE_CORE_CTA_NONE_SLUG => array(
			'name'        => 'No Lower CTA',
			'title'       => '',
			'description' => '',
			'lifecycle'   => 'active',
		),
	);

	foreach ( $terms as $slug => $data ) {
		$term = get_term_by( 'slug', $slug, LMHG_SITE_CORE_CTA_TAXONOMY );
		if ( ! $term instanceof WP_Term ) {
			$created = wp_insert_term( $data['name'], LMHG_SITE_CORE_CTA_TAXONOMY, array( 'slug' => $slug ) );
			if ( is_wp_error( $created ) ) {
				continue;
			}
			$term = get_term( (int) $created['term_id'], LMHG_SITE_CORE_CTA_TAXONOMY );
		}
		if ( ! $term instanceof WP_Term ) {
			continue;
		}

		if ( '' === (string) get_term_meta( $term->term_id, '_lmhg_cta_lifecycle', true ) ) {
			update_term_meta( $term->term_id, '_lmhg_cta_title', $data['title'] );
			update_term_meta( $term->term_id, '_lmhg_cta_description', $data['description'] );
			update_term_meta( $term->term_id, '_lmhg_cta_lifecycle', $data['lifecycle'] );
		}
		update_term_meta( $term->term_id, '_lmhg_cta_system', '1' );
	}
}

/** Returns the stored global Reach Out configuration with safe defaults. */
function lmhg_site_core_reach_out_settings(): array {
	$stored = get_option( LMHG_SITE_CORE_CTA_OPTION, array() );
	$stored = is_array( $stored ) ? $stored : array();
	return array(
		'label' => sanitize_text_field( (string) ( $stored['label'] ?? 'Reach Out' ) ),
		'url'   => esc_url_raw( (string) ( $stored['url'] ?? LMHG_SITE_CORE_CTA_DEFAULT_URL ) ),
	);
}

/** Resolves the approved legacy destination through the global setting. */
function lmhg_site_core_resolve_reach_out_url( string $url ): string {
	$url = trim( $url );
	if ( LMHG_SITE_CORE_CTA_DEFAULT_URL !== $url ) {
		return $url;
	}

	$settings = lmhg_site_core_reach_out_settings();
	return '' !== $settings['url'] ? $settings['url'] : LMHG_SITE_CORE_CTA_DEFAULT_URL;
}

/** Keeps recognized intake links in stored HTML aligned with the global destination. */
function lmhg_site_core_resolve_reach_out_links( string $content ): string {
	if ( ! str_contains( $content, LMHG_SITE_CORE_CTA_DEFAULT_URL ) ) {
		return $content;
	}

	return str_replace( LMHG_SITE_CORE_CTA_DEFAULT_URL, esc_url( lmhg_site_core_resolve_reach_out_url( LMHG_SITE_CORE_CTA_DEFAULT_URL ) ), $content );
}

/** Registers the reusable server-rendered global button. */
function lmhg_site_core_register_reach_out_block(): void {
	register_block_type(
		'lmhg/reach-out-button',
		array(
			'api_version'     => 3,
			'render_callback' => 'lmhg_site_core_render_reach_out_block',
			'attributes'      => array(
				'className' => array( 'type' => 'string', 'default' => '' ),
			),
		)
	);
}

/** Registers the editor-side companion for the server-rendered button block. */
function lmhg_site_core_enqueue_reach_out_editor_block(): void {
	$asset_path = dirname( __DIR__ ) . '/assets/reach-out-button-editor.js';
	if ( ! is_readable( $asset_path ) ) {
		return;
	}

	wp_enqueue_script(
		'lmhg-reach-out-button-editor',
		plugins_url( 'assets/reach-out-button-editor.js', dirname( __DIR__ ) . '/lmhg-site-core.php' ),
		array( 'wp-block-editor', 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-server-side-render' ),
		(string) filemtime( $asset_path ),
		true
	);
}

/** Renders a core-button-compatible Reach Out block. */
function lmhg_site_core_render_reach_out_block( array $attributes = array() ): string {
	$settings = lmhg_site_core_reach_out_settings();
	if ( '' === $settings['label'] || '' === $settings['url'] ) {
		return '';
	}

	$class = trim( 'wp-block-button lmhg-global-reach-out ' . sanitize_html_class( (string) ( $attributes['className'] ?? '' ) ) );
	$rel   = wp_parse_url( $settings['url'], PHP_URL_HOST ) === wp_parse_url( home_url( '/' ), PHP_URL_HOST ) ? '' : ' rel="noopener"';

	return sprintf(
		'<div class="%1$s"><a class="wp-block-button__link wp-element-button" href="%2$s"%3$s>%4$s</a></div>',
		esc_attr( $class ),
		esc_url( $settings['url'] ),
		$rel,
		esc_html( $settings['label'] )
	);
}

/** Gets the single assigned CTA term. */
function lmhg_site_core_page_cta_term( int $post_id ): ?WP_Term {
	$terms = wp_get_object_terms( $post_id, LMHG_SITE_CORE_CTA_TAXONOMY );
	if ( is_wp_error( $terms ) || 1 !== count( $terms ) ) {
		return null;
	}
	return $terms[0] instanceof WP_Term ? $terms[0] : null;
}

/** Appends the resolved CTA after all plugin-managed page sections. */
function lmhg_site_core_append_lower_cta( string $content ): string {
	if ( is_admin() || ! is_singular( 'page' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$content = lmhg_site_core_remove_legacy_lower_cta( $content );
	$term    = lmhg_site_core_page_cta_term( (int) get_the_ID() );
	if ( ! $term instanceof WP_Term || LMHG_SITE_CORE_CTA_NONE_SLUG === $term->slug ) {
		return $content;
	}
	if ( 'active' !== (string) get_term_meta( $term->term_id, '_lmhg_cta_lifecycle', true ) ) {
		return $content;
	}

	$title       = trim( (string) get_term_meta( $term->term_id, '_lmhg_cta_title', true ) );
	$description = trim( (string) get_term_meta( $term->term_id, '_lmhg_cta_description', true ) );
	if ( '' === $title || '' === $description ) {
		return $content;
	}

	$cta = sprintf(
		'<section class="wp-block-group wp2026-page-cta lmhg-taxonomy-cta" data-lmhg-cta-variant="%1$s"><h2 class="wp-block-heading wp2026-section-title">%2$s</h2><p>%3$s</p><div class="wp-block-buttons wp2026-closing-actions">%4$s</div></section>',
		esc_attr( $term->slug ),
		esc_html( $title ),
		esc_html( $description ),
		lmhg_site_core_render_reach_out_block()
	);

	return rtrim( $content ) . "\n" . $cta;
}

/** Removes legacy page-closing CTA groups from Gutenberg content. */
function lmhg_site_core_remove_legacy_lower_cta( string $content ): string {
	$patterns = array(
		'/<!--\s+wp:group\s+\{[^\n]*"className":"[^"]*wp2026-page-cta[^"]*"[^\n]*\}\s+-->.*?<!--\s+\/wp:group\s+-->/s',
		'/<!--\s+wp:group\s+\{[^\n]*"className":"[^"]*wp2026-home-closing[^"]*"[^\n]*\}\s+-->.*?<!--\s+\/wp:group\s+-->/s',
		'/<section\b[^>]*class="[^"]*wp2026-page-cta[^"]*"[^>]*>.*?<\/section>/s',
		'/<section\b[^>]*class="[^"]*wp2026-home-closing[^"]*"[^>]*>.*?<\/section>/s',
	);
	return trim( (string) preg_replace( $patterns, '', $content ) );
}

/** Replaces recognized Gutenberg Reach Out button blocks with the dynamic block. */
function lmhg_site_core_replace_reach_out_button_blocks( string $content ): string {
	return (string) preg_replace_callback(
		'/<!--\s+wp:button(?:\s+\{.*?\})?\s+-->\s*<div\s+class="wp-block-button[^"]*">\s*<a\b[^>]*>(.*?)<\/a>\s*<\/div>\s*<!--\s+\/wp:button\s+-->/s',
		static function ( array $matches ): string {
			$label = trim( wp_strip_all_tags( $matches[1] ) );
			return 'Reach Out' === $label ? '<!-- wp:lmhg/reach-out-button /-->' : $matches[0];
		},
		$content
	);
}

/** Assigns the default CTA to newly published pages. */
function lmhg_site_core_assign_default_cta_to_new_page( string $new_status, string $old_status, WP_Post $post ): void {
	if ( 'page' !== $post->post_type || 'publish' !== $new_status || 'publish' === $old_status ) {
		return;
	}
	$terms = wp_get_object_terms( $post->ID, LMHG_SITE_CORE_CTA_TAXONOMY, array( 'fields' => 'ids' ) );
	if ( ! is_wp_error( $terms ) && empty( $terms ) ) {
		wp_set_object_terms( $post->ID, LMHG_SITE_CORE_CTA_DEFAULT_SLUG, LMHG_SITE_CORE_CTA_TAXONOMY, false );
	}
}

/** Enforces single-term CTA assignment regardless of write surface. */
function lmhg_site_core_enforce_single_cta_term( int $object_id, array|string $terms, array $tt_ids, string $taxonomy, bool $append, array $old_tt_ids ): void {
	unset( $append, $old_tt_ids );
	static $enforcing = false;
	if ( $enforcing || LMHG_SITE_CORE_CTA_TAXONOMY !== $taxonomy || count( $tt_ids ) <= 1 ) {
		return;
	}
	$requested = is_array( $terms ) ? reset( $terms ) : $terms;
	if ( false === $requested || '' === (string) $requested ) {
		return;
	}
	$enforcing = true;
	wp_set_object_terms( $object_id, array( $requested ), $taxonomy, false );
	$enforcing = false;
}

/** Runs the one-time assignment and post-content cleanup migration. */
function lmhg_site_core_run_cta_migration(): void {
	global $wpdb;

	$installed_version = (string) get_option( LMHG_SITE_CORE_CTA_MIGRATION_OPTION, '' );
	if ( LMHG_SITE_CORE_CTA_MIGRATION_VERSION === $installed_version ) {
		return;
	}
	$pages = get_posts( array( 'post_type' => 'page', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'ASC' ) );
	if ( empty( $pages ) ) {
		return;
	}

	$complete           = true;
	$assignments        = lmhg_site_core_page_data_cta_assignments();
	$assign_cta_variants = '' === $installed_version;
	$report              = array( 'pages' => count( $pages ), 'legacy_seen' => 0, 'changed' => 0, 'written' => 0 );
	foreach ( $pages as $page ) {
		if ( ! $page instanceof WP_Post ) {
			continue;
		}
		$database_content = (string) $wpdb->get_var(
			$wpdb->prepare( "SELECT post_content FROM {$wpdb->posts} WHERE ID = %d", $page->ID )
		);
		if ( str_contains( $database_content, 'wp2026-page-cta' ) || str_contains( $database_content, 'wp2026-home-closing' ) ) {
			++$report['legacy_seen'];
		}
		if ( $assign_cta_variants ) {
			$path             = (int) get_option( 'page_on_front' ) === (int) $page->ID ? '/' : '/' . trim( get_page_uri( $page ), '/' ) . '/';
			$has_standard_cta = str_contains( $database_content, 'wp2026-page-cta' );
			$slug             = $assignments[ $path ] ?? ( $has_standard_cta ? LMHG_SITE_CORE_CTA_DEFAULT_SLUG : LMHG_SITE_CORE_CTA_NONE_SLUG );
			$assigned         = wp_set_object_terms( $page->ID, $slug, LMHG_SITE_CORE_CTA_TAXONOMY, false );
			if ( is_wp_error( $assigned ) ) {
				$complete = false;
				continue;
			}
		}

		$content = lmhg_site_core_replace_reach_out_button_blocks( lmhg_site_core_remove_legacy_lower_cta( $database_content ) );
		if ( $content !== $database_content ) {
			++$report['changed'];
			$updated = wp_update_post( wp_slash( array( 'ID' => $page->ID, 'post_content' => $content ) ), true );
			if ( is_wp_error( $updated ) ) {
				$complete = false;
				continue;
			}

			$written = $wpdb->update(
				$wpdb->posts,
				array( 'post_content' => $content ),
				array( 'ID' => $page->ID ),
				array( '%s' ),
				array( '%d' )
			);
			clean_post_cache( $page->ID );
			if ( false !== $written ) {
				++$report['written'];
			}
			$complete = false !== $written && (string) get_post_field( 'post_content', $page->ID, 'raw' ) === $content && $complete;
		}
	}

	update_option( 'lmhg_cta_taxonomy_migration_report', $report, false );
	if ( $complete ) {
		update_option( LMHG_SITE_CORE_CTA_MIGRATION_OPTION, LMHG_SITE_CORE_CTA_MIGRATION_VERSION, false );
	}
}

/**
 * Repeats content-only cleanup after the Playground runtime has mounted its
 * authoritative editor store. Existing CTA assignments are never changed.
 */
function lmhg_site_core_run_admin_cta_content_cleanup(): void {
	global $wpdb;

	if (
		! current_user_can( 'manage_options' )
		|| LMHG_SITE_CORE_CTA_CLEANUP_VERSION === (string) get_option( LMHG_SITE_CORE_CTA_CLEANUP_OPTION, '' )
	) {
		return;
	}

	$pages             = get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'ASC' ) );
	$complete          = ! empty( $pages );
	$changed           = 0;
	$revision_changes  = 0;
	foreach ( $pages as $page ) {
		if ( ! $page instanceof WP_Post ) {
			continue;
		}
		$content = lmhg_site_core_replace_reach_out_button_blocks( lmhg_site_core_remove_legacy_lower_cta( $page->post_content ) );
		if ( $content === $page->post_content ) {
			continue;
		}
		$updated = wp_update_post( wp_slash( array( 'ID' => $page->ID, 'post_content' => $content ) ), true );
		if ( is_wp_error( $updated ) ) {
			$complete = false;
			continue;
		}
		++$changed;
	}

	$page_ids = array_map( 'intval', wp_list_pluck( $pages, 'ID' ) );
	if ( ! empty( $page_ids ) ) {
		$placeholders = implode( ',', array_fill( 0, count( $page_ids ), '%d' ) );
		$revisions    = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_content FROM {$wpdb->posts} WHERE post_type = 'revision' AND post_parent IN ({$placeholders})",
				...$page_ids
			)
		);
		foreach ( $revisions as $revision ) {
			$content = lmhg_site_core_replace_reach_out_button_blocks( lmhg_site_core_remove_legacy_lower_cta( (string) $revision->post_content ) );
			if ( $content === (string) $revision->post_content ) {
				continue;
			}
			$written = $wpdb->update(
				$wpdb->posts,
				array( 'post_content' => $content ),
				array( 'ID' => (int) $revision->ID ),
				array( '%s' ),
				array( '%d' )
			);
			$complete = false !== $written && $complete;
			if ( false !== $written ) {
				++$revision_changes;
				clean_post_cache( (int) $revision->ID );
			}
		}
	}

	update_option(
		'lmhg_cta_content_cleanup_report',
		array( 'pages' => count( $pages ), 'changed' => $changed, 'revisions_changed' => $revision_changes ),
		false
	);
	if ( $complete ) {
		update_option( LMHG_SITE_CORE_CTA_CLEANUP_OPTION, LMHG_SITE_CORE_CTA_CLEANUP_VERSION, false );
	}
}

/** Reads durable CTA assignments from the theme's structured page data. */
function lmhg_site_core_page_data_cta_assignments(): array {
	$path = get_theme_file_path( 'wp2026-page-data.json' );
	if ( ! is_readable( $path ) ) {
		return array();
	}
	$data  = json_decode( (string) file_get_contents( $path ), true );
	$pages = is_array( $data ) && isset( $data['pages'] ) && is_array( $data['pages'] ) ? $data['pages'] : array();
	$map   = array();
	foreach ( $pages as $page ) {
		if ( ! is_array( $page ) ) {
			continue;
		}
		$page_path = (string) ( $page['path'] ?? '' );
		$variant   = sanitize_key( (string) ( $page['ctaVariant'] ?? '' ) );
		if ( '' !== $page_path && in_array( $variant, array( LMHG_SITE_CORE_CTA_DEFAULT_SLUG, LMHG_SITE_CORE_CTA_NONE_SLUG ), true ) ) {
			$map[ $page_path ] = $variant;
		}
	}
	return $map;
}

/** Renders shared fields on the new-term form. */
function lmhg_site_core_render_cta_add_fields(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	wp_nonce_field( 'lmhg_save_cta_term', 'lmhg_cta_term_nonce' );
	lmhg_site_core_render_cta_term_inputs();
}

/** Renders shared fields on the edit-term form. */
function lmhg_site_core_render_cta_edit_fields( WP_Term $term ): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	wp_nonce_field( 'lmhg_save_cta_term', 'lmhg_cta_term_nonce' );
	lmhg_site_core_render_cta_term_inputs( $term );
}

/** Renders CTA term metadata inputs. */
function lmhg_site_core_render_cta_term_inputs( ?WP_Term $term = null ): void {
	$values = array(
		'title'       => $term ? (string) get_term_meta( $term->term_id, '_lmhg_cta_title', true ) : '',
		'description' => $term ? (string) get_term_meta( $term->term_id, '_lmhg_cta_description', true ) : '',
		'lifecycle'   => $term ? (string) get_term_meta( $term->term_id, '_lmhg_cta_lifecycle', true ) : 'draft',
		'experiment'  => $term ? (string) get_term_meta( $term->term_id, '_lmhg_cta_experiment_label', true ) : '',
	);
	foreach ( array( 'title' => 'CTA Title', 'description' => 'CTA Description', 'experiment' => 'Experiment Label' ) as $key => $label ) {
		if ( $term ) {
			echo '<tr class="form-field"><th scope="row"><label for="lmhg-cta-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>';
		} else {
			echo '<div class="form-field"><label for="lmhg-cta-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';
		}
		if ( 'description' === $key ) {
			echo '<textarea id="lmhg-cta-description" name="lmhg_cta_description" rows="4" class="large-text">' . esc_textarea( $values[ $key ] ) . '</textarea>';
		} else {
			echo '<input id="lmhg-cta-' . esc_attr( $key ) . '" name="lmhg_cta_' . esc_attr( $key ) . '" type="text" class="regular-text" value="' . esc_attr( $values[ $key ] ) . '" />';
		}
		echo $term ? '</td></tr>' : '</div>';
	}
	$selected = $values['lifecycle'];
	if ( $term ) {
		echo '<tr class="form-field"><th scope="row"><label for="lmhg-cta-lifecycle">Lifecycle</label></th><td>';
	} else {
		echo '<div class="form-field"><label for="lmhg-cta-lifecycle">Lifecycle</label>';
	}
	echo '<select id="lmhg-cta-lifecycle" name="lmhg_cta_lifecycle">';
	foreach ( array( 'draft' => 'Draft', 'active' => 'Active', 'retired' => 'Retired' ) as $value => $label ) {
		echo '<option value="' . esc_attr( $value ) . '" ' . selected( $selected, $value, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select>' . ( $term ? '</td></tr>' : '</div>' );
}

/** Saves CTA term metadata. */
function lmhg_site_core_save_cta_term_fields( int $term_id ): void {
	if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['lmhg_cta_term_nonce'] ) ) {
		return;
	}
	$nonce = sanitize_text_field( wp_unslash( $_POST['lmhg_cta_term_nonce'] ) );
	if ( ! wp_verify_nonce( $nonce, 'lmhg_save_cta_term' ) ) {
		return;
	}
	$fields = array(
		'_lmhg_cta_title'            => isset( $_POST['lmhg_cta_title'] ) ? sanitize_text_field( wp_unslash( $_POST['lmhg_cta_title'] ) ) : '',
		'_lmhg_cta_description'      => isset( $_POST['lmhg_cta_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['lmhg_cta_description'] ) ) : '',
		'_lmhg_cta_lifecycle'        => isset( $_POST['lmhg_cta_lifecycle'] ) ? lmhg_site_core_sanitize_cta_lifecycle( wp_unslash( $_POST['lmhg_cta_lifecycle'] ) ) : 'draft',
		'_lmhg_cta_experiment_label' => isset( $_POST['lmhg_cta_experiment'] ) ? sanitize_text_field( wp_unslash( $_POST['lmhg_cta_experiment'] ) ) : '',
	);
	foreach ( $fields as $key => $value ) {
		update_term_meta( $term_id, $key, $value );
	}
}

/** Returns whether a CTA term is a protected system definition. */
function lmhg_site_core_is_protected_cta_term( int $term_id, string $taxonomy ): bool {
	return LMHG_SITE_CORE_CTA_TAXONOMY === $taxonomy && rest_sanitize_boolean( get_term_meta( $term_id, '_lmhg_cta_system', true ) );
}

/** Removes the misleading Delete action from protected CTA rows. */
function lmhg_site_core_filter_protected_cta_row_actions( array $actions, WP_Term $term ): array {
	if ( lmhg_site_core_is_protected_cta_term( (int) $term->term_id, (string) $term->taxonomy ) ) {
		unset( $actions['delete'] );
	}
	return $actions;
}

/** Returns a structured REST denial before core attempts to delete a protected term. */
function lmhg_site_core_protect_cta_system_terms_rest( mixed $result, WP_REST_Server $server, WP_REST_Request $request ): mixed {
	unset( $server );
	if ( null !== $result || 'DELETE' !== $request->get_method() ) {
		return $result;
	}

	$route_pattern = '#^/wp/v2/' . preg_quote( LMHG_SITE_CORE_CTA_TAXONOMY, '#' ) . '/([0-9]+)$#';
	if ( preg_match( $route_pattern, $request->get_route(), $matches ) && lmhg_site_core_is_protected_cta_term( absint( $matches[1] ), LMHG_SITE_CORE_CTA_TAXONOMY ) ) {
		return new WP_Error(
			'lmhg_protected_cta_term',
			'System CTA variants cannot be deleted.',
			array( 'status' => 403 )
		);
	}

	return $result;
}

/** Stops non-REST deletion paths before WordPress modifies a protected term. */
function lmhg_site_core_protect_cta_system_terms( int $term_id, string $taxonomy ): void {
	if ( ! lmhg_site_core_is_protected_cta_term( $term_id, $taxonomy ) ) {
		return;
	}

	wp_die(
		esc_html__( 'System CTA variants cannot be deleted.', 'lmhg-site-core' ),
		esc_html__( 'Protected CTA Variant', 'lmhg-site-core' ),
		array( 'response' => 403 )
	);
}
