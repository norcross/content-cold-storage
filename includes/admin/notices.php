<?php
/**
 * Handle the admin notices.
 *
 * @package ContentColdStorage
 */

// Declare our namespace.
namespace Norcross\ContentColdStorage\Admin\AdminNotices;

/**
 * Start our engines.
 */
add_action( 'admin_notices', __NAMESPACE__ . '\display_admin_notices' );

/**
 * Possibly display some of the admin notices.
 *
 * @return void
 */
function display_admin_notices() {

	// Check the result string.
	$check_action   = filter_input( INPUT_GET, 'ccs-action', FILTER_SANITIZE_SPECIAL_CHARS );

	// Only filter these on our results.
	if ( empty( $check_action ) || ! in_array( $check_action, ['run-bulk', 'run-row'], true ) ) { // phpcs:ignore -- there is no nonce needed.
		return;
	}

	// @todo add the possible errors. this only has the success.

	// Handle our bulk response first.
	if ( 'run-bulk' === $check_action ) {

		// Get my result count.
		$get_count  = filter_input( INPUT_GET, 'ccs-bulk-count', FILTER_SANITIZE_NUMBER_INT );

		// Generate our success text.
		/* translators: %d: how many were found */
		$setup_text = sprintf( _n( 'Success! %d items was moved to cold storage.', 'Success! %d items were moved to cold content-cold-storage.', $get_count, 'content-cold-storage' ), $get_count )

		// And display the notice.
		wp_admin_notice(
			'<strong>' . esc_html( $setup_text ) . '</strong>',
			[
				'type'        => 'success',
				'dismissible' => true,
			]
		);

		// And be done.
		return;
	}

	// Generate our success text.
	$setup_text = esc_html__( 'Success! The selected content was moved to cold storage.', 'content-cold-storage' );

	// And display the notice.
	wp_admin_notice(
		'<strong>' . esc_html( $setup_text ) . '</strong>',
		[
			'type'        => 'success',
			'dismissible' => true,
		]
	);

	// And be done.
	return;
}
