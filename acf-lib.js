 
/**
 * Dummy onsole
 * @see https://github.com/andyet/ConsoleDummy.js
 */
(function(b){function c(){}for(var d="assert,count,debug,dir,dirxml,error,exception,group,groupCollapsed,groupEnd,info,log,markTimeline,profile,profileEnd,time,timeEnd,trace,warn".split(","),a;a=d.pop();)b[a]=b[a]||c})(window.console=window.console||{});


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
					// remove contact message
					jQuery('#' + widgetID + ' .alpha-contact-message').filter(':visible').fadeOut();
					
					// parse missing fields
					jQuery.each( result.missing, function(fieldName, errMessage) {
						jQuery( '#' + widgetID + ' .alpha-field-' + fieldName).addClass('missing')
						jQuery( '#' + widgetID + ' .alpha-validation-message.alpha-field-' + fieldName).html(errMessage).show()
						
						//console.log( '#' + widgetID + ' .alpha-field-' + fieldName )
					})
				} else if(result.error == true) {
					// set error message
					
					jQuery('#' + widgetID + ' .alpha-contact-message').html(result.message).removeClass('sending-success').addClass('sending-error').fadeIn();
					
				} else if(result.success == true) {
					//console.log('successfully sent the message!');
					// remove error messages
					jQuery('#' + widgetID + ' input[type=text], #' + widgetID + ' textarea').removeClass('missing')
					jQuery('#' + widgetID + ' .alpha-validation-message').hide()
					
					// set success message
					jQuery('#' + widgetID + ' .alpha-contact-message').html(result.message).removeClass('sending-error').addClass('sending-success').fadeIn();
					
					// set timeout for success message if applicable
					if(typeof acf_ajax.message_timeout != 'undefined' && acf_ajax.message_timeout > 0) {
						window.setTimeout( function() { jQuery('#' + widgetID + ' .alpha-contact-message').fadeOut() }, acf_ajax.message_timeout );
					}
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
