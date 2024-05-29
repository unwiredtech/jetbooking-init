<?php

namespace JET_ABAF\DB\Tables;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Bookings extends Base {

	/**
	 * Return table slug.
	 *
	 * @since 3.3.0
	 *
	 * @return string
	 */
	public function table_slug() {
		return 'apartment_bookings';
	}

	/**
	 * Schema.
	 *
	 * Returns booking table columns schema.
	 *
	 * @since  3.3.0
	 *
	 * @return string[]
	 */
	public function schema() {
		return [
			'booking_id'     => "bigint(20) NOT NULL AUTO_INCREMENT",
			'status'         => "text",
			'apartment_id'   => "bigint(20)",
			'apartment_unit' => "bigint(20)",
			'check_in_date'  => "bigint(20)",
			'check_out_date' => "bigint(20)",
			'user_id'        => "bigint(20)",
			'order_id'       => "bigint(20)",
			'import_id'      => "text",
		];
	}

	/**
	 * Returns table schema.
	 *
	 * @since 3.3.0
	 *
	 * @return string
	 */
	public function get_table_schema() {

		$default_columns    = $this->schema();
		$additional_columns = jet_abaf()->db->get_additional_db_columns();
		$columns_schema     = '';

		foreach ( $default_columns as $column => $desc ) {
			$columns_schema .= "$column $desc,";
		}

		if ( is_array( $additional_columns ) && ! empty( $additional_columns ) ) {
			foreach ( $additional_columns as $column ) {
				$columns_schema .= "$column text,";
			}
		}

		$table           = $this->table();
		$charset_collate = $this->wpdb()->get_charset_collate();

		return "CREATE TABLE $table ( $columns_schema PRIMARY KEY ( booking_id ) ) $charset_collate;";

	}

	/**
	 * Allow child classes do own sanitize of the data before write it into DB.
	 *
	 * @since 3.3.0
	 *
	 * @param  array  $data Table data.
	 *
	 * @return array|mixed
	 */
	public function sanitize_data_before_db( $data = [] ) {

		if ( ! empty( $data['check_in_date'] ) ) {
			$data['check_in_date'] ++;
		}

		return $data;

	}

}
