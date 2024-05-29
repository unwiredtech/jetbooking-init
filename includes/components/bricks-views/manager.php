<?php

namespace JET_ABAF\Components\Bricks_Views;

use \Bricks\Elements;
use \Bricks\Conditions;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

class Manager {

	public function __construct() {

		if ( ! defined( 'BRICKS_VERSION' ) ) {
			return;
		}

		// Register custom elements.
		add_action( 'init', [ $this, 'register_elements' ], 11 );

		// Provide a translatable category string for the builder.
		add_filter( 'bricks/builder/i18n', function ( $i18n ) {
			$i18n['jetbooking'] = __( 'JetBooking', 'jet-booking' );

			return $i18n;
		} );

		// Add JetBooking icons font.
		add_action( 'wp_enqueue_scripts', function () {
			if ( bricks_is_builder() ) {
				wp_enqueue_style(
					'jet-booking-icons',
					JET_ABAF_URL . 'assets/lib/jet-booking-icons/icons.css',
					[],
					JET_ABAF_VERSION
				);
			}
		} );

		if ( 'plain' === jet_abaf()->settings->get( 'booking_mode' ) ) {
			// Inject control group and controls to JetForms element.
			add_action( 'bricks/elements/jet-form-builder-form/control_groups', [ $this, 'inject_date_range_picker_control_groups' ] );
			add_action( 'bricks/elements/jet-form-builder-form/controls', [ $this, 'inject_date_range_picker_controls' ] );
		}

		// Register elements visibility conditions group.
		add_filter( 'bricks/conditions/groups', [ $this, 'register_conditions_group' ] );
		// Register elements visibility conditions.
		add_action( 'bricks/conditions/options', [ $this, 'register_conditions_options' ] );
		// Execute elements visibility logic.
		add_filter( 'bricks/conditions/result', [ $this, 'check_condition' ], 10, 3 );

	}

	/**
	 * Register elements.
	 *
	 * Load and register custom elements.
	 *
	 * @since 3.1.0
	 */
	public function register_elements() {

		$element_files = [
			JET_ABAF_PATH . 'includes/components/bricks-views/elements/calendar.php',
		];

		foreach ( $element_files as $file ) {
			Elements::register_element( $file );
		}

	}

	/**
	 * Register condition group.
	 *
	 * Register and returns specific JetBooking elements visibility conditions group.
	 *
	 * @since  3.3.0
	 *
	 * @param array $groups List of groups.
	 *
	 * @return mixed
	 */
	public function register_conditions_group( $groups ) {

		$groups[] = [
			'name'  => 'jet_booking',
			'label' => __( 'JetBooking', 'jet-booking' ),
		];

		return $groups;

	}

	/**
	 * Register conditions.
	 *
	 * Register specific JetBooking elements visibility conditions.
	 *
	 * @since  3.3.0
	 *
	 * @param array $options List of elements visibility options.
	 *
	 * @return mixed
	 */
	public function register_conditions_options( $options ) {

		$options[] = [
			'key'     => 'booking_is_cancellable',
			'label'   => __( 'Booking is Cancellable', 'jet-booking' ),
			'group'   => 'jet_booking',
			'compare' => [],
			'value'   => []
		];

		$options[] = [
			'key'     => 'booking_is_not_cancellable',
			'label'   => __( 'Booking is not Cancellable', 'jet-booking' ),
			'group'   => 'jet_booking',
			'compare' => [],
			'value'   => []
		];

		return $options;

	}

	/**
	 * Check condition.
	 *
	 * Execute elements visibility logic.
	 *
	 * @since 3.3.0
	 *
	 * @param boolean $result        Condition result.
	 * @param string  $condition_key Condition option name.
	 * @param array   $condition     condition parameters.
	 *
	 * @return bool
	 */
	public function check_condition( $result, $condition_key, $condition ) {

		if ( $condition_key !== 'booking_is_cancellable' && $condition_key !== 'booking_is_not_cancellable' ) {
			return $result;
		}

		$booking = apply_filters( 'jet-booking/bricks-views/condition/object', null );

		if ( ! $booking || ! is_a( $booking, '\JET_ABAF\Resources\Booking' ) ) {
			return false;
		}

		switch ( $condition_key ) {
			case 'booking_is_cancellable':
				$result = $booking->is_cancellable();
				break;

			case 'booking_is_not_cancellable':
				$result = ! $booking->is_cancellable();
				break;
		}

		return $result;

	}

	/**
	 * Inject date range picker control groups.
	 *
	 * Add custom control groups to a specific element.
	 *
	 * @since 3.2.0
	 *
	 * @param array $control_groups List of control groups.
	 *
	 * @return mixed
	 */
	public function inject_date_range_picker_control_groups( $control_groups ) {

		$control_groups['section_date_range_picker_styles'] = [
			'tab'   => 'style',
			'title' => __( 'Date Range Picker', 'jet-booking' ),
		];

		return $control_groups;

	}

	/**
	 * Inject date range picker controls.
	 *
	 * Add custom controls to any element.
	 *
	 * @since 3.2.0
	 *
	 * @param array $controls List of controls.
	 *
	 * @return mixed
	 */
	public function inject_date_range_picker_controls( $controls ) {

		$controls['date_range_picker_color'] = [
			'tab'   => 'style',
			'group' => 'section_date_range_picker_styles',
			'label' => __( 'Color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.date-picker-wrapper',
				]
			],
		];

		$controls['date_range_picker_bg_color'] = [
			'tab'   => 'style',
			'group' => 'section_date_range_picker_styles',
			'label' => __( 'Background color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.date-picker-wrapper',
				]
			],
		];

		$controls['date_range_picker_days_heading'] = [
			'tab'   => 'style',
			'group' => 'section_date_range_picker_styles',
			'label' => __( 'Days', 'jet-booking' ),
			'type'  => 'separator',
		];

		$controls['date_range_picker_days_color'] = [
			'tab'   => 'style',
			'group' => 'section_date_range_picker_styles',
			'label' => __( 'Color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.date-picker-wrapper tbody .day',
				]
			],
		];

		$controls['date_range_picker_days_bg_color'] = [
			'tab'   => 'style',
			'group' => 'section_date_range_picker_styles',
			'label' => __( 'Background color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.date-picker-wrapper tbody .day',
				]
			],
		];

		$controls['date_range_picker_inactive_days_heading'] = [
			'tab'   => 'style',
			'group' => 'section_date_range_picker_styles',
			'label' => __( 'Inactive days', 'jet-booking' ),
			'type'  => 'separator',
		];

		$controls['date_range_picker_inactive_days_color'] = [
			'tab'   => 'style',
			'group' => 'section_date_range_picker_styles',
			'label' => __( 'Color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.date-picker-wrapper div.day.invalid',
				]
			],
		];

		$controls['date_range_picker_inactive_days_bg_color'] = [
			'tab'   => 'style',
			'group' => 'section_date_range_picker_styles',
			'label' => __( 'Background color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.date-picker-wrapper div.day.invalid',
				]
			],
		];

		$controls['date_range_picker_current_day_heading'] = [
			'tab'   => 'style',
			'group' => 'section_date_range_picker_styles',
			'label' => __( 'Current day', 'jet-booking' ),
			'type'  => 'separator',
		];

		$controls['date_range_picker_current_day_color'] = [
			'tab'   => 'style',
			'group' => 'section_date_range_picker_styles',
			'label' => __( 'Color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.date-picker-wrapper tbody .day.real-today:not(.invalid)',
				]
			],
		];

		$controls['date_range_picker_current_day_bg_color'] = [
			'tab'   => 'style',
			'group' => 'section_date_range_picker_styles',
			'label' => __( 'Background color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.date-picker-wrapper tbody .day.real-today:not(.invalid)',
				]
			],
		];

		$controls['date_range_picker_edges_heading'] = [
			'tab'   => 'style',
			'group' => 'section_date_range_picker_styles',
			'label' => __( 'Days range edges', 'jet-booking' ),
			'type'  => 'separator',
		];

		$controls['date_range_picker_edges_color'] = [
			'tab'   => 'style',
			'group' => 'section_date_range_picker_styles',
			'label' => __( 'Color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property'  => 'color',
					'selector'  => '.date-picker-wrapper div.day.first-date-selected',
					'important' => true,
				],
				[
					'property'  => 'color',
					'selector'  => '.date-picker-wrapper div.day.last-date-selected',
					'important' => true,
				]
			],
		];

		$controls['date_range_picker_edges_bg_color'] = [
			'tab'   => 'style',
			'group' => 'section_date_range_picker_styles',
			'label' => __( 'Background color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property'  => 'background-color',
					'selector'  => '.date-picker-wrapper div.day.first-date-selected',
					'important' => true,
				],
				[
					'property'  => 'background-color',
					'selector'  => '.date-picker-wrapper div.day.last-date-selected',
					'important' => true,
				]
			],
		];

		$controls['date_range_picker_trace_heading'] = [
			'tab'   => 'style',
			'group' => 'section_date_range_picker_styles',
			'label' => __( 'Days range trace', 'jet-booking' ),
			'type'  => 'separator',
		];

		$controls['date_range_picker_trace_color'] = [
			'tab'   => 'style',
			'group' => 'section_date_range_picker_styles',
			'label' => __( 'Color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'color',
					'selector' => '.date-picker-wrapper tbody div.day.checked',
				],
				[
					'property' => 'color',
					'selector' => '.date-picker-wrapper tbody div.day.hovering',
				],
				[
					'property' => 'color',
					'selector' => '.date-picker-wrapper tbody div.day.has-tooltip:hover',
				]
			],
		];

		$controls['date_range_picker_trace_bg_color'] = [
			'tab'   => 'style',
			'group' => 'section_date_range_picker_styles',
			'label' => __( 'Background color', 'jet-booking' ),
			'type'  => 'color',
			'css'   => [
				[
					'property' => 'background-color',
					'selector' => '.date-picker-wrapper tbody div.day.checked',
				],
				[
					'property' => 'background-color',
					'selector' => '.date-picker-wrapper tbody div.day.hovering',
				],
				[
					'property' => 'background-color',
					'selector' => '.date-picker-wrapper tbody div.day.hovering.has-tooltip:hover',
				]
			]
		];

		return $controls;

	}

}
