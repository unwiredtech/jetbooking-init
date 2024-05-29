<?php

namespace JET_ABAF\Macros\Traits;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

trait Bookings_Count_Trait {

	/**
	 * Macros tag.
	 *
	 * Returns macros tag.
	 *
	 * @since 3.2.0
	 *
	 * @return string
	 */
	public function macros_tag() {
		return 'bookings_count';
	}

	/**
	 * Macros name.
	 *
	 * Returns macros name.
	 *
	 * @since 3.2.0
	 *
	 * @return string
	 */
	public function macros_name() {
		return __( 'Bookings Count', 'jet-booking' );
	}

	/**
	 * Macros args.
	 *
	 * Return custom macros attributes list.
	 *
	 * @since 3.2.0
	 *
	 * @return array
	 */
	public function macros_args() {
		return [
			'booking_start_date' => [
				'type'        => 'text',
				'label'       => __( 'Start Date', 'jet-booking' ),
				'description' => __( 'Enter date in Universal time format: `Y-m-d H:i:s` or `Y-m-d`. Example: `1996-04-09 00:00:00` or `1996-04-09`.', 'jet-booking' ),
			],
			'booking_end_date'   => [
				'type'        => 'text',
				'label'       => __( 'End Date', 'jet-booking' ),
				'description' => __( 'Enter date in Universal time format: `Y-m-d H:i:s` or `Y-m-d`. Example: `1996-04-09 00:00:00` or `1996-04-09`.', 'jet-booking' ),
			],
		];
	}

	/**
	 * Macros callback.
	 *
	 * Callback function to return macros value.
	 *
	 * @since 3.2.0
	 *
	 * @param array $args Macros arguments list.
	 *
	 * @return string
	 */
	public function macros_callback( $args = [] ) {

		$from = ! empty( $args['booking_start_date'] ) ? $args['booking_start_date'] : '';
		$to   = ! empty( $args['booking_end_date'] ) ? $args['booking_end_date'] : '';

		if ( empty( $from ) ) {
			return __( 'Please specify date range.', 'jet-booking' );
		}

		if ( empty( $to ) ) {
			$to = $from;
		}

		$booking = [
			'apartment_id'   => get_the_ID(),
			'check_in_date'  => strtotime( $from ),
			'check_out_date' => strtotime( $to ),
		];

		$units = jet_abaf()->db->get_booked_units( $booking );

		return ! empty( $units ) ? count( $units ) : 0;

	}

}
