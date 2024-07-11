<?php
/**
 * This file implements the UI for file deletion
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @global Filelist
 */
global $selected_Filelist;


$Form = new Form( NULL, 'filesdelete_checkchanges', 'post', 'compact' );

$Form->global_icon( TB_('Cancel delete!'), 'close', regenerate_url() );

$Form->begin_form( 'fform', TB_('Confirm delete'), array( 'formstart_class' => 'panel-danger' ) );

	$Form->add_crumb( 'file' );
	$Form->hidden_ctrl();
	$Form->hiddens_by_key( get_memorized() );
	$Form->hidden( 'action', 'delete' );
	$Form->hidden( 'confirmed', 1 );

	echo $selected_Filelist->count() > 1
		? TB_('Do you really want to delete the following files?')
		: TB_('Do you really want to delete the following file?');

	$selected_Filelist->restart();
	echo '<ul>';
		while( $l_File = & $selected_Filelist->get_next() )
		{
			echo '<li>'.$l_File->get_prefixed_name().'</li>';
		}
	echo '</ul>';

	echo '<div class="checkbox"><label><input type="checkbox" name="delete_nonempty" value="1" /> '
		.TB_('Delete non-empty folders').'</label></div><br>';

	$Form->button_input( array( 'value' => TB_('Delete'), 'class' => 'btn-danger' ) );

$Form->end_form();
?>
