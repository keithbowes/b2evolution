<?php
/**
 * This file implements the Hitlist class.
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
 *
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * A list of hits. Provides functions for maintaining and extraction of Hits.
 *
 * @package evocore
 */
class Hitlist
{
	/**
	 * Delete all hits for a specific date
	 *
	 * @param int unix timestamp to delete hits for
	 * @return mixed Return value of {@link DB::query()}
	 */
	static function prune( $date )
	{
		global $DB;

		// We should also delete goal hits to avoid goal hits rows with wrong ghit_hit_ID:
		Hitlist::prune_goal_hits( $date );

		return $DB->query( 'DELETE FROM T_hitlog
			WHERE DATE_FORMAT( hit_datetime, "%Y-%m-%d" ) = '.$DB->quote( date( 'Y-m-d', $date ) ),
			'Prune hits for a specific date' );
	}


	/**
	 * Delete all goal hits for a specific date
	 *
	 * @param int unix timestamp to delete hits for
	 * @return mixed Return value of {@link DB::query()}
	 */
	static function prune_goal_hits( $date )
	{
		global $DB;

		return $DB->query( 'DELETE T_track__goalhit FROM T_track__goalhit
			INNER JOIN T_hitlog ON ghit_hit_ID = hit_ID
			WHERE DATE_FORMAT( hit_datetime, "%Y-%m-%d" ) = '.$DB->quote( date( 'Y-m-d', $date ) ),
			'Prune goal hits for a specific date' );
	}


	/**
	 * Change type for a hit
	 *
	 * @param int ID to change
	 * @param string new type, must be valid ENUM for hit_referer_type field
	 * @return mixed Return value of {@link DB::query()}
	 */
	static function change_type( $hit_ID, $type )
	{
		global $DB;

		$sql = '
				UPDATE T_hitlog
				   SET hit_referer_type = '.$DB->quote( $type ).',
				       hit_datetime = hit_datetime ' /* prevent mySQL from updating timestamp */ .'
				 WHERE hit_ID = '.$DB->quote( $hit_ID );
		return $DB->query( $sql, 'Change type for a specific hit' );
	}


	/**
	 * Log a message of the hits pruning process
	 *
	 * @param string Message
	 * @param boolean|string 'cron_job' - to log messages for cron job
	 * @param boolean TRUE is end of cron action
	 * @return string
	 */
	static function log_pruning( $message, $output_message = false, $is_end_action = false )
	{
		if( $output_message === 'cron_job' )
		{	// Log a message for cron job:
			if( $is_end_action )
			{
				cron_log_action_end( $message );
			}
			else
			{
				cron_log_append( $message );
			}
		}

		return $message."\n";
	}



	/**
	 * Auto pruning of old stats.
	 *
	 * It uses a general setting to store the day of the last prune, avoiding multiple prunes per day.
	 * fplanque>> Check: How much faster is this than DELETING right away with an INDEX on the date field?
	 *
	 * Note: we're using {@link $localtimenow} to log hits, so use this for pruning, too.
	 *
	 * NOTE: do not call this directly, but only in conjuction with auto_prune_stats_mode.
	 *
	 * @param boolean|string TRUE to print out messages, 'cron_job' - to log messages for cron job
	 * @param boolean TRUE to limit a pruning by one execution per day
	 * @return array array(
	 *   'result'  => 'error' | 'ok'
	 *   'message' => Message of the error or result data
	 * )
	 */
	static function dbprune( $output_message = true, $day_limit = true )
	{
		/**
		 * @var DB
		 */
		global $DB, $tableprefix;
		global $Debuglog, $Settings, $localtimenow;
		global $Plugins, $Messages;

		$return_message = '';

		// Prune when $localtime is a NEW day (which will be the 1st request after midnight):
		$last_prune = $Settings->get( 'auto_prune_stats_done' );
		if( $last_prune >= date( 'Y-m-d', $localtimenow ) && $last_prune <= date( 'Y-m-d', $localtimenow + 86400 ) )
		{ // Already pruned today (and not more than one day in the future -- which typically never happens)
			$error_message = Hitlist::log_pruning( T_('Pruning has already been done today'), $output_message );
			if( $output_message )
			{
				$Messages->add( $error_message, 'error' );
			}
			if( $day_limit )
			{	// Limit pruning to one execution per day:
				return array(
						'result'  => 'error',
						'message' => $error_message
					);
			}
			else
			{	// Don't limit by day but display a warning:
				$return_message .= Hitlist::log_pruning( '<span class="text-danger">'.T_('WARNING').': '.$error_message.'</span>', $output_message );
			}
		}

		// DO NOT TRANSLATE! (This is sysadmin level info -- we assume they can read English)
		$return_message .= Hitlist::log_pruning( 'STATUS:', $output_message );

		// Get tables info:
		$tables_info = $DB->get_results( 'SHOW TABLE STATUS WHERE Name IN ( '.$DB->quote( array( 'T_hitlog', 'T_track__goalhit', 'T_sessions', 'T_basedomains' ) ).' )' );
		foreach( $tables_info as $table_info )
		{
			$return_message .= Hitlist::log_pruning( preg_replace( '/^'.preg_quote( $tableprefix ).'/', 'T_', $table_info->Name ).': '.$table_info->Engine.' - '.$table_info->Rows.' rows', $output_message, true );
		}

		// Init Timer for hitlist
		// Note: Don't use global $Timer because it works only in debug mode
		load_class( '_core/model/_timer.class.php', 'Timer' );
		$hitlist_Timer = new Timer( 'prune_hits' );

		$time_prune_before = ( $localtimenow - ( $Settings->get( 'auto_prune_stats' ) * 86400 ) ); // 1 day = 86400 seconds

		$return_message .= Hitlist::log_pruning( "\n".'AGGREGATING:', $output_message );

		// Aggregate the hits before they will be deleted below:
		$hitlist_Timer->start( 'aggregate' );
		Hitlist::aggregate_hits();
		$hitlist_Timer->stop( 'aggregate' );
		$return_message .= Hitlist::log_pruning( sprintf( 'Aggregate the rows from %s to %s, Execution time: %s seconds', 'T_hitlog', 'T_hits__aggregate', $hitlist_Timer->get_duration( 'aggregate' ) ), $output_message, true );

		// Aggregate the goal hits before they will be deleted below:
		$hitlist_Timer->start( 'aggregate_goal_hits' );
		Hitlist::aggregate_goal_hits();
		$hitlist_Timer->stop( 'aggregate_goal_hits' );
		$return_message .= Hitlist::log_pruning( sprintf( 'Aggregate the rows from %s to %s, Execution time: %s seconds', 'T_track__goalhit', 'T_track__goalhit_aggregate', $hitlist_Timer->get_duration( 'aggregate_goal_hits' ) ), $output_message, true );

		// Aggregate the counts of unique sessions:
		$session_types = array(
			'coll_browser' => 'ONLY collection browser sessions',
			'coll_api'     => 'ONLY collection API sessions',
			'all_browser'  => 'ALL browser sessions',
			'all_api'      => 'ALL API sessions',
		);
		foreach( $session_types as $session_type => $session_type_desc )
		{
			$hitlist_Timer->start( 'aggregate_sessions_'.$session_type );
			Hitlist::aggregate_sessions( $session_type );
			$hitlist_Timer->stop( 'aggregate_sessions_'.$session_type );
			$return_message .= Hitlist::log_pruning( sprintf( 'Aggregate '.$session_type_desc.' from %s to %s, Execution time: %s seconds', 'T_hitlog', 'T_hits__aggregate_sessions', $hitlist_Timer->get_duration( 'aggregate_sessions_'.$session_type ) ), $output_message, true );
		}

		// PRUNE:
		$return_message .= Hitlist::log_pruning( "\n".'PRUNING:', $output_message );

		// PRUNE GOAL HITS:
		$hitlist_Timer->start( 'goal_hits' );
		$goal_hits_rows_affected = $DB->query( 'DELETE T_track__goalhit FROM T_track__goalhit
			INNER JOIN T_hitlog ON ghit_hit_ID = hit_ID
			WHERE hit_datetime < "'.date( 'Y-m-d', $time_prune_before ).'"', 'Autopruning goal hits' );
		$hitlist_Timer->stop( 'goal_hits' );
		$Debuglog->add( 'Hitlist::dbprune(): autopruned '.$goal_hits_rows_affected.' rows from T_track__goalhit.', 'request' );
		$return_message .= Hitlist::log_pruning( sprintf( '%s rows from %s, Execution time: %s seconds', $goal_hits_rows_affected, 'T_track__goalhit', $hitlist_Timer->get_duration( 'goal_hits' ) ), $output_message, true );

		// PRUNE HITLOG:
		$hitlist_Timer->start( 'hitlog' );
		$hitlog_rows_affected = $DB->query( '
			DELETE FROM T_hitlog
			WHERE hit_datetime < "'.date( 'Y-m-d', $time_prune_before ).'"', 'Autopruning hit log' );
		$hitlist_Timer->stop( 'hitlog' );
		$Debuglog->add( 'Hitlist::dbprune(): autopruned '.$hitlog_rows_affected.' rows from T_hitlog.', 'request' );
		$return_message .= Hitlist::log_pruning( sprintf( '%s rows from %s, Execution time: %s seconds', $hitlog_rows_affected, 'T_hitlog', $hitlist_Timer->get_duration( 'hitlog' ) ), $output_message, true );


		// PREPARE PRUNING SESSIONS:
		// Prune sessions that have timed out and are older than auto_prune_stats
		$sess_prune_before = ( $localtimenow - $Settings->get( 'timeout_sessions' ) );
		// IMPORTANT: we cut off at the oldest date between session timeout and sessions pruning.
		// So if session timeout is really long (2 years for example), the sessions table won't be pruned as small as expected from the pruning delay.
		$oldest_date = min( $sess_prune_before, $time_prune_before );

		// allow plugins to prune session based data
		$Plugins->trigger_event( 'BeforeSessionsDelete', $temp_array = array( 'cutoff_timestamp' => $oldest_date ) );

		// PRUNE SESSIONS:
		$sessions_rows_affected_total = 0;
		$sessions_rows_affected_i = 1;
		$sessions_i = 0;
		$hitlist_Timer->start( 'sessions_total' );
		while( $sessions_rows_affected_i )
		{
			$hitlist_Timer->start( 'sessions_i' );
			$sessions_rows_affected_i = $DB->query( 'DELETE FROM T_sessions
				WHERE
					( sess_user_ID IS NOT NULL AND sess_lastseen_ts < '.$DB->quote( date( 'Y-m-d H:i:s', $oldest_date ) ).' )
					OR
					( sess_user_ID IS NULL AND sess_lastseen_ts < '.$DB->quote( date( 'Y-m-d H:i:s', $time_prune_before ) ).' )
				LIMIT 1000',
				'Autoprune sessions' );
			$hitlist_Timer->stop( 'sessions_i' );
			if( $sessions_i == 0 || $sessions_rows_affected_i > 0 )
			{
				$Debuglog->add( 'Hitlist::dbprune(): autopruned '.$sessions_rows_affected_i.' rows from T_sessions.', 'request' );
				$return_message .= Hitlist::log_pruning( sprintf( '%s rows from %s, Execution time: %s seconds', $sessions_rows_affected_i, 'T_sessions', $hitlist_Timer->get_duration( 'sessions_i' ) ), $output_message, true );
				$sessions_rows_affected_total += $sessions_rows_affected_i;
			}
			$sessions_i++;
			if( $sessions_rows_affected_i < 1000 )
			{	// Don't try next query if current already is less 1000 records:
				break;
			}
		}
		$hitlist_Timer->stop( 'sessions_total' );
		if( $sessions_i > 1 )
		{	// Display total pruned sessions only if it was executed more 1 time per 1000 limited records:
			$Debuglog->add( 'Hitlist::dbprune(): Total autopruned '.$sessions_rows_affected_total.' rows from T_sessions.', 'request' );
			$return_message .= Hitlist::log_pruning( sprintf( 'Total %s rows from %s, Execution time: %s seconds', $sessions_rows_affected_total, 'T_sessions', $hitlist_Timer->get_duration( 'sessions_total' ) ), $output_message, true );
		}


		// PRUNE BASEDOMAINS:
		// Prune non-referrered basedomains (where the according hits got deleted)
		// BUT only those with unknown dom_type/dom_status, because otherwise this
		//     info is useful when we get hit again.
		$hitlist_Timer->start( 'basedomains' );
		$basedomains_rows_affected = $DB->query( '
			DELETE T_basedomains
			  FROM T_basedomains LEFT JOIN T_hitlog ON hit_referer_dom_ID = dom_ID
			 WHERE hit_referer_dom_ID IS NULL
			 AND dom_type = "unknown"
			 AND dom_status = "unknown"' );
		$hitlist_Timer->stop( 'basedomains' );
		$Debuglog->add( 'Hitlist::dbprune(): autopruned '.$basedomains_rows_affected.' rows from T_basedomains.', 'request' );
		$return_message .= Hitlist::log_pruning( sprintf( '%s rows from T_basedomains, Execution time: %s seconds', $basedomains_rows_affected, $hitlist_Timer->get_duration( 'basedomains' ) ), $output_message, true );


		// OPTIMIZE TABLES:
		$return_message .= Hitlist::log_pruning( "\n".'OPTIMIZING:', $output_message );

		$hitlist_Timer->start( 'optimize_hitlog' );
		$DB->query( 'OPTIMIZE TABLE T_hitlog' );
		$hitlist_Timer->stop( 'optimize_hitlog' );
		$return_message .= Hitlist::log_pruning( sprintf( 'T_hitlog: %s seconds', $hitlist_Timer->get_duration( 'optimize_hitlog' ) ), $output_message, true );

		$hitlist_Timer->start( 'optimize_goal_hits' );
		$DB->query( 'OPTIMIZE TABLE T_track__goalhit' );
		$hitlist_Timer->stop( 'optimize_goal_hits' );
		$return_message .= Hitlist::log_pruning( sprintf( 'T_track__goalhit: %s seconds', $hitlist_Timer->get_duration( 'optimize_goal_hits' ) ), $output_message, true );

		$hitlist_Timer->start( 'optimize_sessions' );
		$DB->query( 'OPTIMIZE TABLE T_sessions' );
		$hitlist_Timer->stop( 'optimize_sessions' );
		$return_message .= Hitlist::log_pruning( sprintf( 'T_sessions: %s seconds', $hitlist_Timer->get_duration( 'optimize_sessions' ) ), $output_message, true );

		$hitlist_Timer->start( 'optimize_basedomains' );
		$DB->query( 'OPTIMIZE TABLE T_basedomains' );
		$hitlist_Timer->stop( 'optimize_basedomains' );
		$return_message .= Hitlist::log_pruning( sprintf( 'T_basedomains: %s seconds', $hitlist_Timer->get_duration( 'optimize_basedomains' ) ), $output_message, true );


		// Stop total hitlist timer
		$hitlist_Timer->stop( 'prune_hits' );

		$return_message .= Hitlist::log_pruning( "\n".sprintf( 'Total execution time: %s seconds', $hitlist_Timer->get_duration( 'prune_hits' ) ), $output_message );

		$Settings->set( 'auto_prune_stats_done', date( 'Y-m-d H:i:s', $localtimenow ) ); // save exact datetime
		$Settings->dbupdate();

		if( $output_message )
		{
			$Messages->add( T_('The old hits & sessions have been pruned.'), 'success' );
		}
		return array(
				'result'  => 'ok',
				// DO NOT TRANSLATE! (This is sysadmin level info -- we assume they can read English)
				'message' => $return_message
			);
	}


	/**
	 * Aggregate the hits
	 */
	static function aggregate_hits()
	{
		global $DB;

		// NOTE: Do NOT aggregate current day because it is not ended yet
		$max_aggregate_date = date( 'Y-m-d H:i:s', mktime( 0, 0, 0 ) );

		$DB->query( 'REPLACE INTO T_hits__aggregate ( hagg_date, hagg_coll_ID, hagg_type, hagg_referer_type, hagg_agent_type, hagg_count )
			SELECT DATE( hit_datetime ) AS hit_date, IFNULL( hit_coll_ID, 0 ), hit_type, hit_referer_type, hit_agent_type, COUNT( hit_ID )
			  FROM T_hitlog
			 WHERE hit_datetime < '.$DB->quote( $max_aggregate_date ).'
			 GROUP BY hit_date, hit_coll_ID, hit_type, hit_referer_type, hit_agent_type',
			'Aggregate hits log' );
	}


	/**
	 * Aggregate the counts of unique sessions
	 *
	 * @param string What to aggregate?
	 *               - all: ALL sessions
	 *               - coll_browser: ONLY collection browser sessions
	 *               - coll_api: ONLY collection API sessions
	 *               - all_browser: ALL browser sessions
	 *               - all_api: ALL API sessions
	 */
	static function aggregate_sessions( $type = 'all' )
	{
		global $DB;

		// NOTE: Do NOT aggregate current day because it is not ended yet
		$max_aggregate_date = date( 'Y-m-d H:i:s', mktime( 0, 0, 0 ) );

		if( $type == 'all' || $type == 'coll_browser' )
		{	// ONLY collection browser sessions:
			$DB->query( 'REPLACE INTO T_hits__aggregate_sessions ( hags_date, hags_coll_ID, hags_count_browser )
				SELECT DATE( hit_datetime ) AS hit_date, hit_coll_ID, COUNT( DISTINCT hit_sess_ID )
				  FROM T_hitlog
				 WHERE hit_datetime < '.$DB->quote( $max_aggregate_date ).'
				   AND hit_agent_type = "browser"
				   AND hit_coll_ID > 0
				 GROUP BY hit_date, hit_coll_ID',
				'Aggregate ONLY collection sessions from hit log (hit_agent_type = "browser")' );
		}
		if( $type == 'all' || $type == 'coll_api' )
		{	// ONLY collection API sessions:
			$DB->query( 'INSERT INTO T_hits__aggregate_sessions ( hags_date, hags_coll_ID, hags_count_api )
				SELECT DATE( hit_datetime ) AS hit_date, hit_coll_ID, COUNT( DISTINCT hit_sess_ID )
				  FROM T_hitlog
				 WHERE hit_datetime < '.$DB->quote( $max_aggregate_date ).'
				   AND hit_type = "api"
				   AND hit_coll_ID > 0
				 GROUP BY hit_date, hit_coll_ID
				ON DUPLICATE KEY UPDATE hags_count_api = VALUES( hags_count_api )',
				'Aggregate ONLY collection sessions from hit log (hit_type = "api")' );
		}
		if( $type == 'all' || $type == 'all_browser' )
		{	// ALL browser sessions:
			$DB->query( 'REPLACE INTO T_hits__aggregate_sessions ( hags_date, hags_coll_ID, hags_count_browser )
				SELECT DATE( hit_datetime ) AS hit_date, 0, COUNT( DISTINCT hit_sess_ID )
				  FROM T_hitlog
				 WHERE hit_datetime < '.$DB->quote( $max_aggregate_date ).'
				   AND hit_agent_type = "browser"
				 GROUP BY hit_date',
				'Aggregate ALL sessions from hit log (hit_agent_type = "browser")' );
		}
		if( $type == 'all' || $type == 'all_api' )
		{	// ALL API sessions:
			$DB->query( 'INSERT INTO T_hits__aggregate_sessions ( hags_date, hags_coll_ID, hags_count_api )
				SELECT DATE( hit_datetime ) AS hit_date, 0, COUNT( DISTINCT hit_sess_ID )
				  FROM T_hitlog
				 WHERE hit_datetime < '.$DB->quote( $max_aggregate_date ).'
				   AND hit_type = "api"
				 GROUP BY hit_date
				ON DUPLICATE KEY UPDATE hags_count_api = VALUES( hags_count_api )',
				'Aggregate ALL sessions from hit log (hit_type = "api")' );
		}
	}


	/**
	 * Aggregate the goal hits
	 */
	static function aggregate_goal_hits()
	{
		global $DB;

		// NOTE: Do NOT aggregate current day because it is not ended yet
		$max_aggregate_date = date( 'Y-m-d H:i:s', mktime( 0, 0, 0 ) );

		$DB->query( 'REPLACE INTO T_track__goalhit_aggregate ( ghag_date, ghag_goal_ID, ghag_count )
			SELECT DATE( hit_datetime ) AS hit_date, ghit_goal_ID, COUNT( ghit_ID )
			  FROM T_track__goalhit
			 INNER JOIN T_hitlog ON hit_ID = ghit_hit_ID
			 WHERE hit_datetime < '.$DB->quote( $max_aggregate_date ).'
			 GROUP BY hit_date, ghit_goal_ID',
			'Aggregate goal hits log' );
	}
}

?>
