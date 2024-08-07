<?php
/**
 * This is sent to ((Users)) and/or ((Moderators)) to notify them that a new comment has been posted.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.html.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

global $admin_url, $Collection, $Blog, $Session;

// Default params:
$params = array_merge( array(
		'notify_full'    => false,
		'Comment'        => NULL,
		'Blog'           => NULL,
		'Item'           => NULL,
		'author_ID'      => NULL,
		'author_name'    => '',
		'notify_type'    => '',
		'recipient_User' => NULL,
		'is_new_comment' => true,
	), $params );


$Comment = $params['Comment'];
$Collection = $Blog = $params['Blog'];
$Item = $params['Item'];
$recipient_User = & $params['recipient_User'];

$author_name = empty( $params['author_ID'] ) ? $params['author_name'] : get_user_colored_login_link( $params['author_name'], array( 'use_style' => true, 'protocol' => 'http:', 'login_text' => 'name' ) );
if( is_null( $Comment ) )
{
	$author_type = empty( $params['author_ID'] ) ? '<span'.emailskin_style( '.label+.label_warning').'>'.T_('Visitor').'</span>' : '<span'.emailskin_style( '.label+.label_info' ).'>'.T_('Member').'</span>';
}
else
{
	$author_type = $Comment->get_author_label( array(
			'member_before'  => '<span'.emailskin_style( '.label+.label-info' ).'>',
			'member_after'   => '</span>',
			'visitor_before' => '<span'.emailskin_style( '.label+.label-warning' ).'>',
			'visitor_after'  => '</span>',
		) );
}
if( $params['notify_type'] == 'meta_comment' || $params['notify_type'] == 'meta_comment_mentioned' )
{ // Internal comment
	$info_text = T_( '%s posted a new internal comment on %s in %s.' );
}
else
{ // Normal comment
	$info_text = T_( '%s posted a new comment on %s in %s.' );
}
$notify_message = '<p'.emailskin_style( '.p' ).'>'.sprintf( $info_text, '<b>'.$author_name.'</b> '.$author_type, '<b>'.get_link_tag( $Item->get_permanent_url( '', '', '&' ), $Item->get( 'title' ), '.a' ).'</b>', '<b>'.$Blog->get('shortname').'</b>' )."</p>\n";

if( $params['notify_type'] == 'comment_mentioned' )
{	// Add this info line if user was mentioned in the comment content:
	$notify_message .= '<p'.emailskin_style( '.p' ).'>'.T_( 'You were mentioned in this comment.' )."</p>\n";
}
elseif( $params['notify_type'] == 'meta_comment_mentioned' )
{	// Add this info line if user was mentioned in the internal comment content:
	$notify_message .= '<p'.emailskin_style( '.p' ).'>'.T_( 'You were mentioned in this internal comment.' )."</p>\n";
}

if( $params['notify_full'] )
{ // Long format notification:
	if( ! empty( $recipient_User ) && $recipient_User->check_perm( 'stats', 'view' ) )
	{
		$session_ID = '<a href="'.$admin_url.'?ctrl=stats&amp;tab=hits&amp;blog=0&amp;sess_ID='.$Session->ID.'">'.$Session->ID.'</a>';
	}
	else
	{
		$session_ID = $Session->ID;
	}

	switch( $Comment->type )
	{
		case 'trackback':
			$notify_message .= '<p'.emailskin_style( '.p' ).'>'.T_('Session ID').': '.$session_ID."</p>\n";
			$notify_message .= '<p'.emailskin_style( '.p' ).'>'.T_('Url').': '.get_link_tag( $Comment->author_url, '', '.a' )."</p>\n";
			break;

		default:
			if( ! $Comment->get_author_User() )
			{ // Comment from visitor:
				$notify_message .= '<p'.emailskin_style( '.p' ).'>'.T_('Session ID').': '.$session_ID."</p>\n";
				$notify_message .= '<p'.emailskin_style( '.p' ).'>'.T_('Email').': '.$Comment->author_email."</p>\n";
				$notify_message .= '<p'.emailskin_style( '.p' ).'>'.T_('Url').': '.get_link_tag( $Comment->author_url, '', '.a' )."</p>\n";
			}
	}

	if( !empty( $Comment->rating ) )
	{
		$notify_message .= '<p'.emailskin_style( '.p' ).'>'.T_('Rating').': '.$Comment->rating.'/5'."</p>\n";
	}

	if( $params['notify_type'] == 'moderator' )
	{
		$notify_message .= '<p'.emailskin_style( '.p' ).'>'.T_('Status').': '.$Comment->get( 't_status' )."</p>\n";
	}

	// Content:
	$notify_message .= '<div class="email_ugc"'.emailskin_style( 'div.email_ugc' ).'>'."\n";
	$notify_message .= '<p'.emailskin_style( '.p' ).'>'.nl2br( $Comment->get('content') ).'</p>';
	$notify_message .= "</div>\n";

	// Attachments:
	$LinkCache = & get_LinkCache();
	$comment_links = $LinkCache->get_by_comment_ID( $Comment->ID );
	if( !empty( $comment_links ) )
	{
		$notify_message .= '<p'.emailskin_style( '.p' ).'>'.T_('Attachments').':<ul>'."\n";
		foreach( $comment_links as $Link )
		{
			if( $File = $Link->get_File() )
			{
				$notify_message .= '<li><a href="'.$File->get_url().'"'.emailskin_style( '.a' ).'>';
				if( $File->is_image() )
				{ // Display an image
					$notify_message .= $File->get_thumb_imgtag( 'fit-80x80', '', 'middle' ).' ';
				}
				$notify_message .= $File->get_name().'</a></li>'."\n";
			}
		}
		$notify_message .= "</ul></p>\n";
	}
}
else
{	// Short format notification:
	if( $params['notify_type'] == 'moderator' )
	{
		$notify_message .= '<p'.emailskin_style( '.p' ).'>'.T_('Status').': <b>'.$Comment->get( 't_status' )."</b></p>\n";

		$notify_message .= '<div class="email_ugc"'.emailskin_style( 'div.email_ugc' ).'>'."\n";
		$notify_message .= '<p'.emailskin_style( '.p' ).'><i'.emailskin_style( '.note' ).'>'.T_( 'This is a short form notification. To make these emails more useful, ask the administrator to send you long form notifications instead.' ).'</i></p>';
		$notify_message .= "</div>\n";
	}
}

echo $notify_message;

// Buttons:

echo '<div'.emailskin_style( 'div.buttons' ).'>'."\n";

echo get_link_tag( $Comment->get_permanent_url( '&', '#comments' ), T_( 'Read full comment' ), 'div.buttons a+a.btn-primary' )."\n";

if( $params['notify_type'] == 'moderator' )
{ // moderation email
	if( ( $Blog->get_setting( 'comment_quick_moderation' ) != 'never' ) && ( !empty( $Comment->secret ) ) )
	{ // quick moderation is permitted, and comment secret was set
		echo get_link_tag( '$secret_content_start$'.get_htsrv_url().'comment_review.php?cmt_ID='.$Comment->ID.'&secret='.$Comment->secret.'$secret_content_end$', T_('Quick moderation'), 'div.buttons a+a.btn-primary' )."\n";
	}
	echo get_link_tag( $admin_url.'?ctrl=comments&action=edit&comment_ID='.$Comment->ID, T_('Edit comment'), 'div.buttons a+a.btn-default' )."\n";
}

echo "</div>\n";


// add unsubscribe and edit links
$params['unsubscribe_text'] = '';
switch( $params['notify_type'] )
{
	case 'moderator':
		// moderation email
		if( $params['is_new_comment'] )
		{	// about new comment:
			$unsubscribe_text = T_( 'If you don\'t want to receive any more notifications about moderating new comments, click here' );
			$unsubscribe_type = 'comment_moderator';
		}
		else
		{	// about updated comment:
			$unsubscribe_text = T_( 'If you don\'t want to receive any more notifications about moderating updated comments, click here' );
			$unsubscribe_type = 'comment_moderator_edit';
		}
		$params['unsubscribe_text'] = T_( 'You are a moderator of this blog and you are receiving notifications when a comment may need moderation.' ).'<br />'
			.$unsubscribe_text.': '
			.get_link_tag( get_htsrv_url().'quick_unsubscribe.php?type='.$unsubscribe_type.'&user_ID=$user_ID$&key=$unsubscribe_key$', T_('instant unsubscribe'), '.a' );
		break;

	case 'comment_mentioned':
		// user is mentioned in the comment
		$params['unsubscribe_text'] = T_( 'You were mentioned in this comment, and you are receiving notifications when anyone mentions your name in a comment.' ).'<br />'
			.T_( 'If you don\'t want to receive any more notifications when you were mentioned in a comment, click here' ).': '
			.get_link_tag( get_htsrv_url().'quick_unsubscribe.php?type=comment_mentioned&user_ID=$user_ID$&key=$unsubscribe_key$', T_('instant unsubscribe'), '.a' );
		break;

	case 'meta_comment_mentioned':
		// user is mentioned in the internal comment
		$params['unsubscribe_text'] = T_( 'You were mentioned in this internal comment, and you are receiving notifications when anyone mentions your name in an internal comment.' ).'<br />'
			.T_( 'If you don\'t want to receive any more notifications when you were mentioned in an internal comment, click here' ).': '
			.get_link_tag( get_htsrv_url().'quick_unsubscribe.php?type=meta_comment_mentioned&user_ID=$user_ID$&key=$unsubscribe_key$', T_('instant unsubscribe'), '.a' );
		break;

	case 'blog_subscription':
		// blog subscription
		$params['unsubscribe_text'] = T_( 'You are receiving notifications when anyone comments on any post.' ).'<br />'
			.T_( 'If you don\'t want to receive any more notifications on this blog, click here' ).': '
			.get_link_tag( get_htsrv_url().'quick_unsubscribe.php?type=coll_comment&coll_ID='.$Blog->ID.'&user_ID=$user_ID$&key=$unsubscribe_key$', T_('instant unsubscribe'), '.a' );
		// subscribers are not allowed to see comment author email
		break;

	case 'item_subscription':
		// item subscription for registered user:
		$params['unsubscribe_text'] = T_( 'You are receiving notifications when anyone comments on this post.' ).'<br />'
			.T_( 'If you don\'t want to receive any more notifications on this post, click here' ).': '
			.get_link_tag( get_htsrv_url().'quick_unsubscribe.php?type=post&post_ID='.$Item->ID.'&user_ID=$user_ID$&key=$unsubscribe_key$', T_('instant unsubscribe'), '.a' );
		// subscribers are not allowed to see comment author email
		break;

	case 'anon_subscription':
		// item subscription for anonymous user:
		$params['unsubscribe_text'] = T_( 'You are receiving notifications when anyone comments on this post.' ).'<br />'
			.T_( 'If you don\'t want to receive any more notifications on this post, click here' ).': '
			.get_link_tag( get_htsrv_url().'quick_unsubscribe.php?type=post&post_ID='.$Item->ID.'&comment_ID='.$params['comment_ID'].'&key=$unsubscribe_key$', T_('instant unsubscribe'), '.a' );
		// subscribers are not allowed to see comment author email
		break;

	case 'creator':
		// user is the creator of the post
		$params['unsubscribe_text'] = T_( 'This is your post. You are receiving notifications when anyone comments on your posts.' ).'<br />'
			.T_( 'If you don\'t want to receive any more notifications on your posts, click here' ).':'
			.get_link_tag( get_htsrv_url().'quick_unsubscribe.php?type=creator&user_ID=$user_ID$&key=$unsubscribe_key$', T_('instant unsubscribe'), '.a' );
		break;

	case 'meta_comment':
		// internal comment subscription
		$params['unsubscribe_text'] = T_( 'You are receiving notifications when internal comment is added on this post.' ).'<br />'
			.T_( 'If you don\'t want to receive any more notifications about internal comments, click here' ).': '
			.get_link_tag( get_htsrv_url().'quick_unsubscribe.php?type=meta_comment&user_ID=$user_ID$&key=$unsubscribe_key$', T_('instant unsubscribe'), '.a' );
		break;
}

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.html.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>
