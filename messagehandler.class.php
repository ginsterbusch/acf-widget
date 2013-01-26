<?php
/**
 * Simple, generic / global message handler for the WP Admin interface
 * 
 * @param class 			The main HTML class of the message div
 * @param content_class		The HTML class of the message content
 * @param details_class		The HTML class of the message details view
 *
 * @version 0.2
 */
 
class genericMessageHandler {
	var $className = 'Generic Message Handler',
		$classVersion = '0.2',
		$classPrefix = 'generic_message_handler_',
		
		// customizable HTML classes
		$strHTMLClass = 'message-handler',
		$strHTMLContentClass = 'message-content',
		$strHTMLDetailsClass = 'message-details',
		
		// message itself
		$strMessage = '',
		$strType = 'error',
		$valDetails = false;
		
		
	function __construct( $arrParams ) {
		// params
		extract( $arrParams, EXTR_PREFIX_ALL, 'param_');
		
		// set main class
		if( !empty( $param_class ) ) {
			$this->strHTMLClass = $param_class;
		}
		
		// set content class
		if( !empty( $param_content_class ) ) {
			$this->strHTMLContentClass = $param_content_class;
		}
		
		// set details class (ie. debug info)
		if( !empty( $param_details_class ) ) {
			$this->strHTMLDetailsClass = $param_details_class;
		}
		
		
		
		// actions
		
		if( is_admin() ) {
			// add jquery to the queue
			add_action('admin_enqueue_scripts', array( $this, 'add_admin_js') );
			
			// add css for the message
			add_action( 'admin_print_footer_scripts', array( $this, 'show_admin_css') ) ; 
			
			// add JS code for opening/closing the details section
			add_action( 'admin_print_footer_scripts', array( $this, 'show_admin_js') ) ; 
		}
		
	}
	
	public function add_admin_js() {
		wp_enqueue_script('jquery');
	}
	
	public function show_admin_css() {
?>
	<!-- CSS for the genericMessageHandler class -->
	<style type="text/css">
		.js .message-handler .message-details {
			display: none;
		}
	</style>
<?php
	}
	
	public function show_admin_js() {
?>
<script type="text/javascript">
	
jQuery(function() {
	jQuery('.<?php echo $this->strHTMLDetailsClass; ?>-link').bind('click', function(e) {
	// some nice JS binding
	// ie9+ et all w3c-conform browsers
	
	//document.getElementsByClassName( 'message-hamdler-details-link' ).addEventListener('click', function(e) {
		// parent = div.message-handler
		
		jQuery(e).parent().find( '.<?php echo $this->strHTMLDetailsClass; ?>' ).toggle();
			
		if (e && e.preventDefault) {
			e.preventDefault();
			//e.stopPropagation();
		} else if (window.event && window.event.returnValue) { // fun with IE
			window.eventReturnValue = false;
		}
			
		
		
	});

});
</script>

<?php	
	}
	
	public function set_type( $strType = 'error' ) {
		$return = false;
		
		if( !empty( $strType) ) {
			
			// known types
			switch( $strType ) {
				case 'error':
					$this->strType = 'error';
					$return = true;
				case 'update':
				case 'saved':
				case 'notice':
				case 'note':
					$this->strType = 'updated fade';
					$return = true;
			}
		}
		
		return $return;
	}
	
	public function set_message( $strText = null ) {
		$return = false;
		
		if( !empty( $strText ) ) {
			$this->strMessage = $strText;
			$return = true;
		}
		
		return $return;
	}
	
	
	
	
	public function set_details( $detailValue = null ) {
		$return = true;
		
		$this->detailValue = $detailValue;

		return $return;
	}
	
	
	public function get_message() {
		return $this->_prepare_message();
	}
	
	public function show_message() {
		$return = '';
		$result = $this->_prepare_message();
		
		if( $result != false ) {
			$return = $result;
		}
		
		echo $return;
	}
	
	
	
	
	protected function _prepare_message() {
		$return = false;
		
		if( !empty( $this->strMessage) ) {
		
			// put all together
		//	$arrReturn = array(
			
			
	}
}
