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
<main role="main">
<section id="content">
<h2 class="section-heading"><?php echo $Skin->T_('Main Content'); ?></h2>
<?php
}
?>
