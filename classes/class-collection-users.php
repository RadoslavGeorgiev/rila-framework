<?php
namespace Rila\Collection;

use Rila\Collection;

/**
 * Handles collections ot users.
 *
 * @since 0.1
 */
class Users extends Collection {
	/**
	 * Holds the type of supported items.
	 *
	 * @since 0.1
	 * @var string
	 */
	protected $item_type = 'Rila\\User';

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
		$args = array();

		if( ! is_null( $this->ids ) ) {
			if( empty( $this->ids ) ) {
				$this->initialized = true;
				return;
			}
			
			$args = array(
				'include' => $this->ids,
				'order'   => 'ASC',
				'orderby' => 'include'
			);
		} elseif( ! is_null( $this->args ) ) {
			$args = $this->args;
		}

		if( isset( $args[ 'meta_query' ] ) ) {
			$args[ 'meta_query' ] = array_merge( $args[ 'meta_query' ], $this->meta_query );
		} else {
			$args[ 'meta_query' ] = $this->meta_query;
		}

		$this->items = array_map( 'rila_user', get_users( $args ) );
		$this->initialized = true;
	}

	/**
	 * Returns all items of the type.
	 *
	 * @since 0.1
	 * @return Posts;
	 */
	public static function all() {
		return new Users();
	}

	/**
	 * Adds a value to the arguments.
	 *
	 * @since 0.1
	 * @param string $key   The key for the argument.
	 * @param mixed  $value The value of the argument.
	 */
	protected function set( $key, $value ) {
		static $argument_keys;

		if( is_null( $argument_keys ) ) {
			$argument_keys = [ 'blog_id', 'role', 'role__in', 'role__not_in', 'meta_key', 'meta_value', 'meta_compare', 'meta_query', 'date_query', 'include', 'exclude', 'orderby', 'order', 'offset', 'search', 'number', 'count_total', 'fields', 'who' ];
		}

		if( in_array( $key, $argument_keys ) ) {
			$this->args[ $key ] = $value;
		} else {
			$this->meta_query[] = compact( 'key', 'value' );
		}
	}
}
