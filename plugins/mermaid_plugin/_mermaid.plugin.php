<?php
/**
 * This file implements the mermaid plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @package plugins
 */
class mermaid_plugin extends Plugin
{
	var $code = 'evo_mermaid';
	var $name = 'Mermaid Diagrams';
	var $priority = 85;
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
		$this->short_desc = T_( 'Display mermaid diagrams.' ).' '.T_( '(Plugin not available in WYSIWYG mode)' );
		$this->long_desc = T_( 'Display diagrams rendered by JavaScript plugin Mermaid.' ).' '.T_( '(Plugin not available in WYSIWYG mode)' );
	}


	/**
	 * Filters out the custom tag that would not validate, PLUS escapes the actual code.
	 *
	 * @param mixed $params
	 */
	function FilterItemContents( & $params )
	{
		$content = & $params['content'];
		$content = $this->filter_code( $content );

		return true;
	}


	/**
	 * Formats post contents ready for editing
	 *
	 * @param mixed $params
	 */
	function UnfilterItemContents( & $params )
	{
		$content = & $params['content'];
		$content = $this->unfilter_code( $content );

		return true;
	}


	/**
	 * Event handler: Called before at the beginning, if a comment form gets sent (and received).
	 */
	function CommentFormSent( & $params )
	{
		$ItemCache = & get_ItemCache();
		$comment_Item = & $ItemCache->get_by_ID( $params['comment_item_ID'], false );
		if( !$comment_Item )
		{ // Incorrect item
			return false;
		}

		$item_Blog = & $comment_Item->get_Blog();
		$apply_rendering = $this->get_coll_setting( 'coll_apply_comment_rendering', $item_Blog );
		if( $this->is_renderer_enabled( $apply_rendering, $params['renderers'] ) )
		{ // render code blocks in comment
			$params['content' ] = & $params['comment'];
			$this->FilterItemContents( $params );
		}
	}


	/**
	 * Event handler: Called before at the beginning, if a message of thread form gets sent (and received).
	 */
	function MessageThreadFormSent( & $params )
	{
		$apply_rendering = $this->get_msg_setting( 'msg_apply_rendering' );
		if( $this->is_renderer_enabled( $apply_rendering, $params['renderers'] ) )
		{ // render code blocks in message
			$this->FilterItemContents( $params );
		}
	}


	/**
	 * Event handler: Called before at the beginning, if an email form gets sent (and received).
	 */
	function EmailFormSent( & $params )
	{
		$apply_rendering = $this->get_email_setting( 'email_apply_rendering' );
		if( $this->is_renderer_enabled( $apply_rendering, $params['renderers'] ) )
		{	// render code blocks in message:
			$this->FilterItemContents( $params );
		}
	}


	/**
	 * Perform rendering
	 *
	 * @see Plugin::RenderItemAsHtml()
	 */
	function RenderItemAsHtml( & $params )
	{
		$content = & $params['data'];

		$content = preg_replace_callback( '#(\<p>)?\<pre class="line-numbers">\<code class="language-mermaid">([\s\S]+?)</code>\</pre>(\</p>)?#i',
			array( $this, 'render_codeblock_callback' ), $content );

		return true;
	}


	/**
	 * Callback to render code block
	 *
	 * @param array Matches
	 * @return string Formatted code block
	 */
	function render_codeblock_callback( $block )
	{
		return '<div class="mermaid">'.$block[2] .'</div>';
	}


	/**
	 * Encode HTML entities inside <code> blocks
	 *
	 * @param array Block
	 * @return string
	 */
	function encode_html_entities( $block )
	{
		return $block[1].htmlspecialchars( $block[2] ).$block[3];
	}


	/**
	 * Convert code blocks to html tags
	 *
	 * @param string Content
	 * @return string Content
	 */
	function filter_code( $content )
	{
		$content = preg_replace_callback( '#```mermaid([\s\S]+?)?```#i',
			array( $this, 'filter_code_callback' ), $content );

		return $content;
	}


	/**
	 * Formats code ready for rendering
	 *
	 * @param array $block ( 1 - type, 2 - attributes, 3 - content )
	 * @return string formatted code || empty
	 */
	function filter_code_callback( $block )
	{
		return '<pre class="line-numbers">'
				.'<code class="language-mermaid">'.trim( $block[1], "\r\n " ).'</code>'
			.'</pre>';
	}


	/**
	 * Convert code html tags to code blocks to edit format
	 *
	 * @param string Content
	 * @return string Content
	 */
	function unfilter_code( $content )
	{
		$content = preg_replace_callback( '#<pre class="line-numbers"><code class="language-mermaid">([\s\S]+?)?</code></pre>#i',
			array( $this, 'unfilter_code_callback' ), $content );

		return $content;
	}


	/**
	 * Formats code ready for editing
	 *
	 * @param array $block ( 1 - start of <pre> tag, 4 - language, 5 - content )
	 * @return string formatted code || empty
	 */
	function unfilter_code_callback( $block )
	{
		return '```mermaid'."\r\n".$block[1]."\r\n".'```';
	}


	/**
	 * Event handler: Called at the beginning of the skin's HTML HEAD section.
	 *
	 * Use this to add any HTML HEAD lines (like CSS styles or links to resource files (CSS, JavaScript, ..)).
	 *
	 * @param array Associative array of parameters
	 */
	function SkinBeginHtmlHead( & $params )
	{
		global $Collection, $Blog;

		if( ! isset( $Blog ) || (
		    $this->get_coll_setting( 'coll_apply_rendering', $Blog ) == 'never' &&
		    $this->get_coll_setting( 'coll_apply_comment_rendering', $Blog ) == 'never' ) )
		{ // Don't load css/js files when plugin is not enabled
			return;
		}

		$this->require_js_defer( 'js/mermaid.min.js' );	// Loaded in defer mode because this can take a while and init script below may already fire
		$this->require_js_defer( 'js/evo_init_mermaid.js' );
	}


	/**
	 * Event handler: Called when ending the admin html head section.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we do something?
	 */
	function AdminEndHtmlHead( & $params )
	{
		global $ctrl;

		if( $ctrl == 'campaigns' && get_param( 'tab' ) == 'send' && $this->get_email_setting( 'email_apply_rendering' ) )
		{	// Load this only on form to preview email campaign:
			$this->require_js_defer( 'js/mermaid.min.js' ); // Loaded in defer mode because this can take a while and init script below may already fire
			$this->require_js_defer( 'js/evo_init_mermaid.js' );
		}
	}


	/**
	 * Event handler: Called when displaying editor toolbars on comment form.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function DisplayCommentToolbar( & $params )
	{
		if( !empty( $params['Comment'] ) )
		{ // Comment is set, get Blog from comment
			$Comment = & $params['Comment'];
			if( !empty( $Comment->item_ID ) )
			{
				$comment_Item = & $Comment->get_Item();
				$Collection = $Blog = & $comment_Item->get_Blog();
			}
		}

		if( empty( $Blog ) )
		{ // Comment is not set, try global Blog
			global $Collection, $Blog;
			if( empty( $Blog ) )
			{ // We can't get a Blog, this way "apply_comment_rendering" plugin collection setting is not available
				return false;
			}
		}

		$apply_rendering = $this->get_coll_setting( 'coll_apply_comment_rendering', $Blog );
		if( ! empty( $apply_rendering ) && $apply_rendering != 'never' )
		{
			return $this->display_toolbar( $params );
		}
		return false;
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
		{
			return $this->display_toolbar( $params );
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
		{
			return $this->display_toolbar( $params );
		}
		return false;
	}


	/**
	 * Event handler: Called when displaying editor toolbars on post/item form.
	 *
	 * This is for post/item edit forms only. Comments, PMs and emails use different events.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function AdminDisplayToolbar( & $params )
	{
		if( !empty( $params['Item'] ) )
		{ // Item is set, get Blog from post
			$edited_Item = & $params['Item'];
			$Collection = $Blog = & $edited_Item->get_Blog();
		}

		if( empty( $Blog ) )
		{ // Item is not set, try global Blog
			global $Collection, $Blog;
			if( empty( $Blog ) )
			{ // We can't get a Blog, this way "apply_rendering" plugin collection setting is not available
				return false;
			}
		}

		$apply_rendering = $this->get_coll_setting( 'coll_apply_rendering', $Blog );
		if( empty( $apply_rendering ) || $apply_rendering == 'never' )
		{	// Don't display toolbar:
			return false;
		}

		// Display toolbar
		$this->display_toolbar( $params );
	}


	/**
	 * Display toolbar
	 *
	 * @param array Params
	 */
	function display_toolbar( $params )
	{
		$params = array_merge( array(
				'js_prefix' => '', // Use different prefix if you use several toolbars on one page
			), $params );

		// Codeblock buttons:
		echo $this->get_template( 'toolbar_before', array( '$toolbar_class$' => $params['js_prefix'].$this->code.'_toolbar' ) );
		echo $this->get_template( 'toolbar_title_before' ).T_('Mermaid').': '.$this->get_template( 'toolbar_title_after' );
		echo $this->get_template( 'toolbar_group_before' );
		echo '<input type="button" title="'.T_('Insert Mermaid codeblock').'" class="'.$this->get_template( 'toolbar_button_class' ).'" data-func="'.$params['js_prefix'].'mermaid_tag|html" value="'.T_('Diagram').'" />';
		echo $this->get_template( 'toolbar_group_after' );
		echo $this->get_template( 'toolbar_after' );

		// Load js to work with textarea
		require_js_defer( 'functions.js', 'blog', true );

		?><script>
			//<![CDATA[
			function <?php echo $params['js_prefix']; ?>mermaid_tag( lang, type )
			{
				textarea_wrap_selection( <?php echo $params['js_prefix']; ?>b2evoCanvas, '\r\n```mermaid\r\n', '\r\n```', 0 );
			}
			//]]>
		</script><?php

		return true;
	}
}

?>
