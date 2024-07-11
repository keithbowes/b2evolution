<?php
/**
 * This file implements the user_profile_pics_Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2008 by Daniel HAHLER - {@link http://daniel.hahler.de/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );

/**
 * user_profile_pics_Widget Class.
 *
 * This displays the blog owner's avatar.
 *
 * @package evocore
 */
class user_profile_pics_Widget extends ComponentWidget
{
	var $icon = 'user-circle';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'user_profile_pics' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'profile-picture-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('User Profile Picture');
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display the profile picture of the viewed user, item creator or collection owner.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param array local params
	 *  - 'size': Size definition, see {@link $thumbnail_sizes}. E.g. 'fit-160x160'.
	 */
	function get_param_definitions( $params )
	{
		load_funcs( 'files/model/_image.funcs.php' );

		$r = array_merge( array(
			'display_main' => array(
					'type' => 'checkbox',
					'label' => T_('Display main picture'),
					'note' => '',
					'defaultvalue' => 1,
				),
			'display_other' => array(
					'type' => 'checkbox',
					'label' => T_('Display additional pictures'),
					'note' => T_('NOTE: Only for logged in and activated users.'),
					'defaultvalue' => 0,
				),
			'link_to' => array(
					'label' => T_('Link to'),
					'note' => '',
					'type' => 'radio',
					'field_lines' => true,
					'options' => array(
							array( 'userpage', T_('User profile') ),
							array( 'fullsize', T_('Full-size profile picture (using colorbox)') ) ),
					'defaultvalue' => 'userpage',
				),
			'thumb_size' => array(
					'type' => 'select',
					'label' => T_('Image size'),
					'options' => get_available_thumb_sizes(),
					'note' => sprintf( /* TRANS: %s is a config variable name */ T_('List of available image sizes is defined in %s.'), '$thumbnail_sizes' ),
					'defaultvalue' => 'fit-160x160',
				),
			'anon_thumb_size' => array(
					'type' => 'select',
					'label' => T_('Image size for anonymous users'),
					'options' => get_available_thumb_sizes(),
					'note' => sprintf( /* TRANS: %s is a config variable name */ T_('List of available image sizes is defined in %s.'), '$thumbnail_sizes' ),
					'defaultvalue' => 'fit-160x160',
				),
			'anon_overlay_show' => array(
					'type' => 'checkbox',
					'label' => T_('Show overlay text for anonymous users'),
					'note' => T_('Check to show overlay text from field below for anonymous users.'),
					'defaultvalue' => 0,
				),
			'anon_overlay_text' => array(
					'type' => 'textarea',
					'label' => T_('Overlay text for anonymous users'),
					'note' => T_('Text to use as image overlay for anomymous users (leave empty for default).'),
					'defaultvalue' => '',
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
		global $disp, $current_User;

		$this->init_display( $params );

		$target_User = & $this->get_target_User();

		// START DISPLAY:
		echo $this->disp_params['block_start'];

		// Display title if requested
		$this->disp_title();

		echo $this->disp_params['block_body_start'];

		if( $this->get_param( 'display_main' ) )
		{	// Display main profile pictures:

			// Set overlay text:
			$thumb_overlay_text = '';
			if( ! is_logged_in() && $this->get_param( 'anon_overlay_show' ) )
			{	// If it is enabled by widget setting:
				if( trim( $this->get_param( 'anon_overlay_text' ) ) != '' )
				{	// Get overlay text from params:
					$thumb_overlay_text = nl2br( $this->get_param( 'anon_overlay_text' ) );
				}
				else
				{	// Get default overlay text from general settings:
					global $Settings;
					$thumb_overlay_text = $Settings->get( 'bubbletip_overlay' );
				}
			}

			$profile_image_tag = $target_User->get_link( array(
					'link_to'       => ( $this->disp_params['link_to'] == 'userpage' ? 'userpage' : 'none' ),
					'link_text'     => 'avatar',
					'thumb_size'    => is_logged_in() ? $this->disp_params['thumb_size'] : $this->get_param( 'anon_thumb_size' ),
					'thumb_zoom'    => ( $this->disp_params['link_to'] == 'fullsize' ),
					'thumb_overlay' => $thumb_overlay_text,
				) );

			if( is_logged_in() && $target_User->ID == $current_User->ID && ! $target_User->has_avatar() )
			{	// If user hasn't an avatar, add a link to go for uploading of avatar:
				$profile_image_tag = '<a href="'.get_user_avatar_url().'">'.$profile_image_tag.'</a>';
			}

			echo $profile_image_tag;
		}

		if( $this->get_param( 'display_other' ) )
		{	// Display additional pictures:
			if( check_user_status( 'can_view_user', $target_User->ID ) )
			{	// Only for logged in and activated users
				$user_pictures = $target_User->get_avatar_Links();
				if( count( $user_pictures ) > 0 )
				{
					$image_link_to = ( $this->disp_params['link_to'] == 'fullsize' ? 'original' : $target_User->get_userpage_url() );
					foreach( $user_pictures as $user_Link )
					{
						echo $user_Link->get_tag( array(
							'before_image'        => NULL,
							'before_image_legend' => NULL,
							'after_image_legend'  => NULL,
							'after_image'         => NULL,
							'image_size'          => $this->disp_params['thumb_size'],
							'image_link_to'       => $image_link_to,
							'image_link_title'    => $target_User->get_username(),
							'image_link_rel'      => ( $this->disp_params['link_to'] == 'fullsize' ? 'lightbox[user]' : '' )
						) );
					}
				}
			}
		}

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
		global $Blog;

		$cache_keys = array(
				'wi_ID'   => $this->ID,					// Have the widget settings changed ?
				'set_coll_ID' => $Blog->ID,			// Have the settings of the blog changed ? (ex: new owner, new skin)
			);

		if( $target_User = & $this->get_target_User() )
		{
			$cache_keys['user_ID'] = $target_User->ID; // Has the target User changed? (name, avatar, etc..)
		}

		return $cache_keys;
	}
}

?>