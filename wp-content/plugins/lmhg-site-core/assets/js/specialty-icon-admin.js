( function ( $ ) {
	'use strict';

	function setPreview( $field, attachment ) {
		var imageUrl = attachment && attachment.url ? attachment.url : '';

		if ( attachment && attachment.sizes ) {
			if ( attachment.sizes.thumbnail && attachment.sizes.thumbnail.url ) {
				imageUrl = attachment.sizes.thumbnail.url;
			} else if ( attachment.sizes.medium && attachment.sizes.medium.url ) {
				imageUrl = attachment.sizes.medium.url;
			}
		}

		$field.find( '.lmhg-specialty-icon-id' ).val( attachment && attachment.id ? attachment.id : '' );

		if ( imageUrl ) {
			$field.find( '.lmhg-specialty-icon-preview' ).empty().append(
				$( '<img>' ).attr( {
					alt: '',
					class: 'lmhg-specialty-icon-preview__image',
					src: imageUrl,
				} )
			);
			return;
		}

		$field.find( '.lmhg-specialty-icon-preview' ).html( '<span>No icon selected.</span>' );
	}

	$( document ).on( 'click', '.lmhg-specialty-icon-select', function ( event ) {
		event.preventDefault();

		if ( ! window.wp || ! window.wp.media ) {
			return;
		}

		var $field = $( this ).closest( '.lmhg-specialty-icon-field' );
		var frame = wp.media( {
			button: {
				text: 'Use this icon',
			},
			library: {
				type: 'image',
			},
			multiple: false,
			title: 'Choose LMHG page icon',
		} );

		frame.on( 'select', function () {
			var attachment = frame.state().get( 'selection' ).first().toJSON();
			setPreview( $field, attachment );
		} );

		frame.open();
	} );

	$( document ).on( 'click', '.lmhg-specialty-icon-remove', function ( event ) {
		event.preventDefault();
		setPreview( $( this ).closest( '.lmhg-specialty-icon-field' ), null );
	} );
}( jQuery ) );
