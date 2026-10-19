<?php
/**
 * Single event page: date, location and ticket link above the text.
 *
 * @package mlab-eventmanager
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'the_content', 'mlab_em_add_details_to_content' );

/**
 * Prepends the event details on single event pages.
 *
 * @param string $content Post content.
 */
function mlab_em_add_details_to_content( string $content ): string {
	if ( ! is_singular( MLAB_EM_POST_TYPE ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	if ( ! apply_filters( 'mlab_em_show_details', true, get_the_ID() ) ) {
		return $content;
	}

	return mlab_em_get_template( 'event-details', array( 'post_id' => get_the_ID() ) ) . $content;
}
