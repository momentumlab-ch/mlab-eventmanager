<?php
/**
 * Migration from WP Event Manager (Events > Migration).
 *
 * Copies events, venues and organizers into this plugin. The source data is never changed or
 * deleted, and events that were already copied are skipped, so the migration can be run again.
 *
 * @package mlab-eventmanager
 */

defined( 'ABSPATH' ) || exit;

const MLAB_EM_SOURCE_EVENT     = 'event_listing';
const MLAB_EM_SOURCE_VENUE     = 'event_venue';
const MLAB_EM_SOURCE_ORGANIZER = 'event_organizer';
const MLAB_EM_SOURCE_META      = '_mlab_em_migrated_from';
const MLAB_EM_MIGRATION_BATCH  = 50;
const MLAB_EM_SKIPPED_OPTION   = 'mlab_em_migration_skipped';

add_action( 'admin_menu', 'mlab_em_add_migration_page' );
add_action( 'admin_post_mlab_em_migrate', 'mlab_em_handle_migration' );

/**
 * Adds the submenu page, only for administrators.
 */
function mlab_em_add_migration_page(): void {
	add_submenu_page(
		'edit.php?post_type=' . MLAB_EM_POST_TYPE,
		__( 'Migration from WP Event Manager', 'mlab-eventmanager' ),
		__( 'Migration', 'mlab-eventmanager' ),
		'manage_options',
		'mlab-em-migration',
		'mlab_em_render_migration_page'
	);
}

/**
 * Source post statuses we copy. "preview" are unfinished submissions and are left out.
 *
 * @return string[]
 */
function mlab_em_source_statuses(): array {
	return array_merge( mlab_em_event_statuses(), array( 'expired' ) );
}

/**
 * IDs of source posts that were copied already or skipped for lack of a date.
 *
 * @return int[]
 */
function mlab_em_migration_done_ids(): array {
	global $wpdb;

	$copied = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reads the copied IDs once per page view.
		$wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s", MLAB_EM_SOURCE_META )
	);

	return array_map( 'absint', array_merge( $copied, (array) get_option( MLAB_EM_SKIPPED_OPTION, array() ) ) );
}

/**
 * Source events that still have to be copied, oldest first.
 *
 * @param int $limit Maximum number of events, -1 for all.
 * @return WP_Post[]
 */
function mlab_em_source_events( int $limit = -1 ): array {
	mlab_em_migration_prepare_source();
	$done = mlab_em_migration_done_ids();

	return get_posts(
		array(
			'post_type'      => MLAB_EM_SOURCE_EVENT,
			'post_status'    => mlab_em_source_statuses(),
			'posts_per_page' => $limit,
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'no_found_rows'  => true,
			'post__not_in'   => $done ? $done : array( 0 ), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Skips events that were copied already.
		)
	);
}

/**
 * Number of source posts by type, all copied statuses together.
 *
 * @param string $post_type Source post type.
 */
function mlab_em_source_count( string $post_type ): int {
	mlab_em_migration_prepare_source();

	$query = new WP_Query(
		array(
			'post_type'      => $post_type,
			'post_status'    => mlab_em_source_statuses(),
			'fields'         => 'ids',
			'posts_per_page' => 1,
		)
	);

	return (int) $query->found_posts;
}

/**
 * Number of events that were copied already.
 */
function mlab_em_migrated_count(): int {
	$query = new WP_Query(
		array(
			'post_type'      => MLAB_EM_POST_TYPE,
			'post_status'    => 'any',
			'fields'         => 'ids',
			'posts_per_page' => 1,
			'meta_key'       => MLAB_EM_SOURCE_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Counts copied events once per page view.
		)
	);

	return (int) $query->found_posts;
}

/**
 * Converts a WP Event Manager date to the site timezone.
 *
 * @param string $date     Stored date, e.g. "2026-10-24 19:00:00".
 * @param string $time     Stored time of day, used when the date has none.
 * @param string $timezone Stored timezone: an identifier like "Europe/Zurich" or "UTC+2".
 * @return string Date in storage format, empty if it cannot be read.
 */
function mlab_em_migration_date( string $date, string $time, string $timezone ): string {
	if ( '' === trim( $date ) ) {
		return '';
	}

	try {
		$value = new DateTimeImmutable( $date, mlab_em_migration_timezone( $timezone ) );
		if ( '00:00:00' === $value->format( 'H:i:s' ) && '' !== trim( $time ) ) {
			$parts = date_parse( $time );
			$value = $value->setTime( (int) $parts['hour'], (int) $parts['minute'] );
		}
	} catch ( Exception $e ) {
		return '';
	}

	return $value->setTimezone( wp_timezone() )->format( MLAB_EM_DATE_FORMAT );
}

/**
 * Timezone of a stored value. Unknown values mean the site timezone.
 *
 * @param string $raw Stored timezone.
 */
function mlab_em_migration_timezone( string $raw ): DateTimeZone {
	$raw = trim( $raw );

	if ( in_array( $raw, timezone_identifiers_list(), true ) ) {
		return new DateTimeZone( $raw );
	}
	if ( preg_match( '/^UTC([+-]\d{1,2})(?::?(\d{2}))?$/', $raw, $match ) ) {
		return new DateTimeZone( sprintf( '%+03d:%02d', (int) $match[1], (int) ( $match[2] ?? 0 ) ) );
	}

	return wp_timezone();
}

/**
 * Meta value of a source post.
 *
 * @param int    $post_id Source post ID.
 * @param string $key     Meta key.
 */
function mlab_em_source_meta( int $post_id, string $key ): string {
	$value = get_post_meta( $post_id, $key, true );

	return is_scalar( $value ) ? trim( (string) $value ) : '';
}

/**
 * IDs of a stored list of linked posts.
 *
 * @param int    $post_id Source event ID.
 * @param string $key     Meta key.
 * @return int[]
 */
function mlab_em_source_linked_ids( int $post_id, string $key ): array {
	return array_values( array_filter( array_map( 'absint', (array) get_post_meta( $post_id, $key, true ) ) ) );
}

/**
 * Price text from the ticket settings.
 *
 * @param int $post_id Source event ID.
 */
function mlab_em_migration_price( int $post_id ): string {
	if ( 'free' === mlab_em_source_meta( $post_id, '_event_ticket_options' ) ) {
		return __( 'Free', 'mlab-eventmanager' );
	}

	return mlab_em_source_meta( $post_id, '_event_ticket_price' );
}

/**
 * Maps one source event to our fields, without writing anything.
 *
 * @param WP_Post $source Source event.
 * @return array{fields: array<string, string>, warnings: string[], venue: int, organizer: int}
 */
function mlab_em_migration_map_event( WP_Post $source ): array {
	$id       = $source->ID;
	$timezone = mlab_em_source_meta( $id, '_event_timezone' );
	$register = mlab_em_source_meta( $id, '_registration' );
	$warnings = array();

	$fields = array(
		'start'      => mlab_em_migration_date( mlab_em_source_meta( $id, '_event_start_date' ), mlab_em_source_meta( $id, '_event_start_time' ), $timezone ),
		'end'        => mlab_em_migration_date( mlab_em_source_meta( $id, '_event_end_date' ), mlab_em_source_meta( $id, '_event_end_time' ), $timezone ),
		'location'   => mlab_em_source_meta( $id, '_event_location' ),
		'address'    => mlab_em_source_meta( $id, '_event_address' ),
		'price'      => mlab_em_migration_price( $id ),
		'ticket_url' => preg_match( '#^https?://#i', $register ) ? $register : '',
	);

	if ( '' === $fields['start'] ) {
		$warnings[] = __( 'No readable start date.', 'mlab-eventmanager' );
	}
	if ( '' !== $register && '' === $fields['ticket_url'] ) {
		$warnings[] = __( 'The registration e-mail address cannot be copied.', 'mlab-eventmanager' );
	}
	if ( 'yes' === mlab_em_source_meta( $id, '_event_online' ) ) {
		$warnings[] = __( 'Marked as online, but there is no online link to copy.', 'mlab-eventmanager' );
	}

	$venues     = mlab_em_source_linked_ids( $id, '_event_venue_ids' );
	$organizers = mlab_em_source_linked_ids( $id, '_event_organizer_ids' );
	if ( count( $organizers ) > 1 ) {
		$warnings[] = __( 'More than one organizer: only the first is copied.', 'mlab-eventmanager' );
	}

	return array(
		'fields'    => $fields,
		'warnings'  => $warnings,
		'venue'     => $venues[0] ?? 0,
		'organizer' => $organizers[0] ?? 0,
	);
}

/**
 * Our entry for a source venue or organizer. It is created on first use.
 *
 * @param int    $source_id   Source post ID.
 * @param string $target_type Our post type.
 * @return int Our post ID, 0 if the source does not exist.
 */
function mlab_em_migration_linked( int $source_id, string $target_type ): int {
	$existing = mlab_em_find_migrated( $source_id, $target_type );
	$source   = get_post( $source_id );
	if ( $existing || ! $source ) {
		return $existing;
	}

	$new_id = wp_insert_post(
		array(
			'post_type'   => $target_type,
			'post_title'  => $source->post_title,
			'post_status' => 'publish',
		)
	);
	if ( ! $new_id || is_wp_error( $new_id ) ) {
		return 0;
	}

	if ( MLAB_EM_VENUE === $target_type ) {
		$address = trim( mlab_em_source_meta( $source_id, '_venue_location' ) . ' ' . mlab_em_source_meta( $source_id, '_venue_zipcode' ) );
		$input   = array(
			'address' => $address,
			'url'     => mlab_em_source_meta( $source_id, '_venue_website' ),
		);
	} else {
		$input = array( 'url' => mlab_em_source_meta( $source_id, '_organizer_website' ) );
	}

	mlab_em_store_values( $new_id, mlab_em_sanitize_values( mlab_em_linked_fields( $target_type ), $input ) );
	update_post_meta( $new_id, MLAB_EM_SOURCE_META, $source_id );

	return (int) $new_id;
}

/**
 * Our post that was created from a source post, 0 if none.
 *
 * @param int    $source_id   Source post ID.
 * @param string $target_type Our post type.
 */
function mlab_em_find_migrated( int $source_id, string $target_type ): int {
	$found = get_posts(
		array(
			'post_type'      => $target_type,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Looks up the copy of one source post.
				array(
					'key'   => MLAB_EM_SOURCE_META,
					'value' => $source_id,
				),
			),
		)
	);

	return (int) ( $found[0] ?? 0 );
}

/**
 * Makes the types and statuses of WP Event Manager known for this request, so its data can be
 * read when that plugin is not active. Nothing is stored.
 */
function mlab_em_migration_prepare_source(): void {
	foreach ( array( 'event_listing_category', 'event_listing_type' ) as $taxonomy ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			register_taxonomy( $taxonomy, MLAB_EM_SOURCE_EVENT, array( 'public' => false ) );
		}
	}
	foreach ( array( 'expired', 'preview' ) as $status ) {
		if ( ! get_post_status_object( $status ) ) {
			register_post_status( $status, array( 'public' => false ) );
		}
	}
}

/**
 * Copies the terms of a source event: categories to categories, types to tags.
 *
 * @param int $source_id Source event ID.
 * @param int $new_id    Our event ID.
 */
function mlab_em_migration_copy_terms( int $source_id, int $new_id ): void {
	$map = array(
		'event_listing_category' => MLAB_EM_CATEGORY,
		'event_listing_type'     => MLAB_EM_TAG,
	);

	foreach ( $map as $from => $to ) {
		$names = wp_get_object_terms( $source_id, $from, array( 'fields' => 'names' ) );
		if ( is_array( $names ) && $names ) {
			wp_set_object_terms( $new_id, $names, $to );
		}
	}
}

/**
 * Copies the featured image, or the banner if it is a media library file.
 *
 * @param int $source_id Source event ID.
 * @param int $new_id    Our event ID.
 */
function mlab_em_migration_copy_image( int $source_id, int $new_id ): void {
	$thumbnail = (int) get_post_thumbnail_id( $source_id );
	if ( ! $thumbnail ) {
		$banner    = get_post_meta( $source_id, '_event_banner', true );
		$banner    = is_array( $banner ) ? (string) reset( $banner ) : (string) $banner;
		$thumbnail = '' === $banner ? 0 : (int) attachment_url_to_postid( $banner );
	}
	if ( $thumbnail ) {
		set_post_thumbnail( $new_id, $thumbnail );
	}
}

/**
 * Creates our event from a source event.
 *
 * @param WP_Post                                                          $source Source event.
 * @param array{fields: array<string, string>, venue: int, organizer: int} $mapped Result of mlab_em_migration_map_event().
 * @return int New event ID, 0 on failure.
 */
function mlab_em_migration_create_event( WP_Post $source, array $mapped ): int {
	$new_id = wp_insert_post(
		array(
			'post_type'     => MLAB_EM_POST_TYPE,
			'post_title'    => $source->post_title,
			'post_name'     => $source->post_name,
			'post_content'  => $source->post_content,
			'post_excerpt'  => $source->post_excerpt,
			'post_status'   => 'expired' === $source->post_status ? 'publish' : $source->post_status,
			'post_author'   => get_userdata( (int) $source->post_author ) ? (int) $source->post_author : get_current_user_id(),
			'post_date'     => $source->post_date,
			'post_date_gmt' => $source->post_date_gmt,
		)
	);
	if ( ! $new_id || is_wp_error( $new_id ) ) {
		return 0;
	}

	$input                 = $mapped['fields'];
	$input['venue_id']     = (string) mlab_em_migration_linked( $mapped['venue'], MLAB_EM_VENUE );
	$input['organizer_id'] = (string) mlab_em_migration_linked( $mapped['organizer'], MLAB_EM_ORGANIZER );

	mlab_em_save_fields( (int) $new_id, $input );
	mlab_em_migration_copy_terms( $source->ID, (int) $new_id );
	mlab_em_migration_copy_image( $source->ID, (int) $new_id );
	update_post_meta( $new_id, MLAB_EM_SOURCE_META, $source->ID );
	do_action( 'mlab_em_event_migrated', (int) $new_id, $source->ID );

	return (int) $new_id;
}

/**
 * Remembers an event that cannot be copied, so the next run does not try it again.
 *
 * @param int $source_id Source event ID.
 */
function mlab_em_migration_remember_skipped( int $source_id ): void {
	$skipped = array_map( 'absint', (array) get_option( MLAB_EM_SKIPPED_OPTION, array() ) );
	update_option( MLAB_EM_SKIPPED_OPTION, array_values( array_unique( array_merge( $skipped, array( $source_id ) ) ) ), false );
}

/**
 * Copies the next batch of events, or only checks them.
 *
 * @param bool $dry_run True to change nothing.
 * @return array{copied: int, skipped: int, remaining: int, dry_run: bool, rows: array<int, array{title: string, start: string, warnings: string[], skipped: bool}>}
 */
function mlab_em_migration_run( bool $dry_run ): array {
	$result = array(
		'copied'    => 0,
		'skipped'   => 0,
		'remaining' => 0,
		'dry_run'   => $dry_run,
		'rows'      => array(),
	);
	$batch  = mlab_em_source_events( MLAB_EM_MIGRATION_BATCH );

	foreach ( $batch as $source ) {
		$mapped  = mlab_em_migration_map_event( $source );
		$skipped = '' === $mapped['fields']['start'];

		if ( $skipped ) {
			++$result['skipped'];
			if ( ! $dry_run ) {
				mlab_em_migration_remember_skipped( $source->ID );
			}
		} elseif ( $dry_run || mlab_em_migration_create_event( $source, $mapped ) ) {
			++$result['copied'];
		}

		$result['rows'][] = array(
			'title'    => get_the_title( $source ),
			'start'    => $mapped['fields']['start'],
			'warnings' => $mapped['warnings'],
			'skipped'  => $skipped,
		);
	}

	$result['remaining'] = count( mlab_em_source_events() );

	return $result;
}

/**
 * Runs the migration from the form and shows the result on the page.
 */
function mlab_em_handle_migration(): void {
	check_admin_referer( 'mlab_em_migrate', 'mlab_em_nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You may not run the migration.', 'mlab-eventmanager' ), '', array( 'response' => 403 ) );
	}

	if ( isset( $_POST['mlab_em_retry_skipped'] ) ) {
		delete_option( MLAB_EM_SKIPPED_OPTION );
	}

	$dry_run = isset( $_POST['mlab_em_dry_run'] );
	set_transient( 'mlab_em_migration_' . get_current_user_id(), mlab_em_migration_run( $dry_run ), 10 * MINUTE_IN_SECONDS );

	wp_safe_redirect( admin_url( 'edit.php?post_type=' . MLAB_EM_POST_TYPE . '&page=mlab-em-migration' ) );
	exit;
}

/**
 * Prints the migration page.
 */
function mlab_em_render_migration_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$key    = 'mlab_em_migration_' . get_current_user_id();
	$result = get_transient( $key );
	delete_transient( $key );

	echo mlab_em_get_template( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The template escapes its own output.
		'admin-migration',
		array(
			'found'      => mlab_em_source_count( MLAB_EM_SOURCE_EVENT ),
			'venues'     => mlab_em_source_count( MLAB_EM_SOURCE_VENUE ),
			'organizers' => mlab_em_source_count( MLAB_EM_SOURCE_ORGANIZER ),
			'migrated'   => mlab_em_migrated_count(),
			'pending'    => count( mlab_em_source_events() ),
			'skipped'    => count( (array) get_option( MLAB_EM_SKIPPED_OPTION, array() ) ),
			'result'     => is_array( $result ) ? $result : null,
		)
	);
}
