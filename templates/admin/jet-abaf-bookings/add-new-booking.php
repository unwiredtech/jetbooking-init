<?php
/**
 * Booking list add new booking template.
 *
 * @package JET_ABAF
 */

?>

<div class="jet-abaf-bookings-add-new">
	<cx-vui-button
		button-style="accent"
		size="mini"
		@click="showAddDialog()"
	>
		<template slot="label">
			<?php esc_html_e( 'Add New', 'jet-booking' ); ?>
		</template>
	</cx-vui-button>

	<cx-vui-popup
		:class="[ 'jet-abaf-popup', 'jet-abaf-popup-add', { 'jet-abaf-submitting': submitting } ]"
		v-model="addDialog"
		body-width="500px"
		ok-label="<?php esc_html_e( 'Add New', 'jet-booking' ) ?>"
		@on-cancel="addDialog = false"
		@on-ok="handleAdd"
	>
		<div slot="title" class="cx-vui-subtitle">
			<?php esc_html_e( 'Add New Booking:', 'jet-booking' ); ?>
		</div>

		<div
			slot="content"
			class="jet-abaf-bookings-error"
			v-if="overlappingBookings"
			v-html="overlappingBookings"
		></div>

		<div slot="content" class="jet-abaf-details">
			<br>

			<div class="jet-abaf-details__field jet-abaf-details__field-status">
				<div class="jet-abaf-details__label">
					<?php esc_html_e( 'Status:', 'jet-booking' ) ?>
				</div>
				<div class="jet-abaf-details__content">
					<select v-model="newItem.status">
						<option v-for="( label, value ) in statuses" :value="value" :key="value">
							{{ label }}
						</option>
					</select>
				</div>
			</div>

			<div class="jet-abaf-details__field jet-abaf-details__field-apartment_id">
				<div class="jet-abaf-details__label">
					<?php esc_html_e( 'Booking Item:', 'jet-booking' ) ?>
				</div>
				<div class="jet-abaf-details__content">
					<select @change="onApartmentChange()" v-model="newItem.apartment_id">
						<option v-for="( label, value ) in bookingInstances" :value="value" :key="value">
							{{ label }}
						</option>
					</select>

					<?php if ( jet_abaf()->wc->has_woocommerce() && 'wc_based' === jet_abaf()->settings->get( 'booking_mode' ) && ! jet_abaf()->tools->get_booking_posts() ) {
						printf(
							__( 'No booking products found. Create booking products to start using this functionality. <a href="%s" target="_blank">Create your first product</a>.', 'jet-booking' ),
							add_query_arg( [ 'post_type' => 'product', 'jet_booking_product' => 1 ], admin_url( 'post-new.php' ) )
						);
					} ?>
				</div>
			</div>

			<div
				:class="[ 'jet-abaf-details__booking-dates',  { 'jet-abaf-disabled': isDisabled } ]"
				ref="jetABAFDatePicker">
				<div class="jet-abaf-details__check-in-date">
					<div class="jet-abaf-details__label">
						<?php esc_html_e( 'Check in:', 'jet-booking' ) ?>
					</div>
					<div class="jet-abaf-details__content">
						<input type="text" v-model="newItem.check_in_date"/>
					</div>
				</div>

				<div class="jet-abaf-details__check-out-date">
					<div class="jet-abaf-details__label">
						<?php esc_html_e( 'Check out:', 'jet-booking' ) ?>
					</div>
					<div class="jet-abaf-details__content">
						<input type="text" v-model="newItem.check_out_date"/>
					</div>
				</div>
			</div>

			<div class="jet-abaf-details__field">
				<div class="jet-abaf-details__label">
					<?php esc_html_e( 'Booking Price:', 'jet-booking' ) ?>
				</div>
				<div class="jet-abaf-details__content" v-html="bookingPrice"></div>
			</div>

			<div class="jet-abaf-details__fields">
				<template v-for="field in fields">
					<div
						v-if="beVisible( field )"
						:key="field"
						:class="[ 'jet-abaf-details__field', 'jet-abaf-details__field-' + field ]"
					>
						<div class="jet-abaf-details__label">{{ field }}:</div>
						<div class="jet-abaf-details__content">
							<input type="text" v-model="newItem[ field ]"/>
						</div>
					</div>
				</template>
			</div>

			<div v-if="'plain' === bookingMode">
				<div v-if="orderPostType || wcIntegration" class="jet-abaf-details__field">
					<div class="jet-abaf-details__label">
						<template v-if="orderPostType">
							<?php esc_html_e( 'Create Booking Order', 'jet-booking' ) ?>
						</template>

						<template v-else-if="wcIntegration">
							<?php esc_html_e( 'Create WC Order', 'jet-booking' ) ?>
						</template>
					</div>

					<div v-if="orderPostType || wcIntegration" class="jet-abaf-details__content">
						<cx-vui-switcher v-model="createRelatedOrder"></cx-vui-switcher>
					</div>
				</div>

				<div v-if="orderPostType && createRelatedOrder" class="jet-abaf-details__field">
					<div class="jet-abaf-details__label">
						<?php esc_html_e( 'Order Status:', 'jet-booking' ) ?>
					</div>
					<div class="jet-abaf-details__content">
						<select v-model="bookingOrderStatus">
							<option v-for="( label, value ) in orderPostTypeStatuses" :key="value" :value="value">
								{{ label }}
							</option>
						</select>
					</div>
				</div>
			</div>

			<div v-else class="cx-vui-subtitle">
				<?php esc_html_e( 'Billing details:', 'jet-booking' ); ?>
			</div>

			<div
				v-if="( wcIntegration && createRelatedOrder ) || 'wc_based' === bookingMode"
				class="jet-abaf-details__fields"
			>
				<div class="jet-abaf-details__field">
					<div class="jet-abaf-details__label">
						<?php esc_html_e( 'First Name:', 'jet-booking' ); ?>
					</div>
					<div class="jet-abaf-details__content">
						<input type="text" v-model.trim="wcOrderFirstName"/>
					</div>
				</div>

				<div class="jet-abaf-details__field">
					<div class="jet-abaf-details__label">
						<?php esc_html_e( 'Last Name:', 'jet-booking' ); ?>
					</div>
					<div class="jet-abaf-details__content">
						<input type="text" v-model.trim="wcOrderLastName"/>
					</div>
				</div>

				<div class="jet-abaf-details__field">
					<div class="jet-abaf-details__label">
						<?php esc_html_e( 'Email Address:', 'jet-booking' ); ?>
					</div>
					<div class="jet-abaf-details__content">
						<input type="email" v-model.trim="wcOrderEmail"/>
					</div>
				</div>

				<div class="jet-abaf-details__field">
					<div class="jet-abaf-details__label">
						<?php esc_html_e( 'Phone:', 'jet-booking' ); ?>
					</div>
					<div class="jet-abaf-details__content">
						<input type="tel" v-model.trim="wcOrderPhone"/>
					</div>
				</div>
			</div>
		</div>
	</cx-vui-popup>
</div>