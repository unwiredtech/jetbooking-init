<?php

namespace JET_ABAF\Formbuilder_Plugin;

use \JET_ABAF\Formbuilder_Plugin\Actions\Action_Manager;
use \JET_ABAF\Formbuilder_Plugin\Blocks\Blocks_Manager;
use \Jet_Form_Builder\Classes\Tools;
use \Jet_Form_Builder\Presets\Preset_Manager;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Jfb_Plugin {

	const PACKAGE = 'https://downloads.wordpress.org/plugin/jetformbuilder.zip';
	const PLUGIN = 'jetformbuilder/jet-form-builder.php';

	public function __construct() {

		new Blocks_Manager();
		new Action_Manager();
		new Manage_Calculated_Data();
		new Gateway_Manager();

		// Register form generators.
		add_filter( 'jet-form-builder/forms/options-generators', function ( $generators ) {
			$generators[] = new Generators\Get_From_Booking_Statuses();

			return $generators;
		} );

		// Register booking preset config.
		add_action( 'jet-form-builder/editor/preset-config', [ $this, 'preset_configuration' ] );

		// Register form preset sources.
		if ( class_exists( '\Jet_Form_Builder\Presets\Preset_Manager' ) && method_exists( Preset_Manager::instance(), 'register_source_type' ) ) {
			Preset_Manager::instance()->register_source_type( new Presets\Source_Booking() );
		}

	}

	/**
	 * Returns modified preset configurations.
	 *
	 * @since 3.3.0
	 *
	 * @param array $config List of default presets configurations.
	 *
	 * @return mixed
	 */
	public function preset_configuration( $config ) {

		$config['global_fields'][0]['options'][] = [
			'value' => 'jet_booking',
			'label' => __( 'JetBooking', 'jet-booking' ),
		];

		$config['global_fields'][] = [
			'name'      => 'booking_source',
			'type'      => 'select',
			'label'     => __( 'Get Booking ID From:', 'jet-booking' ),
			'options'   => Tools::with_placeholder( [
				[
					'value' => 'current_post',
					'label' => __( 'Current Post', 'jet-booking' ),
				],
				[
					'value' => 'query_var',
					'label' => __( 'URL Query Variable', 'jet-booking' ),
				],
			] ),
			'condition' => [
				'field' => 'from',
				'value' => 'jet_booking',
			],
		];

		$config['global_fields'][] = [
			'name'             => 'query_var',
			'type'             => 'text',
			'label'            => __( 'Query Variable Name:', 'jet-booking' ),
			'parent_condition' => [
				'field' => 'from',
				'value' => 'jet-booking',
			],
			'condition'        => [
				'field' => 'booking_source',
				'value' => 'query_var',
			],
		];

		$config['map_fields'][] = [
			'name'             => 'prop',
			'type'             => 'select',
			'label'            => __( 'Booking Property:', 'jet-booking' ),
			'options'          => Tools::with_placeholder( [
				[
					'value' => 'status',
					'label' => __( 'Booking Status', 'jet-booking' ),
				],
				[
					'value' => 'apartment_id',
					'label' => __( 'Booking Apartment', 'jet-booking' ),
				],
				[
					'value' => 'dates',
					'label' => __( 'Booking Dates', 'jet-booking' ),
				],
				[
					'value' => 'additional_column',
					'label' => __( 'Booking Additional Column', 'jet-booking' ),
				],
			] ),
			'parent_condition' => [
				'field' => 'from',
				'value' => 'jet_booking',
			],
		];

		$additional_columns = jet_abaf()->tools->prepare_list_for_js( jet_abaf()->settings->get_clean_columns() );

		$config['map_fields'][] = [
			'name'             => 'column_name',
			'type'             => 'select',
			'label'            => __( 'Column Name:', 'jet-booking' ),
			'options'          => Tools::with_placeholder( $additional_columns ),
			'parent_condition' => [
				'field' => 'from',
				'value' => 'jet_booking',
			],
			'condition'        => [
				'field' => 'prop',
				'value' => 'additional_column',
			],
		];

		return $config;

	}

	public static function get_path( $path = '' ) {
		return JET_ABAF_PATH . '/includes/formbuilder-plugin/' . $path;
	}

	public static function install_and_activate() {
		if ( file_exists( WP_PLUGIN_DIR . '/' . self::PLUGIN ) ) {
			return self::activate_plugin();
		}

		$installed = self::install_plugin();
		if ( $installed['success'] ) {
			$activated = self::activate_plugin();

			if ( $activated['success'] && ! function_exists( 'jet_form_builder' ) ) {
				require_once WP_PLUGIN_DIR . '/' . self::PLUGIN;
			}

			return $activated;
		}

		return $installed;
	}

	public static function activate_plugin() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return [
				'success' => false,
				'message' => esc_html__( 'Sorry, you are not allowed to install plugins on this site.', 'jet-form-builder' ),
				'data'    => [],
			];
		}

		$activate = null;

		if ( ! is_plugin_active( self::PLUGIN ) ) {
			$activate = activate_plugin( self::PLUGIN );
		}

		return is_null( $activate ) ? [ 'success' => true ] : [ 'success' => false ];
	}

	public static function install_plugin() {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return [
				'success' => false,
				'message' => esc_html__( 'Sorry, you are not allowed to install plugins on this site.', 'jet-form-builder' ),
				'data'    => [],
			];
		}

		include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );

		$skin     = new \WP_Ajax_Upgrader_Skin();
		$upgrader = new \Plugin_Upgrader( $skin );
		$result   = $upgrader->install( self::PACKAGE );

		if ( is_wp_error( $result ) ) {
			$status['errorCode']    = $result->get_error_code();
			$status['errorMessage'] = $result->get_error_message();

			return [
				'success' => false,
				'message' => $result->get_error_message(),
				'data'    => [],
			];
		} elseif ( is_wp_error( $skin->result ) ) {
			$status['errorCode']    = $skin->result->get_error_code();
			$status['errorMessage'] = $skin->result->get_error_message();

			return [
				'success' => false,
				'message' => $skin->result->get_error_message(),
				'data'    => [],
			];
		} elseif ( $skin->get_errors()->get_error_code() ) {
			$status['errorMessage'] = $skin->get_error_messages();

			return [
				'success' => false,
				'message' => $skin->get_error_messages(),
				'data'    => [],
			];
		} elseif ( is_null( $result ) ) {
			global $wp_filesystem;

			$status['errorMessage'] = 'Unable to connect to the filesystem. Please confirm your credentials.';

			// Pass through the error from WP_Filesystem if one was raised.
			if ( $wp_filesystem instanceof \WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->get_error_code() ) {
				$status['errorMessage'] = esc_html( $wp_filesystem->errors->get_error_message() );
			}

			return [
				'success' => false,
				'message' => $status['errorMessage'],
				'data'    => [],
			];
		}

		return [
			'success' => true,
			'message' => esc_html__( 'JetFormBuilder has been installed.', 'jet-form-builder' ),
		];
	}

}