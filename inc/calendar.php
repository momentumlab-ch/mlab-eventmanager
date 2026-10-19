<?php
/**
 * "Add to calendar": .ics download and Google Calendar link.
 *
 * @package mlab-eventmanager
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'query_vars', 'mlab_em_ics_query_var' );
add_action( 'template_redirect', 'mlab_em_serve_ics' );

/**
 * Registers the query variable of the .ics download.
 *
 * @param string[] $vars Query variables.
 * @return string[]
 */
function mlab_em_ics_query_var( array $vars ): array {
	$vars[] = 'mlab_em_ics';

	return $vars;
}

/**
 * URL of the .ics file of an event.
 *
 * @param int $post_id Event ID.
 */
function mlab_em_ics_url( int $post_id ): string {
	return add_query_arg( 'mlab_em_ics', $post_id, home_url( '/' ) );
}

/**
 * Google Calendar link of an event, empty if it has no start.
 *
 * @param int $post_id Event ID.
 */
function mlab_em_google_calendar_url( int $post_id ): string {
	$start = mlab_em_get_timestamp( $post_id, 'start' );
	if ( null === $start ) {
		return '';
	}
	$end = max( $start, (int) mlab_em_get_timestamp( $post_id, 'end' ) );

	return add_query_arg(
		array_map(
			'rawurlencode',
			array(
				'action'   => 'TEMPLATE',
				'text'     => mlab_em_plain_title( $post_id ),
				'dates'    => gmdate( 'Ymd\THis\Z', $start ) . '/' . gmdate( 'Ymd\THis\Z', $end ),
				'location' => mlab_em_place_text( $post_id ),
				'details'  => mlab_em_plain_description( $post_id ) . "\n" . get_permalink( $post_id ),
			)
		),
		'https://calendar.google.com/calendar/render'
	);
}

/**
 * Escapes a text value for iCalendar.
 *
 * @param string $text Plain text.
 */
function mlab_em_ics_escape( string $text ): string {
	return str_replace( array( '\\', ';', ',', "\r\n", "\n", "\r" ), array( '\\\\', '\;', '\,', '\n', '\n', '\n' ), $text );
}

/**
 * Folds a content line at 75 octets, as the format requires.
 *
 * @param string $line Unfolded line.
 */
function mlab_em_ics_fold( string $line ): string {
	$parts  = array();
	$length = strlen( $line );
	while ( $length > 75 ) {
		$cut = 75;
		// Never cut inside a multi-byte character.
		while ( $cut > 0 && 0x80 === ( ord( $line[ $cut ] ) & 0xC0 ) ) {
			--$cut;
		}
		$parts[] = substr( $line, 0, $cut );
		$line    = ' ' . substr( $line, $cut );
		$length  = strlen( $line );
	}
	$parts[] = $line;

	return implode( "\r\n", $parts );
}

/**
 * Builds the calendar file of an event.
 *
 * @param int $post_id Event ID.
 */
function mlab_em_build_ics( int $post_id ): string {
	$start = (int) mlab_em_get_timestamp( $post_id, 'start' );
	$end   = max( $start, (int) mlab_em_get_timestamp( $post_id, 'end' ) );
	$lines = array(
		'BEGIN:VCALENDAR',
		'VERSION:2.0',
		'PRODID:-//MomentumLab//Event Manager//EN',
		'CALSCALE:GREGORIAN',
		'BEGIN:VEVENT',
		'UID:' . $post_id . '@' . wp_parse_url( home_url(), PHP_URL_HOST ),
		'DTSTAMP:' . gmdate( 'Ymd\THis\Z' ),
		'DTSTART:' . gmdate( 'Ymd\THis\Z', $start ),
		'DTEND:' . gmdate( 'Ymd\THis\Z', $end ),
		'SUMMARY:' . mlab_em_ics_escape( mlab_em_plain_title( $post_id ) ),
		'DESCRIPTION:' . mlab_em_ics_escape( mlab_em_plain_description( $post_id ) ),
		'LOCATION:' . mlab_em_ics_escape( mlab_em_place_text( $post_id ) ),
		'URL:' . get_permalink( $post_id ),
		'END:VEVENT',
		'END:VCALENDAR',
	);

	return implode( "\r\n", array_map( 'mlab_em_ics_fold', $lines ) ) . "\r\n";
}

/**
 * Sends the .ics file for ?mlab_em_ics=<event id>. Only published events.
 */
function mlab_em_serve_ics(): void {
	$post_id = absint( get_query_var( 'mlab_em_ics' ) );
	if ( ! $post_id ) {
		return;
	}
	if ( MLAB_EM_POST_TYPE !== get_post_type( $post_id ) || 'publish' !== get_post_status( $post_id ) || null === mlab_em_get_timestamp( $post_id, 'start' ) || post_password_required( $post_id ) ) {
		status_header( 404 );
		nocache_headers();
		exit;
	}

	header( 'Content-Type: text/calendar; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="event-' . $post_id . '.ics"' );
	echo mlab_em_build_ics( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- iCalendar text, escaped per value in mlab_em_build_ics().
	exit;
}
