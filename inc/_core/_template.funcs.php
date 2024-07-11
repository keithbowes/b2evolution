<?php
/**
 * This file implements misc functions that handle output of the HTML page.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Template tag. Output content-type header
 *
 * @param string content-type; override for RSS feeds
 */
function header_content_type( $type = 'text/html', $charset = '#' )
{
	global $io_charset;
	global $content_type_header;

	$content_type_header = 'Content-type: '.$type;

	if( !empty($charset) )
	{
		if( $charset == '#' )
		{
			$charset = $io_charset;
		}

		$content_type_header .= '; charset='.$charset;
	}

	header( $content_type_header );
}


/**
 * This is a placeholder for future development.
 *
 * @param string content-type; override for RSS feeds
 * @param integer seconds
 * @param string charset
 * @param boolean flush already collected content from the PageCache
 */
function headers_content_mightcache( $type = 'text/html', $max_age = '#', $charset = '#', $flush_pagecache = true )
{
	global $current_User, $is_admin_page;
	global $Debuglog, $Messages, $PageCache;

	header_content_type( $type, $charset );

	if( empty($max_age) || $is_admin_page || (is_logged_in() && $current_User->check_perm('admin', 'restricted')) || $Messages->count() )
	{
		// Don't cache if max_age is given as 0 (for error messages)
		// + NEVER EVER allow admin pages to be cached
		// + NEVER EVER allow logged-in admins to be cached
		// + NEVER EVER allow transactional Messages to be cached!:
		header_cache('nocache');

		// Check server caching too, but note that this is a different caching process then caching on the client
		// It's important that this is a double security check only and server caching should be prevented before this
		// If something should not be cached on the client, it should never be cached on the server either
		if( !empty( $PageCache ) )
		{ // Abort PageCache collect
			$Debuglog->add( 'Abort server caching in headers_content_mightcache() function. This should have been prevented!' );
			$PageCache->abort_collect( $flush_pagecache );
		}
		return;
	}

	// If we are on a "normal" page, we may, under some circumstances, tell the browser it can cache the data.
	// This MAY be extremely confusing though, every time a user logs in and gets back to a screen with no evobar!
	// This cannot be enabled by default and requires admin switches.

	// For feeds, it is a little bit less confusing. We might want to have the param enabled by default in that case.

	// WARNING: extra special care needs to be taken before ever caching a blog page that might contain a form or a comment preview
	// having user details cached would be extremely bad.

	// in the meantime...
	header_cache();
}


/**
 * Get URL to return
 *
 * @return string URL
 */
function get_returnto_url()
{
	global $Hit, $Blog, $baseurl, $ReqHost;

	// See if there's a redirect_to request param given:
	$redirect_to = param( 'redirect_to', 'url', '' );

	if( empty( $redirect_to ) )
	{	// If default redirect_to param is not defined:
		if( ! empty( $Hit->referer ) )
		{	// Use referer page:
			$redirect_to = $Hit->referer;
		}
		elseif( isset( $Blog ) && is_object( $Blog ) )
		{	// Use collection default page URL:
			$redirect_to = $Blog->get( 'url' );
		}
		else
		{	// Use base URL:
			$redirect_to = $baseurl;
		}
	}
	elseif( $redirect_to[0] == '/' )
	{	// relative URL, prepend current host:
		$redirect_to = $ReqHost.$redirect_to;
	}

	return $redirect_to;
}


/**
 * Check if the requested URL is internal system URL
 * (base URL or URL of one collection from this system)
 *
 * @param string URL
 * @return boolean
 */
function is_internal_url( $url )
{
	global $Blog, $basehost, $baseurl;

	if( strpos( $url, $baseurl ) === 0 ||
	    strpos( $url, force_https_url( $baseurl ) ) === 0 ||
	    ( ! empty( $Blog ) && strpos( $url, $Blog->gen_baseurl() ) === 0 ) ||
	    ( ! empty( $Blog ) && strpos( $url, force_https_url( $Blog->gen_baseurl() ) ) === 0 ) )
	{	// The URL is base URL or URL of current collection:
		return true;
	}

	$url_domain = preg_replace( '~(https?://|//)([^/]+)/?.*~i', '$2', $url );

	if( preg_match( '~\.'.preg_quote( $basehost, '~' ).'(:\d+)?$~', $url_domain ) )
	{	// The URL goes to a subdomain of basehost:
		return true;
	}

	// Check if URL domain is used as absolute URL for at least 1 collection on the system:
	global $DB;

	$abs_url_coll_SQL = new SQL( 'Find collection with absolute URL by requested URL domain' );
	$abs_url_coll_SQL->SELECT( 'blog_ID' );
	$abs_url_coll_SQL->FROM( 'T_blogs' );
	$abs_url_coll_SQL->WHERE( 'blog_access_type = "absolute"' );
	$abs_url_coll_SQL->WHERE_and( 'blog_siteurl LIKE '.$DB->quote( '%://'.str_replace( '_', '\_', $url_domain.'/%' ) ) );
	$abs_url_coll_SQL->LIMIT( '1' );
	$abs_url_coll_ID = $DB->get_var( $abs_url_coll_SQL );

	// If at least one collection has the same domain as requested URL:
	return ! empty( $abs_url_coll_ID );
}


/**
 * Sends HTTP header to redirect to the previous location (which can be given as function parameter, GET parameter (redirect_to),
 * is taken from {@link Hit::$referer} or {@link $baseurl}).
 *
 * {@link $Debuglog} and {@link $Messages} get stored in {@link $Session}, so they are available after the redirect.
 *
 * @todo fp> do NOT allow $redirect_to = NULL. This leads to spaghetti code and unpredictable behavior.
 *
 * @return boolean false IF blocked AND $return_to_caller_if_forbidden BUT most of the time, this function {@link exit() exits} the php script execution.
 * @param string Destination URL to redirect to
 * @param boolean|integer is this a permanent redirect? if true, send a 301; otherwise a 303 OR response code 301,302,303
 * @param boolean is this a redirected post display? This param may be true only if we should redirect to a post url where the post status is 'redirected'!
 * @param boolean do we want to return to the caller if the redirect is forbidden? (useful when trying to redirect after post edit)
 */
function header_redirect( $redirect_to = NULL, $status = false, $redirected_post = false, $return_to_caller_if_forbidden = false )
{
	/**
	 * put your comment there...
	 *
	 * @var Hit
	 */
	global $Hit;
	global $baseurl, $Collection, $Blog, $htsrv_url, $ReqHost, $ReqURL, $dispatcher;
	global $Session, $Debuglog, $Messages, $debug;
	global $http_response_code, $allow_redirects_to_different_domain;

	if( empty( $redirect_to ) )
	{	// Use automatic return URL if a redirect URL is not defined:
		$redirect_to = get_returnto_url();
	}

	// Keep ONLY allowed params from current URL by config:
	$redirect_to = url_keep_params( $redirect_to );

	$Debuglog->add('Preparing to redirect to: '.$redirect_to, 'request' );

	// Determine if this is an external or internal redirect:

	$external_redirect = true; // Start with worst case, then whitelist:

	if( $redirect_to[0] == '/' || $redirect_to[0] == '?' )
	{ // We stay on the same domain or same page:
		$external_redirect = false;
	}
	elseif( strpos( $redirect_to, $dispatcher ) === 0 )
	{ // $dispatcher is DEPRECATED and pages should use $admin_url URL instead, but at least we're staying on the same site:
		$external_redirect = false;
	}
	elseif( strpos( $redirect_to, $baseurl ) === 0 )
	{
		$Debuglog->add('Redirecting within $baseurl, all is fine.', 'request' );
		$external_redirect = false;
	}
	elseif( strpos( $redirect_to, force_https_url( $baseurl ) ) === 0 )
	{	// Protocol https may be forced for all login, registration and etc. pages:
		$Debuglog->add('Redirecting within https of $baseurl, all is fine.', 'request' );
		$external_redirect = false;
	}
	elseif( ! empty( $Blog ) && strpos( $redirect_to, $Blog->gen_baseurl() ) === 0 )
	{
		$Debuglog->add( 'Redirecting within current collection URL, all is fine.', 'request' );
		$external_redirect = false;
	}
	elseif( ! empty( $Blog ) && strpos( $redirect_to, force_https_url( $Blog->gen_baseurl() ) ) === 0 )
	{	// Protocol https may be forced for all login, registration and etc. pages:
		$Debuglog->add('Redirecting within https of current collection URL, all is fine.', 'request' );
		$external_redirect = false;
	}


	// Remove login and pwd parameters from URL, so that they do not trigger the login screen again (and also as global security measure):
	$redirect_to = preg_replace( '~(?<=\?|&) (login|pwd) = [^&]+ ~x', '', $redirect_to );

	if( $external_redirect == false )
	{	// (blueyed>) Remove "confirm(ed)?" from redirect_to so it doesn't do the same thing twice
		// TODO: fp> confirm should be normalized to confirmed
		$redirect_to = preg_replace( '~(?<=\?|&) (confirm(ed)?) = [^&]+ ~x', '', $redirect_to );
	}


	$allow_collection_redirect = false;

	if( $external_redirect
		&& $allow_redirects_to_different_domain == 'all_collections_and_redirected_posts'
		&& ! $redirected_post )
	{	// If a redirect is external and we allow to redirect to all collection domains:
		$allow_collection_redirect = is_internal_url( $redirect_to );
	}

	// Check if we're trying to redirect to an external URL:
	if( $external_redirect // Attempting external redirect
		&& ( $allow_redirects_to_different_domain != 'always' ) // Always allow redirects to different domains is not set
		&& ( ! $allow_collection_redirect ) // This is not a redirect to collection domain of this site
		&& ( ! ( in_array( $allow_redirects_to_different_domain, array( 'all_collections_and_redirected_posts', 'only_redirected_posts' ) ) && $redirected_post ) ) ) // This is not a 'redirected' post display request
	{ // Force header redirects into the same domain. Do not allow external URLs.
		$Messages->add( T_('A redirection to an external URL was blocked for security reasons.'), 'error' );
		syslog_insert( 'A redirection to an external URL '.$redirect_to.' was blocked for security reasons.', 'error', NULL );
		if( $return_to_caller_if_forbidden )
		{	// Return to caller meaning we did not redirect:
			return false;
		}
		$redirect_to = $baseurl;
	}

	// Send the predefined cookies:
	evo_sendcookies();

	if( is_integer($status) )
	{
		$http_response_code = $status;
	}
	else
	{
		$http_response_code = $status ? 301 : 303;
	}
	$Debuglog->add('***** REDIRECT TO '.$redirect_to.' (status '.$http_response_code.') *****', 'request' );

	if( ! empty($Session) )
	{	// Session is required here
		if( ! empty( $debug ) )
		{	// Transfer full debug info to next page only when debug is enabled:
			ob_start();
			debug_info( true );
			$current_debug_info = ob_get_clean();
			if( ! empty( $current_debug_info ) )
			{	// Save full debug info into Session, so that it's available after redirect (gets loaded by Session constructor):
				$sess_debug_infos = $Session->get( 'debug_infos' );
				if( empty( $sess_debug_infos ) )
				{
					$sess_debug_infos = array();
				}
				// NOTE: We must encode data in order to avoid error "Session data corrupted" because of special chars on unserialize the data:
				$sess_debug_infos[] = gzencode( $current_debug_info );
				$Session->set( 'debug_infos', $sess_debug_infos, 60 /* expire in 60 seconds */ );
			}
		}

		// Transfer of Messages to next page:
		if( $Messages->count() )
		{	// Set Messages into user's session, so they get restored on the next page (after redirect):
			$Session->set( 'Messages', $Messages );
		 // echo 'Passing Messages to next page';
		}

		$Session->dbsave(); // If we don't save now, we run the risk that the redirect goes faster than the PHP script shutdown.
	}

	// see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
	switch( $http_response_code )
	{
		case 301:
			// This should be a permanent move redirect!
			header_http_response( '301 Moved Permanently' );
			break;

		case 303:
			// This should be a "follow up" redirect
			// Note: Also see http://de3.php.net/manual/en/function.header.php#50588 and the other comments around
			header_http_response( '303 See Other' );
			break;

		case 302:
		default:
			header_http_response( '302 Found' );
	}

	if( $debug &&
	    ! empty( $ReqHost ) &&
	    strpos( $redirect_to, $ReqHost ) !== 0 )
	{	// Append param to redirect from different domain in order to see debug info of the current page after redirect:
		$redirect_to = url_add_param( $redirect_to, 'get_redirected_debuginfo_from_sess_ID='.$Session->ID, '&' );
	}

	// debug_die($redirect_to);
	if( headers_sent($filename, $line) )
	{
		debug_die( sprintf('Headers have already been sent in %s on line %d.', basename($filename), $line)
						.'<br />Cannot <a href="'.htmlspecialchars($redirect_to).'">redirect</a>.' );
	}
	header( 'Location: '.$redirect_to, true, $http_response_code ); // explictly setting the status is required for (fast)cgi
	exit(0);
}


/**
 * Redirect to URL with additional checking from email log
 *
 * @param string Redirect URL
 * @param integer Header response status code: 301, 302, 303
 * @param string Email log content, NULL - if we need to get email log message from DB by email log ID and key
 * @param string Email log ID
 * @param string Email log key
 */
function header_redirect_from_email( $redirect_to, $status = false, $email_log_message = NULL, $email_log_ID = NULL, $email_log_key = NULL )
{
	global $baseurl;

	if( empty( $redirect_to ) )
	{	// Use base site URL for redirect if it is not provided:
		$redirect_to = $baseurl;
	}

	// 1) Try to redirect if it is allowed by config $allow_redirects_to_different_domain,
	// (Use $return_to_caller_if_forbidden = true in order to return false without redirect)
	$redirect_result = header_redirect( $redirect_to, $status, false, true );
	// May be EXITed here!

	// 2) Otherwise(when $redirect_result === false) use additional checking by email log:
	if( ! check_redirect_url_by_email_log( $redirect_to, $email_log_message, $email_log_ID, $email_log_key ) )
	{	// Deny redirect to URL what is not found in the email message:
		$redirect_to = $baseurl;
	}

	// Campaign author explicitly wanted to link to an external URL:
	// Use php function header() instead of b2evolution core function header_redirect(),
	// because we already used it above to redirect and it can prevent redirection depending
	// on some advanced settings like $allow_redirects_to_different_domain!
	header( 'Location: '.$redirect_to, true, $status ); // explictly setting the status is required for (fast)cgi
	exit(0);
}


/**
 * Sends HTTP headers specifying the right kind of caching
 * @param string The type of caching to perform (one of cache, etag, nocache, noexpire)
 * @param int Unix time stamp
 *
 * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
 */
function header_cache( $cache_type = 'cache', $timestamp = NULL )
{
	global $servertimenow;
	if( empty($timestamp) )
	{
		$timestamp = $servertimenow;
	}

	switch ($cache_type)
	{
		case 'cache':
			header('Last-Modified: ' . get_last_modified());
		case 'etag':
			header('Etag: ' . gen_current_page_etag());
			break;
		case 'nocache':
			header('Expires: '.gmdate('r',$timestamp));
			header('Last-Modified: '.gmdate('r',$timestamp));
			header('Cache-Control: no-cache, must-revalidate');
			header('Pragma: no-cache');
			break;
		case 'noexpire':
			header('Expires: '.gmdate('r', $timestamp + 31536000)); // 86400*365 (1 year)
			break;
	}
}


/**
 * Generate an etag to identify the version of the current page.
 * We use this primarily to make a difference between the same page that has been generated for anonymous users
 * and a version that has been generated for a specific user.
 *
 * A common problem without this would be that when users log out, the page cache would tell them "304 Not Modified"
 * based on the date of the cache and then the browser would show a locally cached version of the page that includes
 * the evobar.
 *
 * When a specific user logs out, the browser will send back the Etag of the logged in version it got and we will
 * be able to detect that this is not a "304 Not Modified" case -> we will send back the anonymous version of the page.
 */
function gen_current_page_etag()
{
	global $current_User, $Messages;

	if( isset($current_User) )
	{
		$etag = 'user:'.$current_User->ID;
	}
	else
	{
		$etag = 'user:anon';
	}

	if( $Messages->count() )
	{	// This case has never been observed yet, but let's forward protect us against client side cached messages
		$etag .= '-msg:'.md5($Messages->get_string('',''));
	}

	return '"'.md5(serialize(array('user' => $etag, 'last-modified' => get_last_modified()))).'"';
}


/**
 * Get global title matching filter params
 *
 * Outputs the title of the category when you load the page with <code>?cat=</code>
 * Display "Archive Directory" title if it has been requested
 * Display "Latest comments" title if these have been requested
 * Display "Statistics" title if these have been requested
 * Display "User profile" title if it has been requested
 *
 * @todo single month: Respect locales datefmt
 * @todo single post: posts do no get proper checking (wether they are in the requested blog or wether their permissions match user rights,
 * thus the title sometimes gets displayed even when it should not. We need to pre-query the ItemList instead!!
 * @todo make it complete with all possible params!
 *
 * @param array params
 *        - "auto_pilot": "seo_title": Use the SEO title autopilot. (Default: "none")
 */
function get_request_title( $params = array() )
{
	global $MainList, $preview, $disp, $action, $Collection, $Blog, $admin_url;

	$r = array();

	$params = array_merge( array(
			'auto_pilot'          => 'none', // This can be used to override several params at once. Possible value: 'seo_title'
			'title_before'        => '',
			'title_after'         => '',
			'title_none'          => '',
			'title_single_disp'   => true,
			'title_single_before' => '#',
			'title_single_after'  => '#',
			'title_page_disp'     => true,
			'title_page_before'   => '#',
			'title_page_after'    => '#',
			'title_widget_page_disp'   => false,	// We never want a title to be automatically displayed on a widget page.
			'title_widget_page_before' => '#',
			'title_widget_page_after'  => '#',
			'title_terms_disp'    => true,
			'title_terms_before'  => '#',
			'title_terms_after'   => '#',
			'glue'                => ' - ',
			'format'              => 'htmlbody',
			// Title for each disp:
			// fp> TODO: Make a global array of $disp => Clear text disp
			'anonpost_text'       => '#',// # - 'New [Post]' ('Post' is item type name)
			'arcdir_text'         => T_('Archive Directory'),
			'catdir_text'         => T_('Category Directory'),
			'mediaidx_text'       => T_('Photo Index'),
			'postidx_text'        => T_('Post Index'),
			'search_text'         => T_('Search'),
			'sitemap_text'        => T_('Site Map'),
			'msgform_text'        => T_('Contact'),
			'messages_text'       => T_('Messages'),
			'contacts_text'       => T_('Contacts'),
			'requires_login_text_seo' => T_('Login Required'),
			'login_text'          => /* TRANS: trailing space = verb */ T_('Login '),
			'register_text'       => T_('Register'),
			'register_finish_text'=> T_('Finish Registration'),
			'req_activate_email'  => T_('Account activation'),
			'account_activation'  => T_('Account activation'),
			'lostpassword_text'   => T_('Lost your password?'),
			'profile_text'        => T_('User Profile'),
			'avatar_text'         => T_('Profile picture'),
			'pwdchange_text'      => T_('Password change'),
			'userprefs_text'      => T_('User preferences'),
			'user_text'           => T_('User: %s'),
			'users_text'          => T_('Users'),
			'closeaccount_text'   => T_('Account closure'),
			'subs_text'           => T_('Notifications & Subscriptions'),
			'visits_text'         => T_('Who visited my profile?'),
			'comments_text'       => T_('Latest Comments'),
			'feedback-popup_text' => T_('Feedback'),
			'edit_comment_text'   => T_('Editing comment'),
			'front_text'          => '',		// We don't want to display a special title on the front page
			'posts_text'          => '#',		// Automatic - display filters
			'useritems_text'      => T_('User posts'),
			'usercomments_text'   => T_('User comments'),
			'download_head_text'  => T_('Download').' - $file_title$ - $post_title$',
			'download_body_text'  => '',
			// fp> TODO: verify if we really want this:
			'display_edit_links'  => true, // Display the links to advanced editing on disp=edit|edit_comment
			'edit_links_template' => array(), // More params for the links to advanced editing on disp=edit|edit_comment
			'tags_text'           => T_('Tags'),
			'flagged_text'        => T_('Flagged posts'),
			'mustread_text'       => T_('Must Read Items'),
			'help_text'           => T_('In case of issues with this site...'),
			'compare_text'           => /* TRANS: title for disp=compare */ T_('%s compared'),
			'compare_text_separator' => /* TRANS: title separator for disp=compare */ ' '.T_('vs').' ',
			'proposechange_text'     => T_('Propose change for Item %s'),
		), $params );

	if( $params['auto_pilot'] == 'seo_title' )
	{	// We want to use the SEO title autopilot. Do overrides:
		$params['format'] = 'htmlhead';
		$params['title_after'] = $params['glue'].$Blog->get('name');
		$params['title_single_after'] = '';
		$params['title_page_after'] = '';
		$params['title_none'] = $Blog->dget('name','htmlhead');
	}


	$before = $params['title_before'];
	$after = $params['title_after'];

	switch( $disp )
	{
		case 'front':
			// We are requesting a front page:
			if( !empty( $params['front_text'] ) )
			{
				$r[] = $params['front_text'];
			}
			break;

		case 'arcdir':
			// We are requesting the archive directory:
			$r[] = $params['arcdir_text'];
			break;

		case 'catdir':
			// We are requesting the archive directory:
			$r[] = $params['catdir_text'];
			break;

		case 'mediaidx':
			$r[] = $params['mediaidx_text'];
			break;

		case 'postidx':
			$r[] = $params['postidx_text'];
			break;

		case 'sitemap':
			$r[] = $params['sitemap_text'];
			break;

		case 'search':
			$r[] = $params['search_text'];
			break;

		case 'comments':
			// We are requesting the latest comments:
			global $Item;
			if( isset( $Item ) )
			{
				$r[] = sprintf( $params['comments_text'] . T_(' on %s'), $Item->get('title') );
			}
			else
			{
				$r[] = $params['comments_text'];
			}
			break;

		case 'feedback-popup':
			// We are requesting the comments on a specific post:
			// Should be in first position
			$Item = & $MainList->get_by_idx( 0 );
			$r[] = sprintf( $params['feedback-popup_text'] . T_(' on %s'), $Item->get('title') );
			break;

		case 'profile':
			// We are requesting the user profile:
			$r[] = $params['profile_text'];
			break;

		case 'avatar':
			// We are requesting the user avatar:
			$r[] = $params['avatar_text'];
			break;

		case 'pwdchange':
			// We are requesting the user change password:
			$r[] = $params['pwdchange_text'];
			break;

		case 'userprefs':
			// We are requesting the user preferences:
			$r[] = $params['userprefs_text'];
			break;

		case 'subs':
			// We are requesting the subscriptions screen:
			$r[] = $params['subs_text'];
			break;

		case 'register_finish':
			// We are requesting the register finish form:
			$r[] = $params['register_finish_text'];
			break;

		case 'visits':
			// We are requesting the profile visits screen:
			$user_ID = param( 'user_ID', 'integer', 0 );
			$r[] = $params['visits_text'];
			break;

		case 'msgform':
			// We are requesting the message form:
			$msgform_title = utf8_trim( $Blog->get_setting( 'msgform_title' ) );
			$r[] = empty( $msgform_title ) ? $params['msgform_text'] : $msgform_title;
			break;

		case 'threads':
		case 'messages':
			// We are requesting the messages form
			global $disp_detail;
			$thrd_ID = param( 'thrd_ID', 'integer', 0 );
			if( ! empty( $thrd_ID ) )
			{	// We get a thread title by ID
				load_class( 'messaging/model/_thread.class.php', 'Thread' );
				$ThreadCache = & get_ThreadCache();
				if( $Thread = $ThreadCache->get_by_ID( $thrd_ID, false ) )
				{	// Thread exists and we get a title
					if( $params['auto_pilot'] == 'seo_title' )
					{	// Display thread title only for tag <title>
						$r[] = $Thread->title;
						break;
					}
				}
			}

			if( $disp_detail == 'msgform' )
			{	// disp=msgform for logged in user:
				$msgform_title = utf8_trim( $Blog->get_setting( 'msgform_title' ) );
				$r[] = empty( $msgform_title ) ? $params['msgform_text'] : $msgform_title;
			}
			else
			{
				$r[] = strip_tags( $params['messages_text'] );
			}
			break;

		case 'contacts':
			// We are requesting the message form:
			$r[] = $params['contacts_text'];
			break;

		case 'access_requires_login':
		case 'content_requires_login':
			// We are requesting the login form when anonymous user has no access to the Collection:
			if( $params['auto_pilot'] == 'seo_title' )
			{	// Use text only for <title> tag in <head>:
				$r[] = $params['requires_login_text_seo'];
			}
			break;

		case 'login':
			// We are requesting the login form:
			if( $action == 'req_activate_email' )
			{
				$r[] = $params['req_activate_email'];
			}
			else
			{
				$r[] = $params['login_text'];
			}
			break;

		case 'register':
			// We are requesting the registration form:
			$r[] = $params['register_text'];
			break;

		case 'activateinfo':
			// We are requesting the activate info form:
			$r[] = $params['account_activation'];
			break;

		case 'lostpassword':
			// We are requesting the lost password form:
			$r[] = $params['lostpassword_text'];
			break;

		case 'single':
		case 'page':
		case 'widget_page':
		case 'terms':
			// We are displaying a single message:
			if( $preview )
			{	// We are requesting a post preview:
				$r[] = /* TRANS: Noun */ T_('PREVIEW');
			}
			elseif( $params['title_'.$disp.'_disp'] && isset( $MainList ) )
			{
				$r = array_merge( $r, $MainList->get_filter_titles( array( 'visibility', 'hide_future' ), $params ) );
			}
			if( $params['title_'.$disp.'_before'] != '#' )
			{
				$before = $params['title_'.$disp.'_before'];
			}
			if( $params['title_'.$disp.'_after'] != '#' )
			{
				$after = $params['title_'.$disp.'_after'];
			}
			break;

		case 'download':
			// We are displaying a download page:
			global $download_Link;

			$download_text = ( $params['format'] == 'htmlhead' ) ? $params['download_head_text'] : $params['download_body_text'];
			if( strpos( $download_text, '$file_title$' ) !== false )
			{ // Replace a mask $file_title$ with real file name
				$download_File = & $download_Link->get_File();
				$download_text = str_replace( '$file_title$', $download_File->get_name(), $download_text );
			}
			if( strpos( $download_text, '$post_title$' ) !== false )
			{ // Replace a mask $file_title$ with real file name
				$download_text = str_replace( '$post_title$', implode( $params['glue'], $MainList->get_filter_titles( array( 'visibility', 'hide_future' ) ) ), $download_text );
			}
			$r[] = $download_text;
			break;

		case 'user':
			// We are requesting the user page:
			$user_ID = param( 'user_ID', 'integer', 0 );
			$UserCache = & get_UserCache();
			$User = & $UserCache->get_by_ID( $user_ID, false, false );
			$user_login = $User ? $User->get( 'login' ) : '';
			$r[] = sprintf( $params['user_text'], $user_login );
			break;

		case 'users':
			$r[] = $params['users_text'];
			break;

		case 'closeaccount':
			$r[] = $params['closeaccount_text'];
			break;

		case 'anonpost':
			if( $params['anonpost_text'] == '#' )
			{	// Initialize default auto title:
				$new_Item = get_session_Item( 0, true );
				$r[] = sprintf( T_('New [%s]'), $new_Item->get_type_setting( 'name' ) );
			}
			else
			{	// Use custom title from param:
				$r[] = $params['anonpost_text'];
			}
			break;

		case 'edit':
			global $edited_Item;
			$type_name = $edited_Item->get_type_setting( 'name' );

			$action = param_action(); // Edit post by switching into 'In skin' mode from Back-office
			$p = param( 'p', 'integer', 0 ); // Edit post from Front-office
			$post_ID = param ( 'post_ID', 'integer', 0 ); // Update the edited post( If user is redirected to edit form again with some error messages )
			$cp = param( 'cp', 'integer', 0 ); // Copy post from Front-office
			if( $action == 'edit_switchtab' || $p > 0 || $post_ID > 0 )
			{	// Edit post
				$title = sprintf( T_('Edit [%s]'), $type_name );
			}
			else if( $cp > 0 )
			{	// Copy post
				$title = sprintf( T_('Duplicate [%s]'), $type_name );
			}
			else
			{	// Create post
				$title = sprintf( T_('New [%s]'), $type_name );
			}
			if( $params['display_edit_links'] && $params['auto_pilot'] != 'seo_title' )
			{ // Add advanced edit and close icon
				$params['edit_links_template'] = array_merge( array(
						'before'              => '<span class="title_action_icons">',
						'after'               => '</span>',
						'advanced_link_class' => '',
						'close_link_class'    => '',
					), $params['edit_links_template'] );

				global $edited_Item;
				if( !empty( $edited_Item ) && $edited_Item->ID > 0 )
				{ // Set the cancel editing url as permanent url of the item
					$cancel_url = $edited_Item->get_permanent_url();
				}
				else
				{ // Set the cancel editing url to home page of the blog
					$cancel_url = $Blog->gen_blogurl();
				}

				$title .= $params['edit_links_template']['before'];
				if( check_user_perm( 'admin', 'restricted' ) )
				{
					global $advanced_edit_link;
					$title .= action_icon( T_('Go to advanced edit screen'), 'edit', $advanced_edit_link['href'], ' '.T_('Advanced editing'), NULL, 3, array(
							'onclick' => $advanced_edit_link['onclick'],
							'class'   => $params['edit_links_template']['advanced_link_class'].' action_icon',
							'data-shortcut' => 'f2,ctrl+f2',
						) );
				}
				$title .= action_icon( T_('Cancel editing'), 'close', $cancel_url, ' '.T_('Cancel editing'), NULL, 3, array(
						'class' => $params['edit_links_template']['close_link_class'].' action_icon',
					) );
				$title .= $params['edit_links_template']['after'];
			}
			$r[] = $title;
			break;

		case 'proposechange':
			global $edited_Item;
			$r[] = sprintf( $params['proposechange_text'], '"'.$edited_Item->get_title().'"' );
			break;

		case 'edit_comment':
			global $comment_Item, $edited_Comment;
			$title = $params['edit_comment_text'];
			if( $params['display_edit_links'] && $params['auto_pilot'] != 'seo_title' )
			{ // Add advanced edit and close icon
				$params['edit_links_template'] = array_merge( array(
						'before'              => '<span class="title_action_icons">',
						'after'               => '</span>',
						'advanced_link_class' => '',
						'close_link_class'    => '',
					), $params['edit_links_template'] );

				$title .= $params['edit_links_template']['before'];
				if( check_user_perm( 'admin', 'restricted' ) )
				{
					$advanced_edit_url = url_add_param( $admin_url, 'ctrl=comments&amp;action=edit&amp;blog='.$Blog->ID.'&amp;comment_ID='.$edited_Comment->ID );
					$title .= action_icon( T_('Go to advanced edit screen'), 'edit', $advanced_edit_url, ' '.T_('Advanced editing'), NULL, 3, array(
							'onclick' => 'return switch_edit_view();',
							'class'   => $params['edit_links_template']['advanced_link_class'].' action_icon',
						) );
				}
				if( empty( $comment_Item ) )
				{
					$comment_Item = & $edited_Comment->get_Item();
				}
				if( !empty( $comment_Item ) )
				{
					$title .= action_icon( T_('Cancel editing'), 'close', url_add_tail( $comment_Item->get_permanent_url(), '#c'.$edited_Comment->ID ), ' '.T_('Cancel editing'), NULL, 3, array(
							'class' => $params['edit_links_template']['close_link_class'].' action_icon',
						) );
				}
				$title .= $params['edit_links_template']['after'];
			}
			$r[] = $title;
			break;

		case 'useritems':
			// We are requesting the user items list:
			$r[] = $params['useritems_text'];
			break;

		case 'usercomments':
			// We are requesting the user comments list:
			$r[] = $params['usercomments_text'];
			break;

		case 'tags':
			// We are requesting the tags directory:
			$r[] = $params['tags_text'];
			break;

		case 'flagged':
			// We are requesting the flagged posts list:
			$r[] = $params['flagged_text'];
			break;

		case 'mustread':
			// We are requesting the must read posts list:
			$r[] = $params['mustread_text'];
			break;

		case 'help':
			$r[] = $params['help_text'];
			break;

		case 'compare':
			// We are requesting the compare list:
			$items = trim( param( 'items', '/^[\d,]*$/' ), ',' );

			if( ! empty( $items ) )
			{	// It at least one item is selected to compare
				$items = explode( ',', $items );

				// Load all requested posts into the cache:
				$ItemCache = & get_ItemCache();
				$ItemCache->load_list( $items );

				$compare_item_titles = array();
				foreach( $items as $item_ID )
				{
					if( $Item = & $ItemCache->get_by_ID( $item_ID, false, false ) )
					{	// Use only existing Item:
						$compare_item_titles[] = $Item->get( 'title' );
					}
				}

				$r[] = sprintf( $params['compare_text'], implode( $params['compare_text_separator'], $compare_item_titles ) );
			}

			break;

		case 'posts':
			// We are requesting a posts page:
			if( $params['posts_text'] != '#' )
			{
				$r[] = $params['posts_text'];
				break;
			}
			// No break if empty, Use title from default case
		default:
			if( isset( $MainList ) )
			{
				$r = array_merge( $r, $MainList->get_filter_titles( array( 'visibility', 'hide_future', 'itemtype' ), $params ) );
			}
			break;
	}


	if( ! empty( $r ) )
	{	// We have at leats one title match:
		$r = implode( $params['glue'], $r );
		if( ! empty( $r ) )
		{	// This is in case we asked for an empty title (e-g for search)
			$r = $before.format_to_output( $r, $params['format'] ).$after;
		}
	}
	elseif( !empty( $params['title_none'] ) )
	{
		$r = $params['title_none'];
	}
	else
	{	// never return array()
		$r = '';
	}

	return $r;
}


/**
 * Display a global title matching filter params
 *
 * @param array params
 *        - "auto_pilot": "seo_title": Use the SEO title autopilot. (Default: "none")
 */
function request_title( $params = array() )
{
	$r = get_request_title( $params );

	if( !empty( $r ) )
	{ // We have something to display:
		echo $r;
	}
}


/**
 * Returns a "<base />" tag and remembers that we've used it ({@link regenerate_url()} needs this).
 *
 * @param string URL to use (this gets used as base URL for all relative links on the HTML page)
 * @return string
 */
function base_tag( $url, $target = NULL )
{
	global $base_tag_set;
	$base_tag_set = $url;
	echo '<base href="'.$url.'"';

	if( !empty($target) )
	{
		echo ' target="'.$target.'"';
	}
	echo " />\n";
}


/**
 * Robots tag
 *
 * Outputs the robots meta tag if necessary
 */
function robots_tag()
{
	global $robots_index, $robots_follow;

	if( is_null($robots_index) && is_null($robots_follow) )
	{
		return;
	}

	$r = '<meta name="robots" content="';

	if( $robots_index === false )
		$r .= 'NOINDEX';
	else
		$r .= 'INDEX';

	$r .= ',';

	if( $robots_follow === false )
		$r .= 'NOFOLLOW';
	else
		$r .= 'FOLLOW';

	$r .= '" />'."\n";

	echo $r;
}


/**
 * Output a link to current blog.
 *
 * We need this function because if no Blog is currently active (some admin pages or site pages)
 * then we'll go to the general home.
 */
function blog_home_link( $before = '', $after = '', $blog_text = 'Blog', $home_text = 'Home' )
{
	global $Collection, $Blog, $baseurl;

	if( !empty( $Blog ) )
	{
		echo $before.'<a href="'.$Blog->get( 'url' ).'">'.$blog_text.'</a>'.$after;
	}
	elseif( !empty($home_text) )
	{
		echo $before.'<a href="'.$baseurl.'">'.$home_text.'</a>'.$after;
	}
}


/**
 * Expose PHP variable to JS variable in order to print out them into <script> before </body>
 *
 * @param string Name
 * @param string Value
 */
function expose_var_to_js( $name, $value, $parent_object = NULL )
{
	global $evo_exposed_js_vars;

	if( ! is_array( $evo_exposed_js_vars ) )
	{	// Initialize array once:
		$evo_exposed_js_vars = array();
	}

	if( ! empty( $parent_object ) )
	{
		if( ! isset( $evo_exposed_js_vars[$parent_object] ) )
		{
			$evo_exposed_js_vars[$parent_object] = array();
		}

		$evo_exposed_js_vars[$parent_object][$name] = $value;
	}
	else
	{
		$evo_exposed_js_vars[ $name ] = $value;
	}
}


/**
 * Include ALL exposed JS variables into <script>
 */
function include_js_vars()
{
	global $evo_exposed_js_vars;

	if( empty( $evo_exposed_js_vars ) ||
	    ! is_array( $evo_exposed_js_vars ) )
	{	// No exposed JS variables found:
		return;
	}

	echo "<script>\n/* <![CDATA[ */\n";

	foreach( $evo_exposed_js_vars as $var_name => $var_value )
	{
		if( is_array( $var_value ) )
		{
			$var_value = evo_json_encode( $var_value, JSON_FORCE_OBJECT );
		}
		elseif( is_bool( $var_value ) )
		{
			$var_value = $var_value ? 'true' : 'false';
		}
		echo 'var '.$var_name.' = '.$var_value.";\n";
	}

	echo "\n/* ]]> */\n</script>";
}


/**
 * Get library url of JS or CSS file by file name or alias
 *
 * @param string File or Alias name
 * @param boolean|string 'relative' or true (relative to <base>),
 *                       'absolute'(for absolute url)
 *                       'rsc_url' (relative to $rsc_url),
 *                       'blog' (relative to current blog URL -- may be subdomain or custom domain)
 * @param string 'js' or 'css' or 'build'
 * @return string URL
 * @param string version number to append at the end of requested url to avoid getting an old version from the cache
 */
function get_require_url( $lib_file, $relative_to = 'rsc_url', $subfolder = 'js', $version = '#' )
{
	global $library_local_urls, $library_cdn_urls, $use_cdns, $debug, $rsc_url, $rsc_uri;
	global $Collection, $Blog, $baseurl, $assets_baseurl, $ReqURL;

	if( $relative_to == 'blog' && ( is_admin_page() || empty( $Blog ) ) )
	{	// Make sure we never use resource url relative to any blog url in case of an admin page ( important in case of multi-domain installations ):
		$relative_to = 'rsc_url';
	}

	// Check if we have a public CDN we want to use for this library file:
	if( $use_cdns && ! empty( $library_cdn_urls[ $lib_file ] ) )
	{ // Rewrite local urls with public CDN urls if they are defined in _advanced.php
		$library_local_urls[ $lib_file ] = $library_cdn_urls[ $lib_file ];
		// Don't append version for global CDN urls
		$version = NULL;
	}

	if( ! empty( $library_local_urls[ $lib_file ] ) )
	{ // We are requesting an alias
		if( $debug && ! empty( $library_local_urls[ $lib_file ][1] ) )
		{ // Load JS file for debug mode (optional)
			$lib_file = $library_local_urls[ $lib_file ][1];
		}
		else
		{ // Load JS file for production mode
			$lib_file = $library_local_urls[ $lib_file ][0];
		}

		if( $relative_to === 'relative' || $relative_to === true )
		{ // Aliases cannot be relative to <base>, make it relative to $rsc_url
			$relative_to = 'rsc_url';
		}
	}

	if( strpos( $lib_file, 'ext:' ) === 0 || strpos( $lib_file, 'customized:' ) === 0 )
	{	// This file must be loaded from subfolder '/rsc/ext/' or '/rsc/customized/' :
		$subfolder = strpos( $lib_file, 'ext:' ) === 0 ? 'ext' : 'customized';
		// Remove prefix 'ext:' from beginning of the file:
		$lib_file = substr( $lib_file, strlen( $subfolder ) + 1 );
	}

	if( $relative_to === 'relative' || $relative_to === true )
	{ // Make the file relative to current page <base>:
		$lib_url = $lib_file;
	}
	elseif( $relative_to === 'absolute' || preg_match( '~^(https?:)?//~', $lib_file ) )
	{	// It's already an absolute url, keep it as is:
		// (used to require CSS and JS files from Skin and Plugin because there we always use absolute URLs)
		$lib_url = $lib_file;
	}
	elseif( $relative_to === 'blog' && ! empty( $Blog ) )
	{ // Get the file from $rsc_uri relative to the current blog's domain (may be a subdomain or a custom domain):
		if( $assets_baseurl !== $baseurl )
		{ // We are using a specific domain, don't try to load from blog specific domain
			$lib_url = $rsc_url.$subfolder.'/'.$lib_file;
		}
		else
		{
			$lib_url = $Blog->get_local_rsc_url().$subfolder.'/'.$lib_file;
		}
	}
	elseif( $relative_to === 'siteskin' )
	{	// Get the file from current site skin if it is enabled otherwise from relative current page or head tag <base>:
		if( $site_Skin = & get_site_Skin() )
		{
			$lib_url = $site_Skin->get_url().$lib_file;
		}
		else
		{
			$lib_url = $lib_file;
		}
	}
	elseif( $relative_to === 'rsc_uri' )
	{ // Get the file from $rsc_uri:
		$lib_url = $rsc_uri.$subfolder.'/'.$lib_file;
	}
	else
	{ // Get the file from $rsc_url:
		$lib_url = $rsc_url.$subfolder.'/'.$lib_file;
	}

	if( ! empty( $version ) )
	{ // Be sure to get a fresh copy of this CSS file after application upgrades:
		if( $version == '#' )
		{
			global $app_version_long, $Skin;

			$version = $app_version_long;

			if( ( $relative_to == 'relative' || $relative_to === true ) && ! is_admin_page() && isset( $Skin ) )
			{	// Prepand skin version to clear file from browser cache after skin switching:
				$version = $Skin->folder.'+'.$Skin->version.'+'.$version;
			}
		}
		$lib_url = url_add_param( $lib_url, 'v='.$version );
	}

	if( preg_match( '~^https://~', $ReqURL ) )
	{ // If base url is safe then fix all media urls to protocol-relative format:
		$lib_url = preg_replace( '~^http://~', '//', $lib_url );
	}

	return $lib_url;
}


/**
 * Check if the requested file is bundled in another
 *
 * @param string alias, url or filename (relative to rsc/js) for javascript file
 * @param boolean|string Is the file's path relative to the base path/url?
 * @param string 'js' or 'css' or 'build'
 * @param string version number to append at the end of requested url to avoid getting an old version from the cache
 * @return integer Index of first file that was dequeued because it is bundled inside current requested file
 */
function check_bundled_file( $file, $relative_to = 'rsc_url', $subfolder = 'js', $version = '#' )
{
	global $required_js, $required_css, $bundled_files;

	// Store here index of first file that was dequeued because it is bundled inside current requested file:
	$first_dequeued_file_index = NULL;

	if( isset( $bundled_files[ $file ] ) )
	{	// If currently required file contains other JS files which must not be required twice:
		foreach( $bundled_files[ $file ] as $bundled_file )
		{	// Include all bundled files in the global array in order to don't call them twice:
			$bundled_url = strtolower( get_require_url( $bundled_file, $relative_to, $subfolder, $version ) );
			if( $subfolder == 'js' )
			{	// JS file:
				if( empty( $required_js ) || ! in_array( $bundled_url, $required_js ) )
				{	// Include bundled file into this global array in order to don't require this if it will be required further:
					$required_js[] = $bundled_url;
				}
			}
			else // 'css' or 'build'
			{	// CSS file:
				if( empty( $required_css ) || ! in_array( $bundled_url, $required_css ) )
				{	// Include bundled file into this global array in order to don't require this if it will be required further:
					$required_css[] = $bundled_url;
				}
			}
			// Dequeue the file if it was required before:
			$dequeued_file_index = dequeue( $bundled_file, $relative_to );
			if( $first_dequeued_file_index === NULL )
			{	// We need to know first dequeued file in order to insert currently
				// required file in that place instead of insert it as last ordered:
				$first_dequeued_file_index = $dequeued_file_index;
			}
		}
	}

	return $first_dequeued_file_index;
}


/**
 * Memorize that a specific javascript file will be required by the current page.
 * All requested files will be included in the page head only once (when headlines is called)
 *
 * Accepts absolute urls, filenames relative to the rsc/js directory and certain aliases, like 'jquery' and 'jquery_debug'
 * If 'jquery' is used and $debug is set to true, the 'jquery_debug' is automatically swapped in.
 * Any javascript added to the page is also added to the $required_js array, which is then checked to prevent adding the same code twice
 *
 * @param string alias, url or filename (relative to rsc/js) for javascript file
 * @param boolean|string Is the file's path relative to the base path/url?
 * @param boolean 'async' or TRUE to add attribute "async" to load javascript asynchronously,
 *                'defer' to add attribute "defer" asynchronously in the order they occur in the page,
 *                'immediate' or FALSE to load javascript immediately
 * @param boolean TRUE to print script tag on the page, FALSE to store in array to print then inside <head>
 * @param string version number to append at the end of requested url to avoid getting an old version from the cache
 * @param string Position where the CSS files will be inserted, either 'headlines' (inside <head>) or 'footerlines' (before </body>)
 */
function require_js( $js_file, $relative_to = 'rsc_url', $async_defer = false, $output = false, $version = '#', $position = 'headlines' )
{
	global $required_js; // Use this var as global and NOT static, because it is used in other functions(e.g. display_ajax_form(), check_bundled_file())
	global $use_defer;

	if( is_admin_page() && in_array( $js_file, array( 'functions.js', 'ajax.js', 'form_extensions.js', 'extracats.js', 'dynamic_select.js', 'backoffice.js' ) ) )
	{	// Don't require this file on back-office because it is auto loaded by bundled file evo_backoffice.bmin.js:
		return;
	}

	if( is_dequeued( $js_file, $relative_to ) )
	{	// Don't require if the file was already dequeued once:
		return;
	}

	// Get index of first file that was dequeued because it is bundled inside current requested file:
	$first_dequeued_file_index = check_bundled_file( $js_file, $relative_to, 'js', $version );

	if( in_array( $js_file, array( '#jqueryUI#', 'communication.js', 'functions.js' ) ) )
	{	// Dependency : ensure jQuery is loaded
		// Don't use TRUE for $async and $output because it may loads jQuery twice on AJAX request, e.g. on comment AJAX form,
		// and all jQuery UI libraries(like resizable, sortable and etc.) will not work, e.g. on attachments fieldset
		require_js_defer( '#jquery#', $relative_to, false, $version, $position );
	}

	// Get library url of JS file by alias name
	$js_url = get_require_url( $js_file, $relative_to, 'js', $version );

	// Add to headlines, if not done already:
	if( empty( $required_js ) || ! in_array( strtolower( $js_url ), $required_js ) )
	{
		$required_js[] = strtolower( $js_url );

		$script_tag = '<script';
		if( $async_defer == 'async' || $async_defer === true )
		{
			$script_tag .= ' async';
		}
		elseif( use_defer() && $async_defer == 'defer' )
		{
			$script_tag .= ' defer';
		}
		//else 'immediate' or false
		$script_tag .= ' src="'.$js_url.'">';
		$script_tag .= '</script>';

		if( $output )
		{ // Print script tag right here
			echo $script_tag;
		}
		else
		{ // Add script tag to <head>
			if( $position == 'headlines' )
			{
				add_headline( $script_tag, $js_file, $relative_to, $first_dequeued_file_index );
			}
			elseif( $position == 'footerlines' )
			{
				add_footerline( $script_tag, $js_file, $relative_to, $first_dequeued_file_index );
			}
		}
	}

	/* Yura: Don't require this plugin when it is already concatenated in jquery.bundle.js
	 * But we should don't forget it for CDN jQuery file and when js code uses deprecated things of jQuery */
	if( $js_file == '#jquery#' )
	{ // Dependency : The plugin restores deprecated features and behaviors so that older code will still run properly on jQuery 1.9 and later
		require_js_defer( '#jquery_migrate#', $relative_to, $output, $version );
	}
}


/**
 * Require javascript file to load asynchronously with attribute "async"
 *
 * @param string Alias, url or filename (relative to rsc/js) for javascript file
 * @param boolean|string Is the file's path relative to the base path/url?
 * @param boolean TRUE to print script tag on the page, FALSE to store in array to print then inside <head>
 * @param string Version number to append at the end of requested url to avoid getting an old version from the cache
 * @param string Position where the CSS files will be inserted, either 'headlines' (inside <head>) or 'footerlines' (before </body>)
 */
function require_js_async( $js_file, $relative_to = 'rsc_url', $output = false, $version = '#', $position = 'headlines' )
{
	require_js( $js_file, $relative_to, 'async', $output, $version, $position );
}


/**
 * Require javascript file to load asynchronously with attribute "defer" in the order they occur in the page
 *
 * @param string Alias, url or filename (relative to rsc/js) for javascript file
 * @param boolean|string Is the file's path relative to the base path/url?
 * @param boolean TRUE to print script tag on the page, FALSE to store in array to print then inside <head>
 * @param string Version number to append at the end of requested url to avoid getting an old version from the cache
 * @param string Position where the CSS files will be inserted, either 'headlines' (inside <head>) or 'footerlines' (before </body>)
 */
function require_js_defer( $js_file, $relative_to = 'rsc_url', $output = false, $version = '#', $position = 'headlines' )
{
	require_js( $js_file, $relative_to, 'defer', $output, $version, $position );
}


/**
 * Memorize that a specific css that file will be required by the current page.
 * All requested files will be included in the page head only once (when headlines is called)
 *
 * Accepts absolute urls, filenames relative to the rsc/css directory.
 * Set $relative_to_base to TRUE to prevent this function from adding on the rsc_path
 *
 * @param string alias, url or filename (relative to rsc/css) for CSS file
 * @param boolean|string 'relative' or true (relative to <base>) or 'rsc_url' (relative to $rsc_url)  or 'rsc_uri' (relative to $rsc_uri) or 'blog' (relative to current blog URL -- may be subdomain or custom domain)
 * @param string title.  The title for the link tag
 * @param string media.  ie, 'print'
 * @param string version number to append at the end of requested url to avoid getting an old version from the cache
 * @param boolean TRUE to print style tag on the page, FALSE to store in array to print then inside <head> or <body>
 * @param string Position where the CSS files will be inserted, either 'headlines' (inside <head>) or 'footerlines' (before </body>)
 * @param boolean TRUE to load CSS file asynchronously, FALSE otherwise.
 */
function require_css( $css_file, $relative_to = 'rsc_url', $title = NULL, $media = NULL, $version = '#', $output = false, $position = 'headlines', $async = false )
{
	global $required_css; // Use this var as global and NOT static, because it is used in other functions(e.g. check_bundled_file())

	// Which subfolder do we want to use in case of absolute paths? (doesn't appy to 'relative')
	$subfolder = 'css';
	if( $relative_to == 'rsc_url' || $relative_to == 'rsc_uri' || $relative_to == 'blog' )
	{
		if( preg_match( '/\.(bundle|bmin|min)\.css$/', $css_file ) )
		{
			$subfolder = 'build';
		}
	}

	if( is_dequeued( $css_file, $relative_to ) )
	{	// Don't require if the file was already dequeued once:
		return;
	}

	// Get index of first file that was dequeued because it is bundled inside current requested file:
	$first_dequeued_file_index = check_bundled_file( $css_file, $relative_to, $subfolder, $version );

	// Get library url of CSS file by alias name
	$css_url = get_require_url( $css_file, $relative_to, $subfolder, $version );

	// Add to headlines/footerlines, if not done already:
	if( empty( $required_css ) || ! in_array( strtolower( $css_url ), $required_css ) )
	{
		$required_css[] = strtolower( $css_url );

		$stylesheet_tag = '';

		if( $async )
		{
			$stylesheet_tag .= '<link rel="preload" as="style" onload="this.onload=null;this.rel=\'stylesheet\'"';
			$stylesheet_tag .= empty( $title ) ? '' : ' title="'.$title.'"';
			$stylesheet_tag .= empty( $media ) ? '' : ' media="'.$media.'"';
			$stylesheet_tag .= ' href="'.$css_url.'" />';
			$stylesheet_tag .= '<noscript>';
		}
		
		$stylesheet_tag .= '<link type="text/css" rel="stylesheet"';
		$stylesheet_tag .= empty( $title ) ? '' : ' title="'.$title.'"';
		$stylesheet_tag .= empty( $media ) ? '' : ' media="'.$media.'"';
		$stylesheet_tag .= ' href="'.$css_url.'" />';

		if( $async )
		{
			$stylesheet_tag .= '</noscript>';
		}

		if( $output )
		{	// Print stylesheet tag right here
			echo $stylesheet_tag;
		}
		else
		{	// Add stylesheet tag to <head>
			if($position == 'headlines' )
			{
				add_headline( $stylesheet_tag, $css_file, $relative_to, $first_dequeued_file_index );
			}
			elseif( $position == 'footerlines' )
			{
				add_footerline( $stylesheet_tag, $css_file, $relative_to, $first_dequeued_file_index );
			}
		}
	}
}


/**
 * Require CSS file to load asynchronously
 *
 * @param string Alias, url or filename (relative to rsc/css) for CSS file
 * @param boolean|string Is the file's path relative to the base path/url?
 * @param string title.  The title for the link tag
 * @param string media.  ie, 'print'
 * @param string Version number to append at the end of requested url to avoid getting an old version from the cache
 * @param boolean TRUE to print script tag on the page, FALSE to store in array to print then inside <head> or <body>
 * @param string Position where the CSS files will be inserted, either 'headlines' (inside <head>) or 'footerlines' (before </body>)
 */
function require_css_async( $css_file, $relative_to = 'rsc_url', $title = NULL, $media = NULL, $version = '#', $output = false, $position = 'headlines' )
{
	require_css( $css_file, $relative_to, $title, $media, $version, $output, $position, true );
}


/**
 * Dequeue a file from $headlines and $footerlines array by file name or alias
 *
 * @param string alias, url or filename (relative to rsc/js) for javascript file
 * @param boolean|string What group of headlines/footerlines touch to dequeue
 * @return integer|NULL Index/order of the file in global array on required files, NULL - if the file was not found
 */
function dequeue( $file_name, $group_relative_to = '#anygroup#' )
{
	global $headline_include_file, $headline_file_index, $dequeued_headlines;

	if( is_dequeued( $file_name, $group_relative_to ) )
	{	// Don't dequeue twice:
		pre_dump( 'is_dequeued' );
		return NULL;
	}

	// Convert boolean, NULL and etc. values to string format:
	$group_relative_to = strval( $group_relative_to );

	if( ! is_array( $dequeued_headlines ) )
	{	// Initialize array first time:
		$dequeued_headlines = array();
	}

	// Store each dequeued file in order to don't require this next time:
	$dequeued_headlines[ $group_relative_to ][ $file_name ] = true;

	// Find and store here index/order of first file:
	$dequeued_file_index = NULL;

	// Try to find and dequeue headline file:
	if( ! empty( $headline_file_index ) )
	{
		$headline_file_indexes = ( $group_relative_to == '#anygroup#' ? $headline_file_index : array() );
		if( $group_relative_to == '#anygroup#' )
		{	// Dequeue the file from any group:
			$headline_file_indexes = $headline_file_index;
		}
		elseif( isset( $headline_file_index[ $group_relative_to ] ) )
		{	// Dequeue the file only from the requested group:
			$headline_file_indexes = array( $group_relative_to => $headline_file_index[ $group_relative_to ] );
		}
		if( ! empty( $headline_file_indexes ) )
		{	// If relative_to group is found:
			foreach( $headline_file_indexes as $group_key => $group_headlines )
			{
				if( isset( $group_headlines[ $file_name ] ) )
				{	// Dequeue html/include tag with src/href of the file:
					$dequeued_file_index = $headline_file_index[ $group_key ][ $file_name ];
					unset( $headline_include_file[ $group_headlines[ $file_name ] ] );
					// Dequeue index/order of the file:
					unset( $headline_file_index[ $group_key ][ $file_name ] );
					// Don't find the file in next groups because it must be unique:
					return $dequeued_file_index;
				}
			}
		}
	}

	// Footerlines if the files is not found in Headlines above:
	global $footerline_include_file, $footerline_file_index, $dequeued_footerlines;

	if( ! is_array( $dequeued_footerlines ) )
	{	// Initialize array first time:
		$dequeued_footerlines = array();
	}

	// Store each dequeued file in order to don't require this next time:
	$dequeued_footerlines[ $group_relative_to ][ $file_name ] = true;

	// Find and store here index/order of first file:
	$dequeued_file_index = NULL;

	// Try to find and dequeue footerline file:
	if( ! empty( $footerline_file_index ) )
	{	// Try to dequeue footerline file:
		$footerline_file_indexes = ( $group_relative_to == '#anygroup#' ? $footerline_file_index : array() );
		if( $group_relative_to == '#anygroup#' )
		{	// Dequeue the file from any group:
			$footerline_file_indexes = $footerline_file_index;
		}
		elseif( isset( $footerline_file_index[ $group_relative_to ] ) )
		{	// Dequeue the file only from the requested group:
			$footerline_file_indexes = array( $group_relative_to => $footerline_file_index[ $group_relative_to ] );
		}
		if( ! empty( $footerline_file_indexes ) )
		{	// If relative_to group is found:
			foreach( $footerline_file_indexes as $group_key => $group_footerlines )
			{
				if( isset( $group_footerlines[ $file_name ] ) )
				{	// Dequeue html/include tag with src/href of the file:
					$dequeued_file_index = $footerline_file_index[ $group_key ][ $file_name ];
					unset( $footerline_include_file[ $group_footerlines[ $file_name ] ] );
					// Dequeue index/order of the file:
					unset( $footerline_file_index[ $group_key ][ $file_name ] );
					// Don't find the file in next groups because it must be unique:
					return $dequeued_file_index;
				}
			}
		}
	}

	return $dequeued_file_index;
}


/**
 * Check if file was dequeued from required list
 *
 * @param string Alias, url or relative file path of JS/CSS file
 * @param boolean|string Group of file
 */
function is_dequeued( $file_name, $group_relative_to )
{
	global $dequeued_headlines, $dequeued_footerlines;

	// Convert boolean, NULL and etc. values to string format:
	$group_relative_to = strval( $group_relative_to );

	return isset( $dequeued_headlines[ $group_relative_to ][ $file_name ] ) ||
		isset( $dequeued_footerlines[ $group_relative_to ][ $file_name ] );
}


/**
 * Memorize that a specific js helper will be required by the current page.
 * This allows to require JS + SS + do init.
 *
 * All requested helpers will be included in the page head only once (when headlines is called)
 * Requested helpers should add their required translation strings and any other settings
 *
 * @param string helper, name of the required helper
 */
function require_js_helper( $helper = '', $relative_to = 'rsc_url' )
{
	static $helpers;

	if( empty( $helpers ) || !in_array( $helper, $helpers ) )
	{ // Helper not already added, add the helper:

		switch( $helper )
		{
			case 'helper' :
				// main helper object required
				global $debug;
				require_js_defer( '#jquery#', $relative_to ); // dependency
				require_js_defer( 'helper.js', $relative_to );
				add_js_headline('jQuery(document).ready(function()
				{
					b2evoHelper.Init({
						debug:'.( $debug ? 'true' : 'false' ).'
					});
				});');
				break;

			case 'communications' :
				// communications object required
				require_js_helper('helper', $relative_to ); // dependency

				global $dispatcher;
				require_js_defer( 'communication.js', $relative_to );
				add_js_headline('jQuery(document).ready(function()
				{
					b2evoCommunications.Init({
						dispatcher:"'.$dispatcher.'"
					});
				});' );
				// add translation strings
				T_('Update cancelled', NULL, array( 'for_helper' => true ) );
				T_('Update paused', NULL, array( 'for_helper' => true ) );
				T_('Changes pending', NULL, array( 'for_helper' => true ) );
				T_('Saving changes', NULL, array( 'for_helper' => true ) );
				break;

			case 'colorbox':
				// Colorbox: a lightweight Lightbox alternative -- allows zooming on images and slideshows in groups of images
				// Added by fplanque - (MIT License) - http://colorpowered.com/colorbox/

				global $b2evo_icons_type, $blog;
				$blog_param = empty( $blog ) ? '' : '&amp;blog='.$blog;
				// Colorbox params to translate the strings:
				$colorbox_strings_params = 'current: "{current} / {total}",
					previous: "'.TS_('Previous').'",
					next: "'.TS_('Next').'",
					close: "'.TS_('Close').'",
					openNewWindowText: "'.TS_('Open in a new window').'",
					slideshowStart: "'.TS_('Start slideshow').'",
					slideshowStop: "'.TS_('Stop slideshow').'",';
				// Colorbox params to display a voting panel:
				$colorbox_voting_params = '{'.$colorbox_strings_params.'
					displayVoting: true,
					votingUrl: "'.get_htsrv_url().'anon_async.php?action=voting&vote_type=link&b2evo_icons_type='.$b2evo_icons_type.$blog_param.'",
					minWidth: 320}';
				// Colorbox params without voting panel:
				$colorbox_no_voting_params = '{'.$colorbox_strings_params.'
					minWidth: 255}';

				// Initialize js variables b2evo_colorbox_params* that are used in async loaded colorbox file
				if( is_logged_in() )
				{ // User is logged in
					// All unknown images have a voting panel
					$colorbox_params_other = 'var b2evo_colorbox_params_other = '.$colorbox_voting_params;
					if( is_admin_page() )
					{ // Display a voting panel for all images in backoffice
						$colorbox_params_post = 'var b2evo_colorbox_params_post = '.$colorbox_voting_params;
						$colorbox_params_cmnt = 'var b2evo_colorbox_params_cmnt = '.$colorbox_voting_params;
						$colorbox_params_user = 'var b2evo_colorbox_params_user = '.$colorbox_voting_params;
					}
					else
					{ // Display a voting panel depending on skin settings
						global $Skin;
						if( ! empty( $Skin ) )
						{
							$colorbox_params_post = 'var b2evo_colorbox_params_post = '.( $Skin->get_setting( 'colorbox_vote_post' ) ? $colorbox_voting_params : $colorbox_no_voting_params );
							$colorbox_params_cmnt = 'var b2evo_colorbox_params_cmnt = '.( $Skin->get_setting( 'colorbox_vote_comment' ) ? $colorbox_voting_params : $colorbox_no_voting_params );
							$colorbox_params_user = 'var b2evo_colorbox_params_user = '.( $Skin->get_setting( 'colorbox_vote_user' ) ? $colorbox_voting_params : $colorbox_no_voting_params );
						}
					}
				}
				if( ! isset( $colorbox_params_post ) )
				{ // Don't display a voting panel for all images if user is NOT logged in OR for case when $Skin is not defined
					$colorbox_params_other = 'var b2evo_colorbox_params_other = '.$colorbox_no_voting_params;
					$colorbox_params_post = 'var b2evo_colorbox_params_post = '.$colorbox_no_voting_params;
					$colorbox_params_cmnt = 'var b2evo_colorbox_params_cmnt = '.$colorbox_no_voting_params;
					$colorbox_params_user = 'var b2evo_colorbox_params_user = '.$colorbox_no_voting_params;
				}

				require_js_defer( '#jquery#', $relative_to );
				// Initialize the colorbox settings:
				add_js_footerline(
					// For post images
					$colorbox_params_post.';
					'// For comment images
					.$colorbox_params_cmnt.';
					'// For user images
					.$colorbox_params_user.';
					'// For all other images
					.$colorbox_params_other.';' );
				// TODO: translation strings for colorbox buttons

				// Do NOT require colorbox.bmin.js here because it is grunted in evo_generic.bmin.js:
				// require_js_defer( 'build/colorbox.bmin.js', $relative_to );

				if( is_admin_page() )
				{
					global $AdminUI;
					if( ! empty( $AdminUI ) )
					{
						$colorbox_css_file = $AdminUI->get_template( 'colorbox_css_file' );
					}
				}
				else
				{
					global $Skin;
					if( ! empty( $Skin ) )
					{
						$colorbox_css_file = $Skin->get_template( 'colorbox_css_file' );
					}
				}
				require_css( ( empty( $colorbox_css_file ) ? 'colorbox-regular.min.css' : $colorbox_css_file ), $relative_to );
				break;
		}
		// add to list of loaded helpers
		$helpers[] = $helper;
	}
}

/**
 * Memorize that a specific translation will be required by the current page.
 * All requested translations will be included in the page body only once (when footerlines is called)
 *
 * @param string string, untranslated string
 * @param string translation, translated string
 */
function add_js_translation( $string, $translation )
{
	global $js_translations;
	if( $string != $translation )
	{ // it's translated
		$js_translations[ $string ] = $translation;
	}
}


/**
 * Add a headline, which then gets output in the HTML HEAD section.
 * If you want to include CSS or JavaScript files, please use
 * {@link require_css()} and {@link require_js_async()} and {@link require_js_defer()} instead.
 * This avoids duplicates and allows caching/concatenating those files
 * later (not implemented yet)
 *
 * @param string HTML tag like <script></script> or <link />
 * @param string File name (used to index)
 * @param boolean|string Group headlines by this group in order to allow use files with same names from several places
 * @param integer Insert new headline in the given index, Useful to insert superbundled file instead of first bundled file
 */
function add_headline( $headline, $file_name = NULL, $group_relative_to = '#nogroup#', $file_index = NULL )
{
	if( $file_name === NULL )
	{	// Add inline code:
		global $headline_inline_code;
		$headline_inline_code[] = $headline;
	}
	else
	{	// Add include file:
		global $headline_include_file, $headline_file_index;
		// Convert boolean, NULL and etc. values to string format:
		$group_relative_to = strval( $group_relative_to );
		if( isset( $headline_file_index[ $group_relative_to ][ $file_name ] ) )
		{	// Skip already included file from the same group:
			return;
		}
		if( $file_index === NULL || isset( $headline_include_file[ $file_index ] ) )
		{	// Use auto order/index:
			$headline_include_file[] = $headline;
			$file_index = max( array_keys( $headline_include_file ) );
		}
		else
		{	// Use specific order/index when it is requested and the index is free:
			$headline_include_file[ $file_index ] = $headline;
		}
		// Flag to don't include same file from same group twice,
		// Also store value as index/order in order to dequeue it quickly:
		$headline_file_index[ $group_relative_to ][ $file_name ] = $file_index;
	}
}


/**
 * Add a footerline, which then gets output before the </body> tag.
 * If you want to include CSS or JavaScript files, please use
 * {@link require_css()} and {@link require_js_async()} and {@link require_js_defer()} instead.
 * This avoids duplicates and allows caching/concatenating those files
 * later (not implemented yet)
 *
 * @param string HTML tag like <script></script> or <link />
 * @param string File name (used to index)
 * @param boolean|string Group footerlines by this group in order to allow use files with same names from several places
 * @param integer Insert new headline in the given index, Useful to insert superbundled file instead of first bundled file
 */
function add_footerline( $footerline, $file_name = NULL, $group_relative_to = '#nogroup#', $file_index = NULL )
{
	if( $file_name === NULL )
	{	// Add inline code:
		global $footerline_inline_code;
		$footerline_inline_code[] = $footerline;
	}
	else
	{	// Add include file:
		global $footerline_include_file, $footerline_file_index;
		// Convert boolean, NULL and etc. values to string format:
		$group_relative_to = strval( $group_relative_to );
		if( isset( $footerline_file_index[ $group_relative_to ][ $file_name ] ) )
		{	// Skip already included file from the same group:
			return;
		}
		if( $file_index === NULL || isset( $footerline_include_file[ $file_index ] ) )
		{	// Use auto order/index:
			$footerline_include_file[] = $footerline;
			$file_index = max( array_keys( $footerline_include_file ) );
		}
		else
		{	// Use specific order/index when it is requested and the index is free:
			$footerline_include_file[ $file_index ] = $footerline;
		}
		// Flag to don't include same file from same group twice,
		// Also store value as index/order in order to dequeue it quickly:
		$footerline_file_index[ $group_relative_to ][ $file_name ] = $file_index;
	}
}


/**
 * Add a Javascript headline.
 * This is an extra function, to provide consistent wrapping and allow to bundle it
 * (i.e. create a bundle with all required JS files and these inline code snippets,
 *  in the correct order).
 * @param string Javascript
 */
function add_js_headline($headline)
{
	add_headline("<script>\n\t/* <![CDATA[ */\n\t\t"
		.$headline."\n\t/* ]]> */\n\t</script>");
	[

/**
 * Registers headlines for initialization of file multi uploader
 *
 * @param boolean|string 'relative' or true (relative to <base>) or 'rsc_url' (relative to $rsc_url) or 'blog' (relative to current blog URL -- may be subdomain or custom domain)
 * @param boolean TRUE to make the links table sortable
 */
function init_fileuploader_js( $relative_to = 'rsc_url', $load_sortable_js = true )
{
	require_js_defer( '#jquery#', $relative_to, true );
	// Used to make uploader area resizable:
	require_js_defer( '#jqueryUI#', $relative_to, true );

	if( $load_sortable_js )
	{	// Load JS file uploader with sortable feature for links/attachments:
		require_js_defer( 'build/evo_fileuploader_sortable.bmin.js', $relative_to, true );
	}
	else
	{	// Load JS file uploader:
		require_js_defer( 'build/evo_fileuploader.bmin.js', $relative_to, true );
	}

	// Styles for file uploader:
	require_css( 'fine-uploader.css', $relative_to, NULL, NULL, '#', true );
}


/**
 * Get a label for PRO version
 *
 * @return string
 */
function get_pro_label()
{
	return '<span class="label label-sm label-primary">PRO</span>';
}


/**
 * Resolve auto content mode depending on current disp detail
 *
 * @param string Content mode
 * @param object Collection
 * @return string Content mode
 */
function resolve_auto_content_mode( $content_mode, $setting_Blog = NULL )
{
	global $disp_detail;

	if( $content_mode != 'auto' )
	{	// Use this function only for auto content mode:
		return $content_mode;
	}

	if( $setting_Blog === NULL )
	{	// Use current Collection:
		global $Blog;
		$setting_Blog = $Blog;
	}

	if( empty( $setting_Blog ) )
	{	// Collection must be defined on call this function:
		debug_die( 'Collection is not initialized to resolve auto content mode!' );
	}

	switch( $disp_detail )
	{
		case 'posts-cat':
		case 'posts-topcat-intro':
		case 'posts-topcat-nointro':
		case 'posts-subcat-intro':
		case 'posts-subcat-nointro':
			return $setting_Blog->get_setting('chapter_content');

		case 'posts-tag':
			return $setting_Blog->get_setting('tag_content');

		case 'posts-date':
			return $setting_Blog->get_setting('archive_content');

		case 'single':
		case 'page':
			return 'full';

		case 'posts-default':  // home page 1
		case 'posts-next':     // next page 2, 3, etc
		case 'posts-next-intro':   // next page with intro
		case 'posts-next-nointro': // next page without intro
			return $setting_Blog->get_setting('main_content');

		default: // posts-filtered, search, flagged and etc.
			return $setting_Blog->get_setting('filtered_content');
	}
}
>>>>>>> a7920fa31b433793ff59c14f70e6f01f613f595f
?>
