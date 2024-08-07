<?php
/**
 * This file implements the SkinCache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 *
 * @author fplanque: Francois PLANQUE
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobjectcache.class.php', 'DataObjectCache' );

load_class( 'skins/model/_skin.class.php', 'Skin' );

load_funcs( 'skins/_skin.funcs.php' );

/**
 * Skin Cache Class
 *
 * @package evocore
 */
class SkinCache extends DataObjectCache
{
	/**
	 * Cache by folder
	 * @var array
	 */
	var $cache_by_folder = array();
	var $cache_by_class = array();

	var $loaded_types = array();


	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct( 'Skin', false, 'T_skins__skin', 'skin_', 'skin_ID', 'skin_name', NULL,
			/* TRANS: "None" select option */ NT_('No skin') );
	}


	/**
	 * Add object to cache, handling our own indices.
	 *
	 * @param object Skin object
	 * @return boolean True on add, false if already existing.
	 */
	function add( $Skin )
	{
		$this->cache_by_folder[ $Skin->folder ] = $Skin;
		$this->cache_by_class[ $Skin->class ] = $Skin;

		return parent::add( $Skin );
	}


	/**
	 * Get an object from cache by its folder name.
	 *
	 * Load the object into cache, if necessary.
	 *
	 * This is used to get a skin for an RSS/Aom type; also to check if a skin is installed.
	 *
	 * @param string folder name of object to load
	 * @param boolean false if you want to return false on error
	 * @return Skin A Skin object on success, false on failure (may also halt!)
	 */
	function & get_by_folder( $req_folder, $halt_on_error = true )
	{
		global $DB, $Debuglog;

		if( isset($this->cache_by_folder[$req_folder]) )
		{
			return $this->cache_by_folder[$req_folder];
		}

		// Load just the requested object:
		$Debuglog->add( "Loading <strong>$this->objtype($req_folder)</strong> into cache", 'dataobjects' );
		$sql = "
				SELECT *
				  FROM $this->dbtablename
				 WHERE skin_folder = ".$DB->quote($req_folder);
		$row = $DB->get_row( $sql );

		if( empty( $row ) )
		{ // Requested object does not exist
			if( $halt_on_error ) debug_die( "Requested $this->objtype does not exist!" );
			$r = false;
			return $r;
		}

		$Skin = new Skin( $row ); // COPY!
		$this->add( $Skin );

		return $Skin;
	}


	/**
	 * Get an object from cache by its skin class.
	 *
	 * This is used to get a skin for an RSS/Aom type; also to check if a skin is installed.
	 *
	 * @param string skin class name of object to load
	 * @param boolean false if you want to return false on error
	 * @return Skin A Skin object on success, false on failure (may also halt!)
	 */
	function & get_by_class( $req_class, $halt_on_error = true )
	{
		global $DB, $Debuglog;

		if( isset( $this->cache_by_class[$req_class] ) )
		{
			return $this->cache_by_class[$req_class];
		}

		// Load just the requested object:
		$Debuglog->add( "Loading <strong>$this->objtype( $req_class )</strong> into cache", 'dataobjects' );
		$sql = "
				SELECT *
					FROM $this->dbtablename
					WHERE skin_class = ".$DB->quote( $req_class );
		$row = $DB->get_row( $sql );

		if( empty( $row ) )
		{ // Requested object does not exist
			if( $halt_on_error ) debug_die( "Requested $this->objtype does not exist!" );
			$r = false;
			return $r;
		}

		$Skin = new Skin( $row ); // COPY!
		$this->add( $Skin );

		return $Skin;
	}


	/**
	 * Load the cache by type
	 *
	 * @param string
 	 */
	function load_by_type( $type )
	{
		/**
		 * @var DB
		 */
		global $DB;
		global $Debuglog;

		if( $this->all_loaded || !empty($this->loaded_types[$type]) )
		{ // Already loaded
			return false;
		}

		$Debuglog->add( get_class($this).' - Loading <strong>'.$this->objtype.'('.$type.')</strong> into cache', 'dataobjects' );
		$sql = 'SELECT *
					 FROM T_skins__skin
				   WHERE skin_type = '.$DB->quote($type).'
				   ORDER BY skin_name';

		foreach( $DB->get_results( $sql, OBJECT, 'Loading Skins('.$type.') into cache' ) as $row )
		{
			// Instantiate a custom object
			$this->instantiate( $row );
		}

		$this->loaded_types[$type] = true;

		return true;
	}


	/**
	 * Instanciate a new object within this cache
	 *
	 * @param object Skin row
	 * @param string Skin folder name
	 * @param boolean TRUE if function should die on error
	 */
	function & new_obj( $row = NULL, $skin_folder = NULL, $halt_on_error = false )
	{
		if( is_null( $skin_folder ) )
		{	// This happens when using the default skin
			$skin_folder = $row->skin_folder;
		}

		list( $base_skin, $skin_version ) = get_skin_folder_base_version( $skin_folder );

		// Check if we have a custom class derived from Skin:
		if( skin_file_exists( $skin_folder, '_skin.class.php' ) )
		{
			global $skins_path;
			require_once( $skins_path.$skin_folder.'/_skin.class.php' );
			$short_skin_folder = preg_replace( '/_skin$/', '', $base_skin ); // Remove '_skin' suffix
			$objtype = $short_skin_folder.'_Skin';
			if( ! class_exists( $objtype ) )
			{
				debug_die( 'There seems to be a <code>_skin.class.php</code> file in the skin directory <code>'.$skin_folder.'</code>, but it does not contain a properly named class. Expected class name is: <code>'.$objtype.'</code>' );
			}
		}
		elseif( $halt_on_error )
		{	// Don't allow to install skin if its class file doesn't exist:
			debug_die( 'There is no file <code>_skin.class.php</code> in the skin directory <code>'.$skin_folder.'</code>.' );
		}
		else
		{
			$objtype = 'Skin';
		}

		// Instantiate a custom object
		$obj = new $objtype( $row, $skin_folder ); // COPY !!

		return $obj;
	}

}

?>
