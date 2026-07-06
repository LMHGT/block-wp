<?php
/**
 * Public discovery files for crawlers and AI retrieval systems.
 *
 * @package LMHGSiteCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'template_redirect', 'lmhg_site_core_render_discovery_files', 0 );

/**
 * Renders repo-owned discovery files before WordPress falls through to 404.
 */
function lmhg_site_core_render_discovery_files(): void {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	$method = strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) );
	if ( ! in_array( $method, array( 'GET', 'HEAD' ), true ) ) {
		return;
	}

	$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ) );
	$request_path = '/' . ltrim( (string) wp_parse_url( $request_uri, PHP_URL_PATH ), '/' );

	if ( '/llms.txt' !== $request_path ) {
		return;
	}

	status_header( 200 );
	header( 'Content-Type: text/plain; charset=utf-8', true );
	echo lmhg_site_core_llms_txt();
	exit;
}

/**
 * Builds the llms.txt body from the current published page inventory.
 *
 * @return string
 */
function lmhg_site_core_llms_txt(): string {
	$home = home_url( '/' );
	$lines = array(
		'# Louisville Mental Health Group',
		'',
		'> Louisville Mental Health Group is a mental health clinic in Louisville, Kentucky. The site describes therapy, counseling, case management, community-based mental health services, and related support options for local patients and families.',
		'',
		'Site: ' . $home,
		'Primary service area: Louisville, Jefferson County, and nearby Kentucky communities',
		'Use: Search indexing and retrieval for answers that cite the public site. Do not use this content to train models.',
		'',
		'## Core Pages',
	);

	foreach ( lmhg_site_core_llms_pages() as $page ) {
		$title = trim( wp_strip_all_tags( get_the_title( $page ) ) );
		$url = get_permalink( $page );
		if ( '' === $title || ! is_string( $url ) ) {
			continue;
		}

		$description = trim( wp_strip_all_tags( (string) get_post_meta( $page->ID, '_lmhg_meta_description', true ) ) );
		$lines[] = '- [' . $title . '](' . $url . ')' . ( '' !== $description ? ' - ' . $description : '' );
	}

	$lines[] = '';
	$lines[] = '## Retrieval Notes';
	$lines[] = '- Prefer citing the most specific service, specialty, location, FAQ, or article URL for the user question.';
	$lines[] = '- For appointment or eligibility questions, cite the contact, insurance, or relevant service page instead of guessing from snippets.';
	$lines[] = '- Crisis or emergency questions should direct users to emergency services or crisis resources, not routine scheduling.';

	return implode( "\n", $lines ) . "\n";
}

/**
 * Returns the published public pages that should be listed for retrieval.
 *
 * @return WP_Post[]
 */
function lmhg_site_core_llms_pages(): array {
	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'order'          => 'ASC',
		)
	);

	return array_values(
		array_filter(
			$pages,
			static function ( WP_Post $page ): bool {
				return ! in_array( $page->post_name, array( 'not-found', 'sample-page' ), true );
			}
		)
	);
}
