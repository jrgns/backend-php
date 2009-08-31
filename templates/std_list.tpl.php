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
			</tr>
		</thead>
		<tbody>
		<?php foreach($list as $row):
			$odd = $odd ? false : true;
			?>
			<tr class="<?php echo $odd ? '' : 'even' ?>">
				<td><?php echo implode('</td><td>', $row) ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
<?php else: ?>
	No object
<?php endif; ?>
