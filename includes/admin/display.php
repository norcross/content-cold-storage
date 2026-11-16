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

// And any functions or constants on their own.
use const Norcross\ContentColdStorage\VERS;
use const Norcross\ContentColdStorage\ASSETS_URL;

/**
 * Start our engines.
 */
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\load_cs_admin_assets' );
add_action( 'admin_menu', __NAMESPACE__ . '\modify_admin_menu', 11 );

/**
 * Load our JS and CSS files.
 *
 * @return void
 */
function load_cs_admin_assets() {

	// Set my handle.
	$handle = 'ccs-admin';

	// Set a file suffix structure based on whether or not we want a minified version.
	$file   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? $handle : $handle . '.min';

	// Set a version for whether or not we're debugging.
	$vers   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : VERS;

	// Load the CSS first.
	wp_enqueue_style( $handle, ASSETS_URL . '/css/' . $file . '.css', false, $vers, 'all' );

	// And load our JS.
	wp_enqueue_script( $handle, ASSETS_URL . '/js/' . $file . '.js', [ 'jquery' ], $vers );
	wp_localize_script( $handle, 'ccsLocalArgs',
		[
			'confirmTo'   => __( 'Are you sure you want to move this to cold storage?', 'content-cold-storage' ),
			'confirmFrom' => __( 'Are you sure you want to restore this content?', 'content-cold-storage' ),
		]
	);
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

	// Call the global.
	global $submenu;

	// Begin looping the submenu, since we can't remove the term pages another way.
	foreach ( $submenu as $parent_menu => $menu_args ) {

		// Only target our menu.
		if ( 'edit.php?post_type=cold-storage' !== $parent_menu ) {
			continue;
		}

		// Now loop the menu args to remove all but what we want.
		foreach ( $menu_args as $sub_key => $sub_args ) {

			// 5 is our important one, so leave that there.
			if ( 5 === absint( $sub_key ) ) {
				continue;
			}

			// Remove whatever this is, which is likely a taxonomy.
			unset( $submenu[ $parent_menu ][ $sub_key ] );
		}
	}

	// Nothing left inside this.
}
