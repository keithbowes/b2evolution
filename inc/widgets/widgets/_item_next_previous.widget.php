<?php
/**
 * This file implements the item_next_previous Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
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
 * @author erhsatingin: Erwin Rommel Satingin.
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
class item_next_previous_Widget extends ComponentWidget
{
	var $icon = 'angle-left';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'item_next_previous' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'item-next-previous-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Next/Previous Item');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( T_('Item Next/Previous') );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display controls to navigate to the next/previous items.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		global $Blog;

		$r = array_merge( array(
				'title' => array(
					'label' => T_( 'Title' ),
					'size' => 40,
					'note' => T_( 'This is the title to display' ),
					'defaultvalue' => '',
				),
			), parent::get_param_definitions( $params ) );

		if( isset( $r['allow_blockcache'] ) )
		{	// Disable "allow blockcache" because this widget displays dynamic data:
			$r['allow_blockcache']['defaultvalue'] = false;
			$r['allow_blockcache']['disabled'] = 'disabled';
			$r['allow_blockcache']['note'] = T_('This widget cannot be cached in the block cache.');
		}

		return $r;
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $disp;

		$this->init_display( $params );

		// Make sure the param  widget_item_next_previous_params' exists:
		$params = array_merge( array(
				'widget_item_next_previous_params' => array(),
			), $params );

		// Add defaults:
		$widget_params = array_merge( array(
				'block_start' => '',
				'block_end' => '',
				// We use defaults designed for Bootstrap because this widget was not used before v6 skins:
				'block_start' => '<nav><ul class="pager">',
				'block_end' => '</ul></nav>',
				'prev_start' => '<li class="previous">',
				'prev_end' => '</li>',
				'next_start' => '<li class="next">',
				'next_end' => '</li>',
			), $params['widget_item_next_previous_params'] );

		ob_start();
		item_prevnext_links( $widget_params );
		$item_prevnext_links = ob_get_clean();

		if( $disp == 'single' && ! empty( $item_prevnext_links ) )
		{
			echo $this->disp_params['block_start'];
			$this->disp_title();
			echo $this->disp_params['block_body_start'];

			echo $item_prevnext_links;

			echo $this->disp_params['block_body_end'];
			echo $this->disp_params['block_end'];

			return true;
		}
		else
		{
			$this->display_debug_message();
			return false;
		}
	}
}

?>