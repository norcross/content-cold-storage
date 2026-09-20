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
		'ID'             => absint( $post_id ),
		'post_type'      => 'cold-storage',
		'post_name'      => 'cs-' . strtotime( $setup_post->post_date ),
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
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
		'slug'   => $setup_post->post_name,
	];

	// Allow a filter of the audit args here.
	$audit_args = apply_filters( \Norcross\ContentColdStorage\ACTION_PREFIX . 'audit_args', $audit_args, $setup_post );

	// Update the metadata, assuming we didn't clear it out.
	if ( ! empty( $audit_args ) ) {
		update_post_meta( absint( $post_id ), \Norcross\ContentColdStorage\META_PREFIX . 'audit_record', $audit_args );
	}

	// Include an action after making the change.
	do_action( \Norcross\ContentColdStorage\META_PREFIX . 'after_cold_storage', $post_id, $source );

	// Return true since we made it.
	return true;
}
