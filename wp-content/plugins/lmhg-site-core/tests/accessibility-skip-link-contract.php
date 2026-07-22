<?php
/**
 * Focused contract checks for the LMHG skip-link owner.
 *
 * Run with:
 * php wp-content/plugins/lmhg-site-core/tests/accessibility-skip-link-contract.php
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

$lmhg_test_actions = array();

/** Minimal in-memory WordPress action registry for this isolated contract test. */
function add_action( string $hook, string $callback, int $priority = 10 ): bool {
	global $lmhg_test_actions;
	$lmhg_test_actions[ $hook ][ $priority ][] = $callback;
	return true;
}

/** Removes a callback from the isolated action registry. */
function remove_action( string $hook, string $callback, int $priority = 10 ): bool {
	global $lmhg_test_actions;
	$callbacks = $lmhg_test_actions[ $hook ][ $priority ] ?? array();
	$index     = array_search( $callback, $callbacks, true );
	if ( false === $index ) {
		return false;
	}

	unset( $lmhg_test_actions[ $hook ][ $priority ][ $index ] );
	return true;
}

/** Reports whether the isolated registry contains a callback on a hook. */
function has_action( string $hook, string $callback ): bool {
	global $lmhg_test_actions;
	foreach ( $lmhg_test_actions[ $hook ] ?? array() as $callbacks ) {
		if ( in_array( $callback, $callbacks, true ) ) {
			return true;
		}
	}

	return false;
}

/** Fails this standalone contract with a useful message. */
function lmhg_test_expect( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

// WordPress 7.0 registers both callbacks before loading plugins.
add_action( 'wp_enqueue_scripts', 'wp_enqueue_block_template_skip_link' );
add_action( 'wp_footer', 'the_block_template_skip_link' );

require dirname( __DIR__ ) . '/includes/accessibility.php';

lmhg_test_expect(
	has_action( 'after_setup_theme', 'lmhg_site_core_disable_core_block_template_skip_link' ),
	'LMHG schedules removal of the WordPress block-template skip link before template rendering.'
);
lmhg_test_expect(
	function_exists( 'lmhg_site_core_disable_core_block_template_skip_link' ),
	'The WordPress block-template skip-link removal callback exists.'
);

lmhg_site_core_disable_core_block_template_skip_link();

lmhg_test_expect(
	! has_action( 'wp_enqueue_scripts', 'wp_enqueue_block_template_skip_link' ),
	'The WordPress 7.0 block-template skip-link callback is removed.'
);
lmhg_test_expect(
	! has_action( 'wp_footer', 'the_block_template_skip_link' ),
	'The deprecated WordPress skip-link fallback is removed.'
);

$html     = '<!doctype html><html><body><div class="wp-site-blocks"><main id="main-content"></main></div></body></html>';
$filtered = lmhg_site_core_ensure_skip_link( $html );
$filtered = lmhg_site_core_ensure_skip_link( $filtered );

lmhg_test_expect(
	1 === substr_count( $filtered, 'class="lmhg-skip-link"' ),
	'The LMHG skip link remains the single site-owned skip link after repeated filtering.'
);
lmhg_test_expect(
	str_contains( $filtered, 'href="#main-content"' ),
	'The LMHG skip link targets the primary main landmark.'
);

echo "PASS: accessibility skip-link contract\n";
