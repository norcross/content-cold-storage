<?php
/**
 * Handle our admin side setup.
 *
 * @package ContentColdStorage
 */

// Declare our namespace.
namespace Norcross\ContentColdStorage\AdminSetup;

/**
 * Start our engines.
 */
add_filter( 'plugins_api', __NAMESPACE__ . '\prevent_plugin_update_check', 100, 3 );
add_filter( 'removable_query_args', __NAMESPACE__ . '\admin_removable_args' );

/**
 * Prevent the WP plugin update check from looking at this one.
 *
 * @param  false|object|array $result  The result object or array. Default false.
 * @param  string             $action  The type of information being requested from the Plugin Installation API.
 * @param  object             $args    Plugin API arguments.
 *
 * @return boolean
 */
function prevent_plugin_update_check( $result, $action, $args ) {

	// Return the initial value if this isn't us.
	if ( empty( $args ) || ! is_object( $args ) || 'content-cold-storage' !== $args->slug ) {
		return $result;
	}

	// Create a new object.
	$empty_result = new \stdClass();

	// Set the plugin name because it wants that.
	$empty_result->name = __( 'Content Cold Storage', 'content-cold-storage' );

	// Return our new phantom.
	return $empty_result;
}

/**
 * Add our custom strings to the vars.
 *
 * @param  array $args  The existing array of args.
 *
 * @return array $args  The modified array of args.
 */
function admin_removable_args( $args ) {

	// Set an array of the args we wanna exclude.
	$remove = [
		'ccs-eventual-arg-here',
	];

	// Include my new args and return.
	return wp_parse_args( $remove, $args );
}

/**
 * Set the post types that this can be enabled on.
 * Defaults to all.
 *
 * @return array  An empty array assumed all post types are allowed.
 */
function get_allowed_post_types() {
	return apply_filters( \Norcross\ContentColdStorage\ACTION_PREFIX . 'allowed_post_types', [] );
}

/**
 * Set the post statuses that this can be enabled on.
 * Defaults to draft, published, and scheduled.
 *
 * @return array  The array of statuses.
 */
function get_allowed_post_statuses() {
	return apply_filters( \Norcross\ContentColdStorage\ACTION_PREFIX . 'allowed_post_statuses', [] );
}

/**
 * Set the default user permisson.
 *
 * @return string
 */
function get_user_cap_for_run() {
	return apply_filters( \Norcross\ContentColdStorage\ACTION_PREFIX . 'allowed_user_perm', 'edit_others_posts' );
}
