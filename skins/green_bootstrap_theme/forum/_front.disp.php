<?php
/**
 * This is the template that displays the links to the latest comments for a blog
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display a feedback, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=comments
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage bootstrap_forums
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $number_of_posts_in_cat, $cat, $legend_icons, $Item;

$params = array(
		'item_class' => 'jumbotron evo_content_block evo_post',
		'intro_mode' => 'normal', // Intro posts will be displayed in normal mode
	);

// Breadcrumbs
skin_widget( array(
		// CODE for the widget:
		'widget' => 'breadcrumb_path',
		// Optional display params
		'block_start'           => '<ol class="breadcrumb">',
		'block_end'             => '</ol><div class="clear"></div>',
		'separator'             => '',
		'item_mask'             => '<li><a href="$url$">$title$</a></li>',
		'item_logo_mask'        => '<li>$logo$ <a href="$url$">$title$</a></li>',
		'item_active_logo_mask' => '<li class="active">$logo$ $title$</li>',
		'item_active_mask'      => '<li class="active">$title$</li>',
		'coll_logo_size'        => 'fit-128x16',
	) );

if( ! is_array( $legend_icons ) )
{ // Init this array only first time
	$legend_icons = array();
}

// ------------------------------- START OF POSTS ASSIGNED TO CURRENT USER -------------------------------
if( $Blog->get_setting( 'use_workflow' ) &&
    check_user_perm( 'blog_can_be_assignee', 'edit', false, $Blog->ID ) )
{	// Only if current User can be assigned to tasks of the current Collection:
	$assigned_ItemList = new ItemList2( $Blog, NULL, NULL, 15, 'ItemCache', 'assigned_' );
	$assigned_ItemList->set_filters( array(
			'assignees' => $current_User->ID,
			'orderby'   => 'priority,last_touched_ts',
			'order'     => 'ASC,DESC',
			'page'      => param( 'assigned_paged', 'integer', 1 ),
			'statuses'  => param( 'status', '/^(-|-[0-9]+|[0-9]+)(,[0-9]+)*$/', '' ),
		), false );
	$assigned_ItemList->query();
?>
<div class="panel panel-default forums_list">
<?php
	// Display header for list of assigned tasks for current User:
	$Skin->display_posts_list_header( T_('Assigned to me') );

	if( $assigned_ItemList->result_num_rows > 0 )
	{	// Display panel with assigned posts if at least one is found:
		while( $Item = $assigned_ItemList->get_item() )
		{
			// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
			skin_include( '_item_list.inc.php', array(
					'content_mode'  => 'auto', // 'auto' will auto select depending on $disp-detail
					'image_size'    => 'fit-1280x720',
				) );
			// ----------------------------END ITEM BLOCK  ----------------------------
		}

		// Display pagination for assigned posts:
		$assigned_ItemList->page_links(  array(
				'block_start'           => '<div class="panel-body comments_link__pagination" style="margin-bottom:0"><ul class="pagination">',
				'block_end'             => '</ul></div>',
				'page_current_template' => '<span>$page_num$</span>',
				'page_item_before'      => '<li>',
				'page_item_after'       => '</li>',
				'page_item_current_before' => '<li class="active">',
				'page_item_current_after'  => '</li>',
				'prev_text'             => '<i class="fa fa-angle-double-left"></i>',
				'next_text'             => '<i class="fa fa-angle-double-right"></i>',
			) );
	}
	else
	{	// No assigned tasks:
		echo '<div class="ft_no_post">'.T_('No assigned tasks.').'</div>';
	}
?>
</div>
<?php
}
// -------------------------------- END OF POSTS ASSIGNED TO CURRENT USER --------------------------------

// ------------------------------- START OF INTRO-FRONT POST -------------------------------
if( $Item = & get_featured_Item( 'front' ) )
{ // We have a intro-front post to display:
?>
<div id="<?php $Item->anchor_id() ?>" class="<?php $Item->div_classes( $params ) ?>" lang="<?php $Item->lang() ?>">

	<?php
	$Item->locale_temp_switch(); // Temporarily switch to post locale (useful for multilingual blogs)

	$action_links = $Item->get_edit_link( array( // Link to backoffice for editing
			'before' => '',
			'after'  => '',
			'text'   => $Item->is_intro() ? get_icon( 'edit' ).' '.T_('Edit Intro') : '#',
			'class'  => button_class( 'text' ),
		) );
	if( $Item->status != 'published' )
	{
		$Item->format_status( array(
				'template' => '<div class="evo_status evo_status__$status$ badge pull-right" data-toggle="tooltip" data-placement="top" title="$tooltip_title$">$status_title$</div>',
			) );
	}
	$Item->title( array(
			'link_type'  => 'none',
			'before'     => '<div class="evo_post_title"><h1>',
			'after'      => '</h1><div class="'.button_class( 'group' ).'">'.$action_links.'</div></div>',
			'nav_target' => false,
		) );

	// ---------------------- POST CONTENT INCLUDED HERE ----------------------
	skin_include( '_item_content.inc.php', array_merge( $params, array( 'Item' => $Item ) ) );
	// Note: You can customize the default item content by copying the generic
	// /skins/_item_content.inc.php file into the current skin folder.
	// -------------------------- END OF POST CONTENT -------------------------

	locale_restore_previous();	// Restore previous locale (Blog locale)
	?>
</div>
<?php
// ------------------------------- END OF INTRO-FRONT POST -------------------------------
}

$ChapterCache = & get_ChapterCache();
$chapters = $ChapterCache->get_chapters( $Blog->ID, $cat, true );

if( count( $chapters ) > 0 )
{
?>
	<div class="panel panel-default forums_list front_panel">
<?php
	foreach( $chapters as $root_Chapter )
	{ // Loop through categories:
		if( $root_Chapter->meta )
		{ // Meta category
			$chapters_children = $root_Chapter->get_children( true );
?>
		<header class="panel-heading meta_category"><a href="<?php echo $root_Chapter->get_permanent_url(); ?>" class="forumlink"><?php echo $root_Chapter->dget( 'name' ); ?></a></header>
<?php
		}
		else
		{ // Simple category with posts
			$chapters_children = array( $root_Chapter );
		}
?>
		<section class="table table-hover">
<?php
		foreach( $chapters_children as $Chapter )
		{ // Loop through categories:
			if( $Chapter->lock )
			{ // Set icon for locked chapter
				$chapter_icon = 'fa-lock big';
				$chapter_icon_title = T_('This forum is locked: you cannot post, reply to, or edit topics.');
				$legend_icons['forum_locked'] = 1;
			}
			else
			{ // Set icon for unlocked chapter
				$chapter_icon = 'fa-folder big';
				$chapter_icon_title = T_('Forum (contains several topics)');
				$legend_icons['forum_default'] = 1;
			}
?>
		<article class="container group_row">
			<div class="ft_status__ft_title col-lg-8 col-md-7 col-sm-7 col-xs-6">
				<div class="ft_status"><i class="icon fa <?php echo $chapter_icon; ?>" title="<?php echo $chapter_icon_title; ?>"></i></div>
				<div class="ft_title ellipsis">
					<a href="<?php echo $Chapter->get_permanent_url(); ?>" class="forumlink"><?php echo $Chapter->dget( 'name' ); ?></a>
					<?php
					if( $Chapter->dget( 'description' ) != '' )
					{
						echo '<br /><span class="ft_desc ellipsis">'.$Chapter->dget( 'description' ).'</span>';
					}

					$sorted_sub_chapters = $Chapter->get_children( true );
					if( count( $sorted_sub_chapters ) > 0 )
					{ // Subforums exist
						echo '<div class="subcats ellipsis">';
						echo T_('Subforums').': ';
						$cc = 0;
						foreach( $sorted_sub_chapters as $child_Chapter )
						{ // Display subforum
							echo '<a href="'.$child_Chapter->get_permanent_url().'" class="forumlink">'.$child_Chapter->get('name').'</a>';
							echo $cc < count( $sorted_sub_chapters ) - 1 ? ', ' : '';
							$cc++;
						}
						echo '</div>';
					}
					?>
				</div>
			</div>
			<div class="ft_count col-lg-1 col-md-1 col-sm-1 col-xs-2">
				<?php printf( T_('%s topics'), '<div><a href="'. $Chapter->get_permanent_url() .'">'.get_postcount_in_category( $Chapter->ID ).'</a></div>' ); ?>
			</div>
			<div class="ft_count second_of_class col-lg-1 col-md-1 col-sm-1 col-xs-2"><?php printf( T_('%s replies'), '<div><a href="'. $Chapter->get_permanent_url() .'">'.get_commentcount_in_category( $Chapter->ID ).'</a></div>' ); ?></div>
			<div class="ft_date col-lg-2 col-md-3 col-sm-3"><?php echo $Chapter->get_last_touched_date( locale_extdatefmt().'<\b\r>'.locale_shorttimefmt() ); ?></div>
			<!-- Apply this on XS size -->
			<div class="ft_date_shrinked col-xs-2"><?php echo $Chapter->get_last_touched_date( locale_datefmt() ); ?></div>
		</article>
<?php
		}
?>
		</section>
<?php
	} // End of categories loop.
?>
	</div>
<?php
}
?>
