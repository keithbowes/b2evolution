<?php
/**
 * This is the template that displays the comment form for a post
 *
 * This file is not meant to be called directly.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $cookie_name, $cookie_email, $cookie_url;
global $comment_allowed_tags;
global $comment_cookies, $comment_allow_msgform, $comment_anon_notify;
global $checked_attachments; // Set this var as global to use it in the method $Item->can_attach()
global $PageCache, $Session;
global $Collection, $Blog, $dummy_fields;

// Default params:
$params = array_merge( array(
		'disp_comment_form'    => true,
		'form_title_start'     => '<div class="panel '.( $Session->get( 'core.preview_Comment'.( isset( $params['comment_type'] ) && $params['comment_type'] == 'meta' ? 'meta' : '' ) ) ? 'panel-danger' : 'panel-default' ).'">'
																.'<div class="panel-heading"><h4 class="panel-title">',
		'form_title_end'       => '</h4></div><div class="panel-body">',
		'form_title_text'      => T_('Leave a comment'),
		'form_comment_text'    => T_('Comment text'),
		'form_submit_text'     => T_('Send comment'),
		'form_params'          => array( // Use to change structure of form, i.e. fieldstart, fieldend and etc.
			'comments_disabled_before' => '<p class="alert alert-warning">',
			'comments_disabled_after' => '</p>',
			),
		'policy_text'          => '',
		'author_link_text'     => 'auto',
		'textarea_lines'       => 10,
		'default_text'         => '',
		'preview_block_start'  => '',
		'preview_start'        => '<article class="evo_comment evo_comment__preview panel panel-warning" id="comment_preview">',
		'comment_template'     => '_item_comment.inc.php',	// The template used for displaying individual comments (including preview)
		'preview_end'          => '</article>',
		'preview_block_end'    => '',
		'before_comment_error' => '<p><em>',
		'comment_closed_text'  => '#',
		'after_comment_error'  => '</em></p>',
		'before_comment_form'  => '',
		'after_comment_form'   => '</div></div>',
		'form_comment_redirect_to' => $Item->get_feedback_url( $disp == 'feedback-popup', '&' ),
		'comment_image_size'       => 'fit-1280x720',
		'comment_attach_info'      => get_icon( 'help', 'imgtag', array(
				'data-toggle'    => 'tooltip',
				'data-placement' => 'bottom',
				'data-html'      => 'true',
				'title'          => htmlspecialchars( get_upload_restriction( array(
						'block_after'     => '',
						'block_separator' => '<br /><br />' ) ) )
			) ),
		'comment_type'         => 'comment',
		'comment_title_before'  => '<div class="panel-heading"><h4 class="evo_comment_title panel-title">',
		'comment_title_after'   => '</h4></div><div class="panel-body">',
		'comment_rating_before' => '<div class="evo_comment_rating">',
		'comment_rating_after'  => '</div>',
		'comment_text_before'   => '<div class="evo_comment_text">',
		'comment_text_after'    => '</div>',
		'comment_info_before'   => '<footer class="evo_comment_footer clear text-muted"><small>',
		'comment_info_after'    => '</small></footer></div>',
		'disp_notification'     => true,
		'disp_comments'         => true,
		'disp_trackbacks'       => true,
		'feed_title'            => '#',
	), $params );

$comment_reply_ID = param( 'reply_ID', 'integer', 0 );

$email_is_detected = false; // Used when comment contains an email strings

// Consider comment attachments list empty
$comment_attachments = '';

// Default renderers:
$comment_renderers = array( 'default' );

/*
 * Comment form:
 */
if( $params['comment_type'] == 'meta' )
{	// Use different form anchor for internal comments:
	$params['comment_form_anchor'] = 'meta_form_p';
}

if( $params['comment_type'] == 'meta' )
{
	$section_title = $params['form_title_start'].$params['form_title_text'].$params['form_title_end'];
	if( $params['disp_comment_form'] && ( $params['comment_type'] == 'meta' && $Item->can_meta_comment() ||
			$Item->can_comment( $params['before_comment_error'], $params['after_comment_error'], '#', $params['comment_closed_text'], $section_title, $params ) ) )
	{ // We want to display the comments form and the item can be commented on:

		echo '<a name="meta-comment-form"></a>';

		echo $params['before_comment_form'];

		// INIT/PREVIEW:
		if( $Comment = get_comment_from_session( 'preview', $params['comment_type'] ) )
		{	// We have a comment to preview
			if( $Comment->item_ID == $Item->ID )
			{ // display PREVIEW:

				// We do not want the current rendered page to be cached!!
				if( !empty( $PageCache ) )
				{
					$PageCache->abort_collect();
				}

				if( $Comment->email_is_detected )
				{	// We set it to define a some styles below
					$email_is_detected = true;
				}

				if( empty( $Comment->in_reply_to_cmt_ID ) )
				{ // Display the comment preview here only if this comment is not a reply, otherwise it was already displayed
					// ------------------ PREVIEW COMMENT INCLUDED HERE ------------------
					skin_include( $params['comment_template'], array(
							'Comment'               => & $Comment,
							'comment_block_start'   => $Comment->email_is_detected ? '' : $params['preview_block_start'],
							'comment_start'         => $Comment->email_is_detected ? $params['comment_error_start'] : $params['preview_start'],
							'comment_end'           => $Comment->email_is_detected ? $params['comment_error_end'] : $params['preview_end'],
							'comment_block_end'     => $Comment->email_is_detected ? '' : $params['preview_block_end'],
							'comment_title_before'  => $params['comment_title_before'],
							'comment_title_after'   => $params['comment_title_after'],
							'comment_rating_before' => $params['comment_rating_before'],
							'comment_rating_after'  => $params['comment_rating_after'],
							'comment_text_before'   => $params['comment_text_before'],
							'comment_text_after'    => $params['comment_text_after'],
							'comment_info_before'   => $params['comment_info_before'],
							'comment_info_after'    => $params['comment_info_after'],
							'author_link_text'      => $params['author_link_text'],
							'image_size'            => $params['comment_image_size'],
						) );
					// Note: You can customize the default item comment by copying the generic
					// /skins/_item_comment.inc.php file into the current skin folder.
					// ---------------------- END OF PREVIEW COMMENT ---------------------
				}

				$comment_cookies = $Session->get( 'core.comment_cookies_preview' );
				$Session->delete( 'core.comment_cookies_preview' );

				// Form fields:
				$comment_content = $Comment->original_content;
				// comment_attachments contains all file IDs that have been attached
				$comment_attachments = $Comment->preview_attachments;
				// checked_attachments contains all attachment file IDs which checkbox was checked in
				$checked_attachments = $Comment->checked_attachments;
				// for visitors:
				$comment_author = $Comment->author;
				$comment_author_email = $Comment->author_email;
				$comment_author_url = $Comment->author_url;
				$comment_allow_msgform = $Comment->allow_msgform;
				$comment_anon_notify = $Comment->anon_notify;
				$comment_user_notify = isset( $Comment->user_notify ) ? $Comment->user_notify : NULL;
				// Get what renderer checkboxes were selected on form:
				$comment_renderers = explode( '.', $Comment->get( 'renderers' ) );

				// Display error messages again after preview of comment
				global $Messages;
				$Messages->display();
			}
		}
		else
		{ // New comment:
			if( ( $Comment = get_comment_from_session( 'unsaved', $params['comment_type'] ) ) == NULL )
			{ // there is no saved Comment in Session
				$Comment = new Comment();
				$Comment->set( 'type', $params['comment_type'] );
				$Comment->set( 'item_ID', $Item->ID );
				if( ( !empty( $PageCache ) ) && ( $PageCache->is_collecting ) )
				{	// This page is going into the cache, we don't want personal data cached!!!
					// fp> These fields should be filled out locally with Javascript tapping directly into the cookies. Anyone JS savvy enough to do that?
					$comment_author = '';
					$comment_author_email = '';
					$comment_author_url = '';
				}
				else
				{ // Get params from $_COOKIE
					$comment_author = param_cookie( $cookie_name, 'string', '' );
					$comment_author_email = utf8_strtolower( param_cookie( $cookie_email, 'string', '' ) );
					$comment_author_url = param_cookie( $cookie_url, 'string', '' );
				}
				if( empty($comment_author_url) )
				{	// Even if we have a blank cookie, let's reset this to remind the bozos what it's for
					$comment_author_url = 'http://';
				}

				$comment_content =  $params['default_text'];
			}
			else
			{ // set saved Comment attributes from Session
				$comment_cookies = $Session->get( 'core.comment_cookies' );
				$Session->delete( 'core.comment_cookies' );

				$comment_content = $Comment->content;
				$comment_author = $Comment->author;
				$comment_author_email = $Comment->author_email;
				$comment_author_url = $Comment->author_url;
				$comment_allow_msgform = $Comment->allow_msgform;
				$comment_anon_notify = $Comment->anon_notify;
				$comment_user_notify = isset( $Comment->user_notify ) ? $Comment->user_notify : NULL;
				// comment_attachments contains all file IDs that have been attached
				$comment_attachments = $Comment->preview_attachments;
				// checked_attachments contains all attachment file IDs which checkbox was checked in
				$checked_attachments = $Comment->checked_attachments;
			}

			$quoted_comment_ID = param( 'quote_comment', 'integer', 0 );
			$quoted_post_ID = param( 'quote_post', 'integer', 0 );
			if( $quoted_comment_ID || $quoted_post_ID )
			{ // Quote for comment/post
				$comment_content = param( $dummy_fields[ 'content' ], 'html' );
				if( ! empty( $quoted_comment_ID ) &&
						( $CommentCache = & get_CommentCache() ) &&
						( $quoted_Comment = & $CommentCache->get_by_ID( $quoted_comment_ID, false ) ) &&
						$params['comment_type'] == $quoted_Comment->get( 'type' ) )
				{	// Allow comment quoting only for the same comment type form:
					$quoted_Item = $quoted_Comment->get_Item();
					if( $quoted_User = $quoted_Comment->get_author_User() )
					{ // User is registered
						$quoted_login = $quoted_User->login;
					}
					else
					{ // Anonymous user
						$quoted_login = $quoted_Comment->get_author_name();
					}
					$quoted_content = $quoted_Comment->get( 'content' );
					$quoted_ID = 'c'.$quoted_Comment->ID;
				}
				elseif( ! empty( $quoted_post_ID ) && $params['comment_type'] != 'meta' )
				{	// Allow item quoting only for normal(not meta) comment type form:
					$ItemCache = & get_ItemCache();
					$quoted_Item = & $ItemCache->get_by_ID( $quoted_post_ID, false );
					$quoted_login = $quoted_Item->get_creator_login();
					$quoted_content = $quoted_Item->get( 'content' );
					$quoted_ID = 'p'.$quoted_Item->ID;
				}

				if( !empty( $quoted_Item ) )
				{	// Format content for editing, if we were not already in editing...
					$comment_title = '';
					$comment_content .= '[quote=@'.$quoted_login.'#'.$quoted_ID.']'.strip_tags($quoted_content).'[/quote]';

					$Plugins_admin = & get_Plugins_admin();
					$quoted_Item->load_Blog();
					$plugins_params = array( 'object_type' => 'Comment', 'object_Blog' => & $quoted_Item->Blog );
					$Plugins_admin->unfilter_contents( $comment_title /* by ref */, $comment_content /* by ref */, $quoted_Item->get_renderers_validated(), $plugins_params );
				}
			}
		}

		if( ( !empty( $PageCache ) ) && ( $PageCache->is_collecting ) )
		{	// This page is going into the cache, we don't want personal data cached!!!
			// fp> These fields should be filled out locally with Javascript tapping directly into the cookies. Anyone JS savvy enough to do that?
		}
		else
		{
			// Get values that may have been passed through after a preview
			param( 'comment_cookies', 'integer', NULL );
			param( 'comment_allow_msgform', 'integer', NULL ); // checkbox

			if( is_null( $comment_cookies ) )
			{	// "Remember me" checked, if remembered before:
				$comment_cookies = isset( $_COOKIE[$cookie_name] ) || isset( $_COOKIE[$cookie_email] ) || isset( $_COOKIE[$cookie_url] );
			}
		}

		echo $params['form_title_start'];
		echo $params['form_title_text'];
		echo $params['form_title_end'];

		if( $params['comment_type'] != 'meta' )
		{	// Display a message before comment form:
			$Item->display_comment_form_msg();
		}

		/*
			echo '<script>
		/* <![CDATA[ *
		function validateCommentForm(form)
		{
			if( form.'.$dummy_fields['content'].'.value.replace(/^\s+|\s+$/g,"").length == 0 )
			{
				alert("'.TS_('Please do not send empty comments.').'");
				return false;
			}
		}
		/* ]]> *
		</script>';*/
		$Form = new Form( get_htsrv_url().'comment_post.php', 'evo_comment_form_id_'.$Item->ID, 'post', NULL, 'multipart/form-data' );

		$Form->switch_template_parts( $params['form_params'] );

		$Form->begin_form( 'evo_form evo_form__comment', '', array( 'target' => '_self'/*, 'onsubmit' => 'return validateCommentForm(this);'*/ ) );

		// TODO: dh> a plugin hook would be useful here to add something to the top of the Form.
		//           Actually, the best would be, if the $Form object could be changed by a plugin
		//           before display!

		$Form->add_crumb( 'comment' );
		$Form->hidden( 'comment_type', $params['comment_type'] );
		$Form->hidden( 'comment_item_ID', $Item->ID );

		$comment_type = param( 'comment_type', 'string', 'comment' );
		if( ! empty( $comment_reply_ID ) && $comment_type == $params['comment_type'] )
		{
			$Form->hidden( 'reply_ID', $comment_reply_ID );

			// Link to scroll back up to replying comment
			echo '<a href="'.url_add_param( $Item->get_permanent_url(), 'reply_ID='.$comment_reply_ID.'&amp;redir=no' ).'#c'.$comment_reply_ID.'" class="comment_reply_current" rel="'.$comment_reply_ID.'">'.T_('You are currently replying to a specific comment').'</a>';
		}
		$Form->hidden( 'redirect_to',
				// Make sure we get back to the right page (on the right domain)
				// fp> TODO: check if we can use the permalink instead but we must check that application wide,
				// that is to say: check with the comments in a pop-up etc...
				// url_rel_to_same_host(regenerate_url( '', '', $Blog->get('blogurl'), '&' ), get_htsrv_url())
				// fp> what we need is a regenerate_url that will work in permalinks
				// fp> below is a simpler approach:
				$params['form_comment_redirect_to']
			);

		if( ! is_logged_in( false ) )
		{	// User is not logged in or not activated:
			if( is_logged_in() && empty( $comment_author ) && empty( $comment_author_email ) )
			{
				$comment_author = $current_User->get_username();
				$comment_author_email = $current_User->email;
			}
			// Note: we use funky field names to defeat the most basic guestbook spam bots
			$Form->text( $dummy_fields[ 'name' ], $comment_author, 40, T_('Name'), '<br />'.sprintf( T_('<a %s>Click here to log in</a> if you already have an account on this site.'), 'href="'.get_login_url( 'comment form', $Item->get_permanent_url() ).'" style="font-weight:bold"' ), 100, 'evo_comment_field' );

			$Form->email_input( $dummy_fields[ 'email' ], $comment_author_email, 40, T_('Email'), array(
				'bottom_note' => T_('Your email address will <strong>not</strong> be revealed on this site.'),
				'maxlength'   => 255,
				'class'       => 'evo_comment_field' ) );

			$Item->load_Blog();
			if( $Item->Blog->get_setting( 'allow_anon_url' ) )
			{
				$Form->text( $dummy_fields[ 'url' ], $comment_author_url, 40, T_('Website'), '<br />'.T_('Your URL will be displayed.'), 255, 'evo_comment_field' );
			}
		}

		/*
		if( ! $Comment->is_meta() && $Item->can_rate() )
		{ // Comment rating:
			ob_start();
			$Comment->rating_input( array( 'item_ID' => $Item->ID ) );
			$comment_rating = ob_get_clean();
			$Form->info_field( T_('Your vote'), $comment_rating );
		}
		*/

		if( !empty($params['policy_text']) )
		{	// We have a policy text to display
			$Form->info_field( '', $params['policy_text'] );
		}

		if( $Item->can_edit_workflow() )
		{	// Prepend info for the form submit button title to inform user about additional action when workflow properties are on the form:
			$params['form_submit_text'] = T_('Update Status').' / '.$params['form_submit_text'];
		}

		// Set prefix for js code in plugins:
		$plugin_js_prefix = ( $params['comment_type'] == 'meta' ? 'meta_' : '' );

		// Display plugin captcha for comment form before textarea:
		$Plugins->display_captcha( array(
				'Form'          => & $Form,
				'form_type'     => 'comment',
				'form_position' => 'before_textarea',
			) );

		ob_start();
		echo '<div class="comment_toolbars">';
		// CALL PLUGINS NOW:
		$Plugins->trigger_event( 'DisplayCommentToolbar', array(
				'Comment'     => & $Comment,
				'Item'        => & $Item,
				'js_prefix'   => $plugin_js_prefix,
			) );
		echo '</div>';
		$comment_toolbar = ob_get_clean();

		// Message field:
		$content_id = $dummy_fields['content'].'_'.$params['comment_type'];
		$form_inputstart = $Form->inputstart;
		$Form->inputstart .= $comment_toolbar;
		$note = '';
		// $note = T_('Allowed XHTML tags').': '.htmlspecialchars(str_replace( '><',', ', $comment_allowed_tags));
		$Form->textarea_input( $dummy_fields['content'], $comment_content, $params['textarea_lines'], $params['form_comment_text'], array(
				'note'  => $note,
				'cols'  => 38,
				'class' => ( check_autocomplete_usernames( $Comment ) ? 'autocomplete_usernames ' : '' ).'link_attachment_dropzone',
				'id'    => $content_id,
				'maxlength' => ( $Comment->is_meta() ? '' : $Blog->get_setting( 'comment_maxlen' ) ),
			) );
		$Form->inputstart = $form_inputstart;

		// Set canvas object for plugins:
		echo '<script>var '.$plugin_js_prefix.'b2evoCanvas = document.getElementById( "'.$content_id.'" );</script>';

		if( $Item->can_attach( false, $Comment->type ) )
		{	// If current user has permission to attach files for the item:
			load_class( 'links/model/_linkcomment.class.php', 'LinkComment' );
			// Create $LinkComment to generate temporary link owner ID for the $Comment:
			$LinkOwner = new LinkComment( $Comment, $Comment->temp_link_owner_ID );

			if( empty( $Comment->temp_link_owner_ID ) )
			{	// Set Comment temp_link_owner_ID:
				$Comment->temp_link_owner_ID = $LinkOwner->get_ID();
			}
		}

		// CALL PLUGINS NOW:
		ob_start();
		$admin_editor_params = array(
				'target_type'   => 'Comment',
				'target_object' => $Comment,
				'content_id'    => $content_id,
				'edit_layout'   => 'inskin',
			);
		if( isset( $LinkOwner) && $LinkOwner->is_temp() )
		{
			$admin_editor_params['temp_ID'] = $LinkOwner->get_ID();
		}
		$Plugins->trigger_event( 'AdminDisplayEditorButton', $admin_editor_params );
		$admin_display_editor_button = ob_get_clean();

		$comment_options = array();
		if( ! is_logged_in( false ) )
		{	// For anonymous or not activated user:
			// TODO: If we got info from cookies, Add a link called "Forget me now!" (without posting a comment).
			$comment_options[] = array( 'comment_cookies', 1, T_('Remember me'), $comment_cookies, false, '('.T_('Set cookies so I don\'t need to fill out my details next time').')' );
			// TODO: If we have an email in a cookie, Add links called "Add a contact icon to all my previous comments" and "Remove contact icon from all my previous comments".
			$comment_options[] = array( 'comment_allow_msgform', 1, T_('Allow message form'), $comment_allow_msgform, false, '('.T_('Allow users to contact me through a message form -- Your email will <strong>not</strong> be revealed!').')', ( $email_is_detected ? 'comment_recommended_option' : '' ) );
			if( $Blog->get_setting( 'allow_anon_subscriptions' ) )
			{	// If item anonymous subscriptions are allowed for current collection:
				$comment_options[] = array( 'comment_anon_notify', 1, T_('Notify me of replies'), isset( $comment_anon_notify ) ? $comment_anon_notify : $Blog->get_setting( 'default_anon_comment_notify' ) );
			}
		}
		/*
		elseif( $params['comment_type'] != 'meta' && $Blog->get_setting( 'allow_item_subscriptions' ) )
		{	// For registered user and normal(not meta) comment and if item subscriptions are allowed for current collection:
			$comment_options[] = array( 'comment_user_notify', 1, T_('Notify me of replies'), ( isset( $comment_user_notify ) ? $comment_user_notify : 1 ) );
		}
		*/

		// Display renderers
		$comment_renderer_checkboxes = $Plugins->get_renderer_checkboxes( $comment_renderers, array(
				'Blog'         => & $Blog,
				'setting_name' => 'coll_apply_comment_rendering',
				'js_prefix'    => $plugin_js_prefix,
			) );

		$text_renderers = '';
		if( !empty( $comment_renderer_checkboxes ) )
		{
			$text_renderers .= '<div id="commentform_renderers" class="btn-group dropup pull-right">
					<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"><span class="caret"></span> '.T_('Text Renderers').'</button>
					<div class="dropdown-menu dropdown-menu-right">'.$comment_renderer_checkboxes.'</div>
				</div>';
				// JS code to don't hide popup on click to checkbox:
				expose_var_to_js( 'evo_commentform_renderers__click', true );
		}

		if( $Blog->get_setting( 'allow_html_comment' ) )
		{
			$form_fieldstart = $Form->fieldstart;
			$Form->fieldstart = add_tag_class( $Form->fieldstart, 'comment_text_renderers' );
			$Form->begin_line();
			echo '<div class="text_editor_controls">';
				echo '<div>';
				echo $admin_display_editor_button;
				echo '</div>';

				if( ! empty( $text_renderers ) )
				{
					echo $text_renderers;
				}
			echo '</div>';
			$Form->end_line();
			$Form->fieldstart = $form_fieldstart;

			if( count( $comment_options ) > 0 )
			{
				$Form->checklist( $comment_options, 'comment_options', T_('Options') );
			}
		}
		else
		{
			if( count( $comment_options ) > 0 )
			{
				$form_inputstart = $Form->inputstart;
				$form_inputend   = $Form->inputend;

				$Form->inputstart = add_tag_class( $Form->inputstart, 'text_editor_controls' );
				$Form->inputstart .='<div>';
				$Form->inputend = '</div><div class="comment_text_renderers">'.$text_renderers.'</div>'.$Form->inputend;

				$Form->checklist( $comment_options, 'comment_options', T_('Options') );

				$Form->inputstart = $form_inputstart;
				$Form->inputend   = $form_inputend;
			}
			elseif( ! empty( $text_renderers ) )
			{
				$form_fieldstart = $Form->fieldstart;
				$Form->fieldstart = add_tag_class( $Form->fieldstart, 'comment_text_renderers' );
				$Form->begin_line();
				echo $text_renderers;
				$Form->end_line();
				$Form->fieldstart = $form_fieldstart;
			}
		}


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
				$list_options[] = array( 'preview_attachment'.$attachment_ID, 1, '', $checked, false, $attachment_File->get( 'name' ) );
			}
		}
		if( !empty( $list_options ) )
		{	// display list
			$Form->checklist( $list_options, 'comment_attachments', T_( 'Attached files' ) );
		}
		// memorize all attachments ids
		$Form->hidden( 'preview_attachments', $comment_attachments );
	}
	if( $Item->can_attach() )
	{	// Display attach file input field when JavaScript is disabled:
		echo '<noscript>';
		$Form->input_field( array( 'label' => T_('Attach files'), 'note' => $params['comment_attach_info'], 'name' => 'uploadfile[]', 'type' => 'file' ) );
		echo '<p>'.T_('Please enable JavaScript to use file uploader.').'</p>';
		echo '</noscript>';
	}
	// Display attachments fieldset:
	$Form->attachments_fieldset( $Comment, false, $Comment->is_meta() ? 'meta_' : '' );

		// Display workflow properties if current user can edit at least one workflow property:
		skin_include( '_item_comment_workflow.inc.php', array_merge( $params, array(
			'Form'    => & $Form,
			'Comment' => & $Comment,
		) ) );

		$Plugins->trigger_event( 'DisplayCommentFormFieldset', array( 'Form' => & $Form, 'Item' => & $Item ) );

		// Display plugin captcha for comment form before submit button:
		$Plugins->display_captcha( array(
				'Form'          => & $Form,
				'form_type'     => 'comment',
				'form_position' => 'before_submit_button',
			) );

		$Form->begin_fieldset();
			echo $Form->buttonsstart;

			$preview_text = ( $Item->can_attach() ) ? T_('Preview/Add file') : T_('Preview');
			$Form->button_input( array( 'name' => 'submit_comment_post_'.$Item->ID.'[preview]', 'class' => 'preview btn-info', 'value' => $preview_text, 'tabindex' => 9 ) );
			$Form->button_input( array( 'name' => 'submit_comment_post_'.$Item->ID.'[save]', 'class' => 'submit SaveButton', 'value' => $params['form_submit_text'], 'tabindex' => 10 ) );

		if( $Item->can_attach() )
		{	// Don't display "/Add file" on the preview button if JS is enabled:
			echo '<script>document.querySelector( "input[type=submit].preview.btn-info" ).value = "'.TS_('Preview').'";</script>';
		}

			$Plugins->trigger_event( 'DisplayCommentFormButton', array( 'Form' => & $Form, 'Item' => & $Item ) );

			echo $Form->buttonsend;
		$Form->end_fieldset();

		$Form->end_form();

		echo $params['after_comment_form'];

		echo_comment_reply_js( $Item );
	}
}
else
{
	// ------------------------- "Comment Area" CONTAINER EMBEDDED HERE --------------------------
	// Display container contents:
	widget_container( 'comment_area', array(
		'widget_context' => 'item',	// Signal that we are displaying within an Item
		// The following (optional) params will be used as defaults for widgets included in this container:
		'container_display_if_empty' => false, // If no widget, don't display container at all
		// This will enclose each widget in a block:
		'block_start' => '<div class="evo_widget $wi_class$">',
		'block_end' => '</div>',
		// This will enclose the title of each widget:
		'block_title_start' => '<h3>',
		'block_title_end' => '</h3>',

		'widget_item_comment_form_params' => $params,

		'widget_item_notification_display' => $params['disp_notification'],

		'widget_item_comment_feed_link_display' => ( $params['disp_comments'] || $params['disp_trackbacks'] ),
		'widget_item_comment_feed_link_params' => array(
				'title' => $params['feed_title'],
			),

		// Params for plugin widget "Financial Contribution":
		'override_params_for_fin_contrib' => array(
				'block_start'       => '<div class="evo_widget $wi_class$ panel panel-default">',
				'block_end'         => '</div>',
				'block_title_start' => '<div class="panel-heading"><h4 class="panel-title">',
				'block_title_end'   => '</h4></div>',
				'block_body_start'  => '<div class="panel-body">',
				'block_body_end'    => '</div>',
			),
	) );
	// ----------------------------- END OF "Comment Area" CONTAINER -----------------------------
}
?>
