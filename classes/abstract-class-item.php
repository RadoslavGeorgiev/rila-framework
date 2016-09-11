<?php
namespace Rila;

use Rila\Undefined_Property_Exception;

/**
 * Works as a base for post types and taxonomies,
 * encapsulating translations and mapping.
 *
 * @since 0.1
 */
abstract class Item {
	/**
	 * Holds the actual item (post/term) we're operating with.
	 *
	 * @param WP_Post
	 */
	public $item;

	/**
	 * Holds a dictionary for properties.
	 *
	 * Allows a shortcut to be used instead of the real property,
	 * ex. title instead of post_title, id instead of ID and etc.
	 *
	 * This will be filled with the "translate" method of each class.
	 *
	 * @var string[]
	 */
	protected $dictionary = array();

	 /**
	  * Holds a map of items, which should be mapped to a certain object type.
	  * Works both with object properties and meta values.
	  *
	  * This will be filled with the "map" method of each class.
	  *
	  * @var string[]
	  */
	protected $map = array();

	 /**
	  * Holds all cached requests.
	  *
	  * @since 2.0
	  * @var mixed[]
	  */
	protected $cache = array();

	/**
	* Keeps all meta-values accessible.
	*
	* WordPress retrieves all metadata automatically, so it all comes here.
	*
	* @since 2.0
	* @var mixed[]
	*/
	protected $meta = array();

	/**
	 * Holds the arguments for each registered post type or taxonomy.
	 *
	 * @since 0.1
	 * @var mixed[]
	 */
	protected static $registered = array();

	/**
	 * Holds the groups that need to be registered on init.
	 *
	 * @since 0.1
	 * @var ACF_Group[]
	 */
	protected static $registered_groups = array();

	/**
	 * Holds external methods that provide methods and/or properties.
	 *
	 * @since 0.1
	 * @var callable[]
	 */
	protected $external = array();

	/**
	* Initializes the item.
	*/
	protected function initialize() {
		/**
		 * Allows external methods to be added to the object.
		 *
		 * @since 0.1
		 *
		 * @param Item $item The item that can be modified.
		 */
		do_action( 'rila.item.extend', $this );
	}

	/**
	 * Allows external callbacks to be added.
	 *
	 * If the third parameter, $args, is set to 0 meaning that the method does
	 * not require any parameters, the method will be accessible as a property too.
	 *
	 * @since 0.1
	 *
	 * @param string   $name The name of the property/method the callback works with.
	 * @param callable $func The function that will handle the property.
	 * @param int      $args The amount of required arguments.
	 * @return Item
	 */
	public function add_external_method( $name, $callable, $args = 0 ) {
		$this->external[ $name ] = array( $callable, $args );
	}

	/**
	* The isset call confirms if a property exists.
	*
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
	* Handles the retrival of unknown values, which for us is most of them.
	*
	* @since 2.0
	*
	* @param string $property The name of the property.
	* @return mixed
	*/
	public function __get( $property ) {
		# Check cache first
		if( isset( $this->cache[ $property ] ) ) {
			return $this->cache[ $property ];
		}

		# Priority 0: External methods
		if( isset( $this->external[ $property ] ) ) {
			$callback = $this->external[ $property ];

			if( 0 === $callback[ 1 ] ) {
				return call_user_func( $callback[ 0 ], $this );
			}
		}

		# Priority 1: Custom methods
		if( method_exists( $this, $property ) ) {
			$returnable = $this->$property();
		}

		# Translate the property for further calls
		$original = $property;
		$property = $this->translate_property( $property );

		# Priority 2: Meta values
		if( ! isset( $returnable ) && isset( $this->meta[ $property ] ) ) {
			$returnable = $this->meta[ $property ];
		}

		# Priority 3: Class-based
		if( ! isset( $returnable ) && method_exists( $this, 'get' ) ) {
			$custom = $this->get( $property );

			if( ! is_null( $custom ) ) {
				$returnable = $custom;
			}
		}

		# Last priority: Item data (post/taxonomy)
		if( ! isset( $returnable ) && property_exists( $this->item, $property ) ) {
			$returnable = $this->item->$property;
		}

		# If there is no value, just return false
		if( ! isset( $returnable ) ) {
			return false;
		}

		/**
		 * Allows the returnable value of an item to be modified.
		 *
		 * @since 0.1
		 *
		 * @param mixed  $value    The value before being mapped to anything.
		 * @param string $property The name of the property.
		 * @param Item   $item     The item whose property is being modified.
		 * @return mixed
		 */
		$returnable = apply_filters( 'rila.property.raw', $returnable, $property, $this );

		# Maybe map
		if( $returnable ) {
			$returnable = $this->map_value( $returnable, $property );
		}

		/**
		 * Allows the already mapped/processed value of an item to be modified.
		 *
		 * @since 0.1
		 *
		 * @param mixed  $value    The value before being mapped to anything.
		 * @param string $property The name of the property.
		 * @param Item   $item     The item whose property is being modified.
		 * @return mixed
		 */
		$returnable = apply_filters( 'rila.property.mapped', $returnable, $property, $this );

		$this->cache[ $original ] = $returnable;

		return $returnable;
	}

	 /**
	  * Uses the dictionary to translate a property.
	  *
	  * @param string $property The name of the property.
	  * @return string
	  */
	protected function translate_property( $property ) {
		$class_name = get_class( $this );

 		if( isset( $this->dictionary[ $property ] ) ) {
 			return $this->dictionary[ $property ];
 		}

		return $property;
	}

	/**
	 * Adds a property to the dictionary.
	 *
	 * @since 0.1
	 *
	 * @param string[] $translations {
	 * 		@param string $request The value that can be requested.
	 * 		@param string $response The value to respond with.
	 * }
	 */
	public function translate( $translations ) {
		$this->dictionary = array_merge( $this->dictionary, $translations );
	}

	/**
	 * Parses meta values, related to the current post/item.
	 *
	 * @since 0.1
	 *
	 * @param mixed[] $meta The meta to parse.
	 */
	protected function setup_meta( $meta ) {
		/**
		 * Allows the meta values of a certain item to be parsed
		 * before trying to process them anyway, allowing ACF extensions to work.
		 *
		 * @since 0.1
		 *
		 * @param mixed[] $meta The meta before being processed.
		 * @return mixed[]|null Don't return anything to bypass.
		 */
		$processed = apply_filters( 'rila.meta', array(), $meta, $this );

		if( ! empty( $processed ) ) {
			$this->meta = $processed;
		} else {
			if( $meta ) foreach( $meta as $key => $value ) {
				# Standard values
				if( is_array( $value ) && ( 1 === count( $value ) ) && isset( $value[ 0 ] ) ) {
					$value = $value[ 0 ];
				}

				$this->meta[ $key ] = maybe_unserialize( $value );
			}
		}
	}

	/**
	 * Maps a value to a certain class/filter.
	 *
	 * @since 0.1
	 *
	 * @param mixed $value The value that is being mapped.
	 * @param string $property The name of the property mapping.
	 */
	public function map_value( $value, $property ) {
		static $map;

		if( is_null( $map ) ) {
			$map = rila_dot_to_array( $this->map );
		}

		return Meta::map( $value, $property, $map );
	}

	/**
	 * Adds mapping data to the object type.
	 *
	 * The function receives an array of (or a singular) property => type assotiations.
	 * The property should consist of a key (ex. post_title).
	 *
	 * For nested values, like arrays, the value can include the first
	 * key and the sub-value for repeaters. Types are also allowed, ex:
	 * - content_blocks.title (Handles the title property of each "block")
	 * - content_blocks.text.title (Handles the title property of the block with type 'text'.)
	 *
	 * The type value can have the following format:
	 * - 'image':                         This will map the value to a Rila\Image object.
	 * - 'date':                          This will map the value to a Rila\Date object.
	 * - 'post':                          This will map the value to the appropriate post type.
	 * - 'filter:[filter_name]':          This will call apply_filters( filter_name ) on the value.
	 * - Class name:                      If class_exists() returns true, the function will attempt to create an object.
	 * - '[class_name]::[static_method]': This will call the method with the given value.
	 *
	 * @param string[] $values Either the key (from) or an from=>to hash.
	 * @param string   $to     The function/class to map to (for singular values).
	 */
	public function map( $values, $to = null ) {
		if( $to ) {
			$values = array( $values => $to );
		}

		$this->map = array_merge( $this->map, $values );
	}

	/**
	 * Returns the sortable property of the class.
	 *
	 * @since 0.1
	 * @return mixed
	 */
	public function order() {
		return $this->title;
	}

	/**
	 * Retrieves the slug for a class name.
	 *
	 * @since 0.1
	 *
	 * @param string $class_name The class to look for.
	 * @return string
	 */
	public static function get_registered_slug( $class_name ) {
		if( isset( self::$registered[ $class_name ] ) ) {
			return self::$registered[ $class_name ];
		} else {
			$basic_name = preg_replace( '~^.*\\\\([^\\\\/]+)$~', '$1', $class_name );
			return strtolower( str_replace( '_', '-', $basic_name ) );
		}
	}

	/**
	 * Creates a new group of fields and directly associates it with the item.
	 *
	 * @since 0.1
	 * @link https://github.com/RadoslavGeorgiev/acf-code-helper
	 *
	 * @param string             $id        The ID for the ACF group.
	 * @param string             $title     The title of the metabox.
	 * @param ACF_Group_Location $location  The primary location of the group.
	 * @param mixed[]            $fields    The fields to add in the metabox.
	 * @return ACF_Group The created ACF_Group, which can be modified further.
	 */
	protected static function _add_fields( $id, $title, $location, $fields ) {
		static $hook_added;

		# Make sure that there is a hook that registers the groups after they get modified.
		if( is_null( $hook_added ) ) {
			$hook_added = true;
			add_action( 'widgets_init', array( get_class(), 'register_fields' ) );
		}

		$group = \ACF_Group::create( $id, $title )
			->add_location( $location )
			->add_fields( $fields );

		self::$registered_groups[] = $group;

		return $group;
	}

	/**
	 * Generates a unique group ID.
	 *
	 * @since 0.1
	 *
	 * @param string $id The normal ID.
	 * @return string An ID with an appended number.
	 */
	protected static function unique_id( $id ) {
		static $ids;

		if( is_null( $ids ) ) {
			$ids = array();
		}

		$existing = 0;
		foreach( $ids as $i ) {
			if( strpos( $i, $id ) === 0 ) {
				$existing++;
			}
		}

		if( $existing ) {
			$id .= '-' . ( $existing + 1 );
		}

		$ids[] = $id;

		return $id;
	}

	/**
	 * Registes field groups.
	 *
	 * @since 0.1
	 */
	public static function register_fields() {
		foreach( self::$registered_groups as $group ) {
			$group->register();
		}
	}

	/**
	 * Attempt calling a custom method.
	 *
	 * @since 0.1
	 *
	 * @param string $method The name of the method.
	 * @param mixed[] $args The arguments for the method.
	 * @return mixed
	 */
	public function __call( $method, $args ) {
		if( isset( $this->external[ $method ] ) ) {
			$callback = $this->external[ $method ];

			if( count( $args ) >= $callback[ 1 ] ) {
				array_unshift( $args, $this );

				return call_user_func_array( $callback[ 0 ], $args );
			}
		}

		trigger_error( 'Call to undefined method ' . __CLASS__ . '::' . $method . '()', E_USER_ERROR );
	}
}