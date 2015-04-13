<?php
global $main_elem;
$main_elem = supports_xhtml() ? 'div' : 'main';
echo "<!-- begin main content -->\n\n<$main_elem id=\"content\">";
?>
