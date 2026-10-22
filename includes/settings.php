<?php
/**
 * Settings → K-Builder: lead form and PDF texts.
 */

defined( 'ABSPATH' ) || exit;

// Name of the Fluent Forms hidden field that receives the chosen design.
define( 'KBUILDER_DESIGN_FIELD', 'kbuilder_design' );

add_action( 'admin_menu', 'kbuilder_add_settings_page' );
add_action( 'admin_init', 'kbuilder_register_settings' );
add_filter( 'plugin_action_links_' . plugin_basename( KBUILDER_FILE ), 'kbuilder_settings_link' );

function kbuilder_get_settings() {
	$defaults = array(
		'form_id'     => 0,
		'pdf_contact' => __( 'Un asesor de Karleton se pondrá en contacto contigo para resolver tus dudas y dar seguimiento a tu proyecto.', 'kbuilder' ),
	);

	return wp_parse_args( (array) get_option( 'kbuilder_settings', array() ), $defaults );
}

function kbuilder_register_settings() {
	register_setting(
		'kbuilder',
		'kbuilder_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'kbuilder_sanitize_settings',
			'default'           => array(),
		)
	);
}

function kbuilder_sanitize_settings( $input ) {
	$input = (array) $input;

	return array(
		'form_id'     => absint( $input['form_id'] ?? 0 ),
		'pdf_contact' => sanitize_textarea_field( $input['pdf_contact'] ?? '' ),
	);
}

function kbuilder_add_settings_page() {
	add_options_page( 'K-Builder', 'K-Builder', 'manage_options', 'kbuilder', 'kbuilder_render_settings_page' );
}

function kbuilder_settings_link( $links ) {
	$url = admin_url( 'options-general.php?page=kbuilder' );
	array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'kbuilder' ) . '</a>' );

	return $links;
}

/**
 * Published Fluent Forms, or an empty array when the plugin isn't active.
 */
function kbuilder_get_fluent_forms() {
	global $wpdb;

	if ( ! defined( 'FLUENTFORM' ) ) {
		return array();
	}

	$table = $wpdb->prefix . 'fluentform_forms';

	return $wpdb->get_results( "SELECT id, title FROM {$table} ORDER BY title ASC" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
}

function kbuilder_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings = kbuilder_get_settings();
	$forms    = kbuilder_get_fluent_forms();
	$form_ids = wp_list_pluck( $forms, 'id' );
	?>
	<div class="wrap">
		<h1>K-Builder</h1>

		<?php if ( ! defined( 'FLUENTFORM' ) ) : ?>
			<div class="notice notice-warning inline">
				<p><?php esc_html_e( 'Fluent Forms is not active. Until it is, visitors download the PDF without leaving their contact details.', 'kbuilder' ); ?></p>
			</div>
		<?php endif; ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'kbuilder' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="kbuilder-form-id"><?php esc_html_e( 'Lead form', 'kbuilder' ); ?></label></th>
					<td>
						<?php if ( $forms ) : ?>
							<select id="kbuilder-form-id" name="kbuilder_settings[form_id]">
								<option value="0"><?php esc_html_e( '— None: download without a form —', 'kbuilder' ); ?></option>
								<?php if ( $settings['form_id'] && ! in_array( (string) $settings['form_id'], $form_ids, true ) ) : ?>
									<option value="<?php echo esc_attr( $settings['form_id'] ); ?>" selected>
										<?php /* translators: %d: form ID */ printf( esc_html__( 'Form #%d (not found)', 'kbuilder' ), (int) $settings['form_id'] ); ?>
									</option>
								<?php endif; ?>
								<?php foreach ( $forms as $form ) : ?>
									<option value="<?php echo esc_attr( $form->id ); ?>" <?php selected( (int) $settings['form_id'], (int) $form->id ); ?>>
										<?php echo esc_html( sprintf( '%s (#%d)', $form->title, $form->id ) ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						<?php else : ?>
							<input type="number" min="0" class="small-text" id="kbuilder-form-id" name="kbuilder_settings[form_id]" value="<?php echo esc_attr( $settings['form_id'] ); ?>">
						<?php endif; ?>
						<p class="description">
							<?php esc_html_e( 'Visitors fill this Fluent Forms form before the PDF downloads. Recommended fields: Name, Email and Phone.', 'kbuilder' ); ?><br>
							<?php esc_html_e( 'In the form settings, set Confirmation Type to "Same Page" (a redirect would interrupt the download).', 'kbuilder' ); ?><br>
							<?php
							printf(
								/* translators: %s: hidden field name */
								esc_html__( 'Optional: add a Hidden field with the name attribute %s to save the chosen design with each entry (and use it in the email notification).', 'kbuilder' ),
								'<code>' . esc_html( KBUILDER_DESIGN_FIELD ) . '</code>'
							);
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="kbuilder-pdf-contact"><?php esc_html_e( 'PDF contact text', 'kbuilder' ); ?></label></th>
					<td>
						<textarea id="kbuilder-pdf-contact" class="large-text" rows="4" name="kbuilder_settings[pdf_contact]"><?php echo esc_textarea( $settings['pdf_contact'] ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Printed under "Próximos pasos" in the PDF: phone, WhatsApp, showroom address, etc.', 'kbuilder' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Usage', 'kbuilder' ); ?></th>
					<td>
						<p><?php esc_html_e( 'Add this shortcode to any page:', 'kbuilder' ); ?> <code>[kbuilder]</code></p>
						<p class="description"><?php esc_html_e( 'To start on a specific design:', 'kbuilder' ); ?> <code>[kbuilder house="house-1"]</code></p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
