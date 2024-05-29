<?php

namespace JET_ABAF\Rest_API\Endpoints;

defined( 'ABSPATH' ) || exit;

class Booked_Dates extends Base {

	/**
	 * Get name.
	 *
	 * Returns route name.
	 *
	 * @since  2.2.5
	 *
	 * @return string
	 */
	public function get_name() {
		return 'booked-dates';
	}

	/**
	 * Callback.
	 *
	 * API callback.
	 *
	 * @since  2.2.5
	 * @since  3.2.0 Refactored.
	 *
	 * @param object $request Endpoint request object.
	 *
	 * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
	 * @throws \Exception
	 */
	public function callback( $request ) {

		$params = $request->get_params();
		$item   = ! empty( $params['item'] ) ? $params['item'] : [];

		if ( empty( $item ) ) {
			return rest_ensure_response( [
				'success' => false,
				'data'    => __( 'No data to check booked dates.', 'jet-booking' ),
			] );
		}

		$apartment_id = $item['apartment_id'];

		if ( empty( $apartment_id ) ) {
			return rest_ensure_response( [
				'success' => false,
				'data'    => __( 'Incorrect item data.', 'jet-booking' ),
			] );
		}

		$localized_data = jet_abaf()->assets->get_localized_data( $apartment_id );

		return rest_ensure_response( wp_parse_args( $localized_data, [
			'success'       => true,
			'start_of_week' => get_option( 'start_of_week' ) ? 'monday' : 'sunday',
			'units'         => jet_abaf()->db->get_apartment_units( $apartment_id ),
		] ) );

	}

	/**
	 * Permission callback.
	 *
	 * Check user access to current end-point.
	 *
	 * @since  2.2.5
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
	 * @since  2.2.5
	 *
	 * @return string
	 */
	public function get_method() {
		return 'POST';
	}

}