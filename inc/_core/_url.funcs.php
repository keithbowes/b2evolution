<?php
/**
 * URL manipulation functions
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2006 by Daniel HAHLER - {@link http://daniel.hahler.de/}.
 *
 * @package evocore
 *
 * @author blueyed: Daniel HAHLER
 * @author Danny Ferguson
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Check the validity of a given URL
 *
 * Checks allowed URI schemes and URL ban list.
 * URL can be empty.
 *
 * Note: We have a problem when trying to "antispam" a keyword which is already blacklisted
 * If that keyword appears in the URL... then the next page has a bad referer! :/
 *
 * {@internal This function gets tested in misc.funcs.simpletest.php.}}
 *
 * @param string Url to validate
 * @param string Context ("posting", "commenting", "download_src", "http-https")
 * @param boolean also do an antispam check on the url
 * @return mixed false (which means OK) or error message
 */
function validate_url( $url, $context = 'posting', $antispam_check = true )
{
	global $Debuglog, $debug;

	if( empty($url) )
	{ // Empty URL, no problem
		return false;
	}

	// Do not give verbose info for comments, unless debug is enabled.
	$verbose = $debug || $context != 'commenting';

	$allowed_uri_schemes = get_allowed_uri_schemes( $context );

	// Validate URL structure
	if( $url[0] == '$' )
	{ // This is a 'special replace code' URL (used in footers)
		if( ! preg_match( '~\$([a-z_]+)\$~', $url ) )
		{
			return T_('Invalid URL $code$ format');
		}
	}
	elseif( preg_match( '~^\w+:~', $url ) )
	{ // there's a scheme and therefor an absolute URL:
		if( substr($url, 0, 7) == 'mailto:' )
		{ // mailto:link
			if( ! in_array( 'mailto', $allowed_uri_schemes ) )
			{ // Scheme not allowed
				$scheme = 'mailto:';
				$Debuglog->add( 'URI scheme &laquo;'.$scheme.'&raquo; not allowed!', 'error' );
				return $verbose
					? sprintf( T_('URI scheme "%s" not allowed.'), htmlspecialchars($scheme) )
					: T_('URI scheme not allowed.');
			}

			preg_match( '~^(mailto):(.*?)(\?.*)?$~', $url, $match );
			if( ! $match )
			{
				return $verbose
					? sprintf( T_('Invalid email link: %s.'), htmlspecialchars($url) )
					: T_('Invalid email link.');
			}
			elseif( ! is_email($match[2]) )
			{
				return $verbose
					? sprintf( T_('Supplied email address (%s) is invalid.'), htmlspecialchars($match[2]) )
					: T_('Invalid email address.');
			}
		}
		elseif( substr($url, 0, 6) == 'clsid:' )
		{ // clsid:link
			if( ! in_array( 'clsid', $allowed_uri_schemes ) )
			{ // Scheme not allowed
				$scheme = 'clsid:';
				$Debuglog->add( 'URI scheme &laquo;'.$scheme.'&raquo; not allowed!', 'error' );
				return $verbose
					? sprintf( T_('URI scheme "%s" not allowed.'), htmlspecialchars($scheme) )
					: T_('URI scheme not allowed.');
			}

			if( ! preg_match( '~^(clsid):([a-fA-F0-9\-]+)$~', $url, $match) )
			{
				return T_('Invalid class ID format');
			}
		}
		elseif( substr($url, 0, 11) == 'javascript:' )
		{ // javascript:
			// Basically there could be anything here
			if( ! in_array( 'javascript', $allowed_uri_schemes ) )
			{ // Scheme not allowed
				$scheme = 'javascript:';
				$Debuglog->add( 'URI scheme &laquo;'.$scheme.'&raquo; not allowed!', 'error' );
				return $verbose
					? sprintf( T_('URI scheme "%s" not allowed.'), htmlspecialchars($scheme) )
					: T_('URI scheme not allowed.');
			}

			preg_match( '~^(javascript):~', $url, $match );
		}
		else
		{
			// convert URL to IDN:
			$url = idna_encode($url);

			if( ! preg_match('~^           # start
				([a-z][a-z0-9+.\-]*)             # scheme
				://                              # authorize absolute URLs only ( // not present in clsid: -- problem? ; mailto: handled above)
				(\w+(:\w+)?@)?                   # username or username and password (optional)
				( localhost |
						[a-z0-9]([a-z0-9\-])*            # Don t allow anything too funky like entities
						\.                               # require at least 1 dot
						[a-z0-9]([a-z0-9.\-])+           # Don t allow anything too funky like entities
				)
				(:[0-9]+)?                       # optional port specification
				.*                               # allow anything in the path (including spaces - used in FileManager - but no newlines).
				$~ix', $url, $match) )
			{ // Cannot validate URL structure
				$Debuglog->add( 'URL &laquo;'.$url.'&raquo; does not match url pattern!', 'error' );
				return $verbose
					? sprintf( T_('Invalid URL format (%s).'), htmlspecialchars($url) )
					: T_('Invalid URL format.');
			}

			$scheme = strtolower($match[1]);
			if( ! in_array( $scheme, $allowed_uri_schemes ) )
			{ // Scheme not allowed
				$Debuglog->add( 'URI scheme &laquo;'.$scheme.'&raquo; not allowed!', 'error' );
				return $verbose
					? sprintf( T_('URI scheme "%s" not allowed.'), htmlspecialchars($scheme) )
					: T_('URI scheme not allowed.');
			}
		}
	}
	else
	{ // URL is relative..
		if( $context == 'commenting' || $context == 'download_src' || $context == 'http-https' )
		{ // We do not allow relative URLs in comments and download urls
			return $verbose ? sprintf( T_('URL "%s" must be absolute.'), htmlspecialchars($url) ) : T_('URL must be absolute.');
		}

		$char = substr( $url, 0, 1 );
		if( $char != '/' && $char != '#' )
		{ // must start with a slash or hash (for HTML anchors to the same page)
			return $verbose
				? sprintf( T_('URL "%s" must be a full path starting with "/" or an anchor starting with "#".'), htmlspecialchars($url) )
				: T_('URL must be a full path starting with "/" or an anchor starting with "#".');
		}
	}

	if( $antispam_check )
	{ // Search for blocked keywords:
		if( $block = antispam_check($url) )
		{
			// Log into system log
			syslog_insert( sprintf( 'Antispam: URL "%s" not allowed. The URL contains blacklisted word "%s".', htmlspecialchars($url), $block ), 'error' );

			return $verbose
				? sprintf( T_('URL "%s" not allowed: blacklisted word "%s".'), htmlspecialchars($url), $block )
				: T_('URL not allowed');
		}
	}

	return false; // OK
}


/**
 * Get allowed URI schemes for a given context.
 * @param string Context ("posting", "commenting", "download_src", "http-https")
 * @return array
 */
function get_allowed_uri_schemes( $context = 'posting' )
{
	/**
	 * @var User
	 */
	global $current_User;

	$schemes = array(
			'http',
			'https'
		);

	if( $context == 'http-https' )
	{ // for context == 'http-https' we accepts only http, https.
		return $schemes;
	}

	$schemes[] = 'ftp';

	if( $context == 'download_src' )
	{ // for context == 'download_src' we also accepts ftp.
		return $schemes;
	}

	$schemes = array_merge( $schemes, array(
			'gopher',
			'nntp',
			'news',
			'mailto',
			'irc',
			'aim',
			'icq'
		) );

	if( $context == 'commenting' )
	{
		return $schemes;
	}

	// for context == 'posting' we MAY allow additional "DANGEROUS" schemes:
	if( !empty( $current_User ) )
	{ // Add additional permissions the current User may have:

		$Group = & $current_User->get_Group();

		if( $Group->perm_xhtml_javascript )
		{
			$schemes[] = 'javascript';
		}

		if( $Group->perm_xhtml_objects )
		{
			$schemes[] = 'clsid';
		}

	}

	return $schemes;
}


/**
 * Get the last HTTP status code received by the HTTP/HTTPS wrapper of PHP.
 *
 * @param array The $http_response_header array (by reference).
 * @return integer|boolean False if no HTTP status header could be found,
 *                         the HTTP status code otherwise.
 */
function _http_wrapper_last_status( & $headers )
{
	for( $i = count( $headers ) - 1; $i >= 0; --$i )
	{
		if( preg_match( '|^HTTP/\d+\.\d+ (\d+)|', $headers[$i], $matches ) )
		{
			return $matches[1];
		}
	}

	return false;
}


/**
 * Fetch remote page
 *
 * Attempt to retrieve a remote page using a HTTP GET request, first with
 * cURL, then fsockopen, then fopen.
 *
 * cURL gets skipped, if $max_size_kb is requested, since there appears to be no
 * method to control this.
 * {@internal (CURLOPT_READFUNCTION maybe? But it has not been called for me.. seems
 *            to affect sending, not fetching?!)}}
 *
 * @todo dh> Should we try remaining methods, if the previous one(s) failed?
 * @todo Tblue> Also allow HTTP POST.
 *
 * @param string URL
 * @param array Info (by reference)
 *        'error': holds error message, if any
 *        'status': HTTP status (e.g. 200 or 404)
 *        'used_method': Used method ("curl", "fopen", "fsockopen" or null if no method
 *                       is available)
 * @param integer Timeout (default: 15 seconds)
 * @param integer Maximum size in kB
 * @param array Additional parameters
 * @return string|false The remote page as a string; false in case of error
 */
function fetch_remote_page( $url, & $info, $timeout = NULL, $max_size_kb = NULL, $params = array() )
{
	global $outgoing_proxy_hostname, $outgoing_proxy_port, $outgoing_proxy_username, $outgoing_proxy_password;

	$params = array_merge( array(
			'method'       => 'GET',
			'content_type' => '',
			'fields'       => '', // Array or string of POST/GET fields
		), $params );

	$info = array(
		'error' => '',
		'status' => NULL,
		'mimetype' => NULL,
		'used_method' => NULL,
	);

	if( ! isset($timeout) )
		$timeout = 15;

	if( extension_loaded('curl') && ! $max_size_kb ) // dh> I could not find an option to support "maximum size" for curl (to abort during download => memory limit).
	{	// CURL:
		$info['used_method'] = 'curl';

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
		curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
		if( $params['method'] == 'POST' )
		{	// Use POST method:
			curl_setopt( $ch, CURLOPT_POST, true );
		}
		if( ! empty( $params['fields'] ) )
		{	// Add fields for the request:
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $params['fields'] );
		}

		// Set proxy:
		if( !empty($outgoing_proxy_hostname) )
		{
			curl_setopt( $ch, CURLOPT_PROXY, $outgoing_proxy_hostname );
			curl_setopt( $ch, CURLOPT_PROXYPORT, $outgoing_proxy_port );
			curl_setopt( $ch, CURLOPT_PROXYUSERPWD, $outgoing_proxy_username.':'.$outgoing_proxy_password );
		}

		@curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true ); // made silent due to possible errors with safe_mode/open_basedir(?)
		curl_setopt( $ch, CURLOPT_MAXREDIRS, 3 );
		$r = curl_exec( $ch );

		$info['mimetype'] = curl_getinfo( $ch, CURLINFO_CONTENT_TYPE );
		$info['status'] = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$info['error'] = curl_error( $ch );
		if( ( $errno = curl_errno( $ch ) ) )
		{
			$info['error'] .= ' (#'.$errno.')';
		}
		curl_close( $ch );

		return $r;
	}

	if( function_exists( 'fsockopen' ) ) // may have been disabled
	{	// FSOCKOPEN:
		$info['used_method'] = 'fsockopen';

		if ( ( $url_parsed = @parse_url( $url ) ) === false
			 || ! isset( $url_parsed['host'] ) )
		{
			$info['error'] = NT_( 'Could not parse URL' );
			return false;
		}

		if( isset( $url_parsed['scheme'] ) && $url_parsed['scheme'] == 'https' )
		{	// Special params for https urls:
			$host_prefix = 'ssl://';
			$default_port = 443;
		}
		else
		{	// Default params for normal urls:
			$host_prefix = '';
			$default_port = 80;
		}

		$host = $url_parsed['host'];
		$port = empty( $url_parsed['port'] ) ? $default_port : $url_parsed['port'];
		$path = empty( $url_parsed['path'] ) ? '/' : $url_parsed['path'];
		if( ! empty( $url_parsed['query'] ) )
		{
			$path .= '?'.$url_parsed['query'];
		}

		if( ! empty( $params['fields'] ) )
		{	// Convert fields array to string:
			$url_fields_string = ( is_array( $params['fields'] ) ? http_build_query( $params['fields'] ) : $params['fields'] );
		}

		$out = $params['method'].' '.$path.' HTTP/1.1'."\r\n";
		$out .= 'Host: '.$host;
		if( ! empty( $url_parsed['port'] ) )
		{	// we don't want to add :80 if not specified. remote end may not resolve it. (e-g b2evo multiblog does not)
			$out .= ':'.$port;
		}
		$out .= "\r\n";
		if( ! empty( $params['content_type'] ) )
		{
			$out .= 'Content-type: '.$params['content_type']."\r\n";
		}
		if( ! empty( $url_fields_string ) )
		{
			$out .= 'Content-length: '.strlen( $url_fields_string )."\r\n";
		}
		$out .= 'Connection: close'."\r\n\r\n";
		if( ! empty( $url_fields_string ) )
		{	// Append fields to the request:
			$out .= $url_fields_string;
		}

		$fp = @fsockopen( $host_prefix.$host, $port, $errno, $errstr, $timeout );
		if( ! $fp )
		{
			$info['error'] = $errstr.' (#'.$errno.')';
			return false;
		}

		// Send request:
		fwrite( $fp, $out );

		// Set timeout for data:
		if( function_exists( 'stream_set_timeout' ) )
		{
			stream_set_timeout( $fp, $timeout ); // PHP 4.3.0
		}
		else
		{
			socket_set_timeout( $fp, $timeout ); // PHP 4
		}

		// Read response:
		$r = '';
		// First line:
		$s = fgets( $fp );
		if( ! preg_match( '~^HTTP/\d+\.\d+ (\d+)~', $s, $match ) )
		{
			$info['error'] = NT_( 'Invalid response' ).'.';
			fclose( $fp );
			return false;
		}

		while( ! feof( $fp ) )
		{
			$r .= fgets( $fp );
			if( $max_size_kb && evo_bytes($r) >= $max_size_kb*1024 )
			{
				$info['error'] = NT_( sprintf( 'Maximum size of %d kB reached.', $max_size_kb ) );
				return false;
			}
		}
		fclose($fp);

		if ( ( $pos = strpos( $r, "\r\n\r\n" ) ) === false )
		{
			$info['error'] = NT_( 'Could not locate end of headers' );
			return false;
		}

		// Remember headers to extract info at the end
		$headers = explode("\r\n", substr($r, 0, $pos));

		$info['status'] = $match[1];
		$r = substr( $r, $pos + 4 );
	}
	elseif( ini_get( 'allow_url_fopen' ) )
	{	// URL FOPEN:
		$info['used_method'] = 'fopen';

		$url_http_params = array();
		if( ! empty( $params['content_type'] ) )
		{	// Header of the request:
			$url_http_params['header'] = 'Content-type: '.$params['content_type']."\r\n";
		}
		if( $params['method'] != 'GET' )
		{	// Method of the request:
			$url_http_params['method'] = $params['method'];
		}
		if( ! empty( $params['fields'] ) )
		{	// Additional fields of the request:
			$url_http_params['content'] = http_build_query( $params['fields'] );
		}

		if( empty( $url_http_params ) )
		{	// Open simple URL:
			$fp = @fopen( $url, 'r' );
		}
		else
		{	// Open URL with additional params:
			$url_context = stream_context_create( array( 'http' => $url_http_params ) );
			$fp = @fopen( $url, 'r', false, $url_context );
		}

		if( ! $fp )
		{
			if( isset( $http_response_header )
			    && ( $code = _http_wrapper_last_status( $http_response_header ) ) !== false )
			{	// fopen() returned false because it got a bad HTTP code:
				$info['error'] = NT_( 'Invalid response' );
				$info['status'] = $code;
				return false;
			}

			$info['error'] = NT_( 'fopen() failed' );
			return false;
		}
		// Check just to be sure:
		else if ( ! isset( $http_response_header )
		          || ( $code = _http_wrapper_last_status( $http_response_header ) ) === false )
		{
			$info['error'] = NT_( 'Invalid response' );
			return false;
		}
		else
		{
			// Used to get info at the end
			$headers = $http_response_header;

			// Retrieve contents
			$r = '';
			while( ! feof( $fp ) )
			{
				$r .= fgets( $fp );
				if( $max_size_kb && evo_bytes($r) >= $max_size_kb*1024 )
				{
					$info['error'] = NT_( sprintf( 'Maximum size of %d kB reached.', $max_size_kb ) );
					return false;
				}
			}

			$info['status'] = $code;
		}
		fclose( $fp );
	}

	// Extract mimetype info from the headers (for fsockopen/fopen)
	if( isset($r) )
	{
		foreach($headers as $header)
		{
			$header = strtolower($header);
			if( substr($header, 0, 13) == 'content-type:' )
			{
				$info['mimetype'] = trim(substr($header, 13));
				break; // only looking for mimetype
			}
		}

		if( $info['mimetype'] == 'application/json' &&
		    strpos( $r, '{' ) !== false &&
		    preg_match( '/^[^\{]*(\{.+\})[^\}]*$/', $r, $match ) )
		{	// Fix response in JSON format, so it must be started with "{" and ended with "}":
			$r = $match[1];
		}

		return $r;
	}

	// All failed:
	$info['error'] = NT_( 'No method available to access URL!' );
	return false;
}


/**
 * Force URL to https if currently this protocol is used
 *
 * @param string URL
 * @return string URL forced to https protocol
 */
function force_https_if_needed( $url )
{
	if( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off' &&
	    substr( $url, 0, 7 ) == 'http://' )
	{	// Force http URL to currently used https protocol:
		$url = 'https://'.substr( $url, 7 );
	}
	// else: we do NOT need to change https -> http

	return $url;
}


/**
 * Get $url with the same protocol (http/https) as $other_url.
 *
 * @deprecated Use force_https_if_needed() instead because we do NOT need to change https -> http, only http -> https is really important.
 *
 * @param string URL
 * @param string other URL (defaults to {@link $ReqHost})
 * @return string
 */
function url_same_protocol( $url, $other_url = NULL )
{
	if( is_null($other_url) )
	{
		global $ReqHost;

		$other_url = $ReqHost;
	}

	// change protocol of $url to same of admin ('https' <=> 'http')
	if( substr( $url, 0, 7 ) == 'http://' )
	{
		if( substr( $other_url, 0, 8 ) == 'https://' )
		{
			$url = 'https://'.substr( $url, 7 );
		}
	}
	elseif( substr( $url, 0, 8 ) == 'https://' )
	{
		if( substr( $other_url, 0, 7 ) == 'http://' )
		{
			$url = 'http://'.substr( $url, 8 );
		}
	}

	return $url;
}


/**
 * Add param(s) at the end of an URL, using either "?" or "&amp;" depending on existing url
 *
 * @param string existing url
 * @param string|array Params to add (string as-is) or array, which gets urlencoded.
 * @param string delimiter to use for more params
 * @param boolean true by default for extra security checking
 * @return string URL with added param
 */
function url_add_param( $url, $param, $glue = '&amp;', $prevent_quotes = true )
{
	if( empty( $param ) )
	{
		return $url;
	}

	if( ( $anchor_pos = strpos( $url, '#' ) ) !== false )
	{ // There's an "#anchor" in the URL
		$anchor = substr( $url, $anchor_pos );
		$url = substr( $url, 0, $anchor_pos );
	}
	else
	{ // URL without "#anchor"
		$anchor = '';
	}

	// Handle array use case
	if( is_array( $param ) )
	{ // list of key => value pairs
		$param_list = array();
		foreach( $param as $k => $v )
		{
			$param_list[] = get_param_urlencoded( $k, $v, $glue );
		}
		$param = implode( $glue, $param_list );
	}

	if( $prevent_quotes &&
	    ( strpos( $param, '"' ) !== false || strpos( $param, '\'' ) !== false ) )
	{	// Don't allow chars " and ' in new set params:
		debug_die( 'Invalid chars in params <b>'.format_to_output( $param, 'htmlbody' ).'</b> for <code>url_add_param()</code> !' );
	}

	if( strpos( $url, '?' ) !== false )
	{ // There are already params in the URL
		$r = $url;
		if( substr( $url, -1 ) != '?' && substr( $param, 0, 1 ) != '#' )
		{ // the "?" is not the last char AND "#" is not first char of param
			$r .= $glue;
		}
		return $r.$param.$anchor;
	}

	// These are the first params
	return $url.'?'.$param.$anchor;
}


/**
 * Add a tail (starting with "/") at the end of an URL before any params (starting with "?")
 *
 * @param string existing url
 * @param string tail to add
 */
function url_add_tail( $url, $tail )
{
	$parts = explode( '?', $url );
	if( substr($parts[0], -1) == '/' )
	{
		$parts[0] = substr($parts[0], 0, -1);
	}
	if( isset($parts[1]) )
	{
		return $parts[0].$tail.'?'.$parts[1];
	}

	return $parts[0].$tail;
}


/**
 * Create a crumb param to be passed in action urls...
 *
 * @access public
 * @param string crumb_name
 */
function url_crumb( $crumb_name )
{
	return 'crumb_'.$crumb_name.'='.get_crumb($crumb_name);
}


/**
 * Get crumb via {@link $Session}.
 * @access public
 * @param string crumb_name
 * @return string
 */
function get_crumb( $crumb_name )
{
	global $Session;
	return isset( $Session ) ? $Session->create_crumb( $crumb_name ) : '';
}


/**
 * Try to make $url relative to $target_url, if scheme, host, user and pass matches.
 *
 * This is useful for redirect_to params, to keep them short and avoid mod_security
 * rejecting the request as "Not Acceptable" (whole URL as param).
 *
 * @param string URL to handle
 * @param string URL where we want to make $url relative to
 * @return string
 */
function url_rel_to_same_host( $url, $target_url )
{
	// Prepend fake scheme to URLs starting with "//" (relative to current protocol), since
	// parse_url fails to handle them correctly otherwise (recognizes them as path-only)
	$mangled_url = substr($url, 0, 2) == '//' ? 'noprotocolscheme:'.$url : $url;

	if( substr($target_url, 0, 2) == '//' )
		$target_url = 'noprotocolscheme:'.$target_url;


	$parsed_url = @parse_url( $mangled_url );
	if( ! $parsed_url )
	{ // invalid url
		return $url;
	}
	if( empty($parsed_url['scheme']) || empty($parsed_url['host']) )
	{ // no protocol or host information
		return $url;
	}

	$target_url = @parse_url( $target_url );
	if( ! $target_url )
	{ // invalid url
		return $url;
	}
	if( ! empty($target_url['scheme']) && $target_url['scheme'] != $parsed_url['scheme']
		&& $parsed_url['scheme'] != 'noprotocolscheme' )
	{ // scheme/protocol is different
		return $url;
	}
	if( ! empty($target_url['host']) )
	{
		if( empty($target_url['scheme']) || $target_url['host'] != $parsed_url['host'] )
		{ // target has no scheme (but a host) or hosts differ
			return $url;
		}

		if( @$target_url['port'] != @$parsed_url['port'] )
			return $url;
		if( @$target_url['user'] != @$parsed_url['user'] )
			return $url;
		if( @$target_url['pass'] != @$parsed_url['pass'] )
			return $url;
	}

	// We can make the URL relative:
	$r = '';
	if( isset($parsed_url['path']) && strlen($parsed_url['path']) )
		$r .= $parsed_url['path'];

	if( isset($parsed_url['query']) && strlen($parsed_url['query']) )
		$r .= '?'.$parsed_url['query'];

	if( isset($parsed_url['fragment']) && strlen($parsed_url['fragment']) )
		$r .= '#'.$parsed_url['fragment'];

	return $r;
}


/**
 * Make an $url absolute according to $host, if it is not absolute yet.
 *
 * @param string URL
 * @param string Base (including protocol, e.g. 'http://example.com'); autodedected
 * @return string
 */
function url_absolute( $url, $base = NULL )
{
	load_funcs('_ext/_url_rel2abs.php');

	if( is_absolute_url($url) )
	{	// URL is already absolute
		return $url;
	}

	if( empty($base) )
	{	// Detect current page base
		global $Collection, $Blog, $ReqHost, $base_tag_set, $baseurl;

		if( $base_tag_set )
		{	// <base> tag is set
			$base = $base_tag_set;
		}
		else
		{
			if( ! empty( $Blog ) )
			{	// Get original blog skin, not passed with 'tempskin' param
				$SkinCache = & get_SkinCache();
				if( ($Skin = $SkinCache->get_by_ID( $Blog->get_skin_ID(), false )) !== false )
				{
					$base = $Blog->get_local_skins_url().$Skin->folder.'/';
				}
				else
				{ // Skin not set:
					$base = $Blog->gen_baseurl();
				}
			}
			else
			{	// We are displaying a general page that is not specific to a blog:
				$base = $ReqHost;
			}
		}
	}

	if( ($absurl = url_to_absolute($url, $base)) === false )
	{	// Return relative URL in case of error
		$absurl = $url;
	}
	return $absurl;
}


/**
 * Make links in $s absolute.
 *
 * It searches for "src" and "href" HTML tag attributes and makes the absolute.
 *
 * @uses url_absolute()
 * @param string content
 * @param string Hostname including scheme, e.g. http://example.com; defaults to $ReqHost
 * @return string
 */
function make_rel_links_abs( $s, $host = NULL )
{
	load_class( '_core/model/_urlhelper.class.php', 'UrlHelper' );
	$url_helper = new UrlHelper( $host );
	$s = preg_replace_callback( '~(<[^>]+?)\b((?:src|href)\s*=\s*)(["\'])?([^\\3]+?)(\\3)~i', array( $url_helper, 'callback' ), $s );
	return $s;
}


/**
 * Display an URL, constrained to a max length
 *
 * @param string
 * @param integer
 */
function disp_url( $url, $max_length = NULL )
{
	if( !empty($max_length) && utf8_strlen($url) > $max_length )
	{
		$disp_url = htmlspecialchars(substr( $url, 0, $max_length-1 )).'&#8230;';
	}
	else
	{
		$disp_url = htmlspecialchars($url);
	}
	echo '<a href="'.$url.'">'.$disp_url.'</a>';
}


/**
 * Is a given URL absolute?
 * Note: "//foo/bar" is absolute - leaving the protocol out.
 *
 * @param string URL
 * @return boolean
 */
function is_absolute_url( $url )
{
	load_funcs('_ext/_url_rel2abs.php');

	if( ($parsed_url = split_url($url)) !== false )
	{
		if( !empty($parsed_url['scheme']) || !empty($parsed_url['host']) )
		{
			return true;
		}
	}
	return false;
}


/**
 * Compare two given URLs, if they are the same.
 * This converts all urlencoded chars (e.g. "%AA") to lowercase.
 * It appears that some webservers use lowercase for the chars (Apache),
 * while others use uppercase (lighttpd).
 *
 * @param string First URL
 * @param string Second URL
 * @param boolean TRUE to make the compared URLs same even if have a different protocols http or https
 * @return boolean
 */
function is_same_url( $a, $b, $ignore_http_protocol = FALSE )
{
	$a = preg_replace_callback('~%[0-9A-F]{2}~', '_is_same_url_callback', $a);
	$b = preg_replace_callback('~%[0-9A-F]{2}~', '_is_same_url_callback', $b);

	if( $ignore_http_protocol )
	{
		$re = "/^https?\:\/\/(.*)/i";
		$subst = "$1";

		$a = preg_replace( $re, $subst, $a );
		$b = preg_replace( $re, $subst, $b );
	}

	return $a == $b;
}


/**
 * Callback for preg_replace_callback in is_same_url()
 */
function _is_same_url_callback( $matches )
{
	return strtolower( $matches[0] );
}


/**
 * IDNA-Encode URL to Punycode.
 * @param string URL
 * @return string Encoded URL (ASCII)
 */
function idna_encode( $url )
{
	global $evo_charset;

	$url_utf8 = convert_charset( $url, 'utf-8', $evo_charset );

	load_class('_ext/idna/_idna_convert.class.php', 'idna_convert' );
	$IDNA = new idna_convert();

	//echo '['.$url_utf8.'] ';
	$url = $IDNA->encode( $url_utf8 );
	/* if( $idna_error = $IDNA->get_last_error() )
	{
		echo $idna_error;
	} */
	// echo '['.$url.']<br>';

	return $url;
}


/**
 * Decode IDNA puny-code ("xn--..") to UTF-8 name.
 *
 * @param string
 * @return string The decoded puny-code ("xn--..") (UTF8!)
 */
function idna_decode( $url )
{
	load_class('_ext/idna/_idna_convert.class.php', 'idna_convert' );
	$IDNA = new idna_convert();
	return $IDNA->decode($url);
}


/**
 * Get disp urls for Frontoffice part OR ctrl urls for Backoffice
 *
 * @param string specific sub entry url
 * @param string additional params
 */
function get_dispctrl_url( $dispctrl, $params = '' )
{
	global $Collection, $Blog;

	if( $params != '' )
	{
		$params = '&amp;'.$params;
	}

	if( is_admin_page() || empty( $Blog ) )
	{ // Backoffice part
		if( check_user_perm( 'admin', 'restricted' ) && check_user_status( 'can_access_admin' ) )
		{ // User must has an access to backoffice
			global $admin_url;
			return url_add_param( $admin_url, 'ctrl='.$dispctrl.$params );
		}
		else
		{ // Return empty because user has no access
			return NULL;
		}
	}

	if( in_array( $dispctrl, array( 'threads', 'messages', 'contacts', 'msgform' ) ) )
	{ // Get this url through Blog function, because it can be linked to other blog
		return $Blog->get( $dispctrl.'url' ).$params;
	}
	else
	{ // Use current blog url
		return url_add_param( $Blog->gen_blogurl(), 'disp='.$dispctrl.$params );
	}
}


/**
 * Get link tag
 *
 * @param string Url
 * @param string Link Text
 * @param string Link class
 * @param integer Max length of url when url is used as link text
 * @return string HTML link tag
 */
function get_link_tag( $url, $text = '', $class = '', $max_url_length = 50 )
{
	if( empty( $text ) )
	{ // Link text is empty, Use url
		$text = $url;
		if( strlen( $text ) > $max_url_length )
		{ // Crop url text
			$text = substr( $text, 0, $max_url_length ).'&#8230;';
		}
	}

	$link_attrs = array( 'href' => str_replace( '&amp;', '&', $url ) );

	if( ! empty( $class ) )
	{
		if( strpos( $class, '.' ) === false )
		{ // Simple class name
			$link_attrs['class'] = $class;
		}
		else
		{ // This class name is used for email template
			$link_attrs['style'] = emailskin_style( $class, false );
		}
	}

	return '<a'.get_field_attribs_as_string( $link_attrs ).'>'.$text.'</a>';
}


/**
 * Get part of url, Based on function parse_url()
 *
 * @param string URL
 * @param string Part name:
 *    scheme - e.g. http
 *    host
 *    port
 *    user
 *    pass
 *    path
 *    query - after the question mark ?
 *    fragment - after the hashmark #
 * @return string Part of url
 */
function url_part( $url, $part )
{
	$url_data = @parse_url( $url );
	if( $url_data && ! empty( $url_data[ $part ] ) )
	{
		return $url_data[ $part ];
	}

	return '';
}


/**
 * Check if the check_url has the same domains as the main_url
 * Note: check_url may also be a subdomain of the main_url
 *
 * @param string main url to compare domain with
 * @param string the url which needs to be checked
 * @return boolean true in case of the same main domain, false otherwise
 */
function url_check_same_domain( $main_url, $check_url )
{
	$main_url_host = url_part( $main_url, 'host' );
	$check_url_host = url_part( $check_url, 'host' );

	// Check same domain
	$same_domain = ( ( $check_url_host == null ) || ( $check_url_host == $main_url_host ) );
	// Check subdomain
	return $same_domain || ( substr( $check_url_host, - ( strlen( $main_url_host ) + 1 ) ) == '.'.$main_url_host );
}


/**
 * Check redirect URL if it is a part of redirect URLs in email log content
 *
 * Used to check redirect_to URLs from email message
 *
 * @param string Redirect URL
 * @param string Email log content, NULL - if we need to get email log message from DB by email log ID and key
 * @param string Email log ID
 * @param string Email log key
 * @return boolean TRUE if the requested URL can be used as redirect URL for the email log
 */
function check_redirect_url_by_email_log( $redirect_to, $email_log_message = NULL, $email_log_ID = NULL, $email_log_key = NULL )
{
	global $baseurl;

	if( empty( $redirect_to ) )
	{	// No URL to check:
		return false;
	}

	if( stripos( $redirect_to, $baseurl ) === 0 )
	{	// Allow redirect url if it is started with same domain as base url:
		return true;
	}

	if( $email_log_message === NULL &&
	    ! empty( $email_log_ID ) &&
	    ! empty( $email_log_key ) )
	{	// Try to get email log message from DB if it is not provided yet:
		global $DB;
		$SQL = new SQL( 'Get message of email log #'.$email_log_ID.' to check redirect url' );
		$SQL->SELECT( 'emlog_message' );
		$SQL->FROM( 'T_email__log' );
		$SQL->WHERE( 'emlog_ID = '.$DB->quote( $email_log_ID ) );
		$SQL->WHERE_and( 'emlog_key = '.$DB->quote( $email_log_key ) );
		$email_log_message = $DB->get_var( $SQL );
	}

	if( empty( $email_log_message ) )
	{	// Email log message is not provided and not found in DB:
		return false;
	}

	if( strpos( $email_log_message, 'redirect_to='.rawurlencode( $redirect_to ) ) !== false )
	{	// Allow to use found the requested redirect URL from provided content:
		return true;
	}

	// Additional check for case when URLs are encoded to html entities:
	if( strpos( $email_log_message, 'redirect_to='.rawurlencode( str_replace( '&', '&amp;', $redirect_to ) ) ) !== false )
	{	// Allow to use found the requested redirect URL from provided content:
		return true;
	}

	// The redirect URL is not allowed:
	return false;
}


/**
 * Get current URL
 *
 * @param string Exclude params separated by comma
 * @return string
 */
function get_current_url( $exclude_params = NULL )
{
	$current_url = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://' )
		.$_SERVER['HTTP_HOST']
		.$_SERVER['REQUEST_URI'];

	if( $exclude_params !== NULL )
	{	// Exclude params from current url:
		$current_url = clear_url( $current_url, $exclude_params );
	}

	return $current_url;
}


/**
 * Remove parameters from URL
 *
 * @param string Original URL
 * @param string Parameters which should be removed from URL (separated by comma)
 * @return string Cleared URL
 */
function clear_url( $url, $exclude_params )
{
	$exclude_params = str_replace( ',', '|', preg_quote( $exclude_params ) );
	$url = preg_replace( '/((\?)|&(amp;)?)('.$exclude_params.')=[^&]+/i', '$2', $url );
	return rtrim( preg_replace( '/\?(&(amp;)?)+/', '?', $url ), '?' );
}


/**
 * Keep allowed params from current URL in the given URL by config
 *
 * @param string Given URL
 * @param string Separator between URL params
 * @param array Additional params for config params. Used for Item's switchable params
 * @return string Given URL with allowed params which are found in currently opened URL
 */
function url_keep_params( $url, $glue = '&', $custom_keep_params = array() )
{
	// By default allow params from this config for all cases:
	global $passthru_in_all_redirs__params;

	$all_keep_params = is_array( $custom_keep_params ) ? $custom_keep_params : array();
	if( is_array( $passthru_in_all_redirs__params ) )
	{	// Merge config and custom params:
		$all_keep_params = array_merge( $passthru_in_all_redirs__params, $all_keep_params );
	}

	if( empty( $all_keep_params ) )
	{	// No allowed params:
		return $url;
	}

	// Get all params from the given URL:
	preg_match_all( '#(&(amp;)?|\?)([^=]+)=[^&]*#', $url, $url_params );
	$url_params = isset( $url_params[3] ) ? $url_params[3] : array();

	$allowed_params = array();
	foreach( $_GET as $param => $value )
	{	// Check each GET param:
		if( in_array( $param, $all_keep_params ) && // If param is allowed by config and custom params
		    ! in_array( $param, $url_params ) ) // If param is NOT defined in the given URL yet
		{
			$allowed_params[ $param ] = $value;
		}
	}

	// Append allowed params from current URL to the given URL:
	return url_add_param( $url, $allowed_params, $glue );
}


/**
 * Keep allowed params from current URL in the given Canonical URL
 *
 * @param string Canonical URL
 * @param string Separator between URL params
 * @param array Additional params for config params. Used for Item's switchable params
 * @return string Canonical URL with allowed params which are found in currently opened URL
 */
function url_keep_canonicals_params( $canonical_url, $glue = '&', $custom_keep_params = array() )
{
	global $accepted_in_canonicals__params, $accepted_in_canonicals_disp__params, $disp;

	// For canonical URLs we should keep params from additional config:
	if( is_array( $accepted_in_canonicals__params ) )
	{	// Merge config and custom params:
		$custom_keep_params = array_merge( $accepted_in_canonicals__params, $custom_keep_params );
	}

	if( isset( $disp, $accepted_in_canonicals_disp__params[ $disp ] ) &&
	    is_array( $accepted_in_canonicals_disp__params[ $disp ] ) )
	{	// Allow also params per current disp:
		$custom_keep_params = array_merge( $accepted_in_canonicals_disp__params[ $disp ], $custom_keep_params );
	}

	return url_keep_params( $canonical_url, $glue, $custom_keep_params );
}


/**
 * Get URL with same domain as current URL
 *
 * @param string Original URL to check and use with current domain
 * @return string Fixed URL with domain of current URL
 */
function get_same_domain_url( $url )
{
	global $ReqHost;

	if( ! isset( $ReqHost ) || strpos( $url, $ReqHost ) === 0 )
	{	// If domain of original URL is same as current URL domain:
		return $url;
	}
	else
	{	// Use current domain if domains are different, e.g. when collection URL uses subdomain or different absolute URL:
		return preg_replace( '#^https?://[^/]+#i', $ReqHost, $url );
	}
}


/**
 * Get admin URL
 *
 * @param string URL params
 * @param string Delimiter to use for more params
 * @return string Admin URL
 */
function get_admin_url( $url_params = '', $glue = '&amp;' )
{
	global $admin_url, $current_admin_url;

	if( ! isset( $current_admin_url ) )
	{	// Initialize current admin URL once:
		$current_admin_url = get_same_domain_url( $admin_url );
	}

	return url_add_param( $current_admin_url, $url_params, $glue );
}
?>
