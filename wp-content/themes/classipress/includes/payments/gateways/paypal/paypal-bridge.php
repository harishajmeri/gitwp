<?php

class APP_PayPal_Bridge{

	public function __construct(){
		add_action( 'appthemes_paypal_ipn_register_callbacks', array( $this, 'register_hooks' ) );
	}

	public function register_hooks(){
		APP_PayPal_Notifier::register_callback( 'web_accept', 'completed', array( $this, 'complete' ) );

	}
	
	/**
	 * Handles a PayPal Response by notifying the problem callbacks via APP_PayPal_Notifier
	 * @param  array  $response      A series of response variables from PayPal
	 * @param  string $order_limiter An order id. Will fail if response indicates an order other than this one. 
	 *                               	Used for single transactions where the intended ID is known.
	 * @return bool                  True when the order was found and processed. False on error.
	 */
	public function handle_response( $response, $order_limiter = '' ){

		$order = $this->find_order( $response );
		if( !( $order instanceof APP_Order ) ){
			return false;
		}
		if( !empty( $order_limiter ) && $order->get_id() != $order_limiter ){
			return false;
		}

		$type = strtolower( $response['txn_type'] );
		$action = strtolower( $response['payment_status'] );

		APP_PayPal_Notifier::notify( $type, $action, $order );
		return true;
	}

	/**
	 * Locates an order based on PayPal response variables
	 * @param  array $response  A series of response variables from PayPal
	 * @return mxied            APP_Order for the order indicated on success. WP_Error on error.
	 */
	public function find_order( $response ){
		$options = APP_Gateway_Registry::get_gateway_options( 'paypal' );

		$error = new WP_Error;
		$item_number = false;
		if( isset( $response['item_number1'] ) )
			$item_number = $response['item_number1'];

		if( isset( $response['item_number'] ) )
			$item_number = $response['item_number'];

		if( !$item_number ){
			$error->add( 'missing_item_number', 'Response did not include item nuber.');
			return $error;
		}

		$order = appthemes_get_order( $item_number );
		if( !$order ){
			$error->add( 'bad_order', 'Could not find an order with given id.');
			return $error;
		}

		if( !isset( $response['mc_currency'] ) || $order->get_currency() != strtoupper( $response['mc_currency'] ) )
			$error->add( 'bad_currency', 'Response currency did not match order.');

		if( $order->get_gateway() != 'paypal' )
			$error->add( 'bad_gateway', 'Order was not using PayPal as a gateway.');

		if( !isset( $response['mc_gross'] ) || $order->get_total() != $response['mc_gross'] ){	
			$error->add( 'bad_amount', 'Response amount did not match order.');
		}

		if( !isset( $response['business'] ) || strtolower( $options['email_address'] ) != $response['business'] )
			$error->add( 'bad_email', 'Response email did not match settings.');

		if( ! $error->get_error_codes() )
			return $order;
		else
			return $error;

	}

	/**
	 * Completes the given order. Simple, but useful for callbacks
	 * @param  APP_Order $order The order to be completed
	 * @return void        
	 */
	public function complete( $order ){
		$order->complete();
	}
}