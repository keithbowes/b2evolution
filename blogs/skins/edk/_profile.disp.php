<?php
/**
 * This is the template that displays the user profile form. It gets POSTed to /htsrv/profile_update.php.
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display a feedback, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=profile
 * Note: don't code this URL by hand, use the template functions to generate it!
 *
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * PROGIDISTRI grants Francois PLANQUE the right to license
 * PROGIDISTRI's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evoskins
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _profile.disp.php,v 1.24 2011/09/04 22:13:24 fplanque Exp $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'regional/model/_country.class.php', 'Country' );

global $Blog, $Session, $Messages, $inc_path;
global $action, $user_profile_only, $edited_User, $form_action;

$form_action = $Blog->gen_blogurl().'?disp='.$disp;

if( ! is_logged_in() )
{ // must be logged in!
	echo '<p class="error">'.T_( 'You are not logged in.' ).'</p>';
	return;
}

$user_profile_only = true;
// edited_User is always the current_User
$edited_User = $current_User;

$action = param_action();

if( !empty( $action ) )
{ // Check that this action request is not a CSRF hacked request:
	$Session->assert_received_crumb( 'user' );
}

switch( $action )
{
	case 'update_avatar':
		$file_ID = param( 'file_ID', 'integer', NULL );
		$current_User->update_avatar( $file_ID );
		$Messages->display();
		break;

	case 'remove_avatar':
		$current_User->remove_avatar();
		$Messages->display();
		break;

	case 'update':
		$current_User->update_from_request();
		$Messages->display();
		break;

	case 'upload_avatar':
		$current_User->update_avatar_from_upload();
		$Messages->display();
		break;
}

// Display tabs
echo '<div class="tabs">';
$entries = get_user_sub_entries( false, NULL );
foreach( $entries as $entry => $entry_data )
{
	if( $entry == $disp )
	{
		echo '<div class="selected">';
	}
	else
	{
		echo '<div class="option">';
	}
	echo '<a href="'.$entry_data['href'].'">'.$entry_data['text'].'</a>';
	echo '</div>';
}
echo '</div>';

// Display form
switch( $disp )
{
	case 'profile':
		require $inc_path.'users/views/_user_identity.form.php';
		break;
	case 'avatar':
		require $inc_path.'users/views/_user_avatar.form.php';
		break;
	case 'pwdchange':
		require $inc_path.'users/views/_user_password.form.php';
		break;
	case 'userprefs':
		require $inc_path.'users/views/_user_preferences.form.php';
		break;
	default:
		debug_die( "Unknown user tab" );
}

?>
