<?php
/**
 * ACF Widget - Admin section
 */

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
				
				<p><label for="field-thanks-message">Custom thanks message</label><br />
				<textarea name="thanks_message" id="field-thanks-message"></textarea><br />
				<small>Adjust the message which is being displayed after successfully sending the contents of the contact form.</small></p>
				
				<p><input type="checkbox" class="checkbox" name="use_custom_translations" id="field-custom-translations" value="1" /> <label for="field-custom-translations">Enable custom translations</label></p>
				
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
