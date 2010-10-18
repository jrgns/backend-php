<?php if (!empty($Object)) {
	$list         = $Object->array['list'];
	$list_count   = $Object->array['list_count'];
	$area         = empty($area)        ? Controller::$area          : $area;
	$action       = empty($action)      ? Controller::$action . '/' . Controller::$parameters[0] : $action;
	$list_start   = empty($list_start)  ? Controller::$parameters[1] : $list_start;
	$list_length  = empty($list_length) ? Controller::$parameters[2] : $list_length;
	$pages        = ceil($list_count / $list_length);
	$current_page = floor($list_start / $list_length) + 1;
	//var_dump(count($list), $list_count, $area, $action, $list_start, $list_length, $pages, $current_page);
}
if (!empty($Object->array['list']) && is_array($Object->array['list'])):
	foreach($Object->array['list'] as $item): ?>
		<?php var_dump($item); ?>
	<?php endforeach;
endif;

