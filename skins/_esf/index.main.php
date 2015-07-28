<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Note: even if we request the same post as $Item earlier, the following will do more restrictions (dates, etc.)
// Init the MainList object:
init_MainList( $Blog->get_setting('posts_per_feed') );

// What level of detail do we want?
$feed_content = $Blog->get_setting('feed_content');
if( $feed_content == 'none' )
{	// We don't want to provide this feed!
	global $skins_path;
	require $skins_path.'_404_not_found.main.php';
	exit();
}

$Item = mainlist_get_item();
/* Weird random code to make an entity tag. */
$etag = '"' . @preg_replace('/(.*)[\n\r].*/', '$1', $Item->content | $app_version << $Item->wordcount) . '"';

if (@strstr($_SERVER['HTTP_IF_NONE_MATCH'], $etag))
{
	header('HTTP/1.1 304 Not Modified');
	header("ETag: $etag");
	
	exit();
}

header('Cache-Control: cache', true);
header("ETag: $etag");


skin_content_header('text/plain');
require_once 'ad.include.php';

echo "title\t";
html_entity_decode($Blog->disp('name', 'xml'), ENT_QUOTES, 'UTF-8');
echo "\n";
echo "link\t";
$Blog->disp('link', 'xml');
echo "\n";

do
{
	if (!is_object($Item)) break;
	echo "\n";
	echo strtotime($Item->issue_date);
	echo "\t";
	echo html_entity_decode($Item->title, ENT_QUOTES, 'UTF-8');
	echo "\t";
	$Item->permanent_url('single');
} while ($Item = mainlist_get_item() );

$Hit->log();
exit();

?>
