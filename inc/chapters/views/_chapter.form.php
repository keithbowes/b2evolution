<?php
/**
 * This file implements the Chapter form
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Chapter
 */
global $edited_Chapter;

/**
 * @var ChapterCache
 */
global $ChapterCache;

global $Settings, $action, $subset_ID;

// Determine if we are creating or updating...
$creating = is_create_action( $action );

$Form = new Form( NULL, 'form_checkchanges' );

$close_url = get_chapter_redirect_url( get_param( 'redirect_page' ), $edited_Chapter->parent_ID, $edited_Chapter->ID );
$Form->global_icon( TB_('Cancel editing').'!', 'close', $close_url );

$Form->begin_form( 'fform', $creating ?  TB_('New category') : TB_('Category') );

$Form->add_crumb( 'element' );
$Form->hidden( 'action', $creating ? 'create' : 'update' );
$Form->hiddens_by_key( get_memorized( 'action' ) );

$Form->begin_fieldset( TB_('Properties').get_manual_link( 'category-edit-form' ) );

	// We're essentially double checking here...
	$edited_Blog = & $edited_Chapter->get_Blog();
	$move = '';
	if( $Settings->get('allow_moving_chapters') && ( ! $creating ) )
	{ // If moving cats between blogs is allowed:
		$move = ' '.action_icon( TB_('Move to a different blog...'), 'file_move', regenerate_url( 'action,cat_ID', 'cat_ID='.$edited_Chapter->ID.'&amp;action=move' ), TB_('Move') );
	}
	$Form->info( TB_('Collection'), $edited_Blog->get_maxlen_name().$move );

	$Form->select_input_options( 'cat_parent_ID',
				$ChapterCache->recurse_select( $edited_Chapter->parent_ID, $subset_ID, true, NULL, 0, array($edited_Chapter->ID) ), TB_('Parent category') );

	$Form->text_input( 'cat_name', $edited_Chapter->name, 40, TB_('Name'), '', array( 'required' => true, 'maxlength' => 255 ) );

	$Form->text_input( 'cat_urlname', $edited_Chapter->urlname, 40, TB_('URL "slug"'), TB_('Used for clean URLs. Must be unique.'), array( 'maxlength' => 255 ) );

	$cat_image_params = array( 'file_type' => 'image', 'max_file_num' => 1, 'window_title' => TB_('Select category image'), 'size_name' => 'fit-320x320' );
	$Form->fileselect( 'cat_image_file_ID', $edited_Chapter->get( 'image_file_ID' ), TB_('Category image'), NULL, $cat_image_params );

	$social_media_image_params = array( 'file_type' => 'image', 'max_file_num' => 1, 'window_title' => TB_('Select social media boilerplate image'), 'size_name' => 'fit-320x320' );
	$Form->fileselect( 'cat_social_media_image_file_ID', $edited_Chapter->get( 'social_media_image_file_ID' ), TB_('Social media boilerplate'), NULL, $social_media_image_params );

	$Form->text_input( 'cat_description', $edited_Chapter->description, 40, TB_('Description'), TB_('May be used as a title tag and/or meta description.'), array( 'maxlength' => 255 ) );

	$parent_cat_order = $edited_Chapter->get_parent_subcat_ordering();
	if( $parent_cat_order == 'manual' )
	{
		$Form->text_input( 'cat_order', $edited_Chapter->order, 5, TB_('Order'), TB_('For manual ordering of the categories.'), array( 'maxlength' => 11 ) );
	}

	$Form->radio_input( 'cat_subcat_ordering', $edited_Chapter->get( 'subcat_ordering' ), array(
					array( 'value'=>'parent', 'label'=>TB_('Same as parent') ),
					array( 'value'=>'alpha', 'label'=>TB_('Alphabetically') ),
					array( 'value'=>'manual', 'label'=>TB_('Manually') ),
			 ), TB_('Sort sub-categories') );

	$Form->checkbox_input( 'cat_meta', $edited_Chapter->meta, TB_('Meta category'), array( 'note' => TB_('If you check this box you will not be able to put any posts into this category.') ) );

	echo '<div id="cat_ityp_ID_selector"'.( $edited_Chapter->get( 'meta' ) ? ' style="display:none"' : '' ).'>';
	// Include "No default type" option only for not default category of the collection:
	$include_no_default_option = ( $edited_Chapter->ID == 0 || ( ( $cat_Blog = & $edited_Chapter->get_Blog() ) && $cat_Blog->get_default_cat_ID() != $edited_Chapter->ID ) );
	$item_type_options = collection_item_type_titles( $edited_Chapter->get( 'blog_ID' ), $edited_Chapter->get( 'ityp_ID' ), '', $include_no_default_option );
	$Form->select_input_array( 'cat_ityp_ID', $edited_Chapter->ityp_ID, $item_type_options, TB_('Default Item Type'), NULL, array( 'force_keys_as_values' => true ) );
	echo '</div>';

	$Form->checkbox_input( 'cat_lock', $edited_Chapter->lock, TB_('Locked category'), array( 'note' => TB_('Check this to lock all posts under this category. (Note: for posts with multiple categories, the post is only locked if *all* its categories are locked.)') ) );

$Form->end_fieldset();

$Form->end_form( array( array( 'submit', 'submit', ( $creating ? TB_('Record') : TB_('Save Changes!') ), 'SaveButton', 'data-shortcut' => 'ctrl+s,command+s,ctrl+enter,command+enter' ) ) );

?>
<script>
jQuery( '#cat_meta' ).click( function()
{	// Show/Hide selector of default Item Type depending on meta setting:
	if( jQuery( this ).prop( 'checked' ) )
	{
		jQuery( '#cat_ityp_ID_selector' ).hide();
	}
	else
	{
		jQuery( '#cat_ityp_ID_selector' ).show();
	}
} );
</script>
