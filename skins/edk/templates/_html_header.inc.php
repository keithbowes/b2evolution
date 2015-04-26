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

global $Skin;
global $app_name, $app_version, $xmlsrv_url;
global $baseurl, $content_type, $io_charset;

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
		elseif ($type == 'application/*' && empty($ret['application/xhtml+xml']))
			$type = 'application/xhtml+xml';
		elseif ($type == '*/*' || $type == '*')
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

function init_content_type()
{
	global $content_type, $supports_xhtml, $use_strict;

	if (!isset($content_type))
	{
		/* Make sure user agents with inaccurate Accept headers get the right represention */
		$ua_overrides = array(
			'AppleWebKit' => TRUE,
			'Dillo' => FALSE,
			'Validator.nu' => FALSE,
			'Validator' => TRUE,
		);

		foreach ($ua_overrides as $ua => $support)
		{
			if (strpos($_SERVER['HTTP_USER_AGENT'], $ua) !== FALSE)
			{
				$r = $support;
				break;
			}
		}

		if (!isset($r))
		{
			/* If not overriden, let the HTTP headers decide */
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

		$content_type = $r ? 'application/xhtml+xml' : 'text/html';
	}

	$supports_xhtml = 'text/html' != $content_type;
	$use_strict = $supports_xhtml;
}

function supports_xhtml()
{
	global $supports_xhtml;
	return $supports_xhtml;
}

function is_text_browser()
{
	$ua = $_SERVER['HTTP_USER_AGENT'];
	return preg_match('/^L_?y_?n_?x|[Ll]inks/', $ua);
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
	$categorytablename = isset($Item) && isset($Item->main_Chapter) ? $Item->main_Chapter->dbtablename : 'T_categories';
	$itemtablename = isset($Item) && isset($Item->dbtablename) ? $Item->dbtablename : 'T_items__item';
	$postid = isset($Item) && isset($Item->ID) ? $Item->ID : 0;

	if (!empty($dir))
		$item_data = $DB->get_row('SELECT post_datestart, post_ID, post_main_cat_ID, post_title, post_urltitle FROM ' . $itemtablename . ' ORDER BY UNIX_TIMESTAMP(post_datestart) ' . $dir, ARRAY_A, $row);
	elseif (isset($Item))
		$item_data = $DB->get_row('SELECT post_datestart, post_ID, post_main_cat_ID, post_title, post_urltitle FROM ' . $itemtablename . ' WHERE post_ID=' . $postid, ARRAY_A, 0);
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

/* Get the DB info about the first or last item of the current blog.
 * If you can think of a better way to do this, you're my hero.
 *
 * @param string The direction to go. Can be ASC (ascending) for the first item or DESC  (descending) for the last item.
 * @return array An array containing the DB fields.
 */
function get_item($dir)
{
	global $Blog, $DB, $Item;
	$blogid = $Item ? $Item->blog_ID : $Blog ? $Blog->ID : -1;
	$blogslug = $Item ? $Item->urltitle : '';
	$categorytablename = isset($Item) && isset($Item->main_Chapter) ? $Item->main_Chapter->dbtablename : 'T_categories';
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
		elseif ($cat_data['cat_blog_ID'] == $blogid)
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

/* b2evolution's idea of prev and next seems backwards to me */
global $next_item, $prev_item;
$next_item = get_prevnext_item('prev');
$prev_item = get_prevnext_item('next');

function edk_get_meta($type, $value, $content, $extra = array())
{
	if (supports_xhtml())
	{
		if ($type != 'charset')
			$r = sprintf('<meta property="%s" content="%s" />', $value, $content);
		else
		{
			global $content_type;
			$r = sprintf('<meta property="Content-Type" content="%s;charset=%s" />', $content_type, $value);
		}
	}
	else
	{
		$r = '<meta ';
		$attrs = array(
			$type => $value,
			'content' => $content,
		);
		$attrs = array_merge($extra, $attrs);

		do
		{
			$key = key($attrs);
			$r .= $key . '="' . $attrs[$key] . '" ';
		} while(next($attrs));
		$r .= '/>';
	}

	return $r;
}

function edk_meta($type, $value, $content, $extra = array())
{
	add_headline(edk_get_meta($type, $value, $content, $extra), $value);
}

function edk_css_include()
{
	global $Skin;
	global $edk_base, $headlines;
	$visual_media = 'handheld, print, projection, screen, tty, tv';

	/* Main CSS files */
	require_css($edk_base.'css/core.css', 'relative', NULL, 'all');
	require_css($edk_base.'css/visual.css', 'relative', NULL, $visual_media);

	/* Alternate CSS files */
	require_css($edk_base.'css/classic.css', 'relative', to_ascii($Skin->T_('Classic Look')), $visual_media);
	require_css($edk_base.'css/clear.css', 'relative', to_ascii($Skin->T_('Clear Look')), $visual_media);
	require_css($edk_base.'css/transitional.css', 'relative', to_ascii($Skin->T_('Transitional Look')), $visual_media);

	/* Media-specific overrides */
	require_css($edk_base.'css/print.css', 'relative', NULL, 'print');
	require_css($edk_base.'css/smallscreen.css', 'relative', NULL, '(max-width: 640px)');
	require_css($edk_base.'css/speech.css', 'relative', NULL, 'speech');

	/* Don't embed style.css, as it doesn't exist in this theme */
	unset($headlines['style.css']);

	/* In XHTML, it needs to be outputted as XML processing instructions,
	 * so do that and remove it from the headlines to include. */
	if (supports_xhtml())
	{
		$alt = FALSE;
		foreach ($headlines as $file => $elem)
		{
			/* Only for CSS files.  For JS, etc, don't do anything. */
			if (preg_match('/\.css$/', $file))
			{
				$elem = str_replace(array('<link', ' rel="stylesheet"', 'title=', ' />'), array('<?xml-stylesheet', '', 'alternate="yes" title=', '?>'), $elem);

				/* The first stylesheet with a title shouldn't be alternate */
				if (!$alt && strpos($elem, 'alternate="yes"') !== FALSE)
				{
					$elem = str_replace('alternate="yes" ', '', $elem);
					$alt = TRUE;
				}

				echo $elem . "\n";
				unset($headlines[$file]);
			}
		}
	}
}

function to_ascii($str)
{
	/* Don't do anything if already ASCII or if HTML5 */
	if (!supports_xhtml() || is_ascii($str))
		return $str;
	else
	{
		require_once 'inc/locales/_charset.funcs.php';
		global $current_locale, $default_locale, $locales;
		$locale = !empty($current_locale) ? $current_locale : $default_locale;

		$charset = $locales[$locale]['charset'];
		$tm = $locales[$locale]['transliteration_map'];

		// Decode entities so that we can convert them
		$str = html_entity_decode($str, ENT_QUOTES, $charset);

		/* Try to use the locale's built-in transliteraton map, if available */
		if ($constr = strtr($str, $tm))
			$str = $constr;

		/* Try to transliterate by iconv if available */
		/* Should be after the transliteration map, in case iconv can't handle some chars */
		if (($newstr = evo_iconv_transliterate($str)) !== FALSE)
			$str = $newstr;

		/* If either/both of the steps above worked, we should return the converted string.
		 * If not, more crude conversion to follow */
		if ($constr || $newstr)
			return $str;

		/* If there's no transliteration map and iconv failed,
		 * try to replace each multibyte character with a question mark */
		if (extension_loaded('mbstring'))
		{
			$old_encoding = mb_internal_encoding();
			mb_internal_encoding($charset);

			$newstr = '';
			$l = mb_strlen($str);
			for ($i = 0; $i < $l; $i++)
			{
				$c= mb_substr($str, $i, 1);
				if (ord($c) < 128)
					$newstr .= $c;
				else
					$newstr .= '?';
			}

			mb_internal_encoding($old_encoding);
			return $newstr;
		}
		/* If all else fails, format non-ASCII names like URLs */
		else
			return replace_special_chars($str);
	}
}

global $edk_base, $skin;
$edk_base = $Blog->get_local_skins_url().$skin.'/';

init_content_type();
skin_content_header($content_type);

if (supports_xhtml())
{
	header(sprintf('Default-Style: %s', to_ascii($Skin->T_('Transitional Look'))));
	echo '<?xml version="1.0" encoding="' . $io_charset . '"?' . '>';
	echo "\n";
	edk_css_include();
	for ($i = 0; $i < 23; $i++)
		$space .= ' ';

	$dtd = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 2.0//EN"' . "\n" .
	   $space . '"' . $edk_base . 'DTD/xhtml2.dtd">';

	$langattrs = 'xml:base="'. $edk_base . '" xml:lang="' . $locale . '"';
	$htmlelem = "<html xmlns=\"http://www.w3.org/1999/xhtml\" $langattrs>";
}
else
{
	edk_css_include();
	edk_meta('http-equiv', 'Default-Style', $Skin->T_('Clear Look'));
	$dtd = '<!DOCTYPE html>';
	$langattrs ="lang=\"$locale\"";
	$htmlelem = "<html $langattrs>";
}

$params = array_merge( array(
	'auto_pilot'    => 'seo_title',
	'body_class'    => NULL,
	'generator_tag' => edk_get_meta('name', 'generator', sprintf('%s %s', $app_name, $app_version)) . '<!-- ' . $Skin->T_('Please leave this for stats') . " -->\n",
	'html_tag'      => "$dtd\n$htmlelem\n",
), $params );


echo $params['html_tag'];
?>

<head>
<?php
echo edk_get_meta('charset', $io_charset);
if (!supports_xhtml())
	skin_base_tag(); /* Base URL for this skin. You need this to fix relative links! */
	$Plugins->trigger_event( 'SkinBeginHtmlHead' );
?>

  <title><?php
		// ------------------------- TITLE FOR THE CURRENT REQUEST -------------------------
		request_title($params);
		// ------------------------------ END OF REQUEST TITLE -----------------------------
	?></title>
<?php

	edk_meta('name', 'author', $Blog->get_owner_User()->get('fullname'));
	edk_meta('property', 'DC.rights', get_copyright(array('display' => FALSE, 'license' =>  FALSE)));
	edk_meta('property', 'copyright', get_copyright(array('display' =>  FALSE, 'license' =>  FALSE)));
	edk_meta('property', 'license', get_license(array('display' => FALSE, 'format' =>  'text')));

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
			$linklang = "xml:lang=\"$lang\"";
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
	include_headlines(); /* Add javascript and css files included by plugins and the skin */

	$Blog->disp( 'blog_css', 'raw');
	$Blog->disp( 'user_css', 'raw');
	$Blog->disp_setting( 'head_includes', 'raw');
?>
</head>

<body<?php skin_body_attrs( array( 'class' => $params['body_class'] ) ); ?>>

