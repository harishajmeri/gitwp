<div id="main">
	<div class="section-head">
		  <h1><?php _e( 'Pricing Options', APP_TD ); ?></h1>
	</div>
	<form id="create-listing" method="POST" action="<?php echo appthemes_get_step_url(); ?>">
		<fieldset>
			<div class="pricing-options">
				<?php if( !empty( $plans ) ) { ?>
					<?php foreach( $plans as $key => $plan ){ ?>
						<div class="plan">
							<div class="content">
								<div class="title">
									<?php echo $plan['title']; ?>
								</div>
								<div class="description">
									<?php echo $plan['description']; ?>
								</div>
								<div class="categories">
									<?php 
									if ( !isset( $plan['included_categories'] ) ) {
										printf( _n( 'Choose up to %d category', 'Choose up to %d categories', $va_options->included_categories, APP_TD ), $va_options->included_categories );
									} else if( isset( $plan['included_categories'] ) && 0 == $plan['included_categories'] ) {
										_e( 'Choose unlimited categories!', APP_TD );
									} else {
										printf( _n( 'Choose up to %d category', 'Choose up to %d categories', $plan['included_categories'], APP_TD ), $plan['included_categories'] );
									}
									?>
								</div>
								<div class="featured-options">
								<?php if( _va_no_featured_available( $plan ) ) { ?>
									<div class="option-header">
										<?php _e( 'Featured Listings are not available for this price plan.', APP_TD ); ?>
									</div>
								<?php } else { ?>
									<div class="option-header">
										<?php _e( 'Please choose additional featured options:', APP_TD ); ?>
									</div>
									<?php foreach ( array( VA_ITEM_FEATURED_HOME, VA_ITEM_FEATURED_CAT ) as $addon ) : ?>
										<div class="featured-option"><label>
										<?php if( _va_already_featured( $addon, $listing->ID ) ): ?>
											<?php _va_show_purchased_featured_addon( $addon, $plan['post_data']->ID, $listing->ID ); ?>
										<?php else: ?>
											<?php _va_show_purchasable_featured_addon( $addon, $plan['post_data']->ID ); ?>
										<?php endif; ?>
										</label></div>
								<?php endforeach; ?>
								<?php } ?>
								</div>
							</div>
							<div class="price-box">
								<div class="price">
									<?php appthemes_display_price( $plan['price'] ); ?>
								</div>
								<div class="duration">
									<?php if( $plan['duration'] != 0 ){ ?>
										<?php printf( _n( 'for <br /> %s day', 'for <br /> %s days', $plan['duration'], APP_TD ), $plan['duration'] ); ?>
									<?php }else{ ?>
										<?php _e( 'Unlimited</br> days', APP_TD ); ?>
									<?php } ?>
								</div>
								<div class="radio-button">
									<label>
										<input name="plan" type="radio" <?php echo ($key == 0) ? 'checked="checked"' : ''; ?> value="<?php echo $plan['post_data']->ID; ?>" />
										<?php _e( 'Choose this option', APP_TD ); ?>
									</label>
								</div>
							</div>
						</div>
					<?php } ?>
				<?php } else { ?>
					<em><?php _e( 'No Plans are currently available for this category. Please come back later.', APP_TD ); ?></em>
				<?php } ?>
			</div>
		</fieldset>
		<?php if( !_va_no_featured_purchasable( $plan, $listing ) ): ?>
		<fieldset>
			<input type="hidden" name="action" value="purchase-listing">
			<input type="hidden" name="ID" value="<?php echo $listing->ID; ?>">
			<div classess="form-field"><input type="submit" value="<?php _e( 'Continue', APP_TD ) ?>" /></div>
		</fieldset>
		<?php endif; ?>
	</form>
</div>
