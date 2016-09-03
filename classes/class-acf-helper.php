<?php
namespace Rila;

/**
 * Handles ACF repeater & flexible content fields.
 *
 * @since 0.1
 */
class ACF_Helper {
	/**
	 * Creates a singular instance of the class.
	 *
	 * @since 0.1
	 */
	public static function instance() {
		static $instance;

		if( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Adds the needed handlers.
	 *
	 * @since 0.1
	 */
	protected function __construct() {
		add_filter( 'rila.meta', array( $this, 'handle_site' ), 9, 3 );
		add_filter( 'rila.meta', array( $this, 'handle_terms' ), 9, 3 );
		add_filter( 'rila.meta', array( $this, 'handle_widget' ), 9, 3 );
		add_filter( 'rila.meta', array( $this, 'handle_meta' ), 10, 3 );
		add_filter( 'acf/settings/autoload', '__return_true' );
	}

	/**
	 * Handles the meta for an object.
	 *
	 * Parses the normal meta values to a linear array,
	 * afterwards tries flexible content fields first and repeaters afterwards.
	 *
	 * @since 0.1
	 *
	 * @param mixed[] $meta The existing meta data.
	 * @return mixed[]
	 */
	public function handle_meta( $empty, $meta, $object = null ) {
		$processed = array();

		// Treat options differently
		if(
			is_a( $object, 'Rila\\Site' )
			|| is_a( $object, 'Rila\\Taxonomy' )
			|| is_a( $object, 'Rila\\Widget' )
		) {
			return $empty;
		}

		# Simplify once
		if( $meta ) foreach( $meta as $key => $value ) {
			if( is_array( $value ) && 1 == count( $value ) ) {
				$processed[ $key ] = maybe_unserialize( $value[ 0 ] );
			} else {
				$processed[ $key ] = $value;
			}
		}

		$processed = $this->maybe_parse_flexible_content( $processed );
		$processed = $this->maybe_parse_repeaters( $processed );

		return $processed;
	}

	/**
	 * Within a linear array of values, tries to handle
	 *
	 * @since 0.1
	 *
	 * @param mixed[] $meta The existing meta values.
	 * @param mixed[] Eventually modified arrays with nested values.
	 */
	protected function maybe_parse_flexible_content( $meta ) {
		$processed = array();
		$repeaters = array();

		# Make sure shorter keys appear first
		ksort( $meta );

		# Detect repeaters
		foreach( $meta as $key => $value ) {
			# we need arrays
			if( ! is_array( $value ) ) {
				continue;
			}

			if( count( array_filter( $value ) ) != count( $value ) ) {
				continue; // Not all keys
			}

			# Look for values
			$found = false;
			foreach( $meta as $t1 => $t2 ) {
				if( $key != $t1 && strpos( $t1, $key ) === 0 ) {
					$found = true;
					$nested = false;

					foreach( $repeaters as $rv => $rd ) {
						if( strpos( $key, $rv ) === 0 )
							$found = false;
					}

					if( $found ) {
						$repeaters[ $key ] = $value;
						break;
					}
				}
			}
		}

		# Prepare repeater arrays
		$repeater_data = array();
		foreach( $repeaters as $key => $repeater ) {
			$repeater_data[ $key ] = array();
		}

		# Get data for repeaters and prepare ignored values.
		$processed = array();
		$ignored   = array();
		foreach( $meta as $key => $value ) {
			$in_repeater = false;

			foreach( $repeaters as $rkey => $rtypes ) {
				foreach( $rtypes as $i => $type ) {
					$prefix = $rkey . '_' . $i;
					$repeater_data[ $rkey ][ $i . '___type' ] = $type;

					if( strpos( $key, $prefix ) === 0 ) {
						$in_repeater = true;
						$repeater_data[ $rkey ][ preg_replace( '~^' . preg_quote( $rkey ) . '_~', '', $key ) ] = $value;
						$ignored[] = '_' . $key;
						break;
					}
				}
			}

			if( ! $in_repeater ) {
				$processed[ $key ] = $value;
			}
		}

		# Add the repeaters to the normal flow
		foreach( $repeater_data as $key => $repeater_data ) {
			$ignored[] = '_' . $key;
			$processed[ $key ] = $repeater_data;
		}

		# Remove ignored keys (ACF field keys)
		$meta = array();
		foreach( $processed as $key => $value ) {
			if( ! in_array( $key, $ignored ) ) {
				$meta[ $key ] = $value;
			}
		}

		# Process the actual repeaters
		foreach( $repeaters as $rkey => $rgroups ) {
			$repeater_data = array();

			foreach( $meta[ $rkey ] as $key => $value ) {
				$values = preg_match( '~^(\d+)_(.+)$~', $key, $matches );

				if( empty( $values ) ) {
					continue;
				}

				$idx = intval( $matches[ 1 ] );
				$clean_key = $matches[ 2 ];

				if( ! isset( $repeater_data[ $idx ] ) ) {
					$repeater_data[ $idx ] = array();
				}

				$repeater_data[ $idx ][ $clean_key ] = $value;
			}

			$full_data = array();
			foreach( $repeater_data as $group ) {
				$group = $this->maybe_parse_flexible_content( $group );
				$group = $this->maybe_parse_repeaters( $group );
				$full_data[] = $group;
			}

			$meta[ $rkey ] = $full_data;
		}

		return $meta;
	}

	/**
	 * Within a linear array of values, tries to handle
	 *
	 * @since 0.1
	 *
	 * @param mixed[] $meta The existing meta values.
	 * @param mixed[] Eventually modified arrays with nested values.
	 */
	protected function maybe_parse_repeaters( $meta ) {
		# Check for things that have a change to be a repeater
		$could_be_repeater = array();
		foreach( $meta as $key => $value ) {
			if( ( ( is_string( $value ) && preg_match( '~^\d+$~', $value ) ) || is_int( $value ) ) && $int = intval( $value ) ) {
				$could_be_repeater[ $key ] = $int;
			}
		}

		# No repeaters, no fun
		if( empty( $could_be_repeater ) ) {
			return $meta;
		}

		# Check if those things actual have any values
		$is_repeater = array();
		foreach( $could_be_repeater as $rkey => $int ) {
			$found = 0;

			for( $i=0; $i<$int; $i++ ) {
				foreach( $meta as $key => $value ) {
					if( 0 === strpos( $key, $rkey . '_' . $i . '_' ) ) {
						$found++;
						break;
					}
				}

				# Make sure a post ID or a timestamp don't loop forever
				if( ! $found ) {
					break;
				}
			}

			if( $found == $int ) {
				$is_repeater[ $rkey ] = $int;
			}
		}

		# No repeaters, no fun
		if( empty( $is_repeater ) ) {
			return $meta;
		}

		# Go an load repeaters
		$repeater_data = array();
		$ignored       = array();

		foreach( $is_repeater as $rkey => $rcount ) {
			$repeater_data[ $rkey ] = array();

			for( $i=0; $i<$rcount; $i++ ) {
				foreach( $meta as $key => $value ) {
					$idx = $rkey . '_' . $i . '_';

					if( 0 === strpos( $key, $idx ) ) {
						$ignored[] = $key;
						$ignored[] = '_' . $key;
						$repeater_data[ $rkey ][ $i ][ preg_replace( '~^' . preg_quote( $idx ) . '~', '', $key ) ] = $value;
					}
				}
			}
		}
		# Inject the repeater values into place
		foreach( $repeater_data as $key => $value ) {
			$meta[ $key ] = $value;
		}

		# Ignore ignored keys
		$processed = array();
		foreach( $meta as $key => $value ) {
			if( ! in_array( $key, $ignored ) ) {
				$processed[ $key ] = $value;
			}
		}

		return $processed;
	}

	/**
	 * Handles the meta for the site object.
	 *
	 * @since 0.1
	 *
	 * @param Site $site The site to modify.
	 */
	public function handle_site( $processed, $source, $site ) {
		if( ! is_a( $site, 'Rila\\Site' ) )
			return $processed;

		$raw = wp_load_alloptions();
		$options = array();

		foreach( $raw as $key => $value ) {
			$options[ $key ] = maybe_unserialize( $value );
		}

		$options = $this->maybe_parse_flexible_content( $options );
		$options = $this->maybe_parse_repeaters( $options );

		$ready = array();
		$acf   = array();
		foreach( $options as $key => $value ) {
			if( 0 === strpos( $key, 'options_' ) ) {
				$acf[ preg_replace( '~^options_~', '', $key ) ] = $value;
			} else {
				$ready[ $key ] = $value;
			}
		}

		$ready = array_merge( $ready, $acf );
		$this->options = $ready;

		return $ready;
	}

	/**
	 * Handles the meta for a term object.
	 *
	 * @since 0.1
	 *
	 * @param Taxonomy $term The term to modify.
	 */
	public function handle_terms( $processed, $source, $term ) {
		if( ! is_a( $term, 'Rila\\Taxonomy' ) )
			return $processed;

		$prefix = $term->item->taxonomy . '_' . $term->item->term_id . '_';
		$meta   = array();

		foreach( $this->options as $key => $value ) {
			if( 0 === strpos( $key, $prefix ) ) {
				$meta[ str_replace( $prefix, '', $key ) ] = $value;
			}
		}

		return $meta;
	}

	/**
	 * Handles the meta for a widget.
	 *
	 * @since 0.1
	 *
	 * @param Taxonomy $term The term to modify.
	 */
	public function handle_widget( $processed, $source, $widget ) {
		if( ! is_a( $widget, 'Rila\\Widget' ) )
			return $processed;

		$prefix = 'widget_' . $widget->wp_widget->id . '_';
		$meta   = array();

		foreach( $this->options as $key => $value ) {
			if( 0 === strpos( $key, $prefix ) ) {
				$meta[ str_replace( $prefix, '', $key ) ] = $value;
			}
		}

		return $meta;
	}
}
