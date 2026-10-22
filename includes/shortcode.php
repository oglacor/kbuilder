<?php
/**
 * [kbuilder] shortcode and front-end assets.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'kbuilder_register_assets' );
add_action( 'init', 'kbuilder_register_shortcode' );
add_action( 'wp_enqueue_scripts', 'kbuilder_enqueue_for_singular' );

function kbuilder_register_assets() {
	wp_register_style( 'kbuilder-fonts', KBUILDER_URL . 'assets/css/fonts.css', array(), KBUILDER_VERSION );
	wp_register_style( 'kbuilder', KBUILDER_URL . 'assets/css/style.css', array( 'kbuilder-fonts' ), KBUILDER_VERSION );
	wp_register_script( 'kbuilder-jspdf', KBUILDER_URL . 'assets/vendor/jspdf/jspdf.umd.min.js', array(), '4.2.1', true );
	wp_register_script( 'kbuilder', KBUILDER_URL . 'assets/js/kbuilder.js', array( 'jquery', 'kbuilder-jspdf' ), KBUILDER_VERSION, true );
}

function kbuilder_register_shortcode() {
	add_shortcode( 'kbuilder', 'kbuilder_render_shortcode' );
}

/**
 * Load the stylesheet in <head> when the current post uses the shortcode,
 * so the builder doesn't flash unstyled. The shortcode enqueues it too, for
 * page builders and widgets this check can't see.
 */
function kbuilder_enqueue_for_singular() {
	$post = get_post();

	if ( is_singular() && $post && has_shortcode( $post->post_content, 'kbuilder' ) ) {
		wp_enqueue_style( 'kbuilder' );
	}
}

function kbuilder_render_shortcode( $atts ) {
	static $instance = 0;

	$atts   = shortcode_atts( array( 'house' => '' ), $atts, 'kbuilder' );
	$houses = kbuilder_get_houses();

	if ( empty( $houses ) ) {
		return '';
	}

	$palettes     = kbuilder_get_palettes();
	$active_house = isset( $houses[ $atts['house'] ] ) ? $atts['house'] : key( $houses );
	$settings     = kbuilder_get_settings();

	$form_html = '';
	if ( $settings['form_id'] && shortcode_exists( 'fluentform' ) ) {
		$form_html = do_shortcode( sprintf( '[fluentform id="%d"]', $settings['form_id'] ) );
	}
	$show_notice = '' === trim( $form_html ) && current_user_can( 'manage_options' );

	wp_enqueue_style( 'kbuilder' );
	wp_enqueue_script( 'kbuilder' );

	$instance++;
	$instance_id = 'kbuilder-' . $instance;
	$config      = kbuilder_get_js_config( $houses, $palettes, $active_house, $settings, '' !== trim( $form_html ) );

	ob_start();
	include KBUILDER_DIR . 'templates/builder.php';

	return ob_get_clean();
}

/**
 * Data the front-end script needs. Shapes stay in the SVG markup; the script
 * reads them from there when it renders the PDF.
 */
function kbuilder_get_js_config( $houses, $palettes, $active_house, $settings, $has_form ) {
	$houses_js = array();
	foreach ( $houses as $house_key => $house ) {
		$regions = array();
		foreach ( $house['regions'] as $region_key => $region ) {
			$regions[ $region_key ] = array(
				'label'   => $region['label'],
				'palette' => $region['palette'],
				'default' => isset( $region['default'] ) ? $region['default'] : '',
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

	$config = array(
		'house'       => $active_house,
		'hasForm'     => $has_form,
		'designField' => KBUILDER_DESIGN_FIELD,
		'houses'      => $houses_js,
		'palettes'    => $palettes,
		'pdf'         => array(
			'logo'     => kbuilder_image_url( 'kbuilder-logo.svg' ),
			'contact'  => $settings['pdf_contact'],
			'site'     => wp_parse_url( home_url(), PHP_URL_HOST ),
			'filename' => 'k-builder',
		),
		'i18n'        => array(
			'original'    => __( 'Color original', 'kbuilder' ),
			'generating'  => __( 'Generando PDF…', 'kbuilder' ),
			'pdfError'    => __( 'No pudimos generar el PDF. Por favor intenta de nuevo.', 'kbuilder' ),
			'pdfTitle'    => __( 'Tu diseño personalizado', 'kbuilder' ),
			'preparedFor' => __( 'Preparado para:', 'kbuilder' ),
			'selections'  => __( 'Selección de colores', 'kbuilder' ),
			'nextSteps'   => __( 'Próximos pasos', 'kbuilder' ),
			'disclaimer'  => __( 'Imagen ilustrativa. Los colores son una simulación digital y pueden variar respecto al producto real.', 'kbuilder' ),
			'dateLocale'  => 'es-MX',
		),
	);

	return apply_filters( 'kbuilder_js_config', $config );
}
