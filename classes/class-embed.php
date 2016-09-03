<?php
namespace Rila;

/**
 * Handles embeds through their URL.
 * 
 * @since 0.1
 */
class Embed {
	/**
	 * Holds the URL.
	 * 
	 * @since 0.1
	 * @var string
	 */
	protected $url;

	/**
	 * Width of the embed.
	 * 
	 * @since 0.1
	 * @var int
	 */
	protected $width;

	/**
	 * Height of the element.
	 * 
	 * @since 0.1
	 * @var int
	 */
	protected $height;

	/**
	 * Initializes the class by receiving an URL.
	 * 
	 * @since 0.1
	 * 
	 * @param string $url The URL to handle.
	 */
	public function __construct( $url ) {
		$this->url = $url;
	}

	/**
	 * Handles the actual embedding.
	 * 
	 * @since 0.1
	 * 
	 * @return string
	 */
	public function __toString() {
		$args = array_filter( array(
			'width'  => $this->width,
			'height' => $this->height
		));

		# Retrieve and return
		$cache_key = 'embed_transient_' . md5( $this->url . serialize( $args ) );
		if( $cached = get_transient( $cache_key ) ) {
			return $cached;
		}

		# Cache and prosper
		$code = wp_oembed_get( $this->url, $args );
		set_transient( $cache_key, $code, 60 * 60 * 3 );

		return $code;
	}

	/**
	 * Allows the width of the embed to be changed.
	 *
	 * @since 0.1
	 * 
	 * @param int $width The width in pixels.
	 * @return Embed
	 */
	public function width( $width ) {
		$this->width = $width;
		
		return $this;
	}

	/**
	 * Allows the height of the embed to be changed.
	 *
	 * @since 0.1
	 * 
	 * @param int $height The height in pixels.
	 * @return Embed
	 */
	public function height( $height ) {
		$this->height = $height;
		
		return $this;
	}
}