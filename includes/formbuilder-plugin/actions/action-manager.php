<?php
/**
 * Registering form actions.
 *
 * The script for registering the action is displayed in the \JET_ABAF\Formbuilder_Plugin\Blocks\Blocks_Manager class.
 *
 * @since   2.2.5
 * @package JET_ABAF\Formbuilder_Plugin\Actions
 */

namespace JET_ABAF\Formbuilder_Plugin\Actions;

use JET_ABAF\Formbuilder_Plugin\With_Form_Builder;
use Jet_Form_Builder\Blocks\Block_Helper;
use Jet_Form_Builder\Actions\Manager;

class Action_Manager {

	use With_Form_Builder;

	public function manager_init() {
		add_action( 'jet-form-builder/actions/register', [ $this, 'register_actions' ] );
		add_filter( 'jet-booking/form-fields/queried-post-id', [ $this, 'set_booking_queried_post_id' ] );
	}

	/**
	 * Register actions.
	 *
	 * Register booking related form actions.
	 *
	 * @since 2.2.5
	 * @singe 3.3.0 Update action added.
	 *
	 * @param Manager $manager JetFormBuilder action manager instance.
	 */
	public function register_actions( $manager ) {
		$manager->register_action_type( new Types\Insert_Booking() );
		$manager->register_action_type( new Types\Update_Booking() );
	}

	/**
	 * Set booking queried post id.
	 *
	 *  Setup bookings form actions queried post id.
	 *
	 * @since 3.3.2
	 *
	 * @param int $id Post ID.
	 *
	 * @return int|mixed|null
	 */
	public function set_booking_queried_post_id( $id ) {

		$post = get_post( $id );

		if ( $post->post_type === jet_abaf()->settings->get( 'apartment_post_type' ) ) {
			return $id;
		}

		$block = Block_Helper::find_by_block_name( jet_fb_live()->blocks, 'jet-forms/hidden-field' );

		if ( empty( $block ) ) {
			return $id;
		}

		$attrs = $block['attrs'];

		if ( empty( $attrs['field_value'] ) ) {
			return $id;
		}

		$object = null;

		switch ( $attrs['field_value'] ) {
			case 'booking_id':
				$object = apply_filters( 'jet-booking/formbuilder-plugin/actions/object', $object );
				break;

			case 'query_var':
				$var        = ! empty( $attrs['query_var_key'] ) ? $attrs['query_var_key'] : '';
				$booking_id = ( $var && isset( $_REQUEST[ $var ] ) ) ? absint( $_REQUEST[ $var ] ) : false;
				$object     = jet_abaf_get_booking( $booking_id );

				break;

			default:
				break;
		}

		if ( ! $object || ! is_a( $object, '\JET_ABAF\Resources\Booking' ) ) {
			return $id;
		}

		return $object->get_apartment_id();

	}

}