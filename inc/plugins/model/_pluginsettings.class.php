<?php
/**
 * This file implements the PluginSettings class, to handle plugin/name/value triplets.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package plugins
 *
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'settings/model/_abstractsettings.class.php', 'AbstractSettings' );

/**
 * Class to handle settings for plugins
 *
 * @package plugins
 */
class PluginSettings extends AbstractSettings
{
	var $plugin_ID;
	/**
	 * Constructor
	 *
	 * @param integer plugin ID where these settings are for
	 */
	function __construct( $plugin_ID )
	{ // constructor
		parent::__construct( 'T_pluginsettings', array( 'pset_plug_ID', 'pset_name' ), 'pset_value', 1 );

		$this->plugin_ID = $plugin_ID;
	}


	/**
	 * Get a setting by name for the Plugin.
	 *
	 * @param string The settings name.
	 * @return mixed|NULL|false False in case of error, NULL if not found, the value otherwise.
	 */
	function get( $setting = NULL, $arg1 = NULL, $arg2 = NULL )
	{
		if( strpos( $setting, '[' ) !== false )
		{	// Get value for array setting like "sample_sets[0][group_name_param_name]":
			$setting_names = explode( '[', $setting );
			$setting_value = parent::getx( $this->plugin_ID, $setting_names[0] );
			unset( $setting_names[0] );
			foreach( $setting_names as $setting_name )
			{
				$setting_name = trim( $setting_name, ']' );
				if( isset( $setting_value[ $setting_name ] ) )
				{
					$setting_value = $setting_value[ $setting_name ];
				}
				else
				{
					$setting_value = NULL;
					break;
				}
			}
			return $setting_value;
		}

		// Get normal(not array) setting value:
		return parent::getx( $this->plugin_ID, $setting );
	}


	/**
	 * Set a Plugin setting. Use {@link dbupdate()} to write it to the database.
	 *
	 * @param string The settings name.
	 * @param string The settings value.
	 * @return boolean true, if the value has been set, false if it has not changed.
	 */
	function set( $setting = NULL, $value = NULL )
	{
		return parent::setx( $this->plugin_ID, $setting, $value );
	}


	/**
	 * Delete a setting.
	 *
	 * Use {@link dbupdate()} to commit it to the database.
	 *
	 * @param string name of setting
	 */
	function delete( $setting )
	{
		return parent::delete( $this->plugin_ID, $setting );
	}


	/**
	 * Commit changed plugin settings to DB.
	 *
	 * @return boolean true, if settings have been updated; false otherwise
	 */
	function dbupdate()
	{
		$result = parent::dbupdate();

		if( $result )
		{	// BLOCK CACHE INVALIDATION:
			BlockCache::invalidate_key( 'plugin_ID', $this->plugin_ID ); // Plugin has changed
		}

		return $result;
	}

}

?>
