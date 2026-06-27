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
				'show_admin_column' => true,
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
