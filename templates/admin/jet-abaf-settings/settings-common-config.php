<?php
/**
 * Common datepicker configuration template.
 *
 * @package JET_ABAF
 */

?>

<div class="cx-vui-components">
	<cx-vui-switcher
		label="<?php esc_html_e( 'One day bookings', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'If this option is checked only single days bookings are allowed. If Weekly bookings are enabled this option will not work.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		v-if="'per_nights' !== settings.booking_period"
		:value="settings.one_day_bookings"
		@input="updateSetting( $event, 'one_day_bookings' )"
	></cx-vui-switcher>

	<cx-vui-switcher
		label="<?php esc_html_e( 'Week-long bookings', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'If this option is checked, only week-long bookings are allowed.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:value="settings.weekly_bookings"
		@input="updateSetting( $event, 'weekly_bookings' )"
	></cx-vui-switcher>

	<cx-vui-input
		label="<?php esc_html_e( 'Weekday offset', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'Allows you to change the first booked day of the week.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		size="fullwidth"
		:value="settings.week_offset"
		v-if="settings.weekly_bookings"
		@on-input-change="updateSetting( $event.target.value, 'week_offset' )"
		type="number"
	></cx-vui-input>

	<cx-vui-input
		label="<?php esc_html_e( 'Starting day offset', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'This string defines offset for the earliest date which is available to the user.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		size="fullwidth"
		:value="settings.start_day_offset"
		@on-input-change="updateSetting( $event.target.value, 'start_day_offset' )"
		type="number"
	></cx-vui-input>

	<cx-vui-input
		label="<?php esc_html_e( 'Min days', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'This number defines the minimum days of the selected range. If it equals 0, it means minimum days are not limited.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		size="fullwidth"
		:value="settings.min_days"
		@on-input-change="updateSetting( $event.target.value, 'min_days' )"
		type="number"
	></cx-vui-input>

	<cx-vui-input
		label="<?php esc_html_e( 'Max days', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'This number defines the maximum days of the selected range. If it equals 0, it means maximum days are not limited.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		size="fullwidth"
		:value="settings.max_days"
		@on-input-change="updateSetting( $event.target.value, 'max_days' )"
		type="number"
	></cx-vui-input>
</div>