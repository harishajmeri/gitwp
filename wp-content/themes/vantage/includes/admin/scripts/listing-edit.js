jQuery( function() {

	// ** EDIT POST METABOX **

	var cat_checklist = jQuery( '#' + VA_admin_l18n.listing_category + 'checklist');

	// don't allow adding new categories on existing listings, except for admins
	if ( cat_checklist.length && ! VA_admin_l18n.user_admin && VA_admin_l18n.listing_type == VA_admin_l18n.post_type ) {
		var cats_count = cat_checklist.find(':checked').length;
		if ( cats_count > 0 ) jQuery( '#' + VA_admin_l18n.listing_category + '-add-toggle').hide();
	}

	// ** QUICK EDIT METABOX **

	// auto select the options checklist
	jQuery('a.editinline').live('click', function() {

		if ( VA_admin_l18n.listing_type != VA_admin_l18n.post_type )
			return false;

		if ( typeof (this) == "object" ) {
            post_id = inlineEditPost.getId(this)
        }

		var cats = 0;
		var inline = jQuery("#inline_" + post_id);
		var edit = jQuery("#edit-" + post_id);

		jQuery(".post_category", inline).each(function () {
             var term_id = jQuery(this).text();
             if (term_id) {
                 jQuery("ul." + VA_admin_l18n.listing_category + "-checklist :radio", edit).val(term_id.split(','))
				 cats++;
              }
         });

		 // enable the categories on the quick edit panel for listings with no categories selected
		 if ( ! cats ) jQuery("ul." + VA_admin_l18n.listing_category + "-checklist :radio", edit).attr('disabled', false);

		return false;

	});


	// ** GALLERY MANAGER **

	function va_init_gallery_manager() {

		var win = window.dialogArguments || opener || parent || top;

		// retrieve the attachment id from the uploader form
		get_thumbnail_id = function( media_item ) {
			var image = jQuery('input[name*=send]', media_item );
			var attachment_id = jQuery( image ).attr('name');

			attachment_id = attachment_id.replace(/send\[|\]/g,'');

			return attachment_id;
		}

		// display attach/unattach links on the media manager window
		display_gallery_operation = function( media_item ) {
			var attachment_id = get_thumbnail_id( media_item );
			var gallery_image = win.jQuery('input[name=listing-thumb][value=' + attachment_id + ']');
			var image = gallery_image.val();

			if ( 'undefined' == typeof( image ) )
				action = the_gallery_operation_link( attachment_id, VA_admin_i18n.attach );
			else
				action = the_gallery_operation_link( attachment_id, VA_admin_i18n.unattach );

			// display the link on the media uploader footer
			jQuery('input[name="send['+ attachment_id +']"]').after( action );
		}

		// helper to output the display attach/unattach links on the media manager window
		the_gallery_operation_link = function( attachment_id, operation ) {
			if ( VA_admin_i18n.attach == operation )
				operation_text = VA_admin_i18n.attach_text;
			else
				operation_text = VA_admin_i18n.unattach_text;

			link = "<a class='wp-post-thumbnail " + operation + "' id='wp-post-attachment-" + attachment_id + "' href='#'>" + operation_text + "</a>";	

			return link;
		}

		// displays the uploaded/attached image on the gallery meta box
		gallery_attach = function( html ) {
			var placeholder = jQuery('.inside img:last', '#gallerydiv').html();

			if ( placeholder )
				jQuery('.inside img:last', '#gallerydiv').after( html );
			else
				jQuery('.inside p.hide-if-no-js', '#gallerydiv').before( html );
		}

		// removes the specified image from the gallery meta box
		gallery_unattach = function( id ) {
			jQuery('#listing-thumb-' + id, '#gallerydiv').remove();
		}

		// attach uploaded images to a listing using ajax
		update_gallery = function( id, operation, featured ) {
			if ( 'undefined' == typeof( id ) ) return;

			var $link = jQuery('a#wp-post-attachment-' + id);
			$link.text( VA_admin_i18n.saving );

			jQuery.post(VA_admin_i18n.ajaxurl, {
				action: 		'va_update_listing_attachment',
				operation:		operation,
				post_id: 		post_id,
				thumbnail_id: 	id,
				_ajax_nonce: 	VA_admin_i18n.ajax_nonce,
				cookie: 		encodeURIComponent(document.cookie),
				featured: 		featured,
			}, function(str) {
				var win = window.dialogArguments || opener || parent || top;

				$link.text( VA_admin_i18n.attached );
				if ( '0' == str ) {
					alert( VA_admin_i18n.error );
				} else {
					jQuery('a.wp-post-thumbnail').show();
					$link.text( VA_admin_i18n.done ).fadeOut( 2000, function() {
						$link.replaceWith( the_gallery_operation_link( id, ( VA_admin_i18n.attach == operation ? VA_admin_i18n.unattach : VA_admin_i18n.attach ) ) );
					});

					// update the gallery metabox HTML
					if ( VA_admin_i18n.attach == operation )
						win.gallery_attach( str );
					else
						win.gallery_unattach( id );
				}
			});
		}

		// bind the gallery updater with the Wordpress uploader
		if  ( 'undefined' != typeof( uploader ) ) {
			uploader.bind('FileUploaded', function(up, file, response) {
				update_gallery( response.response, VA_admin_i18n.attach );
			});
		}

		// display the attach/unattach links when a new media item is being displayed
		jQuery('.media-item.open').live('mouseover', function() {
	 		if ( ! jQuery(this).data('init') ) {
				if ( 'undefined' != typeof ( this ) ) {
	            	jQuery(this).data('init', true);
					display_gallery_operation( this );
				}
	        }
		});

		// attach/unattach images to the listing gallery
		jQuery('.wp-post-thumbnail').live('click', function(event) {
				var $link = jQuery(this);
				var media_item = $link.parents('.media-item');
				var attachment_id = get_thumbnail_id( media_item );
				var gallery_image = win.jQuery('input[name=listing-thumb][value=' + attachment_id + ']');

				var featured = 0;
				var operation = '';

				// attach an image
				if ( $link.hasClass( VA_admin_i18n.attach ) )
					operation = VA_admin_i18n.attach;

				// unattach an image
				else if ( $link.hasClass( VA_admin_i18n.unattach ) ) {
					operation = VA_admin_i18n.unattach;

					// if the image is featured, trigger the thumbnail link
					if ( gallery_image.hasClass('featured') ) {
						win.jQuery('#remove-post-thumbnail').trigger('click');
					}

				// feature and attach image
				} else {

					action = $link.attr('onclick');
					if ( 'undefined' != typeof( action ) && action.match(/WPSetAsThumbnail/gi) ) {

						// remove any existing 'featured' CSS class from the listing gallery
						win.jQuery('input[name=listing-thumb]').removeClass('featured');

						// attach image to listing if not already attached
						if ( ! gallery_image.length ) {

							operation = VA_admin_i18n.attach;
							featured = 1;

						// image already attached - add the 'featured' CSS class
						} else {
							 gallery_image.addClass('featured');
						}

					}
				}

				// dinamically update the listing gallery
				if ( operation ) {
					update_gallery( attachment_id, operation, featured );
				}
				return false;
		});
	}

	// initialize Vantage gallery manager
	va_init_gallery_manager();

});

function vantage_map_edit() {
	function map_init( lat, lng ) {

		map_initialized = true;

		var markers_opts = [
			{
				"lat" : lat,
				"lng" : lng,
				'draggable' : true,
			}
		];

		jQuery('#listing-map').appthemes_map({
			zoom : 15,
			center_lat : lat,
			center_lng : lng,
			markers: markers_opts,
			auto_zoom: false,
			marker_drag_end: function( marker_key, lat, lng ) {

				jQuery('input[name="lat"]').val( lat );
				jQuery('input[name="lng"]').val( lng );
				
				update_position( lat, lng , marker_key );
				geocode_lat_lng( lat, lng );
			}
		});

		var address = jQuery('#listing-address').val();
		var lat = jQuery('input[name="lat"]').val();
		var lng = jQuery('input[name="lng"]').val();
	
		if ( address != '' && ( lat == 0 && lng == 0 ) )
			update_map(jQuery.noop);
	}

	function geocode_lat_lng(lat, lng) {

		jQuery.getJSON( ajaxurl, {
			action: 'vantage_single_listing_geocode',
			lat: lat,
			lng: lng
		}, function(response) {

			if( response.address ) {
				jQuery('#listing-address').val( response.address );
			}
		} );
	}

	function update_position( lat, lng, marker_key ) {

		if ( !map_initialized ) {
			return map_init( lat, lng );
		}

		var marker_update_opts = {
			marker_key: marker_key,
			lat: lat,
			lng: lng
		};

		jQuery('#listing-map').appthemes_map('update_marker_position', marker_update_opts );
	}

	jQuery('#listing-address').keydown(function(e) {
		if (e.keyCode == 13) {
			jQuery('#listing-find-on-map').click();
			e.preventDefault();
		}
	});

	jQuery('#listing-find-on-map').click(function(ev) {

		jQuery.getJSON( ajaxurl, {
			action: 'vantage_single_listing_geocode',
			address: jQuery('#listing-address').val(),
		}, function(response) {
			if ( response.formatted_address ) { 
				jQuery('#listing-address').val( response.formatted_address );
			}

			jQuery('input[name="lat"]').val(response.coords.lat);
			jQuery('input[name="lng"]').val(response.coords.lng);

			update_position( response.coords.lat, response.coords.lng, 0 );
		} );

	});

	var map_initialized = false;
	var address = jQuery('#listing-address').val();
	var lat = jQuery('input[name="lat"]').val();
	var lng = jQuery('input[name="lng"]').val();

	if ( address != '' && ( lat == 0 && lng == 0 ) )
		update_map(jQuery.noop);

	if ( lat != 0 && lng != 0 )
		update_position( lat, lng, 0 );

	function update_map( callback ) {
		if ( typeof ajaxurl === 'undefined' ) {
			return setTimeout('update_map( callback )', 500);
		}

		if ( !map_initialized ) {
			var lat = jQuery('input[name="lat"]').val();
			var lng = jQuery('input[name="lng"]').val();
			return map_init( lat, lng );
		}

		jQuery.getJSON( ajaxurl, {
			action: 'vantage_single_listing_geocode',
			address: jQuery('#listing-address').val(),
		}, function(response) {
			if( response.address ) {
				jQuery('#listing-address').val( response.address );
				jQuery('input[name="lat"]').val( response.coords.lat );
				jQuery('input[name="lng"]').val( response.coords.lng );
				update_position( response.coords.lat, response.coords.lng, 0 );
			}
		} );

	}

}

function quickEditListing() {

	if(typeof inlineEditPost === 'undefined') return;

	var _edit = inlineEditPost.edit;
	inlineEditPost.edit = function( id ) {

		var args = [].slice.call( arguments );
		_edit.apply( this, args );
		
		if ( typeof( id ) == 'object' ) {
			id = this.getId( id );
		}

		if ( this.type == 'post' ) {
			var editRow = jQuery( '#edit-' + id ), postRow = jQuery( '#post-'+id );
			
			// get the existing values
			var listing_claimable = ( 1 == jQuery( 'input[name="listing_claimable"]', postRow ).val() ? true : false );

			// set the values in the quick-editor
			jQuery( ':input[name="listing_claimable"]', editRow ).attr( 'checked', listing_claimable );
		}
	};
}

// Ensure inlineEditPost.edit isn't patched until it's defined
if ( typeof inlineEditPost !== 'undefined' ) {
	quickEditListing();
} else {
	jQuery( quickEditListing );
}
