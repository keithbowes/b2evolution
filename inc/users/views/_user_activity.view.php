<?php /**
 * This file implements the UI view for the user's activity on user profile page.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var instance of User class
 */
global $edited_User;
/**
 * @var current action
 */
global $action;
/**
 * @var user permission, if user is only allowed to edit his profile
 */
global $user_profile_only;

global $user_tab, $user_ID;

global $current_User, $UserSettings;

if( $current_User->ID != $user_ID && ! $current_User->can_moderate_user( $edited_User->ID ) )
{ // Check permission:
	debug_die( T_( 'You have no permission to see this tab!' ) );
}


memorize_param( 'user_tab', 'string', '', $user_tab );
memorize_param( 'user_ID', 'integer', 0, $user_ID );

// ------------------- PREV/NEXT USER LINKS -------------------
user_prevnext_links( array(
		'user_tab' => 'activity'
	) );
// ------------- END OF PREV/NEXT USER LINKS -------------------

$usertab_header = get_usertab_header( $edited_User, $user_tab, '<span class="nowrap">'.( $current_User->ID == $edited_User->ID ? T_('My Activity') : T_('User Activity') ).'</span>'.get_manual_link( 'user-activity-tab' ) );

if( !$user_profile_only )
{ // echo user edit action icons
	$Widget = new Widget();
	echo_user_actions( $Widget, $edited_User, $action );
	$usertab_header = str_replace( '$global_icons$', $Widget->gen_global_icons(), $usertab_header );
}

echo $usertab_header;

// Display IP address from where this user was created
echo '<div style="margin-top:25px"><b>'.T_('User created from IP').': '.int2ip( $UserSettings->get( 'created_fromIPv4', $edited_User->ID ) ).'</b></div>';

// Display user tags:
$user_tags_SQL = new SQL( 'Get tags for the User #'.$edited_User->ID );
$user_tags_SQL->SELECT( 'utag_name' );
$user_tags_SQL->FROM( 'T_users__tag' );
$user_tags_SQL->FROM_add( 'INNER JOIN T_users__usertag ON uutg_emtag_ID = utag_ID' );
$user_tags_SQL->WHERE( 'uutg_user_ID = '.$edited_User->ID );
$user_tags_SQL->ORDER_BY( 'utag_name' );
$user_tags = $DB->get_col( $user_tags_SQL );
echo '<div style="margin-top:25px"><b>'.T_('User Tags').':</b> ';
foreach( $user_tags as $user_tag )
{
	echo '<span class="label label-info">'.$user_tag.'</span> ';
}
echo '</div>';

/**** Emails sent to the User ****/
user_sent_emails_results_block( array(
		'edited_User' => $edited_User,
		'action'      => $action,
	) );
evo_flush();

/**** Email returns from the User's email address ****/
user_email_returns_results_block( array(
		'edited_User' => $edited_User,
		'action'      => $action,
	) );
evo_flush();

/**** Reports of the User by other Users ****/
user_reports_results_block( array(
		'edited_User' => $edited_User,
	) );
evo_flush();

/**** Collections owned by the User ****/
blogs_user_results_block( array(
		'edited_User' => $edited_User,
		'action'      => $action,
	) );
evo_flush();

/**** Posts created by the user ****/
items_created_results_block( array(
		'edited_User' => $edited_User,
		'action'      => $action,
	) );
evo_flush();

/**** Posts edited by the user ****/
items_edited_results_block( array(
		'edited_User' => $edited_User,
		'action'      => $action,
	) );
evo_flush();

/**** Comments posted by the user ****/
comments_results_block( array(
		'edited_User' => $edited_User,
		'action'      => $action,
	) );
evo_flush();

/**** Private messages sent by the user ****/
threads_results_block( array(
		'edited_User' => $edited_User,
		'action'      => $action,
	) );
evo_flush();

/**** Private messages received by the user ****/
received_threads_results_block( array(
		'edited_User' => $edited_User,
		'action'      => $action,
	) );
evo_flush();

/**** Polls owned by the user ****/
polls_results_block( array(
		'edited_User'          => $edited_User,
		'action'               => $action,
		'results_title'        => T_('Polls owned by the user'),
		'manual_link'          => '',
		'display_filters'      => false,
		'display_owner'        => false,
		'display_btn_add'      => false,
		'display_btn_user_del' => true,
	) );
evo_flush();


if( $action != 'view' && $current_User->ID != $edited_User->ID && $edited_User->ID != 1 && check_user_perm( 'users', 'edit' ) )
{ // User can NOT delete admin and own account
	echo '<div style="margin:25px 0">';
	echo action_icon( T_('Delete User and All his contributions'), 'delete', '?ctrl=user&amp;user_tab=activity&amp;action=delete_all_userdata&amp;user_ID='.$edited_User->ID.'&amp;'.url_crumb('user'), ' '.T_('Delete User and All his contributions'), 3, 4, array( 'class' => 'btn btn-danger' ) );
	echo action_icon( T_('Delete User and All his contributions').' + '.T_('Mark as email Spammer'), 'delete', '?ctrl=users&amp;action=delete&amp;deltype=spammer&amp;user_ID='.$edited_User->ID, ' '.T_('Delete User and All his contributions').' + '.T_('Mark as email Spammer'), 3, 4, array( 'class' => 'btn btn-danger', 'style' => 'margin-left: 1ex;' ) );
	echo '</div>';
}

?>
