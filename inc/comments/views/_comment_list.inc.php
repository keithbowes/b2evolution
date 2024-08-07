<?php
/**
 * This file implements the comment list
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Comment
 */
global $Comment;
/**
 * @var CommentList
 */
global $CommentList;

global $AdminUI, $UserSettings;

// If rediret_to was not set, create new redirect
$redirect_to = param( 'redirect_to', 'url', regenerate_url( '', 'filter=restore', '', '&' ) );
$save_context = param( 'save_context', 'boolean', 'true' );
$show_comments = param( 'show_comments', 'string', 'all' );

$item_id = param( 'item_id', 'integer', 0 );
if( empty( $item_id ) )
{ // Try to get an item ID from param "p" that is used on edit post page
	$item_id = param( 'p', 'integer', 0 );
}
$currentpage = param( 'currentpage', 'integer', 1 );
$comments_number = param( 'comments_number', 'integer', 0 );

// Check if current comments list displays internal comments:
$is_meta_comments_list = ( isset( $CommentList->filters['types'] ) && in_array( 'meta', $CommentList->filters['types'] ) );

if( ! $is_meta_comments_list && $CommentList->total_rows > 0 )
{	// Allow to select ONLY normal comments(EXCLUDE internal comments) for action on item view page:
	global $blog, $admin_url;

	$Form = new Form( $admin_url );

	$Form->begin_form();
	$Form->hidden( 'ctrl', 'items' );
	$Form->hidden( 'blog', $blog );
	$Form->hidden( 'p', $item_id );
	$Form->hidden( 'page', $CommentList->page );
	$Form->add_crumb( 'comments' );
}

if( ( $item_id != 0 ) && ( $comments_number > 0 ) )
{ // Display a pagination:
	$comment_params = array_merge( $AdminUI->get_template( 'pagination' ), array( 'page_size' => $CommentList->limit ) );
	echo_comment_pages( $item_id, $currentpage, $comments_number, $comment_params );
}

if( $item_id > 0 )
{	// Don't display additional info when we are already viewing a selected post page:
	$display_meta_title = false;
}
else
{	// Display additional info of internal comment when no post page, e.g. on "Internal comments" tab:
	$display_meta_title = true;
}

// Check if mode "Threaded comments" is active to current filterset:
$threaded_comments_mode = ! empty( $CommentList->filters['threaded_comments'] );

if( $threaded_comments_mode )
{	// This is "Threaded comments" mode, Initialize global array to store replies:
	global $CommentReplies;
	$CommentReplies = array();

	if( ( get_param( 'reply_ID' ) > 0 ) &&
	    isset( $item_ID ) &&
	    ( $Comment = get_comment_from_session( 'preview', $comment_type ) ) &&
	    ( $Comment->item_ID == $item_ID ) )
	{	// Put a preview comment in array to display it in proper place:
		$CommentReplies[ $Comment->in_reply_to_cmt_ID ] = array( $Comment );
	}
}

// Flag vars to know the comments list has at least one comment to recycle or delete in order to display multiple buttons:
$comments_can_be_recycled = false;
$comments_can_be_deleted = false;

while( $Comment = & $CommentList->get_next() )
{ // Loop through comments:
	if( ( $show_comments == 'draft' ) && ( $Comment->get( 'status' ) != 'draft' ) )
	{ // if show only draft comments, and current comment status isn't draft, then continue with the next comment
		continue;
	}

	if( $threaded_comments_mode && $Comment->in_reply_to_cmt_ID > 0 )
	{	// Store the comment replies in a special array:
		if( !isset( $CommentReplies[ $Comment->in_reply_to_cmt_ID ] ) )
		{
			$CommentReplies[ $Comment->in_reply_to_cmt_ID ] = array();
		}
		$CommentReplies[ $Comment->in_reply_to_cmt_ID ][] = $Comment;
		// Skip dispay a comment reply here in order to dispay it after parent comment by function display_comment_replies():
		continue;
	}

	// Display a comment:
	echo_comment( $Comment, $redirect_to, $save_context, $Comment->get_inlist_order(), $display_meta_title );

	if( $threaded_comments_mode )
	{	// Display the comment replies:
		echo_comment_replies( $Comment->ID, array(
				'redirect_to'        => $redirect_to,
				'save_context'       => $save_context,
				'display_meta_title' => $display_meta_title,
			) );
	}

	if( ! $comments_can_be_recycled && $Comment->get( 'status' ) != 'trash' && check_user_perm( 'comment!CURSTATUS', 'delete', false, $Comment ) )
	{	// Set flag to know at least one comment from the current list can be recycled:
		$comments_can_be_recycled = true;
	}
	if( ! $comments_can_be_deleted && check_user_perm( 'comment!CURSTATUS', 'delete', false, $Comment ) )
	{	// Set flag to know at least one comment from the current list can be deleted:
		$comments_can_be_deleted = true;
	}
} //end of the loop, don't delete

if( ( $item_id != 0 ) && ( $comments_number > 0 ) )
{ // Display a pagination:
	$comment_params = array_merge( $AdminUI->get_template( 'pagination' ), array( 'page_size' => $CommentList->limit ) );
	echo_comment_pages( $item_id, $currentpage, $comments_number, $comment_params );
}

if( ! $is_meta_comments_list && $CommentList->total_rows > 0 )
{	// Allow to select ONLY normal comments(EXCLUDE internal comments) for action on item view page:
	echo T_('With checked comments').': ';

	// Display a button to change visibility of selected comments:
	$ItemCache = & get_ItemCache();
	$Item = & $ItemCache->get_by_ID( $item_id, false, false );
	$item_status = $Item ? $Item->get( 'status' ) : '';
	$Form->hidden( 'comment_status', $item_status );
	echo_comment_status_buttons( $Form, NULL, $item_status, 'comments_visibility' );
	echo_status_dropdown_button_js( 'comment' );

	if( $item_id > 0 && check_user_perm( 'blog_post_statuses', 'edit', false, $blog ) )
	{	// Display a button to create a post from selected comments:
		echo ' '.T_('or').' ';
		$Form->button( array( 'submit', 'actionArray[create_comments_post]', T_('Create new Post'), 'btn-warning' ) );
	}

	if( $comments_can_be_recycled || $comments_can_be_deleted )
	{	// Display buttons to recycle or delete the selected comments:
		echo ' '.T_('or').' ';
		if( $comments_can_be_recycled && $comments_can_be_deleted )
		{	// Group recycle and delete buttons:
			echo '<div class="btn-group">';
		}
		if( $comments_can_be_recycled )
		{	// Button to recycle the comments:
			$Form->button_input( array(
					'tag'   => 'button',
					'name'  => 'actionArray[recycle_comments]',
					'value' => get_icon( 'recycle' ).' '.T_('Recycle').'!',
					'class' => 'btn-danger'
				) );
		}
		if( $comments_can_be_deleted )
		{	// Button to delete the comments:
			$Form->button_input( array(
					'tag'     => 'button',
					'name'    => 'actionArray[delete_comments]',
					'value'   => get_icon( 'delete' ).' '.T_('Delete').'!',
					'class'   => 'btn-danger',
					'onclick' => 'return confirm( \''.TS_('You are about to delete the selected comments!\\nThis cannot be undone!').'\' )',
				) );
		}
		if( $comments_can_be_recycled && $comments_can_be_deleted )
		{	// End of group recycle and delete buttons:
			echo '</div>';
		}
	}

	$Form->end_form();
}

?>
