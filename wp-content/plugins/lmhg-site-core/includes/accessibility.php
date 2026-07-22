<?php
/**
 * Front-end accessibility normalization for the WordPress 2026 proof runtime.
 *
 * @package LMHGSiteCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'template_redirect', 'lmhg_site_core_start_accessibility_buffer', 20 );
add_action( 'after_setup_theme', 'lmhg_site_core_disable_core_block_template_skip_link', 20 );

/**
 * Keeps the LMHG link as the sole skip link on block templates.
 *
 * WordPress 7.0 inserts its block-template skip link only while both of these
 * core callbacks remain registered. LMHG already provides an equivalent link
 * with a stable target, so remove the core pair before template rendering.
 *
 * @see https://developer.wordpress.org/reference/functions/wp_enqueue_block_template_skip_link/
 */
function lmhg_site_core_disable_core_block_template_skip_link(): void {
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_block_template_skip_link' );
	remove_action( 'wp_footer', 'the_block_template_skip_link' );
}

/**
 * Starts a small HTML buffer so the live rendered page has a skip link and main landmark.
 */
function lmhg_site_core_start_accessibility_buffer(): void {
	if ( ! lmhg_site_core_should_filter_frontend_html() ) {
		return;
	}

	ob_start( 'lmhg_site_core_filter_frontend_html' );
}

/**
 * Determines whether this request should be treated as front-end HTML.
 *
 * @return bool
 */
function lmhg_site_core_should_filter_frontend_html(): bool {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_feed() || is_robots() ) {
		return false;
	}

	$method = strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) );
	if ( ! in_array( $method, array( 'GET', 'HEAD' ), true ) ) {
		return false;
	}

	$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ) );
	$path = (string) wp_parse_url( $request_uri, PHP_URL_PATH );
	if ( preg_match( '/\.(?:css|js|json|xml|txt|png|jpe?g|gif|webp|svg|ico|woff2?)$/i', $path ) ) {
		return false;
	}

	return true;
}

/**
 * Adds a skip link and wraps the primary content in a main landmark when the block renderer omits it.
 *
 * @param string $html Rendered HTML.
 * @return string
 */
function lmhg_site_core_filter_frontend_html( string $html ): string {
	if ( false === stripos( $html, '<body' ) ) {
		return $html;
	}

	$html = lmhg_site_core_ensure_skip_link( $html );
	$html = lmhg_site_core_ensure_main_landmark( $html );
	$html = lmhg_site_core_expand_generic_link_names( $html );
	$html = lmhg_site_core_add_intrinsic_upload_dimensions( $html );
	$html = lmhg_site_core_normalize_frontend_core30_copy( $html );

	return $html;
}

/**
 * Adds intrinsic dimensions to raw uploads images without rewriting page copy.
 *
 * Gutenberg normally emits these values for Image blocks. A small set of
 * decorative legacy HTML images bypass that renderer, so resolve their local
 * upload files and add dimensions to prevent layout shift.
 */
function lmhg_site_core_add_intrinsic_upload_dimensions( string $html ): string {
	$uploads = wp_upload_dir();
	if ( ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) ) {
		return $html;
	}

	$base_dir = trailingslashit( wp_normalize_path( (string) $uploads['basedir'] ) );
	return preg_replace_callback(
		'/<img\b[^>]*>/i',
		static function ( array $matches ) use ( $base_dir ): string {
			$tag = (string) $matches[0];
			if ( preg_match( '/\bwidth\s*=/i', $tag ) || preg_match( '/\bheight\s*=/i', $tag ) ) {
				return $tag;
			}
			if ( ! preg_match( '/\bsrc\s*=\s*(["\'])(.*?)\1/i', $tag, $source_match ) ) {
				return $tag;
			}

			$path = (string) wp_parse_url( html_entity_decode( (string) $source_match[2] ), PHP_URL_PATH );
			$upload_marker = '/wp-content/uploads/';
			$plugin_marker = '/wp-content/plugins/';
			$upload_offset = strpos( $path, $upload_marker );
			$plugin_offset = strpos( $path, $plugin_marker );
			if ( false !== $upload_offset ) {
				$allowed_base = $base_dir;
				$relative = rawurldecode( substr( $path, $upload_offset + strlen( $upload_marker ) ) );
			} elseif ( false !== $plugin_offset ) {
				$allowed_base = trailingslashit( wp_normalize_path( WP_PLUGIN_DIR ) );
				$relative = rawurldecode( substr( $path, $plugin_offset + strlen( $plugin_marker ) ) );
			} else {
				return $tag;
			}

			$file = wp_normalize_path( $allowed_base . ltrim( $relative, '/' ) );
			if ( ! str_starts_with( $file, $allowed_base ) || ! is_file( $file ) ) {
				return $tag;
			}

			$dimensions = lmhg_site_core_local_image_dimensions( $file );
			if ( empty( $dimensions ) ) {
				return $tag;
			}

			return preg_replace(
				'/\s*\/?>$/',
				' width="' . absint( $dimensions[0] ) . '" height="' . absint( $dimensions[1] ) . '" />',
				$tag,
				1
			) ?? $tag;
		},
		$html
	) ?? $html;
}

/** Returns raster dimensions or an SVG viewBox size for a trusted local file. */
function lmhg_site_core_local_image_dimensions( string $file ): array {
	if ( 'svg' !== strtolower( (string) pathinfo( $file, PATHINFO_EXTENSION ) ) ) {
		$size = getimagesize( $file );
		return is_array( $size ) && ! empty( $size[0] ) && ! empty( $size[1] )
			? array( absint( $size[0] ), absint( $size[1] ) )
			: array();
	}

	$source = file_get_contents( $file, false, null, 0, 1024 );
	if ( ! is_string( $source ) || ! preg_match( '/\bviewBox=["\']\s*[-\d.]+\s+[-\d.]+\s+([\d.]+)\s+([\d.]+)\s*["\']/i', $source, $matches ) ) {
		return array();
	}

	return array( max( 1, (int) round( (float) $matches[1] ) ), max( 1, (int) round( (float) $matches[2] ) ) );
}

/**
 * Gives generic internal "Learn More" links a destination-specific accessible name.
 *
 * @param string $html Rendered HTML.
 * @return string
 */
function lmhg_site_core_expand_generic_link_names( string $html ): string {
	return preg_replace_callback(
		'/<a\b([^>]*)>\s*Learn More\s*<\/a>/i',
		static function ( array $matches ): string {
			$attributes = (string) $matches[1];
			if ( preg_match( '/\baria-label\s*=/i', $attributes ) ) {
				return $matches[0];
			}

			if ( ! preg_match( '/\bhref\s*=\s*(["\'])(.*?)\1/i', $attributes, $href_match ) ) {
				return $matches[0];
			}

			$path = (string) wp_parse_url( (string) $href_match[2], PHP_URL_PATH );
			if ( '' === $path || ! str_starts_with( $path, '/' ) ) {
				return $matches[0];
			}

			$page  = get_page_by_path( trim( $path, '/' ), OBJECT, 'page' );
			$label = $page instanceof WP_Post ? wp_strip_all_tags( get_the_title( $page ) ) : '';
			if ( '' === trim( $label ) ) {
				$label = ucwords( str_replace( '-', ' ', basename( trim( $path, '/' ) ) ) );
			}

			$link_text = 'Learn more about ' . $label;
			return '<a' . $attributes . ' aria-label="' . esc_attr( $link_text ) . '">' . esc_html( $link_text ) . '</a>';
		},
		$html
	) ?? $html;
}

/**
 * Inserts a skip link immediately after the body tag.
 *
 * @param string $html Rendered HTML.
 * @return string
 */
function lmhg_site_core_ensure_skip_link( string $html ): string {
	if ( str_contains( $html, 'class="lmhg-skip-link"' ) || str_contains( $html, "class='lmhg-skip-link'" ) ) {
		return $html;
	}

	return preg_replace(
		'/(<body\b[^>]*>)/i',
		'$1' . "\n" . '<a class="lmhg-skip-link" href="#main-content">Skip to main content</a>',
		$html,
		1
	) ?? $html;
}

/**
 * Ensures there is exactly one primary main-content target in the rendered page chrome.
 *
 * @param string $html Rendered HTML.
 * @return string
 */
function lmhg_site_core_ensure_main_landmark( string $html ): string {
	if ( preg_match( '/<main\b/i', $html ) ) {
		if ( preg_match( '/<main\b[^>]*\bid=["\']main-content["\']/i', $html ) ) {
			return $html;
		}

		return preg_replace( '/<main\b/i', '<main id="main-content" tabindex="-1"', $html, 1 ) ?? $html;
	}

	$header_close = stripos( $html, '</header>' );
	if ( false === $header_close ) {
		return $html;
	}

	$content_start = $header_close + strlen( '</header>' );
	$footer_start = stripos( $html, '<footer', $content_start );
	if ( false === $footer_start || $footer_start <= $content_start ) {
		return $html;
	}

	$before = substr( $html, 0, $content_start );
	$content = substr( $html, $content_start, $footer_start - $content_start );
	$after = substr( $html, $footer_start );

	return $before
		. "\n" . '<main id="main-content" class="lmhg-main-content" tabindex="-1">'
		. $content
		. '</main>' . "\n"
		. $after;
}

/**
 * Applies final Core30 copy normalization to generated frontend HTML.
 *
 * @param string $html Rendered HTML.
 * @return string
 */
function lmhg_site_core_normalize_frontend_core30_copy( string $html ): string {
	return str_replace(
		array(
			'Community Based Services',
		),
		array(
			'Community-Based Services',
		),
		$html
	);
}
