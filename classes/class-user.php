<?php
namespace Rila;

use Rila\Item;
use Rila\Query;
use Rila\Missing_Object_Exception;

/**
 * Encapsulates the WP_User class in order to provide
 * smart and template-friendly functionality.
 *
 * @since 0.1
 */
class User extends Item {
	/**
	 * Holds the actual user object, as the "item" property will contain user data.
	 *
	 * @since 2.0
	 * @var WP_User
	 */
	protected $user;

	/**
	 * Constructs the item by receiving user data.
	 *
	 * @since 0.1
	 *
	 * @param WP_User $post The user that we're working with.
	 */
	function __construct( \WP_User $user ) {
		$this->user = $user;
		$this->item = $user->data;

		$this->setup_meta( get_user_meta( $this->user->ID ) );

		# After all the rest is done, use individual initializers
		$this->initialize();
	}

	/**
	 * Creates a new instance of the class.
	 *
	 * @param int|WP_User|User|null $user The ID of the user, a WP_User object or a
	 *                                    User object or null for the current user.
	 * @return User
	 */
	public static function factory( $user = null ) {
		if( is_a( $user, 'Rila\\User' ) ) {
			return $user;
		}

		if( is_null( $user ) && is_user_logged_in() ) {
			$user = wp_get_current_user();
		}

		if( ! is_object( $user ) && intval( $user ) ) {
			$user = get_user_by( 'id', $user );
		}

		if( ! is_a( $user, 'WP_User' ) ) {
			throw new Missing_Object_Exception( 'User factory could not find a user.' );
		}

		/**
		 * Allows the class that is used for users to be overridden.
		 *
		 * @since 0.1
		 *
		 * @param string $class_name The default class name that will be used.
		 * @return string
		 */
		$class_name = apply_filters( 'rila.user_class', get_class(), $user );

		return new $class_name( $user );
	}

	/**
	 * Handles type-specific actions, like translations, etc.
	 *
	 * @since 0.1
	 */
	protected function initialize() {
		parent::initialize();

		$this->translate(array(
			'id'    => 'ID',
			'name'  => 'display_name',
			'title' => 'display_name',
			'email' => 'user_email',
			'login' => 'user_login',
		));

		/**
		 * Allows additional values to be translated or mapped for a user.
		 *
		 * @since 0.1
		 *
		 * @param Rila\User $user The user that is being modified.
		 */
		do_action( 'rila.user', $this );
	}

	/**
	 * Handles additionall getters, for posts, just taxonomies.
	 *
	 * @since 0.1
	 *
	 * @param string $property The name of the property.
	 * @return mixed[]
	 */
	protected function get( $property ) {
		if( property_exists( $this->user, $property ) ) {
			$returnable = $this->user->$property;
		}

		if( isset( $returnable ) ) {
			return $returnable;
		}
	}

	/**
	 * Returns a link to the post.
	 *
	 * @return string
	 */
	public function url() {
		return get_author_posts_url( $this->user->ID );
	}

	/**
	 * Converts the user to a string.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function __toString() {
		return sprintf(
			'<a href="%s">%s</a>',
			$this->url,
			$this->title
		);
	}

	/**
	 * Returns a query, that is prepared to handle the posts of the user.
	 *
	 * @return Rila\Query
	 */
	public function posts() {
		return new Query(array(
			'post_type' => 'any',
			'author'    => $this->user->ID
		));
	}

	/**
	 * Proxies the "can" method of the WP_User object.
	 *
	 * @since 0.1
	 *
	 * @param string $capability The capability yo check for.
	 * @return bool
	 */
	public function can( $capability ) {
		if( ! is_user_logged_in() )
			return false;

		return $this->user->has_cap( $capability );
	}

	/**
	 * Creates a new group of fields and directly associates it with users.
	 *
	 * @since 0.1
	 * @link https://github.com/RadoslavGeorgiev/acf-code-helper
	 *
	 * @param string  $title   The title of the metabox.
	 * @param mixed[] $fields The fields to add in the metabox.
	 * @return ACF_Group The created ACF_Group, which can be modified further.
	 */
	protected static function add_fields( $title, $fields ) {
		$caller = get_called_class();
		$slug   = self::get_registered_slug( $caller );
		$id     = self::unique_id( $slug );

		$location = new \ACF_Group_Location();
		$location->add_rule( 'user_form', 'all' );

		return self::_add_fields( $id, $title, $location, $fields );
	}

}
