<?php
/**
 * SEO-informed draft FAQ question queue for service and specialty pages.
 *
 * @package LMHGSiteCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const LMHG_SITE_CORE_FAQ_QUEUE_OPTION  = 'lmhg_faq_question_queue_version';
const LMHG_SITE_CORE_FAQ_QUEUE_VERSION = '2026-07-12-seo-question-queue-v1';
const LMHG_SITE_CORE_FAQ_QUEUE_REPORT  = 'lmhg_faq_question_queue_report';
const LMHG_SITE_CORE_FAQ_QUEUE_META    = '_lmhg_faq_question_queue_key';

add_action( 'init', 'lmhg_site_core_run_faq_question_queue_migration', 46 );
add_action( 'init', 'lmhg_site_core_register_faq_question_queue_admin', 47 );

/** Registers FAQ-list helpers after the relationship post type is available. */
function lmhg_site_core_register_faq_question_queue_admin(): void {
	if ( ! defined( 'LMHG_SITE_CORE_FAQ_POST_TYPE' ) ) {
		return;
	}
	add_filter( 'manage_' . LMHG_SITE_CORE_FAQ_POST_TYPE . '_posts_columns', 'lmhg_site_core_faq_question_queue_columns' );
	add_action( 'manage_' . LMHG_SITE_CORE_FAQ_POST_TYPE . '_posts_custom_column', 'lmhg_site_core_faq_question_queue_column', 10, 2 );
	add_action( 'admin_notices', 'lmhg_site_core_faq_question_queue_notice' );
}

/** Adds editorial context to the FAQ list table. */
function lmhg_site_core_faq_question_queue_columns( array $columns ): array {
	$updated = array();
	foreach ( $columns as $key => $label ) {
		$updated[ $key ] = $label;
		if ( 'title' === $key ) {
			$updated['lmhg_faq_set']       = 'FAQ Set';
			$updated['lmhg_answer_status'] = 'Answer Status';
		}
	}
	return $updated;
}

/** Renders the FAQ Set and calculated answer-readiness columns. */
function lmhg_site_core_faq_question_queue_column( string $column, int $post_id ): void {
	if ( 'lmhg_faq_set' === $column ) {
		$terms = get_the_terms( $post_id, LMHG_SITE_CORE_FAQ_SET_TAXONOMY );
		if ( ! is_array( $terms ) || empty( $terms ) ) {
			echo '&mdash;';
			return;
		}
		echo esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) );
		return;
	}
	if ( 'lmhg_answer_status' !== $column ) {
		return;
	}

	$post    = get_post( $post_id );
	$answer  = $post instanceof WP_Post ? trim( wp_strip_all_tags( (string) $post->post_content ) ) : '';
	$status  = $post instanceof WP_Post ? (string) $post->post_status : '';
	if ( 'publish' === $status && '' !== $answer ) {
		echo '<strong>Published</strong>';
	} elseif ( '' !== $answer ) {
		echo '<strong>Ready to publish</strong>';
	} else {
		echo '<strong>Needs answer</strong>';
	}
}

/** Shows the remaining question-only editorial workload on the FAQ screen. */
function lmhg_site_core_faq_question_queue_notice(): void {
	if ( ! is_admin() || ! current_user_can( 'edit_posts' ) ) {
		return;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen instanceof WP_Screen || 'edit-' . LMHG_SITE_CORE_FAQ_POST_TYPE !== $screen->id ) {
		return;
	}

	$query = new WP_Query(
		array(
			'post_type'      => LMHG_SITE_CORE_FAQ_POST_TYPE,
			'post_status'    => 'draft',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => LMHG_SITE_CORE_FAQ_QUEUE_META,
			'meta_compare'   => 'EXISTS',
		)
	);
	if ( $query->found_posts > 0 ) {
		echo '<div class="notice notice-info"><p><strong>LMHG FAQ answer queue:</strong> ' . esc_html( (string) $query->found_posts ) . ' researched questions are waiting for practice-specific answers. Add an answer and publish each FAQ when approved.</p></div>';
	}
}

/**
 * Replaces generic service/specialty FAQs with three answer-ready draft questions.
 */
function lmhg_site_core_run_faq_question_queue_migration(): void {
	if (
		LMHG_SITE_CORE_FAQ_QUEUE_VERSION === (string) get_option( LMHG_SITE_CORE_FAQ_QUEUE_OPTION, '' )
		|| ! defined( 'LMHG_SITE_CORE_FAQ_POST_TYPE' )
		|| ! defined( 'LMHG_SITE_CORE_FAQ_SET_TAXONOMY' )
		|| ! post_type_exists( LMHG_SITE_CORE_FAQ_POST_TYPE )
		|| ! taxonomy_exists( LMHG_SITE_CORE_FAQ_SET_TAXONOMY )
	) {
		return;
	}

	$catalog = lmhg_site_core_faq_question_queue_catalog();
	$report  = array(
		'version'           => LMHG_SITE_CORE_FAQ_QUEUE_VERSION,
		'completed_at'      => '',
		'pages_expected'    => count( $catalog ),
		'pages_configured'  => 0,
		'questions_created' => 0,
		'questions_kept'    => 0,
		'faqs_trashed'      => 0,
		'shared_detached'   => 0,
		'fallbacks_cleared' => 0,
		'failures'          => array(),
	);

	if ( ! lmhg_site_core_faq_question_queue_catalog_is_valid( $catalog ) ) {
		$report['failures'][] = array( 'reason' => 'invalid_question_catalog' );
		update_option( LMHG_SITE_CORE_FAQ_QUEUE_REPORT, $report, false );
		return;
	}

	foreach ( $catalog as $path => $definition ) {
		$page = lmhg_site_core_faq_question_queue_page( $path );
		if ( ! $page instanceof WP_Post ) {
			$report['failures'][] = array( 'path' => $path, 'reason' => 'page_not_found' );
			continue;
		}

		$term_id = lmhg_site_core_faq_question_queue_term( $definition['set'], $definition['label'] );
		if ( $term_id <= 0 ) {
			$report['failures'][] = array( 'path' => $path, 'reason' => 'faq_set_failed' );
			continue;
		}

		$assigned = wp_set_object_terms( (int) $page->ID, array( $term_id ), LMHG_SITE_CORE_FAQ_SET_TAXONOMY, false );
		if ( is_wp_error( $assigned ) ) {
			$report['failures'][] = array( 'path' => $path, 'reason' => $assigned->get_error_code() );
			continue;
		}

		lmhg_site_core_faq_question_queue_archive_old_posts( $term_id, $report );
		$report['fallbacks_cleared'] += lmhg_site_core_faq_question_queue_clear_page_fallbacks( (int) $page->ID );

		$page_complete = true;
		foreach ( $definition['questions'] as $index => $question ) {
			$key    = $definition['set'] . ':' . ( $index + 1 );
			$faq_id = lmhg_site_core_faq_question_queue_find_post( $key );
			if ( $faq_id <= 0 ) {
				$faq_id = lmhg_site_core_faq_question_queue_create_post( $key, $path, $definition['label'], $question, $index + 1 );
				if ( $faq_id > 0 ) {
					++$report['questions_created'];
				}
			} else {
				++$report['questions_kept'];
			}

			if ( $faq_id <= 0 ) {
				$page_complete = false;
				$report['failures'][] = array( 'path' => $path, 'question' => $question, 'reason' => 'faq_post_failed' );
				continue;
			}

			$faq_terms = wp_set_object_terms( $faq_id, array( $term_id ), LMHG_SITE_CORE_FAQ_SET_TAXONOMY, false );
			if ( is_wp_error( $faq_terms ) ) {
				$page_complete = false;
				$report['failures'][] = array( 'path' => $path, 'question' => $question, 'reason' => $faq_terms->get_error_code() );
			}
		}

		if ( $page_complete && 3 === lmhg_site_core_faq_question_queue_count( $term_id ) ) {
			++$report['pages_configured'];
		} else {
			$report['failures'][] = array( 'path' => $path, 'reason' => 'question_count_mismatch' );
		}
	}

	if ( empty( $report['failures'] ) && $report['pages_expected'] === $report['pages_configured'] ) {
		$report['completed_at'] = gmdate( 'c' );
		update_option( LMHG_SITE_CORE_FAQ_QUEUE_OPTION, LMHG_SITE_CORE_FAQ_QUEUE_VERSION, false );
	}
	update_option( LMHG_SITE_CORE_FAQ_QUEUE_REPORT, $report, false );
}

/** Returns an imported page for one normalized catalog path. */
function lmhg_site_core_faq_question_queue_page( string $path ): ?WP_Post {
	if ( '/' === $path ) {
		$page = get_post( (int) get_option( 'page_on_front' ) );
		return $page instanceof WP_Post ? $page : null;
	}

	$page = get_page_by_path( trim( $path, '/' ), OBJECT, 'page' );
	return $page instanceof WP_Post ? $page : null;
}

/** Ensures the dedicated FAQ Set term exists. */
function lmhg_site_core_faq_question_queue_term( string $slug, string $label ): int {
	$term = get_term_by( 'slug', $slug, LMHG_SITE_CORE_FAQ_SET_TAXONOMY );
	if ( $term instanceof WP_Term ) {
		return (int) $term->term_id;
	}

	$created = wp_insert_term(
		$label,
		LMHG_SITE_CORE_FAQ_SET_TAXONOMY,
		array(
			'slug'        => $slug,
			'description' => 'SEO-informed FAQ question queue for ' . $label . '.',
		)
	);
	return is_wp_error( $created ) ? 0 : (int) ( $created['term_id'] ?? 0 );
}

/** Moves superseded records to Trash while protecting FAQs shared with another set. */
function lmhg_site_core_faq_question_queue_archive_old_posts( int $term_id, array &$report ): void {
	$posts = get_posts(
		array(
			'post_type'      => LMHG_SITE_CORE_FAQ_POST_TYPE,
			'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'tax_query'      => array(
				array(
					'taxonomy' => LMHG_SITE_CORE_FAQ_SET_TAXONOMY,
					'field'    => 'term_id',
					'terms'    => array( $term_id ),
				),
			),
		)
	);

	foreach ( $posts as $post ) {
		if ( ! $post instanceof WP_Post || '' !== (string) get_post_meta( (int) $post->ID, LMHG_SITE_CORE_FAQ_QUEUE_META, true ) ) {
			continue;
		}
		$term_ids = wp_get_object_terms( (int) $post->ID, LMHG_SITE_CORE_FAQ_SET_TAXONOMY, array( 'fields' => 'ids' ) );
		if ( ! is_wp_error( $term_ids ) && count( $term_ids ) > 1 ) {
			wp_remove_object_terms( (int) $post->ID, array( $term_id ), LMHG_SITE_CORE_FAQ_SET_TAXONOMY );
			++$report['shared_detached'];
			continue;
		}
		if ( wp_trash_post( (int) $post->ID ) instanceof WP_Post ) {
			++$report['faqs_trashed'];
		}
	}
}

/** Removes legacy embedded FAQ arrays so the FAQ taxonomy is the sole owner. */
function lmhg_site_core_faq_question_queue_clear_page_fallbacks( int $post_id ): int {
	$updated = 0;
	foreach ( array( '_lmhg_route_manifest_entry', '_lmhg_page_data_entry' ) as $meta_key ) {
		$raw  = (string) get_post_meta( $post_id, $meta_key, true );
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) || empty( $data['faqItems'] ) ) {
			continue;
		}
		$data['faqItems'] = array();
		if ( update_post_meta( $post_id, $meta_key, wp_slash( wp_json_encode( $data ) ) ) ) {
			++$updated;
		}
	}
	return $updated;
}

/** Finds a question record created by this versioned queue. */
function lmhg_site_core_faq_question_queue_find_post( string $key ): int {
	$posts = get_posts(
		array(
			'post_type'      => LMHG_SITE_CORE_FAQ_POST_TYPE,
			'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'posts_per_page' => 1,
			'no_found_rows'  => true,
			'meta_key'       => LMHG_SITE_CORE_FAQ_QUEUE_META,
			'meta_value'     => LMHG_SITE_CORE_FAQ_QUEUE_VERSION . ':' . $key,
		)
	);
	return ! empty( $posts ) && $posts[0] instanceof WP_Post ? (int) $posts[0]->ID : 0;
}

/** Creates one blank-answer draft FAQ for later practice-specific editing. */
function lmhg_site_core_faq_question_queue_create_post( string $key, string $path, string $label, string $question, int $order ): int {
	$faq_id = wp_insert_post(
		wp_slash(
			array(
				'post_type'    => LMHG_SITE_CORE_FAQ_POST_TYPE,
				'post_status'  => 'draft',
				'post_name'    => 'seo-question-' . sanitize_title( $key ),
				'post_title'   => $question,
				'post_excerpt' => 'Answer needed for ' . $label . '. Add an LMHG-specific answer, then publish.',
				'post_content' => '',
				'menu_order'   => $order * 10,
			)
		),
		true
	);
	if ( is_wp_error( $faq_id ) || (int) $faq_id <= 0 ) {
		return 0;
	}

	update_post_meta( (int) $faq_id, LMHG_SITE_CORE_FAQ_QUEUE_META, LMHG_SITE_CORE_FAQ_QUEUE_VERSION . ':' . $key );
	update_post_meta( (int) $faq_id, '_lmhg_faq_queue_page_path', $path );
	update_post_meta( (int) $faq_id, '_lmhg_faq_research_source', 'DataForSEO LLM Mentions and Louisville SERP' );
	update_post_meta( (int) $faq_id, '_lmhg_faq_answer_status', 'needs-answer' );
	return (int) $faq_id;
}

/** Counts current queue records assigned to a FAQ Set. */
function lmhg_site_core_faq_question_queue_count( int $term_id ): int {
	$query = new WP_Query(
		array(
			'post_type'      => LMHG_SITE_CORE_FAQ_POST_TYPE,
			'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_key'       => LMHG_SITE_CORE_FAQ_QUEUE_META,
			'meta_compare'   => 'EXISTS',
			'tax_query'      => array(
				array(
					'taxonomy' => LMHG_SITE_CORE_FAQ_SET_TAXONOMY,
					'field'    => 'term_id',
					'terms'    => array( $term_id ),
				),
			),
		)
	);
	return count( $query->posts );
}

/** Validates three non-empty, globally unique questions per page before writing. */
function lmhg_site_core_faq_question_queue_catalog_is_valid( array $catalog ): bool {
	$seen = array();
	foreach ( $catalog as $definition ) {
		if ( ! isset( $definition['set'], $definition['label'], $definition['questions'] ) || 3 !== count( $definition['questions'] ) ) {
			return false;
		}
		foreach ( $definition['questions'] as $question ) {
			$key = strtolower( trim( wp_strip_all_tags( (string) $question ) ) );
			if ( '' === $key || isset( $seen[ $key ] ) ) {
				return false;
			}
			$seen[ $key ] = true;
		}
	}
	return true;
}

/**
 * Returns the approved question-only editorial queue.
 *
 * @return array<string,array{set:string,label:string,questions:string[]}>
 */
function lmhg_site_core_faq_question_queue_catalog(): array {
	return array(
		'/services/' => array(
			'set' => 'services', 'label' => 'Counseling Services',
			'questions' => array( 'What types of counseling services does LMHG offer in Louisville?', 'How do I choose between individual, couples, family, child, or trauma therapy?', 'Does LMHG accept Kentucky Medicaid, commercial insurance, and private pay?' ),
		),
		'/individual-counseling/' => array(
			'set' => 'individual-counseling', 'label' => 'Individual Counseling',
			'questions' => array( 'Does insurance or Kentucky Medicaid cover individual counseling?', 'What is the main focus of individual counseling?', 'How often do people usually attend individual therapy?' ),
		),
		'/adult-counseling/' => array(
			'set' => 'adult-counseling', 'label' => 'Adult Counseling',
			'questions' => array( 'What concerns can adult counseling help with?', 'How do I choose an adult therapist in Louisville?', 'Can adult counseling address overlapping stress, grief, trauma, and anxiety?' ),
		),
		'/anxiety-depression-therapy/' => array(
			'set' => 'anxiety-depression-therapy', 'label' => 'Anxiety and Depression Therapy',
			'questions' => array( 'What type of therapy is used for anxiety and depression?', 'Can the same therapy address anxiety and depression together?', 'When should I seek therapy for anxiety or depression instead of waiting?' ),
		),
		'/child-counseling/' => array(
			'set' => 'child-counseling', 'label' => 'Child Therapy',
			'questions' => array( 'What are signs that a child may benefit from therapy?', 'How are child therapy, play therapy, and behavioral therapy different?', 'Does Kentucky Medicaid or commercial insurance cover child therapy?' ),
		),
		'/adolescent-counseling/' => array(
			'set' => 'adolescent-counseling', 'label' => 'Teen Therapy',
			'questions' => array( 'What are signs that a teenager may need therapy?', 'What type of therapy is usually appropriate for teenagers?', 'How do I find a teen therapist in Louisville who is a good fit?' ),
		),
		'/family-therapy/' => array(
			'set' => 'family-therapy', 'label' => 'Family Therapy',
			'questions' => array( 'What is the difference between family counseling and family therapy?', 'What kinds of family problems can family therapy help address?', 'Is family therapy covered by insurance or Kentucky Medicaid?' ),
		),
		'/couples-counseling/' => array(
			'set' => 'couples-counseling', 'label' => 'Couples Counseling',
			'questions' => array( 'When should a couple consider couples counseling?', 'What type of therapist should we choose for couples counseling?', 'Does insurance typically cover couples counseling?' ),
		),
		'/trauma-therapy/' => array(
			'set' => 'trauma-therapy', 'label' => 'Trauma Therapy',
			'questions' => array( 'What types of therapy are commonly used for trauma?', 'How do I know whether trauma therapy or EMDR may be appropriate?', 'Is trauma therapy covered by insurance or Kentucky Medicaid?' ),
		),
		'/group-therapy/' => array(
			'set' => 'group-therapy', 'label' => 'Group Therapy',
			'questions' => array( 'What is the difference between group therapy and a support group?', 'Who is, and is not, a good fit for group therapy?', 'What types of therapy groups are available in Louisville?' ),
		),
		'/court-ordered/' => array(
			'set' => 'court-ordered', 'label' => 'Court-Ordered Services',
			'questions' => array( 'How do I begin therapy after receiving a Kentucky Family Court order?', 'How is court-ordered therapy different from voluntary therapy?', 'Can insurance or Kentucky Medicaid cover court-ordered therapy?' ),
		),
		'/community-based-services/' => array(
			'set' => 'community-based-services', 'label' => 'Community-Based Mental Health Services',
			'questions' => array( 'What are community-based mental health services?', 'Can community-based services happen at home, school, or elsewhere in Louisville?', 'Can community-based support be combined with therapy and case management?' ),
		),
		'/specialties/' => array(
			'set' => 'specialties', 'label' => 'Specialized Therapy',
			'questions' => array( 'What is specialized therapy, and how is it different from general counseling?', 'How do I know whether EMDR, play therapy, attachment therapy, or another specialty fits?', 'Can LMHG coordinate multiple therapy services when needs overlap?' ),
		),
		'/emdr-therapy/' => array(
			'set' => 'emdr-therapy', 'label' => 'EMDR Therapy',
			'questions' => array( 'What is EMDR therapy, and how does it work?', 'What concerns can EMDR address besides PTSD?', 'How is EMDR different from traditional talk therapy or somatic therapy?' ),
		),
		'/play-therapy/' => array(
			'set' => 'play-therapy', 'label' => 'Play Therapy',
			'questions' => array( 'What qualifies a child for play therapy?', 'What training should a qualified play therapist have?', 'Does insurance or Kentucky Medicaid cover play therapy?' ),
		),
		'/attachment-therapy/' => array(
			'set' => 'attachment-therapy', 'label' => 'Parent-Child Attachment Therapy',
			'questions' => array( 'What signs suggest that a parent-child relationship may benefit from attachment therapy?', 'What happens during the first parent-child attachment therapy sessions?', 'Can attachment therapy help after separation, reunification, adoption, or another major family change?' ),
		),
		'/child-behavioral-intervention/' => array(
			'set' => 'child-behavioral-intervention', 'label' => 'Child Behavioral Therapy',
			'questions' => array( 'What type of therapy works best for behavioral problems in children?', 'How is child behavioral therapy different from play therapy?', 'What role do parents and schools have in child behavioral therapy?' ),
		),
		'/parenting-support/' => array(
			'set' => 'parenting-support', 'label' => 'Parenting Support',
			'questions' => array( 'When should a parent consider counseling or parenting support?', 'Can a parent attend parenting-support sessions without the child?', 'How is parenting support different from a parenting class or family therapy?' ),
		),
		'/conflict-resolution-counseling/' => array(
			'set' => 'conflict-resolution-counseling', 'label' => 'Conflict Resolution Counseling',
			'questions' => array( 'What problems can conflict-resolution counseling help address?', 'How is conflict-resolution counseling different from couples therapy, family therapy, or mediation?', 'Can counseling help with recurring arguments, escalation, or emotional shutdown?' ),
		),
		'/co-parenting/' => array(
			'set' => 'co-parenting', 'label' => 'Co-Parenting Services',
			'questions' => array( 'Is co-parenting counseling worth considering for high-conflict co-parents?', 'Can co-parenting counseling improve communication between separated parents?', 'How is co-parenting counseling different from couples counseling or mediation?' ),
		),
		'/family-reunification/' => array(
			'set' => 'family-reunification', 'label' => 'Family Reunification Services',
			'questions' => array( 'What is the primary goal of family reunification therapy?', 'Who usually participates in family reunification services?', 'How long does the family reunification process usually take?' ),
		),
		'/case-management/' => array(
			'set' => 'case-management', 'label' => 'Case Management',
			'questions' => array( 'What does a mental-health case manager do?', 'Who qualifies for mental-health case management?', 'Can a case manager help coordinate housing, benefits, medical appointments, or community resources?' ),
		),
		'/community-support/' => array(
			'set' => 'community-support', 'label' => 'Community Support Services',
			'questions' => array( 'What are examples of community support services?', 'How can community support help with everyday skills and stability?', 'How is community support different from case management?' ),
		),
	);
}
