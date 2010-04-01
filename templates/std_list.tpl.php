<?php if (!empty($Object)):
	$fields       = $Object->getMeta('fields');
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
	<table>
		<thead>
			<tr>
				<?php foreach($fields as $name => $field): ?>
					<th><?php echo humanize($name) ?></th>
				<?php endforeach; ?>
				<?php if (Permission::check('display', class_for_url($Object))): ?>
					<th></th>
				<?php endif; ?>
				<?php if (Permission::check('update', class_for_url($Object))): ?>
					<th></th>
				<?php endif; ?>
				<?php if (Permission::check('delete', class_for_url($Object))): ?>
					<th></th>
				<?php endif; ?>
			</tr>
		</thead>
		<tbody>
		<?php foreach($list as $row):
			$odd = !$odd;
			?>
			<tr class="<?php echo $odd ? '' : 'even' ?>">
				<td><?php echo implode('</td><td>', $row) ?></td>
				<?php if (Permission::check('display', class_for_url($Object))): ?>
					<td><a href="?q=<?php echo class_for_url($Object) ?>/display/<?php echo $row['id'] ?>"><img src="#SITE_LINK#images/icons/magnifier.png"></a></td>
				<?php endif; ?>
				<?php if (Permission::check('update', class_for_url($Object))): ?>
					<td><a href="?q=<?php echo class_for_url($Object) ?>/update/<?php echo $row['id'] ?>"><img src="#SITE_LINK#images/icons/pencil.png"></a></td>
				<?php endif; ?>
				<?php if (Permission::check('delete', class_for_url($Object))): ?>
					<td><a href="#" class="delete_link" id="delete_<?php echo $row['id'] ?>"><img src="#SITE_LINK#images/icons/cross.png"></a></td>
				<?php endif; ?>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	{tpl:list_paging.tpl.php}
<form class="inline" id="form_list_delete" method="post" action="?q=<?php echo class_for_url($Object) ?>/delete">
	<input type="hidden" id="delete_id" name="delete_id" value="false" />
</form>
<?php else: ?>
	No object
<?php endif; ?>