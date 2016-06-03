<?php
/**
 * This is the template that displays the 404 disp content
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $disp_detail, $baseurl, $app_name;

echo '<div class="error_404">';

echo '<h2>' . $Skin->T_('404 Not Found') . '</h2>';

printf($Skin->T_('<p><a href="%s">%s</a> cannot resolve the requested URL.</p>'), $baseurl, $app_name);

// You may use this to further customize this page:
// echo $disp_detail;

echo '</div>';

?>
