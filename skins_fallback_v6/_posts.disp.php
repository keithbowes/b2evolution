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
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ------------------------- "Item List" CONTAINER EMBEDDED HERE --------------------------
// Display container contents:
widget_container( 'item_list', array(
	// The following (optional) params will be used as defaults for widgets included in this container:
	'container_display_if_empty' => false, // If no widget, don't display container at all
	// This will enclose each widget in a block:
	'block_start'           => '<div class="evo_widget $wi_class$">',
	'block_end'             => '</div>',
	// This will enclose the title of each widget:
	'block_title_start'     => '<h3>',
	'block_title_end'       => '</h3>',
	// The following params will be used as default for widgets
	'widget_coll_item_list_pages_params' => array(
		'block_start'              => '<div class="center"><ul class="pagination">',
		'block_end'                => '</ul></div>',
		'page_item_before'         => '<li>',
		'page_item_after'          => '</li>',
		'page_item_current_before' => '<li class="active">',
		'page_item_current_after'  => '</li>',
		'page_current_template'    => '<span>$page_num$</span>',
		'prev_text'                => '<i class="fa fa-angle-double-left"></i>',
		'next_text'                => '<i class="fa fa-angle-double-right"></i>',
	)
) );
// ----------------------------- END OF "Item List" CONTAINER -----------------------------
/* To be removed. Replaced by Item Next Previous widget in Item Single Header container:
// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
mainlist_page_links( array(
		'block_start'           => '<div class="center"><ul class="pagination">',
		'block_end'             => '</ul></div>',
		'page_item_before'      => '<li>',
		'page_item_after'       => '</li>',
		'page_item_current_before' => '<li class="active">',
		'page_item_current_after'  => '</li>',
		'page_current_template' => '<span>$page_num$</span>',
		'prev_text'             => '<i class="fa fa-angle-double-left"></i>',
		'next_text'             => '<i class="fa fa-angle-double-right"></i>',
	) );
// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------
*/
// --------------------------------- START OF POSTS -------------------------------------
// Display message if no post:
display_if_empty();

while( mainlist_get_item() )
{ // For each blog post, do everything below up to the closing curly brace "}"

	// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
	skin_include( '_item_block.inc.php', array_merge( array(
			'content_mode' => 'auto', // 'auto' will auto select depending on $disp-detail
		), $params ) );
	// ----------------------------END ITEM BLOCK  ----------------------------

} // ---------------------------------- END OF POSTS ------------------------------------

// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
mainlist_page_links( array(
		'block_start'           => '<div class="center"><ul class="pagination">',
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

?>