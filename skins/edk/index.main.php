<?php
if (param('diaspora-pod'))
{
	setcookie('Diaspora-Pod', param('diaspora-pod'), time() + (9 * 7 * 24 * 60 * 60) /* 9 weeks */);
	header('Location: ' . param('diaspora-pod') . '/bookmarklet?url=' . param('url') . '&title=' . param('title'));
	die();
}

$show_mode = param('show', 'string', 'post');

$last_date = '';
$hl = 'single' != $disp ? 'h3' : 'h2';

/* Functions to avoid redundant translations of core phrases */
function __($str)
{
	return T_($str);
}

function _s($str)
{
	return TS_($str);
}

function _t($str)
{
	return NT_($str);
}

skin_init( $disp );
skin_include( '_html_header.inc.php' );
skin_include( '_body_header.inc.php' );

display_if_empty();
while( $Item = & mainlist_get_item() ):

global $use_strict;
$_item_title = ($disp == 'single') ? $Item->get_title() : '';
$_item_url = ($disp == 'single') ? $Item->get_tinyurl() : '';
$_item_lang = preg_replace('/^(\w{2,3})-.+$/', '$1', $Item->dget('locale', 'raw'));
$_item_langattrs = (supports_xhtml() == FALSE) ? "lang=\"$_item_lang\"" : ($use_strict ? "xml:lang=\"$_item_lang\" lang=\"$_item_lang\"" : "xml:lang=\"$_item_lang\"");
$_item_country = strtolower(preg_replace('/^\w{2,3}-([^-]+).*$/', '$1', $Item->dget('locale', 'raw')));

preg_match('/^(\S*)\s*(\S*)$/', $Item->issue_date, $matches);
list($match, $date, $time) = $matches;
if ($last_date != $date && 'single' != $disp)
{
  echo '<h2>';
  $Item->issue_date();
  echo '</h2>';
}
$last_date = $date;
?>
	<div class="post" id="<?php $Item->anchor_id(); ?>" <?php echo $_item_langattrs ?>>
<?php 
	$Item->locale_temp_switch();
	printf('<%4$s class="storytitle"><a rel="permalink" href="%1$s"  title="%3$s">%2$s</a></%5$s>', $Item->get_single_url(), $Item->title, __('Permanent link to full entry'), $hl, $hl);
?>
  <div class="meta"><?php echo __('Posted in'); ?> <?php $Item->categories(); ?>
 <?php echo __('by'); ?>
	<a href="<?php $Item->get_creator_User()->url(); ?>"><?php echo $Item->get_creator_User()->firstname; ?></a>
 <?php
printf($Skin->T_('on %s'), $Item->get_issue_date());
echo $Skin->T_(' at ');
$Item->issue_time();
$Item->locale_flag();
echo preg_replace('/(\s*alt=)"[^"]*"/', '$1""', $Item->get_edit_link(array('title' => '#')));
?></div>

<?php
if ($show_mode != 'comments' || $disp != 'single')
{
	global $first_item, $last_item, $next_item, $prev_item;
	if ((!supports_xhtml() && !supports_link_toolbar()) &&
		(NULL !== $first_item || NULL !== $last_item || NULL !== $next_item || NULL !== $prev_item))
	{
?>
<div id="prevnext">
<?php
		if (NULL !== $first_item)
		{
?>
		<div id="firstitem">
			<a <?php if (supports_xhtml()) echo 'rel="first" '; ?>href="<?php echo get_full_url($first_item['post_urltitle']) ?>">↓ <?php echo htmlspecialchars($first_item['post_title']) ?></a>
		</div>
<?php
		}
		if (NULL !== $last_item)
		{
?>
		<div id="lastitem">
			<a <?php if (supports_xhtml()) echo 'rel="last" '; ?>href="<?php echo get_full_url($last_item['post_urltitle']) ?>"><?php echo htmlspecialchars($last_item['post_title']) ?> ↑</a>
		</div>
<?php
		}
		if (NULL !== $prev_item)
		{
?>
		<div id="previtem">
			<a rel="prev" href="<?php echo $prev_item->get_permanent_url() ?>">← <?php echo htmlspecialchars($prev_item->title) ?></a>
		</div>
<?php
		}
		if (NULL !== $next_item)
		{
?>
		<div id="nextitem">
			<a rel="next" href="<?php echo $next_item->get_permanent_url() ?>"><?php echo htmlspecialchars($next_item->title) ?> →</a>
		</div>
<?php
		}
?>
</div>

<?php
	}
?>

<div class="storycontent">
		<?php $Item->content(); ?>
	</div>

<?php
	if ('single' == $disp)
		$hl[1] = $hl[1] + 1;

		$Item->tags(
			array(
				'after' => "\n</ul>\n</div>\n",
				'before' => "<div class=\"meta\">\n<$hl class=\"tag-list-header\">" . __('Tags') . "</$hl>\n<ul class=\"tag-list\">",
				'separator' => '',
				'tag_after' => '</li>',
				'tag_before' => "\n<li>",
			)
		);

		$Item->feedback_link(
			array(
				'link_after' => '</div>',
				'link_anchor_more' => '',
				'link_anchor_one' => '',
				'link_anchor_zero' => '',
				'link_before' => '<div class="postmetadata">',
				'show_in_single_mode' => !empty($show_mode),
				'type' => 'comments',
				'url' => $Item->get_feedback_url() . '?show=comments&amp;redir=no#comments',
			)
		);
}
?>

</div>
<?php

if ($show_mode != 'post') skin_include( '_item_feedback.inc.php');
endwhile;

skin_include('$disp$', array(
  'disp_posts' => '',
  'disp_single' => '',
  'disp_page' => ''
));

if ($MainList)
{
	$row = $DB->get_row('SELECT COUNT(*) FROM ' . $MainList->ItemQuery->dbtablename .
		' WHERE post_main_cat_ID IN (SELECT cat_ID FROM T_categories' .
		' WHERE cat_blog_ID=' . $Blog->ID . ')', ARRAY_A, 0);
	$MainList->page_links(array(
			'block_start' => '<div id="page-links">',
			'block_end' => '</div>',
			'prev_text' => '<span class="sago">←</span>',
			'next_text' => '<span class="sago">→</span>',
			'list_span' => ceil($row['COUNT(*)'] / $Blog->get_setting('posts_per_page')),
		)
	);
}

  skin_include( '_body_footer.inc.php' );
  skin_include( '_html_footer.inc.php' );
?>

