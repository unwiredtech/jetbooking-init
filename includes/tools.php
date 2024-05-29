<?php

namespace JET_ABAF;

class Tools {

	/**
	 * Date format JS to PHP.
	 *
	 * Returns PHP date format from JavaScript format.
	 *
	 * @access public
	 *
	 * @param null  $format JS date format.
	 * @param array $mask   Provided transform mask.
	 *
	 * @return mixed|string|string[]
	 */
	public static function date_format_js_to_php( $format = null, $mask = [] ) {

		if ( ! $format ) {
			return '';
		}

		$mask = ! empty( $mask ) ? $mask : [
			'/HH{1}/'   => 'H',
			'/hh{1}/'   => 'h',
			'/YYYY{1}/' => 'Y',
			'/YY{1}/'   => 'y',
			'/MMMM{1}/' => 'F',
			'/MMM{1}/'  => 'M',
			'/MM{1}/'   => 'm',
			'/M{1}/'    => 'n',
			'/mm{1}/'   => 'i',
			'/DD{1}/'   => 'd',
			'/D{1}/'    => 'j',
			'/dddd{1}/' => 'l',
			'/ddd{1}/'  => 'D',
		];

		foreach ( $mask as $key => $value ) {
			$format = preg_replace( $key, $value, $format );
		}

		return $format;

	}

	/**
	 * Date format PHP to JS.
	 *
	 * Returns JavaScript date format from PHP format.
	 *
	 * @access public
	 *
	 * @param null  $format PHP date format.
	 * @param array $mask   Provided transform mask.
	 *
	 * @return mixed|string|string[]
	 */
	public static function date_format_php_to_js( $format = null, $mask = [] ) {

		if ( ! $format ) {
			return '';
		}

		$mask = ! empty( $mask ) ? $mask : [
			'/H{1}/' => 'HH',
			'/h{1}/' => 'hh',
			'/Y{1}/' => 'YYYY',
			'/y{1}/' => 'YY',
			'/M{1}/' => 'MMM',
			'/n{1}/' => 'M',
			'/m{1}/' => 'MM',
			'/F{1}/' => 'MMMM',
			'/d{1}/' => 'DD',
			'/D{1}/' => 'ddd',
			'/j{1}/' => 'D',
			'/l{1}/' => 'dddd',
			'/i{1}/' => 'mm',
			'/g{1}/' => 'hh',
		];

		foreach ( $mask as $key => $value ) {
			$format = preg_replace( $key, $value, $format );
		}

		return $format;

	}

	/**
	 * Get post types for js.
	 *
	 * Returns all post types list to use in JavaScript components
	 *
	 * @since 3.2.0
	 *
	 * @param false $placeholder Placeholder value.
	 * @param false $key         Array key.
	 *
	 * @return array
	 */
	public function get_post_types_for_js( $placeholder = false, $key = false ) {

		$post_types = get_post_types( [], 'objects' );
		$types_list = $this->prepare_list_for_js( $post_types, 'name', 'label', $key );

		if ( $placeholder && is_array( $placeholder ) ) {
			$types_list = array_merge( [ $placeholder ], $types_list );
		}

		return $types_list;

	}

	/**
	 * Prepare list for js.
	 *
	 * Prepare passed array for using in JavaScript options.
	 *
	 * @since 3.2.0
	 *
	 * @param array $array     Initial list of posts.
	 * @param null  $value_key List value key.
	 * @param null  $label_key List label key.
	 * @param false $key       Array key.
	 *
	 * @return array
	 */
	public static function prepare_list_for_js( $array = [], $value_key = null, $label_key = null, $key = false ) {

		$result = [];

		if ( ! is_array( $array ) || empty( $array ) ) {
			return $result;
		}

		$array_key = false;

		foreach ( $array as $index => $item ) {
			$value = null;
			$label = null;

			if ( is_object( $item ) ) {
				$value = $item->$value_key;
				$label = $item->$label_key;

				if ( $key ) {
					$array_key = $item->$key;
				}
			} elseif ( is_array( $item ) ) {
				$value = $item[ $value_key ];
				$label = $item[ $label_key ];

				if ( $key ) {
					$array_key = $item[ $key ];
				}
			} else {
				if ( ARRAY_A === $value_key ) {
					$value = $index;
				} else {
					$value = $item;
				}

				$label = $item;

				if ( $key ) {
					$array_key = $index;
				}
			}

			if ( $key && false !== $array_key ) {
				$result[ $array_key ] = [
					'value' => $value,
					'label' => $label,
				];
			} else {
				$result[] = [
					'value' => $value,
					'label' => $label,
				];
			}
		}

		return $result;

	}

	/**
	 * Get booking posts.
	 *
	 * Returns list of all created bookings posts.
	 *
	 * @since  2.6.1
	 * @since  3.1.1 Added $args parameter.
	 *
	 * @param array $args Booking post arguments.
	 *
	 * @return array|int[]|\WP_Post[]
	 */
	public function get_booking_posts( $args = [] ) {

		$post_type = jet_abaf()->settings->get( 'apartment_post_type' );

		if ( ! $post_type ) {
			return [];
		}

		$defaults = apply_filters( 'jet-booking/tools/post-type-args', [
			'post_type'      => $post_type,
			'posts_per_page' => - 1,
		] );

		$args  = wp_parse_args( $args, $defaults );
		$posts = get_posts( $args );

		if ( ! $posts ) {
			return [];
		}

		return $posts;

	}

	/**
	 * Get unavailable apartments.
	 *
	 * @since 2.0.0
	 * @since 2.6.1 New handling.
	 * @since 3.1.0 Moved to tools class.
	 *
	 * @param string $from Range start date in timestamp.
	 * @param string $to   Range end date in timestamp.
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function get_unavailable_apartments( $from, $to ) {

		$args  = apply_filters( 'jet-booking/tools/booking-posts-args', [] );
		$posts = $this->get_booking_posts( $args );

		if ( empty( $posts ) ) {
			return [];
		}

		$booked_apartments = [];

		foreach ( $posts as $post ) {
			$invalid_dates = $this->get_invalid_dates_in_range( $from, $to, $post->ID );

			if ( ! empty( $invalid_dates ) ) {
				$booked_apartments[] = $post->ID;
			}
		}

		return $booked_apartments;

	}

	/**
	 * Get invalid dates in range.
	 *
	 * Returns list of booked, disabled and off dates in defined range.
	 *
	 * @since  2.5.5
	 * @since  2.6.1 Added `$instance_id` parameter.
	 * @since  2.7.1 Checkout only compatibility.
	 * @access public
	 *
	 * @param string        $from        First date of range in timestamp.
	 * @param string        $to          Last date of range in timestamp.
	 * @param string|number $instance_id Booking instance ID.
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function get_invalid_dates_in_range( $from, $to, $instance_id ) {

		$period        = $this->get_booking_period( $from, $to );
		$booked_dates  = jet_abaf()->settings->get_off_dates( $instance_id );
		$disabled_days = jet_abaf()->settings->get_days_by_rule( $instance_id );
		$booked_range  = [];

		foreach ( $period as $key => $value ) {
			if ( in_array( $value->format( 'Y-m-d' ), $booked_dates ) || in_array( $value->format( 'w' ), $disabled_days ) ) {
				$booked_range[] = $value->format( 'Y-m-d' );
			}
		}

		sort( $booked_range );

		$days_off = jet_abaf()->settings->get_booking_days_off( $instance_id );

		if ( jet_abaf()->settings->checkout_only_allowed() ) {
			if ( false !== ( $index = array_search( date( 'Y-m-d', $to ), $booked_range ) ) && 0 === $index && ! in_array( $booked_range[ $index ], $days_off ) && ! in_array( date( 'N', $to ), $disabled_days ) ) {
				unset( $booked_range[ $index ] );
			}
		}

		return array_values( $booked_range );

	}

	/**
	 * Get field default value.
	 *
	 * Returns check in check out field default values.
	 *
	 * @since  3.0.0
	 * @access public
	 *
	 * @param string     $value   Initial value.
	 * @param string     $format  Date format.
	 * @param string|int $post_id Queried post ID.
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function get_field_default_value( $value, $format, $post_id ) {

		$check_in_days  = jet_abaf()->settings->get_days_by_rule( $post_id, 'check_in' );
		$check_out_days = jet_abaf()->settings->get_days_by_rule( $post_id, 'check_out' );

		if ( ! empty( $check_in_days ) && 7 > count( $check_in_days ) || ! empty( $check_out_days ) && 7 > count( $check_out_days ) || jet_abaf()->settings->get( 'weekly_bookings' ) ) {
			return [];
		}

		$result         = [];
		$store_type     = jet_abaf()->settings->get( 'filters_store_type' );
		$searched_dates = jet_abaf()->stores->get_store( $store_type )->get( 'searched_dates' );
		$value          = $value ?: $searched_dates;

		if ( ! trim( $value ) ) {
			return $result;
		}

		$value = explode( ' - ', $value );

		if ( ! empty( $value[0] ) && $this->is_valid_timestamp( $value[0] ) && ! empty( $value[1] ) && $this->is_valid_timestamp( $value[1] ) ) {
			$checkin          = date( 'Y-m-d', $value[0] );
			$checkout         = date( 'Y-m-d', $value[1] );
			$period_start     = new \DateTime( $checkin );
			$period_end       = new \DateTime( $checkout );
			$period           = jet_abaf()->settings->is_per_nights_booking() ? $period_start->diff( $period_end ) : $period_start->diff( $period_end->modify( '+1 day' ) );
			$min_days         = jet_abaf()->settings->get_config_setting( $post_id, 'min_days' );
			$max_days         = jet_abaf()->settings->get_config_setting( $post_id, 'max_days' );
			$start_day_offset = jet_abaf()->settings->get_config_setting( $post_id, 'start_day_offset' );

			$price          = new Price( $post_id );
			$seasonal_price = $price->seasonal_price->get_price();
			$in_season      = false;

			if ( ! empty( $seasonal_price ) ) {
				foreach ( $seasonal_price as $season ) {
					if ( ! isset( $season['enable_config'] ) || ! filter_var( $season['enable_config'], FILTER_VALIDATE_BOOLEAN ) ) {
						continue;
					}

					if ( $value[0] >= $season['start'] && $value[0] <= $season['end'] || $value[1] >= $season['start'] && $value[1] <= $season['end'] || $season['start'] >= $value[0] && $season['start'] <= $value[0] || $season['end'] >= $value[0] && $season['end'] <= $value[0] ) {
						$in_season = true;

						if ( ! empty( $season['min_days'] ) && $period->days < $season['min_days'] || ! empty( $season['max_days'] ) && $period->days > $season['max_days'] || ! empty( $season['start_day_offset'] ) && $period->days <= $season['start_day_offset'] ) {
							return $result;
						}
					}
				}
			}

			if ( ! $in_season && ( $min_days && $period->days < $min_days || $max_days && $period->days > $max_days || $period->days <= $start_day_offset ) ) {
				return $result;
			}

			$booked_range = $this->get_invalid_dates_in_range( $value[0], $value[1], $post_id );

			if ( $checkin >= date( 'Y-m-d' ) && ! ( in_array( $checkin, $booked_range ) && in_array( $checkout, $booked_range ) ) ) {
				if ( in_array( $checkin, $booked_range ) ) {
					$checkin = strtotime( end( $booked_range ) . ' + 1 day' );
					reset( $booked_range );
				} else {
					$checkin = $value[0];
				}

				if ( in_array( $checkout, $booked_range ) ) {
					$checkout = strtotime( $booked_range[0] . ' - 1 day' );
				} else {
					if ( ! empty( $booked_range ) && ! in_array( date( 'Y-m-d', $value[0] ), $booked_range ) ) {
						$checkout = strtotime( $booked_range[0] . ' - 1 day' );
					} else {
						$checkout = $value[1];
					}
				}

				$format = self::date_format_js_to_php( $format );

				$result['checkin']  = date( $format, $checkin );
				$result['checkout'] = jet_abaf()->settings->is_one_day_bookings( $post_id ) ? $result['checkin'] : date( $format, $checkout );
			}
		}

		return $result;

	}

	/**
	 * Booked dates.
	 *
	 * Returns booked dates list.
	 *
	 * @since  1.0.0
	 *
	 * @return array List of booked dates.
	 * @throws \Exception
	 */
	public function get_booked_dates( $post_id ) {

		$bookings = jet_abaf()->db->get_future_bookings( $post_id );

		if ( empty( $bookings ) ) {
			return [];
		}

		$units           = jet_abaf()->db->get_apartment_units( $post_id );
		$units_num       = ! empty( $units ) ? count( $units ) : 0;
		$weekly_bookings = jet_abaf()->settings->is_weekly_bookings( $post_id );
		$week_offset     = jet_abaf()->settings->get_config_setting( $post_id, 'week_offset' );
		$skip_statuses   = jet_abaf()->statuses->invalid_statuses();
		$skip_statuses[] = jet_abaf()->statuses->temporary_status();
		$dates           = [];

		if ( ! $units_num || 1 === $units_num ) {
			foreach ( $bookings as $booking ) {
				if ( ! empty( $booking['status'] ) && in_array( $booking['status'], $skip_statuses ) ) {
					continue;
				}

				$from = new \DateTime( date( 'F d, Y', $booking['check_in_date'] ) );
				$to   = new \DateTime( date( 'F d, Y', $booking['check_out_date'] ) );

				if ( $weekly_bookings && ! $week_offset || ! jet_abaf()->settings->is_per_nights_booking() ) {
					$to = $to->modify( '+1 day' );
				}

				if ( $to->format( 'Y-m-d' ) === $from->format( 'Y-m-d' ) ) {
					$dates[] = $from->format( 'Y-m-d' );
				} else {
					$period = new \DatePeriod( $from, new \DateInterval( 'P1D' ), $to );

					foreach ( $period as $key => $value ) {
						$dates[] = $value->format( 'Y-m-d' );
					}
				}
			}
		} else {
			$booked_units = [];

			foreach ( $bookings as $booking ) {
				if ( ! empty( $booking['status'] ) && in_array( $booking['status'], $skip_statuses ) ) {
					continue;
				}

				$from = new \DateTime( date( 'F d, Y', $booking['check_in_date'] ) );
				$to   = new \DateTime( date( 'F d, Y', $booking['check_out_date'] ) );

				if ( $weekly_bookings && ! $week_offset || ! jet_abaf()->settings->is_per_nights_booking() ) {
					$to = $to->modify( '+1 day' );
				}

				if ( $to->format( 'Y-m-d' ) === $from->format( 'Y-m-d' ) ) {
					if ( empty( $booked_units[ $from->format( 'Y-m-d' ) ] ) ) {
						$booked_units[ $from->format( 'Y-m-d' ) ] = 1;
					} else {
						$booked_units[ $from->format( 'Y-m-d' ) ] ++;
					}
				} else {
					$period = new \DatePeriod( $from, new \DateInterval( 'P1D' ), $to );

					foreach ( $period as $key => $value ) {
						if ( empty( $booked_units[ $value->format( 'Y-m-d' ) ] ) ) {
							$booked_units[ $value->format( 'Y-m-d' ) ] = 1;
						} else {
							$booked_units[ $value->format( 'Y-m-d' ) ] ++;
						}
					}
				}
			}

			foreach ( $booked_units as $date => $booked_units_num ) {
				if ( $units_num <= $booked_units_num ) {
					$dates[] = $date;
				}
			}
		}

		return $dates;

	}

	/**
	 * Get next booked dates.
	 *
	 * Returns list of dates that booked next.
	 *
	 * @param array $booked_dates Booked dates list.
	 *
	 * @return array
	 */
	public function get_next_booked_dates( $booked_dates ) {

		$result = [];

		if ( ! jet_abaf()->settings->checkout_only_allowed() ) {
			return $result;
		}

		foreach ( $booked_dates as $index => $date ) {
			$next_date = date( 'Y-m-d', strtotime( $date ) + DAY_IN_SECONDS );
			$prev_date = date( 'Y-m-d', strtotime( $date ) - DAY_IN_SECONDS );

			if ( ! in_array( $next_date, $booked_dates ) && ! in_array( $prev_date, $booked_dates ) ) {
				$result[] = $next_date;
			}
		}

		return $result;

	}

	/**
	 * Returns booking period data.
	 *
	 * @since 3.2.1
	 *
	 * @param int|string $from Start period date in timestamp format.
	 * @param int|string $to   End period date in timestamp format.
	 *
	 * @return \DatePeriod
	 * @throws \Exception
	 */
	public function get_booking_period( $from, $to ) {

		$start = new \DateTime( date( 'Y-m-d', $from ) );
		$end   = new \DateTime( date( 'Y-m-d', $to ) );

		return new \DatePeriod( $start, new \DateInterval( 'P1D' ), $end->modify( '+1 day' ) );

	}

	/**
	 * Check in booking period containing disables days.
	 *
	 * @since 3.2.1
	 *
	 * @param array $booking Booking data.
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function is_booking_period_available( $booking ) {

		$disabled_days  = jet_abaf()->settings->get_days_by_rule( $booking['apartment_id'] );
		$days_off       = jet_abaf()->settings->get_booking_days_off( $booking['apartment_id'] );
		$booking_period = $this->get_booking_period( $booking['check_in_date'], $booking['check_out_date'] );
		$invalid_days   = [];

		foreach ( $booking_period as $key => $value ) {
			if ( in_array( $value->format( 'w' ), $disabled_days ) || in_array( $value->format( 'Y-m-d' ), $days_off ) ) {
				$invalid_days[] = $value->format( 'Y-m-d' );
			}
		}

		return empty( $invalid_days );

	}

	/**
	 * Is valid timestamp.
	 *
	 * Check if is valid timestamp
	 *
	 * @since 3.2.0
	 *
	 * @param mixed $timestamp Date in timestamp format.
	 *
	 * @return boolean
	 */
	public static function is_valid_timestamp( $timestamp ) {

		if ( is_array( $timestamp ) || is_object( $timestamp ) ) {
			return false;
		}

		return ( ( string ) ( int ) $timestamp === $timestamp || ( int ) $timestamp === $timestamp ) && ( $timestamp <= PHP_INT_MAX ) && ( $timestamp >= ~PHP_INT_MAX );

	}

}
