<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2018 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


$Form = new Form( NULL, 'itemtype_edit_field' );

$Form->begin_form( 'fform' );

$Form->hidden( 'itcf_ID', get_param( 'itcf_ID' ) );

// Order:
$Form->text( 'itcf_order', get_param( 'itcf_order' ), 6, TB_('Order'), '', 11 );

// Title:
$Form->text( 'itcf_label', get_param( 'itcf_label' ), 120, TB_('Label'), '', 255 );

// Name:
$Form->text( 'itcf_name', get_param( 'itcf_name' ), 60, TB_('Name'), '', 255 );

// Schema Property Name:
$Form->text( 'itcf_schema_prop', get_param( 'itcf_schema_prop' ), 60, TB_('Schema Property Name'), '', 255 );

// Type:
$Form->info( TB_('Type'), get_item_type_field_type_title( get_param( 'itcf_type' ) ) );

// Format:
switch( get_param( 'itcf_type' ) )
{
	case 'double':
	case 'computed':
	case 'separator':
	case 'url':
		$Form->text( 'itcf_format', get_param( 'itcf_format' ), 60, TB_('Format'), '', 2000 );
		break;
	case 'image':
		global $thumbnail_sizes;
		$Form->select_input_array( 'itcf_format', get_param( 'itcf_format' ), array_keys( $thumbnail_sizes ), TB_('Format') );
		break;
}

// Formula:
if( get_param( 'itcf_type' ) == 'computed' )
{
	$Form->text( 'itcf_formula', get_param( 'itcf_formula' ), 100, TB_('Formula'), '', 2000 );
}

// Note:
$Form->text( 'itcf_note', get_param( 'itcf_note' ), 60, TB_('Note'), '', 255 );

// Required:
$Form->checkbox( 'itcf_required', get_param( 'itcf_required' ), TB_('Required') );

// With MC(Internal Comment):
$Form->checkbox( 'itcf_meta', get_param( 'itcf_meta' ), TB_('With IC'), TB_('Update also on Internal Comment form') );

// Public:
$Form->checkbox( 'itcf_public', get_param( 'itcf_public' ), TB_('Public') );

// Display condition:
$Form->text( 'itcf_disp_condition', get_param( 'itcf_disp_condition' ), 60, TB_('Display condition'), '', 2000 );

// Header cell class:
$Form->text( 'itcf_header_class', get_param( 'itcf_header_class' ), 60, TB_('Header cell class'), sprintf( TB_('Enter class names such as %s etc. (Separate with space)'), '<code>left</code> <code>center</code> <code>right</code> <code>nowrap</code>' ), 255 );

// Data cell class:
if( get_param( 'itcf_type' ) != 'separator' )
{
	$Form->text( 'itcf_cell_class', get_param( 'itcf_cell_class' ), 60, TB_('Data cell class'), sprintf( TB_('Enter class names such as %s etc. (Separate with space)'), '<code>left</code> <code>center</code> <code>right</code> <code>red</code>' ), 255 );
}

// Link options:
if( ! in_array( get_param( 'itcf_type' ), array( 'text', 'html', 'separator' ) ) )
{
	$Form->begin_line( TB_('Link') );
		// Link:
		$Form->select_input_array( 'itcf_link', get_param( 'itcf_link' ), get_item_type_field_linkto_options( get_param( 'itcf_type' ) ), '', '', array( 'force_keys_as_values' => true ) );
		// No follow:
		$Form->checkbox_input( 'itcf_link_nofollow', get_param( 'itcf_link_nofollow' ), '', array( 'input_prefix' => '<label class="text-normal">', 'input_suffix' => ' '.TB_('No Follow').'</label>' ) );
	$Form->end_line();

	// Link class:
	$Form->text( 'itcf_link_class', get_param( 'itcf_link_class' ), 60, TB_('Link class'), sprintf( TB_('Enter class names such as %s etc. (Separate with space)'), '<code>btn btn-sm btn-info</code>' ), 255 );
}

// Highlight options:
if( get_param( 'itcf_type' ) != 'separator' )
{
	$Form->select_input_array( 'itcf_line_highlight', get_param( 'itcf_line_highlight' ), get_item_type_field_highlight_options( 'line' ), TB_('Line highlight'), '', array( 'force_keys_as_values' => true ) );
	$Form->select_input_array( 'itcf_green_highlight', get_param( 'itcf_green_highlight' ), get_item_type_field_highlight_options( 'green' ), TB_('Green highlight'), '', array( 'force_keys_as_values' => true ) );
	$Form->select_input_array( 'itcf_red_highlight', get_param( 'itcf_red_highlight' ), get_item_type_field_highlight_options( 'red' ), TB_('Red highlight'), '', array( 'force_keys_as_values' => true ) );
}

// Description:
$Form->textarea( 'itcf_description', get_param( 'itcf_description' ), 3, TB_('Description') );

// Auto merge:
if( get_param( 'itcf_type' ) != 'separator' )
{
	$Form->checkbox( 'itcf_merge', get_param( 'itcf_merge' ), TB_('Auto merge'), TB_('Merge the column cells if the value is identical to the next') );
}

$Form->end_form( array( array( 'submit', 'actionArray[select_custom_fields]', TB_('Update'), 'SaveButton' ) ) );
?>