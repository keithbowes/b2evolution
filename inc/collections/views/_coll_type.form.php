<?php
/**
 * This file implements the UI view for the blog type.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog;


global $action, $next_action, $blogtemplate, $blog, $tab, $admin_url;

$Form = new Form();

$Form->begin_form( 'fform' );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'update_type' );
$Form->hidden( 'tab', $tab );
$Form->hidden( 'blog', $blog );


$Form->begin_fieldset( TB_('Collection type').get_manual_link('collection-change-type') );

	$collection_kinds = get_collection_kinds();
	$radio_options = array();
	foreach( $collection_kinds as $kind_value => $kind )
	{
		$radio_options[] = array( $kind_value, $kind['name'], $kind['desc'] );
	}
	$Form->radio( 'type', $edited_Blog->get( 'type' ), $radio_options, TB_('Type'), true );

	$Form->checkbox_input( 'reset', 0, TB_('Reset'), array(
			'input_suffix' => ' '.TB_('Reset all Widgets, Skin settings and Plugins settings as for a new collection.'),
			'note' => TB_('(Only keep collection name, owner, URL, categories, content, users and groups permissions, features and collection settings).')
		) );

$Form->end_fieldset();


$Form->buttons( array( array( 'submit', 'submit', TB_('Save Changes!'), 'SaveButton' ) ) );

$Form->end_form();

?>
