<?php
/**
 * Runtime page-class design enhancements for the WordPress 2026 theme.
 *
 * @package LMHGSiteCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'the_content', 'lmhg_site_core_render_page_class_design_sections', 28 );

/**
 * Adds conservative class-specific design sections without editing page bodies.
 *
 * @param string $content Existing rendered content.
 * @return string
 */
function lmhg_site_core_render_page_class_design_sections( string $content ): string {
	if ( is_admin() || ! is_singular( 'page' ) || ! in_the_loop() || ! is_main_query() || is_front_page() ) {
		return $content;
	}

	$post = get_post();
	if ( ! $post instanceof WP_Post ) {
		return $content;
	}

	$template = lmhg_site_core_page_class_template_slug( $post );
	$path     = lmhg_site_core_page_class_path( $post );
	$section  = match ( $template ) {
		'services-hub'         => lmhg_site_core_render_services_hub_design(),
		'service-page'         => lmhg_site_core_render_service_page_design( $path ),
		'specialties-hub'      => lmhg_site_core_render_specialties_hub_design(),
		'specialty-page'       => lmhg_site_core_render_specialty_page_design( $path ),
		'faq-hub'              => lmhg_site_core_render_faq_hub_design(),
		'faq-page'             => lmhg_site_core_render_faq_page_design( $path ),
		'article-hub'          => lmhg_site_core_render_article_hub_design(),
		'article-page'         => lmhg_site_core_render_article_page_design( $path ),
		'location-access-page' => lmhg_site_core_render_location_access_design( $path ),
		'trust-page'           => lmhg_site_core_render_trust_page_design( $path ),
		'legal-utility-page'   => lmhg_site_core_render_legal_utility_design( $path ),
		default                => '',
	};

	if ( '' === $section ) {
		return $content;
	}

	return lmhg_site_core_insert_before_page_cta( $content, $section );
}

/**
 * Gets the active custom template slug for a page.
 *
 * @param WP_Post $post Page post.
 * @return string
 */
function lmhg_site_core_page_class_template_slug( WP_Post $post ): string {
	$template = get_page_template_slug( $post );
	return is_string( $template ) ? trim( $template ) : '';
}

/**
 * Gets a normalized permalink path for a page.
 *
 * @param WP_Post $post Page post.
 * @return string
 */
function lmhg_site_core_page_class_path( WP_Post $post ): string {
	$path = (string) wp_parse_url( get_permalink( $post ), PHP_URL_PATH );
	if ( '' === $path ) {
		return '/';
	}

	return '/' . trim( $path, '/' ) . '/';
}

/**
 * Inserts a section before the generic page CTA when present.
 *
 * @param string $content Rendered content.
 * @param string $section Section HTML.
 * @return string
 */
function lmhg_site_core_insert_before_page_cta( string $content, string $section ): string {
	$pattern = '/(<div\s+class="[^"]*\bwp2026-page-cta\b[^"]*")/i';
	if ( preg_match( $pattern, $content ) ) {
		return preg_replace( $pattern, $section . "\n" . '$1', $content, 1 ) ?? $content . "\n" . $section;
	}

	return $content . "\n" . $section;
}

/**
 * Renders the Services hub Core30 map.
 *
 * @return string
 */
function lmhg_site_core_render_services_hub_design(): string {
	$cards = array();
	foreach ( lmhg_site_core_core30_service_families() as $family ) {
		$cards[] = sprintf(
			'<article class="lmhg-page-class-card lmhg-page-class-card--family"><h3><a href="%1$s">%2$s</a></h3><p>%3$s</p>%4$s</article>',
			esc_url( home_url( $family['url'] ) ),
			esc_html( $family['title'] ),
			esc_html( $family['description'] ),
			lmhg_site_core_render_link_list( $family['children'], 'lmhg-page-class-link-list' )
		);
	}

	return sprintf(
		'<section class="lmhg-page-class-guide lmhg-page-class-guide--services-hub" aria-labelledby="lmhg-services-map-title"><p class="lmhg-page-class-eyebrow">Core30 service map</p><h2 id="lmhg-services-map-title">Choose a service family, then compare related options.</h2><p class="lmhg-page-class-lead">The services hub should organize broad commercial service intent first, then expose related specialty pages as crawlable links. This keeps the page useful for visitors and aligned with the Core30 service-family model.</p><div class="lmhg-page-class-grid lmhg-page-class-grid--families">%s</div></section>',
		implode( '', $cards )
	);
}

/**
 * Renders broad service page enhancements.
 *
 * @param string $path Current path.
 * @return string
 */
function lmhg_site_core_render_service_page_design( string $path ): string {
	$family = lmhg_site_core_find_service_family( $path );
	if ( empty( $family ) ) {
		return '';
	}

	$children = ! empty( $family['children'] )
		? lmhg_site_core_render_link_list( $family['children'], 'lmhg-page-class-link-list lmhg-page-class-link-list--chips' )
		: '<p class="lmhg-page-class-note">This service family currently owns its own Core30 intent without direct child pages.</p>';

	return sprintf(
		'<section class="lmhg-page-class-guide lmhg-page-class-guide--service-page" aria-labelledby="lmhg-service-map-title"><p class="lmhg-page-class-eyebrow">Service family</p><h2 id="lmhg-service-map-title">%1$s pathway</h2><div class="lmhg-page-class-split"><div><p class="lmhg-page-class-lead">%2$s</p><p>Use this page to understand the broad service family, then compare the more specific pages that may fit the visitor&apos;s situation.</p></div><div class="lmhg-page-class-panel"><h3>Related options</h3>%3$s</div></div></section>',
		esc_html( $family['title'] ),
		esc_html( $family['description'] ),
		$children
	);
}

/**
 * Renders the specialties hub grouped by Core30 service family.
 *
 * @return string
 */
function lmhg_site_core_render_specialties_hub_design(): string {
	$groups = array();
	foreach ( lmhg_site_core_core30_service_families() as $family ) {
		if ( empty( $family['children'] ) ) {
			continue;
		}
		$groups[] = sprintf(
			'<article class="lmhg-page-class-card"><h3><a href="%1$s">%2$s</a></h3>%3$s</article>',
			esc_url( home_url( $family['url'] ) ),
			esc_html( $family['title'] ),
			lmhg_site_core_render_link_list( $family['children'], 'lmhg-page-class-link-list' )
		);
	}

	return sprintf(
		'<section class="lmhg-page-class-guide lmhg-page-class-guide--specialties-hub" aria-labelledby="lmhg-specialties-map-title"><p class="lmhg-page-class-eyebrow">Specialty map</p><h2 id="lmhg-specialties-map-title">Specific pages should stay connected to their parent service family.</h2><p class="lmhg-page-class-lead">Specialty pages are narrower than service-family pages. This hub should help visitors compare specific modalities, concerns, court-service paths, and community-service options without fragmenting service intent.</p><div class="lmhg-page-class-grid">%s</div></section>',
		implode( '', $groups )
	);
}

/**
 * Renders specialty page enhancements.
 *
 * @param string $path Current path.
 * @return string
 */
function lmhg_site_core_render_specialty_page_design( string $path ): string {
	$parents = lmhg_site_core_find_specialty_parents( $path );
	if ( empty( $parents ) ) {
		return '';
	}

	$parent_cards = array();
	foreach ( $parents as $parent ) {
		$siblings = array_values(
			array_filter(
				$parent['children'],
				static fn( array $child ): bool => $child['url'] !== $path
			)
		);

		$parent_cards[] = sprintf(
			'<article class="lmhg-page-class-card"><h3><a href="%1$s">%2$s</a></h3><p>%3$s</p>%4$s</article>',
			esc_url( home_url( $parent['url'] ) ),
			esc_html( $parent['title'] ),
			esc_html( $parent['description'] ),
			! empty( $siblings ) ? '<h4>Nearby options</h4>' . lmhg_site_core_render_link_list( $siblings, 'lmhg-page-class-link-list' ) : ''
		);
	}

	return sprintf(
		'<section class="lmhg-page-class-guide lmhg-page-class-guide--specialty-page" aria-labelledby="lmhg-specialty-context-title"><p class="lmhg-page-class-eyebrow">Specific service context</p><h2 id="lmhg-specialty-context-title">Compare this option inside its broader care family.</h2><p class="lmhg-page-class-lead">A specialty page should answer one focused need while making its parent service context easy to understand.</p><div class="lmhg-page-class-grid">%s</div></section>',
		implode( '', $parent_cards )
	);
}

/**
 * Renders FAQ hub enhancements.
 *
 * @return string
 */
function lmhg_site_core_render_faq_hub_design(): string {
	$groups = array(
		array(
			'title' => 'Starting care',
			'body'  => 'Questions about what LMHG is, how the process works, and how to choose a first step.',
			'links' => array(
				array( 'title' => 'About LMHG', 'url' => '/faq/about-lmhg/' ),
				array( 'title' => 'Our approach', 'url' => '/faq/our-approach/' ),
				array( 'title' => 'Contact the office', 'url' => '/contact-us/' ),
			),
		),
		array(
			'title' => 'Cost and access',
			'body'  => 'Questions about insurance, Medicaid, private pay, availability, and care settings.',
			'links' => array(
				array( 'title' => 'Therapy cost', 'url' => '/faq/cost/' ),
				array( 'title' => 'Insurance and Medicaid', 'url' => '/insurance/' ),
				array( 'title' => 'Locations and access', 'url' => '/locations/' ),
			),
		),
		array(
			'title' => 'Services and fit',
			'body'  => 'Questions that should link back to commercial service families instead of becoming isolated FAQ content.',
			'links' => array(
				array( 'title' => 'Services', 'url' => '/services/' ),
				array( 'title' => 'Specialties', 'url' => '/specialties/' ),
			),
		),
	);

	return sprintf(
		'<section class="lmhg-page-class-guide lmhg-page-class-guide--faq-hub" aria-labelledby="lmhg-faq-index-title"><p class="lmhg-page-class-eyebrow">FAQ index</p><h2 id="lmhg-faq-index-title">Group questions around decisions visitors are trying to make.</h2><div class="lmhg-page-class-grid">%s</div></section>',
		lmhg_site_core_render_plain_cards( $groups )
	);
}

/**
 * Renders FAQ detail enhancements.
 *
 * @param string $path Current path.
 * @return string
 */
function lmhg_site_core_render_faq_page_design( string $path ): string {
	$links = match ( $path ) {
		'/faq/cost/' => array(
			array( 'title' => 'Insurance and Medicaid', 'url' => '/insurance/' ),
			array( 'title' => 'Contact the office', 'url' => '/contact-us/' ),
			array( 'title' => 'Locations and access', 'url' => '/locations/' ),
		),
		'/faq/about-lmhg/' => array(
			array( 'title' => 'Services', 'url' => '/services/' ),
			array( 'title' => 'Team', 'url' => '/meet-the-team/' ),
			array( 'title' => 'Reviews', 'url' => '/reviews/' ),
		),
		default => array(
			array( 'title' => 'Services', 'url' => '/services/' ),
			array( 'title' => 'Specialties', 'url' => '/specialties/' ),
			array( 'title' => 'Contact', 'url' => '/contact-us/' ),
		),
	};

	return sprintf(
		'<section class="lmhg-page-class-guide lmhg-page-class-guide--faq-page" aria-labelledby="lmhg-faq-related-title"><p class="lmhg-page-class-eyebrow">Related next steps</p><h2 id="lmhg-faq-related-title">Use the answer, then move to the practical page that fits.</h2>%s</section>',
		lmhg_site_core_render_link_list( $links, 'lmhg-page-class-link-list lmhg-page-class-link-list--chips' )
	);
}

/**
 * Renders article hub enhancements.
 *
 * @return string
 */
function lmhg_site_core_render_article_hub_design(): string {
	$topics = array(
		array( 'title' => 'Therapy versus counseling', 'url' => '/articles/family-therapy-vs-individual-therapy/', 'body' => 'Comparison content should support the homepage and services hub.' ),
		array( 'title' => 'Starting therapy', 'url' => '/articles/what-to-expect-when-starting-therapy/', 'body' => 'Starter content should help visitors decide what to ask before scheduling.' ),
		array( 'title' => 'When support may help', 'url' => '/articles/top-5-signs-its-time-to-seek-therapy/', 'body' => 'Informational content should route back to service-family pages.' ),
	);

	return sprintf(
		'<section class="lmhg-page-class-guide lmhg-page-class-guide--article-hub" aria-labelledby="lmhg-article-hub-title"><p class="lmhg-page-class-eyebrow">Article strategy</p><h2 id="lmhg-article-hub-title">Articles should absorb informational demand and point back to care pages.</h2><p class="lmhg-page-class-lead">Use article pages for questions, comparisons, and modifiers that would otherwise create duplicate commercial pages.</p><div class="lmhg-page-class-grid">%s</div></section>',
		lmhg_site_core_render_plain_cards( $topics )
	);
}

/**
 * Renders article detail enhancements.
 *
 * @param string $path Current path.
 * @return string
 */
function lmhg_site_core_render_article_page_design( string $path ): string {
	$links = lmhg_site_core_article_related_links( $path );

	return sprintf(
		'<section class="lmhg-page-class-guide lmhg-page-class-guide--article-page" aria-labelledby="lmhg-article-related-title"><p class="lmhg-page-class-eyebrow">Related care pages</p><h2 id="lmhg-article-related-title">Use this article as context, then compare care options.</h2>%s<p class="lmhg-page-class-note">Articles are educational support content and do not replace clinical care or emergency support.</p></section>',
		lmhg_site_core_render_link_list( $links, 'lmhg-page-class-link-list lmhg-page-class-link-list--chips' )
	);
}

/**
 * Renders location and access enhancements.
 *
 * @param string $path Current path.
 * @return string
 */
function lmhg_site_core_render_location_access_design( string $path ): string {
	$cards = array(
		array( 'title' => 'Louisville office', 'url' => '/locations/in-person/', 'body' => 'The physical office is on Bardstown Road in Louisville.' ),
		array( 'title' => 'Telehealth', 'url' => '/locations/online/', 'body' => 'Online care may fit when travel, schedule, or distance are barriers.' ),
		array( 'title' => 'Community care', 'url' => '/locations/community/', 'body' => 'Some services support clients in community-based settings.' ),
		array( 'title' => 'In-home and school support', 'url' => '/locations/in-home/', 'body' => 'Some care paths may involve in-home or school-based support when clinically appropriate.' ),
	);

	if ( str_contains( $path, '/locations/school/' ) ) {
		$cards[3]['url'] = '/locations/school/';
	}

	return sprintf(
		'<section class="lmhg-page-class-guide lmhg-page-class-guide--location" aria-labelledby="lmhg-location-access-title"><p class="lmhg-page-class-eyebrow">Access and service area</p><h2 id="lmhg-location-access-title">One Louisville office, several care settings.</h2><p class="lmhg-page-class-lead">Location pages should clarify access without duplicating service-page content or implying separate clinic locations.</p><div class="lmhg-page-class-grid">%s</div></section>',
		lmhg_site_core_render_plain_cards( $cards )
	);
}

/**
 * Renders trust, contact, reviews, team, careers, and insurance enhancements.
 *
 * @param string $path Current path.
 * @return string
 */
function lmhg_site_core_render_trust_page_design( string $path ): string {
	$cards = match ( $path ) {
		'/contact-us/' => array(
			array( 'title' => 'Call the office', 'url' => 'tel:5024161416', 'body' => 'Use phone for availability, fit, insurance, and next-step questions.' ),
			array( 'title' => 'Start intake', 'url' => 'https://intakeq.com/new/g91Z8x/bjxuno', 'body' => 'Use the intake form when you are ready to share what is happening.' ),
			array( 'title' => 'Location details', 'url' => '/locations/in-person/', 'body' => 'Confirm office and access information before visiting.' ),
		),
		'/reviews/' => array(
			array( 'title' => 'Contact LMHG', 'url' => '/contact-us/', 'body' => 'Reviews should support trust, not replace a fit conversation.' ),
			array( 'title' => 'Services', 'url' => '/services/', 'body' => 'Compare service families after reading trust signals.' ),
		),
		'/meet-the-team/' => array(
			array( 'title' => 'Services', 'url' => '/services/', 'body' => 'Provider fit should connect back to service needs.' ),
			array( 'title' => 'Contact', 'url' => '/contact-us/', 'body' => 'Ask the office about provider availability and fit.' ),
		),
		'/careers/' => array(
			array( 'title' => 'About LMHG', 'url' => '/faq/about-lmhg/', 'body' => 'Careers content should stay connected to mission and service model.' ),
			array( 'title' => 'Contact', 'url' => '/contact-us/', 'body' => 'Use contact details for practical next steps.' ),
		),
		default => array(
			array( 'title' => 'Cost FAQ', 'url' => '/faq/cost/', 'body' => 'Coverage and eligibility need situation-specific confirmation.' ),
			array( 'title' => 'Contact', 'url' => '/contact-us/', 'body' => 'Ask the office to help check fit and practical next steps.' ),
		),
	};

	return sprintf(
		'<section class="lmhg-page-class-guide lmhg-page-class-guide--trust" aria-labelledby="lmhg-trust-next-title"><p class="lmhg-page-class-eyebrow">Trust and access</p><h2 id="lmhg-trust-next-title">Keep support pages practical and decision-focused.</h2><div class="lmhg-page-class-grid">%s</div></section>',
		lmhg_site_core_render_plain_cards( $cards )
	);
}

/**
 * Renders legal utility enhancements.
 *
 * @param string $path Current path.
 * @return string
 */
function lmhg_site_core_render_legal_utility_design( string $path ): string {
	unset( $path );
	$links = array(
		array( 'title' => 'Privacy policy', 'url' => '/privacy-policy/' ),
		array( 'title' => 'Terms of use', 'url' => '/terms-of-use/' ),
		array( 'title' => 'Compliance', 'url' => '/compliance/' ),
		array( 'title' => 'Contact', 'url' => '/contact-us/' ),
	);

	return sprintf(
		'<section class="lmhg-page-class-guide lmhg-page-class-guide--legal" aria-labelledby="lmhg-legal-support-title"><p class="lmhg-page-class-eyebrow">Legal and compliance support</p><h2 id="lmhg-legal-support-title">Keep legal pages plain, readable, and easy to connect to support.</h2>%s</section>',
		lmhg_site_core_render_link_list( $links, 'lmhg-page-class-link-list lmhg-page-class-link-list--chips' )
	);
}

/**
 * Core30 service-family data used for design sections.
 *
 * @return array<int,array<string,mixed>>
 */
function lmhg_site_core_core30_service_families(): array {
	return array(
		array(
			'title'       => 'Individual Counseling',
			'url'         => '/individual-counseling/',
			'description' => 'One-on-one care for adults and teens comparing therapy, counseling, anxiety, depression, stress, trauma, and life-change support.',
			'children'    => array(
				array( 'title' => 'Adult Counseling', 'url' => '/adult-counseling/' ),
				array( 'title' => 'Anxiety and Depression Therapy', 'url' => '/anxiety-depression-therapy/' ),
			),
		),
		array(
			'title'       => 'Child Counseling',
			'url'         => '/child-counseling/',
			'description' => 'Child and adolescent support for behavior, emotional regulation, family stress, school pressure, trauma, and developmentally appropriate care.',
			'children'    => array(
				array( 'title' => 'Teen Therapy', 'url' => '/adolescent-counseling/' ),
				array( 'title' => 'Play Therapy', 'url' => '/play-therapy/' ),
				array( 'title' => 'Child Behavioral Intervention', 'url' => '/child-behavioral-intervention/' ),
			),
		),
		array(
			'title'       => 'Family Therapy',
			'url'         => '/family-therapy/',
			'description' => 'Family support for communication, routines, parenting stress, attachment concerns, conflict, and major transitions.',
			'children'    => array(
				array( 'title' => 'Attachment Therapy', 'url' => '/attachment-therapy/' ),
				array( 'title' => 'Parenting Support', 'url' => '/parenting-support/' ),
			),
		),
		array(
			'title'       => 'Couples Counseling',
			'url'         => '/couples-counseling/',
			'description' => 'Relationship support for communication, recurring conflict, emotional distance, repair, and relationship decisions.',
			'children'    => array(
				array( 'title' => 'Relationship Counseling', 'url' => '/relationship-counseling/' ),
				array( 'title' => 'Couples Conflict Resolution', 'url' => '/couples-conflict-resolution/' ),
			),
		),
		array(
			'title'       => 'Court-Ordered Services',
			'url'         => '/court-ordered/',
			'description' => 'Court-involved family support for reunification, co-parenting, documentation questions, and stability during legal stress.',
			'children'    => array(
				array( 'title' => 'Family Reunification', 'url' => '/family-reunification/' ),
				array( 'title' => 'Co-Parenting', 'url' => '/co-parenting/' ),
			),
		),
		array(
			'title'       => 'Community-Based Services',
			'url'         => '/community-based-services/',
			'description' => 'Practical support for care coordination, community support, in-home needs, resources, and follow-through outside a traditional office visit.',
			'children'    => array(
				array( 'title' => 'Case Management', 'url' => '/case-management/' ),
				array( 'title' => 'Community Support', 'url' => '/community-support/' ),
				array( 'title' => 'In-Home Therapy', 'url' => '/therapy-in-your-home/' ),
			),
		),
		array(
			'title'       => 'Group Therapy',
			'url'         => '/group-therapy/',
			'description' => 'Structured therapy in a group setting for shared learning, skill practice, and guided support.',
			'children'    => array(),
		),
		array(
			'title'       => 'Trauma Therapy',
			'url'         => '/trauma-therapy/',
			'description' => 'Trauma-focused support for distressing memories, triggers, grief, anxiety, PTSD symptoms, and experiences that still feel active.',
			'children'    => array(
				array( 'title' => 'EMDR Therapy', 'url' => '/emdr-therapy/' ),
			),
		),
	);
}

/**
 * Finds a broad service family by URL.
 *
 * @param string $path Current path.
 * @return array<string,mixed>
 */
function lmhg_site_core_find_service_family( string $path ): array {
	foreach ( lmhg_site_core_core30_service_families() as $family ) {
		if ( $family['url'] === $path ) {
			return $family;
		}
	}

	return array();
}

/**
 * Finds parent service families for a specialty URL.
 *
 * @param string $path Current path.
 * @return array<int,array<string,mixed>>
 */
function lmhg_site_core_find_specialty_parents( string $path ): array {
	$parents = array();
	foreach ( lmhg_site_core_core30_service_families() as $family ) {
		foreach ( $family['children'] as $child ) {
			if ( $child['url'] === $path ) {
				$parents[] = $family;
				break;
			}
		}
	}

	return $parents;
}

/**
 * Returns article-to-service links for current known article pages.
 *
 * @param string $path Current path.
 * @return array<int,array<string,string>>
 */
function lmhg_site_core_article_related_links( string $path ): array {
	return match ( $path ) {
		'/articles/family-therapy-vs-individual-therapy/' => array(
			array( 'title' => 'Family Therapy', 'url' => '/family-therapy/' ),
			array( 'title' => 'Individual Counseling', 'url' => '/individual-counseling/' ),
		),
		'/articles/guide-to-individual-therapy/' => array(
			array( 'title' => 'Individual Counseling', 'url' => '/individual-counseling/' ),
			array( 'title' => 'Adult Counseling', 'url' => '/adult-counseling/' ),
		),
		'/articles/how-to-talk-to-your-loved-ones-about-going-to-therapy/' => array(
			array( 'title' => 'Services', 'url' => '/services/' ),
			array( 'title' => 'Family Therapy', 'url' => '/family-therapy/' ),
		),
		'/articles/top-5-signs-its-time-to-seek-therapy/' => array(
			array( 'title' => 'Services', 'url' => '/services/' ),
			array( 'title' => 'Contact', 'url' => '/contact-us/' ),
		),
		default => array(
			array( 'title' => 'Services', 'url' => '/services/' ),
			array( 'title' => 'FAQ', 'url' => '/faq/' ),
			array( 'title' => 'Contact', 'url' => '/contact-us/' ),
		),
	};
}

/**
 * Renders simple cards.
 *
 * @param array<int,array<string,string|array<int,array<string,string>>>> $cards Card data.
 * @return string
 */
function lmhg_site_core_render_plain_cards( array $cards ): string {
	$html = array();
	foreach ( $cards as $card ) {
		$title = (string) ( $card['title'] ?? '' );
		$url   = (string) ( $card['url'] ?? '' );
		$body  = (string) ( $card['body'] ?? '' );
		$links = isset( $card['links'] ) && is_array( $card['links'] ) ? $card['links'] : array();

		$title_html = '' !== $url
			? sprintf( '<a href="%1$s">%2$s</a>', esc_url( lmhg_site_core_page_class_url( $url ) ), esc_html( $title ) )
			: esc_html( $title );

		$html[] = sprintf(
			'<article class="lmhg-page-class-card"><h3>%1$s</h3>%2$s%3$s</article>',
			$title_html,
			'' !== $body ? '<p>' . esc_html( $body ) . '</p>' : '',
			! empty( $links ) ? lmhg_site_core_render_link_list( $links, 'lmhg-page-class-link-list' ) : ''
		);
	}

	return implode( '', $html );
}

/**
 * Renders a list of links.
 *
 * @param array<int,array<string,string>> $links Link data.
 * @param string                          $class List class.
 * @return string
 */
function lmhg_site_core_render_link_list( array $links, string $class ): string {
	if ( empty( $links ) ) {
		return '';
	}

	$items = array();
	foreach ( $links as $link ) {
		$title = trim( (string) ( $link['title'] ?? '' ) );
		$url   = trim( (string) ( $link['url'] ?? '' ) );
		if ( '' === $title || '' === $url ) {
			continue;
		}

		$items[] = sprintf(
			'<li><a href="%1$s">%2$s</a></li>',
			esc_url( lmhg_site_core_page_class_url( $url ) ),
			esc_html( $title )
		);
	}

	if ( empty( $items ) ) {
		return '';
	}

	return sprintf( '<ul class="%1$s">%2$s</ul>', esc_attr( $class ), implode( '', $items ) );
}

/**
 * Converts internal paths to site URLs while preserving explicit external URLs.
 *
 * @param string $url Raw URL or path.
 * @return string
 */
function lmhg_site_core_page_class_url( string $url ): string {
	if ( str_starts_with( $url, 'http://' ) || str_starts_with( $url, 'https://' ) || str_starts_with( $url, 'tel:' ) || str_starts_with( $url, 'mailto:' ) ) {
		return $url;
	}

	return home_url( $url );
}
