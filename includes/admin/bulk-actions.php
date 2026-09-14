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
add_action( 'init', __NAMESPACE__ . '\add_bulk_action_items' );

/**
 * Add the bulk actions to an applicable dropdown.
 */
function add_bulk_action_items() {

	// Get all the post types we suppor.
	$enabled_types  = AdminConfig\get_enabled_post_types();

	// Loop and add the filter.
	foreach ( $enabled_types as $enabled_type ) {
		add_filter("bulk_actions-edit-{$enabled_type}", __NAMESPACE__ . '\manage_bulk_actions', 30);
	}
}

/**
 * Remove bulk actions from the table views on workflow items.
 *
 * @param  array $actions  Our existing array.
 *
 * @return array           The resulting array.
 */
function manage_bulk_actions( $actions ) {

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

	// Add the author assignment item.
	$actions['ccs-run-bulk'] = __( 'Add to Cold Storage', 'content-cold-storage' );

	// And return the resulting array.
	return $actions;
}
