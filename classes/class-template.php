<?php
namespace Rila;

/**
 * Handles WP query in the nice way.
 */
class Template {
	/**
	 * Holds the name of the template.
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * Holds the context arguments for the template.
	 *
	 * @var mixed[]
	 */
	protected $context = array();

	/**
	 * Holds the default textdomain that will be used for l10n functions.
	 *
	 * @since 0.1
	 * @var string
	 */
	protected static $textdomain;

	/**
	 * Holds the main views directory.
	 *
	 * @since 0.1
	 * @var string
	 */
	protected static $views;

	/**
	 * Holds the main cachedirectory.
	 *
	 * @since 0.1
	 * @var string
	 */
	protected static $cache;

	/**
	 * Initializes the template.
	 *
	 * @param string  $name    The template name.
	 * @param mixed[] $context The name of the context (Pptional).
	 */
	public function __construct( $name, $context = array() ) {
		$this->name    = $name;
		$this->context = $context;

		# Make sure that localisation functions are loaded
		L10N::instance();

		# Load directories
		self::directories();
	}

	/**
	 * Prepares the directories for views and cache.
	 *
	 * @since 0.1
	 */
	protected static function directories() {
		if( ! is_null( self::$views ) ) {
			return array(
				self::$views,
				self::$cache
			);
		}

		# Include both parent and child themes, so children can overwrite templates
		$view_dirs = array(
			get_stylesheet_directory() . '/views/',
			get_template_directory() . '/views/',
		);

		# Don't use the same thing twice
		$view_dirs = array_unique( $view_dirs );

		/**
		 * Allows the directory with views to be modified.
		 *
		 * @since 0.1
		 *
		 * @param string[] $view_dirs The directory with the views.
		 * @return string
		 */
		self::$views = apply_filters( 'rila.views', $view_dirs );

		/**
		 * Allows the directory for cache to be modified.
		 *
		 * @since 0.1
		 *
		 * @param string $цацхе_дир The directory with the views.
		 * @return string
		 */
		self::$cache = apply_filters( 'rila.cache', Site::instance()->uploads_dir );

		if( ! file_exists( self::$cache ) ) {
			mkdir( self::$cache, 0777, true );
		}

		return array(
			self::$views,
			self::$cache
		);
	}

	/**
	 * Prepares all available templating engines.
	 *
	 * @since 0.1
	 *
	 * @return string[] An array of file format => engine class.
	 */
	public static function engines() {
		static $engines;

		if( ! is_null( $engines ) ) {
			return $engines;
		}

		$engines = array(
			'blade.php' => 'Rila\\Engine\\Blade_PHP',
			'twig'      => 'Rila\\Engine\\Twig',
			'php'       => 'Rila\\Engine\\PHP',
		);

		/**
		 * Allows the list of available templating engines to be altered.
		 *
		 * @since 0.1
		 *
		 * @param string[] $engines The available engine classes based on extension.
		 * @return string[]
		 */
		$engines = apply_filters( 'rila.engines', $engines );

		return $engines;
	}

	/**
	 * Creates an engine.
	 *
	 * @since 0.1
	 *
	 * @param string $engine_class The class for the engine.
	 * @return Rila\Engine
	 */
	protected function create_engine( $engine_class ) {
		static $cached = array();

		$engines = self::engines();
		$encoded = md5( $engine_class );

		if( ! in_array( $engine_class, $engines ) ) {
			throw new Exception( "Templating engine not found!" );
		}

		if( isset( $cached[ $encoded ] ) ) {
			return $cached[ $encoded ];
		}

		list( $dir, $cache ) = self::directories();
		$engine = new $engine_class( $dir, $cache );

		$cached[ $encoded ] = $engine;

		return $engine;
	}

	/**
	 * Renders the template and eventually echoes it.
	 *
	 * @since 0.1
	 *
	 * @param bool $echo A flag that indicates if the output should be echoed.
	 * @return string
	 */
	public function render( $echo = true ) {
		$engines              = self::engines();
		list( $dirs, $cache ) = self::directories();
		$found                = false;
		$the_extension        = false;

		# Detect the template
		foreach( (array) $this->name as $full_file ) {
			# Check if there is an extension
			$has_extension = false;
			foreach( $engines as $extension => $engine ) {
				if( preg_match( '~\.' . preg_quote( $extension ) . '$i~', $full_file ) ) {
					$has_extension = $extension;
					break;
				}
			}

			# Make a list of extensions whose files to check
			if( $has_extension ) {
				$available_extensions = array( $extension );
			} else {
				$available_extensions = array_keys( $engines );
			}

			# Check if the file exists
			foreach( $available_extensions as $extension ) {
				$file = preg_replace( '~\.' . preg_quote( $extension ) . '$i~', '', $full_file );

				# Replace dots with folders
				$file = str_replace( '.', '/', $file );

				foreach( $dirs as $dir ) {
					$full = $dir . $file . '.' . $extension;

					# Check the particular extension
					if( file_exists( $full ) ) {
						$found         = $file;
						$the_extension = $extension;
						break;
					}

					if( $found ) {
						break;
					}
				}

				if( $found ) {
					break;
				}
			}
		}

		if( false == $found ) {
			throw new Missing_Template_Exception( "No template found!" );
		}

		# Create some aliases
		$name = $found;
		$file = $found . '.' . $the_extension;
		$ext  = $the_extension;

		# Load the appropriate engine
		$engine = $this->create_engine( $engines[ $ext ] );

		# Prepare the context
		$defaults = self::context();

		# Handle context variables
		$context = array_merge(
			$defaults,
			$this->context
		);

		/**
		 * Allows the context for a template to be modified.
		 *
		 * @since 0.1
		 *
		 * @param mixed[] $context The context for a template.
		 * @param string  $name    The name of the template.
		 * @return mixed[]
		 */
		$context = apply_filters( 'rila.context', $context, $name );

		# Finally, render the template
		$html = $engine->render( $file, $context );

		if( $echo ) {
			echo $html;
		}

		return $html;
	}

	/**
	 * Renders the template.
	 */
	public function __toString() {
		return $this->render( false );
	}

	/**
	 * Returns the default context.
	 *
	 */
	public static function context() {
		static $defaults;

		if( ! is_null( $defaults ) ) {
			return $defaults;
		}

		$defaults = array(
			'site'    => Site::instance(),
			'query'   => new Query( $GLOBALS[ 'wp_query' ] ),
			'request' => Request::instance(),
			'theme'   => Theme::instance(),
			'now'     => new Date
		);

		# Add the first post to the instance, if any.
		if( have_posts() ) {
			$defaults[ 'post' ] = Post_Type::factory();
		} else {
			$defaults[ 'post' ] = false;
		}

		# On singular terms, make the term available
		if( is_tax() || is_category() || is_tag() ) {
			$defaults[ 'term' ] = Taxonomy::factory( get_queried_object() );
		} else {
			$defaults[ 'term' ] = false;
		}

		# Locate post types
		foreach( get_post_types() as $post_type ) {
			$plural = preg_match( '~y$~i', $post_type )
				? preg_replace( '~y$~i', 'ies', $post_type )
				: $post_type . 's';

			$plural = strtolower( str_replace( '-', '_', $plural ) );

			$collection = new Collection\Posts();
			$defaults[ $plural ] = $collection->type( $post_type );
		}

		# Locate taxonomies
		foreach( get_taxonomies() as $taxonomy ) {
			$plural = preg_match( '~y$~i', $taxonomy )
				? preg_replace( '~y$~i', 'ies', $taxonomy )
				: $taxonomy . 's';

			$plural = strtolower( str_replace( '-', '_', $plural ) );

			$collection = new Collection\Terms();
			$defaults[ $plural ] = $collection->where( 'taxonomy', $taxonomy );
		}

		/**
		 * Allows the global Twig context to be modified.
		 *
		 * @since 0.1
		 *
		 * @param mixed[] $defaults The defaults that will be included.
		 * @return mixed[]
		 */
		$defaults = apply_filters( 'rila.defaults', $defaults );

		/**
		 * Allows individual defaults to be modified.
		 *
		 * @since 0.1
		 *
		 * @param mixed The object that can be changed.
		 */
		foreach( $defaults as $key => $value ) {
			do_action( 'rila.defaults.' . $key, $value );
		}

		return $defaults;
	}

	/**
	 * Adds additional context arguments.
	 *
	 * @param string $key The key for the argument.
	 * @param mixed  $value The value for the argument.
	 */
	public function with( $key, $value ) {
		$this->context[ $key ] = $value;
		return $this;
	}
}
