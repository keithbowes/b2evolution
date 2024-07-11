<?php
/**
 * This file implements the UI view for the antispam settings.
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

global $Plugins, $antispamsrv_tos_url;


$Form = new Form( NULL, 'antispam_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'antispam' );
$Form->hiddens_by_key( get_memorized() );
$Form->hidden( 'action', 'update' );

$Form->begin_fieldset( TB_('Comments/Feedback').get_manual_link('antispam-settings-comments') );
	$Form->text( 'antispam_threshold_publish', $Settings->get('antispam_threshold_publish'), 3, TB_('Publishing threshold'), TB_("(-100 to 100). Automatically publish feedbacks with a spam karma below this value.") );
	$Form->text( 'antispam_threshold_delete', $Settings->get('antispam_threshold_delete'), 3, TB_('Deletion threshold'), TB_("(-100 to 100). Automatically delete feedbacks with a spam karma over this value.") );

	$Form->info( '', sprintf( /* TRANS: %s gets replaced by the translation for this setting */ TB_('Feedbacks with a spam karma between these two thresholds will get the default status of the blog ("%s").'), TB_('New feedback status') ) );
$Form->end_fieldset();


$Form->begin_fieldset( TB_('Misc').get_manual_link('antispam-settings-misc') );
	$Form->checkbox( 'antispam_block_contact_form', $Settings->get( 'antispam_block_contact_form' ),
		TB_('Block spam on contact form'), TB_('Check to use the antispam blacklist on contact form submissions') );
	$Form->checkbox( 'antispam_block_spam_referers', $Settings->get('antispam_block_spam_referers'),
		TB_('Block spam referers'), TB_('If a referrer has been detected as spam, should we block the request with a "403 Forbidden" page?') );
	$Form->checkbox( 'antispam_report_to_central', $Settings->get('antispam_report_to_central'),
		TB_('Report to central blacklist'), TB_('When banning a keyword, offer an option to report to the central blacklist.')
		.' [<a href="'.$antispamsrv_tos_url.'">'.TB_('Terms of service').'</a>]' );
$Form->end_fieldset();


$Form->begin_fieldset( TB_('Spam detection relevance weight').get_manual_link('antispam-settings-detection-relevance-weight') );

echo '<p>'.TB_('This defines the weight of the plugin, in relation to the others.').'</p>';

$karma_plugins = $Plugins->get_list_by_events( array('GetSpamKarmaForComment') );

if( empty($karma_plugins) )
{
	echo '<p>'.TB_('There are no spam karma plugins enabled.').'</p>';
}
else foreach( $karma_plugins as $loop_Plugin )
{
	$Form->text( 'antispam_plugin_spam_weight['.$loop_Plugin->ID.']', $Plugins->index_ID_rows[$loop_Plugin->ID]['plug_spam_weight'], 2, $loop_Plugin->name );
}

$Form->end_fieldset();

$Form->begin_fieldset( TB_('Suspect users').get_manual_link('antispam-settings-suspect-users') );

	$GroupCache = & get_GroupCache( true, NT_('Don\'t move suspect users') );
	$Form->select_object( 'antispam_suspicious_group', $Settings->get('antispam_suspicious_group'), $GroupCache, TB_('Move suspect users to'), '', true );

	$trust_groups = $Settings->get('antispam_trust_groups') != '' ? explode( ',', $Settings->get('antispam_trust_groups') ) : array();
	$groups_options = array();
	$groups = $GroupCache->get_option_array();
	foreach( $groups as $group_ID => $group_name )
	{
		$groups_options[] = array( 'antispam_trust_groups[]', $group_ID, $group_name, in_array( $group_ID, $trust_groups ) );
	}
	$Form->checklist( $groups_options, 'antispam_trust_groups', TB_('Never touch users from these groups') );

$Form->end_fieldset();

if( check_user_perm( 'options', 'edit' ) )
{
	$Form->end_form( array(
		array( 'submit', 'submit', TB_('Save Changes!'), 'SaveButton', 'data-shortcut' => 'ctrl+s,command+s,ctrl+enter,command+enter' ),
		array( 'submit', 'submit[restore_defaults]', TB_('Restore defaults'), 'ResetButton' ),
		) );
}

?>
