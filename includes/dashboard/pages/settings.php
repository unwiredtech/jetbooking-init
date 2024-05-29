<?php

namespace JET_ABAF\Dashboard\Pages;

use JET_ABAF\Dashboard\Helpers\Page_Config;

class Settings extends Base {

	/**
	 * Page slug.
	 *
	 * @return string
	 */
	public function slug() {
		return 'jet-abaf-settings';
	}

	/**
	 * Page title.
	 *
	 * @return string
	 */
	public function title() {
		return __( 'Settings', 'jet-booking' );
	}

	/**
	 * Is settings page.
	 *
	 * @return boolean
	 */
	public function is_settings_page() {
		return true;
	}

	/**
	 * Render.
	 *
	 * Settings page render function.
	 *
	 * @since  3.0.0 Refactored.
	 * @access public
	 *
	 * @return void
	 */
	public function render() {
		echo '<div id="jet-abaf-settings-page"></div>';
	}

	/**
	 * Return settings  page config object.
	 *
	 * @return Page_Config
	 */
	public function page_config() {
		return new Page_Config( $this->slug(), [
			'post_types'       => jet_abaf()->tools->get_post_types_for_js( [
				'value' => '',
				'label' => __( 'Select...', 'jet-booking' ),
			] ),
			'settings'         => jet_abaf()->settings->get_all(),
			'cron_schedules'   => $this->get_cron_schedules(),
			'db_tables_exists' => jet_abaf()->db->tables_exists(),
		] );
	}

	/**
	 * Get cron schedules.
	 *
	 * Returns registered cron intervals.
	 *
	 * @access public
	 *
	 * @return array
	 */
	public function get_cron_schedules() {

		$schedules = wp_get_schedules();

		uasort( $schedules, function ( $a, $b ) {
			if ( $a['interval'] == $b['interval'] ) {
				return 0;
			}

			return ( $a['interval'] < $b['interval'] ) ? -1 : 1;
		} );

		$result          = [];
		$found_intervals = [];

		foreach ( $schedules as $name => $int ) {
			if ( ! in_array( $int['interval'], $found_intervals ) ) {
				$diff = human_time_diff( 0, $int['interval'] );

				$result[] = [
					'value' => $name,
					'label' => $int['display'] . ' (' . $diff . ')',
				];

				$found_intervals[] = $int['interval'];
			}
		}

		return $result;

	}

	/**
	 * Assets.
	 *
	 * Dashboard settings page specific assets.
	 *
	 * @access public
	 *
	 * @return void
	 */
	public function assets() {

		parent::assets();

		$this->enqueue_script( 'moment-js', 'assets/lib/moment/js/moment.js' );
		$this->enqueue_script( 'vuejs-datepicker', 'assets/js/lib/vuejs-datepicker.min.js' );
		$this->enqueue_script( 'jet-abaf-meta-extras', 'assets/js/admin/meta-extras.js' );
		$this->enqueue_script( 'jet-abaf-schedule-manager', 'assets/js/admin/schedule-manager.js' );
		$this->enqueue_script( $this->slug(), 'assets/js/admin/settings.js' );

		$this->enqueue_style( 'jet-abaf-admin-style', 'assets/css/admin/jet-abaf-admin-style.css' );

	}

	/**
	 * Vue templates.
	 *
	 * Page components templates.
	 *
	 * @since  3.0.0 Added `settings-field-settings` template.
	 * @access public
	 *
	 * @return array
	 */
	public function vue_templates() {
		return [
			'settings',
			'settings-advanced',
			'settings-common-config',
			'settings-configuration',
			'settings-field-settings',
			'settings-general',
			'settings-labels',
			'settings-schedule',
			'settings-tools',
		];
	}

}