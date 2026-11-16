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
add_action( 'pre_get_posts', __NAMESPACE__ . '\manage_admin_table_sort', 10 );

/**
 * Add some CSS to make our row action
 * stand out a bit and condense the column.
 *
 * @return void
 */
function load_cs_admin_css() {

	// Set my CSS up.
	$setup_css  = '

		.column-ccs-comm-count {
			width: 60px;
		}

		.column-ccs-comm-count .ccs-table-comment-count {
			color: #fff;
			background-color: #646970;
			padding: 3px;
			border-radius: 50%;
			height: 20px;
			width: 20px;
			display: inline-block;
			text-align: center;
			font-weight: 700;
			margin-left: -6px;
		}

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

/**
 * Handle the sorting of the cold storage table.
 *
 * @param  WP_Query $query  The query object.
 *
 * @return void
 */
function manage_admin_table_sort( $query ) {

	// Run this on admin only.
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	// Only do this on cold storage.
	if ( empty( $query->get( 'post_type' ) ) || 'cold-storage' !== sanitize_text_field( $query->get( 'post_type' ) ) ) {
		return;
	}

	// Check each one we know.
	switch ( $query->get( 'orderby' ) ) {

		// Our original post type.
		case 'ccs-original-type' :
			$query->set( 'meta_key', \Norcross\ContentColdStorage\META_PREFIX . 'original_type' );
			$query->set( 'orderby', 'meta_value' );
			break;

		// Hijacking the date sort to use our storage time.
		case 'date' :
			$query->set( 'meta_key', \Norcross\ContentColdStorage\META_PREFIX . 'storage_time' );
			$query->set( 'orderby', 'meta_value_num' );
			break;

		// Doing the same as above, since we want date as the default.
		default :
			$query->set( 'meta_key', \Norcross\ContentColdStorage\META_PREFIX . 'storage_time' );
			$query->set( 'orderby', 'meta_value_num' );
			break;
	}

	// Nothing left to modify.
}
