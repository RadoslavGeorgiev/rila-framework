<?php
namespace Rila;

/**
 * Handles the definition of a content block.
 *
 * This includes the title of a block, it's fields, layout and more.
 * It's used within static methods and is only triggered in the back-end.
 *
 * @since 0.1
 */
class Block_Definition {
	protected $data = array(
		'title'   => '',
		'fields'  => array(),
		'display' => 'block',
		'min'     => 0,
		'max'     => 0
	);

	/**
	 * Sets a property to the block.
	 *
	 * @since 0.1
	 *
	 * @param string $property The name of the property. Must be in the data array.
	 * @param mixed  $value    The value that should be used for the property.
	 */
	function __set( $property, $value ) {
		if( ! isset( $this->data[ $property ] ) ) {
			trigger_error( "Block definition does not support $property!" );
			return;
		}

		$this->data[ $property ] = $value;
	}

	/**
	 * Generates the hash for an ACF field.
	 *
	 * @since 0.1
	 *
	 * @return mixed[]
	 */
	public function get_hash() {
		$d = $this->data;

		$d[ 'label' ] = $d[ 'title' ];
		unset( $d[ 'title' ] );

		$d[ 'sub_fields' ] = $d[ 'fields' ];
		unset( $d[ 'fields' ] );

		return $d;
	}
}
