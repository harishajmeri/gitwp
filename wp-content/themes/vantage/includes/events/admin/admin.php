<?php

// Events Importer
add_action( 'wp_loaded', 'va_csv_events_importer' );
add_action( 'appthemes_after_import_upload_form', 'va_geocode_events_on_import_option' );
add_action( 'app_importer_import_row_after', 'va_geocode_events_on_import', 10, 2 );
add_filter( 'app_importer_import_row_after' , 'va_set_imported_event_times', 10, 2 );
add_filter( 'app_importer_import_row_after' , 'va_set_import_event_meta_defaults', 11 );

// Various tweaks
add_action( 'admin_menu', 'va_events_admin_menu_tweak', 15 );

// Admin Scripts
add_action( 'admin_enqueue_scripts', 'va_event_add_admin_scripts', 10 );
add_action( 'admin_print_styles', 'va_events_icon' );

// Events First Run
add_action( 'va_events_first_run', 'va_init_events_menu_items' );
add_action( 'va_events_first_run', 'va_init_events_first_event' );
add_action( 'va_events_first_run', 'va_init_events_widgets' );

function va_init_events_menu_items() {
	$menu = wp_get_nav_menu_object( 'header' );

	if ( ( ! $menu && 0 !== $menu_id ) || is_wp_error( $menu ) )
		return;

	$page_ids = array(
		VA_Event_Categories::get_id(),
		VA_Event_Create::get_id(),
	);

	$page_ids = apply_filters( 'va_init_event_menu_page_ids', $page_ids );

	foreach ( $page_ids as $page_id ) {
		$page = get_post( $page_id );

		if ( !$page )
			continue;

		$items = wp_get_associated_nav_menu_items( $page_id );
		if ( !empty( $items ) )
			continue;

		wp_update_nav_menu_item( $menu->term_id, 0, array(
			'menu-item-type' => 'post_type',
			'menu-item-object' => 'page',
			'menu-item-object-id' => $page_id,
			'menu-item-title' => $page->post_title,
			'menu-item-url' => get_permalink( $page ),
			'menu-item-status' => 'publish'
		) );
	}
}

function va_init_events_first_event() {
	$events = get_posts( array(
		'post_type' => VA_EVENT_PTYPE,
		'posts_per_page' => 1
	) );

	if ( empty( $events ) ) {
		$cat = appthemes_maybe_insert_term( 'WordPress', VA_EVENT_CATEGORY );
	
		$event_id = wp_insert_post( array(
			'post_type' => VA_EVENT_PTYPE,
			'post_status' => 'publish',
			'post_author' => get_current_user_id(),
			'post_title' => 'WordCamp Moonbase 1',
			'post_content' => 'WordCamp is a conference that focuses on everything wordpress. Come join us on Moonbase 1 for a WordCamp that\'s out of this world!',
			'tax_input' => array(
				VA_EVENT_CATEGORY => array( $cat['term_id'] ),
				VA_EVENT_TAG => 'wordpress, wordcamp'
			)
		) );

		$days = $day_times = array();
		$sample_times = array( 'Sunrise-Sundown', '8:00 am-8:00 pm', '3:00-13:00' );
		for($x = 0 ; $x <= 2 ; $x++) {
			$date = date('Y-m-d', strtotime('+' . ( $x + 10 ) . ' days') );
			$days[] = $date;
			$day_times[ $date ] = $sample_times[$x];
			va_insert_event_day( $date );
		}

		asort( $days );
		wp_set_object_terms( $event_id, $days, VA_EVENT_DAY );

		$data = array (
			VA_EVENT_LOCATION_META_KEY => '11 Armstrong Lane, Sea of Tranquility',
			VA_EVENT_LOCATION_URL_META_KEY => 'en.wikipedia.org/wiki/Mare_Tranquilitatis',
			VA_EVENT_COST_META_KEY => 'Free',

			VA_EVENT_DATE_META_KEY => reset( $days ),
			VA_EVENT_DATE_END_META_KEY => end( $days ),
			VA_EVENT_DAY_TIMES_META_KEY => $day_times,

			'address' => 'SR 405, Kennedy Space Center, FL 32899, USA',

			'featured-home' => 1,
			'featured-cat' => 0,
		);

		foreach ( $data as $key => $value )
			update_post_meta( $event_id, $key, $value );

		appthemes_set_coordinates( $event_id, '28.522399', '-80.651235' );
	}
}

function va_init_events_widgets() {
	$sidebars_widgets = get_option('sidebars_widgets');

	if ( !array_key_exists( 'single-event', $sidebars_widgets ) ) {
		$sidebars_widgets[ 'single-event' ] = array();
		update_option( 'sidebars_widgets', $sidebars_widgets );
	}

	if ( !empty( $sidebars_widgets['single-event'] ) )
		return;

	$sidebars_widgets = array (
		'single-event' => array (
			'event_attendees' => array (
				'title' => __( 'Event Attendees', APP_TD ),
			),
			'listing_event_map' => array (
				'title' => __( 'Map', APP_TD ),
				'directions' => 1,
			),
			'sidebar_ad' => array (
				'title' => __( 'Sponsored Ad', APP_TD ),
				'text' => '<a href="http://www.appthemes.com" target="_blank"><img src="' . get_template_directory_uri() . '/images/cp-250x250a.gif" border="0" alt="ClassiPress - Premium Classified Ads Theme"></a>',
			),
			'recent_events' => array (
				'title' => __( 'Recently Added Events', APP_TD ),
				'number' => 5,
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

	va_install_widget( 'create_event_button', 'main', array(), 1, 'prepend' );

}

function va_events_admin_menu_tweak() {
	global $menu;

	// move Events into Posts old spot
	$menu[7] = $menu[8];
	// clear the slot
	unset($menu[8]);

}

function va_csv_events_importer() {
	$fields = array(
		'title'       => 'post_title',
		'description' => 'post_content',
		'author'      => 'post_author',
		'date'        => 'post_date',
		'slug'        => 'post_name',
		'status'      => 'post_status'
	);

	$args = array(
		'taxonomies'    => array( VA_EVENT_CATEGORY, VA_EVENT_TAG ), // FIX THIS!!! THERE will need to be specific meta fields generated for this.

		'custom_fields' => array(
			'address' => array(),
			VA_EVENT_LOCATION_META_KEY => array(),
			VA_EVENT_LOCATION_URL_META_KEY => array(),
			VA_EVENT_COST_META_KEY => array(),
			'phone' => array(),
			'facebook' => array(),
			'twitter' => array(),
			'website' => array(),
		),

		'geodata' => true,
		'attachments' => true
	);

	$args = apply_filters( 'va_csv_importer_args', $args );

	$importer = new VA_Importer( VA_EVENT_PTYPE, $fields, $args );
}


function va_geocode_events_on_import_option() {
	if ( empty($_GET['page']) || $_GET['page'] !== 'app-importer-' . VA_EVENT_PTYPE  ) return;
	?>
	<p><label><?php _e( 'Geocode imported events?:' , APP_TD ); ?> <input type="checkbox" name="geocode_imported" value="1" /></label>
	<br />
	<span class="description"><?php _e( '(Note: Maximum of 2500 geocode requests per day are allowed)' , APP_TD ); ?></span></p>
	<?php
}

function va_geocode_events_on_import( $event_id, $row ) {

	if ( VA_EVENT_PTYPE != get_post_type( $event_id ) ) return;

	if ( empty( $_POST['geocode_imported'] ) ) return;
	if ( !empty( $row['lat'] ) && !empty( $row['lng'] ) ) return;
	va_geocode_address( $event_id );
}

function va_set_imported_event_times( $event_id, $row ) {

	if ( VA_EVENT_PTYPE != get_post_type( $event_id ) ) return;

	if ( empty( $row['event_date_time'] ) ) return;

	$dates_n_times = array_map( 'trim', explode( ',', $row['event_date_time'] ) );
	$days = array();
	$day_times = array();
	foreach( $dates_n_times as $_time_string ) {
		$_time_string = array_map( 'trim', explode( '=', $_time_string ) );
		$day_times[$_time_string[0]] = $_time_string[1];
		$days[] = $_time_string[0];
		va_insert_event_day( $_time_string[0] );
	}

	update_post_meta( $event_id, VA_EVENT_DAY_TIMES_META_KEY, $day_times );

	wp_set_object_terms( $event_id, $days, VA_EVENT_DAY );

	asort( $days );
	update_post_meta( $event_id, VA_EVENT_DATE_META_KEY, reset( $days ) );
	update_post_meta( $event_id, VA_EVENT_DATE_END_META_KEY, end( $days ) );

}

function va_set_import_event_meta_defaults( $event_id ) {
	if ( VA_EVENT_PTYPE != get_post_type( $event_id ) ) return;

	return va_set_event_meta_defaults( $event_id );
}

function va_event_add_admin_scripts( $hook ) {
	global $post;

	if ( empty( $post ) || VA_EVENT_PTYPE != $post->post_type ) return;

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
		'va-admin-event-edit',
		get_template_directory_uri() . '/includes/events/admin/scripts/event-edit.js',
		array( 'jquery-validate'),
		VA_VERSION,
		true
	);

	wp_localize_script( 'va-admin-event-edit', 'VA_admin_l18n', array(
		'user_admin' 		=> current_user_can('manage_options'),
		'event_type'  	=> VA_EVENT_PTYPE,
		'event_category'  => VA_EVENT_CATEGORY,
		'post_type'  		=> ( isset( $post->post_type ) ? $post->post_type : '' ),
	) );

}


function va_events_icon(){
?>
<style type="text/css">
	#icon-post.icon32-posts-event,
	#icon-edit.icon32-posts-event {
		background: url('<?php echo get_stylesheet_directory_uri(); ?>/images/admin-icon-events-32x32.png') no-repeat 2px 6px;
	}
</style>
<?php
}
