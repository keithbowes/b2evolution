<?php
/**
 * This is the template that displays the posts for a blog
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display the archive directory, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=posts
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage bootstrap_forums
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $number_of_posts_in_cat, $cat, $legend_icons, $tag;

if( ! is_array( $legend_icons ) )
{ // Init this array only first time
	$legend_icons = array();
}

// Get ID of single selected category:
$single_cat_ID = intval( $cat );

// Get IDs of several selected categories:
$multi_cat_IDs = get_param( 'cat_array' );

if( $single_cat_ID )
{
	$ChapterCache = & get_ChapterCache();
	$current_Chapter = & $ChapterCache->get_by_ID( $single_cat_ID, false, false );
}

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
		'suffix_text'           => empty( $single_cat_ID ) ? T_('Latest topics') : '',
		'coll_logo_size'        => 'fit-128x16',
	) );

// Display default title only for tag page without intro Item:
request_title( array(
		'title_before'      => '<h2 class="page_title">',
		'title_after'       => '</h2>',
		'format'            => 'htmlbody',
		'posts_text'        => ( isset( $tag ) && ! has_featured_Item() ? '#' : '' ),
	) );

// Go Grab the featured post:
if( ! in_array( $disp, array( 'single', 'page' ) ) &&
    $Item = & get_featured_Item( 'posts', NULL, false, ( isset( $tag ) || $single_cat_ID ? false : NULL ) ) )
{	// We have a intro post to display:
	$featured_item_ID = $Item->ID;
	// Use background position image of intro-post for background URL:
	$background_image_url = $Item->get_cover_image_url( 'background' );
	$intro_item_style = $background_image_url ? 'background-image: url("'.$background_image_url.'")' : '';
	// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
	skin_include( '_item_block_intro.inc.php', array(
			'content_mode'  => 'full', // We want regular "full" content, even in category browsing: i-e no excerpt or thumbnail
			'intro_mode'    => 'normal',	// Intro posts will be displayed in normal mode
			'item_class'    => 'well evo_intro_post'.( empty( $intro_item_style ) ? '' : ' evo_hasbgimg' ),
			'item_style'    => $intro_item_style,
			'Item'          => $Item,
		) );
	// ----------------------------END ITEM BLOCK  ----------------------------
}

if( $single_cat_ID )
{	// Display sub-chapters:

$ChapterCache = & get_ChapterCache();
$chapters = $ChapterCache->get_chapters( $Blog->ID, $single_cat_ID, true );

if( count( $chapters ) > 0 )
{
?>
	<div class="panel panel-default forums_list front_panel">
<?php
	$section_is_started = false;
	foreach( $chapters as $Chapter )
	{ // Loop through categories:
		if( $Chapter->meta )
		{ // Meta category
			$chapters_children = $Chapter->get_children( true );
			if( $section_is_started )
			{ // Close previous opened table
?>
<?php
				$section_is_started = false;
			}
?>
		<header class="panel-heading meta_category"><a href="<?php echo $Chapter->get_permanent_url(); ?>" class="forumlink"><?php echo $Chapter->dget( 'name' ); ?></a></header>
<?php
		}
		else
		{ // Simple category with posts
			$chapters_children = array( $Chapter );
		}

		if( ! $section_is_started )
		{
			$section_is_started = true;
?>
		<section class="table table-hover">
<?php
		}

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
				global $disp_detail;
				if( $disp_detail == 'posts-subcat' )
				{
					$chapter_icon_title = T_('Sub-forum (contains several topics)');
					$legend_icons['forum_sub'] = 1;
				}
				else
				{
					$chapter_icon_title = T_('Forum (contains several topics)');
					$legend_icons['forum_default'] = 1;
				}
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
					echo '<br /><span class="ft_desc">'.$Chapter->dget( 'description' ).'</span>';
				}

				$sorted_sub_chapters = $Chapter->get_children( true );
				if( count( $sorted_sub_chapters ) > 0 )
				{ // Subforums exist
					echo '<div class="subcats">';
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
			<div class="ft_count col-lg-1 col-md-1 col-sm-1 col-xs-2"><?php printf( T_('%s topics'), '<div><a href="'. $Chapter->get_permanent_url() .'">'.get_postcount_in_category( $Chapter->ID ).'</a></div>' ); ?></div>
			<div class="ft_count second_of_class col-lg-1 col-md-1 col-sm-1 col-xs-2"><?php printf( T_('%s replies'), '<div><a href="'. $Chapter->get_permanent_url() .'">'.get_commentcount_in_category( $Chapter->ID ).'</a></div>' ); ?></div>
			<div class="ft_date col-lg-2 col-md-3 col-sm-3"><?php echo $Chapter->get_last_touched_date( locale_extdatefmt().'<\b\r>'.locale_shorttimefmt() ); ?></div>
			<!-- Apply this on XS size -->
			<div class="ft_date_shrinked col-xs-2"><?php echo $Chapter->get_last_touched_date( locale_datefmt() ); ?></div>
		</article>
<?php
		}
	} // End of categories loop.
	if( $section_is_started )
	{
?>
		</section>
<?php
	}
?>
	</div>
<?php
}

}

// ---------------------------------- START OF POSTS ------------------------------------
if( isset( $MainList ) &&
    ( ! isset( $current_Chapter ) || ! $current_Chapter->meta ) && // Note: the meta categories cannot contain the posts
    ( empty( $single_cat_ID ) || // disp=posts List all posts
      ! empty( $multi_cat_IDs ) || // Filter for several categories
      isset( $current_Chapter ) ) // Posts of the current viewed category ($disp_detail = posts-cat)
  )
{
	echo !empty( $chapters ) ? '<br />' : '';
?>
<div class="panel panel-default forums_list">
	<?php
	if( $single_cat_ID )
	{	// Display category title:
		$ChapterCache = & get_ChapterCache();
		if( $category = & $ChapterCache->get_by_ID( $single_cat_ID ) )
		{	// Display category title:
			$Skin->display_posts_list_header( '<h3 class="panel-title">'.$category->get( 'name' ).'</h3>', array(
					'actions' => $Skin->get_post_button( $single_cat_ID, NULL, array(
							'group_class'  => 'pull-right',
							'button_class' => 'btn-sm',
						) ),
				) );
		}
	}
	else
	{	// Display header for latest topics:
		$Skin->display_posts_list_header( T_('Latest topics') );
	}
	?>

	<section class="table table-hover">
<?php

if( $single_cat_ID )
{	// Go to grab only featured posts only on pages with defined category:
	while( $Item = & get_featured_Item( 'posts', NULL, false, true, false ) )
	{	// We have the featured posts to display:
		if( isset( $featured_item_ID ) && $featured_item_ID == $Item->ID )
		{	// Skip featured Item if it is already displayed above in intro style block:
			continue;
		}
		// ---------------------- ITEM LIST INCLUDED HERE ------------------------
		skin_include( '_item_list.inc.php', array(
				'Item'       => $Item,
				'intro_mode' => 'normal', // Intro posts will be displayed in normal mode
			) );
		// ----------------------------END ITEM LIST  ----------------------------
	}
}

if( $MainList->result_num_rows > 0 )
{
	while( mainlist_get_item() )
	{ // For each blog post, do everything below up to the closing curly brace "}"

		// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
		skin_include( '_item_list.inc.php', array(
				'content_mode' => 'auto',		// 'auto' will auto select depending on $disp-detail
				'image_size'   => 'fit-1280x720',
			) );
		// ----------------------------END ITEM BLOCK  ----------------------------
	}
}
else
{	// Display a message about no posts:
?>
<div class="ft_no_post">
	<?php echo isset( $current_Chapter ) ? T_('There is no topic in this forum yet.') : T_('No topics.'); ?>
</div>
<?php
}
?>
	</section>

	<div class="panel-body">
	<?php
		// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
		mainlist_page_links( array(
				'block_start'           => '<div class="comments_link__pagination"><ul class="pagination">',
				'block_end'             => '</ul></div>',
				'page_current_template' => '<span>$page_num$</span>',
				'page_item_before'      => '<li>',
				'page_item_after'       => '</li>',
				'page_item_current_before' => '<li class="active">',
				'page_item_current_after'  => '</li>',
				'prev_text'             => '<i class="fa fa-angle-double-left"></i>',
				'next_text'             => '<i class="fa fa-angle-double-right"></i>',
			) );
		// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------

		if( ! is_logged_in() && ! $Blog->get_setting( 'post_anonymous' ) )
		{	// Display a warning to log in or register before new post creating:
			$register_link = '';
			$login_link = '<a class="btn btn-primary btn-sm" href="'.get_login_url( 'cannot post' ).'">'.T_( 'Log in now!' ).'</a>';
			if( ( $Settings->get( 'newusers_canregister' ) == 'yes' ) && ( $Settings->get( 'registration_is_public' ) ) )
			{
				$register_link = '<a class="btn btn-primary btn-sm" href="'.get_user_register_url( NULL, 'reg to post' ).'">'.T_( 'Register now!' ).'</a>';
			}
			echo '<p class="alert alert-warning alert-item-new">';
			echo T_( 'In order to start a new topic' ).' '.$login_link.( ! empty( $register_link ) ? ' '.T_('or').' '.$register_link : '' );
			echo '</p>';
		}

		// Buttons to post/reply
		$Skin->display_post_button( $single_cat_ID );

		if( check_user_status( 'can_be_validated' ) )
		{	// Display a warning if current user cannot post a topic because he must activate account:
			global $Messages;
			$Messages->clear();
			$Messages->add( T_( 'You must activate your account before you can post a new topic.' )
				.' <a href="'.get_activate_info_url( NULL, '&amp;' ).'">'.T_( 'More info &raquo;' ).'</a>', 'warning' );
			$Messages->display();
		}
	?>
	</div>
</div>
<?php
} // ---------------------------------- END OF POSTS ------------------------------------
?>
