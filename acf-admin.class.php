<?php
/**
 * ACF Widget - Admin section
 */
//error_reporting(E_ALL);

class alphaContactFormAdmin extends alphaContactForm {

		
	
	public function __construct() {
		
		
		
		// init message handler
		if( class_exists('genericMessageHandler') != false) {
			$this->msg = new genericMessageHandler();
		}
		
		// actions
		add_action('admin_enqueue_scripts', array( &$this, 'add_admin_js') );
		add_action('admin_enqueue_scripts', array( &$this, 'add_admin_css') );
		
		add_action('admin_menu', array(&$this, 'add_admin_pages') );
		
		
	}
	
	
	public function add_admin_js() {
		wp_enqueue_script( $this->pluginPrefix . 'admin_js', plugins_url('admin.js', __FILE__), array('jquery'));
	}
	
	public function add_admin_css() {
		wp_enqueue_style( $this->pluginPrefix . 'admin_css', plugins_url('admin.css', __FILE__) );
	}
	
	
	/**
	 * Simple message output
	 */
	protected function _show_message( $msg = array() ) {
		if( !empty( $msg ) ) {
			$strReturn = '<div id="message" class="message message-content ' . $msg['type'] . '">'.wpautop($msg['msg']);
			
			if( !empty($msg['result']) ) {
				$strReturn .= '<pre class="message-details" style="display:none">'.print_r($msg['result'], true).'</pre>';
			}
			
			$strReturn .= '</div>';
			
			echo $strReturn;
		}
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
	
	
	protected function save_settings( $strSection = 'settings' ) {
		$return = array('type' => 'error', 'msg' => 'Nothing to do.');
		$bUpdateSettings = false; $arrSettings = array();
		
		switch( $strSection ) {
			case 'settings':
				// hardcoded settings
				$arrSettings = get_option( $this->pluginPrefix . $strSection, $this->get_default_settings() );
				
				// custom thanks message
				if( !empty($_POST['thanks_message'] ) && $_POST['thanks_message'] != $arrSettings['thanks_message'] ) {
					$arrSettings['thanks_message'] = $_POST['thanks_message'];
					$bUpdateSettings = true;
				}
				
				
				
				// thanks message timeout
				if( !empty($_POST['thanks_message_timeout'] ) && $_POST['thanks_message_timeout'] != $arrSettings['thanks_message_timeout'] ) {
					$arrSettings['thanks_message_timeout'] = $_POST['thanks_message_timeout'];
					$bUpdateSettings = true;
				}
				/*
				if( isset( $_POST['use_custom_translations'] ) != false && ( $_POST['use_custom_translations'] == '1' ? true : false ) != $arrSettings['use_custom_translations'] ) {
					$arrSettings['use_custom_translations'] = $_POST['use_custom_translations'] == '1' ? true : false;
					$bUpdateSettings = true;
				}*/
				
			
				
			
				break;
			case 'translations':
				// check if options are already set
				//$arrTranslations = get_option( $this->pluginPrefix . $strSection, _acf_get_custom_translations_defaults() );
				
				$arrDefaultTranslations = $this->get_custom_translations_file('default');
				$arrTranslations = get_option( $this->pluginPrefix . $strSection, $arrDefaultTranslations  );
				
				
				if( !empty( $arrDefaultTranslations) ) {
					
					foreach( $arrDefaultTranslations as $strKeyword => $strText ) {
						if( isset( $_POST['translation'][$strKeyword] ) != false && trim($_POST['translation'][$strKeyword]) != $strText ) {
							$arrTranslations[$strKeyword] = trim($_POST['translation'][$strKeyword]);
						}
					}
				}
				
				// set update flag
				if( $arrTranslations != $arrDefaultTranslations ) {
					$bUpdateSettings = true;
					$arrSettings = $arrTranslations;
					
				}
				break;
			case 'reset_translations':
				// resets translations to their original state - theme-based overwrites will still overwrite the plugin defaults thou
				$arrDefaultTranslations = $this->get_custom_translations_file('default');
				
				
				
				if( !empty( $arrDefaultTranslations) ) {
					$bUpdateSettings = true;
					$strSection = 'translations';
					$arrSettings = $arrDefaultTranslations;
					
					$strMessage = 'Successfully reverted back to original translations.';
					
				}
				break;
				
		}
		
		if( $bUpdateSettings != false && !empty( $arrSettings ) ) {
			
			
			update_option( $this->pluginPrefix . $strSection, $arrSettings );
			$return = array(
				'type' => 'updated',
				'msg' => ( !isset($strMessage) ? 'Changes saved sucessfully.' : $strMessage ),
			);
		}
		
		return $return;
	}
	
	
	public function admin_page_settings() {
		if( $_POST['action'] == 'save_settings') {
		
			$msg = $this->save_settings();
		}
		
		
		$arrSettings = get_option( $this->pluginPrefix . 'settings', $this->get_default_settings() );	
		
		// display message
		if( isset($msg) != false ) {
			$this->_show_message( $msg );
		}

		?>
		<div class="wrap">
			<h2><?php echo $this->pluginName . ' v' . $this->pluginVersion; ?> &raquo; Settings</h2>
			
			<form method="post" action="">
				<input type="hidden" name="action" value="save_settings" />
				<!--<p><label for=""></label> <input type="text" class="widefat" name="" id="" /></p>
				
				<p><input type="checkbox" class="checkbox" name="" id="" /> <label for=""></label></p>-->
				
				<table class="form-table">
					<tbody>
						<tr>
						
							<th scope="row"><label for="field-thanks-message">Custom thanks message</label></th>
							<td>
								<textarea class="wp-editor-area" style="height: 100px; width: 500px" cols="40" name="thanks_message" id="field-thanks-message"><?php if( !empty($arrSettings['thanks_message']) ) { echo $arrSettings['thanks_message']; } ?></textarea><br />
								<span class="description">Adjust the message which is being displayed after successfully sending the contents of the contact form.</span>
							</td>
						</tr>
						
						<tr>
							<th scope="row"><label for="field-thanks-message-timeout">Display time duration of thanks message</label></th>
							<td>
								<input type="text" class="small-text" name="thanks_message_timeout" id="field-thanks-message-timeout" value="<?php if( !empty($arrSettings['thanks_message_timeout']) ) { echo $arrSettings['thanks_message_timeout']; } ?>" /> ms<br /> 
							<span class="description">For how many <a href="http://en.wikipedia.org/wiki/Millisecond">milliseconds</a> long shall the thanks message be displayed till it's being faded out (0 = no fade-out).</span>
							</td>
						</tr>
					</tbody>
				</table>
				
				<p class="form-controls"><button type="submit" class="button-primary button-submit"><?php _e('Save Changes'); ?></button></p>
			</form>
			
			
		</div>
<?php
	}
	
	public function admin_page_translations() {
		
		if( $_POST['action'] == 'save_translations') {
		
			$msg = $this->save_settings('translations');
		} elseif( $_POST['action'] == 'save_reset_translations' || $_POST['save_reset_translations'] == 1 ) {
			$msg = $this->save_settings('reset_translations');
		}
		
		
		$arrTranslations = $this->get_custom_translations_file('default');
		
		

		
		$arrCustomTranslations = get_option( $this->pluginPrefix . 'translations', $arrTranslations );

		
		// display message
		if( isset($msg) != false ) {
			$this->_show_message( $msg );
		} 
		
		?>
		<div class="wrap">
			<h2><?php echo $this->pluginName . ' v' . $this->pluginVersion; ?> &raquo; Custom Translations</h2>
			
			<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo $_GET['page']; ?>">
				<input type="hidden" name="action" value="save_translations" />
				
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
					if(!empty($arrCustomTranslations) ) {
						$iRowCount = 0;
						foreach($arrTranslations as $strKeyword => $strText) { ?>
						<tr class="<?php echo (!($iRowCount % 2) ? 'alternate' : ''); $iRowCount++; ?>">
							<td><?php echo $strKeyword; ?></td>
							<td><?php echo $strText; ?></td>
							<td><textarea name="translation[<?php echo $strKeyword; ?>]" rows="4" cols="40" resizable="resizable"><?php 
							if( isset($arrCustomTranslations[$strKeyword]) != false) {
								echo $arrCustomTranslations[$strKeyword];
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
				
				<p class="form-controls"><button type="submit" class="button-primary button-submit"><?php _e('Save Changes'); ?></button> <label><input type="checkbox" name="save_reset_translations" value="1" id="checkbox-reset-translations" /> <?php _e('Reset to original translations'); ?></label></p>
			</form>
		</div>
		<?php
	}
	
	
	
}
