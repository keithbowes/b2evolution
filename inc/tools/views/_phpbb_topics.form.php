<?php
/**
 * This file display the 5th step of phpBB importer
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $admin_url, $flush_action, $phpbb_tool_title, $phpbb_version;

phpbb_display_steps( 5 );

$Form = new Form();

$Form->begin_form( 'fform', $phpbb_tool_title.' - '.TB_('Step 5: Import topics') );
evo_flush();

$Form->add_crumb( 'phpbb' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'replies' );
$Form->hidden( 'ver', get_param( 'ver' ) );

if( $flush_action == 'topics' )
{
	$Form->begin_fieldset( TB_('Import log') );

	// Import the topics into the posts
	phpbb_import_topics();

	$Form->end_fieldset();
}

$Form->begin_fieldset( TB_('Report of the topics import') );

	$Form->info( TB_('Count of the imported topics'), '<b>'.(int)phpbb_get_var( 'topics_count_imported' ).'</b>' );

	$Form->info( TB_('Count of the imported forums'), (int)phpbb_get_var( 'forums_count_imported' ) );

	$Form->info( TB_('Count of the imported users'), (int)phpbb_get_var( 'users_count_imported' ) );

	$Form->info( TB_('Count of the updated users'), (int)phpbb_get_var( 'users_count_updated' ) );

	$Form->info( TB_('Count of the imported / missing avatars'), intval( phpbb_get_var( 'avatars_count_imported' ) ).' / <b class="red">'.intval( phpbb_get_var( 'avatars_count_missing' ) ).'</b>' );

	if( $phpbb_version == 3 )
	{	// Only for phpBB3:
		$Form->info( TB_('Count of the imported / missing attachments'), intval( phpbb_get_var( 'attachments_count_imported' ) ).' / <b class="red">'.intval( phpbb_get_var( 'attachments_count_missing' ) ).'</b>' );
	}

	$BlogCache = & get_BlogCache();
	$Collection = $Blog = & $BlogCache->get_by_ID( phpbb_get_var( 'blog_ID' ) );
	$Form->info( TB_('Collection'), $Blog->get( 'name' ), '' );

$Form->end_fieldset();

$Form->buttons( array( array( 'submit', 'submit', TB_('Continue').'!', 'SaveButton' )/*,
											 array( 'button', 'button', TB_('Back'), 'SaveButton', 'location.href=\''.$admin_url.'?ctrl=phpbbimport&step=forums\'' )*/ ) );

$Form->end_form();

?>
