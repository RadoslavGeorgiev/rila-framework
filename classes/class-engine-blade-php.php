<?php
namespace Rila\Engine;

use Philo\Blade\Blade;
use Rila\Engine;

/**
 * Allows Laravel Blade to be used as a template engine.
 * 
 * @since 0.1
 */
class Blade_PHP extends Engine {
	/**
	 * Holds the template engine.
	 * 
	 * @since 0.1
	 * @var Blade
	 */
	protected $blade;

	/**
	 * Initializes the engine.
	 * 
	 * @since 0.1
	 */
	protected function initialize() {
		$this->blade = new Blade( $this->directory, $this->cache );
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
		$path = str_replace( '.blade.php', '', $template );
		$view = $this->blade->view()->make( $path );
		$view->with( $context );

		return $view->render();
	}
}