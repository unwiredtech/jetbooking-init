<?php
/**
 * The template for displaying a booking summary to customers.
 * It will display in:
 * - Reviewing a customer order in admin panel.
 *
 * This template can be overridden by copying it to yourtheme/jet-booking/admin/booking-summary.php.
 *
 * @since   3.0.0
 * @version 3.0.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.
?>

<div class="jet-booking-summary">
	<div class="jet-booking-summary__number">
		<strong><?php printf( __( 'Booking #%s', 'jet-booking' ), $booking->get_id() ); ?></strong>
		<?php printf( '<span class="%s">%s</span>', implode( ' ', $status_classes ), $statuses[ $booking->get_status() ] ) ?>
	</div>
	<div class="jet-booking-summary__dates">
		<div class="jet-booking-summary__date-check-in">
			<?php printf( __( 'Check in: %s', 'jet-booking' ), date_i18n( get_option( 'date_format' ), $booking->get_check_in_date() ) ); ?>
		</div>
		<div class="jet-booking-summary__date-check-out">
			<?php printf( __( 'Check out: %s', 'jet-booking' ), date_i18n( get_option( 'date_format' ), $booking->get_check_out_date() ) ); ?>
		</div>
	</div>
	<div class="jet-booking-summary__actions">
		<?php printf( '<a href="%s" target="_blank">%s</a>', add_query_arg( [ 'page' => 'jet-abaf-bookings', 'booking-details' => $booking->get_id() ], admin_url( 'admin.php' ) ), __( 'View details &rarr;', 'jet-booking' ) ); ?>
	</div>
</div>
