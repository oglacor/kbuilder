<?php
/**
 * "K-Builder (full screen)" page template: the builder fills the window,
 * without the theme's header, footer or sidebar.
 */

defined( 'ABSPATH' ) || exit;

define( 'KBUILDER_PAGE_TEMPLATE', 'kbuilder-fullscreen' );

add_filter( 'theme_page_templates', 'kbuilder_add_page_template' );
// After Divi's Theme Builder (98), which would otherwise wrap the page in its header and footer.
add_filter( 'template_include', 'kbuilder_use_page_template', 100 );

function kbuilder_add_page_template( $templates ) {
	$templates[ KBUILDER_PAGE_TEMPLATE ] = __( 'K-Builder (full screen)', 'kbuilder' );

	return $templates;
}

function kbuilder_is_fullscreen_page() {
	return is_page() && KBUILDER_PAGE_TEMPLATE === get_page_template_slug( get_queried_object_id() );
}

function kbuilder_use_page_template( $template ) {
	return kbuilder_is_fullscreen_page() ? KBUILDER_DIR . 'templates/fullscreen.php' : $template;
}
