<?php
/**
 * Plugin Name:       K-Builder
 * Description:       Configurador de fachadas Karleton: elige un diseño de casa, cambia los colores de techo, paredes y ventanas y descarga tu diseño en PDF después de dejar tus datos (Fluent Forms).
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Blue Rabbit
 * Author URI:        https://bluerabbit.io
 * Text Domain:       kbuilder
 */

defined( 'ABSPATH' ) || exit;

define( 'KBUILDER_VERSION', '1.0.0' );
define( 'KBUILDER_FILE', __FILE__ );
define( 'KBUILDER_DIR', plugin_dir_path( __FILE__ ) );
define( 'KBUILDER_URL', plugin_dir_url( __FILE__ ) );

require_once KBUILDER_DIR . 'includes/catalog.php';
require_once KBUILDER_DIR . 'includes/settings.php';
require_once KBUILDER_DIR . 'includes/shortcode.php';
