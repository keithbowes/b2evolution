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
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $Collection, $Blog, $admin_url;

// Create query
$SQL = new SQL();
$SQL->SELECT( 't.*, IF( tb.itc_ityp_ID > 0, 1, 0 ) AS type_enabled, IF( ityp_ID = '.$Blog->get_setting( 'default_post_type' ).', 1, 0 ) AS type_default' );
$SQL->FROM( 'T_items__type AS t' );
$SQL->FROM_add( 'LEFT JOIN T_items__type_coll AS tb ON itc_ityp_ID = ityp_ID AND itc_coll_ID = '.$Blog->ID );

// Create result set:
$Results = new Results( $SQL->get(), 'ityp_' );

$Results->title = T_('Item Types').get_manual_link( 'managing-item-types' );

// get reserved and default ids
global $default_ids;
global $item_type;
$item_type = new ItemType();
$default_ids = $item_type->get_default_ids();

/**
 * Callback to build possible actions depending on item type id
 *
 */
function get_actions_for_itemtype( $id )
{
	global $default_ids, $admin_url;

	// Exit Item Type:
	$action = action_icon( T_('Edit this Item Type...'), 'edit',
		regenerate_url( 'action', 'ityp_ID='.$id.'&amp;action=edit' ) );

	// Copy Item Type:
	$action .= action_icon( T_('Duplicate this Item Type...'), 'copy',
		regenerate_url( 'action', 'ityp_ID='.$id.'&amp;action=new' ) );

	if( is_pro() && check_user_perm( 'options', 'edit' ) )
	{	// Export Item Type only for PRO version:
		$action .= action_icon( T_('Export this Item Type...'), 'download',
			$admin_url.'?ctrl=exportxml&amp;action=export_itemtype&amp;ityp_ID='.$id.'&amp;'.url_crumb( 'itemtype' ) );
	}

	if( ! in_array( $id, $default_ids ) )
	{	// Delete only the not default item types:
		$action .= action_icon( T_('Delete this Item Type!'), 'delete',
			regenerate_url( 'action', 'ityp_ID='.$id.'&amp;action=delete&amp;'.url_crumb( 'itemtype' ) ) );
	}

	return $action;
}

/**
 * Callback to make item type name depending on item type id
 *
 */
function get_name_for_itemtype( $id, $name )
{
	if( check_user_perm( 'options', 'edit' ) )
	{ // Not reserved id AND current User has permission to edit the global settings
		$ret_name = '<a href="'.regenerate_url( 'action,ID', 'ityp_ID='.$id.'&amp;action=edit' ).'">'.$name.'</a>';
	}
	else
	{
		$ret_name = $name;
	}

	return '<strong>'.$ret_name.'</strong>';
}


$Results->cols[] = array(
		'th' => T_('ID'),
		'order' => 'ityp_ID',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
		'td' => '$ityp_ID$',
	);

function ityp_row_enabled( $enabled, $item_type_ID )
{
	global $admin_url, $Collection, $Blog;

	$perm_edit = check_user_perm( 'options', 'edit', false );

	if( $enabled )
	{ // Enabled
		if( $perm_edit && $Blog->can_be_item_type_disabled( $item_type_ID ) )
		{ // URL to disable the item type
			$status_url = $admin_url.'?ctrl=itemtypes&amp;action=disable&amp;ityp_ID='.$item_type_ID.'&amp;blog='.$Blog->ID.'&amp;'.url_crumb( 'itemtype' );
		}
		$status_icon = get_icon( 'bullet_green', 'imgtag', array( 'title' => TB_('The item type is enabled.') ) );
	}
	else
	{ // Disabled
		if( $perm_edit )
		{ // URL to enable the item type
			$status_url = $admin_url.'?ctrl=itemtypes&amp;action=enable&amp;ityp_ID='.$item_type_ID.'&amp;blog='.$Blog->ID.'&amp;'.url_crumb( 'itemtype' );
		}
		if( $Blog->has_items_per_item_type( $item_type_ID ) )
		{	// Use orange icon if collection has at least one Item per this disabled Item Type:
			$status_icon = get_icon( 'bullet_orange', 'imgtag', array( 'title' => TB_('Disabled but used by some Items in this collection.') ) );
		}
		else
		{	// Use "grey empty" icon if collection has no Items per this disabled Item Type:
			$status_icon = get_icon( 'bullet_empty_grey', 'imgtag', array( 'title' => TB_('The item type is disabled.') ) );
		}
	}

	if( isset( $status_url ) )
	{
		return '<a href="'.$status_url.'">'.$status_icon.'</a>';
	}
	else
	{
		return $status_icon;
	}
}
$Results->cols[] = array(
		'th' => sprintf( T_('Enabled in<br />%s'), $Blog->get( 'shortname' ) ),
		'order' => 'type_enabled',
		'default_dir' => 'D',
		'td' => '%ityp_row_enabled( #type_enabled#, #ityp_ID# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'center',
	);

function ityp_row_default( $item_type_ID )
{
	global $admin_url, $Collection, $Blog;

	if( $Blog->get_setting( 'default_post_type' ) == $item_type_ID )
	{ // The item type is default for current collection:
		$status_icon = get_icon( 'bullet_black', 'imgtag', array( 'title' => sprintf( T_('The item type is the default for %s.'), $Blog->get( 'shortname' ) ) ) );
	}
	else
	{ // The item type is not default:
		if( check_user_perm( 'blog_properties', 'edit', false, $Blog->ID ) )
		{ // URL to use the item type as default if current user has a permission to edit collection properties:
			$status_url = $admin_url.'?ctrl=itemtypes&amp;action=default&amp;ityp_ID='.$item_type_ID.'&amp;blog='.$Blog->ID.'&amp;'.url_crumb( 'itemtype' );
			$status_icon_title = sprintf( T_('Set this item type as the default for %s.'), $Blog->get( 'shortname' ) );
		}
		else
		{
			$status_icon_title = sprintf( T_('The item type is not the default for %s.'), $Blog->get( 'shortname' ) );
		}
		$status_icon = get_icon( 'bullet_empty_grey', 'imgtag', array( 'title' => $status_icon_title ) );
	}

	if( isset( $status_url ) )
	{
		return '<a href="'.$status_url.'">'.$status_icon.'</a>';
	}
	else
	{
		return $status_icon;
	}
}
$Results->cols[] = array(
		'th' => sprintf( T_('Default for<br />%s'), $Blog->get( 'shortname' ) ),
		'order' => 'type_default',
		'default_dir' => 'D',
		'td' => '%ityp_row_default( #ityp_ID# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'center',
	);

function ityp_row_usage( $item_type_usage )
{
	switch( $item_type_usage )
	{
		case 'post':
			return /* TRANS: noun */ T_('Post');
		case 'page':
			return T_('Content Page');
		case 'widget-page':
			return T_('Widget Page');
		case 'intro-front':
			return T_('Intro-Front');
		case 'intro-main':
			return T_('Intro-Main');
		case 'intro-cat':
			return T_('Intro-Cat');
		case 'intro-tag':
			return T_('Intro-Tag');
		case 'intro-sub':
			return T_('Intro-Sub');
		case 'intro-all':
			return T_('Intro-All');
		case 'special':
			return T_('Special');
		case 'content-block':
			return T_('Content Block');
		default:
			return $item_type_usage;
	}
}
$Results->cols[] = array(
		'th' => T_('Usage'),
		'order' => 'ityp_usage',
		'td' => '%ityp_row_usage( #ityp_usage# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'nowrap',
	);

$Results->cols[] = array(
		'th' => T_('Name'),
		'order' => 'ityp_name',
		'td' => '%get_name_for_itemtype(#ityp_ID#, #ityp_name#)%',
	);

function ityp_row_perm_level( $level, $id )
{
	$perm_levels = array(
			'standard'   => T_('Standard'),
			'restricted' => T_('Restricted'),
			'admin'      => T_('Admin')
		);

	return isset( $perm_levels[ $level ] ) ? $perm_levels[ $level ] : $level;
}
$Results->cols[] = array(
		'th' => T_('Perm Level'),
		'order' => 'ityp_perm_level',
		'td' => '%ityp_row_perm_level( #ityp_perm_level#, #ityp_ID# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'center',
	);

$Results->cols[] = array(
		'th' => T_('Template name'),
		'order' => 'ityp_template_name',
		'td' => '%conditional( #ityp_template_name# == "", "", #ityp_template_name#.".*.php" )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'center',
	);

if( check_user_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
	$Results->cols[] = array(
							'th' => T_('Actions'),
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							'td' => '%get_actions_for_itemtype( #ityp_ID# )%',
						);

	$Results->global_icon( T_('Import Item Type'), 'import',
		$admin_url.'?ctrl=itimport&amp;it_blog_IDs[]='.$Blog->ID, T_('Import Item Type').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-default' ) );

	$Results->global_icon( T_('Create a new element...'), 'new',
				regenerate_url( 'action', 'action=new' ), T_('New Item Type').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );
}

// Display results:
$Results->display();

?>
