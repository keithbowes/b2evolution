<?php
/**
 * This is the site header include template.
 *
 * If enabled, this will be included at the top of all skins to provide a common identity and site wide navigation.
 * NOTE: each skin is responsible for calling siteskin_include( '_site_body_header.inc.php' );
 *
 * @package foyer
 * @subpackage custom_site_skin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $baseurl, $Settings, $Blog, $disp, $site_Skin;

$notification_logo_file_ID = intval( $Settings->get( 'notification_logo_file_ID' ) );
if( $notification_logo_file_ID > 0 &&
    ( $FileCache = & get_FileCache() ) &&
    ( $File = $FileCache->get_by_ID( $notification_logo_file_ID, false ) ) &&
    $File->is_image() )
{	// Display site logo image if the file exists in DB and it is an image:
	$site_title = $Settings->get( 'notification_long_name' ) != '' ? ' title="'.$Settings->dget( 'notification_long_name', 'htmlattr' ).'"' : '';
	$site_name_text = '<img src="'.$File->get_url().'" alt="'.$Settings->dget( 'notification_short_name', 'htmlattr' ).'"'.$site_title.' />';
	$site_title_class = ' swhead_logo';
	$site_has_logo_file = true;
}
else
{	// Display only short site name if the logo file cannot be used by some reason above:
	$site_name_text = $Settings->get( 'notification_short_name' );
	$site_title_class = '';
	$site_has_logo_file = false;
}
?>

<div id="evo_site_header" class="swhead_wrapper">

	<div class="swhead_menus">
		<div class="container-fluid level1">

			<nav>
				<?php
					// ------------------------- "Right Navigation" CONTAINER EMBEDDED HERE --------------------------
					widget_container( 'right_navigation', array(
							// The following params will be used as defaults for widgets included in this container:
							'container_display_if_empty' => false, // If no widget, don't display container at all
							'container_start'     => '<div class="pull-right evo_container $wico_class$">',
							'container_end'       => '</div>',
							'block_start'         => '',
							'block_end'           => '',
							'block_display_title' => false,
							'list_start'          => '',
							'list_end'            => '',
							'item_start'          => '',
							'item_end'            => '',
							'item_selected_start' => '',
							'item_selected_end'   => '',
							'link_selected_class' => 'btn btn-default active btn-sm ',
							'link_default_class'  => 'btn btn-default btn-sm ',
							'link_text_myprofile' => '$login$',
						) );
					// ----------------------------- END OF "Right Navigation" CONTAINER -----------------------------
				?>

				<?php if( $site_has_logo_file ) { ?>
				<div class="pull-left swhead_sitename<?php echo $site_title_class; ?>">
					<a href="<?php echo $baseurl; ?>"><?php echo $site_name_text; ?></a>
				</div>
				<?php } ?>

				<ul class="nav nav-tabs pull-left">
<?php
				if( ! $site_has_logo_file )
				{	// Display site name:
?>
					<li class="swhead_sitename no_logo<?php echo $site_title_class; ?>">
						<a href="<?php echo $baseurl; ?>"><?php echo $site_name_text; ?></a>
					</li>
<?php
				}

			if( ( $header_tabs = $site_Skin->get_header_tabs() ) !== false )
			{	// Display the grouped header tabs:
				foreach( $header_tabs as $s => $header_tab )
				{	// Display level 0 tabs:
?>
					<li<?php echo $site_Skin->get_header_tab_attr_class( $header_tab, $s ); ?>>
						<a href="<?php echo $header_tab['url']; ?>"<?php echo empty( $header_tab['rel'] ) ? '' : ' rel="'.$header_tab['rel'].'"'; ?>><?php echo $header_tab['name']; ?></a>
					</li>
<?php
				}
			}
			else
			{	// Display not grouped header tabs:

				// --------------------------------- START OF COLLECTION LIST --------------------------------
				// Call widget directly (without container):
				skin_widget( array(
									// CODE for the widget:
									'widget' => 'colls_list_public',
									// Optional display params
									'block_start' => '',
									'block_end' => '',
									'block_display_title' => false,
									'list_start' => '',
									'list_end' => '',
									'item_start' => '<li>',
									'item_end' => '</li>',
									'item_selected_start' => '<li class="active">',
									'item_selected_end' => '</li>',
									'link_selected_class' => 'active',
									'link_default_class' => '',
							) );
				// ---------------------------------- END OF COLLECTION LIST ---------------------------------

				if( $site_Skin->get_info_coll_ID() > 0 )
				{	// We have a collection for shared content blocks:
					// --------------------------------- START OF PAGES LIST --------------------------------
					// Call widget directly (without container):
					skin_widget( array(
									// CODE for the widget:
									'widget' => 'coll_page_list',
									// Optional display params
									'block_start' => '',
									'block_end' => '',
									'block_display_title' => false,
									'list_start' => '',
									'list_end' => '',
									'item_start' => '<li>',
									'item_end' => '</li>',
									'item_selected_start' => '<li class="active">',
									'item_selected_end' => '</li>',
									'link_selected_class' => 'active',
									'link_default_class' => '',
									'blog_ID' => $site_Skin->get_info_coll_ID(),
									'item_group_by' => 'none',
									'order_by' => 'order',		// Order (as explicitly specified)
							) );
					// ---------------------------------- END OF PAGES LIST ---------------------------------
				}

				// --------------------------------- START OF CONTACT LINK --------------------------------
				// Call widget directly (without container):
				skin_widget( array(
									// CODE for the widget:
									'widget' => 'basic_menu_link',
									// Optional display params
									'block_start' => '',
									'block_end' => '',
									'block_display_title' => false,
									'list_start' => '',
									'list_end' => '',
									'item_start' => '<li>',
									'item_end' => '</li>',
									'item_selected_start' => '<li class="active">',
									'item_selected_end' => '</li>',
									'link_selected_class' => 'active',
									'link_default_class' => '',
									'link_type' => 'ownercontact',
							) );
				// --------------------------------- END OF CONTACT LINK --------------------------------
			}
?>
				</ul>
			</nav>

		</div><?php // END OF <div class="container-fluid level1"> ?>

<?php
if( $site_Skin->has_sub_menus() )
{	// Display sub menus of the selected level 0 tab only when at least two exist:
?>
<div class="container-fluid level2">
	<nav>
		<ul class="nav nav-pills">
<?php
	foreach( $header_tabs[ $site_Skin->header_tab_active ]['items'] as $menu_item )
	{
		if( is_array( $menu_item ) )
		{	// Display menu item for collection:
?>
			<li<?php echo $site_Skin->get_header_tab_attr_class( $menu_item ); ?>>
				<a href="<?php echo $menu_item['url']; ?>"<?php echo empty( $menu_item['rel'] ) ? '' : ' rel="'.$menu_item['rel'].'"'; ?>><?php echo $menu_item['name']; ?></a>
			</li>
<?php
		}
		elseif( $menu_item == 'pages' )
		{	// Display menu item for Pages of the info/shared collection:
			// --------------------------------- START OF PAGES LIST --------------------------------
			// Call widget directly (without container):
			skin_widget( array(
							// CODE for the widget:
							'widget' => 'coll_page_list',
							// Optional display params
							'block_start' => '',
							'block_end' => '',
							'block_display_title' => false,
							'list_start' => '',
							'list_end' => '',
							'item_start' => '<li>',
							'item_end' => '</li>',
							'item_selected_start' => '<li class="active">',
							'item_selected_end' => '</li>',
							'blog_ID' => $site_Skin->get_info_coll_ID(),
							'item_group_by' => 'none',
							'order_by' => 'order',		// Order (as explicitly specified)
					) );
			// ---------------------------------- END OF PAGES LIST ---------------------------------
		}
	}
?>
		</ul>
	</nav>
</div><?php // END OF <div class="container-fluid level2"> ?>
<?php
}
?>

	</div><?php // END OF <div class="swhead_menus"> ?>
</div><?php // END OF <div id="evo_site_header"> ?>

<?php if( $site_Skin->get_setting( 'back_to_top_button' ) )
{ // Check if "Back to Top" button is enabled
?>
<a class="btn btn-primary slide-top<?php echo ( show_toolbar() ? ' slide-top-toolbar' : '' ).( $site_Skin->get_setting( 'fixed_header' ) ? ' slide-top-fixed-header' : '' ); ?>"><i class="fa fa-angle-double-up"></i></a>
</script>
<?php
expose_var_to_js( 'evo_init_scroll_to_top', true );
}
?>
