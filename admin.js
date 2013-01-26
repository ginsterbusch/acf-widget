jQuery(function() {
	if( jQuery('#message .message-details') ) {
		jQuery('#message .message-content').append('<a class="message-details-link" href="#message">Show details</a>');
		
		jQuery('.message-details-link').click(function() {
			jQuery(this).parent().find('.message-details').toggle();
		});
	}

	jQuery('#checkbox-reset-translations').on('click', function() {
		var strNoteId = 'acf-admin-translation-reset-note';
		
		var strNote = ' <span id="'+strNoteId+'" style="color: #900;"><strong>Attention:</strong> This will entirely overwrite (ie. <strong>delete</strong>) any customtized translation!</span>';
		if(this.checked != false) {
			
			
			if( document.getElementById('acf-admin-translation-reset-note') ) {
				jQuery('#acf-admin-translation-reset-note').fadeIn();
			} else {
				jQuery('#' +this.id).parent().append( strNote );
				jQuery('#' + strNoteId ).fadeIn();
				var strFormerAction = jQuery('input[name=action]').val();
				
			}
			
			jQuery('input[name=action]').attr('value', 'save_reset_translation').data('former-action', strFormerAction);
		} else {
			if( document.getElementById('acf-admin-translation-reset-note') ) {
				jQuery('#acf-admin-translation-reset-note').fadeOut();
				jQuery('input[name=action]').val( jQuery('input[name=action]').data('former-action') );
			}
		}
	});
});
