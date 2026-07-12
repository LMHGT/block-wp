<?php
/**
 * Custom taxonomies for LMHG imported content classification.
 *
 * @package LMHGSiteCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'lmhg_site_core_register_taxonomies' );

const LMHG_SITE_CORE_TAXONOMY_BACKFILL_OPTION  = 'lmhg_technical_taxonomy_backfill_version';
const LMHG_SITE_CORE_TAXONOMY_BACKFILL_VERSION = '2026-07-12-technical-taxonomy-v1';
const LMHG_SITE_CORE_TAXONOMY_BACKFILL_REPORT  = 'lmhg_technical_taxonomy_backfill_report';

add_action( 'init', 'lmhg_site_core_run_technical_taxonomy_backfill', 26 );

/**
 * Returns the plugin-owned taxonomy definitions.
 *
 * @return array<string,array{label:string,singular:string}>
 */
function lmhg_site_core_taxonomy_definitions(): array {
	return array(
		'lmhg_page_family'      => array(
			'label'    => 'LMHG Page Families',
			'singular' => 'LMHG Page Family',
		),
		'lmhg_template_family'  => array(
			'label'    => 'LMHG Template Families',
			'singular' => 'LMHG Template Family',
		),
		'lmhg_faceted_type'     => array(
			'label'    => 'LMHG Faceted Types',
			'singular' => 'LMHG Faceted Type',
		),
		'lmhg_schema_type'      => array(
			'label'    => 'LMHG Schema Types',
			'singular' => 'LMHG Schema Type',
		),
		'lmhg_migration_status' => array(
			'label'    => 'LMHG Migration Statuses',
			'singular' => 'LMHG Migration Status',
		),
		'lmhg_seo_status'       => array(
			'label'    => 'LMHG SEO Statuses',
			'singular' => 'LMHG SEO Status',
		),
	);
}

/**
 * Registers LMHG taxonomies for imported pages.
 */
function lmhg_site_core_register_taxonomies(): void {
	foreach ( lmhg_site_core_taxonomy_definitions() as $taxonomy => $definition ) {
		register_taxonomy(
			$taxonomy,
			array( 'page' ),
			array(
				'labels'            => array(
					'name'          => $definition['label'],
					'singular_name' => $definition['singular'],
				),
				'public'            => false,
				'publicly_queryable' => false,
				'hierarchical'      => false,
				'show_ui'           => true,
				'show_admin_column' => ! function_exists( 'lmhg_site_core_filter_page_columns' ),
				'show_in_menu'      => ! function_exists( 'lmhg_site_core_register_admin_hub' ),
				'show_in_rest'      => true,
				'show_tagcloud'     => false,
				'rewrite'           => false,
				'query_var'         => false,
			)
		);
	}
}

/**
 * Assigns route-derived taxonomy terms to an imported page.
 *
 * @param int                 $page_id Page ID.
 * @param array<string,mixed> $route Route entry.
 */
function lmhg_site_core_assign_route_terms( int $page_id, array $route ): void {
	if ( ! taxonomy_exists( 'lmhg_page_family' ) ) {
		lmhg_site_core_register_taxonomies();
	}

	$seo = isset( $route['seo'] ) && is_array( $route['seo'] ) ? $route['seo'] : array();

	$values = array(
		'lmhg_page_family'      => (string) ( $route['pageFamily'] ?? '' ),
		'lmhg_template_family'  => (string) ( $route['templateFamily'] ?? '' ),
		'lmhg_faceted_type'     => (string) ( $route['facetedPageType'] ?? '' ),
		'lmhg_schema_type'      => (string) ( $seo['schemaType'] ?? '' ),
		'lmhg_migration_status' => (string) ( $route['migrationStatus'] ?? '' ),
		'lmhg_seo_status'       => (string) ( $seo['status'] ?? '' ),
	);

	foreach ( $values as $taxonomy => $value ) {
		lmhg_site_core_set_single_route_term( $page_id, $taxonomy, $value );
	}
}

/**
 * Sets a single sanitized term value for a route taxonomy.
 *
 * @param int    $page_id Page ID.
 * @param string $taxonomy Taxonomy slug.
 * @param string $value Term value.
 */
function lmhg_site_core_set_single_route_term( int $page_id, string $taxonomy, string $value ): void {
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return;
	}

	$value = trim( $value );
	if ( '' === $value ) {
		wp_set_object_terms( $page_id, array(), $taxonomy, false );
		return;
	}

	$term = term_exists( $value, $taxonomy );
	if ( 0 === $term || null === $term ) {
		$term = wp_insert_term( $value, $taxonomy, array( 'slug' => sanitize_title( $value ) ) );
	}

	if ( is_wp_error( $term ) ) {
		return;
	}

	$term_id = is_array( $term ) ? (int) $term['term_id'] : (int) $term;
	if ( $term_id > 0 ) {
		wp_set_object_terms( $page_id, array( $term_id ), $taxonomy, false );
	}
}

/**
 * Populates the consolidated technical taxonomies for pages imported before
 * those taxonomy assignments were available in the MariaDB runtime.
 *
 * Existing assignments are always preserved. The migration records completion
 * only after every attempted assignment can be read back successfully.
 */
function lmhg_site_core_run_technical_taxonomy_backfill(): void {
	if ( LMHG_SITE_CORE_TAXONOMY_BACKFILL_VERSION === (string) get_option( LMHG_SITE_CORE_TAXONOMY_BACKFILL_OPTION, '' ) ) {
		return;
	}

	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	);
	$report = array(
		'version'             => LMHG_SITE_CORE_TAXONOMY_BACKFILL_VERSION,
		'completed_at'        => '',
		'pages_scanned'       => count( $pages ),
		'assignments_created' => 0,
		'assignments_kept'    => 0,
		'values_unavailable'  => 0,
		'failures'            => array(),
		'by_taxonomy'         => array(),
	);

	foreach ( array_keys( lmhg_site_core_taxonomy_definitions() ) as $taxonomy ) {
		$report['by_taxonomy'][ $taxonomy ] = array( 'created' => 0, 'kept' => 0, 'unavailable' => 0 );
	}

	foreach ( $pages as $page ) {
		if ( ! $page instanceof WP_Post ) {
			continue;
		}

		$page_data = lmhg_site_core_technical_taxonomy_page_data( (int) $page->ID );
		$path      = lmhg_site_core_technical_taxonomy_page_path( $page, $page_data );
		$values    = lmhg_site_core_technical_taxonomy_values( (int) $page->ID, $path, $page_data );

		foreach ( $values as $taxonomy => $value ) {
			$current = wp_get_object_terms( (int) $page->ID, $taxonomy, array( 'fields' => 'ids' ) );
			if ( is_wp_error( $current ) ) {
				$report['failures'][] = array( 'post_id' => (int) $page->ID, 'taxonomy' => $taxonomy, 'reason' => $current->get_error_code() );
				continue;
			}
			if ( ! empty( $current ) ) {
				++$report['assignments_kept'];
				++$report['by_taxonomy'][ $taxonomy ]['kept'];
				continue;
			}
			if ( '' === $value ) {
				++$report['values_unavailable'];
				++$report['by_taxonomy'][ $taxonomy ]['unavailable'];
				continue;
			}

			lmhg_site_core_set_single_route_term( (int) $page->ID, $taxonomy, $value );
			$assigned = wp_get_object_terms( (int) $page->ID, $taxonomy, array( 'fields' => 'slugs' ) );
			if ( is_wp_error( $assigned ) || ! in_array( sanitize_title( $value ), $assigned, true ) ) {
				$report['failures'][] = array( 'post_id' => (int) $page->ID, 'taxonomy' => $taxonomy, 'reason' => 'assignment_readback_failed' );
				continue;
			}

			++$report['assignments_created'];
			++$report['by_taxonomy'][ $taxonomy ]['created'];
		}
	}

	if ( empty( $report['failures'] ) ) {
		$report['completed_at'] = gmdate( 'c' );
		update_option( LMHG_SITE_CORE_TAXONOMY_BACKFILL_OPTION, LMHG_SITE_CORE_TAXONOMY_BACKFILL_VERSION, false );
	}
	update_option( LMHG_SITE_CORE_TAXONOMY_BACKFILL_REPORT, $report, false );
}

/**
 * Reads the page-owned structured record used by the current WordPress import.
 *
 * @return array<string,mixed>
 */
function lmhg_site_core_technical_taxonomy_page_data( int $post_id ): array {
	$raw  = (string) get_post_meta( $post_id, '_lmhg_page_data_entry', true );
	$data = json_decode( $raw, true );
	return is_array( $data ) ? $data : array();
}

/** Returns one imported page's normalized public path. */
function lmhg_site_core_technical_taxonomy_page_path( WP_Post $page, array $page_data ): string {
	$path = (string) get_post_meta( (int) $page->ID, '_lmhg_source_url', true );
	if ( '' === trim( $path ) ) {
		$path = (string) ( $page_data['path'] ?? '' );
	}
	if ( '' === trim( $path ) ) {
		$uri  = trim( (string) get_page_uri( $page ), '/' );
		$path = '' === $uri || (int) get_option( 'page_on_front' ) === (int) $page->ID ? '/' : '/' . $uri . '/';
	}

	$path = '/' . trim( $path, '/' );
	return '/' === $path ? $path : $path . '/';
}

/**
 * Resolves current metadata first and uses stable route/template fallbacks only
 * when the imported page does not already carry a value.
 *
 * @return array<string,string>
 */
function lmhg_site_core_technical_taxonomy_values( int $post_id, string $path, array $page_data ): array {
	$seo       = isset( $page_data['seo'] ) && is_array( $page_data['seo'] ) ? $page_data['seo'] : array();
	$fallbacks = lmhg_site_core_technical_taxonomy_fallbacks( $post_id, $path, $page_data );
	$values    = array(
		'lmhg_page_family'      => (string) get_post_meta( $post_id, '_lmhg_page_family', true ),
		'lmhg_template_family'  => (string) get_post_meta( $post_id, '_lmhg_template_family', true ),
		'lmhg_faceted_type'     => (string) get_post_meta( $post_id, '_lmhg_faceted_page_type', true ),
		'lmhg_schema_type'      => (string) get_post_meta( $post_id, '_lmhg_schema_type', true ),
		'lmhg_migration_status' => (string) get_post_meta( $post_id, '_lmhg_migration_status', true ),
		'lmhg_seo_status'       => (string) get_post_meta( $post_id, '_lmhg_seo_status', true ),
	);

	if ( '' === trim( $values['lmhg_schema_type'] ) ) {
		$values['lmhg_schema_type'] = (string) ( $seo['schemaType'] ?? '' );
	}
	if ( '' === trim( $values['lmhg_seo_status'] ) ) {
		$values['lmhg_seo_status'] = (string) ( $seo['status'] ?? '' );
	}
	foreach ( $values as $taxonomy => $value ) {
		if ( '' === trim( $value ) ) {
			$values[ $taxonomy ] = (string) ( $fallbacks[ $taxonomy ] ?? '' );
		} else {
			$values[ $taxonomy ] = trim( $value );
		}
	}

	return $values;
}

/**
 * Translates the current page model into the original consolidated taxonomy
 * vocabulary without reviving obsolete pre-migration status labels.
 *
 * @return array<string,string>
 */
function lmhg_site_core_technical_taxonomy_fallbacks( int $post_id, string $path, array $page_data ): array {
	$page_family = '';
	$families    = array(
		'homepage'               => array( '/' ),
		'page'                   => array( '/not-found/' ),
		'article'                => array( '/articles/what-to-expect-when-starting-therapy/' ),
		'broad-service-category' => array( '/child-counseling/', '/community-based-services/', '/couples-counseling/', '/court-ordered/', '/family-therapy/', '/group-therapy/', '/individual-counseling/', '/trauma-therapy/' ),
		'concern-condition'      => array( '/adolescent-counseling/', '/anxiety-depression-therapy/', '/conflict-resolution-counseling/', '/parenting-support/' ),
		'contextual-parent'      => array( '/locations/community/', '/locations/in-home/', '/locations/in-person/', '/locations/online/', '/locations/school/' ),
		'primary-hub'            => array( '/contact-us/', '/faq/', '/locations/', '/meet-the-team/', '/services/', '/specialties/' ),
		'secondary-footer'       => array( '/articles/', '/careers/', '/insurance/', '/reviews/' ),
		'service-area'           => array( '/bullitt-county-ky/', '/jefferson-county-ky/', '/louisville-ky/', '/oldham-county-ky/' ),
		'specialty'              => array( '/adult-counseling/', '/attachment-therapy/', '/case-management/', '/child-behavioral-intervention/', '/co-parenting/', '/community-support/', '/emdr-therapy/', '/family-reunification/', '/play-therapy/' ),
		'support'                => array( '/articles/family-therapy-vs-individual-therapy/', '/articles/guide-to-individual-therapy/', '/articles/how-to-talk-to-your-loved-ones-about-going-to-therapy/', '/articles/top-5-signs-its-time-to-seek-therapy/', '/faq/about-lmhg/', '/faq/cost/', '/faq/our-approach/' ),
		'utility'                => array( '/compliance/', '/privacy-policy/', '/terms-of-use/' ),
	);
	foreach ( $families as $family => $paths ) {
		if ( in_array( $path, $paths, true ) ) {
			$page_family = $family;
			break;
		}
	}

	$template     = (string) ( $page_data['template'] ?? '' );
	$template_map = array(
		'article-hub'          => 'article',
		'article-page'         => 'article',
		'contact-page'         => 'trust',
		'faq-hub'              => 'faq',
		'faq-page'             => 'faq',
		'legal-utility-page'   => 'legal-utility',
		'location-access-page' => 'location-access',
		'service-page'         => 'service',
		'services-hub'         => 'service',
		'specialties-hub'      => 'specialty',
		'specialty-page'       => 'specialty',
		'team-page'            => 'trust',
		'trust-page'           => 'trust',
	);
	$template_family = (string) ( $template_map[ $template ] ?? '' );
	if ( '/' === $path ) {
		$template_family = 'home';
	} elseif ( '/not-found/' === $path ) {
		$template_family = 'not-found';
	}

	$schema_type = '';
	if ( '' !== $page_family ) {
		$schema_type = 'MedicalWebPage';
	}
	$schema_overrides = array(
		'/'               => 'MedicalClinic',
		'/articles/'      => 'Article',
		'/contact-us/'    => 'ContactPage',
		'/faq/'           => 'FAQPage',
		'/meet-the-team/' => 'AboutPage',
		'/not-found/'     => '',
	);
	if ( array_key_exists( $path, $schema_overrides ) ) {
		$schema_type = $schema_overrides[ $path ];
	}

	$imported = '' !== (string) get_post_meta( $post_id, '_lmhg_source_id', true )
		|| '' !== (string) get_post_meta( $post_id, '_lmhg_page_data_entry', true )
		|| '' !== (string) get_post_meta( $post_id, '_lmhg_route_manifest_entry', true );

	return array(
		'lmhg_page_family'      => $page_family,
		'lmhg_template_family'  => $template_family,
		'lmhg_faceted_type'     => 'page' === $page_family ? '' : $page_family,
		'lmhg_schema_type'      => $schema_type,
		'lmhg_migration_status' => $imported ? 'migrated' : '',
		'lmhg_seo_status'       => '',
	);
}
