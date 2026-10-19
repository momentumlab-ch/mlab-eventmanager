<?php
/**
 * Form to add or change an event. Override in <theme>/mlab-eventmanager/event-form.php.
 *
 * @package mlab-eventmanager
 *
 * @var array{event: WP_Post|null, categories: WP_Term[]|WP_Error, category: int} $args
 */

defined( 'ABSPATH' ) || exit;

$mlab_em_event      = $args['event'];
$mlab_em_categories = is_array( $args['categories'] ) ? $args['categories'] : array();
$mlab_em_content    = $mlab_em_event ? $mlab_em_event->post_content : '';
$mlab_em_plain      = mlab_em_description_editable( $mlab_em_content );
$mlab_em_all        = mlab_em_event_fields();
$mlab_em_quick      = array_filter( $mlab_em_all, static fn( $field ) => ! empty( $field['quick'] ) || ! empty( $field['required'] ) );
$mlab_em_more       = array_diff_key( $mlab_em_all, $mlab_em_quick );
$mlab_em_value      = static fn( $key, $field ) => $mlab_em_event ? mlab_em_get_field( $mlab_em_event->ID, $key ) : (string) ( $field['default'] ?? '' );
?>
<form class="mlab-em-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="<?php echo $mlab_em_event ? 'mlab_em_update' : 'mlab_em_submit'; ?>">
	<?php wp_nonce_field( $mlab_em_event ? 'mlab_em_update' : 'mlab_em_submit', 'mlab_em_nonce' ); ?>
	<?php if ( $mlab_em_event ) : ?>
		<input type="hidden" name="mlab_em_event" value="<?php echo esc_attr( (string) $mlab_em_event->ID ); ?>">
	<?php endif; ?>

	<p class="mlab-em-field">
		<label class="mlab-em-label" for="mlab-em-title"><?php esc_html_e( 'Title', 'mlab-eventmanager' ); ?></label>
		<input type="text" id="mlab-em-title" name="mlab_em[title]" value="<?php echo esc_attr( $mlab_em_event ? $mlab_em_event->post_title : '' ); ?>" required autocomplete="off" enterkeyhint="next">
	</p>

	<?php mlab_em_render_field_groups( $mlab_em_quick, $mlab_em_value ); ?>

	<fieldset class="mlab-em-group">
		<legend><?php esc_html_e( 'About the event', 'mlab-eventmanager' ); ?></legend>

		<?php if ( $mlab_em_categories ) : ?>
			<p class="mlab-em-field">
				<label class="mlab-em-label" for="mlab-em-category"><?php esc_html_e( 'Category', 'mlab-eventmanager' ); ?></label>
				<select id="mlab-em-category" name="mlab_em[category]">
					<option value="0"><?php esc_html_e( 'No category', 'mlab-eventmanager' ); ?></option>
					<?php foreach ( $mlab_em_categories as $mlab_em_term ) : ?>
						<option value="<?php echo esc_attr( (string) $mlab_em_term->term_id ); ?>"<?php selected( $args['category'], $mlab_em_term->term_id ); ?>><?php echo esc_html( $mlab_em_term->name ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
		<?php endif; ?>

		<p class="mlab-em-field">
			<label class="mlab-em-label" for="mlab-em-image"><?php esc_html_e( 'Photo', 'mlab-eventmanager' ); ?></label>
			<?php if ( $mlab_em_event && has_post_thumbnail( $mlab_em_event->ID ) ) : ?>
				<?php echo get_the_post_thumbnail( $mlab_em_event->ID, 'thumbnail', array( 'class' => 'mlab-em-form__thumb' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core builds the image tag with escaped attributes. ?>
				<span class="mlab-em-hint"><?php esc_html_e( 'Choose a new photo to replace the current one.', 'mlab-eventmanager' ); ?></span>
			<?php endif; ?>
			<input type="file" id="mlab-em-image" name="mlab_em_image" accept="image/*">
		</p>

		<?php if ( $mlab_em_plain ) : ?>
			<p class="mlab-em-field">
				<label class="mlab-em-label" for="mlab-em-description"><?php esc_html_e( 'Description', 'mlab-eventmanager' ); ?></label>
				<textarea id="mlab-em-description" name="mlab_em[description]" rows="4"><?php echo esc_textarea( $mlab_em_content ); ?></textarea>
			</p>
		<?php else : ?>
			<p class="mlab-em-hint"><?php esc_html_e( 'The description has formatting and can only be changed in the backend.', 'mlab-eventmanager' ); ?></p>
		<?php endif; ?>
	</fieldset>

	<?php if ( $mlab_em_more ) : ?>
		<details class="mlab-em-more"<?php echo $mlab_em_event ? ' open' : ''; ?>>
			<summary><?php esc_html_e( 'More details', 'mlab-eventmanager' ); ?></summary>
			<?php mlab_em_render_field_groups( $mlab_em_more, $mlab_em_value ); ?>
		</details>
	<?php endif; ?>

	<?php do_action( 'mlab_em_form_fields_after', $mlab_em_event ); ?>

	<div class="mlab-em-form__actions">
		<button type="submit" class="mlab-em-button"><?php echo $mlab_em_event ? esc_html__( 'Save changes', 'mlab-eventmanager' ) : esc_html__( 'Save event', 'mlab-eventmanager' ); ?></button>
		<?php if ( $mlab_em_event ) : ?>
			<a class="mlab-em-link" href="<?php echo esc_url( mlab_em_my_events_url( 'mine' ) ); ?>"><?php esc_html_e( 'Cancel', 'mlab-eventmanager' ); ?></a>
		<?php endif; ?>
	</div>
</form>
