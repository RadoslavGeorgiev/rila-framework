<?php
namespace Rila;

/**
 * Handles normal breadcrumbs based on the current item.
 *
 * @since 0.11
 */
class Breadcrumbs {
	/**
	 * Holds all current items.
	 *
	 * @since 0.11
	 * @var Rila\Item
	 */
	protected $item;

	/**
	 * Saves the current item if one is available.
	 *
	 * @since 0.11
	 *
	 * @param Rila\Item $item The item that should be used as the current one.
	 */
	public function __construct( $item = null ) {
		if( ! is_null( $item ) ) {
			$this->item = $item;
		}
	}

	/**
	 * Locates the main item for the breadcrumbs.
	 *
	 * @since 0.11
	 *
	 * @return Rila\Item
	 */
	protected function get_item() {
		if( is_null( $this->item ) ) {
			# Determine the current thing
			if( is_404() ) {
				return false;
			} else {
				$item = rila( get_queried_object() );
			}
		} else {
			$item = $this->item;
		}

		/**
		 * Allows the current item for breadcrumbs to be overwritten.
		 *
		 * @since 0.11
		 *
		 * @param mixed $item The item that will be used for the breadcrumbs.
		 */
		$this->item = apply_filters( 'rila.breadcrumbs.item', $item );

		return $this->item;
	}

	/**
	 * Renders the breadcrumbs.
	 *
	 * @since 0.11
	 *
	 * @return string
	 */
	public function render() {
		$item = $this->get_item();

		if( false === $item ) {
			return $this->not_found();
		}

		/**
		 * Allows the breadcrumb settings for Rila to be modified.
		 *
		 * @since 0.1
		 *
		 * @param mixed[] $args The breadcrumb arguments.
		 * @return mixed
		 */
		$args = apply_filters( 'rila.breadcrumbs.args', array(), $item );

		$settings = wp_parse_args( $args, array(
			'glue' => ' &raquo; '
		));

		# Add the normal item tree
		$links = $this->crawl( $item );

		# Render the actual navigation
		$strings = array();
		foreach( $links as $i => $link ) {
			if( is_object( $link ) && method_exists( $link, 'breadcrumb' ) ) {
				$is_root = ( 0 === $i ) && ( 1 != count( $links ) );

				$strings[] = $link->breadcrumb( $is_root, $i + 1 );
			} else {
				$strings[] = (string) $link;
			}
		}

		return implode( $settings[ 'glue' ], $strings );
	}

	/**
	 * Retrieves the breadcrumbs for a certain item.
	 *
	 * @since 0.11
	 *
	 * @param mixed $item The item to crawl.
	 * @return mixed[]
	 */
	protected function crawl( $item ) {
		$links      = array();
		$root_links = array();
		$last_item  = false;

		do {
			$links[]   = $item;
			$last_item = $item;
			$item      = $item->parent;
		} while( $item );

		# Add the root page if there is one.
		if( $last_item && method_exists( $last_item, 'get_breadcrumbs_root' ) ) {
			$root_links = $this->crawl( $last_item->get_breadcrumbs_root() );
		}

		# Reverse the links
		$links = array_reverse( $links );

		$links = array_merge( $root_links, $links );

		return $links;
	}

	/**
	 * Generates the string reprensentation of all breadcrumbs.
	 *
	 * @since 0.11
	 *
	 * @return string
	 */
	public function __toString() {
		try {
			return $this->render();
		} catch( \Exception $e ) {
			return $e->getMessage();
		}
	}

	/**
	 * Displays a 404 string.
	 *
	 * @since 3.0
	 */
	public function not_found() {
		return method_exists( rila_site(), 'not_found' )
			? rila_site()->not_found()
			:  sprintf(
				'<a href="%s">%s</a>',
				rila_site()->url,
				'404'
			);
	}

	/**
	 * Returns the last parent breadcrumb.
	 *
	 * @since 0.3
	 *
	 * @return Rila\Item
	 */
	public function get_parent() {
		$item = $this->get_item();

		if( false === $item ) {
			return false;
		}

		// Check for a normal parent
		if( $item->parent ) {
			return $item->parent;
		} elseif( method_exists( $item, 'get_breadcrumbs_root' ) ) {
			return $item->get_breadcrumbs_root();
		} else {
			return false;
		}
	}
}
