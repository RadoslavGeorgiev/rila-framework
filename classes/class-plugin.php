<?php
namespace Rila;

class Plugin {
	/**
	 * Holds the path of the plugin.
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * Holds the one and only instance of the class.
	 *
	 * @var Plugin
	 */
	protected static $instance;

	/**
	 * Creates an instance of the class, basically initializing the whole thing.
	 *
	 * @param string $path The path to the main plugin file.
	 * @return Plugin
	 */
	public static function init( $path ) {
		if( ! is_null( self::$instance ) ) {
			return self::$instance;
		}

		return self::$instance = new self( $path );
	}

	/**
	 * Directly returns the instance of the class.
	 *
	 * @return Rila
	 */
	public function instance() {
		return self::$instance;
	}

	/**
	 * Initializes the plugin.
	 *
	 * @param string $path The path to the main plugin file.
	 */
	protected function __construct( $path ) {
		$this->path = dirname( $path ) . '/';
		define( 'RILA_FRAMEWORK_DIR', $this->path );

		add_action( 'plugins_loaded', array( $this, 'initialize' ), -1 );

		# Add the necesasry autoloaders
		spl_autoload_register( array( $this, 'include_class' ) );

		# Include basic files
		include_once( $this->path . 'functions.php' );

		# Add AJAX listeners
		add_action( 'template_redirect', array( $this, 'ajax' ) );
	}

	/**
	 * Initializes the plugin.
	 *
	 * @since 0.1
	 */
	public function initialize() {
		/**
		 * Allows the functionality of the plugin to be extended.
		 *
		 * @since 0.1
		 */
		do_action( 'rila.init', $this );

		# Initialize the image helper
		Image::init();

		# If ACF is active, initialize the ACF helper
		if( class_exists( 'ACF' ) ) {
			ACF_Helper::instance();
		}
	}

	/**
	 * Tries to include a class whenever needed.
	 *
	 * @param string $class_name The name of the class.
	 */
	public function include_class( $class_name ) {
		if( 0 !== strpos( $class_name, 'Rila' ) ) {
			return;
		}

		$clean = str_replace( 'Rila\\', '', $class_name );
		$clean = strtolower( $clean );
		$clean = str_replace( '\\', '-', $clean );
		$clean = str_Replace( '_', '-', $clean );

		$files = array(
			'class-' . $clean . '.php',
			'trait-' . $clean . '.php',
			'abstract-class-' . $clean . '.php'
		);

		foreach( $files as $file ) {
			$path = $this->path . 'classes/' . $file;

			if( file_exists( $path ) ) {
				include_once( $path );
			}
		}
	}

	/**
	 * Handles AJAX calls for the API.
	 *
	 * @since 0.1.1
	 */
	public function ajax() {
		if( ! isset( $_GET[ 'rila_load' ] ) ) {
			return;
		}

		$load = trim( $_GET[ 'rila_load' ] );
		$load = explode( ';', $load );
		$type = array_shift( $load );

		switch( $type ) {
			case 'terms':
				$data = array();

				$collection = new Collection\Terms( array_map( 'intval', $load ) );
				foreach( $collection as $term ) {
					$data[] = $term->export();
				}

				echo json_encode( $data );
				exit;

				break;
		}
	}
}
