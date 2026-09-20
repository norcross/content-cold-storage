<?php
/**
 * Handle the various triggers we have for cold storage.
 *
 * @package ContentColdStorage
 */

// Declare our namespace.
namespace Norcross\ContentColdStorage\Admin\Triggers;

// Set our aliases.
use Norcross\ContentColdStorage\Admin\Config as AdminConfig;

/**
 * Start our engines.
 */
add_action( 'admin_init', __NAMESPACE__ . '\run_enabled_row_processing' );
add_action( 'admin_init', __NAMESPACE__ . '\manage_enabled_bulk_processing' );

/**
 * See if there is any cold store processes to run.
 *
 * @return void
 */
function run_enabled_row_processing() {

	// This never runs on front end.
	if ( ! is_admin() ) {
		return;
	}

	// See if we have a trigger.
	$check_trigger  = filter_input( INPUT_GET, 'ccs-action', FILTER_SANITIZE_SPECIAL_CHARS );

	// Do nothing without it.
	if ( empty( $check_trigger ) || 'ccs-single-run' !== $check_trigger ) {
		return;
	}

	// Grab the nonce value.
	$confirm_nonce  = filter_input( INPUT_GET, 'ccs-nonce', FILTER_SANITIZE_SPECIAL_CHARS );

	// Handle the nonce check and die if there is a failure.
	if ( empty( $confirm_nonce ) || ! wp_verify_nonce( $confirm_nonce, \Norcross\ContentColdStorage\NONCE_PREFIX . 'cold_store_row' ) ) {
		wp_die( esc_html__( 'There was an error validating the nonce.', 'content-cold-storage' ), esc_html__( 'Content Cold Storage', 'content-cold-storage' ), [ 'back_link' => true ] );
	}

	// Grab the passed post ID.
	$get_post_id    = filter_input( INPUT_GET, 'ccs-post-id', FILTER_SANITIZE_NUMBER_INT );

	// Bail without a post ID.
	if ( empty( $get_post_id ) ) {
		wp_die( esc_html__( 'A post ID is required to run a cold storage request.', 'content-cold-storage' ), esc_html__( 'Content Cold Storage', 'content-cold-storage' ), [ 'back_link' => true ] );
	}

	// Now run it.
	// @todo handle the error checking.
	\Norcross\ContentColdStorage\Admin\Process\process_single_storage( $get_post_id, 'row-action' );

	// Now set up the link that'll redirect.
	$setup_args = [
		'post_type'   => filter_input( INPUT_GET, 'ccs-post-type', FILTER_SANITIZE_SPECIAL_CHARS ),
		'ccs-success' => true,
		'ccs-action'  => 'run-row',
	];

	// Get the link itself.
	$setup_link  = add_query_arg( $setup_args, admin_url( '/edit.php' ) );

	// Do the redirect.
	wp_safe_redirect( $setup_link );
	exit;
}

/**
 * Add the bulk action processing to applicable post types.
 *
 * @return void
 */
function manage_enabled_bulk_processing() {

	// Get all the post types we suppor.
	$enabled_types  = AdminConfig\get_enabled_post_types();

	// Loop and add the filter.
	foreach ( $enabled_types as $enabled_type ) {
		add_filter( "handle_bulk_actions-edit-{$enabled_type}", __NAMESPACE__ . '\run_enabled_bulk_actions', 30, 3 );
	}
}

/**
 * See if we need to run any of our bulk actions.
 *
 * @param  string $sendback  The redirect URL
 * @param  string $action    The action being taken.
 * @param  array  $post_ids  Which IDs to apply the action to.
 *
 * @return void
 */
function run_enabled_bulk_actions( $sendback, $action, $post_ids ) {

	// Don't show this for users who can't.
	if ( ! current_user_can( AdminConfig\get_required_user_cap() ) ) {
		return $sendback;
	}

	// Grab the post status.
	$ison_table = filter_input( INPUT_GET, 'post_status', FILTER_SANITIZE_SPECIAL_CHARS );

	// Don't attempt to modify things in the trash.
	if ( ! empty( $ison_table ) && 'trash' === $ison_table ) {
		return $sendback;
	}

	// Bail if it's a different bulk action.
	if ( 'ccs-bulk-run' !== $action ) {
		return $sendback;
	}

	// Now loop and process each one.
	// @todo include some error handling.
	foreach ( $post_ids as $post_id ) {
		\Norcross\ContentColdStorage\Admin\Process\process_single_storage( $post_id, 'bulk-action' );
	}

	// Set the return args to include.
	$rtn_args = [
		'ccs-success'    => true,
		'ccs-action'     => 'run-bulk',
		'ccs-bulk-count' => count( $post_ids ),
	];

	// Add our own query args so we can do a notice.
	$sendback = add_query_arg( $rtn_args, $sendback );

	// And return the link.
	return $sendback;
}
