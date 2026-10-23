<?php
/**
 * Admin wiring shared by the K-Builder screens: assets, list order, the guide.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_enqueue_scripts', 'kbuilder_admin_assets' );
add_action( 'admin_menu', 'kbuilder_add_guide_page', 20 );
add_action( 'pre_get_posts', 'kbuilder_admin_list_order' );
add_action( 'admin_notices', 'kbuilder_admin_notices' );

function kbuilder_is_catalog_screen( $base = null ) {
	$screen = get_current_screen();

	return $screen
		&& in_array( $screen->post_type, array( KBUILDER_HOUSE_TYPE, KBUILDER_FINISH_TYPE ), true )
		&& ( null === $base || $base === $screen->base );
}

function kbuilder_admin_assets() {
	if ( ! kbuilder_is_catalog_screen() ) {
		return;
	}

	wp_enqueue_style( 'kbuilder-admin', KBUILDER_URL . 'assets/css/admin.css', array(), KBUILDER_VERSION );

	if ( kbuilder_is_catalog_screen( 'post' ) ) {
		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'kbuilder-hotspot', KBUILDER_URL . 'assets/js/hotspot.js', array(), KBUILDER_VERSION, true );
		wp_enqueue_script( 'kbuilder-recolor', KBUILDER_URL . 'assets/js/recolor.js', array(), KBUILDER_VERSION, true );
		wp_enqueue_script( 'kbuilder-admin', KBUILDER_URL . 'assets/js/admin.js', array( 'jquery', 'wp-color-picker', 'kbuilder-hotspot', 'kbuilder-recolor' ), KBUILDER_VERSION, true );
		wp_localize_script(
			'kbuilder-admin',
			'kbuilderAdmin',
			array(
				'canvas' => array(
					'width'  => KBUILDER_CANVAS_WIDTH,
					'height' => KBUILDER_CANVAS_HEIGHT,
				),
				'i18n'   => array(
					'chooseImage'   => __( 'Choose background image', 'kbuilder' ),
					'chooseTexture' => __( 'Choose texture', 'kbuilder' ),
					'use'           => __( 'Use this image', 'kbuilder' ),
					/* translators: 1: width, 2: height */
					'imageSize'     => __( 'This image is %1$s × %2$s px. Use 1920 × 1080 px so the zones line up with it.', 'kbuilder' ),
					'auto'          => __( 'automatic', 'kbuilder' ),
					'custom'        => __( 'placed by hand', 'kbuilder' ),
				),
			)
		);
	}
}

// Catalog lists follow the "Order" field, like the builder does.
function kbuilder_admin_list_order( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || $query->get( 'orderby' ) ) {
		return;
	}
	if ( in_array( $query->get( 'post_type' ), array( KBUILDER_HOUSE_TYPE, KBUILDER_FINISH_TYPE ), true ) ) {
		$query->set(
			'orderby',
			array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			)
		);
	}
}

/**
 * Queue a notice for the next admin page this user sees (after the save redirect).
 *
 * @param string $type    error, warning, success or info.
 * @param string $message Plain text.
 */
function kbuilder_add_notice( $type, $message ) {
	$key      = 'kbuilder_notices_' . get_current_user_id();
	$notices  = (array) get_transient( $key );
	$notices[] = array( $type, $message );
	set_transient( $key, array_filter( $notices ), MINUTE_IN_SECONDS );
}

function kbuilder_admin_notices() {
	if ( ! kbuilder_is_catalog_screen() ) {
		return;
	}
	$key     = 'kbuilder_notices_' . get_current_user_id();
	$notices = get_transient( $key );
	if ( ! $notices ) {
		return;
	}
	delete_transient( $key );
	foreach ( $notices as $notice ) {
		printf( '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>', esc_attr( $notice[0] ), esc_html( $notice[1] ) );
	}
}

function kbuilder_add_guide_page() {
	add_submenu_page(
		'edit.php?post_type=' . KBUILDER_HOUSE_TYPE,
		__( 'K-Builder guide', 'kbuilder' ),
		__( 'Guide', 'kbuilder' ),
		'edit_pages',
		'kbuilder-guide',
		'kbuilder_render_guide_page'
	);
}

function kbuilder_guide_url( $anchor = '' ) {
	return admin_url( 'edit.php?post_type=' . KBUILDER_HOUSE_TYPE . '&page=kbuilder-guide' ) . ( $anchor ? '#' . $anchor : '' );
}

function kbuilder_render_guide_page() {
	include KBUILDER_DIR . 'templates/admin-guide.php';
}
