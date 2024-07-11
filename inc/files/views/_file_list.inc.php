<?php
/**
 * This file implements the UI for file browsing.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Filelist
 */
global $fm_Filelist;
/**
 * fp> Temporary. I need this for NuSphere debugging.
 * @var File
 */
global $lFile;
/**
 * @var string
 */
global $fm_flatmode;
/**
 * @var UserSettings
 */
global $UserSettings;
/**
 * @var Log
 */
global $Messages;
/**
 * @var Filelist
 */
global $selected_Filelist;
/**
 * @var Link Owner
 */
global $LinkOwner;

global $edited_User;

global $Collection, $Blog, $blog;

global $fm_mode, $fm_hide_dirtree, $create_name, $ads_list_path, $mode;

// Abstract data we want to pass through:
global $linkctrl, $linkdata;

// Name of the iframe we want some actions to come back to:
global $iframe_name, $field_name, $file_type;

$Form = new Form( NULL, 'FilesForm', 'post', 'none' );
$Form->begin_form();
	$Form->hidden_ctrl();

	$Form->hidden( 'confirmed', '0' );
	$Form->hidden( 'md5_filelist', $fm_Filelist->md5_checksum() );
	$Form->hidden( 'md5_cwd', md5($fm_Filelist->get_ads_list_path()) );
	$Form->hiddens_by_key( get_memorized('fm_selected') ); // 'fm_selected' gets provided by the form itself

	if( get_param( 'fm_sources_root' ) == '' )
	{ // Set the root only when it is not defined, otherwise it is gone from memorized param
		$Form->hidden( 'fm_sources_root', $fm_Filelist->_FileRoot->ID );
	}
?>
<table class="filelist table table-striped table-bordered table-hover table-condensed">
	<?php
	ob_start();
	?>
	<thead>
	<?php
		/*****************  Col headers  ****************/

		echo '<tr>';

		// "Go to parent" icon
		echo '<th class="firstcol">';
		if( empty($fm_Filelist->_rds_list_path) )
		{ // cannot go higher
			echo '&#160;';	// for IE
		}
		else
		{
			echo action_icon( T_('Go to parent folder'), 'folder_parent', regenerate_url( 'path', 'path='.rawurlencode( preg_replace( '#[^\/]+\/?$#', '', $fm_Filelist->_rds_list_path ) ) ) );
		}
		echo '</th>';

		echo '<th class="nowrap">';
		if( $UserSettings->get( 'fm_imglistpreview' ) )
		{ // Image file preview:
			$col_title = T_('Icon/Type');
		}
		else
		{
			$col_title = /* TRANS: short for (file)Type */ T_('T ');		// Not to be confused with T for Tuesday
		}
		echo $fm_Filelist->get_sort_link( 'type', $col_title );
		echo '</th>';

		if( $fm_flatmode )
		{
			echo '<th>'.$fm_Filelist->get_sort_link( 'path', /* TRANS: file/directory path */ T_('Path') ).'</th>';
		}

		echo '<th class="nowrap">'.$fm_Filelist->get_sort_link( 'name', /* TRANS: file name */ T_('Name') ).'</th>';

		if( $UserSettings->get('fm_showtypes') )
		{ // Show file types column
			echo '<th class="nowrap">'.$fm_Filelist->get_sort_link( 'type', /* TRANS: file type */ T_('Type') ).'</th>';
		}

		if( $UserSettings->get('fm_showcreator') )
		{ // Show file creator
			echo '<th class="nowrap">'.$fm_Filelist->get_sort_link( 'creator_user_ID', /* TRANS: added by */ T_('Added by') ).'</th>';
		}

		if( $UserSettings->get('fm_showdownload') )
		{  // Show download count column
			echo '<th class="nowrap">'.$fm_Filelist->get_sort_link( 'download_count', /* TRANS: download count */ T_('Downloads') ).'</th>';
		}

		echo '<th class="nowrap">'.$fm_Filelist->get_sort_link( 'size', /* TRANS: file size */ T_('Size') ).'</th>';

		if( $UserSettings->get('fm_showdate') != 'no' )
		{ // Show last mod column
			echo '<th class="nowrap">'.$fm_Filelist->get_sort_link( 'lastmod', /* TRANS: file's last change / timestamp */ T_('Last change') ).'</th>';
		}

		if( $UserSettings->get('fm_showfsperms') )
		{ // Show file perms column
			echo '<th class="nowrap">'.$fm_Filelist->get_sort_link( 'perms', /* TRANS: file's permissions (short) */ T_('Perms') ).'</th>';
		}

		if( $UserSettings->get('fm_showfsowner') )
		{ // Show file owner column
			echo '<th class="nowrap">'.$fm_Filelist->get_sort_link( 'fsowner', /* TRANS: file owner */ T_('Owner') ).'</th>';
		}

		if( $UserSettings->get('fm_showfsgroup') )
		{ // Show file group column
			echo '<th class="nowrap">'.$fm_Filelist->get_sort_link( 'fsgroup', /* TRANS: file group */ T_('Group') ).'</th>';
		}

		echo '<th class="lastcol nowrap">'. /* TRANS: file actions; edit, rename, copy, .. */ T_('Actions').'</th>';
		echo '</tr>';
	?>
	</thead>
	<?php
	$table_headers = ob_get_clean();
	if( $fm_Filelist->count() > 0 )
	{	// Display table headers only when at least file is found in the selected folder and filter:
		echo $table_headers;
	}
	?>
	<tbody class="filelist_tbody">
	<?php
	$checkall = param( 'checkall', 'integer', 0 );  // Non-Javascript-CheckAll
	$fm_highlight = param( 'fm_highlight', 'string', NULL );

	// Set FileList perms
	$all_perm = check_user_perm( 'files', 'all', false );
	$edit_allowed_perm = check_user_perm( 'files', 'edit_allowed', false, $fm_Filelist->get_FileRoot() );

	/***********************************************************/
	/*                    MAIN FILE LIST:                      */
	/***********************************************************/
	$countFiles = 0;
	while( $lFile = & $fm_Filelist->get_next() )
	{ // Loop through all Files:
		echo '<tr class="'.($countFiles%2 ? 'odd' : 'even').'"';

		if( isset($fm_highlight) && $lFile->get_name() == $fm_highlight )
		{ // We want a specific file to be highlighted (user clicked on "locate"/target icon
			echo ' id="fm_highlighted"'; // could be a class, too..
		}
		echo '>';


		/********************    Checkbox:    *******************/

		echo '<td class="checkbox firstcol">';
		echo '<input title="'.T_('Select this file').'" type="checkbox" class="checkbox"
					name="fm_selected[]" value="'.format_to_output( $lFile->get_rdfp_rel_path(), 'formvalue' ).'" id="cb_filename_'.$countFiles.'"';
		if( $checkall || $selected_Filelist->contains( $lFile ) )
		{
			echo ' checked="checked"';
		}
		echo ' />';

		/***********  Hidden info used by Javascript:  ***********/

		if( $mode == 'upload' )
		{	// This mode allows to insert img tags into the post...
			// Hidden info used by Javascript:
			echo '<input type="hidden" name="img_tag_'.$countFiles.'" id="img_tag_'.$countFiles
			    .'" value="'.format_to_output( $lFile->get_tag(), 'formvalue' ).'" />';
		}

		echo '</td>';
		evo_flush();


		/********************  Icon / File type:  *******************/

		echo '<td class="icon_type text-nowrap">';
		if( $UserSettings->get( 'fm_imglistpreview' ) )
		{	// Image preview OR full type:
			if( $lFile->is_dir() )
			{ // Navigate into Directory
				echo '<a href="'.$lFile->get_view_url().'" title="'.T_('Change into this directory').'">'.$lFile->get_icon().' '.T_('Directory').'</a>';
			}
			else
			{
				echo $lFile->get_preview_thumb( 'fulltype', array( 'init' => true ) );
			}
		}
		else
		{	// No image preview, small type:
			if( $lFile->is_dir() )
			{ // Navigate into Directory
				echo '<a href="'.$lFile->get_view_url().'" title="'.T_('Change into this directory').'">'.$lFile->get_icon().'</a>';
			}
			else
			{ // File
				echo $lFile->get_view_link( $lFile->get_icon(), NULL, $lFile->get_icon() );
			}
		}
		echo '</td>';
		evo_flush();

		/*******************  Path (flatmode): ******************/

		if( $fm_flatmode )
		{
			echo '<td class="filepath">';
			echo dirname( $lFile->get_rdfs_rel_path() ).'/';
			echo '</td>';
			evo_flush();
		}

		/*******************  File name: ******************/
		if( ! $fm_flatmode ||
		    ( $selected_Filelist->get_rds_list_path() === false && dirname( $lFile->get_rdfs_rel_path() ) == '.' ) ||
		    ( $selected_Filelist->get_rds_list_path() == dirname( $lFile->get_rdfs_rel_path() ).'/' ) )
		{ // Use a hidden field only for current folder and not for subfolders
		  // It is used to detect a duplicate file on quick upload
			$filename_hidden_field = '<input type="hidden" value="'.$lFile->get_root_and_rel_path().'" />';
		}
		else
		{ // Don't use the hidden field for this file because it is from another folder
			$filename_hidden_field = '';
		}
		echo '<td class="fm_filename">'
			.$filename_hidden_field;

			/*************  Invalid filename warning:  *************/

			if( !$lFile->is_dir() )
			{
				if( $error_filename = validate_filename( $lFile->get_name() ) )
				{ // TODO: Warning icon with hint
					echo get_icon( 'warning', 'imgtag', array( 'class' => 'filenameIcon', 'title' => strip_tags( $error_filename ), 'data-toggle' => 'tooltip' ) ).'&nbsp;';
					syslog_insert( sprintf( 'The unrecognized extension is detected for file %s', '[['.$lFile->get_name().']]' ), 'warning', 'file', $lFile->ID );
				}
			}
			elseif( $error_dirname = validate_dirname( $lFile->get_name() ) )
			{ // TODO: Warning icon with hint
				echo get_icon( 'warning', 'imgtag', array( 'class' => 'filenameIcon', 'title' => strip_tags( $error_dirname ), 'data-toggle' => 'tooltip' ) ).'&nbsp;';
				syslog_insert( sprintf( 'Invalid name is detected for folder %s', '[['.$lFile->get_name().']]' ), 'warning', 'file', $lFile->ID );
			}

			/****  Open in a new window  (only directories)  ****/

			if( $lFile->is_dir() )
			{ // Directory
				$browse_dir_url = $lFile->get_view_url();
				$popup_url = url_add_param( $browse_dir_url, 'mode=popup' );
				$target = 'evo_fm_'.$lFile->get_md5_ID();

				echo '<a href="'.$browse_dir_url.'" target="'.$target.' " class="pull-right"
							title="'.T_('Open in a new window').'" onclick="'
							."return pop_up_window( '$popup_url', '$target' )"
							.'">'.get_icon( 'window_new' ).'</a>';
			}

			/***************  Link ("chain") icon:  **************/

			// if( ! $lFile->is_dir() )	// fp> OK but you need to include an else:placeholder, otherwise the display is ugly
			{	// Only provide link/"chain" icons for files.
				// TODO: dh> provide support for direcories (display included files).

				// fp> here might not be the best place to put the perm check
				if( isset( $LinkOwner ) && $LinkOwner->check_perm( 'edit' ) )
				{	// Offer option to link the file to an Item (or anything else):
					$link_attribs = array( 'class' => 'action_icon link_file btn btn-primary btn-xs' );
					$link_action = 'link';
					if( $mode == 'upload' )
					{	// We want the action to happen in the post attachments iframe:
						$link_attribs['target'] = $iframe_name;
						$link_attribs['onclick'] = 'return evo_link_attach( \''.$LinkOwner->type.'\', '.$LinkOwner->get_ID()
								.', \''.FileRoot::gen_ID( $fm_Filelist->get_root_type(), $fm_Filelist->get_root_ID() )
								.'\', \''.$lFile->get_rdfp_rel_path().'\', \''.param( 'prefix', 'string' ).'\' )';
						$link_action = 'link_inpost';
					}
					echo action_icon( T_('Link this file!'), 'link',
								regenerate_url( 'fm_selected', 'action='.$link_action.'&amp;fm_selected[]='.rawurlencode($lFile->get_rdfp_rel_path()).'&amp;'.url_crumb('file') ),
								' '.T_('Attach'), NULL, 5, $link_attribs );
					echo ' ';
				}

				if( isset($edited_User) ) // fp> Perm already checked in controller
				{	// Offer option to link the file to an Item (or anything else):
					if( $lFile->is_image() )
					{
						echo action_icon( T_('Use this as my profile picture!'), 'link',
									regenerate_url( 'fm_selected', 'action=link_user&amp;fm_selected[]='.rawurlencode($lFile->get_rdfp_rel_path()).'&amp;'.url_crumb('file') ),
									NULL, NULL, NULL, array() );
						echo action_icon( T_('Duplicate and use as profile picture'), 'user',
									regenerate_url( 'fm_selected', 'action=duplicate_user&amp;fm_selected[]='.rawurlencode($lFile->get_rdfp_rel_path()).'&amp;'.url_crumb('file') ),
									NULL, NULL, NULL, array() );
						echo ' ';
					}
				}
				elseif( !$lFile->is_dir() && ! empty( $linkctrl ) && ! empty( $linkdata ) )
				{
					echo action_icon( T_('Link this file!'), 'link',
								regenerate_url( 'fm_selected', 'action=link_data&amp;fm_selected[]='.rawurlencode($lFile->get_rdfp_rel_path()).'&amp;'.url_crumb('file') ),
								NULL, NULL, NULL, array() );

					echo ' ';
				}

				if( $fm_mode == 'file_select' && !empty( $field_name ) && !$lFile->is_dir() && $lFile->get( 'type' ) == $file_type )
				{
					$sfile_root = FileRoot::gen_ID( $fm_Filelist->get_root_type(), $fm_Filelist->get_root_ID() );
					$sfile_path = $lFile->get_rdfp_rel_path();
					$link_attribs = array();
					$link_action = 'set_field';

					$link_attribs['class'] = 'evo_select_file btn btn-primary btn-xs';
					$link_attribs['onclick'] = 'return '.( get_param( 'iframe_name' ) == '' ? 'window.parent' : 'parent.frames[\''.format_to_js( get_param( 'iframe_name' ) ).'\']' ).'.file_select_add( \''.$field_name.'\', \''.$sfile_root.'\', \''.$sfile_path.'\' );';
					$link_attribs['type'] = 'button';
					$link_attribs['title'] = T_('Select file');
					echo '<button'.get_field_attribs_as_string( $link_attribs, false ).'>'.get_icon( 'link' ).' './* TRANS: verb */ T_('Select').'</button> ';
				}
			}

			/******************** File name + meta data ********************/
			echo file_td_name( $lFile );

		echo '</td>';
		evo_flush();

		/*******************  File type  ******************/

		if( $UserSettings->get('fm_showtypes') )
		{ // Show file types
			echo '<td class="type">'.$lFile->get_type().'</td>';
			evo_flush();
		}

		/*******************  Added by  *******************/

		if( $UserSettings->get('fm_showcreator') )
		{
			if( $creator = $lFile->get_creator() )
			{
				echo '<td class="center">'.$creator->get( 'login' ).'</td>';
			}
			else
			{
				echo '<td class="center">unknown</td>';
			}
			evo_flush();
		}

		/****************  Download Count  ****************/

		if( $UserSettings->get('fm_showdownload') )
		{ // Show download count
			// erhsatingin> Can't seem to find proper .less file to add the 'download' class, using class 'center' instead
			echo '<td class="center">'.$lFile->get_download_count().'</td>';
			evo_flush();
		}

		/*******************  File size  ******************/

		echo '<td class="size">'.$fm_Filelist->get_File_size_formatted($lFile).'</td>';

		/****************  File time stamp  ***************/

		if( $UserSettings->get('fm_showdate') != 'no' )
		{ // Show last modified datetime (always full in title attribute)
			$lastmod_date = $lFile->get_lastmod_formatted( 'date' );
			$lastmod_time = $lFile->get_lastmod_formatted( 'time' );
			echo '<td class="timestamp" title="'.format_to_output( $lastmod_date.' '.$lastmod_time, 'htmlattr' ).'">';
			echo file_td_lastmod( $lFile );
			echo '</td>';
			evo_flush();
		}

		/****************  File pemissions  ***************/

		if( $UserSettings->get('fm_showfsperms') )
		{ // Show file perms
			echo '<td class="perms">';
			$fm_permlikelsl = $UserSettings->param_Request( 'fm_permlikelsl', 'fm_permlikelsl', 'integer', 0 );

			if( $edit_allowed_perm )
			{ // User can edit:
				echo '<a title="'.T_('Edit permissions').'" href="'.regenerate_url( 'fm_selected,action', 'action=edit_perms&amp;fm_selected[]='
							.rawurlencode($lFile->get_rdfp_rel_path()) ).'&amp;'.url_crumb( 'file' ).'">'
							.$lFile->get_perms( $fm_permlikelsl ? 'lsl' : '' ).'</a>';
			}
			else
			{
				echo $lFile->get_perms( $fm_permlikelsl ? 'lsl' : '' );
			}
			echo '</td>';
			evo_flush();
		}

		/****************  File owner  ********************/

		if( $UserSettings->get('fm_showfsowner') )
		{ // Show file owner
			echo '<td class="fsowner">';
			echo $lFile->get_fsowner_name();
			echo '</td>';
			evo_flush();
		}

		/****************  File group *********************/

		if( $UserSettings->get('fm_showfsgroup') )
		{ // Show file owner
			echo '<td class="fsgroup">';
			echo $lFile->get_fsgroup_name();
			echo '</td>';
			evo_flush();
		}

		/*****************  Action icons  ****************/

		echo '<td class="actions lastcol text-nowrap">';
		echo file_td_actions( $lFile );
		echo '</td>';
		evo_flush();

		echo '</tr>';
		evo_flush();

		$countFiles++;
	}
	// / End of file list..


	/**
	 * @global integer Number of cols for the files table, 6 is minimum.
	 */
	$filetable_cols = 5
		+ (int)$fm_flatmode
		+ (int)$UserSettings->get('fm_showcreator')
		+ (int)$UserSettings->get('fm_showtypes')
		+ (int)($UserSettings->get('fm_showdate') != 'no')
		+ (int)$UserSettings->get('fm_showfsperms')
		+ (int)$UserSettings->get('fm_showfsowner')
		+ (int)$UserSettings->get('fm_showfsgroup')
		+ (int)$UserSettings->get('fm_showdownloads')
		+ (int)$UserSettings->get('fm_imglistpreview');

	$noresults = '';
	if( $countFiles == 0 )
	{	// Filelist errors or "directory is empty":
		$noresults = '<tr class="noresults">
			<td class="lastcol text-danger" colspan="'.$filetable_cols.'" id="fileman_error">'
				.T_('No files found.')
				.( $fm_Filelist->is_filtering() ? '<br />'.T_('Filter').': &laquo;'.$fm_Filelist->get_filter().'&raquo;' : '' )
			.'</td>
		</tr>';
		// Note: this var is also used for display_dragdrop_upload_button() below:
		echo $noresults;
	}

	echo '</tbody>';

	echo '<tfoot>';

	// -------------
	// Quick upload with drag&drop button:
	// --------------
	if( $Settings->get( 'upload_enabled' ) && check_user_perm( 'files', 'add', false, $fm_FileRoot ) )
	{	// Upload is enabled and we have permission to use it...
	?>
		<tr class="evo_fileuploader_form listfooter firstcol lastcol">
			<td colspan="<?php echo $filetable_cols ?>">
			<?php
			if( isset( $LinkOwner ) && $LinkOwner->check_perm( 'edit' ) )
			{	// Offer option to link the file to an Item (or anything else):
				$link_attribs = array();
				$link_action = 'link';
				if( $mode == 'upload' )
				{	// We want the action to happen in the post attachments iframe:
					$link_attribs['target'] = $iframe_name;
					$link_attribs['onclick'] = 'return evo_link_attach( \''.$LinkOwner->type.'\', '.$LinkOwner->get_ID()
							.', \''.FileRoot::gen_ID( $fm_Filelist->get_root_type(), $fm_Filelist->get_root_ID() )
							.'\', \''.'$file_path$'.'\' )';
					$link_attribs['class'] = 'action_icon link_file btn btn-primary btn-xs';
					$link_action = 'link_inpost';
				}
				$icon_to_link_files = action_icon( T_('Link this file!'), 'link',
							regenerate_url( 'fm_selected', 'action='.$link_action.'&amp;fm_selected[]=$file_path$&amp;'.url_crumb('file') ),
							' '.T_('Attach'), NULL, 5, $link_attribs ).' ';
			}
			else
			{ // No icon to link files
				$icon_to_link_files = '';
			}

			if( $fm_mode == 'file_select' && !empty( $field_name ) )
			{
				$sfile_root = FileRoot::gen_ID( $fm_Filelist->get_root_type(), $fm_Filelist->get_root_ID() );
				$link_attribs = array();
				$link_attribs['class'] = 'evo_select_file btn btn-primary btn-xs';
				$link_attribs['onclick'] = 'return window.parent.file_select_add( \''.$field_name.'\', \''.$sfile_root.'\', \'$file_path$\' );';
				$link_attribs['type'] = 'button';
				$link_attribs['title'] = T_('Select file');
				$icon_to_select_files = '<button'.get_field_attribs_as_string( $link_attribs, false ).'>'.get_icon( 'link' ).' '.T_('Select').'</button> ';
			}
			else
			{	// No icon to select file
				$icon_to_select_files = '';
			}

			$template = '<div class="qq-uploader-selector qq-uploader" qq-drop-area-text="#button_text#">'
				.'<div class="qq-upload-drop-area-selector qq-upload-drop-area" qq-hide-dropzone>'	// Main dropzone
				//The div below is not necessary because were making the main dropzone transparent so
				// the upload button below will not be covered when the main dropzone is "displayed" on drop ((see qq-hide-dropzone doc)):
				//.'<div>#button_text#</div>' //
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
				.'<tr>';

			$template .= '<td class="checkbox firstcol qq-upload-checkbox">&nbsp;</td>';
			$template .= '<td class="icon_type qq-upload-image shrinkwrap"><span class="qq-upload-spinner-selector qq-upload-spinner">&nbsp;</span></td>';

			if( $fm_flatmode )
			{
				$template .= '<td class="filepath">'.( empty( $path ) ? './' : $path ).'</td>';
			}
			$template .= '<td class="fm_filename">';
			$template .= '<div class="qq-upload-file-selector"></div>';
			$template .= '<div class="qq-progress-bar-container-selector progress" style="margin-bottom: 0;">';
			$template .= '<div class="qq-progressbar-selector progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="min-width: 2em;"></div>';
			$template .= '</div>';
			$template .= '</td>';
			if( $UserSettings->get('fm_showtypes') )
			{
				$template .= '<td class="type qq-upload-file-type">&#160;</td>';
			}
			if( $UserSettings->get( 'fm_showcreator' ) )
			{
				$template .= '<td class="center qq-upload-file-creator">&#160;</td>';
			}
			if( $UserSettings->get( 'fm_showdownload' ) )
			{
				$template .= '<td class="center qq-upload-downloads">#160;</td>';
			}
			$template .= '<td class="size"><span class="qq-upload-size-selector">&nbsp;</span>';
			$template .= '</td>';
			if( $UserSettings->get('fm_showdate') != 'no' )
			{
				$template .= '<td class="fsdate timestamp"><span class="qq-upload-status-text-selector qq-upload-status-text"></span></td>';
			}
			if( $UserSettings->get('fm_showfsperms') )
			{
				$template .= '<td class="perms">&#160;</td>';
			}
			if( $UserSettings->get('fm_showfsowner') )
			{
				$template .= '<td class="fsowner">&#160;</td>';
			}
			if( $UserSettings->get('fm_showfsgroup') )
			{
				$template .= '<td class="fsgroup">#160;</td>';
			}
			$template .= '<td class="actions lastcol shrinkwrap">';
			if( $UserSettings->get('fm_showdate') == 'no' )
			{ // Display status in the last column if column with datetime is hidden
				$template .= '<span class="qq-upload-status-text-selector qq-upload-status-text"></span> ';
			}
			$template .= '<a class="qq-upload-cancel-selector qq-upload-cancel" href="#">'.TS_('Cancel').'</a>'.'</td>';

			$template .= '</tr></tbody></table>	</div>';

			// Display a button to quick upload the files by drag&drop method
			display_dragdrop_upload_button( array(
					'fileroot_ID'            => $fm_FileRoot->ID,
					'path'                   => empty( $path ) ? './' : $path,
					'listElement'            => 'jQuery( ".filelist_tbody" ).get(0)',
					'list_element'           => '.filelist_tbody',
					'list_style'             => 'table',
					'template'               => $template,
					'display_support_msg'    => false,
					'display_status_success' => false,
					'additional_dropzone'    => '[ jQuery( ".filelist_tbody" ).get(0) ]',
					'filename_before'        => $icon_to_link_files,
					'table_headers'          => $table_headers,
					'noresults'              => $noresults,
					'table_id'               => 'FilesForm',
				) );
			?>
			</td>
		</tr>
	<?php
	}

		// -------------
		// Footer with "check all", "with selected: ..":
		// --------------
		?>
		<tr class="listfooter firstcol lastcol file_selector">
			<td colspan="<?php echo $filetable_cols ?>">

		<?php
		echo '<div id="evo_multi_file_selector" class="pull-left"'.( $countFiles == 0 ? ' style="display:none"' : '' ).'>';
			$Form->checkbox_controls( 'fm_selected', array( 'button_class' => 'btn btn-default' ) );
			$Form->add_crumb( 'file' );

			$field_options = array();

			/*
			 * TODO: fp> the following is a good idea but in case we also have $mode == 'upload' it currently doesn't refresh the list of attachements
			 * which makes it seem like this feature is broken. Please add necessary javascript for this.
			 */
			if( $fm_mode == 'link_object' && $mode != 'upload' )
			{	// We are linking to an object...
				$field_options['link'] = $LinkOwner->translate( 'Link files to current xxx' );
			}

			if( ( $fm_Filelist->get_root_type() == 'collection' || ( ! empty( $Blog )
						&& check_user_perm( 'blog_post_statuses', 'edit', false, $Blog->ID ) ) )
				&& $mode != 'upload'
				&& check_user_perm( 'admin', 'normal' ) )
			{ // We are browsing files for a collection:
				// User must have access to admin permission
				// fp> TODO: use current as default but let user choose into which blog he wants to post
				$field_options['make_post'] = T_('Make one post (including all images)');
				$field_options['make_posts_pre'] = T_('Make multiple posts (1 per image)');
			}

			if( $edit_allowed_perm )
			{ // User can edit:
				$field_options['move_copy'] = T_('Copy/Move to another directory...');
			}

			if( $mode == 'upload' &&
			    isset( $LinkOwner ) &&
			    ( $LinkOwner->type == 'item' ||
			      ( $LinkOwner->is_temp() && $LinkOwner->link_Object->tmp_type == 'item' )
			    ) )
			{	// We are uploading in a popup opened by an edit/new item form:
				$field_options['img_tag'] = T_('Insert IMG/link into post');
			}

			if( $edit_allowed_perm )
			{ // User can edit:
				$field_options['rename'] = T_('Rename files...');
				$field_options['resize'] = T_('Resize images...');
				$field_options['delete'] = T_('Delete files...');
				$field_options['create_zip'] = T_('Create ZIP archive').'...';
				$field_options['unpack_zip'] = T_('Unpack ZIP archives').'...';
				// NOTE: No delete confirmation by javascript, we need to check DB integrity!
			}

			// BROKEN ?
			$field_options['download'] = T_('Download files as ZIP archive...');

			/* Not fully functional:
			$field_options['file_copy'] = T_('Copy the selected files...');
			$field_options['file_move'] = T_('Move the selected files...');

			// This is too geeky! Default perms radio options and unchecked radio groups! NO WAY!
			// If you want this feature to be usable by average users you must only have one line per file OR one file for all. You can't mix both.
			// The only way to have both is to have 2 spearate forms: 1 titled "change perms for all files simultaneously"-> submit  and another 1 title "change perms for each file individually" -> another submit
			// fplanque>> second thought: changing perms for multiple files at once is useful. BUT assigning different perms to several files with ONE form is trying to solve a problem that not even geeks can face once in a lifetime.
			// This has to be simplified to ONE single set of permissions for all selected files. (If you need different perms, click again)
			$field_options['file_perms'] = T_('Change permissions for the selected files...');
			*/

			$Form->switch_layout( 'none' );
			$Form->select_input_array( 'group_action', $action, $field_options, ' &mdash; <strong>'.T_('With selected files').'</strong>' );
			$Form->submit_input( array( 'name'=>'actionArray[group_action]', 'value'=>T_('Go!'), 'onclick'=>'return js_act_on_selected();' ) );
			$Form->switch_layout( NULL );

		echo '</div>';

			/* fp> the following has been integrated into the select.
			if( $mode == 'upload' )
			{	// We are uploading in a popup opened by an edit screen
				?>
				&mdash;
				<input class="ActionButton"
					title="<?php echo T_('Insert IMG or link tags for the selected files, directly into the post text'); ?>"
					name="actionArray[img_tag]"
					value="<?php echo T_('Insert IMG/link into post') ?>"
					type="submit"
					onclick="insert_tag_for_selected_files(); return false;" />
				<?php
			}
			*/


			/*
			 * CREATE FILE/FOLDER CREATE PANEL:
			 */
			if( ( $Settings->get( 'fm_enable_create_dir' ) || $Settings->get( 'fm_enable_create_file' ) )
						&& check_user_perm( 'files', 'add', false, $fm_FileRoot ) )
			{	// dir or file creation is enabled and we're allowed to add files:
				global $create_type;

				echo '<div class="evo_file_folder_creator">';
					if( ! $Settings->get( 'fm_enable_create_dir' ) )
					{	// We can create files only:
						echo '<label for="fm_createname" class="tooltitle">'.T_('New file:').'</label>';
						$Form->hidden( 'create_type', 'file' );
					}
					elseif( ! $Settings->get( 'fm_enable_create_file' ) )
					{	// We can create directories only:
						echo '<label for="fm_createname" class="tooltitle">'.T_('New folder:').'</label>';
						$Form->hidden( 'create_type', 'dir' );
					}
					else
					{	// We can create both files and directories:
						echo T_('New').': ';
						echo '<select name="create_type" class="form-control">';
						echo '<option value="dir"'.( isset($create_type) &&  $create_type == 'dir' ? ' selected="selected"' : '' ).'>'.T_('folder').'</option>';
						echo '<option value="file"'.( isset($create_type) && $create_type == 'file' ? ' selected="selected"' : '' ).'>'.T_('file').'</option>';
						echo '</select>:';
					}
				?>
				<input type="text" name="create_name" id="fm_createname" value="<?php echo isset( $create_name ) ? $create_name : ''; ?>" size="15" class="form-control" />
				<input class="ActionButton btn btn-default" type="submit" name="actionArray[createnew]" value="<?php echo format_to_output( T_('Create').'!', 'formvalue' ) ?>" />
				<?php
				echo '</div>';
			}
			?>
			</td>
		</tr>
	</tfoot>
</table>
<?php
	$Form->end_form();

	if( $countFiles )
	{{{ // include JS
		// TODO: remove these javascript functions to an external .js file and include them through add_headline()
		?>
		<script>
			<!--
			function js_act_on_selected()
			{
				// There may be an easier selector than below but couldn't make sense of it :(
				selected_value = jQuery('#group_action option:selected').attr('value');
				if( selected_value == 'img_tag' )
				{
					if( insert_tag_for_selected_files() )
					{ // If images have been inserted successfully
						if( typeof( closeModalWindow ) == 'function' )
						{ // Close modal window after images inserting:
							closeModalWindow( window.parent.document );
						}
					}
					return false;
				}

				// other actions:

				if ( selected_value == 'make_posts_pre' )
				{
					jQuery('#FilesForm').append('<input type="hidden" name="ctrl" value="items" />');
				}
				return true;
			}

			/**
			 * Check if files are selected.
			 *
			 * This should be used as "onclick" handler for "With selected" actions (onclick="return check_if_selected_files();").
			 * @return boolean true, if something is selected, false if not.
			 */
			function check_if_selected_files()
			{
				elems = document.getElementsByName( 'fm_selected[]' );
				var checked = 0;
				for( i = 0; i < elems.length; i++ )
				{
					if( elems[i].checked )
					{
						checked++;
					}
				}
				if( !checked )
				{
					alert( '<?php echo TS_('Nothing selected.') ?>' );
					return false;
				}
				else
				{
					return true;
				}
			}

			/**
			 * Insert IMG tags into parent window for selected files.
			 */
			function insert_tag_for_selected_files()
			{
				var elems = document.getElementsByName( 'fm_selected[]' );
				var snippet = '';
				for( i = 0; i < elems.length; i++ )
				{
					if( elems[i].checked )
					{
						id = elems[i].id.substring( elems[i].id.lastIndexOf('_')+1, elems[i].id.length );
						img_tag_info_field = document.getElementById( 'img_tag_'+id );
						snippet += img_tag_info_field.value + '\n';
					}
				}
				if( ! snippet.length )
				{
					alert( '<?php echo TS_('You must select at least one file!') ?>' );
					return false;
				}
				else
				{
					// Remove last newline from snippet:
					snippet = snippet.substring(0, snippet.length-1);
					if (! (window.focus && window.parent))
					{
						return true;
					}
					window.parent.focus();
					textarea_wrap_selection( window.parent.document.getElementById("itemform_post_content"), snippet, '', 1, window.parent.document );
					return true;
				}
			}

			// Display a message to inform user after file was linked to object
			jQuery( document ).ready( function()
			{
				jQuery( document ).on( 'click', 'a.link_file', function()
				{
					jQuery( this ).parent().append( '<div class="green"><?php echo TS_('The file has been linked.'); ?></div>' );
				} );
			} );

			// Display a message to inform user after the file was selected
			jQuery( document ).ready( function()
			{
				jQuery( document ).on( 'click', '.evo_select_file', function()
				{
					jQuery( '.selected_msg' ).remove();
					jQuery( this ).parent().append( '<div class="green selected_msg"><?php echo TS_('The file has been selected.'); ?></div>' );
				} );
			} );
			// -->
		</script>
		<?php

		if( $fm_highlight )
		{ // we want to highlight a file (e.g. via "Locate this file!"), scroll there and do the success fade
			?>

			<script>
			jQuery( function() {
				var fm_hl = jQuery("#fm_highlighted");
				if( fm_hl.length ) {
					jQuery.getScript('<?php echo get_require_url( '#scrollto#' ); ?>', function () {
						jQuery.scrollTo( fm_hl,
						{ onAfter: function()
							{
								evoFadeHighlight( fm_hl )
							}
						} );
					});
				}
			} );
			</script>

			<?php
		}

	}}}
?>
<!-- End of detailed file list -->
