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

// And any functions or constants on their own.
use const Norcross\ContentColdStorage\META_PREFIX;
use const Norcross\ContentColdStorage\ACTION_PREFIX;

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
	do_action( ACTION_PREFIX . 'before_cold_storage', $post_id, $setup_post, $source );

	// Set the args for updating the post.
	// Ensuring it is set to publish.
	$setup_args = [
		'ID'          => absint( $post_id ),
		'post_type'   => 'cold-storage',
		'post_name'   => 'cs-' . strtotime( $setup_post->post_date ),
		'post_status' => 'publish',
		'post_parent' => 0,
	];

	// Allow a filter of the setup args here.
	$setup_args = apply_filters( ACTION_PREFIX . 'pre_convert_args', $setup_args, $setup_post, $source );

	// Run the update, but don't fire the after hooks or use the whole error return.
	$maybe_move = wp_update_post( $setup_args, false, false );

	// Bail if it failed.
	if ( empty( $maybe_move ) ) {
		return new WP_Error( 'post_update_error', __( 'This item was not updated successfully.', 'content-cold-storage' ) );
	}

	// Set our metadata.
	set_storage_metadata( $setup_post, $source );

	// Include an action after making the change.
	do_action( ACTION_PREFIX . 'after_cold_storage', $post_id, $setup_post, $source );

	// Return true since we made it.
	return true;
}

/**
 * Handle processing a single post ID cold storage.
 *
 * @param  integer $post_id  The ID being moved.
 * @param  string  $source   Where the request was sourced from.
 *
 * @return mixed
 */
function process_single_restore( $post_id = 0, $source = '' ) {

	// Don't allow a restore if it is disabled completely.
	if ( false !== AdminConfig\check_restore_capability() ) {
		return new WP_Error( 'restricted_process', __( 'The restore functionality has been disabled.', 'content-cold-storage' ) );
	}

	// Don't allow this for users who can't.
	if ( ! current_user_can( AdminConfig\get_required_user_cap() ) ) {
		return new WP_Error( 'bad_user_cap', __( 'This user does not have the required permissions to run this process.', 'content-cold-storage' ) );
	}

	// Get the entire post object.
	$setup_post = get_post( absint( $post_id ) );

	// Only run this on items currently in cold storage.
	if ( 'cold-storage' !== $setup_post->post_type ) {
		return new WP_Error( 'invalid_post_type', __( 'Only items in cold storage can be restored.', 'content-cold-storage' ) );
	}

	// Include an action before making the change.
	do_action( ACTION_PREFIX . 'before_cold_restore', $post_id, $setup_post, $source );

	// Now get our meta.
	$get_audit_meta = get_post_meta( absint( $setup_post->ID ), META_PREFIX . 'audit_record', true );

	// Ensure our slug is still unique.
	$ensure_slug    = wp_unique_post_slug(
		$get_audit_meta['slug'],
		absint( $setup_post->ID ),
		$get_audit_meta['status'],
		$get_audit_meta['type'],
		absint( $get_audit_meta['parent'] )
	);

	// Set the args for updating the post.
	$setup_args = [
		'ID'          => absint( $post_id ),
		'post_type'   => sanitize_text_field( $get_audit_meta['type'] ),
		'post_name'   => $ensure_slug,
		'post_status' => sanitize_text_field( $get_audit_meta['status'] ),
	];

	// Allow a filter of the setup args here.
	$setup_args = apply_filters( ACTION_PREFIX . 'pre_restore_args', $setup_args, $setup_post, $source );

	// Run the update, but don't fire the after hooks or use the whole error return.
	$maybe_move = wp_update_post( $setup_args, false, false );

	// Bail if it failed.
	if ( empty( $maybe_move ) ) {
		return new WP_Error( 'post_restore_error', __( 'This item was not restored successfully.', 'content-cold-storage' ) );
	}

	// Set our metadata.
	set_restore_metadata( $setup_post, $get_audit_meta, $source );

	// Include an action after making the change.
	do_action( ACTION_PREFIX . 'after_cold_restore', $post_id, $setup_post, $get_audit_meta, $source );

	// Return true since we made it.
	return true;
}

/**
 * Set and store all the metadata for moving to cold storage.
 *
 * @param  WP_Post $setup_post  The original post object before we made changes.
 * @param  string  $source      What the source of the move was.
 *
 * @return void
 */
function set_storage_metadata( $setup_post, $source ) {

	// Delete any backup we might have.
	delete_post_meta( absint( $setup_post->ID ), META_PREFIX . 'audit_backup' );

	// Set the array of audit data.
	$audit_args = [
		'user'   => get_current_user_id(),
		'source' => $source,
		'time'   => time(),
		'type'   => $setup_post->post_type,
		'slug'   => $setup_post->post_name,
		'status' => $setup_post->post_status,
		'parent' => $setup_post->post_parent,
	];

	// Allow a filter of the audit args here.
	$audit_args = apply_filters( ACTION_PREFIX . 'audit_storage_args', $audit_args, $setup_post );

	// Bail if we somehow cleared this out.
	if ( empty( $audit_args ) ) {
		return;
	}

	// Update the whole array.
	update_post_meta( absint( $setup_post->ID ), META_PREFIX . 'audit_record', $audit_args );

	// Set the specifics for sorting and filtering.
	update_post_meta( absint( $setup_post->ID ), META_PREFIX . 'storage_time', $audit_args['time'] );
	update_post_meta( absint( $setup_post->ID ), META_PREFIX . 'original_type', $audit_args['type'] );
	update_post_meta( absint( $setup_post->ID ), META_PREFIX . 'original_status', $audit_args['status'] );
}

/**
 * Set and store all the metadata for moving from cold storage.
 *
 * @param  WP_Post $setup_post  The original post object before we made changes.
 * @param  array   $audit_meta  The audit metadata from when it was moved.
 * @param  string  $source      What the source of the move was.
 *
 * @return void
 */
function set_restore_metadata( $setup_post, $audit_meta, $source ) {

	// First move the current over to the backup.
	update_post_meta( absint( $setup_post->ID ), META_PREFIX . 'audit_backup', $audit_meta );

	// Set the array of audit data.
	$audit_args = [
		'user'   => get_current_user_id(),
		'source' => $source,
		'time'   => time(),
	];

	// Allow a filter of the audit args here.
	$audit_args = apply_filters( ACTION_PREFIX . 'audit_restore_args', $audit_args, $setup_post );

	// Bail if we somehow cleared this out.
	if ( empty( $audit_args ) ) {
		return;
	}

	// Update the whole array.
	update_post_meta( absint( $setup_post->ID ), META_PREFIX . 'audit_record', $audit_args );

	// Delete the sorting related.
	delete_post_meta( absint( $setup_post->ID ), META_PREFIX . 'storage_time' );
	delete_post_meta( absint( $setup_post->ID ), META_PREFIX . 'original_type' );
	delete_post_meta( absint( $setup_post->ID ), META_PREFIX . 'original_status' );
}
