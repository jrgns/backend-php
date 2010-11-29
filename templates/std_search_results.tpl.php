<?php
	$list_start   = !isset($list_start)  ? Controller::$parameters[1] : $list_start;
	$list_length  = !isset($list_length) ? Controller::$parameters[2] : $list_length;
?>
{tpl:std_search.tpl.php}
<?php if (!empty($term)): ?>
	<p class="large">Showing results for the search for <strong>#term#</strong></p>
	<?php if (Render::checkTemplateFile($Object->getArea() . '.list.tpl.php')): ?>
		<?php echo Render::renderFile($Object->getArea() . '.list.tpl.php'); ?>
	<?php else: ?>
		{tpl:std_list.tpl.php}
	<?php endif; ?>
<?php endif; ?>