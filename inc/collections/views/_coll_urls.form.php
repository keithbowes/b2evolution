<?php
/**
 * This file implements the UI view for the collection URL properties.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog;
/**
 * @var GeneralSettings
 */
global $Settings;
/**
 * @var Log
 */
global $Debuglog;

global $admin_url;

?>
<script>
	function show_hide_chapter_prefix(ob)
	{
		var fldset = document.getElementById( 'category_prefix_container' );
		if( ob.value == 'param_num' )
		{
			fldset.style.display = 'none';
		}
		else
		{
			fldset.style.display = '';
		}
	}
</script>


<?php

global $blog, $tab;

global $preset;

$Form = new Form();

$Form->begin_form( 'fform' );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', $tab );
$Form->hidden( 'blog', $blog );


global $baseurl, $baseprotocol, $basehost, $baseport;

// determine siteurl type (if not set from update-action)
if( preg_match('#https?://#', $edited_Blog->get( 'siteurl' ) ) )
{ // absolute
	$blog_siteurl_relative = '';
	$blog_siteurl_absolute = $edited_Blog->get( 'siteurl' );
}
else
{ // relative
	$blog_siteurl_relative = $edited_Blog->get( 'siteurl' );
	$blog_siteurl_absolute = 'http://';
}

$Form->begin_fieldset( TB_('Collection base URL').get_admin_badge().get_manual_link('collection-base-url-settings') );

	$http_protocol_options = array(
			array( 'always_http', sprintf( TB_('Always use %s'), '<code>http</code>' ) ),
			array( 'always_https', sprintf( TB_('Always use %s'), '<code>https</code>' ) ),
			array( 'allow_both', sprintf( TB_('Allow both %s and %s as valid URLs'), '<code>http</code>', '<code>https</code>' ) )
		);

	if( check_user_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
	{	// Permission to edit advanced admin settings

		$Form->radio( 'http_protocol', $edited_Blog->get_setting( 'http_protocol' ), $http_protocol_options, TB_('SSL'), true );

		$Form->text( 'blog_urlname', $edited_Blog->get( 'urlname' ), 20, TB_('Collection URL name'), TB_('Used to uniquely identify this collection. Appears in URLs and gets used as default for the media location (see the advanced tab).'), 255 );

		if( $default_blog_ID = $Settings->get('default_blog_ID') )
		{
			$Debuglog->add('Default collection is set to: '.$default_blog_ID);
			$BlogCache = & get_BlogCache();
			if( $default_Blog = & $BlogCache->get_by_ID($default_blog_ID, false) )
			{ // Default blog exists
				$defblog = $default_Blog->dget('shortname');
			}
		}

		$siteurl_relative_warning = '';
		if( ! preg_match( '~(^|/|\.php.?)$~i', $blog_siteurl_relative ) )
		{
			$siteurl_relative_warning = ' <span class="note red">'.TB_('WARNING: it is highly recommended that this ends in with a / or .php !').'</span>';
		}

		$siteurl_absolute_warning = '';
		if( ! preg_match( '~(^|/|\.php.?)$~i', $blog_siteurl_absolute ) )
		{
			$siteurl_absolute_warning = ' <span class="note red">'.TB_('WARNING: it is highly recommended that this ends in with a / or .php !').'</span>';
		}

		// Initialize html code which is used below to display and update on switching between http and https protocols:
		$baseurl_html = '<span data-protocol-url="'.format_to_output( $baseurl, 'htmlattr' ).'">'.$edited_Blog->get_protocol_url( $baseurl ).'</span>';

		$access_type_options = array(
			/* TODO: Tblue> This option only should be available if the
			 *              current blog is set as the default blog, otherwise
			 *              this setting is confusing. Another possible
			 *              solution would be to change the default blog
			 *              setting if this blog-specific setting is changed,
			 *              but then we would be have the same setting in
			 *              two places... I would be in favor of the first
			 *              solution.
			 * fp> I think it should actually change the default blog setting because
			 * people have a hard time finding the settings. I personally couldn't care
			 * less that there are 2 ways to do the same thing.
			 */
			array( 'baseurl', TB_('Default collection on baseurl'),
											'<code>'.$baseurl_html.'</code> ('.( !isset($defblog)
												?	/* TRANS: NO current default blog */ TB_('No default collection is currently set')
												: /* TRANS: current default blog */ TB_('Current default :').' '.$defblog ).
											')',
										'',
										'onclick="update_urlpreview( \''.$baseurl.'\', \'\' );"'
			),
			array( 'default', TB_('Default collection in index.php'),
											'<code>'.$baseurl_html.'index.php</code> ('.( !isset($defblog)
												?	/* TRANS: NO current default blog */ TB_('No default collection is currently set')
												: /* TRANS: current default blog */ TB_('Current default :').' '.$defblog ).
											')',
										'',
										'onclick="update_urlpreview( \''.$baseurl.'\', \'index.php\' );"'
			),
			array( 'index.php', TB_('Explicit param on index.php'),
										'<code>'.$baseurl_html.'index.php?blog='.$edited_Blog->ID.'</code>',
										'',
										'onclick="update_urlpreview( \''.$baseurl.'\', \'index.php?blog='.$edited_Blog->ID.'\' )"',
			),
			array( 'extrabase', TB_('Extra path on baseurl'),
										'<code>'.$baseurl_html.'<span class="blog_url_text">'.$edited_Blog->get( 'urlname' ).'</span>/</code> ('.TB_('Requires mod_rewrite').')',
										'',
										'onclick="update_urlpreview( \''.$baseurl.'\', document.getElementById( \'blog_urlname\' ).value+\'/\' )"'
			),
			array( 'extrapath', TB_('Extra path on index.php'),
										'<code>'.$baseurl_html.'index.php/<span class="blog_url_text">'.$edited_Blog->get( 'urlname' ).'</span>/</code>',
										'',
										'onclick="update_urlpreview( \''.$baseurl.'\', \'index.php/\'+document.getElementById( \'blog_urlname\' ).value+\'/\' )"'
			),
			array( 'relative', TB_('Relative to baseurl').': ',
										'',
										'<span class="nobr help-inline"><code>'.$baseurl_html.'</code>'
										.'<input type="text" id="blog_siteurl_relative" class="form_text_input form-control" name="blog_siteurl_relative" size="35" maxlength="120" value="'
										.format_to_output( $blog_siteurl_relative, 'formvalue' )
										.'" onkeyup="update_urlpreview( \''.$baseurl.'\', this.value );"
										onfocus="document.getElementsByName(\'blog_access_type\')[5].checked=true;
										update_urlpreview( \''.$baseurl.'\', this.value );" /></span>'.$siteurl_relative_warning,
										'onclick="document.getElementById( \'blog_siteurl_relative\' ).focus();" class="radio-input"',
			)
		);
		if( ! is_valid_ip_format( $basehost ) )
		{	// Not an IP address, we can use subdomains:
			$access_type_options[] = array( 'subdom', TB_('Subdomain of basehost'),
										'<code><span data-protocol-url="'.format_to_output( $baseprotocol.'://', 'htmlattr' ).'">'.$edited_Blog->get_protocol_url( $baseprotocol.'://' ).'</span><span class="blog_url_text">'.$edited_Blog->urlname.'</span>.'.$basehost.$baseport.'/</code>',
										'',
										'onclick="update_urlpreview( \''.$baseprotocol.'://\'+document.getElementById( \'blog_urlname\' ).value+\'.'.$basehost.$baseport.'/\' )"'
			);
		}
		else
		{ // Don't allow subdomain for IP address:
			$access_type_options[] = array( 'subdom', TB_('Subdomain').':',
										sprintf( TB_('(Not possible for %s)'), $basehost ),
										'',
										'disabled="disabled"'
			);
		}
		$access_type_options[] = array( 'absolute', TB_('Absolute URL').':',
										'',
										'<input type="text" id="blog_siteurl_absolute" class="form_text_input form-control" name="blog_siteurl_absolute" size="50" maxlength="120" value="'
											.format_to_output( $blog_siteurl_absolute, 'formvalue' )
											.'" onkeyup="update_urlpreview( this.value );"
											onfocus="document.getElementsByName(\'blog_access_type\')[7].checked=true;
											update_urlpreview( this.value );" />'.$siteurl_absolute_warning,
										'onclick="document.getElementById( \'blog_siteurl_absolute\' ).focus();" class="radio-input"'
		);

		$Form->radio( 'blog_access_type', $edited_Blog->get( 'access_type' ), $access_type_options, TB_('Collection base URL'), true );

?>
<script>
// Script to update the Blog URL preview:
function update_urlpreview( baseurl, url_path )
{
	if( typeof( url_path ) != 'string' )
	{
		url_path = '';
	}
	if( ! baseurl.match( /\/[^\/]+\.[^\/]+\/$/ ) )
	{
		baseurl = baseurl.replace( /\/$/, '' ) + '/';
	}
	jQuery( '#urlpreview' ).html( baseurl + url_path );

	var basepath = baseurl.replace( /^(.+\/)([^\/]+\.[^\/]+)?$/, '$1' );
	basepath = basepath.replace( /^(https?:\/\/(.+?)(:.+?)?)\//i, '/' );

	jQuery( '#media_assets_url_type_relative' ).html( baseurl + 'media/' );
	jQuery( '#rsc_assets_url_type_relative' ).html( basepath + 'rsc/' );
	jQuery( '#skins_assets_url_type_relative' ).html( basepath + 'skins/' );
	jQuery( '#plugins_assets_url_type_relative' ).html( basepath + 'plugins/' );
	jQuery( '#htsrv_assets_url_type_relative' ).html( baseurl + 'htsrv/' );

	// Update data with protocol urls in order to display them on select "SSL" == "Allow both http and https as valid URLs":
	jQuery( '#urlpreview' ).data( 'protocol-url', baseurl + url_path );
	jQuery( '#media_assets_url_type_relative' ).data( 'protocol-url', baseurl + 'media/' );
	jQuery( '#htsrv_assets_url_type_relative' ).data( 'protocol-url', baseurl + 'htsrv/' );

	// Update protocols of the urls:
	force_http_protocols();
}

// Update blog url name in several places on the page:
jQuery( '#blog_urlname' ).bind( 'keyup blur', function()
{
	jQuery( '.blog_url_text' ).html( jQuery( this ).val() );
	var blog_access_type_obj = jQuery( 'input[name=blog_access_type]:checked' );
	if( blog_access_type_obj.length > 0 &&
	    ( blog_access_type_obj.val() == 'extrabase' || blog_access_type_obj.val() == 'extrapath' || blog_access_type_obj.val() == 'subdom' ) )
	{
		blog_access_type_obj.click();
	}
} );

// Select 'absolute' option when cursor is focused on input element
jQuery( '[id$=_assets_absolute_url]' ).focus( function()
{
	var radio_field_name = jQuery( this ).attr( 'id' ).replace( '_absolute_url', '_url_type' );
	jQuery( '[name=' + radio_field_name + ']' ).attr( 'checked', 'checked' );
} );

// Update blog urls depending on selected setting "SSL":
jQuery( '[name=http_protocol]' ).click( function()
{
	force_http_protocols();
} );
function force_http_protocols()
{
	jQuery( '[data-protocol-url]' ).each( function()
	{
		var url = jQuery( this ).html();
		switch( jQuery( '[name=http_protocol]:checked' ).val() )
		{	// Force base URL to http or https for the edited collection:
			case 'always_http':
				url = url.replace( /^https:/, 'http:' );
				break;
			case 'always_https':
				url = url.replace( /^http:/, 'https:' );
				break;
			case 'allow_both':
				url = jQuery( this ).data( 'protocol-url' );
				break;
		}
		jQuery( this ).html( url );
	} );
}
</script>
<?php

	}
	else
	{	// Display only current values as text if user has no permission to edit:
		$current_http_protocol_option = '';
		foreach( $http_protocol_options as $http_protocol_option )
		{
			if( $http_protocol_option[0] == $edited_Blog->get_setting( 'http_protocol' ) )
			{	// Get title of current option:
				$current_http_protocol_option = $http_protocol_option[1];
				break;
			}
		}
		$Form->info( TB_('SSL'), $current_http_protocol_option );
	}

	// URL Preview (always displayed)
	$blogurl = $edited_Blog->gen_blogurl();
	$Form->info( TB_('URL preview'), '<code id="urlpreview" data-protocol-url="'.format_to_output( $blogurl, 'htmlattr' ).'">'.$blogurl.'</code>' );

	$url_aliases = $edited_Blog->get_url_aliases();
	$alias_field_note = get_icon( 'add', 'imgtag', array( 'class' => 'url_alias_add', 'style' => 'cursor: pointer; position: relative;' ) );
	$alias_field_note .= get_icon( 'minus', 'imgtag', array( 'class' => 'url_alias_minus', 'style' => 'display: none; margin-left: 2px; cursor: pointer; position: relative;' ) );
	if( empty( $url_aliases ) )
	{
		$Form->text_input( 'blog_url_alias[]', '', 50, TB_('Alias URL'), $alias_field_note, array( 'class' => 'evo_url_alias', 'maxlength' => 255 ) );
	}

	foreach( $url_aliases as $alias )
	{
		$Form->text_input( 'blog_url_alias[]', $alias, 50, TB_('Alias URL'), $alias_field_note, array( 'class' => 'evo_url_alias', 'maxlength' => 255 ) );
	}

$Form->end_fieldset();


$Form->begin_fieldset( TB_('Cookie Settings').get_admin_badge().get_manual_link( 'collection-cookie-settings' ) );

	if( check_user_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
	{	// If current user has a permission to edit collection advanced admin settings:
		$Form->switch_layout( 'none' );
		$Form->output = false;
		$cookie_domain_custom_field = $Form->text( 'cookie_domain_custom', $edited_Blog->get_setting( 'cookie_domain_custom' ), 50, '', '', 120 );
		$cookie_path_custom_field = $Form->text( 'cookie_path_custom', $edited_Blog->get_setting( 'cookie_path_custom' ), 50, '', '', 120 );
		$Form->output = true;
		$Form->switch_layout( NULL );

		$Form->radio( 'cookie_domain_type', $edited_Blog->get_setting( 'cookie_domain_type' ), array(
				array( 'auto', TB_('Automatic'), $edited_Blog->get_cookie_domain( 'auto' ) ),
				array( 'custom', TB_('Custom').':', '', $cookie_domain_custom_field, 'class="radio-input"' ),
			), TB_('Cookie domain'), true );

		$Form->radio( 'cookie_path_type', $edited_Blog->get_setting( 'cookie_path_type' ), array(
				array( 'auto', TB_('Automatic'), $edited_Blog->get_cookie_path( 'auto' ) ),
				array( 'custom', TB_('Custom').':', '', $cookie_path_custom_field, 'class="radio-input"' ),
			), TB_('Cookie path'), true );
	}
	else
	{	// Display only info about collection cookie domain and path if user has no permission to edit:
		$Form->info( TB_('Cookie domain'), $edited_Blog->get_cookie_domain() );
		$Form->info( TB_('Cookie path'), $edited_Blog->get_cookie_path() );
	}

$Form->end_fieldset();


$Form->begin_fieldset( TB_('Assets URLs / CDN support').get_admin_badge().get_manual_link( 'assets-url-cdn-settings' ) );

	if( check_user_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
	{ // Permission to edit advanced admin settings
		global $rsc_url, $media_url, $skins_url, $plugins_url, $htsrv_url;

		$assets_url_data = array();
		// media url:
		$assets_url_data['media_assets_url_type'] = array(
				'label'        => sprintf( TB_('Load %s assets from'), '<code>/media/</code>' )
			);
		if( $edited_Blog->get( 'media_location' ) == 'none' )
		{ // if media location is disabled
			$assets_url_data['media_assets_url_type']['info'] = sprintf( TB_('The media directory is <a %s>turned off</a> for this collection'), 'href="'.$admin_url.'?ctrl=coll_settings&amp;tab=advanced&amp;blog='.$edited_Blog->ID.'#media_dir_location"' );
		}
		elseif( $edited_Blog->get( 'media_location' ) == 'custom' )
		{ // if media location is customized
			$assets_url_data['media_assets_url_type']['info'] = sprintf( TB_('A custom location has already been set in the <a %s>advanced properties</a>'), 'href="'.$admin_url.'?ctrl=coll_settings&amp;tab=advanced&amp;blog='.$edited_Blog->ID.'"' );
		}
		else
		{
			$assets_url_data['media_assets_url_type'] += array(
					'url'          => $media_url,
					'absolute_url' => 'media_assets_absolute_url',
					'folder'       => '/media/',
					'local_url'    => $edited_Blog->get_local_media_url( 'relative', true )
				);
		}
		// skins url:
		$assets_url_data['skins_assets_url_type'] = array(
				'label'        => sprintf( TB_('Load %s assets from'), '<code>/skins/</code>' ),
				'url'          => $skins_url,
				'absolute_url' => 'skins_assets_absolute_url',
				'folder'       => '/skins/',
				'local_url'    => $edited_Blog->get_local_skins_url( 'relative' )
			);
		// rsc url:
		$assets_url_data['rsc_assets_url_type'] = array(
				'label'        => sprintf( TB_('Load generic %s assets from'), '<code>/rsc/</code>' ),
				'url'          => $rsc_url,
				'absolute_url' => 'rsc_assets_absolute_url',
				'folder'       => '/rsc/',
				'local_url'    => $edited_Blog->get_local_rsc_url( 'relative' )
			);
		// plugins url:
		$assets_url_data['plugins_assets_url_type'] = array(
				'label'        => sprintf( TB_('Load %s assets from'), '<code>/plugins/</code>' ),
				'url'          => $plugins_url,
				'absolute_url' => 'plugins_assets_absolute_url',
				'folder'       => '/plugins/',
				'local_url'    => $edited_Blog->get_local_plugins_url( 'relative' )
			);
		// htsrv url:
		$assets_url_data['htsrv_assets_url_type'] = array(
				'label'        => sprintf( TB_('Link to %s through'), '<code>/htsrv/</code>' ),
				'url'          => $htsrv_url,
				'absolute_url' => 'htsrv_assets_absolute_url',
				'folder'       => '/htsrv/',
				'local_url'    => $edited_Blog->get_htsrv_url()
			);

		foreach( $assets_url_data as $asset_url_type => $asset_url_data )
		{
			if( isset( $asset_url_data['info'] ) )
			{ // Display only info for this url type
				$Form->info( $asset_url_data['label'], $asset_url_data['info'] );
			}
			else
			{ // Display options full list
				$basic_asset_url_note = '<span data-protocol-url="'.format_to_output( $asset_url_data['url'], 'htmlattr' ).'">'.$edited_Blog->get_protocol_url( $asset_url_data['url'] ).'</span>';
				if( ! in_array( $asset_url_type, array( 'media_assets_url_type', 'htsrv_assets_url_type' ) ) &&
				    $edited_Blog->get( 'access_type' ) == 'absolute' &&
				    $edited_Blog->get_setting( $asset_url_type ) == 'basic' )
				{
					$basic_asset_url_note .= ' <span class="red">'
							.sprintf( TB_('ATTENTION: Using a different domain for your collection and your %s folder may cause problems'), '<code>'.$asset_url_data['folder'].'</code>' )
							.' ('.( $asset_url_type == 'plugins_assets_url_type' ? TB_('e-g: Ajax requests') : TB_('e-g: impossible to load fonts') ).')'
						.'</span>';
				}

				$relative_asset_url_note = '<span id="'.$asset_url_type.'_relative" data-protocol-url="'.format_to_output( $asset_url_data['local_url'], 'htmlattr' ).'">'.$edited_Blog->get_protocol_url( $asset_url_data['local_url'] ).'</span>';
				if( ! in_array( $asset_url_type, array( 'skins_assets_url_type', 'media_assets_url_type', 'htsrv_assets_url_type' ) ) &&
				    $edited_Blog->get_setting( 'skins_assets_url_type' ) != 'relative' &&
				    $edited_Blog->get_setting( $asset_url_type ) == 'relative' )
				{
					$relative_asset_url_note .= ' <span class="red">'
							.sprintf( TB_('ATTENTION: using a relative %s folder with a non-relative %s folder will probably lead to undesired results (because of the skin\'s &lt;baseurl&gt;).'), '<code>'.$asset_url_data['folder'].'</code>', '<code>/skins/</code>' )
						.'</span>';
				}

				$absolute_url_note = TB_('Enter path to %s folder ending with /');
				if( ! in_array( $asset_url_type, array( 'plugins_assets_url_type', 'htsrv_assets_url_type' ) ) )
				{
					$absolute_url_note .= ' -- '.TB_('This may be located in a CDN zone');
				}
				$Form->radio( $asset_url_type, $edited_Blog->get_setting( $asset_url_type ), array(
					array( 'relative', (
							in_array( $asset_url_type, array( 'skins_assets_url_type', 'media_assets_url_type', 'htsrv_assets_url_type' ) ) ?
							sprintf( TB_('%s folder relative to current collection (recommended setting)'), '<code>'.$asset_url_data['folder'].'</code>' ) :
							sprintf( TB_('%s folder relative to %s domain (recommended setting)'), '<code>'.$asset_url_data['folder'].'</code>', '<code>/skins/</code>' )
						), $relative_asset_url_note ),
					array( 'basic', TB_('URL configured in Basic Config'), $basic_asset_url_note ),
					array( 'absolute', TB_('Absolute URL').':', '',
						'<input type="text" id="'.$asset_url_data['absolute_url'].'" class="form_text_input form-control" name="'.$asset_url_data['absolute_url'].'"
						size="50" maxlength="120" onfocus="document.getElementsByName(\''.$asset_url_type.'\')[2].checked=true;" value="'.$edited_Blog->get_setting( $asset_url_data['absolute_url'] ).'" />
						<span class="notes">'.sprintf( $absolute_url_note, '<code>'.$asset_url_data['folder'].'</code>' ).'</span>',
						'class="radio-input"'
					)
				), $asset_url_data['label'], true );
			}
		}
	}
	else
	{	// Preview assets urls:
		$Form->info( sprintf( TB_('Load %s assets from'), '<code>/media/</code>' ), $edited_Blog->get_local_media_url() );
		$Form->info( sprintf( TB_('Load %s assets from'), '<code>/skins/</code>' ), $edited_Blog->get_local_skins_url() );
		$Form->info( sprintf( TB_('Load generic %s assets from'), '<code>/rsc/</code>' ), $edited_Blog->get_local_rsc_url() );
		$Form->info( sprintf( TB_('Load %s assets from'), '<code>/plugins/</code>' ), $edited_Blog->get_local_plugins_url() );
		$Form->info( sprintf( TB_('Link to %s through'), '<code>/htsrv/</code>' ), $edited_Blog->get_local_htsrv_url() );
	}

	$Form->info( 'Note', sprintf( TB_('Login, Registration and Password operations are controlled by the following settings: <a %s>In-skin login</a> and <a %s>Require SSL for login</a>'),
		'href="'.$admin_url.'?ctrl=coll_settings&amp;tab=advanced&amp;blog='.$edited_Blog->ID.'#inskin_actions"',
		'href="'.$admin_url.'?ctrl=registration#security_options"' ) );

$Form->end_fieldset();


$Form->begin_fieldset( TB_('Date archive URLs').get_manual_link('date-archive-url-settings')  );

	$Form->radio( 'archive_links', $edited_Blog->get_setting('archive_links'),
		array(
				array( 'param', TB_('Use param'), TB_('E-g: ')
								.url_add_param( $blogurl, '<strong>m=20071231</strong>' ) ),
				array( 'extrapath', TB_('Use extra-path'), TB_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/2007/12/31/</strong>' ) ),
			), TB_('Date archive URLs'), true );

$Form->end_fieldset();


$Form->begin_fieldset( TB_('Category URLs') . get_manual_link('category-url-settings') );

	$Form->radio( 'chapter_links', $edited_Blog->get_setting('chapter_links'),
		array(
				array( 'param_num', TB_('Use param: cat ID'), TB_('E-g: ')
								.url_add_param( $blogurl, '<strong>cat=123</strong>' ),'', 'onclick="show_hide_chapter_prefix(this);"'),
				array( 'subchap', TB_('Use extra-path: sub-category'), TB_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/subcat/</strong>' ), '', 'onclick="show_hide_chapter_prefix(this);"' ),
				array( 'chapters', TB_('Use extra-path: category path'), TB_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/cat/subcat/</strong>' ), '', 'onclick="show_hide_chapter_prefix(this);"' ),
			), TB_('Category URLs'), true );


		echo '<div id="category_prefix_container">';
			$Form->text_input( 'category_prefix', $edited_Blog->get_setting( 'category_prefix' ), 30, TB_('Prefix'),
														TB_('An optional prefix to be added to the URLs of the categories'),
														array('maxlength' => 120) );
		echo '</div>';

		if( $edited_Blog->get_setting( 'chapter_links' ) == 'param_num' )
		{ ?>
		<script>
			<!--
			var fldset = document.getElementById( 'category_prefix_container' );
			fldset.style.display = 'none';
			//-->
		</script>
		<?php
		}

$Form->end_fieldset();


$Form->begin_fieldset( TB_('Tag page URLs') . get_manual_link('tag-page-url-settings'), array('id'=>'tag_links_fieldset') );

	$Form->radio( 'tag_links', $edited_Blog->get_setting('tag_links'),
		array(
			array( 'param', TB_('Use param'), TB_('E-g: ')
				.url_add_param( $blogurl, '<strong>tag=mytag</strong>' ) ),
			array( 'prefix-only', TB_('Use extra-path').': '.'Use URL path prefix only (recommended)', TB_('E-g: ')
				.url_add_tail( $blogurl, '<strong>/<span class="tag_links_tag_prefix"></span>mytag</strong>' ) ),
			array( 'dash', TB_('Use extra-path').': '.'trailing dash', TB_('E-g: ')
				.url_add_tail( $blogurl, '<strong>/<span class="tag_links_tag_prefix"></span>mytag-</strong>' ) ),
			array( 'colon', TB_('Use extra-path').': '.'trailing colon', TB_('E-g: ')
				.url_add_tail( $blogurl, '<strong>/<span class="tag_links_tag_prefix"></span>mytag:</strong>' ) ),
			array( 'semicolon', TB_('Use extra-path').': '.'trailing semi-colon (NOT recommended)', TB_('E-g: ')
				.url_add_tail( $blogurl, '<strong>/<span class="tag_links_tag_prefix"></span>mytag;</strong>' ) ),
		), TB_('Tag page URLs'), true );


	$Form->text_input( 'tag_prefix', $edited_Blog->get_setting( 'tag_prefix' ), 30, TB_('Prefix'),
		TB_('An optional prefix to be added to the URLs of the tag pages'),
		array('maxlength' => 120) );

	$Form->checkbox( 'tag_rel_attrib', $edited_Blog->get_setting( 'tag_rel_attrib' ), TB_('Rel attribute'),
		sprintf( TB_('Add <a %s>rel="tag" attribute</a> to tag links.'), 'href="http://microformats.org/wiki/rel-tag"' ) );

$Form->end_fieldset();

// Javascript juice for the tag fields.
?>
<script>
jQuery( '#tag_links_fieldset input[type=radio]' ).click( function()
{
	if( jQuery( this ).val() == 'param' )
	{ // Disable tag_prefix, if "param" is used.
		jQuery( '#tag_prefix' ).attr( 'disabled', 'disabled' );
	}
	else
	{
		jQuery( '#tag_prefix' ).removeAttr( 'disabled' );
	}

	if( jQuery( this ).val() == 'prefix-only' )
	{ // Enable tag_rel_attrib, if "prefix-only" is used.
		jQuery( '#tag_rel_attrib' ).removeAttr( 'disabled' );
	}
	else
	{
		jQuery( '#tag_rel_attrib' ).attr( 'disabled', 'disabled' );
	}

	// NOTE: dh> ".closest('fieldset').andSelf()" is required for the add-field_required-class-to-fieldset-hack. Remove as appropriate.
	if( jQuery( this ).val() == 'prefix-only' )
	{
		jQuery( '#tag_prefix' ).closest( 'fieldset' ).andSelf().addClass( 'field_required' );
	}
	else
	{
		jQuery( '#tag_prefix' ).closest( 'fieldset' ).andSelf().removeClass( 'field_required' );
	}
} ).filter( ':checked' ).click();

// Set text of span.tag_links_tag_prefix according to this field, defaulting to "tag" for "prefix-only".
jQuery("#tag_prefix").keyup( function() {
	jQuery("span.tag_links_tag_prefix").each(
		function() {
			var newval = ((jQuery("#tag_prefix").val().length || jQuery(this).closest("div").find("input[type=radio]").attr("value") != "prefix-only") ? jQuery("#tag_prefix").val() : "tag");
			if( newval.length ) newval += "/";
			jQuery(this).text( newval );
		}
	) } ).keyup();
</script>


<?php
$Form->begin_fieldset( TB_('User profile page URLs') . get_manual_link('user-profile-page-url-settings') );

	$Form->text_input( 'user_prefix', $edited_Blog->get_setting( 'user_prefix' ), 30, TB_('Prefix'),
		TB_('A prefix to be added to the URLs of the user profile pages'),
		array( 'maxlength' => 120 ) );

	$Form->radio( 'user_links', $edited_Blog->get_setting( 'user_links' ),
		array(
			array( 'params', TB_('Use params'), TB_('E-g: ').'<code>?disp=user&user_ID=4</code>' ),
			array( 'prefix_id', TB_('Use prefix with user ID'), TB_('E-g: ').'<code>prefix:4</code>' ),
			array( 'prefix_login', TB_('Use prefix with user login'), TB_('E-g: ').'<code>prefix:login</code>' ),
		), TB_('User profile URLs'), true );

$Form->end_fieldset();

$Form->begin_fieldset( TB_('Single post URLs') . get_manual_link('single-post-url-settings') );

	$Form->radio( 'single_links', $edited_Blog->get_setting('single_links'),
		array(
			  array( 'param_num', TB_('Use param: post ID'), TB_('E-g: ')
			  				.url_add_param( $blogurl, '<strong>p=123&amp;more=1</strong>' ) ),
			  array( 'param_title', TB_('Use param: post title'), TB_('E-g: ')
			  				.url_add_param( $blogurl, '<strong>title=post-title&amp;more=1</strong>' ) ),
				array( 'short', TB_('Use extra-path: post title'), TB_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/post-title</strong>' ) ),
				array( 'y', TB_('Use extra-path: year'), TB_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/2006/post-title</strong>' ) ),
				array( 'ym', TB_('Use extra-path: year & month'), TB_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/2006/12/post-title</strong>' ) ),
				array( 'ymd', TB_('Use extra-path: year, month & day'), TB_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/2006/12/31/post-title</strong>' ) ),
				array( 'subchap', TB_('Use extra-path: sub-category'), TB_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/subcat/post-title</strong>' ) ),
				array( 'chapters', TB_('Use extra-path: category path'), TB_('E-g: ')
								.url_add_tail( $blogurl, '<strong>/cat/subcat/post-title</strong>' ) ),
			), TB_('Single post URLs'), true );

$Form->end_fieldset();

$Form->begin_fieldset( TB_('Tiny URLs').get_manual_link('tiny-url-settings') );

// Params for tag settings:
$tag_setting_params = array();
if( ! is_pro() )
{	// Disable for not-PRO version:
	$tag_setting_params['disabled'] = 'disabled';
}
$tag_setting_input_params = array_merge( $tag_setting_params, array( 'maxlength' => 255 ) );

$Form->switch_layout( 'none' );
$Form->output = false;
$tinyurl_slug = 'aA1';
$tinyurl_domain = 'http://tiny.url/';
$tinyurl_domain_field = $Form->text( 'tinyurl_domain', $edited_Blog->get_setting( 'tinyurl_domain' ), 20, '', '', 120 );
$tinyurl_domain_note = '<span class="notes">'.sprintf( TB_('Enter absolute URL ending with /, e-g: %s.'), '<code>'.$tinyurl_domain.'</code>' ).'</span>';
$tag_source_field = $Form->text_input( 'tinyurl_tag_source', $edited_Blog->get_setting( 'tinyurl_tag_source' ), 20, '', '', $tag_setting_input_params );
$tag_slug_field = $Form->text_input( 'tinyurl_tag_slug', $edited_Blog->get_setting( 'tinyurl_tag_slug' ), 20, '', '', $tag_setting_input_params );
$tag_extra_term_field = $Form->text_input( 'tinyurl_tag_extra_term', $edited_Blog->get_setting( 'tinyurl_tag_extra_term' ), 20, '', '', $tag_setting_input_params );
$Form->output = true;
$Form->switch_layout( NULL );

$Form->radio( 'tinyurl_type', $edited_Blog->get_setting( 'tinyurl_type' ), array(
		array( 'basic', TB_('Basic: Append to collection URL'), TB_('E-g: ')
					 .url_add_tail( $blogurl, '/'.$tinyurl_slug ) ),
		array( 'advanced', TB_('Advanced: Append to special domain URL').':', '', $tinyurl_domain_field.$tinyurl_domain_note, 'class="radio-input"' ),
	), TB_('Tiny URLs'), true );

$Form->begin_line( TB_('Tag source') );
	$Form->checkbox_input( 'tinyurl_tag_source_enabled', $edited_Blog->get_setting( 'tinyurl_tag_source_enabled' ), '', $tag_setting_params );
	printf( TB_('use param %s to record referer domain -> source tag'), $tag_source_field );
	echo ' '.get_pro_label();
$Form->end_line();

$Form->begin_line( TB_('Tag slug') );
	$Form->checkbox_input( 'tinyurl_tag_slug_enabled', $edited_Blog->get_setting( 'tinyurl_tag_slug_enabled' ), '', $tag_setting_params );
	printf( TB_('use param %s to record the tiny slug'), $tag_slug_field );
	echo ' '.get_pro_label();
$Form->end_line();

$Form->begin_line( TB_('Tag extra term') );
	$Form->checkbox_input( 'tinyurl_tag_extra_term_enabled', $edited_Blog->get_setting( 'tinyurl_tag_extra_term_enabled' ), '', $tag_setting_params );
	printf( TB_('use param %s to record an extra keyword'), $tag_extra_term_field );
	echo ' <span class="note">'.TB_('E-g: ').'<code>/tinyslug+extra-term/</code>'.'</span>';
	echo ' '.get_pro_label();
$Form->end_line();

$Form->end_fieldset();


$Form->buttons( array( array( 'submit', 'submit', TB_('Save Changes!'), 'SaveButton' ) ) );

$Form->end_form();

?>
<script type="text/javascript">
	function replace_form_params( result, field_id )
	{
		field_id = ( typeof( field_id ) == 'undefined' ? '' : ' id="' + field_id + '"' );
		return result.replace( '#fieldstart#', '<?php echo str_ireplace( '$id$', "' + field_id + '", format_to_js( $Form->fieldstart ) ); ?>' )
			.replace( '#fieldend#', '<?php echo format_to_js( $Form->fieldend ); ?>' )
			.replace( '#labelclass#', '<?php echo format_to_js( $Form->labelclass ); ?>' )
			.replace( '#labelstart#', '<?php echo format_to_js( $Form->labelstart ); ?>' )
			.replace( '#labelend#', '<?php echo format_to_js( $Form->labelend ); ?>' )
			.replace( '#inputstart#', '<?php echo format_to_js( $Form->inputstart ); ?>' )
			.replace( '#inputend#', '<?php echo format_to_js( $Form->inputend ); ?>' );
	}

	jQuery( document ).on( 'click', 'span.url_alias_add', function()
	{
		var this_obj = jQuery( this );
		var params = '<?php
			global $b2evo_icons_type;
			echo empty( $b2evo_icons_type ) ? '' : '&b2evo_icons_type='.$b2evo_icons_type;
			?>';

		jQuery.ajax({
			type: 'GET',
			url: '<?php echo get_htsrv_url();?>anon_async.php',
			data: 'action=get_url_alias_new_field' + params,
			success: function( result )
			{
				result = ajax_debug_clear( result );
				result = replace_form_params( result );

				var cur_fieldset_obj = this_obj.closest( '.form-group' );
				cur_fieldset_obj.after( result );

				jQuery( 'span.url_alias_minus' ).show();
			}
		});
	});

	jQuery( document ).on( 'click', 'span.url_alias_minus', function()
	{
		var this_obj = jQuery( this );
		var cur_fieldset_obj = this_obj.closest( '.form-group' );
		cur_fieldset_obj.remove();

		if( jQuery( 'input.evo_url_alias' ).length == 1 )
		{
			jQuery( 'span.url_alias_minus' ).hide();
		}
	});
</script>
