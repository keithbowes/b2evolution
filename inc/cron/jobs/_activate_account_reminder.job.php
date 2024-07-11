<?php
/**
 * This file implements the account activation email reminder cron job
 *
 * @author attila: Attila Simo
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $DB, $UserSettings, $Settings;

global $servertimenow, $baseurl;

if( $Settings->get( 'validation_process' ) != 'easy' )
{
	cron_log_append( 'With secure activation process sending reminder emails is not permitted!', 'error' );
	return 2; /* error */
}

if( empty( $UserSettings ) )
{ // initialize UserSettings, because in CLI mode is not initialized yet
	load_class( 'users/model/_usersettings.class.php', 'UserSettings' );
	$UserSettings = new UserSettings();
}

// Only users with "New", "Email changed", "Deactivated", "Failed activation" or "Pending delete" statuses may receive activation reminders
// This will be a precondition to get less users from db, but this will be checked again with check_status() in the send_easy_validate_emails() function
$status_condition = 'user_status IN ( "new", "emailchanged", "deactivated", "failedactivation", "pendingdelete" )';

// Get array of account activation reminder settings:
$activate_account_reminder_config = $Settings->get( 'activate_account_reminder_config' );

// Set configuration details from $activate_account_reminder_config array
$number_of_max_reminders = ( count( $activate_account_reminder_config ) - 1 );
if( $number_of_max_reminders < 3 )
{ // The config array is wrong, it must have at least 4 elements (Reminder #1, Mark as failed, Delete warning, Delete account)
	cron_log_append( 'The job advanced configuration is wrong, can\'t send reminders!', 'error' );
	return 3; /* error */
}

$reminder_delay_conditions = array();
for( $i = 0; $i <= $number_of_max_reminders; $i++ )
{
	$reminder_date = date2mysql( $servertimenow - $activate_account_reminder_config[$i] );
	$reminder_delay_conditions[] = ( $i == 0
		? '( ( last_sent.uset_value IS NULL OR last_sent.uset_value < '.$DB->quote( $reminder_date ).' ) AND ( reminder_sent.uset_value IS NULL OR reminder_sent.uset_value = "'.$i.'" ) )'
		: '( last_sent.uset_value < '.$DB->quote( $reminder_date ).' AND reminder_sent.uset_value = "'.$i.'" )' );
}

$SQL = new SQL( 'Get users which should be reminded or deleted because of they are not activated' );
$SQL->SELECT( 'T_users.user_ID, T_users.user_status, reminder_sent.uset_value' );
$SQL->FROM( 'T_users' );
// join UserSettings
$SQL->FROM_add( 'LEFT JOIN T_users__usersettings last_sent ON last_sent.uset_user_ID = user_ID AND last_sent.uset_name = "last_activation_email"' );
$SQL->FROM_add( 'LEFT JOIN T_users__usersettings notif_setting ON notif_setting.uset_user_ID = user_ID AND notif_setting.uset_name = "send_activation_reminder"' );
$SQL->FROM_add( 'LEFT JOIN T_users__usersettings reminder_sent ON reminder_sent.uset_user_ID = user_ID AND reminder_sent.uset_name = "activation_reminder_count"' );
// check that user status is 'new' or 'emailchanged' or 'deactivated', and send reminders only for these users.
$SQL->WHERE( $status_condition );
// check if user has an email address
$SQL->WHERE_and( 'LENGTH(TRIM(user_email)) > 0' );
// check that user email is not blocked
$SQL->WHERE_and( 'user_email NOT IN ( SELECT emadr_address FROM T_email__address WHERE '.get_mail_blocked_condition().' )' );
// check that user was created more than x ( = confugred activate account reminder threshold ) seconds ago!
$threshold_date = date2mysql( $servertimenow - $Settings->get( 'activate_account_reminder_threshold' ) );
$SQL->WHERE_and( 'user_created_datetime < '.$DB->quote( $threshold_date ) );
// check how many reminders was sent to the user and when => send reminders only if required
$SQL->WHERE_and( implode( ' OR ', $reminder_delay_conditions ) );
// check if user wants to recevice activation reminder or not
$SQL->WHERE_and( 'notif_setting.uset_value IS NULL OR notif_setting.uset_value <> '.$DB->quote( '0' ) );
$reminder_users = $DB->get_results( $SQL );

$all_reminder_users = array();
$send_activation_users = array(); // Users for cron settings "Reminder #X"
$mark_failed_users = array(); // Users for cron setting "Mark as Failed / Pending delete"
$send_delete_warning_users = array(); // Users for cron setting "Delete warning"
$delete_account_users = array(); // Users for cron setting "Delete account"
foreach( $reminder_users as $reminder_user )
{
	$all_reminder_users[] = $reminder_user->user_ID;
	$activation_reminder_count = $reminder_user->uset_value;
	if( $reminder_user->user_status == 'pendingdelete' &&
	    ( // If this is the last reminder number and the option "Delete account" is enabled
	      ( $activation_reminder_count == $number_of_max_reminders && ! empty( $activate_account_reminder_config[ $activation_reminder_count ] ) ) ||
	      // Case when "Delete warning" is not enabled(=="Don't send") but "Delete account" is enabled, so we should use penultimate reminder number as last("Delete account") in order to skip the reminder of "Delete warning":
	      ( $activation_reminder_count == $number_of_max_reminders - 1 && empty( $activate_account_reminder_config[ $number_of_max_reminders - 1 ] ) && ! empty( $activate_account_reminder_config[ $number_of_max_reminders ] ) )
	    ) )
	{	// This user must be deleted completely ONLY if it is in "Pending delete" status:
		$delete_account_users[] = $reminder_user->user_ID;
	}
	elseif( $reminder_user->user_status == 'pendingdelete' &&
	        $activation_reminder_count == $number_of_max_reminders - 1 && // If this is the penultimate reminder number
	        ! empty( $activate_account_reminder_config[ $activation_reminder_count ] ) ) // If the option "Delete warning" is enabled
	{	// This user must receive a delete warning email ONLY if it is in "Pending delete" status:
		$send_delete_warning_users[] = $reminder_user->user_ID;
	}
	elseif( $activation_reminder_count == $number_of_max_reminders - 2 )
	{	// This user must be marked with status "Failed activation" or "Pending delete":
		$mark_failed_users[] = $reminder_user->user_ID;
	}
	elseif( $activation_reminder_count <= $number_of_max_reminders - 3 )
	{	// This user must receive an activation email:
		$send_activation_users[] = $reminder_user->user_ID;
	}
}

$UserCache = & get_UserCache();
$UserCache->clear();
// load all users to reminded into the UserCache:
$UserCache->load_list( $all_reminder_users );

// ---- #1 Send activation reminder:
$send_activation_users_num = count( $send_activation_users );
$success_sent_activation_users_num = send_easy_validate_emails( $send_activation_users, true, false, NULL, 'cron_job' );
cron_log_append( sprintf( T_('%d of %d account activation reminder emails were sent!'), $success_sent_activation_users_num, $send_activation_users_num ),
	( $send_activation_users_num ? ( $send_activation_users_num == $success_sent_activation_users_num ? 'success' : 'warning' ) : NULL ) );

// ---- #2 Mark users as failed:
$mark_failed_users_num = count( $mark_failed_users );
if( $mark_failed_users_num )
{	// Change account status for all users who didn't receive activation reminder or account validation email in the last days of the setting "Mark as Failed / Pending delete",
	// If status was "New" - change status to "Pending delete" otherwise "Failed activation":
	$success_mark_failed_users_num = $DB->query( 'UPDATE T_users
		  SET user_status = CASE
		    WHEN user_status = "new" THEN "pendingdelete"
		    ELSE "failedactivation"
		  END
		WHERE user_ID IN ( '.$DB->quote( $mark_failed_users ).' )
		  AND '.$status_condition );
	// Increase counter and date in order to run next reminder:
	$DB->query( 'UPDATE T_users__usersettings
		INNER JOIN T_users ON uset_user_ID = user_ID
		  SET uset_value = CASE
		    WHEN uset_name = "activation_reminder_count" THEN uset_value + 1
		    WHEN uset_name = "last_activation_email" THEN '.$DB->quote( date2mysql( $servertimenow ) ).'
		    ELSE uset_value
		  END
		WHERE user_ID IN ( '.$DB->quote( $mark_failed_users ).' )
		  AND user_status IN ( "failedactivation", "pendingdelete" )
		  AND uset_name IN ( "activation_reminder_count", "last_activation_email" ) ' );
	// Display this as action because here some users may be updated:
	cron_log_action_end( "\n".sprintf( '%d of %d users(with IDs: %s) were marked with status "Failed activation" or "Pending delete"!',
			$success_mark_failed_users_num, $mark_failed_users_num, implode( ', ', $mark_failed_users ) )."\n",
		( $mark_failed_users_num ? ( $mark_failed_users_num == $success_mark_failed_users_num ? 'success' : 'warning' ) : NULL ) );
}
else
{	// Don't display this as action because no users to update a status:
	cron_log_append( "\n".sprintf( '%d users were marked with status "Failed activation" or "Pending delete"!', 0 )."\n" );
}

// ---- #3 Send a delete warning reminder:
$send_delete_warning_users_num = count( $send_delete_warning_users );
$succes_sent_delete_warning_users_num = check_cron_job_emails_limit() // Run email sending only when max emails number was not reached in the same function above
	? send_easy_validate_emails( $send_delete_warning_users, true, false, NULL, 'cron_job', 'account_delete_warning' )
	: 0;
cron_log_append( sprintf( '%d of %d delete warning reminder emails were sent!', $succes_sent_delete_warning_users_num, $send_delete_warning_users_num )."\n",
	( $send_delete_warning_users_num ? ( $send_delete_warning_users_num == $succes_sent_delete_warning_users_num ? 'success' : 'warning' ) : NULL ) );

// ---- #4 Delete users:
$succes_deleted_users_num = 0;
$delete_users_num = count( $delete_account_users );
if( $delete_users_num )
{
	// Delete private messages, comments and files, but don't mark the deleted users as spammers:
	set_param( 'force_delete_messages', 1 );
	set_param( 'force_delete_comments', 1 );
	set_param( 'force_delete_files', 1 );

	foreach( $delete_account_users as $delete_account_user_ID )
	{
		if( ! ( $deleted_User = & $UserCache->get_by_ID( $delete_account_user_ID, false, false ) ) )
		{	// Wrong user:
			cron_log_action_end( 'User #'.$delete_account_user_ID.' is not found.', 'error' );
			continue;
		}

		$deleted_user_login = $deleted_User->get_identity_link();
		if( $deleted_User->dbdelete() )
		{
			cron_log_action_end( 'User #'.$delete_account_user_ID.' '.$deleted_user_login.' was deleted.' );
			$succes_deleted_users_num++;
		}
		else
		{
			cron_log_action_end( 'User #'.$delete_account_user_ID.' '.$deleted_user_login.' cannot be deleted.', 'error' );
		}
	}
}
cron_log_append( sprintf( '%d users were deleted!', $succes_deleted_users_num ),
	( $delete_users_num ? ( $delete_users_num == $succes_deleted_users_num ? 'success' : 'warning' ) : NULL ) );

return 1; /* ok */
?>
