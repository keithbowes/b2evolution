<?php
/**
 * This is the template that displays the item block
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template (or other templates)
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage bootstrap_forums
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Item, $preview, $dummy_fields, $cat, $app_version;

/**
 * @var array Save all statuses that used on this page in order to show them in the footer legend
 */
global $legend_statuses;

if( !is_array( $legend_statuses ) )
{ // Init this array only first time
	$legend_statuses = array();
}

// Default params:
$params = array_merge( array(
		'feature_block'      => false,
		'content_mode'       => 'auto',		// 'auto' will auto select depending on $disp-detail
		'item_class'         => 'evo_post',
		'item_type_class'    => 'evo_post__ptyp_',
		'item_status_class'  => 'evo_post__',
		'item_disp_class'    => NULL,
		'image_size'         => get_skin_setting( 'main_content_image_size', 'fit-1280x720' ),
	), $params );

// In this skin, it makes no sense to navigate in any different mode than "same category"
// Use the category from param
$current_cat = param( 'cat', 'integer', 0 );
if( $current_cat == 0 )
{ // Use main category by default because the category wasn't set
	$current_cat = $Item->main_cat_ID;
}

// Breadcrumbs
$cat = $current_cat;
skin_widget( array(
		// CODE for the widget:
		'widget' => 'breadcrumb_path',
		// Optional display params
		'block_start'           => '<ol class="breadcrumb">',
		'block_end'             => '</ol><div class="clear"></div>',
		'separator'             => '',
		'item_mask'             => '<li><a href="$url$">$title$</a></li>',
		'item_logo_mask'        => '<li>$logo$ <a href="$url$">$title$</a></li>',
		'item_active_logo_mask' => '<li class="active">$logo$ $title$</li>',
		'item_active_mask'      => '<li class="active">$title$</li>',
		'coll_logo_size'        => 'fit-128x16',
	) );
?>

<a name="top"></a>
<a name="p<?php echo $Item->ID; ?>"></a>

	<?php
		/* To be removed. Replaced by Item Next Previous widget in Item Single Header container:
		// Buttons to prev/next post on single disp
		if( !$Item->is_featured() )
		{
			// ------------------- PREV/NEXT POST LINKS (SINGLE POST MODE) -------------------
			item_prevnext_links( array(
					'block_start'     => '<ul class="pager col-lg-12 post_nav">',
					'prev_start'      => '<li class="previous">',
					'prev_text'       => '<span aria-hidden="true">&larr;</span> $title$',
					'prev_end'        => '</li>',
					'separator'       => ' ',
					'next_start'      => '<li class="next">',
					'next_text'       => '$title$ <span aria-hidden="true">&rarr;</span>',
					'next_end'        => '</li>',
					'block_end'       => '</ul>',
					'target_blog'     => $Blog->ID,	// this forces to stay in the same blog, should the post be cross posted in multiple blogs
					'post_navigation' => 'same_category', // force to stay in the same category in this skin
					'featured'        => false, // don't include the featured posts into navigation list
				) );
			// ------------------------- END OF PREV/NEXT POST LINKS -------------------------
		}
		*/
	?>

<div class="forums_list single_topic evo_content_block">
	<?php /* This empty row is used to fix columns width, when table has css property "table-layout:fixed" */
	if( $disp != 'page' )
	{
	?>

	<div class="single_page_title">
		<?php
		/* To be removed. Replaced by Item Title widget in Item Single Header container:
		// Page title
		$Item->title( array(
				'before'    => '<h2>',
				'after'     => '</h2>',
				'link_type' => 'permalink'
			) );
		*/

		// ------------------------- "Item Single - Header" CONTAINER EMBEDDED HERE --------------------------
		// Display container contents:
		widget_container( 'item_single_header', array(
			'widget_context'             => 'item',	// Signal that we are displaying within an Item
			// The following (optional) params will be used as defaults for widgets included in this container:
			'container_display_if_empty' => false, // If no widget, don't display container at all
			// This will enclose each widget in a block:
			'block_start'                => '<div class="evo_widget $wi_class$">',
			'block_end'                  => '</div>',
			// This will enclose the title of each widget:
			'block_title_start'          => '<h3>',
			'block_title_end'            => '</h3>',
			'author_link_text'           => $params['author_link_text'],
			// Controlling the title:
			'widget_item_title_params'  => array(
					'before'    => '<div class="evo_post_title">'.( in_array( $disp, array( 'single', 'page' ) ) ? '<h1>' : '<h2>' ),
					'after'     => ( in_array( $disp, array( 'single', 'page' ) ) ? '</h1>' : '</h2>' ).'</div>',
					'link_type' => 'permalink',
				),
			// Item Next Previous widget
			'widget_item_next_previous_params' => array(
					'target_blog'     => $Blog->ID,	// this forces to stay in the same blog, should the post be cross posted in multiple blogs
					'post_navigation' => 'same_category', // force to stay in the same category in this skin
					'featured'        => false, // don't include the featured posts into navigation list
				),
		) );
		// ----------------------------- END OF "Item Single - Header" CONTAINER -----------------------------

	?>
	</div>

	<?php } ?>

	<div class="row">
		<div class="evo_content_col <?php echo $Skin->get_column_class_forums( 'single' ); ?>">

	<section class="table evo_content_block<?php echo ' evo_voting_layout__'.$Skin->get_setting( 'voting_place' ); ?>">
	<div class="panel panel-default">
		<div class="panel-heading posts_panel_title_wrapper">
			<div class="cell1 ellipsis">
				<h4 class="evo_comment_title panel-title"><a href="<?php echo $Item->get_permanent_url(); ?>" class="badge badge-primary">1</a>
					<?php
						$Item->author( array(
							'link_text' => 'auto',
						) );
					?>
					<?php
						// Display the post date:
						$Item->issue_time( array(
								'before'      => '<span class="text-muted">',
								'after'       => '</span> &nbsp; &nbsp; ',
								'time_format' => locale_extdatefmt().' '.locale_shorttimefmt(),
							) );
					?>
				</h4>
			</div>
					<?php
						if( $Skin->enabled_status_banner( $Item->status ) )
						{ // Status banner
							echo '<div class="cell2">';
							$Item->format_status( array(
									'template' => '<div class="evo_status evo_status__$status$ badge pull-right" data-toggle="tooltip" data-placement="top" title="$tooltip_title$">$status_title$</div>',
								) );
							$legend_statuses[] = $Item->status;
							echo '</div>';
						}
					?>
		</div>

		<div class="panel-body">
			<div class="ft_avatar<?php echo $Skin->get_setting( 'voting_place' ) == 'under_content' ? ' col-md-1 col-sm-2' : ''; ?>"><?php
				if( $Skin->get_setting( 'voting_place' ) == 'left_score' )
				{	// Display voting panel instead of author avatar:
					$Skin->display_item_voting_panel( $Item, 'left_score' );
				}
				else
				{	// Display author avatar:
					$Item->author( array(
						'link_text'  => 'only_avatar',
						'thumb_size' => 'crop-top-80x80',
					) );
				}
			?></div>
			<div class="post_main<?php echo $Skin->get_setting( 'voting_place' ) == 'under_content' ? ' col-md-11 col-sm-10' : ''; ?>">
				<?php
				if( $disp == 'single' )
				{
					// ------------------------- "Item Single" CONTAINER EMBEDDED HERE --------------------------
					// Display container contents:
					widget_container( 'item_single', array(
						'widget_context' => 'item',	// Signal that we are displaying within an Item
						// The following (optional) params will be used as defaults for widgets included in this container:
						'container_display_if_empty' => false, // If no widget, don't display container at all
						// This will enclose each widget in a block:
						'block_start' => '<div class="evo_widget $wi_class$">',
						'block_end' => '</div>',
						// This will enclose the title of each widget:
						'block_title_start' => '<h3>',
						'block_title_end' => '</h3>',
						// Template params for "Item Link" widget
						'widget_item_link_before'    => '<p class="evo_post_link">',
						'widget_item_link_after'     => '</p>',
						// Template params for "Item Tags" widget
						'widget_item_tags_before'    => '<nav class="small post_tags">',
						'widget_item_tags_after'     => '</nav>',
						'widget_item_tags_separator' => ' ',
						// Params for skin file "_item_content.inc.php"
						'widget_item_content_params' => $params,
						// Template params for "Item Attachments" widget:
						'widget_item_attachments_params' => array(
								'limit_attach'       => 1000,
								'before'             => '<div class="evo_post_attachments"><h3>'.T_('Attachments').':</h3><ul class="evo_files">',
								'after'              => '</ul></div>',
								'before_attach'      => '<li class="evo_file">',
								'after_attach'       => '</li>',
								'before_attach_size' => ' <span class="evo_file_size">(',
								'after_attach_size'  => ')</span>',
							),
						// Template params for "Item Tags" widget
						'widget_item_tags_before'    => '<nav class="small post_tags">',
						'widget_item_tags_after'     => '</nav>',
					) );
					// ----------------------------- END OF "Item Single" CONTAINER -----------------------------
				}
				elseif( $disp == 'page' )
				{
					// ------------------------- "Item Page" CONTAINER EMBEDDED HERE --------------------------
					// Display container contents:
					widget_container( 'item_page', array(
						'widget_context' => 'item',	// Signal that we are displaying within an Item
						// The following (optional) params will be used as defaults for widgets included in this container:
						'container_display_if_empty' => false, // If no widget, don't display container at all
						// This will enclose each widget in a block:
						'block_start' => '<div class="evo_widget $wi_class$">',
						'block_end' => '</div>',
						// This will enclose the title of each widget:
						'block_title_start' => '<h3>',
						'block_title_end' => '</h3>',
						// Params for skin file "_item_content.inc.php"
						'widget_item_content_params' => $params,
						// Template params for "Item Attachments" widget:
						'widget_item_attachments_params' => array(
								'limit_attach'       => 1000,
								'before'             => '<div class="evo_post_attachments"><h3>'.T_('Attachments').':</h3><ul class="evo_files">',
								'after'              => '</ul></div>',
								'before_attach'      => '<li class="evo_file">',
								'after_attach'       => '</li>',
								'before_attach_size' => ' <span class="evo_file_size">(',
								'after_attach_size'  => ')</span>',
							),
					) );
					// ----------------------------- END OF "Item Page" CONTAINER -----------------------------
				}
				else
				{
					// ---------------------- POST CONTENT INCLUDED HERE ----------------------
					skin_include( '_item_content.inc.php', $params );
					// Note: You can customize the default item content by copying the generic
					// /skins/_item_content.inc.php file into the current skin folder.
					// -------------------------- END OF POST CONTENT -------------------------

					if( ! $Item->is_intro() )
					{ // List all tags attached to this topic:
						$Item->tags( array(
								'before'    => '<nav class="small post_tags">',
								'after'     => '</nav>',
								'separator' => ' ',
							) );
					}
				}
				?>
			</div>
		</div><!-- ../panel-body -->

		<div class="panel-footer clearfix small">
			<?php if( $disp != 'page' ) { ?>
			<a href="<?php echo $Item->get_permanent_url(); ?>#skin_wrapper" class="to_top"><?php echo T_('Back to top'); ?></a>
			<?php
			}
				// Check if BBcode plugin is enabled for current blog
				$bbcode_plugin_is_enabled = false;
				if( class_exists( 'bbcode_plugin' ) )
				{ // Plugin exists
					global $Plugins;
					$bbcode_Plugin = & $Plugins->get_by_classname( 'bbcode_plugin' );
					if( $bbcode_Plugin->status == 'enabled' && $bbcode_Plugin->get_coll_setting( 'coll_apply_comment_rendering', $Blog ) != 'never' )
					{ // Plugin is enabled and activated for comments
						$bbcode_plugin_is_enabled = true;
					}
				}
				if( $bbcode_plugin_is_enabled && $Item->can_comment( NULL ) )
				{	// Display button to quote this post
					echo '<a href="'.$Item->get_permanent_url().'?quote_post='.$Item->ID.'#form_p'.$Item->ID.'" title="'.format_to_output( T_('Reply with quote'), 'htmlattr' ).'" class="'.button_class( 'text' ).' pull-left quote_button">'.get_icon( 'comments', 'imgtag', array( 'title' => T_('Reply with quote') ) ).' '.T_('Quote').'</a>';
				}

				if( $disp != 'page' )
				{	// Display a panel with voting buttons for item:
					$Skin->display_item_voting_panel( $Item, 'under_content' );
				}

				echo '<span class="pull-left">';
					$Item->edit_link( array(
							'before' => ' ',
							'after'  => '',
							'title'  => T_('Edit this topic'),
							'text'   => '#',
							'class'  => button_class( 'text' ).' comment_edit_btn',
						) );
				echo '</span>';
				echo '<div class="action_btn_group">';
					$Item->edit_link( array(
							'before' => ' ',
							'after'  => '',
							'title'  => T_('Edit this topic'),
							'text'   => '#',
							'class'  => button_class( 'text' ).' comment_edit_btn',
						) );
					echo '<span class="'.button_class( 'group' ).'">';
					// Set redirect after publish to the same category view of the items permanent url
					$redirect_after_publish = $Item->add_navigation_param( $Item->get_permanent_url(), 'same_category', $current_cat );
					$Item->next_status_link( array( 'before' => ' ', 'class' => button_class( 'text' ), 'post_navigation' => 'same_category', 'nav_target' => $current_cat ), true );
					$Item->next_status_link( array( 'class' => button_class( 'text' ), 'before_text' => '', 'post_navigation' => 'same_category', 'nav_target' => $current_cat ), false );
					$Item->delete_link( '', '', '#', T_('Delete this topic'), button_class( 'text' ), false, '#', TS_('You are about to delete this post!\\nThis cannot be undone!'), get_caturl( $current_cat ) );
					echo '</span>';
				echo '</div>';
		?>

		</div><!-- ../panel-footer -->
	</div><!-- ../panel panel-default -->
	</section><!-- ../table evo_content_block -->
	<?php
		$Item->locale_temp_switch(); // Temporarily switch to post locale (useful for multilingual blogs)
	?>

	<?php
	if( is_single_page() )
	{	// Display comments only on single Item's page:
		// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
		skin_include( '_item_feedback.inc.php', array_merge( $params, array(
			'disp_comments'         => true,
			'disp_comment_form'     => true,
			'disp_trackbacks'       => true,
			'disp_trackback_url'    => true,
			'disp_pingbacks'        => true,
			'disp_webmentions'      => true,
			'disp_meta_comments'    => false,

			'disp_section_title'    => false,
			'disp_meta_comment_info' => false,

			'comment_post_before'   => '<br /><h4 class="evo_comment_post_title ellipsis">',
			'comment_post_after'    => '</h4>',

			'comment_title_before'  => '<div class="panel-heading posts_panel_title_wrapper"><div class="cell1 ellipsis"><h4 class="evo_comment_title panel-title">',
			'comment_status_before' => '</h4></div>',
			'comment_title_after'   => '</div>',

			'comment_avatar_before' => '<span class="evo_comment_avatar'.( $Skin->get_setting( 'voting_place' ) == 'under_content' ? ' col-md-1 col-sm-2' : '' ).'">',
			'comment_avatar_after'  => '</span>',
			'comment_text_before'   => '<div class="evo_comment_text'.( $Skin->get_setting( 'voting_place' ) == 'under_content' ? ' col-md-11 col-sm-10' : '' ).'">',
			'comment_text_after'    => '</div>',
		) ) );
		// Note: You can customize the default item feedback by copying the generic
		// /skins/_item_feedback.inc.php file into the current skin folder.

		echo_comment_moderate_js();

		// ---------------------- END OF FEEDBACK (COMMENTS/TRACKBACKS) ---------------------
	}
	?>

	<?php
	if( evo_version_compare( $app_version, '6.7' ) >= 0 )
	{	// We are running at least b2evo 6.7, so we can include this file:
		// ------------------ INTERNAL COMMENTS INCLUDED HERE ------------------
		skin_include( '_item_meta_comments.inc.php', array(
				'comment_start'         => '<article class="evo_comment evo_comment__meta panel panel-default">',
				'comment_end'           => '</article>',
				'comment_post_before'   => '<h4 class="evo_comment_post_title ellipsis">',
				'comment_post_after'    => '</h4>',
				'comment_title_before'  => '<div class="panel-heading posts_panel_title_wrapper"><div class="cell1 ellipsis"><h4 class="evo_comment_title panel-title">',
				'comment_status_before' => '</h4></div>',
				'comment_title_after'   => '</div>',
				'comment_avatar_before' => '<span class="evo_comment_avatar col-md-1 col-sm-2">',
				'comment_avatar_after'  => '</span>',
				'comment_text_before'   => '<div class="evo_comment_text col-md-11 col-sm-10">',
				'comment_text_after'    => '</div>',
			) );
		// ---------------------- END OF INTERNAL COMMENTS ---------------------
	}
	?>

		</div><!-- .col -->

		<?php
		if( $Skin->is_visible_sidebar_forums( false, 'single' ) )
		{	// Display sidebar:
			?>
			<aside class="evo_sidebar_col col-md-3<?php echo $Skin->get_setting_layout( 'single' ) == 'left_sidebar' ? ' pull-left-md' : '' ?>">
				<div id="evo_container__sidebar_single">
			<?php
				// ------------------------- "Sidebar Single" CONTAINER EMBEDDED HERE --------------------------
				// Display container contents:
				widget_container( 'sidebar_single', array(
						// The following (optional) params will be used as defaults for widgets included in this container:
						'container_display_if_empty' => false, // If no widget, don't display container at all
						'container_start' => '<div class="evo_container $wico_class$">',
						'container_end'   => '</div>',
						// This will enclose each widget in a block:
						'block_start' => '<div class="panel panel-default evo_widget $wi_class$">',
						'block_end' => '</div>',
						// This will enclose the title of each widget:
						'block_title_start' => '<div class="panel-heading"><h4 class="panel-title">',
						'block_title_end' => '</h4></div>',
						// This will enclose the body of each widget:
						'block_body_start' => '<div class="panel-body">',
						'block_body_end' => '</div>',
						// If a widget displays a list, this will enclose that list:
						'list_start' => '<ul>',
						'list_end' => '</ul>',
						// This will enclose each item in a list:
						'item_start' => '<li>',
						'item_end' => '</li>',
						// This will enclose sub-lists in a list:
						'group_start' => '<ul>',
						'group_end' => '</ul>',
						// This will enclose (foot)notes:
						'notes_start' => '<div class="notes">',
						'notes_end' => '</div>',
						// Widget 'Search form':
						'search_class'         => 'compact_search_form',
						'search_input_before'  => '<div class="input-group">',
						'search_input_after'   => '',
						'search_submit_before' => '<span class="input-group-btn">',
						'search_submit_after'  => '</span></div>',
						// Widget 'Item Custom Fields':
						'custom_fields_table_start'                => '<div class="item_custom_fields">',
						'custom_fields_row_start'                  => '<div class="row"$row_attrs$>',
						'custom_fields_topleft_cell'               => '<div class="col-md-12 col-xs-6" style="border:none"></div>',
						'custom_fields_col_header_item'            => '<div class="$col_class$ col-md-12 col-xs-6 center" width="$col_width$"$col_attrs$>$item_link$$item_status$</div>',  // Note: we will also add reverse view later: 'custom_fields_col_header_field
						'custom_fields_row_header_field'           => '<div class="col-md-12 col-xs-6"><b>$field_title$$field_description_icon$:</b></div>',
						'custom_fields_item_status_template'       => '<div><div class="evo_status evo_status__$status$ badge" data-toggle="tooltip" data-placement="top" title="$tooltip_title$">$status_title$</div></div>',
						'custom_fields_description_icon_class'     => 'grey',
						'custom_fields_value_default'              => '<div class="col-md-12 col-xs-6"$data_cell_attrs$>$field_value$</div>',
						'custom_fields_value_difference_highlight' => '<div class="col-md-12 col-xs-6 bg-warning"$data_cell_attrs$>$field_value$</div>',
						'custom_fields_value_green'                => '<div class="col-md-12 col-xs-6 bg-success"$data_cell_attrs$>$field_value$</div>',
						'custom_fields_value_red'                  => '<div class="col-md-12 col-xs-6 bg-danger"$data_cell_attrs$>$field_value$</div>',
						'custom_fields_edit_link_cell'             => '<div class="col-md-12 col-xs-6 center"$edit_link_attrs$>$edit_link$</div>',
						'custom_fields_edit_link_class'            => 'btn btn-xs btn-default',
						'custom_fields_row_end'                    => '</div>',
						'custom_fields_table_end'                  => '</div>',
						// Separate template for separator fields:
						// (Possible to use templates for all field types: 'numeric', 'string', 'html', 'text', 'url', 'image', 'computed', 'separator')
						'custom_fields_separator_row_header_field' => '<div class="col-xs-12" colspan="$cols_count$"><b>$field_title$$field_description_icon$</b></div>',
					) );
				// ----------------------------- END OF "Sidebar Single" CONTAINER -----------------------------
			?>
				</div>
			</aside>
			<?php
		} ?>
	</div><!-- .row -->

</div><!-- ../forums_list single_topic -->

<?php
	locale_restore_previous();	// Restore previous locale (Blog locale)
	expose_var_to_js( 'evo_skin_bootstrap_forum__quote_button_click', true );
?>
