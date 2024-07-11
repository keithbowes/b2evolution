<?php
/**
 * This file implements the Markdown Import class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}.
*
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


load_class( 'tools/model/_abstractimport.class.php', 'AbstractImport' );

/**
 * Markdown Import Class
 *
 * @package evocore
 */
class MarkdownImport extends AbstractImport
{
	var $import_code = 'markdown';
	var $coll_ID;

	var $source;
	var $data;
	var $options;
	var $options_defs;
	var $yaml_fields;

	/**
	 * Initialize data for markdown import
	 */
	function __construct()
	{
		global $Plugins, $Settings;

		// Options definitions:
		$this->options_defs = array(
			// Import mode:
			'import_type' => array(
				'group'   => 'mode',
				'title'   => TB_('Import mode'),
				'options' => array(
					'update' => array( 'title' => TB_('Update existing contents'), 'note' => TB_('Existing Categories & Posts will be re-used (based on slug).') ),
					'append' => array( 'title' => TB_('Append to existing contents') ),
					'delete' => array(
							'title'  => TB_('DELETE & replace ALL contents'),
							'note'   => TB_('WARNING: this option will permanently remove existing posts, comments, categories and tags from the selected collection.'),
							'suffix' => '<br /><div id="import_type_delete_confirm_block" class="alert alert-danger" style="display:none;margin:0">'.TB_('WARNING').': '.TB_('you will LOSE any data that is not part of the files you import.').' '.sprintf( TB_('Type %s to confirm'), '<code>DELETE</code>' ).': <input name="import_type_delete_confirm" type="text" class="form-control" size="8" style="margin:-8px 0" /></div>',
						),
				),
				'type'    => 'string',
				'default' => 'update',
			),
			'reuse_cats' => array(
				'group'    => 'import_type',
				'subgroup' => 'append',
				'title'    => TB_('Reuse existing categories'),
				'note'     => '('.TB_('based on folder name = slug name').')',
				'type'     => 'integer',
				'default'  => 1,
			),
			'delete_files' => array(
				'group'    => 'import_type',
				'subgroup' => 'delete',
				'title'    => TB_('Also delete media files that will no longer be referenced in the destination collection after replacing its contents'),
				'type'     => 'integer',
				'default'  => 0,
			),
			// Checkbox options:
			'allow_extra_cats' => array(
				'group'   => 'options',
				'title'   => sprintf( TB_('Allow %s to cross post into other collections'), '<code>extra-cats:</code>' ),
				'note'    => $Settings->get( 'cross_posting' ) ? '' : TB_('Cross posting is globally disabled'),
				'type'    => 'integer',
				'default' => 1,
				'disabled'=> ! $Settings->get( 'cross_posting' ),
			),
			'convert_md_links' => array(
				'group'   => 'options',
				'title'   => TB_('Convert Markdown links to b2evolution ShortLinks'),
				'type'    => 'integer',
				'default' => 1,
			),
			'check_links' => array(
				'group'   => 'options',
				'title'   => TB_('Check all internal links (slugs) to see if they link to a page of the same language (if not, log a Warning)'),
				'type'    => 'integer',
				'default' => 1,
				'indent'  => 1,
			),
			'diff_lang_suggest' => array(
				'group'   => 'options',
				'title'   => TB_('If different language, use the "linked languages/versions" table to find the equivalent in the same language (and log the suggestion)'),
				'type'    => 'integer',
				'default' => 1,
				'indent'  => 2,
			),
			'same_lang_replace_link' => array(
				'group'   => 'options',
				'title'   => TB_('If a same language match was found, replace the link slug in the post while importing'),
				'type'    => 'integer',
				'default' => 1,
				'indent'  => 3,
			),
			'same_lang_update_file' => array(
				'group'   => 'options',
				'title'   => TB_('If a same language match was found, replace the link slug in the original <code>.md</code> file on disk so it doesn\'t trigger warnings next time (and can be versioned into Git).'),
				'note'    => TB_('This requires using a directory to import, not a ZIP file.'),
				'type'    => 'integer',
				'default' => 1,
				'indent'  => 3,
			),
			'force_item_update' => array(
				'group'   => 'options',
				'title'   => TB_('Force Item update, even if file hash has not changed'),
				'type'    => 'integer',
				'default' => 0,
			),
			// Radio options:
			'slug_diff_coll' => array(
				'group'   => 'radio',
				'title'   => TB_('If filename/slug exists in a different collection'),
				'options' => array(
					'skip'   => array( 'label' => sprintf( TB_('Display error and skip the %s file'), '<code>.md</code>' ) ),
					'create' => array( 'label' => TB_('Create a new slug like xyz-1') ),
				),
				'type'    => 'string',
				'default' => 'skip',
			),
			'extra_cat_only_in_db' => array(
				'group'   => 'radio',
				'title'   => TB_('If extra-categories are found in DB but not in YAML'),
				'options' => array(
					'keep_db'  => array( 'label' => TB_('Display WARNING and do not resolve') ),
					'add_yaml' => array( 'label' => sprintf( TB_('Resolve by adding extra-categories to YAML in %s file on disk'), '<code>.md</code>' ) ),
					'unassign' => array( 'label' => TB_('Resolve by deleting from DB') ),
				),
				'type'    => 'string',
				'default' => 'keep_db',
			),
		);

		// Supported YAML fields:
		$this->yaml_fields = array(
				'title',
				'description',
				'keywords',
				'excerpt',
				'short-title',
				'tags',
				'extra-cats',
				'order',
				'item-type',
			);

		// Call plugin event for additional initialization:
		$Plugins->trigger_event( 'ImporterConstruct', array(
				'type'     => $this->import_code,
				'Importer' => $this,
			) );
	}


	/**
	 * Check source folder or zip archive
	 *
	 * @return boolean|string TRUE on success, Error message of error
	 */
	function check_source()
	{
		if( empty( $this->source ) )
		{	// File is not selected:
			return TB_('Please select file or folder to import.');
		}
		elseif( is_dir( $this->source ) )
		{
			if( ! check_folder_with_extensions( $this->source, 'md' ) )
			{	// Folder has no markdown files:
				return sprintf( TB_('Folder %s has no markdown files.'), '<code>'.$this->source.'</code>' );
			}
		}
		elseif( ! preg_match( '/\.zip$/i', $this->source ) )
		{	// Extension is incorrect:
			return sprintf( TB_('%s has an unrecognized extension.'), '<code>'.$this->source.'</code>' );
		}

		return true;
	}


	/**
	 * Unzip archive
	 *
	 * @return boolean
	 */
	function unzip()
	{
		if( isset( $this->unzip_result ) )
		{	// Don't unzip archive twice:
			return $this->unzip_result;
		}

		if( is_dir( $this->source ) )
		{	// Source is a folder:
			$this->unzip_result = false;
			$this->unzip_errors = '';
			return false;
		}

		if( ! preg_match( '/\.zip$/i', $this->source ) )
		{	// Wrong source:
			$this->unzip_result = false;
			$this->unzip_errors = '';
			return false;
		}

		evo_flush();

		// Extract ZIP and check if it contians at least one markdown file:
		global $media_path;

		// $ZIP_folder_path must be deleted after import!
		$this->unzip_folder_path = $media_path.'import/temp-'.md5( rand() );

		// Try to unpack:
		$this->unzip_result = unpack_archive( $this->source, $this->unzip_folder_path, true, basename( $this->source ), false );

		if( $this->unzip_result !== true )
		{	// Store unzip error from unpack_archive():
			$this->unzip_errors = $this->unzip_result;
			$this->unzip_result = false;
		}

		return $this->unzip_result;
	}


	/**
	 * Get data to start import from markdown folder or ZIP file
	 *
	 * @param string Key of data
	 * @return array|string|NULL Value of the requested data, NULL - ,
	 *                Full data array:
	 *                 'error' - FALSE on success OR error message ,
	 *                 'path'  - Path to folder with markdown files,
	 *                 'type'  - 'folder', 'zip'.
	 */
	function get_data( $key = NULL )
	{
		if( ! isset( $this->data ) )
		{	// Load data in cache:

			$errors = '';

			$folder_path = NULL;
			if( is_dir( $this->source ) )
			{	// Use a folder:
				$folder_path = $this->source;
			}
			else
			{	// Try to extract ZIP:
				if( $this->unzip() )
				{	// Set folder on success unzipping:
					$folder_path = $this->unzip_folder_path;
				}
				else
				{	// Display errors:
					$errors .= $this->unzip_errors;
				}
			}

			// Check if folder contians at least one markdown file:
			if( empty( $this->unzip_errors ) &&
			    ( $folder_path === NULL || ! check_folder_with_extensions( $folder_path, 'md' ) ) )
			{	// No markdown is detected in ZIP package:
				$errors .= '<p class="text-danger">'.TB_('No markdown file is detected in the selected source.').'</p>';
				if( ! empty( $this->unzip_folder_path ) && file_exists( $this->unzip_folder_path ) )
				{	// Delete temporary folder that contains the files from extracted ZIP package:
					rmdir_r( $this->unzip_folder_path );
				}
			}

			// Cache data:
			$this->data = array(
				'errors' => empty( $errors ) ? false : $errors,
				'path'   => $folder_path,
				'type'   => ( empty( $this->unzip_folder_path ) ? 'dir' : 'zip' ),
			);
		}

		if( $key === NULL )
		{
			return $this->data;
		}
		else
		{
			return isset( $this->data[ $key ] ) ? $this->data[ $key ] : NULL;
		}
	}


	/**
	 * Set option
	 *
	 * @param string Option name
	 * @param string Option value
	 */
	function set_option( $option_name, $option_value )
	{
		$this->options[ $option_name ] = $option_value;
	}


	/**
	 * Set option
	 *
	 * @param string Option name
	 * @return string|NULL Option value, NULL - unknown option
	 */
	function get_option( $option_name )
	{
		if( isset( $this->options[ $option_name ] ) )
		{	// Use custom value:
			return $this->options[ $option_name ];
		}

		if( isset( $this->options_defs[ $option_name ] ) )
		{	// Use default value:
			return $this->options_defs[ $option_name ]['default'];
		}

		return NULL;
	}


	/**
	 * Load import data from request
	 *
	 * @return boolean TRUE on load all fields without error
	 */
	function load_from_Request()
	{
		global $Session;

		// Collection:
		$this->coll_ID = param( 'md_blog_ID', 'integer', 0 );
		param_check_not_empty( 'md_blog_ID', TB_('Please select a collection!') );
		// Save last import collection in Session:
		$Session->set( 'last_import_coll_ID', $this->coll_ID );

		// Import File/Folder:
		$this->source = param( 'import_file', 'string', '' );
		$check_source_result = $this->check_source();
		if( $check_source_result !== true )
		{	// Don't import if errors have been detected:
			param_error( 'import_file', $check_source_result );
		}

		// Load options:
		foreach( $this->options_defs as $option_key => $option )
		{
			$this->set_option( $option_key, param( $option_key, $option['type'], ( $option['type'] == 'integer' ? 0 : $option['default'] ) ) );
		}

		if( $this->get_option( 'import_type' ) == 'delete' &&
		    param( 'import_type_delete_confirm', 'string' ) !== 'DELETE' )
		{	// If deleting/replacing is not confirmed:
			param_error( 'import_type_delete_confirm', sprintf( TB_('Type %s to confirm'), '<code>DELETE</code>' ).'!' );
		}

		return ! param_errors_detected();
	}


	/**
	 * Import markdown data from ZIP file or folder into b2evolution database
	 */
	function execute()
	{
		global $Blog, $DB, $tableprefix, $media_path, $media_url, $current_User, $localtimenow, $Plugins;

		$folder_path = $this->get_data( 'path' );
		$source_folder_zip_name = basename( $this->source );

		// Get Collection for current import:
		$md_Blog = & $this->get_Blog();
		// Set current collection because it is used inside several functions like urltitle_validate():
		$Blog = $md_Blog;

		// Check if we should skip a single folder in ZIP archive root which is the same as ZIP file name:
		$root_folder_path = $folder_path;
		if( ! empty( $source_folder_zip_name ) )
		{	// This is an import from ZIP archive
			$zip_file_name = preg_replace( '#\.zip$#i', '', $source_folder_zip_name );
			if( file_exists( $folder_path.'/'.$zip_file_name ) )
			{	// If folder exists in the root with same name as ZIP file name:
				$skip_single_zip_root_folder = true;
				if( $folder_path_handler = @opendir( $folder_path ) )
				{
					while( ( $file = readdir( $folder_path_handler ) ) !== false )
					{
						if( ! preg_match( '#^([\.]{1,2}|__MACOSX|'.preg_quote( $zip_file_name ).')$#i', $file ) )
						{	// This is a different file or folder than ZIP file name:
							$skip_single_zip_root_folder = false;
							break;
						}
					}
					closedir( $folder_path_handler );
				}
				if( $skip_single_zip_root_folder )
				{	// Skip root folder with same name as ZIP file name:
					$folder_path .= '/'.$zip_file_name;
					$source_folder_zip_name .= '/'.$zip_file_name;
				}
			}
		}

		if( ! $this->check_manifest( $folder_path ) )
		{	// Stop import because of restriction from detected file manifest.yaml:
			return;
		}

		$DB->begin();

		if( $this->get_option( 'import_type' ) == 'delete' )
		{	// Remove data from selected collection:

			// Get existing categories
			$SQL = new SQL( 'Get existing categories of collection #'.$this->coll_ID );
			$SQL->SELECT( 'cat_ID' );
			$SQL->FROM( 'T_categories' );
			$SQL->WHERE( 'cat_blog_ID = '.$DB->quote( $this->coll_ID ) );
			$old_categories = $DB->get_col( $SQL );
			if( !empty( $old_categories ) )
			{ // Get existing posts
				$SQL = new SQL();
				$SQL->SELECT( 'post_ID' );
				$SQL->FROM( 'T_items__item' );
				$SQL->WHERE( 'post_main_cat_ID IN ( '.implode( ', ', $old_categories ).' )' );
				$old_posts = $DB->get_col( $SQL->get() );
			}

			$this->log( TB_('Removing the comments... ') );
			if( !empty( $old_posts ) )
			{
				$SQL = new SQL();
				$SQL->SELECT( 'comment_ID' );
				$SQL->FROM( 'T_comments' );
				$SQL->WHERE( 'comment_item_ID IN ( '.implode( ', ', $old_posts ).' )' );
				$old_comments = $DB->get_col( $SQL->get() );
				$DB->query( 'DELETE FROM T_comments WHERE comment_item_ID IN ( '.implode( ', ', $old_posts ).' )' );
				if( !empty( $old_comments ) )
				{
					$DB->query( 'DELETE FROM T_comments__votes WHERE cmvt_cmt_ID IN ( '.implode( ', ', $old_comments ).' )' );
					$DB->query( 'DELETE FROM T_links WHERE link_cmt_ID IN ( '.implode( ', ', $old_comments ).' )' );
				}
			}
			$this->log( TB_('OK').'<br />' );

			$this->log( TB_('Removing the posts... ') );
			if( !empty( $old_categories ) )
			{
				$DB->query( 'DELETE FROM T_items__item WHERE post_main_cat_ID IN ( '.implode( ', ', $old_categories ).' )' );
				if( !empty( $old_posts ) )
				{ // Remove the post's data from related tables
					if( $this->get_option( 'delete_files' ) )
					{ // Get the file IDs that should be deleted from hard drive
						$SQL = new SQL();
						$SQL->SELECT( 'DISTINCT link_file_ID' );
						$SQL->FROM( 'T_links' );
						$SQL->WHERE( 'link_itm_ID IN ( '.implode( ', ', $old_posts ).' )' );
						$deleted_file_IDs = $DB->get_col( $SQL->get() );
					}
					$DB->query( 'DELETE FROM T_items__item_settings WHERE iset_item_ID IN ( '.implode( ', ', $old_posts ).' )' );
					$DB->query( 'DELETE FROM T_items__prerendering WHERE itpr_itm_ID IN ( '.implode( ', ', $old_posts ).' )' );
					$DB->query( 'DELETE FROM T_items__subscriptions WHERE isub_item_ID IN ( '.implode( ', ', $old_posts ).' )' );
					$DB->query( 'DELETE FROM T_items__version WHERE iver_itm_ID IN ( '.implode( ', ', $old_posts ).' )' );
					$DB->query( 'DELETE FROM T_postcats WHERE postcat_post_ID IN ( '.implode( ', ', $old_posts ).' )' );
					$DB->query( 'DELETE FROM T_slug WHERE slug_itm_ID IN ( '.implode( ', ', $old_posts ).' )' );
					$DB->query( 'DELETE l, lv FROM T_links AS l
												 LEFT JOIN T_links__vote AS lv ON lv.lvot_link_ID = l.link_ID
												WHERE l.link_itm_ID IN ( '.implode( ', ', $old_posts ).' )' );
					$DB->query( 'DELETE FROM T_items__user_data WHERE itud_item_ID IN ( '.implode( ', ', $old_posts ).' )' );

					// Call plugin event after Items were deleted:
					$Plugins->trigger_event( 'ImporterAfterItemsDelete', array(
							'type'             => $this->import_code,
							'Importer'         => $this,
							'deleted_item_IDs' => $old_posts,
						) );
				}
			}
			$this->log( TB_('OK').'<br />' );

			$this->log( TB_('Removing the categories... ') );
			$DB->query( 'DELETE FROM T_categories WHERE cat_blog_ID = '.$DB->quote( $this->coll_ID ) );
			$ChapterCache = & get_ChapterCache();
			$ChapterCache->clear();
			$this->log( TB_('OK').'<br />' );

			$this->log( TB_('Removing the tags that are no longer used... ') );
			if( !empty( $old_posts ) )
			{ // Remove the tags

				// Get tags from selected blog
				$SQL = new SQL();
				$SQL->SELECT( 'itag_tag_ID' );
				$SQL->FROM( 'T_items__itemtag' );
				$SQL->WHERE( 'itag_itm_ID IN ( '.implode( ', ', $old_posts ).' )' );
				$old_tags_this_blog = array_unique( $DB->get_col( $SQL->get() ) );

				if( !empty( $old_tags_this_blog ) )
				{
					// Get tags from other blogs
					$SQL = new SQL();
					$SQL->SELECT( 'itag_tag_ID' );
					$SQL->FROM( 'T_items__itemtag' );
					$SQL->WHERE( 'itag_itm_ID NOT IN ( '.implode( ', ', $old_posts ).' )' );
					$old_tags_other_blogs = array_unique( $DB->get_col( $SQL->get() ) );
					$old_tags_other_blogs_sql = !empty( $old_tags_other_blogs ) ? ' AND tag_ID NOT IN ( '.implode( ', ', $old_tags_other_blogs ).' )': '';

					// Remove the tags that are no longer used
					$DB->query( 'DELETE FROM T_items__tag
						WHERE tag_ID IN ( '.implode( ', ', $old_tags_this_blog ).' )'.
						$old_tags_other_blogs_sql );
				}

				// Remove the links of tags with posts
				$DB->query( 'DELETE FROM T_items__itemtag WHERE itag_itm_ID IN ( '.implode( ', ', $old_posts ).' )' );
			}
			$this->log( TB_('OK').'<br />' );

			if( $this->get_option( 'delete_files' ) )
			{ // Delete the files
				$this->log( TB_('Removing the files... ') );

				if( ! empty( $deleted_file_IDs ) )
				{
					// Commit the DB changes before files deleting
					$DB->commit();

					// Get the deleted file IDs that are linked to other objects
					$SQL = new SQL();
					$SQL->SELECT( 'DISTINCT link_file_ID' );
					$SQL->FROM( 'T_links' );
					$SQL->WHERE( 'link_file_ID IN ( '.implode( ', ', $deleted_file_IDs ).' )' );
					$linked_file_IDs = $DB->get_col( $SQL->get() );
					// We can delete only the files that are NOT linked to other objects
					$deleted_file_IDs = array_diff( $deleted_file_IDs, $linked_file_IDs );

					$FileCache = & get_FileCache();
					foreach( $deleted_file_IDs as $deleted_file_ID )
					{
						if( ! ( $deleted_File = & $FileCache->get_by_ID( $deleted_file_ID, false, false ) ) )
						{ // Incorrect file ID
							$this->log( '<p class="text-danger">'.sprintf( TB_('No file #%s found in DB. It cannot be deleted.'), $deleted_file_ID ).'</p>' );
						}
						if( ! $deleted_File->unlink() )
						{ // No permission to delete file
							$this->log( '<p class="text-danger">'.sprintf( TB_('Could not delete the file %s.'), '<code>'.$deleted_File->get_full_path().'</code>' ).'</p>' );
						}
						// Clear cache to save memory
						$FileCache->clear();
					}

					// Start new transaction for the data inserting
					$DB->begin();
				}

				$this->log( TB_('OK').'<br />' );
			}

			$this->log( '<br />' );
		}

		// Get all subfolders and files from the source folder:
		$files = get_filenames( $folder_path );
		$folder_path_length = strlen( $folder_path );

		/* Import categories: */
		$this->log( '<h3>'.TB_('Importing the categories...').' </h3>' );

		load_class( 'chapters/model/_chapter.class.php', 'Chapter' );
		$ChapterCache = & get_ChapterCache();

		$categories = array();
		$cat_results_num = array(
			'added_success'   => 0,
			'added_failed'    => 0,
			'updated_success' => 0,
			'updated_failed'  => 0,
			'no_changed'      => 0,
		);
		foreach( $files as $f => $file_path )
		{
			$file_path = str_replace( '\\', '/', $file_path );

			if( ! is_dir( $file_path ) ||
					preg_match( '#/((.*\.)?assets|__MACOSX)(/|$)#i', $file_path ) )
			{	// Skip a not folder or reserved folder:
				continue;
			}

			$relative_path = substr( $file_path, $folder_path_length + 1 );

			$this->log( '<p>'.sprintf( TB_('Importing category: %s'), '"<b>'.$relative_path.'</b>"...' ) );

			// Get name of current category:
			$last_index = strrpos( $relative_path, '/' );
			$category_name = $last_index === false ? $relative_path : substr( $relative_path, $last_index + 1 );

			// Always reuse existing categories on "update" mode:
			$reuse_cats = ( $this->get_option( 'import_type' ) == 'update' ||
				// Should we reuse existing categories on "append" mode?
				( $this->get_option( 'import_type' ) == 'append' && $this->get_option( 'reuse_cats' ) ) );
				// Don't try to use find existing categories on replace mode.

			if( $reuse_cats && $Chapter = & $this->get_Chapter( $relative_path ) )
			{	// Use existing category with same full url path:
				$categories[ $relative_path ] = $Chapter->ID;
				if( $category_name == $Chapter->get( 'name' ) )
				{	// Don't update category with same name:
					$cat_results_num['no_changed']++;
					$this->log( TB_('No change') );
				}
				else
				{	// Try to update category with different name but same slug:
					$Chapter->set( 'name', $category_name );
					if( $Chapter->dbupdate() )
					{	// If category is updated successfully:
						$this->log( '<span class="text-warning">'.TB_('Updated').'</span>' );
						$cat_results_num['updated_success']++;
					}
					else
					{	// Don't translate because it should not happens:
						$this->log( '<span class="text-danger">Cannot be updated</span>' );
						$cat_results_num['updated_failed']++;
					}
				}
			}
			else
			{	// Create new category:
				$Chapter = new Chapter( NULL, $this->coll_ID );

				// Get parent path:
				$parent_path = substr( $relative_path, 0, $last_index );

				$Chapter->set( 'name', $category_name );
				$Chapter->set( 'urlname', urltitle_validate( $category_name, $category_name, 0, false, 'cat_urlname', 'cat_ID', 'T_categories' ) );
				if( ! empty( $parent_path ) && isset( $categories[ $parent_path ] ) )
				{	// Set category parent ID:
					$Chapter->set( 'parent_ID', $categories[ $parent_path ] );
				}
				if( $Chapter->dbinsert() )
				{	// If category is inserted successfully:
					// Save new category in cache:
					$categories[ $relative_path ] = $Chapter->ID;
					$this->log( '<span class="text-success">'.TB_('Added').'</span>' );
					$cat_results_num['added_success']++;
					// Add new created Chapter into cache to avoid wrong main category ID in ItemLight::get_main_Chapter():
					$ChapterCache->add( $Chapter );
				}
				else
				{	// Don't translate because it should not happens:
					$this->log( '<span class="text-danger">Cannot be inserted</span>' );
					$cat_results_num['added_failed']++;
				}
			}
			$this->log( '.</p>' );

			// Unset folder in order to don't check it twice on creating posts below:
			unset( $files[ $f ] );
		}

		foreach( $cat_results_num as $cat_result_type => $cat_result_num )
		{
			if( $cat_result_num > 0 )
			{
				switch( $cat_result_type )
				{
					case 'added_success':
						$cat_msg_text = TB_('%d categories imported');
						$cat_msg_class = 'text-success';
						break;
					case 'added_failed':
						// Don't translate because it should not happens:
						$cat_msg_text = '%d categories could not be inserted';
						$cat_msg_class = 'text-danger';
						break;
					case 'updated_success':
						$cat_msg_text = TB_('%d categories updated');
						$cat_msg_class = 'text-warning';
						break;
					case 'updated_failed':
						// Don't translate because it should not happens:
						$cat_msg_text = '%d categories could not be updated';
						$cat_msg_class = 'text-danger';
						break;
					case 'no_changed':
						$cat_msg_text = TB_('%d categories no changed');
						$cat_msg_class = '';
						break;
				}
				$this->log( '<b'.( empty( $cat_msg_class ) ? '' : ' class="'.$cat_msg_class.'"').'>'.sprintf( $cat_msg_text, $cat_result_num ).'</b><br>' );
			}
		}

		// Load Spyc library to parse YAML data:
		load_funcs( '_ext/spyc/Spyc.php' );

		/* Import posts: */
		$this->log( '<h3>'.TB_('Importing the posts...').'</h3>' );

		load_class( 'items/model/_item.class.php', 'Item' );
		$ItemCache = get_ItemCache();

		$Plugins_admin = & get_Plugins_admin();

		$posts_count = 0;
		$post_results_num = array(
			'added_success'   => 0,
			'added_failed'    => 0,
			'updated_success' => 0,
			'updated_failed'  => 0,
			'no_changed'      => 0,
		);
		$imported_slugs = array();

		// Force SQL errors handling:
		$current_db_halt_on_error = $DB->halt_on_error;
		$DB->halt_on_error = 'throw';

		foreach( $files as $file_path )
		{
			// End all opened log wrappers if they were not closed by some die error:
			$this->close_log_wrappers();

		try
		{	// Try and catch all SQL errors:
			$file_path = str_replace( '\\', '/', $file_path );

			if( ! preg_match( '#([^/]+)\.md$#i', $file_path, $file_match ) ||
					preg_match( '#/(\.[^/]*$|((.*\.)?assets|__MACOSX)/)#i', $file_path ) )
			{	// Skip a not markdown file,
				// and if file name is started with . (dot),
				// and files from *.assets and __MACOSX folders:
				continue;
			}

			// Use file name as slug for new Item:
			$item_slug = $file_match[1];

			// Flag to know if at least one 
			$this->item_yaml_is_updated = false;

			// Extract title from content:
			$this->item_file_is_updated = false;
			$item_content = trim( file_get_contents( $file_path ) );
			$this->item_file_content = $item_content;
			$item_content_hash = md5( $item_content );
			$content_regexp = '~^(---[\r\n]+(.+?)[\r\n]+---[\r\n]*)?' // YAML data
			  .'(!\[.*?\]\(.+?\)(\{.+?\})?(\r?\n\*.+?\*)?[\r\n]*)?'   // Teaser image
			  .'(#+\s*(.+?)\s*#*\s*([\r\n]+|$))?'                     // First header is used as Item Title
			  .'(.*)$~s';                                             // Item content
			if( preg_match( $content_regexp, $item_content, $content_match ) )
			{
				$item_yaml_data = trim( $content_match[2] );
				if( ! empty( $this->yaml_fields ) && ! empty( $item_yaml_data ) )
				{	// Parse YAML data:
					$item_yaml_data = spyc_load( $item_yaml_data );
				}
				else
				{	// Don't parse when no supported YAML fields or no provided YAML data:
					$item_yaml_data = NULL;
				}
				$item_teaser_image_tag = trim( $content_match[3], "\r\n" );
				$item_title = empty( $content_match[7] )
					// Use yaml short title or item slug as title when title in content is not defined:
					? ( empty( $item_yaml_data['short-title'] ) ? $item_slug : $item_yaml_data['short-title'] )
					// Use title from content:
					: $content_match[7];
				$item_content = $content_match[3].$content_match[9];
			}
			else
			{
				$item_yaml_data = NULL;
				$item_title = $item_slug;
				$item_teaser_image_tag = NULL;
			}

			// Limit title by max possible length:
			$item_title = utf8_substr( $item_title, 0, 255 );

			// Get path to item source/md file:
			$item_source_path = '<code>'.$source_folder_zip_name.substr( $file_path, strlen( $folder_path ) ).'</code>';
			if( $this->get_data( 'type' ) == 'dir' )
			{	// Provide link to md file when import is from forder:
				// (NOTE: We cannot provide link from ZIP arhive because temp extracted folder is deleted after import is done so urls are died)
				$item_source_path = '<a href="'.$media_url.substr( $file_path, strlen( $media_path ) ).'" target="_blank">'.$item_source_path.'</a>';
			}

			$this->log( sprintf( 'Importing Item: %s', '"<b>'.$item_title.'</b>"<br/>'
				//.'<a href="/'.$item_slug.'</a>' // We don't know the exact URL of the post yet. We will display a link later.
				.'&nbsp; &nbsp; Source: '.$item_source_path ) );

			if( in_array( $item_slug, $imported_slugs ) )
			{	// Skip md file/post with same name from different folder/category:
				$this->log( '<ul class="list-default"><li class="text-danger"><span class="label label-danger">'.TB_('ERROR').'</span> '.sprintf( '%s already found before, ignoring second instance.', '<code>'.$item_slug.'.md</code>' ).'</li></ul><br>' );
				continue;
			}

			// Store imported posts slugs to avoid import md files with same name:
			$imported_slugs[] = $item_slug;

			$relative_path = substr( $file_path, $folder_path_length + 1 );

			// Try to get a category ID:
			$category_path = substr( $relative_path, 0, strrpos( $relative_path, '/' ) );
			if( isset( $categories[ $category_path ] ) )
			{	// Use existing category:
				$category_ID = $categories[ $category_path ];
			}
			else
			{	// Use default category:
				if( ! isset( $default_category_ID ) )
				{	// If category is still not defined then we should create default, because blog must has at least one category
					$default_category_urlname = $md_Blog->get( 'urlname' ).'-main';
					if( ! ( $default_Chapter = & $ChapterCache->get_by_urlname( $default_category_urlname, false, false ) ) )
					{	// Create default category if it doesn't exist yet:
						$default_Chapter = new Chapter( NULL, $this->coll_ID );
						$default_Chapter->set( 'name', TB_('Uncategorized') );
						$default_Chapter->set( 'urlname', urltitle_validate( $default_category_urlname, $default_category_urlname, 0, false, 'cat_urlname', 'cat_ID', 'T_categories' ) );
						$default_Chapter->dbinsert();
						// Add new created Chapter into cache to avoid wrong main category ID in ItemLight::get_main_Chapter():
						$ChapterCache->add( $default_Chapter );
					}
					$default_category_ID = $default_Chapter->ID;
				}
				$category_ID = $default_category_ID;
			}

			$item_slug = get_urltitle( $item_slug );
			if( $this->get_option( 'import_type' ) == 'update' )
			{	// For "update" mode try to find existing Item by slug:
				$Item = & $this->get_Item( $item_slug );
				if( $Item === false )
				{	// Skip if Item is found by same slug but in another Collection:
					$this->close_log_wrapper();
					$this->log( '<br>' );
					continue;
				}
			}

			if( $this->get_option( 'import_type' ) != 'update' ||
			    ! $Item )
			{	// Create new Item for not update mode or if it is not found by slug in the requested Collection:
				$Item = new Item();
				$Item->set( 'creator_user_ID', ( is_logged_in() ? $current_User->ID : 1/*Run from CLI mode by admin*/ )  );
				$Item->set( 'datestart', date2mysql( $localtimenow ) );
				$Item->set( 'datecreated', date2mysql( $localtimenow ) );
				$Item->set( 'status', 'published' );
				$Item->set( 'ityp_ID', $md_Blog->get_setting( 'default_post_type' ) );
				$Item->set( 'locale', $md_Blog->get( 'locale' ) );
				$Item->set( 'urltitle', urltitle_validate( $item_slug, $item_slug, 0, false, 'post_urltitle', 'post_ID', 'T_items__item' ) );
			}

			// Get and update item content hash:
			$prev_last_import_hash = $Item->get_setting( 'last_import_hash' );
			$Item->set_setting( 'last_import_hash', $item_content_hash );
			// Decide content was changed when current hash is different than previous:
			$item_content_was_changed = ( $prev_last_import_hash != $item_content_hash );

			$prev_category_ID = $Item->get( 'main_cat_ID' );
			// Set new category for new Item or when post was moved to different category:
			$Item->set( 'main_cat_ID', $category_ID );

			if( $this->get_option( 'convert_md_links' ) )
			{	// Convert Markdown links to b2evolution ShortLinks:
				// NOTE: Do this even when last import hash is different because below we may update content on import images:
				$this->item_content_is_updated_by_linked_file = false;
				$this->current_item_locale = $Item->get( 'locale' );
				// Do convert:
				$item_content = preg_replace_callback( '#(^|[^\!])\[([^\[\]]*)\]\(((([a-z]*://)?([^\)]+[/\\\\])?([^\)]+?)(\.[a-z]{2,4})?)(\#[^\)]+)?)?\)#i', array( $this, 'callback_convert_links' ), $item_content );
				if( $this->item_content_is_updated_by_linked_file )
				{	// Force to update content when at least one link was replaced with proper link to post with same language as current post:
					$item_content_was_changed = true;
				}
			}

			// Set flag to don't filter content twice by renderer plugins:
			$item_is_filtered_by_plugins = false;

			if( $this->get_option( 'force_item_update' ) || $item_content_was_changed )
			{	// Set new fields only when import hash(title + content + YAML data) was really changed:
				$Item->set( 'lastedit_user_ID', ( is_logged_in() ? $current_User->ID : 1/*Run from CLI mode by admin*/ ) );
				$Item->set( 'datemodified', date2mysql( $localtimenow ) );

				// Filter title and content by renderer plugins:
				$item_is_filtered_by_plugins = true;
				$item_Blog = & $Item->get_Blog();
				$item_plugin_params = array(
						'object_type' => 'Item',
						'object'      => & $Item,
						'object_Blog' => & $item_Blog,
					);
				$Plugins_admin->filter_contents( $item_title /* by ref */, $item_content /* by ref */, $Item->get_renderers_validated(), $item_plugin_params /* by ref */ );
				$Item->set( 'title', $item_title );
				$Item->set( 'content', $item_content );

				// NOTE: Use auto generating of excerpt only after set of YAML fields,
				//       because there excerpt field may be defined as not auto generated.
				if( $Item->get( 'excerpt_autogenerated' ) && ! empty( $item_content ) )
				{	// Generate excerpt from content:
					$Item->set( 'excerpt', excerpt( $item_content ), true );
				}
			}

			foreach( $this->yaml_fields as $yaml_field )
			{	// Set YAML field:
				if( ! isset( $item_yaml_data[ $yaml_field ] ) )
				{	// Skip if the Item has no defined value for this YAML field:
					continue;
				}
				$yaml_method = 'set_yaml_'.str_replace( '-', '_', $yaml_field );
				if( method_exists( $this, $yaml_method ) )
				{	// Call method to set YAML field:
					$this->$yaml_method( $item_yaml_data[ $yaml_field ], $Item );
				}

				// Call plugin event to set YAML field:
				$Plugins->trigger_event( 'ImporterSetItemField', array(
						'type'       => $this->import_code,
						'Importer'   => $this,
						'Item'       => $Item,
						'field_type' => 'yaml',
						'field_name' => $yaml_field,
						'field_data' => $item_yaml_data[ $yaml_field ],
					) );
			}

			// Flag to know Item is updated in STEP 1:
			$item_is_updated_step_1 = false;

			$item_result_messages = array();
			$item_result_class = 'default';
			$item_result_suffix = '';
			if( empty( $Item->ID ) )
			{	// Insert new Item:
				$this->log_list( '<span class="label label-info">INSERTING</span> Item in DB...' );
				if( $Item->dbinsert() )
				{	// If post is inserted successfully:
					$item_is_updated_step_1 = true;
					$item_result_class = 'success';
					$item_result_messages[] = /* TRANS: Result of imported Item */ TB_('new file');
					$item_result_messages[] = /* TRANS: Result of imported Item */ TB_('New Post added to DB');
					$post_results_num['added_success']++;
				}
				else
				{	// Don't translate because it should not happens:
					$item_result_messages[] = 'Cannot be inserted';
					$item_result_class = 'danger';
					$post_results_num['added_failed']++;
				}
			}
			else
			{	// Update existing Item:
				if( ! $this->get_option( 'force_item_update' ) && ! $item_content_was_changed && $prev_category_ID == $category_ID && ! $this->item_yaml_is_updated )
				{	// Don't try to update item in DB because import hash(title + content) was not changed after last import:
					$post_results_num['no_changed']++;
					$item_result_messages[] = /* TRANS: Result of imported Item */ TB_('No change');
				}
				else
				{
					$this->log_list( '<span class="label label-info">UPDATING</span> Item in DB...' );
					if(
					// This is UPDATE 1 of 3 (there is a 2nd UPDATE for [image:] tags. These tags cannot be created before the Item ID is known.):
					$Item->dbupdate( true, true, true, 
						$this->get_option( 'force_item_update' ) || $item_content_was_changed/* Force to create new revision only when file hash(title+content) was changed after last import or when update is forced */ ) )      
	// TODO: fp>yb: please give example of situation where we want to NOT create a new revision ? (I think we ALWAYS want to create a new revision)				
					{	// Item has been updated successfully:
						$item_is_updated_step_1 = true;
						$item_result_class = 'warning';
						if( $this->get_option( 'force_item_update' ) )
						{	// If item update was forced:
							$item_result_messages[] = /* TRANS: Result of imported Item */ TB_('Forced update');
						}
						else
						{	// Normal update because content or category was changed:
							$item_result_messages[] = /* TRANS: Result of imported Item */ TB_('Has changed');
						}
						if( $prev_category_ID != $category_ID )
						{	// If moved to different category:
							$item_result_messages[] =/* TRANS: Result of imported Item */  TB_('Moved to different category');
						}
						if( $this->item_yaml_is_updated )
						{	// If YAML fields were updated:
							$item_result_messages[] =/* TRANS: Result of imported Item */  TB_('Updated YAML fields');
						}
						if( $item_content_was_changed )
						{	// If content was changed:
							$item_result_messages[] = /* TRANS: Result of imported Item */ TB_('New revision added to DB');
							if( $prev_last_import_hash === NULL )
							{	// Display additional warning when Item was edited manually:
								global $admin_url;
								$item_result_suffix = '. <br /><span class="label label-danger">'.TB_('CONFLICT').'</span> <b>'
									.sprintf( TB_('WARNING: this item has been manually edited. Check <a %s>changes history</a>'),
										'href="'.$admin_url.'?ctrl=items&amp;action=history&amp;p='.$Item->ID.'" target="_blank"' ).'</b>';
							}
						}
						$post_results_num['updated_success']++;
					}
					else
					{	// Failed update:
						// Don't translate because it should not happens:
						$item_result_messages[] = 'Cannot be updated';
						$item_result_class = 'danger';
						$post_results_num['updated_failed']++;
					}
				}
			}

			// Display result messages of Item inserting or updating:
			if( $Item->ID > 0 )
			{	// Set last message text as link to permanent URL of the inserted/updated Item:
				$last_msg_i = count( $item_result_messages ) - 1;
				$item_result_messages[ $last_msg_i ] = $Item->get_title( array(
						'title_field'    => 'title_override',
						'title_override' => $item_result_messages[ $last_msg_i ],
						'link_type'      => ( $Item->get_permalink_type() == 'none' ? 'admin_view' : '#' ),
						'link_target'    => '_blank',
					) )
					.$Item->get_history_link( array(
						'before'     => ' ',
						// Don't check permission because this link must be displayed even
						// when import is called from CLI mode without logged in admin:
						'check_perm' => false,
					) );
			}
			$this->log_list( '<li class="text-'.$item_result_class.'"><span class="label label-'.$item_result_class.'">RESULT</span> '
					.implode( ' -> ', $item_result_messages )
					.$item_result_suffix
				.'</li>', NULL );

			// Call plugin event after Item was imported:
			$Plugins->trigger_event( 'ImporterAfterItemImport', array(
					'type'     => $this->import_code,
					'Importer' => $this,
					'Item'     => $Item,
					'data'     => $item_yaml_data,
				) );

			$files_imported = false;
			if( ! empty( $Item->ID ) )
			{
				// Link files:
				if( preg_match_all( '#(\[)?\!\[([^\]]*)\]\(([^\)"]+\.('.$this->get_image_extensions().'))\s*("[^"]*")?\)(\{\..+?\})?(\r?\n?\*.*?\*(\r|\n|$))?(.*?\]\((.*?)\))?(\{\..+?\})?#i', $item_content, $image_matches ) )
				{
					$updated_item_content = $item_content;
					$all_links_count = 0;
					$new_links_count = 0;
					$LinkOwner = new LinkItem( $Item );
					$file_params = array(
							'file_root_type' => 'collection',
							'file_root_ID'   => $this->coll_ID,
							'folder_path'    => 'quick-uploads/'.$Item->get( 'urltitle' ),
						);
					foreach( $image_matches[3] as $i => $image_relative_path )
					{
						$image_alt = trim( $image_matches[2][$i] );
						if( strtolower( $image_alt ) == 'img' ||
								strtolower( $image_alt ) == 'image' )
						{	// Don't use this default text for alt image text:
							$image_alt = '';
						}
						$file_params['file_title'] = trim( $image_matches[5][$i], ' "' );
						// Detect link position:
						$content_image_parts = explode( $image_matches[0][$i], $updated_item_content, 2 );
						if( $item_teaser_image_tag == $image_matches[0][$i] &&
						    count( $content_image_parts ) > 1 &&
						    trim( $content_image_parts[0], " \r\n" ) === '' )
						{	// Link image as teaser when image is first before header:
							$file_params['link_position'] = 'teaser';
						}
						else
						{	// Link image as inline when header is before the image or no header in item's content:
							$file_params['link_position'] = 'inline';
						}
						// Try to find existing and linked image File or create, copy and link image File:
						if( $link_data = $this->link_file( $LinkOwner, $folder_path, $category_path, rtrim( $image_relative_path ), $file_params ) )
						{	// Replace this img tag from content with b2evolution format:
							if( $file_params['link_position'] == 'inline' )
							{	// Generate image inline tag:
								$image_inline_caption = preg_replace( '#^[\r\n\s"\*]+(.+?)[\r\n\s"\*]+$#', '$1', $image_matches[7][$i] ); // note: trim() doesn't remove char * on the right side as expected
								$image_inline_class = trim( str_replace( '}{', '', $image_matches[6][$i].$image_matches[11][$i] ), ' {}' );
								$image_options = array();
								// Caption has always a place if at least one option is defined below:
								$image_options[] = $image_inline_caption;
								if( $image_alt !== '' )
								{	// Alt:
									$image_options[] = $image_alt;
								}
								if( $image_matches[1][$i] == '[' && $image_matches[10][$i] !== '' )
								{	// HRef:
									$image_options[] = $image_matches[10][$i];
								}
								if( $image_inline_class !== '' )
								{	// Class:
									$image_options[] = $image_inline_class;
								}
								$image_options = ( count( $image_options ) > 2 || $image_options[0] !== '' ? ':'.implode( ':', $image_options ) : '' );
								$image_inline_tag = '[image:'.$link_data['ID'].$image_options.']';
							}
							else // 'teaser'
							{	// Don't provide inline tag for teaser image:
								$image_inline_tag = '';
							}
							$updated_item_content = replace_content( $updated_item_content, $image_matches[0][$i], $image_inline_tag, 'str', 1 );
							if( $link_data['type'] == 'new' )
							{	// Count new linked files:
								$new_links_count++;
							}
							$all_links_count++;
						}
					}

					if( $new_links_count > 0 || ( $item_is_updated_step_1 && $all_links_count > 0 ) )
					{	// Update content for new markdown image links which were replaced with b2evo inline tags format:
						if( $new_links_count > 0 )
						{	// Update content with new inline image tags:
							$this->log_list( sprintf( TB_('%d new image files were linked to the Item'), $new_links_count )
								.' -> './* TRANS: Result of imported Item */ TB_('Saving to DB').'.',
								'text-warning' );
						}
						else
						{	// Force to update content with inline image tags:
							$this->log_list( TB_('No image file changes BUT Item Update is required')
								.' -> './* TRANS: Result of imported Item */ TB_('Saving <code>[image:]</code> tags to DB').'.',
								'text-warning' );
						}
						if( ! $item_is_filtered_by_plugins )
						{	// Filter title and content by renderer plugins:
							$item_Blog = & $Item->get_Blog();
							$item_plugin_params = array(
									'object_type' => 'Item',
									'object'      => & $Item,
									'object_Blog' => & $item_Blog,
								);
							$Plugins_admin->filter_contents( $item_title /* by ref */, $updated_item_content /* by ref */, $Item->get_renderers_validated(), $item_plugin_params /* by ref */ );
						}
						$Item->set( 'content', $updated_item_content );
						// This is UPDATE 2 of 3 . It is only for [image:] tags.
						$Item->dbupdate( true, true, true, 'no'/* Force to do NOT create new revision because we do this above when store new content */ );
					}

					$files_imported = true;
				}
			}

			if( ! empty( $this->item_file_is_updated ) )
			{	// Update item's file with fixed content:
				if( ( $md_file_handle = @fopen( $file_path, 'w' ) ) &&
				    fwrite( $md_file_handle, $this->item_file_content ) )
				{	// Inform about updated file content:
					$this->log_list( sprintf( 'The file %s was updated on disk (import folder).', '<code>'.$item_slug.'.md</code>'), 'warning' );
					// Close file handle:
					fclose( $md_file_handle );
					// Update file hash after changing of the file's content in order to don't update the Item twice on next import:
					$Item->set_setting( 'last_import_hash', md5( $this->item_file_content ) );
					// This is UPDATE 3 of 3 . It is only changed YAML data.
					$Item->dbupdate( true, true, true, 'no'/* Force to do NOT create new revision because we do this above when store new content */ );
				}
				else
				{	// No file rights to write into the file:
					$this->log_list( sprintf( 'Impossible to update file %s with fixed content, please check file permissions.', '<code>'.$file_path.'</code>' ), 'error' );
				}
			}

		}
		catch( Exception $db_Exception )
		{	// Catch SQL error:
			$this->log( '<div style="background-color:#fdd;padding:1ex">'.$db_Exception->getMessage().'</div>', 'error' );
			$this->log( '<p class="red">Stopping import of this file. Continue import at NEXT .md file...</p>' );
		}

			// Separator between each Item log:
			$this->close_log_wrappers();
			$this->log( '<br>' );
		}

		// Revert DB option:
		$DB->halt_on_error = $current_db_halt_on_error;

		foreach( $post_results_num as $post_result_type => $post_result_num )
		{
			if( $post_result_num > 0 )
			{
				switch( $post_result_type )
				{
					case 'added_success':
						$post_msg_text = TB_('%d new posts added to DB');
						$post_msg_class = 'text-success';
						break;
					case 'added_failed':
						// Don't translate because it should not happens:
						$post_msg_text = '%d posts could not be inserted';
						$post_msg_class = 'text-danger';
						break;
					case 'updated_success':
						$post_msg_text = TB_('%d posts updated');
						$post_msg_class = 'text-warning';
						break;
					case 'updated_failed':
						// Don't translate because it should not happens:
						$post_msg_text = '%d posts could not be updated';
						$post_msg_class = 'text-danger';
						break;
					case 'no_changed':
						$post_msg_text = TB_('%d posts no changed');
						$post_msg_class = '';
						break;
				}
				$this->log( '<b'.( empty( $post_msg_class ) ? '' : ' class="'.$post_msg_class.'"').'>'.sprintf( $post_msg_text, $post_result_num ).'</b><br>' );
			}
		}

		// Commit changes before event_after_import() in order ot avoid unexpected rollback from there:
		$DB->commit();

		//TODO: CCC despite lots of progress log messages that are sent BEFORE the work is committed, and no error messages the changes are not written to DB at all anymore.
		//TODO: HIGHEST PRIO: Fix "hidden rollback"
		// see point A.2 C#11 from "LRT-364011-MD Importer still fails with unhandled exceptions on Glossary inserts (Duplicate entry)"

		// Execute additonal actions after import, e.g. by extended classes:
		$this->event_after_import();

		if( $this->get_data( 'type' ) == 'zip' && file_exists( $root_folder_path ) )
		{	// This folder was created only to extract files from ZIP package, Remove it now:
			rmdir_r( $root_folder_path );
		}

		$this->log( '<h4 class="text-success">'.TB_('Import completed.').'</h4>' );
	}


	/**
	 * Create object File from source path
	 *
	 * @param object LinkOwner
	 * @param string Source folder absolute path
	 * @param string Source Category folder name
	 * @param string Requested file relative path
	 * @param array Params
	 * @return boolean|array FALSE or Array on success ( 'ID' - Link ID, 'type' - 'new'/'old' )
	 */
	function link_file( $LinkOwner, $source_folder_absolute_path, $source_category_folder, $requested_file_relative_path, $params )
	{
		$params = array_merge( array(
				'file_root_type' => 'collection',
				'file_root_ID'   => '',
				'file_title'     => '',
				'folder_path'    => '',
				'link_position'  => 'inline',
			), $params );

		$requested_file_relative_path = ltrim( str_replace( '\\', '/', urldecode( $requested_file_relative_path ) ), '/' );

		$source_file_relative_path = $source_category_folder.'/'.$requested_file_relative_path;
		$file_source_path = $source_folder_absolute_path.'/'.$source_file_relative_path;

		if( strpos( get_canonical_path( $file_source_path ), $source_folder_absolute_path ) !== 0 )
		{	// Don't allow a traversal directory:
			$this->log_list( sprintf( 'Skipping file %s, because path is invalid.', '<code>'.$requested_file_relative_path.'</code>' ), 'error' );
			// Skip it:
			return false;
		}

		if( ! file_exists( $file_source_path ) )
		{	// File doesn't exist
			$this->log_list( sprintf( TB_('Unable to copy file %s, because it does not exist.'), '<code>'.$file_source_path.'</code>' ), 'error' );
			// Skip it:
			return false;
		}

		global $DB;

		$FileCache = & get_FileCache();

		// Get file name from path and replace all space chars with char "-":
		$file_source_name = str_replace( ' ', '-', basename( $file_source_path ) );
		// Get hash of file in order to find existing file in DB:
		$file_source_hash = md5_file( $file_source_path, true );

		// Try to find already existing File by hash in DB:
		$SQL = new SQL( 'Find file by hash' );
		$SQL->SELECT( 'file_ID, link_ID' );
		$SQL->FROM( 'T_files' );
		$SQL->FROM_add( 'LEFT JOIN T_links ON link_file_ID = file_ID AND link_itm_ID = '.$DB->quote( $LinkOwner->get_ID() ) );
		$SQL->WHERE( 'file_hash = '.$DB->quote( $file_source_hash ) );
		$SQL->ORDER_BY( 'link_itm_ID DESC, file_ID' );
		$SQL->LIMIT( '1' );
		$file_data = $DB->get_row( $SQL, ARRAY_A );
		if( ! empty( $file_data ) &&
				( $File = & $FileCache->get_by_ID( $file_data['file_ID'], false, false ) ) )
		{
			if( ! empty( $file_data['link_ID'] ) )
			{	// The found File is already linked to the Item:
				$this->log_list( sprintf( TB_('No file change, because %s is same as %s.'), '<code>'.$source_file_relative_path.'</code>', '<code>'.$File->get_rdfs_rel_path().'</code>' ) );
				return array( 'ID' => $file_data['link_ID'], 'type' => 'old' );
			}
			else
			{	// Try to link the found File object to the Item:
				if( $link_ID = $File->link_to_Object( $LinkOwner, 0, $params['link_position'] ) )
				{	// If file has been linked to the post
					$this->log_list( sprintf( TB_('File %s already exists in %s, it has been linked to this post as %s.'), '<code>'.$source_file_relative_path.'</code>', '<code>'.$File->get_rdfs_rel_path().'</code>', '<code>'.$params['link_position'].'</code>' ), 'text-warning' );
					return array( 'ID' => $link_ID, 'type' => 'new' );
				}
				else
				{	// If file could not be linked to the post:
					$this->log_list( sprintf( 'Existing file of %s could not be linked to this post.', '<code>'.$File->get_rdfs_rel_path().'</code>' ), 'text-warning' );
					return false;
				}
			}
		}

		// Get FileRoot by type and ID:
		$FileRootCache = & get_FileRootCache();
		$FileRoot = & $FileRootCache->get_by_type_and_ID( $params['file_root_type'], $params['file_root_ID'] );

		$replaced_File = NULL;
		$replaced_link_ID = NULL;

		if( $this->get_option( 'import_type' ) == 'update' )
		{	// Try to find existing and linked image File:
			$item_Links = $LinkOwner->get_Links();
			foreach( $item_Links as $item_Link )
			{
				if( ( $File = & $item_Link->get_File() ) &&
						$file_source_name == $File->get( 'name' ) )
				{	// We found File with same name:
					if( $File->get( 'hash' ) != $file_source_hash )
					{	// Update only really changed file:
						$replaced_File = $File;
						$replaced_link_ID = $item_Link->ID;
						$replaced_link_type = 'old';
						// Don't find next files:
						break;
					}
					else
					{	// No change for same file:
						$this->log_list( sprintf( TB_('No file change, because %s is same as %s.'), '<code>'.$source_file_relative_path.'</code>', '<code>'.$File->get_rdfs_rel_path().'</code>' ) );
						return array( 'ID' => $item_Link->ID, 'type' => 'old' );
					}
				}
			}
		}

		if( $this->get_option( 'import_type' ) != 'append' &&
				$replaced_File === NULL )
		{	// Find an existing File on disk to replace with new:
			$File = & $FileCache->get_by_root_and_path( $FileRoot->type, $FileRoot->in_type_ID, trailing_slash( $params['folder_path'] ).$file_source_name, true );
			if( $File && $File->exists() )
			{	// If file already exists:
				$replaced_File = $File;
			}
		}

		if( $replaced_File !== NULL )
		{	// The found File must be replaced:
			if( empty( $replaced_File->ID ) )
			{	// Create new File in DB with additional params:
				$replaced_File->set( 'title', $params['file_title'] );
				if( ! $replaced_File->dbinsert() )
				{	// Don't translate
					$this->log_list( sprintf( 'Cannot to create file %s in DB.', '<code>'.$replaced_File->get_full_path().'</code>' ), 'error' );
					return false;
				}
			}

			// Try to replace old file with new:
			if( ! copy_r( $file_source_path, $replaced_File->get_full_path() ) )
			{	// No permission to replace file:
				$this->log_list( sprintf( TB_('Unable to copy file %s to %s. Please, check the permissions assigned to this folder.'), '<code>'.$file_source_path.'</code>', '<code>'.$replaced_File->get_full_path().'</code>' ), 'error' );
				return false;
			}

			// If file has been updated successfully:
			// Clear evocache:
			$replaced_File->rm_cache();
			// Update file hash:
			$replaced_File->set_param( 'hash', 'string', md5_file( $replaced_File->get_full_path(), true ) );
			$replaced_File->dbupdate();

			if( $replaced_link_ID !== NULL )
			{	// Inform about replaced file:
				$this->log_list( sprintf( TB_('File %s has been replaced in %s successfully.'), '<code>'.$source_file_relative_path.'</code>', '<code>'.$File->get_rdfs_rel_path().'</code>' ), 'text-warning' );
			}
			elseif( $replaced_link_ID = $replaced_File->link_to_Object( $LinkOwner, 0, $params['link_position'] ) )
			{	// If file has been linked to the post
				$replaced_link_type = 'new';
				$this->log_list( sprintf( TB_('File %s already exists in %s, it has been updated and linked to this post as %s successfully.'), '<code>'.$source_file_relative_path.'</code>', '<code>'.$replaced_File->get_rdfs_rel_path().'</code>', '<code>'.$params['link_position'].'</code>' ), 'text-warning' );
			}
			else
			{	// If file could not be linked to the post:
				$this->log_list( sprintf( 'Existing file of %s could not be linked to this post.', '<code>'.$replaced_File->get_rdfs_rel_path().'</code>' ), 'error' );
				return false;
			}

			return array( 'ID' => $replaced_link_ID, 'type' => $replaced_link_type );
		}

		// Create new File:
		// - always for "append" mode,
		// - when File is not found above.

		// Get file name with a fixed name if file with such name already exists in the destination path:
		list( $File, $old_file_thumb ) = check_file_exists( $FileRoot, $params['folder_path'], $file_source_name );

		if( ! $File || ! copy_r( $file_source_path, $File->get_full_path() ) )
		{	// No permission to copy to the destination folder
			$this->log_list( sprintf( TB_('Unable to copy file %s to %s. Please, check the permissions assigned to this folder.'), '<code>'.$file_source_path.'</code>', '<code>'.$File->get_full_path().'</code>' ), 'error' );
			return false;
		}

		// Set additional params and create new File:
		$File->set( 'title', $params['file_title'] );
		$File->dbsave();

		if( $link_ID = $File->link_to_Object( $LinkOwner, 0, $params['link_position'] ) )
		{	// If file has been linked to the post
			$this->log_list( sprintf( TB_('New file %s has been imported to %s as %s successfully.'),
				'<code>'.$source_file_relative_path.'</code>',
				'<code>'.$File->get_rdfs_rel_path().'</code>'.
					( $file_source_name == $File->get( 'name' ) ? '' : '<span class="note">('.TB_('Renamed').'!)</span>'),
				'<code>'.$params['link_position'].'</code>'
			), 'success' );
		}
		else
		{	// If file could not be linked to the post:
			$this->log_list( sprintf( 'New file of %s could not be linked to this post.', '<code>'.$File->get_rdfs_rel_path().'</code>' ), 'text-warning' );
			return false;
		}

		return array( 'ID' => $link_ID, 'type' => 'new' );
	}


	/**
	 * Get category by provided folder path
	 *
	 * @param string Category folder path
	 * @param boolean Check by full path, FALSE - useful to find only by slug
	 * @param integer Item Type ID to check what categories can be allowed for cross-posting
	 * @return object|NULL Chapter object
	 */
	function & get_Chapter( $cat_folder_path, $check_full_path = true, $item_Type_ID = NULL )
	{
		if( isset( $this->chapters_by_path[ $cat_folder_path ] ) )
		{	// Get Chapter from cache:
			return $this->chapters_by_path[ $cat_folder_path ];
		}

		global $DB, $Settings;

		$cat_full_url_path = explode( '/', $cat_folder_path );
		foreach( $cat_full_url_path as $c => $cat_slug )
		{	// Convert title text to slug format:
			$cat_full_url_path[ $c ] = get_urltitle( $cat_slug );
		}
		// Get base of url name without numbers at the end:
		$cat_urlname_base = preg_replace( '/-\d+$/', '', $cat_full_url_path[ count( $cat_full_url_path ) - 1 ] );

		$SQL = new SQL( 'Find categories by path "'.implode( '/', $cat_full_url_path ).'/"' );
		$SQL->SELECT( 'cat_ID' );
		$SQL->FROM( 'T_categories' );
		if( $Settings->get( 'cross_posting' ) && $item_Type_ID !== NULL )
		{	// Select categories from all possible collections where cross-posting is allowed between the imported collection:
			$SQL->FROM_add( 'INNER JOIN T_items__type_coll ON itc_coll_ID = cat_blog_ID' );
			$SQL->WHERE_and( 'itc_ityp_ID = '.$DB->quote( $item_Type_ID ) );
		}
		else
		{	// Select categories only from the imported collection because cross-posting is not allowed in system:
			$SQL->WHERE( 'cat_blog_ID = '.$DB->quote( $this->coll_ID ) );
		}
		$SQL->WHERE_and( 'cat_urlname REGEXP '.$DB->quote( '^('.$cat_urlname_base.')(-[0-9]+)?$' ) );
		$cat_IDs = $DB->get_col( $SQL );

		$r = NULL;
		$ChapterCache = & get_ChapterCache();
		foreach( $cat_IDs as $cat_ID )
		{
			if( $Chapter = & $ChapterCache->get_by_ID( $cat_ID, false, false ) )
			{
				$full_match = true;
				if( $check_full_path )
				{	// Check full path:
					$cat_curr_url_path = explode( '/', substr( $Chapter->get_url_path(), 0 , -1 ) );
					foreach( $cat_full_url_path as $c => $cat_full_url_folder )
					{
						// Decide slug is same without number at the end:
						if( ! isset( $cat_curr_url_path[ $c ] ) ||
								! preg_match( '/^'.preg_quote( $cat_full_url_folder, '/' ).'(-\d+)?$/', $cat_curr_url_path[ $c ] ) )
						{
							$full_match = false;
							break;
						}
					}
				}
				if( $full_match )
				{	// We found category with same full url path:
					$r = $Chapter;
					break;
				}
			}
		}

		$this->chapters_by_path[ $cat_folder_path ] = $r;
		return $r;
	}


	/**
	 * Get Item by slug in given Collection if option "If filename/slug exists in a different collection" = "Create a new slug like xyz-1"
	 * OR Skip Item by log error and return FALSE if the option = "Display error and skip the .md file"
	 *
	 * @param string Item slug
	 * @return object|NULL|FASLE Item object,
	 *                           NULL - if Item is not found and we can create new,
	 *                           FALSE - if Item must be skipped because it exists in another collection
	 */
	function & get_Item( $item_slug )
	{
		global $DB;

		if( $this->get_option( 'slug_diff_coll' ) == 'create' )
		{	// Try to find Item by slug with suffix like "-123" in the requested Collection:
			$item_slug_base = preg_replace( '/-\d+$/', '', $item_slug );
			$sql_title = 'Find Item by slug base "'.$item_slug_base.'" in the Collection #'.$this->coll_ID;
		}
		else
		{	// Try to find Item by slug in ALL Collections:
			$sql_title = 'Find Item by slug "'.$item_slug.'" in ALL Collections';
		}
		$SQL = new SQL( $sql_title );
		$SQL->SELECT( 'post_ID, cat_blog_ID' );
		$SQL->FROM( 'T_slug' );
		$SQL->FROM_add( 'INNER JOIN T_items__item ON post_ID = slug_itm_ID AND slug_type = "item"' );
		$SQL->FROM_add( 'INNER JOIN T_categories ON cat_ID = post_main_cat_ID' );
		if( $this->get_option( 'slug_diff_coll' ) == 'create' )
		{ // Create a new slug like xyz-1 if filename/slug exists in a different collection:
			$SQL->WHERE( 'cat_blog_ID = '.$DB->quote( $this->coll_ID ) );
			$SQL->WHERE_and( 'slug_title REGEXP '.$DB->quote( '^'.$item_slug_base.'(-[0-9]+)?$' ) );
		}
		else
		{	// Display error and skip the .md file 
			$SQL->WHERE( 'slug_title = '.$DB->quote( $item_slug ) );
		}
		$SQL->ORDER_BY( 'slug_title' );
		$SQL->LIMIT( '1' );
		$post_data = $DB->get_row( $SQL );

		if( ! empty( $post_data ) )
		{	// Item is found by slug
			$post_coll_ID = $post_data->cat_blog_ID;
			// Initalize a found Item by ID:
			$post_ID = $post_data->post_ID;
			$ItemCache = & get_ItemCache();
			if( $Item = & $ItemCache->get_by_ID( $post_ID, false, false ) )
			{	// If Item is found by ID

				// Get URL to edit the Item:
				$edit_item_url = $Item->get_edit_url( array(
					'force_backoffice_editing' => true, // Use back-office edit item url
					'save_context'             => false, // Don't append param "redirect_to"
					// Don't check permission because this link must be displayed even
					// when import is called from CLI mode without logged in admin:
					'check_perm'               => false,
				) );

				// Display details of Item that will be updated:
				// TODO: probably we need the same info for new creating Item,
				//       then this log will be moved outside of this function,
				//       after we call Item->dbinsert()/Item->dbupdate()
				$this->log( '<br/>&nbsp; &nbsp; Details: '
					.'<i>slug=</i>'
					.'<a href="'.$Item->get_permanent_url().'" target="_blank"><code>'
						.$item_slug
					.'</code></a> '
					.'<i>post_ID=</i>'
					.'<a href="'.$edit_item_url.'" title="'.format_to_output( TB_('Edit this item...'), 'htmlattr' ).'" target="_blank"><b>'
						.$post_ID
					.'</b></a>' );

				if( $this->get_option( 'slug_diff_coll' ) == 'skip' &&
						$post_coll_ID != $this->coll_ID )
				{	// Skip Item with same slug in another Collection:
					$BlogCahe = get_BlogCache();
					$another_Blog = & $BlogCahe->get_by_ID( $post_coll_ID, false, false );
					$this->log_list( sprintf( 'Skip this %s file because Item %s already exists with same slug in collection %s!',
							'<code>.md</code>',
							// Link to another Item with same slug:
							'<a href="'.$Item->get_permanent_url().'" target="_blank">'.$Item->get( 'title' ).'</a> '
								.'(<a href="'.$edit_item_url.'" title="'.format_to_output( TB_('Edit this item...'), 'htmlattr' ).'" target="_blank">'.$Item->ID.'</a>)',
							// Link to another Collection where Item is found by same slug:
							( $another_Blog ? '<a href="'.$another_Blog->get( 'url' ).'" target="_blank">'.$another_Blog->get( 'name' ).'</a>' : '#'.$post_coll_ID )
						), 'error' );
					// Return FALSE in order to know we found Item by slug but in another Collection:
					$r = false;
					return $r;
				}

				// Return found Item:
				return $Item;
			}
		}

		// Return NULL in order to know we don't find Item by slug:
		$r = NULL;
		return $r;
	}


	/**
	 * Callback function to Convert Markdown links to b2evolution ShortLinks
	 *
	 * @param array Match data
	 * @return string Link in b2evolution ShortLinks format
	 */
	function callback_convert_links( $m )
	{
		$link_title = trim( $m[2] );
		$link_url = isset( $m[3] ) ? trim( $m[3] ) : '';

		if( $link_url === '' )
		{	// URL must be defined:
			$this->log_linked_file( 'error_link', $m[0] );
			return $m[0];
		}

		if( ! empty( $m[5] ) )
		{	// Use full URL because this is URL with protocol like http://
			$item_url = $m[3];
			// Anchor is already included in the $m[3]:
			$link_anchor = '';
		}
		elseif( isset( $m[8] ) && $m[8] === '.md' )
		{	// Extract item slug from relative URL of md file:
			$item_url = get_urltitle( $m[7] );
			$item_slug = $item_url;
			$link_anchor = isset( $m[9] ) ? trim( $m[9], '# ' ) : '';
		}
		elseif( strpos( $m[7], '#' ) === 0 && strlen( $m[7] ) > 1 )
		{	// This is anchor URL to current post:
			$item_url = '';
			$link_anchor = substr( $m[7], 1 );
		}
		else
		{	// We cannot convert this markdown link:
			$this->log_linked_file( ( isset( $m[8] ) && in_array( strtolower( substr( $m[8], 1 ) ), array( 'png', 'gif', 'jpg', 'jpeg', 'svg' ) ) ? 'error_image' : 'error_link' ), $m[0] );
			return $m[0];
		}

		if( $this->get_option( 'check_links' ) &&
		    isset( $item_slug ) &&
		    ( $ItemCache = & get_ItemCache() ) &&
		    ( $slug_Item = $ItemCache->get_by_urltitle( $item_slug, false, false ) ) )
		{	// Check internal link (slug) to see if it links to a page of the same language:
			if( $slug_Item->get( 'locale' ) != $this->current_item_locale )
			{	// Different locale:
				$this->log_linked_file( 'check', $m[0], $slug_Item->get_title( array( 'link_type' => 'admin_view' ) ), $slug_Item->get( 'locale' ) );
				if( $this->get_option( 'diff_lang_suggest' ) )
				{	// Find and suggest equivalent from "linked languages/versions" table:
					if( $version_Item = & $slug_Item->get_version_Item( $this->current_item_locale, false ) )
					{	// We found a version Item with required locale:
						$version_item_link = $version_Item->get_title( array( 'link_type' => 'admin_view' ) );
						$this->log_linked_file( 'recommend', NULL, $version_item_link );
						if( $this->get_option( 'same_lang_replace_link' ) )
						{	// Replace the link slug in the post:
							$item_url = $version_Item->get( 'urltitle' );
							$this->log_linked_file( 'content', $m[0], $version_item_link );
							if( $this->get_option( 'same_lang_update_file' ) )
							{	// Update md file with new replaced links:
								$updated_link = str_replace( $m[7].'.md', $version_Item->get( 'urltitle' ).'.md', $m[0] );
								$this->set_item_file_content( str_replace( $m[0], $updated_link, $this->item_file_content ) );
							}
						}
					}
				}
			}
		}

		return $m[1] // Suffix like space or new line before link
			.( substr( $m[2], 0, 1 ) === ' ' ? ' ' : '' ) // space before link text inside []
			.'(('.$item_url
			.( empty( $link_anchor ) ? '' : '#'.$link_anchor )
			.( empty( $link_title ) ? '' : ' '.$link_title ).'))';
	}


	/**
	 * Get available image extensions
	 *
	 * @return string Image extensions separated by |
	 */
	function get_image_extensions()
	{
		if( ! isset( $this->image_extensions ) )
		{	// Load image extensions from DB into cache string:
			global $DB;
			$SQL = new SQL( 'Get available image extensions' );
			$SQL->SELECT( 'ftyp_extensions' );
			$SQL->FROM( 'T_filetypes' );
			$SQL->WHERE( 'ftyp_viewtype = "image"' );
			$this->image_extensions = str_replace( ' ', '|', implode( ' ', $DB->get_col( $SQL ) ) );
		}

		return $this->image_extensions;
	}


	/**
	 * Log message to report about importing Linked File
	 *
	 * @param string Type
	 * @param string Tag data
	 * @param string Info link
	 * @param string Locale of Item found by slug
	 */
	function log_linked_file( $type, $tag, $info_link = NULL, $slug_item_locale = NULL )
	{
		switch( $type )
		{
			case 'error_link':
			case 'error_image':
				$this->log_list( sprintf( 'Markdown link %s could not be convered to b2evolution ShortLink.', '<code>'.$tag.'</code>' ), 'error' );
				if( $type == 'error_image' )
				{	// Special warning when URL to image is used in link markdown tag:
					$this->log_list( 'The above is a markdown link to an image file. Did you forget the <code>!</code> in order to make it an image inclusion, rather than a link?', 'warning' );
				}
				break;
			case 'check':
				$this->log_list( sprintf( 'Link %s points to "%s" which is in %s instead of %s.',
						'<code>'.$tag.'</code>',
						$info_link,
						'<code>'.$slug_item_locale.'</code>',
						'<code>'.$this->current_item_locale.'</code>'
					), 'warning' );
				break;
			case 'recommend':
				$this->log_list( sprintf( 'We recommend "%s" (%s) as destination.',
						$info_link,
						'<code>'.$this->current_item_locale.'</code>'
					), 'warning' );
				break;
			case 'content':
				$this->item_content_is_updated_by_linked_file = true; // Flag to know we should update Item content
				$this->log_list( 'We will update the content accordingly.', 'warning' );
				break;
		}
	}


	/**
	 * Set Item title from YAML data
	 *
	 * @param string Value
	 * @param object Item (by reference)
	 */
	function set_yaml_title( $value, & $Item )
	{
		$Item->set( 'titletag', utf8_substr( $value, 0, 255 ) );
	}


	/**
	 * Set Item meta description from YAML data
	 *
	 * @param string Value
	 * @param object Item (by reference)
	 */
	function set_yaml_description( $value, & $Item )
	{
		$Item->set_setting( 'metadesc', $value );
	}


	/**
	 * Set Item meta keywords from YAML data
	 *
	 * @param string Value
	 * @param object Item (by reference)
	 */
	function set_yaml_keywords( $value, & $Item )
	{
		$Item->set_setting( 'metakeywords', $value );
	}


	/**
	 * Set Item content excerpt from YAML data
	 *
	 * @param string Value
	 * @param object Item (by reference)
	 */
	function set_yaml_excerpt( $value, & $Item )
	{
		$Item->set( 'excerpt', $value, true );
		$Item->set( 'excerpt_autogenerated', 0 );
	}


	/**
	 * Set Item short title from YAML data
	 *
	 * @param string Value
	 * @param object Item (by reference)
	 */
	function set_yaml_short_title( $value, & $Item )
	{
		$Item->set( 'short_title', utf8_substr( $value, 0, 50 ) );
	}


	/**
	 * Set Item tags from YAML data
	 *
	 * @param string|array Value
	 * @param object Item (by reference)
	 */
	function set_yaml_tags( $value, & $Item )
	{
		if( ! $this->check_yaml_array( 'tags', $value, true, true ) )
		{	// Skip wrong data:
			// Don't print error messages here because all messages are initialized inside $this->check_yaml_array().
			return;
		}

		if( $value === '' ||  $value === array() )
		{	// Clear tags:
			$Item->set_tags_from_string( '' );
		}
		else
		{	// Set new tags:
			$Item->set_tags_from_string( is_array( $value )
				// Set tags from array:
				? implode( ',', $value )
				// Set tags from string separated by comma:
				: preg_replace( '#,\s+#', ',', $value ) );
		}
	}


	/**
	 * Set Item extra-categories from YAML data
	 *
	 * @param array Value
	 * @param object Item (by reference)
	 */
	function set_yaml_extra_cats( $value, & $Item )
	{
		if( ! $this->check_yaml_array( 'extra-cats', $value ) )
		{	// Skip wrong data:
			// Don't print error messages here because all messages are initialized inside $this->check_yaml_array().
			return;
		}

		// Get current extra-categories:
		if( isset( $Item->extra_cat_IDs ) )
		{	// Clear to be sure all etra categories are loaded from DB:
			unset( $Item->extra_cat_IDs );
		}
		$old_extra_cat_IDs = $Item->get( 'extra_cat_IDs' );

		// Get extra-categories from YAML block:
		$extra_cat_IDs = array();
		$specified_yaml_message = ' ('.TB_('specified in YAML block').')';
		foreach( $value as $extra_cat_slug )
		{
			if( $extra_Chapter = & $this->get_Chapter( $extra_cat_slug, ( strpos( $extra_cat_slug, '/' ) !== false ), $Item->get( 'ityp_ID' ) ) )
			{	// Use only existing category:
				$extra_cat_IDs[] = $extra_Chapter->ID;
				// Inform about new or already assigned extra-category:
				$cat_message = ( in_array( $extra_Chapter->ID, $old_extra_cat_IDs ) ? TB_('Extra category already assigned: %s') : TB_('Assigned new extra-category: %s') );
				$cross_posted_message = ( $extra_Chapter->get( 'blog_ID' ) != $this->coll_ID ? ' <b>'.TB_('Cross-posted').'</b>' : '' );
				$this->log_list( sprintf( $cat_message, $extra_Chapter->get_permanent_link().$specified_yaml_message.$cross_posted_message ), 'info' );
			}
			else
			{	// Display error on not existing category:
				$this->log_list( sprintf( TB_('Skipping extra-category %s because it doesn\'t exist.'), '<code>'.$extra_cat_slug.'</code>'.$specified_yaml_message ), 'error' );
			}
		}

		// Get old extra-categories existing in DB but not in YAML:
		$ChapterCache = & get_ChapterCache();
		$only_db_extra_cat_IDs = array_diff( $old_extra_cat_IDs, $extra_cat_IDs );
		// Load all old categories in single SQL query:
		$ChapterCache->load_list( $only_db_extra_cat_IDs );
		$only_db_extra_cat_links = array();
		$only_db_extra_cat_slugs = array();
		foreach( $only_db_extra_cat_IDs as $o => $only_db_extra_cat_ID )
		{
			if( $only_db_extra_Chapter = & $ChapterCache->get_by_ID( $only_db_extra_cat_ID, false, false ) )
			{	// Get link to Category if it is found in DB:
				$only_db_extra_cat_link = $only_db_extra_Chapter->get_permanent_link().( $only_db_extra_Chapter->get( 'blog_ID' ) != $this->coll_ID ? '(<b>'.TB_('Cross-posted').'</b>)' : '' );;
				if( $this->get_option( 'extra_cat_only_in_db' ) == 'unassign' &&
				    $only_db_extra_Chapter->ID == $Item->get( 'main_cat_ID' ) )
				{	// Main category cannot be un-assigned, however it must be stored together with extra-categories in DB table:
					$extra_cat_IDs[] = $only_db_extra_Chapter->ID;
					unset( $only_db_extra_cat_IDs[ $o ] );
					$this->log_list( sprintf( 'Keeping MAIN category in DB: %s although it is NOT in YAML.', $only_db_extra_cat_link ), 'warning' );
					continue;
				}
				$only_db_extra_cat_links[] = $only_db_extra_cat_link;
				if( $this->get_option( 'extra_cat_only_in_db' ) == 'add_yaml' )
				{	// We need slugs in order to append them in YAML block:
					$only_db_extra_cat_slugs[] = $only_db_extra_Chapter->get( 'urlname' );
				}
			}
			else
			{	// Unset wrong extra-category because it is not found in DB:
				unset( $only_db_extra_cat_IDs[ $o ] );
				// This cannot happens for normal DB structure but inform about such died extra-categories:
				$this->log_list( sprintf( 'Un-assign old extra-category #%d because is not found in DB and YAML.', intval( $only_db_extra_cat_ID ) ), 'error' );
			}
		}

		if( ! empty( $only_db_extra_cat_IDs ) )
		{	// If extra-categories are found in DB but not in YAML:
			switch( $this->get_option( 'extra_cat_only_in_db' ) )
			{
				case 'keep_db':
					// Display WARNING and do not resolve:
					$this->log_list( sprintf( 'Keeping existing extra-category in DB: %s although it is NOT in YAML.', implode( ', ', $only_db_extra_cat_links ) ), 'warning' );
					// Keep old extra-category from DB for the importing Item:
					$extra_cat_IDs = array_merge( $extra_cat_IDs, $only_db_extra_cat_IDs );
					break;
				case 'add_yaml':
					// Resolve by adding extra-categories to YAML in .md file on disk:
					$this->log_list( sprintf( 'Writing <code>extra-cats:</code> to YAML: %s', implode( ', ', $only_db_extra_cat_links ) ), 'warning' );
					// Keep old extra-categories from DB for the importing Item:
					$extra_cat_IDs = array_merge( $extra_cat_IDs, $only_db_extra_cat_IDs );
					// Add extra-categories from DB into YAML block:
					$this->set_item_file_yaml_field( 'extra-cats', array_merge( $value, $only_db_extra_cat_slugs ) );
					break;
				case 'unassign':
					// Resolve by deleting from DB:
					$this->log_list( sprintf( 'Un-assigning old extra-category: %s because it is NOT in YAML.', implode( ', ', $only_db_extra_cat_links ) ), 'info' );
					// Set flag to know Item must be updated in order to un-assign old extra-categories:
					$this->item_yaml_is_updated = true;
					// Don't append old extra-categories to categories from YAML block so on item update they will be un-assigned.
					break;
			}
		}

		$Item->set( 'extra_cat_IDs', $extra_cat_IDs );
	}


	/**
	 * Set Item order from YAML data
	 *
	 * @param array Value
	 * @param object Item (by reference)
	 */
	function set_yaml_order( $value, & $Item )
	{
		if( ! preg_match( '#^-?[0-9]*(\.[0-9]+)?$#', $value ) )
		{	// Order value must be a decimal number:
			$this->log_list( sprintf( 'Wrong value %s in yaml field %s.', '<code>'.$value.'</code>', '<code>order</code>' ), 'error' );
			return;
		}

		// Set same order per each category of the Item:
		$Item->set( 'order', $value );
		$this->log_list( sprintf( 'Use order %s from yaml field %s for all categories of the Item.', '<code>'.$value.'</code>', '<code>order</code>' ), 'info' );
	}


	/**
	 * Set Item Type from YAML data
	 *
	 * @param array Value
	 * @param object Item (by reference)
	 */
	function set_yaml_item_type( $value, & $Item )
	{
		if( $value === '' )
		{	// Skip empty Item Type name
			$this->log_list( sprintf( 'Skip empty yaml field %s.', '<code>item-type</code>' ), 'warning' );
			return;
		}

		$ItemTypeCache = & get_ItemTypeCache();
		if( ! ( $ItemType = & $ItemTypeCache->get_by_name( $value, false, false ) ) )
		{	// Skip unknown Item Type:
			$this->log_list( sprintf( 'Not found Item Type %s for yaml field %s.', '"'.$value.'"', '<code>item-type</code>' ), 'error' );
			return;
		}

		if( ! $ItemType->is_enabled( $Item->get_blog_ID() ) )
		{	// Skip not enabled Item Type:
			$this->log_list( sprintf( 'Cannot use Item Type %s from yaml field %s because it is not enabled for the collection.', '"'.$value.'"', '<code>item-type</code>' ), 'error' );
			return;
		}

		// Set Item Type:
		$Item->set( 'ityp_ID', $ItemType->ID );
		$this->log_list( sprintf( 'Use Item Type %s from yaml field %s.', '"'.$value.'"', '<code>item-type</code>' ), 'info' );
	}


	/**
	 * Check YAML data array
	 * 
	 * @param string YALM field name
	 * @param array|string YALM field value
	 * @param boolean TRUE to allow string for the YAML field
	 * @param boolean TRUE to allow empty value for the YAML field
	 * @return boolean TRUE - correct data, FALSE - wrong data
	 */
	function check_yaml_array( $field_name, $field_value, $allow_string_format = false, $allow_empty = false )
	{
		if( ! $allow_empty )
		{	// Check for not empty value:
			if( ( $allow_string_format && $field_value === '' ) ||
					( $field_value === array() ) )
			{	// Skip empty yaml field:
				$this->log_list( sprintf( TB_('Skipping YAML field %s, because it is empty.'), '<code>'.$field_name.'</code>' ), 'warning' );
				return false;
			}
		}

		if( $allow_string_format && is_string( $field_value ) )
		{	// Don't check array if the YAML field is allowed to be a string:
			return true;
		}

		if( ! is_array( $field_value ) )
		{	// Wrong not array data:
			$this->log_list( sprintf( TB_('Skipping YAML field %s, because it is not an array.'), '<code>'.$field_name.'</code>' ), 'error' );
			return false;
		}

		foreach( $field_value as $string )
		{
			if( is_array( $string ) )
			{	// Skip wrong indented data:
				$this->log_list( sprintf( TB_('Skipping YAML field %s, because it is wrongly indented.'), '<code>'.$field_name.'</code>' ), 'error' );
				return false;
			}
		}

		return true;
	}


	/**
	 * Additional actions after import is done
	 *
	 * Useful for extended classes
	 */
	function event_after_import()
	{
	}


	/**
	 * Set new content for item *.md file
	 *
	 * @param string New content
	 */
	function set_item_file_content( $new_content )
	{
		if( $this->item_file_content != $new_content )
		{	// Update item content only when content is really changed:
			$this->item_file_content = $new_content;
			// Set flag to know the item content was updated:
			$this->item_file_is_updated = true;
		}
	}


	/**
	 * Set value for YAML field in content of item *.md file
	 *
	 * @param string YAML field name
	 * @param array New field values
	 */
	function set_item_file_yaml_field( $field_name, $new_field_value )
	{
		$this->set_item_file_content( preg_replace(
				'#^(---.+?'.preg_quote( $field_name, '#' ).':)(\s+.+?\n)([a-z\-]+:.+?)?---#is',
				'$1'."\n  - ".implode( "\n  - ", array_unique( $new_field_value ) )."\n".'$3---',
				$this->item_file_content
			) );
		// Set flag in order to know YAML block is updated in .md file:
		$this->item_yaml_is_updated = true;
	}


	/**
	 * Display process of importing
	 */
	function display_import()
	{
		// Start to log:
		$this->start_log();

		$this->log( '<p style="margin-bottom:0">' );

		if( preg_match( '/\.zip$/i', $this->source ) )
		{	// ZIP archive:
			$this->log( '<b>'.TB_('Source ZIP').':</b> <code>'.$this->source.'</code><br />' );
			$this->log( '<b>'.TB_('Unzipping').'...</b> '.( $this->unzip() ? TB_('OK').'<br />' : '' ) );
		}
		else
		{	// Folder:
			$this->log( '<b>'.TB_('Source folder').':</b> <code>'.$this->source.'</code><br />' );
		}
		if( $this->get_data( 'errors' ) !== false )
		{	// Display errors:
			$this->log( $this->get_data( 'errors' ) );
		}

		$import_Blog = & $this->get_Blog();
		$this->log( '<b>'.TB_('Destination collection').':</b> '.$import_Blog->dget( 'shortname' ).' &ndash; '.$import_Blog->dget( 'name' ).'<br />' );
		$this->log( '<b>'.TB_('Mode').':</b> '
			.( isset( $this->options_defs['import_type']['options'][ $this->get_option( 'import_type' ) ] )
				? $this->options_defs['import_type']['options'][ $this->get_option( 'import_type' ) ]['title']
				: '<b class="red">Unknown mode!</b>' ) );
		$this->log( '</p>' );
		$selected_options = array();
		foreach( $this->options_defs as $option_key => $option )
		{
			if( $option['group'] != 'options' )
			{	// Skip option from different group:
				continue;
			}
			if( $this->get_option( $option_key ) )
			{
				$selected_options[ $option_key ] = array(
						// Option title and note:
						( empty( $option['disabled'] ) ? $option['title'] : '<span class="grey">'.$option['title'].'</span>' )
							.( isset( $option['note'] ) ? ' <span class="note">'.$option['note'].'</span>' : '' ),
						// Indent value:
						isset( $option['indent'] ) ? $option['indent'] : 0
					);
			}
		}
		if( $selected_options_count = count( $selected_options ) )
		{
			$this->log( '<b>'.TB_('Options').':</b> ' );
			if( $selected_options_count == 1 )
			{
				$this->log( $selected_options[0] );
			}
			else
			{
				$this->log( '<ul class="list-default" style="margin-bottom:0">' );
				foreach( $selected_options as $option_key => $option )
				{
					$this->log( '<li'.( $option[1] ? ' style="margin-left:'.( $option[1] * 10 ).'px"' : '' ).'>'.$option[0].'</li>' );
				}
				$this->log( '</ul>' );
			}
		}
		// Radio options:
		foreach( $this->options_defs as $option_key => $option )
		{
			if( $option['group'] == 'radio' && $this->get_option( $option_key ) )
			{	// Display here only radio options:
				$this->log( '<b>'.$option['title'].':</b> '.$option['options'][ $this->get_option( $option_key ) ]['label'].'<br>' );
			}
		}
		$this->log( '<br>' );

		if( $this->get_data( 'errors' ) === false )
		{	// Import the data and display a report on the screen:
			$this->execute();
		}
		else
		{	// Display errors if import cannot be done:
			$this->log( '<p class="text-danger">'.TB_('Import failed.').'</p>' );
		}

		// End log:
		$this->end_log();
	}
}
?>