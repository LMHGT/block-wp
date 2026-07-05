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
const LMHG_SITE_CORE_ARTICLE_CARD_DESCRIPTION_META = '_lmhg_meta_description';
const LMHG_SITE_CORE_SPECIALTY_CARD_DESCRIPTION_META = '_lmhg_specialty_card_description';
const LMHG_SITE_CORE_SPECIALTY_ICON_ID_META = '_lmhg_specialty_icon_id';
const LMHG_SITE_CORE_RELATIONSHIP_STYLE  = 'lmhg-site-core-relationships';
const LMHG_SITE_CORE_TEAM_FIRST_META     = '_lmhg_team_first_name';
const LMHG_SITE_CORE_TEAM_LAST_META      = '_lmhg_team_last_name';
const LMHG_SITE_CORE_TEAM_CREDENTIALS    = '_lmhg_team_credentials';
const LMHG_SITE_CORE_TEAM_HEADSHOT_URL   = '_lmhg_team_headshot_url';
const LMHG_SITE_CORE_SPECIALTY_FAQ_SEED_OPTION  = 'lmhg_specialty_placeholder_faq_seed_version';
const LMHG_SITE_CORE_SPECIALTY_FAQ_SEED_VERSION = '2026-07-05-specialty-faq-placeholders-v1';
const LMHG_SITE_CORE_SERVICE_SPECIALTY_SEED_OPTION  = 'lmhg_service_specialty_relationship_seed_version';
const LMHG_SITE_CORE_SERVICE_SPECIALTY_SEED_VERSION = '2026-07-05-service-specialty-taxonomy-v1';
const LMHG_SITE_CORE_RELATED_PAGE_TERM_SEED_OPTION  = 'lmhg_related_page_term_metadata_seed_version';
const LMHG_SITE_CORE_RELATED_PAGE_TERM_SEED_VERSION = '2026-07-05-related-page-term-metadata-v1';
const LMHG_SITE_CORE_IN_HOME_SPECIALTY_CLEANUP_OPTION  = 'lmhg_in_home_specialty_cleanup_version';
const LMHG_SITE_CORE_IN_HOME_SPECIALTY_CLEANUP_VERSION = '2026-07-05-in-home-location-v1';

add_action( 'init', 'lmhg_site_core_register_relationship_taxonomies', 8 );
add_action( 'init', 'lmhg_site_core_register_relationship_post_types', 9 );
add_action( 'init', 'lmhg_site_core_register_relationship_meta', 10 );
add_action( 'init', 'lmhg_site_core_seed_related_page_terms', 28 );
add_action( 'init', 'lmhg_site_core_seed_service_specialty_relationships', 29 );
add_action( 'init', 'lmhg_site_core_seed_specialty_placeholder_faqs', 30 );
add_action( 'init', 'lmhg_site_core_cleanup_in_home_specialty_classification', 31 );
add_action( 'add_meta_boxes', 'lmhg_site_core_add_relationship_meta_boxes' );
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
			'show_admin_column' => true,
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
			'show_admin_column' => true,
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
			'show_in_menu'        => true,
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
			'show_in_menu'        => true,
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
		'individual-counseling'    => array(
			'name'        => 'Individual Counseling',
			'description' => 'One-on-one care for adults and teens comparing therapy, counseling, anxiety, depression, stress, trauma, and life-change support.',
		),
		'child-counseling'         => array(
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
		'court-ordered'            => array(
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
		'child-counseling'         => array(
			'adolescent-counseling'         => 'Teen Therapy',
			'child-behavioral-intervention' => 'Child Behavioral Intervention',
			'play-therapy'                  => 'Play Therapy',
		),
		'community-based-services' => array(
			'case-management'   => 'Case Management',
			'community-support' => 'Community Support',
		),
		'couples-counseling'       => array(
			'couples-conflict-resolution' => 'Couples Conflict Resolution',
			'relationship-counseling'     => 'Relationship Counseling',
		),
		'court-ordered'            => array(
			'co-parenting'         => 'Co-Parenting',
			'family-reunification' => 'Family Reunification',
		),
		'family-therapy'           => array(
			'attachment-therapy' => 'Attachment Therapy',
			'parenting-support'  => 'Parenting Support',
		),
		'individual-counseling'    => array(
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
		'attachment-therapy'            => 'Attachment Therapy',
		'case-management'               => 'Case Management',
		'child-behavioral-intervention' => 'Child Behavioral Therapy',
		'co-parenting'                  => 'Co-Parenting Services',
		'community-support'             => 'Community Support Services',
		'couples-conflict-resolution'   => 'Couples Conflict Resolution',
		'emdr-therapy'                  => 'EMDR Therapy',
		'family-reunification'          => 'Family Reunification Services',
		'parenting-support'             => 'Parenting Support',
		'play-therapy'                  => 'Play Therapy',
		'relationship-counseling'       => 'Relationship Counseling',
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
	$auth_callback = 'lmhg_site_core_relationship_meta_auth_callback';

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
			'auth_callback'     => $auth_callback,
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
			'auth_callback'     => $auth_callback,
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
function lmhg_site_core_add_relationship_meta_boxes(): void {
	add_meta_box(
		'lmhg-related-pages',
		'Related LMHG Pages',
		'lmhg_site_core_render_article_pages_meta_box',
		'post',
		'side',
		'default'
	);

	add_meta_box(
		'lmhg-article-card-description',
		'LMHG Article Card Description',
		'lmhg_site_core_render_article_card_description_meta_box',
		'post',
		'normal',
		'default'
	);

	add_meta_box(
		'lmhg-team-member-details',
		'Team Member Details',
		'lmhg_site_core_render_team_member_meta_box',
		LMHG_SITE_CORE_TEAM_POST_TYPE,
		'normal',
		'high'
	);
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
	<label class="screen-reader-text" for="lmhg-related-page-ids">Related LMHG pages</label>
	<select id="lmhg-related-page-ids" name="lmhg_related_page_ids[]" multiple="multiple" size="12" style="width:100%;">
		<?php foreach ( $pages as $page ) : ?>
			<option value="<?php echo esc_attr( (string) $page->ID ); ?>" <?php selected( in_array( $page->ID, $selected_ids, true ) ); ?>>
				<?php echo esc_html( lmhg_site_core_page_choice_label( $page ) ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<p class="description">Select one or more service or specialty pages. Use <code>[lmhg_article_pages]</code> in article content to display them.</p>
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
	<p class="description">Controls the short description shown for this post inside service-page Helpful Articles cards. If empty, the card falls back to the excerpt or first content paragraph.</p>
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
		<label for="lmhg-specialty-card-description">Related Card Description</label>
		<?php wp_nonce_field( 'lmhg_site_core_save_specialty_card_description', 'lmhg_specialty_card_description_nonce' ); ?>
		<textarea id="lmhg-specialty-card-description" name="lmhg_specialty_card_description" rows="5" cols="40"></textarea>
		<p>Controls the short description shown below this page when it appears in Related Pages cards. If empty, the term description or linked page summary is used.</p>
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
		<th scope="row"><label for="lmhg-specialty-card-description">Related Card Description</label></th>
		<td>
			<?php wp_nonce_field( 'lmhg_site_core_save_specialty_card_description', 'lmhg_specialty_card_description_nonce' ); ?>
			<textarea id="lmhg-specialty-card-description" name="lmhg_specialty_card_description" rows="5" cols="50" class="large-text"><?php echo esc_textarea( $description ); ?></textarea>
			<p class="description">Controls the short description shown below this page when it appears in Related Pages cards. If empty, the term description or linked page summary is used.</p>
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
	unset( $post );
	if ( lmhg_site_core_should_skip_relationship_save( $post_id ) ) {
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
		return;
	}

	update_post_meta( $post_id, LMHG_SITE_CORE_RELATED_PAGES_META, $page_ids );
}

/**
 * Saves the article card description field.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post Article post.
 */
function lmhg_site_core_save_article_card_description_meta( int $post_id, WP_Post $post ): void {
	unset( $post );
	if ( lmhg_site_core_should_skip_relationship_save( $post_id ) ) {
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
		if ( $page_id > 0 && 'page' === get_post_type( $page_id ) ) {
			$page_ids[] = $page_id;
		}
	}

	return array_values( array_unique( $page_ids ) );
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
	return get_posts(
		array(
			'post_type'              => 'page',
			'post_status'            => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page'         => -1,
			'orderby'                => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);
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
		'0.1.16'
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
		return has_term( '', LMHG_SITE_CORE_SPECIALTY_TAXONOMY, $post )
			|| has_term( '', LMHG_SITE_CORE_FAQ_SET_TAXONOMY, $post )
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

	$sections = array();
	$raw      = (string) $post->post_content;

	if ( 'page' === $post->post_type ) {
		if ( ! has_shortcode( $raw, 'lmhg_service_specialties' ) && ! has_shortcode( $raw, 'lmhg_related_pages' ) && has_term( '', LMHG_SITE_CORE_SPECIALTY_TAXONOMY, $post ) ) {
			$sections[] = lmhg_site_core_render_taxonomy_related_pages( $post->ID );
		}

		if ( ! has_shortcode( $raw, 'lmhg_faqs' ) && ! has_shortcode( $raw, 'lmhg_faq_index' ) && has_term( '', LMHG_SITE_CORE_FAQ_SET_TAXONOMY, $post ) ) {
			$sections[] = lmhg_site_core_render_faqs_for_page( $post->ID );
		}

		if ( ! has_shortcode( $raw, 'lmhg_team' ) && in_array( $post->post_name, lmhg_site_core_team_page_slugs(), true ) ) {
			$sections[] = lmhg_site_core_render_team_members();
		}
	}

	if ( 'post' === $post->post_type && ! has_shortcode( $raw, 'lmhg_article_pages' ) && ! has_shortcode( $raw, 'lmhg_related_pages' ) ) {
		$sections[] = lmhg_site_core_render_article_pages( $post->ID );
	}

	$sections = array_filter( $sections );
	if ( empty( $sections ) ) {
		return $content;
	}

	return $content . "\n" . implode( "\n", $sections );
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
		'individual-counseling'       => array( 'individual-counseling-card-icon-transparent.webp', 'Individual Counseling icon' ),
		'child-counseling'            => array( 'child-counseling-card-icon-transparent.webp', 'Child Therapy icon' ),
		'family-therapy'              => array( 'family-therapy-card-icon-transparent.webp', 'Family Therapy icon' ),
		'couples-counseling'          => array( 'couples-counseling-card-icon-transparent.webp', 'Couples Counseling icon' ),
		'court-ordered'               => array( 'court-ordered-card-icon-transparent.webp', 'Court Ordered Services icon' ),
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
	return lmhg_site_core_render_faqs( lmhg_site_core_faq_set_term_ids( '', $post_id ), 'Common questions', -1 );
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

	$query = new WP_Query(
		array(
			'post_type'              => 'post',
			'post_status'            => 'publish',
			'posts_per_page'         => min( 12, max( 1, absint( $atts['count'] ) ) ),
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'meta_query'             => array(
				array(
					'key'     => LMHG_SITE_CORE_RELATED_PAGES_META,
					'value'   => 'i:' . $page_id . ';',
					'compare' => 'LIKE',
				),
			),
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	return lmhg_site_core_render_post_cards( $query->posts, (string) $atts['heading'], 'lmhg-related-articles' );
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

		$cards[] = sprintf(
			'<article class="lmhg-team-card">%1$s<div class="lmhg-team-card__body"><h3>%2$s</h3>%3$s</div></article>',
			'' !== $headshot ? '<img src="' . esc_url( $headshot ) . '" alt="' . esc_attr( $name ) . '" loading="lazy" decoding="async" />' : '',
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
	return array( 'team', 'our-team', 'team-members' );
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
	if ( '' === $description && 'page' !== $post->post_type ) {
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
