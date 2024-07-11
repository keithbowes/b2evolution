<?php
/**
 * This file implements functions that creation of demo content for posts, comments, categories, etc.
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

global $baseurl, $admin_url, $new_db_version;
global $random_password, $query;
global $timestamp, $admin_email;
global $admins_Group, $moderators_Group, $editors_Group, $users_Group, $suspect_Group, $blogb_Group;
global $blog_all_ID, $blog_home_ID, $blog_a_ID, $blog_b_ID;
global $DB;
global $default_locale, $default_country;
global $Plugins, $Settings;
global $test_install_all_features;
global $user_org_IDs;
global $user_timestamp;

load_class( 'items/model/_item.class.php', 'Item' );
load_class( 'files/model/_file.class.php', 'File' );
load_class( 'links/model/_linkuser.class.php', 'LinkUser' );
load_class( 'users/model/_group.class.php', 'Group' );
load_funcs( 'collections/model/_category.funcs.php' );
load_class( 'users/model/_organization.class.php', 'Organization' );
load_class( 'collections/model/_section.class.php', 'Section' );


/**
 * Begin install task.
 * This will offer other display methods in the future
 */
function task_begin( $title )
{
	echo get_install_format_text_and_log( $title."\n" );
	evo_flush();
}


/**
 * End install task.
 * This will offer other display methods in the future
 */
function task_end( $message = 'OK.' )
{
	echo get_install_format_text_and_log( $message."<br />\n", 'br' );
}


/**
 * Display task errors.
 */
function task_errors( $errors = array(), $type = 'danger' )
{
	if( empty( $errors ) )
	{
		return;
	}

	echo get_install_format_text_and_log( '<br />', 'br' );
	foreach( $errors as $error )
	{
		echo get_install_format_text_and_log( '<span class="text-'.$type.'">'.$error.'</span><br />', 'br' );
	}
}


/**
 * Adjust timestamp value, adjusts it to the current time if not yet set
 *
 * @param timestamp Base timestamp
 * @param integer Min interval in minutes
 * @param integer Max interval in minutes
 * @param boolean Advance timestamp if TRUE, move back if otherwise
 */
function adjust_timestamp( & $base_timestamp, $min = 360, $max = 1440, $forward_direction = true )
{
	if( isset( $base_timestamp ) )
	{
		$interval = ( rand( $min, $max ) * 60 ) + rand( 0, 3600 );
		if( $forward_direction )
		{
			$base_timestamp += $interval;
		}
		else
		{
			$base_timestamp -= $interval;
		}
	}
	else
	{
		$base_timestamp = time();
	}
}


/**
 * Get array of timestamps with random intervals
 *
 * @param integer Number of iterations
 * @param integer Min interval in minutes
 * @param integer Max interval in minutes
 * @param timestamp Base timestamp
 * @return array Array of timestamps
 */
function get_post_timestamp_data( $num_posts = 1, $min = 30, $max = 720, $base_timestamp = NULL )
{
	if( is_null( $base_timestamp ) )
	{
		global $install_post_random_timestamps;
		if( empty( $install_post_random_timestamps ) )
		{	// Start new timestamp from current time:
			$base_timestamp = time();
		}
		else
		{	// Continue to use next time after previous calling:
			$base_timestamp = $install_post_random_timestamps;
		}
	}

	// Add max comment time allowance, i.e., 2 comments at max. 12 hour interval
	$base_timestamp -= 1440 * 60;

	$loop_timestamp = $base_timestamp;
	$post_timestamp_array = array();
	for( $i = 0; $i < $num_posts; $i++ )
	{
		$interval = ( rand( $min, $max ) * 60 ) + rand( 0, 3600 );
		$loop_timestamp -= $interval;
		$install_post_random_timestamps = $loop_timestamp;
		$post_timestamp_array[] = $loop_timestamp;
	}

	return $post_timestamp_array;
}


/**
 * Check if item type is available by name or template name for requested collection
 *
 * @param integer Collection IF
 * @param string Item Type name or $template_name$
 * @return boolean
 */
function is_available_item_type( $blog_ID, $item_type_name_or_template = '#' )
{
	global $DB, $available_item_types;

	$BlogCache = & get_BlogCache();
	$ItemTypeCache = & get_ItemTypeCache();

	$mode = 'name';

	if( $item_type_name_or_template == '#' )
	{
		$Blog = & $BlogCache->get_by_ID( $blog_ID );
		$default_item_type = $ItemTypeCache->get_by_ID( $Blog->get_setting( 'default_post_type' ) );
		$item_type_name_or_template = $default_item_type->get_name();
	}
	elseif( preg_match( '/^\$(.+)\$$/', $item_type_name_or_template, $ityp_match ) )
	{	// This is a request to check by template name, because param is in format like $template_name$:
		$mode = 'template';
		$item_type_name_or_template = $ityp_match[1];
	}

	if( ! isset( $available_item_types[ $blog_ID ] ) )
	{
		if( ! isset( $available_item_types ) )
		{
			$available_item_types = array();
		}
		$available_item_types[ $blog_ID ] = array( 'name' => array(), 'template' => array() );
		$SQL = new SQL( 'Get available item types for the collection #'.$blog_ID );
		$SQL->SELECT( 'ityp_name, ityp_template_name' );
		$SQL->FROM( 'T_items__type' );
		$SQL->FROM_add( 'INNER JOIN T_items__type_coll ON itc_ityp_ID = ityp_ID AND itc_coll_ID = '.$blog_ID );
		$item_types = $DB->get_results( $SQL );
		foreach( $item_types as $item_type )
		{
			if( !in_array( $item_type->ityp_name, $available_item_types[ $blog_ID ]['name'] ) )
			{
				$available_item_types[ $blog_ID ]['name'][] = $item_type->ityp_name;
			}
			if( !in_array( $item_type->ityp_template_name, $available_item_types[ $blog_ID ]['template'] ) )
			{
				$available_item_types[ $blog_ID ]['template'][] = $item_type->ityp_template_name;
			}
		}
	}

	return in_array( $item_type_name_or_template, $available_item_types[ $blog_ID ][ $mode ] );
}


/**
 * Display installation options
 *
 * @param array Display params
 */
function echo_installation_options( $params = array() )
{
	$params = array_merge( array(
			'enable_create_demo_users' => true,
			'show_create_organization' => true,
			'show_create_messages'     => true,
			'show_create_email_lists'     => true,
			'show_create_email_campaigns' => true,
			'show_create_automations'     => true,
	), $params );

	$collections = array(
			'home'     => TD_('Global home page'),
			'a'        => TD_('Sample Blog A (Public)'),
			'b'        => TD_('Sample Blog B (Private)'),
			'photos'   => TD_('Photo Albums'),
			'forums'   => TD_('Forums'),
			'manual'   => TD_('Online Manual'),
			'group'    => TD_('Tracker'),
		);

	// Allow all modules to set what collections should be installed
	$module_collections = modules_call_method( 'get_demo_collections' );
	if( ! empty( $module_collections ) )
	{
		foreach( $module_collections as $module_key => $module_colls )
		{
			foreach( $module_colls as $module_coll_key => $module_coll_title )
			{
				$collections[ $module_key.'_'.$module_coll_key ] = $module_coll_title;
			}
		}
	}

	// Options to select a collection for standard site:
	$standard_collections = $collections;
	unset( $standard_collections['home'] );

	$r = '<div class="checkbox">
				<label>
					<input type="checkbox" name="create_sample_contents" id="create_sample_contents" value="1" />'
					.TB_('Create a demo website').'
				</label>
				<div id="create_sample_contents_options" style="margin:10px 0 0 20px;display:none">
					<div class="radio" style="margin-left:1em">
						<label>
							<input type="radio" name="demo_content_type" id="minisite_demo" value="minisite" />'
							.TB_('Mini-Site').'
						</label>
					</div>
					<div class="radio" style="margin-left:1em">
						<label>
							<input type="radio" name="demo_content_type" id="standard_site_demo" value="standard_site" style="margin-top:9px" />'
							.TB_('Standard Site (1 collection):').'
							<span class="form-inline"><select name="standard_collection" class="form-control">'.Form::get_select_options_string( $standard_collections, NULL, true ).'</span>
							</select>
						</label>
					</div>
					<div class="radio" style="margin-left:1em">
						<label>
							<input type="radio" name="demo_content_type" id="complex_site_demo" value="complex_site" checked="checked" />'
							.TB_('Complex Site, including multiple collections:').'
						</label>
					</div>';

	// Display the collections to select which install
	foreach( $collections as $coll_index => $coll_title )
	{	// Display the checkboxes to select what demo collection to install
		$r .= '<div class="checkbox" style="margin-left:2em">
						<label>
							<input type="checkbox" name="collections[]" id="collection_'.$coll_index.'" value="'.$coll_index.'" checked="checked" />'
							.$coll_title.'
						</label>
					</div>';
	}

	$r .= '</div></div>';


	$r .= '<div class="checkbox" style="margin-top: 15px">
					<label>
						<input type="checkbox" name="create_demo_users" id="create_demo_users" value="1"'.( $params['enable_create_demo_users'] ? '' : ' disabled="disabled"' ).' />'
						.( $params['enable_create_demo_users'] ? TB_('Create demo users') : TB_('Your system already has several user accounts, so we won\'t create demo users.') ).
					'</label>
					<div id="create_demo_user_options" style="margin:10px 0 0 20px;display:none">';

	if( $params['show_create_organization'] )
	{
		$r .= '<div class="checkbox" style="margin-left: 1em">
						<label>
							<input type="checkbox" name="create_demo_organization" id="create_demo_organization" value="1" checked="checked" disabled="disabled" />'
							.TB_('Create a demo organization / team').
						'</label>
					</div>';
	}

	if( $params['show_create_messages'] )
	{
		$r .= '<div class="checkbox" style="margin-left: 1em">
						<label>
							<input type="checkbox" name="create_sample_private_messages" id="create_sameple_private_messages" value="1" checked="checked" disabled="disabled" />'
							.TB_('Create demo private messages between users').
						'</label>
					</div>';
	}

	$r .= '</div></div>';

	$r .= '<div class="checkbox" style="margin-top: 15px">
					<label>
						<input type="checkbox" name="create_demo_email_lists" id="create_demo_email_lists" value="1"'.( $params['show_create_email_lists'] ? '' : ' disabled="disabled"' ).' />'
						.( $params['show_create_email_lists'] ? TB_('Create demo email lists') : TB_('Your system already has an email list, so we won\'t create demo lists.') ).
					'</label>
					<div id="create_demo_email_options" style="margin:10px 0 0 20px;display:none">';

	if( $params['show_create_email_campaigns'] )
	{
		$r .= '<div class="checkbox" style="margin-left: 1em">
						<label>
							<input type="checkbox" name="create_demo_email_campaigns" id="create_demo_email_campaigns" value="1" checked="checked" disabled="disabled" />'
							.TB_('Create demo campaigns').
						'</label>
					</div>';
	}

	if( $params['show_create_automations'] )
	{
		$r .= '<div class="checkbox" style="margin-left: 1em">
						<label>
							<input type="checkbox" name="create_demo_automations" id="create_demo_automations" value="1" checked="checked" disabled="disabled" />'
							.TB_('Create demo automations').
						'</label>
					</div>';
	}

	$r .= '</div></div>';

	$r .= '<script type="text/javascript">
					function toggle_create_demo_content_options()
					{
						if( jQuery( "#create_sample_contents" ).is( ":checked" ) )
						{
							jQuery( "#create_sample_contents_options" ).show();
						}
						else
						{
							jQuery( "#create_sample_contents_options" ).hide();
						}
					}

					function toggle_demo_content_type_options()
					{
						jQuery( "input[name=\'collections[]\']" ).prop( "disabled", ( jQuery( "input[name=demo_content_type]:checked" ).val() != "complex_site" ) );
					}

					function toggle_create_demo_user_options()
					{
						var checked = jQuery( "#create_demo_users" ).is( ":checked" );
						jQuery( "#create_demo_user_options" ).toggle( checked );
						jQuery( "input[name=create_demo_organization], input[name=create_sample_private_messages]" ).prop( "disabled", ! checked );
					}

					function toggle_create_demo_email_options()
					{
						var checked = jQuery( "#create_demo_email_lists" ).is( ":checked" );
						jQuery( "#create_demo_email_options" ).toggle( checked );
						jQuery( "input[name=create_demo_email_campaigns], input[name=create_demo_automations]" ).prop( "disabled", ! checked );
					}

					jQuery( document ).ready( function() {
							toggle_create_demo_content_options();
							toggle_demo_content_type_options();
							toggle_create_demo_user_options();
							toggle_create_demo_email_options();
						} );

					jQuery( "#create_sample_contents" ).click( toggle_create_demo_content_options );
					jQuery( "input[name=\"demo_content_type\"]" ).click( toggle_demo_content_type_options );
					jQuery( "#create_demo_users" ).click( toggle_create_demo_user_options );
					jQuery( "#create_demo_email_lists" ).click( toggle_create_demo_email_options );
					jQuery( "select[name=standard_collection]" ).focus( function() {
						jQuery( "#standard_site_demo" ).prop( "checked", true );
						toggle_demo_content_type_options();
					} );

				</script>';

	return $r;
}


/**
 * Generate filler text for demo content
 *
 * @param string Type of filler text
 * @return string Filler text
 */
function get_filler_text( $type = NULL )
{
	$filler_text = '';

	switch( $type )
	{
		case 'lorem_1paragraph':
			$filler_text = "\n\n<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>";
			break;

		case 'lorem_2more':
			$filler_text = "\n\n<p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?</p>\n\n"
		."<p>At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident, similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga. Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus, omnis voluptas assumenda est, omnis dolor repellendus. Temporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus saepe eveniet ut et voluptates repudiandae sint et molestiae non recusandae. Itaque earum rerum hic tenetur a sapiente delectus, ut aut reiciendis voluptatibus maiores alias consequatur aut perferendis doloribus asperiores repellat.</p>";
			break;

		case 'info_page':
			$filler_text = TD_('<p>This website is powered by b2evolution.</p>')."\r\n"
				.TD_('<p>You are currently looking at an info page about "%s".</p>')."\r\n"
				.TD_('<p>Info pages are Standalone pages: contrary to regular Posts, do not appear in the regular flow of posts. Instead, they are typically accessed directly from a navigation menu.</p>')."\r\n"
				.TD_('<p>Note: If needed, skins may format info pages differently from regular posts.</p>');
			break;

		case 'markdown_examples_content':
			$filler_text = TD_('Heading
=======

Sub-heading
-----------

### H3 header

#### H4 header ####

> Email-style angle brackets
> are used for blockquotes.

> > And, they can be nested.

> ##### Headers in blockquotes
>
> * You can quote a list.
> * Etc.

[This is a link](http://b2evolution.net/) if Links are turned on in the markdown plugin settings

Paragraphs are separated by a blank line.

    This is a preformatted
    code block.

Text attributes *Italic*, **bold**, `monospace`.

Shopping list:

* apples
* oranges
* pears

The rain---not the reign---in Spain.');
			break;
	}

	return $filler_text;
}

/**
 * Create a new blog
 * This funtion has to handle all needed DB dependencies!
 *
 * @todo move this to Blog object (only half done here)
 */
function create_blog(
		$blog_name,
		$blog_shortname,
		$blog_urlname,
		$blog_tagline = '',
		$blog_longdesc = '',
		$blog_skin_name = 'Bootstrap Blog',
		$kind = 'std', // standard blog; notorious variations: "photo", "group", "forum"
		$allow_rating_items = '',
		$use_inskin_login = 0,
		$blog_access_type = '#', // '#' - to use default access type from $Settings->get( 'coll_access_type' ) - "Default URL for New Collections", possible values: baseurl, default, index.php, extrabase, extrapath, relative, subdom, absolute
		$allow_html = true,
		$in_bloglist = 'public',
		$owner_user_ID = 1,
		$blog_allow_access = 'public',
		$section_ID = NULL )
{
	global $default_locale, $install_test_features, $local_installation, $Plugins, $Blog;

	$SkinCache = & get_SkinCache();
	$blog_Skin = & $SkinCache->get_by_name( $blog_skin_name, false, false );
	if( ! $blog_Skin )
	{	// Try looking for skin using class name:
		$blog_skin_class = strtolower( $blog_skin_name );
		$blog_skin_class = trim( preg_replace( array( '/\h+/', '/_[s|S]kin$/' ), array( '_', '' ), $blog_skin_class ) ).'_Skin';
		$blog_Skin = & $SkinCache->get_by_class( $blog_skin_name, false, false );
	}

	if( ! $blog_Skin )
	{
		trigger_error( sprintf( 'Unable to find the default skin of the collection (%s).', $blog_skin_name ), E_USER_NOTICE );
		return false;
	}

	$Collection = $Blog = new Blog( NULL );

	if( $blog_access_type != '#' )
	{	// Force default new collection URL with a given param:
		$Blog->set( 'access_type', $blog_access_type );
	}

	$Blog->set( 'sec_ID', $section_ID );
	$Blog->set( 'normal_skin_ID', $blog_Skin->ID );

	$Blog->init_by_kind( $kind, $blog_name, $blog_shortname, $blog_urlname );

	if( ( $kind == 'forum' || $kind == 'manual' ) && ( $Plugin = & $Plugins->get_by_code( 'b2evMark' ) ) !== false )
	{	// Initialize special Markdown plugin settings for Forums and Manual blogs
		$Blog->set_setting( 'plugin'.$Plugin->ID.'_coll_apply_comment_rendering', 'opt-out' );
		$Blog->set_setting( 'plugin'.$Plugin->ID.'_links', '1' );
		$Blog->set_setting( 'plugin'.$Plugin->ID.'_images', '1' );
	}
	if( $kind == 'photo' )
	{	// Display category directory on front page for photo blogs
		$Blog->set_setting( 'front_disp', 'catdir' );
	}

	$Blog->set( 'tagline', $blog_tagline );
	$Blog->set( 'longdesc', $blog_longdesc );
	$Blog->set( 'locale', $default_locale );
	$Blog->set( 'in_bloglist', $in_bloglist );
	$Blog->set( 'owner_user_ID', $owner_user_ID );

	$Blog->dbinsert();

	if( $install_test_features )
	{
		$allow_rating_items = 'any';
		$Blog->set_setting( 'skin'.$blog_Skin->ID.'_bubbletip', '1' );
		echo_install_log( 'TEST FEATURE: Activating username bubble tips on skin of collection #'.$Blog->ID );
		$Blog->set_setting( 'skin'.$blog_Skin->ID.'_gender_colored', '1' );
		echo_install_log( 'TEST FEATURE: Activating gender colored usernames on skin of collection #'.$Blog->ID );
		$Blog->set_setting( 'in_skin_editing', '1' );
		echo_install_log( 'TEST FEATURE: Activating in-skin editing on collection #'.$Blog->ID );

		if( $kind == 'manual' )
		{	// Set a posts ordering by 'postcat_order ASC'
			$Blog->set_setting( 'orderby', 'order' );
			$Blog->set_setting( 'orderdir', 'ASC' );
			echo_install_log( 'TEST FEATURE: Setting a posts ordering by ascending post order field on collection #'.$Blog->ID );
		}

		$Blog->set_setting( 'use_workflow', 1 );
		echo_install_log( 'TEST FEATURE: Activating workflow on collection #'.$Blog->ID );
	}
	if( $allow_rating_items != '' )
	{
		$Blog->set_setting( 'allow_rating_items', $allow_rating_items );
	}
	if( $use_inskin_login || $install_test_features )
	{
		$Blog->set_setting( 'in_skin_login', 1 );
	}

	if( !$allow_html )
	{
		$Blog->set_setting( 'allow_html_comment', 0 );
	}

	$Blog->set( 'order', $Blog->ID );

	if( ! empty( $blog_allow_access ) )
	{
		$Blog->set_setting( 'allow_access', $blog_allow_access );
		switch( $blog_allow_access )
		{	// Automatically enable/disable moderation statuses:
			case 'public':
				// Enable "Community" and "Members":
				$enable_moderation_statuses = array( 'community', 'protected' );
				$enable_comment_moderation_statuses = array( 'community', 'protected', 'review', 'draft' );
				$disable_comment_moderation_statuses = array( 'private' );
				break;
			case 'users':
				// Disable "Community" and Enable "Members":
				$disable_moderation_statuses = array( 'community' );
				$enable_moderation_statuses = array( 'protected' );
				$enable_comment_moderation_statuses = array( 'protected', 'review', 'draft' );
				$disable_comment_moderation_statuses = array( 'community', 'private' );
				break;
			case 'members':
				// Disable "Community" and "Members":
				$disable_moderation_statuses = array( 'community', 'protected' );
				$enable_comment_moderation_statuses = array( 'review', 'draft' );
				$disable_comment_moderation_statuses = array( 'community', 'protected', 'private' );
				break;
		}
		$post_moderation_statuses = $Blog->get_setting( 'post_moderation_statuses' );
		$post_moderation_statuses = empty( $post_moderation_statuses ) ? array() : explode( ',', $post_moderation_statuses );
		$comment_moderation_statuses = $Blog->get_setting( 'moderation_statuses' );
		$comment_moderation_statuses = empty( $comment_moderation_statuses ) ? array() : explode( ',', $comment_moderation_statuses );

		if( ! empty( $disable_moderation_statuses ) )
		{	// Disable moderation statuses:
			$post_moderation_statuses = array_diff( $post_moderation_statuses, $disable_moderation_statuses );
			//$comment_moderation_statuses = array_diff( $comment_moderation_statuses, $disable_moderation_statuses );
		}
		if( ! empty( $enable_moderation_statuses ) )
		{	// Enable moderation statuses:
			$post_moderation_statuses = array_unique( array_merge( $enable_moderation_statuses, $post_moderation_statuses ) );
			//$comment_moderation_statuses = array_unique( array_merge( $enable_moderation_statuses, $comment_moderation_statuses ) );
		}

		if( ! empty( $disable_comment_moderation_statuses ) )
		{
			$comment_moderation_statuses = array_diff( $comment_moderation_statuses, $disable_comment_moderation_statuses );
		}
		if( ! empty( $enable_comment_moderation_statuses ) )
		{
			$comment_moderation_statuses = array_unique( array_merge( $enable_comment_moderation_statuses, $comment_moderation_statuses ) );
		}

		$Blog->set_setting( 'post_moderation_statuses', implode( ',', $post_moderation_statuses ) );
		// Force enabled statuses regardless of previous settings
		$Blog->set_setting( 'moderation_statuses', implode( ',', $enable_comment_moderation_statuses ) );
	}

	if( $local_installation || $Blog->get_setting( 'allow_access' ) != 'public' )
	{	// Turn off all ping plugins if the installation is local/test/intranet or this is a not public collection:
		$Blog->set_setting( 'ping_plugins', '' );
	}

	$Blog->dbupdate();

	// Insert default group permissions:
	$Blog->insert_default_group_permissions();

	return $Blog->ID;
}


/**
 * Create a new User
 *
 * @param array Params
 * @return mixed object User if user was succesfully created otherwise false
 */
function create_user( $params = array() )
{
	global $timestamp;
	global $random_password, $admin_email;
	global $default_locale, $default_country;
	global $Messages, $DB;

	$params = array_merge( array(
			'login'     => '',
			'firstname' => NULL,
			'lastname'  => NULL,
			'pass'      => $random_password, // random
			'email'     => $admin_email,
			'status'    => 'autoactivated', // assume it's active
			'level'     => 0,
			'locale'    => $default_locale,
			'ctry_ID'   => $default_country,
			'gender'    => 'M',
			'group_ID'  => NULL,
			'org_IDs'   => NULL, // array of organization IDs
			'org_roles' => NULL, // array of organization roles
			'org_priorities' => NULL, // array of organization priorities
			'fields'    => NULL, // array of additional user fields
			'datecreated' => $timestamp++
		), $params );

	$GroupCache = & get_GroupCache();
	$Group = $GroupCache->get_by_ID( $params['group_ID'], false, false );
	if( ! $Group )
	{
		$Messages->add( sprintf( TB_('Cannot create demo user "%s" because User Group #%d was not found.'), $params['login'], $params['group_ID'] ), 'error' );
		return false;
	}

	$User = new User();
	$User->set( 'login', $params['login'] );
	$User->set( 'firstname', $params['firstname'] );
	$User->set( 'lastname', $params['lastname'] );
	$User->set_password( $params['pass'] );
	$User->set_email( $params['email'] );
	$User->set( 'status', $params['status'] );
	$User->set( 'level', $params['level'] );
	$User->set( 'locale', $params['locale'] );
	if( !empty( $params['ctry_ID'] ) )
	{	// Set country
		$User->set( 'ctry_ID', $params['ctry_ID'] );
	}
	$User->set( 'gender', $params['gender'] );
	$User->set_Group( $Group );
	//$User->set_datecreated( $params['datecreated'] );
	$User->set_datecreated( time() ); // Use current time temporarily, we'll update these later

	if( ! $User->dbinsert( false ) )
	{	// Don't continue if user creating has been failed
		return false;
	}

	// Update user_created_datetime using FROM_UNIXTIME to prevent invalid datetime values during DST spring forward - fall back
	$DB->query( 'UPDATE T_users SET user_created_datetime = FROM_UNIXTIME('.$params['datecreated'].') WHERE user_login = '.$DB->quote( $params['login'] ) );

	if( ! empty( $params['org_IDs'] ) )
	{	// Add user to organizations:
		$User->update_organizations( $params['org_IDs'], $params['org_roles'], $params['org_priorities'], true );
	}

	if( ! empty( $params['fields'] ) )
	{	// Additional user fields
		global $DB;
		$fields_SQL = new SQL();
		$fields_SQL->SELECT( 'ufdf_ID, ufdf_code' );
		$fields_SQL->FROM( 'T_users__fielddefs' );
		$fields_SQL->WHERE( 'ufdf_code IN ( '.$DB->quote( array_keys( $params['fields'] ) ).' )' );
		$fields = $DB->get_assoc( $fields_SQL->get() );
		$user_field_records = array();
		foreach( $fields as $field_ID => $field_code )
		{
			if( ! isset( $params['fields'][ $field_code ] ) )
			{	// Skip wrong field:
				continue;
			}

			if( is_string( $params['fields'][ $field_code ] ) )
			{
				$params['fields'][ $field_code ] = array( $params['fields'][ $field_code ] );
			}

			foreach( $params['fields'][ $field_code ] as $field_value )
			{	// SQL record for each field value
				$user_field_records[] = '( '.$User->ID.', '.$field_ID.', '.$DB->quote( $field_value ).' )';
			}
		}
		if( count( $user_field_records ) )
		{	// Insert all user fields by single SQL query
			$DB->query( 'INSERT INTO T_users__fields ( uf_user_ID, uf_ufdf_ID, uf_varchar ) VALUES '
				.implode( ', ', $user_field_records ) );
		}
	}

	return $User;
}


/**
 * Associate a profile picture with a user.
 *
 * @param object User
 * @param string File name, NULL to use user login as file name
 */
function assign_profile_picture( & $User, $login = NULL )
{
	$File = new File( 'user', $User->ID, ( is_null( $login ) ? $User->login : $login ).'.jpg' );

	if( ! $File->exists() )
	{	// Don't assign if default user avatar doesn't exist on disk:
		return;
	}

	// Load meta data AND MAKE SURE IT IS CREATED IN DB:
	$File->load_meta( true );
	$User->set( 'avatar_file_ID', $File->ID );
	$User->dbupdate();

	// Set link between user and avatar file
	$LinkOwner = new LinkUser( $User );
	$File->link_to_Object( $LinkOwner );
}


/**
 * Assign secondary groups to user
 *
 * @param integer User ID
 * @param array IDs of groups
 */
function assign_secondary_groups( $user_ID, $secondary_group_IDs )
{
	if( empty( $secondary_group_IDs ) )
	{	// Nothing to assign, Exit here:
		return;
	}

	global $DB;

	$DB->query( 'INSERT INTO T_users__secondary_user_groups ( sug_user_ID, sug_grp_ID )
			VALUES ( '.$user_ID.', '.implode( ' ), ( '.$user_ID.', ', $secondary_group_IDs ).' )',
			'Assign secondary groups ('.implode( ', ', $secondary_group_IDs ).') to User #'.$user_ID );
}


/**
 * Create a demo organization
 *
 * @param integer Owner ID
 * @param string Demo organization name
 * @param boolean Add current user to the demo organization
 * @return object Created organization
 */
function create_demo_organization( $owner_ID, $org_name = 'Company XYZ', $add_current_user = true )
{
	global $DB, $Messages, $current_User;

	// Check if our sample organization already exists
	$demo_org_ID = NULL;
	$OrganizationCache = & get_OrganizationCache();
	$SQL = $OrganizationCache->get_SQL_object( 'Check if our sample organization already exists' );
	$SQL->WHERE_and( 'org_name = '.$DB->quote( $org_name ) );

	$db_row = $DB->get_row( $SQL );
	if( $db_row )
	{
		$demo_org_ID = $db_row->org_ID;
		$Organization = & $OrganizationCache->get_by_ID( $demo_org_ID );
	}
	else
	{	// Sample organization does not exist, let's create one
		$Organization = new Organization();
		$Organization->set( 'owner_user_ID', $owner_ID );
		$Organization->set( 'name', $org_name );
		$Organization->set( 'url', 'http://b2evolution.net/' );
		if( $Organization->dbinsert() )
		{
			$demo_org_ID = $Organization->ID;
			$Messages->add_to_group( sprintf( TB_('The sample organization %s has been created.'), $org_name ), 'success', TB_('Demo contents').':' );
		}
		else
		{
			$Messages->add_to_group( sprintf( TB_('Unable to create sample organization %s.'), '"'.$org_name.'"' ), 'error', TB_('Demo contents').':' );
			return false;
		}
	}

	// Add current user to the demo organization
	if( $add_current_user && $demo_org_ID && isset( $current_User ) )
	{
		// Get current user's organization data
		$org_roles = array();
		$org_priorities = array();
		$org_data = $current_User->get_organizations_data();
		if( isset( $org_data[ $demo_org_ID ] ) )
		{
			$org_roles = array( $org_data[ $demo_org_ID ]['role'] );
			$org_priorities = array( $org_data[ $demo_org_ID ]['priority'] );
		}
		$current_User->update_organizations( array( $demo_org_ID ), $org_roles, $org_priorities, true );
	}

	return $Organization;
}


/**
 * Returns list  of valid demo users
 *
 * @return array Array of demo users with default settings
 */
function get_demo_users_defaults()
{
	return array(
		'admin' => array(
				'login'     => 'admin',
				'firstname' => 'Johnny',
				'lastname'  => 'Admin',
				'level'     => 10, // NOTE: these levels define the order of display in the Organization members widget
				'gender'    => 'M',
				'group'     => 'Administrators',
				'org_IDs'   => '#',
				'org_roles' => array( 'King of Spades' ),
				'org_priorities' => array( 0 ),
				'fields'    => array(
						'microbio' => 'I am the demo administrator of this site.'."\n".'I love having so much power!',
						'website'  => 'http://b2evolution.net/',
						'twitter'  => 'https://twitter.com/b2evolution/',
						'facebook' => 'https://www.facebook.com/b2evolution',
						'linkedin' => 'https://www.linkedin.com/company/b2evolution-net',
						'github'   => 'https://github.com/b2evolution/b2evolution',
					)
			),
		'mary' => array(
				'login'     => 'mary',
				'firstname' => 'Mary',
				'lastname'  => 'Wilson',
				'level'     => 4, // NOTE: these levels define the order of display in the Organization members widget
				'gender'    => 'F',
				'group'     => 'Moderators',
				'org_IDs'   => '#',
				'org_roles' => array( 'Queen of Hearts' ),
				'fields'    => array(
						'microbio' => 'I am a demo moderator for this site.'."\n".'I love it when things are neat!',
						'website'  => 'http://b2evolution.net/',
						'twitter'  => 'https://twitter.com/b2evolution/',
						'facebook' => 'https://www.facebook.com/b2evolution',
						'linkedin' => 'https://www.linkedin.com/company/b2evolution-net',
						'github'   => 'https://github.com/b2evolution/b2evolution',
					),
			),
		'jay' => array(
				'login'     => 'jay',
				'firstname' => 'Jay',
				'lastname'  => 'Parker',
				'level'     => 3, // NOTE: these levels define the order of display in the Organization members widget
				'gender'    => 'M',
				'group'     => 'Moderators',
				'org_IDs'   => '#',
				'org_roles' => array( 'The Artist' ),
				'fields'    => array(
						'microbio' => 'I am a demo moderator for this site.'."\n".'I like to keep things clean!',
						'website'  => 'http://b2evolution.net/',
						'twitter'  => 'https://twitter.com/b2evolution/',
						'facebook' => 'https://www.facebook.com/b2evolution',
						'linkedin' => 'https://www.linkedin.com/company/b2evolution-net',
						'github'   => 'https://github.com/b2evolution/b2evolution',
					),
			),
		'dave' => array(
				'login'     => 'dave',
				'firstname' => 'David',
				'lastname'  => 'Miller',
				'level'     => 2, // NOTE: these levels define the order of display in the Organization members widget
				'gender'    => 'M',
				'group'     => 'Editors',
				'org_IDs'   => '#',
				'org_roles' => array( 'The Writer' ),
				'fields'    => array(
						'microbio' => 'I\'m a demo author.'."\n".'I like to write!',
						'website'  => 'http://b2evolution.net/',
						'twitter'  => 'https://twitter.com/b2evolution/',
						'facebook' => 'https://www.facebook.com/b2evolution',
						'linkedin' => 'https://www.linkedin.com/company/b2evolution-net',
						'github'   => 'https://github.com/b2evolution/b2evolution',
					),
			),
		'paul' => array(
				'login'     => 'paul',
				'firstname' => 'Paul',
				'lastname'  => 'Jones',
				'level'     => 1, // NOTE: these levels define the order of display in the Organization members widget
				'gender'    => 'M',
				'group'     => 'Editors',
				'org_IDs'   => '#',
				'org_roles' => array( 'The Thinker' ),
				'fields'    => array(
						'microbio' => 'I\'m a demo author.'."\n".'I like to think before I write ;)',
						'website'  => 'http://b2evolution.net/',
						'twitter'  => 'https://twitter.com/b2evolution/',
						'facebook' => 'https://www.facebook.com/b2evolution',
						'linkedin' => 'https://www.linkedin.com/company/b2evolution-net',
						'github'   => 'https://github.com/b2evolution/b2evolution',
					),
			),
		'larry' => array(
				'login'     => 'larry',
				'firstname' => 'Larry',
				'lastname'  => 'Smith',
				'level'     => 0,
				'gender'    => 'M',
				'group'     => 'Normal Users',
				'fields'    => array(
						'microbio' => 'Hi there!',
					),
			),
		'kate' => array(
				'login'     => 'kate',
				'firstname' => 'Kate',
				'lastname'  => 'Adams',
				'level'     => 0,
				'gender'    => 'F',
				'group'     => 'Normal Users',
				'fields'    => array(
						'microbio' => 'Just me!',
					),
			),
		);
}


/**
 * Get all available demo users
 *
 * @param boolean Create the demo users if they do not exist
 * @param boolean Display ouput
 * @param array Error messages
 * @return array Array of available demo users indexed by login
 */
function get_demo_users( $create = false, $output = true, &$error_messages = NULL )
{
	$demo_users = get_demo_users_defaults();
	$demo_users_logins = array_keys( $demo_users );

	$available_demo_users = array();
	foreach( $demo_users_logins as $demo_user_login )
	{
		$demo_User = get_demo_user( $demo_user_login, $create, $output, $error_messages );
		if( $demo_User )
		{
			$available_demo_users[$demo_user_login] = $demo_User;
		}
	}

	return $available_demo_users;
}


/**
 * Get demo user
 *
 * @param string User $login
 * @param boolean Create demo user if it does not exist
 * @param boolean Display output
 * @param array Error messages
 * @return mixed object Demo user if successful, false otherwise
 */
function get_demo_user( $login, $create = false, $output = true, &$error_messages = NULL )
{
	global $DB;
	global $user_timestamp;

	// Get list of demo users:
	$demo_users = get_demo_users_defaults();

	if( ! isset( $demo_users[$login] ) )
	{	// Specified login not included in the list of demo users:
		return false;
	}

	$UserCache = & get_UserCache();
	// Check if demo user is already created:
	$demo_user = & $UserCache->get_by_login( $login );

	$GroupCache = & get_GroupCache();
	if( isset( $demo_users[$login]['group'] )
			&& $user_default_Group = $GroupCache->get_by_name( $demo_users[$login]['group'], false, false ) )
	{	// Get default group ID:
		$group_ID = $user_default_Group->ID;
	}
	else
	{
		$group_ID = $GroupCache->get_by_name( 'Normal Users' );
	}

	$user_org_IDs = NULL;
	if( isset( $demo_users[$login]['org_IDs'] )  )
	{
		if( $demo_users[$login]['org_IDs'] == '#' )
		{	// Get first available organization:
			if( $organization_ID = $DB->get_var( 'SELECT org_ID FROM T_users__organization ORDER BY org_ID ASC LIMIT 1' ) )
			{
				$user_org_IDs = array( $organization_ID );
			}
		}
		elseif( is_array( $demo_users[$login]['org_IDs'] ) )
		{
			$user_org_IDs = $demo_users[$login]['org_IDs'];
		}
	}

	if( ! $demo_user && $create )
	{	// Demo user does not exist yet but we can create:
		if( $login == 'admin' && $admin_user = $UserCache->get_by_ID( 1, false, false ) )
		{	// Admin user must have been renamed, skip:
			return false;
		}

		if( $output )
		{
			task_begin( sprintf( TB_('Creating demo user %s...'), $login ) );
		}
		adjust_timestamp( $user_timestamp, 360, 1440, false );

		$user_defaults = array_merge( $demo_users[$login], array(
			'group_ID'    => $group_ID,
			'org_IDs'     => $user_org_IDs,
			'datecreated' => $user_timestamp,
		) );

		$demo_user = create_user( $user_defaults );
		if( $demo_user === false )
		{	// Cannot create demo user, exiting:
			$error_messages[] = sprintf( TB_('Unable to create demo user %s.'), '"'.$login.'"' );
			if( $output )
			{
				task_end( '<span class="text-danger">'.TB_('Failed').'.</span>' );
			}
			return false;
		}

		// Try to assign profile picture to demo user:
		assign_profile_picture( $demo_user );

		if( $demo_user )
		{	// Insert default user settings:
			$DB->query( 'INSERT INTO T_users__usersettings ( uset_user_ID, uset_name, uset_value )
				VALUES ( '.$demo_user->ID.', "created_fromIPv4", '.$DB->quote( ip2int( '127.0.0.1' ) ).' ),
				       ( '.$demo_user->ID.', "user_domain", "localhost" )' );
		}
		if( $output )
		{
			task_end();
		}
	}
	elseif( $demo_user )
	{
		if( ! $demo_user->get( 'avatar_file_ID' ) )
		{	// Demo user already exists but has avatar has not been set:
			assign_profile_picture( $demo_user );
		}

		if( isset( $user_defaults['org_IDs'] ) && isset( $user_defaults['org_roles'] ) )
		{
			$org_priorities = isset( $user_defaults['org_priorities'] ) ? $user_defaults['org_priorities'] : array();
			$demo_user->update_organizations( $user_org_IDs, $user_defaults['org_roles'], $org_priorities, true );
		}
	}

	return $demo_user;
}


/**
 * Create demo private messages
 */
function create_demo_messages()
{
	global $UserSettings, $DB, $now, $localtimenow;

	load_class( 'messaging/model/_thread.class.php', 'Thread' );
	load_class( 'messaging/model/_message.class.php', 'Message' );
	load_class( 'users/model/_usersettings.class.php', 'UserSettings' );
	$UserSettings = new UserSettings();
	$UserCache = & get_UserCache();

	$users_SQL = new SQL();
	$users_SQL->SELECT( 'user_ID, user_login' );
	$users_SQL->FROM( 'T_users' );
	$users_SQL->WHERE( 'NOT user_ID  = 1' );
	$users_SQL->ORDER_BY( 'user_ID' );
	$users = $DB->get_results( $users_SQL->get() );

	$demo_messages = array();
	for( $i = 0; $i < count( $users ); $i++ )
	{
		if( $i % 2 == 0 )
		{
			$author_ID = 1;
			$recipient_ID = $users[$i]->user_ID;
		}
		else
		{
			$author_ID = $users[$i]->user_ID;
			$recipient_ID = 1;
		}

		$author_User = & $UserCache->get_by_ID( $author_ID );
		$recipient_User = & $UserCache->get_by_ID( $recipient_ID );

		$loop_Thread = new Thread();
		$loop_Message = new Message();

		// Initial message
		$loop_Message->Thread = $loop_Thread;
		$loop_Message->Thread->set_param( 'datemodified', 'string', date( 'Y-m-d H:i:s', $localtimenow - 60 ) );
		$loop_Message->Thread->set( 'title', sprintf( TB_('Demo private conversation #%s'), $i + 1 ) );
		$loop_Message->Thread->recipients_list = array( $recipient_ID );
		$loop_Message->set( 'author_user_ID', $author_ID );
		$loop_Message->creator_user_ID = $author_ID;
		$loop_Message->set( 'text', sprintf( TB_('This is a demo private message to %s.'), $recipient_User->login ) );

		$DB->begin();
		$conversation_saved = false;
		if( $loop_Message->Thread->dbinsert() )
		{
			$loop_Message->set_param( 'thread_ID', 'integer', $loop_Message->Thread->ID );
			if( $loop_Message->dbinsert() )
			{
				if( $loop_Message->dbinsert_threadstatus( $loop_Message->Thread->recipients_list ) )
				{
					if( $loop_Message->dbinsert_contacts( $loop_Message->Thread->recipients_list ) )
					{
						if( $loop_Message->dbupdate_last_contact_datetime() )
						{
							$conversation_saved = true;
							$demo_messages[] = $loop_Message;
						}
					}
				}
			}
		}

		if( $conversation_saved )
		{
			$conversation_saved = false;

			// Reply message
			$loop_reply_Message = new Message();
			$loop_reply_Message->Thread = $loop_Thread;
			$loop_reply_Message->set( 'author_user_ID', $recipient_ID );
			$loop_reply_Message->creator_user_ID = $author_ID;
			$loop_reply_Message->set( 'text', sprintf( TB_('This is a demo private reply to %s.'), $author_User->login ) );
			$loop_reply_Message->set_param( 'thread_ID', 'integer', $loop_reply_Message->Thread->ID );

			if( $loop_reply_Message->dbinsert() )
			{
				// Mark reply message as unread by initiator
				$sql = 'UPDATE T_messaging__threadstatus
						SET tsta_first_unread_msg_ID = '.$loop_reply_Message->ID.'
						WHERE tsta_thread_ID = '.$loop_reply_Message->Thread->ID.'
							AND tsta_user_ID = '.$author_ID.'
							AND tsta_first_unread_msg_ID IS NULL';
				$DB->query( $sql, 'Insert thread statuses' );

				// Mark all messages as read by recipient
				$sql = 'UPDATE T_messaging__threadstatus
						SET tsta_first_unread_msg_ID = NULL
						WHERE tsta_thread_ID = '.$loop_reply_Message->Thread->ID.'
							AND tsta_user_ID = '.$recipient_ID;
				$DB->query( $sql, 'Insert thread statuses' );

				// check if contact pairs between sender and recipients exists
				$recipient_list = $loop_reply_Message->Thread->load_recipients();
				// remove author user from recipient list
				$recipient_list = array_diff( $recipient_list, array( $loop_reply_Message->author_user_ID ) );
				// insert missing contact pairs if required
				if( $loop_reply_Message->dbinsert_contacts( $recipient_list ) )
				{
					if( $loop_reply_Message->dbupdate_last_contact_datetime() )
					{
						$DB->commit();
						$conversation_saved = true;
						$demo_messages[] = $loop_reply_Message;
					}
				}
			}
		}

		if( ! $conversation_saved )
		{
			$DB->rollback();
		}
	}

	return $demo_messages;
}

/**
 * This is called only for fresh installs and fills the tables with
 * demo/tutorial things.
 *
 * @param array Array of user objects
 * @param boolean True to create users for the demo content
 * @return integer Number of collections created
 */
function create_demo_contents( $demo_users = array(), $use_demo_users = true, $initial_install = true )
{
	global $current_User, $DB, $Settings;

	// Global exception handler function
	function demo_content_error_handler( $errno, $errstr, $errfile, $errline )
	{	// handle only E_USER_NOTICE
		if( $errno == E_USER_NOTICE )
		{
			echo get_install_format_text_and_log( '<span class="text-warning"><evo:warning>'.$errstr.'</evo:warning></span> ' );
		}
	}

	// Set global exception handler
	set_error_handler( "demo_content_error_handler" );

	$mary_moderator_ID = isset( $demo_users['mary'] ) ? $demo_users['mary']->ID : $current_User->ID;
	$jay_moderator_ID  = isset( $demo_users['jay'] ) ? $demo_users['jay']->ID : $current_User->ID;
	$dave_blogger_ID   = isset( $demo_users['dave'] ) ? $demo_users['dave']->ID : $current_User->ID;
	$paul_blogger_ID   = isset( $demo_users['paul'] ) ? $demo_users['paul']->ID : $current_User->ID;
	$larry_user_ID     = isset( $demo_users['larry'] ) ? $demo_users['larry']->ID : $current_User->ID;
	$kate_user_ID      = isset( $demo_users['kate'] ) ? $demo_users['kate']->ID : $current_User->ID;

	load_class( 'collections/model/_blog.class.php', 'Blog' );
	load_class( 'files/model/_file.class.php', 'File' );
	load_class( 'files/model/_filetype.class.php', 'FileType' );
	load_class( 'links/model/_link.class.php', 'Link' );
	load_funcs( 'widgets/_widgets.funcs.php' );

	$create_sample_contents = param( 'create_sample_contents', 'string', '' );
	$install_collection_minisite = 0;
	$install_collection_home     = 0;
	$install_collection_bloga    = 0;
	$install_collection_blogb    = 0;
	$install_collection_photos   = 0;
	$install_collection_forums   = 0;
	$install_collection_manual   = 0;
	$install_collection_tracker  = 0;
	$site_skins_setting          = 0;
	switch( $create_sample_contents )
	{
		case 'full':
			// Install all collections except of "Mini-Site":
			// (may be used from auto install script)
			$install_collection_home    = 1;
			$install_collection_bloga   = 1;
			$install_collection_blogb   = 1;
			$install_collection_photos  = 1;
			$install_collection_forums  = 1;
			$install_collection_manual  = 1;
			$install_collection_tracker = 1;
			$site_skins_setting         = 1;
			break;

		case 'minisite':
			// Install "Mini-Site" from auto install script:
			$install_collection_minisite = 1;
			break;

		case 'blog-a':
			// Install "Sample Blog A (Public)" from auto install script:
			$install_collection_bloga = 1;
			break;

		case 'blog-b':
			// Install "Sample Blog B (Private)" from auto install script:
			$install_collection_blogb = 1;
			break;

		case 'photos':
			// Install "Photo Albums" from auto install script:
			$install_collection_photos = 1;
			break;

		case 'forums':
			// Install "Forums" from auto install script:
			$install_collection_forums = 1;
			break;

		case 'manual':
			// Install "Online Manual" from auto install script:
			$install_collection_manual = 1;
			break;

		case 'tracker':
			// Install "Tracker" from auto install script:
			$install_collection_tracker = 1;
			break;

		default:
			// Install collections depending on the selected options "Create a demo website" on the submitted form:
			$demo_content_type = param( 'demo_content_type', 'string', NULL );
			switch( $demo_content_type )
			{
				case 'minisite':
					$install_collection_minisite = 1;
					break;

				case 'standard_site':
					$standard_collection = param( 'standard_collection', 'string', '' );
					$install_collection_bloga   = ( $standard_collection == 'a' );
					$install_collection_blogb   = ( $standard_collection == 'b' );
					$install_collection_photos  = ( $standard_collection == 'photos' );
					$install_collection_forums  = ( $standard_collection == 'forums' );
					$install_collection_manual  = ( $standard_collection == 'manual' );
					$install_collection_tracker = ( $standard_collection == 'group' );
					break;

				default: // complex_site
					$collections = param( 'collections', 'array:string', array() );
					$install_collection_home    = in_array( 'home', $collections );
					$install_collection_bloga   = in_array( 'a', $collections );
					$install_collection_blogb   = in_array( 'b', $collections );
					$install_collection_photos  = in_array( 'photos', $collections );
					$install_collection_forums  = in_array( 'forums', $collections );
					$install_collection_manual  = in_array( 'manual', $collections );
					$install_collection_tracker = in_array( 'group', $collections );
					$site_skins_setting         = 1;
			}
	}

	if( ! $install_collection_minisite &&
	    ! $install_collection_home &&
	    ! $install_collection_bloga &&
	    ! $install_collection_blogb &&
	    ! $install_collection_photos &&
	    ! $install_collection_forums &&
	    ! $install_collection_manual &&
	    ! $install_collection_tracker )
	{	// Don't try to install demo content if no collection is selected:
		return 0;
	}

	if( $demo_content_type == 'complex_site' || $create_sample_contents == 'full' )
	{
		task_begin( TB_('Creating default sections...') );
		$SectionCache = & get_SectionCache();

		$sections = array();
		$sections['No Section'] = array( 'owner_ID' => 1, 'order' => 1 );
		if( $install_collection_home )
		{
			$sections['Home'] = array( 'owner_ID' => 1, 'order' => 2 );
		}
		if( $install_collection_bloga || $install_collection_blogb )
		{
			$sections['Blogs'] = array( 'owner_ID' => $jay_moderator_ID, 'order' => 3 );
		}
		if( $install_collection_photos )
		{
			$sections['Photos'] = array( 'owner_ID' => $dave_blogger_ID, 'order' => 4 );
		}
		if( $install_collection_forums || $install_collection_tracker )
		{
			$sections['Forums'] = array( 'owner_ID' => $paul_blogger_ID, 'order' => 5 );
		}
		if( $install_collection_manual )
		{
			$sections['Manual'] = array( 'owner_ID' => $dave_blogger_ID, 'order' => 6 );
		}

		$section_error_messages = array();
		foreach( $sections as $section_name => $section_data )
		{
			if( $loop_Section = $SectionCache->get_by_name( $section_name, false, false ) )
			{
				$sections[$section_name]['ID'] = $loop_Section->ID;
			}
			else
			{
				$new_Section = new Section();
				$new_Section->set( 'name', $section_name );
				$new_Section->set( 'order', $section_data['order'] );
				$new_Section->set( 'owner_user_ID', $section_data['owner_ID'] );
				$insert_section_result = $new_Section->dbsave();
				if( $insert_section_result )
				{
					$sections[$section_name]['ID'] = $new_Section->ID;
				}
				else
				{
					$section_error_messages[] = sprintf( TB_('Failed to create %s section'), $section_name );
				}
			}
		}

		if( $section_error_messages )
		{
			task_errors( $section_error_messages );
		}
		else
		{
			task_end();
		}
	}

	// Create demo polls:
	// (global $demo_poll_ID may be used in default widgets e-g for collection "Blog B")
	global $demo_poll_ID;
	task_begin( TB_('Creating default polls...') );
	$demo_poll_ID = create_demo_poll();
	if( empty( $demo_poll_ID ) )
	{
		task_end( '<span class="text-danger">'.TB_('Failed').'.</span>' );
	}
	else
	{
		task_end();
	}


	// Number of demo collections created:
	$collection_created = 0;

	// Use this var to shift the posts of the collections in time below:
	$timeshift = 0;

	// Initialize for setup default widgets per collection:
	$BlogCache = & get_BlogCache();

	if( $install_collection_home )
	{	// Install Home blog
		$coll_error_messages = array();
		task_begin( sprintf( TB_('Creating %s collection...'), TB_('Home') ) );
		$section_ID = isset( $sections['Home']['ID'] ) ? $sections['Home']['ID'] : 1;
		if( $blog_ID = create_demo_collection( 'main', $jay_moderator_ID, $use_demo_users, $timeshift, $section_ID, $coll_error_messages ) )
		{
			if( $initial_install )
			{
				if( is_callable( 'update_install_progress_bar' ) )
				{
					update_install_progress_bar();
				}
			}
			elseif( $Blog = & $BlogCache->get_by_ID( $blog_ID, false, false ) )
			{	// Insert basic widgets:
				$Blog->setup_default_widgets();
			}

			$collection_created++;
			if( $coll_error_messages )
			{
				task_errors( $coll_error_messages );
			}
			else
			{
				task_end();
			}
		}
		else
		{
			task_end( '<span class="text-danger">'.TB_('Failed').'.</span>' );
		}
	}

	if( $install_collection_bloga )
	{	// Install Blog A
		$coll_error_messages = array();
		$timeshift += 86400;
		task_begin( sprintf( TB_('Creating %s collection...'), TD_('Blog A') ) );
		$section_ID = isset( $sections['Blogs']['ID'] ) ? $sections['Blogs']['ID'] : 1;
		if( $blog_ID = create_demo_collection( 'blog_a', $jay_moderator_ID, $use_demo_users, $timeshift, $section_ID, $coll_error_messages ) )
		{
			if( $initial_install )
			{
				if( is_callable( 'update_install_progress_bar' ) )
				{
					update_install_progress_bar();
				}
			}
			elseif( $Blog = & $BlogCache->get_by_ID( $blog_ID, false, false ) )
			{	// Insert basic widgets:
				$Blog->setup_default_widgets();
			}
			$collection_created++;
			if( $coll_error_messages )
			{
				task_errors( $coll_error_messages );
			}
			else
			{
				task_end();
			}
		}
		else
		{
			task_end( '<span class="text-danger">'.TB_('Failed').'</span>' );
		}
	}

	if( $install_collection_blogb )
	{	// Install Blog B
		$coll_error_messages = array();
		$timeshift += 86400;
		task_begin( sprintf( TB_('Creating %s collection...'), TD_('Blog B') ) );
		$section_ID = isset( $sections['Blogs']['ID'] ) ? $sections['Blogs']['ID'] : 1;
		if( $blog_ID = create_demo_collection( 'blog_b', $paul_blogger_ID, $use_demo_users, $timeshift, $section_ID, $coll_error_messages ) )
		{
			if( $initial_install )
			{
				if( is_callable( 'update_install_progress_bar' ) )
				{
					update_install_progress_bar();
				}
			}
			elseif( $Blog = & $BlogCache->get_by_ID( $blog_ID, false, false ) )
			{	// Insert basic widgets:
				$Blog->setup_default_widgets();
			}
			$collection_created++;
			if( $coll_error_messages )
			{
				task_errors( $coll_error_messages );
			}
			else
			{
				task_end();
			}
		}
		else
		{
			task_end( '<span class="text-danger">'.TB_('Failed').'.</span>' );
		}
	}

	if( $install_collection_photos )
	{	// Install Photos blog
		$coll_error_messages = array();
		$timeshift += 86400;
		task_begin( sprintf( TB_('Creating %s collection...'), TD_('Photos') ) );
		$section_ID = isset( $sections['Photos']['ID'] ) ? $sections['Photos']['ID'] : 1;
		if( $blog_ID = create_demo_collection( 'photo', $dave_blogger_ID, $use_demo_users, $timeshift, $section_ID, $coll_error_messages ) )
		{
			if( $initial_install )
			{
				if( is_callable( 'update_install_progress_bar' ) )
				{
					update_install_progress_bar();
				}
			}
			elseif( $Blog = & $BlogCache->get_by_ID( $blog_ID, false, false ) )
			{	// Insert basic widgets:
				$Blog->setup_default_widgets();
			}
			$collection_created++;
			if( $coll_error_messages )
			{
				task_errors( $coll_error_messages );
			}
			else
			{
				task_end();
			}
		}
		else
		{
			task_end( '<span class="text-danger">'.TB_('Failed').'.</span>' );
		}
	}

	if( $install_collection_forums )
	{	// Install Forums blog
		$coll_error_messages = array();
		$timeshift += 86400;
		task_begin( sprintf( TB_('Creating %s collection...'), TD_('Forums') ) );
		$section_ID = isset( $sections['Forums']['ID'] ) ? $sections['Forums']['ID'] : 1;
		if( $blog_ID = create_demo_collection( 'forum', $paul_blogger_ID, $use_demo_users, $timeshift, $section_ID, $coll_error_messages ) )
		{
			if( $initial_install )
			{
				if( is_callable( 'update_install_progress_bar' ) )
				{
					update_install_progress_bar();
				}
			}
			elseif( $Blog = & $BlogCache->get_by_ID( $blog_ID, false, false ) )
			{	// Insert basic widgets:
				$Blog->setup_default_widgets();
			}
			$collection_created++;
			if( $coll_error_messages )
			{
				task_errors( $coll_error_messages );
			}
			else
			{
				task_end();
			}
		}
		else
		{
			task_end( '<span class="text-danger">'.TB_('Failed').'.</span>' );
		}
	}

	if( $install_collection_manual )
	{	// Install Manual blog
		$coll_error_messages = array();
		$timeshift += 86400;
		task_begin( sprintf( TB_('Creating %s collection...'), TD_('Manual') ) );
		$section_ID = isset( $sections['Manual']['ID'] ) ? $sections['Manual']['ID'] : 1;
		if( $blog_ID = create_demo_collection( 'manual', $dave_blogger_ID, $use_demo_users, $timeshift, $section_ID, $coll_error_messages ) )
		{
			if( $initial_install )
			{
				if( is_callable( 'update_install_progress_bar' ) )
				{
					update_install_progress_bar();
				}
			}
			elseif( $Blog = & $BlogCache->get_by_ID( $blog_ID, false, false ) )
			{	// Insert basic widgets:
				$Blog->setup_default_widgets();
			}
			$collection_created++;
			if( $coll_error_messages )
			{
				task_errors( $coll_error_messages );
			}
			else
			{
				task_end();
			}
		}
		else
		{
			task_end( '<span class="text-danger">'.TB_('Failed').'.</span>' );
		}
	}

	if( $install_collection_tracker )
	{	// Install Tracker blog
		$coll_error_messages = array();
		$timeshift += 86400;
		task_begin( sprintf( TB_('Creating %s collection...'), TD_('Tracker') ) );
		$section_ID = isset( $sections['Forums']['ID'] ) ? $sections['Forums']['ID'] : 1;
		if( $blog_ID = create_demo_collection( 'group', $jay_moderator_ID, $use_demo_users, $timeshift, $section_ID, $coll_error_messages ) )
		{
			if( $initial_install )
			{
				if( is_callable( 'update_install_progress_bar' ) )
				{
					update_install_progress_bar();
				}
			}
			elseif( $Blog = & $BlogCache->get_by_ID( $blog_ID, false, false ) )
			{	// Insert basic widgets:
				$Blog->setup_default_widgets();
			}
			$collection_created++;
			if( $coll_error_messages )
			{
				task_errors( $coll_error_messages );
			}
			else
			{
				task_end();
			}
		}
		else
		{
			task_end( '<span class="text-danger">'.TB_('Failed').'.</span>' );
		}
	}

	if( $install_collection_minisite )
	{	// Install Mini-site collection
		$coll_error_messages = array();
		$timeshift += 86400;
		task_begin( sprintf( TB_('Creating %s collection...'), TD_('Mini-Site') ) );
		if( $blog_ID = create_demo_collection( 'minisite', $jay_moderator_ID, $use_demo_users, $timeshift, 1, $coll_error_messages ) )
		{
			if( $initial_install )
			{
				if( is_callable( 'update_install_progress_bar' ) )
				{
					update_install_progress_bar();
				}
			}
			elseif( $Blog = & $BlogCache->get_by_ID( $blog_ID, false, false ) )
			{	// Insert basic widgets:
				$Blog->setup_default_widgets();
			}
			$collection_created++;
			if( $coll_error_messages )
			{
				task_errors( $coll_error_messages );
			}
			else
			{
				task_end();
			}
		}
		else
		{
			task_end( '<span class="text-danger">'.TB_('Failed').'.</span>' );
		}
	}

	// Install default shared widgets:
	global $installed_default_shared_widgets;
	task_begin( TB_('Installing default shared widgets...') );
	insert_shared_widgets( 'normal' );
	task_end();
	$installed_default_shared_widgets = true;

	// Setting default login and default messaging collection:
	task_begin( TB_('Setting default login and default messaging collection...') );
	if( $demo_content_type == 'minisite' )
	{
		$Settings->set( 'login_blog_ID', 0 );
		$Settings->set( 'msg_blog_ID', 0 );
		$Settings->set( 'info_blog_ID', 0 );
		$Settings->dbupdate();
	}
	else
	{
		$BlogCache = & get_BlogCache();
		//$BlogCache->load_where( 'blog_type = "main" )' );
		if( $first_Blog = & $BlogCache->get_first() )
		{	// Set first blog as default login and default messaging collection
			$Settings->set( 'login_blog_ID', $first_Blog->ID );
			$Settings->set( 'msg_blog_ID', $first_Blog->ID );
			$Settings->set( 'info_blog_ID', $first_Blog->ID );
			$Settings->dbupdate();
		}
	}
	if( $initial_install )
	{
		if( is_callable( 'update_install_progress_bar' ) )
		{
			update_install_progress_bar();
		}
	}
	task_end();

	task_begin( TB_('Set setting for site skins...') );
	$Settings->set( 'site_skins_enabled', $site_skins_setting );
	$Settings->dbupdate();
	task_end();

	if( $initial_install )
	{
		if( $install_test_features )
		{
			echo_install_log( 'TEST FEATURE: Creating fake hit statistics' );
			task_begin( TB_('Creating fake hit statistics...') );
			load_funcs('sessions/model/_hitlog.funcs.php');
			load_funcs('_core/_url.funcs.php');
			$insert_data_count = generate_hit_stat(10, 0, 5000);
			echo sprintf( '%d test hits are added.', $insert_data_count );
			task_end();
		}

		/*
		// Note: we don't really need this any longer, but we might use it for a better default setup later...
		echo 'Creating default user/blog permissions... ';
		// Admin for blog A:
		$query = "INSERT INTO T_coll_user_perms( bloguser_blog_ID, bloguser_user_ID, bloguser_ismember,
								bloguser_perm_poststatuses, bloguser_perm_delpost, bloguser_perm_comments,
								bloguser_perm_meta_comment, bloguser_perm_cats, bloguser_perm_properties,
								bloguser_perm_media_upload, bloguser_perm_media_browse, bloguser_perm_media_change )
							VALUES
								( $blog_a_ID, ".$User_Demo->ID.", 1,
								'published,deprecated,protected,private,draft', 1, 1, 1, 0, 0, 1, 1, 1 )";
		$DB->query( $query );
		echo "OK.<br />\n";
		*/

		// Allow all modules to create their own demo contents:
		modules_call_method( 'create_demo_contents' );

		// Set default locations for each post in test mode installation
		create_default_posts_location();

		//install_basic_widgets( $new_db_version );

		load_funcs( 'tools/model/_system.funcs.php' );
		system_init_caches( true, true ); // Outputs messages
	}

	restore_error_handler();

	return $collection_created;
}


/**
 * Create default email lists
 *
 * @return integer Number of new created email lists
 */
function create_default_newsletters()
{
	global $DB;

	task_begin( TB_('Creating demo email lists...') );

	// Insert default newsletters:
	$created_lists_num = $DB->query( 'INSERT INTO T_email__newsletter ( enlt_name, enlt_label, enlt_order, enlt_owner_user_ID )
		VALUES ( "News", "Send me news about this site.", 1, 1 ),
		       ( "Promotions", "I want to receive ADs that may be relevant to my interests.", 2, 1 )' );

	// Insert default subscriptions for each user on first newsletter:
	$DB->query( 'REPLACE INTO T_email__newsletter_subscription ( enls_user_ID, enls_enlt_ID )
		SELECT user_ID, 1 FROM T_users' );

	task_end();

	return $created_lists_num;
}


/**
 * Create default email campaigns
 */
function create_default_email_campaigns()
{
	global $DB, $Settings, $baseurl;

	task_begin( TB_('Creating demo email campaigns...') );

	load_class( 'email_campaigns/model/_emailcampaign.class.php', 'EmailCampaign' );
	load_funcs( 'email_campaigns/model/_emailcampaign.funcs.php' );

	$email_campaigns = array(
		array(
			'name' => TD_('Markdown Example'),
			'text' => TD_('Heading
=======

Sub-heading
-----------

### H3 header

#### H4 header ####

> Email-style angle brackets
> are used for blockquotes.

> > And, they can be nested.

> ##### Headers in blockquotes
>
> * You can quote a list.
> * Etc.

[This is a link](http://b2evolution.net/) if Links are turned on in the markdown plugin settings

Paragraphs are separated by a blank line.

    This is a preformatted
    code block.

Text attributes *Italic*, **bold**, `monospace`.

Shopping list:

* apples
* oranges
* pears

The rain---not the reign---in Spain.').
"\n".
TD_('Button examples:
[button]This is a button[/button]
[like]I like this[/like] [dislike]I don\'t like this[/dislike]
[cta:1:info]Call to action 1 info button[/cta] [cta:2:warning]Call to action 2 warning button[/cta] [cta:3:default]Call to action 3 default button[/cta]
[cta:1:link]Call to action 1 link only[/cta]'),
		),
		array(
			'name' => TD_('Another example'),
			'text' => sprintf( TD_('Hello %s!'), '$firstname_and_login$' )."\r\n\r\n".TD_('Here are some news...'),
		),
		array(
			'name'  => TD_('Welcome & Activate'),
			'title' => sprintf( TD_( 'Activate your account: %s' ), '$login$' ),
			'text'  => sprintf( TD_('Hello %s!'), '$username$' )."\r\n\r\n"
				.sprintf( TD_('You have recently registered a new account on %s .'), '<a href="'.$baseurl.'">'.$Settings->get( 'notification_short_name' ).'</a>' )."\r\n\r\n"
				.'<b style="color:#d00">'.TD_('You must activate this account by clicking below in order to be able to use all the site features.').'</b>'."\r\n\r\n"
				.TD_('Your login is: $login$')."\r\n\r\n"
				.TD_('Your email is: $email$')."\r\n\r\n"
				.'[activate:primary]'.TD_( 'Activate NOW' ).'[/activate]'
		),
	);

	$user_IDs = $DB->get_col( 'SELECT user_ID FROM T_users' );
	foreach( $email_campaigns as $email_campaign )
	{
		$EmailCampaign = new EmailCampaign();
		$EmailCampaign->set( 'enlt_ID', 1 );
		$EmailCampaign->set( 'name', $email_campaign['name'] );
		$EmailCampaign->set( 'email_title', isset( $email_campaign['title'] ) ? $email_campaign['title'] : $email_campaign['name'] );
		$EmailCampaign->set( 'email_defaultdest', $baseurl );
		$EmailCampaign->set( 'email_text', $email_campaign['text'] );

		if( $EmailCampaign->dbinsert() && ! empty( $user_IDs ) )
		{	// Add recipients after successfull email campaign creating,
			// only if we have found the users in DB:
			$EmailCampaign->add_recipients( $user_IDs );
		}
	}

	task_end();
}


/**
 * Create default automations
 */
function create_default_automations()
{
	global $DB;

	task_begin( TB_('Creating demo automations...') );

	//load_funcs( 'automations/model/_automation.funcs.php' );
	load_class( 'automations/model/_automation.class.php', 'Automation' );
	load_class( 'automations/model/_automationstep.class.php', 'AutomationStep' );

	$Automation = new Automation();
	$Automation->set( 'name', TD_('Sample Automation') );
	$Automation->set( 'owner_user_ID', 1 );
	$Automation->update_newsletters = true;
	$Automation->newsletters = array( array(
			'ID'        => 1,
			'autostart' => 1,
			'autoexit'  => 1,
		) );

	if( $Automation->dbinsert() )
	{	// Add steps after successfull creating of the automation:
		$AutomationStep = new AutomationStep();
		$AutomationStep->set( 'autm_ID', $Automation->ID );
		$AutomationStep->set( 'order', 1 );
		$AutomationStep->set( 'type', 'notify_owner' );
		$AutomationStep->set( 'info', '$login$ has reached step $step_number$ (ID: $step_ID$)'."\n".'in automation $automation_name$ (ID: $automation_ID$)' );
		$AutomationStep->set( 'yes_next_step_ID', 0 ); // Continue
		$AutomationStep->set( 'yes_next_step_delay', 86400 ); // 1 day
		$AutomationStep->set( 'error_next_step_ID', 1 ); // Loop
		$AutomationStep->set( 'error_next_step_delay', 14400 ); // 4 hours
		$AutomationStep->set_label();
		$AutomationStep->dbinsert();

		$AutomationStep = new AutomationStep();
		$AutomationStep->set( 'autm_ID', $Automation->ID );
		$AutomationStep->set( 'order', 2 );
		$AutomationStep->set( 'type', 'send_campaign' );
		$AutomationStep->set( 'info', '1' ); // Email Campaign ID
		$AutomationStep->set( 'yes_next_step_ID', 0 ); // Continue
		$AutomationStep->set( 'yes_next_step_delay', 259200 ); // 3 days
		$AutomationStep->set( 'no_next_step_ID', 0 ); // Continue
		$AutomationStep->set( 'no_next_step_delay', 0 ); // 0 seconds
		$AutomationStep->set( 'error_next_step_ID', 2 ); // Loop
		$AutomationStep->set( 'error_next_step_delay', 604800 ); // 7 days
		$AutomationStep->set_label();
		$AutomationStep->dbinsert();

		$AutomationStep = new AutomationStep();
		$AutomationStep->set( 'autm_ID', $Automation->ID );
		$AutomationStep->set( 'order', 3 );
		$AutomationStep->set( 'type', 'send_campaign' );
		$AutomationStep->set( 'info', '2' ); // Email Campaign ID
		$AutomationStep->set( 'yes_next_step_ID', 0 ); // Continue
		$AutomationStep->set( 'yes_next_step_delay', 259200 ); // 3 days
		$AutomationStep->set( 'no_next_step_ID', 0 ); // Continue
		$AutomationStep->set( 'no_next_step_delay', 0 ); // 0 seconds
		$AutomationStep->set( 'error_next_step_ID', 3 ); // Loop
		$AutomationStep->set( 'error_next_step_delay', 604800 ); // 7 days
		$AutomationStep->set_label();
		$AutomationStep->dbinsert();

		// Add users to this automation:
		$user_IDs = $DB->get_col( 'SELECT user_ID FROM T_users' );
		$Automation->add_users( $user_IDs );
	}

	task_end();
}


/**
 * Create demo emails data like lists, campaigns, automations
 *
 * @return integer Number of new created email lists
 */
function create_demo_emails()
{
	if( param( 'create_demo_email_lists', 'boolean', false ) )
	{
		evo_flush();
		$created_lists_num = create_default_newsletters();

		if( $created_lists_num > 0 )
		{	// Install other emails data only if at least one email list has been installed:
			if( param( 'create_demo_email_campaigns', 'boolean', false ) )
			{	// Install demo email campaigns:
				evo_flush();
				create_default_email_campaigns();
			}

			if( param( 'create_demo_automations', 'boolean', false ) )
			{	// Install demo automations:
				evo_flush();
				create_default_automations();
			}
		}
	}
	else
	{
		$created_lists_num = 0;
	}

	return $created_lists_num;
}


/**
 * Create a demo comment
 *
 * @param integer Item ID
 * @param array List of users as comment authors
 * @param string Comment status
 */
function create_demo_comment( $item_ID, $comment_users , $status = NULL, $comment_timestamp = NULL )
{
	global $DB, $now;

	if( empty( $status ) )
	{
		$ItemCache = & get_ItemCache();
		$commented_Item = $ItemCache->get_by_ID( $item_ID );
		$commented_Item->load_Blog();
		$status = $commented_Item->Blog->get_setting( 'new_feedback_status' );
	}

	// Get comment users
	if( $comment_users )
	{
		$comment_user = $comment_users[ rand( 0, count( $comment_users ) - 1 ) ];

		$user_ID = $comment_user->ID;
		$author = $comment_user->get( 'fullname' );
		$author_email = $comment_user->email;
		$author_email_url = $comment_user->url;
	}
	else
	{
		$user_ID = NULL;
		$author = TD_('Anonymous Demo User') ;
		$author_email = 'anonymous@example.com';
		$author_email_url = 'http://www.example.com';
	}

	// Restrict comment status by parent item:
	$Comment = new Comment();
	$Comment->set( 'item_ID', $item_ID );
	$Comment->set( 'status', $status );
	$Comment->restrict_status( true );
	$status = $Comment->get( 'status' );

	// Set demo content depending on status
	if( $status == 'published' )
	{
		$content = TD_('Hi!

This is a sample comment that has been approved by default!
Admins and moderators can very quickly approve or reject comments from the collection dashboard.');
	}
	else
	{	// draft
		$content = TD_('Hi!

This is a sample comment that has **not** been approved by default!
Admins and moderators can very quickly approve or reject comments from the collection dashboard.');
	}

	if( is_null( $comment_timestamp ) )
	{
		$comment_timestamp = time();
	}

	// We are using FROM_UNIXTIME to prevent invalid datetime during DST spring forward - fall back
	$DB->query( 'INSERT INTO T_comments( comment_item_ID, comment_status,
			comment_author_user_ID, comment_author, comment_author_email, comment_author_url, comment_author_IP,
			comment_date, comment_last_touched_ts, comment_content, comment_renderers, comment_notif_status, comment_notif_flags )
			VALUES( '.$DB->quote( $item_ID ).', '.$DB->quote( $status ).', '
			.$DB->quote( $user_ID ).', '.$DB->quote( $author ).', '.$DB->quote( $author_email ).', '.$DB->quote( $author_email_url ).', "127.0.0.1", '
			.'FROM_UNIXTIME('.$comment_timestamp.'), FROM_UNIXTIME('.$comment_timestamp.'), '.$DB->quote( $content ).', "default", "finished", "moderators_notified,members_notified,community_notified" )' );
}


/**
 * Creates a demo collection
 *
 * @param string Collection type
 * @param integer Owner ID
 * @param boolean Use demo users as comment authors
 * @param integer Shift post time in ms
 * @param integer Section ID
 * @return integer ID of created blog
 */
function create_demo_collection( $collection_type, $owner_ID, $use_demo_user = true, $timeshift = 86400, $section_ID = 1, &$error_messages = NULL )
{
	global $install_test_features, $DB, $admin_url, $timestamp;
	global $blog_minisite_ID, $blog_home_ID, $blog_a_ID, $blog_b_ID, $blog_photoblog_ID, $blog_forums_ID, $blog_manual_ID, $events_blog_ID;

	$default_blog_longdesc = TD_('This is the long description for the collection named \'%s\'. %s');
	$default_blog_access_type = 'relative';

	$timestamp = time();
	$blog_ID = NULL;
	$blog_tagline = TD_('This is the collection\'s tagline.');

	switch( $collection_type )
	{
		// =======================================================================================================
		case 'minisite':
			$blog_shortname = TD_('Mini-Site');
			$blog_more_longdesc = '<br />
<br />
<strong>'.TD_('The main purpose for this blog is to be included as a side item to other blogs where it will display your favorite/related links.').'</strong>';

			$blog_minisite_ID = create_blog(
					TD_('Mini-Site Title'),
					$blog_shortname,
					'minisite',
					$blog_tagline,
					sprintf( $default_blog_longdesc, $blog_shortname, $blog_more_longdesc ),
					'Jared Skin',
					'minisite',
					'any',
					1,
					'default',
					true,
					'never',
					$owner_ID,
					'public',
					$section_ID );

			if( $blog_minisite_ID )
			{
				$blog_ID = $blog_minisite_ID;
			}
			break;

		// =======================================================================================================
		case 'main':
			$blog_shortname = TD_('Home');
			$blog_home_access_type = ( $install_test_features ) ? 'default' : $default_blog_access_type;
			$blog_more_longdesc = '';

			$blog_home_ID = create_blog(
					TD_('Homepage Title'),
					$blog_shortname,
					'home',
					$blog_tagline,
					sprintf( $default_blog_longdesc, $blog_shortname, $blog_more_longdesc ),
					'Bootstrap Main',
					'main',
					'any',
					1,
					'default',
					true,
					'never',
					$owner_ID,
					'public',
					$section_ID );

			if( $blog_home_ID )
			{
				if( ! $DB->get_var( 'SELECT set_value FROM T_settings WHERE set_name = '.$DB->quote( 'info_blog_ID' ) ) && ! empty( $blog_home_ID ) )
				{	// Save ID of this blog in settings table, It is used on top menu, file "/skins_site/_site_body_header.inc.php"
					$DB->query( 'REPLACE INTO T_settings ( set_name, set_value )
							VALUES ( '.$DB->quote( 'info_blog_ID' ).', '.$DB->quote( $blog_home_ID ).' )' );
				}
				$blog_ID = $blog_home_ID;
			}
			break;

		// =======================================================================================================
		case 'std':
		case 'blog_a':
			if( $collection_type == 'blog_a' )
			{
				$blog_shortname = 'Blog A';
			}
			else
			{
				$blog_shortname = 'Blog';
			}
			$blog_stub = 'a';
			$blog_a_ID = create_blog(
					TD_('Public Blog'),
					$blog_shortname,
					$blog_stub,
					$blog_tagline,
					sprintf( $default_blog_longdesc, $blog_shortname, '' ),
					'Bootstrap Blog',
					'std',
					'any',
					1,
					'#',
					true,
					'public',
					$owner_ID,
					'public',
					$section_ID );
			if( $blog_a_ID )
			{
				$blog_ID = $blog_a_ID;
			}
			break;

		// =======================================================================================================
		case 'blog_b':
			// Create group for Blog b
			$blogb_Group = new Group(); // COPY !
			$blogb_Group->set( 'name', 'Blog B Members' );
			$blogb_Group->set( 'usage', 'secondary' );
			$blogb_Group->set( 'level', 1 );
			$blogb_Group->set( 'perm_blogs', 'user' );
			$blogb_Group->set( 'perm_stats', 'none' );
			$blogb_Group->dbinsert();

			// Assign owner to blog b
			assign_secondary_groups( $owner_ID, array( $blogb_Group->ID ) );

			$blog_shortname = 'Blog B';
			$blog_stub = 'b';

			$blog_b_ID = create_blog(
					TD_('Members-Only Blog'),
					$blog_shortname,
					$blog_stub,
					$blog_tagline,
					sprintf( $default_blog_longdesc, $blog_shortname, '' ),
					'Bootstrap Blog',
					'std',
					'',
					0,
					'#',
					true,
					'public',
					$owner_ID,
					'members',
					$section_ID );

			if( $blog_b_ID )
			{
				$BlogCache = & get_BlogCache();
				if( $b_Blog = $BlogCache->get_by_ID( $blog_b_ID, false, false ) )
				{
					$b_Blog->set_setting( 'front_disp', 'front' );
					$b_Blog->set_setting( 'skin2_layout', 'single_column' );
					$b_Blog->set( 'advanced_perms', 1 );
					$b_Blog->dbupdate();
				}
				$blog_ID = $blog_b_ID;
			}
			break;

		// =======================================================================================================
		case 'photo':
			$blog_shortname = 'Photos';
			$blog_stub = 'photos';
			$blog_more_longdesc = '';

			$blog_photoblog_ID = create_blog(
					'Photos',
					$blog_shortname,
					$blog_stub,
					$blog_tagline,
					sprintf( $default_blog_longdesc, $blog_shortname, $blog_more_longdesc ),
					'Bootstrap Gallery Skin',
					'photo', '', 0, '#', true, 'public',
					$owner_ID,
					'public',
					$section_ID );
			if( $blog_photoblog_ID )
			{
				$blog_ID = $blog_photoblog_ID;
			}
			break;

		// =======================================================================================================
		case 'forum':
			$blog_shortname = 'Forums';
			$blog_stub = 'forums';
			$blog_forums_ID = create_blog(
					TD_('Forums Title'),
					$blog_shortname,
					$blog_stub,
					$blog_tagline,
					sprintf( $default_blog_longdesc, $blog_shortname, '' ),
					'Bootstrap Forums',
					'forum', 'any', 1, '#', false, 'public',
					$owner_ID,
					'public',
					$section_ID );
			if( $blog_forums_ID )
			{
				$blog_ID = $blog_forums_ID;
			}
			break;

		// =======================================================================================================
		case 'manual':
			$blog_shortname = 'Manual';
			$blog_stub = 'manual';
			$blog_manual_ID = create_blog(
					TD_('Manual Title'),
					$blog_shortname,
					$blog_stub,
					$blog_tagline,
					sprintf( $default_blog_longdesc, $blog_shortname, '' ),
					'Bootstrap Manual',
					'manual', 'any', 1, '#', false, 'public',
					$owner_ID,
					'public',
					$section_ID );
			if( $blog_manual_ID )
			{
				$blog_ID = $blog_manual_ID;
			}
			break;

		// =======================================================================================================
		case 'group':
			$blog_shortname = 'Tracker';
			$blog_stub = 'tracker';
			$blog_group_ID = create_blog(
					TD_('Tracker Title'),
					$blog_shortname,
					$blog_stub,
					$blog_tagline,
					sprintf( $default_blog_longdesc, $blog_shortname, '' ),
					'Bootstrap Forums',
					'group', 'any', 1, '#', false, 'member',
					$owner_ID,
					'members',
					$section_ID );
			if( $blog_group_ID )
			{
				$BlogCache = & get_BlogCache();
				if( $group_Collection = $BlogCache->get_by_ID( $blog_group_ID, false, false ) )
				{
					$group_Collection->set( 'advanced_perms', 1 );
					$group_Collection->dbupdate();
				}
				$blog_ID = $blog_group_ID;
			}
			break;

		default:
			debug_die( 'Invalid collection type' );
	}

	if( ! empty( $blog_ID ) )
	{
		// Create sample contents for the collection:
		create_sample_content( $collection_type, $blog_ID, $owner_ID, $use_demo_user, $timeshift, $error_messages );
		return $blog_ID;
	}
	else
	{
		return false;
	}
}


/**
 * Creates sample contents for the collection
 *
 * @param string Collection type
 * @param integer Blog ID
 * @param integer Owner ID
 * @param boolean Use demo users as comment authors
 * @param integer Shift post time in ms
 */
function create_sample_content( $collection_type, $blog_ID, $owner_ID, $use_demo_user = true, $timeshift = 86400, &$error_messages = NULL )
{
	global $DB, $install_test_features, $timestamp, $Settings, $admin_url, $installed_collection_info_pages;

	if( ! isset( $installed_collection_info_pages ) )
	{	// Array for item IDs which should be used in default shared widget containers "Main Navigation" and "Navigation Hamburger":
		$installed_collection_info_pages = array();
	}

	$timestamp = time();
	$comment_item_IDs = array();
	$additional_comments_item_IDs = array();
	$demo_users = get_demo_users( false );

	$BlogCache = & get_BlogCache();
	$edited_Blog = $BlogCache->get_by_ID( $blog_ID, false, false );

	// Store here temp vars(e-g IDs of Links) which may be set during one Item creating and used for next other Items:
	$demo_vars = array();

	// Sample Items for collection with ANY type:
	$demo_items = array();

	// --------------------- START OF GENERIC SAMPLE ITEMS -------------------- //
	$demo_items['extended_post'] = array(
		'title'    => TD_('Extended post'),
		'featured' => true,
		'tags'     => 'photo,demo',
		'category' => 'background',
		'content'  => '<p>'.TD_('This is an extended post. This means you only see this small teaser by default and you must click on the link below to see more.').'</p>'.get_filler_text( 'lorem_1paragraph' )
.'[teaserbreak]

<p>'.TD_('This is the extended text. You only see it when you have clicked the "more" link.').'</p>'.get_filler_text( 'lorem_2more' ),
		'files' => array(
			array( 'monument-valley/john-ford-point.jpg', 'cover' ),
			array( 'monument-valley/monument-valley-road.jpg', 'teaser' ),
			array( 'monument-valley/monuments.jpg', 'aftermore' ),
		),
	);

	$demo_items['post_with_images'] = array(
		'title'      => TD_('Post with Images'),
		'featured'   => true,
		'tags'       => 'photo,demo',
		'category'   => 'background',
		'content'    => TD_('<p>This post has several images attached to it. Each one uses a different Attachment Position. Each may be displayed differently depending on the skin they are viewed in.</p>

<p>Check out the photoblog (accessible through the links at the top) to see a completely different skin focused more on the photos than on the blog text.</p>'),
		'files' => array(
			array( 'monument-valley/monument-valley.jpg', 'cover' ),
			array( 'monument-valley/monuments.jpg', 'teaser' ),
			array( 'monument-valley', 'aftermore' ),
			array( 'monument-valley/bus-stop-ahead.jpg', 'aftermore' ),
		),
	);

	$demo_items['mongolian_beef'] = array(
		'title'    => TD_('Mongolian Beef'),
		'featured' => true,
		'tags'     => 'photo,demo,recipe,custom fields',
		'category' => 'recipes',
		'type'     => 'Recipe',
		'content'  => '<p>'.TD_('A quick go-to dinner. Can be made with almost any meat. I often used ground. Works perfect for lettuce wraps. Try replacing the onion with thinly sliced fennel.').'</p>
<p>'.TD_('Optional: spice this thing up, with a dose of your favorite chili paste/sauce.').'</p>
[teaserbreak]
<ol>
	<li>'.TD_('Slice the beef thin and cook with a bit of oil (your choice) and the yellow onion (cut into petals) in a medium saucepan. Set aside when done.').'</li>
	<li>'.TD_('Make the sauce by heating 2 tsp of vegetable oil over med/low heat in the same pan. Don\'t get the oil too hot.').'</li>
	<li>'.TD_('Add ginger and garlic to the pan and quickly add the soy sauce and water before the garlic scorches.').'</li>
	<li>'.TD_('Dissolve the brown sugar in the sauce, then raise the heat to medium and boil the sauce for 2-3 minutes or until the sauce thickens.').'</li>
	<li>'.TD_('Remove from the heat, add beef back in. Toss').'</li>
	<li>'.TD_('Serve with rice, top with green onions').'</li>
</ol>',
		'custom_fields' => array(
			array( 'course', TD_('Main Course') ),
			array( 'cuisine', TD_('Mongolian') ),
			array( 'servings', '4' ),
			array( 'prep_time', '2' ),
			array( 'cook_time', '35' ),
			array( 'passive_time', '5' ),
			array( 'ingredients', TD_('vegetable oil
1/2 teaspoon ginger
1 tablespoon garlic
1/2 cup soy sauce
1/2 cup water
3/4 cup dark brown sugar
1 lb flank steak
1 yellow onion
2 large green onions') ),
		),
		'files' => array(
			array( 'recipes/mongolian-beef.jpg', 'teaser' ),
		),
	);

	$demo_items['stuffed_peppers'] = array(
		'title'    => TD_('Stuffed Peppers'),
		'featured' => true,
		'tags'     => 'photo,demo,recipe,custom fields',
		'category' => 'recipes',
		'type'     => 'Recipe',
		'content'  => '<p>'.TD_('We found these during Happy Hour at Chiso\'s Grill in Bee Cave, Tx. W\'ve since tweaked the recipe a bit. This recipe is just a starting point, add/remove anything you want (like more hot sauce if you\'re into that).').'</p>
[teaserbreak]
<ol>
	<li>'.TD_('combine goat cheese, mayo, sour cream, 2/3rds of your chives, hot sauce, black pepper').'</li>
	<li>'.TD_('if you are feeling spry, beat the mixture to make it fluffy').'</li>
	<li>'.TD_('put filling in a plastic bag, snip of the tip with scissors to make a piping bag').'</li>
	<li>'.TD_('fill peppers, place in bowl, top with chives and hot sauce').'</li>
</ol>',
		'custom_fields' => array(
			array( 'course', TD_('Main Course') ),
			array( 'cuisine', TD_('South African') ),
			array( 'servings', '2' ),
			array( 'prep_time', '1' ),
			array( 'cook_time', '20' ),
			array( 'passive_time', '3' ),
			array( 'ingredients', TD_('1 jar Peppedew Peppers (or piquante pepper)
4oz goat cheese (any flavor)
1 tbsp mayonnaise
1 tbsp sour cream
1 bunch of chives, chopped
hearty shot of hot sauce (Franks, Yellowbird)
hearty crack of pepper') ),
		),
		'files' => array(
			array( 'recipes/stuffed-peppers.jpg', 'teaser' ),
		),
	);

	$demo_items['custom_fields_example'] = array(
		'title'    => TD_('Custom Fields Example'),
		'tags'     => 'demo,custom fields',
		'category' => 'background',
		'type'     => 'Post with Custom Fields',
		'content'  => '<p>'.TD_('This post has a special post type called "Post with Custom Fields".').'</p>
<p>'.TD_('This post type defines 4 custom fields. Here are the sample values that have been entered in these fields:').'</p>
<p>[fields]</p>
[teaserbreak]
<p>'.TD_('It is also possible to selectively display only a couple of these fields:').'</p>
<p>[fields:first_numeric_field,first_string_field,second_numeric_field]</p>
<p>'.sprintf( TD_('Finally, we can also display just the value of a specific field, like this: %s.'), '[field:first_string_field]' ).'</p>
<p>'.sprintf( TD_('It is also possible to create links using a custom field URL: %s'), '[link:url_field:.btn.btn-info]Click me![/link]' ).'</p>',
		'custom_fields' => array(
			array( 'first_numeric_field', '123' ),
			array( 'second_numeric_field', '456' ),
			array( 'usd_price', '29.99' ),
			array( 'eur_price', '24.79' ),
			array( 'first_string_field', 'abc' ),
			array( 'multiline_plain_text_field', 'This is a sample text field.
It can have multiple lines.' ),
			array( 'multiline_html_field', 'This is an <b>HTML</b> <i>field</i>.' ),
			array( 'url_field', 'http://b2evolution.net/' ),
			array( 'checkmark_field', '1' ),
		),
		'files' => array(
			array( 'monument-valley/monument-valley.jpg', 'attachment', 'custom_field' => 'image_1', 'set_var' => 'custom_item_link_ID' ),
		),
	);

	$demo_items['another_custom_fields_example'] = array(
		'title'    => TD_('Another Custom Fields Example'),
		'tags'     => 'demo,custom fields',
		'category' => 'background',
		'type'     => 'Post with Custom Fields',
		'content'  => '<p>'.TD_('This post has a special post type called "Post with Custom Fields".').'</p>
<p>'.TD_('This post type defines 4 custom fields. Here are the sample values that have been entered in these fields:').'</p>
<p>[fields]</p>
[teaserbreak]
<p>'.TD_('It is also possible to selectively display only a couple of these fields:').'</p>
<p>[fields:first_numeric_field,first_string_field,second_numeric_field]</p>
<p>'.sprintf( TD_('Finally, we can also display just the value of a specific field, like this: %s.'), '[field:first_string_field]' ).'</p>
<p>'.sprintf( TD_('It is also possible to create links using a custom field URL: %s'), '[link:url_field:.btn.btn-info]Click me![/link]' ).'</p>',
		'custom_fields' => array(
			array( 'first_numeric_field', '123.45' ),
			array( 'second_numeric_field', '456' ),
			array( 'usd_price', '17.50' ),
			array( 'eur_price', '14.95' ),
			array( 'first_string_field', 'abcdef' ),
			array( 'multiline_plain_text_field', 'This is a sample text field.
It can have multiple lines.
This is an extra line.' ),
			array( 'multiline_html_field', 'This is an <b>HTML</b> <i>field</i>.' ),
			array( 'url_field', 'http://b2evolution.net/' ),
			array( 'checkmark_field', '0' ),
		),
		'files' => array(
			array( 'monument-valley/monument-valley-road.jpg', 'attachment', 'custom_field' => 'image_1' ),
		),
	);

	$demo_items['child_post_example'] = array(
		'title'     => TD_('Child Post Example'),
		'tags'      => 'demo,custom fields',
		'category'  => 'background',
		'type'      => 'Child Post',
		'parent_ID' => '#get_item#another_custom_fields_example#ID#',
		'content'   => sprintf( TD_('This post has a special post type called "Child Post". This allowed to specify a parent post ID. Consequently, this child post is linked to: %s.'), '[parent:titlelink] ([parent:url])' )."\n\n"
.TD_('This also allows us to access the custom fields of the parent post:')."\n\n"
.'[parent:fields]'."\n\n"
.'[teaserbreak]'."\n\n"
.TD_('It is also possible to selectively display only a couple of these fields:')."\n\n"
.'[parent:fields:first_numeric_field,first_string_field,second_numeric_field]'."\n\n"
.sprintf( TD_('Finally, we can also display just the value of a specific field, like this: %s.'), '[parent:field:first_string_field]' )."\n\n"
.sprintf( TD_('We can also reference fields of any other post like this: %s or like this: %s.'), '[item:another-custom-fields-example:field:first_string_field]', '[item:#get_item#another_custom_fields_example#ID#:field:first_string_field]' )."\n\n"
.sprintf( TD_('It is also possible to create links using a custom field URL from the parent post: %s'), '[parent:link:url_field:.btn.btn-info]Click me![/link]' )."\n\n"
.'###'.TD_('Replicated fields')."\n\n"
.TD_('By using the same field names, it is also possible to automatically replicate some fields from parent to child (recursively).')."\n\n"
.TD_('This child post has the following fields which automatically replicate from its parent:')."\n\n"
.'[fields]'."\n\n"
.sprintf( TD_('Another way to show this, is to use b2evolution\'s %s short tag:'), '`[compare:...]`' )."\n\n"
.'[compare:$this$,$parent$]',
		'custom_fields' => array(
			array( 'first_numeric_field', '123' ),
			array( 'first_string_field', 'abc' ),
			array( 'image_1', '#get_var#custom_item_link_ID#' ),
			array( 'checkmark_field', '1' ),
		),
		'files' => array(
			array( 'monument-valley/monument-valley-road.jpg', 'attachment', 'custom_field' => 'image_1' ),
		),
		'settings' => array(
			'editor_code' => 'html', // use markup(don't use tinymce) edtior by default for this demo item
		),
	);

	$demo_items['extended_post_with_no_teaser'] = array(
		'title'       => TD_('Extended post with no teaser'),
		'tags'        => 'demo',
		'category'    => 'background',
		'hide_teaser' => true,
		'content'     => '<p>'.TD_('This is an extended post with no teaser. This means that you won\'t see this teaser any more when you click the "more" link.').'</p>'.get_filler_text( 'lorem_1paragraph' )
.'[teaserbreak]

<p>'.TD_('This is the extended text. You only see it when you have clicked the "more" link.').'</p>'.get_filler_text( 'lorem_2more' ),
	);

	$demo_items['multipage_post'] = array(
		'title'    => TD_('This is a multipage post'),
		'tags'     => 'demo',
		'category' => 'background',
		'content'  => TD_('<p>This is page 1 of a multipage post.</p>

<blockquote><p>This is a Block Quote.</p></blockquote>

<p>You can see the other pages by clicking on the links below the text.</p>').'

[pagebreak]

<p>'.sprintf( TD_('This is page %d.'), 2 ).'</p>'.get_filler_text( 'lorem_2more' ).'

[pagebreak]

<p>'.sprintf( TD_('This is page %d.'), 3 ).'</p>'.get_filler_text( 'lorem_1paragraph' ).'

[pagebreak]

<p>'.sprintf( TD_('This is page %d.'), 4 ).'</p>

<p>'.TD_('It is the last page.').'</p>',
	);

	$demo_items['featured_post'] = array(
		'title'      => TD_('Featured post'),
		'featured'   => true,
		'tags'       => 'demo',
		'category'   => 'background',
		'content'    => TD_('<p>This is a demo of a featured post.</p>

<p>It will be featured whenever we have no specific "Intro" post to display for the current request. To see it in action, try displaying the "Announcements" category.</p>

<p>Also note that when the post is featured, it does not appear in the regular post flow.</p>').get_filler_text( 'lorem_1paragraph' ),
	);

	$demo_items['markdown_examples'] = array(
		'title'    => TD_('Markdown examples'),
		'tags'     => 'demo,rendering',
		'category' => 'background',
		'content'  => get_filler_text( 'markdown_examples_content' ),
	);

	$demo_items['wiki_tables'] = array(
		'title'      => TD_('Wiki Tables'),
		'tags'       => 'demo,rendering',
		'category'   => 'background',
		'content'    => /* DO NOT TRANSLATE - TOO COMPLEX */ '<p>This is the topic with samples of the wiki tables.</p>

{|
|Orange
|Apple
|-
|Bread
|Pie
|-
|Butter
|Ice cream
|}

{|
|Orange||Apple||more
|-
|Bread||Pie||more
|-
|Butter||Ice<br />cream||and<br />more
|}

{|
|Lorem ipsum dolor sit amet,
consetetur sadipscing elitr,
sed diam nonumy eirmod tempor invidunt
ut labore et dolore magna aliquyam erat,
sed diam voluptua.

At vero eos et accusam et justo duo dolores
et ea rebum. Stet clita kasd gubergren,
no sea takimata sanctus est Lorem ipsum
dolor sit amet.
|
* Lorem ipsum dolor sit amet
* consetetur sadipscing elitr
* sed diam nonumy eirmod tempor invidunt
|}

{|
! align="left"| Item
! Amount
! Cost
|-
|Orange
|10
|7.00
|-
|Bread
|4
|3.00
|-
|Butter
|1
|5.00
|-
!Total
|
|15.00
|}

<br />

{|
|+Food complements
|-
|Orange
|Apple
|-
|Bread
|Pie
|-
|Butter
|Ice cream
|}

{| class="wikitable"
|+Food complements
|-
|Orange
|Apple
|-
|Bread
|Pie
|-
|Butter
|Ice cream
|}

{| class="wikitable" style="text-align: center; color: green;"
|Orange
|Apple
|12,333.00
|-
|Bread
|Pie
|500.00
|-
|Butter
|Ice cream
|1.00
|}

{| class="wikitable"
| Orange
| Apple
| align="right"| 12,333.00
|-
| Bread
| Pie
| align="right"| 500.00
|-
| Butter
| Ice cream
| align="right"| 1.00
|}

{| class="wikitable"
| Orange || Apple     || align="right" | 12,333.00
|-
| Bread  || Pie       || align="right" | 500.00
|-
| Butter || Ice cream || align="right" | 1.00
|}

{| class="wikitable"
| Orange
| Apple
| align="right"| 12,333.00
|-
| Bread
| Pie
| align="right"| 500.00
|- style="font-style: italic; color: green;"
| Butter
| Ice cream
| align="right"| 1.00
|}

{| style="border-collapse: separate; border-spacing: 0; border: 1px solid #000; padding: 0"
|-
| style="border-style: solid; border-width: 0 1px 1px 0"|
Orange
| style="border-style: solid; border-width: 0 0 1px 0"|
Apple
|-
| style="border-style: solid; border-width: 0 1px 0 0"|
Bread
| style="border-style: solid; border-width: 0"|
Pie
|}

{| style="border-collapse: collapse; border: 1px solid #000"
|-
| style="border-style: solid; border-width: 1px"|
Orange
| style="border-style: solid; border-width: 1px"|
Apple
|-
| style="border-style: solid; border-width: 1px"|
Bread
| style="border-style: solid; border-width: 1px"|
Pie
|}

{|style="border-style: solid; border-width: 20px"
|
Hello
|}

{|style="border-style: solid; border-width: 10px 20px 100px 0"
|
Hello
|}

{| class="wikitable"
!colspan="6"|Shopping List
|-
|rowspan="2"|Bread &amp; Butter
|Pie
|Buns
|Danish
|colspan="2"|Croissant
|-
|Cheese
|colspan="2"|Ice cream
|Butter
|Yogurt
|}

{| class="wikitable" style="color:green; background-color:#ffffcc;" cellpadding="10"
|Orange
|Apple
|-
|Bread
|Pie
|-
|Butter
|Ice cream
|}

{| class="wikitable"
|+ align="bottom" style="color:#e76700;"|\'\'Food complements\'\'
|-
|Orange
|Apple
|-
|Bread
|Pie
|-
|Butter
|Ice cream
|}

{| style="color: black; background-color: #ffffcc;" width="85%"
| colspan="2" | This column width is 85% of the screen width (and has a background color)
|-
| style="width: 30%; background-color: white;"|
\'\'\'This column is 30% counted from 85% of the screen width\'\'\'
| style="width: 70%; background-color: orange;"|
\'\'\'This column is 70% counted from 85% of the screen width (and has a background color)\'\'\'
|}

{| class="wikitable"
|-
! scope="col"| Item
! scope="col"| Quantity
! scope="col"| Price
|-
! scope="row"| Bread
| 0.3 kg
| $0.65
|-
! scope="row"| Butter
| 0.125 kg
| $1.25
|-
! scope="row" colspan="2"| Total
| $1.90
|}',
	);

	$demo_items['about_widgets'] = array(
		'title'    => TD_('About widgets...'),
		'tags'     => 'widgets',
		'category' => 'background',
		'mustread' => is_pro(),
		'content'  => TD_('<p>b2evolution blogs are installed with a default selection of Widgets. For example, the sidebar of this blog includes widgets like a calendar, a search field, a list of categories, a list of XML feeds, etc.</p>

<p>You can add, remove and reorder widgets from the Blog Settings tab in the admin interface.</p>

<p>Note: in order to be displayed, widgets are placed in containers. Each container appears in a specific place in an evoskin. If you change your blog skin, the new skin may not use the same containers as the previous one. Make sure you place your widgets in containers that exist in the specific skin you are using.</p>'),
	);

	$demo_items['about_skins'] = array(
		'title'    => TD_('About skins...'),
		'tags'     => 'skins',
		'category' => 'background',
		'mustread' => is_pro(),
		'content'  => sprintf( TD_('<p>By default, b2evolution blogs are displayed using an evoskin.</p>

<p>You can change the skin used by any blog by editing the blog settings in the admin interface.</p>

<p>You can download additional skins from the <a href="http://skins.b2evolution.net/" target="_blank">skin site</a>. To install them, unzip them in the /blogs/skins directory, then go to General Settings &gt; Skins in the admin interface and click on "Install new".</p>

<p>You can also create your own skins by duplicating, renaming and customizing any existing skin folder from the /blogs/skins directory.</p>

<p>To start customizing a skin, open its "<code>index.main.php</code>" file in an editor and read the comments in there. Note: you can also edit skins in the "Files" tab of the admin interface.</p>

<p>And, of course, read the <a href="%s" target="_blank">manual on skins</a>!</p>'), get_manual_url( 'skin-structure' ) ),
	);

	$demo_items['apache_optimization'] = array(
		'title'    => TD_('Apache optimization...'),
		'category' => 'background',
		'mustread' => is_pro(),
		'content'  => sprintf( TD_('<p>b2evolution comes with an <code>.htaccess</code> file destined to optimize the way b2evolution is handled by your webseerver (if you are using Apache). In some circumstances, that file may not be automatically activated at setup. Please see the man page about <a %s>Tricky Stuff</a> for more information.</p>

<p>For further optimization, please review the manual page about <a %s>Performance optimization</a>. Depending on your current configuration and on what your <a %s>web hosting</a> company allows you to do, you may increase the speed of b2evolution by up to a factor of 10!</p>'),
'href="'.get_manual_url( 'tricky-stuff' ).'"',
'href="'.get_manual_url( 'performance-optimization' ).'"',
'href="http://b2evolution.net/web-hosting/"' ),
	);

	$demo_items['second_post'] = array(
		'title'    => TD_('Second post'),
		'category' => 'news',
		'extra_cats' => array( 'welcome' ),
		'content'  => TD_('<p>This is the second post in the "[coll:shortname]" collection.</p>

<p>It appears in multiple categories.</p>'),
	);

	$demo_items['first_post'] = array(
		'title'    => TD_('First Post'),
		'category' => 'welcome',
		'content'  => TD_('<p>This is the first post in the "[coll:shortname]" collection.</p>

<p>It appears in multiple categories.</p>'),
	);
	// ---------------------- END OF GENERIC SAMPLE ITEMS -------------------- //

	switch( $collection_type )
	{
		// =======================================================================================================
		case 'minisite':
			// Mini-Site

			// Sample categories:
			$categories = array(
				'b2evolution'  => 'b2evolution',
				'contributors' => TD_('Contributors'),
			);

			// Don't install generic items(except of two recipes) for this collection type:
			foreach( $demo_items as $demo_item_key => $demo_item_data )
			{
				if( $demo_item_key != 'mongolian_beef' && $demo_item_key != 'stuffed_peppers' )
				{
					unset( $demo_items[ $demo_item_key ] );
				}
			}

			// Additional sample Items:
			$demo_items['about_minisite'] = array(
				'title'    => TD_('About Minisite'),
				'category' => 'b2evolution',
				'type'     => 'Standalone Page',
				'content'  => sprintf( get_filler_text( 'info_page' ), TD_('Mini-Site') ),
				'files'    => array(
					array( 'monument-valley/monument-valley.jpg', 'cover' ),
				),
				'widget_info_page' => 'about_minisite',
			);

			$demo_items['more_info'] = array(
				'title'    => TD_('More info'),
				'category' => 'b2evolution',
				'type'     => 'Widget Page',
				'files'    => array(
					array( 'monument-valley/monuments.jpg', 'cover' ),
				),
				'widget_info_page' => 'widget_page',
			);
			break;

		// =======================================================================================================
		case 'main':
			// Global home page

			// Sample categories:
			$categories = array(
				'b2evolution'  => 'b2evolution',
				'contributors' => TD_('Contributors'),
			);

			// Don't install generic items for this collection type:
			$demo_items = array();

			// Additional sample Items:
			$demo_items['terms_conditions'] = array(
				'title'    => TD_('Terms & Conditions'),
				'tags'     => 'intro',
				'category' => 'b2evolution',
				'type'     => 'Terms & Conditions',
				'content'  => '<p>Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum</p>

<p>Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum Lorem ipsum</p>',
			);

			$demo_items['support'] = array(
				'title'    => 'Support',
				'category' => 'contributors',
				'type'     => 'Sidebar link',
				'url'      => 'http://forums.b2evolution.net/',
				'locale'   => 'en-US',
				'comment_status' => 'disabled',
			);

			$demo_items['manual'] = array(
				'title'    => 'Manual',
				'category' => 'contributors',
				'type'     => 'Sidebar link',
				'url'      => get_manual_url( NULL ),
				'locale'   => 'en-US',
				'comment_status' => 'disabled',
			);

			$demo_items['web_hosting'] = array(
				'title'    => 'Web hosting',
				'category' => 'contributors',
				'type'     => 'Sidebar link',
				'url'      => 'http://b2evolution.net/web-hosting/blog/',
				'locale'   => 'en-US',
				'comment_status' => 'disabled',
			);

			$demo_items['blog_news'] = array(
				'title'    => 'Blog news',
				'category' => 'contributors',
				'type'     => 'Sidebar link',
				'url'      => 'http://b2evolution.net/news.php',
				'locale'   => 'en-US',
				'comment_status' => 'disabled',
			);

			$demo_items['francois'] = array(
				'title'    => 'Francois',
				'category' => 'contributors',
				'type'     => 'Sidebar link',
				'url'      => 'http://fplanque.com/',
				'locale'   => 'fr-FR',
				'comment_status' => 'disabled',
			);

			$demo_items['evo_factory'] = array(
				'title'    => 'Evo Factory',
				'category' => 'contributors',
				'type'     => 'Sidebar link',
				'url'      => 'http://evofactory.com/',
				'locale'   => 'en-US',
				'comment_status' => 'disabled',
			);

			$demo_items['about_this_site'] = array(
				'title'    => TD_('About this site'),
				'category' => 'b2evolution',
				'type'     => 'Standalone Page',
				'content'  => TD_('This website is powered by b2evolution.')."\r\n\r\n"
					.TD_('You are currently looking at an info page about this site.')."\r\n\r\n"
					.TD_('Info pages are Standalone pages: contrary to regular Posts, do not appear in the regular flow of posts. Instead, they are typically accessed directly from a navigation menu.')."\r\n\r\n"
					.'[div::view=detailed]'."\r\n"
						.sprintf( TD_('This extra information is only displayed when detailed view is requested. This is achieved by adding a display condition on a block of content like %s.'), '`[div::view=detailed] ... [/div]`' )."\r\n\r\n"
						.TD_('Note: If needed, skins may format info pages differently from regular posts.')."\r\n"
					.'[/div]'."\r\n\r\n"
					.'[switcher:view:buttons]'."\r\n"
					.'	[option:simple]Simple[/option]'."\r\n"
					.'	[option:detailed]Detailed[/option]'."\r\n"
					.'[/switcher]',
				'files'    => array(
					array( 'logos/b2evolution_1016x208_wbg.png' ),
				),
				'widget_info_page' => 'about_this_site',
				'settings' => array(
						'editor_code' => 'html',
						'switchable'  => 1,
						'switchable_params' => 'view=simple',
					),
			);

			$demo_items['widget_page'] = array(
				'title'    => TD_('Widget Page'),
				'category' => 'b2evolution',
				'type'     => 'Widget Page',
				'files'    => array(
					array( 'monument-valley/monuments.jpg', 'cover' ),
				),
				'widget_info_page' => 'widget_page',
			);

			$demo_items['b2evo_the_other_blog_tool'] = array(
				'title'    => /* TRANS: sample ad content */ TD_('b2evo: The other blog tool!'),
				'tags'     => 'photo',
				'category' => 'b2evolution',
				'type'     => 'Advertisement',
				'url'      => 'http://b2evolution.net',
				'content'  => /* TRANS: sample ad content */ TD_('The other blog tool!'),
				'files'    => array(
					array( 'banners/b2evo-125-other.png' ),
				),
			);

			$demo_items['b2evo_better_blog_software'] = array(
				'title'    => /* TRANS: sample ad content */ TD_('b2evo: Better Blog Software!'),
				'tags'     => 'photo',
				'category' => 'b2evolution',
				'type'     => 'Advertisement',
				'url'      => 'http://b2evolution.net',
				'content'  => /* TRANS: sample ad content */ TD_('Better Blog Software!'),
				'files'    => array(
					array( 'banners/b2evo-125-better.png' ),
				),
			);

			$demo_items['b2evo_the_software_for_blog_pros'] = array(
				'title'    => /* TRANS: sample ad content */ TD_('b2evo: The software for blog pros!'),
				'tags'     => 'photo',
				'category' => 'b2evolution',
				'type'     => 'Advertisement',
				'url'      => 'http://b2evolution.net',
				'content'  => /* TRANS: sample ad content */ TD_('The software for blog pros!'),
				'files'    => array(
					array( 'banners/b2evo-125-pros.png' ),
				),
			);

			$demo_items['register_content'] = array(
				'title'    => TD_('Register content'),
				'slug'     => 'register-content',
				'tags'     => 'demo',
				'category' => 'b2evolution',
				'type'     => 'Content Block',
				'content'  => TD_('The information you provide in this form will be recorded in your user account.')
					."\n\n"
					.TD_('You will be able to modify it (or even close your account) at any time after logging in with your username and password.')
					."\n\n"
					.TD_('Should you forget your password, you will be able to reset it by receiving a link on your email address.')
					."\n\n"
					.TD_('All other info is used to personalize your experience with this website.')
					."\n\n"
					.TD_('This site may allow conversation between users.')
					.' '.TD_('Your email address and password will not be shared with other users.')
					.' '.TD_('All other information may be shared with other users.')
					.' '.TD_('Do not provide information you are not willing to share.'),
			);

			$demo_items['help_content'] = array(
				'title'    => TD_('Help content'),
				'slug'     => 'help-content',
				'tags'     => 'demo',
				'category' => 'b2evolution',
				'type'     => 'Content Block',
				'content'  => '### '.TD_('Email preferences')
					."\n\n"
					.sprintf( TD_('You can see and change all your email subscriptions and notifications coming from this site by clicking <a %s>here</a>.'), 'href="'.$edited_Blog->get( 'subsurl' ).'"' )
					."\n\n"
					.'### '.TD_('Managing your personal information')
					."\n\n"
					.sprintf( TD_('You can see and correct the personal details we know about you by clicking <a %s>here</a>.'), 'href="'.$edited_Blog->get( 'profileurl' ).'"' )
					."\n\n"
					.'### '.TD_('Closing your account')
					."\n\n"
					.sprintf( TD_('You can close your account yourself by clicking <a %s>here</a>.'), 'href="'.$edited_Blog->get( 'closeaccounturl' ).'"' ),
			);

			$demo_items['access_denied'] = array(
				'title'    => TD_('Access Denied'),
				'slug'     => 'access-denied',
				'tags'     => 'demo',
				'category' => 'b2evolution',
				'type'     => 'Content Block',
				'content'  => '<p class="center">'.TD_( 'You are not a member of this collection, therefore you are not allowed to access it.' ).'</p>',
			);

			$demo_items['login_required'] = array(
				'title'    => TD_('Login Required'),
				'slug'     => 'login-required',
				'tags'     => 'demo',
				'category' => 'b2evolution',
				'type'     => 'Content Block',
				'content'  => '<div class="alert alert-danger" style="max-width:400px;margin:20px auto">'.TD_( 'You need to log in before you can access this section.' ).'</div>',
			);

			$demo_items['this_is_a_content_block'] = array(
				'title'    => TD_('This is a Content Block'),
				'tags'     => 'demo',
				'category' => 'b2evolution',
				'type'     => 'Content Block',
				'content'  => TD_('<p>This is a Post/Item of type "Content Block".</p>

<p>A content block can be included in several places.</p>'),
			);
			break;

		// =======================================================================================================
		case 'std':
			// Blog
		case 'blog_a':
			// Sample Blog A (Public)

			// Sample categories:
			$categories = array(
				'welcome'    => TD_('Welcome'),
				'news'       => TD_('News'),
				'background' => TD_('Background'),
				'fun'        => array( TD_('Fun'), 'subs' => array(
					'in-real-life' => array( TD_('In real life'), 'subs' => array(
						'recipes' => array( TD_('Recipes'), 'default_item_type' => 'Recipe' ),
						'movies'  => TD_('Movies'),
						'music'   => TD_('Music'),
					) ),
					'on-the-web' => TD_('On the web'),
				) ),
			);

			// Additional sample Items:
			$demo_items['main_intro_post'] = array(
				'title'    => TD_('Main Intro post'),
				'tags'     => 'intro',
				'category' => 'welcome',
				'type'     => 'Intro-Main',
				'content'  => TD_('This is the main intro post of this collection. It appears on the collection\'s front page only.'),
			);

			$demo_items['about_blog_a'] = array(
				'title'    => TD_('About Blog A'),
				'category' => 'welcome',
				'type'     => 'Standalone Page',
				'content'  => sprintf( get_filler_text( 'info_page' ), TD_('Blog A') ),
			);
			break;

		// =======================================================================================================
		case 'blog_b':
			// Sample Blog B (Private)

			// Sample categories:
			$categories = array(
				'welcome'    => TD_('Welcome'),
				'news'       => TD_('News'),
				'background' => TD_('Background'),
				'fun'        => array( TD_('Fun'), 'subs' => array(
					'in-real-life' => array( TD_('In real life'), 'subs' => array(
						'recipes' => array( TD_('Recipes'), 'default_item_type' => 'Recipe' ),
						'movies'  => TD_('Movies'),
						'music'   => TD_('Music'),
					) ),
					'on-the-web' => TD_('On the web'),
				) ),
			);

			// Additional sample Items:
			$demo_items['b2evo_skins_repository'] = array(
				'title'    => TD_('b2evo skins repository'),
				'category' => 'background',
				'type'     => 'Sidebar link',
				'url'      => 'http://skins.b2evolution.net/',
			);

			$demo_items['skin_faktory'] = array(
				'title'    => 'Skin Faktory',
				'category' => 'background',
				'type'     => 'Sidebar link',
				'url'      => 'http://www.skinfaktory.com/',
			);

			$demo_items['about_blog_a'] = array(
				'title'    => TD_('About Blog B'),
				'category' => 'welcome',
				'type'     => 'Standalone Page',
				'content'  => sprintf( get_filler_text( 'info_page' ), TD_('Blog B') ),
			);

			$demo_items['widgets_tag_sub_intro_post'] = array(
				'title'    => TD_('Widgets tag &ndash; Sub Intro post'),
				'tags'     => 'intro',
				'category' => 'welcome',
				'type'     => 'Intro-Tag',
				'content'  => TD_('This uses post type "Intro-Tag" and is tagged with the desired Tag(s).'),
			);

			$demo_items['b2evolution_tips_category_sub_intro_post'] = array(
				'title'    => TD_('b2evolution tips category &ndash; Sub Intro post'),
				'tags'     => 'intro',
				'category' => 'welcome',
				'type'     => 'Intro-Cat',
				'content'  => TD_('This uses post type "Intro-Cat" and is attached to the desired Category(ies).'),
			);

			$demo_items['welcome_to_blog_b'] = array(
				'title'    => TD_('Welcome to Blog B'),
				'tags'     => 'intro',
				'category' => 'welcome',
				'type'     => 'Intro-Front',
				'content'  => sprintf( TD_('<p>This is the intro post for the front page of Blog B.</p>

<p>Blog B is currently configured to show a front page like this one instead of directly showing the blog\'s posts.</p>

<ul>
<li>To view the blog\'s posts, click on "News" in the menu above.</li>
<li>If you don\'t want to have such a front page, you can disable it in the Blog\'s settings > Features > <a %s>Front Page</a>. You can also see an example of a blog without a Front Page in Blog A</li>
</ul>'), 'href="'.$admin_url.'?ctrl=coll_settings&amp;tab=home&amp;blog='.$blog_ID.'"' ),
			);
			break;

		// =======================================================================================================
		case 'photo':
			// Photo Albums

			// Sample categories:
			$categories = array(
				'landscapes' => TD_('Landscapes'),
			);

			// Don't install generic items for this collection type:
			$demo_items = array();

			// Additional sample Items:
			$demo_items['about_photos'] = array(
				'title'    => TD_('About Photos'),
				'category' => 'landscapes',
				'type'     => 'Standalone Page',
				'content'  => sprintf( get_filler_text( 'info_page' ), TD_('Photos') ),
			);

			$demo_items['bus_stop_ahead'] = array(
				'title'    => TD_('Bus Stop Ahead'),
				'tags'     => 'photo',
				'category' => 'landscapes',
				'content'  => TD_('In the middle of nowhere: a school bus stop where you wouldn\'t really expect it!'),
				'files'    => array(
					array( 'monument-valley/bus-stop-ahead.jpg', 'set_var' => 'photo_link_1_ID' ),
					array( 'monument-valley/john-ford-point.jpg', 'aftermore', 'set_var' => 'photo_link_2_ID' ),
					array( 'monument-valley/monuments.jpg', 'aftermore' ),
					array( 'monument-valley/monument-valley-road.jpg', 'aftermore', 'set_var' => 'photo_link_4_ID' ),
					array( 'monument-valley/monument-valley.jpg', 'aftermore' ),
				),
			);
			if( $install_test_features )
			{	// Add examples for infodots plugin
				$demo_items['bus_stop_ahead']['tags'] = 'photo,demo';
				$demo_items['bus_stop_ahead']['update_content'] = $demo_items['bus_stop_ahead']['content'].sprintf( '
[infodot:%s:191:36:100px]School bus [b]here[/b]

#### In the middle of nowhere:
a school bus stop where you wouldn\'t really expect it!

1. Item 1
2. Item 2
3. Item 3

[enddot]
[infodot:%s:104:99]cowboy and horse[enddot]
[infodot:%s:207:28:15em]Red planet[enddot]', '#get_var#photo_link_1_ID#', '#get_var#photo_link_2_ID#', '#get_var#photo_link_4_ID#' );
			}

			$demo_items['sunset'] = array(
				'title'    => TD_('Sunset'),
				'tags'     => 'photo',
				'category' => 'landscapes',
				'files'    => array(
					array( 'sunset/sunset.jpg' ),
				),
			);

			$demo_items['food'] = array(
				'title'    => TD_('Food'),
				'tags'     => 'photo',
				'category' => 'landscapes',
				'files'    => array(
					array( 'recipes/mongolian-beef.jpg' ),
					array( 'recipes/stuffed-peppers.jpg' ),
				),
			);
			break;

		// =======================================================================================================
		case 'forum':
			// Forums

			// Sample categories:
			$categories = array(
				'a_forum_group'    => array( TD_('A forum group'), 'meta' => true, 'order' => 1, 'subs' => array(
					'welcome'       => array( TD_('Welcome'), 'desc' => TD_('Welcome description'), 'order' => 1 ),
					'a_forum'       => array( TD_('A forum'), 'desc' => TD_('Short description of this forum'), 'order' => 2 ),
					'another_forum' => array( TD_('Another forum'), 'desc' => TD_('Short description of this forum'), 'order' => 3 ),
				) ),
				'another_group'    => array( TD_('Another group'), 'meta' => true, 'order' => 2, 'subs' => array(
					'background' => array( TD_('Background'), 'desc' => TD_('Background description'), 'order' => 1 ),
					'news'       => array( TD_('News'), 'desc' => TD_('News description'), 'order' => 2 ),
					'fun'        => array( TD_('Fun'), 'desc' => TD_('Fun description'), 'order' => 3, 'subs' => array(
						'in-real-life' => array( TD_('In real life'), 'order' => 4, 'subcat_ordering' => 'alpha', 'subs' => array(
							'movies' => TD_('Movies'),
							'music'  => TD_('Music'),
						) ),
						'on-the-web' => array( TD_('On the web'), 'order' => 4 ),
					) ),
				) ),
			);

			// Override settings of generic items:
			$demo_items['markdown_examples']['category'] = 'news';

			// Additional sample Items:
			$demo_items['about_forums'] = array(
				'title'    => TD_('About Forums'),
				'category' => 'welcome',
				'type'     => 'Standalone Page',
				'content'  => sprintf( get_filler_text( 'info_page' ), TD_('Photos') ),
			);

			// Don't install the following demo Items:
			$exclude_demo_items = array(
					'mongolian_beef',
					'stuffed_peppers',
					'custom_fields_example',
					'another_custom_fields_example',
					'child_post_example',
				);
			break;

		// =======================================================================================================
		case 'manual':
			// Online Manual

			// Sample categories:
			$categories = array(
				'introduction'    => array( TD_('Introduction'), 'order' => 10 ),
				'getting-started' => array( TD_('Getting Started'), 'order' => 20 ),
				'user-guide'      => array( TD_('User Guide'), 'order' => 30 ),
				'reference'       => array( TD_('Reference'), 'order' => 40, 'subcat_ordering' => 'alpha', 'subs' => array(
					'collections' => array( TD_('Collections'), 'order' => 10, 'subs' => array(
						'blogs'        => array( TD_('Blogs'), 'order' => 35 ),
						'photo_albums' => array( TD_('Photo Albums'), 'order' => 25 ),
						'forums'       => array( TD_('Forums'), 'order' => 5 ),
					) ),
					'recipes'     => array( TD_('Recipes'), 'default_item_type' => 'Recipe' ),
					'other'       => array( TD_('Other'), 'order' => 5 ),
				) ),
			);

			// Override settings of generic items:
			$demo_items['extended_post']['category'] = 'user-guide';
			$demo_items['extended_post']['order'] = 10;
			$demo_items['extended_post_with_no_teaser']['category'] = 'user-guide';
			$demo_items['extended_post_with_no_teaser']['order'] = 20;
			$demo_items['multipage_post']['category'] = 'user-guide';
			$demo_items['multipage_post']['order'] = 30;
			$demo_items['wiki_tables']['category'] = 'reference';
			$demo_items['wiki_tables']['extra_cats'] = array( 'user-guide' );
			$demo_items['wiki_tables']['order'] = 50;
			$demo_items['markdown_examples']['category'] = 'user-guide';
			$demo_items['post_with_images']['category'] = 'getting-started';
			$demo_items['post_with_images']['extra_cats'] = array( 'blogs' );
			$demo_items['post_with_images']['order'] = 10;
			$demo_items['second_post']['order'] = 20;
			$demo_items['second_post']['extra_cats'] = array( 'getting-started' );
			$demo_items['first_post']['order'] = 10;

			// Additional sample Items:
			$demo_items['chapter_intro'] = array(
				'title'    => TD_('Chapter Intro'),
				'tags'     => 'intro',
				'category' => 'reference',
				'type'     => 'Intro-Cat',
				'content'  => TD_('This is an introduction for this chapter. It is a post using the "intro-cat" type.')
."\n\n".TD_('Contrary to the other sections which are explictely sorted by default, this section is sorted alphabetically by default.'),
			);

			$demo_items['chapter_intro'] = array(
				'title'    => TD_('Chapter Intro'),
				'tags'     => 'intro',
				'category' => 'introduction',
				'type'     => 'Intro-Cat',
				'content'  => TD_('This is an introduction for this chapter. It is a post using the "intro-cat" type.'),
			);

			$demo_items['welcome_here'] = array(
				'title'    => TD_('Welcome here!'),
				'tags'     => 'intro',
				'category' => 'introduction',
				'type'     => 'Intro-Front',
				'content'  => TD_('This is the main introduction for this demo online manual. It is a post using the type "Intro-Front". It will only appear on the front page of the manual.

You may delete this post if you don\'t want such an introduction.

Just to be clear: this is a **demo** of a manual. The user manual for b2evolution is here: http://b2evolution.net/man/.'),
			);

			$demo_items['about_this_manual'] = array(
				'title'    => TD_('About this manual'),
				'category' => 'introduction',
				'type'     => 'Standalone Page',
				'content'  => sprintf( get_filler_text( 'info_page' ), TD_('Manual') ),
			);
			break;

		// =======================================================================================================
		case 'group':
			// Tracker

			// Sample categories:
			$categories = array(
				'bug'             => array( TD_('Bug'), 'order' => 10 ),
				'feature_request' => array( TD_('Feature Request'), 'order' => 20 ),
			);

			// Additional sample Items:
			$tasks = 'ABCDEFGHIJKLMNOPQRST';
			$priorities = array( 1, 2, 3, 4, 5 );
			$task_status = array( 1, 2 ); // New, In Progress

			// Check demo users if they can be assignee
			$allowed_assignee = array();
			foreach( $demo_users as $key => $demo_user )
			{
				if( $demo_user->check_perm( 'blog_can_be_assignee', 'edit', false, $blog_ID ) )
				{
					$allowed_assignee[] = $demo_user->ID;
				}
			}

			$top_demo_items = array();

			for( $i = 0, $j = 0, $k = 0, $m = 0; $i < 20; $i++ )
			{
				$top_demo_items['task_'.$tasks[$i]] = array(
					'title'    => sprintf( TD_('Task %s'), $tasks[$i] ),
					'tags'     => 'demo',
					'category' => 'bug',
					'content'  => '<p>'.sprintf( TD_('This is a demo task description for Task %s.'), $tasks[$i] ).'</p>',
					'priority' =>  $priorities[$j],
					'pst_ID'   => $task_status[$k],
				);

				if( $use_demo_user )
				{	// Assign task to allowed assignee:
					$top_demo_items['task_'.$tasks[$i]]['assigned_user_ID'] = $allowed_assignee[$m];
				}

				// Iterate through all priorities and repeat
				if( $j < ( count( $priorities ) - 1 ) )
				{
					$j++;
				}
				else
				{
					$j = 0;
				}

				// Iterate through all status and repeat
				if( $k < ( count( $task_status ) - 1 ) )
				{
					$k++;
				}
				else
				{
					$k = 0;
				}

				// Iterate through all allowed assignee, increment only if $i is odd
				if( $m < ( count( $allowed_assignee ) - 1 ) )
				{
					if( $i % 2 )
					{
						$m++;
					}
				}
				else
				{
					$m = 0;
				}
			}

			// Prepend additional sample Items before generic Items:
			$demo_items = array_merge( $top_demo_items, $demo_items );

			// Don't install the following demo Items:
			$exclude_demo_items = array(
					'mongolian_beef',
					'stuffed_peppers',
					'custom_fields_example',
					'another_custom_fields_example',
					'child_post_example',
				);
			break;
	}

	// Insert sample Categories and Items:
	if( ! empty( $categories ) )
	{	// If at least one category is defined for the collection:

		// Create sample Categories:
		create_demo_categories( $categories, $blog_ID );

		if( ( $first_category_ID = get_demo_category_ID( '#first#', $categories ) ) &&
		    ( $edited_Blog = & $BlogCache->get_by_ID( $blog_ID, false, false ) ) )
		{	// Use first category as default:
			$edited_Blog->set_setting( 'default_cat_ID', $first_category_ID );
			$edited_Blog->dbupdate();
		}

		if( ! empty( $exclude_demo_items ) )
		{	// Exclude demo items which must not be installed:
			foreach( $exclude_demo_items as $exclude_demo_item_key )
			{
				if( isset( $demo_items[ $exclude_demo_item_key ] ) )
				{
					unset( $demo_items[ $exclude_demo_item_key ] );
				}
			}
		}

		// Create sample Items:
		$item_timestamp_array = get_post_timestamp_data( count( $demo_items ) );
		$item_i = 0;
		foreach( $demo_items as $demo_item_key => $demo_item )
		{
			$item_type = isset( $demo_item['type'] ) ? $demo_item['type'] : '#';

			if( ! is_available_item_type( $blog_ID, $item_type ) )
			{	// Skip not supported Item Type:
				$error_messages[] = sprintf( TB_('Unable to create demo post "%s"'), $demo_item['title'] ).': '.sprintf( TB_('The required %s is not found.'), TD_('Item Type') );
				continue;
			}

			if( ! ( $category_ID = get_demo_category_ID( $demo_item['category'], $categories ) ) )
			{	// Skip Item without category:
				$error_messages[] = sprintf( TB_('Unable to create demo post "%s"'), $demo_item['title'] ).': '.sprintf( TB_('The required %s is not found.'), TD_('Category') );
				continue;
			}

			// Set extra categories:
			$extra_cats_IDs = array();
			if( ! empty( $demo_item['extra_cats'] ) )
			{
				foreach( $demo_item['extra_cats'] as $extra_cat )
				{
					if( ! is_array( $extra_cat ) &&
					    ( $extra_cat_ID = get_demo_category_ID( $extra_cat, $categories ) ) )
					{
						$extra_cats_IDs[] = $extra_cat_ID;
					}
				}
			}

			$new_Item = new Item();

			if( ! empty( $demo_item['featured'] ) )
			{	// Mark the Item as featured:
				$new_Item->set( 'featured', 1 );
			}

			if( ! empty( $demo_item['hide_teaser'] ) )
			{	// Set setting "Hide Teaser":
				$new_Item->set_setting( 'hide_teaser', 1 );
			}

			if( ! empty( $demo_item['tags'] ) )
			{	// Set tags:
				$new_Item->set_tags_from_string( $demo_item['tags'] );
			}

			if( ! empty( $demo_item['custom_fields'] ) )
			{	// Set custom fields:
				foreach( $demo_item['custom_fields'] as $custom_field )
				{
					$new_Item->set_custom_field( $custom_field[0], $custom_field[1] );
				}
			}

			if( ! empty( $demo_item['parent_ID'] ) )
			{	// Set parent ID:
				if( $parent_ID = replace_demo_content_vars( $demo_item['parent_ID'], $demo_items, $demo_vars ) )
				{
					$new_Item->set( 'parent_ID', $parent_ID );
				}
				else
				{
					$error_messages[] = sprintf( TB_('Unable to create demo post "%s"'), $demo_item['title'] ).': '.TB_('The parent of this child post does not exist.');
					continue;
				}
			}

			if( ! empty( $demo_item['priority'] ) )
			{	// Set task priority:
				$new_Item->set( 'priority', $demo_item['priority'] );
			}
			if( ! empty( $demo_item['pst_ID'] ) )
			{	// Set task status:
				$new_Item->set( 'pst_ID', $demo_item['pst_ID'] );
			}
			if( ! empty( $demo_item['assigned_user_ID'] ) )
			{	// Set task assigned user ID:
				$new_Item->set( 'assigned_user_ID', $demo_item['assigned_user_ID'] );
			}
			if( isset( $demo_item['mustread'] ) && $demo_item['mustread'] === true )
			{	// Set "Must read" flag:
				$new_Item->set_setting( 'mustread', 1 );
			}

			if( isset( $demo_item['settings'] ) && is_array( $demo_item['settings'] ) )
			{	// Additional settings:
				foreach( $demo_item['settings'] as $demo_item_setting_key => $demo_item_setting_value )
				{
					$new_Item->set_setting( $demo_item_setting_key, $demo_item_setting_value );
				}
			}

			$item_date = date( 'Y-m-d H:i:s', $item_timestamp_array[ $item_i++ ] );

			// Insert new Item:
			$insert_new_item_result = $new_Item->insert(
				$owner_ID,
				$demo_item['title'],
				empty( $demo_item['content'] ) ? '' : replace_demo_content_vars( $demo_item['content'], $demo_items, $demo_vars ),
				$item_date,
				$category_ID,
				$extra_cats_IDs,
				'published',
				isset( $demo_item['locale'] ) ? $demo_item['locale'] : '#',
				isset( $demo_item['slug'] ) ? $demo_item['slug'] : '',
				isset( $demo_item['url'] ) ? $demo_item['url'] : '',
				isset( $demo_item['comment_status'] ) ? $demo_item['comment_status'] : 'open',
				array( 'default' ),
				$item_type,
				NULL,
				isset( $demo_item['order'] ) ? $demo_item['order'] : NULL,
				false );

			if( ! $insert_new_item_result )
			{	// Skip next code if Item could not be inserted successfully:
				$error_messages[] = sprintf( TB_('Unable to create demo post "%s"'), $demo_item['title'] );
				continue;
			}

			// Set Item ID after creating:
			$demo_items[ $demo_item_key]['ID'] = $new_Item->ID;

			$update_new_item = false;
			if( ! empty( $demo_item['files'] ) )
			{	// Attach files to the Item:
				$LinkOwner = new LinkItem( $new_Item );
				foreach( $demo_item['files'] as $f => $demo_item_file )
				{
					$new_File = new File( 'shared', 0, $demo_item_file[0] );
					if( $new_File->exists() )
					{
						$new_file_link_ID = $new_File->link_to_Object( $LinkOwner, $f + 1, ( isset( $demo_item_file[1] ) ? $demo_item_file[1] : NULL ) );
						if( isset( $demo_item_file['custom_field'] ) )
						{	// Update custom field with new linked file:
							$new_Item->set_custom_field( $demo_item_file['custom_field'], $new_file_link_ID );
							$update_new_item = true;
						}
						if( isset( $demo_item_file['set_var'] ) )
						{	// Set var which may be used for next inserted Items:
							$demo_vars[ $demo_item_file['set_var'] ] = $new_file_link_ID;
						}
					}
					else
					{
						$error_messages[] = sprintf( TD_('Missing attachment!').' '.TB_('File <code>%s</code> not found.'), $demo_item_file[0] );
					}
				}
			}

			if( ! empty( $demo_item['update_content'] ) )
			{	// Update content after insert with new var liek Link IDs:
				$new_Item->set( 'content', replace_demo_content_vars( $demo_item['update_content'], $demo_items, $demo_vars ) );
				$new_Item->dbupdate();
				if( $demo_item_key == 'bus_stop_ahead' )
				{
					echo_install_log( 'TEST FEATURE: Adding examples for plugin "Info dots renderer" on item #'.$new_Item->ID );
				}
			}

			if( $update_new_item )
			{
				$new_Item->dbupdate();
			}

			if( ! empty( $demo_item['widget_info_page'] ) )
			{	// Update global variable which may be used on install default widgets:
				$installed_collection_info_pages[ $demo_item['widget_info_page'] ] = $new_Item->ID;
			}

			switch( $item_type )
			{
				case 'Terms & Conditions':
					// Use the Item as default terms & conditions:
					$Settings->set( 'site_terms', $new_Item->ID );
					$Settings->dbupdate();
					break;

				case '#':
				case 'Post with Custom Fields':
				case 'Child Post':
				case '$recipe$':
				case 'Recipe':
				case 'Intro-Cat':
					// Insert default comments only for Items with these Item Types:
					$comment_item_IDs[] = array( $new_Item->ID, $item_date );
					break;
			}


		}
	}

	// Create demo comments
	$comment_users = array_values( $demo_users );
	if( count( $comment_users ) === 1 )
	{	// Only 1 demo user, use anonymous users:
		$comment_users = NULL;
	}
	foreach( $comment_item_IDs as $item_ID )
	{
		$comment_timestamp = strtotime( $item_ID[1] );
		adjust_timestamp( $comment_timestamp, 30, 720 );
		create_demo_comment( $item_ID[0], $comment_users, 'published', $comment_timestamp );
		adjust_timestamp( $comment_timestamp, 30, 720 );
		create_demo_comment( $item_ID[0], $comment_users, NULL, $comment_timestamp );
	}

	if( $install_test_features && count( $additional_comments_item_IDs ) && $use_demo_user )
	{	// Create the additional comments when we install all features
		foreach( $additional_comments_item_IDs as $additional_comments_item_ID )
		{
			// Restrict comment status by parent item:
			$comment_status = 'published';
			$Comment = new Comment();
			$Comment->set( 'item_ID', $additional_comments_item_ID );
			$Comment->set( 'status', $comment_status );
			$Comment->restrict_status( true );
			$comment_status = $Comment->get( 'status' );

			foreach( $demo_users as $demo_user )
			{	// Insert the comments from each user
				$now = date( 'Y-m-d H:i:s' );
				$DB->query( 'INSERT INTO T_comments( comment_item_ID, comment_status, comment_author_user_ID, comment_author_IP,
						comment_date, comment_last_touched_ts, comment_content, comment_renderers, comment_notif_status, comment_notif_flags )
						VALUES( '.$DB->quote( $additional_comments_item_ID ).', '.$DB->quote( $comment_status ).', '.$DB->quote( $demo_user->ID ).', "127.0.0.1", '
						.$DB->quote( $now ).', '.$DB->quote( $now ).', '.$DB->quote( TD_('Hi!

This is a sample comment that has been approved by default!
Admins and moderators can very quickly approve or reject comments from the collection dashboard.') ).', "default", "finished", "moderators_notified,members_notified,community_notified" )' );
			}
		}
		echo_install_log( 'TEST FEATURE: Creating additional comments on items ('.implode( ', ', $additional_comments_item_IDs ).')' );
	}
}


/**
 * Replace vars in demo content
 *
 * @param string Content
 * @param array Items
 * @param array Vars
 * @return string Content with replaced vars
 */
function replace_demo_content_vars( $content, $items, $vars )
{
	if( strpos( $content, '#get_' ) === false )
	{	// No vars in content:
		return $content;
	}

	if( preg_match_all( '/#get_item#([a-z0-9_]+)#(([a-z_]+)#)?/i', $content, $matches ) )
	{	// Item field var:
		foreach( $matches[0] as $m => $match )
		{
			$content = str_replace( $match, ( isset( $items[ $matches[1][$m] ][ $matches[3][$m] ] ) ? $items[ $matches[1][$m] ][ $matches[3][$m] ] : '' ), $content );
		}
	}

	if( preg_match_all( '/#get_var#([a-z0-9_]+)#/i', $content, $matches ) )
	{	// Demo var:
		foreach( $matches[0] as $m => $match )
		{
			$content = str_replace( $match, ( isset( $vars[ $matches[1][$m] ] ) ? $vars[ $matches[1][$m] ] : '' ), $content );
		}
	}

	return $content;
}


/**
 * Create demo categories from config array
 *
 * @param array Categories, Update category ID by reference after successful inserting
 * @param integer Collection ID
 * @param integer Parent category ID
 */
function create_demo_categories( & $categories, $blog_ID, $parent_cat_ID = 'NULL' )
{
	if( empty( $categories ) )
	{	// No categories:
		return;
	}

	foreach( $categories as $cat_key => $cat_data )
	{
		if( ! is_array( $cat_data ) )
		{
			$cat_data = array( $cat_data );
			$categories[ $cat_key ] = $cat_data;
		}

		$default_item_type = ( isset( $cat_data['default_item_type'] ) ? $cat_data['default_item_type'] : NULL );
		$cat_ID = cat_create(
			$cat_data[0],
			$parent_cat_ID,
			$blog_ID,
			NULL,
			true,
			isset( $cat_data['order'] ) ? $cat_data['order'] : NULL,
			isset( $cat_data['subcat_ordering'] ) ? $cat_data['subcat_ordering'] : NULL,
			isset( $cat_data['meta'] ) ? $cat_data['meta'] : false,
			$default_item_type );

		if( $cat_ID )
		{	// If category was inserted successfully:
			$categories[ $cat_key ]['ID'] = $cat_ID;
			if( ! empty( $cat_data['subs'] ) )
			{	// Create sub categories recursively:
				create_demo_categories( $categories[ $cat_key ]['subs'], $blog_ID, $cat_ID );
			}
		}
	}
}


/**
 * Get category ID by key/indeex
 *
 * @param string Category key/index
 * @param array Categories
 * @return integer|false Category ID of the found or first of the given array
 */
function get_demo_category_ID( $category_key, $categories, $use_first = true )
{
	$first_cat_ID = false;
	foreach( $categories as $cat_key => $cat_data )
	{
		if( $use_first && $first_cat_ID === false && ! empty( $cat_data['ID'] ) )
		{	// Set first category ID for case if no requested category will be found below:
			if( empty( $cat_data['meta'] ) )
			{	// If first category is not meta:
				$first_cat_ID = $cat_data['ID'];
			}
			elseif( ! empty( $cat_data['subs'] ) &&
		        ( $first_sub_cat_ID = get_demo_category_ID( $category_key, $cat_data['subs'] ) ) )
			{	// Get first category from subcategories of the root meta category:
				$first_cat_ID = $first_sub_cat_ID;
			}
			if( $first_cat_ID && $category_key == '#first#' )
			{	// Don't search next when only first category ID is requested:
				return $first_cat_ID;
			}
		}


		if( $category_key == $cat_key && ! empty( $cat_data['ID'] ) )
		{	// Return ID of the detected category:
			return $cat_data['ID'];
		}
		elseif( ! empty( $cat_data['subs'] ) &&
		        ( $sub_cat_ID = get_demo_category_ID( $category_key, $cat_data['subs'], false ) ) )
		{	// Return ID of the detected category in sub categories:
			return $sub_cat_ID;
		}
	}

	return $first_cat_ID;
}


/**
 * Create a demo poll
 *
 * @return integer ID of of created poll
 */
function create_demo_poll()
{
	global $DB;

	$demo_users = get_demo_users( false );
	$max_answers = 3;

	$demo_question = TD_('What are your favorite b2evolution feature?');

	// Check if there is already a demo poll:
	$demo_poll_ID = $DB->get_var( 'SELECT pqst_ID FROM T_polls__question WHERE pqst_question_text = '.$DB->quote( $demo_question ) );

	if( empty( $demo_poll_ID ) )
	{
		// Add poll question:
		$result = $DB->query( 'INSERT INTO T_polls__question ( pqst_owner_user_ID, pqst_question_text, pqst_max_answers )
			VALUES ( 1, '.$DB->quote( $demo_question ).', '.$max_answers.' )' );

		if( $result )
		{
			$demo_poll_ID = $DB->insert_id;

			// Add poll answers:
			$answer_texts = array(
					array( TD_('Multiple blogs'), 1 ),
					array( TD_('Photo Galleries'), 2 ),
					array( TD_('Forums'), 3 ),
					array( TD_('Online Manuals'), 4 ),
					array( TD_('Lists / E-mailing'), 5 ),
					array( TD_('Easy Maintenance'), 6 )
				);

			$answer_IDs = array();
			foreach( $answer_texts as $answer_text )
			{
				$DB->query( 'INSERT INTO T_polls__option ( popt_pqst_ID, popt_option_text, popt_order )
						VALUES ( '.$demo_poll_ID.', '.$DB->quote( $answer_text[0] ).', '.$DB->quote( $answer_text[1] ).' )' );
				$answer_IDs[] = $DB->insert_id;
			}

			// Generate answers:
			$insert_values = array();
			foreach( $demo_users as $demo_user )
			{
				$answers = $answer_IDs;
				for( $i = 0; $i < $max_answers; $i++ )
				{
					$rand_key = array_rand( $answers );
					$insert_values[] = '( '.$demo_poll_ID.', '.$demo_user->ID.', '.$answers[$rand_key].' )';
					unset( $answers[$rand_key] );
				}
			}
			if( $insert_values )
			{
				$DB->query( 'INSERT INTO T_polls__answer ( pans_pqst_ID, pans_user_ID, pans_popt_ID )
					VALUES '.implode( ', ', $insert_values ) );
			}
		}
	}

	return $demo_poll_ID;
}


/**
 * This is called installs in the backoffice and fills the tables with
 * demo/tutorial things.
 *
 * @return integer Number of collections installed
 */
function install_demo_content()
{
	global $DB, $current_User;
	global $install_test_features;

	$create_sample_contents   = param( 'create_sample_contents', 'string', false, true );   // during auto install this param can be 'full', 'minisite', 'blog-a', 'blog-b', 'photos, 'forums', 'manual', 'tracker'
	$create_demo_organization = param( 'create_demo_organization', 'boolean', false, true );
	$create_demo_users        = param( 'create_demo_users', 'boolean', false, true );
	$create_demo_messages     = param( 'create_sample_private_messages', 'boolean', false, true );
	$create_demo_email_lists  = param( 'create_demo_email_lists', 'boolean', false );
	$install_test_features    = param( 'install_test_features', 'boolean', false );

	$user_org_IDs = NULL;

	$DB->begin();
	if( $create_demo_organization )
	{
		echo get_install_format_text_and_log( '<h2>'.TB_('Creating demo organization and users...').'</h2>', 'h2' );
		evo_flush();

		if( $create_demo_organization )
		{
			task_begin( TB_('Creating demo organization...') );
			if( $new_demo_organization = create_demo_organization( $current_User->ID ) )
			{
				$user_org_IDs = array( $new_demo_organization->ID );
				task_end();
			}
			else
			{
				task_end( '<span class="text-danger">'.TB_('Failed').'.</span>' );
			}

			if( $user_org_IDs )
			{
				task_begin( TB_('Adding admin user to demo organization...') );
				$current_User->update_organizations( $user_org_IDs, array( 'King of Spades' ), array( 0 ), true );
				task_end();
			}
		}
	}

	$demo_users = get_demo_users( $create_demo_users );

	if( $create_demo_users && $create_demo_messages )
	{
		task_begin( TB_('Creating demo private messages...') );
		$demo_messages = create_demo_messages();
		if( $demo_messages )
		{
			task_end();
		}
		else
		{
			task_end( '<span class="text-danger">'.TB_('Failed').'.</span>' );
		}
	}

	$collections_installed = 0;
	$emails_data_installed = 0;
	if( $create_sample_contents || $create_demo_email_lists )
	{
		echo get_install_format_text_and_log( '<h2>'.TB_('Creating demo website...').'</h2>', 'h2' );
	}

	if( $create_sample_contents )
	{
		evo_flush();
		$collections_installed = create_demo_contents( $demo_users, true, false );
	}

	if( $create_demo_email_lists )
	{	// Create demo emails data like lists, campaigns, automations:
		$emails_data_installed = create_demo_emails();
	}

	if( $collections_installed || $emails_data_installed )
	{
		evo_flush();
		echo '<br/>';
		echo get_install_format_text_and_log( '<span class="text-success">'.TB_('Demo elements successfully created.').'</span>' );

		if( $collections_installed )
		{	// Display button to view website if at least one collection was created:
			global $baseurl;
			echo '<br/><br/><a href="'.$baseurl.'" class="btn btn-info">'.TB_('View website now').' &gt;&gt;</a>';
		}
	}

	$DB->commit();

	return $collections_installed;
}


/**
 * Print out log text on screen
 *
 * @param string Log text
 * @param string Log type: 'warning', 'note', 'success', 'danger'
 */
function echo_install_log( $text, $type = 'warning' )
{
	echo '<p class="alert alert-'.$type.'">'.$text.'</p>';
}
?>
