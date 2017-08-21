<?php
namespace Rila;

/**
 * Adds the necessary functionality and pipes for displaying a date.
 */
class Date {
	/**
	 * Holds the PHP date object.
	 *
	 * @var \Date
	 */
	protected $date;

	/**
	 * Creates a new date object.
	 *
	 * @param mixed $date The needed date.
	 * @return Date
	 */
	public static function factory( $date = null ) {
		return new self( $date );
	}

	/**
	 * Creates a new instance of the class.
	 *
	 * @param mixed $date The needed date.
	 */
	public function __construct( $date = null ) {
		if( is_int( $date ) ) {
			$d = $date;
		} elseif( $date ) {
			$d = strtotime( $date );
		} else {
			$d = time();
		}

		$this->date = new \DateTime();
		$this->date->setTimestamp( $d );
	}

	/**
	 * Converts the date to string.
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->date->format( get_option( 'date_format' ) );
	}

	/**
	 * Returns the time based on the blog's format.
	 *
	 * @return string
	 */
	public function get_time() {
		return $this->date->format( get_option( 'time_format' ) );
	}

	/**
	 * Returns the date in a custom format.
	 *
	 * @param string $format The needed format.
	 * @return string
	 */
	public function format( $format ) {
		return $this->date->format( $format );
	}

	/**
	 * Handles requests to unknown properties.
	 */
	public function __get( $property ) {
		if( 'time' == $property ) {
			return $this->get_time();
		}
	}

	public function __isset( $property ) {
		return 'time' == $property;
	}

	/**
	 * Initializes the class.
	 */
	public static function init() {
		add_action( 'rila.twig.environment', array( 'Rila\Date', 'add_twig_filters' ) );
	}

	/**
	 * Adds the necessary Twig filters.
	 *
	 * @param Twig_Environment $enviroment
	 */
	public static function add_twig_filters( $enviroment ) {
		$filter = new \Twig_SimpleFilter( 'time', function($date) {
			return $date->get_time();
		}, array( 'is_safe' => array( 'html' ) ) );
		$enviroment->addFilter( $filter );
	}

	/**
	 * Returns a himan-readable time diff between the date and now or another date.
	 *
	 * @since 0.3
	 *
	 * @param mixed $compare_to The date to compare to (optional).
	 * @return string
	 */
	public function diff( $compare_to = null ) {
		if( ! is_null( $compare_to ) ) {
			if( is_a( $compare_to, self::class ) ) {
				$compare_to = $compare_to->date;
			}
		} else {
			$compare_to = time();
		}

		return human_time_diff( $this->date->getTimestamp(), $compare_to );
	}
}

Date::init();
