( function ( blocks, blockEditor, components, data, element, i18n, ServerSideRender ) {
	'use strict';

	const el = element.createElement;
	const __ = i18n.__;

	function registerRelationshipBlock( settings ) {
		blocks.registerBlockType( settings.name, {
			apiVersion: 3,
			title: settings.title,
			description: settings.description,
			icon: settings.icon,
			category: 'widgets',
			attributes: {
				heading: {
					type: 'string',
					default: settings.defaultHeading,
				},
			},
			supports: {
				html: false,
			},
			edit: function ( props ) {
				const postId = data.useSelect(
					function ( select ) {
						return select( 'core/editor' ).getCurrentPostId();
					},
					[]
				);
				return el(
					'div',
					blockEditor.useBlockProps(),
					el(
						blockEditor.InspectorControls,
						null,
						el(
							components.PanelBody,
							{ title: __( 'Display', 'lmhg-site-core' ), initialOpen: true },
							el( components.TextControl, {
								label: __( 'Heading', 'lmhg-site-core' ),
								value: props.attributes.heading,
								onChange: function ( value ) {
									props.setAttributes( { heading: value } );
								},
							} )
						)
					),
					el( ServerSideRender, {
						block: settings.name,
						attributes: props.attributes,
						urlQueryArgs: { post_id: postId },
					} )
				);
			},
			save: function () {
				return null;
			},
		} );
	}

	registerRelationshipBlock( {
		name: 'lmhg/related-pages',
		title: __( 'LMHG Related Pages', 'lmhg-site-core' ),
		description: __( 'Displays taxonomy-related pages using their current titles and descriptions.', 'lmhg-site-core' ),
		icon: 'admin-links',
		defaultHeading: 'Related Pages',
	} );

	registerRelationshipBlock( {
		name: 'lmhg/faqs',
		title: __( 'LMHG FAQs', 'lmhg-site-core' ),
		description: __( 'Displays the FAQ records assigned through the page\'s FAQ Set taxonomy.', 'lmhg-site-core' ),
		icon: 'editor-help',
		defaultHeading: 'Common Questions',
	} );
} )(
	window.wp.blocks,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.data,
	window.wp.element,
	window.wp.i18n,
	window.wp.serverSideRender
);
