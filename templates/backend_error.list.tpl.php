<?php if (!empty($db_object) && !empty($db_object->list)):
	$list         = $db_object->list;
	$list_count   = $db_object->list_count;
	$area         = empty($area)        ? Controller::$area          : $area;
	$action       = empty($action)      ? Controller::$action        : $action;
	$list_start   = empty($list_start)  ? Controller::$parameters[0] : $list_start;
	$list_length  = empty($list_length) ? Controller::$parameters[1] : $list_length;
	$pages        = ceil($list_count / $list_length);
	$current_page = floor($list_start / $list_length) + 1;
	//var_dump(count($list), $list_count, $area, $action, $list_start, $list_length, $pages, $current_page);
	$odd = false;
?>
	<table id="backend_error_container">
		<thead>
			<tr>
				<th>Count</th>
				<th>Error</th>
				<th>Location</th>
				<th>Mode</th>
				<th>Query</th>
				<th>Last&nbsp;Recorded</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach($db_object->list as $error): ?>
				<tr>
					<td><?php echo $error['count'] ?></td>
					<td><?php echo $error['string'] ?></td>
					<td><?php echo $error['file'] ?> line <?php echo $error['line'] ?></td>
					<td><?php echo $error['mode'] ?></td>
					<td><?php echo $error['query'] ?></td>
					<td><?php echo $error['last'] ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	{tpl:list_paging.tpl.php}
<?php else: ?>
	No Errors...
<?php endif; ?>
