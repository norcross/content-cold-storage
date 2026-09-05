<?php
/**
 * Load our menu items.
 *
 * @package ContentColdStorage
 */

// Declare our namespace.
namespace Norcross\ContentColdStorage\Admin\MenuItems;

// Set our aliases.
use Norcross\ContentColdStorage\Admin\Config as AdminConfig;

/**
 * Start our engines.
 */
add_action( 'admin_menu', __NAMESPACE__ . '\modify_admin_menu', 11 );

/**
 * Make sure the menu option isn't there for people
 *
 * @return void
 */
function modify_admin_menu() {

	// If the current user doesn't have the cap, remove it.
	if ( ! current_user_can( AdminConfig\get_user_cap_for_run() ) ) {
		remove_menu_page( 'edit.php?post_type=cold-storage' );
	}

	// Nothing left inside this.
}
