<?php
/**
 * This file implements the WidgetContainerCache class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package evocore
 *
 * @version $Id: _widgetcontainercache.class.php 10060 2016-03-09 10:40:31Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobjectcache.class.php', 'DataObjectCache' );

load_class( 'widgets/model/_widgetcontainer.class.php', 'WidgetContainer' );


/**
 * Widget Container Cache Class
 *
 * @package evocore
 */
class WidgetContainerCache extends DataObjectCache
{
	/**
	 * Cache by collection ID, container code and skin type
	 *
	 * @var cache_by_coll_skintype_code array
	 */
	var $cache_by_coll_skintype_code;

	/**
	 * Constructor
	 */
	function __construct()
	{
		// Call parent constructor:
		parent::__construct( 'WidgetContainer', true, 'T_widget__container', 'wico_', 'wico_ID', 'wico_order' );
	}


	/**
	 * Add a WidgetContainer to the cache
	 *
	 * @param object Widget Container
	 * @return boolean true if it was added false otherwise
	 */
	function add( $WidgetContainer )
	{
		$container_code = $WidgetContainer->get( 'code' );
		if( ! empty( $container_code ) )
		{	// This container is not shared and it is a main container ( has code )
			$skin_type = $WidgetContainer->get( 'skin_type' );
			if( ! isset( $this->cache_by_coll_skintype_code[ $WidgetContainer->coll_ID ][ $skin_type ] ) )
			{
				$this->cache_by_coll_skintype_code[ $WidgetContainer->coll_ID ][ $skin_type ] = array();
			}
			$this->cache_by_coll_skintype_code[ $WidgetContainer->coll_ID ][ $skin_type ][ $container_code ] = & $WidgetContainer;
		}

		return parent::add( $WidgetContainer );
	}


	/**
	 * Load all widget containers from the given collection
	 *
	 * @param integer Collection ID
	 */
	function load_by_coll_ID( $coll_ID )
	{
		global $DB;

		if( isset( $this->cache_by_coll_skintype_code[ $coll_ID ] ) )
		{	// Don't load widget containers twice:
			return;
		}

		$SQL = new SQL( 'Load all widget containers for collection #'.$coll_ID.' and shared into cache' );
		$SQL->SELECT( '*' );
		$SQL->FROM( 'T_widget__container' );
		$SQL->WHERE( '( wico_coll_ID = '.$DB->quote( $coll_ID ).' OR wico_coll_ID IS NULL )' );
		$SQL->WHERE_and( 'wico_code IS NOT NULL' );
		$SQL->ORDER_BY( 'wico_order' );

		// Instantiate and cache widget containers:
		$this->instantiate_list( $DB->get_results( $SQL ) );
	}


	/**
	 * Get widget containers from the given collection for the given skin type
	 *
	 * @param integer Collection ID
	 * @param string Skin type: 'normal', 'mobile', 'tablet', 'alt'
	 * @return array of WidgetContainer
	 */
	function get_by_coll_skintype( $coll_ID, $skin_type )
	{
		$this->load_by_coll_ID( $coll_ID );

		if( isset( $this->cache_by_coll_skintype_code[ $coll_ID ][ $skin_type ] ) )
		{
			return $this->cache_by_coll_skintype_code[ $coll_ID ][ $skin_type ];
		}

		return array();
	}


	/**
	 * Get widget container from the given collection with the given container code and for given skin type
	 *
	 * @param integer Collection ID
	 * @param string Skin type
	 * @param string Container code
	 * @param boolean Halt on error
	 * @return object WidgetContainer
	 */
	function & get_by_coll_skintype_code( $coll_ID, $skin_type, $code, $halt_on_error = false )
	{
		$this->load_by_coll_ID( $coll_ID );

		if( empty( $this->cache_by_coll_skintype_code[ $coll_ID ][ $skin_type ][ $code ] ) && // collection/skin container
		    empty( $this->cache_by_coll_skintype_code[''][ $skin_type ][ $code ] ) ) // shared container
		{
			if( $halt_on_error )
			{
				debug_die( 'Requested widget container does not exist!' );
			}
			$r = false;
			return $r;
		}

		if( isset( $this->cache_by_coll_skintype_code[ $coll_ID ][ $skin_type ][ $code ] ) )
		{	// Collection/skin container:
			return $this->cache_by_coll_skintype_code[ $coll_ID ][ $skin_type ][ $code ];
		}
		else
		{	// Shared container:
			return $this->cache_by_coll_skintype_code[''][ $skin_type ][ $code ];
		}
	}


	/**
	 * Get options array with groups for collection and shared sub-containers
	 *
	 * @param string Skin type
	 * @return array
	 */
	function get_subcontainers_option_array( $skin_type )
	{
		global $Blog, $DB;

		$this->clear();
		$SQL = $this->get_SQL_object();
		$SQL->WHERE_and( 'wico_main != 1' );
		$SQL->WHERE_and( 'wico_skin_type = '.$DB->quote( $skin_type ) );
		$SQL->WHERE_and( 'wico_coll_ID IS NULL'.( empty( $Blog ) ? '' : ' OR wico_coll_ID = '.$DB->quote( $Blog->ID ) ) );
		$widget_containers = $this->load_by_sql( $SQL );

		$container_options = array(
				'' => T_('None'),
				'!create_new' => T_('Create New'),
				T_('Existing Sub-Containers') => array(),
				T_('Existing Shared Sub-Containers') => array(),
			);

		foreach( $widget_containers as $WidgetContainer )
		{
			if( $WidgetContainer->get( 'coll_ID' ) )
			{	// Collection sub-container:
				$group = T_('Existing Sub-Containers');
				$prefix = 'coll:';
			}
			else
			{	// Shared sub-container:
				$group = T_('Existing Shared Sub-Containers');
				$prefix = 'shared:';
			}
			$container_options[ $group ][ $prefix.$WidgetContainer->get( 'code' ) ] = $WidgetContainer->get( 'name' );
		}

		return $container_options;
	}
}
?>