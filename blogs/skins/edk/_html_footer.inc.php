<?php
/**
 * This is the HTML footer include template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-structure}
 *
 * This is meant to be included in a page template.
 * Note: This is also included in the popup: do not include site navigation!
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if (!supports_link_toolbar())
{
?>

	<script type="text/javascript">
	/*<![CDATA[*/
		/* Create a link toolbar, in those crappy browsers that don't have one */
		var cont = document.getElementById('content');
		var tb = document.createElement('div');
		tb.id = 'link-toolbar';
		tb.appendChild(document.createTextNode('[ '));

		var link, inText;
		var separator;
		var links = document.getElementsByTagName('link');
		for (var i = 0; i < links.length; i++)
		{
			if (links[i].title)
			{
				attrs = links[i].attributes;
				link = document.createElement('a');
				for (var j = 0; j < attrs.length; j++)
				{
					/* The title will be the link text */
					if ('title' != attrs[j].nodeName)
						link.setAttribute(attrs[j].nodeName, attrs[j].nodeValue);
				}

				inText = document.createTextNode(links[i].title);
				link.appendChild(inText);

				tb.appendChild(link);
				link = null;

				separator = document.createTextNode(' | ');
				tb.appendChild(separator);
				separator = null;
			}
		}

		// Replace the final bar with a closing bracket
		tb.replaceChild(document.createTextNode(' ]'), tb.lastChild);

		// Now, insert the toolbar into the document
		cont.insertBefore(tb, cont.firstChild);
	/*]]>*/
	</script>

<?php
}
	modules_call_method( 'SkinEndHtmlBody' );
	$Blog->disp_setting( 'footer_includes', 'raw' );
?>
</body>
</html>
