<?php
/**
 * This is the handler for asynchronous 'AJAX' calls. This requires access to the back office.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * fp> TODO: it would be better to have the code for the actions below part of the controllers they belong to.
 * This would require some refectoring but would be better for maintenance and code clarity.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */


/**
 * Do the MAIN initializations:
 */
require_once dirname(__FILE__).'/../conf/_config.php';

/**
 * @global boolean Is this AJAX request? Use {@link is_ajax_request()} to query it, because it may change.
 */
$is_ajax_request = true;

/**
 * HEAVY :(
 *
 * @todo dh> refactor _main.inc.php to be able to include small parts
 *           (e.g. $current_User, charset init, ...) only..
 *           It worked already for $DB (_connect_db.inc.php).
 * fp> I think I'll try _core_main.inc , _evo_main.inc , _blog_main.inc ; this file would only need _core_main.inc
 */
require_once $inc_path.'_main.inc.php';

// create global $blog variable
global $blog;
// Init $blog with NULL to avoid injections, it will get the correct value from param where it is required
$blog = NULL;

param( 'action', 'string', '' );

// Check global permission:
if( $action != 'test_api' && ! check_user_perm( 'admin', 'restricted' ) )
{	// No permission to access admin... (Exclude action of API testing in order to make a quick request without logging in)
	require $adminskins_path.'_access_denied.main.php';
}

// Send the predefined cookies:
evo_sendcookies();

// Make sure the async responses are never cached:
header_cache('nocache');
header_content_type( 'text/html', $io_charset );

// Save current debug values
$current_debug = $debug;
$current_debug_jslog = $debug_jslog;

// Do not append Debuglog to response!
$debug = false;

// Do not append Debug JSlog to response!
$debug_jslog = false;

// Don't check new updates from b2evolution.net (@see b2evonet_get_updates()),
// in order to don't break the response data:
$allow_evo_stats = false;

// Init AJAX log
$ajax_Log = new Log();

ajax_log_add( sprintf( T_('action: %s'), $action ), 'note' );

$incorrect_action = false;

$add_response_end_comment = true;

// fp> Does the following have an HTTP fallback when Javascript/AJ is not available?
// dh> yes, but not through this file..
// dh> IMHO it does not make sense to let the "normal controller" handle the AJAX call
//     if there's something lightweight like calling "$UserSettings->param_Request()"!
//     Hmm.. bad example (but valid). Better example: something like the actions below, which
//     output only a small part of what the "real controller" does..
switch( $action )
{
	case 'get_whois_info':
		param( 'query', 'string' );
		param( 'window_height', 'integer' );

		load_funcs( 'antispam/model/_antispam.funcs.php' );
		echo antispam_get_whois( $query, $window_height );
		break;

	case 'add_plugin_sett_set':
		// Dislay a new Plugin(User)Settings set ( it's used only from plugins with "array" type settings):

		// This does not require CSRF because it doesn't update the db, it only displays a new block of empty plugin setting fields

		// Check permission to view plugin settings:
		check_user_perm( 'options', 'view', true );

		// Set admin skin, used for buttons, @see button_class()
		$admin_skin = $UserSettings->get( 'admin_skin', $current_User->ID );
		require_once $adminskins_path.$admin_skin.'/_adminUI.class.php';
		$AdminUI = new AdminUI();

		param( 'plugin_ID', 'integer', true );
		param( 'set_type', 'string', '' ); // 'Settings', 'UserSettings', 'CollSettings', 'MsgSettings', 'EmailSettings', 'Skin', 'Widget'

		if( ! in_array( $set_type, array( 'Settings', 'UserSettings', 'CollSettings', 'MsgSettings', 'EmailSettings', 'Skin', 'Widget' ) ) )
		{
			bad_request_die( 'Invalid set_type param!' );
		}

		param( 'blog', 'integer', 0 );
		$BlogCache = & get_BlogCache();
		$Blog = & $BlogCache->get_by_ID( $blog, false, false );

		$target_Object = NULL;

		switch( $set_type )
		{
			case 'Widget':
				$WidgetCache = & get_WidgetCache();
				$Widget = & $WidgetCache->get_by_ID( $plugin_ID );
				if( ( $Plugin = & $Widget->get_Plugin() ) )
				{	// Set abstract type for Widget initialized from Plugin:
					$set_type = 'PluginWidget';
				}
				else
				{	// This is a normal Widget:
					$Plugin = $Widget;
				}
				$plugin_Object = $Widget;
				break;

			case 'Skin':
				$SkinCache = & get_SkinCache();
				$Skin = & $SkinCache->get_by_ID( $plugin_ID );
				$Plugin = $Skin;
				$plugin_Object = $Skin;
				break;

			default:
				// 'Settings', 'UserSettings', 'CollSettings', 'MsgSettings', 'EmailSettings'
				$admin_Plugins = & get_Plugins_admin(); // use Plugins_admin, because a plugin might be disabled
				$Plugin = & $admin_Plugins->get_by_ID( $plugin_ID );
				$plugin_Object = $Plugin;
				if( $set_type == 'UserSettings' )
				{	// Initialize User object for this plugin type:
					param( 'user_ID', 'integer', true );
					$UserCache = & get_UserCache();
					$target_Object = & $UserCache->get_by_ID( $user_ID );
				}
				break;
		}

		if( ! $Plugin )
		{
			bad_request_die('Invalid Plugin.');
		}
		param( 'param_name', 'string', '' );
		param( 'param_num', 'integer', '' );
		$set_path = $param_name.'['.$param_num.']';

		load_funcs('plugins/_plugin.funcs.php');

		// Init the new setting set:
		$set_node = _set_setting_by_path( $Plugin, $set_type, $set_path, array() );

		// Get the new plugin setting set and display it with a fake Form
		$r = get_plugin_settings_node_by_path( $Plugin, $set_type, $set_path, /* create: */ false );

		$Form = new Form(); // fake Form to display plugin setting
		autoform_display_field( $set_path, $r['set_meta'], $Form, $set_type, $plugin_Object, $target_Object, $set_node );
		break;

	case 'edit_comment':
		// Used to edit a comment from back-office (Note: Only for internal comments now!)

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		$comment_ID = param( 'commentid', 'integer' );

		// Get Comment by ID
		$CommentCache = & get_CommentCache();
		$edited_Comment = & $CommentCache->get_by_ID( $comment_ID );
		// Load Item
		$edited_Comment_Item = & $edited_Comment->get_Item();

		// Check user permission to edit this internal comment
		check_user_perm( 'meta_comment', 'edit', true, $edited_Comment );

		// Load Blog of the Item
		$Collection = $Blog = & $edited_Comment_Item->get_Blog();

		$comment_action = param( 'comment_action', 'string' );

		switch( $comment_action )
		{
			case 'form':
				// Display a form to change a content of the Comment
				$comment_title = '';
				$comment_content = htmlspecialchars_decode( $edited_Comment->content );

				// Format content for editing, if we were not already in editing...
				$Plugins_admin = & get_Plugins_admin();
				$params = array( 'object_type' => 'Comment', 'object_Blog' => & $Blog );
				$Plugins_admin->unfilter_contents( $comment_title /* by ref */, $comment_content /* by ref */, $edited_Comment_Item->get_renderers_validated(), $params );

				// Display <textarea> to change a content
				$rows_approx = round( strlen( $comment_content ) / 200 );
				$rows_number = substr_count( $comment_content, "\n" );
				$rows_number = ( ( $rows_number > 3 && $rows_number > $rows_approx ) ? $rows_number : $rows_approx ) + 2;
				echo '<textarea class="form_textarea_input form-control'.( check_autocomplete_usernames( $edited_Comment ) ? ' autocomplete_usernames' : '' ).'" rows="'.$rows_number.'">'.$comment_content.'</textarea>';
				// Display a button to Save the changes
				echo '<input type="button" value="'.T_('Save Changes!').'" class="SaveButton btn btn-primary" onclick="edit_comment( \'update\', '.$edited_Comment->ID.' )" /> ';
				// Display a button to Cancel the changes
				echo '<input type="button" value="'.T_('Cancel').'" class="ResetButton btn btn-danger" onclick="edit_comment( \'cancel\', '.$edited_Comment->ID.' )" />';
				break;

			case 'update':
				// Save the changed content
				if( $Blog->get_setting( 'allow_html_comment' ) )
				{ // HTML is allowed for this comment
					$text_format = 'html';
				}
				else
				{ // HTML is disallowed for this comment
					$text_format = 'htmlspecialchars';
				}

				$comment_content = param( 'comment_content', $text_format );

				// Trigger event: a Plugin could add a $category="error" message here..
				// This must get triggered before any internal validation and must pass all relevant params.
				// The OpenID plugin will validate a given OpenID here (via redirect and coming back here).
				$Plugins->trigger_event( 'CommentFormSent', array(
						'dont_remove_pre' => true,
						'comment_item_ID' => $edited_Comment_Item->ID,
						'comment' => & $comment_content,
						'renderers' => $edited_Comment->get_renderers_validated(),
					) );

				// Update the content
				$edited_Comment->set( 'content', $comment_content );
				$edited_Comment->dbupdate();

				// Display new content
				$edited_Comment->content( 'htmlbody', 'true' );
				break;

			case 'cancel':
				// The changes were canceled, Display old content
				$edited_Comment->content( 'htmlbody', 'true' );
				break;
		}

		break;

	case 'get_opentrash_link':
		// Used to get a link 'Open recycle bin' in order to show it in the header of comments list

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		// Set admin skin, used for buttons, @see button_class()
		$admin_skin = $UserSettings->get( 'admin_skin', $current_User->ID );
		require_once $adminskins_path.$admin_skin.'/_adminUI.class.php';
		$AdminUI = new AdminUI();

		param( 'blog', 'integer', 0 );

		echo get_opentrash_link( true, true, array(
				'before' => ' <span id="recycle_bin">',
				'after' => '</span>',
				'class' => 'btn btn-default'.( param( 'request_from', 'string' ) == 'items' ? '' : ' btn-sm' ),
			) );
		break;

	case 'delete_comment':
		// Delete a comment from the list on dashboard, on comments full text view screen or on a view item screen

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		$result_success = false;

		// Set admin skin, used for buttons, @see button_class()
		$admin_skin = $UserSettings->get( 'admin_skin', $current_User->ID );
		require_once $adminskins_path.$admin_skin.'/_adminUI.class.php';
		$AdminUI = new AdminUI();

		// Check comment moderate permission below after we have the $edited_Comment objects

		$is_admin_page = true;
		$blog = param( 'blogid', 'integer' );
		$comment_ID = param( 'commentid', 'integer' );
		$statuses = param( 'statuses', 'string', NULL );
		$expiry_status = param( 'expiry_status', 'string', 'active' );
		$item_ID = param( 'itemid', 'integer' );
		$currentpage = param( 'currentpage', 'integer', 1 );
		$limit = param( 'limit', 'integer', 0 );
		$request_from = param( 'request_from', 'string', NULL );
		$comment_type = param( 'comment_type', 'string', 'feedback' );

		$edited_Comment = & Comment_get_by_ID( $comment_ID, false );
		if( $edited_Comment !== false )
		{ // The comment still exists
			// Check permission:
			check_user_perm( 'comment!CURSTATUS', 'delete', true, $edited_Comment );

			$result_success = $edited_Comment->dbdelete();
		}

		if( $result_success === false )
		{ // Some errors on deleting of the comment, Exit here
			header_http_response( '500 '.T_('Comment cannot be deleted!'), 500 );
			exit(0);
		}

		if( in_array( $request_from, array( 'items', 'comments' ) ) )
		{	// AJAX request goes from backoffice and ctrl = items or comments:

			// In case of comments_fullview we must set a filterset name to be abble to restore filterset.
			// If $item_ID is not valid, then this requests came from the comments_fullview
			// TODO: asimo> This should be handled with a better solution
			$filterset_name = /*'';*/( $item_ID > 0 ) ? '' : ( $comment_type == 'meta' ? 'meta' : 'fullview' );

			echo_item_comments( $blog, $item_ID, $statuses, $currentpage, $limit, array(), $filterset_name, $expiry_status, $comment_type );
		}
		break;

	case 'delete_comment_url':
		// Delete spam URL from a comment directly in the dashboard - comment remains otherwise untouched

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		// Check comment edit permission below after we have the $edited_Comment object

		$blog = param( 'blogid', 'integer' );
		$edited_Comment = & Comment_get_by_ID( param( 'commentid', 'integer' ), false );
		if( $edited_Comment !== false && $edited_Comment->author_url != NULL )
		{	// The comment still exists
			// Check permission:
			check_user_perm( 'comment!CURSTATUS', 'edit', true, $edited_Comment );

			$edited_Comment->set( 'author_url', NULL );
			$edited_Comment->dbupdate();
		}

		break;

	case 'refresh_comments':
		// Refresh the comments list on dashboard by clicking on the refresh icon or after ban url
		// Refresh item comments on the item view screen, or refresh all blog comments on comments view, if param itemid = -1
		// A refresh is used on the actions:
		// 1) click on the refresh icon.
		// 2) limit by selected status(radioboxes 'Draft', 'Published', 'All comments').
		// 3) ban by url of a comment

		$is_admin_page = true;
		$blog = param( 'blogid', 'integer' );
		$item_ID = param( 'itemid', 'integer', NULL );
		$statuses = param( 'statuses', 'string', NULL );
		$expiry_status = param( 'expiry_status', 'string', 'active' );
		$currentpage = param( 'currentpage', 'string', 1 );
		$request_from = param( 'request_from', 'string', 'items' );
		$comment_type = param( 'comment_type', 'string', 'feedback' );

		// Ininitialize global collection object:
		$BlogCache = & get_BlogCache();
		$Blog = & $BlogCache->get_by_ID( $blog );

		// Check minimum permissions ( The comment specific permissions are checked when displaying the comments )
		check_user_perm( 'blog_ismember', 'view', true, $blog );

		// Set admin skin, used for buttons, @see button_class()
		$admin_skin = $UserSettings->get( 'admin_skin', $current_User->ID );
		require_once $adminskins_path.$admin_skin.'/_adminUI.class.php';
		$AdminUI = new AdminUI();

		if( in_array( $request_from, array( 'items', 'comments' ) ) )
		{	// AJAX request goes from backoffice and ctrl = items or comments
			echo_item_comments( $blog, $item_ID, $statuses, $currentpage, NULL, array(), '', $expiry_status, $comment_type );
		}
		elseif( $request_from == 'dashboard' || $request_from == 'coll_settings' )
		{ // AJAX request goes from backoffice dashboard
			load_funcs( 'dashboard/model/_dashboard.funcs.php' );
			show_comments_awaiting_moderation( $blog, NULL, 10, array(), false );
		}
		break;

	case 'dom_type_edit':
		// Update type of a reffering domain from list screen by clicking on the type column

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'domtype' );

		// Check permission:
		check_user_perm( 'stats', 'edit', true );

		load_funcs('sessions/model/_hitlog.funcs.php');

		$dom_type = param( 'new_dom_type', 'string' );
		$dom_name = param( 'dom_name', 'string' );

		$DB->query( 'UPDATE T_basedomains
						SET dom_type = '.$DB->quote($dom_type).'
						WHERE dom_name =' . $DB->quote($dom_name));
		echo '<a href="#" rel="'.$dom_type.'">'.stats_dom_type_title( $dom_type ).'</a>';
		break;

	case 'dom_status_edit':
		// Update status of a reffering domain from list screen by clicking on the type column

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'domstatus' );

		// Check permission:
		check_user_perm( 'stats', 'edit', true );

		load_funcs('sessions/model/_hitlog.funcs.php');

		$dom_status = param( 'new_dom_status', 'string' );
		$dom_name = param( 'dom_name', 'string' );

		$DB->query( 'UPDATE T_basedomains
						SET dom_status = '.$DB->quote($dom_status).'
						WHERE dom_name =' . $DB->quote($dom_name));
		echo '<a href="#" rel="'.$dom_status.'" color="'.stats_dom_status_color( $dom_status ).'">'.stats_dom_status_title( $dom_status ).'</a>';
		break;

	case 'iprange_status_edit':
		// Update status of IP range from list screen by clicking on the status column

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'iprange' );

		// Check permission:
		check_user_perm( 'spamblacklist', 'edit', true );

		$new_status = param( 'new_status', 'string' );
		$iprange_ID = param( 'iprange_ID', 'integer', true );

		$DB->query( 'UPDATE T_antispam__iprange
						SET aipr_status = '.( empty( $new_status ) ? 'NULL' : $DB->quote( $new_status ) ).'
						WHERE aipr_ID =' . $DB->quote( $iprange_ID ) );
		echo '<a href="#" rel="'.$new_status.'" color="'.aipr_status_color( $new_status ).'">'.aipr_status_title( $new_status ).'</a>';
		break;

	case 'emadr_status_edit':
		// Update status of email address

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'emadrstatus' );

		// Check permission:
		check_user_perm( 'emails', 'edit', true );

		$new_status = param( 'new_status', 'string' );
		$emadr_ID = param( 'emadr_ID', 'integer', true );

		load_funcs('tools/model/_email.funcs.php');

		$DB->query( 'UPDATE T_email__address
						SET emadr_status = '.( empty( $new_status ) ? 'NULL' : $DB->quote( $new_status ) ).'
						WHERE emadr_ID =' . $DB->quote( $emadr_ID ) );
		echo '<a href="#" rel="'.$new_status.'" color="'.emadr_get_status_color( $new_status ).'">'.emadr_get_status_title( $new_status ).'</a>';
		break;

	case 'user_level_edit':
		// Update level of an user from list screen by clicking on the level column

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'userlevel' );

		$user_level = param( 'new_user_level', 'integer' );
		$user_ID = param( 'user_ID', 'integer' );

		// Check permission:
		$current_User->can_moderate_user( $user_ID, true );

		$UserCache = & get_UserCache();
		if( $User = & $UserCache->get_by_ID( $user_ID, false ) )
		{
			$User->set( 'level', $user_level );
			$User->dbupdate();
			echo '<a href="#" rel="'.$user_level.'">'.$user_level.'</a>';
		}
		break;

	case 'group_level_edit':
		// Update level of a group from list screen by clicking on the level column

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'grouplevel' );

		// Check permission:
		check_user_perm( 'users', 'edit', true );

		$group_level = param( 'new_group_level', 'integer' );
		$group_ID = param( 'group_ID', 'integer' );

		$GroupCache = & get_GroupCache();
		if( $Group = & $GroupCache->get_by_ID( $group_ID, false, false ) )
		{
			$Group->set( 'level', $group_level );
			$Group->dbupdate();
			echo '<a href="#" rel="'.$group_level.'">'.$group_level.'</a>';
		}
		break;

	case 'country_status_edit':
		// Update status of Country from list screen by clicking on the status column

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'country' );

		// Check permission:
		check_user_perm( 'options', 'edit', true );

		load_funcs( 'regional/model/_regional.funcs.php' );

		$new_status = param( 'new_status', 'string' );
		$ctry_ID = param( 'ctry_ID', 'integer', true );

		$DB->query( 'UPDATE T_regional__country
						SET ctry_status = '.( empty( $new_status ) ? 'NULL' : $DB->quote( $new_status ) ).'
						WHERE ctry_ID =' . $DB->quote( $ctry_ID ) );
		echo '<a href="#" rel="'.$new_status.'" color="'.ctry_status_color( $new_status ).'">'.ctry_status_title( $new_status ).'</a>';
		break;

	case 'item_task_edit':
		// Update task fields of Item from list screen by clicking on the cell

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'itemtask' );

		$field = param( 'field', 'string' );
		if( ! in_array( $field, array( 'priority', 'status', 'assigned' ) ) )
		{ // Invalid field
			ajax_log_add( sprintf( 'Invalid field: %s', $field ), 'error' );
			break;
		}

		$post_ID = param( 'post_ID', 'integer', true );

		$ItemCache = & get_ItemCache();
		$Item = & $ItemCache->get_by_ID( $post_ID );

		// Check permission:
		check_user_perm( 'item_post!CURSTATUS', 'edit', true, $Item );

		$new_attrs = '';
		switch( $field )
		{
			case 'priority':
				// Update task priority
				$new_value = param( 'new_priority', 'integer', NULL );
				$new_attrs = ' color="'.item_priority_color( $new_priority ).'"';
				$new_title = item_priority_title( $new_priority );
				$Item->set_from_Request( 'priority', 'new_priority', true );
				$Item->dbupdate();
				break;

			case 'assigned':
				// Update task assigned user
				$new_assigned_ID = param( 'new_assigned_ID', 'integer', NULL );
				$new_assigned_login = param( 'new_assigned_login', 'string', NULL );
				if( $Item->assign_to( $new_assigned_ID, $new_assigned_login ) )
				{ // An assigned user can be changed
					$Item->dbupdate();
					$Item->send_assignment_notification();
				}
				else
				{ // Error on changing of an assigned user
					load_funcs('_core/_template.funcs.php');
					headers_content_mightcache( 'text/html', 0, '#', false );		// Do NOT cache error messages! (Users would not see they fixed them)
					header_http_response('400 Bad Request');
					// This message is displayed after an input field
					echo T_('Username not found!');
					die(2); // Error code 2. Note: this will still call the shutdown function.
					// EXIT here!
				}

				if( empty( $Item->assigned_user_ID ) )
				{
					$new_title = T_('No user');
				}
				else
				{
					$is_admin_page = true;
					$UserCache = & get_UserCache();
					$User = & $UserCache->get_by_ID( $Item->assigned_user_ID );
					$new_title = $User->get_colored_login( array( 'mask' => '$avatar$ $login$' ) );
				}
				$new_value = $Item->assigned_user_ID;
				break;

			case 'status':
				// Update task status
				$new_value = param( 'new_status', 'string', NULL );
				// Remove '_' that is used to don't break a sorting by name on jeditable:
				$new_value = intval( str_replace( '_', '', $new_value ) );
				set_param( 'new_status', $new_value );

				$Item->set_from_Request( 'pst_ID', 'new_status', true );
				$Item->dbupdate();

				$new_title = empty( $Item->pst_ID ) ? T_('No status') : $Item->get( 't_extra_status' );
				if( ! empty( $new_value ) )
				{	// Add '_' to don't break a sorting by name on jeditable:
					$new_value = '_'.$new_value;
				}
				break;
		}

		// Return a link to make the cell editable on next time
		echo '<a href="#" rel="'.$new_value.'"'.$new_attrs.'>'.$new_title.'</a>';
		break;

	case 'item_order_edit':
		// Update an order of Item from list screen by clicking on the cell:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'itemorder' );

		$item_order = param( 'new_item_order', 'string' );
		$post_ID = param( 'post_ID', 'integer' );
		$blog = param( 'blog', 'integer', 0 );
		$cat_ID = param( 'cat_ID', 'integer', NULL );

		$ItemCache = & get_ItemCache();
		$Item = & $ItemCache->get_by_ID( $post_ID );

		// Check permission:
		check_user_perm( 'item_post!CURSTATUS', 'edit', true, $Item );

		if( $item_order === '-' || $item_order === '' )
		{	// Set NULL for these values:
			$item_order = NULL;
		}
		else
		{	// Make an order to double:
			$item_order = floatval( $item_order );
		}

		$Item->update_order( $item_order, $cat_ID, $blog );

		// Return a link to make the cell editable on next time:
		echo '<a href="#" rel="'.$Item->ID.'">'.( $item_order === NULL ? '-' : $item_order ).'</a>';
		break;

	case 'cat_order_edit':
		// Update order of a chapter from list screen by clicking on the order column

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'catorder' );

		$blog = param( 'blogid', 'integer' );
		$cat_order = param( 'new_cat_order', 'string' );
		$cat_ID = param( 'cat_ID', 'integer' );

		// Check permission:
		check_user_perm( 'blog_cats', 'edit', true, $blog );

		if( $cat_order === '-' || $cat_order === '' || intval( $cat_order ) == '' )
		{ // Set NULL for these values
			$cat_order = NULL;
		}

		$ChapterCache = & get_ChapterCache();
		if( $Chapter = & $ChapterCache->get_by_ID( $cat_ID, false ) )
		{ // Update cat order if it exists in DB
			$Chapter->set( 'order', ( $cat_order === '' ? NULL : $cat_order ), true );
			$Chapter->dbupdate();
			echo '<a href="#">'.( $cat_order === NULL ? '-' : $cat_order ).'</a>';
		}
		break;

	case 'item_status_order_edit':
		// Update order of a item status from list screen by clicking on the order column

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'itemstatus' );

		$item_status_order = param( 'new_item_status_order', 'string' );

		// Make sure we got an pst_ID:
		$item_status_ID = param( 'pst_ID', 'integer', true );

		// Check permission:
		check_user_perm( 'options', 'edit', true );

		if( $item_status_order === '-' || $item_status_order === '' )
		{	// Set NULL for these values:
			$item_status_order = NULL;
		}
		else
		{	// Make an order to integer:
			$item_status_order = intval( $item_status_order );
		}

		$ItemStatusCache = & get_ItemStatusCache();
		if( $edited_ItemStatus = & $ItemStatusCache->get_by_ID( $item_status_ID, false ) )
		{ // Update item status order if it exists in DB
			$edited_ItemStatus->set( 'order', ( $item_status_order === '' ? NULL : $item_status_order ), true );
			$edited_ItemStatus->dbupdate();
			echo '<a href="#">'.( $item_status_order === NULL ? '-' : $item_status_order ).'</a>';
		}
		break;

	case 'cat_ityp_ID_edit':
		// Update default Item Type of a chapter from list screen by clicking on the order column:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'catityp' );

		$blog = param( 'blogid', 'integer' );
		$cat_ityp_ID = param( 'new_ityp_ID', 'string', NULL );
		$cat_ID = param( 'cat_ID', 'integer' );

		// Check permission:
		check_user_perm( 'blog_cats', '', true, $blog );

		if( ! empty( $cat_ityp_ID ) )
		{	// Remove prefix "_" which is used only for correct order in jeditable selector:
			$cat_ityp_ID = substr( $cat_ityp_ID, 1 );
		}
		if( $cat_ityp_ID === false || $cat_ityp_ID === '' )
		{	// Convert empty value to NULL to update DB:
			$cat_ityp_ID = NULL;
		}

		$ChapterCache = & get_ChapterCache();
		if( $Chapter = & $ChapterCache->get_by_ID( $cat_ID, false ) )
		{	// Update cat Item Type if it exists in DB:
			$ItemTypeCache = & get_ItemTypeCache();
			if( ! empty( $cat_ityp_ID ) &&
			    ( ! ( $ItemType = & $ItemTypeCache->get_by_ID( $cat_ityp_ID, false, false ) ) ||
			      ! $ItemType->is_enabled( $blog ) ) )
			{	// Revert back to use previous Item Type if new is wrong for current category:
				$cat_ityp_ID = $Chapter->get( 'ityp_ID' );
			}
			$Chapter->set( 'ityp_ID', $cat_ityp_ID, true );
			$Chapter->dbupdate();
			if( $Chapter->get( 'ityp_ID' ) === NULL )
			{
				$cat_ityp_title = T_('Same as collection default');
			}
			elseif( $Chapter->get( 'ityp_ID' ) == '0' )
			{
				$cat_ityp_title = '<b>'.T_('No default type').'</b>';
			}
			elseif( $ItemType = & $ItemTypeCache->get_by_ID( $Chapter->get( 'ityp_ID' ), false, false ) )
			{
				$cat_ityp_title = $ItemType->get_name();
			}
			else
			{
				$cat_ityp_title = '<span class="red">'.T_('Not Found').' #'.$Chapter->get( 'ityp_ID' ).'</span>';
			}
			echo '<a href="#" rel="_'.$Chapter->get( 'ityp_ID' ).'">'.$cat_ityp_title.'</a>';
		}
		break;

	case 'get_goals':
		// Get option list with goals by selected category
		$blog = param( 'blogid', 'integer' );

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'itemgoal' );

		// Check permission:
		check_user_perm( 'blog_post_statuses', 'edit', true, $blog );

		$cat_ID = param( 'cat_id', 'integer', 0 );

		$SQL = new SQL();
		$SQL->SELECT( 'goal_ID, goal_name' );
		$SQL->FROM( 'T_track__goal' );
		$SQL->WHERE( 'goal_redir_url IS NULL' );
		if( empty( $cat_ID ) )
		{ // Select the goals without category
			$SQL->WHERE_and( 'goal_gcat_ID IS NULL' );
		}
		else
		{ // Get the goals from a selected category
			$SQL->WHERE_and( 'goal_gcat_ID = '.$DB->quote( $cat_ID ) );
		}
		$goals = $DB->get_assoc( $SQL->get() );

		echo '<option value="">'.T_('None').'</option>';

		foreach( $goals as $goal_ID => $goal_name )
		{
			echo '<option value="'.$goal_ID.'">'.$goal_name.'</option>';
		}

		break;

	case 'conflict_files':
		// Replace old file with new and set new name for old file

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'conflictfiles' );

		param( 'fileroot_ID', 'string' );

		// Check permission:
		check_user_perm( 'files', 'add', true, $fileroot_ID );

		param( 'path', 'filepath' );
		param( 'oldfile', 'filepath' );
		param( 'newfile', 'filepath' );
		param( 'format', 'string' );

		$fileroot = explode( '_', $fileroot_ID );
		$fileroot_type = $fileroot[0];
		$fileroot_type_ID = empty( $fileroot[1] ) ? 0 : $fileroot[1];

		$result = replace_old_file_with_new( $fileroot_type, $fileroot_type_ID, $path, $newfile, $oldfile, false );

		$data = array();
		if( $format == 'full_path_link' )
		{ // User link with full path to file
			$FileCache = & get_FileCache();
			$new_File = & $FileCache->get_by_root_and_path( $fileroot_type, $fileroot_type_ID, trailing_slash( $path ).$newfile, true );
			$old_File = & $FileCache->get_by_root_and_path( $fileroot_type, $fileroot_type_ID, trailing_slash( $path ).$oldfile, true );
			$data['new'] = $new_File->get_view_link();
			$data['old'] = $old_File->get_view_link();
		}
		else
		{ // Simple text format
			$data['new'] = $newfile;
			$data['old'] = $oldfile;
		}
		if( $result !== true )
		{ // Send an error if it was created during the replacing
			$data['error'] = $result;
		}

		echo evo_json_encode( $data );
		exit(0);

	case 'link_attachment':
		// The content for popup window to link the files to the items/comments

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'link' );

		// Check permission:
		check_user_perm( 'files', 'view' );

		param( 'iframe_name', 'string', '' );
		param( 'link_owner_type', 'string', true );
		param( 'link_owner_ID', 'integer', true );
		// Additional params, Used to highlight file/folder
		param( 'root', 'string', '' );
		param( 'path', 'filepath', '' );
		param( 'fm_highlight', 'string', '' );
		param( 'prefix', 'string' );

		$additional_params = empty( $root ) ? '' : '&amp;root='.$root;
		$additional_params .= empty( $path ) ? '' : '&amp;path='.$path;
		$additional_params .= empty( $fm_highlight ) ? '' : '&amp;fm_highlight='.$fm_highlight;

		echo '<span id="link_attachment_loader" class="loader_img absolute_center" title="'.T_('Loading...').'"></span>'
				.'<iframe src="'.$admin_url.'?ctrl=files&amp;mode=upload&amp;ajax_request=1&amp;iframe_name='.$iframe_name.'&amp;fm_mode=link_object&amp;link_type='.$link_owner_type.'&amp;link_object_ID='.$link_owner_ID.$additional_params.'&amp;prefix='.$prefix.'"'
					.' width="100%" height="100%" marginwidth="0" marginheight="0" align="top" scrolling="auto" frameborder="0"'
					.' onload="document.getElementById(\'link_attachment_loader\').style.display=\'none\'">loading</iframe>';

		break;

	case 'file_attachment':
		// The content for popup window to link the files to the items/comments

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'file_attachment' );

		// Check permission:
		check_user_perm( 'files', 'view' );

		param( 'iframe_name', 'string', '' );
		param( 'field_name', 'string', '' );
		param( 'file_type', 'string', 'image' );
		// Additional params, Used to highlight file/folder
		param( 'root', 'string', '' );
		param( 'path', 'string', '' );
		param( 'fm_highlight', 'string', '' );

		$additional_params = empty( $root ) ? '' : '&amp;root='.$root;
		$additional_params .= empty( $path ) ? '' : '&amp;path='.$path;
		$additional_params .= empty( $fm_highlight ) ? '' : '&amp;fm_highlight='.$fm_highlight;
		//$additional_params .= empty( $field_name ) ? '' : '&amp;field_name='.$field_name;

		echo '<span id="link_attachment_loader" class="loader_img absolute_center" title="'.T_('Loading...').'"></span>'
				.'<iframe src="'.$admin_url.'?ctrl=files&amp;mode=upload&amp;field_name='.$field_name.'&amp;file_type='.$file_type.'&amp;ajax_request=1&amp;iframe_name='.$iframe_name.'&amp;fm_mode=file_select'.$additional_params.'"'
					.' width="100%" height="100%" marginwidth="0" marginheight="0" align="top" scrolling="auto" frameborder="0"'
					.' onload="document.getElementById(\'link_attachment_loader\').style.display=\'none\'">loading</iframe>';

		break;

	case 'import_files':
		// The content for popup window to import the files for XML importer

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'import' );

		$FileRootCache = & get_FileRootCache();
		$FileRoot = & $FileRootCache->get_by_type_and_ID( 'import', '0', true );

		// Check permission:
		check_user_perm( 'files', 'view', true, $FileRoot );

		echo '<span id="import_files_loader" class="loader_img absolute_center" title="'.T_('Loading...').'"></span>'
				.'<iframe src="'.$admin_url.'?ctrl=files&amp;mode=import&amp;ajax_request=1&amp;root=import_0&amp;path='.param( 'path', 'string' ).'"'
					.' width="100%" height="100%" marginwidth="0" marginheight="0" align="top" scrolling="auto" frameborder="0"'
					.' onload="document.getElementById(\'import_files_loader\').style.display=\'none\'">loading</iframe>';

		break;

	case 'test_api':
		// Spec action to test API from ctrl=system:
		echo 'ok';
		break;

	case 'get_userlist_automation':
		// Get automation data for current users list selection:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'users' );

		// Check permission:
		check_user_perm( 'options', 'view', true );

		param( 'autm_ID', 'integer', true );
		param( 'enlt_ID', 'integer', NULL );

		$AutomationCache = & get_AutomationCache();
		$Automation = & $AutomationCache->get_by_ID( $autm_ID );

		$NewsletterCache = & get_NewsletterCache();

		$autm_data = array();

		if( $enlt_ID === NULL )
		{	// Get newsletters tied to the automation:
			$NewsletterCache->load_list( $Automation->get_newsletter_IDs() );
			$autm_data['newsletters'] = array();
			foreach( $NewsletterCache->cache as $automation_Newsletter )
			{
				$autm_data['newsletters'][ $automation_Newsletter->ID ] = $automation_Newsletter->get( 'name' );
			}
		}
		else
		{	// Get automation data for selected newsletter:
			$automation_Newsletter = & $NewsletterCache->get_by_ID( $enlt_ID );

			$autm_data['newsletter_name'] = $automation_Newsletter->get( 'name' );

			load_funcs( 'email_campaigns/model/_emailcampaign.funcs.php' );
			$filterset_user_IDs = get_filterset_user_IDs();

			$no_subs_SQL = new SQL( 'Get a count of not subscribed users' );
			$no_subs_SQL->SELECT( 'COUNT( user_ID )' );
			$no_subs_SQL->FROM( 'T_users' );
			$no_subs_SQL->FROM_add( 'LEFT JOIN T_email__newsletter_subscription ON enls_user_ID = user_ID AND enls_enlt_ID = '.$automation_Newsletter->ID );
			$no_subs_SQL->WHERE( 'user_ID IN ( '.$DB->quote( $filterset_user_IDs ).' )' );
			$no_subs_SQL->WHERE_and( 'enls_subscribed = 0 OR enls_user_ID IS NULL' );
			$autm_data['users_no_subs_num'] = intval( $DB->get_var( $no_subs_SQL ) );

			$automated_SQL = new SQL( 'Get a count of automated users' );
			$automated_SQL->SELECT( 'COUNT( user_ID )' );
			$automated_SQL->FROM( 'T_users' );
			$automated_SQL->FROM_add( 'INNER JOIN T_automation__user_state ON aust_user_ID = user_ID' );
			$automated_SQL->WHERE( 'aust_autm_ID = '.$Automation->ID );
			$automated_SQL->WHERE_and( 'user_ID IN ( '.$DB->quote( $filterset_user_IDs ).' )' );
			$autm_data['users_automated_num'] = intval( $DB->get_var( $automated_SQL ) );

			$autm_data['users_new_num'] = count( $filterset_user_IDs ) - $autm_data['users_automated_num'];
		}

		echo evo_json_encode( $autm_data );

		exit(0); // Exit here in order to don't display the AJAX debug info after JSON formatted data

	case 'get_campaign_recipients':
		// Get recipients of Email Campaign depending on requested skip tags:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'campaign' );

		// Check permission:
		check_user_perm( 'options', 'view', true );

		param( 'ecmp_ID', 'integer', true );
		param( 'skip_tags', 'string', '' );

		$EmailCampaignCache = & get_EmailCampaignCache();
		if( $edited_Campaign = & $EmailCampaignCache->get_by_ID( $ecmp_ID, false, false ) )
		{	// If Email Campaign is found in DB:

			// Set temporarily the requested skip tags in order to calculate a count of recipients depending on them:
			$edited_Campaign->set( 'user_tag_sendskip', $skip_tags );

			load_funcs( 'email_campaigns/model/_emailcampaign.funcs.php' );
			$recipients_data = array(
				'status'      => 'ok',
				'skipped_tag' => $edited_Campaign->get_recipients_count( 'skipped_tag' ),
				'wait'        => $edited_Campaign->get_recipients_count( 'wait' ),
			);
		}
		else
		{	// Wrong request, unknown Email Campaign:
			$recipients_data = array(
				'status' => 'error',
				'error'  => 'email campaign not found'
			);
		}

		echo evo_json_encode( $recipients_data );

		exit(0); // Exit here in order to don't display the AJAX debug info after JSON formatted data

	case 'get_automation_status':
		// Get automation status:

		// Check permission:
		check_user_perm( 'options', 'view', true );

		param( 'autm_ID', 'integer', true );

		$AutomationCache = & get_AutomationCache();
		$Automation = & $AutomationCache->get_by_ID( $autm_ID );

		echo $Automation->get( 'status' );

		exit(0); // Exit here in order to don't display the AJAX debug info.

	case 'get_item_add_version_form':
		// Form to add version for the Item:

		$item_ID = param( 'item_ID', 'integer', true );

		$ItemCache = & get_ItemCache();
		$edited_Item = & $ItemCache->get_by_ID( $item_ID );

		// Initialize back-office skin:
		global $UserSettings, $adminskins_path, $AdminUI;
		$admin_skin = $UserSettings->get( 'admin_skin', $current_User->ID );
		require_once $adminskins_path.$admin_skin.'/_adminUI.class.php';
		$AdminUI = new AdminUI();

		require $inc_path.'items/views/_item_add_version.form.php';
		break;

	case 'get_link_locale_selector':
		// Get a selector to link a collection with other collectios which have same main or extra locale as requested
		param( 'coll_ID', 'integer', true );
		param( 'coll_locale', 'string' );
		param( 'field_name', 'string' );

		$BlogCache = & get_BlogCache();
		$Blog = & $BlogCache->get_by_ID( $coll_ID );

		echo $Blog->get_link_locale_selector( $field_name, $coll_locale, false );
		break;

	case 'get_item_mass_change_cat_form':
		// Form to mass change category of Items:

		param( 'blog', 'integer', true );
		param( 'selected_items', 'array:integer' );
		param( 'cat_type', 'string' );
		param( 'redirect_to', 'url', true );

		// Initialize objects for proper displaying of categories selector table:
		$BlogCache = & get_BlogCache();
		$Blog = & $BlogCache->get_by_ID( $blog );
		load_class( 'items/model/_item.class.php', 'Item' );
		$edited_Item = new Item();
		$post_extracats = array();

		// Initialize back-office skin:
		global $UserSettings, $adminskins_path, $AdminUI;
		$admin_skin = $UserSettings->get( 'admin_skin', $current_User->ID );
		require_once $adminskins_path.$admin_skin.'/_adminUI.class.php';
		$AdminUI = new AdminUI();

		require $inc_path.'items/views/_item_mass_change_cat.form.php';
		break;

	case 'get_item_mass_change_renderer_form':
		// Form to mass change renderer of Items:

		param( 'blog', 'integer', true );
		param( 'selected_items', 'array:integer' );
		param( 'renderer_change_type', 'string' );
		param( 'redirect_to', 'url', true );

		// Initialize objects for proper displaying of list of renderers:
		$BlogCache = & get_BlogCache();
		$Blog = & $BlogCache->get_by_ID( $blog );
		load_class( 'items/model/_item.class.php', 'Item' );
		$edited_Item = new Item();

		// Initialize back-office skin:
		global $UserSettings, $adminskins_path, $AdminUI;
		$admin_skin = $UserSettings->get( 'admin_skin', $current_User->ID );
		require_once $adminskins_path.$admin_skin.'/_adminUI.class.php';
		$AdminUI = new AdminUI();

		require $inc_path.'items/views/_item_mass_change_renderer.form.php';
		break;

	case 'clear_itemprecache':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'tools' );

		load_funcs( 'tools/model/_maintenance.funcs.php' );
		dbm_delete_itemprecache();
		break;

	case 'browse_subdirs':
		// Load sub-directories for Files Browser:

		// Use the glyph or font-awesome icons if requested by skin
		param( 'b2evo_icons_type', 'string', '' );

		// Path to parent directory with root data:
		param( 'path', 'filepath' );

		if( ! preg_match( '#^([a-z]+)_(\d+):(.+)$#', $path, $path_data ) )
		{	// Invalid path:
			debug_die( 'Invalid path!' );
		}

		// Try to get File Root by requested path:
		$FileRootCache = & get_FileRootCache();
		$dir_FileRoot = & $FileRootCache->get_by_type_and_ID( $path_data[1], $path_data[2] );

		// Check permission:
		check_user_perm( 'files', 'view', true, $dir_FileRoot );

		$FileCache = & get_FileCache();
		if( ! ( $dir_File = & $FileCache->get_by_root_and_path( $path_data[1], $path_data[2], $path_data[3] ) ) ||
		    ! $dir_File->is_dir() )
		{	// Invalid directory:
			debug_die( 'Invalid directory!' );
		}

		// Create list to load sub-folders:
		load_class( 'files/model/_filelist.class.php', 'Filelist' );
		$dir_Filelist = new Filelist( $dir_FileRoot, trailing_slash( $dir_File->get_full_path() ) );
		check_showparams( $dir_Filelist );
		$dir_Filelist->load();
		$dir_Filelist->sort( 'name' );

		if( ! $dir_Filelist->count_dirs() )
		{	// Wrong requested directory:
			debug_die( 'No sub-directories!' );
		}

		// Return sub-directories of the requested directory:
		while( $subdir_File = & $dir_Filelist->get_next( 'dir' ) )
		{
			echo '<li>'.get_directory_tree( $dir_FileRoot, $subdir_File->get_full_path(), $dir_File->get_full_path(), false, $subdir_File->get_rdfs_rel_path(), true ).'</li>';
		}
		break;

	case 'browse_existing_attachments':

		global $DB;
		$mode = 'upload';

		$FileRootCache = & get_FileRootCache();

		// Get all Item comments:
		$link_type = param( 'link_type', 'string', true );
		$link_object_ID = param( 'link_object_ID', 'integer', true );

		$LinkOwner = get_LinkOwner( $link_type, $link_object_ID );
		$LinkCache = & get_LinkCache();

		$links = array();
		$link_owner_class = get_class( $LinkOwner->link_Object );

		load_class( '_core/model/dataobjects/_dataobjectlist2.class.php', 'DataObjectList2' );
		$LinkCache = & get_LinkCache();
		$ea_Linklist = new DataObjectList2( $LinkCache );

		switch( $link_owner_class )
		{
			case 'Item':
			case 'Comment':
				if( $link_owner_class == 'Comment' )
				{
					$edited_Item = $LinkOwner->get_Item();
				}
				else
				{
					$edited_Item = $LinkOwner->Item;
				}
				$item_ID = $edited_Item->ID;

				// Get list of comment IDs under Item or related to Comment:
				$comments_SQL = new SQL( 'Get all the comments of an Item' );
				$comments_SQL->SELECT( 'comment_ID' );
				$comments_SQL->FROM( 'T_comments' );
				$comments_SQL->WHERE( 'comment_item_ID = '.$DB->quote( $item_ID ) );
				if( ! $edited_Item->can_meta_comment() )
				{	// If current User doesn't have an access to meta comments:
					$comments_SQL->WHERE( 'comment_type != "meta"' );
				}
				$comment_IDs = $DB->get_col( $comments_SQL );

				$links_SQL = new SQL( 'Get all the links belonging to comments of an Item' );
				$links_SQL->SELECT( '*' );
				$links_SQL->FROM( 'T_links AS l' );
				if( $comment_IDs )
				{
					$links_SQL->WHERE( 'link_cmt_ID IN ('.$DB->quote( $comment_IDs ).')' );
				}
				$links_SQL->WHERE_or( 'link_itm_ID = '.$DB->quote( $item_ID ) );
				$links_SQL->ORDER_BY( 'link_datemodified DESC, link_datecreated DESC' );

				$ea_Linklist->sql = $links_SQL->get();
				$ea_Linklist->run_query( false, false, false, 'get_attachment_LinkList' );

				// Get FileRoot and dummy FileList:
				if( $ea_Linklist->get_total_rows() )
				{	// Use first attachment to get the FileRoot:
					$Link = & $ea_Linklist->get_by_idx( 0 );
					$File = & $Link->get_File();
					$fm_FileRoot = & $File->get_FileRoot();
				}
				else
				{
					global $Blog;

					if( empty( $Blog ) )
					{
						$Blog = $edited_Item->get_Blog();
					}

					$fm_FileRoot = & $FileRootCache->get_by_type_and_ID( 'collection', $Blog->ID );
				}
				load_class( 'files/model/_filelist.class.php', 'FileList' );
				$fm_Filelist = new Filelist( $fm_FileRoot, false ); // Arbitrary list of attached files
				$selected_Filelist = new Filelist( $fm_FileRoot, false ); // Arbitrary list of attached files
				break;

			case 'EmailCampaign':
				if( $edited_Newsletter = & $LinkOwner->link_Object->get_Newsletter() )
				{
					// Get list of email campaign IDs under the same Newsletter:
					$email_campaigns_SQL = new SQL( 'Get all the email campaigns of a List' );
					$email_campaigns_SQL->SELECT( 'ecmp_ID' );
					$email_campaigns_SQL->FROM( 'T_email__campaign' );
					$email_campaigns_SQL->WHERE( 'ecmp_enlt_ID = '.$DB->quote( $edited_Newsletter->ID ) );
					$email_campaign_IDs = $DB->get_col( $email_campaigns_SQL );

					if( $email_campaign_IDs )
					{
						$links_SQL = new SQL( 'Get all the links belonging to email campaigns of a List' );
						$links_SQL->SELECT( '*' );
						$links_SQL->FROM( 'T_links AS l' );
						$links_SQL->WHERE( 'link_ecmp_ID IN ('.$DB->quote( $email_campaign_IDs ).')' );
						$links_SQL->ORDER_BY( 'link_datemodified DESC, link_datecreated DESC' );

						$ea_Linklist->sql = $links_SQL->get();
						$ea_Linklist->run_query( false, false, false, 'get_attachment_LinkList' );
					}
				}

				// Get FileRoot and dummy FileList:
				if( $ea_Linklist->get_total_rows() )
				{	// Use first attachment to get the FileRoot:
					$Link = & $ea_Linklist->get_by_idx( 0 );
					$File = & $Link->get_File();
					$fm_FileRoot = & $File->get_FileRoot();
				}
				else
				{
					$fm_FileRoot = & $FileRootCache->get_by_type_and_ID( 'emailcampaign', $LinkOwner->link_Object->ID );
				}
				load_class( 'files/model/_filelist.class.php', 'FileList' );
				$fm_Filelist = new Filelist( $fm_FileRoot, false ); // Arbitrary list of attached files
				$selected_Filelist = new Filelist( $fm_FileRoot, false ); // Arbitrary list of attached files
				break;

			default:
				debug_die( 'Existing attachments list not available to '.$link_owner_class );
		}
		
		global $current_User, $UserSettings, $is_admin_page, $adminskins_path;
		$admin_skin = $UserSettings->get( 'admin_skin', $current_User->ID );
		$is_admin_page = true;
		require_once $adminskins_path.$admin_skin.'/_adminUI.class.php';

		$AdminUI = new AdminUI();
		$Widget = new Widget( 'file_browser' );
		$Widget->disp_template_replaced( 'block_start' );
		
		require $inc_path.'links/views/_link_file_list.inc.php';

		$Widget->disp_template_raw( 'block_end' );
		break;

	default:
		$incorrect_action = true;
		break;
}

if( !$incorrect_action )
{
	if( $current_debug || $current_debug_jslog )
	{	// debug is ON
		ajax_log_display();
	}

	if( $add_response_end_comment )
	{ // add ajax response end comment
		echo '<!-- Ajax response end -->';
	}

	exit(0);
}

?>
