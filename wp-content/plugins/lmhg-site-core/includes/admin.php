<?php
/**
 * Consolidated LMHG Site administration.
 *
 * @package LMHGSiteCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const LMHG_SITE_CORE_ADMIN_SLUG = 'lmhg-site';

add_action( 'admin_menu', 'lmhg_site_core_register_admin_hub', 9 );
add_action( 'admin_init', 'lmhg_site_core_register_global_settings' );
add_action( 'admin_post_lmhg_bulk_assign_cta', 'lmhg_site_core_handle_bulk_cta_assignment' );
add_filter( 'manage_pages_columns', 'lmhg_site_core_filter_page_columns', 30 );
add_action( 'manage_pages_custom_column', 'lmhg_site_core_render_page_column', 10, 2 );
add_filter( 'manage_edit-' . LMHG_SITE_CORE_CTA_TAXONOMY . '_columns', 'lmhg_site_core_filter_cta_term_columns' );
add_filter( 'manage_' . LMHG_SITE_CORE_CTA_TAXONOMY . '_custom_column', 'lmhg_site_core_render_cta_term_column', 10, 3 );

/** Registers the single LMHG Site menu and its child screens. */
function lmhg_site_core_register_admin_hub(): void {
	add_menu_page( 'LMHG Site', 'LMHG Site', 'edit_posts', LMHG_SITE_CORE_ADMIN_SLUG, 'lmhg_site_core_render_admin_overview', 'dashicons-admin-site-alt3', 24 );
	add_submenu_page( LMHG_SITE_CORE_ADMIN_SLUG, 'LMHG Site Overview', 'Overview', 'edit_posts', LMHG_SITE_CORE_ADMIN_SLUG, 'lmhg_site_core_render_admin_overview' );
	add_submenu_page( LMHG_SITE_CORE_ADMIN_SLUG, 'LMHG Page Controls', 'Page Controls', 'manage_options', 'lmhg-page-controls', 'lmhg_site_core_render_page_controls' );
	add_submenu_page( LMHG_SITE_CORE_ADMIN_SLUG, 'CTA Variants', 'CTA Variants', 'manage_options', 'edit-tags.php?taxonomy=' . LMHG_SITE_CORE_CTA_TAXONOMY . '&post_type=page' );
	add_submenu_page( LMHG_SITE_CORE_ADMIN_SLUG, 'LMHG Specialties', 'Specialties', 'manage_categories', 'edit-tags.php?taxonomy=' . LMHG_SITE_CORE_SPECIALTY_TAXONOMY . '&post_type=page' );
	add_submenu_page( LMHG_SITE_CORE_ADMIN_SLUG, 'LMHG FAQ Sets', 'FAQ Sets', 'manage_categories', 'edit-tags.php?taxonomy=' . LMHG_SITE_CORE_FAQ_SET_TAXONOMY . '&post_type=page' );
	add_submenu_page( LMHG_SITE_CORE_ADMIN_SLUG, 'LMHG Taxonomies', 'Taxonomies', 'manage_categories', 'lmhg-taxonomies', 'lmhg_site_core_render_taxonomy_overview' );
	add_submenu_page( LMHG_SITE_CORE_ADMIN_SLUG, 'LMHG Media Quality', 'Media Quality', 'upload_files', 'lmhg-media-quality', 'lmhg_site_core_render_media_quality' );
	add_submenu_page( LMHG_SITE_CORE_ADMIN_SLUG, 'LMHG SEO Overview', 'SEO Overview', 'edit_pages', 'lmhg-seo-overview', 'lmhg_site_core_render_seo_overview' );
	add_submenu_page( LMHG_SITE_CORE_ADMIN_SLUG, 'LMHG FAQs', 'FAQs', 'edit_posts', 'edit.php?post_type=' . LMHG_SITE_CORE_FAQ_POST_TYPE );
	add_submenu_page( LMHG_SITE_CORE_ADMIN_SLUG, 'LMHG Team', 'Team', 'edit_posts', 'edit.php?post_type=' . LMHG_SITE_CORE_TEAM_POST_TYPE );
	add_submenu_page( LMHG_SITE_CORE_ADMIN_SLUG, 'LMHG Reviews', 'Reviews', 'edit_posts', 'edit.php?post_type=' . LMHG_SITE_CORE_REVIEW_POST_TYPE );
	add_submenu_page( LMHG_SITE_CORE_ADMIN_SLUG, 'LMHG Site Settings', 'Settings', 'manage_options', 'lmhg-site-settings', 'lmhg_site_core_render_settings' );
}

/** Registers global Reach Out settings. */
function lmhg_site_core_register_global_settings(): void {
	register_setting(
		'lmhg_site_core_global',
		LMHG_SITE_CORE_CTA_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'lmhg_site_core_sanitize_reach_out_settings',
			'default'           => array( 'label' => 'Reach Out', 'url' => LMHG_SITE_CORE_CTA_DEFAULT_URL ),
		)
	);
}

/** Sanitizes the global button label and URL. */
function lmhg_site_core_sanitize_reach_out_settings( mixed $value ): array {
	$value = is_array( $value ) ? $value : array();
	$label = sanitize_text_field( (string) ( $value['label'] ?? '' ) );
	$url   = esc_url_raw( (string) ( $value['url'] ?? '' ), array( 'http', 'https' ) );
	add_settings_error( LMHG_SITE_CORE_CTA_OPTION, 'saved', 'Global Reach Out settings saved.', 'success' );
	return array( 'label' => '' !== $label ? $label : 'Reach Out', 'url' => $url );
}

/** Renders the hub overview. */
function lmhg_site_core_render_admin_overview(): void {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}
	$cards = array(
		'Page Controls' => array( 'url' => admin_url( 'admin.php?page=lmhg-page-controls' ), 'text' => 'Review publication readiness and assign approved CTA variants.' ),
		'SEO Overview'  => array( 'url' => admin_url( 'admin.php?page=lmhg-seo-overview' ), 'text' => 'Review primary and secondary keyword coverage.' ),
		'Media Quality' => array( 'url' => admin_url( 'admin.php?page=lmhg-media-quality' ), 'text' => 'Review alt text, intrinsic dimensions, durable roles, and attachment use.' ),
		'FAQs'          => array( 'url' => admin_url( 'edit.php?post_type=' . LMHG_SITE_CORE_FAQ_POST_TYPE ), 'text' => 'Manage reusable FAQ records and sets.' ),
		'Team'          => array( 'url' => admin_url( 'edit.php?post_type=' . LMHG_SITE_CORE_TEAM_POST_TYPE ), 'text' => 'Manage team profiles and credentials.' ),
		'Reviews'       => array( 'url' => admin_url( 'edit.php?post_type=' . LMHG_SITE_CORE_REVIEW_POST_TYPE ), 'text' => 'Manage curated review records.' ),
	);
	$readiness = function_exists( 'lmhg_site_core_readiness_counts' ) ? lmhg_site_core_readiness_counts() : array();
	echo '<div class="wrap"><h1>LMHG Site</h1><p>Central administration for LMHG page relationships, reusable content, CTA variants, and SEO visibility.</p><div class="card-grid">';
	if ( ! empty( $readiness ) ) {
		echo '<div class="card"><h2>Publication Readiness</h2><p><strong>' . esc_html( (string) ( $readiness['ready'] ?? 0 ) ) . '</strong> ready &nbsp; <strong>' . esc_html( (string) ( $readiness['needs-work'] ?? 0 ) ) . '</strong> need work &nbsp; <strong>' . esc_html( (string) ( $readiness['exempt'] ?? 0 ) ) . '</strong> exempt</p><p><a class="button" href="' . esc_url( admin_url( 'admin.php?page=lmhg-page-controls' ) ) . '">Review pages</a></p></div>';
	}
	foreach ( $cards as $label => $card ) {
		echo '<div class="card"><h2><a href="' . esc_url( $card['url'] ) . '">' . esc_html( $label ) . '</a></h2><p>' . esc_html( $card['text'] ) . '</p></div>';
	}
	echo '</div></div>';
}

/** Renders a read-only launch-quality view of Media Library images. */
function lmhg_site_core_render_media_quality(): void {
	if ( ! current_user_can( 'upload_files' ) ) {
		return;
	}

	$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	$paged  = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 );
	$query  = new WP_Query(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => 'image',
			'posts_per_page' => 40,
			'paged'          => $paged,
			'orderby'        => 'title',
			'order'          => 'ASC',
			's'              => $search,
		)
	);

	global $wpdb;
	$total_images = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_status = 'inherit' AND post_mime_type LIKE 'image/%'" );
	$missing_alt = (int) $wpdb->get_var(
		"SELECT COUNT(*) FROM {$wpdb->posts} p
		LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_wp_attachment_image_alt'
		WHERE p.post_type = 'attachment' AND p.post_status = 'inherit' AND p.post_mime_type LIKE 'image/%'
		AND (pm.meta_id IS NULL OR TRIM(pm.meta_value) = '')"
	);
	$duplicate_roles = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM (SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value <> '' GROUP BY meta_value HAVING COUNT(*) > 1) roles",
			defined( 'LMHG_SITE_CORE_MEDIA_ASSET_ROLE_META' ) ? LMHG_SITE_CORE_MEDIA_ASSET_ROLE_META : '_lmhg_asset_role'
		)
	);

	echo '<div class="wrap"><h1>Media Quality</h1><p>Read-only launch view for image semantics and stability. Empty alt text is valid only when an image is intentionally decorative.</p>';
	echo '<div class="card" style="max-width:none"><p><strong>' . esc_html( (string) $total_images ) . '</strong> image records &nbsp; <strong>' . esc_html( (string) $missing_alt ) . '</strong> empty alt values &nbsp; <strong>' . esc_html( (string) $duplicate_roles ) . '</strong> duplicate durable roles</p></div>';
	echo '<form method="get"><input type="hidden" name="page" value="lmhg-media-quality"><p class="search-box"><label class="screen-reader-text" for="lmhg-media-search">Search media</label><input id="lmhg-media-search" type="search" name="s" value="' . esc_attr( $search ) . '"><input class="button" type="submit" value="Search Media"></p></form>';
	echo '<table class="widefat fixed striped"><thead><tr><th>Preview</th><th>Image</th><th>Durable Role</th><th>Alt Text</th><th>Dimensions</th><th>Attached To</th><th>Quality</th></tr></thead><tbody>';
	foreach ( $query->posts as $attachment ) {
		$attachment_id = (int) $attachment->ID;
		$alt = trim( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );
		$role = trim( (string) get_post_meta( $attachment_id, defined( 'LMHG_SITE_CORE_MEDIA_ASSET_ROLE_META' ) ? LMHG_SITE_CORE_MEDIA_ASSET_ROLE_META : '_lmhg_asset_role', true ) );
		$metadata = wp_get_attachment_metadata( $attachment_id );
		$width = is_array( $metadata ) ? absint( $metadata['width'] ?? 0 ) : 0;
		$height = is_array( $metadata ) ? absint( $metadata['height'] ?? 0 ) : 0;
		$quality = array();
		if ( '' === $alt ) {
			$quality[] = 'Review empty alt';
		}
		if ( $width <= 0 || $height <= 0 ) {
			$quality[] = 'Missing dimensions';
		}
		$parent_id = (int) $attachment->post_parent;
		$parent = $parent_id > 0 ? get_post( $parent_id ) : null;
		echo '<tr><td>' . wp_get_attachment_image( $attachment_id, array( 80, 80 ), true, array( 'style' => 'max-width:80px;height:auto' ) ) . '</td><td><strong><a href="' . esc_url( get_edit_post_link( $attachment_id ) ) . '">' . esc_html( get_the_title( $attachment ) ) . '</a></strong><br><small>' . esc_html( basename( (string) get_attached_file( $attachment_id ) ) ) . '</small></td><td><code>' . esc_html( $role ?: '—' ) . '</code></td><td>' . esc_html( $alt ?: '—' ) . '</td><td>' . esc_html( $width > 0 && $height > 0 ? $width . ' × ' . $height : '—' ) . '</td><td>' . ( $parent instanceof WP_Post ? '<a href="' . esc_url( get_edit_post_link( $parent ) ) . '">' . esc_html( get_the_title( $parent ) ) . '</a>' : 'Unattached / reusable' ) . '</td><td>' . esc_html( empty( $quality ) ? 'Ready' : implode( '; ', $quality ) ) . '</td></tr>';
	}
	if ( empty( $query->posts ) ) {
		echo '<tr><td colspan="7">No images found.</td></tr>';
	}
	echo '</tbody></table>';
	lmhg_site_core_render_pagination( $paged, (int) $query->max_num_pages, 'lmhg-media-quality', array( 's' => $search ) );
	echo '</div>';
}

/** Renders the administrator-only CTA assignment table. */
function lmhg_site_core_render_page_controls(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to manage CTA assignments.', 'lmhg-site-core' ) );
	}

	$search           = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	$filter           = isset( $_GET['cta_variant'] ) ? sanitize_key( wp_unslash( $_GET['cta_variant'] ) ) : '';
	$readiness_filter = isset( $_GET['readiness'] ) ? sanitize_key( wp_unslash( $_GET['readiness'] ) ) : '';
	$paged            = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 );
	$per_page         = 25;
	$args             = array( 'post_type' => 'page', 'post_status' => array( 'publish', 'draft', 'private' ), 'posts_per_page' => $per_page, 'paged' => $paged, 'orderby' => 'title', 'order' => 'ASC', 's' => $search );
	if ( '' !== $filter ) {
		$args['tax_query'] = array( array( 'taxonomy' => LMHG_SITE_CORE_CTA_TAXONOMY, 'field' => 'slug', 'terms' => $filter ) );
	}
	$args  = lmhg_site_core_apply_readiness_filter( $args, $readiness_filter );
	$query = new WP_Query( $args );
	$terms = get_terms( array( 'taxonomy' => LMHG_SITE_CORE_CTA_TAXONOMY, 'hide_empty' => false ) );
	$terms = is_wp_error( $terms ) ? array() : $terms;

	echo '<div class="wrap"><h1>Page Controls</h1><p>Review publication readiness and assign approved lower CTA variants. A page is ready when it is published, has primary and secondary keywords, and its copy is approved.</p>';
	if ( isset( $_GET['cta_updated'] ) || isset( $_GET['review_updated'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( (string) absint( $_GET['cta_updated'] ?? 0 ) ) . ' CTA assignments and ' . esc_html( (string) absint( $_GET['review_updated'] ?? 0 ) ) . ' copy-review states updated.</p></div>';
	}
	echo '<form method="get"><input type="hidden" name="page" value="lmhg-page-controls" /><p class="search-box"><label class="screen-reader-text" for="lmhg-page-search">Search Pages</label><input id="lmhg-page-search" type="search" name="s" value="' . esc_attr( $search ) . '" /><input class="button" type="submit" value="Search Pages" /></p><select name="cta_variant"><option value="">All CTA variants</option>';
	foreach ( $terms as $term ) {
		echo '<option value="' . esc_attr( $term->slug ) . '" ' . selected( $filter, $term->slug, false ) . '>' . esc_html( $term->name ) . '</option>';
	}
	echo '</select><select name="readiness"><option value="">All readiness states</option>';
	foreach ( lmhg_site_core_readiness_filter_states() as $value => $label ) {
		echo '<option value="' . esc_attr( $value ) . '" ' . selected( $readiness_filter, $value, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select> <button class="button">Filter</button></form>';

	echo '<form action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" method="post"><input type="hidden" name="action" value="lmhg_bulk_assign_cta" />';
	wp_nonce_field( 'lmhg_bulk_assign_cta', 'lmhg_cta_bulk_nonce' );
	echo '<div class="tablenav top"><div class="alignleft actions"><select name="cta_term_id"><option value="">Keep current CTA</option>';
	foreach ( $terms as $term ) {
		echo '<option value="' . esc_attr( (string) $term->term_id ) . '">' . esc_html( $term->name ) . '</option>';
	}
	echo '</select><select name="copy_review_status"><option value="">Keep copy-review state</option>';
	foreach ( lmhg_site_core_copy_review_states() as $value => $label ) {
		echo '<option value="' . esc_attr( $value ) . '">' . esc_html( $label ) . '</option>';
	}
	echo '</select><button class="button action" type="submit">Apply</button></div></div>';
	echo '<table class="wp-list-table widefat fixed striped table-view-list pages"><thead><tr><td class="manage-column column-cb check-column"><input type="checkbox" id="cb-select-all-1" /></td><th>Page</th><th>Slug / Path</th><th>Readiness</th><th>Copy Review</th><th>Keywords</th><th>CTA Variant</th><th>Resolved CTA</th></tr></thead><tbody>';
	foreach ( $query->posts as $post ) {
		$term      = lmhg_site_core_page_cta_term( (int) $post->ID );
		$readiness = lmhg_site_core_page_readiness( (int) $post->ID );
		$states    = lmhg_site_core_copy_review_states();
		echo '<tr><th class="check-column"><input type="checkbox" name="page_ids[]" value="' . esc_attr( (string) $post->ID ) . '" /></th><td><strong><a href="' . esc_url( get_edit_post_link( $post->ID ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a></strong><div class="row-actions"><a href="' . esc_url( get_permalink( $post ) ) . '">View</a></div></td><td><code>/' . esc_html( trim( get_page_uri( $post ), '/' ) ) . '/</code></td>';
		echo '<td><strong>' . esc_html( $readiness['label'] ) . '</strong>' . ( empty( $readiness['blockers'] ) ? '' : '<br><small>' . esc_html( implode( '; ', $readiness['blockers'] ) ) . '</small>' ) . '</td>';
		echo '<td>' . esc_html( $states[ $readiness['copy_review'] ] ?? 'Needs Copy Review' ) . '</td>';
		echo '<td>' . ( '' !== $readiness['primary'] ? '<span aria-label="Primary keyword present">Primary ✓</span>' : '<span>Primary —</span>' ) . '<br>' . ( ! empty( $readiness['secondary'] ) ? '<span aria-label="Secondary keyword present">Secondary ✓</span>' : '<span>Secondary —</span>' ) . '</td>';
		if ( $term instanceof WP_Term ) {
			$title = (string) get_term_meta( $term->term_id, '_lmhg_cta_title', true );
			$desc  = (string) get_term_meta( $term->term_id, '_lmhg_cta_description', true );
			echo '<td>' . esc_html( $term->name ) . '</td><td><strong>' . esc_html( $title ) . '</strong><br><small>' . esc_html( wp_trim_words( $desc, 12 ) ) . '</small></td>';
		} else {
			echo '<td><span class="notice-error">Missing</span></td><td>—</td>';
		}
		echo '</tr>';
	}
	if ( empty( $query->posts ) ) {
		echo '<tr><td colspan="8">No pages found.</td></tr>';
	}
	echo '</tbody></table></form>';
	lmhg_site_core_render_pagination( $paged, (int) $query->max_num_pages, 'lmhg-page-controls', array( 's' => $search, 'cta_variant' => $filter, 'readiness' => $readiness_filter ) );
	echo '</div>';
}

/** Handles administrator-only bulk CTA assignment. */
function lmhg_site_core_handle_bulk_cta_assignment(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to assign CTA variants.', 'lmhg-site-core' ) );
	}
	check_admin_referer( 'lmhg_bulk_assign_cta', 'lmhg_cta_bulk_nonce' );
	$term_id        = isset( $_POST['cta_term_id'] ) ? absint( $_POST['cta_term_id'] ) : 0;
	$review_status  = isset( $_POST['copy_review_status'] ) ? lmhg_site_core_sanitize_copy_review_status( (string) wp_unslash( $_POST['copy_review_status'] ) ) : '';
	$page_ids       = isset( $_POST['page_ids'] ) && is_array( $_POST['page_ids'] ) ? array_unique( array_filter( array_map( 'absint', wp_unslash( $_POST['page_ids'] ) ) ) ) : array();
	$term           = $term_id > 0 ? get_term( $term_id, LMHG_SITE_CORE_CTA_TAXONOMY ) : null;
	$cta_updated    = 0;
	$review_updated = 0;
	foreach ( $page_ids as $page_id ) {
		if ( 'page' !== get_post_type( $page_id ) ) {
			continue;
		}
		if ( $term instanceof WP_Term && ! is_wp_error( wp_set_object_terms( $page_id, array( $term_id ), LMHG_SITE_CORE_CTA_TAXONOMY, false ) ) ) {
			++$cta_updated;
		}
		if ( '' !== $review_status ) {
			update_post_meta( $page_id, LMHG_SITE_CORE_COPY_REVIEW_META, $review_status );
			++$review_updated;
		}
	}
	wp_safe_redirect( add_query_arg( array( 'cta_updated' => $cta_updated, 'review_updated' => $review_updated ), admin_url( 'admin.php?page=lmhg-page-controls' ) ) );
	exit;
}

/** Renders the consolidated technical taxonomy overview. */
function lmhg_site_core_render_taxonomy_overview(): void {
	if ( ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	$definitions = lmhg_site_core_taxonomy_definitions();
	$selected    = isset( $_GET['taxonomy_tab'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy_tab'] ) ) : (string) array_key_first( $definitions );
	if ( ! isset( $definitions[ $selected ] ) ) {
		$selected = (string) array_key_first( $definitions );
	}
	echo '<div class="wrap"><h1>LMHG Taxonomies</h1><nav class="nav-tab-wrapper">';
	foreach ( $definitions as $taxonomy => $definition ) {
		$url = add_query_arg( array( 'page' => 'lmhg-taxonomies', 'taxonomy_tab' => $taxonomy ), admin_url( 'admin.php' ) );
		echo '<a class="nav-tab ' . ( $selected === $taxonomy ? 'nav-tab-active' : '' ) . '" href="' . esc_url( $url ) . '">' . esc_html( $definition['label'] ) . '</a>';
	}
	echo '</nav>';
	$terms = get_terms( array( 'taxonomy' => $selected, 'hide_empty' => false ) );
	$terms = is_wp_error( $terms ) ? array() : $terms;
	echo '<p><a class="button button-primary" href="' . esc_url( admin_url( 'edit-tags.php?taxonomy=' . $selected . '&post_type=page' ) ) . '">Manage ' . esc_html( $definitions[ $selected ]['label'] ) . '</a></p><table class="widefat striped"><thead><tr><th>Name</th><th>Slug</th><th>Pages</th></tr></thead><tbody>';
	foreach ( $terms as $term ) {
		echo '<tr><td><a href="' . esc_url( get_edit_term_link( $term, $selected, 'page' ) ) . '">' . esc_html( $term->name ) . '</a></td><td><code>' . esc_html( $term->slug ) . '</code></td><td>' . esc_html( (string) $term->count ) . '</td></tr>';
	}
	if ( empty( $terms ) ) {
		echo '<tr><td colspan="3">No terms found.</td></tr>';
	}
	echo '</tbody></table></div>';
}

/** Renders the read-only SEO metadata overview. */
function lmhg_site_core_render_seo_overview(): void {
	if ( ! current_user_can( 'edit_pages' ) ) {
		return;
	}
	$search           = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	$readiness_filter = isset( $_GET['readiness'] ) ? sanitize_key( wp_unslash( $_GET['readiness'] ) ) : '';
	$issue_filter     = isset( $_GET['seo_issue'] ) ? sanitize_key( wp_unslash( $_GET['seo_issue'] ) ) : '';
	$paged            = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 );
	$query_args       = array( 'post_type' => 'page', 'post_status' => array( 'publish', 'draft', 'private' ), 'posts_per_page' => 25, 'paged' => $paged, 'orderby' => 'title', 'order' => 'ASC', 's' => $search );
	$query_args       = lmhg_site_core_apply_readiness_filter( $query_args, $readiness_filter );
	$issue_filters    = array(
		'long-title'     => 'SEO title over 60 characters',
		'short-description' => 'Description under 120 characters',
		'missing-social' => 'Missing social share image',
		'missing-keywords' => 'Missing primary or secondary keyword',
		'copy-review'    => 'Copy review required',
	);
	if ( isset( $issue_filters[ $issue_filter ] ) ) {
		$candidate_ids = get_posts( array( 'post_type' => 'page', 'post_status' => array( 'publish', 'draft', 'private' ), 'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => true ) );
		$issue_ids = array_values( array_filter( array_map( 'absint', $candidate_ids ), static fn( int $post_id ): bool => lmhg_site_core_page_matches_seo_issue( $post_id, $issue_filter ) ) );
		if ( isset( $query_args['post__in'] ) && is_array( $query_args['post__in'] ) ) {
			$issue_ids = array_values( array_intersect( array_map( 'absint', $query_args['post__in'] ), $issue_ids ) );
		}
		$query_args['post__in'] = ! empty( $issue_ids ) ? $issue_ids : array( 0 );
	}
	$query       = new WP_Query( $query_args );
	$integration = function_exists( 'lmhg_site_core_rank_math_handoff_state' ) ? lmhg_site_core_rank_math_handoff_state() : array();
	$rankmath    = ! empty( $integration['active'] );
	$rankmath_pro = ! empty( $integration['pro_active'] );
	$handoff_complete = ! empty( $integration['complete'] );
	$owner       = (string) ( $integration['owner'] ?? 'LMHG' );
	$status      = (string) ( $integration['status'] ?? 'not-installed' );
	$status_labels = array(
		'not-installed'    => 'Waiting for Rank Math Free and PRO',
		'pro-required'     => 'Blocked: Rank Math PRO is not active',
		'conflicts'        => 'Blocked: resolve reported conflicts',
		'ready'            => 'Ready for administrator-approved handoff',
		'complete'         => 'Complete',
		'fallback-active'  => 'Rank Math unavailable; LMHG fallback active',
		'rollback-blocked' => 'Rollback blocked by later manual edits',
	);
	echo '<div class="wrap"><h1>SEO Overview</h1><p>Read-only launch view for keyword coverage, search snippets, social images, and schema. <strong>Current standard SEO owner:</strong> ' . esc_html( $owner ) . '.</p>';
	echo '<div class="card" style="max-width:none"><h2>SEO and schema ownership</h2><p><strong>LMHG Site Core owns schema intent:</strong> each page role and base page type come from the LMHG schema taxonomy. <strong>Rank Math owns standard SEO and final public graph delivery</strong> while the completed integration is active; Site Core emits the equivalent fallback graph when Rank Math is unavailable.</p><p><strong>Rank Math Free:</strong> ' . esc_html( $rankmath ? ( defined( 'RANK_MATH_VERSION' ) ? 'Active ' . RANK_MATH_VERSION : 'Active' ) : 'Not active' ) . ' &nbsp; <strong>PRO:</strong> ' . esc_html( $rankmath_pro ? ( defined( 'RANK_MATH_PRO_VERSION' ) ? 'Active ' . RANK_MATH_PRO_VERSION : 'Active' ) : 'Not active' ) . ' &nbsp; <strong>Handoff:</strong> ' . esc_html( $status_labels[ $status ] ?? $status ) . '</p><p><strong>Journal:</strong> ' . esc_html( (string) ( $integration['journal_status'] ?? '' ) ?: 'No apply journal yet' ) . ( ! empty( $integration['journal_id'] ) ? ' · <code>' . esc_html( (string) $integration['journal_id'] ) . '</code>' : '' ) . '</p><p>The content-analysis bridge adds only LMHG taxonomy-driven sections. Rank Math public metadata and write-heavy modules stay disabled until a conflict-free, journaled handoff completes. If Rank Math is deactivated later, LMHG fallback output resumes automatically.</p></div>';
	$handoff_notice = isset( $_GET['handoff'] ) ? sanitize_key( wp_unslash( $_GET['handoff'] ) ) : '';
	$rollback_notice = isset( $_GET['rollback'] ) ? sanitize_key( wp_unslash( $_GET['rollback'] ) ) : '';
	$operation_reason = isset( $_GET['reason'] ) ? sanitize_key( wp_unslash( $_GET['reason'] ) ) : '';
	if ( 'complete' === $handoff_notice ) {
		echo '<div class="notice notice-success"><p>Rank Math handoff completed and the durable rollback journal was verified.</p></div>';
	} elseif ( 'blocked' === $handoff_notice ) {
		echo '<div class="notice notice-error"><p>Rank Math handoff made no committed changes. Reason: <code>' . esc_html( $operation_reason ?: 'blocked' ) . '</code>.</p></div>';
	}
	if ( 'complete' === $rollback_notice ) {
		echo '<div class="notice notice-success"><p>The most recent Rank Math handoff was rolled back. LMHG owns standard SEO output again.</p></div>';
	} elseif ( 'blocked' === $rollback_notice ) {
		echo '<div class="notice notice-error"><p>Rollback stopped because one or more Rank Math values changed after handoff. Those manual edits were preserved.</p></div>';
	}
	echo '<form method="get"><input type="hidden" name="page" value="lmhg-seo-overview" /><p class="search-box"><label class="screen-reader-text" for="lmhg-seo-search">Search Pages</label><input id="lmhg-seo-search" type="search" name="s" value="' . esc_attr( $search ) . '" /><input class="button" type="submit" value="Search Pages" /></p><select name="readiness"><option value="">All readiness states</option>';
	foreach ( lmhg_site_core_readiness_filter_states() as $value => $label ) {
		echo '<option value="' . esc_attr( $value ) . '" ' . selected( $readiness_filter, $value, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select><select name="seo_issue"><option value="">All SEO issues</option>';
	foreach ( $issue_filters as $value => $label ) {
		echo '<option value="' . esc_attr( $value ) . '" ' . selected( $issue_filter, $value, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select> <button class="button">Filter</button></form>';
	if ( $rankmath && $rankmath_pro && ! $handoff_complete && current_user_can( 'manage_options' ) ) {
		$preview = is_array( $integration['preview'] ?? null ) ? $integration['preview'] : lmhg_site_core_rank_math_handoff( false );
		$notice_class = (int) $preview['conflicts'] > 0 ? 'notice-error' : 'notice-info';
		echo '<div class="notice ' . esc_attr( $notice_class ) . '"><p><strong>Rank Math handoff dry run:</strong> ' . esc_html( (string) $preview['mapped'] ) . ' values ready; ' . esc_html( (string) $preview['conflicts'] ) . ' conflicts; zero writes performed.</p>';
		if ( (int) $preview['conflicts'] > 0 ) {
			echo '<p>The handoff is blocked until every conflict is resolved. Existing Rank Math values will never be overwritten automatically.</p><ul>';
			foreach ( array_slice( (array) $preview['conflict_items'], 0, 10 ) as $conflict ) {
				echo '<li><a href="' . esc_url( get_edit_post_link( (int) $conflict['post_id'] ) ) . '">' . esc_html( (string) $conflict['page_title'] ) . '</a>: <code>' . esc_html( (string) $conflict['target'] ) . '</code></li>';
			}
			echo '</ul>';
		} else {
			echo '<form action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" method="post"><input type="hidden" name="action" value="lmhg_rank_math_handoff" />';
			wp_nonce_field( 'lmhg_rank_math_handoff', 'lmhg_rank_math_nonce' );
			echo '<button class="button button-primary" type="submit">Apply Conflict-Free Handoff</button></form>';
		}
		echo '</div>';
	}
	if ( $handoff_complete && current_user_can( 'manage_options' ) && ! empty( $integration['journal_id'] ) ) {
		echo '<div class="notice notice-warning"><p><strong>Rollback safety:</strong> rollback restores only values written by the journal and stops if any were manually changed later.</p><form action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" method="post"><input type="hidden" name="action" value="lmhg_rank_math_handoff_rollback" />';
		wp_nonce_field( 'lmhg_rank_math_handoff_rollback', 'lmhg_rank_math_rollback_nonce' );
		echo '<button class="button" type="submit">Roll Back Most Recent Handoff</button></form></div>';
	}
	echo '<table class="widefat fixed striped"><thead><tr><th>Page</th><th>Slug / Path</th><th>Keywords</th><th>Search Snippet</th><th>Schema</th><th>Source</th><th>Readiness</th><th>Warnings</th></tr></thead><tbody>';
	foreach ( $query->posts as $post ) {
		$data      = lmhg_site_core_admin_keyword_data( (int) $post->ID );
		$status    = (string) get_post_meta( $post->ID, '_lmhg_seo_status', true );
		$readiness = lmhg_site_core_page_readiness( (int) $post->ID );
		$warnings  = $readiness['blockers'];
		$seo_title = function_exists( 'lmhg_site_core_resolved_seo_title_for_post' ) ? lmhg_site_core_resolved_seo_title_for_post( (int) $post->ID ) : get_the_title( $post );
		$meta_description = function_exists( 'lmhg_site_core_resolved_meta_description_for_post' ) ? lmhg_site_core_resolved_meta_description_for_post( (int) $post->ID ) : '';
		$schema_type = lmhg_site_core_resolved_schema_type_for_page( (int) $post->ID );
		$title_length = mb_strlen( wp_strip_all_tags( $seo_title ) );
		$description_length = mb_strlen( wp_strip_all_tags( $meta_description ) );
		if ( 60 < $title_length ) {
			$warnings[] = 'SEO title exceeds 60 characters';
		}
		if ( 0 === $description_length ) {
			$warnings[] = 'Missing meta description';
		} elseif ( 160 < $description_length ) {
			$warnings[] = 'Meta description exceeds 160 characters';
		}
		if ( ! has_post_thumbnail( $post->ID ) ) {
			$warnings[] = 'No page-specific social share image';
		}
		if ( $rankmath && $data['mismatch'] ) {
			$warnings[] = 'Rank Math differs from LMHG provenance';
		}
		echo '<tr><td><a href="' . esc_url( get_edit_post_link( $post->ID ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a><br><small>SEO status: ' . esc_html( $status ?: '—' ) . '</small></td><td><code>/' . esc_html( trim( get_page_uri( $post ), '/' ) ) . '/</code></td><td><strong>Primary:</strong> ' . esc_html( $data['primary'] ?: '—' ) . '<br><small><strong>Secondary:</strong> ' . esc_html( implode( ', ', $data['secondary'] ) ?: '—' ) . '</small></td><td><strong>' . esc_html( $seo_title ) . '</strong><br><small>Title: ' . esc_html( (string) $title_length ) . ' chars · Description: ' . esc_html( (string) $description_length ) . ' chars</small><br><span>' . esc_html( $meta_description ?: '—' ) . '</span></td><td>' . esc_html( $schema_type ?: 'None' ) . '</td><td>' . esc_html( $data['source'] ) . '</td><td>' . esc_html( $readiness['label'] ) . '</td><td>' . esc_html( implode( '; ', array_unique( $warnings ) ) ?: '—' ) . '</td></tr>';
	}
	echo '</tbody></table>';
	lmhg_site_core_render_pagination( $paged, (int) $query->max_num_pages, 'lmhg-seo-overview', array( 's' => $search, 'readiness' => $readiness_filter, 'seo_issue' => $issue_filter ) );
	echo '</div>';
}

/** Returns whether a page belongs in one SEO Overview issue queue. */
function lmhg_site_core_page_matches_seo_issue( int $post_id, string $issue ): bool {
	$data = lmhg_site_core_admin_keyword_data( $post_id );
	$readiness = lmhg_site_core_page_readiness( $post_id );
	$title = function_exists( 'lmhg_site_core_resolved_seo_title_for_post' ) ? lmhg_site_core_resolved_seo_title_for_post( $post_id ) : get_the_title( $post_id );
	$description = function_exists( 'lmhg_site_core_resolved_meta_description_for_post' ) ? lmhg_site_core_resolved_meta_description_for_post( $post_id ) : '';

	return match ( $issue ) {
		'long-title'        => mb_strlen( wp_strip_all_tags( $title ) ) > 60,
		'short-description' => mb_strlen( wp_strip_all_tags( $description ) ) < 120,
		'missing-social'    => ! has_post_thumbnail( $post_id ),
		'missing-keywords'  => '' === trim( (string) $data['primary'] ) || empty( $data['secondary'] ),
		'copy-review'       => ! in_array( $readiness['copy_review'], array( 'approved', 'exempt' ), true ),
		default             => false,
	};
}

/** Returns primary/secondary keyword data from the active authority. */
function lmhg_site_core_admin_keyword_data( int $post_id ): array {
	$legacy_primary   = trim( (string) get_post_meta( $post_id, '_lmhg_primary_keyword', true ) );
	$legacy_secondary = json_decode( (string) get_post_meta( $post_id, '_lmhg_secondary_keywords', true ), true );
	$legacy_secondary = is_array( $legacy_secondary ) ? array_values( array_filter( array_map( 'sanitize_text_field', $legacy_secondary ) ) ) : array();
	if ( function_exists( 'lmhg_site_core_rank_math_owns_standard_seo' ) && lmhg_site_core_rank_math_owns_standard_seo() ) {
		$keywords  = array_values( array_filter( array_map( 'trim', explode( ',', (string) get_post_meta( $post_id, 'rank_math_focus_keyword', true ) ) ) ) );
		$primary   = (string) array_shift( $keywords );
		$mismatch  = '' !== $legacy_primary && ( $legacy_primary !== $primary || $legacy_secondary !== $keywords );
		return array( 'primary' => $primary, 'secondary' => $keywords, 'source' => 'Rank Math', 'mismatch' => $mismatch );
	}
	return array( 'primary' => $legacy_primary, 'secondary' => $legacy_secondary, 'source' => 'LMHG', 'mismatch' => false );
}

/** Renders global CTA and review settings beneath one hub screen. */
function lmhg_site_core_render_settings(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'reach-out';
	echo '<div class="wrap"><h1>LMHG Site Settings</h1><nav class="nav-tab-wrapper"><a class="nav-tab ' . ( 'reach-out' === $tab ? 'nav-tab-active' : '' ) . '" href="' . esc_url( admin_url( 'admin.php?page=lmhg-site-settings&tab=reach-out' ) ) . '">Reach Out</a><a class="nav-tab ' . ( 'reviews' === $tab ? 'nav-tab-active' : '' ) . '" href="' . esc_url( admin_url( 'admin.php?page=lmhg-site-settings&tab=reviews' ) ) . '">Reviews</a></nav>';
	if ( 'reviews' === $tab ) {
		lmhg_site_core_render_review_settings_page();
		echo '</div>';
		return;
	}
	$settings = lmhg_site_core_reach_out_settings();
	settings_errors( LMHG_SITE_CORE_CTA_OPTION );
	echo '<form action="options.php" method="post">';
	settings_fields( 'lmhg_site_core_global' );
	echo '<table class="form-table"><tr><th scope="row"><label for="lmhg-reach-out-label">Button label</label></th><td><input id="lmhg-reach-out-label" name="' . esc_attr( LMHG_SITE_CORE_CTA_OPTION ) . '[label]" type="text" class="regular-text" value="' . esc_attr( $settings['label'] ) . '" required /></td></tr><tr><th scope="row"><label for="lmhg-reach-out-url">Destination URL</label></th><td><input id="lmhg-reach-out-url" name="' . esc_attr( LMHG_SITE_CORE_CTA_OPTION ) . '[url]" type="url" class="regular-text code" value="' . esc_attr( $settings['url'] ) . '" required /></td></tr></table>';
	submit_button();
	echo '</form></div>';
}

/** Removes verbose taxonomy columns and adds compact LMHG columns. */
function lmhg_site_core_filter_page_columns( array $columns ): array {
	foreach ( array_merge( array_keys( lmhg_site_core_taxonomy_definitions() ), array( LMHG_SITE_CORE_SPECIALTY_TAXONOMY, LMHG_SITE_CORE_FAQ_SET_TAXONOMY ) ) as $taxonomy ) {
		unset( $columns[ 'taxonomy-' . $taxonomy ] );
	}
	$columns['lmhg_cta_variant']     = 'CTA Variant';
	$columns['lmhg_primary_keyword'] = 'Primary Keyword';
	return $columns;
}

/** Renders compact LMHG columns on the ordinary Pages list. */
function lmhg_site_core_render_page_column( string $column, int $post_id ): void {
	if ( 'lmhg_cta_variant' === $column ) {
		$term = lmhg_site_core_page_cta_term( $post_id );
		echo $term instanceof WP_Term ? esc_html( $term->name ) : '<span aria-label="Missing CTA assignment">—</span>';
	}
	if ( 'lmhg_primary_keyword' === $column ) {
		$data = lmhg_site_core_admin_keyword_data( $post_id );
		echo esc_html( $data['primary'] ?: '—' );
	}
}

/** Adds structured CTA metadata columns to the native term table. */
function lmhg_site_core_filter_cta_term_columns( array $columns ): array {
	return array( 'cb' => $columns['cb'] ?? '<input type="checkbox" />', 'name' => 'Variant', 'cta_status' => 'Status', 'cta_title' => 'CTA Title', 'cta_description' => 'Description', 'cta_experiment' => 'Experiment', 'posts' => 'Pages' );
}

/** Renders one CTA term metadata column. */
function lmhg_site_core_render_cta_term_column( string $content, string $column, int $term_id ): string {
	return match ( $column ) {
		'cta_status'      => esc_html( ucfirst( (string) get_term_meta( $term_id, '_lmhg_cta_lifecycle', true ) ) ),
		'cta_title'       => esc_html( (string) get_term_meta( $term_id, '_lmhg_cta_title', true ) ),
		'cta_description' => esc_html( wp_trim_words( (string) get_term_meta( $term_id, '_lmhg_cta_description', true ), 14 ) ),
		'cta_experiment'  => esc_html( (string) get_term_meta( $term_id, '_lmhg_cta_experiment_label', true ) ?: '—' ),
		default           => $content,
	};
}

/** Renders standard pagination links for plugin-owned tables. */
function lmhg_site_core_render_pagination( int $current, int $total, string $page, array $args = array() ): void {
	if ( $total <= 1 ) {
		return;
	}
	$base = add_query_arg( array_merge( array( 'page' => $page, 'paged' => '%#%' ), array_filter( $args ) ), admin_url( 'admin.php' ) );
	$links = paginate_links( array( 'base' => $base, 'format' => '', 'current' => $current, 'total' => $total, 'type' => 'list' ) );
	if ( is_string( $links ) ) {
		echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post( $links ) . '</div></div>';
	}
}
