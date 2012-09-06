<?php
/**
 * Re-usable contact form
 */

?>
	<form id="alpha-form-<?php echo $template->id; ?>" action="<?php echo $template->form_url(); ?>" method="post" class="alpha-contact-form">
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
	if(isset($template->custom_fields) != false) { 
		foreach($template->custom_fields as $strCustomField) { ?>
		<p>
			<label>
				<span><?php echo str_replace('_', ' ', $strCustomField); ?></span>
				<input type="text" name="<?php echo $strCustomField; ?>" class="alpha-custom-field acf-field <?php echo $strCustomField; ?>" />
			</label>
		</p>

	<?php }
	} ?>

		<p>
			<label class="required-field">
				<span>Message</span>

				<textarea name="message" cols="40" rows="5" class="alpha-field-message acf-field required"></textarea>
			</label>
		</p>


		<input type="hidden" name="id" class="alpha-form-widget-id" id="<?php echo $this->id; ?>" value="<?php echo $this->id; ?>" />

		<p class="form-controls"><button type="submit" class="alpha-button-submit">Absenden</button></p>
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
