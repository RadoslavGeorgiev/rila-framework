<?php
namespace Rila;

/**
 * Wraps the request to allow nice and safe access to _GET and _POST properties.
 */
class Request {
	/**
	 * Holds the needed request type.
	 *
	 * @since 2.0
	 * @var string
	 */
	protected $request_type = 'any';

	/**
	 * Creates an instance of the class.
	 *
	 * @since 0.1
	 *
	 * @return Request
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
	protected function __construct() {

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

		# Return a value from the general Request
		switch( $this->request_type ) {
			case 'post':
				if( isset( $_POST[ $property ] ) ) {
					return esc_html( stripslashes( $_POST[ $property ] ) );
				}
				break;

			case 'get':
				if( isset( $_GET[ $property ] ) ) {
					return esc_html( stripslashes( $_GET[ $property ] ) );
				}
				break;

			default:
				if( isset( $_REQUEST[ $property ] ) ) {
					return esc_html( stripslashes( $_REQUEST[ $property ] ) );
				}
				break;
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
	 * Returns the type of the current request.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function type() {
		return 'POST' == $_SERVER[ 'REQUEST_METHOD' ]
			? 'post'
			: 'get';
	}

	/**
	 * Switches to _POST mode.
	 *
	 * @since 0.1
	 *
	 * @return Request
	 */
	public function post() {
		$this->request_type = 'post';
		return $this;
	}

	/**
	 * Switches to _GET mode.
	 *
	 * @since 0.1
	 *
	 * @return Request
	 */
	public function get() {
		$this->request_type = 'get';
		return $this;
	}
}
