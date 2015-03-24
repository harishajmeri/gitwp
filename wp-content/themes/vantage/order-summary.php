<div id="main">

<?php do_action( 'appthemes_notices' ); ?>

<div class="section-head">
	<h1><?php _e( 'Order Summary', APP_TD ); ?></h1>
</div>

<div class="order-summary">
	<?php the_order_summary(); ?>

	<p><?php _e( 'Your order has been completed.', APP_TD ); ?></p>
	
	<?php
		$order = get_order();

		$first_item = $order->get_item(0);

		$post_type_obj = get_post_type_object( $first_item['post']->post_type );

		$url = get_permalink( $first_item['post']->ID );
	?>
	<input type="submit" value="<?php printf( __('Continue to %s', APP_TD ), $post_type_obj->labels->singular_name ); ?>" onClick="location.href='<?php echo $url; ?>';return false;">
</div>

</div>
