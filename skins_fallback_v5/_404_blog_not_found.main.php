<?php
/**
 * This page displays an error message when we cannot resolve the extra path.
 *
 * This happens when you request an url of the form http://.../some_stub_file.php/some_malformed_extra_path/...
 *
 * @package skins
 * @subpackage default_site_skin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

header_http_response('404 Not Found');

$page_title = '404 Not Found';
// -------------------------- HTML HEADER INCLUDED HERE --------------------------
siteskin_include( '_html_header.inc.php' );
// -------------------------------- END OF HEADER --------------------------------

// ---------------------------- SITE HEADER INCLUDED HERE ----------------------------
// If site headers are enabled, they will be included here:
siteskin_include( '_site_body_header.inc.php' );
// ------------------------------- END OF SITE HEADER --------------------------------
?>
<h1>404 Not Found</h1>
<p>The collection you requested doesn't seem to exist on <a href="<?php echo $baseurl ?>">this system</a>.</p>
<?php
// ---------------------------- SITE FOOTER INCLUDED HERE ----------------------------
// If site footers are enabled, they will be included here:
siteskin_include( '_site_body_footer.inc.php' );
// ------------------------------- END OF SITE FOOTER --------------------------------

// -------------------------- HTML FOOTER INCLUDED HERE --------------------------
siteskin_include( '_html_footer.inc.php' );
// -------------------------------- END OF FOOTER --------------------------------
?>