<?php
/**
 * Catalog: zone types, finishes and house models.
 *
 * Finishes and houses are edited in K-Builder → Finishes / House models; these
 * functions turn them into the arrays the builder and the PDF use. A finish
 * is a color or a texture that recolors the zone while keeping the photo's
 * light and shadow (assets/js/recolor.js); screen, lens and PDF share it.
 */

defined( 'ABSPATH' ) || exit;

function kbuilder_image_url( $file ) {
	return KBUILDER_URL . 'assets/images/' . $file;
}

/**
 * Paintable zones a house model can have, in the order the builder lists them.
 *
 * label: shown to visitors and in the PDF. admin_label: shown in the admin.
 * color: overlay color in the admin preview. icon: 68×63 viewBox artwork.
 * profile: frame zone; textures turn to run along each profile.
 */
function kbuilder_get_region_types() {
	$types = array(
		'roof'    => array(
			'label'       => __( 'Techo', 'kbuilder' ),
			'admin_label' => __( 'Roof', 'kbuilder' ),
			'color'       => '#e91e2f',
			'icon'        => array( 'd' => 'M58.89,44.89l-9.76-25.04c-.17-.44-.59-.72-1.06-.72h-9.38v-4.61c0-.63-.51-1.14-1.14-1.14h-4.15c-.63,0-1.14.51-1.14,1.14v4.61h-12.75c-.38,0-.73.19-.94.5-.05.07-.09.15-.12.23l-9.76,25.04c-.11.28-.1.59.02.87.12.28.35.49.63.6.13.05.27.08.41.08.47,0,.89-.28,1.06-.72l8.7-22.32,8.7,22.32c.17.44.59.72,1.06.72h28.55c.38,0,.73-.19.94-.5.21-.31.26-.7.12-1.05ZM35.05,40.18l1.55,3.99h-6.55l-1.55-3.99h6.55ZM32.61,33.92l1.55,3.99h-6.55l-1.55-3.99h6.55ZM30.17,27.66l1.55,3.99h-6.55l-1.55-3.99h6.55ZM39.04,44.17l-8.87-22.76h2.09v2.22c0,.63.51,1.14,1.14,1.14s1.14-.51,1.14-1.14v-7.97h1.88v7.97c0,.63.51,1.14,1.14,1.14s1.14-.51,1.14-1.14v-2.22h8.6l8.87,22.76h-17.12ZM29.28,25.39h-6.55l-1.55-3.99h6.55l1.55,3.99Z' ),
		),
		'walls'   => array(
			'label'       => __( 'Paredes', 'kbuilder' ),
			'admin_label' => __( 'Walls', 'kbuilder' ),
			'color'       => '#2f7de9',
			'icon'        => array( 'd' => 'M50.62,27.05h-7.1v-5.39c0-.8-.65-1.45-1.45-1.45h-7.1v-5.39c0-.8-.65-1.45-1.45-1.45h-17.11c-.8,0-1.45.65-1.45,1.45v34.22c0,.8.65,1.45,1.45,1.45h34.22c.8,0,1.45-.65,1.45-1.45v-20.53c0-.8-.65-1.45-1.45-1.45ZM49.16,29.96v3.94h-14.2v-3.94h14.2ZM17.85,20.21v-3.94h14.2v3.94h-14.2ZM26.41,27.05v-3.94h14.2v3.94h-14.2ZM17.85,27.05v-3.94h5.65v3.94h-5.65ZM49.16,43.64v3.94h-14.2v-3.94h14.2ZM40.61,36.8v3.94h-14.2v-3.94h14.2ZM17.85,33.89v-3.94h14.2v3.94h-14.2ZM17.85,40.74v-3.94h5.65v3.94h-5.65ZM32.05,43.64v3.94h-14.2v-3.94h14.2ZM49.16,36.8v3.94h-5.65v-3.94h5.65Z' ),
		),
		'windows' => array(
			'label'       => __( 'Ventanas', 'kbuilder' ),
			'admin_label' => __( 'Windows', 'kbuilder' ),
			'color'       => '#f5b800',
			'profile'     => true,
			'icon'        => array( 'd' => 'M48.16,46.33h-.14V13.71c0-.46-.38-.84-.84-.84h-28.34c-.46,0-.84.38-.84.84v32.62h-.14c-1.44,0-2.61,1.17-2.61,2.61v2.17c0,.46.38.84.84.84h33.85c.46,0,.84-.38.84-.84v-2.17c0-1.44-1.17-2.61-2.61-2.61ZM19.68,46.33V14.55h26.66v31.78h-26.66ZM49.09,48.94v1.33H16.92v-1.33c0-.51.42-.93.93-.93h30.31c.51,0,.93.42.93.93ZM44.03,16.02h-22.04c-.46,0-.84.38-.84.84v27.16c0,.46.38.84.84.84h22.04c.46,0,.84-.38.84-.84v-27.16c0-.46-.38-.84-.84-.84ZM43.19,17.7v25.48h-20.36v-25.48h20.36ZM25.85,30.71c-.22-.41-.07-.92.33-1.14l12.85-7.05c.41-.22.92-.07,1.14.33.11.2.13.42.07.64-.06.22-.21.39-.4.5l-12.85,7.05c-.12.07-.26.1-.4.1-.31,0-.59-.17-.74-.43ZM26.58,38.47c.14,0,.28-.04.4-.1l12.85-7.05c.41-.22.55-.73.33-1.14-.22-.41-.74-.55-1.14-.33l-12.85,7.05c-.41.22-.55.73-.33,1.14.15.27.43.43.74.43ZM26.58,38.05h0s0,0,0,0ZM26.7,38.02h0s0,0,0,0Z' ),
		),
		'doors'   => array(
			'label'       => __( 'Puertas', 'kbuilder' ),
			'admin_label' => __( 'Doors', 'kbuilder' ),
			'color'       => '#19b37a',
			'profile'     => true,
			'icon'        => array(
				'd'    => 'M21 12h26v37.5h5V52H16v-2.5h5ZM23.5 14.5v35h21v-35ZM40.5 30.2a1.8 1.8 0 1 1 0 3.6a1.8 1.8 0 1 1 0-3.6Z',
				'rule' => 'evenodd',
			),
		),
	);

	return apply_filters( 'kbuilder_region_types', $types );
}

/**
 * Stored settings of a finish:
 * hex, texture (attachment ID), scale (canvas px one texture copy spans),
 * grain (vertical|horizontal: how the grain runs in the texture image),
 * shading (0–150: % of the photo's light and shadow kept), regions (zone keys).
 */
function kbuilder_get_finish_data( $post_id ) {
	$defaults = array(
		'hex'     => '#FFFFFF',
		'texture' => 0,
		'scale'   => 100,
		'grain'   => 'vertical',
		'shading' => 100,
		'regions' => array(),
	);

	return wp_parse_args( (array) get_post_meta( $post_id, KBUILDER_FINISH_META, true ), $defaults );
}

/**
 * Stored settings of a house: image (attachment ID) and regions, keyed by zone:
 * { file: uploaded file name, shapes: see kbuilder_parse_svg(), hotspot: [x%, y%] or null for automatic }.
 */
function kbuilder_get_house_data( $post_id ) {
	$defaults = array(
		'image'   => 0,
		'regions' => array(),
	);

	return wp_parse_args( (array) get_post_meta( $post_id, KBUILDER_HOUSE_META, true ), $defaults );
}

function kbuilder_get_published( $post_type ) {
	return get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'no_found_rows'  => true,
		)
	);
}

/**
 * Published finishes keyed by post ID (as a string, so JSON keeps it an object key).
 *
 * Keys: name, hex, swatch (small texture URL or ''), texture (HD texture URL or ''),
 * scale, grain, shading (0–1.5), regions (zone keys).
 */
function kbuilder_get_finishes() {
	$finishes = array();

	foreach ( kbuilder_get_published( KBUILDER_FINISH_TYPE ) as $post ) {
		$data = kbuilder_get_finish_data( $post->ID );

		$finishes[ (string) $post->ID ] = array(
			'name'    => $post->post_title,
			'hex'     => $data['hex'],
			'swatch'  => kbuilder_texture_url( $data['texture'], 'medium' ),
			'texture' => kbuilder_texture_url( $data['texture'], 'large' ),
			'scale'   => (int) $data['scale'],
			'grain'   => $data['grain'],
			'shading' => $data['shading'] / 100,
			'regions' => $data['regions'],
		);
	}

	return apply_filters( 'kbuilder_finishes', $finishes );
}

/**
 * Published house models that are ready to show (a background image and at
 * least one zone), keyed by slug.
 *
 * Keys: name, image, thumb, width, height, regions (keyed by zone: label, icon, profile, hotspot, shapes).
 */
function kbuilder_get_houses() {
	$types  = kbuilder_get_region_types();
	$houses = array();

	foreach ( kbuilder_get_published( KBUILDER_HOUSE_TYPE ) as $post ) {
		$data    = kbuilder_get_house_data( $post->ID );
		$image   = $data['image'] ? wp_get_attachment_image_url( $data['image'], 'full' ) : '';
		$regions = array();

		foreach ( $types as $type => $region_type ) {
			if ( empty( $data['regions'][ $type ]['shapes'] ) ) {
				continue;
			}
			$regions[ $type ] = array(
				'label'   => $region_type['label'],
				'icon'    => $region_type['icon'],
				'profile' => ! empty( $region_type['profile'] ),
				'hotspot' => isset( $data['regions'][ $type ]['hotspot'] ) ? $data['regions'][ $type ]['hotspot'] : null,
				'shapes'  => $data['regions'][ $type ]['shapes'],
			);
		}

		if ( ! $image || ! $regions ) {
			continue;
		}

		$houses[ $post->post_name ] = array(
			'name'    => $post->post_title,
			'image'   => $image,
			'thumb'   => wp_get_attachment_image_url( $data['image'], 'medium' ),
			'width'   => KBUILDER_CANVAS_WIDTH,
			'height'  => KBUILDER_CANVAS_HEIGHT,
			'regions' => $regions,
		);
	}

	return apply_filters( 'kbuilder_houses', $houses );
}

/**
 * URL of a texture attachment at a size ('large' falls back to the original
 * when the upload is smaller), or ''.
 */
function kbuilder_texture_url( $attachment_id, $size ) {
	$url = $attachment_id ? wp_get_attachment_image_url( $attachment_id, $size ) : '';

	return $url ? $url : '';
}

/**
 * Inline style for a finish swatch: its texture, or its color.
 */
function kbuilder_chip_style( $finish ) {
	return empty( $finish['swatch'] )
		? 'background-color: ' . $finish['hex'] . ';'
		: 'background-image: url("' . esc_url( $finish['swatch'] ) . '");';
}

/**
 * Prints a zone's shapes as <path> elements (shared by the builder and the admin preview).
 */
function kbuilder_print_shapes( $shapes ) {
	foreach ( $shapes as $shape ) {
		printf(
			'<path d="%1$s"%2$s%3$s/>',
			esc_attr( $shape['d'] ),
			empty( $shape['m'] ) ? '' : ' transform="matrix(' . esc_attr( implode( ' ', $shape['m'] ) ) . ')"',
			empty( $shape['r'] ) ? '' : ' fill-rule="evenodd"'
		);
	}
}
