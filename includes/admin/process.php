<?php
/**
 * Handle processing the various post related action requests.
 *
 * @package ContentColdStorage
 */

// Declare our namespace.
namespace Norcross\ContentColdStorage\Admin\Process;

/**
 * Start our engines.
 */
//add_action( 'admin_init', __NAMESPACE__ . '\maybe_run_cold_storage' );
//add_filter( 'handle_bulk_actions-edit-post', __NAMESPACE__ . '\maybe_run_cold_storage', 10, 3 );

/**
 * Check and handle a custom bulk action for posts.
 * Replace 'edit-post' with your specific screen ID (e.g., 'edit-page', 'users', 'edit-comments').
 */


function detect_and_process_my_bulk_action( $redirect_to, $action_name, $item_ids ) {
    // 1. Check if the active action matches your target bulk action
    if ( $action_name !== 'my_custom_bulk_action_key' ) {
        return $redirect_to; // Exit early if it's a different bulk action
    }

    // 2. Perform your logic on the selected bulk items
    foreach ( $item_ids as $post_id ) {
        // Your logic here (e.g., wp_update_post, delete, custom logging)
    }

    // 3. Pass a tracking parameter back to the redirect URL to display an admin notice later
    $redirect_to = add_query_arg( 'bulk_action_processed', count( $item_ids ), $redirect_to );

    return $redirect_to;
}


/**
 * See if there is any cold store processes to run.
 *
 * @return void
 */
function maybe_run_cold_storage() {

	// This never runs on front end.
	if ( ! is_admin() ) {
		return;
	}

	preprint($_GET );
	preprint($_POST, true);

	/*
	// See if we have a trigger.
	$check_trigger  = filter_input( INPUT_GET, 'scc-run-check', FILTER_SANITIZE_SPECIAL_CHARS );

	// Do nothing without it.
	if ( empty( $check_trigger ) || 'yes' !== $check_trigger ) {
		return;
	}

	// Grab the nonce value.
	$confirm_nonce  = filter_input( INPUT_GET, 'scc-run-nonce', FILTER_SANITIZE_SPECIAL_CHARS );

	// Handle the nonce check and die if there is a failure.
	if ( empty( $confirm_nonce ) || ! wp_verify_nonce( $confirm_nonce, \ScheduledContentChecks\NONCE_PREFIX . 'manual_run' ) ) {
		wp_die( esc_html__( 'There was an error validating the nonce.', 'scheduled-content-checks' ), esc_html__( 'Scheduled Content Checks', 'scheduled-content-checks' ), [ 'back_link' => true ] );
	}

	// Bail if current user doesn't have cap.
	if ( ! current_user_can( \ScheduledContentChecks\AdminSetup\get_user_cap_for_run() ) ) {
		wp_die( esc_html__( 'Sorry, you are not authorized to perform this action.', 'scheduled-content-checks' ), esc_html__( 'Scheduled Content Checks', 'scheduled-content-checks' ), [ 'back_link' => true ] );
	}

	// See if we missed any.
	$missed_ids = \ScheduledContentChecks\Queries\get_missed_scheduled_content();

	// If we have none, redirect with that.
	if ( empty( $missed_ids ) ) {

		// Now set up the link that'll redirect.
		$setup_args = [
			'scc-run-result' => 'empty',
			'scc-run-count'  => 0,
		];

		// Get the link itself.
		$setup_link  = add_query_arg( $setup_args, admin_url( '/' ) );

		// Do the redirect.
		wp_safe_redirect( $setup_link );
		exit;
	}

	// Now loop and publish any missing.
	foreach ( $missed_ids as $post_id ) {
		wp_publish_post( $post_id );
	}

	// Now set up the link that'll redirect.
	$setup_args = [
		'scc-run-result' => 'success',
		'scc-run-count'  => count( $missed_ids ),
	];

	// Get the link itself.
	$setup_link  = add_query_arg( $setup_args, admin_url( '/' ) );

	// Do the redirect.
	wp_safe_redirect( $setup_link );
	exit;
	*/
}
