<?php

function the_review_count( $listing_id = '' ) {
	$review_count = va_get_reviews_count( $listing_id );

	echo sprintf( _n( '1 review', '%s reviews', $review_count, APP_TD ), number_format_i18n( $review_count ) );
}

function the_listing_address( $listing_id = '' ) {
	$listing_id = !empty( $listing_id ) ? $listing_id : get_the_ID();

	echo esc_html( get_post_meta( $listing_id , 'address', true ) );
}

function the_listing_tags( $before = null, $sep = ', ', $after = '' ) {
	if ( null === $before )
		$before = __( 'Tags: ', APP_TD );
	echo get_the_term_list( 0, VA_LISTING_TAG, $before, $sep, $after );
}

function the_listing_category( $listing_id = 0 ) {
	_deprecated_function( __FUNCTION__, 'Vantage 1.2', 'the_listing_categories()' );
	return the_listing_categories( $listing_id );
}

function the_listing_categories( $listing_id = 0 ) {
	
	$listing_id = $listing_id ? $listing_id : get_the_ID();
	
	$cats = get_the_listing_categories( $listing_id );
	if ( !$cats ) return;

	$_cats = array();

	foreach($cats as $cat) {
		$_cats[] = html_link( get_term_link( $cat ), $cat->name );
	}

	$cats_list = implode( ', ', $_cats);

	printf( __( 'Listed in %s', APP_TD ), $cats_list );

}

function va_listing_render_form( $listing_id, $categories ) {
	$listing_categories = array();

	if ( is_array( $categories ) ) {
		$listing_categories = array_keys( $categories );
	} else {
		$listing_categories[] = $categories;
	}

	va_render_form( $listing_categories, VA_LISTING_CATEGORY, $listing_id );
}

function the_listing_fields( $listing_id = 0 ) {
	$listing_id = $listing_id ? $listing_id : get_the_ID();

	$cats = array_keys( get_the_listing_categories( $listing_id ) );
	if ( !$cats )
		return;

	$fields = array();
	foreach($cats as $cat){
		foreach ( va_get_fields_for_cat( $cat, VA_LISTING_CATEGORY ) as $field ) {
			$fields[$field['name']] = $field;
		}
	}

	foreach( $fields as $field ) {
		if ( 'checkbox' == $field['type'] ) {
			$value = implode( ', ', get_post_meta( $listing_id, $field['name'] ) );
		} else {
			$value = get_post_meta( $listing_id, $field['name'], true );
		}

		if ( !$value )
			continue;

		$field['id_tag'] = va_make_custom_field_id_tag( $field['name'] );

		echo html( 'p', array('class' => 'listing-custom-field', 'id' => $field['id_tag']),
			html('span', array('class' => 'custom-field-label'), $field['desc'] ). html('span', array('class' => 'custom-field-sep'), ': ' ) . html('span', array('class' => 'custom-field-value'), $value ) );
	}
}

function va_make_custom_field_id_tag( $id_tag, $prefix='listing-custom-field-' ) {
	return esc_attr( $prefix . sanitize_title_with_dashes( $id_tag ) );
}

function va_the_post_byline() {
	// Can't use the_date() because it only shows up once per date
	printf( __( '%1$s | %2$s %3$s', APP_TD ),
		get_the_time( get_option( 'date_format' ) ),
		va_get_author_posts_link(),
		get_the_category_list()
	);
}

function get_the_listing_category( $listing_id = 0 ) {
	_deprecated_function( __FUNCTION__, 'Vantage 1.2', 'get_the_listing_categories()' );
	return get_the_listing_categories( $listing_id );
}

function get_the_listing_categories( $listing_id = 0 ) {
	$listing_id = $listing_id ? $listing_id : get_the_ID();

	$terms = get_the_terms( $listing_id, VA_LISTING_CATEGORY );

	if ( !$terms )
		return array();

	return $terms;
}

function the_listing_edit_link( $listing_id = 0, $text = '' ) {
	$listing_id = $listing_id ? $listing_id : get_the_ID();

	if ( !current_user_can( 'edit_post', $listing_id ) )
		return;

	if( empty( $text ) )
		$text = __( 'Edit Listing', APP_TD );

	echo html( 'a', array(
		'class' => 'listing-edit-link',
		'href' => va_get_listing_edit_url( $listing_id ),
	), $text );
}


function the_listing_renew_link( $listing_id = 0, $text = '' ) {
	$listing_id = $listing_id ? $listing_id : get_the_ID();

	if ( !current_user_can( 'edit_post', $listing_id ) )
		return;

	if( empty( $text ) )
		$text = __( 'Renew Listing', APP_TD );

	echo html( 'a', array(
		'class' => 'listing-edit-link listing-renew-link',
		'href' => va_get_listing_renew_url( $listing_id ),
	), $text );
}

function the_listing_claimable_link( $listing_id = '', $text = '' ) {
	$listing_id = !empty( $listing_id ) ? $listing_id : get_the_ID();
	if( !_va_is_claimable( $listing_id ) ) return;

	if( get_post_status( $listing_id ) == 'pending-claimed' ) return;

	if( empty( $text ) )
		$text = __( 'Claim Listing', APP_TD );

	echo html( 'a', array(
		'class' => 'listing-claim-link',
		'href' => va_get_listing_claim_url( $listing_id ),
	), $text );
}


function va_get_listing_edit_url( $listing_id ) {
	global $wp_rewrite, $va_options;

	if ( $wp_rewrite->using_permalinks() ) {
		$listing_permalink = $va_options->listing_permalink;
		$permalink = $va_options->edit_listing_permalink;
		return home_url( user_trailingslashit( "$listing_permalink/$permalink/$listing_id" ) );
	}

	return home_url( "?listing_edit=$listing_id" );
}

function va_get_listing_renew_url( $listing_id ) {
	global $wp_rewrite, $va_options;

	if ( $wp_rewrite->using_permalinks() ) {
		$listing_permalink = $va_options->listing_permalink;
		$permalink = $va_options->renew_listing_permalink;
		return home_url( user_trailingslashit( "$listing_permalink/$permalink/$listing_id" ) );
	}

	return home_url( "?listing_renew=$listing_id" );
}


function the_listing_purchase_link( $listing_id = 0, $text = '' ) {
	global $va_options;

	if( ! $va_options->listing_charge )
		return;

	if( !va_any_featured_addon_enabled() )
		return;

	$listing_id = $listing_id ? $listing_id : get_the_ID();

	if ( !current_user_can( 'edit_post', $listing_id ) )
		return;

	if( empty( $text ) )
		$text = __( 'Upgrade Listing', APP_TD );

	echo html( 'a', array(
		'class' => 'listing-edit-link',
		'href' => va_get_listing_purchase_url( $listing_id ),
	), $text );
}

function va_get_listing_purchase_url( $listing_id ) {
	global $wp_rewrite, $va_options;

	if ( $wp_rewrite->using_permalinks() ) {
		$listing_permalink = $va_options->listing_permalink;
		$permalink = $va_options->purchase_listing_permalink;
		return home_url( user_trailingslashit( "$listing_permalink/$permalink/$listing_id" ) );
	}

	return home_url( "?listing_purchase=$listing_id" );
}


function va_get_listing_claim_url( $listing_id ) {
	global $wp_rewrite, $va_options;

	if ( $wp_rewrite->using_permalinks() ) {
		$listing_permalink = $va_options->listing_permalink;
		$permalink = $va_options->claim_listing_permalink;
		return home_url( user_trailingslashit( "$listing_permalink/$permalink/$listing_id" ) );
	}

	return home_url( "?listing_claim=$listing_id" );
}

function the_listing_faves_link( $listing_id = 0 ) {
	$listing_id = $listing_id ? $listing_id : get_the_ID();
	va_display_fave_button( $listing_id );
}

function va_get_listing_create_url() {
	return get_permalink( VA_Listing_Create::get_id() );
}

function the_listing_star_rating( $post_id = '' ) {
	$rating = str_replace( '.' , '_' , va_get_rating_average( $post_id ) );

	if ( '' == $rating )
		$rating = '0';

?>
		<div class="stars-cont">
			<div class="stars stars-<?php echo $rating;  ?>"></div>
		</div>
		<meta itemprop="ratingValue" content="<?php echo esc_attr( $rating ); ?>" />
		<meta itemprop="reviewCount" content="<?php echo esc_attr( va_get_reviews_count( $post_id ) ); ?>" />
<?php
}

function the_refine_distance_ui() {
	global $va_options, $wp_query;

	$current_radius = (int) get_query_var( 'radius' );

	$geo_query = $wp_query->get( 'app_geo_query' );

	$current_radius = $geo_query['rad'];
	
	extract(va_calc_radius_slider_controls($current_radius));

?>
<label>
	<input name="radius" value="<?php echo esc_attr( $current_radius ); ?>" type="range" min="<?php echo $min; ?>" max="<?php echo $max; ?>" step="<?php echo $step; ?>" />
	<div class="radius-info-box"><span id="radius-info"><?php echo $current_radius; ?></span> <?php 'km' == $va_options->geo_unit ? _e( 'km', APP_TD ) : _e( 'miles', APP_TD ); ?></div>
</label>
<?php
}

function the_refine_category_ui() {
	require_once ABSPATH . '/wp-admin/includes/template.php';

	$options = array(
		'taxonomy' => VA_LISTING_CATEGORY,
		'request_var' => 'listing_cat',
	);
	
	$options = apply_filters( 'va_sidebar_refine_category_ui', $options );
	ob_start();
	wp_terms_checklist( 0, array(
		'taxonomy' => $options['taxonomy'],
		'selected_cats' => isset( $_GET[$options['request_var']] ) ? $_GET[$options['request_var']] : array(),
		'checked_ontop' => false
	) );
	$output = ob_get_clean();

	$output = str_replace( 'tax_input[' . $options['taxonomy'] . ']', $options['request_var'], $output );
	$output = str_replace( 'disabled=\'disabled\'', '', $output );

	echo html( 'ul', $output );
}

function the_search_refinements() {
	appthemes_pass_request_var( array( 'orderby', 'radius', 'listing_cat' ) );
	do_action('va_header_search_refinements');
}

function va_display_logo(){
	$url = get_header_image();

	if ( $url === false ) {
		$header_image = '';
	} elseif( $url != '' ) {
		$header_image = $url;
	} else {
		$header_image = get_template_directory_uri().'/images/vantage-logo.png';
	}
?>
	<h1 id="site-title">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="custom-header-image" style="height:<?php echo get_custom_header()->height; ?>px;width:<?php echo get_custom_header()->width; ?>px;background: transparent url('<?php echo $header_image; ?>') no-repeat 0 0;"><?php bloginfo( 'title' ); ?></a>
	</h1>
	<?php if( display_header_text() ) { ?>
	<h2 id="site-description" style="color:#<?php header_textcolor(); ?>;"><?php bloginfo( 'description' ); ?></h2>
	<?php } ?>
<?php
}

function va_display_navigation_menu() {

	wp_nav_menu( array(
		'menu_id'         => 'navigation',
		'theme_location' => 'header',
		'container_class' => 'menu rounded',
		'items_wrap' => '<ul id="%1$s">%3$s</ul>',
		'fallback_cb' => false
	) );
?>
	<script type="text/javascript">
		jQuery('#navigation').tinyNav({
			active: 'current-menu-item',
			header: '<?php _e( 'Navigation' , APP_TD ); ?>',
			header_href: '<?php echo esc_js( home_url( '/' ) ); ?>',
			indent: '-',
			excluded: ['#adv_categories']
		});
	</script>
<?php
}

/**
* Taken from http://codex.wordpress.org/Template_Tags/the_author_posts_link.
* Modified to return the link instead of display it
*/
function va_get_author_posts_link() {

        global $authordata;
        if ( !is_object( $authordata ) )
                return false;
        $link = sprintf(
                '<a href="%1$s" title="%2$s" rel="author">%3$s</a>',
                get_author_posts_url( $authordata->ID, $authordata->user_nicename ),
                esc_attr( sprintf( __( 'Posts by %s', APP_TD ), get_the_author() ) ),
                get_the_author()
        );
        return apply_filters( 'the_author_posts_link', $link );
}

function va_js_redirect( $url ) {
	echo html( 'a', array( 'href' => $url ), __( 'Continue', APP_TD ) );
	echo html( 'script', 'location.href="' . $url . '"' );
}

function va_js_redirect_to_listing( $listing_id, $query_args = array() ) {
	if ( !is_admin() ) {
		$url = add_query_arg( $query_args, get_permalink( $listing_id ) );
		va_js_redirect( $url );
	}
}

function va_js_redirect_to_claimed_listing( $listing_id ) {
	if ( !is_admin() ) {
		$url = va_get_claimed_listings_url() . '#post-'. $listing_id;
		va_js_redirect( $url );
	}
}

function va_post_coords( $post_id = 0 ) {
	$post_id = $post_id ? $post_id : get_the_ID();

	$coord = va_geocode_address( $post_id, false );

	return $coord;
}

function va_post_coords_attr( $post_id = 0 ) {
	$post_id = $post_id ? $post_id : get_the_ID();

	$coord = va_post_coords( $post_id );

	$attr = '';

	if ( $coord ) {
		$attr = ' data-lat="' .$coord->lat.'" data-lng="' . $coord->lng . '" ';
	}

	return $attr;
}

function va_listings_base_url() {
	global $va_options;

	$url = '';
	$base = trailingslashit( get_bloginfo( 'url' ) );

	if ( is_tax( VA_LISTING_CATEGORY ) || is_tax( VA_LISTING_TAG ) ) {
		$url = get_term_link( get_queried_object() );
	}

	if( is_post_type_archive( VA_LISTING_PTYPE ) || va_is_home() ) {
		$url = $va_options->listing_permalink;
		$url = trailingslashit( $base . $url );
	}

	return $url;
}

function va_list_sort_dropdown( $post_type = '', $base_link = '', $default_current_sort = 'default' ) {
	global $wp_query;

	$options = array();

	if( $wp_query->post_count == 0 )
		return false;

	if ( empty( $post_type ) ) {
		$post_type = $wp_query->get('post_type');
		$post_type = !empty( $post_type ) ? $post_type : VA_LISTING_PTYPE;
	}

	$options['default'] = __( 'Default', APP_TD );
	
	if ( get_query_var( 'app_geo_query' ) )
		$options['distance'] = __( 'Closest', APP_TD );
	
	if ( $post_type == VA_LISTING_PTYPE )
		$options['highest_rating'] = __( 'Highest Rating', APP_TD );
	if ( $post_type == VA_LISTING_PTYPE )
		$options['most_ratings'] = __( 'Most Ratings', APP_TD );

	if ( va_events_enabled() ) {
		if ( $post_type == VA_EVENT_PTYPE )
			$options['event_date'] = __( 'Event Date', APP_TD );
		if ( $post_type == VA_EVENT_PTYPE )
			$options['popular'] = __( 'Popular', APP_TD );
		if ( $post_type == VA_EVENT_PTYPE )
			$options['most_comments'] = __( 'Most Comments', APP_TD );
	}

	$options['title'] = __( 'Alphabetical', APP_TD );
	$options['newest'] = __( 'Newest', APP_TD );

	if ( va_events_enabled() ) {
		if ( $post_type == VA_EVENT_PTYPE )
			$options['recently_discussed'] = __( 'Recently Discussed', APP_TD );
	}

	if ( $post_type == VA_LISTING_PTYPE )
		$options['recently_reviewed'] = __( 'Recently Reviewed', APP_TD );

	$options['rand'] = __( 'Random', APP_TD );

	$options = apply_filters('va_list_sort_ui', $options );

	$current_sort = get_va_query_var( 'orderby', false );
	
	// Settings backwards compatability
	if ( $current_sort == 'rating' )
		$current_sort = 'highest_rating';

	$current_sort = !empty( $current_sort ) ? $current_sort : $default_current_sort;

	$li = '';
	foreach ( $options as $value => $title ) {
		$args = array( 'data-value' => $value );

		if( $value == $current_sort ) {
			$args['class'] = 'active';
		}

		if ( !empty( $base_link ) ) {
			$href = add_query_arg( 'orderby', $value, $base_link );
		} else {
			$href = add_query_arg( 'orderby', $value );
		}

		$link = html( 'a', array( 'href' => $href  ), $title );

		$li .= html('li', $args, $link );
	}

	$top_div_text = html( 'p', array(), $options[$current_sort] );

	$top_div_control = html( 'div', array('class'=>'control') );
	$top_div = html( 'div', array( 'class' => 'va_sort_list_selected selected' ), $top_div_text . $top_div_control );

	$ul = html( 'ul', array('class'=> 'va_sort_list', 'id' =>'va_sort_list_' . $post_type ), $li );
	$list = html( 'div', array( 'class' => 'va_sort_list_wrap' ), $ul );

	ob_start();
	?>
	<script type="text/javascript">
		jQuery('#va_sort_list_<?php echo $post_type; ?>').tinyNav({
			active: 'active',
			header: '<?php _e( 'Sort Method' , APP_TD ); ?>',
			header_href: '<?php echo add_query_arg( 'orderby', 'default' ); ?>',
			indent: '-',
			append: '#va_sort_list_container_<?php echo $post_type; ?>'
		});
	</script>
	<?php
	$js = ob_get_clean();

	return html( 'div', array( 'class' => 'va_sort_list_container', 'id' =>'va_sort_list_container_' . $post_type ), $top_div . $list . $js );;
}

function get_the_contact_listing_owner_button( $listing_id = 0 ) {
	return va_contact_post_author_button( $listing_id );
}

function the_contact_listing_owner_button( $listing_id = 0 ) {
	echo get_the_contact_listing_owner_button( $listing_id );
}
