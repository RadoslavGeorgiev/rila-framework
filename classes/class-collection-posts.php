<?php
namespace Rila\Collection;

use Rila\Collection;
use Rila\Query_Args;

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
	 * @since 1.0
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
}