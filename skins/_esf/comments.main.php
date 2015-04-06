<?php
/**
 * This template generates an ESF feed for the requested blog's latest comments
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-structure}
 *
 * @package evoskins
 * @subpackage esf
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// What level of detail do we want?
$feed_content = $Blog->get_setting('comment_feed_content');
if( $feed_content == 'none' )
{	// We don't want to provide this feed!
	// This will normaly have been detected earlier but just for security:
	debug_die( 'Feeds are disabled.');
}

if( !$Blog->get_setting( 'comments_latest' ) )
{ // The latest comments are disabled for current blog
	// Redirect to page with text/html mime type
	header_redirect( get_dispctrl_url( 'comments' ), 302 );
	// will have exited
}

$post_ID = NULL;
if( isset($Item) )
{	// Comments for a specific Item:
  $post_ID = $Item->ID;
}

$CommentList = new CommentList2( $Blog );

// Filter list:
$CommentList->set_filters( array(
		'types' => array( 'comment' ),
		'statuses' => array ( 'published' ),
		'post_ID' => $post_ID,
		'order' => 'DESC',
		'comments' => $Blog->get_setting('comments_per_feed'),
	) );

// Get ready for display (runs the query):
$CommentList->display_init();


headers_content_mightcache( 'text/plain' );		// In most situations, you do NOT want to cache dynamic content!
require_once 'ad.include.php';

echo "title\t";
html_entity_decode($Blog->disp('name', 'xml'), ENT_QUOTES, 'UTF-8');
echo "\n";
echo "link\t";
$Blog->disp('link', 'xml') . '?_tempskin=atom&disp=comments';
echo "\n";

?>
<?php

while( $Comment = & $CommentList->get_next( false ) )
{
	echo "\n";
	echo strtotime(mysql2date('isoZ', $Comment->date, true));
	echo "\t";
	$Comment->get_Item();
	printf(
		$Skin->T_('%s responded to "%s"'),
		$Comment->get_author_name(),
		html_entity_decode($Comment->Item->title, ENT_QUOTES, 'UTF-8')
	);
	echo "\t";
	$Comment->permanent_url();
}
