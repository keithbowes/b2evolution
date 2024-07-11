<?php
/**
 * This file implements the Item history details view
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

global $edited_Item, $Revision;

$post_statuses = get_visibility_statuses();

switch( $Revision->iver_type )
{
	case 'archived':
		$revision_note = T_('Archived version');
		$revision_title = sprintf( T_('Archived version #%s for: %s'), $Revision->iver_ID, $edited_Item->get_title() );
		break;

	case 'proposed':
		$revision_note = T_('Proposed change');
		$revision_title = sprintf( T_('Proposed change #%s for: %s'), $Revision->iver_ID, $edited_Item->get_title() );
		break;

	case 'current':
	default:
		$revision_note = T_('Archived version');
		$revision_title = sprintf( T_('Current version for: %s'), $edited_Item->get_title() );
		break;
}

$Form = new Form( NULL, 'history', 'post', 'compact' );

$Form->global_icon( T_('Cancel viewing!'), 'close', regenerate_url( 'action', 'action=history' ) );

$Form->begin_form( 'fform', $revision_title );

$Form->info( T_('Date'), mysql2localedatetime( $Revision->iver_edit_last_touched_ts, 'Y-m-d', 'H:i:s' ) );

$iver_editor_user_link = get_user_identity_link( NULL, $Revision->iver_edit_user_ID );
$Form->info( T_('User'), ( empty( $iver_editor_user_link ) ? T_( '(deleted user)' ) : $iver_editor_user_link ) );

$Form->info( T_('Status'), $post_statuses[ $Revision->iver_status ] );

$Form->info( T_('Note'), $revision_note );

$Form->info( T_('Title'), $Revision->iver_title );

$Form->info( T_('Content'), $Revision->iver_content );

$edited_Item->set( 'revision', $Revision->param_ID );
$Form->info( T_('Custom fields'), $edited_Item->get_custom_fields( array(
		'before'       => '<div class="evo_content_block"><table class="item_custom_fields" style="margin:0">',
		'field_format' => '<tr><th valign="top">$title$:</th><td>$value$</td></tr>',
		'after'        => '</table></div>',
	) ) );

$Form->end_form();

// JS code for merge button:
echo_item_merge_js();
?>
