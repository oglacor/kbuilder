<?php
/**
 * House model screen: background image, one SVG per zone, marker positions.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes_' . KBUILDER_HOUSE_TYPE, 'kbuilder_house_meta_boxes' );
add_action( 'post_edit_form_tag', 'kbuilder_house_form_enctype' );
add_action( 'save_post_' . KBUILDER_HOUSE_TYPE, 'kbuilder_save_house' );
add_filter( 'manage_' . KBUILDER_HOUSE_TYPE . '_posts_columns', 'kbuilder_house_columns' );
add_action( 'manage_' . KBUILDER_HOUSE_TYPE . '_posts_custom_column', 'kbuilder_house_column', 10, 2 );

function kbuilder_house_meta_boxes() {
	add_meta_box( 'kbuilder-house-design', __( 'Background image and preview', 'kbuilder' ), 'kbuilder_house_design_box', null, 'normal', 'high' );
	add_meta_box( 'kbuilder-house-zones', __( 'Zones (SVG files)', 'kbuilder' ), 'kbuilder_house_zones_box', null, 'normal', 'high' );
	add_meta_box( 'kbuilder-house-specs', __( 'File specs', 'kbuilder' ), 'kbuilder_house_specs_box', null, 'side', 'default' );
}

// The zone files post with the edit form.
function kbuilder_house_form_enctype( $post ) {
	if ( KBUILDER_HOUSE_TYPE === $post->post_type ) {
		echo ' enctype="multipart/form-data"';
	}
}

function kbuilder_house_design_box( $post ) {
	$data  = kbuilder_get_house_data( $post->ID );
	$types = kbuilder_get_region_types();
	$image = $data['image'] ? wp_get_attachment_image_url( $data['image'], 'full' ) : '';
	$meta  = $data['image'] ? wp_get_attachment_metadata( $data['image'] ) : array();

	wp_nonce_field( 'kbuilder_save_house', 'kbuilder_house_nonce' );
	?>
	<div class="kbuilder-media-field kbuilder-house-design" data-kbuilder-media="image">
		<input type="hidden" class="kbuilder-media-id" name="kbuilder_image" value="<?php echo esc_attr( $data['image'] ? $data['image'] : '' ); ?>">

		<div class="kbuilder-preview<?php echo $image ? '' : ' is-empty'; ?>">
			<svg class="kbuilder-preview-canvas" viewBox="0 0 <?php echo (int) KBUILDER_CANVAS_WIDTH; ?> <?php echo (int) KBUILDER_CANVAS_HEIGHT; ?>" xmlns="http://www.w3.org/2000/svg">
				<image class="kbuilder-media-image" href="<?php echo esc_url( $image ); ?>" width="100%" height="100%" preserveAspectRatio="xMidYMid slice"/>
				<?php foreach ( $types as $type => $region_type ) : ?>
					<?php if ( ! empty( $data['regions'][ $type ]['shapes'] ) ) : ?>
						<g class="kbuilder-preview-zone" data-region="<?php echo esc_attr( $type ); ?>" style="--zone: <?php echo esc_attr( $region_type['color'] ); ?>;"><?php kbuilder_print_shapes( $data['regions'][ $type ]['shapes'] ); ?></g>
					<?php endif; ?>
				<?php endforeach; ?>
			</svg>
			<p class="kbuilder-preview-empty"><?php esc_html_e( 'No background image yet.', 'kbuilder' ); ?></p>
			<?php foreach ( $types as $type => $region_type ) : ?>
				<?php
				if ( empty( $data['regions'][ $type ]['shapes'] ) ) {
					continue;
				}
				$hotspot = $data['regions'][ $type ]['hotspot'] ?? null;
				$value   = is_array( $hotspot ) ? $hotspot[0] . ',' . $hotspot[1] : '';
				?>
				<button type="button" class="kbuilder-marker" data-region="<?php echo esc_attr( $type ); ?>" style="--zone: <?php echo esc_attr( $region_type['color'] ); ?>;<?php echo $value ? esc_attr( sprintf( ' left: %s%%; top: %s%%;', $hotspot[0], $hotspot[1] ) ) : ''; ?>" title="<?php esc_attr_e( 'Drag to move this marker', 'kbuilder' ); ?>">
					<span><?php echo esc_html( $region_type['admin_label'] ); ?></span>
				</button>
				<input type="hidden" name="kbuilder_hotspot[<?php echo esc_attr( $type ); ?>]" value="<?php echo esc_attr( $value ); ?>">
			<?php endforeach; ?>
		</div>

		<p class="kbuilder-image-warning notice notice-warning inline"<?php echo kbuilder_image_has_canvas_size( $meta ) ? ' hidden' : ''; ?>>
			<?php
			if ( ! kbuilder_image_has_canvas_size( $meta ) ) {
				/* translators: 1: width, 2: height */
				echo esc_html( sprintf( __( 'This image is %1$s × %2$s px. Use 1920 × 1080 px so the zones line up with it.', 'kbuilder' ), $meta['width'], $meta['height'] ) );
			}
			?>
		</p>

		<div class="kbuilder-preview-toolbar">
			<button type="button" class="button kbuilder-media-select"><?php esc_html_e( 'Choose image', 'kbuilder' ); ?></button>
			<button type="button" class="button-link button-link-delete kbuilder-media-remove"<?php echo $image ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove', 'kbuilder' ); ?></button>
			<span class="description"><?php esc_html_e( 'JPG, 1920 × 1080 px.', 'kbuilder' ); ?></span>
		</div>

		<?php if ( $data['regions'] ) : ?>
			<ul class="kbuilder-preview-legend">
				<?php foreach ( $types as $type => $region_type ) : ?>
					<?php
					if ( empty( $data['regions'][ $type ]['shapes'] ) ) {
						continue;
					}
					?>
					<li data-region="<?php echo esc_attr( $type ); ?>" style="--zone: <?php echo esc_attr( $region_type['color'] ); ?>;">
						<span class="kbuilder-zone-dot"></span>
						<strong><?php echo esc_html( $region_type['admin_label'] ); ?></strong>
						<span class="kbuilder-marker-state"></span>
						<button type="button" class="button-link kbuilder-marker-reset"><?php esc_html_e( 'Reset marker', 'kbuilder' ); ?></button>
					</li>
				<?php endforeach; ?>
			</ul>
			<p class="description"><?php esc_html_e( 'Colored areas show each zone over the photo; check they line up. Drag a marker to move it, then click Update.', 'kbuilder' ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Whether an image's metadata says 16:9 (the canvas proportions). Unknown sizes pass.
 */
function kbuilder_image_has_canvas_size( $meta ) {
	if ( empty( $meta['width'] ) || empty( $meta['height'] ) ) {
		return true;
	}

	return abs( ( $meta['width'] / $meta['height'] ) / ( KBUILDER_CANVAS_WIDTH / KBUILDER_CANVAS_HEIGHT ) - 1 ) < 0.01;
}

function kbuilder_house_zones_box( $post ) {
	$data = kbuilder_get_house_data( $post->ID );
	?>
	<?php if ( 'publish' === $post->post_status && ( ! $data['image'] || ! array_filter( wp_list_pluck( $data['regions'], 'shapes' ) ) ) ) : ?>
		<div class="notice notice-warning inline"><p><?php esc_html_e( 'This model is hidden on the site until it has a background image and at least one zone.', 'kbuilder' ); ?></p></div>
	<?php endif; ?>
	<table class="widefat striped kbuilder-zones">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Zone', 'kbuilder' ); ?></th>
				<th><?php esc_html_e( 'Current file', 'kbuilder' ); ?></th>
				<th><?php esc_html_e( 'Upload a new SVG', 'kbuilder' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( kbuilder_get_region_types() as $type => $region_type ) : ?>
				<?php $zone = $data['regions'][ $type ] ?? null; ?>
				<tr>
					<td>
						<span class="kbuilder-zone-dot" style="--zone: <?php echo esc_attr( $region_type['color'] ); ?>;"></span>
						<strong><?php echo esc_html( $region_type['admin_label'] ); ?></strong>
					</td>
					<td>
						<?php if ( ! empty( $zone['shapes'] ) ) : ?>
							<code><?php echo esc_html( $zone['file'] ); ?></code>
							<span class="description">
								<?php
								/* translators: %d: number of shapes */
								echo esc_html( sprintf( _n( '%d shape', '%d shapes', count( $zone['shapes'] ), 'kbuilder' ), count( $zone['shapes'] ) ) );
								?>
							</span>
							<label class="kbuilder-zone-remove">
								<input type="checkbox" name="kbuilder_remove[<?php echo esc_attr( $type ); ?>]" value="1">
								<?php esc_html_e( 'Remove', 'kbuilder' ); ?>
							</label>
						<?php else : ?>
							<span class="description"><?php esc_html_e( 'None. This zone won\'t appear in the builder.', 'kbuilder' ); ?></span>
						<?php endif; ?>
					</td>
					<td>
						<input type="file" name="kbuilder_svg[<?php echo esc_attr( $type ); ?>]" accept=".svg,image/svg+xml">
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<p class="description">
		<?php esc_html_e( 'One SVG per zone, drawn on a 1920 × 1080 px canvas. Zones without a file are skipped. Choose the files and click Publish / Update to process them.', 'kbuilder' ); ?>
		<a href="<?php echo esc_url( kbuilder_guide_url( 'zones' ) ); ?>"><?php esc_html_e( 'How to prepare the SVGs', 'kbuilder' ); ?></a>
	</p>
	<?php
}

function kbuilder_house_specs_box() {
	$samples = KBUILDER_URL . 'assets/samples/house-1/';
	?>
	<ul class="kbuilder-specs">
		<li><strong><?php esc_html_e( 'Background image', 'kbuilder' ); ?></strong><br><?php esc_html_e( 'JPG, 1920 × 1080 px (16:9), sRGB, under 600 KB.', 'kbuilder' ); ?></li>
		<li><strong><?php esc_html_e( 'Zone SVGs', 'kbuilder' ); ?></strong><br><?php esc_html_e( 'Canvas / artboard 1920 × 1080 px at 0,0, the same as the image. One file per zone, filled shapes only.', 'kbuilder' ); ?></li>
		<li><strong><?php esc_html_e( 'Illustrator export', 'kbuilder' ); ?></strong><br><?php esc_html_e( 'File → Export → Export As → SVG, with "Use Artboards" checked.', 'kbuilder' ); ?></li>
	</ul>
	<p>
		<a class="button" href="<?php echo esc_url( KBUILDER_URL . 'assets/samples/kbuilder-canvas-1920x1080.svg' ); ?>" download><?php esc_html_e( 'Download canvas template', 'kbuilder' ); ?></a>
	</p>
	<p class="description">
		<?php esc_html_e( 'Example files:', 'kbuilder' ); ?>
		<a href="<?php echo esc_url( $samples . 'background.jpg' ); ?>" download>background.jpg</a>,
		<a href="<?php echo esc_url( $samples . 'roof.svg' ); ?>" download>roof.svg</a>,
		<a href="<?php echo esc_url( $samples . 'walls.svg' ); ?>" download>walls.svg</a>,
		<a href="<?php echo esc_url( $samples . 'windows.svg' ); ?>" download>windows.svg</a>
	</p>
	<p><a href="<?php echo esc_url( kbuilder_guide_url() ); ?>"><?php esc_html_e( 'Read the full guide', 'kbuilder' ); ?> →</a></p>
	<?php
}

function kbuilder_save_house( $post_id ) {
	if ( ! isset( $_POST['kbuilder_house_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['kbuilder_house_nonce'] ), 'kbuilder_save_house' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$data          = kbuilder_get_house_data( $post_id );
	$image         = absint( $_POST['kbuilder_image'] ?? 0 );
	$data['image'] = $image && wp_attachment_is_image( $image ) ? $image : 0;

	foreach ( kbuilder_get_region_types() as $type => $region_type ) {
		$zone   = $data['regions'][ $type ] ?? null;
		$label  = $region_type['admin_label'];
		$upload = kbuilder_uploaded_svg( $type );

		if ( ! empty( $_POST['kbuilder_remove'][ $type ] ) ) {
			$zone = null;
		}

		if ( is_wp_error( $upload ) ) {
			kbuilder_add_notice( 'error', $label . ': ' . $upload->get_error_message() );
		} elseif ( $upload ) {
			$parsed = kbuilder_parse_svg( $upload['contents'] );
			if ( is_wp_error( $parsed ) ) {
				/* translators: 1: zone, 2: file name, 3: problem */
				kbuilder_add_notice( 'error', sprintf( __( '%1$s (%2$s) was not saved: %3$s', 'kbuilder' ), $label, $upload['name'], $parsed->get_error_message() ) );
			} else {
				// New shapes: let the marker find its spot again.
				$zone = array(
					'file'    => $upload['name'],
					'shapes'  => $parsed['shapes'],
					'hotspot' => null,
				);
				$count = count( $parsed['shapes'] );
				/* translators: 1: zone, 2: number of shapes, 3: file name */
				kbuilder_add_notice( 'success', sprintf( _n( '%1$s: %2$d shape loaded from %3$s.', '%1$s: %2$d shapes loaded from %3$s.', $count, 'kbuilder' ), $label, $count, $upload['name'] ) );
				foreach ( $parsed['notes'] as $note ) {
					kbuilder_add_notice( 'warning', $label . ': ' . $note );
				}
			}
		} elseif ( $zone && isset( $_POST['kbuilder_hotspot'][ $type ] ) ) {
			$zone['hotspot'] = kbuilder_sanitize_hotspot( sanitize_text_field( wp_unslash( $_POST['kbuilder_hotspot'][ $type ] ) ) );
		}

		if ( $zone ) {
			$data['regions'][ $type ] = $zone;
		} else {
			unset( $data['regions'][ $type ] );
		}
	}

	update_post_meta( $post_id, KBUILDER_HOUSE_META, $data );
}

/**
 * The SVG uploaded for a zone: { name, contents }, null when none, or WP_Error.
 */
function kbuilder_uploaded_svg( $type ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Checked in kbuilder_save_house().
	if ( empty( $_FILES['kbuilder_svg']['name'][ $type ] ) ) {
		return null;
	}

	$name  = sanitize_file_name( wp_unslash( $_FILES['kbuilder_svg']['name'][ $type ] ) );
	$error = (int) $_FILES['kbuilder_svg']['error'][ $type ];
	$tmp   = $_FILES['kbuilder_svg']['tmp_name'][ $type ]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Server path.
	// phpcs:enable

	if ( UPLOAD_ERR_OK !== $error ) {
		/* translators: 1: file name, 2: PHP upload error code */
		return new WP_Error( 'kbuilder_upload', sprintf( __( '%1$s could not be uploaded (error %2$d).', 'kbuilder' ), $name, $error ) );
	}
	if ( 'svg' !== strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) ) {
		/* translators: %s: file name */
		return new WP_Error( 'kbuilder_upload', sprintf( __( '%s is not an .svg file.', 'kbuilder' ), $name ) );
	}
	if ( ! is_uploaded_file( $tmp ) ) {
		return new WP_Error( 'kbuilder_upload', __( 'The upload could not be read.', 'kbuilder' ) );
	}

	return array(
		'name'     => $name,
		'contents' => (string) file_get_contents( $tmp ), // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local upload.
	);
}

/**
 * "x,y" percentages from the preview → [x, y], or null for an automatic marker.
 */
function kbuilder_sanitize_hotspot( $value ) {
	$parts = array_map( 'trim', explode( ',', (string) $value ) );
	if ( 2 !== count( $parts ) || ! is_numeric( $parts[0] ) || ! is_numeric( $parts[1] ) ) {
		return null;
	}

	return array(
		round( min( max( (float) $parts[0], 0 ), 100 ), 2 ),
		round( min( max( (float) $parts[1], 0 ), 100 ), 2 ),
	);
}

function kbuilder_house_columns( $columns ) {
	$date = $columns['date'];
	unset( $columns['date'] );

	return array_merge(
		array_slice( $columns, 0, 1, true ),
		array( 'kbuilder_image' => __( 'Image', 'kbuilder' ) ),
		array_slice( $columns, 1, null, true ),
		array(
			'kbuilder_zones' => __( 'Zones', 'kbuilder' ),
			'kbuilder_link'  => __( 'Shortcode / link', 'kbuilder' ),
			'date'           => $date,
		)
	);
}

function kbuilder_house_column( $column, $post_id ) {
	$data = kbuilder_get_house_data( $post_id );

	switch ( $column ) {
		case 'kbuilder_image':
			echo $data['image'] ? wp_get_attachment_image( $data['image'], 'thumbnail', false, array( 'class' => 'kbuilder-list-thumb' ) ) : '—';
			break;

		case 'kbuilder_zones':
			$labels = array();
			foreach ( kbuilder_get_region_types() as $type => $region_type ) {
				if ( ! empty( $data['regions'][ $type ]['shapes'] ) ) {
					$labels[] = '<span class="kbuilder-zone-dot" style="--zone: ' . esc_attr( $region_type['color'] ) . ';"></span>' . esc_html( $region_type['admin_label'] );
				}
			}
			echo $labels ? implode( ' &nbsp; ', $labels ) : '—'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
			break;

		case 'kbuilder_link':
			$slug = get_post_field( 'post_name', $post_id );
			if ( $slug ) {
				printf( '<code>[kbuilder house="%1$s"]</code><br><code>?modelo=%1$s</code>', esc_html( $slug ) );
			}
			break;
	}
}
