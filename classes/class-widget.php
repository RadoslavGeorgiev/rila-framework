<?php
namespace Rila;

use Rila\Item;

/**
 * Encapsulates the WP_Widget class in order to provide
 * smart and template-friendly functionality.
 *
 * @since 0.1
 */
class Widget extends Item {
	/**
	 * Holds the widget args for the instance.
	 *
	 * @var mixed[]
	 */
	public $sidebar;

	/**
	 * Saves the displayed widget.
	 * 
	 * @var Rila\Custom_Widget
	 */
	public $wp_widget;

	/**
	 * Constructs the item by receiving widget data.
	 *
	 * @since 0.1
	 *
	 * @param WP_Comment $comment The comment that we're working with.
	 */
	function __construct( \WP_Widget $widget, $args, $instance ) {
		$this->item      = new \stdClass(); // Fool Item that we have an actual item.
		$this->wp_widget = $widget;
		$this->sidebar   = $args;

		$this->setup_meta( $instance );

		$this->initialize();
	}

	/**
	 * Handles type-specific actions, like translations, etc.
	 *
	 * @since 0.1
	 */
	protected function initialize() {
		// $this->translate(array());
		// $this->map(array());
	}

	/**
	 * Handles additionall getters, for posts, just taxonomies.
	 *
	 * @since 0.1
	 *
	 * @param string $property The name of the property.
	 * @return mixed[]
	 */
	protected function get( $property ) {
		if( isset( $returnable ) ) {
			return $returnable;
		}
	}
}
