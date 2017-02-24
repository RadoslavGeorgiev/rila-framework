<?php
namespace Rila;

/**
 * Handles iterateable and mappable values.
 *
 * @since 0.1
 */
class Meta implements \Iterator, \Countable {
	/**
	 * Holds the data we're working with.
	 *
	 * @since 2.0
	 * @var mixed[]
	 */
	protected $data;

	/**
	 * Holds the mapping for the current meta.
	 *
	 * @since 2.0
	 * @var string[]
	 */
	protected $map;

	/**
	 * Holds the current index within the data array.
	 *
	 * @since 2.0
	 * @var int
	 */
	protected $pointer = 0;

	/**
	 * Holds the cache of processed items.
	 *
	 * @since 2.0
	 * @var mixed
	 */
	protected $cache = array();

	/**
	 * Handles the data within the meta loop.
	 *
	 * @param mixed[] $data The data that is going to be iterated.
	 * @param string[] $map The mapping types needed.
	 */
	public function __construct( $data, $map ) {
		$this->data = $data;
		$this->map  = $map;
	}

	/**
	 * Rewinds the array to the beginning.
	 */
    function rewind() {
    	$this->pointer = 0;
    }

    /**
     * Returns the current value of the iterator.
     *
     * @return Templater\Post
     */
    function current() {
    	if( isset( $this->cache[ $this->pointer ] ) ) {
    		return $this->cache[ $this->pointer ];
    	}

		$map = $this->map;

    	# Check the row and types
    	$row = $this->data[ $this->pointer ];

    	if( isset( $row[ '__type' ] ) ) {
    		$type = $row[ '__type' ];

    		if( isset( $map[ $type ] ) && is_array( $map[ $type ] ) ) {
	    		$map = $map[ $type ];
    		}
    	}

    	$data = array();
    	foreach( $row as $key => $value ) {
    		if( isset( $map[ $key ] ) ) {
    			$data[ $key ] = self::map( $value, $key, $map );
    		} else {
    			$data[ $key ] = $value;
    		}
    	}

    	# Save the value for later
    	$this->cache[ $this->pointer ] = $data;

    	return $data;
    }

    /**
     * Returns the current key/index.
     *
     * @return int
     */
    function key() {
    	return $this->pointer;
    }

    /**
     * Goes to the next post.
     */
    function next() {
    	$this->pointer++;
    }

    /**
     * Checks if there is anything at the current pointer.
     *
     * @return bool
     */
    function valid() {
    	return isset( $this->data[ $this->pointer ] );
    }

    /**
     * Returns all availble shortcuts
     *
     * @since 0.1
     *
     * @return string[]
     */
    public static function shortcuts() {
    	static $shortcuts;

    	if( ! is_null( $shortcuts ) ) {
    		return $shortcuts;
    	}

		$shortcuts = array(
			'date'     => 'Rila\\Date::factory',
			'post'     => 'Rila\\Post_Type::factory',
			'term'     => 'Rila\\Taxonomy::factory',
			'file'     => 'Rila\\File::factory',
			'image'    => 'Rila\\Image::factory',
			'user'     => 'Rila\\User::factory',
			'comment'  => 'Rila\\Comment::factory',
			'query'    => 'Rila\\Query',
			'posts'    => 'Rila\\Collection\\Posts',
			'files'    => 'Rila\\Collection\\Files',
			'images'   => 'Rila\\Collection\\Images',
			'terms'    => 'Rila\\Collection\\Terms',
			'users'    => 'Rila\\Collection\\Users',
			'comments' => 'Rila\\Collection\\Comments',
			'embed'    => 'Rila\\Embed',
			'builder'  => 'Rila\\Builder'
		);

		/**
		 * Allows additional shortcuts to be added to the mapping.
		 *
		 * @since 0.1
		 *
		 * @param string[] $shortcuts The already available shortcuts
		 * @return string[]
		 */
		$shortcuts = apply_filters( 'rila.shortcuts', $shortcuts );

		return $shortcuts;
    }

    public static function map( $value, $property, $map ) {
    	if( ! isset( $map[ $property ] ) ) {
			return $value;
		}

		$shortcuts = self::shortcuts();

		# Handle ACF repeaters separately if the map is an array (two levels)
		if( is_array( $map[ $property ] ) && ! isset( $map[ $property ][ 0 ] ) ) {
			return new Meta( $value, $map[ $property ] );
		}

		# If the map is an array, check if it's an immediate callback
		if( is_array( $map[ $property ] ) && is_callable( $map[ $property ] ) ) {
			$map[ $property ] = array( $map[ $property ] );
		}

		# Treat maps as arrays
		foreach( (array) $map[ $property ] as $map_to ) {
			$is_array = is_string( $map_to ) && preg_match( '~\[\]$~', $map_to );

			if( $is_array ) {
				$map_to = str_replace( '[]', '', $map_to );
			}

			# Wrap callbacks into an extra level to allow looping
	    	if( is_array( $map_to ) && ! is_callable( $map_to ) && is_array( $value ) ) {
				$value = new Meta( $value, $map_to );
				continue;
			}

			try {
				# Check for a direct callback
				if( is_array( $map_to ) && is_callable( $map_to ) ) {
					if( $is_array ) {
						$value = array_map( $map_to, $value );
					} else {
						$value = call_user_func( $map_to, $value );
					}

					continue;
				}

				# Use shortcuts
				if( isset( $shortcuts[ $map_to ] ) ) {
					$map_to = $shortcuts[ $map_to ];
				}

				# Try a simple class name
				if( class_exists( $map_to ) ) {
					if( $is_array ) {
						$all = array();

						foreach( $value as $item ) {
							$all[] = new $map_to( $item );
						}

						$value = $all;
						continue;
					} else {
						$value = new $map_to( $value );
						continue;
					}
				}

				# Try class methods
				if( preg_match( '~^(.+)::(.+)$~', $map_to, $matches ) ) {
					$cn = $matches[ 1 ];
					$mn = $matches[ 2 ];

					if( class_exists( $cn ) && method_exists( $cn, $mn ) ) {
						if( $is_array ) {
							$value = array_map( array( $cn, $mn ), $value );
							continue;
						} else {
							$value = call_user_func( array( $cn, $mn ), $value );
							continue;
						}
					}
				}

				# Try filters
				if( 0 === strpos( $map_to, 'filter:' ) ) {
					$filter_name = str_replace( 'filter:', '', $map_to );

					$value = apply_filters( $filter_name, $value );
					continue;
				}

				# Try normal functions
				if( function_exists( $map_to ) ) {
					if( $is_array ) {
						$value = array_map( $map_to, $value );
						continue;
					} else {
						$value = call_user_func( $map_to, $value );
						continue;
					}
				}
			} catch( Missing_Object_Exception $e ) {
				return false;
			}
		}

		return $value;
    }

    /**
     * Returns the count of the internal values.
     *
     * @since 0.11
     *
     * @return int
     */
    public function count() {
    	return count( $this->data );
    }
}
