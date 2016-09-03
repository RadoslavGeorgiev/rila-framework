<?php
namespace Rila;

use Rila\Pagination\Link;

/**
 * Handles pagination of queries only.
 * 
 * @since 0.1
 */
class Pagination {
	/**
	 * Holds the query, which the pagination is reponsible for.
	 * 
	 * @since 0.1
	 * @var Query
	 */
	protected $query;

	/**
	 * Indicates if prevnext should still be included.
	 * 
	 * When a direction is called/checked, this switches to false.
	 * 
	 * @since 0.1
	 * @var bool
	 */
	protected $prevnext = true;

	/**
	 * Handles cached properties.
	 * 
	 * @since 0.1
	 * @var mixed[]
	 */
	protected $cache = array();

	/**
	 * Holds the baselink of the pagination.
	 * 
	 * Modify this to allow different paging structure.
	 * Should use %#% as a placeholder for the page number.
	 *
	 * @since 0.1
	 * @var string
	 */
	public $baselink = '';

	/**
	 * Holds the arguments for paginate_links()
	 *
	 * @since 0.1
	 * @var mixed
	 */
	public $args = array();

	/**
	 * Initializing based on query.
	 * 
	 * @since 0.1
	 * @param Query $query
	 */
	public function __construct( Query $query ) {
		$this->query = $query;
	}

	/**
	 * The string representation is the numbered output.
	 * 
	 * @since 0.1
	 * @return string
	 */
	public function __toString() {
		$numbers = $this->numbers();

		if( $numbers ) {
			return '<div class="pagination">' . $numbers . '</div>';
		}

		return '';
	}

	/**
	 * Displays normal pagination.
	 * 
	 * @since 0.1
	 */
	public function numbers() {
		$defaults = array(
			'base'      => $this->get_base_link(),
			'format'    => '?paged=%#%',
			'current'   => max( 1, $this->query->query->get( 'paged' ) ),
			'total'     => $this->query->query->max_num_pages,
		);

		$args = array_merge( $defaults, $this->args );

		# Explicitly disable prev/next when the buttons have already been used.
		if( ! $this->prevnext ) {
			$args[ 'prev_next' ] = false;
		}

		$nav = paginate_links( $args );

		return $nav ? $nav : '';
	}

	/**
	 * Handles prev/next buttons.
	 * 
	 * @since 0.1
	 * 
	 * @return mixed[]
	 */
	public function __get( $property ) {
		if( isset( $this->cache[ $property ] ) ) {
			return $this->cache[ $property ];
		}

		$p = max( 1, $this->query->query->get( 'paged' ) );

		if( 'next' == $property ) {
			$this->prevnext = false;

			if( $this->query->query->max_num_pages == $p ) {
				$returnable = false;
			} else {
				$returnable = new Link( $this->link( $p + 1 ), '&raquo;', $p + 1 );
			}
		}

		if( 'prev' == $property || 'previous' == $property ) {
			$this->prevnext = false;

			if( 1 >= $p ) {
				$returnable = false;
			} else {
				$returnable = new Link( $this->link( $p - 1 ), '&laquo;', $p - 1 );				
			}
		}

		if( isset( $returnable ) ) {
			$this->$property = $returnable;
			return $returnable;
		}

		throw new Undefined_Property_Exception( "Undefined property $property" );
	}

	/**
	* The isset call confirms if a property exists.
	*
	* @return bool
	*/
	public function __isset( $property ) {
		try {
			return null !== $this->__get( $property );
		} catch( Undefined_Property_Exception $e ) {
			return false;
		}
	}

	/**
	 * Returns the baselink.
	 * 
	 * @since 0.1
	 * 
	 * @return string
	 */
	function get_base_link() {
		if( $this->baselink ) {
			return $this->baselink;
		}

		$big = 999999999;
		return $this->baselink = str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) );
	}

	/**
	 * Generates the link to a page number.
	 * 
	 * @return bool
	 * 
	 * @param int $page The number of the page.
	 * @return string
	 */
	public function link( $page ) {
		return str_replace( '%#%', $page, $this->get_base_link() );
	}
}