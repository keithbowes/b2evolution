<?php
/**
 * This file implements the UI view for the other email settings.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @var GeneralSettings
 */
global $Settings;

global $baseurl, $admin_url;

global $repath_test_output, $action;


$Form = new Form( NULL, 'settings_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'emailsettings' );
$Form->hidden( 'ctrl', 'email' );
$Form->hidden( 'tab', get_param( 'tab' ) );
$Form->hidden( 'tab3', get_param( 'tab3' ) );
$Form->hidden( 'action', 'settings' );

$Form->begin_fieldset( TB_('Email campaign throttling').get_manual_link( 'email-throttling-settings' ) );

	$Form->radio_input( 'email_campaign_send_mode', $Settings->get( 'email_campaign_send_mode' ),
		array(
			array( 'value' => 'immediate', 'label' => TB_('Immediate'), 'note' => TB_('Press "Next" after each chunk') ),
			array( 'value' => 'cron', 'label' => TB_('Asynchronous'), 'note' => TB_('A scheduled job will send chunks') )
		),
		TB_('Sending'),
		array( 'lines' => true ) );

	$Form->text_input( 'email_campaign_chunk_size', $Settings->get( 'email_campaign_chunk_size' ), 5, TB_('Chunk Size'), TB_('emails at a time'), array( 'maxlength' => 10 ) );

	$Form->text_input( 'email_campaign_max_domain', $Settings->get( 'email_campaign_max_domain' ), 5, TB_('Max emails to same domain'), TB_('In each chunk, avoid sending too many emails to same recipient domain (Useful to avoid balcklisting from gmail.com, hotmail.com, etc.)'), array( 'maxlength' => 10 ) );

	$Form->duration_input( 'email_campaign_cron_repeat', $Settings->get( 'email_campaign_cron_repeat' ), TB_('Delay between chunks'), 'days', 'minutes', array( 'note' => TB_('timing between scheduled job runs') ) );

	$Form->duration_input( 'email_campaign_cron_limited', $Settings->get( 'email_campaign_cron_limited' ), TB_('Delay in case all remaining recipients have reached max # of emails for the current day'), 'days', 'minutes', array( 'note' => TB_('timing between scheduled job runs') ) );

$Form->end_fieldset();

if( check_user_perm( 'emails', 'edit' ) )
{
	$Form->end_form( array( array( 'submit', '', TB_('Save Changes!'), 'SaveButton' ) ) );
}

?>