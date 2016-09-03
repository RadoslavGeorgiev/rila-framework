<?php
namespace Rila\Engine;
use Rila\Engine;

/**
 * Allows Twig to be used as a template engine.
 *
 * @since 0.1
 */
class Twig extends Engine {
	/**
	 * Holds the actual engine.
	 *
	 * @since 0.1
	 * @var Twig_Environment
	 */
	protected $environment;

	/**
	 * Initializes the engine.
	 *
	 * @since 0.1
	 */
	protected function initialize() {
		$loader = new \Twig_Loader_Filesystem( $this->directory );
		$this->environment = new \Twig_Environment( $loader, array(
			'cache'       => $this->cache,
			'autoescape'  => false,
			'auto_reload' => true
		));

		# Default function proxying
		$default_functions = array(
			'wp_head'             => 'wp_head',
			'wp_footer'           => 'wp_footer',
			'language_attributes' => 'language_attributes',
			'body_class'          => 'body_class',
			'sidebar'             => array( $this, 'sidebar' ),
			'woocommerce'         => array( $this, 'woo' )
		);

		foreach( $default_functions as $shortcut => $function ) {
			$this->environment->addFunction( $shortcut, new \Twig_Function_Function( $function ) );
		}

		$this->environment->addFunction( 'svg', new \Twig_Function_Function( array( $this, 'svg' ) ) );

		# Add filters
		$filters = array(
			'html'     => 'esc_html',
			'url'      => 'esc_url',
			'attr'     => 'esc_attr',
			'e'        => 'esc_html',
			'sanitize' => 'sanitize_title',
			'p'        => 'wpautop',
			'ucwords'  => 'ucwords',
			'dump'     => array( $this, 'dump' )
		);

		foreach( $filters as $filter => $func ) {
			$filter = new \Twig_SimpleFilter( $filter, $func, array( 'is_safe' => array( 'html' ) ) );
			$this->environment->addFilter( $filter );
		}

		/**
		 * Allows the environment to be modified for templates and etc.
		 *
		 * @param Twig_Environment
		 */
		do_action( 'rila.twig.environment', $this->environment );
	}

	/**
	 * Renders the actual template.
	 *
	 * @since 0.1
	 *
	 * @param string  $template The template to load.
	 * @param mixed[] $context  The context for the template.
	 */
	public function render( $template, $context = array() ) {
		return $this->environment->render( $template, $context );
	}

	/**
	 * Renders a sidebar.
	 *
	 * @since 0.1
	 *
	 * @param string $name The name of the sidebar.
	 */
	public function sidebar( $id ) {
		dynamic_sidebar( $id );
	}

	/**
	 * Locates an SVG and includes it.
	 * 
	 * @since 0.1
	 *
	 * @param string $name The name of the SVG
	 * @return mixed
	 */
	public function svg( $name ) {
		$path = DW_THEME_DIR . 'res/img/icons/' . $name .'.svg';

		if( file_exists( $path ) ) {
			echo file_get_contents( $path );
		}
	}

	/**
	 * Dumps an item.
	 * 
	 * @since 0.1
	 * 
	 * @param mixed $item THe item that is to be dumpled.
	 */
	public function dump( $item ) {
		echo "\n";
		var_dump($item);
		exit;
	}

	/**
	 * Displays WooCommerce's content.
	 * 
	 * @since 0.1
	 * 
	 * @return string An empty displayable string.
	 */
	public function woo() {
		woocommerce_content();

		return '';
	}
}
