<?php

class VA_Select_Plan_New extends VA_Checkout_Step{

	protected $errors;

	public function __construct(){
		$this->setup( 'purchase-listing', array(
			'priority' => 1,
			'register_to' => array(
				'create-listing',
		       	),
		));
	}

	public function display( $order, $checkout ){
		global $va_options;
		
		$plans = $this->get_available_plans();
		appthemes_load_template( 'purchase-listing-new.php', array(
			'plans' => $plans,
			'va_options' => $va_options,
		) );

	}

	protected function get_available_plans() {

		$plans = new WP_Query( array(
			'post_type' => APPTHEMES_PRICE_PLAN_PTYPE,
			'nopaging' => 1,
		) );

		$plans_data = array();
		foreach( $plans->posts as $key => $plan){
			$plans_data[ $key ] = va_get_plan_options( $plan->ID );
			$plans_data[ $key ]['post_data'] = $plan;
		}

		return $plans_data;
	}

	public function process( $order, $checkout ){
		global $va_options;

		if( ! $va_options->listing_charge ) {
			$this->finish_step();
		}

		if ( !isset( $_POST['action'] ) || 'purchase-listing' != $_POST['action'] )
			return;
	
		if ( !current_user_can( 'edit_listings' ) )
			return;

		$this->errors = apply_filters( 'appthemes_validate_purchase_fields', va_get_listing_error_obj() );

		$plan_id = $this->get_plan();
		$addons = $this->get_addons();
		$coupon_code = $this->get_coupon();

		if( $this->errors->get_error_codes() ){
			return false;
		}

		$checkout->add_data( 'plan', $plan_id );
		$checkout->add_data( 'addons', $addons );
		if ( !empty( $coupon_code ) )
			$checkout->add_data( 'coupon-code', $coupon_code );

		$this->finish_step();
	}

	protected function get_plan(){

		if( empty( $_POST['plan'] ) ){
			$this->errors->add( 'no-plan', __( 'No plan was chosen.', APP_TD ) );
			return false;
		}

		$plan = get_post( intval( $_POST['plan'] ) );
		if( ! $plan ){
			$this->errors->add( 'invalid-plan', __( 'The plan you choose no longer exists.', APP_TD ) );
			return false;
		}
		return $plan->ID ;
	}

	protected function get_addons(){

		$addons = array();
		foreach( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon ){

			if( !empty( $_POST[ $addon.'_'.intval( $_POST['plan'] ) ] ) )
				$addons[] = $addon;

		}
		return $addons;
	}
	
	protected function get_coupon(){
		if ( defined('APPTHEMES_COUPON_PTYPE') && !empty( $_POST['coupon-code'] ) ) {
			return $_POST['coupon-code'];
		} else {
			return '';
		}
	}
}

class VA_Select_Plan_Existing extends VA_Select_Plan_New{

	public function __construct(){
		$this->setup( 'upgrade-listing', array(
			'priority' => 1,
			'register_to' => array( 'upgrade-listing' )
		) );
	}

	public function display( $order, $checkout ){
		global $va_options;

		$listing = get_queried_object();
		$prior_plan = _va_get_last_plan_info( get_queried_object_id() );

		if ( !$prior_plan ) {
			$plans = $this->get_available_plans();
			appthemes_load_template( 'purchase-listing-existing-planless.php', array(
				'listing' => $listing,
				'plans' => $plans,
				'va_options' => $va_options,
			) );
		} else {
			$plan_data = va_get_plan_options( $prior_plan['ID'] );
			appthemes_load_template( 'purchase-listing-existing.php', array(
				'listing' => $listing,
				'plan' => $plan_data,
			) );
		}

	}

	public function process( $order, $checkout ){

		if ( !isset( $_POST['action'] ) || 'purchase-listing' != $_POST['action'] )
			return;
	
		if ( !current_user_can( 'edit_listings' ) )
			return;

		$this->errors = apply_filters( 'appthemes_validate_purchase_fields', va_get_listing_error_obj() );

		$listing = get_queried_object();
		$prior_plan = _va_get_last_plan_info( get_queried_object_id() );

		if ( !$prior_plan ) {
			$addons = parent::get_addons( $listing );
			$plan_id = $this->get_plan();
		} else {
			$addons = $this->get_addons( $listing );
		}

		if( $this->errors->get_error_codes() ){
			return false;
		}

		if ( !$prior_plan ) {
			va_add_plan_to_order( $order, get_queried_object_id(), $plan_id );
		}

		va_add_addons_to_order( $order, get_queried_object_id(), $addons );

		$this->finish_step();
	}

	protected function get_addons( $listing ){
	
		$addons = parent::get_addons( $listing );
		foreach( $addons as $k => $addon ){
			if( _va_already_featured( $addon, $listing->ID ) ){
				unset( $addons[ $k ] );
			}
		}
		return $addons;

	}

}

function va_handle_claim_listing_purchase() {
	global $va_options;

	if ( !isset( $_POST['action'] ) || 'claim-listing' != $_POST['action'] )
		return;

	if ( !current_user_can( 'edit_listings' ) )
		return;

	check_admin_referer( 'va_claim_listing' );

	if ( !$va_options->listing_charge) {
		VA_Listing_Claim::handle_no_charge_claim_listing($_POST['ID']);
	}
}

function va_get_addon_options( $addon ){
	global $va_options;

	return array(
		'title' => APP_Item_Registry::get_title( $addon ),
		'price' => appthemes_get_price( APP_Item_Registry::get_meta( $addon, 'price' ) ),
		'duration' => $va_options->addons[ $addon ]['duration']
	);

}

function _va_get_chosen_plan() {
	$plan = get_post( intval( $_POST['plan'] ) );
	if( ! $plan ){
		return false;
	}
	$plan->plan_data = va_get_plan_options( $plan->ID );

	return $plan;
}

function va_get_plan_options( $plan_id ){

	$data = get_post_custom( $plan_id );
	$collapsed_data = array();
	foreach( $data as $key => $array ){
		$collapsed_data[ $key ] = $array[0];
	}

	$collapsed_data['ID'] = $plan_id;

	return $collapsed_data;
}

function va_get_plan_included_categories( $plan_id ) {
	global $va_options;

	$plan = va_get_plan_options( $plan_id );

	if ( !isset( $plan['included_categories'] ) ) {
		return 0;
	} else {
		return $plan['included_categories'];
	}
}

/**
 * Shows the field for an addon that can be purchased
 */
function _va_show_purchasable_featured_addon( $addon_id, $plan_id ){

	$plan = va_get_plan_options( $plan_id );

	$addon = va_get_addon_options( $addon_id );

	if( ! empty( $plan[ $addon_id ] ) ){
		_va_show_featured_option( $addon_id, true, $plan_id );
		if ( $plan[ $addon_id . '_duration' ] == 0 ) {
			$string = __( ' %s is included in this plan for Unlimited days.', APP_TD );
			printf( $string, $addon['title'], $addon['price'] );
		} else {
			$string = _n( '%s is included in this plan for %s day.', '%s is included in this plan for %s days.', $plan[ $addon_id . '_duration' ], APP_TD );
			printf( $string, $addon['title'], $plan[ $addon_id . '_duration' ], $addon['price'] );
		}

	}
	else if( ! _va_addon_disabled( $addon_id ) ){
		_va_show_featured_option( $addon_id, false, $plan_id );
		if( $addon['duration'] == 0 ){
			$string = __( ' %s for Unlimited days for only %s more.', APP_TD );
			printf( $string, $addon['title'], $addon['price'] );
		}else{
			$string = __( ' %s for %d days for only %s more.', APP_TD );
			printf( $string, $addon['title'], $addon['duration'], $addon['price'] );
		}

	}

}

/**
 * Shows the field for an addon that has already been purchased
 */
function _va_show_purchased_featured_addon( $addon_id, $plan_id, $listing_id ){

	$plan = va_get_plan_options( $plan_id );
	$addon = va_get_addon_options( $addon_id );

	_va_show_featured_option( $addon_id, true, $plan_id );

	$expiration_date = va_get_featured_exipration_date( $addon_id, $listing_id );
	if('Never' == $expiration_date) {
		printf( __( ' %s for Unlimited days', APP_TD ), $addon['title'] );
	} else {
		printf( __( ' %s until %s', APP_TD ), $addon['title'], $expiration_date );
	}
	return;

}

function _va_show_featured_addon( $addon, $plan_id ){
	global $va_options;

	$addon_title = APP_Item_Registry::get_title( $addon ); 
	$addon_price = appthemes_get_price( APP_Item_Registry::get_meta( $addon , 'price' ) ); 
	$addon_duration = $va_options->addons[$addon]['duration']; 

	// If already on featured option, output disabled checkbox with expiration date
	if( _va_already_featured( $addon, $listing_id ) ){
		_va_show_featured_option( $addon, true, $plan_id );

		$expiration_date = va_get_featured_exipration_date( $addon, $listing_id );
		if('Never' == $expiration_date) {
			printf( __( ' %s for Unlimited days', APP_TD ), $addon_title);
		} else {
			printf( __( ' %s until %s', APP_TD ), $addon_title, $expiration_date );
		}
		return;
	}

	// If the featured listing is disabled, don't bother
	if( _va_addon_disabled( $addon ) ){
		return;
	}

	_va_show_featured_option( $addon, false, $plan_id );
	if( $addon_duration == 0 ){
		$string = __( ' %s for Unlimited days for only %s more.', APP_TD );
		printf( $string, $addon_title, $addon_price );
	}else{
		$string = __( ' %s for %d days for only %s more.', APP_TD );
		printf( $string, $addon_title, $addon_duration, $addon_price );
	}
}

function _va_no_featured_available( $plan ) {
	if( empty($plan[VA_ITEM_FEATURED_HOME] ) && empty($plan[VA_ITEM_FEATURED_CAT] ) &&  empty($plan['disable_featured']) ) {
		if( _va_addon_disabled( VA_ITEM_FEATURED_HOME ) && _va_addon_disabled( VA_ITEM_FEATURED_CAT )) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function _va_no_featured_purchasable( $plan, $listing ) {
	if( _va_no_featured_available( $plan ) ){
		return true;
	} 
	
	foreach ( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon ) {
		if( !_va_already_featured( $addon, $listing->ID ) && !_va_addon_disabled( $addon ) ) {
			return false;
		}		
	}
	return true;
}

function _va_already_featured( $addon, $listing_id ){

	$meta = get_post_meta( $listing_id, $addon, true );
	if( $meta ){
		return true;
	}else{
		return false;
	}

}

function _va_addon_disabled( $addon ){
	global $va_options;
	return empty( $va_options->addons[ $addon ]['enabled'] );
}

function _va_show_featured_option( $addon, $enabled = false, $plan_id = ''){

	$name = $addon;
	if( !empty( $plan_id ) )
		$name = $addon . '_' . $plan_id;

	echo html( 'input', array(
		'name' => $name,
		'type' => 'checkbox',
		'disabled' => $enabled,
		'checked' => $enabled
	) );
}

function va_get_claimed_listing( $listing_id = '' ) {
	$listing_id = !empty( $listing_id ) ? $listing_id : get_queried_object_id();
	$args = array(
		'post_type' => VA_LISTING_PTYPE,
		'post_status' => array( 'publish' ),
		'post__in' => array( $listing_id ),
	);

	$query = new WP_Query( $args );
	return $query;
}

function va_get_renewed_listing( $listing_id = '' ) {
	$listing_id = !empty( $listing_id ) ? $listing_id : get_queried_object_id();
	
	$args = array (
		'post_type' => VA_LISTING_PTYPE,
		'post_status' => array( 'expired' ),
		'post__in' => array( $listing_id ),
	);

	$query = new WP_Query( $args );
	return $query;
}
