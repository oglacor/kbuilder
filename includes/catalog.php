<?php
/**
 * Product catalog: color palettes and house designs.
 *
 * A color holds what the picker shows (hex or texture swatch) and how it tints
 * the photo (blend mode + opacity). The on-screen preview and the PDF render
 * both read these values, so a finish only has to be defined here.
 */

defined( 'ABSPATH' ) || exit;

function kbuilder_image_url( $file ) {
	return KBUILDER_URL . 'assets/images/' . $file;
}

/**
 * Palettes keyed by slug. Regions pick one with their `palette` key.
 *
 * Color keys: name, hex, blend (any CSS mix-blend-mode), opacity, texture (optional swatch image URL).
 */
function kbuilder_get_palettes() {
	$paint = array(
		'white'     => array( 'name' => __( 'Blanco', 'kbuilder' ), 'hex' => '#F9FDFE', 'blend' => 'color', 'opacity' => 1 ),
		'pink'      => array( 'name' => __( 'Rosa Mexicano', 'kbuilder' ), 'hex' => '#E4007C', 'blend' => 'multiply', 'opacity' => 0.8 ),
		'cobalt'    => array( 'name' => __( 'Azul Cobalto', 'kbuilder' ), 'hex' => '#0047AB', 'blend' => 'multiply', 'opacity' => 1 ),
		'mustard'   => array( 'name' => __( 'Mostaza', 'kbuilder' ), 'hex' => '#EAC102', 'blend' => 'multiply', 'opacity' => 1 ),
		'terracota' => array( 'name' => __( 'Terracotta', 'kbuilder' ), 'hex' => '#AD7D60', 'blend' => 'multiply', 'opacity' => 1 ),
		'tezontle'  => array( 'name' => __( 'Tezontle', 'kbuilder' ), 'hex' => '#A55749', 'blend' => 'multiply', 'opacity' => 1 ),
		'cantera'   => array( 'name' => __( 'Gris Cantera', 'kbuilder' ), 'hex' => '#B6B6B6', 'blend' => 'multiply', 'opacity' => 1 ),
		'concrete'  => array( 'name' => __( 'Gris Concreto', 'kbuilder' ), 'hex' => '#878883', 'blend' => 'multiply', 'opacity' => 1 ),
		'red'       => array( 'name' => __( 'Rojo', 'kbuilder' ), 'hex' => '#E91E2F', 'blend' => 'multiply', 'opacity' => 0.8 ),
		'teal'      => array( 'name' => __( 'Aqua', 'kbuilder' ), 'hex' => '#00A99D', 'blend' => 'multiply', 'opacity' => 1 ),
	);

	// Same paints, but white brightens the roof cap instead of desaturating it.
	$roof                   = $paint;
	$roof['white']['blend'] = 'screen';

	$frames = array(
		'white'      => array( 'name' => __( 'Blanco', 'kbuilder' ), 'hex' => '#F9FDFE', 'blend' => 'normal', 'opacity' => 0.6 ),
		'mahogany'   => array( 'name' => __( 'Caoba', 'kbuilder' ), 'hex' => '#321616', 'blend' => 'darken', 'opacity' => 0.8, 'texture' => kbuilder_image_url( 'mahogany.jpg' ) ),
		'golden-oak' => array( 'name' => __( 'Roble', 'kbuilder' ), 'hex' => '#8F5D19', 'blend' => 'multiply', 'opacity' => 0.5, 'texture' => kbuilder_image_url( 'oak.jpg' ) ),
		'bronze'     => array( 'name' => __( 'Gris Antracita', 'kbuilder' ), 'hex' => '#2E2F2A', 'blend' => 'multiply', 'opacity' => 0.8, 'texture' => kbuilder_image_url( 'bronze.jpg' ) ),
		'nogal'      => array( 'name' => __( 'Nogal', 'kbuilder' ), 'hex' => '#5C4026', 'blend' => 'multiply', 'opacity' => 0.8, 'texture' => kbuilder_image_url( 'nogal.jpg' ) ),
		'black'      => array( 'name' => __( 'Negro', 'kbuilder' ), 'hex' => '#0D0D0D', 'blend' => 'normal', 'opacity' => 0.8 ),
		'brown'      => array( 'name' => __( 'Café Decor', 'kbuilder' ), 'hex' => '#1C1209', 'blend' => 'multiply', 'opacity' => 0.8 ),
		'shiefeld'   => array( 'name' => __( 'Fresno Americano', 'kbuilder' ), 'hex' => '#999280', 'blend' => 'multiply', 'opacity' => 0.8, 'texture' => kbuilder_image_url( 'shiefeld.jpg' ) ),
		'silver'     => array( 'name' => __( 'Plata', 'kbuilder' ), 'hex' => '#B3B3B3', 'blend' => 'hue', 'opacity' => 1 ),
		'beige'      => array( 'name' => __( 'Beige', 'kbuilder' ), 'hex' => '#D8CFBD', 'blend' => 'normal', 'opacity' => 0.5 ),
	);

	return apply_filters(
		'kbuilder_palettes',
		array(
			'paint'  => $paint,
			'roof'   => $roof,
			'frames' => $frames,
		)
	);
}

/**
 * House designs keyed by slug. Each file in /houses returns one design;
 * see houses/house-1.php for the format.
 */
function kbuilder_get_houses() {
	$houses = array(
		'house-1' => require KBUILDER_DIR . 'houses/house-1.php',
		// 'house-2' => require KBUILDER_DIR . 'houses/house-2.php',
	);

	return apply_filters( 'kbuilder_houses', $houses );
}

/**
 * Nav icon artwork (68×63 viewBox) for each region type.
 */
function kbuilder_get_region_icon( $icon ) {
	$icons = array(
		'roof'    => array( 'd' => 'M58.89,44.89l-9.76-25.04c-.17-.44-.59-.72-1.06-.72h-9.38v-4.61c0-.63-.51-1.14-1.14-1.14h-4.15c-.63,0-1.14.51-1.14,1.14v4.61h-12.75c-.38,0-.73.19-.94.5-.05.07-.09.15-.12.23l-9.76,25.04c-.11.28-.1.59.02.87.12.28.35.49.63.6.13.05.27.08.41.08.47,0,.89-.28,1.06-.72l8.7-22.32,8.7,22.32c.17.44.59.72,1.06.72h28.55c.38,0,.73-.19.94-.5.21-.31.26-.7.12-1.05ZM35.05,40.18l1.55,3.99h-6.55l-1.55-3.99h6.55ZM32.61,33.92l1.55,3.99h-6.55l-1.55-3.99h6.55ZM30.17,27.66l1.55,3.99h-6.55l-1.55-3.99h6.55ZM39.04,44.17l-8.87-22.76h2.09v2.22c0,.63.51,1.14,1.14,1.14s1.14-.51,1.14-1.14v-7.97h1.88v7.97c0,.63.51,1.14,1.14,1.14s1.14-.51,1.14-1.14v-2.22h8.6l8.87,22.76h-17.12ZM29.28,25.39h-6.55l-1.55-3.99h6.55l1.55,3.99Z' ),
		'walls'   => array( 'd' => 'M50.62,27.05h-7.1v-5.39c0-.8-.65-1.45-1.45-1.45h-7.1v-5.39c0-.8-.65-1.45-1.45-1.45h-17.11c-.8,0-1.45.65-1.45,1.45v34.22c0,.8.65,1.45,1.45,1.45h34.22c.8,0,1.45-.65,1.45-1.45v-20.53c0-.8-.65-1.45-1.45-1.45ZM49.16,29.96v3.94h-14.2v-3.94h14.2ZM17.85,20.21v-3.94h14.2v3.94h-14.2ZM26.41,27.05v-3.94h14.2v3.94h-14.2ZM17.85,27.05v-3.94h5.65v3.94h-5.65ZM49.16,43.64v3.94h-14.2v-3.94h14.2ZM40.61,36.8v3.94h-14.2v-3.94h14.2ZM17.85,33.89v-3.94h14.2v3.94h-14.2ZM17.85,40.74v-3.94h5.65v3.94h-5.65ZM32.05,43.64v3.94h-14.2v-3.94h14.2ZM49.16,36.8v3.94h-5.65v-3.94h5.65Z' ),
		'windows' => array( 'd' => 'M48.16,46.33h-.14V13.71c0-.46-.38-.84-.84-.84h-28.34c-.46,0-.84.38-.84.84v32.62h-.14c-1.44,0-2.61,1.17-2.61,2.61v2.17c0,.46.38.84.84.84h33.85c.46,0,.84-.38.84-.84v-2.17c0-1.44-1.17-2.61-2.61-2.61ZM19.68,46.33V14.55h26.66v31.78h-26.66ZM49.09,48.94v1.33H16.92v-1.33c0-.51.42-.93.93-.93h30.31c.51,0,.93.42.93.93ZM44.03,16.02h-22.04c-.46,0-.84.38-.84.84v27.16c0,.46.38.84.84.84h22.04c.46,0,.84-.38.84-.84v-27.16c0-.46-.38-.84-.84-.84ZM43.19,17.7v25.48h-20.36v-25.48h20.36ZM25.85,30.71c-.22-.41-.07-.92.33-1.14l12.85-7.05c.41-.22.92-.07,1.14.33.11.2.13.42.07.64-.06.22-.21.39-.4.5l-12.85,7.05c-.12.07-.26.1-.4.1-.31,0-.59-.17-.74-.43ZM26.58,38.47c.14,0,.28-.04.4-.1l12.85-7.05c.41-.22.55-.73.33-1.14-.22-.41-.74-.55-1.14-.33l-12.85,7.05c-.41.22-.55.73-.33,1.14.15.27.43.43.74.43ZM26.58,38.05h0s0,0,0,0ZM26.7,38.02h0s0,0,0,0Z' ),
		'doors'   => array(
			'd'    => 'M21 12h26v37.5h5V52H16v-2.5h5ZM23.5 14.5v35h21v-35ZM40.5 30.2a1.8 1.8 0 1 1 0 3.6a1.8 1.8 0 1 1 0-3.6Z',
			'rule' => 'evenodd',
		),
	);

	return isset( $icons[ $icon ] ) ? $icons[ $icon ] : $icons['walls'];
}
