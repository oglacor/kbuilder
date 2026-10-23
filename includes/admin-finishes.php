<?php
/**
 * Finish screen: color, texture swatch, how it tints the photo, and which
 * zones offer it. Finishes are shared by every house model.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes_' . KBUILDER_FINISH_TYPE, 'kbuilder_finish_meta_boxes' );
add_action( 'save_post_' . KBUILDER_FINISH_TYPE, 'kbuilder_save_finish' );
add_filter( 'manage_' . KBUILDER_FINISH_TYPE . '_posts_columns', 'kbuilder_finish_columns' );
add_action( 'manage_' . KBUILDER_FINISH_TYPE . '_posts_custom_column', 'kbuilder_finish_column', 10, 2 );

function kbuilder_finish_meta_boxes() {
	add_meta_box( 'kbuilder-finish', __( 'Finish', 'kbuilder' ), 'kbuilder_finish_box', null, 'normal', 'high' );
}

function kbuilder_finish_box( $post ) {
	$data    = kbuilder_get_finish_data( $post->ID );
	$texture = $data['texture'] ? wp_get_attachment_image_url( $data['texture'], 'medium' ) : '';
	$sample  = kbuilder_finish_preview_house();

	wp_nonce_field( 'kbuilder_save_finish', 'kbuilder_finish_nonce' );
	?>
	<table class="form-table kbuilder-finish-form" role="presentation">
		<tr>
			<th scope="row"><label for="kbuilder-finish-hex"><?php esc_html_e( 'Color', 'kbuilder' ); ?></label></th>
			<td>
				<input type="text" id="kbuilder-finish-hex" class="kbuilder-color" name="kbuilder_finish[hex]" value="<?php echo esc_attr( $data['hex'] ); ?>" data-default-color="#FFFFFF">
				<p class="description"><?php esc_html_e( 'Tints the zone on the photo. Also the swatch, when there\'s no texture.', 'kbuilder' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Texture swatch', 'kbuilder' ); ?></th>
			<td>
				<div class="kbuilder-media-field" data-kbuilder-media="texture">
					<input type="hidden" class="kbuilder-media-id" name="kbuilder_finish[texture]" value="<?php echo esc_attr( $data['texture'] ? $data['texture'] : '' ); ?>">
					<span class="kbuilder-texture-chip" style="<?php echo $texture ? esc_attr( 'background-image: url("' . esc_url( $texture ) . '");' ) : ''; ?>"></span>
					<button type="button" class="button kbuilder-media-select"><?php esc_html_e( 'Choose texture', 'kbuilder' ); ?></button>
					<button type="button" class="button-link button-link-delete kbuilder-media-remove"<?php echo $texture ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove', 'kbuilder' ); ?></button>
				</div>
				<p class="description"><?php esc_html_e( 'Optional. A square photo of the product finish (wood grain, anodized metal…), JPG, at least 300 × 300 px. It\'s the swatch visitors pick and what the PDF shows; the photo is still tinted with the color above, so pick a color that matches the texture.', 'kbuilder' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="kbuilder-finish-blend"><?php esc_html_e( 'Blend mode', 'kbuilder' ); ?></label></th>
			<td>
				<select id="kbuilder-finish-blend" name="kbuilder_finish[blend]">
					<?php foreach ( kbuilder_get_blend_modes() as $mode => $label ) : ?>
						<option value="<?php echo esc_attr( $mode ); ?>" <?php selected( $data['blend'], $mode ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e( 'How the color mixes with the photo.', 'kbuilder' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="kbuilder-finish-opacity"><?php esc_html_e( 'Opacity', 'kbuilder' ); ?></label></th>
			<td>
				<input type="range" id="kbuilder-finish-opacity" name="kbuilder_finish[opacity]" min="0" max="100" step="5" value="<?php echo esc_attr( $data['opacity'] ); ?>">
				<output for="kbuilder-finish-opacity" class="kbuilder-opacity-value"><?php echo esc_html( $data['opacity'] ); ?>%</output>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Available for', 'kbuilder' ); ?></th>
			<td>
				<fieldset class="kbuilder-finish-zones">
					<legend class="screen-reader-text"><?php esc_html_e( 'Available for', 'kbuilder' ); ?></legend>
					<?php foreach ( kbuilder_get_region_types() as $type => $region_type ) : ?>
						<label>
							<input type="checkbox" name="kbuilder_finish[regions][]" value="<?php echo esc_attr( $type ); ?>" <?php checked( in_array( $type, $data['regions'], true ) ); ?>>
							<?php echo esc_html( $region_type['admin_label'] ); ?>
						</label>
					<?php endforeach; ?>
				</fieldset>
				<p class="description"><?php esc_html_e( 'The zones that offer this finish, in every house model. Unchecked everywhere = hidden.', 'kbuilder' ); ?></p>
			</td>
		</tr>
		<?php if ( $sample ) : ?>
			<tr>
				<th scope="row"><?php esc_html_e( 'Preview', 'kbuilder' ); ?></th>
				<td>
					<div class="kbuilder-finish-preview">
						<svg viewBox="0 0 <?php echo (int) KBUILDER_CANVAS_WIDTH; ?> <?php echo (int) KBUILDER_CANVAS_HEIGHT; ?>" xmlns="http://www.w3.org/2000/svg">
							<image href="<?php echo esc_url( $sample['image'] ); ?>" width="100%" height="100%" preserveAspectRatio="xMidYMid slice"/>
							<?php foreach ( $sample['regions'] as $type => $region ) : ?>
								<g class="kbuilder-finish-preview-zone" data-region="<?php echo esc_attr( $type ); ?>"><?php kbuilder_print_shapes( $region['shapes'] ); ?></g>
							<?php endforeach; ?>
						</svg>
					</div>
					<p class="description">
						<?php
						/* translators: %s: house model name */
						echo esc_html( sprintf( __( 'Live preview on "%s", in the zones checked above. Not saved until you click Update.', 'kbuilder' ), $sample['name'] ) );
						?>
					</p>
				</td>
			</tr>
		<?php endif; ?>
	</table>
	<?php
}

/**
 * The first house model, used to preview finishes.
 */
function kbuilder_finish_preview_house() {
	$houses = kbuilder_get_houses();

	return $houses ? reset( $houses ) : null;
}

function kbuilder_save_finish( $post_id ) {
	if ( ! isset( $_POST['kbuilder_finish_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['kbuilder_finish_nonce'] ), 'kbuilder_save_finish' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$input   = isset( $_POST['kbuilder_finish'] ) ? (array) wp_unslash( $_POST['kbuilder_finish'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Each field is sanitized below.
	$texture = absint( $input['texture'] ?? 0 );
	$blend   = sanitize_key( $input['blend'] ?? '' );
	$hex     = sanitize_hex_color( $input['hex'] ?? '' );

	update_post_meta(
		$post_id,
		KBUILDER_FINISH_META,
		array(
			'hex'     => $hex ? strtoupper( $hex ) : '#FFFFFF',
			'texture' => $texture && wp_attachment_is_image( $texture ) ? $texture : 0,
			'blend'   => array_key_exists( $blend, kbuilder_get_blend_modes() ) ? $blend : 'multiply',
			'opacity' => min( max( (int) ( $input['opacity'] ?? 100 ), 0 ), 100 ),
			'regions' => array_values( array_intersect( array_keys( kbuilder_get_region_types() ), (array) ( $input['regions'] ?? array() ) ) ),
		)
	);
}

function kbuilder_finish_columns( $columns ) {
	$date = $columns['date'];
	unset( $columns['date'] );

	return array_merge(
		array_slice( $columns, 0, 1, true ),
		array( 'kbuilder_swatch' => __( 'Swatch', 'kbuilder' ) ),
		array_slice( $columns, 1, null, true ),
		array(
			'kbuilder_zones' => __( 'Available for', 'kbuilder' ),
			'kbuilder_tint'  => __( 'Tint', 'kbuilder' ),
			'menu_order'     => __( 'Order', 'kbuilder' ),
			'date'           => $date,
		)
	);
}

function kbuilder_finish_column( $column, $post_id ) {
	$data = kbuilder_get_finish_data( $post_id );

	switch ( $column ) {
		case 'kbuilder_swatch':
			$texture = $data['texture'] ? wp_get_attachment_image_url( $data['texture'], 'thumbnail' ) : '';
			printf(
				'<span class="kbuilder-texture-chip" style="%s"></span>',
				esc_attr( $texture ? 'background-image: url("' . esc_url( $texture ) . '");' : 'background-color: ' . $data['hex'] . ';' )
			);
			break;

		case 'kbuilder_zones':
			$types  = kbuilder_get_region_types();
			$labels = array();
			foreach ( $data['regions'] as $type ) {
				if ( isset( $types[ $type ] ) ) {
					$labels[] = $types[ $type ]['admin_label'];
				}
			}
			echo $labels ? esc_html( implode( ', ', $labels ) ) : '<em>' . esc_html__( 'Hidden (no zones)', 'kbuilder' ) . '</em>';
			break;

		case 'kbuilder_tint':
			$modes = kbuilder_get_blend_modes();
			$mode  = isset( $modes[ $data['blend'] ] ) ? strtok( $modes[ $data['blend'] ], ':' ) : $data['blend'];
			printf( '<code>%1$s</code> %2$s · %3$d%%', esc_html( $data['hex'] ), esc_html( $mode ), (int) $data['opacity'] );
			break;

		case 'menu_order':
			echo (int) get_post_field( 'menu_order', $post_id );
			break;
	}
}
