( function ( blocks, blockEditor, element, i18n, ServerSideRender ) {
	'use strict';
	var el = element.createElement;

	blocks.registerBlockType( 'lmhg/reach-out-button', {
		apiVersion: 3,
		title: i18n.__( 'Global Reach Out Button', 'lmhg-site-core' ),
		description: i18n.__( 'Uses the sitewide label and destination from LMHG Site settings.', 'lmhg-site-core' ),
		category: 'widgets',
		icon: 'admin-links',
		supports: {
			html: false,
			multiple: true,
			reusable: true,
		},
		edit: function ( props ) {
			return el(
				'div',
				blockEditor.useBlockProps(),
				el( ServerSideRender, {
					block: 'lmhg/reach-out-button',
					attributes: props.attributes,
				} )
			);
		},
		save: function () {
			return null;
		},
	} );
} )(
	window.wp.blocks,
	window.wp.blockEditor,
	window.wp.element,
	window.wp.i18n,
	window.wp.serverSideRender
);
