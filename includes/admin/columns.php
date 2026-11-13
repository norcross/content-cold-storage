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

/**
 * Generate the data needed for any custom columns.
 *
 * @param  string  $column_name  The name of the column.
 * @param  integer $post_id      The ID of the post in that row.
 *
 * @return mixed
 */
function load_custom_column_data( $column_name, $post_id ) {

	// Begin the big column switch.
	switch ( $column_name ) {

		// Handle a display.
		case 'some-weird-things' :

			// Eventually put it here.

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
	// preprint($columns, true);

	$new_columns = [];

	// Loop and slip ours in.
	foreach ( $columns as $index => $label ) {

		// First add it back.
		$new_columns[ $index ] = $label;

		// We want this after the author.
		if ( 'author' !== $index ) {
			continue;
		}

		// Add our new original type column.
		$new_columns['ccs-original-type'] = esc_html__( 'Original Type', 'content-cold-storage' );
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
	$audit  = get_post_meta( absint( $post->ID ), \Norcross\ContentColdStorage\META_PREFIX . 'audit_record', true );

	// Start our new markup.
	$build  = '';

	// Assuming we have the time, show it.
	if ( ! empty( $audit['time'] ) ) {
		$build .= sprintf(
			/* translators: 1: Post date, 2: Post time. */
			__( '%1$s at %2$s' ),
			/* translators: Post date format. See https://www.php.net/manual/datetime.format.php */
			gmdate( __( 'Y/m/d' ), $audit['time'] ),
			/* translators: Post time format. See https://www.php.net/manual/datetime.format.php */
			gmdate( __( 'g:i a' ), $audit['time'] )
		);
	} else {
		$build .= '<em>' . esc_html__( 'Unknown', 'content-cold-storage' ) . '</em>';
	}

	// Send back our new markup.
	return $build;
}
