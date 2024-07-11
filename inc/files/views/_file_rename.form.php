<?php
/**
 * This file implements the UI for file rename
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

/**
 * @global Filelist
 */
global $selected_Filelist;

/**
 * @global string
 */
global $new_names;

if( !empty( $new_names ) )
{

	$Form = new Form( NULL, 'fm_rename_checkchanges' );

	$Form->global_icon( TB_('Cancel rename!'), 'close', regenerate_url() );

	$Form->begin_form( 'fform', TB_('Rename') );

		$Form->add_crumb( 'file' );
		$Form->hidden_ctrl();
		$Form->hiddens_by_key( get_memorized() );
		$Form->hidden( 'action', 'rename' );
		$Form->hidden( 'confirmed', 1 );

		$selected_Filelist->restart();
		while( $loop_src_File = & $selected_Filelist->get_next() )
		{
			if( !isset( $new_names[$loop_src_File->get_md5_ID()] ) )
			{
				continue;
			}

			$Form->begin_fieldset( TB_('File').': '.$loop_src_File->dget('name') );

			$Form->text( 'new_names['.$loop_src_File->get_md5_ID().']', $new_names[$loop_src_File->get_md5_ID()], 32,
										TB_('New name'), $loop_src_File->dget('title'), 128 );

			$Form->end_fieldset();
		}


	$Form->end_form( array( array( 'submit', 'submit', TB_('Rename'), 'SaveButton' ) ) );
	}
?>
