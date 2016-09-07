<?php
/**
 * Holds globally available functions.
 *
 * @since 0.1
 */

/**
 * Returns an instance of the main class.
 *
 * @since 0.1
 * 
 * @return Rila\Plugin
 */
function rila_framework() {
	return Rila\Plugin::instance();
}

/**
 * Creates a new post object.
 * 
 * @since 0.1
 * 
 * @param mixed $id Either the ID of the post, a post object or a WP_Post object.
 * @return Post_Type
 */
function rila_post( $id = null ) {
	if( ! $id ) {
		$id = get_the_id();
	}

	return Rila\Post_Type::factory( $id );
}

/**
 * Creates a new term object.
 * 
 * @since 0.1
 * 
 * @param mixed $term Either a term ID, a WP_Term or a Term object.
 * @return Term
 */
function rila_term( $term ) {
	return Rila\Taxonomy::factory( $term );
}

/**
 * Returns the instance of the current site.
 * 
 * @since 0.1
 * 
 * @return Site
 */
function rila_site() {
	return Rila\Site::instance();
}

/**
 * Creates a new view.
 * 
 * When using the view, you need to either return it or echo it, based on the context.
 * 
 * @since 0.1
 * 
 * @param string $name Either the name of the view (without .twig) or an array of names.
 * @param mixed[] $context The context for the view. The ->with( 'name', $value ) can be used too.
 * @return Template A template that can be manipulated or rendered.
 */
function rila_view( $name, $context = array() ) {
	return new Rila\Template( $name, $context );
}

/**
 * Creates a new comment.
 * 
 * @since 0.1
 * 
 * @param mixed $comment Either a WP_Comment or a comment ID.
 * @return Comment
 */
function rila_comment( $comment ) {
	return new Rila\Comment( $comment );
}

/**
 * Creates a new user object.
 * 
 * @since 0.1
 * 
 * @param mixed $user Either a WP_User or a user ID.
 * @return User
 */
function rila_user( $user ) {
	return new Rila\User( $user );
}

/**
 * Creates a new image object.
 * 
 * @since 0.1
 * 
 * @param int $id The ID of the image.
 * @return File
 */
function rila_file( $file ) {
	return new Rila\File( $file );
}

/**
 * Creates a new image object.
 * 
 * @since 0.1
 * 
 * @param int $id The ID of the image.
 * @return Image
 */
function rila_image( $image ) {
	return new Rila\Image( $image );
}

/**
 * Creates a new date object.
 * 
 * @since 0.1
 * 
 * @param mixed $date THe date/time to use.
 * @return Date
 */
function rila_date( $date ) {
	return new Rila\Date( $date );
}

/**
 * Creates a new query.
 * 
 * @since 0.1
 * 
 * @param mixed $request WP_Query arguments. Can also be an array of IDs for specific posts.
 * @return Query
 */
function rila_query( $request = array() ) {
	return new Rila\Query( $request );
}

/**
 * Converts a linear array with dot notations to a nested one.
 * 
 * @since 0.1
 * 
 * @param mixed[] $data The flat array with keys like content_blocks.text.title.
 * @return mixed[] The nested array
 */
function rila_dot_to_array( $data ) {
	$processed = array();
	$go_deep   = array();

	foreach( $data as $key => $value ) {
		if( false !== ( $pos = strpos( $key, '.' ) ) ) {
			$gr = substr( $key, 0, $pos );
			$rest = substr( $key, $pos+1 );

			if( isset( $processed[ $gr ] ) ) {
				$processed[ $gr ][ $rest ] = $value;
			} else {
				$processed[ $gr ] = array( $rest => $value );
			}

			$go_deep[ $gr ] = 1;
		} else {
			$processed[ $key ] = $value;
		}
	}

	foreach( array_keys( $go_deep ) as $key ) {
		$processed[ $key ] = rila_dot_to_array( $processed[ $key ] );
	}

	return $processed;
}

/**
 * Processes arguments for functions that are in URL-like format.
 * 
 * @since 0.1
 * 
 * @param mixed $query The needed query, ex. main-menu?menu_class=the-menu
 * @return moxed[]
 */
function rila_parse_args( $query ) {
	$query = explode( '?', $query );

	$main = $query[ 0 ];
	if( count( $query ) > 1 ) {
		$extra = wp_parse_args( $query[ 1 ] );
	} else {
		$extra = array();
	}

	return array(
		'main' => $main,
		'args' => $extra
	);
}