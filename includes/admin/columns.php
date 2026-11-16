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

// And any functions or constants on their own.
use const Norcross\ContentColdStorage\META_PREFIX;

/**
 * Start our engines.
 */
add_action( 'manage_posts_custom_column', __NAMESPACE__ . '\load_custom_column_data', 10, 2 );
add_filter( 'manage_cold-storage_posts_columns', __NAMESPACE__ . '\modify_admin_table_columns', 999 );
add_filter( 'disable_months_dropdown', __NAMESPACE__ . '\remove_date_dropdown_filter', 20, 2 );
add_filter( 'post_date_column_status', __NAMESPACE__ . '\modify_storage_status', 20, 4 );
add_filter( 'post_date_column_time', __NAMESPACE__ . '\add_storage_date_info', 20, 4 );
add_filter( 'manage_edit-cold-storage_sortable_columns', __NAMESPACE__ . '\set_sortable_columns' );
add_action( 'restrict_manage_posts', __NAMESPACE__ . '\include_table_filter_items', 5, 2 );

// No bulk actions should exist, so remove them.
add_filter( 'bulk_actions-edit-cold-storage', '__return_false' , 30 );

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
	$original_type  = get_post_meta( absint( $post_id ), META_PREFIX . 'original_type', true );

	// Get all the potential post statuses, as a label set.
	$all_statuses   = get_post_stati( [], 'objects' );

	// Begin the big column switch.
	switch ( $column_name ) {

		// Display original post type.
		case 'ccs-original-type' :

			// If we have one, show it nice.
			if ( ! empty( $original_type ) ) {

				// Get our label.
				$type_objct = get_post_type_object( $original_type );

				// And show the label.
				echo esc_html( $type_objct->labels->singular_name );
			}

			// And be done.
			break;

		// Display original post status.
		case 'ccs-original-status' :

			// Check for the status.
			$get_status = get_post_meta( absint( $post_id ), META_PREFIX . 'original_status', true );

			// Set it.
			$set_status = ! empty( $get_status ) ? $all_statuses[ $get_status ]->label : 'Unknown';

			// And show the label.
			echo esc_html( $set_status );

			// And be done.
			break;

		// Display a possible comment count.
		case 'ccs-comm-count' :

			// Check for post type support first.
			if ( ! empty( $original_type ) && post_type_supports( $original_type, 'comments' ) ) {

				// Now get our counts.
				$get_counts = get_comment_count( absint( $post_id ) );

				// Make it look like the bubble.
				printf(
					'<span class="ccs-table-comment-count">' .
						'<span class="comment-count" aria-hidden="true">%d</span>' .
						'<span class="screen-reader-text">%s</span>' .
					'</span>',
					absint( $get_counts['total_comments'] ),
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
function modify_admin_table_columns( $columns ) {

	// First remove the checkbox column.
	unset( $columns['cb'] );

	// Set a new empty array to return.
	$new_columns = [];

	// Loop and slip ours in.
	foreach ( $columns as $index => $label ) {

		// First add it back.
		$new_columns[ $index ] = $label;

		// Add our original post type and status after the title.
		if ( 'title' === $index ) {
			$new_columns['ccs-original-type'] = esc_html__( 'Original Type', 'content-cold-storage' );
			$new_columns['ccs-original-status'] = esc_html__( 'Original Status', 'content-cold-storage' );
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
 * Remove the date dropdown since that ignores storage date.
 *
 * @param  boolean $disable    Whether to disable the drop-down. Default false.
 * @param  string  $post_type  What post type we're looking at.
 *
 * @return boolean
 */
function remove_date_dropdown_filter( $disable, $post_type ) {
	return 'cold-storage' !== $post_type ? $disable : true;
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
	$stored = get_post_meta( absint( $post->ID ), META_PREFIX . 'storage_time', true );

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
function set_sortable_columns( $columns ) {

	// Remove comments since it's not even
	// applicable to all types shown.
	unset( $columns['comments'] );

	// Add one for original type.
	$columns['ccs-original-type'] = [
		'ccs-original-type',
		false,
		__( 'Original Post Type', 'content-cold-storage' ),
		__( 'Table ordered by original post type.', 'content-cold-storage' ),
		false,
	];

	// Add one for original status.
	$columns['ccs-original-status'] = [
		'ccs-original-status',
		false,
		__( 'Original Post Status', 'content-cold-storage' ),
		__( 'Table ordered by original post status.', 'content-cold-storage' ),
		false,
	];

	// Return the resulting array.
	return $columns;
}

/**
 * Add additional dropdown filter options for cold storage.
 *
 * @param  string $post_type  What post type we're on.
 * @param  string $which      The top or bottom.
 *
 * @return HTML
 */
function include_table_filter_items( $post_type, $which ) {

	// Don't attempt to do this on other post types.
	if ( empty( $post_type ) || 'cold-storage' !== $post_type ) {
		return;
	}

	// And use the native dropdown for users.
	wp_dropdown_users( [
		'show_option_all'  => __( 'All Authors', 'content-cold-storage' ),
		'show'             => 'display_name_with_login',
		'name'             => 'author',
		'selected'         => filter_input( INPUT_GET, 'author', FILTER_SANITIZE_NUMBER_INT ),
		'include_selected' => true,
	] );

	// Use our custom dropdown for post types.
	echo custom_filter_dropdown( [
		'show_option_all' => __( 'All Post Types', 'content-cold-storage' ),
		'name'            => 'cs_original_type',
		'selected'        => filter_input( INPUT_GET, 'cs_original_type', FILTER_SANITIZE_SPECIAL_CHARS ),
		'dataset'         => original_type_dropdown_data(),
	] );

	// Use our custom dropdown for post statuses.
	echo custom_filter_dropdown( [
		'show_option_all' => __( 'All Statuses', 'content-cold-storage' ),
		'name'            => 'cs_original_status',
		'selected'        => filter_input( INPUT_GET, 'cs_original_status', FILTER_SANITIZE_SPECIAL_CHARS ),
		'dataset'         => original_status_dropdown_data(),
	] );

	// No additional items.
}

/**
 * Show a dropdown of one of our custom filters.
 *
 * @param  array  $dropdown_args  Args mimicking the way WP does it.
 *
 * @return string
 */
function custom_filter_dropdown( $dropdown_args = [] ) {

	// Bail without data.
	if ( empty( $dropdown_args['dataset'] ) ) {
		return;
	}

	// Begin our markup.
	$build  = '';

	// Set our select tag.
	$build .= '<select name="' . esc_attr( $dropdown_args['name'] ) . '" id="' . esc_attr( $dropdown_args['name'] ) . '" class="postform cs-admin-table-filter-form">';

		// Include the "all" option.
		$build .= '<option value="">' . esc_html( $dropdown_args['show_option_all'] ) . '</option>';

		// Now loop all the types.
		foreach ( $dropdown_args['dataset'] as $data_key => $data_label ) {

			// And set the option.
			$build .= '<option value="' . esc_attr( $data_key ) . '" ' . selected( $dropdown_args['selected'], $data_key, false ) . '>' . esc_html( $data_label ) . '</option>';
		}

	// Close the select tag.
	$build .= '</select>';

	// Send back the build.
	return $build;
}

/**
 * Build the data for the original post type dropdown filter.
 *
 * @return array
 */
function original_type_dropdown_data() {

	// First get the types.
	$allowed_types  = AdminConfig\get_enabled_post_types();

	// If none (weird, but OK) or only 1 exist, no need for filtering.
	if ( empty( $allowed_types ) || 1 === count( $allowed_types ) ) {
		return [];
	}

	// Set the empty.
	$dataset = [];

	// Now loop and build.
	foreach ( $allowed_types as $post_type ) {

		// Get the post type object.
		$type_objct = get_post_type_object( $post_type );

		// Add our label.
		$dataset[ $post_type ] = $type_objct->labels->name;
	}

	// Send back the data.
	return $dataset;
}

/**
 * Build the data for the original post type dropdown filter.
 *
 * @return array
 */
function original_status_dropdown_data() {

	// First get the statuses.
	$allowed_stats  = AdminConfig\get_enabled_post_statuses();

	// If none (weird, but OK) or only 1 exist, no need for filtering.
	if ( empty( $allowed_stats ) || 1 === count( $allowed_stats ) ) {
		return [];
	}

	// Get all the potential post statuses, as a label set.
	$all_statuses   = get_post_stati( [], 'objects' );

	// Set the empty.
	$dataset = [];

	// Now loop and build.
	foreach ( $allowed_stats as $post_status ) {

		// Add our label.
		$dataset[ $post_status ] = $all_statuses[ $post_status ]->label;
	}

	// Send back the data.
	return $dataset;
}
