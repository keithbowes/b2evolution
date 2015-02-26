<?php
/**
 * This file is the template that displays an access denied for not logged in users
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-structure}
 *
 * It is used to display the blog when no specific page template is available to handle the request.
 *
 * @package evoskin
 *
 * @version $Id: access_requires_login.main.php 8256 2015-02-13 06:50:39Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $app_version, $disp, $Blog;

// Display in-skin login form
$disp = 'login';

require $ads_current_skin_path.'index.main.php';
?>