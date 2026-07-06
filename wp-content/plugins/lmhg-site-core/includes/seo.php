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
add_filter( 'wp_headers', 'lmhg_site_core_filter_development_robots_headers' );
add_action( 'send_headers', 'lmhg_site_core_send_development_robots_header' );
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
	$title = '' !== $seo_title ? $seo_title : $title;

	return lmhg_site_core_normalize_core30_seo_copy( $title );
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
 * Adds staging/development discovery suppression headers through WordPress' header filter.
 *
 * @param array<string,string> $headers HTTP headers.
 * @return array<string,string>
 */
function lmhg_site_core_filter_development_robots_headers( array $headers ): array {
	if ( is_admin() || ! lmhg_site_core_should_suppress_indexing() ) {
		return $headers;
	}

	$headers['X-Robots-Tag'] = LMHG_SITE_CORE_DEVELOPMENT_ROBOTS;
	return $headers;
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
	$suppress_indexing = getenv( 'LMHG_SUPPRESS_INDEXING' );
	if ( '1' === $suppress_indexing || 'true' === strtolower( (string) $suppress_indexing ) ) {
		return true;
	}

	if ( defined( 'LMHG_ALLOW_INDEXING' ) && LMHG_ALLOW_INDEXING ) {
		return false;
	}

	$allow_indexing = getenv( 'LMHG_ALLOW_INDEXING' );
	if ( '1' === $allow_indexing || 'true' === strtolower( (string) $allow_indexing ) ) {
		return false;
	}

	if ( lmhg_site_core_is_public_indexable_host() ) {
		return false;
	}

	if ( lmhg_site_core_is_internal_crawl_surface() ) {
		return false;
	}

	return true;
}

/**
 * Allows the production domain to be crawlable without relying on host env flags.
 *
 * @return bool
 */
function lmhg_site_core_is_public_indexable_host(): bool {
	$host = lmhg_site_core_request_host();

	return in_array( $host, array( 'louisvillementalhealth.org', 'www.louisvillementalhealth.org' ), true );
}

/**
 * Allows SEO analysis on private tailnet/local surfaces without exposing public hosts.
 *
 * @return bool
 */
function lmhg_site_core_is_internal_crawl_surface(): bool {
	$host = lmhg_site_core_request_host();

	if ( in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true ) || str_ends_with( $host, '.ts.net' ) ) {
		return true;
	}

	if ( false === filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
		return false;
	}

	$ip = ip2long( $host );
	if ( false === $ip ) {
		return false;
	}

	$tailnet_start = ip2long( '100.64.0.0' );
	$tailnet_end   = ip2long( '100.127.255.255' );

	return false !== $tailnet_start && false !== $tailnet_end && $ip >= $tailnet_start && $ip <= $tailnet_end;
}

/**
 * Gets the normalized request host used for crawl-surface checks.
 *
 * @return string
 */
function lmhg_site_core_request_host(): string {
	$request_host = isset( $_SERVER['HTTP_X_FORWARDED_HOST'] )
		? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_HOST'] ) )
		: sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ?? '' ) );

	return (string) preg_replace( '/:\d+$/', '', strtolower( $request_host ) );
}

/**
 * Outputs the canonical URL from imported source metadata.
 */
function lmhg_site_core_output_canonical(): void {
	if ( is_admin() || is_feed() || is_robots() || is_404() ) {
		return;
	}

	$canonical = lmhg_site_core_current_canonical_url();
	if ( '' === $canonical ) {
		return;
	}

	printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( $canonical ) );
}

/**
 * Gets the canonical URL for the current public request.
 *
 * @return string
 */
function lmhg_site_core_current_canonical_url(): string {
	$post_id = lmhg_site_core_imported_post_id();
	if ( 0 !== $post_id ) {
		$canonical = lmhg_site_core_imported_canonical_url( $post_id );
		if ( '' !== $canonical ) {
			return lmhg_site_core_normalize_canonical_to_home( $canonical );
		}
	}

	if ( is_singular() ) {
		$queried_id = (int) get_queried_object_id();
		if ( $queried_id > 0 ) {
			$permalink = get_permalink( $queried_id );
			return is_string( $permalink ) ? $permalink : '';
		}
	}

	if ( is_front_page() ) {
		return home_url( '/' );
	}

	$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ) );
	$path = (string) wp_parse_url( $request_uri, PHP_URL_PATH );
	if ( '' === $path ) {
		return '';
	}

	return home_url( '/' === $path ? '/' : trailingslashit( ltrim( $path, '/' ) ) );
}

/**
 * Keeps imported canonical paths on the current proof runtime host.
 *
 * @param string $canonical Stored canonical URL.
 * @return string
 */
function lmhg_site_core_normalize_canonical_to_home( string $canonical ): string {
	$path = (string) wp_parse_url( $canonical, PHP_URL_PATH );
	if ( '' === $path ) {
		return $canonical;
	}

	return home_url( '/' === $path ? '/' : trailingslashit( ltrim( $path, '/' ) ) );
}

/**
 * Normalizes Core30 wording that should not position case management as targeted case management.
 *
 * @param string $value SEO copy.
 * @return string
 */
function lmhg_site_core_normalize_core30_seo_copy( string $value ): string {
	return str_replace(
		array( 'Targeted Case Management', 'Targeted case management', 'targeted case management' ),
		array( 'Case Management', 'Case management', 'case management' ),
		$value
	);
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

		$description = lmhg_site_core_normalize_core30_seo_copy( $description );
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

	$description = lmhg_site_core_normalize_core30_seo_copy( wp_html_excerpt( wp_strip_all_tags( $description ), 155, '...' ) );
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
	$imported_post_id = lmhg_site_core_imported_post_id();
	$post_id  = 0 !== $imported_post_id || ! is_singular() ? $imported_post_id : (int) get_queried_object_id();

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
		$schema_type = '' !== $schema_type ? $schema_type : ( is_singular( 'page' ) ? 'WebPage' : 'Article' );
		$headline    = lmhg_site_core_normalize_core30_seo_copy( wp_strip_all_tags( get_the_title( $post_id ) ) );
		$canonical   = 0 !== $imported_post_id ? lmhg_site_core_imported_canonical_url( $post_id ) : lmhg_site_core_current_canonical_url();
		$route       = 0 !== $imported_post_id ? lmhg_site_core_route_manifest_entry( $post_id ) : array();

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

		$service = lmhg_site_core_core30_service_json_ld( $post_id, $headline, $canonical );
		if ( ! empty( $service ) ) {
			$graph_nodes[] = $service;
		}

		$faq_items = lmhg_site_core_json_ld_faq_items( $post_id, $route );
		if ( empty( $faq_items ) && 1 === count( $graph_nodes ) ) {
			$graph = $page_graph;
		} else {
			if ( ! empty( $faq_items ) ) {
				$faq_url = '' !== $canonical ? $canonical : get_permalink( $post_id );
				$graph_nodes[] = array(
					'@id'        => trailingslashit( (string) $faq_url ) . '#faq',
					'@type'      => 'FAQPage',
					'url'        => $faq_url,
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
 * Builds a local service schema node for Core30 service and specialty pages.
 *
 * @param int    $post_id Post ID.
 * @param string $headline Page headline.
 * @param string $canonical Canonical URL.
 * @return array<string,mixed>
 */
function lmhg_site_core_core30_service_json_ld( int $post_id, string $headline, string $canonical ): array {
	$template = get_page_template_slug( $post_id );
	if ( ! in_array( $template, array( 'service-page', 'specialty-page' ), true ) ) {
		return array();
	}

	$url = '' !== $canonical ? $canonical : get_permalink( $post_id );
	if ( ! is_string( $url ) || '' === $url ) {
		return array();
	}

	return array(
		'@type'       => 'Service',
		'@id'         => trailingslashit( $url ) . '#service',
		'name'        => $headline,
		'serviceType' => $headline,
		'url'         => $url,
		'provider'    => lmhg_site_core_local_provider_json_ld(),
		'areaServed'  => lmhg_site_core_louisville_area_served_json_ld(),
	);
}

/**
 * Builds the LMHG provider node used by local service schema.
 *
 * @return array<string,mixed>
 */
function lmhg_site_core_local_provider_json_ld(): array {
	return array(
		'@type'     => 'MedicalOrganization',
		'@id'       => home_url( '/#organization' ),
		'name'      => 'Louisville Mental Health Group',
		'url'       => home_url( '/' ),
		'telephone' => '+15024161416',
		'address'   => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => '4229 Bardstown Rd, Suite 310',
			'addressLocality' => 'Louisville',
			'addressRegion'   => 'KY',
			'postalCode'      => '40218',
			'addressCountry'  => 'US',
		),
	);
}

/**
 * Builds Louisville-area service coverage nodes.
 *
 * @return array<int,array<string,mixed>>
 */
function lmhg_site_core_louisville_area_served_json_ld(): array {
	return array(
		array(
			'@type'   => 'City',
			'name'    => 'Louisville',
			'address' => array(
				'@type'          => 'PostalAddress',
				'addressRegion'  => 'KY',
				'addressCountry' => 'US',
			),
		),
		array(
			'@type' => 'AdministrativeArea',
			'name'  => 'Jefferson County, KY',
		),
	);
}

/**
 * Gets FAQ items that are visible on the current rendered page.
 *
 * @param int                 $post_id Post ID.
 * @param array<string,mixed> $route Route manifest entry.
 * @return array<int,array{question:string,answer:string}>
 */
function lmhg_site_core_json_ld_faq_items( int $post_id, array $route ): array {
	$uses_editable_blocks = function_exists( 'lmhg_site_core_has_editable_block_content' )
		&& lmhg_site_core_has_editable_block_content( $post_id );

	if ( $uses_editable_blocks ) {
		return function_exists( 'lmhg_site_core_publishable_faq_items_for_page' )
			? lmhg_site_core_publishable_faq_items_for_page( $post_id )
			: array();
	}

	$route_items = lmhg_site_core_publishable_faq_items( $route );
	if ( ! empty( $route_items ) ) {
		return $route_items;
	}

	return function_exists( 'lmhg_site_core_publishable_faq_items_for_page' )
		? lmhg_site_core_publishable_faq_items_for_page( $post_id )
		: array();
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
