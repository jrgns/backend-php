<?php if (!empty($Object) && !empty($Object->list)):
	$list         = $Object->list;
	$list_count   = $Object->list_count;
	$area         = empty($area)        ? Controller::$area          : $area;
	$action       = empty($action)      ? Controller::$action        : $action;
	$list_start   = empty($list_start)  ? Controller::$parameters[0] : $list_start;
	$list_length  = empty($list_length) ? Controller::$parameters[1] : $list_length;
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
	{tpl:list_paging.tpl.php}
<?php else: ?>
	No Content Yet...
<?php endif; ?>
