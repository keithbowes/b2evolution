<?php
/**
 * This file implements the UI view for Users > User settings > Invitations
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package admin
 *
 * @version $Id: _organization.view.php 7044 2014-07-02 08:55:10Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $blog, $admin_url, $UserSettings;

// Create result set:
$SQL = new SQL();
$SQL->SELECT( 'SQL_NO_CACHE org_ID, org_name, org_url' );
$SQL->FROM( 'T_users__organization' );

$count_SQL = new SQL();
$count_SQL->SELECT( 'SQL_NO_CACHE COUNT( org_ID )' );
$count_SQL->FROM( 'T_users__organization' );

$Results = new Results( $SQL->get(), 'org_', '-D', $UserSettings->get( 'results_per_page' ), $count_SQL->get() );

$Results->title = T_('Organizations').get_manual_link( 'organizations-tab' );

/*
 * Table icons:
 */
if( $current_User->check_perm( 'users', 'edit', false ) )
{ // create new group link
	$Results->global_icon( T_('Create a new organization...'), 'new', '?ctrl=organizations&amp;action=new', T_('Add organization').' &raquo;', 3, 4 );
}

$Results->cols[] = array(
		'th' => T_('ID'),
		'order' => 'org_ID',
		'th_class' => 'shrinkwrap',
		'td_class' => 'right',
		'td' => '$org_ID$',
	);

$Results->cols[] = array(
		'th' => T_('Name'),
		'order' => 'org_name',
		'td' => $current_User->check_perm( 'users', 'edit', false )
			? '<a href="'.$admin_url.'?ctrl=organization&amp;action=edit&amp;org_ID=$org_ID$"><b>$org_name$</b></a>'
			: '$org_name$',
	);

$Results->cols[] = array(
		'th' => T_('Url'),
		'order' => 'org_url',
		'td' => '~conditional( #org_url# == "", "&nbsp;", "<a href=\"#org_url#\">#org_url#</a>" )~',
	);

if( $current_User->check_perm( 'users', 'edit', false ) )
{
	function org_actions( & $row )
	{
		$r = action_icon( T_('Edit this organization...'), 'edit',
					regenerate_url( 'ctrl,action', 'ctrl=organizations&amp;org_ID='.$row->org_ID.'&amp;action=edit') )
				.action_icon( T_('Duplicate this organization...'), 'copy',
					regenerate_url( 'ctrl,action', 'ctrl=organizations&amp;org_ID='.$row->org_ID.'&amp;action=new') )
				.action_icon( T_('Delete this organization!'), 'delete',
					regenerate_url( 'ctrl,action', 'ctrl=organizations&amp;org_ID='.$row->org_ID.'&amp;action=delete&amp;'.url_crumb('organization') ) );

		return $r;
	}

	$Results->cols[] = array(
			'th' => T_('Actions'),
			'td_class' => 'shrinkwrap',
			'td' => '%org_actions( {row} )%',
		);
}

// Display results:
$Results->display();
?>