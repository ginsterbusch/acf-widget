<?php
/**
 * Server-side form processing for Alpha Contact Form Widget
 * 
 * @package AlphaContactFormWidget
 * @version 0.2
 * @author Fabian Wolf
 * @link http://usability-idealist.de/
 */

// find wp-config.php for bootstraping
$strBootstrapPath = '';
$strPath = str_replace(basename(__FILE__), '', __FILE__);
$strNeedle = 'wp-content/';

if( stripos( $strPath, $strNeedle) !== false ) {
	$x = explode($strNeedle, $strPath);
	$strBootstrapPath = trim($x[0]);
}

// bootstrap path not found

if(empty($strBootstrapPath) != false || !file_exists($strBootstrapPath . 'wp-config.php') ) {
	die;
}

// bootstrap wp-config.php
require_once( $strBootstrapPath . 'wp-config.php');
require_once( 'acf-widget.php');


// init helper class
$_acf_form = new AlphaContactFormHelper();


// use plugin class to do the heavy lifting

$_acf->acf_process_form();

/** 
 * Helper class
 */
 
 
class AlphaContactFormHelper {
	function __construct() {
		
	}
	
	public function html_document( $strContent = '', $iRedirectionTimeout = 30, $strRedirectURL = '' ) {
		
		get_header();
		?>
		<div class="alpha-contact-form-content"><?php echo $strContent; ?></div>
		<script type="text/javascript">
			function redirectMe() {
				<?php if(!empty($strRedirectURL) ) { ?>
				
				document.location.href = '<?php echo $strRedirectURL; ?>'
				
				<?php } else { ?>
				
				document.location.reload()
				
				<?php } ?>
			}
			
			window.setTimout('redirectMe()', <?php echo $iRedirectionTimeout * 1000; ?>)
		</script>
		<?php
		get_footer();
	}
}
