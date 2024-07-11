<?php
/**
 * This file implements the post browsing
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

/**
 * @var Blog
 */
global $Collection, $Blog;
/**
 * @var ItemList2
 */
global $ItemList;
/**
 * Note: definition only (does not need to be a global)
 * @var Item
 */
global $Item;

global $action, $blog, $posts, $poststart, $postend, $ReqURI;
global $edit_item_url, $delete_item_url, $p, $dummy_fields;
global $comment_allowed_tags, $comment_type;
global $Plugins, $DB, $UserSettings, $Session, $Messages;

$highlight = param( 'highlight', 'integer', NULL );

// Run the query:
$ItemList->query();

// Old style globals for category.funcs:
global $postIDlist;
$postIDlist = $ItemList->get_page_ID_list();
global $postIDarray;
$postIDarray = $ItemList->get_page_ID_array();


// Display a panel to confirm mass action with selected items:
display_mass_items_confirmation_panel();


$block_item_Widget = new Widget( 'block_item' );

// This block is used to keep correct css style for the post status banners
echo '<div class="evo_content_block">';

if( $action == 'view' )
{ // We are displaying a single post:
	echo '<div class="global_actions">'
			.action_icon( T_('Close post'), 'close', regenerate_url( 'p,action', 'filter=restore&amp;highlight='.$p ),
				NULL, NULL, NULL, array( 'class' => 'action_icon btn btn-default' ) )
		.'</div>';

	// Initialize things in order to be ready for displaying.
	$display_params = array(
					'header_start' => '',
						'header_text_single' => '',
					'header_end' => '',
					'footer_start' => '',
						'footer_text_single' => '',
					'footer_end' => '',
					'disp_rating_summary'  => true,
				);
}
else
{ // We are displaying multiple posts ( Not a single post! )
	$block_item_Widget->title = T_('Posts Browser').get_manual_link( 'browse-edit-tab' );

	// Generate global icons depending on seleted tab with item type
	item_type_global_icons( $block_item_Widget );

	$block_item_Widget->disp_template_replaced( 'block_start' );

	// --------------------------------- START OF CURRENT FILTERS --------------------------------
	skin_widget( array(
			// CODE for the widget:
			'widget' => 'coll_current_filters',
			// Optional display params
			'ItemList'             => $ItemList,
			'block_start'          => '',
			'block_end'            => '',
			'block_title_start'    => '<b>',
			'block_title_end'      => ':</b> ',
			'show_filters'         => array( 'time' => 1, 'visibility' => 1, 'itemtype' => 1 ),
			'display_button_reset' => false,
			'display_empty_filter' => true,
		) );
	// ---------------------------------- END OF CURRENT FILTERS ---------------------------------

	$block_item_Widget->disp_template_replaced( 'block_end' );

	global $AdminUI;
	$admin_template = $AdminUI->get_template( 'Results' );

	// Initialize things in order to be ready for displaying.
	$display_params = array(
			'header_start' => $admin_template['header_start'],
			'footer_start' => $admin_template['footer_start'],
		);
}

$ItemList->display_init( $display_params );

// Display navigation:
$ItemList->display_nav( 'header' );

$allow_items_list_form = ( $action != 'view' && $ItemList->total_rows > 0 && check_user_perm( 'blog_post_statuses', 'edit', false, $blog ) );
if( $allow_items_list_form )
{	// Allow to select item for action only on items list if current user can edit at least one item status:
	global $admin_url;

	$Form = new Form( $admin_url );

	$Form->begin_form();
	$Form->hidden( 'ctrl', 'items' );
	$Form->hidden( 'tab', get_param( 'tab' ) );
	$Form->hidden( 'blog', $blog );
	$Form->hidden( 'page', $ItemList->page );
	$Form->add_crumb( 'items' );
}

/*
 * Display posts:
 */
while( $Item = & $ItemList->get_item() )
{
	?>
	<div id="<?php $Item->anchor_id() ?>" class="panel panel-default evo_post evo_post__status_<?php $Item->status_raw() ?>" lang="<?php $Item->lang() ?>">
		<?php
		// We don't switch locales in the backoffice, since we use the user pref anyway
		// Load item's creator user:
		$Item->get_creator_User();
		?>
		<div class="panel-heading small<?php echo ( $Item->ID == $highlight ? ' evo_highlight' : '' ); ?>">
			<h3 class="bTitle"><?php
				$Item->title( array(
						'target_blog' => '',
						'link_type'   => $Item->can_be_displayed() ? 'auto' : 'none',
					) );
			?></h3>
			<?php
				echo '<div class="pull-right text-right">';
				$Item->permanent_link( array(
						'before' => '',
						'text'   => get_icon( 'permalink' ).' '.T_('Permalink'),
						'after'  => ' '.action_icon( T_('Copy Item Slug to the clipboard.'), 'clipboard-copy', '#',
							NULL, NULL, NULL, array( 'class' => 'small clipboard-copy', 'data-clipboard-text' => $Item->urltitle, 'onclick' => 'return false;' ) ),
					) );
				// Item slug control:
				$Item->tinyurl_link( array(
						'before' => ' - '.T_('Short').': ',
						'after'  => ''
					) );
				global $admin_url;
				if( check_user_perm( 'slugs', 'view' ) )
				{ // user has permission to view slugs:
					echo '&nbsp;'.action_icon( T_('Edit slugs').'...', 'edit', $admin_url.'?ctrl=slugs&amp;slug_item_ID='.$Item->ID,
						NULL, NULL, NULL, array( 'class' => 'small' ) );
				}
				echo '<br>';
				echo T_('Item ID').': '.$Item->ID;
				if( $parent_Item = $Item->get_parent_Item() )
				{	// Display parent ID if the Item has it:
					echo ' &middot; '.T_('Parent ID').': ';
					if( check_user_perm( 'item_post!CURSTATUS', 'view', false, $parent_Item ) )
					{	// Display parent ID as link to view the parent post if current user has a permission:
						echo '<a href="'.$admin_url.'?ctrl=items&amp;blog='.$parent_Item->get_blog_ID().'&amp;p='.$parent_Item->ID.'" title="'.$parent_Item->dget( 'title', 'htmlattr' ).'">'.$parent_Item->ID.'</a>';
					}
					else
					{	// Display parent ID as text if current user has a permission to view the parent post:
						echo $parent_Item->ID;
					}
				}
				echo '<br>';
				echo $Item->get( 'locale' ).' ';
				$Item->locale_flag( array(' class' => 'flagtop' ) );
				echo '</div>';

				if( $action != 'view' && check_user_perm( 'item_post!CURSTATUS', 'edit', false, $Item ) )
				{	// Display checkbox to select item for action only on items list:
					echo '<input type="checkbox" name="selected_items[]" value="'.$Item->ID.'" /> ';
				}

				$Item->issue_date( array(
						'before'      => '<span class="bDate">',
						'after'       => '</span>',
						'date_format' => '#',
					) );

				$Item->issue_time( array(
						'before'      => ' @ <span class="bTime">',
						'after'       => '</span>',
						'time_format' => '#short_time',
					) );

				// TRANS: backoffice: each post is prefixed by "date BY author IN categories"
				echo ' ', T_('by'), ' ', $Item->creator_User->get_identity_link( array( 'link_text' => 'name' ) );

				// Last modified date:
				echo ' <span class="text-nowrap">&middot; '.T_('Last modified').': '
					.mysql2date( locale_datefmt().' @ '.locale_timefmt(), $Item->get( 'datemodified' ) ).'</span>';

				// Last touched date:
				echo ' <span class="text-nowrap">&middot; '.T_('Last touched').': '
					.mysql2date( locale_datefmt().' @ '.locale_timefmt(), $Item->get( 'last_touched_ts' ) ).'</span>';

				// Contents updated date:
				echo ' <span class="text-nowrap">&middot; '.T_('Contents updated').': '
					.mysql2date( locale_datefmt().' @ '.locale_timefmt(), $Item->get( 'contents_last_updated_ts' ) )
					.$Item->get_refresh_contents_last_updated_link()
					.$Item->get_refresh_contents_last_updated_link( array(
							'title' => T_('Reset the "contents last updated" date to the date of the latest reply on this thread'),
							'type'  => 'created',
						) )
					.'</span>';

				echo '<br />';
				$Item->type( T_('Type').': <span class="bType">', '</span> &#160; ' );

				if( $Blog->get_setting( 'use_workflow' ) )
				{ // Only display workflow properties, if activated for this blog.
					$Item->priority( T_('Priority').': <span class="bPriority">', '</span> &#160; ' );
					$Item->assigned_to( T_('Assigned to').': <span class="bAssignee">', '</span> &#160; ' );
					$Item->extra_status( T_('Task Status').': <span class="bExtStatus">', '</span> &#160; ' );
					if( $Blog->get_setting( 'use_deadline' ) && ! empty( $Item->datedeadline ) )
						echo T_('Deadline').': <span class="bDate">';
						$Item->deadline_date();
						echo ' ';
						$Item->deadline_time();
						echo '</span>';
					}
				}
				echo '&#160;';

				echo '<br />';

				$Item->categories( array(
					'before'          => T_('Categories').': <span class="bCategories">',
					'after'           => '</span>',
					'include_main'    => true,
					'include_other'   => true,
					'include_external'=> true,
					'link_categories' => false,
					'show_locked'     => true,
					'before_main'     => '<b>',
					'after_main'      => '</b>',
				) );

				$order_cat_ID = ( isset( $ItemList->filters['cat_array'] ) && count ( $ItemList->filters['cat_array'] ) == 1 ) ? $ItemList->filters['cat_array'][0] : NULL;
				$item_order = $Item->get_order( $order_cat_ID );
				if( $item_order !== NULL )
				{
					echo ' &middot; '.T_('Order').': '.$item_order;
				}

				// Action buttons:
				echo '<div class="clearfix"></div>';

				// Edit : Propose change | Duplicate... | Merge with...
				$edit_buttons = array();
				if( $item_edit_url = $Item->get_edit_url() )
				{	// Edit
					$edit_buttons[] = array(
						'url'  => $item_edit_url,
						'text' => get_icon( 'edit_button' ).' '.T_('Edit'),
						'shortcut' => 'f2,ctrl+f2',
					);
				}
				if( $item_propose_change_url = $Item->get_propose_change_url() )
				{	// Propose change
					$edit_buttons[] = array(
						'url'  => $item_propose_change_url,
						'text' => get_icon( 'edit_button' ).' '.T_('Propose change'),
					);
				}
				if( $item_copy_url = $Item->get_copy_url() )
				{	// Duplicate...
					$edit_buttons[] = array(
						'url'  => $item_copy_url,
						'text' => get_icon( 'copy' ).' '.T_('Duplicate...'),
					);
				}
				if( $item_merge_click_js = $Item->get_merge_click_js( $params ) )
				{	// Merge with...
					$edit_buttons[] = array(
						'onclick' => $item_merge_click_js,
						'text'    => get_icon( 'merge' ).' '.T_('Merge with...'),
					);
					echo_item_merge_js();
				}
				$edit_buttons_num = count( $edit_buttons );
				if( $edit_buttons_num > 1 )
				{	// Display buttons in dropdown style:
					echo '<div class="'.button_class( 'group' ).'">';
					echo '<a href="'.$edit_buttons[0]['url'].'" class="'.button_class( 'text_primary' ).'"'
							.( isset( $edit_buttons[0]['shortcut'] ) ? ' data-shortcut="'.$edit_buttons[0]['shortcut'].'"' : '' ).'>'
							.$edit_buttons[0]['text'].'</a>';
					echo '<button type="button" class="'.button_class( 'text' ).' dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="caret"></span></button>';
					echo '<ul class="dropdown-menu">';
					for( $b = 1; $b < $edit_buttons_num; $b++ )
					{
						echo '<li><a href="'.( empty( $edit_buttons[ $b ]['url'] ) ? '#' : $edit_buttons[ $b ]['url'] ).'"'
								.( empty( $edit_buttons[ $b ]['onclick'] ) ? '' : ' onclick="'.$edit_buttons[ $b ]['onclick'].'"' ).'>'
								.$edit_buttons[ $b ]['text']
							.'</a></li>';
					}
					echo '</ul></div>';
				}
				elseif( $edit_buttons_num == 1 )
				{	// Display single button:
					echo '<span class="'.button_class( 'group' ).'">';
					echo '<a href="'.$edit_buttons[0]['url'].'" class="'.button_class( 'text_primary' ).'"'
							.( isset( $edit_buttons[0]['shortcut'] ) ? ' data-shortcut="'.$edit_buttons[0]['shortcut'].'"' : '' ).'>'
							.$edit_buttons[0]['text'].'</a>';
					echo '</span>';
				}

				// Details | History | Comments
				echo '<span class="'.button_class( 'group' ).'">';
				if( $action != 'view' )
				{
					echo '<a href="?ctrl=items&amp;blog='.$Blog->ID.'&amp;p='.$Item->ID.'" class="'.button_class( 'text' ).'">'.get_icon( 'magnifier' ).' '.T_('Details').'</a>';
				}

				echo $Item->get_history_link( array(
						'class'     => button_class( $Item->has_proposed_change() ? 'text_warning' : 'text' ),
						'link_text' => '$icon$ '.T_('Changes'),
					) );

				if( $Blog->get_setting( 'allow_comments' ) != 'never' )
				{
					echo '<a href="?ctrl=items&amp;blog='.$Blog->ID.'&amp;p='.$Item->ID.'#comments" class="'.button_class( 'text' ).'">';
					$comments_number = generic_ctp_number( $Item->ID, 'comments', 'total', true );
					echo get_icon( $comments_number > 0 ? 'comments' : 'nocomment' ).' ';
					// TRANS: Link to comments for current post
					comments_number( T_('no comment'), T_('1 comment'), T_('%d comments'), $Item->ID );
					load_funcs('comments/_trackback.funcs.php'); // TODO: use newer call below
					trackback_number('', ' &middot; '.T_('1 Trackback'), ' &middot; '.T_('%d Trackbacks'), $Item->ID);
					echo '</a>';
				}
				echo '</span>';

				// Status | Delete
				echo '<span class="'.button_class( 'group' ).'"> ';
				// Display the moderate buttons if current user has the rights:
				$status_link_params = array(
						'class'       => button_class( 'text' ),
						'redirect_to' => regenerate_url( '', '&highlight='.$Item->ID.'#item_'.$Item->ID, '', '&' ),
					);
				$Item->next_status_link( $status_link_params, true );
				$Item->next_status_link( $status_link_params, false );

				$next_status_in_row = $Item->get_next_status( false );
				if( $next_status_in_row && $next_status_in_row[0] != 'deprecated' )
				{ // Display deprecate button if current user has the rights:
					$Item->deprecate_link( '', '', get_icon( 'move_down_grey', 'imgtag', array( 'title' => '' ) ), '#', button_class() );
				}

				// Display delete button if current user has the rights:
				$Item->delete_link( '', ' ', '#', '#', button_class( 'text' ), false );
				echo '</span>';
			?>
		</div>

		<div class="panel-body">
			<?php
				$Item->format_status( array(
						'template' => '<div class="pull-right"><span class="note status_$status$" data-toggle="tooltip" data-placement="top" title="$tooltip_title$"><span>$status_title$</span></span></div>',
					) );
			?>

			<?php
				// Display images that are linked to this post:
				$Item->images( array(
						'before'              => '<div class="evo_post_images">',
						'before_image'        => '<figure class="evo_image_block">',
						'before_image_legend' => '<figcaption class="evo_image_legend">',
						'after_image_legend'  => '</figcaption>',
						'after_image'         => '</figure>',
						'after'               => '</div>',
						'image_size'          => 'fit-320x320',
						'before_gallery'      => '<div class="evo_post_gallery">',
						'after_gallery'       => '</div>',
						'gallery_table_start' => '',
						'gallery_table_end'   => '',
						'gallery_row_start'   => '',
						'gallery_row_end'     => '',
						'gallery_cell_start'  => '<div class="evo_post_gallery__image">',
						'gallery_cell_end'    => '</div>',
						'gallery_image_limit' => 1000,
						'gallery_link_rel'    => 'lightbox[p'.$Item->ID.']',
						// Optionally restrict to files/images linked to specific position: 'teaser'|'teaserperm'|'teaserlink'|'aftermore'|'inline'|'cover'|'background'
						'restrict_to_image_position' => 'cover,background,background,teaser,teaserperm,teaserlink',
					) );
			?>

			<div class="bText">
				<?php
					// Display CONTENT:
					$Item->content_teaser( array(
							'before'              => '',
							'after'               => '',
							'before_image'        => '<figure class="evo_image_block">',
							'before_image_legend' => '<figcaption class="evo_image_legend">',
							'after_image_legend'  => '</figcaption>',
							'after_image'         => '</figure>',
							'image_size'          => 'fit-320x320',
							'before_gallery'      => '<div class="evo_post_gallery">',
							'after_gallery'       => '</div>',
							'gallery_table_start' => '',
							'gallery_table_end'   => '',
							'gallery_row_start'   => '',
							'gallery_row_end'     => '',
							'gallery_cell_start'  => '<div class="evo_post_gallery__image">',
							'gallery_cell_end'    => '</div>',
							'gallery_image_limit' => 1000,
							'gallery_link_rel'    => 'lightbox[p'.$Item->ID.']',
						) );
					$Item->more_link();
					$Item->content_extension( array(
							'before'      => '',
							'after'       => '',
						) );

					// Links to post pages (for multipage posts):
					$Item->page_links( array(
							'separator' => ' &middot; ',
						) );
				?>
			</div>

		</div>

		<?php
			// List all tags attached to this post:
			$Item->tags( array(
					'url' =>            regenerate_url( 'tag' ),
					'before' =>         '<div class="panel-body small text-muted evo_post__tags">'.T_('Tags').': ',
					'after' =>          '</div>',
					'separator' =>      ', ',
				) );

		// _____________________________________ Displayed in SINGLE VIEW mode only _____________________________________
	?>
	</div>
	<?php
		if( $action == 'view' )
		{ // We are looking at a single post, include files and comments:

			if( $comment_type == 'meta' && ! $Item->can_see_meta_comments() )
			{ // Current user cannot views internal comments
				$comment_type = 'feedback';
			}

			if( isset($GLOBALS['files_Module']) )
			{ // Files:
				echo '<div class="evo_post__attachments">';	// TODO

				/**
				 * Needed by file display funcs
				 * @var Item
				 */
				global $LinkOwner;
				$LinkOwner = new LinkItem( $Item );
				require $inc_path.'links/views/_link_list.inc.php';
				echo '</div>';
			}


			// ---------- comments ----------

			// Actions "Recycle bin" and "Refresh"
			echo '<div class="feedback-actions">';
			echo action_icon( T_('Refresh comment list'), 'refresh', url_add_param( $admin_url, 'ctrl=items&amp;blog='.$blog.'&amp;p='.$Item->ID.'#comments' ),
					' '.T_('Refresh'), 3, 4, array(
						'onclick' => 'startRefreshComments( \''.request_from().'\', '.$Item->ID.', 1, \''.$comment_type.'\' ); return false;',
						'class'   => 'btn btn-default'
					) );
			if( $comment_type != 'meta' )
			{ // Don't display "Recycle bin" link for internal comments, because they are deleted directly without recycle bin
				echo get_opentrash_link( true, false, array(
						'before' => ' <span id="recycle_bin">',
						'after' => '</span>',
						'class' => 'btn btn-default'
					) );
			}
			echo '</div>';

			if( $Item->can_see_meta_comments() )
			{ // Display tabs to switch between user and internal comments Only if current user can views internal comments
				$switch_comment_type_url = $admin_url.'?ctrl=items&amp;blog='.$blog.'&amp;p='.$Item->ID;
				$metas_count = generic_ctp_number( $Item->ID, 'metas', 'total', true );
				$switch_comment_type_tabs = array(
						'feedback' => array(
							'url'   => $switch_comment_type_url.'&amp;comment_type=feedback#comments',
							'title' => T_('User comments').' <span class="badge">'.generic_ctp_number( $Item->ID, 'feedbacks', 'total', true ).'</span>' ),
						'meta' => array(
							'url'   => $switch_comment_type_url.'&amp;comment_type=meta#comments',
							'title' => T_('Internal comments').' <span class="badge'.( $metas_count > 0 ? ' badge-important' : '' ).'">'.$metas_count.'</span>' )
					);
				?>
				<div class="feedback-tabs btn-group">
				<?php
					foreach( $switch_comment_type_tabs as $comment_tab_type => $comment_tab )
					{
						echo '<a href="'.$comment_tab['url'].'" class="btn'.( $comment_type == $comment_tab_type ? ' btn-primary' : ' btn-default' ).'">'.$comment_tab['title'].'</a>';
					}
				?>
				</div>
				<?php
			}

			echo '<div class="clearfix"></div>';

			$comment_moderation_statuses = explode( ',', $Blog->get_setting( 'moderation_statuses' ) );

			$currentpage = param( 'currentpage', 'integer', 1 );
			$total_comments_number = generic_ctp_number( $Item->ID, ( $comment_type == 'meta' ? 'metas' : 'total' ), 'total', true );
			$moderation_comments_number = generic_ctp_number( $Item->ID, ( $comment_type == 'meta' ? 'metas' : 'total' ), $comment_moderation_statuses, true );
			// Decide to show all comments, or only which require moderation:
			if( ( $comment_type != 'meta' ) && // Display all comments in meta mode by default
			    ( $total_comments_number > 5 && $moderation_comments_number > 0 ) )
			{	// Show only requiring moderation comments:
				$statuses = $comment_moderation_statuses;
				$show_comments = 'moderation';
				param( 'comments_number', 'integer', $moderation_comments_number );
			}
			else
			{	// Show all comments:
				$statuses = get_visibility_statuses( 'keys', array( 'redirected', 'trash' ) );
				$show_comments = 'all';
				param( 'comments_number', 'integer', $total_comments_number );
			}

			$show_comments_expiry = param( 'show_comments_expiry', 'string', 'active', false, true );
			$expiry_statuses = array( 'active' );
			if( $show_comments_expiry == 'all' )
			{ // Display also the expired comments
				$expiry_statuses[] = 'expired';
			}

			// We do not want to comment actions use new redirect
			param( 'save_context', 'boolean', false );
			param( 'redirect_to', 'url', url_add_param( $admin_url, 'ctrl=items&amp;blog='.$blog.'&p='.$Item->ID, '&' ), false, true );
			param( 'item_id', 'integer', $Item->ID );
			param( 'show_comments', 'string', $show_comments, false, true );
			$comment_reply_ID = param( 'reply_ID', 'integer', 0 );

			// Display status filter
			?>
			<div class="evo_post__comments">
			<a id="comments"></a>
			<?php
			if( $display_params['disp_rating_summary'] )
			{ // Display a ratings summary
				echo $Item->get_rating_summary();
			}

			if( $comment_type != 'meta' )
			{ // Display this filter only for not internal comments
				?>
				<div class="tile"><label><?php echo T_('Show').':' ?></label></div>
				<div class="tile">
					<input type="radio" name="show_comments" value="moderation" id="only_moderation" class="radio" <?php if( $show_comments == 'moderation' ) echo 'checked="checked" '?> />
					<label for="only_moderation"><?php echo T_('Requiring moderation') ?></label>
				</div>
				<div class="tile">
					<input type="radio" name="show_comments" value="valid" id="only_valid" class="radio" <?php if( $show_comments == 'valid' ) echo 'checked="checked" '?> />
					<label for="only_valid"><?php echo T_('Valid') ?></label>
				</div>
				<div class="tile">
					<input type="radio" name="show_comments" value="all" id="show_all" class="radio" <?php if( $show_comments == 'all' ) echo 'checked="checked" '?> />
					<label for="show_all"><?php echo T_('All comments') ?></label>
				</div>
				<?php
				$expiry_delay = $Item->get_setting( 'comment_expiry_delay' );
				if( ! empty( $expiry_delay ) )
				{ // A filter to display even the expired comments
				?>
				<div class="tile">
					&#160; | &#160;
					<input type="radio" name="show_comments_expiry" value="expiry" id="show_expiry_delay" class="radio" <?php if( $show_comments_expiry == 'active' ) echo 'checked="checked" '?> />
					<label for="show_expiry_delay"><?php echo get_duration_title( $expiry_delay ); ?></label>
				</div>
				<div class="tile">
					<input type="radio" name="show_comments_expiry" value="all" id="show_expiry_all" class="radio" <?php if( $show_comments_expiry == 'all' ) echo 'checked="checked" '?> />
					<label for="show_expiry_all"><?php echo T_('All comments') ?></label>
				</div>
				<?php
				}
			}

			// Display comments of the viewed Item:
			echo '<div id="comments_container" value="'.$Item->ID.'" class="evo_comments_container">';
			echo_item_comments( $blog, $Item->ID, $statuses, $currentpage, NULL, array(), '', $expiry_statuses, $comment_type );
			echo '</div>';

			if( ( $comment_type == 'meta' && $Item->can_meta_comment() ) // User can add internal comment on the Item
			    || $Item->can_comment() ) // User can add standard comment
			{

			// Try to get a previewed Comment and check if it is for current viewed Item:
			$preview_Comment = get_comment_from_session( 'preview', $comment_type );
			$preview_Comment = ( empty( $preview_Comment ) || $preview_Comment->item_ID != $Item->ID ) ? false : $preview_Comment;

			if( $preview_Comment )
			{	// If preview comment is displayed currently

				if( empty( $comment_reply_ID ) || empty( $preview_Comment->in_reply_to_cmt_ID ) )
				{	// Display a previewed comment under all comments only if it is not replied on any other comment:
					echo '<div class="evo_comments_container">';
					echo_comment( $preview_Comment );
					echo '</div>';
				}

				// Display the error message again after preview of comment:
				$Messages->add( T_('This is a preview only! Do not forget to send your comment!'), 'error' );
				$Messages->display();
			}

			?>
			<!-- ========== FORM to add a comment ========== -->
			<h4><?php echo $comment_type == 'meta' ? T_('Leave an internal comment') : T_('Leave a comment'); ?>:</h4>

			<?php

			if( $preview_Comment )
			{	// Get a Comment properties from preview request:
				$Comment = $preview_Comment;

				// Form fields:
				$comment_content = $Comment->original_content;
				// All file IDs that have been attached:
				$comment_attachments = $Comment->preview_attachments;
				// All attachment file IDs which checkbox was checked in:
				$checked_attachments = $Comment->checked_attachments;
				// Get what renderer checkboxes were selected on form:
				$comment_renderers = explode( '.', $Comment->get( 'renderers' ) );
			}
			else
			{	// Create new Comment:
				if( ( $Comment = get_comment_from_session( 'unsaved', $comment_type ) ) === NULL )
				{	// There is no saved Comment in Session
					$Comment = new Comment();
					$Comment->set( 'type', $comment_type );
					$Comment->set( 'item_ID', $Item->ID );
					$comment_attachments = '';
					$checked_attachments = '';
				}
				else
				{	// Get params from Session:
					// comment_attachments contains all file IDs that have been attached
					$comment_attachments = $Comment->preview_attachments;
					// checked_attachments contains all attachment file IDs which checkbox was checked in
					$checked_attachments = $Comment->checked_attachments;
				}
				$comment_content = $Comment->get( 'content' );
				$comment_renderers = $Comment->get_renderers();
			}

			$Form = new Form( get_htsrv_url().'comment_post.php', 'comment_checkchanges', 'post', NULL, 'multipart/form-data' );

			$Form->begin_form( 'evo_form evo_form__comment '.( $comment_type == 'meta' ? ' evo_form__comment_meta' : '' ) );

			if( ! empty( $comment_reply_ID ) )
			{
				$Form->hidden( 'reply_ID', $comment_reply_ID );
				// Display a link to scroll back up to replying comment:
				echo '<a href="'.$admin_url.'?ctrl=items&amp;blog='.$Item->Blog->ID.'&amp;p='.$Item->ID.'&amp;reply_ID='.$comment_reply_ID.'#c'.$comment_reply_ID.'" class="comment_reply_current" rel="'.$comment_reply_ID.'">'.T_('You are currently replying to a specific comment').'</a>';
			}

			if( $comment_type == 'meta' )
			{
				echo '<b class="form_info">'.T_('Please remember: this comment will be included in a private discussion view and <u>only will be visible to other admins</u>').'</b>';
			}

			$Form->add_crumb( 'comment' );
			$Form->hidden( 'comment_item_ID', $Item->ID );
			$Form->hidden( 'comment_type', $comment_type );
			$Form->hidden( 'redirect_to', $admin_url.'?ctrl=items&blog='.$Item->Blog->ID.'&p='.$Item->ID.'&comment_type='.$comment_type );

			if( $comment_type != 'meta' && $Item->can_rate() )
			{	// Comment rating:
				ob_start();
				$Comment->rating_input( array( 'item_ID' => $Item->ID ) );
				$comment_rating = ob_get_clean();
				$Form->info_field( T_('Your vote'), $comment_rating );
			}

			// Display plugin toolbars:
			ob_start();
			echo '<div class="comment_toolbars">';
			$Plugins->trigger_event( 'DisplayCommentToolbar', array( 'Comment' => & $Comment, 'Item' => & $Item ) );
			echo '</div>';
			$comment_toolbar = ob_get_clean();

			// Message field:
			$form_inputstart = $Form->inputstart;
			$Form->inputstart .= $comment_toolbar;
			$Form->textarea_input( $dummy_fields['content'], $comment_content, 12, T_('Comment text'), array(
					'cols'  => 40,
					'class' => ( check_autocomplete_usernames( $Comment ) ? 'autocomplete_usernames ' : '' ).'link_attachment_dropzone'
				) );
			$Form->inputstart = $form_inputstart;

			// Set b2evoCanvas for plugins:
			echo '<script>var b2evoCanvas = document.getElementById( "'.$dummy_fields['content'].'" );</script>';

			$Form->info( T_('Text Renderers'), $Plugins->get_renderer_checkboxes( $comment_renderers, array(
					'Blog'         => & $Blog,
					'setting_name' => 'coll_apply_comment_rendering'
				) ) );

			// Attach files:
			if( !empty( $comment_attachments ) )
			{	// display already attached files checkboxes
				$FileCache = & get_FileCache();
				$attachments = explode( ',', $comment_attachments );
				$final_attachments = explode( ',', $checked_attachments );
				// create attachments checklist
				$list_options = array();
				foreach( $attachments as $attachment_ID )
				{
					$attachment_File = $FileCache->get_by_ID( $attachment_ID, false );
					if( $attachment_File )
					{
						// checkbox should be checked only if the corresponding file id is in the final attachments array
						$checked = in_array( $attachment_ID, $final_attachments );
						$list_options[] = array( 'preview_attachment'.$attachment_ID, 1, $attachment_File->get( 'name' ), $checked, false );
					}
				}
				if( !empty( $list_options ) )
				{	// display list
					$Form->checklist( $list_options, 'comment_attachments', T_( 'Attached files' ) );
				}
				// memorize all attachments ids
				$Form->hidden( 'preview_attachments', $comment_attachments );
			}

			// Display attachments fieldset:
			$Form->attachments_fieldset( $Comment );

			$Form->buttons_input( array(
					array( 'name' => 'submit_comment_post_'.$Item->ID.'[preview]', 'class' => 'preview btn-info', 'value' => /* TRANS: Verb */ T_('Preview') ),
					array( 'name' => 'submit_comment_post_'.$Item->ID.'[save]', 'class' => 'submit SaveButton', 'value' => T_('Send comment') )
				) );

			?>

				<div class="clearfix"></div>
			<?php
				$Form->end_form();
			?>
			<!-- ========== END of FORM to add a comment ========== -->
			<?php

			// ========== START of links to manage subscriptions ========== //
			echo '<br /><nav class="evo_post_comment_notification">';

			$notification_icon = get_icon( 'notification' );

			$not_subscribed = true;
			$creator_User = $Item->get_creator_User();

			if( $Blog->get_setting( 'allow_comment_subscriptions' ) )
			{
				$sql = 'SELECT count( sub_user_ID ) FROM T_subscriptions
							WHERE sub_user_ID = '.$current_User->ID.' AND sub_coll_ID = '.$Blog->ID.' AND sub_comments <> 0';
				if( $DB->get_var( $sql ) > 0 )
				{
					echo '<p class="text-center">'.$notification_icon.' <span>'.T_( 'You are receiving notifications when anyone comments on any post.' );
					echo ' <a href="'.get_notifications_url().'">'.T_( 'Click here to manage your subscriptions.' ).'</a></span></p>';
					$not_subscribed = false;
				}
			}

			if( $not_subscribed && ( $creator_User->ID == $current_User->ID ) && ( $UserSettings->get( 'notify_published_comments', $current_User->ID ) != 0 ) )
			{
				echo '<p class="text-center">'.$notification_icon.' <span>'.T_( 'This is your post. You are receiving notifications when anyone comments on your posts.' );
				echo ' <a href="'.get_notifications_url().'">'.T_( 'Click here to manage your subscriptions.' ).'</a></span></p>';
				$not_subscribed = false;
			}
			if( $not_subscribed && $Blog->get_setting( 'allow_item_subscriptions' ) )
			{
				if( get_user_isubscription( $current_User->ID, $Item->ID ) )
				{
					echo '<p class="text-center">'.$notification_icon.' <span>'.T_( 'You will be notified by email when someone comments here.' );
					echo ' <a href="'.get_htsrv_url().'action.php?mname=collections&action=isubs_update&p='.$Item->ID.'&amp;notify=0&amp;'.url_crumb( 'collections_isubs_update' ).'">'.T_( 'Click here to unsubscribe.' ).'</a></span></p>';
				}
				else
				{
					echo '<p class="text-center"><a href="'.get_htsrv_url().'action.php?mname=collections&action=isubs_update&p='.$Item->ID.'&amp;notify=1&amp;'.url_crumb( 'collections_isubs_update' ).'" class="btn btn-default">'.$notification_icon.' '.T_( 'Notify me by email when someone comments here.' ).'</a></p>';
				}
			}

			echo '</nav>';
			// ========== END of links to manage subscriptions ========== //

			} // / can comment

			// ========== START of item workflow properties ========== //
			if( $Item->can_edit_workflow() )
			{	// Display workflow properties if current user can edit at least one workflow property:
				$Form = new Form( get_htsrv_url().'item_edit.php' );

				$Form->add_crumb( 'item' );
				$Form->hidden( 'blog', $Blog->ID );
				$Form->hidden( 'post_ID', $Item->ID );
				$Form->hidden( 'redirect_to', $admin_url.'?ctrl=items&amp;blog='.$Blog->ID.'&p='.$Item->ID );

				$Form->begin_form( 'evo_item_workflow_form' );

				$Form->begin_fieldset( T_('Workflow properties') );

				echo '<div class="evo_item_workflow_form__fields">';

				$Item->display_workflow_field( 'status', $Form );

				$Item->display_workflow_field( 'user', $Form );

				$Item->display_workflow_field( 'priority', $Form );

				$Item->display_workflow_field( 'deadline', $Form );

				$Form->button( array( 'submit', 'actionArray[update_workflow]', T_('Update'), 'SaveButton' ) );

				echo '</div>';

				$Form->end_fieldset();

				$Form->end_form();
			}
			// ========== END of item workflow properties ========== //
		?>
		</div>
		<?php
	} // / comments requested
}

// Instantiate ClipboardJS:
expose_var_to_js( 'evo_init_item_list_clipboard_js', true );

if( $action == 'view' )
{	// Load JS functions to work with comments
	load_funcs( 'comments/model/_comment_js.funcs.php' );

	// Handle show_comments radioboxes
	echo_show_comments_changed( $comment_type );
}
elseif( $allow_items_list_form )
{	// Allow to select item for action only on items list if current user can edit at least one item status:

	// Buttons to check/uncheck/invert all Items:
	$Form->checkbox_controls( 'selected_items', array(
		'before_buttons' => '<span class="btn-group">',
		'after_buttons'  => '</span> ',
		'button_class'   => 'btn btn-default',
		'icon_class'     => '',
	) );

	echo T_('With checked posts').': ';

	// Display a button to change visibility of selected comments:
	echo_item_status_buttons( $Form, NULL, 'items_visibility' );
	echo_status_dropdown_button_js( 'post' );

	echo ' <span class="btn-group">';
	$Form->button( array( 'button', 'mass_change_main_cat', T_('Change primary category') ) );
	$Form->button( array( 'button', 'mass_add_extra_cat', T_('Add secondary category') ) );
	echo '</span> ';
	if( is_pro() && check_user_perm( 'options', 'edit' ) )
	{	// Export Items only for PRO version:
		$Form->button( array( 'submit', 'actionArray[mass_export]', T_('Export to XML') ) );
	}
	$Form->button( array( 'submit', 'actionArray[mass_delete]', T_('Delete'), 'btn-danger' ) );

	$Form->end_form();

	// JavaScript code to mass change category of Items:
	echo_item_mass_change_cat_js();
}

// Display navigation:
$ItemList->display_nav( 'footer' );

echo '</div>';// END OF <div class="evo_content_block">

?>
