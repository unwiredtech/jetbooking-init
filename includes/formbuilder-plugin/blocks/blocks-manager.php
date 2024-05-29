<?php

namespace JET_ABAF\Formbuilder_Plugin\Blocks;

use \JET_ABAF\Formbuilder_Plugin\With_Form_Builder;
use \Jet_Form_Builder\Blocks\Types\Hidden_Field;

class Blocks_Manager {

	use With_Form_Builder;

	public function manager_init() {

		add_action( 'jet-form-builder/blocks/register', [ $this, 'register_fields' ] );
		add_action( 'jet-form-builder/editor-assets/before', [ $this, 'editor_assets' ] );

		add_filter( 'jet-form-builder/editor/hidden-field/config', [ $this, 'hidden_field_config' ] );
		add_filter( 'jet-form-builder/fields/hidden-field/value-cb', [ $this, 'custom_hidden_field_value' ], 10, 3 );

	}

	public function register_fields( $manager ) {
		$manager->register_block_type( new Check_In_Out_Field() );
	}

	public function editor_assets() {
		wp_enqueue_script(
			'jet-booking-form-builder-fields',
			JET_ABAF_URL . 'assets/js/builder.editor.js',
			[],
			JET_ABAF_VERSION,
			true
		);
	}

	/**
	 * Returns modified list of hidden field configurations.
	 *
	 * @string 3.3.0
	 *
	 * @param array $config List of hidden field configurations.
	 *
	 * @return mixed
	 */
	public function hidden_field_config( $config ) {

		$config['sources'][] = [
			'value' => 'booking_id',
			'label' => __( 'Current Booking ID', 'jet-booking' ),
		];

		return $config;

	}

	/**
	 * Handle hidden fields custom booking related value.
	 *
	 * @since 3.3.0
	 *
	 * @param mixed        $callback     The function to be called.
	 * @param string       $field_value  Value type name.
	 * @param Hidden_Field $hidden_field Hidden field instance.
	 *
	 * @return array
	 */
	public function custom_hidden_field_value( $callback, $field_value, $hidden_field ) {

		if ( 'booking_id' !== $field_value ) {
			return $callback;
		}

		$object = apply_filters( 'jet-booking/form-builder/hidden-field/object', null );

		if ( ! $object || ! is_a( $object, '\JET_ABAF\Resources\Booking' ) ) {
			return $callback;
		}

		return [ $object, 'get_id' ];

	}

}