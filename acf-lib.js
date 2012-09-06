/**
 * Javascript library for Alpha Contact Form Widget
 * 
 * @requires jQuery
 */

jQuery(function() {
	// unobtrusive javascript additions to the form
	
	/** 
	 * Adds a (usually hidden) validation message placeholder directly after all given form fields (except input type=button,
	 * ie. if s/o was so dumb and replaced the button with a input type=button tag)
	 */
	 
	jQuery('form.alpha-contact-form').prepend('<div class="alpha-contact-message"></div>');

	jQuery('form.alpha-contact-form .acf-field').each(function() {
		arrFieldClasses = this.getAttribute('class').split(' ');
		jQuery(this).after('<span class="alpha-validation-message '+arrFieldClasses[0]+'"></span>')
		
		
	})
	
	
	
	// form validation check
	jQuery('form.alpha-contact-form').submit(function() {
		var widgetID = jQuery(this).find('input.alpha-form-widget-id').attr('value'), widgetFormData = ''
		
		widgetFormData = jQuery('form.alpha-contact-form').serialize()
		widgetFormData += (widgetFormData != '' ? '&' : '') + 'action=acf_process_form'
		
		
		// remove missing HTML class
		jQuery('#' + widgetID + ' .acf-field').removeClass('missing')
		
		jQuery.ajax({
			type: 'POST',
			url: acf_ajax.ajaxurl,
			data: widgetFormData,
			success: function( result ) {
				
				//console.log( 'successfully sent' )
				
				console.log( result )
				
				if(result.missing) {
					// parse missing fields
					jQuery.each( result.missing, function(fieldName, errMessage) {
						jQuery( '#' + widgetID + ' .alpha-field-' + fieldName).addClass('missing')
						jQuery( '#' + widgetID + ' .alpha-validation-message.alpha-field-' + fieldName).html(errMessage)
						
						//console.log( '#' + widgetID + ' .alpha-field-' + fieldName )
					})
				} else if(result.success == true) {
					jQuery('#' + widgetID + ' .alpha-form-message').html(result.message)
				}
				
			}
			/*
			,error: function( result ) {
				// parse missing fields
				jQuery.each( result, function(fieldName, errMessage) {
					jQuery( '#' + widgetID + ' .' + fieldName).addClass('missing')
				})
				console.error( 'error result' )
				console.warning( result )
			}*/
		})
		
		return false
	});
	/*jQuery.post(
	 * 
		// see tip #1 for how we declare global javascript variables
		acf_ajax.ajaxurl,
		{
			// here we declare the parameters to send along with the request
			// this means the following action hooks will be fired:
			// wp_ajax_nopriv_myajax-submit and wp_ajax_myajax-submit
			action : 'acf_process_form',
	 
			// other parameters can be added along with "action"
			postID : MyAjax.postID
		},
		function( response ) {
			console.log( response );
		}
	)*/
});
