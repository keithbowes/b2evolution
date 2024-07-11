<?php
/**
 * This file includes advanced settings for the evoCore framework.
 *
 * Please NOTE: You should not comment variables out to prevent
 * URL overrides.
 *
 * @package conf
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


/**
 * Display debugging informations?
 *
 * 0 = no
 * 1 = yes
 * 2 = yes and potentially die() to display debug info (needed before redirects, e-g message_send.php)
 * 'pwd' = require password
 *
 * @global integer
 */
$debug = 'pwd';
$debug_jslog = 'pwd';

/**
 * When $debug is 'pwd' and you set a /password/ below,
 * you can turn on debugging at any time by adding ?debug=YOUR_PASSWORD to your url.
 * You can turn off by adding just ?debug
 *
 * You can ALSO turn on debugging of JavaScript(AJAX Requests) by adding ?jslog=YOUR_PASSWORD to your url.
 * You can turn off by adding just ?jslog
 *
 * @var string
 */
$debug_pwd = '';

// Most of the time you'll want to see all errors, including notices, to alert you on potential issues:
// b2evo should run without any notices! (same for plugins!)
error_reporting( E_ALL | E_STRICT );
/**
 * Do we want to display errors, even when not in debug mode?
 *
 * You are welcome to change/override this if you know what you're doing.
 * This is turned on by default so that newbies can quote meaningful error messages in the forums.
 */
$display_errors_on_production = true;


/**
 * Do you want to display the "Dev" menu in the evobar?
 * This allows to display the dev menu without necessarily enabling debugging.
 * This is useful for skin development on local machines.
 *
 * @var boolean - set to 1 to display the dev menu in the evobar.
 */
$dev_menu = 0;


// If you get blank pages or missing thumbnail images, PHP may be crashing because it doesn't have enough memory.
// The default is 128 MB (in PHP > 5.2)
// Try uncommmenting the following line:
// @ini_set( 'memory_limit', '128M' );


/**
 * Log application errors through {@link error_log() PHP's logging facilities}?
 *
 * This means that they will get logged according to PHP's error_log configuration directive.
 *
 * Experimental! This may be changed to use regular files instead/optionally.
 *
 * @todo Provide logging into normal file instead (more useful for backtraces/multiline error messages)
 *
 * @global integer 0: off; 1: log errors; 2: include function backtraces (Default: 1)
 */
$log_app_errors = 1;


/**
 * Allows to force the timezone used by PHP (in case it's not properly configured in php.ini)
 * See: http://b2evolution.net/man/date_default_timezone-forcing-a-timezone
 */
$date_default_timezone = '';


/**
 * Thumbnail/Image sizes
 *
 * This is used for resizing images to various sizes
 *
 * For each size: name => array( type, width, height, quality, percent of blur effect )
 *
 * @global array
 */
$thumbnail_sizes = array(
	// FIT: Typical images that will be shrunk to max width and/or max height but keep original aspect ratio (the ratios below are only for reference)
		// 16:9 ratio 1.77
			'fit-2880x1620'			=> array( 'fit', 2880, 1620, 80 ),		// 16:9 ratio 1.77	EXPERIMENTAL For Retina displays
			'fit-2560x1440'			=> array( 'fit', 2560, 1440, 80 ),		// 16:9 ratio 1.77	EXPERIMENTAL For Retina displays
			'fit-1920x1080'			=> array( 'fit', 1920, 1080, 80 ),		// 16:9 ratio 1.77	EXPERIMENTAL For Retina displays
			'fit-1600x900'			=> array( 'fit', 1600, 900, 80 ),		// 16:9 ratio 1.77	EXPERIMENTAL For Retina displays
			'fit-1280x720'			=> array( 'fit', 1280, 720, 85 ),		// 16:9 ratio 1.77
			'fit-960x540'			=> array( 'fit', 960, 540, 85 ),			// 16:9 ratio 1.77	EXPERIMENTAL
			'fit-720x500'			=> array( 'fit', 720, 500, 90 ),			// ratio 1.44
			'fit-640x480'			=> array( 'fit', 640, 480, 90 ),			// 4:3 ratio 1.33
			'fit-520x390'			=> array( 'fit', 520, 390, 90 ),			// 4:3 ratio 1.33
			'fit-400x320'			=> array( 'fit', 400, 320, 85 ),			// 5:4 ratio 1.25
			'fit-320x320'			=> array( 'fit', 320, 320, 85 ),			// 1:1 square ratio 1
			'fit-256x256'			=> array( 'fit', 256, 256, 85 ),			// 1:1 square ratio 1
			'fit-192x192'			=> array( 'fit', 192, 192, 85 ),			// 1:1 square ratio 1
			'fit-160x160'			=> array( 'fit', 160, 160, 85 ),			// 1:1 square ratio 1
			'fit-160x120'			=> array( 'fit', 160, 120, 85 ),			// 1:1 square ratio 1
			'fit-128x128'			=> array( 'fit', 128, 128, 85 ),			// 1:1 square ratio 1
			'fit-128x16'			=> array( 'fit', 128, 16, 85 ),				// 8:1 square ratio 8
			'fit-80x80'				=> array( 'fit', 80, 80, 85 ),				// 1:1 square ratio 1
	// FIT+BLUR: Blurred images (probably no need for Retina support, because the intended effect is to be blurred)
			'fit-160x160-blur-13'		=> array( 'fit', 160, 160, 80, 13 ),
			'fit-160x160-blur-18'		=> array( 'fit', 160, 160, 80, 18 ),
	// CROPPED: Images that will be shrunk AND cropped to completely FILL the request aspect ratio
		// 3:2 ratio 1.5
			'crop-480x320'			=> array( 'crop', 480, 320, 90 ),
		// 1:1 square ratio 1
			'crop-512x512'			=> array( 'crop', 512, 512, 85 ),		// EXPERIMENTAL For Retina
			'crop-320x320'			=> array( 'crop', 320, 320, 85 ),
			'crop-256x256'			=> array( 'crop', 256, 256, 85 ),
			'crop-192x192'			=> array( 'crop', 192, 192, 85 ),
			'crop-128x128'			=> array( 'crop', 128, 128, 85 ),
			'crop-80x80'			=> array( 'crop', 80, 80, 85 ),
			'crop-64x64'			=> array( 'crop', 64, 64, 85 ),
			'crop-48x48'			=> array( 'crop', 48, 48, 85 ),
			'crop-32x32'			=> array( 'crop', 32, 32, 85 ),
			'crop-15x15'			=> array( 'crop', 15, 15, 85 ),
	// CROPPED near TOP: Images that will be shrunk with preference towards the top AND cropped to completely FILL the request aspect ratio (typically used for profile pictures)
			'crop-top-640x640'		=> array( 'crop-top', 640, 640, 85 ),		// EXPERIMENTAL For Retina
			'crop-top-320x320'		=> array( 'crop-top', 320, 320, 85 ),
			'crop-top-200x200'		=> array( 'crop-top', 200, 200, 85 ),
			'crop-top-160x160'		=> array( 'crop-top', 160, 160, 85 ),
			'crop-top-80x80'		=> array( 'crop-top', 80, 80, 85 ),
			'crop-top-64x64'		=> array( 'crop-top', 64, 64, 85 ),
			'crop-top-48x48'		=> array( 'crop-top', 48, 48, 85 ),
			'crop-top-32x32'		=> array( 'crop-top', 32, 32, 85 ),
			'crop-top-15x15'		=> array( 'crop-top', 15, 15, 85 ),
	// CROPPED near TOP + BLUR  (typically used to obfuscate profile pictures) (probably no need for Retina support, because the intended effect is to be blurred)
			'crop-top-320x320-blur-8' => array( 'crop-top', 320, 320, 80, 8 ),
	);


/**
 * Generate additional attribute "srcset" for images
 */
$generate_srcset_sizes = true;


/**
 * Demo mode
 *  - Do not allow update of files in the file manager
 *  - Do not allow changes to the 'demouser' and 'admin' account/group
 *  - Blog media directories can only be configured to be inside of {@link $media_path}
 *
 * @global boolean Default: false
 */
$demo_mode = false;

/**
 * If enabled, this will create more demo contents and enable more features during install.
 * This may result in an overloaded/bloated blog.
 *
 * @global boolean
 */
$allow_install_test_features = false;


/**
 * URL of the Home link at the top left.
 *
 * By default this is the base url. And unless you do a complex installation, there is no need to change this.
 */
$home_url = $baseurl;


/**
 * By default images get copied into b2evo cache without resampling if they are smaller
 * than requested thumbnails.
 *
 * If you want to use the BeforeThumbCreate event (Watermark plugin), this should be set to 'true'
 * to make sure that smaller images are also processed.
 *
 * @global boolean Default: false
 */
$resample_all_images = false;


// Decompose the baseurl
// YOU SHOULD NOT EDIT THIS unless you know what you're doing
if( preg_match( '#^((https?)://(www\.)?(.+?)(:.+?)?)(/.*)$#', $baseurl, $matches ) )
{
	$baseurlroot = $matches[1]; // no ending slash!
	// echo "baseurlroot=$baseurlroot <br />";

	$baseprotocol = $matches[2];

	$basehost = $matches[4]; // Will NEVER include "www." at the beginning.
	// echo "basehost=$basehost <br />";

	$baseport =  $matches[5]; // Will start with ":" if a port is specified.
	// echo "baseport=$baseport <br />";

	$basesubpath =  $matches[6];
	// echo "basesubpath=$basesubpath <br />";
}
else
{
	die( 'Your baseurl ('.$baseurl.') set in _basic_config.php seems invalid. You probably missed the "http://" prefix or the trailing slash. Please correct that.' );
}


/**
 * Short name of this system (will be used for cookies and notification emails).
 *
 * Change this only if you install mutliple b2evolution instances on the same server or same domain.
 *
 * WARNING: don't play with this or you'll have tons of cookies sent away and your users will have issues!
 *
 * @global string Default: 'b2evo'
 */
$instance_name = 'b2evo'; // MUST BE A SINGLE WORD! NO SPACES!!


// ** DB options **

/**
 * Show MySQL errors? (default: true)
 *
 * This is recommended on production environments.
 */
$db_config['show_errors'] = true;


/**
 * Halt on MySQL errors? (default: true)
 *
 * Setting this to false is NOT recommended,
 */
$db_config['halt_on_error'] = true;


/**
 * CREATE TABLE options.
 *
 * DO NOT USE unless you know what you're doing -- For most options, we want to work on a table by table basis.
 */
$db_config['table_options'] = ''; 	// Low ranking MySQL hosting compatibility Default


/**
 * Use transactions in DB?
 *
 * b2evolution REQUIRES transactions to function properly. This also means InnoDB is required.
 */
$db_config['use_transactions'] = true;


/**
 * When debugging obhandler functions, we may need to stop polluting the output with debug info.
 *
 * Set this to true to prevent displaying minor changing elements (like time) in order not to have artificial content changes
 *
 * @global boolean Default: false
 */
$obhandler_debug = false;


// ** Cookies **

/**
 * This is the path that will be associated with cookies.
 *
 * That means cookies set by this b2evo install won't be seen outside of this path on the domain below.
 *
 * This applies only to the backoffice. For the frontoffice, the URL will be dynamically generated by function get_cookie_path()
 *
 * @global string Default: preg_replace( '#https?://[^/]+#', '', $baseurl )
 */
$cookie_path = preg_replace( '#https?://[^/]+#', '', $baseurl );

/**
 * Cookie domain.
 *
 * That means cookies set by this b2evo install won't be seen outside of this domain.
 *
 * This applies only to the backoffice. For the frontoffice, the URL will be dynamically generated by function get_cookie_domain()
 *
 * We'll take {@link $basehost} by default (the leading dot includes subdomains), but if there is no dot in it, at least (old?) Firefox will not set the cookie.
 * The most common example for having no dot in the host name is 'localhost', but it's the case for host names in an intranet also.
 *
 * Note: ".domain.com" cookies will be sent to sub.domain.com too.
 * But, see http://www.faqs.org/rfcs/rfc2965:
 *	"If multiple cookies satisfy the criteria above, they are ordered in the Cookie header such that those with more specific Path attributes
 *	precede those with less specific. Ordering with respect to other attributes (e.g., Domain) is unspecified."
 *
 * @global string
 */
if( strpos($basehost, '.') === false )
{	// "localhost" or windows machine name:
	$cookie_domain = '';
}
else
{
	$cookie_domain = $basehost;
}

/* The following is no longer needed because we already strip away "www." from $basehost, so now in all cases the cookie domain should just be the base domain

elseif( preg_match( '~^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$~i', $basehost ) )
{	// The basehost is an IP address, use the basehost as it is:
	$cookie_domain = $basehost;
}
else
{
	// Keep the part of the basehost after the www. :
	//	$cookie_domain = preg_replace( '/^(www\. )? (.+)$/xi', '.$2', $basehost );
}
*/

/**
 * Name used for session cookies.
 */
$cookie_session = 'session_'.$instance_name;

/**
 * Names used for other cookies.
 *
 * The following remember the comment meta data for non registered users:
 */
$cookie_name    = 'cookie'.$instance_name.'name';
$cookie_email   = 'cookie'.$instance_name.'email';
$cookie_url     = 'cookie'.$instance_name.'url';

/**
 * Expiration for comment meta data cookies.
 *
 * Note: user sessions use different settings (config in admin)
 *
 * Value in seconds, set this to 0 if you wish to use non permanent cookies (erased when browser is closed).
 * Default: time() + 31536000 (one year from now)
 *
 * @global int $cookie_expires
 */
$cookie_expires = time() + 31536000;

/**
 * Expired-time used to erase comment meta data cookies.
 *
 * Note: user sessions use different settings (config in admin)
 *
 * Default: time() - 86400 (24 hours ago)
 *
 * @global int $cookie_expired
 */
$cookie_expired = time() - 86400;

/**
 * Crumb expiration time
 *
 * Default: 2 hours
 *
 * @global int $crumb_expires
 */
$crumb_expires = 7200;


/**
 * Page cache expiration time
 * How old can a cached object get before we consider it outdated
 *
 * Default: 15 minutes
 *
 * @global int $pagecache_max_age
 */
$pagecache_max_age = 900;


/**
 * Dummy field names to obfuscate spamboots
 *
 * We use funky field names to defeat the most basic spambots in the front office public forms
 */
$dummy_fields = array(
	'login' => 'x',
	'pwd' => 'q',
	'pass1' => 'm',
	'pass2' => 'c',
	'email' => 'u',
	'name' => 'i',
	'url' => 'h',
	'subject' => 'd',
	'content' => 'g'
);


// ** Location of the b2evolution subdirectories **

/*
	- You should only move these around if you really need to.
	- You should keep everything as subdirectories of the base folder
		($baseurl which is set in _basic_config.php, default is the /blogs/ folder)
	- Remember you can set the baseurl to your website root (-> _basic_config.php).

	NOTE: All paths must have a trailing slash!

	Example of a possible setting:
		$conf_subdir = 'settings/b2evo/';   // Subdirectory relative to base
		$conf_subdir = '../../';            // Relative path to go back to base
*/
/**
 * Location of the configuration files.
 *
 * Note: This folder NEEDS to by accessible by PHP only.
 *
 * @global string $conf_subdir
 */
$conf_subdir = 'conf/';                  // Subdirectory relative to base
$conf_path = str_replace( '\\', '/', dirname(__FILE__) ).'/';

/**
 * @global string Path of the base.
 *                fp> made [i]nsensitive to case because of Windows URL oddities)
 */
$basepath = preg_replace( '#/'.preg_quote( $conf_subdir, '#' ).'$#i', '', $conf_path ).'/';
// echo '<br/>basepath='.$basepath;

/**
 * Location of the include folder.
 *
 * Note: This folder NEEDS to by accessible by PHP only.
 *
 * @global string $inc_subdir
 */
$inc_subdir = 'inc/';   		             	// Subdirectory relative to base
$inc_path = $basepath.$inc_subdir; 		   	// You should not need to change this
$misc_inc_path = $inc_path.'_misc/';	   	// You should not need to change this

/**
 * Location of the HTml SeRVices folder.
 *
 * Note: This folder NEEDS to by accessible through HTTP.
 *
 * @global string $htsrv_subdir
 * @global string $htsrv_path
 * @global string $htsrv_url This applies only to the backoffice. For the frontoffice, the URL will be dynamically generated by function get_htsrv_url( false )
 */
$htsrv_subdir = 'htsrv/';                // Subdirectory relative to base
$htsrv_path = $basepath.$htsrv_subdir;   // You should not need to change this
$htsrv_url = $baseurl.$htsrv_subdir;     // You should not need to change this

/**
 * Location of the XML SeRVices folder.
 * @global string $xmlsrv_subdir
 */
$xmlsrv_subdir = 'xmlsrv/';              // Subdirectory relative to base
$xmlsrv_url = $baseurl.$xmlsrv_subdir;   // You should not need to change this

/**
 * URL of the REST API.
 *
 * @global string $restapi_script
 * @global string $restapi_url This applies only to the backoffice. For the frontoffice, the URL will be dynamically generated by function get_restapi_url()
 */
$restapi_script = 'rest.php?api_version=1&api_request='; // You should not need to change this
$restapi_url = $htsrv_url.$restapi_script; // You should not need to change this

/**
 * Location of the RSC folder.
 *
 * Note: This folder NEEDS to by accessible through HTTP. It MAY be replicated on a CDN.
 *
 * @global string $rsc_subdir
 * @global string $rsc_path
 * @global string $rsc_url This applies only to the backoffice. For the frontoffice, the URL will be dynamically generated by function Blog->get_local_rsc_url()
 * @global string $rsc_uri
 */
$rsc_subdir = 'rsc/';                    // Subdirectory relative to base
$rsc_path = $basepath.$rsc_subdir;       // You should not need to change this
$rsc_url = $assets_baseurl.$rsc_subdir;  // You should not need to change this
$rsc_uri = $basesubpath.$rsc_subdir;     // You should not need to change this

/**
 * Location of the skins folder.
 *
 * Note: This folder NEEDS to by accessible through HTTP. It MAY be replicated on a CDN.
 *
 * @global string $skins_subdir
 * @global string $skins_path
 * @global string $skins_url This applies only to the backoffice. For the frontoffice, the URL will be dynamically generated by function Blog->get_local_skins_url()
 */
$skins_subdir = 'skins/';                   // Subdirectory relative to base
$skins_path = $basepath.$skins_subdir;      // You should not need to change this
$skins_url = $assets_baseurl.$skins_subdir; // You should not need to change this

/**
 * Location of the email skins folder.
 *
 * Note: This folder NEEDS to by accessible through HTTP. It MAY be replicated on a CDN.
 *
 * @global string $emailskins_subdir
 */
$emailskins_subdir = 'skins_email/';               // Subdirectory relative to base
$emailskins_path = $basepath.$emailskins_subdir;   // You should not need to change this
$emailskins_url = $assets_baseurl.$emailskins_subdir;     // You should not need to change this

/**
 * Location of the customizer mode interface
 */
$customizer_relative_url = $basesubpath.'customize.php';

/**
 * Location of the admin interface dispatcher
 */
$dispatcher = 'evoadm.php';
$admin_url = $baseurl.$dispatcher;

/**
 * Location of the admin skins folder.
 *
 * Note: This folder NEEDS to by accessible by both PHP AND through HTTP. It MAY be replicated on a CDN.
 *
 * @global string $adminskins_subdir
 */
$adminskins_subdir = 'skins_adm/';         // Subdirectory relative to ADMIN
$adminskins_path = $basepath.$adminskins_subdir; // You should not need to change this
$adminskins_url = $assets_baseurl.$adminskins_subdir;   // You should not need to change this

/**
 * Location of the locales folder.
 *
 * Note: This folder NEEDS to by accessible by PHP AND MAY NEED to be accessible through HTTP.
 * Exact requirements depend on future uses like localized icons.
 *
 * @global string $locales_subdir
 */
$locales_subdir = 'locales/';            // Subdirectory relative to base
$locales_path = $basepath.$locales_subdir;  // You should not need to change this

/**
 * Location of the plugins.
 *
 * Note: This folder NEEDS to by accessible by PHP AND MAY NEED to be accessible through HTTP.
 * Exact requirements depend on installed plugins.
 *
 * @global string $plugins_subdir
 * @global string $plugins_path
 * @global string $plugins_url This applies only to the backoffice. For the frontoffice, the URL will be dynamically generated by function Blog->get_local_plugins_url()
 */
$plugins_subdir = 'plugins/';            // Subdirectory relative to base
$plugins_path = $basepath.$plugins_subdir;  // You should not need to change this
$plugins_url = $baseurl.$plugins_subdir;    // You should not need to change this

/**
 * Location of the cron folder.
 *
 * Note: Depebding on how you will set up cron execution, this folder may or may not NEED to be accessible by PHP through HTTP.
 *
 * @global string $cron_subdir
 */
$cron_subdir = 'cron/';            // Subdirectory relative to base
$cron_url = $baseurl.$cron_subdir; // You should not need to change this

/**
 * Location of the install folder.
 * @global string $install_subdir
 */
$install_subdir = 'install/';            	 // Subdirectory relative to base
$install_path = $basepath.$install_subdir; // You should not need to change this

/**
 * Location of the rendered page cache folder.
 *
 * Note: This folder does NOT NEED to be accessible through HTTP.
 * This folder MUST be writable by PHP.
 *
 * @global string $cache_subdir
 */
$cache_subdir = '_cache/';              // Subdirectory relative to base
$cache_path = $basepath.$cache_subdir; // You should not need to change this


/**
 * Location of the root media folder.
 *
 * Note: This folder MAY or MAY NOT NEED to be accessible by PHP AND/OR through HTTP.
 * Exact requirements depend on $public_access_to_media .
 *
 * @global string $media_subdir
 * @global string $media_path
 * @global string $media_url This applies only to the backoffice. For the frontoffice, the URL will be dynamically generated by function Blog->get_local_media_url()
 */
$media_subdir = 'media/';                   // Subdirectory relative to base
$media_path = $basepath.$media_subdir;      // You should not need to change this
$media_url = $assets_baseurl.$media_subdir; // You should not need to change this


/**
 * Location of the backup folder.
 *
 * Note: This folder does NOT NEED to be accessible through HTTP.
 * This folder MUST be writable by PHP.
 *
 * @global string $backup_subdir
 */
$backup_subdir = '_backup/';             // Subdirectory relative to base
$backup_path = $basepath.$backup_subdir; // You should not need to change this


/**
 * Location of the upgrade folder.
 *
 * Note: This folder does NOT NEED to be accessible through HTTP.
 * This folder MUST be writable by PHP.
 *
 * @global string $upgrade_subdir
 */
$upgrade_subdir = '_upgrade/';              // Subdirectory relative to base
$upgrade_path = $basepath.$upgrade_subdir;  // You should not need to change this


/**
 * Change to true if you want to be able to install arbitrary ZIP files on the server.
 * ATTENTION: this poses a security risk if the admin password is compromised.
 *
 * @global boolean $auto_upgrade_from_any_url
 */
$auto_upgrade_from_any_url = false;


/**
 * Location of the logs folder.
 *
 * Note: This folder does SHOULD NOT be accessible through HTTP.
 * This folder MUST be writable by PHP.
 *
 * @global string $upgrade_subdir
 */
$logs_subdir = '_logs/';              // Subdirectory relative to base
$logs_path = $basepath.$logs_subdir;  // You should not need to change this


/**
 * Location of the external library folder.
 *
 * Note: This folder does NOT NEED to be accessible through HTTP.
 * This folder MUST be writable by PHP.
 *
 * @global string $ext_subdir
 */
$ext_subdir = 'ext/';               // Subdirectory relative to base
$ext_path = $basepath.$ext_subdir;  // You should not need to change this


/**
 * Allow to use scripts from /cli folder
 *
 * Note: most scripts are available only in b2evolution PRO
 *
 * @global boolean
 */
$allow_cli_folder = false;


/**
 * Do you want to allow public access to the media dir?
 *
 * WARNING: If you set this to false, evocore will use /htsrv/getfile.php as a stub
 * to access files and getfile.php will check the User permisssion to view files.
 * HOWEVER this will not prevent users from hitting directly into the media folder
 * with their web browser. You still need to restrict access to the media folder
 * from your webserver.
 *
 * @global boolean
 */
$public_access_to_media = true;


/**
 * File extensions that can never be made "NOT sensitive"
 * Admins will NOT be able to enable these for non-admin users in the FileType Settings.
 */
$force_upload_forbiddenext = array( 'cgi', 'exe', 'htaccess', 'htpasswd', 'php', 'php3', 'php4', 'php5', 'php6', 'phtml', 'pl', 'vbs' );

/**
 * Should Admins be able to upload/rename/edit sensitive files?
 */
$admins_can_manipulate_sensitive_files = false;

/**
 * The admin can configure the regexp for valid file names in the Settings interface
 * However if the following values are set to non empty, the admin will not be able to customize these values.
 */
$force_regexp_filename = '';
$force_regexp_dirname = '';

/**
 * The maximum length of a file name. On new uploads file names with more characters are not allowed.
 */
$filename_max_length = 64;

/**
 * The maximum length of a file absolute path. Creating folders/files with longer path then this value is not allowed.
 * Note: 247 is the max length of an absolute path what the php file operation functions can handle on windows.
 * On unix systems the file path length is not an issue, so there we can allow a higher value.
 * The OS independent max length is 767, because that is what b2evolution can handle correctly.
 */
$dirpath_max_length = ( ( ( strtoupper( substr( PHP_OS, 0, 3 ) ) ) === 'WIN' ) ? ( 247 - 35 /* the maximum additional path length because of the _evocache folder */ ) : 767 ) - $filename_max_length;


/**
 * Allow double dots in file names
 * Use TRUE if you want to allow ".." in file and directory names like "..filename" or "dir..name"
 */
$filemanager_allow_dotdot_in_filenames = false;


/**
 * XMLRPC logging. Set this to 1 to log XMLRPC calls received by this server (into /xmlsrv/xmlrpc.log).
 *
 * Default: 0
 *
 * @global int $debug_xmlrpc_logging
 */
$debug_xmlrpc_logging = 0;


/**
 * Password change request delay in seconds. Only one email can be requested for one login or email address in each x seconds defined below.
 */
$pwdchange_request_delay = 300; // 5 minutes


/**
 * Enabled password drivers.
 * List what drivers must be enabled on your server.
 * By default only first driver(which is support by server configuration) will be used to store new updated passwords in DB.
 *
 *   possible driver valuse:
 *     - evo_salted
 *     - bcrypt_2y
 *     - bcrypt
 *     - salted_md5
 *     - phpass
 *     - evo_md5 // Use this driver as last choice only.
 */
$enabled_password_drivers = array(
		'evo_salted',
		'bcrypt_2y',
		'bcrypt',
		'salted_md5',
		'phpass',
		'evo_md5', // Use this driver as last choice only.
	);


/**
 * Enable a workaround to allow accessing posts with URL titles ending with
 * a dash (workaround for old bug).
 *
 * In b2evolution v2.4.5 new tag URLs were introduced: You could choose
 * to have tag URLs ending with a dash. This lead to problems with post
 * URL titles accidentially ending with a dash (today, URL titles cannot
 * end with a dash anymore): Instead of displaying the post, the post
 * title was handled as a tag name. When this setting is enabled, all tag
 * names which are exactly 40 chars long and end with a dash are handled
 * in the following way:
 * Try to find a post with the given tag name as the URL title. If there
 * is a matching post, display it; otherwise, display the normal tag page.
 *
 * Note: If you use a 39 chars-long tag name, have an URL title which is
 * the same as the tag *but* additionally has a dash at the end and you
 * use the dash as a tag URL "marker", you won't be able to access either
 * the post or the tag page, depending on the value of this setting.
 *
 * @global boolean $tags_dash_fix
 *
 * @internal Tblue> We perhaps should notify the user if we detect bogus
 *                  post URLs (check on upgrade?) and recommend enabling
 *                  this setting.
 */
$tags_dash_fix = 0;


/**
 * Use hacks file (DEPRECATED) -- see /inc/_main.inc.php
 */
$use_hacks = false;


/**
 * If user tries to log in {$failed_logins_before_lockout} times
 * during the last {$failed_logins_lockout} seconds,
 * we refuse login (even if password is correct) and display that
 * the account is locked out until the above condition is no longer true.
 * If {$failed_logins_lockout} is set to 0, there will never be a lockout.
 */
$failed_logins_before_lockout = 10; // 10 times, Max is 197
$failed_logins_lockout = 600; // 10 minutes


/**
 * Deny registering new accounts with these reserved logins;
 * Also deny changing user logins to one of these;
 * Only admins with permission to create new users can use these:
 */
$reserved_logins = array( 'admin', 'admins', 'administrator', 'administrators', 'moderator', 'moderators', 'webmaster', 'postmaster', 'mailer', 'mail', 'support', 'owner', 'sysop', 'root', 'system', 'web', 'site', 'website', 'server' );


/**
 * Most of the time, the best security practice is to NOT allow redirects from your current site to another domain.
 * That is, unless you specifically configured a redirected post.
 * If this doesn't work for you, you can change this security policy here.
 *
 * Possible values:
 *  - 'always' : Always allow redirects to a different domain
 *  - 'all_collections_and_redirected_posts' ( Default ): Allow redirects to all collection domains, ALL SUB-DOMAINS of $basehost or redirects of posts with redirected status
 *  - 'only_redirected_posts' : Allow redirects to a different domain only in case of posts with redirected status
 *  - 'never' : Force redirects to the same domain in all of the cases, and never allow redirect to a different domain
 */
$allow_redirects_to_different_domain = 'all_collections_and_redirected_posts';


/**
 * Allow parameters in canonical URLs
 * These params will NOT trigger a "301 redirect to canonical" even if the checkboxes for such redirects are enabled
 * This applies to ANY canonical URLs (Items but ALSO: Collection, Category, disp=posts, Archive, Tag, User profile) canonical URLs
 *
 * NOTE: For Item URL we automatically include enabled switchable params of the Item (see "Switchable content" on https://b2evolution.net/man/post-advanced-properties-panel)
 */
$accepted_in_canonicals__params = array(
	'get_redirected_debuginfo_from_sess_ID', // For display debug info of redirected page from different domain
);
// For pages depending on $disp:
$accepted_in_canonicals_disp__params = array(
	'single' => array(
		'page',          // For showing a different page in a multipage post
		'quote_post',    // For quoting a post in the forums
		'quote_comment', // For quoting a comment in the forums
	),
	'page' => array(
		'page',          // For showing a different page in a multipage post
		'quote_post',    // For quoting a post in the forums
		'quote_comment', // For quoting a comment in the forums
	),
	'posts' => array(
		'paged',         // For switching between pages of posts
	),
	'flagged' => array(
		'paged',
	),
	'mustread' => array(
		'paged',
	),
	'users' => array(
		'filter_query',
		'results_u_order',
		'u_paged',
	),
);


/**
 * Pass through the following params in ANY redirect.
 * If these params exist, we include them in ANY redirect we make.
 * We also do NOT overwrite them (e-g: in case of tiny slugs)
 */
$passthru_in_all_redirs__params = array(
	'utm_source',
	'utm_campaign',
	'utm_medium',
);


/**
 * Turn this on to simulate email sends instead of really sending them through SMTP.
 * This is useful if you are debugging a production database on a development machine.
 * It will prevent from sending test notifications to real user accounts.
 * You will still be able to see the emails that would have been sent through the Emails > Sent tab in the back-office.
 */
$email_send_simulate_only = false;


/**
 * Turn this off to prevent sending emails if no external SMTP gateway is configured.
 * If true and no SMTP gateway is configured, b2evolution will behave the same as with $email_send_simulate_only = true;
 * This is useful to avoid sending email (especially campaigns) through a bad IP by mistake.
 */
$email_send_allow_php_mail = true;


/**
 * Would you like to use CDNs as definied in the array $library_cdn_urls below
 * or do you prefer to load all files from the local source as defined in the array $library_local_urls below?
 *
 * @global boolean $use_cdns
 */
$use_cdns = false;		// Use false by default so b2evo works on intranets, local tests on laptops and in countries with firewalls...

/**
 * Which CDN do you want to use for loading common libraries?
 *
 * If you don't want to use a CDN and want to use the local version, comment out the line.
 * Each line starts with the js or css alias.
 * The first string is the production (minified URL), the second is the development URL (optional).
 * By default, only the most trusted CDNs are enabled while the other ones are commented out.
 */
$library_cdn_urls = array(
		'#jquery#' => array( '//code.jquery.com/jquery-1.11.1.min.js', '//code.jquery.com/jquery-1.11.1.js' ),
		// jqueryUI is commented out because b2evo uses only a subset... ?
		//'#jqueryUI#' => array( '//code.jquery.com/ui/1.10.4/jquery-ui.min.js', '//code.jquery.com/ui/1.10.4/jquery-ui.js' ),
		//'#jqueryUI_css#' => array( '//code.jquery.com/ui/1.10.4/themes/smoothness/jquery-ui.min.css', '//code.jquery.com/ui/1.10.4/themes/smoothness/jquery-ui.css' ),
		'#bootstrap#' => array( '//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js', '//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.js' ),
		'#bootstrap_css#' => array( '//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css', '//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.css' ),
		'#bootstrap_theme_css#' => array( '//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css', '//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.css' ),
		// The following are other possible public shared CDNs we are aware of
		// but it is not clear whether or not they are:
		// - Future proof (will they continue to serve old versions of the library in the future?)
		// - Secure (who guarantees the code is genuine?)
		//'#bootstrap_typeahead#' => array( '//cdnjs.cloudflare.com/ajax/libs/typeahead.js/0.10.1/typeahead.bundle.min.js', '//cdnjs.cloudflare.com/ajax/libs/typeahead.js/0.10.1/typeahead.bundle.js' ),
		//'#easypiechart#' => array( '//cdnjs.cloudflare.com/ajax/libs/easy-pie-chart/2.1.1/jquery.easypiechart.min.js', '//cdnjs.cloudflare.com/ajax/libs/easy-pie-chart/2.1.1/jquery.easypiechart.js' ),
		//'#scrollto#' => array( '//cdnjs.cloudflare.com/ajax/libs/jquery-scrollTo/1.4.2/jquery.scrollTo.min.js' ),
		//'#touchswipe#' => array( '//cdn.jsdelivr.net/jquery.touchswipe/1.6.5/jquery.touchSwipe.min.js', '//cdn.jsdelivr.net/jquery.touchswipe/1.6.5/jquery.touchSwipe.js' ),
		/*'#jqplot#' => array( '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.9/jquery.jqplot.min.js', '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.9/jquery.jqplot.js' ),
			'#jqplot_barRenderer#' => array( '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.9/plugins/jqplot.barRenderer.min.js', '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.9/plugins/jqplot.barRenderer.js' ),
			'#jqplot_canvasAxisTickRenderer#' => array( '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.9/plugins/jqplot.canvasAxisTickRenderer.min.js', '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.9/plugins/jqplot.canvasAxisTickRenderer.js' ),
			'#jqplot_canvasTextRenderer#' => array( '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.9/plugins/jqplot.canvasTextRenderer.min.js', '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.9/plugins/jqplot.canvasTextRenderer.js' ),
			'#jqplot_categoryAxisRenderer#' => array( '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.9/plugins/jqplot.categoryAxisRenderer.min.js', '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.9/plugins/jqplot.categoryAxisRenderer.js' ),
			'#jqplot_enhancedLegendRenderer#' => array( '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.9/plugins/jqplot.enhancedLegendRenderer.min.js', '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.9/plugins/jqplot.enhancedLegendRenderer.js' ),
			'#jqplot_highlighter#' => array( '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.9/plugins/jqplot.highlighter.min.js', '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.9/plugins/jqplot.highlighter.js' ),
			'#jqplot_canvasOverlay#' => array( '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.9/plugins/jqplot.canvasOverlay.min.js', '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.9/plugins/jqplot.canvasOverlay.js' ),
			'#jqplot_donutRenderer#' => array( '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.9/plugins/jqplot.donutRenderer.min.js', '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.9/plugins/jqplot.donutRenderer.js' ),
			'#jqplot_css#' => array( '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.9/jquery.jqplot.min.css', '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.9/jquery.jqplot.css' ),*/
		//'#tinymce#' => array( '//cdn.tinymce.com/4/tinymce.min.js' ),
		//'#tinymce_jquery#' => array( '//cdn.tinymce.com/4/jquery.tinymce.min.js' ),
		//'#flowplayer#' => array( '//releases.flowplayer.org/5.4.4/flowplayer.min.js', '//releases.flowplayer.org/5.4.4/flowplayer.js' ),
		//'#mediaelement#' => array( '//cdnjs.cloudflare.com/ajax/libs/mediaelement/2.13.2/js/mediaelement-and-player.min.js', '//cdnjs.cloudflare.com/ajax/libs/mediaelement/2.13.2/js/mediaelement-and-player.js' ),
		//'#mediaelement_css#' => array( '//cdnjs.cloudflare.com/ajax/libs/mediaelement/2.13.2/css/mediaelementplayer.min.css', '//cdnjs.cloudflare.com/ajax/libs/mediaelement/2.13.2/css/mediaelementplayer.css' ),
		//'#videojs#' => array( 'http://vjs.zencdn.net/4.2.0/video.js' ),
		//'#videojs_css#' => array( 'http://vjs.zencdn.net/4.2.0/video-js.css' ),
		//'#clipboardjs#' => array( '//cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.4/clipboard.min.js', '//cdn.rawgit.com/zenorocha/clipboard.js/v2.0.4/dist/clipboard.min.js' ),
		'#fontawesome#' => array( '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css', '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css' ),
	);

/**
 * The aliases for all local JS and CSS files that are used when CDN url is not defined in $library_cdn_urls
 *
 * Each line starts with the js or css alias.
 * The first string is the production (minified URL), the second is the development URL (optional).
 */
$library_local_urls = array(
		'#jquery#' => array( 'ext:jquery/jquery.min.js', 'ext:jquery/jquery.js' ),
		'#jquery_migrate#' => array( 'ext:jquery/jquery-migrate.min.js', 'ext:jquery/jquery-migrate.js' ),
		'#jqueryUI#' => array( 'ext:jquery/ui/js/jquery.ui.b2evo.min.js', 'ext:jquery/ui/js/jquery.ui.b2evo.js' ),
		'#jqueryUI_css#' => array( 'ext:jquery/ui/css/smoothness/jquery-ui.b2evo.min.css', 'ext:jquery/ui/css/smoothness/jquery-ui.b2evo.css' ),
# Uncomment the following lines if your plugins need more jQueryUI features than the ones loaded by b2evo:
#		'#jqueryUI#' => array( 'ext:jquery/ui/js/jquery.ui.all.min.js', 'ext:jquery/ui/js/jquery.ui.all.js' ),
#		'#jqueryUI_css#' => array( 'ext:jquery/ui/css/smoothness/jquery-ui.min.css', 'ext:jquery/ui/css/smoothness/jquery-ui.css' ),
		'#bootstrap#' => array( 'ext:bootstrap/js/bootstrap.min.js', 'ext:bootstrap/js/bootstrap.js' ),
		'#bootstrap_css#' => array( 'ext:bootstrap/css/bootstrap.min.css', 'ext:bootstrap/css/bootstrap.css' ),
		'#bootstrap_theme_css#' => array( 'ext:bootstrap/css/bootstrap-theme.min.css', 'ext:bootstrap/css/bootstrap-theme.css' ),
		'#bootstrap_typeahead#' => array( 'ext:bootstrap/js/typeahead.bundle.min.js', 'ext:bootstrap/js/typeahead.bundle.js' ),
		'#easypiechart#' => array( 'ext:jquery/easy-pie-chart/js/jquery.easy-pie-chart.min.js', 'ext:jquery/easy-pie-chart/js/jquery.easy-pie-chart.js' ),
		'#scrollto#' => array( 'customized:jquery/scrollto/jquery.scrollto.min.js', 'customized:jquery/scrollto/jquery.scrollto.js' ),
		'#touchswipe#' => array( 'ext:jquery/touchswipe/jquery.touchswipe.min.js', 'ext:jquery/touchswipe/jquery.touchswipe.js' ),
		'#jqplot#' => array( 'ext:jquery/jqplot/js/jquery.jqplot.min.js' ),
		'#jqplot_barRenderer#' => array( 'ext:jquery/jqplot/js/jqplot.barRenderer.min.js' ),
		'#jqplot_canvasAxisTickRenderer#' => array( 'ext:jquery/jqplot/js/jqplot.canvasAxisTickRenderer.min.js' ),
		'#jqplot_canvasTextRenderer#' => array( 'ext:jquery/jqplot/js/jqplot.canvasTextRenderer.min.js' ),
		'#jqplot_categoryAxisRenderer#' => array( 'ext:jquery/jqplot/js/jqplot.categoryAxisRenderer.min.js' ),
		'#jqplot_enhancedLegendRenderer#' => array( 'ext:jquery/jqplot/js/jqplot.enhancedLegendRenderer.min.js' ),
		'#jqplot_highlighter#' => array( 'ext:jquery/jqplot/js/jqplot.highlighter.min.js' ),
		'#jqplot_canvasOverlay#' => array( 'ext:jquery/jqplot/js/jqplot.canvasOverlay.min.js' ),
		'#jqplot_donutRenderer#' => array( 'ext:jquery/jqplot/js/jqplot.donutRenderer.min.js' ),
		'#jqplot_css#' => array( 'ext:jquery/jqplot/css/jquery.jqplot.min.css', 'ext:jquery/jqplot/css/jquery.jqplot.css' ),
		'#tinymce#' => array( 'ext:tiny_mce/tinymce.min.js' ),
		'#tinymce_jquery#' => array( 'ext:tiny_mce/jquery.tinymce.min.js' ),
		'#flowplayer#' => array( 'ext:flowplayer/flowplayer.min.js', 'ext:flowplayer/flowplayer.js' ),
		'#mediaelement#' => array( 'ext:mediaelement/js/mediaelement-and-player.min.js', 'ext:mediaelement/js/mediaelement-and-player.js' ),
		'#mediaelement_css#' => array( 'ext:mediaelement/css/mediaelementplayer.min.css', 'ext:mediaelement/css/mediaelementplayer.css' ),
		'#videojs#' => array( 'ext:videojs/js/video.min.js', 'ext:videojs/js/video.js' ),
		'#videojs_css#' => array( 'ext:videojs/css/video-js.min.css', 'ext:videojs/css/video-js.css' ),
		'#jcrop#' => array( 'ext:jquery/jcrop/js/jquery.jcrop.min.js', 'ext:jquery/jcrop/js/jquery.jcrop.js' ),
		'#jcrop_css#' => array( 'ext:jquery/jcrop/css/jquery.jcrop.min.css', 'ext:jquery/jcrop/css/jquery.jcrop.css' ),
		'#fontawesome#' => array( 'ext:font-awesome/css/font-awesome.min.css', 'ext:font-awesome/css/font-awesome.css' ),
		'#clipboardjs#' => array( 'ext:clipboardjs/clipboard.min.js' ),
		'#hotkeys#' => array( 'ext:hotkeys/hotkeys.min.js' ),
	);

/**
 * JS/CSS files which contain other JS/CSS files in order to don't required them twice when main file is required on current page
 *
 * Key - Alias or relative path of main JS/CSS file, Value - array of bundled files inside the main JS/CSS file
 */
$bundled_files = array(
	'build/bootstrap-evo_frontoffice-superbundle.bmin.js' => array(
		'#jquery#',
		'#jquery_migrate#',
		'#jqueryUI#',
		'#bootstrap#',
	),
	'bootstrap-b2evo_base-superbundle.bundle.css' => array(
		'#fontawesome#',
		'#bootstrap_css#',
		'bootstrap-b2evo_base.bundle.css',
	),
	'bootstrap-b2evo_base-superbundle.bmin.css' => array(
		'#fontawesome#',
		'#bootstrap_css#',
		'bootstrap-b2evo_base.bmin.css',
	),
);

/**
 * Allow to send outbound pings on localhost
 */
$allow_post_pings_on_localhost = false;


/**
 * Proxy configuration for all outgoing connections (like pinging b2evolution.net or twitter, etc...)
 * Leave empty if you don't want to use a proxy.
 */
$outgoing_proxy_hostname = '';
$outgoing_proxy_port = '';
$outgoing_proxy_username = '';
$outgoing_proxy_password = '';


/**
 * Check for old browsers like IE and display info message.
 * Set to false if you don't want this check and never inform users if they use an old browser.
 * Note: new default is false because it's easy to annoy IE users with that and they wan't do anything about their enterprise settings that makes them advertsie an older IE than they really have
 *       On our end though we'll send an 'IE-Edge' header and it will make the IE on the other end behva ethe best it can...
 */
$check_browser_version = false;


/**
 * Maximum skin API version which is supported by current version of b2evolution.
 * Skin API version is defined in the method Skin::get_api_version() of each skin.
 */
$max_skin_api_version = 7;


/**
 * Header "Access-Control-Allow-Origin"
 * Used to enable using of evo helpdesk widget from other sites
 */
$access_control_allow_origin = false; // set to '*' or to specific URL to enable CORS requests


/**
 * Allow to use a "defer" way for loading of JavaScript files
 * 
 * TODO: Implement new value 'front' in order to allow this only on front-office
 */
$use_defer = true;

$use_defer_for_backoffice = false;
$use_defer_for_loggedin_users = true;
$use_defer_for_anonymous_users = true;

$use_defer_for_default_register_form = true;

$use_defer_for_anonymous_disp_register = true;
$use_defer_for_anonymous_disp_register_finish = true;
$use_defer_for_anonymous_disp_users = true;
$use_defer_for_anonymous_disp_anonpost = true;

$use_defer_for_loggedin_disp_single_page = true;
$use_defer_for_loggedin_disp_front = true;
$use_defer_for_loggedin_disp_messages = true;
$use_defer_for_loggedin_disp_threads = true;
$use_defer_for_loggedin_disp_profile = true;
$use_defer_for_loggedin_disp_pwdchange = true;
$use_defer_for_loggedin_disp_edit = true;
$use_defer_for_loggedin_disp_proposechange = true;
$use_defer_for_loggedin_disp_edit_comment = true;
$use_defer_for_loggedin_disp_comments = true;
$use_defer_for_loggedin_disp_visits = true;
$use_defer_for_loggedin_disp_contacts = true;

$disable_tinymce_for_frontoffice_comment_form = false; // Disables TinyMCE plugin in the front-office for comment forms


// ----- CHANGE THE FOLLOWING SETTINGS ONLY IF YOU KNOW WHAT YOU'RE DOING! -----
$evonetsrv_protocol = 'http';
$evonetsrv_host = 'rpc.b2evo.net';
$evonetsrv_port = 80;
$evonetsrv_uri = '/evonetsrv/xmlrpc.php';
$evonetsrv_verifypeer = false;
$evonetsrv_retry_delay = 90;

$antispamsrv_protocol = 'http';
$antispamsrv_host = 'antispam.b2evo.net';
$antispamsrv_port = 80;
$antispamsrv_uri = '/evonetsrv/xmlrpc.php';
// For local testing, use something like:
// $antispamsrv_uri = '/.../xmlsrv/xmlrpc.php';
$antispamsrv_tos_url = 'http://b2evolution.net/about/terms.html';

/**
 * Set TRUE if THIS server should be used as central antispam server
 */
$enable_blacklist_server_API = false;

// This is for plugins to add CS files to the TinyMCE editor window:
$tinymce_content_css = array();
?>
