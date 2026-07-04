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
const LMHG_SITE_CORE_RELATIONSHIP_STYLE  = 'lmhg-site-core-relationships';
const LMHG_SITE_CORE_TEAM_FIRST_META     = '_lmhg_team_first_name';
const LMHG_SITE_CORE_TEAM_LAST_META      = '_lmhg_team_last_name';
const LMHG_SITE_CORE_TEAM_CREDENTIALS    = '_lmhg_team_credentials';
const LMHG_SITE_CORE_TEAM_HEADSHOT_URL   = '_lmhg_team_headshot_url';

add_action( 'init', 'lmhg_site_core_register_relationship_taxonomies', 8 );
add_action( 'init', 'lmhg_site_core_register_relationship_post_types', 9 );
add_action( 'init', 'lmhg_site_core_register_relationship_meta', 10 );
add_action( 'add_meta_boxes', 'lmhg_site_core_add_relationship_meta_boxes' );
add_action( 'save_post_post', 'lmhg_site_core_save_article_relationship_meta', 10, 2 );
add_action( 'save_post_' . LMHG_SITE_CORE_TEAM_POST_TYPE, 'lmhg_site_core_save_team_member_meta', 10, 2 );
add_action( 'wp_enqueue_scripts', 'lmhg_site_core_register_relationship_assets' );
add_filter( 'the_content', 'lmhg_site_core_append_relationship_sections', 30 );
add_shortcode( 'lmhg_service_specialties', 'lmhg_site_core_service_specialties_shortcode' );
add_shortcode( 'lmhg_faqs', 'lmhg_site_core_faqs_shortcode' );
add_shortcode( 'lmhg_faq_index', 'lmhg_site_core_faq_index_shortcode' );
add_shortcode( 'lmhg_article_pages', 'lmhg_site_core_article_pages_shortcode' );
add_shortcode( 'lmhg_related_pages', 'lmhg_site_core_article_pages_shortcode' );
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
 * Registers relationship meta for REST/editor compatibility.
 */
function lmhg_site_core_register_relationship_meta(): void {
	$auth_callback = 'lmhg_site_core_relationship_meta_auth_callback';

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
		'0.1.0'
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
		if ( ! has_shortcode( $raw, 'lmhg_service_specialties' ) && has_term( '', LMHG_SITE_CORE_SPECIALTY_TAXONOMY, $post ) ) {
			$sections[] = lmhg_site_core_render_service_specialties( $post->ID );
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
			'heading' => 'Related specialties',
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
function lmhg_site_core_render_service_specialties( int $post_id, string $heading = 'Related specialties' ): string {
	if ( $post_id <= 0 ) {
		return '';
	}

	$terms = wp_get_object_terms(
		$post_id,
		LMHG_SITE_CORE_SPECIALTY_TAXONOMY,
		array(
			'orderby' => 'name',
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
		$description = trim( wp_strip_all_tags( term_description( $term, LMHG_SITE_CORE_SPECIALTY_TAXONOMY ) ) );

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
	$description = trim( (string) get_post_meta( $post->ID, '_lmhg_meta_description', true ) );
	if ( '' === $description ) {
		$description = trim( wp_strip_all_tags( get_the_excerpt( $post ) ) );
	}
	if ( '' === $description ) {
		$description = trim( wp_strip_all_tags( $post->post_content ) );
	}

	return wp_trim_words( $description, 24, '...' );
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
