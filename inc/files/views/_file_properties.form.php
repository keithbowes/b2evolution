<?php
/**
 * This file implements the UI controller for file upload.
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
 * @global File
 */
global $edited_File, $selected_Filelist;

global $blog, $filename_max_length;

global $Settings, $admin_url;

$edit_allowed_perm = check_user_perm( 'files', 'edit_allowed', false, $selected_Filelist->get_FileRoot() );

$Form = new Form( $admin_url, 'fm_properties_checkchanges' );

if( get_param( 'mode' ) != 'modal' )
{
	$Form->global_icon( TB_('Close properties!'), 'close', regenerate_url() );
}

$Form->begin_form( 'fform', ( get_param( 'mode' ) == 'modal' ? '' : TB_('File properties') ) );

	$Form->add_crumb( 'file' );
	$Form->hidden_ctrl();
	$Form->hidden( 'action', 'update_properties' );
	$Form->hiddens_by_key( get_memorized() );

	$Form->begin_fieldset( TB_('Properties') );
		if( $edit_allowed_perm )
		{ // User can edit:
			$Form->text( 'name', $edited_File->dget('name'), 32, TB_('Filename'), TB_('This is the name of the file on the server hard drive.'), $filename_max_length );
		}
		else
		{ // User can view only:
			$Form->info( TB_('Filename'), $edited_File->dget('name'), TB_('This is the name of the file on the server hard drive.') );
		}
		$Form->info( TB_('Type'), $edited_File->get_icon().' '.$edited_File->get_type() );
		if( $edited_File->is_image() )
		{
			$Form->info( TB_('Dimensions'), $edited_File->get_image_size().' px' );
			$Form->checkbox( 'resize_image', 0, TB_('Resize'), /* TRANS: %s is image dimension */ sprintf( TB_('Check to resize and fit into %s px' ),
					'<a href="'.get_dispctrl_url( 'fileset').'">'.$Settings->get( 'fm_resize_width' ).'x'.$Settings->get( 'fm_resize_height' ).'</a>' ) );
		}
	$Form->end_fieldset();

	$Form->begin_fieldset( TB_('Meta data') );
		if( $edit_allowed_perm )
		{ // User can edit:
			$Form->text( 'title', $edited_File->title, 50, TB_('Long title'), TB_('This is a longer descriptive title'), 255 );
			$Form->text( 'alt', $edited_File->alt, 50, TB_('Alternative text'), TB_('This is useful for images'), 255 );
			$Form->textarea( 'desc', $edited_File->desc, 10, TB_('Caption/Description') );
		}
		else
		{ // User can view only:
			$Form->info( TB_('Long title'), $edited_File->dget('title'), TB_('This is a longer descriptive title') );
			$Form->info( TB_('Alternative text'), $edited_File->dget('alt'), TB_('This is useful for images') );
			$Form->info( TB_('Caption/Description'), $edited_File->dget('desc') );
		}
	$Form->end_fieldset();

	$Form->begin_fieldset( TB_('Social votes') );
		$Form->info( TB_('Liked'), $edited_File->get_votes_count_info( 'like' ) );
		$Form->info( TB_('Disliked'), $edited_File->get_votes_count_info( 'dislike' ) );
		$Form->info( TB_('Reported as inappropriate'), $edited_File->get_votes_count_info( 'inappropriate' ) );
		$Form->info( TB_('Reported as spam'), $edited_File->get_votes_count_info( 'spam' ) );
	$Form->end_fieldset();

if( $edit_allowed_perm )
{ // User can edit:
	$Form->end_form( array( array( 'submit', '', TB_('Save Changes!'), 'SaveButton' ) ) );
}
else
{ // User can view only:
	$Form->end_form();
}

?>
