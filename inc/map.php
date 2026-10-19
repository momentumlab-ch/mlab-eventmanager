<?php
/**
 * Map links and the optional embedded map, both OpenStreetMap.
 * The embed is off by default because it loads content from a third party.
 *
 * @package mlab-eventmanager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Link to the place on OpenStreetMap, empty if the place has no address or coordinates.
 *
 * @param int $post_id Event ID.
 */
function mlab_em_map_link( int $post_id ): string {
	$place = mlab_em_get_place( $post_id );

	if ( '' !== $place['lat'] && '' !== $place['lon'] ) {
		return add_query_arg(
			array(
				'mlat' => $place['lat'],
				'mlon' => $place['lon'],
			),
			'https://www.openstreetmap.org/'
		) . '#map=16/' . $place['lat'] . '/' . $place['lon'];
	}

	$query = implode( ', ', array_filter( array( $place['name'], $place['address'] ) ) );

	return '' === $query ? '' : add_query_arg( 'query', rawurlencode( $query ), 'https://www.openstreetmap.org/search' );
}

/**
 * Embed URL for the place, only when coordinates are known.
 *
 * @param int $post_id Event ID.
 */
function mlab_em_map_embed_url( int $post_id ): string {
	$place = mlab_em_get_place( $post_id );
	if ( '' === $place['lat'] || '' === $place['lon'] ) {
		return '';
	}

	$lat = (float) $place['lat'];
	$lon = (float) $place['lon'];

	return add_query_arg(
		array(
			'bbox'   => implode( ',', array( $lon - 0.004, $lat - 0.0025, $lon + 0.004, $lat + 0.0025 ) ),
			'layer'  => 'mapnik',
			'marker' => $lat . ',' . $lon,
		),
		'https://www.openstreetmap.org/export/embed.html'
	);
}
