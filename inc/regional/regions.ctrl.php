<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Load Region class (PHP4):
load_class( 'regional/model/_region.class.php', 'Region' );
load_funcs( 'regional/model/_regional.funcs.php' );

// Check minimum permission:
check_user_perm( 'admin', 'normal', true );
check_user_perm( 'options', 'view', true );

// Memorize this as the last "tab" used in the Global Settings:
$UserSettings->set( 'pref_glob_settings_tab', $ctrl );
$UserSettings->set( 'pref_glob_regional_tab', $ctrl );
$UserSettings->dbupdate();

// Set options path:
$AdminUI->set_path( 'options', 'regional', 'regions' );

// Get action parameter from request:
param_action();

if( param( 'rgn_ID', 'integer', '', true) )
{// Load region from cache:
	$RegionCache = & get_RegionCache();
	if( ($edited_Region = & $RegionCache->get_by_ID( $rgn_ID, false )) === false )
	{	unset( $edited_Region );
		forget_param( 'rgn_ID' );
		$Messages->add( sprintf( TB_('Requested &laquo;%s&raquo; object does not exist any longer.'), TB_('Region') ), 'error' );
		$action = 'nil';
	}
}

switch( $action )
{
	case 'disable_region':
	case 'enable_region':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'region' );

		// Disable a region only if it is enabled, and user has edit access.
		check_user_perm( 'options', 'edit', true );

		// Make sure the region information was loaded. If not, just exit with error.
		if( empty($edited_Region) )
		{
			$Messages->add( sprintf( 'The region with ID %d could not be instantiated.', $rgn_ID ), 'error' );
			break;
		}

		if ( $action == 'disable_region' )
		{	// Disable this region by setting flag to false.
			$edited_Region->set( 'enabled', 0 );
			$Messages->add( sprintf( TB_('Disabled region (%s, #%d).'), $edited_Region->name, $edited_Region->ID ), 'success' );
		}
		elseif ( $action == 'enable_region' )
		{	// Enable region by setting flag to true.
			$edited_Region->set( 'enabled', 1 );
			$Messages->add( sprintf( TB_('Enabled region (%s, #%d).'), $edited_Region->name, $edited_Region->ID ), 'success' );
		}

		// Update db with new flag value.
		$edited_Region->dbupdate();

		param( 'results_rgn_page', 'integer', '', true );
		param( 'results_rgn_order', 'string', '', true );

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( regenerate_url( '', '', '', '&' ), 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	case 'enable_region_pref':
	case 'disable_region_pref':

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'region' );

		// Disable a region only if it is enabled, and user has edit access.
		check_user_perm( 'options', 'edit', true );

		// Make sure the region information was loaded. If not, just exit with error.
		if( empty($edited_Region) )
		{
			$Messages->add( sprintf( 'The region with ID %d could not be instantiated.', $rgn_ID ), 'error' );
			break;
		}

		if ( $action == 'disable_region_pref' )
		{	// Disable this region by setting flag to false.
			$edited_Region->set( 'preferred', 0 );
			$Messages->add( sprintf( TB_('Removed from preferred regions (%s, #%d).'), $edited_Region->name, $edited_Region->ID ), 'success' );
		}
		elseif ( $action == 'enable_region_pref' )
		{	// Enable region by setting flag to true.
			$edited_Region->set( 'preferred', 1 );
			$Messages->add( sprintf( TB_('Added to preferred regions (%s, #%d).'), $edited_Region->name, $edited_Region->ID ), 'success' );
		}

		// Update db with new flag value.
		$edited_Region->dbupdate();

		param( 'results_rgn_page', 'integer', '', true );
		param( 'results_rgn_order', 'string', '', true );

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( regenerate_url( '', '', '', '&' ), 303 ); // Will EXIT
		// We have EXITed already at this point!!
		break;

	case 'new':
		// Check permission:
		check_user_perm( 'options', 'edit', true );

		if( ! isset($edited_Region) )
		{	// We don't have a model to use, start with blank object:
			$edited_Region = new Region();
		}
		else
		{	// Duplicate object in order no to mess with the cache:
			$edited_Region = clone $edited_Region;
			$edited_Region->ID = 0;
		}
		break;

	case 'csv':
		// Check permission:
		check_user_perm( 'options', 'edit', true );
		break;

	case 'edit':
		// Check permission:
		check_user_perm( 'options', 'edit', true );

		// Make sure we got an rgn_ID:
		param( 'rgn_ID', 'integer', true );
 		break;

	case 'create': // Record new region
	case 'create_new': // Record region and create new
	case 'create_copy': // Record region and create similar
		// Insert new region:
		$edited_Region = new Region();

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'region' );

		// Check permission:
		check_user_perm( 'options', 'edit', true );

		// Load data from request
		if( $edited_Region->load_from_Request() )
		{	// We could load data from form without errors:

			// Insert in DB:
			$edited_Region->dbinsert();
			$Messages->add( TB_('New region created.'), 'success' );

			// What next?
			switch( $action )
			{
				case 'create_copy':
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( '?ctrl=regions&action=new&rgn_ID='.$edited_Region->ID, 303 ); // Will EXIT
					// We have EXITed already at this point!!
					break;
				case 'create_new':
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( '?ctrl=regions&action=new', 303 ); // Will EXIT
					// We have EXITed already at this point!!
					break;
				case 'create':
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( '?ctrl=regions', 303 ); // Will EXIT
					// We have EXITed already at this point!!
					break;
			}
		}
		break;

	case 'update':
		// Edit region form:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'region' );

		// Check permission:
		check_user_perm( 'options', 'edit', true );

		// Make sure we got an rgn_ID:
		param( 'rgn_ID', 'integer', true );

		// load data from request
		if( $edited_Region->load_from_Request() )
		{	// We could load data from form without errors:

			// Update in DB:
			$edited_Region->dbupdate();
			$Messages->add( TB_('Region updated.'), 'success' );

			// If no error, Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=regions', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;

	case 'delete':
		// Delete region:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'region' );

		// Check permission:
		check_user_perm( 'options', 'edit', true );

		// Make sure we got an rgn_ID:
		param( 'rgn_ID', 'integer', true );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed, Delete from DB:
			$msg = sprintf( TB_('Region &laquo;%s&raquo; deleted.'), $edited_Region->dget('name') );
			$edited_Region->dbdelete();
			unset( $edited_Region );
			forget_param( 'rgn_ID' );
			$Messages->add( $msg, 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=regions', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		else
		{	// not confirmed, Check for restrictions:
			if( ! $edited_Region->check_delete( sprintf( TB_('Cannot delete region &laquo;%s&raquo;'), $edited_Region->dget('name') ) ) )
			{	// There are restrictions:
				$action = 'view';
			}
		}
		break;

	case 'import':
		// Import new regions:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'region' );

		// Check permission:
		check_user_perm( 'options', 'edit', true );

		set_max_execution_time( 0 );

		// Country Id
		param( 'ctry_ID', 'integer', true );
		param_check_number( 'ctry_ID', TB_('Please select a country'), true );

		// CSV File
		$import_file = param( 'import_file', 'string', '' );
		if( empty( $import_file ) )
		{	// File is not selected:
			$Messages->add( TB_('Please select a CSV file to import.'), 'error' );
		}
		else if( ! preg_match( '/\.csv$/i', $import_file ) )
		{	// Extension is incorrect
			$Messages->add( sprintf( TB_('&laquo;%s&raquo; has an unrecognized extension.'), basename( $import_file ) ), 'error' );
		}

		if( param_errors_detected() )
		{	// Some errors are exist, Stop the importing:
			$action = 'csv';
			break;
		}

		// Import a new regions from CSV file:
		$count_regions = import_regions( $ctry_ID, $import_file );

		load_class( 'regional/model/_country.class.php', 'Country' );
		$CountryCache = & get_CountryCache();
		$Country = $CountryCache->get_by_ID( $ctry_ID );

		$Messages->add( sprintf( TB_('%s regions have been added and %s regions have been updated for country %s.'),
			$count_regions['inserted'], $count_regions['updated'], $Country->get_name() ), 'success' );
		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( $admin_url.'?ctrl=regions&c='.$ctry_ID, 303 ); // Will EXIT
		break;

}


$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( TB_('System'), $admin_url.'?ctrl=system',
		TB_('Global settings are shared between all blogs; see Blog settings for more granular settings.') );
$AdminUI->breadcrumbpath_add( TB_('Regional'), $admin_url.'?ctrl=locales' );
$AdminUI->breadcrumbpath_add( TB_('Regions'), $admin_url.'?ctrl=regions' );

// Set an url for manual page:
switch( $action )
{
	case 'delete':
	case 'new':
	case 'create':
	case 'create_new':
	case 'create_copy':
	case 'edit':
	case 'update':
		$AdminUI->set_page_manual_link( 'regions-editing' );
		break;
	case 'csv':
		$AdminUI->set_page_manual_link( 'regions-import' );
		break;
	default:
		$AdminUI->set_page_manual_link( 'regions-list' );
		break;
}

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

$AdminUI->disp_payload_begin();

/**
 * Display payload:
 */
switch( $action )
{
	case 'nil':
		// Do nothing
		break;

	case 'delete':
		// We need to ask for confirmation:
		$edited_Region->confirm_delete(
				sprintf( TB_('Delete region &laquo;%s&raquo;?'), $edited_Region->dget('name') ),
				'region', $action, get_memorized( 'action' ) );
	case 'new':
	case 'create':
	case 'create_new':
	case 'create_copy':
	case 'edit':
	case 'update':
		$AdminUI->disp_view( 'regional/views/_region.form.php' );
		break;

	case 'csv':
		$AdminUI->disp_view( 'regional/views/_region_import.form.php' );
		break;

	default:
		// No specific request, list all regions:
		// Cleanup context:
		forget_param( 'rgn_ID' );
		// Display regions list:
		$AdminUI->disp_view( 'regional/views/_region_list.view.php' );
		break;
}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>
