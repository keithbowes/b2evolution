<?php
/**
 * This file implements the UI view for the user advanced properties.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 *
 * @author fplanque: Francois PLANQUE
 * @author efy-asimo: Attila SIMO
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var instance of GeneralSettings class
 */
global $Settings;
/**
 * @var instance of UserSettings class
 */
global $UserSettings;
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
/**
 * @var Plugins
 */
global $Plugins;
/**
 * $var AdminUI
 */
global $AdminUI;

/*
 * We currently support only one backoffice skin, so we don't need a system for selecting the backoffice skin.

// Admin skin dropdown list handler
// Display settings corresponding only for the current (loaded) admin skin
?>
<script>
	function admin_skin_changed()
	{
		// admin skin dropdown list selected value
		var val = jQuery( '#edited_user_admin_skin' ).val();

		if( val == null )
		{ // there is no admin skin drop down list
			return;
		}

		if( val != jQuery( '[name |= current_admin_skin]' ).val() )
		{ // popup selected value is different then current admin skin => hide skin settings
			jQuery( '#admin_skin_settings_div' ).hide();
		}
		else
		{ // popup selected value is the same as the current admin skin => show skin settings
			jQuery( '#admin_skin_settings_div' ).show();
		}
	}
</script>
<?php

*/

// Begin payload block:
$this->disp_payload_begin();

// ------------------- PREV/NEXT USER LINKS -------------------
user_prevnext_links( array(
		'user_tab' => 'advanced'
	) );
// ------------- END OF PREV/NEXT USER LINKS -------------------

$Form = new Form( NULL, 'user_checkchanges' );

$Form->title_fmt = '$title$';

if( !$user_profile_only )
{
	echo_user_actions( $Form, $edited_User, $action );
}

$form_text_title = TB_( 'Edit advanced preferences' ); // used for js confirmation message on leave the changed form
$form_title = get_usertab_header( $edited_User, 'advanced', '<span class="nowrap">'.TB_( 'Edit advanced preferences' ).'</span>'.get_manual_link( 'user-advanced-tab' ) );

$Form->begin_form( 'fform', $form_title, array( 'title' => ( isset( $form_text_title ) ? $form_text_title : $form_title ) ) );

	$Form->add_crumb( 'user' );
	$Form->hidden_ctrl();
	$Form->hidden( 'user_tab', 'advanced' );
	$Form->hidden( 'advanced_form', '1' );

	$Form->hidden( 'user_ID', $edited_User->ID );
	$Form->hidden( 'edited_user_login', $edited_User->login );

	/***************  Preferences  **************/

$Form->begin_fieldset( TB_('Preferences').get_manual_link('user-advanced-preferences') );

/*
 * We currently support only one backoffice skin, so we don't need a system for selecting the backoffice skin.
$value_admin_skin = get_param('edited_user_admin_skin');
if( !$value_admin_skin )
{ // no value supplied through POST/GET
	$value_admin_skin = $UserSettings->get( 'admin_skin', $edited_User->ID );
}
if( !$value_admin_skin )
{ // Nothing set yet for the user, use the default
	$value_admin_skin = $Settings->get('admin_skin');
}

$Form->hidden( 'current_admin_skin', $value_admin_skin );
 */

if( $action != 'view' )
{ // We can edit the values:

	/*
	 * We currently support only one backoffice skin, so we don't need a system for selecting the backoffice skin.
	$Form->select_input_array( 'edited_user_admin_skin', $value_admin_skin, get_admin_skins(), TB_('Admin skin'), TB_('The skin defines how the backoffice appears to you.'), array( 'onchange' => 'admin_skin_changed()' ) );
	 */

  // fp> TODO: We gotta have something like $edited_User->UserSettings->get('legend');
	// Icon/text thresholds:
	$Form->text( 'edited_user_action_icon_threshold', $UserSettings->get( 'action_icon_threshold', $edited_User->ID), 1, TB_('Action icon display'), TB_('1:more icons ... 5:less icons') );
	$Form->text( 'edited_user_action_word_threshold', $UserSettings->get( 'action_word_threshold', $edited_User->ID), 1, TB_('Action word display'), TB_('1:more action words ... 5:less action words') );

	// To display or hide icon legend:
	$Form->checkbox( 'edited_user_legend', $UserSettings->get( 'display_icon_legend', $edited_User->ID ), TB_('Display icon legend'), TB_('Display a legend at the bottom of every page including all action icons used on that page.') );

	// To activate or deactivate bozo validator:
	$Form->checkbox( 'edited_user_bozo', $UserSettings->get( 'control_form_abortions', $edited_User->ID ), TB_('Control form closing'), TB_('This will alert you if you fill in data into a form and try to leave the form before submitting the data.') );

	// To activate focus on first form input text
	$Form->checkbox( 'edited_user_focusonfirst', $UserSettings->get( 'focus_on_first_input', $edited_User->ID ), TB_('Focus on first field'), TB_('The focus will automatically go to the first input text field.') );

	// Number of results per page
	$results_per_page_options = array(
			'10' => sprintf( TB_('%s lines'), '10' ),
			'20' => sprintf( TB_('%s lines'), '20' ),
			'30' => sprintf( TB_('%s lines'), '30' ),
			'40' => sprintf( TB_('%s lines'), '40' ),
			'50' => sprintf( TB_('%s lines'), '50' ),
			'100' => sprintf( TB_('%s lines'), '100' ),
			'200' => sprintf( TB_('%s lines'), '200' ),
			'500' => sprintf( TB_('%s lines'), '500' ),
		);
	$Form->select_input_array( 'edited_user_results_page_size', $UserSettings->get( 'results_per_page', $edited_User->ID ), $results_per_page_options, TB_('Results per page'), TB_('Number of rows displayed in results tables.'), array( 'force_keys_as_values' => true ) );
}
else
{ // display only

	/*
	 * We currently support only one backoffice skin, so we don't need a system for selecting the backoffice skin.
	$Form->info_field( TB_('Admin skin'), $value_admin_skin, array( 'note' => TB_('The skin defines how the backoffice appears to you.') ) );
	 */

	$Form->info( TB_('Action icon display'), $UserSettings->get( 'action_icon_threshold', $edited_User->ID ), TB_('1:more icons ... 5:less icons') );
	$Form->info( TB_('Action word display'), $UserSettings->get( 'action_word_threshold', $edited_User->ID ), TB_('1:more action words ... 5:less action words') );
	$Form->info( TB_('Display icon legend'), ( $UserSettings->get( 'display_icon_legend', $edited_User->ID ) ? TB_('yes') : TB_('no') ), TB_('Display a legend at the bottom of every page including all action icons used on that page.') );
	$Form->info( TB_('Control form closing'), ( $UserSettings->get( 'control_form_abortions', $edited_User->ID ) ? TB_('yes') : TB_('no') ), TB_('This will alert you if you fill in data into a form and try to leave the form before submitting the data.') );
	$Form->info( TB_('Focus on first field'), ( $UserSettings->get( 'focus_on_first_input', $edited_User->ID ) ? TB_('yes') : TB_('no') ), TB_('The focus will automatically go to the first input text field.') );
	$Form->info( TB_('Results per page'), $UserSettings->get( 'results_per_page', $edited_User->ID ), TB_('Number of rows displayed in results tables.') );
}

$Form->end_fieldset();

	/***************  Admin skin settings  **************/
// asimo> this div is needed to make sure the settings show/hide js part always work without reference to the AdminUI.
echo '<div id="admin_skin_settings_div">';
	$AdminUI->display_skin_settings( $Form, $edited_User->ID );
echo '</div>';

	/***************  Plugins  **************/

if( $action != 'view' )
{ // We can edit the values:
	// PluginUserSettings
	load_funcs('plugins/_plugin.funcs.php');

	$Plugins->restart();
	while( $loop_Plugin = & $Plugins->get_next() )
	{
		if( ! $loop_Plugin->UserSettings /* NOTE: this triggers autoloading in PHP5, which is needed for the "hackish" isset($this->UserSettings)-method to see if the settings are queried for editing (required before 1.9) */
			&& ! $Plugins->has_event($loop_Plugin->ID, 'PluginSettingsEditDisplayAfter') ) // What do we care about this event for?
		{
			continue;
		}

		// We use output buffers here to display the fieldset only, if there's content in there (either from PluginUserSettings or PluginSettingsEditDisplayAfter).
		ob_start();
		$Form->begin_fieldset( $loop_Plugin->name );

		ob_start();
		// UserSettings:
		$tmp_params = array( 'for_editing' => true, 'user_ID' => $edited_User->ID );
		$plugin_user_settings = $loop_Plugin->GetDefaultUserSettings( $tmp_params );
		if( is_array($plugin_user_settings) )
		{
			foreach( $plugin_user_settings as $l_name => $l_meta )
			{
				// Display form field for this setting:
				autoform_display_field( $l_name, $l_meta, $Form, 'UserSettings', $loop_Plugin, $edited_User );
			}
		}

		// fp> what's a use case for this event? (I soooo want to nuke it...)
		$tmp_params = array( 'Form' => & $Form, 'User' => $edited_User );
		$Plugins->call_method( $loop_Plugin->ID, 'PluginUserSettingsEditDisplayAfter', $tmp_params );

		$has_contents = strlen( ob_get_contents() );
		$Form->end_fieldset();

		if( $has_contents )
		{
			ob_end_flush();
			ob_end_flush();
		}
		else
		{ // No content, discard output buffers:
			ob_end_clean();
			ob_end_clean();
		}
	}
}

	/***************  Buttons  **************/

if( $action != 'view' )
{ // Edit buttons
	$Form->buttons( array(
		array( '', 'actionArray[update]', TB_('Save Changes!'), 'SaveButton' ),
		// dh> TODO: Non-Javascript-confirm before trashing all settings with a misplaced click.
		array( 'type' => 'submit', 'name' => 'actionArray[default_settings]', 'value' => TB_('Restore defaults'), 'class' => 'ResetButton',
			'onclick' => "return confirm('".TS_('This will reset all your user settings.').'\n'.TS_('This cannot be undone.').'\n'.TS_('Are you sure?')."');" ),
	) );
}


$Form->end_form();

// End payload block:
$this->disp_payload_end();

// Enable JS for fieldset folding:
echo_fieldset_folding_js();
?>
