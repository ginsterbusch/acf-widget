<?php
/*
Plugin Name: Alpha Contact Form Widget
Plugin URI: http://usability-idealist.de/
Description: Contact form widget with AJAX support, some simple anti-spam protection and custom translation options. Inspired by Easy Speak Widget Contact Form by <a href="http://www.luke-roberts.info">Luke Roberts</a>.
Version: 1.6
Author: Fabian Wolf
*/

class alphaContactForm {
	var $pluginName = 'Alpha Contact Form',
		$pluginPrefix = 'alpha_contact_form_',
		$pluginVersion = 1.6,
		$pluginSettings = array(
			'enable_custom_translations' => false,
		);
	
	function __construct() {
		// get settings
		$maybeSettings = get_option( $this->pluginPrefix . 'settings', null); // single call for caching fun
		if( $maybeSettings != null) {
			$this->pluginSettings = $maybeSettings;
		}
		
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
		if($this->pluginSettings['enable_custom_translatations'] == true) {
			$this->arrCustomTranslations = get_option( $this->pluginPrefix . 'custom_translations', array() );
		}
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
		wp_localize_script( $this->pluginPrefix . 'lib', 'acf_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
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
					$return['missing']['from'] = 'Sender e-mail address is not being or just incorrectly filled out.';
					$return['message'] = 'Sender e-mail address is not being or just incorrectly filled out.';
				}
				
				// message => alphanumeric chars, no html
				if( !empty($_POST['message']) && wp_kses( $_POST['message'], array() ) != '' ) {
					$strMessage = wp_kses( $_POST['message'], array() );
				} else {
					$return['missing']['message'] = $this->translate( 'Message area is not being filled out or contains invalid data. Note: HTML data is not allowed.', 'missing_field_message');
					$return['message'] = ( sizeof($return['missing']) > 1 ? $return['message'] . '<br />' : '') . 'Message area is not being filled out or contains invalid data. Note: HTML data is not allowed.';
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
					$arrAddHeader[] = 'Mailer: ' . $this->pluginName . '/' . $this->pluginVersion;
					
					$strSubject = str_replace( array('%blog_name%', '%sender%'), array( get_bloginfo('name'), (isset($strFromName) != false ? $strFromName : $strFrom) ), $arrWidgetData['subject'] );
				
					// regular custom fields
					$arrAdditionalData['IP'] = $_SERVER['HTTP_REMOTE_HOST'];
					$arrAdditionalData['Referrer URL'] = $_SERVER['HTTP_REFERER'];
					$arrAdditionalData['Date and time'] = date('Y-m-d H:i:s');
					
					// compile custom fields to the message
					foreach($arrAdditionalData as $strFieldName => $strFieldValue) {
						$strAdditionalData .= $strFieldName . ': ' . $strFieldValue . "\n";
					}
					
					
					$strMessage = $strMessage . "\n" . $strDivider . "\n\n" . $strAdditionalData . "\n\n" . $strDivider . "\n\n";
				
					
					$result = mail( $strRecipient, $strSubject, $strMessage, implode("\n", $arrAddHeader) );
					
					if($result != false) {
						$return['message'] = ( !empty($this->pluginSettings['thanks_message']) ? $this->pluginSettings['thanks_message'] : $this->translate('Message sent successfully', 'sending_success') );
						
						$return['success'] = true;
						
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
			$return['message'] = 'AJAX communication error - wrong ID supplied.';
			$return['debug']['POST data'] = $_POST; // NO array( ...) because this array is already defined! (see widget_id processing!)
			$return['debug']['GET data'] = $_GET;
			$return['debug']['widget_id'] = $widget_id;
			$return['debug']['POST[id]'] = $_POST['id'];
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
	
	public function is_ajax_call() {
		$return = true;
		
		if(basename($_SERVER['PHP_SELF']) == 'acf-form.php' || !isset($_REQUEST['action'] ) ) {
			$return = false;
		}
		
		return $return;
	}
	
	
	private function translate( $strText, $strAssoc = null ) {
		$return = $strText;
		if(!empty($strAssoc) && isset($this->arrCustomTranslations) != false && array_key_exists( $strAssoc, $this->arrCustomTranslations) != false ) {
			$return = $this->arrCustomTranslations[ $strAssoc ];
		}
		
		return $return;
	}
}

$_acf = new alphaContactForm();

/**
 * Admin section
 */
 
if(is_admin() ) {
	$_acf_admnin = new AlphaContactFormAdmin();
}

class AlphaContactFormAdmin {
	var $plugin; // plugin clone variable
	
	public function __construct() {
		global $_acf;
		$this->plugin = &$_acf;
		
		$this->pluginName = $this->plugin->pluginName;
		$this->pluginPrefix = $this->plugin->pluginPrefix;
		$this->pluginVersion = $this->plugin->pluginVersion;
		
		add_action('admin_menu', array(&$this, 'add_admin_pages') );
	}
	
		
	/**
	 * Gets supplied in the admin page
	 * Have to be activated seperatedly
	 */
	
	public function get_custom_translations_defaults() {
		$return = array();
		$strCustomTransFile = 'acf-custom-translations.php';
		$strIncludePath = plugin_dir_path(__FILE__) . $strCustomTransFile;
		
		if( file_exists(get_template_directory() . '/' . $strCustomTransFile) != false ) {
			$strIncludePath = get_template_directory() . '/'.  $strCustomTransFile;
		}
		
		//echo '<h1>includepath = ' . $strIncludePath. '</h1>';
		
		@include_once( $strIncludePath );
		
		//echo 'hui!';
		
		//print_r( $arrCustomTranslations );
		
		if(isset($arrCustomTranslations) != false ) {
			$return = $arrCustomTranslations;
		}
		
		return $return;
	}
	
	public function add_admin_pages() {
		add_menu_page('Alpha Contact Form Settings', 'Alpha Contact Form Widget', 'manage_options', 'acf_settings', array(&$this, 'admin_page_settings' ) );
		
		/**
		 * $menu_slug
		 * (string) (required) The slug name to refer to this menu by (should be unique for this menu). 
		 * If you want to NOT duplicate the parent menu item, you need to set the name of the $menu_slug exactly the same as the parent slug. 
		 */
		add_submenu_page('acf_settings', 'Alpha Contact Form Custom Translations', 'Custom Translations', 'manage_options', 'acf_translations', array(&$this, 'admin_page_translations' ) );
		
		//add_options_page('Alpha Contact Form Settings', 'Alpha Contact Form Widget', 'manage_options', 'acf_settings', array(&$this, 'admin_page_settings' ) );
	}
	
	public function admin_page_settings() {
		?>
		<div class="wrap">
			<h2><?php echo $this->pluginName . ' v' . $this->pluginVersion; ?> &raquo; Settings</h2>
		
			
			<form method="post" action="">
				<!--<p><label for=""></label> <input type="text" class="widefat" name="" id="" /></p>
				
				<p><input type="checkbox" class="checkbox" name="" id="" /> <label for=""></label></p>-->
				
				<p><label for="">Custom thanks message</label> <input type="text" name="" id="" value="" /><br />
				<small>Adjust the message which is being displayed after successfully sending the contents of the contact form.</small></p>
				
				<p><input type="checkbox" class="checkbox" name="" id="" value="" /> <label for="">Enable custom translations</label></p>
				
				<p class="form-controls"><button type="submit" class="button-primary button-submit"><?php _e('Save'); ?></button></p>
			</form>
			
			
		</div>
<?php
	}
	
	public function admin_page_translations() {
		$arrCustomTranslationsDefaults = $this->get_custom_translations_defaults();
		
		?>
		<div class="wrap">
			<h2><?php echo $this->pluginName . ' v' . $this->pluginVersion; ?> &raquo; Custom Translations</h2>
			
			<form method="post" action="">
				
				<table class="wp-list-table widefat fixed posts">
					<colgroup>
						<col width="15%" />
						<col width="35%" />
						<col width="45%" />
					</colgroup>
					<thead>
						<tr>
							<th class="manage-column sortable">Association name</th>
							<th class="manage-column">Original</th>
							<th class="manage-column">Translation</th>
						</tr>
					</thead>
					<tbody>
					<?php 
					if(!empty($arrCustomTranslationsDefaults) ) {
						$iRowCount = 0;
						foreach($arrCustomTranslationsDefaults as $strAssoc => $strText) { ?>
						<tr class="<?php echo (!($iRowCount % 2) ? 'alternate' : ''); $iRowCount++; ?>">
							<td><?php echo $strAssoc; ?></td>
							<td><?php echo $strText; ?></td>
							<td><textarea name="" rows="4" cols="40" resizable="resizable"><?php 
							if( isset($this->arrCustomTranslations[$strAssoc]) != false) {
								echo $this->arrCustomTranslations[$strAssoc];
							}
							?></textarea></td>
						</tr>
					<?php }
					} else { ?>
						<tr>
							<td colspan="3">No data found.</td>
						</tr>
					<?php } ?>
					</tbody>
				</table>
				
				<p class="form-controls"><button type="submit" class="button-primary button-submit"><?php _e('Save Changes'); ?></button></p>
			</form>
		</div>
		<?php
	}
	
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
		if ( !empty($title) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}
	
		echo $args['before_widget'];
		
		// check if there's a override template in the directory of the current theme
		if(file_exists(get_stylesheet_directory() . '/acf-form-template.php') != false) {
			include( get_stylesheet_directory() . '/acf-form-template.php' );
		} else {
		// if not, load the default stuff
		
			include(plugin_dir_path( __FILE__) . '/acf-form-template.php');
		}

		echo $args['after_widget'];	
		
		// widget_output_main.end

		// custom css.start

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
		$subject = (!empty($instance['subject']) ? $instance['subject'] : '[%blog_title: Contact request]' );
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

