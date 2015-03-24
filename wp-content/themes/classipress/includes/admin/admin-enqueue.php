<?php
/**
 * These are scripts used within the AppThemes admin pages
 *
 * @package AppThemes
 *
 */



/**
 * Load admin scripts and styles.
 */
function cp_load_admin_scripts() {
	global $pagenow;

	wp_enqueue_script('jquery-ui-tabs');
	wp_enqueue_style('thickbox'); // needed for image upload


	//TODO: For now we call these on all admin pages because of some javascript errors, however it should be registered per admin page (like wordpress does it)
	wp_enqueue_script('jquery-ui-sortable'); //this script has issues on the page edit.php?post_type=ad_listing


	wp_enqueue_script('jquery-ui-slider');
	wp_enqueue_script( 'timepicker', get_template_directory_uri() . '/includes/js/timepicker.min.js', array( 'jquery-ui-core', 'jquery-ui-datepicker' ), '1.0.0' );

	wp_enqueue_style( 'jquery-ui-style', get_template_directory_uri() . '/includes/js/jquery-ui/jquery-ui.css', false, '1.9.2' );

	wp_enqueue_script( 'easytooltip', get_template_directory_uri() . '/includes/js/easyTooltip.js', array( 'jquery' ), '1.0' );

	if ( $pagenow == 'admin.php' ) // only trigger this on CP edit pages otherwise it causes a conflict with edit ad and edit post meta field buttons
		wp_enqueue_script( 'validate', get_template_directory_uri() . '/includes/js/validate/jquery.validate.min.js', array( 'jquery-ui-core' ), '1.11.0' );

	wp_enqueue_script( 'admin-scripts', get_template_directory_uri() . '/includes/admin/admin-scripts.js', array( 'jquery', 'media-upload', 'thickbox' ), '3.3' );

	wp_enqueue_script( 'excanvas', get_template_directory_uri() . '/includes/js/excanvas.min.js', array( 'jquery' ), '1.0' );
	wp_enqueue_script( 'flot', get_template_directory_uri() . '/includes/js/jquery.flot.min.js', array( 'excanvas' ), '0.6' );

	wp_enqueue_style( 'admin-style', get_template_directory_uri() . '/includes/admin/admin-style.css', false, '3.3' );

	//wp_localize_script( 'admin-scripts', 'theme_scripts_admin', $params );

}
add_action( 'admin_enqueue_scripts', 'cp_load_admin_scripts' );


