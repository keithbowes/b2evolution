<?php
/**
 * This is sent to a ((User)) or ((BlogOwner)) when someone sends them a message through a contact form (which is called from a comment, footer of blog, etc.)
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

global $Session, $evo_charset, $admin_url;

// Default params:
$params = array_merge( array(
		'sender_name'      => '',
		'sender_address'   => '',
		'message_footer'   => '',
		'Blog'             => NULL,
		'message'          => '',
		'comment_ID'       => NULL,
		'post_id'          => NULL,
		'recipient_User'   => NULL,
		'Comment'          => NULL,
	), $params );

$Collection = $Blog = & $params['Blog'];
$recipient_User = & $params['recipient_User'];

// show additional message info
if( !empty( $Blog ) )
{
	echo '<p'.emailskin_style( '.p' ).'>';
	if( !empty( $params['comment_ID'] ) )
	{ // From comment
		$CommentCache = & get_CommentCache();
		$Comment = & $CommentCache->get_by_ID( $params['comment_ID'] );
		$Item = & $Comment->get_Item();
		echo sprintf( T_('Message sent from your <a %s>comment</a> on %s.'),
			'href="'.$Comment->get_permanent_url( '&', '#comments' ).'"'.emailskin_style( '.a' ).'',
			'<a href="'.$Item->get_permanent_url( '', '', '&' ).'"'.emailskin_style( '.a' ).'>'.$Item->get( 'title' ).'</a>' );
	}
	elseif( !empty( $params['post_id'] ) )
	{ // From post
		$ItemCache = & get_ItemCache();
		$Item = & $ItemCache->get_by_ID( $params['post_id'] );
		echo sprintf( T_('Message sent from your post: %s.'),
			'<a href="'.$Item->get_permanent_url( '', '', '&' ).'"'.emailskin_style( '.a' ).'>'.$Item->get( 'title' ).'</a>' );
	}
	else
	{ // From contact form
		echo sprintf( T_('Message sent through the contact form on %s.'), '<b>'.$Blog->get('shortname').'</b>' );
	}
	echo '</p>';
}

// show sender name
echo '<p'.emailskin_style( '.p' ).'>'.sprintf( T_('%s (%s) has sent you this message:'),
			'<b>'.$params['sender_name'].'</b>',
			'<a href="mailto:'.$params['sender_address'].'"'.emailskin_style( '.a' ).'>'.$params['sender_address'].'</a>' )
	.'</p>';

if( ! empty( $params['additional_fields'] ) )
{	// Display additional fields which have been entered:
	echo '<p'.emailskin_style( '.p' ).'>';
	foreach( $params['additional_fields'] as $additional_field )
	{
		echo '<b>'.$additional_field['title'].':</b> '.$additional_field['html_value'].'<br />';
	}
	echo '</p>';
}

if( ! empty( $params['contact_method'] ) )
{	// Display a preferred contact method only if it has been selected:
	echo '<p'.emailskin_style( '.p' ).'>'
			.'<b>'.T_('Reply method').':</b> '
			.$params['contact_method']
		.'</p>';
}

if( ! empty( $params['message'] ) )
{	// Display a message only if it has been entered:
	echo '<div class="email_ugc"'.emailskin_style( 'div.email_ugc' ).'>'."\n";
	echo '<p'.emailskin_style( '.p' ).'>'.nl2br( htmlentities( $params['message'], ENT_COMPAT, $evo_charset ) ).'</p>';
	echo "</div>\n";
}

if( ! empty( $recipient_User ) && $recipient_User->check_perm( 'stats', 'view' ) )
{
	$session_ID = '<a href="'.$admin_url.'?ctrl=stats&amp;tab=hits&amp;blog=0&amp;sess_ID='.$Session->ID.'">'.$Session->ID.'</a>';
}
else
{
	$session_ID = $Session->ID;
}
echo sprintf( T_('Session ID').': %s', $session_ID );

// show sender email address
echo '<p'.emailskin_style( '.p' ).'>'.sprintf( T_( 'By replying, your email will go directly to %s.' ), '<a href="mailto:'.$params['sender_address'].'"'.emailskin_style( '.a' ).'>'.$params['sender_address'].'</a>' ).'</p>';


if( ! empty( $recipient_User ) )
{ // Member:
	global $Settings;
	if( $Settings->get( 'emails_msgform' ) == 'userset' )
	{ // user can allow/deny to receive emails
		$edit_preferences_url = NULL;
		if( !empty( $Blog ) )
		{ // go to blog
			$edit_preferences_url = $Blog->get( 'userprefsurl', array( 'glue' => '&' ) );
		}
		elseif( $recipient_User->check_perm( 'admin', 'restricted' ) )
		{ // go to admin
			$edit_preferences_url = $admin_url.'?ctrl=user&user_tab=userprefs&user_ID='.$recipient_User->ID;
		}
		if( !empty( $edit_preferences_url ) )
		{ // add edit preferences link
			echo '<p'.emailskin_style( '.p' ).'>'.sprintf( T_('You can edit your profile to not receive emails through a <a %s>form</a>'),
				'href="'.$edit_preferences_url.'"'.emailskin_style( '.a' ).'' ).'</p>';
		}
	}

	// Add quick unsubcribe link so users can deny receiving emails through b2evo message form in any circumstances:
	if( empty( $params['email_headers']['Reply-To'] ) )
	{	// Display the message below only when replying is not allowed to current email message (usually for email messages from anonymous users):
		$params['unsubscribe_text'] = T_( 'If you don\'t want to receive any more emails through a message form, click here:' )
			.' <a href="'.get_htsrv_url().'quick_unsubscribe.php?type=msgform&user_ID=$user_ID$&key=$unsubscribe_key$"'.emailskin_style( '.a' ).'>'
			.T_('instant unsubscribe').'</a>.';
	}
}
elseif( !empty( $params['Comment'] ) )
{ // Visitor:
	$params['unsubscribe_text'] = T_( 'If you don\'t want to receive e-mails on your comments for this e-mail address anymore, click here:' )
			.' <a href="'.get_htsrv_url().'anon_unsubscribe.php?type=comment&c='.$params['Comment']->ID.'&anon_email='.rawurlencode( $params['Comment']->author_email ).'"'.emailskin_style( '.a' ).'>'
			.T_('instant unsubscribe').'</a>.';
}

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.html.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>
