<?php if (!empty($db_object) && !empty($db_object->array['list'])):
	$list         = $db_object->array['list'];
	$list_count   = $db_object->array['list_count'];
	$area         = empty($area)        ? Controller::$area          : $area;
	$action       = empty($action)      ? Controller::$action        : $action;
	$list_start   = empty($list_start)  ? Controller::$parameters[1] : $list_start;
	$list_length  = empty($list_length) ? Controller::$parameters[2] : $list_length;
	$pages        = ceil($list_count / $list_length);
	$current_page = floor($list_start / $list_length) + 1;
	//var_dump(count($list), $list_count, $area, $action, $list_start, $list_length, $pages, $current_page);
	$odd = false;
?>
	<div id="content_container">
		<?php foreach($list as $content): ?>
			{tpl:content.preview.tpl.php}
		<?php endforeach; ?>
	</div>
	{tpl:tag.list_paging.tpl.php}
<?php else: ?>
	No Content Yet...
<?php endif; ?>
