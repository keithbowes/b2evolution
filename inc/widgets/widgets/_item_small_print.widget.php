<?php
/**
 * This file implements the item_small_print Widget class.
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
 * @version $Id: _item_small_print.widget.php 10056 2015-10-16 12:47:15Z yura $
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
class item_small_print_Widget extends ComponentWidget
{
	var $icon = 'info-circle';

	/**
	 * Constructor
	 * @param object $db_row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'item_small_print' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'small-print-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Small Print');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( T_('Small Print') );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Print small information about item.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param array $params local params like 'for_editing' => true
	 * @return array
	 */
	function get_param_definitions( $params )
	{
		global $admin_url;

		// Get available templates:
		$context = 'item_details';
		$TemplateCache = & get_TemplateCache();
		$TemplateCache->load_by_context( $context );

		$r = array_merge( array(
				'title' => array(
					'label' => T_( 'Title' ),
					'size' => 40,
					'note' => T_( 'This is the title to display' ),
					'defaultvalue' => '',
				),
				'template' => array(
					'label' => T_('Template'),
					'type' => 'select',
					'options' => $TemplateCache->get_code_option_array(),
					'defaultvalue' => 'item_details_smallprint_standard',
					'input_suffix' => ( check_user_perm( 'options', 'edit' ) ? '&nbsp;'
							.action_icon( '', 'edit', $admin_url.'?ctrl=templates&amp;context='.$context, NULL, NULL, NULL,
							array( 'onclick' => 'return b2template_list_highlight( this )', 'target' => '_blank' ),
							array( 'title' => T_('Manage templates').'...' ) ) : '' ),
					'class' => 'evo_template_select',
				),
			), parent::get_param_definitions( $params ) );

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
	 * @param array $params MUST contain at least the basic display params
	 * @return bool
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

		$TemplateCache = & get_TemplateCache();
		if( ! $TemplateCache->get_by_code( $this->disp_params['template'], false, false ) )
		{
			$this->display_error_message( sprintf( 'Template not found: %s', '<code>'.$this->disp_params['template'].'</code>' ) );
			return false;
		}

		$template = $this->disp_params['template'];

		$template_params = array_merge( array(
				'author_avatar_class' => 'leftmargin',
			), $this->disp_params );
		
		$small_print = render_template_code( $template, $template_params );

		if( ! empty( $small_print ) )
		{
			echo add_tag_class( $this->disp_params['block_start'], 'clearfix' );
			
			$this->disp_title();
			
			echo $this->disp_params['block_body_start'];

			echo $small_print;

			echo $this->disp_params['block_body_end'];
			echo $this->disp_params['block_end'];

			return true;
		}

		$this->display_debug_message();
		return false;
	}


	/**
	 * Maybe be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @return array of keys this widget depends on
	 */
	function get_cache_keys()
	{
		global $Collection, $Blog, $current_User, $Item;

		return array(
				'wi_ID'        => $this->ID, // Have the widget settings changed ?
				'set_coll_ID'  => $Blog->ID, // Have the settings of the blog changed ? (ex: new skin)
				'user_ID'      => ( is_logged_in() ? $current_User->ID : 0 ), // Has the current User changed?
				'cont_coll_ID' => empty( $this->disp_params['blog_ID'] ) ? $Blog->ID : $this->disp_params['blog_ID'], // Has the content of the displayed blog changed ?
				'item_ID'      => ( empty( $Item->ID ) ? 0 : $Item->ID ), // Has the Item page changed?
				'item_user_flag_'.( empty( $Item->ID ) ? 0 : $Item->ID ) => ( is_logged_in() ? $current_User->ID : 0 ), // Has the Item data per current User changed?
				'template_code'=> $this->get_param( 'template' ), // Has the Template changed?
			);
	}


	/**
	 * Display debug message e-g on designer mode when we need to show widget when nothing to display currently
	 *
	 * @param string Message
	 */
	function display_debug_message( $message = NULL )
	{
		if( $this->mode == 'designer' )
		{	// Display message on designer mode:
			echo $this->disp_params['block_start'];
			$this->disp_title();
			echo $this->disp_params['block_body_start'];
			echo $this->disp_params['widget_item_small_print_before'];
			echo $message;
			echo $this->disp_params['widget_item_small_print_after'];
			echo $this->disp_params['block_body_end'];
			echo $this->disp_params['block_end'];
		}
	}
}

?>
