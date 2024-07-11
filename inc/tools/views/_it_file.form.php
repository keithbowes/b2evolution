<?php
/**
 * This file display the 1st step of Item Type importer
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

global $admin_url, $media_subdir, $media_path, $Session;

$Form = new Form( NULL, '', 'post', NULL, 'multipart/form-data' );

$Form->begin_form( 'fform', TB_('Item Type Importer') );

$Form->add_crumb( 'itimport' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'import' );

// Display a panel to upload files before import:
$import_files = display_importer_upload_panel( array(
		'allowed_extensions'  => 'xml',
		'display_type'        => true,
		'help_slug'           => 'item-type-importer',
		'refresh_url'         => $admin_url.'?ctrl=itimport',
	) );

if( ! empty( $import_files ) )
{
	$import_type = param( 'import_type', 'string', 'skip' );

	$Form->begin_fieldset( TB_('Options') );

	$BlogCache = & get_BlogCache();
	$BlogCache->load_all( 'shortname,name', 'ASC' );

	$last_import_coll_IDs = $Session->get( 'last_import_coll_IDs' );
	$coll_options = array();
	foreach( $BlogCache->cache as $it_Blog )
	{
		$coll_options[] = array( 'it_blog_IDs[]', $it_Blog->ID, $it_Blog->get_extended_name(), ( is_array( $last_import_coll_IDs ) && in_array( $it_Blog->ID, $last_import_coll_IDs ) ) );
	}
	$Form->checklist( $coll_options, 'it_blog_IDs', TB_('Enable for collections'), false, false, array(
			'note' => TB_('All Item Types from the selected XML file will be enabled for the checked collections.').' <a href="'.$admin_url.'?ctrl=collections&action=new">'.TB_('Create new blog').' &raquo;</a>',
		) );

	$Form->radio_input( 'import_type', $import_type, array(
				array(
					'value' => 'skip',
					'label' => TB_('Import only non existing Item Types'),
				),
				array(
					'value' => 'update',
					'label' => TB_('Update existing Item Types'),
					'note'  => TB_('This may DELETE some existing custom fields.'),
					'suffix' => '<br /><div id="import_type_update_confirm_block" class="alert alert-danger" style="display:none;margin:0">'.TB_('WARNING').': '.TB_('you will LOSE custom fields that are not part of the Item Types you import.').' '.sprintf( TB_('Type %s to confirm'), '<code>DELETE</code>' ).': <input name="import_type_update_confirm" type="text" class="form-control" size="8" style="margin:-8px 0" /></div>',
				),
			), TB_('Import mode'), array( 'lines' => true ) );

	$Form->end_fieldset();

	$Form->buttons( array( array( 'submit', 'submit', TB_('Continue').'!', 'SaveButton' ) ) );
}

$Form->end_form();
?>
<script>
function evo_it_import_update_mode_visibility()
{	// Show/Hide additional options for import mode:
	jQuery( '#import_type_update_confirm_block' ).css( 'display', ( jQuery( 'input[name=import_type]:checked' ).val() == 'update' ? 'inline-block' : 'none' ) );
}
jQuery( 'input[name=import_type]' ).click( evo_it_import_update_mode_visibility );
jQuery( document ).ready( evo_it_import_update_mode_visibility );
</script>