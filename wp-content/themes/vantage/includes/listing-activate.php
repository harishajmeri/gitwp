<?php

add_action( 'appthemes_transaction_completed', 'va_handle_completed_transaction' );
add_action( 'pending_to_publish', '_va_handle_moderated_transaction');
add_action( 'pending-claimed_to_publish', '_va_handle_moderated_transaction');

add_action( 'appthemes_transaction_activated', '_va_activate_plan');
add_action( 'appthemes_transaction_activated', '_va_activate_addons');

function va_handle_completed_transaction( $order ){
	global $va_options;

	if( get_post_type( _va_get_order_post_id( $order ) ) != VA_LISTING_PTYPE )
		return;

	$needs_moderation = false;

	$listing_id = _va_get_order_listing_id( $order );

	if ( _va_is_claimed( $order ) && $va_options->moderate_claimed_listings ){
		va_update_post_status( $listing_id, 'pending-claimed' );
		return;
	} else if ( _va_is_claimed( $order ) ) {
		$order->activate();
		return;
	}

	if ( _va_is_renewal_order( $order ) ) {
		va_update_post_status( $listing_id, 'publish' );
		$order->activate();
		return;
	}

	if ( $va_options->moderate_listings ) {
		va_update_post_status( $listing_id, 'pending' );
		return;
	}

	va_update_post_status( $listing_id, 'publish' );
	$order->activate();

}

function _va_handle_moderated_transaction( $post ){

	if( $post->post_type != VA_LISTING_PTYPE )
		return;

	$order = _va_get_listing_order( $post->ID );
	if( !$order || $order->get_status() !== APPTHEMES_ORDER_COMPLETED )
		return;

	add_action( 'save_post', '_va_activate_moderated_transaction', 11);
}

function _va_activate_moderated_transaction( $post_id ){

	if( get_post_type( $post_id ) != VA_LISTING_PTYPE )
		return;

	$order = _va_get_listing_order( $post_id );
	$order->activate();

}

function _va_get_order_listing_id( $order ){
	$item = $order->get_item();
	return $item['post_id'];
}

function _va_order_connection_post_status_fix( $wp_query ) {
	if ( !isset( $wp_query->_p2p_capture ) )
		return;

	if ( ( in_array( VA_LISTING_PTYPE, $wp_query->query['post_type'] ) || ( va_events_enabled() && in_array( VA_EVENT_PTYPE, $wp_query->query['post_type'] ) ) ) && in_array( APPTHEMES_ORDER_PTYPE, $wp_query->query['post_type'] ) ) {
		$wp_query->set( 'post_status', 'any' );
	}
}

function _va_get_last_plan_info( $listing_id ){

	$valid_plan_names = array();

	$plans = new WP_Query( array( 
		'post_type' => APPTHEMES_PRICE_PLAN_PTYPE, 
		'nopaging' => 1,
		'post_status' => 'any' 
	) );

	foreach( $plans->posts as $key => $plan){
		$plans_array[ $plan->post_name ] = $plan;
		$valid_plan_names[] = $plan->post_name;
	} 

	add_action( 'parse_query', '_va_order_connection_post_status_fix' );

	$connected = new WP_Query( array(
		'connected_type' => APPTHEMES_ORDER_CONNECTION,
		'connected_to' => $listing_id,
		'connected_meta' => array(
			array(
				'key' => 'type',
				'value' => $valid_plan_names,
				'compare' => 'IN',
			)
		),
		'post_status' => array( APPTHEMES_ORDER_COMPLETED, APPTHEMES_ORDER_ACTIVATED ),
		'nopaging' => true
	) );

	if( ! $connected->posts )
		return false;

	$plan_name = p2p_get_meta( $connected->posts[0]->p2p_id, 'type', true );
	
	$plan_info = get_post_custom( $plans_array[ $plan_name ]->ID );
	$plan_info['ID'] = $plans_array[ $plan_name ]->ID;
	return $plan_info;

}

function _va_get_order_listing_info( $order ){

	$plans = new WP_Query( array( 'post_type' => APPTHEMES_PRICE_PLAN_PTYPE, 'nopaging' => 1, 'post_status' => 'any' ) );
	foreach( $plans->posts as $key => $plan){
		if ( empty( $plan->post_name ) )
			continue;

		$plan_slug = $plan->post_name;

		$items = $order->get_items( $plan_slug );
		if( $items ){
			$plan_data = va_get_plan_options( $plan->ID );
			return array(
				'listing_id' => $items[0]['post_id'],
				'listing' => $items[0]['post'],
				'plan' => $plan,
				'plan_data' => $plan_data
			);
		}
	} 

	return false;
}

function _va_get_order_post_id( $order ){
	$item = $order->get_item();
	return $item['post_id'];
}

function _va_get_listing_order( $listing_id ){

	$connected = new WP_Query( array(
		'connected_type' => APPTHEMES_ORDER_CONNECTION,
		'connected_to' => $listing_id,
		'nopaging' => true
	) );

	if( ! $connected->posts )
		return false;
	else
		return appthemes_get_order( $connected->post->ID );

}

function _va_activate_plan( $order ){

	if( get_post_type( _va_get_order_post_id( $order ) ) != VA_LISTING_PTYPE )
		return;
		
	$listing_data =  _va_get_order_listing_info($order);
	if( !$listing_data )
		return;

	extract( $listing_data );

	if( _va_needs_publish( $listing ) )
		va_update_post_status( $listing_id, 'publish' );

	update_post_meta( $listing_id, 'listing_duration', $plan_data['duration'] );

	foreach( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon ){
		if( !empty( $plan_data[$addon] ) ){
			va_add_featured( $listing_id, $addon, $plan_data[ $addon . '_duration' ] );
		}
	}

}

function _va_activate_addons( $order ){
	global $va_options;

	if( get_post_type( _va_get_order_post_id( $order ) ) != VA_LISTING_PTYPE )
		return;
		
	foreach( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon ){
		foreach( $order->get_items( $addon ) as $item ){
			va_add_featured( $item['post_id'], $addon, $va_options->addons[$addon]['duration'] );
		}
	}

}

function _va_is_claimed( $order ){
	$claimee = $order->get_item( VA_LISTING_CLAIM_ITEM );
	return !empty( $claimee );
}

function _va_is_renewal_order( $order ){
	$renew = $order->get_item( VA_LISTING_RENEW_ITEM );
	return !empty( $renew );
}
