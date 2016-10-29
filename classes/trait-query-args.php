<?php
namespace Rila;

/**
 * Handles query parameter generation for Query and Collection\Posts.
 *
 * @since 0.1
 */
trait Query_Args {
	/**
	 * Indicates if arguments can be augumented at the current moment.
	 * This method would throw an exception in collections and queries if data has
	 * already been retrieved.
	 *
	 * @since 0.1
	 *
	 * @return bool
	 */
	protected function allow_augumentation() {
		return true;
	}

	/**
     * Sets an argument to the query.
     *
     * @param string $key   The key for the argument.
     * @param mixed  $value The new value.
     * @param bool   $merge Wether to merge arrays.
     * @return Query The query.
     */
    public function set( $key, $value, $merge = false ) {
		if( ! $this->allow_augumentation() ) {
			return false;
		}

		# Some properties expect arrays. Make sure to format values
		if( in_array( $key, array( 'post__in', 'post__not_in', 'author__in' ) ) ) {
			$value = (array) $value;
		}

    	if( $merge && is_array( $value ) ) {
    		$current = isset( $this->args[ $key ] ) ? $this->args[ $key ] : array();
    		$current = array_merge( $current, $value );
    		$this->args[ $key ] = $current;
    	} else {
	    	$this->args[ $key ] = $value;
    	}

    	return $this;
    }

    /**
     * Sets the order to alphabetical.
     *
     * @return Query The query.
     */
    public function alphabetical() {
		$this->set( 'order', 'ASC' );
		$this->set( 'orderby', 'post_title' );

		return $this;
    }

    /**
     * Sets the order newest first.
     *
     * @return Query The query.
     */
    public function newest() {
		$this->set( 'order', 'DESC' );
		$this->set( 'orderby', 'post_date' );

		return $this;
    }

    /**
     * Sets the order to oldest first
     *
     * @return Query The query.
     */
    public function oldest() {
		$this->set( 'order', 'ASC' );
		$this->set( 'orderby', 'post_date' );

		return $this;
    }

	/**
	 * Sets the order to ascending menu order.
	 */
	public function menu_order() {
		$this->set( 'order', 'ASC' );
		$this->set( 'orderby', 'menu_order' );

		return $this;
	}

	/**
	 * Allows jQuery-style chaining of query parameters.
	 *
	 * This function allows a multitude of parameters to be used. By default,
	 * $query->post_type( 'post' ) equals to $query->set( 'post_type', 'post' ).
	 *
	 * However, some of the methods are dynamic. An example of that are taxonomies:
	 * Using $query->category( 'news' ) will automatically detect that "category"
	 * is the slug of a taxonomy and instead of using ->set() directly, it will
	 * setup the corresponding tax_query parameter.
	 *
	 * @since 0.1
	 *
	 * @param string  $method The name of the called method.
	 * @param mixed[] $args The arguments for the function.
	 * @return        Query Returns the query so more parameters can be used.
	 */
	public function __call( $method, $args ) {
		static $taxonomies, $shortcuts;

		# Cache taxonomies and shortcuts
		if( is_null( $taxonomies ) || is_null( $shortcuts ) ) {
			$raw = get_taxonomies();

			# Support underscores only
			foreach( $raw as $taxonomy ) {
				$taxonomies[ str_replace( '-', '_', $taxonomy ) ] = $taxonomy;
			}

			# Add shortcuts
			$shortcuts = array(
				'type'    => 'post_type',
				'in'      => 'post__in',
				'include' => 'post__in',
				'exclude' => 'post__not_in',
				'status'  => 'post_status',
				'author'  => 'author__in',
				'search'  => 's'
			);

			/**
			 * Allows the parameter shortcuts to be modified.
			 *
			 * @since 0.1
			 *
			 * @param string[] $shortcuts Property shortcuts for the query.
			 * @return string[]
			 */
			$shortcuts = apply_filters( 'rila.query_args.shortcuts', $shortcuts );
		}

		# Use shortcuts if needed
		if( isset( $shortcuts[ $method ] ) ) {
			$method = $shortcuts[ $method ];
		}

		# Check for a taxonomy
		if( isset( $taxonomies[ $method ] ) ) {
			$terms = $args[ 0 ];

			$row = array(
				'taxonomy' => $taxonomies[ $method ],
				'terms'    => $terms
			);

			# Check for the appropriate field
			$int_only = true;

			foreach( (array) $terms as $term ) {
				if( ! is_int( $term ) ) {
					$int_only = false;
					break;
				}
			}

			$row[ 'field' ] = $int_only ? 'id' : 'slug';

			# Add an operator if needed
			if( isset( $args[ 1 ] ) ) {
				$row[ 'operator' ] = strtoupper( $args[ 1 ] );
			}

			# Add the row to the tax query
			if( ! isset( $this->args[ 'tax_query' ] ) ) {
				$this->args[ 'tax_query' ] = array();
			}

			$this->set( 'tax_query', array( $row ), true );

			return $this;
		}

		# Check for date parameters
		if( in_array( $method, array( 'after', 'before', 'since' ) ) ) {
			$prop = 'before' == $method ? 'before' : 'after';

			# Check for the "inclusive" arg
			$inclusive = null;

			if( count( $args ) > 1 && is_bool( $args[ count( $args ) - 1 ] ) ) {
				$inclusive = array_pop( $args );
			}

			# we will always work with the first date query by default.
			if( ! isset( $this->args[ 'date_query' ] ) ) {
				$this->args[ 'date_query' ] = array( array() );
			}

			$value = count( $args ) > 1 ? $args : $args[ 0 ];
			$date_query = isset( $this->args[ 'date_query' ] )
				? $this->args[ 'date_query' ]
				: array( array() );


			$date_query[ 0 ][ $prop ] = $value;
			if( ! is_null( $inclusive ) ) {
				$date_query[ 0 ][ 'inclusive' ] = $inclusive;
			}

			$this->set( 'date_query', $date_query );

			return $this;
		}

		# For some parameters, allow an array of arguments to be used
		$accept_many_arguments = array(
			'post__in',
			'post__not_in',
			'post_type',
			'post_status',
			'author__in'
		);

		if( in_array( $method, $accept_many_arguments ) && count( $args > 1 ) ) {
			$value = $args;
		} else {
			$value = array_shift( $args );
		}

		# Fallback to the normal "set" method.
		$this->set( $method, $value, true );

		return $this;
	}

	/**
	 * Handles meta queries.
	 *
	 * @since 0.1
	 *
	 * @param string $key The key for the meta query.
	 * @param mixed  $value The value for the meta query.
	 * @param mixed  $param1 Either the compare or type parameter for meta_query.
	 * @param mixed  $param2 Either the compare or type parameter for meta_query.
	 * @return Query
	 */
	public function meta( $key, $value, $param1 = null, $param2 = null ) {
		$row = compact( 'key', 'value' );

		$compare_params = array( '=', '!=', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN', 'EXISTS', 'NOT EXISTS' );
		$type_params    = array( 'NUMERIC', 'BINARY', 'CHAR', 'DATE', 'DATETIME', 'DECIMAL', 'SIGNED', 'TIME', 'UNSIGNED' );

		$extra = array_filter( array( $param1, $param2 ) );
		foreach( $extra as $param ) {
			$param = strtoupper( $param );

			if( in_array( $param, $compare_params ) ) {
				$row[ 'compare' ] = $param;
			}

			if( in_array( $param, $type_params ) ) {
				$row[ 'type' ] = $param;
			}
		}

		# Save the query
		$this->set( 'meta_query', array( $row ), true );

		return $this;
	}

	/**
	 * Handles the arguments for post parents.
	 *
	 * @since 0.1
	 *
	 * @param mixed $parent The needed parent.
	 */
	public function parent( $parent ) {
		if( is_string( $parent ) ) {
			if( preg_match( '~^\d+$~', $parent ) ) {
				$parent = intval( $parent );
			}

			$post = get_page_by_path( $parent, OBJECT, 'any' );
			if( is_a( $post, 'WP_Post' ) ) {
				$parent = $post->ID;
			}
		} elseif( is_a( $parent, 'WP_Post' ) || is_a( $parent, 'Rila\\Post_type' ) ) {
			$parent = $parent->ID;
		}

		if( $parent && is_int( $parent ) ) {
			$this->set( 'post_parent', $parent );
		} else {
			$this->set( 'post_parent', 0 );
		}

		return $this;
	}
}