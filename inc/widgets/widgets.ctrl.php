<?php
/**
 * This file implements the UI controller for managing widgets inside of a blog.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var AdminUI
 */
global $AdminUI;
/**
 * @var Plugins
 */
global $Plugins;

// Memorize this as the last "tab" used in the Blog Settings:
$UserSettings->set( 'pref_coll_settings_tab', 'widgets' );
$UserSettings->dbupdate();

load_funcs( 'widgets/_widgets.funcs.php' );

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );
load_class( 'widgets/model/_widgetcontainer.class.php', 'WidgetContainer' );


// Check permissions on requested blog and autoselect an appropriate blog if necessary.
// This will prevent a fat error when switching tabs and you have restricted perms on blog properties.
if( $selected = autoselect_blog( 'blog_properties', 'edit' ) ) // Includes perm check
{	// We have a blog to work on:

	if( set_working_blog( $selected ) )	// set $blog & memorize in user prefs
	{	// Selected a new blog:
		$BlogCache = & get_BlogCache();
		/**
		 * @var Blog
		 */
		$Collection = $Blog = & $BlogCache->get_by_ID( $blog );
	}

	/**
	 * @var Blog
	 */
	$edited_Blog = & $Blog;
}
else
{	// We could not find a blog we have edit perms on...
	// Note: we may still have permission to edit categories!!
	$Messages->add( TB_('Sorry, you have no permission to edit collection properties.'), 'error' );
	// Redirect to collections list:
	header_redirect( get_admin_url( 'ctrl=collections', '&' ) );
	// EXITED.
}

param( 'skin_type', 'string', 'normal' );

$action = param_action( 'list' );
param( 'display_mode', 'string', 'normal' );
$display_mode = ( in_array( $display_mode, array( 'js', 'normal' ) ) ? $display_mode : 'normal' );
if( $display_mode == 'js' )
{	// JavaScript mode:

	// Check that this action request is not a CSRF hacked request:
	$Session->assert_received_crumb( 'widget' );

	// Javascript in debug mode conflicts/fails.
	// fp> TODO: either fix the debug javascript or have an easy way to disable JS in the debug output.
	$debug = 0;
	$debug_jslog = false;
}
// This should probably be handled with teh existing $mode var

param( 'wico_ID', 'integer', 0, true );

/*
 * Init the objects we want to work on.
 */
switch( $action )
{
	case 'nil':
	case 'list':
	case 'reload':
	case 'new_container':
	case 'edit_container':
	case 'create_container':
	case 'update_container':
	case 'reload_container':
	case 'activate':
	case 'deactivate':
		// Do nothing
		break;

	case 'duplicate':
		param( 'wi_ID', 'integer', true );
		break;

	case 'create':
		param( 'type', 'string', true );
		param( 'code', 'string', true );
	case 'new':
	case 'add_list':
	case 'customize':
		param( 'container', 'string', $action == 'add_list', true );	// memorize
		param( 'container_code', 'string' );
		param( 'skin_type', 'string' );
		break;

	case 're-order' : // js request
		param( 'wico_ID', 'integer', 0 );
		param( 'container_list', 'string', true );
		$containers_list = explode( ',', trim( $container_list, ',' ) );
		$containers = array();
		$passed_widget_IDs = array(); // Store here all passed IDs in order to compare them with current DB
		foreach( $containers_list as $a_container )
		{	// Add each container and grab its widgets:
			$a_container_widgets = explode( ',', param( trim( $a_container, ',' ), 'string', true ) );
			foreach( $a_container_widgets as $a => $a_container_widget )
			{
				if( $a_container_widget_ID = preg_replace( '~[^0-9]~', '', $a_container_widget ) )
				{	// Use only correct widget ID for reordering:
					$a_container_widgets[ $a ] = $a_container_widget_ID;
					$passed_widget_IDs[] = $a_container_widget_ID;
				}
				else
				{	// Skip wrong passed widget ID:
					unset( $a_container_widgets[ $a ] );
				}
			}
			$containers[substr( $a_container, 10 )] = $a_container_widgets;
		}
		break;

	case 'edit':
	case 'update':
	case 'update_edit':
	case 'delete':
	case 'move_up':
	case 'move_down':
	case 'toggle':
	case 'cache_enable':
	case 'cache_disable':
		param( 'wi_ID', 'integer', true );
		$WidgetCache = & get_WidgetCache();
		$edited_ComponentWidget = & $WidgetCache->get_by_ID( $wi_ID );
		// Take blog from Widget if it is not in a shared container ( coll_ID is not set in case of shared containers )!
		$WidgetContainer = & $edited_ComponentWidget->get_WidgetContainer();
		if( ! empty( $WidgetContainer->coll_ID ) )
		{
			set_working_blog( $WidgetContainer->coll_ID );
		}
		$BlogCache = & get_BlogCache();
		/**
		* @var Blog
		*/
		$Collection = $Blog = & $BlogCache->get_by_ID( $blog );

		break;

	case 'destroy_container':
		param( 'wico_ID', 'integer', 0 );
		$WidgetContainerCache = & get_WidgetContainerCache();
		$edited_WidgetContainer = $WidgetContainerCache->get_by_ID( $wico_ID );
		break;

	default:
		debug_die( 'Init objects: unhandled action' );
}

if( ! valid_blog_requested() )
{
	debug_die( 'Invalid blog requested' );
}

switch( $display_mode )
{
	case 'js' : // js response needed
// fp> when does this happen -- should be documented
		if( ! check_user_perm( 'blog_properties', 'edit', false, $blog ) )
		{	// user doesn't have permissions
			$Messages->add( TB_('You do not have permission to perform this action' ) );
// fp>does this only happen when we try to edit settings. The hardcoded 'closeWidgetSettings' response looks bad.
			send_javascript_message( array( 'closeWidgetSettings' => array() ) );
		}
		break;

	case 'normal':
	default : // take usual approach
		check_user_perm( 'blog_properties', 'edit', true, $blog );
		// Initialize JS for color picker field on the edit plugin settings form:
		init_colorpicker_js();
}

/**
 * Perform action:
 */
switch( $action )
{
	case 'nil':
	case 'new':
	case 'edit':
	case 'add_list':
	case 'customize':
		// Do nothing
		break;

	case 'new_container':
		// Initialize widget container for creating form:
		param( 'container_type', 'string', NULL );
		$edited_WidgetContainer = new WidgetContainer();
		if( $container_type == 'shared' )
		{	// Default settings for new shared container:
			$edited_WidgetContainer->set( 'main', 1 );
		}
		elseif( $container_type != 'shared-sub' )
		{	// Default settings for new container:
			$edited_WidgetContainer->set( 'coll_ID', $Blog->ID );
		}
		$edited_WidgetContainer->set( 'skin_type', $skin_type );
		break;

	case 'edit_container':
		// Initialize widget container for editing form:
		$WidgetContainerCache = & get_WidgetContainerCache();
		$edited_WidgetContainer = $WidgetContainerCache->get_by_ID( $wico_ID );
		break;

	case 'duplicate':
		// Duplicate a Widget to container:

		$WidgetCache = & get_WidgetCache();
		$duplicated_Widget = & $WidgetCache->get_by_ID( $wi_ID );
		$duplicated_Widget->load_param_array();

		$type = $duplicated_Widget->type;
		$code = $duplicated_Widget->code;

	case 'create':
		// Add a Widget to container:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'widget' );

		if( $action == 'duplicate' )
		{
			$WidgetContainer = & $duplicated_Widget->get_WidgetContainer();
			$container = 'wico_ID_'.$WidgetContainer->ID;
		}
		else
		{
			$WidgetContainer = & get_WidgetContainer_by_coll_skintype_fieldset( $Blog->ID, $skin_type, $container );
		}
		if( ! in_array( $WidgetContainer->get( 'code' ), array_keys( $Blog->get_main_containers( $skin_type ) ) ) )
		{ // The container is not part of the current skin
			$Messages->add( TB_('WARNING: you are adding to a container that does not seem to be part of the current skin.'), 'warning' );
		}

		switch( $type )
		{
			case 'core':
				// Check the requested core widget is valid:
				$objtype = $code.'_Widget';
				load_class( 'widgets/widgets/_'.$code.'.widget.php', $objtype );
				$edited_ComponentWidget = new $objtype();
				break;

			case 'plugin':
				if( ! $Plugin = & $Plugins->get_by_code( $code ) )
				{
					debug_die( 'Requested plugin not found' );
				}
				if( ! $Plugins->has_event( $Plugin->ID, 'SkinTag' ) )
				{
					debug_die( 'Requested plugin does not support SkinTag' );
				}
				$edited_ComponentWidget = new ComponentWidget( NULL, 'plugin', $code );
				break;

			default:
				debug_die( 'Unhandled widget type' );
		}

		$DB->begin();

		if( $WidgetContainer->ID == 0 )
		{ // New widget container needs to be saved
			$WidgetContainer->dbinsert();
		}
		$edited_ComponentWidget->set( 'wico_ID', $WidgetContainer->ID );
		$edited_ComponentWidget->set( 'enabled', 1 );

		if( $action == 'duplicate' )
		{	// Copy all params from the original widget:
			$duplicated_Widget->load_param_array();
			$edited_ComponentWidget->param_array = $duplicated_Widget->param_array;

			// Get widget order for new widget, must be next to the duplicated widget:
			$widget_order = $duplicated_Widget->order + 1;
			$edited_ComponentWidget->set( 'order', $widget_order );

			// Move other widgets in the same container down, two-step update necessary to prevent unique key violation:
			$DB->query( 'UPDATE T_widget__widget
				  SET wi_order = wi_order + 1
				WHERE wi_wico_ID = '.$DB->quote( $WidgetContainer->ID ).'
				ORDER BY wi_order DESC' );

			$DB->query( 'UPDATE T_widget__widget
					SET wi_order = wi_order - 1
				WHERE wi_wico_ID = '.$DB->quote( $WidgetContainer->ID ).'
				AND wi_order <= '.$DB->quote( $widget_order ).'
				ORDER BY wi_order ASC' );

			$edited_ComponentWidget->set( 'enabled', $duplicated_Widget->get( 'enabled' ) );

			if( ! empty( $duplicated_Widget->get_param( 'title' ) ) )
			{	// Append "(copy)" to title:
				$edited_ComponentWidget->set( 'title', $duplicated_Widget->get_param( 'title' ).' ('.TB_('copy').')' );
			}
		}

		// INSERT INTO DB:
		$edited_ComponentWidget->dbinsert();

		$DB->commit();

		$Messages->add( sprintf( TB_('Widget &laquo;%s&raquo; has been added to container &laquo;%s&raquo;.'),
					$edited_ComponentWidget->get_name(), $edited_ComponentWidget->get_container_param( 'name' ) ), 'success' );

		switch( $display_mode )
		{
			case 'js' :	// this is a js call, lets return the settings page -- fp> what do you mean "settings page" ?
				// fp> wthis will visually live insert the new widget into the container; it probably SHOULD open the edit properties right away
				if( $edited_ComponentWidget->type == 'plugin' && $edited_ComponentWidget->get_Plugin() == false )
				{
					$plugin_disabled = 1;
				}
				else
				{
					$plugin_disabled = 0;
				}

				$methods =  array(
					'addNewWidgetCallback' => array(
						$edited_ComponentWidget->ID,
						$container,
						$edited_ComponentWidget->get( 'order' ),
						'<a href="'.regenerate_url( 'blog', 'action=edit&amp;wi_ID='.$edited_ComponentWidget->ID ).'" class="widget_name">'
							.$edited_ComponentWidget->get_desc_for_list()
						.'</a> '.$edited_ComponentWidget->get_help_link(),
						$edited_ComponentWidget->enabled,
						$plugin_disabled,
						$edited_ComponentWidget->get_cache_status( true ),
						( ( $action == 'duplicate' ) && isset( $duplicated_Widget ) ) ? $duplicated_Widget->ID : NULL,
						$mode,
					),
				);

				if( $mode != 'customizer' )
				{	// Open widget settings, except when in customizer mode:
					$methods['editWidget'] = array( 'wi_ID_'.$edited_ComponentWidget->ID );
				}

				send_javascript_message( $methods );
				break;

			case 'normal' :
			default : // take usual action
				header_redirect( get_admin_url( 'ctrl=widgets&action=edit&wi_ID='.$edited_ComponentWidget->ID.( $mode == 'customizer' ? '&mode=customizer' : '' ), '&' ) );
				break;
		}
		break;


	case 'update':
	case 'update_edit':
		// Update Settings

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'widget' );

		// Update the folding states for current user:
		save_fieldset_folding_values( $Blog->ID );

		$edited_ComponentWidget->load_from_Request();

		if( ! param_errors_detected() )
		{ // Update settings:
			$edited_ComponentWidget->dbupdate();
			$Messages->add( TB_('Widget settings have been updated'), 'success' );
			switch( $display_mode )
			{
				case 'js' : // js reply
					$edited_ComponentWidget->init_display( array() );
					$methods = array();
					$methods['widgetSettingsCallback'] = array(
							$edited_ComponentWidget->ID,
							$edited_ComponentWidget->get_desc_for_list(),
							$edited_ComponentWidget->get_cache_status( true )
						);
					if( $action == 'update' )
					{	// Close window after update, and don't close it when user wants continue editing after updating:
						$methods['closeWidgetSettings'] = array( $action );
					}
					else
					{	// Scroll to messages after update:
						$methods['showMessagesWidgetSettings'] = array( 'success' );
						if( ! empty( $edited_ComponentWidget->reload_page_after_update ) )
						{	// Reload page because it is required to update widget form with some new content which was created during updating:
							$methods['location.reload'] = array();
						}
					}
					send_javascript_message( $methods, true );
					break;
			}
			if( $action == 'update_edit' )
			{	// Stay on edit widget form:
				header_redirect( get_admin_url( 'ctrl=widgets&blog='.$Blog->ID.'&action=edit&wi_ID='.$edited_ComponentWidget->ID.'&display_mode='.$display_mode, '&' ), 303 );
			}
			else
			{	// If $action == 'update'
				// Redirect to widgets list:
				$Session->set( 'fadeout_id', $edited_ComponentWidget->ID );
				if( $mode == 'customizer' && ! empty( $edited_ComponentWidget->reload_page_after_update ) )
				{	// Set flag to refresh customizer page because it is required to update widget form with some new content which was created during updating:
					// (e.g. used to display new auto created sub-container by widget "Columns(Sub-Containers)" - subcontainer_row_Widget)
					$Session->set( 'refresh_customizer_window', 1 );
				}
				header_redirect( get_admin_url( 'ctrl=widgets&blog='.$Blog->ID, '&' ), 303 );
			}
		}
		elseif( $display_mode == 'js' )
		{	// Send errors back as js:
			send_javascript_message( array( 'showMessagesWidgetSettings' => array( 'failed' ) ), true );
		}
		break;

	case 'move_up':
		// Move the widget up:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'widget' );

		$order = $edited_ComponentWidget->order;
		$DB->begin();

 		// Get the previous element
		$row = $DB->get_row( 'SELECT *
				FROM T_widget__widget
				WHERE wi_wico_ID = '.$edited_ComponentWidget->wico_ID.' AND wi_order < '.$order.'
				ORDER BY wi_order DESC
				LIMIT 0,1'
			);

		if( !empty( $row) )
		{
			$prev_ComponentWidget = new ComponentWidget( $row );
			$prev_order = $prev_ComponentWidget->order;

			$edited_ComponentWidget->set( 'order', 0 );	// Temporary
			$edited_ComponentWidget->dbupdate();

			$prev_ComponentWidget->set( 'order', $order );
			$prev_ComponentWidget->dbupdate();

			$edited_ComponentWidget->set( 'order', $prev_order );
			$edited_ComponentWidget->dbupdate();

		}
		$DB->commit();
		break;

	case 'move_down':
		// Move the widget down:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'widget' );

		$order = $edited_ComponentWidget->order;
		$DB->begin();

 		// Get the next element
		$row = $DB->get_row( 'SELECT *
				FROM T_widget__widget
				WHERE wi_wico_ID = '.$edited_ComponentWidget->wico_ID.' AND wi_order > '.$order.'
				ORDER BY wi_order ASC
				LIMIT 0,1'
			);

		if( !empty( $row ) )
		{
			$next_ComponentWidget = new ComponentWidget( $row );
			$next_order = $next_ComponentWidget->order;

			$edited_ComponentWidget->set( 'order', 0 );	// Temporary
			$edited_ComponentWidget->dbupdate();

			$next_ComponentWidget->set( 'order', $order );
			$next_ComponentWidget->dbupdate();

			$edited_ComponentWidget->set( 'order', $next_order );
			$edited_ComponentWidget->dbupdate();

		}
		$DB->commit();
		break;

	case 'toggle':
		// Enable or disable the widget:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'widget' );

		$enabled = $edited_ComponentWidget->get( 'enabled' );
		$edited_ComponentWidget->set( 'enabled', (int)! $enabled );
		$edited_ComponentWidget->dbupdate();

		if( $edited_ComponentWidget->type == 'plugin' && $edited_ComponentWidget->get_Plugin() == false )
		{
			$plugin_disabled = 1;
		}
		else
		{
			$plugin_disabled = 0;
		}

		if ( $enabled )
		{
			$msg = TB_( 'Widget has been disabled.' );
		}
		else
		{
			$msg = TB_( 'Widget has been enabled.' );
		}
		$Messages->add( $msg, 'success' );

		if ( $display_mode == 'js' )
		{
			// EXITS:
			send_javascript_message( array( 'doToggle' => array( $edited_ComponentWidget->ID, (int)! $enabled, $plugin_disabled ) ) );
		}
		header_redirect( get_admin_url( 'ctrl=widgets&blog='.$Blog->ID, '&' ), 303 );
		break;

	case 'cache_enable':
	case 'cache_disable':
		// Enable or disable the block caching for the widget:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'widget' );

		if( $edited_ComponentWidget->get_cache_status() == 'disallowed' )
		{ // Don't allow to change cache status because it is not allowed by widget config
			$Messages->add( TB_( 'This widget cannot be cached in the block cache.' ), 'error' );
		}
		else
		{ // Update widget cache status
			$edited_ComponentWidget->set( 'allow_blockcache', $action == 'cache_enable' ? 1 : 0 );
			$edited_ComponentWidget->dbupdate();

			if( $action == 'cache_enable' )
			{
				$Messages->add( TB_( 'Block caching has been turned on for this widget.' ), 'success' );
			}
			else
			{
				$Messages->add( TB_( 'Block caching has been turned off for this widget.' ), 'success' );
			}
		}

		if ( $display_mode == 'js' )
		{
			// EXITS:
			send_javascript_message( array( 'doToggleCache' => array(
					$edited_ComponentWidget->ID,
					$edited_ComponentWidget->get_cache_status( true ),
				) ) );
		}
		header_redirect( get_admin_url( 'ctrl=widgets&blog='.$Blog->ID, '&' ), 303 );
		break;

	case 'activate':
	case 'deactivate':
		// Enable or disable the widgets:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'widget' );

		$widgets = param( 'widgets', 'array:integer' );
		$wico_ID = param( 'wico_ID', 'integer', 0 );

		if( count( $widgets ) )
		{ // Enable/Disable the selected widgets
			$updated_widgets = $DB->query( 'UPDATE T_widget__widget
				INNER JOIN T_widget__container ON wico_ID = wi_wico_ID
				  SET wi_enabled = '.$DB->quote( $action == 'activate' ? '1' : '0' ).'
				WHERE wi_ID IN ( '.$DB->quote( $widgets ).' )
				  AND wico_coll_ID = '.$DB->quote( $Blog->ID ) );
		}

		if( ! empty( $updated_widgets ) )
		{ // Display a result message only when at least one widget has been updated
			if( $action == 'activate' )
			{
				$Messages->add( sprintf( TB_( '%d widgets have been enabled.' ), $updated_widgets ), 'success' );
			}
			else
			{
				$Messages->add( sprintf( TB_( '%d widgets have been disabled.' ), $updated_widgets ), 'success' );
			}
		}

		if( $mode == 'customizer' )
		{	// Set an URL to redirect back to customizer mode:
			$redirect_to = get_admin_url( 'ctrl=widgets&blog='.$Blog->ID.'&skin_type='.$skin_type.'&action=customize&mode=customizer', '&' );
			$WidgetContainerCache = & get_WidgetContainerCache();
			if( $WidgetContainer = & $WidgetContainerCache->get_by_ID( $wico_ID, false, false ) )
			{
				$redirect_to .= '&container='.urlencode( $WidgetContainer->get( 'name' ) ).'&container_code='.urlencode( $WidgetContainer->get( 'code' ) );
			}
		}
		else
		{	// Set an URL to redirect to normal mode:
			$redirect_to = get_admin_url( 'ctrl=widgets&blog='.$Blog->ID, '&' );
		}

		header_redirect( $redirect_to, 303 );
		break;

	case 'delete':
		// Remove a widget from container:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'widget' );

		$msg = sprintf( TB_('Widget &laquo;%s&raquo; removed.'), $edited_ComponentWidget->get_name() );
		$edited_widget_ID = $edited_ComponentWidget->ID;
		$edited_ComponentWidget->dbdelete();
		unset( $edited_ComponentWidget );
		forget_param( 'wi_ID' );
		$Messages->add( $msg, 'success' );

		switch( $display_mode )
		{
			case 'js' :	// js call : return success message
				send_javascript_message();
				break;

			case 'normal' :
			default : // take usual action
				// PREVENT RELOAD & Switch to list mode:
				header_redirect( get_admin_url( 'ctrl=widgets&blog='.$blog, '&' ) );
				break;
		}
		break;

	case 'list':
		break;

	case 're-order' : // js request
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'widget' );

		$DB->begin();

		if( $wico_ID > 0 )
		{	// Re-order widgets of ONE given container:
			$blog_container_IDs = array( $wico_ID );
		}
		else
		{	// Re-order widgets of ALL containers:
			$SQL = new SQL( 'Get IDs of all widget containers of collection #'.$Blog->ID );
			$SQL->SELECT( 'wico_ID' );
			$SQL->FROM( 'T_widget__container' );
			$SQL->WHERE( '( wico_coll_ID = '.$Blog->ID.' OR wico_coll_ID IS NULL )' );
			$SQL->WHERE_and( 'wico_skin_type = '.$DB->quote( $skin_type ) );
			$blog_container_IDs = $DB->get_col( $SQL );
		}

		// Check if current reordering contains all actual widgets from DB:
		if( empty( $blog_container_IDs ) )
		{
			$server_widget_IDs = array();
		}
		else
		{
			$SQL = new SQL( 'Get all widget IDs for checking correct re-order request' );
			$SQL->SELECT( 'wi_ID' );
			$SQL->FROM( 'T_widget__widget' );
			$SQL->WHERE( 'wi_wico_ID IN ( '.implode( ',', $blog_container_IDs ).' )' );
			$server_widget_IDs = $DB->get_col( $SQL );
		}
		$checked_new_widgets = array_diff( $server_widget_IDs, $passed_widget_IDs );
		$checked_old_widgets = array_diff( $passed_widget_IDs, $server_widget_IDs );

		// Don't allow to reorder if at least one widget was added or deleted in DB since after last page refresh:
		$result = empty( $checked_new_widgets ) && empty( $checked_old_widgets );

		if( $result && $blog_container_IDs )
		{
			$blog_container_IDs = $DB->quote( $blog_container_IDs );

			// Reset the current orders to avoid duplicate entry errors
			$DB->query( 'UPDATE T_widget__widget
				SET wi_order = wi_order * -1
				WHERE wi_wico_ID IN ( '.$blog_container_IDs.' )' );

			foreach( $containers as $container_fieldset_id => $widgets )
			{ // loop through each container and set new order
				$WidgetContainer = & get_WidgetContainer_by_coll_skintype_fieldset( $Blog->ID, $skin_type, $container_fieldset_id );
				if( ( $WidgetContainer->ID == 0 ) && ( count( $widgets ) > 0 ) )
				{ // Widget was moved to an empty main widget container, it needs to be created
					$WidgetContainer->dbinsert();
				}
				$order = 0; // reset counter for this container
				foreach( $widgets as $widget )
				{ // loop through each widget
					$order++;
					$DB->query( 'UPDATE T_widget__widget
						SET wi_order = '.$order.',
							wi_wico_ID = '.$WidgetContainer->ID.'
						WHERE wi_ID = '.$widget.' AND wi_wico_ID IN ( '.$blog_container_IDs.' )' );	// Doh! Don't trust the client request!!
				}
			}
		}

		if( $result )
		{	// Send success message:
			$DB->commit();
			$Messages->add( TB_( 'Widgets updated' ), 'success' );
		}
		else
		{	// Send error message if widgets cannot be reordered:
			$DB->rollback();
			$Messages->add( T_('The widgets have been changed since you last loaded this page.').' '.T_('Please reload the page to be in sync with the server.'), 'error' );
		}
		send_javascript_message( array( 'sendWidgetOrderCallback' => array( 'blog='.$Blog->ID ) ) ); // exits() automatically
		break;


	case 'reload':
		// Reload containers:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'widget' );

		// Save to DB, and display correpsonding messages:
		$Blog->db_save_main_containers( true, $skin_type );

		header_redirect( get_admin_url( 'ctrl=widgets&blog='.$Blog->ID.'&skin_type='.$skin_type, '&' ), 303 );
		break;

	case 'create_container':
	case 'update_container':
		// Save widget container:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'widget_container' );

		if( $wico_ID > 0 )
		{	// Get the existing widget container:
			$WidgetContainerCache = & get_WidgetContainerCache();
			$edited_WidgetContainer = & $WidgetContainerCache->get_by_ID( $wico_ID );
		}
		else
		{	// Get new widget container:
			$edited_WidgetContainer = new WidgetContainer();
		}
		if( $edited_WidgetContainer->load_from_Request() )
		{	// If widget container has been saved successfully:
			if( $edited_WidgetContainer->dbsave() )
			{
				$Messages->add( sprintf( TB_('%s has been saved.'), $edited_WidgetContainer->get_type_title().' "'.$edited_WidgetContainer->get( 'name' ).'"' ), 'success' );
			}
			if( $mode == 'customizer' )
			{	// Redirect back to customizer mode:
				$redirect_to = get_admin_url( 'ctrl=widgets&blog='.$Blog->ID.'&skin_type='.$skin_type.'&action=customize&mode=customizer', '&' );
			}
			else
			{	// Redirect back to back-office widgets list:
				$redirect_to = get_admin_url( 'ctrl=widgets&blog='.$Blog->ID.'&skin_type='.$edited_WidgetContainer->get( 'skin_type' ), '&' );
			}
			header_redirect( $redirect_to, 303 );
		}
		break;

	case 'destroy_container':
		// Destroy a widget container

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'widget_container' );

		$success_msg = sprintf( TB_('Widget container &laquo;%s&raquo; removed.'), $edited_WidgetContainer->get( 'name' ) );
		// Remove the widget container from the database
		$edited_WidgetContainer->dbdelete();
		unset( $edited_WidgetContainer );
		forget_param( 'wico_ID' );
		$Messages->add( $success_msg, 'success' );

		if( $mode == 'customizer' )
		{	// Redirect back to customizer mode:
			$redirect_to = get_admin_url( 'ctrl=widgets&blog='.$blog.'&skin_type='.$skin_type.'&action=customize&mode=customizer', '&' );
		}
		else
		{	// Redirect back to back-office widgets list:
			$redirect_to = get_admin_url( 'ctrl=widgets&blog='.$blog, '&' );
		}
		header_redirect( $redirect_to, 303 );
		break;

	case 'reload_container':
		// Reload widget container:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'widget_container' );

		// Get the existing widget container:
		$WidgetContainerCache = & get_WidgetContainerCache();
		$edited_WidgetContainer = & $WidgetContainerCache->get_by_ID( $wico_ID );

		if( get_default_widgets_by_container( $edited_WidgetContainer->get( 'code' ) ) == false )
		{	// Display error when container is not found 
			// Do NOT translate because it must not happen in normal work:
			$Messages->add( sprintf( '%s cannot be reloaded because it is not configured for default widgets.', $edited_WidgetContainer->get_type_title().' "'.$edited_WidgetContainer->get( 'name' ).'"' ), 'error' );
		}
		else
		{	// Reload widgets:
			$DB->query( 'DELETE FROM T_widget__widget
				WHERE wi_wico_ID = '.$edited_WidgetContainer->ID );
			$new_inserted_widgets_num = install_new_default_widgets( $edited_WidgetContainer->get( 'code' ), '*', $Blog->ID, $edited_WidgetContainer->get( 'skin_type' ) );
			$Messages->add( sprintf( TB_('%s has been reset to defaults (populated with %d widgets).'), $edited_WidgetContainer->get_type_title().' "'.$edited_WidgetContainer->get( 'name' ).'"', $new_inserted_widgets_num ), 'success' );
		}

		// Redirect back to back-office widgets list:
		header_redirect( get_admin_url( 'ctrl=widgets&blog='.$Blog->ID.'&skin_type='.$edited_WidgetContainer->get( 'skin_type' ), '&' ), 303 );
		break;

	default:
		debug_die( 'Action: unhandled action' );
}

if( $display_mode == 'normal' )
{	// this is a normal (not a JS) request
	// fp> This probably shouldn't be handled like this but with $mode
	/**
	 * Display page header, menus & messages:
	 */
	$AdminUI->set_coll_list_params( 'blog_properties', 'edit', array( 'ctrl' => 'widgets' ) );

	$AdminUI->set_path( 'collections', 'widgets', 'skin_'.$skin_type );

	// We should activate toolbar menu items for this controller and mode
	$activate_collection_toolbar = true;

	// load the js and css required to make the magic work
	add_js_headline( '
	/**
	 * @internal T_ array of translation strings required by the UI
	 */
	var T_arr = new Array();
	T_arr["Changes pending"] = \''.TS_( 'Changes pending' ).'\';
	T_arr["Saving changes"] = \''.TS_( 'Saving changes' ).'\';
	T_arr["Widget order unchanged"] = \''.TS_( 'Widget order unchanged' ).'\';
	T_arr["Update cancelled"] = \''.TS_( 'Update cancelled' ).'\';
	T_arr["Update Paused"] = \''.TS_( 'Update Paused' ).'\';

	/**
	 * Image tags for the JavaScript widget UI.
	 *
	 * @internal Tblue> We get the whole img tags here (easier).
	 */
	var edit_icon_tag = \''.get_icon( 'edit', 'imgtag', array( 'title' => TB_( 'Edit widget settings!' ) ) ).'\';
	var duplicate_icon_tag = \''.get_icon( 'duplicate', 'imgtag', array( 'title' => TB_('Duplicate') ) ).'\';
	var delete_icon_tag = \''.get_icon( 'delete', 'imgtag', array( 'title' => TB_( 'Remove this widget!' ) ) ).'\';
	var enabled_icon_tag = \''.get_icon( 'bullet_green', 'imgtag', array( 'title' => TB_( 'The widget is enabled.' ) ) ).'\';
	var disabled_icon_tag = \''.get_icon( 'bullet_empty_grey', 'imgtag', array( 'title' => TB_( 'The widget is disabled.' ) ) ).'\';
	var disabled_plugin_tag = \''.get_icon( 'warning', 'imgtag', array( 'title' => TB_('Inactive / Uninstalled plugin') ) ).'\';
	var activate_icon_tag = \''.get_icon( 'activate', 'imgtag', array( 'title' => TB_( 'Enable this widget!' ) ) ).'\';
	var deactivate_icon_tag = \''.get_icon( 'deactivate', 'imgtag', array( 'title' => TB_( 'Disable this widget!' ) ) ).'\';
	var cache_enabled_icon_tag = \''.get_icon( 'block_cache_on', 'imgtag', array( 'title' => TB_( 'Caching is enabled. Click to disable.' ) ) ).'\';
	var cache_disabled_icon_tag = \''.get_icon( 'block_cache_off', 'imgtag', array( 'title' => TB_( 'Caching is disabled. Click to enable.' ) ) ).'\';
	var cache_disallowed_icon_tag = \''.get_icon( 'block_cache_disabled', 'imgtag', array( 'title' => TB_( 'This widget cannot be cached.' ) ) ).'\';
	var cache_denied_icon_tag = \''.get_icon( 'block_cache_denied', 'imgtag', array( 'title' => TB_( 'This widget could be cached but the block cache is OFF. Click to enable.' ) ) ).'\';

	var widget_crumb_url_param = \''.url_crumb( 'widget' ).'\';

	var b2evo_dispatcher_url = "'.get_admin_url().'";' );
	require_js_defer( '#jqueryUI#' ); // auto requires jQuery
	require_css( 'blog_widgets.css' );
	init_tokeninput_js();
	init_hotkeys_js( 'blog', array( 'ctrl+s', 'command+s', 'ctrl+enter', 'command+enter' ) );

	if( $action == 'list' && $Session->get( 'refresh_customizer_window' ) )
	{	// This is a request to refresh customizer back-office window:
		// (e.g. used to display new auto created sub-container by widget "Columns(Sub-Containers)" - subcontainer_row_Widget)
		add_js_headline( 'window.parent.document.getElementById( "evo_customizer__backoffice" ).contentDocument.location.reload();' );
		// Clear temp variable:
		$Session->delete( 'refresh_customizer_window' );
	}

	$AdminUI->breadcrumbpath_init( true, array( 'text' => T_('Collections'), 'url' => get_admin_url( 'ctrl=collections' ) ) );
	$AdminUI->breadcrumbpath_add( T_('Widgets'), get_admin_url( 'ctrl=widgets&amp;blog=$blog$' ) );

	// Set an url for manual page:
	$AdminUI->set_page_manual_link( 'widget-settings' );

	// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
	$AdminUI->disp_html_head();

	// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
	$AdminUI->disp_body_top();
}

/**
 * Display payload:
 */
switch( $action )
{
	case 'nil':
		// Do nothing
		break;


	case 'new':
		// Begin payload block:
		$AdminUI->disp_payload_begin();

		// Display VIEW:
		$AdminUI->disp_view( 'widgets/views/_widget_list_available.view.php' );

		// End payload block:
		$AdminUI->disp_payload_end();
		break;

	case 'new_container':
	case 'edit_container':
	case 'create_container':
	case 'update_container':
		// Begin payload block:
		$AdminUI->disp_payload_begin();

		// Display VIEW:
		$AdminUI->disp_view( 'widgets/views/_widget_container.form.php' );

		// End payload block:
		$AdminUI->disp_payload_end();
		break;

	case 'edit':
	case 'update':	// on error
	case 'update_edit':
		switch( $display_mode )
		{
			case 'js' : // js request
				ob_start();
				// Display VIEW:
				$AdminUI->disp_view( 'widgets/views/_widget.form.php' );
				$output = ob_get_clean();
				send_javascript_message( array(
						'widgetSettings' => array( $output, $edited_ComponentWidget->get( 'type' ), $edited_ComponentWidget->get( 'code' ) ),
						'evo_initialize_colorpicker_inputs' => array(),
					) );
				break;

			case 'normal' :
			default : // take usual action
				// Begin payload block:
				$AdminUI->disp_payload_begin();

				// Display VIEW:
				$AdminUI->disp_view( 'widgets/views/_widget.form.php' );

				// End payload block:
				$AdminUI->disp_payload_end();
				break;
		}
		break;


	case 'add_list':
		// A list of widgets which can be added:
		switch( $mode )
		{
			case 'customizer':
				if( preg_match( '#^(coll|shared):(.+)$#', $container_code, $container_match ) )
				{	// Sub-container may be from Collection and may be Shared:
					$container_coll_ID = $container_match[1] == 'coll' ? $blog : NULL;
					$container_code = $container_match[2];
				}
				else
				{	// Normal container:
					$container_coll_ID = $blog;
				}

				// Try to get widget container by collection ID, container code and requested skin type:
				$WidgetContainerCache = & get_WidgetContainerCache();
				$WidgetContainer = & $WidgetContainerCache->get_by_coll_skintype_code( $container_coll_ID, $skin_type, $container_code );

				// Change this param to proper work of func get_WidgetContainer_by_coll_skintype_fieldset():
				set_param( 'container', 'wico_ID_'.$WidgetContainer->ID );

				$AdminUI->disp_view( 'widgets/views/_widget_list_available.view.php' );
				break;
		}
		break;

	case 'customize':
		if( preg_match( '#^(coll|shared):(.+)$#', $container_code, $container_match ) )
		{	// Sub-container may be from Collection and may be Shared:
			$container_coll_ID = $container_match[1] == 'coll' ? $blog : NULL;
			$container_code = $container_match[2];
		}
		else
		{	// Normal container:
			$container_coll_ID = $blog;
		}

		if( ! empty( $container_code ) )
		{	// Try to get widget container by collection ID, container code and requested skin type:
			$WidgetContainerCache = & get_WidgetContainerCache();
			$selected_WidgetContainer = & $WidgetContainerCache->get_by_coll_skintype_code( $container_coll_ID, $skin_type, $container_code );
		}
		$AdminUI->disp_view( 'widgets/views/_widget_customize.form.php' );
		break;

	case 'list':
	default:
		// Begin payload block:
		$AdminUI->disp_payload_begin();

		// Display VIEW:

		// this will be enabled if js available:
		echo '<div class="available_widgets">'."\n";
		echo '<div class="available_widgets_toolbar modal-header">'
						.'<a href="#" class="floatright close">'.get_icon('close').'</a>'
						.'<h4 class="modal-title">'.TB_( 'Select widget to add:' ).'</h4>'
					.'</div>'."\n";
		echo '<div id="available_widgets_inner">'."\n";
		$AdminUI->disp_view( 'widgets/views/_widget_list_available.view.php' );
		echo '</div></div>'."\n";

		// Display VIEW:
		$AdminUI->disp_view( 'widgets/views/_widget_list.view.php' );

		// End payload block:
		$AdminUI->disp_payload_end();
		break;
}

if( $display_mode == 'normal' )
{	// Normal mode:
	// Display body bottom, debug info and close </html>:
	$AdminUI->disp_global_footer();
}
?>
