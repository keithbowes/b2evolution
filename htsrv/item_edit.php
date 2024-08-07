<?php
/**
 * Edit item with in-skin mode.
 *
 */

require_once dirname(__FILE__).'/../conf/_config.php';

require_once $inc_path.'_main.inc.php';

// Stop a request from the blocked IP addresses or Domains
antispam_block_request();

if( empty( $Blog ) )
{
	param( 'blog', 'integer', 0 );

	if( isset( $blog) && $blog > 0 )
	{
		$BlogCache = & get_BlogCache();
		$Collection = $Blog = $BlogCache->get_by_ID( $blog, false, false );
	}
}

if( !empty( $Blog ) )
{
	// Activate Blog locale because the new item was created in-skin
	locale_activate( $Blog->get('locale') );

	// Re-Init charset handling, in case current_charset has changed:
	init_charsets( $current_charset );
}

$post_ID = param ( 'post_ID', 'integer', 0 );

/**
 * Basic security checks:
 */
if( ! is_logged_in() )
{ // must be logged in!
	bad_request_die( T_('You are not logged in.') );
}

$action = param_action();

if( $action != 'save_propose' )
{	// check if user can edit this post:
	check_item_perm_edit( $post_ID );
}

if( !empty( $action ) && $action != 'new' )
{ // Check that this action request is not a CSRF hacked request:
	$Session->assert_received_crumb( 'item' );
}

//$post_status = NULL;
if( ( $action == 'create_publish' ) || ( $action == 'update_publish' ) )
{
	$post_status = load_publish_status( $action == 'create_publish' );
	$action = substr( $action, 0, 6 );
}
else
{
	$post_status = param( 'post_status', 'string', 'published' );
}

switch( $action )
{
	case 'update':
	case 'update_workflow': // Update workflow properties from disp=single
	case 'edit_switchtab': // this gets set as action by JS, when we switch tabs
	case 'save_propose':
		// Load post to edit:
		$post_ID = param ( 'post_ID', 'integer', true, true );
		$ItemCache = & get_ItemCache ();
		$edited_Item = & $ItemCache->get_by_ID ( $post_ID );

		// Load the blog we're in:
		$Collection = $Blog = & $edited_Item->get_Blog();
		set_working_blog( $Blog->ID );

		// Where are we going to redirect to?
		param( 'redirect_to', 'url', url_add_param( $admin_url, 'ctrl=items&filter=restore&amp;blog='.$Blog->ID.'&highlight='.$edited_Item->ID, '&' ) );

		// What form button has been pressed?
		param( 'save', 'string', '' );
		$exit_after_save = ( $action != 'update_edit' );
		break;
}

switch( $action )
{
	case 'new_switchtab': // this gets set as action by JS, when we switch tabs
		// New post form  (can be a bookmarklet form if mode == bookmarklet )
		load_class( 'items/model/_item.class.php', 'Item' );
		$edited_Item = new Item();

		$edited_Item->set('main_cat_ID', $Blog->get_default_cat_ID());

		// We use the request variables to fill the edit form, because we need to be able to pass those values
		// from tab to tab via javascript when the editor wants to switch views...
		// Also used by bookmarklet
		$edited_Item->load_from_Request( true ); // needs Blog set

		$edited_Item->status = $post_status;		// 'published' or 'draft' or ...
		// We know we can use at least one status,
		// but we need to make sure the requested/default one is ok:
		$edited_Item->status = $Blog->get_allowed_item_status( $edited_Item->status, $edited_Item );

		// Check if new category was started to create. If yes then set up parameters for next page:
		check_categories_nosave( $post_category, $post_extracats, $edited_Item, 'backoffice' );

		$edited_Item->set( 'main_cat_ID', $post_category );
		if( $edited_Item->main_cat_ID && ( get_allow_cross_posting() < 2 ) && $edited_Item->get_blog_ID() != $blog )
		{ // the main cat is not in the list of categories; this happens, if the user switches blogs during editing:
			$edited_Item->set('main_cat_ID', $Blog->get_default_cat_ID());
		}
		$post_extracats = param( 'post_extracats', 'array:integer', $post_extracats );
		$edited_Item->set( 'extra_cat_IDs', $post_extracats );

		param( 'item_tags', 'string', '' );

		// Trackback addresses (never saved into item)
		param( 'trackback_url', 'string', '' );

		// Params we need for tab switching:
		$tab_switch_params = 'blog='.$blog;

		// Where are we going to redirect to?
		param( 'redirect_to', 'url', url_add_param( $admin_url, 'ctrl=items&filter=restore&amp;blog='.$Blog->ID, '&' ) );
		break;

	case 'edit_switchtab': // this gets set as action by JS, when we switch tabs
		// Check permission based on DB status:
		check_user_perm( 'item_post!CURSTATUS', 'edit', true, $edited_Item );

		$edited_Item->status = $post_status;		// 'published' or 'draft' or ...
		// We know we can use at least one status,
		// but we need to make sure the requested/default one is ok:
		$edited_Item->status = $Blog->get_allowed_item_status( $edited_Item->status, $edited_Item );

		// We use the request variables to fill the edit form, because we need to be able to pass those values
		// from tab to tab via javascript when the editor wants to switch views...
		$edited_Item->load_from_Request ( true ); // needs Blog set

		// Check if new category was started to create. If yes then set up parameters for next page:
		check_categories_nosave( $post_category, $post_extracats, $edited_Item, 'backoffice' );

		$edited_Item->set ( 'main_cat_ID', $post_category );
		if( $edited_Item->main_cat_ID && ( get_allow_cross_posting() < 2 ) && $edited_Item->get_blog_ID() != $blog )
		{ // the main cat is not in the list of categories; this happens, if the user switches blogs during editing:
			$edited_Item->set('main_cat_ID', $Blog->get_default_cat_ID());
		}
		$post_extracats = param( 'post_extracats', 'array:integer', $post_extracats );
		$edited_Item->set( 'extra_cat_IDs', $post_extracats );

		param( 'item_tags', 'string', '' );

		// Trackback addresses (never saved into item)
		param( 'trackback_url', 'string', '' );

		// Params we need for tab switching:
		$tab_switch_params = 'p='.$edited_Item->ID;
		break;

	case 'create': // Create a new post
		$exit_after_save = ( $action != 'create_edit' );

		// Check if new category was started to create. If yes check if it is valid:
		check_categories( $post_category, $post_extracats, NULL, 'frontoffice' );

		// Check permission on statuses:
		check_user_perm( 'cats_post!'.$post_status, 'create', true, $post_extracats );

		// Get requested Post Type:
		$item_typ_ID = param( 'item_typ_ID', 'integer', true /* require input */ );
		// Check permission on post type: (also verifies that post type is enabled and NOT reserved)
		check_perm_posttype( $item_typ_ID, $post_extracats );

		// CREATE NEW POST:
		load_class( 'items/model/_item.class.php', 'Item' );
		$edited_Item = new Item();

		// Set the params we already got:
		$edited_Item->set( 'status', $post_status );
		$edited_Item->set( 'main_cat_ID', $post_category );
		$edited_Item->set( 'extra_cat_IDs', $post_extracats );

		// Set object params:
		$edited_Item->load_from_Request( /* editing? */ ($action == 'create_edit'), /* creating? */ true );

		$Plugins->trigger_event ( 'AdminBeforeItemEditCreate', array ('Item' => & $edited_Item ) );

		// Validate first enabled captcha plugin:
		$Plugins->trigger_event_first_return( 'ValidateCaptcha', array( 'form_type' => 'item' ) );

		if( !empty( $mass_create ) )
		{	// ------ MASS CREATE ------
			$Items = & create_multiple_posts( $edited_Item, param( 'paragraphs_linebreak', 'boolean', 0 ) );
			if( empty( $Items ) )
			{
				param_error( 'content', T_( 'Content must not be empty.' ) );
			}
		}

		if( $Messages->has_errors() )
		{
			if( !empty( $mass_create ) )
			{
				$action = 'new_mass';
			}
			// There have been some validation errors:
			// Params we need for tab switching:
			$tab_switch_params = 'blog='.$blog;
			break;
		}

		if( isset( $Items ) && !empty( $Items ) )
		{	// We can create multiple posts from single post
			foreach( $Items as $edited_Item )
			{	// INSERT NEW POST INTO DB:
				$edited_Item->dbinsert();
			}
		}
		else
		{	// INSERT NEW POST INTO DB:
			$edited_Item->dbinsert();
		}

		// post post-publishing operations:
		param( 'trackback_url', 'string' );
		if( !empty( $trackback_url ) )
		{
			if( $edited_Item->status != 'published' )
			{
				$Messages->add( T_('Post not publicly published: skipping trackback...'), 'note' );
			}
			else
			{ // trackback now:
				load_funcs('comments/_trackback.funcs.php');
				trackbacks( $trackback_url, $edited_Item );
			}
		}

		// Execute or schedule notifications & pings:
		$edited_Item->handle_notifications( NULL, true );

		$Messages->add( T_('Post has been created.'), 'success' );

		if( ! $exit_after_save )
		{	// We want to continue editing...
			$tab_switch_params = 'p='.$edited_Item->ID;
			$action = 'edit';	// It's basically as if we had updated
			break;
		}

		// REDIRECT / EXIT
		header_redirect( $edited_Item->get_tinyurl() );
		break;

	case 'update': // Update an existing post
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'item' );

		// Check edit permission:
		check_user_perm( 'item_post!CURSTATUS', 'edit', true, $edited_Item );

		// Check if new category was started to create.  If yes check if it is valid:
		$isset_category = check_categories( $post_category, $post_extracats, $edited_Item, 'frontoffice' );

		// Get requested Post Type:
		$item_typ_ID = param( 'item_typ_ID', 'integer', true /* require input */ );
		// Check permission on post type: (also verifies that post type is enabled and NOT reserved)
		check_perm_posttype( $item_typ_ID, $post_extracats );

		// UPDATE POST:
		// Set the params we already got:
		$edited_Item->set ( 'status', $post_status );

		if( $isset_category )
		{ // we change the categories only if the check was succesfull
			$edited_Item->set ( 'main_cat_ID', $post_category );
			$edited_Item->set ( 'extra_cat_IDs', $post_extracats );
		}

		// Set object params:
		$edited_Item->load_from_Request( false );

		$Plugins->trigger_event( 'AdminBeforeItemEditUpdate', array( 'Item' => & $edited_Item ) );

		// Params we need for tab switching (in case of error or if we save&edit)
		$tab_switch_params = 'p='.$edited_Item->ID;

		if( $Messages->has_errors() )
		{ // There have been some validation errors:
			break;
		}

		// UPDATE POST IN DB:
		if( $edited_Item->dbupdate() )
		{
			if( $edited_Item->assigned_to_new_user && ! empty( $edited_Item->assigned_user_ID ) )
			{ // Send post assignment notification
				$edited_Item->send_assignment_notification();
			}

			// Clear all proposed changes of the updated Item:
			$edited_Item->clear_proposed_changes();

			// Update attachments folder:
			$edited_Item->update_attachments_folder();
		}

		// post post-publishing operations:
		param( 'trackback_url', 'string' );
		if( !empty( $trackback_url ) )
		{
			if( $edited_Item->status != 'published' )
			{
				$Messages->add( T_('Post not publicly published: skipping trackback...'), 'note' );
			}
			else
			{ // trackback now:
				load_funcs('comments/_trackback.funcs.php');
				trackbacks( $trackback_url, $edited_Item );
			}
		}

		// Execute or schedule notifications & pings:
		$edited_Item->handle_notifications();

		$Messages->add( T_('Post has been updated.'), 'success' );

		$inskin_statuses = get_inskin_statuses( $edited_Item->get_blog_ID(), 'post' );
		if( ! in_array( $post_status, $inskin_statuses ) )
		{ // If post is not published we show it in the Back-office
			$edited_Item->load_Blog();
			if( $post_status == 'redirected' )
			{ // If a post is in "Redirected" status - redirect to homepage of the blog
				$redirect_to = $edited_Item->Blog->gen_baseurl();
			}
			else
			{ // Redirect to view post in the Back-office
				$redirect_to = url_add_param( $admin_url, 'ctrl=items&amp;blog='.$edited_Item->Blog->ID.'&p='.$edited_Item->ID, '&' );
			}
		}
		else
		{ // User can see this post in the Front-office
			if( $edited_Item->get_type_setting( 'usage' ) == 'intro-cat' )
			{ // If post is category intro we should redirect to page of that category
				$main_Chapter = & $edited_Item->get_main_Chapter();
				$redirect_to = $main_Chapter->get_permanent_url();
			}
			else
			{ // Redirect to post permanent url for all other posts
				$redirect_to = $edited_Item->get_permanent_url();
			}
		}

		// REDIRECT / EXIT
		header_redirect( $redirect_to );
		/* EXITED */
		break;

	case 'update_workflow':
		// Update workflow properties from disp=single:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'item' );

		$item_Blog = & $edited_Item->get_Blog();

		// Check if current User has a permission to edit at least one workflow property:
		$edited_Item->can_edit_workflow( 'any', true );

		if( $edited_Item->load_workflow_from_Request() )
		{	// Update workflow properties if they are loaded from request without errors and at least one of them has been changed:
			if( $edited_Item->dbupdate() )
			{	// Display a message on success result:
				$Messages->add( T_('The workflow properties have been updated.'), 'success' );

				if( $edited_Item->assigned_to_new_user && ! empty( $edited_Item->assigned_user_ID ) )
				{ // Send post assignment notification
					$edited_Item->send_assignment_notification();
				}
			}
		}

		// REDIRECT / EXIT
		header_redirect( $redirect_to );
		/* EXITED */
		break;

	case 'save_propose':
		// Save new proposed change:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'item' );

		// Check if current User can create a new proposed change:
		$edited_Item->can_propose_change( true );

		if( $edited_Item->create_proposed_change() )
		{	// If new proposed changes has been inserted in DB successfully:
			$Messages->add( T_('New proposed change has been recorded.'), 'success' );
			if( check_user_perm( 'admin', 'restricted' ) &&
			    check_user_perm( 'item_post!CURSTATUS', 'edit', false, $edited_Item ) )
			{	// Redirect to item history page with new poroposed change if current User has a permisson:
				header_redirect( $admin_url.'?ctrl=items&action=history&p='.$edited_Item->ID );
			}
			else
			{	// Redirect to item view page:
				header_redirect( $edited_Item->get_permanent_url( '', '', '&' ) );
			}
		}

		// If some errors on creating new proposed change,
		// Display the same submitted form of new proposed change:
		$error_disp = 'proposechange';
		break;
}

// Display a 'In-skin editing' form
$SkinCache = & get_SkinCache();
$Skin = & $SkinCache->get_by_ID( $Blog->get_skin_ID() );
$skin = $Skin->folder;
$disp = isset( $error_disp ) ? $error_disp : 'edit';
$ads_current_skin_path = $skins_path.$skin.'/';
if( file_exists( $ads_current_skin_path.$disp.'.main.php' ) )
{	// Include template file from current skin folder
	require $ads_current_skin_path.$disp.'.main.php';
}
else
{	// Include default main template
	require $ads_current_skin_path.'index.main.php';
}

?>