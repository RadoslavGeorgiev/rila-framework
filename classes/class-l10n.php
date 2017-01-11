<?php
namespace Rila;

/**
 * Handles localisation features within the plugin.
 * 
 * @since 0.1
 */
class L10N {
	/**
	 * Holds the textdomain that will be used by default.
	 * 
	 * @since 0.1
	 * @todo Automate the domain loading
	 */
	protected $textdomain;

	/**
	 * Creates an instance of the class.
	 * 
	 * @since 0.1
	 * 
	 * @return L10N
	 */
	public static function instance() {
		static $instance;

		if( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Adds the necessary hooks.
	 * 
	 * @since 0.1
	 */
	protected function __construct() {
		add_action( 'rila.twig.environment', array( $this, 'setup_environment' ) );

		/**
		 * Allows the default texdomain to be modified.
		 * 
		 * @since 0.1
		 * @param string $textdomain A blank textdomain.
		 * @return string
		 */
		$this->textdomain = apply_filters( 'rila.default_textdomain', '' );
	}

	/**
	 * Sets up the Twig environment with the needed methods.
	 * 
	 * @since 0.1
	 * 
	 * @param Twig_Environment $env The environment to modify.
	 */
	public function setup_environment( $environment ) {
		$environment->addFunction( new \Twig_SimpleFunction( '__', array( $this, '__' ) ) );
		$environment->addFunction( new \Twig_SimpleFunction( '_e', array( $this, '_e' ) ) );
		$environment->addFunction( new \Twig_SimpleFunction( 'esc_attr_e', array( $this, 'esc_attr_e' ) ) );
		$environment->addFunction( new \Twig_SimpleFunction( 'esc_html_e', ( $this, 'esc_html_e' ) ) );

		$environment->addFunction( new \Twig_SimpleFunction( '_x', array( $this, '_x' ) ) );
		$environment->addFunction( new \Twig_SimpleFunction( '_ex', array( $this, '_ex' ) ) );
		$environment->addFunction( new \Twig_SimpleFunction( 'esc_attr_x', array( $this, 'esc_attr_x' ) ) );
		$environment->addFunction( new \Twig_SimpleFunction( 'esc_html_x', array( $this, 'esc_html_x' ) ) );

		$environment->addFunction( new \Twig_SimpleFunction( '_n', array( $this, '_n' ) ) );
	}

	public function __( $text, $domain = '' ) {
		return __( $text, $domain ? $domain : $this->textdomain );
	}

	public function _e( $text, $domain = '' ) {
		return _e( $text, $domain ? $domain : $this->textdomain );
	}

	public function esc_attr_e( $text, $domain = '' ) {
		return esc_attr_e( $text, $domain ? $domain : $this->textdomain );
	}

	public function esc_html_e( $text, $domain = '' ) {
		return esc_html_e( $text, $domain ? $domain : $this->textdomain );
	}

	public function _x( $text, $context, $domain = '' ) {
		return _x( $text, $context, $domain ? $domain : $this->textdomain );
	}

	public function _ex( $text, $context, $domain = '' ) {
		return _ex( $text, $context, $domain ? $domain : $this->textdomain );
	}

	public function esc_attr_x( $text, $context, $domain = '' ) {
		return esc_attr_x( $text, $context, $domain ? $domain : $this->textdomain );
	}

	public function esc_html_x( $text, $context, $domain = '' ) {
		return esc_html_x( $text, $context, $domain ? $domain : $this->textdomain );
	}

	public function _n( $single, $plural, $number, $domain = '' ) {
		return _n( $single, $plural, $number, $domain ? $domain : $this->textdomain );
	}
}