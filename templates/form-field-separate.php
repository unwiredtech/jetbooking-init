<?php
/**
 * Render check-in/check-out separate fields for booking form.
 *
 * This template can be overridden by copying it to yourtheme/jet-booking/form-field-separate.php
 *
 * @version 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<div class="jet-abaf-separate-fields">
	<div class="<?php echo implode( ' ', $col_classes ) ?>">
		<?php if ( $checkin_label ) : ?>
			<div class="<?php echo implode( ' ', $label_classes ) ?>">
				<?php
				echo $checkin_label;

				if ( ! empty( $args['required'] ) ) {
					echo '<span class="' . implode( ' ', $required_classes ) . '">&nbsp;<abbr class="required" title="required">*</abbr></span>';
				}
				?>
			</div>
		<?php endif; ?>
		<div class="jet-abaf-separate-field__control">
			<input
				type="text"
				id="jet_abaf_field_1"
				name="<?php echo $args['name']; ?>__in"
				class="<?php echo implode( ' ', $field_classes ) ?>"
				placeholder="<?php echo $checkin_placeholder; ?>"
				value="<?php echo $checkin; ?>"
				autocomplete="off"
				readonly
				<?php echo ! empty( $args['required'] ) ? 'required' : ''; ?>
			/>
		</div>
	</div>

	<div class="<?php echo implode( ' ', $col_classes ) ?>">
		<?php if ( $checkout_label ) : ?>
			<div class="<?php echo implode( ' ', $label_classes ) ?>">
				<?php
				echo $checkout_label;

				if ( ! empty( $args['required'] ) ) {
					echo '<span class="' . implode( ' ', $required_classes ) . '">&nbsp;<abbr class="required" title="required">*</abbr></span>';
				}
				?>
			</div>
		<?php endif; ?>
		<div class="jet-abaf-separate-field__control">
			<input
				type="text"
				id="jet_abaf_field_2"
				name="<?php echo $args['name']; ?>__out"
				class="<?php echo implode( ' ', $field_classes ) ?>"
				placeholder="<?php echo $checkout_placeholder; ?>"
				value="<?php echo $checkout; ?>"
				autocomplete="off"
				readonly
				<?php echo ! empty( $args['required'] ) ? 'required' : ''; ?>
			/>
		</div>
	</div>

	<input
		type="hidden"
		id="jet_abaf_field_range"
		name="<?php echo $args['name']; ?>"
		class="<?php echo implode( ' ', $field_classes ) ?>"
		value="<?php echo $default_value; ?>"
		data-field="checkin-checkout"
		data-format="<?php echo $field_format; ?>"
	/>
</div>

<?php jet_abaf()->assets->ensure_ajax_js(); ?>