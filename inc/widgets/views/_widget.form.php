<?php
/**
 * This file implements the UI view for the widgets params form.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_funcs('plugins/_plugin.funcs.php');

/**
 * @var ComponentWidget
 */
global $edited_ComponentWidget;
global $Collection, $Blog, $admin_url, $AdminUI, $Plugins, $display_mode, $mode;

// Determine if we are creating or updating...
$creating = is_create_action( $action );

if( $mode == 'customizer' )
{	// Display customizer tabs to switch between skin and widgets in special div on customizer mode:
	$AdminUI->display_customizer_tabs( array(
			'path' => array( 'coll', 'widgets' ),
		) );

	// Start of customizer content:
	echo '<div class="evo_customizer__content">';
}

$Form = new Form( NULL, 'widget_checkchanges' );

if( ( $display_mode == 'normal' && empty( $mode ) ) || ! isset( $AdminUI ) || ! isset( $AdminUI->skin_name ) || $AdminUI->skin_name != 'bootstrap' )
{	// Display a link to close form (Don't display this link on bootstrap skin, because it already has an icon to close a modal window)
	$Form->global_icon( TB_('Cancel editing').'!', 'close', regenerate_url( 'action' ), '', 3, 2, array( 'class' => 'action_icon close_link' ) );
}

if( $mode == 'customizer' )
{	// Don't display this title because it is displayed on customizer mode:
	$form_title = '';
}
else
{	// Set form title for all other display modes:
	$form_title = sprintf( $creating ?  TB_('New widget "%s" in container "%s"') : TB_('Edit widget "%s" in container "%s"'), $edited_ComponentWidget->get_name(), $edited_ComponentWidget->get_container_param( 'name' ) )
		.' '.action_icon( TB_('Open relevant page in online manual'), 'manual', $edited_ComponentWidget->get_help_url(), NULL, 5, NULL, array( 'target' => '_blank' ) );
}
$Form->begin_form( 'fform', $form_title, array( 'data-widget-code' => $edited_ComponentWidget->get( 'code' ) ) );

// Plugin widget form event:
$Plugins->trigger_event( 'WidgetBeginSettingsForm', array(
		'Form'            => & $Form,
		'ComponentWidget' => & $edited_ComponentWidget,
	) );

	$Form->add_crumb( 'widget' );
	$Form->hidden( 'action', $creating ? 'create' : 'update' );
	$Form->hidden( 'wi_ID', $edited_ComponentWidget->ID );
	$Form->hidden( 'display_mode', $display_mode );
	$Form->hiddens_by_key( get_memorized( 'action' ) );

// Display properties:
$Form->begin_fieldset( TB_('Widget info'), array( 'id' => 'widget_info' ) );
	if( $mode == 'customizer' )
	{
		$Form->info( '', $edited_ComponentWidget->get_icon().' '.$edited_ComponentWidget->get_name().' '.$edited_ComponentWidget->get_help_link( 'manual', false ), '<br>'.$edited_ComponentWidget->get_desc() );
	}
	else
	{
		$Form->info( TB_('Widget type'), $edited_ComponentWidget->get_icon().' '.$edited_ComponentWidget->get_name() );
		$Form->info( TB_('Description'), $edited_ComponentWidget->get_desc() );
	}
$Form->end_fieldset();


// Display (editable) parameters:

	$opened_fieldsets = 0;
	$default_fieldset_is_opened = false;

	// Loop through all widget params:
	$tmp_params = array( 'for_editing' => true );
	foreach( $edited_ComponentWidget->get_param_definitions( $tmp_params ) as $l_name => $l_meta )
	{
		if( isset( $l_meta['layout'] ) && $l_meta['layout'] == 'begin_fieldset' )
		{	// Flag to know fieldset is started by widget params config:
			$opened_fieldsets++;
			if( $default_fieldset_is_opened )
			{	// End default fieldset before new start of config fieldset:
				$fieldset_name = 'settings_layout_start';
				$fieldset_meta = array(
						'layout' => 'end_fieldset',
					);
				autoform_display_field( $fieldset_name, $fieldset_meta, $Form, 'Widget', $edited_ComponentWidget );
				$opened_fieldsets--;
				$default_fieldset_is_opened = false;
			}
		}
		elseif( isset( $l_meta['layout'] ) && $l_meta['layout'] == 'end_fieldset' )
		{	// Flag to know fieldset is ended by widget params config:
			$opened_fieldsets--;
		}
		elseif( $opened_fieldsets == 0 && ( ! isset( $l_meta['layout'] ) || $l_meta['layout'] != 'html' ) )
		{	// Start default fieldset if it is not defined in widget params config,
			// excluding case when we should print out some HTML code like JavaScript which is not visible on the screen (to avoid empty fieldset panel):
			$fieldset_name = 'settings_layout_start';
			$fieldset_meta = array(
					'layout' => 'begin_fieldset',
					'label'  => TB_('Settings'),
				);
			autoform_display_field( $fieldset_name, $fieldset_meta, $Form, 'Widget', $edited_ComponentWidget );
			$opened_fieldsets++;
			$default_fieldset_is_opened = true;
		}

		$l_value = NULL;
		if( $l_name == 'allow_blockcache' )
		{
			if( isset( $l_meta['disabled'] )
			    && ( $l_meta['disabled'] == 'disabled' ) )
			{ // Force checkbox "Allow caching" to unchecked when it is disallowed from widget config
				$l_value = 0;
			}

			if( ! $Blog->get_setting( 'cache_enabled_widgets' ) )
			{ // Widget/block cache is disabled by blog setting
				$l_meta['allow_blockcache']['note'] = sprintf( TB_('This widget could be cached but the block cache is OFF. Click <a %s>here</a> to enable.'),
						'href="'.get_admin_url( 'ctrl=coll_settings&amp;tab=advanced&amp;blog='.$Blog->ID ).'#fieldset_wrapper_caching"' );
				$l_meta['disabled'] = 'disabled';
			}
		}

		// Display field:
		autoform_display_field( $l_name, $l_meta, $Form, 'Widget', $edited_ComponentWidget, NULL, $l_value );
	}

	for( $o = 0; $o < $opened_fieldsets; $o++ )
	{	// End all not closed fieldsets:
		$fieldset_name = 'settings_layout_start';
		$fieldset_meta = array(
				'layout' => 'end_fieldset',
			);
		autoform_display_field( $fieldset_name, $fieldset_meta, $Form, 'Widget', $edited_ComponentWidget );
	}


// dh> TODO: allow the widget to display information, e.g. the coll_category_list
//       widget could say which blogs it affects. (Maybe this would be useful
//       for all even, so a default info field(set)).
//       Does a callback make sense? Then we should have a action hook too, to
//       catch any params/settings maybe? Although this could be done in the
//       same hook in most cases probably. (dh)

// Plugin widget form event:
$Plugins->trigger_event( 'WidgetEndSettingsForm', array(
		'Form'            => & $Form,
		'ComponentWidget' => & $edited_ComponentWidget,
	) );

$buttons = array();
$buttons[] = array( 'submit', 'submit', ( $mode == 'customizer' ? TB_('Apply Changes!') : TB_('Save Changes!') ), 'SaveButton', 'data-shortcut' => 'ctrl+enter,command+enter' );
if( $mode == 'customizer' )
{	// Display buttons in special div on customizer mode:
	echo '<div class="evo_customizer__buttons">';
	if( $mode == 'customizer' )
	{	// Display a button-link to go back (only in customizer mode):
		$buttons[] = array( 'button', 'button', TB_('Cancel'),
			'tag'    => 'link',
			'href'   => get_admin_url( 'ctrl=widgets&blog='.$Blog->ID.'&skin_type='.$Blog->get_skin_type().'&action=customize&container_code='.urlencode( $edited_ComponentWidget->get_container_param( 'code' ) ).'&mode=customizer', '&' ),
			'target' => '_self',
		);
	}
	$Form->buttons( $buttons );
	echo '</div>';
	// Clear buttons to don't display them twice:
	$buttons = array();
}
else
{	// Additional button for normal mode in back-office:
	$buttons[] = array( 'submit', 'actionArray[update_edit]', TB_('Save and continue editing...'), 'SaveButton', 'data-shortcut' => 'ctrl+s,command+s' );
}

$Form->end_form( $buttons );

if( $mode == 'customizer' )
{	// End of customizer content:
	echo '</div>';
}

// Enable JS for fieldset folding:
echo_fieldset_folding_js();

// Print out JavaScript code which helps to edit widget form:
if( $widget_javascript = $edited_ComponentWidget->get_edit_form_javascript() )
{
	echo "\n".'<script>'.$widget_javascript.'</script>'."\n";
}

if( $display_mode == 'js' )
{	// Reset previous and Initialize new bozo validator for each new opened widget edit form in popup window,
	// because it is not applied for new created forms dynamically:
?>
<script>
if( typeof( bozo ) != 'undefined' )
{
	bozo.reset_changes();
	bozo.init();
}
</script>
<?php
}
?>
