<?php

class VA_Gateway_Select extends VA_Checkout_Step{

	public function __construct(){
		parent::__construct( 'gateway-select', array(
			'register_to' => array (
				'create-listing',
				'upgrade-listing',
				'claim-listing' => array( 'after' => 'claim-listing-categories' ),
				'renew-listing' => array( 'after' => 'renew-listing-categories' ),
		       	)
		) );
	}

	public function display( $order, $checkout ){

		query_posts( array( 'p' => $order->get_id(), 'post_type' => APPTHEMES_ORDER_PTYPE ) );
		appthemes_load_template( 'order-select.php' );

	}

	public function process( $order, $checkout ){

		if ( $order->get_total() == 0 ) {
			$order->complete();
			$this->finish_step();
		}

		if( ! empty( $_POST['payment_gateway'] ) ){
			$is_valid = $order->set_gateway( $_POST['payment_gateway'] );
			if( ! $is_valid )
				return;

			$this->finish_step();
		}

	}

}

class VA_Gateway_Process extends VA_Checkout_Step{

	public function __construct(){
		parent::__construct( 'gateway-process', array(
			'register_to' => array(
				'create-listing' => array(
					'after' => 'gateway-select'
				),
				'upgrade-listing',
				'claim-listing',
				'renew-listing',
		       	)
		) );

		add_filter( 'appthemes_order_return_url', array( $this, 'filter_return_url' ) );
	}

	public function display( $order, $checkout ){

		query_posts( array( 'p' => $order->get_id(), 'post_type' => APPTHEMES_ORDER_PTYPE ) );
		appthemes_load_template( 'order-gateway.php' );

	}

	public function process( $order, $checkout ){

		if ( $order->get_total() == 0 ) {
			$this->finish_step();
		}

		if( in_array( $order->get_status(), array( APPTHEMES_ORDER_COMPLETED, APPTHEMES_ORDER_ACTIVATED ) ) ){
			$this->finish_step();
		}else{
			update_post_meta( $order->get_id(), 'redirect_to', appthemes_get_step_url( $checkout->get_next_step( 'gateway_process' ) ) );
			wp_redirect( $order->get_return_url() );
		}

	}

	public function filter_return_url( $url ){
		return add_query_arg( 'step', $this->step_id, $url );
	}

}

class VA_Order_Summary extends VA_Checkout_Step{

	public function __construct(){
		parent::__construct( 'order-summary', array(
			'register_to' => array(
				'create-listing' => array(
					'after' => 'gateway-process'
				),
				'claim-listing' => array(
					'after' => 'gateway-process'
				),
				'renew-listing' => array(
					'after' => 'gateway-process'
				),
				'upgrade-listing' => array(
					'after' => 'gateway-process'
				),
		       	)
		) );
	}

	public function display( $order, $checkout ){

		query_posts( array( 'p' => $order->get_id(), 'post_type' => APPTHEMES_ORDER_PTYPE ) );
		appthemes_load_template( 'order-summary.php' );

	}
}
