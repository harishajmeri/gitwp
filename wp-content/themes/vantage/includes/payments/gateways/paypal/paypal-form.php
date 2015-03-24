<?php 

class APP_PayPal_Form{
	
	/**
	 * Displays the form for user redirection
	 * @param  APP_Order $order   Order to process
	 * @param  array $options     User inputted options
	 * @return void  
	 */
	public static function create_form( $order, $options, $return_url, $cancel_url ) {
		
		$defaults = array(
			'email_address' => '',
		);

		$options = wp_parse_args( $options, $defaults );

		$fields = array(

			// No Shipping Required
			'noshipping' => 1,

			// Disable the 'Add Note' Paypal Capability
			'no_note' => 1,

			// Return the buyer to our website via POST, and include variables
			'rm' => 0,

			// Use the 'Buy Now' button as the method of purchase
			'cmd' => '_xclick',

			'charset' => 'utf-8',
		);

		// Item Information
		$fields['item_name'] = $order->get_description();
		$fields['item_number'] = $order->get_id();

		// Seller Options
		$fields['business'] = $options['email_address'];
		$fields['currency_code'] = $order->get_currency();

		// Paypal Options
		$fields['cbt'] = sprintf( __( 'Continue to %s', APP_TD ), get_bloginfo( 'name' ) );

		$fields['amount'] = $order->get_total();
		$fields['return'] = $return_url;
		$fields['cancel_return'] = $cancel_url;
		
		$form = array(
			'action' => APP_PayPal::get_request_url(),
			'name' => 'paypal_payform',
			'id' => 'create_listing',
		);

		return array( $form, $fields );

	}

}