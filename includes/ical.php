<?php

namespace JET_ABAF;

use \JET_ABAF\Resources\Booking;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class iCal {

	/**
	 * Trigger to get iCal file
	 *
	 * @var string
	 */
	private $trigger = '_get_ical';
	private $hash = null;
	private $domain = null;
	private $ical_meta = '_import_ical';

	public function __construct() {

		if ( ! filter_var( jet_abaf()->settings->get( 'ical_synch' ), FILTER_VALIDATE_BOOLEAN ) ) {
			return;
		}

		$this->hash = md5( $this->get_domain() );

		if ( ! empty( $_GET[ $this->trigger ] ) && $this->hash === $_GET[ $this->trigger ] && ! empty( $_GET['_id'] ) ) {
			$this->get_calendar_file();
		}

	}

	/**
	 * Returns current domain
	 *
	 * @return [type] [description]
	 */
	public function get_domain() {

		if ( $this->domain ) {
			return $this->domain;
		}

		$find         = array( 'http://', 'https://' );
		$replace      = '';
		$this->domain = str_replace( $find, $replace, home_url() );

		return $this->domain;

	}

	/**
	 * Synch.
	 *
	 * Synchronize calendars.
	 *
	 * @since  3.0.0 Minor refactor.
	 * @access public
	 *
	 * @param int   $post_id Booking instance ID.
	 * @param false $unit_id Booking instance unit ID.
	 *
	 * @return array
	 */
	public function synch( $post_id = 0, $unit_id = false ) {

		$log = [];

		if ( ! $post_id ) {
			return [ __( 'Post ID not found.', 'jet-booking' ) ];
		}

		if ( ! $unit_id ) {
			$unit_id = 'default';
		}

		$import = get_post_meta( $post_id, $this->ical_meta, true );

		if ( ! $import ) {
			$import = [];
		}

		if ( empty( $import[ $unit_id ] ) ) {
			return [ __( 'External calendars is not found for this item.', 'jet-booking' ) ];
		}

		foreach ( $import[ $unit_id ] as $url ) {
			$response = wp_remote_get( $url );
			$label    = '<b>' . $url . ':</b><br> ';

			if ( is_wp_error( $response ) ) {
				$log[] = $label . __( 'Can`t access caledar', 'jet-booking' ) . ', ' . $response->get_error_message();
				continue;
			}

			$body = wp_remote_retrieve_body( $response );

			if ( ! $body ) {
				$log[] = $label . __( 'Empty response from calendar', 'jet-booking' );
				continue;
			}

			$log[] = $label . $this->import_calendar( $body, $post_id, $unit_id );
		}

		return $log;

	}

	/**
	 * Import calendar.
	 *
	 * Import third-party calendar data.
	 *
	 * @since  3.0.0 Added filter hook `jet-booking/ical/import/log`.
	 * @access public
	 *
	 * @param string     $data    Body of the import calendar.
	 * @param int|string $post_id Booking instance post type ID.
	 * @param int|string $unit_id Booking instance post type unit ID.
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function import_calendar( $data = null, $post_id = null, $unit_id = null ) {

		$this->load_deps();

		$calendar_object = new \ZCiCal( $data );

		if ( ! $calendar_object->countEvents() || ! $calendar_object->tree->child ) {
			return __( 'Bookings not found', 'jet-booking' );
		}

		$inserted = [];
		$skipped  = [];

		foreach ( $calendar_object->tree->child as $node ) {
			if ( 'VEVENT' !== $node->getName() ) {
				continue;
			}

			$import_node = [
				'apartment_id' => $post_id,
				'status'       => 'pending',
			];

			if ( 'default' !== $unit_id ) {
				$import_node['apartment_unit'] = $unit_id;
			}

			$import_id = false;

			foreach ( $node->data as $key => $value ) {
				switch ( $key ) {
					case 'DTSTART':
						$import_node['check_in_date'] = \ZDateHelper::fromiCaltoUnixDateTime( $value->getValues() );
						break;

					case 'DTEND':
						$import_node['check_out_date'] = \ZDateHelper::fromiCaltoUnixDateTime( $value->getValues() ) - DAY_IN_SECONDS;
						break;

					case 'UID':
						$import_node['import_id'] = $import_id = untrailingslashit( $value->getValues() );
						break;
				}
			}

			$import_node = apply_filters( 'jet-booking/ical/import/node', $import_node, $node, $calendar_object );

			if ( jet_abaf()->db->is_booking_dates_available( $import_node ) ) {
				$inserted[] = jet_abaf()->db->insert_booking( $import_node );
			} else {
				$skipped[] = $import_id;
			}
		}

		$import_log = sprintf( __( '<i> Inserted bookings: </i> %d units <br><i>Skipped bookings: </i> %d units', 'jet-booking' ), count( $inserted ), count( $skipped ) );

		return apply_filters( 'jet-booking/ical/import/log', $import_log, $post_id, $unit_id, $inserted, $skipped );

	}

	/**
	 * Get calendar file.
	 *
	 * Get calendar export file.
	 *
	 * @since 2.0.0
	 */
	public function get_calendar_file() {

		$post_id = ! empty( $_GET['_id'] ) ? absint( $_GET['_id'] ) : null;
		$uid     = ! empty( $_GET['_uid'] ) ? absint( $_GET['_uid'] ) : null;

		if ( ! $post_id ) {
			esc_html_e( 'Invalid request data', 'jet-booking' );
			die();
		}

		$post = get_post( $post_id );

		if ( ! $post ) {
			esc_html_e( 'Invalid request data', 'jet-booking' );
			die();
		}

		$this->load_deps();

		$datestamp = \ZCiCal::fromUnixDateTime() . 'Z';
		$calendar  = new \ZCiCal();
		$filename  = 'calendar-export--' . $post->post_name;

		$params = [
			'apartment_id' => $post_id,
			'status'       => jet_abaf()->statuses->valid_statuses(),
		];

		if ( $uid ) {
			$params['apartment_unit'] = $uid;
			$filename                 .= '-' . $uid;
		}

		$bookings = jet_abaf_get_bookings( $params );

		if ( ! empty( $bookings ) ) {
			foreach ( $bookings as $booking ) {
				$this->add_booking( $booking, $calendar, $datestamp );
			}
		}

		header( 'Content-type: text/calendar; charset=utf-8' );
		header( 'Content-Disposition: inline; filename=' . $filename . '.ics' );

		echo $calendar->export();

		die();

	}

	/**
	 * Add booking.
	 *
	 * Add new booking into existing calendar.
	 *
	 * @since  2.7.0 Updated summary and description handling.
	 * @access public
	 *
	 * @param Booking $booking   Booking instance.
	 * @param object  $calendar  iCal object instance.
	 * @param string  $datestamp Formatted iCal date/time string.
	 *
	 * @return void
	 */
	public function add_booking( $booking, $calendar, $datestamp ) {

		$summary      = $this->get_ical_template_data( 'summary', $booking );
		$description  = $this->get_ical_template_data( 'description', $booking );
		$hash_string  = $booking->get_check_in_date() . $booking->get_check_out_date() . $booking->get_id();
		$uid          = md5( $hash_string ) . '@' . $this->get_domain();
		$event        = new \ZCiCalNode( 'VEVENT', $calendar->curnode );
		$check_out_ts = $booking->get_check_out_date();

		if ( ! jet_abaf()->settings->is_per_nights_booking() ) {
			$check_out_ts = $check_out_ts + DAY_IN_SECONDS;
		}

		$check_in_date  = date( 'Y-m-d', $booking->get_check_in_date() );
		$check_out_date = date( 'Y-m-d', $check_out_ts );

		$data = apply_filters( 'jet-booking/ical/ical-booking-data', [
			'uid'         => [
				'node'  => 'UID',
				'value' => $uid,
			],
			'dtstart'     => [
				'node'  => 'DTSTART;VALUE=DATE-TIME',
				'value' => \ZCiCal::fromSqlDateTime( $check_in_date ),
			],
			'dtend'       => [
				'node'  => 'DTEND;VALUE=DATE-TIME',
				'value' => \ZCiCal::fromSqlDateTime( $check_out_date ),
			],
			'dtstamp'     => [
				'node'  => 'DTSTAMP',
				'value' => $datestamp,
			],
			'summary'     => [
				'node'  => 'SUMMARY',
				'value' => $summary,
			],
			'description' => [
				'node'  => 'DESCRIPTION',
				'value' => $description,
			],
		], $booking, $calendar );

		foreach ( $data as $row ) {
			$event->addNode( new \ZCiCalDataNode( $row['node'] . ':' . $row['value'] ) );
		}

		do_action_ref_array( 'jet-abaf/ical/export-booking', [ & $booking, & $calendar ] );

	}

	/**
	 * Get iCal template data.
	 *
	 * @since  2.7.0
	 * @access public
	 *
	 * @param string  $data_type Data type key name.
	 * @param Booking $booking   Booking instance.
	 *
	 * @return mixed|string|void
	 */
	public function get_ical_template_data( $data_type, $booking ) {

		$ical_template = get_option( 'jet_booking_ical_template' );

		if ( ! $ical_template || empty( $ical_template[ $data_type ] ) ) {
			return $this->get_booking_data( $data_type, $booking );
		}

		return $this->parse_macros( $ical_template[ $data_type ], $booking );

	}

	/**
	 * Returns booking summary.
	 *
	 * @since  2.0.0
	 * @since  2.8.0 Refactored.
	 * @since  3.2.0 Renamed and refactored.
	 * @access public
	 *
	 * @param string  $data_type Data type name.
	 * @param Booking $booking   Booking instance.
	 *
	 * @return mixed|void
	 */
	public function get_booking_data( $data_type, $booking ) {

		switch ( $data_type ) {
			case 'summary':
				$data = sprintf( 'Booking #%d - %s', $booking->get_id(), get_the_title( $booking->get_apartment_id() ) );
				break;

			case 'description':
				$data = get_the_excerpt( $booking->get_apartment_id() );
				break;

			default:
				$data = __( 'Booking Item', 'jet-booking' );
				break;
		}

		return apply_filters( 'jet-abaf/ical/export-booking-summary', $data, $booking );

	}

	/**
	 * Load dependencies
	 *
	 * @return [type] [description]
	 */
	public function load_deps() {

		if ( defined( '_ZAPCAL' ) ) {
			return;
		}

		require_once JET_ABAF_PATH . 'includes/vendor/icalendar/zapcallib.php';

	}

	/**
	 * Returns export URL
	 *
	 * @param  [type] $post_id [description]
	 * @param  [type] $unit_id [description]
	 *
	 * @return [type]          [description]
	 */
	public function get_export_url( $post_id = 0, $unit_id = 0 ) {
		return add_query_arg(
			array(
				$this->trigger => $this->hash,
				'_id'          => $post_id,
				'_uid'         => $unit_id,
			),
			home_url( '/' )
		);
	}

	/**
	 * Strore external calendar URL to synch
	 *
	 * @param array   $urls    [description]
	 * @param integer $post_id [description]
	 * @param boolean $unit_id [description]
	 *
	 * @return [type]           [description]
	 */
	public function update_import_urls( $urls = array(), $post_id = 0, $unit_id = false ) {

		if ( ! $post_id ) {
			return;
		}

		$existing = get_post_meta( $post_id, $this->ical_meta, true );

		if ( ! $existing ) {
			$existing = array();
		}

		if ( ! $unit_id ) {
			$unit_id = 'default';
		}

		$existing[ $unit_id ] = $urls;

		update_post_meta( $post_id, $this->ical_meta, $existing );

	}

	/**
	 * Get calendars.
	 *
	 * Returns all URLs for calendars.
	 *
	 * @since  2.6.1 Code refactor.
	 * @since  3.0.0 Minor refactor.
	 * @access public
	 *
	 * @return array
	 */
	public function get_calendars() {

		$posts = jet_abaf()->tools->get_booking_posts();

		if ( empty( $posts ) ) {
			return [];
		}

		$result = [];

		foreach ( $posts as $post ) {
			$import = get_post_meta( $post->ID, $this->ical_meta, true );

			if ( ! $import ) {
				$import = [];
			}

			$item = [
				'post_id'    => $post->ID,
				'title'      => $post->post_title,
				'unit_id'    => false,
				'unit_title' => '',
				'import_url' => $import['default'] ?? false,
				'export_url' => $this->get_export_url( $post->ID, false ),
			];

			$units = jet_abaf()->db->get_apartment_units( $post->ID );

			if ( empty( $units ) ) {
				$result[] = $item;
			} else {
				foreach ( $units as $unit ) {
					$unit_item = $item;
					$unit_id   = $unit['unit_id'];

					$unit_item['unit_id']    = $unit_id;
					$unit_item['unit_title'] = $unit['unit_title'];
					$unit_item['import_url'] = $import[ $unit_id ] ?? false;
					$unit_item['export_url'] = $this->get_export_url( $post->ID, $unit_id );

					$result[] = $unit_item;
				}
			}
		}

		return $result;

	}

	/**
	 * Parse macros.
	 *
	 * Parse export calendar template macros.
	 *
	 * @since  3.2.0
	 * @access public
	 *
	 * @param string  $content Content string to parse.
	 * @param Booking $booking Booking instance.
	 *
	 * @return string|string[]|null
	 */
	public function parse_macros( $content, $booking ) {
		return jet_abaf()->macros->macros_handler->do_macros( $content, $booking );
	}

}
