<?php
/**
 * Login shown to guests on the quick entry page. Override in <theme>/mlab-eventmanager/event-login.php.
 *
 * @package mlab-eventmanager
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="mlab-em-form-wrap mlab-em-login">
	<p><?php esc_html_e( 'Please log in to add events. You stay logged in on this device.', 'mlab-eventmanager' ); ?></p>
	<?php
	wp_login_form(
		array(
			'redirect'       => (string) get_permalink(),
			'remember'       => true,
			'value_remember' => true,
		)
	);
	?>
</div>
