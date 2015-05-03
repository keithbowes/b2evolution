/* Remember the user's selected alternate stylesheet */
var default_set = false;
var default_style = null;

/* Some browsers produce an error, so let's take care of that */
try
{
	default_style = document.cookie.match(/^.*Style=([^;]+).*$/)[1];
}
catch (e)
{
}

function setDefaultStyleSheet()
{
	var stylesheets = document.styleSheets;
	var user_style;

	for (i = 0; i < stylesheets.length; i++)
	{
		/* Get the style sheet selected by the user */
		if (stylesheets[i].title && !stylesheets[i].disabled)
		{
			user_style = stylesheets[i].href;
		}

		/* Enable the default style sheet */
		if (stylesheets[i].title &&
			(!default_set && stylesheets[i].href == default_style) ||
			(default_set && stylesheets[i].href == user_style))
		{
			stylesheets[i].disabled = false;
			default_style = stylesheets[i].href;
		}
		/* Disable all other alternate style sheets */
		else if (default_style && stylesheets[i].title)
			stylesheets[i].disabled = true;


		/* Set the cookie */
		if (stylesheets[i].title && stylesheets[i].href == default_style)
		{
			// The cookie expires in one week
			var date = new Date();
			var expiry = new Date(date.setTime(date.getTime() + 7 * 24 * 60 * 60 * 1000)).toUTCString();
			document.cookie = 'Style=' + default_style + '; expires=' + expiry + '; path=' + collection_path;
		}
	}


	default_set = true;
}

// Set the default style sheet on first load and then refresh user changes every second
setDefaultStyleSheet();
setInterval(setDefaultStyleSheet, 1000);
