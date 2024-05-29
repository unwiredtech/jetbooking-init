<?php

use \JET_ABAF\Resources\Booking_Query;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Get bookings.
 *
 * Standard way of retrieving bookings based on certain parameters.
 *
 * This function should be used for booking retrieval so that we have a data agnostic
 * way to get a list of booking.
 *
 * @since 3.3.0
 *
 * @param array $args Array of arguments.
 *
 * @return array|object
 */
function jet_abaf_get_bookings( $args = [] ) {
	$query = new Booking_Query( $args );

	return $query->get_bookings();
}

/**
 * Get booking.
 *
 * Main function for returning products.
 *
 * @since 3.3.0
 *
 * @param string|int $id ID of the booking.
 *
 * @return mixed
 */
function jet_abaf_get_booking( $id ) {
	$bookings = jet_abaf_get_bookings( [ 'include' => $id ] );

	return reset( $bookings );
}
