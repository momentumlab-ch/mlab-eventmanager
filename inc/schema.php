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
		// Google needs an address for every place: without one, the place name stands in (e.g. "Panoramaplatz Grindelwald").
		$locations[] = array_filter(
			array(
				'@type'   => 'Place',
				'name'    => $place['name'],
				'address' => '' !== $place['address'] ? $place['address'] : $place['name'],
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
 * Price from the free text price field: a number becomes the price, "free" words become 0.
 *
 * Without a price field the description is checked for "free entry" only, never for a number.
 *
 * @param string $text        Price field.
 * @param string $description Plain description.
 * @return string|null Price with two decimals, or null when unknown.
 */
function mlab_em_schema_price( string $text, string $description = '' ): ?string {
	$free = '/\b(eintritt\s+frei|freier\s+eintritt|gratis|kostenlos|free|gratuit)\b/iu';
	if ( '' === trim( $text ) ) {
		return preg_match( $free, $description ) ? '0.00' : null;
	}
	if ( preg_match( '/(\d+(?:[.,]\d{1,2})?)/', $text, $match ) ) {
		return number_format( (float) str_replace( ',', '.', $match[1] ), 2, '.', '' );
	}

	return preg_match( $free, $text ) ? '0.00' : null;
}

/**
 * Offer for Google: price and currency when known, link to tickets or the event page.
 *
 * @param int    $post_id     Event ID.
 * @param string $description Plain description.
 * @return array<string, mixed>|null
 */
function mlab_em_schema_offer( int $post_id, string $description ): ?array {
	$ticket = mlab_em_get_field( $post_id, 'ticket_url' );
	$price  = mlab_em_schema_price( mlab_em_get_field( $post_id, 'price' ), $description );
	if ( '' === $ticket && null === $price ) {
		return null;
	}

	return array_filter(
		array(
			'@type'         => 'Offer',
			'url'           => '' !== $ticket ? $ticket : get_permalink( $post_id ),
			'price'         => $price,
			'priceCurrency' => null !== $price ? (string) apply_filters( 'mlab_em_currency', 'CHF', $post_id ) : null,
			'availability'  => 'https://schema.org/InStock',
			'validFrom'     => get_post_time( 'c', true, $post_id ),
		),
		static fn( $value ) => null !== $value && '' !== $value
	);
}

/**
 * Organizer: the chosen one, else the website itself (most events are run by the site owner).
 *
 * @param int $post_id Event ID.
 * @return array<string, string>
 */
function mlab_em_schema_organizer( int $post_id ): array {
	$name = mlab_em_organizer_name( $post_id );
	$data = '' !== $name ? array(
		'@type' => 'Organization',
		'name'  => $name,
	) : array(
		'@type' => 'Organization',
		'name'  => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
		'url'   => home_url( '/' ),
	);

	return (array) apply_filters( 'mlab_em_schema_organizer', $data, $post_id );
}

/**
 * Image in several sizes, largest first: Google picks the best fit for its event list.
 *
 * @param int $post_id Event ID.
 * @return list<string>
 */
function mlab_em_schema_images( int $post_id ): array {
	$id = get_post_thumbnail_id( $post_id );
	if ( ! $id ) {
		return array();
	}
	$urls = array();
	foreach ( array( 'full', 'large', 'medium_large' ) as $size ) {
		$src = wp_get_attachment_image_url( $id, $size );
		if ( $src ) {
			$urls[] = $src;
		}
	}

	return array_values( array_unique( $urls ) );
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
	$text      = mlab_em_plain_description( $post_id );
	$images    = mlab_em_schema_images( $post_id );

	$data = array(
		'@context'            => 'https://schema.org',
		'@type'               => 'Event',
		'name'                => mlab_em_plain_title( $post_id ),
		'description'         => $text,
		'url'                 => get_permalink( $post_id ),
		'startDate'           => $start ? wp_date( 'c', $start ) : null,
		'endDate'             => $end && $end > $start ? wp_date( 'c', $end ) : null,
		'eventStatus'         => 'https://schema.org/EventScheduled',
		'eventAttendanceMode' => mlab_em_attendance_mode( $post_id ),
		'location'            => count( $locations ) > 1 ? $locations : ( $locations[0] ?? null ),
		'image'               => count( $images ) > 1 ? $images : ( $images[0] ?? null ),
		'organizer'           => mlab_em_schema_organizer( $post_id ),
		'offers'              => mlab_em_schema_offer( $post_id, $text ),
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
