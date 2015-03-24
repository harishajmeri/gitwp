<?php

// include all the core admin ClassiPress files
require_once ('admin-values.php');
require_once ('admin-notices.php');
require_once ('admin-addons.php');
require_once ('admin-updates.php');


// load and create all the CP admin pages
function appthemes_admin_options() {

	if ( current_theme_supports( 'app-price-format' ) )
		add_submenu_page( 'app-dashboard', __( 'Packages', APP_TD ), __( 'Packages', APP_TD ), 'manage_options', 'packages', 'cp_ad_packs' );
	add_submenu_page( 'app-dashboard', __( 'Form Layouts', APP_TD ), __(' Form Layouts', APP_TD ), 'manage_options', 'layouts', 'cp_form_layouts' );
	add_submenu_page( 'app-dashboard', __( 'Custom Fields', APP_TD ), __( 'Custom Fields', APP_TD ), 'manage_options', 'fields', 'cp_custom_fields' );
	add_submenu_page( 'app-dashboard', __( 'System Info', APP_TD ), __( 'System Info', APP_TD ), 'manage_options', 'sysinfo', 'cp_system_info' );

	do_action( 'appthemes_add_submenu_page' );
}
add_action( 'admin_menu', 'appthemes_admin_options', 11 );



// creates the category checklist box
function cp_category_checklist($checkedcats, $exclude = '') {

	if (empty($walker) || !is_a($walker, 'Walker'))
		$walker = new Walker_Category_Checklist;

	$args = array();

    if (is_array( $checkedcats ))
        $args['selected_cats'] = $checkedcats;
    else
        $args['selected_cats'] = array();

    $args['popular_cats'] = array();
    $categories = get_categories( array('hide_empty' => 0,
                                       'taxonomy' 	 => APP_TAX_CAT,
                                       'exclude' 	 => $exclude) );

	return call_user_func_array( array(&$walker, 'walk'), array($categories, 0, $args) );
}


// this grabs the cats that should be excluded
function cp_exclude_cats ($id = NULL) {
    global $wpdb;

    $output = array();

    if ( $id )
        $sql = $wpdb->prepare( "SELECT form_cats FROM $wpdb->cp_ad_forms WHERE id != %s", $id );
    else
        $sql = "SELECT form_cats FROM $wpdb->cp_ad_forms";

    $records = $wpdb->get_results( $sql );

    if ( $records ) :

        foreach ( $records as $record )
            $output[] = implode( ',',unserialize($record->form_cats) );

    endif;

    $exclude = cp_unique_str( ',', (join( ',', $output )) );

    return $exclude;
}


// find a category match and then output it
function cp_match_cats($form_cats) {
    global $wpdb;
    $out = array();

    $terms = get_terms( APP_TAX_CAT, array(
        'include' => $form_cats
    ));

    if ( $terms ) :

        foreach ( $terms as $term ) {
            $out[] = '<a href="edit-tags.php?action=edit&taxonomy='.APP_TAX_CAT.'&post_type='.APP_POST_TYPE.'&tag_ID='. $term->term_id .'">'. $term->name .'</a>';
        }

    endif;

    return join( ', ', $out );
}


function cp_unique_str($separator, $str) {

    $str_arr = explode($separator, $str);
    $result = array_unique($str_arr);
    $unique_str = implode(',', $result);

    return $unique_str;
}


/**
* Take field input label value and make custom name
* Strip out everything excepts chars & numbers
* Used for WP custom field name i.e. Middle Name = cp_middle_name
*/
function cp_make_custom_name( $name, $table = '', $random = false ) {
	global $wpdb;
	$not_unique = false;

	$custom_name = appthemes_clean( $name );
	$custom_name = preg_replace( '/[^a-zA-Z0-9\s]/', '', $custom_name );
	if ( empty( $custom_name ) || $random )
		$custom_name = 'id_' . rand( 1, 1000 );
	$custom_name = strtolower( substr( $custom_name, 0, 30 ) );
	$custom_name = 'cp_' . str_replace( ' ', '_', $custom_name );

	if ( $table == 'fields' )
		$not_unique = $wpdb->get_var( $wpdb->prepare( "SELECT field_name FROM $wpdb->cp_ad_fields WHERE field_name = %s", $custom_name ) );

	if ( $table == 'forms' )
		$not_unique = $wpdb->get_var( $wpdb->prepare( "SELECT form_name FROM $wpdb->cp_ad_forms WHERE form_name = %s", $custom_name ) );

	if ( $not_unique )
		return cp_make_custom_name( $name, $table, true );

	return $custom_name;
}

// delete the custom form and the meta custom field data
function cp_delete_form($form_id) {
    global $wpdb;

	$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->cp_ad_forms WHERE id = %s", $form_id ) );
	$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->cp_ad_meta WHERE form_id = %s", $form_id ) );
}


function cp_admin_formbuilder($results) {
	global $wpdb;

	foreach ( $results as $result ) :
	?>

		<tr class="even" id="<?php echo $result->meta_id; ?>"><!-- id needed for jquery sortable to work -->
			<td style="min-width:100px;"><?php echo esc_html( translate( $result->field_label, APP_TD ) ); ?></td>
			<td>

		<?php

		switch ( $result->field_type ) {

		case 'text box':
		?>

			<input name="<?php echo $result->field_name; ?>" type="text" style="min-width:200px;" value="" disabled />

		<?php
		break;

		case 'text area':

		?>

			<textarea rows="4" cols="23" disabled></textarea>

		<?php
		break;

		case 'radio':

			$options = explode( ',', $result->field_values );
			$options = array_map( 'trim', $options );
			foreach ( $options as $label ) {
			?>
				<input type="radio" name="radiobutton" value="" disabled />&nbsp;<?php echo $label; ?><br />

		<?php
			}
		break;

		case 'checkbox':

			$options = explode( ',', $result->field_values );
			$options = array_map( 'trim', $options );
			foreach ( $options as $label ) {
			?>
				<input type="checkbox" name="checkbox" value="" disabled />&nbsp;<?php echo $label; ?><br />

		<?php
			}
		break;

		default: // used for drop-downs, radio buttons, and checkboxes
		?>

			<select name="dropdown">

			<?php
			$options = explode( ',', $result->field_values );
			$options = array_map( 'trim', $options );

			foreach ( $options as $option ) {
			?>

				<option style="min-width:177px" value="<?php echo $option; ?>" disabled><?php echo $option; ?></option>

			<?php
			}
			?>

			</select>

		<?php

		} //end switch
		?>

			</td>

			<td style="text-align:center;">

			    <?php
			    // only show the advanced search checkbox for price, city, and zipcode since they display the sliders
				// all other text fields are not intended for advanced search use
				$ad_search = '';
				if ( $result->field_name == 'cp_price' || $result->field_name == 'cp_city' || $result->field_name == 'cp_zipcode' )
                    $ad_search = '';
				elseif ( $result->field_perm == 1 || $result->field_type == 'text area' || $result->field_type == 'text box' )
				    $ad_search = 'disabled="disabled"';
				?>

				<input type="checkbox" name="<?php echo $result->meta_id; ?>[field_search]" id="" <?php if ( $result->field_search ) echo 'checked="yes"' ?> <?php if ( $result->field_search ) echo 'checked="yes"' ?> <?php echo $ad_search; ?> value="1" style="" />

			</td>

			<td style="text-align:center;">

				<input type="checkbox" name="<?php echo $result->meta_id; ?>[field_req]" id="" <?php if ( $result->field_req ) echo 'checked="yes"' ?> <?php if ( $result->field_req ) echo 'checked="yes"' ?> <?php if ( $result->field_perm == 1 ) echo 'disabled="disabled"'; ?> value="1" style="" />
				<?php if ($result->field_perm == 1) { ?>
					<input type="hidden" name="<?php echo $result->meta_id; ?>[field_req]" checked="yes" value="1" />
				<?php } ?>

			</td>

			<td style="text-align:center;">

				<input type="hidden" name="id[]" value="<?php echo $result->meta_id; ?>" />
				<input type="hidden" name="<?php echo $result->meta_id; ?>[id]" value="<?php echo $result->meta_id; ?>" />

				<?php if ( $result->field_perm == 1 ) { ?>
				<img src="<?php echo get_template_directory_uri(); ?>/images/remove-row-gray.png" alt="<?php _e( 'Cannot remove from layout', APP_TD ); ?>" title="<?php _e( 'Cannot remove from layout', APP_TD ); ?>" />
				<?php } else { ?>
				<a onclick="return confirmBeforeRemove();" href="?page=layouts&amp;action=formbuilder&amp;id=<?php echo $result->form_id; ?>&amp;del_id=<?php echo $result->meta_id; ?>&amp;title=<?php echo urlencode($_GET['title']) ?>"><img src="<?php echo get_template_directory_uri(); ?>/images/remove-row.png" alt="<?php _e( 'Remove from layout', APP_TD ); ?>" title="<?php _e( 'Remove from layout', APP_TD ); ?>" /></a>
				<?php } ?>

			</td>
		</tr>

	<?php
	endforeach;

}

// this creates the default fields when a form layout is created
function cp_add_core_fields($form_id) {
	global $wpdb;

	// check to see if any rows already exist for this form. If so, don't insert any data
	$wpdb->get_results( $wpdb->prepare( "SELECT form_id FROM $wpdb->cp_ad_meta WHERE form_id  = %s", $form_id ) );

	// no fields yet so let's add the defaults
	if ( $wpdb->num_rows == 0 ) {

		// post_title
		$data = array(
			'form_id' => $form_id,
			'field_id' => '1',
			'field_req' => '1',
			'field_pos' => '1',
		);
		$insert = $wpdb->insert( $wpdb->cp_ad_meta, $data );

		// cp_price
		$data = array(
			'form_id' => $form_id,
			'field_id' => '2',
			'field_req' => '1',
			'field_pos' => '2',
		);
		$insert = $wpdb->insert( $wpdb->cp_ad_meta, $data );

		// cp_street
		$data = array(
			'form_id' => $form_id,
			'field_id' => '3',
			'field_req' => '1',
			'field_pos' => '3',
		);
		$insert = $wpdb->insert( $wpdb->cp_ad_meta, $data );

		// cp_city
		$data = array(
			'form_id' => $form_id,
			'field_id' => '4',
			'field_req' => '1',
			'field_pos' => '4',
		);
		$insert = $wpdb->insert( $wpdb->cp_ad_meta, $data );

		// cp_state
		$data = array(
			'form_id' => $form_id,
			'field_id' => '5',
			'field_req' => '1',
			'field_pos' => '5',
		);
		$insert = $wpdb->insert( $wpdb->cp_ad_meta, $data );

		// cp_country
		$data = array(
			'form_id' => $form_id,
			'field_id' => '6',
			'field_req' => '1',
			'field_pos' => '6',
		);
		$insert = $wpdb->insert( $wpdb->cp_ad_meta, $data );

		// cp_zipcode
		$data = array(
			'form_id' => $form_id,
			'field_id' => '7',
			'field_req' => '1',
			'field_pos' => '7',
		);
		$insert = $wpdb->insert( $wpdb->cp_ad_meta, $data );

		// tags_input
		$data = array(
			'form_id' => $form_id,
			'field_id' => '8',
			'field_req' => '1',
			'field_pos' => '8',
		);
		$insert = $wpdb->insert( $wpdb->cp_ad_meta, $data );

		// post_content
		$data = array(
			'form_id' => $form_id,
			'field_id' => '9',
			'field_req' => '1',
			'field_pos' => '9',
		);
		$insert = $wpdb->insert( $wpdb->cp_ad_meta, $data );

	}
}


function cp_admin_db_fields($options, $cp_table, $cp_id) {
    global $wpdb;

    // gat all the admin fields
    $results = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ". $wpdb->prefix . $cp_table . " WHERE ". $cp_id ." = %d", $_GET['id'] ) );

    // If the pack has a type, check if it satisfies.
    if( isset( $results->pack_type ) && strpos( $results->pack_type, "required_" ) === 0 ){
    	$results->pack_satisfies_required = "required_";
      $results->pack_type = mb_substr($results->pack_type, 9, strlen($results->pack_type));
    }else{
    	$results->pack_satisfies_required = "";
    }

    ?>

    <table class="widefat fixed" id="tblspacer" style="width:850px;">

    <?php

    foreach ( $options as $value ) {

      if ( $results ) {

          // foreach ($results as $result):

          // check to prevent "Notice: Undefined property: stdClass::" error when php strict warnings is turned on
          if ( !isset($results->field_type) ) $field_type = ''; else $field_type = $results->field_type;
          if ( !isset($results->field_perm) ) $field_perm = ''; else $field_perm = $results->field_perm;

          switch($value['type']) {

            case 'title':
            ?>

                <thead>
                    <tr>
                        <th scope="col" width="200px"><?php echo $value['name']; ?></th><th scope="col">&nbsp;</th>
                    </tr>
                </thead>

            <?php

            break;

            case 'text':

            ?>

	       <tr id="<?php echo $value['id'] ?>_row" <?php if ($value['vis'] == '0') echo ' style="display:none;"'; ?>>
                    <td class="titledesc"><?php if ( $value['tip'] ) { ?><a href="#" tip="<?php echo esc_attr( $value['tip'] ); ?>" tabindex="99"><div class="helpico"></div></a><?php } ?><?php echo $value['name'] ?>:</td>
                    <td class="forminp"><input name="<?php echo $value['id'] ?>" id="<?php echo $value['id'] ?>" type="<?php echo $value['type'] ?>" style="<?php echo $value['css'] ?>" value="<?php echo $results->$value['id'] ?>" <?php if ($value['req']) { ?> class="required <?php if (!empty($value['altclass'])) echo $value['altclass'] ?>" <?php } ?><?php if ($value['min']) ?> minlength="<?php echo $value['min'] ?>" <?php if($value['id'] == 'field_name') { ?>readonly="readonly"<?php } ?> /><br /><small><?php echo $value['desc'] ?></small></td>
                </tr>

            <?php

            break;

            case 'select':

            ?>

               <tr id="<?php echo $value['id'] ?>_row">
                   <td class="titledesc"><?php if ( $value['tip'] ) { ?><a href="#" tip="<?php echo esc_attr( $value['tip'] ); ?>" tabindex="99"><div class="helpico"></div></a><?php } ?><?php echo $value['name'] ?>:</td>
                   <td class="forminp"><select <?php if ($value['js']) echo $value['js']; ?> <?php if(($field_perm == 1) || ($field_perm == 2)) { ?>DISABLED<?php } ?> name="<?php echo $value['id'] ?>" id="<?php echo $value['id'] ?>" style="<?php echo $value['css'] ?>">

                       <?php foreach ( $value['options'] as $key => $val ) { ?>

                             <option value="<?php echo $key ?>"<?php if (isset($results->$value['id']) && $results->$value['id'] == $key) { ?> selected="selected" <?php $field_type_out = $field_type; } ?>><?php echo $val; ?></option>

                       <?php } ?>

                       </select><br />
                       <small><?php echo $value['desc'] ?></small>

                       <?php
                       // have to submit this field as a hidden value if perms are 1 or 2 since the DISABLED option won't pass anything into the $_POST
                       if ( ($field_perm == 1) || ($field_perm == 2) ) { ?><input type="hidden" name="<?php echo $value['id']; ?>" value="<?php echo $field_type_out; ?>" /><?php } ?>

                   </td>
               </tr>

            <?php

            break;

            case 'textarea':

            ?>

               <tr id="<?php echo $value['id'] ?>_row"<?php if($value['id'] == 'field_values') { ?> style="display: none;" <?php } ?>>
                   <td class="titledesc"><?php if ( $value['tip'] ) { ?><a href="#" tip="<?php echo esc_attr( $value['tip'] ); ?>" tabindex="99"><div class="helpico"></div></a><?php } ?><?php echo $value['name']; ?>:</td>
                   <td class="forminp"><textarea <?php if((($field_perm == 1) || ($field_perm == 2)) && ($value['id'] != 'field_tooltip') && $value['id'] != 'field_values') { ?>readonly="readonly"<?php } ?> name="<?php echo $value['id']?>" id="<?php echo $value['id'] ?>" style="<?php echo $value['css'] ?>"><?php echo $results->$value['id'] ?></textarea>
                       <br /><small><?php echo $value['desc'] ?></small></td>
               </tr>

            <?php

            break;

            case 'checkbox':
            ?>

                <tr id="<?php echo $value['id'] ?>_row">
                    <td class="titledesc"><?php if ( $value['tip'] ) { ?><a href="#" tip="<?php echo esc_attr( $value['tip'] ); ?>" tabindex="99"><div class="helpico"></div></a><?php } ?><?php echo $value['name']; ?>:</td>
                    <td class="forminp"><input type="checkbox" name="<?php echo $value['id'] ?>" id="<?php echo $value['id'] ?>" value="1" style="<?php echo $value['css']?>" <?php if($results->$value['id']) { ?>checked="checked"<?php } ?> />
                        <br /><small><?php echo $value['desc'] ?></small>
                    </td>
                </tr>

            <?php
            break;

            case 'cat_checklist':

            ?>

               <tr id="<?php echo $value['id'] ?>_row">
                   <td class="titledesc"><?php if ( $value['tip'] ) { ?><a href="#" tip="<?php echo esc_attr( $value['tip'] ); ?>" tabindex="99"><div class="helpico"></div></a><?php } ?><?php echo $value['name']; ?>:</td>
                   <td class="forminp">
                       <div id="form-categorydiv">
                           <div class="tabs-panel" id="categories-all" style="<?php echo $value['css'] ?>">
                               <ul class="list:category categorychecklist form-no-clear" id="categorychecklist">

                                   <?php echo cp_category_checklist( unserialize($results->form_cats),(cp_exclude_cats($results->id)) ); ?>

                               </ul>
                           </div>
                       </div>
                       <br /><small><?php echo $value['desc'] ?></small>
                   </td>
               </tr>

            <?php

            break;


        } // end switch

      } // end $results

    } // endforeach

    ?>

    </table>

<?php
}


function cp_admin_fields( $options ) {
	global $shortname, $app_abbr, $cp_options;
?>


<div id="tabs-wrap">


    <?php

    // first generate the page tabs
    $counter = 0;

    echo '<ul class="tabs">'. "\n";
    foreach ( $options as $value ) {

        if ( in_array('tab', $value) ) :
            echo '<li><a href="#'.$value['type'].$counter.'">'.$value['tabname'].'</a></li>'. "\n";
            $counter = $counter + 1;
        endif;

    }
    echo '</ul>'. "\n\n";


     // now loop through all the options
    $counter = 0;
    $table_width = $cp_options->table_width;

    foreach ( $options as $value ) {

        switch ( $value['type'] ) {

            case 'tab':

                echo '<div id="'.$value['type'].$counter.'">'. "\n\n";
                echo '<table class="widefat fixed" style="width:'.$table_width.'; margin-bottom:20px;">'. "\n\n";

            break;

            case 'notab':

                echo '<table class="widefat fixed" style="width:'.$table_width.'; margin-bottom:20px;">'. "\n\n";

            break;

            case 'title':
            ?>

                <thead><tr><th scope="col" width="200px"><?php echo $value['name'] ?></th><th scope="col"><?php if ( isset( $value['desc'] ) ) echo $value['desc'] ?>&nbsp;</th></tr></thead>

            <?php
            break;

            case 'text':
            ?>

            <?php if ( $value['id'] <> 'field_name' ) { // don't show the meta name field used by WP. This is automatically created by CP. ?>
                <tr <?php if ($value['vis'] == '0') { ?>id="<?php if ( !empty($value['visid']) ) { echo $value['visid']; } else { echo 'field_values'; } ?>" style="display:none;"<?php } else { ?>id="<?php echo $value['id'] ?>_row"<?php } ?>>
                    <td class="titledesc"><?php if ( $value['tip'] ) { ?><a href="#" tip="<?php echo esc_attr( $value['tip'] ); ?>" tabindex="99"><div class="helpico"></div></a><?php } ?><?php echo $value['name'] ?>:</td>
                    <td class="forminp"><input name="<?php echo $value['id'] ?>" id="<?php echo $value['id'] ?>" type="<?php echo $value['type'] ?>" style="<?php echo $value['css'] ?>" value="<?php if ( get_option( $value['id'] ) ) echo get_option( $value['id'] ); else echo $value['std'] ?>"<?php if ($value['req']) { ?> class="required <?php if ( !empty($value['altclass']) ) echo $value['altclass'] ?>" <?php } ?> <?php if ( $value['min'] ) { ?> minlength="<?php echo $value['min'] ?>"<?php } ?> /><br /><small><?php echo $value['desc'] ?></small></td>
                </tr>
            <?php } ?>

            <?php
            break;

            case 'select':
            ?>

                <tr id="<?php echo $value['id'] ?>_row">
                    <td class="titledesc"><?php if ( $value['tip'] ) { ?><a href="#" tip="<?php echo esc_attr( $value['tip'] ); ?>" tabindex="99"><div class="helpico"></div></a><?php } ?><?php echo $value['name'] ?>:</td>
                    <td class="forminp"><select <?php if ( !empty( $value['js'] ) ) echo $value['js']; ?> name="<?php echo $value['id'] ?>" id="<?php echo $value['id'] ?>" style="<?php echo $value['css'] ?>"<?php if ( $value['req'] ) { ?> class="required"<?php } ?>>

                        <?php
                        foreach ($value['options'] as $key => $val) {
                        ?>

                            <option value="<?php echo $key ?>" <?php if ( get_option( $value['id'] ) == $key ) { ?> selected="selected" <?php } ?>><?php echo ucfirst($val) ?></option>

                        <?php
                        }
                        ?>

                       </select><br /><small><?php echo $value['desc'] ?></small>
                    </td>
                </tr>

            <?php
            break;

            case 'checkbox':
            ?>

                <tr id="<?php echo $value['id'] ?>_row">
                    <td class="titledesc"><?php if ( $value['tip'] ) { ?><a href="#" tip="<?php echo esc_attr( $value['tip'] ); ?>" tabindex="99"><div class="helpico"></div></a><?php } ?><?php echo $value['name'] ?>:</td>
                    <td class="forminp"><input type="checkbox" name="<?php echo $value['id'] ?>" id="<?php echo $value['id'] ?>" value="true" style="<?php echo $value['css'] ?>" <?php if ( get_option( $value['id'] ) ) { ?>checked="checked"<?php } ?> />
                        <br /><small><?php echo $value['desc'] ?></small>
                    </td>
                </tr>

            <?php
            break;

            case 'textarea':
            ?>
                <tr id="<?php echo $value['id'] ?>_row"<?php if ( $value['id'] == 'field_values' ) { ?> style="display: none;" <?php } ?>>
                    <td class="titledesc"><?php if ( $value['tip'] ) { ?><a href="#" tip="<?php echo esc_attr( $value['tip'] ); ?>" tabindex="99"><div class="helpico"></div></a><?php } ?><?php echo $value['name'] ?>:</td>
                    <td class="forminp">
                        <textarea name="<?php echo $value['id'] ?>" id="<?php echo $value['id'] ?>" style="<?php echo $value['css'] ?>" <?php if ($value['req']) { ?> class="required" <?php } ?><?php if ( $value['min'] ) { ?> minlength="<?php echo $value['min'] ?>"<?php } ?>><?php if ( get_option($value['id']) ) echo stripslashes( get_option($value['id']) ); else echo $value['std']; ?></textarea>
                        <br /><small><?php echo $value['desc'] ?></small>
                    </td>
                </tr>

            <?php
            break;

            case 'cat_checklist':
            ?>

                <tr id="<?php echo $value['id'] ?>_row">
                    <td class="titledesc"><?php if ( $value['tip'] ) { ?><a href="#" tip="<?php echo esc_attr( $value['tip'] ); ?>" tabindex="99"><div class="helpico"></div></a><?php } ?><?php echo $value['name'] ?>:</td>
                    <td class="forminp">
                        <div id="form-categorydiv">
                            <div class="tabs-panel" id="categories-all" style="<?php echo $value['css'] ?>">
                                <ul class="list:category categorychecklist form-no-clear" id="categorychecklist">
                                <?php $catcheck = cp_category_checklist(0,cp_exclude_cats()); ?>
                                <?php if($catcheck) echo $catcheck; else wp_die( '<p style="color:red;">' . __( 'All your categories are currently being used. You must remove at least one category from another form layout before you can continue.', APP_TD ) . '</p>' ); ?>
                                </ul>
                            </div>
                        </div>
                        <br /><small><?php echo $value['desc'] ?></small>
                    </td>
                </tr>

            <?php
            break;

            case 'logo':
            ?>
                <tr id="<?php echo $value['id'] ?>_row">
                    <td class="titledesc"><?php echo $value['name'] ?></td>
                    <td class="forminp">&nbsp;</td>
                </tr>

            <?php
            break;


            case 'tabend':

                echo '</table>'. "\n\n";
                echo '</div> <!-- #tab'.$counter.' -->'. "\n\n";
                $counter = $counter + 1;

            break;

            case 'notabend':

                echo '</table>'. "\n\n";

            break;

        } // end switch

    } // end foreach
    ?>

   </div> <!-- #tabs-wrap -->

<?php
}


do_action( 'appthemes_add_submenu_page_content' );


// show the ad packages admin page
function cp_ad_packs() {
	global $app_abbr, $wpdb, $current_user, $options_new_ad_pack, $options_new_membership_pack, $cp_options;

	$current_user = wp_get_current_user();

	$theswitch = isset($_GET['action']) ? $_GET['action'] : '';
	?>

	<script type="text/javascript">
	/* <![CDATA[ */
		/* initialize the form validation */
		jQuery(document).ready(function($) {
			$("#mainform").validate({errorClass: "invalid"});
		});
	/* ]]> */
	</script>

	<?php
	$options_new_pack = ( isset($_GET['type']) && $_GET['type'] == 'membership' ) ? $options_new_membership_pack : $options_new_ad_pack;

	switch ( $theswitch ) {

		case 'addpack':
		?>

			<div class="wrap">
				<div class="icon32" id="icon-themes"><br /></div>
				<h2><?php if($_GET['type'] == 'membership') _e( 'New Membership Pack', APP_TD ); else _e( 'New Ad Pack', APP_TD ); ?></h2>

				<?php cp_admin_info_box(); ?>

				<?php
				// check and make sure the form was submitted
				if ( isset($_POST['submitted']) ) {

					//setup optional variables for the package
					$post_pack_satisfies_required = isset($_POST['pack_satisfies_required']) ? $_POST['pack_satisfies_required'] : '';
					$post_pack_type = isset($_POST['pack_type']) ? $post_pack_satisfies_required . $_POST['pack_type'] : '';
					$post_pack_membership_price = isset($_POST['pack_membership_price']) ? $_POST['pack_membership_price'] : 0;

					$data = array(
						'pack_name' => appthemes_clean($_POST['pack_name']),
						'pack_desc' => appthemes_clean($_POST['pack_desc']),
						'pack_price' => appthemes_clean_price($_POST['pack_price'], 'float'),
						'pack_duration' => appthemes_clean($_POST['pack_duration']),
						'pack_status' => appthemes_clean($_POST['pack_status']),
						'pack_type' => appthemes_clean($post_pack_type),
						'pack_membership_price' => appthemes_clean_price($post_pack_membership_price, 'float'),
						'pack_owner' => appthemes_clean($_POST['pack_owner']),
						'pack_modified' => current_time('mysql'),
					);

					$insert = $wpdb->insert( $wpdb->cp_ad_packs, $data );


					if ( $insert ) :
					?>

						<p style="text-align:center;padding-top:50px;font-size:22px;"><?php _e( 'Creating your ad package.....', APP_TD ); ?><br /><br /><img src="<?php echo get_template_directory_uri(); ?>/images/loader.gif" alt="" /></p>
						<meta http-equiv="refresh" content="0; URL=?page=packages">

					<?php
					endif;

				} else {
				?>

					<form method="post" id="mainform" action="">

						<?php cp_admin_fields($options_new_pack); ?>

						<p class="submit">
							<input class="btn button-primary" name="save" type="submit" value="<?php _e( 'Create New Ad Package', APP_TD ); ?>" />&nbsp;&nbsp;&nbsp;
							<input class="btn button-secondary" name="cancel" type="button" onClick="location.href='?page=packages'" value="<?php _e( 'Cancel', APP_TD ); ?>" />
							<input name="submitted" type="hidden" value="yes" />
							<input name="pack_owner" type="hidden" value="<?php echo $current_user->user_login; ?>" />
						</p>

					</form>

				<?php } ?>

			</div><!-- end wrap -->

		<?php
		break;

		case 'editpack':
		?>

			<div class="wrap">
				<div class="icon32" id="icon-themes"><br /></div>
				<h2><?php _e( 'Edit Ad Package', APP_TD ); ?></h2>

				<?php cp_admin_info_box(); ?>

				<?php
				if ( isset($_POST['submitted']) && $_POST['submitted'] == 'yes' ) {

					//setup optional variables for the package
					$post_pack_satisfies_required = isset($_POST['pack_satisfies_required']) ? $_POST['pack_satisfies_required'] : '';
					$post_pack_type = isset($_POST['pack_type']) ? $post_pack_satisfies_required . $_POST['pack_type'] : '';
					$post_pack_membership_price = isset($_POST['pack_membership_price']) ? $_POST['pack_membership_price'] : 0;

					$data = array(
						'pack_name' => appthemes_clean($_POST['pack_name']),
						'pack_desc' => appthemes_clean($_POST['pack_desc']),
						'pack_price' => appthemes_clean_price($_POST['pack_price'], 'float'),
						'pack_duration' => appthemes_clean($_POST['pack_duration']),
						'pack_status' => appthemes_clean($_POST['pack_status']),
						'pack_type' => appthemes_clean($post_pack_type),
						'pack_membership_price' => appthemes_clean_price($post_pack_membership_price, 'float'),
						'pack_owner' => appthemes_clean($_POST['pack_owner']),
						'pack_modified' => current_time('mysql'),
					);

					$update = $wpdb->update( $wpdb->cp_ad_packs, $data, array( 'pack_id' => $_GET['id'] ) );

					?>

						<p style="text-align:center;padding-top:50px;font-size:22px;"><?php _e( 'Saving your changes.....', APP_TD ); ?><br /><br /><img src="<?php echo get_template_directory_uri(); ?>/images/loader.gif" alt="" /></p>
						<meta http-equiv="refresh" content="0; URL=?page=packages">

				<?php
				} else {
				?>

					<form method="post" id="mainform" action="">

						<?php cp_admin_db_fields($options_new_pack, 'cp_ad_packs', 'pack_id'); ?>

						<p class="submit">
							<input class="btn button-primary" name="save" type="submit" value="<?php _e( 'Save changes', APP_TD ); ?>" />&nbsp;&nbsp;&nbsp;
							<input class="btn button-secondary" name="cancel" type="button" onClick="location.href='?page=packages'" value="<?php _e( 'Cancel', APP_TD ); ?>" />
							<input name="submitted" type="hidden" value="yes" />
							<input name="pack_owner" type="hidden" value="<?php echo $current_user->user_login; ?>" />
						</p>

					</form>

				<?php } ?>

			</div><!-- end wrap -->

		<?php
		break;

		case 'delete':

			$delete = $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->cp_ad_packs WHERE pack_id = %d", $_GET['id'] ) );
			?>

				<p style="text-align:center;padding-top:50px;font-size:22px;"><?php _e( 'Deleting ad package.....', APP_TD ); ?><br /><br /><img src="<?php echo get_template_directory_uri(); ?>/images/loader.gif" alt="" /></p>
				<meta http-equiv="refresh" content="0; URL=?page=packages">

		<?php
		break;

		default:

			$results = $wpdb->get_results( "SELECT * FROM $wpdb->cp_ad_packs ORDER BY pack_id desc" );
			?>

			<div class="wrap">
				<div class="icon32" id="icon-themes"><br /></div>
				<h2><?php _e( 'Ad Packs', APP_TD ); ?>&nbsp;<a class="add-new-h2" href="?page=packages&amp;action=addpack&amp;type=ad"><?php _e( 'Add New', APP_TD ); ?></a></h2>

				<?php cp_admin_info_box(); ?>

				<?php if ( $cp_options->price_scheme != 'single' ) { ?>
					<div class="error"><p><?php printf( __( 'Ad Packs are disabled. Change the <a href="%1$s">pricing model</a> to enable Ad Packs.', APP_TD ), 'admin.php?page=app-pricing&tab=general' ); ?></p></div>
				<?php } ?>

				<p class="admin-msg"><?php _e( 'Ad Packs allow you to create bundled listing options for your customers to choose from. For example, instead of only offering a set price for xx days (30 days for $5), you could also offer discounts for longer terms (60 days for $7). These only work if you are selling ads and using the "Fixed Price Per Ad" price model.', APP_TD ); ?></p>

				<table id="tblspacer" class="widefat fixed">

					<thead>
						<tr>
							<th scope="col" style="width:35px;">&nbsp;</th>
							<th scope="col"><?php _e( 'Name', APP_TD ); ?></th>
							<th scope="col"><?php _e( 'Description', APP_TD ); ?></th>
							<th scope="col"><?php _e( 'Price Per Ad', APP_TD ); ?></th>
							<th scope="col"><?php _e( 'Duration', APP_TD ); ?></th>
							<th scope="col" style="width:150px;"><?php _e( 'Modified', APP_TD ); ?></th>
							<th scope="col" style="width:75px;"><?php _e( 'Status', APP_TD ); ?></th>
							<th scope="col" style="text-align:center;width:100px;"><?php _e( 'Actions', APP_TD ); ?></th>
							</tr>
						</thead>

						<?php
						if ( $results ) {
							$rowclass = '';
							$i=1;
						?>

							<tbody id="list">

								<?php
								foreach ( $results as $result ) {
									if ( $result->pack_status == 'active' || $result->pack_status == 'inactive' ) :
										$rowclass = ('even' == $rowclass) ? 'alt' : 'even';
								?>

										<tr class="<?php echo $rowclass; ?>">
											<td style="padding-left:10px;"><?php echo $i++; ?>.</td>
											<td><a href="?page=packages&amp;action=editpack&amp;type=ad&amp;id=<?php echo $result->pack_id; ?>"><strong><?php echo stripslashes($result->pack_name); ?></strong></a></td>
											<td><?php echo $result->pack_desc; ?></td>
											<td><?php appthemes_display_price( $result->pack_price ); ?></td>
											<td><?php echo $result->pack_duration ?>&nbsp;<?php _e( 'days', APP_TD ); ?></td>
											<td><?php echo appthemes_display_date( $result->pack_modified ); ?> <?php _e( 'by', APP_TD ); ?> <?php echo $result->pack_owner; ?></td>
											<td><?php echo cp_get_status_i18n($result->pack_status); ?></td>
											<td style="text-align:center">
												<a href="?page=packages&amp;action=editpack&amp;type=ad&amp;id=<?php echo $result->pack_id; ?>"><img src="<?php echo get_template_directory_uri(); ?>/images/edit.png" alt="<?php _e( 'Edit ad package', APP_TD ); ?>" title="<?php _e( 'Edit ad package', APP_TD ); ?>" /></a>&nbsp;&nbsp;&nbsp;
												<a onclick="return confirmBeforeDelete();" href="?page=packages&amp;action=delete&amp;id=<?php echo $result->pack_id; ?>"><img src="<?php echo get_template_directory_uri(); ?>/images/cross.png" alt="<?php _e( 'Delete ad package', APP_TD ); ?>" title="<?php _e( 'Delete ad package', APP_TD ); ?>" /></a>
											</td>
										</tr>

								<?php
									endif; //end if('active' || 'inactive')

								} // end foreach
								unset($i);
								?>

							</tbody>

						<?php
						} else {
						?>

							<tr>
								<td colspan="7"><?php _e( 'No ad packs found.', APP_TD ); ?></td>
							</tr>

						<?php
						} // end $results
						?>

				</table>


			</div><!-- end wrap for ad packs -->

			<div id="membership-packs" class="wrap">
				<div class="icon32" id="icon-themes"><br /></div>
				<h2><?php _e( 'Membership Packs', APP_TD ); ?>&nbsp;<a class="add-new-h2" href="?page=packages&amp;action=addpack&amp;type=membership"><?php _e( 'Add New', APP_TD ); ?></a></h2>

				<?php cp_admin_info_box(); ?>

				<p class="admin-msg"><?php printf( __( 'Membership Packs allow you to setup subscription-based pricing packages. This enables your customers to post unlimited ads for a set period of time or until the membership becomes inactive. These memberships affect pricing regardless of the ad packs or pricing model you have set as long as you have enabled the <a href="%1$s">membership packs</a> option.', APP_TD ), 'admin.php?page=app-pricing&tab=membership' ); ?></p>

				<table id="tblspacer" class="widefat fixed">

					<thead>
						<tr>
							<th scope="col" style="width:35px;">&nbsp;</th>
							<th scope="col"><?php _e( 'Name', APP_TD ); ?></th>
							<th scope="col"><?php _e( 'Description', APP_TD ); ?></th>
							<th scope="col"><?php _e( 'Price Modifier', APP_TD ); ?></th>
							<th scope="col"><?php _e( 'Terms', APP_TD ); ?></th>
							<th scope="col" style="width:150px;"><?php _e( 'Modified', APP_TD ); ?></th>
							<th scope="col" style="width:75px;"><?php _e( 'Status', APP_TD ); ?></th>
							<th scope="col" style="text-align:center;width:100px;"><?php _e( 'Actions', APP_TD ); ?></th>
						</tr>
					</thead>

					<?php
					if ( $results ) {
						$rowclass = '';
						$i=1;
					?>

						<tbody id="list">

							<?php
							foreach ( $results as $result ) {
								if ( $result->pack_status == 'active_membership' || $result->pack_status == 'inactive_membership' ) :
									$rowclass = 'even' == $rowclass ? 'alt' : 'even';
							?>

									<tr class="<?php echo $rowclass; ?>">
										<td style="padding-left:10px;"><?php echo $i++; ?>.</td>
										<td><a href="?page=packages&amp;action=editpack&amp;type=membership&amp;id=<?php echo $result->pack_id; ?>"><strong><?php echo stripslashes($result->pack_name); ?></strong></a></td>
										<td><?php echo $result->pack_desc; ?></td>
										<td>
						<?php switch ($result->pack_type) {
							case 'percentage':
								echo preg_replace('/.00$/', '', $result->pack_price).'% ' . __( 'of price', APP_TD ); //remove decimal when decimal is .00
								break;
							case 'discount':
								printf( __( '%s\'s less per ad', APP_TD ), appthemes_get_price( $result->pack_price ) );
								break;
							case 'required_static':
								if ( (float)$result->pack_price == 0 ) _e( 'Free', APP_TD );
								else printf( __( '%s per ad', APP_TD ), appthemes_get_price( $result->pack_price ) );
								echo ' (' . __( 'required to post', APP_TD ) . ')';
								break;
							case 'required_discount':
								printf( __( '%s\'s less per ad', APP_TD ), appthemes_get_price( $result->pack_price ) );
								echo ' (' . __( 'required to post', APP_TD ) . ')';
								break;
							case 'required_percentage':
								echo preg_replace( '/.00$/', '', $result->pack_price ).'% ' . __( 'of price', APP_TD ); //remove decimal when decimal is .00
								echo ' (' . __( 'required to post', APP_TD ) . ')';
								break;
							default: //likely 'static'
								if ( (float)$result->pack_price == 0 ) _e( 'Free', APP_TD );
								else printf( __( '%s per ad', APP_TD ), appthemes_get_price( $result->pack_price ) );
						}
						?>
										</td>
										<td><?php printf( __( '%s / %s days', APP_TD ), appthemes_get_price( $result->pack_membership_price ), $result->pack_duration ); ?></td>
										<td><?php echo appthemes_display_date( $result->pack_modified ); ?> <?php _e( 'by', APP_TD ); ?> <?php echo $result->pack_owner; ?></td>
										<td><?php echo cp_get_status_i18n($result->pack_status); ?></td>
										<td style="text-align:center">
											<a href="?page=packages&amp;action=editpack&amp;type=membership&amp;id=<?php echo $result->pack_id; ?>"><img src="<?php echo get_template_directory_uri(); ?>/images/edit.png" alt="<?php _e( 'Edit ad package', APP_TD ); ?>" title="<?php _e( 'Edit ad package', APP_TD ); ?>" /></a>&nbsp;&nbsp;&nbsp;
											<a onclick="return confirmBeforeDelete();" href="?page=packages&amp;action=delete&amp;id=<?php echo $result->pack_id; ?>"><img src="<?php echo get_template_directory_uri(); ?>/images/cross.png" alt="<?php _e( 'Delete ad package', APP_TD ); ?>" title="<?php _e( 'Delete ad package', APP_TD ); ?>" /></a>
										</td>
									</tr>

								<?php
								endif; //end if('active_membership' || 'inactive_membership')

							} // end foreach
							unset($i);
							?>

						</tbody>

					<?php
					} else {
					?>

						<tr>
							<td colspan="7"><?php _e( 'No ad packs found.', APP_TD ); ?></td>
						</tr>

					<?php
					} // end $results
					?>

				</table>


			</div><!-- end wrap for membership packs-->

	<?php
	} // end switch
	?>
	<script type="text/javascript">
	/* <![CDATA[ */
		function confirmBeforeDelete() { return confirm("<?php _e( 'Are you sure you want to delete this ad package?', APP_TD ); ?>"); }
	/* ]]> */
	</script>

<?php

}


function cp_form_layouts() {
	global $options_new_form, $wpdb, $current_user;

	$current_user = wp_get_current_user();

	// check to prevent php "notice: undefined index" msg when php strict warnings is on
	if ( isset($_GET['action']) ) $theswitch = $_GET['action']; else $theswitch ='';
	?>

	<script type="text/javascript">
	/* <![CDATA[ */
	/* initialize the form validation */
	jQuery(document).ready(function($) {
		$("#mainform").validate({errorClass: "invalid"});
	});
	/* ]]> */
	</script>

	<?php
	switch ( $theswitch ) {

		case 'addform':
		?>

			<div class="wrap">
				<div class="icon32" id="icon-themes"><br /></div>
				<h2><?php _e( 'New Form Layout', APP_TD ); ?></h2>

				<?php cp_admin_info_box(); ?>

				<?php
				// check and make sure the form was submitted and the hidden fcheck id matches the cookie fcheck id
				if ( isset($_POST['submitted']) ) {

					if ( !isset($_POST['post_category']) )
						wp_die( '<p style="color:red;">' . __( "Error: Please select at least one category. <a href='#' onclick='history.go(-1);return false;'>Go back</a>", APP_TD ) . '</p>' );

					$data = array(
						'form_name' => cp_make_custom_name( $_POST['form_label'], 'forms' ),
						'form_label' => appthemes_clean( $_POST['form_label'] ),
						'form_desc' => appthemes_clean( $_POST['form_desc'] ),
						'form_cats' => serialize( $_POST['post_category'] ),
						'form_status' => appthemes_clean( $_POST['form_status'] ),
						'form_owner' => appthemes_clean( $_POST['form_owner'] ),
						'form_created' => current_time('mysql'),
					);

					$insert = $wpdb->insert( $wpdb->cp_ad_forms, $data );

					if ( $insert ) {
					?>

						<p style="text-align:center;padding-top:50px;font-size:22px;"><?php _e( 'Creating your form.....', APP_TD ); ?><br /><br /><img src="<?php echo get_template_directory_uri(); ?>/images/loader.gif" alt="" /></p>
						<meta http-equiv="refresh" content="0; URL=?page=layouts">

					<?php
					} // end $insert

				} else {
				?>

            <form method="post" id="mainform" action="">

                <?php echo cp_admin_fields($options_new_form); ?>

                <p class="submit"><input class="btn button-primary" name="save" type="submit" value="<?php _e( 'Create New Form', APP_TD ); ?>" />&nbsp;&nbsp;&nbsp;
                <input class="btn button-secondary" name="cancel" type="button" onClick="location.href='?page=layouts'" value="<?php _e( 'Cancel', APP_TD ); ?>" /></p>
                <input name="submitted" type="hidden" value="yes" />
                <input name="form_owner" type="hidden" value="<?php echo $current_user->user_login; ?>" />

            </form>

        <?php
        } // end isset $_POST
        ?>

        </div><!-- end wrap -->

    <?php
    break;


		case 'editform':
		?>

			<div class="wrap">
				<div class="icon32" id="icon-themes"><br /></div>
				<h2><?php _e( 'Edit Form Properties', APP_TD ); ?></h2>

				<?php
				if ( isset($_POST['submitted']) && $_POST['submitted'] == 'yes' ) {

					if ( !isset($_POST['post_category']) )
						wp_die( '<p style="color:red;">' . __( "Error: Please select at least one category. <a href='#' onclick='history.go(-1);return false;'>Go back</a>", APP_TD ) . '</p>' );


					$data = array(
						'form_label' => appthemes_clean($_POST['form_label']),
						'form_desc' => appthemes_clean($_POST['form_desc']),
						'form_cats' => serialize($_POST['post_category']),
						'form_status' => appthemes_clean($_POST['form_status']),
						'form_owner' => appthemes_clean($_POST['form_owner']),
						'form_modified' => current_time('mysql'),
					);

					$wpdb->update( $wpdb->cp_ad_forms, $data, array( 'id' => $_GET['id'] ) );

				?>

					<p style="text-align:center;padding-top:50px;font-size:22px;"><?php _e( 'Saving your changes.....', APP_TD ); ?><br /><br /><img src="<?php echo get_template_directory_uri(); ?>/images/loader.gif" alt="" /></p>
					<meta http-equiv="refresh" content="0; URL=?page=layouts">

				<?php
				} else {
				?>

            <form method="post" id="mainform" action="">

            <?php echo cp_admin_db_fields($options_new_form, 'cp_ad_forms', 'id'); ?>

                <p class="submit"><input class="btn button-primary" name="save" type="submit" value="<?php _e( 'Save changes', APP_TD ); ?>" />&nbsp;&nbsp;&nbsp;
                <input class="btn button-secondary" name="cancel" type="button" onClick="location.href='?page=layouts'" value="<?php _e( 'Cancel', APP_TD ); ?>" /></p>
                <input name="submitted" type="hidden" value="yes" />
                <input name="form_owner" type="hidden" value="<?php echo $current_user->user_login; ?>" />

            </form>

        <?php
        } // end isset $_POST
        ?>

        </div><!-- end wrap -->

    <?php
    break;


    /**
    * Form Builder Page
    * Where fields are added to form layouts
    */

		case 'formbuilder':
		?>

			<div class="wrap">
				<div class="icon32" id="icon-themes"><br /></div>
				<h2><?php _e( 'Edit Form Layout', APP_TD ); ?></h2>

				<?php cp_admin_info_box(); ?>

				<?php
				// add fields to page layout on left side
				if ( isset($_POST['field_id']) ) {

					// take selected checkbox array and loop through ids
					foreach ( $_POST['field_id'] as $value ) {

						$data = array(
							'form_id' => appthemes_clean($_POST['form_id']),
							'field_id' => appthemes_clean($value),
						);

						$insert = $wpdb->insert( $wpdb->cp_ad_meta, $data );

					} // end foreach

				} // end $_POST



				// update form layout positions and required fields on left side.
				if ( isset($_POST['formlayout']) ) {

					// loop through the post array and update the required checkbox and field position
					foreach ( $_POST as $key => $value ) :

						// since there's some $_POST values we don't want to process, only give us the
						// numeric ones which means it contains a meta_id and we want to update it
						if ( is_numeric($key) ) {

							// quick hack to prevent php "notice: undefined index:" msg when php strict warnings is on
							if ( !isset($value['field_req']) ) $value['field_req'] = '';
							if ( !isset($value['field_search']) ) $value['field_search'] = '';

							$data = array(
								'field_req' => appthemes_clean($value['field_req']),
								'field_search' => appthemes_clean($value['field_search']),
							);

							$wpdb->update( $wpdb->cp_ad_meta, $data, array( 'meta_id' => $key ) );

						} // end if_numeric

					endforeach; // end for each

					echo '<p class="info">' . __( 'Your changes have been saved.', APP_TD ) . '</p>';

				} // end isset $_POST


        // check to prevent php "notice: undefined index" msg when php strict warnings is on
        if ( isset($_GET['del_id']) ) $theswitch = $_GET['del_id']; else $theswitch ='';


        // Remove items from form layout
        if ( $theswitch ) $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->cp_ad_meta WHERE meta_id = %s", $_GET['del_id'] ) );


        //update the forms modified date
				$data = array(
					'form_modified' => current_time('mysql'),
				);

				$wpdb->update( $wpdb->cp_ad_forms, $data, array( 'id' => $_GET['id'] ) );
        ?>


        <table>
            <tr style="vertical-align:top;">
                <td style="width:800px;padding:0 20px 0 0;">


                <h3><?php _e( 'Form Name', APP_TD ); ?> - <?php echo ucfirst(urldecode($_GET['title'])); ?>&nbsp;&nbsp;&nbsp;&nbsp;<span id="loading"></span></h3>

                <form method="post" id="mainform" action="">

                    <table class="widefat">
                        <thead>
                            <tr>
                                <th scope="col" colspan="2"><?php _e( 'Form Preview', APP_TD ); ?></th>
								<th scope="col" style="width:75px;text-align:center;" title="<?php _e( 'Show field in the category refine search sidebar', APP_TD ); ?>"><?php _e( 'Advanced Search', APP_TD ); ?></th>
                                <th scope="col" style="width:75px;text-align:center;"><?php _e( 'Required', APP_TD ); ?></th>
                                <th scope="col" style="width:75px;text-align:center;"><?php _e( 'Remove', APP_TD ); ?></th>
                            </tr>
                        </thead>



                        <tbody class="sortable">

                        <?php

                            // If this is the first time this form is being customized then auto
                            // create the core fields and put in cp_meta db table
                            echo cp_add_core_fields( $_GET['id'] );


                            // Then go back and select all the fields assigned to this
                            // table which now includes the added core fields.
                            $sql = $wpdb->prepare( "SELECT f.field_label, f.field_name, f.field_type, f.field_values, f.field_perm, m.meta_id, m.field_pos, m.field_search, m.field_req, m.form_id "
                                 . "FROM $wpdb->cp_ad_fields f "
                                 . "INNER JOIN $wpdb->cp_ad_meta m "
                                 . "ON f.field_id = m.field_id "
                                 . "WHERE m.form_id = %s "
                                 . "ORDER BY m.field_pos asc",
                                 $_GET['id']
                            );

                            $results = $wpdb->get_results( $sql );

                            if ( $results ) {

                                echo cp_admin_formbuilder( $results );

                            } else {

                        ?>

                        <tr>
                            <td colspan="5" style="text-align: center;"><p><br /><?php _e( 'No fields have been added to this form layout yet.', APP_TD ); ?><br /><br /></p></td>
                        </tr>

                        <?php
                            } // end $results
                            ?>

                        </tbody>

                    </table>

                    <p class="submit">
                        <input class="btn button-primary" name="save" type="submit" value="<?php _e( 'Save Changes', APP_TD ); ?>" />&nbsp;&nbsp;&nbsp;
                        <input class="btn button-secondary" name="cancel" type="button" onClick="location.href='?page=layouts'" value="<?php _e( 'Cancel', APP_TD ); ?>" />
                        <input name="formlayout" type="hidden" value="yes" />
                        <input name="form_owner" type="hidden" value="<?php $current_user->user_login; ?>" />
                    </p>
                </form>

                </td>
                <td>

                <h3><?php _e( 'Available Fields', APP_TD ); ?></h3>

                <form method="post" id="mainform" action="">


                <div class="fields-panel">

                    <table class="widefat">
                        <thead>
                            <tr>
                                <th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"/></th>
                                <th scope="col"><?php _e( 'Field Name', APP_TD ); ?></th>
                                <th scope="col"><?php _e( 'Type', APP_TD ); ?></th>
                            </tr>
                        </thead>


                        <tbody>

                        <?php
                        // Select all available fields not currently on the form layout.
                        // Also exclude any core fields since they cannot be removed from the layout.
                        $sql = $wpdb->prepare( "SELECT f.field_id,f.field_label,f.field_type "
                             . "FROM $wpdb->cp_ad_fields f "
                             . "WHERE f.field_id "
                             . "NOT IN (SELECT m.field_id "
                             . "FROM $wpdb->cp_ad_meta m "
                             . "WHERE m.form_id =  %s) "
                             . "AND f.field_perm <> '1'",
                             $_GET['id']);

                        $results = $wpdb->get_results( $sql );

                        if ( $results ) {

                            foreach ( $results as $result ) {
                        ?>

                        <tr class="even">
                            <th class="check-column" scope="row"><input type="checkbox" value="<?php echo $result->field_id; ?>" name="field_id[]"/></th>
                            <td><?php echo esc_html( translate( $result->field_label, APP_TD ) ); ?></td>
                            <td><?php echo $result->field_type; ?></td>
                        </tr>

                        <?php
                            } // end foreach

                        } else {
                        ?>

                        <tr>
                            <td colspan="4" style="text-align: center;"><p><br /><?php _e( 'No fields are available.', APP_TD ); ?><br /><br /></p></td>
                        </tr>

                        <?php
                        } // end $results
                        ?>

                        </tbody>

                    </table>

                </div>

                    <p class="submit"><input class="btn button-primary" name="save" type="submit" value="<?php _e( 'Add Fields to Form Layout', APP_TD ); ?>" /></p>
                        <input name="form_id" type="hidden" value="<?php echo $_GET['id']; ?>" />
                        <input name="submitted" type="hidden" value="yes" />


                </form>

                </td>
            </tr>
        </table>

    </div><!-- /wrap -->

    <?php

    break;



    case 'delete':

        // delete the form based on the form id
        cp_delete_form($_GET['id']);
        ?>
        <p style="text-align:center;padding-top:50px;font-size:22px;"><?php _e( 'Deleting form layout.....', APP_TD ); ?><br /><br /><img src="<?php echo get_template_directory_uri(); ?>/images/loader.gif" alt="" /></p>
        <meta http-equiv="refresh" content="0; URL=?page=layouts">

    <?php
    break;

    default:

        $results = $wpdb->get_results( "SELECT * FROM $wpdb->cp_ad_forms ORDER BY id desc" );

    ?>

        <div class="wrap">
        <div class="icon32" id="icon-themes"><br /></div>
        <h2><?php _e( 'Form Layouts', APP_TD ); ?>&nbsp;<a class="add-new-h2" href="?page=layouts&amp;action=addform"><?php _e( 'Add New', APP_TD ); ?></a></h2>

        <?php cp_admin_info_box(); ?>

        <p class="admin-msg"><?php _e( 'Form layouts allow you to create your own custom ad submission forms. Each form is essentially a container for your fields and can be applied to one or all of your categories. If you do not create any form layouts, the default one will be used. To change the default form, create a new form layout and apply it to all categories.', APP_TD ); ?></p>

        <table id="tblspacer" class="widefat fixed">

            <thead>
                <tr>
                    <th scope="col" style="width:35px;">&nbsp;</th>
                    <th scope="col"><?php _e( 'Name', APP_TD ); ?></th>
                    <th scope="col"><?php _e( 'Description', APP_TD ); ?></th>
                    <th scope="col"><?php _e( 'Categories', APP_TD ); ?></th>
                    <th scope="col" style="width:150px;"><?php _e( 'Modified', APP_TD ); ?></th>
                    <th scope="col" style="width:75px;"><?php _e( 'Status', APP_TD ); ?></th>
                    <th scope="col" style="text-align:center;width:100px;"><?php _e( 'Actions', APP_TD ); ?></th>
                </tr>
            </thead>

            <?php
            if ( $results ) {
              $rowclass = '';
              $i=1;
            ?>

              <tbody id="list">

            <?php
                foreach ( $results as $result ) {

                $rowclass = 'even' == $rowclass ? 'alt' : 'even';
              ?>

                <tr class="<?php echo $rowclass; ?>">
                    <td style="padding-left:10px;"><?php echo $i; ?>.</td>
                    <td><a href="?page=layouts&amp;action=editform&amp;id=<?php echo $result->id; ?>"><strong><?php echo $result->form_label; ?></strong></a></td>
                    <td><?php echo $result->form_desc; ?></td>
                    <td><?php echo cp_match_cats( unserialize($result->form_cats) ); ?></td>
                    <td><?php echo appthemes_display_date( $result->form_modified ); ?> <?php _e( 'by', APP_TD ); ?> <?php echo $result->form_owner; ?></td>
                    <td><?php echo cp_get_status_i18n($result->form_status); ?></td>
                    <td style="text-align:center"><a href="?page=layouts&amp;action=formbuilder&amp;id=<?php echo $result->id; ?>&amp;title=<?php echo urlencode($result->form_label); ?>"><img src="<?php echo get_template_directory_uri(); ?>/images/layout_add.png" alt="<?php _e( 'Edit form layout', APP_TD ); ?>" title="<?php _e( 'Edit form layout', APP_TD ); ?>" /></a>&nbsp;&nbsp;&nbsp;
                        <a href="?page=layouts&amp;action=editform&amp;id=<?php echo $result->id; ?>"><img src="<?php echo get_template_directory_uri(); ?>/images/edit.png" alt="<?php _e( 'Edit form properties', APP_TD ); ?>" title="<?php _e( 'Edit form properties', APP_TD ); ?>" /></a>&nbsp;&nbsp;&nbsp;
                        <a onclick="return confirmBeforeDelete();" href="?page=layouts&amp;action=delete&amp;id=<?php echo $result->id; ?>"><img src="<?php echo get_template_directory_uri(); ?>/images/cross.png" alt="<?php _e( 'Delete form layout', APP_TD ); ?>" title="<?php _e( 'Delete form layout', APP_TD ); ?>" /></a></td>
                </tr>

              <?php

                $i++;

                } // end for each
              ?>

              </tbody>

            <?php

            } else {

            ?>

                <tr>
                    <td colspan="7"><?php _e( 'No form layouts found.', APP_TD ); ?></td>
                </tr>

            <?php
            } // end $results
            ?>

            </table>


        </div><!-- end wrap -->

    <?php
    } // end switch
    ?>
    <script type="text/javascript">
        /* <![CDATA[ */
            function confirmBeforeDelete() { return confirm("<?php _e( 'Are you sure you want to delete this?', APP_TD ); ?>"); }
            function confirmBeforeRemove() { return confirm("<?php _e( 'Are you sure you want to remove this?', APP_TD ); ?>"); }
        /* ]]> */
    </script>

<?php

} // end function


function cp_custom_fields() {
	global $options_new_field, $wpdb, $current_user;

	$current_user = wp_get_current_user();
	?>

	<!-- show/hide the dropdown field values tr -->
	<script type="text/javascript">
	/* <![CDATA[ */
		jQuery(document).ready(function() {
			jQuery("#mainform").validate({errorClass: "invalid"});
		});

		function show(o){
			if(o){switch(o.value){
				case 'drop-down': jQuery('#field_values_row').show(); jQuery('#field_min_length_row').hide(); break;
				case 'radio': jQuery('#field_values_row').show(); jQuery('#field_min_length_row').hide(); break;
				case 'checkbox': jQuery('#field_values_row').show(); jQuery('#field_min_length_row').hide(); break;
				case 'text box': jQuery('#field_min_length_row').show(); jQuery('#field_values_row').hide(); break;
				default: jQuery('#field_values_row').hide(); jQuery('#field_min_length_row').hide();
			}}
		}

		//show/hide immediately on document load
		jQuery(document).ready(function() {
			show(jQuery('#field_type').get(0));
		});

		//hide unwanted options for cp_currency field
		jQuery(document).ready(function() {
			var field_name = jQuery('#field_name').val();
			if(field_name == 'cp_currency'){
				jQuery("#field_type option[value='text box']").attr("disabled", "disabled");
				jQuery("#field_type option[value='text area']").attr("disabled", "disabled");
				jQuery("#field_type option[value='checkbox']").attr("disabled", "disabled");
			}
		});
	/* ]]> */
	</script>

	<?php

	// check to prevent php "notice: undefined index" msg when php strict warnings is on
	if ( isset( $_GET['action'] ) ) $theswitch = $_GET['action']; else $theswitch = '';

	switch ( $theswitch ) {

		case 'addfield':
		?>

			<div class="wrap">
				<div class="icon32" id="icon-themes"><br /></div>
				<h2><?php _e( 'New Custom Field', APP_TD ); ?></h2>

				<?php cp_admin_info_box(); ?>

				<?php
				// check and make sure the form was submitted
				if ( isset( $_POST['submitted'] ) ) {

					$_POST['field_search'] = ''; // we aren't using this field so set it to blank for now to prevent notice

					$data = array(
						'field_name' => cp_make_custom_name( $_POST['field_label'], 'fields' ),
						'field_label' => appthemes_clean( $_POST['field_label'] ),
						'field_desc' => appthemes_clean( $_POST['field_desc'] ),
						'field_tooltip' => appthemes_clean( $_POST['field_tooltip'] ),
						'field_type' => appthemes_clean( $_POST['field_type'] ),
						'field_values' => appthemes_clean( $_POST['field_values'] ),
						'field_search' => appthemes_clean( $_POST['field_search'] ),
						'field_owner' => appthemes_clean( $_POST['field_owner'] ),
						'field_created' => current_time('mysql'),
						'field_modified' => current_time('mysql'),
					);

					$insert = $wpdb->insert( $wpdb->cp_ad_fields, $data );


					if ( $insert ) :
					?>

						<p style="text-align:center;padding-top:50px;font-size:22px;"><?php _e( 'Creating your field.....', APP_TD ); ?><br /><br /><img src="<?php echo get_template_directory_uri(); ?>/images/loader.gif" alt="" /></p>
						<meta http-equiv="refresh" content="0; URL=?page=fields">

					<?php
					endif;

					die;

				} else {
				?>

            <form method="post" id="mainform" action="">

                <?php cp_admin_fields( $options_new_field ) ?>

                <p class="submit"><input class="btn button-primary" name="save" type="submit" value="<?php _e( 'Create New Field', APP_TD ); ?>" />&nbsp;&nbsp;&nbsp;
                    <input class="btn button-secondary" name="cancel" type="button" onClick="location.href='?page=fields'" value="<?php _e( 'Cancel', APP_TD ); ?>" /></p>
                <input name="submitted" type="hidden" value="yes" />
                <input name="field_owner" type="hidden" value="<?php echo $current_user->user_login; ?>" />

            </form>

        <?php
        }
        ?>

        </div><!-- end wrap -->

    <?php
    break;


		case 'editfield':
		?>

			<div class="wrap">
				<div class="icon32" id="icon-themes"><br /></div>
				<h2><?php _e( 'Edit Custom Field', APP_TD ); ?></h2>

				<?php cp_admin_info_box(); ?>

				<?php
				if ( isset( $_POST['submitted'] ) && $_POST['submitted'] == 'yes' ) {

					$data = array(
						'field_name' => appthemes_clean($_POST['field_name']),
						'field_label' => appthemes_clean($_POST['field_label']),
						'field_desc' => appthemes_clean($_POST['field_desc']),
						'field_tooltip' => esc_attr(appthemes_clean($_POST['field_tooltip'])),
						'field_type' => appthemes_clean($_POST['field_type']),
						'field_values' => appthemes_clean($_POST['field_values']),
						'field_min_length' => appthemes_clean($_POST['field_min_length']),
						//'field_search' => appthemes_clean($_POST['field_search']),
						'field_owner' => appthemes_clean($_POST['field_owner']),
						'field_modified' => current_time('mysql'),
					);

					$wpdb->update( $wpdb->cp_ad_fields, $data, array( 'field_id' => $_GET['id'] ) );
				?>

					<p style="text-align:center;padding-top:50px;font-size:22px;">
						<?php _e( 'Saving your changes.....', APP_TD ); ?><br /><br />
						<img src="<?php echo get_template_directory_uri(); ?>/images/loader.gif" alt="" />
					</p>
					<meta http-equiv="refresh" content="0; URL=?page=fields">

				<?php
				} else {
				?>


            <form method="post" id="mainform" action="">

            <?php cp_admin_db_fields($options_new_field, 'cp_ad_fields', 'field_id') ?>

                <p class="submit">
                    <input class="btn button-primary" name="save" type="submit" value="<?php _e( 'Save changes', APP_TD ); ?>" />&nbsp;&nbsp;&nbsp;
                    <input class="btn button-secondary" name="cancel" type="button" onClick="location.href='?page=fields'" value="<?php _e( 'Cancel', APP_TD ); ?>" />
                    <input name="submitted" type="hidden" value="yes" />
                    <input name="field_owner" type="hidden" value="<?php echo $current_user->user_login; ?>" />
                </p>

            </form>

        <?php } ?>

        </div><!-- end wrap -->

    <?php
    break;


    case 'delete':

        // check and make sure this fields perms allow deletion
        $sql = $wpdb->prepare( "SELECT field_perm FROM $wpdb->cp_ad_fields WHERE field_id = %d LIMIT 1", $_GET['id'] );

        $results = $wpdb->get_row( $sql );

        // if it's not greater than zero, then delete it
        if ( !$results->field_perm > 0 ) {

            $delete = $wpdb->prepare( "DELETE FROM $wpdb->cp_ad_fields WHERE field_id = %d", $_GET['id'] );
            $wpdb->query( $delete );
        }
        ?>
        <p style="text-align:center;padding-top:50px;font-size:22px;"><?php _e( 'Deleting custom field.....', APP_TD ); ?><br /><br /><img src="<?php echo get_template_directory_uri(); ?>/images/loader.gif" alt="" /></p>
        <meta http-equiv="refresh" content="0; URL=?page=fields">

    <?php

    break;


    // cp_custom_fields() show the table of all custom fields
    default:

         $sql = "SELECT field_id, field_name, field_label, field_desc, field_tooltip, field_type, field_perm, field_owner, field_modified "
             . "FROM $wpdb->cp_ad_fields "
             . "ORDER BY field_name desc";

        $results = $wpdb->get_results($sql);
        ?>

        <div class="wrap">
        <div class="icon32" id="icon-tools"><br /></div>
        <h2><?php _e( 'Custom Fields', APP_TD ); ?>&nbsp;<a class="add-new-h2" href="?page=fields&amp;action=addfield"><?php _e( 'Add New', APP_TD ); ?></a></h2>

        <?php cp_admin_info_box(); ?>

        <p class="admin-msg"><?php _e( 'Custom fields allow you to customize your ad submission forms and collect more information. Each custom field needs to be added to a form layout in order to be visible on your website. You can create unlimited custom fields and each one can be used across multiple form layouts. It is highly recommended to NOT delete a custom field once it is being used on your ads because it could cause ad editing problems for your customers.', APP_TD ); ?></p>

        <table id="tblspacer" class="widefat fixed">

            <thead>
                <tr>
                    <th scope="col" style="width:35px;">&nbsp;</th>
                    <th scope="col"><?php _e( 'Name', APP_TD ); ?></th>
                    <th scope="col" style="width:100px;"><?php _e( 'Type', APP_TD ); ?></th>
                    <th scope="col"><?php _e( 'Description', APP_TD ); ?></th>
                    <th scope="col" style="width:150px;"><?php _e( 'Modified', APP_TD ); ?></th>
                    <th scope="col" style="text-align:center;width:100px;"><?php _e( 'Actions', APP_TD ); ?></th>
                </tr>
            </thead>

            <?php
            if ($results) {
            ?>

                <tbody id="list">

                  <?php
                  $rowclass = '';
                  $i=1;

                  foreach($results as $result) {

                    $rowclass = 'even' == $rowclass ? 'alt' : 'even';
                    ?>

                    <tr class="<?php echo $rowclass; ?>">
                        <td style="padding-left:10px;"><?php echo $i; ?>.</td>
                        <td><a href="?page=fields&amp;action=editfield&amp;id=<?php echo $result->field_id; ?>"><strong><?php echo esc_html( translate( $result->field_label, APP_TD ) ); ?></strong></a></td>
                        <td><?php echo $result->field_type; ?></td>
                        <td><?php echo esc_html( translate( $result->field_desc, APP_TD ) ); ?></td>
                        <td><?php echo appthemes_display_date( $result->field_modified ); ?> <?php _e( 'by', APP_TD ); ?> <?php echo $result->field_owner; ?></td>
                        <td style="text-align:center">

                            <?php
                            // show the correct edit options based on perms
                            switch($result->field_perm) {

                                case '1': // core fields no editing
                                ?>

                                    <a href="?page=fields&amp;action=editfield&amp;id=<?php echo $result->field_id; ?>"><img src="<?php echo get_template_directory_uri(); ?>/images/edit.png" alt="" /></a>&nbsp;&nbsp;&nbsp;
                                    <img src="<?php echo get_template_directory_uri(); ?>/images/cross-grey.png" alt="" />

                                <?php
                                break;

                                case '2': // core fields some editing
                                ?>

                                    <a href="?page=fields&amp;action=editfield&amp;id=<?php echo $result->field_id; ?>"><img src="<?php echo get_template_directory_uri(); ?>/images/edit.png" alt="" /></a>&nbsp;&nbsp;&nbsp;
                                    <img src="<?php echo get_template_directory_uri(); ?>/images/cross-grey.png" alt="" />

                                <?php
                                break;

                                default: // regular fields full editing
                                    // don't change these two lines to plain html/php. Get t_else error msg
                                    echo '<a href="?page=fields&amp;action=editfield&amp;id='. $result->field_id .'"><img src="'. get_template_directory_uri() .'/images/edit.png" alt="" /></a>&nbsp;&nbsp;&nbsp;';
                                    echo '<a onclick="return confirmBeforeDelete();" href="?page=fields&amp;action=delete&amp;id='. $result->field_id .'"><img src="'. get_template_directory_uri() .'/images/cross.png" alt="" /></a>';

                           } // endswitch
                           ?>

                        </td>
                    </tr>

                <?php
                    $i++;

                  } //end foreach;
                  //} // mystery bracket which makes it work
                  ?>

              </tbody>

            <?php
            } else {
            ?>

                <tr>
                    <td colspan="5"><?php _e( 'No custom fields found. This usually means your install script did not run correctly. Go back and try reactivating the theme again.', APP_TD ); ?></td>
                </tr>

            <?php
            } // end $results
            ?>

        </table>

        </div><!-- end wrap -->

    <?php
    } // endswitch
    ?>



    <script type="text/javascript">
        /* <![CDATA[ */
            function confirmBeforeDelete() { return confirm("<?php _e( 'WARNING: Deleting this field will prevent any existing ads currently using this field from displaying the field value. Deleting fields is NOT recommended unless you do not have any existing ads using this field. Are you sure you want to delete this field?? (This cannot be undone)', APP_TD ); ?>"); }
        /* ]]> */
    </script>

<?php

} // end function


// deletes all the ClassiPress database tables
function cp_delete_db_tables() {
    global $wpdb, $app_db_tables;

    echo '<p class="info">';

    foreach ( $app_db_tables as $key => $value ) {
        $sql = "DROP TABLE IF EXISTS ". $wpdb->prefix . $value;
        $wpdb->query($sql);

        printf( __( "Table '%s' has been deleted.", APP_TD ), $value );
        echo '<br/>';
    }

    echo '</p>';
}


// deletes all the ClassiPress database tables
function cp_delete_all_options() {
    global $wpdb;

    $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name like 'cp_%'" );
    echo '<p class="info">' . __( 'All ClassiPress options have been deleted from the WordPress options table.', APP_TD ) . '</p>';
}

// flushes the caches
function cp_flush_all_cache() {
	global $wpdb, $app_transients;

	$output = '';

	foreach ( $app_transients as $key => $value ) {
		delete_transient($value);
		$output .= sprintf( '<br />' . __( "ClassiPress '%s' cache has been flushed.", APP_TD ) . '<br />', $value );
	}

	return $output;

}


// system information page
function cp_system_info() {
    global $wpdb, $system_info, $app_version;
?>

        <div class="wrap">
            <div class="icon32" id="icon-options-general"><br /></div>
            <h2><?php _e( 'ClassiPress System Info', APP_TD ); ?></h2>

            <?php cp_admin_info_box(); ?>

            <?php
            // delete all the db tables if the button has been pressed.
            if ( isset($_POST['deletetables']) )
                cp_delete_db_tables();

            // delete all the cp config options from the wp_options table if the button has been pressed.
            if ( isset($_POST['deleteoptions']) )
                cp_delete_all_options();

			// flush the cache if the button has been pressed.
			if ( isset($_POST['flushcache']) )
				echo cp_flush_all_cache();

			// reinstall completed
			if ( isset($_GET['reinstall']) )
			    echo '<p class="info">' . __( 'ClassiPress was successfully reinstalled.', APP_TD ) . '</p>';
            ?>

			<script type="text/javascript">
			jQuery(function() {
				jQuery("#tabs-wrap").tabs({
					fx: {
						opacity: 'toggle',
						duration: 200
					}
				});
			});
			</script>

		<div id="tabs-wrap">
			<ul class="tabs">
				<li><a href="#tab0"><?php _e( 'Debug Info', APP_TD ); ?></a></li>
				<li><a href="#tab1"><?php _e( 'Cron Jobs', APP_TD ); ?></a></li>
				<li><a href="#tab2"><?php _e( 'Advanced', APP_TD ); ?></a></li>
			</ul>

			<div id="tab0">


                <table class="widefat fixed" style="width:850px;">

                    <thead>
                        <tr>
                            <th scope="col" width="200px"><?php _e( 'Theme Info', APP_TD ); ?></th>
                            <th scope="col">&nbsp;</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td class="titledesc"><?php _e( 'ClassiPress Version', APP_TD ); ?></td>
                            <td class="forminp"><?php echo $app_version; ?></td>
                        </tr>

                        <tr>
                            <td class="titledesc"><?php _e( 'ClassiPress DB Version', APP_TD ); ?></td>
                            <td class="forminp"><?php echo get_option('cp_db_version'); ?></td>
                        </tr>

                        <tr>
                            <td class="titledesc"><?php _e( 'WordPress Version', APP_TD ); ?></td>
                            <td class="forminp"><?php bloginfo('version'); ?> <?php if ( is_multisite() ) echo '- ' . __( 'Multisite', APP_TD ); ?></td>
                        </tr>

                        <tr>
                            <td class="titledesc"><?php _e( 'Theme Path', APP_TD ); ?></td>
                            <td class="forminp"><?php echo get_template_directory_uri(); ?></td>
                        </tr>

                    <thead>
                        <tr>
                            <th scope="col" width="200px"><?php _e( 'Server Info', APP_TD ); ?></th>
                            <th scope="col">&nbsp;</th>
                        </tr>
                    </thead>

                        <tr>
                            <td class="titledesc"><?php _e( 'PHP Version', APP_TD ); ?></td>
                            <td class="forminp"><?php if (function_exists('phpversion')) echo phpversion(); ?></td>
                        </tr>

                        <tr>
                            <td class="titledesc"><?php _e( 'Server Software', APP_TD ); ?></td>
                            <td class="forminp"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
                        </tr>

                        <tr>
                            <td class="titledesc"><?php _e( 'UPLOAD_MAX_FILESIZE', APP_TD ); ?></td>
                            <td class="forminp"><?php if (function_exists('phpversion')) echo ini_get('upload_max_filesize'); ?></td>
                        </tr>

                        <tr>
                            <td class="titledesc"><?php _e( 'DISPLAY_ERRORS', APP_TD ); ?></td>
                            <td class="forminp"><?php if (function_exists('phpversion')) echo ini_get('display_errors'); ?></td>
                        </tr>


                    <thead>
                        <tr>
                            <th scope="col" width="200px"><?php _e( 'Image Support', APP_TD ); ?></th>
                            <th scope="col">&nbsp;</th>
                        </tr>
                    </thead>

                        <tr>
                            <td class="titledesc"><?php _e( 'GD Library Check', APP_TD ); ?></td>
                            <td class="forminp"><?php if (extension_loaded('gd') && function_exists('gd_info')) echo '<font color="green">' . __( 'Your server supports the GD Library.', APP_TD ). '</font>'; else echo '<font color="red">' . __( 'Your server does not have the GD Library enabled so the legacy image resizer script (TimThumb) will not work. Most servers with PHP 4.3+ includes this by default.', APP_TD ) . '</font>'; ?></td>
                        </tr>

                        <tr>
                            <td class="titledesc"><?php _e( 'Image Upload Path', APP_TD ); ?></td>
                            <td class="forminp"><?php $uploads = wp_upload_dir(); echo $uploads['url']; ?>
                            <?php if ( ! is_multisite() ) printf( ' - <a href="%s">' . __( '(change this)', APP_TD ) . '</a>', 'options-media.php' ); ?></td>
                        </tr>

                    <thead>
                        <tr>
                            <th scope="col" width="200px"><?php _e( 'PayPal IPN Check', APP_TD ); ?></th>
                            <th scope="col">&nbsp;</th>
                        </tr>
                    </thead>

                        <tr>
                            <td class="titledesc"><?php _e( 'FSOCKOPEN Check', APP_TD ); ?></td>
                            <td class="forminp"><?php if ( function_exists('fsockopen') ) echo '<span style="color:green">' . __( 'Your server has fsockopen enabled.', APP_TD ) . '</span>'; else echo '<span style="color:red">' . __( 'Your server does not have fsockopen enabled so PayPal IPN will not work. Contact your host provider to have it enabled.', APP_TD ) . '</span>'; ?></td>
                        </tr>

						<tr>
                            <td class="titledesc"><?php _e( 'OPENSSL Check', APP_TD ); ?></td>
                            <td class="forminp"><?php if ( function_exists('openssl_open') ) echo '<span style="color:green">' . __( 'Your server has openssl_open enabled. Also make sure port 443 is open on the firewall.', APP_TD ) . '</span>'; else echo '<span style="color:red">' . __( 'Your server does not have openssl_open enabled so PayPal IPN will not work. Contact your host provider to have it enabled.', APP_TD ) . '</span>'; ?></td>
                        </tr>

                        <?php if ( function_exists( 'wp_remote_post' ) ) : ?>
                            <tr>
                                <td class="titledesc"><?php _e( 'WP Remote Post Check', APP_TD ); ?></td>
                                <td class="forminp"><?php
                                    $paypal_adr = 'https://www.paypal.com/cgi-bin/webscr';
                                    $request['cmd'] = '_notify-validate';
                                    $params = array(
                                        'timeout' => 10,
                                        'user-agent' => 'ClassiPress/' . $app_version,
                                        'body' => $request
                                    );
                                    $response = wp_remote_post( $paypal_adr, $params );

                                    // Retry
                                    if ( is_wp_error($response) ) {
                                        $params['sslverify'] = false;
                                        $response = wp_remote_post( $paypal_adr, $params );
                                    }

                                    if ( !is_wp_error($response) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) echo '<span style="color:green">' . __( 'The wp_remote_post() test to PayPal was successful.', APP_TD ) . '</span>'; else echo '<span style="color:red">' . __( 'The wp_remote_post() test to PayPal failed. Sorry, PayPal IPN won\'t work with your server.', APP_TD ) . '</span>';
                                ?></td>
                            </tr>
                        <?php endif; ?>

                        <thead>
                        <tr>
                            <th scope="col" width="200px"><?php _e( 'Other Checks', APP_TD ); ?></th>
                            <th scope="col">&nbsp;</th>
                        </tr>
                    </thead>

                        <tr>
                            <td class="titledesc"><?php _e( 'CURL Check', APP_TD ); ?></td>
                            <td class="forminp"><?php if ( function_exists('curl_init') ) echo '<span style="color:green">' . __( 'Your server has curl enabled.', APP_TD ) . '</span>'; else echo '<span style="color:red">' . __( 'Your server does not have curl enabled so some functions will not work. Contact your host provider to have it enabled.', APP_TD ) . '</span>'; ?></td>
                        </tr>

                        <tr>
                            <td class="titledesc"><?php _e( 'JSON DECODE Check', APP_TD ); ?></td>
                            <td class="forminp"><?php if ( function_exists('json_decode') ) echo '<span style="color:green">' . __( 'Your server has json_decode enabled.', APP_TD ) . '</span>'; else echo '<span style="color:red">' . __( 'Your server does not have json_decode enabled so some functions will not work. Contact your host provider to have it enabled.', APP_TD ) . '</span>'; ?></td>
                        </tr>


                    </tbody>

                </table>

				</div> <!-- # tab0 -->

				<div id="tab1">

					<table class="widefat fixed" style="width:850px;">
						<thead>
							<tr>
								<th scope="col"><?php _e( 'Next Run Date', APP_TD ); ?></th>
								<th scope="col"><?php _e( 'Frequency', APP_TD ); ?></th>
								<th scope="col"><?php _e( 'Hook Name', APP_TD ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
								$seconds_offset = get_option('gmt_offset') * 3600;
								$cron = _get_cron_array();
								$schedules = wp_get_schedules();
								$date_format = _x( 'M j, Y @ G:i', 'Cron date/time format', APP_TD );
								foreach ( $cron as $timestamp => $cronhooks ) {
									foreach ( (array) $cronhooks as $hook => $events ) {
										foreach ( (array) $events as $key => $event ) {
											$cron[ $timestamp ][ $hook ][ $key ][ 'date' ] = date_i18n( $date_format, $timestamp + $seconds_offset );
										}
									}
								}
							?>
							<?php foreach ( $cron as $timestamp => $cronhooks ) { ?>
								<?php foreach ( (array) $cronhooks as $hook => $events ) { ?>
									<?php foreach ( (array) $events as $event ) { ?>
										<tr>
											<th scope="row"><?php echo $event[ 'date' ]; ?></th>
											<td>
                                            <?php
                                            if ( $event[ 'schedule' ] ) {
                                                echo $schedules [ $event[ 'schedule' ] ][ 'display' ];
                                            } else {
                                                ?><em><?php _e( 'One-off event', APP_TD ); ?></em><?php
                                            }
                                            ?>
											</td>
											<td><?php echo $hook; ?></td>
										</tr>
									<?php } ?>
								<?php } ?>
							<?php } ?>
						</tbody>
					</table>

				</div> <!-- # tab1 -->

				<div id="tab2">

				<table class="widefat fixed" style="width:850px;">


				<thead>
					<tr>
						<th scope="col" width="200px"><?php _e( 'Theme Cache', APP_TD ); ?></th>
						<th scope="col">&nbsp;</th>
					</tr>
                </thead>

				<form method="post" id="mainform" action="">
                    <tr>
                        <td class="titledesc"><?php _e( 'Flush Theme Cache', APP_TD ); ?></td>
                        <td class="forminp">
                            <p class="submit"><input class="button-secondary" name="save" type="submit" value="<?php _e( 'Flush Entire ClassiPress Cache', APP_TD ); ?>" /><br />
                        <?php _e( "Sometimes you may have changed something and it hasn't been updated on your site. Flushing the cache will empty anything that ClassiPress has stored in the cache (i.e. category drop-down menu, home page directory structure, etc).", APP_TD ); ?>
                            </p>
                            <input name="flushcache" type="hidden" value="yes" />
                        </td>
                    </tr>
                </form>

					<thead>
							<tr>
								<th scope="col" width="200px"><?php _e( 'Uninstall Theme', APP_TD ); ?></th>
								<th scope="col">&nbsp;</th>
							</tr>
						</thead>

					<form method="post" id="mainform" action="">
						<tr>
							<td class="titledesc"><?php _e( 'Delete Database Tables', APP_TD ); ?></td>
							<td class="forminp">
								<p class="submit"><input class="button-secondary" onclick="return confirmBeforeDeleteTbls();" name="save" type="submit" value="<?php _e( 'Delete ClassiPress Database Tables', APP_TD ); ?>" /><br />
							<?php _e( 'Do you wish to completely delete all ClassiPress database tables? Once you do this you will lose any custom fields, forms, ad packs, etc that you have created.', APP_TD ); ?>
								</p>
								<input name="deletetables" type="hidden" value="yes" />
							</td>
						</tr>
					</form>

					<form method="post" id="mainform" action="">
						<tr>
							<td class="titledesc"><?php _e( 'Delete Config Options', APP_TD ); ?></td>
							<td class="forminp">
								<p class="submit"><input class="button-secondary" onclick="return confirmBeforeDeleteOptions();" name="save" type="submit" value="<?php _e( 'Delete ClassiPress Config Options', APP_TD ); ?>" /><br />
							<?php _e( 'Do you wish to completely delete all ClassiPress configuration options? This will delete all values saved on the settings, pricing, gateways, etc admin pages from the wp_options database table.', APP_TD ); ?>
								</p>
								<input name="deleteoptions" type="hidden" value="yes" />
							</td>
						</tr>
					</form>

				<thead>
					<tr>
						<th scope="col" width="200px"><?php _e( 'Theme', APP_TD ); ?></th>
						<th scope="col">&nbsp;</th>
					</tr>
                </thead>
                    <tr>
                        <td class="titledesc"><?php _e( 'Rerun Migration Script', APP_TD ); ?></td>
                        <td class="forminp">
                            <form action="admin.php?page=app-settings" id="reinstall-form" method="post">
                                <p class="submit btop">
                                    <input class="button-secondary" type="submit" value="<?php _e( 'Rerun ClassiPress Migration Script', APP_TD ); ?>" name="migrate" /><br />
			                        <?php _e( "If you're still using ClassiPress version 3.0.4 (or earlier) and were not prompted to upgrade to 3.0.5 or the script timed out, click this button. It will attempt to rerun the migration script again. Running this script won't do any harm if you aren't sure about it.", APP_TD ); ?> <br /><br />
                                </p>
                                <input type="hidden" value="convertToCustomPostType" name="submitted" />
                            </form>
                        </td>
                    </tr>


				</table>

			</div> <!-- # tab2 -->

		</div><!-- #tab-wrap -->


        </div>

        <script type="text/javascript">
        /* <![CDATA[ */
            function confirmBeforeDeleteTbls() { return confirm("<?php _e( 'WARNING: You are about to completely delete all ClassiPress database tables. Are you sure you want to proceed? (This cannot be undone)', APP_TD ); ?>"); }
            function confirmBeforeDeleteOptions() { return confirm("<?php _e( 'WARNING: You are about to completely delete all ClassiPress configuration options from the wp_options database table. Are you sure you want to proceed? (This cannot be undone)', APP_TD ); ?>"); }
        /* ]]> */
        </script>

<?php
}


function cp_csv_importer() {
	$fields = array(
		'title'       => 'post_title',
		'description' => 'post_content',
		'status'      => 'post_status',
		'author'      => 'post_author',
		'date'        => 'post_date',
		'slug'        => 'post_name'
	);

	$args = array(
		'taxonomies' => array( APP_TAX_CAT, APP_TAX_TAG ),

		'custom_fields' => array(
			'id'          => 'cp_sys_ad_conf_id',
			'expire_date' => 'cp_sys_expire_date',
			'duration'    => 'cp_sys_ad_duration',
			'total_cost'  => 'cp_sys_total_ad_cost',
			'price'       => 'cp_price',
			'street'      => 'cp_street',
			'city'        => 'cp_city',
			'zipcode'     => 'cp_zipcode',
			'state'       => 'cp_state',
			'country'     => 'cp_country'
		),

		'attachments' => true

	);

	$args = apply_filters( 'cp_csv_importer_args', $args );

	new CP_Importer( APP_POST_TYPE, $fields, $args );
}
add_action( 'wp_loaded', 'cp_csv_importer' );


class CP_Importer extends APP_Importer {

	function setup() {
		parent::setup();

		$this->args['admin_action_priority'] = 11;
	}
}


