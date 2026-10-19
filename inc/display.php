<?php
/**
 * Reading event data for output: dates, place, organizer, detail rows and buttons.
 *
 * @package mlab-eventmanager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Unix timestamp of a datetime field, in the site timezone.
 *
 * @param int    $post_id Event ID.
 * @param string $key     Field key.
 */
function mlab_em_get_timestamp( int $post_id, string $key ): ?int {
	$date = DateTimeImmutable::createFromFormat( MLAB_EM_DATE_FORMAT, mlab_em_get_field( $post_id, $key ), wp_timezone() );

	return $date ? $date->getTimestamp() : null;
}

/**
 * Human readable date range, e.g. "24. Oktober 2026, 19:00 – 22:00".
 *
 * @param int $post_id Event ID.
 */
function mlab_em_format_date_range( int $post_id ): string {
	$start = mlab_em_get_timestamp( $post_id, 'start' );
	if ( null === $start ) {
		return '';
	}

	$date_format = get_option( 'date_format' );
	$time_format = get_option( 'time_format' );
	$output      = wp_date( $date_format . ', ' . $time_format, $start );
	$end         = mlab_em_get_timestamp( $post_id, 'end' );

	if ( null !== $end && $end > $start ) {
		$same_day = wp_date( 'Y-m-d', $start ) === wp_date( 'Y-m-d', $end );
		$output  .= ' – ' . wp_date( $same_day ? $time_format : $date_format . ', ' . $time_format, $end );
	}

	return (string) apply_filters( 'mlab_em_date_range', $output, $post_id );
}

/**
 * Display text of one field: dates formatted, linked entries by title, everything else as stored.
 *
 * @param int    $post_id Event ID.
 * @param string $key     Field key.
 * @param string $type    Field type.
 */
function mlab_em_display_value( int $post_id, string $key, string $type ): string {
	$value = mlab_em_get_field( $post_id, $key );

	if ( 'post' === $type ) {
		return '' === $value ? '' : get_the_title( (int) $value );
	}
	if ( 'datetime' !== $type ) {
		return $value;
	}

	$timestamp = mlab_em_get_timestamp( $post_id, $key );

	return null === $timestamp ? '' : wp_date( get_option( 'date_format' ) . ', ' . get_option( 'time_format' ), $timestamp );
}

/**
 * Place of an event: the chosen venue, else the free text location.
 *
 * @param int $post_id Event ID.
 * @return array{name: string, address: string, lat: string, lon: string}
 */
function mlab_em_get_place( int $post_id ): array {
	$venue_id = absint( mlab_em_get_field( $post_id, 'venue_id' ) );

	if ( $venue_id && 'publish' === get_post_status( $venue_id ) ) {
		$place = array(
			'name'    => get_the_title( $venue_id ),
			'address' => mlab_em_get_field( $venue_id, 'address' ),
			'lat'     => mlab_em_get_field( $venue_id, 'lat' ),
			'lon'     => mlab_em_get_field( $venue_id, 'lon' ),
		);
	} else {
		$place = array(
			'name'    => mlab_em_get_field( $post_id, 'location' ),
			'address' => mlab_em_get_field( $post_id, 'address' ),
			'lat'     => '',
			'lon'     => '',
		);
	}

	return apply_filters( 'mlab_em_place', $place, $post_id );
}

/**
 * Place as one line, e.g. "Kulturhaus, Hauptstrasse 1, 8000 Zürich".
 *
 * @param int $post_id Event ID.
 */
function mlab_em_place_text( int $post_id ): string {
	$place = mlab_em_get_place( $post_id );

	return implode( ', ', array_filter( array( $place['name'], $place['address'] ) ) );
}

/**
 * Organizer name: the chosen organizer, else the free text.
 *
 * @param int $post_id Event ID.
 */
function mlab_em_organizer_name( int $post_id ): string {
	$organizer_id = absint( mlab_em_get_field( $post_id, 'organizer_id' ) );

	if ( $organizer_id && 'publish' === get_post_status( $organizer_id ) ) {
		return get_the_title( $organizer_id );
	}

	return mlab_em_get_field( $post_id, 'organizer' );
}

/**
 * Rows for the event page: date, place, organizer, then every other filled field.
 *
 * @param int $post_id Event ID.
 * @return array<string, string> Label => text.
 */
function mlab_em_details_rows( int $post_id ): array {
	$rows = array(
		__( 'When', 'mlab-eventmanager' )      => mlab_em_format_date_range( $post_id ),
		__( 'Where', 'mlab-eventmanager' )     => mlab_em_place_text( $post_id ),
		__( 'Organizer', 'mlab-eventmanager' ) => mlab_em_organizer_name( $post_id ),
	);

	foreach ( mlab_em_event_fields() as $key => $field ) {
		if ( false !== ( $field['details'] ?? true ) && empty( $field['button'] ) && 'datetime' !== $field['type'] ) {
			$rows[ $field['label'] ] = mlab_em_display_value( $post_id, $key, $field['type'] );
		}
	}

	return array_filter( apply_filters( 'mlab_em_details_rows', $rows, $post_id ) );
}

/**
 * Buttons for the event page from url fields with a "button" text.
 *
 * @param int $post_id Event ID.
 * @return array<int, array{url: string, label: string}>
 */
function mlab_em_details_buttons( int $post_id ): array {
	$buttons = array();

	foreach ( mlab_em_event_fields() as $key => $field ) {
		$url = mlab_em_get_field( $post_id, $key );
		if ( ! empty( $field['button'] ) && '' !== $url ) {
			$buttons[] = array(
				'url'   => $url,
				'label' => $field['button'],
			);
		}
	}

	return $buttons;
}

/**
 * Description as plain text, the excerpt if there is one.
 *
 * @param int $post_id   Event ID.
 * @param int $max_words Longest text in words.
 */
function mlab_em_plain_description( int $post_id, int $max_words = 55 ): string {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return '';
	}

	$text = '' !== $post->post_excerpt ? $post->post_excerpt : strip_shortcodes( $post->post_content );

	return wp_trim_words( wp_strip_all_tags( $text ), $max_words, '…' );
}

/**
 * Title as plain text, for calendar files and structured data.
 *
 * @param int $post_id Event ID.
 */
function mlab_em_plain_title( int $post_id ): string {
	return html_entity_decode( wp_strip_all_tags( get_the_title( $post_id ) ), ENT_QUOTES, 'UTF-8' );
}
