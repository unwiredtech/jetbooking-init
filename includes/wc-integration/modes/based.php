<?php

namespace JET_ABAF\WC_Integration\Modes;

use JET_ABAF\WC_Integration\WC_Cart_Manager;
use JET_ABAF\WC_Integration\WC_Order_Manager;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Based {

	/**
	 * Product type.
	 *
	 * Hold the name of custom product type.
	 *
	 * @since  3.0.0
	 * @access private
	 *
	 * @var string
	 */
	private $product_type = 'jet_booking';

	public function __construct() {

		// Returns apartment post type as `product`.
		add_filter( 'jet-booking/settings/get/apartment_post_type', function () {
			return 'product';
		} );

		// Register booking product type.
		add_action( 'init', [ $this, 'register_custom_product_type' ] );
		add_filter( 'woocommerce_product_class', [ $this, 'woocommerce_custom_product_class' ], 10, 2 );

		// Display custom product type in Product data selector dropdown.
		add_filter( 'product_type_selector', [ $this, 'product_type_selector' ] );

		// Initialize product creation as JetBooking product type.
		add_filter( 'woocommerce_product_type_query', [ $this, 'maybe_override_product_type' ], 10, 2 );

		// Initialize and handle product edit tabs.
		add_filter( 'woocommerce_product_data_tabs', [ $this, 'product_data_tabs' ] );
		add_action( 'woocommerce_product_options_general_product_data', [ $this, 'booking_data_panel' ] );

		// Display booking meta boxes only for booking product type.
		add_filter( 'postbox_classes_product_jet-abaf', [ $this, 'meta_box_classes' ] );
		add_filter( 'postbox_classes_product_jet-abaf-units', [ $this, 'meta_box_classes' ] );
		add_filter( 'postbox_classes_product_jet_abaf_configuration', [ $this, 'meta_box_classes' ] );
		add_filter( 'postbox_classes_product_jet_abaf_custom_schedule', [ $this, 'meta_box_classes' ] );
		add_filter( 'postbox_classes_product_jet_abaf_price', [ $this, 'meta_box_classes' ] );

		// Save product custom meta data.
		add_action( 'woocommerce_admin_process_product_object', [ $this, 'save_custom_meta_data' ], 20 );

		// Display add to cart button for single and loop appearances.
		add_action( 'woocommerce_jet_booking_add_to_cart', [ $this, 'display_add_to_cart_button' ] );

		// Display date picker form.
		add_action( 'woocommerce_before_add_to_cart_button', [ $this, 'before_add_to_cart_button' ] );
		add_action( 'woocommerce_after_add_to_cart_button', [ $this, 'after_add_to_cart_button' ] );

		// Price calculation and display after date selection.
		add_action( 'wp_ajax_jet_booking_product_set_total_price', [ $this, 'set_total_price' ] );
		add_action( 'wp_ajax_nopriv_jet_booking_product_set_total_price', [ $this, 'set_total_price' ] );

		// Display only booking products in booking admin area.
		add_filter( 'jet-booking/tools/post-type-args', [ $this, 'set_additional_post_type_args' ] );

		// Initialize datepicker functionality in quick view popup.
		add_action( 'wp_footer', [ $this, 'init_quick_view_datepicker' ] );

		require_once JET_ABAF_PATH . 'includes/wc-integration/class-wc-cart-manager.php';
		require_once JET_ABAF_PATH . 'includes/wc-integration/class-wc-order-manager.php';

		new WC_Cart_Manager();
		new WC_Order_Manager();

	}

	/**
	 * Register custom product type.
	 *
	 * Includes files that contains custom product type logic.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function register_custom_product_type() {
		require_once JET_ABAF_PATH . 'includes/wc-integration/class-wc-product-jet-booking.php';
	}

	/**
	 * WooCommerce custom product class.
	 *
	 * Return class name for custom product type.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param string $classname    Extended class name to return.
	 * @param string $product_type Product type.
	 *
	 * @return mixed|string
	 */
	public function woocommerce_custom_product_class( $classname, $product_type ) {

		if ( $this->product_type === $product_type ) {
			$classname = 'WC_Product_Jet_Booking';
		}

		return $classname;

	}

	/**
	 * Add custom product type.
	 *
	 * Add new custom product type to product data select dropdown.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param array $types Array containing product types.
	 *
	 * @return mixed
	 */
	public function product_type_selector( $types ) {
		$types[ $this->product_type ] = __( 'JetBooking product', 'jet-booking' );

		return $types;
	}

	/**
	 * Maybe override product type.
	 *
	 * Override product type for New Product screen, if a request parameter is set.
	 *
	 * @param string $override Product Type
	 * @param int    $product_id
	 *
	 * @return string
	 */
	public function maybe_override_product_type( $override, $product_id ) {

		if ( ! empty( $_REQUEST['jet_booking_product'] ) ) {
			return $this->product_type;
		}

		return $override;

	}

	/**
	 * Product data tabs.
	 *
	 * Handle custom product type data tabs.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param array $tabs Existing tabs list.
	 *
	 * @return mixed
	 */
	public function product_data_tabs( $tabs ) {

		$tabs['shipping']['class'][]       = 'hide_if_' . $this->product_type;
		$tabs['linked_product']['class'][] = 'hide_if_' . $this->product_type;
		$tabs['attribute']['class'][]      = 'hide_if_' . $this->product_type;
		$tabs['advanced']['class'][]       = 'hide_if_' . $this->product_type;

		return $tabs;

	}

	/**
	 * Booking data.
	 *
	 * Show the booking product data information.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function booking_data_panel() {
		if ( wc_tax_enabled() ) : ?>
			<script type="text/javascript">
                jQuery( '._tax_status_field' ).closest( '.show_if_simple' ).addClass( 'show_if_jet_booking' );
			</script>
		<?php else : ?>
			<div class="options_group show_if_<?php echo $this->product_type ?>">
				<p> <?php _e( 'JetBooking product types not require default product data panels. Instead, you can control your booking instance with the help of custom meta boxes, improving user experience and streamlining management.', 'jet-booking' ); ?> </p>
				<p> <?php _e( 'Custom meta boxes showcase relevant information and settings, ensuring efficiency and simplicity in booking product creation. This customization allows for a more focused and intuitive interface for managing and configuring your booking products.', 'jet-booking' ); ?> </p>
			</div>
		<?php endif;
	}

	/**
	 * Meta box classes.
	 *
	 * Returns the list of classes to be used by a meta box.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param array $classes Array of meta box classes.
	 *
	 * @return array
	 */
	public function meta_box_classes( $classes ) {
		$classes[] = 'show_if_' . $this->product_type;

		return $classes;
	}

	/**
	 * Save custom meta data.
	 *
	 * Save custom product meta boxes data.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param \WC_Product $product Product object.
	 *
	 * @return void
	 */
	public function save_custom_meta_data( $product ) {
		if ( $this->is_booking_product( $product ) ) {
			$price = get_post_meta( $product->get_id(), '_apartment_price', true );

			if ( ! empty( $price ) ) {
				$product->set_props( [
					'price'         => $price,
					'regular_price' => $price,
				] );
			}
		}
	}

	/**
	 * Display add to cart button.
	 *
	 * Display single product add to cart button for custom JetBooking product type.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function display_add_to_cart_button() {
		do_action( 'woocommerce_simple_add_to_cart' );
	}

	/**
	 * Before add to cart button.
	 *
	 * Adding JetBooking form open wrapper and custom booking (date picker) input field(s) to the single booking
	 * product page cart form.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function before_add_to_cart_button() {

		global $product;

		if ( ! $product || ! $this->is_booking_product( $product ) ) {
			return;
		}

		$layout          = jet_abaf()->settings->get( 'field_layout' );
		$field_format    = jet_abaf()->settings->get( 'field_date_format' );
		$field_separator = jet_abaf()->settings->get( 'field_separator' );
		$field_classes   = [ 'jet-abaf-field__input', 'input-text', 'text' ];

		$args = [
			'name'     => 'jet_abaf_field',
			'required' => true,
		];

		if ( $field_separator ) {
			if ( 'space' === $field_separator ) {
				$field_separator = ' ';
			}

			$field_format = str_replace( '-', $field_separator, $field_format );
		}

		$default_value = '';
		$options       = jet_abaf()->tools->get_field_default_value( $default_value, $field_format, $product->get_id() );

		jet_abaf()->assets->enqueue_deps( $product->get_id() );

		wp_localize_script( 'jquery-date-range-picker', 'JetABAFInput', [
			'layout'        => $layout,
			'field_format'  => $field_format,
			'start_of_week' => jet_abaf()->settings->get( 'field_start_of_week' ),
			'options'       => $options,
		] );

		$checkin  = '';
		$checkout = '';

		if ( ! empty( $options ) ) {
			$checkin  = $options['checkin'] ?? '';
			$checkout = $options['checkout'] ?? '';

			if ( $checkin && $checkout ) {
				$default_value = $checkin . ' - ' . $checkout;
			}
		}

		echo '<div class="jet-booking-form">';
		echo '<div class="jet-abaf-product-check-in-out">';

		if ( 'single' === $layout ) {
			$label       = jet_abaf()->settings->get( 'field_label' );
			$placeholder = jet_abaf()->settings->get( 'field_placeholder' );

			if ( $label ) {
				echo '<div class="jet-abaf-field__label" >';
				echo '<label for="jet_abaf_field">';
				echo $label;

				if ( ! empty( $args['required'] ) ) {
					echo '<span class="jet-abaf-field__required">&nbsp;<abbr class="required" title="required">*</abbr></span>';
				}

				echo '</label>';
				echo '</div>';
			}

			include JET_ABAF_PATH . 'templates/form-field-single.php';
		} else {
			$fields_position      = jet_abaf()->settings->get( 'field_position' );
			$checkin_label        = jet_abaf()->settings->get( 'check_in_field_label' );
			$checkin_placeholder  = jet_abaf()->settings->get( 'check_in_field_placeholder' );
			$checkout_label       = jet_abaf()->settings->get( 'check_out_field_label' );
			$checkout_placeholder = jet_abaf()->settings->get( 'check_out_field_placeholder' );
			$label_classes        = [ 'jet-abaf-separate-field__label' ];
			$required_classes     = [ 'jet-abaf-field__required' ];
			$col_classes          = [ 'jet-abaf-separate-field' ];

			if ( 'list' === $fields_position ) {
				$col_classes[] = 'jet-abaf-separate-field__list';
			} else {
				$col_classes[] = 'jet-abaf-separate-field__inline';
			}

			include JET_ABAF_PATH . 'templates/form-field-separate.php';
		}

		$desc = jet_abaf()->settings->get( 'field_description' );

		if ( $desc ) {
			echo '<div class="jet-abaf-field__desc" ><small>' . $desc . '</small></div>';
		}

		echo '</div>';
		echo '<div class="jet-abaf-product-total"></div>';

	}

	/**
	 * After add to cart button.
	 *
	 * Adding JetBooking form close wrapper to the single booking product page cart form.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function after_add_to_cart_button() {

		global $product;

		if ( ! $product || ! $this->is_booking_product( $product ) ) {
			return;
		}

		echo '</div>';

	}

	/**
	 * Set total price.
	 *
	 * Set total cost for single booking product.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function set_total_price() {

		$response = [];
		$total    = $_POST['total'] ?? 0;

		ob_start();

		echo '<div class="jet-abaf-product-total__label">' . __( 'Total:', 'jet-booking' ) . '</div>';
		echo '<div class="jet-abaf-product-total__price">' . wc_price( $total ) . '</div>';

		$response['html'] = ob_get_clean();

		wp_send_json_success( $response );

	}

	/**
	 * Set additional post type args.
	 *
	 * Returns a list of arguments to display only booking products.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param array $args List of post arguments.
	 *
	 * @return mixed
	 */
	public function set_additional_post_type_args( $args ) {

		$args['tax_query'][] = [
			'taxonomy' => 'product_type',
			'field'    => 'slug',
			'terms'    => $this->product_type,
		];

		return $args;

	}

	/**
	 * Init quick view datepicker.
	 *
	 * Initialize datepicker functionality in JetWooBuilder quick view popup.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @return void
	 */
	function init_quick_view_datepicker() {

		if ( ! function_exists( 'jet_woo_builder' ) || ! function_exists( 'jet_popup' ) ) {
			return;
		}

		$data = "
			jQuery( window ).on( 'jet-popup/render-content/ajax/success', function ( _, popupData ) {
				if ( ! popupData.data.isJetWooBuilder ) {
					return;
				}
		
				setTimeout( function() {
					JetBooking.initializeCheckInOut( null, 'form.cart' );
				}, 500 );
			} );
		";

		wp_add_inline_script( 'jet-popup-frontend', $data );

	}

	/**
	 * Is booking product.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param object $obj Object instance to check.
	 *
	 * @return bool
	 */
	public function is_booking_product( $obj ) {
		return is_object( $obj ) && is_a( $obj, 'WC_Product' ) && $this->product_type === $obj->get_type();
	}

}
