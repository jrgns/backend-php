<?php if (!empty($db_object)):
	$id_field     = $db_object->getMeta('id_field');
	$list         = $db_object->list;
	$list_count   = $db_object->list_count;
	if ($list_count) {
		$fields   = reset($list);
	} else {
		$fields   = $db_object->getMeta('fields');
	}
	$fields       = array_keys($fields);
	$area         = empty($area)         ? Controller::$area          : $area;
	$action       = empty($action)       ? Controller::$action        : $action;
	$list_start   = !isset($list_start)  ? Controller::$parameters[0] : $list_start;
	$list_length  = !isset($list_length) ? Controller::$parameters[1] : $list_length;
	if ($list_length > 0) {
		$pages        = ceil($list_count / $list_length);
		$current_page = floor($list_start / $list_length) + 1;
	} else {
		$pages        = 0;
		$current_page = 0;
	}
	//var_dump(count($list), $list_count, $area, $action, $list_start, $list_length, $pages, $current_page); die;
	$odd = false;
?>
	<table>
		<thead>
			<tr>
				<?php foreach($fields as $name): ?>
					<th><?php echo humanize($name) ?></th>
				<?php endforeach; ?>
				<?php if (Permission::check('display', class_for_url($db_object))): ?>
					<th></th>
				<?php endif; ?>
				<?php if (Permission::check('update', class_for_url($db_object))): ?>
					<th></th>
				<?php endif; ?>
				<?php if (Permission::check('delete', class_for_url($db_object))): ?>
					<th></th>
				<?php endif; ?>
			</tr>
		</thead>
		<tbody>
			<?php if ($list): ?>
				<?php foreach($list as $row):
					$odd = !$odd;
					?>
					<tr class="<?php echo $odd ? '' : 'even' ?>">
						<td><?php echo implode('</td><td>', $row) ?></td>
						<?php if (Permission::check('display', class_for_url($db_object))): ?>
							<td><a href="?q=<?php echo class_for_url($db_object) ?>/display/<?php echo $row[$id_field] ?>"><img src="#SITE_LINK#images/icons/magnifier.png"></a></td>
						<?php endif; ?>
						<?php if (Permission::check('update', class_for_url($db_object))): ?>
							<td><a href="?q=<?php echo class_for_url($db_object) ?>/update/<?php echo $row[$id_field] ?>"><img src="#SITE_LINK#images/icons/pencil.png"></a></td>
						<?php endif; ?>
						<?php if (Permission::check('delete', class_for_url($db_object))): ?>
							<td><a href="#" class="delete_link" id="delete_<?php echo $row[$id_field] ?>"><img src="#SITE_LINK#images/icons/cross.png"></a></td>
						<?php endif; ?>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
	{tpl:list_paging.tpl.php}
<form class="inline" id="form_list_delete" method="post" action="?q=<?php echo class_for_url($db_object) ?>/delete">
	<input type="hidden" id="delete_id" name="delete_id" value="false" />
</form>
<?php else: ?>
	No object
<?php endif; ?>
