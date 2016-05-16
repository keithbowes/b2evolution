<?php
/**
 * This file implements the register form
 *
 * This file is not meant to be called directly.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author asimo: Evo Factory / Attila Simo
 *
 * @version $Id: _register.disp.php,v 1.13 2011/09/08 23:29:27 fplanque Exp $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'regional/model/_country.class.php', 'Country' );

global $Settings;
global $htsrv_url;
global $notify_from;
global $Blog;

if( is_logged_in() )
{ // if a user is already logged in don't allow to register
	echo '<p>'.__('You are already logged in').'</p>';
	return;
}

if( ! $Settings->get('newusers_canregister') )
{
	echo '<p>'.__('User registration is currently not allowed.').'</p>';
	return;
}

$action = param( 'action', 'string', '' );
$login = param( 'login', 'string', '' );
$email = param( 'email', 'string', '' );
$country = param( 'country', 'string', NULL );
$gender = param( 'gender', 'string', false );
$source = param( 'source', 'string', '' );
$redirect_to = param( 'redirect_to', 'string', '' );

$Form = new Form( $htsrv_url.'register.php', 'login_form', 'post' );

$Form->add_crumb( 'regform' );
if( empty( $action ) )
{
	$action = 'register';
}

$Form->hidden( 'inskin', true );
$Form->hidden( 'blog', $Blog->ID );

if( $action == 'register' )
{ // disp register form
	$Form->begin_form( 'bComment' );

	$Form->hidden( 'action', 'register' );
	$Form->hidden( 'source', $source );
	// fp>asimo: why is there no hidden redirect_to here?

	$Form->begin_field();
	$Form->text_input( 'login', $login, 22, __('Login'), __('Choose a username.'), array( 'maxlength' => 20, 'class' => 'input_text', 'required' => true ) );
	$Form->end_field();

	$Form->begin_field();
	$Form->password_input( 'pass1', '', 18, __('Password'), array( 'note'=>__('Please type your password again.'), 'maxlength' => 70, 'class' => 'input_text', 'required'=>true ) );
	$Form->end_field();

	$Form->begin_field();
	$Form->text_input( 'email', $email, 50, __('Email'), __('We respect your privacy. Your email will remain strictly confidential.'), array( 'maxlength'=>255, 'class'=>'input_text', 'required'=>true ) );

	$registration_require_country = (bool)$Settings->get('registration_require_country');

	if( $registration_require_country )
	{ // country required
		$CountryCache = & get_CountryCache();
		$Form->select_input_object( 'country', $country, $CountryCache, __('Country'), array('allow_none'=>true, 'required'=>true) );
	}

	$registration_require_gender = $Settings->get( 'registration_require_gender' );
	if( $registration_require_gender == 'required' )
	{ // gender required
		$Form->radio_input( 'gender', $gender, array(
					array( 'value' => 'M', 'label' => __('A man') ),
					array( 'value' => 'F', 'label' => __('A woman') ),
				), __('I am'), array( 'required' => true ) );
	}

	if( $Settings->get( 'registration_ask_locale' ) )
	{ // ask user language
		$locale = 'eo_EO';
		$Form->select( 'locale', $locale, 'locale_options_return', __('Locale'), __('Preferred language') );
	}
	$Form->end_field();

	$Form->end_fieldset();

	// Submit button:
	$submit_button = array( array( 'name'=>'register', 'value'=>__('Register my account now!'), 'class'=>'search', 'style'=>'font-size: 120%' ) );

	$Form->buttons_input($submit_button);

	$Form->info( '', '', sprintf( __('Your IP address (%s) and the current time are being logged.'), $Hit->IP ) );

	echo '<div class="login_actions" style="margin: 1em 0 1ex">';
	echo '<strong><a href="'.get_login_url($redirect_to).'">→ '.__('Already have an account... ?').'</a></strong>';
	echo '</div>';

	$Form->end_form();
}
elseif( $action == "reg_complete" )
{	// -----------------------------------------------------------------------------------------------------------------
	// display register complete info ( email validation not required )
	$Form->begin_form( 'bComment' );

	$Form->hidden( 'redirect_to', url_rel_to_same_host($redirect_to, $htsrv_url_sensitive) );
	$Form->hidden( 'inskin', 1 );

	$Form->begin_fieldset();
	$Form->info( __('Login'), $login );
	$Form->info( __('Email'), $email );
	$Form->end_fieldset();

	echo '<p class="center"><a href="'.$Blog->gen_baseurl().'">'.__('Continue').' →</a> ';
	echo '</p>';

	$Form->end_form();
}
elseif( $action == "reg_validation" )
{ // display "validation email sent" info ( email validation required )
	$Form->begin_form( 'bComment' );

	echo '<p>'.sprintf( __( 'An email has just been sent to %s . Please check your email and click on the validation link you will find in that email.' ), '<b>'.$email.'</b>' ).'</p>';
	echo '<p>'.sprintf( __( 'If you have not received the email in the next few minutes, please check your spam folder. The email was sent from %s and has the title «%s».' ), $notify_from,
					'<b>'.sprintf( __('Validate your email address for "%s"'), $login ).'</b>' ).'</p>';
	echo '<p>'.__( 'If you still can\'t find the email or if you would like to try with a different email address,' ).' '.
					'<a href="'.$Blog->gen_baseurl().'">'.__( 'click here to try again' ).'.</a></p>';

	$Form->end_form();
}

?>
