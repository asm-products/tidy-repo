<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class RP4WP_Hook_Related_Auto_Link extends RP4WP_Hook {
	protected $tag = 'transition_post_status';
	protected $args = 3;
	protected $priority = 11;

	public function run( $new_status, $old_status, $post ) {

		// verify this is not an auto save routine.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Only count on post type 'post'
		if ( 'post' != $post->post_type ) {
			return;
		}

		// Post status must be publish
		if ( 'publish' != $new_status ) {
			return;
		}

		// Is automatic linking enabled?
		if ( 1 != RP4WP::get()->settings->get_option( 'automatic_linking' ) ) {
			return;
		}

		// Check if the current post is already auto linked
		if ( 1 != get_post_meta( $post->ID, RP4WP_Constants::PM_POST_AUTO_LINKED, true ) ) {

			// Get automatic linking post amount
			$automatic_linking_post_amount = RP4WP::get()->settings->get_option( 'automatic_linking_post_amount' );

			// Related Posts Manager
			$related_post_manager = new RP4WP_Related_Post_Manager();

			// Link related posts
			$related_post_manager->link_related_post( $post->ID, $automatic_linking_post_amount );

			// Set the auto linked meta
			update_post_meta( $post->ID, RP4WP_Constants::PM_POST_AUTO_LINKED, 1 );
		}

	}
}