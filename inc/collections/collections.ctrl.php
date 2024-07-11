<?php
/**
 * This file implements the UI controller for blog params management, including permissions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 *
 * @todo (sessions) When creating a blog, provide "edit options" (3 tabs) instead of a single long "New" form (storing the new Blog object with the session data).
 * @todo Currently if you change the name of a blog it gets not reflected in the blog list buttons!
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


param( 'tab', 'string', 'list', true );
param( 'skin_type', 'string', 'normal' );

param_action( 'list' );

if( strpos( $action, 'new' ) !== false || $action == 'copy' )
{ // Simulate tab to value 'new' for actions to create new blog
	$tab = 'new';
}
if( ! in_array( $action, array( 'list', 'new', 'new-selskin', 'new-installskin', 'new-name', 'create', 'update_settings_blog', 'update_settings_site', 'new_section', 'edit_section', 'delete_section', 'update_site_skin', 'create_demo_content' ) ) &&
    ! in_array( $tab, array( 'site_settings', 'site_skin' ) ) )
{
	if( valid_blog_requested() )
	{
		// echo 'valid blog requested';
		$edited_Blog = & $Blog;
	}
	else
	{
		// echo 'NO valid blog requested';
		$action = 'list';
	}
}

if( strpos( $action, 'section' ) !== false )
{	// Initialize Section object:
	load_class( 'collections/model/_section.class.php', 'Section' );

	param( 'sec_ID', 'integer', 0 );

	$tab = 'section';

	if( $sec_ID > 0 )
	{	// Try to get the existing section by requested ID:
		$SectionCache = & get_SectionCache();
		$edited_Section = & $SectionCache->get_by_ID( $sec_ID );
	}
	else
	{	// Create new section object:
		$edited_Section = new Section();
	}
}

/**
 * Perform action:
 */
switch( $action )
{
	case 'new':
		// New collection: Select blog type
		param( 'sec_ID', 'integer', 0, true );
	case 'copy':
		// Copy collection:

		if( empty( $sec_ID ) )
		{
			if( isset( $edited_Blog ) )
			{
				$sec_ID = $edited_Blog->sec_ID;
				memorize_param( 'sec_ID', 'integer', $sec_ID );
			}
			else
			{
				$sec_ID = 0;
			}
		}

		// Check permissions to create new collection:
		if( ! check_user_perm( 'blogs', 'create', false, $sec_ID ) )
		{
			$Messages->add( TB_('You don\'t have permission to create a collection.'), 'error' );
			$redirect_to = param( 'redirect_to', 'url', $admin_url );
			header_redirect( $redirect_to );
		}

		// Check permissions to copy the selected collection:
		if( $action == 'copy' && ! check_user_perm( 'blog_properties', 'copy', false, $edited_Blog->ID ) )
		{
			$Messages->add( sprintf( TB_('You don\'t have a permission to copy the collection "%s".'), $edited_Blog->get( 'shortname' ) ), 'error' );
			$redirect_to = param( 'redirect_to', 'url', $admin_url );
			header_redirect( $redirect_to );
		}

		$user_Group = $current_User->get_Group();
		$max_allowed_blogs = $user_Group->get_GroupSettings()->get( 'perm_max_createblog_num', $user_Group->ID );
		$user_blog_count = $current_User->get_num_blogs();

		if( $max_allowed_blogs != '' && $max_allowed_blogs <= $user_blog_count )
		{
			$Messages->add( sprintf( TB_('You already own %d collection/s. You are not currently allowed to create any more.'), $user_blog_count ) );
			$redirect_to = param( 'redirect_to', 'url', $admin_url );
			header_redirect( $redirect_to );
		}

		if( $action == 'copy' )
		{	// Get name of the duplicating collection to display on the form:
			$duplicating_collection_name = $edited_Blog->get( 'shortname' );
		}

		$AdminUI->append_path_level( 'new', array( 'text' => TB_('New') ) );
		break;

	case 'new-selskin':
	case 'new-installskin':
		// New collection: Select or Install skin

		param( 'sec_ID', 'integer', 0, true );

		// Check permissions:
		check_user_perm( 'blogs', 'create', true, $sec_ID );

		param( 'kind', 'string', true );

		$AdminUI->append_path_level( 'new', array( 'text' => sprintf( /* TRANS: %s can become "Standard blog", "Photoblog", "Group blog" or "Forum" */ TB_('New "%s" collection'), get_collection_kinds($kind) ) ) );
		break;

	case 'new-name':
		// New collection: Set general parameters

		param( 'sec_ID', 'integer', 0 );

		// Check permissions:
		check_user_perm( 'blogs', 'create', true, $sec_ID );

		$edited_Blog = new Blog( NULL );

		$edited_Blog->set( 'owner_user_ID', $current_User->ID );

		param( 'skin_ID', 'integer', true );
		$edited_Blog->set( 'normal_skin_ID', $skin_ID );

		param( 'kind', 'string', true );
		$edited_Blog->init_by_kind( $kind );

		if( $sec_ID > 0 )
		{
			$edited_Blog->set( 'sec_ID', $sec_ID );
		}

		$AdminUI->append_path_level( 'new', array( 'text' => sprintf( TB_('New [%s]'), get_collection_kinds($kind) ) ) );
		break;

	case 'create':
		// Insert into DB:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'collection' );

		param( 'sec_ID', 'integer', 0 );

		// Check permissions:
		check_user_perm( 'blogs', 'create', true, $sec_ID );

		$edited_Blog = new Blog( NULL );

		$edited_Blog->set( 'owner_user_ID', $current_User->ID );

		param( 'kind', 'string', true );
		param( 'blog_urlname', 'string', true );

		if( $kind == 'main' && ! check_user_perm( 'blog_admin', 'editAll', false ) )
		{ // Non-collection admins should not be able to create home/main collections
			$Messages->add( sprintf( TB_('You don\'t have permission to create a collection of kind %s.'), '<b>&laquo;'.$kind.'&raquo;</b>' ), 'error' );
			header_redirect( $admin_url.'?ctrl=collections' ); // will EXIT
			// We have EXITed already at this point!!
		}

		param( 'skin_ID', 'integer', true );
		$edited_Blog->set( 'normal_skin_ID', $skin_ID );

		$edited_Blog->init_by_kind( $kind );
		if( ! check_user_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
		{ // validate the urlname, which was already set by init_by_kind() function
		 	// It needs to validated, because the user can not set the blog urlname, and every new blog would have the same urlname without validation.
		 	// When user has edit permission to blog admin part, the urlname will be validated in load_from_request() function.
			$edited_Blog->set( 'urlname', urltitle_validate( empty( $blog_urlname ) ? $edited_Blog->get( 'urlname' ) : $blog_urlname, '', 0, false, 'blog_urlname', 'blog_ID', 'T_blogs' ) );
		}

		// Check how new content should be created for new collection:
		param( 'create_demo_contents', 'boolean', NULL );
		if( $create_demo_contents === NULL )
		{
			param_error( 'create_demo_contents', TB_('Please select an option for "New contents"') );
		}

		if( $current_User->check_perm( 'blog_admin', 'editall', false ) )
		{	// Allow to create demo organization and users only for collection admins:
			param( 'create_demo_org', 'boolean', false );
			param( 'create_demo_users', 'boolean', false );
		}
		else
		{	// Deny to create demo organization and users for not collection admins:
			set_param( 'create_demo_org', false );
			set_param( 'create_demo_users', false );
		}

		if( $edited_Blog->load_from_Request() )
		{
			// create the new blog
			$edited_Blog->create( $kind, array(
					'create_demo_contents' => $create_demo_contents,
					'create_demo_users'    => $create_demo_users,
					'create_demo_org'      => $create_demo_org,
				) );

			global $Settings;

			param( 'set_as_info_blog', 'boolean' );
			param( 'set_as_login_blog', 'boolean' );
			param( 'set_as_msg_blog', 'boolean' );

			if( $set_as_info_blog && ! $Settings->get( 'info_blog_ID' ) )
			{
				$Settings->set( 'info_blog_ID', $edited_Blog->ID );
			}
			if( $set_as_login_blog && ! $Settings->get( 'login_blog_ID' ) )
			{
				$Settings->set( 'login_blog_ID', $edited_Blog->ID );
			}
			if( $set_as_msg_blog && ! $Settings->get( 'mgs_blog_ID' ) )
			{
				$Settings->set( 'msg_blog_ID', $edited_Blog->ID );
			}
			$Settings->dbupdate();

			// We want to highlight the edited object on next list display:
			// $Session->set( 'fadeout_array', array( 'blog_ID' => array($edited_Blog->ID) ) );

			header_redirect( $edited_Blog->gen_blogurl() );// will save $Messages into Session
		}
		break;

	case 'duplicate':
		// Duplicate collection:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'collection' );

		param( 'sec_ID', 'integer', 0 );

		// Check permissions:
		check_user_perm( 'blog_properties', 'copy', true, $edited_Blog->ID );

		// Get name of the duplicating collection to display on the form:
		$duplicating_collection_name = $edited_Blog->get( 'shortname' );

		$duplicate_params = array(
				'duplicate_items'    => param( 'duplicate_items', 'integer', 0 ),
				'duplicate_comments' => param( 'duplicate_comments', 'integer', 0 ),
			);

		if( $edited_Blog->duplicate( $duplicate_params ) )
		{	// The collection has been duplicated successfully:
			$Messages->add( TB_('The collection has been duplicated.'), 'success' );

			header_redirect( $admin_url.'?ctrl=coll_settings&tab=dashboard&blog='.$edited_Blog->ID ); // will save $Messages into Session
		}

		// Set action back to "copy" in order to display the edit form with errors:
		$action = 'copy';
		break;


	case 'delete':
		// ----------  Delete a blog from DB ----------
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'collection' );

		// Check permissions:
		check_user_perm( 'blog_properties', 'edit', true, $blog );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed
			// Delete from DB:
			$msg = sprintf( TB_('Blog &laquo;%s&raquo; deleted.'), $edited_Blog->dget('name') );

			if( $edited_Blog->dbdelete() )
			{ // Blog was deleted
				$Messages->add( $msg, 'success' );

				$BlogCache->remove_by_ID( $blog );
				unset( $edited_Blog );
				unset( $Blog, $Collection );
				forget_param( 'blog' );
				set_working_blog( 0 );
				$UserSettings->delete( 'selected_blog' );	// Needed or subsequent pages may try to access the delete blog
				$UserSettings->dbupdate();
			}

			$action = 'list';
			// Redirect so that a reload doesn't write to the DB twice:
			$redirect_to = param( 'redirect_to', 'url', $admin_url.'?ctrl=collections' );
			header_redirect( $redirect_to, 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		else
		{ // Check if blog has delete restrictions
			if( ! $edited_Blog->check_delete( sprintf( TB_('Cannot delete Blog &laquo;%s&raquo;'), $edited_Blog->get_name() ), array( 'file_root_ID', 'cat_blog_ID' ) ) )
			{ // There are restrictions:
				$action = 'view';
			}
			// Force this virtual tab to select a correct path on delete action
			$tab = 'delete';
		}
		break;


	case 'update_settings_blog':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'collectionsettings' );

		// Check permission:
		check_user_perm( 'options', 'edit', true );

		$Settings->set( 'blogs_order_by', param( 'blogs_order_by', 'string', true ) );
		$Settings->set( 'blogs_order_dir', param( 'blogs_order_dir', 'string', true ) );

		$new_cache_status = param( 'general_cache_enabled', 'integer', 0 );
		if( ! $Messages->has_errors() )
		{
			load_funcs( 'collections/model/_blog.funcs.php' );
			$result = set_cache_enabled( 'general_cache_enabled', $new_cache_status, NULL, false );
			if( $result != NULL )
			{ // general cache setting was changed
				list( $status, $message ) = $result;
				$Messages->add( $message, $status );
			}
		}

		$Settings->set( 'newblog_cache_enabled', param( 'newblog_cache_enabled', 'integer', 0 ) );
		$Settings->set( 'newblog_cache_enabled_widget', param( 'newblog_cache_enabled_widget', 'integer', 0 ) );

		// Outbound pinging:
		param( 'outbound_notifications_mode', 'string', true );
		$Settings->set( 'outbound_notifications_mode',  get_param('outbound_notifications_mode') );

		// Categories:
		$Settings->set( 'allow_moving_chapters', param( 'allow_moving_chapters', 'integer', 0 ) );

		// Cross posting:
		$Settings->set( 'cross_posting', param( 'cross_posting', 'integer', 0 ) );
		$Settings->set( 'cross_posting_blogs', param( 'cross_posting_blogs', 'integer', 0 ) );

		// Always try to match slug:
		$Settings->set( 'always_match_slug', param( 'always_match_slug', 'integer', 0 ) );

		// Redirect moved posts:
		$Settings->set( 'redirect_moved_posts', $Settings->get( 'always_match_slug' ) ? 1 : param( 'redirect_moved_posts', 'integer', 0 ) );

		// Tiny URLs - 301 redirect to canonical URL:
		$Settings->set( 'redirect_tinyurl', param( 'redirect_tinyurl', 'integer', 0 ) );

		// Subscribing to new blogs:
		$Settings->set( 'subscribe_new_blogs', param( 'subscribe_new_blogs', 'string', 'public' ) );

		// Default Skins for New Collections:
		if( param( 'def_normal_skin_ID', 'integer', NULL ) !== NULL )
		{ // this can't be NULL
			$Settings->set( 'def_normal_skin_ID', get_param( 'def_normal_skin_ID' ) );
		}
		$Settings->set( 'def_mobile_skin_ID', param( 'def_mobile_skin_ID', 'integer', 0 ) );
		$Settings->set( 'def_tablet_skin_ID', param( 'def_tablet_skin_ID', 'integer', 0 ) );
		$Settings->set( 'def_alt_skin_ID', param( 'def_alt_skin_ID', 'integer', 0 ) );

		// Default URL for New Collections:
		if( param( 'coll_access_type', 'string', NULL ) !== NULL )
		{	// Update only if this param has been sent by submitted form:
			$Settings->set( 'coll_access_type', get_param( 'coll_access_type' ) );
		}

		if( ! $Messages->has_errors() )
		{
			$Settings->dbupdate();
			$Messages->add( TB_('The collection settings have been updated.'), 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=collections&tab=blog_settings', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;

	case 'update_settings_site':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'collectionsettings' );

		// Check permission:
		check_user_perm( 'options', 'edit', true );

		// Lock system
		if( check_user_perm( 'users', 'edit' ) )
		{
			$system_lock = param( 'system_lock', 'integer', 0 );
			if( $Settings->get( 'system_lock' ) && ( ! $system_lock ) && ( ! $Messages->has_errors() ) && ( 1 == $Messages->count() ) )
			{ // System lock was turned off and there was no error, remove the warning about the system lock
				$Messages->clear();
			}
			$Settings->set( 'system_lock', $system_lock );
		}

		// Site code
		$Settings->set( 'site_code',  param( 'site_code', 'string', '' ) );

		// Site color
		$site_color = param( 'site_color', 'string', '' );
		param_check_color( 'site_color', TB_('Invalid color code.') );
		$Settings->set( 'site_color', $site_color );

		// Site short name
		$short_name = param( 'notification_short_name', 'string', '' );
		param_check_not_empty( 'notification_short_name' );
		$Settings->set( 'notification_short_name', $short_name );

		// Site long name
		$Settings->set( 'notification_long_name', param( 'notification_long_name', 'string', '' ) );

		// Small site logo
		param( 'notification_logo_file_ID', 'integer', NULL );
		$Settings->set( 'notification_logo_file_ID', get_param( 'notification_logo_file_ID' ) );

		// Social media boilerplate logo
		param( 'social_media_image_file_ID', 'integer', NULL );
		$Settings->set( 'social_media_image_file_ID', get_param( 'social_media_image_file_ID' ) );

		// Enable site skins
		$old_site_skins_enabled = $Settings->get( 'site_skins_enabled' );
		$Settings->set( 'site_skins_enabled', param( 'site_skins_enabled', 'integer', 0 ) );
		if( $old_site_skins_enabled != $Settings->get( 'site_skins_enabled' ) )
		{ // If this setting has been changed we should clear all page caches:
			load_funcs( 'tools/model/_maintenance.funcs.php' );
			dbm_delete_pagecache( false );
		}

		// Terms & Conditions:
		$Settings->set( 'site_terms_enabled', param( 'site_terms_enabled', 'integer', 0 ) );
		$Settings->set( 'site_terms', param( 'site_terms', 'integer', '' ) );

		// Default blog
		$Settings->set( 'default_blog_ID', param( 'default_blog_ID', 'integer', 0 ) );

		// Blog for info pages
		$Settings->set( 'info_blog_ID', param( 'info_blog_ID', 'integer', 0 ) );

		// Blog for login|registration
		$Settings->set( 'login_blog_ID', param( 'login_blog_ID', 'integer', 0 ) );

		// Blog for messaging
		$Settings->set( 'msg_blog_ID', param( 'msg_blog_ID', 'integer', 0 ) );

		// Reload page timeout
		$reloadpage_timeout = param_duration( 'reloadpage_timeout' );
		if( $reloadpage_timeout > 99999 )
		{
			param_error( 'reloadpage_timeout', sprintf( TB_( 'Reload-page timeout must be between %d and %d seconds.' ), 0, 99999 ) );
		}
		$Settings->set( 'reloadpage_timeout', $reloadpage_timeout );

		// General cache
		$new_cache_status = param( 'general_cache_enabled', 'integer', 0 );
		if( ! $Messages->has_errors() )
		{
			load_funcs( 'collections/model/_blog.funcs.php' );
			$result = set_cache_enabled( 'general_cache_enabled', $new_cache_status, NULL, false );
			if( $result != NULL )
			{ // general cache setting was changed
				list( $status, $message ) = $result;
				$Messages->add( $message, $status );
			}
		}

		if( ! $Messages->has_errors() )
		{
			$Settings->dbupdate();
			$Messages->add( TB_('Site settings updated.'), 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $admin_url.'?ctrl=collections&tab=site_settings', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}

		break;

	case 'new_section':
	case 'edit_section':
		// New/Edit section:

		// Check permissions:
		check_user_perm( 'section', 'view', true, $edited_Section->ID );
		break;

	case 'create_section':
	case 'update_section':
		// Create/Update section:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'section' );

		// Check permission:
		check_user_perm( 'section', 'edit', true, $edited_Section->ID );

		if( $edited_Section->load_from_Request() )
		{
			if( $edited_Section->dbsave() )
			{
				if( is_create_action( $action ) )
				{
					$Messages->add( TB_('New section has been created.'), 'success' );
				}
				else
				{
					$Messages->add( TB_('The section has been updated.'), 'success' );
				}
			}

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $admin_url.'?ctrl=collections' ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;

	case 'delete_section':
		// Delete section:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'section' );

		// Check permissions:
		check_user_perm( 'section', 'edit', true, $edited_Section->ID );

		if( $edited_Section->ID == 1 )
		{	// Forbid to delete default section:
			$Messages->add( TB_('This section cannot be deleted.'), 'error' );
			$action = 'edit_section';
			break;
		}

		if( param( 'confirm', 'integer', 0 ) )
		{	// confirmed, Delete from DB:
			$msg = sprintf( TB_('Section "%s" has been deleted.'), $edited_Section->dget( 'name' ) );
			$edited_Section->dbdelete();
			unset( $edited_Section );
			forget_param( 'sec_ID' );
			$Messages->add( $msg, 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( $admin_url.'?ctrl=collections' ); // Will EXIT
			// We have EXITed already at this point!!
		}
		else
		{	// not confirmed, Check for restrictions:
			memorize_param( 'sec_ID', 'integer', $sec_ID );
			if( ! $edited_Section->check_delete( sprintf( TB_('Cannot delete section "%s"'), $edited_Section->dget( 'name' ) ) ) )
			{
				$action = 'edit_section';
			}
		}
		break;

	case 'update_site_skin':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'siteskin' );

		// Check permission:
		check_user_perm( 'options', 'edit', true );

		param( 'skinpage', 'string', '' );

		if( $skinpage == 'selection' )
		{
			$SkinCache = & get_SkinCache();

			if( param( 'normal_skin_ID', 'integer', NULL ) !== NULL )
			{	// Normal skin ID:
				$updated_skin_type = 'normal';
				$updated_skin_ID = get_param( 'normal_skin_ID' );
				$Settings->set( 'normal_skin_ID', $updated_skin_ID );
			}
			elseif( param( 'mobile_skin_ID', 'integer', NULL ) !== NULL )
			{	// Mobile skin ID:
				$updated_skin_type = 'mobile';
				$updated_skin_ID = get_param( 'mobile_skin_ID' );
				if( $updated_skin_ID == 0 )
				{	// Don't store this empty setting in DB:
					$Settings->delete( 'mobile_skin_ID' );
				}
				else
				{	// Set mobile skin:
					$Settings->set( 'mobile_skin_ID', $updated_skin_ID );
				}
			}
			elseif( param( 'tablet_skin_ID', 'integer', NULL ) !== NULL )
			{	// Tablet skin ID:
				$updated_skin_type = 'tablet';
				$updated_skin_ID = get_param( 'tablet_skin_ID' );
				if( $updated_skin_ID == 0 )
				{	// Don't store this empty setting in DB:
					$Settings->delete( 'tablet_skin_ID' );
				}
				else
				{	// Set tablet skin:
					$Settings->set( 'tablet_skin_ID', $updated_skin_ID );
				}
			}
			elseif( param( 'alt_skin_ID', 'integer', NULL ) !== NULL )
			{	// Alt skin ID:
				$updated_skin_type = 'alt';
				$updated_skin_ID = get_param( 'alt_skin_ID' );
				if( $updated_skin_ID == 0 )
				{	// Don't store this empty setting in DB:
					$Settings->delete( 'alt_skin_ID' );
				}
				else
				{	// Set alt skin:
					$Settings->set( 'alt_skin_ID', $updated_skin_ID );
				}
			}

			if( ! empty( $updated_skin_ID ) && ! skin_check_compatibility( $updated_skin_ID, 'site' ) )
			{	// Redirect to admin skins page selector if the skin cannot be selected:
				$Messages->add( TB_('This skin cannot be used as a site skin.'), 'error' );
				header_redirect( $admin_url.'?ctrl=collections&tab=site_skin&skinpage=selection&skin_type='.$updated_skin_type );
				break;
			}

			if( $Settings->dbupdate() )
			{
				$Messages->add( TB_('The site skin has been changed.')
									.' <a href="'.$admin_url.'?ctrl=collections&amp;tab=site_skin">'.TB_('Edit...').'</a>', 'success' );
				if( ( ! $Session->is_mobile_session() && ! $Session->is_tablet_session() && ! $Session->is_alt_session() && param( 'normal_skin_ID', 'integer', NULL ) !== NULL ) ||
						( $Session->is_mobile_session() && param( 'mobile_skin_ID', 'integer', NULL ) !== NULL ) ||
						( $Session->is_tablet_session() && param( 'tablet_skin_ID', 'integer', NULL ) !== NULL ) ||
						( $Session->is_alt_session() && param( 'alt_skin_ID', 'integer', NULL ) !== NULL ) )
				{	// Redirect to home page if we change the skin for current device type:
					header_redirect( $baseurl );
				}
				else
				{	// Redirect to admin skins page if we change the skin for another device type:
					header_redirect( $admin_url.'?ctrl=collections&tab=site_skin&skin_type='.$updated_skin_type );
				}
			}
		}
		else
		{	// Update site skin settings:
			if( ! in_array( $skin_type, array( 'normal', 'mobile', 'tablet', 'alt' ) ) )
			{
				debug_die( 'Wrong skin type: '.$skin_type );
			}

			$SkinCache = & get_SkinCache();
			$edited_Skin = & $SkinCache->get_by_ID( $Settings->get( $skin_type.'_skin_ID', ( $skin_type != 'normal' ) ), false, false );

			// Unset global blog vars in order to work with site skin:
			unset( $Blog, $blog, $global_param_list['blog'], $edited_Blog );

			if( ! $edited_Skin )
			{	// Redirect to don't try update empty skin params:
				header_redirect( $admin_url.'?ctrl=collections&tab=site_skin&skin_type='.$skin_type, 303 ); // Will EXIT
			}

			$edited_Skin->load_params_from_Request();

			if(	! param_errors_detected() )
			{	// Update settings:
				$edited_Skin->dbupdate_settings();
				$Messages->add( TB_('Skin settings have been updated'), 'success' );
				// Redirect so that a reload doesn't write to the DB twice:
				header_redirect( $admin_url.'?ctrl=collections&tab=site_skin&skin_type='.$skin_type, 303 ); // Will EXIT
			}
		}
		break;

	case 'create_demo_content':
		// Install demo collections:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'demo_content' );

		// Check permission:
		check_user_perm( 'blogs', 'create', true );

		// Install process is executed below in template in order to display it in real time.
		break;
}

switch( $tab )
{
	case 'site_skin':
		if( $Settings->get( 'site_skins_enabled' ) )
		{
			// Check minimum permission:
			check_user_perm( 'options', 'view', true );

			$AdminUI->set_path( 'site', 'skin', 'skin_'.$skin_type );

			$AdminUI->breadcrumbpath_init( false );
			$AdminUI->breadcrumbpath_add( TB_('Site'), $admin_url.'?ctrl=dashboard' );
			$AdminUI->breadcrumbpath_add( TB_('Site skin'), $admin_url.'?ctrl=collections&amp;tab=site_skin' );

			$AdminUI->set_page_manual_link( 'site-skin-settings' );

			// Init JS to select colors in skin settings:
			init_colorpicker_js();
			break;
		}
		else
		{
			$tab = 'site_settings';
			$Messages->add( TB_('Please enable site skins to use them.'), 'error' );
		}

	case 'site_settings':
		// Check minimum permission:
		check_user_perm( 'options', 'view', true );

		$AdminUI->set_path( 'site', 'settings' );

		$AdminUI->breadcrumbpath_init( false );
		$AdminUI->breadcrumbpath_add( TB_('Site'), $admin_url.'?ctrl=dashboard' );
		$AdminUI->breadcrumbpath_add( TB_('Site Settings'), $admin_url.'?ctrl=collections&amp;tab=site_settings' );

		$AdminUI->set_page_manual_link( 'site-settings' );

		init_colorpicker_js();
		break;

	case 'blog_settings':
		// Check minimum permission:
		check_user_perm( 'options', 'view', true );

		// We should activate toolbar menu items for this controller and tab
		$activate_collection_toolbar = true;

		$AdminUI->set_path( 'collections', 'settings', 'blog_settings' );

		$AdminUI->breadcrumbpath_init( true, array( 'text' => TB_('Collections'), 'url' => $admin_url.'?ctrl=collections' ) );
		$AdminUI->breadcrumbpath_add( TB_('Settings'), $admin_url.'?ctrl=coll_settings&amp;tab=general&amp;blog=$blog$' );
		$AdminUI->breadcrumbpath_add( TB_('Common Settings'), $admin_url.'?ctrl=collections&amp;tab=blog_settings&amp;blog=$blog$' );

		// Set an url for manual page:
		$AdminUI->set_page_manual_link( 'global-collection-settings' );

		// Init params to display a panel with blog selectors
		$AdminUI->set_coll_list_params( 'blog_ismember', 'view', array( 'ctrl' => 'collections', 'tab' => 'blog_settings' ) );
		break;

	case 'new':
		// Init JS to autcomplete the user logins
		init_autocomplete_login_js( 'rsc_url', $AdminUI->get_template( 'autocomplete_plugin' ) );

		$AdminUI->set_path( 'collections' );
		$AdminUI->clear_menu_entries( 'collections' );

		$AdminUI->breadcrumbpath_init( false, array( 'text' => TB_('Collections'), 'url' => $admin_url.'?ctrl=collections' ) );
		$AdminUI->breadcrumbpath_add( TB_('New Collection'), $admin_url.'?ctrl=collections&amp;action=new' );

		// Init params to display a panel with blog selectors
		$AdminUI->set_coll_list_params( 'blog_ismember', 'view', array( 'ctrl' => 'coll_settings', 'tab' => 'dashboard' ) );

		// Reset previous working collection:
		$blog = 0;

		// Set an url for manual page:
		switch( $action )
		{
			case 'new-selskin':
				$AdminUI->set_page_manual_link( 'pick-skin-for-new-collection' );
				break;
			case 'new-name':
				$AdminUI->set_page_manual_link( 'new-collection-settings' );
				break;
			default:
				$AdminUI->set_page_manual_link( 'create-collection-select-type' );
				break;
		}
		break;

	case 'delete':
		// Page to confirm a blog deletion
		$AdminUI->set_path( 'collections' );
		$AdminUI->clear_menu_entries( 'collections' );

		$AdminUI->breadcrumbpath_init( false, array( 'text' => TB_('Collections'), 'url' => $admin_url.'?ctrl=collections' ) );

		// Init params to display a panel with blog selectors
		$AdminUI->set_coll_list_params( 'blog_ismember', 'view', array( 'ctrl' => 'coll_settings', 'tab' => 'dashboard' ) );

		// We should activate toolbar menu items for this controller and tab
		$activate_collection_toolbar = true;
		break;

	case 'section':
		// Pages to create/edit/delete sections:
		$AdminUI->set_path( 'collections' );
		$AdminUI->clear_menu_entries( 'collections' );

		$AdminUI->breadcrumbpath_init( false );
		$AdminUI->breadcrumbpath_add( TB_('List'), $admin_url.'?ctrl=collections' );
		$AdminUI->breadcrumbpath_add( TB_('Collections'), $admin_url.'?ctrl=collections' );

		// Init params to display a panel with blog selectors
		$AdminUI->set_coll_list_params( 'blog_ismember', 'view', array( 'ctrl' => 'coll_settings', 'tab' => 'dashboard' ) );

		// Reset previous working collection:
		$blog = 0;

		// Init JS to autcomplete the user logins:
		init_autocomplete_login_js( 'rsc_url', $AdminUI->get_template( 'autocomplete_plugin' ) );
		break;

	case 'list':
		$AdminUI->set_path( 'collections' );
		$AdminUI->clear_menu_entries( 'collections' );

		$AdminUI->breadcrumbpath_init( false );
		$AdminUI->breadcrumbpath_add( TB_('List'), $admin_url.'?ctrl=collections' );
		$AdminUI->breadcrumbpath_add( TB_('Collections'), $admin_url.'?ctrl=collections' );

		// Init params to display a panel with blog selectors
		$AdminUI->set_coll_list_params( 'blog_ismember', 'view', array( 'ctrl' => 'coll_settings', 'tab' => 'dashboard' ) );

		// Reset previous working collection:
		$blog = 0;

		// Init JS to quick edit an order of the collections and their groups in the table cell by AJAX:
		init_field_editor_js( array(
				'field_prefix' => 'order-blog-',
				'action_url' => $admin_url.'?ctrl=collections&order_action=update&order_data=',
			) );
		init_field_editor_js( array(
				'field_prefix' => 'order-section-',
				'action_url' => $admin_url.'?ctrl=collections&order_action=update_section&order_data=',
			) );
		break;
}

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();


switch( $action )
{
	case 'new':
		//$AdminUI->displayed_sub_begin = 1;	// DIRTY HACK :/ replacing an even worse hack...
		$AdminUI->disp_payload_begin();

		$AdminUI->disp_view( 'collections/views/_coll_sel_type.view.php' );

		$AdminUI->disp_payload_end();
		break;


	case 'new-selskin':
	case 'new-installskin':
		//	$AdminUI->displayed_sub_begin = 1;	// DIRTY HACK :/ replacing an even worse hack...
		$AdminUI->disp_payload_begin();

		$AdminUI->disp_view( 'skins/views/_coll_sel_skin.view.php' );

		$AdminUI->disp_payload_end();
		break;


	case 'new-name':
	case 'create': // in case of validation error
		//$AdminUI->displayed_sub_begin = 1;	// DIRTY HACK :/ replacing an even worse hack...
		$AdminUI->disp_payload_begin();

		$next_action = 'create';

		$AdminUI->disp_view( 'collections/views/_coll_general.form.php' );

		$AdminUI->disp_payload_end();
		break;


	case 'copy':
		$AdminUI->disp_payload_begin();

		$next_action = 'duplicate';

		$AdminUI->disp_view( 'collections/views/_coll_general.form.php' );

		$AdminUI->disp_payload_end();
		break;


	case 'delete':
		// ----------  Delete a blog from DB ----------
		$delete_notes = array();

		// Check how many posts and comments will be deleted
		$number_of_items = $edited_Blog->get_number_of_items();
		if( $number_of_items > 0 )
		{ // There is at least one item
			$number_of_comments = $edited_Blog->get_number_of_comments();
			if( $number_of_comments > 0 )
			{ // There is at least one comment
				$delete_notes[] = array( sprintf( TB_('WARNING: This collection contains %d items and %d comments.'), $number_of_items, $number_of_comments ), 'warning' );
			}
			else
			{
				$delete_notes[] = array( sprintf( TB_('WARNING: This collection contains %d items.'), $number_of_items ), 'warning' );
			}
		}

		// Check if the deleting blog is used as default blog and Display a warning
		if( $default_Blog = & get_setting_Blog( 'default_blog_ID' ) && $default_Blog->ID == $edited_Blog->ID )
		{ // Default blog
			$delete_notes[] = array( TB_('WARNING: You are about to delete the default collection.'), 'warning' );
		}
		if( $info_Blog = & get_setting_Blog( 'info_blog_ID' ) && $info_Blog->ID == $edited_Blog->ID  )
		{ // Info blog
			$delete_notes[] = array( TB_('WARNING: You are about to delete the collection used for info pages.'), 'warning' );
		}
		if( $login_Blog = & get_setting_Blog( 'login_blog_ID' ) && $login_Blog->ID == $edited_Blog->ID  )
		{ // Login blog
			$delete_notes[] = array( TB_('WARNING: You are about to delete the collection used for login/registration pages.'), 'warning' );
		}
		if( $msg_Blog = & get_setting_Blog( 'msg_blog_ID' ) && $msg_Blog->ID == $edited_Blog->ID  )
		{ // Messaging blog
			$delete_notes[] = array( TB_('WARNING: You are about to delete the collection used for messaging pages.'), 'warning' );
		}

		$delete_notes[] = array( TB_('Note: Some files in this collection\'s fileroot may be linked to users or to other collections posts and comments. Those files will ALSO be deleted, which may be undesirable!'), 'note' );
		$edited_Blog->confirm_delete( sprintf( TB_('Delete collection &laquo;%s&raquo;?'), $edited_Blog->get_name() ), 'collection', $action,
			get_memorized( 'action' ), $delete_notes );
		break;

	case 'new_section':
	case 'edit_section':
	case 'create_section':
	case 'update_section':
	case 'delete_section':
		// Form to create/edit section:

		if( $action == 'delete_section' )
		{	// We need to ask for confirmation:
			set_param( 'redirect_to', $admin_url.'?ctrl=collections' );
			$edited_Section->confirm_delete(
				sprintf( TB_('Delete section "%s"?'), $edited_Section->dget( 'name' ) ),
				'section', $action, get_memorized( 'action' ) );
		}

		$AdminUI->disp_view( 'collections/views/_section.form.php' );
		break;

	default:
		// List the blogs:
		$AdminUI->disp_payload_begin();
		// Display VIEW:
		switch( $tab )
		{
			case 'site_settings':
				$AdminUI->disp_view( 'collections/views/_coll_settings_site.form.php' );
				break;

			case 'site_skin':
				param( 'skinpage', 'string', '' );

				// Unset global blog vars in order to work with site skin:
				unset( $Blog, $blog, $global_param_list['blog'], $edited_Blog );

				if( $skinpage == 'selection' )
				{
					$AdminUI->disp_view( 'skins/views/_coll_skin.view.php' );
				}
				else
				{
					$AdminUI->disp_view( 'skins/views/_coll_skin_settings.form.php' );
				}
				break;

			case 'blog_settings':
				$AdminUI->disp_view( 'collections/views/_coll_settings_blog.form.php' );
				break;

			default:
				load_funcs( 'dashboard/model/_dashboard.funcs.php' );
				$collection_count = get_table_count( 'T_blogs' );
				if( $action == 'create_demo_content' && $collection_count == 0 )
				{	// Create new demo content inside template to display a process in real time:
					$block_item_Widget = new Widget( 'block_item' );

					$block_item_Widget->title = TB_('Demo content').':';
					$block_item_Widget->disp_template_replaced( 'block_start' );

					load_funcs( 'collections/_demo_content.funcs.php' );
					$collection_count = install_demo_content();

					$block_item_Widget->disp_template_raw( 'block_end' );
				}

				// Welcome panel to create demo content:
				if( check_user_perm( 'blogs', 'create' ) && $collection_count == 0 )
				{
					$AdminUI->disp_view( 'collections/views/_welcome_demo_content.view.php' );
				}

				if( $collection_count > 0 )
				{	// Collections list:
					$AdminUI->disp_view( 'collections/views/_coll_list.view.php' );
				}

				// Models to start new collections
				$AdminUI->disp_view( 'collections/views/_coll_model_list.view.php' );
		}
		$AdminUI->disp_payload_end();

}


// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>