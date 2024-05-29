<div>
	<cx-vui-switcher
		label="<?php _e( 'Hide DB columns manager', 'jet-booking' ); ?>"
		description="<?php _e( 'Check this to hide the columns manager option to prevent accidental DB changes.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:value="advancedSettings.hide_columns_manager"
		@input="updateSetting( $event, 'hide_columns_manager' )"
	></cx-vui-switcher>

	<cx-vui-switcher
		label="<?php _e( 'Enable iCal synchronization', 'jet-booking' ); ?>"
		description="<?php _e( 'Check this to allow export your bookings into iCal format and synchronize all your data with external calendars in iCal format.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:value="advancedSettings.ical_synch"
		@input="updateSetting( $event, 'ical_synch' )"
	></cx-vui-switcher>

	<cx-vui-select
		label="<?php _e( 'Calendar sync interval', 'jet-booking' ); ?>"
		description="<?php _e( 'Select interval between synchronizing calendars.', 'jet-booking' ); ?>"
		:options-list="cronSchedules"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="advancedSettings.synch_interval"
		@input="updateSetting( $event, 'synch_interval' )"
		v-if="advancedSettings.ical_synch"
	></cx-vui-select>

	<cx-vui-component-wrapper
		v-if="advancedSettings.ical_synch"
		label="<?php _e( 'Calendar sync start', 'jet-booking' ); ?>"
		description="<?php _e( 'Start calendar synchronization from this time.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth', 'schedule-time' ]"
	>
		<cx-vui-select
			:options-list="getInterval( 23 )"
			:value="advancedSettings.synch_interval_hours"
			@input="updateSetting( $event, 'synch_interval_hours' )"
			:prevent-wrap="true"
		></cx-vui-select>
		<span>:</span>
		<cx-vui-select
			:options-list="getInterval( 59 )"
			:value="advancedSettings.synch_interval_mins"
			@input="updateSetting( $event, 'synch_interval_mins' )"
			:prevent-wrap="true"
		></cx-vui-select>
		<span>HH:MM</span>
	</cx-vui-component-wrapper>

	<cx-vui-switcher
		label="<?php _e( 'Automatically remove temporary bookings', 'jet-booking' ); ?>"
		description="<?php _e( 'Check this to allow automatically remove bookings from database with temporary status `Created`.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:value="advancedSettings.remove_temporary_bookings"
		@input="updateSetting( $event, 'remove_temporary_bookings' )"
	></cx-vui-switcher>

	<cx-vui-select
		v-if="advancedSettings.remove_temporary_bookings"
		label="<?php _e( 'Remove interval', 'jet-booking' ); ?>"
		description="<?php _e( 'Select interval between removing bookings with temporary status.', 'jet-booking' ); ?>"
		:options-list="cronSchedules"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="advancedSettings.remove_interval"
		@input="updateSetting( $event, 'remove_interval' )"
	></cx-vui-select>

	<cx-vui-switcher
		label="<?php _e( 'Booking cancellation', 'jet-booking' ); ?>"
		description="<?php _e( 'Check this if the booking can be cancelled by the customer after it has been reserved. A refund will not be sent automatically.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:value="advancedSettings.booking_cancellation"
		@input="updateSetting( $event, 'booking_cancellation' )"
	></cx-vui-switcher>

	<cx-vui-component-wrapper
		v-if="advancedSettings.booking_cancellation"
		label="<?php _e( 'Cancellation deadline', 'jet-booking' ); ?>"
		description="<?php _e( 'Specify the limit and unit for cancellations before the start date of the booking.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth', 'cancellation-deadline' ]"
	>
		<cx-vui-input
			type="number"
			min="1"
			:value="advancedSettings.cancellation_limit"
			@on-input-change="updateSetting( $event.target.value, 'cancellation_limit' )"
		></cx-vui-input>

		<cx-vui-select
			:options-list="[
				{
					value: 'day',
					label: '<?php _e( 'Day(s)', 'jet-booking' ); ?>'
				},
				{
					value: 'week',
					label: '<?php _e( 'Week(s)', 'jet-booking' ); ?>'
				},
				{
					value: 'month',
					label: '<?php _e( 'Month(s)', 'jet-booking' ); ?>'
				}
			]"
			:value="advancedSettings.cancellation_unit"
			@input="updateSetting( $event, 'cancellation_unit' )"
			:prevent-wrap="true"
		></cx-vui-select>
	</cx-vui-component-wrapper>

	<cx-vui-switcher
		label="<?php _e( 'Hide Setup Wizard', 'jet-booking' ); ?>"
		description="<?php _e( 'Enable the toggle to hide Set Up page and avoid unnecessary plugin resets.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:value="advancedSettings.hide_setup"
		@input="updateSetting( $event, 'hide_setup' )"
	></cx-vui-switcher>
</div>