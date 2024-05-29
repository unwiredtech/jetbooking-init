<?php

namespace JET_ABAF;

class Upgrade {

	/**
	 * Settings.
	 *
	 * Default settings list holder.
	 *
	 * @since  2.6.0
	 * @access private
	 *
	 * @var array
	 */
	private $settings = [];

	public function __construct() {

		$this->settings = jet_abaf()->settings->get_all();

		$db_updater = jet_abaf()->framework->get_included_module_data( 'cherry-x-db-updater.php' );

		new \CX_DB_Updater(
			[
				'path'      => $db_updater['path'],
				'url'       => $db_updater['url'],
				'slug'      => 'jet-booking',
				'version'   => '3.3.0',
				'callbacks' => [
					'2.0.0' => [
						[ $this, 'update_db_2_0_0' ],
					],
					'2.5.0' => [
						[ $this, 'update_db_2_5_0' ],
					],
					'2.6.0' => [
						[ $this, 'update_db_2_6_0' ],
					],
					'2.8.0' => [
						[ $this, 'update_db_2_8_0' ],
					],
					'3.0.0' => [
						[ $this, 'update_db_3_0_0' ],
					],
					'3.2.0' => [
						[ $this, 'update_db_3_2_0' ],
					],
					'3.3.0' => [
						[ $this, 'update_db_3_3_0' ],
					],
				],
				'labels'    => [
					'start_update' => __( 'Start Update', 'jet-booking' ),
					'data_update'  => __( 'Data Update', 'jet-booking' ),
					'messages'     => [
						'error'   => __( 'Module DB Updater init error in %s - version and slug is required arguments.', 'jet-booking' ),
						'update'  => __( 'We need to update your database to the latest version.', 'jet-booking' ),
						'updated' => __( 'DB Update complete, thank you for updating to the latest version!', 'jet-booking' ),
					],
				],
			]
		);

		if ( is_admin() && ! wp_doing_ajax() ) {
			$this->update_db_columns();
		}

	}

	/**
	 * Update DB 2.0.0.
	 *
	 * Set values for newly created settings on DB update.
	 *
	 * @since  3.0.0
	 */
	public function update_db_2_0_0() {

		if ( ! jet_abaf()->db->bookings->is_table_exists() ) {
			return;
		}

		if ( ! jet_abaf()->db->column_exists( 'status' ) ) {
			jet_abaf()->db->insert_table_columns( [ 'status' ] );
		}

		if ( jet_abaf()->dashboard->is_dashboard_page() ) {
			if ( ! jet_abaf()->db->column_exists( 'import_id' ) ) {
				jet_abaf()->db->insert_table_columns( [ 'import_id' ] );
			}
		}

		if ( jet_abaf()->settings->get( 'wc_integration' ) ) {
			$additional_columns  = jet_abaf()->settings->get( 'additional_columns' );
			$has_order_id_column = false;

			if ( ! empty( $additional_columns ) ) {
				foreach ( $additional_columns as $column ) {
					if ( ! empty( $column['column'] ) && 'order_id' === $column['column'] ) {
						$has_order_id_column = true;
					}
				}
			}

			if ( ! $has_order_id_column ) {
				if ( ! is_array( $additional_columns ) ) {
					$additional_columns = [];
				}

				$additional_columns[] = [ 'column' => 'order_id' ];

				jet_abaf()->settings->update( 'additional_columns', $additional_columns );

				if ( ! jet_abaf()->db->column_exists( 'order_id' ) ) {
					jet_abaf()->db->insert_table_columns( [ 'order_id' ] );
				}
			}
		}

	}

	/**
	 * Update DB 2.5.0.
	 *
	 * Set values for newly created settings on DB update.
	 *
	 * @since  2.5.0
	 */
	public function update_db_2_5_0() {

		$settings = [
			'days_off',
			'disable_weekday_1',
			'disable_weekday_2',
			'disable_weekday_3',
			'disable_weekday_4',
			'disable_weekday_5',
			'disable_weekend_1',
			'disable_weekend_2',
		];

		$this->update_settings( $settings );

	}

	/**
	 * Update DB 2.6.0.
	 *
	 * Set values for newly created settings on DB update.
	 *
	 * @since  2.6.0
	 */
	public function update_db_2_6_0() {

		$settings = [
			'start_day_offset',
			'min_days',
			'max_days',
		];

		$this->update_settings( $settings );

	}

	/**
	 * Update DB 2.8.0.
	 *
	 * Set values for newly created settings on DB update.
	 *
	 * @since  2.8.0
	 */
	public function update_db_2_8_0() {

		$settings = [
			'check_in_weekday_1',
			'check_out_weekday_1',
			'check_in_weekday_2',
			'check_out_weekday_2',
			'check_in_weekday_3',
			'check_out_weekday_3',
			'check_in_weekday_4',
			'check_out_weekday_4',
			'check_in_weekday_5',
			'check_out_weekday_5',
			'check_in_weekend_1',
			'check_out_weekend_1',
			'check_in_weekend_2',
			'check_out_weekend_2',
		];

		$this->update_settings( $settings );

		if ( ! jet_abaf()->db->bookings->is_table_exists() ) {
			return;
		}

		$table = jet_abaf()->db->bookings->table();

		if ( jet_abaf()->db->column_exists( 'order_id' ) ) {
			jet_abaf()->db::wpdb()->query( "ALTER TABLE $table MODIFY COLUMN order_id bigint(20);" );
		} else {
			jet_abaf()->db::wpdb()->query( "ALTER TABLE $table ADD order_id bigint(20);" );
		}

		$orders_column   = jet_abaf()->settings->get( 'related_post_type_column' );
		$removed_columns = [ 'order_id' ];

		if ( $orders_column && 'order_id' !== $orders_column && jet_abaf()->db->column_exists( $orders_column ) ) {
			jet_abaf()->db::wpdb()->query( "UPDATE $table SET order_id = $orders_column WHERE order_id IS NULL OR TRIM( order_id ) = '';" );
			jet_abaf()->db->delete_table_columns( [ $orders_column ] );

			if ( ! in_array( $orders_column, $removed_columns ) ) {
				$removed_columns[] = $orders_column;
			}
		}

		$additional_columns = jet_abaf()->settings->get( 'additional_columns' );

		foreach ( $additional_columns as $key => $column ) {
			if ( ! empty( $column['column'] ) && in_array( $column['column'], $removed_columns ) ) {
				unset( $additional_columns[ $key ] );
			}
		}

		jet_abaf()->settings->update( 'additional_columns', $additional_columns );

	}

	/**
	 * Update DB 3.0.0.
	 *
	 * Set values for newly created settings on DB update.
	 *
	 * @since  3.0.0
	 */
	public function update_db_3_0_0() {

		$settings = [
			'booking_mode',
			'booking_hold_time',
			'field_layout',
			'field_position',
			'field_label',
			'field_placeholder',
			'check_in_field_label',
			'check_in_field_placeholder',
			'check_out_field_label',
			'check_out_field_placeholder',
			'field_description',
			'field_date_format',
			'field_separator',
			'field_start_of_week',
		];

		$this->update_settings( $settings );
		$this->update_db_2_8_0();

	}

	/**
	 * Update DB 3.2.0.
	 *
	 * Set values for newly created settings on DB update.
	 *
	 * @since  3.2.0
	 */
	public function update_db_3_2_0() {

		$settings = [
			'remove_temporary_bookings',
			'remove_interval',
		];

		$this->update_settings( $settings );

	}

	/**
	 * Update DB 3.3.0.
	 *
	 * Set values for newly created settings on DB update.
	 *
	 * @since  3.3.0
	 */
	public function update_db_3_3_0() {

		$settings = [
			'booking_cancellation',
			'cancellation_limit',
			'cancellation_unit',
		];

		$this->update_settings( $settings );

	}

	/**
	 * Update DB columns.
	 *
	 * @since  3.3.0
	 */
	public function update_db_columns() {

		if ( ! jet_abaf()->db->bookings->is_table_exists() ) {
			return;
		}

		if ( ! jet_abaf()->db->column_exists( 'user_id' ) ) {
			jet_abaf()->db->insert_table_columns( [ 'user_id' => 'bigint(20)' ] );
		}

	}

	/**
	 * Update settings.
	 *
	 * Sen default values for newly created version settings.
	 *
	 * @since  3.0.0
	 *
	 * @param array $settings List of settings to update.
	 */
	public function update_settings( $settings ) {

		if ( ! $this->settings ) {
			return;
		}

		foreach ( $settings as $setting ) {
			if ( ! isset( $this->settings[ $setting ] ) ) {
				$default_setting = jet_abaf()->settings->get( $setting );

				jet_abaf()->settings->update( $setting, $default_setting );
			}
		}

	}

}
