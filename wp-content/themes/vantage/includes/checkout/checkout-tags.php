<?php

class VA_Current_Checkout{

	static $checkout = false, $base_url = '';
	public static function register_checkout( $checkout, $base_url ){
		self::$checkout = $checkout;
		self::$base_url = $base_url;
	}

	public static function get_checkout(){
		return self::$checkout;
	}

	public static function get_base_url(){
		return self::$base_url;
	}
	
}

function appthemes_setup_checkout( $checkout_type, $base_page_id ){

	$hash = _appthemes_get_hash_from_query();
	$checkout = new VA_Dynamic_Checkout( $checkout_type, $hash );

	VA_Current_Checkout::register_checkout( $checkout, $base_page_id );
	do_action( 'appthemes_register_checkout_steps_' . $checkout_type, $checkout );

}

function appthemes_process_checkout(){

	$step = _appthemes_get_step_from_query();
	$checkout = VA_Current_Checkout::get_checkout();

	return $checkout->process_step( $step );
}

function appthemes_display_checkout(){

	$step = _appthemes_get_step_from_query();
	$checkout = VA_Current_Checkout::get_checkout();

	return $checkout->display_step( $step );
}

function _appthemes_get_hash_from_query(){

	if( ! empty( $_GET['hash'] ) )
		return $_GET['hash'];
	else
		return '';

}

function _appthemes_get_step_from_query(){

	if( ! empty( $_GET['step'] ) )
		return $_GET['step'];
	else if( $checkout = VA_Current_Checkout::get_checkout() )
		return $checkout->get_next_step();
	else
		return '';
	

}

function appthemes_get_checkout_url( ){
	return VA_Current_Checkout::get_base_url();
}

function appthemes_get_checkout(){
	return VA_Current_Checkout::get_checkout();
}

function appthemes_get_step_url( $step_id = '' ){

	$checkout = appthemes_get_checkout();
	if( empty( $step_id ) ){
		$step_id = $checkout->get_current_step(); 
	}

	$query_args = array(
		'step' => $step_id,
		'hash' => $checkout->get_hash(), 
	);

	return add_query_arg( $query_args, appthemes_get_checkout_url() );

}

