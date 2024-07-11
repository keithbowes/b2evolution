<?php
/**
 * This file display the Domain edit form
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $edited_Domain;

// Determine if we are creating or updating...
global $action;
$creating = $action == 'domain_new';

$Form = new Form( NULL, 'domain_checkchanges', 'post', 'compact' );

$Form->global_icon( TB_('Cancel editing').'!', 'close', regenerate_url( 'action,domain_ID' ) );

$Form->begin_form( 'fform', $creating ?  TB_('New Domain') : TB_('Domain') );

	$Form->add_crumb( 'domain' );
	$Form->hidden( 'action', 'domain_update' );
	$Form->hidden( 'dom_ID', $edited_Domain->ID );
	$Form->hidden_ctrl();
	$Form->hidden( 'tab', get_param( 'tab' ) );
	$Form->hidden( 'tab_from', get_param( 'tab_from' ) );
	$Form->hidden( 'tab3', get_param( 'tab3' ) );

	$Form->text_input( 'dom_name', $edited_Domain->get( 'name' ), 50, TB_('Name'), '', array( 'maxlength' => 250, 'required' => true ) );

	$Form->select_input_array( 'dom_type', $edited_Domain->get( 'type' ), stats_dom_type_titles() , TB_('Referrer type'), '', array( 'force_keys_as_values' => true, 'required' => true ) );

	$Form->text_input( 'dom_source_tag', $edited_Domain->get( 'source_tag' ), 32, TB_('Source Tag'), '', array( 'maxlength' => 32 ) );

	$Form->select_input_array( 'dom_status', $edited_Domain->get( 'status' ), stats_dom_status_titles() , TB_('Spam status'), '', array( 'force_keys_as_values' => true, 'background_color' => stats_dom_status_colors(), 'required' => true ) );

	$Form->text_input( 'dom_comment', $edited_Domain->get( 'comment' ), 255, TB_('Comment'), '', array( 'maxlength' => 255 ) );

$Form->end_form( array( array( 'submit', 'submit', $creating ? TB_('Record') : TB_('Save Changes!'), 'SaveButton' ) ) );

?>
