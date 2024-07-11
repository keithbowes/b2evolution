<?php
/**
 * This file implements the Auto Anchors plugin for b2evolution
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * The Auto Anchors Plugin.
 *
 * It adds attribute "id" for header tags <h1-6> for auto anchor
 *
 * @package plugins
 */
class auto_anchors_plugin extends Plugin
{
	var $code = 'auto_anchors';
	var $name = 'Auto Anchors';
	var $priority = 33;
	var $version = '7.2.5';
	var $group = 'rendering';
	var $short_desc;
	var $long_desc;
	var $help_topic = 'auto-anchors-plugin';
	var $number_of_installs = 1;

	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Automatic creating of anchors from header tags');
		$this->long_desc = T_('This renderer automatically append attribute "id" for header tags.');
	}


	/**
	 * Define here default collection/blog settings that are to be made available in the backoffice.
	 *
	 * @param array Associative array of parameters.
	 * @return array See {@link Plugin::get_coll_setting_definitions()}.
	 */
	function get_coll_setting_definitions( & $params )
	{
		$default_params = array(
				'default_post_rendering' => 'opt-out',
				'default_comment_rendering' => 'never',
			);

		if( ! empty( $params['blog_type'] ) )
		{	// Set default settings depending on collection type:
			switch( $params['blog_type'] )
			{
				case 'forum':
					$default_params['default_comment_rendering'] = 'never';
					$default_params['default_post_rendering'] = 'never';
					break;
			}
		}

		$default_params = array_merge( $params, $default_params );

		return array_merge( parent::get_coll_setting_definitions( $default_params ),
			array(
				'offset_scroll' => array(
						'label' => T_('Anchor offset'),
						'type' => 'integer',
						'defaultvalue' => 70,
						'suffix' => ' px',
						'note' => T_('This will be used when scrolling to an anchor.'),
					),
				)
			);
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
		$default_params = array_merge( $params, array( 'default_msg_rendering' => 'never' ) );
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
		// set params to allow rendering for emails by default:
		$default_params = array_merge( $params, array( 'default_email_rendering' => 'never' ) );
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
		$default_params = array_merge( $params, array( 'default_shared_rendering' => 'opt-in' ) );
		return parent::get_shared_setting_definitions( $default_params );
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
		global $Collection, $Blog, $disp;

		if( ! isset( $Blog ) || (
		    $this->get_coll_setting( 'coll_apply_rendering', $Blog ) == 'never' &&
		    $this->get_coll_setting( 'coll_apply_comment_rendering', $Blog ) == 'never' ) )
		{	// Don't load css/js files when plugin is not enabled:
			return;
		}

		$this->require_css_async( 'auto_anchors.css', false, 'footerlines' );

		// JS for initialize anchor icons and for better scrolling:
		// NOTE: we need this on each page because content bloc items may be included as widget even on front page!
		expose_var_to_js( 'evo_plugin_auto_anchors_settings', '{
				offset_scroll: '.format_to_js( intval( $this->get_coll_setting( 'offset_scroll', $Blog ) ) ).'
			}' );
	}


	/**
	 * Event handler: Called when ending the admin html head section.
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we do something?
	 */
	function AdminEndHtmlHead( & $params )
	{
		$this->SkinBeginHtmlHead( $params );
	}


	/**
	 * Perform rendering
	 */
	function RenderItemAsHtml( & $params )
	{
		$content = & $params['data'];

		// Load for replace_special_chars():
		load_funcs( 'locales/_charset.funcs.php' );

		// Replace content outside of <code></code>, <pre></pre> and markdown codeblocks:
		$content = replace_outside_code_tags( '#(<h([1-6])([^>]*\sid\s*=\s*["\']([^"\']+)["\'])?[^>]*)>(.+?)(</h\2>)#i', array( $this, 'callback_auto_anchor' ), $content, 'replace_content_callback' );

		return true;
	}


	/**
	 * Callback function to generate anchor from header text
	 *
	 * @param array Match data
	 * @return string
	 */
	function callback_auto_anchor( $m )
	{
		if( empty( $m[4] ) )
		{	// Generate anchor from header text:

			// Remove all HMTL tags from header text:
			$anchor = utf8_strip_tags( $m[5] );

			// Convert special chars/umlauts to ASCII,
			// and replace all non-letter and non-digit chars to single char "-":
			$anchor = replace_special_chars( $anchor );

			// Make anchor lowercase:
			$anchor = utf8_strtolower( $anchor );

			$anchor_attribute = ' id="'.$anchor.'"';
		}
		else
		{	// Use custom defined anchor:
			$anchor = $m[4];
			$anchor_attribute = '';
		}

		if( empty( $anchor ) )
		{	// Return original header tag when anchor is empty:
			return $m[0];
		}

		$header_tag_start = $m[1];
		if( strpos( $header_tag_start, ' class="' ) !== false )
		{	// Append style class to current:
			$header_tag_start = str_replace( ' class="', ' class="evo_auto_anchor_header ', $header_tag_start );
		}
		else
		{	// Add new class attribute:
			$header_tag_start .= ' class="evo_auto_anchor_header"';
		}

		return $header_tag_start.$anchor_attribute.'>'.$m[5].$m[6];
	}
}

?>
