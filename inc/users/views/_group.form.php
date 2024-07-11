<?php
/**
 * This file implements the UI view for the user group properties.
 *
 * Called by {@link b2users.php}
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @var Group
 */
global $edited_Group;

global $action;

/**
 * Display pluggable permissions
 *
 * @param string perm block name  'additional'|'system'
 */
function display_pluggable_permissions( &$Form, $perm_block )
{
	global $edited_Group;

	$GroupSettings = & $edited_Group->get_GroupSettings();
	foreach( $GroupSettings->permission_modules as $perm_name => $module_name )
	{
		$Module = & $GLOBALS[$module_name.'_Module'];
		if( method_exists( $Module, 'get_available_group_permissions' ) )
		{
			$permissions = $Module->get_available_group_permissions( $edited_Group->ID );
			if( array_key_exists( $perm_name, $permissions ) )
			{
				$perm = $permissions[$perm_name];
				if( $perm['perm_block'] == $perm_block )
				{
					if( ! isset( $perm['perm_type'] ) )
					{
						$perm['perm_type'] = 'radiobox';
					}

					switch( $perm['perm_type'] )
					{
						case 'checkbox':
							$Form->checkbox_input( 'edited_grp_'.$perm_name, $GroupSettings->permission_values[$perm_name] == 'allowed', $perm['label'], array( 'input_suffix' => ' '.$perm['note'], 'value' => 'allowed' ) );
							break;

						case 'checklist':
							$checklist_values = explode( ',', $GroupSettings->permission_values[$perm_name] );
							if( ! empty( $checklist_values ) )
							{	// If at least one option is selected:
								foreach( $perm['options'] as $o => $checklist_option )
								{
									if( in_array( $checklist_option[1], $checklist_values ) )
									{	// This option is selected for the group:
										$perm['options'][ $o ][3] = 1;
									}
								}
							}
							$Form->checklist( $perm['options'], 'edited_grp_'.$perm_name, $perm['label'], false, false, array( 'note' => $perm['note'] ) );
						break;

						case 'radiobox':
							if( ! isset( $perm['field_lines'] ) )
							{
								$perm['field_lines'] = true;
							}
							if( ! isset( $perm['field_note'] ) )
							{
								$perm['field_note'] = '';
							}
							$Form->radio( 'edited_grp_'.$perm_name, $GroupSettings->permission_values[$perm_name], $perm['options'], $perm['label'], $perm['field_lines'], $perm['field_note'] );
							break;

						case 'info':
							$Form->info( $perm['label'], $perm['info'] );
							break;

						case 'text_input':
							$Form->text_input( 'edited_grp_'.$perm_name, $GroupSettings->permission_values[$perm_name], 5, $perm['label'], $perm['note'], array( 'maxlength' => $perm['maxlength'] ) );
							break;

						case 'select_object':
							$Form->select_input_object( 'edited_grp_'.$perm_name, $GroupSettings->permission_values[$perm_name], $perm['object_cache'], $perm['label'] );
							break;

						case 'hidden':
							$Form->hidden( 'edited_grp_'.$perm_name, $GroupSettings->permission_values[$perm_name] );
							break;
					}
				}
			}
		}
	}
}

$Form = new Form( NULL, 'group_checkchanges' );

$Form->global_icon( TB_('Cancel editing').'!', 'close', regenerate_url( 'ctrl,grp_ID,action', 'ctrl=groups' ) );

if( $edited_Group->ID == 0 )
{
	$Form->begin_form( 'fform', TB_('Creating new group') );
}
else
{
	$title = ( $action == 'edit' ? TB_('Editing group:') : TB_('Viewing group:') )
						.' '.
						( isset($edited_grp_oldname) ? $edited_grp_oldname : $edited_Group->dget('name') )
						.' ('.TB_('ID').' '.$edited_Group->ID.')';
	$Form->begin_form( 'fform', $title );
}

	$Form->add_crumb( 'group' );
	$Form->hidden_ctrl();
	$Form->hidden( 'action', 'update' );
	$Form->hidden( 'grp_ID', $edited_Group->ID );

$perm_none_option = array( 'none', TB_('No Access') );
$perm_view_option = array( 'view', TB_('View details') );
$perm_edit_option = array( 'edit', TB_('Edit/delete all') );


$Form->begin_fieldset( TB_('General').get_manual_link('group-properties-general') );

	$Form->text( 'edited_grp_name', $edited_Group->name, 50, TB_('Name'), '', 50, 'large' );

	$Form->radio( 'edited_grp_usage', $edited_Group->get( 'usage' ), array(
			array(
					'primary',
					sprintf( TB_('<span %s>Primary</span> Group'), 'class="label label-primary"' ),
					TB_('General use case')
				),
			array(
					'secondary',
					sprintf( TB_('<span %s>Secondary</span> Group'), 'class="label label-info"' ),
					TB_('Use if you need multiple groups per users')
				)
		), TB_('Group usage'), true );

	$Form->text_input( 'edited_grp_level', $edited_Group->get('level'), 2, TB_('Group level'), '[0 - 10]', array( 'required' => true ) );

$Form->end_fieldset();

// Show/Hide the panels below depending on group usage:
$primary_panels_style = $edited_Group->get( 'usage' ) == 'primary' ? '' : 'display:none';

$Form->begin_fieldset( TB_('Evobar & Back-office').get_manual_link('group-properties-evobar'), array( 'id' => 'evobar', 'style' => $primary_panels_style ) );

	display_pluggable_permissions( $Form, 'core_evobar' );

$Form->end_fieldset();

$Form->begin_fieldset( TB_('Blogging permissions').get_manual_link('group-properties-blogging'), array( 'id' => 'blogging', 'style' => $primary_panels_style ) );

	$Form->radio( 'edited_grp_perm_blogs', $edited_Group->get('perm_blogs'),
			array(  array( 'user', TB_('Users can only see Collections they have access to and the Sections these collections belong to + all Collections in the Sections they own.') ),
							array( 'viewall', TB_('View all: User can see all Sections and call Collections (with no additional edit permissions as above)') ),
							array( 'editall', sprintf( TB_('Full Access %s: Users can edit all Sections and Collections, create and delete Collections in any Section, create new Sections and delete empty Sections.'), get_admin_badge( 'coll', '#', TB_('Site Admin'), TB_('Select to give Collection Admin permission') ) ) )
						), TB_('Collections & Site Sections'), true );

	$Form->radio( 'perm_xhtmlvalidation', $edited_Group->get('perm_xhtmlvalidation'),
			array(  array( 'always', TB_('Force valid XHTML + strong security'),
											TB_('The security filters below will be strongly enforced.') ),
							array( 'never', TB_('Basic security checking'),
											TB_('Security filters below will still be enforced but with potential lesser accuracy.') )
						), TB_('XHTML validation'), true );

	$Form->radio( 'perm_xhtmlvalidation_xmlrpc', $edited_Group->get('perm_xhtmlvalidation_xmlrpc'),
			array(  array( 'always', TB_('Force valid XHTML + strong security'),
											TB_('The security filters below will be strongly enforced.') ),
							array( 'never', TB_('Basic security checking'),
											TB_('Security filters below will still be enforced but with potential lesser accuracy.') )
						), TB_('XHTML validation on XML-RPC calls'), true );

	$Form->checklist( array(
						array( 'prevent_css_tweaks', 1, TB_('Prevent CSS tweaks'), ! $edited_Group->get('perm_xhtml_css_tweaks'), false,
											TB_('WARNING: if allowed, users may deface the site, add hidden text, etc.') ),
						array( 'prevent_iframes', 1, TB_('Prevent iframes'), ! $edited_Group->get('perm_xhtml_iframes'), false,
											TB_('WARNING: if allowed, users may do XSS hacks, steal passwords from other users, etc.') ),
						array( 'prevent_javascript', 1, TB_('Prevent javascript'), ! $edited_Group->get('perm_xhtml_javascript'), false,
											TB_('WARNING: if allowed, users can easily do XSS hacks, steal passwords from other users, etc.') ),
						array( 'prevent_objects', 1, TB_('Prevent objects'), ! $edited_Group->get('perm_xhtml_objects'), false,
											TB_('WARNING: if allowed, users can spread viruses and malware through this blog.') ),
					), 'xhtml_security', TB_('Security filters') );

	$Form->checkbox( 'apply_antispam', ! $edited_Group->get('perm_bypass_antispam'), TB_('Antispam filtering'),
										TB_('Inputs from these users will be checked against the antispam blacklist.') );

	// Display pluggable permissions:
	display_pluggable_permissions( $Form, 'blogging' );

$Form->end_fieldset();

$Form->begin_fieldset( TB_('Additional permissions').get_manual_link('group-properties-additional-permissions'), array( 'id' => 'additional', 'style' => $primary_panels_style ) );

	$Form->radio( 'edited_grp_perm_stats', $edited_Group->get('perm_stats'),
			array(  $perm_none_option,
							array( 'user', TB_('View stats for specific blogs'), TB_('Based on each blog\'s edit permissions') ), // fp> dirty hack, I'll tie this to blog edit perm for now
							array( 'view', TB_('View stats for all blogs') ),
							array( 'edit', TB_('Full Access'), TB_('Includes deleting/reassigning of stats') )
						), TB_('Analytics'), true );

	// Display pluggable permissions:
	display_pluggable_permissions( $Form, 'additional' );

$Form->end_fieldset();

$Form->begin_fieldset( TB_('File permissions').get_manual_link('group-properties-file-permissions'), array( 'id' => 'file', 'style' => $primary_panels_style ) );
	display_pluggable_permissions( $Form, 'file' );
$Form->end_fieldset();

$Form->begin_fieldset( TB_('System admin permissions').get_manual_link('group-properties-system-permissions'), array( 'id' => 'system', 'style' => $primary_panels_style ) );

	// Display pluggable permissions:
	display_pluggable_permissions( $Form, 'core' );

	// show Settings children permissions only if this user group has at least "View details" rights on global System Settings
	echo '<div id="perm_options_children"'.( $edited_Group->check_perm( 'options', 'view' ) ? '' : ' style="display:none"' ).'>';
	display_pluggable_permissions( $Form, 'core2' );
	display_pluggable_permissions( $Form, 'system' );
	echo '</div>';

	display_pluggable_permissions( $Form, 'core3' );

$Form->end_fieldset();

$Form->begin_fieldset( TB_( 'Notification options').get_manual_link('notification-options'), array( 'id' => 'notification', 'style' => $primary_panels_style ) );

	// Display pluggale notification options
	display_pluggable_permissions( $Form, 'notifications');

$Form->end_fieldset();

if( $action != 'view' )
{	// If current User can edit this group:

	// Display plugin settings per group:
	global $Plugins;
	load_funcs( 'plugins/_plugin.funcs.php' );

	$Plugins->restart();
	while( $loop_Plugin = & $Plugins->get_next() )
	{
		if( ! $loop_Plugin->GroupSettings )
		{	// Skip plugin without group settings:
			continue;
		}

		// We use output buffers here to display the fieldset only, if there's content in there (either from PluginUserSettings or PluginSettingsEditDisplayAfter).
		ob_start();
		$Form->begin_fieldset( $loop_Plugin->name.' '.$loop_Plugin->get_help_link( '$help_url' ) );

		ob_start();
		$tmp_params = array(
				'for_editing' => true,
				'group_ID'    => $edited_Group->ID
			);
		$plugin_group_settings = $loop_Plugin->GetDefaultGroupSettings( $tmp_params );
		if( is_array( $plugin_group_settings ) )
		{
			foreach( $plugin_group_settings as $l_name => $l_meta )
			{	// Display form field for this setting:
				autoform_display_field( $l_name, $l_meta, $Form, 'GroupSettings', $loop_Plugin, $edited_Group );
			}
		}

		$has_contents = strlen( ob_get_contents() );
		$Form->end_fieldset();

		if( $has_contents )
		{	// Print out plugin group settings:
			ob_end_flush();
			ob_end_flush();
		}
		else
		{	// No content, discard output buffers:
			ob_end_clean();
			ob_end_clean();
		}
	}

	// Display a button to save changes:
	$Form->buttons( array( array( '', '', TB_('Save Changes!'), 'SaveButton' ) ) );
}

$Form->end_form();

// set shared root permission availability, when form was loaded and when file perms was changed
?>
<script>
<?php
global $Settings;
if( $Settings->get('fm_enable_roots_shared') )
{	// asimo> this may belong to the pluggable permissions display
	// javascript to handle shared root permissions, when file permission was changed:
?>
function file_perm_changed()
{
	var file_perm = jQuery( '[name="edited_grp_perm_files"]:checked' ).val();
	if( file_perm == null )
	{ // there is file perms radio
		return;
	}

	switch( file_perm )
	{
	case "none":
		jQuery('#edited_grp_perm_shared_root_radio_2').attr('disabled', 'disabled');
		jQuery('#edited_grp_perm_shared_root_radio_3').attr('disabled', 'disabled');
		jQuery('#edited_grp_perm_shared_root_radio_4').attr('disabled', 'disabled');
		break;
	case "view":
		jQuery('#edited_grp_perm_shared_root_radio_2').removeAttr('disabled');
		jQuery('#edited_grp_perm_shared_root_radio_3').attr('disabled', 'disabled');
		jQuery('#edited_grp_perm_shared_root_radio_4').attr('disabled', 'disabled');
		break;
	case "add":
		jQuery('#edited_grp_perm_shared_root_radio_2').removeAttr('disabled');
		jQuery('#edited_grp_perm_shared_root_radio_3').removeAttr('disabled');
		jQuery('#edited_grp_perm_shared_root_radio_4').attr('disabled', 'disabled');
		break;
	default:
		jQuery('#edited_grp_perm_shared_root_radio_2').removeAttr('disabled');
		jQuery('#edited_grp_perm_shared_root_radio_3').removeAttr('disabled');
		jQuery('#edited_grp_perm_shared_root_radio_4').removeAttr('disabled');
	}
}

file_perm_changed();
jQuery( '[name="edited_grp_perm_files"]' ).click( function() {
	file_perm_changed();
} );
<?php } ?>

jQuery( 'input[name=edited_grp_perm_options]' ).click( function()
{	// Show/Hide the children permissions of the Settings permission
	if( jQuery( this ).val() == 'none' )
	{
		jQuery( 'div#perm_options_children' ).hide();
	}
	else
	{
		jQuery( 'div#perm_options_children' ).show();
	}
} );

jQuery( 'input[name=edited_grp_usage]' ).click( function()
{	// Show/Hide the children permissions of the Settings permission
	var primary_field_ids = '#fieldset_wrapper_evobar, #fieldset_wrapper_blogging, #fieldset_wrapper_additional, #fieldset_wrapper_system, #fieldset_wrapper_notification';
	if( jQuery( this ).val() == 'primary' )
	{
		jQuery( primary_field_ids ).show();
		jQuery( 'fieldset', primary_field_ids ).show();
	}
	else
	{
		jQuery( primary_field_ids ).hide();
		jQuery( 'fieldset', primary_field_ids ).hide();
	}
} );

function set_activity_default_section( set_default_section )
{
	var checked_new_coll = jQuery( 'input[name=edited_grp_perm_createblog], input[name=edited_grp_perm_getblog]' ).is( ':checked' );
	// Enable/Disable if at least one checkbox(to create new collection) is checked:
	jQuery( 'select[name=edited_grp_perm_default_sec_ID], input[type=checkbox][name="edited_grp_perm_allowed_sections[]"]' ).prop( 'disabled', ! checked_new_coll );
	if( set_default_section && checked_new_coll )
	{	// Auto select allowed section from current default section:
		var selected_default_section_ID = jQuery( 'select[name=edited_grp_perm_default_sec_ID] option:selected' ).val();
		jQuery( 'input[type=checkbox][name="edited_grp_perm_allowed_sections[]"][value=' + selected_default_section_ID + ']' ).prop( 'checked', true );
	}
}
jQuery( 'input[name=edited_grp_perm_createblog], input[name=edited_grp_perm_getblog]' ).click( function()
{	// Enable/Disable setting "Default Section" depending on checkboxes of "Creating new blogs":
	set_activity_default_section( true );
} );
set_activity_default_section( false );
</script>
