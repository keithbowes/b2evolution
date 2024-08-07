<?php
/**
 * This file implements Antispam handling functions.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2004 by Vegar BERG GULDAL - {@link http://funky-m.com/}.
 * Parts of this file are copyright (c)2005 by The University of North Carolina at Charlotte as
 * contributed by Jason Edgecombe {@link http://tst.uncc.edu/}.
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * Vegar BERG GULDAL grants Francois PLANQUE the right to license
 * Vegar BERG GULDAL's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * The University of North Carolina at Charlotte grants Francois PLANQUE the right to license
 * Jason EDGECOMBE's contributions to this file and the b2evolution project
 * under the GNU General Public License (http://www.opensource.org/licenses/gpl-license.php)
 * and the Mozilla Public License (http://www.opensource.org/licenses/mozilla1.1.php).
 *  }}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * antispam_create(-)
 *
 * Insert a new abuse string into DB
 *
 * @param string Abuse string
 * @param string Keyword source
 * @return boolean TRUE if antispam keyword was inserted, FALSE if abuse string is empty or keyword is already in DB
 */
function antispam_create( $abuse_string, $keyword_source = 'local' )
{
	global $DB;

	// Cut the crap if the string is empty:
	$abuse_string = trim( $abuse_string );
	if( empty( $abuse_string ) )
	{
		return false;
	}

	// Check if the string already is in the blacklist:
	if( antispam_check( $abuse_string ) )
	{
		return false;
	}

	// Insert new string into DB:
	$DB->query( 'INSERT INTO T_antispam__keyword ( askw_string, askw_source )
		VALUES ( '.$DB->quote( $abuse_string ).', '.$DB->quote( $keyword_source ).' )' );

	return true;
}


/**
 * antispam_update_source(-)
 *
 * Note: We search by string because we sometimes don't know the ID
 * (e-g when download already in list/cache)
 *
 * @param string Abuse string
 * @param string Keyword source
 */
function antispam_update_source( $abuse_string, $keyword_source )
{
	global $DB;

	$DB->query( 'UPDATE T_antispam__keyword
		SET askw_source = '.$DB->quote( $keyword_source ).'
		WHERE askw_string = '.$DB->quote( $abuse_string ) );
}

/*
 * antispam_delete(-)
 *
 * Remove an entry from the ban list
 *
 * @param integer antispam keyword ID
 */
function antispam_delete( $keyword_ID )
{
	global $DB;

	$DB->query( 'DELETE FROM T_antispam__keyword
		WHERE askw_ID = '.intval( $keyword_ID ) );
}


/**
 * Check if a string contains abusive substrings
 *
 * Note: Letting the database do the LIKE %% match is a little faster than doing in it PHP,
 * not to mention the incredibly long overhead of preloading the list into PHP
 *
 * @todo dh> IMHO this method is too generic used! It gets used for:
 *           - comment author name
 *           - comment/message author email
 *           - comment content
 *           - message (email) content
 *           - validate_url
 *           ..and validates all this against the antispam blacklist!
 *           We should rather differentiate here more and make it pluggable!
 *
 * @return string blacklisted keyword found or false if no spam detected
 */
function antispam_check( $haystack )
{
	global $DB, $Debuglog, $Timer;

	// TODO: 'SELECT COUNT(*) FROM T_antispam__keyword WHERE askw_string LIKE "%'.$url.'%" ?

	$Timer->resume( 'antispam_url' ); // resuming to get the total number..
	$block = $DB->get_var(
		'SELECT askw_string
		   FROM  T_antispam__keyword
		  WHERE '.$DB->quote( $haystack ).' LIKE CONCAT("%",askw_string,"%")
		  LIMIT 0, 1', 0, 0, 'Check URL against antispam blacklist' );
	if( $block )
	{
			$Debuglog->add( 'Spam block: '.$block );
			return $block;	// SPAM detected!
	}
	$Timer->pause( 'antispam_url' );

	return false;	// no problem.
}


// -------------------- XML-RPC callers ---------------------------

/**
 * Pings b2evolution.net to report abuse from a particular domain.
 *
 * @param string The keyword to report as abuse.
 * @return boolean True on success, false on failure.
 */
function antispam_report_abuse( $abuse_string )
{
	global $debug, $antispamsrv_protocol, $antispamsrv_host, $antispamsrv_port, $antispamsrv_uri, $antispam_test_for_real;
	global $baseurl, $Messages, $Settings;
	global $outgoing_proxy_hostname, $outgoing_proxy_port, $outgoing_proxy_username, $outgoing_proxy_password;

	if( ! $Settings->get('antispam_report_to_central') )
	{
		$Messages->add( 'Reporting is disabled.', 'error' );  // NO TRANS necessary
		return false;
	}

	if( preg_match( '#^http://localhost[/:]#', $baseurl) && ( $antispamsrv_host != 'localhost' ) && empty( $antispam_test_for_real )  )
	{ // Local install can only report to local test server
		$Messages->add( T_('Reporting abuse to b2evolution aborted (Running on localhost).'), 'error' );
		return false;
	}

	// Construct XML-RPC client:
	load_funcs( 'xmlrpc/model/_xmlrpc.funcs.php' );
	if( ! defined( 'CANUSEXMLRPC' ) || CANUSEXMLRPC !== true )
	{	// Could not use xmlrpc client because server has no the requested extensions:
		return false;
	}
	$client = new xmlrpc_client( $antispamsrv_uri, $antispamsrv_host, $antispamsrv_port, $antispamsrv_protocol );
	// yura: I commented this because xmlrpc_client prints the debug info on screen and it breaks header_redirect()
	// $client->debug = $debug;

	// Set proxy for outgoing connections:
	if( ! empty( $outgoing_proxy_hostname ) )
	{
		$client->setProxy( $outgoing_proxy_hostname, $outgoing_proxy_port, $outgoing_proxy_username, $outgoing_proxy_password );
	}

	// Construct XML-RPC message:
	$message = new xmlrpcmsg(
								'b2evo.reportabuse',                        // Function to be called
								array(
									new xmlrpcval(0,'int'),                   // Reserved
									new xmlrpcval('annonymous','string'),     // Reserved
									new xmlrpcval('nopassrequired','string'), // Reserved
									new xmlrpcval($abuse_string,'string'),    // The abusive string to report
									new xmlrpcval($baseurl,'string'),         // The base URL of this b2evo
								)
							);
	$result = $client->send( $message );
	if( $ret = xmlrpc_logresult( $result, $Messages, false ) )
	{ // Remote operation successful:
		antispam_update_source( $abuse_string, 'reported' );

		$Messages->add( sprintf( T_('Reported abuse to %s.'), $antispamsrv_host ), 'success' );
	}
	else
	{
		$Messages->add( sprintf( T_('Failed to report abuse to %s.'), $antispamsrv_host ), 'error' );
	}

	return $ret;
}


/**
 * Request abuse list from central blacklist.
 *
 * @param boolean Is cron job execution?
 * @return boolean true = success, false = error
 */
function antispam_poll_abuse( $is_cron = false )
{
	global $Messages, $Settings, $baseurl, $debug, $antispamsrv_protocol, $antispamsrv_host, $antispamsrv_port, $antispamsrv_uri;
	global $outgoing_proxy_hostname, $outgoing_proxy_port, $outgoing_proxy_username, $outgoing_proxy_password;

	// Construct XML-RPC client:
	load_funcs('xmlrpc/model/_xmlrpc.funcs.php');
	$client = new xmlrpc_client( $antispamsrv_uri, $antispamsrv_host, $antispamsrv_port, $antispamsrv_protocol );
	// yura: I commented this because xmlrpc_client prints the debug info on screen and it breaks header_redirect()
	// $client->debug = $debug;

	// Set proxy for outgoing connections:
	if( ! empty( $outgoing_proxy_hostname ) )
	{
		$client->setProxy( $outgoing_proxy_hostname, $outgoing_proxy_port, $outgoing_proxy_username, $outgoing_proxy_password );
	}

	// Get datetime from last update, because we only want newer stuff...
	$last_update = $Settings->get( 'antispam_last_update' );
	// Encode it in the XML-RPC format
	$log_message = T_('Latest update timestamp').': '.$last_update;
	if( $is_cron )
	{	// Cron mode:
		cron_log_append( $log_message );
	}
	else
	{	// Normal mode:
		$Messages->add_to_group( $log_message, 'note', T_('Updating antispam:') );
	}
	$startat = mysql2date( 'Ymd\TH:i:s', $last_update );
	//$startat = iso8601_encode( mktime(substr($m,11,2),substr($m,14,2),substr($m,17,2),substr($m,5,2),substr($m,8,2),substr($m,0,4)) );

	// Construct XML-RPC message:
	$message = new xmlrpcmsg(
								'b2evo.pollabuse',                            // Function to be called
								array(
									new xmlrpcval(0,'int'),                     // Reserved
									new xmlrpcval('annonymous','string'),       // Reserved
									new xmlrpcval('nopassrequired','string'),   // Reserved
									new xmlrpcval($startat,'dateTime.iso8601'), // Datetime to start at
									new xmlrpcval(0,'int')                      // Reserved
								)
							);

	$log_message = sprintf( T_('Requesting abuse list from %s...'), $antispamsrv_host );
	if( $is_cron )
	{	// Cron mode:
		cron_log_append( $log_message );
	}
	else
	{	// Normal mode:
		$Messages->add_to_group( $log_message, 'note', T_('Updating antispam:') );
	}

	$result = $client->send( $message );

	if( $ret = xmlrpc_logresult( $result, $Messages, false ) )
	{ // Response is not an error, let's process it:
		$response = $result->value();
		if( $response->kindOf() == 'struct' )
		{ // Decode struct:
			$response = xmlrpc_decode_recurse( $response );
			if( !isset( $response['strings'] ) || !isset( $response['lasttimestamp'] ) )
			{
				$log_message = T_('Incomplete response.');
				if( $is_cron )
				{	// Cron mode:
					cron_log_append( $log_message, 'error' );
				}
				else
				{	// Normal mode:
					$Messages->add_to_group( $log_message, 'error', T_('Updating antispam:') );
				}
				$ret = false;
			}
			else
			{ // Start registering strings:
				$value = $response['strings'];
				if( count( $value ) == 0 )
				{
					$log_message = T_('No new blacklisted strings are available.');
					if( $is_cron )
					{	// Cron mode:
						cron_log_append( $log_message );
					}
					else
					{	// Normal mode:
						$Messages->add_to_group( $log_message, 'note', T_('Updating antispam:') );
					}
				}
				else
				{ // We got an array of strings:
					foreach( $value as $banned_string )
					{
						if( antispam_create( $banned_string, 'central' ) )
						{ // Creation successed
							$log_message = T_('Adding:').' &laquo;'.$banned_string.'&raquo;: '.T_('OK').'.';
							if( $is_cron )
							{	// Cron mode:
								cron_log_action_end( $log_message, 'success' );
							}
							else
							{	// Normal mode:
								$Messages->add_to_group( $log_message, 'note', T_('Adding strings to local blacklist:') );
							}
						}
						else
						{ // Was already handled
							$log_message = T_('Adding:').' &laquo;'.$banned_string.'&raquo;: '.T_('Not necessary! (Already handled)');
							if( $is_cron )
							{	// Cron mode:
								cron_log_action_end( $log_message, 'note' );
							}
							else
							{	// Normal mode:
								$Messages->add_to_group( $log_message, 'note', T_('Adding strings to local blacklist:') );
							}
							antispam_update_source( $banned_string, 'central' );
						}
					}
					// Store latest timestamp:
					$endedat = date('Y-m-d H:i:s', iso8601_decode( $response['lasttimestamp'] ) );
					$log_message = T_('New latest update timestamp').': '.$endedat;
					if( $is_cron )
					{	// Cron mode:
						cron_log_append( $log_message );
					}
					else
					{	// Normal mode:
						$Messages->add_to_group( $log_message, 'note', T_('Adding strings to local blacklist:') );
					}

					$Settings->set( 'antispam_last_update', $endedat );
					$Settings->dbupdate();
				}
				$log_message = T_('Done').'.';
				if( $is_cron )
				{	// Cron mode:
					cron_log_append( $log_message );
				}
				else
				{	// Normal mode:
					$Messages->add( $log_message, 'success' );
				}
			}
		}
		else
		{
			$log_message = T_('Invalid response').'.';
			if( $is_cron )
			{	// Cron mode:
				cron_log_append( $log_message, 'error' );
			}
			else
			{	// Normal mode:
				$Messages->add( $log_message, 'error' );
			}
			$ret = false;
		}
	}

	return $ret ;
}


/**
 * Get the base domain that could be blacklisted from an URL.
 *
 * We want to concentrate on the main domain and we want to prefix it with either . or // in order not
 * to blacklist too large.
 *
 * {@internal This function gets tested in _misc.funcs.simpletest.php}}
 *
 * @param string URL or domain
 * @return string|false the pattern to match this domain in the blacklist; false if we could not extract the base domain
 */
function get_ban_domain( $url )
{
	// echo '<p>'.$url;

	// Remove http:// part + everything after the last path element ( '/' alone is ignored on purpose )
	$domain = preg_replace( '~^ ([a-z]+://)? ([^/#\?]+) (/ ([^/]*/)+ )? .* ~xi', '\\2\\3', $url );

	// echo '<br>'.$domain;

	if( preg_match( '~^[0-9.]+$~', $domain ) )
	{	// All numeric = IP address, don't try to cut it any further
		return '//'.$domain;
	}

	// Remove any www*. prefix:
	$base_domain = preg_replace( '~^(www \w* \. )~xi', '', $domain );

	if( empty($base_domain) )
	{
		return false;
	}

	if( utf8_strlen( $base_domain ) < utf8_strlen( $domain ) )
	{	// The guy is spamming with subdomains (or www):
		return '.'.$base_domain;
	}

	// The guy is spamming with the base domain:
	return '//'.$base_domain;
}


/**
 * Get the blog restricted condition
 *
 * Creates an sql command part, which is a condition, that restrict to show comments from those blogs,
 * where current user has no edit permission for comments.
 * It is used by the antispam.ctrl, when current_User wants to delete the affected comments.
 *
 * asimo> It was changed so it doesn't restrict to blogs now, but it restricts to comment statuses.
 * When we will have per blog permanently delete comments permission then this function must be changed.
 *
 * @param array with key => value pairs, where the keys are the comment statuses and values are the boolean values to delete comments with the given statuses or not
 * @return string sql WHERE condition part, corresponding the user permissions
 */
function blog_restrict( $delstatuses )
{
	if( empty( $delstatuses ) )
	{ // none of the statuses should be deleted
		return ' AND false';
	}

	// asimo> Currently only global blogs editall permission gives rights to permanently delete comments
	// Probably this function must be changed when the advanced collection perms will be finished
	if( ! check_user_perm( 'blogs', 'editall', false ) )
	{ // User has permission to permanently delete comments on this blog
		return ' AND false';
	}

	$restriction = '( comment_status = "%s" )';
	$or = '';
	$condition = '';
	foreach( $delstatuses as $status )
	{
		$condition = $condition.$or.sprintf( $restriction, $status/*, $blog_ids */);
		$or = ' OR ';
	}

	return ' AND ( '.$condition.' )';
}


/**
 * Show affected comments
 *
 * @param array affected Comment list, all comments in this list must have the same status
 * @param string Comment visibility status in this list
 * @param string ban keyword
 * @param integer The number of corresponding comments on which current user has no permission
 */
function echo_affected_comments( $affected_comments, $status, $keyword, $noperms_count )
{
	$num_comments = count( $affected_comments );
	if( $num_comments == 0 )
	{
		if( $noperms_count == 0 )
		{ // There isn't any affected comment witch corresponding status
			printf( '<p>'.T_('No %s comments match the keyword %s.').'</p>', '<strong>'.$status.'</strong>', '<code>'.htmlspecialchars($keyword).'</code>' );
		}
		else
		{ // There are affected comment witch corresponding status, but current user has no permission
			printf( '<p>'.T_('There are %d matching %s comments, but you have no permission to edit them.').'</p>', $noperms_count, '<strong>'.$status.'</strong>' );
		}
		return;
	}

	echo '<p>';
	if( check_user_perm( 'blogs', 'editall', false ) )
	{ // current User has rights to permanently delete comments
		$checkbox_status = 'checked="checked"';
	}
	else
	{ // current User doesn't have rights to permanently delete comments, so disable delete checkbox
		$checkbox_status = 'disabled="disabled"';
	}
	echo '<input type="checkbox" name="del'.$status.'" id="del'.$status.'_cb" value="1" '.$checkbox_status.'/>';
	echo '<label for="del'.$status.'_cb"> ';
	echo sprintf ( T_('Delete the following %s %s comments:'), $num_comments == 500 ? '500+' : $num_comments, '<strong>'.$status.'</strong>' );
	echo '</label>';
	echo '</p>';

	echo '<table class="grouped table-striped table-bordered table-hover table-condensed" cellspacing="0">';
	echo '<thead><tr>';
	echo '<th class="firstcol">'.T_('Date').'</th>';
	echo '<th class="center">'.T_('Auth. IP').'</th>';
	echo '<th>'.T_('Author').'</th>';
	echo '<th>'.T_('Auth. URL').'</th>';
	echo '<th>'.T_('Content starts with...').'</th>';
	echo '<th class="shrinkwrap">'.T_('Action').'</th>';
	echo '</tr></thead>';
	$count = 0;
	foreach( $affected_comments as $Comment )
	{
		echo '<tr class="'.(($count%2 == 1) ? 'odd' : 'even').'">';
		echo '<td class="firstcol timestamp">'.mysql2localedatetime_spans( $Comment->get( 'date' ) ).'</td>';
		echo '<td class="center">'.$Comment->get( 'author_IP' ).'</td>';
		echo '<td>'.$Comment->get_author_name().'</td>';
		echo '<td>';
		disp_url( $Comment->get_author_url(), 50 );
		echo '</td>';
		echo '<td>'.excerpt( $Comment->get_content( 'raw_text' ), 71 ).'</td>';
		// no permission check, because affected_comments contains current user editable comments
		echo '<td class="shrinkwrap">'.action_icon( /* TRANS: Verb */ T_('Edit...'), 'edit', '?ctrl=comments&amp;action=edit&amp;comment_ID='.$Comment->ID ).'</td>';
		echo '</tr>';
		$count++;
	}
	echo "</tbody></table>";
}


/**
 * Get IP ranges from DB
 *
 * @param integer IP start of range
 * @param integer IP end of range
 * @param integer ID of existing IP range
 * @return array Rows of the table T_antispam__iprange (Empty array - if IP range doesn't exist in DB yet)
*/
function get_ip_ranges( $ip_start, $ip_end, $aipr_ID = 0 )
{
	global $DB;

	$SQL = new SQL( 'Get all IP ranges between "'.$ip_start.'" and "'.$ip_end.'"'.( $aipr_ID > 0 ? ' (except of #'.$aipr_ID.')' : '' ) );
	$SQL->SELECT( '*' );
	$SQL->FROM( 'T_antispam__iprange' );
	$SQL->WHERE( ' (
		( '.$DB->quote( $ip_start ).' >= aipr_IPv4start AND '.$DB->quote( $ip_start ).' <= aipr_IPv4end ) OR
		( '.$DB->quote( $ip_end ).' >= aipr_IPv4start AND '.$DB->quote( $ip_end ).' <= aipr_IPv4end ) OR
		( '.$DB->quote( $ip_start ).' <= aipr_IPv4start AND '.$DB->quote( $ip_end ).' >= aipr_IPv4end )
	)' );
	if( ! empty( $aipr_ID ) )
	{	// Exclude IP range with given ID:
		$SQL->WHERE_and( 'aipr_ID != '.$aipr_ID );
	}
	$SQL->ORDER_BY( 'aipr_IPv4start' );

	return $DB->get_results( $SQL );
}


/**
 * Block request by IP address, Domain of current user or block because of a Plugin
 * Bock by Plugin: e.g. GeoIP plugin can block the request if it comes from a blocked country
 */
function antispam_block_request()
{
	global $Plugins;

	// Check to block by current IP addresses:
	antispam_block_by_ip();

	// Check to block by current domain:
	antispam_block_by_domain();

	// Check to block by initial referer:
	antispam_block_by_initial_referer();

	// Check if plugins may block the request:
	$Plugins->trigger_event( 'BeforeBlockableAction' );
}


/**
 * Block request by current IP addresses
 */
function antispam_block_by_ip()
{
	global $DB;

	// Detect request IP adresses
	$request_ip_list = get_ip_list();

	if( empty( $request_ip_list ) )
	{ // Could not get any IP address, so can't check anything
		return;
	}

	$condition = '';
	foreach( $request_ip_list as $ip_address )
	{ // create condition for each detected IP address
		$numeric_ip_address = ip2int( $ip_address );
		$condition .= ' OR ( aipr_IPv4start <= '.$DB->quote( $numeric_ip_address ).' AND aipr_IPv4end >= '.$DB->quote( $numeric_ip_address ).' )';
	}
	$condition = '( '.substr( $condition, 4 ).' )';

	$SQL = new SQL( 'Get blocked IP ranges' );
	$SQL->SELECT( 'aipr_ID' );
	$SQL->FROM( 'T_antispam__iprange' );
	$SQL->WHERE( $condition );
	$SQL->WHERE_and( 'aipr_status = \'blocked\'' );
	$SQL->LIMIT( 1 );
	$ip_range_ID = $DB->get_var( $SQL );

	if( !is_null( $ip_range_ID ) )
	{ // The request from this IP address must be blocked
		$DB->query( 'UPDATE T_antispam__iprange
			SET aipr_block_count = aipr_block_count + 1
			WHERE aipr_ID = '.$DB->quote( $ip_range_ID ) );

		$log_message = sprintf( 'A request with ( %s ) ip addresses was blocked because of a blocked IP range ID#%s.', implode( ', ', $request_ip_list ), $ip_range_ID );
		exit_blocked_request( 'IP', $log_message ); // WILL exit();
	}
}


/**
 * Block request by current domain
 */
function antispam_block_by_domain()
{
	// Detect current IP adresses:
	$current_ip_addreses = get_ip_list();

	if( empty( $current_ip_addreses ) )
	{	// Could not get any IP address, so can't check anything:
		return;
	}

	load_funcs( 'sessions/model/_hitlog.funcs.php' );

	foreach( $current_ip_addreses as $ip_address )
	{
		if( ! is_valid_ip_format( $ip_address ) )
		{	// Skip not valid IP address:
			continue;
		}

		// Get domain name by current IP address:
		$ip_domain = gethostbyaddr( $ip_address );

		if( ! empty( $ip_domain ) &&
		    $Domain = & get_Domain_by_subdomain( $ip_domain ) &&
		    $Domain->get( 'status' ) == 'blocked' )
		{	// The request from this domain must be blocked:
			$log_message = sprintf( 'A request from \'%s\' domain was blocked because of the domain \'%s\' is blocked.', $ip_domain, $Domain->get( 'name' ) );
			exit_blocked_request( 'Domain', $log_message ); // WILL exit();
		}
	}
}


/**
 * Block request by initial referer of current session
 */
function antispam_block_by_initial_referer()
{
	global $Session;

	if( ! isset( $Session ) )
	{	// We cannot use this for request without initialized Session, e-g CLI mode:
		return;
	}

	load_funcs( 'sessions/model/_hitlog.funcs.php' );

	// Get first hit params of current session:
	$first_hit_params = $Session->get_first_hit_params();

	if( $first_hit_params && ! empty( $first_hit_params->hit_referer ) &&
			$Domain = & get_Domain_by_url( $first_hit_params->hit_referer ) &&
			$Domain->get( 'status' ) == 'blocked' )
	{	// The request from this initial referer must be blocked:
		$log_message = sprintf( 'A request from \'%s\' initial referer was blocked because of the domain \'%s\' is blocked.', $first_hit_params->hit_referer, $Domain->get( 'name' ) );
		exit_blocked_request( 'Domain of initial referer', $log_message ); // WILL exit();
	}
}


/**
 * Block request by country
 *
 * @param integer country ID
 * @param boolean set true to block the requet here, or false to handle outside the function
 * @return boolean true if blocked, false otherwise
 */
function antispam_block_by_country( $country_ID, $assert = true )
{
	global $DB;

	$CountryCache = & get_CountryCache();
	$Country = $CountryCache->get_by_ID( $country_ID, false );

	if( $Country && $Country->get( 'status' ) == 'blocked' )
	{ // The country exists in the database and has blocked status
		if( $assert )
		{ // block the request
			$log_message = sprintf( 'A request from \'%s\' was blocked because of this country is blocked.', $Country->get_name() );
			exit_blocked_request( 'Country', $log_message ); // WILL exit();
		}
		// Update the number of requests from blocked countries
		$DB->query( 'UPDATE T_regional__country
			SET ctry_block_count = ctry_block_count + 1
			WHERE ctry_ID = '.$Country->ID );
		return true;
	}

	return false;
}


/**
 * Block request by email address and its domain
 */
function antispam_block_by_email( $email_address )
{
	if( mail_is_blocked( $email_address ) )
	{	// Email address is blocked completely
		$log_message = sprintf( 'A request was blocked because of the email address \'%s\' is blocked.', $email_address );
		exit_blocked_request( 'Email address', $log_message );
		// WILL exit();
	}

	// Extract a domain from the email address:
	$email_domain = preg_replace( '#^[^@]+@#', '', $email_address );

	if( ! empty( $email_domain ) &&
			$Domain = & get_Domain_by_subdomain( $email_domain ) &&
			$Domain->get( 'status' ) == 'blocked' )
	{	// The request from domain of the email address must be blocked:
		$log_message = sprintf( 'A request was blocked because of the domain \'%s\' of the email address \'%s\' is blocked.', $Domain->get( 'name' ), $email_address );
		exit_blocked_request( 'Domain of email address', $log_message );
		// WILL exit();
	}
}


/**
 * Check if we can move current user to suspect group
 *
 * @param integer|NULL User ID, NULL = $current_User
 * @param boolean TRUE to check if user is in trust group
 * @return boolean TRUE if current user can be moved
 */
function antispam_suspect_check( $user_ID = NULL, $check_trust_group = true )
{
	global $Settings;

	$suspicious_group_ID = $Settings->get('antispam_suspicious_group');

	if( empty( $suspicious_group_ID ) )
	{ // We don't need to move users to suspicious group
		return false;
	}

	if( is_null( $user_ID ) )
	{ // current User
		global $current_User;
		$User = $current_User;
	}
	else
	{ // Get User by ID
		$UserCache = & get_UserCache();
		$User = $UserCache->get_by_ID( $user_ID, false, false );
	}

	if( empty( $User ) )
	{ // User must be logged in for this action
		return false;
	}

	if( $User->grp_ID == $suspicious_group_ID )
	{ // Current User already is in suspicious group
		return false;
	}

	if( $check_trust_group )
	{ // Check if user is in trust group
		$antispam_trust_groups = $Settings->get('antispam_trust_groups');
		if( !empty( $antispam_trust_groups ) )
		{
			$antispam_trust_groups = explode( ',', $antispam_trust_groups );
			if( in_array( $User->grp_ID, $antispam_trust_groups ) )
			{ // Current User has group which cannot be moved to suspicious users
				return false;
			}
		}
	}

	// We can move current user to suspect group
	return true;
}


/**
 * Check if the requested data are suspected
 *
 * @param array Array of what should be checked: 'IP_address', 'domain', 'email_domain', 'country_ID', 'country_IP'
 * @return boolean TRUE if at least one requested data item is suspected
 */
function antispam_suspect_check_by_data( $data = array() )
{
	$is_suspected = false;

	foreach( $data as $data_key => $data_item )
	{
		if( empty( $data_item ) )
		{	// Skip empty value:
			continue;
		}

		switch( $data_key )
		{
			case 'IP_address':
				// Check by IP address:
				$IPRangeCache = & get_IPRangeCache();
				$IPRange = & $IPRangeCache->get_by_ip( $data_item, false, false );
				$is_suspected = ( $IPRange && in_array( $IPRange->get( 'status' ), array( 'suspect', 'very_suspect' ) ) );
				break;

			case 'domain':
			case 'email_domain':
				// Check by domain or domain of email address:
				load_funcs( 'sessions/model/_hitlog.funcs.php' );
				if( $data_key == 'email_domain' )
				{	// Extract domain from email address:
					$data_item = preg_replace( '#^[^@]+@#', '', $data_item );
				}
				$Domain = & get_Domain_by_subdomain( $data_item );
				$is_suspected = ( $Domain && $Domain->get( 'status' ) == 'suspect' );
				break;

			case 'country_ID':
				// Check by country ID:
				$CountryCache = & get_CountryCache();
				$Country = & $CountryCache->get_by_ID( $data_item, false, false );
				$is_suspected = ( $Country && $Country->get( 'status' ) == 'suspect' );
				break;

			case 'country_IP':
				// Check by country IP address:
				$Plugins_admin = & get_Plugins_admin();
				if( ( $geoip_Plugin = & $Plugins_admin->get_by_code( 'evo_GeoIP' ) ) &&
				    method_exists( $geoip_Plugin, 'get_country_by_IP' ) && 
				    ( $geoip_Country = $geoip_Plugin->get_country_by_IP( $data_item ) ) )
				{	// Check country only if it is found by GeoIP plugin:
					$is_suspected = ( $geoip_Country->get( 'status' ) == 'suspect' );
				}
				break;
		}

		if( $is_suspected )
		{	// Don't search next i current is already suspected:
			break;
		}
	}

	return $is_suspected;
}


/**
 * Move user to suspicious group
 *
 * @param integer User ID
 * @return boolean TRUE if user was moved to suspicious group
 */
function antispam_suspect_move_user( $user_ID = NULL )
{
	global $Settings;

	$GroupCache = & get_GroupCache();
	if( ! ( $suspicious_Group = & $GroupCache->get_by_ID( intval( $Settings->get( 'antispam_suspicious_group' ) ), false, false ) ) )
	{	// Group exists in DB and we can change user's group:
		return false;
	}

	if( $user_ID === NULL )
	{	// If user_ID was not set, use the current User:
		global $current_User;
		$User = $current_User;
	}
	else
	{	// Get User by given ID:
		$UserCache = & get_UserCache();
		$User = $UserCache->get_by_ID( $user_ID, false, false );
	}

	if( $User )
	{	// Change user group only if it is detected:
		$User->set_Group( $suspicious_Group );
		return $User->dbupdate();
	}

	return false;
}


/**
 * Move user to suspect group by IP address
 *
 * @param string IP address, Empty value to use current IP address
 * @param integer|NULL User ID, NULL = $current_User
 * @param boolean TRUE to check if user is in trust group
 */
function antispam_suspect_user_by_IP( $IP_address = '', $user_ID = NULL, $check_trust_group = true )
{
	global $Timer;

	$Timer->start( 'suspect_user_by_IP' );

	if( ! antispam_suspect_check( $user_ID, $check_trust_group ) )
	{	// Current user cannot be moved to suspect group
		$Timer->stop( 'suspect_user_by_IP' );
		return;
	}

	if( empty( $IP_address ) )
	{
		$IP_address = get_ip_list( true );
	}

	// Check by IP address:
	if( antispam_suspect_check_by_data( array( 'IP_address' => $IP_address ) ) )
	{	// Move the user to suspicious group because current IP address is suspected:
		antispam_suspect_move_user( $user_ID );
	}

	$Timer->stop( 'suspect_user_by_IP' );
}


/**
 * Move user to suspect group by reverse DNS domain(that is generated from IP address on user's registration)
 *
 * @param integer|NULL User ID, NULL = $current_User
 * @param boolean TRUE to check if user is in trust group
 */
function antispam_suspect_user_by_reverse_dns_domain( $user_ID = NULL, $check_trust_group = true )
{
	global $UserSettings, $Timer;

	$Timer->start( 'suspect_user_by_reverse_dns_domain' );

	if( ! antispam_suspect_check( $user_ID, $check_trust_group ) )
	{	// Current user cannot be moved to suspect group:
		$Timer->stop( 'suspect_user_by_reverse_dns_domain' );
		return;
	}

	// Get user's reverse DNS domain that was generated from IP address on registration by function gethostbyaddr()
	$reverse_dns_domain = $UserSettings->get( 'user_registered_from_domain', $user_ID );

	// Check by reverse DNS subdomain:
	if( antispam_suspect_check_by_data( array( 'domain' => $reverse_dns_domain ) ) )
	{	// Move the user to suspicious group because the reverse DNS has a suspect status:
		antispam_suspect_move_user( $user_ID );
	}

	$Timer->stop( 'suspect_user_by_reverse_dns_domain' );
}


/**
 * Move user to suspect group by domain of email address
 *
 * @param integer|NULL User ID, NULL = $current_User
 * @param boolean TRUE to check if user is in trust group
 */
function antispam_suspect_user_by_email_domain( $user_ID = NULL, $check_trust_group = true )
{
	global $Timer;

	$Timer->start( 'suspect_user_by_email_domain' );

	if( ! antispam_suspect_check( $user_ID, $check_trust_group ) )
	{	// Current user cannot be moved to suspect group:
		$Timer->stop( 'suspect_user_by_email_domain' );
		return;
	}

	if( $user_ID === NULL )
	{	// If user_ID was not set, use the current User:
		global $current_User;
		$User = $current_User;
	}
	else
	{	// Get User by given ID:
		$UserCache = & get_UserCache();
		$User = $UserCache->get_by_ID( $user_ID, false, false );
	}

	if( empty( $User ) )
	{	// User must be defined for this action
		$Timer->stop( 'suspect_user_by_email_domain' );
		return;
	}

	// Check by reverse DNS subdomain:
	if( antispam_suspect_check_by_data( array( 'email_domain' => $User->get( 'email' ) ) ) )
	{	// Move the user to suspicious group because the reverse DNS has a suspect status:
		antispam_suspect_move_user( $user_ID );
	}

	$Timer->stop( 'suspect_user_by_email_domain' );
}


/**
 * Move user to suspect group by Country ID
 *
 * @param integer Country ID
 * @param integer|NULL User ID, NULL = $current_User
 * @param boolean TRUE to check if user is in trust group
 */
function antispam_suspect_user_by_country( $country_ID, $user_ID = NULL, $check_trust_group = true )
{
	global $Timer;

	$Timer->start( 'suspect_user_by_country' );

	if( !antispam_suspect_check( $user_ID, $check_trust_group ) )
	{	// Current user cannot be moved to suspect group:
		$Timer->stop( 'suspect_user_by_country' );
		return;
	}

	// Check by country ID:
	if( antispam_suspect_check_by_data( array( 'country_ID' => $country_ID ) ) )
	{	// Move current user to suspicious group because country is suspected:
		antispam_suspect_move_user( $user_ID );
	}

	$Timer->stop( 'suspect_user_by_country' );
}


/**
 * Get status titles of ip range
 *
 * @param boolean TRUE - to include false statuses, which don't exist in DB
 * @return array Status titles
 */
function aipr_status_titles( $include_false_statuses = true )
{
	$status_titles = array();
	$status_titles['trusted'] = T_('Trusted');
	$status_titles['probably_ok'] = T_('Probably OK');
	if( $include_false_statuses )
	{ // Include Unknown status
		$status_titles[''] = T_('Unknown');
	}
	$status_titles['suspect'] = T_('Suspect');
	$status_titles['very_suspect'] = T_('Very Suspect');
	$status_titles['blocked'] = T_('Blocked');

	return $status_titles;
}


/**
 * Get status colors of ip range
 *
 * @return array Color values
 */
function aipr_status_colors()
{
	return array(
			''             => '999999',
			'trusted'      => '00CC00',
			'probably_ok'  => '00FFFF',
			'suspect'      => 'FFAA00',
			'very_suspect' => 'FF8000',
			'blocked'      => 'FF0000',
		);
}


/**
 * Get array of status icons for email address
 *
 * @return array Status icons
 */
function aipr_status_icons()
{
	return array(
			''             => get_icon( 'bullet_white', 'imgtag', array( 'title' => aipr_status_title( '' ) ) ),
			'trusted'      => get_icon( 'bullet_green', 'imgtag', array( 'title' => aipr_status_title( 'trusted' ) ) ),
			'probably_ok'  => get_icon( 'bullet_cyan', 'imgtag', array( 'title' => aipr_status_title( 'probably_ok' ) ) ),
			'suspect'      => get_icon( 'bullet_orange', 'imgtag', array( 'title' => aipr_status_title( 'suspect' ) ) ),
			'very_suspect' => get_icon( 'bullet_redorange', 'imgtag', array( 'title' => aipr_status_title( 'very_suspect' ) ) ),
			'blocked'      => get_icon( 'bullet_red', 'imgtag', array( 'title' => aipr_status_title( 'blocked' ) ) )
		);
}


/**
 * Get status title of ip range by status value
 *
 * @param string Status value
 * @return string Status title
 */
function aipr_status_title( $status )
{
	$aipr_statuses = aipr_status_titles();

	return isset( $aipr_statuses[ $status ] ) ? $aipr_statuses[ $status ] : $status;
}


/**
 * Get status color of ip range by status value
 *
 * @param string Status value
 * @return string Color value
 */
function aipr_status_color( $status )
{
	if( $status == 'NULL' )
	{
		$status = '';
	}

	$aipr_status_colors = aipr_status_colors();

	return isset( $aipr_status_colors[ $status ] ) ? '#'.$aipr_status_colors[ $status ] : 'none';
}


/**
 * Get status icon of ip range by status value
 *
 * @param string Status value
 * @return string Icon
 */
function aipr_status_icon( $status )
{
	$aipr_status_icons = aipr_status_icons();

	return isset( $aipr_status_icons[ $status ] ) ? $aipr_status_icons[ $status ] : '';
}


/**
 * Get blogs with comments numbers
 *
 * @param string Comment status
 * @return array Blogs
 */
function antispam_bankruptcy_blogs( $comment_status = NULL )
{
	global $DB, $Settings;

	$SQL = new SQL( 'Get blogs list with number of comments' );
	$SQL->SELECT( 'blog_ID, blog_name, COUNT( comment_ID ) AS comments_count' );
	$SQL->FROM( 'T_comments' );
	$SQL->FROM_add( 'INNER JOIN T_items__item ON comment_item_ID = post_ID' );
	$SQL->FROM_add( 'INNER JOIN T_categories ON post_main_cat_ID = cat_ID' );
	$SQL->FROM_add( 'INNER JOIN T_blogs ON cat_blog_ID = blog_ID' );
	if( !empty( $comment_status ) )
	{ // Limit by comment status
		$SQL->WHERE( 'comment_status = '.$DB->quote( $comment_status ) );
	}
	$SQL->GROUP_BY( 'blog_ID' );
	$SQL->ORDER_BY( 'blog_'.$Settings->get('blogs_order_by').' '.$Settings->get('blogs_order_dir') );

	return $DB->get_results( $SQL );
}


/**
 * Delete ALL comments from selected blogs
 *
 * @param string Comment status
 * @param array Blog IDs
 */
function antispam_bankruptcy_delete( $blog_IDs = array(), $comment_status = NULL )
{
	global $DB;

	if( empty( $blog_IDs ) )
	{ // No blogs selected
		echo T_('Please select at least one blog.');
		return;
	}

	echo T_('The comments are deleting...');
	evo_flush();

	$DB->begin();

	$items_IDs_SQL = new SQL( 'Get all posts IDs of selected blogs' );
	$items_IDs_SQL->SELECT( 'postcat_post_ID' );
	$items_IDs_SQL->FROM( 'T_postcats' );
	$items_IDs_SQL->FROM_add( 'INNER JOIN T_categories ON postcat_cat_ID = cat_ID' );
	$items_IDs_SQL->WHERE( 'cat_blog_ID IN ( '.$DB->quote( $blog_IDs ).' )' );
	$items_IDs = $DB->get_col( $items_IDs_SQL );

	$comments_IDs_SQL = new SQL( 'Get all comments IDs of selected blogs' );
	$comments_IDs_SQL->SELECT( 'comment_ID' );
	$comments_IDs_SQL->FROM( 'T_comments' );
	$comments_IDs_SQL->WHERE( 'comment_item_ID IN ( '.$DB->quote( $items_IDs ).' )' );
	if( !empty( $comment_status ) )
	{ // Limit by comment status
		$comments_IDs_SQL->WHERE_and( 'comment_status = '.$DB->quote( $comment_status ) );
	}

	$affected_rows = 1;
	while( $affected_rows > 0 )
	{
		$affected_rows = 0;

		// Delete the cascades
		$affected_rows += $DB->query( 'DELETE FROM T_links
			WHERE link_cmt_ID IN ( '.$comments_IDs_SQL->get().' )
			LIMIT 10000' );
		$affected_rows += $DB->query( 'DELETE FROM T_comments__prerendering
			WHERE cmpr_cmt_ID IN ( '.$comments_IDs_SQL->get().' )
			LIMIT 10000' );
		$affected_rows += $DB->query( 'DELETE FROM T_comments__votes
			WHERE cmvt_cmt_ID IN ( '.$comments_IDs_SQL->get().' )
			LIMIT 10000' );

		// Delete the comments
		$sql_comments_where = '';
		if( !empty( $comment_status ) )
		{ // Limit by comment status
			$sql_comments_where = ' AND comment_status = '.$DB->quote( $comment_status );
		}
		$affected_rows += $DB->query( 'DELETE FROM T_comments
			WHERE comment_item_ID IN ( '.$DB->quote( $items_IDs ).' )'.
			$sql_comments_where.'
			LIMIT 10000' );

		echo ' .';
		evo_flush();
	}

	echo 'OK';

	$DB->commit();
}


/**
 * Increase a counter in DB antispam ip range table
 *
 * @param string Counter name: 'user', 'contact_email'
 */
function antispam_increase_counter( $counter_name )
{
	switch( $counter_name )
	{
		case 'user':
			$field_name = 'aipr_user_count';
			break;

		case 'contact_email':
			$field_name = 'aipr_contact_email_count';
			break;

		default:
			debug_die( 'Wrong antispam counter name' );
	}

	foreach( get_ip_list() as $ip )
	{
		if( $ip === '' )
		{ // Skip an empty
			continue;
		}

		// Convert IPv6 to IPv4:
		$ip_int = ip2int( $ip );
		$ip = int2ip( $ip_int );
		if( preg_match( '#^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$#i', $ip ) )
		{	// If current IP address has a correct format:
			$ip_24bit_start = ip2int( preg_replace( '#\.\d{1,3}$#i', '.0', $ip ) );
			$ip_24bit_end = ip2int( preg_replace( '#\.\d{1,3}$#i', '.255', $ip ) );

			global $DB;
			if( $ipranges = get_ip_ranges( $ip_24bit_start, $ip_24bit_end ) )
			{	// If at least one IP range is detected:
				$ip_range_is_detected = false;
				foreach( $ipranges as $iprange )
				{
					if( $iprange->aipr_IPv4start <= $ip_int && $iprange->aipr_IPv4end >= $ip_int )
					{	// If current IP is from the IP range:
						$DB->query( 'UPDATE T_antispam__iprange
								SET '.$field_name.' = '.$field_name.' + 1
								WHERE aipr_ID = '.$DB->quote( $iprange->aipr_ID ) );
						$ip_range_is_detected = true;
					}
				}
				if( ! $ip_range_is_detected )
				{	// If IP range is not detected for current IP address,
					// Try to find what range is free for current IP address:
					$iprange_max = NULL;
					$iprange_min = NULL;
					foreach( $ipranges as $iprange )
					{
						if( ( $iprange_min === NULL || $iprange_min < $iprange->aipr_IPv4end ) && $ip_int > $iprange->aipr_IPv4end )
						{	// Min free possible IP value:
							$iprange_min = $iprange->aipr_IPv4end + 1;
						}
						if( ( $iprange_max === NULL || $iprange_max > $iprange->aipr_IPv4start ) && $ip_int < $iprange->aipr_IPv4start )
						{	// Max free possible IP value:
							$iprange_max = $iprange->aipr_IPv4start - 1;
						}
					}
					if( $iprange_min === NULL )
					{	// Use begining of range *.*.*.0 if no found:
						$iprange_min = $ip_24bit_start;
					}
					if( $iprange_max === NULL )
					{	// Use ending of range *.*.*.255 if no found:
						$iprange_max = $ip_24bit_end;
					}
					// Insert new IP range with possible free values:
					$DB->query( 'INSERT INTO T_antispam__iprange ( aipr_IPv4start, aipr_IPv4end, '.$field_name.' )
									VALUES ( '.$DB->quote( $iprange_min ).', '.$DB->quote( $iprange_max ).', 1 ) ' );
				}
			}
			else
			{	// Insert new IP range with values from *.*.*.0 to *.*.*.255:
				$DB->query( 'INSERT INTO T_antispam__iprange ( aipr_IPv4start, aipr_IPv4end, '.$field_name.' )
								VALUES ( '.$DB->quote( $ip_24bit_start ).', '.$DB->quote( $ip_24bit_end ).', 1 ) ' );
			}
		}
	}
}


/**
 * Get WHOIS information
 *
 * @param string Domain or IP address to query for WHOIS
 * @param integer Window height to limit the result display
 * @return string WHOIS query result
 */
function antispam_get_whois( $query = NULL, $window_height = NULL )
{
	global $admin_url;

	load_class('_ext/phpwhois/whois.main.php', 'whois' );

	$whois = new Whois();

	// Set to true if you want to allow proxy requests
	$allowproxy = false;

	// get faster but less acurate results
	$whois->deep_whois = empty( $_GET['fast'] );

	// To use special whois servers (see README)
	//$whois->UseServer( 'uk', 'whois.nic.uk:1043?{hname} {ip} {query}' );
	//$whois->UseServer( 'au', 'whois-check.ausregistry.net.au' );

	// Comment the following line to disable support for non ICANN tld's
	$whois->non_icann = true;

	$result = $whois->Lookup( $query );

	if( empty( $window_height ) )
	{
		$winfo = '<pre>';
	}
	else
	{
		$winfo = '<pre style="height: '.( $window_height - 200 ).'px; overflow: auto;">';
	}

	if( ! empty( $result['rawdata'] ) )
	{
		for( $i = 0; $i < count( $result['rawdata'] ); $i++ )
		{
			// Highlight lines starting with orgname: or org-name: (case insensitive)
			if( preg_match( '/^(orgname:|org-name:|descr:)/i', $result['rawdata'][$i] ) )
			{
				$result['rawdata'][$i] = '<span style="font-weight: bold; background-color: yellow;">'.$result['rawdata'][$i].'</span>';
			}

			// Make URLs and emails clickable
			if( preg_match_all( '#[-a-zA-Z0-9@:%_\+.~\#?&//=]{2,256}\.[a-z]{2,4}\b(\/[-a-zA-Z0-9@:%_\+.~\#?&//=;]*)?#si', $result['rawdata'][$i], $matches ) )
			{
				foreach( $matches as $match )
				{
					if( filter_var( $match[0], FILTER_VALIDATE_EMAIL ) )
					{ // check if valid email
						$href_string = 'mailto:'.$match[0];
						$result['rawdata'][$i] = str_replace( $match[0], '<a href="'.$href_string.'">'.$match[0].'</a>', $result['rawdata'][$i] );
					}
					else
					{ // check if valid URL
						$href_string = ( ! preg_match( '#^(ht|f)tps?://#', $match[0] ) ) // check if protocol not present
								? 'http://' . $match[0] // temporarily add one
								: $match[0]; // use current
						if( filter_var( $href_string, FILTER_VALIDATE_URL ) )
						{
							$result['rawdata'][$i] = str_replace( $match[0], '<a href="'.$href_string.'" target="_blank">'.$match[0].'</a>', $result['rawdata'][$i] );
						}
					}
				}
			}

			// Make IP ranges clickable
			if( check_user_perm( 'spamblacklist', 'view' ) &&
					preg_match_all( '#(?<=\:)(\s*)(\b(?:(?:25[0-5]|[0-9]|2[0-4][0-9]|1[0-9]{2}|[1-9][0-9])\.){3}(?:25[0-5]|[0-9]|2[0-4][0-9]|1[0-9]{2}|[1-9][0-9])\s?-\s?(?:(?:25[0-5]|[0-9]|2[0-4][0-9]|1[0-9]{2}|[1-9][0-9])\.){3}(?:25[0-5]|[0-9]|2[0-4][0-9]|1[0-9]{2}|[1-9][0-9])\b)#', $result['rawdata'][$i], $matches ) )
			{
				$aipr_status_titles = aipr_status_titles();
				// Try to get IP range from DB:
				$IPRangeCache = & get_IPRangeCache();
				if( $IPRange = & $IPRangeCache->get_by_ip( $query ) )
				{	// Get status of IP range if it exists in DB:
					$iprange_status = $IPRange->get( 'status' );
				}
				else
				{	// Use "Unknown" status for new IP range:
					$iprange_status = '';
				}

				$ip_range_text = $matches[2][0];
				$whois_IPs = explode( '-', $ip_range_text );
				$whois_IP_start = isset( $whois_IPs[0] ) ? trim( $whois_IPs[0] ) : '';
				$whois_IP_end = isset( $whois_IPs[1] ) ? trim( $whois_IPs[1] ) : '';
				if( check_user_perm( 'spamblacklist', 'edit' ) )
				{	// If current user has a permission to edit IP ranges:
					if( $IPRange )
					{	// If IP range is found in DB:
						$db_IP_start = int2ip( $IPRange->get( 'IPv4start' ) );
						$db_IP_end = int2ip( $IPRange->get( 'IPv4end' ) );
						// Display IP range with status from DB to edit it:
						$ip_range_text = '<a href="'.$admin_url.'?ctrl=antispam&amp;tab3=ipranges&amp;'
							.'action=iprange_edit&amp;iprange_ID='.$IPRange->ID.'">'
								.$db_IP_start.' - '.$db_IP_end
							.'</a>';
						if( $db_IP_start != $whois_IP_start || $db_IP_end != $whois_IP_end )
						{	// If IP range of "whois" tool is NOT same as IP range from DB,
							// Display a link to create new IP range from suggested IPs by "whois" tool:
							$whois_ip_range_create_link = '<a href="'.$admin_url.'?ctrl=antispam&amp;tab3=ipranges&amp;'
								.'action=iprange_new&amp;ip_start='.$whois_IP_start.'&amp;ip_end='.$whois_IP_end.'">'
									.$whois_IP_start.' - '.$whois_IP_end
								.'</a>';
							if( $IPRange->get( 'IPv4start' ) <= ip2int( $whois_IP_start ) && $IPRange->get( 'IPv4end' ) >= ip2int( $whois_IP_end ) )
							{	// If IP range of "whois" tool is PART of IP range from DB then
								// Display "whois" IP range link with "Unknown" status BEFORE DB IP range:
								$ip_range_text = $whois_ip_range_create_link
									.' <div id="iprange_status_icon" class="status_icon">'.aipr_status_icon( '' ).'</div>'.$aipr_status_titles['']
									.' included in '.$ip_range_text;
							}
							else
							{	// If IP range of "whois" tool is INERTSECTING with IP range from DB then
								// Display ONLY "whois" IP range link with "Unknown" status,
								// (don't display DB IP range because it will be suggested to edit on creating new intersecting IP range):
								$ip_range_text = $whois_ip_range_create_link;
								$iprange_status = '';
							}
						}
					}
					else
					{	// Display a link to create new IP range if it doesn't exist in DB yet:
						$ip_range_text = '<a href="'.$admin_url.'?ctrl=antispam&amp;tab3=ipranges&amp;'
							.'action=iprange_new&amp;ip_start='.$whois_IP_start.'&amp;ip_end='.$whois_IP_end.'">'
								.$ip_range_text
							.'</a>';
					}
				}
				// Display status of IP range:
				$ip_range_text .= ' <div id="iprange_status_icon" class="status_icon">'.aipr_status_icon( $iprange_status ).'</div>'.$aipr_status_titles[ $iprange_status ];

				// Replace static IP range of "whois" tool with links and ip range status to view/edit/create IP range in back-office:
				$result['rawdata'][$i] = str_replace( $matches[2][0], $ip_range_text, $result['rawdata'][$i] );
			}
		}
		$winfo .= format_to_output( implode( "\n", $result['rawdata'] ) );
	}
	else
	{
		$winfo = format_to_output( implode( "\n", $whois->Query['errstr'] ) )."<br></br>";
	}
	$winfo .= '</pre>';

	return $winfo;
}
?>
