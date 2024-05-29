<?php

namespace JET_ABAF\Compatibility\Packages\Conditions;

use \Jet_Engine\Modules\Dynamic_Visibility\Conditions\Base;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Is_Cancellable extends Base {

	/**
	 * Returns condition ID.
	 *
	 * @since 3.3.0
	 *
	 * @return string
	 */
	public function get_id() {
		return 'is-cancellable';
	}

	/**
	 * Returns condition name.
	 *
	 * @since 3.3.0
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Booking is Cancellable', 'jet-booking' );
	}

	/**
	 * Returns group for current condition.
	 *
	 * @since 3.3.0
	 *
	 * @return string
	 */
	public function get_group() {
		return 'jet_booking';
	}

	/**
	 * Check condition by passed arguments.
	 *
	 * @since 3.3.0
	 *
	 * @return boolean
	 */
	public function check( $args = [] ) {

		$type    = ! empty( $args['type'] ) ? $args['type'] : 'show';
		$booking = jet_engine()->listings->data->get_current_object();

		if ( ! is_a( $booking, '\JET_ABAF\Resources\Booking' ) ) {
			if ( 'hide' === $type ) {
				return ! is_a( $booking, '\JET_ABAF\Resources\Booking' );
			} else {
				return is_a( $booking, '\JET_ABAF\Resources\Booking' );
			}
		}

		if ( 'hide' === $type ) {
			return ! $booking->is_cancellable();
		} else {
			return $booking->is_cancellable();
		}

	}

	/**
	 * Check if is condition available for meta fields control.
	 *
	 * @since 3.3.0
	 *
	 * @return boolean
	 */
	public function is_for_fields() {
		return false;
	}

	/**
	 * Check if is condition available for meta value control.
	 *
	 * @since 3.3.0
	 *
	 * @return boolean
	 */
	public function need_value_detect() {
		return false;
	}

}
