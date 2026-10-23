<?php
/**
 * House models and finishes, edited under the K-Builder admin menu.
 *
 * Both are private post types: they only reach visitors through the builder.
 */

defined( 'ABSPATH' ) || exit;

define( 'KBUILDER_HOUSE_TYPE', 'kbuilder_house' );
define( 'KBUILDER_FINISH_TYPE', 'kbuilder_finish' );
define( 'KBUILDER_HOUSE_META', '_kbuilder_house' );
define( 'KBUILDER_FINISH_META', '_kbuilder_finish' );

add_action( 'init', 'kbuilder_register_post_types' );

function kbuilder_register_post_types() {
	$common = array(
		'public'       => false,
		'show_ui'      => true,
		'show_in_rest' => false, // Classic edit screen: the zone uploads post with the form.
		'hierarchical' => false,
		'rewrite'      => false,
		'query_var'    => false,
		// Editors can manage the catalog; the lead form setting stays admin-only.
		'capability_type' => 'page',
		'map_meta_cap'    => true,
		'supports'        => array( 'title', 'page-attributes' ),
	);

	register_post_type(
		KBUILDER_HOUSE_TYPE,
		array_merge(
			$common,
			array(
				'labels'        => array(
					'name'               => __( 'House models', 'kbuilder' ),
					'singular_name'      => __( 'House model', 'kbuilder' ),
					'menu_name'          => __( 'K-Builder', 'kbuilder' ),
					'all_items'          => __( 'House models', 'kbuilder' ),
					'add_new'            => __( 'Add house model', 'kbuilder' ),
					'add_new_item'       => __( 'Add house model', 'kbuilder' ),
					'edit_item'          => __( 'Edit house model', 'kbuilder' ),
					'new_item'           => __( 'New house model', 'kbuilder' ),
					'search_items'       => __( 'Search house models', 'kbuilder' ),
					'not_found'          => __( 'No house models yet.', 'kbuilder' ),
					'not_found_in_trash' => __( 'No house models in the trash.', 'kbuilder' ),
				),
				'show_in_menu'  => true,
				'menu_position' => 58,
				'menu_icon'     => 'dashicons-admin-home',
			)
		)
	);

	register_post_type(
		KBUILDER_FINISH_TYPE,
		array_merge(
			$common,
			array(
				'labels'       => array(
					'name'               => __( 'Finishes', 'kbuilder' ),
					'singular_name'      => __( 'Finish', 'kbuilder' ),
					'all_items'          => __( 'Finishes', 'kbuilder' ),
					'add_new'            => __( 'Add finish', 'kbuilder' ),
					'add_new_item'       => __( 'Add finish', 'kbuilder' ),
					'edit_item'          => __( 'Edit finish', 'kbuilder' ),
					'new_item'           => __( 'New finish', 'kbuilder' ),
					'search_items'       => __( 'Search finishes', 'kbuilder' ),
					'not_found'          => __( 'No finishes yet.', 'kbuilder' ),
					'not_found_in_trash' => __( 'No finishes in the trash.', 'kbuilder' ),
				),
				'show_in_menu' => 'edit.php?post_type=' . KBUILDER_HOUSE_TYPE,
			)
		)
	);
}
