<?php
/**
 * Render check-in/check-out single fields for booking form.
 *
 * This template can be overridden by copying it to yourtheme/jet-booking/form-field-single.php
 *
 * @version 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<div class="jet-abaf-field">
	<input
		type="text"
		id="jet_abaf_field"
		name="<?php echo $args['name']; ?>"
		class="<?php echo implode( ' ', $field_classes ) ?>"
		placeholder="<?php echo $placeholder; ?>"
		value="<?php echo $default_value; ?>"
		data-field="checkin-checkout"
		data-format="<?php echo $field_format; ?>"
		autocomplete="off"
		readonly
		<?php echo ! empty( $args['required'] ) ? 'required' : ''; ?>
	/>
</div>

<?php jet_abaf()->assets->ensure_ajax_js(); ?>