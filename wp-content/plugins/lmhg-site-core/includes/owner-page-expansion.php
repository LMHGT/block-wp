<?php
/**
 * Narrow page-copy expansions based on repeated owner-provided practice facts.
 *
 * @package LMHGSiteCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const LMHG_SITE_CORE_OWNER_PAGE_OPTION  = 'lmhg_owner_page_expansion_version';
const LMHG_SITE_CORE_OWNER_PAGE_VERSION = '2026-07-19-owner-copy-v1';
const LMHG_SITE_CORE_OWNER_PAGE_REPORT  = 'lmhg_owner_page_expansion_report';

add_action( 'init', 'lmhg_site_core_run_owner_page_expansion', 49 );

/**
 * Applies exact-match corrections and additive Gutenberg sections.
 *
 * If a page has diverged from the known source, the migration reports a
 * conflict and preserves the editor-authored content.
 */
function lmhg_site_core_run_owner_page_expansion(): void {
	if ( LMHG_SITE_CORE_OWNER_PAGE_VERSION === (string) get_option( LMHG_SITE_CORE_OWNER_PAGE_OPTION, '' ) ) {
		return;
	}

	$catalog = lmhg_site_core_owner_page_expansion_catalog();
	$report  = array(
		'version'       => LMHG_SITE_CORE_OWNER_PAGE_VERSION,
		'completed_at'  => '',
		'pages_expected' => count( $catalog ),
		'pages_updated'  => 0,
		'pages_current'  => 0,
		'failures'       => array(),
	);

	foreach ( $catalog as $path => $entry ) {
		$page = get_page_by_path( trim( $path, '/' ), OBJECT, 'page' );
		if ( ! $page instanceof WP_Post || 'publish' !== $page->post_status ) {
			$report['failures'][] = array( 'path' => $path, 'reason' => 'published_page_not_found' );
			continue;
		}

		$content = (string) $page->post_content;
		$changed = false;
		$valid   = true;

		foreach ( $entry['replacements'] as $replacement ) {
			$before = (string) $replacement['before'];
			$after  = (string) $replacement['after'];
			if ( str_contains( $content, $after ) ) {
				continue;
			}
			if ( 1 !== substr_count( $content, $before ) ) {
				$report['failures'][] = array( 'path' => $path, 'reason' => 'replacement_source_changed' );
				$valid = false;
				break;
			}
			$content = str_replace( $before, $after, $content );
			$changed = true;
		}

		if ( ! $valid ) {
			continue;
		}

		$addition = trim( (string) $entry['addition'] );
		$marker   = (string) $entry['marker'];
		if ( '' !== $addition && ! str_contains( $content, $marker ) ) {
			$anchor = '<!-- wp:lmhg/related-pages';
			if ( str_contains( $content, $anchor ) ) {
				$content = preg_replace( '/\n*<!-- wp:lmhg\/related-pages/', "\n\n" . $addition . "\n\n<!-- wp:lmhg/related-pages", $content, 1 );
			} else {
				$content = rtrim( $content ) . "\n\n" . $addition;
			}
			$changed = true;
		}

		if ( ! $changed ) {
			++$report['pages_current'];
			continue;
		}

		$result = wp_update_post(
			wp_slash(
				array(
					'ID'           => (int) $page->ID,
					'post_content' => $content,
				)
			),
			true
		);
		if ( is_wp_error( $result ) || (int) $result !== (int) $page->ID ) {
			$report['failures'][] = array(
				'path'   => $path,
				'reason' => is_wp_error( $result ) ? $result->get_error_code() : 'page_update_failed',
			);
			continue;
		}

		update_post_meta( (int) $page->ID, '_lmhg_owner_page_expansion', LMHG_SITE_CORE_OWNER_PAGE_VERSION );
		++$report['pages_updated'];
	}

	if (
		empty( $report['failures'] )
		&& $report['pages_expected'] === $report['pages_updated'] + $report['pages_current']
	) {
		$report['completed_at'] = gmdate( 'c' );
		update_option( LMHG_SITE_CORE_OWNER_PAGE_OPTION, LMHG_SITE_CORE_OWNER_PAGE_VERSION, false );
	}

	update_option( LMHG_SITE_CORE_OWNER_PAGE_REPORT, $report, false );
}

/**
 * Returns the small set of high-confidence page corrections and additions.
 *
 * @return array<string,array{marker:string,replacements:array<int,array{before:string,after:string}>,addition:string}>
 */
function lmhg_site_core_owner_page_expansion_catalog(): array {
	return array(
		'/services/' => array(
			'marker'       => 'lmhg-owner-expansion--matching',
			'replacements' => array(),
			'addition'     => <<<'HTML'
<!-- wp:group {"className":"wp2026-content-section lmhg-owner-guidance lmhg-owner-expansion--matching","layout":{"type":"constrained"}} -->
<div class="wp-block-group wp2026-content-section lmhg-owner-guidance lmhg-owner-expansion--matching"><!-- wp:heading {"level":2,"className":"wp2026-section-title"} -->
<h2 class="wp-block-heading wp2026-section-title">How LMHG Matches Care</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The first consultation usually happens by phone. Share only what you are comfortable sharing. The office may consider the main concern, specialty needs, provider preference, and schedule when looking for a match.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>When more than one provider fits, LMHG may offer options. You may also ask about someone listed on the <a href="/meet-the-team/">Meet the Team</a> page. The first clinical appointment includes an evaluation and initial treatment recommendations.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
HTML,
		),
		'/specialties/' => array(
			'marker'       => 'lmhg-owner-expansion--specialty-match',
			'replacements' => array(),
			'addition'     => <<<'HTML'
<!-- wp:group {"className":"wp2026-content-section lmhg-owner-guidance lmhg-owner-expansion--specialty-match","layout":{"type":"constrained"}} -->
<div class="wp-block-group wp2026-content-section lmhg-owner-guidance lmhg-owner-expansion--specialty-match"><!-- wp:heading {"level":2,"className":"wp2026-section-title"} -->
<h2 class="wp-block-heading wp2026-section-title">Matching A Specialty With A Provider</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>LMHG offers every service listed here, but not every clinician provides every specialty. Intake helps match the concern with a clinician whose training, schedule, and service availability fit the request.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
HTML,
		),
		'/insurance/' => array(
			'marker'       => 'lmhg-owner-expansion--coverage',
			'replacements' => array(),
			'addition'     => <<<'HTML'
<!-- wp:group {"className":"wp2026-content-section lmhg-owner-guidance lmhg-owner-expansion--coverage","layout":{"type":"constrained"}} -->
<div class="wp-block-group wp2026-content-section lmhg-owner-guidance lmhg-owner-expansion--coverage"><!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Coverage By Service</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>LMHG can bill Kentucky Medicaid or commercial insurance for therapy when the service and provider meet the plan's requirements. Private pay is also available. The office checks benefits and any required approvals before treatment.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Targeted Case Management and Community Support require Kentucky Medicaid prior authorization. People without Medicaid may pay privately for those services. A Medicaid member cannot use private pay to replace a missing authorization.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Court-ordered therapy may be covered because it includes diagnosis-based treatment. Co-Parenting and Family Reunification are separate services and are not billed to insurance.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
HTML,
		),
		'/court-ordered/' => array(
			'marker'       => 'lmhg-owner-expansion--court-intake',
			'replacements' => array(),
			'addition'     => <<<'HTML'
<!-- wp:group {"className":"wp2026-content-section lmhg-owner-guidance lmhg-owner-expansion--court-intake","layout":{"type":"constrained"}} -->
<div class="wp-block-group wp2026-content-section lmhg-owner-guidance lmhg-owner-expansion--court-intake"><!-- wp:heading {"level":2,"className":"wp2026-section-title"} -->
<h2 class="wp-block-heading wp2026-section-title">Before LMHG Accepts A Court Order</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>A court order that names LMHG or a staff member does not guarantee acceptance. LMHG first confirms that the requested service is within its scope, suitable resources are available, and the required parties agree to the service terms.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Send all current court orders for review. LMHG may also require releases that allow reasonable communication with the court and other involved parties.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Court-ordered work includes reporting to the court. Reporting may range from a letter to recommendations or testimony, depending on the order and service. LMHG explains the expected reporting before work starts.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Insurance may cover court-ordered therapy. LMHG does not bill insurance for Co-Parenting or Family Reunification. LMHG also does not provide psychological testing or one-time forensic evaluations.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
HTML,
		),
		'/articles/what-to-expect-when-starting-therapy/' => array(
			'marker'       => 'lmhg-owner-expansion--first-evaluation',
			'replacements' => array(),
			'addition'     => <<<'HTML'
<!-- wp:group {"className":"wp2026-content-section lmhg-owner-guidance lmhg-owner-expansion--first-evaluation","layout":{"type":"constrained"}} -->
<div class="wp-block-group wp2026-content-section lmhg-owner-guidance lmhg-owner-expansion--first-evaluation"><!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">The First Evaluation And Treatment Plan</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The first clinical appointment is an evaluation. The therapist gathers information about current concerns, history, strengths, and goals. The evaluation leads to initial recommendations for the treatment that should begin first and may include a diagnosis when appropriate.</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">How Often Sessions Happen</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>The client and therapist decide how often to meet. Sessions are often more frequent at first and may spread out as goals are met. Most appointments last about one hour. Some family or intensive trauma sessions may be longer.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
HTML,
		),
		'/child-counseling/' => array(
			'marker'       => 'lmhg-owner-expansion--caregiver-privacy',
			'replacements' => array(),
			'addition'     => <<<'HTML'
<!-- wp:group {"className":"wp2026-content-section lmhg-owner-guidance lmhg-owner-expansion--caregiver-privacy","layout":{"type":"constrained"}} -->
<div class="wp-block-group wp2026-content-section lmhg-owner-guidance lmhg-owner-expansion--caregiver-privacy"><!-- wp:heading {"level":2,"className":"wp2026-section-title"} -->
<h2 class="wp-block-heading wp2026-section-title">Caregiver Involvement And Privacy</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Parents and caregivers are an important part of a child's treatment. Their role changes with the child's age, development, and goals. The therapist explains privacy, caregiver involvement, and legal or safety limits at the start.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>If care involves a school, caregiver consent, releases, and school rules may guide communication. LMHG and the family review those requirements before school coordination begins.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
HTML,
		),
		'/trauma-therapy/' => array(
			'marker'       => 'lmhg-owner-expansion--trauma-approach',
			'replacements' => array(),
			'addition'     => <<<'HTML'
<!-- wp:group {"className":"wp2026-content-section lmhg-owner-guidance lmhg-owner-expansion--trauma-approach","layout":{"type":"constrained"}} -->
<div class="wp-block-group wp2026-content-section lmhg-owner-guidance lmhg-owner-expansion--trauma-approach"><!-- wp:heading {"level":2,"className":"wp2026-section-title"} -->
<h2 class="wp-block-heading wp2026-section-title">Trauma Care At LMHG</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>LMHG offers trauma-focused care. A plan may use cognitive therapy-based talk therapy for PTSD and related trauma concerns. Some clinicians also provide EMDR.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>The consultation and evaluation help match the client with an approach and provider. The choice depends on the client's needs, goals, readiness, schedule, and the clinician's training.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
HTML,
		),
		'/community-based-services/' => array(
			'marker'       => 'lmhg-owner-expansion--community-payment',
			'replacements' => array(
				array(
					'before' => '<p>Case Management and Community Support are most often paid for by Medicaid. Private pay may be an option for people with other plans. Many plans cover in-home therapy, but we must check. A fee may apply to school meetings if a plan will not pay for them.</p>',
					'after'  => '<p>Kentucky Medicaid requires prior authorization for Targeted Case Management and Community Support. People without Medicaid may pay privately for these services. A Medicaid member cannot use private pay to replace a missing authorization. In-home therapy may be billed to Medicaid or commercial insurance when the service fits the treatment plan and coverage requirements.</p>',
				),
			),
			'addition'     => <<<'HTML'
<!-- wp:group {"className":"wp2026-content-section lmhg-owner-guidance lmhg-owner-expansion--community-payment","layout":{"type":"constrained"}} -->
<div class="wp-block-group wp2026-content-section lmhg-owner-guidance lmhg-owner-expansion--community-payment"><!-- wp:heading {"level":2,"className":"wp2026-section-title"} -->
<h2 class="wp-block-heading wp2026-section-title">A Coordinated Community Team</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Therapy, Community Support, and Targeted Case Management may happen at the same time. LMHG uses a collaborative, wraparound approach when coordinated roles can make care more useful.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Services outside the office depend on the treatment plan, consent, setting rules, location, staff schedules, and whether a provider can travel.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
HTML,
		),
		'/case-management/' => array(
			'marker'       => 'Kentucky Medicaid requires prior authorization for Targeted Case Management.',
			'replacements' => array(
				array(
					'before' => '<p>Case Management is most often for Medicaid clients. Private pay may be an option in some cases. We must check the plan and service fit before work starts. Visit <a href="/insurance/">Costs and Insurance</a> to learn more.</p>',
					'after'  => '<p>Kentucky Medicaid requires prior authorization for Targeted Case Management. People without Medicaid may pay privately. A Medicaid member cannot use private pay to replace a missing authorization. LMHG must confirm eligibility and payment before work starts. Visit <a href="/insurance/">Costs and Insurance</a> to learn more.</p>',
				),
			),
			'addition'     => '',
		),
		'/community-support/' => array(
			'marker'       => 'Kentucky Medicaid requires prior authorization for Community Support.',
			'replacements' => array(
				array(
					'before' => '<p>Community support is mostly Medicaid-based. A private-pay or fee-for-service plan may be possible case by case. LMHG must check cost, fit, and service needs before care starts. The <a href="/insurance/">costs and insurance page</a> explains general payment options.</p>',
					'after'  => '<p>Kentucky Medicaid requires prior authorization for Community Support. People without Medicaid may pay privately. A Medicaid member cannot use private pay to replace a missing authorization. LMHG must confirm eligibility and payment before care starts. The <a href="/insurance/">costs and insurance page</a> explains general payment options.</p>',
				),
			),
			'addition'     => '',
		),
		'/faq/our-approach/' => array(
			'marker'       => 'lmhg-owner-expansion--wraparound',
			'replacements' => array(),
			'addition'     => <<<'HTML'
<!-- wp:group {"className":"wp2026-content-section lmhg-owner-guidance lmhg-owner-expansion--wraparound","layout":{"type":"constrained"}} -->
<div class="wp-block-group wp2026-content-section lmhg-owner-guidance lmhg-owner-expansion--wraparound"><!-- wp:heading {"level":2,"className":"wp2026-section-title"} -->
<h2 class="wp-block-heading wp2026-section-title">Coordinated Care When Needs Overlap</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Some clients and families need more than one therapist or service. LMHG may coordinate provider roles and discuss choices to present to the client when a collaborative, wraparound plan can improve care.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>This coordination is especially useful in complex, court-involved, or high-conflict situations. Each provider should have a clear role, and communication follows the client's consent and privacy requirements.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
HTML,
		),
		'/attachment-therapy/' => array(
			'marker'       => 'lmhg-owner-expansion--attachment-approaches',
			'replacements' => array(),
			'addition'     => <<<'HTML'
<!-- wp:group {"className":"wp2026-content-section lmhg-owner-guidance lmhg-owner-expansion--attachment-approaches","layout":{"type":"constrained"}} -->
<div class="wp-block-group wp2026-content-section lmhg-owner-guidance lmhg-owner-expansion--attachment-approaches"><!-- wp:heading {"level":2,"className":"wp2026-section-title"} -->
<h2 class="wp-block-heading wp2026-section-title">More Than One Attachment Approach</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Parent-child attachment therapy is not one fixed method at LMHG. Depending on the family, it may include Child-Parent Relationship Therapy, attachment-based play, attachment work within family therapy, or parent coaching.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>The first evaluation looks at the child's needs, family history, safety, current stress, and treatment goals. The therapist then recommends who should attend and which approach may fit.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
HTML,
		),
		'/parenting-support/' => array(
			'marker'       => 'Parenting Support is not therapy.',
			'replacements' => array(
				array(
					'before' => '<p class="wp2026-lead">Parenting support in Louisville, KY, helps parents work through hard moments at home. Parent counseling gives them a place to slow down, spot patterns, and choose clear next steps. Caregiver guidance may focus on behavior, big feelings, limits, routines, school stress, or family strain.</p>',
					'after'  => '<p class="wp2026-lead">Parenting support in Louisville, KY, helps parents work through hard moments at home. It gives caregivers a place to slow down, spot patterns, and choose clear next steps. Support may focus on behavior, big feelings, limits, routines, school stress, or family strain.</p>',
				),
				array(
					'before' => '<p>Parenting support is not a class. It does not follow one set lesson plan or promise a certificate. It is parent-only counseling and guidance based on what is happening in your home.</p>',
					'after'  => '<p>Parenting Support is not therapy. It is also not a class, fixed lesson plan, or certificate program. It provides parent-only skills, planning, and support based on what is happening at home.</p>',
				),
			),
			'addition'     => '',
		),
	);
}
