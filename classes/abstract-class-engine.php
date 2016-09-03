<?php
namespace Rila;

/**
 * This class works as a base for template engines.
 * 
 * @since 0.1
 */
abstract class Engine {
	/**
	 * Holds the folder, which contains views.
	 * 
	 * @since 0.1
	 * @var string
	 */
	protected $directory;

	/**
	 * Holds the directory, where templates should be cached.
	 * 
	 * @since 0.1
	 * @var string
	 */
	protected $cache;

	/**
	 * Initializes the engine.
	 * 
	 * @since 0.1
	 * 
	 * @param string $views_dir The directory with views.
	 * @param string $cache_dir The directory for cache (Optional)
	 */
	public function __construct( $views_dir, $cache_dir = '' ) {
		$subdir = str_replace( 'Rila\\Engine\\', '', get_class( $this ) );
		$subdir = strtolower( $subdir );
		$subdir = str_replace( '_', '-', $subdir );

		$this->directory = $views_dir;
		$this->cache     = $cache_dir . '/' . $subdir;

		if( ! file_exists( $this->cache ) ) {
			mkdir( $this->cache, 0777, true );
		}

		$this->initialize();
	}

	/**
	 * Initializes the templating engine.
	 * 
	 * @since 0.1
	 */
	abstract protected function initialize();

	/**
	 * Renders the actual template.
	 * 
	 * @since 0.1
	 * 
	 * @param string  $template The template to load.
	 * @param mixed[] $context  The context for the template.
	 * @return string The generated template
	 */
	abstract public function render( $template, $context = array() );
}