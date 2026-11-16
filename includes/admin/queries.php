<?php
/**
 * Load our admin display and menu items.
 *
 * @package ContentColdStorage
 */

// Declare our namespace.
namespace Norcross\ContentColdStorage\Admin\Queries;

// Set our aliases.
use Norcross\ContentColdStorage\Admin\Config as AdminConfig;

// And any functions or constants on their own.
use const Norcross\ContentColdStorage\META_PREFIX;

/**
 * Start our engines.
 */
add_action( 'pre_get_posts', __NAMESPACE__ . '\manage_admin_table_display', 10 );

/**
 * Handle the various sorting and filtering of the cold storage table.
 *
 * @param  WP_Query $query  The query object.
 *
 * @return void
 */
function manage_admin_table_display( $query ) {

	// Run this on admin only.
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	// Only do this on cold storage.
	if ( empty( $query->get( 'post_type' ) ) || 'cold-storage' !== sanitize_text_field( $query->get( 'post_type' ) ) ) {
		return;
	}

	// First check for the meta query setups.
	maybe_add_meta_query_sort( $query );

	// Set the orderby with the fallback so we don't bork other sorts.
	$set_orderbyarg = ! empty( $query->get( 'orderby' ) ) ? $query->get( 'orderby' ) : 'date';

	// Swing through and set our order up.
	switch ( $set_orderbyarg ) {

		// Our original post type.
		case 'ccs-original-type' :
			$query->set( 'meta_key', META_PREFIX . 'original_type' );
			$query->set( 'orderby', 'meta_value' );
			break;

		// Our original post status.
		case 'ccs-original-status' :
			$query->set( 'meta_key', META_PREFIX . 'original_status' );
			$query->set( 'orderby', 'meta_value' );
			break;

		// Hijacking the date sort to use our storage time.
		case 'date' :
			$query->set( 'meta_key', META_PREFIX . 'storage_time' );
			$query->set( 'orderby', 'meta_value_num' );
			break;
	}

	// Nothing left to modify.
}

/**
 * Set up the various meta query items we have.
 *
 * @param  WP_Query $query  The query object.
 *
 * @return void
 */
function maybe_add_meta_query_sort( $query ) {

	// Check for possible existing ones.
	$check_meta_args = $query->get( 'meta_query' );

	// Now set them, with a fallback.
	$meta_query_args = ! empty( $check_meta_args ) ? $check_meta_args : [];

	// If we have an original type filter being passed, handle it.
	if ( ! empty( $query->get( 'cs_original_type' ) ) ) {

		// Set the args for a query.
		$meta_query_args[] = [
			'key'     => META_PREFIX . 'original_type',
			'value'   => esc_attr( $query->get( 'cs_original_type' ) ),
			'compare' => '=',
		];
	}

	// If we have an original status filter being passed, handle it.
	if ( ! empty( $query->get( 'cs_original_status' ) ) ) {

		// Set the args for a query.
		$meta_query_args[] = [
			'key'     => META_PREFIX . 'original_status',
			'value'   => esc_attr( $query->get( 'cs_original_status' ) ),
			'compare' => '=',
		];
	}

	// Bail if we don't have args to process.
	if ( empty( $meta_query_args ) ) {
		return;
	}

	// Include the relation if we have more than one.
	if ( count( $meta_query_args ) > 1 ) {
		$meta_query_args['relation'] = 'AND';
	}

	// Now add this to the larger array.
	$query->set( 'meta_query', $meta_query_args ); // phpcs:ignore -- this is a required meta query.
}
