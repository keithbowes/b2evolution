<?php
/**
 * This file implements creation of DB tables
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004 by Vegar BERG GULDAL - {@link http://funky-m.com/}
 * Parts of this file are copyright (c)2005 by Jason EDGECOMBE
 *
 * @package install
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'users/model/_group.class.php', 'Group' );
load_funcs( 'collections/model/_category.funcs.php' );
load_class( 'users/model/_organization.class.php', 'Organization' );
load_funcs( 'collections/_demo_content.funcs.php' );

/**
 * Used for fresh install
 */
function create_tables()
{
	global $inc_path;

	// Load DB schema from modules
	load_db_schema();

	// Update the progress bar status
	update_install_progress_bar();

	load_funcs('_core/model/db/_upgrade.funcs.php');

	// Alter DB to match DB schema:
	install_make_db_schema_current( true );
}


/**
 * Insert all default data:
 */
function create_default_data()
{
	global $admins_Group, $moderators_Group, $editors_Group, $users_Group, $suspect_Group, $spam_Group, $blogb_Group;
	global $DB, $locales, $current_locale, $baseurl;
	// This will install all sorts of additional things... for testing purposes:
	global $install_test_features, $create_sample_contents;

	// Inserting sample data triggers events: instead of checking if $Plugins is an object there, just use a fake one..
	load_class('plugins/model/_plugins_admin_no_db.class.php', 'Plugins_admin_no_DB' );
	global $Plugins;
	$Plugins = new Plugins_admin_no_DB(); // COPY

	// added in 0.8.7
	task_begin( 'Creating default blacklist entries... ' );
	// This string contains antispam information that is obfuscated because some hosting
	// companies prevent uploading PHP files containing "spam" strings.
	// pre_dump(get_antispam_query());
	$query = get_antispam_query();
	$DB->query( $query );
	task_end();

	task_begin( 'Creating default antispam IP ranges... ' );
	$DB->query( '
		INSERT INTO T_antispam__iprange ( aipr_IPv4start, aipr_IPv4end, aipr_status )
		VALUES ( '.$DB->quote( ip2int( '127.0.0.0' ) ).', '.$DB->quote( ip2int( '127.0.0.255' ) ).', "trusted" ),
			( '.$DB->quote( ip2int( '10.0.0.0' ) ).', '.$DB->quote( ip2int( '10.255.255.255' ) ).', "trusted" ),
			( '.$DB->quote( ip2int( '172.16.0.0' ) ).', '.$DB->quote( ip2int( '172.31.255.255' ) ).', "trusted" ),
			( '.$DB->quote( ip2int( '192.168.0.0' ) ).', '.$DB->quote( ip2int( '192.168.255.255' ) ).', "trusted" )
		' );
	task_end();

	// added in 0.8.9
	task_begin( 'Creating default groups... ' );
	$admins_Group = new Group(); // COPY !
	$admins_Group->set( 'name', 'Administrators' );
	$admins_Group->set( 'level', 10 );
	$admins_Group->set( 'perm_blogs', 'editall' );
	$admins_Group->set( 'perm_stats', 'edit' );
	$admins_Group->set( 'perm_xhtml_css_tweaks', 1 );
	$admins_Group->dbinsert();

	$moderators_Group = new Group(); // COPY !
	$moderators_Group->set( 'name', 'Moderators' );
	$moderators_Group->set( 'level', 8 );
	$moderators_Group->set( 'perm_blogs', 'viewall' );
	$moderators_Group->set( 'perm_stats', 'user' );
	$moderators_Group->set( 'perm_xhtml_css_tweaks', 1 );
	$moderators_Group->dbinsert();

	$editors_Group = new Group(); // COPY !
	$editors_Group->set( 'name', 'Editors' );
	$editors_Group->set( 'level', 6 );
	$editors_Group->set( 'perm_blogs', 'user' );
	$editors_Group->set( 'perm_stats', 'none' );
	$editors_Group->set( 'perm_xhtml_css_tweaks', 1 );
	$editors_Group->dbinsert();

	$users_Group = new Group(); // COPY !
	$users_Group->set( 'name', 'Normal Users' );
	$users_Group->set( 'level', 4 );
	$users_Group->set( 'perm_blogs', 'user' );
	$users_Group->set( 'perm_stats', 'none' );
	$users_Group->dbinsert();

	$suspect_Group = new Group(); // COPY !
	$suspect_Group->set( 'name', 'Misbehaving/Suspect Users' );
	$suspect_Group->set( 'level', 2 );
	$suspect_Group->set( 'perm_blogs', 'user' );
	$suspect_Group->set( 'perm_stats', 'none' );
	$suspect_Group->dbinsert();

	$spam_Group = new Group(); // COPY !
	$spam_Group->set( 'name', 'Spammers/Restricted Users' );
	$spam_Group->set( 'level', 1 );
	$spam_Group->set( 'perm_blogs', 'user' );
	$spam_Group->set( 'perm_stats', 'none' );
	$spam_Group->dbinsert();
	task_end();

	task_begin( 'Creating groups for user field definitions... ' );
	$DB->query( "
		INSERT INTO T_users__fieldgroups ( ufgp_name, ufgp_order )
		VALUES ( 'About me', '1' ),
					 ( 'Instant Messaging', '2' ),
					 ( 'Phone', '3' ),
					 ( 'Web', '4' ),
					 ( 'Address', '5' ),
					 ( 'Administrative', '6' )" );
	task_end();

	task_begin( 'Creating user field definitions... ' );
	// fp> Anyone, please add anything you can think of. It's better to start with a large list that update it progressively.
	$DB->query( "
		INSERT INTO T_users__fielddefs (ufdf_ufgp_ID, ufdf_type, ufdf_name, ufdf_options, ufdf_required, ufdf_visibility, ufdf_duplicated, ufdf_order, ufdf_suggest, ufdf_code, ufdf_icon_name)
		 VALUES ( 1, 'text',   'Micro bio',     NULL, 'recommended', 'unrestricted', 'forbidden', '1',  '0', 'microbio',     'fa fa-info-circle' ),
						( 1, 'word',   'I like',        NULL, 'recommended', 'unrestricted', 'list',      '2',  '1', 'ilike',        'fa fa-thumbs-o-up' ),
						( 1, 'word',   'I don\'t like', NULL, 'recommended', 'unrestricted', 'list',      '3',  '1', 'idontlike',    'fa fa-thumbs-o-down' ),
						( 1, 'list',   'Industry',      'Energy, Utilities & Resources
Financial Services
Health Services
Hospitality & Tourism
Industrial Manufacturing
Pharma & Life Sciences
Public Sector
Real Estate
Retail & Consumer Goods
Sports Business Advisory
Technology, Media & Telecom',                     'recommended', 'unrestricted', 'allowed',   '4',  '1', 'industry',     'fa fa-industry' ),
						( 2, 'email',  'MSN/Live IM',   NULL, 'optional',    'unrestricted', 'allowed',   '1',  '0', 'msnliveim',    NULL ),
						( 2, 'word',   'Yahoo IM',      NULL, 'optional',    'unrestricted', 'allowed',   '2',  '0', 'yahooim',      'fa fa-yahoo' ),
						( 2, 'word',   'AOL AIM',       NULL, 'optional',    'unrestricted', 'allowed',   '3',  '0', 'aolaim',       NULL ),
						( 2, 'number', 'ICQ ID',        NULL, 'optional',    'unrestricted', 'allowed',   '4',  '0', 'icqid',        NULL ),
						( 2, 'phone',  'Skype',         NULL, 'optional',    'private',      'allowed',   '5',  '0', 'skype',        'fa fa-skype' ),
						( 2, 'phone',  'WhatsApp',      NULL, 'optional',    'private',      'allowed',   '6',  '0', 'whatsapp',     'fa fa-whatsapp' ),
						( 3, 'phone',  'Main phone',    NULL, 'optional',    'private',      'forbidden', '1',  '0', 'mainphone',    'fa fa-phone' ),
						( 3, 'phone',  'Cell phone',    NULL, 'optional',    'private',      'allowed',   '2',  '0', 'cellphone',    'fa fa-mobile-phone' ),
						( 3, 'phone',  'Office phone',  NULL, 'optional',    'private',      'allowed',   '3',  '0', 'officephone',  'fa fa-phone' ),
						( 3, 'phone',  'Home phone',    NULL, 'optional',    'private',      'allowed',   '4',  '0', 'homephone',    'fa fa-phone' ),
						( 3, 'phone',  'Office FAX',    NULL, 'optional',    'private',      'allowed',   '5',  '0', 'officefax',    'fa fa-fax' ),
						( 3, 'phone',  'Home FAX',      NULL, 'optional',    'private',      'allowed',   '6',  '0', 'homefax',      'fa fa-fax' ),
						( 4, 'url',    'Twitter',       NULL, 'recommended', 'unrestricted', 'forbidden', '1',  '0', 'twitter',      'fa fa-twitter' ),
						( 4, 'url',    'Facebook',      NULL, 'recommended', 'unrestricted', 'forbidden', '2',  '0', 'facebook',     'fa fa-facebook' ),
						( 4, 'url',    'Linkedin',      NULL, 'optional',    'unrestricted', 'forbidden', '4',  '0', 'linkedin',     'fa fa-linkedin fa-x-linkedin--nudge' ),
						( 4, 'url',    'GitHub',        NULL, 'optional',    'unrestricted', 'forbidden', '5',  '0', 'github',       'fa fa-github-alt' ),
						( 4, 'url',    'Website',       NULL, 'optional',    'unrestricted', 'allowed',   '6',  '0', 'website',      NULL ),
						( 4, 'url',    'Blog',          NULL, 'optional',    'unrestricted', 'allowed',   '7',  '0', 'blog',         NULL ),
						( 4, 'url',    'Myspace',       NULL, 'optional',    'unrestricted', 'forbidden', '8',  '0', 'myspace',      NULL ),
						( 4, 'url',    'Flickr',        NULL, 'optional',    'unrestricted', 'forbidden', '9',  '0', 'flickr',       'fa fa-flickr' ),
						( 4, 'url',    'YouTube',       NULL, 'optional',    'unrestricted', 'forbidden', '10', '0', 'youtube',      'fa fa-youtube' ),
						( 4, 'url',    'Digg',          NULL, 'optional',    'unrestricted', 'forbidden', '11', '0', 'digg',         'fa fa-digg' ),
						( 4, 'url',    'StumbleUpon',   NULL, 'optional',    'unrestricted', 'forbidden', '12', '0', 'stumbleupon',  'fa fa-stumbleupon' ),
						( 4, 'url',    'Pinterest',     NULL, 'optional',    'unrestricted', 'forbidden', '13', '0', 'pinterest',    'fa fa-pinterest-p' ),
						( 4, 'url',    'SoundCloud',    NULL, 'optional',    'unrestricted', 'forbidden', '14', '0', 'soundcloud',   'fa fa-soundcloud' ),
						( 4, 'url',    'Yelp',          NULL, 'optional',    'unrestricted', 'forbidden', '15', '0', 'yelp',         'fa fa-yelp' ),
						( 4, 'url',    'PayPal',        NULL, 'optional',    'unrestricted', 'forbidden', '16', '0', 'paypal',       'fa fa-paypal' ),
						( 4, 'url',    '500px',         NULL, 'optional',    'unrestricted', 'forbidden', '17', '0', '500px',        'fa fa-500px' ),
						( 4, 'url',    'Amazon',        NULL, 'optional',    'unrestricted', 'forbidden', '18', '0', 'amazon',       'fa fa-amazon' ),
						( 4, 'url',    'Instagram',     NULL, 'optional',    'unrestricted', 'forbidden', '19', '0', 'instagram',    'fa fa-instagram' ),
						( 4, 'url',    'Vimeo',         NULL, 'optional',    'unrestricted', 'forbidden', '20', '0', 'vimeo',        'fa fa-vimeo' ),
						( 5, 'text',   'Main address',  NULL, 'optional',    'private',      'forbidden', '1',  '0', 'mainaddress',  'fa fa-building' ),
						( 5, 'text',   'Home address',  NULL, 'optional',    'private',      'forbidden', '2',  '0', 'homeaddress',  'fa fa-home' ),
						( 6, 'text',   'Admin notes',   NULL, 'recommended', 'admin',        'forbidden', '1',  '0', 'adminnotes',   'fa fa-edit' )" );

	if( is_pro() )
	{	// Install default user field definitions for PRO version:
		load_funcs( '_core/_pro_features.funcs.php' );
		pro_install_default_user_fields();
	}
	task_end();


	// don't change order of the following two functions as countries has relations to currencies
	create_default_currencies();
	create_default_countries();

	create_default_regions();


	// Do not create organization yet
	global $user_org_IDs;
	$user_org_IDs = NULL;

	task_begin( 'Creating admin user... ' );
	global $timestamp, $admin_email, $default_locale, $default_country, $install_login, $install_password;
	global $random_password;

	// Set default country from locale code
	$country_code = explode( '-', $default_locale );
	if( isset( $country_code[1] ) )
	{
		$default_country = $DB->get_var( '
			SELECT ctry_ID
			  FROM T_regional__country
			 WHERE ctry_code = '.$DB->quote( strtolower( $country_code[1] ) ) );
	}

	if( !isset( $install_password ) )
	{
		$random_password = generate_random_passwd(); // no ambiguous chars
	}
	else
	{
		$random_password = $install_password;
	}

	global $admin_user;
	$admin_user = create_user( array(
			'login'     => isset( $install_login ) ? $install_login : 'admin',
			'firstname' => 'Johnny',
			'lastname'  => 'Admin',
			'level'     => 10,
			'gender'    => 'M',
			'group_ID'  => $admins_Group->ID,
			'org_IDs'   => $user_org_IDs,
			'org_roles' => array( 'King of Spades' ),
			'org_priorities' => array( 0 ),
			'fields'    => array(
					'microbio' => 'I am the demo administrator of this site.'."\n".'I love having so much power!',
					'website'  => 'http://b2evolution.net/',
					'twitter'  => 'https://twitter.com/b2evolution/',
					'facebook' => 'https://www.facebook.com/b2evolution',
					'linkedin' => 'https://www.linkedin.com/company/b2evolution-net',
					'github'   => 'https://github.com/b2evolution/b2evolution',
				)
		) );
	task_end();

	// Activating multiple sessions and email message form for administrator, and set other user settings
	task_begin( 'Set settings for administrator user... ' );
	$DB->query( "
		INSERT INTO T_users__usersettings ( uset_user_ID, uset_name, uset_value )
		VALUES ( 1, 'login_multiple_sessions', '1' ),
				( 1, 'enable_email', '1' ),
				( 1, 'created_fromIPv4', '".ip2int( '127.0.0.1' )."' ),
				( 1, 'user_registered_from_domain', 'localhost' )" );
	task_end();


	// added in Phoenix-Alpha
	task_begin( 'Creating default Post Types... ' );
	$post_types = array();
	$post_types[] = array(
			'name'           => 'Post',
			'schema'         => 'Article',
		);
	$post_types[] = array(
			'name'          => 'Recipe',
			'template_full' => 'recipe_content_full',
		);
	$post_types[] = array(
			'name'           => 'Post with Custom Fields',
		);
	$post_types[] = array(
			'name'           => 'Child Post',
			'use_parent'     => 'required',
		);
	$post_types[] = array(
			'name'           => 'Podcast Episode',
			'podcast'        => 1,
		);
	$post_types[] = array(
			'name'           => 'Photo Album',
		);
	$post_types[] = array(
			'name'           => 'Manual Page',
			'use_short_title'=> 'optional',
			'allow_html'     => 0,
		);
	$post_types[] = array(
			'name'           => 'Forum Topic',
			'allow_html'     => 0,
		);
	$post_types[] = array(
			'name'       => 'Bug Report',
			'allow_html' => 0,
		);
	$post_types[] = array(
			'name'           => 'Standalone Page',
			'usage'          => 'page',
			'template_name'  => 'page',
			'perm_level'     => 'restricted',
			'use_comments'   => 0,
			'allow_featured' => 0,
		);
	$post_types[] = array(
			'name'           => 'Widget Page',
			'usage'          => 'widget-page',
			'template_name'  => 'widget_page',
			'perm_level'     => 'admin',
			'use_text'       => 'never',
			'allow_html'        => 0,
			'allow_breaks'      => 0,
			'allow_attachments' => 0,
			'allow_featured'    => 0,
			'use_comments'   => 0,
			'use_coordinates'=> 'optional',
		);
	$post_types[] = array(
			'name'           => 'Intro-Front',
			'usage'          => 'intro-front',
			'template_name'  => NULL,
			'allow_breaks'   => 0,
			'allow_featured' => 0,
			'perm_level'     => 'restricted',
			'allow_disabling_comments' => 1,
		);
	$post_types[] = array(
			'name'           => 'Intro-Main',
			'usage'          => 'intro-main',
			'template_name'  => NULL,
			'allow_breaks'   => 0,
			'allow_featured' => 0,
			'perm_level'     => 'restricted',
			'allow_disabling_comments' => 1,
		);
	$post_types[] = array(
			'name'           => 'Intro-Cat',
			'usage'          => 'intro-cat',
			'template_name'  => NULL,
			'allow_breaks'   => 0,
			'allow_featured' => 0,
			'perm_level'     => 'restricted',
			'allow_disabling_comments' => 1,
		);
	$post_types[] = array(
			'name'           => 'Intro-Tag',
			'usage'          => 'intro-tag',
			'template_name'  => NULL,
			'allow_breaks'   => 0,
			'allow_featured' => 0,
			'perm_level'     => 'restricted',
			'allow_disabling_comments' => 1,
		);
	$post_types[] = array(
			'name'           => 'Intro-Sub',
			'usage'          => 'intro-sub',
			'template_name'  => NULL,
			'allow_breaks'   => 0,
			'allow_featured' => 0,
			'perm_level'     => 'restricted',
			'allow_disabling_comments' => 1,
		);
	$post_types[] = array(
			'name'           => 'Intro-All',
			'usage'          => 'intro-all',
			'template_name'  => NULL,
			'allow_breaks'   => 0,
			'allow_featured' => 0,
			'perm_level'     => 'restricted',
			'allow_disabling_comments' => 1,
		);
	$post_types[] = array(
			'name'           => 'Content Block',
			'usage'          => 'content-block',
			'template_name'  => NULL,
			'allow_breaks'   => 0,
			'allow_featured' => 0,
			'use_comments'   => 0,
		);
	$post_types[] = array(
			'name'           => 'Text Ad',
			'usage'          => 'content-block',
			'template_name'  => NULL,
			'allow_breaks'   => 0,
			'allow_featured' => 0,
			'use_comments'   => 0,
		);
	$post_types[] = array(
			'name'           => 'Sidebar link',
			'usage'          => 'special',
			'template_name'  => NULL,
			'perm_level'     => 'admin',
			'allow_disabling_comments' => 1,
			'allow_featured' => 0,
		);
	$post_types[] = array(
			'name'           => 'Advertisement',
			'usage'          => 'special',
			'template_name'  => NULL,
			'perm_level'     => 'admin',
			'allow_featured' => 0,
		);
	$post_types[] = array(
			'name'                   => 'Terms & Conditions',
			'usage'                  => 'special',
			'template_name'          => NULL,
			'allow_breaks'           => 0,
			'allow_featured'         => 0,
			'perm_level'             => 'admin',
			'description'            => 'Use this post type for terms & conditions of the site.',
			'use_text'               => 'required',
			'use_tags'               => 'never',
			'use_excerpt'            => 'never',
			'use_url'                => 'never',
			'use_parent'             => 'never',
			'use_title_tag'          => 'never',
			'use_meta_desc'          => 'never',
			'use_meta_keywds'        => 'never',
			'use_comments'           => 0,
			'allow_closing_comments' => 0,
			'use_comment_expiration' => 'never',
		);
	$post_types[] = array(
			'name'   => 'Product',
			'schema' => 'Product',
		);
	$post_types[] = array(
			'name'   => 'Review',
			'schema' => 'Review',
		);
	$post_types[] = array(
			'name'            => 'Homepage Content Tab',
			'usage'           => 'post',
			'use_short_title' => 'optional',
		);
	$post_types[] = array(
			'name'                 => 'Task',
			'allow_html'           => 0,
			'front_order_workflow' => 20,
		);
	// Default settings:
	$post_type_default_settings = array(
			'name'                     => '',
			'description'              => NULL,
			'usage'                    => 'post',
			'template_full'            => NULL,
			'template_name'            => 'single',
			'perm_level'               => 'standard',
			'use_short_title'          => 'never',
			'short_title_maxlen'       => 30,
			'title_maxlen'             => 100,
			'allow_html'               => 1,
			'allow_breaks'             => 1,
			'allow_attachments'        => 1,
			'allow_featured'           => 1,
			'use_text'                 => 'optional',
			'use_tags'                 => 'optional',
			'use_excerpt'              => 'optional',
			'use_url'                  => 'optional',
			'podcast'                  => 0,
			'use_parent'               => 'never',
			'use_title_tag'            => 'optional',
			'use_meta_desc'            => 'optional',
			'use_meta_keywds'          => 'optional',
			'use_comments'             => 1,
			'allow_closing_comments'   => 1,
			'allow_disabling_comments' => 0,
			'use_comment_expiration'   => 'optional',
			'schema'                   => NULL,
			'use_coordinates'          => 'never',
			'front_order_title'        => 10,
			'front_order_attachments'  => 30,
			'front_order_workflow'     => NULL,
			'front_order_text'         => 80,
			'front_order_location'     => 90,
		);
	$post_types_sql = 'INSERT INTO T_items__type ( ityp_'.implode( ', ityp_', array_keys( $post_type_default_settings ) ).' ) VALUES ';
	foreach( $post_types as $p => $post_type )
	{
		$post_type = array_merge( $post_type_default_settings, $post_type );
		$post_types_sql .= '( '.$DB->quote( $post_type ).' )';
		if( $p != count( $post_types ) - 1 )
		{
			$post_types_sql .= ',';
		}
	}
	// Insert item types:
	$DB->query( $post_types_sql );

	// Item type custom fields:
	$parent_ityp_ID = 3;
	$child_ityp_ID = 4;
	$recipe_ityp_ID = 2;
	$product_ityp_ID = 21;
	$review_ityp_ID = 22;
	$forum_topic_ityp_ID = 8;
	$custom_fields = array(
		// for Item Type "Post with Custom Fields":
		array(
			'label'           => T_('Image 1'),
			'name'            => 'image_1',
			'type'            => 'image',
			'order'           => 110,
			'note'            => T_('Enter a link ID'),
			'format'          => 'fit-192x192',
			'link'            => 'linkpermzoom',
			'line_highlight'  => 'never',
			'green_highlight' => 'never',
		),
		array(
			'label'           => T_('First numeric field'),
			'name'            => 'first_numeric_field',
			'type'            => 'double',
			'order'           => 120,
			'note'            => T_('Enter a number'),
			'cell_class'      => 'right',
		),
		array(
			'label'           => T_('Second numeric field'),
			'name'            => 'second_numeric_field',
			'type'            => 'double',
			'order'           => 140,
			'note'            => T_('Enter a number'),
			'cell_class'      => 'right',
		),
		array(
			'label'           => T_('USD Price'),
			'name'            => 'usd_price',
			'type'            => 'double',
			'order'           => 180,
			'note'            => T_('Enter a number'),
			'format'          => '$ 0 0.00[.green];$ 0 0.00[.red]',
			'cell_class'      => 'right',
			'green_highlight' => 'lowest',
		),
		array(
			'label'           => T_('EUR Price'),
			'name'            => 'eur_price',
			'type'            => 'double',
			'order'           => 190,
			'note'            => T_('Enter a number'),
			'format'          => '0 0.00 €',
			'cell_class'      => 'right',
			'green_highlight' => 'lowest',
		),
		array(
			'label'           => T_('First string field'),
			'name'            => 'first_string_field',
			'type'            => 'varchar',
			'order'           => 130,
			'note'            => T_('Enter a string'),
		),
		array(
			'label'           => T_('Multiline plain text field'),
			'name'            => 'multiline_plain_text_field',
			'type'            => 'text',
			'order'           => 160,
			'note'            => T_('Enter multiple lines'),
		),
		array(
			'label'           => T_('Multiline HTML field'),
			'name'            => 'multiline_html_field',
			'type'            => 'html',
			'order'           => 150,
			'note'            => T_('Enter HTML code'),
		),
		array(
			'label'           => T_('URL field'),
			'name'            => 'url_field',
			'type'            => 'url',
			'order'           => 170,
			'note'            => T_('Enter an URL (absolute or relative)'),
			'link'            => 'fieldurl',
		),
		array(
			'label'           => T_('Checkmark field'),
			'name'            => 'checkmark_field',
			'type'            => 'double',
			'order'           => 200,
			'note'            => T_('1 = Yes; 0 = No'),
			'format'          => '#yes#;;#no#;n/a',
			'cell_class'      => 'right',
		),
		array(
			'label'           => TD_('Numeric Average'),
			'name'            => 'numeric_average',
			'type'            => 'computed',
			'order'           => 210,
			'cell_class'      => 'right',
			'formula'         => '($first_numeric_field$+$second_numeric_field$)/2',
		),
		// for Item Type "Child Post":
		array(
			'ityp_ID'         => $child_ityp_ID,
			'label'           => T_('Image 1'),
			'name'            => 'image_1',
			'type'            => 'image',
			'order'           => 110,
			'note'            => T_('Enter a link ID'),
			'format'          => 'fit-192x192',
			'link'            => 'linkpermzoom',
			'line_highlight'  => 'never',
			'green_highlight' => 'never',
		),
		array(
			'ityp_ID'         => $child_ityp_ID,
			'label'           => T_('First numeric field'),
			'name'            => 'first_numeric_field',
			'type'            => 'double',
			'order'           => 120,
			'note'            => T_('Enter a number'),
			'cell_class'      => 'right',
		),
		array(
			'ityp_ID'         => $child_ityp_ID,
			'label'           => T_('First string field'),
			'name'            => 'first_string_field',
			'type'            => 'varchar',
			'order'           => 130,
			'note'            => T_('Enter a string'),
		),
		array(
			'ityp_ID'         => $child_ityp_ID,
			'label'           => T_('Checkmark field'),
			'name'            => 'checkmark_field',
			'type'            => 'double',
			'order'           => 140,
			'note'            => T_('1 = Yes; 0 = No'),
			'format'          => '#yes#;;#no#;n/a',
			'cell_class'      => 'right',
		),
		// for Item Type "Recipe":
		array(
			'ityp_ID'         => $recipe_ityp_ID,
			'label'           => TD_('Course'),
			'name'            => 'course',
			'type'            => 'varchar',
			'order'           => 110,
			'note'            => T_('E-g: ').'"'.TD_('Dessert').'"',
			'header_class'    => '',
			'cell_class'      => '',
		),
		array(
			'ityp_ID'         => $recipe_ityp_ID,
			'label'           => TD_('Cuisine'),
			'name'            => 'cuisine',
			'type'            => 'varchar',
			'order'           => 120,
			'note'            => T_('E-g: ').'"'.TD_('Italian').'"',
			'header_class'    => '',
			'cell_class'      => '',
		),
		array(
			'ityp_ID'         => $recipe_ityp_ID,
			'label'           => TD_('Servings'),
			'name'            => 'servings',
			'order'           => 130,
			'note'            => TD_('people'),
			'format'          => sprintf( TD_('%d people'), 0 ),
			'header_class'    => '',
			'cell_class'      => '',
		),
		array(
			'ityp_ID'         => $recipe_ityp_ID,
			'label'           => TD_('Prep Time'),
			'name'            => 'prep_time',
			'order'           => 140,
			'note'            => TD_('minutes'),
			'format'          => sprintf( TD_('%s minutes'), 0 ),
			'header_class'    => '',
			'cell_class'      => '',
		),
		array(
			'ityp_ID'         => $recipe_ityp_ID,
			'label'           => TD_('Cook Time'),
			'name'            => 'cook_time',
			'order'           => 150,
			'note'            => TD_('minutes'),
			'format'          => sprintf( TD_('%s minutes'), 0 ),
			'header_class'    => '',
			'cell_class'      => '',
		),
		array(
			'ityp_ID'         => $recipe_ityp_ID,
			'label'           => TD_('Passive Time'),
			'name'            => 'passive_time',
			'order'           => 160,
			'note'            => TD_('minutes'),
			'format'          => sprintf( TD_('%s minutes'), 0 ),
			'header_class'    => '',
			'cell_class'      => '',
		),
		array(
			'ityp_ID'         => $recipe_ityp_ID,
			'label'           => TD_('Total time'),
			'name'            => 'total_time',
			'type'            => 'computed',
			'order'           => 170,
			'format'          => sprintf( TD_('%s minutes'), 0 ),
			'formula'         => '$prep_time$ + $cook_time$ + $passive_time$',
			'header_class'    => '',
			'cell_class'      => '',
		),
		array(
			'ityp_ID'         => $recipe_ityp_ID,
			'label'           => TD_('Ingredients'),
			'name'            => 'ingredients',
			'type'            => 'text',
			'order'           => 180,
			'header_class'    => '',
			'cell_class'      => '',
		),
		// for Item Type "Product":
		array(
			'ityp_ID'         => $product_ityp_ID,
			'label'           => T_('Brand'),
			'name'            => 'brand',
			'schema_prop'     => 'brand',
			'type'            => 'varchar',
			'order'           => 110,
		),
		array(
			'ityp_ID'         => $product_ityp_ID,
			'label'           => T_('SKU'),
			'name'            => 'sku',
			'schema_prop'     => 'sku',
			'type'            => 'varchar',
			'order'           => 120,
		),
		array(
			'ityp_ID'         => $product_ityp_ID,
			'label'           => T_('Price'),
			'name'            => 'price',
			'schema_prop'     => 'offers.price',
			'type'            => 'double',
			'order'           => 130,
			'format'          => '$ 0 0.00',
			'cell_class'      => 'right',
		),
		array(
			'ityp_ID'         => $product_ityp_ID,
			'label'           => T_('Currency'),
			'name'            => 'currency',
			'schema_prop'     => 'offers.priceCurrency',
			'type'            => 'varchar',
			'order'           => 140,
			'note'            => T_('in three-letter ISO 4217 format'),
		),
		array(
			'ityp_ID'         => $product_ityp_ID,
			'label'           => T_('Availability'),
			'name'            => 'availability',
			'schema_prop'     => 'offers.availability',
			'type'            => 'varchar',
			'order'           => 150,
		),
		array(
			'ityp_ID'         => $product_ityp_ID,
			'label'           => T_('Color'),
			'name'            => 'item_color',
			'schema_prop'     => 'color',
			'type'            => 'varchar',
			'order'           => 160,
		),
		array(
			'ityp_ID'         => $product_ityp_ID,
			'label'           => T_('Package total weight'),
			'name'            => 'package_total_weight',
			'type'            => 'double',
			'order'           => 170,
			'note'            => T_('Kg'),
			'cell_class'      => 'right',
		),
		array(
			'ityp_ID'         => $product_ityp_ID,
			'label'           => T_('Package length'),
			'name'            => 'package_length',
			'type'            => 'double',
			'order'           => 180,
			'note'            => T_('cm'),
			'cell_class'      => 'right',
		),
		array(
			'ityp_ID'         => $product_ityp_ID,
			'label'           => T_('Package width'),
			'name'            => 'package_width',
			'type'            => 'double',
			'order'           => 190,
			'note'            => T_('cm'),
			'cell_class'      => 'right',
		),
		array(
			'ityp_ID'         => $product_ityp_ID,
			'label'           => T_('Package height'),
			'name'            => 'package_height',
			'type'            => 'double',
			'order'           => 200,
			'note'            => T_('cm'),
			'cell_class'      => 'right',
		),
		// for Item Type "Review":
		array(
			'ityp_ID'         => $review_ityp_ID,
			'label'           => T_('Item reviewed'),
			'name'            => 'item_reviewed_name',
			'schema_prop'     => 'itemReviewed.name',
			'type'            => 'varchar',
			'order'           => 110,
		),
		array(
			'ityp_ID'         => $review_ityp_ID,
			'label'           => T_('Rating value'),
			'name'            => 'review_rating_value',
			'schema_prop'     => 'reviewRating.ratingValue',
			'type'            => 'double',
			'order'           => 120,
			'note'            => T_('Rating must be a value between 1 and 5 with 5 being the highest.'),
		),
	);
	// Default settings for custom fields:
	$custom_field_default_settings = array(
			'ityp_ID'         => $parent_ityp_ID,
			'label'           => '',
			'name'            => '',
			'schema_prop'     => NULL,
			'type'            => 'double',
			'order'           => '',
			'note'            => NULL,
			'format'          => NULL,
			'formula'         => NULL,
			'header_class'    => 'right nowrap',
			'cell_class'      => 'center',
			'link'            => 'nolink',
			'line_highlight'  => 'differences',
			'green_highlight' => 'never',
			'red_highlight'   => 'never',
		);
	// Insert item type custom fields:
	$custom_fields_sql = 'INSERT INTO T_items__type_custom_field ( itcf_'.implode( ', itcf_', array_keys( $custom_field_default_settings ) ).' ) VALUES ';
	foreach( $custom_fields as $c => $custom_field )
	{
		$custom_field = array_merge( $custom_field_default_settings, $custom_field );
		$custom_fields_sql .= '( '.$DB->quote( $custom_field ).' )';
		if( $c != count( $custom_fields ) - 1 )
		{
			$custom_fields_sql .= ',';
		}
	}
	$DB->query( $custom_fields_sql );
	task_end();


	task_begin( 'Creating default Post Statuses... ' );
	$post_status_with_order = array(" ( 'New', 10 ) ", " ( 'In Progress', 20 ) ", " ( 'Duplicate', 30 ) ", " ( 'Not A Bug', 40 ) ", " ( 'In Review', 50 ) ", " ( 'Fixed', 60 ) ", " ( 'Closed', 70 ) ", " ( 'OK', 80 ) ", );
	
	$DB->query( "INSERT INTO T_items__status ( pst_name, pst_order )	VALUES ". implode( ",", $post_status_with_order ) );
	task_end();


	task_begin( 'Creating default post status and post type associations...' );
	// Enable all post statuses for post type Bug Report
	$DB->query( 'INSERT INTO T_items__status_type (its_pst_ID, its_ityp_ID)
			( SELECT pst_ID, ityp_ID FROM T_items__type, T_items__status WHERE ityp_name = "Bug Report" )' );

	// Enable post status 'New', 'Duplicate', 'In Review' and 'OK' for all post types
	$DB->query( 'INSERT IGNORE INTO T_items__status_type (its_pst_ID, its_ityp_ID)
			( SELECT pst_ID, ityp_ID FROM T_items__type, T_items__status WHERE pst_name IN ( "New", "Duplicate", "In Review", "OK" ) )' );
	task_end();


	// added in Phoenix-Beta
	task_begin( 'Creating default file types... ' );
	// Contribs: feel free to add more types here...
	// TODO: dh> shouldn't they get localized to the app's default locale? fp> ftyp_name, yes
	$DB->query( "INSERT INTO T_filetypes
			(ftyp_ID, ftyp_extensions, ftyp_name, ftyp_mimetype, ftyp_icon, ftyp_viewtype, ftyp_allowed)
		VALUES
			(1, 'gif', 'GIF image', 'image/gif', 'file_image', 'image', 'any'),
			(2, 'png', 'PNG image', 'image/png', 'file_image', 'image', 'any'),
			(3, 'jpg jpeg', 'JPEG image', 'image/jpeg', 'file_image', 'image', 'any'),
			(4, 'txt', 'Text file', 'text/plain', 'file_document', 'text', 'registered'),
			(5, 'htm html', 'HTML file', 'text/html', 'file_www', 'browser', 'admin'),
			(6, 'pdf', 'PDF file', 'application/pdf', 'file_pdf', 'browser', 'registered'),
			(7, 'doc docx', 'Microsoft Word file', 'application/msword', 'file_doc', 'external', 'registered'),
			(8, 'xls xlsx', 'Microsoft Excel file', 'application/vnd.ms-excel', 'file_xls', 'external', 'registered'),
			(9, 'ppt pptx', 'Powerpoint', 'application/vnd.ms-powerpoint', 'file_ppt', 'external', 'registered'),
			(10, 'pps', 'Slideshow', 'pps', 'file_pps', 'external', 'registered'),
			(11, 'zip', 'ZIP archive', 'application/zip', 'file_zip', 'external', 'registered'),
			(12, 'php php3 php4 php5 php6', 'PHP script', 'application/x-httpd-php', 'file_php', 'text', 'admin'),
			(13, 'css', 'Style sheet', 'text/css', 'file_document', 'text', 'registered'),
			(14, 'mp3', 'MPEG audio file', 'audio/mpeg', 'file_sound', 'browser', 'registered'),
			(15, 'm4a', 'MPEG audio file', 'audio/x-m4a', 'file_sound', 'browser', 'registered'),
			(16, 'mp4 f4v', 'MPEG video', 'video/mp4', 'file_video', 'browser', 'registered'),
			(17, 'mov', 'Quicktime video', 'video/quicktime', 'file_video', 'browser', 'registered'),
			(18, 'm4v', 'MPEG video file', 'video/x-m4v', 'file_video', 'browser', 'registered'),
			(19, 'flv', 'Flash video file', 'video/x-flv', 'file_video', 'browser', 'registered'),
			(20, 'swf', 'Flash video file', 'application/x-shockwave-flash', 'file_video', 'browser', 'admin'),
			(21, 'webm', 'WebM video file', 'video/webm', 'file_video', 'browser', 'registered'),
			(22, 'ogv', 'Ogg video file', 'video/ogg', 'file_video', 'browser', 'registered'),
			(23, 'm3u8', 'M3U8 video file', 'application/x-mpegurl', 'file_video', 'browser', 'registered'),
			(24, 'xml', 'XML file', 'application/xml', 'file_www', 'browser', 'admin'),
			(25, 'md', 'Markdown text file', 'text/plain', 'file_document', 'text', 'registered'),
			(26, 'csv', 'CSV file', 'text/plain', 'file_document', 'text', 'registered'),
			(27, 'svg', 'SVG file', 'image/svg+xml', 'file_document', 'image', 'admin'),
			(28, 'ico', 'ICO image', 'image/x-icon', 'file_image', 'image', 'admin')
		" );
	task_end();

	// Insert default locales into T_locales.
	create_default_locales();

	// Insert default settings into T_settings.
	create_default_settings();

	// Create default scheduled jobs
	create_default_jobs();

	// Create default templates
	create_default_templates();

	task_begin( 'Creating default "help" slug... ' );
	$DB->query( '
		INSERT INTO T_slug( slug_title, slug_type )
		VALUES( "help", "help" )', 'Add "help" slug' );
	task_end();

	// Create the 'Default' goal category which must always exists and which is not deletable
	// The 'Default' category ID will be always 1 because it will be always the first entry in the T_track__goalcat table
	task_begin( 'Creating default goal category... ' );
	$DB->query( 'INSERT INTO T_track__goalcat ( gcat_name, gcat_color )
		VALUES ( '.$DB->quote( 'Default' ).', '.$DB->quote( '#999999' ).' )' );
	task_end();


	// Update the progress bar status
	update_install_progress_bar();

	install_basic_skins();

	install_basic_plugins();

	return true;
}


/**
 * Create default currencies
 *
 */
function create_default_currencies( $table_name = 'T_regional__currency' )
{
	global $DB;

	task_begin( 'Creating default currencies... ' );
	$DB->query( "
		INSERT INTO $table_name (curr_ID, curr_code, curr_shortcut, curr_name)
		 VALUES
			(1, 'AFN', '&#x60b;', 'Afghani'),
			(2, 'EUR', '&euro;', 'Euro'),
			(3, 'ALL', 'Lek', 'Lek'),
			(4, 'DZD', 'DZD', 'Algerian Dinar'),
			(5, 'USD', '$', 'US Dollar'),
			(6, 'AOA', 'AOA', 'Kwanza'),
			(7, 'XCD', '$', 'East Caribbean Dollar'),
			(8, 'ARS', '$', 'Argentine Peso'),
			(9, 'AMD', 'AMD', 'Armenian Dram'),
			(10, 'AWG', '&fnof;', 'Aruban Guilder'),
			(11, 'AUD', '$', 'Australian Dollar'),
			(12, 'AZN', '&#x43c;&#x430;&#x43d;', 'Azerbaijanian Manat'),
			(13, 'BSD', '$', 'Bahamian Dollar'),
			(14, 'BHD', 'BHD', 'Bahraini Dinar'),
			(15, 'BDT', 'BDT', 'Taka'),
			(16, 'BBD', '$', 'Barbados Dollar'),
			(17, 'BYR', 'p.', 'Belarussian Ruble'),
			(18, 'BZD', 'BZ$', 'Belize Dollar'),
			(19, 'XOF', 'XOF', 'CFA Franc BCEAO'),
			(20, 'BMD', '$', 'Bermudian Dollar'),
			(21, 'BAM', 'KM', 'Convertible Marks'),
			(22, 'BWP', 'P', 'Pula'),
			(23, 'NOK', 'kr', 'Norwegian Krone'),
			(24, 'BRL', 'R$', 'Brazilian Real'),
			(25, 'BND', '$', 'Brunei Dollar'),
			(26, 'BGN', '&#x43b;&#x432;', 'Bulgarian Lev'),
			(27, 'BIF', 'BIF', 'Burundi Franc'),
			(28, 'KHR', '&#x17db;', 'Riel'),
			(29, 'XAF', 'XAF', 'CFA Franc BEAC'),
			(30, 'CAD', '$', 'Canadian Dollar'),
			(31, 'CVE', 'CVE', 'Cape Verde Escudo'),
			(32, 'KYD', '$', 'Cayman Islands Dollar'),
			(33, 'CNY', '&yen;', 'Yuan Renminbi'),
			(34, 'KMF', 'KMF', 'Comoro Franc'),
			(35, 'CDF', 'CDF', 'Congolese Franc'),
			(36, 'NZD', '$', 'New Zealand Dollar'),
			(37, 'CRC', '&#x20a1;', 'Costa Rican Colon'),
			(38, 'HRK', 'kn', 'Croatian Kuna'),
			(39, 'CZK', 'K&#x10d;', 'Czech Koruna'),
			(40, 'DKK', 'kr', 'Danish Krone'),
			(41, 'DJF', 'DJF', 'Djibouti Franc'),
			(42, 'DOP', 'RD$', 'Dominican Peso'),
			(43, 'EGP', '&pound;', 'Egyptian Pound'),
			(44, 'ERN', 'ERN', 'Nakfa'),
			(45, 'EEK', 'EEK', 'Kroon'),
			(46, 'ETB', 'ETB', 'Ethiopian Birr'),
			(47, 'FKP', '&pound;', 'Falkland Islands Pound'),
			(48, 'FJD', '$', 'Fiji Dollar'),
			(49, 'XPF', 'XPF', 'CFP Franc'),
			(50, 'GMD', 'GMD', 'Dalasi'),
			(51, 'GEL', 'GEL', 'Lari'),
			(52, 'GHS', 'GHS', 'Cedi'),
			(53, 'GIP', '&pound;', 'Gibraltar Pound'),
			(54, 'GTQ', 'Q', 'Quetzal'),
			(55, 'GBP', '&pound;', 'Pound Sterling'),
			(56, 'GNF', 'GNF', 'Guinea Franc'),
			(57, 'GYD', '$', 'Guyana Dollar'),
			(58, 'HNL', 'L', 'Lempira'),
			(59, 'HKD', '$', 'Hong Kong Dollar'),
			(60, 'HUF', 'Ft', 'Forint'),
			(61, 'ISK', 'kr', 'Iceland Krona'),
			(62, 'INR', 'Rs', 'Indian Rupee'),
			(63, 'IDR', 'Rp', 'Rupiah'),
			(64, 'IRR', '&#xfdfc;', 'Iranian Rial'),
			(65, 'IQD', 'IQD', 'Iraqi Dinar'),
			(66, 'ILS', '&#x20aa;', 'New Israeli Sheqel'),
			(67, 'JMD', 'J$', 'Jamaican Dollar'),
			(68, 'JPY', '&yen;', 'Yen'),
			(69, 'JOD', 'JOD', 'Jordanian Dinar'),
			(70, 'KZT', '&#x43b;&#x432;', 'Tenge'),
			(71, 'KES', 'KES', 'Kenyan Shilling'),
			(72, 'KPW', '&#x20a9;', 'North Korean Won'),
			(73, 'KRW', '&#x20a9;', 'Won'),
			(74, 'KWD', 'KWD', 'Kuwaiti Dinar'),
			(75, 'KGS', '&#x43b;&#x432;', 'Som'),
			(76, 'LAK', '&#x20ad;', 'Kip'),
			(77, 'LVL', 'Ls', 'Latvian Lats'),
			(78, 'LBP', '&pound;', 'Lebanese Pound'),
			(79, 'LRD', '$', 'Liberian Dollar'),
			(80, 'LYD', 'LYD', 'Libyan Dinar'),
			(81, 'CHF', 'CHF', 'Swiss Franc'),
			(82, 'LTL', 'Lt', 'Lithuanian Litas'),
			(83, 'MOP', 'MOP', 'Pataca'),
			(84, 'MKD', '&#x434;&#x435;&#x43d;', 'Denar'),
			(85, 'MGA', 'MGA', 'Malagasy Ariary'),
			(86, 'MWK', 'MWK', 'Kwacha'),
			(87, 'MYR', 'RM', 'Malaysian Ringgit'),
			(88, 'MVR', 'MVR', 'Rufiyaa'),
			(89, 'MRO', 'MRO', 'Ouguiya'),
			(90, 'MUR', 'Rs', 'Mauritius Rupee'),
			(91, 'MDL', 'MDL', 'Moldovan Leu'),
			(92, 'MNT', '&#x20ae;', 'Tugrik'),
			(93, 'MAD', 'MAD', 'Moroccan Dirham'),
			(94, 'MZN', 'MT', 'Metical'),
			(95, 'MMK', 'MMK', 'Kyat'),
			(96, 'NPR', 'Rs', 'Nepalese Rupee'),
			(97, 'ANG', '&fnof;', 'Netherlands Antillian Guilder'),
			(98, 'NIO', 'C$', 'Cordoba Oro'),
			(99, 'NGN', '&#x20a6;', 'Naira'),
			(100, 'OMR', '&#xfdfc;', 'Rial Omani'),
			(101, 'PKR', 'Rs', 'Pakistan Rupee'),
			(102, 'PGK', 'PGK', 'Kina'),
			(103, 'PYG', 'Gs', 'Guarani'),
			(104, 'PEN', 'S/.', 'Nuevo Sol'),
			(105, 'PHP', 'Php', 'Philippine Peso'),
			(106, 'PLN', 'z&#x142;', 'Zloty'),
			(107, 'QAR', '&#xfdfc;', 'Qatari Rial'),
			(108, 'RON', 'lei', 'New Leu'),
			(109, 'RUB', '&#x440;&#x443;&#x431;', 'Russian Ruble'),
			(110, 'RWF', 'RWF', 'Rwanda Franc'),
			(111, 'SHP', '&pound;', 'Saint Helena Pound'),
			(112, 'WST', 'WST', 'Tala'),
			(113, 'STD', 'STD', 'Dobra'),
			(114, 'SAR', '&#xfdfc;', 'Saudi Riyal'),
			(115, 'RSD', '&#x414;&#x438;&#x43d;.', 'Serbian Dinar'),
			(116, 'SCR', 'Rs', 'Seychelles Rupee'),
			(117, 'SLL', 'SLL', 'Leone'),
			(118, 'SGD', '$', 'Singapore Dollar'),
			(119, 'SBD', '$', 'Solomon Islands Dollar'),
			(120, 'SOS', 'S', 'Somali Shilling'),
			(121, 'ZAR', 'R', 'Rand'),
			(122, 'LKR', 'Rs', 'Sri Lanka Rupee'),
			(123, 'SDG', 'SDG', 'Sudanese Pound'),
			(124, 'SRD', '$', 'Surinam Dollar'),
			(125, 'SZL', 'SZL', 'Lilangeni'),
			(126, 'SEK', 'kr', 'Swedish Krona'),
			(127, 'SYP', '&pound;', 'Syrian Pound'),
			(128, 'TWD', '$', 'New Taiwan Dollar'),
			(129, 'TJS', 'TJS', 'Somoni'),
			(130, 'TZS', 'TZS', 'Tanzanian Shilling'),
			(131, 'THB', 'THB', 'Baht'),
			(132, 'TOP', 'TOP', 'Pa'),
			(133, 'TTD', 'TT$', 'Trinidad and Tobago Dollar'),
			(134, 'TND', 'TND', 'Tunisian Dinar'),
			(135, 'TRY', 'TL', 'Turkish Lira'),
			(136, 'TMT', 'TMT', 'Manat'),
			(137, 'UGX', 'UGX', 'Uganda Shilling'),
			(138, 'UAH', '&#x20b4;', 'Hryvnia'),
			(139, 'AED', 'AED', 'UAE Dirham'),
			(140, 'UZS', '&#x43b;&#x432;', 'Uzbekistan Sum'),
			(141, 'VUV', 'VUV', 'Vatu'),
			(142, 'VEF', 'Bs', 'Bolivar Fuerte'),
			(143, 'VND', '&#x20ab;', 'Dong'),
			(144, 'YER', '&#xfdfc;', 'Yemeni Rial'),
			(145, 'ZMK', 'ZMK', 'Zambian Kwacha'),
			(146, 'ZWL', 'Z$', 'Zimbabwe Dollar'),
			(147, 'XAU', 'XAU', 'Gold'),
			(148, 'XBA', 'XBA', 'EURCO'),
			(149, 'XBB', 'XBB', 'European Monetary Unit'),
			(150, 'XBC', 'XBC', 'European Unit of Account 9'),
			(151, 'XBD', 'XBD', 'European Unit of Account 17'),
			(152, 'XDR', 'XDR', 'SDR'),
			(153, 'XPD', 'XPD', 'Palladium'),
			(154, 'XPT', 'XPT', 'Platinum'),
			(155, 'XAG', 'XAG', 'Silver'),
			(156, 'COP', '$', 'Colombian peso'),
			(157, 'CUP', '$', 'Cuban peso'),
			(158, 'SVC', 'SVC', 'Salvadoran colon'),
			(159, 'CLP', '$', 'Chilean peso'),
			(160, 'HTG', 'G', 'Haitian gourde'),
			(161, 'MXN', '$', 'Mexican peso'),
			(162, 'PAB', 'PAB', 'Panamanian balboa'),
			(163, 'UYU', '$', 'Uruguayan peso')
			" );
	task_end();
}


/**
 * Create default countries with relations to currencies
 *
 */
function create_default_countries( $table_name = 'T_regional__country', $set_preferred_country = true )
{
	global $DB, $current_locale;

	task_begin( 'Creating default countries... ' );
	$DB->query( "
		INSERT INTO $table_name ( ctry_ID, ctry_code, ctry_name, ctry_curr_ID)
		VALUES
			(1, 'af', 'Afghanistan', 1),
			(2, 'ax', 'Aland Islands', 2),
			(3, 'al', 'Albania', 3),
			(4, 'dz', 'Algeria', 4),
			(5, 'as', 'American Samoa', 5),
			(6, 'ad', 'Andorra', 2),
			(7, 'ao', 'Angola', 6),
			(8, 'ai', 'Anguilla', 7),
			(9, 'aq', 'Antarctica', NULL),
			(10, 'ag', 'Antigua And Barbuda', 7),
			(11, 'ar', 'Argentina', 8),
			(12, 'am', 'Armenia', 9),
			(13, 'aw', 'Aruba', 10),
			(14, 'au', 'Australia', 11),
			(15, 'at', 'Austria', 2),
			(16, 'az', 'Azerbaijan', 12),
			(17, 'bs', 'Bahamas', 13),
			(18, 'bh', 'Bahrain', 14),
			(19, 'bd', 'Bangladesh', 15),
			(20, 'bb', 'Barbados', 16),
			(21, 'by', 'Belarus', 17),
			(22, 'be', 'Belgium', 2),
			(23, 'bz', 'Belize', 18),
			(24, 'bj', 'Benin', 19),
			(25, 'bm', 'Bermuda', 20),
			(26, 'bt', 'Bhutan', 62),
			(27, 'bo', 'Bolivia', NULL),
			(28, 'ba', 'Bosnia And Herzegovina', 21),
			(29, 'bw', 'Botswana', 22),
			(30, 'bv', 'Bouvet Island', 23),
			(31, 'br', 'Brazil', 24),
			(32, 'io', 'British Indian Ocean Territory', 5),
			(33, 'bn', 'Brunei Darussalam', 25),
			(34, 'bg', 'Bulgaria', 26),
			(35, 'bf', 'Burkina Faso', 19),
			(36, 'bi', 'Burundi', 27),
			(37, 'kh', 'Cambodia', 28),
			(38, 'cm', 'Cameroon', 29),
			(39, 'ca', 'Canada', 30),
			(40, 'cv', 'Cape Verde', 31),
			(41, 'ky', 'Cayman Islands', 32),
			(42, 'cf', 'Central African Republic', 29),
			(43, 'td', 'Chad', 29),
			(44, 'cl', 'Chile', 159),
			(45, 'cn', 'China', 33),
			(46, 'cx', 'Christmas Island', 11),
			(47, 'cc', 'Cocos Islands', 11),
			(48, 'co', 'Colombia', 156),
			(49, 'km', 'Comoros', 34),
			(50, 'cg', 'Congo', 29),
			(51, 'cd', 'Congo Republic', 35),
			(52, 'ck', 'Cook Islands', 36),
			(53, 'cr', 'Costa Rica', 37),
			(54, 'ci', 'Cote Divoire', 19),
			(55, 'hr', 'Croatia', 38),
			(56, 'cu', 'Cuba', 157),
			(57, 'cy', 'Cyprus', 2),
			(58, 'cz', 'Czech Republic', 39),
			(59, 'dk', 'Denmark', 40),
			(60, 'dj', 'Djibouti', 41),
			(61, 'dm', 'Dominica', 7),
			(62, 'do', 'Dominican Republic', 42),
			(63, 'ec', 'Ecuador', 5),
			(64, 'eg', 'Egypt', 43),
			(65, 'sv', 'El Salvador', 158),
			(66, 'gq', 'Equatorial Guinea', 29),
			(67, 'er', 'Eritrea', 44),
			(68, 'ee', 'Estonia', 45),
			(69, 'et', 'Ethiopia', 46),
			(70, 'fk', 'Falkland Islands (Malvinas)', 47),
			(71, 'fo', 'Faroe Islands', 40),
			(72, 'fj', 'Fiji', 48),
			(73, 'fi', 'Finland', 2),
			(74, 'fr', 'France', 2),
			(75, 'gf', 'French Guiana', 2),
			(76, 'pf', 'French Polynesia', 49),
			(77, 'tf', 'French Southern Territories', 2),
			(78, 'ga', 'Gabon', 29),
			(79, 'gm', 'Gambia', 50),
			(80, 'ge', 'Georgia', 51),
			(81, 'de', 'Germany', 2),
			(82, 'gh', 'Ghana', 52),
			(83, 'gi', 'Gibraltar', 53),
			(84, 'gr', 'Greece', 2),
			(85, 'gl', 'Greenland', 40),
			(86, 'gd', 'Grenada', 7),
			(87, 'gp', 'Guadeloupe', 2),
			(88, 'gu', 'Guam', 5),
			(89, 'gt', 'Guatemala', 54),
			(90, 'gg', 'Guernsey', 55),
			(91, 'gn', 'Guinea', 56),
			(92, 'gw', 'Guinea-bissau', 19),
			(93, 'gy', 'Guyana', 57),
			(94, 'ht', 'Haiti', 160),
			(95, 'hm', 'Heard Island And Mcdonald Islands', 11),
			(96, 'va', 'Holy See (vatican City State)', 2),
			(97, 'hn', 'Honduras', 58),
			(98, 'hk', 'Hong Kong', 59),
			(99, 'hu', 'Hungary', 60),
			(100, 'is', 'Iceland', 61),
			(101, 'in', 'India', 62),
			(102, 'id', 'Indonesia', 63),
			(103, 'ir', 'Iran', 64),
			(104, 'iq', 'Iraq', 65),
			(105, 'ie', 'Ireland', 2),
			(106, 'im', 'Isle Of Man', NULL),
			(107, 'il', 'Israel', 66),
			(108, 'it', 'Italy', 2),
			(109, 'jm', 'Jamaica', 67),
			(110, 'jp', 'Japan', 68),
			(111, 'je', 'Jersey', 55),
			(112, 'jo', 'Jordan', 69),
			(113, 'kz', 'Kazakhstan', 70),
			(114, 'ke', 'Kenya', 71),
			(115, 'ki', 'Kiribati', 11),
			(116, 'kp', 'Korea', 72),
			(117, 'kr', 'Korea', 73),
			(118, 'kw', 'Kuwait', 74),
			(119, 'kg', 'Kyrgyzstan', 75),
			(120, 'la', 'Lao', 76),
			(121, 'lv', 'Latvia', 77),
			(122, 'lb', 'Lebanon', 78),
			(123, 'ls', 'Lesotho', 121),
			(124, 'lr', 'Liberia', 79),
			(125, 'ly', 'Libyan Arab Jamahiriya', 80),
			(126, 'li', 'Liechtenstein', 81),
			(127, 'lt', 'Lithuania', 82),
			(128, 'lu', 'Luxembourg', 2),
			(129, 'mo', 'Macao', 83),
			(130, 'mk', 'Macedonia', 84),
			(131, 'mg', 'Madagascar', 85),
			(132, 'mw', 'Malawi', 86),
			(133, 'my', 'Malaysia', 87),
			(134, 'mv', 'Maldives', 88),
			(135, 'ml', 'Mali', 19),
			(136, 'mt', 'Malta', 2),
			(137, 'mh', 'Marshall Islands', 5),
			(138, 'mq', 'Martinique', 2),
			(139, 'mr', 'Mauritania', 89),
			(140, 'mu', 'Mauritius', 90),
			(141, 'yt', 'Mayotte', 2),
			(142, 'mx', 'Mexico', 161),
			(143, 'fm', 'Micronesia', 2),
			(144, 'md', 'Moldova', 91),
			(145, 'mc', 'Monaco', 2),
			(146, 'mn', 'Mongolia', 92),
			(147, 'me', 'Montenegro', 2),
			(148, 'ms', 'Montserrat', 7),
			(149, 'ma', 'Morocco', 93),
			(150, 'mz', 'Mozambique', 94),
			(151, 'mm', 'Myanmar', 95),
			(152, 'na', 'Namibia', 121),
			(153, 'nr', 'Nauru', 11),
			(154, 'np', 'Nepal', 96),
			(155, 'nl', 'Netherlands', 2),
			(156, 'an', 'Netherlands Antilles', 97),
			(157, 'nc', 'New Caledonia', 49),
			(158, 'nz', 'New Zealand', 36),
			(159, 'ni', 'Nicaragua', 98),
			(160, 'ne', 'Niger', 19),
			(161, 'ng', 'Nigeria', 99),
			(162, 'nu', 'Niue', 36),
			(163, 'nf', 'Norfolk Island', 11),
			(164, 'mp', 'Northern Mariana Islands', 5),
			(165, 'no', 'Norway', 23),
			(166, 'om', 'Oman', 100),
			(167, 'pk', 'Pakistan', 101),
			(168, 'pw', 'Palau', 5),
			(169, 'ps', 'Palestinian Territory', NULL),
			(170, 'pa', 'Panama', 162),
			(171, 'pg', 'Papua New Guinea', 102),
			(172, 'py', 'Paraguay', 103),
			(173, 'pe', 'Peru', 104),
			(174, 'ph', 'Philippines', 105),
			(175, 'pn', 'Pitcairn', 36),
			(176, 'pl', 'Poland', 106),
			(177, 'pt', 'Portugal', 2),
			(178, 'pr', 'Puerto Rico', 5),
			(179, 'qa', 'Qatar', 107),
			(180, 're', 'Reunion', 2),
			(181, 'ro', 'Romania', 108),
			(182, 'ru', 'Russian Federation', 109),
			(183, 'rw', 'Rwanda', 110),
			(184, 'bl', 'Saint Barthelemy', 2),
			(185, 'sh', 'Saint Helena', 111),
			(186, 'kn', 'Saint Kitts And Nevis', 7),
			(187, 'lc', 'Saint Lucia', 7),
			(188, 'mf', 'Saint Martin', 2),
			(189, 'pm', 'Saint Pierre And Miquelon', 2),
			(190, 'vc', 'Saint Vincent And The Grenadines', 7),
			(191, 'ws', 'Samoa', 112),
			(192, 'sm', 'San Marino', 2),
			(193, 'st', 'Sao Tome And Principe', 113),
			(194, 'sa', 'Saudi Arabia', 114),
			(195, 'sn', 'Senegal', 19),
			(196, 'rs', 'Serbia', 115),
			(197, 'sc', 'Seychelles', 116),
			(198, 'sl', 'Sierra Leone', 117),
			(199, 'sg', 'Singapore', 118),
			(200, 'sk', 'Slovakia', 2),
			(201, 'si', 'Slovenia', 2),
			(202, 'sb', 'Solomon Islands', 119),
			(203, 'so', 'Somalia', 120),
			(204, 'za', 'South Africa', 121),
			(205, 'gs', 'South Georgia', NULL),
			(206, 'es', 'Spain', 2),
			(207, 'lk', 'Sri Lanka', 122),
			(208, 'sd', 'Sudan', 123),
			(209, 'sr', 'Suriname', 124),
			(210, 'sj', 'Svalbard And Jan Mayen', 23),
			(211, 'sz', 'Swaziland', 125),
			(212, 'se', 'Sweden', 126),
			(213, 'ch', 'Switzerland', 81),
			(214, 'sy', 'Syrian Arab Republic', 127),
			(215, 'tw', 'Taiwan, Province Of China', 128),
			(216, 'tj', 'Tajikistan', 129),
			(217, 'tz', 'Tanzania', 130),
			(218, 'th', 'Thailand', 131),
			(219, 'tl', 'Timor-leste', 5),
			(220, 'tg', 'Togo', 19),
			(221, 'tk', 'Tokelau', 36),
			(222, 'to', 'Tonga', 132),
			(223, 'tt', 'Trinidad And Tobago', 133),
			(224, 'tn', 'Tunisia', 134),
			(225, 'tr', 'Turkey', 135),
			(226, 'tm', 'Turkmenistan', 136),
			(227, 'tc', 'Turks And Caicos Islands', 5),
			(228, 'tv', 'Tuvalu', 11),
			(229, 'ug', 'Uganda', 137),
			(230, 'ua', 'Ukraine', 138),
			(231, 'ae', 'United Arab Emirates', 139),
			(232, 'gb', 'United Kingdom', 55),
			(233, 'us', 'United States', 5),
			(234, 'um', 'United States Minor Outlying Islands', 5),
			(235, 'uy', 'Uruguay', 163),
			(236, 'uz', 'Uzbekistan', 140),
			(237, 'vu', 'Vanuatu', 141),
			(239, 've', 'Venezuela', 142),
			(240, 'vn', 'Viet Nam', 143),
			(241, 'vg', 'Virgin Islands, British', 5),
			(242, 'vi', 'Virgin Islands, U.s.', 5),
			(243, 'wf', 'Wallis And Futuna', 49),
			(244, 'eh', 'Western Sahara', 93),
			(245, 'ye', 'Yemen', 144),
			(246, 'zm', 'Zambia', 145),
			(247, 'zw', 'Zimbabwe', 146),
			(248, 'ct', 'Catalonia', 2)" );

	if( $set_preferred_country && !empty( $current_locale ) )
	{	// Set default preferred country from current locale
		$result = array();
		preg_match('#.*?-(.*)#', strtolower($current_locale),$result);

		$DB->query( "UPDATE $table_name
			SET ctry_preferred = 1
			WHERE ctry_code = '".$DB->escape($result[1])."'" );
	}
	task_end();
}


/**
 * Create default regions
 *
 */
function create_default_regions()
{
	global $DB, $current_charset;

	task_begin( 'Creating default regions... ' );
	$DB->query( convert_charset("
		INSERT INTO T_regional__region ( rgn_ID, rgn_ctry_ID, rgn_code, rgn_name )
		VALUES".
			/* United States */"
			(1, 233, 'AL', 'Alabama'),
			(2, 233, 'AK', 'Alaska'),
			(3, 233, 'AZ', 'Arizona'),
			(4, 233, 'AR', 'Arkansas'),
			(5, 233, 'CA', 'California'),
			(6, 233, 'CO', 'Colorado'),
			(7, 233, 'CT', 'Connecticut'),
			(8, 233, 'DE', 'Delaware'),
			(9, 233, 'FL', 'Florida'),
			(10, 233, 'GA', 'Georgia'),
			(11, 233, 'HI', 'Hawaii'),
			(12, 233, 'ID', 'Idaho'),
			(13, 233, 'IL', 'Illinois'),
			(14, 233, 'IN', 'Indiana'),
			(15, 233, 'IA', 'Iowa'),
			(16, 233, 'KS', 'Kansas'),
			(17, 233, 'KY', 'Kentucky'),
			(18, 233, 'LA', 'Louisiana'),
			(19, 233, 'ME', 'Maine'),
			(20, 233, 'MD', 'Maryland'),
			(21, 233, 'MA', 'Massachusetts'),
			(22, 233, 'MI', 'Michigan'),
			(23, 233, 'MN', 'Minnesota'),
			(24, 233, 'MS', 'Mississippi'),
			(25, 233, 'MO', 'Missouri'),
			(26, 233, 'MT', 'Montana'),
			(27, 233, 'NE', 'Nebraska'),
			(28, 233, 'NV', 'Nevada'),
			(29, 233, 'NH', 'New Hampshire'),
			(30, 233, 'NJ', 'New Jersey'),
			(31, 233, 'NM', 'New Mexico'),
			(32, 233, 'NY', 'New York'),
			(33, 233, 'NC', 'North Carolina'),
			(34, 233, 'ND', 'North Dakota'),
			(35, 233, 'OH', 'Ohio'),
			(36, 233, 'OK', 'Oklahoma'),
			(37, 233, 'OR', 'Oregon'),
			(38, 233, 'PA', 'Pennsylvania'),
			(39, 233, 'RI', 'Rhode Island'),
			(40, 233, 'SC', 'South Carolina'),
			(41, 233, 'SD', 'South Dakota'),
			(42, 233, 'TN', 'Tennessee'),
			(43, 233, 'TX', 'Texas'),
			(44, 233, 'UT', 'Utah'),
			(45, 233, 'VT', 'Vermont'),
			(46, 233, 'VA', 'Virginia'),
			(47, 233, 'WA', 'Washington'),
			(48, 233, 'WV', 'West Virginia'),
			(49, 233, 'WI', 'Wisconsin'),
			(50, 233, 'WY', 'Wyoming')",
		$current_charset, 'iso-8859-1' ) );

	task_end();
}


/**
 * Create default scheduled jobs that don't exist yet:
 * - Prune page cache
 * - Prune hit log & session log from stats
 * - Poll antispam blacklist
 *
 * @param boolean true if it's called from the ugrade script, false if it's called from the install script
 */
function create_default_jobs( $is_upgrade = false )
{
	global $DB, $localtimenow;

	// get tomorrow date
	$today = date2mysql( $localtimenow );
	$tomorrow = date2mysql( $localtimenow + 86400 );
	$ctsk_params = $DB->quote( 'N;' );
	$next_sunday = date2mysql( strtotime( 'next Sunday',  $localtimenow + 86400 ) );

	$cleanup_jobs_key         = 'cleanup-scheduled-jobs';
	$cleanup_email_logs_key   = 'cleanup-email-logs';
	$heavy_db_maintenance_key = 'heavy-db-maintenance';
	$light_db_maintenance_key = 'light-db-maintenance';
	$poll_antispam_key        = 'poll-antispam-blacklist';
	$process_hitlog_key       = 'process-hit-log';
	$prune_pagecache_key      = 'prune-old-files-from-page-cache';
	$prune_sessions_key       = 'prune-old-hits-and-sessions';
	$prune_comments_key       = 'prune-recycled-comments';
	$activate_reminder_key    = 'send-non-activated-account-reminders';
	$inactive_reminder_key    = 'send-inactive-account-reminders';
	$comment_reminder_key     = 'send-unmoderated-comments-reminders';
	$messages_reminder_key    = 'send-unread-messages-reminders';
	$post_reminder_key        = 'send-unmoderated-posts-reminders';
	$alert_old_contents_key   = 'monthly-alert-old-contents';
	$execute_automations_key  = 'execute-automations';
	$manage_email_status_key  = 'manage-email-statuses';
	$process_return_path_key  = 'process-return-path-inbox';

	// init insert values
	$insert_values = array(
			$execute_automations_key  => "( ".$DB->quote( form_date( $today, '00:00:00' ) ).", 300, ".$DB->quote( $execute_automations_key ).", ".$ctsk_params." )",

			// run check return path inbox every 11 minutes:
			$process_return_path_key  => "( ".$DB->quote( form_date( $tomorrow, '00:03:00' ) ).", 660, ".$DB->quote( $process_return_path_key ).", ".$ctsk_params." )",
			// run unread messages reminder in every 29 minutes:
			$messages_reminder_key    => "( ".$DB->quote( form_date( $tomorrow, '01:06:00' ) ).", 1740,  ".$DB->quote( $messages_reminder_key ).", ".$ctsk_params." )",
			// run activate account reminder in every 31 minutes:
			$activate_reminder_key    => "( ".$DB->quote( form_date( $tomorrow, '01:09:00' ) ).", 1860,  ".$DB->quote( $activate_reminder_key ).", ".$ctsk_params." )",

			$prune_pagecache_key      => "( ".$DB->quote( form_date( $tomorrow, '02:00:00' ) ).", 86400, ".$DB->quote( $prune_pagecache_key ).", ".$ctsk_params." )",
			$process_hitlog_key       => "( ".$DB->quote( form_date( $tomorrow, '02:15:00' ) ).", 86400, ".$DB->quote( $process_hitlog_key ).", ".$ctsk_params." )",
			$prune_sessions_key       => "( ".$DB->quote( form_date( $tomorrow, '02:30:00' ) ).", 86400, ".$DB->quote( $prune_sessions_key ).", ".$ctsk_params." )",
			$poll_antispam_key        => "( ".$DB->quote( form_date( $tomorrow, '02:45:00' ) ).", 86400, ".$DB->quote( $poll_antispam_key ).", ".$ctsk_params." )",
			$post_reminder_key        => "( ".$DB->quote( form_date( $tomorrow, '03:00:00' ) ).", 86400, ".$DB->quote( $post_reminder_key ).", ".$ctsk_params." )",
			$inactive_reminder_key    => "( ".$DB->quote( form_date( $tomorrow, '03:15:00' ) ).", 86400, ".$DB->quote( $inactive_reminder_key ).", ".$ctsk_params." )",
			$comment_reminder_key     => "( ".$DB->quote( form_date( $tomorrow, '03:30:00' ) ).", 86400, ".$DB->quote( $comment_reminder_key ).", ".$ctsk_params." )",
			$prune_comments_key       => "( ".$DB->quote( form_date( $tomorrow, '03:45:00' ) ).", 86400, ".$DB->quote( $prune_comments_key ).", ".$ctsk_params." )",
			$cleanup_email_logs_key   => "( ".$DB->quote( form_date( $tomorrow, '04:00:00' ) ).", 86400, ".$DB->quote( $cleanup_email_logs_key ).", ".$ctsk_params." )",
			$manage_email_status_key  => "( ".$DB->quote( form_date( $tomorrow, '04:15:00' ) ).", 86400, ".$DB->quote( $manage_email_status_key ).", ".$ctsk_params." )",
			$cleanup_jobs_key         => "( ".$DB->quote( form_date( $tomorrow, '04:30:00' ) ).", 86400, ".$DB->quote( $cleanup_jobs_key ).", ".$ctsk_params." )",
			$light_db_maintenance_key => "( ".$DB->quote( form_date( $tomorrow, '04:45:00' ) ).", 86400, ".$DB->quote( $light_db_maintenance_key ).", ".$ctsk_params." )",

			$alert_old_contents_key   => "( ".$DB->quote( form_date( $next_sunday, '05:00:00' ) ).", 604800, ".$DB->quote( $alert_old_contents_key ).", ".$ctsk_params." )",
			$heavy_db_maintenance_key => "( ".$DB->quote( form_date( $next_sunday, '05:15:00' ) ).", 604800, ".$DB->quote( $heavy_db_maintenance_key ).", ".$ctsk_params." )",
		);
	if( $is_upgrade )
	{	// Check if these jobs already exist, and don't create another
		$SQL = new SQL();
		$SQL->SELECT( 'COUNT( ctsk_ID ) AS job_number, ctsk_key' );
		$SQL->FROM( 'T_cron__task' );
		$SQL->FROM_add( 'LEFT JOIN T_cron__log ON ctsk_ID = clog_ctsk_ID' );
		$SQL->WHERE( 'clog_status IS NULL' );
		$SQL->WHERE_and( 'ctsk_key IN ( '.$DB->quote( array_keys( $insert_values ) ).' )' );
		$SQL->GROUP_BY( 'ctsk_key' );
		$result = $DB->get_results( $SQL->get() );
		foreach( $result as $row )
		{	// clear existing jobs insert values
			unset( $insert_values[ $row->ctsk_key ] );
		}
	}

	$values = implode( ', ', $insert_values );
	if( empty( $values ) )
	{	// nothing to create
		return;
	}

	task_begin( T_( 'Creating default scheduled jobs... ' ) );
	$DB->query( '
		INSERT INTO T_cron__task ( ctsk_start_datetime, ctsk_repeat_after, ctsk_key, ctsk_params )
		VALUES '.$values, T_( 'Create default scheduled jobs' ) );
	task_end();
}


/**
 * Create demo users
 *
 * @param array Array of organization IDs
 * @return  array Array of demo user objects
 */
function create_demo_users()
{
	global $admins_Group, $moderators_Group, $editors_Group, $users_Group, $suspect_Group, $spam_Group, $blogb_Group;

	$demo_Users = array();

	task_begin('Assigning avatar to Admin... ');
	$UserCache = & get_UserCache();
	$GroupCache = & get_GroupCache();
	$normal_Group = & $GroupCache->get_by_name( 'Normal Users' );
	$User_Admin = & $UserCache->get_by_ID( 1 );

	global $media_path;
	$src_admin_dir = $media_path.'users/admin';
	$dest_admin_dir = $media_path.'users/'.$User_Admin->login;
	if( $User_Admin->login != 'admin' )
	{	// If admin login is not "admin" we should try to rename folder of the admin avatars
		if( ! file_exists( $src_admin_dir ) ||
		    ! is_dir( $src_admin_dir ) ||
		    ! @rename( $src_admin_dir, $dest_admin_dir ) )
		{	// Impossible to rename the admin folder to another name

			// Display the errors:
			echo get_install_format_text_and_log( '<span class="text-danger"><evo:error>'.sprintf( 'ERROR: Impossible to rename <code>%s</code> to <code>%s</code>.', $src_admin_dir, $dest_admin_dir ).'</evo:error></span> ' );
			echo get_install_format_text_and_log( '<span class="text-danger"><evo:error>'.sprintf( 'ERROR: Impossible to use "%s" for the admin account. Using "admin" instead.', $User_Admin->login ).'</evo:error></span> ' );

			// Change admin login to "admin":
			$User_Admin->set( 'login', 'admin' );
			if( $User_Admin->dbupdate() )
			{	// Change global var of admin login for report:
				global $install_login;
				$install_login = 'admin';
			}
		}
	}

	if( file_exists( $media_path.'users/'.$User_Admin->login ) )
	{	// Do assign avatars to admin only if it the admin folder exists on the disk
		assign_profile_picture( $User_Admin, 'admin' );

		// Associate secondary picture:
		$File = new File( 'user', $User_Admin->ID, 'faceyourmanga_admin_boy.png' );
		// Load meta data AND MAKE SURE IT IS CREATED IN DB:
		$File->load_meta( true );
		// Set link between user and avatar file
		$LinkOwner = new LinkUser( $User_Admin );
		$File->link_to_Object( $LinkOwner );

		// Associate secondary picture:
		$File = new File( 'user', $User_Admin->ID, 'faceyourmanga_admin_girl.png' );
		// Load meta data AND MAKE SURE IT IS CREATED IN DB:
		$File->load_meta( true );
		// Set link between user and avatar file
		$LinkOwner = new LinkUser( $User_Admin );
		$File->link_to_Object( $LinkOwner );
	}
	task_end();

	$demo_Users = get_demo_users( true );

	return $demo_Users;
}


/**
 * Create default location for all posts
 */
function create_default_posts_location()
{
	global $install_test_features;

	if( $install_test_features )
	{	// Set default location in test mode installation
		global $DB;

		$DB->query( 'UPDATE T_items__item SET
			post_ctry_ID = '.$DB->quote( '74'/* France */ ) );

		echo_install_log( 'TEST FEATURE: Defining default location "France" for all posts' );
	}
}


/**
 * Create default templates that don't already exist
 *
 * @param boolean TRUE to use this as separate task
 */
function create_default_templates( $is_task = true )
{
	global $DB;

	if( $is_task )
	{
		task_begin( 'Creating default templates... ' );
	}

	$templates = array(
		// Item Info "info line" replacements:
		'item_details_infoline_date' => array(
			'name'     => 'Item Details: Posted on Date at Time',
			'context'  => 'item_details',
			'template' => '<span class="small text-muted">[flag_icon] Posted on [issue_time|time_format=#extended_date] at [issue_time|time_format=#short_time]</span>',
		),
		'item_details_infoline_standard' => array(
			'name'     => 'Item Details: Posted by Author on Date at Time in Categories',
			'context'  => 'item_details',
			'template' => '<span class="small text-muted">[flag_icon] Posted by [author] on [issue_time|time_format=#extended_date] in [categories]</span>',
		),
		'item_details_infoline_long' => array(
			'name'     => 'Item Details: Long info line',
			'context'  => 'item_details',
			'template' => '<span class="small text-muted">[flag_icon] [Item:permalink|text=#linkicon] Posted by [author] on [issue_date|date_format=#extended_date] at [issue_time|time_format=#short_time] in [categories] — Last touched: [last_touched] — Last Updated: [contents_last_updated][refresh_contents_last_updated_link] [edit_link]</span>',
		),
		'item_details_infoline_forums' => array(
			'name'     => 'Item Details: Thread last updated on Date',
			'context'  => 'item_details',
			'template' => '<span class="small text-muted">[flag_icon] Thread last updated on [contents_last_updated|format=#extended_date] at [contents_last_updated|format=#short_time] [refresh_contents_last_updated_link]</span>',
		),

		// Item info New :
		'item_details_feedback_link' => array(
			'name'     => 'Item Details: Comment Link',
			'context'  => 'item_details',
			'template' => '<nav class="post_comments_link">[feedback_link]</nav>'
		),

		// Item Info "Small print" replacements:
		'item_details_smallprint_standard' => array(
			'name'     => 'Item Details: Small Print: Standard',
			'context'  => 'item_details',
			'template' => '[author|link_text=only_avatar|thumb_size=crop-top-32x32|link_class=leftmargin] This entry was posted by [author|link_text=preferredname] and filed under [categories].[tags|before= Tags: |after=.] [edit_link]'
		),
		'item_details_smallprint_long' => array(
			'name'     => 'Item Details: Small Print: Long',
			'context'  => 'item_details',
			'template' => '[author|link_text=only_avatar|thumb_size=crop-top-32x32|link_class=leftmargin] [flag_icon] This entry was posted on [issue_time|time_format=#extended_date] at [issue_time|time_format=#short_time] by [author|link_text=preferredname] and filed under [categories].[tags|before= Tags: |after=.] [edit_link]'
		),
		'item_details_revisions' => array(
			'name'     => 'Item Details: Small Print: Revisions',
			'context'  => 'item_details',
			'template' => '[flag_icon] Created by [author] &bull; Last edit by [lastedit_user] on [mod_date|date_format=#extended_date] [history_link|before=&bull; ] [propose_change_link|before=&bull; ]'
		),
		'item_details_author_details' => array(
			'name'     => 'Item Details: Author Details',
			'context'  => 'item_details',
			'template' => '<table>
	<tbody>
		<tr>
			<th>Picture</th>
			<td>[User:picture|size=crop-top-128x128]</td>
		</tr>
		<tr>
			<th>Fullname</th>
			<td>[User:fullname]</td>
		</tr>
		<tr>
			<th>Last name</th>
			<td>[User:last_name]</td>
		</tr>
		<tr>
			<th>First name</th>
			<td>[User:first_name]</td>
		</tr>
		<tr>
			<th>Nickname</th>
			<td>[User:nick_name]</td>
		</tr>
		<tr>
			<th>Preferred name</th>
			<td>[User:preferred_name]</td>
		</tr>
		<tr>
			<th>ID</th>
			<td>[User:id]</td>
		</tr>
		<tr>
			<th>Login</th>
			<td>[User:login]</td>
		</tr>
		<tr>
			<th>Email</th>
			<td>[User:email]</td>
		</tr>
		<tr>
			<th>Micro bio</th>
			<td>[User:custom|field=microbio]</td>
		</tr>
		<tr>
			<th>Twitter</th>
			<td>[User:custom|field=twitter]</td>
		</tr>
		<tr>
			<th>Facebook</th>
			<td>[User:custom|field=facebook]</td>
		</tr>
		<tr>
			<th>LinkedIn</th>
			<td>[User:custom|field=linkedin]</td>
		</tr>
		<tr>
			<th>GitHub</th>
			<td>[User:custom|field=github]</td>
		</tr>
		<tr>
			<th>Website</th>
			<td>[User:custom|field=website|separator=<br />]</td>
		</tr>
	</tbody>
</table>'
		),

		// Item attachments:
		'item_details_files_list' => array(
			'name'     => 'Item Details: Attachments: List',
			'context'  => 'item_details',
			'template' => '[files|
				before=<div class="item_attachments"><ul class="bFiles">|
				before_attach=<li>|
				before_attach_size=<span class="file_size">(|
				after_attach_size=)</span>|
				after_attach=</li>|
				after=</ul></div>|
				file_link_format=$file_name$|
				display_download_icon=1|
				file_link_text=title|
				display_file_size=1|
				display_file_desc=1|
			]'
		),
		'item_details_files_buttons' => array(
			'name'     => 'Item Details: Attachments: Buttons',
			'context'  => 'item_details',
			'template' => '[files|
				before=|
				before_attach=|
				before_attach_size=(|
				after_attach_size=)|
				after_attach=|
				after=|
				attach_format=$file_link$|
				file_link_format=$icon$ <b>Download Now!</b><br />$file_name$ $file_size$ $file_desc$|
				display_download_icon=1|
				file_link_text=title|
				file_link_class=btn btn-success|
				display_file_size=1|
				display_file_desc=1|
			]'
		),

		// About Author widget:
		'about_author' => array(
			'name'     => 'Item Details: About Author',
			'context'  => 'item_details',
			'template' => '<div class="clearfix"><div class="evo_avatar" rel="bubbletip_user_[User:id]">
	[User:picture|size=crop-top-48x48]
</div>
<div class="evo_author_display_field">
	[User:custom|field=microbio]
</div></div>',
		),

		// Content List widget:
		'content_list' => array(
			'name'     => 'Content List',
			'context'  => 'content_list_master',
			'template' => '[set:before_list=<ul class="chapters_list posts_list">]
[set:after_list=</ul>]
[set:subcat_template=content_list_subcat]
[set:item_template=content_list_item]
[set:crossposted_item_template=content_list_crossposted_item| // Use same as item_template]
[set:active_item_template=content_list_active_item| // Use same as item_template]',
		),
		'content_list_subcat' => array(
			'name'     => 'Content List: Subcat',
			'context'  => 'content_list_category',
			'template' => '<li class="chapter">
	<h3>[Cat:permalink|text=#expandicon+name|class=link]</h3>
	<div class="evo_cat__description">[Cat:description]</div>
</li>',
		),
		'content_list_item' => array(
			'name'     => 'Content List: Item',
			'context'  => 'content_list_item',
			'template' => '<li>
	<h3>[read_status] [Item:permalink|text=#fileicon+title|class=link] [flag_icon]</h3>[visibility_status]
	[Item:excerpt|
		before=<div class="evo_post__excerpt_text">|
		after=</div>|
		excerpt_before_more=<span class="evo_post__excerpt_more_link">|
		excerpt_more_text=#more+arrow|excerpt_after_more=</span>]
</li>',
		),
		'content_list_crossposted_item' => array(
			'name'     => 'Content List: Crossposted Item',
			'context'  => 'content_list_item',
			'template' => '<li><i>
	<h3>[read_status] [Item:permalink|text=#fileicon+title|class=link] [flag_icon]</h3>[visibility_status]
	[Item:excerpt|
		before=<div class="evo_post__excerpt_text">|
		after=</div>|
		excerpt_before_more=<span class="evo_post__excerpt_more_link">|
		excerpt_more_text=#more+arrow|excerpt_after_more=</span>]
</i></li>',
		),

		// Content Title List:
		'content_title_list' => array(
			'name'     => 'Content Title List',
			'context'  => 'content_list_master',
			'template' => '[set:before_list=<ul>]
[set:after_list=</ul>]
[set:item_template=content_title_list_item]
[set:crossposted_item_template=| // Use same as item_template]
[set:active_item_template=content_title_list_active_item]',
		),
		'content_title_list_item' => array(
			'name'     => 'Content Title List: Item',
			'context'  => 'content_list_item',
			'template' => '<li>[Item:permalink|class=default|title=]</li>',
		),
		'content_title_list_active_item' => array(
			'name'     => 'Content Title List: Active Item',
			'context'  => 'content_list_item',
			'template' => '<li class="selected">[Item:permalink|class=selected|title=]</li>',
		),


		// Content Tiles style 1 (default):
		'content_tiles' => array(
            'name'     => 'Content Tiles Style 1 (Fully clickable)',
            'context'  => 'content_list_master',
            'template' => '[set:before_list=<div class="evo_tiles row">]
[set:after_list=</div>]
[set:subcat_template=content_tiles_subcat|          // Sub-template for displaying categories]
[set:item_template=content_tiles_item|              // Sub-template for displaying items]
[set:crossposted_item_template=|                    // Sub-template for displaying crossposted items]
[set:active_item_template=|                         // Sub-template for displaying active item]
[set:rwd_cols=col-xs-12 col-sm-6 col-md-6 col-lg-4| // RWD classes for tile containers]
[set:evo_tile__modifiers=evo_tile__md evo_tile__grey_bg evo_tile__hoverglow| // Modifier classes for each tile]
[set:evo_tile_image__modifiers=|                    // Modifier classes for each tile image]
[set:evo_tile_image__classes=evo_image_block|       // Modifier classes for each evo_image_block]
[set:evo_tile_image__size=fit-400x320|              // Image size for old browsers]
[set:evo_tile_image__sizes=(max-width: 430px) 400px, (max-width: 670px) 640px, (max-width: 767px) 720px, (max-width: 991px) 345px, (max-width: 1199px) 334px, (max-width: 1799px) 262px, 400px]
[set:evo_tile_text__modifiers=evo_tile_text__gradient]
',
		),
		'content_tiles_contain' => array(
			'name'     => 'Content Tiles Style 1.1 (Contained images)',
			'context'  => 'content_list_master',
			'template' => '[set:before_list=<div class="evo_tiles row">]
[set:after_list=</div>]
[set:subcat_template=content_tiles_subcat]
[set:item_template=content_tiles_item]
[set:crossposted_item_template=| // Use same as item_template]
[set:active_item_template=| // Use same as item_template]
[set:rwd_cols=col-xs-12 col-sm-6 col-md-6 col-lg-4]
[set:evo_tile__modifiers=evo_tile__md evo_tile__grey_bg evo_tile__hoverglow]
[set:evo_tile_image__modifiers=]
[set:evo_tile_image__classes=evo_image_block contain]
[set:evo_tile_image__size=fit-400x320]
[set:evo_tile_image__sizes=(max-width: 430px) 400px, (max-width: 670px) 640px, (max-width: 767px) 720px, (max-width: 991px) 345px, (max-width: 1199px) 334px, (max-width: 1799px) 262px, 400px]
[set:evo_tile_text__modifiers=evo_tile_text__gradient]
',
		),

		'content_tiles_subcat' => array(
			'name'     => 'Content Tiles Style 1 (Fully clickable): Subcat',
			'context'  => 'content_list_category',
			'template' => '<div class="[echo:rwd_cols]">
	<div class="evo_tile [echo:evo_tile__modifiers]">
		<div class="hide_overflow">
			<div class="evo_tile_image [echo:evo_tile_image__modifiers]">
				[Cat:image|
					size=$evo_tile_image__size$|
					sizes=$evo_tile_image__sizes$|
					link_to=#category_url|
					before=<figure class="evo_image_block">|
					before_classes=$evo_tile_image__classes$|
					after=</figure>]
			</div>
			<div class="evo_tile_body">
				<h3>[Cat:name]</h3>
				<div class="evo_tile_text [echo:evo_tile_text__modifiers]">[Cat:description]</div>
			</div>
		</div>
		[Cat:permalink|text=]
	</div>
</div>',
		),

		'content_tiles_item' => array(
			'name'     => 'Content Tiles Style 1 (Fully clickable): Item',
			'context'  => 'content_list_item',
			'template' => '<div class="[echo:rwd_cols]">
	<div class="evo_tile [echo:evo_tile__modifiers]">
		<div class="hide_overflow">
			<div class="evo_tile_image [echo:evo_tile_image__modifiers]">
				[Item:images|
					restrict_to_image_position=#cover_and_teaser_all| // Priority to cover image, fall back to any teaser image
					limit=1|	                                      // Max 1 images
					image_size=$evo_tile_image__size$|                // Size for old browsers
					image_sizes=$evo_tile_image__sizes$|	          // RWD Sizes for modern browsers
					image_link_to=|	                                  // Do NOT link to anything
					placeholder=#file_text_icon|	                  // If no image available, display text file icon
					before_image_classes=$evo_tile_image__classes$|	  // CSS classes to inject into evo_image_block
				]
				<div class="evo_tile_overlay">[Item:cat_name]</div>
			</div>
			<div class="evo_tile_body">
				<h3>[Item:title]</h3>
				<div class="evo_tile_text [echo:evo_tile_text__modifiers]">
					[Item:excerpt|
						excerpt_more_text=| // No "more" link
					]
				</div>
			</div>
		</div>
		[Item:permalink|text=| // This is a link without text which will cover the whole tile and make the whole tile clickable]
	</div>
</div>',
		),


		// Tiles style 2:
		'content_tiles_btn' => array(
			'name'     => 'Content Tiles Style 2 (Button)',
			'context'  => 'content_list_master',
			'template' => '[set:before_list=<div class="evo_tiles row">]
[set:after_list=</div>]
[set:subcat_template=content_tiles_btn_subcat]
[set:item_template=content_tiles_btn_item]
[set:crossposted_item_template=| // Use same as item_template]
[set:active_item_template=| // Use same as item_template]
[set:rwd_cols=col-xs-12 col-sm-6 col-md-6 col-lg-4]
[set:evo_tile__modifiers=evo_tile__md evo_tile__grey_bg evo_tile__shadow]
[set:evo_tile_image__modifiers=evo_tile_image__margin]
[set:evo_tile_image__classes=evo_image_block]
[set:evo_tile_image__size=fit-400x320]
[set:evo_tile_image__sizes=(max-width: 430px) 400px, (max-width: 670px) 640px, (max-width: 767px) 720px, (max-width: 991px) 345px, (max-width: 1199px) 334px, (max-width: 1799px) 262px, 400px]
[set:evo_tile_text__modifiers=evo_tile_text__gradient]',
		),

		'content_tiles_btn_subcat' => array(
			'name'     => 'Content Tiles Style 2 (Button): Subcat',
			'context'  => 'content_list_category',
			'template' => '<div class="[echo:rwd_cols]">
	<div class="evo_tile [echo:evo_tile__modifiers]">
		<div class="hide_overflow">
			<div class="evo_tile_image [echo:evo_tile_image__modifiers]">
				[Cat:image|
					size=$evo_tile_image__size$|
					sizes=$evo_tile_image__sizes$|
					link_to=#category_url|
					before=<figure class="evo_image_block">|
					after=</figure>|
					before_classes=$evo_tile_image__classes$]
			</div>
			<div class="evo_tile_body">
				<h3>[Cat:permalink|class=evo_tile_title]</h3>
				<div class="evo_tile_text [echo:evo_tile_text__modifiers]">[Cat:description]</div>
				[Cat:permalink|text=#view+arrow|class=evo_tile_more btn btn-sm btn-default]
			</div>
		</div>
	</div>
</div>',
		),

		'content_tiles_btn_item' => array(
			'name'     => 'Content Tiles Style 2 (Button): Item',
			'context'  => 'content_list_item',
			'template' => '<div class="[echo:rwd_cols]">
	<div class="evo_tile [echo:evo_tile__modifiers]">
		<div class="hide_overflow">
			<div class="evo_tile_image [echo:evo_tile_image__modifiers]">
				[Item:images|
					restrict_to_image_position=#cover_and_teaser_all|
					limit=1|
					image_size=$evo_tile_image__size$|
					image_sizes=$evo_tile_image__sizes$|
					image_link_to=single|
					placeholder=#file_text_icon|
					before_image_classes=$evo_tile_image__classes$]
				<div class="evo_tile_overlay">[Item:cat_name]</div>
			</div>
			<div class="evo_tile_body">
				<h3>[Item:permalink|text=#title|class=evo_tile_title]</h3>
				<div class="evo_tile_text [echo:evo_tile_text__modifiers]">[Item:excerpt|excerpt_more_text=]</div>
				[Item:permalink|text=#view+arrow|class=evo_tile_more btn btn-sm btn-default]
			</div>
		</div>
	</div>
</div>',
		),


		// Tiles style 3 (BG image:Experimental):
		'content_tiles_bgimg' => array(
			'name'     => 'Content Tiles Style 3 (BG image:Experimental)',
			'context'  => 'content_list_master',
			'template' => '[set:before_list=<div class="evo_tiles row">]
[set:after_list=</div>]
[set:subcat_template=content_tiles_bgimg_subcat]
[set:item_template=content_tiles_bgimg_item]
[set:crossposted_item_template=| // Use same as item_template]
[set:active_item_template=| // Use same as item_template]
[set:rwd_cols=col-xs-12 col-sm-6 col-md-6 col-lg-4]
[set:evo_tile__modifiers=evo_tile__md evo_tile__grey_bg evo_tile__square evo_tile__shadow]
[set:evo_tile_image__modifiers=]
[set:evo_tile_image__classes=evo_image_block]
[set:evo_tile_image__size=fit-400x320]
[set:evo_tile_image__sizes=(max-width: 430px) 400px, (max-width: 670px) 640px, (max-width: 767px) 720px, (max-width: 991px) 345px, (max-width: 1199px) 334px, (max-width: 1799px) 262px, 400px]
[set:evo_tile_text__modifiers=evo_tile_text__gradient]',
		),

		'content_tiles_bgimg_subcat' => array(
			'name'     => 'Content Tiles Style 3 (BG image:Experimental): Subcat',
			'context'  => 'content_list_category',
			'template' => '<div class="[echo:rwd_cols]">
	<div class="evo_tile [echo:evo_tile__modifiers]">
		<div class="hide_overflow">
			<div class="evo_tile_cover" style="[Cat:background_image_css|size=fit-400x320|size_2x=fit-720x500]"></div>
			<div class="evo_tile_body">
				<h3>[Cat:permalink|class=evo_tile_title]</h3>
				<div class="evo_tile_text [echo:evo_tile_text__modifiers]">[Cat:description]</div>
				[Cat:permalink|text=#view+arrow|class=evo_tile_more btn btn-sm btn-default]
			</div>
		</div>
	</div>
</div>',
		),

		'content_tiles_bgimg_item' => array(
			'name'     => 'Content Tiles Style 3 (BG image:Experimental): Item',
			'context'  => 'content_list_item',
			'template' => '<div class="[echo:rwd_cols]">
	<div class="evo_tile [echo:evo_tile__modifiers]">
		<div class="hide_overflow">
			<div class="evo_tile_cover" style="[Item:background_image_css|size=fit-400x320|size_2x=fit-720x500]">
				<div class="evo_tile_overlay">[Item:cat_name]</div>
			</div>
			<div class="evo_tile_body">
				<h3>[Item:permalink|text=#title|class=evo_tile_title]</h3>
				<div class="evo_tile_text [echo:evo_tile_text__modifiers]">[Item:excerpt|excerpt_more_text=]</div>
				[Item:permalink|text=#view+arrow|class=evo_tile_more btn btn-sm btn-default]
			</div>
		</div>
	</div>
</div>',
		),


		// Content Blocks:
		'cblock_clearfix' => array(
			'name'     => 'Include Content Block: with clearfix',
			'context'  => 'content_block',
			'template' => '<div class="evo_content_block clearfix [echo:content_block_class]">
	[Item:images|restrict_to_image_position=#teaser_all|before=<div class="evo_cblock_images evo_cblock_teaser">|after=</div>]
	<div class="evo_cblock_text">
		[Item:content_teaser] 
	</div>
	[Item:images|restrict_to_image_position=aftermore|before=<div class="evo_cblock_images evo_cblock_aftermore">|after=</div>]
</div>',
		),
		'cblock_noclearfix' => array(
			'name'     => 'Include Content Block: without clearfix',
			'context'  => 'content_block',
			'template' => '<div class="evo_content_block [echo:content_block_class]">
	[Item:images|
		restrict_to_image_position=#teaser_all|
		before=<div class="evo_cblock_images evo_cblock_teaser">|
		after=</div>]
	<div class="evo_cblock_text">
		[Item:content_teaser] 
	</div>
	[Item:images|
		restrict_to_image_position=aftermore|
		before=<div class="evo_cblock_images evo_cblock_aftermore">|
		after=</div>]
</div>',
		),

		// Item Contents:
		'item_content_excerpt' => array(
			'name' => 'Item Excerpt',
			'context'  => 'item_content',
			'template' => '<section class="evo_post__excerpt">
[Item:excerpt|
	before=<div class="evo_post__excerpt_text">|
	after=</div>|excerpt_before_more=<span class="evo_post__excerpt_more_link">|
	excerpt_more_text=#more+arrow|
	excerpt_after_more=</span>]
</section>',
		),
		'item_content_teaser' => array(
			'name' => 'Item Teaser content',
			'context'  => 'item_content',
			'template' => '<section class="evo_post__full">
[Item:images|
	restrict_to_image_position=#teaser_all|
	image_size=fit-1280x720|
	image_class=img-responsive|
	before=<div class="evo_post_images">|
	after=</div>|
	before_image=<figure class="evo_image_block">|
	after_image=</figure>]
<div class="evo_post__full_text clearfix">
	[Item:content_teaser]
	[Item:more_link|link_text=Read more &raquo;]
</div>
</section>',
		),
		'item_content_full' => array(
			'name' => 'Item Full content',
			'context'  => 'item_content',
			'template' => '<section class="evo_post__full">
[Item:images|
	restrict_to_image_position=#teaser_all|
	image_size=fit-1280x720|
	image_class=img-responsive|
	before=<div class="evo_post_images">|
	after=</div>|
	before_image=<figure class="evo_image_block">|
	after_image=</figure>]
<div class="evo_post__full_text clearfix">
	[Item:content_teaser]
	[Item:more_link|anchor_text=]
	[Item:images|
		restrict_to_image_position=aftermore|
		image_size=fit-1280x720|
		image_class=img-responsive|
		before=<div class="evo_post_images">|
		after=</div>|
		before_image=<figure class="evo_image_block">|
		after_image=</figure>]
	[Item:content_extension]
	[Item:page_links]
	[Item:footer]
</div>
</section>',
		),
		'recipe_content_full' => array(
			'name' => 'Recipe Full content',
			'context'  => 'item_content',
			'template' => '<section class="evo_post__full">
<div class="row">
	<div class="col-sm-5">
		[Item:images|
			restrict_to_image_position=#cover_and_teaser_all|
			image_size=crop-320x320|image_class=img-responsive|
			before=<div class="evo_post_images">|
			after=</div>|
			before_image=<figure class="evo_image_block">|
			after_image=</figure>]
	</div>
	<div class="col-sm-7">
		[Item:content_teaser]
		[Item:tags|before=<nav class="small post_tags">|after=</nav>|separator= ]
		[Item:custom_fields|
			fields=course,cuisine,servings|
			custom_fields_table_start=|
			custom_fields_row_start=<div class="row"$row_attrs$>|
			custom_fields_row_header_field=<div class="col-xs-3 $header_cell_class$"><b>$field_title$$field_description_icon$</b></div>|
			custom_fields_description_icon_class=grey|
			custom_fields_value_default=<div class="col-xs-9 $data_cell_class$"$data_cell_attrs$>$field_value$</div>|
			custom_fields_row_end=</div>|
			custom_fields_table_end=]
		[Item:custom_fields|
			fields=prep_time,cook_time,passive_time,total_time|
			custom_fields_table_start=<br /><div class="row">|
			custom_fields_row_start=<span$row_attrs$>|
			custom_fields_row_header_field=<div class="col-sm-3 col-xs-6 $header_cell_class$"><b>$field_title$$field_description_icon$</b>|
			custom_fields_description_icon_class=grey|
			custom_fields_value_default=<br /><span class="$data_cell_class$"$data_cell_attrs$>$field_value$</span></div>|
			custom_fields_row_end=</span>|
			custom_fields_table_end=</div>|
			hide_empty_lines=1]
	</div>
</div>
<div class="row">
	<div class="col-lg-3 col-sm-4">
		<h4>[Item:custom|field=ingredients|what=label]</h4>
		<p>[Item:custom|field=ingredients]</p>
	</div>
	<div class="col-lg-9 col-sm-8">
		<h4>Directions</h4>
		[Item:content_extension]
		[Item:page_links]
		[Item:footer]
		[Item:feedback_link]
	</div>
</div>
</section>',
		),
/*
		'cblock_imgleft_textright' => array(
			'name'     => 'Include Content Block: Images Left / Text Right',
			'context'  => 'content_block',
			'template' => '<div class="evo_content_block [cb_class]">
	<img src="[teaser_image]" class="floatleft">
	<div class="evo_content_block_text">
		[content] 
	</div>
</div>',
		),
		'cblock_textleft_imgright' => array(
			'name'     => 'Include Content Block: Text Left / Images Right',
			'context'  => 'content_block',
			'template' => '<div class="evo_content_block [cb_class]">
	<img src="[teaser_image]" class="floatright">
	<div class="evo_content_block_text">
		[content] 
	</div>
</div>',
		),
*/


		// Content Tabs:
		'content_tabs' => array(
			'name'     => 'Content Tabs',
			'context'  => 'content_list_master',
			'template' => '[set:before_list=<div class="row">]
[set:after_list=</div>]
[set:item_template=content_tabs_item]
[set:crossposted_item_template=| // Use same as item_template]
[set:active_item_template=| // Use same as item_template]
[set:rwd_header_col=col-sm-5 col-xs-12]
[set:rwd_text_col=col-sm-5 col-xs-12]
[set:rwd_image_col=col-sm-7 pull-right-sm col-xs-12]
[set:evo_tabs_image__size=fit-1920x1080]',
		),

		'content_tabs_item' => array(
			'name'     => 'Content Tabs: Item',
			'context'  => 'content_list_item',
			'template' => '
	<div class="[echo:rwd_header_col]">
		<h1>[Item:title]</h1>
	</div>
	<div class="[echo:rwd_image_col]">
		[Item:images|
			restrict_to_image_position=#cover_and_teaser_all|
			limit=1|
			image_size=$evo_tabs_image__size$]
	</div>
	<div class="[echo:rwd_text_col]">
		[Item:content_teaser]
	</div>
',
		),


		// Registration Templates:
		'registration_master_standard' => array(
			'name'     => 'Registration: Standard',
			'context'  => 'registration_master',
			'template' => '
[set:reg1_template=registration_standard]
[set:reg1_required=login,password,email]
',
		),

		'registration_standard' => array(
			'name'     => 'Registration: Standard',
			'context'  => 'registration',
			'template' => '[Form:login]
[Form:password]
[Form:email]
<div class="evo_register_buttons">
	[Form:submit|
		name=register|
		class=btn btn-primary btn-lg|
		value=Register my account now!]
	<br>
	[Link:disp|
		disp=login|
		class=btn btn-default|
		text=Already have an account... ?]
</div>',
		),



		// Registration Templates:
		'registration_master_ask_name' => array(   
			'name'     => 'Registration: Ask for Name',
			'context'  => 'registration_master',
			'template' => '
[set:reg1_template=registration_ask_name]
[set:reg1_required=firstname,password,email]
',
		),

		'registration_ask_name' => array(
			'name'     => 'Registration: Ask for Name',
			'context'  => 'registration',
			'template' => '[Form:firstname]
[Form:lastname]
[Form:email]
[Form:password]
<div class="evo_register_buttons">
	[Form:submit|
		name=register|
		class=btn btn-primary btn-lg|
		value=Register my account now!]
	<br>
	[Link:disp|
		disp=login|
		class=btn btn-default|
		text=Already have an account... ?]
</div>'
		),

		// Registration Templates:
		'registration_master_email_social' => array(   
			'name'     => 'Registration: email & social buttons',
			'context'  => 'registration_master',
			'template' => '
[set:reg1_template=registration_email_social]
[set:reg1_required=email]

[set:reg2_template=registration_step2| // Page 2 is not implemented yet]
[set:reg2_required=firstname]
',
		),

		'registration_email_social' => array(
			'name'     => 'Registration: email & social buttons',
			'context'  => 'registration',
			'template' => '[Form:email]
<div class="evo_register_buttons">
	[Form:submit|
		name=register|
		class=btn btn-primary btn-lg|
		value=Register my account now!]
	<br>
	[Link:disp|
		disp=login|
		class=btn btn-default|
		text=Already have an account... ?]
</div>
[Plugin:evo_sociallogin| // Call the SkinTag of the plugin
	before=<div class="evo_social_login_buttons margin-top-md">|
	after=</div>]
',
		),

		'registration_step2' => array(
			'name'     => 'Registration: Step 2',
			'context'  => 'registration',
			'template' => '[Form:firstname]
	[Form:lastname]
	[Form:custom_field|field=industry| // this is an example of a User custom field]
	[Form:submit|
		name=register|
		class=btn btn-primary btn-lg|
		value=Continue]
',
		),

		'search_form_full' => array(
			'name'     => 'Search Form: Full',
			'context'  => 'search_form',
			'template' => '<div class="row row-gutter-sm">
	<div class="col-sm-12 margin-top-sm margin-bottom-xs">
		<div class="input-group">
			[Form:search_input|class=w-100]
			<span class="input-group-btn">[Form:submit|value=Search]</span>
		</div>
	</div>
</div>
<div class="row row-gutter-sm">
	<div class="col-sm-12 col-md-4 col-lg-5 margin-y-xs">
		[Form:search_author]
	</div>
	<div class="col-sm-12 col-md-4 col-lg-4 margin-y-xs">
		[Form:search_content_age]
	</div>
	<div class="col-sm-12 col-md-4 col-lg-3 margin-y-xs">
		[Form:search_content_type]
	</div>
</div>',
		),

		'search_form_simple' => array(
			'name'     => 'Search Form: Simple',
			'context'  => 'search_form',
			'template' => '<div class="input-group">
	[Form:search_input|class=w-100]
	<span class="input-group-btn">[Form:submit|value=Search]</span>
</div>',
		),

		'search_result_item' => array(
			'name'     => 'Search Result: Item',
			'context'  => 'search_result',
			'template' => '<div class="search_result">
	<div class="search_result_score dimmed">[echo:percentage]%</div>
	<div class="search_content_wrap">
		<div class="search_title">[Item:permalink] <span class="label label-primary">[Item:type]</span></div>
		<div class="result_content">[Item:excerpt|excerpt_more_text=]</div>
		<div class="search_info dimmed">[Item:categories|before=In ]</div>
		<div class="search_info dimmed">Published by [Item:author|
			link_text=avatar_login|
			thumb_size=crop-top-15x15] on [Item:creation_time|format=#short_date]
		</div>
	</div>
</div>',
		),

		'search_result_comment' => array(
			'name'     => 'Search Result: Comment',
			'context'  => 'search_result',
			'template' => '<div class="search_result">
	<div class="search_result_score dimmed">[echo:percentage]%</div>
	<div class="search_content_wrap">
		<div class="search_title">[Comment:permalink] <span class="label label-primary">Comment</span></div>
		<div class="result_content">[Comment:excerpt]</div>
		<div class="search_info dimmed">Published by [Comment:author|
			link_text=avatar_name|
			thumb_size=crop-top-15x15|
			thumb_class=avatar_before_login] on [Comment:creation_time|format=#short_date]
		</div>
	</div>
</div>',
		),

		'search_result_meta' => array(
			'name'     => 'Search Result: Internal comment',
			'context'  => 'search_result',
			'template' => '<div class="search_result">
	<div class="search_result_score dimmed">[echo:percentage]%</div>
	<div class="search_content_wrap">
		<div class="search_title">[Comment:permalink] <span class="label label-info">Internal comment</span></div>
		<div class="result_content">[Comment:excerpt]</div>
		<div class="search_info dimmed">Published by [Comment:author|
			link_text=avatar_name|
			thumb_size=crop-top-15x15|
			thumb_class=avatar_before_login] on [Comment:creation_time|format=#short_date]
		</div>
	</div>
</div>',
		),

		'search_result_file' => array(
			'name'     => 'Search Result: File',
			'context'  => 'search_result',
			'template' => '<div class="search_result">
	<div class="search_result_score dimmed">[echo:percentage]%</div>
	<div class="search_content_wrap">
		<div class="search_title">[File:file_link|link_text=title] <span class="label label-primary">File: [File:file_link|link_text=icon] [File:type]</span></div>
		<div class="result_content">
			[File:url]
			[File:description|before=<div>|after=</div>]
		</div>
		<div class="search_info dimmed">File size: [File:file_size]</div>
	</div>
</div>',
		),

		'search_result_category' => array(
			'name'     => 'Search Result: Category',
			'context'  => 'search_result',
			'template' => '<div class="search_result">
	<div class="search_result_score dimmed">[echo:percentage]%</div>
	<div class="search_content_wrap">
		<div class="search_title">[Cat:permalink] <span class="label label-primary">Category</span></div>
		<div class="result_content">[Cat:description]</div>
	</div>
</div>',
		),

		'search_result_tag' => array(
			'name'     => 'Search Result: Tag',
			'context'  => 'search_result',
			'template' => '<div class="search_result">
	<div class="search_result_score dimmed">[echo:percentage]%</div>
	<div class="search_content_wrap">
		<div class="search_title">[Tag:permalink] <span class="label label-primary">Tag</span></div>
		<div class="result_content">[echo:tag_post_count] posts are tagged with "[Tag:name]"</div>
	</div>
</div>',
		),

		'content_list_with_thumbnail' => array(
			'name'     => 'Content List with Thumbnail',
			'context'  => 'content_list_master',
			'template' => '[set:before_list=<ul class="evo_thumblist">]
[set:after_list=</ul>]
[set:item_template=content_list_with_thumbnail_item| // Sub-template for displaying items]
[set:crossposted_item_template=|                     // Sub-template for displaying crossposted items]
[set:active_item_template=|                          // Sub-template for displaying active item]
[set:evo_thumblist_image__modifiers=|                // Modifier classes for each thumbnail image]
[set:evo_thumblist_image__size=crop-80x80|           // Image size for displaying image]',
		),

		'content_list_with_thumbnail_item' => array(
			'name'     => 'Content List with Thumbnail: Item',
			'context'  => 'content_list_item',
			'template' => '<li>
		<div class="evo_thumblist_image [echo:evo_thumblist_image__modifiers]">
			[Item:images|
				restrict_to_image_position=#cover_and_teaser_all| // Priority to cover image, fall back to any teaser image
				limit=1|	                                      // Max 1 images
				image_size=$evo_thumblist_image__size$|                
				image_link_to=single|	                                  // Link to item details
				placeholder=#file_thumbnail_text_icon|	                  // If no image available, display text file icon
			]
		</div>
		<div class="evo_thumblist_title">
			[Item:permalink|text=#title|class=default]
		</div>
		<div class="evo_thumblist_body">
			<p>[Item:excerpt|
							excerpt_no_more_link=| // No "more" link
							max_words=20| // how many words we will display
			]
			</p> 
			[Item:permalink|text=...|class=btn btn-default  evo_thumblist_button evo_thumblist_button__transparent|title=]
		</div>
</li>',
		)

	);

	$templates_sql = array();
	foreach( $templates as $code => $template )
	{
		$templates_sql[] = '( '.$DB->quote( $template['name'] ).', '.$DB->quote( $code ).', '.$DB->quote( $template['context'] ).', '.$DB->quote( $template['template'] ).' )';
	}

	// Insert/Update templates:
	$DB->query( 'INSERT INTO T_templates ( tpl_name, tpl_code, tpl_context, tpl_template_code )
		VALUES '.implode( ', ', $templates_sql ).'
		ON DUPLICATE KEY UPDATE tpl_name = VALUES( tpl_name ), tpl_context = VALUES( tpl_context ), tpl_template_code = VALUES( tpl_template_code )',
		'Creating/Updating default templates' );

	if( $is_task )
	{
		task_end();
	}
}
?>
