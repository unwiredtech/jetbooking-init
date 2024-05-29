<?php

namespace JET_ABAF\WC_Integration;

use JET_ABAF\Price;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class WC_Order_Manager {

	/**
	 * Check if booking in deleting process.
	 *
	 * @var bool
	 */
	public $booking_deleting = false;

	public function __construct() {

		// Process order creation with validation.
		add_action( 'woocommerce_checkout_order_processed', [ $this, 'process_order' ], 10, 3 );
		add_action( 'woocommerce_store_api_checkout_order_processed', [ $this, 'process_order_by_api' ] );

		// Link order line item to the booking item with booking id.
		add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'add_custom_order_line_item_meta' ], 10, 4 );

		// Hide custom line item key from order edit page.
		add_filter( 'woocommerce_hidden_order_itemmeta', [ $this, 'hide_order_item_meta_data' ] );

		// Display booking data for related order.
		add_action( 'woocommerce_order_item_meta_end', [ $this, 'display_order_booking_summary' ], 10, 4 );
		add_action( 'woocommerce_after_order_itemmeta', [ $this, 'display_admin_order_booking_summary' ], 10, 3 );

		// Cancel bookings when an order refunded partially.
		add_action( 'woocommerce_order_partially_refunded', [ $this, 'cancel_bookings_for_partial_refunds' ], 10, 2 );

		// Handle order line item deletion.
		add_action( 'woocommerce_delete_order_item', [ $this, 'delete_related_booking_item' ] );

		// Handle order trash and delete.
		add_action( 'wp_trash_post', [ $this, 'trash_post' ] );
		add_action( 'before_delete_post', [ $this, 'delete_post' ] );

		// Handle HPOS order trash and delete.
		add_action( 'woocommerce_before_trash_order', [ $this, 'trash_post' ] );
		add_action( 'woocommerce_before_delete_order', [ $this, 'delete_post' ] );

		// Update line item meta data on order status update.
		add_action( 'jet-booking/wc-integration/process-order', [ $this, 'update_order_line_item_meta' ], 10, 3 );

		// Handle related order and order line items on linked booking item manipulations.
		add_action( 'jet-booking/db/booking-updated', [ $this, 'update_related_order_line_item' ], 10 );
		add_action( 'jet-booking/db/before-booking-delete', [ $this, 'maybe_remove_related_order_line_item' ], 10 );

		// Prevent circular bookings updates.
		add_action( 'jet-booking/wc-integration/before-set-order-data', function () {
			remove_action( 'jet-booking/db/booking-updated', [ $this, 'update_related_order_line_item' ], 10 );
		} );

		// Set related order data for booking created via admin panel.
		add_action( 'jet-booking/rest-api/add-booking/set-related-order-data', [ $this, 'set_booking_related_order' ], 10, 2 );

	}

	/**
	 * Process order.
	 *
	 * Process order with additional bookings expiration check.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param int       $order_id WC order ID.
	 * @param array     $data     Order data.
	 * @param \WC_Order $order    WC order object instance.
	 *
	 * @return void
	 */
	public function process_order( $order_id, $data, $order ) {
		foreach ( $order->get_items() as $item_id => $item ) {
			if ( ! empty( $item->get_meta( '__jet_booking_id' ) ) ) {
				$booking = jet_abaf_get_booking( $item->get_meta( '__jet_booking_id' ) );

				if ( ! $booking ) {
					$order->remove_item( $item_id );
					$order->calculate_totals();
					$order->save();
				}
			}
		}
	}

	/**
	 * Process order by API.
	 *
	 * Process new order creation for new checkout block API with additional bookings expiration check.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param \WC_Order $order WC order object instance.
	 *
	 * @return void
	 */
	public function process_order_by_api( $order ) {
		$this->process_order( $order->get_id(), [], $order );
	}

	/**
	 * Add custom order line item meta.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param \WC_Order_Item_Product $item          WC order item instance.
	 * @param string                 $cart_item_key Arbitrary key of cart items.
	 * @param array                  $values        Values list for the cart item key.
	 * @param \WC_Order              $order         WC order instance.
	 *
	 * @return void
	 */
	public function add_custom_order_line_item_meta( $item, $cart_item_key, $values, $order ) {
		if ( ! empty( $values[ jet_abaf()->wc->data_key ] ) ) {
			$booking_data   = $values[ jet_abaf()->wc->data_key ];
			$line_item_meta = [
				'__jet_booking_id'             => $booking_data['booking_id'] ?? 0,
				'__jet_booking_status'         => $booking_data['status'] ?? 'on-hold',
				'__jet_booking_check_in_date'  => $booking_data['check_in_date'] ?? '',
				'__jet_booking_check_out_date' => $booking_data['check_out_date'] ?? '',
			];

			foreach ( $line_item_meta as $key => $value ) {
				$item->add_meta_data( $key, $value, true );
			}
		}
	}

	/**
	 * Hide order item meta data.
	 *
	 * Hide custom line item key from order edit page.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param array $keys List of keys to hide.
	 *
	 * @return array
	 */
	public function hide_order_item_meta_data( $keys ) {

		$custom_keys = [
			'__jet_booking_id',
			'__jet_booking_status',
			'__jet_booking_check_in_date',
			'__jet_booking_check_out_date',
		];

		return array_merge( $keys, $custom_keys );

	}

	/**
	 * Display order booking summary.
	 *
	 * Show booking data if a line item is linked to a booking ID.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param int            $item_id WooCommerce order item ID.
	 * @param \WC_Order_Item $item    WooCommerce order item instance.
	 * @param \WC_Order      $order   WooCommerce order instance.
	 * @param string         $plain_text
	 *
	 * @return void
	 */
	public function display_order_booking_summary( $item_id, $item, $order, $plain_text ) {

		$bookings = jet_abaf_get_bookings( [ 'order_id' => $order->get_id() ] );

		if ( empty( $bookings ) ) {
			return;
		}

		foreach ( $bookings as $booking ) {
			if ( absint( $booking->get_id() ) === absint( $item->get_meta( '__jet_booking_id' ) ) ) {
				include JET_ABAF_PATH . 'templates/booking-summary.php';
			}
		}

	}

	/**
	 * Display admin order booking summary.
	 *
	 * Show booking data if a line item is linked to a booking ID.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param int            $item_id WooCommerce order item ID.
	 * @param \WC_Order_Item $item    WooCommerce order item instance.
	 * @param \WC_Product    $product WooCommerce product instance.
	 *
	 * @throws \Exception
	 * @return void
	 */
	public function display_admin_order_booking_summary( $item_id, $item, $product ) {

		$order_id = wc_get_order_id_by_order_item_id( $item_id );
		$bookings = jet_abaf_get_bookings( [ 'order_id' => $order_id ] );

		if ( empty( $bookings ) ) {
			return;
		}

		$statuses       = jet_abaf()->statuses->get_statuses();
		$status_classes = [ 'notice', 'notice-alt' ];

		foreach ( $bookings as $booking ) {
			if ( absint( $booking->get_id() ) !== absint( $item->get_meta( '__jet_booking_id' ) ) ) {
				continue;
			}

			if ( in_array( $booking->get_status(), jet_abaf()->statuses->finished_statuses() ) ) {
				$status_classes[] = 'notice-success';
			}

			if ( in_array( $booking->get_status(), jet_abaf()->statuses->in_progress_statuses() ) ) {
				$status_classes[] = 'notice-warning';
			}

			if ( in_array( $booking->get_status(), jet_abaf()->statuses->invalid_statuses() ) ) {
				$status_classes[] = 'notice-error';
			}

			include JET_ABAF_PATH . 'templates/admin/booking-summary.php';
		}

	}

	/**
	 * Cancel booking for partial refunds.
	 *
	 * Cancel bookings when an order refunded partially.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param int $order_id  Order ID.
	 * @param int $refund_id Refund ID.
	 *
	 * @return void
	 */
	public function cancel_bookings_for_partial_refunds( $order_id, $refund_id ) {

		$order = wc_get_order( $order_id );

		foreach ( $order->get_items() as $item_id => $item ) {
			$refunded_qty = $order->get_qty_refunded_for_item( $item_id );
			$booking_id   = $item->get_meta( '__jet_booking_id' );

			if ( $booking_id && 0 !== $refunded_qty ) {
				remove_action( 'jet-booking/db/booking-updated', [ $this, 'update_related_order_line_item' ], 10 );
				jet_abaf()->db->update_booking( $booking_id, [ 'status' => 'refunded' ] );
			}
		}

	}

	/**
	 * Delete related booking item.
	 *
	 * Delete an booking item after deletion related order line item.
	 *
	 * @since  3.0.0
	 *
	 * @param int $item_id Item ID.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function delete_related_booking_item( $item_id ) {

		if ( $this->booking_deleting ) {
			return;
		}

		$booking_id = wc_get_order_item_meta( $item_id, '__jet_booking_id' );

		if ( $booking_id ) {
			jet_abaf()->db->delete_booking( [ 'booking_id' => $booking_id ] );
		}

	}

	/**
	 * Trash post.
	 *
	 * Trash bookings with orders.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param mixed $post_id ID of post being trashed.
	 *
	 * @retur  void
	 */
	public function trash_post( $post_id ) {

		if ( ! $post_id || 'shop_order' !== get_post_type( $post_id ) ) {
			return;
		}

		$bookings = jet_abaf_get_bookings( [ 'order_id' => $post_id ] );

		if ( empty( $bookings ) ) {
			return;
		}

		remove_action( 'jet-booking/db/booking-updated', [ $this, 'update_related_order_line_item' ], 10 );

		foreach ( $bookings as $booking ) {
			jet_abaf()->db->update_booking( $booking->get_id(), [ 'status' => 'cancelled' ] );
		}

	}

	/**
	 * Delete post.
	 *
	 * Removes the bookings associated with the deleted WooCommerce order.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param mixed $post_id ID of post being deleted.
	 *
	 * @return void
	 */
	public function delete_post( $post_id ) {

		if ( ! current_user_can( 'delete_posts' ) || ! $post_id || 'shop_order' !== get_post_type( $post_id ) ) {
			return;
		}

		$bookings = jet_abaf_get_bookings( [ 'order_id' => $post_id ] );

		if ( empty( $bookings ) ) {
			return;
		}

		remove_action( 'jet-booking/db/before-booking-delete', [ $this, 'maybe_remove_related_order_line_item' ], 10 );

		foreach ( $bookings as $booking ) {
			jet_abaf()->db->delete_booking( [ 'booking_id' => $booking->get_id() ] );
		}

	}

	/**
	 * Update order line item meta.
	 *
	 * Update line item meta data on order status update.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param string|int $order_id  Order ID.
	 * @param \WC_Order  $order     WooCommerce order instance.
	 * @param array      $cart_item Cart items list.
	 *
	 * @return void.
	 */
	public function update_order_line_item_meta( $order_id, $order, $cart_item ) {
		foreach ( $order->get_items() as $item_id => $item ) {
			if ( ! empty( $item->get_meta( '__jet_booking_status' ) ) && $item->get_meta( '__jet_booking_status' ) !== $order->get_status() ) {
				$item->update_meta_data( '__jet_booking_status', $order->get_status() );
				$item->save();
			}
		}
	}

	/**
	 * Update related order line item.
	 *
	 * Update related order booking line item data on booking item update.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param string|int $booking_id Booking item ID.
	 *
	 * @throws \Exception
	 * @return void
	 */
	public function update_related_order_line_item( $booking_id ) {

		$booking = jet_abaf()->db->get_booking_by( 'booking_id', $booking_id );

		if ( ! $booking ) {
			return;
		}

		$order = $this->get_booking_related_order( $booking_id, $booking );

		if ( ! $order ) {
			return;
		}

		if ( ! empty( $booking['status'] ) ) {
			if ( 'refunded' === $booking['status'] ) {
				$line_items    = [];
				$refund_amount = 0;
				$refund_reason = '';

				foreach ( $order->get_items() as $item_id => $item ) {
					if ( absint( $booking_id ) === absint( $item->get_meta( '__jet_booking_id' ) ) ) {
						if ( ! $order->get_qty_refunded_for_item( $item_id ) ) {
							$item_total    = wc_get_order_item_meta( $item_id, '_line_total' );
							$refund_amount = wc_format_decimal( $refund_amount ) + wc_format_decimal( $item_total );
							$refund_reason = sprintf( __( 'Booking #%s status changed to %s.', 'jet-booking' ), $booking_id, ucfirst( $booking['status'] ) );

							$line_items[ $item_id ] = [
								'qty'          => wc_get_order_item_meta( $item_id, '_qty' ),
								'refund_total' => wc_format_decimal( $item_total ),
							];

							$item->update_meta_data( '__jet_booking_status', $booking['status'] );
							$item->save();
						} else {
							return;
						}
					}
				}

				if ( ! empty( $line_items ) ) {
					wc_create_refund( [
						'amount'     => $refund_amount,
						'reason'     => $refund_reason,
						'order_id'   => $order->get_id(),
						'line_items' => $line_items,
					] );

					$order->add_order_note( sprintf( __( 'Refunded Booking #%s.', 'jet-booking' ), $booking_id ), false, true );
				}
			}

			$booking_status = $booking['status'];
			$status_keys    = jet_abaf()->statuses->get_statuses_ids();

			if ( array_search( $booking_status, $status_keys ) > array_search( $order->get_status(), $status_keys ) ) {
				foreach ( $order->get_items() as $item_id => $item ) {
					if ( absint( $booking_id ) !== absint( $item->get_meta( '__jet_booking_id' ) ) ) {
						if ( array_search( $booking_status, $status_keys ) > array_search( $item->get_meta( '__jet_booking_status' ), $status_keys ) && ! empty( $item->get_meta( '__jet_booking_status' ) ) ) {
							$booking_status = $item->get_meta( '__jet_booking_status' );
						}
					}
				}
			}

			if ( $booking_status !== $order->get_status() ) {
				remove_action( 'woocommerce_order_status_changed', [ jet_abaf()->wc, 'update_status_on_order_update' ], 10 );
				$order->update_status( $booking_status, '', true );
			}
		}

		$calculate_totals = wp_cache_get( 'calculate_booking_totals_' . $booking_id );
		$note             = '';

		foreach ( $order->get_items() as $item_id => $item ) {
			if ( absint( $booking_id ) === absint( $item->get_meta( '__jet_booking_id' ) ) ) {
				if ( ! empty( $booking['status'] ) && $booking['status'] !== $item->get_meta( '__jet_booking_status' ) ) {
					$note .= sprintf( __( '<br> - Status changed from %s to %s.', 'jet-booking' ), wc_get_order_status_name( $item->get_meta( '__jet_booking_status' ) ), wc_get_order_status_name( $booking['status'] ) );
					$item->update_meta_data( '__jet_booking_status', $booking['status'] );
				}

				if ( ! empty( $booking['apartment_id'] ) && absint( $booking['apartment_id'] ) !== $item->get_product_id() ) {
					$note .= sprintf( __( '<br> - Item changed from %s to %s.', 'jet-booking' ), get_the_title( $item->get_product_id() ), get_the_title( $booking['apartment_id'] ) );

					$item->set_product_id( $booking['apartment_id'] );
					$item->set_name( get_the_title( $booking['apartment_id'] ) );
				}

				if ( ! empty( $booking['check_in_date'] ) && $booking['check_in_date'] !== $item->get_meta( '__jet_booking_check_in_date' ) ) {
					$note .= sprintf( __( '<br> - Check in date changed from %s to %s.', 'jet-booking' ), date_i18n( get_option( 'date_format' ), $item->get_meta( '__jet_booking_check_in_date' ) ), date_i18n( get_option( 'date_format' ), $booking['check_in_date'] ) );
					$item->update_meta_data( '__jet_booking_check_in_date', $booking['check_in_date'] );
				}

				if ( ! empty( $booking['check_out_date'] ) && $booking['check_out_date'] !== $item->get_meta( '__jet_booking_check_out_date' ) ) {
					$note .= sprintf( __( '<br> - Check out date changed from %s to %s.', 'jet-booking' ), date_i18n( get_option( 'date_format' ), $item->get_meta( '__jet_booking_check_out_date' ) ), date_i18n( get_option( 'date_format' ), $booking['check_out_date'] ) );
					$item->update_meta_data( '__jet_booking_check_out_date', $booking['check_out_date'] );
				}

				if ( $calculate_totals ) {
					$price         = new Price( $booking['apartment_id'] );
					$booking_price = $price->get_booking_price( $booking );

					$item->set_subtotal( $booking_price );
					$item->set_total( $booking_price );
				}

				$item->save();
			}
		}

		if ( $calculate_totals ) {
			$old_total = $order->get_total();

			$order->calculate_totals();
			$order->save();

			if ( $old_total > $order->get_total() ) {
				$note .= sprintf( __( '<br> - Total price of the order decreased by %s.', 'jet-booking' ), jet_abaf()->wc->get_formatted_price( $old_total - $order->get_total() ) );
			} elseif ( $old_total < $order->get_total() ) {
				$note .= sprintf( __( '<br> - Total price of the order increased by %s.', 'jet-booking' ), jet_abaf()->wc->get_formatted_price( $order->get_total() - $old_total ) );
			}

			wp_cache_delete( 'calculate_booking_totals_' . $booking_id );
		}

		if ( ! empty( $note ) ) {
			$order->add_order_note( sprintf( __( 'Updated Booking #%s. %s', 'jet-booking' ), $booking_id, $note ), false, true );
		}

	}

	/**
	 * Maybe remove related order line item.
	 *
	 * Remove Woocommerce relater order line item for when deleting linked booking item.
	 *
	 * @since  3.0.0
	 *
	 * @param array $args Deletion arguments array.
	 *
	 * @return void
	 */
	public function maybe_remove_related_order_line_item( $args ) {

		if ( ! $this->booking_deleting ) {
			$this->booking_deleting = true;
		}

		if ( empty( $args['booking_id'] ) ) {
			return;
		}

		$order = $this->get_booking_related_order( $args['booking_id'] );

		if ( ! $order ) {
			return;
		}

		foreach ( $order->get_items() as $item_id => $item ) {
			if ( absint( $args['booking_id'] ) === absint( $item->get_meta( '__jet_booking_id' ) ) ) {
				$order->remove_item( $item_id );
			}
		}

		$order->add_order_note( sprintf( __( 'Deleted Booking #%s.', 'woocommerce' ), $args['booking_id'] ), false, true );
		$order->calculate_totals();
		$order->save();

		if ( ! $order->get_item_count() ) {
			wp_delete_post( $order->get_id() );
		}

	}

	/**
	 * Set booking related order.
	 *
	 * Set WooCommerce related order for booking instance item.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param array      $order_data Related order data array.
	 * @param string|int $booking_id Created booking ID.
	 *
	 * @return void
	 */
	public function set_booking_related_order( $order_data, $booking_id ) {

		$order = wc_create_order();

		if ( is_wp_error( $order ) ) {
			return;
		}

		$booking = jet_abaf()->db->inserted_booking;

		if ( ! $booking ) {
			return;
		}

		$price         = new Price( $booking['apartment_id'] );
		$booking_price = $price->get_booking_price( $booking );

		$order_item_id = $order->add_product(
			wc_get_product( $booking['apartment_id'] ),
			1,
			[
				'subtotal' => $booking_price,
				'total'    => $booking_price,
			]
		);

		$order->set_billing_address( [
			'first_name' => $order_data['firstName'] ?? '',
			'last_name'  => $order_data['lastName'] ?? '',
			'email'      => $order_data['email'] ?? '',
			'phone'      => $order_data['phone'] ?? '',
		] );

		$order->set_status( $booking['status'] );
		$order->calculate_totals();
		$order->save();

		$order_item     = $order->get_item( $order_item_id );
		$line_item_meta = [
			'__jet_booking_id'             => $booking_id,
			'__jet_booking_status'         => $order->get_status(),
			'__jet_booking_check_in_date'  => $booking['check_in_date'] ?? '',
			'__jet_booking_check_out_date' => $booking['check_out_date'] ?? '',
		];

		foreach ( $line_item_meta as $key => $value ) {
			$order_item->add_meta_data( $key, $value, true );
		}

		$order_item->save();

		jet_abaf()->db->update_booking( $booking_id, [ 'order_id' => $order->get_id() ] );

	}

	/**
	 * Get booking related order.
	 *
	 * Return booking related WooCommerce object instance.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param int   $booking_id Booking ID.
	 * @param array $booking    Booking item params array.
	 *
	 * @return bool|\WC_Order|\WC_Order_Refund
	 */
	public function get_booking_related_order( $booking_id = 0, $booking = [] ) {

		if ( empty( $booking ) ) {
			$booking = jet_abaf()->db->get_booking_by( 'booking_id', $booking_id );
		}

		if ( ! $booking ) {
			return false;
		}

		$order_id = ! empty( $booking['order_id'] ) ? absint( $booking['order_id'] ) : false;

		if ( ! $order_id ) {
			return false;
		}

		return wc_get_order( $order_id );

	}

}
