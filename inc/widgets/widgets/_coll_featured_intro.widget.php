<?php
/**
 * This file implements the Featured/Intro Post Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
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
class coll_featured_intro_Widget extends ComponentWidget
{
	var $icon = 'asterisk';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'coll_featured_intro' );
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		load_funcs( 'files/model/_image.funcs.php' );

		$r = array_merge( array(
				'skin_template' => array(
					'label' => T_('Template'),
					'note' => '.inc.php',
					'defaultvalue' => '_item_block',
				),
				'featured_class' => array(
					'label' => T_('Featured Item class'),
					'note' => T_('Leave empty for default'),
					'defaultvalue' => '',
				),
				'intro_class' => array(
					'label' => T_('Intro Item class'),
					'note' => T_('Leave empty for default'),
					'defaultvalue' => '',
				),
				'disp_title' => array(
					'label' => T_( 'Title' ),
					'note' => T_( 'Display title.' ),
					'type' => 'checkbox',
					'defaultvalue' => true,
				),
				'item_title_link_type' => array(
					'label' => T_('Link title'),
					'note' => T_('Intro posts are never linked to their permalink URL'),
					'type' => 'select',
					'options' => array(
							'auto'        => T_('Automatic'),
							'permalink'   => T_('Item permalink'),
							'linkto_url'  => T_('Item URL'),
							'none'        => T_('Nowhere'),
						),
					'defaultvalue' => 'auto',
				),
				'image_size' => array(
					'label' => T_('Image Size'),
					'note' => T_('Cropping and sizing of thumbnails'),
					'type' => 'select',
					'options' => get_available_thumb_sizes(),
					'defaultvalue' => 'fit-400x320',
				),
				'attached_pics' => array(
					'label' => T_('Attached pictures'),
					'note' => '',
					'type' => 'radio',
					'options' => array(
							array( 'none', T_('None') ),
							array( 'first', T_('Display first') ),
							array( 'all', T_('Display all') ) ),
					'defaultvalue' => 'none',
				),
				'item_pic_link_type' => array(
					'label' => T_('Link pictures'),
					'note' => T_('Where should pictures be linked to?'),
					'type' => 'select',
					'options' => array(
							'original' => T_('Image URL'),
							'single'   => T_('Item permalink'),
						),
					'defaultvalue' => 'original',
				),
				'blog_ID' => array(
					'label' => T_('Collections'),
					'note' => T_('List collection IDs separated by \',\', \'*\' for all collections, \'-\' for current collection without aggregation or leave empty for current collection including aggregation.'),
					'size' => 4,
					'type' => 'text',
					'valid_pattern' => array( 'pattern' => '/^(\d+(,\d+)*|-|\*)?$/',
																		'error'   => T_('Invalid list of Collection IDs.') ),
					'defaultvalue' => '-',
				),
			), parent::get_param_definitions( $params ) );

		return $r;
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'featured-intro-post-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Featured/Intro Post');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return $this->get_name();
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display an Item if an Intro or a Featured item is available for display.');
	}


	/**
	 * Prepare display params
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function init_display( $params )
	{
		$params = array_merge( array(
				'featured_intro_before' => '',
				'featured_intro_after'  => '',
			), $params );

		parent::init_display( $params );

		// Use container params if DB params are empty:
		if( empty( $this->disp_params['intro_class'] ) && ! empty( $params['intro_class'] ) )
		{
			$this->disp_params['intro_class'] = $params['intro_class'];
		}

		if( empty( $this->disp_params['featured_class'] ) && ! empty( $params['featured_class'] ) )
		{
			$this->disp_params['featured_class'] = $params['featured_class'];
		}
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

		// Go Grab the featured post:
		if( $Item = & get_featured_Item( $disp, $this->disp_params['blog_ID'], true ) )
		{	// We have a featured/intro post to display:
			$item_style = '';
			$LinkOwner = new LinkItem( $Item );
			$LinkList = $LinkOwner->get_attachment_LinkList( 1, 'background' );
			if( ! empty( $LinkList ) &&
					$Link = & $LinkList->get_next() &&
					$File = & $Link->get_File() &&
					$File->exists() &&
					$File->is_image() )
			{	// Use cover image of intro-post as background:
				$item_style = 'background-image: url("'.$File->get_url().'")';
			}
			// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
			echo $this->disp_params['block_start'];
			echo $this->disp_params['block_body_start'];
			if( empty( $item_style ) )
			{	// No item style:
				echo $this->disp_params['featured_intro_before'];
			}
			else
			{	// Append item style to use cover as background:
				echo update_html_tag_attribs( $this->disp_params['featured_intro_before'], array( 'style' => $item_style, 'class' => 'evo_hasbgimg' ) );
			}

			$template_params = array(
					'feature_block'        => true,
					'content_mode'         => 'auto',   // 'auto' will auto select depending on $disp-detail
					'intro_mode'           => 'normal', // Intro posts will be displayed in normal mode
					'image_size'           => $this->disp_params['image_size'],
					'disp_title'           => $this->disp_params['disp_title'],
					'item_title_link_type' => $this->disp_params['item_title_link_type'],
					'attached_pics'        => $this->disp_params['attached_pics'],
					'item_pic_link_type'   => $this->disp_params['item_pic_link_type'],
					'Item'                 => $Item,
			);

			// Add item_class:
			$item_class = array();
			if( $Item->is_intro() )
			{
				$item_class = preg_split( '/[\s,]+/', $this->disp_params['intro_class'] );
			}
			elseif( $Item->is_featured() )
			{
				$item_class = preg_split( '/[\s,]+/', $this->disp_params['featured_class'] );
			}

			if( !empty( $item_class ) )
			{
				$template_params['item_class'] = implode( ' ', $item_class );
			}

			skin_include( $this->disp_params['skin_template'].'.inc.php', $template_params );
			echo $this->disp_params['featured_intro_after'];
			echo $this->disp_params['block_body_end'];
			echo $this->disp_params['block_end'];
			// ----------------------------END ITEM BLOCK  ----------------------------
			return true;
		}
		else
		{	// No featured Item:
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because there is no featured/intro post to display' );
			return false;
		}
	}


	/**
	 * Maybe be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @return array of keys this widget depends on
	 */
	function get_cache_keys()
	{
		global $Collection, $Blog, $FeaturedList, $current_User, $disp;

		// Get intro Item which is displayed for this widget:
		$Item = & get_featured_Item( $disp, $this->disp_params['blog_ID'], true );

		return array(
				'wi_ID' => $this->ID, // Have the widget settings changed ?
				'set_coll_ID' => $Blog->ID, // Have the settings of the blog changed ? (ex: new skin)
				'user_ID' => (is_logged_in() ? $current_User->ID : 0), // Has the current User changed?
				'intro_feat_coll_ID' => empty($this->disp_params['blog_ID']) ? $Blog->ID : $this->disp_params['blog_ID'], // Has the content of the intro/featured post changed ?
				'item_ID' => empty( $Item ) ? 0 : $Item->ID, // Cache each item separately + Has the Item changed?
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
			echo $this->disp_params['block_body_start'];
			echo $this->disp_params['featured_intro_before'];
			echo $message;
			echo $this->disp_params['featured_intro_after'];
			echo $this->disp_params['block_body_end'];
			echo $this->disp_params['block_end'];
		}
	}
}
?>
