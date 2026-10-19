<?php
/**
 * Quick entry form for phones. Override in <theme>/mlab-eventmanager/event-form.php.
 *
 * @package mlab-eventmanager
 *
 * @var array{fields: array<string, array<string, mixed>>, categories: WP_Term[]|WP_Error, notice: array<string, string>|null} $args
 */

defined( 'ABSPATH' ) || exit;

$mlab_em_notice     = $args['notice'];
$mlab_em_categories = is_array( $args['categories'] ) ? $args['categories'] : array();
?>
<div class="mlab-em-form-wrap">
	<?php
	if ( $mlab_em_notice ) {
		echo mlab_em_get_template( 'event-notice', $mlab_em_notice ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The template escapes its own output.
	}
	?>

	<form class="mlab-em-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="mlab_em_submit">
		<?php wp_nonce_field( 'mlab_em_submit', 'mlab_em_nonce' ); ?>

		<p class="mlab-em-field">
			<label class="mlab-em-label" for="mlab-em-title"><?php esc_html_e( 'Title', 'mlab-eventmanager' ); ?></label>
			<input type="text" id="mlab-em-title" name="mlab_em[title]" required autocomplete="off" enterkeyhint="next">
		</p>

		<?php foreach ( $args['fields'] as $mlab_em_key => $mlab_em_field ) : ?>
			<p class="mlab-em-field">
				<?php mlab_em_render_input( $mlab_em_key, $mlab_em_field, (string) ( $mlab_em_field['default'] ?? '' ) ); ?>
			</p>
		<?php endforeach; ?>

		<?php if ( $mlab_em_categories ) : ?>
			<p class="mlab-em-field">
				<label class="mlab-em-label" for="mlab-em-category"><?php esc_html_e( 'Category', 'mlab-eventmanager' ); ?></label>
				<select id="mlab-em-category" name="mlab_em[category]">
					<option value="0"><?php esc_html_e( 'No category', 'mlab-eventmanager' ); ?></option>
					<?php foreach ( $mlab_em_categories as $mlab_em_term ) : ?>
						<option value="<?php echo esc_attr( (string) $mlab_em_term->term_id ); ?>"><?php echo esc_html( $mlab_em_term->name ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
		<?php endif; ?>

		<p class="mlab-em-field">
			<label class="mlab-em-label" for="mlab-em-image"><?php esc_html_e( 'Photo', 'mlab-eventmanager' ); ?></label>
			<input type="file" id="mlab-em-image" name="mlab_em_image" accept="image/*">
		</p>

		<p class="mlab-em-field">
			<label class="mlab-em-label" for="mlab-em-description"><?php esc_html_e( 'Description', 'mlab-eventmanager' ); ?></label>
			<textarea id="mlab-em-description" name="mlab_em[description]" rows="4"></textarea>
		</p>

		<?php do_action( 'mlab_em_form_fields_after' ); ?>

		<div class="mlab-em-form__actions">
			<button type="submit" class="mlab-em-button"><?php esc_html_e( 'Save event', 'mlab-eventmanager' ); ?></button>
		</div>
	</form>
</div>
