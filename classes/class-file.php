<?php
namespace Rila;

/**
 * Handles files within the front-end.
 */
class File extends Post_Type {
	/**
	 * Returns a link to the post.
	 *
	 * @return string
	 */
	public function url() {
		return wp_get_attachment_url( $this->item->ID );
	}

	/**
	 * Converts the image to string.
	 *
	 * @return string
	 */
	public function __toString() {
		return wp_get_attachment_image( $this->item->ID );
	}
}