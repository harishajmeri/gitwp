<?php
// Replace any children the "Categories" menu item might have with the category dropdown
add_filter( 'wp_nav_menu_objects', 'va_disable_event_cat_children_menu', 10, 2 );
add_filter( 'walker_nav_menu_start_el', 'va_insert_event_cat_dropdown_menu', 10, 4 );


function va_disable_event_cat_children_menu( $items, $args ) {
	foreach ( $items as $key => $item ) {
		if ( $item->object_id == VA_Event_Categories::get_id() ) {
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

function va_insert_event_cat_dropdown_menu( $item_output, $item, $depth, $args ) {
	if ( $item->object_id == VA_Event_Categories::get_id() ) {
		$item_output .= '<div class="adv_categories" id="adv_categories">' . va_cat_menu_drop_down( 'menu', VA_EVENT_CATEGORY ) . '</div>';
	}
	return $item_output;
}
