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
emailskin_include( '_email_header.inc.txt.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------

global $admin_url, $Session;

// Default params:
$params = array_merge( array(
		'notify_full' => false,
		'Comment'     => NULL,
		'Blog'        => NULL,
		'Item'        => NULL,
		'author_ID'   => NULL,
		'author_name' => '',
		'notify_type' => '',
		'is_new_comment' => true,
	), $params );


$Comment = $params['Comment'];
$Collection = $Blog = $params['Blog'];
$Item = $params['Item'];
$recipient_User = & $params['recipient_User'];

if( $params['notify_type'] == 'meta_comment' || $params['notify_type'] == 'meta_comment_mentioned' )
{ // Internal comment
	$info_text = T_( '%s posted a new internal comment on %s in %s.' );
}
else
{ // Normal comment
	$info_text = T_( '%s posted a new comment on %s in %s.' );
}
$author_type = empty( $params['author_ID'] ) ? ' ['.T_('Visitor').']' : ' ['.T_('Member').']';
$notify_message = sprintf( $info_text, $params['author_name'].$author_type, '"'.$Item->get('title').'"', '"'.$Blog->get('shortname').'"' )."\n\n";

if( $params['notify_type'] == 'comment_mentioned' )
{	// Add this info line if user was mentioned in the comment content:
	$notify_message .= T_( 'You were mentioned in this comment.' )."\n\n";
}
elseif( $params['notify_type'] == 'meta_comment_mentioned' )
{	// Add this info line if user was mentioned in the internal comment content:
	$notify_message .= T_( 'You were mentioned in this internal comment.' )."\n\n";
}

if( $params['notify_full'] )
{ // Long format notification:
	$notify_message .=
		( $params['notify_type'] == 'meta_comment' ? T_('New internal comment') : T_('New comment') ).': '
		.$Comment->get_permanent_url( '&', '#comments' )."\n"
		// TODO: fp> We MAY want to force a short URL and avoid it to wrap on a new line in the mail which may prevent people from clicking
		.T_('Collection').': '.$Blog->get('shortname')."\n"
		// Mail bloat: .' ( '.str_replace('&amp;', '&', $Blog->gen_blogurl())." )\n"
		./* TRANS: noun */ T_('Post').': '.$Item->get('title')."\n";
		// Mail bloat: .' ( '.str_replace('&amp;', '&', $Item->get_permanent_url( '', '', '&' ))." )\n";
		// TODO: fp> We MAY want to force short URL and avoid it to wrap on a new line in the mail which may prevent people from clicking

	if( ! empty( $recipient_User ) && $recipient_User->check_perm( 'stats', 'view' ) )
	{
		$session_ID = $admin_url.'?ctrl=stats&amp;tab=hits&amp;blog=0&amp;sess_ID='.$Session->ID;
	}
	else
	{
		$session_ID = $Session->ID;
	}
	switch( $Comment->type )
	{
		case 'trackback':
			$notify_message .= T_('Website').": $Comment->author (".T_('Session ID').": $session_ID)\n";
			$notify_message .= T_('Url').": $Comment->author_url\n";
			break;

		default:
			if( $Comment->get_author_User() )
			{ // Comment from a registered user:
				$notify_message .= T_('Author').': '.$Comment->author_User->get('preferredname').' ('.$Comment->author_User->get('login').")\n";
			}
			else
			{ // Comment from visitor:
				$notify_message .= T_('Author').": $Comment->author (".T_('Session ID').": $session_ID)\n";
				$notify_message .= T_('Email').": $Comment->author_email\n";
				$notify_message .= T_('Url').": $Comment->author_url\n";
			}
	}

	if( !empty( $Comment->rating ) )
	{
		$notify_message .= T_('Rating').': '.$Comment->rating.'/5'."\n";
	}

	if( $params['notify_type'] == 'moderator' )
	{
		$notify_message .= T_('Status').': '.$Comment->get( 't_status' )."\n";
	}

	// Content:
	$notify_message .= $Comment->get('content')."\n";

	// Attachments:
	$LinkCache = & get_LinkCache();
	$comment_links = $LinkCache->get_by_comment_ID( $Comment->ID );
	if( !empty( $comment_links ) )
	{
		$notify_message .= "\n".T_('Attachments').":\n";
		foreach( $comment_links as $Link )
		{
			if( $File = $Link->get_File() )
			{
				$notify_message .= ' - '.$File->get_name().': '.$File->get_url()."\n";
			}
		}
		$notify_message .= "\n";
	}
}
else
{ // Shot format notification:
	$notify_message .= T_( 'To read the full content of the comment click here:' ).' '
					.$Comment->get_permanent_url( '&', '#comments' )."\n";
					// TODO: fp> We MAY want to force a short URL and avoid it to wrap on a new line in the mail which may prevent people from clicking
	if( $params['notify_type'] == 'moderator' )
	{
		$notify_message .= "\n"
						.T_('Status').': '.$Comment->get( 't_status' )."\n"
						.T_( 'This is a short form notification. To make these emails more useful, ask the administrator to send you long form notifications instead.' )
						."\n";
	}
}

$notify_message .= "\n\n";

if( $params['notify_type'] == 'moderator' )
{	// moderation email
	if( ( $Blog->get_setting( 'comment_quick_moderation' ) != 'never' ) && ( !empty( $Comment->secret ) ) )
	{	// quick moderation is permitted, and comment secret was set
		$notify_message .= T_('Quick moderation').': '.'$secret_content_start$'.get_htsrv_url().'comment_review.php?cmt_ID='.$Comment->ID.'&secret='.$Comment->secret.'$secret_content_end$'."\n\n";
	}
	$notify_message .= T_('Edit comment').': '.$admin_url.'?ctrl=comments&action=edit&comment_ID='.$Comment->ID."\n\n";
}

echo $notify_message;

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
		$params['unsubscribe_text'] = T_( 'You are a moderator of this blog and you are receiving notifications when a comment may need moderation.' )."\n"
			.$unsubscribe_text.': '
			.get_htsrv_url().'quick_unsubscribe.php?type='.$unsubscribe_type.'&user_ID=$user_ID$&key=$unsubscribe_key$';
		break;

	case 'comment_mentioned':
		// user is mentioned in the comment
		$params['unsubscribe_text'] = T_( 'You were mentioned in this comment, and you are receiving notifications when anyone mentions your name in a comment.' )."\n"
			.T_( 'If you don\'t want to receive any more notifications when you were mentioned in a comment, click here' ).': '
			.get_htsrv_url().'quick_unsubscribe.php?type=comment_mentioned&user_ID=$user_ID$&key=$unsubscribe_key$';
		break;

	case 'meta_comment_mentioned':
		// user is mentioned in the internal comment
		$params['unsubscribe_text'] = T_( 'You were mentioned in this internal comment, and you are receiving notifications when anyone mentions your name in an internal comment.' )."\n"
			.T_( 'If you don\'t want to receive any more notifications when you were mentioned in an internal comment, click here' ).': '
			.get_htsrv_url().'quick_unsubscribe.php?type=meta_comment_mentioned&user_ID=$user_ID$&key=$unsubscribe_key$';
		break;

	case 'blog_subscription':
		// blog subscription
		$params['unsubscribe_text'] = T_( 'You are receiving notifications when anyone comments on any post.' )."\n"
			.T_( 'If you don\'t want to receive any more notifications on this blog, click here' ).': '
			.get_htsrv_url().'quick_unsubscribe.php?type=coll_comment&coll_ID='.$Blog->ID.'&user_ID=$user_ID$&key=$unsubscribe_key$';
		// subscribers are not allowed to see comment author email
		break;

	case 'item_subscription':
		// item subscription for registered user:
		$params['unsubscribe_text'] = T_( 'You are receiving notifications when anyone comments on this post.' )."\n"
			.T_( 'If you don\'t want to receive any more notifications on this post, click here' ).': '
			.get_htsrv_url().'quick_unsubscribe.php?type=post&post_ID='.$Item->ID.'&user_ID=$user_ID$&key=$unsubscribe_key$';
		// subscribers are not allowed to see comment author email
		break;

	case 'anon_subscription':
		// item subscription for anonymous user:
		$params['unsubscribe_text'] = T_( 'You are receiving notifications when anyone comments on this post.' )."\n"
			.T_( 'If you don\'t want to receive any more notifications on this post, click here' ).': '
			.get_htsrv_url().'quick_unsubscribe.php?type=post&post_ID='.$Item->ID.'&comment_ID='.$params['comment_ID'].'&key=$unsubscribe_key$';
		// subscribers are not allowed to see comment author email
		break;

	case 'creator':
		// user is the creator of the post
		$params['unsubscribe_text'] = T_( 'This is your post. You are receiving notifications when anyone comments on your posts.' )."\n"
			.T_( 'If you don\'t want to receive any more notifications on your posts, click here' ).': '
			.get_htsrv_url().'quick_unsubscribe.php?type=creator&user_ID=$user_ID$&key=$unsubscribe_key$';
		break;

	case 'meta_comment':
		// internal comment subscription
		$params['unsubscribe_text'] = T_( 'You are receiving notifications when internal comment is added on this post.' )."\n"
			.T_( 'If you don\'t want to receive any more notifications about internal comments, click here' ).': '
			.get_htsrv_url().'quick_unsubscribe.php?type=meta_comment&user_ID=$user_ID$&key=$unsubscribe_key$';
		break;
}

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.txt.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>
