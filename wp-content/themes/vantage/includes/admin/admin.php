<?php

// Installation procedures
add_action( 'appthemes_first_run', 'va_init_settings' );
add_action( 'appthemes_first_run', 'va_init_content' );
add_action( 'appthemes_first_run', 'va_init_menu' );
add_action( 'appthemes_first_run', 'va_init_widgets' );

// Upgrade procedures
add_action( 'appthemes_first_run', 'va_upgrade_pages', 6 );
add_action( 'appthemes_first_run', 'va_setup_postmeta' );
add_action( 'appthemes_first_run', 'va_setup_featured_flag' );

add_action( 'load-post-new.php', 'va_disable_admin_listing_creation' );
add_action( 'load-post.php', 'va_disable_admin_listing_editing' );

// Importer
add_action( 'wp_loaded', 'va_csv_importer' );

// Various tweaks
add_action( 'admin_menu', 'va_admin_menu_tweak' );
add_action( 'admin_print_styles', 'va_admin_styles' );

// Admin Scripts
add_action( 'admin_enqueue_scripts', 'va_add_admin_scripts', 10 );

class VA_Importer extends APP_Importer {

	function setup() {
		$this->textdomain = APP_TD;

		$post_type_obj = get_post_type_object( $this->post_type );

		$this->args = array(
			'page_title' => __( 'CSV ' . $post_type_obj->labels->name . ' Importer', APP_TD ),
			'menu_title' => __( $post_type_obj->labels->name . ' Importer', APP_TD ),
			'page_slug' => 'app-importer-' . $post_type_obj->name,
			'parent' => 'app-dashboard',
			'screen_icon' => 'tools',
		);
	}

}

function va_csv_importer() {
	$fields = array(
		'title'       => 'post_title',
		'description' => 'post_content',
		'author'      => 'post_author',
		'date'        => 'post_date',
		'slug'        => 'post_name',
		'status'      => 'post_status'
	);

	$args = array(
		'taxonomies'    => array( VA_LISTING_CATEGORY, VA_LISTING_TAG ),

		'custom_fields' => array(
			'address' => array(),
			'phone' => array(),
			'facebook' => array(),
			'twitter' => array(),
			'website' => array(),
			'listing_duration' => array('internal_key' => 'listing_duration', 'default' => 0),
		),

		'geodata' => true,
		'attachments' => true
	);

	$args = apply_filters( 'va_csv_importer_args', $args );

	$importer = new VA_Importer( VA_LISTING_PTYPE, $fields, $args );
}

function va_init_settings() {
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', VA_Home_Archive::get_id() );
	update_option( 'page_for_posts', VA_Blog_Archive::get_id() );

	if ( !get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
	}

	flush_rewrite_rules();
}

function va_upgrade_pages() {
	global $va_options;

	list( $args ) = get_theme_support( 'app-versions' );
	$previous_version = get_option( $args['option_key'] );

	if ( version_compare( $previous_version, '1.2', '<' ) && !$va_options->page_template_updates_1_2 ) {

		$template_upgrades = array (
			'VA_Listing_Categories' => array (
				'old' => 'categories-list.php',
				'new' => 'categories-list-listing.php'
			),
		);

		foreach ( $template_upgrades as $class => $template ) {
			$page_q = new WP_Query( array(
				'post_type' => 'page',
				'meta_key' => '_wp_page_template',
				'meta_value' => $template['old'],
				'posts_per_page' => 1,
				'suppress_filters' => true
			) );

			if ( !empty( $page_q->posts ) ) {
				$page_id = $page_q->posts[0]->ID;
				update_post_meta( $page_id, '_wp_page_template', $template['new'] );
			}

		}

		$va_options->page_template_updates_1_2 = true;

	}
}

function va_setup_postmeta() {

	list( $args ) = get_theme_support( 'app-versions' );
	$previous_version = get_option( $args['option_key'] );

	if ( version_compare( $previous_version, '1.2', '<' ) ) {
		foreach ( _va_get_listing_meta_defaults() as $default_key => $default_val ) {
			$args = array(
				'post_type' => VA_LISTING_PTYPE,
				'nopaging' => true,
				'fields' => 'ids',
				'cache_results' => false,
				'meta_query' => array (
					array (
						'key' => $default_key,
						'compare' => 'NOT EXISTS'
					)
				),
			);

			$query = new WP_Query( $args );

			if ( $query->post_count > 0 ) {
				foreach( $query->posts as $k => $post_id ) {
					update_post_meta( $post_id, $default_key, $default_val );
				}
			}

		}
	}
}

function va_setup_featured_flag() {

	list( $args ) = get_theme_support( 'app-versions' );
	$previous_version = get_option( $args['option_key'] );

	if ( version_compare( $previous_version, '1.2.1', '<' ) ) {
		$args = array(
			'post_type' => array( VA_LISTING_PTYPE, VA_EVENT_PTYPE ),
			'nopaging' => true,
			'fields' => 'ids',
			'cache_results' => false,
			'meta_query' => array (
				array (
					'key' => VA_ITEM_FEATURED,
					'compare' => 'NOT EXISTS'
				)
			),
		);

		$query = new WP_Query( $args );

		if ( $query->post_count > 0 ) {
			foreach( $query->posts as $k => $post_id ) {
				va_featured_flag( $post_id );
			}
		}
	}
}

function va_init_content() {
	// Deliberately left untranslated

	$listings = get_posts( array(
		'post_type' => VA_LISTING_PTYPE,
		'posts_per_page' => 1
	) );

	if ( empty( $listings ) ) {

		$cat = appthemes_maybe_insert_term( 'Software', VA_LISTING_CATEGORY );
	
		$listing_id = wp_insert_post( array(
			'post_type' => VA_LISTING_PTYPE,
			'post_status' => 'publish',
			'post_author' => get_current_user_id(),
			'post_title' => 'AppThemes',
			'post_content' => 'AppThemes is a fast growing company that employs talent from all around the world. Our diverse team consists of highly skilled WordPress developers, designers, and enthusiasts who come together to make awesome premium themes available in over two dozen different languages.',
			'tax_input' => array(
				VA_LISTING_CATEGORY => array( $cat['term_id'] ),
				VA_LISTING_TAG => 'wordpress, themes'
			)
		) );
	
		$data = array(
			'phone' => '415-287-3474',
			'address' => '548 Market St, San Francisco, CA 94104, USA',
			'website' => 'appthemes.com',
			'twitter' => 'appthemes',
			'facebook' => 'appthemes',
			'rating_avg' => '5',
		);
	
		foreach ( $data as $key => $value )
			update_post_meta( $listing_id, $key, $value );
	
		appthemes_set_coordinates( $listing_id, '37.7899027', '-122.40078460000001' );
	
		$user_id = username_exists( 'customer' );
		if ( !$user_id ) {
			$user_id = wp_insert_user( array(
				'user_login' => 'customer',
				'display_name' => 'Satisfied Customer',
				'user_pass' => wp_generate_password()
			) );
		}
	
		$review_id = wp_insert_comment( array(
			'comment_type' => VA_REVIEWS_CTYPE,
			'comment_post_ID' => $listing_id,
			'user_id' => $user_id,
			'comment_content' => "Wow! Really powerful stuff from AppThemes. Their themes simply blow away the competition. It seems like everyone is trying to make money online and AppThemes makes it easy to do just that. After downloading and installing their themes, it's just a few button clicks before you have an amazing website - no not a website, a web application. That's what you're getting with AppThemes, really powerful web applications. All you have to take care of is getting traffic to your site. The themes from AppThemes do the rest."
		) );
	
		va_set_rating( $review_id, 5 );
	}
	
	$plans = get_posts( array(
		'post_type' => APPTHEMES_PRICE_PLAN_PTYPE,
		'posts_per_page' => 1
	) );

	if ( empty( $plans ) ) {
		
		$plan_id = wp_insert_post( array(
			'post_type' => APPTHEMES_PRICE_PLAN_PTYPE,
			'post_status' => 'publish',
			'post_author' => get_current_user_id(),
			'post_title' => 'Basic',
			'post_content' => '',
			'tax_input' => array(
				VA_LISTING_CATEGORY => array( $cat['term_id'] ),
			)
		) );
	
		$data = array(
			'title' => 'Basic',
			'description' => 'Get your listing out there with our Basic plan. No frills, no fuss.',
			'duration' => 30,
			'price' => 0,
			'included_categories' => 0,
		);
	
		foreach ( $data as $key => $value )
			add_post_meta( $plan_id, $key, $value );
	
	}
}

function va_init_menu() {
	if ( is_nav_menu( 'header' ) ) {
		$nav_menu_locations = get_theme_mod( 'nav_menu_locations' );
		if ( empty( $nav_menu_locations ) ){
			$menu_obj = wp_get_nav_menu_object( 'header' );
			$locations['header'] = $menu_obj->term_id;
			set_theme_mod( 'nav_menu_locations', $locations );
		}
		return;
	}

	$menu_id = wp_create_nav_menu( __( 'Header', APP_TD ) );
	if ( is_wp_error( $menu_id ) ) {
		return;
	}

	$page_ids = array(
		VA_Listing_Categories::get_id(),
		VA_Listing_Create::get_id(),
		VA_Blog_Archive::get_id(),
	);

	$page_ids = apply_filters( 'va_init_menu_page_ids', $page_ids );

	foreach ( $page_ids as $page_id ) {
		$page = get_post( $page_id );

		if ( !$page )
			continue;

		$items = wp_get_associated_nav_menu_items( $page_id );
		if ( !empty( $items ) )
			continue;

		wp_update_nav_menu_item( $menu_id, 0, array(
			'menu-item-type' => 'post_type',
			'menu-item-object' => 'page',
			'menu-item-object-id' => $page_id,
			'menu-item-title' => $page->post_title,
			'menu-item-url' => get_permalink( $page ),
			'menu-item-status' => 'publish'
		) );
	}

	$locations = get_theme_mod( 'nav_menu_locations' );
	$locations['header'] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}

function va_init_widgets() {
	list( $args ) = get_theme_support( 'app-versions' );

	if ( !get_option( $args['option_key'] ) && $args['current_version'] == get_transient( APP_UPDATE_TRANSIENT ) ) {
	
		$sidebars_widgets = array (
			'single-listing' => array (
				'listing_event_map' => array (
					'title' => __( 'Map', APP_TD ),
					'directions' => 1,
				),
				'listing_categories' => array (
					'title' => __( 'Related Categories', APP_TD ),
					'count' => 1,
				),
				'sidebar_ad' => array (
					'title' => __( 'Sponsored Ad', APP_TD ),
					'text' => '<a href="http://www.appthemes.com" target="_blank"><img src="' . get_template_directory_uri() . '/images/cp-250x250a.gif" border="0" alt="ClassiPress - Premium Classified Ads Theme"></a>',
				),
				'recent_listings' => array (
					'title' => __( 'Recently Added Businesses', APP_TD ),
					'number' => 5,
				),
			),
			'main' => array (
				'create_listing_button' => array(),
				'recent_reviews' => array (
					'title' => __( 'Recent Reviews', APP_TD ),
					'number' => 5,
				),
				'sidebar_ad' => array (
					'title' => __( 'Advertisement', APP_TD ),
					'text' => '<a href="http://www.appthemes.com" target="_blank"><img src="' . get_template_directory_uri() . '/images/cp-250x250a.gif" border="0" alt="ClassiPress - Premium Classified Ads Theme"></a>',
				),
				'popular_listing_categories' => array (
					'title' => __( 'Popular Categories', APP_TD ),
					'amount' => '10',
					'count' => 1,
				),
			),
			'page' => array (
				'create_listing_button' => array(),
				'popular_listing_categories' => array (
					'title' => __( 'Popular Categories', APP_TD ),
					'amount' => '10',
					'count' => 1,
				),
				'recent_listings' => array (
					'title' => __( 'Recent Listings', APP_TD ),
					'number' => 5,
				),
			),
			'va-header' => array (
				'text' => array (
					'text' => '<a href="http://www.appthemes.com" target="_blank"><img src="' . get_template_directory_uri() . '/images/at-468x60c.gif" border="0" alt="Premium WordPress Apps"></a>',
				),
			),
			'va-list-page-top' => array (
				'listings_events_map' => array(),
			),
			'va-footer' => array (
				'text' => array (
					'title' => __( 'Text Widget', APP_TD ),
					'text' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.',
				),
				'sidebar_ad' => array (
					'title' => __( 'Advertisement', APP_TD ),
					'text' => '<a href="http://www.appthemes.com" target="_blank"><img src="' . get_template_directory_uri() . '/images/cp-250x250a.gif" border="0" alt="ClassiPress - Premium Classified Ads Theme"></a>'
				),
				'recent_listings' => array (
					'title' => __( 'Recent Listings', APP_TD ),
					'number' => 10,
				),
				'connect' => array (
					'title' => __( 'Connect!', APP_TD ),
					'twitter' => 'twitter.com/appthemes',
					'twitter_inc' => 1,
					'facebook' => 'www.facebook.com/appthemes',
					'facebook_inc' => 1,
					'linkedin' => 'www.linkedin.com/appthemes',
					'linkedin_inc' => 1,
					'youtube' => 'www.youtube.com/appthemes',
					'youtube_inc' => 1,
					'google' => 'www.google.com/appthemes',
					'google_inc' => 1,
					'rss' => 'http://www.appthemes.com/blog/feed',
					'rss_inc' => 1,
				),
			),
			'va-listings-ad' => array(
				'text' => array (
					'text' => '<a href="http://www.appthemes.com" target="_blank"><img src="' . get_template_directory_uri() . '/images/at-468x60c.gif" border="0" alt="Premium WordPress Apps"></a>',
				),
			),
		);

		foreach ( $sidebars_widgets as $sidebar => $widgets ) {
			$current_sidebars_widgets = get_option('sidebars_widgets');
			if ( array_key_exists( $sidebar, $current_sidebars_widgets ) ) {
				$current_sidebars_widgets[ $sidebar ] = array();
				update_option( 'sidebars_widgets', $current_sidebars_widgets );
			}

			foreach ( $widgets as $widget => $settings ) {
				va_install_widget( $widget, $sidebar, $settings );
			}
		}

	}

}

function va_admin_menu_tweak() {
	global $menu;

	// move Media down
	$menu[13] = $menu[10];
	// clear the old Media slot
	unset($menu[10]);
	// move Posts below Events.
	$menu[12] = $menu[5];
	// move separator down
	$menu[5] = $menu[4];
	unset($menu[4]);
	// Copy seperator to group off Listings and Events
	$menu[10] = $menu[5];

}

add_action( 'widgets_admin_page', 'va_widgets_admin_page_sort_sidebars' );
global $_va_sidebar_sort_order;
function _va_sidebar_sort( $a, $b ) {
	global $_va_sidebar_sort_order;
	return $_va_sidebar_sort_order[$a] > $_va_sidebar_sort_order[$b];
}

function va_widgets_admin_page_sort_sidebars(){
	global $wp_registered_sidebars, $_va_sidebar_sort_order;

	 $sort_order = array(
		'single-listing'		=> 1,
		'single-event'			=> 5,

		'page'					=> 15,
		'main'					=> 20,
		'search-listing'		=> 25,

		'va-header'				=> 30,
		'va-list-page-top'		=> 35,

		'va-footer'				=> 40,
		'va-listings-ad'		=> 45,

		'wp_inactive_widgets' 	=> 99,
	);

	$_va_sidebar_sort_order = apply_filters( 'va_sidebar_sort_order', $sort_order );

	uksort( $wp_registered_sidebars, '_va_sidebar_sort' );
}

function va_admin_styles() {
	appthemes_menu_sprite_css( array(
		'#toplevel_page_app-dashboard',
		'#adminmenu #menu-posts-listing',
		'#adminmenu #menu-posts-event',
	) );
	?>
	<style>
		.inline-edit-listing .inline-edit-group .alignleft {
			display: none;
		}
		.inline-edit-listing .inline-edit-group .alignleft.inline-edit-status,
		.inline-edit-listing .inline-edit-group .alignleft.inline-edit-claimable{
			display: block;
		}
		
		.wp-list-table th.column-claimable,
		.wp-list-table td.column-claimable {
			display: none;
		}
	</style>
	<?php
}

function va_add_admin_scripts( $hook ) {
	global $post;

	if ( empty( $post ) || VA_LISTING_PTYPE != $post->post_type ) return;

	// selective load
	$pages = array ( 'edit.php', 'post.php', 'post-new.php', 'media-upload-popup' );

 	if( ! in_array( $hook, $pages ) )
		return;

	wp_register_script(
		'jquery-validate',
		get_template_directory_uri() . '/scripts/jquery.validate.min.js',
		array( 'jquery' ),
		'1.9.0',
		true
	);

	wp_enqueue_script(
		'va-admin-listing-edit',
		get_template_directory_uri() . '/includes/admin/scripts/listing-edit.js',
		array( 'jquery-validate'),
		VA_VERSION,
		true
	);

	wp_localize_script( 'va-admin-listing-edit', 'VA_admin_l18n', array(
		'user_admin' 		=> current_user_can('manage_options'),
		'listing_type'  	=> VA_LISTING_PTYPE,
		'listing_category'  => VA_LISTING_CATEGORY,
		'post_type'  		=> ( isset( $post->post_type ) ? $post->post_type : '' ),
	) );

}

function va_disable_admin_listing_creation() {
	if( current_user_can( 'edit_others_listings') )
		return;

	if ( VA_LISTING_PTYPE != @$_GET['post_type'] )
		return;

	wp_redirect( va_get_listing_create_url() );
	exit;
}

function va_disable_admin_listing_editing() {

	if( current_user_can( 'edit_others_listings') )
		return;

	if ( 'edit' != @$_GET['action'] )
		return;

	$post_id = (int) @$_GET['post'];

	if ( VA_LISTING_PTYPE != get_post_type( $post_id ) )
		return;

	wp_redirect( va_get_listing_edit_url( $post_id ) );
	exit;
}
