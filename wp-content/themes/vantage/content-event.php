<?php global $va_options; ?>

<?php
	echo html( 'a', array(
		'href' => get_permalink( get_the_ID() ),
		'title' => esc_attr( get_the_title() ) . ' - ' . va_get_the_event_days_list(),
		'rel' => 'bookmark',
	), va_get_the_event_cal_thumbnail() );	
?>

<?php appthemes_before_post_title( VA_EVENT_PTYPE ); ?>
<h2><a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a></h2>

<?php appthemes_after_post_title( VA_EVENT_PTYPE ); ?>

<p class="event-cat"><?php the_event_categories(); ?></p>
<?php if ( function_exists('sharethis_button') && $va_options->event_sharethis ): ?>
	<div class="event-sharethis"><?php sharethis_button(); ?></div>
	<div class="clear"></div>
<?php endif; ?>
<div class="content-event event-faves"><?php the_event_faves_link(); ?></div>
<p class="event-span"><?php echo va_get_the_event_days_span('', 'F j, Y', __( ' to ', APP_TD ) ); ?></p>
<p class="event-phone"><?php echo esc_html( get_post_meta( get_the_ID(), 'phone', true ) ); ?></p>
<p class="event-address"><?php the_listing_address(); ?></p>
<p class="event-description"><strong><?php _e( 'Description:', APP_TD ); ?></strong> <?php the_excerpt(); ?> <?php echo html_link( get_permalink(), __( 'Read more...', APP_TD ) ); ?></p>
