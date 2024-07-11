<?php
/**
 * This file implements the user_register_quick_Widget class.
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
load_class( '_core/model/dataobjects/_dataobjectlist2.class.php', 'DataObjectList2' );

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class user_register_quick_Widget extends ComponentWidget
{
	var $icon = 'registered';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'user_register_quick' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'email-capture-quick-registration-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Email capture / Quick registration');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( $this->disp_params['title'] );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display a quick registration form.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		global $Settings;

		// Load all active newsletters:
		$NewsletterCache = & get_NewsletterCache();
		$NewsletterCache->load_where( 'enlt_active = 1 AND enlt_perm_subscribe = "anyone"' );
		// Initialize checkbox options for param "Newsletter":
		$def_newsletters = explode( ',', $Settings->get( 'def_newsletters' ) );
		foreach( $NewsletterCache->cache as $Newsletter )
		{
			$newsletters_options[] = array(
				$Newsletter->ID,
				$Newsletter->get( 'name' ).': '.$Newsletter->get( 'label' ),
				in_array( $Newsletter->ID, $def_newsletters ) ? 1 : 0, // checked by default
			);
		}
		$newsletters_options[] = array(
			'default',
			T_('Also subscribe user to all default lists for new users.'),
			1, // checked by default
		);

		$r = array_merge( array(
				'title' => array(
					'label' => T_('Block title'),
					'note' => T_('Title to display in your skin.'),
					'size' => 40,
					'defaultvalue' => T_('Get our newsletter!'),
				),
				'intro' => array(
					'label' => T_('Intro text'),
					'note' => '',
					'type' => 'html_textarea',
					'defaultvalue' => T_('Don\'t miss the news!'),
				),
				'ask_firstname' => array(
					'label' => T_('Ask for first name'),
					'note' => '',
					'type' => 'radio',
					'options' => array(
							array( 'no', T_('No') ),
							array( 'optional', T_('Optional') ),
							array( 'required', T_('Required') )
						),
					'defaultvalue' => 'no',
				),
				'ask_lastname' => array(
					'label' => T_('Ask for last name'),
					'note' => '',
					'type' => 'radio',
					'options' => array(
							array( 'no', T_('No') ),
							array( 'optional', T_('Optional') ),
							array( 'required', T_('Required') )
						),
					'defaultvalue' => 'no',
				),
				'ask_country' => array(
					'label' => T_('Ask for country'),
					'note' => '',
					'type' => 'radio',
					'options' => array(
							array( 'no', T_('No') ),
							array( 'optional', T_('Optional') ),
							array( 'required', T_('Required') )
						),
					'defaultvalue' => 'no',
				),
				'source' => array(
					'label' => T_('Source code'),
					'note' => '',
					'size' => 30,
					'maxlength' => 30,
					'defaultvalue' => 'email capture form',
				),
				'usertags' => array(
					'label' => T_('Tag user with'),
					'type' => 'usertag',
					'size' => 30,
					'maxlength' => 255,
				),
				'newsletters' => array(
					'label' => T_('Subscribe to lists'),
					'type' => 'checklist',
					'options' => $newsletters_options,
					'note' => ''
				),
				'subscribe' => array(
					'label' => T_('Subscribe to collection'),
					'type' => 'checklist',
					'options' => array(
						array( 'post', T_('check to auto subscribe new user to current collection posts'), 1 ),
						array( 'comment', T_('check to auto subscribe new user to current collection comments'), 1 ),
					),
				),
				'button' => array(
					'label' => T_('Button title'),
					'note' => T_('Text that appears on the form submit button.'),
					'size' => 40,
					'defaultvalue' => T_('Sign up!'),
				),
				'button_class' => array(
					'label' => T_('Button class'),
					'note' => T_('Form submit button class'),
					'size' => 40,
					'defaultvalue' => 'btn-primary'
				),
				'redirect_to' => array(
					'label' => T_('Redirect to'),
					'note' => T_('Enter an Item slug or an URL.'),
					'size' => 100,
					'defaultvalue' => '',
				),

				// Hidden, used by emailcapture shorttag
				'inline' => array(
					'label' => 'Internal: Display inline',
					'defaultvalue' => 0,
					'no_edit' => true
				)
			), parent::get_param_definitions( $params ) );

		if( isset( $r['allow_blockcache'] ) )
		{ // Set default blockcache to false and disable this setting because caching is never allowed for this widget
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
		global $Collection, $Blog;

		if( is_logged_in() )
		{	// No display when user is already registered
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because you are already registered.' );
			return false;
		}

		$params['redirect_to'] = param( 'redirect_to', 'url', regenerate_url( '', '', '', '&' ) );

		if( $Blog->get_ajax_form_enabled() )
		{	// Load widget form through AJAX:
			$widget_params = array(
				'action' => 'get_widget_form',
				'blog'   => $Blog->ID,
				'params' => $params,
			);
			if( empty( $this->ID ) )
			{	// Use code for calling widget from content with inline short tag like [emailcapture]:
				$widget_params['wi_code'] = $this->get( 'code' );
			}
			else
			{	// Use ID for calling widget from DB:
				$widget_params['wi_ID'] = $this->ID;
			}
			display_ajax_form( $widget_params );
		}
		else
		{	// Display widget form:
			$this->display_form( $params );
		}
	}


	/**
	 * Display widget form
	 *
	 * @param array Params
	 */
	function display_form( $params )
	{
		global $Collection, $Blog, $Settings, $Session, $Plugins, $redirect_to, $dummy_fields;

		if( $Settings->get( 'newusers_canregister' ) != 'yes' || ! $Settings->get( 'quick_registration' ) )
		{ // Display error message when quick registration is disabled
			echo '<p class="error">'.T_('Quick registration is currently disabled on this system.').'</p>';
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because quick registration is currently disabled on this system.' );
			return false;
		}

		// Restore the typed in params from the redirected page:
		$widget_param_input_err_messages = $Session->get( 'param_input_err_messages_'.$this->ID );
		$widget_param_input_values = $Session->get( 'param_input_values_'.$this->ID );
		if( ! empty( $widget_param_input_err_messages ) )
		{ // Convert param errors to global $param_input_err_messages that is used to display an error text under input field
			global $param_input_err_messages;
			$param_input_err_messages = $widget_param_input_err_messages;
		}
		// Clear the temp session vars
		$Session->delete( 'param_input_err_messages_'.$this->ID );
		$Session->delete( 'param_input_values_'.$this->ID );
		$Session->dbsave();

		$this->init_display( $params );

		if( isset( $this->BlockCache ) )
		{	// Do NOT cache some of these links are using a redirect_to param, which makes it page dependent.
			// Note: also beware of the source param.
			// so this will be cached by the PageCache; there is no added benefit to cache it in the BlockCache
			// (which could have been shared between several pages):
			$this->BlockCache->abort_collect();
		}

		echo $this->disp_params['block_start'];

		$this->disp_title();

		echo $this->disp_params['block_body_start'];

		if( trim( $this->disp_params['intro'] ) !== '' )
		{ // Intro text
			echo '<p>'.$this->disp_params['intro'].'</p>';
		}

		// Get current form display mode:
		$form_display_mode = $this->get_form_display( 'compact' );
		$is_nolabels_mode = in_array( $form_display_mode, array( 'nolabels', 'inline', 'grouped' ) );

		$Form = new Form( get_htsrv_url( 'login' ).'register.php', 'register_form', 'post' );

		$Form->begin_form( NULL, '', array( 'class' => 'widget_register_form evo_widget_form__'.$form_display_mode ) );

		$Plugins->trigger_event( 'DisplayRegisterFormBefore', array( 'Form' => & $Form, 'inskin' => true, 'Widget' => & $this ) );

		$Form->add_crumb( 'regform' );
		$Form->hidden( 'action', 'quick_register' );
		$Form->hidden( 'inskin', true );
		$Form->hidden( 'blog', $Blog->ID );
		$Form->hidden( 'widget', $this->ID );
		$Form->hidden( 'redirect_to', $params['redirect_to'] );

		if( $this->disp_params['inline'] == 1 )
		{
			$Form->hidden( 'inline', 1 );
			$Form->hidden( 'source', $this->disp_params['source'] );
			$Form->hidden( 'ask_firstname', $this->disp_params['ask_firstname'] );
			$Form->hidden( 'ask_lastname', $this->disp_params['ask_lastname'] );
			$Form->hidden( 'ask_country', $this->disp_params['ask_country'] );
			$Form->hidden( 'usertags', $this->disp_params['usertags'] );
			$Form->hidden( 'subscribe_post', $this->disp_params['subscribe']['post'] );
			$Form->hidden( 'subscribe_comment', $this->disp_params['subscribe']['comment'] );

			$newsletters = array();
			foreach( $this->disp_params['newsletters'] as $loop_newsletter )
			{
				if( $loop_newsletter[2] == 1 )
				{
					$newsletters[] = $loop_newsletter[0];
				}
			}
			$Form->hidden( 'newsletters', implode( ',', $newsletters ) );
		}

		if( $form_display_mode == 'grouped' )
		{	// Start a group of all inputs and submit button in single line:
			$Form->begin_line();
			$Form->inputend = '';
			$Form->fieldend = '';
		}

		if( $this->disp_params['ask_firstname'] != 'no' )
		{ // First name
			$firstname_value = isset( $widget_param_input_values['firstname'] ) ? $widget_param_input_values['firstname'] : '';
			$firstname_params = array(
					'maxlength' => 50,
					'class' => 'input_text'.( $this->disp_params['inline'] == 1 ? ' inline_widget' : '' ),
					'input_suffix' => '',
				);
			if( $this->disp_params['ask_firstname'] == 'required' )
			{	// Params if first name is required:
				// Set css class "field_required":
				$firstname_params['required'] = true;
				// Set HTML5 attribute required="required" to display JS error before submit form:
				$firstname_params['input_required'] = 'required';
			}
			if( $is_nolabels_mode )
			{	// Display placeholder only in mode when labels are hidden:
				$firstname_params['placeholder'] = T_('Your first name');
			}
			$Form->text_input( 'firstname', $firstname_value, 18, ( $is_nolabels_mode ? '' : T_('Your first name') ), '', $firstname_params );
		}

		if( $this->disp_params['ask_lastname'] != 'no' )
		{ // Last name
			$lastname_value = isset( $widget_param_input_values['lastname'] ) ? $widget_param_input_values['lastname'] : '';
			$lastname_params = array(
					'maxlength' => 50,
					'class' => 'input_text'.( $this->disp_params['inline'] == 1 ? ' inline_widget' : '' ),
					'input_suffix' => '', // Remove default "\n" in order to avoid a space on grouped mode
				);
			if( $this->disp_params['ask_lastname'] == 'required' )
			{	// Params if first name is required:
				// Set css class "field_required":
				$lastname_params['required'] = true;
				// Set HTML5 attribute required="required" to display JS error before submit form:
				$lastname_params['input_required'] = 'required';
			}
			if( $is_nolabels_mode )
			{	// Display placeholder only in mode when labels are hidden:
				$lastname_params['placeholder'] = T_('Your last name');
			}
			$Form->text_input( 'lastname', $lastname_value, 18, ( $is_nolabels_mode ? '' : T_('Your last name') ), '', $lastname_params );
		}

		// E-mail
		$email_params = array(
			'maxlength'      => 255,
			'class'          => 'input_text'.( $this->disp_params['inline'] == 1 ? ' inline_widget' : '' ),
			'required'       => true,
			'input_required' => 'required',
			'input_suffix' => '', // Remove default "\n" in order to avoid a space on grouped mode
		);
		if( $is_nolabels_mode )
		{	// Display placeholder only in mode when labels are hidden:
			$email_params['placeholder'] = T_('Your email');
		}
		$email_value = isset( $widget_param_input_values[ $dummy_fields['email'] ] ) ? $widget_param_input_values[ $dummy_fields['email'] ] : '';
		$Form->email_input( $dummy_fields['email'], $email_value, 50, ( $is_nolabels_mode ? '' : T_('Your email') ), $email_params );

		if( $this->disp_params['ask_country'] != 'no' && empty( $this->disp_params['hide_country_by_plugin'] ) )
		{	// Country
			$CountryCache = & get_CountryCache();
			$CountryCache->none_option_text = NT_('Select your country');
			$country_value = isset( $widget_param_input_values['country'] ) ? $widget_param_input_values['country'] : get_param( 'country' );
			$Form->select_country( 'country', $country_value, $CountryCache, ( $is_nolabels_mode ? '' : T_('Country') ), array(
				'allow_none' => true,
				'required' => $this->disp_params['ask_country'] == 'required',
				'input_suffix' => '', // Remove default "\n" in order to avoid a space on grouped mode
			) );
		}

		$Form->buttons( array( array(
				'value' => $this->disp_params['button'],
				'class' => $this->disp_params['button_class'].' submit' )
			) );

		if( $form_display_mode == 'grouped' )
		{	// End of the group of all inputs and submit button in single line:
			$Form->end_line();
		}

		// Submit button:
		$Form->end_form();

		if( ! is_logged_in() )
		{	// JS code to get crumb from AJAX request when page caching is enabled:
			echo '<script>
var user_reg_widget_request_sent = false;
jQuery( ".widget_register_form" ).submit( function()
{
	if( user_reg_widget_request_sent )
	{	// A submit request was already sent, do not send another:
		return;
	}

	user_reg_widget_request_sent = true;
	var form = jQuery( this );

	jQuery.ajax(
	{
		type: "POST",
		url: "'.get_htsrv_url().'anon_async.php",
		data: { "action": "get_regform_crumb" },
		success: function( result )
		{
			result = ajax_debug_clear( result );
			form.find( "[name=crumb_regform]" ).val( result );
			form.submit();
		},
		error: function( jqXHR, textStatus, errorThrown )
		{	// Display error text on error request:
			requestSent = false;
			var wrong_response_code = typeof( jqXHR.status ) != "undefined" && jqXHR.status != 200 ? "\nHTTP Response code: " + jqXHR.status : "";
			alert( "Error: could not get crumb from server. Please contact the site admin and check the browser and server error logs. (" + textStatus + ": " + errorThrown + ")"
				+ wrong_response_code );
		}
	} );

	return false;
} );
</script>';
		}

		echo $this->disp_params['block_body_end'];

		echo $this->disp_params['block_end'];

		if( ! empty( $widget_param_input_err_messages ) )
		{ // Clear param errors here because we already display them above
			// Don't display them twice on another widget form
			$param_input_err_messages = NULL;
		}

		return true;
	}
}

?>