<?php
/**
 * This file implements the Language/Locale/Version switch Widget class.
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
 * ComponentWidget: Common navigation links.
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class coll_locale_switch_Widget extends ComponentWidget
{
	var $icon = 'language';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'coll_locale_switch' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'coll-locale-switch-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Language/Locale/Version switch');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( @$this->disp_params['title'] );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display flags to switch between Language/Locale/Version');
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
					'label' => T_('Block title'),
					'note' => T_('Title to display in your skin.'),
					'size' => 40,
					'defaultvalue' => '',
				),
				'show_current' => array(
					'label' => T_('Show current'),
					'note' => T_('Check to show the current version in the list.'),
					'type' => 'checkbox',
					'defaultvalue' => 0,
				),
			), parent::get_param_definitions( $params ) );

		return $r;

	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Collection, $Blog, $locales, $current_locale;

		$this->init_display( $params );

		// Get collection locales:
		$coll_locales = $Blog->get_locales( 'all' );

		if( count( $coll_locales ) < 2 )
		{	// Don't display this widget when less 2 locales:
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden as long as you don\'t enable at least 2 locales in the collection.' );
			return false;
		}

		echo $this->disp_params['block_start'];

		$this->disp_title();

		echo $this->disp_params['block_body_start'];

		echo $this->disp_params['list_start'];

		// Get currently viewing Item:
		$current_Item = & get_current_Item();
		// Get currently viewing locale:
		$view_locale = ( $current_Item ? $current_Item->get( 'locale' ) : $current_locale );

		foreach( $coll_locales as $coll_locale => $linked_coll_ID )
		{
			if( ! isset( $locales[ $coll_locale ] ) || ! $locales[ $coll_locale ]['enabled'] )
			{	// Skip wrong or disabled locale:
				continue;
			}

			if( ! $this->disp_params['show_current'] && $view_locale == $coll_locale )
			{	// Don't show current locale:
				continue;
			}

			if( $current_Item &&
			    ( $version_Item = & $current_Item->get_version_Item( $coll_locale ) ) )
			{	// Use permanent URL of the version Item with requested locale:
				$locale_switch_url = $version_Item->get_permanent_url();
			}
			else
			{	// Use URL to front page of the current collection:
				if( ! empty( $linked_coll_ID ) )
				{	// Use linked collection:
					$BlogCache = & get_BlogCache();
					$locale_Blog = & $BlogCache->get_by_ID( $linked_coll_ID, false, false );
				}
				else
				{	// Use current collection:
					$locale_Blog = $Blog;
				}
				if( $locale_Blog )
				{
					$locale_switch_url = $locale_Blog->get( 'url' );
					if( $coll_locale != $locale_Blog->get( 'locale' ) )
					{	// Append locale param only when main locale of the Collection is different than current needed:
						$locale_switch_url = url_add_param( $locale_switch_url, 'coll_locale='.urlencode( $coll_locale ) );
					}
				}
				else
				{	// Skip wrong Collection:
					continue;
				}
			}

			$class = empty( $this->disp_params['widget_css_class'] ) ? $this->disp_params['link_default_class'] : $this->disp_params['widget_css_class'];

			echo $this->disp_params['item_start'];

			echo '<a href="'.$locale_switch_url.'"'.( empty( $class ) ? '' : ' class="'.$class.'"' ).'>'.
					locale_flag( $coll_locale, 'w16px', 'flag', '', false ).' '.
					$locales[ $coll_locale ]['name'].
				'</a>';

			echo $this->disp_params['item_end'];
		}

		echo $this->disp_params['list_end'];

		echo $this->disp_params['block_body_end'];

		echo $this->disp_params['block_end'];

		return true;
	}


	/**
	 * Maybe be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @return array of keys this widget depends on
	 */
	function get_cache_keys()
	{
		global $Collection, $Blog;

		return array(
				'wi_ID'        => $this->ID, // Have the widget settings changed ?
				'set_coll_ID'  => $Blog->ID, // Have the settings of the blog changed ? (ex: new skin)
				'cont_coll_ID' => $Blog->ID, // Has the content of the displayed blog changed ?
				'item_ID'      => ( $current_Item = & get_current_Item() ? $current_Item->ID : 0 ), // Cache each item separately + Has the Item changed?
			);
	}
}

?>
