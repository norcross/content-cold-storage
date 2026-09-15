<?php
/**
 * Handle our admin side config.
 *
 * @package ContentColdStorage
 */

// Declare our namespace.
namespace Norcross\ContentColdStorage\Admin\Config;

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
 * Set the post types that this can be enabled on. Defaults to posts and pages.
 *
 * @return array  An array of all post types are allowed.
 */
function get_enabled_post_types() {
	return apply_filters( \Norcross\ContentColdStorage\ACTION_PREFIX . 'enabled_post_types', ['post','page'] );
}

/**
 * Set the post statuses that this can be enabled on. Defaults to draft, published, and scheduled.
 *
 * @return array  The array of statuses.
 */
function get_enabled_post_statuses() {
	return apply_filters( \Norcross\ContentColdStorage\ACTION_PREFIX . 'enabled_post_statuses', ['draft','publish','future','pending'] );
}

/**
 * Set the required user cap.
 *
 * @return string
 */
function get_required_user_cap() {
	return apply_filters( \Norcross\ContentColdStorage\ACTION_PREFIX . 'required_user_cap', 'edit_others_posts' );
}

/**
 * Set any IDs that should not be allowed to move to cold storage. Includes front page and blog page IDs.
 *
 * @return array
 */
function get_excluded_post_ids() {

	// Set our empty.
	$set_excluded   = [];

	// Set an array of the options we wanna check.
	$option_array   = [
		'page_on_front',
		'page_for_posts',
		'wp_page_for_privacy_policy',
	];

	// Loop, and if it exists, exclude it.
	foreach ( $option_array as $option_key ) {

		// Check for the value.
		$has_value  = get_option( $option_key, 0 );

		// Add it if we have it.
		if ( ! empty( $has_value ) ) {
			$set_excluded[] = $has_value;
		}
	}

	// Set it get filtered first.
	$set_excluded   = apply_filters( \Norcross\ContentColdStorage\ACTION_PREFIX . 'excluded_post_ids', $set_excluded );

	// Now send it back with all the integers.
	return array_map( 'absint', $set_excluded );
}
