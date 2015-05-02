<?php
$nine_weeks = time() + 9 * 7 * 24 * 60 * 60;

/*
 * Use the value of diaspora-pod-select if available.  It won't be in HTML5 browsers with <datalist> support.
 * Otherwise use the value of diaspora-pod in HTML5 browsers.
 * However, in browsers without <datalist>, we don't want to use diaspora-pod.
 */
if (!($diaspora_pod = param('diaspora-pod-select')))
	$diaspora_pod = param('diaspora-pod');

if ($diaspora_pod)
{
	setcookie('Diaspora-Pod', $diaspora_pod,  $nine_weeks);
	header('Location: ' . $diaspora_pod . '/bookmarklet?url=' . param('diaspora-url') . '&title=' . param('diaspora-title'));
	die();
}

skin_init( $disp );
skin_include('templates/_funcs.inc.php');

if (param('delete_cookies'))
{
	delete_cookies();
}

$show_mode = param('show', 'string', 'post');

$hl = 'single' != $disp ? 'h3' : 'h2';

/* Output the end of HTML */
function end_html()
{
	skin_include( 'templates/_sidebar.inc.php' );
	skin_include( 'templates/_body_footer.inc.php' );
	skin_include( 'templates/_html_footer.inc.php' );
}

skin_include( 'templates/_html_header.inc.php' );
?>

	<h1><?php bloginfo('name'); ?></h1>
<?php

skin_include( 'templates/_body_header.inc.php' );

$footer_elem = supports_xhtml() ? 'div' : 'footer';
$last_date = '';

if (is_text_browser() && 'menu' == $show_mode)
{
	end_html();
	return;
}

display_if_empty();
while( $Item = & mainlist_get_item() ):

$_item_title = ($disp == 'single') ? $Item->title : '';
$_item_url = ($disp == 'single') ? $Item->get_tinyurl() : '';
$_item_lang = preg_replace('/^(\w{2,3})-.+$/', '$1', $Item->dget('locale', 'raw'));
$_item_langattrs = (supports_xhtml() == FALSE) ? "lang=\"$_item_lang\"" : "xml:lang=\"$_item_lang\"";
$_item_country = strtolower(preg_replace('/^\w{2,3}-([^-]+).*$/', '$1', $Item->dget('locale', 'raw')));

preg_match('/^(\S*)\s*(\S*)$/', $Item->issue_date, $matches);
list($match, $date, $time) = $matches;
if ($last_date != $date && 'single' != $disp)
{
	echo '<h2 class="post-date">';
	$Item->issue_date();
	echo '</h2>';
	$last_date = $date;
}
?>
	<div role="article" id="<?php $Item->anchor_id(); ?>" <?php echo $_item_langattrs ?>>
<?php
	$Item->locale_temp_switch();
	printf('<%4$s class="storytitle"><a rel="%5$s" href="%1$s"  title="%3$s">%2$s</a></%4$s>', $Item->get_single_url(), $Item->title, __('Permanent link to full entry'), $hl, supports_xhtml() ? 'permalink' : 'bookmark');
?>
	<div class="meta"><?php get_meta($Item); ?></div>

<?php
if ($show_mode != 'comments')
{
	global $first_item, $last_item, $next_item, $prev_item;
	if ((!supports_xhtml() && !supports_link_toolbar()) && 'single' == $disp &&
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
		<?php skin_include('templates/_item_content.inc.php'); ?>
	</div>

<?php
	if ('single' == $disp)
		$hl[1] = $hl[1] + 1;

	$Item->tags(
		array(
			'after' => "\n</li>\n</ul>\n</div>\n",
			'before' => "<div class=\"meta\">\n<$hl class=\"tag-list-header\">" . __('Tags') . "</$hl>\n<ul class=\"tag-list\">\n<li>",
			'separator' => '</li><li>',
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

if ($show_mode != 'post') skin_include( 'templates/_item_feedback.inc.php');
endwhile;

skin_include('$disp$', array(
  'disp_posts' => '',
  'disp_single' => '',
  'disp_page' => '',
  'disp_profile' => 'templates/_profile.disp.php',
  'disp_user' => 'templates/_user.disp.php',
));

if ($MainList)
{
	$row = $DB->get_row('SELECT COUNT(*) FROM ' . $MainList->ItemQuery->dbtablename .
		' WHERE post_main_cat_ID IN (SELECT cat_ID FROM T_categories' .
		' WHERE cat_blog_ID=' . $Blog->ID . ')', ARRAY_A, 0);

	$MainList->page_links(array(
			'block_start' => "\n" . '<!-- begin footer -->' . "\n" . '<' . $footer_elem . ' id="page-links"' . (supports_xhtml() ? ' role="navigation"' : '') . '>',
			'block_end' => '</' . $footer_elem . '>' . "\n" . '<!-- end footer -->' . "\n",
			'prev_text' => '<span class="sago">←</span>',
			'next_text' => '<span class="sago">→</span>',
			'list_span' => ceil($row['COUNT(*)'] / $Blog->get_setting('posts_per_page')),
		)
	);
}

?>

</section>

<?php
if (!is_text_browser() || 'menu' == $disp)
	end_html();
?>
