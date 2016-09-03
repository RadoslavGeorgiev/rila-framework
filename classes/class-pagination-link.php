<?php
namespace Rila\Pagination;

use Rila\Pagination;

/**
 * Handles prev/next links for the pagination.
 * 
 * @since 0.1
 */
class Link {
	/**
	 * Holds the URL for the link.
	 * 
	 * @since 0.1
	 * @var string
	 */
	public $url;

	/**
	 * Holds the text of the link.
	 * 
	 * @since 0.1
	 * @var string
	 */
	public $text;

	/**
	 * Holds the simple number of the page.
	 * 
	 * @since 0.1
	 * @var int
	 */
	public $number;

	/**
	 * Constructs the class by getting the right numbers.
	 * 
	 * @since 0.1
	 * 
	 * @param string $url The URL of the link.
	 * @param string $text The text of the link.
	 * @param int $number  The number of the page.
	 */
	public function __construct( $url, $text, $number ) {
		$this->url    = $url;
		$this->text   = $text;
		$this->number = $number;
	}

	/**
	 * Converts the link to a string for direct echoing.
	 *
	 * @since 0.1
	 * 
	 * @return string
	 */
	public function __toString() {
		return sprintf(
			' <a href="%s" class="arrow-link page-numbers">%s</a> ',
			$this->url,
			$this->text
		);
	}
}