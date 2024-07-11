<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 *
 * @version _userfield.class.php,v 1.5 2009/09/16 18:11:51 fplanque Exp
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * Userfield Class
 *
 * @package evocore
 */
class Userfield extends DataObject
{
	/**
	 * Userfield Group ID
	 * @var integer
	 */
	var $ufgp_ID = 0;

	var $type = '';
	var $name = '';
	var $options;
	var $required = 'recommended';
	var $visibility = 'unrestricted';
	var $duplicated = 'allowed';
	var $order = '';
	var $suggest = '1';
	var $bubbletip;
	var $icon_name;
	var $code;
	var $grp_ID;

	/**
	 * Constructor
	 *
	 * @param object Database row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_users__fielddefs', 'ufdf_', 'ufdf_ID' );

		// Allow inseting specific IDs
		$this->allow_ID_insert = true;

		if( $db_row != NULL )
		{
			$this->ID         = $db_row->ufdf_ID;
			$this->ufgp_ID    = $db_row->ufdf_ufgp_ID;
			$this->type       = $db_row->ufdf_type;
			$this->name       = $db_row->ufdf_name;
			$this->options    = $db_row->ufdf_options;
			$this->required   = $db_row->ufdf_required;
			$this->visibility = isset( $db_row->ufdf_visibility ) ? $db_row->ufdf_visibility : 'unrestricted';
			$this->duplicated = $db_row->ufdf_duplicated;
			$this->order      = $db_row->ufdf_order;
			$this->suggest    = $db_row->ufdf_suggest;
			$this->bubbletip  = $db_row->ufdf_bubbletip;
			$this->icon_name  = $db_row->ufdf_icon_name;
			$this->code       = $db_row->ufdf_code;
			$this->grp_ID     = isset( $db_row->ufdf_grp_ID ) ? $db_row->ufdf_grp_ID : NULL;
		}
	}


	/**
	 * Get delete cascade settings
	 *
	 * @return array
	 */
	static function get_delete_cascades()
	{
		return array(
				array( 'table' => 'T_users__fields', 'fk' => 'uf_ufdf_ID', 'msg' => T_('%d user fields') ),
			);
	}


	/**
	 * Format options array
	 *
	 * @param array Source options
	 * @param string Format
	 * @return array Formatted Options
	 */
	static function format_options( $options, $format = NULL )
	{
		if( $format == 'radio' )
		{	// Format for radio form element:
			$formatted_options = array();
			foreach( $options as $key => $title )
			{
				$formatted_options[] = array( 'value' => $key, 'label' => $title );
			}
			return $formatted_options;
		}

		return $options;
	}


	/**
	 * Returns array of possible user field types
	 *
	 * @return array
	 */
	static function get_types()
	{
		return array(
			'word'   => T_('String'),
			'number' => T_('Numeric'),
			'list'   => T_('Option list'),
			'text'   => T_('Multiline text'),
			'email'  => T_('Email address'),
			'url'    => T_('URL'),
			'phone'  => T_('Phone number'),
			'user'   => T_('User select'),
		 );
	}


	/**
	 * Returns array of possible user field required types
	 *
	 * @param string Format
	 * @return array
	 */
	static function get_requireds( $format = NULL )
	{
		return Userfield::format_options( array(
				'require'     => T_('Required'),
				'recommended' => T_('Recommended'),
				'optional'    => T_('Optional'),
				'hidden'      => T_('Hidden'),
			), $format );
	}


	/**
	 * Returns array of possible user field visibilities
	 *
	 * @param string Format
	 * @return array
	 */
	static function get_visibilities( $format = NULL )
	{
		return Userfield::format_options( array(
				'unrestricted' => T_('Unrestricted'),
				'private'      => T_('Private (owner + admins)').' '.get_userfield_visibility_icon( 'private' ),
				'admin'        => T_('Admins only').' '.get_userfield_visibility_icon( 'admin' ),
			), $format );
	}


	/**
	 * Returns array of possible user field duplicated types
	 *
	 * @param string Format
	 * @return array
	 */
	static function get_duplicateds( $format = NULL )
	{
		return Userfield::format_options( array(
				'forbidden' => T_('Forbidden'),
				'allowed'   => T_('Allowed'),
				'list'      => T_('List style'),
			), $format );
	}

	/**
	 * Returns array of user field groups
	 *
	 * @return array
	 */
	function get_groups()
	{
		global $DB;

		return $DB->get_assoc( '
			SELECT ufgp_ID, ufgp_name
			  FROM T_users__fieldgroups
			 ORDER BY ufgp_order, ufgp_ID' );
	}


	/**
	 * Get last order number for current group
	 * Used in the action add a new field OR move fielddef from other group
	 *
	 * @param integer Group ID
	 * @return integer
	 */
	function get_last_order( $group_ID )
	{
		global $DB;

		$order = $DB->get_var( '
			SELECT MAX( ufdf_order )
			  FROM T_users__fielddefs
			 WHERE ufdf_ufgp_ID = '.$DB->quote( $group_ID ) );

		return $order + 1;
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		// Group
		$old_group_ID = $this->ufgp_ID; // Save old group ID to know if it was changed
		param_string_not_empty( 'ufdf_ufgp_ID', T_('Please select a group.') );
		$this->set_from_Request( 'ufgp_ID' );

		// Type
		param_string_not_empty( 'ufdf_type', T_('Please enter a type.') );
		$this->set_from_Request( 'type' );

		// User group
		if( $this->get( 'type' ) == 'user' )
		{	// Group is required for type "User select":
			$ufdf_grp_ID = param( 'ufdf_grp_ID', 'integer', NULL );
			if( $ufdf_grp_ID === NULL || ! is_pro() )
			{
				param_error( 'ufdf_grp_ID', 'Please select Group for field type "User select"' );
			}
			$this->set( 'grp_ID', ( empty( $ufdf_grp_ID ) ? NULL : $ufdf_grp_ID ), true );
		}
		else
		{	// Reset group for not user select type:
			$this->set( 'grp_ID', NULL, true );
		}

		// Code
		$code = param( 'ufdf_code', 'string' );
		param_check_not_empty( 'ufdf_code', T_('Please provide a code to uniquely identify this field.') );
		// Code MUST be lowercase ASCII only:
		param_check_regexp( 'ufdf_code', '#^[a-z0-9_]{1,20}$#', T_('The field code must contain only lowercase letters, digits or the "_" sign. 20 characters max.') );
		$this->set_from_Request( 'code' );

		// Name
		param_string_not_empty( 'ufdf_name', T_('Please enter a name.') );
		$this->set_from_Request( 'name' );

		// Icon name
		param( 'ufdf_icon_name', 'string' );
		$this->set_from_Request( 'icon_name', 'ufdf_icon_name', true );

		// Options
		if( param( 'ufdf_type', 'string' ) == 'list' )
		{ // Save 'Options' only for Field type == 'Option list'
			$ufdf_options = param( 'ufdf_options', 'text' );
			if( count( explode( "\n", $ufdf_options ) ) < 2 )
			{ // We don't want save an option list with one item
				param_error( 'ufdf_options', T_('Please enter at least 2 options on 2 different lines.') );
			}
			elseif( utf8_strlen( $ufdf_options ) > 255 )
			{ // This may not happen in normal circumstances because the textarea max length is set to 255 chars
				// This extra check is for the case if js is not enabled or someone would try to directly edit the html
				param_error( 'ufdf_options', T_('"Options" field content can not be longer than 255 symbols.') );
			}
			$this->set( 'options', $ufdf_options );
		}

		// Required
		param_string_not_empty( 'ufdf_required', 'Please select Hidden, Optional, Recommended or Required.' );
		$this->set_from_Request( 'required' );

		// Field visibility
		param_string_not_empty( 'ufdf_visibility', 'Please select Field visibility' );
		$this->set_from_Request( 'visibility' );

		// Duplicated
		param_string_not_empty( 'ufdf_duplicated', 'Please select Forbidden, Allowed or List style.' );
		$this->set_from_Request( 'duplicated' );

		// Order
		if( $old_group_ID != $this->ufgp_ID )
		{ // Group is changing, set order as last
			$this->set( 'order', $this->get_last_order( $this->ufgp_ID ) );
		}

		// Suggest
		if( param( 'ufdf_type', 'string' ) == 'word' )
		{ // Save 'Suggest values' only for Field type == 'Single word'
			param( 'ufdf_suggest', 'integer', 0 );
			$this->set_from_Request( 'suggest' );
		}

		// Bubbletip
		param( 'ufdf_bubbletip', 'text', '' );
		$this->set_from_Request( 'bubbletip', NULL, true );

		if( ! param_errors_detected() )
		{ // Field code must be unique, Check it only when no errors on the form
			if( $field_ID = $this->dbexists( 'ufdf_code', $this->get( 'code' ) ) )
			{ // We have a duplicate entry:
				param_error( 'ufdf_code',
					sprintf( T_('Another user field already uses this code. Do you want to <a %s>edit the existing user field</a>?'),
						'href="?ctrl=userfields&amp;action=edit&amp;ufdf_ID='.$field_ID.'"' ) );
			}
		}

		return ! param_errors_detected();
	}


	/**
	 * Set param value
	 *
	 * By default, all values will be considered strings
	 *
	 * @param string parameter name
	 * @param mixed parameter value
	 * @param boolean true to set to NULL if empty string value
	 */
	function set( $parname, $parvalue, $make_null = false )
	{
		switch( $parname )
		{
			case 'type':
			case 'name':
			case 'required':
			default:
				$this->set_param( $parname, 'string', $parvalue, $make_null );
		}
	}


	/**
	 * Get user field name.
	 *
	 * @return string user field name
	 */
	function get_name()
	{
		return $this->name;
	}


	/**
	 * Get label for input of the User Field
	 *
	 * @return string HTML code for user field input label
	 */
	function get_input_label()
	{
		return trim(
			// User field icon:
			get_userfield_icon( $this->get( 'icon_name' ), $this->get( 'code' ) ).' '
			// User field name:
			.$this->get( 'name' ).' '
			// User field visibility icon(blue/red lock):
			.get_userfield_visibility_icon( $this->get( 'visibility' ) ) );
	}
}
?>