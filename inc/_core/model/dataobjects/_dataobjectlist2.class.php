<?php
/**
 * This file implements the abstract DataObjectList2 base class.
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

load_class('_core/ui/results/_filteredresults.class.php', 'FilteredResults' );


/**
 * Data Object List Base Class 2
 *
 * This is typically an abstract class, useful only when derived.
 * Holds DataObjects in an array and allows walking through...
 *
 * This SECOND implementation will deprecate the first one when finished.
 *
 * @package evocore
 * @version beta
 * @abstract
 */
class DataObjectList2 extends FilteredResults
{


	/**
	 * Constructor
	 *
	 * If provided, executes SQL query via parent Results object
	 *
	 * @param DataObjectCache
	 * @param integer number of lines displayed on one screen (null for default [20])
	 * @param string prefix to differentiate page/order params when multiple Results appear on same page
	 * @param string default ordering of columns (special syntax)
	 */
	function __construct( & $Cache, $limit = null, $param_prefix = '', $default_order = NULL )
	{
		// WARNING: we are not passing any SQL query to the Results object
		// This will make the Results object behave a little bit differently than usual:
		Results::__construct( NULL, $param_prefix, $default_order, $limit, NULL, false );

		// The list objects will also be cached in this cache.
		// The Cache object may also be useful to get table information for the Items.
		$this->Cache = & $Cache;

		// Colum used for IDs
		$this->ID_col = $Cache->dbIDname;
	}


	function & get_row_by_idx( $idx )
	{
		return $this->rows[ $idx ];
	}


	/**
	 * Instantiate an object for requested row and cache it:
	 */
	function & get_by_idx( $idx )
	{
		return $this->Cache->instantiate( $this->rows[$idx] ); // pass by reference: creates $rows[$idx]!
	}


	/**
	 * Instantiate an object for requested row by field and cache it:
	 *
	 * @param string DB field name
	 * @param string Value
	 * @return object
	 */
	function & get_by_field( $field_name, $field_value )
	{
		$obj_ID = 0;
		$null_Obj = NULL;

		foreach( $this->rows as $row )
		{	// Find object ID by field value
			if( $row->$field_name == $field_value )
			{
				$obj_ID = $row->{$this->ID_col};
				break;
			}
		}

		if( $obj_ID == 0 )
		{	// No object ID found, Exit here
			return $null_Obj;
		}

		$this->restart();
		while( $Obj = & $this->get_next() )
		{	// Find Object by ID
			if( $Obj->ID == $obj_ID )
			{
				return $Obj;
			}
		}

		return $null_Obj;
	}


	/**
	 * Get next object in list
	 */
	function & get_next()
	{
		// echo '<br />Get next, current idx was: '.$this->current_idx.'/'.$this->result_num_rows;

		if( $this->current_idx >= $this->result_num_rows )
		{	// No more object in list
			$this->current_Obj = NULL;
			$r = false; // TODO: try with NULL
			return $r;
		}

		// We also keep a local ref in case we want to use it for display:
		$this->current_Obj = & $this->get_by_idx( $this->current_idx );
		$this->next_idx();

		return $this->current_Obj;
	}


	/**
	 * Display a global title matching filter params
	 *
	 * @todo implement $order
	 *
	 * @param string prefix to display if a title is generated
	 * @param string suffix to display if a title is generated
	 * @param string glue to use if multiple title elements are generated
	 * @param string comma separated list of titles inthe order we would like to display them
	 * @param string format to output, default 'htmlbody'
	 */
	function get_filter_title( $prefix = ' ', $suffix = '', $glue = ' - ', $order = NULL, $format = 'htmlbody' )
	{
		$title_array = $this->get_filter_titles();

		if( empty( $title_array ) )
		{
			return '';
		}

		// We have something to display:
		$r = implode( $glue, $title_array );
		$r = $prefix.format_to_output( $r, $format ).$suffix;
		return $r;
	}


	/**
	 * Move up the element order in database
	 *
	 * @param integer id element
	 * @return unknown
	 */
	function move_up( $id )
	{
		global $DB, $Messages, $result_fadeout;

		$DB->begin();

		if( ($obj = & $this->Cache->get_by_ID( $id )) === false )
		{
			$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Entry') ), 'error' );
			$DB->commit();
			return false;
		}
		$order = $obj->order;

		// Get the ID of the inferior element which his order is the nearest
		$rows = $DB->get_results( 'SELECT '.$this->Cache->dbIDname
			 	.' FROM '.$this->Cache->dbtablename
				.' WHERE '.$this->Cache->dbprefix.'order < '.$order
				.' ORDER BY '.$this->Cache->dbprefix.'order DESC'
				.' LIMIT 0,1' );

		if( count( $rows ) )
		{
			// instantiate the inferior element
			$obj_inf = & $this->Cache->get_by_ID( $rows[0]->{$this->Cache->dbIDname} );

			//  Update element order
			$obj->set( 'order', $obj_inf->order );
			$obj->dbupdate();

			// Update inferior element order
			$obj_inf->set( 'order', $order );
			$obj_inf->dbupdate();

			// EXPERIMENTAL FOR FADEOUT RESULT
			$result_fadeout[$this->Cache->dbIDname][] = $id;
			$result_fadeout[$this->Cache->dbIDname][] = $obj_inf->ID;
		}
		else
		{
			$Messages->add( T_('This element is already at the top.'), 'error' );
		}
		$DB->commit();
	}


	/**
	 * Move down the element order in database
	 *
	 * @param integer id element
	 * @return unknown
	 */
	function move_down( $id )
	{
		global $DB, $Messages, $result_fadeout;

		$DB->begin();

		if( ($obj = & $this->Cache->get_by_ID( $id )) === false )
		{
			$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Entry') ), 'error' );
			$DB->commit();
			return false;
		}
		$order = $obj->order;

		// Get the ID of the inferior element which his order is the nearest
		$rows = $DB->get_results( 'SELECT '.$this->Cache->dbIDname
														 	.' FROM '.$this->Cache->dbtablename
														 .' WHERE '.$this->Cache->dbprefix.'order > '.$order
													.' ORDER BY '.$this->Cache->dbprefix.'order ASC
														 		LIMIT 0,1' );

		if( count( $rows ) )
		{
			// instantiate the inferior element
			$obj_sup = & $this->Cache->get_by_ID( $rows[0]->{$this->Cache->dbIDname} );

			//  Update element order
			$obj->set( 'order', $obj_sup->order );
			$obj->dbupdate();

			// Update inferior element order
			$obj_sup->set( 'order', $order );
			$obj_sup->dbupdate();

			// EXPERIMENTAL FOR FADEOUT RESULT
			$result_fadeout[$this->Cache->dbIDname][] = $id;
			$result_fadeout[$this->Cache->dbIDname][] = $obj_sup->ID;
		}
		else
		{
			$Messages->add( T_('This element is already at the bottom.'), 'error' );
		}
		$DB->commit();
	}
}

?>
