<?php

require dirname( __FILE__ ) . '/custom-forms/form-builder.php';

add_theme_support( 'app-form-builder', array(
	'show_in_menu' => true
) );

add_action( 'admin_menu', 'va_custom_forms_admin_menu_tweak' );
add_action( 'init', 'va_forms_register_post_type', 11 );
add_filter( 'parent_file', 'va_forms_tax_menu_fix' );
add_action( 'wp_ajax_app-render-listing-form', 'va_forms_ajax_render_listing_form' );

function va_forms_register_post_type() {
	register_taxonomy_for_object_type( VA_LISTING_CATEGORY, APP_FORMS_PTYPE );
}

function va_forms_tax_menu_fix( $parent_file ) {
	global $submenu;

	$listing_tax = get_taxonomy( VA_LISTING_CATEGORY );

	if ( isset( $submenu['edit.php?post_type=custom-form'] ) ) {
		foreach( $submenu['edit.php?post_type=custom-form'] as $k => $submenu_item ) {
			if ( $submenu_item[0] == $listing_tax->labels->menu_name )
				unset( $submenu['edit.php?post_type=custom-form'][$k] );
		}
	}

	return $parent_file;
}

function va_forms_ajax_render_listing_form() {
	if ( empty( $_POST['_' . VA_LISTING_CATEGORY ] ) )
		die;

	$cat = $_POST['_' . VA_LISTING_CATEGORY ];

	$listing_id = !empty( $_POST['listing_id'] ) ? $_POST['listing_id'] : '';

	the_files_editor( $listing_id, __( 'Listing Files', APP_TD ) );
	va_render_form( $cat, VA_LISTING_CATEGORY, $listing_id );
	die;
}

function va_render_form( $categories, $taxonomy, $listing_id = 0 ) {
	$fields = array();

	foreach ( $categories as $category ) {
		foreach ( va_get_fields_for_cat( $category, $taxonomy ) as $field ) {
			$fields[$field['name']] = $field;
			$fields[$field['name']]['cat'] = $category;
		}
	}

	$fields = apply_filters( 'va_render_form_fields', $fields, $listing_id, $categories );
	foreach( $fields as $field ) {
		$html = html( 'div class="form-field"', scbForms::input_from_meta( $field, $listing_id ) );
		echo apply_filters( 'va_render_form_field', $html, $field, $listing_id, $categories, $taxonomy );
	}
}

function va_get_fields_for_cat( $cat, $taxonomy ) {
	$form = get_posts(
		array(
			'fields' => 'ids',
			'post_type' => APP_FORMS_PTYPE,
			'tax_query' => array(
				array(
					'taxonomy' => $taxonomy,
					'terms' => $cat,
					'field' => 'term_id',
					'include_children' => false
				)
			),
			'post_status' => 'publish',
			'numberposts' => 1
		)
	);

	if ( empty( $form ) )
		return array();

	return APP_Form_Builder::get_fields( $form[0] );
}

function va_custom_forms_admin_menu_tweak() {
	global $menu;
	$custom_forms_position = 26;

	foreach( $menu as $menu_k => $menu_item ) {
		if ( !empty( $menu_item[5] ) && 'menu-posts-custom-form' == $menu_item[5] ){
			$custom_forms_position = $menu_k;
			break;
		}
	}

	$menu[9] = $menu[ $custom_forms_position ];
	unset( $menu[ $custom_forms_position ] );
}
