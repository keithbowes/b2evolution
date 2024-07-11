<?php
/**
 * This file implements the Skin properties form.
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
 * @author fplanque: Francois PLANQUE.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $Collection, $Blog, $Settings, $AdminUI, $skin_type, $admin_url, $mode;

switch( $skin_type )
{
	case 'normal':
		$skin_ID = isset( $Blog ) ? $Blog->get( 'normal_skin_ID' ) : $Settings->get( 'normal_skin_ID' );
		$fieldset_title = isset( $Blog ) ? TB_('Standard skin for this collection') : TB_('Standard skin for this site');
		break;

	case 'mobile':
		$skin_ID = isset( $Blog ) ? $Blog->get( 'mobile_skin_ID', array( 'real_value' => true ) ) : $Settings->get( 'mobile_skin_ID', true );
		$fieldset_title = isset( $Blog ) ? TB_('Phone skin for this collection') : TB_('Phone skin for this site');
		break;

	case 'tablet':
		$skin_ID = isset( $Blog ) ? $Blog->get( 'tablet_skin_ID', array( 'real_value' => true ) ) : $Settings->get( 'tablet_skin_ID', true );
		$fieldset_title = isset( $Blog ) ? TB_('Tablet skin for this collection') : TB_('Tablet skin for this site');
		break;

	case 'alt':
		$skin_ID = isset( $Blog ) ? $Blog->get( 'alt_skin_ID', array( 'real_value' => true ) ) : $Settings->get( 'alt_skin_ID', true );
		$fieldset_title = isset( $Blog ) ? TB_('Alt skin for this collection') : TB_('Alt skin for this site');
		break;

	default:
		debug_die( 'Wrong skin type: '.$skin_type );
}

$link_select_skin = action_icon( TB_('Select another skin...'), 'choose',
		regenerate_url( 'action,mode', 'skinpage=selection&amp;skin_type='.$skin_type ),
		' '.TB_('Choose a different skin').' &raquo;', 3, 4, array(
			'class' => $mode == 'customizer' ? 'small' : 'action_icon btn btn-info btn-sm',
			'target' => $mode == 'customizer' ? '_top' : '',
	) );
$link_reset_params = '';

// Check if current user can edit skin settings:
$can_edit_skin_settings =
	// When skin ID has a real value ( when $skin_ID = 0 means it must be the same as the normal skin value )
	$skin_ID &&
		// If current User can edit collection properties:
	( ( isset( $Blog ) && check_user_perm( 'blog_properties', 'edit', false, $Blog->ID ) ) ||
		// If site skins are enabled and current User can edit site options:
		( $Settings->get( 'site_skins_enabled' ) && check_user_perm( 'options', 'edit' ) )
	);

if( $can_edit_skin_settings )
{	// Display "Reset params" button if current User can edit skin settings:
	$link_reset_url = regenerate_url( 'ctrl,action', 'ctrl=skins&amp;skin_ID='.$skin_ID.'&amp;skin_type='.$skin_type.'&amp;blog='.( isset( $Blog ) ? $Blog->ID : get_working_blog() ).'&amp;action='.( isset( $Blog ) ? 'reset_coll' : 'reset_site' ).'&amp;'.url_crumb( 'skin' ) );
	$link_reset_params = action_icon( TB_('Reset params'), 'reload',
			$link_reset_url,
			' '.TB_('Reset params'), 3, 4, array(
				'class'   => $mode == 'customizer' ? 'small' : 'action_icon btn btn-default btn-sm',
				'onclick' => 'return evo_confirm_skin_reset()',
				'target' => $mode == 'customizer' ? 'evo_customizer__backoffice' : '',
		) );
}

if( $mode == 'customizer' )
{	// Display customizer tabs to switch between site/collection skins and widgets in special div on customizer mode:
	$AdminUI->display_customizer_tabs( array(
			'path'         => isset( $Blog ) ? array( 'coll', 'skin' ) : 'site',
			'action_links' => $link_select_skin.$link_reset_params
		) );

	// Start of customizer content:
	echo '<div class="evo_customizer__content">';
}

$Form = new Form( NULL, 'skin_settings_checkchanges', 'post', ( $mode == 'customizer' ? 'accordion' : NULL ) );

$Form->begin_form( 'fform' );

	$Form->hidden_ctrl();

	if( isset( $Blog ) )
	{
		$Form->add_crumb( 'collection' );
		$Form->hidden( 'tab', 'skin' );
		$Form->hidden( 'skin_type', $skin_type );
		$Form->hidden( 'action', 'update' );
		$Form->hidden( 'blog', $Blog->ID );
		$Form->hidden( 'mode', $mode );
	}
	else
	{
		$Form->add_crumb( 'siteskin' );
		$Form->hidden_ctrl();
		$Form->hidden( 'tab', 'site_skin' );
		$Form->hidden( 'skin_type', $skin_type );
		$Form->hidden( 'action', 'update_site_skin' );
	}

	// Initialize a link to go to site/collection skin settings:
	if( isset( $Blog ) )
	{	// If collection skin page is opened currently:
		if( check_user_perm( 'options', 'view' ) )
		{	// If current user has a permission to view site skin:
			$goto_link_url = $admin_url.'?ctrl=collections&amp;tab=site_skin'.( $skin_type == 'mobile' || $skin_type == 'tablet' || $skin_type == 'alt' ? '&amp;skin_type='.$skin_type : '' );
			$goto_link_title = TB_('Go to Site skin');
		}
		// Append manual/doc link:
		$fieldset_title .= get_manual_link( 'blog-skin-settings' );
	}
	else
	{	// If site skin page is opened currently:
		if( ( $working_coll_ID = get_working_blog() ) &&
		    check_user_perm( 'blog_properties', 'edit', false, $working_coll_ID ) )
		{	// If working collection is set and current user has a permission to edit the collection skin:
			$goto_link_url = $admin_url.'?ctrl=coll_settings&amp;tab=skin&amp;blog='.$working_coll_ID.( $skin_type == 'mobile' || $skin_type == 'tablet' || $skin_type == 'alt' ? '&amp;skin_type='.$skin_type : '' );
			$goto_link_title = TB_('Go to Collection skin');
		}
		// Append manual/doc link:
		$fieldset_title .= get_manual_link( 'site-skin-settings' );
	}
	if( isset( $goto_link_url ) )
	{
		$fieldset_title .= ' <span class="panel_heading_action_icons"><a href="'.$goto_link_url.'" class="btn btn-sm btn-info">'.$goto_link_title.' &raquo;</a></span>';
	}

	display_skin_fieldset( $Form, $skin_ID, array(
			'fieldset_title' => $fieldset_title,
			'fieldset_links' => '<span class="panel_heading_action_icons pull-right">'.$link_select_skin.$link_reset_params.'</span><div class="clearfix"></div>'
		) );

$buttons = array();
if( $can_edit_skin_settings )
{	// Display a button to update skin params only when if current User can edit this:
	$buttons[] = array( 'submit', 'save', ( $mode == 'customizer' ? TB_('Apply Changes!') : TB_('Save Changes!') ), 'SaveButton', 'data-shortcut' => 'ctrl+s,command+s' );
	if( $mode == 'customizer' )
	{	// Display cancel button only on customizer mode:
		// Reload settings from DB, which may be different from "reset" setting since the user has clicked "Apply changes"
		$buttons[] = array( 'button', 'cancel', T_('Cancel'), '', 'location.reload()' );
	}
}

if( $mode == 'customizer' )
{	// Display buttons in special div on customizer mode:
	echo '<div class="evo_customizer__buttons">';
	$Form->buttons( $buttons );
	echo '</div>';
	// Clear buttons to don't display them twice:
	$buttons = array();
}

$Form->end_form( $buttons );

if( $mode == 'customizer' )
{	// End of customizer content:
	echo '</div>';
}

if( isset( $link_reset_url ) )
{	// Initialize JS to confirm skin reset action if current user has a permission:
	$skin_reset_confirmation_msg = TS_( 'This will reset all the params to the defaults recommended by the skin.\nYou will lose your custom settings.\nAre you sure?' );
?>
<script>
function evo_confirm_skin_reset()
{
<?php
if( $mode == 'customizer' )
{	// If skin customizer mode:
?>
	window.parent.openModalWindow( '<form action="<?php echo str_replace( '&amp;', '&', $link_reset_url ); ?>" method="post" target="evo_customizer__backoffice" onsubmit="closeModalWindow()">' +
				'<span class="text-danger"><?php echo $skin_reset_confirmation_msg; ?></span>' +
				'<input type="submit" value="<?php echo TS_('Reset params'); ?>" />' +
			'</form>',
		'500px', '100px', true, '<?php echo TS_('Reset params'); ?>', [ '<?php echo TS_('Reset params'); ?>', 'btn btn-danger' ] );
	return false;
<?php
}
else
{	// Normal back-office mode:
?>
	return confirm( '<?php echo $skin_reset_confirmation_msg; ?>' );
<?php
}
?>
}
<?php
if( $mode == 'customizer' )
{	// Reload front-office iframe on cancel changes:
?>
jQuery( 'input[type=button][name=cancel]' ).on( 'click', function()
{
	if( typeof( parent.evo_customizer_reload_frontoffice ) == "function" )
	{
		parent.evo_customizer_reload_frontoffice();
	}
} );
<?php
}
?>
jQuery( document ).ready( function()
{	// Restore skin setting to default value:
	jQuery( '[data-default-value]' ).click( function()
	{
		var setting_input = jQuery( this ).closest( '[id^=ffield_edit_skin_]' ).find( 'input,select' );
		if( setting_input.length &&
		    setting_input.val() != jQuery( this ).data( 'default-value' ) )
		{	// Restore only not default value:
			setting_input.val( jQuery( this ).data( 'default-value' ) );
			if( typeof( parent.evo_customizer_update_style ) == "function" )
			{	// Update style in designer customizer mode if it is enabled currently:
				if( setting_input.hasClass( 'form_color_input' ) )
				{	// For color input enough to fire event "change":
					setting_input.trigger( 'change' );
				}
				else
				{	// For other fields we should update style by function:
					parent.evo_customizer_update_style( setting_input );
				}
			}
		}
	} );
} );
</script>
<?php
}

// Enable JS for fieldset folding:
echo_fieldset_folding_js();

// Additional JavaScript code for skin settings form:
$SkinCache = & get_SkinCache();
if( $edited_Skin = & $SkinCache->get_by_ID( $skin_ID, false, false ) )
{
	$edited_Skin->echo_settings_form_js();
}
?>
