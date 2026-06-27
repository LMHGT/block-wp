<?php
/**
 * Front-end SEO output for imported LMHG pages.
 *
 * @package LMHGSiteCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

remove_action( 'wp_head', 'rel_canonical' );
add_filter( 'pre_get_document_title', 'lmhg_site_core_document_title' );
add_filter( 'wp_robots', 'lmhg_site_core_filter_robots' );
add_action( 'send_headers', 'lmhg_site_core_send_development_robots_header' );
add_action( 'wp_head', 'lmhg_site_core_output_development_robots_meta', 1 );
add_action( 'wp_head', 'lmhg_site_core_output_canonical', 4 );
add_action( 'wp_head', 'lmhg_site_core_output_meta_description', 5 );
add_action( 'wp_head', 'lmhg_site_core_output_json_ld', 20 );

const LMHG_SITE_CORE_DEVELOPMENT_ROBOTS = 'noindex, nofollow, noarchive, nosnippet, noimageindex';

/**
 * Uses source SEO title when an imported page has one.
 *
 * @param string $title Existing title.
 * @return string
 */
function lmhg_site_core_document_title( string $title ): string {
	$post_id = lmhg_site_core_imported_post_id();
	if ( 0 === $post_id ) {
		return $title;
	}

	$seo_title = trim( (string) get_post_meta( $post_id, '_lmhg_seo_title', true ) );
	return '' !== $seo_title ? $seo_title : $title;
}

/**
 * Applies noindex when the imported source record requires it.
 *
 * @param array<string,bool|string> $robots Robots directives.
 * @return array<string,bool|string>
 */
function lmhg_site_core_filter_robots( array $robots ): array {
	if ( lmhg_site_core_should_suppress_indexing() ) {
		$robots['noindex']     = true;
		$robots['nofollow']    = true;
		$robots['noarchive']   = true;
		$robots['nosnippet']   = true;
		$robots['noimageindex'] = true;
		unset( $robots['index'] );
	}

	$post_id = lmhg_site_core_imported_post_id();
	if ( 0 === $post_id ) {
		return $robots;
	}

	if ( '1' === (string) get_post_meta( $post_id, '_lmhg_noindex', true ) ) {
		$robots['noindex'] = true;
		$robots['nofollow'] = true;
		unset( $robots['index'] );
	}

	return $robots;
}

/**
 * Sends staging/development discovery suppression headers.
 */
function lmhg_site_core_send_development_robots_header(): void {
	if ( is_admin() || ! lmhg_site_core_should_suppress_indexing() ) {
		return;
	}

	header( 'X-Robots-Tag: ' . LMHG_SITE_CORE_DEVELOPMENT_ROBOTS, true );
}

/**
 * Outputs explicit staging/development robots meta for parity checks.
 */
function lmhg_site_core_output_development_robots_meta(): void {
	if ( is_admin() || is_feed() || is_robots() || ! lmhg_site_core_should_suppress_indexing() ) {
		return;
	}

	printf( '<meta name="robots" content="%s" />' . "\n", esc_attr( LMHG_SITE_CORE_DEVELOPMENT_ROBOTS ) );
}

/**
 * Determines whether this WordPress surface must stay hidden from indexing.
 *
 * @return bool
 */
function lmhg_site_core_should_suppress_indexing(): bool {
	if ( defined( 'LMHG_ALLOW_INDEXING' ) && LMHG_ALLOW_INDEXING ) {
		return false;
	}

	$allow_indexing = getenv( 'LMHG_ALLOW_INDEXING' );
	if ( '1' === $allow_indexing || 'true' === strtolower( (string) $allow_indexing ) ) {
		return false;
	}

	return true;
}

/**
 * Outputs the canonical URL from imported source metadata.
 */
function lmhg_site_core_output_canonical(): void {
	$post_id = lmhg_site_core_imported_post_id();
	if ( 0 === $post_id ) {
		return;
	}

	$canonical = lmhg_site_core_imported_canonical_url( $post_id );
	if ( '' === $canonical ) {
		return;
	}

	printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( $canonical ) );
}

/**
 * Outputs a source meta description and avoids migration-stub excerpts.
 */
function lmhg_site_core_output_meta_description(): void {
	if ( is_admin() || is_feed() || is_robots() ) {
		return;
	}

	$post_id = lmhg_site_core_imported_post_id();
	if ( 0 !== $post_id ) {
		$description = trim( (string) get_post_meta( $post_id, '_lmhg_meta_description', true ) );
		if ( '' === $description ) {
			$description = lmhg_site_core_fallback_meta_description( $post_id );
		}
		if ( '' === $description ) {
			return;
		}

		printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $description ) );
		return;
	}

	$description = get_bloginfo( 'description' );
	if ( is_singular() ) {
		$excerpt = get_the_excerpt();
		if ( '' !== trim( $excerpt ) ) {
			$description = $excerpt;
		}
	}

	$description = wp_html_excerpt( wp_strip_all_tags( $description ), 155, '...' );
	if ( '' === trim( $description ) ) {
		return;
	}

	printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $description ) );
}

/**
 * Outputs JSON-LD from imported schema metadata.
 */
function lmhg_site_core_output_json_ld(): void {
	if ( is_admin() || is_feed() || is_robots() ) {
		return;
	}

	$site_url = home_url( '/' );
	$name     = get_bloginfo( 'name' );
	$post_id  = lmhg_site_core_imported_post_id();

	if ( 0 === $post_id ) {
		$graph = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'WebSite',
			'name'            => $name,
			'url'             => $site_url,
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => add_query_arg( 's', '{search_term_string}', $site_url ),
				'query-input' => 'required name=search_term_string',
			),
		);
	} else {
		$schema_type = trim( (string) get_post_meta( $post_id, '_lmhg_schema_type', true ) );
		$schema_type = '' !== $schema_type ? $schema_type : ( is_front_page() ? 'WebPage' : 'Article' );
		$headline    = wp_strip_all_tags( get_the_title( $post_id ) );
		$canonical   = lmhg_site_core_imported_canonical_url( $post_id );
		$route       = lmhg_site_core_route_manifest_entry( $post_id );

		$page_graph = array(
			'@context'     => 'https://schema.org',
			'@type'        => $schema_type,
			'name'         => $headline,
			'headline'     => $headline,
			'url'          => '' !== $canonical ? $canonical : get_permalink( $post_id ),
			'isPartOf'     => array(
				'@type' => 'WebSite',
				'name'  => $name,
				'url'   => $site_url,
			),
			'dateModified' => get_the_modified_date( DATE_W3C, $post_id ),
		);

		$graph_nodes = array( $page_graph );
		$breadcrumb = lmhg_site_core_breadcrumb_json_ld( $post_id, $route, $canonical );
		if ( ! empty( $breadcrumb ) ) {
			$graph_nodes[] = $breadcrumb;
		}

		$faq_items = lmhg_site_core_publishable_faq_items( $route );
		if ( empty( $faq_items ) && 1 === count( $graph_nodes ) ) {
			$graph = $page_graph;
		} else {
			if ( ! empty( $faq_items ) ) {
				$graph_nodes[] = array(
					'@type'      => 'FAQPage',
					'mainEntity' => array_map(
						static fn( array $item ): array => array(
							'@type'          => 'Question',
							'name'           => $item['question'],
							'acceptedAnswer' => array(
								'@type' => 'Answer',
								'text'  => $item['answer'],
							),
						),
						$faq_items
					),
				);
			}

			$graph = array(
				'@context' => 'https://schema.org',
				'@graph'   => $graph_nodes,
			);
		}
	}

	printf(
		'<script type="application/ld+json">%s</script>' . "\n",
		wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
	);
}

/**
 * Builds graph-derived BreadcrumbList JSON-LD for imported pages.
 *
 * @param int                 $post_id Post ID.
 * @param array<string,mixed> $route Route manifest entry.
 * @param string              $canonical Canonical URL.
 * @return array<string,mixed>
 */
function lmhg_site_core_breadcrumb_json_ld( int $post_id, array $route, string $canonical ): array {
	$source_url = trim( (string) get_post_meta( $post_id, '_lmhg_source_url', true ) );
	if ( '' === $source_url || '/' === $source_url ) {
		return array();
	}

	$crumbs = array(
		array(
			'name' => 'Home',
			'item' => home_url( '/' ),
		),
	);

	$relationship = isset( $route['relationship'] ) && is_array( $route['relationship'] ) ? $route['relationship'] : array();
	$parent_url = trim( (string) ( $relationship['primaryParentPageUrl'] ?? '' ) );
	if ( '' !== $parent_url && '/' !== $parent_url && $parent_url !== $source_url ) {
		$crumbs[] = array(
			'name' => lmhg_site_core_title_for_source_url( $parent_url ),
			'item' => home_url( '/' . ltrim( $parent_url, '/' ) ),
		);
	}

	$crumbs[] = array(
		'name' => wp_strip_all_tags( get_the_title( $post_id ) ),
		'item' => '' !== $canonical ? $canonical : get_permalink( $post_id ),
	);

	return array(
		'@type'           => 'BreadcrumbList',
		'itemListElement' => array_map(
			static fn( array $crumb, int $index ): array => array(
				'@type'    => 'ListItem',
				'position' => $index + 1,
				'name'     => $crumb['name'],
				'item'     => $crumb['item'],
			),
			$crumbs,
			array_keys( $crumbs )
		),
	);
}

/**
 * Gets the current imported singular post ID.
 *
 * @return int
 */
function lmhg_site_core_imported_post_id(): int {
	if ( ! is_singular() ) {
		return 0;
	}

	$post_id = (int) get_queried_object_id();
	if ( $post_id <= 0 ) {
		return 0;
	}

	$source_url = trim( (string) get_post_meta( $post_id, '_lmhg_source_url', true ) );
	return '' !== $source_url ? $post_id : 0;
}

/**
 * Gets the stored route manifest entry for an imported page.
 *
 * @param int $post_id Post ID.
 * @return array<string,mixed>
 */
function lmhg_site_core_route_manifest_entry( int $post_id ): array {
	$json = (string) get_post_meta( $post_id, '_lmhg_route_manifest_entry', true );
	$route = json_decode( $json, true );
	return is_array( $route ) ? $route : array();
}

/**
 * Builds the absolute canonical URL for an imported page.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function lmhg_site_core_imported_canonical_url( int $post_id ): string {
	$canonical = trim( (string) get_post_meta( $post_id, '_lmhg_canonical_url', true ) );
	if ( '' === $canonical ) {
		$canonical = trim( (string) get_post_meta( $post_id, '_lmhg_source_url', true ) );
	}

	if ( '' === $canonical ) {
		return '';
	}

	if ( str_starts_with( $canonical, 'http://' ) || str_starts_with( $canonical, 'https://' ) ) {
		return $canonical;
	}

	return home_url( '/' === $canonical ? '/' : '/' . ltrim( $canonical, '/' ) );
}

/**
 * Builds a non-stub fallback description from source optimization terms.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function lmhg_site_core_fallback_meta_description( int $post_id ): string {
	$terms_json = (string) get_post_meta( $post_id, '_lmhg_optimization_terms', true );
	$terms = json_decode( $terms_json, true );
	if ( ! is_array( $terms ) || empty( $terms ) ) {
		return '';
	}

	$terms = array_values(
		array_filter(
			array_map(
				static fn( $term ) => trim( wp_strip_all_tags( (string) $term ) ),
				$terms
			)
		)
	);

	if ( empty( $terms ) ) {
		return '';
	}

	$description = implode( '. ', array_slice( $terms, 0, 3 ) );
	if ( ! str_ends_with( $description, '.' ) ) {
		$description .= '.';
	}

	return wp_html_excerpt( $description, 155, '...' );
}

/**
 * Returns FAQ items that have publishable question and answer text.
 *
 * @param array<string,mixed> $route Route entry.
 * @return array<int,array{question:string,answer:string}>
 */
function lmhg_site_core_publishable_faq_items( array $route ): array {
	$items = isset( $route['faqItems'] ) && is_array( $route['faqItems'] )
		? $route['faqItems']
		: array();
	$publishable = array();

	foreach ( $items as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}

		$question = lmhg_site_core_clean_faq_text( (string) ( $item['question'] ?? '' ) );
		$answer = lmhg_site_core_clean_faq_text( (string) ( $item['answer'] ?? '' ) );

		if ( '' === $question || '' === $answer ) {
			continue;
		}

		$publishable[] = array(
			'question' => $question,
			'answer'   => $answer,
		);
	}

	return $publishable;
}

/**
 * Cleans source FAQ text and rejects workbook placeholders.
 *
 * @param string $value Source text.
 * @return string
 */
function lmhg_site_core_clean_faq_text( string $value ): string {
	$value = trim( wp_strip_all_tags( $value ) );
	$value = preg_replace( '/\s*---\s*$/', '', $value ) ?? $value;
	$value = preg_replace( '/`+\s*$/', '', $value ) ?? $value;
	$value = trim( $value );

	if ( '' === $value || '[...]' === $value || str_contains( $value, '[...]' ) || preg_match( '/^\[[^\]]+\]$/', $value ) ) {
		return '';
	}

	return $value;
}
