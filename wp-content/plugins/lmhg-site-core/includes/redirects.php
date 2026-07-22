<?php
/**
 * Redirect handling for LMHG route parity.
 *
 * @package LMHGSiteCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'lmhg_site_core_register_static_404_route', 1 );
add_filter( 'query_vars', 'lmhg_site_core_register_static_route_query_vars' );
add_filter( 'redirect_canonical', 'lmhg_site_core_preserve_static_404_url', 10, 2 );
add_action( 'template_redirect', 'lmhg_site_core_handle_manifest_redirects', 0 );

/**
 * Registers the static Astro not-found page URL as an imported page route.
 */
function lmhg_site_core_register_static_404_route(): void {
	add_rewrite_rule( '^404\.html$', 'index.php?pagename=not-found&lmhg_static_404=1', 'top' );
}

/**
 * Allows the static route marker query var through WordPress routing.
 *
 * @param array<int,string> $vars Query vars.
 * @return array<int,string>
 */
function lmhg_site_core_register_static_route_query_vars( array $vars ): array {
	$vars[] = 'lmhg_static_404';
	return $vars;
}

/**
 * Keeps `/404.html` addressable instead of canonicalizing to `/not-found/`.
 *
 * @param string|false $redirect_url Proposed canonical URL.
 * @param string       $requested_url Requested URL.
 * @return string|false
 */
function lmhg_site_core_preserve_static_404_url( string|false $redirect_url, string $requested_url ): string|false {
	$path = lmhg_site_core_normalize_redirect_path( (string) wp_parse_url( $requested_url, PHP_URL_PATH ) );
	if ( '/404.html' === $path ) {
		return false;
	}

	return $redirect_url;
}

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

	$map = lmhg_site_core_static_redirect_map();
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
 * Returns redirects owned by the deployed site topology.
 *
 * These rules remain active even when no imported Astro redirect manifest is present.
 *
 * @return array<string,array{target:string,statusCode:int}>
 */
function lmhg_site_core_static_redirect_map(): array {
	return array(
		'/articles/family-therapy-vs-individual-therapy/' => array(
			'target'     => '/family-therapy-vs-individual-therapy/',
			'statusCode' => 301,
		),
		'/articles/guide-to-individual-therapy/' => array(
			'target'     => '/guide-to-individual-therapy/',
			'statusCode' => 301,
		),
		'/articles/how-to-talk-to-your-loved-ones-about-going-to-therapy/' => array(
			'target'     => '/how-to-talk-to-your-loved-ones-about-going-to-therapy/',
			'statusCode' => 301,
		),
		'/articles/top-5-signs-its-time-to-seek-therapy/' => array(
			'target'     => '/top-5-signs-its-time-to-seek-therapy/',
			'statusCode' => 301,
		),
		'/articles/what-to-expect-when-starting-therapy/' => array(
			'target'     => '/what-to-expect-when-starting-therapy/',
			'statusCode' => 301,
		),
		'/articles/' => array(
			'target'     => '/blogs/',
			'statusCode' => 301,
		),
		'/court-ordered/' => array(
			'target'     => '/family-court/',
			'statusCode' => 301,
		),
		'/child-counseling/' => array(
			'target'     => '/child-therapy/',
			'statusCode' => 301,
		),
		'/individual-counseling/' => array(
			'target'     => '/individual-therapy/',
			'statusCode' => 301,
		),
		'/faq/about-lmhg/' => array(
			'target'     => '/what-we-do/',
			'statusCode' => 301,
		),
		'/careers/' => array(
			'target'     => '/we-are-hiring/',
			'statusCode' => 301,
		),
		'/services/' => array(
			'target'     => '/our-services/',
			'statusCode' => 301,
		),
		'/faqs/questions-about-individual-therapy/' => array(
			'target'     => '/faq/',
			'statusCode' => 301,
		),
		'/sitemap/'                      => array(
			'target'     => '/sitemap_index.xml',
			'statusCode' => 301,
		),
		'/jeffersontown-ky/'             => array(
			'target'     => '/jefferson-county-ky/',
			'statusCode' => 301,
		),
		'/shively-ky/'                   => array(
			'target'     => '/jefferson-county-ky/',
			'statusCode' => 301,
		),
		'/springhurst-ky/'               => array(
			'target'     => '/jefferson-county-ky/',
			'statusCode' => 301,
		),
		'/crestwood-ky/'                 => array(
			'target'     => '/oldham-county-ky/',
			'statusCode' => 301,
		),
		'/la-grange-ky/'                 => array(
			'target'     => '/oldham-county-ky/',
			'statusCode' => 301,
		),
		'/prospect-ky/'                  => array(
			'target'     => '/locations/',
			'statusCode' => 301,
		),
		'/bardstown-ky/'                 => array(
			'target'     => '/locations/',
			'statusCode' => 301,
		),
		'/taylorsville-ky/'              => array(
			'target'     => '/locations/',
			'statusCode' => 301,
		),
		'/clarksville-ky/'               => array(
			'target'     => '/locations/',
			'statusCode' => 301,
		),
		'/jeffersonville-ky/'            => array(
			'target'     => '/locations/',
			'statusCode' => 301,
		),
		'/couples-conflict-resolution/' => array(
			'target'     => '/conflict-resolution-counseling/',
			'statusCode' => 301,
		),
		'/relationship-counseling/'      => array(
			'target'     => '/couples-counseling/',
			'statusCode' => 301,
		),
		'/therapy-in-your-home/'        => array(
			'target'     => '/locations/in-home/',
			'statusCode' => 301,
		),
	);
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

	if ( preg_match( '/\.(?:html|xml)$/', $path ) ) {
		return $path;
	}

	return $path . '/';
}
