<?php
/**
 * This is the main/default page template for the "bootstrap_manual" skin.
 *
 * This skin only uses one single template which includes most of its features.
 * It will also rely on default includes for specific dispays (like the comment form).
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-development-primer}
 *
 * The main page template is used to display the blog when no specific page template is available
 * to handle the request (based on $disp).
 *
 * @package evoskins
 * @subpackage bootstrap_manual
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if( evo_version_compare( $app_version, '6.4' ) < 0 )
{ // Older skins (versions 2.x and above) should work on newer b2evo versions, but newer skins may not work on older b2evo versions.
	die( 'This skin is designed for b2evolution 6.4 and above. Please <a href="http://b2evolution.net/downloads/index.html">upgrade your b2evolution</a>.' );
}


global $bootstrap_manual_posts_text;

// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );


// -------------------------- HTML HEADER INCLUDED HERE --------------------------
skin_include( '_html_header.inc.php', array(
	'front_text' => '',
	'posts_text' => isset( $bootstrap_manual_posts_text ) ? $bootstrap_manual_posts_text : '',
	'flagged_text' => T_('Flagged pages'),
	'mustread_text' => T_('Must Read pages'),
) );
// -------------------------------- END OF HEADER --------------------------------


// ---------------------------- SITE HEADER INCLUDED HERE ----------------------------
// If site headers are enabled, they will be included here:
siteskin_include( '_site_body_header.inc.php', array(
	// Optional params here...
) );
// ------------------------------- END OF SITE HEADER --------------------------------

?>

<div class="<?php echo $Skin->get_layout_class( 'container' ); ?>">

<header id="header" class="row<?php echo $Settings->get( 'site_skins_enabled' ) ? ' site_skins' : ''; ?>">

	<?php
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
	?>

	<?php
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
	?>

</header><!-- .row -->

<?php
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
?>

<div class="row">

	<div class="<?php echo $Skin->get_layout_class( 'main_column' ); ?>">

		<main><!-- This is were a link like "Jump to main content" would land -->

		<!-- =================================== START OF MAIN AREA =================================== -->
		<?php
			if( ! in_array( $disp, array( 'login', 'lostpassword', 'register', 'activateinfo' ) ) )
			{ // Don't display the messages here because they are displayed inside wrapper to have the same width as form
				// ------------------------- MESSAGES GENERATED FROM ACTIONS -------------------------
				messages( array(
						'block_start' => '<div class="action_messages">',
						'block_end'   => '</div>',
					) );
				// --------------------------------- END OF MESSAGES ---------------------------------
			}
		?>

		<?php
			// ------------------------ TITLE FOR THE CURRENT REQUEST ------------------------
			request_title( array(
					'title_before'      => '<h1 class="page_title">',
					'title_after'       => '</h1>',
					'title_single_disp' => false,
					'title_page_disp'   => false,
					'title_widget_page_disp'  => false,
					'format'            => 'htmlbody',
					'category_text'     => '',
					'categories_text'   => '',
					'catdir_text'       => '',
					'front_text'        => '',
					'posts_text'        => '',
					'flagged_text'      => '',
					'mustread_text'     => '',
					'register_text'     => '',
					'login_text'        => '',
					'lostpassword_text' => '',
					'account_activation' => '',
					'msgform_text'      => '',
					'user_text'         => '',
					'users_text'        => '',
					'comments_text'     => '',
					'search_text'       => '',
					'display_edit_links'  => ( $disp == 'edit' ),
					'edit_links_template' => array(
						'before'              => '<span class="pull-right">',
						'after'               => '</span>',
						'advanced_link_class' => 'btn btn-info btn-sm',
						'close_link_class'    => 'btn btn-default btn-sm',
					),
				) );
			// ----------------------------- END OF REQUEST TITLE ----------------------------
?>


<?php
	// -------------- MAIN CONTENT TEMPLATE INCLUDED HERE (Based on $disp) --------------
	skin_include( '$disp$', $Skin->get_template( 'disp_params' ) );
	// Note: you can customize any of the sub templates included here by
	// copying the matching php file into your skin directory.
	// ------------------------- END OF MAIN CONTENT TEMPLATE ---------------------------
?>


		</main>

	</div><!-- .col -->

	<?php
	if( $Skin->is_side_navigation_visible() )
	{	// Display side (left and/or right) columns with navigation only for several pages:
	?>

		<?php
		if( $disp == 'single' )
		{	// Only for single disp:
		?>

		<!-- =================================== START OF SINGLE SIDEBAR =================================== -->
		<aside class="<?php echo $Skin->get_layout_class( 'right_column' ); ?>">

			<div id="evo_container__sidebar_single">
				<?php
					// ------------------------- "Sidebar Single" CONTAINER EMBEDDED HERE --------------------------
					// Display container contents:
					widget_container( 'sidebar_single', array(
							// The following (optional) params will be used as defaults for widgets included in this container:
							'container_display_if_empty' => false, // If no widget, don't display container at all
							// This will enclose each widget in a block:
							'block_start' => '<div class="panel panel-default evo_widget $wi_class$">',
							'block_end' => '</div>',
							// This will enclose the title of each widget:
							'block_title_start' => '<div class="panel-heading"><h4 class="panel-title">',
							'block_title_end' => '</h4></div>',
							// This will enclose the body of each widget:
							'block_body_start' => '<div class="panel-body">',
							'block_body_end' => '</div>',
							// If a widget displays a list, this will enclose that list:
							'list_start' => '<ul>',
							'list_end' => '</ul>',
							// This will enclose each item in a list:
							'item_start' => '<li>',
							'item_end' => '</li>',
							// This will enclose sub-lists in a list:
							'group_start' => '<ul>',
							'group_end' => '</ul>',
							// This will enclose (foot)notes:
							'notes_start' => '<div class="notes">',
							'notes_end' => '</div>',
							// Widget 'Search form':
							'search_class'         => 'compact_search_form',
							'search_input_before'  => '<div class="input-group">',
							'search_input_after'   => '',
							'search_submit_before' => '<span class="input-group-btn">',
							'search_submit_after'  => '</span></div>',
							// Widget 'Item Custom Fields':
							'custom_fields_table_start'                => '<div class="item_custom_fields">',
							'custom_fields_row_start'                  => '<div class="row"$row_attrs$>',
							'custom_fields_topleft_cell'               => '<div class="col-md-12 col-xs-6" style="border:none"></div>',
							'custom_fields_col_header_item'            => '<div class="$col_class$ col-md-12 col-xs-6 center" width="$col_width$"$col_attrs$>$item_link$$item_status$</div>',  // Note: we will also add reverse view later: 'custom_fields_col_header_field
							'custom_fields_row_header_field'           => '<div class="col-md-12 col-xs-6"><b>$field_title$$field_description_icon$:</b></div>',
							'custom_fields_item_status_template'       => '<div><div class="evo_status evo_status__$status$ badge" data-toggle="tooltip" data-placement="top" title="$tooltip_title$">$status_title$</div></div>',
							'custom_fields_description_icon_class'     => 'grey',
							'custom_fields_value_default'              => '<div class="col-md-12 col-xs-6"$data_cell_attrs$>$field_value$</div>',
							'custom_fields_value_difference_highlight' => '<div class="col-md-12 col-xs-6 bg-warning"$data_cell_attrs$>$field_value$</div>',
							'custom_fields_value_green'                => '<div class="col-md-12 col-xs-6 bg-success"$data_cell_attrs$>$field_value$</div>',
							'custom_fields_value_red'                  => '<div class="col-md-12 col-xs-6 bg-danger"$data_cell_attrs$>$field_value$</div>',
							'custom_fields_edit_link_cell'             => '<div class="col-md-12 col-xs-6 center"$edit_link_attrs$>$edit_link$</div>',
							'custom_fields_edit_link_class'            => 'btn btn-xs btn-default',
							'custom_fields_row_end'                    => '</div>',
							'custom_fields_table_end'                  => '</div>',
							// Separate template for separator fields:
							// (Possible to use templates for all field types: 'numeric', 'string', 'html', 'text', 'url', 'image', 'computed', 'separator')
							'custom_fields_separator_row_header_field' => '<div class="col-xs-12" colspan="$cols_count$"><b>$field_title$$field_description_icon$</b></div>',
						) );
					// ----------------------------- END OF "Sidebar Single" CONTAINER -----------------------------
				?>

			</div>

		</aside>

		<?php
		}
		?>

		<!-- =================================== START OF SIDEBAR =================================== -->
		<aside class="<?php echo $Skin->get_layout_class( 'left_column' ); ?>">

			<div id="evo_container__sidebar">

				<?php
					// <div data-spy="affix" data-offset-top="165" class="affix_block">
					// ------------------------- "Sidebar" CONTAINER EMBEDDED HERE --------------------------
					// Display container and contents:
					// Note: this container is designed to be a single <ul> list
					widget_container( 'sidebar', array(
							// The following (optional) params will be used as defaults for widgets included in this container:
							'container_display_if_empty' => false, // If no widget, don't display container at all
							// This will enclose each widget in a block:
							'block_start' => '<div class="panel panel-default evo_widget $wi_class$">',
							'block_end'   => '</div>',
							// This will enclose the title of each widget:
							'block_title_start' => '<div class="panel-heading"><h4 class="panel-title">',
							'block_title_end'   => '</h4></div>',
							// This will enclose the body of each widget:
							'block_body_start' => '<div class="panel-body">',
							'block_body_end'   => '</div>',
							// This will enclose (foot)notes:
							'notes_start' => '<div class="small text-muted">',
							'notes_end'   => '</div>',
							// Widget 'Search form':
							'search_class'         => 'compact_search_form',
							'search_input_before'  => '<div class="input-group">',
							'search_input_after'   => '',
							'search_submit_before' => '<span class="input-group-btn">',
							'search_submit_after'  => '</span></div>',
							// Widget 'Content Hierarchy':
							'item_before_opened'   => get_icon( 'collapse' ),
							'item_before_closed'   => get_icon( 'expand' ),
							'item_before_post'     => get_icon( 'file_message' ),
							'item_title_fields'    => 'short_title,title',
							'sorted'               => true
						) );
					// ----------------------------- END OF "Sidebar" CONTAINER -----------------------------
				?>

			</div><!-- DO WE NEED THIS DIV? -->

		</aside><!-- .col -->

		<!-- =================================== START OF SIDEBAR 2 =================================== -->
		<aside class="<?php echo $Skin->get_layout_class( 'right_column' ); ?>">

			<div id="evo_container__sidebar_2">

				<?php
					// <div data-spy="affix" data-offset-top="165" class="affix_block">
					// ------------------------- "Sidebar" CONTAINER EMBEDDED HERE --------------------------
					// Display container and contents:
					// Note: this container is designed to be a single <ul> list
					widget_container( 'sidebar_2', array(
							// The following (optional) params will be used as defaults for widgets included in this container:
							'container_display_if_empty' => false, // If no widget, don't display container at all
							// This will enclose each widget in a block:
							'block_start' => '<div class="panel panel-default evo_widget $wi_class$">',
							'block_end'   => '</div>',
							// This will enclose the title of each widget:
							'block_title_start' => '<div class="panel-heading"><h4 class="panel-title">',
							'block_title_end'   => '</h4></div>',
							// This will enclose the body of each widget:
							'block_body_start' => '<div class="panel-body">',
							'block_body_end'   => '</div>',
							// This will enclose (foot)notes:
							'notes_start' => '<div class="small text-muted">',
							'notes_end'   => '</div>',
							// Widget 'Search form':
							'search_class'         => 'compact_search_form',
							'search_input_before'  => '<div class="input-group">',
							'search_input_after'   => '',
							'search_submit_before' => '<span class="input-group-btn">',
							'search_submit_after'  => '</span></div>',
							// Widget 'Content Hierarchy':
							'item_before_opened'   => get_icon( 'collapse' ),
							'item_before_closed'   => get_icon( 'expand' ),
							'item_before_post'     => get_icon( 'file_message' ),
							'item_title_fields'    => 'short_title,title',
							'sorted'               => true
						) );
					// ----------------------------- END OF "Sidebar" CONTAINER -----------------------------
				?>

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

			</div><!-- DO WE NEED THIS DIV? -->

		</aside><!-- .col -->
	<?php } ?>

</div><!-- .row -->


<footer class="row">

	<!-- =================================== START OF FOOTER =================================== -->
	<div class="col-md-12">

			<?php
			// Display container and contents:
			widget_container( 'footer', array(
					// The following params will be used as defaults for widgets included in this container:
					'container_display_if_empty' => false, // If no widget, don't display container at all
					'container_start' => '<div class="evo_container $wico_class$ clearfix">', // Note: clearfix is because of Bootstraps' .cols
					'container_end'   => '</div>',
					'block_start'     => '<div class="evo_widget $wi_class$">',
					'block_end'       => '</div>',
				) );
			?>

		<p class="center">
			<?php
				// Display footer text (text can be edited in Blog Settings):
				$Blog->footer_text( array(
						'before' => '',
						'after'  => ' &bull; ',
					) );
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
						'before'      => ' ',
						'after'       => ' ',
						'text'        => T_('Help'),
					) );
			?>

			<?php
				// Display additional credits:
				// If you can add your own credits without removing the defaults, you'll be very cool :))
				// Please leave this at the bottom of the page to make sure your blog gets listed on b2evolution.net
				credits( array(
						'list_start'  => '&bull;',
						'list_end'    => ' ',
						'separator'   => '&bull;',
						'item_start'  => ' ',
						'item_end'    => ' ',
					) );
			?>
		</p>

	</div><!-- .col -->

</footer><!-- .row -->


</div><!-- .container -->

<?php
// ---------------------------- SITE FOOTER INCLUDED HERE ----------------------------
// If site footers are enabled, they will be included here:
siteskin_include( '_site_body_footer.inc.php' );
// ------------------------------- END OF SITE FOOTER --------------------------------


// ------------------------- HTML FOOTER INCLUDED HERE --------------------------
skin_include( '_html_footer.inc.php' );
// ------------------------------- END OF FOOTER --------------------------------
?>
