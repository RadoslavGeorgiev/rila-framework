<?php
namespace Rila;

/**
 * Handles images within the website.
 */
class Image extends File {
	/**
	 * Holds the needed image size.
	 *
	 * @var string
	 */
	public $size = 'full';

	/**
	 * Caches attributes.
	 *
	 * @since 0.1
	 * @var mixed[]
	 */
	protected $attr_cache = array();

	/**
	 * Handles type-specific actions, like translations, etc.
	 *
	 * @since 0.1
	 */
	protected function initialize() {
		$this->initialize_taxonomies();

		$this->translate(array(
			'id'      => 'ID',
			'title'   => 'post_title',
			'content' => 'post_content',
			'date'    => 'post_date',
			'post'    => 'post_parent',
			'author'  => 'post_author',
			'user'    => 'post_author'
		));

		$this->map(array(
			'post_date'     => 'date',
			'post_date_gmt' => 'date',
			'post_parent'   => 'post',
			'post_title'    => 'filter:the_title',
			'post_parent'   => 'post',
			'post_author'   => 'user',
			'post_content'  => 'filter:the_content'
		));
	}

	/**
	 * Converts the image to string.
	 *
	 * @return string
	 */
	public function __toString() {
		return wp_get_attachment_image( $this->item->ID, $this->size );
	}

	/**
	 * Adds easier shortcuts for within templates.
	 *
	 * @since 0.1
	 *
	 * @param string $property The needed property.
	 * @return mixed
	 */
	public function get( $property ) {
		if( in_array( $property, self::get_image_sizes() ) ) {
			$image = clone $this;
			$image->size = $property;
			return $image;
		}

		$attributes = $this->image_attributes();
		if( isset( $attributes[ $property ] ) ) {
			return $attributes[ $property ];
		}

		return parent::get( $property );
	}

	/**
	 * Returns the source of the image.
	 *
	 * @return string
	 */
	public function get_source() {
		$src = wp_get_attachment_image_src( $this->item->ID, $this->size );
		return $src[ 0 ];
	}

	/**
	 * Initializes the class.
	 */
	public static function init() {
		add_action( 'rila.twig.environment', array( 'Rila\Image', 'add_twig_filters' ) );
	}

	/**
	 * Returns all available image sizes.
	 *
	 * @since 0.1
	 * @return string
	 */
	public static function get_image_sizes() {
		$sizes   = get_intermediate_image_sizes();
		$sizes[] = 'full';

		return $sizes;
	}

	/**
	 * Adds the necessary Twig filters.
	 *
	 * @param Twig_Environment $enviroment
	 */
	public static function add_twig_filters( $enviroment ) {
		$sizes = self::get_image_sizes();

		foreach( $sizes as $size ) {
			$filter = new \Twig_SimpleFilter( $size, function($image) use( $size ) {
				$image->size = $size;
				return $image;
			}, array( 'is_safe' => array( 'html' ) ) );

			$enviroment->addFilter( $filter );
		}

		$filter = new \Twig_SimpleFilter( 'src', function($image) {
			return $image->get_source();
		}, array( 'is_safe' => array( 'html' ) ) );
		$enviroment->addFilter( $filter );
	}

	/**
	 * Prepaares the image attributes.
	 *
	 * @since 0.1
	 *
	 * @return mixed[]
	 */
	protected function image_attributes() {
		if( isset( $this->attr_cache[ $this->size ] ) ) {
			return $this->attr_cache[ $this->size ];
		}

		$alt = isset( $this->meta[ '_wp_attachment_image_alt' ] )
			? $this->meta[ '_wp_attachment_image_alt' ]
			: '';

		if( ! $alt )
			$alt = $this->item->post_excerpt;

		if( ! $alt )
			$alt = $this->item->post_title;

		list( $src, $width, $height ) = wp_get_attachment_image_src( $this->item->ID, $this->size, false );

		$attr = array(
			'alt'    => trim( strip_tags( $alt ) ),
			'src'    => $src,
			'width'  => $width,
			'height' => $height
		);

		/**
		 * Allows the alt tag to be modified before using it.
		 *
		 * @see wp-includes/media.php
		 */
		$attr = apply_filters( 'wp_get_attachment_image_attributes', $attr, $this->item, $this->size );

		# Cache the attributes
		$this->attr_cache[ $this->size ] = $attr;

		return $attr;
	}
}