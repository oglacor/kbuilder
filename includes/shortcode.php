<?php
/**
 * [kbuilder] shortcode, the builder renderer it shares with the full-screen
 * template, and the front-end assets.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'kbuilder_register_assets' );
add_action( 'init', 'kbuilder_register_shortcode' );
add_action( 'wp_enqueue_scripts', 'kbuilder_enqueue_for_singular' );

function kbuilder_register_assets() {
	wp_register_style( 'kbuilder-fonts', KBUILDER_URL . 'assets/css/fonts.css', array(), KBUILDER_VERSION );
	wp_register_style( 'kbuilder', KBUILDER_URL . 'assets/css/style.css', array( 'kbuilder-fonts' ), KBUILDER_VERSION );
	wp_register_script( 'kbuilder-jspdf', KBUILDER_URL . 'assets/vendor/jspdf/jspdf.umd.min.js', array(), '4.2.1', true );
	wp_register_script( 'kbuilder-hotspot', KBUILDER_URL . 'assets/js/hotspot.js', array(), KBUILDER_VERSION, true );
	wp_register_script( 'kbuilder-recolor', KBUILDER_URL . 'assets/js/recolor.js', array(), KBUILDER_VERSION, true );
	wp_register_script( 'kbuilder', KBUILDER_URL . 'assets/js/kbuilder.js', array( 'jquery', 'kbuilder-jspdf', 'kbuilder-hotspot', 'kbuilder-recolor' ), KBUILDER_VERSION, true );
}

function kbuilder_register_shortcode() {
	add_shortcode( 'kbuilder', 'kbuilder_render_shortcode' );
}

/**
 * Load the stylesheet in <head> when the current page shows the builder, so
 * it doesn't flash unstyled. The renderer enqueues it too, for page builders
 * and widgets this check can't see.
 */
function kbuilder_enqueue_for_singular() {
	$post = get_post();

	if ( is_singular() && $post && ( kbuilder_is_fullscreen_page() || has_shortcode( $post->post_content, 'kbuilder' ) ) ) {
		wp_enqueue_style( 'kbuilder' );
	}
}

function kbuilder_render_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'house' => '' ), $atts, 'kbuilder' );

	return kbuilder_render( array( 'house' => $atts['house'] ) );
}

/**
 * Builder markup.
 *
 * @param array $args {
 *     house:      slug of the model to start on (?modelo=slug in the URL wins).
 *     fullscreen: fill the window (page template) instead of sitting in the content.
 * }
 */
function kbuilder_render( $args = array() ) {
	static $instance = 0;

	$args   = wp_parse_args(
		$args,
		array(
			'house'      => '',
			'fullscreen' => false,
		)
	);
	$houses = kbuilder_get_houses();

	if ( empty( $houses ) ) {
		return current_user_can( 'edit_pages' )
			? '<p class="kbuilder-notice">' . esc_html__( 'K-Builder (only editors see this): there are no published house models with a background image and zones yet. Add one in K-Builder → House models.', 'kbuilder' ) . '</p>'
			: '';
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only deep link.
	$requested    = isset( $_GET['modelo'] ) ? sanitize_title( wp_unslash( $_GET['modelo'] ) ) : $args['house'];
	$active_house = isset( $houses[ $requested ] ) ? $requested : key( $houses );
	$finishes     = kbuilder_get_finishes();
	$settings     = kbuilder_get_settings();
	$fullscreen   = (bool) $args['fullscreen'];

	// Finish IDs offered in each zone, in catalog order.
	$region_finishes = array();
	foreach ( kbuilder_get_region_types() as $type => $region_type ) {
		$region_finishes[ $type ] = array();
		foreach ( $finishes as $finish_id => $finish ) {
			if ( in_array( $type, $finish['regions'], true ) ) {
				$region_finishes[ $type ][] = (string) $finish_id;
			}
		}
	}

	$form_html = '';
	if ( $settings['form_id'] && shortcode_exists( 'fluentform' ) ) {
		$form_html = do_shortcode( sprintf( '[fluentform id="%d"]', $settings['form_id'] ) );
	}
	$show_notice = '' === trim( $form_html ) && current_user_can( 'manage_options' );

	wp_enqueue_style( 'kbuilder' );
	wp_enqueue_script( 'kbuilder' );

	$instance++;
	$instance_id = 'kbuilder-' . $instance;
	$config      = kbuilder_get_js_config( $houses, $finishes, $active_house, $settings, '' !== trim( $form_html ) );

	ob_start();
	include KBUILDER_DIR . 'templates/builder.php';

	return ob_get_clean();
}

/**
 * Data the front-end script needs. Shapes stay in the SVG markup; the script
 * reads them from there to paint the finishes.
 */
function kbuilder_get_js_config( $houses, $finishes, $active_house, $settings, $has_form ) {
	$houses_js = array();
	foreach ( $houses as $house_key => $house ) {
		$regions = array();
		foreach ( $house['regions'] as $region_key => $region ) {
			$regions[ $region_key ] = array(
				'label'   => $region['label'],
				'profile' => $region['profile'],
			);
		}
		$houses_js[ $house_key ] = array(
			'name'    => $house['name'],
			'image'   => $house['image'],
			'width'   => (int) $house['width'],
			'height'  => (int) $house['height'],
			'regions' => $regions,
		);
	}

	$finishes_js = array();
	foreach ( $finishes as $finish_id => $finish ) {
		unset( $finish['regions'] );
		$finishes_js[ $finish_id ] = $finish;
	}

	$config = array(
		'house'       => $active_house,
		'hasForm'     => $has_form,
		'designField' => KBUILDER_DESIGN_FIELD,
		'houses'      => $houses_js,
		'finishes'    => (object) $finishes_js,
		'pdf'         => array(
			'logo'     => kbuilder_image_url( 'kbuilder-logo.svg' ),
			'contact'  => $settings['pdf_contact'],
			'site'     => wp_parse_url( home_url(), PHP_URL_HOST ),
			'filename' => 'k-builder',
		),
		'i18n'        => array(
			'original'    => __( 'Original', 'kbuilder' ),
			'generating'  => __( 'Generando PDF…', 'kbuilder' ),
			'pdfError'    => __( 'No pudimos generar el PDF. Por favor intenta de nuevo.', 'kbuilder' ),
			'pdfTitle'    => __( 'Tu diseño personalizado', 'kbuilder' ),
			'preparedFor' => __( 'Preparado para:', 'kbuilder' ),
			'selections'  => __( 'Selección de acabados', 'kbuilder' ),
			'nextSteps'   => __( 'Próximos pasos', 'kbuilder' ),
			'disclaimer'  => __( 'Imagen ilustrativa. Los colores son una simulación digital y pueden variar respecto al producto real.', 'kbuilder' ),
			'dateLocale'  => 'es-MX',
		),
	);

	return apply_filters( 'kbuilder_js_config', $config );
}
