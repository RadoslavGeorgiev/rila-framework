<?php
namespace Rila;

/**
 * Wraps the needed data about the active theme.
 */
class Theme {
	/**
	 * Holds the theme we're working with.
	 *
	 * @since 0.1
	 *
	 * @var WP_Theme
	 */
	protected $theme;

	/**
	 * Creates an instance of the class.
	 *
	 * @since 0.1
	 *
	 * @return Theme
	 */
	public static function instance() {
		static $instance;

		if( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Initializes the class.
	 *
	 * @since 0.1
	 */
	public function __construct( $theme = null ) {
		$this->theme = wp_get_theme( $theme );
	}

	/**
	 * Handles calls.
	 *
	 * @since 0.1
	 */
	public function __get( $property ) {
		if( method_exists( $this, $property ) ) {
			return $this->$property();
		}

		if( method_exists( $this->theme, $property ) ) {
			return $this->theme->$property();
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
	 * Returns the URL of the current theme.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function url() {
		return trailingslashit( get_stylesheet_directory_uri() );
	}
}
