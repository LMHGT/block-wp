<?php
/**
 * Helpful not-found and intentionally unavailable feed responses.
 *
 * @package LMHGSiteCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'render_block', 'lmhg_site_core_render_helpful_404_main', 20, 2 );
add_action( 'template_redirect', 'lmhg_site_core_handle_unavailable_feed_early', 0 );

foreach ( array( 'do_feed', 'do_feed_rdf', 'do_feed_rss', 'do_feed_rss2', 'do_feed_atom' ) as $lmhg_feed_hook ) {
	add_action( $lmhg_feed_hook, 'lmhg_site_core_render_unavailable_feed', -100 );
}
unset( $lmhg_feed_hook );

/**
 * Handles disabled feeds before the general inventory 404 interceptor exits.
 */
function lmhg_site_core_handle_unavailable_feed_early(): void {
	if (
		is_admin()
		|| wp_doing_ajax()
		|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
		|| ! is_feed()
	) {
		return;
	}

	lmhg_site_core_render_unavailable_feed();
}

/**
 * Replaces the theme's minimal 404 body while preserving its header and footer.
 *
 * @param string              $block_content Rendered block HTML.
 * @param array<string,mixed> $block Parsed block data.
 */
function lmhg_site_core_render_helpful_404_main( string $block_content, array $block ): string {
	if ( ! is_404() ) {
		return $block_content;
	}

	$class_name = (string) ( $block['attrs']['className'] ?? '' );
	if ( ! str_contains( $class_name, 'wp2026-template-404' ) ) {
		return $block_content;
	}

	return lmhg_site_core_helpful_404_markup(
		lmhg_site_core_not_found_title(),
		lmhg_site_core_not_found_message()
	);
}

/** Returns the editable inventory page title with a stable fallback. */
function lmhg_site_core_not_found_title(): string {
	$page = get_page_by_path( 'not-found', OBJECT, 'page' );
	if ( is_object( $page ) && isset( $page->post_title ) ) {
		$title = trim( wp_strip_all_tags( (string) $page->post_title ) );
		if ( '' !== $title ) {
			return $title;
		}
	}

	return 'Page Not Found';
}

/**
 * Reuses the first meaningful paragraph from the editable inventory page.
 * Shortcodes are never required for the recovery page to work.
 */
function lmhg_site_core_not_found_message(): string {
	$page = get_page_by_path( 'not-found', OBJECT, 'page' );
	if ( is_object( $page ) && isset( $page->post_content ) ) {
		$content = strip_shortcodes( (string) $page->post_content );
		$message = lmhg_site_core_find_meaningful_paragraph_in_blocks( parse_blocks( $content ) );
		$message = preg_replace( '/\s+Reach Out\s*$/i', '', trim( $message ) ) ?? '';
		if ( '' !== $message ) {
			return $message;
		}
	}

	return 'The page you requested is not available. It may have moved, or the address may be incomplete.';
}

/**
 * Builds recovery navigation without redirecting an unknown URL to unrelated content.
 */
function lmhg_site_core_helpful_404_markup( string $title, string $message ): string {
	$destinations = array(
		'/our-services/' => array( 'Services', 'Browse counseling and support services.' ),
		'/specialties/'   => array( 'Specialties', 'Find help by concern or type of support.' ),
		'/locations/'     => array( 'Locations', 'Review office, telehealth, and community options.' ),
		'/faq/'           => array( 'Common Questions', 'Read answers about care, cost, and getting started.' ),
		'/blogs/'         => array( 'Articles', 'Browse practical mental health articles.' ),
		'/contact-us/'    => array( 'Contact Us', 'Ask the office for help finding the right page.' ),
	);

	$cards = '';
	foreach ( $destinations as $path => $destination ) {
		$cards .= sprintf(
			'<li class="wp2026-service-card"><a href="%1$s"><strong>%2$s</strong><span>%3$s</span></a></li>',
			esc_url( home_url( $path ) ),
			esc_html( $destination[0] ),
			esc_html( $destination[1] )
		);
	}

	return sprintf(
		'<main id="main-content" class="wp-block-group wp2026-template-404" aria-labelledby="lmhg-not-found-title">'
		. '<div class="wp-block-group alignwide wp2026-content-section">'
		. '<p class="wp2026-kicker">404 error</p>'
		. '<h1 id="lmhg-not-found-title" class="wp-block-heading">%1$s</h1>'
		. '<p class="wp2026-lead">%2$s</p>'
		. '<div class="wp-block-buttons"><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="%3$s">Return Home</a></div>'
		. '<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="%4$s">Contact Us</a></div></div>'
		. '<h2 class="wp-block-heading wp2026-section-title">Helpful places to continue</h2>'
		. '<ul class="wp-block-list wp2026-service-grid wp2026-not-found-links">%5$s</ul>'
		. '<p>If you followed a link on this website, please contact the office so we can correct it.</p>'
		. '</div></main>',
		esc_html( $title ),
		esc_html( $message ),
		esc_url( home_url( '/' ) ),
		esc_url( home_url( '/contact-us/' ) ),
		$cards
	);
}

/**
 * Stops disabled feeds from falling through to indexable RSS output.
 *
 * @param bool $for_comments Whether WordPress requested a comments feed.
 */
function lmhg_site_core_render_unavailable_feed( bool $for_comments = false ): void {
	unset( $for_comments );
	status_header( 404 );
	nocache_headers();
	header( 'Content-Type: text/plain; charset=UTF-8', true );
	header( 'X-Robots-Tag: noindex, follow', true );
	echo 'This feed is not available. Visit the website to browse current content.';
	exit;
}
