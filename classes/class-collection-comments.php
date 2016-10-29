<?php
namespace Rila\Collection;

use Rila\Collection;

/**
 * Handles collections of comments.
 *
 * @since 0.1
 */
class Comments extends Collection {
	/**
	 * Holds the type of supported items.
	 *
	 * @since 0.1
	 * @var string
	 */
	protected $item_type = 'Rila\\Comment';

	/**
	 * Holds additional values for a meta query.
	 *
	 * @since 0.1
	 * @var mixed[]
	 */
	protected $meta_query = array();

	/**
	 * Loads data from the database.
	 *
	 * @since 0.1
	 */
	protected function load() {
		$args = array(
			'order' => 'ASC'
		);

		if( ! is_null( $this->ids ) ) {
			$args = array(
				'comment__in' => $this->ids,
				'order'       => 'ASC',
				'orderby'     => 'comment__in'
			);
		} elseif( ! is_null( $this->args ) ) {
			$args = array_merge( $args, $this->args );
		}

		if( isset( $args[ 'meta_query' ] ) ) {
			$args[ 'meta_query' ] = array_merge( $args[ 'meta_query' ], $this->meta_query );
		} else {
			$args[ 'meta_query' ] = $this->meta_query;
		}

		$this->items = array_map( 'rila_comment', get_comments( $args ) );
		$this->initialized = true;
	}

	/**
	 * Returns all items of the type.
	 *
	 * @since 0.1
	 * @return Posts;
	 */
	public static function all() {
		return new Comments();
	}

	/**
	 * Adds a value to the arguments.
	 *
	 * @since 0.1
	 * @param string $key   The key for the argument.
	 * @param mixed  $value The value of the argument.
	 */
	protected function set( $key, $value ) {
		static $dummy;

		if( is_null( $dummy ) ) {
			$dummy = new \WP_Comment( new \stdClass() );
		}

		if( property_exists( $dummy, $key ) ) {
			$this->args[ $key ] = $value;
		} else {
			$this->meta_query[] = compact( 'key', 'value' );
		}
	}
}
