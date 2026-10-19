<?php
/**
 * Event fields in one place: definitions, sanitizing, saving and input rendering.
 * Meta box, REST, phone form, admin list and event page all read from mlab_em_event_fields().
 *
 * @package mlab-eventmanager
 */

defined( 'ABSPATH' ) || exit;

const MLAB_EM_DATE_FORMAT = 'Y-m-d H:i';

/**
 * Field groups in display order. Extend via the "mlab_em_field_groups" filter.
 *
 * @return array<string, string> Group key => label.
 */
function mlab_em_field_groups(): array {
	$groups = array(
		'when'      => __( 'When', 'mlab-eventmanager' ),
		'where'     => __( 'Where', 'mlab-eventmanager' ),
		'online'    => __( 'Online', 'mlab-eventmanager' ),
		'organizer' => __( 'Organizer', 'mlab-eventmanager' ),
		'tickets'   => __( 'Tickets and price', 'mlab-eventmanager' ),
		'display'   => __( 'Display', 'mlab-eventmanager' ),
	);

	return apply_filters( 'mlab_em_field_groups', $groups );
}

/**
 * Label of a field group.
 *
 * @param string $group Group key.
 */
function mlab_em_group_label( string $group ): string {
	return mlab_em_field_groups()[ $group ] ?? __( 'More', 'mlab-eventmanager' );
}

/**
 * Field definitions. Add or change fields via the "mlab_em_event_fields" filter.
 *
 * Keys: label, type (datetime|text|url|textarea|post), group, required, default, hint,
 * quick (visible without "More details" in the phone form), post_type (type post),
 * column (shown in the admin list), button (url fields: shown as button with this text),
 * details (false hides it from the event page).
 *
 * @return array<string, array<string, mixed>>
 */
function mlab_em_event_fields(): array {
	$fields = array(
		'start'        => array(
			'label'    => __( 'Start', 'mlab-eventmanager' ),
			'type'     => 'datetime',
			'group'    => 'when',
			'required' => true,
			'quick'    => true,
			'default'  => mlab_em_default_start(),
		),
		'end'          => array(
			'label' => __( 'End (optional)', 'mlab-eventmanager' ),
			'type'  => 'datetime',
			'group' => 'when',
			'quick' => true,
		),
		'venue_id'     => array(
			'label'     => __( 'Venue', 'mlab-eventmanager' ),
			'type'      => 'post',
			'post_type' => MLAB_EM_VENUE,
			'group'     => 'where',
			'quick'     => true,
			'details'   => false,
		),
		'location'     => array(
			'label'   => __( 'Location', 'mlab-eventmanager' ),
			'type'    => 'text',
			'group'   => 'where',
			'quick'   => true,
			'details' => false,
			'hint'    => __( 'Only needed if no venue is selected.', 'mlab-eventmanager' ),
		),
		'address'      => array(
			'label'   => __( 'Address', 'mlab-eventmanager' ),
			'type'    => 'text',
			'group'   => 'where',
			'details' => false,
			'hint'    => __( 'Only needed if no venue is selected.', 'mlab-eventmanager' ),
		),
		'lat'          => array(
			'label'   => __( 'Latitude', 'mlab-eventmanager' ),
			'type'    => 'latitude',
			'group'   => 'where',
			'details' => false,
			'hint'    => __( 'Filled in automatically from the address when the map is switched on.', 'mlab-eventmanager' ),
		),
		'lon'          => array(
			'label'   => __( 'Longitude', 'mlab-eventmanager' ),
			'type'    => 'longitude',
			'group'   => 'where',
			'details' => false,
		),
		'online_url'   => array(
			'label'  => __( 'Online link', 'mlab-eventmanager' ),
			'type'   => 'url',
			'group'  => 'online',
			'button' => __( 'Join online', 'mlab-eventmanager' ),
		),
		'organizer_id' => array(
			'label'     => __( 'Organizer', 'mlab-eventmanager' ),
			'type'      => 'post',
			'post_type' => MLAB_EM_ORGANIZER,
			'group'     => 'organizer',
			'details'   => false,
		),
		'organizer'    => array(
			'label'   => __( 'Other organizer', 'mlab-eventmanager' ),
			'type'    => 'text',
			'group'   => 'organizer',
			'details' => false,
			'hint'    => __( 'Only needed if no organizer is selected.', 'mlab-eventmanager' ),
		),
		'price'        => array(
			'label' => __( 'Price', 'mlab-eventmanager' ),
			'type'  => 'text',
			'group' => 'tickets',
		),
		'ticket_url'   => array(
			'label'  => __( 'Ticket link', 'mlab-eventmanager' ),
			'type'   => 'url',
			'group'  => 'tickets',
			'button' => __( 'Get tickets', 'mlab-eventmanager' ),
		),
		'show_image'   => array(
			'label'   => __( 'Show image on the event page', 'mlab-eventmanager' ),
			'type'    => 'checkbox',
			'group'   => 'display',
			'default' => '1',
			'details' => false,
			'hint'    => __( 'The event image stays in lists and teasers either way.', 'mlab-eventmanager' ),
		),
		'show_map'     => array(
			'label'   => __( 'Show map (OpenStreetMap)', 'mlab-eventmanager' ),
			'type'    => 'checkbox',
			'group'   => 'display',
			'default' => '',
			'details' => false,
			'hint'    => __( 'Shows a map of the place on the event page. The position is looked up from the address when you save.', 'mlab-eventmanager' ),
		),
	);

	return apply_filters( 'mlab_em_event_fields', $fields );
}

/**
 * Sorts fields into their groups, in group order. Unknown groups come last.
 *
 * @param array<string, array<string, mixed>> $fields Field definitions.
 * @return array<string, array<string, array<string, mixed>>> Group => fields.
 */
function mlab_em_group_fields( array $fields ): array {
	$groups = array_fill_keys( array_keys( mlab_em_field_groups() ), array() );

	foreach ( $fields as $key => $field ) {
		$groups[ $field['group'] ?? 'other' ][ $key ] = $field;
	}

	return array_filter( $groups );
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
 * @param int    $post_id Post ID (event, venue or organizer).
 * @param string $key     Field key.
 */
function mlab_em_get_field( int $post_id, string $key ): string {
	return (string) get_post_meta( $post_id, mlab_em_meta_key( $key ), true );
}

/**
 * Sanitizes one value according to its field type.
 *
 * @param mixed                $value Raw value.
 * @param string               $type  Field type.
 * @param array<string, mixed> $field Field definition, for the allowed post types of linked entries.
 */
function mlab_em_sanitize_value( $value, string $type, array $field = array() ): string {
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
		case 'post':
			$clean = mlab_em_sanitize_linked_post( absint( $raw ), (array) ( $field['post_type'] ?? mlab_em_linked_post_types() ) );
			break;
		case 'checkbox':
			$clean = '1' === $raw ? '1' : '0';
			break;
		case 'latitude':
			$clean = mlab_em_sanitize_coordinate( $raw, 90 );
			break;
		case 'longitude':
			$clean = mlab_em_sanitize_coordinate( $raw, 180 );
			break;
		default:
			$clean = sanitize_text_field( $raw );
	}

	return (string) apply_filters( 'mlab_em_sanitize_value', $clean, $value, $type );
}

/**
 * A venue or organizer ID is only kept if it is a published entry of ours.
 *
 * @param int      $post_id    Post ID, 0 for none.
 * @param string[] $post_types Allowed post types.
 */
function mlab_em_sanitize_linked_post( int $post_id, array $post_types ): string {
	$valid = $post_id && 'publish' === get_post_status( $post_id ) && in_array( get_post_type( $post_id ), $post_types, true );

	return $valid ? (string) $post_id : '';
}

/**
 * A coordinate in decimal degrees within +/- $limit, or empty.
 *
 * @param string $raw   Raw input, comma or point as decimal separator.
 * @param int    $limit Largest allowed absolute value.
 */
function mlab_em_sanitize_coordinate( string $raw, int $limit ): string {
	$raw = str_replace( ',', '.', trim( $raw ) );
	if ( ! is_numeric( $raw ) || abs( (float) $raw ) > $limit ) {
		return '';
	}

	return (string) round( (float) $raw, 6 );
}

/**
 * First required field that is empty after sanitizing, or null if all are set.
 *
 * @param array<string, mixed> $input Raw values keyed by field key.
 */
function mlab_em_missing_required_field( array $input ): ?string {
	foreach ( mlab_em_event_fields() as $key => $field ) {
		if ( ! empty( $field['required'] ) && '' === mlab_em_sanitize_value( $input[ $key ] ?? '', $field['type'], $field ) ) {
			return $key;
		}
	}

	return null;
}

/**
 * Sanitizes the submitted values of the given fields. Fields not submitted are skipped.
 *
 * @param array<string, array<string, mixed>> $fields Field definitions.
 * @param array<string, mixed>                $input  Raw values keyed by field key.
 * @return array<string, string>
 */
function mlab_em_sanitize_values( array $fields, array $input ): array {
	$values = array();

	foreach ( $fields as $key => $field ) {
		if ( array_key_exists( $key, $input ) ) {
			$values[ $key ] = mlab_em_sanitize_value( $input[ $key ], $field['type'], $field );
		}
	}

	return $values;
}

/**
 * Writes sanitized values as post meta.
 *
 * @param int                  $post_id Post ID.
 * @param array<string, mixed> $values  Sanitized values keyed by field key.
 */
function mlab_em_store_values( int $post_id, array $values ): void {
	foreach ( $values as $key => $value ) {
		update_post_meta( $post_id, mlab_em_meta_key( $key ), $value );
	}
}

/**
 * Saves submitted event values. An empty end takes the start, so lists can
 * filter on the end date alone.
 *
 * @param int                  $post_id Event ID.
 * @param array<string, mixed> $input   Raw values keyed by field key.
 */
function mlab_em_save_fields( int $post_id, array $input ): void {
	$values = mlab_em_sanitize_values( mlab_em_event_fields(), $input );

	if ( isset( $values['start'] ) && ( empty( $values['end'] ) || $values['end'] < $values['start'] ) ) {
		$values['end'] = $values['start'];
	}

	mlab_em_store_values( $post_id, $values );
	do_action( 'mlab_em_event_saved', $post_id, $values );
}

/**
 * Prints label, input and hint for one field. Shared by the meta boxes and the phone form.
 * Custom field types render as text input unless "mlab_em_input_type" maps them.
 *
 * @param string               $key   Field key.
 * @param array<string, mixed> $field Field definition.
 * @param string               $value Current value.
 */
function mlab_em_render_input( string $key, array $field, string $value ): void {
	$id = 'mlab-em-' . str_replace( '_', '-', $key );

	printf( '<label class="mlab-em-label" for="%s">%s</label>', esc_attr( $id ), esc_html( $field['label'] ) );
	mlab_em_render_control( $id, 'mlab_em[' . $key . ']', $field, $value );

	if ( ! empty( $field['hint'] ) ) {
		printf( '<span class="mlab-em-hint">%s</span>', esc_html( $field['hint'] ) );
	}
}

/**
 * Prints the form control of a field.
 *
 * @param string               $id    Element ID.
 * @param string               $name  Input name.
 * @param array<string, mixed> $field Field definition.
 * @param string               $value Current value.
 */
function mlab_em_render_control( string $id, string $name, array $field, string $value ): void {
	$required = empty( $field['required'] ) ? '' : ' required';

	if ( 'post' === $field['type'] ) {
		mlab_em_render_post_select( $id, $name, (string) $field['post_type'], $value );
		return;
	}
	if ( 'checkbox' === $field['type'] ) {
		$checked = '' === $value ? (string) ( $field['default'] ?? '' ) : $value;
		printf( '<input type="hidden" name="%s" value="0"><input type="checkbox" id="%s" name="%s" value="1"%s>', esc_attr( $name ), esc_attr( $id ), esc_attr( $name ), checked( $checked, '1', false ) );
		return;
	}
	if ( 'textarea' === $field['type'] ) {
		printf( '<textarea id="%s" name="%s" rows="4"%s>%s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_attr( $required ), esc_textarea( $value ) );
		return;
	}

	$types = array(
		'datetime' => 'datetime-local',
		'url'      => 'url',
	);
	$type  = (string) apply_filters( 'mlab_em_input_type', $types[ $field['type'] ] ?? 'text', $field );
	$value = 'datetime' === $field['type'] ? str_replace( ' ', 'T', $value ) : $value;

	printf( '<input type="%s" id="%s" name="%s" value="%s"%s>', esc_attr( $type ), esc_attr( $id ), esc_attr( $name ), esc_attr( $value ), esc_attr( $required ) );
}

/**
 * Prints a select of published venues or organizers.
 *
 * @param string $id        Element ID.
 * @param string $name      Input name.
 * @param string $post_type Post type to list.
 * @param string $value     Selected post ID.
 */
function mlab_em_render_post_select( string $id, string $name, string $post_type, string $value ): void {
	$posts = get_posts(
		array(
			'post_type'   => $post_type,
			'post_status' => 'publish',
			'numberposts' => 200, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts -- A select of 200 venues is the practical limit.
			'orderby'     => 'title',
			'order'       => 'ASC',
		)
	);

	printf( '<select id="%s" name="%s">', esc_attr( $id ), esc_attr( $name ) );
	printf( '<option value="">%s</option>', esc_html__( '— None —', 'mlab-eventmanager' ) );
	foreach ( $posts as $post ) {
		printf( '<option value="%d"%s>%s</option>', (int) $post->ID, selected( $value, (string) $post->ID, false ), esc_html( get_the_title( $post ) ) );
	}
	echo '</select>';
}

/**
 * Prints fields as paragraphs.
 *
 * @param array<string, array<string, mixed>> $fields  Field definitions.
 * @param callable                            $current Returns the current value for a field key.
 */
function mlab_em_render_field_list( array $fields, callable $current ): void {
	foreach ( $fields as $key => $field ) {
		printf( '<p class="mlab-em-field%s">', 'checkbox' === ( $field['type'] ?? '' ) ? ' mlab-em-field--checkbox' : '' );
		mlab_em_render_input( $key, $field, (string) $current( $key, $field ) );
		echo '</p>';
	}
}

/**
 * Prints fields grouped in fieldsets, each with its group label as legend.
 *
 * @param array<string, array<string, mixed>> $fields  Field definitions.
 * @param callable                            $current Returns the current value for a field key.
 */
function mlab_em_render_field_groups( array $fields, callable $current ): void {
	foreach ( mlab_em_group_fields( $fields ) as $group => $group_fields ) {
		printf( '<fieldset class="mlab-em-group mlab-em-group--%s"><legend>%s</legend>', esc_attr( $group ), esc_html( mlab_em_group_label( $group ) ) );
		mlab_em_render_field_list( $group_fields, $current );
		echo '</fieldset>';
	}
}
