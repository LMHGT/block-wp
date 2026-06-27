<?php
/**
 * LMHG Block Theme setup.
 *
 * @package LMHGBlockTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'after_setup_theme',
	function (): void {
		add_theme_support( 'wp-block-styles' );
		add_editor_style( 'style.css' );
	}
);

add_action(
	'init',
	function (): void {
		register_block_pattern_category(
			'lmhg',
			array( 'label' => __( 'LMHG', 'lmhg-block-theme' ) )
		);

		wp_enqueue_block_style(
			'core/navigation',
			array(
				'handle' => 'lmhg-block-theme-navigation',
				'src'    => get_theme_file_uri( 'assets/css/blocks/navigation.css' ),
				'path'   => get_theme_file_path( 'assets/css/blocks/navigation.css' ),
			)
		);
	}
);
