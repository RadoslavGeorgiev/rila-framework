<?php
namespace Rila\Collection;

/**
 * Handles collections of posts.
 *
 * @since 0.1
 */
class Files extends Posts {
	/**
	 * Holds the type of supported items.
	 *
	 * @since 0.1
	 * @var string
	 */
	protected $item_type = 'Rila\\File';

	/**
	 * Loads data from the database.
	 *
	 * @since 0.1
	 */
	protected function load() {
		$args = array();

		if( ! is_null( $this->ids ) ) {
			$args = array(
				'post_type'      => 'attachment',
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

		$args[ 'post_type' ] = 'attachment';

		$this->items = array_map( 'rila_file', get_posts( $args ) );
		$this->initialized = true;
	}

	/**
	 * Returns all items of the type.
	 *
	 * @since 0.1
	 * @return Posts;
	 */
	public static function all() {
		return new Files(array(
			'posts_per_page' => -1,
			'post_type'      => 'attachment'
		));
	}
}
