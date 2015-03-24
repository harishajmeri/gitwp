<?php
/*
Template Name: Add New Listing
*/


// grabs the user info and puts into vars
global $current_user, $app_abbr;

?>


<div class="content">

	<div class="content_botbg">

		<div class="content_res">

			<!-- full block -->
			<div class="shadowblock_out">

				<div class="shadowblock">

					<?php
						// check and make sure the form was submitted from step1 and the session value exists
						if( isset($_POST['step1']) ) {

							include_once( get_template_directory() . '/includes/forms/step2.php' );

						} elseif( isset($_POST['step2']) ) {

							include_once( get_template_directory() . '/includes/forms/step3.php' );

						} else {

							// create a unique ID for this new ad order
							// uniqid requires a param for php 4.3 or earlier. added for 3.0.1
							$order_id  = uniqid(rand(10,1000), false);
							include_once( get_template_directory() . '/includes/forms/step1.php' );

						}
					?>

				</div><!-- /shadowblock -->

			</div><!-- /shadowblock_out -->

			<div class="clr"></div>

		</div><!-- /content_res -->

	</div><!-- /content_botbg -->

</div><!-- /content -->
