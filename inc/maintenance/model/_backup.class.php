<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @package maintenance
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @var strings base application paths
 */
global $basepath, $conf_subdir, $skins_subdir, $adminskins_subdir;
global $plugins_subdir, $media_subdir, $backup_subdir, $upgrade_subdir;

/**
 * @var array backup paths
 */
global $backup_paths;

/**
 * @var array backup exclude paths
 */
global $backup_exclude_folders;

/**
 * @var array backup tables
 */
global $backup_tables;


/**
 * Backup folder/files default settings
 * - 'label' checkbox label
 * - 'note' checkbox note
 * - 'path' path to folder or file
 * - 'included' true if folder or file must be in backup
 * @var array
 */
$backup_paths = array(
	'application_files'   => array(
		'label'    => TB_('Application files'), /* It is files root. Please, don't remove it. */
		'path'     => '*',
		'included' => true ),

	'configuration_files' => array(
		'label'    => TB_('Configuration files'),
		'path'     => $conf_subdir,
		'included' => true ),

	'skins_files'         => array(
		'label'    => TB_('Skins'),
		'path'     => array( $skins_subdir,
							$adminskins_subdir ),
		'included' => true ),

	'plugins_files'       => array(
		'label'    => TB_('Plugins'),
		'path'     => $plugins_subdir,
		'included' => true ),

	'media_files'         => array(
		'label'    => TB_('Media folder'),
		'path'     => $media_subdir,
		'included' => false ),

	'backup_files'        => array(
		'label'    => NULL,		// Don't display in form. Just exclude from backup.
		'path'     => $backup_subdir,
		'included' => false ),

	'upgrade_files'        => array(
		'label'    => NULL,		// Don't display in form. Just exclude from backup.
		'path'     => $upgrade_subdir,
		'included' => false ) );


/**
 * Exclude the backup folder/files default settings
 * - 'path' Folders name
 * - 'excluded' true if folder must be excluded from backup by default
 * @var array
 */
$backup_exclude_folders = array(
	'_cache' => array(
		'path' => array( '_cache' ),
		'excluded' => true ),

	'cache' => array(
		'path'     => array( '_evocache', '.evocache' ),
		'excluded' => true ),

	'version_control' => array(
		'path'     => array( '.svn', '.git', '.cvs' ),
		'excluded' => true ),
	);

/**
 * Backup database tables default settings
 * - 'label' checkbox label
 * - 'note' checkbox note
 * - 'tables' tables list
 * - 'included' true if database tables must be in backup
 * @var array
 */
$backup_tables = array(
	'content_tables'      => array(
		'label'    => TB_('Content tables'), /* It means collection of all of the tables. Please, don't remove it. */
		'table'   => '*',
		'included' => true ),

	'logs_stats_tables'   => array(
		'label'    => TB_('Logs & stats tables'),
		'table'   => array(
			'T_email__log',
			'T_hitlog',
			'T_sessions',
			'T_track__goalhit',
			'T_track__keyphrase',
			'T_syslog',
		),
		'included' => false ) );


/**
 * Backup class
 * This class is responsible to backup application files and data.
 *
 */
class Backup
{
	/**
	 * All of the paths and their 'included' values defined in backup configuration file
	 * @var array
	 */
	var $backup_paths;

	/**
	 * All of the excluded folders and their 'included' values defined in backup configuration file
	 * @var array
	 */
	var $exclude_folders;

	/**
	 * Ignore files and folders listed in "conf/backup_ignore.conf"
	 * @var boolean
	 */
	var $ignore_config = true;

	/**
	 * All of the tables and their 'included' values defined in backup configuration file
	 * @var array
	 */
	var $backup_tables;

	/**
	 * Add "CREATE TABLE" statements for ALL tables
	 * @var boolean
	 */
	var $backup_db_structure = true;

	/**
	 * Add "DROP TABLE IF EXISTS" before every "CREATE TABLE"
	 */
	var $drop_table_first = true;

	/**
	 * True if pack backup files
	 * @var boolean
	 */
	var $pack_backup_files;


	/**
	 * Constructor
	 */
	function __construct()
	{
		global $backup_paths, $backup_exclude_folders, $backup_tables;

		// Set default settings defined in backup configuration file

		// Set backup folders/files default settings
		$this->backup_paths = array();
		foreach( $backup_paths as $name => $settings )
		{
			$this->backup_paths[$name] = $settings['included'];
		}

		// Set backup exclude folders default settings
		$this->exclude_folders = array();
		foreach( $backup_exclude_folders as $name => $settings )
		{
			$this->exclude_folders[$name] = $settings['excluded'];
		}

		// Set backup tables default settings
		$this->backup_tables = array();
		foreach( $backup_tables as $name => $settings )
		{
			$this->backup_tables[$name] = $settings['included'];
		}

		$this->pack_backup_files = true;
	}


	/**
	 * Load settings from request
	 *
	 * @param boolean TRUE to memorize all params
	 */
	function load_from_Request( $memorize_params = false )
	{
		global $backup_paths, $backup_exclude_folders, $backup_tables, $Messages;

		// Load folders/files settings from request
		foreach( $backup_paths as $name => $settings )
		{
			if( array_key_exists( 'label', $settings ) && !is_null( $settings['label'] ) )
			{	// We can set param
				$this->backup_paths[$name] = param( 'bk_'.$name, 'boolean', 0, $memorize_params );
			}
		}

		// Load the excluded folders settings from request:
		foreach( $backup_exclude_folders as $name => $settings )
		{
			$this->exclude_folders[$name] = param( 'exclude_bk_'.$name, 'boolean', 0, $memorize_params );
		}

		$this->ignore_config = param( 'ignore_bk_config', 'boolean', 0, $memorize_params );

		// Load tables settings from request
		foreach( $backup_tables as $name => $settings )
		{
			$this->backup_tables[$name] = param( 'bk_'.$name, 'boolean', 0, $memorize_params );
		}

		$this->backup_db_structure = param( 'db_structure', 'boolean', false, $memorize_params );

		$this->drop_table_first = param( 'drop_table_first', 'boolean', false, $memorize_params );

		$this->pack_backup_files = param( 'bk_pack_backup_files', 'boolean', 0, $memorize_params );

		// Check are there something to backup
		if( ! $this->has_included( $this->backup_paths ) &&
		    ! $this->has_included( $this->backup_tables ) &&
		    ! $this->backup_db_structure )
		{
			$Messages->add( TB_('You have not selected anything to backup.'), 'error' );
			return false;
		}

		return true;
	}


	/**
	 * Start backup
	 */
	function start_backup()
	{
		global $basepath, $backup_path, $servertimenow;

		// Create current backup path
		$cbackup_path = $backup_path.date( 'Y-m-d-H-i-s', $servertimenow ).'/';

		echo '<p>'.sprintf( TB_('Starting backup to: &laquo;%s&raquo; ...'), $cbackup_path ).'</p>';
		evo_flush();

		// Prepare backup directory
		$success = prepare_maintenance_dir( $backup_path, true );

		// Backup directories and files
		if( $success && $this->has_included( $this->backup_paths ) )
		{
			$backup_files_path = $this->pack_backup_files ? $cbackup_path : $cbackup_path.'www/';

			// Prepare files backup directory
			if( $success = prepare_maintenance_dir( $backup_files_path, false ) )
			{	// We can backup files
				$success = $this->backup_files( $backup_files_path );
			}
		}

		// Backup database
		if( $success && ( $this->has_included( $this->backup_tables ) || $this->backup_db_structure ) )
		{
			// Prepare database backup directory
			if( $success = prepare_maintenance_dir( $cbackup_path, false ) )
			{	// We can backup database
				$success = $this->backup_database( $cbackup_path );
			}
		}

		if( $success )
		{
			echo '<p>'.sprintf( TB_('Backup complete. Directory: &laquo;%s&raquo;'), $cbackup_path ).'</p>';
			evo_flush();

			return true;
		}

		@rmdir_r( $cbackup_path );
		return false;
	}


	/**
	 * Backup files
	 * @param string backup directory path
	 */
	function backup_files( $backup_dirpath )
	{
		global $basepath, $backup_paths, $backup_exclude_folders, $inc_path, $Settings;

		echo '<h4>'.TB_('Creating folders/files backup...').'</h4>';
		evo_flush();

		// Find included and excluded files

		$included_files = array();

		if( $root_included = $this->backup_paths['application_files'] )
		{
			$filename_params = array(
					'recurse'        => false,
					'basename'       => true,
					'trailing_slash' => true,
					//'inc_evocache' => true, // Uncomment to backup ?evocache directories
					'inc_temp'       => false,
				);
			$included_files = get_filenames( $basepath, $filename_params );
		}

		// Prepare included/excluded paths
		$excluded_files = array();

		foreach( $this->backup_paths as $name => $included )
		{
			foreach( $this->path_to_array( $backup_paths[$name]['path'] ) as $path )
			{
				if( $root_included && !$included )
				{
					$excluded_files[] = $path;
				}
				elseif( !$root_included && $included )
				{
					$included_files[] = $path;
				}
			}
		}

		$backup_current_exclude_folders = array();
		foreach( $this->exclude_folders as $name => $excluded )
		{
			if( $excluded )
			{
				foreach( $this->path_to_array( $backup_exclude_folders[$name]['path'] ) as $name )
				{
					// Exclude root folder with name:
					$excluded_files[] = $name.'/';
					// Exclude all subfolders with name:
					$backup_current_exclude_folders[] = $name;
				}
			}
		}

		if( $this->ignore_config )
		{	// Ignore files and folders listed in "conf/backup_ignore.conf":
			global $conf_path;
			$backup_ignore_file = $conf_path.'backup_ignore.conf';
			if( file_exists( $backup_ignore_file ) && is_readable( $backup_ignore_file ) )
			{
				$backup_ignore_file_lines = preg_split( '/\r\n|\n|\r/', file_get_contents( $backup_ignore_file ) );
				foreach( $backup_ignore_file_lines as $backup_ignore_file_line )
				{
					// Ignore root folder and file with name:
					$excluded_files[] = trim( $backup_ignore_file_line ).'/';
					$excluded_files[] = trim( $backup_ignore_file_line );
				}
			}
			else
			{
				echo '<p style="color:red">'.sprintf( TB_('Config file %s cannot be read.'), '<b>'.$backup_ignore_file.'</b>' ).'</p>';
				evo_flush();
			}
		}

		// Remove excluded list from included list
		$included_files = array_diff( $included_files, $excluded_files );

		if( $this->pack_backup_files )
		{	// Create ZIPped backup:
			$zip_filepath = $backup_dirpath.'www.zip';

			echo sprintf( TB_('Archiving files to &laquo;<strong>%s</strong>&raquo;...'), $zip_filepath ).'<br/>';
			evo_flush();

			return pack_archive( $zip_filepath, $basepath, $included_files, 'www', $backup_current_exclude_folders );
		}
		else
		{	// Copy directories and files to backup directory
			foreach( $included_files as $included_file )
			{
				$this->recurse_copy( no_trailing_slash( $basepath.$included_file ),
					no_trailing_slash( $backup_dirpath.$included_file ),
					true, $backup_current_exclude_folders );
			}
		}

		return true;
	}


	/**
	 * Backup database
	 *
	 * @param string backup directory path
	 */
	function backup_database( $backup_dirpath )
	{
		global $DB, $db_config, $backup_tables, $inc_path, $Settings;

		echo '<h4>'.TB_('Creating database backup...').'</h4>';
		evo_flush();

		$backup_structure = array();
		$backup_data = array();
		if( $this->backup_db_structure )
		{	// Backup structure of all tables:
			$backup_structure = $DB->get_col( 'SHOW TABLES' );
		}

		// Collect all included tables
		foreach( $this->backup_tables as $name => $included )
		{
			if( $included )
			{
				$tables = aliases_to_tables( $backup_tables[$name]['table'] );
				if( is_array( $tables ) )
				{
					$backup_data = array_merge( $backup_data, $tables );
				}
				elseif( $tables == '*' )
				{
					$backup_data = array_merge( $backup_data, $DB->get_col( 'SHOW TABLES' ) );
				}
				else
				{
					$backup_data[] = $tables;
				}
			}
		}

		// Ensure there are no duplicated tables
		$backup_data = array_unique( $backup_data );
		$backup_structure = array_unique( array_merge( $backup_structure, $backup_data ) );

		// Exclude tables
		foreach( $this->backup_tables as $name => $included )
		{
			if( !$included )
			{
				$tables = aliases_to_tables( $backup_tables[$name]['table'] );
				if( is_array( $tables ) )
				{
					$backup_data = array_diff( $backup_data, $tables );
					if( ! $this->backup_db_structure )
					{
						$backup_structure = array_diff( $backup_structure, $tables );;
					}
				}
				elseif( $tables != '*' )
				{
					$index = array_search( $tables, $backup_data );
					if( $index )
					{
						unset( $backup_data[$index] );
						if( ! $this->backup_db_structure )
						{
							unset( $backup_structure[$index] );
						}
					}
				}
			}
		}

		// Create and save created SQL backup script
		$backup_sql_filename = 'db.sql';
		$backup_sql_filepath = $backup_dirpath.$backup_sql_filename;

		// Check if backup file exists
		if( file_exists( $backup_sql_filepath ) )
		{	// Stop tables backup, because backup file exists
			echo '<p style="color:red">'.sprintf( TB_('Unable to write database dump. Database dump already exists: &laquo;%s&raquo;'), $backup_sql_filepath ).'</p>';
			evo_flush();

			return false;
		}

		$f = @fopen( $backup_sql_filepath , 'w+' );
		if( $f == false )
		{	// Stop backup, because it can't open backup file for writing
			echo '<p style="color:red">'.sprintf( TB_('Unable to write database dump. Could not open &laquo;%s&raquo; for writing.'), $backup_sql_filepath ).'</p>';
			evo_flush();

			return false;
		}

		echo sprintf( TB_('Dumping tables to &laquo;<strong>%s</strong>&raquo;...'), $backup_sql_filepath ).'<br/>';
		evo_flush();

		if( $this->drop_table_first )
		{	// Disable foreign key check to avoid errors due to referencial constraints:
			fwrite( $f, "SET @fkey_check = @@foreign_key_checks;\n" );
			fwrite( $f, "SET FOREIGN_KEY_CHECKS=0;\n\n" );
		}

		// Create and save created SQL backup script
		foreach( $backup_structure as $table )
		{
			// progressive display of what backup is doing
			echo sprintf( TB_('Backing up table &laquo;<strong>%s</strong>&raquo; to SQL file...'), $table );
			evo_flush();

			if( $this->drop_table_first )
			{	// Drop existing table before creating a new one:
				fwrite( $f, 'DROP TABLE IF EXISTS `'.$table."`;\n" );
			}
			$row_table_data = $DB->get_row( 'SHOW CREATE TABLE '.$table, ARRAY_N );
			fwrite( $f, $row_table_data[1].";\n\n" );

			if( in_array( $table, $backup_data ) )
			{	// Dump data of the table:
				$page = 0;
				$page_size = 500;
				$is_insert_sql_started = false;
				$is_first_insert_sql_value = true;
				while( ! empty( $rows ) || $page == 0 )
				{ // Get the records by page(500) in order to save memory and avoid fatal error
					$rows = $DB->get_results( 'SELECT * FROM '.$table.' LIMIT '.( $page * $page_size ).', '.$page_size, ARRAY_N );

					if( $page == 0 && ! $is_insert_sql_started && ! empty( $rows ) )
					{ // Start SQL INSERT clause
						fwrite( $f, 'INSERT INTO '.$table.' VALUES ' );
						$is_insert_sql_started = true;
					}

					foreach( $rows as $row )
					{
						$values = '(';
						$num_fields = count( $row );
						for( $index = 0; $index < $num_fields; $index++ )
						{
							if( isset( $row[$index] ) )
							{
								$row[$index] = str_replace("\n","\\n", addslashes( $row[$index] ) );
								$values .= '\''.$row[$index].'\'' ;
							}
							else
							{ // The $row[$index] value is not set or is NULL
								$values .= 'NULL';
							}

							if( $index<( $num_fields-1 ) )
							{
								$values .= ',';
							}
						}
						$values .= ')';
						if( $is_first_insert_sql_value )
						{ // Don't write a comma before first row values
							$is_first_insert_sql_value = false;
						}
						else
						{ // Write a comma between row values
							$values = ','.$values;
						}

						fwrite( $f, $values );
					}
					$page++;
				}
				unset( $rows );

				if( $is_insert_sql_started )
				{ // End SQL INSERT clause
					fwrite( $f, ";\n\n" );
				}
			}
			else
			{	// Display info to know only structure is backed up of the table:
				echo '<span class="text-warning">('.TB_('only structure').')</span>';
			}

			// Flush the output to a file
			if( fflush( $f ) )
			{
				echo ' OK.';
			}
			echo '<br />';
			evo_flush();
		}

		if( $this->drop_table_first )
		{	// Restore foreign key check:
			fwrite( $f, "SET FOREIGN_KEY_CHECKS=@fkey_check;\n" );
		}

		// Close backup file input stream
		fclose( $f );

		$result = true;

		if( $this->pack_backup_files )
		{ // Pack created backup SQL script
			$result = pack_archive( $backup_dirpath.'db.zip', $backup_dirpath, $backup_sql_filename );

			unlink( $backup_sql_filepath );
		}

		return true;
	}


	/**
	 * Copy directory recursively
	 *
	 * @param string source directory
	 * @param string destination directory
	 * @param boolean TRUE to display of what backup is doing
	 * @param array excluded directories
	 */
	function recurse_copy( $src, $dest, $root = true, $exclude_folders = array() )
	{
		if( is_dir( $src ) )
		{
			if( ! ( $dir = opendir( $src ) ) )
			{
				return false;
			}
			if( ! evo_mkdir( $dest ) )
			{
				return false;
			}
			while( false !== ( $file = readdir( $dir ) ) )
			{
				if( $file == '.' || $file == '..' )
				{ // Skip these reserved names
					continue;
				}

				if( $file == 'upload-tmp' )
				{ // Skip temp folder
					continue;
				}

				if( in_array( $file, $exclude_folders ) )
				{	// Skip the excluded files/folders:
					continue;
				}

				$srcfile = $src.'/'.$file;
				if( is_dir( $srcfile ) )
				{
					if( $root )
					{ // progressive display of what backup is doing
						echo sprintf( TB_('Backing up &laquo;<strong>%s</strong>&raquo; ...'), $srcfile ).'<br/>';
						evo_flush();
					}
					$this->recurse_copy( $srcfile, $dest . '/' . $file, false, $exclude_folders );
				}
				else
				{ // Copy file
					copy( $srcfile, $dest.'/'. $file );
				}
			}
			closedir( $dir );
		}
		else
		{
			copy( $src, $dest );
		}
	}


	/**
	 * Include all of the folders and tables to backup.
	 */
	function include_all()
	{
		global $backup_paths, $backup_tables;

		foreach( $backup_paths as $name => $settings )
		{
			if( array_key_exists( 'label', $settings ) && !is_null( $settings['label'] ) )
			{
				$this->backup_paths[$name] = true;
			}
		}

		foreach( $backup_tables as $name => $settings )
		{
			$this->backup_tables[$name] = true;
		}
	}


	/**
	 * Check has data list included directories/files or tables
	 * @param array list
	 * @return boolean
	 */
	function has_included( & $data_list )
	{
		foreach( $data_list as $included )
		{
			if( $included )
			{
				return true;
			}
		}
		return false;
	}


	/**
	 * Convert path to array
	 * @param mixed path
	 * @return array
	 */
	function path_to_array( $path )
	{
		if( is_array( $path ) )
		{
			return $path;
		}
		return array( $path );
	}
}

?>
