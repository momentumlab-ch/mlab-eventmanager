<?php
/**
 * Position of an event from its address, for the embedded map.
 * Runs only when the map is switched on and no venue with coordinates is chosen.
 *
 * @package mlab-eventmanager
 */

defined( 'ABSPATH' ) || exit;

add_action( 'mlab_em_event_saved', 'mlab_em_geocode_event', 10, 2 );

/**
 * Looks up the coordinates on OpenStreetMap (Nominatim) when the address changed.
 *
 * @param int                  $post_id Event ID.
 * @param array<string, mixed> $values  Saved values.
 */
function mlab_em_geocode_event( int $post_id, array $values ): void {
	if ( ! mlab_em_show_map( $post_id ) ) {
		return;
	}
	$venue_id = absint( mlab_em_get_field( $post_id, 'venue_id' ) );
	if ( $venue_id && '' !== mlab_em_get_field( $venue_id, 'lat' ) ) {
		return;
	}
	$query = implode( ', ', array_filter( array( mlab_em_get_field( $post_id, 'location' ), mlab_em_get_field( $post_id, 'address' ) ) ) );
	if ( '' === $query ) {
		return;
	}
	$manual = ! empty( $values['lat'] ) && ! empty( $values['lon'] );
	$known  = get_post_meta( $post_id, '_mlab_em_geo_query', true ) === $query && '' !== mlab_em_get_field( $post_id, 'lat' );
	if ( $manual || $known ) {
		return;
	}
	$point = mlab_em_geocode( $query );
	if ( $point ) {
		mlab_em_store_values( $post_id, $point );
		update_post_meta( $post_id, '_mlab_em_geo_query', $query );
	}
}

/**
 * Coordinates for a place text, or null if nothing was found.
 *
 * @param string $query Place name and address.
 * @return array{lat: string, lon: string}|null
 */
function mlab_em_geocode( string $query ): ?array {
	/**
	 * Request parameters for the address search, e.g. a country limit: array( 'countrycodes' => 'ch' ).
	 *
	 * @param array  $args  Query arguments.
	 * @param string $query Place text.
	 */
	$args = apply_filters(
		'mlab_em_geocode_args',
		array(
			'q'      => $query,
			'format' => 'jsonv2',
			'limit'  => 1,
		),
		$query
	);
	$response = wp_remote_get(
		add_query_arg( array_map( 'rawurlencode', $args ), 'https://nominatim.openstreetmap.org/search' ),
		array(
			'timeout'    => 6,
			'user-agent' => 'MomentumLab Event Manager/' . MLAB_EM_VERSION . ' (' . home_url( '/' ) . ')',
		)
	);
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return null;
	}
	$hits = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( empty( $hits[0]['lat'] ) || empty( $hits[0]['lon'] ) ) {
		return null;
	}
	return array(
		'lat' => mlab_em_sanitize_coordinate( (string) $hits[0]['lat'], 90 ),
		'lon' => mlab_em_sanitize_coordinate( (string) $hits[0]['lon'], 180 ),
	);
}
