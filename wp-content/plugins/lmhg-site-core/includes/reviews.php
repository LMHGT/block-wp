<?php
/**
 * Admin-managed review showcase for LMHG.
 *
 * @package LMHGSiteCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const LMHG_SITE_CORE_REVIEW_POST_TYPE   = 'lmhg_review';
const LMHG_SITE_CORE_REVIEW_OPTION      = 'lmhg_review_showcase_settings';
const LMHG_SITE_CORE_REVIEW_STYLE       = 'lmhg-site-core-reviews';
const LMHG_SITE_CORE_REVIEW_OPTION_PAGE = 'lmhg_review_showcase';

add_action( 'init', 'lmhg_site_core_register_review_post_type' );
add_action( 'init', 'lmhg_site_core_register_review_meta' );
add_action( 'add_meta_boxes', 'lmhg_site_core_add_review_meta_box' );
add_action( 'save_post_' . LMHG_SITE_CORE_REVIEW_POST_TYPE, 'lmhg_site_core_save_review_meta', 10, 2 );
add_action( 'admin_menu', 'lmhg_site_core_add_review_settings_page' );
add_action( 'admin_init', 'lmhg_site_core_register_review_settings' );
add_action( 'init', 'lmhg_site_core_register_review_showcase_block', 20 );
add_action( 'wp_enqueue_scripts', 'lmhg_site_core_register_review_assets' );
add_filter( 'the_content', 'lmhg_site_core_append_reviews_to_configured_page', 25 );
add_shortcode( 'lmhg_reviews', 'lmhg_site_core_reviews_shortcode' );

/**
 * Registers the curated review post type.
 */
function lmhg_site_core_register_review_post_type(): void {
	register_post_type(
		LMHG_SITE_CORE_REVIEW_POST_TYPE,
		array(
			'labels'              => array(
				'name'               => 'LMHG Reviews',
				'singular_name'      => 'LMHG Review',
				'add_new_item'       => 'Add New Review',
				'edit_item'          => 'Edit Review',
				'new_item'           => 'New Review',
				'view_item'          => 'View Review',
				'search_items'       => 'Search Reviews',
				'not_found'          => 'No reviews found',
				'not_found_in_trash' => 'No reviews found in Trash',
				'menu_name'          => 'LMHG Reviews',
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			'menu_icon'           => 'dashicons-star-filled',
			'supports'            => array( 'title', 'editor', 'excerpt', 'revisions', 'page-attributes' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
		)
	);
}

/**
 * Registers review metadata for editor and REST compatibility.
 */
function lmhg_site_core_register_review_meta(): void {
	$auth_callback = static function ( mixed $allowed = false, string $meta_key = '', int $object_id = 0, int $user_id = 0, string $cap = '', array $caps = array() ): bool {
		return $object_id > 0 ? current_user_can( 'edit_post', $object_id ) : current_user_can( 'edit_posts' );
	};

	register_post_meta(
		LMHG_SITE_CORE_REVIEW_POST_TYPE,
		'_lmhg_review_rating',
		array(
			'type'              => 'number',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'lmhg_site_core_sanitize_review_rating',
			'auth_callback'     => $auth_callback,
		)
	);

	register_post_meta(
		LMHG_SITE_CORE_REVIEW_POST_TYPE,
		'_lmhg_review_date',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => $auth_callback,
		)
	);

	register_post_meta(
		LMHG_SITE_CORE_REVIEW_POST_TYPE,
		'_lmhg_review_source_name',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => $auth_callback,
		)
	);

	register_post_meta(
		LMHG_SITE_CORE_REVIEW_POST_TYPE,
		'_lmhg_review_source_url',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'esc_url_raw',
			'auth_callback'     => $auth_callback,
		)
	);

	register_post_meta(
		LMHG_SITE_CORE_REVIEW_POST_TYPE,
		'_lmhg_review_featured',
		array(
			'type'              => 'boolean',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'rest_sanitize_boolean',
			'auth_callback'     => $auth_callback,
		)
	);
}

/**
 * Adds the review details metabox.
 */
function lmhg_site_core_add_review_meta_box(): void {
	add_meta_box(
		'lmhg-review-details',
		'Review Details',
		'lmhg_site_core_render_review_meta_box',
		LMHG_SITE_CORE_REVIEW_POST_TYPE,
		'side',
		'high'
	);
}

/**
 * Renders review metadata fields.
 *
 * @param WP_Post $post Review post.
 */
function lmhg_site_core_render_review_meta_box( WP_Post $post ): void {
	wp_nonce_field( 'lmhg_site_core_save_review_meta', 'lmhg_site_core_review_meta_nonce' );

	$rating      = get_post_meta( $post->ID, '_lmhg_review_rating', true );
	$review_date = get_post_meta( $post->ID, '_lmhg_review_date', true );
	$source_name = get_post_meta( $post->ID, '_lmhg_review_source_name', true );
	$source_url  = get_post_meta( $post->ID, '_lmhg_review_source_url', true );
	$featured    = rest_sanitize_boolean( get_post_meta( $post->ID, '_lmhg_review_featured', true ) );

	if ( '' === $source_name ) {
		$source_name = 'Google';
	}

	?>
	<p>
		<label for="lmhg-review-rating"><strong>Rating</strong></label>
		<input id="lmhg-review-rating" name="lmhg_review_rating" type="number" min="0" max="5" step="0.1" class="widefat" value="<?php echo esc_attr( (string) $rating ); ?>" />
	</p>
	<p>
		<label for="lmhg-review-date"><strong>Review Date</strong></label>
		<input id="lmhg-review-date" name="lmhg_review_date" type="date" class="widefat" value="<?php echo esc_attr( (string) $review_date ); ?>" />
	</p>
	<p>
		<label for="lmhg-review-source-name"><strong>Source Name</strong></label>
		<input id="lmhg-review-source-name" name="lmhg_review_source_name" type="text" class="widefat" value="<?php echo esc_attr( (string) $source_name ); ?>" />
	</p>
	<p>
		<label for="lmhg-review-source-url"><strong>Source URL</strong></label>
		<input id="lmhg-review-source-url" name="lmhg_review_source_url" type="url" class="widefat" value="<?php echo esc_url( (string) $source_url ); ?>" />
	</p>
	<p>
		<label>
			<input name="lmhg_review_featured" type="checkbox" value="1" <?php checked( $featured ); ?> />
			Show in review showcase
		</label>
	</p>
	<p class="description">Use the title for the reviewer name and the editor for the review text.</p>
	<?php
}

/**
 * Saves review metadata from explicit admin fields.
 *
 * @param int     $post_id Review post ID.
 * @param WP_Post $post Review post.
 */
function lmhg_site_core_save_review_meta( int $post_id, WP_Post $post ): void {
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$nonce = isset( $_POST['lmhg_site_core_review_meta_nonce'] )
		? sanitize_text_field( wp_unslash( $_POST['lmhg_site_core_review_meta_nonce'] ) )
		: '';
	if ( ! wp_verify_nonce( $nonce, 'lmhg_site_core_save_review_meta' ) ) {
		return;
	}

	$rating = isset( $_POST['lmhg_review_rating'] )
		? lmhg_site_core_sanitize_review_rating( wp_unslash( $_POST['lmhg_review_rating'] ) )
		: '';
	lmhg_site_core_update_or_delete_review_meta( $post_id, '_lmhg_review_rating', $rating );

	$review_date = isset( $_POST['lmhg_review_date'] )
		? lmhg_site_core_sanitize_review_date( wp_unslash( $_POST['lmhg_review_date'] ) )
		: '';
	lmhg_site_core_update_or_delete_review_meta( $post_id, '_lmhg_review_date', $review_date );

	$source_name = isset( $_POST['lmhg_review_source_name'] )
		? sanitize_text_field( wp_unslash( $_POST['lmhg_review_source_name'] ) )
		: '';
	lmhg_site_core_update_or_delete_review_meta( $post_id, '_lmhg_review_source_name', $source_name );

	$source_url = isset( $_POST['lmhg_review_source_url'] )
		? esc_url_raw( wp_unslash( $_POST['lmhg_review_source_url'] ) )
		: '';
	lmhg_site_core_update_or_delete_review_meta( $post_id, '_lmhg_review_source_url', $source_url );

	update_post_meta( $post_id, '_lmhg_review_featured', isset( $_POST['lmhg_review_featured'] ) ? '1' : '0' );
}

/**
 * Updates or removes an empty review meta value.
 *
 * @param int          $post_id Post ID.
 * @param string       $key Meta key.
 * @param string|float $value Meta value.
 */
function lmhg_site_core_update_or_delete_review_meta( int $post_id, string $key, string|float $value ): void {
	if ( '' === $value ) {
		delete_post_meta( $post_id, $key );
		return;
	}

	update_post_meta( $post_id, $key, $value );
}

/**
 * Adds review settings under Settings.
 */
function lmhg_site_core_add_review_settings_page(): void {
	add_options_page(
		'LMHG Review Showcase',
		'LMHG Reviews',
		'manage_options',
		LMHG_SITE_CORE_REVIEW_OPTION_PAGE,
		'lmhg_site_core_render_review_settings_page'
	);
}

/**
 * Registers review settings and fields.
 */
function lmhg_site_core_register_review_settings(): void {
	register_setting(
		LMHG_SITE_CORE_REVIEW_OPTION_PAGE,
		LMHG_SITE_CORE_REVIEW_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'lmhg_site_core_sanitize_review_settings',
			'default'           => lmhg_site_core_default_review_settings(),
		)
	);

	add_settings_section(
		'lmhg-review-display',
		'Display Settings',
		static function(): void {
			echo '<p>Manage the manual review showcase. No Google API calls are made by this plugin.</p>';
		},
		LMHG_SITE_CORE_REVIEW_OPTION_PAGE
	);

	$fields = array(
		'enabled'            => 'Enable showcase',
		'auto_page_slug'     => 'Auto-render page slug',
		'heading'            => 'Heading',
		'intro'              => 'Intro text',
		'summary_label'      => 'Summary label',
		'overall_rating'     => 'Overall rating',
		'review_count'       => 'Total review count',
		'google_profile_url' => 'Google profile URL',
		'max_reviews'        => 'Maximum reviews shown',
	);

	foreach ( $fields as $key => $label ) {
		add_settings_field(
			$key,
			$label,
			static function() use ( $key ): void {
				lmhg_site_core_render_review_setting_field( $key );
			},
			LMHG_SITE_CORE_REVIEW_OPTION_PAGE,
			'lmhg-review-display'
		);
	}
}

/**
 * Renders the review settings page.
 */
function lmhg_site_core_render_review_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	?>
	<div class="wrap">
		<h1>LMHG Review Showcase</h1>
		<p>Add individual curated reviews under <strong>LMHG Reviews</strong>. The reviews page renders the selected showcase automatically; the shortcode remains available only for legacy or custom placements.</p>
		<form action="options.php" method="post">
			<?php
			settings_fields( LMHG_SITE_CORE_REVIEW_OPTION_PAGE );
			do_settings_sections( LMHG_SITE_CORE_REVIEW_OPTION_PAGE );
			submit_button();
			?>
		</form>
	</div>
	<?php
}

/**
 * Renders one settings field.
 *
 * @param string $key Setting key.
 */
function lmhg_site_core_render_review_setting_field( string $key ): void {
	$settings = lmhg_site_core_review_settings();
	$name     = LMHG_SITE_CORE_REVIEW_OPTION . '[' . $key . ']';
	$value    = $settings[ $key ] ?? '';

	if ( 'enabled' === $key ) {
		printf(
			'<label><input type="checkbox" name="%1$s" value="1" %2$s /> Show review showcase on the front end</label>',
			esc_attr( $name ),
			checked( '1', (string) $value, false )
		);
		return;
	}

	if ( 'intro' === $key ) {
		printf(
			'<textarea name="%1$s" rows="3" class="large-text">%2$s</textarea>',
			esc_attr( $name ),
			esc_textarea( (string) $value )
		);
		return;
	}

	if ( 'overall_rating' === $key ) {
		printf(
			'<input type="number" min="0" max="5" step="0.1" name="%1$s" value="%2$s" class="small-text" />',
			esc_attr( $name ),
			esc_attr( (string) $value )
		);
		return;
	}

	if ( 'review_count' === $key || 'max_reviews' === $key ) {
		printf(
			'<input type="number" min="0" step="1" name="%1$s" value="%2$s" class="small-text" />',
			esc_attr( $name ),
			esc_attr( (string) $value )
		);
		return;
	}

	if ( 'google_profile_url' === $key ) {
		printf(
			'<input type="url" name="%1$s" value="%2$s" class="regular-text" />',
			esc_attr( $name ),
			esc_url( (string) $value )
		);
		return;
	}

	printf(
		'<input type="text" name="%1$s" value="%2$s" class="regular-text" />',
		esc_attr( $name ),
		esc_attr( (string) $value )
	);
}

/**
 * Sanitizes review showcase settings.
 *
 * @param mixed $input Raw option input.
 * @return array<string,string|int>
 */
function lmhg_site_core_sanitize_review_settings( mixed $input ): array {
	$input    = is_array( $input ) ? $input : array();
	$defaults = lmhg_site_core_default_review_settings();

	return array(
		'enabled'            => ! empty( $input['enabled'] ) ? '1' : '0',
		'auto_page_slug'     => sanitize_title( (string) ( $input['auto_page_slug'] ?? $defaults['auto_page_slug'] ) ),
		'heading'            => sanitize_text_field( (string) ( $input['heading'] ?? $defaults['heading'] ) ),
		'intro'              => sanitize_textarea_field( (string) ( $input['intro'] ?? $defaults['intro'] ) ),
		'summary_label'      => sanitize_text_field( (string) ( $input['summary_label'] ?? $defaults['summary_label'] ) ),
		'overall_rating'     => lmhg_site_core_sanitize_review_rating( $input['overall_rating'] ?? $defaults['overall_rating'] ),
		'review_count'       => max( 0, absint( $input['review_count'] ?? $defaults['review_count'] ) ),
		'google_profile_url' => esc_url_raw( (string) ( $input['google_profile_url'] ?? $defaults['google_profile_url'] ) ),
		'max_reviews'        => min( 12, max( 1, absint( $input['max_reviews'] ?? $defaults['max_reviews'] ) ) ),
	);
}

/**
 * Returns default review showcase settings.
 *
 * @return array<string,string|int>
 */
function lmhg_site_core_default_review_settings(): array {
	return array(
		'enabled'            => '1',
		'auto_page_slug'     => 'reviews',
		'heading'            => 'Client feedback',
		'intro'              => '',
		'summary_label'      => 'Google rating',
		'overall_rating'     => '',
		'review_count'       => 0,
		'google_profile_url' => '',
		'max_reviews'        => 5,
	);
}

/**
 * Returns sanitized review showcase settings.
 *
 * @return array<string,string|int>
 */
function lmhg_site_core_review_settings(): array {
	$settings = get_option( LMHG_SITE_CORE_REVIEW_OPTION, array() );
	return lmhg_site_core_sanitize_review_settings( wp_parse_args( is_array( $settings ) ? $settings : array(), lmhg_site_core_default_review_settings() ) );
}

/**
 * Sanitizes ratings to one decimal between 0 and 5.
 *
 * @param mixed $value Raw rating.
 * @return string|float
 */
function lmhg_site_core_sanitize_review_rating( mixed $value ): string|float {
	if ( '' === $value || null === $value ) {
		return '';
	}

	$rating = (float) $value;
	if ( $rating < 0 || $rating > 5 ) {
		return '';
	}

	return round( $rating, 1 );
}

/**
 * Sanitizes YYYY-MM-DD review dates.
 *
 * @param mixed $value Raw date.
 * @return string
 */
function lmhg_site_core_sanitize_review_date( mixed $value ): string {
	$date = sanitize_text_field( (string) $value );
	return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ? $date : '';
}

/**
 * Registers review assets and enqueues them when the current request needs them.
 */
function lmhg_site_core_register_review_assets(): void {
	lmhg_site_core_register_review_style();

	if ( lmhg_site_core_request_needs_review_assets() ) {
		wp_enqueue_style( LMHG_SITE_CORE_REVIEW_STYLE );
	}
}

/**
 * Registers review showcase CSS for front-end and block-editor use.
 */
function lmhg_site_core_register_review_style(): void {
	if ( wp_style_is( LMHG_SITE_CORE_REVIEW_STYLE, 'registered' ) ) {
		return;
	}

	wp_register_style(
		LMHG_SITE_CORE_REVIEW_STYLE,
		plugin_dir_url( dirname( __DIR__ ) . '/lmhg-site-core.php' ) . 'assets/css/reviews.css',
		array(),
		'0.1.0'
	);
}

/**
 * Ensures review CSS is queued for template-level rendering.
 */
function lmhg_site_core_enqueue_review_assets(): void {
	if ( ! wp_style_is( LMHG_SITE_CORE_REVIEW_STYLE, 'registered' ) ) {
		lmhg_site_core_register_review_assets();
	}

	wp_enqueue_style( LMHG_SITE_CORE_REVIEW_STYLE );
}

/**
 * Determines whether the current request should enqueue review CSS.
 */
function lmhg_site_core_request_needs_review_assets(): bool {
	if ( is_admin() || ! is_singular() ) {
		return false;
	}

	$post = get_post();
	if ( ! $post instanceof WP_Post ) {
		return false;
	}

	if ( has_shortcode( $post->post_content, 'lmhg_reviews' ) ) {
		return true;
	}

	return lmhg_site_core_page_exposes_review_showcase( $post );
}

/**
 * Registers a server-rendered Gutenberg block for manual review showcases.
 */
function lmhg_site_core_register_review_showcase_block(): void {
	lmhg_site_core_register_review_style();

	$script_handle = 'lmhg-site-core-review-showcase-block';
	wp_register_script(
		$script_handle,
		plugin_dir_url( dirname( __DIR__ ) . '/lmhg-site-core.php' ) . 'assets/js/review-showcase-block.js',
		array( 'wp-block-editor', 'wp-blocks', 'wp-components', 'wp-element', 'wp-i18n', 'wp-server-side-render' ),
		'0.1.1',
		true
	);

	register_block_type(
		'lmhg/review-showcase',
		array(
			'api_version'     => 3,
			'editor_script'   => $script_handle,
			'editor_style'    => LMHG_SITE_CORE_REVIEW_STYLE,
			'style'           => LMHG_SITE_CORE_REVIEW_STYLE,
			'attributes'      => array(
				'heading' => array(
					'type'    => 'string',
					'default' => 'Client feedback',
				),
				'intro'   => array(
					'type'    => 'string',
					'default' => '',
				),
				'count'   => array(
					'type'    => 'number',
					'default' => 3,
				),
				'context' => array(
					'type'    => 'string',
					'default' => 'default',
				),
			),
			'render_callback' => 'lmhg_site_core_render_review_showcase_block',
		)
	);
}

/**
 * Appends the review showcase to the configured reviews page.
 *
 * @param string $content Existing content.
 * @return string
 */
function lmhg_site_core_append_reviews_to_configured_page( string $content ): string {
	if ( is_admin() || ! is_singular( 'page' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$post = get_post();
	if ( ! $post instanceof WP_Post ) {
		return $content;
	}

	if ( has_shortcode( (string) $post->post_content, 'lmhg_reviews' ) || str_contains( $content, 'data-lmhg-review-showcase' ) ) {
		return $content;
	}

	if ( ! lmhg_site_core_is_review_showcase_page( $post ) ) {
		return $content;
	}

	$showcase = lmhg_site_core_render_review_showcase();
	if ( '' === $showcase ) {
		return $content;
	}

	if ( function_exists( 'lmhg_site_core_insert_before_page_cta' ) ) {
		return lmhg_site_core_insert_before_page_cta( $content, $showcase );
	}

	return $content . "\n" . $showcase;
}

/**
 * Determines whether a page should expose the manual review showcase without a shortcode.
 *
 * @param WP_Post $post Page post.
 * @return bool
 */
function lmhg_site_core_is_review_showcase_page( WP_Post $post ): bool {
	$settings = lmhg_site_core_review_settings();
	if ( '1' !== (string) $settings['enabled'] || 'page' !== $post->post_type ) {
		return false;
	}

	return in_array( $post->post_name, lmhg_site_core_review_showcase_page_slugs( $settings ), true );
}

/**
 * Returns page slugs that should render the manual review showcase.
 *
 * @param array<string,string|int>|null $settings Optional review settings.
 * @return string[]
 */
function lmhg_site_core_review_showcase_page_slugs( ?array $settings = null ): array {
	$settings = null === $settings ? lmhg_site_core_review_settings() : $settings;
	$slugs    = array( 'reviews' );

	if ( '' !== (string) ( $settings['auto_page_slug'] ?? '' ) ) {
		$slugs[] = sanitize_title( (string) $settings['auto_page_slug'] );
	}

	return array_values( array_unique( array_filter( $slugs ) ) );
}

/**
 * Determines whether the current page has a visible review showcase surface.
 *
 * @param WP_Post $post Page post.
 * @return bool
 */
function lmhg_site_core_page_exposes_review_showcase( WP_Post $post ): bool {
	if ( lmhg_site_core_is_review_showcase_page( $post ) ) {
		return true;
	}

	if ( has_block( 'lmhg/review-showcase', (string) $post->post_content ) ) {
		return true;
	}

	return is_front_page() && (int) get_queried_object_id() === (int) $post->ID;
}

/**
 * Renders the manual review showcase dynamic block.
 *
 * @param array<string,mixed> $attributes Block attributes.
 * @return string
 */
function lmhg_site_core_render_review_showcase_block( array $attributes = array() ): string {
	$count = isset( $attributes['count'] ) ? min( 12, max( 1, absint( $attributes['count'] ) ) ) : 3;
	$args  = array(
		'count'   => (string) $count,
		'heading' => isset( $attributes['heading'] ) ? sanitize_text_field( (string) $attributes['heading'] ) : 'Client feedback',
		'intro'   => isset( $attributes['intro'] ) ? sanitize_textarea_field( (string) $attributes['intro'] ) : '',
	);

	$html = lmhg_site_core_render_review_showcase( $args );
	if ( '' === $html ) {
		return '';
	}

	$context = isset( $attributes['context'] ) ? sanitize_key( (string) $attributes['context'] ) : '';
	if ( '' === $context || 'default' === $context ) {
		return $html;
	}

	return preg_replace(
		'/<section class="lmhg-review-showcase"/',
		'<section class="lmhg-review-showcase lmhg-review-showcase--' . esc_attr( $context ) . '"',
		$html,
		1
	) ?? $html;
}

/**
 * Renders review showcase shortcode.
 *
 * @param array<string,mixed> $atts Shortcode attributes.
 * @return string
 */
function lmhg_site_core_reviews_shortcode( array|string $atts = array() ): string {
	$atts = is_array( $atts ) ? $atts : array();
	$atts = shortcode_atts(
		array(
			'count'   => '',
			'heading' => '',
			'intro'   => '',
		),
		$atts,
		'lmhg_reviews'
	);

	return lmhg_site_core_render_review_showcase( $atts );
}

/**
 * Renders the review showcase.
 *
 * @param array<string,mixed> $args Optional render arguments.
 * @return string
 */
function lmhg_site_core_render_review_showcase( array $args = array() ): string {
	$settings = lmhg_site_core_review_settings();
	if ( '1' !== (string) $settings['enabled'] ) {
		return '';
	}

	$max_reviews = isset( $args['count'] ) && '' !== $args['count']
		? min( 12, max( 1, absint( $args['count'] ) ) )
		: (int) $settings['max_reviews'];

	$reviews = lmhg_site_core_featured_reviews( $max_reviews );
	$summary = lmhg_site_core_render_review_summary( $settings );
	$cards   = lmhg_site_core_render_review_cards( $reviews, $settings );

	if ( '' === $summary && '' === $cards ) {
		return '';
	}

	lmhg_site_core_enqueue_review_assets();

	$heading = '' !== trim( (string) ( $args['heading'] ?? '' ) )
		? trim( (string) $args['heading'] )
		: (string) $settings['heading'];
	$intro = '' !== trim( (string) ( $args['intro'] ?? '' ) )
		? trim( (string) $args['intro'] )
		: (string) $settings['intro'];

	return sprintf(
		'<section class="lmhg-review-showcase" data-lmhg-review-showcase><div class="lmhg-review-showcase__header"><h2>%1$s</h2>%2$s</div>%3$s%4$s</section>',
		esc_html( $heading ),
		'' !== $intro ? '<p>' . esc_html( $intro ) . '</p>' : '',
		$summary,
		$cards
	);
}

/**
 * Queries featured review posts.
 *
 * @param int $limit Maximum reviews.
 * @return WP_Post[]
 */
function lmhg_site_core_featured_reviews( int $limit ): array {
	$query = new WP_Query(
		array(
			'post_type'              => LMHG_SITE_CORE_REVIEW_POST_TYPE,
			'post_status'            => 'publish',
			'posts_per_page'         => $limit,
			'orderby'                => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
			'meta_query'             => array(
				array(
					'key'   => '_lmhg_review_featured',
					'value' => '1',
				),
			),
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
		)
	);

	return $query->posts;
}

/**
 * Renders the aggregate review summary.
 *
 * @param array<string,string|int> $settings Review settings.
 * @return string
 */
function lmhg_site_core_render_review_summary( array $settings ): string {
	$rating = $settings['overall_rating'];
	if ( '' === $rating ) {
		return '';
	}

	$rating_text = sprintf( '%s / 5', esc_html( lmhg_site_core_format_rating( (float) $rating ) ) );
	$count       = (int) $settings['review_count'];
	$label       = '' !== (string) $settings['summary_label'] ? (string) $settings['summary_label'] : 'Google rating';
	$source_url  = (string) $settings['google_profile_url'];
	$count_text  = $count > 0 ? sprintf( 'from %s reviews', number_format_i18n( $count ) ) : '';
	$summary     = sprintf(
		'<div><p class="lmhg-review-showcase__label">%1$s</p><p class="lmhg-review-showcase__rating" aria-label="%2$s">%3$s</p>%4$s</div>',
		esc_html( $label ),
		esc_attr( sprintf( '%s out of 5', lmhg_site_core_format_rating( (float) $rating ) ) ),
		esc_html( $rating_text ),
		'' !== $count_text ? '<p class="lmhg-review-showcase__count">' . esc_html( $count_text ) . '</p>' : ''
	);

	if ( '' !== $source_url ) {
		$summary .= sprintf(
			'<a class="lmhg-review-showcase__source" href="%1$s" target="_blank" rel="nofollow noopener noreferrer">View Google profile</a>',
			esc_url( $source_url )
		);
	}

	return '<div class="lmhg-review-showcase__summary">' . $summary . '</div>';
}

/**
 * Renders review cards.
 *
 * @param WP_Post[]                $reviews Review posts.
 * @param array<string,string|int> $settings Review settings.
 * @return string
 */
function lmhg_site_core_render_review_cards( array $reviews, array $settings ): string {
	if ( empty( $reviews ) ) {
		return '';
	}

	$cards = array();
	foreach ( $reviews as $review ) {
		if ( ! $review instanceof WP_Post ) {
			continue;
		}

		$reviewer = trim( wp_strip_all_tags( get_the_title( $review ) ) );
		$quote    = trim( wp_strip_all_tags( get_post_field( 'post_content', $review->ID ) ) );
		if ( '' === $reviewer || '' === $quote ) {
			continue;
		}

		$rating      = get_post_meta( $review->ID, '_lmhg_review_rating', true );
		$review_date = lmhg_site_core_sanitize_review_date( get_post_meta( $review->ID, '_lmhg_review_date', true ) );
		$source_name = trim( (string) get_post_meta( $review->ID, '_lmhg_review_source_name', true ) );
		$source_url  = trim( (string) get_post_meta( $review->ID, '_lmhg_review_source_url', true ) );

		if ( '' === $source_name ) {
			$source_name = 'Google';
		}
		if ( '' === $source_url ) {
			$source_url = (string) $settings['google_profile_url'];
		}

		$meta = array();
		if ( '' !== $rating ) {
			$meta[] = sprintf(
				'<span class="lmhg-review-card__rating" aria-label="%1$s">%2$s</span>',
				esc_attr( sprintf( '%s out of 5', lmhg_site_core_format_rating( (float) $rating ) ) ),
				esc_html( lmhg_site_core_format_rating( (float) $rating ) . ' / 5' )
			);
		}
		if ( '' !== $review_date ) {
			$meta[] = sprintf(
				'<time datetime="%1$s">%2$s</time>',
				esc_attr( $review_date ),
				esc_html( date_i18n( get_option( 'date_format' ), strtotime( $review_date ) ) )
			);
		}

		$source = '' !== $source_url
			? sprintf( '<a href="%1$s" target="_blank" rel="nofollow noopener noreferrer">%2$s</a>', esc_url( $source_url ), esc_html( $source_name ) )
			: esc_html( $source_name );

		$cards[] = sprintf(
			'<article class="lmhg-review-card"><div class="lmhg-review-card__meta">%1$s</div><blockquote>%2$s</blockquote><footer><strong>%3$s</strong><span>%4$s</span></footer></article>',
			implode( '<span aria-hidden="true">/</span>', $meta ),
			wpautop( esc_html( $quote ) ),
			esc_html( $reviewer ),
			$source
		);
	}

	if ( empty( $cards ) ) {
		return '';
	}

	return '<div class="lmhg-review-showcase__grid">' . implode( '', $cards ) . '</div>';
}

/**
 * Builds JSON-LD graph nodes from manually selected review records.
 *
 * @param int $post_id Current page ID.
 * @return array<int,array<string,mixed>>
 */
function lmhg_site_core_review_showcase_schema_nodes( int $post_id ): array {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post || ! lmhg_site_core_page_exposes_review_showcase( $post ) ) {
		return array();
	}

	$settings    = lmhg_site_core_review_settings();
	$max_reviews = (int) $settings['max_reviews'];
	$reviews     = lmhg_site_core_featured_reviews( $max_reviews );
	$review_nodes = lmhg_site_core_review_schema_items( $reviews );
	$aggregate    = lmhg_site_core_review_aggregate_schema( $settings );

	if ( empty( $review_nodes ) && empty( $aggregate ) ) {
		return array();
	}

	$organization = array(
		'@type' => 'MedicalOrganization',
		'@id'   => home_url( '/#organization' ),
		'name'  => get_bloginfo( 'name' ),
		'url'   => home_url( '/' ),
	);

	if ( ! empty( $aggregate ) ) {
		$organization['aggregateRating'] = $aggregate;
	}

	if ( ! empty( $review_nodes ) ) {
		$organization['review'] = $review_nodes;
	}

	return array( $organization );
}

/**
 * Converts featured manual review posts into schema-safe Review items.
 *
 * @param WP_Post[] $reviews Review posts.
 * @return array<int,array<string,mixed>>
 */
function lmhg_site_core_review_schema_items( array $reviews ): array {
	$items = array();

	foreach ( $reviews as $review ) {
		if ( ! $review instanceof WP_Post ) {
			continue;
		}

		$reviewer = trim( wp_strip_all_tags( get_the_title( $review ) ) );
		$quote    = trim( wp_strip_all_tags( get_post_field( 'post_content', $review->ID ) ) );
		if ( '' === $reviewer || '' === $quote ) {
			continue;
		}

		$item = array(
			'@type'        => 'Review',
			'author'       => array(
				'@type' => 'Person',
				'name'  => $reviewer,
			),
			'reviewBody'   => $quote,
			'itemReviewed' => array(
				'@id' => home_url( '/#organization' ),
			),
		);

		$rating = get_post_meta( $review->ID, '_lmhg_review_rating', true );
		if ( '' !== $rating ) {
			$item['reviewRating'] = array(
				'@type'       => 'Rating',
				'ratingValue' => lmhg_site_core_format_rating( (float) $rating ),
				'bestRating'  => '5',
				'worstRating' => '1',
			);
		}

		$review_date = lmhg_site_core_sanitize_review_date( get_post_meta( $review->ID, '_lmhg_review_date', true ) );
		if ( '' !== $review_date ) {
			$item['datePublished'] = $review_date;
		}

		$items[] = $item;
	}

	return $items;
}

/**
 * Builds AggregateRating schema from manual review showcase settings.
 *
 * @param array<string,string|int> $settings Review settings.
 * @return array<string,mixed>
 */
function lmhg_site_core_review_aggregate_schema( array $settings ): array {
	if ( '' === (string) $settings['overall_rating'] ) {
		return array();
	}

	$aggregate = array(
		'@type'       => 'AggregateRating',
		'ratingValue' => lmhg_site_core_format_rating( (float) $settings['overall_rating'] ),
		'bestRating'  => '5',
		'worstRating' => '1',
	);

	$count = (int) $settings['review_count'];
	if ( $count > 0 ) {
		$aggregate['ratingCount'] = $count;
		$aggregate['reviewCount'] = $count;
	}

	return $aggregate;
}

/**
 * Formats ratings without trailing .0 noise.
 *
 * @param float $rating Rating value.
 * @return string
 */
function lmhg_site_core_format_rating( float $rating ): string {
	return rtrim( rtrim( number_format_i18n( $rating, 1 ), '0' ), '.' );
}
