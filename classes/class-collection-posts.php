<?php
namespace Rila\Collection;

use Rila\Collection;

/**
 * Handles collections of posts.
 *
 * @since 0.1
 */
class Posts extends Collection {
	/**
	 * Holds the type of supported items.
	 *
	 * @since 0.1
	 * @var string
	 */
	protected $item_type = 'Rila\\Post_Type';

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
			$args = array(
				'post_type'      => 'any',
				'post__in'       => $this->ids,
				'posts_per_page' => -1,
				'order'          => 'ASC',
				'orderby'        => 'post__in'
			);
		} elseif( ! is_null( $this->args ) ) {
			$args = $this->args;
		}

		if( isset( $args[ 'meta_query' ] ) ) {
			$args[ 'meta_query' ] = array_merge( $args[ 'meta_query' ], $this->meta_query );
		} else {
			$args[ 'meta_query' ] = $this->meta_query;
		}

		$this->items = array_map( 'rila_post', get_posts( $args ) );
		$this->initialized = true;
	}

	/**
	 * Returns all items of the type.
	 *
	 * @since 0.1
	 * @return Posts;
	 */
	public static function all() {
		return new Posts(array(
			'posts_per_page' => -1
		));
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
			$dummy = new \WP_Post( new \stdClass() );
		}

		if( property_exists( $dummy, $key ) ) {
			$this->args[ $key ] = $value;
		} else {
			$this->meta_query[] = compact( 'key', 'value' );
		}
	}
}
