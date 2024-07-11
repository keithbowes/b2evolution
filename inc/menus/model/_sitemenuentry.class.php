<?php
/**
 * This file implements the Site Menu class.
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

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );


/**
 * Menu Entry Class
 *
 * @package evocore
 */
class SiteMenuEntry extends DataObject
{
	var $menu_ID;
	var $parent_ID;
	var $order;
	var $user_pic_size;
	var $text;
	var $type;
	var $coll_logo_size;
	var $coll_ID;
	var $cat_ID;
	var $item_ID;
	var $item_slug;
	var $url;
	var $visibility = 'always';
	var $access = 'perms';
	var $show_badge = 1;
	var $highlight = 1;
	var $hide_empty = 0;
	var $class;

	/**
	 * Error message if current User has no access to requested URL
	 * Useful when it is used from widget Menu
	 */
	var $url_error = NULL;

	/**
	 * Collection
	 */
	var $Blog = NULL;

	/**
	 * Chapter
	 */
	var $Chapter = NULL;

	/**
	 * Item
	 */
	var $Item = NULL;

	/**
	 * Site Menu Entry children list
	 */
	var $children = array();
	var $children_sorted = false;

	/**
	 * Constructor
	 *
	 * @param object table Database row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_menus__entry', 'ment_', 'ment_ID' );

		if( $db_row != NULL )
		{	// Get menu entry data from DB:
			$this->ID = $db_row->ment_ID;
			$this->menu_ID = $db_row->ment_menu_ID;
			$this->parent_ID = $db_row->ment_parent_ID;
			$this->order = $db_row->ment_order;
			$this->user_pic_size = $db_row->ment_user_pic_size;
			$this->text = $db_row->ment_text;
			$this->type = $db_row->ment_type;
			$this->coll_logo_size = $db_row->ment_coll_logo_size;
			$this->coll_ID = $db_row->ment_coll_ID;
			$this->cat_ID = $db_row->ment_cat_ID;
			$this->item_ID = $db_row->ment_item_ID;
			$this->item_slug = $db_row->ment_item_slug;
			$this->url = $db_row->ment_url;
			$this->visibility = $db_row->ment_visibility;
			$this->access = $db_row->ment_access;
			$this->show_badge = $db_row->ment_show_badge;
			$this->highlight = $db_row->ment_highlight;
			$this->hide_empty = $db_row->ment_hide_empty;
			$this->class = $db_row->ment_class;
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
				array( 'table' => 'T_menus__entry', 'fk' => 'ment_menu_ID', 'msg' => T_('%d menu entries') ),
			);
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		// Menu:
		param( 'ment_menu_ID', 'integer' );
		param_check_not_empty( 'ment_menu_ID', T_('Please select menu!') );
		$this->set_from_Request( 'menu_ID' );

		// Parent:
		param( 'ment_parent_ID', 'integer', NULL );
		$this->set_from_Request( 'parent_ID', NULL, true );

		// Order:
		param( 'ment_order', 'integer', NULL );
		$this->set_from_Request( 'order', NULL, true );

		// Profile picture before text:
		param( 'ment_user_pic_size', 'string' );
		$this->set_from_Request( 'user_pic_size' );

		// Text:
		param( 'ment_text', 'string' );
		$this->set_from_Request( 'text' );

		// Type:
		param( 'ment_type', 'string' );
		$this->set_from_Request( 'type' );

		// Collection logo size:
		param( 'ment_coll_logo_size', 'string' );
		$this->set_from_Request( 'coll_logo_size' );

		// Collection ID:
		param( 'ment_coll_ID', 'integer', NULL );
		$this->set_from_Request( 'coll_ID', NULL, true );

		// Category ID:
		param( 'ment_cat_ID', 'integer', NULL );
		$this->set_from_Request( 'cat_ID', NULL, true );

		// Item ID:
		$item_ID = param( 'ment_item_ID', 'integer', NULL );
		if( $item_ID )
		{
			$ItemCache = & get_ItemCache();
			$menu_Item_from_ID = & $ItemCache->get_by_ID( $item_ID, false, false );
			if( ! $menu_Item_from_ID )
			{
				param_error( 'ment_item_ID', sprintf( T_('The Item %s doesn\'t exist.'), '<code>'.$item_ID.'</code>' ) );
			}
			elseif( in_array( $menu_Item_from_ID->get_type_setting( 'usage' ), array( 'special', 'content-block' ) ) )
			{
				param_error( 'ment_item_ID', sprintf( T_('Items with usage type %s is not allowed.'), '<code>'.$menu_Item_from_ID->get_type_setting( 'usage' ).'</code>' ) );
			}
		}
		$this->set_from_Request( 'item_ID', NULL, true );

		// Item Slug:
		$item_slug = param( 'ment_item_slug', 'string', NULL );
		if( $item_slug )
		{
			$ItemCache = & get_ItemCache();
			$menu_Item_from_slug = & $ItemCache->get_by_urltitle( $item_slug, false, false );
			if( ! $menu_Item_from_slug )
			{
				param_error( 'ment_item_slug', sprintf( T_('The Item %s doesn\'t exist.'), '<code>'.$item_slug.'</code>' ) );
			}
			elseif( in_array( $menu_Item_from_slug->get_type_setting( 'usage' ), array( 'special', 'content-block' ) ) )
			{
				param_error( 'ment_item_slug', sprintf( T_('Items with usage type %s is not allowed.'), '<code>'.$menu_Item_from_slug->get_type_setting( 'usage' ).'</code>' ) );
			}
		}
		$this->set_from_Request( 'item_slug', NULL, true );

		// URL:
		param( 'ment_url', 'url' );
		$this->set_from_Request( 'url' );

		// Visibility:
		param( 'ment_visibility', 'string' );
		$this->set_from_Request( 'visibility' );

		// Show to:
		param( 'ment_access', 'string' );
		$this->set_from_Request( 'access' );

		// Show badge:
		param( 'ment_show_badge', 'integer', 0 );
		$this->set_from_Request( 'show_badge' );

		// Highlight:
		param( 'ment_highlight', 'integer', 0 );
		$this->set_from_Request( 'highlight' );

		// Hide if empty:
		param( 'ment_hide_empty', 'integer', 0 );
		$this->set_from_Request( 'hide_empty' );

		// Extra CSS classes:
		param( 'ment_class', 'string', NULL );
		$this->set_from_Request( 'class', NULL, true );

		if( ! empty( $menu_Item_from_ID ) && ! empty( $menu_Item_from_slug ) && ( $menu_Item_from_ID->ID != $menu_Item_from_slug->ID ) )
		{
			global $Messages;

			$Messages->add( T_('You entered both an Item ID and Item slug. Only Item ID will be used. Item slug will be ignored.'), 'warning' );
		}


		return ! param_errors_detected();
	}


	/**
	 * Get name of Menu Entry
	 *
	 * @return string Menu Entry
	 */
	function get_name()
	{
		return $this->get( 'text' );
	}


	/**
	 * Add a child
	 *
	 * @param object SiteMenuEntry
	 */
	function add_child_entry( & $SiteMenuEntry )
	{
		if( !isset( $this->children[ $SiteMenuEntry->ID ] ) )
		{	// Add only if it was not added yet:
			$this->children[ $SiteMenuEntry->ID ] = & $SiteMenuEntry;
		}
	}


	/**
	 * Sort Site Menu Entry childen
	 */
	function sort_children()
	{
		if( $this->children_sorted )
		{ // Site Menu Entry children list is already sorted
			return;
		}

		// Sort children list
		uasort( $this->children, array( 'SiteMenuEntryCache','compare_site_menu_entries' ) );
	}


	/**
	 * Get children/sub-entires of this category
	 *
	 * @param boolean set to true to sort children, leave false otherwise
	 * @return array of SiteMenuEntries - children of this SiteMenuEntry
	 */
	function get_children( $sorted = false )
	{
		$SiteMenuEntryCache = & get_SiteMenuEntryCache();
		$SiteMenuEntryCache->reveal_children( $this->get( 'menu_ID' ), $sorted );

		$parent_SiteMenuEntry = & $SiteMenuEntryCache->get_by_ID( $this->ID );
		if( $sorted )
		{	// Sort child entries:
			$parent_SiteMenuEntry->sort_children();
		}

		return $parent_SiteMenuEntry->children;
	}


	/**
	 * Get Collection
	 *
	 * @return object|NULL|false Collection
	 */
	function & get_Blog()
	{
		if( $this->Blog === NULL )
		{	// Load collection once:
			$BlogCache = & get_BlogCache();
			$this->Blog = & $BlogCache->get_by_ID( $this->get( 'coll_ID' ), false, false );

			if( empty( $this->Blog ) )
			{	// Use current Collection if it is not defined or it doesn't exist in DB:
				global $Blog;
				if( isset( $Blog ) )
				{
					$this->Blog = & $Blog;
				}
			}
		}

		return $this->Blog;
	}


	/**
	 * Get Chapter
	 *
	 * @return object|NULL|false Chapter
	 */
	function & get_Chapter()
	{
		if( $this->Chapter === NULL )
		{	// Load collection once:
			$ChapterCache = & get_ChapterCache();
			$this->Chapter = & $ChapterCache->get_by_ID( $this->get( 'cat_ID' ), false, false );
		}

		return $this->Chapter;
	}


	/**
	 * Get Item
	 *
	 * @return object|NULL|false Item
	 */
	function & get_Item()
	{
		if( $this->Item === NULL )
		{	// Load collection once:
			$ItemCache = & get_ItemCache();
			if( $this->get( 'item_ID' ) )
			{	// Item ID has priority because it is faster to resolve:
				$this->Item = & $ItemCache->get_by_ID( $this->get( 'item_ID' ), false, false );
			}
			elseif( $this->get( 'item_slug' ) )
			{	// Use item slug only if there is no Item ID specified:
				$this->Item = & $ItemCache->get_by_urltitle( $this->get( 'item_slug' ), false, false );
			}
		}

		return $this->Item;
	}


	/**
	 * Get Menu Entry Text based on type
	 *
	 * @param boolean TRUE to force to default value
	 * @return string
	 */
	function get_text( $force_default = false )
	{
		global $thumbnail_sizes, $current_User;

		$entry_Blog = & $this->get_Blog();

		if( ! $force_default && $this->get( 'text' ) != '' )
		{	// Use custom text:
			$text = $this->get( 'text' );
		}
		else
		{	// Use default text:
			switch( $this->get( 'type' ) )
			{
				case 'home':
					$text = T_('Front Page');
					break;

				case 'recentposts':
					if( $entry_Chapter = & $this->get_Chapter() )
					{	// Use category name instead of default if the defined category is found in DB:
						$text = $entry_Chapter->get( 'name' );
					}
					else
					{
						$text = T_('Recently');
					}
					break;

				case 'search':
					$text = T_('Search');
					break;

				case 'arcdir':
					$text = T_('Archives');
					break;

				case 'catdir':
					$text = T_('Categories');
					break;

				case 'tags':
					$text = T_('Tags');
					break;

				case 'postidx':
					$text = T_('Post index');
					break;

				case 'mediaidx':
					$text = T_('Photo index');
					break;

				case 'sitemap':
					$text = T_('Site map');
					break;

				case 'latestcomments':
					$text = T_('Latest comments');
					break;

				case 'owneruserinfo':
					$text = T_('Owner details');
					break;

				case 'ownercontact':
					$text = T_('Contact');
					break;

				case 'login':
					$text = T_('Log in');
					break;

				case 'logout':
					$text = T_('Log out');
					break;

				case 'register':
					$text = T_('Register');
					break;

				case 'profile':
					$text = T_('Edit profile');
					break;

				case 'avatar':
					$text = T_('Profile picture');
					break;

				case 'visits':
					$text = T_('My visits');
					if( is_logged_in() )
					{
						$text .= ' <span class="badge badge-info">'.$current_User->get_profile_visitors_count().'</span>';
					}
					break;

				case 'useritems':
					$text = T_('User\'s posts/items');
					break;

				case 'usercomments':
					$text = T_('User\'s usercomments');
					break;

				case 'users':
					$text = T_('User directory');
					break;

				case 'item':
					$entry_Item = & $this->get_Item();
					if( $entry_Item && $entry_Item->can_be_displayed() )
					{	// Item is not found or it cannot be displayed for current user on front-office:
						$text = $entry_Item->get( 'title');
					}
					else
					{
						$text = '[NOT FOUND]';
					}
					break;

				case 'url':
					$text = $this->get( 'url' );
					break;

				case 'postnew':
					if( ( $entry_Chapter = & $this->get_Chapter() ) &&
					    ( $cat_ItemType = & $entry_Chapter->get_ItemType( true ) ) )
					{	// Use text depending on default category's Item Type:
						$text = $cat_ItemType->get_item_denomination( 'inskin_new_btn' );
					}
					else
					{
						$text = T_('Write a new post');
					}
					break;

				case 'myprofile':
					$text = '$username$';
					break;

				case 'admin':
					$text = T_('Admin').' &raquo;';
					break;

				case 'messages':
					$text = T_('Messages');
					break;

				case 'contacts':
					$text = T_('Contacts');
					break;

				case 'flagged':
					$text = T_('Flagged Items');
					break;

				default:
					$text = '[UNKNOWN]';
			}
		}

		// Replace masks:
		$text = preg_replace_callback( '#\$([a-z]+)\$#', array( $this, 'callback_text_mask' ), $text );

		// Profile picture before text:
		if( in_array( $this->get( 'type' ), array( 'logout', 'myprofile', 'visits', 'profile', 'avatar', 'useritems', 'usercomments' ) ) &&
		    is_logged_in() &&
		    ( $user_pic_size = $this->get( 'user_pic_size' ) ) &&
		    isset( $thumbnail_sizes[ $user_pic_size ] ) )
		{
			$text = $current_User->get_avatar_imgtag( $user_pic_size, 'avatar_before_login_middle' ).$text;
		}

		// Collection logo before link text:
		if( ! in_array( $this->get( 'type' ), array( 'item', 'admin', 'url', 'text' ) ) &&
				( $coll_logo_size = $this->get( 'coll_logo_size' ) ) &&
				isset( $thumbnail_sizes[ $coll_logo_size ] ) &&
				( $coll_logo_File = $entry_Blog->get( 'collection_image' ) ) )
		{
			$text = $coll_logo_File->get_thumb_imgtag( $coll_logo_size ).' '.$text;
		}

		// Badge with count of unread messages or flagged items:
		if( $this->get( 'show_badge' ) )
		{
			switch( $this->get( 'type' ) )
			{
				case 'messages':
					// Show badge with count of uread messages:
					$unread_messages_count = get_unread_messages_count();
					if( $unread_messages_count > 0 )
					{	// If at least one unread message:
						$text .= ' <span class="badge badge-important">'.$unread_messages_count.'</span>';
					}
					break;

				case 'flagged':
					// Show badge with count of flagged items:
					$flagged_items_count = $current_User->get_flagged_items_count( $entry_Blog->ID );
					if( $flagged_items_count > 0 )
					{	// If at least one flagged item:
						$text .= ' <span class="badge badge-warning">'.$flagged_items_count.'</span>';
					}
					break;
			}
		}

		return $text;
	}


	/**
	 * Callback function to replace masks in menu text
	 *
	 * @param array Matches
	 * @return string Text with replaced masks to proper values
	 */
	function callback_text_mask( $m )
	{
		global $current_User;

		switch( $m[0] )
		{
			case '$username$':
				return is_logged_in()
					? $current_User->get_colored_login( array( 'login_text' => 'name' ) )
					: '('.T_('anonymous').')';

			case '$login$':
				return is_logged_in()
					? $current_User->get_colored_login( array( 'login_text' => 'login' ) )
					: '('.T_('anonymous').')';
		}

		return $m[0];
	}


	/**
	 * Get Menu Entry URL based on type
	 *
	 * @return string|boolean URL or FALSE on unknown type
	 */
	function get_url()
	{
		$entry_Blog = & $this->get_Blog();

		if( empty( $entry_Blog ) )
		{	// We cannot use this menu entry without current collection:
			$this->url_error = 'No Collection';
			return false;
		}

		if( $this->get( 'visibility' ) == 'access' && ! $entry_Blog->has_access() )
		{	// Don't use this menu entry because current user has no access to the collection:
			$this->url_error = 'No access';
			return false;
		}

		switch( $this->get( 'type' ) )
		{
			case 'home':
				return $entry_Blog->get( 'url' );

			case 'recentposts':
				if( ! $entry_Blog->get_setting( 'postlist_enable' ) )
				{	// This page is disabled:
					$this->url_error = 'Disabled';
					return false;
				}
				if( $entry_Chapter = & $this->get_Chapter() )
				{	// Use category url instead of default if the defined category is found in DB:
					return $entry_Chapter->get_permanent_url();
				}
				return $entry_Blog->get( 'recentpostsurl' );

			case 'search':
				if( ! $entry_Blog->get_setting( 'search_enable' ) )
				{	// This page is disabled:
					$this->url_error = 'Disabled';
					return false;
				}
				return $entry_Blog->get( 'searchurl' );

			case 'arcdir':
				return $entry_Blog->get( 'arcdirurl' );

			case 'catdir':
				return $entry_Blog->get( 'catdirurl' );

			case 'tags':
				return $entry_Blog->get( 'tagsurl' );

			case 'postidx':
				return $entry_Blog->get( 'postidxurl' );

			case 'mediaidx':
				return $entry_Blog->get( 'mediaidxurl' );

			case 'sitemap':
				return $entry_Blog->get( 'sitemapurl' );

			case 'latestcomments':
				if( ! $entry_Blog->get_setting( 'comments_latest' ) )
				{	// This page is disabled:
					$this->url_error = 'Disabled';
					return false;
				}
				return $entry_Blog->get( 'lastcommentsurl' );

			case 'owneruserinfo':
				return $entry_Blog->get( 'userurl', array( 'user_ID' => $entry_Blog->owner_user_ID ) );

			case 'ownercontact':
				return $entry_Blog->get_contact_url();

			case 'login':
				if( is_logged_in() )
				{	// Don't display this link for already logged in users:
					$this->url_error = 'Not logged in';
					return false;
				}
				global $Settings;
				return get_login_url( 'menu link', $Settings->get( 'redirect_to_after_login' ), false, $entry_Blog->ID );

			case 'logout':
				if( ! is_logged_in() )
				{	// Current user must be logged in:
					$this->url_error = 'Not logged in';
					return false;
				}
				return get_user_logout_url( $entry_Blog->ID );

			case 'register':
				return get_user_register_url( NULL, 'menu link', false, '&amp;', $entry_Blog->ID );

			case 'profile':
				if( ! is_logged_in() )
				{	// Current user must be logged in:
					$this->url_error = 'Not logged in';
					return false;
				}
				return get_user_profile_url( $entry_Blog->ID );

			case 'avatar':
				if( ! is_logged_in() )
				{	// Current user must be logged in:
					$this->url_error = 'Not logged in';
					return false;
				}
				return get_user_avatar_url( $entry_Blog->ID );

			case 'password':
				if( ! is_logged_in() )
				{	// Current user must be logged in:
					$this->url_error = 'Not logged in';
					return false;
				}
				return get_user_pwdchange_url( $entry_Blog->ID );

			case 'userprefs':
				if( ! is_logged_in() )
				{	// Current user must be logged in:
					$this->url_error = 'Not logged in';
					return false;
				}
				return get_user_preferences_url( $entry_Blog->ID );

			case 'usersubs':
				if( ! is_logged_in() )
				{	// Current user must be logged in:
					$this->url_error = 'Not logged in';
					return false;
				}
				return get_user_subs_url( $entry_Blog->ID );

			case 'visits':
				global $Settings, $current_User;
				if( ! is_logged_in() || ! $Settings->get( 'enable_visit_tracking' ) )
				{	// Current user must be logged in and visit tracking must be enabled:
					return false;
				}

				return $current_User->get_visits_url( $entry_Blog->ID );

			case 'useritems':
				if( ! is_logged_in() )
				{	// Don't allow anonymous users to see items list:
					return false;
				}
				return url_add_param( $entry_Blog->gen_blogurl(), 'disp=useritems' );

			case 'usercomments':
				if( ! is_logged_in() )
				{	// Don't allow anonymous users to see comments list:
					return false;
				}
				return url_add_param( $entry_Blog->gen_blogurl(), 'disp=usercomments' );

			case 'users':
				global $Settings, $user_ID;
				if( ! is_logged_in() && ! $Settings->get( 'allow_anonymous_user_list' ) )
				{	// Don't allow anonymous users to see users list:
					return false;
				}
				return $entry_Blog->get( 'usersurl' );

			case 'item':
				$entry_Item = & $this->get_Item();
				if( ! $entry_Item || ! $entry_Item->can_be_displayed() )
				{	// Item is not found or it cannot be displayed for current user on front-office:
					return false;
				}
				if( ! empty( $this->item_ID ) )
				{	// Item ID is specified and has priority:
					return $entry_Item->get_permanent_url();
				}
				else
				{	// No Item ID specified, use item slug:
					return url_add_tail( $entry_Blog->get( 'url' ), '/'.$this->item_slug );
				}

			case 'url':
				$entry_url = $this->get( 'url' );
				return ( empty( $entry_url ) ? false : $entry_url );

			case 'postnew':
				if( ! check_item_perm_create( $entry_Blog ) )
				{	// Don't allow users to create a new post:
					return false;
				}
				$url = url_add_param( $entry_Blog->get( 'url' ), 'disp=edit' );
				if( $entry_Chapter = & $this->get_Chapter() )
				{	// Append category ID to the URL:
					$url = url_add_param( $url, 'cat='.$entry_Chapter->ID );
					$cat_ItemType = & $entry_Chapter->get_ItemType( true );
					if( $cat_ItemType === false )
					{	// Don't allow to create a post in this category because this category has no default Item Type:
						return false;
					}
					if( $cat_ItemType )
					{	// Append item type ID to the URL:
						$url = url_add_param( $url, 'item_typ_ID='.$cat_ItemType->ID );
					}
				}
				return $url;
				break;

			case 'myprofile':
				if( ! is_logged_in() )
				{	// Don't show this link for not logged in users:
					return false;
				}
				global $current_User;
				return $entry_Blog->get( 'userurl', array( 'user_ID' => $current_User->ID, 'user_login' => $current_User->login ) );
				break;

			case 'admin':
				if( ! check_user_perm( 'admin', 'restricted' ) && check_user_status( 'can_access_admin' ) )
				{	// Don't allow admin url for users who have no access to backoffice:
					return false;
				}
				global $admin_url;
				return $admin_url;

			case 'messages':
			case 'contacts':
				switch( $this->get( 'access' ) )
				{
					case 'loggedin':
						if( ! is_logged_in() )
						{	// User is not logged in:
							$this->url_error = 'Not logged in';
							return false;
						}
						break;
					case 'perms':
						if( ! check_user_perm( 'perm_messaging', 'reply', false ) )
						{	// User has no access for messaging:
							$this->url_error = 'No access';
							return false;
						}
						break;
				}
				return $this->get( 'type' ) == 'messages'
					// Messages:
					? $entry_Blog->get( 'threadsurl' )
					// Contacts:
					: $entry_Blog->get( 'contactsurl' );

			case 'flagged':
				if( ! is_logged_in() )
				{	// Only logged in user can flag items:
					$this->url_error = 'Not logged in';
					return false;
				}
				global $current_User;
				if( $this->get( 'hide_empty' ) && $current_User->get_flagged_items_count() == 0 )
				{	// Hide this menu if current user has no flagged posts yet:
					$this->url_error = 'No flagged posts';
					return false;
				}
				return $entry_Blog->get( 'flaggedurl' );
		}

		return false;
	}


	/**
	 * Check if Menu Entry is active
	 *
	 * @return boolean
	 */
	function is_active()
	{
		global $Blog, $disp;

		if( ! $this->get( 'highlight' ) )
		{	// Don't highlight this menu entry:
			return false;
		}

		// Get current collection ID:
		$current_blog_ID = isset( $Blog ) ? $Blog->ID : NULL;

		// Get collection of this Menu Entry:
		$entry_Blog = & $this->get_Blog();

		if( $current_blog_ID != $entry_Blog->ID )
		{	// This is a different collection than defined in this Menu Entry:
			return false;
		}

		switch( $this->get( 'type' ) )
		{
			case 'home':
				global $is_front;
				return ( $disp == 'front' || ! empty( $is_front ) );

			case 'recentposts':
				global $cat;
				$entry_Chapter = & $this->get_Chapter();
				return ( $disp == 'posts' && ( empty( $entry_Chapter ) || $cat == $entry_Chapter->ID ) );

			case 'search':
				return ( $disp == 'search' );

			case 'arcdir':
				return ( $disp == 'arcdir' );

			case 'catdir':
				return ( $disp == 'catdir' );

			case 'tags':
				return ( $disp == 'tags' );

			case 'postidx':
				return ( $disp == 'postidx' );

			case 'mediaidx':
				return ( $disp == 'mediaidx' );

			case 'sitemap':
				return ( $disp == 'sitemap' );

			case 'latestcomments':
				return ( $disp == 'comments' );

			case 'owneruserinfo':
				global $User;
				return ( $disp == 'user' && ! empty( $User ) && $User->ID == $entry_Blog->owner_user_ID );

			case 'ownercontact':
				return ( $disp == 'msgform' || ( isset( $_GET['disp'] ) && $_GET['disp'] == 'msgform' ) );

			case 'login':
				return ( $disp == 'login' );

			case 'logout':
				// This is never highlighted:
				return false;

			case 'register':
				return ( $disp == 'register' );

			case 'profile':
				return in_array( $disp, array( 'profile', 'avatar', 'pwdchange', 'userprefs', 'subs' ) );

			case 'avatar':
				// Note: we never highlight this, it will always highlight 'profile' instead:
				return false;

			case 'visits':
				return ( $disp == 'visits' );

			case 'useritems':
				return ( $disp == 'useritems' );

			case 'usercomments':
				return ( $disp == 'usercomments' );

			case 'users':
				global $user_ID;
				// Note: If $user_ID is not set, it means we are viewing "My Profile" instead
				return ( $disp == 'users' || ( $disp == 'user' && ! empty( $user_ID ) ) );

			case 'item':
				global $Item;
				$entry_Item = & $this->get_Item();
				return ( ! empty( $Item ) && $entry_Item->ID == $Item->ID );

			case 'url':
				// Note: we never highlight this link
				return false;

			case 'postnew':
				global $cat;
				$entry_Chapter = & $this->get_Chapter();
				return ( $disp == 'edit' && ( empty( $Chapter ) || $cat == $entry_Chapter->ID ) );

			case 'myprofile':
				global $user_ID;
				return ( $disp == 'user' && empty( $user_ID ) );

			case 'admin':
				// This is never highlighted:
				return false;

			case 'messages':
				return $disp == 'messages' || ( $disp == 'threads' && ( ! isset( $_GET['disp'] ) || $_GET['disp'] != 'msgform' ) );

			case 'contacts':
				return $disp == 'contacts';

			case 'flagged':
				return $disp == 'flagged';
		}

		return false;
	}
}
?>
