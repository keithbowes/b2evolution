<?php
/**
 * This is the template that displays a single comment
 *
 * This file is not meant to be called directly.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Default params:
$params = array_merge( array(
    'comment_start'  => '<div class="bComment">',
    'comment_end'    => '</div>',
		'link_to'        => 'userurl>userpage',
    'Comment'        => NULL, // This object MUST be passed as a param!
		'author_link_text'     => 'preferredname',
		'before_image'         => '<div class="image_block">',
		'before_image_legend'  => '<div class="image_legend">',
		'after_image_legend'   => '</div>',
		'after_image'          => '</div>',
		'image_size'           => 'fit-400x320',
		'Comment'              => NULL, // This object MUST be passed as a param!

	), $params );

/**
 * @var Comment
 */
$Comment = & $params['Comment'];

?>
<!-- ========== START of a COMMENT/TB/PB ========== -->
<?php
if(!empty($Comment->author_user_ID) && $Comment->author_user_ID == $Item->get_creator_User()->ID) $author_class = ' class="author-comment"';
else $author_class = ' class="user-comment"';
?>
	<div<?php echo $author_class; ?> id="<?php echo $Comment->get_anchor(); ?>">
<?php
  echo $params['comment_start'];
?>
	<div class="bCommentTitle">
	<?php
		switch( $Comment->get( 'type' ) )
		{
			case 'comment': // Display a comment:
				if( empty($Comment->ID) )
				{	// PREVIEW comment
					echo T_('PREVIEW Comment from:').' ';
				}
				else
				{
					global $DB;
					$DB->query('SELECT comment_in_reply_to_cmt_ID FROM T_comments WHERE comment_ID=' . $Comment->ID);
					$refcomment = $DB->get_row(NULL, ARRAY_N)[0];

					if (0 != $refcomment)
					{
						$DB->query('SELECT comment_author, comment_author_user_ID FROM T_comments WHERE comment_ID=' . $refcomment);
						$refname = $DB->get_row(NULL, ARRAY_N)[0];
						if (!$refname)
						{
							$refid = $DB->get_row(NULL, ARRAY_N, 0)[1];
							$DB->query('SELECT user_nickname, user_firstname, user_lastname, user_login FROM T_users WHERE user_ID=' . $refid);
							$refname = $DB->get_row(NULL, ARRAY_N, 0)[0];	
							if (!$refname)
							{
								$refname = $DB->get_row(NULL, ARRAY_N, 0)[1] . ' ' . $DB->get_row(NULL, ARRAY_N, 0)[2];
								if (!$refname)
									$refname = $DB->get_row(NULL, ARRAY_N, 0)[3];
							}
						}
						$after_user_text = sprintf($Skin->T_(' (in response to <a href="%s">%s</a>)'), htmlentities($_SERVER['REQUEST_URI']) . '#c' . $refcomment, $refname);
					} else $after_user_text = '';
				
					// Normal comment
					$Comment->permanent_link( array(
							'before'    => '',
							'after'     => ' '.$Skin->T_('from').' ',
							'text' 		=> T_('Comment'),
							'title'     => '#',
							'nofollow'	=> true,
						) );
				}

				$Comment->author2( array(
						'before'       => ' ',
						'after'        => $after_user_text,
						'before_user'  => '',
						'after_user'   => $after_user_text,
						'format'       => 'htmlbody',
						'link_to'	   => 'userpage>userurl',		// 'userpage' or 'userurl' or 'userurl>userpage' or 'userpage>userurl'
						'link_text'    => 'preferredname',
					) );
				$Comment->msgform_link( $Blog->get('msgformurl') );
				break;

			case 'trackback': // Display a trackback:
				$Comment->permanent_link( array(
						'before'    => '',
						'after'     => ' '.T_('from:').' ',
						'text' 		=> T_('Trackback'),
						'nofollow'	=> true,
					) );
				$Comment->author( '', '#', '', '#', 'htmlbody', true );
				break;

			case 'pingback': // Display a pingback:
				$Comment->permanent_link( array(
						'before'    => '',
						'after'     => ' '.T_('from:').' ',
						'text' 		=> T_('Pingback'),
						'nofollow'	=> true,
					) );
				$Comment->author( '', '#', '', '#', 'htmlbody', true );
				break;
		}
	?>
  </div>
	<?php $Comment->rating(); ?>
    <div class="bCommentText">
		<?php 
		$Comment->avatar();
		$Comment->content();
?>
	</div>
	<div class="bCommentSmallPrint">
		<?php
$Comment->date();
echo $Skin->T_(' at ');
$Comment->time();

		$Comment->reply_link(' <span style="display: none">|</span> '); /* Link for replying to the Comment */
		$Comment->vote_helpful( '', '', '&amp;', true, true );


			$Comment->edit_link( ' <span style="display: none">|</span> ', '', '#', '#', 'permalink_right' ); /* Link to backoffice for editing */
			$Comment->delete_link( ' <span style="display: none">|</span> ', '', '#', '#', 'permalink_right' ); /* Link to backoffice for deleting */
?>
  </div>
  </div>
<?php
echo $params['comment_end'];
?>
<!-- ========== END of a COMMENT/TB/PB ========== -->
