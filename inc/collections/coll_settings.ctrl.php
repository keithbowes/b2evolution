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


// Store/retrieve preferred tab from UserSettings:
$UserSettings->param_Request( 'tab', 'pref_coll_settings_tab', 'string', 'dashboard', true /* memorize */, true /* force */ );

if( $tab == 'widgets' )
{	// This is another controller!
	require_once dirname(__FILE__).'/../widgets/widgets.ctrl.php';
	return;
}
else if( $tab == 'manage_skins' )
{	// This is another controller!
	require_once dirname(__FILE__).'/../skins/skins.ctrl.php';
	return;
}

if( empty( $tab ) || $tab == "dashboard" )
{
	param_action( 'dashboard' );
}
else
{
	param_action( 'edit' );

	// We should activate toolbar menu items for this controller
	$activate_collection_toolbar = true;

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
		$Messages->add( TB_('Sorry, you have no permission to edit blog properties.'), 'error' );
		// redirect to blog list:
		header_redirect( $admin_url.'?ctrl=collections' );
		// EXITED:
	}

	memorize_param( 'blog', 'integer', -1 );	// Needed when generating static page for example

	param( 'skin_type', 'string', 'normal' );
	param( 'skinpage', 'string', '' );

	if( ( $tab == 'perm' || $tab == 'permgroup' )
		&& ( empty($blog) || ! $Blog->advanced_perms ) )
	{	// We're trying to access advanced perms but they're disabled!
		$tab = 'general';	// the screen where you can enable advanced perms
		if( $action == 'update' )
		{ // make sure we don't update anything here
			$action = 'edit';
		}
	}
}

/**
 * Perform action:
 */
switch( $action )
{
	case 'update':
	case 'update_confirm':
		// Update DB:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'collection' );

		// Check permissions:

		// Set URL to redirect after succesful action:
		$update_redirect_url = '?ctrl=coll_settings&amp;tab='.$tab.'&amp;blog='.$blog.( empty( $mode ) ? '' : '&amp;mode='.$mode );

		switch( $tab )
		{
			case 'general':
			case 'urls':
			case 'comments':
				if( $edited_Blog->load_from_Request( array( $tab ) ) )
				{ // Commit update to the DB:
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
					if( $tab == 'comments' )
					{
						// Comment recycle bin
						param( 'auto_empty_trash', 'integer', $Settings->get_default('auto_empty_trash'), false, false, true, false );
						$Settings->set( 'auto_empty_trash', get_param('auto_empty_trash') );
					}
					$Settings->dbupdate();

					$edited_Blog->dbupdate();
					$Messages->add( TB_('The collection settings have been updated.'), 'success' );
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( $update_redirect_url, 303 ); // Will EXIT
				}
				break;

			case 'home':
			case 'features':
			case 'contact':
			case 'userdir':
			case 'search':
			case 'other':
			case 'popup':
			case 'metadata':
			case 'more':
				if( $edited_Blog->load_from_Request( array( $tab ) ) )
				{ // Commit update to the DB:
					$edited_Blog->dbupdate();
					$Messages->add( TB_('The collection settings have been updated.'), 'success' );
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( $update_redirect_url, 303 ); // Will EXIT
				}
				break;

			case 'seo':
				if( $edited_Blog->load_from_Request( array( 'seo' ) ) )
				{ // Commit update to the DB:
					$edited_Blog->dbupdate();
					$Messages->add( TB_('The collection settings have been updated.'), 'success' );
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( $update_redirect_url, 303 ); // Will EXIT
				}
				break;

			case 'skin':
				if( $skinpage == 'selection' )
				{	// Set new skin for the collection:
					if( $edited_Blog->load_from_Request( array() ) )
					{ // Commit update to the DB:
						$edited_Blog->dbupdate();

						if( param( 'reset_widgets', 'integer', 0 ) )
						{	// Widget must be reseted:
							$updated_skin_type = '';
							if( get_param( 'normal_skin_ID' ) !== NULL )
							{	// Normal skin has been changed:
								$updated_skin_type = 'normal';
							}
							elseif( get_param( 'tablet_skin_ID' ) !== NULL )
							{	// Tablet skin has been changed:
								$updated_skin_type = 'tablet';
							}
							elseif( get_param( 'mobile_skin_ID' ) !== NULL )
							{	// Mobile skin has been changed:
								$updated_skin_type = 'mobile';
							}
							if( ! empty( $updated_skin_type ) )
							{	// Reset previous widgets with new from skin default widget declarations:
								$edited_Blog->reset_widgets( $updated_skin_type );
							}
						}

						$Messages->add( TB_('The blog skin has been changed.')
											.' <a href="'.$admin_url.'?ctrl=coll_settings&amp;tab=skin&amp;blog='.$edited_Blog->ID.'">'.TB_('Edit...').'</a>', 'success' );
						if( ( ! $Session->is_mobile_session() && ! $Session->is_tablet_session() && ! $Session->is_alt_session() && param( 'normal_skin_ID', 'integer', NULL ) !== NULL ) ||
						    ( $Session->is_mobile_session() && param( 'mobile_skin_ID', 'integer', NULL ) !== NULL ) ||
						    ( $Session->is_tablet_session() && param( 'tablet_skin_ID', 'integer', NULL ) !== NULL ) ||
						    ( $Session->is_alt_session() && param( 'alt_skin_ID', 'integer', NULL ) !== NULL ) )
						{	// Redirect to blog home page if we change the skin for current device type
							header_redirect( $edited_Blog->gen_blogurl() );
						}
						else
						{	// Redirect to admin skins page if we change the skin for another device type:
							$skin_type = ( get_param( 'mobile_skin_ID' ) !== NULL ? 'mobile'
								: ( get_param( 'tablet_skin_ID' ) !== NULL ? 'tablet'
								: ( get_param( 'alt_skin_ID' ) !== NULL ? 'alt'
								: 'normal' ) ) );
							header_redirect( $admin_url.'?ctrl=coll_settings&tab=skin&blog='.$edited_Blog->ID.'&skin_type='.$skin_type );
						}
					}
				}
				else
				{	// Update skin params/settings of the collection:
					if( ! in_array( $skin_type, array( 'normal', 'mobile', 'tablet', 'alt' ) ) )
					{
						debug_die( 'Wrong skin type: '.$skin_type );
					}

					$SkinCache = & get_SkinCache();

					// Get skin by selected type:
					$skin_ID = $Blog->get( $skin_type.'_skin_ID', array( 'real_value' => ( $skin_type != 'normal' ) ) );
					$edited_Skin = & $SkinCache->get_by_ID( $skin_ID, false, false );

					if( ! $edited_Skin )
					{	// Redirect to don't try update empty skin params:
						header_redirect( $update_redirect_url.'&skin_type='.$skin_type, 303 ); // Will EXIT
					}

					// Update the folding states for current user:
					save_fieldset_folding_values( $edited_Blog->ID );

					// Load skin params from request:
					$edited_Skin->load_params_from_Request();

					if(	! param_errors_detected() )
					{	// Update settings:
						$edited_Skin->dbupdate_settings();
						$Messages->add( TB_('Skin settings have been updated'), 'success' );
						// Redirect so that a reload doesn't write to the DB twice:
						header_redirect( $update_redirect_url.'&skin_type='.$skin_type, 303 ); // Will EXIT
					}
				}
				break;

			case 'plugins':
				$plugin_group = param( 'plugin_group', 'string', NULL );
				if( isset( $plugin_group ) )
				{
					$update_redirect_url .= '&plugin_group='.$plugin_group;
				}

				// Update the folding states for current user:
				save_fieldset_folding_values( $edited_Blog->ID );

				// Update Plugin params/Settings
				load_funcs('plugins/_plugin.funcs.php');

				$Plugins->restart();
				while( $loop_Plugin = & $Plugins->get_next() )
				{
					if( $loop_Plugin->group != $plugin_group )
					{
						continue;
					}

					$tmp_params = array( 'for_editing' => true );
					$pluginsettings = $loop_Plugin->get_coll_setting_definitions( $tmp_params );
					if( empty($pluginsettings) )
					{
						continue;
					}

					// Loop through settings for this plugin:
					foreach( $pluginsettings as $set_name => $set_meta )
					{
						autoform_set_param_from_request( $set_name, $set_meta, $loop_Plugin, 'CollSettings', $Blog );
					}

					// Let plugins process settings
					$tmp_params = array();
					$Plugins->call_method( $loop_Plugin->ID, 'PluginCollSettingsUpdateAction', $tmp_params );
				}

				if(	! param_errors_detected() )
				{	// Update settings:
					$Blog->dbupdate();
					$Messages->add( TB_('Plugin settings have been updated').'.', 'success' );
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( $update_redirect_url, 303 ); // Will EXIT
				}
				break;

			case 'advanced':
				if( $edited_Blog->load_from_Request( array( 'pings', 'cache', 'authors', 'login', 'styles', 'template', 'credits', 'meta' ) ) )
				{ // Commit update to the DB:
					if( check_user_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
					{
						$cache_status = param( 'cache_enabled', 'integer', 0 );
						load_funcs( 'collections/model/_blog.funcs.php' );
						$result = set_cache_enabled( 'cache_enabled', $cache_status, $edited_Blog->ID, false );
						if( $result != NULL )
						{
							list( $status, $message ) = $result;
							$Messages->add( $message, $status );
						}
					}

					$edited_Blog->dbupdate();
					$Messages->add( TB_('The collection settings have been updated.'), 'success' );
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( $update_redirect_url, 303 ); // Will EXIT
				}
				break;

			case 'perm':
				blog_update_perms( $blog, 'user' );
				$Messages->add( TB_('The collection permissions have been updated.'), 'success' );
				break;

			case 'permgroup':
				blog_update_perms( $blog, 'group' );
				$Messages->add( TB_('The collection permissions have been updated.'), 'success' );
				break;

			case 'chapters':
				param( 'category_ordering', 'string' );
				$edited_Blog->set_setting( 'category_ordering', get_param( 'category_ordering' ) );
				$edited_Blog->dbupdate();
				$Messages->add( TB_('Category ordering has been changed.'), 'success' );
				header_redirect( param( 'redirect_to', 'url', '?ctrl=chapters&blog='.$edited_Blog->ID ), 303 ); // Will EXIT
				break;
		}

		break;

	case 'update_type':
		// Update DB:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'collection' );

		// Check permissions:
		check_user_perm( 'blog_properties', 'edit', true, $blog );
		$update_redirect_url = '?ctrl=coll_settings&amp;tab='.$tab.'&amp;blog='.$blog;

		param( 'reset', 'boolean', '' );
		param( 'type', 'string', '' );
		param_check_not_empty( 'type', TB_('Please select a type') );

		if( param_errors_detected() )
		{
			$action = 'type';
			break;
		}

		if( $reset )
		{	// Reset all settings:
			// Remove previous plugin and skin settings:
			$DB->query( 'DELETE FROM T_coll_settings
				WHERE cset_coll_ID = '.$DB->quote( $edited_Blog->ID ).'
				AND ( cset_name LIKE "skin%" OR cset_name LIKE "plugin%" )' );
			// Reset previous widgets with new from all skins default widget declarations:
			$edited_Blog->reset_widgets();
		}

		$edited_Blog->init_by_kind( $type, $edited_Blog->get( 'name' ), $edited_Blog->get( 'shortname' ), $edited_Blog->get( 'urlname' ) );
		$edited_Blog->dbupdate();

		$Messages->add( TB_('The collection type has been updated'), 'success' );
		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( $update_redirect_url, 303 ); // Will EXIT

		break;

	case 'enable_setting':
	case 'disable_setting':
		// Update blog settings:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'collection' );

		// Check permissions:
		check_user_perm( 'blog_properties', 'edit', true, $blog );

		$update_redirect_url = $admin_url.'?ctrl=collections';

		$setting = param( 'setting', 'string', '' );
		$setting_value = ( $action == 'enable_setting' ? '1' : '0' );

		switch( $setting )
		{
			case 'fav':
				// Favorite Blog
				$edited_Blog->favorite( $current_User->ID, $setting_value );
				$result_message = TB_('The collection settings have been updated.');
				break;

			case 'page_cache':
				// Page caching
				$edited_Blog->set_setting( 'cache_enabled', $setting_value );
				if( $setting_value )
				{ // If we are enabling the page caching we should also enable AJAX forms
					$edited_Blog->set_setting( 'ajax_form_enabled', 1 );
				}
				$result_message = $setting_value ?
						TB_('Page caching has been turned on for the collection.') :
						TB_('Page caching has been turned off for the collection.');
				break;

			case 'block_cache':
				// Widget/block caching
				$edited_Blog->set_setting( 'cache_enabled_widgets', $setting_value );
				$result_message = $setting_value ?
						TB_('Block caching has been turned on for the collection.') :
						TB_('Block caching has been turned off for the collection.');
				break;

			default:
				// Incorrect setting name
				header_redirect( $update_redirect_url, 303 ); // Will EXIT
				break;
		}

		// Update the changed settings
		$edited_Blog->dbupdate();

		$Messages->add( $result_message, 'success' );
		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( $update_redirect_url, 303 ); // Will EXIT

		break;

	case 'export_userperms':
		// Export user permissions into CSV file:

		load_funcs( 'collections/views/_coll_perm_view.funcs.php' );

		$keywords = param( 'keywords', 'string', '' );

		// Get SQL for collection user permissions:
		$SQL = get_coll_user_perms_SQL( $edited_Blog, $keywords, false );

		$user_perms = $DB->get_results( $SQL );

		header_nocache();
		header_content_type( 'text/csv' );
		header( 'Content-Disposition: attachment; filename=colls_userperms.csv' );

		echo get_csv_coll_perms( 'bloguser_', $user_perms, $edited_Blog );
		exit;

	case 'export_groupperms':
		// Export group permissions into CSV file:

		load_funcs( 'collections/views/_coll_perm_view.funcs.php' );

		$keywords = param( 'keywords', 'string', '' );

		// Get SQL for collection user permissions:
		$SQL = get_coll_group_perms_SQL( $edited_Blog, $keywords, false );

		$group_perms = $DB->get_results( $SQL );

		header_nocache();
		header_content_type( 'text/csv' );
		header( 'Content-Disposition: attachment; filename=colls_groupperms.csv' );

		echo get_csv_coll_perms( 'bloggroup_', $group_perms, $edited_Blog );
		exit;
}

if( $action == 'dashboard' )
{
	// load dashboard functions
	load_funcs( 'dashboard/model/_dashboard.funcs.php' );

	if( ! check_user_perm( 'blog_ismember', 'view', false, $blog ) )
	{ // We don't have permission for the requested blog (may happen if we come to admin from a link on a different blog)
		set_working_blog( 0 );
		unset( $Blog, $Collection );
	}

	$AdminUI->set_path( 'collections', 'dashboard' );

	// Init params to display a panel with blog selectors
	$AdminUI->set_coll_list_params( 'blog_ismember', 'view', array( 'ctrl' => 'coll_settings' ) );

	$AdminUI->breadcrumbpath_init( true, array( 'text' => TB_('Collections'), 'url' => $admin_url.'?ctrl=coll_settings&amp;blog=$blog$' ) );
	$AdminUI->breadcrumbpath_add( TB_('Collection Dashboard'), $admin_url.'?ctrl=coll_settings&amp;blog=$blog$' );

	// Set an url for manual page:
	$AdminUI->set_page_manual_link( 'collection-dashboard' );

	// We should activate toolbar menu items for this controller and action
	$activate_collection_toolbar = true;

	// Load jquery UI to animate background color on change comment status and to transfer a comment to recycle bin
	require_js_defer( '#jqueryUI#' );

	// Load the appropriate blog navigation styles (including calendar, comment forms...):
	require_css( $AdminUI->get_template( 'blog_base.css' ) ); // Default styles for the blog navigation
	// Colorbox (a lightweight Lightbox alternative) allows to zoom on images and do slideshows with groups of images:
	require_js_helper( 'colorbox' );

	// Include files to work with charts
	require_js_defer( '#easypiechart#' );
	require_css( 'ext:jquery/easy-pie-chart/css/jquery.easy-pie-chart.css' );

	// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
	$AdminUI->disp_html_head();

	// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
	$AdminUI->disp_body_top();

	// Flush to immediately display the above
	evo_flush();

	if ( $blog )
	{
		$Timer->start( 'Panel: Comments Awaiting Moderation' );
		load_class( 'items/model/_itemlist.class.php', 'ItemList' );
		$block_item_Widget = new Widget( 'dash_item' );
		$nb_blocks_displayed = 0;

		$blog_moderation_statuses = explode( ',', $Blog->get_setting( 'moderation_statuses' ) );
		$highest_publish_status = get_highest_publish_status( 'comment', $Blog->ID, false );
		$user_modeartion_statuses = array();

		foreach( $blog_moderation_statuses as $status )
		{
			if( ( $status !== $highest_publish_status ) && check_user_perm( 'blog_comment!'.$status, 'edit', false, $blog ) )
			{
				$user_modeartion_statuses[] = $status;
			}
		}
		$user_perm_moderate_cmt = count( $user_modeartion_statuses );

		if( $user_perm_moderate_cmt )
		{
			// Comments for Moderation
			$CommentList = new CommentList2( $Blog );

			// Filter list:
			$CommentList->set_filters( array(
					'types' => array( 'comment','trackback','pingback','webmention' ),
					'statuses' => $user_modeartion_statuses,
					'user_perm' => 'moderate',
					'post_statuses' => array( 'published', 'community', 'protected' ),
					'order' => 'DESC',
					'comments' => 10,
				) );

			// Set param prefix for URLs
			$param_prefix = 'cmnt_fullview_';
			if( !empty( $CommentList->param_prefix ) )
			{
				$param_prefix = $CommentList->param_prefix;
			}

			// Get ready for display (runs the query):
			$CommentList->display_init();

			// Load data of comments from the current page at once to cache variables:
			$CommentList->load_list_data();
		}

		// Check if we have comments and posts to moderate
		$have_comments_to_moderate = $user_perm_moderate_cmt && $CommentList->result_num_rows;

		$Timer->pause( 'Panel: Comments Awaiting Moderation' );

		// Posts for Moderation
		$Timer->start( 'Panel: Posts Awaiting Moderation' );
		$post_moderation_statuses = explode( ',', $Blog->get_setting( 'post_moderation_statuses' ) );
		ob_start();
		foreach( $post_moderation_statuses as $status )
		{ // go through all statuses
			if( display_posts_awaiting_moderation( $status, $block_item_Widget ) )
			{ // a block was displayed for this status
				$nb_blocks_displayed++;
			}
		}
		$posts_awaiting_moderation_content = ob_get_contents();
		ob_clean();

		$Timer->pause( 'Panel: Posts Awaiting Moderation' );

		// Check if we have posts that $blog_moderation
		$have_posts_to_moderate = ! empty( $posts_awaiting_moderation_content );


		// Begin payload block:
		// This div is to know where to display the message after overlay close:
		echo '<div class="first_payload_block">'."\n";

		$AdminUI->disp_payload_begin();
		$collection_image = $Blog->get( 'collection_image' );
		echo '<div class="row">';
			echo '<div class="col-xs-12 col-sm-3 col-sm-push-9 col-lg-2 col-lg-push-10 text-right">';
			echo action_icon( TS_('View in Front-Office'), '', $Blog->get( 'url' ), TB_('View in Front-Office'), 3, 4, array( 'class' => 'action_icon hoverlink btn btn-info' ) );
			echo '</div>';
			echo '<h2 class="col-xs-12 col-sm-9 col-sm-pull-3 col-lg-10 col-lg-pull-2 page-title">'
					.get_coll_fav_icon( $Blog->ID, array( 'class' => 'coll-fav' ) ).'&nbsp;'
					.( ! empty( $collection_image ) ? $collection_image->get_tag( '', '', '', '', 'crop-32x32' ).'&nbsp;' : '' )
					.$Blog->dget( 'name' )
					.' <span class="text-muted" style="font-size: 0.6em;">('./* TRANS: abbr. for "Collection" */ TB_('Collection').' #'.$Blog->ID.')</span>'
					.'</h2>';
		echo '</div>';
		load_funcs( 'collections/model/_blog_js.funcs.php' );
		echo '<div class="row browse">';

		// Block Group 1
		$perm_options_edit = check_user_perm( 'options', 'edit' );

		if( $perm_options_edit )
		{
			$Timer->start( 'Panel: Collection Metrics' );
			echo '<!-- Start of Block Group 1 -->';
			echo '<div class="col-xs-12 col-sm-12 col-md-3 col-md-push-0 col-lg-'.( ($have_comments_to_moderate || $have_posts_to_moderate) ? '6' : '3' ).' col-lg-push-0 floatright">';

			$side_item_Widget = new Widget( 'side_item' );
			$perm_blog_properties = check_user_perm( 'blog_properties', 'edit', false, $Blog->ID );

			// Collection Analytics Block
			if( $perm_options_edit )
			{ // We have some serious admin privilege:

				// -- Collection stats -- //{
				$chart_data = array();

				// Posts
				$posts_sql_from = 'INNER JOIN T_categories ON cat_ID = post_main_cat_ID';
				$posts_sql_where = 'cat_blog_ID = '.$DB->quote( $blog );
				$chart_data[] = array(
						'title' => TB_('Posts'),
						'value' => get_table_count( 'T_items__item', $posts_sql_where, $posts_sql_from, 'Get a count of Items metric for collection #'.$blog ),
						'type'  => 'number',
					);
				// Slugs
				$slugs_sql_from = 'INNER JOIN T_items__item ON post_ID = slug_itm_ID '.$posts_sql_from;
				$slugs_sql_where = 'slug_type = "item" AND '.$posts_sql_where;
				$chart_data[] = array(
						'title' => TB_('Slugs'),
						'value' => get_table_count( 'T_slug', $slugs_sql_where, $slugs_sql_from, 'Get a count of Slugs metric for collection #'.$blog ),
						'type'  => 'number',
					);
				// Comments
				$comments_sql_from = 'INNER JOIN T_items__item ON post_ID = comment_item_ID '.$posts_sql_from;
				$comments_sql_where = $posts_sql_where;
				$chart_data[] = array(
						'title' => TB_('Comments'),
						'value' => get_table_count( 'T_comments', $comments_sql_where, $comments_sql_from, 'Get a count of Comments metric for collection #'.$blog ),
						'type'  => 'number',
					);

				echo '<div>';
				$side_item_Widget->title = TB_('Collection metrics');
				$side_item_Widget->disp_template_replaced( 'block_start' );
				display_charts( $chart_data );
				$side_item_Widget->disp_template_raw( 'block_end' );
				echo '</div>';
			}
			$Timer->stop( 'Panel: Collection Metrics' );

			if( $Blog->get( 'notes' ) )
			{
				$edit_link = '';
				if( check_user_perm( 'blog_properties', 'edit', false, $blog ) )
				{
					$edit_link = action_icon( TB_('Edit').'...', 'edit_button', $admin_url.'?ctrl=coll_settings&amp;tab=general&amp;blog='.$Blog->ID, ' '.TB_('Edit').'...', 3, 4, array( 'class' => 'btn btn-default btn-sm' ) );
				}

				$block_item_Widget = new Widget( 'block_item' );
				$block_item_Widget->title = '<span class="pull-right panel_heading_action_icons">'.$edit_link.'</span>'.TB_('Notes');
				$block_item_Widget->disp_template_replaced( 'block_start' );
				$Blog->disp( 'notes', 'htmlbody' );
				$block_item_Widget->disp_template_replaced( 'block_end' );
			}

			echo '</div><!-- End of Block Group 1 -->';
			evo_flush();
		}

		// Block Group 2
		if( $have_comments_to_moderate || $have_posts_to_moderate )
		{
			echo '<!-- Start of Block Group 2 -->';
			echo '<div class="col-xs-12 col-sm-12 '.( $perm_options_edit ? 'col-md-9' : 'col-md-12' ).' col-md-pull-0 col-lg-6 col-lg-pull-0 floatleft">';

			// Comments Awaiting Moderation Block
			if( $have_comments_to_moderate )
			{
				load_funcs( 'comments/model/_comment_js.funcs.php' );

				$Timer->resume( 'Panel: Comments Awaiting Moderation' );
				echo '<!-- Start of Comments Awaiting Moderation Block -->';
				$opentrash_link = get_opentrash_link( true, false, array(
						'class'  => 'btn btn-default btn-sm',
						'before' => '<span id="recycle_bin">',
						'after'  => '</span> ',
					) );
				$refresh_link = action_icon( TB_('Refresh comment list'), 'refresh', $admin_url.'?blog='.$blog, ' '.TB_('Refresh'), 3, 4, array( 'onclick' => 'startRefreshComments( \'dashboard\' ); return false;', 'class' => 'btn btn-default btn-sm' ) );

				$show_statuses_param = $param_prefix.'show_statuses[]='.implode( '&amp;'.$param_prefix.'show_statuses[]=', $user_modeartion_statuses );
				$block_item_Widget->title = '<span class="pull-right panel_heading_action_icons">'.$opentrash_link.$refresh_link.'</span>'.
					TB_('Comments awaiting moderation').
					' <a href="'.$admin_url.'?ctrl=comments&amp;blog='.$Blog->ID.'&amp;'.$show_statuses_param.'" style="text-decoration:none">'.
					'<span id="badge" class="badge badge-important">'.$CommentList->get_total_rows().'</span></a>'.
					get_manual_link( 'dashboard-comments-awaiting-moderation' );

				echo '<div class="evo_content_block">';
				echo '<div id="comments_block" class="dashboard_comments_block">';

				$block_item_Widget->disp_template_replaced( 'block_start' );
				echo '<div id="comments_container" class="evo_comments_container">';

				// GET COMMENTS AWAITING MODERATION (the code generation is shared with the AJAX callback):
				$Timer->start( 'show_comments_awaiting_moderation' );
				// erhsatingin > this takes up most of the rendering time!
				show_comments_awaiting_moderation( $Blog->ID, $CommentList );
				$Timer->stop( 'show_comments_awaiting_moderation' );

				echo '</div>';
				$block_item_Widget->disp_template_raw( 'block_end' );
				echo '</div></div>';
				echo '<!-- End of Comments Awaiting Moderation Block -->';
				$Timer->stop( 'Panel: Comments Awaiting Moderation' );
			}

			// Posts Awaiting Moderation Block
			if( !empty( $have_posts_to_moderate ) )
			{
				$Timer->resume( 'Panel: Posts Awaiting Moderation' );
				echo '<!-- Start of Posts Awaiting Moderation Block -->';
				echo '<div class="items_container evo_content_block">';
				echo $posts_awaiting_moderation_content;
				echo '</div>';
				echo '<!-- End of Posts Awaiting Moderation Block -->';
				$Timer->stop( 'Panel: Posts Awaiting Moderation' );
			}

			// The following div is required to ensure that Block Group 3 will align properly on large screen media
			echo '<div style="min-height: 100px;" class="hidden-xs hidden-sm hidden-md"></div>';
			echo '</div><!-- End of Block Group 2 -->';
		}

		evo_flush();

		// Block Group 3
		echo '<!-- Start of Block Group 3 -->';
		if( $perm_options_edit )
		{
			echo '<div class="col-xs-12 col-sm-12 '
				.( $have_comments_to_moderate ? 'col-md-12' : 'col-md-9' ).' col-md-pull-0 '
				.( $have_comments_to_moderate || $have_posts_to_moderate ? 'col-lg-6' : 'col-lg-9' )
				.' col-lg-pull-0 coll-dashboard-block-3">';
		}
		else
		{
			echo '<div class="col-xs-12 col-sm-12 col-md-12'
				.( $have_comments_to_moderate || $have_posts_to_moderate ? ' col-md-pull-0 col-lg-6 col-lg-pull-0' : '' ).'">';
		}

		if( check_user_perm( 'meta_comment', 'view', false, $Blog->ID ) )
		{	// If user has a perm to view internal comments of the collection:

			// Latest Internal Comments Block
			$Timer->start( 'Panel: Latest Internal Comments' );
			$CommentList = new CommentList2( $Blog );

			// Filter list:
			$CommentList->set_filters( array(
					'types' => array( 'meta' ),
					'order' => 'DESC',
					'comments' => 5,
				) );

			// Set param prefix for URLs:
			$param_prefix = 'cmnt_meta_';
			if( !empty( $CommentList->param_prefix ) )
			{
				$param_prefix = $CommentList->param_prefix;
			}

			// Get ready for display (runs the query):
			$CommentList->display_init();

			// Load data of comments from the current page at once to cache variables:
			$CommentList->load_list_data();

			if( $CommentList->result_num_rows )
			{	// We have the internal comments

				load_funcs( 'comments/model/_comment_js.funcs.php' );

				$nb_blocks_displayed++;

				echo '<!-- Start of Latest Internal Comments Block -->';

				$show_statuses_param = $param_prefix.'show_statuses[]='.implode( '&amp;'.$param_prefix.'show_statuses[]=', $user_modeartion_statuses );
				$block_item_Widget->title = TB_('Latest Internal Comments').
					' <a href="'.$admin_url.'?ctrl=comments&amp;blog='.$Blog->ID.'&amp;tab3=meta" style="text-decoration:none">'.
					'<span id="badge" class="badge badge-important">'.$CommentList->get_total_rows().'</span></a>';

				echo '<a id="comments"></a>'; // Used to animate a moving the deleting comment to trash by ajax
				echo '<div id="styled_content_block" class="evo_content_block">';
				echo '<div id="meta_comments_block" class="dashboard_comments_block dashboard_comments_block__meta">';

				$block_item_Widget->disp_template_replaced( 'block_start' );

				echo '<div id="comments_container" class="evo_comments_container">';
				// GET LATEST INTERNAL COMMENTS:
				show_comments_awaiting_moderation( $Blog->ID, $CommentList );
				echo '</div>';
				$block_item_Widget->disp_template_raw( 'block_end' );

				echo '</div>';
				echo '<!-- End of Latest Internal Comments Block-->';
			}
			$Timer->start( 'Panel: Latest Internal Comments' );
		}

		$Timer->start( 'Panel: Recently Edited Post' );

		// Recently Edited Posts Block
		// Create empty List:
		$ItemList = new ItemList2( $Blog, NULL, NULL );

		// Filter list:
		$ItemList->set_filters( array(
				'visibility_array' => get_visibility_statuses( 'keys', array('trash') ),
				'orderby' => 'datemodified',
				'order' => 'DESC',
				'posts' => 5,
			) );

		// Get ready for display (runs the query):
		$ItemList->display_init();

		if( $ItemList->result_num_rows )
		{	// We have recent edits

			$nb_blocks_displayed++;

			echo '<!-- Start of Recently Edited Post Block-->';
			if( check_user_perm( 'blog_post_statuses', 'edit', false, $Blog->ID ) )
			{	// We have permission to add a post with at least one status:
				$block_item_Widget->global_icon( TB_('Write a new post...'), 'new', '?ctrl=items&amp;action=new&amp;blog='.$Blog->ID, TB_('New post').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary btn-sm' ) );
			}
			echo '<div class="items_container evo_content_block">';

			$block_item_Widget->title = TB_('Recently edited posts').get_manual_link( 'dashboard-recently-edited-posts' );
			$block_item_Widget->disp_template_replaced( 'block_start' );

			while( $Item = & $ItemList->get_item() )
			{
				echo '<div class="dashboard_post dashboard_post_'.($ItemList->current_idx % 2 ? 'even' : 'odd' ).'" lang="'.$Item->get('locale').'">';
				// We don't switch locales in the backoffice, since we use the user pref anyway
				// Load item's creator user:
				$Item->get_creator_User();

		/* OLD:
				$Item->status( array(
						'before' => '<div class="floatright"><span class="note status_'.$Item->status.'"><span>',
						'after'  => '</span></span></div>',
					) );
		NEW:
		*/
				$Item->format_status( array(
						'template' => '<div class="floatright"><span class="note status_$status$" data-toggle="tooltip" data-placement="top" title="$tooltip_title$"><span>$status_title$</span></span></div>',
					) );

				echo '<div class="dashboard_float_actions">';
				$Item->edit_link( array( // Link to backoffice for editing
						'before'    => ' ',
						'after'     => ' ',
						'class'     => 'ActionButton btn btn-primary btn-sm w80px',
						'text'      => get_icon( 'edit_button' ).' '.TB_('Edit')
					) );

				// Display images that are linked to this post:
				$Item->images( array(
						'before'              => '<div class="dashboard_thumbnails">',
						'before_image'        => '',
						'before_image_legend' => NULL,	// No legend
						'after_image_legend'  => NULL,
						'after_image'         => '',
						'after'               => '</div>',
						'image_size'          => 'crop-80x80',
						'limit'               => 1,
						// Optionally restrict to files/images linked to specific position: 'teaser'|'teaserperm'|'teaserlink'|'aftermore'|'inline'|'fallback'|'cover'|'background'
						'restrict_to_image_position' => 'cover,background,teaser,teaserperm,teaserlink,aftermore,inline',
						// Sort the attachments to get firstly "Cover", then "Teaser", and "After more" as last order
						'links_sql_select'    => ', CASE '
								.'WHEN link_position = "cover"      THEN "1" '
								.'WHEN link_position = "teaser"     THEN "2" '
								.'WHEN link_position = "teaserperm" THEN "3" '
								.'WHEN link_position = "teaserlink" THEN "4" '
								.'WHEN link_position = "aftermore"  THEN "5" '
								.'WHEN link_position = "inline"     THEN "6" '
								// .'ELSE "99999999"' // Use this line only if you want to put the other position types at the end
							.'END AS position_order',
						'links_sql_orderby'   => 'position_order, link_order',
					) );
				echo '</div>';

				echo '<div class="dashboard_content">';

				echo '<h3 class="dashboard_post_title">';
				$item_title = $Item->dget('title');
				if( ! strlen($item_title) )
				{
					$item_title = '['.format_to_output(TB_('No title')).']';
				}
				echo '<a href="?ctrl=items&amp;blog='.$Blog->ID.'&amp;p='.$Item->ID.'">'.$item_title.'</a>';
				echo '</h3>';

				echo $Item->get( 'excerpt' );

				echo '</div>';

				echo '<div class="clear"></div>';
				echo '</div>';
			}

			echo '</div></div>';
			echo '<!-- End of Recently Edited Post Block -->';
			$Timer->stop( 'Panel: Recently Edited Post' );

			$block_item_Widget->disp_template_raw( 'block_end' );
		}

		// Getting Started Block
		if( $nb_blocks_displayed == 0 )
		{	// We haven't displayed anything yet!

			$nb_blocks_displayed++;

			$block_item_Widget = new Widget( 'block_item' );
			$block_item_Widget->title = TB_('Getting Started');
			$block_item_Widget->disp_template_replaced( 'block_start' );

			echo '<p><strong>'.TB_('Welcome to your new collection\'s dashboard!').'</strong></p>';
			echo '<p>'.sprintf( TB_('Write your <a %s>first post</a>  or customize this collection via the <a %s>Settings</a> tab.'), 'href="'.get_dispctrl_url( 'items', 'action=new&amp;blog='.$Blog->ID ).'"', 'href="'.get_dispctrl_url( 'coll_settings', 'tab=general&amp;blog='.$Blog->ID ).'"').'</p>';
			echo '<p>'.TB_('You can see your collection\'s front page at any time by clicking "Collection &raquo; Front Page..." in the b2evolution toolbar at the top of this page.').'</p>';
			echo '<p>'.TB_('You can come back here at any time by clicking "Collection &raquo; Dashboard..." in that same evobar.').'</p>';

			$block_item_Widget->disp_template_raw( 'block_end' );
		}

		// End payload block:
		$AdminUI->disp_payload_end();
		echo '</div>'."\n";
		echo '<!-- End of Block Group 3 --></div>';
		evo_flush();
	}
	else
	{ // We're on the GLOBAL tab...
		$AdminUI->disp_payload_begin();
		// Display blog list VIEW:
		$AdminUI->disp_view( 'collections/views/_coll_list.view.php' );
		$AdminUI->disp_payload_end();


		/*
		* DashboardGlobalMain to be added here (anyone?)
		*/
	}

	if( ! empty( $chart_data ) )
	{ // JavaScript to initialize charts
	?>
	<script>
	jQuery( 'document' ).ready( function()
	{
		var chart_params = {
			barColor: function(percent)
			{
				return get_color_by_percent( {r:97, g:189, b:79}, {r:242, g:214, b:0}, {r:255, g:171, b:74}, percent );
			},
			size: 75,
			trackColor: '#eee',
			scaleColor: false,
			lineCap: 'round',
			lineWidth: 6,
			animate: 700
		}
		jQuery( '.chart .number' ).easyPieChart( chart_params );
	} );

	function get_color_by_percent( color_from, color_middle, color_to, percent )
	{
		function get_color_hex( start_color, end_color )
		{
			num = start_color + Math.round( ( end_color - start_color ) * ( percent / 100 ) );
			num = Math.min( num, 255 ); // not more than 255
			num = Math.max( num, 0 ); // not less than 0
			var str = num.toString( 16 );
			if( str.length < 2 )
			{
				str = "0" + str;
			}
			return str;
		}

		if( percent < 50 )
		{
			color_to = color_middle;
			percent *= 2;
		}
		else
		{
			color_from = color_middle;
			percent = ( percent - 50 ) * 2;
		}

		return "#" +
			get_color_hex( color_from.r, color_to.r ) +
			get_color_hex( color_from.g, color_to.g ) +
			get_color_hex( color_from.b, color_to.b );
	}
	</script>
	<?php
	}

	// Display body bottom, debug info and close </html>:
	$AdminUI->disp_global_footer();
}
else
{
	$AdminUI->set_path( 'collections', $tab );


	/**
	* Display page header, menus & messages:
	*/
	$AdminUI->set_coll_list_params( 'blog_properties', 'edit',
							array( 'ctrl' => 'coll_settings', 'tab' => $tab, 'action' => 'edit' ) );


	$AdminUI->breadcrumbpath_init( true, array( 'text' => TB_('Collections'), 'url' => $admin_url.'?ctrl=' ) );
	switch( $AdminUI->get_path(1) )
	{
		case 'general':
			$AdminUI->set_path( 'collections', 'settings', $tab );
			$AdminUI->breadcrumbpath_add( TB_('Settings'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab=general' );
			$AdminUI->breadcrumbpath_add( TB_('General'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
			$AdminUI->set_page_manual_link( 'general-collection-settings' );
			if( $action == 'type' )
			{
				$AdminUI->breadcrumbpath_add( TB_('Collection type'), '?ctrl=coll_settings&amp;blog=$blog$&amp;action=type&amp;tab='.$tab );
			}
			// Init JS to autcomplete the user logins
			init_autocomplete_login_js( 'rsc_url', $AdminUI->get_template( 'autocomplete_plugin' ) );
			break;

		case 'home':
			$AdminUI->set_path( 'collections', 'features', $tab );
			$AdminUI->breadcrumbpath_add( TB_('Features'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
			$AdminUI->breadcrumbpath_add( TB_('Front page'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
			$AdminUI->set_page_manual_link( 'collection-front-page-settings' );
			break;

		case 'features':
			$AdminUI->set_path( 'collections', 'features', $tab );
			$AdminUI->breadcrumbpath_add( TB_('Features'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab=home' );
			$AdminUI->breadcrumbpath_add( TB_('Posts'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
			$AdminUI->set_page_manual_link( 'post-features' );
			break;

		case 'comments':
			$AdminUI->set_path( 'collections', 'features', $tab );
			$AdminUI->breadcrumbpath_add( TB_('Features'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab=home' );
			$AdminUI->breadcrumbpath_add( TB_('Comments'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
			$AdminUI->set_page_manual_link( 'comment-features' );
			break;

		case 'contact':
			$AdminUI->set_path( 'collections', 'features', $tab );
			$AdminUI->breadcrumbpath_add( TB_('Features'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab=home' );
			$AdminUI->breadcrumbpath_add( TB_('Contact form'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
			$AdminUI->set_page_manual_link( 'contact-form-features' );
			break;

		case 'userdir':
			$AdminUI->set_path( 'collections', 'features', $tab );
			$AdminUI->breadcrumbpath_add( TB_('Features'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab=home' );
			$AdminUI->breadcrumbpath_add( TB_('User directory'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
			$AdminUI->set_page_manual_link( 'features-user-directory' );
			break;

		case 'search':
			$AdminUI->set_path( 'collections', 'features', $tab );
			$AdminUI->breadcrumbpath_add( TB_('Features'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab=home' );
			$AdminUI->breadcrumbpath_add( TB_('Search'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
			$AdminUI->set_page_manual_link( 'features-search' );
			break;

		case 'other':
			$AdminUI->set_path( 'collections', 'features', $tab );
			$AdminUI->breadcrumbpath_add( TB_('Features'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab=home' );
			$AdminUI->breadcrumbpath_add( TB_('Other displays'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
			$AdminUI->set_page_manual_link( 'features-others' );
			break;

		case 'popup':
			$AdminUI->set_path( 'collections', 'features', $tab );
			$AdminUI->breadcrumbpath_add( TB_('Features'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab=home' );
			$AdminUI->breadcrumbpath_add( TB_('Popups'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
			$AdminUI->set_page_manual_link( 'features-popups' );
			break;

		case 'metadata':
			$AdminUI->set_path( 'collections', 'features', $tab );
			$AdminUI->breadcrumbpath_add( TB_('Features'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab=home' );
			$AdminUI->breadcrumbpath_add( TB_('Meta data'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
			$AdminUI->set_page_manual_link( 'blog-meta-data' );
			break;

		case 'more':
			$AdminUI->set_path( 'collections', 'features', $tab );
			$AdminUI->breadcrumbpath_add( TB_('Features'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab=home' );
			$AdminUI->breadcrumbpath_add( TB_('More'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
			$AdminUI->set_page_manual_link( 'more-features' );
			break;

		case 'skin':
			$AdminUI->set_path( 'collections', 'skin', 'skin_'.$skin_type );
			$AdminUI->breadcrumbpath_add( TB_('Skin'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
			if( $skinpage == 'selection' )
			{
				$AdminUI->breadcrumbpath_add( TB_('Skin selection'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab.'&amp;skinpage=selection' );
			}
			else
			{
				init_colorpicker_js();
				$AdminUI->breadcrumbpath_add( TB_('Default'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
			}
			$AdminUI->set_page_manual_link( 'skins-for-this-blog' );
			break;

		case 'urls':
			$AdminUI->set_path( 'collections', 'settings', $tab );
			$AdminUI->breadcrumbpath_add( TB_('Settings'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab=general' );
			$AdminUI->breadcrumbpath_add( TB_('URLs'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
			$AdminUI->set_page_manual_link( 'url-settings' );
			break;

		case 'seo':
			$AdminUI->set_path( 'collections', 'settings', $tab );
			$AdminUI->breadcrumbpath_add( TB_('Settings'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab=general' );
			$AdminUI->breadcrumbpath_add( TB_('SEO'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
			$AdminUI->set_page_manual_link( 'seo-settings' );
			break;

		case 'plugins':
			$AdminUI->set_path( 'collections', 'settings', $tab );
			$AdminUI->breadcrumbpath_add( TB_('Settings'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab=general' );
			$AdminUI->breadcrumbpath_add( TB_('Plugins'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
			$AdminUI->set_page_manual_link( 'blog-plugin-settings' );
			// Initialize JS for color picker field on the edit plugin settings form:
			init_colorpicker_js();
			break;

		case 'advanced':
			$AdminUI->set_path( 'collections', 'settings', $tab );
			$AdminUI->breadcrumbpath_add( TB_('Settings'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab=general' );
			$AdminUI->breadcrumbpath_add( TB_('Advanced settings'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
			$AdminUI->set_page_manual_link( 'advanced-collection-settings' );
			break;

		case 'perm':
			$AdminUI->set_path( 'collections', 'settings', $tab );
			load_funcs( 'collections/views/_coll_perm_view.funcs.php' );
			$AdminUI->breadcrumbpath_add( TB_('Settings'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab=general' );
			$AdminUI->breadcrumbpath_add( TB_('User permissions'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
			$AdminUI->set_page_manual_link( 'advanced-user-permissions' );
			// Load JavaScript to toggle checkboxes:
			require_js_async( 'collectionperms.js', 'rsc_url' );
			break;

		case 'permgroup':
			$AdminUI->set_path( 'collections', 'settings', $tab );
			load_funcs( 'collections/views/_coll_perm_view.funcs.php' );
			$AdminUI->breadcrumbpath_add( TB_('Settings'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab=general' );
			$AdminUI->breadcrumbpath_add( TB_('Group permissions'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
			$AdminUI->set_page_manual_link( 'advanced-group-permissions' );
			// Load JavaScript to toggle checkboxes:
			require_js_async( 'collectionperms.js', 'rsc_url' );
			break;
	}

	// Initialize Hotkeys:
	init_hotkeys_js();


	// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
	$AdminUI->disp_html_head();

	// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
	$AdminUI->disp_body_top();


	// Begin payload block:
	$AdminUI->disp_payload_begin();


	// Display VIEW:
	switch( $AdminUI->get_path(1) )
	{
		case 'features':
			switch( $AdminUI->get_path(2) )
			{
				case 'features':
					$AdminUI->disp_view( 'collections/views/_coll_features.form.php' );
					break;
				case 'comments':
					$AdminUI->disp_view( 'collections/views/_coll_comments.form.php' );
					break;
				case 'contact':
					$AdminUI->disp_view( 'collections/views/_coll_contact.form.php' );
					break;
				case 'userdir':
					$AdminUI->disp_view( 'collections/views/_coll_user_dir.form.php' );
					break;
				case 'search':
					$AdminUI->disp_view( 'collections/views/_coll_search.form.php' );
					break;
				case 'other':
					$AdminUI->disp_view( 'collections/views/_coll_other.form.php' );
					break;
				case 'popup':
					$AdminUI->disp_view( 'collections/views/_coll_popup.form.php' );
					break;
				case 'metadata':
					$AdminUI->disp_view( 'collections/views/_coll_metadata.form.php' );
					break;
				case 'more':
					$AdminUI->disp_view( 'collections/views/_coll_more.form.php' );
					break;
				default:
					$AdminUI->disp_view( 'collections/views/_coll_home.form.php' );
					break;
			}
			break;

		case 'skin':
			if( $skinpage == 'selection' )
			{
				$AdminUI->disp_view( 'skins/views/_coll_skin.view.php' );
			}
			else
			{
				$AdminUI->disp_view( 'skins/views/_coll_skin_settings.form.php' );
			}
			break;

		case 'settings':
			switch( $AdminUI->get_path(2) )
			{
				case 'general':
					if( $action == 'type' )
					{	// Form to change type
						$AdminUI->disp_view( 'collections/views/_coll_type.form.php' );
					}
					else
					{	// General settings of blog
						$next_action = 'update';
						$AdminUI->disp_view( 'collections/views/_coll_general.form.php' );
					}
					break;
				case 'urls':
					$AdminUI->disp_view( 'collections/views/_coll_urls.form.php' );
					break;
				case 'seo':
					$AdminUI->disp_view( 'collections/views/_coll_seo.form.php' );
					break;
				case 'plugins':
					$AdminUI->disp_view( 'collections/views/_coll_plugin_settings.form.php' );
					break;
				case 'advanced':
					$AdminUI->disp_view( 'collections/views/_coll_advanced.form.php' );
					break;
				case 'perm':
					$AdminUI->disp_view( 'collections/views/_coll_user_perm.form.php' );
					break;
				case 'permgroup':
					$AdminUI->disp_view( 'collections/views/_coll_group_perm.form.php' );
					break;
			}
			break;
	}

	// End payload block:
	$AdminUI->disp_payload_end();


	// Display body bottom, debug info and close </html>:
	$AdminUI->disp_global_footer();
}

?>
