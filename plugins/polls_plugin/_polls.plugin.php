<?php
/**
 * This file implements the Poll plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Renderer plugin that replaces [poll:nnn] with the same thing as the poll widget displays
 *
 * @package plugins
 */
class polls_plugin extends Plugin
{
	var $code = 'evo_poll';
	var $name = 'Polls';
	var $priority = 65;
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $version = '7.2.5';
	var $number_of_installs = 1;


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Polls plugin');
		$this->long_desc = T_('This is a basic poll plugin. Use it by entering [poll:nnn] into your post, where nnn is the ID of the poll.');
	}


	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::get_coll_setting_definitions()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		$default_params = array_merge( $params, array(
				'default_comment_rendering' => 'never',
				'default_post_rendering' => 'opt-out'
			) );

		return parent::get_coll_setting_definitions( $default_params );
	}


	/**
	 * Define here default message settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_msg_setting_definitions( & $params )
	{
		// set params to allow rendering for messages by default
		$default_params = array_merge( $params, array( 'default_msg_rendering' => 'opt-out' ) );
		return parent::get_msg_setting_definitions( $default_params );
	}


	/**
	 * Define here default email settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_email_setting_definitions( & $params )
	{
		// set params to allow rendering for messages by default
		$default_params = array_merge( $params, array( 'default_email_rendering' => 'opt-out' ) );
		return parent::get_email_setting_definitions( $default_params );
	}


	/**
	 * Define here default shared settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::GetDefaultSettings()}.
	 */
	function get_shared_setting_definitions( & $params )
	{
		// set params to allow rendering for shared container widgets by default:
		$default_params = array_merge( $params, array( 'default_shared_rendering' => 'never' ) );
		return parent::get_shared_setting_definitions( $default_params );
	}


	/**
	 * Dummy placeholder. Without it the plugin would ne be considered to be a renderer...
	 *
	 * @see Plugin::RenderItemAsHtml
	 */
	function RenderItemAsHtml( & $params )
	{
		return false;
	}


	/**
	 * Perform rendering of email
	 *
	 * @see Plugin::RenderEmailAsHtml()
	 */
	function RenderEmailAsHtml( & $params )
	{
		$content = & $params['data'];

		$params['check_code_block'] = true; // TRUE to find inline tags only outside of codeblocks

		$content = $this->render_polls_data( $content, $params, 'email' );

		return true;
	}


	/**
	 * Perform rendering
	 *
	 * @see Plugin::DisplayrItemAsHtml()
	 */
	function DisplayItemAsHtml( & $params )
	{
		$content = & $params['data'];

		$params['check_code_block'] = true; // TRUE to find inline tags only outside of codeblocks

		$content = $this->render_polls_data( $content, $params );

		return true;
	}


	/**
	 * Convert inline poll tags into HTML tags like:
	 *    [poll:123] - Display a widget "Poll" with poll ID #123
	 *    [poll:123:Panel Title] - Use custom panel title instead of default T_('Poll')
	 *    [poll:123:-] - No title and panel are displayed at all
	 *    [poll:123:Panel Title:Question message?] - Custom title + Replace poll question from DB with custom question text
	 *    [poll:123:Panel Title:-] - Custom title + Hide question
	 *    [poll:123:-:-] - Hide title + Hide question
	 *
	 * @param string Source content
	 * @param array Params
	 * @return string Content
	 */
	function render_polls_data( $content, $params = array(), $format = 'html' )
	{
		if( isset( $params['check_code_block'] ) && $params['check_code_block'] && ( ( stristr( $content, '<code' ) !== false ) || ( stristr( $content, '<pre' ) !== false ) ) )
		{	// Call $this->render_polls_data() on everything outside code/pre:
			$params['check_code_block'] = false;
			$content = callback_on_non_matching_blocks( $content,
				'~<(code|pre)[^>]*>.*?</\1>~is',
				array( $this, 'render_polls_data' ), array( $params ) );
			return $content;
		}

		// Find all matches with tags of poll data:
		preg_match_all( '#\[poll:(\d+):?([^:\]]*):?([^:\]]*):?(.*?)\]#', $content, $tags );

		if( count( $tags[0] ) > 0 )
		{	// If at least one poll inline tag is found in content:

			// Initialize widget "Poll" in order to render poll blocks:
			load_class( 'widgets/widgets/_poll.widget.php', 'poll_Widget' );
			$poll_Widget = new poll_Widget();

			foreach( $tags[0] as $t => $source_tag )
			{	// Render poll inline tag as html with widget "Poll":
				$poll_ID = $tags[1][$t];
				$poll_title = ( empty( $tags[2][ $t ] ) ? T_('Poll') : $tags[2][ $t ] );
				$poll_question = ( empty( $tags[3][ $t ] ) ? NULL : $tags[3][ $t ] );
				$redirect_to = ( empty( $tags[4][ $t ] ) ? NULL : $tags[4][ $t ] );

				// Display title only when it doesn't equal "-":
				$display_title = ( $poll_title !== '-' );

				ob_start();
				switch( $format )
				{
					case 'email':
						$this->render_email( array(
								'poll_ID'             => $poll_ID,
								'title'               => $poll_title,
								'poll_question'       => $poll_question,
								'redirect_to'         => $redirect_to,
								'block_display_title' => $display_title,
								'block_start'         => $display_title ? '<div>' : '',
								'block_end'           => $display_title ? '</div>' : '',
								'block_title_start'   => $display_title ? '<h3>' : '',
								'block_title_end'     => $display_title ? '</h3>' : '',
								'block_body_start'    => $display_title ? '<div>' : '',
								'block_body_end'      => $display_title ? '</div>' : '',
							) );
						break;

					case 'html':
					default:
						$poll_Widget->display( array(
								'poll_ID'             => $poll_ID,
								'title'               => $poll_title,
								'poll_question'       => $poll_question,
								'block_display_title' => $display_title,
								'block_start'         => $display_title ? '<div class="panel panel-default">' : '',
								'block_end'           => $display_title ? '</div>' : '',
								'block_title_start'   => $display_title ? '<div class="panel-heading">' : '',
								'block_title_end'     => $display_title ? '</div>' : '',
								'block_body_start'    => $display_title ? '<div class="panel-body">' : '',
								'block_body_end'      => $display_title ? '</div>' : '',
							) );
				}
				$poll_Widget->disp_params = NULL;

				// Replace poll inline tag with the rendered poll html block:
				$content = substr_replace( $content, ob_get_clean(), strpos( $content, $source_tag ), strlen( $source_tag ) );
			}
		}

		return $content;
	}


	/**
	 * Event handler: Called when displaying editor toolbars on a post/item form.
	 * This is for post/item edit forms only. Comments, PMs and emails use different events.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function AdminDisplayToolbar( & $params )
	{
		if( ! empty( $params['Item'] ) )
		{	// Item is set, get Blog from post:
			$edited_Item = & $params['Item'];
			$Collection = $Blog = & $edited_Item->get_Blog();
		}

		if( empty( $Blog ) )
		{	// Item is not set, try global Blog:
			global $Collection, $Blog;
			if( empty( $Blog ) )
			{	// We can't get a Blog, this way "apply_rendering" plugin collection setting is not available:
				return false;
			}
		}

		$apply_rendering = $this->get_coll_setting( 'coll_apply_rendering', $Blog );
		if( empty( $apply_rendering ) || $apply_rendering == 'never' )
		{	// Plugin is not enabled for current case, so don't display a toolbar:
			return false;
		}

		return $this->DisplayCodeToolbar( $params );
	}


	/**
	 * Event handler: Called when displaying editor toolbars on comment form.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayCommentToolbar( & $params )
	{
		if( ! empty( $params['Comment'] ) )
		{	// Comment is set, get Blog from comment:
			$Comment = & $params['Comment'];
			if( ! empty( $Comment->item_ID ) )
			{
				$comment_Item = & $Comment->get_Item();
				$Collection = $Blog = & $comment_Item->get_Blog();
			}
		}

		if( empty( $Blog ) )
		{	// Comment is not set, try global Blog:
			global $Collection, $Blog;
			if( empty( $Blog ) )
			{	// We can't get a Blog, this way "apply_comment_rendering" plugin collection setting is not available:
				return false;
			}
		}

		$apply_rendering = $this->get_coll_setting( 'coll_apply_comment_rendering', $Blog );
		if( empty( $apply_rendering ) || $apply_rendering == 'never' )
		{	// Plugin is not enabled for current case, so don't display a toolbar:
			return false;
		}

		// Print toolbar on screen
		return $this->DisplayCodeToolbar( $params );
	}


	/**
	 * Event handler: Called when displaying editor toolbars for message.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayMessageToolbar( & $params )
	{
		$apply_rendering = $this->get_msg_setting( 'msg_apply_rendering' );
		if( ! empty( $apply_rendering ) && $apply_rendering != 'never' )
		{	// Print toolbar on screen:
			return $this->DisplayCodeToolbar( $params );
		}
		return false;
	}


	/**
	 * Event handler: Called when displaying editor toolbars for email.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayEmailToolbar( & $params )
	{
		$apply_rendering = $this->get_email_setting( 'email_apply_rendering' );
		if( ! empty( $apply_rendering ) && $apply_rendering != 'never' )
		{	// Print toolbar on screen:
			return $this->DisplayCodeToolbar( $params );
		}
		return false;
	}


	/**
	 * Display Toolbar
	 *
	 * @param array Params
	 */
	function DisplayCodeToolbar( $params = array() )
	{
		global $Hit, $debug;

		if( $Hit->is_lynx() )
		{ // let's deactive toolbar on Lynx, because they don't work there
			return false;
		}

		$params = array_merge( array(
				'js_prefix' => '', // Use different prefix if you use several toolbars on one page
			), $params );

		// Load JS to work with textarea
		require_js_defer( 'functions.js', 'blog', true );

		// Load CSS for modal window
		$this->require_css( 'polls.css', true );

		// Initialize JavaScript to build and open window:
		echo_modalwindow_js();

		$js_config = array(
				'prefix'               => $params['js_prefix'],
				'plugin_code'          => $this->code,
				'debug'                => $debug,

				'toolbar_title_before' => format_to_js( $this->get_template( 'toolbar_title_before' ) ),
				'toolbar_title_after'  => format_to_js( $this->get_template( 'toolbar_title_after' ) ),
				'toolbar_group_before' => format_to_js( $this->get_template( 'toolbar_group_before' ) ),
				'toolbar_group_after'  => format_to_js( $this->get_template( 'toolbar_group_after' ) ),
				'toolbar_title'        => T_('Polls').':',
				
				'button_title'         => T_('Insert a Poll'),
				'button_value'         => T_('Insert a Poll'),
				'button_class'         => $this->get_template( 'toolbar_button_class' ),
				'modal_window_title'   => T_('Insert a Poll'),
			);

		if( is_ajax_request() )
		{
			?>
			<script>
				jQuery( document ).ready( function() {
						window.evo_init_polls_toolbar( <?php echo evo_json_encode( $js_config ); ?> );
					} );
			</script>
			<?php
		}
		else
		{
			expose_var_to_js( 'polls_toolbar_'.$params['js_prefix'], $js_config, 'evo_init_polls_toolbar_config' );
		}

		echo $this->get_template( 'toolbar_before', array( '$toolbar_class$' => $params['js_prefix'].$this->code.'_toolbar' ) );
		echo $this->get_template( 'toolbar_after' );

		return true;
	}


	/**
	 * Render poll for email
	 *
	 * @param array Associative array of parameters
	 */
	function render_email( $params = array() )
	{
		$PollCache = & get_PollCache();
		$Poll = $PollCache->get_by_ID( $params['poll_ID'], false, false );

		$poll_question = empty( $params['poll_question'] ) ? $Poll->get( 'question_text' ) : $params['poll_question'];
		$poll_options = $Poll->get_poll_options();

		if( count( $poll_options ) > 0 )
		{
			if( $params['block_display_title'] )
			{
				echo $params['block_title_start'].$params['title'].$params['block_title_end'];
			}
			echo '<ul>'.$poll_question == '-' ? '' : '<b>'.$poll_question.'</b>';
			foreach( $poll_options as $poll_option )
			{
				$vote_url = get_htsrv_url().'action.php?mname=polls&action=email_vote&poll_ID='.$params['poll_ID'].'&poll_answer='.$poll_option->ID.'&email_ID=$mail_log_ID$&email_key=$email_key$';
				if( ! empty( $params['redirect_to'] ) )
				{
					$vote_url = url_add_param( $vote_url, 'redirect_to='.rawurlencode( $params['redirect_to'] ) );
				}
				echo '<li'.emailskin_style('li.evo_poll_option').'><a href="'.$vote_url.'">'.$poll_option->option_text.'</a></li>';
			}
			echo '</ul>';
		}
	}
}
?>
