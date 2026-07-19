<?php
/**
 * Publishes owner-supported answers from the SEO FAQ question queue.
 *
 * Existing published answers are never overwritten. Questions whose source
 * information was explicitly deferred remain drafts for later owner review.
 *
 * @package LMHGSiteCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const LMHG_SITE_CORE_OWNER_FAQ_OPTION  = 'lmhg_owner_faq_expansion_version';
const LMHG_SITE_CORE_OWNER_FAQ_VERSION = '2026-07-19-owner-answers-v1';
const LMHG_SITE_CORE_OWNER_FAQ_REPORT  = 'lmhg_owner_faq_expansion_report';

add_action( 'init', 'lmhg_site_core_run_owner_faq_expansion', 48 );

/**
 * Applies supported answers without replacing anything the owner published.
 */
function lmhg_site_core_run_owner_faq_expansion(): void {
	if (
		LMHG_SITE_CORE_OWNER_FAQ_VERSION === (string) get_option( LMHG_SITE_CORE_OWNER_FAQ_OPTION, '' )
		|| ! defined( 'LMHG_SITE_CORE_FAQ_POST_TYPE' )
		|| ! defined( 'LMHG_SITE_CORE_FAQ_QUEUE_META' )
	) {
		return;
	}

	$answers = lmhg_site_core_owner_faq_answer_catalog();
	$report  = array(
		'version'              => LMHG_SITE_CORE_OWNER_FAQ_VERSION,
		'completed_at'         => '',
		'answers_expected'     => count( $answers ),
		'answers_published'    => 0,
		'published_preserved'  => 0,
		'deferred_questions'   => lmhg_site_core_owner_faq_deferred_keys(),
		'failures'             => array(),
	);

	foreach ( $answers as $key => $entry ) {
		$posts = get_posts(
			array(
				'post_type'      => LMHG_SITE_CORE_FAQ_POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'posts_per_page' => 2,
				'no_found_rows'  => true,
				'meta_key'       => LMHG_SITE_CORE_FAQ_QUEUE_META,
				'meta_value'     => LMHG_SITE_CORE_FAQ_QUEUE_VERSION . ':' . $key,
			)
		);

		if ( 1 !== count( $posts ) || ! $posts[0] instanceof WP_Post ) {
			$report['failures'][] = array( 'key' => $key, 'reason' => 'queue_record_not_unique' );
			continue;
		}

		$post = $posts[0];
		if ( 'publish' === $post->post_status ) {
			++$report['published_preserved'];
			continue;
		}

		$answer  = trim( (string) $entry['answer'] );
		$content = "<!-- wp:paragraph -->\n<p>" . esc_html( $answer ) . "</p>\n<!-- /wp:paragraph -->";
		$result  = wp_update_post(
			wp_slash(
				array(
					'ID'           => (int) $post->ID,
					'post_title'   => (string) $entry['question'],
					'post_content' => $content,
					'post_excerpt' => $answer,
					'post_status'  => 'publish',
				)
			),
			true
		);

		if ( is_wp_error( $result ) || (int) $result !== (int) $post->ID ) {
			$report['failures'][] = array(
				'key'    => $key,
				'reason' => is_wp_error( $result ) ? $result->get_error_code() : 'post_update_failed',
			);
			continue;
		}

		update_post_meta( (int) $post->ID, '_lmhg_faq_answer_status', 'owner-approved' );
		update_post_meta( (int) $post->ID, '_lmhg_faq_answer_source', LMHG_SITE_CORE_OWNER_FAQ_VERSION );
		++$report['answers_published'];
	}

	if (
		empty( $report['failures'] )
		&& $report['answers_expected'] === $report['answers_published'] + $report['published_preserved']
	) {
		$report['completed_at'] = gmdate( 'c' );
		update_option( LMHG_SITE_CORE_OWNER_FAQ_OPTION, LMHG_SITE_CORE_OWNER_FAQ_VERSION, false );
	}

	update_option( LMHG_SITE_CORE_OWNER_FAQ_REPORT, $report, false );
}

/**
 * Queue records held for a later owner expansion.
 *
 * @return string[]
 */
function lmhg_site_core_owner_faq_deferred_keys(): array {
	return array(
		'anxiety-depression-therapy:3',
		'child-counseling:1',
		'adolescent-counseling:1',
		'group-therapy:1',
		'group-therapy:2',
		'group-therapy:3',
		'play-therapy:2',
	);
}

/**
 * Owner-supported question and answer copy.
 *
 * Answers intentionally stay short, direct, and conditional where coverage,
 * clinical fit, availability, or court requirements can vary.
 *
 * @return array<string,array{question:string,answer:string}>
 */
function lmhg_site_core_owner_faq_answer_catalog(): array {
	return array(
		'services:1' => array(
			'question' => 'What counseling and mental health services does LMHG offer in Louisville?',
			'answer'   => 'LMHG offers individual care for adults, children, and teens. We also offer couples, family, trauma, group, play, EMDR, attachment, and behavior care. Other services include Parenting Support, Conflict Resolution, Co-Parenting, Family Reunification, Case Management, Community Support, in-home care, and court-ordered services. A first call helps match clients with trained staff.',
		),
		'services:2' => array(
			'question' => 'How do I choose between individual, couples, family, child, or trauma therapy?',
			'answer'   => 'You do not have to choose alone. During the first call, LMHG learns what help you want. We then suggest a service and provider. The first clinical visit includes an evaluation. It may lead to changes in the starting care plan.',
		),
		'services:3' => array(
			'question' => 'Does LMHG accept Kentucky Medicaid, commercial insurance, and private pay?',
			'answer'   => 'Yes. LMHG accepts Kentucky Medicaid, commercial insurance, and private pay. Targeted Case Management and Community Support need Medicaid approval. People without Medicaid may pay for them on their own. We do not bill insurance for Co-Parenting or Family Reunification.',
		),
		'individual-counseling:1' => array(
			'question' => 'Does insurance or Kentucky Medicaid cover individual counseling?',
			'answer'   => 'Yes. LMHG accepts Kentucky Medicaid and commercial insurance for individual counseling. Coverage depends on the client\'s plan and any needed approval. Private pay is also an option. Medicaid members must use their benefits for services that Medicaid covers.',
		),
		'individual-counseling:2' => array(
			'question' => 'What is the main focus of individual counseling?',
			'answer'   => 'Individual counseling focuses on the client\'s concerns, diagnosis, and care goals. The first visit is an evaluation. It gathers useful facts and gives the client a starting plan. The therapist and client can change that plan as care continues.',
		),
		'individual-counseling:3' => array(
			'question' => 'How often do people usually attend individual therapy?',
			'answer'   => 'The client and therapist decide how often to meet. Visits are often more frequent at the start. They may become less frequent as goals are met. Most visits last about one hour. Some complex or intensive visits may take longer.',
		),
		'adult-counseling:1' => array(
			'question' => 'What concerns can adult counseling help with?',
			'answer'   => 'Adult counseling at LMHG can help with anxiety and depression. It can also address stress, trauma, grief, relationships, and life changes. These concerns may overlap. The first evaluation looks at the client\'s needs and suggests a starting plan.',
		),
		'adult-counseling:2' => array(
			'question' => 'How do I choose an adult therapist in Louisville?',
			'answer'   => 'LMHG uses the first phone call to find a good fit. We look at the client\'s concerns and the therapist\'s focus. We also ask about provider gender and schedule needs. Clients may request someone from the Meet the Team page.',
		),
		'adult-counseling:3' => array(
			'question' => 'Can adult counseling address overlapping stress, grief, trauma, and anxiety?',
			'answer'   => 'Yes. One care plan can address several related concerns. LMHG clinicians have training in trauma care. They may use cognitive behavioral therapy or related methods. This work can address thoughts, feelings, actions, and trauma symptoms together.',
		),
		'anxiety-depression-therapy:1' => array(
			'question' => 'What therapy approaches may LMHG use for anxiety and depression?',
			'answer'   => 'LMHG often uses cognitive behavioral therapy, or CBT, for adults with anxiety or depression. We may also use methods based on CBT. The exact method depends on the evaluation, diagnosis, care goals, and the provider\'s judgment.',
		),
		'anxiety-depression-therapy:2' => array(
			'question' => 'Can the same therapy address anxiety and depression together?',
			'answer'   => 'Yes. One care plan can address both anxiety and depression. The first evaluation looks at all of the client\'s concerns. It gives a diagnosis and starting advice. This helps the therapist choose an approach for the full picture.',
		),
		'child-counseling:2' => array(
			'question' => 'How are child therapy, play therapy, and behavioral therapy different?',
			'answer'   => 'Child therapy is a broad type of care. It is shaped for the child\'s age and growth. Play therapy uses play during care. Behavioral therapy works more on actions and skills. The first evaluation helps LMHG suggest where to start.',
		),
		'child-counseling:3' => array(
			'question' => 'Does Kentucky Medicaid or commercial insurance cover child therapy?',
			'answer'   => 'Yes. LMHG accepts Kentucky Medicaid and commercial insurance for child therapy. Coverage depends on the child\'s plan and any needed approval. The first visit is an evaluation. It supports the diagnosis and the first care plan.',
		),
		'adolescent-counseling:2' => array(
			'question' => 'What type of therapy is usually appropriate for teenagers?',
			'answer'   => 'No single type of therapy fits every teen. LMHG starts with an evaluation. The plan reflects the teen\'s needs, age, growth, diagnosis, and goals. At the start, the therapist explains privacy. They also explain how caregivers may take part.',
		),
		'adolescent-counseling:3' => array(
			'question' => 'How do I find a teen therapist in Louisville who is a good fit?',
			'answer'   => 'LMHG uses the first call to match a teen with a provider. We look at the teen\'s concerns and the provider\'s focus. Gender and schedule needs may also matter. Families may request someone from the Meet the Team page.',
		),
		'family-therapy:1' => array(
			'question' => 'What is the difference between family counseling and family therapy?',
			'answer'   => 'At LMHG, family counseling and family therapy usually mean the same clinical service. Co-Parenting, Family Reunification, and Parenting Support are different services. LMHG uses those names on their own. We do not call them therapy or counseling.',
		),
		'family-therapy:2' => array(
			'question' => 'What kinds of family problems can family therapy help address?',
			'answer'   => 'Family therapy may help with talks, routines, parenting stress, and attachment. It may also help with repeat conflict or a major change. The first evaluation helps decide who should take part. It also shows whether another service may be a better start.',
		),
		'family-therapy:3' => array(
			'question' => 'Is family therapy covered by insurance or Kentucky Medicaid?',
			'answer'   => 'Yes. LMHG accepts Kentucky Medicaid and commercial insurance for family therapy. Coverage depends on the plan and its clinical rules. Family Reunification and Co-Parenting are separate services. They are not therapy, so LMHG does not bill insurance for them.',
		),
		'couples-counseling:1' => array(
			'question' => 'When should a couple consider couples counseling?',
			'answer'   => 'Couples counseling may help with repeat fights or poor communication. It may also help with rising conflict or emotional shutdown. In some cases, one or both partners need individual work first. This can make joint counseling more useful.',
		),
		'couples-counseling:2' => array(
			'question' => 'How does LMHG help us choose a couples therapist?',
			'answer'   => 'During the first call, LMHG asks what the couple wants to address. We then match them with a clinician trained for those needs. Provider choice and schedule also matter. A couple may ask to work with a certain therapist.',
		),
		'couples-counseling:3' => array(
			'question' => 'Can LMHG bill insurance for couples counseling?',
			'answer'   => 'LMHG accepts Kentucky Medicaid and commercial insurance for couples counseling. The service must meet the plan\'s clinical and coverage rules. Co-Parenting is a different service. LMHG does not bill it to insurance because it does not treat a diagnosis.',
		),
		'trauma-therapy:1' => array(
			'question' => 'What trauma therapy approaches does LMHG offer?',
			'answer'   => 'LMHG offers talk therapy based on cognitive therapy for PTSD and related trauma concerns. Some clinicians also offer EMDR. Other trauma-informed methods may be used. The choice depends on the evaluation, goals, readiness, and provider training.',
		),
		'trauma-therapy:2' => array(
			'question' => 'How do I know whether trauma therapy or EMDR may be appropriate?',
			'answer'   => 'The first call and evaluation help find the right trauma care. The plan may use general trauma therapy, EMDR, or another method. EMDR needs a clinician trained in that method. It is not the best first step for every client.',
		),
		'trauma-therapy:3' => array(
			'question' => 'Is trauma therapy covered by insurance or Kentucky Medicaid?',
			'answer'   => 'Yes. LMHG accepts Kentucky Medicaid and commercial insurance for trauma therapy. Coverage depends on the plan and any needed approval. The first call also helps find a trauma-trained clinician. The clinician\'s focus and schedule must fit.',
		),
		'court-ordered:1' => array(
			'question' => 'How do I start court-ordered services with LMHG?',
			'answer'   => 'Contact LMHG for a first call. A court order that names LMHG does not promise acceptance. We must offer the required service and have the needed staff and resources. We also need all court orders, reasonable releases, and agreement to our service terms.',
		),
		'court-ordered:2' => array(
			'question' => 'What should I know before starting court-ordered services?',
			'answer'   => 'Court-ordered work may include therapy, Co-Parenting, or Family Reunification. Before work starts, LMHG explains its role and terms. We also explain records and court reports. A report may be a letter or testimony. LMHG does not give psychological tests or one-time forensic reviews.',
		),
		'court-ordered:3' => array(
			'question' => 'Which court-ordered services can insurance cover?',
			'answer'   => 'Insurance can cover court-ordered therapy because it treats a diagnosis. It does not cover LMHG\'s Co-Parenting or Family Reunification services. Those services do not treat a diagnosis. Therapy coverage still depends on the client\'s plan.',
		),
		'community-based-services:1' => array(
			'question' => 'What are community-based mental health services?',
			'answer'   => 'LMHG offers three main community-based mental health services. They are in-home therapy, Targeted Case Management, and Community Support. Based on the client\'s needs, they may offer clinical care or skill practice. They may also link resources or give support outside the office.',
		),
		'community-based-services:2' => array(
			'question' => 'Can community-based services happen at home, school, or elsewhere in Louisville?',
			'answer'   => 'Yes, when the setting fits the service and care plan. School rules and location may affect access. Therapist travel, schedule, and clinical fit also matter. LMHG checks the service and insurance plan before care starts.',
		),
		'community-based-services:3' => array(
			'question' => 'Can community-based support be combined with therapy and case management?',
			'answer'   => 'Yes. LMHG may combine therapy, Community Support, and Targeted Case Management. This can help when a client needs care from a team. Staff may talk with each other and use one wraparound plan. Medicaid must approve Community Support and Targeted Case Management.',
		),
		'specialties:1' => array(
			'question' => 'How does specialized therapy differ from general counseling at LMHG?',
			'answer'   => 'Specialized therapy uses a focused method or area of training to fit a client\'s needs. General counseling may use a wider range of methods. LMHG uses the first call and evaluation to check fit. We may suggest a specialty service or a more general starting point.',
		),
		'specialties:2' => array(
			'question' => 'How do I know whether EMDR, play therapy, attachment therapy, or another specialty fits?',
			'answer'   => 'LMHG first learns about the client\'s concerns, age, growth, goals, and preferences. The first call helps match the client with a provider. The first evaluation gives clinical advice on where to start. Not every clinician has training in every specialty.',
		),
		'specialties:3' => array(
			'question' => 'Can LMHG coordinate multiple therapy services when needs overlap?',
			'answer'   => 'Yes. A client or family may use more than one LMHG service. More than one therapist or community staff member may take part when needed. LMHG uses team talks and a wraparound plan. This is often helpful in complex or high-conflict cases.',
		),
		'emdr-therapy:1' => array(
			'question' => 'What is EMDR therapy, and how does it work?',
			'answer'   => 'EMDR is a planned form of trauma therapy. A trained clinician guides the client while working with a memory. The clinician also uses bilateral, or back-and-forth, input such as guided eye movements. The goal is to reduce distress and help the client process the memory.',
		),
		'emdr-therapy:2' => array(
			'question' => 'When might someone ask LMHG about EMDR?',
			'answer'   => 'A person may ask about EMDR after a hard event. The event may be linked with triggers, anxiety, grief, or other trauma symptoms. An evaluation looks at the client\'s concerns and readiness. It helps decide if EMDR is a good fit.',
		),
		'emdr-therapy:3' => array(
			'question' => 'How does LMHG choose between EMDR and trauma-focused talk therapy?',
			'answer'   => 'EMDR uses a set process to work with memories. It also uses bilateral, or back-and-forth, input. Trauma-focused talk therapy uses more talk and thought work. LMHG may use one or both methods. The choice depends on the evaluation, readiness, goals, and provider training.',
		),
		'play-therapy:1' => array(
			'question' => 'How does LMHG decide whether play therapy fits a child?',
			'answer'   => 'Every child starts with an evaluation. LMHG looks at the child\'s needs, age, growth, diagnosis, and care goals. We then suggest a place to start. It may be play therapy, behavior work, family work, or another type of care.',
		),
		'play-therapy:3' => array(
			'question' => 'Does insurance or Kentucky Medicaid cover play therapy?',
			'answer'   => 'Yes. LMHG accepts Kentucky Medicaid and commercial insurance for play therapy. Play therapy must be part of the child\'s clinical care plan. Coverage also depends on the insurance plan and any needed approval.',
		),
		'attachment-therapy:1' => array(
			'question' => 'What does parent-child attachment therapy mean at LMHG?',
			'answer'   => 'At LMHG, parent-child attachment therapy is a broad term. The therapist picks an approach that fits the family. It may include child-parent relationship therapy or family work focused on attachment. It may also use attachment-based play or parent coaching in family therapy.',
		),
		'attachment-therapy:2' => array(
			'question' => 'What happens before LMHG recommends parent-child attachment work?',
			'answer'   => 'The first visit is an evaluation. It gathers useful history, finds the family\'s needs, and gives starting advice. The therapist then picks an approach focused on attachment. They also decide how the parent, child, or other family members should take part.',
		),
		'attachment-therapy:3' => array(
			'question' => 'Can parent-child attachment therapy help after a major family change?',
			'answer'   => 'It may. Parent-child attachment work can fit some relationship concerns and major family changes. The approach depends on the child, parent, family history, and safety. Care goals also matter. An evaluation helps set the starting plan.',
		),
		'child-behavioral-intervention:1' => array(
			'question' => 'How does LMHG choose therapy for a child\'s behavioral problems?',
			'answer'   => 'LMHG starts with an evaluation. We do not assume one method is best. The clinician looks at the child\'s behavior, age, growth, diagnosis, family needs, and goals. They may suggest behavioral therapy, play therapy, family work, or another approach.',
		),
		'child-behavioral-intervention:2' => array(
			'question' => 'How is child behavioral therapy different from play therapy?',
			'answer'   => 'Behavioral therapy works on actions, coping skills, and practice. Play therapy uses play that fits the child\'s age and growth. Play is part of the child\'s care. A child may need one method or both. The evaluation and care goals guide that choice.',
		),
		'child-behavioral-intervention:3' => array(
			'question' => 'What role do parents and schools have in child behavioral therapy?',
			'answer'   => 'Parents or caregivers have an important role. Their role depends on the child\'s age, growth, and goals. Schools may take part with the right consent and releases. Care at school must follow school rules. It must also follow privacy rules and limits.',
		),
		'parenting-support:3' => array(
			'question' => 'How is parenting support different from a parenting class or family therapy?',
			'answer'   => 'Parenting Support is not therapy. It may offer parent-only skills, planning, and support. A parenting class usually follows a set course. Family therapy is clinical care. It may include several family members and address diagnosed needs through a care plan.',
		),
	);
}
