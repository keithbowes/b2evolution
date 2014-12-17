<?php
/**
 * This file implements the Admin UI class for the evo skin.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin-skin
 * @subpackage evo
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id: _adminUI.class.php 6134 2014-03-08 07:48:07Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes
 */
require_once dirname(__FILE__).'/../_adminUI_general.class.php';


/**
 * We'll use the default AdminUI templates etc.
 *
 * @package admin-skin
 * @subpackage evo
 */
class AdminUI extends AdminUI_general
{

	/**
	 * This function should init the templates - like adding Javascript through the {@link add_headline()} method.
	 */
	function init_templates()
	{
		global $Hit;
		// This is included before controller specifc require_css() calls:
		require_css( 'basic_styles.css', 'rsc_url' ); // the REAL basic styles
		require_css( 'basic.css', 'rsc_url' ); // Basic styles
		require_css( 'results.css', 'rsc_url' ); // Results/tables styles
		require_css( 'item_base.css', 'rsc_url' ); // Default styles for the post CONTENT
		require_css( 'fileman.css', 'rsc_url' ); // Filemanager styles
		require_css( 'admin.global.css', 'rsc_url' ); // Basic admin styles
		require_css( 'skins_adm/evo/rsc/css/style.css', true );

		if ( $Hit->is_IE() )
		{ // CSS for IE
			require_css( 'admin_global_ie.css', 'rsc_url' );
		}
		if( $Hit->is_IE( 9 ) )
		{ // CSS for IE9
			require_css( 'ie9.css', 'rsc_url' );
		}

		require_js( '#jquery#', 'rsc_url' );
		require_js( 'jquery/jquery.raty.min.js', 'rsc_url' );
	}


	/**
	 * Get the top of the HTML <body>.
	 *
	 * @uses get_page_head()
	 * @return string
	 */
	function get_body_top()
	{
		global $Messages, $app_shortname, $app_version;

		$r = '';

		$r .= $this->get_page_head();

		// Display MAIN menu:
		$r .= '<div id="mainmenu">'."\n".
				$this->get_html_menu()."\n".
				'<p class="center">'.$app_shortname.' v <strong>'.$app_version.'</strong></p>'."\n".
			'</div>'."\n".
			'<div id="panelbody" class="panelbody">'."\n";

		$r .= '

		<div id="payload">
		';

		$r .= $this->get_bloglist_buttons();

		// Display info & error messages
		$r .= $Messages->display( NULL, NULL, false, 'action_messages' );

		return $r;
	}


	/**
	 * Close open div(s).
	 *
	 * @return string
	 */
	function get_body_bottom()
	{
		global $rsc_url;

		$r = '';

		$r .= "\n\t</div>";

		$r .= "</div>\n";	// Close right col.

		$r .= get_icon( 'pixel' );

		return $r;
	}


	/**
	 * GLOBAL HEADER - APP TITLE, LOGOUT, ETC.
	 *
	 * @return string
	 */
	function get_page_head()
	{
		global $UserSettings, $current_User;

		$r = '
		<div id="header">
			<h1>';
		if( $UserSettings->get( 'show_breadcrumbs', $current_User->ID ) )
		{
			$r .= $this->breadcrumbpath_get_html( array( 'beforeText' => '' ) );
		}
		else
		{
			$r .= $this->get_title_for_titlearea();
		}
		$r .= '</h1>
		</div>
		';

		return $r;
	}

	/**
	 * Get a template by name and depth.
	 *
	 * @param string The template name ('main', 'sub').
	 * @return array
	 */
	function get_template( $name, $depth = 0 )
	{
		switch( $name )
		{
			case 'main':
				// main level
				$r = parent::get_template( $name, $depth );
				$r['before'] = '<ul>';
				$r['after'] = '</ul>';
				$r['_props'] = array(
						'recurse'       => 'yes',
						'recurse_level' => 2,
					);
				return $r;
				break;


			case 'CollectionList':
				// Template for a list of Collections (Blogs)
				return array(
						'before' => '<div id="TitleArea">',
						'after' => '</div>',
						'select_start' => '<div class="collection_select">',
						'select_end' => '</div>',
						'buttons_start' => '',
						'buttons_end' => '',
						'beforeEach' => '',
						'afterEach' => '',
						'beforeEachSel' => '',
						'afterEachSel' => '',
					);


			default:
				// Delegate to parent class:
				return parent::get_template( $name, $depth );
		}
	}

	/**
	 * Get colors for page elements that can't be controlled by CSS (charts)
	 */
	function get_color( $what )
	{
		switch( $what )
		{
			case 'payload_background':
				return 'fbfbfb';
				break;
		}
		debug_die( 'unknown color' );
	}


	/**
	 * Display the start of a payload block
	 *
	 * Note: it is possible to display several payload blocks on a single page.
	 *       The first block uses the "sub" template, the others "block".
	 *
	 * @see disp_payload_end()
	 */
	function disp_payload_begin( $params = array() )
	{
		parent::disp_payload_begin( array(
				'display_menu2' => false,
			) );
	}


}

?>
