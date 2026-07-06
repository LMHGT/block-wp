( function ( blocks, blockEditor, components, element, i18n, ServerSideRender ) {
	const el = element.createElement;
	const __ = i18n.__;
	const InspectorControls = blockEditor.InspectorControls;
	const useBlockProps = blockEditor.useBlockProps;
	const PanelBody = components.PanelBody;
	const RangeControl = components.RangeControl;
	const SelectControl = components.SelectControl;
	const TextControl = components.TextControl;
	const TextareaControl = components.TextareaControl;

	blocks.registerBlockType( 'lmhg/review-showcase', {
		apiVersion: 3,
		title: __( 'LMHG Review Showcase', 'lmhg-site-core' ),
		description: __( 'Displays manually selected LMHG review records without using a shortcode.', 'lmhg-site-core' ),
		icon: 'star-filled',
		category: 'widgets',
		attributes: {
			heading: {
				type: 'string',
				default: 'Client feedback',
			},
			intro: {
				type: 'string',
				default: '',
			},
			count: {
				type: 'number',
				default: 3,
			},
			context: {
				type: 'string',
				default: 'default',
			},
		},
		edit: function ( props ) {
			const attributes = props.attributes;
			const blockProps = useBlockProps();

			return el(
				'div',
				blockProps,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Review display', 'lmhg-site-core' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Heading', 'lmhg-site-core' ),
							value: attributes.heading,
							onChange: function ( value ) {
								props.setAttributes( { heading: value } );
							},
						} ),
						el( TextareaControl, {
							label: __( 'Intro', 'lmhg-site-core' ),
							value: attributes.intro,
							onChange: function ( value ) {
								props.setAttributes( { intro: value } );
							},
						} ),
						el( RangeControl, {
							label: __( 'Maximum reviews', 'lmhg-site-core' ),
							value: attributes.count,
							min: 1,
							max: 12,
							onChange: function ( value ) {
								props.setAttributes( { count: value } );
							},
						} ),
						el( SelectControl, {
							label: __( 'Placement style', 'lmhg-site-core' ),
							value: attributes.context,
							options: [
								{ label: __( 'Default', 'lmhg-site-core' ), value: 'default' },
								{ label: __( 'Homepage band', 'lmhg-site-core' ), value: 'home' },
							],
							onChange: function ( value ) {
								props.setAttributes( { context: value } );
							},
						} )
					)
				),
				el( ServerSideRender, {
					block: 'lmhg/review-showcase',
					attributes: attributes,
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
	window.wp.components,
	window.wp.element,
	window.wp.i18n,
	window.wp.serverSideRender
);
