<?php

// Replace any children the "Categories" menu item might have with the category dropdown
add_filter( 'wp_nav_menu_objects', 'va_disable_cat_children_menu', 10, 2 );
add_filter( 'walker_nav_menu_start_el', 'va_insert_cat_dropdown_menu', 10, 4 );


function va_disable_cat_children_menu( $items, $args ) {
	foreach ( $items as $key => $item ) {
		if ( $item->object_id == VA_Listing_Categories::get_id() ) {
			$item->current_item_ancestor = false;
			$item->current_item_parent = false;
			$menu_id = $item->ID;
		}
	}

	if ( isset( $menu_id ) ) {
		foreach ( $items as $key => $item )
			if ( $item->menu_item_parent == $menu_id )
				unset( $items[$key] );
	}

	return $items;
}

function va_insert_cat_dropdown_menu( $item_output, $item, $depth, $args ) {
	if ( $item->object_id == VA_Listing_Categories::get_id() ) {
		$item_output .= '<div class="adv_categories" id="adv_categories">' . va_cat_menu_drop_down( 'menu', VA_LISTING_CATEGORY ) . '</div>';
	}
	return $item_output;
}

function va_cat_menu_drop_down( $location = 'menu', $taxonomy ) {
	global $va_options;

	$key = 'categories_' . $location;
	$options = $va_options->$key;

	$args['menu_cols'] = ( $location == 'menu' ? 3 : 2 );
	$args['menu_depth'] = $options['depth'];
	$args['menu_sub_num'] = $options['sub_num'];
	$args['cat_parent_count'] = $options['count'];
	$args['cat_child_count'] = $options['count'];
	$args['cat_hide_empty'] = $options['hide_empty'];
	$args['cat_nocatstext'] = true;
	$args['cat_order'] = 'ASC';
	$args['taxonomy'] = $taxonomy;

	$terms_args['pad_counts'] = false;
	$terms_args['app_pad_counts'] = true;

	return va_categories_list($args, $terms_args);
}

/**
 * Create categories list.
 *
 * @param array $args
 * @param array $terms_args
 *
 * @return string
 */
function va_categories_list( $args, $terms_args = array() ) {

	$defaults = array(
		'menu_cols' => 2,
		'menu_depth' => 3,
		'menu_sub_num' => 3,
		'cat_parent_count' => false,
		'cat_child_count' => false,
		'cat_hide_empty' => false,
		'cat_nocatstext' => true,
		'taxonomy' => 'category',
	);

	$options = wp_parse_args( (array)$args, $defaults );

	$terms_defaults = array(
		'hide_empty' => false,
		'hierarchical' => true,
		'pad_counts' => true,
		'show_count' => true,
		'orderby' => 'name',
		'order' => 'ASC',
	);

	$terms_args = wp_parse_args( (array)$terms_args, $terms_defaults );

	// get all terms for the taxonomy
	$terms = get_terms( $options['taxonomy'], $terms_args );
	$cats = array();
	$subcats = array();
	$cat_menu = '';

	if ( !empty( $terms ) ) {
		// separate into cats and subcats arrays
		foreach ( $terms as $key => $value ) {
			if ( $value->parent == 0 )
				$cats[$key] = $terms[$key];
			else
				$subcats[$key] = $terms[$key];
			unset( $terms[$key] );
		}

		$i = 0;
		$cat_cols = $options['menu_cols']; // menu columns
		$total_main_cats = count( $cats ); // total number of parent cats
		$cats_per_col = ceil( $total_main_cats / $cat_cols ); // parent cats per column

		// loop through all the cats
		foreach ( $cats as $cat ) :

			if ( ( $i == 0 ) || ( $i == $cats_per_col ) || ( $i == ( $cats_per_col * 2 ) ) || ( $i == ( $cats_per_col * 3 ) ) ) {
				if ( $i == 0 ) $first = ' first'; else $first = '';
				$cat_menu .= '<div class="catcol '. $first .'">';
				$cat_menu .= '<ul class="maincat-list">';
			}

		// only show the total count if option is set
		$show_count = $options['cat_parent_count'] ? '<span class="cat-item-count"> ('. $cat->count .') </span>' : '';

		$cat_menu .= '<li class="maincat cat-item-'. $cat->term_id .'"><a href="'. get_term_link( $cat, $options['taxonomy'] ) .'" title="'. esc_attr( $cat->description ) .'">'. $cat->name . $show_count . '</a>';
		if ( $options['menu_sub_num'] > 0 ) {
			// create child tree
			$temp_menu = appthemes_create_child_list( $subcats, $options['taxonomy'], $cat->term_id, 0, $options['menu_depth'], $options['menu_sub_num'], $options['cat_child_count'], $options['cat_hide_empty'] );
			if ( $temp_menu )
				$cat_menu .= $temp_menu;
			if ( !$temp_menu && !$options['cat_nocatstext'] )
				$cat_menu .= '<ul class="subcat-list"><li class="cat-item">'.__( 'No categories', APP_TD ).'</li></ul>';
		}
		$cat_menu .= '</li>';

		if ( ( $i == ( $cats_per_col - 1 ) ) || ( $i == ( ( $cats_per_col * 2 ) - 1 ) ) || ( $i == ( ( $cats_per_col * 3 ) - 1 ) ) || ( $i == ( $total_main_cats - 1 ) ) ) {
			$cat_menu .= '</ul>';
			$cat_menu .= '</div><!-- /catcol -->';
		}
		$i++;

		endforeach;

	}

	return $cat_menu;

}