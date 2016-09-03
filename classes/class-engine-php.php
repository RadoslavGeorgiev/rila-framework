<?php
namespace Rila\Engine;

use Rila\Engine;

/**
 * Allows pure PHP to be used as a templating engine.
 * 
 * @since 0.1
 */
class PHP extends Engine {
	/**
	 * Initializes the engine.
	 * 
	 * @since 0.1
	 */
	protected function initialize() {
		# This is PHP it's already initialized :)
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
		$file_path = $this->directory . $template;

		extract( $context, EXTR_SKIP );

		ob_start();
		include( $file_path );
		return ob_get_clean();
	}
}