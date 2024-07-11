<?php
/**
 * This file implements the UI view for the Advanced blog properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 *
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog;

global $Plugins, $Settings;

global $basepath, $rsc_url, $admin_url;

$Form = new Form( NULL, 'blogadvanced_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'advanced' );
$Form->hidden( 'blog', $edited_Blog->ID );


$Form->begin_fieldset( TB_('After each new post...').get_manual_link('after-each-new-post') );
	if( $edited_Blog->get_setting( 'allow_access' ) == 'users' )
	{
		echo '<p class="center orange">'.TB_('This collection is for logged in users only.').' '.TB_('The ping plugins can be enabled only for public collections.').'</p>';
	}
	elseif( $edited_Blog->get_setting( 'allow_access' ) == 'members' )
	{
		echo '<p class="center orange">'.TB_('This collection is for members only.').' '.TB_('The ping plugins can be enabled only for public collections.').'</p>';
	}
	$ping_plugins = preg_split( '~\s*,\s*~', $edited_Blog->get_setting( 'ping_plugins' ), -1, PREG_SPLIT_NO_EMPTY );

	$available_ping_plugins = $Plugins->get_list_by_event( 'ItemSendPing' );
	$displayed_ping_plugin = false;
	if( $available_ping_plugins )
	{
		foreach( $available_ping_plugins as $loop_Plugin )
		{
			if( empty( $loop_Plugin->code ) )
			{ // Ping plugin needs a code
				continue;
			}
			$displayed_ping_plugin = true;

			$checked = in_array( $loop_Plugin->code, $ping_plugins );
			$Form->checkbox( 'blog_ping_plugins[]', $checked,
				isset( $loop_Plugin->ping_service_setting_title ) ? $loop_Plugin->ping_service_setting_title : sprintf( /* TRANS: %s is a ping service name */ TB_('Ping %s'), $loop_Plugin->ping_service_name ),
				$loop_Plugin->ping_service_note, '', $loop_Plugin->code,
				// Disable ping plugins for not public collection:
				$edited_Blog->get_setting( 'allow_access' ) != 'public' );

			while( ( $key = array_search( $loop_Plugin->code, $ping_plugins ) ) !== false )
			{
				unset( $ping_plugins[$key] );
			}
		}
	}
	if( ! $displayed_ping_plugin )
	{
		echo '<p>'.TB_('There are no ping plugins activated.').'</p>';
	}

	// Provide previous ping services as hidden fields, in case the plugin is temporarily disabled:
	foreach( $ping_plugins as $ping_plugin_code )
	{
		$Form->hidden( 'blog_ping_plugins[]', $ping_plugin_code );
	}
$Form->end_fieldset();


$Form->begin_fieldset( TB_('External Feeds').get_manual_link('external-feeds') );

	$Form->text_input( 'atom_redirect', $edited_Blog->get_setting( 'atom_redirect' ), 50, TB_('Atom Feed URL'),
	TB_('Example: Your Feedburner Atom URL which should replace the original feed URL.').'<br />'
			.sprintf( TB_( 'Note: the original URL was: %s' ), url_add_param( $edited_Blog->get_item_feed_url( '_atom' ), 'redir=no' ) ),
	array('maxlength'=>255, 'class'=>'large') );

	$Form->text_input( 'rss2_redirect', $edited_Blog->get_setting( 'rss2_redirect' ), 50, TB_('RSS2 Feed URL'),
	TB_('Example: Your Feedburner RSS2 URL which should replace the original feed URL.').'<br />'
			.sprintf( TB_( 'Note: the original URL was: %s' ), url_add_param( $edited_Blog->get_item_feed_url( '_rss2' ), 'redir=no' ) ),
	array('maxlength'=>255, 'class'=>'large') );

$Form->end_fieldset();

$Form->begin_fieldset( TB_('Template').get_manual_link('collection-template') );
	$Form->checkbox_input( 'blog_allow_duplicate', $edited_Blog->get_setting( 'allow_duplicate' ), TB_('Allow duplication'), array( 'note' => TB_('Check to allow anyone to duplicate this collection.') ) );
$Form->end_fieldset();


if( check_user_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
{	// Permission to edit advanced admin settings

	$Form->begin_fieldset( TB_('Caching').get_admin_badge().get_manual_link('collection-cache-settings'), array( 'id' => 'caching' ) );
		$Form->checklist( array(
				array( 'ajax_form_enabled', 1, TB_('Comment, Contact & Quick registration forms will be fetched by javascript'), $edited_Blog->get_setting( 'ajax_form_enabled' ) ),
				array( 'ajax_form_loggedin_enabled', 1, TB_('Also use JS forms for logged in users'), $edited_Blog->get_setting( 'ajax_form_loggedin_enabled' ), ! $edited_Blog->get_setting( 'ajax_form_enabled' ) ),
			), 'ajax_form', TB_('Enable AJAX forms') );

		$Form->checkbox_input( 'cache_enabled', $edited_Blog->get_setting('cache_enabled'), get_icon( 'page_cache_on' ).' '.TB_('Enable page cache'), array( 'note'=>TB_('Cache rendered blog pages') ) );
		$Form->checkbox_input( 'cache_enabled_widgets', $edited_Blog->get_setting('cache_enabled_widgets'), get_icon( 'block_cache_on' ).' '.TB_('Enable widget/block cache'), array( 'note'=>TB_('Cache rendered widgets') ) );
	$Form->end_fieldset();

	$Form->begin_fieldset( TB_('In-skin Actions').get_admin_badge().get_manual_link('in-skin-action-settings'), array( 'id' => 'inskin_actions' ) );
		if( $login_Blog = & get_setting_Blog( 'login_blog_ID', $edited_Blog ) )
		{ // The login blog is defined in general settings
			$Form->info( TB_( 'In-skin login' ), sprintf( TB_('All login/registration functions are delegated to the collection: %s'), '<a href="'.$admin_url.'?ctrl=collections&amp;tab=site_settings">'.$login_Blog->get( 'shortname' ).'</a>' ) );
		}
		else
		{ // Allow to select in-skin login for this blog
			$Form->checkbox_input( 'in_skin_login', $edited_Blog->get_setting( 'in_skin_login' ), TB_( 'In-skin login' ), array( 'note' => TB_( 'Use in-skin login form every time it\'s possible' ) ) );
		}
		$Form->checkbox_input( 'in_skin_editing', $edited_Blog->get_setting( 'in_skin_editing' ), TB_( 'In-skin editing' ), array( 'note' => sprintf( TB_('See more options in Features &gt; <a %s>Posts</a>'), 'href="'.$admin_url.'?ctrl=coll_settings&amp;tab=features&amp;blog='.$edited_Blog->ID.'#post_options"' ) ) );
		$Form->checkbox_input( 'in_skin_change_proposal', $edited_Blog->get_setting( 'in_skin_change_proposal' ), TB_( 'In-skin change proposal' ) );
	$Form->end_fieldset();

	$Form->begin_fieldset( TB_('Media directory location').get_admin_badge().get_manual_link('media-directory-location'), array( 'id' => 'media_dir_location' ) );
	global $media_path;
	$Form->radio( 'blog_media_location', $edited_Blog->get( 'media_location' ),
			array(
				array( 'none', TB_('None') ),
				array( 'default', TB_('Default'), $media_path.'blogs/'.$edited_Blog->urlname.'/' ),
				array( 'subdir', TB_('Subdirectory of media folder').':',
					'',
					' <span class="nobr"><code>'.$media_path.'</code><input
						type="text" name="blog_media_subdir" class="form_text_input form-control" size="20" maxlength="255"
						class="'.( param_has_error('blog_media_subdir') ? 'field_error' : '' ).'"
						value="'.$edited_Blog->dget( 'media_subdir', 'formvalue' ).'" /></span>', '' ),
				array( 'custom',
					TB_('Custom location').':',
					'',
					'<fieldset class="form-group">'
					.'<div class="label control-label col-lg-2">'.TB_('directory').':</div><div class="input controls col-xs-8"><input
						type="text" class="form_text_input form-control" name="blog_media_fullpath" size="50" maxlength="255"
						class="'.( param_has_error('blog_media_fullpath') ? 'field_error' : '' ).'"
						value="'.$edited_Blog->dget( 'media_fullpath', 'formvalue' ).'" /></div>'
					.'<div class="clear"></div>'
					.'<div class="label control-label col-lg-2">'.TB_('URL').':</div><div class="input controls col-xs-8"><input
						type="text" class="form_text_input form-control" name="blog_media_url" size="50" maxlength="255"
						class="'.( param_has_error('blog_media_url') ? 'field_error' : '' ).'"
						value="'.$edited_Blog->dget( 'media_url', 'formvalue' ).'" /></div></fieldset>' )
			), TB_('Media directory'), true
		);
	$Form->info( TB_('URL preview'), '<span id="blog_media_url_preview">'.$edited_Blog->get_media_url().'</span>'
		.' <a href="'.$admin_url.'?ctrl=coll_settings&amp;tab=urls&amp;blog='.$edited_Blog->ID.'" class="small">'.TB_('CDN configuration').'</a>' );
	$Form->end_fieldset();

}

$Form->begin_fieldset( TB_('Software credits').get_manual_link('software-credits') );
	$max_credits = $edited_Blog->get_setting( 'max_footer_credits' );
	if( is_pro() )
	{	// Allow to remove "Powered by b2evolution" logos only for PRO version:
		$Form->checkbox( 'powered_by_logos', $edited_Blog->get_setting( 'powered_by_logos' ), TB_('Powered by logos'), TB_('Check this to remove "Powered by b2evolution" logos.') );
		// Don't inform about free version for PRO version:
		$max_credits_note = '';
	}
	else
	{	// Inform about free version:
		$max_credits_note = TB_('You get the b2evolution software for <strong>free</strong>. We do appreciate you giving us credit. <strong>Thank you for your support!</strong>');
		if( $max_credits < 1 )
		{
			$max_credits_note = '<img src="'.$rsc_url.'smilies/icon_sad.gif" /> '.$max_credits_note;
		}
	}
	$Form->text( 'max_footer_credits', $max_credits, 1, TB_('Max footer credits'), $max_credits_note, 1 );
$Form->end_fieldset();


if( check_user_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
{	// Permission to edit advanced admin settings

	$Form->begin_fieldset( TB_('Skin and style').get_admin_badge().get_manual_link('skin-and-style') );

		$Form->output = false;
		$Form->switch_layout( 'none' );
		$display_alt_skin_referer_url_input = $Form->text( 'display_alt_skin_referer_url', $edited_Blog->get_setting( 'display_alt_skin_referer_url' ), 64, '', '', 10000 );
		$Form->switch_layout( NULL );
		$Form->output = true;
		$Form->checklist( array(
				array( 'display_alt_skin_referer', 1, sprintf( TB_('Referer URL contains %s'), $display_alt_skin_referer_url_input ), $edited_Blog->get_setting( 'display_alt_skin_referer' ), false, '', 'checkbox_with_input' ),
			), 'alt_skin_conditions', TB_('Automatically display Alt skin if') );

		$Form->checkbox( 'blog_allowblogcss', $edited_Blog->get( 'allowblogcss' ), TB_('Allow customized blog CSS file'), TB_('You will be able to customize the blog\'s skin stylesheet with a file named style.css in the blog\'s media file folder.') );
		$Form->checkbox( 'blog_allowusercss', $edited_Blog->get( 'allowusercss' ), TB_('Allow user customized CSS file for this blog'), TB_('Users will be able to customize the blog and skin stylesheets with a file named style.css in their personal file folder.') );
		$Form->textarea( 'blog_head_includes', $edited_Blog->get_setting( 'head_includes' ), 5, TB_('Custom meta tag/css section (before &lt;/head&gt;)'),
			TB_('Add custom meta tags and/or css styles to the &lt;head&gt; section. Example use: website verification, Google+, favicon image...'), 50 );
		$Form->textarea( 'blog_body_includes', $edited_Blog->get_setting( 'body_includes' ), 5, TB_('Custom javascript section (after &lt;body&gt;)'),
			TB_('Add custom javascript after the opening &lt;body&gt; tag.<br />Example use: tracking scripts, javascript libraries...'), 50 );
		$Form->textarea( 'blog_footer_includes', $edited_Blog->get_setting( 'footer_includes' ), 5, TB_('Custom javascript section (before &lt;/body&gt;)'),
			TB_('Add custom javascript before the closing &lt;/body&gt; tag in order to avoid any issues with page loading delays for visitors with slow connection speeds.<br />Example use: tracking scripts, javascript libraries...'), 50 );
	$Form->end_fieldset();

}


$Form->end_form( array( array( 'submit', 'submit', TB_('Save Changes!'), 'SaveButton', 'data-shortcut' => 'ctrl+s,command+s,ctrl+enter,command+enter' ) ) );

?>

<script>
	jQuery( 'input[name=ajax_form_enabled]' ).click( function()
	{
		var checked = jQuery( this ).prop( 'checked' );
		jQuery( 'input[name=ajax_form_loggedin_enabled]' ).prop( 'disabled', ! checked );
		if( ! checked )
		{
			jQuery( 'input[name=cache_enabled]' ).prop( 'checked', false );
		}
	} );
	jQuery( '#cache_enabled' ).click( function()
	{
		if( jQuery( this ).prop( 'checked' ) )
		{
			jQuery( 'input[name=ajax_form_enabled]' ).prop( 'checked', true );
			jQuery( 'input[name=ajax_form_loggedin_enabled]' ).prop( 'disabled', false );
		}
	} );
	jQuery( '#advanced_perms' ).click( function()
	{
		if( ! jQuery( this ).is( ':checked' ) && jQuery( 'input[name=blog_allow_access][value=members]' ).is( ':checked' ) )
		{
			jQuery( 'input[name=blog_allow_access][value=users]' ).attr( 'checked', true );
		}
	} );
	jQuery( 'input[name=blog_allow_access][value=members]' ).click( function()
	{
		if( jQuery( this ).is( ':checked' ) )
		{
			jQuery( '#advanced_perms' ).attr( 'checked', true );
		}
	} );

	function update_blog_media_url_preview()
	{
		var url_preview = '';
		switch( jQuery( 'input[name=blog_media_location]:checked' ).val() )
		{
			case 'default':
				url_preview = '<?php echo format_to_js( $edited_Blog->get_local_media_url().'blogs/'.$edited_Blog->urlname.'/' ); ?>';
				break;
			case 'subdir':
				url_preview = '<?php echo format_to_js( $edited_Blog->get_local_media_url() ); ?>' + jQuery( 'input[name=blog_media_subdir]' ).val();
				break;
			case 'custom':
				url_preview = jQuery( 'input[name=blog_media_url]' ).val();
				switch( '<?php echo $edited_Blog->get_setting( 'http_protocol' ) ?>' )
				{	// Force base URL to http or https for the edited collection:
					case 'always_http':
						url_preview = url_preview.replace( /^https:/, 'http:' );
						break;
					case 'always_https':
						url_preview = url_preview.replace( /^http:/, 'https:' );
						break;
				}
				break;
		}
		jQuery( '#blog_media_url_preview' ).html( url_preview );
	}
	jQuery( 'input[name=blog_media_location]' ).click( function() { update_blog_media_url_preview(); } );
	jQuery( 'input[name=blog_media_subdir], input[name=blog_media_url]' ).keyup( function() { update_blog_media_url_preview(); } );
