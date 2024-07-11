<?php
/**
 * This file implements the UI view for the top IPs.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * View funcs
 */
require_once dirname(__FILE__).'/_stats_view.funcs.php';

global $UserSettings, $Plugins, $blog, $sec_ID;

$SQL = new SQL();
$SQL->SELECT( 'SQL_NO_CACHE hit_remote_addr, COUNT( hit_ID ) AS hit_count_by_IP' );
$SQL->FROM( 'T_hitlog' );
$SQL->GROUP_BY( 'hit_remote_addr' );
$SQL->ORDER_BY( 'hit_count_by_IP DESC' );

$count_SQL = new SQL();
$count_SQL->SELECT( 'SQL_NO_CACHE hit_ID' );
$count_SQL->FROM( 'T_hitlog' );
$count_SQL->GROUP_BY( 'hit_remote_addr' );

if( ! empty( $sec_ID ) )
{	// Filter by section:
	$SQL->FROM_add( 'LEFT JOIN T_blogs ON hit_coll_ID = blog_ID' );
	$SQL->WHERE_and( 'blog_sec_ID = '.$sec_ID );
	$count_SQL->FROM_add( 'LEFT JOIN T_blogs ON hit_coll_ID = blog_ID' );
	$count_SQL->WHERE_and( 'blog_sec_ID = '.$sec_ID );
}
if( $blog > 0 )
{	// Filter by collection:
	$SQL->WHERE_and( 'hit_coll_ID = '.$blog );
	$count_SQL->WHERE_and( 'hit_coll_ID = '.$blog );
}

$count_top_IPs = count( $DB->get_col( $count_SQL->get() ) );

$Results = new Results( $SQL->get(), 'topips_', ''/*an order is static in SQL query*/, $UserSettings->get( 'results_per_page' ), $count_top_IPs );

$Results->title = T_('Top IPs').get_manual_link( 'top-ips' );

// IP address
$Results->cols[] = array(
		'th' => T_('IP'),
		'td' => '%hit_ip_address_lookup( #hit_remote_addr# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'nowrap'
	);

// A count of the hits
$Results->cols[] = array(
		'th' => T_('Hits'),
		'td' => '$hit_count_by_IP$',
		'td_class' => 'shrinkwrap'
	);

// Reverse DNS
$Results->cols[] = array(
		'th' => T_('Reverse DNS'),
		'td_class' => 'nowrap',
		'td' => '%gethostbyaddr( #hit_remote_addr# )%%evo_flush()%'
	);

if( ( $Plugin = & $Plugins->get_by_code( 'evo_GeoIP' ) ) !== false )
{ // Country by GeoIP plugin
	$Plugins->trigger_event( 'GetAdditionalColumnsTable', array(
			'table'   => 'top_ips',
			'column'  => 'hit_remote_addr',
			'Results' => $Results,
			'order'   => false
		) );
}
else
{ // No country, Display help icon
	$Results->cols[] = array(
			'th' => T_('Country'),
			'td' => '%get_manual_link( "geoip-plugin" )%',
			'td_class' => 'shrinkwrap',
		);
}

// Status
$Results->cols[] = array(
		'th' => T_('Status'),
		'td' => '%hit_iprange_status_title( #hit_remote_addr# )%',
		'th_class' => 'shrinkwrap',
		'extra' => array ( 'style' => 'background-color: %hit_iprange_status_color( "#hit_remote_addr#" )%;', 'format_to_output' => false )
	);

// Display results:
$Results->display();
?>
