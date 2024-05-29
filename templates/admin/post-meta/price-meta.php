<div id='jet-abaf-price-meta-box'>
	<cx-vui-component-wrapper
		label="<?php _e( 'Price per 1 day/night', 'jet-booking' ); ?>"
		description="<?php _e( 'Name: _apartment_price.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'width-30-40', 'apartment-price' ]"
	>
		<cx-vui-input
			type="number"
			size="small"
			:preventWrap="'false'"
			:min="0"
			:max="10000000000000"
			:step="0.1"
			v-model="meta._apartment_price"
			@on-blur="updateSetting"
		></cx-vui-input>

		<cx-vui-button
			class="jet-abaf-rate-price"
			button-style="accent-border"
			size="mini"
			@click="showPopUp( meta._pricing_rates, meta._apartment_price, 'rates' )"
		>
			<template slot="label">
				<?php _e( 'Add rates', 'jet-booking' ); ?>
			</template>
		</cx-vui-button>

		<cx-vui-button
			button-style="accent-border"
			size="mini"
			@click="showPopUp( meta._weekend_prices, meta._apartment_price, 'weekend' )"
		>
			<template slot="label">
				<?php _e( 'Add weekend ', 'jet-booking' ); ?>
			</template>
		</cx-vui-button>
	</cx-vui-component-wrapper>

	<cx-vui-component-wrapper
		label="<?php _e( 'Seasonal prices', 'jet-booking' ); ?>"
		:wrapper-css="[ 'width-30-40' ]"
	>
		<cx-vui-repeater
			button-label="<?php _e( 'Add price', 'jet-booking' ); ?>"
			button-style="accent-border"
			button-size="mini"
			v-model="meta._seasonal_prices"
			@add-new-item="addSP"
		>
			<cx-vui-repeater-item
				v-for="( item, index ) in meta._seasonal_prices"
				:title="getRepeaterTitle( item )"
				:collapsed="isCollapsed( item )"
				:index="index"
				:key="index"
				@clone-item="cloneSP( item )"
				@delete-item="deleteSP( index )"
			>
				<cx-vui-input
					label="<?php _e( 'Title', 'jet-booking' ); ?>"
					size="fullwidth"
					:value="item.title"
					@on-blur="changeSPValue( $event, 'title', index )"
				></cx-vui-input>

				<cx-vui-component-wrapper
					label="<?php _e( 'Price per 1 day/night', 'jet-booking' ); ?>"
					:wrapper-css="[ 'width-30-40', 'apartment-price' ]"
				>
					<cx-vui-input
						size="default"
						type="number"
						:preventWrap="'false'"
						:min="0"
						:max="10000000000000"
						:step="0.1"
						:value="item.price"
						@on-blur="changeSPValue( $event, 'price', index )"
					></cx-vui-input>

					<cx-vui-button
						class="jet-abaf-rate-price"
						button-style="accent-border"
						size="mini"
						@click="showPopUp( item._pricing_rates, item.price, 'rates' )"
					>
						<template slot="label">
							<?php _e( 'Add rates', 'jet-booking' ); ?>
						</template>
					</cx-vui-button>

					<cx-vui-button
						button-style="accent-border"
						size="mini"
						@click="showPopUp( item._weekend_prices, item.price, 'weekend' )"
					>
						<template slot="label"><?php _e( 'Add weekend ', 'jet-booking' ); ?></template>
					</cx-vui-button>
				</cx-vui-component-wrapper>

				<cx-vui-switcher
					label="<?php _e( 'Date Picker Configuration', 'jet-booking' ); ?>"
					description="<?php _e( 'You can enable and setup datepicker configuration for apartment season.', 'jet-booking' ); ?>"
					:wrapper-css="[ 'equalwidth' ]"
					:return-true="true"
					:return-false="false"
					v-model="item.enable_config"
					@input="changeSPValue( $event, 'enable_config', index )"
				></cx-vui-switcher>

				<div v-if="item.enable_config">
					<cx-vui-input
						label="<?php _e( 'Starting day offset', 'jet-booking' ); ?>"
						size="fullwidth"
						:value="item.start_day_offset"
						@on-blur="changeSPValue( $event, 'start_day_offset', index )"
					></cx-vui-input>

					<cx-vui-input
						label="<?php _e( 'Min days', 'jet-booking' ); ?>"
						size="fullwidth"
						:value="item.min_days"
						@on-blur="changeSPValue( $event, 'min_days', index )"
					></cx-vui-input>

					<cx-vui-input
						label="<?php _e( 'Max days', 'jet-booking' ); ?>"
						size="fullwidth"
						:value="item.max_days"
						@on-blur="changeSPValue( $event, 'max_days', index )"
					></cx-vui-input>
				</div>

				<cx-vui-component-wrapper
					label="<?php _e( 'Start Date', 'jet-booking' ); ?>"
					:wrapper-css="[ 'width-30-40' ]"
				>
					<vuejs-datepicker
						input-class="cx-vui-input size-fullwidth"
						placeholder="<?php _e( 'Select Date', 'jet-booking' ); ?>"
						:value="secondsToMilliseconds( item.startTimestamp )"
						:format="datePickerFormat"
						@selected="changeSPValue( $event, 'startTimestamp', index )"
					></vuejs-datepicker>
				</cx-vui-component-wrapper>

				<cx-vui-component-wrapper
					label="<?php _e( 'End Date', 'jet-booking' ); ?>"
					:wrapper-css="[ 'width-30-40' ]"
				>
					<vuejs-datepicker
						input-class="cx-vui-input size-fullwidth"
						placeholder="<?php _e( 'Select Date', 'jet-booking' ); ?>"
						:value="secondsToMilliseconds( item.endTimestamp )"
						:format="datePickerFormat"
						@selected="changeSPValue( $event, 'endTimestamp', index )"
					></vuejs-datepicker>
				</cx-vui-component-wrapper>
				<?php /*
				<cx-vui-select
					label="<?php esc_html_e( 'Repeat Season', 'jet-booking' ); ?>"
					description="<?php esc_html_e( 'Select the aging period of the season. For example every year, month or week.', 'jet-booking' ); ?>"
					:options-list="period_repeats_seasons"
					:wrapper-css="[ 'width-30-40' ]"
					:size="'fullwidth'"
					:value="item.repeatSeason"
					@input="changeSPValue( $event, 'repeatSeason', index )"
				></cx-vui-select>
				*/ ?>

			</cx-vui-repeater-item>
		</cx-vui-repeater>
	</cx-vui-component-wrapper>

	<cx-vui-popup
		v-model="popUpActive_rates"
		body-width="600px"
		:footer="false"
		@on-cancel="hidePopUp('rates')"
		class="jet-apb-popup"
	>
		<div class="cx-vui-subtitle" slot="title">
			<?php _e( 'Set up advanced pricing rates', 'jet-booking' ); ?>
		</div>

		<div slot="content">
			<div class="jet-abaf-rates-list">
				<div class="jet-abaf-rates-list__item default">
					<div class="jet-abaf-rates-list__col col-title">
						<?php _e( 'From', 'jet-booking' ); ?>&nbsp;&nbsp;
						<input type="number" value="1" disabled>&nbsp;&nbsp;
						<?php _e( 'days/nights', 'jet-booking' ); ?>
					</div>

					<div class="jet-abaf-rates-list__col col-price">
						<?php _e( 'Price:', 'jet-booking' ); ?>&nbsp;&nbsp;
						<input type="number" min="0" :value="popUpData.price" disabled>&nbsp;&nbsp;
						<?php _e( 'per day/night', 'jet-booking' ); ?>
					</div>
					<div class="jet-abaf-rates-list__col col-delete">&nbsp;</div>
				</div>

				<div
					class="jet-abaf-rates-list__item"
					v-for="( rate, index ) in popUpData.items"
					:key="'rate-' + index">
					<div class="jet-abaf-rates-list__col col-title">
						<?php _e( 'From', 'jet-booking' ); ?>&nbsp;&nbsp;
						<input type="number" min="2" step="1" v-model="rate.duration">&nbsp;&nbsp;
						<?php _e( 'days/nights', 'jet-booking' ); ?>
					</div>

					<div class="jet-abaf-rates-list__col col-price">
						<?php _e( 'Price:', 'jet-booking' ); ?>&nbsp;&nbsp;
						<input type="number" min="0" step="0.1" v-model="rate.value">&nbsp;&nbsp;
						<?php _e( 'per day/night', 'jet-booking' ); ?>
					</div>
					<div class="jet-abaf-rates-list__col col-delete">
						<span @click="deleteRate( index )" class="dashicons dashicons-trash"></span>
					</div>
				</div>
			</div>

			<a href="#" class="jet-abaf-add-rate" @click.prevent="newRate">
				<?php _e( '+ Add new rate', 'jet-booking' ); ?>
			</a>

			<div class="jet-abaf-popup-actions">
				<button class="button button-primary" type="button" aria-expanded="true" @click="saveMeta">
					<span v-if="!saving">
						<?php _e( 'Save', 'jet-booking' ); ?>
					</span>
					<span v-else>
						<?php _e( 'Saving...', 'jet-booking' ); ?>
					</span>
				</button>

				<button class="button-link" type="button" aria-expanded="true" @click="hidePopUp('rates')">
					<?php _e( 'Cancel', 'jet-booking' ); ?>
				</button>
			</div>
		</div>
	</cx-vui-popup>

	<cx-vui-popup
		v-model="popUpActive_weekend"
		body-width="400px"
		class="jet-apb-popup"
		@on-ok="saveMeta"
		@on-cancel="hidePopUp('weekend')"
		ok-label="<?php _e( 'Save', 'jet-booking' ) ?>"
		cancel-label="<?php _e( 'Close', 'jet-booking' ) ?>"
	>
		<div class="cx-vui-subtitle" slot="title">
			<?php _e( 'Set up weekend pricing', 'jet-appointments-booking' ); ?>
		</div>

		<template slot="content">
			<div class="jet-abaf-weekend-list__item" v-for="( item, index ) in popUpData.items" :key="'price-' + index">
				<div class="jet-abaf-weekend-list__col col-title">
					<strong>{{ weekdays_label[ index ] }}</strong>
				</div>

				<div class="jet-abaf-weekend-list__col col-price">
					<cx-vui-switcher
						v-model="item.active"
						class="jet-abaf-weekend-switcher"
					></cx-vui-switcher>

					<label>
						<?php _e( 'Price:', 'jet-booking' ); ?>
					</label>

					<cx-vui-input
						size="small"
						type="number"
						:preventWrap="false"
						:min="0"
						:max="10000000000000"
						:step="0.1"
						v-model="item.price"
					></cx-vui-input>&nbsp;&nbsp;

					<?php _e( 'per day/night', 'jet-booking' ); ?>
				</div>
			</div>
		</template>
	</cx-vui-popup>
</div>
