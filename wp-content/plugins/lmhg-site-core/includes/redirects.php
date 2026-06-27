<?php
/**
 * Redirect handling for LMHG route parity.
 *
 * @package LMHGSiteCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'template_redirect', 'lmhg_site_core_handle_manifest_redirects', 0 );

/**
 * Handles repo-owned redirect rules before WordPress canonical redirects.
 */
function lmhg_site_core_handle_manifest_redirects(): void {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	$method = strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) );
	if ( ! in_array( $method, array( 'GET', 'HEAD' ), true ) ) {
		return;
	}

	$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ) );
	$request_path = lmhg_site_core_normalize_redirect_path( (string) wp_parse_url( $request_uri, PHP_URL_PATH ) );

	if ( '' === $request_path || str_starts_with( $request_path, '/wp-' ) ) {
		return;
	}

	$redirects = lmhg_site_core_redirect_map();
	$rule = $redirects[ $request_path ] ?? null;

	if ( null === $rule ) {
		return;
	}

	$target_path = lmhg_site_core_normalize_redirect_path( $rule['target'] );
	if ( '' === $target_path || $target_path === $request_path ) {
		return;
	}

	$query = (string) wp_parse_url( $request_uri, PHP_URL_QUERY );
	$target = home_url( $target_path );
	if ( '' !== $query ) {
		$target .= '?' . $query;
	}

	wp_safe_redirect( $target, $rule['statusCode'] );
	exit;
}

/**
 * Builds a map of normalized source paths to redirect targets.
 *
 * @return array<string,array{target:string,statusCode:int}>
 */
function lmhg_site_core_redirect_map(): array {
	static $map = null;

	if ( is_array( $map ) ) {
		return $map;
	}

	$map = array();
	$redirects = get_option( 'lmhg_route_redirects', array() );
	if ( ! is_array( $redirects ) || empty( $redirects ) ) {
		$manifest = lmhg_site_core_read_route_manifest();
		$redirects = isset( $manifest['redirects'] ) && is_array( $manifest['redirects'] )
			? $manifest['redirects']
			: array();
	}

	foreach ( $redirects as $redirect ) {
		if ( ! is_array( $redirect ) ) {
			continue;
		}

		$source = lmhg_site_core_normalize_redirect_path( (string) ( $redirect['source'] ?? '' ) );
		$target = lmhg_site_core_normalize_redirect_path( (string) ( $redirect['target'] ?? '' ) );
		$status_code = absint( $redirect['statusCode'] ?? 301 );

		if ( '' === $source || '' === $target || $source === $target || $status_code < 300 || $status_code > 399 ) {
			continue;
		}

		if ( ! isset( $map[ $source ] ) ) {
			$map[ $source ] = array(
				'target'     => $target,
				'statusCode' => $status_code,
			);
		}
	}

	return $map;
}

/**
 * Reads the route manifest bundled with this proof repo.
 *
 * @return array<string,mixed>
 */
function lmhg_site_core_read_route_manifest(): array {
	static $manifest = null;

	if ( is_array( $manifest ) ) {
		return $manifest;
	}

	$path = dirname( __DIR__, 4 ) . '/data/lmhg/source-route-manifest.json';
	if ( ! file_exists( $path ) ) {
		$manifest = array();
		return $manifest;
	}

	$decoded = json_decode( (string) file_get_contents( $path ), true );
	$manifest = is_array( $decoded ) ? $decoded : array();
	return $manifest;
}

/**
 * Normalizes manifest and request paths for redirect matching.
 *
 * @param string $path Path or URL.
 * @return string
 */
function lmhg_site_core_normalize_redirect_path( string $path ): string {
	if ( '' === $path ) {
		return '';
	}

	$parsed = (string) wp_parse_url( $path, PHP_URL_PATH );
	$path = '/' . trim( rawurldecode( $parsed ), '/' );

	if ( '/' === $path ) {
		return '/';
	}

	if ( str_ends_with( $path, '.html' ) ) {
		return $path;
	}

	return $path . '/';
}
