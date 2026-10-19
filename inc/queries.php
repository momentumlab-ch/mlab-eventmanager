<?php
/**
 * Event queries: sorting and filtering by date.
 *
 * @package mlab-eventmanager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Statuses of events that are listed in the admin, export and "My events".
 *
 * @return string[]
 */
function mlab_em_event_statuses(): array {
	return array( 'publish', 'pending', 'draft', 'future', 'private' );
}

/**
 * Current site time in storage format, for upcoming/past queries.
 */
function mlab_em_now(): string {
	return current_datetime()->format( MLAB_EM_DATE_FORMAT );
}

/**
 * Query args for events sorted by start, optionally only upcoming or past.
 * One named meta clause serves both the sort and the filter.
 *
 * @param string $show  upcoming, past or all.
 * @param string $from  Earliest day (Y-m-d) for upcoming and all, empty for no limit.
 * @param string $order ASC or DESC, empty for soonest first (latest first for past).
 * @return array<string, mixed>
 */
function mlab_em_date_query_args( string $show, string $from = '', string $order = '' ): array {
	$meta_query = array(
		'mlab_em_start' => array(
			'key'     => mlab_em_meta_key( 'start' ),
			'compare' => 'EXISTS',
		),
	);

	if ( 'past' === $show ) {
		$meta_query['mlab_em_end'] = array(
			'key'     => mlab_em_meta_key( 'end' ),
			'value'   => mlab_em_now(),
			'compare' => '<',
		);
	} elseif ( 'upcoming' === $show || '' !== $from ) {
		$earliest                  = '' !== $from ? $from . ' 00:00' : '';
		$meta_query['mlab_em_end'] = array(
			'key'     => mlab_em_meta_key( 'end' ),
			'value'   => 'upcoming' === $show ? max( mlab_em_now(), $earliest ) : $earliest,
			'compare' => '>=',
		);
	}

	$order = in_array( strtoupper( $order ), array( 'ASC', 'DESC' ), true ) ? strtoupper( $order ) : ( 'past' === $show ? 'DESC' : 'ASC' );

	return array(
		'meta_query' => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Sorting and filtering by event date.
		'orderby'    => array( 'mlab_em_start' => $order ),
	);
}
