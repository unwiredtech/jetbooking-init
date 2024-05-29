<div class="jet-abaf-settings-schedule">
	<div class="jet-abaf-disabled-days jet-abaf-settings-schedule__column">
		<h4 slot="title" class="cx-vui-subtitle">
			<?php _e( 'Weekday Booking Rules', 'jet-booking' ); ?>
		</h4>
		<div class="cx-vui-component__desc">
			<?php _e( 'Configure which weekdays will be available for checking in/out and which will be disabled.', 'jet-booking' ); ?>
		</div>

		<cx-vui-list-table>
			<cx-vui-list-table-heading
				:slots="[ 'day', 'disable', 'check_in', 'check_out' ]"
				slot="heading"
			>
				<span slot="day"></span>
				<span slot="disable">
					<?php _e( 'Disable', 'jet-booking' ); ?>
				</span>
				<span slot="check_in">
					<?php _e( 'Check In', 'jet-booking' ); ?>
				</span>
				<span slot="check_out">
					<?php _e( 'Check Out', 'jet-booking' ); ?>
				</span>
			</cx-vui-list-table-heading>

			<cx-vui-list-table-item
				slot="items"
				:slots="[ 'day', 'disable', 'check_in', 'check_out' ]"
				class-name="status-row"
			>
				<span slot="day">
					<?php _e( 'Status', 'jet-booking' ); ?>
				</span>
				<span slot="disable">{{ disabledDaysStatusLabel }}</span>
				<span slot="check_in">{{ checkInOutDaysStatusLabel( 'in' ) }}</span>
				<span slot="check_out">{{ checkInOutDaysStatusLabel( 'out' ) }}</span>
			</cx-vui-list-table-item>

			<cx-vui-list-table-item
				slot="items"
				:slots="[ 'day', 'disable', 'check_in', 'check_out' ]"
			>
				<span slot="day">
					<?php _e( 'Monday', 'jet-booking' ); ?>
				</span>
				<div slot="disable">
					<cx-vui-switcher
						:return-true="true"
						:return-false="false"
						v-model="settings.disable_weekday_1"
						@input="updateSetting( $event, 'disable_weekday_1' )"
					></cx-vui-switcher>
				</div>
				<div slot="check_in">
					<cx-vui-switcher
						v-if="! settings.disable_weekday_1"
						:return-true="true"
						:return-false="false"
						v-model="settings.check_in_weekday_1"
						@input="updateSetting( $event, 'check_in_weekday_1' )"
					></cx-vui-switcher>
					<span v-else class="dashicons dashicons-no-alt"></span>
				</div>
				<div slot="check_out">
					<cx-vui-switcher
						v-if="! settings.disable_weekday_1"
						:return-true="true"
						:return-false="false"
						v-model="settings.check_out_weekday_1"
						@input="updateSetting( $event, 'check_out_weekday_1' )"
					></cx-vui-switcher>
					<span v-else class="dashicons dashicons-no-alt"></span>
				</div>
			</cx-vui-list-table-item>

			<cx-vui-list-table-item
				slot="items"
				:slots="[ 'day', 'disable', 'check_in', 'check_out' ]"
			>
				<span slot="day">
					<?php _e( 'Tuesday', 'jet-booking' ); ?>
				</span>
				<div slot="disable">
					<cx-vui-switcher
						:return-true="true"
						:return-false="false"
						v-model="settings.disable_weekday_2"
						@input="updateSetting( $event, 'disable_weekday_2' )"
					></cx-vui-switcher>
				</div>
				<div slot="check_in">
					<cx-vui-switcher
						v-if="! settings.disable_weekday_2"
						:return-true="true"
						:return-false="false"
						v-model="settings.check_in_weekday_2"
						@input="updateSetting( $event, 'check_in_weekday_2' )"
					></cx-vui-switcher>
					<span v-else class="dashicons dashicons-no-alt"></span>
				</div>
				<div slot="check_out">
					<cx-vui-switcher
						v-if="! settings.disable_weekday_2"
						:return-true="true"
						:return-false="false"
						v-model="settings.check_out_weekday_2"
						@input="updateSetting( $event, 'check_out_weekday_2' )"
					></cx-vui-switcher>
					<span v-else class="dashicons dashicons-no-alt"></span>
				</div>
			</cx-vui-list-table-item>

			<cx-vui-list-table-item
				slot="items"
				:slots="[ 'day', 'disable', 'check_in', 'check_out' ]"
			>
				<span slot="day">
					<?php _e( 'Wednesday', 'jet-booking' ); ?>
				</span>
				<div slot="disable">
					<cx-vui-switcher
						:return-true="true"
						:return-false="false"
						v-model="settings.disable_weekday_3"
						@input="updateSetting( $event, 'disable_weekday_3' )"
					></cx-vui-switcher>
				</div>
				<div slot="check_in">
					<cx-vui-switcher
						v-if="! settings.disable_weekday_3"
						:return-true="true"
						:return-false="false"
						v-model="settings.check_in_weekday_3"
						@input="updateSetting( $event, 'check_in_weekday_3' )"
					></cx-vui-switcher>
					<span v-else class="dashicons dashicons-no-alt"></span>
				</div>
				<div slot="check_out">
					<cx-vui-switcher
						v-if="! settings.disable_weekday_3"
						:return-true="true"
						:return-false="false"
						v-model="settings.check_out_weekday_3"
						@input="updateSetting( $event, 'check_out_weekday_3' )"
					></cx-vui-switcher>
					<span v-else class="dashicons dashicons-no-alt"></span>
				</div>
			</cx-vui-list-table-item>

			<cx-vui-list-table-item
				slot="items"
				:slots="[ 'day', 'disable', 'check_in', 'check_out' ]"
			>
				<span slot="day">
					<?php _e( 'Thursday', 'jet-booking' ); ?>
				</span>
				<div slot="disable">
					<cx-vui-switcher
						:return-true="true"
						:return-false="false"
						v-model="settings.disable_weekday_4"
						@input="updateSetting( $event, 'disable_weekday_4' )"
					></cx-vui-switcher>
				</div>
				<div slot="check_in">
					<cx-vui-switcher
						v-if="! settings.disable_weekday_4"
						:return-true="true"
						:return-false="false"
						v-model="settings.check_in_weekday_4"
						@input="updateSetting( $event, 'check_in_weekday_4' )"
					></cx-vui-switcher>
					<span v-else class="dashicons dashicons-no-alt"></span>
				</div>
				<div slot="check_out">
					<cx-vui-switcher
						v-if="! settings.disable_weekday_4"
						:return-true="true"
						:return-false="false"
						v-model="settings.check_out_weekday_4"
						@input="updateSetting( $event, 'check_out_weekday_4' )"
					></cx-vui-switcher>
					<span v-else class="dashicons dashicons-no-alt"></span>
				</div>
			</cx-vui-list-table-item>

			<cx-vui-list-table-item
				slot="items"
				:slots="[ 'day', 'disable', 'check_in', 'check_out' ]"
			>
				<span slot="day">
					<?php _e( 'Friday', 'jet-booking' ); ?>
				</span>
				<div slot="disable">
					<cx-vui-switcher
						:return-true="true"
						:return-false="false"
						v-model="settings.disable_weekday_5"
						@input="updateSetting( $event, 'disable_weekday_5' )"
					></cx-vui-switcher>
				</div>
				<div slot="check_in">
					<cx-vui-switcher
						v-if="! settings.disable_weekday_5"
						:return-true="true"
						:return-false="false"
						v-model="settings.check_in_weekday_5"
						@input="updateSetting( $event, 'check_in_weekday_5' )"
					></cx-vui-switcher>
					<span v-else class="dashicons dashicons-no-alt"></span>
				</div>
				<div slot="check_out">
					<cx-vui-switcher
						v-if="! settings.disable_weekday_5"
						:return-true="true"
						:return-false="false"
						v-model="settings.check_out_weekday_5"
						@input="updateSetting( $event, 'check_out_weekday_5' )"
					></cx-vui-switcher>
					<span v-else class="dashicons dashicons-no-alt"></span>
				</div>
			</cx-vui-list-table-item>

			<cx-vui-list-table-item
				slot="items"
				:slots="[ 'day', 'disable', 'check_in', 'check_out' ]"
			>
				<span slot="day">
					<?php _e( 'Saturday', 'jet-booking' ); ?>
				</span>
				<div slot="disable">
					<cx-vui-switcher
						:return-true="true"
						:return-false="false"
						v-model="settings.disable_weekend_1"
						@input="updateSetting( $event, 'disable_weekend_1' )"
					></cx-vui-switcher>
				</div>
				<div slot="check_in">
					<cx-vui-switcher
						v-if="! settings.disable_weekend_1"
						:return-true="true"
						:return-false="false"
						v-model="settings.check_in_weekend_1"
						@input="updateSetting( $event, 'check_in_weekend_1' )"
					></cx-vui-switcher>
					<span v-else class="dashicons dashicons-no-alt"></span>
				</div>
				<div slot="check_out">
					<cx-vui-switcher
						v-if="! settings.disable_weekend_1"
						:return-true="true"
						:return-false="false"
						v-model="settings.check_out_weekend_1"
						@input="updateSetting( $event, 'check_out_weekend_1' )"
					></cx-vui-switcher>
					<span v-else class="dashicons dashicons-no-alt"></span>
				</div>
			</cx-vui-list-table-item>

			<cx-vui-list-table-item
				slot="items"
				:slots="[ 'day', 'disable', 'check_in', 'check_out' ]"
			>
				<span slot="day">
					<?php _e( 'Sunday', 'jet-booking' ); ?>
				</span>
				<div slot="disable">
					<cx-vui-switcher
						:return-true="true"
						:return-false="false"
						v-model="settings.disable_weekend_2"
						@input="updateSetting( $event, 'disable_weekend_2' )"
					></cx-vui-switcher>
				</div>
				<div slot="check_in">
					<cx-vui-switcher
						v-if="! settings.disable_weekend_2"
						:return-true="true"
						:return-false="false"
						v-model="settings.check_in_weekend_2"
						@input="updateSetting( $event, 'check_in_weekend_2' )"
					></cx-vui-switcher>
					<span v-else class="dashicons dashicons-no-alt"></span>
				</div>
				<div slot="check_out">
					<cx-vui-switcher
						v-if="! settings.disable_weekend_2"
						:return-true="true"
						:return-false="false"
						v-model="settings.check_out_weekend_2"
						@input="updateSetting( $event, 'check_out_weekend_2' )"
					></cx-vui-switcher>
					<span v-else class="dashicons dashicons-no-alt"></span>
				</div>
			</cx-vui-list-table-item>
		</cx-vui-list-table>
	</div>

	<div class="jet-abaf-days-off jet-abaf-settings-schedule__column">
		<cx-vui-collapse :collapsed="false">
			<h4 slot="title" class="cx-vui-subtitle">
				<?php _e( 'Days Off', 'jet-booking' ); ?>
			</h4>

			<div slot="content">
				<div class="jet-abaf-days-off__heading">
					<div class="cx-vui-component__desc">
						<?php _e( 'Set the days off, holidays, and weekend dates.', 'jet-booking' ); ?>
					</div>

					<cx-vui-button size="mini" button-style="accent" @click="showEditDay( 'days_off' )">
						<span slot="label">
							<?php _e( '+ Add Days', 'jet-booking' ); ?>
						</span>
					</cx-vui-button>
				</div>

				<div class="jet-abaf-days-off__body">
					<div class="jet-abaf-days-off-schedule-slot" v-for="(offDate, key) in settings.days_off" :key="key">
						<div class="jet-abaf-days-off-schedule-slot__head">
							<div class="jet-abaf-days-off-schedule-slot__head-name">{{ offDate.name }}</div>

							<div class="jet-abaf-days-off-schedule-slot__head-actions">
								<span class="dashicons dashicons-edit" @click="showEditDay( 'days_off', offDate )"></span>

								<div style="position:relative;">
									<span class="dashicons dashicons-trash" @click="confirmDeleteDay( offDate )"></span>

									<div class="cx-vui-tooltip" v-if="deleteDayTrigger === offDate">
										<?php _e( 'Are you sure?', 'jet-booking' ); ?>

										<br>

										<span class="cx-vui-repeater-item__confrim-del" @click="deleteDay( 'days_off', offDate )">
											<?php _e( 'Yes', 'jet-booking' ); ?>
										</span>
										/
										<span class="cx-vui-repeater-item__cancel-del" @click="deleteDayTrigger = null">
											<?php _e( 'No', 'jet-booking' ); ?>
										</span>
									</div>
								</div>
							</div>
						</div>

						<div class="jet-abaf-days-off-schedule-slot__body">
							{{ offDate.start }}<span v-if=offDate.end> — {{ offDate.end }}</span>
						</div>
					</div>
				</div>
			</div>
		</cx-vui-collapse>
	</div>

	<cx-vui-popup
		v-model="editDay"
		body-width="600px"
		ok-label="<?php _e( 'Save', 'jet-booking' ) ?>"
		cancel-label="<?php _e( 'Cancel', 'jet-booking' ) ?>"
		@on-cancel="handleDayCancel"
		@on-ok="handleDayOk"
	>
		<div class="cx-vui-subtitle" slot="title">
			<?php _e( 'Select Days', 'jet-booking' ); ?>
		</div>

		<cx-vui-input
			label="<?php _e( 'Range Label', 'jet-booking' ); ?>"
			description="<?php _e( 'Name the range that will be unavailable for booking (e.g., name of the holiday).', 'jet-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			size="fullwidth"
			v-model="date.name"
			slot="content"
		></cx-vui-input>

		<cx-vui-component-wrapper
			:wrapper-css="[ 'equalwidth' ]"
			label="<?php _e( 'Start Date *', 'jet-booking' ); ?>"
			description="<?php _e( 'Pick the start day.', 'jet-booking' ); ?>"
			slot="content"
		>
			<vuejs-datepicker
				input-class="cx-vui-input size-fullwidth"
				placeholder="<?php _e( 'Select Date', 'jet-booking' ); ?>"
				:format="datePickerFormat"
				:disabled-dates="disabledDate"
				:value="secondsToMilliseconds( date.startTimeStamp )"
				:monday-first="true"
				@selected="selectedDate( $event, 'start' )"
			></vuejs-datepicker>
		</cx-vui-component-wrapper>

		<cx-vui-component-wrapper
			:wrapper-css="[ 'equalwidth' ]"
			label="<?php _e( 'End Date', 'jet-booking' ); ?>"
			description="<?php _e( 'Pick the end day.', 'jet-booking' ); ?>"
			slot="content"
		>
			<vuejs-datepicker
				input-class="cx-vui-input size-fullwidth"
				placeholder="<?php _e( 'Select Date', 'jet-booking' ); ?>"
				:format="datePickerFormat"
				:disabled-dates="disabledDate"
				:value="secondsToMilliseconds( date.endTimeStamp )"
				:monday-first="true"
				@selected="selectedDate( $event, 'end' )"
			></vuejs-datepicker>
		</cx-vui-component-wrapper>
	</cx-vui-popup>
</div>