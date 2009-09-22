<?php if (!empty($Object)):
	$fields = $Object->getMeta('fields');
	$list = $Object->list;
	$odd = false;
	$row_width = 15;
	$title_width = 2;
	$input_width = $row_width - $title_width - 1;
?>
	<table>
		<thead>
			<tr>
			<?php foreach($fields as $name => $field): ?>
				<th><?php echo humanize($name) ?></th>
			<?php endforeach; ?>
			<td></td><td></td><td></td>
			</tr>
		</thead>
		<tbody>
		<?php foreach($list as $row):
			$odd = $odd ? false : true;
			?>
			<tr class="<?php echo $odd ? '' : 'even' ?>">
				<td><?php echo implode('</td><td>', $row) ?></td>
				<td><a href="?q=<?php echo class_for_url($Object) ?>/display/<?php echo $row['id'] ?>"><img src="<?php echo SITE_LINK ?>images/icons/magnifier.png"></a></td>
				<td><a href="?q=<?php echo class_for_url($Object) ?>/update/<?php echo $row['id'] ?>"><img src="<?php echo SITE_LINK ?>images/icons/pencil.png"></a></td>
				<td><a href="#" class="delete_link" id="delete_<?php echo $row['id'] ?>"><img src="<?php echo SITE_LINK ?>images/icons/cross.png"></a></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
<form class="inline" id="form_list_delete" method="post" action="?q=<?php echo class_for_url($Object) ?>/delete">
	<input type="hidden" id="delete_id" name="delete_id" value="false" />
</form>
<?php else: ?>
	No object
<?php endif; ?>
