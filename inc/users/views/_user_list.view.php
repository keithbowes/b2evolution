<?php
/**
 * This file implements the UI view for the user/group list for user/group editing.
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


if( !isset( $display_params ) )
{ // init display_params
	$display_params = array();
}

// Display the users results table:
users_results_block( array(
		'display_sec_groups' => true,
		'display_params'     => $display_params,
		'display_contact'    => false,
		'display_email'      => true,
		'display_automation' => true,
		'display_btn_tags'   => true,
		'display_btn_account_status' => true,
		'display_btn_change_groups'  => true,
		'display_btn_delspam'=> true,
		'display_btn_export' => true,
	) );

if( is_admin_page() )
{	// Call plugins event:
	global $Plugins;
	$Plugins->trigger_event( 'AdminAfterUsersList' );
}

load_funcs( 'users/model/_user_js.funcs.php' );
?>
