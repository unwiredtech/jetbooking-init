<?php

namespace JET_ABAF\Cron;

class Clear_On_Expire extends Base {

	public function __construct() {

		if ( ! $this->is_enabled() ) {
			return;
		}

		add_action( $this->event_name(), [ $this, 'event_callback' ] );
		add_action( 'jet-booking/wc-integration/process-order', [ $this, 'clear_scheduled_on_process_order' ], 10, 3 );

	}

	/**
	 * Is enable.
	 *
	 * Check if recurrent event is active.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @return boolean
	 */
	public function is_enabled() {
		return 'wc_based' === jet_abaf()->settings->get( 'booking_mode' );
	}

	/**
	 * Event timestamp.
	 *
	 * Returns unix timestamp (UTC) for when to next run the event.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @return int
	 */
	public function event_timestamp() {
		return time() + absint( jet_abaf()->settings->get( 'booking_hold_time' ) );
	}

	/**
	 * Event name.
	 *
	 * Returns event name.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @return string
	 */
	public function event_name() {
		return 'jet-booking-clear-on-expire';
	}

	/**
	 * Event callback.
	 *
	 * Method to execute when the event is run.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param int|string $booking_id Booking ID.
	 *
	 * @return void
	 */
	public function event_callback( $booking_id = null ) {
		jet_abaf()->db->delete_booking( [ 'booking_id' => $booking_id ] );
	}

	/**
	 * Clear schedules on process order.
	 *
	 * Clear schedule booking event after order proceeded.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param int    $order_id  Process order ID.
	 * @param object $order     WC order object instance.
	 * @param array  $cart_item Processed cart item data list.
	 *
	 * @return void
	 */
	public function clear_scheduled_on_process_order( $order_id, $order, $cart_item ) {
		if ( ! empty( $cart_item ) && jet_abaf()->wc->mode->is_booking_product( $cart_item['data'] ) && ! empty( $cart_item[ jet_abaf()->wc->data_key ] ) ) {
			$this->unschedule_single_event( [ $cart_item[ jet_abaf()->wc->data_key ]['booking_id'] ] );
		}
	}

}

