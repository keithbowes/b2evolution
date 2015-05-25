<?php
/**
 * This is the main install menu
 *
 * ---------------------------------------------------------------------------------------------------------------
 * IF YOU ARE READING THIS IN YOUR WEB BROWSER, IT MEANS THAT YOU DID NOT LOAD THIS FILE THROUGH A PHP WEB SERVER. 
 * TO GET STARTED, GO TO THIS PAGE: http://b2evolution.net/man/getting-started
 * ---------------------------------------------------------------------------------------------------------------
 * 
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package install
 */

// Turn off the output buffering to do the correct work of the function flush()
@ini_set( 'output_buffering', 'off' );

/**
 * include config and default functions:
 */
require_once dirname(__FILE__).'/../conf/_config.php';

// Make the includes believe they are being called in the right place...
define( 'EVO_MAIN_INIT', true );

/**
 * Define that we're in the install process.
 */
define( 'EVO_IS_INSTALLING', true );

/**
 * @global boolean Is this an install page? Use {@link is_install_page()} to query it, because it may change.
 */
$is_install_page = true;

// Force to display errors during install/upgrade, even when not in debug mode
$display_errors_on_production = true;

$script_start_time = time();
$localtimenow = $script_start_time; // used e.g. for post_datemodified (sample posts)
$servertimenow = $script_start_time; // used e.g. for itpr_datemodified, cmpr_datemodified, mspr_datemodified (sample data)

if( ! $config_is_done )
{	// Base config is not done yet, try to guess some values needed for correct display:
	$rsc_url = '../rsc/';
}

require_once $inc_path.'_core/_class'.floor(PHP_VERSION).'.funcs.php';
require_once $inc_path.'_core/_misc.funcs.php';

/**
 * Load locale related functions
 */
require_once $inc_path.'locales/_locale.funcs.php';

load_class( '_core/model/_log.class.php', 'Log');
$Debuglog = new Log();
load_class( '_core/model/_messages.class.php', 'Messages' );
$Messages = new Messages();

/**
 * System log
 */
load_class( 'tools/model/_syslog.class.php', 'Syslog' );

/**
 * Load modules.
 *
 * This initializes table name aliases and is required before trying to connect to the DB.
 */
load_class( '_core/model/_module.class.php', 'Module' );
foreach( $modules as $module )
{
	require_once $inc_path.$module.'/_'.$module.'.init.php';
}

// fp> TODO: we may want to try to get the base init into here somehow
// $require_base_config = false;

require_once $conf_path.'_upgrade.php';
// no longer exists: require_once $inc_path.'_vars.inc.php';
load_class( '/_core/model/db/_db.class.php', 'DB' );
//load_funcs('collections/model/_blog.funcs.php');
//load_funcs('collections/model/_category.funcs.php');
//load_class( 'items/model/_item.class.php', 'Item' );
//load_funcs('items/model/_item.funcs.php');
//load_funcs('users/model/_user.funcs.php');
//load_funcs( '_core/ui/forms/_form.funcs.php' );
load_class( '_core/model/_timer.class.php', 'Timer' );
//load_class( 'plugins/model/_plugins.class.php', 'Plugins' );
load_funcs( '_core/_url.funcs.php' );


require_once dirname(__FILE__).'/_functions_install.php';

$Timer = new Timer('main');

load_funcs('_core/_param.funcs.php');

// Let the modules load/register what they need:
modules_call_method( 'init' );

// Init charset variables based on the $evo_charset value
$current_charset = $evo_charset;
init_charsets( $current_charset );

// Init action param
// echo "utf8_ltrim('abc')=".utf8_ltrim('abc')."<br>\n";
// echo "utf8_substr('abc',0)=".utf8_substr('abc',0)."<br>\n";
param( 'action', 'string', 'default' );
// echo "action=*$action*<br>\n ";

// check if we should try to connect to db if config is not done
switch( $action )
{
	case 'evoupgrade':
	case 'auto_upgrade':
	case 'svn_upgrade':
	case 'newdb':
	case 'cafelogupgrade':
	case 'deletedb':
	case 'menu':
	case 'localeinfo':
	case 'utf8upgrade':
		$try_db_connect = true;
		break;
	case 'start':
	case 'conf':
	case 'default':
		$try_db_connect = false;
		break;
	default:
		// set a valid action
		$action = 'default';
		$try_db_connect = false;
		break;
}

$timestamp = time() - 120; // We start dates 2 minutes ago because their dates increase 1 second at a time and we want everything to be visible when the user watches the blogs right after install :P

// Load all available locale defintions:
locales_load_available_defs();
param( 'locale', 'string' );
$use_locale_from_request = false;
if( preg_match( '/[a-z]{2}-[A-Z]{2}(-.{1,14})?/', $locale ) )
{
	$default_locale = $locale;
	$use_locale_from_request = true;
}
if( ! empty( $default_locale ) && ! empty( $locales ) && isset( $locales[ $default_locale ] ) )
{ // Set correct charset, The main using is for DB connection
	$evo_charset = $locales[ $default_locale ]['charset'];
}

if( $config_is_done || $try_db_connect )
{ // Connect to DB:

	$tmp_evoconf_db = $db_config;

	// We want a friendly message if we can't connect:
	$tmp_evoconf_db['halt_on_error'] = false;
	$tmp_evoconf_db['show_errors'] = false;

	// Make sure we use the proper charset:
	$tmp_evoconf_db['connection_charset'] = $evo_charset;

	// CONNECT TO DB:
	$DB = new DB( $tmp_evoconf_db );
	unset($tmp_evoconf_db);

	if( !$DB->error )
	{ // restart conf
		$DB->halt_on_error = true;  // From now on, halt on errors.
		$DB->show_errors = true;    // From now on, show errors (they're helpful in case of errors!).

		// Check MySQL version
		$mysql_version = $DB->get_version();
		foreach( $required_mysql_version as $key => $value )
		{ // check required MySQL version for the whole application and for each module
			if( version_compare( $mysql_version, $value, '<' ) )
			{
				if( $key == 'application' )
				{
					$error_message = sprintf( T_('The minimum requirement for this version of b2evolution is %s version %s but you are trying to use version %s!'), 'MySQL', $value, $mysql_version );
				}
				else
				{
					$error_message = sprintf( T_('The minimum requirement for %s module is %s version %s but you are trying to use version %s!'), $key, 'MySQL', $value, $mysql_version );
				}
				die( '<h1>'.T_('Insufficient Requirements').'</h1><p><strong>'.$error_message.'</strong></p>' );
			}
		}
	}
}

if( ! $use_locale_from_request )
{ // detect language
	// try to check if db already exists and default locale is set on it
	$default_locale = get_default_locale_from_db();
	if( empty( $default_locale ) )
	{ // db doesn't exists yet
		$default_locale = locale_from_httpaccept();
	}
	// echo 'detected locale: ' . $default_locale. '<br />';
	if( isset( $locales[ $default_locale ] ) && strcasecmp($evo_charset, $locales[ $default_locale ]['charset']) != 0 )
	{ // Redirect to install page with correct defined locale in order to avoid broken chars, e.g. when db locale has utf8 encoding and default locale - latin1
		header_redirect( 'index.php?locale='.$default_locale );
		// Exit here.
	}
}
// Activate default locale:
if( ! locale_activate( $default_locale ) )
{	// Could not activate locale (non-existent?), fallback to en-US:
	$default_locale = 'en-US';
	locale_activate( 'en-US' );
}

init_charsets( $current_charset );

switch( $action )
{
	case 'evoupgrade':
	case 'auto_upgrade':
	case 'svn_upgrade':
		$title = T_('Upgrade from a previous version');
		break;

	case 'newdb':
		$title = T_('New Install');
		break;

	case 'cafelogupgrade':
		$title = T_('Upgrade from Cafelog/b2');
		break;

	case 'deletedb':
		$title = T_('Delete b2evolution tables');
		break;

	case 'utf8upgrade':
		$title = T_('Convert/Normalize your DB to UTF-8/ASCII');
		break;

	case 'start':
		$title = T_('Base configuration');
		break;

	case 'conf':
		$config_is_done = 0;
	case 'menu':
	case 'localeinfo':
	case 'default':
		$title = '';
		break;
}

// Form params
$booststrap_install_form_params = array(
		'formstart'      => '',
		'formend'        => '',
		'fieldstart'     => '<div class="form-group" $ID$>'."\n",
		'fieldend'       => "</div>\n\n",
		'labelclass'     => 'control-label col-sm-4',
		'labelstart'     => '',
		'labelend'       => "\n",
		'labelempty'     => '<label class="control-label col-sm-4"></label>',
		'inputstart'     => '<div class="col-sm-8">',
		'inputend'       => "</div>\n",
		'buttonsstart'   => '<div class="form-group"><div class="control-buttons col-sm-offset-4 col-sm-8">',
		'buttonsend'     => "</div></div>\n\n",
		'note_format'    => ' <span class="help-inline text-muted small">%s</span>',
	);

header('Content-Type: text/html; charset='.$evo_charset);
header('Cache-Control: no-cache'); // no request to this page should get cached!
?>
<!DOCTYPE html>
<html lang="<?php locale_lang() ?>">
	<head>
		<base href="<?php echo get_script_baseurl(); ?>">
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="robots" content="noindex, follow" />
		<title><?php echo format_to_output( T_('b2evo installer').( $title ? ': '.$title : '' ), 'htmlhead' ); ?></title>
		<script type="text/javascript" src="../rsc/js/jquery.min.js"></script>
		<!-- Bootstrap -->
		<script type="text/javascript" src="../rsc/js/bootstrap/bootstrap.min.js"></script>
		<link href="../rsc/css/bootstrap/bootstrap.min.css" rel="stylesheet">
		<link href="../rsc/build/b2evo_helper_screens.css" rel="stylesheet">
	</head>
	<body>
		<div class="container">
			<div class="header">
				<nav>
					<ul class="nav nav-pills pull-right">
						<li role="presentation"><a href="../readme.html"><?php echo T_('Read me'); ?></a></li>
						<li role="presentation" class="active"><a href="index.php"><?php echo T_('Installer'); ?></a></li>
						<li role="presentation"><a href="../index.php"><?php echo T_('Your site'); ?></a></li>
					</ul>
				</nav>
				<h3 class="text-muted"><a href="http://b2evolution.net/"><img src="../rsc/img/b2evolution8.png" alt="b2evolution CCMS"></a></h3>
			</div>

		<!-- InstanceBeginEditable name="Main" -->

<?php

// echo $action;
$date_timezone = ini_get( "date.timezone" );
if( empty( $date_timezone ) && empty( $date_default_timezone ) )
{ // The default timezone is not set, display a warning
	display_install_messages( sprintf( T_("No default time zone is set. Please open PHP.ini and set the value of 'date.timezone' (Example: date.timezone = Europe/Paris) or open /conf/_advanced.php and set the value of %s (Example: %s)"), '$date_default_timezone', '$date_default_timezone = \'Europe/Paris\';' ) );
}

if( ( $config_is_done || $try_db_connect ) && ( $DB->error ) )
{ // DB connect was unsuccessful, restart conf
	display_install_messages( T_('Check your database config settings below and update them if necessary...') );
	display_base_config_recap();
	$action = 'start';
}

// Check other dependencies:
// TODO: Non-install/upgrade-actions should be allowed (e.g. "deletedb")
if( $req_errors = install_validate_requirements() )
{
	echo '<p class="text-danger"><strong>'.T_('b2evolution cannot be installed, because of the following errors:').'</strong></p>';
	display_install_messages( $req_errors );
	die;
}

switch( $action )
{
	case 'conf':
		/*
		 * -----------------------------------------------------------------------------------
		 * Write conf file:
		 * -----------------------------------------------------------------------------------
		 */
		display_locale_selector();

		param( 'conf_db_user', 'string', true );
		param( 'conf_db_password', 'raw', true );
		param( 'conf_db_name', 'string', true );
		param( 'conf_db_host', 'string', true );
		param( 'conf_db_tableprefix', 'string', $tableprefix );
		param( 'conf_baseurl', 'string', true );
		$conf_baseurl = preg_replace( '#(/)?$#', '', $conf_baseurl ).'/'; // force trailing slash
		param( 'conf_admin_email', 'string', true );

		// Connect to DB:
		$DB = new DB( array(
			'user' => $conf_db_user,
			'password' => $conf_db_password,
			'name' => $conf_db_name,
			'host' => $conf_db_host,
			'aliases' => $db_config['aliases'],
			'use_transactions' => $db_config['use_transactions'],
			'table_options' => $db_config['table_options'],
			'connection_charset' => empty( $db_config['connection_charset'] ) ? DB::php_to_mysql_charmap( $evo_charset ) : $db_config['connection_charset'],
			'halt_on_error' => false ) );
		if( $DB->error )
		{ // restart conf
			display_install_messages( T_('It seems that the database config settings you entered don\'t work. Please check them carefully and try again...') );
			$action = 'start';
		}
		else
		{
			$conf_template_filepath = $conf_path.'_basic_config.template.php';
			$conf_filepath = $conf_path.'_basic_config.php';

			// Read original:
			$file_loaded = @file( $conf_template_filepath );

			if( empty( $file_loaded ) )
			{ // This should actually never happen, just in case...
				display_install_messages( sprintf( T_('Could not load original conf file [%s]. Is it missing?'), $conf_filepath ) );
				break;
			}

			// File loaded...
			$conf = implode( '', $file_loaded );
			// Update conf:
			$conf = preg_replace(
				array(
					'#\$db_config\s*=\s*array\(
						\s*[\'"]user[\'"]\s*=>\s*[\'"].*?[\'"],     ([^\n\r]*\r?\n)
						\s*[\'"]password[\'"]\s*=>\s*[\'"].*?[\'"], ([^\n\r]*\r?\n)
						\s*[\'"]name[\'"]\s*=>\s*[\'"].*?[\'"],     ([^\n\r]*\r?\n)
						\s*[\'"]host[\'"]\s*=>\s*[\'"].*?[\'"],     ([^\n\r]*\r?\n)
						#ixs',
					"#tableprefix\s*=\s*'.*?';#",
					"#baseurl\s*=\s*'.*?';#",
					"#admin_email\s*=\s*'.*?';#",
					"#config_is_done\s*=.*?;#",
				),
				array(
					"\$db_config = array(\n"
						."\t'user'     => '".str_replace( array( "'", "\$" ), array( "\'", "\\$" ), $conf_db_user )."',\$1"
						."\t'password' => '".str_replace( array( "'", "\$" ), array( "\'", "\\$" ), $conf_db_password )."',\$2"
						."\t'name'     => '".str_replace( array( "'", "\$" ), array( "\'", "\\$" ), $conf_db_name )."',\$3"
						."\t'host'     => '".str_replace( array( "'", "\$" ), array( "\'", "\\$" ), $conf_db_host )."',\$4",
					"tableprefix = '".str_replace( "'", "\'", $conf_db_tableprefix )."';",
					"baseurl = '".str_replace( "'", "\'", $conf_baseurl )."';",
					"admin_email = '".str_replace( "'", "\'", $conf_admin_email )."';",
					'config_is_done = 1;',
				), $conf );

			// Write new contents:
			if( save_to_file( $conf, $conf_filepath, 'w' ) )
			{
				display_install_messages( sprintf( T_('Your configuration file [%s] has been successfully created.').'</p>', $conf_filepath ), 'success' );

				$tableprefix = $conf_db_tableprefix;
				$baseurl = $conf_baseurl;
				$admin_email = $conf_admin_email;
				$config_is_done = 1;
				$action = 'menu';
			}
			else
			{
				?>
				<h1><?php echo T_('Config file update') ?></h1>
				<p><strong><?php printf( T_('We cannot automatically create or update your config file [%s]!'), $conf_filepath ); ?></strong></p>
				<p><?php echo T_('There are two ways to deal with this:') ?></p>
				<ul>
					<li><strong><?php echo T_('You can allow the installer to create the config file by changing permissions for the /conf directory:') ?></strong>
						<ol>
							<li><?php printf( T_('Make sure there is no existing and potentially locked configuration file named <code>%s</code>. If so, please delete it.'), $conf_filepath ); ?></li>
							<li><?php printf( T_('<code>chmod 777 %s</code>. If needed, see the <a %s>online manual about permissions</a>.'), $conf_path, 'href="'.get_manual_url( 'directory-and-file-permissions' ).'" target="_blank"' ); ?></li>
							<li><?php echo T_('Come back to this page and refresh/reload.') ?></li>
						</ol>
						<br />
					</li>
					<li><strong><?php echo T_('Alternatively, you can update the config file manually:') ?></strong>
						<ol>
							<li><?php echo T_('Create a new text file with a text editor.') ?></li>
							<li><?php echo T_('Copy the contents from the box below.') ?></li>
							<li><?php echo T_('Paste them into your local text editor. <strong>ATTENTION: make sure there is ABSOLUTELY NO WHITESPACE after the final <code>?&gt;</code> in the file.</strong> Any space, tab, newline or blank line at the end of the conf file may prevent cookies from being set when you try to log in later.') ?></li>
							<li><?php echo T_('Save the file locally under the name <code>_basic_config.php</code>') ?></li>
							<li><?php echo T_('Upload the file to your server, into the <code>/_conf</code> folder.') ?></li>
							<li><?php printf( T_('<a %s>Call the installer from scratch</a>.'), 'href="index.php?locale='.$default_locale.'"') ?></li>
						</ol>
					</li>
				</ul>
				<p><?php echo T_('This is how your _basic_config.php should look like:') ?></p>
				<blockquote>
				<pre><?php
					echo htmlspecialchars( $conf );
				?></pre>
				</blockquote>
				<?php
				break;
			}
		}
		// ATTENTION: we continue here...

	case 'start':
	case 'default':
		/*
		 * -----------------------------------------------------------------------------------
		 * Start of install procedure:
		 * -----------------------------------------------------------------------------------
		 */
		if( $action == 'start' || !$config_is_done )
		{
			track_step( 'installer-startdb' );

			display_locale_selector();

			echo '<h1>'.T_('Base configuration').'</h1>';

			if( $config_is_done && $allow_evodb_reset != 1 )
			{
				echo '<p><strong>'.T_('Resetting the base configuration is currently disabled for security reasons.').'</strong></p>';
				echo '<p>'.sprintf( T_('To enable it, please go to the %s file and change: %s to %s'), '/conf/_basic_config.php', '<pre>$allow_evodb_reset = 0;</pre>', '<pre>$allow_evodb_reset = 1;</pre>' ).'</p>';
				echo '<p>'.T_('Then reload this page and a reset option will appear.').'</p>';
				block_close();
				break;
			}
			else
			{

			// Set default params if not provided otherwise:
			param( 'conf_db_user', 'string', $db_config['user'] );
			param( 'conf_db_password', 'raw', $db_config['password'] );
			param( 'conf_db_name', 'string', $db_config['name'] );
			param( 'conf_db_host', 'string', $db_config['host'] );
			param( 'conf_db_tableprefix', 'string', $tableprefix );
			// Guess baseurl:
			// TODO: dh> IMHO HTTP_HOST would be a better default, because it's what the user accesses for install.
			//       fp, please change it, if it's ok. SERVER_NAME might get used if HTTP_HOST is not given, but that shouldn't be the case normally.
			// fp> ok for change and test after first 3.x-stable release
			$baseurl = 'http://'.( isset( $_SERVER['SERVER_NAME'] ) ? $_SERVER['SERVER_NAME'] : 'yourserver.com' );
			if( isset( $_SERVER['SERVER_PORT'] ) && ( $_SERVER['SERVER_PORT'] != '80' ) )
				$baseurl .= ':'.$_SERVER['SERVER_PORT'];

			// ############ Get ReqPath & ReqURI ##############
			list($ReqPath,$ReqURI) = get_ReqURI();

			$baseurl .= preg_replace( '#/install(/(index.php)?)?$#', '', $ReqPath ).'/';

			param( 'conf_baseurl', 'string', $baseurl );
			param( 'conf_admin_email', 'string', $admin_email );

			?>

			<p><?php echo T_('The basic configuration file (<code>/conf/_basic_config.php</code>) has not been created yet. You can do automatically generate it by filling out the form below.') ?></p>

			<p><?php echo T_('This is the minimum info we need to set up b2evolution on this server:') ?></p>

			<?php
			$Form = new Form( 'index.php' );

			$Form->switch_template_parts( $booststrap_install_form_params );

			$Form->begin_form( 'form-horizontal' );

			$Form->hidden( 'action', 'conf' );
			$Form->hidden( 'locale', $default_locale );

			block_open( T_('Database you want to install into') );
			?>
				<p class="text-muted small"><?php echo T_('b2evolution stores blog posts, comments, user permissions, etc. in a MySQL database. You must create this database prior to installing b2evolution and provide the access parameters to this database below. If you are not familiar with this, you can ask your hosting provider to create the database for you.') ?></p>
				<?php
				$Form->text( 'conf_db_host', $conf_db_host, 16, T_('MySQL Host/Server'), sprintf( T_('Typically looks like "localhost" or "sql-6" or "sql-8.yourhost.net"...' ) ), 120 );
				$Form->text( 'conf_db_name', $conf_db_name, 16, T_('MySQL Database'), sprintf( T_('Name of the MySQL database you have created on the server' ) ), 100);
				$Form->text( 'conf_db_user', $conf_db_user, 16, T_('MySQL Username'), sprintf( T_('Used by b2evolution to access the MySQL database' ) ), 100 );
				$Form->text( 'conf_db_password', $conf_db_password, 16, T_('MySQL Password'), sprintf( T_('Used by b2evolution to access the MySQL database' ) ), 100 ); // no need to hyde this. nobody installs b2evolution from a public place
				// Too confusing for (most) newbies.	form_text( 'conf_db_tableprefix', $conf_db_tableprefix, 16, T_('MySQL tables prefix'), sprintf( T_('All DB tables will be prefixed with this. You need to change this only if you want to have multiple b2evo installations in the same DB.' ) ), 30 );
			block_close();

			block_open( T_('Additional settings') );
				$Form->text( 'conf_baseurl', $conf_baseurl, 50, T_('Base URL'), sprintf( T_('This is where b2evo and your blogs reside by default. CHECK THIS CAREFULLY or not much will work. If you want to test b2evolution on your local machine, in order for login cookies to work, you MUST use http://<strong>localhost</strong>/path... Do NOT use your machine\'s name!' ) ), 120 );
				$Form->text( 'conf_admin_email', $conf_admin_email, 50, T_('Your email'), sprintf( T_('This is used to create your admin account. You will receive notifications for comments on your blog, etc.' ) ), 80 );
			block_close();

			$Form->end_form( array( array( 'name' => 'submit', 'value' => T_('Update config file'), 'class' => 'btn-primary btn-lg' ),
					array( 'type' => 'reset', 'value' => T_('Reset'), 'class' => 'btn-default btn-lg' )
				) );

			break;
			}
		}
		// if config was already done, move on to main menu:

	case 'menu':
		/*
		 * -----------------------------------------------------------------------------------
		 * Menu
		 * -----------------------------------------------------------------------------------
		 */
		track_step( 'installer-menu' );

		display_locale_selector();

		?>
		<h1><?php echo T_('How would you like your b2evolution installed?') ?></h1>

		<?php
			$old_db_version = get_db_version();
			$require_charset_update = false;

			if( ! is_null( $old_db_version ) )
			{
				$expected_connection_charset = $DB->php_to_mysql_charmap( $evo_charset );
				if( $DB->connection_charset != $expected_connection_charset )
				{
					display_install_messages( sprintf( T_('In order to install b2evolution with the %s locale, your MySQL needs to support the %s connection charset.').' (mysqli::set_charset(%s))',
						$current_locale, $evo_charset, $expected_connection_charset ) );
					// sam2kb> TODO: If something is not supported we can display a message saying "do this and that, enable extension X etc. etc... or switch to a better hosting".
					break;
				}
				else
				{ // Check if some of the tables have different charset than what we expect
					load_funcs( 'tools/model/_system.funcs.php' );
					if( system_check_charset_update() )
					{
						$require_charset_update = true;
						display_install_messages( sprintf( T_("WARNING: Some of your tables have different charset than the expected %s. It is strongly recommended to upgrade your database charset by running the preselected task below:"), utf8_strtoupper( $evo_charset ) ) );
					}
				}
			}
		?>

		<form action="index.php" method="get">
			<input type="hidden" name="locale" value="<?php echo $default_locale ?>" />
			<input type="hidden" name="confirmed" value="0" />
			<input type="hidden" name="installer_version" value="10" />

			<p><?php echo T_('The installation can be done in different ways. Choose one:')?></p>

			<div class="radio">
				<label>
					<input type="radio" name="action" id="newdb" value="newdb"
					<?php
						// fp> change the above to 'newdbsettings' for an additional settings screen.
						if( is_null($old_db_version) )
						{
							echo 'checked="checked"';
						}
					?>/>
					<?php echo T_('<strong>New Install</strong>: Install the b2evolution database tables. Optionally add some default contents.')?>
				</label>
			</div>
			
			<div style="margin-left:2em">
				<?php
				if( $test_install_all_features && $allow_evodb_reset )
				{ // Option to quick delete before new installation
				?>
				<div class="checkbox">
					<label>
						<input type="checkbox" name="delete_contents" id="delete_contents" value="1" checked="checked" />
						<?php echo T_('Delete pre-existing b2evolution tables &amp; cache files.')?>
					</label>
				</div>
				<?php } ?>
				<div class="checkbox">
					<label>
						<input type="checkbox" name="create_sample_contents" id="create_sample_contents" value="1" checked="checked" />
						<?php echo T_('Also install sample blogs &amp; sample contents. The sample posts explain several features of b2evolution. This is highly recommended for new users.')?>
					</label>
					<br />
					<?php echo T_('You can start adding your own content whenever you\'re ready. Until then, it may be handy to have some demo contents to play around with. You can easily delete these demo contents once you\'re done testing.'); ?>
					<?php
						// Display the collections to select which install
						$collections = array(
								'home'   => T_('Home'),
								'a'      => T_('Blog A'),
								'b'      => T_('Blog B'),
								'photos' => T_('Photos'),
								'forums' => T_('Forums'),
								'manual' => T_('Manual'),
							);

						// Allow all modules to set what collections should be installed
						$module_collections = modules_call_method( 'get_demo_collections' );
						if( ! empty( $module_collections ) )
						{
							foreach( $module_collections as $module_key => $module_colls )
							{
								foreach( $module_colls as $module_coll_key => $module_coll_title )
								{
									$collections[ $module_key.'_'.$module_coll_key ] = $module_coll_title;
								}
							}
						}

						foreach( $collections as $coll_index => $coll_title )
						{ // Display the checkboxes to select what demo collection to install
					?>
					<div class="checkbox" style="margin-left:2em">
						<label>
							<input type="checkbox" name="collections[]" id="collection_<?php echo $coll_index; ?>" value="<?php echo $coll_index; ?>" checked="checked" />
							<?php echo $coll_title; ?>
						</label>
					</div>
					<?php } ?>
				</div>
				<div class="checkbox">
					<?php
						// Pre-check if current installation is local
						$is_local = php_sapi_name() != 'cli' && // NOT php CLI mode
							( $basehost == 'localhost' ||
								( isset( $_SERVER['SERVER_ADDR'] ) && (
									$_SERVER['SERVER_ADDR'] == '127.0.0.1' ||
									$_SERVER['SERVER_ADDR'] == '::1' ) // IPv6 address of 127.0.0.1
								) ||
								( isset( $_SERVER['REMOTE_ADDR'] ) && (
									$_SERVER['REMOTE_ADDR'] == '127.0.0.1' ||
									$_SERVER['REMOTE_ADDR'] == '::1' )
								) ||
								( isset( $_SERVER['HTTP_HOST'] ) && (
									$_SERVER['HTTP_HOST'] == '127.0.0.1' ||
									$_SERVER['HTTP_HOST'] == '::1' )
								) ||
								( isset( $_SERVER['SERVER_NAME'] ) && (
									$_SERVER['SERVER_NAME'] == '127.0.0.1' ||
									$_SERVER['SERVER_NAME'] == '::1' )
								)
							);
					?>
					<label>
						<input type="checkbox" name="local_installation" id="local_installation" value="1"<?php echo $is_local ? ' checked="checked"' : ''; ?> />
						<?php echo T_('This is a local / test / intranet installation.')?>
					</label>
				</div>
				<?php
					if( $test_install_all_features )
					{ // Checkbox to install all features
				?>
				<div class="checkbox">
					<label>
						<input accept=""type="checkbox" name="install_all_features" id="install_all_features" value="1" />
						<?php echo T_('Also install all test features.')?>
					</label>
				</div>
				<?php } ?>
			</div>

			<div class="radio">
				<label>
					<input type="radio" name="action" id="evoupgrade" value="evoupgrade"
					<?php if( !is_null($old_db_version) && ! $require_charset_update && $old_db_version < $new_db_version )
						{
							echo 'checked="checked"';
						}
					?>/>
					<?php echo T_('<strong>Upgrade from a previous version of b2evolution</strong>: Upgrade your b2evolution database tables in order to make them compatible with the current version. <strong>WARNING:</strong> If you have modified your database, this operation may fail. Make sure you have a backup.') ?>
				</label>
			</div>

			<?php
			if( $allow_evodb_reset == 1 )
			{
			?>
			<div class="radio">
				<label>
					<input type="radio" name="action" id="deletedb" value="deletedb" />
					<?php echo T_('<strong>Delete b2evolution tables &amp; cache files. WARNING:</strong> All your b2evolution tables and data will be lost! Any non-b2evolution tables will remain untouched.')?>
				</label>
			</div>

			<div class="radio">
				<label>
					<input type="radio" name="action" id="start" value="start" />
					<?php echo T_('<strong>Change your base configuration</strong> (see recap below): You only want to do this in rare occasions where you may have moved your b2evolution files or database to a different location...')?>
				</label>
			</div>
			<?php
			}
			?>
			<div class="radio">
				<label>
					<input type="radio" name="action" id="utf8upgrade" value="utf8upgrade"<?php echo ( $require_charset_update ) ? ' checked="checked"' : '' ?>/>
					<?php echo T_('<strong>Convert/Normalize your DB to UTF-8/ASCII</strong>: The content tables in your b2evolution MySQL database will be converted to UTF-8 instead of their current charset. Some system tables will also be converted to plain ASCII for better performance.')?>
				</label>
			</div>
			<?php


			if( $allow_evodb_reset != 1 )
			{
				echo '<div class="pull-right"><a href="index.php?action=deletedb&amp;locale='.$default_locale.'">'.T_('Need to start anew?').' &raquo;</a></div>';
			}
			?>

			<p>
				<button id="install_button" type="submit" class="btn btn-primary btn-lg">&#160; <?php echo T_('GO!')?> &#160;</button>
			</p>
			</form>
		<?php

		display_base_config_recap();
		echo_install_button_js();
		break;

	case 'localeinfo':
		// Info about getting additional locales.
		display_locale_selector();

		// Note: Do NOT make these strings translatable. We are not in the desired language anyways!
		?>
		<h2>What if your language is not in the list above?</h2>
		<ol>
			<li>Go to the <a href="http://b2evolution.net/downloads/language-packs.html" target="_blank">language packs section on b2evolution.net</a>.</li>
			<li>Select the version of b2evolution you're trying to install. If it's not available select the closest match (in most cases this should work).</li>
			<li>Find your language and click the "Download" link.</li>
			<li>Unzip the contents of the downloaded ZIP file.</li>
			<li>Upload the new folder (for example es_ES) into the /locales folder on your server. (The /locales folder already contains a few locales such as de_DE, ru_RU, etc.)</li>
			<li>Reload this page. The new locale should now appear in the list at the top of this screen. If it doesn't, it means the language pack you installed is not compatible with this version of b2evolution.</li>
		</ol>

		<h3>What if there is no language pack to download?</h3>
		<p>Nobody has contributed a language pack in your language yet. You could help by providing a translation for your language.</p>
		<p>For now, you will have to install b2evolution with a supported language.</p>
		<p>Once you get familiar with b2evolution you will be able to <a href="<?php echo get_manual_url( 'localization' ); ?>" target="_blank">create your own language pack</a> fairly easily.</p>
		<?php
		// A link to back to install menu
		display_install_back_link();
		break;

	case 'newdbsettings':
		/*
		 * fp> TODO: Add a screen for additionnal settings:
		 * - create_sample_contents : to be moved away from main screen
		 * - admin_email: to be moved out of conf file
		 * - storage_charset: offer option to FORCE storing data in UTF-8 even if current locale doesn't require it (must be supported by MySQL) -- recommended for multilingual blogs
		 * - evo_charset: offer option to FORCE handling data internally in UTF-8 even if current locale doesn't require it (requires mbstring) -- not recommended in most situations
		 */


	case 'newdb':
		/*
		 * -----------------------------------------------------------------------------------
		 * NEW DB: install a new b2evolution database.
		 * -----------------------------------------------------------------------------------
		 * Note: auto installers should kick in directly at this step and provide all required params.
		 */
		track_step( 'install-start' );

		$config_test_install_all_features = $test_install_all_features;
		if( $test_install_all_features )
		{ // Allow to use $test_install_all_features from request only when it is enabled in config
			$test_install_all_features = param( 'install_all_features', 'boolean', false );
		}
		else
		{
			$test_install_all_features = false;
		}

		// fp> TODO: this test should probably be made more generic and applied to upgrade too.
		$expected_connection_charset = DB::php_to_mysql_charmap($evo_charset);
		if( $DB->connection_charset != $expected_connection_charset )
		{
			display_install_messages( sprintf( T_('In order to install b2evolution with the %s locale, your MySQL needs to support the %s connection charset.').' (mysqli::set_charset(%s))',
				$current_locale, $evo_charset, $expected_connection_charset ) );
			// sam2kb> TODO: If something is not supported we can display a message saying "do this and that, enable extension X etc. etc... or switch to a better hosting".
			break;
		}

		// Progress bar
		start_install_progress_bar( T_('Installation in progress'), get_install_steps_count() );

		echo '<h2>'.T_('Installing b2evolution...').'</h2>';

		if( $config_test_install_all_features && $allow_evodb_reset )
		{ // Allow to quick delete before new installation only when these two settings are enabled in config files
			$delete_contents = param( 'delete_contents', 'integer', 0 );

			if( $delete_contents )
			{ // A quick deletion is requested before new installation
				require_once( dirname(__FILE__). '/_functions_delete.php' );

				echo '<h2>'.T_('Deleting b2evolution tables from the datatase...').'</h2>';
				evo_flush();

				// Uninstall b2evolution: Delete DB & Cache files
				uninstall_b2evolution();

				// Update the progress bar status
				update_install_progress_bar();
			}
		}

		if( $old_db_version = get_db_version() )
		{
			echo '<p><strong>'.T_('OOPS! It seems b2evolution is already installed!').'</strong></p>';

			if( $old_db_version < $new_db_version )
			{
				echo '<p>'.sprintf( T_('Would you like to <a %s>upgrade your existing installation now</a>?'), 'href="?action=evoupgrade"' ).'</p>';
			}

			// Stop the animation of the progress bar
			stop_install_progress_bar();

			break;
		}

		echo '<h2>'.T_('Checking files...').'</h2>';
		evo_flush();
		// Check for .htaccess:
		if( ! install_htaccess( false ) )
		{ // Exit installation here because the .htaccess file has the some errors
			break;
		}

		// Update the progress bar status
		update_install_progress_bar();

		// Here's the meat!
		install_newdb();

		// Stop the animation of the progress bar
		stop_install_progress_bar();
		break;


	case 'evoupgrade':
	case 'auto_upgrade':
	case 'svn_upgrade':
		/*
		 * -----------------------------------------------------------------------------------
		 * EVO UPGRADE: Upgrade data from existing b2evolution database
		 * -----------------------------------------------------------------------------------
		 */
		track_step( 'upgrade-start' );

		require_once( dirname(__FILE__). '/_functions_evoupgrade.php' );

		// Progress bar
		start_install_progress_bar( T_('Uprade in progress'), get_upgrade_steps_count() );

		echo '<h2>'.T_('Upgrading b2evolution...').'</h2>';

		echo '<h2>'.T_('Checking files...').'</h2>';
		evo_flush();
		// Check for .htaccess:
		if( ! install_htaccess( true ) )
		{ // Exit installation here because the .htaccess file has the some errors
			break;
		}

		// Update the progress bar status
		update_install_progress_bar();

		// Try to obtain some serious time to do some serious processing (5 minutes)
		// NOte: this must NOT be in upgrade_b2evo_tables(), otherwise it will mess with the longer setting used by the auto upgrade feature.
		if( set_max_execution_time(300) === false )
		{ // max_execution_time ini setting could not be changed for this script, display a warning
			$manual_url = 'href="'.get_manual_url( 'blank-or-partial-page' ).'" target = "_blank"';
			echo '<div class="text-warning">'.sprintf( T_('WARNING: the max_execution_time is set to %s seconds in php.ini and cannot be increased automatically. This may lead to a PHP <a %s>timeout causing the upgrade to fail</a>. If so please post a screenshot to the <a %s>forums</a>.'), ini_get( 'max_execution_time' ), $manual_url, 'href="http://forums.b2evolution.net/"' ).'</div>';
		}

		echo '<h2>'.T_('Upgrading data in existing b2evolution database...').'</h2>';
		evo_flush();

		$not_evoupgrade = ( $action !== 'evoupgrade' );
		if( upgrade_b2evo_tables( $action ) )
		{
			if( $not_evoupgrade )
			{ // After successful auto_upgrade or svn_upgrade we must remove files/folder based on the upgrade_policy.conf
				remove_after_upgrade();
				// disable maintenance mode at the end of the upgrade script
				switch_maintenance_mode( false, 'upgrade' );
			}

			// Update the progress bar status
			update_install_progress_bar();

			?>
			<p class="text-success"><?php echo T_('Upgrade completed successfully!')?></p>
			<p><?php printf( T_('Now you can <a %s>log in</a> with your usual b2evolution username and password.'), 'href="'.$admin_url.'"' )?></p>
			<?php
		}
		else
		{
			if( $not_evoupgrade )
			{ // disable maintenance mode at the end of the upgrade script
				switch_maintenance_mode( false, 'upgrade' );
			}
			?>
			<p class="text-danger"><?php echo T_('Upgrade failed!')?></p>
			<?php
			// A link to back to install menu
			display_install_back_link();
		}

		// Stop the animation of the progress bar
		stop_install_progress_bar();

		break;


	case 'deletedb':
		/*
		 * -----------------------------------------------------------------------------------
		 * DELETE DB: Delete the db structure!!! (Everything will be lost)
		 * -----------------------------------------------------------------------------------
		 */
		require_once( dirname(__FILE__). '/_functions_delete.php' );

		$confirmed = param( 'confirmed', 'integer', 1 );

		if( $confirmed )
		{ // Progress bar
			start_install_progress_bar( T_('Deletion in progress') );
		}

		echo '<h2>'.T_('Deleting b2evolution tables from the datatase...').'</h2>';
		evo_flush();

		if( $allow_evodb_reset != 1 )
		{
			echo T_('If you have installed b2evolution tables before and wish to start anew, you must delete the b2evolution tables before you can start a new installation. b2evolution can delete its own tables for you, but for obvious security reasons, this feature is disabled by default.');
			echo '<p>'.sprintf( T_('To enable it, please go to the %s file and change: %s to %s'), '/conf/_basic_config.php', '<pre>$allow_evodb_reset = 0;</pre>', '<pre>$allow_evodb_reset = 1;</pre>' ).'</p>';
			echo '<p>'.T_('Then reload this page and a reset option will appear.').'</p>';
			// A link to back to install menu
			display_install_back_link();

			break;
		}

		if( ! $confirmed )
		{
			?>
			<p>
			<?php
			echo nl2br( htmlspecialchars( sprintf( /* TRANS: %s gets replaced by app name, usually "b2evolution" */ T_( "Are you sure you want to delete your existing %s tables?\nDo you have a backup?" ), $app_name ) ) );
			?>
			</p>
			<p>
			<form name="form" action="index.php" method="post" style="display:inline-block">
				<input type="hidden" name="action" value="deletedb" />
				<input type="hidden" name="confirmed" value="1" />
				<input type="hidden" name="locale" value="<?php echo $default_locale; ?>" />
				<input type="submit" value="&#160; <?php echo T_('I am sure!')?> &#160;" class="btn btn-danger btn-lg" />
			</form>

			<form name="form" action="index.php" method="get" style="display:inline-block">
				<input type="hidden" name="locale" value="<?php echo $default_locale; ?>" />
				<input type="submit" value="&#160; <?php echo T_('CANCEL')?> &#160;" class="btn btn-default btn-lg" />
			</form>
			</p>
			<?php
			break;
		}

		// Uninstall Plugins
		// TODO: fp>> I don't trust the plugins to uninstall themselves correctly. There will be tons of lousy poorly written plugins. All I trust them to do is to crash the uninstall procedure. We want a hardcore brute force uninsall! and most users "may NOT want" to even think about "ma-nu-al-ly" removing something from their DB.
		/*
				$DB->show_errors = $DB->halt_on_error = false;
				$Plugins = new Plugins();
				$DB->show_errors = $DB->halt_on_error = true;
				$at_least_one_failed = false;
				foreach( $Plugins->get_list_by_event( 'Uninstall' ) as $l_Plugin )
				{
					$success = $Plugins->call_method( $l_Plugin->ID, 'Uninstall', $params = array( 'unattended' => true ) );
					if( $success === false )
					{
						echo "Failed un-installing plugin $l_Plugin->classname (ID $l_Plugin->ID)...<br />\n";
						$at_least_one_failed = false;
					}
					else
					{
						echo "Uninstalled plugin $l_Plugin->classname (ID $l_Plugin->ID)...<br />\n";
					}
				}
				if( $at_least_one_failed )
				{
					echo "You may want to manually remove left files or DB tables from the failed plugin(s).<br />\n";
				}
				$DB->show_errors = $DB->halt_on_error = true;
		*/

		// Uninstall b2evolution: Delete DB & Cache files
		uninstall_b2evolution();

		// Stop the animation of the progress bar
		stop_install_progress_bar();

		// A link to back to install menu
		display_install_back_link();
		break;


	case 'utf8upgrade':
		/*
		 * -----------------------------------------------------------------------------------
		 * UPGRADE DB to UTF-8: all DB tables will be converted to UTF-8
		 * -----------------------------------------------------------------------------------
		 */

		load_funcs('_core/model/db/_upgrade.funcs.php');

		// Progress bar
		start_install_progress_bar( T_('Conversion in progress') );

		db_upgrade_to_utf8_ascii();

		// Stop the animation of the progress bar
		stop_install_progress_bar();

		// A link to back to install menu
		display_install_back_link();
		break;
}

block_close();
?>

<!-- InstanceEndEditable -->

			<footer class="footer">
				<p class="pull-right"><a href="https://github.com/b2evolution/b2evolution" class="text-nowrap"><?php echo T_('GitHub page'); ?></a></p>
				<p><a href="http://b2evolution.net/" class="text-nowrap">b2evolution.net</a>
				&bull; <a href="http://b2evolution.net/about/recommended-hosting-lamp-best-choices.php" class="text-nowrap"><?php echo T_('Find a host'); ?></a>
				&bull; <a href="http://b2evolution.net/man/" class="text-nowrap"><?php echo T_('Online manual'); ?></a>
				&bull; <a href="http://forums.b2evolution.net" class="text-nowrap"><?php echo T_('Help forums'); ?></a>
				</p>
			</footer>

		</div><!-- /container -->

	<?php
		// We need to manually call debug_info since there is no shutdown function registered during the install process.
		// debug_info( true ); // force output of debug info

		// The following comments get checked in the automatic install script of demo.b2evolution.net:
?>
<!-- b2evo-install-action:<?php echo $action ?> -->
<!-- b2evo-install-end -->
	</body>
</html>
