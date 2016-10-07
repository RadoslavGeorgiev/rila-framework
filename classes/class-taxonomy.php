<?php
namespace Rila;

use Rila\Item;
use Rila\Collection\Posts;
use Rila\Collection\Terms;
use Rila\Missing_Object_Exception;

/**
 * Encapsulates the WP_Term class in order to provide
 * smart and template-friendly functionality.
 *
 * @since 0.1
 */
class Taxonomy extends Item {
	/**
	 * Constructs the item by receiving post data.
	 *
	 * @since 0.1
	 *
	 * @param WP_Term $term The post that we're working with.
	 */
	function __construct( \WP_Term $term ) {
		$this->item = $term;
		$this->setup_meta( get_term_meta( $this->item->ID ) );

		# After all the rest is done, use individual initializers
		$this->initialize();
	}

	/**
	 * Creates a new instance of the class.
	 *
	 * @param WP_Term|Base $term A term object or just null.
	 * @return Base
	 */
	public static function factory( $term = null ) {
		if( is_a( $term, 'Rila\\Taxonomy' ) ) {
			return $term;
		}

		if( ( is_int( $term ) || is_string( $term ) ) && $id = intval( $term ) ) {
			$term = get_term( $id );
		}

		if( ! is_a( $term, 'WP_Term' ) ) {
			throw new Missing_Object_Exception( 'Taxonomy could not find a term.' );
		}

		$taxonomy = str_replace( 'dw-', '', $term->taxonomy );
		if( in_array( $taxonomy, self::$registered ) ) {
			$class_name = array_search( $taxonomy, self::$registered );
		} else {
			$class_name = 'Rila\\Taxonomy\\' . ucwords( $taxonomy );
		}

		/**
		 * Allows the class name for a term object to be modified before being used.
		 *
		 * @since 0.1
		 *
		 * @param string  $class_name The class name for the current term.
		 * @param WP_Term $term       The term whose class will be replaced.
		 * @return string
		 */
		$class_name = apply_filters( 'rila.term_class', $class_name, $term );

		if( class_exists( $class_name ) ) {
			return new $class_name( $term );
		} else {
			return new self( $term );
		}
	}

	/**
	 * Handles type-specific actions, like translations, etc.
	 *
	 * @since 0.1
	 */
	protected function initialize() {
		parent::initialize();
		
		$this->translate(array(
			'id'    => 'term_id',
			'title' => 'name'
		));

		$this->map(array(
			'parent' => 'Rila\\Taxonomy::factory'
		));
	}

	/**
	 * Returns the posts for the category.
	 *
	 * @return Templater\Query
	 */
	public function posts() {
        return new Posts(array(
            'post_type' => 'any',
            'tax_query' => array(
                array(
                    'taxonomy' => $this->item->taxonomy,
                    'terms'    => $this->item->term_id
                )
            )
        ));
	}

	/**
	 * Returns a link to the term.
	 *
	 * @return string
	 */
	public function url() {
		switch( $this->item->taxonomy ) {
			case 'category':
				return get_category_link( $this->item );

			case 'post_tag':
				return get_tag_link( $this->item );

			default:
				return get_term_link( $this->item, $this->item->taxonomy );
		}
	}

	/**
	 * Converts a term to a string.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function __toString() {
		return sprintf(
			'<a href="%s">%s</a>',
			$this->url,
			$this->title
		);
	}

	/**
	 * Automates the process of registering a public taxonomy.
	 *
	 * @link https://codex.wordpress.org/Function_Reference/register_taxonomy
	 *
	 * @since 0.1
	 *
	 * @param mixed   $post_type The post type(s) to associate the taxonomy with.
	 * @param mixed[] $args      The same arguments, that would work for register_taxonomy()
	 */
	protected static function register_taxonomy( $post_type, $args = array() ) {
		$caller = get_called_class();

		# Remove the namespace
		$basic_name = preg_replace( '~^.*\\\\([^\\\\/]+)$~', '$1', $caller );

		# Adjust the slug
		$slug = strtolower( str_replace( '_', '-', $basic_name ) );

		# Prepare the basic labels
		$singular = str_replace( '_', ' ', $basic_name );
		$plural   = preg_match( '~y$~i', $singular )
			? preg_replace( '~y$~i', 'ies', $singular )
			: $singular . 's';

		if( isset( $args[ 'singular' ] ) ) {
			$singular = $args[ 'singular' ];
		}

		if( isset( $args[ 'plural' ] ) ) {
			$singular = $args[ 'plural' ];
		}

		if( isset( $args[ 'slug' ] ) ) {
			$slug = $args[ 'slug' ];
		} else {
			$args[ 'slug' ] = $slug;
		}

		$labels = array(
			'name'              => $plural,
			'singular_name'     => $singular,
			'search_items'      => "Search $plural",
			'all_items'         => "All $plural",
			'parent_item'       => "Parent $singular",
			'parent_item_colon' => "Parent $singular:",
			'edit_item'         => "Edit $singular",
			'update_item'       => "Update $singular",
			'add_new_item'      => "Add New $singular",
			'new_item_name'     => "New $singular Name",
			'menu_name'         => $singular
		);

		$basic = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => $slug ),
		);

		$args = array_merge_recursive( $basic, $args );

		# Save the arguments
		self::$registered[ $caller ] = $slug;

		# Prepare the post types
		$object_types = array();
		foreach( (array) $post_type as $pt ) {
			if( isset( self::$registered[ $pt ] ) ) {
				$pt = self::$registered[ $pt ];
			}

			$object_types[] = $pt;
		}

		register_taxonomy( $slug, $object_types, $args );
	}

	/**
	 * Creates a new group of fields and directly associates it with the taxonomy.
	 *
	 * @since 0.1
	 * @link https://github.com/RadoslavGeorgiev/acf-code-helper
	 *
	 * @param string  $title   The title of the metabox.
	 * @param mixed[] $fields The fields to add in the metabox.
	 * @return ACF_Group The created ACF_Group, which can be modified further.
	 */
	protected static function add_fields( $title, $fields ) {
		$caller = get_called_class();
		$slug   = self::get_registered_slug( $caller );
		$id     = self::unique_id( $slug );

		$location = new \ACF_Group_Location();
		$location->add_rule( 'taxonomy', $slug );

		return self::_add_fields( $id, $title, $location, $fields );
	}

	/**
	 * Returns all terms from the taxonomy.
	 *
	 * @since 0.1
	 *
	 * @return Taxonomy[]
	 */
	public static function all() {
		return new Terms( array( 'taxonomy' => 'event-category' ) );
	}
}
