<?php
/*
Plugin Name: Alpha Contact Form Widget
Plugin URI: https://github.com/ginsterbusch/acf-widget/
Description: Contact form widget with unobtrusive AJAX support and custom translation options. Somewhat inspired by Easy Speak Widget Contact Form by <a href="http://www.luke-roberts.info">Luke Roberts</a>.
Version: 1.8.5
Author: Fabian Wolf
Author URI: http://usability-idealist.de/
Changelog: https://github.com/ginsterbusch/acf-widget/commits/master
*/


class alphaContactForm {
	var $pluginName = 'Alpha Contact Form',
		$pluginPrefix = 'alpha_contact_form_',
		$pluginVersion = '1.8.5',
		$pluginSettings = array(
			'enable_custom_translations' => false,
		);
	
	function __construct( $arrParams = array() ) {
		// get settings
		//$maybeSettings = get_option( $this->pluginPrefix . 'settings', null ); // single call for caching fun
		
		// join default AND custom settings
		$this->pluginSettings = $this->get_settings();
		
		//$this->pluginSettings = ( $maybeSettings != null ? $maybeSettings : $this->get_default_settings() );
		
		
		// add actions
		
		/**
		 * Note: is_admin() returns ALWAYS true when being called within admin-ajax.php!
		 * @see http://old.nabble.com/Ajax-requests,-admin-ajax.php-and-the-WP_ADMIN-constant-td33276374.html
		 */
		add_action('wp_ajax_nopriv_acf_process_form', array(&$this, 'acf_process_form') ); // 'guest' = regular user
		if(is_admin() ) {
			add_action('wp_ajax_acf_process_form', array(&$this, 'acf_process_form') ); // user is logged in
		}
		
		// register widget
		add_action( 'widgets_init', array(&$this, 'init_widget' ) );
		
		
		// add frontend AJAX handling
		if(!is_admin() ) {
			add_action('wp_enqueue_scripts', array(&$this, 'init_frontend_js' ) );	
		}
		
		// get custom translations (if enabled AND set)
		//if($this->pluginSettings['enable_custom_translatations'] == true) {
			
		$this->arrCustomTranslations = $this->get_custom_translations();
			
			/*get_option( $this->pluginPrefix . 'translations', array() );*/
		//}
	}


	public function get_settings( $strSection = 'settings' ) {
		$return = array();
		
		switch( $strSection ) {
			case 'settings':
			default:
				$maybeSettings = get_option( $this->pluginPrefix . $strSection, null );
				$defaultSettings = $this->get_default_settings();
				break;
			case 'custom_translations':
			case 'translations':
				$maybeSettings = $this->get_custom_translations();
				$defaultSettings = $this->get_custom_translations_file();
				break;
		}
		
		/**
		 * The main functionality is always the same 
		 * isset( $string ) => if $string == NULL return false; else return true 
		 * @see http://php.net/isset
		 * 
		 * so .. 0 is also a VALID value.
		 */
		
		if( $maybeSettings != null ) { // there are some custom settings
			foreach( $defaultSettings as $strSetting => $defaultValue ) {
				$return[$strSetting] = ( isset($maybeSettings[$strSetting]) ? $maybeSettings[$strSetting] : $defaultValue );
			}
		} else { // no settings found
			$return = $defaultSettings;
		}
		
		
		return $return;
	}

	/**
	 * Gets supplied in the admin page
	 */
	
	public function get_default_settings( $strSection = 'settings', $defaultValue = false ) {
		$return = $defaultValue;
		
		switch( $strSection ) {
			case 'settings':
				$return = array(
					'thanks_message' => '',
					'thanks_message_timeout' => 10000, /* in ms */
					'mail_method' => 'php', /* available methods: php, wp */
				);
			
				break;
			case 'custom_translations':
				$return = $this->get_custom_translations_file();
				break;
		}
		
		return $return;
	}

	
	public function get_custom_translations() {
		$return = array();
		
		$arrDefaultTranslations = $this->get_custom_translations_file();
		$arrTranslations = get_option($this->pluginPrefix .'translations', false);
		
		$return = ( !empty( $arrTranslations ) ? $arrTranslations : $arrDefaultTranslations );
		
		return $return;
	}

	
	public function get_custom_translations_file( $strSection = null ) {
		$return = false;
		
		$strCustomTransFile = 'acf-translations.json';
		$strTransFilePath = plugin_dir_path(__FILE__) . $strCustomTransFile;
	
		/**
		 * Note: use get_stylesheet_directory instead template_directory to avoid using the wrong theme. in case of a child theme, theme_directory will return the PARENT theme, whilest stylesheet_directory will return always return the currently active theme, which would in this case be the CHILD theme.
		 */
		
		if( file_exists( get_stylesheet_directory() . '/' . $strCustomTransFile ) != false && filesize( get_stylesheet_directory() . '/' . $strCustomTransFile ) > 50 ) {
			$strTransFilePath = get_stylesheet_directory() . '/'.  $strCustomTransFile;
			//echo '<h1>using override ..</h1>';
		}
		
		
		$strData = file_get_contents( $strTransFilePath );
	
		//print_r($strData);
	
		// strip out comments, line breaks and other useless stuff that might make json_decode angry

		
		$result = json_decode( $strData );
		
		// if decoded successfully, convert to array
		if( !empty( $result ) ) {
			//echo '<pre>'.print_r($result, true).'</pre>';
			
			$arrReturn = object_to_array( $result );
			
			//echo '<pre>'.print_r($arrReturn, true).'</pre>'; 
			
			if( !empty( $strSection) ) {
				$arrKeys = array_keys( $arrReturn );
				
				if( in_array( $strSection, $arrKeys ) != false) {
					$return = $arrReturn[ $strSection ];
				} elseif( sizeof( $arrKeys ) == 1 ) { // if there is just one section defined, this will be the default one
					
					$return = $arrReturn[$arrKeys[0]];
				}
			} else {
				$return = $arrReturn;
			}

		}
		
		
		
		return $return;
	}
	
	public function init_widget() {
		// register widget
		register_widget('alphaContactFormWidget');
	}
	
	public function init_frontend_js() {
		// adds ajax communication + simple form validation
		
		wp_enqueue_script($this->pluginPrefix . 'lib', plugin_dir_url(__FILE__) .'acf-lib.js', array('jquery') );
		
		// embed the javascript file that makes the AJAX request
		//wp_enqueue_script( 'my-ajax-request', plugin_dir_url( __FILE__ ) . 'js/ajax.js', array( 'jquery' ) );
 
		// declare the URL to the file that handles the AJAX request (wp-admin/admin-ajax.php)
		$arrJSCom = array( 
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
		);
		
		// also add interesting settings like the thanks message timeout and stuff .. :P
		if( !empty($this->pluginSettings['thanks_message_timeout']) ) {
			$arrJSCom['message_timeout'] = $this->pluginSettings['thanks_message_timeout'];
		}
		
		wp_localize_script( $this->pluginPrefix . 'lib', 'acf_ajax', $arrJSCom);
	}
	
	// parses the AJAX submitted form data
	
	public function acf_process_form() {
		// defaults
		$return = array( 'success' => false, 'error' => true, 'message' => 'Nothing to do' );
		
		
		// detect widget ID
		if(is_numeric( $_POST['id']) && intval($_POST['id']) > 0) {
			$widget_id = $_POST['id'];
			
		} elseif( stripos ( $_POST['id'], $this->pluginPrefix . 'widget-') !== false )  { // not numeric, but string is there
			//$return['debug']['stripos($_POST[id])'] = stripos ( $_POST['id'], $this->pluginPrefix . 'widget-');
			
			$x = explode($this->pluginPrefix . 'widget-', $_POST['id']);

			//$return['debug']['explode($_POST[id])'] = $x;

			if( is_numeric($x[1]) != false)  {
				$widget_id = trim($x[1]);
			}
			
		} 
		
	

		// fetch form fields based on widget ID
		
		if($widget_id > 0) {
			// fetch widget data
			$arrWidgets = get_option( 'widget_' . $this->pluginPrefix . 'widget' );
			
			$return['debug']['widget_data'] = $arrWidgets;
			
			if(array_key_exists($widget_id, $arrWidgets) != false) {
				$arrWidgetData = $arrWidgets[$widget_id];

				// check required fields: from, message
				
				// from
				if( !empty($_POST['from']) && filter_var( $_POST['from'], FILTER_VALIDATE_EMAIL ) != false) {
					$strFrom = filter_var( $_POST['from'], FILTER_SANITIZE_EMAIL );
				} else {
					$return['missing']['from'] = $this->translate( 'Sender e-mail address is not being or just incorrectly filled out.', 'missing_field_email' );
					$return['message'] = $this->translate( 'Sender e-mail address is not being or just incorrectly filled out.', 'missing_field_email' );
				}
				
				// message => alphanumeric chars, no html
				if( !empty($_POST['message']) && wp_kses( $_POST['message'], array() ) != '' ) {
					$strMessage = wp_kses( $_POST['message'], array() );
				} else {
					$return['missing']['message'] = $this->translate( 'Message area is not being filled out or contains invalid data. Note: HTML data is not allowed.', 'missing_field_message');
					$return['message'] = ( sizeof($return['missing']) > 1 ? $return['message'] . '<br />' : '') . $this->translate( 'Message area is not being filled out or contains invalid data. Note: HTML data is not allowed.', 'missing_field_message');;
				}
				
				// optional fields: name
				if( !empty($_POST['name']) && strip_tags( $_POST['name'] ) != '' ) {
					$strFrom = strip_tags($_POST['name']) .' <' . $strFrom . '>'; 
				}
				
				// check custom fields => basically handled as strings + strip_tags etc.
				if(!empty($arrWidgetData['custom_fields']) ) {
					$arrCustomFields = stripos( $arrWidgetData['custom_fields'], ';') !== false ? explode(';', $arrWidgetData['custom_fields'] ) : array( $arrWidgetData['custom_fields'] );
					
					// check single field
					foreach($arrCustomFields as $strCustomField) {
						if( !empty($_POST[$strCustomField]) && wp_kses($strCustomField, array() ) != '' ) {
							$arrAdditionalData[$strCustomField] = wp_kses($strCustomField, array() );
						}
					}
				}
				
				// send mail if all is whole and well
				if(!isset($return['missing']) ) {
					$strDivider = '-';
					for($n = 0; $n < 50; $n++) {
						$strDivider .= '-';
					}
					
					$strRecipient = $arrWidgetData['recipient'];
					
					if(!empty($arrWidgetData['send_cc']) ) {
						$arrAddHeader[] = 'Cc: ' . str_replace(';', ',', $arrWidgetData['send_cc']);
					}
					
					$arrAddHeader[] = 'From: ' . $strFrom;
					$arrAddHeader[] = 'X-Mailer: ' . $this->pluginName . '/' . $this->pluginVersion . ' (https://github.com/ginsterbusch/acf-widget/)';
					
					
					
					$strSubject = str_replace( array('%blog_title%','%blog_name%', '%sender%'), array( '%blog_name%', get_bloginfo('name'), (isset($strFromName) != false ? $strFromName : $strFrom) ), $arrWidgetData['subject'] );
				
					// regular custom fields
					$arrAdditionalData['IP'] = $_SERVER['REMOTE_ADDR'];
					$arrAdditionalData['Referrer URL'] = $_SERVER['HTTP_REFERER'];
					
					/**
					 * NOTE: Proper locale-aware date and time
					 */
					$arrAdditionalData['Date and time'] = date('Y-m-d H:i:s', current_time('timestamp') );
					//$arrAdditionalData['Date and time'] = date('Y-m-d H:i:s');
					
					// compile custom fields to the message
					foreach($arrAdditionalData as $strFieldName => $strFieldValue) {
						$strAdditionalData .= $strFieldName . ': ' . $strFieldValue . "\n";
					}
					
					
					$strMessage = $strMessage . "\n" . $strDivider . "\n\n" . $strAdditionalData . "\n\n" . $strDivider . "\n\n";
				
					// add correct encoding + mime info to the mail header
					/**
					 * @see http://www.php.net/manual/en/language.operators.array.php
					 */
					/*
					$arrAddHeader = $arrAddHeader + array(
						'MIME-Version: 1.0',
						'Content-Transfer-Encoding: 8bit',
						'Content-Type: text/plain; charset="' . get_bloginfo('charset') .'"',
					);*/ // do NOT override any possibly set mime + encoding info
					
					/**
					 * NOTE: Looks like using wp_mail solves the i18n character problems automagically ;)
					 */
					
					$result = $this->send_mail_wp( $strRecipient, $strSubject, $strMessage, $arrAddHeader );
					
					
					if($result != false) {
						$return['message'] = ( !empty($this->pluginSettings['thanks_message']) ? $this->pluginSettings['thanks_message'] : $this->translate('Message sent successfully', 'sending_success') );
						
						
						$return['success'] = true;
						
						// avoid security issues
						
						if(is_user_logged_in() != false ) {
							
							$return['debug'] = array(
								'recipient' => $strRecipient,
								'subject' => $strSubject,
								'message' => $strMessage,
								'headers' => $arrAddHeader,
							); 

						}
						unset($return['error']);
					} else {
						$return['message'] = $this->translate('Error occured while trying to send your message.', 'sending_error');
					}
				}
			

			} else { // sir, we've got a problem ..
				$return['message'] = $this->translate('AJAX communication error - contact form settings not found.', 'ajax_error_generic');
			}
		} else { // widget id not found or wrong
			$return['missing']['id'] = $this->translate('AJAX communication error - wrong ID supplied.', 'ajax_error_missing_id');
			$return['message'] = $this->translate('AJAX communication error - wrong ID supplied.', 'ajax_error_missing_id');
			
			// avoid security issues, eg. XSS
			
			if( is_user_logged_in() != false ) {
			
				$return['debug']['POST data'] = $_POST; // NO array( ...) because this array is already defined! (see widget_id processing!)
				$return['debug']['GET data'] = $_GET;
				$return['debug']['widget_id'] = $widget_id;
				$return['debug']['POST[id]'] = $_POST['id'];
			}
		}
		
		
		// compile response
		if( $this->is_ajax_call() != false ) {
			$return = json_encode($return);
			
			// header stuff
			header( 'Content-Type: application/json' );
			
		} else { // in server-side processing mode - or some idiot tried calling this file directly
			if(basename($_SERVER['PHP_SELF'] == 'acf-widget.php') != false) {
				unset($return);
				$return = '<p>Nope, you bloody moron. This file is NOT to be called directly!</p>';
			} else {
				global $_acf_form;
				$return = '';
				
				switch( $return['success'] ) {
					case true:
						$strReturnContent = '';
						break;
					case false:
						break;
					default: // some error occured					
				}
				
				if(isset($_acf_form) != false ) {
					// content, timeout, redirection-url
					$_acf_form->html_document( $strReturnContent, 30, $_SERVER['PHP_SELF'] );
				} else {
					$return = '<html><head><meta http-equiv="refresh" content="30; url='.$_SERVER['PHP_SELF'].'" /></head><body>'.$strReturnContent.'</body></html>';
				}

			}
		}
			
		// .. end of story
		exit( $return );
		
	}
	
	/**
	 * Wrapper for _send_mail( 'php', ... )
	 */
	public function send_mail_wp( $strRecipient, $strSubject = '[no subject]', $strMessage = '', $arrMailHeader = array(), $arrMailAttachments = array() ) {
		$return = $this->_send_mail('wp', $strRecipient, $strSubject, $strMessage, $arrMailHeader, $arrMailAttachments );
	
		return $return;
	}
	
	public function send_mail( $strRecipient, $strSubject = '[no subject]', $strMessage = '', $arrMailHeader = array(), $arrMailAttachments = array() ) {
		$return = $this->_send_mail('php', $strRecipient, $strSubject, $strMessage, $arrMailHeader, $arrMailAttachments );
		
		return $return;
	}
	
	
	/**
	 * Internal wrapper for sending mail with different mailers
	 * 
	 * @since 1.8.4
	 */
	
	protected function _send_mail( $strMethod = 'php', $strRecipient, $strSubject = '[no subject]', $strMessage = '', $arrMailHeader = array(), $arrMailAttachments = array() ) {
		$return = false;
		
		$strMethod = ( isset($this->pluginSettings['mailer']) != false ) ? $this->pluginSettings['mail_method'] : ( !empty($strMethod) ? $strMethod : 'php' );

		// check if headers are set
		if( empty( $arrMailHeader) != false) {
			$arrMailHeader = array('X-Mailer: PHP/' . phpversion() );
		}
		
		

		// which mail sending method to use
		switch( $strMethod ) {
			default:
			case 'php':
				// prepare mail header
				$strHeader = ( sizeof($arrMailHeader) > 1 ? implode("\r\n", $arrMailHeader) : $arrMailHeader[0] );
			
				$return = mail( $strRecipient, $strSubject, $strMessage, $strHeader );
				break;
		
			case 'wp':
				/**
				 * wp_mail does a bit of the work for as already, eg. automatically converting header lines to the right format, use the local server encoding and so on
				 * @see http://codex.wordpress.org/Function_Reference/wp_mail#Using_.24headers_To_Set_.22From:.22.2C_.22Cc:.22_and_.22Bcc:.22_Parameters
				 */
				
				
				$return = wp_mail( $strRecipient, $strSubject, $strMessage, $arrMailHeader, $arrMailAttachments );
				break;
		}
		
		return $return;
	}
		
	
	
	
	public function is_ajax_call() {
		$return = true;
		
		if(basename($_SERVER['PHP_SELF']) == 'acf-form.php' || !isset($_REQUEST['action'] ) ) {
			$return = false;
		}
		
		return $return;
	}
	
	/**
	 * Simple translation using either string-based or key-based translations - if available. 
	 * 
	 * @param string $text						Text to translate if possible. 
	 * @param [optional]string $associated_key	Use key-based (association) translation instead. 
	 */
	
	protected function translate( $strText = '', $strAlloKey = null ) {
		$return = $strText;
		
		if(!empty($strAlloKey) && isset($this->arrCustomTranslations) != false && array_key_exists( $strAlloKey, $this->arrCustomTranslations) != false ) {
			$return = $this->arrCustomTranslations[ $strAlloKey ];
		}
		
		return $return;
	}
}

$_acf = new alphaContactForm();

/**
 * Admin section
 */
 
if(is_admin() ) {
	//require_once( plugin_dir_path(__FILE__) . '/messagehandler.class.php' );
	require_once( plugin_dir_path(__FILE__) . '/acf-admin.class.php' );
	$_acf_admin = new alphaContactFormAdmin();
}



/**
 * Widget
 */


class alphaContactFormWidget extends WP_Widget {

	function __construct() {
		$widget_ops = array( 'description' => 'Contact form widget with optional AJAX support.' );
		$control_ops = array('width' => 450, 'height' => 500);
		parent::__construct( 'alpha_contact_form_widget', __('Contact Form Widget'), $widget_ops, $control_ops );
		
		// add required JS (yes, we could output the JS code directly, but that a) would fuck up any caching systems and b) also lead to ugly page loading pauses which we'd like to avoid, don't we? ;))
		//add_action('wp_enqueue_scripts', array(&$this, 'init_js') );
		
		// add transient api call
	}

	public function init_frontend_js() {
		//wp_localize_script( $this->id . ''
	}

	/*private function create_nounce() {
		return 
	}*/

	//public function init_js() {
		//wp_enqueue_script('_ui_ligawidget_js', plugins_url( basename(__FILE__, 'php') . 'js?id=' . $this->id_base , __FILE__ ), array('jquery') );
	//}

	function widget($args, $instance) {
		$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
		
		$custom_css = $instance['custom_css'];
		
		$template = new StdClass;
		$template->title = $title;
		$template->widget_id = $this->id;
		$template->id = $this->id;
		$template->widget_class = $this->widget_options['classname'];
		$template->form_url = plugins_url( __FILE__, 'acf-form.php' );
		
		
		if(!empty($instance['custom_fields']) && stripos($instance['custom_fields'], ';') !== false) {
			$arrCustomFields = explode(';', $instance['custom_fields']);
			
			foreach($arrCustomFields as $iCount => $strCustomField) {
				$strFieldName = (stripos($strCustomField, ':') !== false ? substr(0, stripos($strCustomField, ':')+1) : $strCustomField);
				$template->custom_fields[$iCount] = $strFieldName;
			}
		}

	// widget_output_main.start
	
		// hook before title output
		do_action('acf_widget_before_title');
	
		if ( !empty($title) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}
	
		do_action('acf_widget_after_title');
	
		echo $args['before_widget'];
		
		do_action('acf_widget_before_template');
		
		// check if there's a override template in the directory of the current theme
		if(file_exists(get_stylesheet_directory() . '/acf-form-template.php') != false) {
			include( get_stylesheet_directory() . '/acf-form-template.php' );
		} else {
		// if not, load the default stuff
		
			include(plugin_dir_path( __FILE__) . '/acf-form-template.php');
		}

		do_action('acf_widget_after_template');

		echo $args['after_widget'];	
		
		// widget_output_main.end

		// custom css.start

		do_action('acf_widget_before_custom_css');

		if( !empty( $custom_css ) ) { ?>
		<style type="text/css">
		<?php if(!empty($custom_css) ) { /** A duplicate one might ask? No, actually NOT. Snippet out of a much more advanced widget. So I left this in here just in case I need to add back a few other functions ;) */ ?>
			/* custom css for .<?php echo $this->classname; ?>, #<?php echo $this->id; ?> */
			<?php echo str_replace( array('[widget_id]', '[widget_class]'), array('#'.$this->id, '.'.$this->widget_options['classname']), $custom_css ); ?>
		<?php } ?>
		
		</style>
		<?php
		}
		// custom css.end
	
		do_action('acf_widget_after_custom_css');
	
	}

	function update( $new_instance, $old_instance ) {
		$instance['title'] = strip_tags( stripslashes($new_instance['title']) );
		
		$instance['recipient'] = (!empty($new_instance['recipient']) ? $new_instance['recipient'] : get_option('admin_email', '') );
		$instance['send_cc'] = $new_instance['send_cc'];
		$instance['custom_fields'] = $new_instance['custom_fields'];
		$instance['subject'] = (!empty($new_instance['subject']) ? $new_instance['subject'] : '');	
		
		$instance['custom_css'] = $new_instance['custom_css'];
		return $instance;
	}

	function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : '';
		$recipient = (!empty($instance['recipient']) ? $instance['recipient'] : get_option('admin_email', '') );
		$subject = (!empty($instance['subject']) ? $instance['subject'] : '[%blog_title%: Contact request]' );
		$send_cc = $instance['send_cc'];
		
		
		//Optionals
		$custom_fields = $instance['custom_fields']; // forgot this in the last commit :(		
		$custom_css = $instance['custom_css'];
	
	
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:') ?></label>
			<input type="text" class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $title; ?>" />
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id('recipient'); ?>"><?php _e('Recipient:') ?></label>
			<input type="text" class="widefat" id="<?php echo $this->get_field_id('recipient'); ?>" name="<?php echo $this->get_field_name('recipient'); ?>" value="<?php echo $recipient; ?>" /><br />
			<small>The recipient of the contact form. Required field. Will be automatically sent to the administrator e-mail address if left empty.</small>
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id('send_cc'); ?>"><?php _e('Additional recipients') ?></label>
			<input type="text" class="widefat" id="<?php echo $this->get_field_id('send_cc'); ?>" name="<?php echo $this->get_field_name('send_cc'); ?>" value="<?php echo $send_cc; ?>" /><br />
			<small>Additional recipients of the contact form. Seperate each e-mail address with a semicolon.</small>
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id('subject'); ?>"><?php _e('Subject:') ?></label>
			<input type="text" class="widefat" id="<?php echo $this->get_field_id('subject'); ?>" name="<?php echo $this->get_field_name('subject'); ?>" value="<?php echo $subject; ?>" /><br />
			<small>The subject line of the mail that gets send out after submitting the contact form. Required field; will be automatically filled if left empty.<br />
			Available placeholders, which are automatically replaced when the contact form is being sent: <strong>%blog_title%</strong> - the title of this blog (&quot;<?php bloginfo('name'); ?>&quot;), <strong>%sender%</strong> - the name or e-mail address of the sender</small>
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id('custom_fields'); ?>"><?php _e( 'Custom Fields:' ); ?></label> <textarea class="widefat" name="<?php echo $this->get_field_name('custom_fields'); ?>" id="<?php echo $this->get_field_id('custom_fields'); ?>"><?php echo $custom_fields; ?></textarea>
			<br />
			<small>Additional fields for the contact form. Allowed characters are letters, numbers and underscore. Underscores will be automatically replaced with a space, and first letter of each word will also be automatically uppercased when generating the field label. If you want to add your own labels, just use the syntax <code>label:field_name</code>, eg. <code>Your phone number:phone_number</code>. Seperate each field with a semicolon.</small> <!--Field type (for form validation) is being set by adding a simple double-colon after the field, eg. <code>post code:numbers</code>. Known field types are: <strong>numbers</strong>, <strong>text</strong> (default field type, may be left out)-->
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('custom_css'); ?>"><?php _e( 'Custom CSS:' ); ?></label> <textarea class="widefat" name="<?php echo $this->get_field_name('custom_css'); ?>" id="<?php echo $this->get_field_id('custom_css'); ?>"><?php echo $custom_css; ?></textarea>
			<br />
			<small><?php _e( 'Set custom CSS for this widget. [widget_id] and [widget_class] will be replaced accordingly.' ); ?></small>
		</p>
		<?php
	}
}


/**
 * Little helpful function library
 */


function _debug( $data, $strTitle = false, $useComments = false ) {
	$return = print_r( $data, true );
	
	$strBefore = '<div class="debug"><p class="debug-title"><strong>%s</strong></p><pre class="debug-data">';
	$strAfter = '</pre></div>';
	
	
	if( $useComments != false ) {
		$strBefore = "\n". '<!-- %s'; $strAfter = '-->' . "\n";
	}
	
	$return =  "\n\n" . sprintf( $strBefore, $strTitle ) . $return . $strAfter . "\n";
	
	echo $return;
}
 
function strip_whitespace( $text ) {
	$return = $text;
	
	// strip whitespace
	$return = str_replace( array("\r\n", "\n", "\t"), '', $return );
	
	return $return;
}

/**
 * @see http://www.if-not-true-then-false.com/2009/php-tip-convert-stdclass-object-to-multidimensional-array-and-convert-multidimensional-array-to-stdclass-object/
 */

function object_to_array($d) {
	if (is_object($d)) {
		// Gets the properties of the given object
		// with get_object_vars function
		$d = get_object_vars($d);
	}

	if (is_array($d)) {
		/*
		* Return array converted to object
		* Using __FUNCTION__ (Magic constant)
		* for recursive call
		*/
		return array_map(__FUNCTION__, $d);
	}
	else {
		// Return array
		return $d;
	}
}
