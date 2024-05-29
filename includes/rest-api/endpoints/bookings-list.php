<?php

namespace JET_ABAF\Rest_API\Endpoints;

defined( 'ABSPATH' ) || exit;

class Bookings_List extends Base {

	/**
	 * Get name.
	 *
	 * Returns route name.
	 *
	 * @since  2.0.0
	 *
	 * @return string
	 */
	public function get_name() {
		return 'bookings-list';
	}

	/**
	 * Callback.
	 *
	 * API callback.
	 *
	 * @since  2.0.0
	 * @since  3.2.0 Refactored.
	 *
	 * @param object $request Endpoint request object.
	 *
	 * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
	 */
	public function callback( $request ) {

		$params   = jet_abaf()->db->prepare_params( $request->get_params() );
		$bookings = jet_abaf_get_bookings( wp_parse_args( $params, [ 'return' => 'arrays' ] ) );

		unset( $params['limit'] );

		return rest_ensure_response( [
			'success' => true,
			'data'    => $this->format_dates( $bookings ),
			'total'   => count( jet_abaf_get_bookings( $params ) ),
		] );

	}

	/**
	 * Format dates.
	 *
	 * Transform dates to human readable format and add additional parameters to booked item.
	 *
	 * @since  2.0.0
	 * @since  2.5.4 Added timestamp dates.
	 * @access public
	 *
	 * @param array $bookings List of all bookings.
	 *
	 * @return array
	 */
	public function format_dates( $bookings = [] ) {

		$date_format = get_option( 'date_format', 'F j, Y' );

		return array_map( function ( $booking ) use ( $date_format ) {
			$booking['check_in_date_timestamp']  = $booking['check_in_date'];
			$booking['check_in_date']            = date_i18n( $date_format, $booking['check_in_date'] );
			$booking['check_out_date_timestamp'] = $booking['check_out_date'];
			$booking['check_out_date']           = date_i18n( $date_format, $booking['check_out_date'] );
			$booking['status']                   = ! empty( $booking['status'] ) ? $booking['status'] : 'pending';

			return $booking;
		}, $bookings );

	}

	/**
	 * Permission callback.
	 *
	 * Check user access to current end-point.
	 *
	 * @since  2.0.0
	 *
	 * @param object $request Endpoint request object.
	 *
	 * @return bool
	 */
	public function permission_callback( $request ) {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get method.
	 *
	 * Returns endpoint request method - GET/POST/PUT/DELETE.
	 *
	 * @since  2.0.0
	 *
	 * @return string
	 */
	public function get_method() {
		return 'GET';
	}

	/**
	 * Get args.
	 *
	 * Returns arguments config.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function get_args() {
		return [
			'offset'   => [
				'default'  => 0,
				'required' => false,
			],
			'per_page' => [
				'default'  => 50,
				'required' => false,
			],
			'filters'  => [
				'default'  => [],
				'required' => false,
			],
			'mode'     => [
				'default'  => 'all',
				'required' => false,
			],
		];
	}

}
