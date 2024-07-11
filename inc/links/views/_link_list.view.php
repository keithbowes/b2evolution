<?php
/**
 * This file displays the links attached to an Object, which can be an Item, Comment, ... (called within the attachment_frame)
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
 * @var Blog
 */
global $Collection, $Blog;

/**
 * Needed by functions
 * @var LinkOwner
 */
global $LinkOwner;

global $AdminUI, $current_User;

if( ! isset( $Skin ) )
{
	global $Skin;
}

global $fm_mode;

if( empty( $Blog ) )
{
	$Collection = $Blog = & $LinkOwner->get_Blog();
}

if( ! isset( $fieldset_prefix ) )
{	// Define default fieldset prefix:
	// (used to display several fieldset on same page, e.g. for normal and internal comments)
	$fieldset_prefix = '';
}

// Name of the iframe we want some actions to come back to:
$iframe_name = param( 'iframe_name', 'string', '', true );
$link_type = param( 'link_type', 'string', 'item', true );

$SQL = $LinkOwner->get_SQL();

$Results = new Results( $SQL->get(), '', '', 1000 );

$Results->title = T_('Attachments');

$Results->cols[] = array(
						'th' => T_('Icon/Type'),
						'td_class' => 'shrinkwrap',
						'td' => '%link_add_iframe( display_subtype( #link_ID# ) )%',
					);

if( $fm_mode == 'file_select' )
{
	$Results->cols[] = array(
							'th' => T_('Destination'),
							'td' => '%select_link_button( #link_ID# ).\' \'.link_add_iframe( link_destination() )%',
							'td_class' => 'fm_filename',
						);
}
else
{
	$Results->cols[] = array(
							'th' => T_('Destination'),
							'td' => '%link_add_iframe( link_destination() )%',
							'td_class' => 'fm_filename',
						);
}

$Results->cols[] = array(
						'th' => T_('Link ID'),
						'td' => '<span data-order="$link_order$">$link_ID$</span>',
						'th_class' => 'shrinkwrap',
						'td_class' => 'shrinkwrap link_id_cell',
					);

$Results->cols[] = array(
						'th' => T_('Actions'),
						'td_class' => 'shrinkwrap',
						'td' => '%link_actions( #link_ID#, {ROW_IDX_TYPE}, "'.$LinkOwner->type.'" )%',
					);

$Results->cols[] = array(
					'th' => T_('Position'),
					'th_class' => 'shrinkwrap',
					'td_class' => 'nowrap '.( count( $LinkOwner->get_positions() ) > 1 ? 'left' : 'center' ),
					'td' => '%display_link_position( {row}, '.( $fm_mode == 'file_select' ? 'false' : 'true' ).', "'.$fieldset_prefix.'" )%',
				);

// Add attr "id" to handle quick uploader
$tbody_start = '<tbody class="filelist_tbody"'.( $fm_mode == 'file_select' ? ' data-file-select="true"' : '' );
$compact_results_params = is_admin_page() ? $AdminUI->get_template( 'compact_results' ) : $Skin->get_template( 'compact_results' );
$compact_results_params['body_start'] = str_replace( '<tbody', $tbody_start, $compact_results_params['body_start'] );
$compact_results_params['no_results_start'] = str_replace( '<tbody', $tbody_start, $compact_results_params['no_results_start'] );

// Disable flush because it breaks layout when comment form is called from widget "Item Comment Form":
$compact_results_params['disable_evo_flush'] = true;

$Results->display( $compact_results_params );

// Print out JavaScript to change a link position:
echo_link_position_js();
// Print out JavaScript to make links table sortable:
echo_link_sortable_js( $fieldset_prefix );

if( $Results->total_pages == 0 )
{ // If no results we should get a template of headers in order to add it on first quick upload
	ob_start();
	$Results->display_col_headers();
	$table_headers = ob_get_clean();
}
else
{ // Headers are already on the page
	$table_headers = '';
}

// Load FileRoot class to get fileroot ID of collection below:
load_class( '/files/model/_fileroot.class.php', 'FileRoot' );

$link_owner_type = ( $LinkOwner->type == 'temporary' ) ? $LinkOwner->link_Object->tmp_type : $LinkOwner->type;

switch( $link_owner_type )
{
	case 'item':
		$upload_fileroot = FileRoot::gen_ID( 'collection', $LinkOwner->get_blog_ID() );
		$upload_path = '/quick-uploads/'.( $LinkOwner->is_temp() ? 'tmp'.$LinkOwner->get_ID() : $LinkOwner->Item->get( 'urltitle' ) ).'/';
		break;

	case 'comment':
	case 'metacomment':
		$upload_fileroot = FileRoot::gen_ID( 'collection', $LinkOwner->get_blog_ID() );
		$upload_path = '/quick-uploads/'.( $LinkOwner->is_temp() ? 'tmp' : 'c' ).$LinkOwner->get_ID().'/';
		break;

	case 'emailcampaign':
		$upload_fileroot = FileRoot::gen_ID( 'emailcampaign', $LinkOwner->get_ID() );
		$upload_path = '/'.$LinkOwner->get_ID().'/';
		break;

	case 'message':
		$upload_fileroot = FileRoot::gen_ID( 'user', $current_User->ID );
		$upload_path = '/private_message/'.( $LinkOwner->is_temp() ? 'tmp' : 'pm' ).$LinkOwner->get_ID().'/';
		break;
}

$link_owner_positions = $LinkOwner->get_positions();

// Display a button to quick upload the files by drag&drop method
display_dragdrop_upload_button( array(
		'before'                 => '<div class="evo_fileuploader_form">',
		'after'                  => '</div>',
		'fileroot_ID'            => $upload_fileroot,
		'path'                   => $upload_path,
		'listElement'            => 'jQuery( "#'.$fieldset_prefix.'attachments_fieldset_table .filelist_tbody" ).get(0)',
		'list_element'           => '#'.$fieldset_prefix.'attachments_fieldset_table .filelist_tbody',
		'list_style'             => 'table',
		'template'               => '<div class="qq-uploader-selector qq-uploader" qq-drop-area-text="#button_text#">'
				.'<div class="qq-upload-drop-area-selector qq-upload-drop-area" qq-hide-dropzone>'	// Main dropzone
					// The div below is not necessary because were making the main dropzone transparent so
					// the upload button below will not be covered when the main dropzone is "displayed" on drop ((see qq-hide-dropzone doc)):
					//.'<div>#button_text#</div>'
				.'</div>'
				.'<div class="qq-upload-button-selector qq-upload-button">'
					.'<div>#button_text#</div>'
				.'</div>'
				.'<span class="qq-drop-processing-selector qq-drop-processing">'
					.'<span>'.TS_('Processing dropped files...').'</span>'
					.'<span class="qq-drop-processing-spinner-selector qq-drop-processing-spinner"></span>'
				.'</span>'
				.'<table>'
					.'<tbody class="qq-upload-list-selector qq-upload-list" aria-live="polite" aria-relevant="additions removals">'
						.'<tr>'
							.'<td class="firstcol shrinkwrap qq-upload-image"><span class="qq-upload-spinner-selector qq-upload-spinner">&#160;</span></td>'
							.'<td class="fm_filename">'
								.'<div class="qq-upload-file-selector"></div>'
								.'<div class="qq-progress-bar-container-selector progress" style="margin-bottom: 0;">'
									.'<div class="qq-progress-bar-selector progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="min-width: 2em;"></div>'
								.'</div>'
							.'</td>'
							.'<td class="qq-upload-link-id shrinkwrap link_id_cell">&#160;</td>'
							.'<td class="qq-upload-link-actions shrinkwrap">'
								.'<div class="qq-upload-status-text-selector qq-upload-status-text">'
									.'<span class="qq-upload-size-selector"></span>'
									.' <a class="qq-upload-cancel-selector qq-upload-cancel" href="#">'.TS_('Cancel').'</a>'
								.'</div>'
							.'</td>'
							.'<td class="qq-upload-link-position lastcol shrinkwrap"></td>'
						.'</tr>',
		'display_support_msg'    => false,
		'additional_dropzone'    => 'jQuery( "#'.$fieldset_prefix.'attachments_fieldset_table" ).closest( "form" ).add( jQuery( "#'.$fieldset_prefix.'attachments_fieldset_table" ).closest( "form" ).find( "textarea.link_attachment_dropzone" ) )',
		'filename_before'        => '',
		'LinkOwner'              => $LinkOwner,
		'display_status_success' => false,
		'status_conflict_place'  => 'before_button',
		'conflict_file_format'   => 'full_path_link',
		'resize_frame'           => true,
		'table_headers'          => $table_headers,
		'fm_mode'                => $fm_mode,
		'fieldset_prefix'        => $fieldset_prefix,
	) );

	if( ! isset( $attachment_tab ) )
	{
		// Initialize attachments fieldset to set proper height and handler to resize it:
		if( is_ajax_request() )
		{
			?>
			<script>
			jQuery( document ).ready( function() {
					evo_link_initialize_fieldset( '<?php echo $fieldset_prefix;?>' );
				} );
			</script>
			<?php
		}
		else
		{
			expose_var_to_js( 'link_initialize_fieldset_'.$fieldset_prefix, array( 'fieldset_prefix' => $fieldset_prefix ), 'evo_link_initialize_fieldset_config' );
		}
	}
?>
