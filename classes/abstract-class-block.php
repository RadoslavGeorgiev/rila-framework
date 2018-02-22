<?php
namespace Rila;

use Rila\Meta;

/**
 * Adds the support for basic content blocks.
 *
 * @since 0.1
 */
abstract class Block implements \ArrayAccess {
	/**
	 * Holds the data about an individual block.
	 *
	 * @since 0.1
	 * @var mixed
	 */
	protected $data = array();

	/**
	 * Handles the setup of the block.
	 *
	 * Everything here is based on setting up a Block_Definiton object.
	 *
	 * @since 0.1
	 *
	 * @param Block_Definiton $block The block object to set up.
	 * @return void
	 */
	public static function setup( $block ) {}

	public static function factory( $block_data ) {
		$class_name = get_called_class();
		$block = new $class_name( $block_data );

		if( ! $block->skip() ) {
			return array( $block );
		} else {
			return array();
		}
	}

	/**
	 * Generates the ACF layout hash for a block.
	 *
	 * @since 0.1
	 *
	 * @param string $class_name The class of the block.
	 * @return mixed[]
	 */
	public static function get_hash( $class_name ) {
		$definition = new Block_Definition;

		$queue = array( $class_name );
		$parent = $class_name;
		while( ( $parent = get_parent_class( $parent ) ) && 'Rila\\Block' != $parent ) {
			$queue[] = $parent;
		}

		# Reverse the queue and setup
		foreach( array_reverse( $queue ) as $cn ) {
			$cn::setup( $definition );
		}

		$data = $definition->get_hash();
		$data[ 'name' ] = str_replace( '\\', '_ns_', $class_name );
		$data[ 'key' ] = str_replace( '\\', '_', $class_name );

		return $data;
	}

	/**
	 * Creates a new repeater group, usable within an Ultimate Fields repeater field.
	 *
	 * @since 0.3
	 *
	 * @param string $class_name The class of the block.
	 * @return Ultimate_Fields\Container\Repeater_Group;
	 */
	public static function get_group( $class_name ) {
		$group = new \Ultimate_Fields\Container\Repeater_Group( str_replace( '\\', '_ns_', $class_name ) );

		$queue = array( $class_name );
		$parent = $class_name;
		while( ( $parent = get_parent_class( $parent ) ) && 'Rila\\Block' != $parent ) {
			$queue[] = $parent;
		}

		# Reverse the queue and setup
		foreach( array_reverse( $queue ) as $cn ) {
			$cn::setup( $group );
		}

		return $group;
	}

	/**
	 * Initializes a new block by receiving it's data.
	 *
	 * @since 0.1
	 */
	public function __construct( $data ) {
		$this->data = $data;
	}

	/**
	 * Renders the block.
	 *
	 * @since 0.1
	 *
	 * @param mixed[] $data The data needed for rendering. Available as this->data too.
	 * @return string
	 */
	protected function render( $data ) {
		# Use the default block
		$block = strtolower( rila_cleanup_class( get_class( $this ), 'Block' ) );
		$block = str_replace( '_', '-', $block );
		$block = 'block/' . $block;

		return rila_view( $block, $data );
	}

	/**
	 * Returns the string representation of the block.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function __toString() {
		return rila_convert_to_string( $this, 'toString' );
	}

	/**
	 * Converts the block to a string.
	 * This is a public function, which is used in blocks, in order to avoid
	 * meaningless messages about __toString throwing exceptions.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public final function toString() {
		# Map values first
		if( method_exists( $this, 'map' ) ) {
			$map  = rila_dot_to_array( $this->map() );
			$data = array();

			foreach( $this->data as $key => $value ) {
				$data[ $key ] = Meta::map( $value, $key, $map );
			}

			$this->data = $data;
		}

		return $this->render( $this->data );
	}

	/**
	 * Determines if the block must be skipped.
	 *
	 * @since 0.1
	 *
	 * @return bool
	 */
	public function skip() {
		return false;
	}

	/**
	 * Adds a value to the data of the block.
	 *
	 * @since 0.1
	 *
	 * @param mixed $offset The index of the item.
	 * @param mixed $value The that should be added to the array.
	 */
	public function offsetSet( $offset, $value ) {
        if( is_null( $offset ) ) {
            $this->data[] = $value;
        } else {
            $this->data[ $offset ] = $value;
        }
    }

    /**
     * Checks if an element exists in the internal data array.
     *
     * @since 0.1
     *
     * @param mixed $offset The index of the item.
     */
    public function offsetExists( $offset ) {
        return isset( $this->data[ $offset ] );
    }

    /**
     * Unsets an element from the internal data array.
     *
     * @since 0.1
     *
     * @param mixed $offset The index of the item.
     */
    public function offsetUnset( $offset ) {
        unset( $this->data[ $offset ] );
    }

    /**
     * Returns the value for an offset.
     *
     * @since 0.1
     *
     * @param mixed $offset The index of the needed item.
     * @return mixed
     */
    public function offsetGet( $offset ) {
    	if( ! isset( $this->data[ $offset ] ) ) {
    		return null;
    	}

		$value = $this->data[ $offset ];
		if( method_exists( $this, 'map' ) ) {
			$map   = rila_dot_to_array( $this->map() );
			return Meta::map( $value, $offset, $map );
		} else {
			return $value;
		}
    }
}
