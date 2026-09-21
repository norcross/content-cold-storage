<?php
/**
 * Setting up any custom post types.
 *
 * @package ContentColdStorage
 */

// Declare our namespace.
namespace Norcross\ContentColdStorage\Structure\PostTypes;

// Set our aliases.
use Norcross\ContentColdStorage\Admin\Config as AdminConfig;

/**
 * Start our engines.
 */
add_action( 'init', __NAMESPACE__ . '\register_cold_storage_post_type' );
add_action( 'template_redirect', __NAMESPACE__ . '\restrict_cold_storage_access' );

/**
 * Set the cold storage post type.
 *
 * @return void
 */
function register_cold_storage_post_type() {

	// Define our labels.
	$set_label_args = [
		'name'                  => _x( 'Cold Storage', 'Post Type General Name', 'content-cold-storage' ),
		'singular_name'         => _x( 'Cold Storage', 'Post Type Singular Name', 'content-cold-storage' ),
		'menu_name'             => __( 'Cold Storage', 'content-cold-storage' ),
	];

	// Now set up the arguments.
	$set_type_args  = [
		'label'               => __( 'Cold Storage', 'content-cold-storage' ),
		'description'         => __( 'All content placed in cold storage', 'content-cold-storage' ),
		'labels'              => $set_label_args,
		'supports'            => [
			'title',
			'editor',
			'author',
			'excerpt',
			'thumbnail',
			'revisions',
			'custom-fields',
			'comments',
		],
		'hierarchical'        => false,
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_position'       => 200,
		'menu_icon'           => 'dashicons-index-card',
		'show_in_admin_bar'   => false,
		'show_in_nav_menus'   => false,
		'can_export'          => true,
		'exclude_from_search' => true,
		'publicly_queryable'  => true,
		'capability_type'     => 'post',
		'capabilities'        => [
			'create_posts' => false,
			'delete_posts' => true,
		],
		'show_in_rest'        => true,
		'has_archive'         => false,
		'rewrite'             => false,
	];

	// And register the post type.
	register_post_type( 'cold-storage', $set_type_args );
}

/**
 * Never allow front-end access to non-logged in users.
 *
 * @return void
 */
function restrict_cold_storage_access() {

	// Only run this on the front-end, non-RSS feeds, non-logged in, and not REST stuff.
	if ( is_admin() || is_feed() || is_user_logged_in() || wp_is_serving_rest_request() ) {
		return;
	}

	// This only applies to singular cold storage items.
	if ( ! is_singular( 'cold-storage' ) ) {
		return;
	}

	// Set our default redirect link with a filter.
	$setup_redirect = apply_filters( \Norcross\ContentColdStorage\ACTION_PREFIX . 'front_redirect_url', home_url( '/' ) );

	// We met all the criteria, so redirect.
	wp_safe_redirect( esc_url( $setup_redirect ), 302 );
	exit();
}
