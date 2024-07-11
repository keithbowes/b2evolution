<?php
/**
 * This file implements the UI view for the Collection comments properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}.
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog, $AdminUI, $Settings, $admin_url;
$notifications_mode = $Settings->get( 'outbound_notifications_mode' );

?>
<script>
	<!--
	function show_hide_feedback_details(ob)
	{
		if( ob.value == 'never' )
		{
			jQuery( '.feedback_details_container' ).hide();
		}
		else
		{
			jQuery( '.feedback_details_container' ).show();
		}
	}
	//-->
</script>
<?php

// This warning is used for 'Trackbacks' and 'New feedback status'
$spammers_warning = '<span class="red"$attrs$>'.get_icon( 'warning_yellow' ).' '.TB_('Warning: this makes your site a preferred target for spammers!').'<br /></span>';

// Permission to edit advanced admin settings
$perm_blog_admin = check_user_perm( 'blog_admin', 'edit', false, $edited_Blog->ID );

$Form = new Form( NULL, 'coll_comments_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'comments' );
$Form->hidden( 'blog', $edited_Blog->ID );

$Form->begin_fieldset( TB_('Comment viewing options') . get_manual_link('comment-viewing-options') );

	$Form->radio( 'allow_view_comments', $edited_Blog->get_setting( 'allow_view_comments' ),
						array(  array( 'any', TB_('Any user'), TB_('Including anonymous users') ),
								array( 'registered', TB_('Registered users only') ),
								array( 'member', TB_('Members only'),  TB_( 'Users have to be members of this blog' ) ),
								array( 'moderator', TB_('Moderators & Admins only') ),
					), TB_('Comment viewing by'), true );

	// put this on feedback details container, this way it won't be displayed if comment posting is not allowed
	echo '<div class="feedback_details_container">';

	$Form->radio( 'comments_orderdir', $edited_Blog->get_setting('comments_orderdir'),
						array(	array( 'ASC', TB_('Chronologic') ),
								array ('DESC', TB_('Reverse') ),
						), TB_('Display order'), true );

	$Form->checkbox( 'threaded_comments', $edited_Blog->get_setting( 'threaded_comments' ), TB_('Threaded comments'), TB_('Check to enable hierarchical threads of comments.') );

	$paged_comments_disabled = (boolean) $edited_Blog->get_setting( 'threaded_comments' );
	$Form->checkbox( 'paged_comments', $edited_Blog->get_setting( 'paged_comments' ), TB_( 'Paged comments' ), TB_( 'Check to enable paged comments on the public pages.' ), '', 1, $paged_comments_disabled );

	$Form->text( 'comments_per_page', $edited_Blog->get_setting('comments_per_page'), 4, TB_('Comments/Page'),  TB_('How many comments do you want to display on one page?'), 4 );

	$Form->checkbox( 'comments_avatars', $edited_Blog->get_setting( 'comments_avatars' ), TB_('Display profile pictures'), TB_('Display profile pictures/avatars for comments.') );

	$Form->checkbox( 'comments_latest', $edited_Blog->get_setting( 'comments_latest' ), TB_('Latest comments'), TB_('Check to enable viewing of the latest comments') );

	$Form->checklist( get_inskin_statuses_options( $edited_Blog, 'comment' ), 'comment_inskin_statuses', TB_('Front office statuses'), false, false, array( 'note' => 'Uncheck the statuses that should never appear in the front office.' ) );

	echo '</div>';

$Form->end_fieldset();

$Form->begin_fieldset( TB_('Feedback options') . get_manual_link('comment-feedback-options') );

	$Form->radio( 'allow_comments', $edited_Blog->get_setting( 'allow_comments' ),
						array(  array( 'any', TB_('Any user'), TB_('Including anonymous users'),
										'', 'onclick="show_hide_feedback_details(this);"'),
								array( 'registered', TB_('Registered users only'),  '',
										'', 'onclick="show_hide_feedback_details(this);"'),
								array( 'member', TB_('Members only'),  TB_( 'Users have to be members of this blog' ),
										'', 'onclick="show_hide_feedback_details(this);"'),
								array( 'never', TB_('Not allowed'), '',
										'', 'onclick="show_hide_feedback_details(this);"'),
					), TB_('Comment posting by'), true, $edited_Blog->get_advanced_perms_warning() );

	echo '<div class="feedback_details_container">';

	$Form->textarea_input( 'comment_form_msg', $edited_Blog->get_setting( 'comment_form_msg' ), 3, TB_('Message before comment form') );

	$Form->checklist( array(
			array( 'require_anon_name', 1, TB_('Require a name'), $edited_Blog->get_setting( 'require_anon_name' ) ),
			array( 'require_anon_email', 1, TB_('Require an email'), $edited_Blog->get_setting( 'require_anon_email' ) ),
			array( 'allow_anon_url', 1, TB_('Allow to submit an URL'), $edited_Blog->get_setting( 'allow_anon_url' ) )
		), 'allow_anon_url', TB_('Anonymous comments') );

	$Form->text_input( 'comment_maxlen', $edited_Blog->get_setting( 'comment_maxlen' ), 4, TB_('Max. comment length'), TB_('Leave empty for unrestricted.') );
	$Form->checkbox( 'allow_html_comment', $edited_Blog->get_setting( 'allow_html_comment' ),
						TB_( 'Allow HTML' ), TB_( 'Check to allow HTML in comments.' ).' ('.TB_('HTML code will pass several sanitization filters.').')' );

	$any_option = array( 'any', TB_('Any user'), TB_('Including anonymous users'), '' );
	$registered_option = array( 'registered', TB_('Registered users only'),  '', '' );
	$member_option = array( 'member', TB_('Members only'), TB_('Users have to be members of this blog'), '' );
	$never_option = array( 'never', TB_('Not allowed'), '', '' );
	$Form->radio( 'allow_attachments', $edited_Blog->get_setting( 'allow_attachments' ),
						array(  $any_option, $registered_option, $member_option, $never_option,
						), TB_('Allow attachments from'), true );

	$max_attachments_params = array();
	if( $edited_Blog->get_setting( 'allow_attachments' ) == 'any' )
	{	// Disable field "Max # of attachments" when Allow attachments from Any user
		$max_attachments_params['disabled'] = 'disabled';
	}
	$Form->text_input( 'max_attachments', $edited_Blog->get_setting( 'max_attachments' ), 10, TB_('Max # of attachments per User per Post'), TB_('(leave empty for no limit)'), $max_attachments_params );

	if( $perm_blog_admin || $edited_Blog->get( 'allowtrackbacks' ) )
	{ // Only admin can turn ON this setting
		$trackbacks_warning_attrs = ' id="trackbacks_warning" style="display:'.( $edited_Blog->get( 'allowtrackbacks' ) ? 'inline' : 'none' ).'"';
		$trackbacks_warning = str_replace( '$attrs$', $trackbacks_warning_attrs, $spammers_warning );
		$trackbacks_title = !$edited_Blog->get( 'allowtrackbacks' ) ? get_admin_badge() : '';
		$Form->checkbox( 'blog_allowtrackbacks', $edited_Blog->get( 'allowtrackbacks' ), TB_('Trackbacks').$trackbacks_title, $trackbacks_warning.TB_('Allow other bloggers to send trackbacks to this blog, letting you know when they refer to it. This will also let you send trackbacks to other blogs.') );
	}

	if( $perm_blog_admin || $edited_Blog->get_setting( 'webmentions' ) )
	{	// Only admin can turn ON this setting
		$Form->checkbox( 'blog_webmentions', $edited_Blog->get_setting( 'webmentions' ),
			TB_('Webmentions').( ! $edited_Blog->get_setting( 'webmentions' ) ? get_admin_badge() : '' ),
			TB_('Allow other bloggers to send webmentions to this collection, letting you know when they refer to it.')
			// Display additional note for not public collection:
			.( $edited_Blog->get_setting( 'allow_access' ) != 'public' ? ' <span class="red">'.TB_('This collection cannot receive webmentions because it is not public.').'</span>' : '' ),
			'', 1,
			// Disable receiving of webmentions for not public collections:
			$edited_Blog->get_setting( 'allow_access' ) != 'public' );
	}

	$Form->checkbox( 'autocomplete_usernames', $edited_Blog->get_setting( 'autocomplete_usernames' ),
		TB_( 'Autocomplete usernames in back-office' ), TB_( 'Check to enable auto-completion of usernames entered after a "@" sign in the comment forms' ) );

	echo '</div>';

	if( $edited_Blog->get_setting( 'allow_comments' ) == 'never' )
	{ ?>
	<script>
		<!--
		jQuery( '.feedback_details_container' ).hide();
		//-->
	</script>
	<?php
	}

$Form->end_fieldset();

$Form->begin_fieldset( TB_('Voting options') . get_manual_link('comment-voting-options'), array( 'class' => 'feedback_details_container' ) );

	$Form->checkbox( 'display_rating_summary', $edited_Blog->get_setting( 'display_rating_summary' ), TB_('Display summary'), TB_('Display a summary of ratings above the comments') );

	$Form->radio( 'allow_rating_items', $edited_Blog->get_setting( 'allow_rating_items' ),
						array( $any_option, $registered_option, $member_option, $never_option,
						), TB_('Allow star ratings from'), true );

	$Form->textarea_input( 'rating_question', $edited_Blog->get_setting( 'rating_question' ), 3, TB_('Star rating question') );

	$Form->checkbox( 'allow_rating_comment_helpfulness', $edited_Blog->get_setting( 'allow_rating_comment_helpfulness' ), TB_('Allow helpful/not helpful'), TB_('Allow users to say if a comment was helpful or not.') );

$Form->end_fieldset();


// display comments settings provided by optional modules:
// echo 'modules';
modules_call_method( 'display_collection_comments', array( 'Form' => & $Form, 'edited_Blog' => & $edited_Blog ) );

$Form->begin_fieldset( TB_('Comment moderation') . get_manual_link('comment-moderation') );

	// Get max allowed visibility status:
	$max_allowed_status = get_highest_publish_status( 'comment', $edited_Blog->ID, false );

	$is_bootstrap_skin = ( isset( $AdminUI, $AdminUI->skin_name ) && $AdminUI->skin_name == 'bootstrap' );
	$newstatus_warning_attrs = ' id="newstatus_warning" style="display:'.( $edited_Blog->get_setting('new_feedback_status') == 'published' ? 'inline' : 'none' ).'"';
	$newstatus_warning = str_replace( '$attrs$', $newstatus_warning_attrs, $spammers_warning );
	$status_options = get_visibility_statuses( '', array( 'redirected', 'trash' ) );
	if( $edited_Blog->get_setting('new_feedback_status') != 'published' )
	{
		if( $perm_blog_admin )
		{ // Only admin can set this setting to 'Public'
			$status_options['published'] .= $is_bootstrap_skin ? get_admin_badge( 'coll', false ) : ' ['.TB_('Admin').']';
		}
		else
		{ // Remove published status for non-admin users
			unset( $status_options['published'] );
		}
	}
	// Set this flag to false in order to find first allowed status below:
	$status_is_allowed = false;
	foreach( $status_options as $status_key => $status_option )
	{
		if( $status_key == $max_allowed_status )
		{	// This is first allowed status, then all next statuses are also allowed:
			$status_is_allowed = true;
		}
		if( ! $status_is_allowed && $edited_Blog->get_setting( 'new_feedback_status' ) != $status_key )
		{	// Don't allow to select this status because it is not allowed by collection restriction:
			unset( $status_options[ $status_key ] );
		}
	}
	// put this on feedback details container, this way it won't be displayed if comment posting is not allowed
	echo '<div class="feedback_details_container">';

	if( $is_bootstrap_skin )
	{	// Use dropdown for bootstrap skin:
		$new_status_field = get_status_dropdown_button( array(
				'name'    => 'new_feedback_status',
				'value'   => $edited_Blog->get_setting('new_feedback_status'),
				'options' => $status_options,
			) );
		$Form->info( TB_('Status for new Anonymous comments'), $new_status_field, $newstatus_warning.TB_('Logged in users will get the highest possible status allowed by their permissions. Plugins may also override this default.') );
		$Form->hidden( 'new_feedback_status', $edited_Blog->get_setting('new_feedback_status') );
		echo_form_dropdown_js();
	}
	else
	{	// Use standard select element for other skins:
		$Form->select_input_array( 'new_feedback_status', $edited_Blog->get_setting('new_feedback_status'), $status_options,
				TB_('Status for new Anonymous comments'), $newstatus_warning.TB_('Logged in users will get the highest possible status allowed by their permissions. Plugins may also override this default.') );
	}
	echo '</div>';

	// Moderation statuses setting:
	$all_statuses = get_visibility_statuses( 'keys', NULL );
	$not_moderation_statuses = array_diff( $all_statuses, get_visibility_statuses( 'moderation' ) );
	// Get moderation statuses with status text:
	$moderation_statuses = get_visibility_statuses( '', $not_moderation_statuses );
	$moderation_status_icons = get_visibility_statuses( 'icons', $not_moderation_statuses );
	$blog_moderation_statuses = $edited_Blog->get_setting( 'moderation_statuses' );
	$checklist_options = array();
	// Set this flag to false in order to find first allowed status below:
	$status_is_hidden = true;
	foreach( $all_statuses as $status )
	{	// Add a checklist option for each possible moderation status:
		if( $status == $max_allowed_status )
		{	// This is first allowed status, then all next statuses are also allowed:
			$status_is_hidden = false;
		}
		if( ! isset( $moderation_statuses[ $status ] ) )
		{	// Don't display a checkbox for non moderation status:
			continue;
		}
		$checklist_options[] = array(
				'notif_'.$status, // Field name of checkbox
				1, // Field value
				$moderation_status_icons[ $status ].' '.$moderation_statuses[ $status ], // Text
				( strpos( $blog_moderation_statuses, $status ) !== false ), // Checked?
				'', // Disabled?
				'', // Note
				'', // Class
				$status_is_hidden, // Hidden field instead of checkbox?
				array(
					'data-toggle' => 'tooltip',
					'data-placement' => 'top',
					'title' => get_status_tooltip_title( $status ) )
			);
	}
	$Form->checklist( $checklist_options, 'moderation_statuses', TB_('"Require moderation" statuses'), false, false, array( 'note' => TB_('Comments with the selected statuses will be considered to require moderation. They will trigger "moderation required" notifications and will appear as such on the collection dashboard.') ) );

	$Form->radio( 'comment_quick_moderation', $edited_Blog->get_setting( 'comment_quick_moderation' ),
					array(  array( 'never', TB_('Never') ),
							array( 'expire', TB_('Links expire on first edit action') ),
							array( 'always', TB_('Always available') )
						), TB_('Comment quick moderation'), true );
$Form->end_fieldset();

$Form->begin_fieldset( TB_('RSS/Atom feeds') . get_manual_link('comment-rss-atom-feeds') );
	$Form->radio( 'comment_feed_content', $edited_Blog->get_setting('comment_feed_content'),
								array(  array( 'none', TB_('No feeds') ),
										array( 'excerpt', TB_('Comment excerpts') ),
										array( 'normal', TB_('Standard comment contents') ),
									), TB_('Comment feed contents'), true, TB_('How much content do you want to make available in comment feeds?') );

	$Form->text( 'comments_per_feed', $edited_Blog->get_setting('comments_per_feed'), 4, TB_('Comments in feeds'),  TB_('How many of the latest comments do you want to include in RSS & Atom feeds?'), 4 );
$Form->end_fieldset();

if( $notifications_mode != 'off' )
{
	$Form->begin_fieldset( TB_('Subscriptions') . get_manual_link('comment-subscriptions') );
		$Form->checklist( array(
					array( 'allow_comment_subscriptions', 1, TB_('Allow users to subscribe and receive email notifications for each new comment.'), $edited_Blog->get_setting( 'allow_comment_subscriptions' ) ),
					array( 'allow_item_subscriptions', 1, TB_( 'Allow users to subscribe and receive email notifications for comments on a specific post.' ), $edited_Blog->get_setting( 'allow_item_subscriptions' ) ),
				), 'allow_coll_subscriptions', TB_('Registered users') );
		$Form->checklist( array(
				array( 'allow_anon_subscriptions', 1, TB_( 'Allow users to subscribe and receive email notifications for replies to their comments.' ), $edited_Blog->get_setting( 'allow_anon_subscriptions' ) ),
			), 'allow_anon_subscriptions', TB_('Anonymous users') );
		$Form->radio( 'default_anon_comment_notify', $edited_Blog->get_setting( 'default_anon_comment_notify' ), array(
				array( 1, TB_('Checked') ),
				array( 0, TB_('Unchecked') ),
			), TB_('Default option') );
		$Form->text( 'anon_notification_email_limit', $edited_Blog->get_setting( 'anon_notification_email_limit' ), 4, TB_('Limit'),  TB_('Max # of emails an anonymous user may receive per day.'), 4 );
	$Form->end_fieldset();
}


$Form->begin_fieldset( TB_('Registration of commenters') . get_manual_link('comment-registration-of-commenters') );
	$Form->checkbox( 'comments_detect_email', $edited_Blog->get_setting( 'comments_detect_email' ), TB_('Email addresses'), TB_( 'Detect email addresses in comments.' ) );

	$Form->checkbox( 'comments_register', $edited_Blog->get_setting( 'comments_register' ), TB_('Register after comment'), TB_( 'Display the registration form right after submitting a comment.' ) );
$Form->end_fieldset();


$Form->begin_fieldset( TB_('Comment recycle bin').get_manual_link('recycle-bin-settings') );

	$Form->text_input( 'auto_empty_trash', $Settings->get('auto_empty_trash'), 5, TB_('Prune recycled comments after'), TB_('days').'. '.TB_('Warning: This affects ALL collections on the system.') );

$Form->end_fieldset();


$Form->begin_fieldset( TB_('Internal Comments').get_manual_link( 'meta-comments-settings' ) );

	$Form->checkbox( 'meta_comments_frontoffice', $edited_Blog->get_setting( 'meta_comments_frontoffice' ), TB_('Display in Front-Office'), TB_('Display internal comments in Front-Office.') );

$Form->end_fieldset();


$Form->end_form( array( array( 'submit', 'submit', TB_('Save Changes!'), 'SaveButton', 'data-shortcut' => 'ctrl+s,command+s,ctrl+enter,command+enter' ) ) );

echo '<div class="well">';
echo '<p>'.sprintf( TB_('You can find more settings in the <a %s>Post Types</a>, including:'), 'href="'.$admin_url.'?blog='.$edited_Blog->ID.'&amp;ctrl=itemtypes&amp;ityp_ID='.$edited_Blog->get_setting( 'default_post_type' ).'&amp;action=edit"' ).'</p>';
echo '<ul>';
echo '<li>'.TB_('Message before comment form').'</li>';
echo '<li>'.TB_('Allow closing comments').'</li>';
echo '<li>'.TB_('Allow disabling comments').'</li>';
echo '<li>'.TB_('Use comment expiration').'</li>';
echo '</ul>';
echo '</div>';

?>
<script>
	var paged_comments_is_checked = jQuery( '#paged_comments' ).is( ':checked' );
	jQuery( '#threaded_comments' ).click( function()
	{ // Disable checkbox "Paged comments" if "Threaded comments" is ON
		if( jQuery( this ).is( ':checked' ) )
		{
			jQuery( '#paged_comments' ).attr( 'disabled', 'disabled' );
			paged_comments_is_checked = jQuery( '#paged_comments' ).is( ':checked' );
			jQuery( '#paged_comments' ).removeAttr( 'checked' );
			jQuery( '#comments_per_page' ).val( '1000' );
		}
		else
		{
			jQuery( '#paged_comments' ).removeAttr( 'disabled' );
			if( paged_comments_is_checked )
			{
				jQuery( '#paged_comments' ).attr( 'checked', 'checked' );
				jQuery( '#comments_per_page' ).val( '20' );
			}
		}
	} );

	jQuery( '#paged_comments' ).click( function()
	{
		if( jQuery( this ).is( ':checked' ) )
		{
			jQuery( '#comments_per_page' ).val( '20' );
		}
		else
		{
			jQuery( '#comments_per_page' ).val( '1000' );
		}
	} );

	jQuery( 'input[name=allow_attachments]' ).click( function()
	{	// Disable field "Max # of attachments" when Allow attachments from Any user
		if( jQuery( this ).val() == 'any' )
		{
			jQuery( '#max_attachments' ).attr( 'disabled', 'disabled' );
		}
		else
		{
			jQuery( '#max_attachments' ).removeAttr( 'disabled' );
		}
	} );

	jQuery( '#blog_allowtrackbacks' ).click( function()
	{ // Show/Hide warning for 'Trackbacks'
		if( jQuery( this ).is( ':checked' ) )
		{
			jQuery( '#trackbacks_warning' ).css( 'display', 'inline' );
		}
		else
		{
			jQuery( '#trackbacks_warning' ).hide();
		}
	} );

	jQuery( '#new_feedback_status' ).change( function()
	{ // Show/Hide warning for 'New feedback status'
		if( jQuery( this ).val() == 'published' )
		{
			jQuery( '#newstatus_warning' ).css( 'display', 'inline' );
		}
		else
		{
			jQuery( '#newstatus_warning' ).hide();
		}
	} );
</script>
