<?php
namespace Rila;

/**
 * Handles the registration and rewrite of basic classes.
 *
 * @since 0.1
 */
class Class_Handler {
	/**
	 * Holds the class that should be handled.
	 *
	 * @since 0.1
	 * @var string
	 */
	protected $name;

	/**
	 * Creates a new isntance of the handler.
	 *
	 * @since 0.1
	 *
	 * @param string $class_name The name of the class that should be handled.
	 */
	public function __construct( $class_name ) {
		if(
			   ! is_subclass_of( $class_name, 'Rila\\Post_Type' )
			&& ! is_subclass_of( $class_name, 'Rila\\Taxonomy' )
			&& ! is_subclass_of( $class_name, 'Rila\\User' )
			&& ! is_subclass_of( $class_name, 'Rila\\Comment' )
			&& ! is_subclass_of( $class_name, 'Rila\\Site' )
		) {
			throw new Exception( "Only post types, taxonomies, users, comments and sites can be registered!" );
		}

		$this->name = $class_name;

		# Register post types/taxonomies is available.
		if( method_exists( $class_name, 'register' ) ) {
			add_action( 'init', array( $class_name, 'register' ) );
		}

		# Register fields if a method is available.
		if(
			method_exists( $class_name, 'register_fields' )
			&& function_exists( 'acf_add_local_field_group' )
		) {
			add_action( 'register_acf_groups', array( $class_name, 'register_fields' ) );
		}

		# Handle the overwriting of certain classes, as early as possible
		if( is_subclass_of( $class_name, 'Rila\\Post_Type' ) )
			add_filter( 'rila.post_class', array( $this, 'overwrite_post' ), 9, 2 );

		else if( is_subclass_of( $class_name, 'Rila\\Taxonomy' ) )
			add_filter( 'rila.term_class', array( $this, 'overwrite_term' ), 9, 2 );

		else if( is_subclass_of( $class_name, 'Rila\\User' ) )
			add_filter( 'rila.user_class', array( $this, 'overwrite_user' ), 9 );

		else if( is_subclass_of( $class_name, 'Rila\\Comment' ) )
			add_filter( 'rila.comment_class', array( $this, 'overwrite_comment' ), 9 );

		else if( is_subclass_of( $class_name, 'Rila\\Site' ) )
			add_filter( 'rila.site_class', array( $this, 'overwrite_site' ), 9 );
	}

	/**
	 * Overwrites a post.
	 *
	 * @since 0.1
	 *
	 * @param string $class_name The name of the class that would be normally used.
	 * @return string An eventually overwritten class.
	 */
	public function overwrite_post( $class_name, $post ) {
		$clean    = strtolower( rila_cleanup_class( $this->name ) );
		$possible = array( $clean, str_replace( '_', '-', $clean ) );

		$post_type = $post->post_type;
		if( defined( 'RILA_POST_TYPE_PREFIX' ) ) {
			$regex = '~^' . preg_quote( RILA_POST_TYPE_PREFIX ) . '[\-_]~';
			$post_type = preg_replace( $regex, '', $post_type );
		}

		if( in_array( $post_type, $possible ) ) {
			return $this->name;
		}

		return $class_name;
	}

	/**
	 * Overwrites a term.
	 *
	 * @since 0.1
	 *
	 * @param string $class_name The name of the class that would be normally used.
	 * @return string An eventually overwritten class.
	 */
	public function overwrite_term( $class_name, $term ) {
		$clean    = strtolower( rila_cleanup_class( $this->name ) );
		$possible = array( $clean, str_replace( '_', '-', $clean ) );

		$taxonomy = $term->taxonomy;
		if( defined( 'RILA_POST_TYPE_PREFIX' ) ) {
			$regex = '~^' . preg_quote( RILA_POST_TYPE_PREFIX ) . '[\-_]~';
			$post_type = preg_replace( $regex, '', $taxonomy );
		}

		if( in_array( $taxonomy, $possible ) ) {
			return $this->name;
		}

		return $class_name;
	}

	/**
	 * Overwrites a user.
	 *
	 * @since 0.1
	 *
	 * @param string $class_name The name of the class that would be normally used.
	 * @return string An eventually overwritten class.
	 */
	public function overwrite_user( $class_name ) {
		return $this->name;
	}

	/**
	 * Overwrites a comment.
	 *
	 * @since 0.1
	 *
	 * @param string $class_name The name of the class that would be normally used.
	 * @return string An eventually overwritten class.
	 */
	public function overwrite_comment( $class_name ) {
		return $this->name;
	}

	/**
	 * Overwrites a site.
	 *
	 * @since 0.1
	 *
	 * @param string $class_name The name of the class that would be normally used.
	 * @return string An eventually overwritten class.
	 */
	public function overwrite_site( $class_name ) {
		return $this->name;
	}
}