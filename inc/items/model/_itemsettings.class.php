<?php
/**
 * This file implements the  ItemSettings class which handles
 * item_ID/name/value triplets for collections/items.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'settings/model/_abstractsettings.class.php', 'AbstractSettings' );

/**
 * Class to handle the settings for collections/items
 *
 * @package evocore
 */
class ItemSettings extends AbstractSettings
{
	/**
	 * Changes for params, Key is param name/key, Value is new value by function ItemSettings::set()
	 *
	 * @var array
	 */
	var $changes = array();

	/**
	 * The default settings to use, when a setting is not defined in the database.
	 *
	 * @access protected
	 */
	var $_defaults = array(
		'editor_code'          => NULL, // Plugin code of the editor which was last used to edit this post
		'hide_teaser'          => '0',  // Setting to show/hide teaser when displaying -- more --
		'metakeywords'         => NULL, // Meta keywords for this post
		'metadesc'             => NULL, // Meta Description tag for this post
		'comment_expiry_delay' => NULL, // Post comments are not displayed and post ratings are not counted after they are older then this expiry delay value. If this value is null then comments will never expire.

		// Location & google map settings:
		'latitude' => NULL,
		'longitude' => NULL,
		'map_zoom' => NULL,
		'map_type' => NULL,

		// Add new default here.
		);


	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct( 'T_items__item_settings', array( 'iset_item_ID', 'iset_name' ), 'iset_value', 1 );
	}


	/**
	 * Loads the settings. Not meant to be called directly, but gets called
	 * when needed.
	 *
	 * @access protected
	 * @param string First column key
	 * @param string Second column key, NULL - to load all settings of the requested Item
	 * @param string NOT USED (provided for compatibility with parent class)
	 * @return boolean
	 */
	function _load( $item_ID = NULL, $arg = NULL, $arg3 = NULL )
	{
		if( empty( $item_ID ) || ( empty( $arg ) && $arg !== NULL ) )
		{
			return false;
		}

		return parent::_load( $item_ID, $arg );
	}


	/**
	 * Get a setting from the DB settings table.
	 *
	 * @uses get_default()
	 * @param integer Item ID
	 * @param string Setting name/key
	 * @return string|false|NULL value as string on success; NULL if not found; false in case of error
	 */
	function get( $item_ID, $parname )
	{
		return parent::getx( $item_ID, $parname );
	}


	/**
	 * Temporarily sets a setting ({@link dbupdate()} writes it to DB).
	 *
	 * @param integer Item ID
	 * @param string Setting name/key
	 * @param mixed Value
	 * @return boolean true, if the value has been set, false if it has not changed.
	 */
	function set( $item_ID, $parname, $value )
	{
		// Limit value with max possible length:
		$value = utf8_substr( $value, 0, 10000 );

		$this->changes[ $parname ] = $value;

		return parent::setx( $item_ID, $parname, $value );
	}
}

?>
