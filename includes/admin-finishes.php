<?php
/**
 * Finish screen: color, HD texture and how it's laid on the photo, and which
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
	$swatch  = kbuilder_texture_url( $data['texture'], 'medium' );
	$texture = kbuilder_texture_url( $data['texture'], 'large' );
	$sample  = kbuilder_finish_preview_house();

	wp_nonce_field( 'kbuilder_save_finish', 'kbuilder_finish_nonce' );
	?>
	<table class="form-table kbuilder-finish-form" role="presentation">
		<tr>
			<th scope="row"><label for="kbuilder-finish-hex"><?php esc_html_e( 'Color', 'kbuilder' ); ?></label></th>
			<td>
				<input type="text" id="kbuilder-finish-hex" class="kbuilder-color" name="kbuilder_finish[hex]" value="<?php echo esc_attr( $data['hex'] ); ?>" data-default-color="#FFFFFF">
				<p class="description"><?php esc_html_e( 'The finish color. With a texture, the texture is painted instead; pick its average color for the lists and as a fallback.', 'kbuilder' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Texture', 'kbuilder' ); ?></th>
			<td>
				<div class="kbuilder-media-field" data-kbuilder-media="texture" data-url="<?php echo esc_url( $texture ); ?>">
					<input type="hidden" class="kbuilder-media-id" name="kbuilder_finish[texture]" value="<?php echo esc_attr( $data['texture'] ? $data['texture'] : '' ); ?>">
					<span class="kbuilder-texture-chip" style="<?php echo $swatch ? esc_attr( 'background-image: url("' . esc_url( $swatch ) . '");' ) : ''; ?>"></span>
					<button type="button" class="button kbuilder-media-select"><?php esc_html_e( 'Choose texture', 'kbuilder' ); ?></button>
					<button type="button" class="button-link button-link-delete kbuilder-media-remove"<?php echo $swatch ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove', 'kbuilder' ); ?></button>
				</div>
				<p class="description"><?php esc_html_e( 'Optional. A photo of the real product finish (wood grain, anodized metal…): JPG, 1024–2048 px, straight-on and evenly lit, seamless (tileable) if you have it. It\'s painted on the zone with the photo\'s light and shadow, and shown in the swatch, the lens and the PDF.', 'kbuilder' ); ?></p>
			</td>
		</tr>
		<tr class="kbuilder-texture-setting">
			<th scope="row"><label for="kbuilder-finish-scale"><?php esc_html_e( 'Grain size', 'kbuilder' ); ?></label></th>
			<td>
				<input type="range" id="kbuilder-finish-scale" name="kbuilder_finish[scale]" min="20" max="400" step="5" value="<?php echo esc_attr( $data['scale'] ); ?>">
				<output for="kbuilder-finish-scale" class="kbuilder-range-value"><?php echo esc_html( $data['scale'] ); ?> px</output>
				<p class="description"><?php esc_html_e( 'How much of the 1920 px house one copy of the texture covers. Smaller = finer grain.', 'kbuilder' ); ?></p>
			</td>
		</tr>
		<tr class="kbuilder-texture-setting">
			<th scope="row"><label for="kbuilder-finish-grain"><?php esc_html_e( 'Grain direction', 'kbuilder' ); ?></label></th>
			<td>
				<select id="kbuilder-finish-grain" name="kbuilder_finish[grain]">
					<option value="vertical" <?php selected( $data['grain'], 'vertical' ); ?>><?php esc_html_e( 'Vertical in the texture file', 'kbuilder' ); ?></option>
					<option value="horizontal" <?php selected( $data['grain'], 'horizontal' ); ?>><?php esc_html_e( 'Horizontal in the texture file', 'kbuilder' ); ?></option>
				</select>
				<p class="description"><?php esc_html_e( 'On windows and doors the grain is turned to run along each profile, as on the real product.', 'kbuilder' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="kbuilder-finish-shading"><?php esc_html_e( 'Shading', 'kbuilder' ); ?></label></th>
			<td>
				<input type="range" id="kbuilder-finish-shading" name="kbuilder_finish[shading]" min="0" max="150" step="5" value="<?php echo esc_attr( $data['shading'] ); ?>">
				<output for="kbuilder-finish-shading" class="kbuilder-range-value"><?php echo esc_html( $data['shading'] ); ?>%</output>
				<p class="description"><?php esc_html_e( 'How much of the photo\'s light and shadow shows on the finish. 100% = as in the photo; lower is flatter, higher has more contrast.', 'kbuilder' ); ?></p>
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
					<div class="kbuilder-finish-preview" data-photo="<?php echo esc_url( $sample['image'] ); ?>" data-width="<?php echo (int) $sample['width']; ?>" data-height="<?php echo (int) $sample['height']; ?>">
						<canvas class="kbuilder-finish-preview-house" width="<?php echo (int) $sample['width']; ?>" height="<?php echo (int) $sample['height']; ?>"></canvas>
						<div class="kbuilder-finish-preview-lens">
							<canvas width="360" height="360"></canvas>
							<span class="kbuilder-finish-preview-zone"></span>
						</div>
						<svg hidden xmlns="http://www.w3.org/2000/svg">
							<?php foreach ( $sample['regions'] as $type => $region ) : ?>
								<g data-region="<?php echo esc_attr( $type ); ?>" data-label="<?php echo esc_attr( kbuilder_get_region_types()[ $type ]['admin_label'] ); ?>" data-profile="<?php echo $region['profile'] ? '1' : ''; ?>" data-hotspot="<?php echo esc_attr( is_array( $region['hotspot'] ) ? implode( ',', $region['hotspot'] ) : '' ); ?>"><?php kbuilder_print_shapes( $region['shapes'] ); ?></g>
							<?php endforeach; ?>
						</svg>
					</div>
					<p class="description">
						<?php
						/* translators: %s: house model name */
						echo esc_html( sprintf( __( 'Live preview on "%s", in the zones checked above, with the close-up visitors see in the lens. Not saved until you click Update.', 'kbuilder' ), $sample['name'] ) );
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
	$hex     = sanitize_hex_color( $input['hex'] ?? '' );

	update_post_meta(
		$post_id,
		KBUILDER_FINISH_META,
		array(
			'hex'     => $hex ? strtoupper( $hex ) : '#FFFFFF',
			'texture' => $texture && wp_attachment_is_image( $texture ) ? $texture : 0,
			'scale'   => min( max( (int) ( $input['scale'] ?? 100 ), 20 ), 400 ),
			'grain'   => 'horizontal' === ( $input['grain'] ?? '' ) ? 'horizontal' : 'vertical',
			'shading' => min( max( (int) ( $input['shading'] ?? 100 ), 0 ), 150 ),
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
			'kbuilder_zones'  => __( 'Available for', 'kbuilder' ),
			'kbuilder_render' => __( 'Render', 'kbuilder' ),
			'menu_order'      => __( 'Order', 'kbuilder' ),
			'date'            => $date,
		)
	);
}

function kbuilder_finish_column( $column, $post_id ) {
	$data = kbuilder_get_finish_data( $post_id );

	switch ( $column ) {
		case 'kbuilder_swatch':
			$texture = kbuilder_texture_url( $data['texture'], 'thumbnail' );
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

		case 'kbuilder_render':
			printf( '<code>%s</code> ', esc_html( $data['hex'] ) );
			if ( $data['texture'] ) {
				/* translators: %d: grain size in px */
				echo esc_html( sprintf( __( 'texture %d px', 'kbuilder' ), $data['scale'] ) ) . ' · ';
			}
			/* translators: %d: shading percentage */
			echo esc_html( sprintf( __( 'shading %d%%', 'kbuilder' ), $data['shading'] ) );
			break;

		case 'menu_order':
			echo (int) get_post_field( 'menu_order', $post_id );
			break;
	}
}
