<?php
/**
 * Filter bar above the event list. Override in <theme>/mlab-eventmanager/event-filters.php.
 *
 * @package mlab-eventmanager
 *
 * @var array{values: array{q: string, cat: string, from: string}, show_category: bool} $args
 */

defined( 'ABSPATH' ) || exit;

$mlab_em_values     = $args['values'];
$mlab_em_categories = get_terms( array( 'taxonomy' => MLAB_EM_CATEGORY ) );
$mlab_em_active     = array_filter( $mlab_em_values );
?>
<form class="mlab-em-filters" method="get" action="<?php echo esc_url( (string) get_permalink() ); ?>">
	<?php if ( ! get_option( 'permalink_structure' ) ) : ?>
		<input type="hidden" name="page_id" value="<?php echo esc_attr( (string) get_the_ID() ); ?>">
	<?php endif; ?>

	<label class="mlab-em-filters__field">
		<span class="mlab-em-label"><?php esc_html_e( 'Search', 'mlab-eventmanager' ); ?></span>
		<input type="search" name="mlab_em_q" value="<?php echo esc_attr( $mlab_em_values['q'] ); ?>">
	</label>

	<?php if ( $args['show_category'] && is_array( $mlab_em_categories ) && $mlab_em_categories ) : ?>
		<label class="mlab-em-filters__field">
			<span class="mlab-em-label"><?php esc_html_e( 'Category', 'mlab-eventmanager' ); ?></span>
			<select name="mlab_em_cat">
				<option value=""><?php esc_html_e( 'All categories', 'mlab-eventmanager' ); ?></option>
				<?php foreach ( $mlab_em_categories as $mlab_em_term ) : ?>
					<option value="<?php echo esc_attr( $mlab_em_term->slug ); ?>"<?php selected( $mlab_em_values['cat'], $mlab_em_term->slug ); ?>><?php echo esc_html( $mlab_em_term->name ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
	<?php endif; ?>

	<label class="mlab-em-filters__field">
		<span class="mlab-em-label"><?php esc_html_e( 'From', 'mlab-eventmanager' ); ?></span>
		<input type="date" name="mlab_em_from" value="<?php echo esc_attr( $mlab_em_values['from'] ); ?>">
	</label>

	<div class="mlab-em-filters__actions">
		<button type="submit" class="mlab-em-button"><?php esc_html_e( 'Filter', 'mlab-eventmanager' ); ?></button>
		<?php if ( $mlab_em_active ) : ?>
			<a class="mlab-em-link" href="<?php echo esc_url( (string) get_permalink() ); ?>"><?php esc_html_e( 'Reset', 'mlab-eventmanager' ); ?></a>
		<?php endif; ?>
	</div>
</form>
