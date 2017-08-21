<?php
namespace Rila\Collection;

use Rila\Collection;
use Rila\Query_Args;
use Rila\Query;

/**
 * Handles collections of posts.
 *
 * @since 0.1
 */
class Posts extends Collection {
	use Query_args;

	/**
	 * Holds the type of supported items.
	 *
	 * @since 0.1
	 * @var string
	 */
	protected $item_type = 'Rila\\Post_Type';

	/**
	 * Ensures that the internal arguments are saved.
	 *
	 * @since 0.1
	 */
	protected function initialize() {
		$args = array(
			'posts_per_page' => -1,
			'paged'          => 1
		);

		if( ! is_null( $this->ids ) ) {
			$id_args = array(
				'post_type'      => 'any',
				'post__in'       => $this->ids,
				'posts_per_page' => -1,
				'order'          => 'ASC',
				'orderby'        => 'post__in'
			);

			$args = array_merge( $args, $id_args );
		} elseif( ! is_null( $this->args ) ) {
			$args = array_merge( $args, $this->args );
		}

		$this->args = $args;
	}

	/**
	 * Loads data from the database.
	 *
	 * @since 0.1
	 */
	protected function load() {
		$this->items = array_map( 'rila_post', get_posts( $this->args ) );
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
	 * Ensures that arguments for get_posts() cannot be changed once posts are retrieved.
	 *
	 * @since 0.1
	 *
	 * @return bool
	 */
	protected function allow_augumentation() {
		if( $this->initialized ) {
			throw new \Exception( "Once a collection is initialized with data, it cannot be filtered from the database. Use ->filter() instead!" );
			return false;
		}

		return true;
	}

	/**
	 * Generates a new query, which uses the same arguments as the collection.
	 *
	 * @since 0.3
	 *
	 * @return Query
	 */
	public function get_query() {
		return new Query( $this->args );
	}
}
