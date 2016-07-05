/* Remember the user's selected alternate stylesheet */

function saveDefaultStyleSheet(e)
{
  var stylesheets = document.styleSheets;
  var selected_style = null;

  /* Enumerate through the stylesheets to see which alternate was selected */
  for (i = 0; i < stylesheets.length; i++)
  {
    if (stylesheets[i].title && !stylesheets[i].disabled)
    {
      selected_style = stylesheets[i].href;
      break;
    }
  }

  if (selected_style)
  {
    if ('beforeunload' == e.type)
    {
      /* Set the cookie */
      /* The cookie expires in one week */
      var date = new Date();
      var expiry = new Date(date.setTime(date.getTime() + 7 * 24 * 60 * 60 * 1000)).toUTCString();

      document.cookie = 'Default-Style=' + selected_style + '; expires=' + expiry + '; path=' + cookie_path;
    }
  }
  else if ('load' == e.type)
    /* Hack for Gecko-based browsers, which won't apply the default stylesheet on a cached page unless it's reloaded */
    location.reload();
}

addEventListener('beforeunload', saveDefaultStyleSheet, false);
addEventListener('load', saveDefaultStyleSheet, false);
