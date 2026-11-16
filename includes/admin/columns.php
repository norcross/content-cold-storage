<?php
/**
 * Load our various column filtering.
 *
 * @package ContentColdStorage
 */

// Declare our namespace.
namespace Norcross\ContentColdStorage\Admin\Columns;

// Set our aliases.
use Norcross\ContentColdStorage\Admin\Config as AdminConfig;

/**
 * Start our engines.
 */
add_action( 'manage_posts_custom_column', __NAMESPACE__ . '\load_custom_column_data', 10, 2 );
add_filter( 'manage_cold-storage_posts_columns', __NAMESPACE__ . '\modify_cs_table_columns', 999 );
add_filter( 'post_date_column_status', __NAMESPACE__ . '\modify_storage_status', 20, 4 );
add_filter( 'post_date_column_time', __NAMESPACE__ . '\add_storage_date_info', 20, 4 );
add_filter( 'manage_edit-cold-storage_sortable_columns', __NAMESPACE__ . '\set_cs_sortable_columns' );

/**
 * Generate the data needed for any custom columns.
 *
 * @param  string  $column_name  The name of the column.
 * @param  integer $post_id      The ID of the post in that row.
 *
 * @return mixed
 */
function load_custom_column_data( $column_name, $post_id ) {

	// Get our original post type info.
	$org_type   = get_post_meta( absint( $post_id ), \Norcross\ContentColdStorage\META_PREFIX . 'original_type', true );

	// Begin the big column switch.
	switch ( $column_name ) {

		// Display original post type.
		case 'ccs-original-type' :

			// If we have one, show it nice.
			if ( ! empty( $org_type ) ) {

				// Get our label.
				$type_objct = get_post_type_object( $org_type );

				// And show the label.
				echo esc_html( $type_objct->labels->singular_name );
			}

			// And be done.
			break;

		// Display a possible comment count.
		case 'ccs-comm-count' :

			// Check for post type support first.
			if ( ! empty( $org_type ) && post_type_supports( $org_type, 'comments' ) ) {

				// Now get our counts.
				$get_counts = get_comment_count( absint( $post_id ) );

				// Make it look like the bubble.
				printf(
					'<span class="ccs-table-comment-count">' .
						'<span class="comment-count" aria-hidden="true">%s</span>' .
						'<span class="screen-reader-text">%s</span>' .
					'</span>',
					$get_counts['total_comments'],
					__( 'Total Comments', 'content-cold-storage' )
				);
			}

			// And be done.
			break;

		// End all case breaks.
	}
}

/**
 * Modify the columns for all the post types.
 *
 * @param  array  $columns  Our existing array.
 *
 * @return array            The resulting array.
 */
function modify_cs_table_columns( $columns ) {
	//textarrprint($columns, true);
	// Set a new empty array to return.
	$new_columns = [];

	// Loop and slip ours in.
	foreach ( $columns as $index => $label ) {

		// First add it back.
		$new_columns[ $index ] = $label;

		// Add our original post type after the title.
		if ( 'title' === $index ) {
			$new_columns['ccs-original-type'] = esc_html__( 'Original Type', 'content-cold-storage' );
		}

		// Replace the existing comments with our own.
		if ( 'comments' === $index ) {

			// Remove the existing.
			unset( $new_columns[ $index ] );

			// Add ours, which mimics the markup but drops the linking.
			$new_columns['ccs-comm-count'] = '<span class="vers comment-grey-bubble" title="Comments" aria-hidden="true"></span><span class="screen-reader-text">Comments</span>';
		}
	}

	// Return them.
	return $new_columns;
}

/**
 * Add information to the existing date column.
 *
 * @param string  $ttime        The published time.
 * @param WP_Post $post         Post object.
 * @param string  $column_name  The column name.
 * @param string  $mode         The list display mode ('excerpt' or 'list').
 */
function modify_storage_status( $ttime, $post, $column_name, $mode ) {

	// Don't attempt to do this on other post types.
	if ( empty( $post->post_type ) || 'cold-storage' !== $post->post_type ) {
		return $ttime;
	}

	// Send back our appended string.
	return '<span class="ccs-date-column-title">' . esc_html__( 'Stored', 'content-cold-storage' ) . '</span>';
}

/**
 * Add information to the existing date column.
 *
 * @param string  $ttime        The published time.
 * @param WP_Post $post         Post object.
 * @param string  $column_name  The column name.
 * @param string  $mode         The list display mode ('excerpt' or 'list').
 */
function add_storage_date_info( $ttime, $post, $column_name, $mode ) {

	// Don't attempt to do this on other post types.
	if ( empty( $post->post_type ) || 'cold-storage' !== $post->post_type ) {
		return $ttime;
	}

	// Get our cold storage info.
	$stored = get_post_meta( absint( $post->ID ), \Norcross\ContentColdStorage\META_PREFIX . 'storage_time', true );

	// Start our new markup.
	$build  = '';

	// Assuming we have the time, show it.
	if ( ! empty( $stored ) ) {
		$build .= sprintf(
			/* translators: 1: Post date, 2: Post time. */
			__( '%1$s at %2$s' ),
			/* translators: Post date format. See https://www.php.net/manual/datetime.format.php */
			gmdate( __( 'Y/m/d' ), $stored ),
			/* translators: Post time format. See https://www.php.net/manual/datetime.format.php */
			gmdate( __( 'g:i a' ), $stored )
		);
	} else {
		$build .= '<em>' . esc_html__( 'Unknown', 'content-cold-storage' ) . '</em>';
	}

	// Send back our new markup.
	return $build;
}

/**
 * Modify which columns are and are not sortable.
 *
 * @param  array  $columns  Our existing array.
 *
 * @return array            The resulting array.
 */
function set_cs_sortable_columns( $columns ) {

	// Remove ones we don't want.
	unset( $columns['comments'] );

	// Add one for original type.
	$columns['ccs-original-type'] = [
		'ccs-original-type',
		true, // Initialise with my specified order, false to disregard
		__( 'Original Post Type' ),
		__( 'Table ordered by original post type.' ),
		'desc',
	];

	// Return the resulting array.
	return $columns;
}
