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

global $Session, $admin_url;

// ---------------------------- EMAIL HEADER INCLUDED HERE ----------------------------
emailskin_include( '_email_header.inc.txt.php', $params );
// ------------------------------- END OF EMAIL HEADER --------------------------------


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

// show sender name
echo sprintf( T_('%s has sent you this message:'), $params['sender_name'] );

if( ! empty( $params['additional_fields'] ) )
{	// Display additional fields which have been entered:
	echo "\n\n-- \n";
	foreach( $params['additional_fields'] as $additional_field )
	{
		echo $additional_field['title'].': '.$additional_field['text_value']."\n\n";
	}
}

if( ! empty( $params['contact_method'] ) )
{	// Display a preferred contact method only if it has been selected:
	echo "\n\n-- \n".T_('Reply method').': '.$params['contact_method'];
}

if( ! empty( $params['message'] ) )
{	// Display a message only if it has been entered:
	echo "\n\n-- \n".$params['message'];
}

echo "\n\n-- \n";

if( ! empty( $recipient_User ) && $recipient_User->check_perm( 'stats', 'view' ) )
{
	$session_ID = $admin_url.'?ctrl=stats&amp;tab=hits&amp;blog=0&amp;sess_ID='.$Session->ID;
}
else
{
	$session_ID = $Session->ID;
}
echo sprintf( T_('Session ID').': %s', $session_ID ) . "\n";

// show sender email address
echo sprintf( T_( 'By replying, your email will go directly to %s.' ), $params['sender_address'] );

// show additional message info
$CommentCache = & get_CommentCache();
$ItemCache = & get_ItemCache();

if( !empty( $params['comment_ID'] ) && ( $Comment = & $CommentCache->get_by_ID( $params['comment_ID'], false, false ) ) )
{
	echo "\n\n".T_('Message sent from your comment:') . "\n"
		.$Comment->get_permanent_url();
}
elseif( !empty( $params['post_id'] ) && ( $Item = & $ItemCache->get_by_ID( $params['post_id'], false, false ) ) )
{
	echo "\n\n".T_('Message sent from your post:') . "\n"
		.$Item->get_permanent_url();
}
elseif( ! empty( $Blog ) )
{
	echo "\n\n".sprintf( T_('Message sent through the contact form on %s.'), $Blog->get('shortname') ). "\n";
}

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
			echo "\n\n".T_('You can edit your profile to not receive emails through a form:')."\n".$edit_preferences_url."\n";
		}
	}

	// Add quick unsubcribe link so users can deny receiving emails through b2evo message form in any circumstances:
	if( empty( $params['email_headers']['Reply-To'] ) )
	{	// Display the message below only when replying is not allowed to current email message (usually for email messages from anonymous users):
		$params['unsubscribe_text'] = T_( 'If you don\'t want to receive any more emails through a message form, click here:' ).' '.
			get_htsrv_url().'quick_unsubscribe.php?type=msgform&user_ID=$user_ID$&key=$unsubscribe_key$';
	}
}
elseif( !empty( $params['Comment'] ) )
{ // Visitor:
	$params['unsubscribe_text'] = T_("Click on the following link to not receive e-mails on your comments\nfor this e-mail address anymore:").' '.
		get_htsrv_url().'anon_unsubscribe.php?type=comment&c='.$params['Comment']->ID.'&anon_email='.rawurlencode( $params['Comment']->author_email );
}

// ---------------------------- EMAIL FOOTER INCLUDED HERE ----------------------------
emailskin_include( '_email_footer.inc.txt.php', $params );
// ------------------------------- END OF EMAIL FOOTER --------------------------------
?>
