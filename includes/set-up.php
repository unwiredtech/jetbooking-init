<?php

namespace JET_ABAF;

use JET_ABAF\Formbuilder_Plugin\Form_Manager;
use JET_ABAF\Formbuilder_Plugin\Jfb_Plugin as Builder_Plugin;

class Set_Up {

	private $setup_page   = null;
	private $success_page = null;

	public function __construct() {
		add_filter( 'jet-abaf/dashboard/helpers/page-config/config', [ $this, 'check_setup' ] );
		add_action( 'wp_ajax_jet_abaf_setup', [ $this, 'process_setup' ] );
	}

	/**
	 * Process setup.
	 *
	 * Setup booking functionalities.
	 *
	 * @since  1.0.0
	 * @since  2.6.2 Added `nonce` security check.
	 * @since  2.8.0 Removed `related_post_type_column` settings handling.
	 * @since  3.0.0 Refactored.
	 * @access public
	 *
	 * @return void
	 */
	public function process_setup() {

		if ( empty( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], 'jet-abaf-set-up' ) ) {
			wp_send_json_error( [
				'message' => __( 'Security check failed.', 'jet-booking' ),
			] );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [
				'message' => __( 'Access denied. Not enough permissions.', 'jet-booking' ),
			] );
		}

		$setup_data = ! empty( $_REQUEST['setup_data'] ) ? $_REQUEST['setup_data'] : [];

		if ( ! isset( $setup_data['booking_mode'] ) ) {
			$setup_data['booking_mode'] = 'plain';
		}

		if ( ! isset( $setup_data['wc_integration'] ) ) {
			$setup_data['wc_integration'] = false;
		}

		if ( ! isset( $setup_data['wc_sync_orders'] ) ) {
			$setup_data['wc_sync_orders'] = false;
		}

		if ( ! isset( $setup_data['related_post_type'] ) ) {
			$setup_data['related_post_type'] = false;
		}

		// Clear existing settings before processing set up
		jet_abaf()->settings->clear();

		$create_form  = false;
		$bool         = [ 'wc_integration', 'wc_sync_orders', 'create_single_form' ];
		$form_actions = [ 'create_single_form' ];

		if ( ! empty( $setup_data ) ) {
			foreach ( $setup_data as $setting => $value ) {
				if ( in_array( $setting, $bool ) ) {
					$value = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
				} else {
					$value = is_array( $value ) ? $value : esc_attr( $value );
				}

				if ( jet_abaf()->settings->setting_registered( $setting ) ) {
					jet_abaf()->settings->update( $setting, $value, false );
				} elseif ( in_array( $setting, $form_actions ) && $value ) {
					$create_form = $value;
				}
			}
		}

		if ( 'plain' === $setup_data['booking_mode'] && ! empty( $_REQUEST['db_columns'] ) ) {
			$columns_names = [];

			foreach ( $_REQUEST['db_columns'] as $key => $column ) {
				if ( ! in_array( $column['column'], $columns_names ) && ! empty( $column['column'] ) ) {
					$columns_names[] = $column['column'];
				} else {
					unset( $_REQUEST['db_columns'][$key] );
				}
			}

			jet_abaf()->settings->update( 'additional_columns', $_REQUEST['db_columns'], false );
		}

		jet_abaf()->settings->hook_db_columns();
		jet_abaf()->db->bookings->create_table( true );
		jet_abaf()->db->units->create_table( true );
		jet_abaf()->settings->update( 'is_set', true, false );
		jet_abaf()->settings->write();

		if ( 'plain' === $setup_data['booking_mode'] ) {
			$created_form = [];

			if ( $create_form ) {
				if ( function_exists( 'jet_form_builder' ) ) {
					$created_form = Form_Manager::instance()->insert_form( 'create_single_form' );
				} else {
					$result = Builder_Plugin::install_and_activate();

					if ( $result['success'] ) {
						jet_form_builder_init();

						jet_form_builder()->init_components();
						jet_form_builder()->post_type->register_post_type();

						$created_form = Form_Manager::instance()->insert_form( 'create_single_form' );
					}
				}
			}

			$bookings_cpt = jet_abaf()->settings->get( 'apartment_post_type' );
			$orders_cpt   = jet_abaf()->settings->get( 'related_post_type' );
			$product_id   = jet_abaf()->settings->get( 'wc_product_id' );

			$data = [
				'instances_page' => $bookings_cpt ? add_query_arg( [ 'post_type' => $bookings_cpt ], admin_url( 'edit.php' ) ) : false,
				'orders_page'    => $orders_cpt ? add_query_arg( [ 'post_type' => $orders_cpt ], admin_url( 'edit.php' ) ) : false,
				'wc_integration' => [
					'enabled'      => jet_abaf()->settings->get( 'wc_integration' ),
					'product_link' => $product_id ? get_edit_post_link( $product_id, 'url' ) : false,
					'orders_page'  => add_query_arg( [ 'post_type' => 'shop_order' ], admin_url( 'edit.php' ) ),
				],
				'form'           => $created_form,
			];
		} else {
			$data = [
				'product_link'  => add_query_arg( [ 'post_type' => 'product', 'jet_booking_product' => 1 ], admin_url( 'post-new.php' ) ),
				'bookings_page' => add_query_arg( [ 'page' => 'jet-abaf-bookings' ], admin_url( 'admin.php' ) ),
			];
		}

		wp_send_json_success( wp_parse_args( $data, [
			'settings_url' => $this->success_page->get_url(),
		] ) );

	}

	/**
	 * Register setup page.
	 *
	 * Register setup page for the plugin. If page already registered will throw the error.
	 *
	 * @access public
	 *
	 * @param object $setup_page Dashboard page instance.
	 *
	 * @return void
	 */
	public function register_setup_page( $setup_page ) {
		if ( null !== $this->setup_page ) {
			trigger_error( 'Setup page is already registered!' );
		} else {
			$this->setup_page = $setup_page;
		}
	}

	/**
	 * Register setup success page.
	 *
	 * Register setup success page for the plugin.
	 *
	 * @access public
	 *
	 * @param object $success_page Dashboard page instance.
	 *
	 * @return void
	 */
	public function register_setup_success_page( $success_page ) {
		if ( null !== $this->success_page ) {
			trigger_error( 'Setup page is already registered!' );
		} else {
			$this->success_page = $success_page;
		}
	}

	/**
	 * Check setup.
	 *
	 * Check if plugin is correctly configured and pass this data into appropriate localized data.
	 *
	 * @since  1.0.0
	 * @since  3.0.0 Refactored.
	 * @access public
	 *
	 * @param array $args List of arguments.
	 *
	 * @return array
	 */
	public function check_setup( $args = [] ) {

		$args['setup'] = [
			'is_set'    => jet_abaf()->settings->get( 'is_set' ) || jet_abaf()->db->bookings->is_table_exists(),
			'setup_url' => $this->setup_page->get_url(),
		];

		if ( jet_abaf()->dashboard->is_page_now( $this->setup_page ) ) {
			$args['post_types'] = jet_abaf()->tools->get_post_types_for_js();
			$args['db_fields']  = jet_abaf()->db->get_default_fields();

			$args['reset'] = [
				'is_reset'  => ! empty( $_GET['jet_abaf_reset'] ),
				'reset_url' => add_query_arg( [ 'jet_abaf_reset' => 1 ], $this->setup_page->get_url() ),
			];
		}

		return $args;

	}

}