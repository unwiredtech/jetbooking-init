<?php

namespace JET_ABAF\Components\Elementor_Views\Dynamic_Tags\Tags;

use \Elementor\Controls_Manager;
use \Elementor\Core\DynamicTags\Tag;
use \Elementor\Modules\DynamicTags\Module as Parent_Module;
use \JET_ABAF\Components\Elementor_Views\Dynamic_Tags\Module as Child_Module;

defined( 'ABSPATH' ) || exit;

class Bookings_Count extends Tag {

	/**
	 * Get name.
	 *
	 * Retrieve the dynamic tag name.
	 *
	 * @since 2.2.5
	 *
	 * @return string The name.
	 */
	public function get_name() {
		return 'jet-bookings-count';
	}

	/**
	 * Get title.
	 *
	 * Retrieve the dynamic tag title.
	 *
	 * @since 2.2.5
	 *
	 * @return string The title.
	 */
	public function get_title() {
		return __( 'Bookings count', 'jet-booking' );
	}

	/**
	 * Get group.
	 *
	 * Retrieve the dynamic tag group.
	 *
	 * @since 2.2.5
	 *
	 * @return string The group.
	 */
	public function get_group() {
		return Child_Module::JET_GROUP;
	}

	/**
	 * Get categories.
	 *
	 * Retrieve the dynamic tag categories.
	 *
	 * @since 2.2.5
	 *
	 * @return array The categories.
	 */
	public function get_categories() {
		return [
			Parent_Module::TEXT_CATEGORY,
			Parent_Module::NUMBER_CATEGORY,
			Parent_Module::POST_META_CATEGORY,
		];
	}

	/**
	 * Is settings required.
	 *
	 * Point to the requires of the additional settings.
	 *
	 * @since 2.2.5
	 *
	 * @return string The group.
	 */
	public function is_settings_required() {
		return true;
	}

	/**
	 * Register controls.
	 *
	 * Used to add new controls to any element type.
	 *
	 * @since 2.2.5
	 */
	protected function register_controls() {

		$this->add_control(
			'bookings_period',
			[
				'type'  => Controls_Manager::HEADING,
				'label' => __( 'Bookings Period', 'plugin-name' ),
			]
		);

		$this->add_control(
			'start_date',
			[
				'type'        => Controls_Manager::TEXT,
				'label'       => __( 'Start Date', 'jet-booking' ),
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->add_control(
			'end_date',
			[
				'type'        => Controls_Manager::TEXT,
				'label'       => __( 'End Date', 'jet-booking' ),
				'label_block' => true,
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->add_control(
			'period_tip',
			[
				'type' => Controls_Manager::RAW_HTML,
				'raw'  => __( 'Enter date in Universal time format: </br> `Y-m-d H:i:s` or `Y-m-d`. <b>Example:</b></br> `1996-04-09 00:00:00` or `1996-04-09`.', 'jet-booking' ),
			]
		);

	}

	/**
	 * Render element.
	 *
	 * Generates the final HTML on the frontend.
	 *
	 * @since 2.2.5
	 */
	public function render() {

		$from = $this->get_settings_for_display( 'start_date' );
		$to   = $this->get_settings_for_display( 'end_date' );

		if ( empty( $from ) ) {
			echo __( 'Please select date range.', 'jet-booking' );

			return;
		}

		if ( empty( $to ) ) {
			$to = $from;
		}

		$booking = [
			'apartment_id'   => get_the_ID(),
			'check_in_date'  => strtotime( $from ),
			'check_out_date' => strtotime( $to ),
		];

		$units = jet_abaf()->db->get_booked_units( $booking );

		echo ! empty( $units ) ? count( $units ) : $this->get_settings_for_display( 'fallback' );

	}

}
