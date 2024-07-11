<?php
/**
 * This is the template that displays the user profile page.
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
* @var Blog
*/
global $Collection, $Blog;
/**
 * @var GeneralSettings
 */
global $Settings;
/**
 * @var Current User
 */
global $current_User;

// init is logged in status
$is_logged_in = is_logged_in();

// Default params:
$params = array_merge( array(
		'edit_my_profile_link_text'        => T_('Edit my profile'),
		'edit_user_admin_link_text'        => T_('Edit in Back-Office'),
		'skin_form_params'                 => array(),
	), $params );

// ------------------- PREV/NEXT USER LINKS (SINGLE USER MODE) -------------------
user_prevnext_links();
// ------------------------- END OF PREV/NEXT USER LINKS -------------------------


// ---- START OF PROFILE CONTENT ---- //
echo '<div class="profile_content">';

$user_ID = param( 'user_ID', 'integer', '' );
if( empty($user_ID) )
{	// Grab the current User
	$user_ID = $current_User->ID;
}

$UserCache = & get_UserCache();
/**
 * @var User
 */
$User = & $UserCache->get_by_ID( $user_ID );

$profileForm = new Form( NULL, '', 'post', NULL, '', 'div' );

$profileForm->switch_template_parts( $params['skin_form_params'] );

$profileForm->switch_layout( 'fixed', false );

$profileForm->begin_form( 'evo_form evo_form_user' );

// ---- START OF LEFT COLUMN ---- //
echo '<div class="profile_column_left">';

	// ------------------------- "User Profile - Left" CONTAINER EMBEDDED HERE --------------------------
	// Display container contents:
	widget_container( 'user_profile_left', array(
		'widget_context' => 'user',	// Signal that we are displaying within an User
		// The following (optional) params will be used as defaults for widgets included in this container:
		'container_display_if_empty' => false, // If no widget, don't display container at all
		// This will enclose each widget in a block:
		'block_start' => '<div class="evo_widget $wi_class$">',
		'block_end' => '</div>',
		// This will enclose the title of each widget:
		'block_title_start' => '<p><b>',
		'block_title_end' => '</b></p>',
	) );
	// ----------------------------- END OF "User Profile - Left" CONTAINER -----------------------------

echo '</div>';
// ---- END OF LEFT COLUMN ---- //

// ---- START OF RIGHT COLUMN ---- //
echo '<div class="profile_column_right">';

	// ------------------------- "User Profile - Right" CONTAINER EMBEDDED HERE --------------------------
	// Display container contents:
	widget_container( 'user_profile_right', array(
		'widget_context' => 'user',	// Signal that we are displaying within an User
		// The following (optional) params will be used as defaults for widgets included in this container:
		'container_display_if_empty' => false, // If no widget, don't display container at all
		// This will enclose each widget in a block:
		'block_start' => '<div class="evo_widget $wi_class$">',
		'block_end' => '</div>',
		// This will enclose the title of each widget:
		'block_title_start' => '<h3>',
		'block_title_end' => '</h3>',
		// Template params for "User fields" widget:
		'group_start'      => '<fieldset class="fieldset"><div class="panel panel-default">',
		'group_item_start' => '<legend class="panel-heading">',
		'group_item_end'   => '</legend>',
		'list_start'       => '<div class="panel-body">',
		'item_start'       => '<div class="form-group fixedform-group">',
		'item_title_start' => '<label class="control-label fixedform-label">',
		'item_title_end'   => ':</label>',
		'item_text_start'  => '<div class="controls fixedform-controls form-control-static">',
		'item_text_end'    => '</div>',
		'item_end'         => '</div>',
		'list_end'         => '</div>',
		'group_end'        => '</div></fieldset>',
		// The following (optional) params will be used as defaults for widgets with code "subcontainer":
		'override_params_for_subcontainer' => array(
			// This will enclose each widget in a block:
			'block_start'       => '<div class="evo_widget $wi_class$"><fieldset class="fieldset"><div class="panel panel-default">',
			'block_end'         => '</div></fieldset></div>',
			// This will enclose the title of each widget:
			'block_title_start' => '<legend class="panel-heading">',
			'block_title_end'   => '</legend>',
			// This will enclose the body of each widget:
			'block_body_start'  => '<div class="panel-body">',
			'block_body_end'    => '</div>',
			// The following (optional) params will be used as defaults for widgets with code "user_info":
			'override_params_for_user_info' => array(
				// This will enclose each widget in a block:
				'block_start'       => '<div class="$wi_class$ form-group fixedform-group">',
				'block_end'         => '</div>',
				// This will enclose the title of each widget:
				'block_title_start' => '<label class="control-label fixedform-label">',
				'block_title_end'   => ':</label>',
				// This will enclose the body of each widget:
				'block_body_start'  => '<div class="controls fixedform-controls form-control-static">',
				'block_body_end'    => '</div>',
			),
		),
	) );
	// ----------------------------- END OF "User Profile - Right" CONTAINER -----------------------------

	$Plugins->trigger_event( 'DisplayProfileFormFieldset', array( 'Form' => & $profileForm, 'User' => & $User, 'edit_layout' => 'public' ) );

echo '</div>';
// ---- END OF RIGHT COLUMN ---- //

echo '<div class="clear"></div>';

// ---- END OF PROFILE CONTENT ---- //
echo '</div>'; // .profile_content


$profileForm->end_form();

// Init JS for user reporting
echo_user_report_window();
// Init JS for user contact editing
echo_user_contact_groups_window();
?>