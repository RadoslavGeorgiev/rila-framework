<?php
namespace Rila;

use Rila\Item;

/**
 * Handles the website object.
 *
 * @since 0.1
 */
class Site extends Item {
	/**
	 * Holds all properties that should be linked to bloginfo().
	 *
	 * @var string[]
	 */
	protected $bloginfo_args = array( 'name', 'description', 'wpurl', 'url', 'admin_email', 'charset', 'version', 'html_type', 'pre_option_html_type', 'text_direction', 'language', 'stylesheet_url', 'stylesheet_directory', 'template_url', 'template_directory', 'pingback_url', 'atom_url', 'rdf_url', 'rss_url', 'rss2_url', 'comments_atom_url', 'comments_rss2_url' );

	/**
	 * Returns a new instance of the class.
	 *
	 * @return Site
	 */
	public static function instance() {
		static $instance;

		if( ! is_null( $instance ) ) {
			return $instance;
		}

		/**
		 * Allows the class that handles the site to be modified.
		 *
		 * @since 0.1
		 *
		 * @param string $class_name The class name that will handle the site.
		 * @return string Either the original or new class name.
		 */
		$class_name = apply_filters( 'rila.site_class', get_class() );

		return $instance = new $class_name();
	}

	/**
	 * Initializes the needed values.
	 */
	protected function __construct() {
		$this->initialize();

		$this->item = new \stdClass();

		# Allow the ACF or other extensions to load meta.
		$this->setup_meta( array() );

		$this->translate(array(
			'template' => 'template_url',
			'home'     => 'page_on_front',
			'blog'     => 'page_for_posts',
			'title'    => 'name'
		));

		$this->map(array(
			'page_on_front'  => 'post',
			'page_for_posts' => 'post'
		));

		/**
		 * Allows additional properties to be mapped or translated.
		 *
		 * @since 0.1
		 *
		 * @param Rila\Site $site The website to modify.
		 */
		do_action( 'rila.setup_site', $this );
	}

	/**
	 * Handles all additionally needed methods.
	 */
	protected function get( $property ) {
		if( in_array( $property, $this->bloginfo_args ) ) {
			return get_bloginfo( $property );
		} else {
			return get_option( $property );
		}
	}

	/**
	 * Handles the uploads URL.
	 *
	 * @return string
	 */
	protected function uploads_url() {
		$wp_upload_dir = wp_upload_dir();
		return trailingslashit( $wp_upload_dir[ 'baseurl' ] );
	}

	/**
	 * Handles the uploads directory.
	 *
	 * @return string
	 */
	protected function uploads_dir() {
		$wp_upload_dir = wp_upload_dir();
		return trailingslashit( $wp_upload_dir[ 'basedir' ] );
	}

	/**
	 * Renders a menu.
	 */
	public function menu( $request ) {
		$data = rila_parse_args( $request );

		$args = array_merge( array(
			'theme_location' => $data[ 'main' ],
			'fallback_cb'    => ''
		), $data[ 'args' ] );

		/**
		 * Allows the argumentws for a menu to be modified.
		 *
		 * @since 0.1
		 *
		 * @param mixed[] $args The arguments for wp_nav_menu()
		 * @return mixed[]
		 */
		$args = apply_filters( 'rila.menu', $args );

		wp_nav_menu( $args );
	}

	/**
	 * Handles an action.
	 *
	 * @param string $name THe name of the action
	 */
	public function action( $name ) {
		do_action( $name );
	}

	/**
	 * Returns a site-wide option.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function option( $key ) {
		$value = get_option( $key );

		if( $value ) {
			return $value;
		} else {
			return get_option( 'options_' . $key );
		}
	}

	/**
	 * Handles the language attributes.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function language_attributes() {
		language_attributes();

		return '';
	}

	/**
	 * Calls the wp_head function.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function head() {
		wp_head();

		return '';
	}

	/**
	 * Calls the wp_footer function.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function footer() {
		wp_footer();

		return '';
	}

	/**
	 * Checks if the current page is something.
	 *
	 * @since 0.1
	 *
	 * @param string $page_type The page type.
	 * @return bool
	 */
	public function is( $page_type )  {
		$func_name = 'is_' . $page_type;

		if( function_exists( $func_name ) ) {
			$args = func_get_args();
			array_shift( $args );

			return call_user_func_array( $func_name, $args );
		} else {
			return false;
		}
	}
}