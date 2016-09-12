<?php
namespace Rila;

/**
 * Handles WP query in the nice way.
 */
class Query implements \Iterator, \Countable {
	use Query_Args;
	
	/**
	 * Holds the actual, prepared arguments for WP_Query/get_posts.
	 *
	 * @since 0.1
	 * @var mixed[]
	 */
	public $args = array();

	/**
	 * Holds the query for the posts.
	 *
	 * @var WP_Query
	 */
	public $query;

	/**
	 * Holds the current post index.
	 *
	 * @var int
	 */
	protected $position = 0;

	/**
	 * Holds the current post.
	 *
	 * @var Templater\Post
	 */
	protected $post;

	/**
	 * Holds the index of the next page.
	 *
	 * @var int
	 */
	public $next = 0;

	/**
	 * Holds the pagination once generated.
	 *
	 * @since 0.1
	 * @var Pagination
	 */
	protected $pagination_obj;

	/**
	 * Saves the initial parameters for a query.
	 *
	 * @param mixed[] $args The additional arguments for the query.
	 */
	public function __construct( $args = array() ) {
		if( is_a( $args, 'WP_Query' ) ) {
			$this->query = $args;
		} else {
			# Check if the args are all ids
			$ids = array();
			$args = wp_parse_args( $args );

			$i=0;
			foreach( $args as $k => $arg ) {
				if( $i == $k && is_scalar( $arg ) && $id = intval( $arg ) ) {
					$ids[] = $id;
				}

				$i++;
			}

			if( count( $args ) && count( $ids ) == count( $args ) ) {
				# We have an array of IDs
				$args = array(
					'post_type'      => 'any',
					'posts_per_page' => -1,
					'order'          => 'ASC',
					'orderby'        => 'post__in',
					'post__in'       => $ids
				);
			} else {
				$args = wp_parse_args( $args, array(
					'posts_per_page' => -1,
					'post_type'      => 'any'
				));
			}

			if( ! isset( $args[ 'paged' ] ) ) {
				$args[ 'paged' ] = 1;
			}

			$this->args = $args;
		}
	}

	/**
	 * Initializes the internal WP_Query.
	 *
	 * @return WP_Query
	 */
	protected function query() {
		if( is_null( $this->query ) ) {
			$args = $this->args;

			$this->query = new \WP_Query( $args );

			# Check the next page
			if( isset( $args[ 'posts_per_page' ] ) && -1 != $args[ 'posts_per_page' ] ) {
				if( $this->query->max_num_pages > $args[ 'paged' ] ) {
					$this->next = 1 + $this->args[ 'paged' ];
				}
			}
		}

		return $this->query;
	}

	/**
	 * Handles the retrival of unknown properties.
	 *
	 * @since 0.1
	 *
	 * @param string $property The needed property.
	 * @return mixed
	 */
	public function __get( $property ) {
		$this->query();

		if( 'count' == $property ) {
			return $this->query->found_posts;
		} elseif( 'pagination' == $property ) {
			return $this->pagination();
		}

		throw new Undefined_Property_Exception( "Undefined property $property" );
	}

	/**
	 * Handles isset calls.
	 *
	 * @since 0.1
	 *
	 * @param string $property The property to be checked.
	 * @return bool
	 */
 	public function __isset( $property ) {
 		try {
 			return null !== $this->__get( $property );
 		} catch( Undefined_Property_Exception $e ) {
 			return false;
 		}
 	}

	/**
	 * Rewinds the array to the beginning.
	 */
    function rewind() {
    	$this->query();

		$this->query->current_post = 0;
    }

    /**
     * Returns the current value of the iterator.
     *
     * @return Templater\Post
     */
    function current() {
    	$this->query();

		$p = $this->query->posts[ $this->query->current_post ];
		return rila_post( $p );
    }

    /**
     * Returns the current key/index.
     *
     * @return int
     */
    function key() {
    	$this->query();

		return $this->query->current_post;
    }

    /**
     * Goes to the next post.
     */
    function next() {
    	$this->query();

		++$this->query->current_post;
    }

    /**
     * Checks if there is anything at the current pointer.
     *
     * @return bool
     */
    function valid() {
    	return isset( $this->query->posts[ $this->query->current_post ] );
    }

	/**
	 * Display pagination.
	 */
	public function pagination() {
		if( is_null( $this->pagination_obj ) ) {
			$this->pagination_obj = new Pagination( $this );
		}

		return $this->pagination_obj;
	}

    /**
     * Sets a specific posts-per-page count.
     * Automatically loads the page number from the global paged query var.
     *
     * @param int $per_page     The count of posts per page.
     * @param int $current_page The current page, can be loaded automatically.
     * @return Query
     */
    public function paginate( $per_page = 10, $current_page = null ) {
    	$this->args[ 'posts_per_page' ] = $per_page;

    	if( is_null( $current_page ) ) {
    		$current_page = max( 1, intval( get_query_var( 'paged' ) ) );
    	}

    	$this->args[ 'paged' ] = $current_page;

    	return $this;
    }

	/**
	 * Returns the amount of posts in the query.
	 *
	 * @since 0.1
	 *
	 * @return int
	 */
	public function count() {
		$this->query();

		return count( $this->query->posts );
	}

	/**
	 * Returns an array of simple properties for var_dump()
	 *
	 * @since 0.1
	 *
	 * @return mixed[]
	 */
	public function __debugInfo() {
		return array(
			'args'  => $this->args,
			'query' => $this->query
		);
	}
}
