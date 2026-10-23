<?php
/**
 * Plugin Name:       K-Builder
 * Description:       Configurador de fachadas Karleton: elige un modelo de casa, cambia los acabados de techo, paredes, ventanas y puertas y descarga tu diseño en PDF después de dejar tus datos (Fluent Forms). Modelos y acabados se administran en K-Builder.
 * Version:           2.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Blue Rabbit
 * Author URI:        https://bluerabbit.io
 * Text Domain:       kbuilder
 */

defined( 'ABSPATH' ) || exit;

define( 'KBUILDER_VERSION', '2.0.0' );
define( 'KBUILDER_FILE', __FILE__ );
define( 'KBUILDER_DIR', plugin_dir_path( __FILE__ ) );
define( 'KBUILDER_URL', plugin_dir_url( __FILE__ ) );

// Every house render and zone SVG uses this canvas, so shapes line up with the photo.
define( 'KBUILDER_CANVAS_WIDTH', 1920 );
define( 'KBUILDER_CANVAS_HEIGHT', 1080 );

require_once KBUILDER_DIR . 'includes/post-types.php';
require_once KBUILDER_DIR . 'includes/catalog.php';
require_once KBUILDER_DIR . 'includes/svg.php';
require_once KBUILDER_DIR . 'includes/install.php';
require_once KBUILDER_DIR . 'includes/settings.php';
require_once KBUILDER_DIR . 'includes/shortcode.php';
require_once KBUILDER_DIR . 'includes/page-template.php';

if ( is_admin() ) {
	require_once KBUILDER_DIR . 'includes/admin.php';
	require_once KBUILDER_DIR . 'includes/admin-houses.php';
	require_once KBUILDER_DIR . 'includes/admin-finishes.php';
}
