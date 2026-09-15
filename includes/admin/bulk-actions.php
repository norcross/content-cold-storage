<?php
/**
 * Handle our admin bulk actions.
 *
 * @package ContentColdStorage
 */

// Declare our namespace.
namespace Norcross\ContentColdStorage\Admin\BulkActions;

// Set our aliases.
use Norcross\ContentColdStorage\Admin\Config as AdminConfig;

/**
 * Start our engines.
 */
add_action( 'init', __NAMESPACE__ . '\manage_enabled_bulk_action_items' );
add_filter( 'bulk_actions-edit-cold-storage', __NAMESPACE__ . '\add_revert_bulk_action', 30 );

/**
 * Add the bulk actions to an applicable dropdown.
 */
function manage_enabled_bulk_action_items() {

	// Get all the post types we suppor.
	$enabled_types  = AdminConfig\get_enabled_post_types();

	// Loop and add the filter.
	foreach ( $enabled_types as $enabled_type ) {
		add_filter( "bulk_actions-edit-{$enabled_type}", __NAMESPACE__ . '\add_enabled_bulk_action', 30 );
	}
}

/**
 * Add our option to the dropdown for enabled types.
 *
 * @param  array $actions  Our existing array.
 *
 * @return array           The resulting array.
 */
function add_enabled_bulk_action( $actions ) {

	// Don't show this for users who can't.
	if ( ! current_user_can( AdminConfig\get_required_user_cap() ) ) {
		return $actions;
	}

	// Grab the post status.
	$ison_table = filter_input( INPUT_GET, 'post_status', FILTER_SANITIZE_SPECIAL_CHARS );

	// Don't attempt to modify things in the trash.
	if ( ! empty( $ison_table ) && 'trash' === $ison_table ) {
		return $actions;
	}

	// Add our action.
	$actions['ccs-run-bulk'] = __( 'Move to Cold Storage', 'content-cold-storage' );

	// And return the resulting array.
	return $actions;
}

/**
 * Add our option to the dropdown to revert.
 *
 * @param  array $actions  Our existing array.
 *
 * @return array           The resulting array.
 */
function add_revert_bulk_action( $actions ) {

	// Don't show this for users who can't.
	if ( ! current_user_can( AdminConfig\get_required_user_cap() ) ) {
		return $actions;
	}

	// Grab the post status.
	$ison_table = filter_input( INPUT_GET, 'post_status', FILTER_SANITIZE_SPECIAL_CHARS );

	// Don't attempt to modify things in the trash.
	if ( ! empty( $ison_table ) && 'trash' === $ison_table ) {
		return $actions;
	}

	// Add our action.
	$actions['ccs-revert-bulk'] = __( 'Restore to Original', 'content-cold-storage' );

	// And return the resulting array.
	return $actions;
}
