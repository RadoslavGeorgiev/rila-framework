<?php
namespace Rila;

use Rila\Item;
use Rila\Collection\Comments;
use Rila\Missing_Object_Exception;
use Rila\Taxonomy;

/**
 * Encapsulates the WP_Post class in order to provide
 * smart and template-friendly functionality.
 *
 * @since 0.1
 */
class Post_Type extends Item {
	/**
	 * Holds all available taxonomies, in order to provide shortcuts.
	 *
	 * @static
	 * @var string[]
	 */
	protected static $taxonomies = array(
		'singular' => array(),
		'plural'   => array()
	);

	/**
	 * Constructs the item by receiving post data.
	 *
	 * @since 0.1
	 *
	 * @param WP_Post $post The post that we're working with.
	 */
	function __construct( \WP_Post $post ) {
		$this->item = $post;
		$this->setup_meta( get_post_meta( $this->item->ID ) );

		# After all the rest is done, use individual initializers
		$this->initialize();
	}

	/**
	 * Creates a new instance of the class.
	 *
	 * @param int|WP_Post|Base|null $post The ID of the post, a post object or just null.
	 * @return Post
	 */
	public static function factory( $post = null ) {
		if( is_a( $post, 'Rila\\Post_Type' ) ) {
			return $post;
		}

		if( is_null( $post ) || ( ! is_object( $post ) && intval( $post ) ) ) {
			$post = get_post( $post );
		}

		if( ! is_a( $post, 'WP_Post' ) ) {
			throw new Missing_Object_Exception( 'Post type factory could not find a post.' );
		}

		if( 'attachment' == $post->post_type ) {
			if( 0 === strpos( $post->post_mime_type, 'image/' ) ) {
				return new Image( $post );
			} else {
				return new File( $post );
			}
		}

		if( in_array( $post->post_type, self::$registered ) ) {
			$class_name = array_search( $post->post_type, self::$registered );
		} else {
			$post_type = str_replace( 'dw-', '', $post->post_type );
			$class_name = 'Rila\\Post_Type\\' . ucwords( $post_type );
		}

		/**
		 * Allows the class name for a post type object to be modified before being used.
		 *
		 * @since 0.1
		 *
		 * @param string  $class_name The class name for the current post.
		 * @param WP_Post $post       The post whose class will be replaced.
		 * @return string
		 */
		$class_name = apply_filters( 'rila.post_class', $class_name, $post );

		if( class_exists( $class_name ) ) {
			return new $class_name( $post );
		} else {
			return new self( $post );
		}
	}

	/**
	 * Handles type-specific actions, like translations, etc.
	 *
	 * @since 0.1
	 */
	protected function initialize() {
		parent::initialize();

		$this->initialize_taxonomies();

		$this->translate(array(
			'id'        => 'ID',
			'title'     => 'post_title',
			'content'   => 'post_content',
			'date'      => 'post_date',
			'image'     => '_thumbnail_id',
			'thumbnail' => '_thumbnail_id',
			'status'    => 'post_status',
			'parent'    => 'post_parent',
			'template'  => '_wp_page_template',
			'author'    => 'post_author',
			'user'      => 'post_author',
			'type'      => 'post_type'
		));

		$this->map(array(
			'_thumbnail_id' => 'image',
			'post_date'     => 'date',
			'post_date_gmt' => 'date',
			'post_parent'   => 'post',
			'post_title'    => 'filter:the_title',
			'post_parent'   => 'post',
			'post_author'   => 'user',
			'post_content'  => 'filter:the_content'
		));
	}

	/**
	 * Checks for all taxonomies, which are applicable to post-types.
	 */
	protected function initialize_taxonomies() {
		static $done = false;

		if( $done ) {
			return;
		}

		foreach( get_taxonomies() as $taxonomy ) {
			if( defined( 'rila_post_TYPE_PREFIX' ) ) {
				$pure = str_replace( rila_post_TYPE_PREFIX, '', $taxonomy );
			} else {
				$pure = $taxonomy;
			}

			$pure = str_replace( '-', '_', $pure );

			self::$taxonomies[ 'singular' ][ $pure ] = $taxonomy;

			$plural = $pure;
			if( preg_match( '~y$~i', $plural ) ) {
				$plural = preg_replace( '~y$~i', 'ies', $plural );
			}

			self::$taxonomies[ 'plural' ][ $plural ] = $taxonomy;
		}
	}

	/**
	 * Handles additionall getters, for posts, just taxonomies.
	 *
	 * @since 0.1
	 *
	 * @param string $property The name of the property.
	 * @return mixed[]
	 */
	protected function get( $property ) {
		if( isset( self::$taxonomies[ 'singular' ][ $property ] ) ) {
			$term = $this->get_term( self::$taxonomies[ 'singular' ][ $property ] );

			if( false !== $term ) {
				$returnable = $term;
			}
		}

		if( ! isset( $returnable ) && isset( self::$taxonomies[ 'plural' ][ $property ] ) ) {
			$terms = $this->get_terms( self::$taxonomies[ 'plural' ][ $property ] );

			if( false !== $terms ) {
				$returnable = $terms;
			}
		}

		if( isset( $returnable ) ) {
			return $returnable;
		}
	}

	/**
	 * Retrieves terms.
	 *
	 * @param string $taxonomy The key of the taxonomy.
	 * @return Templater\Term or boolean
	 */
	protected function get_terms( $taxonomy ) {
		$terms = get_the_terms( $this->item->ID, $taxonomy );

		if( false == $terms || is_a( $terms, 'WP_Error' ) ) {
			return false;
		}

		return new Collection\Terms( $terms );
	}

	/**
	 * Returns a single term.
	 *
	 * @param string $taxonomy The key of the taxonomy.
	 * @return Templater\Term or boolean
	 */
	protected function get_term( $taxonomy ) {
		$terms = $this->get_terms( $taxonomy );

		if( false === $terms ) {
			return false;
		}

		return empty( $terms )
			? null
			: $terms->at( 0 );
	}

	/**
	 * Returns a link to the post.
	 *
	 * @return string
	 */
	public function url() {
		return get_permalink( $this->item->ID );
	}

	/**
	 * Shortcuts to the time of the event.
	 *
	 * @return string/
	 */
	public function time() {
		return $this->__get( 'date' )->get_time();
	}

	/**
	 * Converts the post type to a string.
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
	 * Returns the formatted content of the post.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function content() {
		if( is_singular() && $this->ID == get_queried_object_id() ) {
			# Make sure the current post is globally set up
			$GLOBALS[ 'post' ] = $this->item;
			setup_postdata( $GLOBALS[ 'post' ] );

			return get_the_content();
		} else {
			return apply_filters( 'the_content', $this->item->post_content );
		}
	}

	/**
	 * Handles post pagination.
	 *
	 * @since 0.1
	 *
	 * @param mixed[] $args Arguments for wp_link_pages().
	 * @return string
	 */
	public function pagination( $args = array() ) {
		$args[ 'echo' ] = false;

		return wp_link_pages( $args );
	}

	/**
	 * Handles the excerpt.
	 *
	 * @return string
	 */
	public function excerpt() {
		if( $this->item->post_excerpt ) {
			$text = $this->item->post_excerpt;
		} else {
			$text = $this->item->post_content;
		}

		$excerpt_length = apply_filters( 'excerpt_length', 55 );
		$excerpt_more   = apply_filters( 'excerpt_more', ' ' . '...' );
		$text           = wp_trim_words( $text, $excerpt_length, $excerpt_more );

		return apply_filters( 'get_the_excerpt', $text, $this->item );
	}

	/**
	 * Returns all comments that belong to the post.
	 *
	 * @return Comment[]
	 */
	public function comments() {
		return new Comments( 'post_id=' . $this->item->ID );
	}

	/**
	 * Handles the comments form.
	 *
	 * @since 0.1
	 *
	 * @param mixed[] $args Arguments for the comment form.
	 * @return string An empty string.
	 */
	public function comments_form( $args = array() ) {
		comment_form( $args, $this->item->ID );

		return '';
	}

	/**
	 * Returns the needed CSS classes.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function css_class( $additional = '' ) {
		return implode( ' ', get_post_class( $additional, $this->item->ID ) );
	}

	/**
	 * Automates the process of registering a public post type.
	 *
	 * @link https://codex.wordpress.org/Function_Reference/register_post_type
	 *
	 * @since 0.1
	 *
	 * @param mixed[] $args The same arguments, that would work for register_post_type()
	 */
	protected static function register_post_type( $args = array() ) {
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
			'name'               => $plural,
			'singular_name'      => $singular,
			'name'               => $plural,
			'singular_name'      => $singular,
			'menu_name'          => $plural,
			'name_admin_bar'     => $singular,
			'add_new'            => "Add New",
			'add_new_item'       => "Add New $singular",
			'new_item'           => "New $singular",
			'edit_item'          => "Edit $singular",
			'view_item'          => "View $singular",
			'all_items'          => "All $plural",
			'search_items'       => "Search $plural",
			'parent_item_colon'  => "Parent $plural:",
			'not_found'          => "No $plural found.",
			'not_found_in_trash' => "No $plural found in Trash"
		);

		$basic = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => $slug, 'with_front' => false ),
			'has_archive'        => true,
			'hierarchical'       => false,
			'supports'           => array( 'title', 'editor', 'thumbnail' )
		);

		$args = array_merge_recursive( $basic, $args );

		# Save the arguments
		self::$registered[ $caller ] = $slug;

		register_post_type( $slug, $args );
	}

	/**
	 * Creates a new group of fields and directly associates it with the post type.
	 *
	 * @since 0.1
	 * @link https://github.com/RadoslavGeorgiev/acf-code-helper
	 *
	 * @param string $title   The title of the metabox.
	 * @param mixed[] $fields The fields to add in the metabox.
	 * @return ACF_Group The created ACF_Group, which can be modified further.
	 */
	protected static function add_fields( $title, $fields ) {
		$caller = get_called_class();
		$slug   = self::get_registered_slug( $caller );
		$id     = self::unique_id( $slug );

		$location = new \ACF_Group_Location();
		$location->add_rule( 'post_type', $slug );

		return self::_add_fields( $id, $title, $location, $fields );
	}

	/**
	 * Returns an array of simple properties for var_dump() or print_r().
	 *
	 * @since 0.1
	 *
	 * @return mixed[]
	 */
	public function __debugInfo() {
		return (array) $this->item;
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
		# Check for taxonomy calls, ex. has_category( 'cat' ) and has_tax_classes( '20', '30' )
		if( 0 === stripos( $method, 'has_' ) ) {
			$taxonomy       = str_replace( 'has_', '', $method );
			$terms_to_check = array();

			# Check for a single call, ex. has_category()
			foreach( self::$taxonomies[ 'singular' ] as $short => $full ) {
				if( $short == $taxonomy ) {
					$terms_to_check = array(
						'taxonomy' => $full,
						'terms'    => array( $args[ 0 ] )
					);
				}
			}

			# Check for a plural call, ex. has_categories()
			foreach( self::$taxonomies[ 'plural' ] as $short => $full ) {
				if( $short == $taxonomy ) {
					$terms_to_check = array(
						'taxonomy' => $full,
						'terms'    => is_array( $args[ 0 ] ) ? $args[ 0 ] : $args
					);
				}
			}

			if( $terms_to_check ) {
				$terms = array();

				foreach( $terms_to_check[ 'terms' ] as $term ) {
					if( is_a( $term, Taxonomy::class ) ) {
						$term = $term->id;
					}

					$terms[] = $term;
				}

				return has_term( $terms, $terms_to_check[ 'taxonomy' ], $this->item->ID );
			}
		}

		return parent::__call( $method, $args );
	}

	/**
	 * Checks if the post is password protected.
	 *
	 * @since 0.1
	 *
	 * @return bool
	 */
	public function password_required() {
		return post_password_required( $this->item );
	}

	/**
	 * Handles the password form.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function password_form() {
		return get_the_password_form( $this->item );
	}
}
