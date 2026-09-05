<?php
/**
 * Setting up any custom post types.
 *
 * @package ContentColdStorage
 */

// Declare our namespace.
namespace Norcross\ContentColdStorage\PostTypes;

/**
 * Start our engines.
 */
add_action( 'init', __NAMESPACE__ . '\register_ccs_post_type' );

/**
 * Set the cold storage post type.
 *
 * @return void
 */
function register_ccs_post_type() {

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
		'menu_position'       => 55,
		'menu_icon'           => 'dashicons-index-card',
		'show_in_admin_bar'   => false,
		'show_in_nav_menus'   => false,
		'can_export'          => true,
		'exclude_from_search' => false,
		'publicly_queryable'  => false,
		'capability_type'     => 'post',
		'capabilities'        => [
			'create_posts' => false,
		],
		'map_meta_cap'        => false,
		'show_in_rest'        => false,
		'has_archive'         => false,
		'rewrite'             => false,
	];

	// And register the post type.
	register_post_type( 'cold-storage', $set_type_args );
}
