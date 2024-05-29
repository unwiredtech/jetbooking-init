<?php

namespace JET_ABAF\WC_Integration;

use JET_ABAF\Cron\Manager as Cron_Manager;
use JET_ABAF\Price;

class WC_Cart_Manager {

	/**
	 * Schedule.
	 *
	 * Schedule event holder.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @var array|false|mixed
	 */
	private $schedule;

	public function __construct() {

		$this->schedule = Cron_Manager::instance()->get_schedules( 'jet-booking-clear-on-expire' );

		// Validate data before adding product to cart.
		add_filter( 'woocommerce_add_to_cart_validation', [ $this, 'validate_custom_fields_data' ], 10, 3 );

		// Add posted booking data to the cart item.
		add_filter( 'woocommerce_add_cart_item_data', [ $this, 'add_custom_cart_item_data' ], 10, 4 );

		// Adjust the price of the booking product based on booking properties
		add_filter( 'woocommerce_add_cart_item', [ $this, 'add_cart_item' ], 10, 2 );
		add_filter( 'woocommerce_get_cart_item_from_session', [ $this, 'get_cart_item_from_session' ], 10, 3 );

		// Handle removed and restored cart item.
		add_action( 'woocommerce_cart_item_removed', [ $this, 'cart_item_removed' ], 20 );
		add_action( 'woocommerce_cart_item_restored', [ $this, 'cart_item_restored' ], 20, 2 );

		// Remove expired cart items.
		add_action( 'woocommerce_cart_loaded_from_session', [ $this, 'remove_expired_cart_items' ] );

	}

	/**
	 * Validate custom fields data.
	 *
	 * Validate booking product custom dates input field value when a booking is added to the cart.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param bool  $passed     Validation.
	 * @param int   $product_id Product ID.
	 * @param mixed $qty        Products quantity.
	 *
	 * @return false|mixed
	 */
	public function validate_custom_fields_data( $passed, $product_id, $qty ) {

		$product = wc_get_product( $product_id );

		if ( jet_abaf()->wc->mode->is_booking_product( $product ) ) {
			$message = '';

			if ( empty( $_POST['jet_abaf_field'] ) ) {
				$message = __( 'Dates is a required field(s).', 'jet-booking' );
			} else {
				$dates = $this->get_transformed_dates( $_POST['jet_abaf_field'], $product_id );

				if ( ! $dates ) {
					$message = __( 'Dates is a required field(s).', 'jet-booking' );
				} else {
					$booking = [
						'apartment_id'   => $product_id,
						'check_in_date'  => absint( $dates[0] ) + 1,
						'check_out_date' => $dates[0] === $dates[1] ? absint( $dates[1] ) + 12 * HOUR_IN_SECONDS : $dates[1],
					];

					$booking['apartment_unit'] = jet_abaf()->db->get_available_unit( $booking );

					$is_available       = jet_abaf()->db->booking_availability( $booking );
					$is_dates_available = jet_abaf()->db->is_booking_dates_available( $booking );

					if ( ! $is_available && ! $is_dates_available ) {
						$message = __( 'Booking could not be added. Selected dates are no longer available.', 'jet-booking' );
					}
				}
			}

			if ( ! empty( $message ) ) {
				wc_add_notice( $message, 'error' );

				return false;
			}
		}

		return $passed;

	}

	/**
	 * Add custom cart item data.
	 *
	 * Add posted custom booking data to cart item.
	 *
	 * @since  3.0.0
	 * @since  3.3.0 Fixed One day bookings.
	 *
	 * @param array $cart_item_data Cart items list..
	 * @param int   $product_id     ID of the added product.
	 * @param int   $variation_id   ID of the added product variation.
	 * @param int   $quantity       Quantity of items.
	 *
	 * @return mixed
	 */
	public function add_custom_cart_item_data( $cart_item_data, $product_id, $variation_id, $quantity ) {

		$product = wc_get_product( $product_id );

		if ( jet_abaf()->wc->mode->is_booking_product( $product ) && ! empty( $_POST['jet_abaf_field'] ) ) {
			$dates = $this->get_transformed_dates( $_POST['jet_abaf_field'], $product_id );
			$args  = [
				'apartment_id'   => $product_id,
				'status'         => 'on-hold',
				'check_in_date'  => $dates[0],
				'check_out_date' => $dates[1],
			];

			if ( $args['check_in_date'] === $args['check_out_date'] ) {
				$args['check_out_date'] += 12 * HOUR_IN_SECONDS;
			}

			if ( is_user_logged_in() ) {
				$args['user_id'] = get_current_user_id();
			}

			$booking_id = jet_abaf()->db->insert_booking( $args );

			if ( $booking_id ) {
				$args['check_in_date'] ++;

				$cart_item_data[ jet_abaf()->wc->data_key ] = wp_parse_args( [ 'booking_id' => $booking_id ], $args );
				$this->schedule->schedule_single_event( [ $booking_id ] );

				do_action( 'jet-booking/wc-integration/booking-inserted', $booking_id );
			}
		}

		return $cart_item_data;

	}

	/**
	 * Add cart item.
	 *
	 * Adjust the price of the booking product based on booking properties.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param array  $cart_item_data WooCommerce cart item data array.
	 * @param string $cart_item_key  WooCommerce cart item key.
	 *
	 * @return mixed
	 */
	public function add_cart_item( $cart_item_data, $cart_item_key ) {

		if ( jet_abaf()->wc->mode->is_booking_product( $cart_item_data['data'] ) && ! empty( $cart_item_data[ jet_abaf()->wc->data_key ] ) ) {
			$price         = new Price( $cart_item_data[ jet_abaf()->wc->data_key ]['apartment_id'] );
			$booking_price = $price->get_booking_price( $cart_item_data[ jet_abaf()->wc->data_key ] );

			$cart_item_data['data']->set_price( $booking_price );
		}

		return $cart_item_data;

	}

	/**
	 * Get cart item from session.
	 *
	 * Get data from the session and add to the cart item's meta.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param array  $cart_item_data WooCommerce cart item data array.
	 * @param array  $values         Cart values array.
	 * @param string $cart_item_key  WooCommerce cart item key.
	 *
	 * @return array|mixed
	 */
	public function get_cart_item_from_session( $cart_item_data, $values, $cart_item_key ) {

		if ( ! empty( $values[ jet_abaf()->wc->data_key ] ) ) {
			$cart_item_data[ jet_abaf()->wc->data_key ] = $values[ jet_abaf()->wc->data_key ];
			$cart_item_data                             = $this->add_cart_item( $cart_item_data, $cart_item_key );
		}

		return $cart_item_data;

	}

	/**
	 * Remove cart item.
	 *
	 * Delete and clear schedule for JetBooking product.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param string $cart_item_key Removed cart item in identifier.
	 *
	 * @return void
	 */
	public function cart_item_removed( $cart_item_key ) {

		$cart_item = WC()->cart->removed_cart_contents[ $cart_item_key ];

		if ( ! empty( $cart_item[ jet_abaf()->wc->data_key ] ) ) {
			$booking_id = $cart_item[ jet_abaf()->wc->data_key ]['booking_id'];

			jet_abaf()->db->delete_booking( [ 'booking_id' => $booking_id ] );
			$this->schedule->unschedule_single_event( [ $booking_id ] );
		}

	}

	/**
	 * Cart item restored.
	 *
	 * Restore cart item as well as related booking, reschedule expired event.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param string   $cart_item_key Removed cart item in identifier.
	 * @param \WC_Cart $cart          WooCommerce cart instance.
	 *
	 * @return void
	 */
	public function cart_item_restored( $cart_item_key, $cart ) {

		$cart_items = $cart->get_cart();
		$cart_item  = $cart_items[ $cart_item_key ];

		if ( jet_abaf()->wc->mode->is_booking_product( $cart_item['data'] ) && ! empty( $cart_item[ jet_abaf()->wc->data_key ] ) ) {
			$booking_id = jet_abaf()->db->insert_booking( $cart_item[ jet_abaf()->wc->data_key ] );

			if ( $booking_id ) {
				$this->schedule->schedule_single_event( [ $booking_id ] );

				do_action( 'jet-booking/wc-integration/booking-inserted', $booking_id );
			}
		}

	}

	/**
	 * Remove expired cart items.
	 *
	 * Check for invalid bookings and remove related cart items.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param \WC_Cart $cart WooCommerce cart instance.
	 *
	 * @return void
	 */
	public function remove_expired_cart_items( $cart ) {

		$titles       = [];
		$titles_count = 0;

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( jet_abaf()->wc->mode->is_booking_product( $cart_item['data'] ) && ! empty( $cart_item[ jet_abaf()->wc->data_key ] ) ) {
				$booking = jet_abaf_get_booking( $cart_item[ jet_abaf()->wc->data_key ]['booking_id'] );

				if ( ! $booking ) {
					unset( $cart->cart_contents[ $cart_item_key ] );

					$cart->calculate_totals();

					if ( $cart_item['product_id'] ) {
						$title = '<a href="' . get_permalink( $cart_item['product_id'] ) . '">' . get_the_title( $cart_item['product_id'] ) . '</a>';

						if ( ! in_array( $title, $titles, true ) ) {
							$titles[] = $title;
						}

						$titles_count ++;
					}
				}
			}
		}

		if ( $titles_count < 1 ) {
			return;
		}

		$formatted_titles = wc_format_list_of_items( $titles );
		$notice           = sprintf( __( 'A booking for %s has been removed from your cart due to inactivity.', 'jet-booking' ), $formatted_titles );

		if ( $titles_count > 1 ) {
			$notice = sprintf( __( 'Bookings for %s have been removed from your cart due to inactivity.', 'jet-booking' ), $formatted_titles );
		}

		wc_add_notice( $notice, 'notice' );

	}

	/**
	 * Get transformed dates.
	 *
	 * Returns transformed list of dates.
	 *
	 * @since  3.0.0
	 * @since  3.0.1 Added $product_id parameter.
	 * @access public
	 *
	 * @param string $dates      Initial booking dates.
	 * @param int    $product_id Booking product ID.
	 *
	 * @return false|string[]
	 */
	public function get_transformed_dates( $dates, $product_id ) {

		$dates     = explode( ' - ', $dates );
		$format    = jet_abaf()->settings->get( 'field_date_format' );
		$format    = jet_abaf()->tools->date_format_js_to_php( '!' . $format );
		$separator = jet_abaf()->settings->get( 'field_separator' );

		if ( 'space' === $separator ) {
			$separator = ' ';
		}

		if ( ! empty( $dates[0] ) ) {
			$dates[0] = str_replace( $separator, '-', $dates[0] );
		}

		if ( jet_abaf()->settings->is_one_day_bookings( $product_id ) ) {
			$dates[1] = $dates[0];
		} elseif ( ! empty( $dates[1] ) ) {
			$dates[1] = str_replace( $separator, '-', $dates[1] );
		}

		$check_in_object  = \DateTime::createFromFormat( $format, $dates[0] );
		$check_out_object = \DateTime::createFromFormat( $format, $dates[1] );

		$dates[0] = $check_in_object ? $check_in_object->getTimestamp() : strtotime( $dates[0] );
		$dates[1] = $check_out_object ? $check_out_object->getTimestamp() : strtotime( $dates[1] );

		if ( empty( $dates[0] ) || empty( $dates[1] ) ) {
			return false;
		}

		return $dates;

	}

}
