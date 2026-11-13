<?php
/**
 * Load our admin display and menu items.
 *
 * @package ContentColdStorage
 */

// Declare our namespace.
namespace Norcross\ContentColdStorage\Admin\Display;

// Set our aliases.
use Norcross\ContentColdStorage\Admin\Config as AdminConfig;

/**
 * Start our engines.
 */
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\load_cs_admin_css' );
add_action( 'admin_menu', __NAMESPACE__ . '\modify_admin_menu', 11 );

/**
 * Add some CSS to make our row action
 * stand out a bit and condense the column.
 *
 * @return void
 */
function load_cs_admin_css() {

	// Set my CSS up.
	$setup_css  = '
		a.ccs-action-row-link {
			color: #d14d9e;
		}
		a.ccs-action-row-link:hover,
		a.ccs-action-row-link:focus {
			color: #ab3c80;
		}
	';

	// And add the CSS.
	wp_add_inline_style( 'common', $setup_css );
}

/**
 * Make sure the menu option isn't there for people
 *
 * @return void
 */
function modify_admin_menu() {

	// If the current user doesn't have the cap, remove it.
	if ( ! current_user_can( AdminConfig\get_required_user_cap() ) ) {
		remove_menu_page( 'edit.php?post_type=cold-storage' );
	}

	// Nothing left inside this.
}
