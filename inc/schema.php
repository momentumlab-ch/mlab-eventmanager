<?php
/**
 * Event structured data (schema.org JSON-LD) for search engines.
 *
 * @package mlab-eventmanager
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_head', 'mlab_em_print_schema' );

/**
 * Schema.org attendance mode: offline, online or mixed.
 *
 * @param int $post_id Event ID.
 */
function mlab_em_attendance_mode( int $post_id ): string {
	$online  = '' !== mlab_em_get_field( $post_id, 'online_url' );
	$offline = '' !== mlab_em_place_text( $post_id );
	$mode    = ! $online ? 'Offline' : ( $offline ? 'Mixed' : 'Online' );

	return 'https://schema.org/' . $mode . 'EventAttendanceMode';
}

/**
 * Location entries: the place and/or the online link.
 *
 * @param int $post_id Event ID.
 * @return array<int, array<string, mixed>>
 */
function mlab_em_schema_locations( int $post_id ): array {
	$locations = array();
	$place     = mlab_em_get_place( $post_id );
	$online    = mlab_em_get_field( $post_id, 'online_url' );

	if ( '' !== mlab_em_place_text( $post_id ) ) {
		$locations[] = array_filter(
			array(
				'@type'   => 'Place',
				'name'    => $place['name'],
				'address' => $place['address'],
			)
		);
	}
	if ( '' !== $online ) {
		$locations[] = array(
			'@type' => 'VirtualLocation',
			'url'   => $online,
		);
	}

	return $locations;
}

/**
 * Schema.org data of an event.
 *
 * @param int $post_id Event ID.
 * @return array<string, mixed>
 */
function mlab_em_schema_data( int $post_id ): array {
	$start     = mlab_em_get_timestamp( $post_id, 'start' );
	$end       = mlab_em_get_timestamp( $post_id, 'end' );
	$locations = mlab_em_schema_locations( $post_id );
	$organizer = mlab_em_organizer_name( $post_id );
	$ticket    = mlab_em_get_field( $post_id, 'ticket_url' );

	$data = array(
		'@context'            => 'https://schema.org',
		'@type'               => 'Event',
		'name'                => mlab_em_plain_title( $post_id ),
		'description'         => mlab_em_plain_description( $post_id ),
		'url'                 => get_permalink( $post_id ),
		'startDate'           => $start ? wp_date( 'c', $start ) : null,
		'endDate'             => $end && $end > $start ? wp_date( 'c', $end ) : null,
		'eventStatus'         => 'https://schema.org/EventScheduled',
		'eventAttendanceMode' => mlab_em_attendance_mode( $post_id ),
		'location'            => count( $locations ) > 1 ? $locations : ( $locations[0] ?? null ),
		'image'               => get_the_post_thumbnail_url( $post_id, 'large' ) ?: null, // phpcs:ignore Universal.Operators.DisallowShortTernary.Found -- Empty string means no image.
		'organizer'           => '' !== $organizer ? array(
			'@type' => 'Organization',
			'name'  => $organizer,
		) : null,
		'offers'              => '' !== $ticket ? array(
			'@type' => 'Offer',
			'url'   => $ticket,
		) : null,
	);

	return (array) apply_filters( 'mlab_em_schema', array_filter( $data ), $post_id );
}

/**
 * Prints the JSON-LD block on single event pages.
 */
function mlab_em_print_schema(): void {
	if ( ! is_singular( MLAB_EM_POST_TYPE ) ) {
		return;
	}

	$data = mlab_em_schema_data( get_queried_object_id() );
	if ( empty( $data['startDate'] ) ) {
		return;
	}

	$json = wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP );
	if ( $json ) {
		echo '<script type="application/ld+json">' . $json . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON encoded with HEX_TAG and HEX_AMP.
	}
}
