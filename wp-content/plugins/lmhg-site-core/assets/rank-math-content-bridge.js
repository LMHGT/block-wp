( function ( wp, settings ) {
	'use strict';

	if ( ! wp || ! wp.hooks || ! settings || ! settings.extraContent ) {
		return;
	}

	wp.hooks.addFilter(
		'rank_math_content',
		'lmhg-site-core/server-rendered-content',
		function ( content ) {
			return String( content || '' ) + '\n' + settings.extraContent;
		}
	);
}( window.wp, window.lmhgRankMathAnalysis ) );
