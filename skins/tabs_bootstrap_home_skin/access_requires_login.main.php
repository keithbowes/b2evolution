<?php
/**
 * This file is the template that displays "login required" for non logged-in users.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-development-primer}
 *
 * @package evoskins
 * @subpackage tabs_bootstrap_home_skin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $app_version, $disp, $Collection, $Blog;

if( evo_version_compare( $app_version, '6.4' ) < 0 )
{ // Older skins (versions 2.x and above) should work on newer b2evo versions, but newer skins may not work on older b2evo versions.
	die( 'This skin is designed for b2evolution 6.4 and above. Please <a href="http://b2evolution.net/downloads/index.html">upgrade your b2evolution</a>.' );
}

// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );


// -------------------------- HTML HEADER INCLUDED HERE --------------------------
skin_include( '_html_header.inc.php', array(
	'body_class' => 'pictured',
) );
// -------------------------------- END OF HEADER --------------------------------


// ---------------------------- SITE HEADER INCLUDED HERE ----------------------------
// If site headers are enabled, they will be included here:
siteskin_include( '_site_body_header.inc.php' );
// ------------------------------- END OF SITE HEADER --------------------------------

// Display a picture from skin setting as background image
$FileCache = & get_FileCache();
$bg_File = NULL;
if( $bg_File_ID = $Skin->get_setting( 'front_bg_image_file_ID' ) )
{
	$bg_File = & $FileCache->get_by_ID( $bg_File_ID, false, false );
}
echo '<div class="evo_pictured_layout">';
if( $bg_File && $bg_File->exists() )
{ // If it exists in media folder
	echo '<img class="evo_pictured__image" src="'.$bg_File->get_url().'" />';
}

?>


<div class="container main_page_wrapper">

<header class="row">

		<?php
		if( $Skin->show_container_when_access_denied( 'page_top' ) )
		{	// Display 'Page Top' widget container:
			// ------------------------- "Page Top" CONTAINER EMBEDDED HERE --------------------------
			// Display container and contents:
			widget_container( 'page_top', array(
					// The following params will be used as defaults for widgets included in this container:
					'container_display_if_empty' => true, // Display container anyway even if no widget
					'container_start'     => '<div class="col-xs-12 col-sm-12 col-md-4 col-md-push-8"><div class="evo_container $wico_class$">',
					'container_end'       => '</div></div>',
					'block_start'         => '<div class="evo_widget $wi_class$">',
					'block_end'           => '</div>',
					'block_display_title' => false,
					'list_start'          => '<ul>',
					'list_end'            => '</ul>',
					'item_start'          => '<li>',
					'item_end'            => '</li>',
				) );
			// ----------------------------- END OF "Page Top" CONTAINER -----------------------------
		} ?>

		<?php
		if( $Skin->show_container_when_access_denied( 'header' ) )
		{	// Display 'Header' widget container:
			// ------------------------- "Header" CONTAINER EMBEDDED HERE --------------------------
			// Display container and contents:
			widget_container( 'header', array(
					// The following params will be used as defaults for widgets included in this container:
					'container_display_if_empty' => true, // Display container anyway even if no widget
					'container_start'   => '<div class="col-xs-12 col-sm-12 col-md-8 col-md-pull-4"><div class="evo_container $wico_class$">',
					'container_end'     => '</div></div>',
					'block_start'       => '<div class="evo_widget $wi_class$">',
					'block_end'         => '</div>',
					'block_title_start' => '<h1>',
					'block_title_end'   => '</h1>',
				) );
			// ----------------------------- END OF "Header" CONTAINER -----------------------------
		} ?>

</header><!-- .row -->

<?php
if( $Skin->show_container_when_access_denied( 'menu' ) )
{	// Display 'Menu' widget container:

			// ------------------------- "Menu" CONTAINER EMBEDDED HERE --------------------------
			// Display container and contents:
			// Note: this container is designed to be a single <ul> list
			widget_container( 'menu', array(
					// The following params will be used as defaults for widgets included in this container:
					'container_display_if_empty' => false, // If no widget, don't display container at all
					'container_start'     => '<nav class="row"><div class="col-md-12"><ul class="nav nav-tabs evo_container $wico_class$">',
					'container_end'       => '</ul></div></nav>',
					'block_start'         => '',
					'block_end'           => '',
					'block_display_title' => false,
					'list_start'          => '',
					'list_end'            => '',
					'item_start'          => '<li class="evo_widget $wi_class$">',
					'item_end'            => '</li>',
					'item_selected_start' => '<li class="active evo_widget $wi_class$">',
					'item_selected_end'   => '</li>',
					'item_title_before'   => '',
					'item_title_after'    => '',
				) );
			// ----------------------------- END OF "Menu" CONTAINER -----------------------------

} ?>

<div class="row">
	<div class="col-md-12">
		<main><!-- This is were a link like "Jump to main content" would land -->

		<!-- ================================= START OF MAIN AREA ================================== -->

		<?php
			// -------------- MAIN CONTENT TEMPLATE INCLUDED HERE (Based on $disp) --------------
			skin_include( '$disp$' );
			// Note: you can customize any of the sub templates included here by
			// copying the matching php file into your skin directory.
			// ------------------------- END OF MAIN CONTENT TEMPLATE ---------------------------
		?>

		</main>

	</div><!-- .col -->

</div><!-- .row -->

</div><!-- .container -->

</div><!-- .evo_pictured_layout -->


<!-- =================================== START OF SECONDARY AREA =================================== -->
<section class="secondary_area"><!-- white background -->
<div class="container">

	<div class="row">

		<footer class="col-md-12">

			<?php
			if( $Skin->show_container_when_access_denied( 'footer' ) )
			{	// Display 'Footer' widget container:
				// ------------------------- "Footer" CONTAINER EMBEDDED HERE --------------------------
				widget_container( 'footer', array(
						// The following params will be used as defaults for widgets included in this container:
						'container_display_if_empty' => false, // If no widget, don't display container at all
						'container_start' => '<div class="evo_container $wico_class$ clearfix">', // Note: clearfix is because of Bootstraps' .cols
						'container_end'   => '</div>',
						'block_start'     => '<div class="evo_widget $wi_class$">',
						'block_end'       => '</div>',
					) );
				// ----------------------------- END OF "Footer" CONTAINER -----------------------------
			} ?>

			<p class="center">
			<?php
				// Display footer text (text can be edited in Blog Settings):
				$Blog->footer_text( array(
						'before' => '',
						'after'  => ' &bull; ',
					) );

			// TODO: dh> provide a default class for pTyp, too. Should be a name and not the ityp_ID though..?!
			?>

			<?php
				// Display a link to contact the owner of this blog (if owner accepts messages):
				$Blog->contact_link( array(
						'before' => '',
						'after'  => ' &bull; ',
						'text'   => T_('Contact'),
						'title'  => T_('Send a message to the owner of this blog...'),
					) );
				// Display a link to help page:
				$Blog->help_link( array(
						'before' => ' ',
						'after'  => ' ',
						'text'   => T_('Help'),
					) );
			?>

			<?php
				// Display additional credits:
				// If you can add your own credits without removing the defaults, you'll be very cool :))
				// Please leave this at the bottom of the page to make sure your blog gets listed on b2evolution.net
				credits( array(
						'list_start' => '&bull;',
						'list_end'   => ' ',
						'separator'  => '&bull;',
						'item_start' => ' ',
						'item_end'   => ' ',
					) );
			?>
			</p>

			<?php
				// Please help us promote b2evolution and leave this logo on your blog:
				powered_by( array(
						'block_start' => '<div class="powered_by">',
						'block_end'   => '</div>',
						// Check /rsc/img/ for other possible images -- Don't forget to change or remove width & height too
						'img_url'     => '$rsc$img/powered-by-b2evolution-120t.gif',
						'img_width'   => 120,
						'img_height'  => 32,
					) );
			?>

		</footer><!-- .col -->

	</div><!-- .row -->


</div><!-- .container -->

</section><!-- .secondary_area -->


<?php
// ---------------------------- SITE FOOTER INCLUDED HERE ----------------------------
// If site footers are enabled, they will be included here:
siteskin_include( '_site_body_footer.inc.php' );
// ------------------------------- END OF SITE FOOTER --------------------------------


// ------------------------- HTML FOOTER INCLUDED HERE --------------------------
skin_include( '_html_footer.inc.php' );
// ------------------------------- END OF FOOTER --------------------------------
?>
