<?php
/**
 * Handle processing the various post related action requests.
 *
 * @package ContentColdStorage
 */

// Declare our namespace.
namespace Norcross\ContentColdStorage\Admin\Process;

// Set our aliases.
use Norcross\ContentColdStorage\Admin\Config as AdminConfig;

// And pull in any other namespaces.
use WP_Error;

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
	process_single_storage( $get_post_id, 'row-action' );

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
		process_single_storage( $post_id, 'bulk-action' );
	}

	// Set the return args to include.
	$rtn_args = [
		'ccs-success'    => true,
		'ccs-action'     => 'run-bulk',
		'ccs-bulk-count' => count( $post_ids ),
	];

	// Add our own query args so we can do a notice.
	$sendback = add_query_arg( 'ccs-bulk-count', $rtn_args, $sendback );

	// And return the link.
	return $sendback;
}

/**
 * Handle processing a single post ID to cold storage.
 *
 * @param  integer $post_id  The ID being moved.
 * @param  string  $source   Where the request was sourced from.
 *
 * @return mixed
 */
function process_single_storage( $post_id = 0, $source = '' ) {

	// Don't allow this for users who can't.
	if ( ! current_user_can( AdminConfig\get_required_user_cap() ) ) {
		return new WP_Error( 'bad_user_cap', __( 'This user does not have the required permissions to run this process.', 'content-cold-storage' ) );
	}

	// Do our check for excluded IDs.
	if ( in_array( absint( $post_id ), AdminConfig\get_excluded_post_ids(), true ) ) {
		return new WP_Error( 'invalid_post_id', __( 'This post ID is not approved for cold storage.', 'content-cold-storage' ) );
	}

	// Get the entire post object.
	$setup_post = get_post( absint( $post_id ) );

	// Only do this on post types we support.
	if ( ! in_array( $setup_post->post_type, AdminConfig\get_enabled_post_types(), true ) ) {
		return new WP_Error( 'invalid_post_type', __( 'The post type for this ID is not approved for cold storage.', 'content-cold-storage' ) );
	}

	// Only do this on post statuses we support.
	if ( ! in_array( $setup_post->post_status, AdminConfig\get_enabled_post_statuses(), true ) ) {
		return new WP_Error( 'invalid_post_status', __( 'The post status for this ID is not approved for cold storage.', 'content-cold-storage' ) );
	}

	// Include an action before making the change.
	do_action( \Norcross\ContentColdStorage\META_PREFIX . 'before_cold_storage', $post_id, $setup_post, $source );

	// Set the args for updating the post.
	$setup_args = [
		'ID'        => absint( $post_id ),
		'post_type' => 'cold-storage',
	];

	// Allow a filter of the setup args here.
	$setup_args = apply_filters( \Norcross\ContentColdStorage\ACTION_PREFIX . 'pre_convert_args', $setup_args, $setup_post, $source );

	// Run the update.
	$maybe_move = wp_update_post( $setup_args, false, false );

	// Bail if it failed.
	if ( empty( $maybe_move ) ) {
		return new WP_Error( 'post_update_error', __( 'This post was not updated successfully.', 'content-cold-storage' ) );
	}

	// Set the array of audit data.
	$audit_args = [
		'user'   => get_current_user_id(),
		'source' => $source,
		'time'   => time(),
		'type'   => $setup_post->post_type,
	];

	// Allow a filter of the audit args here.
	$audit_args = apply_filters( \Norcross\ContentColdStorage\ACTION_PREFIX . 'audit_args', $audit_args, $setup_post );

	// Update the metadata.
	update_post_meta( absint( $post_id ), \Norcross\ContentColdStorage\META_PREFIX . 'audit_record', $audit_args );

	// Include an action after making the change.
	do_action( \Norcross\ContentColdStorage\META_PREFIX . 'after_cold_storage', $post_id, $source );

	// Return true since we made it.
	return true;
}
