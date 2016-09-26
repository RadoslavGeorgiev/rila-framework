<?php
namespace Rila;

/**
 * Handles the grouping of content blocks and their display.
 *
 * @since 0.1
 */
class Builder implements \Iterator, \Countable {
	/**
	 * Generates the needed layout groups for an ACF flexible content field.
	 *
	 * @since 0.1
	 *
	 * @param string[] $blocks The enabled content blocks.
	 * @return mixed[] The array for the 'layouts' option of an ACF field.
	 */
	public static function generate( $blocks ) {
		return array_map( array( Block::class, 'get_hash' ), $blocks );
	}

	/**
	 * Holds raw data, which contains block, but is used only
	 * for the initialisation of the block.
	 *
	 * @since 0.1
	 * @var mixed
	 */
	protected $data = array();

	/**
	 * Holds the blocks of the builder.
	 *
	 * @since 0.1
	 * @var mixed[]
	 */
	protected $blocks = array();

	/**
	 * Holds the index of the current block.
	 *
	 * @since 0.1
	 * @var int
	 */
	protected $pointer = 0;

	/**
	 * Initializes the class by receiving an array of content blocks.
	 *
	 * !! This class can be directly mapped within an item. !!
	 *
	 * @since 0.1
	 *
	 * @param mixed[] $blocks The existing blocks.
	 */
	public function __construct( $data ) {
		if( ! $data || ! is_array( $data ) ) {
			return;
		}

		$this->data = $data;
	}

	/**
	 * Turns blocks into actual content blocks.
	 *
	 * @since 0.1
	 */
	protected function init() {
		if( ! empty( $this->blocks ) || empty( $this->data ) ) {
			return;
		}

		foreach( $this->data as $block ) {
			if( ! isset( $block[ '__type' ] ) )
				continue;

			$type = $block[ '__type' ];
			$type = str_replace( '_ns_', '\\', $type );

			if( ! class_exists( $type ) ) {
				continue;
			}

			$block = new $type( $block );

			if( ! $block->skip() ) {
				$this->blocks[] = $block;
			}
		}
	}


	/**
	 * Returns the currently selected block.
	 *
	 * @since 0.1
	 * @return mixed
	 */
	public function current() {
		$this->init();

		return $this->blocks[ $this->pointer ];
	}

	/**
	 * Returns the current key.
	 *
	 * @since 0.1
	 * @return scalar
	 */
	public function key() {
		$this->init();

		return $this->pointer;
	}

	/**
	 * Goes to the next item.
	 *
	 * @since 0.1
	 * @return void
	 */
	public function next() {
		$this->pointer++;
	}

	/**
	 * Rewinds the loop.
	 *
	 * @since 0.1
	 * @return void
	 */
	public function rewind() {
		$this->pointer = 0;
	}

	/**
	 * Checks if there is an element at the current index.
	 *
	 * @since 0.1
	 * @return boolean
	 */
	public function valid() {
		$this->init();

		return $this->pointer < count( $this->blocks );
	}

	/**
	 * Returns the amount of elements in the collection.
	 *
	 * @since 0.1
	 *
	 * @return int
	 */
	public function count() {
		$this->init();

		return count( $this->blocks );
	}

	/**
	 * Converts the whole builder to a string by rendering it's content.
	 *
	 * @since 0.1
	 * @return string
	 */
	public function __toString() {
		$this->init();
		$out = '';

		foreach( $this->blocks as $block ) {
			$simplified = rila_cleanup_class( get_class( $block ), 'Block' );
			$simplified = strtolower( $simplified );

			# Remove misc. characters
			$simplified = preg_replace( '~[_\\\\\s]~', '-', $simplified );

			/**
			 * Allows the opening element for a block to be modified.
			 *
			 * @since 0.1
			 *
			 * @param string $html  The opening HTML.
			 * @param Block  $block The block and it's data.
			 * @return string
			 */
			$before = apply_filters( 'rila.builder.before_block', '<div class="block block-' . $simplified . '">', $block );

			/**
			 * Allows the closing element for a block to be modified.
			 *
			 * @since 0.1
			 *
			 * @param string $html  The closing HTML.
			 * @param Block  $block The block and it's data.
			 * @return string
			 */
			$after = apply_filters( 'rila.builder.after_block', '</div>', $block );

			$out .= $before . $block . $after;
		}

		return $out;
	}
}
