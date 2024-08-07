<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var AdminUI_general
 */
global $AdminUI;

param( 'user_tab', 'string', '', true );
if( empty( $user_tab ) )
{
	$user_tab = 'profile';
}
elseif( $user_tab == 'social' && ! is_pro() )
{	// Don't allow social accounts tabs for non-PRO:
	$user_tab = 'profile';
}

$AdminUI->set_path( 'users', 'users' );

param_action();

param( 'user_ID', 'integer', NULL );	// Note: should NOT be memorized (would kill navigation/sorting) use memorize_param() if needed
param( 'redirect_to', 'url', NULL );

param( 'display_mode', 'string', 'normal' );

/**
 * @global boolean true, if user is only allowed to edit his profile
 */
$user_profile_only = ! check_user_perm( 'users', 'view' );

if( $user_profile_only )
{ // User has no permissions to view: he can only edit his profile

	if( isset($user_ID) && $user_ID != $current_User->ID )
	{ // User is trying to edit something he should not: add error message (Should be prevented by UI)
		$Messages->add( TB_('You have no permission to view other users!'), 'error' );
	}

	// Make sure the user only edits himself:
	$user_ID = $current_User->ID;
	if( ! in_array( $action, array( 'update', 'update_avatar', 'upload_avatar', 'remove_avatar', 'delete_avatar', 'rotate_avatar_90_left', 'rotate_avatar_180', 'rotate_avatar_90_right', 'crop', 'edit', 'default_settings', 'redemption' ) ) )
	{
		$action = 'edit';
	}
}

if( $action == 'new' )
{	// Check permission, only admins can create new user:
	check_user_perm( 'users', 'edit', true );
}

/*
 * Load editable objects and set $action (while checking permissions)
 */

$UserCache = & get_UserCache();

if( ! is_null( $user_ID ) )
{ // User selected
	if( $action == 'update' && $user_ID == 0 )
	{ // we create a new user
		$edited_User = new User();
		$edited_User->set_datecreated( $localtimenow );
	}
	elseif( ( $edited_User = & $UserCache->get_by_ID( $user_ID, false ) ) === false )
	{	// We could not find the User to edit:
		unset( $edited_User );
		forget_param( 'user_ID' );
		$Messages->add( sprintf( TB_('Requested &laquo;%s&raquo; object does not exist any longer.'), TB_('User') ), 'error' );
		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( '?ctrl=users', 303 ); // Will EXIT
		// We have EXITed already at this point!!
	}

	if( $action != 'view' )
	{ // check edit permissions
		if( ! $current_User->can_moderate_user( $edited_User->ID )
		    && $edited_User->ID != $current_User->ID )
		{ // user is only allowed to _view_ other user's profiles
			$Messages->add( TB_('You have no permission to edit other users!'), 'error' );
			if( in_array( $user_tab, array( 'pwdchange', 'marketing', 'admin', 'sessions', 'activity', 'social' ) ) )
			{	// Don't allow the restricted pages for view:
				$user_tab = 'profile';
			}
			$action = 'view';
		}
		elseif( $demo_mode && ( $edited_User->ID <= 7 ) )
		{ // Demo mode restrictions: users created by install process cannot be edited
			$Messages->add( TB_('You cannot edit the admin and demo users profile in demo mode!'), 'error' );

			if( strpos( $action, 'delete_' ) === 0 || $action == 'promote' )
			{   // Fallback to list/view action
				header_redirect( regenerate_url( 'ctrl,action', 'ctrl=users&action=list', '', '&' ) );
			}
			else
			{
				$action = 'view';
			}
		}
		elseif( $user_tab == 'visits' && $Settings->get( 'enable_visit_tracking' ) != 1 )
		{
			$Messages->add( TB_('Visit tracking is not enabled.') );
			header_redirect( '?ctrl=users&user_tab=profile&user_ID='.$current_User->ID, 403 );
		}

		$user_tags = implode( ', ', $edited_User->get_usertags() );
	}
}
elseif( $action != 'new' )
{ // user ID is not set, edit the current user
	$user_ID = $current_User->ID;
	$edited_User = $current_User;
}

/*
 * Perform actions, if there were no errors:
 */
if( !$Messages->has_errors() )
{ // no errors
	switch( $action )
	{
		case 'new':
			// We want to create a new user:
			if( isset( $edited_User ) )
			{ // We want to use a template
				$new_User = $edited_User; // Copy !
				$new_User->set( 'ID', 0 );
				$edited_User = & $new_User;
			}
			else
			{ // We use an empty user:
				$edited_User = new User();
				$edited_User->set( 'status', 'manualactivated' );
			}
			break;

		case 'remove_avatar':
			// Remove profile picture
		case 'forbid_avatar':
			// Forbid profile picture

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			if( empty( $edited_User ) || ! is_object( $edited_User ) )
			{
				$Messages->add( 'No user set!' ); // Needs no translation, should be prevented by UI.
				$action = 'list';
				break;
			}

			if( ! $edited_User->remove_avatar( ( $action == 'forbid_avatar' ) ) )
			{ // could not remove/forbid the avatar
				$action = 'view';
				break;
			}

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=user&user_tab='.$user_tab.'&user_ID='.$edited_User->ID, 303 ); // Will EXIT
			// We have EXITed already at this point!!
			break;

		case 'delete_avatar':
			// Delete profile picture

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			if( empty( $edited_User ) || ! is_object( $edited_User ) )
			{
				$Messages->add( 'No user set!' ); // Needs no translation, should be prevented by UI.
				$action = 'list';
				break;
			}
			$file_ID = param( 'file_ID', 'integer', NULL );

			$result = $edited_User->delete_avatar( $file_ID );
			if( $result !== true )
			{
				$action = $result;
				break;
			}
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=user&user_tab='.$user_tab.'&user_ID='.$edited_User->ID, 303 ); // Will EXIT
			// We have EXITed already at this point!!
			break;

		case 'upload_avatar':
			// Upload new profile picture

			// Stop a request from the blocked IP addresses or Domains
			antispam_block_request();

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			if( empty( $edited_User ) || ! is_object( $edited_User ) )
			{
				$Messages->add( 'No user set!' ); // Needs no translation, should be prevented by UI.
				$action = 'list';
				break;
			}

			$result = $edited_User->update_avatar_from_upload();
			if( $result !== true )
			{
				$action = $result;
				break;
			}
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=user&user_tab=avatar&user_ID='.$edited_User->ID, 303 ); // Will EXIT
			// We have EXITed already at this point!!
			break;

		case 'update_avatar':
			// Update profile picture
		case 'restore_avatar':
			// Restore profile picture

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			if( empty( $edited_User ) || ! is_object( $edited_User ) )
			{
				$Messages->add( 'No user set!' ); // Needs no translation, should be prevented by UI.
				$action = 'list';
				break;
			}
			$file_ID = param( 'file_ID', 'integer', NULL );

			// Update/Restore profile picture
			$result = $edited_User->update_avatar( $file_ID, ( $action == 'restore_avatar' ) );
			if( $result !== true )
			{
				$action = $result;
				break;
			}
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=user&user_tab=avatar&user_ID='.$edited_User->ID, 303 ); // Will EXIT
			// We have EXITed already at this point!!
			break;

		case 'rotate_avatar_90_left':
		case 'rotate_avatar_180':
		case 'rotate_avatar_90_right':
			// Rotate profile picture

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			if( empty($edited_User) || !is_object($edited_User) )
			{
				$Messages->add( 'No user set!' ); // Needs no translation, should be prevented by UI.
				$action = 'list';
				break;
			}
			$file_ID = param( 'file_ID', 'integer', NULL );

			switch( $action )
			{
				case 'rotate_avatar_90_left':
					$degrees = 90;
					break;
				case 'rotate_avatar_180':
					$degrees = 180;
					break;
				case 'rotate_avatar_90_right':
					$degrees = 270;
					break;
			}

			$result = $edited_User->rotate_avatar( $file_ID, $degrees );
			if( $result !== true )
			{
				switch( $result )
				{
					case 'only_own_profile':
						$action = 'view';
						break;

					case 'wrong_file':
					case 'other_user':
					case 'rotate_error':
					default:
						$action = 'edit';
						break;
				}
				break;
			}
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=user&user_tab='.$user_tab.'&user_ID='.$edited_User->ID, 303 ); // Will EXIT
			// We have EXITed already at this point!!
			break;

		case 'crop':
			// Crop profile picture

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			if( empty( $edited_User ) || ! is_object( $edited_User ) )
			{
				$Messages->add( 'No user set!' ); // Needs no translation, should be prevented by UI.
				$action = 'list';
				break;
			}
			$file_ID = param( 'file_ID', 'integer', NULL );

			// Check data to crop
			$image_crop_data = param( 'image_crop_data', 'string', '' );
			$image_crop_data = empty( $image_crop_data ) ? array() : explode( ':', $image_crop_data );
			foreach( $image_crop_data as $image_crop_value )
			{
				$image_crop_value = (float)$image_crop_value;
				if( $image_crop_value < 0 || $image_crop_value > 100 )
				{ // Wrong data to crop, This value is percent of real size, so restrict it from 0 and to 100
					$action = 'view';
					break 2;
				}
			}
			if( count( $image_crop_data ) < 4 )
			{ // Wrong data to crop
				$action = 'view';
				break;
			}

			$result = $edited_User->crop_avatar( $file_ID, $image_crop_data[0], $image_crop_data[1], $image_crop_data[2], $image_crop_data[3] );
			if( $result !== true )
			{
				switch( $result )
				{
					case 'only_own_profile':
						$action = 'view';
						break;

					case 'wrong_file':
					case 'other_user':
					case 'crop_error':
					default:
						$action = 'edit';
						break;
				}
				break;
			}

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=user&user_tab='.$user_tab.'&user_ID='.$edited_User->ID, 303 ); // Will EXIT
			// We have EXITed already at this point!!
			break;

		case 'update':
		case 'add_field':
		case 'subscribe':
			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			// Update existing user OR create new user:
			if( empty($edited_User) || !is_object($edited_User) )
			{
				$Messages->add( 'No user set!' ); // Needs no translation, should be prevented by UI.
				$action = 'list';
				break;
			}

			// if new user is true then it will redirect to user list after user has been created
			$is_new_user = $edited_User->ID == 0 ? true : false;

			$result = $edited_User->update_from_request( $is_new_user );
			if( $result !== true )
			{
				$action = $result;
				break;
			}

			if( param( 'advanced_form', 'boolean', false ) )
			{
				/*
				 * We currently support only one backoffice skin, so we don't need a system for selecting the backoffice skin.
				$current_admin_skin = param( 'current_admin_skin', 'string' );
				if( ( $current_admin_skin == $UserSettings->get( 'admin_skin', $current_User->ID ) ) &&
					( $current_admin_skin == $UserSettings->get( 'admin_skin', $edited_User->ID ) ) )
				{ // Save Admin skin display settings if admin skin wasn't changed, and
					// edited user admin skin is the same as current user admin skin
					$AdminUI->set_skin_settings( $edited_User->ID );
				}
				 */

				// Update the folding states for current user:
				save_fieldset_folding_values();

				if( $UserSettings->dbupdate() )
				{
					$Messages->add( TB_('User feature settings have been changed.'), 'success');
				}

				// PluginUserSettings
				load_funcs('plugins/_plugin.funcs.php');

				$any_plugin_settings_updated = false;
				$Plugins->restart();
				while( $loop_Plugin = & $Plugins->get_next() )
				{
					$tmp_params = array( 'for_editing' => true );
					$pluginusersettings = $loop_Plugin->GetDefaultUserSettings( $tmp_params );
					if( empty($pluginusersettings) )
					{
						continue;
					}

					// Loop through settings for this plugin:
					foreach( $pluginusersettings as $set_name => $set_meta )
					{
						autoform_set_param_from_request( $set_name, $set_meta, $loop_Plugin, 'UserSettings', $edited_User );
					}

					// Let the plugin handle custom fields:
					$tmp_params = array( 'User' => & $edited_User, 'action' => 'save' );
					$ok_to_update = $Plugins->call_method( $loop_Plugin->ID, 'PluginUserSettingsUpdateAction', $tmp_params );

					if( $ok_to_update === false )
					{
						$loop_Plugin->UserSettings->reset();
					}
					elseif( $loop_Plugin->UserSettings->dbupdate() )
					{
						$any_plugin_settings_updated = true;
					}
				}

				if( $any_plugin_settings_updated )
				{
					$Messages->add( TB_('Plugin user settings have been updated.'), 'success' );
				}
			}

			if( $is_new_user )
			{ // New user is created

				// Reset the filters in order to the new user can be seen
				load_class( 'users/model/_userlist.class.php', 'UserList' );
				$UserList = new UserList( 'admin' );
				$UserList->refresh_query = true;
				$UserList->query();

				if( param( 'send_pass_email', 'integer', 0 ) )
				{	// Inform new created user by email:
					locale_temp_switch( $edited_User->get( 'locale' ) );
					send_mail_to_User( $edited_User->ID, sprintf( TB_('Your new account on %s'), $Settings->get( 'notification_short_name' ) ), 'new_account_password_info', array(
							'login'    => $edited_User->get( 'login' ),
							'password' => get_param( 'edited_user_pass1' ),
						), true );
					locale_restore_previous();
				}

				header_redirect( regenerate_url( 'ctrl,action', 'ctrl=users&action=list', '', '&' ), 303 );
			}
			else
			{ // The user is updated
				if( ( $user_tab == 'admin' ) && ( $edited_User->ID == $current_User->ID ) )
				{ // an admin user has edited his own admin preferences
					if( check_user_status( 'is_closed' ) )
					{ // an admin user has changed his own status to closed, logout the user
						logout();
						header_redirect( $baseurl, 303 );
						// will have exited
					}
					if( $current_User->grp_ID != 1 )
					{ // admin user has changed his own group, change user_tab for redirect
						$user_tab = 'profile';
					}
				}

				if( isset( $current_User->previous_pass_driver ) &&
						$current_User->previous_pass_driver == 'nopass' &&
						$current_User->previous_pass_driver != $current_User->get( 'pass_driver' ) )
				{	// Redirect to page as we use after email validation if current user set password first time, e-g after email capture/quick registration:
					$redirect_to = redirect_after_account_activation();
				}
				else
				{
					$redirect_to = regenerate_url( '', 'user_ID='.$edited_User->ID.'&action=edit&user_tab='.$user_tab, '', '&' );
				}

				header_redirect( $redirect_to, 303 );
			}
			break;

		case 'default_settings':
			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			$reload_page = false; // We set it to true, if a setting changes that needs a page reload (locale, admin skin, ..)

			// Admin skin:
			$cur_admin_skin = $UserSettings->get('admin_skin');

			$UserSettings->delete( 'admin_skin', $edited_User->ID );
			if( $cur_admin_skin
					&& $UserSettings->get('admin_skin', $edited_User->ID ) != $cur_admin_skin
					&& ($edited_User->ID == $current_User->ID) )
			{ // admin_skin has changed:
				$reload_page = true;
			}

			// Reset user settings to defaults:
			$UserSettings->reset_to_defaults( $edited_User->ID, false );

			// Update user settings:
			if( $UserSettings->dbupdate() ) $Messages->add( TB_('User feature settings have been changed.'), 'success');

			// PluginUserSettings
			$any_plugin_settings_updated = false;
			$Plugins->restart();
			while( $loop_Plugin = & $Plugins->get_next() )
			{
				$tmp_params = array( 'for_editing' => true );
				$pluginusersettings = $loop_Plugin->GetDefaultUserSettings( $tmp_params );

				if( empty($pluginusersettings) )
				{
					continue;
				}

				foreach( $pluginusersettings as $k => $l_meta )
				{
					if( isset($l_meta['layout']) || ! empty($l_meta['no_edit']) )
					{ // a layout "setting" or not for editing
						continue;
					}

					$loop_Plugin->UserSettings->delete($k, $edited_User->ID);
				}

				// Let the plugin handle custom fields:
				$tmp_params = array( 'User' => & $edited_User, 'action' => 'reset' );
				$ok_to_update = $Plugins->call_method( $loop_Plugin->ID, 'PluginUserSettingsUpdateAction', $tmp_params );

				if( $ok_to_update === false )
				{
					$loop_Plugin->UserSettings->reset();
				}
				elseif( $loop_Plugin->UserSettings->dbupdate() )
				{
					$any_plugin_settings_updated = true;
				}
			}
			if( $any_plugin_settings_updated )
			{
				$Messages->add( TB_('Plugin user settings have been updated.'), 'success' );
			}

			// Always display the profile again:
			$action = 'edit';

			if( $reload_page )
			{ // reload the current page through header redirection:
				header_redirect( regenerate_url( '', 'user_ID='.$edited_User->ID.'&action='.$action, '', '&' ) ); // will save $Messages into Session
			}
			break;

		case 'refresh_regional':
			// Refresh a regions, sub-regions & cities (when JavaScript is disabled)

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			$edited_User->ctry_ID = param( 'edited_user_ctry_ID', 'integer', 0 );
			$edited_User->rgn_ID = param( 'edited_user_rgn_ID', 'integer', 0 );
			$edited_User->subrg_ID = param( 'edited_user_subrg_ID', 'integer', 0 );
			break;

		case 'delete_all_sent_emails':
			// Delete all emails sent to the edited user:

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			// Check edit permissions:
			check_user_perm( 'emails', 'edit', true );

			if( param( 'confirm', 'integer', 0 ) )
			{	// confirmed
				if( $edited_User->delete_sent_emails() )
				{	// The blogs were deleted successfully
					$Messages->add( TB_('All emails sent to the user were deleted.'), 'success' );

					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( '?ctrl=user&user_tab=activity&user_ID='.$user_ID, 303 ); // Will EXIT
					// We have EXITed already at this point!!
				}
			}
			break;

		case 'delete_all_email_returns':
			// Delete all email returns from the edited user's email address:

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			// Check edit permissions:
			check_user_perm( 'emails', 'edit', true );

			if( param( 'confirm', 'integer', 0 ) )
			{	// confirmed
				if( $edited_User->delete_email_returns() )
				{	// The blogs were deleted successfully
					$Messages->add( TB_('All email returns from the user\'s email address were deleted.'), 'success' );

					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( '?ctrl=user&user_tab=activity&user_ID='.$user_ID, 303 ); // Will EXIT
					// We have EXITed already at this point!!
				}
			}
			break;

		case 'delete_all_blogs':
			// Delete all blogs of edited user recursively

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			// Check edit permissions:
			$current_User->can_moderate_user( $edited_User->ID, true );

			if( param( 'confirm', 'integer', 0 ) )
			{	// confirmed
				if( $edited_User->delete_blogs() )
				{	// The blogs were deleted successfully
					$Messages->add( TB_('All blogs of the user were deleted.'), 'success' );

					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( '?ctrl=user&user_tab=activity&user_ID='.$user_ID, 303 ); // Will EXIT
					// We have EXITed already at this point!!
				}
			}
			break;

		case 'delete_all_posts_created':
			// Delete all posts created by the user

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			// Check edit permissions:
			$current_User->can_moderate_user( $edited_User->ID, true );

			if( param( 'confirm', 'integer', 0 ) )
			{	// confirmed
				if( $edited_User->delete_posts( 'created' ) )
				{	// The posts were deleted successfully
					$Messages->add( TB_('The posts created by the user were deleted.'), 'success' );

					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( '?ctrl=user&user_tab=activity&user_ID='.$user_ID, 303 ); // Will EXIT
					// We have EXITed already at this point!!
				}
			}
			break;

		case 'delete_all_posts_edited':
			// Delete all posts edited by the user

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			// Check edit permissions:
			$current_User->can_moderate_user( $edited_User->ID, true );

			if( param( 'confirm', 'integer', 0 ) )
			{	// confirmed
				if( $edited_User->delete_posts( 'edited' ) )
				{	// The posts were deleted successfully
					$Messages->add( TB_('The posts edited by the user were deleted.'), 'success' );

					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( '?ctrl=user&user_tab=activity&user_ID='.$user_ID, 303 ); // Will EXIT
					// We have EXITed already at this point!!
				}
			}
			break;

		case 'delete_all_comments':
			// Delete all comments posted by the user

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			// Check edit permissions:
			$current_User->can_moderate_user( $edited_User->ID, true );

			if( param( 'confirm', 'integer', 0 ) )
			{	// confirmed
				if( $edited_User->delete_comments() )
				{	// The posts were deleted successfully
					$Messages->add( TB_('The comments posted by the user were deleted.'), 'success' );

					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( '?ctrl=user&user_tab=activity&user_ID='.$user_ID, 303 ); // Will EXIT
					// We have EXITed already at this point!!
				}
			}
			break;

		case 'delete_all_messages':
			// Delete all messages posted by the user

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			// Check edit permissions:
			$current_User->can_moderate_user( $edited_User->ID, true );

			if( param( 'confirm', 'integer', 0 ) )
			{	// confirmed
				if( $edited_User->delete_messages( 'sent' ) )
				{	// The messages were deleted successfully
					$Messages->add( TB_('The private messages sent by the user were deleted.'), 'success' );

					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( '?ctrl=user&user_tab=activity&user_ID='.$user_ID, 303 ); // Will EXIT
					// We have EXITed already at this point!!
				}
			}
			break;

		case 'delete_all_received_messages':
			// Delete all messages received by the user

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			// Check edit permissions:
			$current_User->can_moderate_user( $edited_User->ID, true );

			if( param( 'confirm', 'integer', 0 ) )
			{	// confirmed
				if( $edited_User->delete_messages( 'received' ) )
				{	// The messages were deleted successfully
					$Messages->add( TB_('The private messages received by the user were deleted.'), 'success' );

					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( '?ctrl=user&user_tab=activity&user_ID='.$user_ID, 303 ); // Will EXIT
					// We have EXITed already at this point!!
				}
			}
			break;

		case 'delete_all_polls':
			// Delete all polls posted by the user

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			// Check edit permissions:
			$current_User->can_moderate_user( $edited_User->ID, true );

			if( param( 'confirm', 'integer', 0 ) )
			{	// confirmed
				if( $edited_User->delete_polls() )
				{	// The polls were deleted successfully
					$Messages->add( TB_('The polls owned by the user were deleted.'), 'success' );

					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( '?ctrl=user&user_tab=activity&user_ID='.$user_ID, 303 ); // Will EXIT
					// We have EXITed already at this point!!
				}
			}
			break;

		case 'delete_all_userdata':
			// Delete user and all his contributions

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			// Check edit permissions:
			check_user_perm( 'users', 'edit', true );

			if( $edited_User->ID == $current_User->ID || $edited_User->ID == 1 )
			{	// Don't delete a logged in user
				break;
			}

			if( param( 'confirm', 'integer', 0 ) )
			{	// confirmed
				$user_login = $edited_User->dget( 'login' );

				$edited_User->delete_sent_emails();
				$edited_User->delete_email_returns();
				$edited_User->delete_messages();
				$edited_User->delete_comments();
				$edited_User->delete_posts( 'created|edited' );
				$edited_User->delete_blogs();
				$edited_User->delete_polls();
				if( $edited_User->dbdelete( $Messages ) )
				{	// User and all his contributions were deleted successfully
					$Messages->add( sprintf( TB_('The user &laquo;%s&raquo; and all his contributions were deleted.'), $user_login ), 'success' );

					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( '?ctrl=users', 303 ); // Will EXIT
					// We have EXITed already at this point!!
				}
			}
			break;

		case 'delete_data':
			// Delete all posts, comments or private messages of the user

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			// Check edit permissions:
			$current_User->can_moderate_user( $edited_User->ID, true );

			if( param( 'delete_comments', 'integer', 0 ) )
			{ // Delete the comments
				// Count even recycled comments only if current User has global editall blogs permission, because only those users can delete trashed comments
				$comments_created = $edited_User->get_num_comments( '', check_user_perm( 'blogs', 'eidtall', false ) );
				if( $comments_created > 0 && $edited_User->delete_comments() )
				{ // The comments were deleted successfully
					$result_message = ( $comments_created == 1 ) ? TB_('1 comment was deleted.') : sprintf( TB_('%s comments were deleted.'), $comments_created );
					$Messages->add( $result_message, 'success' );
				}
			}

			if( param( 'delete_posts', 'integer', 0 ) )
			{ // Delete the posts
				$posts_created = $edited_User->get_num_posts();
				if( $posts_created > 0 && $edited_User->delete_posts( 'created' ) )
				{ // The posts were deleted successfully
					$result_message = ( $posts_created == 1 ) ? TB_('1 post was deleted.') : sprintf( TB_('%s posts were deleted.'), $posts_created );
					$Messages->add( $result_message, 'success' );
				}
			}

			if( param( 'delete_messages', 'integer', 0 ) )
			{ // Delete the messages
				$messages_sent = $edited_User->get_num_messages( 'sent' );
				if( $messages_sent > 0 && $edited_User->delete_messages() )
				{ // The messages were deleted successfully
					$result_message = ( $messages_sent == 1 ) ? TB_('1 private message was deleted.') : sprintf( TB_('%s private messages were deleted.'), $messages_sent );
					$Messages->add( $result_message, 'success' );
				}
			}

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $admin_url.'?ctrl=user&user_tab='.$user_tab.'&user_ID='.$user_ID ); // Will EXIT
			// We have EXITed already at this point!!
			break;

		case 'redemption':
			// Change status of user email to 'redemption'

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			$EmailAddressCache = & get_EmailAddressCache();
			if( $EmailAddress = & $EmailAddressCache->get_by_name( $edited_User->get( 'email' ), false, false ) &&
			    in_array( $EmailAddress->get( 'status' ), array( 'warning', 'suspicious1', 'suspicious2', 'suspicious3', 'prmerror' ) ) )
			{ // Change to 'redemption' status only if status is 'warning', 'suspicious1', 'suspicious2', 'suspicious3' or 'prmerror'
				$EmailAddress->set( 'status', 'redemption' );
				$EmailAddress->dbupdate();
			}

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $admin_url.'?ctrl=user&user_tab='.$user_tab.'&user_ID='.$user_ID ); // Will EXIT
			// We have EXITed already at this point!!
			break;

		case 'add_automation':
			// Add user to automation:

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			// Check edit permissions:
			$current_User->can_moderate_user( $edited_User->ID, true );

			param( 'autm_ID', 'integer', true );

			$AutomationCache = & get_AutomationCache();
			$user_Automation = & $AutomationCache->get_by_ID( $autm_ID, false, false );
			$automation_title = ( $user_Automation ? '"'.$user_Automation->get( 'name' ).'"' : '#'.$autm_ID );

			// Add user anyway even it it is not subscribed to Newsletter of the Automation:
			if( $user_Automation && $user_Automation->add_users( $edited_User->ID, array( 'users_no_subs' => 'add' ) ) )
			{	// Display message if user has been added to the selected automation really:
				$Messages->add( sprintf( TB_('The user %s has been added to automation %s.'), '"'.$edited_User->dget( 'login' ).'"', $automation_title ), 'success' );
			}
			else
			{
				// NOTE: Don't translate this message because this case should not be in normal, display only for debug:
				$Messages->add( sprintf( 'The user %s was already added to automation %s.', '"'.$edited_User->dget( 'login' ).'"', $automation_title ), 'warning' );
			}

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=user&user_tab=marketing&user_ID='.$edited_User->ID, 303 ); // Will EXIT
			// We have EXITed already at this point!!
			break;

		case 'remove_automation':
			// Remove user from automation:

			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			// Check edit permissions:
			$current_User->can_moderate_user( $edited_User->ID, true );

			param( 'autm_ID', 'integer', true );

			$AutomationCache = & get_AutomationCache();
			$user_Automation = & $AutomationCache->get_by_ID( $autm_ID, false, false );
			$automation_title = ( $user_Automation ? '"'.$user_Automation->get( 'name' ).'"' : '#'.$autm_ID );

			$r = $DB->query( 'DELETE FROM T_automation__user_state
				WHERE aust_user_ID = '.$DB->quote( $edited_User->ID ).'
				  AND aust_autm_ID = '.$DB->quote( $autm_ID ) );

			if( $r )
			{	// Display message if user has been removed from selected automation really:
				$Messages->add( sprintf( TB_('The user %s has been removed from automation %s.'), '"'.$edited_User->dget( 'login' ).'"', $automation_title ), 'success' );
			}
			else
			{
				// NOTE: Don't translate this message because this case should not be in normal, display only for debug:
				$Messages->add( sprintf( 'The user %s is not detected in automation %s.', '"'.$edited_User->dget( 'login' ).'"', $automation_title ), 'warning' );
			}

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=user&user_tab=marketing&user_ID='.$edited_User->ID, 303 ); // Will EXIT
			// We have EXITed already at this point!!
			break;
	}
}

if( $display_mode != 'js')
{
	// Display a form to quick search users
	$AdminUI->top_block = get_user_quick_search_form();

	// require colorbox js
	require_js_helper( 'colorbox', 'rsc_url' );

	$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
	$AdminUI->breadcrumbpath_add( TB_('Users'), '?ctrl=users' );
	if( $action == 'new' )
	{
		$AdminUI->breadcrumbpath_add( $edited_User->login, '?ctrl=user&amp;user_ID='.$edited_User->ID );
	}
	else
	{
		$AdminUI->breadcrumbpath_add( $edited_User->get_colored_login( array( 'login_text' => 'name' ) ), '?ctrl=user&amp;user_ID='.$edited_User->ID );
	}

	switch( $user_tab )
	{
		case 'profile':
			$AdminUI->breadcrumbpath_add( TB_('Profile'), '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab='.$user_tab );
			init_userfields_js( 'rsc_url', $AdminUI->get_template( 'tooltip_plugin' ) );
			require_js_defer( '#jcrop#', 'rsc_url' );
			require_css( '#jcrop_css#', 'rsc_url' );

			// Set an url for manual page:
			if( $action == 'new' )
			{
				$AdminUI->set_page_manual_link( 'user-edit' );
			}
			else
			{
				$AdminUI->set_page_manual_link( 'user-profile-tab' );
			}
			break;
		case 'avatar':
			if( isset($GLOBALS['files_Module']) )
			{
				$AdminUI->breadcrumbpath_add( TB_('Profile picture'), '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab='.$user_tab );

				// Set an url for manual page:
				$AdminUI->set_page_manual_link( 'user-profile-picture-tab' );
			}
			require_js_defer( '#jcrop#', 'rsc_url' );
			require_css( '#jcrop_css#', 'rsc_url' );
			break;
		case 'social':
			if( is_pro() )
			{
				// We need to initiate session now before sending any output to the browser for HybridAuth to work:
				session_start();

				$AdminUI->breadcrumbpath_add( TB_('Social Accounts'), '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab='.$user_tab );

				// Set an url for manual page:
				$AdminUI->set_page_manual_link( 'user-social-tab' );
			}
			break;
		case 'pwdchange':
			// Check and redirect if current URL must be used as https instead of http:
			check_https_url( 'login' );

			$AdminUI->breadcrumbpath_add( TB_('Change password'), '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab='.$user_tab );

			// Set an url for manual page:
			$AdminUI->set_page_manual_link( 'user-password-tab' );
			break;
		case 'userprefs':
			$AdminUI->breadcrumbpath_add( TB_('Preferences'), '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab='.$user_tab );

			// Set an url for manual page:
			$AdminUI->set_page_manual_link( 'user-preferences-tab' );
			break;
		case 'subs':
			$AdminUI->breadcrumbpath_add( TB_('Emails'), '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab='.$user_tab );

			// Set an url for manual page:
			$AdminUI->set_page_manual_link( 'user-notifications-tab' );
			break;
		case 'marketing':
			$AdminUI->breadcrumbpath_add( TB_('Marketing'), '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab='.$user_tab );

			// Set an url for manual page:
			$AdminUI->set_page_manual_link( 'user-marketing-tab' );

			// Initialize user tag input
			init_tokeninput_js();
			break;
		case 'visits':
			// Initialize user tag input
			init_tokeninput_js();
			// Load jQuery QueryBuilder plugin files for user list filters:
			init_querybuilder_js( 'rsc_url' );
			$AdminUI->breadcrumbpath_add( TB_('Visits'), '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab='.$user_tab );

			// Set an url for manual page:
			$AdminUI->set_page_manual_link( 'profile-visits-tab' );
			break;
		case 'advanced':
			$AdminUI->breadcrumbpath_add( TB_('Advanced'), '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab='.$user_tab );

			// Set an url for manual page:
			$AdminUI->set_page_manual_link( 'user-advanced-tab' );

			// Initialize JS for color picker field on the edit plugin settings form:
			init_colorpicker_js();
			break;
		case 'admin':
			$AdminUI->breadcrumbpath_add( TB_('Admin'), '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab='.$user_tab );
			load_funcs( 'tools/model/_email.funcs.php' );
			load_funcs( 'sessions/model/_hitlog.funcs.php' );

			// Set an url for manual page:
			$AdminUI->set_page_manual_link( 'user-admin-tab' );
			break;
		case 'sessions':
			$AdminUI->breadcrumbpath_add( TB_('Sessions'), '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab='.$user_tab );

			// Set an url for manual page:
			$AdminUI->set_page_manual_link( 'user-sessions-tab' );
			break;
		case 'activity':
			$AdminUI->breadcrumbpath_add( $current_User->ID == $edited_User->ID ? TB_('My Activity') : TB_('User Activity'), '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab='.$user_tab );
			require_css( $AdminUI->get_template( 'blog_base.css' ) ); // Default styles for the blog navigation

			// Set an url for manual page:
			$AdminUI->set_page_manual_link( 'user-activity-tab' );
			break;
		default:
			// Display back-office UI for modules:
			modules_call_method( 'init_backoffice_UI', array(
					'ctrl'    => 'user',
					'action'  => $action,
					'tab'     => $user_tab,
					'user_ID' => $edited_User->ID,
				) );
			break;
	}

	// Display messages depending on user email status
	display_user_email_status_message( $edited_User->ID );

	// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
	$AdminUI->disp_html_head();

	// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
	$AdminUI->disp_body_top( true, array( 'display_menu3' => false ) );
}

/*
 * Display appropriate payload:
 */
switch( $action )
{
	case 'nil':
		// Display NO payload!
		break;

	case 'new':
	case 'view':
	case 'edit':
	default:
		load_class( 'users/model/_userlist.class.php', 'UserList' );
		// Initialize users list from session cache in order to display prev/next links
		$UserList = new UserList( 'admin' );
		$UserList->memorize = false;
		$UserList->load_from_Request();

		switch( $user_tab )
		{
			case 'profile':
				// Display user identity form:
				$AdminUI->disp_payload_begin();
				$AdminUI->disp_view( 'users/views/_user_identity.form.php' );
				if( $edited_User->has_avatar() )
				{ // Init JS for form to crop pictures of user
					echo_user_crop_avatar_window();
				}
				$AdminUI->disp_payload_end();
				break;
			case 'avatar':
				// Display user avatar form:
				if( $Settings->get('allow_avatars') )
				{
					$AdminUI->disp_payload_begin();
					$AdminUI->disp_view( 'users/views/_user_avatar.form.php' );
					// Init JS for form to crop pictures of user
					echo_user_crop_avatar_window();
					$AdminUI->disp_payload_end();
				}
				break;
			case 'social':
				// Display social accounts form:
				if( is_pro() )
				{	// Social Accounts tab available to PRO version only:
					$AdminUI->disp_payload_begin();
					$AdminUI->disp_view( 'users/views/_user_social.form.php' );
					$AdminUI->disp_payload_end();
				}
				break;
			case 'pwdchange':
				// Display user password form:
				$AdminUI->disp_payload_begin();
				$AdminUI->disp_view( 'users/views/_user_password.form.php' );
				$AdminUI->disp_payload_end();
				break;
			case 'userprefs':
				// Display user preferences form:
				$AdminUI->disp_payload_begin();
				$AdminUI->disp_view( 'users/views/_user_preferences.form.php' );
				$AdminUI->disp_payload_end();
				break;
			case 'subs':
				// Display user subscriptions form:
				$AdminUI->disp_payload_begin();
				$AdminUI->disp_view( 'users/views/_user_subscriptions.form.php' );
				$AdminUI->disp_payload_end();
				break;
			case 'visits':
				// Display profile visits view
				$AdminUI->disp_payload_begin();
				$AdminUI->disp_view( 'users/views/_user_profile_visits.view.php' );
				$AdminUI->disp_payload_end();
				break;
			case 'marketing':
				// Display user marketing form:
				load_funcs( 'automations/model/_automation.funcs.php' );
				memorize_param( 'user_ID', 'integer', 0 );
				$AdminUI->disp_view( 'users/views/_user_marketing.form.php' );
				if( $display_mode != 'js' )
				{ // Init JS for form to add user to automation:
					echo_user_automation_js();
				}
				break;
			case 'advanced':
				// Display user advanced form:
				$AdminUI->disp_view( 'users/views/_user_advanced.form.php' );
				break;
			case 'admin':
				// Display user admin form:
				$AdminUI->disp_view( 'users/views/_user_admin.form.php' );
				if( $display_mode != 'js' )
				{ // Init JS for form to delete the posts, the comments and the messages of user
					echo_user_deldata_js();

					// Init JS for WHOIS query window
					echo_whois_js_bootstrap();
				}
				break;
			case 'sessions':
				// Display user admin form:
				$AdminUI->disp_view( 'sessions/views/_stats_sessions_list.view.php' );
				break;
			case 'activity':
				// Display user activity lists:
				load_funcs( 'polls/model/_poll.funcs.php' );
				$AdminUI->disp_payload_begin();

				if( in_array( $action, array( 'delete_all_sent_emails', 'delete_all_email_returns', 'delete_all_blogs', 'delete_all_posts_created', 'delete_all_posts_edited', 'delete_all_comments', 'delete_all_messages', 'delete_all_received_messages', 'delete_all_polls', 'delete_all_userdata' ) ) )
				{	// We need to ask for confirmation before delete:
					param( 'user_ID', 'integer', 0 , true ); // Memorize user_ID
					// Create Data Object to user only one method confirm_delete()
					$DataObject = new DataObject( '' );
					switch( $action )
					{
						case 'delete_all_sent_emails':
							$sent_emails_count = $edited_User->get_num_sent_emails();
							if( $sent_emails_count > 0 )
							{	// Display a confirm message if current user can delete at least one email log of the edited user:
								$confirm_message = sprintf( TB_('Delete %d emails sent to the user?'), $sent_emails_count );
							}
							break;

						case 'delete_all_email_returns':
							$email_returns_count = $edited_User->get_num_email_returns();
							if( $email_returns_count > 0 )
							{	// Display a confirm message if current user can delete at least one email returns of the edited user:
								$confirm_message = sprintf( TB_('Delete %d email returns from the user\'s email address?'), $email_returns_count );
							}
							break;

						case 'delete_all_blogs':
							$deleted_blogs_count = count( $edited_User->get_deleted_blogs() );
							if( $deleted_blogs_count > 0 )
							{	// Display a confirm message if current user can delete at least one blog of the edited user
								$confirm_message = sprintf( TB_('Delete %d blogs of the user?'), $deleted_blogs_count );
							}
							break;

						case 'delete_all_posts_created':
							$deleted_posts_created_count = count( $edited_User->get_deleted_posts( 'created' ) );
							if( $deleted_posts_created_count > 0 )
							{	// Display a confirm message if current user can delete at least one post created by the edited user
								$confirm_message = sprintf( TB_('Delete %d posts created by the user?'), $deleted_posts_created_count );
							}
							break;

						case 'delete_all_posts_edited':
							$deleted_posts_edited_count = count( $edited_User->get_deleted_posts( 'edited' ) );
							if( $deleted_posts_edited_count > 0 )
							{	// Display a confirm message if current user can delete at least one post created by the edited user
								$confirm_message = sprintf( TB_('Delete %d posts edited by the user?'), $deleted_posts_edited_count );
							}
							break;

						case 'delete_all_comments':
							if( $edited_User->has_comment_to_delete() )
							{ // Display a confirm message if current user can delete at least one comment posted by the edited user
								$confirm_message = sprintf( TB_('Delete %s comments posted by the user?'), $edited_User->get_num_comments( '', true ) );
							}
							break;

						case 'delete_all_messages':
							$messages_count = $edited_User->get_num_messages( 'sent' );
							if( $messages_count > 0 && check_user_perm( 'perm_messaging', 'abuse' ) )
							{	// Display a confirm message if current user can delete the messages sent by the edited user
								$confirm_message = sprintf( TB_('Delete %d private messages sent by the user?'), $messages_count );
							}
							break;

						case 'delete_all_received_messages':
							$messages_count = $edited_User->get_num_messages( 'received' );
							if( $messages_count > 0 && check_user_perm( 'perm_messaging', 'abuse' ) )
							{	// Display a confirm message if curent user can delete the messages sent by the edited user
								$confirm_message = sprintf( TB_('Delete %d private messages received by the user?'), $messages_count );
							}
							break;

						case 'delete_all_polls':
							$polls_count = $edited_User->get_num_polls();
							if( $polls_count > 0 )
							{	// Display a confirm message if current user can delete the polls owned by the edited user
								$confirm_message = sprintf( TB_('Delete %d polls owned by the user?'), $polls_count );
							}
							break;

						case 'delete_all_userdata':
							if(  $current_User->ID != $edited_User->ID && $edited_User->ID != 1 )
							{	// User can NOT delete admin and own account:
								$confirm_messages = array();
								$sent_emails_count = $edited_User->get_num_sent_emails();
								if( $sent_emails_count > 0 && check_user_perm( 'emails', 'edit' ) )
								{	// Display a confirm message if current user can delete at least one email sent log of the edited user:
									$confirm_messages[] = array( sprintf( TB_('%d emails sent to the user'), $sent_emails_count ), 'warning' );
								}
								$email_returns_count = $edited_User->get_num_email_returns();
								if( $email_returns_count > 0 && check_user_perm( 'emails', 'edit' ) )
								{	// Display a confirm message if current user can delete at least one email return of the edited user:
									$confirm_messages[] = array( sprintf( TB_('%d email returns from the user\'s email address'), $email_returns_count ), 'warning' );
								}
								$deleted_blogs_count = count( $edited_User->get_deleted_blogs() );
								if( $deleted_blogs_count > 0 )
								{	// Display a confirm message if current user can delete at least one blog of the edited user:
									$confirm_messages[] = array( sprintf( TB_('%d collections of the user'), $deleted_blogs_count ), 'warning' );
								}
								$deleted_posts_created_count = count( $edited_User->get_deleted_posts( 'created' ) );
								if( $deleted_posts_created_count > 0 )
								{	// Display a confirm message if current user can delete at least one post created by the edited user:
									$confirm_messages[] = array( sprintf( TB_('%d posts created by the user'), $deleted_posts_created_count ), 'warning' );
								}
								$deleted_posts_edited_count = count( $edited_User->get_deleted_posts( 'edited' ) );
								if( $deleted_posts_edited_count > 0 )
								{	// Display a confirm message if current user can delete at least one post created by the edited user:
									$confirm_messages[] = array( sprintf( TB_('%d posts edited by the user'), $deleted_posts_edited_count ), 'warning' );
								}
								if( $edited_User->has_comment_to_delete() )
								{	// Display a confirm message if current user can delete at least one comment posted by the edited user:
									$confirm_messages[] = array( sprintf( TB_('%s comments posted by the user'), $edited_User->get_num_comments( '', true ) ), 'warning' );
								}
								$messages_count = $edited_User->get_num_messages();
								if( $messages_count > 0 && check_user_perm( 'perm_messaging', 'abuse' ) )
								{	// Display a confirm message if current user can delete the messages sent by the edited user
									$confirm_messages[] = array( sprintf( TB_('%d private messages sent by the user'), $messages_count ), 'warning' );
								}
								// Find other users with the same email address
								$message_same_email_users = find_users_with_same_email( $edited_User->ID, $edited_User->get( 'email' ), TB_('Note: this user has the same email address (%s) as: %s') );
								if( $message_same_email_users !== false )
								{
									$confirm_messages[] = array( $message_same_email_users, 'note' );
								}
								// Displays a form to confirm the deletion of all user contributions:
								$edited_User->confirm_delete( TB_('Delete user and all his contributions?'), 'user', $action, get_memorized( 'action' ), $confirm_messages );
							}
							break;
					}
					if( !empty( $confirm_message ) )
					{	// Displays form to confirm deletion
						$DataObject->confirm_delete( $confirm_message, 'user', $action, get_memorized( 'action' ) );
					}
				}

				$AdminUI->disp_view( 'users/views/_user_activity.view.php' );
				$AdminUI->disp_payload_end();
				break;

			case 'deldata':
				if( $display_mode == 'js')
				{ // Do not append Debuglog & Debug JSlog to response!
					$debug = false;
					$debug_jslog = false;
				}

				if( $display_mode != 'js')
				{
					$AdminUI->disp_payload_begin();
				}
				$user_tab = param( 'user_tab_from', 'string', 'profile' );
				$AdminUI->disp_view( 'users/views/_user_deldata.form.php' );
				if( $display_mode != 'js')
				{
					$AdminUI->disp_payload_end();
				}
				break;

			case 'crop':
				if( $display_mode == 'js')
				{ // Do not append Debuglog & Debug JSlog to response!
					$debug = false;
					$debug_jslog = false;
				}

				$file_ID = param( 'file_ID', 'integer' );
				$cropped_File = & $edited_User->get_File_by_ID( $file_ID, $error_code );
				if( ! $cropped_File )
				{ // Wrong file for cropping
					break;
				}

				if( $display_mode != 'js')
				{
					require_js_defer( '#jcrop#', 'rsc_url' );
					require_css( '#jcrop_css#', 'rsc_url' );
					$AdminUI->disp_payload_begin();
				}
				$image_width = param( 'image_width', 'integer' );
				$image_height = param( 'image_height', 'integer' );
				$aspect_ratio = param( 'aspect_ratio', 'double' );
				$content_width = param( 'content_width', 'integer' );
				$content_height = param( 'content_height', 'integer' );
				$AdminUI->disp_view( 'users/views/_user_crop.form.php' );
				if( $display_mode != 'js')
				{
					$AdminUI->disp_payload_end();
				}
				break;

			case 'automation':
				if( $display_mode == 'js')
				{ // Do not append Debuglog & Debug JSlog to response!
					$debug = false;
					$debug_jslog = false;
				}

				if( $display_mode != 'js')
				{
					$AdminUI->disp_payload_begin();
				}
				$user_tab = param( 'user_tab_from', 'string', 'marketing' );
				$AdminUI->disp_view( 'users/views/_user_automation.form.php' );
				if( $display_mode != 'js')
				{
					$AdminUI->disp_payload_end();
				}
				break;

			default:
				// Display back-office UI for modules:
				modules_call_method( 'display_backoffice_UI', array(
						'ctrl'   => 'user',
						'action' => $action,
						'tab'    => $user_tab,
					) );
				break;
		}

		break;
}

if( $display_mode != 'js')
{
	// Init JS for user reporting
	echo_user_report_window();

	// Display body bottom, debug info and close </html>:
	$AdminUI->disp_global_footer();
}
?>
