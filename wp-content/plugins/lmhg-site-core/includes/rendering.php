<?php
/**
 * Rendered LMHG markers and graph sections for imported pages.
 *
 * @package LMHGSiteCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'the_content', 'lmhg_site_core_render_imported_content', 20 );
add_filter( 'render_block', 'lmhg_site_core_mark_post_title_block', 20, 2 );

/**
 * Replaces migration stubs with source-derived proof content.
 *
 * @param string $content Existing post content.
 * @return string
 */
function lmhg_site_core_render_imported_content( string $content ): string {
	$post_id = lmhg_site_core_imported_post_id();
	if ( 0 === $post_id || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$route = lmhg_site_core_route_manifest_entry( $post_id );
	if ( empty( $route ) ) {
		return $content;
	}

	$source_url = trim( (string) get_post_meta( $post_id, '_lmhg_source_url', true ) );
	$sections = array(
		lmhg_site_core_render_summary_section( $post_id, $source_url ),
		lmhg_site_core_render_source_copy_section( $route, $source_url ),
		lmhg_site_core_render_breadcrumb_section( $post_id, $route, $source_url ),
		lmhg_site_core_render_related_section( $route, $source_url ),
		lmhg_site_core_render_faq_section( $route, $source_url ),
		lmhg_site_core_render_faq_readiness( $route, $source_url ),
	);

	return implode( "\n", array_filter( $sections ) );
}

/**
 * Adds stable marker identity to rendered post title blocks.
 *
 * @param string              $block_content Rendered block HTML.
 * @param array<string,mixed> $block Parsed block.
 * @return string
 */
function lmhg_site_core_mark_post_title_block( string $block_content, array $block ): string {
	if ( ( $block['blockName'] ?? '' ) !== 'core/post-title' ) {
		return $block_content;
	}

	$post_id = lmhg_site_core_imported_post_id();
	if ( 0 === $post_id ) {
		return $block_content;
	}

	$source_url = trim( (string) get_post_meta( $post_id, '_lmhg_source_url', true ) );
	$marker = lmhg_site_core_marker_id( $source_url, 'h1' );

	return preg_replace(
		'/<(h[1-6])([^>]*)>/',
		'<$1$2 data-lmhg-edit-field="' . esc_attr( $marker ) . '">',
		$block_content,
		1
	) ?? $block_content;
}

/**
 * Renders source-derived summary copy.
 *
 * @param int    $post_id Post ID.
 * @param string $source_url Source URL.
 * @return string
 */
function lmhg_site_core_render_summary_section( int $post_id, string $source_url ): string {
	$description = trim( (string) get_post_meta( $post_id, '_lmhg_meta_description', true ) );
	if ( '' === $description ) {
		$description = lmhg_site_core_fallback_meta_description( $post_id );
	}

	if ( '' === $description ) {
		return '';
	}

	return sprintf(
		'<section class="lmhg-source-summary" data-lmhg-edit-field="%1$s"><p>%2$s</p></section>',
		esc_attr( lmhg_site_core_marker_id( $source_url, 'summary' ) ),
		esc_html( $description )
	);
}

/**
 * Renders sanitized source copy from the exported implementation target.
 *
 * @param array<string,mixed> $route Route entry.
 * @param string              $source_url Source URL.
 * @return string
 */
function lmhg_site_core_render_source_copy_section( array $route, string $source_url ): string {
	$source_content = isset( $route['sourceContent'] ) && is_array( $route['sourceContent'] )
		? $route['sourceContent']
		: array();
	$snippets = isset( $source_content['textSnippets'] ) && is_array( $source_content['textSnippets'] )
		? $source_content['textSnippets']
		: array();
	$snippets = array_values(
		array_filter(
			array_map(
				static fn( $snippet ): string => trim( wp_strip_all_tags( (string) $snippet ) ),
				$snippets
			)
		)
	);

	if ( empty( $snippets ) ) {
		return sprintf(
			'<div hidden data-lmhg-readiness-warning="source-copy-missing" data-lmhg-edit-field="%s"></div>',
			esc_attr( lmhg_site_core_marker_id( $source_url, 'source-content-readiness' ) )
		);
	}

	$items = array();
	foreach ( array_slice( $snippets, 0, 12 ) as $index => $snippet ) {
		$items[] = sprintf(
			'<p data-lmhg-edit-field="%1$s">%2$s</p>',
			esc_attr( lmhg_site_core_marker_id( $source_url, 'sourceContent.textSnippets[' . $index . ']' ) ),
			esc_html( $snippet )
		);
	}

	return sprintf(
		'<section class="lmhg-source-copy" data-lmhg-edit-field="%1$s" data-lmhg-source-content-path="%2$s"><h2>Page copy</h2>%3$s</section>',
		esc_attr( lmhg_site_core_marker_id( $source_url, 'source-content' ) ),
		esc_attr( (string) ( $source_content['path'] ?? '' ) ),
		implode( '', $items )
	);
}

/**
 * Renders a graph-derived breadcrumb section.
 *
 * @param int                 $post_id Post ID.
 * @param array<string,mixed> $route Route entry.
 * @param string              $source_url Source URL.
 * @return string
 */
function lmhg_site_core_render_breadcrumb_section( int $post_id, array $route, string $source_url ): string {
	$crumbs = array(
		array(
			'url'   => '/',
			'label' => 'Home',
		),
	);

	$relationship = isset( $route['relationship'] ) && is_array( $route['relationship'] ) ? $route['relationship'] : array();
	$parent_url = trim( (string) ( $relationship['primaryParentPageUrl'] ?? '' ) );
	if ( '' !== $parent_url && '/' !== $parent_url && $parent_url !== $source_url ) {
		$crumbs[] = array(
			'url'   => $parent_url,
			'label' => lmhg_site_core_title_for_source_url( $parent_url ),
		);
	}

	$crumbs[] = array(
		'url'   => '',
		'label' => get_the_title( $post_id ),
	);

	$items = array();
	foreach ( $crumbs as $index => $crumb ) {
		$label = esc_html( $crumb['label'] );
		if ( '' !== $crumb['url'] ) {
			$items[] = sprintf(
				'<a href="%1$s" data-lmhg-graph-url="%2$s">%3$s</a>',
				esc_url( home_url( $crumb['url'] ) ),
				esc_attr( $crumb['url'] ),
				$label
			);
		} else {
			$items[] = sprintf( '<span aria-current="page">%s</span>', $label );
		}

		if ( $index < count( $crumbs ) - 1 ) {
			$items[] = '<span aria-hidden="true">/</span>';
		}
	}

	return sprintf(
		'<nav class="lmhg-breadcrumbs" aria-label="Breadcrumb" data-lmhg-edit-field="%1$s">%2$s</nav>',
		esc_attr( lmhg_site_core_marker_id( $source_url, 'breadcrumbs' ) ),
		implode( ' ', $items )
	);
}

/**
 * Renders related links from the source graph.
 *
 * @param array<string,mixed> $route Route entry.
 * @param string              $source_url Source URL.
 * @return string
 */
function lmhg_site_core_render_related_section( array $route, string $source_url ): string {
	$related_pages = isset( $route['relatedPages'] ) && is_array( $route['relatedPages'] )
		? $route['relatedPages']
		: array();
	$links = array();

	foreach ( $related_pages as $related ) {
		if ( ! is_array( $related ) || ! empty( $related['avoidLink'] ) ) {
			continue;
		}

		$target_url = trim( (string) ( $related['targetPageUrl'] ?? '' ) );
		if ( '' === $target_url ) {
			continue;
		}

		$label = trim( (string) ( $related['label'] ?? '' ) );
		if ( '' === $label ) {
			$label = lmhg_site_core_title_for_source_url( $target_url );
		}

		$links[] = sprintf(
			'<li><a href="%1$s" data-lmhg-related-page="%2$s">%3$s</a></li>',
			esc_url( home_url( $target_url ) ),
			esc_attr( $target_url ),
			esc_html( $label )
		);
	}

	if ( empty( $links ) ) {
		return sprintf(
			'<div hidden data-lmhg-readiness-warning="related-pages-empty" data-lmhg-edit-field="%s"></div>',
			esc_attr( lmhg_site_core_marker_id( $source_url, 'related-pages-readiness' ) )
		);
	}

	return sprintf(
		'<section class="lmhg-related-pages" data-lmhg-edit-field="%1$s"><h2>Related care options</h2><ul>%2$s</ul></section>',
		esc_attr( lmhg_site_core_marker_id( $source_url, 'related-pages' ) ),
		implode( '', $links )
	);
}

/**
 * Renders publishable FAQ items.
 *
 * @param array<string,mixed> $route Route entry.
 * @param string              $source_url Source URL.
 * @return string
 */
function lmhg_site_core_render_faq_section( array $route, string $source_url ): string {
	$faq_items = lmhg_site_core_publishable_faq_items( $route );
	if ( empty( $faq_items ) ) {
		return '';
	}

	$items = array();
	foreach ( $faq_items as $index => $item ) {
		$items[] = sprintf(
			'<details class="lmhg-faq-item" data-lmhg-faq-question="%1$d"><summary data-lmhg-edit-field="%2$s">%3$s</summary><p data-lmhg-edit-field="%4$s">%5$s</p></details>',
			$index,
			esc_attr( lmhg_site_core_marker_id( $source_url, 'faq[' . $index . '].question' ) ),
			esc_html( $item['question'] ),
			esc_attr( lmhg_site_core_marker_id( $source_url, 'faq[' . $index . '].answer' ) ),
			esc_html( $item['answer'] )
		);
	}

	return sprintf(
		'<section class="lmhg-faq-section" data-lmhg-edit-field="%1$s"><h2>Common questions</h2>%2$s</section>',
		esc_attr( lmhg_site_core_marker_id( $source_url, 'faq' ) ),
		implode( '', $items )
	);
}

/**
 * Emits hidden FAQ readiness markers without publishing workbook prompts.
 *
 * @param array<string,mixed> $route Route entry.
 * @param string              $source_url Source URL.
 * @return string
 */
function lmhg_site_core_render_faq_readiness( array $route, string $source_url ): string {
	$faq_items = isset( $route['faqItems'] ) && is_array( $route['faqItems'] )
		? $route['faqItems']
		: array();
	$count = count( $faq_items ) - count( lmhg_site_core_publishable_faq_items( $route ) );

	if ( $count <= 0 ) {
		return '';
	}

	return sprintf(
		'<div hidden data-lmhg-readiness-warning="faq-answer-missing" data-lmhg-faq-count="%1$d" data-lmhg-edit-field="%2$s"></div>',
		$count,
		esc_attr( lmhg_site_core_marker_id( $source_url, 'faq-readiness' ) )
	);
}

/**
 * Resolves a readable title for a source URL.
 *
 * @param string $source_url Source URL.
 * @return string
 */
function lmhg_site_core_title_for_source_url( string $source_url ): string {
	$posts = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'any',
			'meta_key'       => '_lmhg_source_url',
			'meta_value'     => $source_url,
			'posts_per_page' => 1,
		)
	);

	if ( ! empty( $posts ) && $posts[0] instanceof WP_Post ) {
		return get_the_title( $posts[0] );
	}

	$path = trim( preg_replace( '/\.html$/', '', $source_url ), '/' );
	if ( '' === $path ) {
		return 'Home';
	}

	return ucwords( str_replace( '-', ' ', basename( $path ) ) );
}

/**
 * Builds a stable marker ID for rendered imported fields.
 *
 * @param string $source_url Source URL.
 * @param string $field Field name.
 * @return string
 */
function lmhg_site_core_marker_id( string $source_url, string $field ): string {
	return 'page:' . ( '' !== $source_url ? $source_url : 'unknown' ) . ':' . $field;
}
