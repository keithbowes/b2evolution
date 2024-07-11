<?php
/**
 * This file implements a class derived of the generic Skin class in order to provide custom code for
 * the skin in this folder.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @package skins
 * @subpackage bootstrap_blocks_blog
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Specific code for this skin.
 *
 * ATTENTION: if you make a new skin you have to change the class name below accordingly
 */
class bootstrap_blocks_blog_Skin extends Skin
{
	/**
	 * Skin version
	 * @var string
	 */
	var $version = '7.2.5';

	/**
	 * Do we want to use style.min.css instead of style.css ?
	 */
	var $use_min_css = 'check';  // true|false|'check' Set this to true for better optimization
	// Note: we leave this on "check" in the bootstrap_blog_skin so it's easier for beginners to just delete the .min.css file
	// But for best performance, you should set it to true.

	/**
	 * Get default name for the skin.
	 * Note: the admin can customize it.
	 */
	function get_default_name()
	{
		return 'Bootstrap Blocks Blog';
	}


	/**
	 * Get default type for the skin.
	 */
	function get_default_type()
	{
		return 'rwd';
	}


	/**
	 * What evoSkins API does has this skin been designed with?
	 *
	 * This determines where we get the fallback templates from (skins_fallback_v*)
	 * (allows to use new markup in new b2evolution versions)
	 */
	function get_api_version()
	{
		return 7;
	}


	/**
	 * Get supported collection kinds.
	 *
	 * This should be overloaded in skins.
	 *
	 * For each kind the answer could be:
	 * - 'yes' : this skin does support that collection kind (the result will be was is expected)
	 * - 'partial' : this skin is not a primary choice for this collection kind (but still produces an output that makes sense)
	 * - 'maybe' : this skin has not been tested with this collection kind
	 * - 'no' : this skin does not support that collection kind (the result would not be what is expected)
	 * There may be more possible answers in the future...
	 */
	public function get_supported_coll_kinds()
	{
		$supported_kinds = array(
				'minisite' => 'partial',
				'main' => 'partial',
				'std' => 'yes',		// Blog
				'photo' => 'no',
				'forum' => 'no',
				'manual' => 'no',
				'group' => 'maybe',  // Tracker
				// Any kind that is not listed should be considered as "maybe" supported
			);

		return $supported_kinds;
	}


	/**
	 * Get the container codes of the skin main containers
	 *
	 * This should NOT be protected. It should be used INSTEAD of file parsing.
	 * File parsing should only be used if this function is not defined
	 *
	 * @return array Array which overrides default containers; Empty array means to use all default containers.
	 */
	function get_declared_containers()
	{
		// Array to override default containers from function get_skin_default_containers():
		// - Key is widget container code;
		// - Value: array( 0 - container name, 1 - container order ),
		//          NULL - means don't use the container, WARNING: it(only empty/without widgets) will be deleted from DB on changing of collection skin or on reload container definitions.
		return array(
				'sidebar_single' => array( NT_('Sidebar Single'), 95 ),
			);
	}


	/**
	 * What CSS framework does has this skin been designed with?
	 *
	 * This may impact default markup returned by Skin::get_template() for example
	 */
	function get_css_framework()
	{
		return 'bootstrap';
	}


	/**
	 * Get screen sizes for skin settings
	 */
	function get_screen_sizes()
	{
		return array(
			'xs' => array(
				'max_width'    => '768px',
				'title'        => T_('Extra small screen'),
				// Default values:
				'block_height' => '',
				'cover_height' => '400',
			),
			'sm' => array(
				'min_width'    => '768px',
				'title'        => T_('Small screen'),
				// Default values:
				'block_height' => '400',
				'cover_height' => '270',
			),
			'md' => array(
				'min_width'    => '992px',
				'title'        => T_('Medium screen'),
				// Default values:
				'block_height' => '350',
				'cover_height' => '220',
			),
			'lg' => array(
				'min_width'    => '1200px',
				'title'        => T_('Large screen'),
				// Default values:
				'block_height' => '400',
				'cover_height' => '270',
			),
			'xxl' => array(
				'min_width'    => '1785px',
				'title'        => T_('Extra large screen'),
				// Default values:
				'block_height' => '400',
				'cover_height' => '270',
			),
		);
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 * @return array
	 */
	function get_param_definitions( $params )
	{
		// Load for function get_available_thumb_sizes():
		load_funcs( 'files/model/_image.funcs.php' );

		$r = array_merge( array(
				'section_layout_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Layout Settings')
				),
					'use_3_cols' => array(
						'label' => T_('Use 3 cols'),
						'type' => 'checklist',
						'options' => array(
							array( 'single',       sprintf( /* TRANS: position On disp=single or other disps */T_('On %s'), '<code>disp=single</code>' ), 1 ),
							array( 'posts-default',sprintf( /* TRANS: position On disp=single or other disps */T_('On %s'), '<code>disp=posts-default</code>' ), 1 ),
							array( 'posts-topcat', sprintf( /* TRANS: position On disp=single or other disps */T_('On %s'), '<code>disp=posts-topcat-intro</code>, <code>disp=posts-topcat-nointro</code>' ), 1 ),
							array( 'posts-subcat', sprintf( /* TRANS: position On disp=single or other disps */T_('On %s'), '<code>disp=posts-subcat-intro</code>, <code>disp=posts-subcat-nointro</code>' ), 1 ),
							array( 'front',        sprintf( /* TRANS: position On disp=single or other disps */T_('On %s'), '<code>disp=front</code>' ), 1 ),
							array( 'other',        T_('On other disps'), 1 ),
						),
					),
					'main_content_image_size' => array(
						'label' => T_('Image size for main content'),
						'note' => T_('Controls Aspect, Ratio and Standard Size'),
						'defaultvalue' => 'fit-1280x720',
						'options' => get_available_thumb_sizes(),
						'type' => 'select',
					),
					'max_image_height' => array(
						'label' => T_('Max image height'),
						'input_suffix' => ' px ',
						'note' => T_('Constrain height of content images by CSS.'),
						'defaultvalue' => '',
						'type' => 'integer',
						'allow_empty' => true,
					),

					'font' => array(
						'label' => T_('Default font'),
						'type'  => 'input_group',
						'inputs' => array(
							'_family' => array(
								'defaultvalue' => 'system_helveticaneue',
								'options'      => $this->get_font_definitions(),
								'type'         => 'select'
							),
							'_size' => array(
								'label' => T_('Size'),
								'defaultvalue' => 'default',
								'options'      => array(
									'default'        => T_('Default (14px)'),
									'standard'       => T_('Standard (16px)'),
									'medium'         => T_('Medium (18px)'),
									'large'          => T_('Large (20px)'),
									'very_large'     => T_('Very large (22px)'),
								),
								'type' => 'select'
							),
							'_weight' => array(
								'label' => T_('Weight'),
								'defaultvalue' => '400',
								'options' => array(
										'100' => '100',
										'200' => '200',
										'300' => '300',
										'400' => '400 ('.T_('Normal').')',
										'500' => '500',
										'600' => '600',
										'700' => '700 ('.T_('Bold').')',
										'800' => '800',
										'900' => '900',
									),
								'type' => 'select',
							)
						)
					),

					'message_affix_offset' => array(
						'label' => T_('Messages affix offset'),
						'note' => 'px. ' . T_('Set message top offset value.'),
						'defaultvalue' => '',
						'type' => 'integer',
						'allow_empty' => true,
					),
				'section_layout_end' => array(
					'layout' => 'end_fieldset',
				),

/*
				'posts_list_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Posts List Settings')
				),
*/
			)
		);
/*
		foreach( $this->get_screen_sizes() as $screen_key => $screen )
		{
			$r = array_merge( $r, array(
				$screen_key.'_posts_list_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => $screen['title'].' <code>'.( isset( $screen['min_width'] ) ? '(>='.$screen['min_width'].')' : '(<'.$screen['max_width'].')' ).'</code>'
				),
					$screen_key.'_block_height' => array(
						'label' => T_('Block height'),
						'input_suffix' => ' px ',
						'defaultvalue' => isset( $screen['block_height'] ) ? $screen['block_height'] : '',
						'type' => 'integer',
						'allow_empty' => true,
						'valid_range' => array( 'min' => 1 ),
					),
					$screen_key.'_cover_height' => array(
						'label' => T_('Cover height'),
						'input_suffix' => ' px ',
						'defaultvalue' => isset( $screen['cover_height'] ) ? $screen['cover_height'] : '',
						'type' => 'integer',
						'allow_empty' => true,
						'valid_range' => array( 'min' => 1 ),
					),
				$screen_key.'_posts_list_end' => array(
					'layout' => 'end_fieldset',
				),
			) );
		}
*/
		$r = array_merge( $r, array(
/*
				'posts_list_end' => array(
					'layout' => 'end_fieldset',
				),
*/

				'section_color_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Custom Settings')
				),
					'page_bg_color' => array(
						'label' => T_('Background color'),
						'defaultvalue' => '#fff',
						'type' => 'color',
						'transparency' => true,
					),
					'page_text_color' => array(
						'label' => T_('Text color'),
						'defaultvalue' => '#333',
						'type' => 'color',
					),
					'page_link_color' => array(
						'label' => T_('Link color'),
						'defaultvalue' => '#337ab7',
						'type' => 'color',
					),
					'page_hover_link_color' => array(
						'label' => T_('Hover link color'),
						'defaultvalue' => '#23527c',
						'type' => 'color',
					),
					'bgimg_text_color' => array(
						'label' => T_('Text color on background image'),
						'defaultvalue' => '#fff',
						'type' => 'color',
					),
					'bgimg_link_color' => array(
						'label' => T_('Link color on background image'),
						'defaultvalue' => '#6cb2ef',
						'type' => 'color',
					),
					'bgimg_hover_link_color' => array(
						'label' => T_('Hover link color on background image'),
						'defaultvalue' => '#6cb2ef',
						'type' => 'color',
					),
					'current_tab_text_color' => array(
						'label' => T_('Current tab text color'),
						'defaultvalue' => '#333',
						'type' => 'color',
					),
					'current_tab_bg_color' => array(
						'label' => T_('Current tab background color'),
						'defaultvalue' => '#fff',
						'type' => 'color',
						'transparency' => true,
					),
					'hover_tab_bg_color' => array(
						'label' => T_('Hovered tab background color'),
						'defaultvalue' => '#eee',
						'type' => 'color',
						'transparency' => true,
					),
					'panel_bg_color' => array(
						'label' => T_('Panel background color'),
						'note' => T_('Choose background color for function panels and widgets.'),
						'defaultvalue' => '#ffffff',
						'type' => 'color',
						'transparency' => true,
					),
					'panel_border_color' => array(
						'label' => T_('Panel border color'),
						'note' => T_('Choose border color for function panels and widgets.'),
						'defaultvalue' => '#ddd',
						'type' => 'color',
					),
					'panel_heading_bg_color' => array(
						'label' => T_('Panel heading background color'),
						'note' => T_('Choose background color for function panels and widgets.'),
						'defaultvalue' => '#f5f5f5',
						'type' => 'color',
						'transparency' => true,
					),
				'section_color_end' => array(
					'layout' => 'end_fieldset',
				),


				'section_colorbox_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Colorbox Image Zoom')
				),
					'colorbox' => array(
						'label' => T_('Colorbox Image Zoom'),
						'note' => T_('Check to enable javascript zooming on images (using the colorbox script)'),
						'defaultvalue' => 1,
						'type' => 'checkbox',
					),
					'colorbox_vote_post' => array(
						'label' => T_('Voting on Post Images'),
						'note' => T_('Check this to enable AJAX voting buttons in the colorbox zoom view'),
						'defaultvalue' => 1,
						'type' => 'checkbox',
					),
					'colorbox_vote_post_numbers' => array(
						'label' => T_('Display Votes'),
						'note' => T_('Check to display number of likes and dislikes'),
						'defaultvalue' => 1,
						'type' => 'checkbox',
					),
					'colorbox_vote_comment' => array(
						'label' => T_('Voting on Comment Images'),
						'note' => T_('Check this to enable AJAX voting buttons in the colorbox zoom view'),
						'defaultvalue' => 1,
						'type' => 'checkbox',
					),
					'colorbox_vote_comment_numbers' => array(
						'label' => T_('Display Votes'),
						'note' => T_('Check to display number of likes and dislikes'),
						'defaultvalue' => 1,
						'type' => 'checkbox',
					),
					'colorbox_vote_user' => array(
						'label' => T_('Voting on User Images'),
						'note' => T_('Check this to enable AJAX voting buttons in the colorbox zoom view'),
						'defaultvalue' => 1,
						'type' => 'checkbox',
					),
					'colorbox_vote_user_numbers' => array(
						'label' => T_('Display Votes'),
						'note' => T_('Check to display number of likes and dislikes'),
						'defaultvalue' => 1,
						'type' => 'checkbox',
					),
				'section_colorbox_end' => array(
					'layout' => 'end_fieldset',
				),


				'section_username_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Username options')
				),
					'gender_colored' => array(
						'label' => T_('Display gender'),
						'note' => T_('Use colored usernames to differentiate men & women.'),
						'defaultvalue' => 0,
						'type' => 'checkbox',
					),
					'bubbletip' => array(
						'label' => T_('Username bubble tips'),
						'note' => T_('Check to enable bubble tips on usernames'),
						'defaultvalue' => 0,
						'type' => 'checkbox',
					),
					'autocomplete_usernames' => array(
						'label' => T_('Autocomplete usernames'),
						'note' => T_('Check to enable auto-completion of usernames entered after a "@" sign in the comment forms'),
						'defaultvalue' => 1,
						'type' => 'checkbox',
					),
				'section_username_end' => array(
					'layout' => 'end_fieldset',
				),


				'section_access_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('When access is denied or requires login...')
				),
					'access_login_containers' => array(
						'label' => T_('Display on login screen'),
						'note' => '',
						'type' => 'checklist',
						'options' => array(
							array( 'header',   sprintf( T_('"%s" container'), NT_('Header') ),    1 ),
							array( 'page_top', sprintf( T_('"%s" container'), NT_('Page Top') ),  1 ),
							array( 'menu',     sprintf( T_('"%s" container'), NT_('Menu') ),      0 ),
							array( 'sidebar',  sprintf( T_('"%s" container'), NT_('Sidebar') ),   0 ),
							array( 'sidebar2', sprintf( T_('"%s" container'), NT_('Sidebar 2') ), 0 ),
							array( 'footer',   sprintf( T_('"%s" container'), NT_('Footer') ),    1 ) ),
						),
				'section_access_end' => array(
					'layout' => 'end_fieldset',
				),

			), parent::get_param_definitions( $params ) );

		return $r;
	}


	/**
	 * Get ready for displaying the skin.
	 *
	 * This may register some CSS or JS...
	 */
	function display_init()
	{
		global $Messages, $disp, $debug;

		// Request some common features that the parent function (Skin::display_init()) knows how to provide:
		parent::display_init( array(
				'superbundle',             // Load general front-office JS + bundled jQuery and Bootstrap
				'bootstrap_messages',      // Initialize $Messages Class to use Bootstrap styles
				'style_css',               // Load the style.css file of the current skin
				'colorbox',                // Load Colorbox (a lightweight Lightbox alternative + customizations for b2evo)
				'disp_auto',               // Automatically include additional CSS and/or JS required by certain disps (replace with 'disp_off' to disable this)
			) );

		// Skin specific initializations:

		// **** Layout Settings / START ****
/*
		// Max image height:
		$this->dynamic_style_rule( 'max_image_height', '.evo_image_block img { max-height: $setting_value$px; width: auto; }', array(
			'check' => 'not_empty'
		) );
*/
		// Default font - Family:
		$this->dynamic_style_rule( 'font_family', '#skin_wrapper { font-family: $setting_value$ }', array(
			'options' => $this->get_font_definitions( 'style' )
		) );
		// Default font - Size:
		$this->dynamic_style_rule( 'font_size', '$setting_value$', array(
			'options' => array(
				'default' => '',
				'standard' =>
					'.container { font-size: 16px !important}'.
					'.container input.search_field { height: 100%}'.
					'.container h1 { font-size: 38px }'.
					'.container h2 { font-size: 32px }'.
					'.container h3 { font-size: 26px }'.
					'.container h4 { font-size: 18px }'.
					'.container h5 { font-size: 16px }'.
					'.container h6 { font-size: 14px }'.
					'.container .small { font-size: 85% !important }',
				'medium' =>
					'.container { font-size: 18px !important }'.
					'.container input.search_field { height: 100% }'.
					'.container h1 { font-size: 40px }'.
					'.container h2 { font-size: 34px }'.
					'.container h3 { font-size: 28px }'.
					'.container h4 { font-size: 20px }'.
					'.container h5 { font-size: 18px }'.
					'.container h6 { font-size: 16px }'.
					'.container .small { font-size: 85% !important }',
				'large' =>
					'.container { font-size: 20px !important }'.
					'.container input.search_field { height: 100% }'.
					'.container h1 { font-size: 42px }'.
					'.container h2 { font-size: 36px }'.
					'.container h3 { font-size: 30px }'.
					'.container h4 { font-size: 22px }'.
					'.container h5 { font-size: 20px }'.
					'.container h6 { font-size: 18px }'.
					'.container .small { font-size: 85% !important }',
				'very_large' =>
					'.container { font-size: 22px !important }'.
					'.container input.search_field { height: 100% }'.
					'.container h1 { font-size: 44px }'.
					'.container h2 { font-size: 38px }'.
					'.container h3 { font-size: 32px }'.
					'.container h4 { font-size: 24px }'.
					'.container h5 { font-size: 22px }'.
					'.container h6 { font-size: 20px }'.
					'.container .small { font-size: 85% !important }',
			)
		) );
		// Default font - Weight:
		$this->dynamic_style_rule( 'font_weight', '#skin_wrapper { font-weight: $setting_value$ }' );
		// **** Layout Settings / END ****

		// **** Posts List Settings / START ****
/*
		foreach( $this->get_screen_sizes() as $screen_key => $screen )
		{
			if( isset( $screen['min_width'] ) )
			{	// Start of @media screen wrapper:
				$this->add_dynamic_style( '@media only screen and (min-width: '.$screen['min_width'].') { ' );
			}

			// Block height:
			$this->dynamic_style_rule( $screen_key.'_block_height', '.evo_tiles>div>.evo_tile { height: $setting_value$px }', array(
					'check' => 'not_empty'
			) );
			// Cover height:
			$this->dynamic_style_rule( $screen_key.'_cover_height', '.evo_tiles .evo_tile_cover { height: $setting_value$px }', array(
					'check' => 'not_empty'
			) );

			if( isset( $screen['min_width'] ) )
			{	// End of @media screen wrapper:
				$this->add_dynamic_style( ' }' );
			}
		}
*/
		// **** Posts List Settings / END ****

		// **** Custom Settings / START ****
		// Background color:
		$this->dynamic_style_rule( 'page_bg_color', '#skin_wrapper { background-color: $setting_value$ }' );
		// Text color:
		$this->dynamic_style_rule( 'page_text_color', '#skin_wrapper { color: $setting_value$ }' );
		// Link color:
		$this->dynamic_style_rule( 'page_link_color',
			'a { color: $setting_value$ }'.
			'h4.evo_comment_title a, h4.panel-title a.evo_comment_type, .pagination li:not(.active) a, .pagination li:not(.active) span { color: $setting_value$ !important }'.
			'.pagination li.active a, .pagination li.active span { color: #fff; background-color: $setting_value$ !important; border-color: $setting_value$ }'
		);
		if( $this->get_setting( 'gender_colored' ) !== 1 )
		{	// If gender option is not enabled, choose custom link color. Otherwise, chose gender link colors:
			$this->dynamic_style_rule( 'page_link_color', 'h4.panel-title a { color: $setting_value$ }' );
		}
		// Hover link color:
		$this->dynamic_style_rule( 'page_hover_link_color', 'a:hover { color: $setting_value$ }' );
		// Text color on background image:
		$this->dynamic_style_rule( 'bgimg_text_color', '.evo_hasbgimg { color: $setting_value$ }' );
		// Link color on background image:
		$this->dynamic_style_rule( 'bgimg_link_color', '.evo_hasbgimg a:not(.btn) { color: $setting_value$ }' );
		// Hover link color on background image:
		$this->dynamic_style_rule( 'bgimg_hover_link_color', '.evo_hasbgimg a:not(.btn):hover { color: $setting_value$ }' );
		// Current tab text color:
		$this->dynamic_style_rule( 'current_tab_text_color', 'ul.nav.nav-tabs li a.selected { color: $setting_value$ }' );
		// Current tab background color:
		$this->dynamic_style_rule( 'current_tab_bg_color', 'ul.nav.nav-tabs li a.selected { background-color: $setting_value$ }' );
		// Hovered tab background color:
		$this->dynamic_style_rule( 'hover_tab_bg_color', 'ul.nav.nav-tabs li a.default:hover { background-color: $setting_value$; border-top-color: $setting_value$; border-left-color: $color; border-right-color: $setting_value$ }' );
		// Panel background color:
		$this->dynamic_style_rule( 'panel_bg_color', '.panel, .pagination>li>a { background-color: $setting_value$ }' );
		// Panel border color:
		$this->dynamic_style_rule( 'panel_border_color',
			'.pagination li a, .pagination>li>a:focus, .pagination>li>a:hover, .pagination>li>span:focus, .pagination>li>span:hover,'.
			'.nav-tabs, .panel-default, .panel .panel-footer,'.
			'.panel .table, .panel .table th, .table-bordered>tbody>tr>td, .table-bordered>tbody>tr>th, .table-bordered>tfoot>tr>td, .table-bordered>tfoot>tr>th, .table-bordered>thead>tr>td, .table-bordered>thead>tr>th'.
			'{ border-color: $setting_value$ }'.
			'.panel .panel-heading { border-color: $setting_value$; background-color: $setting_value$ }'.
			'.nav-tabs>li>a:hover { border-bottom: 1px solid $setting_value$ }'.
			'.nav-tabs>li.active>a, .nav-tabs>li.active>a:focus, .nav-tabs>li.active>a:hover { border-top-color: $setting_value$; border-left-color: $setting_value$; border-right-color: $setting_value$ }'
		);
		// Panel heading background color:
		$this->dynamic_style_rule( 'panel_heading_bg_color', '.panel .panel-heading, .panel .panel-footer { background-color: $setting_value$ }' );
		// **** Custom Settings / END ****

		// Add dynamic CSS rules headline:
		$this->add_dynamic_css_headline();

		// Init JS to affix Messages:
		init_affix_messages_js( $this->get_setting( 'message_affix_offset' ) );
	}


	/**
	 * Check if side(left and/or right) navigations are visible for current page
	 *
	 * @return boolean TRUE on visible
	 */
	function is_side_navigation_visible()
	{
		global $disp;

		if( in_array( $disp, array( 'access_requires_login', 'content_requires_login', 'access_denied' ) ) )
		{ // Display left navigation column on this page when at least one sidebar container is visible:
			return $this->show_container_when_access_denied( 'sidebar' ) || $this->show_container_when_access_denied( 'sidebar2' );
		}

		// Display left navigation column only on these pages:
		return in_array( $disp, array( 'front', 'posts', 'comments', 'flagged', 'mustread', 'single', 'search', 'edit', 'edit_comment', 'catdir', '404' ) );
	}


	/**
	 * Check if 3rd/right column layout can be used for current page
	 *
	 * @return boolean
	 */
	function is_3rd_right_column_layout()
	{
		global $disp, $disp_detail;

		if( ! $this->is_side_navigation_visible() )
		{	// Side navigation is hidden for current page:
			return false;
		}

		// Check when we should use layout with 3 columns:
		if( $disp == 'front' )
		{	// Front page
			return (boolean)$this->get_checklist_setting( 'use_3_cols', 'front' );
		}

		if( $disp == 'single' )
		{	// Single post/item page:
			return ( $this->get_checklist_setting( 'use_3_cols', 'single' )
				// old setting should be supported:
				|| $this->get_setting( 'single_3_cols' ) );
		}

		if( $disp_detail == 'posts-default' )
		{	// Category page:
			return (boolean)$this->get_checklist_setting( 'use_3_cols', 'posts-default' );
		}

		if( $disp_detail == 'posts-topcat-nointro' || $disp_detail == 'posts-topcat-intro' )
		{	// Category page with or without intro:
			return (boolean)$this->get_checklist_setting( 'use_3_cols', 'posts-topcat' );
		}

		if( $disp_detail == 'posts-subcat-nointro' || $disp_detail == 'posts-subcat-intro' )
		{	// Sub-category page with or without intro:
			return (boolean)$this->get_checklist_setting( 'use_3_cols', 'posts-subcat' );
		}

		// All other disps:
		return (boolean)$this->get_checklist_setting( 'use_3_cols', 'other' );
	}


	/**
	 * Get layout style class depending on skin settings and current disp
	 *
	 * @param string Place where class is used
	 */
	function get_layout_class( $place )
	{
		global $disp;

		$r = '';

		switch( $place )
		{
			case 'container':
				$r .= 'container';
				if( $disp == 'posts' || $this->is_3rd_right_column_layout() )
				{	// Layout with 3 columns on current page:
					$r .= ' container-xxl';
				}
				break;

			case 'main_column':
				if( $this->is_side_navigation_visible() )
				{	// Layout with visible left sidebar:
					if( $this->is_3rd_right_column_layout() )
					{	// Layout with 3 columns on current page:
						$r .= 'col-xxl-8 col-xxl-pull-2 ';
					}
					elseif( $disp == 'posts' )
					{
						$r .= 'col-xxl-10 ';
					}
					$r .= 'col-md-9 pull-right-md';
				}
				else
				{
					$r .= 'col-md-12';
				}
				break;

			case 'left_column':
				if( $this->is_3rd_right_column_layout() )
				{	// Layout with 3 columns on current page:
					$r .= 'col-xxl-2 ';
				}
				elseif( $disp == 'posts' )
				{
					$r .= 'col-xxl-2 ';
				}
				$r .= 'col-md-3 col-xs-12 pull-left-md';
				break;

			case 'right_column':
				if( $this->is_3rd_right_column_layout() )
				{	// Layout with 3 columns on current page:
					$r .= 'col-xxl-2 col-xxl-push-8 ';
				}
				elseif( $disp == 'posts' )
				{
					$r .= 'col-xxl-2 ';
				}
				$r .= 'col-md-3 col-xs-12 pull-right-md';
				break;
		}

		return $r;
	}
}

?>