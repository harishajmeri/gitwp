<?php

class APP_PayPal_Notifier{

	private static $callbacks = array(
		'—' => array(),
		'adjustment' => array(),
		'cart' => array(),
		'express_checkout' => array(),
		'masspay' => array(),
		'mp_signup' => array(),
		'merch_pmt' => array(),
		'new_case' => array(),
		'recurring_payment' => array(),
		'recurring_payment_expired' => array(),
		'recurring_payment_profile_created' => array(),
		'recurring_payment_skipped' => array(),
		'send_money' => array(),
		'subcr_cancel' => array(),
		'subcr_eot' => array(),
		'subcr_failed' => array(),
		'subcr_modify' => array(),
		'subcr_payment' => array(),
		'subcr_signup' => array(),
		'web_accept' => array(),
	);

	public static function register_callback( $type, $status, $callback ){
		if( isset( self::$callbacks[ $type ][ $status ] ) )
			self::$callbacks[ $type ][ $status ][] = $callback;
		else
			self::$callbacks[ $type ][ $status ] = array( $callback );
	}

	public static function notify( $type, $status, $order ){
		do_action( 'appthemes_paypal_ipn_register_callbacks' );

		if( !isset( self::$callbacks[ $type ][ $status ] ) )
			return;

		foreach( self::$callbacks[ $type ][ $status ] as $callback ){
			call_user_func( $callback, $order );
		}

	}

	public static function notify_email( $type, $status, $order ){

		$options = APP_Gateway_Registry::get_gateway_options( 'paypal' );
		if( ! in_array( $status, $options['notifications'] ) )
			return;
		
		echo 'sending email';

	}

}