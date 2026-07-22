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
add_filter( 'rank_math/frontend/robots', 'lmhg_site_core_filter_rank_math_404_robots', 100 );
add_filter( 'rank_math/frontend/canonical', 'lmhg_site_core_filter_rank_math_404_canonical', 100 );
add_filter( 'wp_headers', 'lmhg_site_core_filter_development_robots_headers' );
add_filter( 'wp_headers', 'lmhg_site_core_filter_unavailable_surface_headers', 100 );
add_action( 'send_headers', 'lmhg_site_core_send_development_robots_header' );
add_action( 'send_headers', 'lmhg_site_core_send_unavailable_surface_robots_header', 100 );
add_action( 'wp_head', 'lmhg_site_core_output_canonical', 4 );
add_action( 'wp_head', 'lmhg_site_core_output_meta_description', 5 );
add_action( 'wp_head', 'lmhg_site_core_output_social_metadata', 6 );
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
	if ( is_404() ) {
		return array(
			'noindex' => true,
			'follow'  => true,
		);
	}

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
 * Keeps Rank Math's robots output explicit and recovery-friendly on real 404s.
 *
 * @param array<string,string> $robots Rank Math robots directives.
 * @return array<string,string>
 */
function lmhg_site_core_filter_rank_math_404_robots( array $robots ): array {
	if ( ! is_404() ) {
		return $robots;
	}

	return array(
		'index'  => 'noindex',
		'follow' => 'follow',
	);
}

/**
 * Prevents Rank Math from identifying an error URL as canonical content.
 *
 * @param string|false $canonical Rank Math canonical URL.
 * @return string|false
 */
function lmhg_site_core_filter_rank_math_404_canonical( string|false $canonical ): string|false {
	return is_404() ? false : $canonical;
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
 * Marks real errors and intentionally unavailable feeds as non-indexable.
 *
 * @param array<string,string> $headers HTTP response headers.
 * @return array<string,string>
 */
function lmhg_site_core_filter_unavailable_surface_headers( array $headers ): array {
	if ( is_admin() || ( ! is_404() && ! is_feed() ) ) {
		return $headers;
	}

	$headers['X-Robots-Tag'] = 'noindex, follow';
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

/** Ensures late header writers cannot make an unavailable surface indexable. */
function lmhg_site_core_send_unavailable_surface_robots_header(): void {
	if ( is_admin() || ( ! is_404() && ! is_feed() ) || headers_sent() ) {
		return;
	}

	header( 'X-Robots-Tag: noindex, follow', true );
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

	$post_id     = is_singular() ? (int) get_queried_object_id() : 0;
	$description = $post_id > 0 ? lmhg_site_core_resolved_meta_description_for_post( $post_id ) : '';
	if ( '' === $description ) {
		$description = lmhg_site_core_normalize_core30_seo_copy( wp_html_excerpt( wp_strip_all_tags( get_bloginfo( 'description' ) ), 155, '...' ) );
	}
	if ( '' === trim( $description ) ) {
		return;
	}

	printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $description ) );
}

/**
 * Returns the public meta description for any canonical page.
 */
function lmhg_site_core_resolved_meta_description_for_post( int $post_id ): string {
	$description = trim( (string) get_post_meta( $post_id, '_lmhg_meta_description', true ) );
	if ( '' === $description ) {
		$description = lmhg_site_core_fallback_meta_description( $post_id );
	}
	if ( '' === $description ) {
		$description = trim( (string) get_post_field( 'post_excerpt', $post_id ) );
	}
	if ( '' === $description ) {
		$description = lmhg_site_core_first_meaningful_content_paragraph( $post_id );
	}

	return lmhg_site_core_normalize_core30_seo_copy(
		wp_html_excerpt( wp_strip_all_tags( $description ), 155, '...' )
	);
}

/**
 * Finds the first substantive paragraph in serialized Gutenberg content.
 */
function lmhg_site_core_first_meaningful_content_paragraph( int $post_id ): string {
	$blocks = parse_blocks( (string) get_post_field( 'post_content', $post_id ) );
	return lmhg_site_core_find_meaningful_paragraph_in_blocks( $blocks );
}

/**
 * Recursively finds one substantive paragraph.
 *
 * @param array<int,array<string,mixed>> $blocks Parsed blocks.
 */
function lmhg_site_core_find_meaningful_paragraph_in_blocks( array $blocks ): string {
	$excluded_classes = array( 'wp2026-breadcrumbs', 'wp2026-start-number', 'wp2026-service-link', 'wp2026-kicker' );
	foreach ( $blocks as $block ) {
		if ( ! is_array( $block ) ) {
			continue;
		}

		if ( 'core/paragraph' === (string) ( $block['blockName'] ?? '' ) ) {
			$class_name = (string) ( $block['attrs']['className'] ?? '' );
			$excluded   = array_filter( $excluded_classes, static fn( string $class ): bool => str_contains( $class_name, $class ) );
			$text       = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) ( $block['innerHTML'] ?? '' ) ) ) ?? '' );
			if ( empty( $excluded ) && mb_strlen( $text ) >= 40 ) {
				return $text;
			}
		}

		$inner_blocks = isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ? $block['innerBlocks'] : array();
		if ( ! empty( $inner_blocks ) ) {
			$text = lmhg_site_core_find_meaningful_paragraph_in_blocks( $inner_blocks );
			if ( '' !== $text ) {
				return $text;
			}
		}
	}

	return '';
}

/** Returns the preferred social/document title for an arbitrary page. */
function lmhg_site_core_resolved_seo_title_for_post( int $post_id ): string {
	$title = trim( (string) get_post_meta( $post_id, '_lmhg_seo_title', true ) );
	if ( '' === $title ) {
		$title = wp_strip_all_tags( get_the_title( $post_id ) );
	}
	return lmhg_site_core_normalize_core30_seo_copy( $title );
}

/** Outputs shared Open Graph and Twitter metadata while Rank Math is absent. */
function lmhg_site_core_output_social_metadata(): void {
	if ( is_admin() || is_feed() || is_robots() || is_404() || ( function_exists( 'lmhg_site_core_rank_math_owns_standard_seo' ) && lmhg_site_core_rank_math_owns_standard_seo() ) ) {
		return;
	}

	$post_id     = is_singular() ? (int) get_queried_object_id() : 0;
	$title       = $post_id > 0 ? lmhg_site_core_resolved_seo_title_for_post( $post_id ) : wp_get_document_title();
	$description = $post_id > 0 ? lmhg_site_core_resolved_meta_description_for_post( $post_id ) : trim( (string) get_bloginfo( 'description' ) );
	$url         = lmhg_site_core_current_canonical_url();
	$type        = $post_id > 0 && 'Article' === lmhg_site_core_default_schema_type_for_page( $post_id ) ? 'article' : 'website';

	$tags = array(
		array( 'property', 'og:locale', 'en_US' ),
		array( 'property', 'og:type', $type ),
		array( 'property', 'og:title', $title ),
		array( 'property', 'og:description', $description ),
		array( 'property', 'og:url', $url ),
		array( 'property', 'og:site_name', get_bloginfo( 'name' ) ),
		array( 'name', 'twitter:card', has_post_thumbnail( $post_id ) ? 'summary_large_image' : 'summary' ),
		array( 'name', 'twitter:title', $title ),
		array( 'name', 'twitter:description', $description ),
	);

	if ( $post_id > 0 && has_post_thumbnail( $post_id ) ) {
		$image_id = (int) get_post_thumbnail_id( $post_id );
		$image    = wp_get_attachment_image_src( $image_id, 'full' );
		if ( is_array( $image ) && ! empty( $image[0] ) ) {
			$tags[] = array( 'property', 'og:image', (string) $image[0] );
			$tags[] = array( 'property', 'og:image:width', (string) ( $image[1] ?? '' ) );
			$tags[] = array( 'property', 'og:image:height', (string) ( $image[2] ?? '' ) );
			$tags[] = array( 'name', 'twitter:image', (string) $image[0] );
		}
	}

	foreach ( $tags as $tag ) {
		if ( '' !== trim( (string) $tag[2] ) ) {
			printf( '<meta %1$s="%2$s" content="%3$s" />' . "\n", esc_attr( $tag[0] ), esc_attr( $tag[1] ), esc_attr( $tag[2] ) );
		}
	}
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
		$queried_id = is_singular() ? (int) get_queried_object_id() : 0;
		if ( $queried_id > 0 ) {
			$headline = lmhg_site_core_normalize_core30_seo_copy( wp_strip_all_tags( get_the_title( $queried_id ) ) );
			$canonical = lmhg_site_core_current_canonical_url();
			$page_graph = array(
				'@context'     => 'https://schema.org',
				'@type'        => 'WebPage',
				'name'         => $headline,
				'headline'     => $headline,
				'url'          => '' !== $canonical ? $canonical : get_permalink( $queried_id ),
				'isPartOf'     => array(
					'@type' => 'WebSite',
					'name'  => $name,
					'url'   => $site_url,
				),
				'dateModified' => get_the_modified_date( DATE_W3C, $queried_id ),
			);

			$graph = lmhg_site_core_singular_schema_graph( $page_graph, $queried_id );
		} else {
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
		}
	} else {
		$schema_type = trim( (string) get_post_meta( $post_id, '_lmhg_schema_type', true ) );
		$schema_type = '' !== $schema_type ? $schema_type : lmhg_site_core_default_schema_type_for_page( $post_id );
		$headline    = lmhg_site_core_normalize_core30_seo_copy( wp_strip_all_tags( get_the_title( $post_id ) ) );
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

		$graph = lmhg_site_core_singular_schema_graph( $page_graph, $post_id, $route, $graph_nodes );
	}

	printf(
		'<script type="application/ld+json">%s</script>' . "\n",
		wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
	);
}

/** Returns the semantic base schema type for a page template. */
function lmhg_site_core_default_schema_type_for_page( int $post_id ): string {
	if ( (int) get_option( 'page_on_front' ) === $post_id ) {
		return 'WebPage';
	}

	$template = sanitize_key( (string) get_page_template_slug( $post_id ) );
	if ( 'article-page' === $template ) {
		return 'Article';
	}

	return match ( $template ) {
		'service-page', 'specialty-page', 'location-access-page' => 'MedicalWebPage',
		'services-hub', 'specialties-hub', 'article-hub', 'faq-hub', 'team-page', 'trust-page' => 'CollectionPage',
		'contact-page' => 'ContactPage',
		default        => 'WebPage',
	};
}

/**
 * Builds the complete graph for a singular page.
 *
 * @param array<string,mixed> $page_graph Base WebPage/Article node.
 * @param int                 $post_id Page/post ID.
 * @param array<string,mixed> $route Optional route manifest entry.
 * @param array<int,array<string,mixed>> $graph_nodes Optional prebuilt nodes.
 * @return array<string,mixed>
 */
function lmhg_site_core_singular_schema_graph( array $page_graph, int $post_id, array $route = array(), array $graph_nodes = array() ): array {
	if ( empty( $graph_nodes ) ) {
		$graph_nodes = array( $page_graph );
	}

	$url = isset( $page_graph['url'] ) ? (string) $page_graph['url'] : '';

	$service_node = lmhg_site_core_service_schema_node( $post_id, $url );
	if ( ! empty( $service_node ) ) {
		$graph_nodes[] = $service_node;
	}

	$faq_items = function_exists( 'lmhg_site_core_publishable_faq_items_for_page' )
		? lmhg_site_core_publishable_faq_items_for_page( $post_id )
		: array();
	if ( empty( $faq_items ) ) {
		$faq_items = lmhg_site_core_publishable_faq_items( $route );
	}
	if ( ! empty( $faq_items ) ) {
		$graph_nodes[] = lmhg_site_core_faq_schema_node( $faq_items, $url );
	}

	$review_nodes = function_exists( 'lmhg_site_core_review_showcase_schema_nodes' )
		? lmhg_site_core_review_showcase_schema_nodes( $post_id )
		: array();
	foreach ( $review_nodes as $review_node ) {
		$graph_nodes[] = $review_node;
	}

	if ( 1 === count( $graph_nodes ) ) {
		return $page_graph;
	}

	return array(
		'@context' => 'https://schema.org',
		'@graph'   => $graph_nodes,
	);
}

/**
 * Builds service schema for active service and specialty templates.
 *
 * @param int    $post_id Page ID.
 * @param string $url Canonical page URL.
 * @return array<string,mixed>
 */
function lmhg_site_core_service_schema_node( int $post_id, string $url ): array {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post || 'page' !== $post->post_type ) {
		return array();
	}

	$template = get_page_template_slug( $post );
	if ( ! in_array( $template, array( 'service-page', 'specialty-page' ), true ) ) {
		return array();
	}

	$url = '' !== $url ? $url : (string) get_permalink( $post_id );
	if ( '' === $url ) {
		return array();
	}

	$name = lmhg_site_core_normalize_core30_seo_copy( wp_strip_all_tags( get_the_title( $post_id ) ) );

	return array(
		'@type'       => 'Service',
		'@id'         => trailingslashit( $url ) . '#service',
		'name'        => $name,
		'serviceType' => $name,
		'url'         => $url,
		'provider'    => lmhg_site_core_organization_schema_node(),
		'areaServed'  => array(
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
		),
	);
}

/**
 * Builds the organization node used by local service and review schema.
 *
 * @return array<string,mixed>
 */
function lmhg_site_core_organization_schema_node(): array {
	return array(
		'@type'     => 'MedicalOrganization',
		'@id'       => home_url( '/#organization' ),
		'name'      => get_bloginfo( 'name' ),
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
 * Builds FAQPage schema from visible/publishable FAQ items.
 *
 * @param array<int,array{question:string,answer:string}> $faq_items FAQ items.
 * @param string                                          $url Canonical page URL.
 * @return array<string,mixed>
 */
function lmhg_site_core_faq_schema_node( array $faq_items, string $url ): array {
	$node = array(
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

	if ( '' !== $url ) {
		$node['@id'] = trailingslashit( $url ) . '#faq';
		$node['url'] = $url;
	}

	return $node;
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
