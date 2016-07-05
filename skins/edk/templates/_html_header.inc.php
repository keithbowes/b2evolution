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
global $basepath, $baseurl, $content_type, $io_charset;
global $first_item, $last_item, $next_item, $prev_item;

function edk_css_include()
{
	global $Skin;
	global $current_locale, $edk_base, $headlines, $io_charset, $locales;

	$alternate_styles = array(
		'classic' => array(
			'file' => $edk_base . 'css/classic.css',
			'title' => $Skin->T_('Classic Look'),
		),
		'html5' => array(
			'file' => $edk_base . 'css/clear.css',
			'title' => $Skin->T_('Clear Look'),
		),
		'mobile' => array(
			'file' => $edk_base . 'css/one.css',
			'title' => $Skin->T_('One-column Look'),
		),
		'xhtml' => array(
			'file' => $edk_base . 'css/transitional.css',
			'title' => $Skin->T_('Transitional Look'),
		),
	);

	$visual_media = prefers_xhtml() ? 'handheld, print, projection, screen, tty, tv' : 'not speech';

	/* Main styles */
	require_css($edk_base . 'css/core.css', 'relative', NULL, 'all');
	require_css($edk_base . 'css/visual.css', 'relative', NULL, $visual_media);

	/* Sort the alternate styles based on locale */
	$locales_to_try = array(
		$locales[$current_locale]['charset'],
		str_replace('-', '_', $current_locale) . '.' . $io_charset,
		'en_US.' . $io_charset,
		'',
	);
	$old_locale = setlocale(LC_ALL, 0);
	setlocale(LC_ALL, $locales_to_try);
	uasort($alternate_styles, function($a, $b) { return strcmp($a['title'], $b['title']); });
	setlocale(LC_ALL, $old_locale);

	foreach ($alternate_styles as $style)
		require_css($style['file'], 'relative', $style['title'], $visual_media);

	/* Media-specific overrides */
	require_css($edk_base . 'css/print.css', 'relative', NULL, 'print');
	require_css($edk_base . 'css/speech.css', 'relative', NULL, 'speech');

	/* Don't embed style.css, as it doesn't exist in this theme */
	unset($headlines['style.css']);

	/* Don't embed the invalid minimal CSS file */
	unset($headlines['b2evo_base.bmin.css']);

	/* Determine the default style sheet from the Default-Style cookie if available.
	 * If not, use the above arrays. */
	if (isset($_COOKIE['Default-Style']))
		$default_style = preg_replace('/\?.+$/', '', $_COOKIE['Default-Style']);
	else
	{
		global $Session;
		if (!$Session->is_desktop_session())
			$default_style = $alternate_styles['mobile']['file'];
		elseif (prefers_xhtml())
			$default_style = $alternate_styles['xhtml']['file'];
		else
			$default_style = $alternate_styles['html5']['file'];
	}

	/* In XHTML, it needs to be outputted as XML processing instructions,
	 * so do that and remove it from the headlines to include. */
	if (prefers_xhtml())
	{
		foreach ($headlines as $file => $elem)
		{
			/* Only for CSS files.  For JS, etc, don't do anything. */
			if (preg_match('/\.css$/', $file))
			{
				$elem = str_replace(array('<link', ' rel="stylesheet"', ' />'), array('<?xml-stylesheet', '', '?>'), $elem);

				/* The default stylesheet shouldn't be alternate */
				if ($file != $default_style)
					$elem = str_replace('title=', 'alternate="yes" title=', $elem);

				echo $elem . "\n";
				unset($headlines[$file]);
			}
		}
	}
	else
	{
		/* Get the default style sheet array from the file name */
		foreach ($alternate_styles as $style)
			if ($default_style == $style['file'])
				break;

		/* Set the default style sheet, for browsers that support it
		 * (most CSS-enabled browsers do) */
		header('Default-Style: ' . $style['title']);
	}
}

function edk_get_meta($type, $value, $content = '', $extra = array())
{
	if (prefers_xhtml())
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

function get_full_url($part = '')
{
	global $Blog;

	$r = $Blog->get('url');
	$r .= empty($part) ? '' : $part;
	return $r;
}

function get_other_blogs()
{
	global $Blog, $DB;
	$DB->query('SELECT * FROM ' . $Blog->dbtablename . ' WHERE blog_ID <> ' . $Blog->ID . ' AND blog_in_bloglist = 1');

	while ($row = $DB->get_row())
	{
		$curblog = new Blog($row);
		$blogs[$curblog->ID]['blog_locale'] = $curblog->locale;
		$blogs[$curblog->ID]['blog_name'] = $curblog->name;
		$blogs[$curblog->ID]['blog_siteurl'] = $curblog->get('url');
	}

	return $blogs;
}


global $edk_base, $skin;
$edk_base = $Blog->get_local_skins_url().$skin.'/';

init_content_type();
skin_content_header($content_type);

if (prefers_xhtml())
{
	echo '<?xml version="1.0" encoding="' . $io_charset . '"?' . '>';
	echo "\n";
	edk_css_include();

	$space = '';
	for ($i = 0; $i < 23; $i++)
		$space .= ' ';

	$dtd = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 2.0//EN"' . "\n" .
	   $space . '"' . $edk_base . 'DTD/xhtml2.dtd">';

	$htmlelem = "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:base=\"$edk_base\" xml:lang=\"" . locale_lang(FALSE) . '">';
}
else
{
	edk_css_include();
	$dtd = '<!DOCTYPE html>';
	$htmlelem = '<html lang="' . locale_lang(FALSE) . '">';
}

$params = array_merge( array(
	'auto_pilot'    => 'seo_title',
	'body_class'    => NULL,
	'generator_tag' => edk_get_meta('name', 'generator', sprintf('%s %s', $app_name, $app_version)) . '<!-- ' . $Skin->T_('Please leave this for stats') . " -->\n",
	'html_tag'      => "$dtd\n$htmlelem\n",
	'viewport_tag'  => prefers_xhtml() ? NULL : '#responsive#',
), $params );


	edk_meta('name', 'author', $Blog->get_owner_User()->get('fullname'));
	edk_meta('property', 'copyright', get_copyright(array('display' =>  FALSE, 'license' =>  FALSE)));
	edk_meta('property', 'license', get_license(array('display' => FALSE, 'format' =>  'text')));

	global $cookie_path;
	add_js_headline('var cookie_path = "' .  $cookie_path . '";');
	require_js($edk_base . 'js/styleprefs.js', NULL, TRUE);

	if (prefers_xhtml())
		add_headline(sprintf('<link rel="jslicense" href="%s" title="%s" />', $Skin->T_('https://www.gnu.org/licenses/lgpl-3.0.en.html'), $Skin->T_('GNU General Public License Version 3')));

	/* Hold this info in a variable instead of querying the DB multiple times */
	$canonical_url = get_full_url(get_post_urltitle());
	if ('single' == $disp)
	{
		add_headline(sprintf('<link rel="canonical" href="%s" title="%s" />%s', $canonical_url, $Skin->T_('Canonical Permalink'), "\n"));
		add_headline(sprintf('<link rel="shortlink" href="%s" title="%s" />%s', get_tinyurl(), $Skin->T_('Shortened Permalink'),  "\n"));
	}

	if (prefers_xhtml() || supports_link_toolbar())
	{
		$comment_args = is_text_browser() ? '?show=menu&amp;redir=no' : '';
		add_headline(sprintf('<link rel="bookmark" href="%s#content" title="%s" />', $canonical_url, $Skin->T_('Main Content')));
		add_headline(sprintf('<link rel="bookmark" href="%s%s#menu" title="%s" />', $canonical_url, $comment_args, $Skin->T_('Menu')));

if ('single' == $disp)
		{
  add_headline(sprintf('<link rel="bookmark" href="%s?show=comments&amp;redir=no#comments" title="%s" />', htmlspecialchars($_SERVER['REQUEST_URI']), __('Comments')));
		}
  add_headline(sprintf('<link rel="top" href="%sdefault.php" title="%s" />', $baseurl, __('Go back to home page')));

if ('posts' != $disp)
{
	add_headline(sprintf('<link rel="up" href="%s%s" title="%s" />', $baseurl, $Blog->siteurl, htmlspecialchars($Blog->name)));
}
else
{
foreach (get_other_blogs() as $blog)
	add_headline(sprintf('<link rel="alternate" href="%1$s" title="%2$s" %3$s="%4$s" hreflang="%4$s" />%5$s', $blog['blog_siteurl'], $blog['blog_name'], prefers_xhtml() ? 'xml:lang' : 'lang', $blog['blog_locale'], "\n"));
}

if (NULL !== $first_item)
{
	add_headline(sprintf('<link rel="first" href="%s" title="↓ %s" />', get_full_url($first_item['post_urltitle']), htmlspecialchars($first_item['post_title'])));
}
if (NULL !== $last_item)
{
	add_headline(sprintf('<link rel="last" href="%s" title="%s ↑" />', get_full_url($last_item['post_urltitle']), htmlspecialchars($last_item['post_title'])));
}

if (NULL !== $prev_item)
{
  add_headline(sprintf('<link rel="prev" href="%s" title="← %s" />', $prev_item->get_permanent_url(), htmlspecialchars($prev_item->title)));
}
if (NULL !== $next_item)
{
  add_headline(sprintf('<link rel="next" href="%s" title="%s →" />', $next_item->get_permanent_url(), htmlspecialchars($next_item->title)));
}}
if ($Blog->get_setting('feed_content') != 'none')
{
	if (file_exists("$skins_path/_esf"))
	{
	add_headline(sprintf('<link rel="alternate" type="text/plain" title="ESF 1.0" href="%s%s?blog=%d&amp;tempskin=_esf" />', $baseurl, $Blog->siteurl, $Blog->ID));
	}
	if (file_exists("$skins_path/_rss3"))
	{
  add_headline(sprintf('<link rel="alternate" type="text/plain" title="RSS 3.0" href="%s%s?blog=%d&amp;tempskin=_rss3" />', $baseurl, $Blog->siteurl, $Blog->ID));
	}
}

if (method_exists($Skin, 'get_api_version'))
{
	$skin_version = $Skin->get_api_version();
	if (!is_int($skin_version) || $skin_version < 5)
		$skin_version = 5;

	$fallback_path = $basepath . 'skins_fallback_v' . $skin_version . '/';
}
/* Concession for pre-6.0 versions of b2evolution */
else
{
	$fallback_path = $skins_path;
}

/* Must be included this way rather than by skin_include so that $params will be correctly passed */
require_once $fallback_path . '_html_header.inc.php';

printf('%s<h1><a href="%s">%s</a></h1>', PHP_EOL, $Blog->dget('url'), $Blog->dget('name'));

?>

<section id="content" role="main">