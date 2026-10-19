<?php
/**
 * CSV export of events under Events > Export. Opens directly in Excel.
 *
 * @package mlab-eventmanager
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'mlab_em_add_export_page' );
add_action( 'admin_post_mlab_em_export', 'mlab_em_handle_export' );

/**
 * Capability needed to export events.
 */
function mlab_em_export_capability(): string {
	return (string) apply_filters( 'mlab_em_export_capability', 'edit_others_posts' );
}

/**
 * Adds the submenu page.
 */
function mlab_em_add_export_page(): void {
	add_submenu_page(
		'edit.php?post_type=' . MLAB_EM_POST_TYPE,
		__( 'Export events', 'mlab-eventmanager' ),
		__( 'Export', 'mlab-eventmanager' ),
		mlab_em_export_capability(),
		'mlab-em-export',
		'mlab_em_render_export_page'
	);
}

/**
 * Prints the export page.
 */
function mlab_em_render_export_page(): void {
	if ( ! current_user_can( mlab_em_export_capability() ) ) {
		return;
	}
	?>
	<div class="wrap mlab-em-wrap">
		<h1><?php esc_html_e( 'Export events', 'mlab-eventmanager' ); ?></h1>
		<p><?php esc_html_e( 'Downloads a CSV file that opens in Excel, Numbers and Google Sheets.', 'mlab-eventmanager' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="mlab_em_export">
			<?php wp_nonce_field( 'mlab_em_export', 'mlab_em_nonce' ); ?>
			<p>
				<label for="mlab-em-export-show"><?php esc_html_e( 'Which events', 'mlab-eventmanager' ); ?></label><br>
				<select id="mlab-em-export-show" name="show">
					<option value="all"><?php esc_html_e( 'All events', 'mlab-eventmanager' ); ?></option>
					<option value="upcoming"><?php esc_html_e( 'Upcoming events', 'mlab-eventmanager' ); ?></option>
					<option value="past"><?php esc_html_e( 'Past events', 'mlab-eventmanager' ); ?></option>
				</select>
			</p>
			<?php submit_button( __( 'Download CSV', 'mlab-eventmanager' ) ); ?>
		</form>
	</div>
	<?php
}

/**
 * Column headings and the function that reads each column from an event.
 *
 * @return array<string, callable> Heading => reader taking the event.
 */
function mlab_em_export_columns(): array {
	$columns = array(
		'ID'                                  => static fn( WP_Post $event ) => (string) $event->ID,
		__( 'Title', 'mlab-eventmanager' )    => static fn( WP_Post $event ) => mlab_em_plain_title( $event->ID ),
		__( 'Status', 'mlab-eventmanager' )   => static fn( WP_Post $event ) => mlab_em_status_label( $event->post_status ),
		__( 'Category', 'mlab-eventmanager' ) => static fn( WP_Post $event ) => implode( ', ', wp_list_pluck( (array) get_the_terms( $event, MLAB_EM_CATEGORY ), 'name' ) ),
	);

	foreach ( mlab_em_event_fields() as $key => $field ) {
		$columns[ $field['label'] ] = static fn( WP_Post $event ) => 'datetime' === $field['type'] ? mlab_em_get_field( $event->ID, $key ) : mlab_em_display_value( $event->ID, $key, $field['type'] );
	}
	$columns[ __( 'Address of venue', 'mlab-eventmanager' ) ] = static fn( WP_Post $event ) => mlab_em_get_place( $event->ID )['address'];
	$columns[ __( 'Link', 'mlab-eventmanager' ) ]             = static fn( WP_Post $event ) => (string) get_permalink( $event );

	return apply_filters( 'mlab_em_export_columns', $columns );
}

/**
 * Stops spreadsheets from running text as a formula.
 *
 * @param string $value Cell text.
 */
function mlab_em_csv_safe( string $value ): string {
	return preg_match( '/^[=+\-@\t\r]/', $value ) ? "'" . $value : $value;
}

/**
 * Sends the CSV file.
 */
function mlab_em_handle_export(): void {
	check_admin_referer( 'mlab_em_export', 'mlab_em_nonce' );

	if ( ! current_user_can( mlab_em_export_capability() ) ) {
		wp_die( esc_html__( 'You may not export events.', 'mlab-eventmanager' ), '', array( 'response' => 403 ) );
	}

	$show   = isset( $_POST['show'] ) ? sanitize_key( $_POST['show'] ) : 'all';
	$show   = in_array( $show, array( 'upcoming', 'past' ), true ) ? $show : 'all';
	$events = get_posts(
		array(
			'post_type'      => MLAB_EM_POST_TYPE,
			'post_status'    => mlab_em_event_statuses(),
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		) + mlab_em_date_query_args( $show )
	);

	$columns   = mlab_em_export_columns();
	$delimiter = (string) apply_filters( 'mlab_em_export_delimiter', ';' );

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="events-' . gmdate( 'Y-m-d' ) . '.csv"' );

	$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Streams the download.
	fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- UTF-8 marker so Excel reads umlauts.
	fputcsv( $out, array_keys( $columns ), $delimiter, '"', '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
	foreach ( $events as $event ) {
		fputcsv( $out, array_map( static fn( $read ) => mlab_em_csv_safe( (string) $read( $event ) ), $columns ), $delimiter, '"', '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
	}
	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	exit;
}
