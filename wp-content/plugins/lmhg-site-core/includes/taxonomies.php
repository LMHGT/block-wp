<?php
/**
 * Custom taxonomies for LMHG imported content classification.
 *
 * @package LMHGSiteCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/faq-question-queue.php';
require_once __DIR__ . '/owner-faq-expansion.php';
require_once __DIR__ . '/owner-page-expansion.php';

add_action( 'init', 'lmhg_site_core_register_taxonomies' );

const LMHG_SITE_CORE_TAXONOMY_BACKFILL_OPTION  = 'lmhg_technical_taxonomy_backfill_version';
const LMHG_SITE_CORE_TAXONOMY_BACKFILL_VERSION = '2026-07-12-technical-taxonomy-v1';
const LMHG_SITE_CORE_TAXONOMY_BACKFILL_REPORT  = 'lmhg_technical_taxonomy_backfill_report';
const LMHG_SITE_CORE_SCHEMA_TAXONOMY_MIGRATION_OPTION  = 'lmhg_schema_taxonomy_migration_version';
const LMHG_SITE_CORE_SCHEMA_TAXONOMY_MIGRATION_VERSION = '2026-07-22-authoritative-page-roles-v1';
const LMHG_SITE_CORE_SCHEMA_TAXONOMY_MIGRATION_REPORT  = 'lmhg_schema_taxonomy_migration_report';

add_action( 'init', 'lmhg_site_core_run_technical_taxonomy_backfill', 26 );
add_action( 'init', 'lmhg_site_core_run_schema_taxonomy_migration', 31 );

/** Returns the schema.org types allowed on the canonical page entity. */
function lmhg_site_core_allowed_page_schema_types(): array {
	return array( 'WebPage', 'MedicalWebPage', 'CollectionPage', 'FAQPage', 'AboutPage', 'ContactPage', 'ProfilePage' );
}

/**
 * Returns the approved base page type for each canonical LMHG route.
 *
 * Article, MedicalClinic, Service, Person, and Review remain separately linked
 * graph entities rather than replacing the page entity's type.
 *
 * @return array<string,string>
 */
function lmhg_site_core_canonical_page_schema_types(): array {
	$types = array(
		'/'                                  => 'WebPage',
		'/blogs/'                            => 'CollectionPage',
		'/compliance/'                       => 'WebPage',
		'/contact-us/'                       => 'ContactPage',
		'/faq/'                              => 'CollectionPage',
		'/faq/cost/'                         => 'FAQPage',
		'/faq/our-approach/'                 => 'FAQPage',
		'/insurance/'                        => 'WebPage',
		'/locations/'                        => 'CollectionPage',
		'/meet-the-team/'                    => 'CollectionPage',
		'/our-services/'                     => 'CollectionPage',
		'/privacy-policy/'                   => 'WebPage',
		'/reviews/'                          => 'CollectionPage',
		'/specialties/'                      => 'CollectionPage',
		'/terms-of-use/'                     => 'WebPage',
		'/we-are-hiring/'                    => 'WebPage',
		'/what-we-do/'                       => 'AboutPage',
		'/family-therapy-vs-individual-therapy/' => 'WebPage',
		'/guide-to-individual-therapy/'      => 'WebPage',
		'/how-to-talk-to-your-loved-ones-about-going-to-therapy/' => 'WebPage',
		'/top-5-signs-its-time-to-seek-therapy/' => 'WebPage',
		'/what-to-expect-when-starting-therapy/' => 'WebPage',
	);

	$medical_paths = array(
		'/adolescent-counseling/',
		'/adult-counseling/',
		'/anxiety-depression-therapy/',
		'/attachment-therapy/',
		'/bullitt-county-ky/',
		'/case-management/',
		'/child-behavioral-intervention/',
		'/child-therapy/',
		'/co-parenting/',
		'/community-based-services/',
		'/community-support/',
		'/conflict-resolution-counseling/',
		'/couples-counseling/',
		'/emdr-therapy/',
		'/family-court/',
		'/family-reunification/',
		'/family-therapy/',
		'/group-therapy/',
		'/individual-therapy/',
		'/jefferson-county-ky/',
		'/locations/community/',
		'/locations/in-home/',
		'/locations/in-person/',
		'/locations/online/',
		'/locations/school/',
		'/louisville-ky/',
		'/oldham-county-ky/',
		'/parenting-support/',
		'/play-therapy/',
		'/trauma-therapy/',
	);
	foreach ( $medical_paths as $path ) {
		$types[ $path ] = 'MedicalWebPage';
	}

	return $types;
}

/** Returns the role-derived page type without consulting taxonomy terms. */
function lmhg_site_core_schema_type_for_page_role( int $post_id ): string {
	if ( $post_id <= 0 || 'page' !== get_post_type( $post_id ) ) {
		return 'WebPage';
	}

	$page = get_post( $post_id );
	if ( ! $page instanceof WP_Post ) {
		return 'WebPage';
	}

	$path      = lmhg_site_core_technical_taxonomy_page_path( $page, lmhg_site_core_technical_taxonomy_page_data( $post_id ) );
	$canonical = lmhg_site_core_canonical_page_schema_types();
	if ( isset( $canonical[ $path ] ) ) {
		return $canonical[ $path ];
	}
	if ( '/not-found/' === $path ) {
		return '';
	}

	$template = sanitize_key( (string) get_page_template_slug( $post_id ) );
	return match ( $template ) {
		'article-page', 'legal-utility-page' => 'WebPage',
		'service-page', 'specialty-page', 'location-access-page' => 'MedicalWebPage',
		'services-hub', 'specialties-hub', 'article-hub', 'faq-hub', 'team-page' => 'CollectionPage',
		'faq-page'     => 'FAQPage',
		'contact-page' => 'ContactPage',
		'profile-page' => 'ProfilePage',
		default        => 'WebPage',
	};
}

/**
 * Resolves the authoritative base schema type for a Page.
 *
 * Exactly one valid taxonomy term wins. Missing, multiple, invalid, and legacy
 * entity terms fall back to the approved route/template role mapping.
 */
function lmhg_site_core_resolved_schema_type_for_page( int $post_id ): string {
	$terms = wp_get_object_terms( $post_id, 'lmhg_schema_type' );
	if ( ! is_wp_error( $terms ) && 1 === count( $terms ) ) {
		$term = reset( $terms );
		$type = $term instanceof WP_Term ? trim( (string) $term->name ) : '';
		if ( in_array( $type, lmhg_site_core_allowed_page_schema_types(), true ) ) {
			return $type;
		}
	}

	return lmhg_site_core_schema_type_for_page_role( $post_id );
}

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
		'lmhg_schema_type'      => lmhg_site_core_schema_type_for_page_role( $page_id ),
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
	if ( 'lmhg_schema_type' === $taxonomy && '' !== $value && ! in_array( $value, lmhg_site_core_allowed_page_schema_types(), true ) ) {
		$value = lmhg_site_core_schema_type_for_page_role( $page_id );
	}
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
 * Corrects canonical page schema terms and retires the superseded post meta.
 *
 * Original term and meta state is retained in the report across retries. The
 * legacy meta is deleted only after the intended single term can be read back.
 */
function lmhg_site_core_run_schema_taxonomy_migration(): void {
	if ( LMHG_SITE_CORE_SCHEMA_TAXONOMY_MIGRATION_VERSION === (string) get_option( LMHG_SITE_CORE_SCHEMA_TAXONOMY_MIGRATION_OPTION, '' ) ) {
		return;
	}
	if ( ! taxonomy_exists( 'lmhg_schema_type' ) ) {
		return;
	}

	$stored = get_option( LMHG_SITE_CORE_SCHEMA_TAXONOMY_MIGRATION_REPORT, array() );
	$report = is_array( $stored ) && LMHG_SITE_CORE_SCHEMA_TAXONOMY_MIGRATION_VERSION === (string) ( $stored['version'] ?? '' )
		? $stored
		: array(
			'version'      => LMHG_SITE_CORE_SCHEMA_TAXONOMY_MIGRATION_VERSION,
			'started_at'   => gmdate( 'c' ),
			'completed_at' => '',
			'pages'        => array(),
			'attempts'     => array(),
		);
	$report['completed_at'] = '';
	$report['failures']     = array();

	$published = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	);
	$by_path = array();
	foreach ( $published as $page ) {
		if ( ! $page instanceof WP_Post ) {
			continue;
		}
		$path = lmhg_site_core_technical_taxonomy_page_path( $page, lmhg_site_core_technical_taxonomy_page_data( (int) $page->ID ) );
		$by_path[ $path ] = $page;
	}

	$targets = lmhg_site_core_canonical_page_schema_types();
	if ( isset( $by_path['/not-found/'] ) ) {
		$targets['/not-found/'] = '';
	}
	$report['canonical_pages_expected'] = count( lmhg_site_core_canonical_page_schema_types() );
	$report['pages_targeted']            = count( $targets );

	$attempt_failures = array();
	foreach ( $targets as $path => $target_type ) {
		$page = $by_path[ $path ] ?? null;
		if ( ! $page instanceof WP_Post ) {
			$failure = array( 'path' => $path, 'reason' => 'canonical_page_not_found' );
			$report['failures'][] = $failure;
			$attempt_failures[]   = $failure;
			continue;
		}

		$post_id = (int) $page->ID;
		$key     = (string) $post_id;
		$before_terms = wp_get_object_terms( $post_id, 'lmhg_schema_type' );
		if ( is_wp_error( $before_terms ) ) {
			$failure = array( 'post_id' => $post_id, 'path' => $path, 'reason' => $before_terms->get_error_code() );
			$report['failures'][] = $failure;
			$attempt_failures[]   = $failure;
			continue;
		}

		if ( ! isset( $report['pages'][ $key ]['before'] ) ) {
			$term_journal = array();
			foreach ( $before_terms as $term ) {
				if ( $term instanceof WP_Term ) {
					$term_journal[] = array(
						'term_id' => (int) $term->term_id,
						'name'    => (string) $term->name,
						'slug'    => (string) $term->slug,
					);
				}
			}
			$report['pages'][ $key ] = array(
				'post_id'     => $post_id,
				'path'        => $path,
				'target_type' => $target_type,
				'before'      => array(
					'terms' => $term_journal,
					'meta'  => array(
						'existed' => metadata_exists( 'post', $post_id, '_lmhg_schema_type' ),
						'values'  => get_post_meta( $post_id, '_lmhg_schema_type', false ),
					),
				),
			);
			$journaled = update_option( LMHG_SITE_CORE_SCHEMA_TAXONOMY_MIGRATION_REPORT, $report, false );
			if ( ! $journaled && $report !== get_option( LMHG_SITE_CORE_SCHEMA_TAXONOMY_MIGRATION_REPORT, array() ) ) {
				$failure = array( 'post_id' => $post_id, 'path' => $path, 'reason' => 'before_state_journal_failed' );
				$report['failures'][] = $failure;
				$attempt_failures[]   = $failure;
				$report['pages'][ $key ]['status'] = 'before_state_journal_failed';
				continue;
			}
		}

		$result = '' === $target_type
			? wp_set_object_terms( $post_id, array(), 'lmhg_schema_type', false )
			: wp_set_object_terms( $post_id, array( $target_type ), 'lmhg_schema_type', false );
		if ( is_wp_error( $result ) ) {
			$failure = array( 'post_id' => $post_id, 'path' => $path, 'reason' => $result->get_error_code() );
			$report['failures'][] = $failure;
			$attempt_failures[]   = $failure;
			$report['pages'][ $key ]['status'] = 'term_assignment_failed';
			continue;
		}

		$assigned = wp_get_object_terms( $post_id, 'lmhg_schema_type', array( 'fields' => 'names' ) );
		$verified = ! is_wp_error( $assigned ) && ( '' === $target_type ? empty( $assigned ) : array( $target_type ) === array_values( $assigned ) );
		if ( ! $verified ) {
			$failure = array( 'post_id' => $post_id, 'path' => $path, 'reason' => 'assignment_readback_failed' );
			$report['failures'][] = $failure;
			$attempt_failures[]   = $failure;
			$report['pages'][ $key ]['status'] = 'term_readback_failed';
			continue;
		}

		if ( metadata_exists( 'post', $post_id, '_lmhg_schema_type' ) ) {
			delete_post_meta( $post_id, '_lmhg_schema_type' );
		}
		if ( metadata_exists( 'post', $post_id, '_lmhg_schema_type' ) ) {
			$failure = array( 'post_id' => $post_id, 'path' => $path, 'reason' => 'legacy_meta_delete_failed' );
			$report['failures'][] = $failure;
			$attempt_failures[]   = $failure;
			$report['pages'][ $key ]['status'] = 'legacy_meta_delete_failed';
			continue;
		}

		$report['pages'][ $key ]['after'] = array(
			'terms'       => $assigned,
			'meta_exists' => false,
		);
		$report['pages'][ $key ]['status'] = 'migrated';
	}

	$report['attempts'][] = array(
		'attempted_at' => gmdate( 'c' ),
		'failures'     => $attempt_failures,
	);
	if ( empty( $report['failures'] ) ) {
		$report['completed_at'] = gmdate( 'c' );
		$report_saved = update_option( LMHG_SITE_CORE_SCHEMA_TAXONOMY_MIGRATION_REPORT, $report, false );
		if ( $report_saved || $report === get_option( LMHG_SITE_CORE_SCHEMA_TAXONOMY_MIGRATION_REPORT, array() ) ) {
			$completion_saved = update_option( LMHG_SITE_CORE_SCHEMA_TAXONOMY_MIGRATION_OPTION, LMHG_SITE_CORE_SCHEMA_TAXONOMY_MIGRATION_VERSION, false );
			if ( $completion_saved || LMHG_SITE_CORE_SCHEMA_TAXONOMY_MIGRATION_VERSION === (string) get_option( LMHG_SITE_CORE_SCHEMA_TAXONOMY_MIGRATION_OPTION, '' ) ) {
				return;
			}
			$report['completed_at'] = '';
			$report['failures'][]   = array( 'reason' => 'completion_marker_write_failed' );
		} else {
			$report['completed_at'] = '';
			$report['failures'][]   = array( 'reason' => 'completed_report_journal_failed' );
		}
	}
	update_option( LMHG_SITE_CORE_SCHEMA_TAXONOMY_MIGRATION_REPORT, $report, false );
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
		'lmhg_schema_type'      => (string) $fallbacks['lmhg_schema_type'],
		'lmhg_migration_status' => (string) get_post_meta( $post_id, '_lmhg_migration_status', true ),
		'lmhg_seo_status'       => (string) get_post_meta( $post_id, '_lmhg_seo_status', true ),
	);

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
		'article'                => array( '/what-to-expect-when-starting-therapy/' ),
		'broad-service-category' => array( '/child-therapy/', '/community-based-services/', '/couples-counseling/', '/family-court/', '/family-therapy/', '/group-therapy/', '/individual-therapy/', '/trauma-therapy/' ),
		'concern-condition'      => array( '/adolescent-counseling/', '/anxiety-depression-therapy/', '/conflict-resolution-counseling/', '/parenting-support/' ),
		'contextual-parent'      => array( '/locations/community/', '/locations/in-home/', '/locations/in-person/', '/locations/online/', '/locations/school/' ),
		'primary-hub'            => array( '/contact-us/', '/faq/', '/locations/', '/meet-the-team/', '/our-services/', '/specialties/' ),
		'secondary-footer'       => array( '/blogs/', '/we-are-hiring/', '/insurance/', '/reviews/' ),
		'service-area'           => array( '/bullitt-county-ky/', '/jefferson-county-ky/', '/louisville-ky/', '/oldham-county-ky/' ),
		'specialty'              => array( '/adult-counseling/', '/attachment-therapy/', '/case-management/', '/child-behavioral-intervention/', '/co-parenting/', '/community-support/', '/emdr-therapy/', '/family-reunification/', '/play-therapy/' ),
		'support'                => array( '/family-therapy-vs-individual-therapy/', '/guide-to-individual-therapy/', '/how-to-talk-to-your-loved-ones-about-going-to-therapy/', '/top-5-signs-its-time-to-seek-therapy/', '/what-we-do/', '/faq/cost/', '/faq/our-approach/' ),
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

	$schema_type = lmhg_site_core_schema_type_for_page_role( $post_id );

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
