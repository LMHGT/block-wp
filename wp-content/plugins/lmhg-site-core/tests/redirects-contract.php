<?php
/**
 * Static redirect authority contract.
 *
 * Run with: php wp-content/plugins/lmhg-site-core/tests/redirects-contract.php
 */

define( 'ABSPATH', __DIR__ . '/' );

/** No-op WordPress hook stub for loading the module. */
function add_action(): void {}

/** No-op WordPress hook stub for loading the module. */
function add_filter(): void {}

require dirname( __DIR__ ) . '/includes/redirects.php';

$map = lmhg_site_core_static_redirect_map();
$expected = array(
	'/articles/'                    => '/blogs/',
	'/services/'                    => '/our-services/',
	'/child-counseling/'            => '/child-therapy/',
	'/court-ordered/'               => '/family-court/',
	'/careers/'                     => '/we-are-hiring/',
	'/couples-conflict-resolution/' => '/conflict-resolution-counseling/',
	'/relationship-counseling/'     => '/couples-counseling/',
	'/faq/about-lmhg/'              => '/what-we-do/',
	'/therapy-in-your-home/'        => '/locations/in-home/',
);

foreach ( $expected as $source => $target ) {
	$rule = $map[ $source ] ?? null;
	if ( ! is_array( $rule ) || $target !== ( $rule['target'] ?? null ) || 301 !== ( $rule['statusCode'] ?? null ) ) {
		fwrite( STDERR, "FAIL: {$source} must redirect to {$target} with status 301.\n" );
		exit( 1 );
	}
}

$redirect_source = file_get_contents( dirname( __DIR__ ) . '/includes/redirects.php' );
if ( false === $redirect_source || str_contains( $redirect_source, 'source-route-manifest.json' ) ) {
	fwrite( STDERR, "FAIL: redirects must not depend on an absent source-route manifest.\n" );
	exit( 1 );
}

echo "PASS: tracked static and option-backed redirects are the only redirect authorities.\n";
