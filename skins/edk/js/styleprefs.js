/* Remember the user's selected alternate stylesheet */

function saveDefaultStyleSheet()
{
  var stylesheets = document.styleSheets;
  var user_style;

  /* Enumerate through the stylesheets to see which alternate was selected */
  for (i = 0; i < stylesheets.length; i++)
  {
    user_style = stylesheets[i].href;
    if (stylesheets[i].title && !stylesheets[i].disabled)
      break;
  }

  /* Set the cookie */

  /* The cookie expires in one week */
  var date = new Date();
  var expiry = new Date(date.setTime(date.getTime() + 7 * 24 * 60 * 60 * 1000)).toUTCString();

  document.cookie = 'Style=' + user_style + '; expires=' + expiry + '; path=' + collection_path;
}

/* Might as well use jQuery to hopefully get around the myriad browser incompatibilities with unload event listeners */
$(window).bind('beforeunload', saveDefaultStyleSheet);
