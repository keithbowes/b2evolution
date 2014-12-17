<?php
/**
 * This file implements the file moderation.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: file_moderation.ctrl.php 7808 2014-12-12 11:45:53Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Check permission:
$current_User->check_perm( 'files', 'view', true );

// Check permission:
$current_User->check_perm( 'options', 'edit', true );


//param( 'action', 'string' );
param( 'tab', 'string', 'likes', true );


/**
 * We need make this call to build menu for all modules
 */
$AdminUI->set_path( 'files' );

file_controller_build_tabs();

$AdminUI->set_path( 'files', 'moderation', $tab );

// fp> TODO: this here is a bit sketchy since we have Blog & fileroot not necessarilly in sync. Needs investigation / propositions.
// Note: having both allows to post from any media dir into any blog.
$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('Files'), '?ctrl=files&amp;blog=$blog$' );
$AdminUI->breadcrumbpath_add( T_('Moderation'), '?ctrl=filemod' );
switch( $tab )
{
	case 'likes':
		$AdminUI->breadcrumbpath_add( T_('Likes'), '?ctrl=filemod&amp;tab='.$tab );
		break;

	case 'suspicious':
		$AdminUI->breadcrumbpath_add( T_('Suspicious'), '?ctrl=filemod&amp;tab='.$tab );
		break;

	case 'duplicates':
		$AdminUI->breadcrumbpath_add( T_('Duplicates'), '?ctrl=filemod&amp;tab='.$tab );
		break;
}

// require colorbox js
require_js_helper( 'colorbox' );

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();


/**
 * Display payload:
 */
$AdminUI->disp_payload_begin();
switch( $tab )
{
	case 'duplicates':
		$AdminUI->disp_view( 'files/views/_file_duplicates.view.php' );
		break;

	case 'suspicious':
		$AdminUI->disp_view( 'files/views/_file_suspicious.view.php' );
		break;

	case 'likes':
	default:
		$AdminUI->disp_view( 'files/views/_file_likes.view.php' );
		break;
}
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>
