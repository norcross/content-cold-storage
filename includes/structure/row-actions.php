<?php
/**
 * Handle our post row actions.
 */

// Declare our namespace.
namespace Norcross\ContentColdStorage\Structure\RowActions;

// Set our aliases.
use Norcross\ContentColdStorage\Admin\Config as AdminConfig;

/**
 * Start our engines.
 */
add_action( 'post_row_actions', __NAMESPACE__ . '\add_cold_storage_row_actions', 50, 2);
add_action( 'page_row_actions', __NAMESPACE__ . '\add_cold_storage_row_actions', 50, 2);

/**
 * Include the post and page row actions to set up a workflow clone.
 *
 * @param  array   $actions  The current array of actions.
 * @param  WP_Post $post     The WP_Post object.
 *
 * @return array
 */
function add_cold_storage_row_actions( array $actions, \WP_Post $post ) {

	// Only do this on post types we support.
	if ( empty( $post->post_type ) || ! in_array( $post->post_type, AdminConfig\get_allowed_post_types(), true ) ) {
		return $actions;
	}

	// Only do this on post statuses we support.
	if ( empty( $post->post_status ) || ! in_array( $post->post_status, AdminConfig\get_allowed_post_statuses(), true ) ) {
		return $actions;
	}

	// See if we are on the trash tab, which we don't wanna deal with.
	$ison_table = filter_input( INPUT_GET, 'post_status', FILTER_SANITIZE_SPECIAL_CHARS );

	// Don't attempt to modify things in the trash.
	if ( ! empty( $ison_table ) && 'trash' === $ison_table ) {
		return $actions;
	}

	// Define our args for the link.
	$setup_args = [
		'ccs-workflow' => 'yes',
		'ccs-action'   => 'cold-store-row',
		'ccs-post-id'  => $post->ID,
		'ccs-nonce'    => wp_create_nonce( \Norcross\ContentColdStorage\NONCE_PREFIX . 'cold_store_row' ),
	];

	// Build our link.
	$setup_link = add_query_arg( $setup_args, admin_url( '/' ) );

	// And include our action.
	$actions['ccs-add-cold'] = sprintf(
		'<a class="ccs-action-row-link ccs-cold-store-row-link" href="%1$s" rel="bookmark" aria-label="%2$s">%3$s</a>',
		esc_url( $setup_link ),
		esc_attr__( 'Move this content to the cold storage location.', 'content-cold-storage' ),
		esc_html__( 'Cold Storage', 'content-cold-storage' )
	);

	// Return the array.
	return $actions;
}
