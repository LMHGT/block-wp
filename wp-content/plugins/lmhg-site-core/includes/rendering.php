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
add_filter( 'the_content', 'lmhg_site_core_prepend_sitewide_page_hero', 34 );
add_filter( 'render_block', 'lmhg_site_core_mark_post_title_block', 20, 2 );
add_filter( 'run_wptexturize', 'lmhg_site_core_disable_texturize_for_editable_blocks' );

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

	if ( function_exists( 'lmhg_site_core_has_editable_block_content' ) && lmhg_site_core_has_editable_block_content( $post_id ) ) {
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

	if (
		function_exists( 'lmhg_site_core_has_editable_block_content' )
		&& lmhg_site_core_has_editable_block_content( $post_id )
		&& lmhg_site_core_post_content_has_h1( $post_id )
	) {
		return '';
	}

	$source_url = trim( (string) get_post_meta( $post_id, '_lmhg_source_url', true ) );
	if ( lmhg_site_core_uses_sitewide_page_hero( $post_id ) ) {
		return '';
	}

	$marker = lmhg_site_core_marker_id( $source_url, 'h1' );
	$title = trim( wp_strip_all_tags( (string) get_the_title( $post_id ) ) );
	$title_chars = function_exists( 'mb_strlen' ) ? mb_strlen( $title ) : strlen( $title );
	$title_fit_vw = lmhg_site_core_title_fit_vw( (int) $title_chars );

	return preg_replace_callback(
		'/<(h[1-6])([^>]*)>/',
		static function ( array $matches ) use ( $marker, $title_chars, $title_fit_vw ): string {
			$attributes = $matches[2];
			$class_count = 0;
			$attributes = preg_replace_callback(
				'/\sclass=(["\'])(.*?)\1/i',
				static function ( array $class_matches ): string {
					return ' class=' . $class_matches[1] . esc_attr( trim( $class_matches[2] . ' wp2026-title-fit' ) ) . $class_matches[1];
				},
				$attributes,
				1,
				$class_count
			) ?? $attributes;
			if ( 0 === $class_count ) {
				$attributes .= ' class="wp2026-title-fit"';
			}

			$style_value = '--wp2026-title-fit-vw: ' . $title_fit_vw;
			$style_count = 0;
			$attributes = preg_replace_callback(
				'/\sstyle=(["\'])(.*?)\1/i',
				static function ( array $style_matches ) use ( $style_value ): string {
					$existing_style = trim( $style_matches[2] );
					if ( '' !== $existing_style && ';' !== substr( $existing_style, -1 ) ) {
						$existing_style .= ';';
					}

					return ' style=' . $style_matches[1] . esc_attr( trim( $existing_style . ' ' . $style_value . ';' ) ) . $style_matches[1];
				},
				$attributes,
				1,
				$style_count
			) ?? $attributes;
			if ( 0 === $style_count ) {
				$attributes .= ' style="' . esc_attr( $style_value . ';' ) . '"';
			}

			return sprintf(
				'<%1$s%2$s data-lmhg-edit-field="%3$s" data-lmhg-title-chars="%4$d">',
				$matches[1],
				$attributes,
				esc_attr( $marker ),
				(int) $title_chars
			);
		},
		$block_content,
		1
	) ?? $block_content;
}

/**
 * Returns whether an imported page should use the shared title, description,
 * logo, and CTA hero used by the homepage.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function lmhg_site_core_uses_sitewide_page_hero( int $post_id ): bool {
	if ( $post_id <= 0 || ! is_singular( 'page' ) || is_front_page() ) {
		return false;
	}

	return '' !== trim( (string) get_post_meta( $post_id, '_lmhg_source_url', true ) );
}

/**
 * Prepends the shared page hero while leaving the editable source blocks in
 * place for WordPress authors. CSS suppresses the original lead and hero CTA
 * inside the body so each value is presented once on the public page.
 *
 * @param string $content Rendered post content.
 * @return string
 */
function lmhg_site_core_prepend_sitewide_page_hero( string $content ): string {
	if ( is_admin() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$post_id = lmhg_site_core_imported_post_id();
	if ( ! lmhg_site_core_uses_sitewide_page_hero( $post_id ) || str_contains( $content, 'wp2026-sitewide-hero' ) ) {
		return $content;
	}

	$title       = trim( wp_strip_all_tags( (string) get_the_title( $post_id ) ) );
	$description = lmhg_site_core_page_hero_description( $content, $post_id );
	if ( '' === $title || '' === $description ) {
		return $content;
	}

	$source_url = trim( (string) get_post_meta( $post_id, '_lmhg_source_url', true ) );
	$marker     = lmhg_site_core_marker_id( $source_url, 'h1' );
	$reach_out  = function_exists( 'lmhg_site_core_render_reach_out_block' )
		? lmhg_site_core_render_reach_out_block()
		: '';
	$call       = '<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="tel:+15024161416">Call (502) 416-1416</a></div>';

	$hero = sprintf(
		'<section class="wp-block-group alignwide wp2026-home-hero wp2026-sitewide-hero" aria-labelledby="lmhg-page-hero-title"><div class="wp-block-group wp2026-home-hero-columns"><div class="wp-block-group wp2026-hero-copy"><h1 id="lmhg-page-hero-title" class="wp-block-heading wp2026-home-title" data-lmhg-edit-field="%1$s">%2$s</h1><p class="wp2026-home-lead">%3$s</p><div class="wp-block-buttons wp2026-hero-actions">%4$s%5$s</div></div></div></section>',
		esc_attr( $marker ),
		esc_html( $title ),
		wp_kses_post( $description ),
		$reach_out,
		$call
	);

	return $hero . "\n" . ltrim( $content );
}

/**
 * Gets the first page-specific lead, falling back to the stored SEO summary.
 *
 * @param string $content Rendered post content.
 * @param int    $post_id Post ID.
 * @return string
 */
function lmhg_site_core_page_hero_description( string $content, int $post_id ): string {
	if ( preg_match( '/<p\b[^>]*class=(["\'])[^"\']*\bwp2026-lead\b[^"\']*\1[^>]*>(.*?)<\/p>/is', $content, $matches ) ) {
		$description = trim( wp_kses_post( $matches[2] ) );
		if ( '' !== $description ) {
			return $description;
		}
	}

	$description = trim( (string) get_post_meta( $post_id, '_lmhg_meta_description', true ) );
	if ( '' === $description ) {
		$description = lmhg_site_core_fallback_meta_description( $post_id );
	}

	return esc_html( $description );
}

/**
 * Returns the viewport font-size value used to keep page titles on one line.
 *
 * @param int $title_chars Title character count.
 * @return string CSS vw value.
 */
function lmhg_site_core_title_fit_vw( int $title_chars ): string {
	$effective_chars = max( 8, $title_chars );
	$divisor = max( 8.0, round( $effective_chars * 0.66, 1 ) );
	$vw = 100 / $divisor;

	return rtrim( rtrim( number_format( $vw, 2, '.', '' ), '0' ), '.' ) . 'vw';
}

/**
 * Detects whether editable post content already owns the document H1.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function lmhg_site_core_post_content_has_h1( int $post_id ): bool {
	$content = (string) get_post_field( 'post_content', $post_id );
	if ( '' === trim( $content ) ) {
		return false;
	}

	if ( preg_match( '/<h1(?:\s|>)/i', $content ) ) {
		return true;
	}

	return function_exists( 'parse_blocks' ) && lmhg_site_core_blocks_contain_h1( parse_blocks( $content ) );
}

/**
 * Checks parsed Gutenberg blocks for an H1 heading.
 *
 * @param array<int,array<string,mixed>> $blocks Parsed blocks.
 * @return bool
 */
function lmhg_site_core_blocks_contain_h1( array $blocks ): bool {
	foreach ( $blocks as $block ) {
		if ( 'core/heading' === ( $block['blockName'] ?? '' ) && 1 === (int) ( $block['attrs']['level'] ?? 2 ) ) {
			return true;
		}

		if ( isset( $block['innerHTML'] ) && is_string( $block['innerHTML'] ) && preg_match( '/<h1(?:\s|>)/i', $block['innerHTML'] ) ) {
			return true;
		}

		if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) && lmhg_site_core_blocks_contain_h1( $block['innerBlocks'] ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Removes theme-owned header/footer template parts when post content owns full-page staging text.
 *
 * @param string              $block_content Rendered block HTML.
 * @param array<string,mixed> $block Parsed block.
 * @return string
 */
function lmhg_site_core_hide_theme_chrome_for_editable_blocks( string $block_content, array $block ): string {
	if ( ( $block['blockName'] ?? '' ) !== 'core/template-part' ) {
		return $block_content;
	}

	$post_id = lmhg_site_core_imported_post_id();
	if ( 0 === $post_id || ! lmhg_site_core_has_editable_block_content( $post_id ) ) {
		return $block_content;
	}

	return '';
}

/**
 * Preserves staging-derived punctuation for imported full-page block content.
 *
 * @param bool $run_texturize Whether WordPress should apply smart punctuation.
 * @return bool
 */
function lmhg_site_core_disable_texturize_for_editable_blocks( bool $run_texturize ): bool {
	$post_id = lmhg_site_core_imported_post_id();
	if ( 0 !== $post_id && lmhg_site_core_has_editable_block_content( $post_id ) ) {
		return false;
	}

	return $run_texturize;
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

	$content = 'markdown' === (string) ( $source_content['type'] ?? '' )
		? lmhg_site_core_render_markdown_source_content( $source_content, $source_url )
		: lmhg_site_core_render_json_source_content( $source_content, $source_url );

	if ( '' === $content ) {
		$content = lmhg_site_core_render_source_snippets( $source_content, $source_url, 12 );
	}

	return sprintf(
		'<section class="lmhg-source-copy" data-lmhg-edit-field="%1$s" data-lmhg-source-content-path="%2$s"><h2>Page copy</h2>%3$s</section>',
		esc_attr( lmhg_site_core_marker_id( $source_url, 'source-content' ) ),
		esc_attr( (string) ( $source_content['path'] ?? '' ) ),
		$content
	);
}

/**
 * Renders source-copy snippets as marked paragraphs.
 *
 * @param array<string,mixed> $source_content Source content payload.
 * @param string              $source_url Source URL.
 * @param int                 $limit Snippet limit.
 * @return string
 */
function lmhg_site_core_render_source_snippets( array $source_content, string $source_url, int $limit ): string {
	$snippets = isset( $source_content['textSnippets'] ) && is_array( $source_content['textSnippets'] )
		? array_values( $source_content['textSnippets'] )
		: array();
	$items = array();

	foreach ( array_slice( $snippets, 0, $limit ) as $index => $snippet ) {
		$text = trim( wp_strip_all_tags( (string) $snippet ) );
		if ( '' === $text ) {
			continue;
		}

		$items[] = sprintf(
			'<p data-lmhg-edit-field="%1$s">%2$s</p>',
			esc_attr( lmhg_site_core_marker_id( $source_url, 'sourceContent.textSnippets[' . $index . ']' ) ),
			esc_html( $text )
		);
	}

	return implode( '', $items );
}

/**
 * Renders Markdown source content with article structure.
 *
 * @param array<string,mixed> $source_content Source content payload.
 * @param string              $source_url Source URL.
 * @return string
 */
function lmhg_site_core_render_markdown_source_content( array $source_content, string $source_url ): string {
	$blocks = isset( $source_content['blocks'] ) && is_array( $source_content['blocks'] )
		? $source_content['blocks']
		: array();
	$html = array();

	foreach ( array_slice( $blocks, 0, 24 ) as $index => $block ) {
		if ( ! is_array( $block ) ) {
			continue;
		}

		$type = (string) ( $block['type'] ?? '' );
		if ( 'heading' === $type ) {
			$level = 3 === (int) ( $block['level'] ?? 2 ) ? 3 : 2;
			$text = trim( wp_strip_all_tags( (string) ( $block['text'] ?? '' ) ) );
			if ( '' !== $text ) {
				$html[] = sprintf(
					'<h%1$d data-lmhg-edit-field="%2$s">%3$s</h%1$d>',
					$level,
					esc_attr( lmhg_site_core_marker_id( $source_url, 'sourceContent.blocks[' . $index . '].text' ) ),
					esc_html( $text )
				);
			}
			continue;
		}

		if ( 'paragraph' === $type ) {
			$text = trim( wp_strip_all_tags( (string) ( $block['text'] ?? '' ) ) );
			if ( '' !== $text ) {
				$html[] = lmhg_site_core_render_marked_source_paragraph( $source_content, $source_url, $text, 'sourceContent.blocks[' . $index . '].text' );
			}
			continue;
		}

		if ( 'list' === $type && isset( $block['items'] ) && is_array( $block['items'] ) ) {
			$items = array();
			foreach ( $block['items'] as $item_index => $item ) {
				$text = trim( wp_strip_all_tags( (string) $item ) );
				if ( '' === $text ) {
					continue;
				}
				$items[] = sprintf(
					'<li data-lmhg-edit-field="%1$s">%2$s</li>',
					esc_attr( lmhg_site_core_source_text_marker( $source_content, $source_url, $text, 'sourceContent.blocks[' . $index . '].items[' . $item_index . ']' ) ),
					esc_html( $text )
				);
			}
			if ( ! empty( $items ) ) {
				$html[] = '<ul class="lmhg-source-copy__list">' . implode( '', $items ) . '</ul>';
			}
		}
	}

	return implode( '', $html );
}

/**
 * Renders JSON source content with intro snippets and card groups.
 *
 * @param array<string,mixed> $source_content Source content payload.
 * @param string              $source_url Source URL.
 * @return string
 */
function lmhg_site_core_render_json_source_content( array $source_content, string $source_url ): string {
	$data = isset( $source_content['data'] ) && is_array( $source_content['data'] )
		? $source_content['data']
		: array();
	if ( empty( $data ) ) {
		return '';
	}

	$sections = array(
		'<div class="lmhg-source-copy__intro">' . lmhg_site_core_render_source_snippets( $source_content, $source_url, 8 ) . '</div>',
		lmhg_site_core_render_source_card_groups( $data, $source_content, $source_url ),
	);

	return implode( '', array_filter( $sections ) );
}

/**
 * Renders card groups from known source-copy card arrays.
 *
 * @param array<string,mixed> $data Source JSON data.
 * @param array<string,mixed> $source_content Source content payload.
 * @param string              $source_url Source URL.
 * @return string
 */
function lmhg_site_core_render_source_card_groups( array $data, array $source_content, string $source_url ): string {
	$groups = array();
	if ( isset( $data['cards'] ) && is_array( $data['cards'] ) ) {
		$groups[] = array(
			'title' => 'Explore options',
			'path'  => 'sourceContent.data.cards',
			'cards' => $data['cards'],
		);
	}
	if ( isset( $data['services']['cards'] ) && is_array( $data['services']['cards'] ) ) {
		$groups[] = array(
			'title' => (string) ( $data['services']['title'] ?? 'Services' ),
			'path'  => 'sourceContent.data.services.cards',
			'cards' => $data['services']['cards'],
		);
	}

	$html = array();
	foreach ( $groups as $group_index => $group ) {
		$cards = array_values( array_filter( $group['cards'], 'is_array' ) );
		if ( empty( $cards ) ) {
			continue;
		}

		$items = array();
		foreach ( array_slice( $cards, 0, 12 ) as $card_index => $card ) {
			$title = trim( wp_strip_all_tags( (string) ( $card['title'] ?? '' ) ) );
			$description = trim( wp_strip_all_tags( (string) ( $card['description'] ?? '' ) ) );
			$href = trim( (string) ( $card['href'] ?? '' ) );
			if ( '' === $title && '' === $description ) {
				continue;
			}

			$title_html = '' !== $href
				? sprintf(
					'<a href="%1$s" data-lmhg-source-card-link="%2$s">%3$s</a>',
					esc_url( home_url( $href ) ),
					esc_attr( $href ),
					esc_html( $title )
				)
				: esc_html( $title );

			$items[] = sprintf(
				'<li class="lmhg-source-card" data-lmhg-source-card="%1$s"><h3 data-lmhg-edit-field="%2$s">%3$s</h3>%4$s</li>',
				esc_attr( $group_index . ':' . $card_index ),
				esc_attr( lmhg_site_core_marker_id( $source_url, $group['path'] . '[' . $card_index . '].title' ) ),
				$title_html,
				'' !== $description ? lmhg_site_core_render_marked_source_paragraph( $source_content, $source_url, $description, $group['path'] . '[' . $card_index . '].description' ) : ''
			);
		}

		if ( empty( $items ) ) {
			continue;
		}

		$html[] = sprintf(
			'<section class="lmhg-source-card-group" data-lmhg-edit-field="%1$s"><h3>%2$s</h3><ul class="lmhg-source-card-list">%3$s</ul></section>',
			esc_attr( lmhg_site_core_marker_id( $source_url, $group['path'] ) ),
			esc_html( (string) $group['title'] ),
			implode( '', $items )
		);
	}

	return implode( '', $html );
}

/**
 * Renders a paragraph with a snippet marker when the text maps to one.
 *
 * @param array<string,mixed> $source_content Source content payload.
 * @param string              $source_url Source URL.
 * @param string              $text Paragraph text.
 * @param string              $fallback_field Fallback marker field.
 * @return string
 */
function lmhg_site_core_render_marked_source_paragraph( array $source_content, string $source_url, string $text, string $fallback_field ): string {
	return sprintf(
		'<p data-lmhg-edit-field="%1$s">%2$s</p>',
		esc_attr( lmhg_site_core_source_text_marker( $source_content, $source_url, $text, $fallback_field ) ),
		esc_html( $text )
	);
}

/**
 * Resolves a stable marker for a source text value.
 *
 * @param array<string,mixed> $source_content Source content payload.
 * @param string              $source_url Source URL.
 * @param string              $text Source text.
 * @param string              $fallback_field Fallback marker field.
 * @return string
 */
function lmhg_site_core_source_text_marker( array $source_content, string $source_url, string $text, string $fallback_field ): string {
	$snippets = isset( $source_content['textSnippets'] ) && is_array( $source_content['textSnippets'] )
		? array_values( $source_content['textSnippets'] )
		: array();
	foreach ( $snippets as $index => $snippet ) {
		if ( trim( wp_strip_all_tags( (string) $snippet ) ) === $text ) {
			return lmhg_site_core_marker_id( $source_url, 'sourceContent.textSnippets[' . $index . ']' );
		}
	}

	return lmhg_site_core_marker_id( $source_url, $fallback_field );
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
