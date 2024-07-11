<?php
/**
 * This file implements the AbstractImport class designed to handle any kind of Imports.
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


/**
 * Abstract Import Class
 *
 * @package evocore
 */
class AbstractImport
{
	var $import_code;
	var $coll_ID;
	var $log_file = true;
	var $log_errors_num = 0;
	var $log_cli_format = 'text';

	/**
	 * Log here what wrappers are opened currently
	 * @var array Key - autoincremented started with 0, Value - wrapper types: 'list'
	 */
	private $log_wrappers = array();

	/**
	 * Get collection
	 *
	 * @param object|NULL|FALSE Collection
	 */
	function & get_Blog()
	{
		$BlogCache = & get_BlogCache();
		$Blog = & $BlogCache->get_by_ID( $this->coll_ID );

		return $Blog;
	}


	/**
	 * Start to log into file on disk
	 */
	function start_log()
	{	// TODO: Factorize with start_install_log():
		global $baseurl, $media_path, $rsc_url, $app_version_long;

		// Get file path for log:
		$log_file_path = $media_path.'import/logs/'
			// Current data/time:
			.date( 'Y-m-d-H-i-s' ).'-'
			// Site base URL:
			.str_replace( '/', '-', preg_replace( '#^https?://#i', '', trim( $baseurl, '/' ) ) ).'-'
			// Collection short name:
			.( $import_Blog = & $this->get_Blog() ? preg_replace( '#[^a-z\d]+#', '-', strtolower( $import_Blog->get( 'shortname' ) ) ).'-' : '' )
			// Suffix for this import tool:
			.$this->import_code.'-import-log-'
			// Random hash:
			.generate_random_key( 16 ).'.html';

		// Try to create folder for log files:
		if( ! mkdir_r( $media_path.'import/logs/' ) )
		{	// Display error if folder cannot be created for log files:
			$this->display_log_file_error( 'Cannot create the folder <code>'.$media_path.'import/logs/</code> for log files!' );
			return false;
		}

		if( ! ( $this->log_file_handle = fopen( $log_file_path, 'w' ) ) )
		{	// Display error if the log fiel cannot be created in the log folder:
			$this->display_log_file_error( 'Cannot create the file <code>'.$log_file_path.'</code> for current log!' );
			return false;
		}

		// Display where log will be stored:
		$this->log_to_screen( '<b>Log file:</b> <code>'.$log_file_path.'</code><br />' );

		// Write header of the log file:
		$this->log_to_file( '<!DOCTYPE html>'."\r\n"
			.'<html lang="en-US">'."\r\n"
			.'<head>'."\r\n"
			.'<link href="'.$rsc_url.'css/bootstrap/bootstrap.css?v='.$app_version_long.'" type="text/css" rel="stylesheet" />'."\r\n"
			.'<link href="'.$rsc_url.'build/bootstrap-backoffice-b2evo_base.bundle.css?v='.$app_version_long.'" type="text/css" rel="stylesheet" />'."\r\n"
			.'</head>'."\r\n"
			.'<body>' );

		// Display start time:
		$this->log( 'Import started at: '.date( 'Y-m-d H:i:s' ) );
	}


	/**
	 * Display error when log cannot be stored in file on disk
	 *
	 * @param string Message
	 */
	function display_log_file_error( $message )
	{
		if( empty( $this->log_file_error_reported ) )
		{	// Report only first detected error to avoid next duplicated errors on screen:
			echo '<p class="text-danger"><span class="label label-danger">ERROR</span> '.$message.'</p>';
			$this->log_file_error_reported = true;
		}
	}


	/**
	 * End of log into file on disk
	 */
	function end_log()
	{	// TODO: Factorize with end_install_log():
		// Display finish time:
		$this->log( 'Import finished at: '.date( 'Y-m-d H:i:s' ) );

		// Write footer of the log file:
		$this->log_to_file( '</body>'."\r\n"
			.'</html>' );

		if( isset( $this->log_file_handle ) && $this->log_file_handle )
		{	// Close the log file:
			fclose( $this->log_file_handle );
		}
	}


	/**
	 * Get a log message
	 *
	 * @param string Message
	 * @param string Type: 'success', 'error', 'warning', 'info'
	 * @param string HTML tag for type/styled log: 'p', 'span', 'b', etc.
	 * @param boolean TRUE to display label
	 * @return string|FALSE Formatted log message, FALSE - when message should not be displayed
	 */
	function get_log( $message, $type = NULL, $type_html_tag = 'p', $display_label = true )
	{	// TODO: Factorize with get_install_log():
		if( $message === '' )
		{	// Don't log empty strings:
			return false;
		}

		switch( $type )
		{
			case 'success':
				$before = '<'.$type_html_tag.' class="text-success"> ';
				$after = '</'.$type_html_tag.'>';
				break;

			case 'error':
			case 'text-error':
				$before = '<'.$type_html_tag.' class="text-danger">'.( $display_label  && $type == 'error'? '<span class="label label-danger">ERROR</span>' : '' ).' ';
				$after = '</'.$type_html_tag.'>';
				break;

			case 'warning':
			case 'text-warning':
				$before = '<'.$type_html_tag.' class="text-warning">'.( $display_label && $type == 'warning' ? '<span class="label label-warning">WARNING</span>' : '' ).' ';
				$after = '</'.$type_html_tag.'>';
				break;

			case 'info':
			case 'text-info':
				$before = '<'.$type_html_tag.' class="text-info">'.( $display_label && $type == 'info' ? '<span class="label label-info">INFO</span>' : '' ).' ';
				$after = '</'.$type_html_tag.'>';
				break;

			case 'text':
				$before = '<'.$type_html_tag.'>';
				$after = '</'.$type_html_tag.'>';
				break;

			default:
				$before = '';
				$after = '';
				break;
		}

		return $before.$message.$after;
	}


	/**
	 * Log message inside list
	 * Helper for quick log in list
	 *
	 * @param string Message
	 * @param string Type: 'success', 'error', 'warning', 'text', NULL - to don't use wrapper around message
	 * @param string HTML tag for type/styled log: 'p', 'span', 'b', etc.
	 */
	function log_list( $message, $type = 'text', $type_html_tag = 'li' )
	{
		$this->log_inside( 'list' );
		$this->log( $message, $type, $type_html_tag );
	}


	/**
	 * Start to log next messages inside requested wrapper
	 * Print start of wrapper tag if it was not opened yet,
	 * otherwise continue log in currently opened wrapper
	 *
	 * @param string Wrapper type
	 */
	function log_inside( $wrapper_type )
	{
		if( in_array( $wrapper_type, $this->log_wrappers ) )
		{	// The requested wrapper is already opened,
			// don't open this twice:
			return;
		}

		// Flag to know the wrapper is already opened:
		$this->log_wrappers[] = $wrapper_type;

		switch( $wrapper_type )
		{
			case 'list':
				$this->log( '<ul class="list-default" style="margin-bottom:0">' );
				break;

			case 'p':
				$this->log( '<p>' );
				break;
		}
	}


	/**
	 * Close last/currently opened log wrapper
	 *
	 * @return string|FALSE Type of last ended wrapper, FALSE - if no opened wrappers
	 */
	function close_log_wrapper()
	{
		if( empty( $this->log_wrappers ) )
		{	// No opened wrappers
			return false;
		}

		// Get last wrapper:
		if( ! ( $last_wrapper_type = array_pop( $this->log_wrappers ) ) )
		{	// No found last wrapper
			return false;
		}

		switch( $last_wrapper_type )
		{
			case 'list':
				$this->log( '</ul>' );
				break;

			case 'p':
				$this->log( '</p>' );
				break;
		}

		return $last_wrapper_type;
	}


	/**
	 * Close ALL opened log wrappers
	 */
	function close_log_wrappers()
	{
		while( $this->close_log_wrapper() !== false );
	}


	/**
	 * Log a message on screen and into file on disk
	 *
	 * @param string Message
	 * @param string Type: 'success', 'error', 'warning'
	 * @param string HTML tag for type/styled log: 'p', 'span', 'b', etc.
	 * @param boolean TRUE to display label
	 */
	function log( $message, $type = NULL, $type_html_tag = 'p', $display_label = true )
	{	// TODO: Factorize with prepare_install_log_message():
		$message = $this->get_log( $message, $type, $type_html_tag, $display_label );

		if( $message === false )
		{	// Skip when message should not be displayed:
			return;
		}

		if( $display_label && $type == 'error' )
		{	// Count a number of errors:
			$this->log_errors_num++;
		}

		// Display message on screen:
		$this->log_to_screen( $message );

		// Try to store a message into the log file on the disk:
		$this->log_to_file( $message );
	}


	/**
	 * Log SUCCESS message on screen and into file on disk
	 *
	 * @param string Message
	 * @param string HTML tag for type/styled log: 'p', 'span', 'b', etc.
	 * @param boolean TRUE to display label
	 */
	function log_success( $message, $type_html_tag = 'p', $display_label = true )
	{
		$this->log( $message, 'success', $type_html_tag, $display_label );
	}


	/**
	 * Log ERROR message on screen and into file on disk
	 *
	 * @param string Message
	 * @param string HTML tag for type/styled log: 'p', 'span', 'b', etc.
	 * @param boolean TRUE to display label
	 */
	function log_error( $message, $type_html_tag = 'p', $display_label = true )
	{
		$this->log( $message, 'error', $type_html_tag, $display_label );
	}


	/**
	 * Log WARNING message on screen and into file on disk
	 *
	 * @param string Message
	 * @param string HTML tag for type/styled log: 'p', 'span', 'b', etc.
	 * @param boolean TRUE to display label
	 */
	function log_warning( $message, $type_html_tag = 'p', $display_label = true )
	{
		$this->log( $message, 'warning', $type_html_tag, $display_label );
	}


	/**
	 * Log INFO message on screen and into file on disk
	 *
	 * @param string Message
	 * @param string HTML tag for type/styled log: 'p', 'span', 'b', etc.
	 * @param boolean TRUE to display label
	 */
	function log_info( $message, $type_html_tag = 'p', $display_label = true )
	{
		$this->log( $message, 'info', $type_html_tag, $display_label );
	}


	/**
	 * Log a message into file on disk
	 *
	 * @param string Message
	 */
	function log_to_file( $message )
	{	// TODO: Factorize with install_log_to_file():
		if( ! $this->log_file )
		{	// Don't log into file:
			return;
		}

		if( ! isset( $this->log_file_handle ) || ! $this->log_file_handle )
		{	// Log must be started:
			$this->display_log_file_error( 'You must start log by function <code>'.get_class( $this ).'->start_log()</code>!' );
			return;
		}

		// Put a message into the log file on the disk:
		fwrite( $this->log_file_handle, $message."\r\n" );
	}


	/**
	 * Log a message to screen
	 *
	 * @param string Message
	 */
	function log_to_screen( $message )
	{
		global $is_cli;

		if( $is_cli && $this->log_cli_format == 'text' )
		{	// Remove and convert all html tags to text format on CLI mode:

			// Remove all new lines because we build them from HTML tags:
			$message = str_replace( array( "\n", "\r" ), '', $message );

			// Keep all URLs and display them:
			$message = preg_replace( '/<a[^>]+href="([^"]+)"[^>]*>(.+)<\/a>/i', '$2(URL: $1)', $message );

			// Convert all HTML tags to readable on CLI mode:
			$message = preg_replace_callback( '#<(/?[a-z\d]+)[^>]*>([\r\n]*)#is', array( $this, 'log_to_screen_cli_callback' ), $message );

			// Remove all not formatted HTML tags from text:
			$text = strip_tags( $message );

			// Replace all html entities like "&nbsp;", "&raquo;", "&laquo;" to readable chars:
			$message = html_entity_decode( $message );
		}

		echo $message;
		evo_flush();
	}


	/**
	 * Callback function to clear log message for CLI mode
	 *
	 * @param array Matches
	 */
	function log_to_screen_cli_callback( $m )
	{
		switch( $m[1] )
		{
			case 'br':
				return '';

			case 'p':
				return "\n";
			case '/p':
				return "\n";

			case 'h1':
			case 'h2':
			case 'h3':
			case 'h4':
			case 'h5':
			case 'h6':
				return "\n\n".str_repeat( '-', substr( $m[1], 1 ) ).' ';
			case '/h1':
			case '/h2':
			case '/h3':
			case '/h4':
			case '/h5':
			case '/h6':
				return ' '.str_repeat( '-', substr( $m[1], 2 ) )."\n";

			case 'b':
			case '/b':
				return '*';

			case 'ul':
			case '/ul':
				return '';
			case 'li':
				return ' - ';
			case '/li':
				return '';

			case 'code':
			case '/code':
				return '`';

			case 'span':
				return '[';
			case '/span':
				return ']';
		}

		return $m[0];
	}


	/**
	 * Check restriction by file manifest.yaml
	 *
	 * @param string Folder path where we should find the manifest file
	 * @param boolean TRUE when no restrictions to import, FALSE at least one restriction is detected, so import must be stopped
	 */
	function check_manifest( $path )
	{
		$manifest_path = rtrim( $path, '/' ).'/manifest.yaml';

		if( file_exists( $manifest_path ) )
		{	// Manifest file is detected:
			$manifest_content = file_get_contents( $manifest_path );

			// Load Spyc library to parse YAML data:
			load_funcs( '_ext/spyc/Spyc.php' );

			$manifest = spyc_load( $manifest_content );

			if( empty( $manifest ) || ! is_array( $manifest ) )
			{	// Wrong or empty manifest, Don't restrict import:
				return true;
			}

			foreach( $manifest as $manifest_rule => $manifest_value )
			{
				$log_prefix = 'Manifest: <code>'.$manifest_rule.': '.$manifest_value.'</code>: ';
				switch( $manifest_rule )
				{
					case 'collection-urlname':
						// Check collection urlname:
						if( ( $import_Blog = & $this->get_Blog() ) &&
						    $import_Blog->get( 'urlname' ) != $manifest_value )
						{	// Stop import:
							$this->log( $log_prefix.'NOT OK as destination collection URL name is <code>'.$import_Blog->get( 'urlname' ).'</code>: STOPPING IMPORT.', 'error' );
							return false;
						}
						$this->log( $log_prefix.'OK', 'success' );
						break;

					case 'collection-locale':
						// Check collection locale:
						if( ( $import_Blog = & $this->get_Blog() ) &&
						    ! $import_Blog->has_locale( $manifest_value ) )
						{	// Stop import:
							$this->log( $log_prefix.'NOT OK as destination collection locale is <code>'.$import_Blog->get( 'locale' ).'</code>: STOPPING IMPORT.', 'error' );
							return false;
						}
						$this->log( $log_prefix.'OK', 'success' );
						break;

					case 'import-mode':
						if( $this->get_option( 'import_type' ) != $manifest_value )
						{	// Stop import because currently selected import mode is defferent than rule from manifest file:
							$this->log( $log_prefix.'NOT OK as currently selected import mode is <code>'.$this->get_option( 'import_type' ).'</code>', 'error' );
							return false;
						}
						$this->log( $log_prefix.'OK', 'success' );
						break;
				}
			}
		}
		else
		{	// Display warning when no manifest bu don't stop import:
			$this->log( 'No <code>manifest.yaml</code> file was found in <code>'.$manifest_path.'</code>', 'warning' );
		}

		// Allow import because no restriction was found in manifest file:
		return true;
	}
}
?>