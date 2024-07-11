<?php
/**
 * This file implements the UI view for those user preferences which are visible only for admin users.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var instance of User class
 */
global $edited_User, $UserSettings, $Settings, $Plugins;

global $current_User;

global $servertimenow, $admin_url, $action;

if( !$current_User->can_moderate_user( $edited_User->ID ) )
{ // Check permission:
	debug_die( TB_( 'You have no permission to see this tab!' ) );
}

// Begin payload block:
$this->disp_payload_begin();

// ------------------- PREV/NEXT USER LINKS -------------------
user_prevnext_links( array(
		'user_tab' => 'admin'
	) );
// ------------- END OF PREV/NEXT USER LINKS -------------------

$user_status_icons = get_user_status_icons();

$Form = new Form( NULL, 'user_checkchanges' );

$Form->title_fmt = '$title$';

echo_user_actions( $Form, $edited_User, $action );

$form_text_title = TB_( 'User admin settings' ); // used for js confirmation message on leave the changed form
$form_title = get_usertab_header( $edited_User, 'admin', '<span class="nowrap">'.TB_( 'User admin settings' ).'</span>'.get_manual_link( 'user-admin-tab' ) );

$Form->begin_form( 'fform', $form_title, array( 'title' => ( isset( $form_text_title ) ? $form_text_title : $form_title ) ) );

$Form->add_crumb( 'user' );
$Form->hidden_ctrl();
$Form->hidden( 'user_tab', 'admin' );
$Form->hidden( 'admin_form', '1' );

$Form->hidden( 'user_ID', $edited_User->ID );
$Form->hidden( 'edited_user_login', $edited_User->login );

/***************  User permissions  **************/

$Form->begin_fieldset( TB_('User permissions').get_manual_link('user-admin-permissions'), array( 'class'=>'fieldset clear' ) );

// Status:
$status_icon = '<div id="user_status_icon" class="status_icon">'.$user_status_icons[ $edited_User->get( 'status' ) ].'</div>';
if( $edited_User->ID == 1 )
{	// This is Admin user, Don't allow to change status:
	$Form->info( TB_('Account status'), $status_icon.' '.TB_( 'Autoactivated' ) );
}
else
{	// Allow to change status for non-admin users:
	$Form->select_input_array( 'edited_user_status', $edited_User->get( 'status' ), get_user_statuses(), TB_( 'Account status' ), '', array( 'input_prefix' => $status_icon ) );
}

// Primary and secondary groups:
display_user_groups_selectors( $edited_User, $Form );

// User level:
$level_fieldnote = '[0 - 10]';
if( $edited_User->ID == 1 )
{	// This is Admin user, Don't allow to change level:
	$Form->info_field( TB_('User level'), $edited_User->get('level'), array( 'note' => $level_fieldnote ) );
}
else
{	// Allow to change level for non-admin users:
	$Form->text_input( 'edited_user_level', $edited_User->get('level'), 2, TB_('User level'), $level_fieldnote, array( 'required' => true ) );
}

$Form->end_fieldset(); // user permissions

$Form->begin_fieldset( TB_('Email').get_manual_link('user-admin-email') );

	$Form->begin_line( TB_('Email') );
		$email_fieldnote = '<a href="mailto:'.$edited_User->get( 'email' ).'" class="'.button_class().'">'.get_icon( 'email', 'imgtag', array('title'=>TB_('Send an email')) ).'</a>';
		$Form->email_input( 'edited_user_email', $edited_User->get( 'email' ), 30, '', array( 'maxlength' => 255, 'required' => true, 'note' => $email_fieldnote ) );

		$email_status = $edited_User->get_email_status();
		$email_status_icon = '<div id="email_status_icon" class="status_icon">'.emadr_get_status_icon( $email_status ).'</div>';
		if( check_user_perm( 'users', 'edit' ) )
		{
			$Form->select_input_array( 'edited_email_status', $email_status, emadr_get_status_titles(), '<b class="evo_label_inline">'.TB_('Status').': </b>'.$email_status_icon, '', array( 'force_keys_as_values' => true, 'background_color' => emadr_get_status_colors() ) );
		}
		else
		{ // Moderators can only view the email status
			$email_status_titles = emadr_get_status_titles();
			$Form->info( '<b class="evo_label_inline">'.TB_('Status').': </b>', $email_status_icon.$email_status_titles[ $email_status ] );
		}
	$Form->end_line();

	user_domain_info_display( TB_('Email domain'), 'email_domain_status', $edited_User->get_email_domain(), $Form );

	global $UserSettings;

	// Display notification sender email adderss setting
	$default_notification_sender_email = $Settings->get( 'notification_sender_email' );
	$notifcation_sender_email = $UserSettings->get( 'notification_sender_email', $edited_User->ID );
	$notifcation_sender_email_note = '';
	if( empty( $notifcation_sender_email ) )
	{
		$notifcation_sender_email_note = TB_('Will use the default sender address which is:').' '.$default_notification_sender_email;
	}
	elseif( $default_notification_sender_email != $notifcation_sender_email )
	{
		$notifcation_sender_email_note = get_icon( 'warning_yellow' ).' '.TB_('This is different from the new sender address which is currently:').' '.$default_notification_sender_email;
	}
	$Form->email_input( 'notification_sender_email', $notifcation_sender_email, 50, TB_( 'Sender email address' ), array( 'note' => $notifcation_sender_email_note ) );

	// Display notification sender name setting
	$default_notification_sender_name = $Settings->get( 'notification_sender_name' );
	$notification_sender_name = $UserSettings->get( 'notification_sender_name', $edited_User->ID );
	$notifcation_sender_name_note = '';
	if( empty( $notification_sender_name ) )
	{
		$notifcation_sender_name_note = TB_('Will use the default sender name which is:').' '.$default_notification_sender_name;
	}
	elseif( $default_notification_sender_name != $notification_sender_name )
	{
		$notifcation_sender_name_note = get_icon( 'warning_yellow' ).' '.TB_('This is different from the new sender name which is currently:').' '.$default_notification_sender_name;
	}
	$Form->text_input( 'notification_sender_name', $notification_sender_name, 50, TB_( 'Sender name' ), $notifcation_sender_name_note );

	// Display user account activation info ( Last/Next activate account email )
	$account_activation_info = get_account_activation_info( $edited_User );
	foreach( $account_activation_info as $field_options )
	{ // Display each info field
		$field_note = isset( $field_options[2] ) ? $field_options[2] : '';
		$Form->info_field( $field_options[0], $field_options[1], array( 'note' => $field_note ) );
	}

	// Display last unread messages reminder info
	$last_unread_messages_reminder = $UserSettings->get( 'last_unread_messages_reminder', $edited_User->ID );
	$Form->info_field( TB_('Latest unread messages reminder'), empty( $last_unread_messages_reminder ) ? TB_('None yet') : format_to_output( $last_unread_messages_reminder ), array( 'note' => TB_(' Scheduled job responsible for reminders is "Send reminders about unread messages".') ) );
	// Display next unread message reminder info
	$reminder_info = get_next_reminder_info( $edited_User->ID );
	if( is_array( $reminder_info ) )
	{ // We have field note to display
		$Form->info_field( TB_('Next unread messages reminder'), $reminder_info[0], array( 'note' => $reminder_info[1] ) );
	}
	else
	{ // Display next reminder info, without note
		$Form->info_field( TB_('Next unread messages reminder'), $reminder_info );
	}

	// Display information about notification emails
	$last_notification_email = $UserSettings->get( 'last_notification_email', $edited_User->ID );
	if( empty( $last_notification_email ) )
	{ // Notification email to the edited User was not sent yet
		$Form->info_field( TB_('Latest notification email'), TB_('None yet'), array( 'note' => TB_('The latest between all kind of notification emails.') ) );
	}
	else
	{ // At least one notification email was sent
		// Separator between the last notification email timestamp and counter
		$counter_separator = strpos( $last_notification_email, '_' );
		$last_notificaiton_timestamp = substr( $last_notification_email, 0, $counter_separator );
		$last_notificaiton_date = mysql2localedatetime( date2mysql( $last_notificaiton_timestamp ) );
		$Form->info_field( TB_('Latest notification email'), $last_notificaiton_date, array( 'note' => TB_('The latest between all kind of notification emails.') ) );
		$notification_counter = ( date( 'Ymd', $servertimenow ) == date( 'Ymd', $last_notificaiton_timestamp ) ) ? substr( $last_notification_email, $counter_separator + 1 ) : 0;
		$notification_limit = $UserSettings->get( 'notification_email_limit',  $edited_User->ID );
		$Form->info_field( TB_('Notifications already sent today'), sprintf( TB_('%d out of a maximum allowed of %d'), $notification_counter, $notification_limit ) );
	}

	// Display information about newsletters
	$last_newsletter = $UserSettings->get( 'last_newsletter', $edited_User->ID );
	if( empty( $last_newsletter ) )
	{ // Newsletter to the edited User was not sent yet
		$Form->info_field( TB_('Latest list'), TB_('None yet') );
	}
	else
	{ // At least one newsletter was sent
		// Separator between the last newsletter timestamp and counter
		$counter_separator = strpos( $last_newsletter, '_' );
		$last_newsletter_timestamp = substr( $last_newsletter, 0, $counter_separator );
		$last_newsletter_date = mysql2localedatetime( date2mysql( $last_newsletter_timestamp ) );
		$Form->info_field( TB_('Latest list'), $last_newsletter_date );
		$newsletter_counter = ( date( 'Ymd', $servertimenow ) == date( 'Ymd', $last_newsletter_timestamp ) ) ? substr( $last_newsletter, $counter_separator + 1 ) : 0;
		$newsletter_limit = $UserSettings->get( 'newsletter_limit',  $edited_User->ID );
		$Form->info_field( TB_('Lists already sent today'), sprintf( TB_('%d out of a maximum allowed of %d'), $newsletter_counter, $newsletter_limit ) );
	}

	// Display date/time of the latest inactive account email reminder:
	$last_inactive_status_email = $UserSettings->get( 'last_inactive_status_email', $edited_User->ID );
	$Form->info_field( TB_('Latest inactive account email'), empty( $last_inactive_status_email ) ? TB_('None yet') : mysql2localedatetime( $last_inactive_status_email ) );
$Form->end_fieldset(); // Email info

$Form->begin_fieldset( TB_('Usage info').get_manual_link('user-admin-usage') );

	$activity_tab_url = '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab=activity';

	$Form->info_field( TB_('ID'), $edited_User->ID );

	// Other users reports from the edited User
	$Form->info_field( TB_('Reports'), count_reports_from( $edited_User->ID ) );

	// Number of blogs owned by the edited User
	$blogs_owned_num = $edited_User->get_num_blogs();
	$blogs_owned = $blogs_owned_num;
	if( $blogs_owned > 0 )
	{
		$blogs_owned .= ' - <a href="'.$activity_tab_url.'#owned_blogs_result" class="'.button_class().' middle" title="'.format_to_output( TB_('Go to user activity'), 'htmlattr' ).'">'.get_icon( 'magnifier', 'imgtag', array( 'title' => TB_('Go to user activity') ) ).'</a>';
	}
	$Form->info_field( TB_('Blogs owned'), $blogs_owned, array( 'class' => $blogs_owned_num > 0 ? 'info_full_height' : '' ) );

	// Number of post created and edited by the edited User:
	$posts_created = $edited_User->get_num_posts();
	$posts_edited = $edited_User->get_num_edited_posts();
	$Form->begin_line( TB_('Posts created'), NULL, ( $posts_created > 0 || $posts_edited > 0 ) ? '' : 'info' );
		if( $posts_created > 0 )
		{
			$posts_created .= ' - <a href="'.$activity_tab_url.'#created_posts_result" class="'.button_class().' middle" title="'.format_to_output( TB_('Go to user activity'), 'htmlattr' ).'">'.get_icon( 'magnifier', 'imgtag', array( 'title' => TB_('Go to user activity') ) ).'</a>';
			if( $action != 'view' )
			{	// If current user can edit this user:
				$posts_created .= ' - '.action_icon( TB_('Delete All').'...', 'delete', $admin_url.'?ctrl=user&amp;user_tab=deldata&amp;user_ID='.$edited_User->ID, ' '.TB_('Delete All').'...', 3, 4, array( 'onclick' => 'return user_deldata( '.$edited_User->ID.', \''.get_param( 'user_tab' ).'\')' ) );
				$posts_created .= get_manual_link( 'delete-user-data' );
			}
		}
		$Form->info_field( '', $posts_created );
		if( $posts_edited > 0 )
		{
			$posts_edited .= ' - <a href="'.$activity_tab_url.'#edited_posts_result" class="'.button_class().' middle" title="'.format_to_output( TB_('Go to user activity'), 'htmlattr' ).'">'.get_icon( 'magnifier', 'imgtag', array( 'title' => TB_('Go to user activity') ) ).'</a>';
		}
		$Form->info_field( '<b class="evo_label_inline">'.TB_('Edited').': </b>', $posts_edited );
	$Form->end_line( NULL, ( $posts_created > 0 || $posts_edited > 0 ) ? '' : 'info' );

	// Number of comments created by the edited User
	evo_flush(); // The following might take a while on systems with many comments
	// Get the number of edited User comments, but count recycled comments only if the user has global editall blogs permission
	$comments_created_num = $edited_User->get_num_comments( '', check_user_perm( 'blogs', 'editall', false ) );
	$comments_created = $comments_created_num;
	if( $comments_created > 0 )
	{
		$comments_created .= ' - <a href="'.$activity_tab_url.'#comments_result" class="'.button_class().' middle" title="'.format_to_output( TB_('Go to user activity'), 'htmlattr' ).'">'.get_icon( 'magnifier', 'imgtag', array( 'title' => TB_('Go to user activity') ) ).'</a>';
		if( $action != 'view' )
		{	// If current user can edit this user:
			$comments_created .= ' - '.action_icon( TB_('Delete All').'...', 'delete', $admin_url.'?ctrl=user&amp;user_tab=deldata&amp;user_ID='.$edited_User->ID, ' '.TB_('Delete All').'...', 3, 4, array( 'onclick' => 'return user_deldata( '.$edited_User->ID.', \''.get_param( 'user_tab' ).'\')' ) );
			$comments_created .= get_manual_link( 'delete-user-data' );
		}
	}
	$Form->info_field( TB_('Comments'), $comments_created, array( 'class' => $comments_created_num > 0 ? 'info_full_height' : '' ) );

	// Number of edited User's sessions:
	$num_sessions = $edited_User->get_num_sessions( true );
	$Form->info_field( TB_('# of sessions'), $num_sessions, array( 'class' => $num_sessions > 0 ? 'info_full_height' : '' ) );

	// Number of sent and received private messages:
	$messages_sent = $edited_User->get_num_messages( 'sent' );
	$messages_received = $edited_User->get_num_messages( 'received' );
	$Form->begin_line( TB_('# of private messages sent'), NULL, ( $messages_sent > 0 || $messages_received > 0 ) ? '' : 'info' );
		if( $messages_sent > 0 )
		{
			$messages_sent .= ' - <a href="'.$activity_tab_url.'#threads_result" class="'.button_class().' middle" title="'.format_to_output( TB_('Go to user activity'), 'htmlattr' ).'">'.get_icon( 'magnifier', 'imgtag', array( 'title' => TB_('Go to user activity') ) ).'</a>';
			if( check_user_perm( 'perm_messaging', 'abuse' ) )
			{
				$messages_sent .= ' - <a href="'.$admin_url.'?ctrl=abuse&amp;colselect_submit=Filter+list&amp;u='.$edited_User->login.'">'.TB_('Go to abuse management').' &raquo;</a>';
			}
			if( $action != 'view' )
			{	// If current user can edit this user:
				$messages_sent .= ' - '.action_icon( TB_('Delete All').'...', 'delete', $admin_url.'?ctrl=user&amp;user_tab=deldata&amp;user_ID='.$edited_User->ID, ' '.TB_('Delete All').'...', 3, 4, array( 'onclick' => 'return user_deldata( '.$edited_User->ID.', \''.get_param( 'user_tab' ).'\')' ) );
				$messages_sent .= get_manual_link( 'delete-user-data' );
			}
		}
		$Form->info_field( '', $messages_sent );
		if( $messages_received > 0 && check_user_perm( 'perm_messaging', 'abuse' ) )
		{
			$messages_received .= ' - <a href="'.$admin_url.'?ctrl=abuse&amp;colselect_submit=Filter+list&amp;u='.$edited_User->login.'" class="'.button_class().' middle" title="'.format_to_output( TB_('Go to abuse management'), 'htmlattr' ).'">'.get_icon( 'magnifier', 'imgtag', array( 'title' => TB_('Go to abuse management') ) ).'</a>';
		}
		$Form->info_field( '<b class="evo_label_inline">'.TB_('Received').': </b>', $messages_received );
	$Form->end_line( NULL, ( $messages_sent > 0 || $messages_received > 0 ) ? '' : 'info' );

	$Form->begin_line( TB_('Last seen on'), NULL, 'info' );
		$edited_user_lastseen = $edited_User->get( 'lastseen_ts' );
		$Form->info_field( '', ( empty( $edited_user_lastseen ) ? '' : mysql2localedatetime( $edited_user_lastseen ) ) );
		$Form->info_field( '<b class="evo_label_inline">'.TB_('On IP').': </b>', $edited_User->get_last_session_param('ipaddress') );
	$Form->end_line( NULL, 'info' );
$Form->end_fieldset(); // Usage info

$Form->begin_fieldset( TB_('Reputation').get_manual_link('user-admin-reputation') );

	$Form->info( TB_('Posts'), $edited_User->get_reputation_posts() );

	$Form->info( TB_('Comments'), '<span class="reputation_message">'.$edited_User->get_reputation_comments( array( 'view_type' => 'extended' ) ).'</span>' );

	$Form->info( TB_('Photos'), '<span class="reputation_message">'.$edited_User->get_reputation_files( array( 'file_type' => 'image', 'view_type' => 'extended' ) ).'</span>' );

	$Form->info( TB_('Audio'), '<span class="reputation_message">'.$edited_User->get_reputation_files( array( 'file_type' => 'audio' ) ).'</span>' );

	$Form->info( TB_('Video'), '<span class="reputation_message">'.$edited_User->get_reputation_files( array( 'file_type' => 'video' ) ).'</span>' );

	$Form->info( TB_('Other files'), '<span class="reputation_message">'.$edited_User->get_reputation_files( array( 'file_type' => 'other' ) ).'</span>' );

	$Form->info( TB_('Upload total'), '<span class="repuration_message">'.$edited_User->get_reputation_total_upload().'</span>' );

	$Form->info( TB_('Spam fighter score'), '<span class="reputation_message">'.$edited_User->get_reputation_spam().'</span>' );

$Form->end_fieldset(); // Reputation

$from_country = '';
if( !empty( $edited_User->reg_ctry_ID ) )
{	// Get country that was defined by GeoIP Plugin on registration
	load_class( 'regional/model/_country.class.php', 'Country' );
	load_funcs( 'regional/model/_regional.funcs.php' );
	$CountryCache = & get_CountryCache();
	$Country = $CountryCache->get_by_ID( $edited_User->reg_ctry_ID );
	$from_country = country_flag( $Country->get( 'code' ), $Country->get_name(), 'w16px', 'flag', '', false, true, 'margin-bottom:3px;vertical-align:middle;' ).' '.$Country->get_name();
}

// Get field suffix for a field 'From Country' from the Plugins
$user_from_country_suffix = '';
$Plugins->restart();
while( $loop_Plugin = & $Plugins->get_next() )
{
	$tmp_params = array( 'User' => & $edited_User );
	$user_from_country_suffix .= $loop_Plugin->GetUserFromCountrySuffix( $tmp_params );
}

$Form->begin_fieldset( TB_('Registration info').get_manual_link('user-admin-registration') );
	$user_ip_address = int2ip( $UserSettings->get( 'created_fromIPv4', $edited_User->ID ) );
	$Form->begin_line( TB_('Account registered on'), NULL, 'info' );
		$Form->info_field( '', mysql2localedatetime( $edited_User->dget('datecreated') ), array( 'note' => '('.date_ago( strtotime( $edited_User->get( 'datecreated' ) ) ).')') );
		$Form->info_field( '<b class="evo_label_inline">'.TB_('From IP').': </b>',
			$user_ip_address.( empty( $user_ip_address ) ? '' : ' <a href="'.$admin_url.'?ctrl=antispam&amp;action=whois&amp;query='.$user_ip_address.'" class="btn btn-info middle" onclick="return get_whois_info(\''.$user_ip_address.'\');">'.get_icon( 'magnifier' ).'</a>' ) );
	$Form->end_line( NULL, 'info' );

	if( check_user_perm( 'spamblacklist', 'view' ) )
	{ // User can view IP ranges
		// Get status and name of IP range
		$IPRangeCache = & get_IPRangeCache();
		if( $IPRange = & $IPRangeCache->get_by_ip( $user_ip_address ) )
		{ // IP range exists in DB
			$iprange_status = $IPRange->get( 'status' );
			$iprange_name = $IPRange->get_name();
			if( check_user_perm( 'spamblacklist', 'view' ) )
			{	// Display IP range as link to edit form if current user has the permissions:
				$iprange_name = '<a href="'.$admin_url.'?ctrl=antispam&amp;tab3=ipranges&amp;action=iprange_edit&amp;iprange_ID='.$IPRange->ID.'">'.$iprange_name.'</a>';
			}
		}
		else
		{ // There is no IP range in DB
			$iprange_status = '';
			$iprange_name = '';
		}
		$perm_spamblacklist = check_user_perm( 'spamblacklist', 'edit' );
		$Form->begin_line( TB_('IP range'), NULL, ( $perm_spamblacklist ? '' : 'info' ) );
			$Form->info_field( '', $iprange_name );
			$email_status_icon = '<div id="iprange_status_icon" class="status_icon">'.aipr_status_icon( $iprange_status ).'</div>';
			if( $perm_spamblacklist )
			{ // User can edit IP ranges
				$Form->select_input_array( 'edited_iprange_status', $iprange_status, aipr_status_titles( true ), '<b class="evo_label_inline">'.TB_( 'Status' ).': </b>'.$email_status_icon, '', array( 'force_keys_as_values' => true, 'background_color' => aipr_status_colors() ) );
			}
			else
			{ // Only view status of IP range
				$Form->info( '<b class="evo_label_inline">'.TB_( 'Status' ).': </b>', $email_status_icon.aipr_status_title( $iprange_status ) );
			}
		$Form->end_line( NULL, ( $perm_spamblacklist ? '' : 'info' ) );
	}

	$Form->info_field( TB_('From Country'), $from_country, array( 'field_suffix' => $user_from_country_suffix ) );

	user_domain_info_display( TB_('From Domain'), 'domain_status', $UserSettings->get( 'user_registered_from_domain', $edited_User->ID ), $Form );

	$Form->info_field( TB_('With Browser'), format_to_output( $UserSettings->get( 'user_browser', $edited_User->ID ) ) );

	$Form->text_input( 'edited_user_source', $edited_User->source, 30, TB_('Source link/code'), '', array( 'maxlength' => 30 ) );

	$Form->info_field( TB_('Registration trigger Page'), $UserSettings->get( 'registration_trigger_url', $edited_User->ID ) );

	$Form->begin_line( TB_('Initial Blog ID'), NULL, 'info' );
		$Form->info_field( '', $UserSettings->get( 'initial_blog_ID', $edited_User->ID ) );
		$Form->info_field( '<b class="evo_label_inline">'.TB_('Initial URI').': </b>', $UserSettings->get( 'initial_URI', $edited_User->ID ) );
	$Form->end_line( NULL, 'info' );

	$perm_stat_edit = check_user_perm( 'stats', 'edit' );
	$initial_referer = $UserSettings->get( 'initial_referer', $edited_User->ID );
	$display_initial_referer = ( ! empty( $initial_referer ) && check_user_perm( 'stats', 'list' ) );
	$Form->begin_line( TB_('Initial referer'), NULL, ( $display_initial_referer && $perm_stat_edit ? '' : 'info' ) );
		$Domain = & get_Domain_by_url( $initial_referer );
		$initial_referer_formatted = format_to_output( $initial_referer );
		if( $Domain && $perm_stat_edit )
		{
			$initial_referer_formatted = preg_replace( '#^(.+)('.preg_quote( trim( $Domain->get( 'name' ), '.' ), '#' ).')(/(.+)?|$)#i',
				'$1<a href="'.$admin_url.'?ctrl=stats&amp;tab=domains&amp;action=domain_edit&amp;dom_ID='.$Domain->ID.'" '
					.'title="'.format_to_output( sprintf( TB_('Edit domain %s'), $Domain->get( 'name' ) ), 'htmlattr' ).'">$2</a>$3',
				$initial_referer_formatted );
		}
		$Form->info_field( '', '<a href="'.$initial_referer.'" target="_blank">'.get_icon( 'permalink' ).'</a> '.$initial_referer_formatted );
		if( $display_initial_referer )
		{ // User can view Domains
			$domain_status = $Domain ? $Domain->get( 'status' ) : 'unknown';
			$domain_status_icon = '<div id="initial_referer_status_icon" class="status_icon">'.stats_dom_status_icon( $domain_status ).'</div>';
			if( $perm_stat_edit )
			{ // User can edit Domain
				global $admin_url;
				$initial_referer_domain = $domain_name = url_part( $initial_referer, 'host' );
				$domain_status_action = '';
				if( !$Domain || $initial_referer_domain != $Domain->get( 'name' ) )
				{ // Link to create a new domain
					$domain_status_action .= action_icon( sprintf( TB_('Add domain %s'), $initial_referer_domain ), 'new', $admin_url.'?ctrl=stats&amp;tab=domains&amp;action=domain_new&amp;dom_name='.$initial_referer_domain.'&amp;dom_status=blocked&amp;dom_type=normal' );
				}
				if( $Domain )
				{ // Link to edit existing domain
					$domain_status_action .= action_icon( sprintf( TB_('Edit domain %s'), $Domain->get( 'name' ) ), 'edit', $admin_url.'?ctrl=stats&amp;tab=domains&amp;action=domain_edit&amp;dom_ID='.$Domain->ID );
				}
				$Form->select_input_array( 'edited_initial_referer_status', $domain_status, stats_dom_status_titles(), '<b class="evo_label_inline">'.TB_( 'Status' ).': </b>'.$domain_status_icon, '', array( 'force_keys_as_values' => true, 'background_color' => stats_dom_status_colors(), 'field_suffix' => $domain_status_action ) );
			}
			else
			{ // Only view status of Domain
				$Form->info( '<b class="evo_label_inline">'.TB_( 'Status' ).': </b>', $domain_status_icon.stats_dom_status_title( $domain_status ) );
			}
		}
	$Form->end_line( NULL, ( $display_initial_referer && $perm_stat_edit ? '' : 'info' ) );

	//$registration_ts = strtotime( $edited_User->get( 'datecreated' ) );
	if( $edited_User->check_status( 'is_closed' ) )
	{
		$account_close_ts = $UserSettings->get( 'account_close_ts', $edited_User->ID );
		$account_close_date =  empty( $account_close_ts ) ? TB_( 'Unknown date' ) : format_to_output( date2mysql( $account_close_ts ) );
		//$days_on_site = empty( $account_close_ts ) ? TB_( 'Unknown' ) : ( round( ( $account_close_ts - $registration_ts ) / 86400/* 60*60*24 */) );
	}
	else
	{
		$account_close_date = /* TRANS: "Not Available" */ TB_('N/A');
		//$days_on_site = ( round( ( $servertimenow - $registration_ts ) / 86400/* 60*60*24 */) );
	}

	$Form->info_field( TB_('Account closed on'), $account_close_date );
	$textarea_params = array( 'cols' => 40, 'maxlength' => 255, 'style' =>'resize: none' );
	if( $edited_User->ID == 1 )
	{
		$textarea_params['disabled'] = "disabled";
	}
	$Form->textarea_input( 'account_close_reason', $UserSettings->get( 'account_close_reason', $edited_User->ID ), 4, TB_('Account close reason'), $textarea_params );
	//$Form->info_field( TB_('Days on site'), $days_on_site );

$Form->end_fieldset(); // Registration info

if( $action != 'view' )
{	// If current user can edit this user:
	$Form->buttons( array( array( '', 'actionArray[update]', TB_('Save Changes!'), 'SaveButton' ) ) );
}

$Form->end_form();

// End payload block:
$this->disp_payload_end();
?>
<script>
var user_status_icons = new Array;
<?php
foreach( $user_status_icons as $status => $icon )
{	// Init js array with user status icons
?>
user_status_icons['<?php echo $status; ?>'] = '<?php echo format_to_js( $icon ); ?>';
<?php } ?>

jQuery( '#edited_user_status' ).change( function()
{	// Change icon of the user status
	if( typeof user_status_icons[ jQuery( this ).val() ] != 'undefined' )
	{
		jQuery( '#user_status_icon' ).html( user_status_icons[ jQuery( this ).val() ] );
	}
	else
	{
		jQuery( '#user_status_icon' ).html( '' );
	}
} );

<?php
if( check_user_perm( 'users', 'edit' ) )
{ // START OF email status change script
?>
var email_status_icons = new Array;
<?php
$email_status_icons = emadr_get_status_icons();
foreach( $email_status_icons as $status => $icon )
{	// Init js array with email status icons
?>
email_status_icons['<?php echo $status; ?>'] = '<?php echo format_to_js( $icon ); ?>';
<?php } ?>

jQuery( '#edited_email_status' ).change( function()
{	// Change icon of the email status
	if( typeof email_status_icons[ jQuery( this ).val() ] != 'undefined' )
	{
		jQuery( '#email_status_icon' ).html( email_status_icons[ jQuery( this ).val() ] );
	}
	else
	{
		jQuery( '#email_status_icon' ).html( '' );
	}
} );

var current_email = '<?php echo format_to_js( $edited_User->get( 'email' ) ); ?>';
jQuery( 'input#edited_user_email' ).keyup( function()
{	// Disable/Enable to select email status when email address is changed
	if( current_email != jQuery( this ).val() )
	{	// Disable
		if( jQuery( '#edited_email_status' ).html() != '' )
		{
			email_status_selected = jQuery( '#edited_email_status option:selected' ).val();
			email_status_options = jQuery( '#edited_email_status' ).html();
		}
		//alert(email_status_options);
		jQuery( '#edited_email_status' ).html( '' )
			.attr( 'disabled', 'disabled' );
		jQuery( '#email_status_icon' ).hide();
	}
	else
	{	// Enable
		jQuery( '#edited_email_status' ).removeAttr( 'disabled' )
			.html( email_status_options );
		jQuery( '#edited_email_status option[value=' + email_status_selected + ']' ).attr( 'selected', 'selected' );
		jQuery( '#email_status_icon' ).show();
	}
} );
<?php } // END OF email status change script ?>

<?php
if( check_user_perm( 'spamblacklist', 'edit' ) )
{ // User can edit IP ranges
?>
var iprange_status_icons = new Array;
<?php
$iprange_status_icons = aipr_status_icons();
foreach( $iprange_status_icons as $status => $icon )
{ // Init js array with IP range status icons
?>
iprange_status_icons['<?php echo $status; ?>'] = '<?php echo format_to_js( $icon ); ?>';
<?php } ?>

jQuery( '#edited_iprange_status' ).change( function()
{ // Change icon of the ip range status
	if( typeof iprange_status_icons[ jQuery( this ).val() ] != 'undefined' )
	{
		jQuery( '#iprange_status_icon' ).html( iprange_status_icons[ jQuery( this ).val() ] );
	}
	else
	{
		jQuery( '#iprange_status_icon' ).html( '' );
	}
} );
<?php } ?>

<?php
if( check_user_perm( 'stats', 'edit' ) )
{ // User can edit Domain
?>
var domain_status_icons = new Array;
<?php
$domain_status_icons = stats_dom_status_icons();
foreach( $domain_status_icons as $status => $icon )
{ // Init js array with Domain status icons
?>
domain_status_icons['<?php echo $status; ?>'] = '<?php echo format_to_js( $icon ); ?>';
<?php } ?>

jQuery( '#edited_domain_status, #edited_initial_referer_status, #edited_email_domain_status' ).change( function()
{ // Change icon of the domain status
	if( typeof domain_status_icons[ jQuery( this ).val() ] != 'undefined' )
	{
		jQuery( this ).prev().html( domain_status_icons[ jQuery( this ).val() ] );
	}
	else
	{
		jQuery( this ).prev().html( '' );
	}
} );
<?php } ?>
</script>