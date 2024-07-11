<?php
/**
 * This file implements the item_content Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
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
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _item_content.widget.php 10056 2015-10-16 12:47:15Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class item_content_Widget extends ComponentWidget
{
	var $icon = 'file-text';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'item_content' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'item-content-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Content');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( T_('Item Content') );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display item content.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		$r = array_merge( array(
				'title' => array(
					'label' => T_( 'Title' ),
					'size' => 40,
					'note' => T_( 'This is the title to display' ),
					'defaultvalue' => '',
				),
				'info' => array(
					'type' => 'info',
					'label' => T_('Info'),
					'info' => sprintf( T_('This widget will use the templates associated with the current <a %s>Item Type</a>.'), 'href="'.get_admin_url( 'ctrl=itemtypes&amp;blog='.$this->get_coll_ID() ).'"' ),
				),
			), parent::get_param_definitions( $params ) );

		if( isset( $r['allow_blockcache'] ) )
		{	// Disable "allow blockcache" because item content may includes other items by inline tags like [inline:item-slug]:
			$r['allow_blockcache']['defaultvalue'] = false;
			$r['allow_blockcache']['disabled'] = 'disabled';
			$r['allow_blockcache']['note'] = T_('This widget cannot be cached in the block cache.');
		}

		return $r;
	}


	/**
	 * Prepare display params
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function init_display( $params )
	{
		global $preview;

		parent::init_display( $params );

		if( $preview )
		{	// Disable block caching for this widget when item is previewed currently:
			$this->disp_params['allow_blockcache'] = 0;
		}
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Item;

		if( empty( $Item ) )
		{	// Don't display this widget when no Item object:
			$this->display_error_message( 'Widget "'.$this->get_name().'" is hidden because there is no Item.' );
			return false;
		}

		$this->init_display( $params );

		// Prepare params to be transmitted to template:
		$this->disp_params = array_merge( array(
				'widget_item_content_params' => array(),
			), $this->disp_params );
		$widget_item_content_params = $this->disp_params['widget_item_content_params'];

		// Now, for some skins (2015), merge in the OLD name:
		if( isset($this->disp_params['widget_coll_item_content_params']) )
		{	// The new correct stuff gets precedence over the old stuff:
			$widget_item_content_params = array_merge( $widget_item_content_params, $this->disp_params['widget_coll_item_content_params'] );
		}

		// Get content mode from current params or resolved auto content mode depending on current disp and collection settings:
		$content_mode = ( isset( $widget_item_content_params['content_mode'] ) ? $widget_item_content_params['content_mode'] : 'auto' );
		$content_mode = resolve_auto_content_mode( $content_mode );
		switch( $content_mode )
		{
			case 'excerpt':
				$template_code = $Item->get_type_setting( 'template_excerpt' );
				break;
			case 'normal':
				$template_code = $Item->get_type_setting( 'template_normal' );
				break;
			case 'full':
				$template_code = $Item->get_type_setting( 'template_full' );
				break;
			default:
				$template_code = NULL;
		}

		$TemplateCache = & get_TemplateCache();
		if( ! empty( $template_code ) &&
		    ! ( $content_Template = & $TemplateCache->get_by_code( $template_code, false, false ) ) )
		{	// Display error when no or wrong template for content display:
			$this->display_error_message( sprintf( 'Template is not found: %s for content display!', '<code>'.$template_code.'</code>' ) );
			return false;
		}

		echo $this->disp_params['block_start'];
		$this->disp_title();
		echo $this->disp_params['block_body_start'];

		if( ! empty( $content_Template ) )
		{	// Render Item content by Easy Template:
			echo render_template_code( $template_code, $widget_item_content_params );
		}
		else
		{	// Use PHP Template to display content:
			// ---------------------- POST CONTENT INCLUDED HERE ----------------------
			skin_include( '_item_content.inc.php', $widget_item_content_params );
			// Note: You can customize the default item content by copying the generic
			// /skins/_item_content.inc.php file into the current skin folder.
			// -------------------------- END OF POST CONTENT -------------------------
		}

		echo $this->disp_params['block_body_end'];
		echo $this->disp_params['block_end'];

		return true;
	}
}

?>