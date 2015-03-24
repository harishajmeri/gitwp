/**
 * Admin jQuery functions
 * Written by AppThemes
 *
 * http://www.appthemes.com
 *
 * Built for use with the jQuery library
 *
 *
 */


jQuery(document).ready(function($) {

	/* initialize the tooltip feature */
	$("td.titledesc a").easyTooltip();

	/* admin option pages tabs */
	$("div#tabs-wrap").tabs( {
		fx: {opacity: 'toggle', duration: 200},
		show: function() {
			$('div#tabs-wrap').tabs('option', 'selected');
		}
	});

	/* strip out all the auto classes since they create a conflict with the calendar */
	$('#tabs-wrap').removeClass('ui-tabs ui-widget ui-widget-content ui-corner-all');
	$('ul.ui-tabs-nav').removeClass('ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all');
	$('div#tabs-wrap div').removeClass('ui-tabs-panel ui-widget-content ui-corner-bottom');

	/* clear text field, hide image preview */
	$(".delete_button").click(function(el) {
		var id = $(this).attr("rel");
		$("#" + id).val("");
		$("#" + id + "_image img").hide();
	});


});


