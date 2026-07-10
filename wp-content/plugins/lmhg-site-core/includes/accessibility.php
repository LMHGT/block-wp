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
	$html = lmhg_site_core_normalize_frontend_core30_copy( $html );

	return $html;
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
