<?php
if (!supports_xhtml())
{
	global $app_name, $app_version;
?>
</main>

<footer>
<?php
	$Plugins->trigger_event('SkinEndHtmlBody');

	printf($Skin->T_('<div>Powered by <cite><a href="http://www.duckduckgo.com/?q=!+%s">%s</a> %s</cite>.</div>'), $app_name, $app_name, $app_version);
	get_copyright();
?>

</footer>

<?php
}
?>
