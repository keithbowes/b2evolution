<?php
/**
 * Controller mappings.
 *
 * For each controller name, we associate a controller file to be found in /inc/ .
 * The advantage of this indirection is that it is easy to reorganize the controllers into
 * subdirectories by modules. It is also easy to deactivate some controllers if you don't
 * want to provide this functionality on a given installation.
 *
 * Note: while the controller mappings might more or less follow the menu structure, we do not merge
 * the two tables since we could, at any time, decide to make a skin with a different menu structure.
 * The controllers however would most likely remain the same.
 *
 * @global array
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


/**
 * Minimum PHP version required for maintenance module to function properly
 */
$required_php_version[ 'maintenance' ] = '5.6';

/**
 * Minimum MYSQL version required for maintenance module to function properly
 */
$required_mysql_version[ 'maintenance' ] = '5.1';

$ctrl_mappings['backup'] = 'maintenance/backup.ctrl.php';
$ctrl_mappings['upgrade'] = 'maintenance/upgrade.ctrl.php';


/**
 * maintenance_Module definition
 */
class maintenance_Module extends Module
{
	/**
	 * Do the initializations. Called from in _main.inc.php.
	 * This is typically where classes matching DB tables for this module are registered/loaded.
	 *
	 * Note: this should only load/register things that are going to be needed application wide,
	 * for example: for constructing menus.
	 * Anything that is needed only in a specific controller should be loaded only there.
	 * Anything that is needed only in a specific view should be loaded only there.
	 */
	function init()
	{
		$this->check_required_php_version( 'maintenance' );

		load_funcs( 'maintenance/model/_maintenance.funcs.php' );
	}


	/**
	 * Get default module permissions
	 *
	 * @param integer Group ID
	 * @return array
	 */
	function get_default_group_permissions( $grp_ID )
	{
		switch( $grp_ID )
		{
			case 1: // Administrators group ID equals 1
				$perm_maintenance = 'upgrade'; // Maintenance permissions like backup or upgrade
				break;
			default: // Other groups
				$perm_maintenance = 'none';
				break;
		}

		// We can return as many default permissions as we want:
		// e.g. array ( permission_name => permission_value, ... , ... )
		return $permissions = array( 'perm_maintenance' => $perm_maintenance );
	}


	/**
	 * Get available group permissions
	 *
	 * @return array (may contain several permission blocks)
	 */
	function get_available_group_permissions()
	{
		// 'label' is used in the group form as label for radio buttons group
		// 'user_func' function used to check user permission. This function should be defined in Module.
		// 'group_func' function used to check group permission. This function should be defined in Module.
		// 'perm_block' group form block where this permissions will be displayed. Now available, the following blocks: additional, system
		// 'options' is permission options
		$permissions = array(
			'perm_maintenance' => array(
				'label' => TB_('Maintenance'),
				'user_func'  => 'check_maintenance_user_perm',
				'group_func' => 'check_maintenance_group_perm',
				'perm_block' => 'system',
				'options'  => array(
						// format: array( radio_button_value, radio_button_label, radio_button_note )
						array( 'none', TB_('No Access'), '' ),
						array( 'backup', TB_('Create backups'), '' ),
						array( 'upgrade', TB_('Create backups & upgrade b2evolution'), '' ),
					),
				),
			);
		return $permissions;
	}


	/**
	 * Check a permission for the user. ( see 'user_func' in get_available_group_permissions() function  )
	 *
	 * @param string Requested permission level
	 * @param string Permission value
	 * @param mixed Permission target (blog ID, array of cat IDs...)
	 * @return boolean True on success (permission is granted), false if permission is not granted
	 */
	function check_maintenance_user_perm( $permlevel, $permvalue, $permtarget )
	{
		return true;
	}


	/**
	 * Check a permission for the group. ( see 'group_func' in get_available_group_permissions() function )
	 *
	 * @param string Requested permission level
	 * @param string Permission value
	 * @param mixed Permission target (blog ID, array of cat IDs...)
	 * @return boolean True on success (permission is granted), false if permission is not granted
	 */
	function check_maintenance_group_perm( $permlevel, $permvalue, $permtarget )
	{
		$perm = false;
		switch ( $permvalue )
		{
			case 'upgrade':
				// Users can create backups & upgrade the app.
				if( $permlevel == 'upgrade' )
				{ // User can ask for delete perm...
					$perm = true;
					break;
				}

			case 'backup':
				//  Users can create backups
				if( $permlevel == 'backup' )
				{
					$perm = true;
					break;
				}
		}

		return $perm;
	}


	/**
	 * Builds the 3rd half of the menu. This is the one with the configuration features
	 *
	 * At some point this might be displayed differently than the 1st half.
	 */
	function build_menu_3()
	{
		global $AdminUI, $auto_upgrade_from_any_url;

		if( ! check_user_perm( 'admin', 'normal' ) )
		{
			return;
		}

		if( check_user_perm( 'maintenance', 'backup' ) )
		{
			// Display Backup tab in System -> Maintenance menu
			$AdminUI->add_menu_entries( array( 'options', 'misc' ), array(
									'backup' => array(
									'text' => TB_('Backup'),
									'href' => '?ctrl=backup'	),
							) );
		}

		if( check_user_perm( 'maintenance', 'upgrade' ) )
		{
			// Display Updates tab in System -> Maintenance menu
			$AdminUI->add_menu_entries( array( 'options', 'misc' ), array(
									'upgrade' => array(
									'text' => TB_('Auto Upgrade'),
									'href' => '?ctrl=upgrade' ),
							) );
			if( $auto_upgrade_from_any_url )
			{	// Deny upgrade from Git because upgrade from any URL is denied by config:
				$AdminUI->add_menu_entries( array( 'options', 'misc' ), array(
									'upgradegit' => array(
									'text' => TB_('Upgrade from Git'),
									'href' => '?ctrl=upgrade&amp;tab=git' ),
							) );
			}
		}
	}


	/**
	 * Get the maintenance module cron jobs
	 *
	 * @see Module::get_cron_jobs()
	 */
	function get_cron_jobs()
	{
		return array(
			'test' => array(
				'name'   => TB_('Basic test job'),
				'help'   => '#',
				'ctrl'   => 'cron/jobs/_test.job.php',
				'params' => NULL,
			),
			'error-test' => array(
				'name'   => TB_('Error test job'),
				'help'   => '#',
				'ctrl'   => 'cron/jobs/_error_test.job.php',
				'params' => NULL,
			),
			'cleanup-scheduled-jobs' => array(
				'name'   => TB_('Clean up scheduled jobs older than a threshold'),
				'help'   => '#',
				'ctrl'   => 'cron/jobs/_cleanup_jobs.job.php',
				'params' => NULL,
			),
			'cleanup-email-logs' => array(
				'name'   => TB_('Clean up email logs older than a threshold'),
				'help'   => '#',
				'ctrl'   => 'cron/jobs/_cleanup_email_logs.job.php',
				'params' => NULL,
			),
			'heavy-db-maintenance' => array(
				'name'   => TB_('Heavy DB maintenance (CHECK & OPTIMIZE)'),
				'help'   => '#',
				'ctrl'   => 'cron/jobs/_heavy_db_maintenance.job.php',
				'params' => NULL,
			),
			'light-db-maintenance' => array(
				'name'   => TB_('Light DB maintenance (ANALYZE)'),
				'help'   => '#',
				'ctrl'   => 'cron/jobs/_light_db_maintenance.job.php',
				'params' => NULL,
			),
			'prune-old-files-from-page-cache' => array(
				'name'   => TB_('Prune old files from page cache'),
				'help'   => '#',
				'ctrl'   => 'cron/jobs/_prune_page_cache.job.php',
				'params' => NULL,
			),
			'prune-recycled-comments' => array(
				'name'   => TB_('Prune recycled comments'),
				'help'   => '#',
				'ctrl'   => 'cron/jobs/_prune_recycled_comments.job.php',
				'params' => NULL,
			),
		);
	}
}

$maintenance_Module = new maintenance_Module();

?>
