<?php
/**
 * Event fields in one place. Meta box, saving, REST, quick entry form and
 * display all read from mlab_em_event_fields().
 *
 * @package mlab-eventmanager
 */

defined( 'ABSPATH' ) || exit;

const MLAB_EM_DATE_FORMAT = 'Y-m-d H:i';

/**
 * Field definitions. Add or change fields via the "mlab_em_event_fields" filter.
 *
 * Keys: label, type (datetime|text|url|textarea), required, default,
 * quick (shown in the phone form), column (shown in the admin list),
 * button (url fields: shown as button with this text), details (false hides it on the event page).
 *
 * @return array<string, array<string, mixed>>
 */
function mlab_em_event_fields(): array {
	$fields = array(
		'start'      => array(
			'label'    => __( 'Start', 'mlab-eventmanager' ),
			'type'     => 'datetime',
			'required' => true,
			'quick'    => true,
			'default'  => mlab_em_default_start(),
		),
		'end'        => array(
			'label' => __( 'End (optional)', 'mlab-eventmanager' ),
			'type'  => 'datetime',
			'quick' => true,
		),
		'location'   => array(
			'label'  => __( 'Location', 'mlab-eventmanager' ),
			'type'   => 'text',
			'quick'  => true,
			'column' => true,
		),
		'address'    => array(
			'label' => __( 'Address', 'mlab-eventmanager' ),
			'type'  => 'text',
		),
		'organizer'  => array(
			'label' => __( 'Organizer', 'mlab-eventmanager' ),
			'type'  => 'text',
		),
		'price'      => array(
			'label' => __( 'Price', 'mlab-eventmanager' ),
			'type'  => 'text',
		),
		'ticket_url' => array(
			'label'  => __( 'Ticket link', 'mlab-eventmanager' ),
			'type'   => 'url',
			'button' => __( 'Get tickets', 'mlab-eventmanager' ),
		),
	);

	return apply_filters( 'mlab_em_event_fields', $fields );
}

/**
 * Default start for a new event: tomorrow at 19:00, site time.
 */
function mlab_em_default_start(): string {
	$start = current_datetime()->modify( '+1 day' )->setTime( 19, 0 );

	return (string) apply_filters( 'mlab_em_default_start', $start->format( MLAB_EM_DATE_FORMAT ) );
}

/**
 * Meta key for a field.
 *
 * @param string $key Field key.
 */
function mlab_em_meta_key( string $key ): string {
	return '_mlab_em_' . $key;
}

/**
 * Stored value of a field.
 *
 * @param int    $post_id Event ID.
 * @param string $key     Field key.
 */
function mlab_em_get_field( int $post_id, string $key ): string {
	return (string) get_post_meta( $post_id, mlab_em_meta_key( $key ), true );
}

/**
 * Sanitizes one value according to its field type.
 *
 * @param mixed  $value Raw value.
 * @param string $type  Field type.
 */
function mlab_em_sanitize_value( $value, string $type ): string {
	$raw = is_scalar( $value ) ? (string) $value : '';

	switch ( $type ) {
		case 'datetime':
			$date  = DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i', substr( str_replace( ' ', 'T', trim( $raw ) ), 0, 16 ) );
			$clean = $date ? $date->format( MLAB_EM_DATE_FORMAT ) : '';
			break;
		case 'url':
			$clean = esc_url_raw( trim( $raw ) );
			break;
		case 'textarea':
			$clean = sanitize_textarea_field( $raw );
			break;
		default:
			$clean = sanitize_text_field( $raw );
	}

	return (string) apply_filters( 'mlab_em_sanitize_value', $clean, $value, $type );
}

/**
 * First required field that is empty after sanitizing, or null if all are set.
 *
 * @param array<string, mixed> $input Raw values keyed by field key.
 */
function mlab_em_missing_required_field( array $input ): ?string {
	foreach ( mlab_em_event_fields() as $key => $field ) {
		if ( ! empty( $field['required'] ) && '' === mlab_em_sanitize_value( $input[ $key ] ?? '', $field['type'] ) ) {
			return $key;
		}
	}

	return null;
}

/**
 * Saves submitted field values. An empty end takes the start, so lists can
 * filter on the end date alone.
 *
 * @param int                  $post_id Event ID.
 * @param array<string, mixed> $input   Raw values keyed by field key.
 */
function mlab_em_save_fields( int $post_id, array $input ): void {
	$values = array();

	foreach ( mlab_em_event_fields() as $key => $field ) {
		if ( array_key_exists( $key, $input ) ) {
			$values[ $key ] = mlab_em_sanitize_value( $input[ $key ], $field['type'] );
		}
	}

	if ( isset( $values['start'] ) && ( empty( $values['end'] ) || $values['end'] < $values['start'] ) ) {
		$values['end'] = $values['start'];
	}

	foreach ( $values as $key => $value ) {
		update_post_meta( $post_id, mlab_em_meta_key( $key ), $value );
	}

	do_action( 'mlab_em_event_saved', $post_id, $values );
}

/**
 * Prints the input for one field. Shared by the meta box and the phone form.
 * Custom field types render as text input unless "mlab_em_input_type" maps them.
 *
 * @param string               $key   Field key.
 * @param array<string, mixed> $field Field definition.
 * @param string               $value Current value.
 */
function mlab_em_render_input( string $key, array $field, string $value ): void {
	$id       = 'mlab-em-' . str_replace( '_', '-', $key );
	$name     = 'mlab_em[' . $key . ']';
	$required = empty( $field['required'] ) ? '' : ' required';

	printf( '<label class="mlab-em-label" for="%s">%s</label>', esc_attr( $id ), esc_html( $field['label'] ) );

	if ( 'textarea' === $field['type'] ) {
		printf( '<textarea id="%s" name="%s" rows="4"%s>%s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_attr( $required ), esc_textarea( $value ) );
		return;
	}

	$types = array(
		'datetime' => 'datetime-local',
		'url'      => 'url',
	);
	$type  = $types[ $field['type'] ] ?? 'text';
	$value = 'datetime' === $field['type'] ? str_replace( ' ', 'T', $value ) : $value;
	$type  = (string) apply_filters( 'mlab_em_input_type', $type, $field );

	printf( '<input type="%s" id="%s" name="%s" value="%s"%s>', esc_attr( $type ), esc_attr( $id ), esc_attr( $name ), esc_attr( $value ), esc_attr( $required ) );
}

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
 * Current site time in storage format, for upcoming/past queries.
 */
function mlab_em_now(): string {
	return current_datetime()->format( MLAB_EM_DATE_FORMAT );
}

/**
 * Rows for the event page: the date range, then every filled field.
 *
 * @param int $post_id Event ID.
 * @return array<string, string> Label => text.
 */
function mlab_em_details_rows( int $post_id ): array {
	$rows = array( __( 'When', 'mlab-eventmanager' ) => mlab_em_format_date_range( $post_id ) );

	foreach ( mlab_em_event_fields() as $key => $field ) {
		$skip = in_array( $key, array( 'start', 'end' ), true ) || false === ( $field['details'] ?? true ) || ! empty( $field['button'] );
		if ( ! $skip ) {
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
 * Display text of one field: dates formatted, everything else as stored.
 *
 * @param int    $post_id Event ID.
 * @param string $key     Field key.
 * @param string $type    Field type.
 */
function mlab_em_display_value( int $post_id, string $key, string $type ): string {
	if ( 'datetime' !== $type ) {
		return mlab_em_get_field( $post_id, $key );
	}

	$timestamp = mlab_em_get_timestamp( $post_id, $key );

	return null === $timestamp ? '' : wp_date( get_option( 'date_format' ) . ', ' . get_option( 'time_format' ), $timestamp );
}

/**
 * Query args for events sorted by start, optionally only upcoming or past.
 * One named meta clause serves both the sort and the filter.
 *
 * @param string $show upcoming, past or all.
 * @return array<string, mixed>
 */
function mlab_em_date_query_args( string $show ): array {
	$meta_query = array(
		'mlab_em_start' => array(
			'key'     => mlab_em_meta_key( 'start' ),
			'compare' => 'EXISTS',
		),
	);

	if ( 'all' !== $show ) {
		$meta_query['mlab_em_end'] = array(
			'key'     => mlab_em_meta_key( 'end' ),
			'value'   => mlab_em_now(),
			'compare' => 'past' === $show ? '<' : '>=',
		);
	}

	return array(
		'meta_query' => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Sorting and filtering by event date.
		'orderby'    => array( 'mlab_em_start' => 'past' === $show ? 'DESC' : 'ASC' ),
	);
}
