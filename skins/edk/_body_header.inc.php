<?php
if (supports_xhtml())
{
?>
<div id="content">
<?php
}
else
{
?>
<main>
<?php
skin_include('_sidebar.inc.php');
?>
<section id="content">
<?php
}
?>
