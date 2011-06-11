<?php
	$list_start   = !isset($list_start)  ? Controller::$parameters[1] : $list_start;
	$list_length  = !isset($list_length) ? Controller::$parameters[2] : $list_length;
?>
{tpl:std_search.tpl.php}
<?php if (!empty($term)): ?>
	<p class="large">Showing results for the search for <strong>#term#</strong></p>
	<?php if (Render::checkTemplateFile($db_object->getArea() . '.list.tpl.php')): ?>
		<?php echo Render::renderFile($db_object->getArea() . '.list.tpl.php', array('list_start' => $list_start, 'list_length' => $list_length)); ?>
	<?php else: ?>
		{tpl:std_list.tpl.php}
	<?php endif; ?>
<?php endif; ?>
