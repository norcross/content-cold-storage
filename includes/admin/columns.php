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
add_filter( 'post_date_column_status', __NAMESPACE__ . '\modify_storage_status', 20, 4 );
add_filter( 'post_date_column_time', __NAMESPACE__ . '\add_storage_date_info', 20, 4 );

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
