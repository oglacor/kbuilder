<?php
/**
 * First run of 2.0: creates the finishes and the house model that 1.x kept in
 * code, so sites that update keep the same builder, now editable in the admin.
 */

defined( 'ABSPATH' ) || exit;

define( 'KBUILDER_DB_VERSION', 2 );

// After the post types are registered; also on the front end, so an update
// works even before anyone opens the admin.
add_action( 'init', 'kbuilder_maybe_install', 20 );

function kbuilder_maybe_install() {
	if ( (int) get_option( 'kbuilder_db_version' ) >= KBUILDER_DB_VERSION || get_transient( 'kbuilder_installing' ) ) {
		return;
	}
	// The lock expires on its own if a run dies half way; the next one picks up what's missing.
	set_transient( 'kbuilder_installing', 1, 5 * MINUTE_IN_SECONDS );

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	if ( ! kbuilder_has_posts( KBUILDER_FINISH_TYPE ) ) {
		kbuilder_install_finishes();
	}
	if ( ! kbuilder_has_posts( KBUILDER_HOUSE_TYPE ) ) {
		kbuilder_install_sample_house();
	}

	update_option( 'kbuilder_db_version', KBUILDER_DB_VERSION );
	delete_transient( 'kbuilder_installing' );
}

function kbuilder_has_posts( $post_type ) {
	return (bool) get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
}

/**
 * The 1.x palettes: paints for walls and roof, PVC profile finishes for windows and doors.
 */
function kbuilder_install_finishes() {
	$paint  = array( 'walls', 'roof' );
	$frames = array( 'windows', 'doors' );

	// name, hex, zones, texture file.
	$finishes = array(
		array( 'Blanco', '#F9FDFE', array( 'walls' ) ),
		array( 'Blanco', '#F9FDFE', array( 'roof' ) ),
		array( 'Rosa Mexicano', '#E4007C', $paint ),
		array( 'Azul Cobalto', '#0047AB', $paint ),
		array( 'Mostaza', '#EAC102', $paint ),
		array( 'Terracotta', '#AD7D60', $paint ),
		array( 'Tezontle', '#A55749', $paint ),
		array( 'Gris Cantera', '#B6B6B6', $paint ),
		array( 'Gris Concreto', '#878883', $paint ),
		array( 'Rojo', '#E91E2F', $paint ),
		array( 'Aqua', '#00A99D', $paint ),
		array( 'Blanco', '#F9FDFE', $frames ),
		array( 'Caoba', '#321616', $frames, 'mahogany.jpg' ),
		array( 'Roble', '#8F5D19', $frames, 'oak.jpg' ),
		array( 'Gris Antracita', '#2E2F2A', $frames, 'bronze.jpg' ),
		array( 'Nogal', '#5C4026', $frames, 'nogal.jpg' ),
		array( 'Negro', '#0D0D0D', $frames ),
		array( 'Café Decor', '#1C1209', $frames ),
		array( 'Fresno Americano', '#999280', $frames, 'shiefeld.jpg' ),
		array( 'Plata', '#B3B3B3', $frames ),
		array( 'Beige', '#D8CFBD', $frames ),
	);

	foreach ( $finishes as $order => $finish ) {
		$post_id = wp_insert_post(
			array(
				'post_type'   => KBUILDER_FINISH_TYPE,
				'post_status' => 'publish',
				'post_title'  => $finish[0],
				'menu_order'  => $order + 1,
			)
		);
		if ( ! $post_id || is_wp_error( $post_id ) ) {
			continue;
		}
		$texture = isset( $finish[3] ) ? kbuilder_install_attachment( KBUILDER_DIR . 'assets/samples/textures/' . $finish[3], $finish[0], 'kbuilder-' . $finish[3] ) : 0;
		update_post_meta(
			$post_id,
			KBUILDER_FINISH_META,
			array(
				'hex'     => $finish[1],
				'texture' => $texture,
				'regions' => $finish[2],
			)
		);
	}
}

/**
 * The 1.x house ("Diseño 1"), built from the sample files the guide also points designers to.
 */
function kbuilder_install_sample_house() {
	$dir      = KBUILDER_DIR . 'assets/samples/house-1/';
	$hotspots = array(
		'roof'    => array( 47.6, 11.6 ),
		'walls'   => array( 41.6, 60.6 ),
		'windows' => array( 67.6, 37.6 ),
	);
	$regions  = array();

	foreach ( $hotspots as $type => $hotspot ) {
		$parsed = kbuilder_parse_svg( (string) file_get_contents( $dir . $type . '.svg' ) );
		if ( ! is_wp_error( $parsed ) ) {
			$regions[ $type ] = array(
				'file'    => $type . '.svg',
				'shapes'  => $parsed['shapes'],
				'hotspot' => $hotspot,
			);
		}
	}

	$post_id = wp_insert_post(
		array(
			'post_type'   => KBUILDER_HOUSE_TYPE,
			'post_status' => 'publish',
			'post_title'  => 'Diseño 1',
			'post_name'   => 'house-1', // 1.x's [kbuilder house="house-1"] keeps working.
			'menu_order'  => 1,
		)
	);
	if ( ! $post_id || is_wp_error( $post_id ) ) {
		return;
	}

	update_post_meta(
		$post_id,
		KBUILDER_HOUSE_META,
		array(
			'image'   => kbuilder_install_attachment( $dir . 'background.jpg', 'K-Builder: Diseño 1', 'kbuilder-house-1.jpg' ),
			'regions' => $regions,
		)
	);
}

/**
 * Copies a bundled image into the media library as $name. Returns the attachment ID, or 0.
 */
function kbuilder_install_attachment( $path, $title, $name ) {
	$tmp = wp_tempnam( $name );
	if ( ! $tmp || ! copy( $path, $tmp ) ) {
		return 0;
	}

	$id = media_handle_sideload(
		array(
			'name'     => $name,
			'tmp_name' => $tmp,
		),
		0,
		$title
	);
	if ( is_wp_error( $id ) ) {
		wp_delete_file( $tmp );
		return 0;
	}

	return $id;
}
