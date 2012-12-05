<?php
/**
 * Re-usable contact form
 * 
 * @version 0.2
 * + added actions hooks
 */

?>
	<form id="alpha-form-<?php echo $template->id; ?>" action="<?php echo $template->form_url; ?>" method="post" class="alpha-contact-form">
		<p>
			<label>
				<span>Name</span>
				<input type="text" name="name" class="alpha-field-name acf-field" />
			</label>
		</p>

		<p>
			<label class="required-field">
				<span>E-Mail</span>
				<input type="text" name="from" class="alpha-field-from acf-field required" />
			</label>
		</p>

	<?php
	// hook before output of custom form fields
	do_action('acf_widget_form_before_custom_fields');
	
	if(isset($template->custom_fields) != false) { 
		foreach($template->custom_fields as $strCustomField) { ?>
		<p>
			<label>
				<span><?php echo str_replace('_', ' ', $strCustomField); ?></span>
				<input type="text" name="<?php echo $strCustomField; ?>" class="alpha-custom-field acf-field <?php echo $strCustomField; ?>" />
			</label>
		</p>

	<?php }
	} 
	
	// hook after output of custom form fields
	do_action('acf_widget_form_after_custom_fields');
	
	?>
	
		<p>
			<label class="required-field">
				<span>Message</span>

				<textarea name="message" cols="40" rows="5" class="alpha-field-message acf-field required"></textarea>
			</label>
		</p>

		<?php // hook after the message field - eg. to insert a CAPTCHA section
		do_action('acf_widget_form_after_message_field'); ?>

		<?php // hook for hidden fields
		do_action('acf_worm_form_before_hidden_fields'); ?>

		<input type="hidden" name="id" class="alpha-form-widget-id" id="<?php echo $this->id; ?>" value="<?php echo $this->id; ?>" />

		<?php // hook for hidden fields
		do_action('acf_worm_form_after_hidden_fields'); ?>
		
		
		<?php // hook before form controls
		do_action('acf_worm_form_before_form_controls'); ?>

		<p class="form-controls"><button type="submit" class="alpha-button-submit">Submit</button></p>
		
		<?php // hook AFTER form controls
		do_action('acf_worm_form_after_form_controls'); ?>
	</form>

	<style type="text/css">
		#alpha-form-<?php echo $template->id; ?> .required-field label span {
			font-weight: bold;
		}
		
		#alpha-form-<?php echo $template->id; ?> .required.missing {
			border: 2xp solid #c33;
			background-color: #fdd;
			color: #111;
		}
	</style>
