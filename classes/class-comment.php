<?php
namespace Rila;

use Rila\Item;
use Rila\Collection\Comments;
use Rila\Missing_Object_Exception;

/**
 * Encapsulates the WP_Comment class in order to provide
 * smart and template-friendly functionality.
 *
 * @since 0.1
 */
class Comment extends Item {
	/**
	 * Constructs the item by receiving post data.
	 *
	 * @since 0.1
	 *
	 * @param WP_Comment $comment The comment that we're working with.
	 */
	function __construct( \WP_Comment $post ) {
		$this->item = $post;
		$this->setup_meta( get_comment_meta( $this->item->ID ) );

		# After all the rest is done, use individual initializers
		$this->initialize();
	}

	/**
	 * Creates a new instance of the class.
	 *
	 * @param int|WP_Comment|Comment $comment The ID of the comment, or a comment object.
	 * @return Comment
	 */
	public static function factory( $comment = null ) {
		if( is_a( $comment, 'Rila\\Comment' ) ) {
			return $comment;
		}

		if( is_scalar( $comment ) && intval( $comment ) ) {
			$comment = get_comment( $comment );
		}

		if( ! is_a( $comment, 'WP_Comment' ) ) {
			throw new Missing_Object_Exception( 'Comment factory could not retrieve a comment.' );
		}

		/**
		 * Allows the class that is used for comments to be overridden.
		 *
		 * @since 0.1
		 *
		 * @param string $class_name The default class name that will be used.
		 * @return string
		 */
		$class_name = apply_filters( 'rila.comment_class', get_class(), $comment );

		return new $class_name( $comment );
	}

	/**
	 * Handles type-specific actions, like translations, etc.
	 *
	 * @since 0.1
	 */
	protected function initialize() {
		parent::initialize();

		$this->translate(array(
			'id'       => 'comment_ID',
			'ID'       => 'comment_ID',
			'post'     => 'comment_post_ID',
			'date'     => 'comment_date',
			'text'     => 'comment_content',
			'content'  => 'comment_content',
			'user'     => 'user_id',
			'author'   => 'user_id',
			'approved' => 'comment_approved',
			'parent'   => 'comment_parent'
		));

		$this->map(array(
			'comment_post_ID' => 'post',
			'comment_content' => 'wpautop',
			'user_id'         => 'user',
			'comment_parent'  => 'comment',
			'comment_date'    => 'date'
		));

		/**
		 * Allows additional values to be translated or mapped for a comment.
		 *
		 * @since 0.1
		 *
		 * @param Rila\Comment $comment The comment that is being modified.
		 */
		do_action( 'rila.comment', $this );
	}

	/**
	 * Handles the children of the comment.
	 *
	 * @since 0.1
	 * @ToDo: Verify is this is the best method for retrieving comments
	 * without generating one additional query per comment.
	 *
	 * @return Rila\Collection\Comments
	 */
	public function children() {
		return new Comments(array(
			'parent' => $this->item->comment_ID
		));
	}

	/**
	 * Returns a human_time_diff string for when the comment was created.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function ago() {
		return human_time_diff( time(), strtotime( $this->item->post_date ) );
	}

	/**
	 * Returns the reply link.
	 *
	 * @since 0.1
	 *
	 * @param mixed[] $args Arguments for the link.
	 * @return string
	 */
	public function reply_link( $args = array() ) {
		$defaults = array(
			'tag'        => 'div',
			'add_below'  => 'comment',
			'reply_text' => __( 'Reply' ),
			'depth'      => 1,
			'max_depth'  => 3
		);

		$args = wp_parse_args( $args, $defaults );

		return get_comment_reply_link( $args, $this->item->comment_ID, $this->item->comment_post_ID );
	}

	/**
	 * Creates a new group of fields and directly associates it with comments.
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
		$location->add_rule( 'comment', 'all' );

		return self::_add_fields( $id, $title, $location, $fields );
	}
}
