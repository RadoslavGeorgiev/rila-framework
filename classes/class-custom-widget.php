<?php
namespace Rila;

/**
 * Allows the creation of custom widgets within themes.
 *
 * @since 0.1
 */
abstract class Custom_Widget extends \WP_Widget {
	/**
	 * Holds the title of the widget.
	 *
	 * @var string
	 */
	protected $title;

	/**
	 * Holds the description of the widget.
	 *
	 * @var string
	 */
	protected $description = '';

	/**
	 * Holds the CSS classes of the widget.
	 *
	 * @var string[]
	 */
	protected $css_class = array();

	/**
	 * Holds fields, which would be registered along the widget.
	 *
	 * @since 0.1
	 *
	 * @var mixed[]
	 */
	protected $fields = array();

	/**
	 * Holds the backend width of the widget.
	 *
	 * @var int
	 */
	protected $width;

	/**
	 * Holds all needed mapping data.
	 *
	 * @since 0.1
	 * @var mixed[]
	 */
	protected $map = array();

	/**
	 * Sets up the widgets name etc.
	 *
	 * @since 0.1
	 */
	public function __construct() {
		if( ! method_exists( $this, 'initialize' ) ) {
			wp_die( 'Custom widgets should have an initialize method!' );
		}

		# Generate title and ID automatically
		$simplified = rila_cleanup_class( get_class( $this ), 'Widget' );
		$this->id = strtolower( $simplified );
		$this->title = ucwords( str_replace( '_', ' ', $this->id ) );

		$this->initialize();

		$widget_ops = array(
			'classname'   => implode( ' ' , $this->css_class ),
			'description' => $this->description
		);

		$control_ops = array(
			'width' => $this->width
		);

		# Add fields
		if( ! empty( $this->fields ) && class_exists( 'ACF_Group' ) && function_exists( 'acf_add_local_field_group' ) ) {
			\ACF_Group::create( $this->id, $this->title )
				->add_location_rule( 'widget', $this->id )
				->add_fields( $this->fields )
				->register();
		}

		parent::__construct( $this->id, $this->title, $widget_ops, $control_ops );
	}

	/**
	 * Initializes the class.
	 *
	 * This function receives no arguments, but handles IDs, titles and etc.
	 * Use $this->title = 'Custom Widget' and etc. to modify the widget.
	 *
	 * @since 0.1
	 */
	abstract protected function initialize();

	/**
	 * Maps a certain value to a certain function.
	 *
	 * @see Rila\Item::map();
	 * @since 0.1
	 *
	 * @param string[] $values Either the key (from) or an from=>to hash.
	 * @param string   $to     The function/class to map to (for singular values).
	 */
	public function map( $values, $to = null ) {
		if( $to ) {
			$values = array( $values => $to );
		}

		$this->map = array_merge( $this->map, $values );
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		$widget = new Widget( $this, $args, $instance );
		$widget->map( $this->map );


		echo $args[ 'before_widget' ];
			$r = $this->render( $widget );

			if( $r && is_object( $r ) ) {
				echo $r;
			}
		echo $args[ 'after_widget' ];
	}

	/**
	 * Renders the widget.
	 * Receives a Timber widget as an argument, which is used as data source.
	 *
	 * @since 0.1
	 *
	 * @param Rila\Widget $widget The widget to render.
	 */
	abstract protected function render( $widget );

	/**
	 * Add a form function.
	 *
	 * This is just meant to fool ACF that there is a form, so we have a save button.
	 *
	 * @since 0.1
	 *
	 * @param mixed[] $data The data about the widget.
	 */
	public function form( $data ) {
		// The night is dark and full of terror :o
		echo ' ';
	}
}
