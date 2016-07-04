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
global $footer_elem;
echo '<' . $footer_elem . ' role="contentinfo" id="credit">' . "\n";
	show_footer();
echo '</' . $footer_elem . '>' . "\n";
?>

<!-- End of skin_wrapper -->
</div>
</body>
</html>