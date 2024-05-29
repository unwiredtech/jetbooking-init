<?php

namespace JET_ABAF;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Settings {

	/**
	 * Default settings array
	 *
	 * @var array
	 */
	private $defaults = [
		'is_set'                      => false,
		'hide_columns_manager'        => false,
		'related_post_type'           => '',
		'wc_integration'              => false,
		'wc_product_id'               => '',
		'wc_sync_orders'              => false,
		'filters_store_type'          => 'session',
		'additional_columns'          => [],
		'apartment_post_type'         => '',
		'booking_period'              => 'per_nights',
		'allow_checkout_only'         => false,
		'weekly_bookings'             => false,
		'week_offset'                 => '',
		'one_day_bookings'            => false,
		'start_day_offset'            => '',
		'min_days'                    => '',
		'max_days'                    => '',
		'use_custom_labels'           => false,
		'hide_setup'                  => false,
		'ical_synch'                  => false,
		'remove_temporary_bookings'   => false,
		'remove_interval'             => 'daily',
		'booking_cancellation'        => false,
		'cancellation_limit'          => 1,
		'cancellation_unit'           => 'week',
		'labels_booked'               => 'Sold out',
		'labels_only_checkout'        => 'Only checkout',
		'labels_selected'             => 'Chosen:',
		'labels_nights'               => 'Nights',
		'labels_days'                 => 'Days',
		'labels_apply'                => 'Close',
		'labels_week_1'               => 'Mon',
		'labels_week_2'               => 'Tue',
		'labels_week_3'               => 'Wed',
		'labels_week_4'               => 'Thu',
		'labels_week_5'               => 'Fri',
		'labels_week_6'               => 'Sat',
		'labels_week_7'               => 'Sun',
		'labels_month_name'           => 'January, February, March, April, May, June, July, August, September, October, November, December',
		'labels_past'                 => 'Past',
		'labels_previous'             => 'Previous',
		'labels_prev_week'            => 'Week',
		'labels_prev_month'           => 'Month',
		'labels_prev_year'            => 'Year',
		'labels_default'              => 'Please select a date range',
		'synch_interval'              => 'daily',
		'synch_interval_hours'        => '00',
		'synch_interval_mins'         => '00',
		'days_off'                    => [],
		'disable_weekday_1'           => false,
		'check_in_weekday_1'          => false,
		'check_out_weekday_1'         => false,
		'disable_weekday_2'           => false,
		'check_in_weekday_2'          => false,
		'check_out_weekday_2'         => false,
		'disable_weekday_3'           => false,
		'check_in_weekday_3'          => false,
		'check_out_weekday_3'         => false,
		'disable_weekday_4'           => false,
		'check_in_weekday_4'          => false,
		'check_out_weekday_4'         => false,
		'disable_weekday_5'           => false,
		'check_in_weekday_5'          => false,
		'check_out_weekday_5'         => false,
		'disable_weekend_1'           => false,
		'check_in_weekend_1'          => false,
		'check_out_weekend_1'         => false,
		'disable_weekend_2'           => false,
		'check_in_weekend_2'          => false,
		'check_out_weekend_2'         => false,
		'booking_hold_time'           => '300',
		'field_layout'                => 'single',
		'field_position'              => 'inline',
		'field_label'                 => '',
		'field_placeholder'           => 'YYYY-MM-DD',
		'check_in_field_label'        => '',
		'check_in_field_placeholder'  => 'YYYY-MM-DD',
		'check_out_field_label'       => '',
		'check_out_field_placeholder' => 'YYYY-MM-DD',
		'field_description'           => '',
		'field_date_format'           => 'YYYY-MM-DD',
		'field_separator'             => '-',
		'field_start_of_week'         => 'monday',
		'booking_mode'                => 'plain',
	];

	/**
	 * Settings DB key
	 *
	 * @var string
	 */
	private $key = 'jet-abaf';

	/**
	 * Stored settings cache
	 *
	 * @var null
	 */
	private $settings = null;

	/**
	 * Stored labels
	 *
	 * @var null
	 */
	private $labels = null;

	/**
	 * Booking configuration setting holder.
	 *
	 * @var bool
	 */
	public $config_settings = false;

	/**
	 * Booked days holder.
	 *
	 * @var array
	 */
	private $booked_dates = [];

	public function __construct() {

		add_action( 'wp_ajax_jet_abaf_save_settings', [ $this, 'ajax_save_settings' ] );
		add_action( 'wp_ajax_jet_abaf_process_tables', [ $this, 'ajax_process_tables' ] );

		if ( is_admin() && ! wp_doing_ajax() ) {
			$this->hook_db_columns();
		}

	}

	/**
	 * AJAX save settings.
	 *
	 * Save settings by AJAX request.
	 *
	 * @since  1.0.0
	 * @since  2.6.2 Added `nonce` security check.
	 * @since  2.8.0 Small optimization.
	 * @since  3.0.0 Added reload for some setting change.
	 * @access public
	 *
	 * @return void
	 */
	public function ajax_save_settings() {

		if ( empty( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], 'jet-abaf-settings' ) ) {
			wp_send_json_error( [
				'message' => __( 'Security check failed.', 'jet-booking' ),
			] );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [
				'message' => __( 'Access denied. Not enough permissions.', 'jet-booking' ),
			] );
		}

		$data     = ! empty( $_REQUEST['settings'] ) ? $_REQUEST['settings'] : [];
		$settings = wp_parse_args( $data, $this->defaults );

		if ( empty( $settings ) ) {
			wp_send_json_error( [
				'message' => __( 'Empty data.', 'jet-booking' ),
			] );
		}

		if ( ! isset( $settings['additional_columns'] ) ) {
			$settings['additional_columns'] = [];
		}

		$response        = [ 'message' => __( 'Settings saved!', 'jet-booking' ) ];
		$reload_settings = [ 'booking_mode', 'ical_synch' ];

		foreach ( $settings as $setting => $value ) {
			if ( $this->setting_registered( $setting ) ) {
				$value = $this->sanitize_setting( $setting, $value );
				$value = apply_filters( 'jet-booking/settings/on-save/value', $value, $setting );

				if ( ! jet_abaf()->wc->has_woocommerce() ) {
					if ( 'is_set' !== $setting && $value !== $this->get( $setting ) ) {
						if ( 'booking_mode' !== $setting ) {
							$settings['booking_mode'] = 'plain';
						} else {
							$value = 'plain';
						}
					}

					if ( 'booking_mode' === $setting && 'wc_based' === $settings[ $setting ] ) {
						wp_send_json_error( [
							'message' => __( 'WooCommerce Based booking mode setting requires WooCommerce to be installed and activated.', 'jet-booking' ),
						] );
					}
				}

				if ( in_array( $setting, $reload_settings ) && $value !== $this->get( $setting ) ) {
					$response['message'] = __( 'Settings saved! <br> This setting requires page reloading. Page will be reloaded automatically in a few seconds.', 'jet-booking' );
					$response['reload']  = true;
				}

				$this->update( $setting, $value, false );
			}
		}

		do_action( 'jet-booking/settings/on-ajax-save', $this );

		$this->write();

		wp_send_json_success( $response );

	}

	/**
	 * AJAX process tables.
	 *
	 * AJAX callback for creating/saving DB tables.
	 *
	 * @since  1.0.0
	 * @since  2.6.2 Added `nonce` security check.
	 * @since  3.3.0 Refactored table creation part.
	 */
	public function ajax_process_tables() {

		if ( empty( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], 'jet-abaf-settings' ) ) {
			wp_send_json_error( [
				'message' => __( 'Security check failed.', 'jet-booking' ),
			] );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [
				'message' => __( 'Access denied. Not enough permissions.', 'jet-booking' ),
			] );
		}

		$message = __( 'DB tables created!', 'jet-booking' );

		ob_start();

		try {
			if ( ! jet_abaf()->db->units->is_table_exists() ) {
				jet_abaf()->db->units->create_table();
			}

			if ( jet_abaf()->db->bookings->is_table_exists() ) {
				$message = __( 'DB tables updated!', 'jet-booking' );
				jet_abaf()->db->update_columns_diff( $this->get_clean_columns() );
			} else {
				$this->hook_db_columns();
				jet_abaf()->db->bookings->create_table();
			}
		} catch ( \Exception $e ) {
			ob_get_clean();
			wp_send_json_error( [ 'message' => $e->getMessage() ] );
		}

		$warnings = ob_get_clean();

		if ( ! empty( $warnings ) ) {
			wp_send_json_error( [ 'message' => $warnings ] );
		} else {
			wp_send_json_success( [ 'message' => $message ] );
		}

	}

	/**
	 * Get preapred additional columns list
	 *
	 * @return array
	 */
	public function get_clean_columns() {

		$columns       = $this->get( 'additional_columns' );
		$clean_columns = array();

		if ( empty( $columns ) ) {
			return $clean_columns;
		}

		foreach ( $columns as $column ) {
			if ( ! empty( $column['column'] ) ) {
				$clean_columns[] = $this->sanitize_column_name( $column['column'] );
			}
		}

		return $clean_columns;

	}

	public function sanitize_column_name( $column ) {
		return sanitize_key( str_replace( array( ' ', '-' ), '_', $column ) );
	}

	/**
	 * Hook new DB columns.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function hook_db_columns() {

		$columns = $this->get_clean_columns();

		if ( empty( $columns ) ) {
			return;
		}

		add_filter( 'jet-abaf/db/additional-db-columns', function ( $db_columns ) use ( $columns ) {
			if ( empty( $db_columns ) || ! is_array( $db_columns ) ) {
				$db_columns = [];
			}

			foreach ( $columns as $column ) {
				if ( is_array( $column ) && ! empty( $column['column'] ) ) {
					$db_columns[] = $column['column'];
				} else {
					$db_columns[] = $column;
				}
			}

			return $db_columns;
		} );

	}

	/**
	 * Sanitize_settings.
	 *
	 * Sanitize updated bookings settings.
	 *
	 * @since  2.8.0 Added new schedules settings. Updates structure.
	 * @access public
	 *
	 * @param string $setting Settings key name.
	 * @param mixed  $value   Settings value.
	 *
	 * @return array|false|mixed
	 */
	public function sanitize_setting( $setting, $value ) {

		switch ( $setting ) {
			case 'additional_columns':
				$value = array_values( $value );
				break;

			case 'days_off':
				$value = is_array( $value ) ? wp_unslash( $value ) : false;
				break;

			case 'is_set':
			case 'use_custom_labels':
			case 'hide_setup':
			case 'hide_columns_manager':
			case 'wc_integration':
			case 'wc_sync_orders':
			case 'ical_synch':
			case 'remove_temporary_bookings':
			case 'booking_cancellation':
			case 'weekly_bookings':
			case 'allow_checkout_only':
			case 'one_day_bookings':
			case 'disable_weekday_1':
			case 'check_in_weekday_1':
			case 'check_out_weekday_1':
			case 'disable_weekday_2':
			case 'check_in_weekday_2':
			case 'check_out_weekday_2':
			case 'disable_weekday_3':
			case 'check_in_weekday_3':
			case 'check_out_weekday_3':
			case 'disable_weekday_4':
			case 'check_in_weekday_4':
			case 'check_out_weekday_4':
			case 'disable_weekday_5':
			case 'check_in_weekday_5':
			case 'check_out_weekday_5':
			case 'disable_weekend_1':
			case 'check_in_weekend_1':
			case 'check_out_weekend_1':
			case 'disable_weekend_2':
			case 'check_in_weekend_2':
			case 'check_out_weekend_2':
				$value = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
				break;

			case 'cancellation_limit':
				if ( 0 >= $value ) {
					$value = 1;
				}

				break;

			default:
				$value = wp_unslash( $value );
				break;
		}

		return $value;
	}

	/**
	 * Passe settings.
	 *
	 * Processes settings before localization.
	 *
	 * @since  2.4.7
	 * @access public
	 *
	 * @param array $settings Plugin settings list.
	 *
	 * @return mixed
	 */
	public function passe_settings( $settings ) {

		if ( ! empty( $settings ) && ! empty( $settings['days_off'] ) ) {
			$new_days_off = [];

			foreach ( $settings['days_off'] as $value ) {
				if ( ! isset( $value["date"] ) ) {
					$new_days_off[] = $value;
					continue;
				}

				$start          = $value["date"];
				$startTimeStamp = strtotime( $start );

				$new_days_off[] = [
					'start'          => $start,
					'startTimeStamp' => $startTimeStamp,
					'end'            => $start,
					'endTimeStamp'   => $startTimeStamp,
					'name'           => $value["name"],
					'type'           => 'days_off',
				];
			}

			$settings['days_off'] = $new_days_off;
		}

		return $settings;

	}

	/**
	 * Get labels.
	 *
	 * Return all available labels list.
	 *
	 * @access public
	 *
	 * @return mixed
	 */
	public function get_labels( $key = null ) {

		if ( null === $this->labels ) {
			$this->labels = [
				'booked'          => $this->get( 'labels_booked' ),
				'only-checkout'   => $this->get( 'labels_only_checkout' ),
				'selected'        => $this->get( 'labels_selected' ),
				'nights'          => $this->get( 'labels_nights' ),
				'days'            => $this->get( 'labels_days' ),
				'apply'           => $this->get( 'labels_apply' ),
				'week-1'          => $this->get( 'labels_week_1' ),
				'week-2'          => $this->get( 'labels_week_2' ),
				'week-3'          => $this->get( 'labels_week_3' ),
				'week-4'          => $this->get( 'labels_week_4' ),
				'week-5'          => $this->get( 'labels_week_5' ),
				'week-6'          => $this->get( 'labels_week_6' ),
				'week-7'          => $this->get( 'labels_week_7' ),
				'month-name'      => $this->get_array_from_string( $this->get( 'labels_month_name' ) ),
				'past'            => $this->get( 'labels_past' ),
				'previous'        => $this->get( 'labels_previous' ),
				'prev-week'       => $this->get( 'labels_prev_week' ),
				'prev-month'      => $this->get( 'labels_prev_month' ),
				'prev-year'       => $this->get( 'labels_prev_year' ),
				'default-default' => $this->get( 'labels_default' ),
			];
		}

		if ( ! $key ) {
			return $this->labels;
		} else {
			return $this->labels[ $key ] ?? null;
		}

	}

	/**
	 * Parse array from strig
	 *
	 * @return [type] [description]
	 */
	public function get_array_from_string( $string ) {
		$string = str_replace( ', ', ',', $string );

		return explode( ',', $string );
	}

	/**
	 * Get all.
	 *
	 * Return all settings and setup settings cache.
	 *
	 * @since  3.0.0 Refactored.
	 * @access public
	 *
	 * @return array|mixed|null
	 */
	public function get_all() {

		if ( null === $this->settings ) {
			$this->settings = get_option( $this->key, [] );
			$this->settings = wp_parse_args( $this->settings, $this->defaults );
			$this->settings = $this->passe_settings( $this->settings );

			$this->settings['additional_columns'] = ! empty( $this->settings['additional_columns'] ) ? array_values( $this->settings['additional_columns'] ) : [];
		}

		return $this->settings;

	}

	/**
	 * Get.
	 *
	 * Returns setting by key.
	 *
	 * @since  2.0.0
	 * @since  3.0.0 Refactored.
	 * @access public
	 *
	 * @param string $setting Setting key.
	 *
	 * @return mixed
	 */
	public function get( $setting ) {

		$settings = $this->get_all();

		if ( isset( $settings[ $setting ] ) ) {
			$value = $settings[ $setting ];
		} else {
			$value = $this->defaults[ $setting ] ?? false;
		}

		return apply_filters( 'jet-booking/settings/get/' . $setting, $value );

	}

	public function checkout_only_allowed() {
		return ( 'per_nights' === $this->get( 'booking_period' ) && $this->get( 'allow_checkout_only' ) );
	}

	/**
	 * Is per night bookings.
	 *
	 * Check if per nights booking mode enable.
	 *
	 * @since  2.8.0 Optimization.
	 *
	 * @return boolean
	 */
	public function is_per_nights_booking() {

		$period = $this->get( 'booking_period' );

		if ( ! $period || 'per_nights' === $period ) {
			return true;
		}

		return false;

	}

	/**
	 * Is one day booking.
	 *
	 * Check if one day booking option available and enable.
	 *
	 * @since  2.5.2
	 * @since  2.6.3 Added booking instance item handling.
	 *
	 * @param string|number $id Booking instance item ID.
	 *
	 * @return bool
	 */
	public function is_one_day_bookings( $id ) {

		$check_in_days  = $this->get_days_by_rule( $id, 'check_in' );
		$check_out_days = $this->get_days_by_rule( $id, 'check_out' );

		if ( ! empty( $check_in_days ) && 7 > count( $check_in_days ) || ! empty( $check_out_days ) && 7 > count( $check_out_days ) ) {
			return false;
		}

		return ( 'per_nights' !== $this->get( 'booking_period' ) && $this->get_config_setting( $id, 'one_day_bookings' ) );

	}

	/**
	 * Is weekly bookings.
	 *
	 * Check if weekly bookings option available and enable.
	 *
	 * @since  3.0.0
	 *
	 * @param string|number $id Booking instance item ID.
	 *
	 * @return false|mixed
	 */
	public function is_weekly_bookings( $id ) {

		$check_in_days  = $this->get_days_by_rule( $id, 'check_in' );
		$check_out_days = $this->get_days_by_rule( $id, 'check_out' );

		if ( ! empty( $check_in_days ) && 7 > count( $check_in_days ) || ! empty( $check_out_days ) && 7 > count( $check_out_days ) ) {
			return false;
		}

		return $this->get_config_setting( $id, 'weekly_bookings' );

	}

	/**
	 * Get config option.
	 *
	 * Returns option for date range picker configuration.
	 *
	 * @since  2.6.0
	 *
	 * @param integer $post_id Post ID.
	 * @param string  $key     Options name key.
	 *
	 * @return mixed
	 */
	public function get_config_setting( $post_id, $key ) {

		$value = jet_abaf()->settings->get( $key );

		if ( ! $this->config_settings ) {
			$this->config_settings = get_post_meta( $post_id, 'jet_abaf_configuration', true );
		}

		if ( isset( $this->config_settings['config'] ) && $this->config_settings['config']['enable_config'] ) {
			$value = $this->config_settings['config'][ $key ] ?? $value;
		}

		return $value;

	}

	/**
	 * Schedule settings.
	 *
	 * Return custom schedule settings list for specific post or global.
	 *
	 * @since  2.5.0
	 * @since  2.8.0 Code refactor.
	 *
	 * @param null $post_id       Booking post type ID.
	 * @param null $default_value Default schedule value.
	 * @param null $meta_key      Post type meta value key.
	 *
	 * @return mixed|void
	 */
	public function get_schedule_settings( $post_id = null, $default_value = null, $meta_key = null ) {

		$schedule         = null;
		$post_schedule    = get_post_meta( $post_id, 'jet_abaf_custom_schedule', true );
		$general_schedule = $this->get( $meta_key ) ?? $default_value;

		if ( ! isset( $post_schedule['custom_schedule'] ) || ! $post_schedule['custom_schedule']['use_custom_schedule'] ) {
			$schedule = $general_schedule;
		} elseif ( isset( $post_schedule['custom_schedule'][ $meta_key ] ) ) {
			$schedule = $post_schedule['custom_schedule'][ $meta_key ] ?? $general_schedule;
		}

		return apply_filters( 'jet-booking/settings/custom-schedule', $schedule, $meta_key, $default_value, $post_id );

	}

	/**
	 * Get days by rule.
	 *
	 * Returns list of days by passed rule.
	 *
	 * @since  2.8.0
	 *
	 * @param string|number $post_id Booking post type id.
	 * @param string        $type    Rule type.
	 *
	 * @return array
	 */
	public function get_days_by_rule( $post_id = null, $type = 'disable' ) {

		if ( ! $post_id ) {
			return [];
		}

		$days          = [];
		$post_schedule = get_post_meta( $post_id, 'jet_abaf_custom_schedule', true );

		$rules = [
			$type . '_weekend_2',
			$type . '_weekday_1',
			$type . '_weekday_2',
			$type . '_weekday_3',
			$type . '_weekday_4',
			$type . '_weekday_5',
			$type . '_weekend_1',
		];

		if ( ! isset( $post_schedule['custom_schedule'] ) || ! $post_schedule['custom_schedule']['use_custom_schedule'] ) {
			foreach ( $rules as $key => $value ) {
				if ( $this->get( $value ) ) {
					$days[] = $key;
				}
			}
		} else {
			foreach ( $rules as $key => $value ) {
				if ( isset( $post_schedule['custom_schedule'][ $value ] ) && filter_var( $post_schedule['custom_schedule'][ $value ], FILTER_VALIDATE_BOOLEAN ) ) {
					$days[] = $key;
				}
			}
		}

		if ( 'disable' === $type ) {
			return $days;
		}

		$disabled_days = $this->get_days_by_rule( $post_id );

		if ( empty( $disabled_days ) ) {
			return $days;
		}

		foreach ( $disabled_days as $day ) {
			if ( ( $key = array_search( $day, $days ) ) !== false ) {
				unset( $days[ $key ] );
			}
		}

		return array_values( $days );

	}

	/**
	 * Booking days off.
	 *
	 * Returns booking days off - official days off.
	 *
	 * @since  2.5.0
	 *
	 * @param int $post_id Booking post type ID.
	 *
	 * @return array List of days off.
	 * @throws \Exception
	 */
	public function get_booking_days_off( $post_id ) {

		$days_off = $this->get_schedule_settings( $post_id, null, 'days_off' );
		$dates    = [];

		if ( empty( $days_off ) ) {
			return $dates;
		}

		foreach ( $days_off as $day ) {
			$from = new \DateTime( date( 'F d, Y', $day['startTimeStamp'] ) );
			$to   = new \DateTime( date( 'F d, Y', $day['endTimeStamp'] ) );

			if ( $to->format( 'Y-m-d' ) === $from->format( 'Y-m-d' ) ) {
				$dates[] = $from->format( 'Y-m-d' );
			} else {
				$to     = $to->modify( '+1 day' );
				$period = new \DatePeriod( $from, new \DateInterval( 'P1D' ), $to );

				foreach ( $period as $key => $value ) {
					$dates[] = $value->format( 'Y-m-d' );
				}
			}
		}

		return $dates;

	}

	/**
	 * Off dates.
	 *
	 * Returns off dates - official days off and booked dates.
	 *
	 * @since  2.5.0
	 * @since  2.5.5 Added additional `$post_id` handling.
	 * @access public
	 *
	 * @param int $post_id Booking post type ID.
	 *
	 * @return array|mixed
	 * @throws \Exception
	 */
	public function get_off_dates( $post_id ) {

		$post_id = jet_abaf()->db->get_initial_booking_item_id( $post_id );

		if ( isset( $this->booked_dates[ $post_id ] ) ) {
			return $this->booked_dates[ $post_id ];
		}

		$booked_dates = jet_abaf()->tools->get_booked_dates( $post_id );
		$days_off     = jet_abaf()->settings->get_booking_days_off( $post_id );

		if ( empty( $booked_dates ) && empty( $days_off ) ) {
			$this->booked_dates[ $post_id ] = [];

			return [];
		}

		$dates = array_merge( $booked_dates, $days_off );

		$this->booked_dates[ $post_id ] = $dates;

		return $dates;

	}

	/**
	 * Update.
	 *
	 * Update setting in cache and database.
	 *
	 * @since  3.0.0 Added `'jet-booking/settings/before-update'` action hook.
	 * @access public
	 *
	 * @param null $setting Setting key name.
	 * @param null $value   Setting value.
	 * @param bool $write   DB update status.
	 *
	 * @return void
	 */
	public function update( $setting = null, $value = null, $write = true ) {

		$this->get_all();

		/**
		 * Modify options before write into DB.
		 */
		do_action( 'jet-booking/settings/before-update', $this->settings, $setting, $value );

		$this->settings[ $setting ] = $value;

		if ( $write ) {
			$this->write();
		}

	}

	/**
	 * Clear.
	 *
	 * Clear settings data.
	 *
	 * @since  2.0.0
	 * @since  2.8.0 Added global settings variable reset.
	 */
	public function clear() {
		delete_option( $this->key );
		$this->settings = null;
	}

	/**
	 * Write.
	 *
	 * Write settings cache.
	 *
	 * @since 2.0.0
	 */
	public function write() {

		/**
		 * Modify options before write into DB
		 */
		do_action( 'jet-abaf/settings/before-write', $this );

		update_option( $this->key, $this->settings, false );
	}

	/**
	 * Setting registered.
	 *
	 * Check if passed settings is registered in defaults.
	 *
	 * @since 2.0.0
	 *
	 * @param string $setting Setting key name.
	 *
	 * @return bool
	 */
	public function setting_registered( $setting ) {
		return ( $setting && isset( $this->defaults[ $setting ] ) );
	}

}