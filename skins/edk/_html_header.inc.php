<?php
/**
 * This is the HTML header include template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://manual.b2evolution.net/Skins_2.0}
 *
 * This is meant to be included in a page template.
 * Note: This is also included in the popup: do not include site navigation!
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Hit, $Session, $Skin;
global $app_name, $app_version, $xmlsrv_url;
global $baseurl, $baseurlroot, $io_charset;

global $content_type;
function parse_accept()
{
	$ret = array();
	$acc = @$_SERVER['HTTP_ACCEPT'];
	$arr = explode(',', $acc);
	for ($i = 0; $i < count($arr); $i++)
	{
		$ar2 = explode(';q=', $arr[$i]);
		$type = trim($ar2[0]);

		if ($type == 'text/*' && empty($ret['text/html']))
			$type = 'text/html';
		else if ($type == 'application/*' && empty($ret['application/xhtml+xml']))
			$type = 'application/xhtml+xml';
		else if ($type == '*/*' || $type == '*')
		{
			if (!empty($ret['text/html']) && empty($ret['application/xhtml+xml']))
				$type = 'application/xhtml+xml';
			elseif (empty($ret['text/html']))
				$type = 'text/html';
			else
				continue;
		}

		@$qual = $ar2[1];
		if (empty($qual))
			$qual = 1;

		$ret[$type] = $qual;
	}

	return $ret;
}

function supports_xhtml()
{
	global $use_strict;

	/* Make sure HTML validators get the right representation */
	if ($_SERVER['HTTP_USER_AGENT'] == 'Validator.nu/LV')
	{
		$r = FALSE;
	}
	elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Validator') !== FALSE)
	{
		$r = TRUE;
	}

	/* If not a validator, let the HTTP headers decide */
	if (!isset($r))
	{
		$types = parse_accept();
		if (!empty($types['application/xhtml+xml']))
		{
			if (!empty($types['text/html']))
				$r = $types['application/xhtml+xml'] >= $types['text/html'];
			else
				$r = $types['application/xhtml+xml'] > 0;
		}
		else
			$r = FALSE;
	}

	$use_strict = $r;
	return $r;
}

function is_text_browser()
{
	$ua = $_SERVER['HTTP_USER_AGENT'];
	return preg_match('/^Lynx|[Ll]inks/', $ua);
}

function supports_link_toolbar()
{
	$ua = $_SERVER['HTTP_USER_AGENT'];
	/* Note: Opera > 12.x is based on WebKit and doesn't have a link toolbar,
	 * but its user-agent is OPR instead of Opera, so we're OK. */
	$ret = preg_match('/Iceape|Opera|SeaMonkey/', $ua); // Graphical browsers
	$ret = $ret || is_text_browser(); // Text browsers
	$ret = $ret || preg_match('/UdiWWW|i?C[Aa][Bb]|Emacs_W3/', $ua); // Ancient browsers
	return $ret;
}

if (supports_xhtml())
{
	$content_type = 'application/xhtml+xml';
	skin_content_header($content_type);
	echo '<?xml version="1.0" encoding="' . $io_charset . '"?' . '>';
	echo "\n";
}
else
{
	$content_type = 'text/html';
	skin_content_header($content_type);
}

global $locale;
$locale = preg_replace('/(\w{2,3})-.*$/', '$1', locale_lang(false));

function get_full_url($part = '')
{
	global $Blog;
	global $baseurl;

	$r = $baseurl . $Blog->siteurl;
	$r .= empty($part) ? '' : '/' . $part;
	return $r;
}

function is_valid_query($result)
{
	return ($result !== FALSE && is_array($result) && count($result) > 0);
}

function get_post_urltitle($dir = '', $row = 0)
{
	global $Blog, $DB, $Item;

	$blogid = isset($Item) ? $Item->blog_ID : isset($Blog) ? $Blog->ID : -1;
	$blogslug = isset($Item) ? $Item->urltitle : '';
	$categorytablename = isset($Item) ? $Item->main_Chapter->dbtablename : 'T_categories';
	$itemtablename = isset($Item) ? $Item->dbtablename : 'T_items__item';

	if (!empty($dir))
		$item_data = $DB->get_row('SELECT post_datestart, post_ID, post_main_cat_ID, post_title, post_urltitle FROM ' . $itemtablename . ' ORDER BY UNIX_TIMESTAMP(post_datestart) ' . $dir, ARRAY_A, $row);
	elseif (isset($Item))
		$item_data = $DB->get_row('SELECT post_datestart, post_ID, post_main_cat_ID, post_title, post_urltitle FROM ' . $itemtablename . ' WHERE post_ID=' . $Item->ID, ARRAY_A, 0);
	else
	{
		return '';
	}

	if (!is_valid_query($item_data)) return NULL;

	$cat_data = $DB->get_row('SELECT cat_parent_ID, cat_name, cat_blog_ID FROM ' . $categorytablename . ' WHERE cat_ID = ' . $item_data['post_main_cat_ID'], ARRAY_A, 0);
	if (!is_valid_query($cat_data)) return NULL;

	$pathinfo = $DB->get_row('SELECT cset_value from T_coll_settings WHERE cset_coll_ID = ' . $blogid . ' AND cset_name = \'single_links\'', ARRAY_A, 0);
	if (!is_valid_query($pathinfo)) return NULL;

	$cat_data['cat_name'] = strtolower($cat_data['cat_name']);

	switch ($pathinfo['cset_value'])
	{
		case 'param_num':
			$item_data['post_urltitle'] = '?p=' . $item_data['post_ID']; 
			break;
		case 'param_title':
			$item_data['post_urltitle'] = '?title=' . $item_data['post_urltitle'];
		case 'short':
			// Do nothing
			break;
		case 'y':
			$item_data['post_urltitle'] = strftime('%Y/', $item_data['post_datestart']) . $item_data['post_urltitle'];
			break;
		case 'ym':
			$item_data['post_urltitle'] = strftime('%Y/%m/', $item_data['post_datestart']) . $item_data['post_urltitle'];
			break;
		case 'ymd':
			$item_data['post_urltitle'] = strftime('%Y/%m/%d/', $item_data['post_datestart']) . $item_data['post_urltitle'];
			break;
		case 'subchap':
			$item_data['post_urltitle'] = $cat_data['cat_name'] . '/' . $item_data['post_urltitle']; 
			break;
		case 'chapters':
			if (isset($cat_data['cat_parent_ID']))
			{
				$parent_cat = $DB->get_row("SELECT cat_name FROM $categorytablename WHERE cat_ID = " . $cat_data['cat_parent_ID'], ARRAY_A, 0);
				$item_data['post_urltitle'] = $cat_data['cat_name'] . '/' . strtolower($parent_cat['cat_name']) .' /' . $item_data['post_urltitle']; 
			}
			else
				$item_data['post_urltitle'] = $cat_data['cat_name'] . '/' . $item_data['post_urltitle']; 
			break;
	}

	return $item_data['post_urltitle'];
}

/* If you can think of a better way to do this, you're my hero. */
function get_item($dir)
{
	global $Blog, $DB, $Item;
	$blogid = $Item ? $Item->blog_ID : $Blog ? $Blog->ID : -1;
	$blogslug = $Item ? $Item->urltitle : '';
	$categorytablename = $Item ? $Item->main_Chapter->dbtablename : 'T_categories';
	$itemtablename = $Item ? $Item->dbtablename : 'T_items__item';
	if (!$categorytablename || !$itemtablename) return;
	$row = 0;
	// Here we iterate through the items until we can get an item with a category associated with the current blog
	while (true) {
		/* I do feel dirty about direct DB access (not exactly future-proof),
		 * but I see no alternative. */
		$item_data = $DB->get_row("SELECT post_datestart, post_ID, post_main_cat_ID, post_title, post_urltitle FROM $itemtablename ORDER BY UNIX_TIMESTAMP(post_datestart) $dir", ARRAY_A, $row);
		if (!is_valid_query($item_data))
			return NULL;

		$item_data['post_datestart'] = strtotime($item_data['post_datestart']);
		$cat_data = $DB->get_row("SELECT cat_parent_ID, cat_name, cat_blog_ID FROM $categorytablename WHERE cat_ID = " . $item_data['post_main_cat_ID'], ARRAY_A, 0);
		if (!is_valid_query($cat_data))
			return NULL;
		else if ($cat_data['cat_blog_ID'] == $blogid)
		{
			if ($item_data['post_urltitle'] != $blogslug)
			{
				$item_data['post_urltitle'] = get_post_urltitle($dir, $row);
				return $item_data;
			}
			else
				return NULL;
		}

		$row++;
	}
}

global $first_item, $last_item;
$first_item = get_item('ASC');
$last_item = get_item('DESC');

function get_license($params = array())
{
	global $Skin;
	global $locale;

	$params = array_merge(
		array(
			'display' => TRUE,
			'format' => 'html',
		),
		$params
	);

	$fmt ='<a rel="license" href="http://creativecommons.org/licenses/by/4.0/deed.' . $locale . '"><img src="https://licensebuttons.net/l/by/4.0/80x15.png" alt="' . $Skin->T_('Creative Commons') . '" title="' . $Skin->T_('Creative Commons') . '" /></a>';
	$func = $params['display'] ? 'printf' : 'sprintf';
	return $func(($params['format'] == 'html') ? $fmt : $Skin->T_('Creative Commons'));
}

function get_copyright($params = array())
{
	global $Blog, $Skin;
	global $first_item;

	$params = array_merge(
		array(
			'display' => TRUE,
			'license' => TRUE,
		),
		$params
	);

	$fmt = str_replace(
		array('(C)', '-'),
		array('©', '–'),
		$params['license'] ?
		# TRANS: Params: Start year, end year, author, license
		$Skin->T_('(C) %1$d-%2$d %3$s under %4$s') :
		# TRANS: Params: Start year, end year, author
		$Skin->T_('(C) %1$d-%2$d %3$s')
	);	

	if ($params['display'])
		$func = 'printf';
	else
		$func = 'sprintf';

	return $func($fmt, strftime('%Y', $first_item['post_datestart']), strftime('%Y'), $Blog->get_owner_User()->get('fullname'), get_license(array('display' => FALSE)));
}

function get_prevnext_item($which)
{
	global $MainList;
	if ($MainList)
	{
		return $MainList->get_prevnext_Item($which);
	}
	return NULL;
}
/* b2evolution's idea of prev and next seem backwards to me */
global $next_item, $prev_item;
$next_item = get_prevnext_item('prev');
$prev_item = get_prevnext_item('next');

if (!supports_xhtml())
{
	$dtd = '<!DOCTYPE html>';	
	$langattrs ="lang=\"$locale\"";
	$htmlelem = "<html $langattrs>";
}
else
{
	global $use_strict;
	global $skin;
	$xml_base = $Blog->get_local_skins_url().$skin.'/';
	for ($i = 0; $i < 23; $i++)
		$space .= ' ';
	$dtd = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 2.0//EN"' . "\n" .
	   $space . '"' . $xml_base . 'xhtml2.dtd">';

	$langattrs ="xml:lang=\"$locale\" xml:base=\"". $xml_base . '"';
	$htmlelem = "<html xmlns=\"http://www.w3.org/1999/xhtml\" $langattrs>";
}


$params = array_merge( array(
	'auto_pilot'    => 'seo_title',
	'body_class'    => NULL,
	'generator_tag' => '<meta property="generator" content="' . $app_name . ' '.$app_version.'" /><!-- ' . $Skin->T_('Please leave this for stats') . " -->\n",
	'html_tag'      => "$dtd\n$htmlelem\n",
), $params );


echo $params['html_tag'];
?>

<head>
<?php
	if (!supports_xhtml()) echo "<meta charset=\"$io_charset\">\n"; /* Charset for static pages */
if (!supports_xhtml())
	skin_base_tag(); /* Base URL for this skin. You need this to fix relative links! */
	$Plugins->trigger_event( 'SkinBeginHtmlHead' );
?>
  <title><?php
		// ------------------------- TITLE FOR THE CURRENT REQUEST -------------------------
		request_title($params);
		// ------------------------------ END OF REQUEST TITLE -----------------------------
	?></title>
		<meta property="DC.rights" content="<?php get_copyright(array('license' => FALSE)); ?>" />
		<meta property="copyright" content="<?php get_copyright(array('license' => FALSE)) ?>" />
		<meta property="license" content="<?php get_license(array('format' => 'text')); ?>" />
<?php
		skin_description_tag();
		skin_keywords_tag();
		skin_opengraph_tags();
		robots_tag();

		echo $params['generator_tag'];

	if (supports_xhtml() || supports_link_toolbar())
	{
		$comment_args = is_text_browser() ? '?show=menu&amp;redir=no' : '';
?>
  <link rel="bookmark" href="<?php echo get_full_url(get_post_urltitle()); ?>#content" title="<?php echo $Skin->T_('Main Content'); ?>" />
  <link rel="bookmark" href="<?php echo get_full_url(get_post_urltitle()) . $comment_args; ?>#menu" title="<?php echo $Skin->T_('Menu'); ?>" />

<?php
if ('single' == $disp)
		{
?>
  <link rel="bookmark" href="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>?show=comments&amp;redir=no#comments" title="<?php echo __('Comments') ?>" />
<?php
		}
?>
  <link rel="top" href="<?php echo $baseurl; ?>" title="<?php echo __('Go back to home page'); ?>" />

<?php
if ('posts' != $disp)
{
?>
	<link rel="up" href="<?php echo $baseurl . $Blog->siteurl ?>" title="<?php echo htmlspecialchars($Blog->name); ?>" />
<?php
}
else
{
	global $DB;
	$DB->query('SELECT blog_locale, blog_name, blog_siteurl FROM ' . $Blog->dbtablename . ' WHERE blog_ID <> ' . $Blog->ID . ' AND blog_in_bloglist = 1');
	while ($row = $DB->get_row(NULL, ARRAY_A))
	{
		$lang = preg_replace('/^([^-]+)-?.*$/', '$1', $row['blog_locale']);
		if (supports_xhtml())
		{
			if ($use_strict)
			{
				$linklang = "lang=\"$lang\" xml:lang=\"$lang\"";
			}
			else
			{
				$linklang = "xml:lang=\"$lang\"";
			}
		}
		else
			$linklang = "lang=\"$lang\"";
		echo '<link rel="alternate" href="' . $baseurl . $row['blog_siteurl'] . '" title="' . $row['blog_name'] . '" ' . $linklang . ' hreflang="' . $lang . '" />' . "\n";
	}
}

if (NULL !== $first_item)
{
?>
	<link rel="first" href="<?php echo get_full_url($first_item['post_urltitle']) ?>" title="↑ <?php echo htmlspecialchars($first_item['post_title']); ?>" />
<?php
}
if (NULL !== $last_item)
{
?>
	<link rel="last" href="<?php echo get_full_url($last_item['post_urltitle']) ?>" title="<?php echo htmlspecialchars($last_item['post_title']) ?> ↓" />
<?php
}

if (NULL !== $prev_item)
{
?>
  <link rel="prev" href="<?php echo $prev_item->get_permanent_url(); ?>" title="← <?php echo htmlspecialchars($prev_item->title); ?>" />
<?php
}
if (NULL !== $next_item)
{
?>
  <link rel="next" href="<?php echo $next_item->get_permanent_url(); ?>" title="<?php echo htmlspecialchars($next_item->title); ?> →" />
<?php
}}
if ($Blog->get_setting('feed_content') != 'none')
{
	if (file_exists("$skins_path/_esf"))
	{
?>
  <link rel="alternate" type="text/plain" title="ESF 1.0" href="<?php echo $baseurl . $Blog->siteurl; ?>?tempskin=_esf" />
<?php
	}
	if (file_exists("$skins_path/_rss3"))
	{
?>
  <link rel="alternate" type="text/plain" title="RSS 3.0" href="<?php echo $baseurl . $Blog->siteurl; ?>?tempskin=_rss3" />
<?php
	}
	if (!file_exists("$skins_path/_esf") && !file_exists("$skins_path/_rss3"))
	{
?>
  <link rel="alternate" type="application/atom+xml" title="Atom 1.0" href="<?php $Blog->disp('atom_url', 'raw'); ?>" />
<?php
	}
}
?>
  <link rel="EditURI" type="application/rsd+xml" title="RSD" href="<?php echo $xmlsrv_url; ?>rsd.php?blog=<?php echo $Blog->ID; ?>" />
<?php
	require_css('style.css', 'relative', NULL, 'all');
	require_css('speech.css', 'relative', NULL, 'speech');
	require_css('visual.css', 'relative', NULL, 'handheld, print, projection, screen, tty, tv');
	require_css('smallscreen.css', 'relative', NULL, '(max-width: 640px)');
	require_css('print.css', 'relative', NULL, 'print');

	if (supports_xhtml())
	{
		require_css('xhtml.css', 'relative', NULL, 'handeld, print, projection, screen, tty, tv');
		require_css('smallscreen-xhtml.css', 'relative', NULL, '(max-width: 640px)');
		require_css('print-xhtml.css', 'relative', NULL, 'print');
	}

	include_headlines(); /* Add javascript and css files included by plugins and skin */

	$Blog->disp( 'blog_css', 'raw');
	$Blog->disp( 'user_css', 'raw');
	$Blog->disp_setting( 'head_includes', 'raw');
?>
</head>

<body<?php skin_body_attrs( array( 'class' => $params['body_class'] ) ); ?>>

